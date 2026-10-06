@extends('ai-tutor::layouts.tutor')
@section('title', 'Credit AI của bạn')
@section('content')
<main class="tai-license" style="max-width:1000px;padding:24px">
    <a href="{{ route('dashboard.v2') }}">← Dashboard v2</a>
    <h1>Credit AI của bạn</h1>
    @if(!$account)
        <section>Chưa có tài khoản credit AI. Liên hệ quản trị viên để được cấp credit.</section>
    @else
        <section>
            <h2>{{ $account['balance'] === null ? 'Số dư không giới hạn' : number_format($account['balance']).' credit khả dụng' }}</h2>
            <p>{{ $account['status'] === 'active' ? 'Tài khoản credit đang hoạt động.' : 'Tài khoản credit đang tạm khóa.' }}</p>
            <p>Đang giữ cho yêu cầu AI: {{ number_format($account['held']) }} credit. Phần này đã được trừ khỏi số dư khả dụng.</p>
            @foreach(['daily_limit' => 'ngày', 'weekly_limit' => 'tuần', 'monthly_limit' => 'tháng'] as $key => $label)
                @php($quota = $account['quotas'][$key])
                <p>Hạn mức {{ $label }}: {{ $quota['limit'] === null ? 'Không giới hạn' : number_format($quota['remaining']).' / '.number_format($quota['limit']).' credit còn lại' }} · Đã dùng: {{ number_format($quota['spent']) }} credit.</p>
            @endforeach
            <p>Hạn mức giới hạn mức sử dụng, không tự cộng credit vào số dư. Mốc đặt lại: {{ config('app.timezone') }}.</p>
        </section>
        <h2>Lịch sử credit</h2>
        <div style="overflow-x:auto">
            <table style="width:100%;text-align:left;border-spacing:0 12px">
                <thead><tr><th>Thời gian</th><th>Giao dịch</th><th>Tính năng</th><th>Credit</th><th>Số dư sau giao dịch</th></tr></thead>
                <tbody>
                @forelse($history as $entry)
                    <tr><td>{{ \Carbon\Carbon::parse($entry->created_at, config('app.timezone'))->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td><td>{{ ['grant' => 'Cấp credit', 'reserve' => 'Giữ credit', 'commit' => 'Sử dụng', 'release' => 'Hoàn phần giữ'][$entry->type] ?? $entry->type }}</td><td>{{ $entry->feature ?? '—' }}</td><td>{{ number_format($entry->units) }}</td><td>{{ $entry->balance_after === null ? 'Không giới hạn' : number_format($entry->balance_after) }}</td></tr>
                @empty
                    <tr><td colspan="5">Chưa có giao dịch credit.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $history->links() }}
    @endif
    <p>Chỉ thống kê credit do AI Tutor quản lý. Các lượt dùng khóa API riêng và các tính năng AI cũ có thể không trừ credit ở đây.</p>
</main>
@endsection
