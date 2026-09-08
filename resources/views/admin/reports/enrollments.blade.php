@extends('layouts.admin')
@section('title', 'Báo cáo Ghi danh')
@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-400 hover:text-fsel-teal transition-colors inline-flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại Báo cáo
            </a>
            <h2 class="text-xl font-bold text-white">📋 Báo cáo Ghi danh</h2>
        </div>
        <a href="{{ route('admin.reports.export', 'enrollments') }}" class="bg-green-600/20 hover:bg-green-600/30 text-green-400 text-xs px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
            Xuất CSV
        </a>
    </div>

    {{-- Filters --}}
    <div class="card-dark p-4 mb-6 relative z-30">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div :class="open ? 'relative z-50' : 'relative z-20'" class="min-w-[170px]" x-data="{
                open: false,
                selected: '{{ request('status') }}',
                options: {
                    '': 'Tất cả trạng thái',
                    'active': 'Đang học',
                    'completed': 'Hoàn thành',
                    'dropped': 'Đã hủy'
                }
            }" @click.outside="open = false">
                <input type="hidden" name="status" :value="selected">
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
            <input type="text" name="search" placeholder="Tìm học viên..." value="{{ request('search') }}" class="bg-fsel-navy border border-fsel-border rounded-lg text-sm text-white px-3 py-2 placeholder-gray-500">
            <button type="submit" class="bg-fsel-blue text-white text-sm px-4 py-2 rounded-lg hover:bg-fsel-blue/80 transition-colors">Lọc</button>
            @if(request()->hasAny(['status', 'course_id', 'search']))
                <a href="{{ route('admin.reports.enrollments') }}" class="text-xs text-gray-400 hover:text-white">Xóa bộ lọc</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="card-dark overflow-hidden relative z-10">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-fsel-border/30">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Học viên</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Khóa học</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Trạng thái</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Tiến độ</th>
                        <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Điểm cuối</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">Ngày GD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fsel-border/15">
                    @forelse($enrollments as $e)
                        <tr class="hover:bg-fsel-navy/30 transition-colors">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-white">{{ $e->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $e->user->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-300">{{ $e->course->title ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($e->status === 'completed')
                                    <span class="bg-green-500/20 text-green-400 text-xs px-2.5 py-1 rounded-full">Hoàn thành</span>
                                @elseif($e->status === 'active')
                                    <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2.5 py-1 rounded-full">Đang học</span>
                                @else
                                    <span class="bg-red-500/20 text-red-400 text-xs px-2.5 py-1 rounded-full">Đã hủy</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-20 bg-fsel-navy rounded-full h-1.5">
                                        <div class="bg-fsel-blue h-1.5 rounded-full" style="width: {{ $e->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400 w-8">{{ $e->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm font-semibold text-white">{{ $e->final_grade ? number_format($e->final_grade, 1) : '-' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right text-xs text-gray-500">{{ $e->enrolled_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">Không tìm thấy kết quả</td></tr>
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
