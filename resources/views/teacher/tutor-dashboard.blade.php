<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <noscript><style>.tdash-filter-submit { display: inline-flex !important; }</style></noscript>
    <title>Quản lý Gia sư AI · EduLearn</title>
    @vite(['resources/scss/ai-tutor.scss', 'resources/js/ai-tutor.js'])
</head>
<body class="dv2 tdash" data-ai-tutor-root data-ai-tutor-theme="light">
<aside class="dv2-sidebar" aria-label="Điều hướng giảng dạy">
    <a class="dv2-brand" href="{{ route('dashboard.v2') }}">@include('dashboard-v2.icon', ['name' => 'book', 'size' => 44])<span>Edu<span>Learn</span><small>Học để tiến xa hơn</small></span></a>
    <nav>
        @foreach([['book', 'Tổng quan', route('dashboard.v2')], ['clipboard', 'Khóa học', route('courses.index')], ['users', 'Học viên', route('teacher.ai-tutor.index', ['section' => 'learners', 'course_id' => $course?->id])], ['robot', 'Gia sư AI', route('teacher.ai-tutor.index')], ['chart', 'Báo cáo', route('teacher.ai-tutor.index', ['course_id' => $course?->id]) . '#usage-chart']] as [$icon, $label, $href])
            <a href="{{ $href }}" title="{{ $label }}" aria-label="{{ $label }}" @if($label === 'Gia sư AI') class="is-active" aria-current="page" @endif>@include('dashboard-v2.icon', ['name' => $icon, 'size' => 23])<span>{{ $label }}</span></a>
        @endforeach
    </nav>
    <a class="dv2-back" href="{{ route('courses.index') }}" title="Trở về khóa học">@include('dashboard-v2.icon', ['name' => 'headphones'])<span>Cần hỗ trợ?<small>Trở về khóa học của bạn</small></span></a>
</aside>
<div class="dv2-workspace">
    <header class="dv2-topbar">
        <a class="dv2-search" href="{{ route('courses.index') }}">@include('dashboard-v2.icon', ['name' => 'search']) Tìm khóa học của bạn…</a>
        <button type="button" data-tai-theme class="dv2-theme" aria-label="Đổi giao diện sáng hoặc tối">@include('dashboard-v2.icon', ['name' => 'sun'])</button>
        <a class="dv2-profile" href="{{ route('profile.edit') }}"><img src="{{ $user->avatar_url }}" alt=""><span>{{ $user->name }}<small>{{ $user->isAdmin() ? 'Quản trị viên' : 'Giảng viên' }}</small></span>@include('dashboard-v2.icon', ['name' => 'chevron-down'])</a>
    </header>
    <main class="tdash-main">
        <div class="tdash-breadcrumb"><a href="{{ route('dashboard.v2') }}">Trang chủ</a><span>/</span><span>Gia sư AI</span><span>/</span><strong>Quản lý</strong></div>
        <div class="tdash-intro">
            <div><h1>Quản lý Gia sư AI</h1><p>Theo dõi hiệu quả, quản lý cấu hình và hỗ trợ học viên với trợ lý AI trong khóa học của bạn.</p></div>
            <form method="get" class="tdash-filters">
                <label>Khóa học<select name="course_id" aria-label="Khóa học" onchange="this.form.submit()">@forelse($courses as $option)<option value="{{ $option->id }}" @selected($course?->id === $option->id)>{{ $option->title }}</option>@empty<option>Chưa có khóa học</option>@endforelse</select></label>
                <label>Khoảng thời gian<select name="days" aria-label="Khoảng thời gian" onchange="this.form.submit()">@foreach([7,30,90] as $option)<option value="{{ $option }}" @selected($days === $option)>{{ $option }} ngày gần đây</option>@endforeach</select></label>
                <button class="tdash-button tdash-filter-submit" type="submit">Áp dụng</button>
                @if($course)<a class="tdash-button" href="{{ route('teacher.ai-tutor.index', ['course_id' => $course->id, 'days' => $days, 'export' => 'csv']) }}">@include('dashboard-v2.icon', ['name' => 'download', 'size' => 21]) Xuất báo cáo</a>@endif
            </form>
        </div>
        @if(!$course)
            <section class="dv2-card tdash-empty"><h2>Chưa có khóa học để quản lý</h2><p>Khóa học bạn tạo hoặc được phân công vai trò giáo viên/quản lý sẽ xuất hiện tại đây.</p><a href="{{ route('courses.index') }}">Xem khóa học →</a></section>
        @else
            @if(in_array($course->slug, ['demo-ai-tutor-management', 'demo-ai-tutor-ielts', 'demo-ai-tutor-ielts-foundation', 'demo-ai-tutor-ielts-band45', 'demo-ai-tutor-ielts-band55', 'demo-ai-tutor-ielts-band70', 'demo-ai-tutor-ielts-band75', 'demo-ai-tutor-toeic', 'demo-ai-tutor-toeic-four-skills', 'demo-ai-tutor-toeic-starter', 'demo-ai-tutor-toeic-foundation', 'demo-ai-tutor-toeic-intermediate', 'demo-ai-tutor-toeic-advanced', 'demo-ai-tutor-toeic-intensive', 'demo-ai-tutor-toeic-sw-starter', 'demo-ai-tutor-toeic-sw-foundation', 'demo-ai-tutor-toeic-sw-intermediate', 'demo-ai-tutor-toeic-sw-advanced', 'demo-ai-tutor-toeic-sw-intensive', 'demo-ai-tutor-cefr-a1', 'demo-ai-tutor-cefr-a2', 'demo-ai-tutor-cefr-b1', 'demo-ai-tutor-cefr-b2', 'demo-ai-tutor-cefr-c1', 'demo-ai-tutor-cefr-c2'], true))<div class="dv2-notice" role="status">Khóa học DEMO: học viên, kết quả, hội thoại và chi phí đều là dữ liệu mô phỏng để xem giao diện. @if(in_array($course->slug, ['demo-ai-tutor-ielts', 'demo-ai-tutor-ielts-foundation', 'demo-ai-tutor-ielts-band45', 'demo-ai-tutor-ielts-band55', 'demo-ai-tutor-ielts-band70', 'demo-ai-tutor-ielts-band75'], true)) Điểm phần trăm là điểm luyện tập, không phải band IELTS. @endif @if(in_array($course->slug, ['demo-ai-tutor-toeic', 'demo-ai-tutor-toeic-four-skills', 'demo-ai-tutor-toeic-starter', 'demo-ai-tutor-toeic-foundation', 'demo-ai-tutor-toeic-intermediate', 'demo-ai-tutor-toeic-advanced', 'demo-ai-tutor-toeic-intensive'], true)) Điểm phần trăm là điểm luyện tập, không phải điểm TOEIC chính thức. Mục tiêu L&amp;R áp dụng cho Listening &amp; Reading; Speaking &amp; Writing luyện riêng. @endif @if(in_array($course->slug, ['demo-ai-tutor-toeic-sw-starter', 'demo-ai-tutor-toeic-sw-foundation', 'demo-ai-tutor-toeic-sw-intermediate', 'demo-ai-tutor-toeic-sw-advanced', 'demo-ai-tutor-toeic-sw-intensive'], true)) Điểm phần trăm là điểm luyện tập, không phải điểm TOEIC chính thức. Mục tiêu Speaking và Writing được đặt riêng trên thang 0–200 cho mỗi kỹ năng. Bài Speaking demo dùng dàn ý văn bản, chưa có bản ghi âm. @endif @if(in_array($course->slug, ['demo-ai-tutor-cefr-a1', 'demo-ai-tutor-cefr-a2', 'demo-ai-tutor-cefr-b1', 'demo-ai-tutor-cefr-b2', 'demo-ai-tutor-cefr-c1', 'demo-ai-tutor-cefr-c2'], true)) Cấp CEFR là mục tiêu khóa học. Điểm phần trăm là điểm luyện tập mô phỏng, không xác nhận trình độ CEFR thực tế. Listening dùng transcript; Speaking dùng dàn ý văn bản, chưa có audio hoặc bản ghi âm. @endif</div>@endif
            @php
                $links = ['course_id' => $course->id, 'days' => $days];
                $questionChange = $report['previousQuestions'] ? round(($report['questions'] - $report['previousQuestions']) * 100 / $report['previousQuestions']) : null;
                $feedbackChange = $report['helpful'] !== null && $report['previousHelpful'] !== null ? $report['helpful'] - $report['previousHelpful'] : null;
                $selectedCurrency = $report['costs']->first()?->currency;
            @endphp
            @if(!$report['ready'])<p class="dv2-notice">Chưa cài đặt dữ liệu hội thoại AI Tutor. Thống kê lượt hỏi sẽ xuất hiện sau khi cài đặt.</p>@endif
            <div class="tdash-grid">
                <div class="tdash-primary">
                    <div class="tdash-stats">
                        <section class="dv2-card tdash-stat"><span class="tdash-stat-icon">@include('dashboard-v2.icon', ['name' => 'message', 'size' => 34])</span><div><strong>{{ $report['ready'] ? number_format($report['questions']) : '—' }}</strong><p>lượt hỏi</p><small>@if($questionChange !== null)<b class="tdash-positive">{{ $questionChange >= 0 ? '↑' : '↓' }} {{ abs($questionChange) }}%</b> so với {{ $days }} ngày trước @else Chưa có kỳ trước để so sánh @endif</small></div></section>
                        <section class="dv2-card tdash-stat"><span class="tdash-stat-icon tdash-green">@include('dashboard-v2.icon', ['name' => 'thumbs-up', 'size' => 34])</span><div><strong>{{ $report['helpful'] === null ? '—' : $report['helpful'].'%' }}</strong><p>phản hồi hữu ích</p><small>@if($feedbackChange !== null)<b class="{{ $feedbackChange >= 0 ? 'tdash-positive' : 'tdash-negative' }}">{{ $feedbackChange >= 0 ? '↑' : '↓' }} {{ abs($feedbackChange) }} điểm %</b> so với kỳ trước @else {{ $report['feedbackCount'] }} lượt đánh giá đã gửi @endif</small></div></section>
                        <section class="dv2-card tdash-stat"><span class="tdash-stat-icon">@include('dashboard-v2.icon', ['name' => 'users', 'size' => 36])</span><div><strong>{{ number_format($report['learners']->count()) }}</strong><p>học viên cần hỗ trợ</p><small>Dựa trên điểm khóa học đã lưu</small></div></section>
                    </div>
                    <section class="dv2-card tdash-chart" id="usage-chart">
                        <header class="tdash-card-header"><h2>@include('dashboard-v2.icon', ['name' => 'document']) Lượt sử dụng và chi phí</h2><div class="tdash-legend"><span><i></i>Lượt hỏi (cột)</span><span><i class="tdash-line-key"></i>Chi phí (đường)</span>
                            @if($report['costs']->isNotEmpty())<select data-tutor-currency aria-label="Tiền tệ biểu đồ">@foreach($report['costs'] as $cost)<option value="{{ $cost->currency }}">{{ $cost->currency }}</option>@endforeach</select>@endif
                        </div></header>
                        @include('teacher.tutor-chart', ['daily' => $report['daily'], 'currencies' => $report['costs']->pluck('currency')->all(), 'selectedCurrency' => $selectedCurrency])
                        <details class="tdash-cost-disclosure"><summary>Chi phí ước tính · {{ $report['costs']->sum('unpriced') }} lượt chưa có giá</summary>
                            <p class="tdash-note">Gồm lượt gọi Gia sư AI và truy xuất kiến thức liên quan. Chỉ cộng các lượt đã có giá; không quy đổi tiền tệ.</p>
                            <div class="tdash-cost-totals">@forelse($report['costs'] as $cost)<span><b>{{ $cost->currency }} {{ $cost->priced ? number_format((float) $cost->cost, 6, '.', ',') : '—' }}</b> · {{ $cost->unpriced }} lượt chưa có giá</span>@empty<span>Chưa có dữ liệu chi phí trong kỳ.</span>@endforelse</div>
                        </details>
                    </section>
                    <section class="dv2-card tdash-learners">
                        <header class="tdash-card-header"><h2>@include('dashboard-v2.icon', ['name' => 'users']) Học viên cần hỗ trợ</h2><a href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'learners']) }}#details">Xem tất cả</a></header>
                        <div class="tdash-table-wrap"><table><thead><tr><th>Học viên</th><th>Tiến độ học</th><th>Số lần hỏi</th><th>Điểm khóa học</th><th>Mức độ</th><th>Hành động</th></tr></thead><tbody>
                            @forelse($report['learners']->take(5) as $enrollment)
                                <tr><td><div class="tdash-person"><img src="{{ $enrollment->user->avatar_url }}" alt="">{{ $enrollment->user->name }}</div></td><td>{{ round($enrollment->progress_percentage ?? 0) }}% hoàn thành</td><td>{{ $report['questionCounts']->get($enrollment->user_id, 0) }}</td><td>{{ (float) $enrollment->final_grade }}%</td><td><span class="tdash-status {{ $enrollment->final_grade < ($course->passing_grade ?? 50) ? 'tdash-urgent' : '' }}">@include('dashboard-v2.icon', ['name' => 'alert', 'size' => 14]) {{ $enrollment->final_grade < ($course->passing_grade ?? 50) ? 'Cần hỗ trợ' : 'Cần theo dõi' }}</span></td><td><a class="tdash-outline" href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'learners', 'learner_id' => $enrollment->user_id]) }}#details">Xem chi tiết</a></td></tr>
                            @empty<tr><td colspan="6" class="tdash-empty">Chưa có học viên có điểm dưới {{ max(75, (float) ($course->passing_grade ?? 50)) }}%. Học viên chưa có điểm không được phân loại.</td></tr>@endforelse
                        </tbody></table></div>
                    </section>
                </div>
                <div class="tdash-secondary">
                    <section class="dv2-card tdash-popular">
                        <header class="tdash-card-header"><h2>@include('dashboard-v2.icon', ['name' => 'document']) Câu hỏi phổ biến</h2><a href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'questions']) }}#details">Xem tất cả</a></header>
                        <ol>@forelse($report['popular'] as $question)<li><span>{{ $loop->iteration }}</span><p title="{{ $question->user_content }}">{{ \Illuminate\Support\Str::limit($question->user_content, 90) }}</p><small>{{ number_format($question->total) }} lượt</small></li>@empty<li class="tdash-empty">Chưa có câu hỏi trong khoảng thời gian này.</li>@endforelse</ol>
                    </section>
                    <section class="dv2-card tdash-unanswered"><span class="tdash-error-icon">@include('dashboard-v2.icon', ['name' => 'alert', 'size' => 23])</span><div><h2>AI chưa trả lời được</h2><strong>{{ $report['ready'] ? $report['failed'] : '—' }}</strong><small>lượt xử lý lỗi trong kỳ</small></div><a class="tdash-outline" href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'errors']) }}#details">Xem để xử lý @include('dashboard-v2.icon', ['name' => 'chevron-right', 'size' => 18])</a></section>
                    <section class="dv2-card tdash-config" id="course-config">
                        <header class="tdash-card-header"><h2>@include('dashboard-v2.icon', ['name' => 'settings']) Cấu hình khóa học</h2></header>
                        <p>Tùy chỉnh cách hoạt động của Gia sư AI theo từng bài học.</p>
                        <div class="tdash-config-row">@include('dashboard-v2.icon', ['name' => 'check-circle'])<div><strong>Chính sách lời giải</strong><small>{{ $report['lessons']->where('ai_answer_policy', 'teacher_controlled')->where('ai_teacher_solution_allowed', true)->where('ai_exam_mode', false)->count() }} / {{ $report['lessons']->count() }} bài cho phép lời giải do giảng viên kiểm soát</small></div></div>
                        <div class="tdash-config-row">@include('dashboard-v2.icon', ['name' => 'check-circle'])<div><strong>Chế độ kiểm tra</strong><small>{{ $report['lessons']->where('ai_exam_mode', true)->count() }} / {{ $report['lessons']->count() }} bài bật chế độ không cung cấp đáp án</small></div></div>
                        @include('teacher.tutor-policy-form')
                        <div class="tdash-credit-note"><strong>Credit mỗi học viên</strong><small>Hạn mức theo tài khoản học viên; không đặt riêng theo khóa học.</small>@if(app(\TDSoft\AiTutor\Contracts\CreditAdministrator::class)->actorId() !== null)<a href="{{ route('ai-tutor.credits.index') }}">Quản lý hạn mức credit →</a>@endif</div>
                    </section>
                </div>
            </div>
            @if($section)
                <section class="dv2-card tdash-details" id="details">
                    <header class="tdash-card-header"><h2>{{ ['questions' => 'Các câu hỏi trong kỳ', 'errors' => 'Yêu cầu cần xử lý', 'learners' => 'Học viên trong khóa học'][$section] }}</h2><a href="{{ route('teacher.ai-tutor.index', $links) }}">Đóng danh sách</a></header>
                    @if($section === 'learners')
                        @if($learnerSupport)
                            @include('teacher.tutor-learner-support')
                        @else
                            <div class="tdash-table-wrap"><table><thead><tr><th>Học viên</th><th>Tiến độ</th><th>Điểm khóa học</th><th>Lượt hỏi trong kỳ</th><th>Hành động</th></tr></thead><tbody>@forelse($detail as $enrollment)<tr><td>{{ $enrollment->user?->name ?? 'Học viên đã xóa' }}</td><td>{{ round($enrollment->progress_percentage ?? 0) }}%</td><td>{{ $enrollment->final_grade === null ? 'Chưa có điểm' : (float) $enrollment->final_grade.'%' }}</td><td>{{ $report['questionCounts']->get($enrollment->user_id, 0) }}</td><td>@if($enrollment->user)<a class="tdash-outline" href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'learners', 'learner_id' => $enrollment->user_id]) }}#details">Xem chi tiết</a>@endif</td></tr>@empty<tr><td colspan="5">Không có học viên phù hợp trong khóa học.</td></tr>@endforelse</tbody></table></div>
                        @endif
                    @elseif($section === 'errors')
                        @include('teacher.tutor-failures')
                    @else
                        @forelse($detail ?? [] as $message)<article class="tdash-message"><time>{{ \Carbon\Carbon::parse($message->created_at, config('app.timezone'))->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</time><p>{{ $message->user_content }}</p><small>{{ $message->status }} @if($section === 'errors')· {{ $message->error_code }}@endif</small></article>@empty<p class="tdash-empty">Chưa có yêu cầu phù hợp.</p>@endforelse
                    @endif
                    @if($detail){{ $detail->links() }}@endif
                </section>
            @endif
        @endif
    </main>
</div>
</body>
</html>
