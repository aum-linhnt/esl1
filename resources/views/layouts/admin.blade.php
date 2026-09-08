<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'ESL LMS Admin Portal' }} - Quản trị Hệ thống</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Alpine.js & Tailwind CSS via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #070a12;
        }
        .admin-sidebar-link {
            display: flex;
            items-center: center;
            gap: 0.75rem;
            padding: 0.625rem 0.875rem;
            border-radius: 0.625rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #94a3b8;
            transition: all 0.2s ease;
        }
        .admin-sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(30, 41, 59, 0.6);
        }
        .admin-sidebar-link.active {
            color: #ffffff;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.25) 0%, rgba(59, 130, 246, 0.15) 100%);
            border: 1px solid rgba(99, 102, 241, 0.4);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.15);
        }
        .admin-card {
            background-color: #0f172a;
            border: 1px solid rgba(51, 65, 85, 0.6);
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
        }
        select,
        select.login-input {
            transition: none !important;
            cursor: pointer;
        }
        select option {
            background-color: #0f172a !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body class="min-h-screen text-gray-100 antialiased" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">

    {{-- 1. ADMIN SIDEBAR --}}
    <aside class="fixed inset-y-0 left-0 z-50 bg-[#090d1a] border-r border-slate-800/80 transition-all duration-300 flex flex-col"
           :class="sidebarOpen ? 'w-64' : 'w-20 hidden md:flex'">

        {{-- Brand Header --}}
        <div class="flex items-center justify-between px-4 py-4 border-b border-slate-800/80">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5" :class="!sidebarOpen && 'justify-center w-full'">
                <div class="w-9 h-9 flex-shrink-0 bg-gradient-to-tr from-indigo-600 via-blue-500 to-teal-400 rounded-xl p-0.5 shadow-lg shadow-indigo-500/20 flex items-center justify-center">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                    <div class="flex items-center gap-1.5">
                        <span class="text-lg font-black text-white tracking-wide">ESL</span>
                        <span class="bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-[9px] font-bold px-1.5 py-0.2 rounded uppercase">Admin</span>
                    </div>
                    <p class="text-[9px] text-gray-400 tracking-wider">Enterprise LMS Control</p>
                </div>
            </a>

            <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors hidden md:block">
                <svg x-show="sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                <svg x-show="!sidebarOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
            </button>
        </div>

        {{-- Navigation Links --}}
        <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
            
            <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span x-show="sidebarOpen">Tổng quan (Dashboard)</span>
            </a>

            <div class="pt-3 pb-1">
                <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">Hệ thống Đào tạo</p>
            </div>

            <a href="{{ route('admin.users.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span x-show="sidebarOpen">Quản lý Người dùng</span>
            </a>

            <a href="{{ route('admin.roles.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span x-show="sidebarOpen">Vai trò & Phân quyền</span>
            </a>

            <a href="{{ route('admin.courses.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span x-show="sidebarOpen">Khóa học & Bài giảng</span>
            </a>

            <a href="{{ route('admin.exams.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-show="sidebarOpen">Quản lý Đề thi & Khảo thí</span>
            </a>

            <a href="{{ route('admin.questions.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-show="sidebarOpen">Ngân hàng Câu hỏi</span>
            </a>

            <div class="pt-3 pb-1">
                <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">Đánh giá & AI Engine</p>
            </div>

            <a href="{{ route('admin.submissions.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.submissions.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span x-show="sidebarOpen">Lịch sử Chấm & Bài nộp</span>
            </a>

            <a href="{{ route('teacher.ai_generator.index') }}" class="admin-sidebar-link {{ request()->routeIs('teacher.ai_generator.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-fsel-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span x-show="sidebarOpen">AI Exercise Generator</span>
            </a>

            <a href="{{ route('admin.gamification.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.gamification.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                <span x-show="sidebarOpen">Gamification & Badges</span>
            </a>

            <div class="pt-3 pb-1">
                <p x-show="sidebarOpen" class="px-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">Hệ thống</p>
            </div>

            <a href="{{ route('admin.settings.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="sidebarOpen">Cấu hình LMS</span>
            </a>
        </nav>

        {{-- Footer Actions: Back to Student Portal & Logout --}}
        <div class="p-3 border-t border-slate-800/80 space-y-2">
            <a href="{{ route('dashboard') }}" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-gray-300 bg-slate-800/60 hover:bg-slate-700/60 hover:text-white transition-colors" :class="!sidebarOpen && 'justify-center'">
                <svg class="w-4 h-4 text-fsel-teal flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span x-show="sidebarOpen">{{ __('messages.nav.back_to_student') }}</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors" :class="!sidebarOpen && 'justify-center'">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span x-show="sidebarOpen">{{ __('messages.nav.logout') }}</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- 2. MAIN ADMIN CONTENT WRAPPER --}}
    <div class="transition-all duration-300 min-h-screen flex flex-col"
         :class="sidebarOpen ? 'md:ml-64' : 'md:ml-20'">

        {{-- Admin Top Navigation Bar --}}
        <header class="sticky top-0 z-40 bg-[#090d1a]/90 backdrop-blur-md border-b border-slate-800/80 px-4 sm:px-8 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-400 hover:text-white md:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            {{-- Right Admin Actions: Language Switcher & Profile Info --}}
            <div class="flex items-center gap-4">
                {{-- Moodle-Style Language Switcher --}}
                <x-language-switcher />

                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <span class="text-xs font-bold text-white block">{{ Auth::user()->name }}</span>
                        <span class="text-[10px] text-fsel-teal font-mono uppercase font-semibold">{{ Auth::user()->role }}</span>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 flex items-center justify-center text-xs font-black text-white shadow-md overflow-hidden">
                        <img src="{{ Auth::user()->avatar_url }}" 
                             alt="{{ Auth::user()->name }}" 
                             class="w-full h-full object-cover"
                             onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                    </div>
                </div>

            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mx-4 sm:mx-8 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between">
                <span>✓ {{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
            </div>
        @endif
        @if(session('error'))
            <div class="mx-4 sm:mx-8 mt-4 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-center justify-between">
                <span>⚠ {{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-white">&times;</button>
            </div>
        @endif

        {{-- Main Page Content --}}
        <main class="flex-1 p-4 sm:p-8">
            @yield('content')
        </main>

        {{-- Admin Footer --}}
        <footer class="border-t border-slate-800/60 px-4 sm:px-8 py-4 text-xs text-gray-500 flex flex-col sm:flex-row items-center justify-between gap-2">
            <span>ESL English LMS &copy; {{ date('Y') }} — ESL E-Learning Platform.</span>
            <span class="font-mono text-[11px]">System Status: Operational</span>
        </footer>
    </div>
</body>
</html>
