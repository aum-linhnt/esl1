@extends('layouts.app')

@section('content')
<div class="w-full space-y-6 sm:space-y-8">
    
    @if($isTrialExpired)
        {{-- Smart Renewal Notice Banner (Unobtrusive) --}}
        <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-500/20 via-orange-500/15 to-indigo-500/20 border border-amber-500/40 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3.5">
                <span class="text-3xl">⏳</span>
                <div>
                    <h3 class="text-sm font-bold text-amber-300">Tài khoản học thử của bạn đã hết hạn</h3>
                    <p class="text-xs text-gray-300">Gia hạn gói tài khoản để mở khóa toàn bộ bài giảng nâng cao và tính năng AI chấm bài không giới hạn.</p>
                </div>
            </div>
            <a href="{{ route('renew') }}" class="btn-primary !w-auto !py-2.5 px-6 text-xs font-bold whitespace-nowrap shadow-glow-blue flex-shrink-0">
                Gia hạn tài khoản
            </a>
        </div>
    @endif

        {{-- ─── 1. HERO BANNER: STREAK FLAME & XP RADAR (12-COL HARMONIOUS BENTO) ─── --}}
        <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-r from-slate-900/90 via-[#0e1628]/90 to-slate-900/90 border border-slate-700/50 shadow-2xl backdrop-blur-xl p-4 sm:p-6 lg:p-7">
            {{-- Ambient Glows --}}
            <div class="absolute -top-24 -left-24 w-72 h-72 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 right-10 w-80 h-80 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6 items-center">
                {{-- Left: Greeting & User Profile Header (Aligns with Left Column) --}}
                <div class="lg:col-span-7 xl:col-span-8 flex items-center gap-3.5 sm:gap-5 min-w-0">
                    {{-- Avatar --}}
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-indigo-500 via-blue-600 to-teal-400 flex items-center justify-center text-white font-black text-xl sm:text-2xl shadow-xl shadow-indigo-500/20 ring-2 ring-white/15 flex-shrink-0 overflow-hidden">
                        <img src="{{ $user->avatar_url }}" 
                             alt="{{ $user->name }}" 
                             class="w-full h-full object-cover"
                             onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                    </div>

                    {{-- Name & Message --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-0.5 sm:mb-1">
                            <h2 class="text-base sm:text-2xl font-black text-white tracking-tight flex items-center gap-1.5 flex-wrap">
                                <span>Xin chào,</span>
                                <span class="text-sky-300 font-extrabold" style="color: #38bdf8;">{{ $user->name }}</span>
                                <span class="inline-block">👋</span>
                            </h2>
                            {{-- Level Badge --}}
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-teal-500/15 border border-teal-400/30 text-[11px] sm:text-xs font-mono font-semibold text-teal-300 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse"></span>
                                <span>{{ $user->current_level ?: 'A1' }}</span>
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                            Bạn đang duy trì phong độ xuất sắc. Hãy hoàn thành các thử thách hôm nay để bứt phá bảng xếp hạng!
                        </p>
                    </div>
                </div>

                {{-- Right: Modern Glass Stat Cards (Aligns with Right Column) --}}
                <div class="lg:col-span-5 xl:col-span-4 grid grid-cols-3 gap-2 sm:gap-3">
                    {{-- Streak Stat --}}
                    <div class="flex flex-col items-center justify-center text-center p-2.5 sm:p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 hover:border-amber-500/40 backdrop-blur-md shadow-sm transition-all duration-200 min-w-0">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-xs sm:text-sm shadow-inner flex-shrink-0 text-amber-400 mb-1">
                            🔥
                        </div>
                        <div class="text-[9px] sm:text-[10px] uppercase font-bold tracking-wider text-slate-400 truncate w-full">Chuỗi học</div>
                        <div class="text-xs sm:text-sm font-extrabold text-white font-mono truncate w-full">{{ $streakCount }} Ngày</div>
                    </div>

                    {{-- XP Stat --}}
                    <div class="flex flex-col items-center justify-center text-center p-2.5 sm:p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 hover:border-teal-500/40 backdrop-blur-md shadow-sm transition-all duration-200 min-w-0">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-teal-500/15 border border-teal-500/30 flex items-center justify-center text-xs sm:text-sm shadow-inner flex-shrink-0 text-teal-300 mb-1">
                            ⚡
                        </div>
                        <div class="text-[9px] sm:text-[10px] uppercase font-bold tracking-wider text-slate-400 truncate w-full">Kinh nghiệm</div>
                        <div class="text-xs sm:text-sm font-extrabold text-teal-300 font-mono truncate w-full">{{ number_format($xp ?? 100) }} XP</div>
                    </div>

                    {{-- Leaderboard Rank Stat --}}
                    <a href="{{ route('leaderboard.index') }}" class="flex flex-col items-center justify-center text-center p-2.5 sm:p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 hover:border-indigo-500/40 hover:bg-slate-900/60 backdrop-blur-md shadow-sm transition-all duration-200 group min-w-0">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-xs sm:text-sm shadow-inner flex-shrink-0 text-indigo-300 mb-1 group-hover:scale-110 transition-transform">
                            🏆
                        </div>
                        <div class="text-[9px] sm:text-[10px] uppercase font-bold tracking-wider text-slate-400 truncate w-full">Thứ hạng</div>
                        <div class="text-xs sm:text-sm font-extrabold text-indigo-300 font-mono truncate w-full">Hạng #{{ $userRank }}</div>
                    </a>
                </div>
            </div>
        </div>

        {{-- ─── 2. MAIN 2-COLUMN DASHBOARD GRID ─── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- ── LEFT COLUMN: DAILY QUESTS & LEARNING TRACKS (8 Cols on XL, 7 on LG) ── --}}
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                {{-- 🎯 THỬ THÁCH HÀNG NGÀY (DAILY QUESTS) --}}
                <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-4 sm:p-6 space-y-4 backdrop-blur-xl shadow-xl shadow-black/20">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 border-b border-slate-800/80 pb-3.5">
                        <div class="flex items-center gap-2.5 sm:gap-3">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-sm sm:text-base flex-shrink-0">
                                🎯
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider">Thử Thách Hàng Ngày</h3>
                                <p class="text-[11px] sm:text-xs text-slate-400">Hoàn thành để nhận thêm điểm kinh nghiệm XP thưởng</p>
                            </div>
                        </div>
                        <span class="self-start sm:self-auto inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/60 text-slate-300 font-mono text-[10px] sm:text-[11px] font-medium shadow-sm">
                            <span class="text-slate-400">⏱️</span> Làm mới sau mỗi 24h
                        </span>
                    </div>

                    <div class="space-y-3">
                        @foreach($dailyQuests as $quest)
                            <div class="p-4 rounded-xl border transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $quest['is_completed'] ? 'bg-emerald-950/15 border-emerald-500/30 hover:bg-emerald-950/25' : 'bg-slate-950/40 border-slate-800/80 hover:border-slate-700/80 hover:bg-slate-900/50' }}">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-base flex-shrink-0 {{ $quest['is_completed'] ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-slate-900 text-indigo-400 border border-slate-800' }}">
                                        {{ $quest['icon'] }}
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-xs sm:text-sm font-bold text-white">{{ $quest['title'] }}</h4>
                                            @if($quest['is_completed'])
                                                <span class="inline-flex items-center gap-1 text-[10px] font-mono px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                                    ✓ Đã hoàn thành
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-400">{{ $quest['description'] }}</p>

                                        {{-- Progress Bar --}}
                                        <div class="flex items-center gap-3 pt-1">
                                            <div class="w-32 bg-slate-950 rounded-full h-1.5 overflow-hidden border border-slate-800/80">
                                                <div class="h-full rounded-full bg-gradient-to-r {{ $quest['is_completed'] ? 'from-emerald-500 to-teal-400' : 'from-indigo-500 to-sky-400' }}"
                                                     style="width: {{ min(round(($quest['current'] / $quest['target']) * 100), 100) }}%;"></div>
                                            </div>
                                            <span class="text-[10px] font-mono text-slate-400">{{ $quest['current'] }}/{{ $quest['target'] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center sm:flex-col items-end justify-between sm:justify-center gap-2 flex-shrink-0">
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-mono font-bold flex items-center gap-1 shadow-sm">
                                        <span class="text-amber-400 text-[10px]">⚡</span> +{{ $quest['reward_xp'] }} XP
                                    </span>
                                    @if(!$quest['is_completed'])
                                        <a href="{{ route('practice.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1 transition-colors">
                                            <span>Thực hiện</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- 🚀 TIẾP TỤC HỌC TẬP (COURSES) --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-base flex-shrink-0">
                                📖
                            </div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Khóa Học Đang Theo Học</h3>
                        </div>
                        <a href="{{ route('courses.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition-colors">
                            <span>Xem tất cả</span>
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-4">
                        @foreach($courses as $course)
                            <a href="{{ route('courses.show', $course->id) }}" class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-5 flex flex-col justify-between space-y-3 hover:border-indigo-500/50 hover:bg-slate-900/95 transition-all duration-300 group shadow-xl shadow-black/10 backdrop-blur-xl">
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 text-xs font-bold font-mono px-2.5 py-0.5 rounded-lg">
                                            Level {{ $course->level }}
                                        </span>
                                        <span class="text-[11px] font-mono text-slate-400">📝 {{ $course->lessons_count }} bài học</span>
                                    </div>
                                    <h4 class="text-sm font-bold text-white group-hover:text-indigo-300 transition-colors leading-snug">
                                        {{ $course->title }}
                                    </h4>
                                    <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ $course->description }}
                                    </p>
                                </div>

                                <div class="pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-xs text-indigo-400 font-semibold group-hover:text-indigo-300 transition-colors">
                                    <span>Vào học ngay</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 🎯 QUICK PRACTICE SHORTCUT BANNER --}}
                <div class="rounded-2xl bg-gradient-to-r from-indigo-950/40 via-slate-900/90 to-slate-900/90 border border-slate-800/80 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 backdrop-blur-xl shadow-xl shadow-black/20">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 flex items-center justify-center text-2xl flex-shrink-0">
                            🎯
                        </div>
                        <div>
                            <h4 class="text-sm sm:text-base font-bold text-white">Luyện Đề Thi Thử 4 Kỹ Năng (CBT Simulation)</h4>
                            <p class="text-xs text-slate-400 mt-0.5">Mô phỏng 100% phòng thi chuẩn CEFR: Từ vựng, Ngữ pháp, Đọc hiểu, Nghe hiểu</p>
                        </div>
                    </div>
                    <a href="{{ route('practice.index', ['skill' => 'full_mock']) }}" class="btn-primary !w-auto !py-2.5 px-5 text-xs font-bold shadow-lg shadow-indigo-600/25 flex items-center gap-1.5 flex-shrink-0 hover:scale-105 transition-all">
                        <span>Làm đề thi thử</span>
                    </a>
                </div>
            </div>

            {{-- ── RIGHT COLUMN: TARGETS, MINI LEADERBOARD & SKILLS (4 Cols on XL, 5 on LG) ── --}}
            <div class="lg:col-span-5 xl:col-span-4 space-y-6">

                {{-- 🎯 MỤC TIÊU TUẦN NÀY (7-DAY WEEKLY STREAK MATRIX) --}}
                <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-4 sm:p-6 space-y-4 backdrop-blur-xl shadow-xl shadow-black/20 min-w-0">
                    {{-- Header with Weekly Progress --}}
                    <div class="flex items-center justify-between gap-2 border-b border-slate-800/80 pb-3.5 flex-wrap">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-sm flex-shrink-0">
                                🎯
                            </div>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider font-sans whitespace-nowrap">Mục Tiêu Tuần Này</h3>
                            </div>
                        </div>

                        {{-- Weekly Days Completed Pill --}}
                        <span class="px-2.5 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/25 text-xs font-mono font-bold text-indigo-300 whitespace-nowrap flex-shrink-0 flex items-center gap-1 shadow-sm"
                              title="Số ngày đã học trong tuần này (Thứ 2 - Chủ nhật)">
                            <span class="text-indigo-400 text-xs">📅</span>{{ $weeklyActiveCount ?? 1 }}/7 ngày
                        </span>
                    </div>

                    {{-- Weekly Mini Progress Bar --}}
                    <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-800/80">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-blue-500 to-teal-400 transition-all duration-500 shadow-sm"
                             style="width: {{ min(100, max(14, round((($weeklyActiveCount ?? 1) / 7) * 100))) }}%;"></div>
                    </div>

                    {{-- 7-Day Matrix Row (Equally Distributed) --}}
                    <div class="grid grid-cols-7 gap-1.5 sm:gap-2 text-center pt-1">
                        @foreach($weeklyStreakDays as $day)
                            <div class="flex flex-col items-center gap-1.5">
                                {{-- Day of Week Label --}}
                                <span class="text-[11px] font-mono transition-colors {{ $day['is_today'] ? 'text-amber-400 font-black' : ($day['is_active'] ? 'text-amber-300/90 font-bold' : 'text-slate-500') }}">
                                    {{ $day['day_name'] }}
                                </span>

                                {{-- Day Status Node --}}
                                @if($day['is_active'])
                                    {{-- Active Flame Node (Completed) --}}
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 border border-amber-400/40 shadow-[0_2px_12px_rgba(245,158,11,0.35)] flex items-center justify-center text-xs sm:text-sm text-white hover:scale-110 transition-transform cursor-pointer relative {{ $day['is_today'] ? 'ring-2 ring-amber-400/80 ring-offset-1 ring-offset-slate-950' : '' }}"
                                          title="Thứ {{ $day['day_name'] }} ({{ $day['date_num'] }}): Đã học hôm nay!">
                                        <span class="drop-shadow-sm">🔥</span>
                                    </div>
                                @elseif($day['is_today'])
                                    {{-- Today Pending Node (Waiting for study) --}}
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-indigo-500/10 border border-dashed border-indigo-400/60 text-indigo-300 flex items-center justify-center text-xs font-bold font-mono animate-pulse hover:scale-105 transition-transform cursor-pointer"
                                          title="Hôm nay: Vào học để giữ chuỗi!">
                                        <span>⚡</span>
                                    </div>
                                @elseif($day['is_past'])
                                    {{-- Past Inactive Node (Missed) --}}
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-950/60 border border-slate-800/80 text-slate-600 flex items-center justify-center text-xs"
                                          title="Chưa học">
                                        <span class="text-slate-600 text-xs leading-none">•</span>
                                    </div>
                                @else
                                    {{-- Future Node (Upcoming - Elegant dot) --}}
                                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-950/30 border border-slate-800/40 flex items-center justify-center"
                                          title="Sắp tới ({{ $day['date_num'] }})">
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-700/60"></div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Motivational Micro-Footer Showing Total Continuous Streak --}}
                    <div class="pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-xs whitespace-nowrap gap-2">
                        <span class="text-slate-400">Chuỗi học liên tiếp:</span>
                        <span class="font-mono text-amber-400 font-bold flex-shrink-0 bg-amber-500/10 px-2.5 py-1 rounded-lg border border-amber-500/20 shadow-sm flex items-center gap-1.5 text-xs">
                            <span>🔥</span> {{ $streakCount }} ngày liên tục
                        </span>
                    </div>
                </div>

                {{-- 🏆 BẢNG XẾP HẠNG TRỰC QUAN (MINI LEADERBOARD) --}}
                <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-4 sm:p-6 space-y-4 backdrop-blur-xl shadow-xl shadow-black/20 min-w-0">
                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-2 border-b border-slate-800/80 pb-3.5 flex-wrap">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-sm flex-shrink-0">
                                🏆
                            </div>
                            <div class="flex items-center gap-1.5 min-w-0">
                                <h3 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider whitespace-nowrap">Top Học Viên XP</h3>
                                <span class="text-[9px] sm:text-[10px] px-1.5 sm:px-2 py-0.2 rounded-full bg-amber-500/10 text-amber-300 font-mono font-bold border border-amber-500/20 flex-shrink-0">Tuần</span>
                            </div>
                        </div>
                        <a href="{{ route('leaderboard.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition-colors whitespace-nowrap flex-shrink-0">
                            <span>Tất cả</span>
                        </a>
                    </div>

                    {{-- Top 5 List --}}
                    <div class="space-y-2">
                        @foreach($topLeaderboard as $idx => $topUser)
                            @php 
                                $isCurrent = $topUser->id === $user->id;
                                $isFirst = $idx === 0;
                                $isSecond = $idx === 1;
                                $isThird = $idx === 2;
                            @endphp

                            <div class="p-2.5 rounded-xl border transition-all duration-200 flex items-center justify-between gap-2 group
                                {{ $isFirst ? 'bg-amber-500/10 border-amber-500/30 hover:bg-amber-500/15 shadow-sm' : '' }}
                                {{ $isSecond ? 'bg-slate-800/40 border-slate-700/60 hover:bg-slate-800/60' : '' }}
                                {{ $isThird ? 'bg-amber-950/15 border-amber-900/40 hover:bg-amber-950/25' : '' }}
                                {{ !$isFirst && !$isSecond && !$isThird ? ($isCurrent ? 'bg-indigo-950/40 border-indigo-500/50 text-white' : 'bg-slate-950/40 border-slate-800/80 hover:bg-slate-900/60 text-slate-300') : '' }}
                                {{ $isCurrent && !$isFirst ? 'ring-1 ring-indigo-400/40' : '' }}">
                                
                                {{-- Left: Rank, Avatar & Name --}}
                                <div class="flex items-center gap-2.5 min-w-0">
                                    {{-- Medal / Rank Badge --}}
                                    <div class="w-5 text-center flex-shrink-0">
                                        @if($isFirst)
                                            <span class="text-base">🥇</span>
                                        @elseif($isSecond)
                                            <span class="text-base">🥈</span>
                                        @elseif($isThird)
                                            <span class="text-base">🥉</span>
                                        @else
                                            <span class="font-mono font-bold text-xs text-slate-500">#{{ $idx + 1 }}</span>
                                        @endif
                                    </div>

                                    {{-- Avatar --}}
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-[11px] flex-shrink-0 shadow-sm overflow-hidden
                                        {{ $isFirst ? 'bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 ring-1 ring-amber-300' : '' }}
                                        {{ $isSecond ? 'bg-gradient-to-tr from-slate-400 to-slate-200 text-slate-950 ring-1 ring-slate-300' : '' }}
                                        {{ $isThird ? 'bg-gradient-to-tr from-amber-700 to-amber-500 text-white ring-1 ring-amber-600' : '' }}
                                        {{ !$isFirst && !$isSecond && !$isThird ? 'bg-gradient-to-tr from-indigo-600 to-blue-500 text-white ring-1 ring-indigo-500/40' : '' }}">
                                        <img src="{{ $topUser->avatar_url }}" 
                                             alt="{{ $topUser->name }}" 
                                             class="w-full h-full object-cover"
                                             onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                                    </div>


                                    {{-- User Meta --}}
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-bold text-white truncate block max-w-[120px] sm:max-w-[200px] lg:max-w-[140px] xl:max-w-[180px] 2xl:max-w-[240px] group-hover:text-indigo-200 transition-colors">
                                                {{ $topUser->name }}
                                            </span>
                                            @if($isCurrent)
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300 font-bold border border-indigo-400/30 flex-shrink-0">Bạn</span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-amber-400/90 font-mono flex items-center gap-0.5">
                                            <span>🔥</span>{{ $topUser->streak_count ?? 1 }}d
                                        </span>
                                    </div>
                                </div>

                                {{-- Right: XP Counter Badge --}}
                                <div class="flex-shrink-0">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold flex items-center gap-1 shadow-sm whitespace-nowrap bg-slate-950/80 border border-slate-800/80 text-teal-300">
                                        <span class="text-amber-400 text-[10px]">⚡</span>
                                        <span>{{ number_format($topUser->xp) }} XP</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pinned User Rank Showcase --}}
                    <div class="p-3 rounded-xl bg-gradient-to-r from-indigo-950/40 to-slate-950/60 border border-indigo-500/30 space-y-2 shadow-md">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-6 h-6 rounded-md bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-xs text-indigo-300 font-black font-mono flex-shrink-0">
                                    #{{ $userRank }}
                                </span>
                                <div class="min-w-0">
                                    <span class="text-[9px] uppercase font-bold text-indigo-300 tracking-wider block leading-tight">Vị trí của bạn</span>
                                    <span class="text-xs font-bold text-white truncate block">{{ $user->name }}</span>
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <span class="text-xs font-mono font-bold text-teal-300 flex items-center gap-1 whitespace-nowrap">
                                    <span class="text-amber-400 text-[10px]">⚡</span>{{ number_format($xp ?? 100) }} XP
                                </span>
                            </div>
                        </div>

                        {{-- Encouragement Micro-Text --}}
                        <div class="pt-1.5 border-t border-indigo-500/20 text-[10px] text-slate-300">
                            @if($userRank === 1)
                                <span class="text-amber-300 font-medium flex items-center gap-1.5 truncate">
                                    <span>👑</span><span>Đang dẫn đầu bảng tuần này!</span>
                                </span>
                            @else
                                <span class="text-indigo-300 font-medium flex items-center gap-1.5 truncate">
                                    <span>🚀</span><span>Hoàn thành thêm bài để tăng hạng!</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 📊 MA TRẬN 4 KỸ NĂNG (SKILL MASTERY) --}}
                <div class="rounded-2xl bg-slate-900/80 border border-slate-800/80 p-5 sm:p-6 space-y-4 backdrop-blur-xl shadow-xl shadow-black/20">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-base flex-shrink-0">
                                📊
                            </div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Đánh Giá Kỹ Năng</h3>
                        </div>
                        <a href="{{ route('progress.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition-colors">
                            <span>Chi tiết</span>
                        </a>
                    </div>

                    @php
                        $skillsMeta = [
                            'listening' => ['name' => 'Nghe hiểu (Listening)', 'icon' => '🎧', 'color' => 'from-amber-500 to-orange-500'],
                            'speaking' => ['name' => 'Nói (Speaking)', 'icon' => '🎙️', 'color' => 'from-pink-500 to-rose-500'],
                            'reading' => ['name' => 'Đọc hiểu (Reading)', 'icon' => '📖', 'color' => 'from-teal-500 to-emerald-500'],
                            'writing' => ['name' => 'Viết (Writing)', 'icon' => '✍️', 'color' => 'from-sky-500 to-blue-500'],
                        ];
                    @endphp

                    <div class="space-y-3">
                        @foreach($skillsMeta as $skKey => $meta)
                            @php 
                                $skRecord = $learnerSkills->get($skKey);
                                $score = $skRecord ? $skRecord->mastery_score : 60;
                            @endphp
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-300 flex items-center gap-2">
                                        <span>{{ $meta['icon'] }}</span>
                                        <span>{{ $meta['name'] }}</span>
                                    </span>
                                    <span class="font-mono text-teal-300 font-bold">{{ $score }}%</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-1.5 overflow-hidden border border-slate-800/80">
                                    <div class="h-full rounded-full bg-gradient-to-r {{ $meta['color'] }}" style="width: {{ $score }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection

