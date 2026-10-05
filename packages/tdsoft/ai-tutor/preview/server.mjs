import http from 'node:http';
import { randomUUID } from 'node:crypto';
import { readFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, resolve, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const directory = dirname(fileURLToPath(import.meta.url));
const root = resolve(directory, '../../../..');
const port = Number(process.argv[2] ?? 9013);
if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('Invalid preview port');
const cache = mkdtempSync(join(tmpdir(), 'ai-tutor-preview-'));
const manifest = JSON.parse(readFileSync(join(root, 'public/build/manifest.json'), 'utf8'));
const assets = new Map([
    ['/preview.css', { entry: 'resources/scss/ai-tutor.scss', type: 'text/css' }],
    ['/preview.js', { entry: 'resources/js/ai-tutor.js', type: 'text/javascript' }],
]);
const pages = new Map();
const runId = randomUUID();
for (const page of ['widget', 'tutor', 'knowledge']) {
    const rendered = spawnSync(process.env.AI_PREVIEW_PHP ?? 'php8.3',
        [join(directory, 'render.php'), page, String(port), cache, runId], { encoding: 'utf8', maxBuffer: 4 * 1024 * 1024 });
    if (rendered.status !== 0) throw new Error('Preview template failed: ' + rendered.stderr);
    const head = '<link rel="stylesheet" href="/preview.css"><script type="module" src="/preview.js"></script>';
    const banner = '<header style="padding:16px;background:#312e81;color:white;font:14px system-ui"><strong>PREVIEW / MOCK — Không phải website thật. Không nhập dữ liệu nhạy cảm.</strong><nav style="margin-top:10px"><a style="color:white;margin-right:20px" href="/">Bài học + widget</a><a style="color:white;margin-right:20px" href="/tutor">Chat riêng</a><a style="color:white" href="/knowledge">Knowledge</a></nav><p>Dữ liệu giả trong bộ nhớ; tắt server sẽ mất. Dùng lesson-1 hoặc lesson-2. Gửi “/error” để xem lỗi mô phỏng.</p></header>';
    pages.set(page === 'widget' ? '/' : '/' + page,
        rendered.stdout.replaceAll('http://127.0.0.1:' + port, '')
            .replace('</head>', head + '</head>').replace(/(<body[^>]*>)/, '$1' + banner));
}
const conversations = new Map(), messages = new Map(), documents = new Map(), versions = new Map();
const stamp = () => new Date().toISOString();
const prefix = '/ai-tutor/api/v1/';
function json(res, data, status = 200) {
    res.writeHead(status, { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' });
    res.end(JSON.stringify(data));
}
function fail(res, code, status = 409) { json(res, { error: { code } }, status); }
async function body(req) {
    let data = '';
    for await (const chunk of req) {
        data += chunk;
        if (Buffer.byteLength(data) > 250000) throw new Error('PREVIEW_BODY_TOO_LARGE');
    }
    return data ? JSON.parse(data) : {};
}
function newVersion(document, data) {
    const version = { id: randomUUID(), document_id: document.id, status: 'draft',
        format: data.format ?? 'text', content: String(data.content ?? ''), created_at: stamp() };
    versions.set(version.id, version);
    return version;
}
const seed = { id: randomUUID(), title: 'Present simple — tài liệu mẫu', lesson_id: 'lesson-1',
    visibility: 'learners', created_at: stamp() };
const seedVersion = newVersion(seed, { content: 'Dùng present simple cho thói quen. Với he/she/it, động từ thường thêm -s hoặc -es.' });
seedVersion.status = 'published';
seed.published_version_id = seedVersion.id;
documents.set(seed.id, seed);

const server = http.createServer(async (req, res) => {
    // Separate loopback origin only; never proxy or fall through to a filesystem web root.
    if (!['127.0.0.1:' + port, 'localhost:' + port].includes(req.headers.host)) return fail(res, 'PREVIEW_HOST_DENIED', 403);
    const allowedOrigins = ['http://127.0.0.1:' + port, 'http://localhost:' + port];
    if ((req.headers.origin && !allowedOrigins.includes(req.headers.origin)) || req.headers['sec-fetch-site'] === 'cross-site')
        return fail(res, 'PREVIEW_ORIGIN_DENIED', 403);
    res.setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
    res.setHeader('X-Content-Type-Options', 'nosniff');
    try {
        const url = new URL(req.url, 'http://127.0.0.1:' + port);
        if (req.method === 'GET' && pages.has(url.pathname)) {
            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' });
            return res.end(pages.get(url.pathname));
        }
        if (req.method === 'GET' && assets.has(url.pathname)) {
            const asset = assets.get(url.pathname);
            res.writeHead(200, { 'Content-Type': asset.type, 'Cache-Control': 'no-store' });
            return res.end(readFileSync(join(root, 'public/build', manifest[asset.entry].file)));
        }
        if (!url.pathname.startsWith(prefix)) return fail(res, 'PREVIEW_NOT_FOUND', 404);
        const path = url.pathname.slice(prefix.length);
        const data = ['POST', 'DELETE'].includes(req.method) ? await body(req) : {};
        if (path === 'context') return ['lesson-1', 'lesson-2'].includes(url.searchParams.get('lesson_id'))
            ? json(res, { lesson_id: url.searchParams.get('lesson_id') }) : fail(res, 'AI_CONTEXT_FORBIDDEN', 403);
        if (path === 'conversations' && req.method === 'POST') {
            if (!['lesson-1', 'lesson-2'].includes(data.lesson_id)) return fail(res, 'AI_CONTEXT_FORBIDDEN', 403);
            const conversation = { id: randomUUID(), lesson_id: data.lesson_id, teaching_mode: data.teaching_mode, created_at: stamp() };
            conversations.set(conversation.id, conversation);
            return json(res, conversation, 201);
        }
        const c = path.match(/^conversations\/([^/]+)(?:\/(messages|export))?$/);
        if (c) {
            const conversation = conversations.get(c[1]);
            if (!conversation) return fail(res, 'AI_CONVERSATION_NOT_FOUND', 404);
            const history = () => [...messages.values()].filter(m => m.conversation_id === conversation.id);
            if (req.method === 'GET') {
                if (c[2] === 'export') res.setHeader('Content-Disposition', 'attachment; filename="preview-conversation.json"');
                return json(res, { conversation, messages: history() });
            }
            if (req.method === 'DELETE') {
                if (history().some(m => m.status === 'processing')) return fail(res, 'AI_CONVERSATION_BUSY');
                for (const message of history()) messages.delete(message.id);
                conversations.delete(conversation.id);
                return json(res, { deleted: true });
            }
            if (c[2] === 'messages' && req.method === 'POST') {
                if (data.message === '/error') return fail(res, 'AI_PROVIDER_UNAVAILABLE');
                let message = history().find(m => m.request_id === data.request_id);
                if (message && message.user_content !== data.message) return fail(res, 'AI_REQUEST_DUPLICATE');
                if (message?.status === 'processing') return fail(res, 'AI_CONVERSATION_BUSY');
                if (!message) {
                    message = { id: randomUUID(), request_id: data.request_id, conversation_id: conversation.id,
                        user_content: String(data.message ?? ''), status: 'processing', content: null, sources: [],
                        metadata: { credit_units: 1, missing_sources: true }, credit_balance: 99 };
                    messages.set(message.id, message);
                    const source = [...documents.values()].find(d => d.lesson_id === conversation.lesson_id && d.visibility === 'learners' && d.published_version_id);
                    if (source && conversation.teaching_mode !== 'exam') {
                        message.sources = [{ chunk_id: source.published_version_id, title: source.title, citation: 1 }];
                        message.metadata.missing_sources = false;
                    }
                    if (conversation.teaching_mode === 'exam') message.metadata.credit_units = 0;
                }
                const answer = conversation.teaching_mode === 'exam'
                    ? '[MOCK] Bài thi không cho phép hỗ trợ đáp án.'
                    : '[MOCK] Với “she”, động từ “work” thêm -s: “She works every day.” Đây là phản hồi cố định để kiểm tra giao diện, không phải kết quả AI thật. Bạn thử đặt một câu với “he” nhé.';
                res.writeHead(200, { 'Content-Type': 'text/event-stream', 'Cache-Control': 'no-store', 'X-Accel-Buffering': 'no' });
                const event = (name, payload) => res.write('event: ' + name + '\ndata: ' + JSON.stringify(payload) + '\n\n');
                event('start', { request_id: message.request_id });
                if (message.status !== 'completed') {
                    for (const token of answer.match(/.{1,12}/gu) ?? []) {
                        event('delta', { text: token });
                        await new Promise(resolve => setTimeout(resolve, 80));
                    }
                    message.content = answer;
                    message.status = 'completed';
                }
                event('completed', message);
                return res.end();
            }
        }
        const sourceRoute = path.match(/^messages\/([^/]+)\/sources\/([^/]+)$/);
        if (sourceRoute && req.method === 'GET') {
            const message = messages.get(sourceRoute[1]);
            const source = message?.sources.find(s => s.chunk_id === sourceRoute[2]);
            const version = source && versions.get(source.chunk_id);
            if (!version || version.status !== 'published') return fail(res, 'AI_SOURCE_NOT_FOUND', 404);
            return json(res, { content: version.content, title: source.title, citation: source.citation });
        }
        if (path === 'knowledge/documents') {
            if (req.method === 'GET') return json(res, [...documents.values()].filter(d => d.lesson_id === url.searchParams.get('lesson_id')));
            if (req.method === 'POST') {
                const document = { id: randomUUID(), title: String(data.title), lesson_id: data.lesson_id,
                    visibility: data.visibility, created_at: stamp() };
                const version = newVersion(document, data);
                documents.set(document.id, document);
                return json(res, { id: document.id, version_id: version.id }, 201);
            }
        }
        const d = path.match(/^knowledge\/documents\/([^/]+)\/(versions|withdraw)$/);
        if (d) {
            const document = documents.get(d[1]);
            if (!document) return fail(res, 'AI_DOCUMENT_NOT_FOUND', 404);
            if (d[2] === 'withdraw' && req.method === 'POST') {
                if (document.published_version_id) versions.get(document.published_version_id).status = 'ready';
                document.published_version_id = null;
                return json(res, { status: 'unpublished' });
            }
            if (req.method === 'GET') return json(res, { document, versions: [...versions.values()].filter(v => v.document_id === document.id) });
            if (req.method === 'POST') return json(res, newVersion(document, data), 201);
        }
        const v = path.match(/^knowledge\/document-versions\/([^/]+)(?:\/(process|publish|content))?$/);
        if (v) {
            const version = versions.get(v[1]);
            if (!version) return fail(res, 'AI_DOCUMENT_NOT_FOUND', 404);
            if (req.method === 'POST' && v[2] === 'process') {
                version.status = 'ready';
                return json(res, { id: version.id, status: 'ready', processing_status: 'completed' });
            }
            if (req.method === 'POST' && v[2] === 'publish') {
                if (!['ready', 'published'].includes(version.status)) return fail(res, 'AI_DOCUMENT_NOT_READY');
                const doc = documents.get(version.document_id);
                if (doc.published_version_id) versions.get(doc.published_version_id).status = 'archived';
                version.status = 'published';
                doc.published_version_id = version.id;
                return json(res, { id: version.id, status: 'published' });
            }
            if (req.method === 'GET') return json(res, v[2] === 'content' ? version : {
                id: version.id, status: version.status, processing_status: version.status === 'draft' ? 'pending' : 'completed',
            });
        }
        return fail(res, 'PREVIEW_NOT_FOUND', 404);
    } catch {
        if (!res.headersSent) fail(res, 'PREVIEW_REQUEST_INVALID', 400);
        else res.end();
    }
});
server.listen(port, '127.0.0.1', () => console.log('AI Tutor MOCK preview: http://127.0.0.1:' + port));
server.on('error', error => { console.error('Preview server:', error.code); process.exitCode = 1; });
