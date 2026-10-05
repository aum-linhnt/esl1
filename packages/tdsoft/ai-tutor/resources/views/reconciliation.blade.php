@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'Đối soát request Gia sư AI'])
@push('styles')
    @if(config('ai-tutor.ui.asset_entries'))
        @vite(config('ai-tutor.ui.asset_entries'))
    @endif
@endpush
@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license tai-credits" data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.license.admin_theme') ?? 'system' }}">
    <header class="tai-credit-header">
        <div><span class="tai-credit-eyebrow">GIA SƯ AI / TÀI CHÍNH</span><h1>Đối soát request</h1><p>Xử lý các request có kết quả provider chưa xác định chắc chắn.</p></div>
        <a href="{{ route('ai-tutor.credits.index') }}">← Rule & Credit</a>
    </header>
    @if(session('reconciliation_notice'))<p role="status">{{ session('reconciliation_notice') }}</p>@endif
    @if(session('reconciliation_error'))<p role="alert">{{ session('reconciliation_error') }}</p>@endif
    @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    <section class="tai-reconciliation">
        <h2><span class="tai-credit-step">{{ $items->count() }}</span> Đang chờ xử lý</h2>
        <p>Chỉ thao tác sau khi kiểm tra dashboard hoặc log provider. Commit ghi nhận credit đã dùng; giải phóng hoàn lại toàn bộ khoản giữ.</p>
        @if($items->isEmpty())
            <p role="status">Không có request nào đang chờ đối soát.</p>
        @else
            <div class="tai-reconciliation-list">
            @foreach($items as $item)
                <article class="tai-reconciliation-card">
                    <div class="tai-reconciliation-title"><strong>{{ $item->feature }}</strong><span>{{ $item->created_at }}</span></div>
                    <dl>
                        <div><dt>Request</dt><dd><code>{{ $item->request_id }}</code></dd></div>
                        <div><dt>Người dùng</dt><dd>{{ $item->user_id ?? 'Không xác định' }}</dd></div>
                        <div><dt>Khoản đang giữ</dt><dd>{{ $item->reserved_units }} credit</dd></div>
                        <div><dt>Provider / model</dt><dd>{{ $item->provider ?? 'Chưa xác định' }} / {{ $item->model ?? 'Chưa xác định' }}</dd></div>
                        <div><dt>Remote request</dt><dd>{{ $item->remote_request_id ?? 'Không có' }}</dd></div>
                        <div><dt>Kết quả mã hóa</dt><dd>{{ $item->encrypted_result ? 'Có — provider đã trả kết quả' : 'Không có' }}</dd></div>
                    </dl>
                    @if($item->encrypted_result)<p class="tai-reconciliation-warning">Có kết quả đã lưu. Hãy ưu tiên xác minh provider trước khi quyết định.</p>@endif
                    <div class="tai-reconciliation-actions">
                        <form method="post" action="{{ route('ai-tutor.reconciliation.update', $item->request_id) }}">
                            @csrf
                            <input type="hidden" name="decision" value="commit">
                            <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <label>Credit thực tế <input type="number" name="actual_units" min="0" max="{{ $item->reserved_units }}" value="{{ $item->reserved_units }}" required></label>
                            <label>Lý do xác nhận <textarea name="reason" minlength="10" maxlength="500" rows="2" required></textarea></label>
                            <label><input type="checkbox" name="confirm" value="1" required> Tôi đã xác minh provider có xử lý request.</label>
                            <button type="submit">Commit credit</button>
                        </form>
                        <form method="post" action="{{ route('ai-tutor.reconciliation.update', $item->request_id) }}">
                            @csrf
                            <input type="hidden" name="decision" value="release">
                            <input type="hidden" name="actual_units" value="0">
                            <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <label>Lý do giải phóng <textarea name="reason" minlength="10" maxlength="500" rows="2" required></textarea></label>
                            <label><input type="checkbox" name="confirm" value="1" required> Tôi đã xác minh request không phát sinh sử dụng.</label>
                            <button type="submit" class="tai-button-safe">Giải phóng credit</button>
                        </form>
                    </div>
                </article>
            @endforeach
            </div>
        @endif
    </section>
    <section class="tai-reconciliation-history">
        <div class="tai-history-heading"><div><h2>Lịch sử đối soát</h2><p>Các quyết định đã được ghi audit và không thể chỉnh sửa.</p></div><span>Hiển thị {{ $audit->count() }} thao tác gần nhất</span></div>
        <div class="tai-history-list">
        @forelse($audit as $entry)
            @php($detail = json_decode($entry->details, true) ?: [])
            @php($committed = str_ends_with($entry->action, '.commit'))
            @php($displayTime = \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y · H:i:s'))
            @php($reason = trim((string) ($detail['reason'] ?? '')))
            @php($displayReason = mb_strlen($reason) >= 10 ? $reason : ($committed ? 'Xác nhận provider đã xử lý request' : 'Xác nhận request không phát sinh sử dụng'))
            <article class="tai-history-card">
                <div class="tai-history-topline">
                    <div>
                        <span class="tai-history-badge {{ $committed ? 'is-commit' : 'is-release' }}">{{ $committed ? 'Đã commit' : 'Đã giải phóng' }}</span>
                        <strong>{{ $displayReason }}</strong>
                    </div>
                    <time datetime="{{ $entry->created_at }}">{{ $displayTime }}</time>
                </div>
                <dl>
                    <div><dt>Người dùng</dt><dd>{{ $detail['user_id'] ?? 'Không xác định' }}</dd></div>
                    <div><dt>Admin xử lý</dt><dd>{{ $adminNames->get($entry->actor_id, 'Admin #'.$entry->actor_id) }}</dd></div>
                    <div><dt>Credit giữ trước</dt><dd>{{ $detail['reserved_units'] ?? 0 }}</dd></div>
                    <div><dt>Credit thực tế</dt><dd>{{ $detail['actual_units'] ?? 0 }}</dd></div>
                    <div><dt>Provider</dt><dd>{{ $detail['provider'] ?? 'Không xác định' }}</dd></div>
                    <div><dt>Model</dt><dd>{{ $detail['model'] ?? 'Không xác định' }}</dd></div>
                </dl>
                <div class="tai-history-request">
                    <span>Request ID</span>
                    <code>{{ $entry->target_id }}</code>
                    <button type="button" class="tai-copy-button" data-copy-text="{{ $entry->target_id }}" aria-label="Sao chép Request ID" title="Sao chép Request ID">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h11v11H8zM5 16H4V4h12v1"/></svg>
                        <span>Sao chép</span>
                    </button>
                </div>
                <details>
                    <summary>Dữ liệu kỹ thuật</summary>
                    <pre class="tai-preview">{{ json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            </article>
        @empty
            <p class="tai-history-empty">Chưa có thao tác đối soát.</p>
        @endforelse
        </div>
    </section>
</div>
@endsection
