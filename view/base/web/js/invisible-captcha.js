/**
 * Main Invisible Captcha Component
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    './model/invisible-captcha',
    './model/token-manager',
    './model/form-manager',
    './model/script-loader'
], function ($, ko, Component, captchaModel, TokenManager, FormManager, ScriptLoader) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_InvisibleCaptcha/invisible-captcha',
            action: '',
            siteKey: '',
            captchaId: '',
            lazyLoad: false
        },

        /**
         * Component initialization
         */
        initialize: function () {
            this._super();

            // Initialize managers
            this.tokenManager = new TokenManager(this.captchaId, this.siteKey, this.action);
            this.scriptLoader = new ScriptLoader(this.captchaId, this.siteKey);
            this.formManager = new FormManager(this.captchaId, this.tokenManager, this.lazyLoad, this.scriptLoader);

            // Initialize script loading
            this.scriptLoader.initialize();

            if (!this.lazyLoad) {
                this.scriptLoader.loadScript();
            }
        },

        /**
         * Check if reCAPTCHA is ready for a specific form
         */
        isRecaptchaLoaded: function (captchaId) {
            return captchaModel.isApiLoaded() &&
                captchaModel.initializedForms().indexOf(captchaId) !== -1;
        },

        /**
         * Public initialization method called from template
         */
        initializeCaptcha: function (element, self) {
            const $container = $(element);
            const $form = $container.closest('form');

            console.log(`Initializing Invisible Captcha for form:`, $form);

            // Store reference for later use if API not loaded
            if (!captchaModel.isApiLoaded()) {
                captchaModel.initializeForms.push({
                    element: element,
                    self: self
                });
            }

            // Initialize form
            self.formManager.initializeForm(element);

            // If API is already loaded, activate immediately
            if (captchaModel.isApiLoaded()) {
                self.formManager.activateForm($form, $container);
            } else {
                $(window).one(`recaptcha_api_ready_${self.captchaId}`, () => {
                    self.formManager.activateForm($form, $container);
                });
            }
        },

        /**
         * Component cleanup
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