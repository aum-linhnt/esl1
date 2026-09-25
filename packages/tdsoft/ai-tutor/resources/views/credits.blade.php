@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'Rule và Credit Gia sư AI'])
@push('styles')
    @if(config('ai-tutor.ui.asset_entries'))
        @vite(config('ai-tutor.ui.asset_entries'))
    @endif
@endpush
@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license tai-credits" data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.license.admin_theme') ?? 'system' }}">
    <header class="tai-credit-header"><div><span class="tai-credit-eyebrow">GIA SƯ AI / QUẢN TRỊ</span><h1>Rule & Credit</h1><p>Thiết lập mức sử dụng AI và cấp credit cho người dùng.</p></div><a href="{{ route('ai-tutor.reconciliation.index') }}">Đối soát @if($pendingReconciliations)<span class="tai-header-count">{{ $pendingReconciliations }}</span>@endif ↗</a></header>
    <p>Credit là quota nội bộ, không phải tiền OpenAI. Cấp credit không mua thêm số dư API và không mở quyền license.</p>
    @if(session('credit_notice'))<p role="status">{{ session('credit_notice') }}</p>@endif
    @if(session('credit_error'))<p role="alert">{{ session('credit_error') }}</p>@endif
    @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if(!$ready)<p role="alert">Cần chạy migration audit mới trước khi thay đổi rule hoặc cấp credit.</p>@endif
    <nav class="tai-credit-tabs" aria-label="Quản lý credit">
        <a href="{{ route('ai-tutor.credits.index', ['tab' => 'rules']) }}" class="{{ $tab === 'rules' ? 'is-active' : '' }}" @if($tab === 'rules') aria-current="page" @endif>Quy tắc credit</a>
        <a href="{{ route('ai-tutor.credits.index', ['tab' => 'grant']) }}" class="{{ $tab === 'grant' ? 'is-active' : '' }}" @if($tab === 'grant') aria-current="page" @endif>Cấp credit</a>
        <a href="{{ route('ai-tutor.credits.index', ['tab' => 'history']) }}" class="{{ $tab === 'history' ? 'is-active' : '' }}" @if($tab === 'history') aria-current="page" @endif>Lịch sử quản trị</a>
    </nav>


    @if($tab === 'rules')
    <section>
        <h2><span class="tai-credit-step">01</span> Quy tắc tính credit</h2>
        <p>knowledge_embedding: tạo vector tài liệu và câu hỏi tìm nguồn. tutor_message: tạo câu trả lời chat.</p>
        <details class="tai-credit-help"><summary>Hướng dẫn tính credit</summary><p>Với rule không có usage blocks, đặt cơ bản = trần = 1 để tính cố định 1 credit/lần. Hệ thống giữ trước mức trần rồi quyết toán thực tế. Giá trị 0 miễn credit nhưng vẫn tốn phí provider.</p></details>
        <p>Rule mới chỉ ảnh hưởng request mới. Cấu hình token block/cost rate hiện có được giữ nguyên, không bị form này xóa.</p>
        @php($featureLabels = ['tutor_message' => 'Trò chuyện với gia sư', 'knowledge_embedding' => 'Vector kiến thức', 'tutor_image_question' => 'Câu hỏi bằng hình ảnh', 'speaking_transcription' => 'Chuyển giọng nói thành văn bản', 'speaking_assessment' => 'Đánh giá kỹ năng nói', 'writing_assessment' => 'Đánh giá bài viết', 'writing_recheck' => 'Chấm lại bài viết'])
        <div class="tai-credit-rule-grid">
        @foreach(config('ai-tutor.features', []) as $feature => $module)
            @php($rule = $rules->get($feature))
            <details class="tai-credit-rule" @if(in_array($feature, ['knowledge_embedding', 'tutor_message'])) open @endif>
                <summary><span><strong>{{ $featureLabels[$feature] ?? $feature }}</strong><code>{{ $feature }}</code></span><span class="tai-credit-badge {{ $rule && $rule->enabled ? 'is-enabled' : '' }}">{{ $rule ? ($rule->enabled ? 'Đang bật' : 'Đang tắt') : 'Chưa cấu hình' }}</span></summary>
                <form method="post" action="{{ route('ai-tutor.credits.rule') }}">
                    @csrf
                    <input type="hidden" name="feature" value="{{ $feature }}">
                    <input type="hidden" name="expected" value="{{ $service->ruleToken($rule) }}">
                    <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <div class="tai-credit-fields"><label>Credit cơ bản/lần <input name="base_units" type="number" min="0" max="1000000" required value="{{ $rule->base_units ?? 1 }}"></label>
                    <label>Trần credit/lần (giữ trước) <input name="max_units" type="number" min="0" max="1000000" required value="{{ $rule->max_units_per_request ?? 1 }}"></label>
                    </div><label>Trạng thái <select name="enabled"><option value="1" @selected(!$rule || $rule->enabled)>Bật</option><option value="0" @selected($rule && !$rule->enabled)>Tắt</option></select></label>
                    @if($rule?->blocks)<p>Token/usage blocks hiện tại (chỉ đọc): <code>{{ $rule->blocks }}</code></p>@endif
                    <button type="submit" @disabled(!$ready)>Lưu cấu hình</button>
                </form>
            </details>
        @endforeach
        </div>
    </section>
    @endif
    @if($tab === 'grant')
    @include('ai-tutor::partials.credit-grant')
    @endif
    @if($tab === 'history')
    <section class="tai-admin-history">
        <div class="tai-history-heading"><div><h2><span class="tai-credit-step">03</span> Lịch sử quản trị</h2><p>Các thay đổi rule và lần cấp credit đã được ghi audit.</p></div><span>Hiển thị {{ $audit->count() }} thao tác gần nhất</span></div>
        <div class="tai-history-list">
        @forelse($audit as $entry)
            @php($detail = json_decode($entry->details, true) ?: [])
            @php($isGrant = $entry->action === 'credit.grant')
            @php($before = (array) ($detail['before'] ?? []))
            @php($after = (array) ($detail['after'] ?? []))
            @php($displayTime = \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y · H:i:s'))
            @php($title = $isGrant ? 'Cấp '.($detail['units'] ?? 0).' credit cho người dùng #'.$entry->target_id : 'Cập nhật rule '.$entry->target_id)
            <article class="tai-history-card">
                <div class="tai-history-topline">
                    <div>
                        <span class="tai-history-badge {{ $isGrant ? 'is-grant' : 'is-rule' }}">{{ $isGrant ? 'Cấp credit' : 'Cập nhật rule' }}</span>
                        <strong>{{ $title }}</strong>
                    </div>
                    <time datetime="{{ $entry->created_at }}">{{ $displayTime }}</time>
                </div>
                <dl>
                @if($isGrant)
                    <div><dt>Admin xử lý</dt><dd>{{ $adminNames->get($entry->actor_id, 'Admin #'.$entry->actor_id) }}</dd></div>
                    <div><dt>Lý do</dt><dd>{{ $detail['reason'] ?? 'Không ghi lý do' }}</dd></div>
                    <div><dt>Số dư trước</dt><dd>{{ $detail['balance_before'] ?? 'Không giới hạn' }}</dd></div>
                    <div><dt>Số dư sau</dt><dd>{{ $detail['balance_after'] ?? 'Không giới hạn' }}</dd></div>
                @else
                    <div><dt>Admin xử lý</dt><dd>{{ $adminNames->get($entry->actor_id, 'Admin #'.$entry->actor_id) }}</dd></div>
                    <div><dt>Credit cơ bản</dt><dd>{{ $before['base_units'] ?? '—' }} → {{ $after['base_units'] ?? '—' }}</dd></div>
                    <div><dt>Trần credit</dt><dd>{{ $before['max_units_per_request'] ?? '—' }} → {{ $after['max_units_per_request'] ?? '—' }}</dd></div>
                    <div><dt>Trạng thái</dt><dd>{{ isset($after['enabled']) ? ($after['enabled'] ? 'Đang bật' : 'Đang tắt') : 'Không xác định' }}</dd></div>
                @endif
                </dl>
                <details>
                    <summary>Dữ liệu kỹ thuật</summary>
                    <pre class="tai-preview">{{ json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            </article>
        @empty
            <p class="tai-history-empty">Chưa có thao tác quản trị.</p>
        @endforelse
        </div>
    </section>
    @endif
</div>
@endsection
