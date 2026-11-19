/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 *
 * The background verification (view/frontend/web/js/silent-verification.js),
 * run in a vm context over a minimal fake page: the config data block, the
 * provider script it injects, the off-screen widget host, the verify request
 * and the session storage that remembers the outcome.
 *
 * Run: node --test 'Test/Js/*.test.mjs'
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const SCRIPT = readFileSync(
    fileURLToPath(new URL('../../view/frontend/web/js/silent-verification.js', import.meta.url)),
    'utf8'
);

const TURNSTILE_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
const RECAPTCHA_URL = 'https://www.google.com/recaptcha/api.js';

const WIDGET = {
    verifyUrl: 'https://shop.example/invisiblecaptcha/verify',
    mode: 'widget',
    provider: 'turnstile',
    siteKey: 'turnstile-key',
    responseParam: 'cf-turnstile-response',
    scriptUrl: TURNSTILE_URL,
    render: { size: 'flexible', appearance: 'interaction-only' },
};

const SCORE = {
    verifyUrl: 'https://shop.example/invisiblecaptcha/verify',
    mode: 'score',
    provider: 'recaptcha_v3',
    siteKey: 'v3-key',
    responseParam: 'g-recaptcha-response',
    scriptUrl: RECAPTCHA_URL,
    render: { widgetMode: 'score', action: 'route_gate' },
};

/**
 * A fake page that records what the script does to it.
 *
 * @param {{config?: object|null, readyState?: string, stored?: string|null, idle?: boolean}} options
 */
function page({ config = WIDGET, readyState = 'complete', stored = null, idle = true } = {}) {
    const injected = [];
    const appended = [];
    const requests = [];
    const idleTasks = [];
    const windowListeners = [];
    const storage = new Map(stored === null ? [] : [['hryvinskyi-captcha-silent', stored]]);

    const configElement = config === null ? null : { textContent: JSON.stringify(config) };

    class FakeXhr {
        constructor() { this.headers = {}; requests.push(this); }
        open(method, url) { this.method = method; this.url = url; }
        setRequestHeader(name, value) { this.headers[name] = value; }
        send(body) { this.body = body; }
        /** Answer the request as the verify endpoint would. */
        answer(status, body) {
            this.readyState = 4;
            this.status = status;
            this.responseText = body;
            this.onreadystatechange();
        }
    }

    const element = () => ({
        style: {},
        attributes: {},
        listeners: {},
        parentNode: null,
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener(type, listener) { this.listeners[type] = listener; },
    });

    const body = {
        children: [],
        appendChild(child) { child.parentNode = body; this.children.push(child); appended.push(child); },
        removeChild(child) { child.parentNode = null; this.children = this.children.filter((c) => c !== child); },
    };

    const window = {
        sessionStorage: {
            getItem: (key) => (storage.has(key) ? storage.get(key) : null),
            setItem: (key, value) => storage.set(key, value),
        },
        setTimeout: (task) => idleTasks.push(task),
        addEventListener: (type, listener, options) => windowListeners.push({ type, listener, options }),
    };
    if (idle) {
        window.requestIdleCallback = (task) => idleTasks.push(task);
    }
    const document = {
        readyState,
        body,
        head: { appendChild: (script) => injected.push(script) },
        getElementById: (id) => (id === 'hryvinskyi-captcha-silent-config' ? configElement : null),
        createElement: () => element(),
    };
    window.document = document;

    const context = vm.createContext({ window, document, XMLHttpRequest: FakeXhr, JSON, Object, encodeURIComponent });
    vm.runInContext(SCRIPT, context);

    return {
        window,
        body,
        injected,
        appended,
        requests,
        storage,
        windowListeners,
        /** Run what the script left for the idle browser. */
        runIdle() {
            while (idleTasks.length > 0) {
                idleTasks.shift()();
            }
        },
    };
}

/** A Turnstile global that records each render and removal. */
function fakeTurnstile() {
    const rendered = [];
    const removed = [];
    return {
        rendered,
        removed,
        api: {
            render(host, options) { rendered.push({ host, options }); return 'widget-' + rendered.length; },
            remove(id) { removed.push(id); },
        },
    };
}

/** Load the Turnstile script the page asked for. */
function loadTurnstile(p, api) {
    const callback = new URL(p.injected[0].src).searchParams.get('onload');
    p.window.turnstile = api;
    p.window[callback]();
}

test('nothing starts before the page is idle', () => {
    const p = page();

    assert.equal(p.injected.length, 0);
    p.runIdle();
    assert.equal(p.injected.length, 1);
});

test('a page still loading waits for the load event', () => {
    const p = page({ readyState: 'interactive' });

    p.runIdle();
    assert.equal(p.injected.length, 0);
    assert.equal(p.windowListeners.length, 1);
    assert.equal(p.windowListeners[0].type, 'load');

    p.windowListeners[0].listener();
    p.runIdle();
    assert.equal(p.injected.length, 1);
});

test('without requestIdleCallback the attempt still runs', () => {
    const p = page({ idle: false });
    p.runIdle();

    assert.equal(p.injected.length, 1);
});

test('a missing config block does nothing', () => {
    const p = page({ config: null });
    p.runIdle();

    assert.equal(p.injected.length, 0);
});

test('an attempt already made this session is not repeated', () => {
    const p = page({ stored: 'failed' });
    p.runIdle();

    assert.equal(p.injected.length, 0);
});

test('an unknown mode does nothing', () => {
    const p = page({ config: { ...WIDGET, mode: 'checkbox' } });
    p.runIdle();

    assert.equal(p.injected.length, 0);
    assert.equal(p.storage.size, 0);
});

test('the widget is rendered off screen, never interactive, and its token is verified', () => {
    const p = page();
    p.runIdle();
    assert.ok(p.injected[0].src.startsWith(TURNSTILE_URL + '?onload='));
    assert.ok(p.injected[0].src.endsWith('&render=explicit'));

    const turnstile = fakeTurnstile();
    loadTurnstile(p, turnstile.api);

    const { host, options } = turnstile.rendered[0];
    assert.equal(host.style.left, '-10000px');
    assert.equal(host.attributes['aria-hidden'], 'true');
    assert.equal(options.sitekey, 'turnstile-key');
    assert.equal(options.appearance, 'interaction-only');
    assert.equal(options.retry, 'never');

    options.callback('token-1');

    assert.deepEqual(turnstile.removed, ['widget-1']);
    assert.equal(p.body.children.length, 0, 'the host is removed');
    assert.equal(p.requests.length, 1);
    const xhr = p.requests[0];
    assert.equal(xhr.url, WIDGET.verifyUrl);
    assert.equal(xhr.body, 'cf-turnstile-response=token-1');
    assert.equal(xhr.timeout, 10000);

    xhr.answer(200, '{"success":true}');
    assert.equal(p.storage.get('hryvinskyi-captcha-silent'), 'passed');
});

test('a widget that would need interaction gives up without a verify request', () => {
    const p = page();
    p.runIdle();
    const turnstile = fakeTurnstile();
    loadTurnstile(p, turnstile.api);

    turnstile.rendered[0].options['before-interactive-callback']();

    assert.deepEqual(turnstile.removed, ['widget-1']);
    assert.equal(p.body.children.length, 0);
    assert.equal(p.requests.length, 0);
    assert.equal(p.storage.get('hryvinskyi-captcha-silent'), 'failed');

    // A late token after giving up is ignored.
    turnstile.rendered[0].options.callback('late-token');
    assert.equal(p.requests.length, 0);
});

test('a widget error gives up', () => {
    const p = page();
    p.runIdle();
    const turnstile = fakeTurnstile();
    loadTurnstile(p, turnstile.api);

    turnstile.rendered[0].options['error-callback']();

    assert.equal(p.requests.length, 0);
    assert.equal(p.storage.get('hryvinskyi-captcha-silent'), 'failed');
});

test('a provider script that cannot load gives up', () => {
    const p = page();
    p.runIdle();

    p.injected[0].listeners.error();

    assert.equal(p.storage.get('hryvinskyi-captcha-silent'), 'failed');
});

test('a rejected token is remembered as failed', () => {
    const p = page();
    p.runIdle();
    const turnstile = fakeTurnstile();
    loadTurnstile(p, turnstile.api);
    turnstile.rendered[0].options.callback('token-1');

    p.requests[0].answer(200, '{"success":false}');

    assert.equal(p.storage.get('hryvinskyi-captcha-silent'), 'failed');
});

test('a verify request that times out is remembered as failed', () => {
    const p = page();
    p.runIdle();
    const turnstile = fakeTurnstile();
    loadTurnstile(p, turnstile.api);
    turnstile.rendered[0].options.callback('token-1');

    p.requests[0].ontimeout();

    assert.equal(p.storage.get('hryvinskyi-captcha-silent'), 'failed');
});

test('a Turnstile already on the page is used without loading it again', () => {
    const p = page({ readyState: 'interactive' });
    const turnstile = fakeTurnstile();
    p.window.turnstile = turnstile.api;
    p.windowListeners[0].listener();
    p.runIdle();

    assert.equal(p.injected.length, 0);
    assert.equal(turnstile.rendered.length, 1);
});

test('a score provider is executed with the route-gate action and its token verified', async () => {
    const p = page({ config: SCORE });
    p.runIdle();
    assert.equal(p.injected[0].src, RECAPTCHA_URL + '?render=v3-key');

    const executed = [];
    p.window.grecaptcha = {
        ready: (run) => run(),
        execute: (siteKey, options) => {
            executed.push({ siteKey, options });
            return Promise.resolve('score-token');
        },
    };
    p.injected[0].listeners.load();
    await new Promise((resolve) => setImmediate(resolve));

    assert.equal(executed.length, 1);
    assert.equal(executed[0].siteKey, 'v3-key');
    assert.equal(executed[0].options.action, 'route_gate');
    assert.equal(p.requests[0].body, 'g-recaptcha-response=score-token');
});

test('an Enterprise score provider is executed through grecaptcha.enterprise', async () => {
    const p = page({ config: { ...SCORE, provider: 'recaptcha_enterprise', render: { enterprise: true } } });
    p.runIdle();

    const executed = [];
    p.window.grecaptcha = {
        enterprise: {
            ready: (run) => run(),
            execute: (siteKey) => { executed.push(siteKey); return Promise.resolve('enterprise-token'); },
        },
    };
    p.injected[0].listeners.load();
    await new Promise((resolve) => setImmediate(resolve));

    assert.deepEqual(executed, ['v3-key']);
    assert.equal(p.requests[0].body, 'g-recaptcha-response=enterprise-token');
});

test('a score provider that rejects gives up', async () => {
    const p = page({ config: SCORE });
    p.runIdle();
    p.window.grecaptcha = { ready: (run) => run(), execute: () => Promise.reject(new Error('network')) };
    p.injected[0].listeners.load();
    await new Promise((resolve) => setImmediate(resolve));

    assert.equal(p.requests.length, 0);
    assert.equal(p.storage.get('hryvinskyi-captcha-silent'), 'failed');
});
