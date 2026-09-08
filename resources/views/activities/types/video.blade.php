{{-- Activity Type: Video Interactive --}}
<div class="space-y-4">
    @if($activity->hasFile())
        <div class="aspect-video rounded-2xl overflow-hidden bg-black shadow-2xl border border-slate-800">
            <video controls class="w-full h-full" preload="metadata">
                <source src="{{ $activity->getFileUrl() }}" type="{{ $activity->file?->mime_type ?? 'video/mp4' }}">
                Trình duyệt của bạn không hỗ trợ phát video.
            </video>
        </div>
    @elseif(!empty($content['video_url']))
        <div class="aspect-video rounded-2xl overflow-hidden bg-black shadow-2xl border border-slate-800">
            @php
                $videoUrl = $content['video_url'];
                if (str_contains($videoUrl, 'youtube.com/watch?v=')) {
                    $videoUrl = str_replace('watch?v=', 'embed/', $videoUrl);
                    $videoUrl = explode('&', $videoUrl)[0];
                } elseif (str_contains($videoUrl, 'youtu.be/')) {
                    $parts = explode('youtu.be/', $videoUrl);
                    $videoUrl = 'https://www.youtube.com/embed/' . ($parts[1] ?? '');
                }
            @endphp
            <iframe src="{{ $videoUrl }}" class="w-full h-full" allowfullscreen frameborder="0"></iframe>
        </div>
    @else
        <div class="p-8 text-center text-gray-400 bg-slate-900 rounded-2xl border border-slate-800 text-xs">
            Chưa có video được tải lên hoặc liên kết.
        </div>
    @endif

    @if(!empty($content['description']))
        <p class="text-sm text-gray-400">{{ $content['description'] }}</p>
    @endif

    @include('activities._completion-button', ['label' => 'Đã xem xong video bài giảng ✓'])
</div>
