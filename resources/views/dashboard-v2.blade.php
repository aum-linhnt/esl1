<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tổng quan học tập · EnglishUp</title>
    @vite(['resources/scss/ai-tutor.scss', 'resources/js/ai-tutor.js'])
</head>
<body class="dv2" data-ai-tutor-root data-ai-tutor-theme="light">
@php
    $levels = ['A1' => 'Người mới bắt đầu', 'A2' => 'Sơ cấp', 'B1' => 'Trung cấp', 'B2' => 'Trung cao cấp'];
    if (in_array($user->current_level, ['C1', 'C2'])) $levels += ['C1' => 'Cao cấp', 'C2' => 'Thành thạo'];
    $currentLevel = $user->current_level ?: 'A1';
    $levelEnrollment = $enrollments->filter(fn ($enrollment) => $enrollment->course->level === $currentLevel);
    $levelProgress = $levelEnrollment->isNotEmpty() ? (int) round($levelEnrollment->avg('progress_percentage')) : null;
    $teacherTutorUrl = ($user->isTeacher() || $user->isAdmin()) ? route('teacher.ai-tutor.index') : null;
    $tutorUrl = $nextLesson ? route('ai-tutor.page', ['lesson_id' => $nextLesson->id]) : route('courses.index');
    $skillDefinitions = ['listening' => ['Listening', 'headphones', 'green'], 'speaking' => ['Speaking', 'mic', 'purple'], 'reading' => ['Reading', 'book', 'blue'], 'writing' => ['Writing', 'pencil', 'orange']];

@endphp
<aside class="dv2-sidebar" aria-label="Điều hướng học tập">
    <a class="dv2-brand" aria-label="EnglishUp — Tổng quan học tập" href="{{ route('dashboard.v2') }}">@include('dashboard-v2.icon', ['name' => 'book', 'size' => 28]) <span>English<span>Up</span><small>Không gian học tập</small></span></a>
    <nav>
        @foreach([
            ['home', 'Tổng quan', route('dashboard.v2')],
            ['book', 'Khóa học của tôi', route('enrollments.myCourses')],
            ['robot', 'Gia sư AI', $teacherTutorUrl ?? $tutorUrl],
            ['mic', 'Luyện nói', route('ai.speaking.index')],
            ['pencil', 'Luyện viết', route('ai-tutor.writing.index')],
            ['clipboard', 'Luyện tập', route('practice.index')],
            ['chart', 'Kết quả', route('progress.index')],
        ] as [$icon, $label, $href])
            <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" @if($loop->first) class="is-active" aria-current="page" @endif>@include('dashboard-v2.icon', ['name' => $icon, 'size' => 25]) <span>{{ $label }}</span></a>
        @endforeach
    </nav>
    <a class="dv2-back" aria-label="Quay lại dashboard cũ" title="Dashboard cũ" href="{{ route('dashboard') }}">@include('dashboard-v2.icon', ['name' => 'arrow-left'])<span>Dashboard cũ<small>Quay lại giao diện trước</small></span></a>
</aside>
<div class="dv2-workspace">
    <header class="dv2-topbar">
        <a class="dv2-search" href="{{ route('courses.index') }}">@include('dashboard-v2.icon', ['name' => 'search']) Khám phá khóa học và bài học…</a>
        <button type="button" data-tai-theme class="dv2-theme" aria-label="Đổi giao diện sáng hoặc tối">@include('dashboard-v2.icon', ['name' => 'sun'])</button>
        <a class="dv2-profile" href="{{ route('profile.edit') }}"><img src="{{ $user->avatar_url }}" alt=""><span>{{ $user->name }}<small>Học viên</small></span>@include('dashboard-v2.icon', ['name' => 'chevron-down'])</a>
    </header>
    <main class="dv2-main">
        @if($user->isTrialExpired())
            <div class="dv2-notice">Tài khoản học thử đã hết hạn. <a href="{{ route('renew') }}">Gia hạn tài khoản →</a></div>
        @endif
        @if(session('learning-goal-saved'))<div class="dv2-goal-success" role="status">@include('dashboard-v2.icon', ['name' => 'check']) Đã lưu mục tiêu học tập. Đề xuất đã được cập nhật theo mục tiêu của bạn.</div>@endif
        <div class="dv2-welcome">
            <div><h1>Lộ trình học tiếng Anh</h1><p>Chào {{ $user->name }}, cùng tiếp tục mục tiêu hôm nay nhé! 👋</p></div>
            <blockquote><b>“</b><span><em>Every small step brings you closer to a better you.</em><small>— Keep going! ✨</small></span></blockquote>
            <div class="dv2-tutor dv2-card"><a class="dv2-tutor-link" href="{{ $tutorUrl }}"><span class="dv2-mascot">@include('dashboard-v2.icon', ['name' => 'robot', 'size' => 45])</span><span><strong>Gia sư AI</strong><small><i></i>{{ $nextLesson ? 'Hỗ trợ bạn trong bài học' : 'Chọn bài học để bắt đầu' }}</small></span></a>
                <a class="dv2-credit" href="{{ route('dashboard.v2.credits') }}">@include('dashboard-v2.icon', ['name' => 'trophy', 'size' => 17]) {{ isset($creditAccount) ? ($creditAccount['status'] !== 'active' ? 'Credit tạm khóa' : ($creditAccount['balance'] === null ? 'Credit không giới hạn' : number_format($creditAccount['balance']).' credit')) : 'Xem credit' }} @include('dashboard-v2.icon', ['name' => 'chevron-right', 'size' => 14])</a>
            </div>
        </div>
        <div class="dv2-roadmap-row">
            <section class="dv2-card">
                <header class="dv2-card-heading"><span class="dv2-heading-icon">@include('dashboard-v2.icon', ['name' => 'target'])</span><div><h2>Lộ trình CEFR của bạn</h2><p>Học theo chuẩn quốc tế, từng bước chinh phục tiếng Anh</p></div></header>
                <div class="dv2-levels">
                    @foreach($levels as $level => $label)
                        <div class="dv2-level {{ $level === $currentLevel ? 'is-current' : '' }}"><span class="dv2-level-circle">{{ $level }}</span><strong>{{ $label }}</strong>
                            @if($level === $currentLevel)
                                @if($levelProgress !== null)<div class="dv2-level-progress"><progress max="100" value="{{ $levelProgress }}" aria-label="Tiến độ khóa học {{ $level }}"></progress><b>{{ $levelProgress }}%</b></div>@else<small>Trình độ hiện tại</small>@endif
                            @else<small>{{ array_search($level, array_keys($levels)) < array_search($currentLevel, array_keys($levels)) ? 'Chặng trước' : 'Chặng tiếp theo' }}</small>@endif
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="dv2-card">
                <header class="dv2-card-heading"><span class="dv2-heading-icon">@include('dashboard-v2.icon', ['name' => 'trophy'])</span><div><h2>Mục tiêu chứng chỉ</h2><p>Khám phá bài luyện cho mục tiêu của bạn</p></div></header>
                <div class="dv2-certificates">
                    @foreach(['toeic' => ['TOEIC 750+', 'toeic', 'Phù hợp cho công việc', 'và cơ hội nghề nghiệp'], 'ielts' => ['IELTS 6.5', 'ielts', 'Phục vụ du học,', 'định cư và học thuật']] as $framework => [$label, $logo, $line1, $line2])
                        <a href="{{ route('dashboard.v2.goals.edit', ['framework' => $framework]) }}" data-goal-open="{{ $framework }}" class="{{ $goal?->framework === $framework ? 'is-selected' : '' }}" @if($goal?->framework === $framework) aria-current="true" @endif><span class="dv2-certificate-indicator" aria-hidden="true"></span><strong>{{ $goal?->framework === $framework ? $goal->label() : $label }}</strong><b class="dv2-{{ $logo }}">{{ strtoupper($logo) }}@if($logo === 'toeic')<span>↗</span>@endif</b><p>{{ $line1 }}<br>{{ $line2 }}</p><small>{{ $goal?->framework === $framework ? 'Mục tiêu đang chọn' : 'Chọn mục tiêu' }}</small></a>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="dv2-middle-row">
            <section class="dv2-card" id="skills">
                <header class="dv2-card-heading"><span class="dv2-heading-icon">@include('dashboard-v2.icon', ['name' => 'chart'])</span><div><h2>Năng lực hiện tại</h2><p>Dựa trên kết quả đánh giá đã lưu</p></div><a href="{{ route('dashboard.v2.skills') }}">Lịch sử đánh giá →</a></header>
                <div class="dv2-skills">
                    @foreach($skillDefinitions as $key => [$label, $icon, $color])
                        @php($skill = $skills->get($key))
                        <div class="dv2-skill dv2-tone-{{ $color }}"><span class="dv2-skill-icon">@include('dashboard-v2.icon', ['name' => $icon, 'size' => 28])</span><div><div class="dv2-skill-score"><strong title="{{ $skill->score_label ?? $label }}">{{ $label }}</strong><span>@if($skill)<b>{{ $skill->mastery_score + 0 }}</b>/{{ $skill->score_max ?? 100 }} @else<small>Chưa đánh giá</small>@endif</span></div><progress max="{{ $skill->score_max ?? 100 }}" value="{{ $skill ? max(0, min($skill->score_max ?? 100, $skill->mastery_score)) : 0 }}" aria-label="Điểm {{ $label }}"></progress>@if(isset($skill->score_label))<small class="dv2-score-source">{{ $skill->score_label }}</small>@endif</div></div>
                    @endforeach
                </div>
            </section>
            <section class="dv2-card dv2-next">
                <header class="dv2-card-heading"><span class="dv2-heading-icon">@include('dashboard-v2.icon', ['name' => 'document'])</span><h2>Bài học tiếp theo</h2><a href="{{ route('enrollments.myCourses') }}">Xem lộ trình →</a></header>
                <div class="dv2-next-body"><div class="dv2-lesson-art" aria-hidden="true">@include('dashboard-v2.icon', ['name' => 'book', 'size' => 56])<span>ENGLISH<br>EVERY DAY</span></div><div><small class="dv2-tag">{{ $nextLesson?->course->level ?? 'Bắt đầu học' }}</small><h3>{{ $nextLesson?->title ?? 'Sẵn sàng cho bài học đầu tiên?' }}</h3><p>{{ $nextLesson ? $nextLesson->course->title : 'Chọn một khóa học và ghi danh để xây dựng lộ trình của bạn.' }}</p>@if($nextLesson)<small>{{ $nextLesson->estimated_minutes ?: '—' }} phút · Học theo lộ trình</small>@endif</div></div>
                <a class="dv2-primary" href="{{ $nextLesson ? route('lessons.show', $nextLesson->id) : route('courses.index') }}">@include('dashboard-v2.icon', ['name' => 'play']) {{ $nextLesson ? 'Tiếp tục học' : 'Khám phá khóa học' }}</a>
            </section>
            <section class="dv2-card">
                <header class="dv2-card-heading"><span class="dv2-heading-icon">@include('dashboard-v2.icon', ['name' => 'sparkles'])</span><div><h2>Đề xuất cho bạn</h2><p>Dựa trên kết quả và hoạt động học của bạn</p></div></header>
                <div class="dv2-recommendations">
                    @foreach($recommendations as $recommendation)
                        <a class="dv2-tone-{{ $recommendation['color'] }}" href="{{ $recommendation['url'] }}"><span class="dv2-skill-icon">@include('dashboard-v2.icon', ['name' => $recommendation['icon']])</span><span><strong>{{ $recommendation['title'] }}</strong><small>{{ $recommendation['reason'] }}</small></span>@include('dashboard-v2.icon', ['name' => 'chevron-right'])</a>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="dv2-bottom-row">
            <section class="dv2-card">
                <header class="dv2-card-heading"><span class="dv2-heading-icon">@include('dashboard-v2.icon', ['name' => 'trending-up'])</span><div><h2>Tiến độ học tập trong 7 ngày qua</h2><p>Thời gian học được ghi nhận mỗi ngày (phút)</p></div><a href="{{ route('dashboard.v2.study') }}">Xem chi tiết →</a></header>
                @php($chartMax = max(30, (int) (ceil($studyDays->max('minutes') / 30) * 30)))
                <div class="dv2-chart" role="img" aria-label="Thời gian học 7 ngày: {{ $studyDays->map(fn ($day) => $day['date'].': '.$day['minutes'].' phút')->implode(', ') }}">
                    <div class="dv2-chart-axis">@foreach([$chartMax, $chartMax / 2, 0] as $tick)<span>{{ $tick }}</span>@endforeach</div>
                    <div class="dv2-chart-bars">@foreach($studyDays as $day)<div class="dv2-chart-column"><div class="dv2-chart-track"><span class="dv2-chart-bar" style="height: {{ $day['minutes'] / $chartMax * 100 }}%"><b>{{ $day['minutes'] }}</b></span></div><small>{{ $day['label'] }}</small><time>{{ $day['date'] }}</time></div>@endforeach</div>
                </div>
                @if($studyDays->sum('seconds') === 0)<p class="dv2-chart-empty">Chưa ghi nhận thời gian học trong 7 ngày qua.</p>@endif
            </section>
            <section class="dv2-card dv2-streak"><header class="dv2-card-heading"><span class="dv2-flame">@include('dashboard-v2.icon', ['name' => 'flame', 'size' => 28])</span><h2>Chuỗi ngày học liên tiếp</h2></header><strong class="dv2-streak-count">@include('dashboard-v2.icon', ['name' => 'flame', 'size' => 34]) {{ $streak }} <span>ngày liên tiếp</span></strong><p>{{ $streak ? 'Tuyệt vời! Hãy giữ vững thói quen này để đạt mục tiêu sớm hơn nhé!' : 'Hoàn thành một hoạt động hôm nay để bắt đầu chuỗi ngày học của bạn.' }}</p><div class="dv2-week">@foreach($weekDays as $day)<div><span class="{{ $day['active'] ? 'is-active' : '' }} {{ $day['today'] ? 'is-today' : '' }}">{{ $day['active'] ? '✓' : ($day['today'] ? '★' : '·') }}</span><small>{{ $day['label'] }}</small></div>@endforeach</div></section>
            <section class="dv2-card dv2-goals">
                <header class="dv2-card-heading"><span class="dv2-trophy">@include('dashboard-v2.icon', ['name' => 'trophy', 'size' => 28])</span><h2>Mục tiêu của bạn</h2><a href="{{ route('dashboard.v2.goals.edit') }}" data-goal-open>Chỉnh sửa ↗</a></header>
                <div class="dv2-goal"><span class="dv2-skill-icon dv2-tone-purple">@include('dashboard-v2.icon', ['name' => 'target'])</span><div><strong>{{ $goal ? 'Chinh phục '.$goal->label() : ($user->target_level ? 'Chinh phục CEFR '.$user->target_level : 'Đặt mục tiêu học tập') }}</strong><small>{{ $goal?->target_date ? 'Dự kiến: '.$goal->target_date->format('d/m/Y') : 'Chọn thời hạn cho mục tiêu của bạn' }}</small>@if($goal?->target_date && $goal->target_date->lt(today()))<small class="dv2-goal-overdue">Đã qua thời hạn · Hãy cập nhật kế hoạch</small>@endif</div></div>
                @if($levelProgress !== null)<div class="dv2-goal"><span class="dv2-skill-icon dv2-tone-blue">@include('dashboard-v2.icon', ['name' => 'flag'])</span><div><strong>Hoàn thành khóa học {{ $currentLevel }}</strong><div class="dv2-level-progress"><progress max="100" value="{{ $levelProgress }}" aria-label="Tiến độ khóa học"></progress><b>{{ $levelProgress }}%</b></div></div></div>@endif
                <blockquote>@include('dashboard-v2.icon', ['name' => 'seedling', 'size' => 28]) <em>“Kỷ luật hôm nay,<br>kết quả ngày mai.”</em></blockquote>
            </section>
        </div>
    </main>
</div>
<dialog class="dv2-goal-dialog" data-has-errors="{{ $errors->learningGoal->any() ? 'true' : 'false' }}" aria-labelledby="dv2-goal-dialog-title">
    <header><h2 id="dv2-goal-dialog-title">Mục tiêu học tập</h2><button type="button" data-goal-close aria-label="Đóng mục tiêu học tập">@include('dashboard-v2.icon', ['name' => 'close'])</button></header>
    @include('dashboard-v2.goal-form')
</dialog>
</body>
</html>
