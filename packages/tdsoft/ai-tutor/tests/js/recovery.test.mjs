import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { webcrypto } from 'node:crypto';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/index.js', import.meta.url), 'utf8');

function element() {
    const listeners = {};
    return { disabled: false, hidden: false, textContent: '', value: '', dataset: {}, children: [], classList: { toggle() {} },
        addEventListener(name, fn) { listeners[name] = fn; },
        trigger(name, event = {}) { return listeners[name]?.(event); },
        append(child) { this.children.push(child); }, replaceChildren() { this.children = []; },
        querySelector() { return null; }, querySelectorAll() { return []; },
    };
}

async function setup(recovery, { completed = false, confirmRetry = true, interrupt = false, hintLevel = 1, maxHintLevel = 3 } = {}) {
    const controls = Object.fromEntries(['data-chat-form', 'data-history', 'data-retry', 'data-mode', 'data-reload',
        'data-export', 'data-new-conversation', 'data-delete', 'data-status', 'data-credit-balance', 'data-next-hint'].map(key => [key, element()]));
    const textarea = element(), submit = element();
    controls['data-chat-form'].elements = { message: textarea };
    controls['data-chat-form'].querySelector = () => submit;
    controls['data-mode'].value = 'hints_first';
    const root = { dataset: { api: '/chat', lesson: 'lesson-1', actor: 'learner-1' },
        querySelector: selector => controls[selector.slice(1, -1)] ?? null,
        querySelectorAll: () => [], matches: () => false, isConnected: true };
    const original = { message: 'Explain', request_id: 'original-request', idempotency_key: 'original-key' };
    const storage = new Map([['tai-chat:learner-1:lesson-1', JSON.stringify({ conversation: 'conversation-1', pending: original })]]);
    const posts = [], confirmations = [];
    const sandbox = {
        crypto: webcrypto, TextDecoder, URLSearchParams,
        sessionStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) },
        document: { readyState: 'loading', querySelector: () => null, createElement: element, addEventListener() {} },
        MutationObserver: class { observe() {} }, setInterval() {}, clearInterval() {}, setTimeout() {}, clearTimeout() {},
        confirm: text => { confirmations.push(text); return confirmRetry; },
        fetch: async (url, options) => {
            if (options.method === 'GET') {
                return { ok: true, json: async () => ({ conversation: { teaching_mode: 'hints_first' }, messages: [{
                    id: 'original-message', request_id: original.request_id, user_content: 'Explain',
                    status: completed ? 'completed' : 'failed', content: completed ? 'Answer' : null,
                    recovery: completed ? 'completed' : recovery, sources: [], credit_balance: 98,
                    metadata: { credit_units: 2, hint_level: hintLevel, max_hint_level: maxHintLevel },
                }] }) };
            }
            posts.push(JSON.parse(options.body));
            if (interrupt) throw new Error('connection lost');
            const payload = new TextEncoder().encode('event: completed\ndata: ' + JSON.stringify({
                content: 'Answer', sources: [], credit_balance: 98,
                metadata: { credit_units: 2, hint_level: Math.min(maxHintLevel, hintLevel + (posts.at(-1).next_hint ? 1 : 0)), max_hint_level: maxHintLevel },
            }) + '\n\n');
            let read = false;
            return { ok: true, headers: { get: () => 'text/event-stream' }, body: { getReader: () => ({
                read: async () => read ? { done: true } : (read = true, { done: false, value: payload }),
            }) } };
        },
    };
    vm.runInNewContext(source.replace('export const AI_TUTOR_ASSET_VERSION', 'const AI_TUTOR_ASSET_VERSION')
        + '\nglobalThis.startChat = chat;', sandbox);
    const client = sandbox.startChat(root);
    await new Promise(setImmediate);
    return { controls, posts, confirmations, textarea, storage, client };
}

test('safe failure requires confirmation and creates a linked new attempt in the existing conversation', async () => {
    const x = await setup('new_attempt');
    // Initial reload finishes before the click's own read-only refresh.
    await x.controls['data-retry'].trigger('click');
    assert.equal(x.confirmations.length, 1);
    assert.equal(x.posts.length, 1);
    assert.notEqual(x.posts[0].request_id, 'original-request');
    assert.equal(x.posts[0].retry_of_message_id, 'original-message');
    assert.equal(x.posts[0].confirm_retry, true);
    assert.equal(JSON.parse(x.storage.get('tai-chat:learner-1:lesson-1')).pending, null);
});

test('declining a new paid attempt sends nothing and retains the failed IDs', async () => {
    const x = await setup('new_attempt', { confirmRetry: false });
    await x.controls['data-retry'].trigger('click');
    assert.equal(x.posts.length, 0);
    assert.equal(JSON.parse(x.storage.get('tai-chat:learner-1:lesson-1')).pending.request_id, 'original-request');
});

test('ambiguous outcome blocks new inference; completed reconnect clears pending without a POST', async () => {
    for (const recovery of ['reconciliation', 'blocked']) {
        const x = await setup(recovery);
        await x.controls['data-retry'].trigger('click');
        assert.equal(x.posts.length, 0);
        assert.equal(x.confirmations.length, 0);
        assert.equal(x.controls['data-retry'].disabled, true);
    }
    const x = await setup('completed', { completed: true });
    await x.controls['data-retry'].trigger('click');
    assert.equal(x.posts.length, 0);
    assert.equal(x.textarea.value, '');
    assert.equal(JSON.parse(x.storage.get('tai-chat:learner-1:lesson-1')).pending, null);
});

test('a dropped retry connection preserves new IDs across reconnect instead of creating another attempt', async () => {
    const x = await setup('new_attempt', { interrupt: true });
    await x.controls['data-retry'].trigger('click');
    await x.controls['data-retry'].trigger('click');
    assert.equal(x.posts.length, 2);
    assert.equal(x.confirmations.length, 1);
    assert.equal(x.posts[0].request_id, x.posts[1].request_id);
    assert.equal(x.posts[0].idempotency_key, x.posts[1].idempotency_key);
});

test('next hint requests only the next server-controlled level and stops at the policy cap', async () => {
    const x = await setup('completed', { completed: true, hintLevel: 1, maxHintLevel: 3 });
    assert.equal(x.controls['data-next-hint'].disabled, false);
    x.controls['data-next-hint'].trigger('click');
    await new Promise(setImmediate);
    assert.equal(x.posts.length, 1);
    assert.equal(x.posts[0].next_hint, true);
    assert.equal(x.posts[0].hint_level, undefined);
    assert.equal(x.posts[0].answer_policy, undefined);
    assert.equal(x.controls['data-next-hint'].textContent, 'Xin gợi ý cấp 3');
    for (const [hintLevel, maxHintLevel] of [[3, 3], [4, 4], [0, 0]]) {
        const capped = await setup('completed', { completed: true, hintLevel, maxHintLevel });
        assert.equal(capped.controls['data-next-hint'].disabled, true);
        capped.controls['data-next-hint'].trigger('click');
        await new Promise(setImmediate);
        assert.equal(capped.posts.length, 0);
    }
});

test('a failed next-hint transport retains its progression flag and IDs when retried', async () => {
    const x = await setup('completed', { completed: true, interrupt: true });
    x.controls['data-next-hint'].trigger('click');
    await new Promise(setImmediate);
    await x.controls['data-retry'].trigger('click');
    assert.equal(x.posts.length, 2);
    assert.equal(x.posts[0].next_hint, true);
    assert.equal(x.posts[1].next_hint, true);
    assert.equal(x.posts[0].request_id, x.posts[1].request_id);
});


test('selecting another question cannot abandon a pending paid request', async () => {
    const x = await setup('new_attempt');
    await assert.rejects(() => x.client.setContext({ lessonId: 'lesson-1', questionId: 'inline:1', attemptId: 'a1' }), /AI_CONVERSATION_BUSY/);
    assert.equal(x.posts.length, 0);
    assert.equal(JSON.parse(x.storage.get('tai-chat:learner-1:lesson-1')).pending.request_id, 'original-request');
});

test('switching question restores its own draft and retains the previous lesson draft', async () => {
    const x = await setup('completed', { completed: true });
    x.textarea.value = 'Lesson draft';
    const key = 'tai-chat:learner-1:lesson-1:' + JSON.stringify(['a1', 'inline:1']);
    x.storage.set(key, JSON.stringify({ draft: 'Question draft' }));
    await x.client.setContext({ lessonId: 'lesson-1', questionId: 'inline:1', attemptId: 'a1' });
    assert.equal(x.textarea.value, 'Question draft');
    assert.equal(JSON.parse(x.storage.get('tai-chat:learner-1:lesson-1')).draft, 'Lesson draft');
    assert.equal(x.posts.length, 0);
});
