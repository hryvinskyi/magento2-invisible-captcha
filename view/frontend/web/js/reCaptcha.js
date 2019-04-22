/*
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    window.reCaptchaId = 0;

    $.widget('hryvinskyi.reCaptcha', {
        captchaInitialized: false,
        reCaptchaId: null,
        tokenField: null,

        /**
         * Recaptcha create
         */
        _create: function () {
            this._initCaptcha();
        },

        /**
         * Initialize reCaptcha after first rendering
         */
        _initCaptcha: function () {
            var self = this,
                element = $(self.element),
                action = element.attr('action');

            if (typeof action === 'string') {
                var actions;

                action = action.replace(/^.*\/\/[^\/]+/, '');
                action = action.replace(/[^A-Za-z\\/]/g,'');
                actions = action.split("/");
                actions = actions.filter(function (value) {
                    return value !== null && value !== '';
                });
                actions = actions.slice(0, 3);
                action = actions.join('_');

            } else {
                action = self._getReCaptchaId() + '_action';
            }

            if (self.captchaInitialized) {
                return;
            }

            self.captchaInitialized = true;

            self.tokenField = $('<input type="hidden" id="' + self._getReCaptchaId() + '_token" ' +
                'name="hryvinskyi_invisible_token" />');

            grecaptcha.ready(function () {
                grecaptcha.execute(window.reCapchaSiteKey, {action: action}).then(function (token) {
                    self.reCaptchaCallback(token);
                });
            });

            element.append(self.tokenField);
        },

        /**
         * Recaptcha callback
         *
         * @param {String} token
         */
        reCaptchaCallback: function (token) {
            this.tokenField.val(token);
        },

        /**
         * Get reCaptcha ID
         * @returns {String}
         */
        _getReCaptchaId: function () {
            if (this.reCaptchaId === null) {
                window.reCaptchaId++;
                this.reCaptchaId = 'hryvinskyi_recaptcha_' + window.reCaptchaId
            }
            return this.reCaptchaId;
        }
    });
});
