@extends('layouts.app')

@section('content')
<div class="w-full space-y-8">
    
    {{-- 1. OVERALL STATS HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Tiến trình & Chẩn đoán Năng lực</h2>
            <p class="text-xs text-gray-400">Theo dõi toàn diện 4 kỹ năng ngôn ngữ và tiến độ các khóa học</p>
        </div>

        <a href="{{ route('practice.index') }}" class="btn-primary !w-auto !py-2.5 px-5 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Làm bài kiểm tra thích ứng AI</span>
        </a>
    </div>

    {{-- Top 3 Metric Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card-dark p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center text-xl">
                🏆
            </div>
            <div>
                <span class="text-xs text-gray-400">Trình độ đánh giá (CEFR)</span>
                <p class="text-2xl font-extrabold text-white mt-0.5">{{ $currentLevel ?: 'A2' }}</p>
                <span class="text-[11px] text-fsel-teal">Chỉ số năng lực tổng hợp</span>
            </div>
        </div>

        <div class="card-dark p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-500/20 text-fsel-teal flex items-center justify-center text-xl">
                ⚡
            </div>
            <div>
                <span class="text-xs text-gray-400">Độ thuần thục trung bình</span>
                <p class="text-2xl font-extrabold text-fsel-teal mt-0.5">{{ $overallMastery }}%</p>
                <span class="text-[11px] text-gray-400">Tính trên 4 kỹ năng cốt lõi</span>
            </div>
        </div>

        <div class="card-dark p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center flex-shrink-0">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-yellow-300 via-amber-400 to-amber-600 flex items-center justify-center shadow-md shadow-amber-500/20 border border-yellow-200/50">
                    <span class="text-xs font-black text-slate-950 font-mono">¢</span>
                </div>
            </div>
            <div>
                <span class="text-xs text-gray-400">Số dư ESL Coins</span>
                <p class="text-2xl font-extrabold text-amber-400 mt-0.5 font-mono">{{ number_format($totalCoins) }}</p>
                <span class="text-[11px] text-amber-300/80">Sẵn sàng đổi quà & khóa học</span>
            </div>
        </div>
    </div>

    {{-- 2. 4-SKILL DIAGNOSTIC MATRIX --}}
    <div class="card-dark p-6 lg:p-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span>Ma trận Chẩn đoán 4 Kỹ năng (Diagnostic Matrix)</span>
                </h3>
                <p class="text-xs text-gray-400">Được cập nhật tự động sau mỗi bài tập và bài test thích ứng</p>
            </div>
            <span class="text-xs text-fsel-teal bg-fsel-teal/10 border border-fsel-teal/30 px-3 py-1 rounded-full font-semibold">
                AI Diagnostic Active
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($skills as $key => $skill)
                <div class="bg-fsel-navy/50 border border-fsel-border/30 rounded-xl p-5 hover:border-fsel-accent/30 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">{{ $skill['icon'] }}</span>
                            <div>
                                <h4 class="text-sm font-bold text-white">{{ $skill['title'] }}</h4>
                                <span class="text-[10px] text-gray-500">Cập nhật: {{ $skill['updated_at'] }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="bg-fsel-dark text-fsel-accent border border-fsel-border px-2.5 py-0.5 rounded text-xs font-bold font-mono">
                                {{ $skill['level'] }}
                            </span>
                            <p class="text-xs font-bold text-white mt-1">{{ $skill['score'] }}%</p>
                        </div>
                    </div>

                    {{-- Mastery Progress Bar --}}
                    <div class="w-full bg-fsel-dark rounded-full h-2 overflow-hidden border border-fsel-border/20">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width: {{ $skill['score'] }}%; background-color: {{ $skill['color'] }};"></div>
                    </div>

                    <div class="flex justify-between text-[10px] text-gray-500 mt-2">
                        <span>A1 (Cơ bản)</span>
                        <span>A2 (Sơ cấp)</span>
                        <span>B1 (Trung cấp)</span>
                        <span>B2 (Nâng cao)</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 3. COURSE PROGRESS --}}
    <div class="card-dark p-6">
        <h3 class="text-lg font-bold text-white mb-4">Tiến độ theo khóa học</h3>
        
        <div class="space-y-4">
            @forelse($courseProgress as $item)
                <div class="bg-fsel-navy/40 border border-fsel-border/30 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="bg-fsel-blue/20 text-fsel-blue text-xs font-bold px-2 py-0.5 rounded">{{ $item['course']->level }}</span>
                            <h4 class="text-sm font-bold text-white">{{ $item['course']->title }}</h4>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-400">
                            <span>Đã hoàn thành: {{ $item['completed'] }}/{{ $item['total'] }} hoạt động</span>
                            <span>·</span>
                            <span>{{ $item['percentage'] }}% hoàn thành</span>
                        </div>
                        <div class="w-full bg-fsel-dark rounded-full h-2 mt-2 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 {{ $item['percentage'] === 100 ? 'bg-green-500' : 'bg-gradient-to-r from-fsel-blue to-fsel-teal' }}"
                                 style="width: {{ $item['percentage'] }}%"></div>
                        </div>
                    </div>

                    <a href="{{ route('courses.show', $item['course']->id) }}" class="btn-primary !w-auto !py-2 px-4 text-xs self-start sm:self-center">
                        Tiếp tục
                    </a>
                </div>
            @empty
                <p class="text-sm text-gray-500 italic">Chưa có khóa học nào.</p>
            @endforelse
        </div>
    </div>

    {{-- 4. RECENT ASSESSMENT SUBMISSION LOGS --}}
    @if($recentAssessments->isNotEmpty())
        <div class="card-dark p-6">
            <h3 class="text-base font-bold text-white mb-4">Lịch sử đánh giá gần đây</h3>
            <div class="divide-y divide-fsel-border/20">
                @foreach($recentAssessments as $sub)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-white uppercase">{{ str_replace('_', ' ', $sub->test_type) }}</span>
                            <span class="text-gray-500 ml-2">{{ $sub->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-fsel-teal font-bold">{{ $sub->accuracy_rate }}% đúng</span>
                            <span class="px-2 py-0.5 rounded font-semibold {{ $sub->is_passed ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                {{ $sub->is_passed ? 'Đạt' : 'Chưa đạt' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
