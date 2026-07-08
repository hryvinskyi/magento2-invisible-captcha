/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Script Loader.
 *
 * Coordinates a single load of a provider's API script per page and exposes a
 * namespaced onload callback. Load state lives in the shared model keyed by
 * provider, so multiple forms using the same provider share one download, while
 * different providers stay isolated.
 *
 * On load completion a `captcha_api_ready_<provider>` window event is fired;
 * each form component listens for its own provider's event.
 */
define([
    'jquery',
    './invisible-captcha'
], function ($, captchaModel) {
    'use strict';

    return function (strategy, config) {
        const provider = config.provider;
        const readyEvent = 'captcha_api_ready_' + provider;

        return {
            /**
             * Register the namespaced onload callback. If the provider API is
             * already loaded (e.g. by another widget), re-fire the ready event.
             */
            initialize: function () {
                if (captchaModel.isApiLoaded(provider)) {
                    $(window).trigger(readyEvent);
                    return;
                }

                const callbackName = strategy.getOnloadCallbackName();

                // Only the first widget for a provider installs the global callback.
                if (typeof window[callbackName] !== 'function') {
                    window[callbackName] = function () {
                        captchaModel.setApiLoaded(provider, true);
                        captchaModel.setApiLoading(provider, false);
                        $(window).trigger(readyEvent);
                    };
                }
            },

            /**
             * Load the provider API script exactly once per provider.
             */
            loadScript: function () {
                if (captchaModel.isApiLoaded(provider) || captchaModel.isApiLoading(provider)) {
                    return;
                }

                captchaModel.setApiLoading(provider, true);
                strategy.loadScript();
            },

            /**
             * Whether the provider API has finished loading.
             */
            isApiReady: function () {
                return captchaModel.isApiLoaded(provider);
            }
        };
    };
});
