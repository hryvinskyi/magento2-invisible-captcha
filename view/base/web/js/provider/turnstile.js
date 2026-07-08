/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Provider strategy: Cloudflare Turnstile.
 *
 * A managed widget is rendered; the token is produced by the widget (often
 * without user interaction in "interaction-only" mode) and delivered through
 * the render callback.
 */
define([], function () {
    'use strict';

    return function (config) {
        const siteKey = config.siteKey;
        const callbackName = '__hryvinskyiCaptchaOnload_' + config.provider;

        /**
         * Poll until the Turnstile API object exists, then resolve.
         */
        function whenReady(resolve) {
            if (window.turnstile) {
                resolve(window.turnstile);
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
             * Render the Turnstile widget.
             */
            render: function (el, opts) {
                opts = opts || {};

                const params = {
                    sitekey: siteKey,
                    size: config.size || 'flexible',
                    appearance: config.appearance || 'interaction-only',
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
                };

                if (config.theme) {
                    params.theme = config.theme;
                }

                return window.turnstile.render(el, params);
            },

            /**
             * No programmatic challenge — resolve the current response if present.
             */
            execute: function (opts) {
                const token = this.getResponse(opts && opts.widgetId);

                return token
                    ? Promise.resolve(token)
                    : Promise.reject(new Error('Turnstile challenge not completed'));
            },

            getResponse: function (widgetId) {
                try {
                    return window.turnstile.getResponse(widgetId) || '';
                } catch (e) {
                    return '';
                }
            },

            reset: function (widgetId) {
                try {
                    window.turnstile.reset(widgetId);
                } catch (e) {
                    // Widget not rendered yet; ignore.
                }
            }
        };
    };
});
