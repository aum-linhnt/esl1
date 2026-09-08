{{-- Mobile App-Like Bottom Navigation Bar (Hidden on Desktop) --}}
<nav class="fixed bottom-0 inset-x-0 z-40 bg-[#070c18]/95 backdrop-blur-2xl border-t border-slate-800/90 px-2 py-1.5 lg:hidden shadow-[0_-8px_25px_rgba(0,0,0,0.6)]"
     style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));">
    <div class="grid grid-cols-5 items-center justify-around">
        {{-- 1. Trang chủ --}}
        <a href="{{ route('dashboard') }}" 
           class="flex flex-col items-center justify-center py-1 transition-all group relative {{ request()->routeIs('dashboard') ? 'text-sky-400' : 'text-slate-400 hover:text-slate-200' }}">
            <div class="relative p-1 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-sky-500/15 ring-1 ring-sky-500/30' : '' }}">
                <svg class="w-5 h-5 transition-transform group-active:scale-90" fill="{{ request()->routeIs('dashboard') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 whitespace-nowrap {{ request()->routeIs('dashboard') ? 'font-bold text-sky-300' : 'font-medium' }}">{{ app()->getLocale() === 'vi' ? 'Trang chủ' : 'Home' }}</span>
            @if(request()->routeIs('dashboard'))
                <span class="absolute -bottom-0.5 w-1 h-1 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.8)]"></span>
            @endif
        </a>

        {{-- 2. Khóa học --}}
        <a href="{{ route('courses.index') }}" 
           class="flex flex-col items-center justify-center py-1 transition-all group relative {{ request()->routeIs('courses.*') || request()->routeIs('enrollments.*') ? 'text-indigo-400' : 'text-slate-400 hover:text-slate-200' }}">
            <div class="relative p-1 rounded-xl transition-all {{ request()->routeIs('courses.*') || request()->routeIs('enrollments.*') ? 'bg-indigo-500/15 ring-1 ring-indigo-500/30' : '' }}">
                <svg class="w-5 h-5 transition-transform group-active:scale-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 whitespace-nowrap {{ request()->routeIs('courses.*') || request()->routeIs('enrollments.*') ? 'font-bold text-indigo-300' : 'font-medium' }}">{{ app()->getLocale() === 'vi' ? 'Khóa học' : 'Courses' }}</span>
            @if(request()->routeIs('courses.*') || request()->routeIs('enrollments.*'))
                <span class="absolute -bottom-0.5 w-1 h-1 rounded-full bg-indigo-400 shadow-[0_0_8px_rgba(99,102,241,0.8)]"></span>
            @endif
        </a>

        {{-- 3. Luyện tập --}}
        <a href="{{ route('practice.index') }}" 
           class="flex flex-col items-center justify-center py-1 transition-all group relative {{ request()->routeIs('practice.*') ? 'text-teal-400' : 'text-slate-400 hover:text-slate-200' }}">
            <div class="relative p-1 rounded-xl transition-all {{ request()->routeIs('practice.*') ? 'bg-teal-500/15 ring-1 ring-teal-500/30' : '' }}">
                <svg class="w-5 h-5 transition-transform group-active:scale-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 whitespace-nowrap {{ request()->routeIs('practice.*') ? 'font-bold text-teal-300' : 'font-medium' }}">{{ app()->getLocale() === 'vi' ? 'Luyện tập' : 'Practice' }}</span>
            @if(request()->routeIs('practice.*'))
                <span class="absolute -bottom-0.5 w-1 h-1 rounded-full bg-teal-400 shadow-[0_0_8px_rgba(45,212,191,0.8)]"></span>
            @endif
        </a>

        {{-- 4. Bảng xếp hạng --}}
        <a href="{{ route('leaderboard.index') }}" 
           class="flex flex-col items-center justify-center py-1 transition-all group relative {{ request()->routeIs('leaderboard.*') ? 'text-amber-400' : 'text-slate-400 hover:text-slate-200' }}">
            <div class="relative p-1 rounded-xl transition-all {{ request()->routeIs('leaderboard.*') ? 'bg-amber-500/15 ring-1 ring-amber-500/30' : '' }}">
                <svg class="w-5 h-5 transition-transform group-active:scale-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-[10px] tracking-tight mt-0.5 whitespace-nowrap {{ request()->routeIs('leaderboard.*') ? 'font-bold text-amber-300' : 'font-medium' }}">{{ app()->getLocale() === 'vi' ? 'Xếp hạng' : 'Ranking' }}</span>
            @if(request()->routeIs('leaderboard.*'))
                <span class="absolute -bottom-0.5 w-1 h-1 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.8)]"></span>
            @endif
        </a>

        {{-- 5. Menu / Thêm --}}
        <button type="button" 
                @click="sidebarOpen = true"
                class="flex flex-col items-center justify-center py-1 text-slate-400 hover:text-white transition-all group">
            <div class="p-1 rounded-xl transition-all group-active:scale-90">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                </svg>
            </div>
            <span class="text-[10px] font-medium tracking-tight mt-0.5">Menu</span>
        </button>
    </div>
</nav>
