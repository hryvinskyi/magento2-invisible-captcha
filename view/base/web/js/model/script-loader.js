/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

/**
 * Script Loader - Handles reCAPTCHA script loading
 */
define([
    'jquery',
    './invisible-captcha'
], function ($, captchaModel) {
    'use strict';

    const RECAPTCHA_API_URL = 'https://www.google.com/recaptcha/api.js';

    return function (captchaId, siteKey, formManager) {
        return {
            /**
             * Initialize reCAPTCHA loading
             */
            initialize: function () {
                if (captchaModel.isApiLoad()) {
                    $(window).trigger(`recaptcha_api_ready_${captchaId}`);
                    return;
                }

                window.onloadCallbackGoogleRecapcha = () => {
                    captchaModel.isApiLoaded(true);

                    // Process all pending forms
                    captchaModel.initializeForms().forEach(item => {
                        if (item.self && item.self.formManager) {
                            const $container = $(item.element);
                            const $form = $container.closest('form');
                            item.self.formManager.activateForm($form, $container);
                        }
                    });

                    // Clear the queue
                    captchaModel.initializeForms.removeAll();

                    $(window).trigger(`recaptcha_api_ready_${captchaId}`);
                };
            },

            /**
             * Load reCAPTCHA script
             */
            loadScript: function () {
                if (!captchaModel.isApiLoaded() && !captchaModel.isApiLoad()) {
                    captchaModel.isApiLoad(true);
                    require([`${RECAPTCHA_API_URL}?onload=onloadCallbackGoogleRecapcha&render=${siteKey}`]);
                }
            },

            /**
             * Check if API is ready
             */
            isApiReady: function () {
                return captchaModel.isApiLoaded();
            }
        };
    };
});