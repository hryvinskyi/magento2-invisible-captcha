/**
 * Form Manager - Handles form interactions and submissions
 */
define([
    'jquery',
    'underscore',
    './invisible-captcha'
], function ($, _, captchaModel) {
    'use strict';

    const SUBMIT_DISABLED_CLASS = 'hryvinskyi-recaptcha-disabled-submit';

    return function (captchaId, tokenManager, lazyLoad, scriptLoader, config = {}) {
        const pendingSubmissions = new WeakMap();
        const formStates = new Map();

        // Default settings with debug option
        const settings = $.extend({
            debug: true  // Debug setting defaults to false
        }, config);

        // Logger functions that respect debug setting
        const logger = {
            log: function(...args) {
                if (settings.debug) {
                    console.log(...args);
                }
            },
            error: function(...args) {
                if (settings.debug) {
                    console.error(...args);
                }
            },
            trace: function(...args) {
                if (settings.debug) {
                    console.trace(...args);
                }
            }
        };

        return {
            /**
             * Initialize form with captcha
             */
            initializeForm: function (container) {
                const $container = $(container);
                const $form = $container.closest('form');
                const formId = this.getFormId($form);

                // Store container reference for this form
                formStates.set(formId, {
                    container: $container,
                    form: $form,
                    initialized: false,
                    captchaId: captchaId
                });

                if (lazyLoad) {
                    this.setupLazyLoad($form, $container);
                }

                this.setupSubmitHandler($form, $container);

                if (!lazyLoad && window.grecaptcha) {
                    this.activateForm($form, $container);
                }
            },

            /**
             * Get unique form ID
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
             * Setup lazy loading
             */
            setupLazyLoad: function ($form, $container) {
                const loadOnce = _.once(() => scriptLoader.loadScript());

                // Load on interaction
                $form.on('focus blur change', ':input', loadOnce);

                // Handle submit attempts before load
                $form.on('click', ':submit', (e) => {
                    if (!this.isFormReady($form)) {
                        e.preventDefault();
                        pendingSubmissions.set($form[0], {
                            type: 'button',
                            target: e.target
                        });
                        loadOnce();
                        return false;
                    }
                });

                // Clean up inline submit handler
                const onsubmit = $form.attr('onsubmit');
                if (onsubmit && onsubmit.startsWith('return false;')) {
                    $form.attr('onsubmit', onsubmit.substring(13));
                }
            },

            /**
             * Setup form submit handler
             */
            setupSubmitHandler: function ($form, $container) {
                let isSubmitting = false;
                const formId = this.getFormId($form);
                const self = this;

                // Mark this form with its captcha ID for identification
                $form.attr('data-captcha-form-id', formId);
                $form.attr('data-captcha-id', captchaId);

                // Standard form submission
                $form.on('submit', (e) => {
                    $form.addClass(SUBMIT_DISABLED_CLASS);

                    if (!this.isFormReady($form)) {
                        e.preventDefault();
                        pendingSubmissions.set($form[0], { type: 'submit' });
                        return false;
                    }

                    // For standard (non-AJAX) form submissions
                    if (!isSubmitting && tokenManager && !$form.data('ajax')) {
                        isSubmitting = true;

                        tokenManager.refreshForForm(formId, $container).then(token => {
                            $form.removeClass(SUBMIT_DISABLED_CLASS);
                            isSubmitting = false;
                        });
                    }

                    return true;
                });

                // Setup AJAX detection for this specific form
                this.monitorFormAjax($form, $container);
            },

            /**
             * Monitor AJAX submissions for specific form
             */
            monitorFormAjax: function ($form, $container) {
                const formId = this.getFormId($form);
                const self = this;

                // Store reference to token manager
                $form.data('recaptcha-token-manager', tokenManager);
                $form.data('recaptcha-container', $container);
                $form.data('recaptcha-form-id', formId);

                // Method 1: Direct AJAX detection via jQuery
                $(document).ajaxComplete(function(event, xhr, settings) {
                    // Check if this request belongs to our form
                    if (self.isFormRequest($form, settings)) {
                        logger.log(`AJAX complete detected for form ${formId}`);
                        self.refreshFormToken($form, $container, 'ajaxComplete');
                    }
                });

                // Method 2: Form validation events (Magento)
                $form.on('ajax:complete', function(e) {
                    if (e.target === this) {
                        logger.log(`Form ajax:complete event for ${formId}`);
                        self.refreshFormToken($form, $container, 'form-ajax-event');
                    }
                });

                // Method 3: Monitor form submit with preventDefault
                $form.on('submit', function(e) {
                    if (e.isDefaultPrevented()) {
                        // Form submission was prevented, likely AJAX
                        logger.log(`Prevented submit detected for form ${formId}`);
                        setTimeout(() => {
                            self.refreshFormToken($form, $container, 'prevented-submit');
                        }, 1000);
                    }
                });

                // Method 4: Override jQuery.ajax to detect form submissions
                const originalAjax = $.ajax;
                $.ajax = function(settings) {
                    if (settings && self.isFormRequest($form, settings)) {
                        logger.trace(`$.ajax called with form ${formId} data`);
                        const originalComplete = settings.complete;
                        settings.complete = function(xhr, status) {
                            self.refreshFormToken($form, $container, 'jquery-ajax');
                            if (originalComplete) {
                                return originalComplete.apply(this, arguments);
                            }
                        };
                    }
                    return originalAjax.apply(this, arguments);
                };

                // Store reference to restore later
                $form.data('original-ajax', originalAjax);
            },

            /**
             * Check if AJAX request belongs to form
             */
            isFormRequest: function($form, settings) {
                if (!settings) return false;

                const formAction = $form.attr('action') || '';
                const formId = $form.attr('id');

                // Parse request data regardless of format
                let requestData = '';
                if (settings.data) {
                    if (typeof settings.data === 'string') {
                        requestData = settings.data;
                    } else if (typeof settings.data === 'object') {
                        requestData = $.param(settings.data);
                    }
                }

                // Look for form ID or action in URL or data - strongest indicators
                if (formId && (
                    (settings.url && settings.url.indexOf(formId) !== -1) ||
                    (requestData && requestData.indexOf(formId) !== -1)
                )) {
                    return true;
                }

                // Check URL match with action
                if (formAction && settings.url) {
                    const actionUrl = formAction.replace(/^https?:\/\/[^\/]+/, ''); // Remove domain
                    const requestUrl = settings.url.replace(/^https?:\/\/[^\/]+/, '');

                    if (requestUrl === actionUrl || requestUrl.endsWith(actionUrl)) {
                        return true;
                    }
                }

                // Check for form token that's specific to this form
                const tokenInput = $form.find(`input[name="hryvinskyi_invisible_token"]`);
                if (tokenInput.length && tokenInput.val()) {
                    const token = tokenInput.val();
                    if (requestData.indexOf(token) !== -1) {
                        return true;
                    }
                }

                return false;
            },

            /**
             * Refresh token for specific form only
             */
            refreshFormToken: function ($form, $container, source) {
                const formId = this.getFormId($form);
                const formState = formStates.get(formId);

                // Disable the form during token refresh
                $form.addClass(SUBMIT_DISABLED_CLASS);

                if (!formState || !formState.initialized) {
                    logger.log(`Form ${formId} not initialized, skipping refresh`);
                    return;
                }

                // Check if this form's captcha ID matches
                if (formState.captchaId !== captchaId) {
                    logger.log(`Form ${formId} captcha ID mismatch, skipping refresh`);
                    return;
                }

                // Check if there's already a refresh in progress
                if (formState.pendingRefresh) {
                    logger.log(`[${captchaId}] Skipping refresh for form ${formId} - refresh already in progress (source: ${source})`);
                    return;
                }

                // Mark as pending to prevent parallel refresh requests
                formState.pendingRefresh = true;

                logger.log(`[${captchaId}] Refreshing token for form ${formId} (source: ${source})`);

                if (tokenManager) {
                    tokenManager.refreshForForm(formId, $container).then(() => {
                        logger.log(`[${captchaId}] Token refreshed successfully for form ${formId}`);
                        formState.lastRefresh = Date.now();
                        formState.pendingRefresh = false;

                        // Re-enable the form after successful token refresh
                        $form.removeClass(SUBMIT_DISABLED_CLASS);
                    }).catch((error) => {
                        logger.error(`[${captchaId}] Error refreshing token for form ${formId}:`, error);
                        formState.pendingRefresh = false;

                        // Also re-enable the form if token refresh fails
                        $form.removeClass(SUBMIT_DISABLED_CLASS);
                    });
                } else {
                    logger.error(`Token manager not available for form ${formId}`);
                    formState.pendingRefresh = false;

                    // Re-enable the form if token manager is not available
                    $form.removeClass(SUBMIT_DISABLED_CLASS);
                }
            },

            /**
             * Activate form after reCAPTCHA loads
             */
            activateForm: function ($form, $container) {
                const formId = this.getFormId($form);
                const formState = formStates.get(formId);

                if (formState && formState.initialized) {
                    return;
                }

                window.grecaptcha.ready(() => {
                    tokenManager.generateToken().then(token => {
                        if (token) {
                            tokenManager.setFormIdTokenLifetime(formId, tokenManager.TOKEN_REFRESH_INTERVAL);

                            // Put token input in the recaptcha container
                            tokenManager.updateTokenInput($container, token);

                            // Start auto refresh only for this form
                            tokenManager.startAutoRefresh(formId, $container);

                            if (formState) {
                                formState.initialized = true;
                                formState.lastRefresh = Date.now();
                            }

                            if (captchaModel.initializedForms().indexOf(captchaId) === -1) {
                                captchaModel.initializedForms.push(captchaId);
                            }

                            // Handle pending submission
                            this.processPendingSubmission($form);
                        }
                    });
                });
            },

            /**
             * Process pending submission
             */
            processPendingSubmission: function ($form) {
                const pending = pendingSubmissions.get($form[0]);
                if (pending) {
                    pendingSubmissions.delete($form[0]);
                    setTimeout(() => {
                        if (pending.type === 'submit') {
                            $form.submit();
                        } else if (pending.type === 'button') {
                            $(pending.target).trigger('click');
                        }
                    }, 100);
                }
            },

            /**
             * Check if form is ready
             */
            isFormReady: function ($form) {
                const formId = this.getFormId($form);
                const formState = formStates.get(formId);

                return captchaModel.isApiLoaded() &&
                    formState &&
                    formState.initialized;
            },

            /**
             * Get all managed forms
             */
            getManagedForms: function () {
                return formStates;
            },

            /**
             * Enable or disable debug logging
             */
            setDebug: function(enable) {
                settings.debug = !!enable;
            },

            /**
             * Cleanup
             */
            destroy: function () {
                // Restore original $.ajax if we modified it
                formStates.forEach((state, formId) => {
                    if (state.form) {
                        const originalAjax = state.form.data('original-ajax');
                        if (originalAjax) {
                            $.ajax = originalAjax;
                        }

                        const namespace = `.recaptcha_${state.captchaId}_${formId}`;
                        state.form.off(namespace);
                        state.form.off('ajax:complete');
                        state.form.off('submit.recaptcha');

                        // Restore original submit if modified
                        if (state.form[0]._originalSubmit) {
                            state.form[0].submit = state.form[0]._originalSubmit;
                        }
                    }
                });
                formStates.clear();
            }
        };
    };
});