@props([
    'icon' => '🔍',
    'title' => 'Không có dữ liệu',
    'description' => null,
    'actionText' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'p-8 sm:p-12 text-center rounded-3xl bg-slate-900/40 border border-slate-800/80 backdrop-blur-sm space-y-3']) }}>
    <div class="w-16 h-16 rounded-2xl bg-slate-800/60 text-2xl sm:text-3xl flex items-center justify-center mx-auto border border-slate-700/50 shadow-inner">
        {{ $icon }}
    </div>
    
    <div class="max-w-sm mx-auto space-y-1">
        <h4 class="text-sm sm:text-base font-bold text-white">{{ $title }}</h4>
        @if($description)
            <p class="text-xs text-slate-400 leading-relaxed">{{ $description }}</p>
        @endif
    </div>

    @if($actionText && $actionUrl)
        <div class="pt-2">
            <a href="{{ $actionUrl }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-500/25 transition-all">
                <span>{{ $actionText }}</span>
                <span>→</span>
            </a>
        </div>
    @endif

    {{ $slot }}
</div>
