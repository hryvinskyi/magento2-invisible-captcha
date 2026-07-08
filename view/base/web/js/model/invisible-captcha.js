/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

/**
 * Shared (singleton) captcha load state.
 *
 * State is keyed by provider code so that several providers can coexist on the
 * same page (e.g. a reCAPTCHA v3 form and a Turnstile route challenge) without
 * clobbering each other's "loading"/"loaded" flags.
 */
define(['ko'], function (ko) {
    'use strict';

    // provider code -> { loading, loaded, forms[] }
    const states = {};

    /**
     * Lazily create / return the state bucket for a provider.
     */
    function stateFor(provider) {
        if (!states[provider]) {
            states[provider] = {
                loading: ko.observable(false),
                loaded: ko.observable(false),
                forms: []
            };
        }

        return states[provider];
    }

    return {
        /**
         * Whether the provider script load is in progress.
         */
        isApiLoading: function (provider) {
            return stateFor(provider).loading();
        },

        /**
         * Flag the provider script load state.
         */
        setApiLoading: function (provider, value) {
            stateFor(provider).loading(!!value);
        },

        /**
         * Whether the provider API has finished loading.
         */
        isApiLoaded: function (provider) {
            return stateFor(provider).loaded();
        },

        /**
         * Flag the provider API as loaded.
         */
        setApiLoaded: function (provider, value) {
            stateFor(provider).loaded(!!value);
        },

        /**
         * Observable backing the loaded flag (for KO subscriptions).
         */
        loadedObservable: function (provider) {
            return stateFor(provider).loaded;
        },

        /**
         * List of captcha ids already initialised for a provider.
         */
        initializedForms: function (provider) {
            return stateFor(provider).forms;
        },

        /**
         * Record a captcha id as initialised for a provider.
         */
        markFormInitialized: function (provider, captchaId) {
            const forms = stateFor(provider).forms;
            if (forms.indexOf(captchaId) === -1) {
                forms.push(captchaId);
            }
        }
    };
});
