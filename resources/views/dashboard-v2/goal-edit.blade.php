<!doctype html>
<html lang="vi"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mục tiêu học tập · EnglishUp</title>
    @vite(['resources/scss/ai-tutor.scss', 'resources/js/ai-tutor.js'])
</head><body class="dv2" data-ai-tutor-root data-ai-tutor-theme="light">
<main class="dv2-goal-page"><a href="{{ route('dashboard.v2') }}">← Dashboard v2</a><section class="dv2-card"><header class="dv2-card-heading"><span class="dv2-heading-icon">@include('dashboard-v2.icon', ['name' => 'target'])</span><h1>Mục tiêu học tập</h1></header>@include('dashboard-v2.goal-form')</section></main>
</body></html>
