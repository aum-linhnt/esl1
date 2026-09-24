<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gia sư AI</title>
    @if(config('ai-tutor.ui.asset_entries'))
        @vite(config('ai-tutor.ui.asset_entries'))
    @endif
</head>
<body data-ai-tutor-root class="tai-license-standalone" data-ai-tutor-theme="{{ config('ai-tutor.theme.default', 'system') }}">
    @yield('content')
</body>
</html>
