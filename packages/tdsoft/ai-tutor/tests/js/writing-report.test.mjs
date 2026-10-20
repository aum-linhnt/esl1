import test from 'node:test';
import assert from 'node:assert/strict';
import { writingReport } from '../../resources/js/writing-report.js';

test('report uses immutable historical text, IELTS bands and complete analysis', () => {
    const submission = { id: 'attempt-1', revision: 2, status: 'completed', original: 'Old essay 😀', result: {
        score_scale: 'ielts_band_0_9', overall_score: 6.5, criteria: {
            task_achievement: { score: 6, rationale: 'Overview missing.', evidence: ['Old essay'], next_step: 'Add an overview.' },
            grammar: { score: null },
        }, feedback: 'Feedback', strengths: ['Clear'], improvements: ['Overview'], priority_actions: ['Add summary'],
        paragraph_analysis: [{ paragraph_number: 1, excerpt: 'Old essay', comment: 'Comment', next_step: 'Next' }],
        task_requirements: [{ requirement: 'Overview', status: 'partial', comment: 'Incomplete', evidence: ['Old essay'] }],
        structure_analysis: [{ component: 'overview', status: 'partial', comment: 'Develop overview.', evidence: ['Old essay'], next_step: 'Add trend.' }],
        issues: [{ category: 'grammar', original: 'Old', replacement: 'New', explanation: 'Reason', applicable: false }],
    } };
    const before = JSON.stringify(submission);
    const report = writingReport(submission, { content: 'LATEST UNSENT ESSAY', topic: '<script>literal topic</script>', task: 'ielts_task_1' });
    assert.equal(report.filename, 'writing-attempt-1-v2.txt');
    for (const text of ['Old essay 😀', '6.5 (thang 9)', 'Task Achievement: 6.0', 'Grammatical Range & Accuracy: Chưa đủ dữ liệu',
        'Add an overview.', 'Đoạn 1', 'Đáp ứng một phần', 'Vị trí chưa xác minh', '<script>literal topic</script>', 'Tổng quan (Overview)', 'Add trend.']) assert.ok(report.text.includes(text), text);
    assert.ok(!report.text.includes('LATEST UNSENT ESSAY'));
    assert.equal(JSON.stringify(submission), before);
});

test('legacy CEFR report preserves scale and missing score without requiring new fields', () => {
    const report = writingReport({ id: '../../unsafe/file', revision: 1, status: 'completed', original: 'A1 essay',
        result: { overall_score: null, criteria: { grammar: { score: 20 } }, feedback: 'Practice' } });
    assert.equal(report.filename, 'writing-unsafefile-v1.txt');
    assert.ok(report.text.includes('Chưa đủ dữ liệu (thang 100)'));
    assert.ok(report.text.includes('Ngữ pháp: 20'));
    assert.ok(!report.text.includes('undefined'));
});

test('incomplete and failed submissions cannot export a valid assessment', () => {
    for (const status of ['failed', 'queued', 'processing']) {
        assert.throws(() => writingReport({ status, original: 'Essay', result: {} }), /WRITING_REPORT_UNAVAILABLE/);
    }
    assert.throws(() => writingReport({ status: 'completed', result: {} }), /WRITING_REPORT_UNAVAILABLE/);
});

test('report preserves issue-change observations without claiming errors were fixed', () => {
    const report = writingReport({ id: '1', revision: 2, status: 'completed', original: 'New essay', result: { criteria: {} },
        comparison: { previous_revision: 1, issue_changes: {
            recurring: { count: 0, items: [] }, newly_reported: { count: 0, items: [] }, unverified_count: 1,
            not_reported: { count: 1, items: [{ original: 'Old phrase', replacement: 'New phrase', explanation: 'Reason', original_still_present: false }] },
        } } });
    assert.ok(report.text.includes('GÓP Ý SO VỚI PHIÊN BẢN 1'));
    assert.ok(report.text.includes('AI không báo lại: 1'));
    assert.ok(report.text.includes('chưa xác nhận lỗi đã sửa'));
});

test('CEFR report retains the selected target and qualitative evidence without a score conversion', () => {
    const report = writingReport({ id: '1', revision: 1, status: 'completed', original: 'I likes books.', result: { overall_score: 25, criteria: {},
        cefr_target_analysis: { target: 'A2', expectation: 'Short everyday messages.', aspects: [
            { aspect: 'language', status: 'partial', comment: 'Check agreement.', next_step: 'Use I like.', evidence: ['I likes'] },
        ] } } });
    assert.ok(report.text.includes('SO VỚI MỤC TIÊU CEFR A2'));
    assert.ok(report.text.includes('Thể hiện một phần'));
    assert.ok(report.text.includes('Dẫn chứng: I likes'));
    assert.ok(report.text.includes('không quy đổi từ điểm 100'));
});
