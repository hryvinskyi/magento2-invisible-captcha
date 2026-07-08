/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Invisible Captcha UI component.
 *
 * Provider-agnostic entry point. Resolves the matching client-side strategy via
 * the registry (by `this.provider`) and wires the per-form managers. The block
 * injects the per-form config produced by ClientConfigProvider / the active
 * provider's getRenderConfig().
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    './model/invisible-captcha',
    './model/token-manager',
    './model/form-manager',
    './model/script-loader',
    './provider/registry'
], function ($, ko, Component, captchaModel, TokenManager, FormManager, ScriptLoader, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_InvisibleCaptcha/invisible-captcha',
            provider: '',
            siteKey: '',
            scriptUrl: '',
            responseParam: '',
            tokenTtl: 90000,
            isScoreBased: false,
            supportsAction: false,
            action: '',
            widgetMode: 'score',
            theme: '',
            size: '',
            badge: 'bottomright',
            appearance: '',
            hideBadge: false,
            hideBadgeText: '',
            lazyLoad: false,
            isDisabledSubmitForm: false,
            tokenField: 'hryvinskyi_invisible_token',
            captchaId: ''
        },

        /**
         * Component initialization.
         */
        initialize: function () {
            this._super();

            if (!this.provider || !registry.has(this.provider)) {
                console.error('[InvisibleCaptcha] Unknown or missing provider:', this.provider);
                return this;
            }

            this.captchaConfig = this.buildConfig();

            // Resolve the provider strategy and wire the managers.
            this.strategy = registry.create(this.provider, this.captchaConfig);
            this.tokenManager = new TokenManager(this.strategy, this.captchaConfig, this.captchaId);
            this.scriptLoader = new ScriptLoader(this.strategy, this.captchaConfig);
            this.formManager = new FormManager(
                this.captchaId,
                this.tokenManager,
                this.strategy,
                this.scriptLoader,
                this.captchaConfig
            );

            this.scriptLoader.initialize();

            if (!this.captchaConfig.lazyLoad) {
                this.scriptLoader.loadScript();
            }

            return this;
        },

        /**
         * Collect the client config from component properties.
         */
        buildConfig: function () {
            return {
                provider: this.provider,
                siteKey: this.siteKey,
                scriptUrl: this.scriptUrl,
                responseParam: this.responseParam,
                tokenTtl: this.tokenTtl,
                isScoreBased: this.isScoreBased,
                supportsAction: this.supportsAction,
                action: this.action,
                widgetMode: this.widgetMode,
                theme: this.theme,
                size: this.size,
                badge: this.badge,
                appearance: this.appearance,
                hideBadge: this.hideBadge,
                hideBadgeText: this.hideBadgeText,
                lazyLoad: this.lazyLoad,
                isDisabledSubmitForm: this.isDisabledSubmitForm,
                tokenField: this.tokenField || 'hryvinskyi_invisible_token',
                captchaId: this.captchaId,
                debug: this.debug
            };
        },

        /**
         * Whether the provider API is ready for this component.
         */
        isCaptchaReady: function () {
            return !!this.scriptLoader && this.scriptLoader.isApiReady();
        },

        /**
         * Public initialization method called from the template (afterRender).
         */
        initializeCaptcha: function (element, self) {
            if (!self.strategy) {
                return;
            }

            const $container = $(element);
            const $form = $container.closest('form');

            self.formManager.initializeForm(element);

            // Activate now if ready, otherwise wait for this provider's event.
            if (self.scriptLoader.isApiReady()) {
                self.formManager.activateForm($form, $container);
            } else {
                $(window).one('captcha_api_ready_' + self.provider, function () {
                    self.formManager.activateForm($form, $container);
                });
            }
        },

        /**
         * Component cleanup.
         */
        destroy: function () {
            if (this.tokenManager) {
                this.tokenManager.destroy();
            }
            if (this.formManager) {
                this.formManager.destroy();
            }
            this._super();
        }
    });
});
