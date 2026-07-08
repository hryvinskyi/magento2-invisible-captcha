/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Token Manager.
 *
 * Provider-agnostic token lifecycle. Obtains tokens through the active provider
 * strategy and writes them into the neutral wrapper field consumed server-side
 * (`config.tokenField`, default 'hryvinskyi_invisible_token'). Refresh cadence
 * comes from `config.tokenTtl`.
 *
 * Token sourcing depends on the strategy's executionMode:
 *   - 'auto'     : execute() on load + timed refresh (reCAPTCHA v3 / Enterprise)
 *   - 'onSubmit' : execute(widgetId) on demand        (reCAPTCHA v2 invisible)
 *   - 'callback' : token delivered by the widget       (v2 checkbox / Turnstile)
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (strategy, config, captchaId) {
        const tokenField = config.tokenField || 'hryvinskyi_invisible_token';
        const refreshTimers = new Map();
        const tokensLifetime = new Map();
        const refreshStates = new Map();
        let currentToken = null;
        let widgetId = null;

        return {
            // Refresh interval (ms) — provider recommended TTL.
            TOKEN_REFRESH_INTERVAL: config.tokenTtl || 90000,

            /**
             * Get the last known token.
             */
            getCurrentToken: function () {
                return currentToken;
            },

            /**
             * Store a token obtained from a widget callback.
             */
            setToken: function (token) {
                currentToken = token || null;
                return currentToken;
            },

            /**
             * Remember the rendered widget id (for execute/getResponse/reset).
             */
            setWidgetId: function (id) {
                widgetId = id;
            },

            /**
             * Get the rendered widget id.
             */
            getWidgetId: function () {
                return widgetId;
            },

            /**
             * Read the native widget response (visible providers).
             */
            getResponse: function () {
                return strategy.getResponse ? (strategy.getResponse(widgetId) || '') : '';
            },

            /**
             * Reset the widget and clear the cached token.
             */
            reset: function () {
                if (strategy.reset) {
                    strategy.reset(widgetId);
                }
                currentToken = null;
            },

            /**
             * Obtain a token through the active strategy.
             */
            generateToken: function (opts) {
                // Visible widgets: token arrives via the render callback.
                if (strategy.executionMode === 'callback') {
                    return Promise.resolve(currentToken || this.getResponse());
                }

                if (refreshStates.get(captchaId)) {
                    return Promise.resolve(currentToken);
                }

                refreshStates.set(captchaId, true);

                const execOpts = $.extend({ action: config.action, widgetId: widgetId }, opts || {});
                const self = this;

                return strategy.execute(execOpts)
                    .then(function (token) {
                        currentToken = token;
                        refreshStates.set(captchaId, false);
                        return token;
                    })
                    .catch(function (error) {
                        refreshStates.set(captchaId, false);
                        console.error('[InvisibleCaptcha] Token execution failed:', error);
                        throw error;
                    });
            },

            /**
             * Create or update the neutral hidden token field in the container.
             */
            updateTokenInput: function (container, token) {
                const $container = $(container);
                let $tokenInput = $container.find('[name="' + tokenField + '"]');

                if (!$tokenInput.length) {
                    $tokenInput = $('<input>', {
                        type: 'hidden',
                        name: tokenField,
                        'data-captcha-id': captchaId
                    });

                    if (config.action) {
                        $tokenInput.attr('data-action', config.action);
                    }

                    $container.append($tokenInput);
                }

                $tokenInput.val(token !== undefined && token !== null ? token : (currentToken || ''));

                return $tokenInput;
            },

            /**
             * Start timed auto-refresh (execute-on-load providers only).
             */
            startAutoRefresh: function (formId, container) {
                // Only 'auto' (score-based) providers benefit from a timer; an
                // 'onSubmit' invisible challenge or a 'callback' widget must not
                // be triggered on a timer.
                if (strategy.executionMode !== 'auto') {
                    return;
                }

                this.stopAutoRefresh(formId);

                const self = this;
                const refresh = function () {
                    const expirationTime = tokensLifetime.get(formId) || 0;

                    if (Date.now() > expirationTime) {
                        self.generateToken().then(function (token) {
                            if (token) {
                                self.updateTokenInput(container, token);
                                self.setFormIdTokenLifetime(formId, self.TOKEN_REFRESH_INTERVAL);
                            } else {
                                self.setFormIdTokenLifetime(formId, 0);
                            }
                        }).catch(function () {
                            self.setFormIdTokenLifetime(formId, 0);
                        });
                    }
                };

                refreshTimers.set(formId, setInterval(refresh, 100));
            },

            /**
             * Stop timed auto-refresh for a form.
             */
            stopAutoRefresh: function (formId) {
                const timer = refreshTimers.get(formId);
                if (timer) {
                    clearInterval(timer);
                    refreshTimers.delete(formId);
                }
            },

            /**
             * Refresh the token for a single submitted form.
             */
            refreshForForm: function (formId, container) {
                const refreshKey = formId + '_refreshing';

                if (refreshStates.get(refreshKey)) {
                    return Promise.resolve(currentToken);
                }

                refreshStates.set(refreshKey, true);

                const self = this;

                return this.generateToken()
                    .then(function (token) {
                        if (token) {
                            self.updateTokenInput(container, token);
                        }
                        refreshStates.set(refreshKey, false);
                        self.setFormIdTokenLifetime(formId, self.TOKEN_REFRESH_INTERVAL);
                        return token;
                    })
                    .catch(function (error) {
                        refreshStates.set(refreshKey, false);
                        self.setFormIdTokenLifetime(formId, 0);
                        console.error('[InvisibleCaptcha] Error refreshing token:', error);
                        throw error;
                    });
            },

            /**
             * Set the next refresh deadline for a form.
             *
             * @param {String} formId
             * @param {Number} [lifetime] Lifetime in ms (defaults to the TTL).
             */
            setFormIdTokenLifetime: function (formId, lifetime) {
                if (lifetime === undefined) {
                    lifetime = this.TOKEN_REFRESH_INTERVAL;
                }
                tokensLifetime.set(formId, Date.now() + lifetime);
            },

            /**
             * Cleanup.
             */
            destroy: function () {
                const self = this;
                refreshTimers.forEach(function (timer, formId) {
                    self.stopAutoRefresh(formId);
                });
                refreshStates.clear();
                currentToken = null;
            }
        };
    };
});
