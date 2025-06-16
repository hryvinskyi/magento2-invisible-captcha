/**
 * Token Manager - Handles token generation and refresh
 */
define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    const TOKEN_INPUT_NAME = 'hryvinskyi_invisible_token';

    return function (captchaId, siteKey, action) {
        const refreshTimers = new Map();
        const tokensLifetime = new Map();
        const refreshStates = new Map();
        let currentToken = null;

        return {
            // Recaptcha token expires every 2 minutes, but we refresh it every 90 seconds to ensure we always have a valid token
            TOKEN_REFRESH_INTERVAL: 90000,

            /**
             * Get current token
             */
            getCurrentToken: function () {
                return currentToken;
            },

            /**
             * Generate new token
             */
            generateToken: function () {
                if (!window.grecaptcha || refreshStates.get(captchaId)) {
                    return Promise.resolve(currentToken);
                }

                refreshStates.set(captchaId, true);

                return new Promise(function (resolve, reject) {
                    window.grecaptcha
                        .execute(siteKey, { action: action })
                        .then(function (token) {
                            currentToken = token;
                            refreshStates.set(captchaId, false);
                            resolve(token);
                        })
                        .catch(function (error) {
                            refreshStates.set(captchaId, false);
                            console.error('ReCAPTCHA execution failed:', error);
                            reject(error);
                        });
                });
            },

            /**
             * Create or update token input in container
             */
            updateTokenInput: function (container, token) {
                let $tokenInput = container.find(`[name="${TOKEN_INPUT_NAME}"]`);

                if (!$tokenInput.length) {
                    $tokenInput = $('<input>', {
                        type: 'hidden',
                        name: TOKEN_INPUT_NAME,
                        'data-action': action,
                        'data-captcha-id': captchaId
                    });
                    container.append($tokenInput);
                }

                $tokenInput.val(token || currentToken);
                return $tokenInput;
            },

            /**
             * Start automatic token refresh for specific form
             */
            startAutoRefresh: function (formId, container) {
                this.stopAutoRefresh(formId);

                const refresh = () => {
                    // Check if we need to refresh
                    const expirationTime = tokensLifetime.get(formId) || 0;

                    if (Date.now() > expirationTime) {
                        this.generateToken().then(token => {
                            if (token) {
                                this.updateTokenInput(container, token);
                                this.setFormIdTokenLifetime(formId, this.TOKEN_REFRESH_INTERVAL);
                            } else {
                                this.setFormIdTokenLifetime(formId, 0);
                            }
                        });
                    }
                };

                // Only refresh on timer, not on AJAX
                refreshTimers.set(formId, setInterval(refresh, 100));
            },

            /**
             * Stop automatic token refresh for specific form
             */
            stopAutoRefresh: function (formId) {
                const timer = refreshTimers.get(formId);
                if (timer) {
                    clearInterval(timer);
                    refreshTimers.delete(formId);
                }
            },

            /**
             * Refresh token for submitted form only
             */
            refreshForForm: function (formId, container) {
                // Check if we're already refreshing
                const refreshKey = `${formId}_refreshing`;
                if (refreshStates.get(refreshKey)) {
                    console.log(`Already refreshing token for form ${formId}`);
                    return Promise.resolve(currentToken);
                }

                // Prevent multiple refreshes within short time
                const lastRefreshKey = `${formId}_lastRefresh`;
                const now = Date.now();

                refreshStates.set(refreshKey, true);
                refreshStates.set(lastRefreshKey, now);

                return this.generateToken()
                    .then(token => {
                        if (token) {
                            this.updateTokenInput(container, token);
                        }
                        refreshStates.set(refreshKey, false);
                        this.setFormIdTokenLifetime(formId, this.TOKEN_REFRESH_INTERVAL);
                        return token;
                    })
                    .catch(error => {
                        refreshStates.set(refreshKey, false);
                        this.setFormIdTokenLifetime(formId, 0);
                        console.error('Error refreshing token:', error);
                        throw error;
                    });
            },

            /**
             * Set form ID token lifetime
             *
             * @param {string} formId - Unique identifier for the form
             * @param {number} lifetime - Lifetime in milliseconds, default is 90 seconds
             */
            setFormIdTokenLifetime: function (formId, lifetime = this.TOKEN_REFRESH_INTERVAL) {
                tokensLifetime.set(formId, Date.now() + lifetime);
            },

            /**
             * Cleanup
             */
            destroy: function () {
                refreshTimers.forEach((timer, formId) => {
                    this.stopAutoRefresh(formId);
                });
                refreshStates.clear();
                currentToken = null;
            }
        };
    };
});