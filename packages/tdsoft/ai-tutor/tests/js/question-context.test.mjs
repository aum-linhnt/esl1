import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
const source = readFileSync(new URL('../../resources/js/index.js', import.meta.url), 'utf8');
const sandbox = {};
vm.runInNewContext(source.replace('export const AI_TUTOR_ASSET_VERSION', 'const AI_TUTOR_ASSET_VERSION')
    + '\nglobalThis.contextKey = chatContextKey; globalThis.validateContext = validateChatContext;', sandbox);

test('drafts and recovery IDs are isolated by learner, lesson, attempt and question', () => {
    const keys = [
        sandbox.contextKey('u1', 'l1'),
        sandbox.contextKey('u1', 'l1', 'inline:0', 'a1'),
        sandbox.contextKey('u1', 'l1', 'inline:1', 'a1'),
        sandbox.contextKey('u1', 'l1', 'inline:0', 'a2'),
        sandbox.contextKey('u2', 'l1', 'inline:0', 'a1'),
        sandbox.contextKey('u1', 'l2', 'inline:0', 'a1'),
    ];
    assert.equal(new Set(keys).size, keys.length);
    assert.equal(keys[0], 'tai-chat:u1:l1'); // Preserve existing lesson-only storage.
    assert.notEqual(sandbox.contextKey('u', 'l', 'a:b', 'c'), sandbox.contextKey('u', 'l', 'b', 'c:a'));
});

test('question selection requires both opaque question and attempt IDs', () => {
    for (const context of [
        { lessonId: 'l', questionId: 'q' }, { lessonId: 'l', attemptId: 'a' },
        { lessonId: 'l', questionId: 1, attemptId: 'a' },
        { lessonId: 'l', questionId: 'q', attemptId: {} },
        { lessonId: '', questionId: 'q', attemptId: 'a' },
    ]) assert.throws(() => sandbox.validateContext(context), /AI_CONTEXT_INVALID/);
    const context = sandbox.validateContext({ lessonId: 'l', questionId: 'inline:0', attemptId: '10', correct_answer: 'SPOOF' });
    assert.equal(context.questionId, 'inline:0');
    assert.equal(Object.hasOwn(context, 'correct_answer'), false);
});
