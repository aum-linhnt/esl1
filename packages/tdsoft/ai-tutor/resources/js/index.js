export const AI_TUTOR_ASSET_VERSION = '0.2.0-dev';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const statusTimers = new WeakMap();
function recoveryNotice(message) {
    if (message.recovery === 'new_attempt') return 'Yêu cầu đã thất bại và không còn giữ credit. Sau khi khắc phục lỗi, chọn “Thử lại bằng yêu cầu mới”.';
    if (message.recovery === 'reconciliation') return 'Chưa xác định được kết quả hoặc credit đang chờ đối soát. Liên hệ quản trị viên và tải lại hội thoại; không tạo lần thử mới.';
    if (message.recovery === 'blocked') return 'Không thể thử lại yêu cầu này. Tải lại hội thoại hoặc liên hệ quản trị viên. Bạn vẫn có thể bắt đầu hội thoại mới.';
    return 'Yêu cầu chưa hoàn tất. Tải lại hội thoại hoặc thử lại cùng mã yêu cầu.';
}
function newRetryAttempt(pending) {
    return { message: pending.message, request_id: crypto.randomUUID(), idempotency_key: crypto.randomUUID(),
        sent_at: new Date().toISOString(), retry_of_message_id: pending.message_id, next_hint: pending.next_hint ?? false };
}
async function json(url, method = 'GET', data) {
    const response = await fetch(url, {
        method, credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: data === undefined ? undefined : JSON.stringify(data),
    });
    const body = await response.json();
    if (!response.ok) {
        const error = new Error(body.error?.code ?? body.message ?? 'AI_REQUEST_FAILED');
        error.code = body.error?.code ?? 'AI_REQUEST_FAILED';
        throw error;
    }
    return body;
}
function paragraph(parent, text) {
    const node = document.createElement('p');
    node.textContent = text;
    parent.append(node);
    return node;
}
function status(root, text, kind = '') {
    const target = root.querySelector('[data-status]');
    const content = target.querySelector('[data-status-text]');
    if (content) content.textContent = text;
    else target.textContent = text;
    target.classList.toggle('is-error', kind === 'error');
    target.classList.toggle('is-ready', kind === 'success');
    if (root.matches('[data-tai-knowledge]')) {
        clearTimeout(statusTimers.get(target));
        target.classList.add('is-toast');
        statusTimers.set(target, setTimeout(() => target.classList.remove('is-toast'), kind === 'error' ? 10000 : 7000));
    }
}
const knowledgeError = code => ({
    AI_PROVIDER_NOT_CONFIGURED: 'Chưa cấu hình AI provider. Hãy thêm API key và embedding model rồi tải lại trang.',
    AI_DOCUMENT_NOT_READY: 'Version chưa sẵn sàng để publish. Hãy tạo vector và chờ trạng thái ready.',
    AI_DISABLED: 'AI Tutor đang tắt trong cấu hình.',
    BILLING_MODE_NOT_SUPPORTED: 'Chế độ billing hiện tại chưa hỗ trợ tạo vector.',
    AI_CREDIT_INSUFFICIENT: 'Tài khoản không đủ credit để tạo vector.',
    AI_DAILY_QUOTA_EXCEEDED: 'Đã hết quota AI trong ngày.',
    AI_PROVIDER_AUTH_FAILED: 'API key không hợp lệ hoặc không có quyền sử dụng model.',
    AI_PROVIDER_RATE_LIMITED: 'Provider đang giới hạn yêu cầu. Vui lòng thử lại sau.',
    AI_PROVIDER_UNAVAILABLE: 'Provider đang tạm thời không khả dụng.',
}[code] ?? code);
const knowledgeLabel = value => ({
    private: 'Riêng tư', learners: 'Học viên', draft: 'Bản nháp', processing: 'Đang xử lý',
    ready: 'Sẵn sàng', published: 'Đang publish', archived: 'Đã lưu trữ', failed: 'Thất bại',
    text: 'Text', markdown: 'Markdown', html: 'HTML',
}[value] ?? value);
const knowledgeDate = value => {
    const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return match ? `${match[3]}/${match[2]}/${match[1]} · ${match[4]}:${match[5]}` : String(value ?? '');
};
const shortId = value => String(value).length > 28 ? String(value).slice(0, 14) + '…' + String(value).slice(-10) : String(value);
function enhanceSelect(select) {
    if (select.dataset.enhanced === '1') return;
    select.dataset.enhanced = '1';
    select.classList.add('tai-native-select');
    const shell = document.createElement('div');
    shell.className = 'tai-select';
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'tai-select__trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    const value = document.createElement('span');
    const arrow = document.createElement('span');
    arrow.className = 'tai-select__arrow';
    arrow.setAttribute('aria-hidden', 'true');
    arrow.textContent = '▾';
    trigger.append(value, arrow);
    const list = document.createElement('div');
    list.className = 'tai-select__options';
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    shell.append(trigger, list);
    select.after(shell);
    const close = () => {
        list.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        shell.classList.remove('is-open');
        shell.closest('details')?.classList.remove('has-open-select');
    };
    const open = () => {
        if (trigger.disabled) return;
        document.querySelectorAll('.tai-select.is-open').forEach(item => { if (item !== shell) item.querySelector('.tai-select__trigger').click(); });
        list.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        shell.classList.add('is-open');
        shell.closest('details')?.classList.add('has-open-select');
        list.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' });
    };
    const refresh = () => {
        list.replaceChildren();
        trigger.disabled = select.disabled;
        value.textContent = select.selectedOptions[0]?.textContent ?? 'Chọn';
        Array.from(select.options).forEach(option => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'tai-select__option';
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
            item.disabled = option.disabled;
            item.textContent = option.textContent;
            item.addEventListener('click', () => {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                refresh();
                close();
                trigger.focus();
            });
            list.append(item);
        });
    };
    trigger.addEventListener('click', () => list.hidden ? open() : close());
    trigger.addEventListener('keydown', event => {
        if (event.key === 'Escape') { close(); return; }
        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        if (list.hidden) open();
        const items = Array.from(list.querySelectorAll('.tai-select__option:not(:disabled)'));
        const current = items.indexOf(document.activeElement);
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
            : event.key === 'ArrowUp' ? Math.max(0, current < 0 ? items.length - 1 : current - 1)
                : Math.min(items.length - 1, current + 1);
        items[next]?.focus();
    });
    list.addEventListener('keydown', event => {
        if (event.key === 'Escape') { close(); trigger.focus(); }
        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const items = Array.from(list.querySelectorAll('.tai-select__option:not(:disabled)'));
        const current = items.indexOf(document.activeElement);
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
            : event.key === 'ArrowUp' ? Math.max(0, current - 1) : Math.min(items.length - 1, current + 1);
        items[next]?.focus();
    });
    document.addEventListener('click', event => { if (!shell.contains(event.target)) close(); });
    new MutationObserver(refresh).observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected'] });
    select.addEventListener('change', refresh);
    refresh();
}
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
function formatMessageTime(value, now = new Date()) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const formatter = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Ho_Chi_Minh', year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
    });
    const parts = input => Object.fromEntries(formatter.formatToParts(input).map(part => [part.type, part.value]));
    const sent = parts(date), today = parts(now);
    const time = sent.hour + ':' + sent.minute;
    return ['year', 'month', 'day'].every(key => sent[key] === today[key])
        ? time : time + ' ' + sent.day + '/' + sent.month + '/' + sent.year;
}
function messageTime(row, value) {
    if (!row || !value || !formatMessageTime(value)) return;
    let time = row.querySelector('[data-message-time]');
    if (!time) {
        time = document.createElement('time');
        time.dataset.messageTime = '';
        time.className = 'lesson-message-time';
        (row.querySelector('.lesson-message-content') ?? row).append(time);
    }
    time.dateTime = new Date(value).toISOString();
    time.textContent = formatMessageTime(value);
}
function chatContextKey(actor, lesson, question = null, attempt = null) {
    return 'tai-chat:' + actor + ':' + lesson + (question ? ':' + JSON.stringify([attempt, question]) : '');
}
function validateChatContext({ lessonId, questionId = null, attemptId = null }) {
    const valid = value => typeof value === 'string' && value.length > 0 && value.length <= 191;
    if (!valid(lessonId) || (questionId !== null && !valid(questionId)) || (attemptId !== null && !valid(attemptId))
        || ((questionId === null) !== (attemptId === null))) throw new Error('AI_CONTEXT_INVALID');
    return { lessonId, questionId, attemptId };
}
function chat(root) {
    const api = root.dataset.api;
    let lesson = root.dataset.lesson;
    let question = root.dataset.question || null, attempt = root.dataset.attempt || null;
    const form = root.querySelector('[data-chat-form]');
    const history = root.querySelector('[data-history]');
    const retry = root.querySelector('[data-retry]');
    const mode = root.querySelector('[data-mode]');
    const nextHint = root.querySelector('[data-next-hint]');
    let hintLevel = 0, maxHintLevel = 3;
    const updateHint = message => {
        hintLevel = message.metadata?.hint_level ?? 1;
        maxHintLevel = message.metadata?.max_hint_level ?? 3;
    };
    const quickPrompts = root.querySelectorAll('[data-quick-prompt]');
    const creditBadge = root.querySelector('[data-credit-balance]');
    const clearWelcome = () => root.querySelector('.lesson-chat-welcome')?.remove();
    const messageContainer = role => {
        const row = document.createElement('div');
        const template = root.querySelector('[data-chat-avatar="' + role + '"]');
        if (!template) return { row, body: row };
        row.className = 'lesson-message lesson-message--' + role;
        row.setAttribute('aria-label', role === 'tutor' ? 'Gia sư AI' : 'Bạn');
        row.append(template.content.cloneNode(true));
        const body = document.createElement('div');
        body.className = 'lesson-message-bubble';
        const content = document.createElement('div');
        content.className = 'lesson-message-content';
        content.append(body);
        row.append(content);
        return { row, body };
    };
    const learnerMessage = (text, sentAt) => {
        if (!root.querySelector('[data-chat-avatar="learner"]')) {
            const node = paragraph(history, 'Bạn: ' + text);
            messageTime(node, sentAt);
            return node;
        }
        const { row, body } = messageContainer('learner');
        paragraph(body, text);
        messageTime(row, sentAt);
        history.append(row);
        return row;
    };
    let followHistory = true;
    history.addEventListener('scroll', () => {
        followHistory = history.scrollHeight - history.scrollTop - history.clientHeight < 80;
    });
    new MutationObserver(() => {
        if (followHistory) history.scrollTop = history.scrollHeight;
    }).observe(history, { childList: true, subtree: true, characterData: true });
    let storageKey = chatContextKey(root.dataset.actor, lesson, question, attempt);
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
        quickPrompts.forEach(button => { button.disabled = state || pending !== null; });
        form.querySelector('button[type="submit"]').disabled = state || pending !== null;
        retry.disabled = state || ['reconciliation', 'blocked'].includes(pending?.recovery);
        retry.hidden = pending === null;
        retry.textContent = pending?.recovery === 'new_attempt' ? 'Thử lại bằng yêu cầu mới'
            : pending?.recovery === 'reconciliation' ? 'Đang chờ đối soát' : 'Thử lại cùng yêu cầu';
        if (nextHint) {
            nextHint.disabled = state || pending !== null || !conversation || hintLevel === 0 || hintLevel >= maxHintLevel;
            nextHint.textContent = hintLevel > 0 && hintLevel < maxHintLevel ? 'Xin gợi ý cấp ' + (hintLevel + 1) : 'Xin gợi ý tiếp theo';
        }
        mode.disabled = state || conversation !== null;
        root.querySelector('[data-reload]').disabled = state;
        root.querySelector('[data-export]').disabled = state || conversation === null;
        root.querySelector('[data-new-conversation]').disabled = state || conversation === null;
        root.querySelector('[data-delete]').disabled = state || conversation === null;
    };
    const resetConversation = () => {
        conversation = pending = null;
        hintLevel = 0;
        form.elements.message.value = '';
        history.replaceChildren();
        mode.disabled = false;
        save();
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
                if (message.status === 'completed') updateHint(message);
                learnerMessage(message.user_content, message.created_at);
                const { row, body: block } = messageContainer('tutor');
                history.append(row);
                paragraph(block, (row === block ? 'Gia sư: ' : '') + (message.content ?? message.status));
                sources(message, block);
                messageTime(row, message.completed_at);
                if (pending && message.request_id === pending.request_id) {
                    if (message.status === 'completed') {
                        if (form.elements.message.value === pending.message) form.elements.message.value = '';
                        pending = null;
                        if (creditBadge) creditBadge.textContent = message.credit_balance === null ? 'Credit không giới hạn' : 'Còn ' + message.credit_balance + ' credit';
                        status(root, 'Đã khôi phục kết quả hoàn tất. Credit đã dùng: ' + (message.metadata?.credit_units ?? 0));
                    } else {
                        pending.message_id = message.id;
                        pending.recovery = message.recovery;
                        status(root, recoveryNotice(message));
                    }
                }
            }
            save();
        } catch (error) { status(root, error.message); }
        finally { controls(false); }
    }
    async function send(advanceHint = false) {
        if (busy) return;
        controls(true);
        status(root, 'Đang xử lý… Không gửi lại bằng mã yêu cầu mới khi kết nối gián đoạn.');
        const { row, body: block } = messageContainer('tutor');
        const output = paragraph(block, '');
        try {
            if (!conversation) {
                const created = await json(api + '/conversations', 'POST', { lesson_id: lesson, teaching_mode: mode.value, question_id: question, attempt_id: attempt });
                conversation = created.id;
                save();
            }
            if (!pending) {
                pending = { message: form.elements.message.value, request_id: crypto.randomUUID(), idempotency_key: crypto.randomUUID(), sent_at: new Date().toISOString(), next_hint: advanceHint };
                save();
            }
            clearWelcome();
            followHistory = true;
            const learnerRow = learnerMessage(pending.message, pending.sent_at);
            history.append(row);
            const response = await fetch(api + '/conversations/' + conversation + '/messages', {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'text/event-stream', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ message: pending.message, request_id: pending.request_id, idempotency_key: pending.idempotency_key,
                    next_hint: pending.next_hint ?? false,
                    ...(pending.retry_of_message_id ? { retry_of_message_id: pending.retry_of_message_id, confirm_retry: true } : {}) }),
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
                        updateHint(data);
                        completed = true;
                        if (creditBadge && data.credit_balance !== undefined) {
                            creditBadge.textContent = data.credit_balance === null ? 'Credit không giới hạn' : 'Còn ' + data.credit_balance + ' credit';
                        }
                        output.textContent = data.content;
                        sources(data, block);
                        messageTime(learnerRow, data.created_at);
                        messageTime(row, data.completed_at);
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
        } finally {
            controls(false);
            if (pending) await reload();
        }
    }
    form.addEventListener('submit', event => { event.preventDefault(); if (!pending) send(); });
    nextHint?.addEventListener('click', () => {
        if (busy || pending || hintLevel === 0 || hintLevel >= maxHintLevel) return;
        if (!form.elements.message.value.trim()) form.elements.message.value = 'Hãy cho tôi gợi ý tiếp theo cho nội dung đang trao đổi.';
        save();
        send(true);
    });
    quickPrompts.forEach(button => button.addEventListener('click', () => {
        if (busy || pending) return;
        form.elements.message.value = button.dataset.quickPrompt;
        save();
        form.requestSubmit();
    }));
    retry.addEventListener('click', async () => {
        if (busy || !pending) return;
        // Refresh durable state before deciding whether a NEW paid attempt is safe.
        await reload();
        if (!pending || busy || ['reconciliation', 'blocked'].includes(pending.recovery)) return;
        if (pending.recovery === 'new_attempt') {
            if (!confirm('Yêu cầu trước đã thất bại và không còn giữ credit. Tạo lần thử mới trong hội thoại này? Lần thử mới có thể sử dụng credit.')) return;
            pending = newRetryAttempt(pending);
            save();
        }
        await send();
    });
    root.querySelector('[data-reload]').addEventListener('click', reload);
    root.querySelector('[data-export]').addEventListener('click', () => {
        if (!busy && conversation) window.open(api + '/conversations/' + conversation + '/export', '_blank', 'noopener');
    });
    root.querySelector('[data-new-conversation]').addEventListener('click', () => {
        if (busy || !conversation || !confirm('Bắt đầu hội thoại mới? Hội thoại hiện tại vẫn được giữ để tải lại hoặc đối soát.')) return;
        resetConversation();
        controls(false);
        status(root, 'Đã bắt đầu hội thoại mới. Hội thoại cũ vẫn được lưu an toàn.');
    });
    root.querySelector('[data-delete]').addEventListener('click', async () => {
        if (busy || !conversation || !confirm('Xóa vĩnh viễn nội dung hội thoại này? Lịch sử credit/usage vẫn được giữ.')) return;
        controls(true);
        try {
            await json(api + '/conversations/' + conversation, 'DELETE');
            resetConversation();
            status(root, 'Đã xóa nội dung hội thoại. Không thể khôi phục từ ứng dụng.');
        } catch (error) {
            status(root, error.message === 'AI_CONVERSATION_BUSY'
                ? 'Chưa thể xóa vì yêu cầu AI đang cần đối soát. Hãy dùng “Hội thoại mới”; dữ liệu và credit của yêu cầu cũ vẫn được giữ an toàn.'
                : error.message);
        }
        finally { controls(false); }
    });
    const refreshTimes = () => history.querySelectorAll('[data-message-time]').forEach(time => {
        const label = formatMessageTime(time.dateTime);
        if (time.textContent !== label) time.textContent = label;
    });
    const timeRefresh = setInterval(() => {
        if (!root.isConnected) { clearInterval(timeRefresh); return; }
        refreshTimes();
    }, 60000);
    document.addEventListener('visibilitychange', refreshTimes);
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
        async setContext(context) {
            const { lessonId, questionId, attemptId } = validateChatContext(context);
            await ready;
            if (busy || pending) throw new Error('AI_CONVERSATION_BUSY');
            controls(true);
            try {
                await json(api + '/context?' + new URLSearchParams({ lesson_id: lessonId,
                    ...(questionId ? { question_id: questionId, attempt_id: attemptId } : {}) }));
                save();
                lesson = lessonId;
                question = questionId;
                attempt = attemptId;
                root.dataset.lesson = lesson;
                storageKey = chatContextKey(root.dataset.actor, lesson, question, attempt);
                conversation = pending = null;
                hintLevel = 0;
                form.elements.message.value = '';
                mode.value = 'hints_first';
                try {
                    const saved = JSON.parse(sessionStorage.getItem(storageKey) ?? '{}');
                    conversation = saved.conversation ?? null;
                    pending = saved.pending ?? null;
                    form.elements.message.value = saved.draft ?? pending?.message ?? '';
                } catch {}
                const label = root.querySelector('[data-lesson-label]');
                if (label) label.textContent = question ? 'Câu hỏi ' + question : 'Bài học ' + lesson;
                history.replaceChildren();
                status(root, question ? 'Đang hỗ trợ câu hỏi đã chọn trong lần làm bài này.' : 'Đã chuyển ngữ cảnh bài học.');
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
function courseSync(root) {
    const section = root.querySelector('[data-course-sync]');
    if (!section) return;
    const api = root.dataset.api;
    const course = section.querySelector('[data-sync-course]');
    const list = section.querySelector('[data-sync-lessons]');
    const submit = section.querySelector('[data-sync-submit]');
    const enqueue = section.querySelector('[data-sync-enqueue]');
    const summary = section.querySelector('[data-sync-summary]');
    const result = section.querySelector('[data-sync-result]');
    let rows = [], previewCourse = null, busy = false;
    const chosen = () => rows.filter(row => row.checkbox.checked);
    const update = () => {
        const selected = chosen();
        summary.textContent = selected.length + ' bài · ' + selected.reduce((n, r) => n + r.chunks, 0)
            + ' chunk dự kiến. Bài không đổi được tái sử dụng; số chunk không phải giá tiền/token.';
        submit.disabled = busy || !selected.length || selected.length > 50 || course.value !== previewCourse;
    };
    const lock = value => {
        busy = value;
        section.querySelectorAll('button, select, input').forEach(element => { element.disabled = value; });
        rows.forEach(row => { row.checkbox.disabled = value || row.status === 'empty'; });
        update();
    };
    section.querySelector('[data-sync-courses]').addEventListener('click', async () => {
        lock(true);
        try {
            const courses = await json(api + '/knowledge/sync/courses');
            course.replaceChildren(new Option('Chọn khóa học', ''));
            courses.forEach(item => course.append(new Option(item.title, item.id)));
            rows = []; previewCourse = null; list.replaceChildren();
        } catch (error) { status(root, error.message); }
        finally { lock(false); }
    });
    course.addEventListener('change', () => {
        rows = []; previewCourse = null; list.replaceChildren(); result.replaceChildren(); update();
    });
    section.querySelector('[data-sync-preview]').addEventListener('click', async () => {
        if (!course.value || busy) return;
        lock(true);
        rows = []; previewCourse = null; list.replaceChildren(); result.replaceChildren();
        try {
            const data = await json(api + '/knowledge/sync/preview?course_id=' + encodeURIComponent(course.value));
            previewCourse = course.value;
            for (const item of data) {
                const wrapper = document.createElement('div');
                const label = document.createElement('label');
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = ['new', 'changed'].includes(item.status) && rows.filter(r => r.checkbox.checked).length < 50;
                checkbox.addEventListener('change', update);
                label.append(checkbox, document.createTextNode(item.title + ' · ' + item.status + ' · ' + item.chunks + ' chunk'));
                wrapper.append(label);
                paragraph(wrapper, item.warning);
                const details = document.createElement('details'), heading = document.createElement('summary');
                heading.textContent = 'Xem nội dung sẽ đồng bộ';
                const text = document.createElement('pre');
                text.className = 'tai-preview'; text.textContent = item.content;
                details.append(heading, text); wrapper.append(details); list.append(wrapper);
                rows.push({ ...item, checkbox });
            }
            if (!data.length) paragraph(list, 'Không có bài học đang hiển thị trong khóa học.');
        } catch (error) { status(root, error.message); }
        finally { lock(false); }
    });
    submit.addEventListener('click', async () => {
        const selected = chosen();
        if (busy || !selected.length || selected.length > 50 || course.value !== previewCourse) return;
        const paid = enqueue.checked;
        if (!confirm(paid
            ? 'Tạo bản nháp và đưa các version vào queue tạo vector? Thao tác này có thể phát sinh chi phí OpenAI và credit.'
            : 'Tạo/cập nhật bản nháp cho các bài đã chọn? Chưa gọi AI và chưa publish.')) return;
        lock(true);
        try {
            const response = await json(api + '/knowledge/sync', 'POST', {
                course_id: previewCourse, enqueue: paid,
                lessons: selected.map(row => ({ lesson_id: row.lesson_id, fingerprint: row.fingerprint })),
            });
            result.replaceChildren();
            for (const item of response.results) {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = 'Bài ' + item.lesson_id + ' · ' + item.status + ' · chọn version';
                button.addEventListener('click', () => {
                    root.querySelector('[data-version]').value = item.version_id;
                    const form = root.querySelector('[data-list-form]');
                    form.elements.lesson_id.value = item.lesson_id;
                    form.requestSubmit();
                });
                result.append(button);
            }
            status(root, response.queued ? 'Đã đồng bộ và đưa vào queue. Kiểm tra ready trước khi publish.' : 'Đã đồng bộ bản nháp, không gọi AI. Chọn version để xử lý tiếp.');
        } catch (error) { status(root, error.message + ' — Nếu nội dung đã đổi, hãy xem trước lại.'); }
        finally { lock(false); }
    });
}
function knowledge(root) {
    courseSync(root);
    root.querySelectorAll('select').forEach(enhanceSelect);
    const pageStatus = root.querySelector('[data-status]');
    pageStatus.querySelector('[data-status-close]').addEventListener('click', () => {
        clearTimeout(statusTimers.get(pageStatus));
        pageStatus.classList.remove('is-toast');
    });
    const api = root.dataset.api;
    const form = root.querySelector('[data-document-form]');
    const version = root.querySelector('[data-version]');
    let selected = null;
    const versionForm = root.querySelector('[data-version-form]');
    const listForm = root.querySelector('[data-list-form]');
    const withdraw = root.querySelector('[data-withdraw]');
    const processButton = root.querySelector('[data-process]');
    const publishButton = root.querySelector('[data-publish]');
    const actionStatus = root.querySelector('[data-action-status]');
    const embeddingReady = root.dataset.embeddingReady === '1';
    const actionMessage = (text, kind = '') => {
        actionStatus.textContent = text;
        actionStatus.classList.toggle('is-error', kind === 'error');
        actionStatus.classList.toggle('is-success', kind === 'success');
        status(root, text, kind);
    };
    processButton.disabled = !embeddingReady;
    publishButton.disabled = true;
    version.addEventListener('input', () => {
        publishButton.disabled = true;
        actionMessage('Kiểm tra trạng thái version trước khi publish.');
    });
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
    const recordButton = (parent, title, id, badge, work, options = {}) => {
        const row = document.createElement('div');
        row.className = 'tai-knowledge__record-row';
        const card = document.createElement('div');
        card.className = 'tai-knowledge__record';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'tai-knowledge__record-select';
        const content = document.createElement('span');
        const heading = document.createElement('strong');
        heading.textContent = title;
        content.append(heading);
        button.append(content);
        const badges = document.createElement('span');
        badges.className = 'tai-knowledge__record-badges';
        if (options.format) {
            const format = document.createElement('em');
            format.className = 'is-format';
            format.textContent = knowledgeLabel(options.format);
            badges.append(format);
        }
        if (options.latest) {
            const latest = document.createElement('em');
            latest.className = 'is-latest';
            latest.textContent = 'Mới nhất';
            badges.append(latest);
        }
        if (badge) {
            const state = document.createElement('em');
            state.textContent = knowledgeLabel(badge);
            state.dataset.state = badge;
            badges.append(state);
        }
        const selectedMark = document.createElement('em');
        selectedMark.className = 'is-selected-mark';
        selectedMark.textContent = 'Đang chọn';
        badges.append(selectedMark);
        button.append(badges);
        const arrow = document.createElement('b');
        arrow.className = 'tai-knowledge__record-arrow';
        arrow.textContent = '›';
        button.append(arrow);
        card.addEventListener('click', async event => {
            if (event.target.closest('.tai-knowledge__copy')) return;
            parent.querySelectorAll('.tai-knowledge__record').forEach(item => item.classList.remove('is-selected'));
            card.classList.add('is-selected');
            button.disabled = true;
            try { await work(); } catch (error) { status(root, knowledgeError(error.code ?? error.message), 'error'); }
            finally { button.disabled = false; }
        });
        const copy = document.createElement('button');
        copy.type = 'button';
        copy.className = 'tai-knowledge__copy';
        const copyIcon = document.createElement('span');
        copyIcon.setAttribute('aria-hidden', 'true');
        copyIcon.textContent = '⧉';
        copy.append(copyIcon, document.createTextNode('Sao chép'));
        copy.title = 'Sao chép ' + id;
        copy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(String(id));
                status(root, 'Đã sao chép ID.', 'success');
            } catch { status(root, 'Không thể sao chép tự động. ID: ' + id, 'error'); }
        });
        const meta = document.createElement('div');
        meta.className = 'tai-knowledge__record-meta';
        const detail = document.createElement('small');
        detail.textContent = 'ID ' + shortId(id);
        detail.title = String(id);
        meta.append(detail, copy);
        card.append(button, meta);
        row.append(card);
        parent.append(row);
        return row;
    };
    async function loadVersions(id) {
        const data = await json(api + '/knowledge/documents/' + id + '/versions');
        selected = id;
        root.querySelector('[data-selected]').textContent = 'Tài liệu đang chọn: ' + data.document.title;
        versionForm.querySelector('[data-version-submit]').disabled = true;
        withdraw.disabled = !data.document.published_version_id;
        root.querySelector('[data-withdraw-row]').hidden = !data.document.published_version_id;
        root.querySelector('[data-withdraw-hint]').textContent = data.document.published_version_id
            ? 'Thu hồi sẽ gỡ tài liệu khỏi truy xuất nhưng vẫn giữ lịch sử version.'
            : 'Tài liệu này chưa có version đang publish.';
        const container = root.querySelector('[data-versions]');
        container.replaceChildren();
        data.versions.forEach((item, index) => {
            const row = recordButton(container, knowledgeDate(item.created_at), item.id, item.status, async () => {
                version.value = item.id;
                publishButton.disabled = true;
                const preview = await json(api + '/knowledge/document-versions/' + item.id + '/content');
                root.querySelector('[data-preview]').textContent = preview.content;
                root.querySelector('[data-preview-shell]').hidden = false;
                versionForm.elements.content.value = preview.content;
                versionForm.elements.format.value = preview.format;
                versionForm.elements.format.dispatchEvent(new Event('change', { bubbles: true }));
                versionForm.querySelector('[data-version-submit]').disabled = false;
                versionForm.classList.add('is-highlighted');
                versionForm.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
                setTimeout(() => versionForm.classList.remove('is-highlighted'), 1800);
                status(root, 'Đã nạp version vào biểu mẫu Tạo version mới bên dưới.', 'success');
            }, { latest: index === 0, format: item.format });
            if (index >= 5) row.hidden = true;
        });
        if (data.versions.length > 5) {
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'tai-knowledge__version-toggle';
            toggle.textContent = 'Xem thêm ' + (data.versions.length - 5) + ' version';
            toggle.addEventListener('click', () => {
                const expanded = toggle.dataset.expanded === '1';
                container.querySelectorAll('.tai-knowledge__record-row').forEach((row, index) => { if (index >= 5) row.hidden = expanded; });
                toggle.dataset.expanded = expanded ? '0' : '1';
                toggle.textContent = expanded ? 'Xem thêm ' + (data.versions.length - 5) + ' version' : 'Thu gọn danh sách';
            });
            container.append(toggle);
        }
    }
    listForm.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            const docs = await json(api + '/knowledge/documents?lesson_id=' + encodeURIComponent(listForm.elements.lesson_id.value));
            const container = root.querySelector('[data-documents]');
            container.replaceChildren();
            for (const doc of docs) recordButton(container, doc.title, doc.id, doc.visibility, () => loadVersions(doc.id));
            if (!docs.length) paragraph(container, 'Chưa có tài liệu.');
        } catch (error) { status(root, error.message); }
    });
    versionForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!selected) return;
        const button = versionForm.querySelector('[data-version-submit]');
        button.disabled = true;
        try {
            const created = await json(api + '/knowledge/documents/' + selected + '/versions', 'POST', Object.fromEntries(new FormData(versionForm)));
            version.value = created.id;
            await loadVersions(selected);
            status(root, 'Đã tạo version mới. Cần xử lý vector trước khi publish.', 'success');
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
        const button = form.querySelector('[data-document-submit]');
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
            if (!/^[0-9a-f-]{36}$/i.test(version.value.trim())) { actionMessage('Cần ID version hợp lệ.', 'error'); return; }
            button.disabled = true;
            try {
                const result = await json(api + '/knowledge/document-versions/' + encodeURIComponent(version.value.trim()) + action, method);
                if (selector === '[data-process]') {
                    publishButton.disabled = true;
                    actionMessage('Đã đưa version vào hàng đợi tạo vector. Hãy kiểm tra lại trạng thái sau khi worker xử lý.', 'success');
                } else if (selector === '[data-check]') {
                    const ready = ['ready', 'published'].includes(result.status) && result.processing_status !== 'failed';
                    publishButton.disabled = !ready;
                    if (result.processing_status === 'failed') actionMessage('Tạo vector thất bại: ' + knowledgeError(result.error_code ?? 'AI_REQUEST_FAILED'), 'error');
                    else if (ready) actionMessage('Version đã sẵn sàng' + (result.status === 'published' ? ' và đang được publish.' : ' để publish.'), 'success');
                    else actionMessage('Trạng thái: ' + result.status + ' · Xử lý vector: ' + result.processing_status + '.');
                } else {
                    publishButton.disabled = false;
                    actionMessage('Đã publish version thành công.', 'success');
                }
                if (selected) await loadVersions(selected);
            } catch (error) {
                if (selector === '[data-publish]') publishButton.disabled = true;
                actionMessage(knowledgeError(error.code ?? error.message), 'error');
            } finally {
                if (selector === '[data-process]') button.disabled = !embeddingReady;
                else if (selector !== '[data-publish]') button.disabled = false;
            }
        });
    }
}
function copyButtons() {
    document.querySelectorAll('[data-copy-text]').forEach(button => {
        button.addEventListener('click', async () => {
            const label = button.querySelector('span');
            const original = label?.textContent ?? 'Sao chép';
            try {
                await navigator.clipboard.writeText(button.dataset.copyText ?? '');
                if (label) label.textContent = 'Đã chép';
                button.classList.add('is-copied');
                setTimeout(() => {
                    if (label) label.textContent = original;
                    button.classList.remove('is-copied');
                }, 1600);
            } catch {
                if (label) label.textContent = 'Không thể chép';
            }
        });
    });
}
function boot() {
    theme();
    copyButtons();
    document.querySelectorAll('.tai-credits select').forEach(enhanceSelect);
    document.querySelectorAll('[data-tai-chat]').forEach(root => {
        const client = chat(root);
        const shell = root.closest('[data-tai-widget]');
        if (shell) window.AITutor = widget(shell, client);
        const selectQuestion = async context => {
            try {
                await client.setContext(context);
                if (shell) window.AITutor.open();
                window.dispatchEvent(new CustomEvent('ai-tutor-open'));
                root.querySelector('[name="message"]').focus();
            } catch (error) {
                status(root, error.message === 'AI_CONVERSATION_BUSY'
                    ? 'Hãy xử lý yêu cầu đang chờ trước khi chuyển câu hỏi.'
                    : 'Không thể mở câu hỏi này. Kiểm tra lần làm bài và quyền truy cập.');
            }
        };
        document.addEventListener('ai-tutor-question', event => selectQuestion(event.detail));
        root.querySelector('[data-lesson-context]')?.addEventListener('click', () => selectQuestion({ lessonId: lesson }));
        window.addEventListener('message', event => {
            const frame = document.querySelector('[data-activity-frame]');
            if (event.origin !== window.location.origin || !frame || event.source !== frame.contentWindow
                || event.data?.type !== 'ai-tutor-question'
                || String(event.data.activityId) !== frame.dataset.activityId) return;
            selectQuestion(event.data.context);
        });
    });
    document.querySelectorAll('[data-tai-knowledge]').forEach(knowledge);
}
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
}
