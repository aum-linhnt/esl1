@extends('layouts.admin')
@section('title', 'Báo cáo LMS')
@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white">📈 Báo cáo LMS</h2>
            <p class="text-sm text-gray-400 mt-1">Tổng quan về ghi danh, điểm số và hoạt động học tập</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.reports.export', 'enrollments') }}" class="bg-fsel-navy hover:bg-fsel-navy/80 text-gray-300 text-xs px-3 py-2 rounded-lg transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-blue">{{ $enrollmentStats['total'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Tổng ghi danh</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-green-400">{{ $enrollmentStats['active'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Đang học</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-teal">{{ $enrollmentStats['completed'] }}</p>
            <p class="text-xs text-gray-400 mt-1">Hoàn thành</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-yellow-400">{{ number_format($enrollmentStats['completion_rate'], 1) }}%</p>
            <p class="text-xs text-gray-400 mt-1">Tỷ lệ hoàn thành</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-purple">{{ $totalActivitiesCompleted }}</p>
            <p class="text-xs text-gray-400 mt-1">HĐ hoàn thành</p>
        </div>
        <div class="card-dark p-4 text-center">
            <p class="text-2xl font-bold text-fsel-accent">{{ $totalLessonsCompleted }}</p>
            <p class="text-xs text-gray-400 mt-1">Bài hoàn thành</p>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('admin.reports.enrollments') }}" class="card-dark p-5 hover:border-fsel-blue/50 transition-all duration-300 group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-fsel-blue/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-fsel-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white group-hover:text-fsel-blue transition-colors">Báo cáo Ghi danh</h3>
                    <p class="text-xs text-gray-500">Chi tiết ghi danh theo khóa học</p>
                </div>
                <svg class="w-5 h-5 text-gray-500 group-hover:text-fsel-blue ml-auto transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
        <a href="{{ route('admin.reports.grades') }}" class="card-dark p-5 hover:border-fsel-purple/50 transition-all duration-300 group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-fsel-purple/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-fsel-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white group-hover:text-fsel-purple transition-colors">Báo cáo Điểm số</h3>
                    <p class="text-xs text-gray-500">Thống kê điểm và xếp loại</p>
                </div>
                <svg class="w-5 h-5 text-gray-500 group-hover:text-fsel-purple ml-auto transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
        <a href="{{ route('admin.reports.completions') }}" class="card-dark p-5 hover:border-fsel-teal/50 transition-all duration-300 group">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-fsel-teal/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-fsel-teal" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white group-hover:text-fsel-teal transition-colors">Báo cáo Hoàn thành</h3>
                    <p class="text-xs text-gray-500">Theo dõi hoạt động hoàn thành</p>
                </div>
                <svg class="w-5 h-5 text-gray-500 group-hover:text-fsel-teal ml-auto transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Grade Distribution --}}
        <div class="card-dark p-5">
            <h3 class="text-sm font-semibold text-white mb-4">Phân bố xếp loại</h3>
            <div class="space-y-3">
                @php
                    $gradeColors = ['A' => 'bg-green-500', 'B' => 'bg-blue-500', 'C' => 'bg-yellow-500', 'D' => 'bg-orange-500', 'F' => 'bg-red-500'];
                    $totalGrades = array_sum($gradeDistribution);
                @endphp
                @foreach($gradeDistribution as $letter => $count)
                    @php $pct = $totalGrades > 0 ? round(($count / $totalGrades) * 100) : 0; @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-8 text-sm font-bold text-white">{{ $letter }}</span>
                        <div class="flex-1 bg-fsel-navy rounded-full h-4">
                            <div class="{{ $gradeColors[$letter] }} h-4 rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                 style="width: {{ max($pct, 5) }}%">
                                @if($pct > 10)
                                    <span class="text-[10px] font-bold text-white">{{ $count }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 w-10 text-right">{{ $pct }}%</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top Courses --}}
        <div class="card-dark p-5">
            <h3 class="text-sm font-semibold text-white mb-4">Top khóa học (theo ghi danh)</h3>
            <div class="space-y-3">
                @forelse($topCourses as $index => $course)
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full bg-fsel-blue/20 text-fsel-blue text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white truncate">{{ $course->title }}</p>
                            <p class="text-xs text-gray-500">{{ $course->level }}</p>
                        </div>
                        <span class="text-sm font-semibold text-fsel-accent">{{ $course->enrollments_count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có dữ liệu ghi danh</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Enrollments --}}
    <div class="card-dark mt-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-fsel-border/20">
            <h3 class="text-sm font-semibold text-white">Ghi danh gần đây</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-fsel-border/20">
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-400">Học viên</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-400">Khóa học</th>
                        <th class="text-center px-5 py-3 text-xs font-medium text-gray-400">Trạng thái</th>
                        <th class="text-center px-5 py-3 text-xs font-medium text-gray-400">Tiến độ</th>
                        <th class="text-right px-5 py-3 text-xs font-medium text-gray-400">Ngày ghi danh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fsel-border/10">
                    @forelse($recentEnrollments as $enrollment)
                        <tr class="hover:bg-fsel-navy/30">
                            <td class="px-5 py-3 text-sm text-white">{{ $enrollment->user->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-sm text-gray-300">{{ $enrollment->course->title ?? '-' }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($enrollment->status === 'completed')
                                    <span class="bg-green-500/20 text-green-400 text-xs px-2 py-0.5 rounded-full">Hoàn thành</span>
                                @elseif($enrollment->status === 'active')
                                    <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2 py-0.5 rounded-full">Đang học</span>
                                @else
                                    <span class="bg-red-500/20 text-red-400 text-xs px-2 py-0.5 rounded-full">Hủy</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 bg-fsel-navy rounded-full h-1.5">
                                        <div class="bg-fsel-blue h-1.5 rounded-full" style="width: {{ $enrollment->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $enrollment->progress_percentage }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right text-xs text-gray-500">{{ $enrollment->enrolled_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500 text-sm">Chưa có ghi danh nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
