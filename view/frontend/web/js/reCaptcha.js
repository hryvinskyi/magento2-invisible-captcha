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
                action = element.attr('action'),
                tokenField;

            if (typeof action === 'string') {
                action = action.replace(/\W+(?!$)/g, '_')
                    .replace(/(^\w+:|^)\/\//, '_')
                    .replace('/', '');
            } else {
                action = this._getReCaptchaId() + '_action';
            }

            console.log(action);

            if (this.captchaInitialized) {
                return;
            }

            this.captchaInitialized = true;

            tokenField = $('<input type="hidden" id="' + this._getReCaptchaId() + '_token" ' +
                'name="hryvinskyi_invisible_token" />');

            grecaptcha.ready(function () {
                grecaptcha.execute(window.reCapchaSiteKey, {action: action}).then(function (token) {
                    tokenField.val(token);
                });
            });

            element.append(tokenField);
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
