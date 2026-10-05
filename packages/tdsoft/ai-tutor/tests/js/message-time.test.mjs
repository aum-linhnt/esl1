import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
const source = readFileSync(new URL('../../resources/js/index.js', import.meta.url), 'utf8');
const sandbox = {};
vm.runInNewContext(source.replace('export const AI_TUTOR_ASSET_VERSION', 'const AI_TUTOR_ASSET_VERSION') + '\nglobalThis.formatTime = formatMessageTime;', sandbox);
const now = new Date('2026-12-25T14:32:00Z');
test('today shows only hours/minutes in Vietnam time', () => {
    assert.equal(sandbox.formatTime('2026-12-25T03:15:00Z', now), '10:15');
    assert.equal(sandbox.formatTime('2026-12-25T14:32:00Z', now), '21:32');
});
test('older messages show date including year', () => {
    assert.equal(sandbox.formatTime('2026-12-24T03:15:00Z', now), '10:15 24/12/2026');
});
test('midnight uses Vietnam day and changes labels next day', () => {
    assert.equal(sandbox.formatTime('2026-12-24T17:00:00Z', now), '00:00');
    assert.equal(sandbox.formatTime('2026-12-25T14:32:00Z', new Date('2026-12-25T17:00:00Z')), '21:32 25/12/2026');
});
test('missing/invalid timestamps do not invent a send time', () => {
    assert.equal(sandbox.formatTime(null, now), '');
    assert.equal(sandbox.formatTime('invalid', now), '');
});
