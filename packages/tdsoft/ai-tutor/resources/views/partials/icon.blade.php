{{-- Shared outline icons; decorative SVGs inherit their surrounding text color. --}}
<svg class="learning-icon" width="{{ $size ?? 20 }}" height="{{ $size ?? 20 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('sun')
            <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>
            @break
        @case('moon')
            <path d="M20.5 14A9 9 0 0 1 10 3.5 9 9 0 1 0 20.5 14Z"/>
            @break
        @case('book')
            <path d="M12 5c-3-2-6-2-10-1v15c4-1 7-1 10 1 3-2 6-2 10-1V4c-4-1-7-1-10 1Z"/><path d="M12 5v15"/>
            @break
        @case('play')
            <circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4Z"/>
            @break
        @case('cards')
            <rect x="7" y="5" width="13" height="16" rx="2"/><path d="M4 17H3a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1M11 10h5m-5 4h3"/>
            @break
        @case('clipboard')
            <rect x="5" y="4" width="14" height="18" rx="2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="m8 13 3 3 5-6"/>
            @break
        @case('document')
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 0v6h6M8 12h8m-8 4h6"/>
            @break
        @case('grid')
            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
            @break
        @case('robot')
            <rect x="4" y="7" width="16" height="14" rx="4"/><path d="M12 7V4M1 12v5m22-5v5M9 16h6"/><circle cx="12" cy="3" r="1"/><path d="M8 11v1m8-1v1"/>
            @break
        @case('sparkles')
            <path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5ZM20 2v4m-2-2h4"/>
            @break
        @case('lock')
            <rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>
            @break
        @case('check')
            <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>
            @break
        @case('target')
            <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>
            @break
        @case('arrow-left')
            <path d="M20 12H4m6-6-6 6 6 6"/>
            @break
        @case('arrow-right')
            <path d="M4 12h16m-6-6 6 6-6 6"/>
            @break
        @case('chevron-right')
            <path d="m9 5 7 7-7 7"/>
            @break
        @case('external')
            <path d="M14 3h7v7m0-7L10 14m0-10H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-5"/>
            @break
        @case('close')
            <path d="m6 6 12 12M6 18 18 6"/>
            @break
        @case('more')
            <circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>
            @break
        @case('send')
            <path d="m22 2-7 20-4-9-9-4Zm0 0L11 13"/>
            @break
    @endswitch
</svg>
