@props([
    'title' => '',
    'value' => '0',
    'icon' => null,
    'color' => 'indigo',
    'subtitle' => null,
    'trend' => null,
])

@php
    $colorMap = [
        'indigo' => 'from-indigo-600/20 to-blue-600/10 border-indigo-500/30 text-indigo-400',
        'emerald' => 'from-emerald-600/20 to-teal-600/10 border-emerald-500/30 text-emerald-400',
        'amber' => 'from-amber-600/20 to-orange-600/10 border-amber-500/30 text-amber-400',
        'rose' => 'from-rose-600/20 to-red-600/10 border-rose-500/30 text-rose-400',
        'purple' => 'from-purple-600/20 to-pink-600/10 border-purple-500/30 text-purple-400',
    ];
    $colorClasses = $colorMap[$color] ?? $colorMap['indigo'];
@endphp

<div {{ $attributes->merge(['class' => "p-5 rounded-2xl bg-gradient-to-br {$colorClasses} border backdrop-blur-sm transition-all hover:scale-[1.01] hover:shadow-lg"]) }}>
    <div class="flex items-center justify-between gap-3">
        <span class="text-xs font-medium text-slate-400">{{ $title }}</span>
        @if($icon)
            <div class="w-8 h-8 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-base">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="mt-2 flex items-baseline gap-2">
        <span class="text-2xl font-black text-white tracking-tight">{{ $value }}</span>
        @if($trend)
            <span class="text-xs font-semibold {{ str_starts_with($trend, '+') ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ $trend }}
            </span>
        @endif
    </div>

    @if($subtitle)
        <p class="mt-1 text-[11px] text-slate-400">{{ $subtitle }}</p>
    @endif
</div>
