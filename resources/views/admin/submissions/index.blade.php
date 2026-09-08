@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    
    {{-- Header & Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-30">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Lịch sử Bài nộp & Đánh giá Năng lực</h1>
            <p class="text-xs text-gray-400">Theo dõi toàn bộ bài thi thích ứng, bài luận AI Writing và phát âm AI Speaking của học viên</p>
        </div>

        <form method="GET" action="{{ route('admin.submissions.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Tìm học viên..." 
                   class="login-input !py-1.5 !px-3 text-xs w-48">

            {{-- Test Type --}}
            <div :class="open ? 'relative z-50' : 'relative z-20'" class="min-w-[170px]" x-data="{
                open: false,
                selected: '{{ $testType }}',
                options: {
                    '': '-- Tất cả loại bài thi --',
                    'adaptive_diagnostic': 'Adaptive Diagnostic',
                    'ai_writing': 'AI Writing',
                    'ai_speaking': 'AI Speaking',
                    'lesson_quiz': 'Lesson Quiz'
                }
            }" @click.outside="open = false">
                <input type="hidden" name="test_type" :value="selected">
                <button type="button" @click="open = !open" class="login-input !py-1.5 !px-3 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                    <span x-text="options[selected] || selected" class="text-white truncate"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                    <template x-for="(lbl, val) in options" :key="val">
                        <div @click="selected = val; open = false; $nextTick(() => $el.closest('form').submit())" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                            <span x-text="lbl"></span>
                            <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Passed --}}
            <div :class="open ? 'relative z-50' : 'relative z-20'" class="min-w-[130px]" x-data="{
                open: false,
                selected: '{{ $passed ?? '' }}',
                options: {
                    '': '-- Kết quả --',
                    '1': 'Đạt (Pass)',
                    '0': 'Chưa đạt (Fail)'
                }
            }" @click.outside="open = false">
                <input type="hidden" name="passed" :value="selected">
                <button type="button" @click="open = !open" class="login-input !py-1.5 !px-3 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                    <span x-text="options[selected] || selected" class="text-white truncate"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl">
                    <template x-for="(lbl, val) in options" :key="val">
                        <div @click="selected = val; open = false; $nextTick(() => $el.closest('form').submit())" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                            <span x-text="lbl"></span>
                            <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                        </div>
                    </template>
                </div>
            </div>

            <button type="submit" class="btn-primary !w-auto !py-1.5 px-4 text-xs">Lọc</button>
            @if($search || $testType || $passed !== null)
                <a href="{{ route('admin.submissions.index') }}" class="text-xs text-gray-400 hover:text-white px-2">Xóa lọc</a>
            @endif
        </form>
    </div>

    {{-- Submissions Table --}}
    <div class="admin-card overflow-hidden relative z-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-slate-900/80 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">ID / Thời gian</th>
                        <th class="px-5 py-3.5">Học viên</th>
                        <th class="px-5 py-3.5">Loại bài thi</th>
                        <th class="px-5 py-3.5">Lần thi (Attempt)</th>
                        <th class="px-5 py-3.5">Điểm số</th>
                        <th class="px-5 py-3.5">Độ chính xác</th>
                        <th class="px-5 py-3.5">Trạng thái</th>
                        <th class="px-5 py-3.5 text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($submissions as $sub)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <span class="font-mono text-gray-400">#{{ $sub->id }}</span>
                                <span class="text-[10px] text-gray-500 block">{{ $sub->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-white">{{ $sub->user->name ?? 'User #' . $sub->user_id }}</div>
                                <div class="text-[10px] text-gray-400 font-mono">{{ $sub->user->email ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="font-mono text-[11px] font-bold text-indigo-400 uppercase">
                                    {{ str_replace('_', ' ', $sub->test_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="px-2 py-0.5 rounded-md font-mono text-[11px] font-bold bg-slate-800 text-indigo-300 border border-indigo-500/20">
                                    Lần #{{ $sub->attempt_number ?? 1 }}
                                </span>
                                @if($sub->time_spent_seconds)
                                    <span class="text-[10px] text-gray-500 block font-mono mt-0.5">
                                        {{ floor($sub->time_spent_seconds / 60) }}m {{ $sub->time_spent_seconds % 60 }}s
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-mono font-bold text-white">
                                {{ $sub->total_score }}/{{ $sub->max_score }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="font-mono font-bold text-fsel-teal">{{ $sub->accuracy_rate }}%</span>
                            </td>
                            <td class="px-5 py-3.5">
                                @if($sub->is_passed)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                        ✓ Đạt tiêu chuẩn
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-400 bg-red-500/10 px-2 py-0.5 rounded-full border border-red-500/20">
                                        ✕ Chưa đạt
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.submissions.show', $sub->id) }}" class="text-xs text-indigo-400 hover:text-white px-2.5 py-1 rounded bg-indigo-500/10 hover:bg-indigo-500/20 font-semibold transition-colors">
                                    Xem bài làm
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-gray-500">
                                Không có bản ghi bài nộp nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-800 bg-slate-900/40">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
