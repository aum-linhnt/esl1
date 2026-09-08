{{-- Activity Type: File Resource --}}
<div class="space-y-4">
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 space-y-4 text-center">
        <span class="text-4xl block">📁</span>
        <h3 class="text-base font-bold text-white">{{ $activity->title }}</h3>
        @if(!empty($activity->description))
            <p class="text-xs text-gray-400 max-w-md mx-auto">{{ $activity->description }}</p>
        @endif
        @if(!empty($content['notes']))
            <p class="text-xs text-gray-400 max-w-md mx-auto italic">{{ $content['notes'] }}</p>
        @endif

        @if($activity->hasFile())
            <div class="pt-2">
                <a href="{{ $activity->getFileUrl() }}" download="{{ $activity->getFileOriginalName() }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-orange-500/20 text-orange-300 hover:bg-orange-500/30 border border-orange-500/30 text-xs font-semibold transition-colors">
                    <span>📥 Tải về: {{ $activity->getFileOriginalName() }} ({{ $activity->getFileSizeFormatted() }})</span>
                </a>
            </div>
        @else
            <p class="text-xs text-gray-500">Chưa có tệp đính kèm.</p>
        @endif
    </div>

    @include('activities._completion-button', ['label' => 'Đã tải tài liệu ✓'])
</div>
