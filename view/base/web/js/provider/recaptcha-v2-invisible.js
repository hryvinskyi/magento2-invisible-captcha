/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Provider strategy: Google reCAPTCHA v2 invisible.
 *
 * A widget is rendered (size: 'invisible') so we obtain a widgetId, then
 * grecaptcha.execute(widgetId) is called on submit. The token is delivered
 * asynchronously through the render callback, which resolves the pending
 * execute() promise.
 */
define([], function () {
    'use strict';

    return function (config) {
        const siteKey = config.siteKey;
        const callbackName = '__hryvinskyiCaptchaOnload_' + config.provider;
        // widgetId -> { resolve, reject } for the in-flight execute() call.
        const pending = {};

        /**
         * Poll until grecaptcha is ready, then resolve.
         */
        function whenReady(resolve) {
            if (window.grecaptcha && typeof window.grecaptcha.ready === 'function') {
                window.grecaptcha.ready(function () {
                    resolve(window.grecaptcha);
                });
            } else {
                setTimeout(function () {
                    whenReady(resolve);
                }, 50);
            }
        }

        return {
            provider: config.provider,
            // Token is fetched programmatically when the form is submitted.
            executionMode: 'onSubmit',
            // Must render to obtain a widgetId (badge only).
            requiresRender: true,
            responseParamName: config.responseParam,
            tokenTtlMs: config.tokenTtl,

            getOnloadCallbackName: function () {
                return callbackName;
            },

            /**
             * Explicit rendering so we control when execute() runs.
             */
            buildScriptUrl: function () {
                return config.scriptUrl + '?onload=' + callbackName + '&render=explicit';
            },

            loadScript: function () {
                require([this.buildScriptUrl()]);
            },

            ready: function () {
                return new Promise(function (resolve) {
                    whenReady(resolve);
                });
            },

            /**
             * Render the invisible widget; wire callbacks to resolve execute().
             */
            render: function (el, opts) {
                opts = opts || {};

                const widgetId = window.grecaptcha.render(el, {
                    sitekey: siteKey,
                    size: 'invisible',
                    badge: config.badge || 'bottomright',
                    callback: function (token) {
                        const p = pending[widgetId];
                        if (p) {
                            delete pending[widgetId];
                            p.resolve(token);
                        }
                        if (typeof opts.onToken === 'function') {
                            opts.onToken(token);
                        }
                    },
                    'expired-callback': function () {
                        if (typeof opts.onExpired === 'function') {
                            opts.onExpired();
                        }
                    },
                    'error-callback': function () {
                        const p = pending[widgetId];
                        if (p) {
                            delete pending[widgetId];
                            p.reject(new Error('reCAPTCHA v2 invisible error'));
                        }
                        if (typeof opts.onError === 'function') {
                            opts.onError();
                        }
                    }
                });

                return widgetId;
            },

            /**
             * Trigger the invisible challenge and resolve with the token via callback.
             */
            execute: function (opts) {
                const widgetId = opts && opts.widgetId;

                return new Promise(function (resolve, reject) {
                    if (widgetId === undefined || widgetId === null) {
                        reject(new Error('Missing widgetId for reCAPTCHA v2 invisible'));
                        return;
                    }

                    pending[widgetId] = { resolve: resolve, reject: reject };

                    try {
                        // Reset to guarantee a single-use, fresh token.
                        window.grecaptcha.reset(widgetId);
                        window.grecaptcha.execute(widgetId);
                    } catch (e) {
                        delete pending[widgetId];
                        reject(e);
                    }
                });
            },

            getResponse: function (widgetId) {
                try {
                    return window.grecaptcha.getResponse(widgetId) || '';
                } catch (e) {
                    return '';
                }
            },

            reset: function (widgetId) {
                try {
                    window.grecaptcha.reset(widgetId);
                } catch (e) {
                    // Widget not rendered yet; ignore.
                }
            }
        };
    };
});
