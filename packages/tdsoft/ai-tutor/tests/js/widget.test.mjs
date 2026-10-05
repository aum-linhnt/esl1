import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/index.js', import.meta.url), 'utf8');
const sandbox = {};
vm.runInNewContext(source.replace('export const AI_TUTOR_ASSET_VERSION', 'const AI_TUTOR_ASSET_VERSION')
    + '\nglobalThis.makeWidget = widget;', sandbox);

function element() {
    const listeners = {};
    return {
        hidden: true, attributes: {}, focused: false, textContent: '',
        addEventListener(name, callback) { listeners[name] = callback; },
        setAttribute(name, value) { this.attributes[name] = value; },
        focus() { this.focused = true; },
        trigger(name, event = {}) { return listeners[name]?.(event); },
    };
}
function setup() {
    const panel = element(), launcher = element(), expand = element(), close = element(), root = element();
    const classes = new Set();
    root.classList = { toggle(name) {
        if (classes.has(name)) { classes.delete(name); return false; }
        classes.add(name); return true;
    } };
    root.querySelector = selector => ({
        '[data-widget-panel]': panel, '[data-widget-open]': launcher,
        '[data-widget-expand]': expand, '[data-widget-close]': close,
    })[selector];
    panel.querySelector = () => close;
    let asks = 0;
    const client = {
        ask: async text => { asks++; return text; },
        setContext: async context => context,
    };
    return { root, panel, launcher, expand, close, classes, asks: () => asks,
        api: sandbox.makeWidget(root, client) };
}

test('open/close/expand preserve the same chat subtree without creating AI requests', () => {
    const x = setup();
    const draft = { text: 'Draft', streaming: true };
    x.panel.chat = draft;
    x.api.open();
    assert.equal(x.panel.hidden, false);
    assert.equal(x.launcher.attributes['aria-expanded'], 'true');
    x.expand.trigger('click');
    assert.ok(x.classes.has('tai-widget--expanded'));
    x.api.close();
    assert.equal(x.panel.hidden, true);
    x.api.open();
    x.expand.trigger('click');
    assert.equal(x.panel.chat, draft);
    assert.equal(x.panel.chat.streaming, true);
    assert.equal(x.asks(), 0);
});

test('public ask forwards once; setContext forwards to the authenticated chat client', async () => {
    const x = setup();
    assert.equal(await x.api.ask('Explain'), 'Explain');
    assert.equal(x.asks(), 1);
    assert.equal(x.panel.hidden, false);
    const context = { lessonId: 'lesson-2' };
    assert.equal(await x.api.setContext(context), context);
});

test('Escape closes the panel and restores launcher focus', () => {
    const x = setup();
    x.api.open();
    let stopped = false;
    x.root.trigger('keydown', { key: 'Escape', stopPropagation() { stopped = true; } });
    assert.equal(x.panel.hidden, true);
    assert.equal(x.launcher.focused, true);
    assert.equal(stopped, true);
});
