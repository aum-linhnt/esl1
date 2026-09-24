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
    let lesson = root.dataset.lesson;
    const form = root.querySelector('[data-chat-form]');
    const history = root.querySelector('[data-history]');
    const retry = root.querySelector('[data-retry]');
    const mode = root.querySelector('[data-mode]');
    let storageKey = 'tai-chat:' + root.dataset.actor + ':' + lesson;
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
        root.querySelector('[data-export]').disabled = state || conversation === null;
        root.querySelector('[data-delete]').disabled = state || conversation === null;
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
        if (!conversation || busy) return;
        controls(true);
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
        } catch (error) { status(root, error.message); }
        finally { controls(false); }
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
    root.querySelector('[data-export]').addEventListener('click', () => {
        if (!busy && conversation) window.open(api + '/conversations/' + conversation + '/export', '_blank', 'noopener');
    });
    root.querySelector('[data-delete]').addEventListener('click', async () => {
        if (busy || !conversation || !confirm('Xóa vĩnh viễn nội dung hội thoại này? Lịch sử credit/usage vẫn được giữ.')) return;
        controls(true);
        try {
            await json(api + '/conversations/' + conversation, 'DELETE');
            conversation = pending = null;
            form.elements.message.value = '';
            history.replaceChildren();
            save();
            status(root, 'Đã xóa nội dung hội thoại. Không thể khôi phục từ ứng dụng.');
        } catch (error) { status(root, error.message); }
        finally { controls(false); }
    });
    controls(false);
    const ready = reload();
    return {
        async ask(text) {
            await ready;
            if (busy || pending) throw new Error('AI_CONVERSATION_BUSY');
            if (typeof text !== 'string' || !text.trim() || text.length > 4000) throw new Error('AI_MESSAGE_INVALID');
            form.elements.message.value = text;
            save();
            return send();
        },
        async setContext({ lessonId }) {
            await ready;
            if (busy) throw new Error('AI_CONVERSATION_BUSY');
            if (typeof lessonId !== 'string' || !lessonId || lessonId.length > 191) throw new Error('AI_CONTEXT_INVALID');
            controls(true);
            try {
                await json(api + '/context?lesson_id=' + encodeURIComponent(lessonId));
                save();
                lesson = lessonId;
                root.dataset.lesson = lesson;
                storageKey = 'tai-chat:' + root.dataset.actor + ':' + lesson;
                conversation = pending = null;
                form.elements.message.value = '';
                mode.value = 'hints_first';
                try {
                    const saved = JSON.parse(sessionStorage.getItem(storageKey) ?? '{}');
                    conversation = saved.conversation ?? null;
                    pending = saved.pending ?? null;
                    form.elements.message.value = saved.draft ?? pending?.message ?? '';
                } catch {}
                root.querySelector('[data-lesson-label]').textContent = lesson;
                history.replaceChildren();
                status(root, 'Đã chuyển ngữ cảnh bài học.');
            } finally { controls(false); }
            return reload();
        },
    };
}
function widget(root, client) {
    const panel = root.querySelector('[data-widget-panel]');
    const launcher = root.querySelector('[data-widget-open]');
    const expand = root.querySelector('[data-widget-expand]');
    const open = () => {
        panel.hidden = false;
        launcher.setAttribute('aria-expanded', 'true');
        panel.querySelector('button')?.focus();
    };
    const close = () => {
        panel.hidden = true;
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
        // Keep DOM, fetch and reader alive while collapsed.
    };
    launcher.addEventListener('click', () => panel.hidden ? open() : close());
    root.querySelector('[data-widget-close]').addEventListener('click', close);
    root.addEventListener('keydown', event => {
        if (event.key === 'Escape') { close(); event.stopPropagation(); }
    });
    expand?.addEventListener('click', () => {
        const expanded = root.classList.toggle('tai-widget--expanded');
        expand.setAttribute('aria-pressed', String(expanded));
        expand.textContent = expanded ? 'Về khung bài học' : 'Mở rộng';
    });
    return {
        open, close,
        ask: text => { open(); return client.ask(text); },
        setContext: context => client.setContext(context),
    };
}
function knowledge(root) {
    const api = root.dataset.api;
    const form = root.querySelector('[data-document-form]');
    const version = root.querySelector('[data-version]');
    let selected = null;
    const versionForm = root.querySelector('[data-version-form]');
    const listForm = root.querySelector('[data-list-form]');
    const withdraw = root.querySelector('[data-withdraw]');
    const actionButton = (parent, label, work) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.addEventListener('click', async () => {
            button.disabled = true;
            try { await work(); } catch (error) { status(root, error.message); }
            finally { button.disabled = false; }
        });
        parent.append(button);
    };
    async function loadVersions(id) {
        const data = await json(api + '/knowledge/documents/' + id + '/versions');
        selected = id;
        root.querySelector('[data-selected]').textContent = data.document.title;
        versionForm.querySelector('button').disabled = false;
        withdraw.disabled = !data.document.published_version_id;
        const container = root.querySelector('[data-versions]');
        container.replaceChildren();
        for (const item of data.versions) {
            actionButton(container, item.created_at + ' · ' + item.status + ' · ' + item.id, async () => {
                version.value = item.id;
                const preview = await json(api + '/knowledge/document-versions/' + item.id + '/content');
                root.querySelector('[data-preview]').textContent = preview.content;
                versionForm.elements.content.value = preview.content;
                versionForm.elements.format.value = preview.format;
                status(root, 'Đã chọn version. Nội dung chỉ được tạo thành bản mới, không ghi đè bản cũ.');
            });
        }
    }
    listForm.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            const docs = await json(api + '/knowledge/documents?lesson_id=' + encodeURIComponent(listForm.elements.lesson_id.value));
            const container = root.querySelector('[data-documents]');
            container.replaceChildren();
            for (const doc of docs) actionButton(container, doc.title + ' · ' + doc.visibility, () => loadVersions(doc.id));
            if (!docs.length) paragraph(container, 'Chưa có tài liệu.');
        } catch (error) { status(root, error.message); }
    });
    versionForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!selected) return;
        const button = versionForm.querySelector('button');
        button.disabled = true;
        try {
            const created = await json(api + '/knowledge/documents/' + selected + '/versions', 'POST', Object.fromEntries(new FormData(versionForm)));
            version.value = created.id;
            await loadVersions(selected);
            status(root, 'Đã tạo version mới. Cần xử lý vector trước khi publish.');
        } catch (error) { status(root, error.message); }
        finally { button.disabled = false; }
    });
    withdraw.addEventListener('click', async () => {
        if (!selected || !confirm('Thu hồi tài liệu khỏi truy xuất của học viên?')) return;
        withdraw.disabled = true;
        try {
            await json(api + '/knowledge/documents/' + selected + '/withdraw', 'POST');
            await loadVersions(selected);
            status(root, 'Đã thu hồi publish; lịch sử version vẫn được giữ.');
        } catch (error) { status(root, error.message); withdraw.disabled = false; }
    });
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
                if (selected) await loadVersions(selected);
            } catch (error) { status(root, error.message); }
            finally { button.disabled = false; }
        });
    }
}
function boot() {
    theme();
    document.querySelectorAll('[data-tai-chat]').forEach(root => {
        const client = chat(root);
        const shell = root.closest('[data-tai-widget]');
        if (shell) window.AITutor = widget(shell, client);
    });
    document.querySelectorAll('[data-tai-knowledge]').forEach(knowledge);
}
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
}
