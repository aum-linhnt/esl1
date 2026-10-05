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
<header class="sticky top-0 z-40 bg-[#0a0f1d]/90 backdrop-blur-xl border-b border-slate-800/60 px-2.5 sm:px-6 lg:px-8 py-2 sm:py-3 w-full max-w-full">
    <div class="flex items-center justify-between gap-1.5 sm:gap-3 max-w-full">
        {{-- Left: Mobile Brand Logo or Desktop Page Title Pill --}}
        <div class="flex items-center gap-2 min-w-0 flex-shrink-0">
            {{-- Mobile Brand Logo (replacing hamburger + title pill on mobile) --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 sm:gap-2 lg:hidden group flex-shrink-0" title="Trang chủ ESL LMS">
                <div class="w-7 h-7 sm:w-8 sm:h-8 flex-shrink-0 transition-transform group-active:scale-95">
                    <svg viewBox="0 0 48 48" class="w-full h-full drop-shadow-sm">
                        <circle cx="24" cy="24" r="22" fill="none" stroke="var(--theme-primary, #6366f1)" stroke-width="1.5" opacity="0.6"/>
                        <circle cx="24" cy="24" r="16" fill="none" stroke="var(--theme-primary, #818cf8)" stroke-width="1" opacity="0.4"/>
                        <circle cx="24" cy="24" r="4" fill="var(--theme-primary, #a5b4fc)"/>
                        <circle cx="24" cy="8" r="2" fill="#fbbf24"/>
                    </svg>
                </div>
                <div class="flex items-center gap-1">
                    <span class="brand-logo-text text-base sm:text-lg font-black tracking-tight text-white font-sans leading-none">
                        <span class="brand-letter-e">E</span><span class="text-fsel-accent">S</span><span class="text-fsel-teal">L</span>
                    </span>
                    <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wider text-slate-400 bg-slate-800/80 border border-slate-700/60 px-1 py-0.5 rounded leading-none hidden xs:inline">
                        LMS
                    </span>
                </div>
            </a>

            {{-- Desktop Active Page Title Pill --}}
            <div class="header-page-title hidden lg:block px-5 py-2 rounded-xl bg-[#141d35] border border-slate-700/70 shadow-md text-white font-bold text-sm tracking-wide select-none">
                {{ $headerPillTitle }}
            </div>
        </div>

        {{-- Right: Actions (Language, Theme, Streak, Golden Coin Badge, Ringed Avatar) --}}
        <div class="flex items-center gap-1 sm:gap-2.5 flex-shrink-0">

            {{-- Theme Switcher (Color Palette & Dark/Light Mode) --}}
            <x-theme-switcher />

            {{-- Language Switcher (hidden on mobile, in menu instead) --}}
            <div class="hidden sm:block">
                <x-language-switcher />
            </div>

            {{-- Notifications & Messages Dropdown / Icon --}}
            <x-header-notifications />

            {{-- Streak Badge (Uniform with XP Badge) --}}
            <a href="{{ route('leaderboard.index') }}" 
               class="header-stat-pill h-[32px] sm:h-9 px-2 sm:px-3 rounded-xl bg-[#141d35] border border-slate-700/60 hover:border-amber-500/50 transition-all shadow-md group flex items-center gap-1 sm:gap-2 flex-shrink-0"
               title="{{ __('messages.common.streak') }}">
                {{-- Fire Icon Box --}}
                <div class="w-5 h-5 sm:w-5.5 sm:h-5.5 rounded-lg bg-gradient-to-tr from-amber-600 via-orange-500 to-amber-400 flex items-center justify-center text-amber-950 shadow-sm group-hover:scale-105 transition-transform flex-shrink-0">
                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 fill-current" viewBox="0 0 24 24">
                        <path d="M12 23c-4.97 0-9-3.8-9-8.5 0-3.5 2.2-6.5 4.5-9 1-1.1 2.1-2.2 3.1-3.5.3-.4.8-.6 1.3-.4.4.2.7.6.7 1.1 0 2.2.8 4.3 2.1 5.9.6-.7 1.2-1.5 1.7-2.3.2-.4.7-.6 1.1-.5.4.1.7.5.8.9.7 2.7 1.7 5.1 2.7 7.8 0 0 .1.2.1.3 0 4.7-4 8.2-9 8.2z"/>
                    </svg>
                </div>
                <span class="header-streak-text text-xs sm:text-sm font-bold font-mono text-amber-400 leading-none">
                    {{ Auth::user()->streak_count ?? 1 }}
                </span>
                <span class="text-[10px] text-slate-400 hidden md:inline font-mono">ngày</span>
            </a>

            {{-- Golden Coin / XP Pill (Uniform with Streak Badge, hidden on mobile screens < 640px to prevent clutter) --}}
            <a href="{{ route('leaderboard.index') }}" 
               class="header-stat-pill hidden sm:flex h-[34px] sm:h-9 px-2.5 sm:px-3 rounded-xl bg-[#141d35] border border-slate-700/60 hover:border-yellow-500/50 transition-all shadow-md group items-center gap-1.5 sm:gap-2 flex-shrink-0"
               title="Điểm kinh nghiệm (XP)">
                {{-- Golden Shield / Coin Icon Box --}}
                <div class="w-5 h-5 sm:w-5.5 sm:h-5.5 rounded-lg bg-gradient-to-tr from-amber-500 via-yellow-400 to-amber-300 flex items-center justify-center text-amber-950 font-black shadow-sm group-hover:scale-105 transition-transform flex-shrink-0">
                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 fill-current" viewBox="0 0 24 24">
                        <polygon points="12 2 22 8.5 18 21 6 21 2 8.5" fill="currentColor"/>
                    </svg>
                </div>
                <span class="header-xp-text text-xs sm:text-sm font-bold font-mono text-white leading-none" id="xp-count">
                    {{ number_format(Auth::user()->xp ?? 100) }}
                </span>
            </a>

            {{-- User Avatar & Profile Dropdown --}}
            <div class="relative flex-shrink-0" x-data="{ profileOpen: false }">
                <button @click="profileOpen = !profileOpen" 
                        class="header-avatar-btn w-[32px] h-[32px] sm:w-9 sm:h-9 rounded-full ring-2 ring-slate-300 dark:ring-sky-400 ring-offset-2 ring-offset-white dark:ring-offset-[#0a0f1d] hover:ring-indigo-500 transition-all shadow-sm flex-shrink-0 overflow-hidden bg-gradient-to-tr from-indigo-600 via-blue-600 to-teal-400 flex items-center justify-center cursor-pointer">
                    <img src="{{ Auth::user()->avatar_url }}" 
                         alt="{{ Auth::user()->name }}" 
                         class="w-full h-full object-cover"
                         onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                </button>

                {{-- Dropdown Card --}}
                <div x-show="profileOpen" 
                     x-cloak 
                     style="display: none;" 
                     @click.outside="profileOpen = false" 
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                     class="header-profile-dropdown absolute right-0 mt-2 p-1.5 shadow-2xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 z-[100] w-72 sm:w-64 max-w-[calc(100vw-1rem)]">
                    
                    {{-- User mini header --}}
                    <div class="header-profile-user-box px-3 py-2.5 mb-1.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/40">
                        <div class="flex items-center justify-between gap-2">
                            <p class="header-profile-name text-xs font-bold text-slate-800 dark:text-white truncate">{{ Auth::user()->name }}</p>
                            @if(Auth::user()->isAdmin())
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">Admin</span>
                            @elseif(Auth::user()->isTeacher())
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300">Teacher</span>
                            @endif
                        </div>
                        <p class="header-profile-email text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ Auth::user()->email }}</p>
                    </div>

                    <div class="space-y-0.5">
                        @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                            <a href="{{ route('admin.dashboard') }}" 
                               class="header-profile-item flex items-center gap-2.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-800/80 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <div class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <span>{{ __('messages.nav.admin_portal') }}</span>
                            </a>
                        @endif

                        <a href="{{ route('profile.edit') }}" 
                           class="header-profile-item flex items-center gap-2.5 px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white transition-colors">
                            <div class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <span>{{ __('messages.nav.profile') }}</span>
                        </a>
                    </div>

                    {{-- Mobile Language Switcher --}}
                    <div class="sm:hidden my-1 px-3 py-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Ngôn ngữ:</span>
                        <div class="flex items-center gap-1.5 font-bold font-mono">
                            <a href="{{ route('language.switch', 'vi') }}" class="px-2 py-0.5 rounded {{ app()->getLocale() === 'vi' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">VI</a>
                            <span class="text-slate-600">·</span>
                            <a href="{{ route('language.switch', 'en') }}" class="px-2 py-0.5 rounded {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white' }}">EN</a>
                        </div>
                    </div>

                    <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit" 
                                class="header-profile-logout flex items-center gap-2.5 w-full px-3 py-2 text-xs font-semibold rounded-xl text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors text-left cursor-pointer">
                            <div class="w-6 h-6 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </div>
                            <span>{{ __('messages.nav.logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
