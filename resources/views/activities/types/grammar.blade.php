{{-- Activity Type: Grammar Guide --}}
<div class="space-y-5">
    <div class="bg-blue-50/60 dark:bg-slate-900/60 p-4 sm:p-5 rounded-2xl border border-blue-100 dark:border-slate-800 space-y-2">
        <h3 class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $content['title'] ?? $activity->title }}</h3>
        <p class="text-sm text-slate-700 dark:text-gray-300 leading-relaxed">{{ $content['explanation'] ?? '' }}</p>
    </div>

    @if(!empty($content['rules']))
        <div class="space-y-2.5">
            <h4 class="text-xs uppercase font-extrabold text-amber-600 dark:text-amber-400 tracking-wider flex items-center gap-1.5">
                <span>📐</span> Quy tắc cốt lõi:
            </h4>
            @foreach($content['rules'] as $rule)
                <div class="text-xs sm:text-sm text-slate-800 dark:text-gray-200 bg-amber-50/70 dark:bg-slate-900/60 border border-amber-200/80 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 font-mono leading-relaxed">
                    {{ $rule }}
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($content['examples']))
        <div class="space-y-2.5 pt-1">
            <h4 class="text-xs uppercase font-extrabold text-teal-600 dark:text-teal-400 tracking-wider flex items-center gap-1.5">
                <span>💡</span> Ví dụ thực tế:
            </h4>
            @foreach($content['examples'] as $example)
                <div class="text-xs sm:text-sm text-slate-700 dark:text-gray-300 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 italic leading-relaxed">
                    "{{ $example }}"
                </div>
            @endforeach
        </div>
    @endif

    @include('activities._completion-button', ['label' => 'Đã nắm vững ngữ pháp ✓'])
</div>
