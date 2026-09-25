@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'License Gia sư AI'])
@push('styles')
    @if(config('ai-tutor.license.asset_entries'))
        @vite(config('ai-tutor.license.asset_entries'))
    @endif
@endpush
@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license tai-license-mode" data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.license.admin_theme') ?? 'system' }}">
    <header class="tai-license-mode__header">
        <div>
            <span class="tai-license-mode__eyebrow">GIA SƯ AI / BẢN QUYỀN</span>
            <h1>License Gia sư AI</h1>
            <p>Thông tin bản quyền và phạm vi tính năng của hệ thống.</p>
        </div>
        @unless($modeError)<span class="tai-license-mode__status"><i aria-hidden="true"></i> Đang hoạt động</span>@endunless
    </header>
    @if($modeError)
        <p role="alert">Cấu hình license không hợp lệ: {{ $modeError }}. Không cấp quyền AI.</p>
    @else
        <section class="tai-license-mode__hero">
            <div class="tai-license-mode__icon" aria-hidden="true">✓</div>
            <div>
                <span class="tai-license-mode__label">CHẾ ĐỘ BẢN QUYỀN</span>
                <h2>Bản quyền source</h2>
                <p>Đã cấp quyền sử dụng source code, không yêu cầu kích hoạt qua License Server.</p>
            </div>
            <span class="tai-license-mode__pill">SOURCE OWNED</span>
        </section>

        <div class="tai-license-mode__grid">
            <section class="tai-license-mode__card">
                <div class="tai-license-mode__card-icon" aria-hidden="true">⌁</div>
                <h2>Không phụ thuộc License Server</h2>
                <p>Không kiểm tra thời hạn, domain hoặc installation và không hỗ trợ thu hồi từ xa.</p>
                <ul class="tai-license-mode__checks">
                    <li>Không cần kích hoạt</li>
                    <li>Không có thời gian hết hạn</li>
                    <li>Không ràng buộc tên miền</li>
                </ul>
            </section>

            <section class="tai-license-mode__card">
                <div class="tai-license-mode__card-icon" aria-hidden="true">▦</div>
                <h2>Module được cấu hình</h2>
                <p>Các module AI được phép hoạt động trong bản triển khai hiện tại.</p>
                <ul class="tai-license-mode__modules">@forelse($modules as $module)
                    <li><span>{{ $module }}</span><strong>Đã bật</strong></li>
                @empty<li><span>Chưa bật module nào.</span></li>@endforelse</ul>
            </section>
        </div>

        <section class="tai-license-mode__notice">
            <span aria-hidden="true">i</span>
            <div><h2>Các kiểm soát khác vẫn được áp dụng</h2><p>Đăng nhập, quyền LMS, cấu hình AI provider và quota/credit vẫn hoạt động bình thường.</p></div>
        </section>

        <p class="tai-license-mode__footnote">Chế độ bản quyền chỉ thay đổi qua cấu hình triển khai, không có nút bật/tắt trên trang quản trị.</p>
    @endif
</div>
@endsection
