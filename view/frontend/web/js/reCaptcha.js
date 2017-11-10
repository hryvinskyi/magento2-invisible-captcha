/*
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */
define([
        'jquery',
        'ko',
        'Script_InvisibleCaptcha/js/registry',
        'jquery/ui'
    ], function ($, ko, registry) {
        'use strict';

        window.reCaptchaId = 0;

        $.widget('script.reCaptcha', {

            options: {
                callback: null
            },

            captchaInitialized: false,
            reCaptchaId: null,
            widgetId: null,
            tokenField: {},
            $parentForm : {},

            /**
             * Recaptcha create
             */
            _create : function () {
                this._initCaptcha();
            },

            /**
             * Recaptcha reset
             * use: $(form).reCaptcha('reset');
             */
            reset : function () {
                this._reset();
            },

            _reset : function () {
                grecaptcha.reset(this.widgetId);
            },

            /**
             * Recaptcha callback
             * @param {String} token
             * @param {Object} callback
             */
            _reCaptchaCallback: function (token, callback) {
                if (!$.isFunction(callback)) {
                    if(this.$parentForm.validation() && this.$parentForm.validation('isValid')) {
                        this.tokenField.val(token);
                        this.$parentForm.submit();
                    }
                } else {
                    callback(this, token);
                }

                this._reset();
            },

            /**
             * Initialize reCaptcha after first rendering
             */
            _initCaptcha: function () {
                var me = this,
                    widgetId,
                    listeners;

                if (this.captchaInitialized) {
                    return;
                }

                this.captchaInitialized = true;

                var button = me.element.find('[type="submit"]')[0];

                widgetId = grecaptcha.render(button, {
                    'sitekey': window.reCapchaSiteKey,
                    'callback': function (token) {
                        me._reCaptchaCallback(token, me.options.callback);
                    }
                });

                me.element.submit(function (event) {
                    if (!me.tokenField.val()) {
                        grecaptcha.execute(widgetId);
                        event.preventDefault(event);
                        event.stopImmediatePropagation();
                    }
                });

                listeners = $._data(me.element[0], 'events').submit;
                listeners.unshift(listeners.pop());

                this.tokenField = $('<input type="hidden" id="' + this._getReCaptchaId() + '_token" name="script_invisible_token" value="" />');
                this.$parentForm = me.element;
                this.$parentForm.append(this.tokenField);
                this.widgetId = widgetId;

                registry.ids.push(this._getReCaptchaId());
                registry.captchaList.push(widgetId);
                registry.tokenFields.push(this.tokenField);
            },

            /**
             * Get reCaptcha ID
             * @returns {String}
             */
            _getReCaptchaId: function () {
                if(this.reCaptchaId === null) {
                    window.reCaptchaId++;
                    this.reCaptchaId = 'script_recaptcha_' + window.reCaptchaId
                }
                return this.reCaptchaId;
            }
        });
    }
);