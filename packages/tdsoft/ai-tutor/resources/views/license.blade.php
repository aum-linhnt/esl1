<!doctype html>
<html lang="vi" data-ai-tutor-theme="{{ in_array(config('ai-tutor.theme.default'), ['light', 'dark']) ? config('ai-tutor.theme.default') : 'system' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>License Gia sư AI</title>
    @if(config('ai-tutor.license.asset_entries'))
        @vite(config('ai-tutor.license.asset_entries'))
    @endif
</head>
<body data-ai-tutor-root>
<main class="tai-license">
    <a href="{{ url('/admin') }}">← Quản trị</a>
    <h1>License Gia sư AI</h1>
    @if(session('license_notice'))<p role="status">{{ session('license_notice') }}</p>@endif
    @if(session('license_error'))<p role="alert">{{ session('license_error') }}</p>@endif
    @if(!$migrationReady)<p role="alert">Cần chạy migration license trước khi kích hoạt.</p>@endif
    @if(!$configured)<p>Chưa cấu hình License Server. Bạn vẫn có thể khởi tạo mã cài đặt. Chỉ license có chữ ký hợp lệ mới mở quyền sử dụng AI.</p>@endif
    @if($license->warning)<p role="alert">License sắp hết thời gian được phép hoạt động hoặc đang dùng thời gian grace. Vui lòng kiểm tra kết nối và thời hạn.</p>@endif
    <section>
        <h2>Trạng thái</h2>
        <dl>
            <dt>License</dt><dd>{{ $license->state }}</dd>
            <dt>Mã cài đặt</dt><dd>{{ $installationId ?? 'Chưa khởi tạo' }}</dd>
            <dt>Domain</dt><dd>{{ $domain ?? 'Chưa cấu hình' }}</dd>
            <dt>Xác nhận thành công gần nhất</dt><dd>{{ $lastRefreshedAt ?? 'Chưa có' }}</dd>
            <dt>Refresh tiếp theo</dt><dd>{{ $nextAttemptAt ?? 'Chưa có' }}</dd>
            <dt>Hạn license</dt><dd>{{ $license->document['expires_at'] ?? 'Chưa có' }}</dd>
            <dt>Hạn hoạt động offline</dt><dd>{{ $license->offlineUntil ?? 'Chưa có' }}</dd>
            <dt>Giới hạn học viên trong license</dt><dd>{{ $license->document['student_limit'] ?? 'Chưa có' }}</dd>
            <dt>Lỗi gần nhất</dt><dd>{{ $license->errorCode ?? $lastError ?? 'Không có' }}</dd>
        </dl>
        <h3>Module được cấp</h3>
        <ul>@forelse(($license->document['modules'] ?? []) as $module)<li>{{ $module }}</li>@empty<li>Chưa có module.</li>@endforelse</ul>
    </section>
    @if($migrationReady)
        @if(!$installationId)
            <form method="post" action="{{ route('ai-tutor.license.initialize') }}">@csrf<button type="submit">Khởi tạo mã cài đặt</button></form>
        @endif
        <section>
            <h2>Kích hoạt license</h2>
            <form method="post" action="{{ route('ai-tutor.license.activate') }}" autocomplete="off">
                @csrf
                <label for="license_key">Mã license</label>
                <input id="license_key" name="license_key" type="password" maxlength="512" required autocomplete="new-password">
                <button type="submit" @disabled(!$configured)>Kích hoạt</button>
            </form>
            <form method="post" action="{{ route('ai-tutor.license.refresh') }}">
                @csrf<button type="submit" @disabled(!$configured || !$license->document)>Refresh license</button>
            </form>
        </section>
    @endif
    <section>
        <h2>Lịch sử kiểm tra gần nhất</h2>
        <ul>@forelse($attempts as $attempt)
            <li>{{ $attempt->started_at }} — {{ $attempt->operation }} — {{ $attempt->status }} @if($attempt->error_code)({{ $attempt->error_code }})@endif</li>
        @empty<li>Chưa có lần kiểm tra nào.</li>@endforelse</ul>
    </section>
</main>
</body>
</html>
