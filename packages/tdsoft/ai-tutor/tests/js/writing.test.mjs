import test from 'node:test';
import assert from 'node:assert/strict';
import { WritingSession, applyWritingIssues, wordCount } from '../../resources/js/writing.js';

function setup(cached = null, actor = 'learner-1') {
    const map = new Map(cached ? [[`tai-writing:${actor}:draft-1`, JSON.stringify(cached)]] : []);
    const storage = { getItem: key => map.get(key), setItem: (key, value) => map.set(key, value) };
    let draft = { id: 'draft-1', revision: 1, content: 'I likes reading books.', topic: 'Hobby', task: 'cefr_writing', profile: { framework: 'cefr', target: 'B1' } };
    const calls = [], submissions = new Map(); let counter = 0, hook;
    const request = async (url, method = 'GET', body) => {
        calls.push({ url, method, body });
        if (hook) { const result = await hook(url, method, body); if (result !== undefined) return result; }
        if (url === '/writing/drafts/draft-1' && method === 'GET') return { ...draft };
        if (method === 'PATCH') { draft = { ...draft, content: body.content, revision: draft.revision + 1 }; return { ...draft }; }
        if (url.startsWith('/writing/drafts/draft-1/submissions')) {
            const requestId = new URL(url, 'http://mock').searchParams.get('request_id');
            return { data: [...submissions.values()].filter(s => !requestId || s.request_id === requestId), next_page: null };
        }
        if (method === 'GET' && url.includes('/submissions/')) return submissions.get(url.split('/').at(-1));
        if (method === 'POST') {
            const s = { id: 'submission-' + body.request_id, request_id: body.request_id, revision: body.revision ?? 1,
                status: 'queued', recovery: 'same_request', original: draft.content };
            submissions.set(s.id, s); return s;
        }
        throw new Error('Unexpected mock request');
    };
    const options = { actor, draft: 'draft-1', api: '/writing', storage, request, uuid: () => `uuid-${++counter}` };
    const session = new WritingSession(options);
    return { session, calls, submissions, map, storage, options,
        setDraft: value => { draft = { ...draft, ...value }; }, setHook: value => { hook = value; } };
}

test('word count and UTF-16 issue application preserve Unicode and reject overlapping/unverified spans', () => {
    assert.equal(wordCount("I'm learning English — Tôi học. 😀"), 5);
    const text = '😀 I likes books.';
    const issues = [ { applicable: true, start_utf16: 5, end_utf16: 10, original: 'likes', replacement: 'like' },
        { applicable: true, start_utf16: 11, end_utf16: 16, original: 'books', replacement: 'reading' },
        { applicable: true, start_utf16: 5, end_utf16: 10, original: 'likes', replacement: 'love' } ];
    assert.equal(applyWritingIssues(text, issues, new Set([0, 1])), '😀 I like reading.');
    assert.throws(() => applyWritingIssues(text, issues, new Set([0, 2])), /OVERLAP/);
    assert.throws(() => applyWritingIssues('changed essay', issues, new Set([0])), /SPAN_INVALID/);
});

test('typing while autosave is in flight does not lose later edits', async () => {
    const f = setup(); await f.session.load();
    let release;
    f.setHook(async (_, method, body) => {
        if (method === 'PATCH') { await new Promise(resolve => { release = resolve; }); return { revision: 2, content: body.content }; }
    });
    f.session.edit('First updated essay.'); const save = f.session.save();
    await new Promise(setImmediate); f.session.edit('Later typing is preserved.'); release(); await save;
    assert.equal(f.session.content, 'Later typing is preserved.');
    assert.equal(f.session.base, 'First updated essay.'); assert.equal(f.session.revision, 2); assert.equal(f.session.dirty, true);
});

test('lost PATCH response reconciles exact saved content without another write', async () => {
    const f = setup(); await f.session.load(); f.session.edit('Durable updated essay.');
    f.setHook(async (_, method, body) => {
        if (method === 'PATCH') { f.setDraft({ content: body.content, revision: 2, updated_at: '2026-10-05T03:24:00Z' }); throw new Error('WRITING_CONNECTION'); }
    });
    await f.session.save(); assert.equal(f.session.revision, 2); assert.equal(f.session.dirty, false);
    assert.equal(f.calls.filter(c => c.method === 'PATCH').length, 1);
    assert.equal(f.session.savedAt, '2026-10-05T03:24:00Z');
    f.session.edit('A later unsaved change.');
    assert.equal(f.session.savedAt, '2026-10-05T03:24:00Z');
});

test('revision conflict preserves local editor and requires explicit resolution', async () => {
    const f = setup(); await f.session.load(); f.session.edit('My unsaved local essay.');
    f.setDraft({ content: 'Another tab essay.', revision: 2 });
    f.setHook(async (_, method) => { if (method === 'PATCH') throw Object.assign(new Error('AI_WRITING_REVISION_CONFLICT'), { code: 'AI_WRITING_REVISION_CONFLICT', status: 409 }); });
    await assert.rejects(f.session.save(), /CONFLICT/);
    assert.equal(f.session.content, 'My unsaved local essay.'); assert.equal(f.session.conflict.content, 'Another tab essay.');
    assert.equal(f.session.canSubmit, false);
    f.setHook(null); await f.session.resolveConflict(true);
    assert.equal(f.session.content, 'My unsaved local essay.'); assert.equal(f.session.conflict, null); assert.equal(f.session.dirty, false);
});

test('reload restores unsaved text and detects conflicting remote changes', async () => {
    const f = setup({ content: 'Unsaved essay.', base: 'Old essay.', revision: 1 });
    await f.session.load(); assert.equal(f.session.content, 'Unsaved essay.'); assert.ok(f.session.conflict);
    await f.session.resolveConflict(false); assert.equal(f.session.content, 'I likes reading books.');
});

test('clean cached text follows newer remote revision rather than creating a false conflict', async () => {
    const f = setup({ content: 'Old essay.', base: 'Old essay.', revision: 1 });
    await f.session.load(); assert.equal(f.session.content, 'I likes reading books.'); assert.equal(f.session.conflict, null);
});

test('submit writes durable IDs before POST and loss/reload reuses exactly those IDs', async () => {
    const f = setup(); await f.session.load();
    f.setHook(async (_, method) => { if (method === 'POST') {
        const cached = JSON.parse(f.map.get(f.session.key)); assert.equal(cached.pending.request_id, 'uuid-1'); throw new Error('WRITING_CONNECTION');
    } });
    await assert.rejects(f.session.submit(), /CONNECTION/); const pending = { ...f.session.pending };
    assert.equal(f.session.canSubmit, false);
    const reload = new WritingSession(f.options); await reload.load(); assert.deepEqual(reload.pending, pending);
    f.setHook(null); await reload.sendPending();
    const posts = f.calls.filter(c => c.method === 'POST'); assert.deepEqual(posts[0].body, posts[1].body);
    assert.equal(reload.pending.request_id, pending.request_id);
});

test('read-only recovery finds completed submit without another inference request', async () => {
    const f = setup(); await f.session.load();
    await f.session.submit(); const id = f.session.result.id;
    f.submissions.set(id, { ...f.session.result, status: 'completed', recovery: 'completed' });
    await f.session.refresh(); assert.equal(f.session.pending, null); assert.equal(f.session.result.status, 'completed');
    assert.equal(f.calls.filter(c => c.method === 'POST').length, 1);
});

test('paid submit is blocked if stable request state cannot be stored', async () => {
    const f = setup(); await f.session.load();
    f.storage.setItem = () => { throw new Error('quota'); };
    await assert.rejects(f.session.submit(), /WRITING_STORAGE/); assert.equal(f.calls.filter(c => c.method === 'POST').length, 0);
});

test('definitively rejected submit confirms no attempt before releasing pending IDs for conflict resolution', async () => {
    const f = setup(); await f.session.load();
    f.setDraft({ content: 'Another tab saved a new revision.', revision: 2 });
    f.setHook(async (_, method) => { if (method === 'POST') throw Object.assign(new Error('AI_WRITING_REVISION_CONFLICT'), { code: 'AI_WRITING_REVISION_CONFLICT', status: 409 }); });
    await assert.rejects(f.session.submit(), /CONFLICT/);
    assert.equal(f.session.pending, null); assert.equal(f.session.conflict.revision, 2);
    assert.equal(f.session.content, 'I likes reading books.');
});

test('assessment refresh failure does not discard an accessible draft or unsaved edits', async () => {
    const f = setup({ content: 'My unsaved essay.', base: 'I likes reading books.', revision: 1 });
    f.setHook(async url => { if (url.includes('/submissions')) throw new Error('WRITING_CONNECTION'); });
    await f.session.load(); assert.equal(f.session.content, 'My unsaved essay.');
    assert.equal(f.session.loadError.message, 'WRITING_CONNECTION'); assert.equal(f.session.dirty, true);
});

test('new retry requires confirmation and new_attempt; uncertain requests never create a new attempt', async () => {
    const f = setup(); await f.session.load();
    f.session.result = { id: 'failed-original', revision: 1, recovery: 'reconciliation' };
    await assert.rejects(f.session.retry(true), /RETRY_BLOCKED/);
    f.session.result.recovery = 'new_attempt'; await assert.rejects(f.session.retry(false), /RETRY_BLOCKED/);
    f.setHook(async (_, method) => { if (method === 'POST') throw new Error('WRITING_CONNECTION'); });
    await assert.rejects(f.session.retry(true), /CONNECTION/);
    assert.equal(f.session.pending.kind, 'retry'); const pending = { ...f.session.pending };
    f.setHook(null); await f.session.sendPending();
    assert.equal(f.session.pending.request_id, pending.request_id);
    assert.deepEqual(f.calls.filter(c => c.method === 'POST')[0].body, f.calls.filter(c => c.method === 'POST')[1].body);
});

test('local drafts are isolated by actor and draft', async () => {
    const f = setup(); await f.session.load(); f.session.edit('Private learner one text.');
    const other = new WritingSession({ ...f.options, actor: 'learner-2' }); await other.load();
    assert.equal(other.content, 'I likes reading books.'); assert.notEqual(other.key, f.session.key);
});

test('applying multiple issues preserves offsets, and manual edits invalidate later automatic patches', async () => {
    const f = setup(); await f.session.load();
    f.session.accept({ id: 'completed', status: 'completed', revision: 1, original: 'I likes books.', result: { issues: [
        { applicable: true, start_utf16: 2, end_utf16: 7, original: 'likes', replacement: 'like' },
        { applicable: true, start_utf16: 8, end_utf16: 13, original: 'books', replacement: 'reading' },
    ] } });
    f.session.content = 'I likes books.'; f.session.apply(0); f.session.apply(1);
    assert.equal(f.session.content, 'I like reading.');
    f.session.edit('My manually changed essay.'); assert.throws(() => f.session.apply(0), /SPAN_INVALID/);
});
