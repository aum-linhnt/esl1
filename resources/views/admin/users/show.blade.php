@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-white mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại danh sách người dùng
            </a>
            <h1 class="text-2xl font-bold text-white tracking-tight">Hồ sơ Học tập & Chẩn đoán: {{ $user->name }}</h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn-primary !w-auto !py-2 px-4 text-xs font-semibold flex items-center gap-1.5 shadow-glow-blue">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Chỉnh sửa tài khoản</span>
            </a>
        </div>
    </div>

    {{-- User Profile Overview (Top 4 Cards) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Trình độ CEFR</span>
            <p class="text-2xl font-black text-fsel-teal mt-1 font-mono">{{ $user->current_level }}</p>
            <span class="text-[10px] text-gray-500">Chẩn đoán thích ứng</span>
        </div>

        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Số dư Coins</span>
            <div class="flex items-center gap-2 mt-1">
                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-yellow-300 via-amber-400 to-amber-600 flex items-center justify-center shadow-md shadow-amber-500/20 border border-yellow-200/50 flex-shrink-0">
                    <span class="text-[10px] font-black text-slate-950 font-mono">¢</span>
                </div>
                <span class="text-2xl font-black text-amber-400 font-mono">{{ number_format($user->coins) }}</span>
            </div>
            <span class="text-[10px] text-gray-500 mt-1 block">Điểm thưởng tích lũy</span>
        </div>

        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Chuỗi học liên tục</span>
            <p class="text-2xl font-black text-orange-400 mt-1 font-mono">🔥 {{ $user->streak_count }} ngày</p>
            <span class="text-[10px] text-gray-500">Hoạt động gần nhất: {{ $user->last_active_date ?? 'Hôm nay' }}</span>
        </div>

        <div class="admin-card p-4">
            <span class="text-[10px] uppercase font-bold text-gray-400">Trạng thái & Vai trò</span>
            <p class="text-base font-bold text-white mt-1 capitalize">{{ $user->role }}</p>
            <span class="text-[10px] font-bold {{ $user->status === 'active' ? 'text-emerald-400' : ($user->status === 'trial_expired' ? 'text-yellow-400' : 'text-red-400') }}">
                ● {{ strtoupper($user->status) }}
            </span>
        </div>
    </div>

    {{-- 4-SKILL DIAGNOSTIC MATRIX --}}
    <div class="admin-card p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-white">Ma trận Chẩn đoán 4 Kỹ năng (Diagnostic Matrix)</h3>
            <span class="text-xs text-fsel-teal font-mono">AI Evaluated</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $skills = $user->skills->keyBy('skill_type');
                $skillConfig = [
                    'listening' => ['title' => 'Nghe hiểu (Listening)', 'icon' => '🎧', 'color' => '#fbbf24'],
                    'speaking' => ['title' => 'Nói (Speaking)', 'icon' => '🎙️', 'color' => '#f43f5e'],
                    'reading' => ['title' => 'Đọc hiểu (Reading)', 'icon' => '📖', 'color' => '#2dd4bf'],
                    'writing' => ['title' => 'Viết (Writing)', 'icon' => '✍️', 'color' => '#38bdf8'],
                ];
            @endphp

            @foreach($skillConfig as $key => $conf)
                @php $sk = $skills->get($key); @endphp
                <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-white flex items-center gap-1.5">
                            <span>{{ $conf['icon'] }}</span>
                            <span>{{ $conf['title'] }}</span>
                        </span>
                        <span class="font-mono text-xs font-bold text-fsel-teal">
                            {{ $sk ? $sk->assessed_level : 'A1' }}
                        </span>
                    </div>

                    <div class="flex items-baseline justify-between text-xs">
                        <span class="text-[10px] text-gray-500">Độ thuần thục</span>
                        <span class="font-bold text-white font-mono">{{ $sk ? $sk->mastery_score : 0 }}%</span>
                    </div>

                    <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $sk ? $sk->mastery_score : 0 }}%; background-color: {{ $conf['color'] }};"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Submissions & Badges Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {{-- Recent Submissions (7 cols) --}}
        <div class="lg:col-span-7 admin-card p-6 space-y-4">
            <h3 class="text-base font-bold text-white">Bài nộp đánh giá gần nhất</h3>

            <div class="divide-y divide-slate-800">
                @forelse($submissions as $sub)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-white uppercase font-mono">{{ str_replace('_', ' ', $sub->test_type) }}</span>
                            <span class="text-gray-500 text-[10px] block">{{ $sub->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-mono font-bold text-fsel-teal">{{ $sub->accuracy_rate }}%</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $sub->is_passed ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' }}">
                                {{ $sub->is_passed ? 'Pass' : 'Fail' }}
                            </span>
                            <a href="{{ route('admin.submissions.show', $sub->id) }}" class="text-xs text-indigo-400 hover:text-white font-medium">Xem</a>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 py-3 italic">Học viên chưa nộp bài đánh giá nào.</p>
                @endforelse
            </div>
        </div>

        {{-- Badges & Certificates (5 cols) --}}
        <div class="lg:col-span-5 admin-card p-6 space-y-4">
            <h3 class="text-base font-bold text-white">Huy hiệu đã đạt ({{ $user->badges->count() }})</h3>

            <div class="grid grid-cols-2 gap-2.5">
                @forelse($user->badges as $badge)
                    <div class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl flex items-center gap-2.5">
                        <span class="text-2xl">{{ $badge->badge_icon }}</span>
                        <div class="overflow-hidden">
                            <span class="text-xs font-bold text-white block truncate">{{ $badge->badge_name }}</span>
                            <span class="text-[9px] text-gray-500">{{ $badge->unlocked_at?->format('d/m/Y') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="col-span-2 text-xs text-gray-500 py-3 italic">Chưa mở khóa huy hiệu nào.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
