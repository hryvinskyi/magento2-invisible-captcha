/**
 * AJAX Interceptor - Monitors AJAX form submissions
 */
define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    return function () {
        const registeredForms = new Map();

        return {
            /**
             * Register form for AJAX monitoring
             */
            registerForm: function ($form, onAjaxSubmit) {
                const formId = this.getFormId($form);

                registeredForms.set(formId, {
                    form: $form,
                    callback: onAjaxSubmit,
                    formSignature: this.getFormSignature($form)
                });

                this.setupFormMonitoring($form, formId, onAjaxSubmit);
            },

            /**
             * Get or create form ID
             */
            getFormId: function ($form) {
                let formId = $form.attr('id');
                if (!formId) {
                    formId = 'form_' + Math.random().toString(36).substr(2, 9);
                    $form.attr('id', formId);
                }
                return formId;
            },

            /**
             * Get form signature for matching
             */
            getFormSignature: function ($form) {
                const fields = {};
                $form.find(':input[name]').each(function () {
                    fields[this.name] = true;
                });
                return {
                    action: $form.attr('action') || '',
                    method: ($form.attr('method') || 'GET').toUpperCase(),
                    fields: fields
                };
            },

            /**
             * Setup monitoring for various AJAX patterns
             */
            setupFormMonitoring: function ($form, formId, callback) {
                const self = this;

                // Create a single debounced callback to prevent multiple triggers
                const debouncedCallback = _.debounce(function(type) {
                    callback(type);
                }, 1000, { leading: false, trailing: true });

                // 1. Monitor form's data-ajax attribute (Magento pattern)
                if ($form.data('ajax') || $form.attr('data-mage-init')) {
                    this.monitorMagentoAjax($form, debouncedCallback);
                }

                // 2. Monitor form submit button clicks
                $form.on('click.ajaxrecaptcha', ':submit', function (e) {
                    const $button = $(this);
                    $form.data('last-submit-button', $button);

                    // Check if form will submit via AJAX
                    setTimeout(() => {
                        if ($form.data('ajax-submitted')) {
                            $form.data('ajax-submitted', false);
                            debouncedCallback('button-ajax-submit');
                        }
                    }, 50);
                });

                // 3. Monitor jQuery form plugin events
                $form.on('form-submit-validate.ajaxrecaptcha', function () {
                    $form.data('ajax-validation-started', true);
                });

                $form.on('form-submit-notify.ajaxrecaptcha', function () {
                    if ($form.data('ajax-validation-started')) {
                        $form.data('ajax-validation-started', false);
                        debouncedCallback('form-plugin-submit');
                    }
                });

                // 4. Monitor AJAX events on document - but only for THIS form
                $(document).on(`ajaxSend.ajaxrecaptcha_${formId}`, function (event, xhr, settings) {
                    if (self.isFormRelatedAjax($form, settings)) {
                        $form.data('ajax-request-sent', true);
                        $form.data('ajax-request-time', Date.now());
                    }
                });

                $(document).on(`ajaxComplete.ajaxrecaptcha_${formId}`, function (event, xhr, settings) {
                    if ($form.data('ajax-request-sent') && self.isFormRelatedAjax($form, settings)) {
                        const requestTime = $form.data('ajax-request-time') || 0;
                        const now = Date.now();

                        // Only trigger if this is a recent request (within 5 seconds)
                        if (now - requestTime < 5000) {
                            $form.data('ajax-request-sent', false);
                            // Only trigger callback for POST requests (likely submissions)
                            if (!settings.type || settings.type.toUpperCase() === 'POST') {
                                debouncedCallback('ajax-post-complete');
                            }
                        }
                    }
                });

                // 5. Monitor validation.js events (Magento 2)
                $form.on('ajax:beforeSend.ajaxrecaptcha', function (e) {
                    $form.data('ajax-submitted', true);
                });

                $form.on('ajax:complete.ajaxrecaptcha ajax:success.ajaxrecaptcha', function (e) {
                    debouncedCallback('validation-ajax-complete');
                });

                // 6. Override form submit if needed
                const originalSubmit = $form[0].submit;
                $form[0]._originalSubmit = originalSubmit;
                $form[0].submit = function () {
                    const isAjax = $form.data('ajax') || $form.hasClass('ajax-submit');
                    if (isAjax) {
                        setTimeout(() => debouncedCallback('form-submit-ajax'), 100);
                    }
                    if (originalSubmit) {
                        return originalSubmit.apply(this, arguments);
                    }
                };
            },

            /**
             * Monitor Magento-specific AJAX patterns
             */
            monitorMagentoAjax: function ($form, callback) {
                // Monitor Magento 2 form widget
                if ($form.data('mage-formData') || $form.data('form-widget')) {
                    $form.on('submit.ajaxrecaptcha', function (e) {
                        // Check if default was prevented (indicates AJAX)
                        setTimeout(() => {
                            if (e.isDefaultPrevented()) {
                                callback('magento-widget-ajax');
                            }
                        }, 10);
                    });
                }

                // Monitor customer data updates
                require(['Magento_Customer/js/customer-data'], function (customerData) {
                    $form.on('submit.ajaxrecaptcha', function () {
                        const sections = customerData.getExpiredSectionNames();
                        if (sections.length > 0) {
                            callback('customer-data-ajax');
                        }
                    });
                }, function () {
                    // Module not available, ignore
                });
            },

            /**
             * Check if AJAX request is related to form
             */
            isFormRelatedAjax: function ($form, settings) {
                if (!settings) return false;

                const formData = registeredForms.get($form.attr('id'));
                if (!formData) return false;

                // Check URL match
                const formAction = formData.formSignature.action;
                if (formAction && settings.url && settings.url.indexOf(formAction) !== -1) {
                    return true;
                }

                // Check if form data is being sent
                if (settings.data) {
                    const data = typeof settings.data === 'string' ? settings.data : $.param(settings.data);
                    const formFields = Object.keys(formData.formSignature.fields);

                    // Count matching fields
                    let matches = 0;
                    for (let field of formFields) {
                        if (data.indexOf(encodeURIComponent(field) + '=') !== -1 ||
                            data.indexOf(field + '=') !== -1) {
                            matches++;
                        }
                    }

                    // If significant number of fields match
                    return matches >= Math.min(3, formFields.length * 0.3);
                }

                return false;
            },

            /**
             * Unregister form
             */
            unregisterForm: function ($form) {
                const formId = $form.attr('id');
                if (formId) {
                    registeredForms.delete(formId);
                    $form.off('.ajaxrecaptcha');
                    $(document).off(`.ajaxrecaptcha_${formId}`);

                    // Restore original submit if modified
                    if ($form[0]._originalSubmit) {
                        $form[0].submit = $form[0]._originalSubmit;
                    }
                }
            }
        };
    };
});