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
    AI_CREDIT_INSUFFICIENT: 'Không đủ credit để đánh giá bài viết.',
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
        this.lastId = null; this.conflict = null; this.saving = null; this.busy = false; this.fatal = false; this.applied = new Set();
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
            } catch (error) {
                if (error.code === 'AI_WRITING_REVISION_CONFLICT' || !error.status) {
                    const server = await this.request(`${this.api}/drafts/${this.draft}`);
                    // Lost PATCH response: recognize that exact content was durably saved.
                    if (server.content === sent || (server.content === base && server.revision === revision)) {
                        this.base = server.content; this.revision = server.revision;
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

export async function startWriting(root) {
    const q = name => root.querySelector(`[data-writing-${name}]`);
    const api = root.dataset.api, page = root.dataset.page;
    let session, saveTimer, pollTimer, historyPage = 1, historyNext = null, refreshing = false;
    const status = (text, error = false) => { q('status').textContent = text; q('status').classList.toggle('is-error', error); };
    const fail = error => {
        if (session && [401, 403, 419].includes(error.status)) { session.fatal = true; session.result = null; session.notify(); }
        status(errorText(error.code ?? error.message), true);
    };
    const run = async fn => { try { return await fn(); } catch (error) { fail(error); } };
    async function history() {
        const data = await request(session ? `${api}/drafts/${session.draft}/submissions?page=${historyPage}` : `${api}/drafts?page=${historyPage}`);
        historyNext = data.next_page; q('history').replaceChildren();
        q('history-title').textContent = session ? 'Lịch sử đánh giá' : 'Bản nháp của bạn';
        if (!data.data.length) q('history').append(node('p', 'Chưa có bài viết hoặc lượt đánh giá ở trang này.'));
        for (const item of data.data) {
            if (!session) {
                const link = node('a', `${item.topic} · ${taskNames[item.task] ?? item.task} · phiên bản ${item.revision}`, 'tai-writing-history-item');
                link.href = `${page}/${encodeURIComponent(item.id)}`; q('history').append(link);
            } else {
                const button = node('button', `Phiên bản ${item.revision} · ${stateLabel(item.status)}`, 'tai-writing-history-item tai-writing-secondary');
                button.type = 'button'; button.addEventListener('click', () => run(async () => {
                    if (session.pending || session.busy) throw new Error('WRITING_PENDING');
                    session.accept(await request(`${api}/submissions/${item.id}`));
                })); q('history').append(button);
            }
        }
        q('history-prev').disabled = historyPage === 1; q('history-next').disabled = !historyNext;
    }
    function render(s) {
        const editor = q('editor'); if (editor.value !== s.content) editor.value = s.content;
        editor.readOnly = s.busy || s.fatal;
        q('count').textContent = `${wordCount(s.content)} từ · ${new TextEncoder().encode(s.content).length.toLocaleString('vi')} / 20.000 byte`;
        q('revision').textContent = `Phiên bản ${s.revision}`;
        q('save').textContent = s.conflict ? 'Cần chọn phiên bản' : s.saving ? 'Đang lưu…' : s.dirty ? 'Chưa đồng bộ' : 'Đã lưu';
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
        const result = s.result;
        q('feedback').replaceChildren(); q('issues').replaceChildren(); q('original-panel').hidden = !result?.result;
        q('credit').textContent = result ? `Credit đã dùng: ${result.credit_units ?? 'chưa xác định'} · Còn lại: ${result.credit_balance ?? 'chưa xác định'}` : '';
        q('assessment-status').textContent = result ? `${stateLabel(result.status)} · phiên bản ${result.revision}. ${recoveryLabel(result.recovery)}`
            : s.pending ? 'Chưa xác định kết quả gửi bài. Cập nhật trạng thái hoặc tiếp tục đúng yêu cầu đang gửi.' : 'Gửi bài để nhận góp ý theo từng tiêu chí.';
        if (!result?.result) return;
        q('feedback').append(node('h3', result.result.overall_score === null ? 'Chưa đủ evidence để tính điểm tổng' : `Điểm luyện tập: ${result.result.overall_score}/100`));
        q('feedback').append(node('p', result.result.feedback));
        for (const [key, criterion] of Object.entries(result.result.criteria)) q('feedback').append(node('p',
            `${criterionNames[key] ?? key}: ${criterion.score === null ? 'Chưa đủ dữ liệu' : `${criterion.score}/100`}`, 'tai-writing-score'));
        highlight(q('original'), result.original, result.result.issues);
        result.result.issues.forEach((issue, index) => {
            const card = node('section', undefined, 'tai-writing-issue');
            card.append(node('h3', criterionNames[issue.category] ?? issue.category), node('p', `${issue.original} → ${issue.replacement}`), node('p', issue.explanation));
            const apply = node('button', s.applied.has(index) ? 'Đã áp dụng' : 'Áp dụng sửa lỗi này'); apply.type = 'button';
            try { apply.disabled = s.busy || s.fatal || s.applied.has(index) || !issue.applicable
                || s.content !== applyWritingIssues(result.original, result.result.issues, s.applied);
                applyWritingIssues(result.original, result.result.issues, new Set([...s.applied, index])); }
            catch { apply.disabled = true; }
            apply.addEventListener('click', () => run(async () => { s.apply(index); scheduleSave(); })); card.append(apply);
            if (!issue.applicable) card.append(node('p', 'Vị trí chưa xác minh được; đọc góp ý và sửa thủ công.'));
            q('issues').append(card);
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
        q('start').hidden = true; q('workspace').hidden = false;
        q('topic').textContent = session.metadata.topic;
        q('profile').textContent = `${taskNames[session.metadata.task]} · ${session.metadata.profile.framework.toUpperCase()} ${session.metadata.profile.target}`;
        await history();
        status(session.conflict ? errorText('AI_WRITING_REVISION_CONFLICT') : 'Bản nháp đã sẵn sàng. Nội dung được tự lưu khi bạn ngừng gõ.', !!session.conflict);
        if (session.loadError) fail(session.loadError);
        pollTimer = setInterval(() => { if (session.pending && !document.hidden) run(refresh); }, 10000);
        if (session.dirty && !session.conflict) scheduleSave();
    }
    q('editor').addEventListener('input', () => { session.edit(q('editor').value); scheduleSave(); });
    q('save-now').addEventListener('click', () => run(() => session.save()));
    q('submit').addEventListener('click', () => run(async () => {
        if (!confirm('Gửi bài để AI đánh giá? Thao tác này có thể sử dụng credit.')) return;
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
        if (!confirm('Tạo lần thử mới cho bài đã thất bại? Lần thử này có thể sử dụng credit.')) return;
        await session.retry(true); await history();
    }));
    q('keep').addEventListener('click', () => run(async () => {
        if (confirm('Lưu bài đang soạn thay cho nội dung hiện tại trên máy chủ? Phiên bản cũ vẫn được giữ trong lịch sử.')) await session.resolveConflict(true);
    }));
    q('use-server').addEventListener('click', () => run(async () => {
        if (confirm('Thay nội dung editor bằng bản trên máy chủ? Phần đang soạn chưa đồng bộ sẽ bị bỏ.')) await session.resolveConflict(false);
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
        options(); form.elements.framework.addEventListener('change', options);
        q('start').hidden = false; status('Chọn mục tiêu và đề bài để bắt đầu.'); await run(history);
        form.addEventListener('submit', event => { event.preventDefault(); run(async () => {
            const button = form.querySelector('button[type=submit]'); button.disabled = true;
            try {
                const data = Object.fromEntries(new FormData(form));
                if (new TextEncoder().encode(data.topic).length > 5000) throw new Error('WRITING_TOPIC_TOO_LONG');
                const draft = await request(`${api}/drafts`, 'POST', data);
                window.history.replaceState(null, '', `${page}/${encodeURIComponent(draft.id)}`);
                historyPage = 1; await open(draft.id);
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
