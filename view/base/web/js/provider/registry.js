/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Provider strategy registry.
 *
 * Resolves the correct client-side captcha strategy factory by provider code
 * (matching Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface::CODE_*)
 * and builds a configured strategy instance.
 */
define([
    './recaptcha-v3',
    './recaptcha-enterprise',
    './recaptcha-v2-invisible',
    './recaptcha-v2-checkbox',
    './turnstile'
], function (recaptchaV3, recaptchaEnterprise, recaptchaV2Invisible, recaptchaV2Checkbox, turnstile) {
    'use strict';

    // Provider code -> strategy factory.
    const factories = {
        recaptcha_v3: recaptchaV3,
        recaptcha_enterprise: recaptchaEnterprise,
        recaptcha_v2_invisible: recaptchaV2Invisible,
        recaptcha_v2_checkbox: recaptchaV2Checkbox,
        turnstile: turnstile
    };

    return {
        /**
         * Whether a strategy is registered for the given provider code.
         */
        has: function (provider) {
            return Object.prototype.hasOwnProperty.call(factories, provider);
        },

        /**
         * Build a configured strategy instance for the given provider code.
         *
         * @param {String} provider Provider code.
         * @param {Object} config Per-form client config (see ClientConfigProvider).
         * @returns {Object} Strategy instance.
         */
        create: function (provider, config) {
            const factory = factories[provider];

            if (!factory) {
                throw new Error('[InvisibleCaptcha] Unknown provider: ' + provider);
            }

            return factory(config);
        }
    };
});
