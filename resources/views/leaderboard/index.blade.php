@extends('layouts.app')

@section('content')
<div class="w-full space-y-6 sm:space-y-8" x-data="{ currentTab: 'all_time' }">

    {{-- 1. TOP HERO HEADER --}}
    <div class="card-dark p-4 sm:p-7 border-indigo-500/40 relative overflow-hidden bg-gradient-to-br from-indigo-950/40 via-purple-950/20 to-slate-900/80 shadow-2xl rounded-2xl sm:rounded-3xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6">
            <div class="space-y-2 max-w-2xl min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-1 rounded-lg bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 text-[10px] sm:text-xs font-mono font-bold flex items-center gap-1">
                        <span>🏆</span>
                        <span>ESL Champions League</span>
                    </span>
                    <span class="px-2.5 py-1 rounded-lg bg-orange-500/20 text-orange-300 border border-orange-500/30 text-[10px] sm:text-xs font-mono font-bold flex items-center gap-1">
                        <span>🔥 Streak:</span>
                        <span>{{ $currentUser->streak_count ?? 1 }} Ngày</span>
                    </span>
                </div>
                <h1 class="text-xl sm:text-3xl font-black text-white tracking-tight leading-tight">
                    Bảng Xếp Hạng Điểm XP
                </h1>
                <p class="text-xs sm:text-sm text-gray-300 leading-relaxed">
                    Học tập chăm chỉ mỗi ngày, luyện đề thi thử 4 kỹ năng và duy trì Streak để tích lũy XP vươn lên đỉnh bảng vàng.
                </p>
            </div>

            {{-- Current User XP & Rank Stat Card --}}
            <div class="card-dark p-3.5 sm:p-5 border-yellow-500/40 bg-slate-950/80 w-full md:w-auto md:min-w-[200px] shadow-glow-blue flex-shrink-0 rounded-xl sm:rounded-2xl">
                <div class="flex md:flex-col items-center justify-between md:justify-center gap-2 md:space-y-1 text-center">
                    <div class="text-left md:text-center">
                        <span class="text-[10px] sm:text-xs text-gray-400 font-mono uppercase block">Hạng của bạn</span>
                        <div class="text-2xl sm:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-amber-200 font-mono">
                            #{{ $userRank }}
                        </div>
                    </div>
                    <div class="flex items-center justify-end md:justify-center gap-2 text-xs font-mono font-bold bg-slate-900/80 md:bg-transparent px-3 py-1.5 md:p-0 rounded-lg">
                        <span class="text-teal-300">⚡ {{ number_format($currentUser->xp ?? 100) }} XP</span>
                        <span class="text-gray-600">·</span>
                        <span class="text-orange-400">🔥 {{ $currentUser->streak_count ?? 1 }}d</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Background Glow --}}
        <div class="absolute top-0 right-0 w-80 h-80 bg-yellow-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    {{-- 2. TOP 3 PODIUM DISPLAY (Responsive layout) --}}
    @if($podium->count() >= 3)
        <div class="grid grid-cols-3 gap-2 sm:gap-6 items-end pt-2 sm:pt-4">
            
            {{-- 2nd Place (Silver) --}}
            @if(isset($podium[1]))
                <div class="order-1 card-dark p-3 sm:p-6 border-slate-700/80 bg-gradient-to-b from-slate-800/40 to-slate-950/80 text-center space-y-2 sm:space-y-3 relative hover:scale-[1.02] transition-transform rounded-xl sm:rounded-2xl">
                    <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-slate-400/20 text-slate-200 font-black text-xs sm:text-base flex items-center justify-center mx-auto border border-slate-400/40 shadow-lg">
                        🥈 2
                    </div>
                    <div class="w-10 h-10 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-slate-600 to-slate-400 text-white font-black text-sm sm:text-xl flex items-center justify-center mx-auto shadow-lg ring-2 ring-slate-400/50">
                        {{ substr($podium[1]->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-white text-xs sm:text-sm truncate">{{ $podium[1]->name }}</h4>
                        <span class="text-[9px] sm:text-[10px] font-mono text-gray-400 hidden sm:inline">Level: <strong class="text-white">{{ $podium[1]->current_level ?: 'A2' }}</strong></span>
                    </div>
                    <div class="pt-1.5 sm:pt-2 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between text-[10px] sm:text-xs font-mono gap-1">
                        <span class="text-orange-400">🔥 {{ $podium[1]->streak_count ?? 1 }}d</span>
                        <span class="font-bold text-teal-300">⚡ {{ number_format($podium[1]->xp) }}</span>
                    </div>
                </div>
            @endif

            {{-- 1st Place (Gold Champion) --}}
            @if(isset($podium[0]))
                <div class="order-2 card-dark p-3.5 sm:p-7 border-yellow-500/60 bg-gradient-to-b from-yellow-950/30 via-slate-900 to-slate-950 text-center space-y-2.5 sm:space-y-4 relative hover:scale-[1.03] transition-transform shadow-2xl ring-1 sm:ring-2 ring-yellow-500/40 rounded-xl sm:rounded-2xl pb-4 sm:pb-6">
                    <div class="absolute -top-3 sm:-top-4 left-1/2 -translate-x-1/2 px-2.5 sm:px-4 py-0.5 sm:py-1 rounded-full bg-gradient-to-r from-yellow-500 to-amber-400 text-slate-950 font-black text-[9px] sm:text-xs uppercase tracking-wider shadow-lg flex items-center gap-1 whitespace-nowrap">
                        <span>👑</span>
                        <span>Quán Quân</span>
                    </div>
                    <div class="w-8 h-8 sm:w-12 sm:h-12 rounded-full bg-yellow-500/20 text-yellow-300 font-black text-sm sm:text-lg flex items-center justify-center mx-auto border border-yellow-500/50 shadow-lg mt-1 sm:mt-2">
                        🥇 1
                    </div>
                    <div class="w-12 h-12 sm:w-20 sm:h-20 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-yellow-500 via-amber-400 to-orange-500 text-slate-950 font-black text-base sm:text-2xl flex items-center justify-center mx-auto shadow-2xl ring-2 sm:ring-4 ring-yellow-500/50">
                        {{ substr($podium[0]->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-black text-white text-xs sm:text-base truncate">{{ $podium[0]->name }}</h4>
                        <span class="text-[9px] sm:text-xs font-mono text-yellow-300 hidden sm:inline">Level: <strong>{{ $podium[0]->current_level ?: 'B1' }}</strong> · Top 1</span>
                    </div>
                    <div class="pt-1.5 sm:pt-2 border-t border-yellow-500/30 flex flex-col sm:flex-row items-center justify-between text-[10px] sm:text-xs font-mono gap-1">
                        <span class="text-orange-400 font-bold">🔥 {{ $podium[0]->streak_count ?? 1 }}d</span>
                        <span class="font-black text-yellow-400 text-xs sm:text-sm">⚡ {{ number_format($podium[0]->xp) }}</span>
                    </div>
                </div>
            @endif

            {{-- 3rd Place (Bronze) --}}
            @if(isset($podium[2]))
                <div class="order-3 card-dark p-3 sm:p-6 border-amber-800/80 bg-gradient-to-b from-amber-950/20 to-slate-950/80 text-center space-y-2 sm:space-y-3 relative hover:scale-[1.02] transition-transform rounded-xl sm:rounded-2xl">
                    <div class="w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-amber-700/20 text-amber-400 font-black text-xs sm:text-base flex items-center justify-center mx-auto border border-amber-700/40 shadow-lg">
                        🥉 3
                    </div>
                    <div class="w-10 h-10 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-amber-700 to-amber-500 text-white font-black text-sm sm:text-xl flex items-center justify-center mx-auto shadow-lg ring-2 ring-amber-600/50">
                        {{ substr($podium[2]->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-white text-xs sm:text-sm truncate">{{ $podium[2]->name }}</h4>
                        <span class="text-[9px] sm:text-[10px] font-mono text-gray-400 hidden sm:inline">Level: <strong class="text-white">{{ $podium[2]->current_level ?: 'A2' }}</strong></span>
                    </div>
                    <div class="pt-1.5 sm:pt-2 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between text-[10px] sm:text-xs font-mono gap-1">
                        <span class="text-orange-400">🔥 {{ $podium[2]->streak_count ?? 1 }}d</span>
                        <span class="font-bold text-teal-300">⚡ {{ number_format($podium[2]->xp) }}</span>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- 3. HOW TO EARN XP & MAINTAIN STREAK --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
        <div class="card-dark p-3 sm:p-4 space-y-1 border-slate-800/80 bg-slate-950/60 rounded-xl sm:rounded-2xl">
            <div class="flex items-center gap-1.5 text-yellow-400 text-xs sm:text-sm font-bold">
                <span>⚡</span>
                <span>+50 XP</span>
            </div>
            <span class="text-[11px] sm:text-xs font-bold text-white block leading-tight">Đề thi 4 Kỹ Năng</span>
            <p class="text-[10px] text-gray-400 line-clamp-2 leading-tight">Làm đề thi thử chuẩn CEFR.</p>
        </div>

        <div class="card-dark p-3 sm:p-4 space-y-1 border-slate-800/80 bg-slate-950/60 rounded-xl sm:rounded-2xl">
            <div class="flex items-center gap-1.5 text-teal-300 text-xs sm:text-sm font-bold">
                <span>⚡</span>
                <span>+30 XP</span>
            </div>
            <span class="text-[11px] sm:text-xs font-bold text-white block leading-tight">Bài học mới</span>
            <p class="text-[10px] text-gray-400 line-clamp-2 leading-tight">Video, từ vựng & trắc nghiệm.</p>
        </div>

        <div class="card-dark p-3 sm:p-4 space-y-1 border-slate-800/80 bg-slate-950/60 rounded-xl sm:rounded-2xl">
            <div class="flex items-center gap-1.5 text-orange-400 text-xs sm:text-sm font-bold">
                <span>🔥</span>
                <span>+20~50 XP</span>
            </div>
            <span class="text-[11px] sm:text-xs font-bold text-white block leading-tight">Chuỗi Streak ngày</span>
            <p class="text-[10px] text-gray-400 line-clamp-2 leading-tight">Học đều đặn mỗi ngày.</p>
        </div>

        <div class="card-dark p-3 sm:p-4 space-y-1 border-slate-800/80 bg-slate-950/60 rounded-xl sm:rounded-2xl">
            <div class="flex items-center gap-1.5 text-indigo-400 text-xs sm:text-sm font-bold">
                <span>🏆</span>
                <span>+100 XP</span>
            </div>
            <span class="text-[11px] sm:text-xs font-bold text-white block leading-tight">Mở Huy hiệu</span>
            <p class="text-[10px] text-gray-400 line-clamp-2 leading-tight">Đạt các mốc bài tập giỏi.</p>
        </div>
    </div>

    {{-- 4. LEADERBOARD LIST / TABLE --}}
    <div class="card-dark overflow-hidden border-slate-800 rounded-2xl">
        <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="text-base sm:text-lg">📜</span>
                <h3 class="text-xs sm:text-sm font-bold text-white uppercase tracking-wider">Bảng Vinh Danh Top Học Viên</h3>
            </div>
            <span class="text-[11px] sm:text-xs font-mono text-gray-400">Tổng: {{ $totalLearners }} học viên</span>
        </div>

        {{-- MOBILE VIEW: App-Style Ranking Cards (hidden on md and up) --}}
        <div class="md:hidden divide-y divide-slate-800/60">
            @forelse($topLearners as $index => $learner)
                @php $rank = $index + 1; $isSelf = $learner->id === $currentUser->id; @endphp
                <div class="p-3.5 flex items-center gap-3 transition-colors {{ $isSelf ? 'bg-indigo-600/15 border-l-4 border-indigo-500' : 'hover:bg-slate-900/40' }}">
                    {{-- Rank Icon / Number --}}
                    <div class="w-8 flex-shrink-0 text-center">
                        @if($rank === 1)
                            <span class="text-lg">🥇</span>
                        @elseif($rank === 2)
                            <span class="text-lg">🥈</span>
                        @elseif($rank === 3)
                            <span class="text-lg">🥉</span>
                        @else
                            <span class="text-xs font-mono font-bold text-gray-400">#{{ $rank }}</span>
                        @endif
                    </div>

                    {{-- Learner Avatar --}}
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm overflow-hidden">
                        <img src="{{ $learner->avatar_url }}" 
                             alt="{{ $learner->name }}" 
                             class="w-full h-full object-cover"
                             onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.svg') }}';">
                    </div>


                    {{-- Name & Level --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-bold text-xs text-white truncate max-w-[140px]">{{ $learner->name }}</span>
                            @if($isSelf)
                                <span class="text-[9px] font-bold text-indigo-300 bg-indigo-500/20 px-1.5 py-0.2 rounded">Bạn</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-[10px] text-gray-400 font-mono">
                            <span class="text-indigo-400 font-semibold">{{ $learner->current_level ?: 'A1' }}</span>
                            <span>·</span>
                            <span class="text-orange-400">🔥 {{ $learner->streak_count ?? 1 }}d</span>
                        </div>
                    </div>

                    {{-- XP Score --}}
                    <div class="text-right flex-shrink-0">
                        <span class="text-xs font-black text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-cyan-200 font-mono">
                            ⚡ {{ number_format($learner->xp) }}
                        </span>
                        <span class="text-[9px] text-gray-500 block font-mono">XP</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500 text-xs">
                    Chưa có dữ liệu bảng xếp hạng.
                </div>
            @endforelse
        </div>

        {{-- DESKTOP VIEW: Full Data Table (hidden on mobile) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-slate-900/90 text-gray-400 uppercase font-mono text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-5 w-16">Hạng</th>
                        <th class="py-3.5 px-5">Học viên</th>
                        <th class="py-3.5 px-5">Cấp độ CEFR</th>
                        <th class="py-3.5 px-5">Chuỗi Streak</th>
                        <th class="py-3.5 px-5">Huy hiệu</th>
                        <th class="py-3.5 px-5 text-right">Tổng Điểm XP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    @forelse($topLearners as $index => $learner)
                        @php $rank = $index + 1; $isSelf = $learner->id === $currentUser->id; @endphp
                        <tr class="transition-colors {{ $isSelf ? 'bg-indigo-600/20 font-bold text-white border-l-4 border-indigo-500' : 'hover:bg-slate-900/40' }}">
                            <td class="py-4 px-5">
                                @if($rank === 1)
                                    <span class="text-base">🥇</span>
                                @elseif($rank === 2)
                                    <span class="text-base">🥈</span>
                                @elseif($rank === 3)
                                    <span class="text-base">🥉</span>
                                @else
                                    <span class="text-gray-400 font-bold">#{{ $rank }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white font-bold text-xs flex items-center justify-center flex-shrink-0">
                                        {{ substr($learner->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-sans font-bold text-white block truncate max-w-xs">{{ $learner->name }}</span>
                                        <span class="text-[10px] text-gray-500 font-mono">{{ '@' . $learner->username }} {{ $isSelf ? '(Bạn)' : '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-5">
                                <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px] font-bold px-2 py-0.5 rounded">
                                    {{ $learner->current_level ?: 'A1' }}
                                </span>
                            </td>
                            <td class="py-4 px-5 text-orange-400 font-bold">
                                🔥 {{ $learner->streak_count ?? 1 }} Ngày
                            </td>
                            <td class="py-4 px-5 text-gray-400">
                                🏆 {{ $learner->badges_count }}
                            </td>
                            <td class="py-4 px-5 text-right">
                                <span class="text-sm font-black text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-cyan-200">
                                    ⚡ {{ number_format($learner->xp) }} XP
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-500">
                                Chưa có dữ liệu bảng xếp hạng.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
