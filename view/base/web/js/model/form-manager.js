/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Form Manager.
 *
 * Provider-aware form wiring: widget rendering, submit gating, AJAX-resubmit
 * token refresh and lazy loading. Behaviour branches on the strategy's
 * executionMode:
 *   - 'auto'     : token executed on load and kept fresh; refreshed before
 *                  every (re)submission (reCAPTCHA v3 / Enterprise).
 *   - 'onSubmit' : token executed on submit, then the form is resubmitted
 *                  (reCAPTCHA v2 invisible).
 *   - 'callback' : token produced by a visible widget; submission is gated
 *                  until a token exists (reCAPTCHA v2 checkbox / Turnstile).
 */
define([
    'jquery',
    'underscore',
    './invisible-captcha'
], function ($, _, captchaModel) {
    'use strict';

    const SUBMIT_DISABLED_CLASS = 'hryvinskyi-captcha-disabled-submit';

    return function (captchaId, tokenManager, strategy, scriptLoader, config) {
        config = config || {};

        const provider = config.provider;
        const lazyLoad = !!config.lazyLoad;
        const gateSubmit = !!config.isDisabledSubmitForm;
        const mode = strategy.executionMode; // 'auto' | 'onSubmit' | 'callback'

        const pendingSubmissions = new WeakMap();
        const formStates = new Map();

        const settings = $.extend({ debug: false }, config);

        // Debug-gated logger.
        const logger = {
            log: function () {
                if (settings.debug) {
                    console.log.apply(console, ['[InvisibleCaptcha]'].concat(Array.prototype.slice.call(arguments)));
                }
            },
            error: function () {
                if (settings.debug) {
                    console.error.apply(console, ['[InvisibleCaptcha]'].concat(Array.prototype.slice.call(arguments)));
                }
            },
            trace: function () {
                if (settings.debug) {
                    console.trace.apply(console, arguments);
                }
            }
        };

        return {
            /**
             * Initialize form with captcha.
             */
            initializeForm: function (container) {
                const $container = $(container);
                const $form = $container.closest('form');
                const formId = this.getFormId($form);

                formStates.set(formId, {
                    container: $container,
                    form: $form,
                    initialized: false,
                    captchaId: captchaId,
                    widgetId: null
                });

                // Gate submission until the captcha is ready / solved.
                if (gateSubmit) {
                    $form.addClass(SUBMIT_DISABLED_CLASS);
                }

                if (lazyLoad) {
                    this.setupLazyLoad($form, $container);
                }

                this.setupSubmitHandler($form, $container);

                if (!lazyLoad && scriptLoader.isApiReady()) {
                    this.activateForm($form, $container);
                }
            },

            /**
             * Get (or assign) a unique form id.
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
             * Setup lazy loading triggers.
             */
            setupLazyLoad: function ($form, $container) {
                const self = this;
                const loadOnce = _.once(function () {
                    scriptLoader.loadScript();
                });

                // Load on first interaction.
                $form.on('focus blur change', ':input', loadOnce);

                // Handle submit attempts before the script has loaded.
                $form.on('click', ':submit', function (e) {
                    if (!self.isFormReady($form)) {
                        e.preventDefault();
                        pendingSubmissions.set($form[0], {
                            type: 'button',
                            target: e.target
                        });
                        loadOnce();
                        return false;
                    }
                });

                // Strip any legacy inline `return false;` onsubmit guard left in
                // stale full-page-cache HTML from older versions (current code
                // gates submission via the disabled-submit class + CSS instead).
                const onsubmit = $form.attr('onsubmit');
                if (onsubmit && onsubmit.startsWith('return false;')) {
                    $form.attr('onsubmit', onsubmit.substring(13));
                }
            },

            /**
             * Setup the provider-aware submit handler.
             */
            setupSubmitHandler: function ($form, $container) {
                let isSubmitting = false;
                const formId = this.getFormId($form);
                const self = this;

                $form.attr('data-captcha-form-id', formId);
                $form.attr('data-captcha-id', captchaId);

                $form.on('submit', function (e) {
                    // Allow a programmatic resubmit (after on-submit token fetch).
                    if ($form.data('captcha-resubmit')) {
                        $form.data('captcha-resubmit', false);
                        return true;
                    }

                    // Not ready: gate and queue until the API/widget is available.
                    if (!self.isFormReady($form)) {
                        e.preventDefault();
                        $form.addClass(SUBMIT_DISABLED_CLASS);
                        pendingSubmissions.set($form[0], { type: 'submit' });
                        if (lazyLoad) {
                            scriptLoader.loadScript();
                        }
                        return false;
                    }

                    // Visible widgets: a token must already exist.
                    if (mode === 'callback') {
                        const token = tokenManager.getCurrentToken() || tokenManager.getResponse();
                        if (!token) {
                            e.preventDefault();
                            $form.addClass(SUBMIT_DISABLED_CLASS);
                            logger.log('Form ' + formId + ' blocked: captcha not completed');
                            return false;
                        }
                        tokenManager.updateTokenInput($container, token);
                        $form.removeClass(SUBMIT_DISABLED_CLASS);
                        return true;
                    }

                    // reCAPTCHA v2 invisible: fetch token, then resubmit.
                    if (mode === 'onSubmit') {
                        if (isSubmitting || $form.data('ajax')) {
                            return true;
                        }
                        isSubmitting = true;
                        $form.addClass(SUBMIT_DISABLED_CLASS);
                        e.preventDefault();

                        tokenManager.refreshForForm(formId, $container).then(function (token) {
                            isSubmitting = false;
                            $form.removeClass(SUBMIT_DISABLED_CLASS);
                            if (token) {
                                $form.data('captcha-resubmit', true);
                                $form.submit();
                            }
                        }).catch(function (error) {
                            isSubmitting = false;
                            $form.removeClass(SUBMIT_DISABLED_CLASS);
                            logger.error('Token fetch failed for form ' + formId + ':', error);
                        });

                        return false;
                    }

                    // Score-based 'auto': token already present; refresh for next.
                    $form.addClass(SUBMIT_DISABLED_CLASS);
                    if (!isSubmitting && !$form.data('ajax')) {
                        isSubmitting = true;
                        tokenManager.refreshForForm(formId, $container).then(function () {
                            $form.removeClass(SUBMIT_DISABLED_CLASS);
                            isSubmitting = false;
                        }).catch(function () {
                            $form.removeClass(SUBMIT_DISABLED_CLASS);
                            isSubmitting = false;
                        });
                    } else {
                        $form.removeClass(SUBMIT_DISABLED_CLASS);
                    }

                    return true;
                });

                this.monitorFormAjax($form, $container);
            },

            /**
             * Monitor AJAX submissions for this specific form.
             */
            monitorFormAjax: function ($form, $container) {
                const formId = this.getFormId($form);
                const self = this;

                $form.data('captcha-token-manager', tokenManager);
                $form.data('captcha-container', $container);
                $form.data('captcha-form-id', formId);

                // Method 1: direct AJAX detection via jQuery.
                $(document).ajaxComplete(function (event, xhr, settings) {
                    if (self.isFormRequest($form, settings)) {
                        logger.log('AJAX complete detected for form ' + formId);
                        self.refreshFormToken($form, $container, 'ajaxComplete');
                    }
                });

                // Method 2: Magento form validation events.
                $form.on('ajax:complete', function (e) {
                    if (e.target === this) {
                        logger.log('Form ajax:complete event for ' + formId);
                        self.refreshFormToken($form, $container, 'form-ajax-event');
                    }
                });

                // Method 3: monitor prevented (likely AJAX) submits.
                $form.on('submit', function (e) {
                    if (e.isDefaultPrevented()) {
                        logger.log('Prevented submit detected for form ' + formId);
                        setTimeout(function () {
                            self.refreshFormToken($form, $container, 'prevented-submit');
                        }, 1000);
                    }
                });

                // Method 4: wrap jQuery.ajax to detect form submissions.
                const originalAjax = $.ajax;
                $.ajax = function (settings) {
                    if (settings && self.isFormRequest($form, settings)) {
                        logger.trace('$.ajax called with form ' + formId + ' data');
                        const originalComplete = settings.complete;
                        settings.complete = function () {
                            self.refreshFormToken($form, $container, 'jquery-ajax');
                            if (originalComplete) {
                                return originalComplete.apply(this, arguments);
                            }
                        };
                    }
                    return originalAjax.apply(this, arguments);
                };

                // Keep a reference so it can be restored on destroy.
                $form.data('captcha-original-ajax', originalAjax);
            },

            /**
             * Check whether an AJAX request belongs to this form.
             */
            isFormRequest: function ($form, settings) {
                if (!settings) {
                    return false;
                }

                const formAction = $form.attr('action') || '';
                const formId = $form.attr('id');
                const tokenField = config.tokenField || 'hryvinskyi_invisible_token';
                let requestData = '';

                if (settings.data) {
                    if (typeof settings.data === 'string') {
                        requestData = settings.data;
                    } else if (settings.data instanceof FormData) {
                        const parts = [];
                        for (const [key, value] of settings.data.entries()) {
                            parts.push(key + '=' + value);
                        }
                        requestData = parts.join('&');
                    } else if (
                        typeof settings.data === 'object' &&
                        !(settings.data instanceof $) &&
                        !(settings.data instanceof Element)
                    ) {
                        try {
                            requestData = $.param(settings.data);
                        } catch (e) {
                            logger.error('Error serializing settings.data:', e);
                        }
                    }
                }

                // Strongest indicators: form id present in URL or data.
                if (formId && (
                    (settings.url && settings.url.indexOf(formId) !== -1) ||
                    (requestData && requestData.indexOf(formId) !== -1)
                )) {
                    return true;
                }

                // URL match with the form action.
                if (formAction && settings.url) {
                    const actionUrl = formAction.replace(/^https?:\/\/[^\/]+/, '');
                    const requestUrl = settings.url.replace(/^https?:\/\/[^\/]+/, '');

                    if (requestUrl === actionUrl || requestUrl.endsWith(actionUrl)) {
                        return true;
                    }
                }

                // The neutral captcha token is present in the payload.
                const tokenInput = $form.find('input[name="' + tokenField + '"]');
                if (tokenInput.length && tokenInput.val()) {
                    if (requestData.indexOf(tokenInput.val()) !== -1) {
                        return true;
                    }
                }

                return false;
            },

            /**
             * Refresh / reset the token for a single form (provider-aware).
             */
            refreshFormToken: function ($form, $container, source) {
                const formId = this.getFormId($form);
                const formState = formStates.get(formId);

                $form.addClass(SUBMIT_DISABLED_CLASS);

                if (!formState || !formState.initialized) {
                    logger.log('Form ' + formId + ' not initialized, skipping refresh');
                    return;
                }

                if (formState.captchaId !== captchaId) {
                    logger.log('Form ' + formId + ' captcha id mismatch, skipping refresh');
                    return;
                }

                if (formState.pendingRefresh) {
                    logger.log('[' + captchaId + '] Refresh already in progress for ' + formId + ' (source: ' + source + ')');
                    return;
                }

                formState.pendingRefresh = true;
                logger.log('[' + captchaId + '] Refreshing token for form ' + formId + ' (source: ' + source + ')');

                // Visible widgets: the token is single-use. Reset so the user
                // re-verifies; the widget callback re-enables submission.
                if (mode === 'callback') {
                    tokenManager.reset();
                    tokenManager.updateTokenInput($container, '');
                    formState.lastRefresh = Date.now();
                    formState.pendingRefresh = false;
                    if (!gateSubmit) {
                        $form.removeClass(SUBMIT_DISABLED_CLASS);
                    }
                    return;
                }

                // Execute-based providers ('auto' / 'onSubmit'): fetch a fresh token.
                tokenManager.refreshForForm(formId, $container).then(function () {
                    logger.log('[' + captchaId + '] Token refreshed for form ' + formId);
                    formState.lastRefresh = Date.now();
                    formState.pendingRefresh = false;
                    $form.removeClass(SUBMIT_DISABLED_CLASS);
                }).catch(function (error) {
                    logger.error('[' + captchaId + '] Error refreshing token for form ' + formId + ':', error);
                    formState.pendingRefresh = false;
                    $form.removeClass(SUBMIT_DISABLED_CLASS);
                });
            },

            /**
             * Activate the form once the provider API is ready.
             */
            activateForm: function ($form, $container) {
                const formId = this.getFormId($form);
                const formState = formStates.get(formId);
                const self = this;

                if (formState && formState.initialized) {
                    return;
                }

                strategy.ready().then(function () {
                    // Visible / explicit widgets need an actual render.
                    if (strategy.requiresRender) {
                        const widgetId = strategy.render($container[0], {
                            onToken: function (token) {
                                tokenManager.setToken(token);
                                tokenManager.updateTokenInput($container, token);
                                $form.removeClass(SUBMIT_DISABLED_CLASS);
                            },
                            onExpired: function () {
                                tokenManager.setToken(null);
                                tokenManager.updateTokenInput($container, '');
                                if (gateSubmit) {
                                    $form.addClass(SUBMIT_DISABLED_CLASS);
                                }
                            },
                            onError: function () {
                                logger.error('Widget error for form ' + formId);
                            }
                        });

                        if (formState) {
                            formState.widgetId = widgetId;
                        }
                        tokenManager.setWidgetId(widgetId);
                    }

                    if (mode === 'auto') {
                        // Score-based: fetch the initial token and keep it fresh.
                        tokenManager.generateToken().then(function (token) {
                            if (token) {
                                tokenManager.updateTokenInput($container, token);
                                tokenManager.setFormIdTokenLifetime(formId, tokenManager.TOKEN_REFRESH_INTERVAL);
                                tokenManager.startAutoRefresh(formId, $container);
                            }
                            self.finishActivation($form, formState, formId);
                        }).catch(function (error) {
                            logger.error('Initial token fetch failed for form ' + formId + ':', error);
                            self.finishActivation($form, formState, formId);
                        });
                    } else {
                        // 'onSubmit' (token on submit) / 'callback' (token via widget).
                        self.finishActivation($form, formState, formId);
                    }
                });
            },

            /**
             * Mark a form as activated and flush any queued submission.
             */
            finishActivation: function ($form, formState, formId) {
                if (formState) {
                    formState.initialized = true;
                    formState.lastRefresh = Date.now();
                }

                captchaModel.markFormInitialized(provider, captchaId);

                // 'callback' widgets stay gated until the user solves them; for
                // every other mode the form can be released here.
                if (mode !== 'callback') {
                    $form.removeClass(SUBMIT_DISABLED_CLASS);
                }

                this.processPendingSubmission($form);
            },

            /**
             * Re-trigger a queued submission once the form became ready.
             */
            processPendingSubmission: function ($form) {
                const pending = pendingSubmissions.get($form[0]);
                if (pending) {
                    pendingSubmissions.delete($form[0]);
                    setTimeout(function () {
                        if (pending.type === 'submit') {
                            $form.submit();
                        } else if (pending.type === 'button') {
                            $(pending.target).trigger('click');
                        }
                    }, 100);
                }
            },

            /**
             * Whether the form's captcha is ready (API loaded + activated).
             */
            isFormReady: function ($form) {
                const formId = this.getFormId($form);
                const formState = formStates.get(formId);

                return scriptLoader.isApiReady() &&
                    !!formState &&
                    formState.initialized;
            },

            /**
             * Get all managed form states.
             */
            getManagedForms: function () {
                return formStates;
            },

            /**
             * Enable or disable debug logging.
             */
            setDebug: function (enable) {
                settings.debug = !!enable;
            },

            /**
             * Cleanup.
             */
            destroy: function () {
                formStates.forEach(function (state, formId) {
                    if (state.form) {
                        const originalAjax = state.form.data('captcha-original-ajax');
                        if (originalAjax) {
                            $.ajax = originalAjax;
                        }

                        const namespace = '.captcha_' + state.captchaId + '_' + formId;
                        state.form.off(namespace);
                        state.form.off('ajax:complete');
                        state.form.off('submit.captcha');

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
