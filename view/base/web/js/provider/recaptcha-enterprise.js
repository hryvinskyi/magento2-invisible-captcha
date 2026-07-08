/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Provider strategy: Google reCAPTCHA Enterprise.
 *
 * Behaves like reCAPTCHA v3 (invisible, score-based) but loads enterprise.js
 * and uses the grecaptcha.enterprise.* API surface.
 */
define([], function () {
    'use strict';

    return function (config) {
        const siteKey = config.siteKey;
        const callbackName = '__hryvinskyiCaptchaOnload_' + config.provider;

        /**
         * Poll until grecaptcha.enterprise is ready, then resolve.
         */
        function whenReady(resolve) {
            const api = window.grecaptcha && window.grecaptcha.enterprise;

            if (api && typeof api.ready === 'function') {
                api.ready(function () {
                    resolve(api);
                });
            } else {
                setTimeout(function () {
                    whenReady(resolve);
                }, 50);
            }
        }

        return {
            provider: config.provider,
            executionMode: 'auto',
            requiresRender: false,
            responseParamName: config.responseParam,
            tokenTtlMs: config.tokenTtl,

            getOnloadCallbackName: function () {
                return callbackName;
            },

            /**
             * Enterprise also auto-renders via render=siteKey.
             */
            buildScriptUrl: function () {
                return config.scriptUrl +
                    '?render=' + encodeURIComponent(siteKey) +
                    '&onload=' + callbackName;
            },

            loadScript: function () {
                require([this.buildScriptUrl()]);
            },

            ready: function () {
                return new Promise(function (resolve) {
                    whenReady(resolve);
                });
            },

            render: function () {
                return null;
            },

            /**
             * Execute via the enterprise namespace.
             */
            execute: function (opts) {
                const action = (opts && opts.action) || config.action || 'submit';

                return window.grecaptcha.enterprise.execute(siteKey, { action: action });
            },

            getResponse: function () {
                return '';
            },

            reset: function () {
            }
        };
    };
});
