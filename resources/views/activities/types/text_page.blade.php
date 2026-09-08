{{-- Activity Type: Text Page --}}
<div class="space-y-4">
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 space-y-4">
        <h3 class="text-lg font-bold text-white">{{ $activity->title }}</h3>
        <div class="text-xs text-gray-300 leading-relaxed whitespace-pre-line">
            {!! nl2br(e($content['body'] ?? $activity->description ?? '')) !!}
        </div>
    </div>

    @include('activities._completion-button', ['label' => 'Đã đọc xong nội dung ✓'])
</div>
