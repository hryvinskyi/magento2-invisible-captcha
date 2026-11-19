/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 *
 * Background verification of an unverified visitor on a page route protection
 * does not gate. Once the page has loaded and the browser is idle, the route-gate
 * provider is asked for a token without any interaction and the token is posted
 * to the verify endpoint, which issues the verification cookie. A visitor who
 * browses on to a protected page is then let through without the challenge page.
 *
 * Nothing is ever shown: when the provider cannot verify the visitor invisibly
 * (it would need interaction, it errors, the network fails), the attempt is
 * dropped and the visitor meets the challenge page on a protected route as
 * before. Each outcome is remembered for the browser session, so a visitor who
 * could not be verified is not asked again on every page.
 *
 * Configuration comes from <script type="application/json"
 * id="hryvinskyi-captcha-silent-config">:
 *   { verifyUrl, mode: 'widget'|'score', provider, siteKey, responseParam,
 *     scriptUrl, render: {...} }
 */
(function () {
    'use strict';

    var CONFIG_ELEMENT_ID = 'hryvinskyi-captcha-silent-config';
    var STORAGE_KEY = 'hryvinskyi-captcha-silent';
    var VERIFY_TIMEOUT = 10000;
    var IDLE_TIMEOUT = 3000;
    var LOAD_CALLBACK = '__hryvinskyiCaptchaSilentLoad';

    // Unparsable or missing config: nothing to verify with.
    function readConfig() {
        var el = document.getElementById(CONFIG_ELEMENT_ID);
        if (!el) {
            return null;
        }
        try {
            var parsed = JSON.parse(el.textContent || '');
            if (!parsed || typeof parsed !== 'object'
                || typeof parsed.verifyUrl !== 'string' || parsed.verifyUrl === ''
                || typeof parsed.siteKey !== 'string' || parsed.siteKey === ''
                || typeof parsed.responseParam !== 'string' || parsed.responseParam === ''
                || typeof parsed.scriptUrl !== 'string' || parsed.scriptUrl === '') {
                return null;
            }
            parsed.render = (parsed.render && typeof parsed.render === 'object') ? parsed.render : {};
            return parsed;
        } catch (e) {
            return null;
        }
    }

    // sessionStorage can be unavailable (privacy modes); the attempt then
    // simply runs on every page of the session.
    function attemptedThisSession() {
        try {
            return window.sessionStorage.getItem(STORAGE_KEY) !== null;
        } catch (e) {
            return false;
        }
    }

    function remember(outcome) {
        try {
            window.sessionStorage.setItem(STORAGE_KEY, outcome);
        } catch (e) { /* storage unavailable: nothing to remember */ }
    }

    function withQuery(url, query) {
        return url + (url.indexOf('?') === -1 ? '?' : '&') + query;
    }

    function injectScript(url, onload, onerror) {
        var s = document.createElement('script');
        s.src = url;
        s.async = true;
        if (onload) {
            s.addEventListener('load', onload);
        }
        s.addEventListener('error', onerror);
        document.head.appendChild(s);
    }

    function submitToken(cfg, token, done) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', cfg.verifyUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = VERIFY_TIMEOUT;
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }
            var passed = false;
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    passed = !!(resp && resp.success);
                } catch (e) {
                    passed = false;
                }
            }
            done(passed);
        };
        xhr.ontimeout = function () { done(false); };
        xhr.send(encodeURIComponent(cfg.responseParam) + '=' + encodeURIComponent(token));
    }

    // ── How each kind of provider issues a token invisibly ───────────────
    // Keyed by cfg.mode, which the server maps from the provider code.

    // An invisible widget in an off-screen host. It gives up instead of
    // showing anything when the provider would need interaction.
    function runWidget(cfg, onToken, onFail) {
        function render() {
            var api = window.turnstile;
            if (!api || typeof api.render !== 'function') {
                onFail();
                return;
            }
            var host = document.createElement('div');
            host.setAttribute('aria-hidden', 'true');
            host.style.position = 'fixed';
            host.style.left = '-10000px';
            host.style.top = '0';
            host.style.width = '1px';
            host.style.height = '1px';
            host.style.overflow = 'hidden';
            document.body.appendChild(host);

            var widgetId = null;
            var finished = false;
            function finish() {
                finished = true;
                try {
                    if (widgetId !== null) {
                        api.remove(widgetId);
                    }
                } catch (e) { /* widget already gone */ }
                if (host.parentNode) {
                    host.parentNode.removeChild(host);
                }
            }
            function fail() {
                if (finished) {
                    return;
                }
                finish();
                onFail();
            }

            widgetId = api.render(host, {
                sitekey: cfg.siteKey,
                appearance: 'interaction-only',
                retry: 'never',
                callback: function (token) {
                    if (finished) {
                        return;
                    }
                    finish();
                    onToken(token);
                },
                'before-interactive-callback': fail,
                'error-callback': fail,
                'unsupported-callback': fail,
                'timeout-callback': fail
            });
        }

        if (window.turnstile && typeof window.turnstile.render === 'function') {
            render();
            return;
        }
        window[LOAD_CALLBACK] = function () {
            try {
                delete window[LOAD_CALLBACK];
            } catch (e) {
                window[LOAD_CALLBACK] = undefined;
            }
            render();
        };
        injectScript(withQuery(cfg.scriptUrl, 'onload=' + LOAD_CALLBACK + '&render=explicit'), null, onFail);
    }

    // A score-based execute call: never interactive by design.
    function runScore(cfg, onToken, onFail) {
        function api() {
            var g = window.grecaptcha;
            if (!g) {
                return null;
            }
            var candidate = cfg.render.enterprise ? g.enterprise : g;
            return candidate && typeof candidate.execute === 'function' ? candidate : null;
        }
        function execute() {
            var g = api();
            if (!g) {
                onFail();
                return;
            }
            var action = typeof cfg.render.action === 'string' && cfg.render.action !== ''
                ? cfg.render.action
                : 'route_gate';
            var run = function () {
                try {
                    g.execute(cfg.siteKey, { action: action }).then(onToken, onFail);
                } catch (e) {
                    onFail();
                }
            };
            if (typeof g.ready === 'function') {
                g.ready(run);
            } else {
                run();
            }
        }

        if (api()) {
            execute();
            return;
        }
        injectScript(withQuery(cfg.scriptUrl, 'render=' + encodeURIComponent(cfg.siteKey)), execute, onFail);
    }

    var RUNNERS = Object.freeze({
        widget: runWidget,
        score: runScore
    });

    function verify(cfg) {
        var run = RUNNERS[cfg.mode];
        if (typeof run !== 'function') {
            return;
        }
        run(
            cfg,
            function (token) {
                submitToken(cfg, token, function (passed) {
                    remember(passed ? 'passed' : 'failed');
                });
            },
            function () { remember('failed'); }
        );
    }

    // Wait until the page is loaded and the browser is idle, so the provider
    // script never competes with the page's own resources.
    function whenIdle(task) {
        var schedule = function () {
            if (typeof window.requestIdleCallback === 'function') {
                window.requestIdleCallback(task, { timeout: IDLE_TIMEOUT });
            } else {
                window.setTimeout(task, 1);
            }
        };
        if (document.readyState === 'complete') {
            schedule();
        } else {
            window.addEventListener('load', schedule, { once: true });
        }
    }

    var cfg = readConfig();
    if (cfg === null || attemptedThisSession()) {
        return;
    }
    whenIdle(function () { verify(cfg); });
})();
