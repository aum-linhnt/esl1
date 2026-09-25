@extends(config('ai-tutor.license.admin_layout', 'ai-tutor::layouts.admin'), ['title' => 'Rule và Credit Gia sư AI'])
@push('styles')
    @if(config('ai-tutor.ui.asset_entries'))
        @vite(config('ai-tutor.ui.asset_entries'))
    @endif
@endpush
@section(config('ai-tutor.license.admin_section', 'content'))
<div class="tai-license tai-credits" data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.license.admin_theme') ?? 'system' }}">
    <header class="tai-credit-header"><div><span class="tai-credit-eyebrow">GIA SƯ AI / QUẢN TRỊ</span><h1>Rule & Credit</h1><p>Thiết lập mức sử dụng AI và cấp credit cho người dùng.</p></div><a href="{{ route('ai-tutor.reconciliation.index') }}">Trang đối soát ↗</a></header>
    <p>Credit là quota nội bộ, không phải tiền OpenAI. Cấp credit không mua thêm số dư API và không mở quyền license.</p>
    @if(session('credit_notice'))<p role="status">{{ session('credit_notice') }}</p>@endif
    @if(session('credit_error'))<p role="alert">{{ session('credit_error') }}</p>@endif
    @if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if(!$ready)<p role="alert">Cần chạy migration audit mới trước khi thay đổi rule hoặc cấp credit.</p>@endif

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
    <section id="credit-recipient">
        <h2><span class="tai-credit-step">02</span> Tìm người nhận credit</h2>
        <p>Tìm theo tên hoặc ID. Khi chưa tìm kiếm, hiển thị tài khoản admin hiện tại để cấp credit tạo vector.</p>
        <form class="tai-credit-search" method="get" action="{{ route('ai-tutor.credits.index') }}">
            <label>Tên hoặc ID người dùng <input name="q" maxlength="100" value="{{ request('q') }}"></label>
            <button type="submit">Tìm người dùng</button>
        </form>
        <ul class="tai-credit-people">
            @forelse($people as $personRow)
                <li><a href="{{ route('ai-tutor.credits.index', ['recipient' => $personRow['id']]) }}#credit-grant"><span><strong>{{ $personRow['name'] }}</strong><small>ID {{ $personRow['id'] }}</small></span><span>Chọn →</span></a></li>
            @empty<li>Không tìm thấy người dùng đang hoạt động.</li>@endforelse
        </ul>
    </section>
    @if($person)
    <section id="credit-grant">
        <h2>Cấp credit cho {{ $person['name'] }} — ID {{ $person['id'] }}</h2>
        <p>Số dư khả dụng: {{ $account ? ($account->balance ?? 'Không giới hạn') : 'Chưa có tài khoản credit' }}.</p>
        <p>Giới hạn/ngày: {{ $account ? ($account->daily_limit ?? 'Không giới hạn') : config('ai-tutor.daily_credits', 10) }}.
            Trạng thái: {{ $account->status ?? 'Sẽ tạo tài khoản khi cấp' }}.</p>
        <p>Cấp thêm số dư không tăng giới hạn ngày/tuần/tháng. Tài khoản mới dùng AI_DAILY_CREDITS; tài khoản cũ giữ nguyên quota.</p>
        <form method="post" action="{{ route('ai-tutor.credits.grant') }}">
            @csrf
            <input type="hidden" name="recipient" value="{{ $person['id'] }}">
            <input type="hidden" name="operation_id" value="{{ $grantId }}">
            <label>Số credit cấp thêm <input type="number" name="units" min="1" max="1000000" required></label>
            <label>Lý do <textarea name="reason" maxlength="500" required rows="2"></textarea></label>
            <label><input type="checkbox" name="confirm" value="1" required> Tôi xác nhận cấp thêm credit cho đúng người nhận nêu trên.</label>
            <button type="submit" @disabled(!$ready || ($account && ($account->status !== 'active' || $account->balance === null)))>Xác nhận cấp credit</button>
        </form>
        <h3>20 giao dịch gần nhất của tài khoản</h3>
        <ul>@forelse($ledger as $entry)<li>{{ $entry->created_at }} — {{ $entry->type }} — {{ $entry->units }} credit — số dư sau: {{ $entry->balance_after ?? 'Không giới hạn' }}</li>@empty<li>Chưa có giao dịch.</li>@endforelse</ul>
    </section>
    @endif
    <section>
        <h2><span class="tai-credit-step">03</span> Lịch sử quản trị</h2>
        <p>20 thao tác gần nhất để theo dõi và đối soát.</p>
        <ul class="tai-credit-audit">@forelse($audit as $entry)
            <li>{{ $entry->created_at }} — admin {{ $entry->actor_id }} — {{ $entry->action }} — {{ $entry->target_id }}
                <details><summary>Chi tiết audit</summary><pre class="tai-preview">{{ $entry->details }}</pre></details>
            </li>
        @empty<li>Chưa có thao tác.</li>@endforelse</ul>
    </section>
</div>
@endsection
