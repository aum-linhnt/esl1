<!doctype html>
<html lang="vi" data-learning-theme="dark" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $lesson->title }} · {{ config('app.name', 'ESL LMS') }}</title>
    @include('lessons.partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/study-time.js', 'resources/css/learning.css', 'resources/js/learning.js', 'resources/js/ai-tutor.js'])
</head>
<body class="learning-page">
<header class="learning-header">
    <a class="learning-brand" href="{{ route('courses.index') }}"><span class="learning-brand-icon">@include('ai-tutor::partials.icon', ['name' => 'book', 'size' => 38])</span><span>English<span>Up</span><small>Học tiếng Anh, mở ra thế giới</small></span></a>
    <span class="learning-header-caption">Không gian học tập của bạn</span>
    @include('ai-tutor::partials.learning-theme-toggle')
    <a class="learning-profile" href="{{ route('profile.edit') }}">@include('ai-tutor::partials.avatar', ['avatarRole' => 'learner'])<span>{{ auth()->user()->name }}<small>Học viên</small></span></a>
</header>
@yield('content')
</body>
</html>
