/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    './model/invisible-captcha'
], function ($, ko, Component, invisibleCaptcha) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_InvisibleCaptcha/invisible-captcha',
            action: '',
            siteKey: '',
            captchaId: '',
            lazyLoad: false
        },
        _initializedForms: [],

        /**
         * Initialization
         */
        initialize: function () {
            this._super();
            this._loadGoogleApi();
        },

        /**
         * Initialize Google ReCaptca Script
         *
         * @private
         */
        _loadGoogleApi: function () {
            var self = this;

            if (invisibleCaptcha.isApiLoad() === true) {
                $(window).trigger('recaptcha_api_ready_' + self.captchaId);

                return;
            }

            window.onloadCallbackGoogleRecapcha = function () {
                invisibleCaptcha.isApiLoaded(true);
                invisibleCaptcha.initializeForms.each(function (item) {
                    self._initializeTokenField(item.element, item.self);
                });

                $(window).trigger('recaptcha_api_ready_' + self.captchaId);
            };

            if (self.lazyLoad === false) {
                self._loadRecaptchaScript();
            }
        },

        /**
         * Load google recaptcha main script
         *
         * @private
         */
        _loadRecaptchaScript: function () {
            if (invisibleCaptcha.isApiLoaded() === false) {
                require([
                    'https://www.google.com/recaptcha/api.js?onload=onloadCallbackGoogleRecapcha&render=' + this.siteKey
                ]);

                invisibleCaptcha.isApiLoad(true);
            }
        },

        /**
         * Create form input token
         *
         * @private
         */
        _createToken: function (token, element, action, captchaId) {
            $(element).find('[name="hryvinskyi_invisible_token"]').remove();
            var tokenField = $('<input type="hidden" name="hryvinskyi_invisible_token" />');

            tokenField.val(token);
            tokenField.attr('data-action', action);
            $(element).append(tokenField);
            invisibleCaptcha.initializedForms.push(captchaId);
        },

        /**
         * Loads google API and triggers event, when loaded
         *
         * @private
         */
        _initializeTokenField: function (element, self) {
            if (invisibleCaptcha.initializedForms.indexOf(self.captchaId) === -1) {
                var execute = function () {
                    window.grecaptcha
                        .execute(self.siteKey, {action: self.action})
                        .then(function (token) {
                            $.proxy(self._createToken(token, element, self.action, self.captchaId), self);
                        });
                };

                window.grecaptcha.ready(execute);
                setInterval(execute, 90 * 1000);
                $(document).on("ajaxComplete", execute);
            }
        },

        /**
         * Check is recaptcha loaded
         *
         * @param captchaId
         * @returns {boolean}
         */
        isRecaptchaLoaded: function (captchaId) {
            return invisibleCaptcha.isApiLoaded() && invisibleCaptcha.initializedForms().indexOf(captchaId) !== -1;
        },

        /**
         * Initialize recaptcha
         *
         * @param {Dom} element
         * @param {Object} self
         */
        initializeCaptcha: function (element, self) {
            var form = $(element).closest('form');

            form.on('submit', function (e) {
                form.addClass('hryvinskyi-recaptcha-disabled-submit');
                setTimeout(function () {
                    if (invisibleCaptcha.initializedForms.indexOf(self.captchaId) !== -1) {
                        invisibleCaptcha.initializedForms.remove(self.captchaId);
                    }

                    self._initializeTokenField(element, self);
                    form.removeClass('hryvinskyi-recaptcha-disabled-submit');
                }, 50);

                return true;
            });

            if (self.lazyLoad === true) {
                form.on('focus blur change', ':input', $.proxy(self._loadRecaptchaScript, self));

                // Disable submit form
                form.on('click', ':submit', function (e) {
                    if (self.isRecaptchaLoaded(self.captchaId) === false) {
                        form.data('needClickOnSubmit', e.target);
                        e.preventDefault();
                        return false;
                    }
                });

                form.submit(function (e) {
                    if (self.isRecaptchaLoaded(self.captchaId) === false) {
                        form.data('needSubmit', true);
                        e.preventDefault();
                        return false;
                    }
                });

                // Submit form after recaptcha loaded
                invisibleCaptcha.initializedForms.subscribe(function (newValue) {
                    let isLoaded = newValue.indexOf(self.captchaId) !== -1 && invisibleCaptcha.isApiLoaded() === true;

                    if (isLoaded) {
                        if (form.data('needSubmit')) {
                            form.submit();
                            form.data('needSubmit', null);
                            return;
                        }

                        if (form.data('needClickOnSubmit')) {
                            $(form.data('needClickOnSubmit')).trigger('click');
                            form.data('needClickOnSubmit', null);
                            return;
                        }
                    }
                });

                if (form.attr('onsubmit') !== undefined) {
                    form.attr('onsubmit', form.attr('onsubmit').replace(/^.{13}/, ''));
                }

                form.removeClass('hryvinskyi-recaptcha-disabled-submit');
            }

            if ((invisibleCaptcha.isApiLoad() === true || self.lazyLoad === true) && invisibleCaptcha.isApiLoaded() !== true) {
                invisibleCaptcha.initializeForms.push({'element': element, self: self});
            } else if (invisibleCaptcha.isApiLoaded() === true) {
                self._initializeTokenField(element, self);
            } else {
                $(window).on('recaptcha_api_ready_' + self.captchaId, function () {
                    self._initializeTokenField(element, self);
                });
            }
        }
    });
});
