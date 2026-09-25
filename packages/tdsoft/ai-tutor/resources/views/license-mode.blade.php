@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'License Gia sư AI'])
@push('styles')
    @if(config('ai-tutor.license.asset_entries'))
        @vite(config('ai-tutor.license.asset_entries'))
    @endif
@endpush
@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license" data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.license.admin_theme') ?? 'system' }}">
    <h1>License Gia sư AI</h1>
    @if($modeError)
        <p role="alert">Cấu hình license không hợp lệ: {{ $modeError }}. Không cấp quyền AI.</p>
    @else
        <h2>Bản quyền source — không yêu cầu kích hoạt</h2>
        <p>Không kiểm tra License Server, thời hạn, domain hoặc installation. Không hỗ trợ thu hồi từ xa.</p>
        <p>Đăng nhập, quyền LMS, cấu hình provider và quota/credit vẫn áp dụng.</p>
        <h3>Module được cấu hình</h3>
        <ul>@forelse($modules as $module)<li>{{ $module }}</li>@empty<li>Chưa bật module nào.</li>@endforelse</ul>
        <p>Chế độ này chỉ thay đổi qua cấu hình triển khai, không có nút bật/tắt trên trang quản trị.</p>
    @endif
</div>
@endsection
