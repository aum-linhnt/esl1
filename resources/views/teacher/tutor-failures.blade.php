<p class="tdash-failure-intro">Các lượt xử lý lỗi trong {{ $days }} ngày đang chọn. Hướng dẫn dựa trên mã lỗi đã lưu, chưa phải kết luận nguyên nhân cuối cùng.</p>
<div class="tdash-failure-summary" aria-label="Loại lỗi trong khóa học">
    @foreach($report['errorSummary'] as $item)
        <a href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'errors', 'error_code' => $item->error_code]) }}#details" @if($errorCode === $item->error_code && $errorCode !== null) aria-current="true" @endif><span>{{ $item->guide['title'] }}</span><b>{{ $item->total }}</b></a>
    @endforeach
</div>
<form method="get" action="{{ route('teacher.ai-tutor.index') }}#details" class="tdash-failure-filters">
    <input type="hidden" name="course_id" value="{{ $course->id }}"><input type="hidden" name="days" value="{{ $days }}"><input type="hidden" name="section" value="errors">
    <label>Bài học<select name="error_lesson_id" aria-label="Lọc lỗi theo bài học"><option value="">Tất cả bài học</option>@foreach($report['lessons'] as $lesson)<option value="{{ $lesson->id }}" @selected($errorLessonId === $lesson->id)>{{ $lesson->title }}</option>@endforeach</select></label>
    <label>Loại lỗi<select name="error_code" aria-label="Lọc theo mã lỗi"><option value="">Tất cả loại lỗi</option>@foreach($report['errorSummary'] as $item)@if($item->error_code)<option value="{{ $item->error_code }}" @selected($errorCode === $item->error_code)>{{ $item->guide['title'] }} · {{ $item->error_code }}</option>@endif @endforeach</select></label>
    <button type="submit" class="tdash-button">Lọc yêu cầu</button>
    <a class="tdash-outline" href="{{ route('teacher.ai-tutor.index', [...$links, 'section' => 'errors']) }}#details">Xóa bộ lọc</a>
</form>
<p class="tdash-failure-count">{{ $detail?->total() ?? 0 }} yêu cầu phù hợp · Các nhóm lỗi phía trên tính cho toàn khóa học trong kỳ.</p>
@forelse($detail ?? [] as $message)
    <article class="tdash-failure-card">
        <header><span class="tdash-error-icon">@include('dashboard-v2.icon', ['name' => 'alert', 'size' => 20])</span><div><h3>{{ $message->guide['title'] }}</h3><time>{{ \Carbon\Carbon::parse($message->created_at, config('app.timezone'))->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</time></div></header>
        <p class="tdash-failure-question">{{ $message->user_content }}</p>
        <p class="tdash-failure-lesson">Bài học: <strong>{{ $message->lesson?->title ?? 'Bài học không còn trong khóa học' }}</strong></p>
        <div class="tdash-failure-advice">@include('dashboard-v2.icon', ['name' => 'seedling', 'size' => 19])<p>{{ $message->guide['advice'] }}</p></div>
        <div class="tdash-failure-actions">
            @if($message->lesson)
                <a class="tdash-outline" href="{{ route('lessons.show', $message->lesson->id) }}">Xem bài học</a>
                <a class="tdash-outline" href="{{ route('teacher.ai-tutor.index', [...$links, 'lesson_id' => $message->lesson->id]) }}#course-config">Cấu hình AI bài học</a>
            @endif
            @if($message->guide['action'] === 'credits' && app(\TDSoft\AiTutor\Contracts\CreditAdministrator::class)->actorId() !== null)<a class="tdash-outline" href="{{ route('ai-tutor.credits.index') }}">Kiểm tra credit</a>@endif
            @if($message->guide['action'] === 'knowledge' && app(\TDSoft\AiTutor\Contracts\KnowledgeAdministrator::class)->allows())<a class="tdash-outline" href="{{ route('ai-tutor.knowledge') }}">Quản lý kiến thức</a>@endif
            @if($message->guide['action'] === 'settings' && $user->isAdmin())<a class="tdash-outline" href="{{ route('admin.ai.usage') }}">Kiểm tra báo cáo AI</a>@endif
        </div>
        <details class="tdash-failure-code"><summary>Mã lỗi để gửi quản trị viên</summary><code>{{ $message->error_code ?? 'Chưa có mã lỗi' }}</code></details>
    </article>
@empty
    <p class="tdash-empty">Không có yêu cầu lỗi phù hợp với bộ lọc này.</p>
@endforelse
