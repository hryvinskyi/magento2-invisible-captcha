/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 *
 * The AJAX fallback (view/frontend/web/js/ajax-fallback.js), run in a vm
 * context over a minimal fake page: the config data block, the wrapped
 * XMLHttpRequest, and the challenge document swapped in when an AJAX request
 * is answered with the challenge.
 *
 * Run: node --test 'Test/Js/*.test.mjs'
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const SCRIPT = readFileSync(
    fileURLToPath(new URL('../../view/frontend/web/js/ajax-fallback.js', import.meta.url)),
    'utf8'
);

const PAGE_URL = 'https://shop.example/women.html';
const RETURN_URL_ATTRIBUTE = 'data-hryvinskyi-captcha-return-url';

/** An element that keeps its attributes. */
function element() {
    const attributes = new Map();
    return {
        attributes,
        hasAttribute: (name) => attributes.has(name),
        getAttribute: (name) => (attributes.has(name) ? attributes.get(name) : null),
        setAttribute: (name, value) => attributes.set(name, String(value)),
    };
}

/**
 * A fake page; `config` is printed as the data block unless null.
 *
 * @param {{config?: object|null}} options
 */
function page({ config = null } = {}) {
    const documentListeners = [];
    const swapped = [];
    const xhrs = [];

    class NativeXhr {
        constructor() {
            this.listeners = {};
            this.readyState = 0;
            this.status = 0;
            this.responseText = '';
            this.headers = {};
            xhrs.push(this);
        }
        open(method, url) { this.url = url; }
        send() {}
        addEventListener(type, listener) { (this.listeners[type] ||= []).push(listener); }
        getResponseHeader(name) { return this.headers[name] ?? null; }
        /** Finish the request as a server would. */
        respond(status, headers, body) {
            this.readyState = 4;
            this.status = status;
            this.headers = headers;
            this.responseText = body;
            const event = { target: this, stopImmediatePropagation() {} };
            for (const type of ['readystatechange', 'load', 'loadend']) {
                (this.listeners[type] || []).forEach((listener) => listener(event));
            }
        }
    }
    NativeXhr.DONE = 4;

    const document = {
        documentElement: element(),
        getElementById: (id) => (id === 'hryvinskyi-captcha-ajax-config' && config !== null
            ? { textContent: JSON.stringify(config) }
            : null),
        addEventListener: (type, listener, capture) => documentListeners.push({ type, listener, capture }),
        replaceChild(newRoot) { swapped.push(newRoot); this.documentElement = newRoot; },
        querySelectorAll: () => [],
        createElement: () => element(),
    };

    class FakeDomParser {
        parseFromString(html) {
            const root = element();
            root.html = html;
            return { documentElement: root };
        }
    }

    const window = { XMLHttpRequest: NativeXhr, stop() {} };
    const context = vm.createContext({
        window,
        document,
        location: { href: PAGE_URL },
        DOMParser: FakeDomParser,
        URL,
        WeakMap,
        WeakSet,
        Set,
        Object,
        Array,
        JSON,
        RegExp,
    });
    context.XMLHttpRequest = window.XMLHttpRequest;
    vm.runInContext(SCRIPT, context);

    return {
        window,
        document,
        swapped,
        xhrs,
        documentListeners,
        context,
        /** Click an element inside a filter link with the given href. */
        clickFilter(href) {
            const anchor = { href };
            const target = { closest: () => anchor };
            documentListeners
                .filter((l) => l.type === 'click')
                .forEach((l) => l.listener({ target }));
        },
        /** Run the script a second time on the same page. */
        runAgain() { vm.runInContext(SCRIPT, context); },
    };
}

const CHALLENGE = [403, { 'X-InvisibleCaptcha-Challenge': '1' }, '<html><body>challenge</body></html>'];

test('the filter link selector comes from the config data block', () => {
    const p = page({ config: { filterAnchorSelector: '.filter a' } });

    assert.equal(p.documentListeners.filter((l) => l.type === 'click').length, 1);
});

test('without a config block no filter clicks are watched', () => {
    const p = page();

    assert.equal(p.documentListeners.length, 0);
});

test('a second copy of the script on the page wraps nothing again', () => {
    const p = page({ config: { filterAnchorSelector: '.filter a' } });
    const wrapped = p.window.XMLHttpRequest;

    p.runAgain();

    assert.equal(p.window.XMLHttpRequest, wrapped);
    assert.equal(p.documentListeners.length, 1);
});

test('a filter click answered with the challenge swaps it in, carrying the clicked URL without form_key', () => {
    const p = page({ config: { filterAnchorSelector: '.filter a' } });
    p.clickFilter('https://shop.example/women.html?color=red&form_key=abc');

    const xhr = new p.window.XMLHttpRequest();
    xhr.open('GET', 'https://shop.example/women.html?color=red&isAjax=1');
    xhr.respond(...CHALLENGE);

    assert.equal(p.swapped.length, 1);
    assert.equal(p.swapped[0].html, CHALLENGE[2]);
    assert.equal(p.swapped[0].getAttribute(RETURN_URL_ATTRIBUTE), 'https://shop.example/women.html?color=red');
    assert.equal('__hryvinskyiCaptchaReturnUrl' in p.window, false);
});

test('without a click the return URL is rebuilt from the AJAX URL', () => {
    const p = page({ config: {} });

    const xhr = new p.window.XMLHttpRequest();
    xhr.open('GET', 'https://shop.example/women.html?size=m&isAjax=1');
    xhr.respond(...CHALLENGE);

    assert.equal(p.swapped[0].getAttribute(RETURN_URL_ATTRIBUTE), 'https://shop.example/women.html?size=m');
});

test('a background request answered with the challenge carries no return URL', () => {
    const p = page({ config: { backgroundAjaxMarkerParams: ['is_scroll'] } });

    const xhr = new p.window.XMLHttpRequest();
    xhr.open('GET', 'https://shop.example/women.html?p=2&is_scroll=1');
    xhr.respond(...CHALLENGE);

    assert.equal(p.swapped.length, 1);
    assert.equal(p.swapped[0].hasAttribute(RETURN_URL_ATTRIBUTE), false);
});

test('an ordinary response is left alone', () => {
    const p = page({ config: {} });

    const xhr = new p.window.XMLHttpRequest();
    xhr.open('GET', 'https://shop.example/women.html?size=m&isAjax=1');
    xhr.respond(200, {}, '{"ok":true}');

    assert.equal(p.swapped.length, 0);
});
