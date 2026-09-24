<!doctype html>
<html lang="vi" data-ai-tutor-theme="{{ in_array(config('ai-tutor.theme.default'), ['light', 'dark']) ? config('ai-tutor.theme.default') : 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>License Gia sư AI</title>
    @stack('styles')
</head>
<body data-ai-tutor-root class="tai-license-standalone">
    @yield(config('ai-tutor.license.admin_section', 'content'))
</body>
</html>
