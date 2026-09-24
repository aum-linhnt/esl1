export const AI_TUTOR_ASSET_VERSION = '0.2.0-dev';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
async function json(url, method = 'GET', data) {
    const response = await fetch(url, {
        method, credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: data === undefined ? undefined : JSON.stringify(data),
    });
    const body = await response.json();
    if (!response.ok) throw new Error(body.error?.code ?? body.message ?? 'AI_REQUEST_FAILED');
    return body;
}
function paragraph(parent, text) {
    const node = document.createElement('p');
    node.textContent = text;
    parent.append(node);
    return node;
}
function status(root, text) { root.querySelector('[data-status]').textContent = text; }
function theme() {
    document.querySelectorAll('[data-tai-theme]').forEach(button => {
        const root = button.closest('[data-ai-tutor-root]') ?? document.body;
        try {
            const saved = localStorage.getItem('tai-theme');
            if (['light', 'dark'].includes(saved)) root.dataset.aiTutorTheme = saved;
        } catch {}
        button.addEventListener('click', () => {
            const dark = root.dataset.aiTutorTheme === 'dark'
                || (root.dataset.aiTutorTheme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
            root.dataset.aiTutorTheme = dark ? 'light' : 'dark';
            try { localStorage.setItem('tai-theme', root.dataset.aiTutorTheme); } catch {}
        });
    });
}
function chat(root) {
    const api = root.dataset.api;
    const lesson = root.dataset.lesson;
    const form = root.querySelector('[data-chat-form]');
    const history = root.querySelector('[data-history]');
    const retry = root.querySelector('[data-retry]');
    const mode = root.querySelector('[data-mode]');
    const storageKey = 'tai-chat:' + lesson;
    let conversation = null, pending = null, busy = false;
    try {
        const saved = JSON.parse(sessionStorage.getItem(storageKey) ?? '{}');
        conversation = saved.conversation ?? null;
        pending = saved.pending ?? null;
        form.elements.message.value = saved.draft ?? pending?.message ?? '';
    } catch { /* Storage may be unavailable. Server idempotency remains authoritative. */ }
    const save = () => {
        try { sessionStorage.setItem(storageKey, JSON.stringify({ conversation, pending, draft: form.elements.message.value })); } catch {}
    };
    form.elements.message.addEventListener('input', save);
    const controls = state => {
        busy = state;
        form.querySelector('button[type="submit"]').disabled = state || pending !== null;
        retry.disabled = state;
        retry.hidden = pending === null;
        mode.disabled = state || conversation !== null;
        root.querySelector('[data-reload]').disabled = state;
    };
    const sources = (message, parent) => {
        for (const source of message.sources ?? []) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = '[' + source.citation + '] ' + source.title;
            button.addEventListener('click', async () => {
                try {
                    const detail = await json(api + '/messages/' + message.id + '/sources/' + source.chunk_id);
                    paragraph(parent, detail.content);
                    button.disabled = true;
                } catch (error) { status(root, error.message); }
            });
            parent.append(button);
        }
    };
    async function reload() {
        if (!conversation) return;
        try {
            const data = await json(api + '/conversations/' + conversation);
            history.replaceChildren();
            mode.value = data.conversation.teaching_mode;
            for (const message of data.messages) {
                paragraph(history, 'Bạn: ' + message.user_content);
                const block = document.createElement('div');
                history.append(block);
                paragraph(block, 'Gia sư: ' + (message.content ?? message.status));
                sources(message, block);
                if (pending && message.request_id === pending.request_id && message.status === 'completed') {
                    pending = null;
                }
            }
            save();
            controls(false);
        } catch (error) { status(root, error.message); }
    }
    async function send() {
        if (busy) return;
        controls(true);
        status(root, 'Đang xử lý… Không gửi lại bằng mã yêu cầu mới khi kết nối gián đoạn.');
        const block = document.createElement('div');
        const output = paragraph(block, '');
        try {
            if (!conversation) {
                const created = await json(api + '/conversations', 'POST', { lesson_id: lesson, teaching_mode: mode.value });
                conversation = created.id;
                save();
            }
            if (!pending) {
                pending = { message: form.elements.message.value, request_id: crypto.randomUUID(), idempotency_key: crypto.randomUUID() };
                save();
            }
            paragraph(history, 'Bạn: ' + pending.message);
            history.append(block);
            const response = await fetch(api + '/conversations/' + conversation + '/messages', {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'text/event-stream', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(pending),
            });
            if (!response.ok || !response.headers.get('content-type')?.includes('text/event-stream')) {
                const data = await response.json();
                throw new Error(data.error?.code ?? data.message ?? 'AI_REQUEST_FAILED');
            }
            const reader = response.body.getReader(), decoder = new TextDecoder();
            let buffer = '', completed = false;
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                let boundary;
                while ((boundary = buffer.indexOf('\n\n')) !== -1) {
                    const frame = buffer.slice(0, boundary);
                    buffer = buffer.slice(boundary + 2);
                    const event = frame.split('\n').find(line => line.startsWith('event:'))?.slice(6).trim();
                    const data = JSON.parse(frame.split('\n').filter(line => line.startsWith('data:')).map(line => line.slice(5).trim()).join('\n'));
                    if (event === 'delta') output.textContent += data.text;
                    if (event === 'error') throw new Error(data.code);
                    if (event === 'completed') {
                        completed = true;
                        output.textContent = data.content;
                        sources(data, block);
                        status(root, 'Hoàn tất · Credit đã dùng: ' + (data.metadata?.credit_units ?? 0)
                            + (data.credit_balance !== null ? ' · Số dư: ' + data.credit_balance : '')
                            + (data.metadata?.missing_sources ? ' · Chưa có nguồn Knowledge phù hợp.' : ''));
                    }
                }
            }
            if (!completed) throw new Error('AI_STREAM_INTERRUPTED — dùng Thử lại cùng yêu cầu hoặc tải lại hội thoại.');
            pending = null;
            form.elements.message.value = '';
            save();
        } catch (error) {
            status(root, error.message);
        } finally { controls(false); }
    }
    form.addEventListener('submit', event => { event.preventDefault(); if (!pending) send(); });
    retry.addEventListener('click', send);
    root.querySelector('[data-reload]').addEventListener('click', reload);
    controls(false);
    reload();
}
function knowledge(root) {
    const api = root.dataset.api;
    const form = root.querySelector('[data-document-form]');
    const version = root.querySelector('[data-version]');
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;
        try {
            const data = Object.fromEntries(new FormData(form));
            data.contains_answers = data.contains_answers === '1';
            const created = await json(api + '/knowledge/documents', 'POST', data);
            version.value = created.version_id;
            status(root, 'Đã tạo bản nháp. Document: ' + created.id + '. Bấm tạo vector để xử lý.');
        } catch (error) { status(root, error.message); }
        finally { button.disabled = false; }
    });
    for (const [selector, action, method] of [['[data-process]', '/process', 'POST'], ['[data-check]', '', 'GET'], ['[data-publish]', '/publish', 'POST']]) {
        const button = root.querySelector(selector);
        button.addEventListener('click', async () => {
            if (!/^[0-9a-f-]{36}$/i.test(version.value.trim())) { status(root, 'Cần ID version hợp lệ.'); return; }
            button.disabled = true;
            try {
                const result = await json(api + '/knowledge/document-versions/' + encodeURIComponent(version.value.trim()) + action, method);
                status(root, JSON.stringify(result));
            } catch (error) { status(root, error.message); }
            finally { button.disabled = false; }
        });
    }
}
function boot() {
    theme();
    document.querySelectorAll('[data-tai-chat]').forEach(chat);
    document.querySelectorAll('[data-tai-knowledge]').forEach(knowledge);
}
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
}
