{{-- Activity Type: PDF & Slide Document --}}
<div class="space-y-4">
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 space-y-4 text-center">
        <span class="text-4xl block">📑</span>
        <h3 class="text-base font-bold text-white">{{ $activity->title }}</h3>
        <p class="text-xs text-gray-400 max-w-md mx-auto">{{ $content['notes'] ?? $content['summary'] ?? 'Tài liệu tóm tắt kiến thức quan trọng dành cho bài học này.' }}</p>

        @php
            $docSrc = $activity->hasFile() ? $activity->getFileUrl() : ($content['document_url'] ?? '');
        @endphp

        @if($docSrc)
            <div class="pt-2">
                <a href="{{ $docSrc }}" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/30 text-xs font-semibold transition-colors">
                    <span>Tải xuống / Mở tài liệu ({{ $activity->getFileOriginalName() ?: 'Xem trực tuyến' }})</span>
                    <span>↗</span>
                </a>
            </div>
        @else
            <p class="text-xs text-gray-500">Chưa có tệp tài liệu được đính kèm.</p>
        @endif
    </div>

    @include('activities._completion-button', ['label' => 'Đã đọc xong tài liệu ✓'])
</div>
