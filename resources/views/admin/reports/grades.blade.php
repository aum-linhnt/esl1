@extends('layouts.admin')
@section('title', 'Báo cáo Điểm số')
@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-400 hover:text-fsel-teal transition-colors inline-flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại Báo cáo
            </a>
            <h2 class="text-xl font-bold text-white">📊 Báo cáo Điểm số</h2>
        </div>
        <a href="{{ route('admin.reports.export', 'grades') }}" class="bg-green-600/20 hover:bg-green-600/30 text-green-400 text-xs px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"/></svg>
            Xuất CSV
        </a>
    </div>

    {{-- Grade Distribution --}}
    <div class="card-dark p-5 mb-6">
        <h3 class="text-sm font-semibold text-white mb-4">Phân bố xếp loại</h3>
        <div class="flex items-end gap-3 h-32">
            @php
                $totalGrades = array_sum($gradeDistribution);
                $gradeColors = ['A' => 'bg-green-500', 'B' => 'bg-blue-500', 'C' => 'bg-yellow-500', 'D' => 'bg-orange-500', 'F' => 'bg-red-500'];
                $maxCount = max(1, max($gradeDistribution));
            @endphp
            @foreach($gradeDistribution as $letter => $count)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-xs font-bold text-white">{{ $count }}</span>
                    <div class="w-full {{ $gradeColors[$letter] }} rounded-t-lg transition-all duration-500"
                         style="height: {{ ($count / $maxCount) * 100 }}%"></div>
                    <span class="text-xs font-bold text-gray-400">{{ $letter }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-dark p-4 mb-6 relative z-30">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div :class="open ? 'relative z-50' : 'relative z-20'" class="min-w-[220px]" x-data="{
                open: false,
                selected: '{{ request('course_id') }}',
                options: {
                    '': 'Tất cả khóa học',
                    @foreach($courses as $course)
                        '{{ $course->id }}': '{{ addslashes($course->title) }}',
                    @endforeach
                }
            }" @click.outside="open = false">
                <input type="hidden" name="course_id" :value="selected">
                <button type="button" @click="open = !open" class="bg-fsel-navy border border-fsel-border rounded-lg text-sm text-white px-3 py-2 flex items-center justify-between cursor-pointer text-left w-full">
                    <span x-text="options[selected] || selected" class="truncate"></span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                    <template x-for="(lbl, val) in options" :key="val">
                        <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                            <span x-text="lbl"></span>
                            <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                        </div>
                    </template>
                </div>
            </div>
            <button type="submit" class="bg-fsel-blue text-white text-sm px-4 py-2 rounded-lg hover:bg-fsel-blue/80 transition-colors">Lọc</button>
        </form>
    </div>

    {{-- Grades Table --}}
    <div class="card-dark overflow-hidden relative z-10">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-fsel-border/30">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase">Học viên</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase">Khóa học</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase">Điểm</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase">Xếp loại</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fsel-border/15">
                    @forelse($gradesData as $item)
                        @php $e = $item['enrollment']; @endphp
                        <tr class="hover:bg-fsel-navy/30 transition-colors">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-white">{{ $e->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $e->user->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-300">{{ $e->course->title ?? '-' }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-lg font-bold {{ $item['grade'] >= 80 ? 'text-green-400' : ($item['grade'] >= 60 ? 'text-yellow-400' : 'text-red-400') }}">
                                    {{ number_format($item['grade'], 1) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm font-bold px-3 py-1 rounded-lg
                                    {{ $item['letter'] === 'A' ? 'bg-green-500/20 text-green-400' :
                                       ($item['letter'] === 'B' ? 'bg-blue-500/20 text-blue-400' :
                                       ($item['letter'] === 'C' ? 'bg-yellow-500/20 text-yellow-400' :
                                       ($item['letter'] === 'D' ? 'bg-orange-500/20 text-orange-400' : 'bg-red-500/20 text-red-400'))) }}">
                                    {{ $item['letter'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($e->status === 'completed')
                                    <span class="bg-green-500/20 text-green-400 text-xs px-2.5 py-1 rounded-full">Hoàn thành</span>
                                @else
                                    <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2.5 py-1 rounded-full">Đang học</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-500">Không có dữ liệu</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($enrollments->hasPages())
            <div class="px-5 py-3 border-t border-fsel-border/20">
                {{ $enrollments->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
