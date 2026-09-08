{{-- Mobile Backdrop Overlay --}}
<div x-show="sidebarOpen" 
     x-cloak
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-black/75 backdrop-blur-sm z-40 lg:hidden"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"></div>

{{-- Sidebar (Off-canvas drawer on mobile, docked bar on desktop) --}}
<aside class="fixed inset-y-0 left-0 z-50 bg-fsel-sidebar border-r border-fsel-border/30 transition-transform lg:transition-all duration-300 flex flex-col shadow-2xl lg:shadow-none -translate-x-full lg:translate-x-0 w-72 max-w-[85vw] lg:w-64"
       :class="{
           '!translate-x-0 w-72 max-w-[85vw] lg:w-64': sidebarOpen,
           '-translate-x-full lg:translate-x-0 lg:w-20': !sidebarOpen
       }">

    {{-- Logo & Toggle --}}
    <div class="flex items-center justify-between px-4 py-4 sm:py-5 border-b border-fsel-border/20">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5" :class="!sidebarOpen && 'lg:justify-center lg:w-full'">
            <div class="w-9 h-9 flex-shrink-0">
                <svg viewBox="0 0 48 48" class="w-full h-full">
                    <circle cx="24" cy="24" r="22" fill="none" stroke="#6366f1" stroke-width="1.5" opacity="0.6"/>
                    <circle cx="24" cy="24" r="16" fill="none" stroke="#818cf8" stroke-width="1" opacity="0.4"/>
                    <circle cx="24" cy="24" r="4" fill="#a5b4fc"/>
                    <circle cx="24" cy="8" r="2" fill="#fbbf24"/>
                </svg>
            </div>
            <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                <span class="text-xl font-bold text-white tracking-wide">E<span class="text-fsel-accent">S</span><span class="text-fsel-teal">L</span></span>
                <p class="text-[8px] text-gray-400 tracking-[0.15em] -mt-0.5 italic">Reach for the stars</p>
            </div>
        </a>

        <div class="flex items-center gap-1">
            {{-- Desktop Toggle Button --}}
            <button @click="sidebarOpen = !sidebarOpen"
                    class="text-gray-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-fsel-sidebar-hover hidden lg:block"
                    :class="!sidebarOpen && 'absolute -right-3 top-7 bg-fsel-sidebar border border-fsel-border rounded-full p-1'"
                    style="z-index: 50;">
                <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <svg x-show="!sidebarOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </button>

            {{-- Mobile Close Button --}}
            <button @click="sidebarOpen = false" 
                    class="text-gray-400 hover:text-white p-1.5 rounded-xl hover:bg-slate-800 transition-colors lg:hidden"
                    title="Đóng menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.dashboard') }}</span>
        </a>
        <a href="{{ route('courses.index') }}" class="sidebar-link {{ request()->routeIs('courses.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.learn') }}</span>
        </a>
        <a href="{{ route('enrollments.myCourses') }}" class="sidebar-link {{ request()->routeIs('enrollments.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.my_courses') }}</span>
        </a>
        <a href="{{ route('gradebook.index') }}" class="sidebar-link {{ request()->routeIs('gradebook.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.gradebook') }}</span>
        </a>
        <a href="{{ route('practice.index') }}" class="sidebar-link {{ request()->routeIs('practice.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.practice') }}</span>
        </a>

        <a href="{{ route('progress.index') }}" class="sidebar-link {{ request()->routeIs('progress.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.progress') }}</span>
        </a>

        <a href="{{ route('leaderboard.index') }}" class="sidebar-link {{ request()->routeIs('leaderboard.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.leaderboard') }}</span>
        </a>

        <a href="{{ route('marketplace.index') }}" class="sidebar-link {{ request()->routeIs('marketplace.*') ? 'active' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.marketplace') }}</span>
        </a>

        {{-- AI Modules --}}
        <div class="pt-2 pb-1">
            <p x-show="sidebarOpen" class="px-4 mb-1.5 text-[10px] uppercase tracking-wider text-fsel-teal font-semibold">{{ __('messages.nav.ai_tools') }}</p>
            <a href="{{ route('ai.writing.index') }}" class="sidebar-link {{ request()->routeIs('ai.writing.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.ai_writing') }}</span>
            </a>
            <a href="{{ route('ai.speaking.index') }}" class="sidebar-link {{ request()->routeIs('ai.speaking.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                <span x-show="sidebarOpen" x-transition>{{ __('messages.nav.ai_speaking') }}</span>
            </a>
        </div>



    </nav>

    {{-- Bottom CTA Banner --}}
    <div x-show="sidebarOpen" x-transition class="px-3 pb-4">
        <a href="{{ route('renew') }}" class="block">
            <div class="relative rounded-xl overflow-hidden bg-gradient-to-br from-yellow-500 via-orange-500 to-red-500 p-4 hover:shadow-lg transition-shadow cursor-pointer">
                <div class="relative z-10">
                    <div class="flex items-center gap-1 mb-1">
                        <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">ĐĂNG KÝ</span>
                    </div>
                    <p class="text-white text-xs font-bold leading-tight">NÂNG CẤP TÀI KHOẢN HỌC CÙNG AI</p>
                    <p class="text-white/90 text-[10px] mt-0.5">MỞ KHÓA CÁC KHÓA HỌC TIẾP THEO</p>
                    <div class="mt-2">
                        <span class="text-white text-[10px] flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                            CLICK TẠI ĐÂY
                        </span>
                    </div>
                </div>
                <div class="absolute top-2 right-2 opacity-40">
                    <span class="text-[10px] font-bold text-white">ESL</span>
                </div>
            </div>
        </a>
    </div>
</aside>
