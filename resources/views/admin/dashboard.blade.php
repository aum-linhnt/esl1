@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    
    {{-- 1. PAGE HEADER & QUICK ACTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-white tracking-tight">Trung tâm Quản trị LMS (Control Center)</h1>
            <p class="text-xs text-gray-400 mt-1">Giám sát tổng thể hoạt động đào tạo, học viên, đánh giá năng lực và AI Engine theo thời gian thực.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('teacher.ai_generator.index') }}" class="btn-primary !w-auto !py-2 px-4 text-xs font-semibold flex items-center gap-1.5 shadow-glow-blue">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>AI Sinh đề thi</span>
            </a>
            <a href="{{ route('admin.courses.create') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white border border-slate-700 transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tạo Khóa học mới</span>
            </a>
        </div>
    </div>

    {{-- 2. TOP KPI CARDS (8 METRICS) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        
        {{-- Total Users --}}
        <div class="admin-card p-5">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Học viên & Users</span>
                <span class="text-lg">👥</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl lg:text-3xl font-black text-white">{{ $totalUsers }}</span>
                <span class="text-xs text-emerald-400 font-semibold">{{ $activeUsersCount }} Active</span>
            </div>
            <div class="flex gap-2 text-[10px] text-gray-400 mt-2">
                <span>HV: {{ $studentsCount }}</span> · <span>GV: {{ $teachersCount }}</span> · <span>Admin: {{ $adminsCount }}</span>
            </div>
        </div>

        {{-- Courses & Lessons --}}
        <div class="admin-card p-5">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Khóa học & Bài giảng</span>
                <span class="text-lg">📚</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl lg:text-3xl font-black text-indigo-400">{{ $totalCourses }}</span>
                <span class="text-xs text-gray-400">Khóa</span>
            </div>
            <div class="flex gap-2 text-[10px] text-gray-400 mt-2">
                <span>{{ $totalLessons }} bài học</span> · <span>{{ $totalActivities }} hoạt động</span>
            </div>
        </div>

        {{-- Question Bank --}}
        <div class="admin-card p-5">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Ngân hàng Câu hỏi</span>
                <span class="text-lg">❓</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl lg:text-3xl font-black text-fsel-teal">{{ $totalQuestions }}</span>
                <span class="text-xs text-fsel-teal">câu hỏi</span>
            </div>
            <div class="text-[10px] text-gray-400 mt-2">
                Đủ 4 kỹ năng & 3 cấp độ A1-B1
            </div>
        </div>

        {{-- Submissions & Accuracy --}}
        <div class="admin-card p-5">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Bài nộp & Đánh giá</span>
                <span class="text-lg">🎯</span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl lg:text-3xl font-black text-white">{{ $totalSubmissions }}</span>
                <span class="text-xs text-emerald-400 font-semibold">{{ round($avgAccuracy, 1) }}% Avg</span>
            </div>
            <div class="text-[10px] text-gray-400 mt-2">
                {{ $passedSubmissionsCount }} lượt đạt tiêu chuẩn (Pass)
            </div>
        </div>
    </div>

    {{-- 3. CEFR DISTRIBUTION & GAMIFICATION OVERVIEW --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- CEFR Proficiency Breakdown (7 cols) --}}
        <div class="lg:col-span-7 admin-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">Phân bổ Trình độ Học viên (CEFR Matrix)</h3>
                    <p class="text-xs text-gray-400">Dữ liệu từ thuật toán Adaptive Diagnostic & Kết quả học</p>
                </div>
                <span class="text-xs text-indigo-400 font-mono bg-indigo-500/10 px-2.5 py-1 rounded-full border border-indigo-500/20">
                    Live Diagnostics
                </span>
            </div>

            <div class="space-y-3 pt-2">
                @php
                    $colors = ['A1' => '#38bdf8', 'A2' => '#2dd4bf', 'B1' => '#818cf8', 'B2' => '#fbbf24'];
                @endphp
                @foreach($cefrDistribution as $lvl => $count)
                    @php $percent = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-bold text-white flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $colors[$lvl] }};"></span>
                                Trình độ {{ $lvl }}
                            </span>
                            <span class="text-gray-400">{{ $count }} học viên ({{ $percent }}%)</span>
                        </div>
                        <div class="w-full bg-slate-800/80 rounded-full h-2 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 style="width: {{ $percent }}%; background-color: {{ $colors[$lvl] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Gamification & Economy (5 cols) --}}
        <div class="lg:col-span-5 admin-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">Chỉ số Gamification & Coins</h3>
                    <p class="text-xs text-gray-400">Kích thích tinh thần tự học của học viên</p>
                </div>
                <a href="{{ route('admin.gamification.index') }}" class="text-xs text-fsel-teal hover:underline">Chi tiết</a>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                {{-- Total Coins Card --}}
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-gray-400">Tổng Coins lưu hành</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M14.8 9A2 2 0 0 0 13 8h-2a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-2a2 2 0 0 1-1.8-1"/>
                                <path d="M12 6v2m0 8v2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-white font-mono">{{ number_format($totalCoins) }}</span>
                        <span class="text-xs text-amber-400 font-semibold font-mono">Coins</span>
                    </div>
                    <span class="text-[10px] text-gray-500 mt-2 block">Phần thưởng bài học & AI</span>
                </div>

                {{-- Active Streaks Card --}}
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-gray-400">Streak ≥ 3 ngày</span>
                        <div class="w-8 h-8 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-black text-white font-mono">{{ $activeStreaksCount }}</span>
                        <span class="text-xs text-orange-400 font-semibold font-mono">Học viên</span>
                    </div>
                    <span class="text-[10px] text-gray-500 mt-2 block">Duy trì học liên tục</span>
                </div>
            </div>

            <div class="p-3.5 bg-indigo-950/40 border border-indigo-500/20 rounded-xl flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🏆</span>
                    <span class="text-gray-300">Tổng huy hiệu đã mở khóa:</span>
                </div>
                <span class="font-bold text-white font-mono">{{ $totalBadgesUnlocked }} Badges</span>
            </div>
        </div>
    </div>

    {{-- 4. LIVE SUBMISSION & ACTIVITY STREAMS --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Recent Submissions Feed (7 cols) --}}
        <div class="lg:col-span-7 admin-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <span>⚡ Bài nộp đánh giá gần nhất</span>
                </h3>
                <a href="{{ route('admin.submissions.index') }}" class="text-xs text-indigo-400 hover:underline">Xem tất cả</a>
            </div>

            <div class="divide-y divide-slate-800/80">
                @forelse($recentSubmissions as $sub)
                    <div class="py-3 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-xs font-bold text-indigo-400">
                                {{ strtoupper(substr($sub->test_type, 0, 2)) }}
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-white block">{{ $sub->user->name ?? 'User #' . $sub->user_id }}</span>
                                <span class="text-[10px] text-gray-400">{{ strtoupper(str_replace('_', ' ', $sub->test_type)) }} · {{ $sub->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono font-bold text-fsel-teal">{{ $sub->accuracy_rate }}%</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $sub->is_passed ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                                {{ $sub->is_passed ? 'Pass' : 'Fail' }}
                            </span>
                            <a href="{{ route('admin.submissions.show', $sub->id) }}" class="text-xs text-gray-400 hover:text-white p-1">
                                Xem
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 py-4 italic">Chưa có bài nộp nào.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Activity Telemetry & Users (5 cols) --}}
        <div class="lg:col-span-5 admin-card p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-white">Học viên mới đăng ký</h3>
                <a href="{{ route('admin.users.index') }}" class="text-xs text-indigo-400 hover:underline">Quản lý</a>
            </div>

            <div class="divide-y divide-slate-800/80">
                @forelse($recentUsers as $u)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-slate-800 flex items-center justify-center font-bold text-white text-[10px]">
                                {{ substr($u->name, 0, 1) }}
                            </div>
                            <div>
                                <span class="font-medium text-white block">{{ $u->name }}</span>
                                <span class="text-[10px] text-gray-400 font-mono">{{ $u->username }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded capitalize bg-slate-800 text-indigo-400">
                                {{ $u->role }}
                            </span>
                            <span class="text-[9px] text-gray-500 block mt-0.5">{{ $u->created_at->format('d/m H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 py-4 italic">Chưa có học viên nào.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
