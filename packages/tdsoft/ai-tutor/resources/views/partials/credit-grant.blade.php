@if(!$person)
<section id="credit-recipient">
    <h2><span class="tai-credit-step">02</span> Tìm người nhận credit</h2><p>Tìm theo tên hoặc ID để xem số dư và cấp thêm credit.</p>
    <form class="tai-credit-search" method="get" action="{{ route('ai-tutor.credits.index') }}"><input type="hidden" name="tab" value="grant"><label>Tên hoặc ID người dùng <input name="q" maxlength="100" value="{{ request('q') }}"></label><button type="submit">Tìm người dùng</button></form>
    <ul class="tai-credit-people">@forelse($people as $personRow)<li><a href="{{ route('ai-tutor.credits.index', ['tab' => 'grant', 'recipient' => $personRow['id']]) }}"><span><strong>{{ $personRow['name'] }}</strong><small>ID {{ $personRow['id'] }}</small></span><span>Chọn →</span></a></li>@empty<li>Không tìm thấy người dùng đang hoạt động.</li>@endforelse</ul>
</section>
@else
<section id="credit-grant" class="tai-grant-workspace">
    <div class="tai-selected-person"><div><strong>{{ $person['name'] }}</strong><small>ID {{ $person['id'] }}</small></div><a href="{{ route('ai-tutor.credits.index', ['tab' => 'grant']) }}">Đổi người dùng</a></div>
    <div class="tai-credit-stats">
        <article><span>Số dư khả dụng</span><strong>{{ $account ? ($account->balance ?? '∞') : 'Chưa có' }}</strong></article>
        <article><span>Giới hạn ngày</span><strong>{{ $account ? ($account->daily_limit ?? '∞') : config('ai-tutor.daily_credits', 10) }}</strong></article>
        <article><span>Trạng thái</span><strong>{{ !$account ? 'Chưa tạo' : ($account->status === 'active' ? 'Đang hoạt động' : 'Tạm khóa') }}</strong></article>
        <article><span>Credit đang giữ</span><strong>{{ $heldUnits }}</strong></article>
    </div>
    <div class="tai-grant-grid">
        <article class="tai-grant-form-panel">
            <h2>Cấp thêm credit</h2><p>Chỉ cộng số dư khả dụng, không thay đổi giới hạn ngày, tuần hoặc tháng.</p>
            <form method="post" action="{{ route('ai-tutor.credits.grant') }}">@csrf
                <input type="hidden" name="recipient" value="{{ $person['id'] }}"><input type="hidden" name="operation_id" value="{{ $grantId }}">
                <label>Số credit cấp thêm <input type="number" name="units" min="1" max="1000000" required></label>
                <label>Lý do <textarea name="reason" maxlength="500" required rows="3" placeholder="Ví dụ: Bổ sung quota học tập tháng 9"></textarea></label>
                <p class="tai-form-note">Thao tác này không mua thêm số dư API provider.</p>
                <label class="tai-confirm-box"><input type="checkbox" name="confirm" value="1" required> Tôi xác nhận cấp thêm credit cho đúng người dùng.</label>
                <button type="submit" @disabled(!$ready || ($account && ($account->status !== 'active' || $account->balance === null)))>Cấp thêm credit</button>
            </form>
        </article>
        <aside class="tai-ledger-panel">
            <div class="tai-ledger-heading"><h2>Lịch sử credit</h2><span>{{ $ledger->count() }} thao tác gần nhất</span></div>
            <p class="tai-ledger-help">Theo dõi credit được cấp, tạm giữ và sử dụng của người dùng này.</p>
            <details class="tai-ledger-guide">
                <summary>Cách đọc lịch sử</summary>
                <p>Khi gửi yêu cầu AI, hệ thống tạm giữ credit và giảm số dư khả dụng. Khi hoàn tất, “Đã sử dụng” xác nhận phần credit đã giữ, không trừ thêm lần nữa. Credit dư hoặc yêu cầu được hủy sẽ được hoàn lại.</p>
            </details>
            <div class="tai-ledger-list">@forelse($ledger as $entry)
                @php
                    $transactionLabel = ['grant' => 'Admin cấp thêm', 'reserve' => 'Tạm giữ cho yêu cầu AI', 'commit' => 'Đã sử dụng', 'release' => 'Hoàn credit đã giữ'][$entry->type] ?? $entry->type;
                    $transactionDescription = ['grant' => 'Cộng thêm vào số dư khả dụng.', 'reserve' => 'Tạm giảm số dư khả dụng để xử lý yêu cầu.', 'commit' => 'Xác nhận đã dùng phần credit tạm giữ; không trừ thêm.', 'release' => 'Trả lại phần credit tạm giữ chưa sử dụng.'][$entry->type] ?? 'Ghi nhận thao tác credit.';
                    $featureLabel = ['knowledge_embedding' => 'Tạo vector / tìm nguồn Knowledge', 'tutor_message' => 'Chat với Gia sư AI', 'tutor_image_question' => 'Hỏi bằng hình ảnh', 'speaking_transcription' => 'Chuyển giọng nói thành văn bản', 'speaking_assessment' => 'Đánh giá nói', 'writing_assessment' => 'Đánh giá viết', 'writing_recheck' => 'Chấm lại bài viết'][$entry->feature ?? ''] ?? ($entry->feature ?? null);
                    $unitPrefix = match ($entry->type) { 'grant', 'release' => '+', 'reserve' => '−', default => '' };
                    $grantRecord = $entry->type === 'grant' ? $grantAudit->get(substr($entry->idempotency_key, strlen('grant:admin:'))) : null;
                    $grantDetails = $grantRecord ? json_decode($grantRecord->details, true) : [];
                @endphp
                @php($transactionTime = \Illuminate\Support\Carbon::parse($entry->created_at)->format('d/m/Y · H:i'))
                <article class="tai-ledger-item">
                    <div><span class="tai-ledger-badge is-{{ $entry->type }}">{{ $transactionLabel }}</span><time>{{ $transactionTime }}</time></div>
                    @if($featureLabel)<p class="tai-ledger-feature">{{ $featureLabel }}</p>@endif
                    <div class="tai-ledger-amount"><strong>{{ $unitPrefix }}{{ $entry->units }} credit</strong><span>Số dư: {{ $entry->balance_before ?? '∞' }} → {{ $entry->balance_after ?? '∞' }}</span></div>
                    <p class="tai-ledger-description">{{ $transactionDescription }}</p>
                    @if($grantRecord)
                        <p class="tai-ledger-description"><strong>Người cấp:</strong> {{ $adminNames[$grantRecord->actor_id] ?? $grantRecord->actor_id }}</p>
                        <p class="tai-ledger-description"><strong>Lý do:</strong> {{ $grantDetails['reason'] ?? 'Chưa ghi nhận' }}</p>
                    @endif
                    @if($entry->request_id)
                        <details class="tai-ledger-request">
                            <summary>Mã yêu cầu AI</summary>
                            <div class="tai-history-request">
                                <code>{{ $entry->request_id }}</code>
                                <button type="button" class="tai-copy-button" data-copy-text="{{ $entry->request_id }}" aria-label="Sao chép mã yêu cầu AI">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V4H4v12h4"/></svg><span>Sao chép</span>
                                </button>
                            </div>
                        </details>
                    @endif
                </article>
            @empty<p class="tai-history-empty">Chưa có giao dịch.</p>@endforelse</div>
        </aside>
    </div>
</section>
@endif
