@php
    $currentLocale = app()->getLocale();
@endphp

<div class="relative inline-block text-left" x-data="{ langOpen: false }" @click.outside="langOpen = false">
    {{-- Trigger Button (Clean Minimalist Pill) --}}
    <button @click="langOpen = !langOpen" 
            type="button" 
            class="flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-1 sm:py-1.5 rounded-full bg-slate-800/90 hover:bg-slate-700 border border-slate-700 hover:border-indigo-500/60 text-xs text-gray-200 font-medium transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
        
        {{-- Globe Icon --}}
        <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
        </svg>

        {{-- Current Language Label --}}
        <span class="font-semibold hidden sm:inline">{{ $currentLocale === 'vi' ? 'Tiếng Việt' : 'English' }}</span>
        <span class="font-bold sm:hidden text-[11px] uppercase font-mono">{{ $currentLocale }}</span>

        {{-- Dropdown Arrow --}}
        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0" 
             :class="langOpen ? 'rotate-180 text-indigo-300' : ''" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown Menu: Ultra Minimalist, 100% Solid Dark, No Redundant Text --}}
    <div x-show="langOpen" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         style="background-color: #0b1120; display: none;"
         class="absolute right-0 mt-2 w-48 rounded-xl shadow-[0_20px_50px_rgba(0,0,0,0.95)] border border-slate-700/80 p-1.5 z-[100]">
        
        {{-- Option 1: Tiếng Việt (vi) --}}
        <a href="{{ route('language.switch', 'vi') }}" 
           class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors {{ $currentLocale === 'vi' ? 'bg-indigo-600/25 text-white font-bold' : 'text-gray-300 hover:bg-slate-800/90 hover:text-white' }}">
            <div class="flex items-center gap-2.5">
                {{-- Circular Vietnam Flag --}}
                <div class="w-5 h-5 rounded-full overflow-hidden flex-shrink-0 ring-1 ring-white/20 shadow-sm">
                    <svg viewBox="0 0 30 20" class="w-full h-full object-cover">
                        <rect width="30" height="20" fill="#da251d"/>
                        <polygon points="15,4 16.5,8.8 21.5,8.8 17.5,11.8 19,16.5 15,13.5 11,16.5 12.5,11.8 8.5,8.8 13.5,8.8" fill="#ffff00"/>
                    </svg>
                </div>
                <span class="text-xs font-semibold whitespace-nowrap">Tiếng Việt (vi)</span>
            </div>
            @if($currentLocale === 'vi')
                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            @endif
        </a>

        {{-- Option 2: English (en) --}}
        <a href="{{ route('language.switch', 'en') }}" 
           class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors {{ $currentLocale === 'en' ? 'bg-indigo-600/25 text-white font-bold' : 'text-gray-300 hover:bg-slate-800/90 hover:text-white' }}">
            <div class="flex items-center gap-2.5">
                {{-- Circular UK Flag --}}
                <div class="w-5 h-5 rounded-full overflow-hidden flex-shrink-0 ring-1 ring-white/20 shadow-sm">
                    <svg viewBox="0 0 60 30" class="w-full h-full object-cover">
                        <clipPath id="uk-flag-min"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
                        <clipPath id="uk-flag-min-c"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
                        <g clip-path="url(#uk-flag-min)">
                            <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
                            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
                            <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#uk-flag-min-c)" stroke="#C8102E" stroke-width="4"/>
                            <path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/>
                            <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
                        </g>
                    </svg>
                </div>
                <span class="text-xs font-semibold whitespace-nowrap">English (en)</span>
            </div>
            @if($currentLocale === 'en')
                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            @endif
        </a>
    </div>
</div>
