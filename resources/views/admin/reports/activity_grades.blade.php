@extends('layouts.admin')
@section('title', 'Báo cáo Điểm số Từng Hoạt Động')
@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ currentTab: '{{ $activeTab ?? 'matrix' }}', matrixSearch: '' }">

    {{-- Header & Sub-navigation --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
                <a href="{{ route('admin.reports.index') }}" class="hover:text-fsel-teal transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Báo cáo LMS
                </a>
                <span>/</span>
                <span class="text-gray-300">Điểm số từng hoạt động</span>
            </div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2.5">
                <span class="p-2 bg-gradient-to-tr from-indigo-600/30 to-purple-600/30 border border-indigo-500/30 rounded-xl text-indigo-400">📊</span>
                Báo cáo Điểm số Từng Hoạt Động
            </h1>
            <p class="text-xs text-gray-400 mt-1">Theo dõi bảng điểm ma trận học viên x hoạt động, phân tích tỷ lệ đạt và điểm trung bình theo từng khóa học</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            {{-- Export CSV Matrix --}}
            @if($selectedCourse)
                <a href="{{ route('admin.reports.export', ['type' => 'activity-grades', 'format' => 'matrix', 'course_id' => $selectedCourseId]) }}" 
                   class="bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/30 text-indigo-300 text-xs font-semibold px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 shadow-sm shadow-indigo-500/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Xuất CSV Ma trận Điểm
                </a>
            @endif

            {{-- Export CSV List --}}
            <a href="{{ route('admin.reports.export', array_merge(['type' => 'activity-grades'], request()->query())) }}" 
               class="bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/30 text-emerald-300 text-xs font-semibold px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 shadow-sm shadow-emerald-500/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Xuất CSV Chi tiết
            </a>
        </div>
    </div>

    {{-- Tabs switcher: Điểm từng hoạt động vs Điểm tổng kết khóa học --}}
    <div class="flex items-center gap-2 border-b border-slate-800/80 pb-1">
        <a href="{{ route('admin.reports.activity_grades') }}" 
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 bg-indigo-600 text-white shadow-lg shadow-indigo-600/25">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Bảng điểm Từng Hoạt động
        </a>
        <a href="{{ route('admin.reports.grades', ['tab' => 'course']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-medium text-gray-400 hover:text-white hover:bg-slate-800/60 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
            Điểm Tổng kết Khóa học (GPA)
        </a>
        <a href="{{ route('admin.reports.completions') }}" 
           class="px-4 py-2 rounded-xl text-xs font-medium text-gray-400 hover:text-teal-300 hover:bg-teal-500/10 transition-all flex items-center gap-2 ml-auto">
            <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Báo cáo Hoàn thành theo Khóa học →
        </a>
    </div>

    {{-- Primary Course Selector Card --}}
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950/40 border border-slate-800/90 p-4 sm:p-5 rounded-2xl shadow-xl">
        <form method="GET" action="{{ route('admin.reports.activity_grades') }}" id="courseFilterForm" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <input type="hidden" name="tab" :value="currentTab">
            <div class="flex-1 max-w-2xl">
                <label for="course_id" class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-1.5 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    Chọn Khóa học để Xem Bảng Điểm Ma trận
                </label>
                <div class="relative">
                    <select name="course_id" id="course_id" onchange="this.form.submit()" 
                            class="w-full bg-slate-800/90 border border-slate-700 hover:border-indigo-500/50 rounded-xl text-sm font-semibold text-white px-4 py-2.5 pr-10 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-inner">
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
                        <p class="text-[11px] text-gray-400">Điểm TB Lớp: <span class="text-indigo-400 font-black text-sm">{{ $classOverallAvgScore }}đ</span> &bull; {{ $totalEnrolled }} Học viên</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/30">
                        {{ $selectedCourse->level }}
                    </span>
                </div>
            @endif
        </form>
    </div>

    {{-- View Mode Tabs: Ma trận Điểm số vs Danh sách chi tiết vs Thống kê tổng quan --}}
    <div class="flex items-center gap-2 border-b border-slate-800/80 pb-2">
        <button type="button" @click="currentTab = 'matrix'" 
                :class="currentTab === 'matrix' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-gray-400 hover:text-white hover:bg-slate-800/60'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            🎯 Ma trận Bảng điểm (Học viên x Hoạt động)
            <span class="bg-white/20 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $totalEnrolled }} HV</span>
        </button>

        <button type="button" @click="currentTab = 'table'" 
                :class="currentTab === 'table' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-gray-400 hover:text-white hover:bg-slate-800/60'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            📋 Danh sách Chi tiết Lượt nộp
            <span class="bg-white/20 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $totalGraded }}</span>
        </button>

        <button type="button" @click="currentTab = 'analytics'" 
                :class="currentTab === 'analytics' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-gray-400 hover:text-white hover:bg-slate-800/60'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
            📊 Thống kê & Xếp loại
        </button>
    </div>

    {{-- TAB 1: MA TRẬN BẢNG ĐIỂM HỌC VIÊN X HOẠT ĐỘNG (LEARNER X ACTIVITY GRADE MATRIX) --}}
    <div x-show="currentTab === 'matrix'" x-transition class="space-y-4">
        {{-- Matrix Controls Bar --}}
        <div class="bg-slate-900/90 border border-slate-800/80 p-3.5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    Ma trận Điểm số Hoạt động
                </span>
                <span class="text-xs text-gray-400">({{ $gradeMatrix->count() }} học viên x {{ $courseActivities->count() }} hoạt động)</span>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative w-64">
                    <input type="text" x-model="matrixSearch" placeholder="Tìm tên hoặc email học viên..." 
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white pl-8 pr-3 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
        </div>

        {{-- Grade Legend --}}
        <div class="flex items-center gap-3.5 text-xs text-gray-400 px-1 flex-wrap">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-emerald-500/20 border border-emerald-500/50"></span> &ge; 80đ (Giỏi / XS)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-blue-500/20 border border-blue-500/50"></span> 65 - 79đ (Khá)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-amber-500/20 border border-amber-500/50"></span> 50 - 64đ (Trung bình)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-rose-500/20 border border-rose-500/50"></span> &lt; 50đ (Chưa đạt sàn)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-slate-800 border border-slate-700"></span> &minus; Chưa làm</span>
            <span class="text-gray-500 text-[11px] ml-auto hidden lg:inline">💡 Rê chuột vào ô điểm để xem ngày hoàn thành & thời gian làm</span>
        </div>

        {{-- Matrix Table --}}
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto max-h-[640px] overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    {{-- Sticky Table Header --}}
                    <thead class="sticky top-0 z-20 bg-slate-800 shadow-md">
                        <tr>
                            {{-- Sticky Student Column Header --}}
                            <th class="sticky left-0 z-30 bg-slate-800 px-4 py-3 text-xs font-bold text-white uppercase tracking-wider w-72 border-r border-slate-700/80 shadow-r">
                                Học viên &bull; Điểm TB &bull; Xếp loại
                            </th>

                            {{-- Activity Headers --}}
                            @if($selectedCourse)
                                @foreach($selectedCourse->lessons as $lesson)
                                    @foreach($lesson->activities as $act)
                                        @php $typeInfo = $activityTypes[$act->type] ?? null; @endphp
                                        <th class="px-2.5 py-2.5 text-center text-[10px] font-medium text-gray-300 border-r border-slate-700/50 min-w-[75px] max-w-[95px]"
                                            title="{{ $lesson->title }} &bull; {{ $act->title }} ({{ $typeInfo['label'] ?? $act->type }}) &bull; Sàn: {{ $act->passing_grade ?? 50 }}đ">
                                            <span class="text-sm block">{{ $typeInfo['icon'] ?? '🎯' }}</span>
                                            <span class="block truncate font-bold text-white text-[10px]">{{ $act->title }}</span>
                                            <span class="text-[8px] text-gray-400 block">Sàn: {{ $act->passing_grade ?? 50 }}đ</span>
                                        </th>
                                    @endforeach
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($gradeMatrix as $row)
                            @php
                                $u = $row['user'];
                                $avg = $row['avg_score'];
                                $letter = $row['letter_grade'];
                                $doneCount = $row['completed_count'];
                                $totalActs = $row['total_activities'];
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition-colors"
                                x-show="!matrixSearch || '{{ strtolower($u->name) }} {{ strtolower($u->email) }}'.includes(matrixSearch.toLowerCase())">
                                {{-- Student Sticky Column --}}
                                <td class="sticky left-0 z-10 bg-slate-900/95 hover:bg-slate-850 px-4 py-3 border-r border-slate-800 shadow-r">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0 shadow-sm">
                                                {{ strtoupper(substr($u->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-white truncate max-w-[130px]">{{ $u->name }}</p>
                                                <p class="text-[10px] text-gray-400 truncate max-w-[130px]">{{ $u->email }}</p>
                                            </div>
                                        </div>

                                        {{-- Average Score & Letter Grade Badge --}}
                                        <div class="text-right flex-shrink-0">
                                            @if($avg !== null)
                                                <div class="flex items-center gap-1.5 justify-end">
                                                    <span class="text-xs font-black {{ $avg >= 80 ? 'text-emerald-400' : ($avg >= 65 ? 'text-blue-400' : ($avg >= 50 ? 'text-amber-400' : 'text-rose-400')) }}">
                                                        {{ $avg }}đ
                                                    </span>
                                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded {{ $letter === 'A' ? 'bg-emerald-500/20 text-emerald-300' : ($letter === 'B' ? 'bg-blue-500/20 text-blue-300' : ($letter === 'C' ? 'bg-yellow-500/20 text-yellow-300' : ($letter === 'D' ? 'bg-orange-500/20 text-orange-300' : 'bg-red-500/20 text-red-300'))) }}">
                                                        {{ $letter }}
                                                    </span>
                                                </div>
                                                <span class="block text-[9px] text-gray-500 font-mono">{{ $doneCount }}/{{ $totalActs }} HĐ</span>
                                            @else
                                                <span class="text-xs text-gray-600 font-mono">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Activity Score Cells --}}
                                @if($selectedCourse)
                                    @foreach($selectedCourse->lessons as $lesson)
                                        @foreach($lesson->activities as $act)
                                            @php
                                                $scoreData = $row['activities_scores'][$act->id] ?? null;
                                                $hasScore = $scoreData && $scoreData['has_score'];
                                                $score = $hasScore ? $scoreData['score'] : null;
                                                $isPassed = $scoreData['is_passed'] ?? false;
                                                $compAt = $scoreData['completed_at'] ?? null;
                                                $time = $scoreData['time_spent'] ?? 0;
                                            @endphp
                                            <td class="px-2 py-2.5 text-center border-r border-slate-800/40 group relative">
                                                @if($hasScore && $score !== null)
                                                    @php
                                                        $badgeColor = match(true) {
                                                            $score >= 80 => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 hover:bg-emerald-500/30',
                                                            $score >= 65 => 'bg-blue-500/20 text-blue-300 border-blue-500/40 hover:bg-blue-500/30',
                                                            $score >= 50 => 'bg-amber-500/20 text-amber-300 border-amber-500/40 hover:bg-amber-500/30',
                                                            default      => 'bg-rose-500/20 text-rose-300 border-rose-500/40 hover:bg-rose-500/30',
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center justify-center min-w-[34px] px-1.5 py-1 rounded-lg text-xs font-bold border transition-transform group-hover:scale-105 cursor-pointer {{ $badgeColor }}"
                                                          title="{{ $u->name }} &#10;{{ $act->title }} &#10;Điểm: {{ $score }}/{{ $scoreData['max_score'] }} ({{ $scoreData['percentage'] }}%) &#10;Yêu cầu: >= {{ $scoreData['passing_grade'] }}đ ({{ $isPassed ? 'ĐẠT' : 'CHƯA ĐẠT' }}) &#10;Làm lúc: {{ $compAt ? $compAt->format('d/m/Y H:i') : 'N/A' }} &#10;Thời gian: {{ $time > 0 ? gmdate('i:s', $time) : '-' }}">
                                                        {{ $score }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center min-w-[30px] py-1 text-xs text-gray-600 font-bold"
                                                          title="Chưa có điểm">
                                                        &minus;
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    @endforeach
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $courseActivities->count() + 1 }}" class="px-4 py-8 text-center text-xs text-gray-500">
                                    Chưa có học viên nào ghi danh hoặc làm bài trong khóa học này
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Sticky Table Footer: Class Column Summaries (Class Averages) --}}
                    @if($gradeMatrix->isNotEmpty() && $selectedCourse)
                        <tfoot class="sticky bottom-0 z-20 bg-slate-850 border-t-2 border-slate-700 shadow-2xl">
                            <tr class="font-bold">
                                {{-- Sticky Class Average Cell --}}
                                <td class="sticky left-0 z-30 bg-slate-850 px-4 py-3 border-r border-slate-700/80 shadow-r">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm">🎯</span>
                                            <div>
                                                <span class="text-xs font-black text-white uppercase tracking-wider block">Điểm TB Cả Lớp</span>
                                                <span class="text-[10px] text-gray-400 font-normal">Toàn bộ hoạt động</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-black text-indigo-400">{{ $classOverallAvgScore }}đ</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Activity Average Footers --}}
                                @foreach($selectedCourse->lessons as $lesson)
                                    @foreach($lesson->activities as $act)
                                        @php
                                            $colStat = $activityColumnSummaries[$act->id] ?? null;
                                            $colAvg = $colStat['avg_score'] ?? null;
                                            $colRate = $colStat['pass_rate'] ?? null;
                                        @endphp
                                        <td class="px-2 py-2.5 text-center border-r border-slate-700/50 bg-slate-850"
                                            title="Điểm TB lớp: {{ $colAvg ?? '-' }}đ &bull; Tỷ lệ đạt: {{ $colRate ?? 0 }}%">
                                            @if($colAvg !== null)
                                                <span class="text-xs font-black block {{ $colAvg >= 80 ? 'text-emerald-400' : ($colAvg >= 65 ? 'text-blue-400' : 'text-amber-400') }}">
                                                    {{ $colAvg }}
                                                </span>
                                                <span class="text-[8px] text-gray-400 block font-normal">{{ $colRate ?? 0 }}% đạt</span>
                                            @else
                                                <span class="text-xs text-gray-600 block">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: DANH SÁCH CHI TIẾT LƯỢT NỘP (TABLE LOG) --}}
    <div x-show="currentTab === 'table'" x-transition class="space-y-4">
        {{-- Filter Panel --}}
        <div class="bg-slate-900/90 border border-slate-800/80 p-4 rounded-2xl">
            <form method="GET" action="{{ route('admin.reports.activity_grades') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <input type="hidden" name="tab" value="table">

                {{-- Course filter --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 mb-1">Khóa học</label>
                    <select name="course_id" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả Khóa học</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id', $selectedCourseId) == $c->id ? 'selected' : '' }}>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Lesson filter --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 mb-1">Bài học</label>
                    <select name="lesson_id" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
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
                    <select name="activity_type" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả loại hoạt động</option>
                        @foreach($activityTypes as $typeKey => $meta)
                            <option value="{{ $typeKey }}" {{ request('activity_type') == $typeKey ? 'selected' : '' }}>
                                {{ $meta['icon'] }} {{ $meta['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Result Status --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 mb-1">Kết quả điểm</label>
                    <select name="result" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Tất cả kết quả</option>
                        <option value="passed" {{ request('result') === 'passed' ? 'selected' : '' }}>✅ Đạt điểm sàn</option>
                        <option value="failed" {{ request('result') === 'failed' ? 'selected' : '' }}>❌ Chưa đạt điểm sàn</option>
                        <option value="excellent" {{ request('result') === 'excellent' ? 'selected' : '' }}>🏆 Xuất sắc (>= 90đ)</option>
                        <option value="good" {{ request('result') === 'good' ? 'selected' : '' }}>⭐ Giỏi (80-89đ)</option>
                        <option value="average" {{ request('result') === 'average' ? 'selected' : '' }}>⚡ Trung bình (60-79đ)</option>
                        <option value="weak" {{ request('result') === 'weak' ? 'selected' : '' }}>⚠️ Yếu (&lt; 60đ)</option>
                    </select>
                </div>

                {{-- Sort --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 mb-1">Sắp xếp theo</label>
                    <select name="sort" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="score_desc" {{ request('sort') === 'score_desc' ? 'selected' : '' }}>Điểm cao &darr;</option>
                        <option value="score_asc" {{ request('sort') === 'score_asc' ? 'selected' : '' }}>Điểm thấp &uarr;</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Tên học viên (A-Z)</option>
                        <option value="time_desc" {{ request('sort') === 'time_desc' ? 'selected' : '' }}>Thời gian làm lâu nhất</option>
                    </select>
                </div>

                {{-- Search & Actions --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-400 mb-1">Tìm kiếm</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tên học viên, HĐ..." 
                               class="w-full bg-slate-800 border border-slate-700 rounded-xl text-xs text-white pl-8 pr-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex items-center justify-end gap-2 pt-2 border-t border-slate-800/80">
                    @if(request()->hasAny(['lesson_id', 'activity_type', 'result', 'sort', 'search']))
                        <a href="{{ route('admin.reports.activity_grades', ['course_id' => $selectedCourseId, 'tab' => 'table']) }}" class="text-xs text-gray-400 hover:text-white px-3 py-1.5 transition-colors">
                            Xóa bộ lọc
                        </a>
                    @endif
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all shadow-md shadow-indigo-600/20">
                        Áp dụng lọc
                    </button>
                </div>
            </form>
        </div>

        {{-- Main Detailed Table --}}
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-5 py-4 border-b border-slate-800/80 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>📋</span>
                        Chi tiết Điểm số Từng Lượt Làm Bài của Học viên
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">Hiển thị {{ $activityGrades->firstItem() ?? 0 }} - {{ $activityGrades->lastItem() ?? 0 }} trên tổng số {{ $activityGrades->total() }} bản ghi</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-800/60 border-b border-slate-700/60">
                            <th class="px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Học viên</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Khóa học / Bài học</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Hoạt động</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Điểm số</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Điểm sàn</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Kết quả</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">Thời gian</th>
                            <th class="px-5 py-3 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Ngày hoàn thành</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($activityGrades as $grade)
                            @php
                                $user = $grade->user;
                                $act = $grade->activity;
                                $lesson = $grade->lesson;
                                $course = $lesson?->course;
                                $passingGrade = $act?->passing_grade ?? 50;
                                $score = $grade->score;
                                $maxScore = $grade->max_score > 0 ? $grade->max_score : 100;
                                $pct = round(($score / $maxScore) * 100, 1);
                                $isPassed = $score >= $passingGrade;
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                {{-- Student --}}
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white shadow-sm flex-shrink-0">
                                            {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-white truncate">{{ $user?->name ?? 'Chưa rõ' }}</p>
                                            <p class="text-[11px] text-gray-500 truncate">{{ $user?->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Course & Lesson --}}
                                <td class="px-4 py-3.5">
                                    <p class="text-xs font-medium text-gray-200 truncate max-w-[200px]">{{ $course?->title ?? 'N/A' }}</p>
                                    <p class="text-[11px] text-gray-500 truncate max-w-[200px]">{{ $lesson?->title ?? 'N/A' }}</p>
                                </td>

                                {{-- Activity --}}
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base flex-shrink-0">{{ $activityTypes[$act?->type]['icon'] ?? '🎯' }}</span>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-white truncate max-w-[220px]">{{ $act?->title ?? 'N/A' }}</p>
                                            <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-800 text-gray-400 border border-slate-700/60 uppercase">
                                                {{ $activityTypes[$act?->type]['label'] ?? ($act?->type ?? 'activity') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Score --}}
                                <td class="px-4 py-3.5 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="text-sm font-black {{ $pct >= 80 ? 'text-emerald-400' : ($pct >= 60 ? 'text-yellow-400' : 'text-rose-400') }}">
                                            {{ $score }}/{{ $maxScore }}
                                        </span>
                                        <span class="text-[10px] font-semibold text-gray-400">({{ $pct }}%)</span>
                                    </div>
                                </td>

                                {{-- Passing Grade Requirement --}}
                                <td class="px-4 py-3.5 text-center text-xs font-semibold text-gray-400">
                                    {{ $passingGrade }}đ
                                </td>

                                {{-- Result Badge --}}
                                <td class="px-4 py-3.5 text-center">
                                    @if($isPassed)
                                        <span class="inline-flex items-center gap-1 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            ĐẠT
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-rose-500/10 border border-rose-500/30 text-rose-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            CHƯA ĐẠT
                                        </span>
                                    @endif
                                </td>

                                {{-- Time Spent --}}
                                <td class="px-4 py-3.5 text-center text-xs text-gray-400">
                                    @if($grade->time_spent_seconds > 0)
                                        <span class="font-mono text-gray-300">{{ gmdate('i:s', $grade->time_spent_seconds) }}</span>
                                    @else
                                        <span class="text-gray-600">-</span>
                                    @endif
                                </td>

                                {{-- Completed At --}}
                                <td class="px-5 py-3.5 text-right text-xs text-gray-400 font-mono">
                                    {{ $grade->completed_at ? $grade->completed_at->format('d/m/Y H:i') : ($grade->created_at ? $grade->created_at->format('d/m/Y H:i') : '-') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-3xl mb-2">🔍</span>
                                        <p class="text-sm font-semibold text-gray-400">Không tìm thấy dữ liệu điểm số nào phù hợp</p>
                                        <p class="text-xs text-gray-600 mt-1">Hãy thử thay đổi tiêu chí lọc hoặc tìm kiếm</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($activityGrades->hasPages())
                <div class="px-5 py-3 border-t border-slate-800/80 bg-slate-900/60">
                    {{ $activityGrades->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 3: THỐNG KÊ TỔNG QUAN & XẾP LOẠI --}}
    <div x-show="currentTab === 'analytics'" x-transition class="space-y-6">
        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-indigo-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Lượt làm bài</span>
                    <span class="text-base p-1.5 bg-indigo-500/10 rounded-lg text-indigo-400">📝</span>
                </div>
                <p class="text-2xl font-black text-white group-hover:text-indigo-300 transition-colors">{{ number_format($totalGraded) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Lượt ghi nhận điểm số</p>
            </div>

            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-emerald-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Điểm Trung bình</span>
                    <span class="text-base p-1.5 bg-emerald-500/10 rounded-lg text-emerald-400">🎯</span>
                </div>
                <p class="text-2xl font-black text-emerald-400">{{ number_format($avgScore, 1) }}<span class="text-sm font-semibold text-gray-400">/100</span></p>
                <p class="text-[11px] text-gray-500 mt-1">Cao: {{ $maxScore }} | Thấp: {{ $minScore }}</p>
            </div>

            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-teal-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Tỷ lệ Đạt</span>
                    <span class="text-base p-1.5 bg-teal-500/10 rounded-lg text-teal-400">✅</span>
                </div>
                <p class="text-2xl font-black text-teal-300">{{ number_format($passRate, 1) }}%</p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $passCount }}/{{ $totalGraded }} lượt đạt điểm sàn</p>
            </div>

            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-amber-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Thời gian TB</span>
                    <span class="text-base p-1.5 bg-amber-500/10 rounded-lg text-amber-400">⏱️</span>
                </div>
                <p class="text-2xl font-black text-amber-300">{{ $avgTime > 0 ? gmdate('i:s', $avgTime) : '00:00' }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Phút : Giây làm bài</p>
            </div>

            <div class="bg-slate-900/80 border border-slate-800/80 p-4 rounded-2xl relative overflow-hidden group hover:border-purple-500/40 transition-all">
                <div class="flex items-center justify-between text-gray-400 mb-2">
                    <span class="text-xs font-medium uppercase tracking-wider">Học sinh Giỏi/XS</span>
                    <span class="text-base p-1.5 bg-purple-500/10 rounded-lg text-purple-400">🏆</span>
                </div>
                <p class="text-2xl font-black text-purple-300">{{ ($distribution['A'] ?? 0) + ($distribution['B'] ?? 0) }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Đạt loại A & B (>=80đ)</p>
            </div>
        </div>

        {{-- Score Distribution & Top Activities Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            {{-- Grade Distribution Histogram --}}
            <div class="lg:col-span-5 bg-slate-900/90 border border-slate-800/80 p-5 rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Phân bố Xếp loại Hoạt động
                    </h3>
                    <span class="text-[11px] text-gray-400">{{ $totalGraded }} bài nộp</span>
                </div>
                <div class="flex items-end gap-3 h-36 pt-4 pb-2 border-b border-slate-800/60">
                    @php
                        $colors = [
                            'A' => ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-400', 'label' => '>= 90'],
                            'B' => ['bar' => 'bg-blue-500', 'text' => 'text-blue-400', 'label' => '80-89'],
                            'C' => ['bar' => 'bg-yellow-500', 'text' => 'text-yellow-400', 'label' => '70-79'],
                            'D' => ['bar' => 'bg-orange-500', 'text' => 'text-orange-400', 'label' => '60-69'],
                            'F' => ['bar' => 'bg-red-500', 'text' => 'text-red-400', 'label' => '< 60'],
                        ];
                        $maxHist = max(1, max($distribution));
                    @endphp
                    @foreach($distribution as $letter => $count)
                        @php $pctHist = round(($count / max(1, $totalGraded)) * 100); @endphp
                        <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end group">
                            <span class="text-[11px] font-bold {{ $colors[$letter]['text'] }} opacity-0 group-hover:opacity-100 transition-opacity">{{ $count }}</span>
                            <div class="w-full bg-slate-800/60 rounded-t-lg overflow-hidden flex items-end h-full">
                                <div class="w-full {{ $colors[$letter]['bar'] }} rounded-t-lg transition-all duration-700 shadow-sm"
                                     style="height: {{ max(6, ($count / $maxHist) * 100) }}%"></div>
                            </div>
                            <div class="text-center">
                                <span class="text-xs font-black text-white block">{{ $letter }}</span>
                                <span class="text-[9px] text-gray-400 block">{{ $pctHist }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 mt-2 px-1">
                    <span>A (Xuất sắc)</span>
                    <span>B (Giỏi)</span>
                    <span>C (Khá)</span>
                    <span>D (Trung bình)</span>
                    <span>F (Yếu)</span>
                </div>
            </div>

            {{-- Top Activity Performance Overview --}}
            <div class="lg:col-span-7 bg-slate-900/90 border border-slate-800/80 p-5 rounded-2xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                            Tổng quan Hiệu suất theo Hoạt động
                        </h3>
                        <span class="text-[11px] text-gray-400">Top hoạt động được làm nhiều nhất</span>
                    </div>

                    <div class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                        @forelse($activitySummaries->take(5) as $summary)
                            @php $act = $summary->activity; @endphp
                            <div class="flex items-center justify-between p-2.5 bg-slate-800/40 hover:bg-slate-800/80 border border-slate-700/50 rounded-xl transition-all">
                                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                    <span class="text-base flex-shrink-0">{{ $activityTypes[$act?->type]['icon'] ?? '🎯' }}</span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-white truncate">{{ $act?->title ?? 'Hoạt động #'.$summary->activity_id }}</p>
                                        <p class="text-[10px] text-gray-400 truncate">{{ $act?->lesson?->course?->title ?? '' }} &bull; {{ $act?->lesson?->title ?? '' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 flex-shrink-0 text-right">
                                    <div>
                                        <span class="text-xs font-bold {{ $summary->avg_score >= 80 ? 'text-emerald-400' : ($summary->avg_score >= 60 ? 'text-yellow-400' : 'text-rose-400') }}">
                                            {{ number_format($summary->avg_score, 1) }}đ
                                        </span>
                                        <span class="block text-[9px] text-gray-500">Điểm TB</span>
                                    </div>
                                    <div class="hidden sm:block">
                                        <span class="text-xs font-semibold text-gray-300">{{ $summary->attempts_count }}</span>
                                        <span class="block text-[9px] text-gray-500">Lượt làm</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 py-4 text-center">Chưa có dữ liệu thống kê hoạt động</p>
                        @endforelse
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-gray-400 mt-2">
                    <span>💡 Thống kê tự động cập nhật theo lượt làm bài của học viên</span>
                    <button type="button" @click="currentTab = 'table'" class="text-indigo-400 hover:text-indigo-300">Xem danh sách chi tiết &rarr;</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
