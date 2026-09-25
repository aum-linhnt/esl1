import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';

const base = 'http://127.0.0.1:' + (process.argv[2] ?? '9013');
const api = base + '/ai-tutor/api/v1/';
async function call(path, method = 'GET', body) {
    const response = await fetch(api + path, { method, headers: { 'Content-Type': 'application/json' },
        body: body === undefined ? undefined : JSON.stringify(body) });
    assert.equal(response.ok, true, path);
    return response.json();
}
for (const page of ['', '/tutor', '/knowledge']) {
    const response = await fetch(base + page);
    const html = await response.text();
    assert.equal(response.status, 200);
    assert.ok(html.includes('PREVIEW / MOCK'));
    assert.ok(html.includes('data-api="/ai-tutor/api/v1"'));
}
for (const path of ['/.env', '/composer.json', '/admin/ai/license', '/upload']) {
    assert.equal((await fetch(base + path)).status, 404, path);
}
assert.equal((await fetch(base, { headers: { Origin: 'https://untrusted.example' } })).status, 403);
assert.equal((await fetch(api + 'context?lesson_id=unknown')).status, 403);
const conversation = await call('conversations', 'POST', { lesson_id: 'lesson-1', teaching_mode: 'hints_first' });
const payload = { message: 'Hello', request_id: randomUUID(), idempotency_key: randomUUID() };
const send = async () => (await fetch(api + 'conversations/' + conversation.id + '/messages', {
    method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'text/event-stream' }, body: JSON.stringify(payload),
})).text();
assert.match(await send(), /event: delta/);
assert.match(await send(), /event: completed/);
const history = await call('conversations/' + conversation.id);
assert.equal(history.messages.length, 1);
assert.equal(history.messages[0].status, 'completed');
const source = history.messages[0].sources[0];
assert.ok((await call('messages/' + history.messages[0].id + '/sources/' + source.chunk_id)).content);
const document = await call('knowledge/documents', 'POST', { lesson_id: 'lesson-1', title: 'Smoke', content: 'Example', visibility: 'learners' });
await call('knowledge/document-versions/' + document.version_id + '/process', 'POST', {});
await call('knowledge/document-versions/' + document.version_id + '/publish', 'POST', {});
assert.equal((await call('knowledge/documents/' + document.id + '/versions')).document.published_version_id, document.version_id);
await call('knowledge/documents/' + document.id + '/withdraw', 'POST', {});
await call('conversations/' + conversation.id, 'DELETE');
console.log('Preview smoke passed: pages, isolation, SSE replay, citation, versions, publish/withdraw, delete.');
