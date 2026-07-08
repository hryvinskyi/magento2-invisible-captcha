/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Provider strategy: Google reCAPTCHA v2 "I'm not a robot" checkbox.
 *
 * A visible widget is rendered; the token is produced by user interaction and
 * delivered through the render callback (no programmatic execute()).
 */
define([], function () {
    'use strict';

    return function (config) {
        const siteKey = config.siteKey;
        const callbackName = '__hryvinskyiCaptchaOnload_' + config.provider;

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
            // Token arrives from the widget callback, not a timed execute().
            executionMode: 'callback',
            requiresRender: true,
            responseParamName: config.responseParam,
            tokenTtlMs: config.tokenTtl,

            getOnloadCallbackName: function () {
                return callbackName;
            },

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
             * Render the visible checkbox widget.
             */
            render: function (el, opts) {
                opts = opts || {};

                return window.grecaptcha.render(el, {
                    sitekey: siteKey,
                    theme: config.theme || 'light',
                    size: config.size || 'normal',
                    callback: function (token) {
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
                        if (typeof opts.onError === 'function') {
                            opts.onError();
                        }
                    }
                });
            },

            /**
             * No programmatic challenge — resolve the current response if present.
             */
            execute: function (opts) {
                const token = this.getResponse(opts && opts.widgetId);

                return token
                    ? Promise.resolve(token)
                    : Promise.reject(new Error('reCAPTCHA checkbox not completed'));
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
