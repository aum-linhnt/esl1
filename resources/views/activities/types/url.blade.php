{{-- Activity Type: External URL --}}
<div class="space-y-4">
    <div class="bg-slate-50 dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-4 text-center">
        <span class="text-4xl block">🔗</span>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $activity->title }}</h3>
        @if(!empty($content['instructions']))
            <p class="text-xs sm:text-sm text-slate-600 dark:text-gray-300 max-w-md mx-auto">{{ $content['instructions'] }}</p>
        @endif

        @if(!empty($content['url']))
            <div class="pt-2">
                <a href="{{ $content['url'] }}" target="{{ ($content['open_in_new_tab'] ?? true) ? '_blank' : '_self' }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-cyan-500/15 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-500/25 border border-cyan-500/30 text-xs font-semibold transition-colors">
                    <span>Truy cập tài nguyên liên kết ↗</span>
                </a>
            </div>
        @endif
    </div>

    @include('activities._completion-button', ['label' => 'Đã truy cập tài nguyên ✓'])
</div>
