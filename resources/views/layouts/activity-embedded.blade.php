<!doctype html>
<html lang="vi" data-learning-theme="dark" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $activity->title }}</title>
    <base target="_top">
    @include('lessons.partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/learning.css', 'resources/js/learning.js'])
</head>
<body class="activity-embedded">
    @yield('content')
</body>
</html>
