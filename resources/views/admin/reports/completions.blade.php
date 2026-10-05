@extends('layouts.admin')
@section('title', 'Báo cáo Hoàn Thành Từng Hoạt Động Theo Khóa Học')
@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ currentTab: '{{ $activeTab ?? 'overview' }}', matrixFilter: '' }">

    {{-- Header & Actions --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
                <a href="{{ route('admin.reports.index') }}" class="hover:text-fsel-teal transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Báo cáo LMS
                </a>
                <span>/</span>
                <span class="text-gray-300">Hoàn thành theo Khóa học</span>
            </div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2.5">
                <span class="p-2 bg-gradient-to-tr from-teal-600/30 to-emerald-600/30 border border-teal-500/30 rounded-xl text-teal-400">✅</span>
                Báo cáo Hoàn Thành Từng Hoạt Động Theo Khóa Học
            </h1>
            <p class="text-xs text-gray-400 mt-1">Phân tích tỷ lệ hoàn thành của từng bài học & hoạt động, phát hiện điểm nghẽn học tập và theo dõi ma trận tiến độ học viên</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="{{ route('admin.reports.export', array_merge(['type' => 'completions'], request()->query())) }}" 
               class="bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/30 text-emerald-300 text-xs font-semibold px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 shadow-sm shadow-emerald-500/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Xuất CSV Hoàn thành
            </a>
            <a href="{{ route('admin.reports.activity_grades') }}" 
               class="bg-slate-800 hover:bg-slate-700/80 border border-slate-700 text-gray-300 hover:text-white text-xs font-medium px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Xem Báo cáo Điểm số
            </a>
        </div>
    </div>

    {{-- Primary Course Selector Card --}}
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950/40 border border-slate-800/90 p-4 sm:p-5 rounded-2xl shadow-xl">
        <form method="GET" action="{{ route('admin.reports.completions') }}" id="courseFilterForm" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <input type="hidden" name="tab" :value="currentTab">
            <div class="flex-1 max-w-2xl">
                <label for="course_id" class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1.5 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                    Chọn Khóa học để Phân tích Hoạt động
                </label>
                <div class="relative">
                    <select name="course_id" id="course_id" onchange="this.form.submit()" 
                            class="w-full bg-slate-800/90 border border-slate-700 hover:border-teal-500/50 rounded-xl text-sm font-semibold text-white px-4 py-2.5 pr-10 focus:ring-2 focus:ring-teal-500 focus:outline-none transition-all shadow-inner">
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ $selectedCourseId == $c->id ? 'selected' : '' }}>
                                [{{ $c->level }}] {{ $c->title }} &mdash; ({{ $c->enrollments_count }} học viên ghi danh)
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            @if($selectedCourse)
                <div class="flex items-center gap-3 pt-2 md:pt-0 border-t md:border-t-0 border-slate-800">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-semibold text-white">{{ $selectedCourse->title }}</p>
                        <p class="text-[11px] text-gray-400">Trình độ: <span class="text-teal-400 font-bold">{{ $selectedCourse->level }}</span> &bull; {{ $selectedCourse->lessons->count() }} Bài học</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $selectedCourse->is_published ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30' }}">
                        {{ $selectedCourse->is_published ? 'Đang phát hành' : 'Bản nháp' }}
                    </span>
                </div>
            @endif
        </form>
    </div>

    @if(!$selectedCourse)
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-gray-400">
            <span class="text-4xl block mb-2">📚</span>
            <p class="text-base font-semibold text-white">Chưa chọn khóa học nào</p>
            <p class="text-xs text-gray-500 mt-1">Vui lòng chọn một khóa học ở trên để xem phân tích hoàn thành</p>
        </div>
    @else

        {{-- Course KPIs Bar --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
            {{-- Total Enrolled --}}
            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-teal-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Học viên Ghi danh</span>
                    <span class="text-base p-1.5 bg-teal-500/10 rounded-lg text-teal-400">👥</span>
                </div>
                <p class="text-2xl font-black text-white group-hover:text-teal-300 transition-colors">{{ $totalEnrolled }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Học viên đang theo học</p>
            </div>

            {{-- Total Activities --}}
            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-indigo-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Tổng Hoạt động</span>
                    <span class="text-base p-1.5 bg-indigo-500/10 rounded-lg text-indigo-400">📖</span>
                </div>
                <p class="text-2xl font-black text-indigo-300">{{ $totalActivities }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Trong {{ $selectedCourse->lessons->count() }} bài học</p>
            </div>

            {{-- Course Average Completion Rate --}}
            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-emerald-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Tỷ lệ Hoàn thành TB</span>
                    <span class="text-base p-1.5 bg-emerald-500/10 rounded-lg text-emerald-400">📈</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-black text-emerald-400">{{ $overallCourseCompletionRate }}%</p>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700" style="width: {{ $overallCourseCompletionRate }}%"></div>
                </div>
            </div>

            {{-- Fully Completed Students --}}
            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-amber-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Hoàn thành 100%</span>
                    <span class="text-base p-1.5 bg-amber-500/10 rounded-lg text-amber-400">🎓</span>
                </div>
                <p class="text-2xl font-black text-amber-300">{{ $fullyCompletedStudents }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Học viên đã học hết bài</p>
            </div>

            {{-- Bottleneck Activity Alert --}}
            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-rose-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Điểm Rơi rớt (Thấp nhất)</span>
                    <span class="text-base p-1.5 bg-rose-500/10 rounded-lg text-rose-400">⚠️</span>
                </div>
                @if($bottleneckActivity)
                    <p class="text-sm font-bold text-rose-300 truncate" title="{{ $bottleneckActivity['activity']->title }}">
                        {{ $bottleneckActivity['activity']->title }}
                    </p>
                    <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1.5">
                        <span class="font-bold text-rose-400">{{ $bottleneckActivity['completion_rate'] }}%</span>
                        hoàn thành ({{ $bottleneckActivity['completed_count'] }}/{{ $totalEnrolled }})
                    </p>
                @else
                    <p class="text-sm text-gray-500">Chưa có dữ liệu</p>
                @endif
            </div>
        </div>

        {{-- View Mode / Tabs Navigation --}}
        <div class="flex items-center gap-2 border-b border-slate-800/80 pb-2">
            <button type="button" @click="currentTab = 'overview'" 
                    :class="currentTab === 'overview' ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/25' : 'text-gray-400 hover:text-white hover:bg-slate-800/60'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Thống kê theo Hoạt động
                <span class="bg-white/20 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $totalActivities }}</span>
            </button>

            <button type="button" @click="currentTab = 'matrix'" 
                    :class="currentTab === 'matrix' ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/25' : 'text-gray-400 hover:text-white hover:bg-slate-800/60'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Ma trận Hoàn thành (Học viên x Hoạt động)
                <span class="bg-white/20 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $totalEnrolled }} HV</span>
            </button>

            <button type="button" @click="currentTab = 'log'" 
                    :class="currentTab === 'log' ? 'bg-teal-600 text-white shadow-lg shadow-teal-600/25' : 'text-gray-400 hover:text-white hover:bg-slate-800/60'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Nhật ký Chi tiết
            </button>
        </div>

        {{-- TAB 1: THỐNG KÊ HOÀN THÀNH THEO TỪNG HOẠT ĐỘNG TRONG KHÓA --}}
        <div x-show="currentTab === 'overview'" x-transition class="space-y-6">
            @forelse($selectedCourse->lessons as $lesson)
                @php
                    $lessonActivities = $activityStats->where('lesson.id', $lesson->id);
                    $lessonTotalActs = $lessonActivities->count();
                    $lessonAvgRate = $lessonActivities->count() > 0 ? round($lessonActivities->avg('completion_rate'), 1) : 0;
                @endphp
                <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-lg">
                    {{-- Lesson Header --}}
                    <div class="px-5 py-3.5 bg-slate-800/50 border-b border-slate-700/60 flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-xl bg-teal-500/20 text-teal-300 font-bold text-xs flex items-center justify-center border border-teal-500/30">
                                {{ $lesson->order ?? 1 }}
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-white">{{ $lesson->title }}</h3>
                                <p class="text-[11px] text-gray-400">{{ $lessonTotalActs }} hoạt động trong bài học này</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <span class="text-xs font-bold text-white">{{ $lessonAvgRate }}%</span>
                                <span class="block text-[9px] text-gray-400">Hoàn thành TB bài học</span>
                            </div>
                            <div class="w-24 bg-slate-700/60 rounded-full h-2 overflow-hidden hidden sm:block">
                                <div class="bg-teal-400 h-2 rounded-full transition-all duration-500" style="width: {{ $lessonAvgRate }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Activities Table for this Lesson --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-850 border-b border-slate-800 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                                    <th class="px-4 py-2.5 w-12 text-center">#</th>
                                    <th class="px-4 py-2.5">Hoạt động</th>
                                    <th class="px-4 py-2.5">Tiêu chí hoàn thành</th>
                                    <th class="px-4 py-2.5 text-center">Số HV hoàn thành</th>
                                    <th class="px-4 py-2.5 text-center w-48">Tỷ lệ Hoàn thành</th>
                                    <th class="px-4 py-2.5 text-center">Điểm TB</th>
                                    <th class="px-4 py-2.5 text-center">Thời gian TB</th>
                                    <th class="px-4 py-2.5 text-center">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @forelse($lessonActivities as $stat)
                                    @php
                                        $act = $stat['activity'];
                                        $rate = $stat['completion_rate'];
                                        $compCount = $stat['completed_count'];
                                        $typeInfo = $activityTypes[$act->type] ?? null;
                                    @endphp
                                    <tr class="hover:bg-slate-800/30 transition-colors">
                                        {{-- Order --}}
                                        <td class="px-4 py-3 text-center text-xs text-gray-500 font-mono">
                                            {{ $act->order ?? '-' }}
                                        </td>

                                        {{-- Activity Title & Type --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-base flex-shrink-0">{{ $typeInfo['icon'] ?? '🎯' }}</span>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-white truncate max-w-[260px]">{{ $act->title }}</p>
                                                    <span class="inline-block text-[9px] font-semibold px-1.5 py-0.2 rounded bg-slate-800 text-gray-400 border border-slate-700/60 uppercase">
                                                        {{ $typeInfo['label'] ?? $act->type }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Completion Criteria --}}
                                        <td class="px-4 py-3 text-xs text-gray-400">
                                            @php
                                                $compType = $act->completion_type ?? 'manual';
                                            @endphp
                                            <span class="inline-flex items-center gap-1 text-[11px] text-gray-300">
                                                @if($compType === 'auto_grade')
                                                    🎯 Đạt &ge; {{ $act->passing_grade ?? 50 }}đ
                                                @elseif($compType === 'auto_view')
                                                    👁️ Xem xong bài
                                                @elseif($compType === 'auto_submit')
                                                    📤 Nộp bài tập
                                                @else
                                                    ✋ Học viên tự đánh dấu
                                                @endif
                                            </span>
                                        </td>

                                        {{-- Completed Count / Enrolled --}}
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-xs font-bold text-white">{{ $compCount }}</span>
                                            <span class="text-xs text-gray-500">/ {{ $totalEnrolled }}</span>
                                            <span class="block text-[9px] text-gray-500">{{ $stat['incomplete_count'] }} chưa xong</span>
                                        </td>

                                        {{-- Completion Rate & Progress Bar --}}
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                                <span class="{{ $rate >= 80 ? 'text-emerald-400' : ($rate >= 50 ? 'text-blue-400' : 'text-rose-400') }}">
                                                    {{ $rate }}%
                                                </span>
                                                @if($rate < 50 && $totalEnrolled > 0)
                                                    <span class="text-[9px] font-bold text-rose-400 bg-rose-500/10 px-1 py-0.2 rounded">Cần hỗ trợ</span>
                                                @endif
                                            </div>
                                            <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                                <div class="{{ $rate >= 80 ? 'bg-emerald-500' : ($rate >= 50 ? 'bg-blue-500' : 'bg-rose-500') }} h-2 rounded-full transition-all duration-700" 
                                                     style="width: {{ $rate }}%"></div>
                                            </div>
                                        </td>

                                        {{-- Average Score --}}
                                        <td class="px-4 py-3 text-center text-xs">
                                            @if($stat['avg_score'] !== null)
                                                <span class="font-bold {{ $stat['avg_score'] >= 80 ? 'text-emerald-400' : ($stat['avg_score'] >= 60 ? 'text-yellow-400' : 'text-rose-400') }}">
                                                    {{ $stat['avg_score'] }}đ
                                                </span>
                                            @else
                                                <span class="text-gray-600">-</span>
                                            @endif
                                        </td>

                                        {{-- Average Time Spent --}}
                                        <td class="px-4 py-3 text-center text-xs text-gray-400 font-mono">
                                            {{ $stat['avg_time_spent'] > 0 ? gmdate('i:s', $stat['avg_time_spent']) : '-' }}
                                        </td>

                                        {{-- Status --}}
                                        <td class="px-4 py-3 text-center">
                                            @if($rate == 100)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                                    100% Xong
                                                </span>
                                            @elseif($rate >= 50)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-teal-400 bg-teal-500/10 px-2 py-0.5 rounded-full border border-teal-500/20">
                                                    Tiến độ tốt
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                                                    Tỷ lệ thấp
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-xs text-gray-500">Bài học này chưa có hoạt động nào</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-gray-500">
                    Khóa học này chưa có bài học nào
                </div>
            @endforelse
        </div>

        {{-- TAB 2: MA TRẬN HOÀN THÀNH (LEARNER X ACTIVITY MATRIX) --}}
        <div x-show="currentTab === 'matrix'" x-transition class="space-y-4">
            {{-- Matrix Controls --}}
            <div class="bg-slate-900/90 border border-slate-800/80 p-3.5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-white uppercase tracking-wider">🎯 Ma trận Hoàn thành Hoạt động</span>
                    <span class="text-xs text-gray-400">({{ $learnerMatrix->count() }} học viên x {{ $totalActivities }} hoạt động)</span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative w-64">
                        <input type="text" x-model="matrixFilter" placeholder="Lọc học viên trong ma trận..." 
                               class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white pl-8 pr-3 py-1.5 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div class="flex items-center gap-4 text-xs text-gray-400 px-1 flex-wrap">
                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[9px] font-bold">✓</span> Đã hoàn thành</span>
                <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded-full bg-slate-700 text-gray-400 flex items-center justify-center text-[9px]">&minus;</span> Chưa hoàn thành</span>
                <span class="text-gray-500 text-[11px] ml-auto hidden md:inline">💡 Rê chuột vào ô để xem điểm và thời gian hoàn thành</span>
            </div>

            {{-- Matrix Table --}}
            <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-20 bg-slate-800 shadow-md">
                            <tr>
                                {{-- Sticky Student Column Header --}}
                                <th class="sticky left-0 z-30 bg-slate-800 px-4 py-3 text-xs font-bold text-white uppercase tracking-wider w-64 border-r border-slate-700/80 shadow-r">
                                    Học viên &bull; Tiến độ
                                </th>

                                {{-- Activity Headers --}}
                                @foreach($selectedCourse->lessons as $lesson)
                                    @foreach($lesson->activities as $act)
                                        @php $typeInfo = $activityTypes[$act->type] ?? null; @endphp
                                        <th class="px-2.5 py-2.5 text-center text-[10px] font-medium text-gray-300 border-r border-slate-700/50 min-w-[70px] max-w-[90px]"
                                            title="{{ $lesson->title }} &bull; {{ $act->title }} ({{ $typeInfo['label'] ?? $act->type }})">
                                            <span class="text-sm block">{{ $typeInfo['icon'] ?? '🎯' }}</span>
                                            <span class="block truncate font-bold text-white text-[10px]">{{ $act->title }}</span>
                                            <span class="text-[8px] text-gray-400 uppercase">{{ substr($act->type, 0, 5) }}</span>
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($learnerMatrix as $row)
                                @php
                                    $u = $row['user'];
                                    $uProgress = $row['progress_percentage'];
                                @endphp
                                <tr class="hover:bg-slate-800/40 transition-colors"
                                    x-show="!matrixFilter || '{{ strtolower($u->name) }} {{ strtolower($u->email) }}'.includes(matrixFilter.toLowerCase())">
                                    {{-- Student Sticky Column --}}
                                    <td class="sticky left-0 z-10 bg-slate-900/95 hover:bg-slate-850 px-4 py-3 border-r border-slate-800 shadow-r">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-teal-500 to-emerald-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                                {{ strtoupper(substr($u->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-bold text-white truncate">{{ $u->name }}</p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <div class="w-16 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                                        <div class="bg-teal-400 h-1.5 rounded-full" style="width: {{ $uProgress }}%"></div>
                                                    </div>
                                                    <span class="text-[10px] font-bold text-teal-300">{{ $uProgress }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Activity Cells --}}
                                    @foreach($selectedCourse->lessons as $lesson)
                                        @foreach($lesson->activities as $act)
                                            @php
                                                $compState = $row['activities_map'][$act->id] ?? null;
                                                $isComp = $compState && $compState['is_completed'];
                                                $score = $compState['score'] ?? null;
                                                $compAt = $compState['completed_at'] ?? null;
                                            @endphp
                                            <td class="px-2 py-2.5 text-center border-r border-slate-800/40 group relative">
                                                @if($isComp)
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 font-black text-xs border border-emerald-500/30 cursor-pointer shadow-sm shadow-emerald-500/10"
                                                          title="Hoàn thành{{ $score !== null ? ' - Điểm: '.$score : '' }}{{ $compAt ? ' lúc '.$compAt->format('d/m H:i') : '' }}">
                                                        ✓
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-slate-800 text-gray-600 text-xs font-bold"
                                                          title="Chưa hoàn thành">
                                                        &minus;
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $totalActivities + 1 }}" class="px-4 py-8 text-center text-xs text-gray-500">
                                        Chưa có học viên ghi danh vào khóa học này
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 3: NHẬT KÝ CHI TIẾT HOÀN THÀNH --}}
        <div x-show="currentTab === 'log'" x-transition class="space-y-4">
            {{-- Filter Form for Log --}}
            <div class="bg-slate-900/90 border border-slate-800/80 p-4 rounded-2xl">
                <form method="GET" action="{{ route('admin.reports.completions') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    <input type="hidden" name="course_id" value="{{ $selectedCourseId }}">
                    <input type="hidden" name="tab" value="log">

                    {{-- Lesson filter --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 mb-1">Bài học</label>
                        <select name="lesson_id" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                            <option value="">Tất cả Bài học</option>
                            @foreach($availableLessons as $l)
                                <option value="{{ $l->id }}" {{ request('lesson_id') == $l->id ? 'selected' : '' }}>
                                    {{ $l->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Activity Type filter --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 mb-1">Loại hoạt động</label>
                        <select name="activity_type" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                            <option value="">Tất cả loại hoạt động</option>
                            @foreach($activityTypes as $typeKey => $meta)
                                <option value="{{ $typeKey }}" {{ request('activity_type') == $typeKey ? 'selected' : '' }}>
                                    {{ $meta['icon'] }} {{ $meta['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Completion Status filter --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 mb-1">Trạng thái hoàn thành</label>
                        <select name="status" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                            <option value="">Tất cả trạng thái</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>✅ Đã hoàn thành</option>
                            <option value="incomplete" {{ request('status') === 'incomplete' ? 'selected' : '' }}>⏳ Chưa hoàn thành</option>
                        </select>
                    </div>

                    {{-- Search --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 mb-1">Tìm học viên / HĐ</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tên học viên, email..." 
                                   class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white pl-8 pr-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all shadow-md shadow-teal-600/20 w-full sm:w-auto">
                            Lọc
                        </button>
                        @if(request()->hasAny(['lesson_id', 'activity_type', 'status', 'search']))
                            <a href="{{ route('admin.reports.completions', ['course_id' => $selectedCourseId, 'tab' => 'log']) }}" class="text-xs text-gray-400 hover:text-white px-2 py-2">
                                Xóa lọc
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-800/60 border-b border-slate-700/60">
                                <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Học viên</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Hoạt động</th>
                                <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Bài học</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Tiêu chí</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Trạng thái</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Điểm số</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Thời gian</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Ngày hoàn thành</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($detailedCompletions as $c)
                                @php
                                    $act = $c->activity;
                                    $lesson = $c->lesson;
                                    $user = $c->user;
                                    $typeInfo = $activityTypes[$act?->type] ?? null;
                                    $isDone = $c->completed_at !== null;
                                @endphp
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    {{-- Student --}}
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-teal-500 to-emerald-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                                                {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-white truncate">{{ $user?->name ?? 'Chưa rõ' }}</p>
                                                <p class="text-[11px] text-gray-500 truncate">{{ $user?->email ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Activity --}}
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base flex-shrink-0">{{ $typeInfo['icon'] ?? '🎯' }}</span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-white truncate max-w-[200px]">{{ $act?->title ?? 'N/A' }}</p>
                                                <span class="inline-block text-[9px] font-semibold px-1.5 py-0.2 rounded bg-slate-800 text-gray-400 border border-slate-700/60 uppercase">
                                                    {{ $typeInfo['label'] ?? ($act?->type ?? 'activity') }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Lesson --}}
                                    <td class="px-4 py-3.5 text-xs text-gray-400 truncate max-w-[180px]">
                                        {{ $lesson?->title ?? 'N/A' }}
                                    </td>

                                    {{-- Criteria --}}
                                    <td class="px-4 py-3.5 text-center text-[11px] text-gray-400">
                                        @if($act?->completion_type === 'auto_grade')
                                            &ge; {{ $act->passing_grade ?? 50 }}đ
                                        @elseif($act?->completion_type === 'auto_view')
                                            Xem xong
                                        @elseif($act?->completion_type === 'auto_submit')
                                            Nộp bài
                                        @else
                                            Thủ công
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3.5 text-center">
                                        @if($isDone)
                                            <span class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                ✓ Hoàn thành
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-slate-700/60 text-gray-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                ⏳ Đang học
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Score --}}
                                    <td class="px-4 py-3.5 text-center text-xs font-bold text-white">
                                        {{ $c->score > 0 ? $c->score . 'đ' : '-' }}
                                    </td>

                                    {{-- Time Spent --}}
                                    <td class="px-4 py-3.5 text-center text-xs text-gray-400 font-mono">
                                        {{ $c->time_spent_seconds > 0 ? gmdate('i:s', $c->time_spent_seconds) : '-' }}
                                    </td>

                                    {{-- Completed Date --}}
                                    <td class="px-5 py-3.5 text-right text-xs text-gray-400 font-mono">
                                        {{ $c->completed_at ? $c->completed_at->format('d/m/Y H:i') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-gray-500">
                                        Không tìm thấy bản ghi hoàn thành nào phù hợp
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($detailedCompletions->hasPages())
                    <div class="px-5 py-3 border-t border-slate-800/80 bg-slate-900/60">
                        {{ $detailedCompletions->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
@endsection
