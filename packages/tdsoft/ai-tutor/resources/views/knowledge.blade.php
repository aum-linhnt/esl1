@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'Knowledge Gia sư AI'])
@push('styles')
    @if(config('ai-tutor.ui.asset_entries'))
        @vite(config('ai-tutor.ui.asset_entries'))
    @endif
@endpush
@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license" data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.license.admin_theme') ?? 'system' }}" data-tai-knowledge data-api="{{ url('/ai-tutor/api/v1') }}">
    <h1>Knowledge Gia sư AI</h1>
    <p>Nhập nội dung → tạo vector bằng OpenAI → kiểm tra → publish. Chỉ nguồn đã publish và đúng quyền bài học được dùng.</p>
    <p>Hiện hỗ trợ nội dung Text, Markdown và HTML. Không nhập đáp án bài thi vào tài liệu cho học viên.</p>
    <p role="status" data-status>Sẵn sàng.</p>
    <section>
        <h2>Tài liệu mới</h2>
        <form data-document-form>
            <label>Tiêu đề <input name="title" maxlength="191" required></label>
            <label>ID bài học <input name="lesson_id" maxlength="191" required></label>
            <label>Định dạng <select name="format"><option value="text">Text</option><option value="markdown">Markdown</option><option value="html">HTML</option></select></label>
            <label>Phạm vi <select name="visibility"><option value="private">Riêng tư</option><option value="learners">Học viên bài học</option></select></label>
            <label>Chứa đáp án <select name="contains_answers"><option value="0">Không</option><option value="1">Có — không dùng cho RAG học viên</option></select></label>
            <label>Nội dung <textarea name="content" rows="10" maxlength="200000" required></textarea></label>
            <button type="submit">Tạo bản nháp</button>
        </form>
    </section>
    <section>
        <h2>Xử lý và publish</h2>
        <label>ID version <input data-version maxlength="36"></label>
        <button type="button" data-process>Tạo vector qua queue</button>
        <button type="button" data-check>Kiểm tra trạng thái</button>
        <button type="button" data-publish>Publish version</button>
        <p>Chỉ publish sau khi trạng thái là ready. Publish version mới thay thế version cũ trong truy xuất.</p>
    </section>
</div>
@endsection
