@extends(config('ai-tutor.ui.writing_layout', 'ai-tutor::layouts.tutor'))
@php
    $writingStandalone = config('ai-tutor.ui.writing_layout', 'ai-tutor::layouts.tutor') === 'ai-tutor::layouts.tutor';
    $title = 'Writing Studio';
@endphp
@if(!$writingStandalone)
    @push('head')
        @if(config('ai-tutor.ui.asset_entries'))
            @vite(config('ai-tutor.ui.asset_entries'))
        @endif
    @endpush
@endif
@section('title', 'Writing Studio')
@section('content')
<section aria-label="Writing Studio" class="tai-writing {{ $writingStandalone ? '' : 'tai-writing--embedded' }}"
      @if(!$writingStandalone) data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.theme.default', 'system') }}" @endif data-tai-writing data-actor="{{ $actorId }}" data-draft="{{ $draftId }}"
      @if(config('ai-tutor.ui.study_tracking', false)) data-study-source="writing" data-study-api="{{ url('/dashboard-v2/study-sessions') }}" @endif
      data-api="{{ url('/ai-tutor/api/v1/writing') }}" data-page="{{ url('/ai-tutor/writing') }}">
    @if($writingStandalone)
    <nav class="tai-writing-nav" aria-label="Điều hướng Writing">
        <a class="tai-writing-brand" href="{{ url('/dashboard') }}">@include('ai-tutor::partials.icon', ['name' => 'book', 'size' => 30]) <span>ESL <strong>Writing Studio</strong></span></a>
        <div class="tai-writing-nav-links"><a href="{{ url('/dashboard') }}">Trang học tập</a><a href="{{ url('/ai-tutor/writing') }}" aria-current="page">Luyện viết</a></div>
        <button type="button" data-tai-theme aria-label="Đổi giao diện sáng hoặc tối">@include('ai-tutor::partials.icon', ['name' => 'sun']) <span>Sáng / Tối</span></button>
    </nav>
    @endif
    @foreach(['arrow-right', 'chevron-left', 'chevron-right', 'chevron-up', 'chevron-down', 'document', 'check', 'warning', 'sparkles', 'history'] as $writingIcon)
        <template data-writing-icon="{{ $writingIcon }}">@include('ai-tutor::partials.icon', ['name' => $writingIcon, 'size' => 18])</template>
    @endforeach
    <header class="tai-writing-header">
        <div class="tai-writing-heading"><span class="tai-writing-heading-icon">@include('ai-tutor::partials.icon', ['name' => 'document', 'size' => 28])</span><div><p class="tai-writing-eyebrow">ENGLISH PRACTICE / WRITING</p><h1 data-writing-title>Writing Studio</h1>
            <p>Viết từng bước. Nhận góp ý cụ thể. Giữ giọng văn của bạn.</p></div></div>
        <div class="tai-writing-header-actions">
            @if(!$writingStandalone)
                <button type="button" data-tai-theme aria-label="Đổi giao diện sáng hoặc tối">@include('ai-tutor::partials.icon', ['name' => 'sun']) <span>Sáng / Tối</span></button>
            @endif
            <a class="tai-writing-new" href="{{ url('/ai-tutor/writing') }}">@include('ai-tutor::partials.icon', ['name' => 'plus', 'size' => 15]) Bài viết mới</a>
        </div>
    </header>
    <p data-writing-status hidden role="status" aria-live="polite">Đang tải bài viết…</p>
    <div data-writing-workspace class="tai-writing-grid">
        <div class="tai-writing-compose">
            <section data-writing-start hidden class="tai-writing-card tai-writing-setup">
                <div class="tai-writing-section-heading"><span class="tai-writing-step">01</span><div><h2>Thiết lập bài viết</h2><p>Chọn mục tiêu, bắt đầu từ một đề bài.</p></div></div>
                <form data-writing-create>
                    <div class="tai-writing-example-picker">
                        <label>Ví dụ để test<select data-writing-example><option value="">Chọn ví dụ mẫu…</option></select></label>
                        <p>Ví dụ có lỗi cố ý để kiểm tra góp ý. Chọn mẫu sẽ điền lại thiết lập, đề và bài viết phía dưới.</p>
                    </div>
                    <div class="tai-writing-fields">
                        <label>Chương trình<select name="framework"><option value="cefr">CEFR</option><option value="ielts">IELTS</option><option value="toeic">TOEIC</option></select></label>
                        <label>Mục tiêu<select name="target"></select></label>
                        <label>Dạng bài<select name="task"></select></label>
                        <label>Ngôn ngữ nhận xét<select name="feedback_language"><option value="vi">Tiếng Việt</option><option value="en">English</option><option value="bilingual">Song ngữ</option></select></label>
                    </div>
                    <label>Đề bài<textarea name="topic" rows="3" maxlength="5000" required placeholder="Nhập đề bài hoặc tình huống bạn muốn luyện viết."></textarea></label>
                    <p data-writing-task-hint hidden class="tai-writing-muted">IELTS Task 1: nhập đề và dữ liệu mô tả để AI có cơ sở đánh giá.</p>
                    <label>Bài viết ban đầu <span class="tai-writing-muted">(không bắt buộc)</span><textarea name="content" rows="5" maxlength="20000" placeholder="Viết bài của bạn hoặc chọn ví dụ mẫu phía trên."></textarea></label>
                    <button type="submit">Bắt đầu viết</button>
                </form>
            </section>

            <div class="tai-writing-metrics">
                <div class="tai-writing-metric">
                    <span class="tai-writing-icon">@include('ai-tutor::partials.icon', ['name' => 'target'])</span>
                    <div><span>Mục tiêu của bạn</span><strong data-writing-target>—</strong></div>
                </div>
                <div class="tai-writing-metric">
                    <span class="tai-writing-icon">@include('ai-tutor::partials.icon', ['name' => 'document'])</span>
                    <div><span>Số từ hiện tại</span><strong data-writing-words>0 từ</strong></div>
                </div>
                <div class="tai-writing-metric">
                    <span class="tai-writing-icon">@include('ai-tutor::partials.icon', ['name' => 'check'])</span>
                    <div><span>Trạng thái bản nháp</span><strong data-writing-save role="status" aria-live="polite">Chưa bắt đầu</strong></div>
                </div>
            </div>
            <section data-writing-prompt hidden class="tai-writing-card tai-writing-prompt">
                <div class="tai-writing-prompt-heading">
                    <span class="tai-writing-prompt-icon">@include('ai-tutor::partials.icon', ['name' => 'document'])</span>
                    <h2>Đề bài</h2>
                    <button type="button" data-writing-hint-toggle class="tai-writing-secondary" aria-expanded="false" aria-controls="tai-writing-prompt-hints">Xem gợi ý @include('ai-tutor::partials.icon', ['name' => 'arrow-right', 'size' => 16])</button>
                </div>
                <div class="tai-writing-prompt-copy"><p data-writing-topic></p><p data-writing-profile class="tai-writing-muted"></p></div>
                <div id="tai-writing-prompt-hints" data-writing-prompt-hints hidden class="tai-writing-prompt-hints">
                    <h3>Gợi ý cách làm</h3>
                    <ol>
                        <li>Đọc kỹ yêu cầu và xác định những ý cần trả lời.</li>
                        <li>Lập dàn ý ngắn; bổ sung ví dụ hoặc dữ liệu phù hợp với đề bài.</li>
                        <li>Viết bằng lời của bạn, rồi kiểm tra ngữ pháp và sự liên kết giữa các ý.</li>
                    </ol>
                </div>
            </section>
            <section class="tai-writing-card tai-writing-editor-card is-not-started">
                <div class="tai-writing-editor-heading">
                    <label class="tai-writing-editor-label" for="tai-writing-editor">Bài viết của em</label>
                    <span class="tai-writing-editor-language">Viết bằng tiếng Anh</span>
                    <span data-writing-revision>Chưa có bản nháp</span>
                </div>
                <div data-writing-history-view hidden class="tai-writing-history-view" role="status">
                    <span>Đang xem bài đã gửi. Bản nháp hiện tại được giữ nguyên.</span>
                    <button type="button" data-writing-return-draft class="tai-writing-secondary">Quay lại bản nháp</button>
                </div>
                <div data-writing-toolbar class="tai-writing-toolbar" role="toolbar" aria-label="Định dạng bài viết">
                    @foreach(['undo' => ['', 'Hoàn tác'], 'redo' => ['', 'Làm lại']] as $command => $tool)
                        <button type="button" data-writing-format="{{ $command }}" title="{{ $tool[1] }}" aria-label="{{ $tool[1] }}" disabled>@include('ai-tutor::partials.icon', ['name' => $command, 'size' => 18])</button>
                    @endforeach
                    <select data-writing-block aria-label="Kiểu đoạn văn" disabled><option value="p">Đoạn văn</option><option value="h2">Tiêu đề</option><option value="h3">Tiêu đề phụ</option></select>
                    @foreach(['bold' => ['', 'In đậm'], 'italic' => ['', 'In nghiêng'], 'underline' => ['', 'Gạch chân'], 'strikeThrough' => ['', 'Gạch ngang'], 'insertUnorderedList' => ['', 'Danh sách dấu đầu dòng'], 'insertOrderedList' => ['', 'Danh sách đánh số']] as $command => $tool)
                        <button type="button" data-writing-format="{{ $command }}" title="{{ $tool[1] }}" aria-label="{{ $tool[1] }}" aria-pressed="false" disabled>@include('ai-tutor::partials.icon', ['name' => $command, 'size' => 18])</button>
                    @endforeach
                    <span data-writing-editor-words class="tai-writing-toolbar-words">0 từ</span>
                    <button type="button" data-writing-expand title="Mở rộng editor" aria-label="Mở rộng editor" aria-pressed="false">@include('ai-tutor::partials.icon', ['name' => 'expand', 'size' => 18])</button>
                </div>
                <div id="tai-writing-editor" data-writing-editor role="textbox" aria-label="Bài viết của bạn" aria-multiline="true" aria-disabled="true" contenteditable="false" spellcheck="true" data-placeholder="Chọn mục tiêu và nhập đề bài phía trên, rồi bấm Bắt đầu viết."></div>
                <div class="tai-writing-editor-footer">
                <div class="tai-writing-save-indicator-footer"><span class="tai-writing-save-indicator">@include('ai-tutor::partials.icon', ['name' => 'check', 'size' => 16])<span data-writing-editor-save>Chưa bắt đầu</span></span></div>
                <div data-writing-controls hidden class="tai-writing-actions">
                    <button type="button" data-writing-submit disabled>Gửi đánh giá</button>
                    <button type="button" data-writing-save-now disabled class="tai-writing-secondary">Lưu ngay</button>
                    <button type="button" data-writing-resume hidden class="tai-writing-secondary">Tiếp tục yêu cầu đang gửi</button>
                    <button type="button" data-writing-refresh disabled class="tai-writing-secondary">@include('ai-tutor::partials.icon', ['name' => 'redo', 'size' => 16]) Cập nhật kết quả</button>
                    <button type="button" data-writing-retry hidden>Thử lại bằng yêu cầu mới</button>
                </div>
                </div>
                <section data-writing-conflict hidden class="tai-writing-conflict">
                    <h3>Bản nháp đã thay đổi ở nơi khác</h3><p>Nội dung đang soạn vẫn được giữ trong editor. So sánh với bản trên máy chủ trước khi chọn.</p>
                    <pre data-writing-remote></pre>
                    <button type="button" data-writing-keep>Giữ bài đang soạn và lưu thành phiên bản mới</button>
                    <button type="button" data-writing-use-server class="tai-writing-secondary">Dùng bản trên máy chủ</button>
                </section>
            </section>
            <section data-writing-editor-notes hidden class="tai-writing-card tai-writing-save-guide">
                <h3>@include('ai-tutor::partials.icon', ['name' => 'info', 'size' => 18]) Cách lưu bài viết</h3>
                <p>Bản nháp tự lưu khi bạn ngừng gõ. Nội dung chưa đồng bộ được giữ tạm trong phiên trình duyệt của tài khoản này. Hãy chờ “Đã tự động lưu” trước khi đóng tab hoặc đăng xuất.</p>
            </section>
        </div>
        <div class="tai-writing-result-column">
        <aside class="tai-writing-card tai-writing-results" aria-label="Kết quả đánh giá bài viết">
            <div class="tai-writing-result-heading"><span class="tai-writing-icon">@include('ai-tutor::partials.icon', ['name' => 'sparkles'])</span><div><h2>Kết quả đánh giá</h2><span class="tai-writing-muted">Góp ý theo từng tiêu chí</span></div><button type="button" data-writing-export hidden class="tai-writing-secondary tai-writing-export" title="Tải đề, bài đã chấm và góp ý của phiên bản đang xem. Không dùng credit.">@include('ai-tutor::partials.icon', ['name' => 'download', 'size' => 16]) <span>Tải báo cáo</span></button></div>
            <p data-writing-assessment-status role="status" aria-live="polite">Gửi bài để nhận góp ý theo từng tiêu chí.</p>
            <p data-writing-result-error hidden role="alert" class="tai-writing-result-error"></p>
            <div data-writing-scoreboard hidden class="tai-writing-scoreboard">
                <div class="tai-writing-ring" data-writing-ring><svg viewBox="0 0 120 120" aria-hidden="true"><circle class="tai-writing-ring-track" cx="60" cy="60" r="51"/><circle class="tai-writing-ring-value" data-writing-ring-value cx="60" cy="60" r="51" pathLength="100"/></svg><div><span data-writing-score-label>Điểm luyện tập</span><strong data-writing-overall>—</strong><small data-writing-score-scale>trên 100 điểm</small></div></div>
                <div data-writing-criteria class="tai-writing-criteria"><p class="tai-writing-muted">Điểm từng tiêu chí sẽ xuất hiện sau khi đánh giá.</p></div>
            </div>
            <p data-writing-score-note hidden class="tai-writing-score-note">Điểm AI mang tính tham khảo, không phải band IELTS chính thức.</p>
            <p data-writing-credit hidden class="tai-writing-credit"></p>
            <div data-writing-tabs hidden class="tai-writing-tabs" role="tablist" aria-label="Nội dung đánh giá">
                <button type="button" id="tai-writing-tab-overview" data-writing-tab="overview" role="tab" aria-selected="true" aria-controls="tai-writing-overview">Nhận xét</button>
                <button type="button" id="tai-writing-tab-details" data-writing-tab="details" role="tab" aria-selected="false" aria-controls="tai-writing-details" tabindex="-1">Sửa chi tiết <span data-writing-issue-count>0</span></button>
            </div>
            <section id="tai-writing-overview" data-writing-tab-panel="overview" role="tabpanel" aria-labelledby="tai-writing-tab-overview" tabindex="0">
                <div data-writing-feedback></div>
                <section data-writing-overview-corrections hidden class="tai-writing-overview-corrections">
                    <div data-writing-overview-issue-nav class="tai-writing-issue-nav"></div>
                    <div data-writing-overview-issues></div>

                </section>
                <div data-writing-empty class="tai-writing-empty"><span class="tai-writing-loading-symbol">@include('ai-tutor::partials.icon', ['name' => 'robot', 'size' => 40])<span class="tai-writing-loading-orbit" aria-hidden="true"></span></span><h3 data-writing-empty-title>Mỗi bản nháp là một bước tiến</h3><p data-writing-empty-description>Viết bài rồi gửi đánh giá để xem điểm từng tiêu chí, nhận xét và gợi ý sửa câu.</p><ol data-writing-assessment-progress hidden class="tai-writing-assessment-progress" aria-label="Tiến trình đánh giá"><li><span>1</span>Gửi bài</li><li><span>2</span>Chờ chấm</li><li><span>3</span>Phân tích</li></ol><ol data-writing-guide class="tai-writing-guide"><li><strong>Nhập đề bài</strong><span>Chọn chương trình và mục tiêu luyện tập.</span></li><li><strong>Viết bài của bạn</strong><span>Bản nháp tự lưu khi bạn ngừng gõ.</span></li><li><strong>Gửi đánh giá</strong><span>Nhận góp ý, sửa câu và chấm lại khi sẵn sàng.</span></li></ol></div>
            </section>
            <section id="tai-writing-details" data-writing-tab-panel="details" role="tabpanel" aria-labelledby="tai-writing-tab-details" tabindex="0" hidden>
                <p class="tai-writing-muted">Áp dụng từng gợi ý vào bản nháp, sau đó đọc lại để giữ đúng ý của bạn.</p>
                <div data-writing-issue-filters class="tai-writing-issue-filters" role="group" aria-label="Lọc gợi ý theo loại lỗi"></div>
                <div data-writing-issue-nav class="tai-writing-issue-nav" hidden></div>
                <div data-writing-issues></div>

                <details data-writing-original-panel hidden><summary>Bài được đánh giá · xem vị trí lỗi</summary><div data-writing-original class="tai-writing-original"></div></details>
            </section>
        </aside>
                <div data-writing-result-actions hidden class="tai-writing-result-actions">
                    <button type="button" data-writing-result-action="errors" class="tai-writing-secondary">@include('ai-tutor::partials.icon', ['name' => 'eye']) Xem lỗi trong bài</button>
                    <button type="button" data-writing-result-action="improve">@include('ai-tutor::partials.icon', ['name' => 'bulb']) <span>Gợi ý cải thiện</span></button>
                    <button type="button" data-writing-result-action="submit" disabled>@include('ai-tutor::partials.icon', ['name' => 'send']) Nộp lại bài</button>
                </div>
        </div>
    </div>
    <section data-writing-history-panel class="tai-writing-card tai-writing-history">
        <div class="tai-writing-row"><h2 class="tai-writing-history-heading">@include('ai-tutor::partials.icon', ['name' => 'history', 'size' => 20]) <span data-writing-history-title>Bản nháp của bạn</span></h2></div>
        <div data-writing-history></div>
        <div data-writing-history-pagination hidden class="tai-writing-actions"><button type="button" data-writing-history-prev class="tai-writing-secondary" disabled>Trang trước</button><button type="button" data-writing-history-next class="tai-writing-secondary" disabled>Trang sau</button></div>
    </section>
    <dialog data-writing-confirm-dialog class="tai-writing-credit-dialog tai-writing-confirm-dialog" aria-labelledby="tai-writing-confirm-title" aria-describedby="tai-writing-confirm-description">
        <span class="tai-writing-icon">@include('ai-tutor::partials.icon', ['name' => 'clipboard', 'size' => 24])</span>
        <h2 id="tai-writing-confirm-title" data-writing-confirm-title></h2>
        <p id="tai-writing-confirm-description" data-writing-confirm-description></p>
        <div class="tai-writing-confirm-actions">
            <button type="button" data-writing-confirm-cancel class="tai-writing-secondary" autofocus>Hủy</button>
            <button type="button" data-writing-confirm-accept>Xác nhận</button>
        </div>
    </dialog>
    <dialog data-writing-credit-dialog class="tai-writing-credit-dialog" aria-labelledby="tai-writing-credit-title" aria-describedby="tai-writing-credit-description">
        <span class="tai-writing-icon">@include('ai-tutor::partials.icon', ['name' => 'lock', 'size' => 24])</span>
        <h2 id="tai-writing-credit-title">Không đủ credit để chấm bài</h2>
        <p id="tai-writing-credit-description">Liên hệ quản trị viên để bổ sung credit, sau đó thử lại. Bản nháp và các chỉnh sửa của bạn vẫn được giữ.</p>
        <button type="button" data-writing-credit-close autofocus>Đã hiểu</button>
    </dialog>
    <noscript><p>Bật JavaScript để dùng editor và autosave. Không gửi bài qua form này khi JavaScript bị tắt.</p></noscript>
</section>
@endsection
