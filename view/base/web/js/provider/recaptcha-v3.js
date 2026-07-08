/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Provider strategy: Google reCAPTCHA v3.
 *
 * Invisible, score-based. There is no visible widget — a fresh token is
 * obtained programmatically through grecaptcha.execute(siteKey, {action}).
 *
 * Strategy contract (shared by every provider):
 *   loadScript()                 - inject the vendor API script
 *   ready(): Promise             - resolves once the API is usable
 *   render(el, opts): widgetId   - render a widget (no-op for score providers)
 *   execute(opts): Promise<token>- obtain a token
 *   getResponse(widgetId): string
 *   reset(widgetId)
 *   responseParamName, tokenTtlMs, executionMode, requiresRender, provider
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
            // Token is fetched on load and kept fresh by a timer.
            executionMode: 'auto',
            // Score-based: nothing visible to render.
            requiresRender: false,
            responseParamName: config.responseParam,
            tokenTtlMs: config.tokenTtl,

            /**
             * Namespaced global callback the API script invokes once ready.
             */
            getOnloadCallbackName: function () {
                return callbackName;
            },

            /**
             * Build the provider-specific script URL (render=siteKey for v3).
             */
            buildScriptUrl: function () {
                return config.scriptUrl +
                    '?render=' + encodeURIComponent(siteKey) +
                    '&onload=' + callbackName;
            },

            /**
             * Inject the vendor API script.
             */
            loadScript: function () {
                require([this.buildScriptUrl()]);
            },

            /**
             * Resolve once grecaptcha is ready to execute.
             */
            ready: function () {
                return new Promise(function (resolve) {
                    whenReady(resolve);
                });
            },

            /**
             * Score-based providers have no visible widget.
             */
            render: function () {
                return null;
            },

            /**
             * Execute reCAPTCHA v3 for the configured action and resolve a token.
             */
            execute: function (opts) {
                const action = (opts && opts.action) || config.action || 'submit';

                return window.grecaptcha.execute(siteKey, { action: action });
            },

            /**
             * No persistent response for score-based providers.
             */
            getResponse: function () {
                return '';
            },

            /**
             * No widget to reset.
             */
            reset: function () {
            }
        };
    };
});
