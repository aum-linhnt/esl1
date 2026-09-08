{{-- Activity Type: Grammar Guide --}}
<div class="space-y-4">
    <h3 class="text-lg font-semibold text-fsel-blue">{{ $content['title'] ?? $activity->title }}</h3>
    <p class="text-sm text-gray-300 leading-relaxed">{{ $content['explanation'] ?? '' }}</p>

    @if(!empty($content['rules']))
        <div class="space-y-2">
            <h4 class="text-xs uppercase font-bold text-fsel-gold tracking-wider">📐 Quy tắc cốt lõi:</h4>
            @foreach($content['rules'] as $rule)
                <div class="text-xs text-gray-200 bg-fsel-navy/50 border border-fsel-border/20 rounded-xl p-3 font-mono">
                    {{ $rule }}
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($content['examples']))
        <div class="space-y-2 pt-2">
            <h4 class="text-xs uppercase font-bold text-fsel-teal tracking-wider">💡 Ví dụ thực tế:</h4>
            @foreach($content['examples'] as $example)
                <div class="text-xs text-gray-300 bg-fsel-dark/60 border border-fsel-border/20 rounded-xl p-3 italic">
                    "{{ $example }}"
                </div>
            @endforeach
        </div>
    @endif

    @include('activities._completion-button', ['label' => 'Đã nắm vững ngữ pháp ✓'])
</div>
