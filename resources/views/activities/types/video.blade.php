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
        @php
            $videoPath = parse_url($content['video_url'], PHP_URL_PATH) ?: '';
            $videoExtension = strtolower(pathinfo($videoPath, PATHINFO_EXTENSION));
            $isVideoFile = in_array($videoExtension, ['mp4', 'webm', 'ogv'], true);
        @endphp
        <div class="aspect-video rounded-2xl overflow-hidden bg-black shadow-2xl border border-slate-800">
            @if($isVideoFile)
                <video controls class="w-full h-full" preload="metadata" @if(!empty($content['poster_url'])) poster="{{ $content['poster_url'] }}" @endif>
                    <source src="{{ $content['video_url'] }}" type="{{ match($videoExtension) { 'webm' => 'video/webm', 'ogv' => 'video/ogg', default => 'video/mp4' } }}">
                    @if(!empty($content['captions_url']))
                        <track kind="captions" src="{{ $content['captions_url'] }}" srclang="en" label="English" default>
                    @endif
                    Trình duyệt của bạn không hỗ trợ phát video.
                </video>
            @else
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
            @endif
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
