@extends('ai-tutor::layouts.tutor')
@section('title', 'Writing Studio')
@section('content')
<main class="tai-writing" data-tai-writing data-actor="{{ $actorId }}" data-draft="{{ $draftId }}"
      data-api="{{ url('/ai-tutor/api/v1/writing') }}" data-page="{{ url('/ai-tutor/writing') }}">
    <header class="tai-writing-header">
        <div><a href="{{ url('/dashboard') }}">← Về trang học tập</a><p class="tai-writing-eyebrow">ENGLISH PRACTICE</p><h1>Writing Studio</h1>
            <p>Viết từng bước. Nhận góp ý cụ thể. Giữ giọng văn của bạn.</p></div>
        <button type="button" data-tai-theme aria-label="Đổi giao diện sáng hoặc tối">Sáng / Tối</button>
    </header>
    <p data-writing-status role="status" aria-live="polite">Đang tải bài viết…</p>
    <section data-writing-start hidden class="tai-writing-card">
        <h2>Bắt đầu bài viết</h2>
        <form data-writing-create>
            <div class="tai-writing-fields">
                <label>Chương trình<select name="framework"><option value="cefr">CEFR</option><option value="ielts">IELTS</option><option value="toeic">TOEIC</option></select></label>
                <label>Mục tiêu<select name="target"></select></label>
                <label>Dạng bài<select name="task"></select></label>
                <label>Ngôn ngữ nhận xét<select name="feedback_language"><option value="vi">Tiếng Việt</option><option value="en">English</option><option value="bilingual">Song ngữ</option></select></label>
            </div>
            <label>Đề bài<textarea name="topic" rows="3" maxlength="5000" required placeholder="Nhập đề bài hoặc tình huống bạn muốn luyện viết."></textarea></label>
            <p class="tai-writing-muted">IELTS Task 1: nhập đề và dữ liệu mô tả. Điểm AI là điểm luyện tập tham khảo.</p>
            <button type="submit">Tạo bản nháp</button>
        </form>
    </section>
    <div data-writing-workspace hidden class="tai-writing-grid">
        <section class="tai-writing-card">
            <div class="tai-writing-row"><h2 data-writing-topic></h2><span data-writing-revision></span></div>
            <p data-writing-profile class="tai-writing-muted"></p>
            <label for="tai-writing-editor">Bài viết của bạn</label>
            <textarea id="tai-writing-editor" data-writing-editor rows="18" spellcheck="true" placeholder="Start writing here…"></textarea>
            <div class="tai-writing-row"><span data-writing-count>0 từ</span><span data-writing-save role="status" aria-live="polite"></span></div>
            <div class="tai-writing-actions">
                <button type="button" data-writing-submit disabled>Gửi đánh giá</button>
                <button type="button" data-writing-save-now class="tai-writing-secondary">Lưu ngay</button>
                <button type="button" data-writing-resume hidden class="tai-writing-secondary">Tiếp tục yêu cầu đang gửi</button>
                <button type="button" data-writing-refresh class="tai-writing-secondary">Cập nhật kết quả</button>
                <button type="button" data-writing-retry hidden>Thử lại bằng yêu cầu mới</button>
            </div>
            <p class="tai-writing-muted">Gửi đánh giá hoặc chấm lại có thể dùng credit. Bài đã gửi được giữ riêng với bản nháp đang sửa.</p>
            <section data-writing-conflict hidden class="tai-writing-conflict">
                <h3>Bản nháp đã thay đổi ở nơi khác</h3><p>Nội dung đang soạn vẫn được giữ trong editor. So sánh với bản trên máy chủ trước khi chọn.</p>
                <pre data-writing-remote></pre>
                <button type="button" data-writing-keep>Giữ bài đang soạn và lưu thành phiên bản mới</button>
                <button type="button" data-writing-use-server class="tai-writing-secondary">Dùng bản trên máy chủ</button>
            </section>
            <details><summary>Cách lưu bài viết</summary><p>Bản nháp tự lưu khi bạn ngừng gõ. Nội dung chưa đồng bộ được giữ tạm trong phiên trình duyệt của tài khoản này. Hãy chờ “Đã lưu” trước khi đóng tab hoặc đăng xuất.</p></details>
        </section>
        <aside class="tai-writing-card" aria-label="Nhận xét bài viết">
            <h2>Nhận xét</h2><p data-writing-assessment-status>Gửi bài để nhận góp ý theo từng tiêu chí.</p>
            <p data-writing-credit class="tai-writing-muted"></p>
            <div data-writing-feedback></div>
            <div data-writing-issues></div>
            <details data-writing-original-panel hidden><summary>Bài được đánh giá · xem vị trí lỗi</summary><div data-writing-original class="tai-writing-original"></div></details>
        </aside>
    </div>
    <section class="tai-writing-card tai-writing-history">
        <div class="tai-writing-row"><h2 data-writing-history-title>Bản nháp của bạn</h2><a href="{{ url('/ai-tutor/writing') }}">+ Bài viết mới</a></div>
        <div data-writing-history></div>
        <div class="tai-writing-actions"><button type="button" data-writing-history-prev class="tai-writing-secondary" disabled>Trang trước</button><button type="button" data-writing-history-next class="tai-writing-secondary" disabled>Trang sau</button></div>
    </section>
    <noscript><p>Bật JavaScript để dùng editor và autosave. Không gửi bài qua form này khi JavaScript bị tắt.</p></noscript>
</main>
@endsection
