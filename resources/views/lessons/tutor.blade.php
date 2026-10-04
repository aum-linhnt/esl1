@extends('layouts.learning')
@section('content')
@php
    $tabs = ['lesson' => ['play', 'Bài học'], 'vocabulary' => ['cards', 'Từ vựng'], 'practice' => ['clipboard', 'Bài tập'], 'resources' => ['document', 'Tài liệu']];
    $percent = $totalActivities > 0 ? round($completedCount / $totalActivities * 100) : 0;
    $canPreviewAsStaff = $canPreviewAsStaff ?? (auth()->user()->isAdmin() || auth()->user()->isTeacher());
@endphp
<div class="learning-workspace" x-data="{ tab: @js($initialTab), tutorOpen: false }" @keydown.escape.window="tutorOpen = false" :class="{ 'tutor-is-open': tutorOpen }">
    <aside class="learning-sidebar" aria-label="Điều hướng khóa học">
        <a class="learning-course" href="{{ route('courses.show', $course->id) }}">@include('ai-tutor::partials.icon', ['name' => 'book', 'size' => 20]) <span>{{ $course->title }}</span></a>
        <div class="learning-progress"><progress value="{{ $completedCount }}" max="{{ max(1, $totalActivities) }}"></progress><strong data-lesson-percent>{{ $percent }}%</strong></div>
        <p class="learning-muted" data-lesson-progress data-total="{{ $totalActivities }}" data-completed="{{ json_encode(array_values($completedActivityIds)) }}">{{ $completedCount }}/{{ $totalActivities }} hoạt động hoàn thành trong bài</p>
        <nav class="learning-nav">
            <a href="{{ route('courses.show', $course->id) }}">@include('ai-tutor::partials.icon', ['name' => 'grid', 'size' => 20]) <span>Tổng quan khóa học</span></a>
            <a href="{{ route('lessons.show', $lesson->id) }}" aria-current="page">@include('ai-tutor::partials.icon', ['name' => 'book', 'size' => 20]) <span>Bài học hiện tại</span></a>
            <button type="button" @click="tab = 'practice'">@include('ai-tutor::partials.icon', ['name' => 'clipboard', 'size' => 20]) <span>Bài tập</span></button>
            <button type="button" @click="tab = 'vocabulary'">@include('ai-tutor::partials.icon', ['name' => 'cards', 'size' => 20]) <span>Từ vựng</span></button>
            <button type="button" @click="tab = 'resources'">@include('ai-tutor::partials.icon', ['name' => 'document', 'size' => 20]) <span>Tài liệu</span></button>
        </nav>
        <div class="learning-sidebar-note"><span>@include('ai-tutor::partials.icon', ['name' => 'sparkles', 'size' => 20]) HỌC CÙNG AI</span><h3>Mỗi câu hỏi là một bước tiến.</h3><p>Hỏi gia sư để hiểu bài, xem thêm ví dụ và luyện tập theo tốc độ của bạn.</p></div>
        <a class="learning-back" href="{{ route('courses.index') }}">@include('ai-tutor::partials.icon', ['name' => 'arrow-left', 'size' => 20]) Khám phá khóa học</a>
    </aside>
    <template data-completion-check>@include('ai-tutor::partials.icon', ['name' => 'check', 'size' => 20])</template>
    <main class="learning-main">
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px">@include('lessons.partials.layout-toggle', ['tutorLayout' => true])</div>
        <div class="learning-breadcrumb"><a href="{{ route('courses.show', $course->id) }}">{{ $course->title }}</a><span>@include('ai-tutor::partials.icon', ['name' => 'chevron-right', 'size' => 20])</span><span>Bài {{ $lesson->order }}</span></div>
        <div class="learning-title-row"><div><h1>{{ $lesson->title }}</h1>@if($lesson->description)<p>{{ $lesson->description }}</p>@endif</div>
            <nav class="learning-pagination" aria-label="Chuyển bài học">
                @if($prevLesson)<a href="{{ route('lessons.show', $prevLesson->id) }}" aria-label="Bài trước: {{ $prevLesson->title }}">@include('ai-tutor::partials.icon', ['name' => 'arrow-left', 'size' => 20]) <span>Bài trước</span></a>@endif
                @if($nextLesson)<a class="learning-primary" href="{{ route('lessons.show', $nextLesson->id) }}" aria-label="Bài tiếp theo: {{ $nextLesson->title }}"><span>Bài tiếp theo</span> @include('ai-tutor::partials.icon', ['name' => 'arrow-right', 'size' => 20])</a>@endif
            </nav>
        </div>
        @if($isTrialMode)<div class="learning-notice">@include('ai-tutor::partials.icon', ['name' => 'sparkles', 'size' => 20]) Bạn đang học thử. <a href="{{ route('courses.show', $course->id) }}">Ghi danh để mở nội dung và lưu tiến trình.</a></div>@endif
        @foreach(['success', 'error', 'info'] as $messageType)
            @if(session($messageType))<p class="learning-notice" role="status">{{ session($messageType) }}</p>@endif
        @endforeach
        <div class="learning-tabs" role="tablist" aria-label="Nội dung bài học">
            @foreach($tabs as $key => [$icon, $label])
                <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" :aria-selected="tab === '{{ $key }}'" :class="{ 'is-active': tab === '{{ $key }}' }" @click="tab = '{{ $key }}'"><span>@include('ai-tutor::partials.icon', ['name' => $icon])</span>{{ $label }}</button>
            @endforeach
        </div>
        @foreach($tabs as $key => [$icon, $label])
            <section id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" x-show="tab === '{{ $key }}'" @if($initialTab !== $key) x-cloak @endif>
                <div class="learning-activity-list">
                    @forelse($activityGroups->get($key, collect()) as $item)
                        @php $locked = !$canPreviewAsStaff && (($isTrialMode && !$item->is_free_trial) || !$item->isAvailable()); @endphp
                        <a @if($locked) aria-disabled="true" title="Hoạt động đang bị khóa" @endif class="learning-activity {{ $selectedActivity?->id === $item->id ? 'is-selected' : '' }}" @unless($locked) href="{{ route('lessons.show', ['lessonId' => $lesson->id, 'activity' => $item->id]) }}" @endunless @if($selectedActivity?->id === $item->id) aria-current="true" @endif>
                            <span data-completion-icon="{{ $item->id }}">@include('ai-tutor::partials.icon', ['name' => $locked ? 'lock' : (in_array($item->id, $completedActivityIds) ? 'check' : $icon)])</span><span>{{ $item->title }}</span><small>{{ $item->estimated_minutes ? $item->estimated_minutes.' phút' : '' }}</small>
                        </a>
                    @empty
                        <div class="learning-empty"><span>@include('ai-tutor::partials.icon', ['name' => $icon])</span><h2>Chưa có {{ mb_strtolower($label) }}</h2><p>Nội dung sẽ xuất hiện tại đây khi được bổ sung vào bài học.</p></div>
                    @endforelse
                </div>
                @if($selectedActivity && $initialTab === $key)
                    @if($canStudySelected)
                        <iframe class="learning-activity-frame" data-activity-frame data-activity-id="{{ $selectedActivity->id }}" src="{{ route('activities.show', ['activityId' => $selectedActivity->id, 'embedded' => 1]) }}" title="{{ $selectedActivity->title }}" allow="fullscreen; microphone" allowfullscreen></iframe>
                        <a class="learning-open-activity" href="{{ route('activities.show', $selectedActivity->id) }}">Mở hoạt động trong trang riêng @include('ai-tutor::partials.icon', ['name' => 'external', 'size' => 20])</a>
                    @else
                        <div class="learning-empty"><span>@include('ai-tutor::partials.icon', ['name' => 'lock', 'size' => 20])</span><h2>Hoạt động đang bị khóa</h2><p>Hoạt động yêu cầu ghi danh hợp lệ và phải trong thời gian được phép truy cập.</p><a class="learning-primary" href="{{ route('courses.show', $course->id) }}">Xem khóa học</a></div>
                    @endif
                @endif
            </section>
        @endforeach
        <div class="learning-summary-grid">
            <section><span>@include('ai-tutor::partials.icon', ['name' => 'target', 'size' => 20])</span><div><h2>Nội dung bài học</h2><p>{{ (!$isTrialMode || $lesson->is_free_trial || auth()->user()->isAdmin() || auth()->user()->isTeacher()) && $lesson->summary ? strip_tags($lesson->summary) : 'Hoàn thành các hoạt động trong bài và hỏi gia sư khi bạn cần giải thích thêm.' }}</p></div></section>
            <section><span>@include('ai-tutor::partials.icon', ['name' => 'check', 'size' => 20])</span><div><h2>Học theo tốc độ của bạn</h2><p>{{ $totalActivities }} hoạt động{{ $lesson->estimated_minutes ? ' · Khoảng '.$lesson->estimated_minutes.' phút' : '' }}. Tiến trình được ghi nhận theo điều kiện hoàn thành của từng hoạt động.</p></div></section>
        </div>
    </main>
    <aside class="learning-tutor" aria-label="Gia sư tiếng Anh AI">
        <button type="button" class="learning-tutor-close" @click="tutorOpen = false; $refs.tutorToggle.focus()">Đóng gia sư @include('ai-tutor::partials.icon', ['name' => 'close', 'size' => 20])</button>
        <x-ai-tutor::widget :course-id="(string) $course->id" :lesson-id="(string) $lesson->id" :embedded="true" />
        <div class="learning-tutor-unavailable"><span>@include('ai-tutor::partials.icon', ['name' => 'sparkles', 'size' => 20])</span><h2>Gia sư AI chưa khả dụng</h2><p>Gia sư sẽ xuất hiện khi tài khoản và bài học có quyền sử dụng tính năng này.</p></div>
    </aside>
    <button type="button" x-ref="tutorToggle" class="learning-tutor-toggle" @click="tutorOpen = !tutorOpen" :aria-expanded="tutorOpen">@include('ai-tutor::partials.icon', ['name' => 'sparkles', 'size' => 20]) Hỏi gia sư AI</button>
</div>
@endsection
