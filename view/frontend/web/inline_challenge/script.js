(function () {
    'use strict';

    // Provider-agnostic interstitial bootstrap. The server prints the active
    // route-gate provider (and an optional fallback) as a JSON data block,
    // <script type="application/json" id="hryvinskyi-captcha-config">:
    //   { verifyUrl, refId, provider, siteKey, responseParam, scriptUrl,
    //     render:{...}, fallbackEnabled, fallbackDelay,
    //     fallback?:{ provider, siteKey, responseParam, scriptUrl, render } }
    // This script dynamically loads the provider API script, renders the
    // primary widget, and reveals and renders the fallback widget after
    // fallbackDelay ms, or at once when the primary reports an error, so a
    // visitor can still pass when the primary does not resolve. The verify
    // endpoint accepts either token; whichever the visitor completes first wins.
    var CONFIG_ELEMENT_ID = 'hryvinskyi-captcha-config';

    // Unparsable or missing config leaves every field empty: no provider script
    // to load, so the bootstrap shows the failure state.
    function readConfig() {
        var el = document.getElementById(CONFIG_ELEMENT_ID);
        if (!el) {
            return {};
        }
        try {
            var parsed = JSON.parse(el.textContent || '');
            return (parsed && typeof parsed === 'object') ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    var cfg = readConfig();

    var VERIFY_URL = cfg.verifyUrl || '';
    var REF_ID = cfg.refId || '';
    var FALLBACK_ENABLED = !!cfg.fallbackEnabled && cfg.fallback && typeof cfg.fallback === 'object';
    var FALLBACK_DELAY = typeof cfg.fallbackDelay === 'number' ? cfg.fallbackDelay : 10000;
    // The verify endpoint's own siteverify call is capped by the server's
    // HTTP client; the rest is headroom for a busy PHP-FPM queue. A request
    // still unanswered by then shows the retry state instead of spinning forever.
    var VERIFY_TIMEOUT = 10000;

    // DOM hosts (kept stable so the bundled styles.css can target them).
    var PRIMARY_HOST_ID = 'turnstile-widget';
    var FALLBACK_HOST_ID = 'recaptchaMount';

    var verified = false;
    var fallbackRequested = false;
    var loadedScripts = {}; // url → true (dedupe injected <script> tags)
    var cbSeq = 0;

    // ── Widget specs ─────────────────────────────────────────────────────
    var primarySpec = {
        provider: cfg.provider || '',
        siteKey: cfg.siteKey || '',
        responseParam: cfg.responseParam || '',
        scriptUrl: cfg.scriptUrl || '',
        render: cfg.render || {},
        isFallback: false
    };

    function buildFallbackSpec() {
        var fb = cfg.fallback || {};
        return {
            provider: fb.provider || '',
            siteKey: fb.siteKey || '',
            responseParam: fb.responseParam || '',
            scriptUrl: fb.scriptUrl || '',
            render: fb.render || {},
            isFallback: true
        };
    }

    function fallbackAvailable() {
        return FALLBACK_ENABLED && !!(cfg.fallback && cfg.fallback.siteKey);
    }

    // ── UX states ────────────────────────────────────────────────────────
    function showFail() {
        if (verified) {
            return;
        }
        document.body.classList.add('state-fail');
    }

    // A challenge answered to an AJAX request was swapped into the open page;
    // the page's AJAX fallback put the URL the visitor was heading to on the
    // swapped-in <html>. Go there instead of reloading the AJAX endpoint.
    var RETURN_URL_ATTRIBUTE = 'data-hryvinskyi-captcha-return-url';

    function reloadPage() {
        var root = document.documentElement;
        var returnUrl = root ? root.getAttribute(RETURN_URL_ATTRIBUTE) : null;
        if (typeof returnUrl === 'string' && returnUrl !== '') {
            root.removeAttribute(RETURN_URL_ATTRIBUTE);
            window.location.href = returnUrl;
            return;
        }
        window.location.reload();
    }

    // A primary that reports an error (e.g. Turnstile 600010) has already
    // failed, so the visitor gets the fallback now instead of after the delay;
    // the primary may keep retrying beside it and whichever passes first wins.
    // Without a fallback, or when the fallback itself errors, show the
    // failure state.
    function onProviderError(spec) {
        if (spec && spec.isFallback) {
            showFail();
            return;
        }
        if (fallbackAvailable()) {
            revealFallback();
            return;
        }
        showFail();
    }

    // ── Token submission ─────────────────────────────────────────────────
    function submitToken(paramName, token) {
        if (verified || !paramName) {
            return;
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', VERIFY_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }
            if (xhr.status !== 200) {
                showFail();
                return;
            }
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp && resp.success) {
                    verified = true;
                    reloadPage();
                } else {
                    showFail();
                }
            } catch (e) {
                showFail();
            }
        };
        xhr.onerror = showFail;
        xhr.timeout = VERIFY_TIMEOUT;
        xhr.ontimeout = showFail;
        var body = encodeURIComponent(paramName) + '=' + encodeURIComponent(token);
        if (REF_ID) {
            body += '&ref=' + encodeURIComponent(REF_ID);
        }
        xhr.send(body);
    }

    // ── Provider helpers ─────────────────────────────────────────────────
    function isRecaptcha(provider) {
        return typeof provider === 'string' && provider.indexOf('recaptcha') === 0;
    }

    // The grecaptcha namespace differs between classic and Enterprise.
    function recaptchaApi(render) {
        if (render && render.enterprise) {
            return (window.grecaptcha && window.grecaptcha.enterprise) || null;
        }
        return window.grecaptcha || null;
    }

    // Returns the ready provider global, or null when not yet available.
    function providerReady(spec) {
        if (spec.provider === 'turnstile') {
            return (typeof window.turnstile !== 'undefined') ? window.turnstile : null;
        }
        if (isRecaptcha(spec.provider)) {
            var api = recaptchaApi(spec.render);
            if (api && (typeof api.render === 'function' || typeof api.execute === 'function')) {
                return api;
            }
        }
        return null;
    }

    function isScoreMode(spec) {
        return isRecaptcha(spec.provider) && (spec.render || {}).widgetMode === 'score';
    }

    function buildScriptUrl(spec, cbName) {
        var url = spec.scriptUrl || '';
        if (url === '') {
            return '';
        }
        var sep = url.indexOf('?') === -1 ? '?' : '&';
        if (spec.provider === 'turnstile') {
            return url + sep + 'onload=' + cbName + '&render=explicit';
        }
        if (isRecaptcha(spec.provider)) {
            if (isScoreMode(spec)) {
                // v3 / Enterprise: the API must be loaded bound to the site key.
                return url + sep + 'render=' + encodeURIComponent(spec.siteKey);
            }
            // Explicit v2 (checkbox / invisible).
            return url + sep + 'onload=' + cbName + '&render=explicit';
        }
        return url;
    }

    function injectScript(url, onload, onerror) {
        if (url === '') {
            if (onerror) { onerror(); }
            return;
        }
        if (loadedScripts[url]) {
            if (onload) { onload(); }
            return;
        }
        loadedScripts[url] = true;
        var s = document.createElement('script');
        s.src = url;
        s.async = true;
        s.defer = true;
        if (onload) {
            s.addEventListener('load', onload);
        }
        s.addEventListener('error', function () {
            if (onerror) { onerror(); }
        });
        document.head.appendChild(s);
    }

    // ── Dynamic load + render ────────────────────────────────────────────
    function loadAndRender(spec, hostId) {
        if (verified || spec.scriptUrl === '') {
            if (spec.scriptUrl === '') { onProviderError(spec); }
            return;
        }

        // Provider global already available (e.g. fallback shares it with the
        // primary): render immediately.
        if (providerReady(spec)) {
            renderWidget(spec, hostId);
            return;
        }

        // Score-based reCAPTCHA loads with render=<siteKey> and has no onload
        // hook — render on the script's load event (then api.ready inside).
        if (isScoreMode(spec)) {
            injectScript(
                buildScriptUrl(spec, ''),
                function () { renderWidget(spec, hostId); },
                function () { onProviderError(spec); }
            );
            return;
        }

        // Turnstile / explicit reCAPTCHA expose an onload callback global.
        var cbName = '__hryvinskyiCaptchaCb' + (++cbSeq);
        window[cbName] = function () {
            try {
                delete window[cbName];
            } catch (e) {
                window[cbName] = undefined;
            }
            renderWidget(spec, hostId);
        };
        injectScript(buildScriptUrl(spec, cbName), null, function () { onProviderError(spec); });
    }

    function renderWidget(spec, hostId) {
        if (verified) {
            return;
        }
        if (spec.provider === 'turnstile') {
            renderTurnstile(spec, hostId);
        } else if (isRecaptcha(spec.provider)) {
            renderRecaptcha(spec, hostId);
        } else {
            onProviderError(spec);
        }
    }

    // ----- Cloudflare Turnstile -----
    function renderTurnstile(spec, hostId) {
        if (typeof window.turnstile === 'undefined') {
            onProviderError(spec);
            return;
        }
        var host = document.getElementById(hostId);
        if (!host) {
            onProviderError(spec);
            return;
        }
        var render = spec.render || {};
        var widgetId = window.turnstile.render(host, {
            sitekey: spec.siteKey,
            size: render.size || 'flexible',
            appearance: render.appearance || 'interaction-only',
            callback: function (token) { submitToken(spec.responseParam, token); },
            'error-callback': function () { onProviderError(spec); },
            'expired-callback': function () {
                if (widgetId !== null && widgetId !== undefined) {
                    window.turnstile.reset(widgetId);
                }
            }
        });
    }

    // ----- Google reCAPTCHA (v2 checkbox / invisible, v3, Enterprise) -----
    function renderRecaptcha(spec, hostId) {
        var render = spec.render || {};
        var api = recaptchaApi(render);
        if (!api) {
            onProviderError(spec);
            return;
        }

        // Score-based (v3 / Enterprise): invisible, executed programmatically.
        if (render.widgetMode === 'score') {
            var action = render.action || 'route_gate';
            var run = function () {
                try {
                    api.execute(spec.siteKey, {action: action}).then(
                        function (token) { submitToken(spec.responseParam, token); },
                        function () { onProviderError(spec); }
                    );
                } catch (e) {
                    onProviderError(spec);
                }
            };
            if (typeof api.ready === 'function') {
                api.ready(run);
            } else {
                run();
            }
            return;
        }

        // Explicit (v2 checkbox / invisible).
        if (typeof api.render !== 'function') {
            onProviderError(spec);
            return;
        }
        var host = document.getElementById(hostId);
        if (!host) {
            onProviderError(spec);
            return;
        }
        // Clear the static placeholder before mounting the real widget.
        host.innerHTML = '';

        var widgetId = null;
        var opts = {
            sitekey: spec.siteKey,
            callback: function (token) { submitToken(spec.responseParam, token); },
            'expired-callback': function () {
                if (widgetId !== null && typeof api.reset === 'function') {
                    api.reset(widgetId);
                }
            },
            'error-callback': function () { onProviderError(spec); }
        };
        if (render.theme) { opts.theme = render.theme; }
        if (render.size) { opts.size = render.size; }
        if (render.badge) { opts.badge = render.badge; }

        widgetId = api.render(host, opts);

        // The invisible v2 badge must be executed to trigger the challenge.
        if (render.size === 'invisible' && typeof api.execute === 'function') {
            try {
                api.execute(widgetId);
            } catch (e) { /* ignore */ }
        }
    }

    // ── Fallback reveal (after the configured delay) ─────────────────────
    function revealFallback() {
        if (verified || !fallbackAvailable()) {
            return;
        }

        document.body.classList.add('show-human');

        var foot = document.getElementById('footState');
        if (foot) {
            var waiting = foot.getAttribute('data-waiting');
            if (waiting) {
                foot.textContent = waiting;
            }
        }

        if (fallbackRequested) {
            return;
        }
        fallbackRequested = true;

        loadAndRender(buildFallbackSpec(), FALLBACK_HOST_ID);
    }

    // ── Bootstrap ────────────────────────────────────────────────────────
    function init() {
        var btn = document.getElementById('tryAgain');
        if (btn) {
            btn.addEventListener('click', function () { reloadPage(); });
        }

        if (primarySpec.scriptUrl) {
            loadAndRender(primarySpec, PRIMARY_HOST_ID);
        } else {
            showFail();
        }

        if (fallbackAvailable()) {
            window.setTimeout(revealFallback, FALLBACK_DELAY);
        }
    }

    // A challenge answered to an AJAX request is swapped into the open page
    // long after DOMContentLoaded has fired, so a listener alone would never
    // run there.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
