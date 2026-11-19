/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 *
 * The inline challenge bootstrap (view/frontend/web/inline_challenge/script.js),
 * run in a vm context over a minimal fake page: the config data block, the
 * widget hosts, the provider scripts it injects and the verify request it sends.
 *
 * Run: node --test Test/Js
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const SCRIPT = readFileSync(
    fileURLToPath(new URL('../../view/frontend/web/inline_challenge/script.js', import.meta.url)),
    'utf8'
);

const TURNSTILE_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
const RECAPTCHA_URL = 'https://www.google.com/recaptcha/api.js';

const PRIMARY = {
    verifyUrl: 'https://shop.example/invisiblecaptcha/verify',
    refId: 'A7F23K9M',
    provider: 'turnstile',
    siteKey: 'turnstile-key',
    responseParam: 'cf-turnstile-response',
    scriptUrl: TURNSTILE_URL,
    render: { size: 'flexible', appearance: 'interaction-only' },
    fallbackEnabled: false,
    fallbackDelay: 15000,
};

const WITH_FALLBACK = {
    ...PRIMARY,
    fallbackEnabled: true,
    fallback: {
        provider: 'recaptcha_v2_checkbox',
        siteKey: 'recaptcha-key',
        responseParam: 'g-recaptcha-response',
        scriptUrl: RECAPTCHA_URL,
        render: { theme: 'light' },
    },
};

/**
 * A fake page that records what the bootstrap does to it.
 *
 * @param {{config?: object|null, rawConfig?: string, readyState?: string}} options
 */
function page({ config = PRIMARY, rawConfig, readyState = 'complete', returnUrl = null } = {}) {
    const bodyClasses = new Set();
    const injectedScripts = [];
    const timers = [];
    const requests = [];
    const domListeners = [];

    const element = (id, extra = {}) => ({
        id,
        innerHTML: '',
        textContent: '',
        listeners: {},
        attributes: {},
        addEventListener(type, listener) { this.listeners[type] = listener; },
        getAttribute(name) { return this.attributes[name] ?? null; },
        ...extra,
    });

    const elements = {
        'turnstile-widget': element('turnstile-widget'),
        recaptchaMount: element('recaptchaMount'),
        tryAgain: element('tryAgain'),
        footState: element('footState', { attributes: { 'data-waiting': 'Awaiting your verification' } }),
    };
    if (config !== null || rawConfig !== undefined) {
        elements['hryvinskyi-captcha-config'] = element('hryvinskyi-captcha-config', {
            textContent: rawConfig ?? JSON.stringify(config),
        });
    }

    class FakeXhr {
        constructor() {
            this.headers = {};
            this.readyState = 0;
            this.status = 0;
            requests.push(this);
        }
        open(method, url) { this.method = method; this.url = url; }
        setRequestHeader(name, value) { this.headers[name] = value; }
        send(body) { this.body = body; }
    }

    const window = {
        setTimeout(callback, delay) { timers.push({ callback, delay }); return timers.length; },
        location: { reload() { window.reloaded = true; }, href: '' },
    };
    const rootAttributes = new Map(returnUrl === null ? [] : [['data-hryvinskyi-captcha-return-url', returnUrl]]);
    const document = {
        readyState,
        documentElement: {
            getAttribute: (name) => (rootAttributes.has(name) ? rootAttributes.get(name) : null),
            removeAttribute: (name) => rootAttributes.delete(name),
        },
        body: { classList: { add: (name) => bodyClasses.add(name) } },
        head: { appendChild: (script) => injectedScripts.push(script) },
        getElementById: (id) => elements[id] ?? null,
        createElement: () => {
            const script = { listeners: {}, addEventListener(type, l) { this.listeners[type] = l; } };
            return script;
        },
        addEventListener(type, listener, options) { domListeners.push({ type, listener, options }); },
    };
    window.document = document;
    window.XMLHttpRequest = FakeXhr;

    const context = vm.createContext({ window, document, XMLHttpRequest: FakeXhr, JSON, encodeURIComponent });
    vm.runInContext(SCRIPT, context);

    return {
        window,
        rootAttributes,
        elements,
        bodyClasses,
        injectedScripts,
        timers,
        requests,
        domListeners,
        /** Run the onload callback the injected provider script names, with the given provider global. */
        loadProvider(index, globals) {
            const src = injectedScripts[index].src;
            const callback = new URL(src).searchParams.get('onload');
            Object.assign(window, globals);
            window[callback]();
        },
    };
}

/** A Turnstile global whose render() hands back the options it was given. */
function fakeTurnstile() {
    const rendered = [];
    return {
        rendered,
        api: {
            render(host, options) { rendered.push({ host, options }); return 'widget-1'; },
            reset() {},
        },
    };
}

test('a page that has already loaded runs the bootstrap at once and loads the primary from the config block', () => {
    const p = page();

    assert.equal(p.injectedScripts.length, 1);
    assert.ok(p.injectedScripts[0].src.startsWith(TURNSTILE_URL + '?onload='));
    assert.equal(p.domListeners.length, 0);
});

test('a page still loading waits for DOMContentLoaded, once', () => {
    const p = page({ readyState: 'loading' });

    assert.equal(p.injectedScripts.length, 0);
    assert.equal(p.domListeners.length, 1);
    assert.equal(p.domListeners[0].type, 'DOMContentLoaded');
    assert.deepEqual({ ...p.domListeners[0].options }, { once: true });

    p.domListeners[0].listener();
    assert.equal(p.injectedScripts.length, 1);
});

test('a missing config block shows the failure state', () => {
    const p = page({ config: null });

    assert.ok(p.bodyClasses.has('state-fail'));
    assert.equal(p.injectedScripts.length, 0);
});

test('an unparsable config block shows the failure state', () => {
    const p = page({ rawConfig: '{not json' });

    assert.ok(p.bodyClasses.has('state-fail'));
});

test('a primary error reveals the fallback at once, before the delay runs out', () => {
    const p = page({ config: WITH_FALLBACK });
    const turnstile = fakeTurnstile();
    p.loadProvider(0, { turnstile: turnstile.api });

    turnstile.rendered[0].options['error-callback']();

    assert.ok(p.bodyClasses.has('show-human'));
    assert.ok(!p.bodyClasses.has('state-fail'));
    assert.equal(p.injectedScripts.length, 2);
    assert.ok(p.injectedScripts[1].src.startsWith(RECAPTCHA_URL + '?onload='));
    assert.equal(p.elements.footState.textContent, 'Awaiting your verification');
});

test('the delayed reveal after an early one loads the fallback only once', () => {
    const p = page({ config: WITH_FALLBACK });
    const turnstile = fakeTurnstile();
    p.loadProvider(0, { turnstile: turnstile.api });
    turnstile.rendered[0].options['error-callback']();

    const delayed = p.timers.find((t) => t.delay === WITH_FALLBACK.fallbackDelay);
    assert.ok(delayed, 'the fallback delay is scheduled');
    delayed.callback();

    assert.equal(p.injectedScripts.length, 2);
});

test('a primary error without a fallback shows the failure state', () => {
    const p = page();
    const turnstile = fakeTurnstile();
    p.loadProvider(0, { turnstile: turnstile.api });

    turnstile.rendered[0].options['error-callback']();

    assert.ok(p.bodyClasses.has('state-fail'));
    assert.ok(!p.bodyClasses.has('show-human'));
});

test('the verify request gives up after 10 s and shows the failure state', () => {
    const p = page();
    const turnstile = fakeTurnstile();
    p.loadProvider(0, { turnstile: turnstile.api });

    turnstile.rendered[0].options.callback('token-1');

    assert.equal(p.requests.length, 1);
    const xhr = p.requests[0];
    assert.equal(xhr.url, PRIMARY.verifyUrl);
    assert.equal(xhr.body, 'cf-turnstile-response=token-1&ref=A7F23K9M');
    assert.equal(xhr.timeout, 10000);

    xhr.ontimeout();
    assert.ok(p.bodyClasses.has('state-fail'));
});

test('a passed verification reloads the page', () => {
    const p = page();
    const turnstile = fakeTurnstile();
    p.loadProvider(0, { turnstile: turnstile.api });
    turnstile.rendered[0].options.callback('token-1');

    const xhr = p.requests[0];
    xhr.readyState = 4;
    xhr.status = 200;
    xhr.responseText = '{"success":true}';
    xhr.onreadystatechange();

    assert.equal(p.window.reloaded, true);
    assert.ok(!p.bodyClasses.has('state-fail'));
});

test('a challenge swapped in for an AJAX request goes on to the URL the visitor was heading to', () => {
    const p = page({ returnUrl: 'https://shop.example/women.html?color=red' });
    const turnstile = fakeTurnstile();
    p.loadProvider(0, { turnstile: turnstile.api });
    turnstile.rendered[0].options.callback('token-1');

    const xhr = p.requests[0];
    xhr.readyState = 4;
    xhr.status = 200;
    xhr.responseText = '{"success":true}';
    xhr.onreadystatechange();

    assert.equal(p.window.location.href, 'https://shop.example/women.html?color=red');
    assert.equal(p.window.reloaded, undefined);
    assert.equal(p.rootAttributes.size, 0);
});
