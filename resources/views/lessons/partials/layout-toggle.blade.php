@php
    $targetLayout = $tutorLayout ? 'classic' : 'tutor';
    $toggleLabel = $tutorLayout ? 'Chuyển về giao diện cũ' : 'Bật giao diện học cùng AI';
    $toggleParams = ['lessonId' => $lesson->id, 'layout' => $targetLayout];
    if (isset($toggleActivityId)) {
        $toggleParams['activity'] = $toggleActivityId;
    } elseif (($preserveActivity ?? true) && request()->filled('activity')) {
        $toggleParams['activity'] = request()->query('activity');
    }
@endphp
<a href="{{ route('lessons.show', $toggleParams) }}"
   title="{{ $toggleLabel }}" aria-label="{{ isset($toggleText) ? $toggleText . ' — ' . $toggleLabel : $toggleLabel }}"
   data-lesson-layout-toggle="{{ $targetLayout }}"
   style="display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;border:1px solid {{ $tutorLayout ? '#ddd9ff' : '#475569' }};background:{{ $tutorLayout ? '#f3f1ff' : '#1e293b' }};color:{{ $tutorLayout ? '#5548df' : '#c4b5fd' }};font-size:12px;line-height:1.4;text-decoration:none;flex-shrink:0">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
        <rect x="3" y="4" width="18" height="16" rx="3" />
        <path d="M3 9h18M15 9v11" />
        @if(!$tutorLayout)<path d="M7 13h4m-2-2v4" />@else<path d="m7 13 2 2 3-4" />@endif
    </svg>
    <span>{{ $toggleText ?? ($tutorLayout ? 'Giao diện cũ' : 'Học cùng AI') }}</span>
</a>
