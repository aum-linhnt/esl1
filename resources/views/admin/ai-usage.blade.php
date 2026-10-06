@extends('layouts.admin')
@section('title', 'Sử dụng và chi phí AI')
@section('content')
<div class="max-w-7xl mx-auto text-gray-300">
    <h1 class="text-2xl font-bold text-white mb-2">Sử dụng & chi phí AI</h1>
    <p class="text-sm text-gray-400 mb-4">Dữ liệu AI Tutor từ {{ $start->format('d/m/Y') }} đến {{ $end->copy()->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }} (giờ Việt Nam).</p>
    <form method="get" class="flex items-center gap-3 mb-6">
        <label for="usage-days">Khoảng thời gian</label>
        <select name="days" id="usage-days" class="bg-gray-900 border border-gray-700 rounded-lg p-2">@foreach([7, 30, 90] as $option)<option value="{{ $option }}" @selected($days === $option)>{{ $option }} ngày</option>@endforeach</select>
        <button class="bg-indigo-600 text-white rounded-lg px-4 py-2">Xem báo cáo</button>
        <a href="{{ route('ai-tutor.credits.index') }}" class="text-indigo-300">Quản lý credit →</a>
    </form>
    @if(!$ready)
        <div class="card-dark p-4">Chưa cài đặt bảng dữ liệu AI Tutor.</div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach(['total' => 'Lượt có usage', 'input_tokens' => 'Token đầu vào', 'output_tokens' => 'Token đầu ra', 'credit_units' => 'Credit tính theo quy tắc'] as $key => $label)
                <div class="card-dark p-4"><p class="text-sm text-gray-400">{{ $label }}</p><p class="text-2xl font-bold text-white">{{ number_format($totals->$key) }}</p></div>
            @endforeach
        </div>
        <p class="text-sm mb-4">Token cache: {{ number_format($totals->cached_tokens) }} (nằm trong token đầu vào) · Âm thanh: {{ number_format($totals->audio_seconds) }} giây.</p>
        <section class="card-dark p-4 mb-6">
            <h2 class="text-lg font-bold text-white mb-3">Chi phí ước tính đã ghi nhận</h2>
            @forelse($costs as $cost)
                <p>{{ $cost->currency }}: {{ $cost->priced > 0 ? number_format((float) $cost->cost, 6, '.', ',') : 'Chưa có giá' }} · {{ number_format($cost->priced) }} lượt có giá · {{ number_format($cost->unpriced) }} lượt chưa có giá.</p>
            @empty
                <p>Chưa có dữ liệu sử dụng trong khoảng thời gian này.</p>
            @endforelse
            <p class="text-sm text-gray-400 mt-3">Ước tính theo giá được lưu lúc gọi AI; chưa có giá được loại khỏi tổng. Các tiền tệ được tính riêng. Credit tính theo quy tắc bao gồm lượt dùng khóa API riêng, không đồng nghĩa số credit đã trừ.</p>
        </section>
        <section class="card-dark p-4 mb-6">
            <h2 class="text-lg font-bold text-white mb-3">Trạng thái yêu cầu được tạo trong kỳ</h2>
            <div class="flex gap-4 flex-wrap">@forelse($statuses as $status)<span>{{ $status->status }}: {{ number_format($status->total) }}</span>@empty<span>Chưa có yêu cầu.</span>@endforelse</div>
            <p class="text-sm text-gray-400 mt-3">Usage tính theo thời điểm ghi nhận; yêu cầu lỗi có thể chưa có usage hoặc chi phí. Báo cáo chưa bao gồm các API AI cũ ngoài AI Tutor.</p>
        </section>
        <h2 class="text-lg font-bold text-white mb-3">Theo tính năng và mô hình</h2>
        <div class="overflow-x-auto card-dark mb-6">
            <table class="w-full text-sm text-left"><thead class="text-gray-400"><tr><th class="p-3">Tính năng / Billing</th><th class="p-3">Provider / Model</th><th class="p-3">Lượt</th><th class="p-3">Token vào / ra</th><th class="p-3">Credit theo quy tắc</th></tr></thead><tbody>
            @forelse($features as $row)<tr class="border-t border-gray-800"><td class="p-3">{{ $row->feature }}<small class="block text-gray-400">{{ $row->billing_mode }}</small></td><td class="p-3 break-all">{{ $row->provider }} / {{ $row->model }}</td><td class="p-3">{{ number_format($row->total) }}</td><td class="p-3">{{ number_format($row->input_tokens) }} / {{ number_format($row->output_tokens) }}</td><td class="p-3">{{ number_format($row->credit_units) }}</td></tr>@empty<tr><td class="p-3" colspan="5">Chưa có dữ liệu.</td></tr>@endforelse
            </tbody></table>
        </div>
        <h2 class="text-lg font-bold text-white mb-3">Các lượt sử dụng gần nhất</h2>
        <div class="overflow-x-auto card-dark mb-4">
            <table class="w-full text-sm text-left"><thead class="text-gray-400"><tr><th class="p-3">Thời gian</th><th class="p-3">Tính năng</th><th class="p-3">Mô hình / Billing</th><th class="p-3">Chi phí ước tính</th></tr></thead><tbody>
            @forelse($history as $row)<tr class="border-t border-gray-800"><td class="p-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($row->created_at, config('app.timezone'))->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td><td class="p-3">{{ $row->feature }}</td><td class="p-3 break-all">{{ $row->provider }} / {{ $row->model }}<small class="block text-gray-400">{{ $row->billing_mode }}</small></td><td class="p-3 whitespace-nowrap">{{ $row->estimated_cost === null ? 'Chưa có giá' : number_format((float) $row->estimated_cost, 6, '.', ',').' '.$row->currency }}</td></tr>@empty<tr><td class="p-3" colspan="4">Chưa có lượt sử dụng.</td></tr>@endforelse
            </tbody></table>
        </div>
        {{ $history->links() }}
    @endif
</div>
@endsection
