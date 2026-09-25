@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'License Gia sư AI'])

@push('styles')
    @if(config('ai-tutor.license.asset_entries'))
        @vite(config('ai-tutor.license.asset_entries'))
    @endif
@endpush

@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license tai-license-server" data-ai-tutor-root data-ai-tutor-theme="{{ in_array(config('ai-tutor.license.admin_theme') ?? config('ai-tutor.theme.default'), ['light', 'dark']) ? (config('ai-tutor.license.admin_theme') ?? config('ai-tutor.theme.default')) : 'system' }}">
    <header class="tai-license-server__header">
        <div><span class="tai-license-server__eyebrow">GIA SƯ AI / BẢN QUYỀN</span><h1>License Gia sư AI</h1><p>Quản lý kích hoạt, thời hạn và phạm vi sử dụng AI.</p></div>
        <span class="tai-license-server__status {{ $configured ? 'is-ready' : '' }}"><i aria-hidden="true"></i>{{ $configured ? 'Đã cấu hình' : 'Chưa cấu hình' }}</span>
    </header>
    @if(session('license_notice'))<p role="status">{{ session('license_notice') }}</p>@endif
    @if(session('license_error'))<p role="alert">{{ session('license_error') }}</p>@endif
    @if(!$migrationReady)<p role="alert">Cần chạy migration license trước khi kích hoạt.</p>@endif
    @if(!$configured)<div class="tai-license-server__banner"><span aria-hidden="true">!</span><div><strong>Chưa cấu hình License Server</strong><p>Bạn vẫn có thể khởi tạo mã cài đặt. Chỉ license có chữ ký hợp lệ mới mở quyền sử dụng AI.</p></div></div>@endif
    @if($license->warning)<p role="alert">License sắp hết thời gian được phép hoạt động hoặc đang dùng thời gian grace. Vui lòng kiểm tra kết nối và thời hạn.</p>@endif
    <section class="tai-license-server__overview">
        <div class="tai-license-server__section-title"><span aria-hidden="true">⌁</span><div><h2>Trạng thái license</h2><p>Thông tin nhận dạng và thời hạn hiện tại.</p></div></div>
        <div class="tai-license-server__state"><span>Trạng thái hiện tại</span><strong>{{ $license->state }}</strong></div>
        <dl class="tai-license-server__facts">
            <dt>Mã cài đặt</dt><dd>{{ $installationId ?? 'Chưa khởi tạo' }}</dd>
            <dt>Domain</dt><dd>{{ $domain ?? 'Chưa cấu hình' }}</dd>
            <dt>Xác nhận thành công gần nhất</dt><dd>{{ $lastRefreshedAt ?? 'Chưa có' }}</dd>
            <dt>Refresh tiếp theo</dt><dd>{{ $nextAttemptAt ?? 'Chưa có' }}</dd>
            <dt>Hạn license</dt><dd>{{ $license->document['expires_at'] ?? 'Chưa có' }}</dd>
            <dt>Hạn hoạt động offline</dt><dd>{{ $license->offlineUntil ?? 'Chưa có' }}</dd>
            <dt>Giới hạn học viên trong license</dt><dd>{{ $license->document['student_limit'] ?? 'Chưa có' }}</dd>
            <dt>Lỗi gần nhất</dt><dd class="{{ ($license->errorCode ?? $lastError) ? 'is-error' : '' }}">{{ $license->errorCode ?? $lastError ?? 'Không có' }}</dd>
        </dl>
        <div class="tai-license-server__modules"><h3>Module được cấp</h3><div>@forelse(($license->document['modules'] ?? []) as $module)<span>{{ $module }}</span>@empty<p>Chưa có module nào được cấp.</p>@endforelse</div></div>
    </section>
    @if($migrationReady)
        @if(!$installationId)
            <form class="tai-license-server__initialize" method="post" action="{{ route('ai-tutor.license.initialize') }}">@csrf<button type="submit">Khởi tạo mã cài đặt <span aria-hidden="true">→</span></button></form>
        @endif
        <section class="tai-license-server__activation">
            <div class="tai-license-server__section-title"><span aria-hidden="true">⌘</span><div><h2>Kích hoạt license</h2><p>Nhập mã do License Server phát hành cho cài đặt này.</p></div></div>
            <form method="post" action="{{ route('ai-tutor.license.activate') }}" autocomplete="off">
                @csrf
                <label for="license_key">Mã license <small>Mã được bảo mật và không hiển thị lại.</small></label>
                <input id="license_key" name="license_key" type="password" maxlength="512" required autocomplete="new-password">
                <button type="submit" @disabled(!$configured)>Kích hoạt license</button>
                @if(!$configured)<p class="tai-license-server__hint">Cần cấu hình địa chỉ License Server trước khi kích hoạt.</p>@endif
            </form>
            <form method="post" action="{{ route('ai-tutor.license.refresh') }}">
                @csrf<button class="tai-license-server__secondary" type="submit" @disabled(!$configured || !$license->document)>Đồng bộ trạng thái</button>
            </form>
        </section>
    @endif
    <section class="tai-license-server__history">
        <div class="tai-license-server__section-title"><span aria-hidden="true">↻</span><div><h2>Lịch sử kiểm tra</h2><p>Các lần liên hệ License Server gần nhất.</p></div></div>
        <ul>@forelse($attempts as $attempt)
            <li><i aria-hidden="true"></i><div><strong>{{ $attempt->operation }}</strong><small>{{ $attempt->started_at }}</small></div><span>{{ $attempt->status }} @if($attempt->error_code)· {{ $attempt->error_code }}@endif</span></li>
        @empty<li class="is-empty"><span aria-hidden="true">○</span><div><strong>Chưa có lần kiểm tra nào</strong><small>Lịch sử sẽ xuất hiện sau lần kích hoạt hoặc đồng bộ đầu tiên.</small></div></li>@endforelse</ul>
    </section>
</div>
@endsection
