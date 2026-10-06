import test from 'node:test';
import assert from 'node:assert/strict';
import { writingAssessmentState } from '../../resources/js/writing-state.js';

test('normal queue and processing show progress without billing reconciliation warnings', () => {
    for (const [status, step] of [['queued', 1], ['processing', 2]]) {
        const view = writingAssessmentState({ status, recovery: 'reconciliation' }, {});
        assert.equal(view.active, true);
        assert.equal(view.step, step);
        assert.ok(!view.description.includes('quản trị viên'));
        assert.ok(view.note.includes('Không cần gửi lại'));
    }
});
test('unknown send and actual reconciliation stay distinct from completion or failure', () => {
    assert.equal(writingAssessmentState(null, {}).state, 'confirming');
    assert.equal(writingAssessmentState(null, null).state, 'idle');
    const reconciliation = writingAssessmentState({ status: 'reconciliation_required' }, {});
    assert.equal(reconciliation.active, false);
    assert.ok(reconciliation.description.includes('credit'));
    for (const status of ['completed', 'failed']) assert.equal(writingAssessmentState({ status }, null).active, false);
});
