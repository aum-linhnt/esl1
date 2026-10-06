{{-- Activity Type: Audio Listening & Podcast --}}
<div class="space-y-5">
    {{-- Audio Player --}}
    <div class="bg-slate-900 p-5 rounded-2xl border border-slate-800 space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-teal-400">🎧 Audio Player</span>
            @if($activity->hasFile())
                <span class="text-[10px] text-gray-400 font-mono">{{ $activity->getFileOriginalName() }} ({{ $activity->getFileSizeFormatted() }})</span>
            @else
                <span class="text-[10px] text-gray-500 font-mono">Synced Transcript</span>
            @endif
        </div>

        @php
            $audioSrc = $activity->hasFile() ? $activity->getFileUrl() : ($content['audio_url'] ?? '');
        @endphp

        @if($audioSrc)
            <audio controls class="w-full rounded-xl">
                <source src="{{ $audioSrc }}" type="{{ $activity->file?->mime_type ?? 'audio/mpeg' }}">
                Trình duyệt của bạn không hỗ trợ phát audio.
            </audio>
        @else
            <p class="text-xs text-gray-400 text-center py-2">Chưa có file audio hoặc link audio.</p>
        @endif
    </div>

    @if(!empty($content['description']))
        <p class="text-xs text-gray-400 leading-relaxed">{{ $content['description'] }}</p>
    @endif

    {{-- Synced Transcript --}}
    @if(!empty($content['transcript']))
        <details class="bg-fsel-navy/40 p-5 rounded-2xl border border-fsel-border/30 space-y-2" @if(empty($content['hide_transcript'])) open @endif>
            <summary class="text-xs font-bold text-white uppercase tracking-wider cursor-pointer">📝 Xem lời thoại (Transcript)</summary>
            <div class="text-xs text-gray-300 leading-relaxed whitespace-pre-line font-serif">
                {{ $content['transcript'] }}
            </div>
        </details>
    @endif

    @include('activities._completion-button', ['label' => 'Đã hoàn thành bài nghe ✓'])
</div>
