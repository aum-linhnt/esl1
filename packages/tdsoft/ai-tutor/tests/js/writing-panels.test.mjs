import test from 'node:test';
import assert from 'node:assert/strict';
import { WritingPanelState } from '../../resources/js/writing-panels.js';

test('open and closed panels remain separate for each assessment', () => {
    const state = new WritingPanelState();
    state.remember('first', { structure: true, vocabulary: false });
    state.remember('second', { structure: false, vocabulary: true });
    assert.equal(state.isOpen('first', 'structure'), true);
    assert.equal(state.isOpen('first', 'vocabulary'), false);
    assert.equal(state.isOpen('second', 'structure'), false);
    assert.equal(state.isOpen('second', 'vocabulary'), true);
    assert.equal(state.isOpen('new-assessment', 'structure'), false);
});

test('snapshots copy preferences and keep memory bounded across history browsing', () => {
    const state = new WritingPanelState(2);
    const preferences = { structure: true };
    state.remember('first', preferences);
    preferences.structure = false;
    assert.equal(state.isOpen('first', 'structure'), true);
    state.remember('second', { structure: true });
    state.remember('first', { structure: false });
    state.remember('third', { structure: true });
    assert.equal(state.entries.size, 2);
    assert.equal(state.entries.has('second'), false);
    assert.equal(state.isOpen('first', 'structure'), false);
    state.remember(null, { structure: true });
    assert.equal(state.entries.size, 2);
});
