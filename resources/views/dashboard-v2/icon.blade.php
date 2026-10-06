@php
    $paths = [
        'check-circle' => '<circle cx="12" cy="12" r="10" fill="currentColor" stroke="none"/><path stroke="white" d="m7 12 3 3 7-7"/>',
        'users' => '<circle cx="9" cy="7" r="4"/><path d="M1 22v-3a8 8 0 0 1 16 0v3M17 3a4 4 0 0 1 0 8m3 3a7 7 0 0 1 3 6v2"/>',
        'thumbs-up' => '<path fill="currentColor" stroke="none" d="M9 10 13 2h2v7h5a3 3 0 0 1 3 4l-2 7a3 3 0 0 1-3 2H9ZM1 10h5v12H1Z"/>',
        'message' => '<path fill="currentColor" stroke="none" d="M12 2C6 2 1 6 1 11c0 3 2 6 5 8l-2 4 6-3h2c6 0 11-4 11-9S18 2 12 2Z"/><path stroke="white" stroke-width="2.5" d="M7 11h.01M12 11h.01M17 11h.01"/>',
        'download' => '<path d="M12 2v13m-5-5 5 5 5-5M3 15v7h18v-7"/>',
        'settings' => '<path d="m9 2-1 3-3 1-3 3 2 3-1 4 3 3 4-1 3 2 3-2 4 1 2-3-1-4 2-3-3-3-3-1-1-3Z"/><circle cx="12" cy="12" r="3"/>',
        'alert' => '<circle cx="12" cy="12" r="10" fill="currentColor" stroke="none"/><path stroke="white" d="M12 6v7m0 4h.01"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="17" rx="2"/><path d="M7 2v6m10-6v6M3 11h18m-14 4h2m4 0h2m-8 4h2m4 0h2"/>',
        'home' => '<path fill="currentColor" stroke="none" d="m12 2 11 9-2 2-2-1.6V22h-5v-7h-4v7H5V11.4L3 13l-2-2Z"/>',
        'headphones' => '<path d="M4 14v-3a8 8 0 0 1 16 0v3"/><rect x="2" y="11" width="5" height="10" rx="2" fill="currentColor"/><rect x="17" y="11" width="5" height="10" rx="2" fill="currentColor"/>',
        'mic' => '<rect x="8" y="2" width="8" height="13" rx="4" fill="currentColor"/><path d="M5 10v2a7 7 0 0 0 14 0v-2M12 19v3m-4 0h8"/>',
        'pencil' => '<path fill="currentColor" stroke="none" d="m16 2 6 6-13 13-7 1 1-7Zm-8 17 11-11-3-3L5 16v3Z"/><path d="M12 22h10"/>',
        'chart' => '<path d="M2 22h20"/><rect x="4" y="11" width="3" height="9" rx="1" fill="currentColor"/><rect x="10.5" y="3" width="3" height="17" rx="1" fill="currentColor"/><rect x="17" y="7" width="3" height="13" rx="1" fill="currentColor"/>',
        'trophy' => '<path fill="currentColor" stroke="none" d="M6 2h12v7a6 6 0 0 1-5 6v4h4v3H7v-3h4v-4a6 6 0 0 1-5-6Z"/><path d="M6 4H2v4a5 5 0 0 0 5 5m11-9h4v4a5 5 0 0 1-5 5"/>',
        'trending-up' => '<path stroke-width="2.5" d="m3 17 6-6 4 4 8-10m-6 0h6v6"/>',
        'flag' => '<path d="M4 22V3"/><path fill="currentColor" stroke="none" d="M5 3c6-5 10 5 17 0v13c-7 5-11-5-17 0Z"/>',
        'search' => '<circle cx="10" cy="10" r="7"/><path d="m15 15 7 7"/>',
        'letter' => '<rect x="3" y="2" width="18" height="20" rx="3"/><path d="m7 17 5-10 5 10m-8-3h6"/>',
        'flame' => '<path fill="currentColor" stroke="none" d="M13 1c2 5-3 7-2 11 2-1 4-3 4-6 5 5 7 8 5 12a9 9 0 0 1-16-1C2 12 5 8 8 5c-1 5 0 7 2 8-1-5 4-6 3-12Z"/><path fill="#ffc65c" stroke="none" d="M12 13c-2 2-4 3-3 6a4 4 0 0 0 7-2c0-2-2-3-2-5 0 2-1 3-2 4Z"/>',
        'seedling' => '<path d="M12 22V12"/><path fill="currentColor" stroke="none" d="M12 15C3 16 2 10 2 6c7 0 11 2 10 9Zm0-3C11 4 16 2 22 2c0 7-3 11-10 10Z"/>',
    ];
@endphp
@if(isset($paths[$name]))
<svg class="learning-icon" width="{{ $size ?? 24 }}" height="{{ $size ?? 24 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] !!}</svg>
@else
@include('ai-tutor::partials.icon', ['name' => $name, 'size' => $size ?? 24])
@endif
