/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
(() => {
    'use strict';

    if (window.__hryvinskyiCaptchaAjaxFallback) {
        return;
    }
    window.__hryvinskyiCaptchaAjaxFallback = true;

    // ─── Constants ──────────────────────────────────────────────────────────

    const CHALLENGE_HEADER = 'X-InvisibleCaptcha-Challenge';
    const CHALLENGE_STATUS = 403;

    // IDL callback properties nulled when the challenge response is detected
    // so the caller's jQuery Deferred / xhr.onerror recovery path (often
    // `window.location = clearUrl`) cannot fire.
    const CALLBACK_PROPS = Object.freeze([
        'onreadystatechange', 'onload', 'onloadend',
        'onerror', 'onabort', 'ontimeout'
    ]);

    // Built-in defaults are kept here so the integration stays safe even if
    // the admin config blob fails to render. Admin-supplied lists, when
    // present and non-empty, replace these wholesale (the admin owns the
    // full list, not just additions). See Block/Frontend/AjaxConfig.php.
    const DEFAULT_AJAX_MARKERS = Object.freeze(
        ['isAjax', 'ajax', '_', 'form_key', 'uenc']
    );
    const DEFAULT_BG_AJAX_MARKERS = Object.freeze([]);

    // ─── Config ─────────────────────────────────────────────────────────────

    const resolveList = (injected, fallback) =>
        Array.isArray(injected) && injected.length > 0
            ? injected.filter((item) => typeof item === 'string' && item !== '')
            : [...fallback];

    const resolveString = (injected) =>
        typeof injected === 'string' ? injected : '';

    const compileRegex = (pattern) => {
        if (pattern === '') return null;
        try { return new RegExp(pattern); }
        catch { return null; }
    };

    // Published by ajax-config.phtml as an inline `window.X = {...}` blob
    // immediately before this script loads. Coerce to {} when missing so
    // the property accesses below never throw — JS defaults will apply.
    const injectedConfig = (window.hryvinskyiCaptchaAjaxConfig
        && typeof window.hryvinskyiCaptchaAjaxConfig === 'object')
        ? window.hryvinskyiCaptchaAjaxConfig
        : {};

    // Stripped from any URL the post-verify JS is about to navigate to as a
    // top-level GET — keeps cache-buster / session-scoped tokens (e.g.
    // form_key, uenc) out of the permanent URL. A stale form_key in
    // particular would trigger Magento's "Invalid Form Key. Please refresh
    // the page." on the destination. Set for O(1) `.has(key)` lookups.
    const ajaxMarkers = new Set(
        resolveList(injectedConfig.ajaxMarkerParams, DEFAULT_AJAX_MARKERS)
    );

    // Presence on a challenged AJAX URL means it was a background preload,
    // not a user-initiated action. The post-verify navigation must NOT
    // inherit that destination.
    const bgAjaxMarkers = new Set(
        resolveList(injectedConfig.backgroundAjaxMarkerParams, DEFAULT_BG_AJAX_MARKERS)
    );

    // CSS selector matching the layered-navigation / filter anchor the
    // visitor clicked. Empty (or missing in config) → filter-click capture
    // is disabled entirely, no listener attached.
    const filterAnchorSelector = resolveString(injectedConfig.filterAnchorSelector);

    // Compiled regex matched against AJAX URL query-param keys. Capture
    // group 1 is taken as the attribute name; the value is merged into the
    // post-verify URL (multi-value attributes joined by comma). Null →
    // structured-param merging is disabled; query params still carry over.
    const filterParamRe = compileRegex(resolveString(injectedConfig.filterParamPattern));

    // ─── State ──────────────────────────────────────────────────────────────

    const xhrUrls = new WeakMap();     // xhr → most recent open() url
    const handledXhrs = new WeakSet(); // xhr → challenge already activated
    let documentSwapped = false;
    let lastFilterClickUrl = null;

    // ─── URL helpers ────────────────────────────────────────────────────────

    const parseUrl = (url) => {
        try {
            return new URL(url, location.href);
        } catch {
            return null;
        }
    };

    const hasAnyMarker = (searchParams, markers) => {
        for (const marker of markers) {
            if (searchParams.has(marker)) return true;
        }
        return false;
    };

    /**
     * Returns true for AJAX URLs that the user did NOT initiate as a
     * navigation (background preloads). These must not steer the post-verify
     * redirect, otherwise the user lands on a page they were not actually
     * viewing.
     */
    const isBackgroundAjax = (ajaxUrl) => {
        const parsed = parseUrl(ajaxUrl);
        return parsed ? hasAnyMarker(parsed.searchParams, bgAjaxMarkers) : false;
    };

    /**
     * Strip session-scoped tokens (form_key, uenc) and AJAX markers from any
     * URL we're about to navigate to as a top-level GET. Carrying a stale
     * form_key into the post-verify navigation is what triggers Magento's
     * "Invalid Form Key. Please refresh the page." on the destination.
     */
    const stripInternalParams = (url) => {
        if (!url) return url;
        const parsed = parseUrl(url);
        if (!parsed) return url;
        let dirty = false;
        for (const marker of ajaxMarkers) {
            if (parsed.searchParams.delete(marker)) dirty = true;
        }
        return dirty ? parsed.href : url;
    };

    /**
     * Translate the URL of the AJAX request that triggered the challenge
     * into a navigable URL. Starts from the current location so existing
     * filters stay; merges in structured filter-param deltas (when a
     * `filter_param_pattern` is configured — capture group 1 = attribute
     * name, value joined by comma for multi-value attrs); strips AJAX
     * markers.
     */
    const resolveReturnUrlFromAjax = (ajaxUrl) => {
        if (!ajaxUrl || isBackgroundAjax(ajaxUrl)) return null;
        const ajax = parseUrl(ajaxUrl);
        const target = parseUrl(location.href);
        if (!ajax || !target) return null;

        for (const [key, value] of ajax.searchParams) {
            const match = filterParamRe ? key.match(filterParamRe) : null;
            if (match) {
                const attr = match[1];
                const existing = target.searchParams.get(attr);
                if (existing) {
                    if (!existing.split(',').includes(value)) {
                        target.searchParams.set(attr, `${existing},${value}`);
                    }
                } else {
                    target.searchParams.set(attr, value);
                }
                continue;
            }
            if (ajaxMarkers.has(key)) continue;
            target.searchParams.set(key, value);
        }

        for (const marker of ajaxMarkers) {
            target.searchParams.delete(marker);
        }

        return target.href;
    };

    /**
     * Pop the most reliable return URL we have:
     *   1. The href of the filter anchor the user clicked (markup tends to
     *      carry the full final URL there — most reliable).
     *   2. Reconstructed from the AJAX request URL + structured deltas.
     *
     * A background preload drops any captured click too: the user formed
     * that navigation intent before the preload happened, and it must not
     * be carried over.
     */
    const takeReturnUrl = (ajaxUrl) => {
        if (ajaxUrl && isBackgroundAjax(ajaxUrl)) {
            lastFilterClickUrl = null;
            return null;
        }
        const captured = lastFilterClickUrl;
        lastFilterClickUrl = null;
        return stripInternalParams(captured) || resolveReturnUrlFromAjax(ajaxUrl);
    };

    // ─── Challenge detection + activation ───────────────────────────────────

    const isChallengeXhr = (xhr) => {
        if (xhr.status !== CHALLENGE_STATUS) return false;
        try {
            return xhr.getResponseHeader(CHALLENGE_HEADER) === '1';
        } catch {
            return false;
        }
    };

    const isChallengeFetchResponse = (response) =>
        response?.status === CHALLENGE_STATUS
        && response.headers?.get(CHALLENGE_HEADER) === '1';

    const swapDocument = (html) => {
        if (documentSwapped || typeof html !== 'string' || html === '') return;
        documentSwapped = true;

        try {
            // Cancel any in-flight requests / animations on the page being
            // replaced; they would race with the challenge's own scripts.
            window.stop?.();

            // Parse the challenge HTML in a detached document and swap the
            // live <html> for it. We avoid document.write / document.open
            // because modern browsers silently drop those when called after
            // page load (Chrome intervention, strict CSP, some extensions).
            const newRoot = new DOMParser()
                .parseFromString(html, 'text/html')
                .documentElement;
            document.replaceChild(newRoot, document.documentElement);

            // <script> nodes that arrive via DOMParser / replaceChild do NOT
            // execute on their own — recreate them so the browser runs them.
            // Preserves attributes (src, type, async, defer) and inline text.
            document.querySelectorAll('script').forEach((oldScript) => {
                const newScript = document.createElement('script');
                for (const attr of oldScript.attributes) {
                    newScript.setAttribute(attr.name, attr.value);
                }
                newScript.text = oldScript.text;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        } catch { /* last-resort: leave the original page in place */ }
    };

    const activateChallenge = (html, ajaxUrl) => {
        const returnUrl = takeReturnUrl(ajaxUrl);
        if (returnUrl) {
            window.__hryvinskyiCaptchaReturnUrl = returnUrl;
        }
        swapDocument(html);
    };

    /**
     * Neutralize every IDL callback the caller wired up so the *next* event
     * in the sequence (e.g. jQuery's xhr.onload, which would run its
     * Deferred chain → caller's `error` → `window.location = clearUrl`)
     * cannot fire its post-AJAX recovery path.
     */
    const disarmXhrCallbacks = (xhr) => {
        for (const prop of CALLBACK_PROPS) {
            try { xhr[prop] = null; } catch { /* readonly setter, skip */ }
        }
    };

    // ─── Filter-anchor click capture ────────────────────────────────────────

    // closest() walks ancestors once and bails immediately when the click
    // wasn't on (or inside) a filter anchor — most clicks on the page.
    // Only registered when a selector is actually configured.
    if (filterAnchorSelector !== '') {
        const captureFilterClick = (event) => {
            const anchor = event.target?.closest?.(filterAnchorSelector);
            if (anchor?.href) {
                lastFilterClickUrl = anchor.href;
            }
        };
        document.addEventListener('click', captureFilterClick, true);
    }

    // ─── XMLHttpRequest interception ────────────────────────────────────────

    // Stop the caller's same-event listeners on every fired event (defense
    // in depth: caller might register on any of readystatechange/load/loadend),
    // but only run the actual challenge activation once per xhr.
    const handleXhrFinish = (event) => {
        const xhr = event.target;
        if (!isChallengeXhr(xhr)) return;
        event.stopImmediatePropagation?.();
        if (handledXhrs.has(xhr)) return;
        handledXhrs.add(xhr);
        disarmXhrCallbacks(xhr);
        activateChallenge(xhr.responseText || '', xhrUrls.get(xhr) ?? null);
    };

    const handleReadyStateChange = (event) => {
        if (event.target.readyState === 4) handleXhrFinish(event);
    };

    // We wrap the CONSTRUCTOR (not XMLHttpRequest.prototype.open) because
    // other modules on the page also patch `XhrProto.open` and may replace
    // ours after we run — listeners attached inside a prototype-level
    // open() wrap would never get registered. Per-instance listeners,
    // attached at construction, survive any later prototype mutation:
    // events always fire on the instance regardless of who later replaced
    // a prototype method.
    if (typeof window.XMLHttpRequest === 'function') {
        const NativeXHR = window.XMLHttpRequest;

        const PatchedXHR = function () {
            const xhr = new NativeXHR();

            // Capture URL via an instance-level open() shim. Instance
            // properties shadow the prototype, so even if another module
            // later replaces XhrProto.open, our shim still runs for this
            // xhr and delegates to whichever open the prototype carried at
            // construction time.
            const openAtCtor = xhr.open;
            xhr.open = function (method, url) {
                try { xhrUrls.set(this, url); } catch { /* ignore */ }
                return openAtCtor.apply(this, arguments);
            };

            xhr.addEventListener('readystatechange', handleReadyStateChange);
            xhr.addEventListener('load', handleXhrFinish);
            xhr.addEventListener('loadend', handleXhrFinish);

            return xhr;
        };
        PatchedXHR.prototype = NativeXHR.prototype;

        // Mirror the readyState constants onto the constructor itself.
        // Native XHR exposes UNSENT/OPENED/HEADERS_RECEIVED/LOADING/DONE both
        // on instances (via the prototype) *and* as static properties on the
        // constructor. Third-party code frequently does
        //   `xhr.readyState === XMLHttpRequest.DONE`
        // where `XMLHttpRequest` is the global — which now points to
        // PatchedXHR. Without these copies the right-hand side is `undefined`,
        // the comparison is always false, and the caller's success branch
        // never runs. That silently breaks every XHR for any tracking /
        // analytics lib written that way, regardless of whether the captcha
        // ever issues a challenge.
        PatchedXHR.UNSENT = NativeXHR.UNSENT;
        PatchedXHR.OPENED = NativeXHR.OPENED;
        PatchedXHR.HEADERS_RECEIVED = NativeXHR.HEADERS_RECEIVED;
        PatchedXHR.LOADING = NativeXHR.LOADING;
        PatchedXHR.DONE = NativeXHR.DONE;

        // Plain assignment (not a locked defineProperty) so other libraries
        // that also wrap XMLHttpRequest — analytics, RUM, tracking pixels —
        // can install their wrapper on top of ours. Their wrapper will read
        // `window.XMLHttpRequest` (= PatchedXHR), construct it internally,
        // and our per-instance challenge listeners stay attached regardless
        // of how many layers wrap us. A previous version locked this with
        // a setter that silently dropped reassignments, which broke any
        // third-party that expected to install its own XHR wrapper.
        window.XMLHttpRequest = PatchedXHR;
    }

    // ─── fetch() interception ───────────────────────────────────────────────

    const resolveFetchUrl = (input) => {
        if (!input) return null;
        if (typeof input === 'string') return input;
        if (typeof Request !== 'undefined' && input instanceof Request) return input.url;
        if (typeof input.url === 'string') return input.url;
        if (typeof input.href === 'string') return input.href;
        return null;
    };

    if (typeof window.fetch === 'function') {
        const nativeFetch = window.fetch.bind(window);
        window.fetch = (input, init) => {
            const requestUrl = resolveFetchUrl(input);
            const promise = nativeFetch(input, init);
            promise.then(
                (response) => {
                    if (!isChallengeFetchResponse(response)
                        || typeof response.clone !== 'function') {
                        return;
                    }
                    response.clone().text().then(
                        (html) => activateChallenge(html, requestUrl),
                        () => { /* body read failed; ignore */ }
                    );
                },
                () => { /* network error; ignore */ }
            );
            return promise;
        };
    }
})();
