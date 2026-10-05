export const writingTargets = { cefr: ['A1', 'A2', 'B1', 'B2'], toeic: ['450+', '650+', '800+'], ielts: ['Foundation', '5.5', '6.5', '7.0+'] };
const tasks = { cefr: ['cefr_writing'], toeic: ['cefr_writing'], ielts: ['ielts_task_1', 'ielts_task_2'] };
const taskNames = { cefr_writing: 'CEFR Writing', ielts_task_1: 'IELTS Task 1', ielts_task_2: 'IELTS Task 2' };
const criterionNames = { grammar: 'Ngữ pháp', vocabulary: 'Từ vựng', coherence: 'Mạch lạc', task_response: 'Đáp ứng đề bài', style: 'Phong cách' };
const errorText = code => ({
    AI_WRITING_REVISION_CONFLICT: 'Bản nháp đã thay đổi ở nơi khác. Bài đang soạn được giữ lại; hãy so sánh hai phiên bản.',
    WRITING_CONNECTION: 'Mất kết nối. Nội dung và mã yêu cầu được giữ lại; kết nối lại rồi cập nhật trạng thái.',
    WRITING_STORAGE: 'Không lưu được trạng thái trong trình duyệt. Hãy sao chép bài viết trước khi đóng tab; chưa gửi đánh giá.',
    WRITING_TOO_LONG: 'Bài viết vượt giới hạn 20.000 byte. Hãy rút ngắn nội dung trước khi lưu.',
    WRITING_TOPIC_TOO_LONG: 'Đề bài vượt giới hạn 5.000 byte. Hãy rút ngắn đề bài.',
    WRITING_PENDING: 'Đang có yêu cầu chưa xác định kết quả. Hãy cập nhật hoặc tiếp tục đúng yêu cầu đó.',
    AI_WRITING_QUEUE_INVALID: 'Chức năng đánh giá chưa sẵn sàng. Hãy liên hệ quản trị viên.',
    AI_WRITING_SCHEMA_REQUIRED: 'Writing chưa được cài đặt đầy đủ. Hãy liên hệ quản trị viên.',
    AI_RUBRIC_NOT_FOUND: 'Dạng bài này chưa có tiêu chí đánh giá. Hãy liên hệ quản trị viên.',
    AI_PROVIDER_NOT_CONFIGURED: 'Dịch vụ AI chưa được cấu hình. Bạn vẫn có thể lưu bản nháp.',
    AI_CREDIT_INSUFFICIENT: 'Không đủ credit để chấm bài. Liên hệ quản trị viên để bổ sung credit; bản nháp của bạn vẫn được giữ.',
    AI_DAILY_LIMIT_REACHED: 'Đã đạt giới hạn credit trong ngày.',
    AI_WRITING_ALREADY_SUBMITTED: 'Phiên bản này đã được gửi. Hãy xem lịch sử đánh giá.',
    AI_WRITING_BUSY: 'Bài viết còn lượt đánh giá đang xử lý. Hãy cập nhật kết quả trước khi gửi tiếp.',
    AI_WRITING_FORBIDDEN: 'Bạn không có quyền truy cập bài viết này.',
    AI_CONTEXT_FORBIDDEN: 'Quyền truy cập bài học đã thay đổi.',
    AI_WRITING_ASSESSMENT_FORBIDDEN: 'Bài học hiện không cho phép dùng AI đánh giá.',
    LICENSE_MODULE_NOT_ALLOWED: 'Tài khoản hiện không được dùng Writing.',
    WRITING_AUTH: 'Phiên đăng nhập hoặc quyền truy cập đã thay đổi. Sao chép phần chưa lưu và đăng nhập lại.',
    AI_WRITING_RETRY_BLOCKED: 'Chưa thể tạo lần thử mới. Hãy cập nhật kết quả hoặc liên hệ quản trị viên.',
})[code] ?? 'Chưa hoàn tất thao tác. Nội dung đang soạn được giữ lại; hãy cập nhật trạng thái hoặc liên hệ quản trị viên.';

export function wordCount(text) {
    return (text.match(/[\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*/gu) ?? []).length;
}
export function applyWritingIssues(original, issues, selected) {
    const patches = [...selected].map(i => issues[i]);
    for (const issue of patches) {
        if (!issue?.applicable || !Number.isInteger(issue.start_utf16) || !Number.isInteger(issue.end_utf16)
            || issue.start_utf16 < 0 || issue.end_utf16 <= issue.start_utf16
            || original.slice(issue.start_utf16, issue.end_utf16) !== issue.original) throw new Error('WRITING_SPAN_INVALID');
    }
    patches.sort((a, b) => a.start_utf16 - b.start_utf16);
    for (let i = 1; i < patches.length; i++) if (patches[i].start_utf16 < patches[i - 1].end_utf16) throw new Error('WRITING_SPAN_OVERLAP');
    return patches.reverse().reduce((text, issue) => text.slice(0, issue.start_utf16) + issue.replacement + text.slice(issue.end_utf16), original);
}

/** Durable client state; injected IO keeps recovery/autosave independently testable. */
export class WritingSession {
    constructor({ actor, draft, api, storage, request, uuid, changed = () => {} }) {
        Object.assign(this, { actor, draft, api, storage, request, uuid, changed });
        this.key = `tai-writing:${actor}:${draft}`;
        this.content = ''; this.base = ''; this.revision = 0; this.pending = null; this.result = null;
        this.lastId = null; this.conflict = null; this.saving = null; this.busy = false; this.fatal = false; this.applied = new Set(); this.savedAt = null;
    }
    get dirty() { return this.content !== this.base; }
    get canSubmit() { return !this.fatal && !this.busy && !this.pending && !this.conflict && this.revision > 0
        && Array.from(this.content.trim()).length >= 10 && new TextEncoder().encode(this.content).length <= 20000
        && (this.dirty || this.result?.revision !== this.revision); }
    persist(required = false) {
        try { this.storage.setItem(this.key, JSON.stringify({ content: this.content, base: this.base, revision: this.revision,
            pending: this.pending, lastId: this.lastId })); return true; }
        catch { if (required) throw new Error('WRITING_STORAGE'); return false; }
    }
    notify() { this.changed(this); }
    async load() {
        const server = await this.request(`${this.api}/drafts/${this.draft}`);
        let cached;
        try { cached = JSON.parse(this.storage.getItem(this.key) ?? 'null'); } catch {}
        this.base = server.content; this.content = server.content; this.revision = server.revision; this.metadata = server;
        this.savedAt = server.updated_at ?? null;
        if (cached && typeof cached.content === 'string' && typeof cached.base === 'string') {
            this.pending = cached.pending ?? null; this.lastId = cached.lastId ?? null;
            if (cached.content !== cached.base && cached.content !== server.content) {
                this.content = cached.content;
                if (cached.base !== server.content) this.conflict = server;
            }
        }
        this.persist(); this.notify();
        try { await this.refresh(); } catch (error) { this.loadError = error; }
        return server;
    }
    edit(content) { this.content = content; this.persist(); this.notify(); }
    async save() {
        if (this.saving) { await this.saving; return this.save(); }
        if (!this.dirty || this.conflict || this.fatal) return;
        const sent = this.content, revision = this.revision, base = this.base;
        if (new TextEncoder().encode(sent).length > 20000) throw new Error('WRITING_TOO_LONG');
        this.saving = (async () => {
            try {
                const saved = await this.request(`${this.api}/drafts/${this.draft}`, 'PATCH', { revision, content: sent });
                this.base = saved.content; this.revision = saved.revision;
                this.savedAt = saved.updated_at ?? null;
            } catch (error) {
                if (error.code === 'AI_WRITING_REVISION_CONFLICT' || !error.status) {
                    const server = await this.request(`${this.api}/drafts/${this.draft}`);
                    // Lost PATCH response: recognize that exact content was durably saved.
                    if (server.content === sent || (server.content === base && server.revision === revision)) {
                        this.base = server.content; this.revision = server.revision;
                        this.savedAt = server.updated_at ?? null;
                        if (server.content !== sent) throw error;
                    } else { this.conflict = server; throw Object.assign(new Error('AI_WRITING_REVISION_CONFLICT'), { code: 'AI_WRITING_REVISION_CONFLICT' }); }
                } else throw error;
            } finally { this.persist(); }
        })();
        this.notify();
        try { await this.saving; } finally { this.saving = null; this.notify(); }
    }
    async resolveConflict(keepLocal) {
        const server = await this.request(`${this.api}/drafts/${this.draft}`);
        this.base = server.content; this.revision = server.revision;
        this.savedAt = server.updated_at ?? null;
        if (!keepLocal) this.content = server.content;
        this.conflict = null; this.persist(); this.notify();
        if (keepLocal) await this.save();
    }
    async submit() {
        if (!this.canSubmit) throw new Error('WRITING_PENDING');
        this.busy = true; this.notify();
        try {
            await this.save();
            if (this.dirty || this.conflict) throw new Error('AI_WRITING_REVISION_CONFLICT');
            this.pending = { kind: 'submit', revision: this.revision, request_id: this.uuid(), idempotency_key: this.uuid() };
            this.persist(true);
            await this.sendPending();
        } finally { this.busy = false; this.notify(); }
    }
    async sendPending() {
        if (!this.pending) return;
        if (this.pending.kind === 'durable') throw new Error('WRITING_PENDING');
        this.persist(true);
        const p = this.pending;
        const url = p.kind === 'retry' ? `${this.api}/submissions/${p.source_id}/retry` : `${this.api}/drafts/${this.draft}/submit`;
        const body = p.kind === 'retry'
            ? { request_id: p.request_id, idempotency_key: p.idempotency_key, confirm_retry: true }
            : { revision: p.revision, request_id: p.request_id, idempotency_key: p.idempotency_key, confirm_cost: true };
        try { this.accept(await this.request(url, 'POST', body)); }
        catch (error) {
            if (['AI_WRITING_REVISION_CONFLICT', 'AI_WRITING_ALREADY_SUBMITTED', 'AI_WRITING_RETRY_BLOCKED'].includes(error.code)) {
                const lookup = await this.request(`${this.api}/drafts/${this.draft}/submissions?request_id=${encodeURIComponent(p.request_id)}`);
                if (lookup.data[0]) this.accept(await this.request(`${this.api}/submissions/${lookup.data[0].id}`));
                else {
                    // A definitive rejection plus an owned read confirms no attempt was created.
                    this.pending = null;
                    if (error.code === 'AI_WRITING_REVISION_CONFLICT') this.conflict = await this.request(`${this.api}/drafts/${this.draft}`);
                    else await this.refresh();
                }
            }
            this.persist(); this.notify(); throw error;
        }
    }
    accept(result) {
        if (result.id !== this.result?.id) this.applied.clear();
        this.result = result; this.lastId = result.id;
        if (this.pending && result.request_id === this.pending.request_id && ['completed', 'failed'].includes(result.status)) this.pending = null;
        this.persist(); this.notify();
    }
    async refresh() {
        if (this.pending) {
            if (this.result?.request_id === this.pending.request_id) this.accept(await this.request(`${this.api}/submissions/${this.result.id}`));
            else {
                const list = await this.request(`${this.api}/drafts/${this.draft}/submissions?request_id=${encodeURIComponent(this.pending.request_id)}`);
                if (list.data[0]) this.accept(await this.request(`${this.api}/submissions/${list.data[0].id}`));
            }
        } else if (this.lastId) {
            this.accept(await this.request(`${this.api}/submissions/${this.lastId}`));
        } else {
            const list = await this.request(`${this.api}/drafts/${this.draft}/submissions`);
            const active = list.data.find(item => ['queued', 'processing', 'reconciliation_required'].includes(item.status)) ?? list.data[0];
            if (active) {
                const result = await this.request(`${this.api}/submissions/${active.id}`);
                if (['queued', 'processing', 'reconciliation_required'].includes(result.status)) {
                    // Rediscovered a durable request; polling is enough, no POST replay needed.
                    this.pending = { kind: 'durable', request_id: result.request_id, idempotency_key: result.idempotency_key, revision: result.revision };
                }
                this.accept(result);
            }
        }
    }
    async retry(confirmed) {
        if (!confirmed || this.result?.recovery !== 'new_attempt' || this.busy || this.pending) throw new Error('AI_WRITING_RETRY_BLOCKED');
        this.busy = true; this.notify();
        try {
            this.pending = { kind: 'retry', source_id: this.result.id, revision: this.result.revision,
                request_id: this.uuid(), idempotency_key: this.uuid() };
            this.persist(true); await this.sendPending();
        } finally { this.busy = false; this.notify(); }
    }
    apply(index) {
        const assessment = this.result?.result;
        if (!assessment || this.content !== applyWritingIssues(this.result.original, assessment.issues, this.applied)) throw new Error('WRITING_SPAN_INVALID');
        const next = new Set([...this.applied, index]);
        const content = applyWritingIssues(this.result.original, assessment.issues, next);
        this.applied = next; this.edit(content);
    }
}

function node(tag, text, className) {
    const el = document.createElement(tag); if (text !== undefined) el.textContent = text;
    if (className) el.className = className; return el;
}
async function request(url, method = 'GET', body) {
    let response;
    try { response = await fetch(url, { method, credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        body: body === undefined ? undefined : JSON.stringify(body) }); }
    catch { throw new Error('WRITING_CONNECTION'); }
    let data; try { data = await response.json(); } catch { throw Object.assign(new Error('WRITING_CONNECTION'), { status: response.status }); }
    if (!response.ok) {
        const code = [401, 403, 419].includes(response.status) ? (data.error?.code ?? 'WRITING_AUTH') : (data.error?.code ?? 'WRITING_REQUEST');
        throw Object.assign(new Error(code), { code, status: response.status });
    }
    return data;
}
function highlight(target, original, issues) {
    target.replaceChildren(); let end = 0;
    const spans = issues.filter(issue => issue.applicable).sort((a, b) => a.start_utf16 - b.start_utf16);
    for (const issue of spans) {
        if (issue.start_utf16 < end || original.slice(issue.start_utf16, issue.end_utf16) !== issue.original) continue;
        target.append(document.createTextNode(original.slice(end, issue.start_utf16)), node('mark', issue.original)); end = issue.end_utf16;
    }
    target.append(document.createTextNode(original.slice(end)));
}

// Browser-generated empty line blocks contain a placeholder BR. Count the
// block boundary once, rather than using layout-dependent innerText spacing.
function editorTextMap(element) {
    const blocks = new Set(['DIV', 'P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'LI', 'UL', 'OL', 'BLOCKQUOTE']);
    let text = ''; const runs = [];
    function read(parent) {
        const children = [...parent.childNodes].filter(child => child.nodeType === 1 || child.nodeType === 3);
        if (children.length === 1 && children[0].nodeName === 'BR') return;
        children.forEach((child, index) => {
            if (index && (blocks.has(child.nodeName) || blocks.has(children[index - 1].nodeName))) text += '\n';
            if (child.nodeType === 3) {
                runs.push({ node: child, start: text.length, end: text.length + child.nodeValue.length });
                text += child.nodeValue;
            } else if (child.nodeName === 'BR') text += '\n';
            else read(child);
        });
    }
    read(element);
    return { text, runs };
}
function editorPlainText(element) { return editorTextMap(element).text; }

export async function startWriting(root) {
    const q = name => root.querySelector(`[data-writing-${name}]`);
    const icon = name => root.querySelector(`template[data-writing-icon="${name}"]`).content.firstElementChild.cloneNode(true);
    const api = root.dataset.api, page = root.dataset.page;
    let session, saveTimer, pollTimer, historyPage = 1, historyNext = null, refreshing = false, issueFilter = 'all', displayedResultId = null;
    let feedbackExpanded = false, feedbackObserver, improvementsObserver, improvementsExpanded = false;
    let issuePage = 0;
    let historySelection = null;
    q('return-draft').addEventListener('click', () => { historySelection = null; render(session); q('editor').focus(); });
    const editorHighlights = [];
    const highlightNames = ['tai-writing-grammar', 'tai-writing-suggestion'];
    const errorTooltip = node('div', undefined, 'tai-writing-error-tooltip');
    errorTooltip.id = 'tai-writing-error-tooltip'; errorTooltip.role = 'tooltip'; errorTooltip.hidden = true;
    root.append(errorTooltip);
    function hideErrorTooltip() { errorTooltip.hidden = true; q('editor').removeAttribute('aria-describedby'); }
    function updateEditorHighlights(s) {
        hideErrorTooltip(); editorHighlights.length = 0;
        if (!globalThis.CSS?.highlights || !globalThis.Highlight) return;
        for (const name of highlightNames) CSS.highlights.delete(name);
        const selected = historySelection ?? s.result;
        const assessment = selected?.result, original = selected?.original;
        const mapped = editorTextMap(q('editor'));
        if (!assessment || typeof original !== 'string' || mapped.text !== original) return;
        const grammar = [], suggestions = [];
        for (const issue of assessment.issues) {
            const start = issue.start_utf16, end = issue.end_utf16;
            if (!issue.applicable || !Number.isInteger(start) || !Number.isInteger(end) || start < 0 || end <= start
                || original.slice(start, end) !== issue.original) continue;
            const first = mapped.runs.find(run => run.start <= start && start < run.end);
            const last = mapped.runs.find(run => run.start < end && end <= run.end);
            if (!first || !last) continue;
            const range = document.createRange();
            range.setStart(first.node, start - first.start); range.setEnd(last.node, end - last.start);
            editorHighlights.push({ range, issue });
            (issue.category === 'grammar' ? grammar : suggestions).push(range);
        }
        CSS.highlights.set(highlightNames[0], new Highlight(...grammar));
        CSS.highlights.set(highlightNames[1], new Highlight(...suggestions));
    }
    function showErrorTooltip(entry, rect) {
        errorTooltip.replaceChildren(node('strong', criterionNames[entry.issue.category] ?? 'Góp ý'), node('p', entry.issue.explanation));
        if (entry.issue.replacement?.trim()) errorTooltip.append(node('small', `Gợi ý: ${entry.issue.replacement}`));
        errorTooltip.classList.toggle('is-grammar', entry.issue.category === 'grammar');
        errorTooltip.hidden = false; q('editor').setAttribute('aria-describedby', errorTooltip.id);
        const bounds = root.getBoundingClientRect();
        const width = errorTooltip.offsetWidth;
        errorTooltip.style.left = `${Math.max(8, Math.min(rect.left - bounds.left, root.clientWidth - width - 8))}px`;
        const above = rect.top - bounds.top - errorTooltip.offsetHeight - 10;
        errorTooltip.style.top = `${above >= 0 ? above : rect.bottom - bounds.top + 10}px`;
    }
    q('editor').addEventListener('mousemove', event => {
        for (const entry of editorHighlights) for (const rect of entry.range.getClientRects()) {
            if (event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom) {
                showErrorTooltip(entry, rect); return;
            }
        }
        hideErrorTooltip();
    });
    q('editor').addEventListener('mouseleave', hideErrorTooltip);
    q('editor').addEventListener('scroll', hideErrorTooltip);
    q('editor').addEventListener('blur', hideErrorTooltip);
    q('editor').addEventListener('keyup', event => {
        if (event.key === 'Escape') { hideErrorTooltip(); return; }
        const selection = window.getSelection();
        if (!selection?.rangeCount) return;
        const caret = selection.getRangeAt(0);
        const entry = editorHighlights.find(item => item.range.isPointInRange(caret.startContainer, caret.startOffset));
        if (entry) showErrorTooltip(entry, entry.range.getBoundingClientRect()); else hideErrorTooltip();
    });
    window.addEventListener('resize', hideErrorTooltip);
    window.addEventListener('scroll', hideErrorTooltip, true);
    q('hint-toggle').addEventListener('click', () => {
        const expanded = q('hint-toggle').getAttribute('aria-expanded') !== 'true';
        q('hint-toggle').setAttribute('aria-expanded', String(expanded));
        q('hint-toggle').replaceChildren(document.createTextNode(expanded ? 'Thu gọn gợi ý ' : 'Xem gợi ý '));
        const arrow = icon(expanded ? 'chevron-up' : 'arrow-right');
        q('hint-toggle').append(arrow);
        q('prompt-hints').hidden = !expanded;
    });
    const confirmDialog = q('confirm-dialog');
    let resolveConfirmation = null;
    function confirmAction(title, description, label) {
        // A second trigger must not create or overwrite an outstanding confirmation.
        if (resolveConfirmation) return Promise.resolve(false);
        q('confirm-title').textContent = title;
        q('confirm-description').textContent = description;
        q('confirm-accept').textContent = label;
        confirmDialog.returnValue = '';
        return new Promise(resolve => { resolveConfirmation = resolve; confirmDialog.showModal(); });
    }
    q('confirm-cancel').addEventListener('click', () => confirmDialog.close('cancel'));
    q('confirm-accept').addEventListener('click', () => confirmDialog.close('confirm'));
    confirmDialog.addEventListener('close', () => {
        const resolve = resolveConfirmation; resolveConfirmation = null;
        resolve?.(confirmDialog.returnValue === 'confirm');
    });
    const shownCreditFailures = new Set();
    const creditDialog = q('credit-dialog');
    function showCreditDialog(key) {
        if (shownCreditFailures.has(key)) return;
        shownCreditFailures.add(key);
        if (!creditDialog.open) creditDialog.showModal();
    }
    q('credit-close').addEventListener('click', () => creditDialog.close());
    const status = (text, error = false) => { q('status').textContent = text; q('status').classList.toggle('is-error', error); q('status').hidden = !error; };
    const fail = error => {
        if (session && [401, 403, 419].includes(error.status)) { session.fatal = true; session.result = null; session.notify(); }
        const code = error.code ?? error.message;
        status(errorText(code), true);
        if (code === 'AI_CREDIT_INSUFFICIENT') showCreditDialog(session?.pending?.request_id ?? 'direct-credit-error');
    };
    const run = async fn => { try { return await fn(); } catch (error) { fail(error); } };
    async function history() {
        const data = await request(session ? `${api}/drafts/${session.draft}/submissions?page=${historyPage}` : `${api}/drafts?page=${historyPage}`);
        q('history-panel').classList.toggle('is-empty', !data.data.length && historyPage === 1);
        historyNext = data.next_page; q('history').replaceChildren();
        q('history-title').textContent = session ? 'Lịch sử đánh giá' : 'Bản nháp của bạn';
        if (!data.data.length) q('history').append(node('p', 'Chưa có bài viết hoặc lượt đánh giá ở trang này.'));
        for (const item of data.data) {
            if (!session) {
                const link = node('a', `${item.topic} · ${taskNames[item.task] ?? item.task} · phiên bản ${item.revision}`, 'tai-writing-history-item');
                link.href = `${page}/${encodeURIComponent(item.id)}`; q('history').append(link);
            } else {
                const button = node('button', undefined, 'tai-writing-history-item tai-writing-secondary tai-writing-history-entry');
                button.dataset.writingHistoryId = item.id;
                button.setAttribute('aria-pressed', String(historySelection?.id === item.id));
                const symbol = node('span', undefined, 'tai-writing-history-symbol'); symbol.append(icon('document'));
                const description = node('span', undefined, 'tai-writing-history-description');
                const title = node('span', undefined, 'tai-writing-history-version'); title.append(node('strong', `Phiên bản ${item.revision}`));
                if (historyPage === 1 && item === data.data[0]) title.append(node('small', 'Mới nhất', 'tai-writing-history-latest'));
                description.append(title);
                const date = item.created_at ? new Date(item.created_at) : null;
                if (date && Number.isFinite(date.getTime())) {
                    const time = node('time', date.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }));
                    time.dateTime = date.toISOString(); description.append(time);
                }
                const statusKind = item.status === 'completed' ? 'success' : item.status === 'failed' ? 'failed' : 'pending';
                const badge = node('span', stateLabel(item.status), `tai-writing-history-badge is-${statusKind}`);
                const action = node('span', undefined, 'tai-writing-history-open');
                action.append(node('span', historySelection?.id === item.id ? 'Đang xem' : 'Xem kết quả'), icon('chevron-right'));
                button.append(symbol, description, badge, action);
                button.type = 'button'; button.addEventListener('click', () => run(async () => {
                    if (session.pending || session.busy) throw new Error('WRITING_PENDING');
                    historySelection = await request(`${api}/submissions/${item.id}`);
                    render(session);
                })); q('history').append(button);
            }
        }
        q('history-pagination').hidden = historyPage === 1 && !historyNext;
        q('history-prev').disabled = historyPage === 1; q('history-next').disabled = !historyNext;
    }
    function render(s) {
        for (const entry of q('history').querySelectorAll('[data-writing-history-id]')) {
            const selected = entry.dataset.writingHistoryId === historySelection?.id;
            entry.setAttribute('aria-pressed', String(selected));
            entry.querySelector('.tai-writing-history-open span').textContent = selected ? 'Đang xem' : 'Xem kết quả';
        }
        const displayedContent = historySelection?.original ?? s.content;
        const editor = q('editor'); if (editorPlainText(editor) !== displayedContent) editor.textContent = displayedContent;
        editor.closest('.tai-writing-editor-card').classList.remove('is-not-started');
        editor.contentEditable = String(!historySelection && !s.busy && !s.fatal);
        editor.setAttribute('aria-readonly', String(!!historySelection));
        q('history-view').hidden = !historySelection;
        updateEditorHighlights(s);
        editor.setAttribute('aria-disabled', String(s.busy || s.fatal));
        for (const control of q('toolbar').querySelectorAll('[data-writing-format], select')) control.disabled = !!historySelection || s.busy || s.fatal;
        q('controls').hidden = !!historySelection;
        q('editor-notes').hidden = !!historySelection;
        q('refresh').hidden = !s.result && !s.pending;
        q('refresh').disabled = s.busy || s.fatal;
        q('save-now').hidden = !s.dirty || !!s.conflict;
        q('words').textContent = `${wordCount(displayedContent)} từ`;
        q('editor-words').textContent = `${wordCount(displayedContent)} từ`;
        q('revision').textContent = `Phiên bản ${historySelection?.revision ?? s.revision}`;
        const savedDate = s.savedAt ? new Date(s.savedAt) : null;
        const savedTime = savedDate && Number.isFinite(savedDate.getTime())
            ? savedDate.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }) : null;
        q('save').textContent = s.conflict ? 'Cần chọn phiên bản' : s.saving ? 'Đang lưu…' : s.dirty ? 'Chưa đồng bộ'
            : savedTime ? `Đã tự động lưu lúc ${savedTime}` : 'Đã tự động lưu';
        q('editor-save').textContent = q('save').textContent;
        if (historySelection) q('editor-save').textContent = 'Bài đã gửi · Chỉ xem';
        q('submit').disabled = !s.canSubmit;
        q('submit').textContent = s.result?.status === 'completed' ? 'Gửi bản sửa để chấm lại' : 'Gửi đánh giá';
        q('save-now').disabled = s.fatal || !!s.conflict || s.busy;
        q('conflict').hidden = !s.conflict; q('remote').textContent = s.conflict?.content ?? '';
        q('resume').hidden = !s.pending || s.result?.request_id === s.pending.request_id || s.pending.kind === 'durable';
        q('resume').disabled = s.busy || s.fatal;
        q('retry').hidden = s.result?.recovery !== 'new_attempt' || !!s.pending;
        q('retry').disabled = s.busy || s.fatal;
        renderResult(s);
    }
    function renderResult(s) {
        const result = historySelection ?? s.result;
        const failed = result?.status === 'failed';
        const failureMessage = failed ? errorText(result.error_code) : '';
        if (failed && result.error_code === 'AI_CREDIT_INSUFFICIENT') showCreditDialog(result.request_id ?? result.id);
        q('result-error').hidden = !failed; q('result-error').textContent = failureMessage;
        feedbackObserver?.disconnect();
        improvementsObserver?.disconnect();
        q('feedback').replaceChildren(); q('issues').replaceChildren(); q('criteria').replaceChildren();
        const assessment = result?.result;
        if (displayedResultId !== result?.id) { issueFilter = 'all'; issuePage = 0; feedbackExpanded = false; improvementsExpanded = false; displayedResultId = result?.id; }
        q('issue-filters').replaceChildren();
        q('issue-nav').replaceChildren(); q('issue-nav').hidden = true;
        q('overview-issue-nav').replaceChildren(); q('overview-issues').replaceChildren(); q('overview-corrections').hidden = true;
        q('scoreboard').hidden = !assessment; q('tabs').hidden = !assessment; q('score-note').hidden = !assessment;
        q('guide').hidden = !!assessment || !!result || !!s.pending;
        if (!assessment) selectTab(tabs[0]);
        const score = Number.isFinite(assessment?.overall_score) ? assessment.overall_score : null;
        q('overall').textContent = score === null ? '—' : String(score);
        q('ring-value').style.strokeDasharray = `${score === null ? 0 : Math.min(100, Math.max(0, score))} 100`;
        q('ring').classList.toggle('is-empty', score === null);
        q('empty').hidden = !!assessment;
        q('empty-title').textContent = failed && result.error_code === 'AI_CREDIT_INSUFFICIENT' ? 'Không đủ credit để chấm bài' : result ? stateLabel(result.status) : s.pending ? 'Đang xác nhận yêu cầu' : 'Bắt đầu bài viết của bạn';
        q('empty-description').textContent = failed ? failureMessage : result || s.pending
            ? 'Kết quả sẽ hiển thị khi lượt đánh giá hoàn tất. Bạn có thể cập nhật trạng thái bằng nút Cập nhật kết quả.'
            : 'Viết bài rồi gửi đánh giá để xem điểm từng tiêu chí, nhận xét và gợi ý sửa câu.';
        q('issue-count').textContent = String(assessment?.issues?.length ?? 0);
        q('original-panel').hidden = !assessment;
        q('result-actions').hidden = !assessment;
        for (const action of root.querySelectorAll('[data-writing-result-action]')) {
            if (action.dataset.writingResultAction === 'submit') {
                action.disabled = !!historySelection || !s.canSubmit;
                action.title = historySelection ? 'Quay lại bản nháp để sửa và nộp bài.' : !s.canSubmit ? 'Sửa nội dung bài viết trước khi nộp lại.' : 'Gửi bản sửa để AI đánh giá.';
            } else if (action.dataset.writingResultAction === 'improve') {
                action.querySelector('span').textContent = s.metadata?.profile?.framework === 'ielts' ? 'Gợi ý nâng band' : 'Gợi ý cải thiện';
            }
        }
        q('original').replaceChildren();
        q('credit').hidden = !result;
        q('credit').textContent = result ? `Credit đã dùng: ${result.credit_units ?? 'chưa xác định'} · Còn lại: ${result.credit_balance ?? 'chưa xác định'}` : '';
        q('assessment-status').textContent = result ? `${stateLabel(result.status)} · phiên bản ${result.revision}. ${recoveryLabel(result.recovery)}`
            : s.pending ? 'Chưa xác định kết quả gửi bài. Cập nhật trạng thái hoặc tiếp tục đúng yêu cầu đang gửi.' : 'Gửi bài để nhận góp ý theo từng tiêu chí.';
        if (!assessment) {
            q('criteria').append(node('p', 'Điểm từng tiêu chí sẽ xuất hiện sau khi đánh giá.', 'tai-writing-muted'));
            q('issues').append(node('p', 'Chưa có gợi ý sửa. Gửi bài để nhận đánh giá.', 'tai-writing-muted'));
            return;
        }
        const feedback = node('section', undefined, 'tai-writing-ai-feedback');
        const feedbackText = node('p', assessment.feedback, 'tai-writing-feedback-text');
        feedbackText.id = 'tai-writing-feedback-text';
        const feedbackToggle = node('button', undefined, 'tai-writing-feedback-toggle');
        feedbackToggle.type = 'button'; feedbackToggle.dataset.writingFeedbackToggle = '';
        feedbackToggle.setAttribute('aria-controls', feedbackText.id);
        feedbackToggle.hidden = !feedbackExpanded;
        const updateFeedback = () => {
            feedbackText.classList.toggle('is-collapsed', !feedbackExpanded);
            feedbackToggle.setAttribute('aria-expanded', String(feedbackExpanded));
            feedbackToggle.replaceChildren(document.createTextNode(feedbackExpanded ? 'Thu gọn' : 'Xem thêm'), icon(feedbackExpanded ? 'chevron-up' : 'chevron-down'));
        };
        feedbackToggle.addEventListener('click', () => { feedbackExpanded = !feedbackExpanded; updateFeedback(); });
        updateFeedback();
        const feedbackHeading = node('h3'); feedbackHeading.append(icon('sparkles'), document.createTextNode('Nhận xét từ AI'));
        feedback.append(feedbackHeading, feedbackText, feedbackToggle);
        q('feedback').append(feedback);
        const strengths = Array.isArray(assessment.strengths) ? assessment.strengths.filter(point => typeof point === 'string' && point.trim()) : [];
        const improvements = Array.isArray(assessment.improvements) && assessment.improvements.length
            ? assessment.improvements.filter(point => typeof point === 'string' && point.trim())
            : [...new Set(assessment.issues.map(issue => issue.explanation).filter(Boolean))].slice(0, 5);
        for (const [kind, title, points, symbol, emptyText] of [
            ['strengths', 'Điểm tốt', strengths, 'check', 'Lượt chấm này chưa có nhận xét riêng về điểm tốt.'],
            ['improvements', 'Cần cải thiện', improvements, 'warning', 'Chưa có gợi ý cải thiện cụ thể trong lượt chấm này.'],
        ]) {
            const panel = node('section', undefined, `tai-writing-feedback-points is-${kind}`);
            panel.dataset.writingFeedbackPoints = kind;
            const body = node('div'); body.append(node('h3', title));
            const content = node('div', undefined, 'tai-writing-points-content');
            if (points.length) { const list = node('ul'); for (const point of points) list.append(node('li', point)); content.append(list); }
            else content.append(node('p', emptyText, 'tai-writing-muted'));
            body.append(content);
            if (kind === 'improvements') {
                content.id = 'tai-writing-improvements-content';
                const toggle = node('button', undefined, 'tai-writing-feedback-toggle');
                toggle.type = 'button'; toggle.dataset.writingImprovementsToggle = '';
                toggle.setAttribute('aria-controls', content.id); toggle.hidden = !improvementsExpanded;
                const update = () => {
                    content.classList.toggle('is-collapsed', !improvementsExpanded);
                    toggle.setAttribute('aria-expanded', String(improvementsExpanded));
                    toggle.replaceChildren(document.createTextNode(improvementsExpanded ? 'Thu gọn' : 'Xem thêm'), icon(improvementsExpanded ? 'chevron-up' : 'chevron-down'));
                };
                toggle.addEventListener('click', () => { improvementsExpanded = !improvementsExpanded; update(); });
                update(); body.append(toggle);
                improvementsObserver = new ResizeObserver(() => {
                    if (!improvementsExpanded && content.clientHeight > 0) toggle.hidden = content.scrollHeight <= content.clientHeight + 1;
                });
                improvementsObserver.observe(content);
            }
            panel.append(icon(symbol), body); q('feedback').append(panel);
        }
        feedbackObserver = new ResizeObserver(() => {
            if (!feedbackExpanded && feedbackText.clientHeight > 0) {
                feedbackToggle.hidden = feedbackText.scrollHeight <= feedbackText.clientHeight + 1;
            }
        });
        feedbackObserver.observe(feedbackText);
        if (score === null) q('feedback').append(node('p', 'Chưa đủ dữ liệu để tính điểm tổng.', 'tai-writing-muted'));
        for (const [key, criterion] of Object.entries(assessment.criteria)) {
            const row = node('div', undefined, 'tai-writing-criterion');
            const heading = node('div', undefined, 'tai-writing-row');
            const value = Number.isFinite(criterion.score) ? criterion.score : null;
            heading.append(node('span', criterionNames[key] ?? key), node('strong', value === null ? '—' : String(value)));
            const meter = node('div', undefined, 'tai-writing-meter');
            if (value !== null) {
                meter.setAttribute('role', 'meter'); meter.setAttribute('aria-label', criterionNames[key] ?? key);
                meter.setAttribute('aria-valuemin', '0'); meter.setAttribute('aria-valuemax', '100'); meter.setAttribute('aria-valuenow', String(value));
            }
            const fill = node('span'); fill.style.width = `${value === null ? 0 : Math.min(100, Math.max(0, value))}%`; meter.append(fill);
            row.append(heading, meter);
            if (value === null) row.append(node('small', 'Chưa đủ dữ liệu'));
            q('criteria').append(row);
        }
        if (!Object.keys(assessment.criteria).length) q('criteria').append(node('p', 'Chưa có điểm từng tiêu chí.', 'tai-writing-muted'));
        const groups = new Map();
        for (const issue of assessment.issues) groups.set(issue.category, (groups.get(issue.category) ?? 0) + 1);
        const summary = node('div', undefined, 'tai-writing-issue-groups');
        for (const [category, count] of groups) {
            const group = node('button', undefined, `tai-writing-issue-group tai-writing-issue-group--${category}`);
            group.type = 'button'; group.dataset.writingGroup = category;
            const badge = node('span', String(count), 'tai-writing-group-badge'); badge.setAttribute('aria-hidden', 'true');
            const text = node('span'); text.append(node('strong', `${count} gợi ý · ${criterionNames[category] ?? category}`), node('small', 'Xem câu gốc, cách sửa và giải thích'));
            const arrow = icon('chevron-right');
            group.append(badge, text, arrow);
            group.addEventListener('click', () => { issueFilter = category; issuePage = 0; renderResult(s); selectTab(tabs[1], true); });
            summary.append(group);
        }
        if (groups.size) q('feedback').append(summary);
        for (const [category, count] of [['all', assessment.issues.length], ...groups]) {
            const filter = node('button', category === 'all' ? `Tất cả (${count})` : `${criterionNames[category] ?? category} (${count})`, 'tai-writing-secondary');
            filter.type = 'button'; filter.dataset.writingIssueFilter = category;
            filter.setAttribute('aria-pressed', String(issueFilter === category));
            filter.addEventListener('click', () => {
                issueFilter = category; issuePage = 0; renderResult(s);
                [...q('issue-filters').children].find(button => button.dataset.writingIssueFilter === category)?.focus();
            });
            q('issue-filters').append(filter);
        }
        if (!assessment.issues.length) q('issues').append(node('p', 'Không có gợi ý sửa chi tiết trong lượt đánh giá này.', 'tai-writing-muted'));
        highlight(q('original'), result.original, result.result.issues);
        const filtered = assessment.issues.map((issue, index) => ({ issue, index }))
            .filter(({ issue }) => issueFilter === 'all' || issueFilter === issue.category);
        issuePage = Math.max(0, Math.min(issuePage, filtered.length - 1));
        if (filtered.length) {
            q('issue-nav').hidden = false;
            q('overview-corrections').hidden = false;
            for (const nav of [q('issue-nav'), q('overview-issue-nav')]) {
            const heading = node('h3', 'Gợi ý chỉnh sửa câu');
            const navigation = node('div', undefined, 'tai-writing-issue-pagination');
            const counter = node('span', `${issuePage + 1}/${filtered.length}`); counter.setAttribute('aria-live', 'polite');
            navigation.append(counter);
            for (const [direction, label, step] of [['prev', 'Gợi ý trước', -1], ['next', 'Gợi ý tiếp theo', 1]]) {
                const button = node('button', undefined, 'tai-writing-secondary');
                button.append(icon(step < 0 ? 'chevron-left' : 'chevron-right'));
                button.type = 'button'; button.dataset.writingIssuePage = direction; button.setAttribute('aria-label', label);
                button.disabled = issuePage + step < 0 || issuePage + step >= filtered.length;
                button.addEventListener('click', () => {
                    issuePage += step; renderResult(s);
                    const buttons = [...nav.querySelectorAll('button')];
                    (buttons.find(item => item.dataset.writingIssuePage === direction && !item.disabled) ?? buttons.find(item => !item.disabled))?.focus();
                });
                navigation.append(button);
            }
            nav.append(heading, navigation);
            }
        }
        result.result.issues.forEach((issue, index) => {
            if (index !== filtered[issuePage]?.index) return;
            for (const destination of [q('issues'), q('overview-issues')]) {
            const card = node('section', undefined, 'tai-writing-issue');
            card.append(node('span', criterionNames[issue.category] ?? issue.category, 'tai-writing-issue-category'));
            const comparison = node('div', undefined, 'tai-writing-comparison');
            const before = node('div', undefined, 'tai-writing-before');
            const after = node('div', undefined, 'tai-writing-after');
            const beforeText = node('p', issue.original), afterText = node('p', issue.replacement.trim() ? issue.replacement : 'Nhận xét để bạn tự chỉnh sửa.');
            if (issue.replacement.trim() && issue.original !== issue.replacement) {
                let prefix = 0, suffix = 0;
                while (prefix < Math.min(issue.original.length, issue.replacement.length) && issue.original[prefix] === issue.replacement[prefix]) prefix++;
                while (prefix > 0 && !/\s/.test(issue.original[prefix - 1])) prefix--;
                while (suffix < Math.min(issue.original.length, issue.replacement.length) - prefix && issue.original.at(-suffix - 1) === issue.replacement.at(-suffix - 1)) suffix++;
                while (suffix > 0 && !/\s/.test(issue.original[issue.original.length - suffix])) suffix--;
                for (const [element, text] of [[beforeText, issue.original], [afterText, issue.replacement]]) {
                    element.replaceChildren(document.createTextNode(text.slice(0, prefix)), node('strong', text.slice(prefix, text.length - suffix)), document.createTextNode(suffix ? text.slice(-suffix) : ''));
                }
            }
            before.append(node('small', 'Câu gốc của bạn'), beforeText);
            after.append(node('small', 'Câu gợi ý'), afterText);
            const arrow = icon('arrow-right'); arrow.classList.add('tai-writing-comparison-arrow');
            comparison.append(before, arrow, after); card.append(comparison, node('p', issue.explanation, 'tai-writing-explanation'));
            const apply = node('button', s.applied.has(index) ? 'Đã áp dụng' : 'Áp dụng sửa lỗi này'); apply.type = 'button';
            try { apply.disabled = s.busy || s.fatal || s.applied.has(index) || !issue.applicable
                || s.content !== applyWritingIssues(result.original, result.result.issues, s.applied);
                applyWritingIssues(result.original, result.result.issues, new Set([...s.applied, index])); }
            catch { apply.disabled = true; }
            apply.addEventListener('click', () => run(async () => { if (historySelection) return; s.apply(index); scheduleSave(); })); if (issue.replacement.trim() && !historySelection) card.append(apply);
            if (!issue.applicable && issue.replacement.trim()) card.append(node('p', 'Vị trí chưa xác minh được; đọc góp ý và sửa thủ công.'));
            destination.append(card);
            }
        });
    }
    function scheduleSave() { clearTimeout(saveTimer); saveTimer = setTimeout(() => run(() => session.save()), 800); }
    async function refresh() {
        if (!session || refreshing || session.busy || session.fatal) return;
        refreshing = true;
        try { await session.refresh(); await history(); status('Đã cập nhật trạng thái bài viết.'); }
        finally { refreshing = false; }
    }
    async function open(id) {
        session = new WritingSession({ actor: root.dataset.actor, draft: id, api, storage: sessionStorage, request,
            uuid: () => crypto.randomUUID(), changed: render });
        await session.load();
        q('start').hidden = true; q('prompt').hidden = false;
        q('editor').dataset.placeholder = 'Start writing here…';
        q('topic').textContent = session.metadata.topic;
        q('title').textContent = taskNames[session.metadata.task] ?? 'Writing Studio';
        q('target').textContent = `${session.metadata.profile.framework.toUpperCase()} ${session.metadata.profile.target}`;
        q('profile').textContent = `${taskNames[session.metadata.task]} · ${session.metadata.profile.framework.toUpperCase()} ${session.metadata.profile.target}`;
        await history();
        status(session.conflict ? errorText('AI_WRITING_REVISION_CONFLICT') : 'Bản nháp đã sẵn sàng. Nội dung được tự lưu khi bạn ngừng gõ.', !!session.conflict);
        if (session.loadError) fail(session.loadError);
        pollTimer = setInterval(() => { if (session.pending && !document.hidden) run(refresh); }, 10000);
        if (session.dirty && !session.conflict) scheduleSave();
    }
    const tabs = [...root.querySelectorAll('[data-writing-tab]')];
    function selectTab(tab, focus = false) {
        for (const button of tabs) {
            const active = button === tab;
            button.setAttribute('aria-selected', String(active)); button.tabIndex = active ? 0 : -1;
        }
        for (const panel of root.querySelectorAll('[data-writing-tab-panel]')) panel.hidden = panel.dataset.writingTabPanel !== tab.dataset.writingTab;
        if (focus) tab.focus();
    }
    for (const tab of tabs) {
        tab.addEventListener('click', () => selectTab(tab));
        tab.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const index = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1
                : (tabs.indexOf(tab) + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            selectTab(tabs[index], true);
        });
    }
    const richEditor = q('editor');
    const syncEditor = () => {
        if (!session || richEditor.contentEditable !== 'true') return;
        session.edit(editorPlainText(richEditor)); scheduleSave();
    };
    richEditor.addEventListener('input', syncEditor);
    // Paste text only; external HTML must never enter the editable document.
    richEditor.addEventListener('paste', event => {
        event.preventDefault();
        if (richEditor.contentEditable === 'true') document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
        syncEditor();
    });
    richEditor.addEventListener('drop', event => event.preventDefault());
    for (const button of q('toolbar').querySelectorAll('[data-writing-format]')) {
        button.addEventListener('mousedown', event => event.preventDefault());
        button.addEventListener('click', () => {
            if (richEditor.contentEditable !== 'true') return;
            richEditor.focus(); document.execCommand(button.dataset.writingFormat, false); syncEditor(); updateFormat();
        });
    }
    let editorRange;
    function updateFormat() {
        const selection = window.getSelection();
        if (!selection?.rangeCount || !richEditor.contains(selection.anchorNode)) return;
        editorRange = selection.getRangeAt(0).cloneRange();
        for (const button of q('toolbar').querySelectorAll('[aria-pressed][data-writing-format]')) {
            button.setAttribute('aria-pressed', String(document.queryCommandState(button.dataset.writingFormat)));
        }
    }
    document.addEventListener('selectionchange', updateFormat);
    q('block').addEventListener('change', () => {
        if (richEditor.contentEditable !== 'true') return;
        richEditor.focus();
        if (editorRange && richEditor.contains(editorRange.startContainer)) { const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(editorRange); }
        document.execCommand('formatBlock', false, q('block').value); syncEditor();
    });
    function expandEditor(expanded) {
        richEditor.closest('.tai-writing-editor-card').classList.toggle('is-expanded', expanded);
        q('expand').setAttribute('aria-pressed', String(expanded));
        q('expand').setAttribute('aria-label', expanded ? 'Thu nhỏ editor' : 'Mở rộng editor');
        q('expand').title = expanded ? 'Thu nhỏ editor' : 'Mở rộng editor';
    }
    q('expand').addEventListener('click', () => expandEditor(q('expand').getAttribute('aria-pressed') !== 'true'));
    root.addEventListener('keydown', event => { if (event.key === 'Escape' && q('expand').getAttribute('aria-pressed') === 'true') { expandEditor(false); q('expand').focus(); } });
    q('save-now').addEventListener('click', () => run(() => session.save()));
    for (const action of root.querySelectorAll('[data-writing-result-action]')) {
        action.addEventListener('click', () => {
            if (!session) return;
            if (action.dataset.writingResultAction === 'submit') { if (!historySelection && session.canSubmit) q('submit').click(); }
            else if (action.dataset.writingResultAction === 'errors') {
                selectTab(tabs[1]); q('original-panel').open = true;
                q('original-panel').scrollIntoView({ block: 'center', behavior: 'smooth' });
            } else {
                improvementsExpanded = true; renderResult(session); selectTab(tabs[0]);
                root.querySelector('[data-writing-feedback-points="improvements"]')?.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });
    }
    q('submit').addEventListener('click', () => run(async () => {
        if (!await confirmAction('Gửi bài để AI đánh giá?', 'Thao tác này có thể sử dụng credit. Bài gửi sẽ được giữ riêng với bản nháp đang sửa.', 'Gửi đánh giá')) return;
        clearTimeout(saveTimer); await session.submit(); await history();
    }));
    q('resume').addEventListener('click', () => run(async () => {
        await refresh(); if (!session.pending || session.result?.id) return;
        session.busy = true; session.notify();
        try { await session.sendPending(); } finally { session.busy = false; session.notify(); }
    }));
    q('refresh').addEventListener('click', () => run(refresh));
    q('retry').addEventListener('click', () => run(async () => {
        await refresh();
        if (session.result?.recovery !== 'new_attempt') return;
        if (!await confirmAction('Thử lại lượt đánh giá?', 'Tạo lần thử mới cho bài đã thất bại. Lần thử này có thể sử dụng credit.', 'Xác nhận thử lại')) return;
        await session.retry(true); await history();
    }));
    q('keep').addEventListener('click', () => run(async () => {
        if (await confirmAction('Giữ bài đang soạn?', 'Lưu bài đang soạn thay cho nội dung hiện tại trên máy chủ. Phiên bản cũ vẫn được giữ trong lịch sử.', 'Giữ và lưu bài')) await session.resolveConflict(true);
    }));
    q('use-server').addEventListener('click', () => run(async () => {
        if (await confirmAction('Dùng bản trên máy chủ?', 'Nội dung editor sẽ được thay bằng bản trên máy chủ. Phần đang soạn chưa đồng bộ sẽ bị bỏ.', 'Dùng bản máy chủ')) await session.resolveConflict(false);
    }));
    for (const [control, move] of [['history-prev', () => historyPage - 1], ['history-next', () => historyNext]]) {
        q(control).addEventListener('click', () => run(async () => { historyPage = move(); await history(); }));
    }
    window.addEventListener('beforeunload', event => { if (session?.dirty || session?.pending || session?.saving) { event.preventDefault(); event.returnValue = ''; } });
    window.addEventListener('online', () => run(async () => { if (session) { await refresh(); await session.save(); } }));
    window.addEventListener('pagehide', () => { clearTimeout(saveTimer); clearInterval(pollTimer); });
    if (root.dataset.draft) await run(() => open(root.dataset.draft));
    else {
        const form = q('create');
        function options() {
            const framework = form.elements.framework.value;
            for (const [name, values] of [['target', writingTargets[framework]], ['task', tasks[framework]]]) {
                form.elements[name].replaceChildren(...values.map(value => { const option = node('option', taskNames[value] ?? value); option.value = value; return option; }));
            }
        }
        function taskHint() { q('task-hint').hidden = form.elements.task.value !== 'ielts_task_1'; }
        form.elements.task.addEventListener('change', taskHint);
        function targetPreview() { q('target').textContent = `${form.elements.framework.value.toUpperCase()} ${form.elements.target.value}`; }
        options(); targetPreview(); taskHint();
        form.elements.framework.addEventListener('change', () => { options(); targetPreview(); taskHint(); });
        form.elements.target.addEventListener('change', targetPreview);
        renderResult({ result: null, pending: null });
        q('start').hidden = false; status('Chọn mục tiêu và đề bài để bắt đầu.'); await run(history);
        form.addEventListener('submit', event => { event.preventDefault(); run(async () => {
            const button = form.querySelector('button[type=submit]'); button.disabled = true;
            try {
                const data = Object.fromEntries(new FormData(form));
                if (new TextEncoder().encode(data.topic).length > 5000) throw new Error('WRITING_TOPIC_TOO_LONG');
                const draft = await request(`${api}/drafts`, 'POST', data);
                window.history.replaceState(null, '', `${page}/${encodeURIComponent(draft.id)}`);
                historyPage = 1; await open(draft.id); q('editor').focus();
            } finally { button.disabled = false; }
        }); });
    }
    return { get session() { return session; }, refresh };
}
function stateLabel(state) { return ({ queued: 'Đang chờ đánh giá', processing: 'Đang đánh giá', completed: 'Đã đánh giá', failed: 'Đánh giá thất bại', reconciliation_required: 'Đang chờ đối soát' })[state] ?? state; }
function recoveryLabel(state) { return ({ reconciliation: 'Liên hệ quản trị viên; chưa thể tạo lần thử mới.', blocked: 'Không thể tự động thử lại lượt này.', new_attempt: 'Có thể xác nhận một lần thử mới.', same_request: 'Cập nhật kết quả; không gửi lượt mới.', completed: 'Bạn có thể sửa bản nháp rồi gửi chấm lại.' })[state] ?? ''; }
if (typeof document !== 'undefined') {
    const boot = () => {
        document.querySelectorAll('[data-tai-writing]').forEach(root => startWriting(root));
        document.querySelectorAll('form[action*="logout"]').forEach(form => form.addEventListener('submit', () => {
            try { for (const key of Object.keys(sessionStorage)) if (key.startsWith('tai-writing:')) sessionStorage.removeItem(key); } catch {}
        }));
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true }); else boot();
}
