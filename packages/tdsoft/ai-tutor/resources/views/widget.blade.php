@once
    @if(config('ai-tutor.ui.asset_entries'))
        @vite(config('ai-tutor.ui.asset_entries'))
    @endif
@endonce
<aside class="tai-widget tai-widget--{{ $settings['position'] }} tai-widget--{{ $settings['mode'] }}"
    data-tai-widget data-ai-tutor-root data-ai-tutor-theme="{{ config('ai-tutor.theme.default', 'system') }}"
    style="--tai-panel-width: {{ $settings['width'] }}px">
    <button type="button" class="tai-launcher" data-widget-open aria-expanded="false" aria-controls="tai-widget-panel">Gia sư AI</button>
    <section id="tai-widget-panel" class="tai-widget-panel" data-widget-panel hidden aria-label="Gia sư AI">
        <nav aria-label="Điều khiển Gia sư AI">
            @if($settings['expand'])
                <button type="button" data-widget-expand aria-pressed="false">Mở rộng</button>
            @endif
            <button type="button" data-widget-close>Thu gọn / Đóng</button>
        </nav>
        @include('ai-tutor::partials.chat')
    </section>
</aside>
