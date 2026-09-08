@php
    $headerPillTitle = $title ?? null;
    if (!$headerPillTitle) {
        if (request()->routeIs('dashboard')) $headerPillTitle = 'Trang chủ';
        elseif (request()->routeIs('courses.*')) $headerPillTitle = 'Khóa học';
        elseif (request()->routeIs('lessons.*')) $headerPillTitle = 'Bài học';
        elseif (request()->routeIs('activities.*')) $headerPillTitle = 'Nội dung học';
        elseif (request()->routeIs('my-courses.*') || request()->routeIs('enrollments.*')) $headerPillTitle = 'Khóa của tôi';
        elseif (request()->routeIs('gradebook.*')) $headerPillTitle = 'Sổ điểm';
        elseif (request()->routeIs('practice.*')) $headerPillTitle = 'Luyện đề';
        elseif (request()->routeIs('progress.*')) $headerPillTitle = 'Tiến trình';
        elseif (request()->routeIs('leaderboard.*')) $headerPillTitle = 'Bảng xếp hạng';
        elseif (request()->routeIs('marketplace.*')) $headerPillTitle = 'Marketplace';
        elseif (request()->routeIs('ai.writing')) $headerPillTitle = 'AI Writing';
        elseif (request()->routeIs('ai.speaking')) $headerPillTitle = 'AI Speaking';
        elseif (request()->routeIs('profile.*')) $headerPillTitle = 'Hồ sơ cá nhân';
        else $headerPillTitle = 'Trang chủ';
    }
@endphp

{{-- Top Header (Minimalist & Modern LMS Style) --}}
<header class="sticky top-0 z-30 bg-[#0a0f1d]/90 backdrop-blur-xl border-b border-slate-800/60 px-3.5 sm:px-6 lg:px-8 py-2.5 sm:py-3 w-full max-w-full">
    <div class="flex items-center justify-between gap-3 max-w-full">
        {{-- Left: Active Page Title Pill --}}
        <div class="flex items-center gap-2.5 min-w-0">
            {{-- Mobile Sidebar Toggle --}}
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="lg:hidden text-slate-400 hover:text-white p-1.5 rounded-xl hover:bg-slate-800/80 transition-colors flex-shrink-0"
                    title="Mở menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Rounded Pill Header Button (matching reference) --}}
            <div class="px-4 sm:px-5 py-2 rounded-xl bg-[#141d35] border border-slate-700/70 shadow-md text-white font-bold text-xs sm:text-sm tracking-wide select-none">
                {{ $headerPillTitle }}
            </div>
        </div>

        {{-- Right: Actions (Language, Streak, Golden Coin Badge, Ringed Avatar) --}}
        <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">

            {{-- Language Switcher (hidden on mobile, in menu instead) --}}
            <div class="hidden sm:block">
                <x-language-switcher />
            </div>

            {{-- Streak Badge --}}
            <a href="{{ route('leaderboard.index') }}" 
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#141d35] border border-slate-700/60 hover:border-amber-500/50 transition-all shadow-md group"
               title="{{ __('messages.common.streak') }}">
                <span class="text-sm group-hover:scale-110 transition-transform">🔥</span>
                <span class="text-xs sm:text-sm font-bold font-mono text-amber-400">
                    {{ Auth::user()->streak_count ?? 1 }}
                </span>
                <span class="text-[10px] text-slate-400 hidden md:inline font-mono">ngày</span>
            </a>

            {{-- Golden Coin / XP Pill (matching golden badge in screenshot) --}}
            <a href="{{ route('leaderboard.index') }}" 
               class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#141d35] border border-slate-700/60 hover:border-yellow-500/50 transition-all shadow-md group"
               title="Điểm kinh nghiệm (XP)">
                {{-- Golden Shield / Coin Icon --}}
                <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-amber-500 via-yellow-400 to-amber-300 flex items-center justify-center text-slate-950 font-black text-xs shadow-sm group-hover:scale-105 transition-transform flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-amber-950 fill-current" viewBox="0 0 24 24">
                        <polygon points="12 2 22 8.5 18 21 6 21 2 8.5" fill="currentColor"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-bold font-mono text-white" id="xp-count">
                    {{ number_format(Auth::user()->xp ?? 100) }}
                </span>
            </a>

            {{-- User Avatar (Circular with sky-blue ring matching screenshot) --}}
            <div class="relative" x-data="{ profileOpen: false }">
                <button @click="profileOpen = !profileOpen" 
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-full ring-2 ring-sky-400 ring-offset-2 ring-offset-[#0a0f1d] hover:ring-sky-300 transition-all shadow-lg flex-shrink-0 overflow-hidden bg-gradient-to-tr from-indigo-600 via-blue-600 to-teal-400 flex items-center justify-center cursor-pointer">
                    <img src="{{ Auth::user()->avatar_url }}" 
                         alt="{{ Auth::user()->name }}" 
                         class="w-full h-full object-cover"
                         onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                </button>

                {{-- Dropdown --}}
                <div x-show="profileOpen" x-cloak style="display: none; background-color: #0f172a; min-width: 220px; width: max-content; border: 1px solid rgba(51, 65, 85, 0.8); border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.85); z-index: 100;" @click.outside="profileOpen = false" x-transition
                     class="absolute right-0 mt-2 py-2 overflow-hidden shadow-2xl">
                    
                    {{-- User mini header --}}
                    <div class="px-4 py-2 mb-1" style="border-bottom: 1px solid rgba(51, 65, 85, 0.6);">
                        <p class="text-xs font-bold text-white truncate" style="color: #ffffff;">{{ Auth::user()->name }}</p>
                        <p class="text-[11px] text-slate-400 truncate" style="color: #94a3b8;">{{ Auth::user()->email }}</p>
                    </div>

                    @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-bold hover:bg-slate-800 transition-colors whitespace-nowrap"
                           style="color: #2dd4bf;">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="whitespace-nowrap">{{ __('messages.nav.admin_portal') }}</span>
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}" 
                       class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium hover:text-white hover:bg-slate-800 transition-colors whitespace-nowrap"
                       style="color: #cbd5e1;">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span class="whitespace-nowrap">{{ __('messages.nav.profile') }}</span>
                    </a>

                    {{-- Mobile Language Switcher --}}
                    <div class="sm:hidden px-4 py-2 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Ngôn ngữ:</span>
                        <div class="flex items-center gap-1.5 font-bold font-mono">
                            <a href="{{ route('language.switch', 'vi') }}" class="px-2 py-0.5 rounded {{ app()->getLocale() === 'vi' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">VI</a>
                            <span class="text-slate-600">·</span>
                            <a href="{{ route('language.switch', 'en') }}" class="px-2 py-0.5 rounded {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">EN</a>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit" 
                                class="flex items-center gap-2.5 w-full px-4 py-2.5 text-xs font-medium hover:text-red-300 hover:bg-slate-800 transition-colors text-left whitespace-nowrap cursor-pointer"
                                style="color: #f87171;">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span class="whitespace-nowrap">{{ __('messages.nav.logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
