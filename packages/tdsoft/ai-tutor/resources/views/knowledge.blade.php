@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'Knowledge Gia sư AI'])
@push('styles')
    @if(config('ai-tutor.ui.asset_entries'))
        @vite(config('ai-tutor.ui.asset_entries'))
    @endif
@endpush
@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license tai-knowledge" data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.license.admin_theme') ?? 'system' }}" data-tai-knowledge data-api="{{ url('/ai-tutor/api/v1') }}" data-embedding-ready="{{ $embeddingError ? '0' : '1' }}" data-embedding-error="{{ $embeddingError }}">
    <header class="tai-knowledge__header"><div><span class="tai-knowledge__eyebrow">GIA SƯ AI / DỮ LIỆU</span><h1>Knowledge</h1><p>Xây dựng nguồn kiến thức đáng tin cậy cho Gia sư AI.</p></div><span class="tai-knowledge__flow">Nháp <i>→</i> Vector <i>→</i> Publish</span></header>
    <div class="tai-knowledge__notice"><span aria-hidden="true">i</span><p>Chỉ nguồn đã publish và đúng quyền bài học mới được sử dụng. Hỗ trợ Text, Markdown và HTML; không nhập đáp án bài thi vào tài liệu cho học viên.</p></div>
    @php($readinessMessage = match($embeddingError) {
        'AI_DISABLED' => 'AI Tutor đang tắt trong cấu hình.',
        'BILLING_MODE_NOT_SUPPORTED' => 'Billing mode hiện tại chưa hỗ trợ tạo vector.',
        'AI_PROVIDER_NOT_CONFIGURED' => 'Chưa cấu hình API key hoặc embedding model.',
        default => $embeddingError ? 'Cấu hình AI chưa hoàn tất.' : 'Knowledge đã sẵn sàng thao tác.',
    })
    <div class="tai-knowledge__status {{ $embeddingError ? 'is-error' : 'is-ready' }}" role="status" data-status><i aria-hidden="true"></i><span data-status-text>{{ $readinessMessage }}</span><button type="button" data-status-close aria-label="Đóng thông báo" title="Đóng">×</button></div>
    <section class="tai-knowledge__sync" data-course-sync>
        <div class="tai-knowledge__section-title"><span>01</span><div><h2>Đồng bộ từ khóa học</h2><p>Tạo nhanh Knowledge từ nội dung bài học đang có.</p></div></div>
        <p>Chỉ lấy tiêu đề và tóm tắt bài học đang hiển thị. Không lấy hoạt động, đáp án, video hay file. Bản mới chưa publish sẽ không được Tutor sử dụng.</p>
        <div class="tai-knowledge__toolbar"><button type="button" data-sync-courses>Tải khóa học</button><label>Khóa học <select data-sync-course><option value="">Chọn khóa học</option></select></label><button class="tai-knowledge__secondary" type="button" data-sync-preview>Xem trước nội dung</button></div>
        <div class="tai-knowledge__lesson-list" data-sync-lessons></div>
        <p class="tai-knowledge__summary" data-sync-summary>Chưa xem trước. Không gọi AI khi xem trước hoặc chỉ tạo bản nháp.</p>
        <div class="tai-knowledge__confirm"><label><input type="checkbox" data-sync-enqueue @disabled($embeddingError)> <span><strong>Tạo vector sau đồng bộ</strong><small>Thực hiện qua queue, phát sinh chi phí provider và credit của admin.</small></span></label><button type="button" data-sync-submit disabled>Đồng bộ bài đã chọn</button></div>
        <p class="tai-knowledge__hint">Tối đa 50 bài/lần. Sau đồng bộ, kiểm tra trạng thái rồi publish từng version.</p>
        <div class="tai-knowledge__results" data-sync-result></div>
    </section>
    <div class="tai-knowledge__grid">
    <section class="tai-knowledge__library">
        <div class="tai-knowledge__section-title"><span>02</span><div><h2>Thư viện bài học</h2><p>Xem tài liệu và các version đã tạo.</p></div></div>
        <form class="tai-knowledge__inline-form" data-list-form>
            <label>ID bài học <input name="lesson_id" maxlength="191" required></label>
            <button type="submit">Tải danh sách</button>
        </form>
        <div class="tai-knowledge__documents" data-documents></div>
        <h3 class="tai-knowledge__selected" data-selected>Chưa chọn tài liệu</h3>
        <p class="tai-knowledge__hint" data-version-hint>Chọn một version để xem nội dung hoặc tạo bản chỉnh sửa mới.</p>
        <div class="tai-knowledge__versions" data-versions></div>
        <div class="tai-knowledge__withdraw" data-withdraw-row hidden><button class="tai-knowledge__secondary" type="button" data-withdraw disabled>Thu hồi publish</button><small data-withdraw-hint></small></div>
        <form class="tai-knowledge__version-editor" data-version-form>
            <h3>Tạo version mới</h3>
            <p class="tai-knowledge__hint">Nội dung mới luôn tạo thành version riêng, không ghi đè lịch sử.</p>
            <label>Định dạng bản mới <select name="format"><option value="text">Text</option><option value="markdown">Markdown</option><option value="html">HTML</option></select></label>
            <label>Nội dung bản mới <textarea name="content" rows="6" maxlength="200000" required></textarea></label>
            <button type="submit" data-version-submit disabled>Tạo version mới</button>
        </form>
        <details class="tai-knowledge__preview" data-preview-shell hidden><summary>Xem nội dung version gốc đang chọn</summary><pre data-preview class="tai-preview"></pre></details>
    </section>
    <section class="tai-knowledge__create">
        <div class="tai-knowledge__section-title"><span>03</span><div><h2>Tạo tài liệu mới</h2><p>Soạn một nguồn Knowledge thủ công.</p></div></div>
        <form data-document-form>
            <label>Tiêu đề <input name="title" maxlength="191" required></label>
            <label>ID bài học <input name="lesson_id" maxlength="191" required><small>Dùng ID bài học từ LMS; tài liệu sẽ bị giới hạn theo quyền của bài học này.</small></label>
            <div class="tai-knowledge__fields"><label>Định dạng <select name="format"><option value="text">Text</option><option value="markdown">Markdown</option><option value="html">HTML</option></select></label>
            <label>Phạm vi <select name="visibility"><option value="private">Riêng tư</option><option value="learners">Học viên bài học</option></select></label></div>
            <label>Chứa đáp án <select name="contains_answers"><option value="0">Không</option><option value="1">Có — không dùng cho RAG học viên</option></select></label>
            <label>Nội dung <textarea name="content" rows="10" maxlength="200000" required></textarea></label>
            <button type="submit" data-document-submit>Tạo bản nháp</button>
        </form>
    </section>
    </div>
    <section class="tai-knowledge__publish">
        <div class="tai-knowledge__section-title"><span>04</span><div><h2>Xử lý và publish</h2><p>Hoàn tất phiên bản trước khi đưa vào truy xuất.</p></div></div>
        <div class="tai-knowledge__publish-controls"><label>ID version <input data-version maxlength="36" placeholder="UUID của version"></label><div><button type="button" data-process @disabled($embeddingError)>Tạo vector</button><button class="tai-knowledge__secondary" type="button" data-check>Kiểm tra trạng thái</button><button class="tai-knowledge__publish-button" type="button" data-publish disabled>Publish version</button></div></div>
        <div class="tai-knowledge__action-status {{ $embeddingError ? 'is-error' : '' }}" role="status" data-action-status>@if($embeddingError)<strong>Chưa thể tạo vector.</strong> {{ $embeddingError }} — hãy cấu hình API key, embedding model và bật AI Tutor.@else Chọn một version rồi kiểm tra trạng thái trước khi publish.@endif</div>
        <p class="tai-knowledge__hint">Chỉ publish khi trạng thái là <strong>ready</strong>. Version mới sẽ thay thế version cũ trong truy xuất.</p>
    </section>
</div>
@endsection
