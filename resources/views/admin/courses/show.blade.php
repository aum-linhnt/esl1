@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="curriculumStudioApp()">
    
    {{-- Top Navigation & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-white mb-2 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại danh sách khóa học
            </a>
            <div class="flex items-center gap-2.5">
                <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold font-mono px-2.5 py-0.5 rounded">
                    Level {{ $course->level }}
                </span>
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ $course->title }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('admin.courses.togglePublish', $course->id) }}">
                @csrf
                <button type="submit" class="text-xs font-semibold px-3 py-2 rounded-xl transition-colors {{ $course->is_published ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/25' : 'bg-slate-800 text-gray-400 border border-slate-700 hover:text-white' }}">
                    ● {{ $course->is_published ? 'Xuất bản (Published)' : 'Bản nháp (Draft)' }}
                </button>
            </form>

            <a href="{{ route('admin.courses.edit', $course->id) }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-gray-300 hover:text-white border border-slate-700 transition-colors">
                ⚙️ Cài đặt khóa
            </a>

            <a href="{{ route('courses.show', $course->id) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-xs font-semibold text-indigo-300 border border-indigo-500/30 transition-colors flex items-center gap-1">
                <span>Xem Cổng Học viên</span>
                <span>↗</span>
            </a>
        </div>
    </div>

    {{-- Modern Segmented Tabs Navigation --}}
    <div class="bg-[#0b1020]/90 p-1.5 rounded-2xl border border-slate-800/80 shadow-lg flex flex-wrap sm:flex-nowrap items-center gap-1.5 w-fit max-w-full overflow-x-auto">
        {{-- Tab 1: Giáo trình & Học liệu --}}
        <button type="button"
                @click="setTab('curriculum')"
                class="group px-4 py-2.5 rounded-xl font-medium text-xs sm:text-sm transition-all duration-200 flex items-center gap-2.5 whitespace-nowrap cursor-pointer select-none"
                :class="currentTab === 'curriculum' 
                    ? 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-blue-600 text-white shadow-lg shadow-indigo-500/25 border border-indigo-400/40 font-bold' 
                    : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent'">
            <div class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors"
                 :class="currentTab === 'curriculum' ? 'bg-white/20 text-white' : 'bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500/20'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span>Giáo trình & Học liệu</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold whitespace-nowrap transition-colors"
                  :class="currentTab === 'curriculum' ? 'bg-white/20 text-white' : 'bg-slate-800 text-indigo-300 border border-slate-700/60'">
                {{ $course->lessons->count() }} bài
            </span>
        </button>

        {{-- Tab 2: Học viên đăng ký --}}
        <button type="button"
                @click="setTab('students')"
                class="group px-4 py-2.5 rounded-xl font-medium text-xs sm:text-sm transition-all duration-200 flex items-center gap-2.5 whitespace-nowrap cursor-pointer select-none"
                :class="currentTab === 'students' 
                    ? 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-blue-600 text-white shadow-lg shadow-indigo-500/25 border border-indigo-400/40 font-bold' 
                    : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent'">
            <div class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors"
                 :class="currentTab === 'students' ? 'bg-white/20 text-white' : 'bg-teal-500/10 text-teal-400 group-hover:bg-teal-500/20'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span>Học viên tham gia</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold whitespace-nowrap transition-colors"
                  :class="currentTab === 'students' ? 'bg-white/20 text-white' : 'bg-slate-800 text-teal-300 border border-slate-700/60'">
                {{ count($enrolledStudents) }} học viên
            </span>
        </button>

        {{-- Tab 3: Ngân hàng câu hỏi --}}
        <button type="button"
                @click="setTab('question_bank')"
                class="group px-4 py-2.5 rounded-xl font-medium text-xs sm:text-sm transition-all duration-200 flex items-center gap-2.5 whitespace-nowrap cursor-pointer select-none"
                :class="currentTab === 'question_bank' 
                    ? 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-blue-600 text-white shadow-lg shadow-indigo-500/25 border border-indigo-400/40 font-bold' 
                    : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent'">
            <div class="w-6 h-6 rounded-lg flex items-center justify-center transition-colors"
                 :class="currentTab === 'question_bank' ? 'bg-white/20 text-white' : 'bg-amber-500/10 text-amber-400 group-hover:bg-amber-500/20'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span>Ngân hàng câu hỏi</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold whitespace-nowrap transition-colors"
                  :class="currentTab === 'question_bank' ? 'bg-white/20 text-white' : 'bg-slate-800 text-amber-300 border border-slate-700/60'"
                  x-text="courseQuestions.length + ' câu'">
            </span>
        </button>
    </div>

    {{-- TAB 1: CURRICULUM STUDIO (FULL LMS MOODLE-STYLE ENGINE) --}}
    <div x-show="currentTab === 'curriculum'" class="space-y-6">
        
        {{-- Toolbar & Metrics Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800">
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="font-bold text-white text-sm">Quản lý Chương trình & Hoạt động (LMS Studio)</span>
                </div>
                <div class="flex items-center gap-3 text-gray-400 font-mono text-[11px]">
                    <span>📚 <strong class="text-white">{{ $course->lessons->count() }}</strong> bài học</span>
                    <span>·</span>
                    <span>⚡ <strong class="text-indigo-300">{{ $course->lessons->sum(fn($l) => $l->activities->count()) }}</strong> hoạt động</span>
                    <span>·</span>
                    <span>⏱ <strong class="text-teal-300">{{ $course->lessons->sum('estimated_minutes') }}</strong> phút học</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        @click="expandAllLessons = !expandAllLessons"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-gray-300 hover:text-white border border-slate-700 transition-colors">
                    <span x-text="expandAllLessons ? 'Thu gọn tất cả ▲' : 'Mở rộng tất cả ▼'"></span>
                </button>

                <button type="button"
                        @click="openAddLessonModal()"
                        class="btn-primary !w-auto !py-1.5 px-4 text-xs font-semibold flex items-center gap-1.5 shadow-glow-blue">
                    <span>+</span>
                    <span>Thêm Bài học mới</span>
                </button>
            </div>
        </div>

        {{-- Drag-and-Drop Hint Banner --}}
        <div class="flex items-center justify-between px-4 py-2 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs">
            <div class="flex items-center gap-2">
                <span>💡</span>
                <span>Bạn có thể <strong>kéo thả biểu tượng ⋮⋮</strong> để sắp xếp thứ tự các Bài học hoặc di chuyển các Hoạt động học tập giữa các bài học một cách trực quan.</span>
            </div>
            <span class="text-[10px] font-mono bg-indigo-500/20 px-2 py-0.5 rounded text-indigo-200">Auto-save</span>
        </div>

        {{-- Lessons Sortable Container --}}
        <div id="lessons-sortable" class="space-y-4">
            @forelse($course->lessons as $lesson)
                <div class="admin-card overflow-hidden border-slate-800 lesson-item transition-all"
                     data-lesson-id="{{ $lesson->id }}"
                     x-data="{ lessonExpanded: true }"
                     x-effect="if (expandAllLessons !== null) lessonExpanded = expandAllLessons">
                    
                    {{-- Lesson Header Bar --}}
                    <div class="bg-slate-900/90 p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-800/80">
                        <div class="flex items-center gap-3">
                            {{-- Drag Handle for Lesson --}}
                            <div class="lesson-handle cursor-grab active:cursor-grabbing p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-slate-800 transition-colors" title="Kéo để đổi thứ tự bài học">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            </div>

                            <span class="w-8 h-8 rounded-xl bg-slate-800 border border-slate-700 text-indigo-400 font-mono font-bold text-sm flex items-center justify-center shadow-md flex-shrink-0">
                                {{ $lesson->order }}
                            </span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-bold text-white truncate">{{ $lesson->title }}</h3>
                                    
                                    {{-- Free Trial Badge --}}
                                    @if($lesson->is_free_trial)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-fsel-teal/15 text-fsel-teal border border-fsel-teal/30">Free Trial</span>
                                    @endif

                                    {{-- Visibility Status Badge --}}
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded border {{ $lesson->is_visible ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20' }}">
                                        {{ $lesson->is_visible ? '● Đang hiển thị' : '○ Đang ẩn' }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 text-[10px] text-gray-400 font-mono mt-0.5">
                                    <span>⏱ {{ $lesson->estimated_minutes }} phút</span>
                                    <span>·</span>
                                    <span>Khóa mở: ≥{{ $lesson->unlock_condition_score }}%</span>
                                    <span>·</span>
                                    <span class="text-indigo-400 font-bold">⚡ {{ $lesson->activities->count() }} hoạt động</span>
                                    @if($lesson->description)
                                        <span>·</span>
                                        <span class="text-gray-400 italic truncate max-w-xs">{{ $lesson->description }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Lesson Actions --}}
                        <div class="flex items-center gap-1.5 flex-shrink-0 self-end md:self-auto">
                            {{-- Add Activity Button --}}
                            <button type="button" 
                                    @click="openActivityPalette({{ $lesson->id }}, '{{ addslashes($lesson->title) }}')"
                                    class="btn-primary !w-auto !py-1.5 px-3 text-xs font-semibold flex items-center gap-1 shadow-glow-blue">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Thêm Hoạt động</span>
                            </button>

                            {{-- 1-Click Toggle Lesson Visibility --}}
                            <button type="button"
                                    @click="toggleLessonVisibility({{ $lesson->id }})"
                                    title="{{ $lesson->is_visible ? 'Ẩn bài học với học viên' : 'Hiển thị bài học' }}"
                                    class="p-1.5 rounded-lg border text-xs transition-colors {{ $lesson->is_visible ? 'bg-slate-800 text-gray-300 border-slate-700 hover:text-white' : 'bg-rose-500/10 text-rose-400 border-rose-500/30' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($lesson->is_visible)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    @endif
                                </svg>
                            </button>

                            {{-- 1-Click Toggle Lesson Free Trial --}}
                            <button type="button"
                                    @click="toggleLessonTrial({{ $lesson->id }})"
                                    title="{{ $lesson->is_free_trial ? 'Học thử: Đang BẬT (Bấm để chuyển về chính thức)' : 'Học thử: Đang TẮT (Bấm để cho phép học thử)' }}"
                                    class="p-1.5 rounded-lg border text-xs transition-colors {{ $lesson->is_free_trial ? 'bg-teal-500/20 text-teal-300 border-teal-500/40 hover:bg-teal-500/30' : 'bg-slate-800 text-gray-500 hover:text-teal-400 border-slate-700' }}">
                                <span class="text-[11px] font-bold">✨</span>
                            </button>

                            {{-- Edit Lesson Button --}}
                            <button type="button"
                                    @click="openEditLessonModal({{ json_encode($lesson) }})"
                                    title="Chỉnh sửa thông tin bài học"
                                    class="p-1.5 rounded-lg bg-slate-800 text-gray-400 hover:text-white border border-slate-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>

                            {{-- Delete Lesson Form --}}
                            <form method="POST" action="{{ route('admin.courses.lessons.destroy', [$course->id, $lesson->id]) }}" onsubmit="return confirm('Xóa bài học này cùng toàn bộ hoạt động bên trong?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Xóa bài học" class="p-1.5 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>

                            {{-- Collapse / Expand Button --}}
                            <button type="button" 
                                    @click="lessonExpanded = !lessonExpanded" 
                                    class="p-1.5 rounded-lg bg-slate-800 text-gray-400 hover:text-white border border-slate-700 transition-colors">
                                <svg class="w-4 h-4 transition-transform duration-200" :class="!lessonExpanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Activities Sortable Container inside Lesson --}}
                    <div x-show="lessonExpanded"
                         class="activities-sortable p-3 sm:p-4 space-y-2.5 bg-slate-950/40 min-h-[60px]"
                         data-lesson-id="{{ $lesson->id }}">
                        
                        @forelse($lesson->activities as $act)
                            @php
                                $typeInfo = $act->getTypeInfo();
                                $availStatus = $act->getAvailabilityStatus();
                            @endphp

                            <div class="activity-card p-3 rounded-xl border transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $act->is_visible ? 'bg-slate-900/80 border-slate-800/90 hover:border-slate-700' : 'bg-slate-900/40 border-dashed border-slate-800 opacity-60' }}"
                                 data-activity-id="{{ $act->id }}"
                                 data-lesson-id="{{ $lesson->id }}">
                                
                                <div class="flex items-center gap-3 min-w-0">
                                    {{-- Drag Handle for Activity --}}
                                    <div class="activity-handle cursor-grab active:cursor-grabbing text-gray-500 hover:text-gray-300 p-1" title="Kéo để đổi vị trí hoặc chuyển sang bài học khác">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                    </div>

                                    {{-- Type Icon & Badge --}}
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-bold flex-shrink-0 bg-{{ $typeInfo['color'] }}-500/10 text-{{ $typeInfo['color'] }}-300 border-{{ $typeInfo['color'] }}-500/20">
                                        <span>{{ $typeInfo['icon'] }}</span>
                                        <span class="hidden md:inline">{{ $typeInfo['label'] }}</span>
                                    </span>

                                    {{-- Title & Info --}}
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-semibold text-xs text-white truncate">{{ $act->title }}</span>
                                            
                                            {{-- Visual Indicator: Hidden --}}
                                            @if(!$act->is_visible)
                                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-500/15 text-rose-400 border border-rose-500/30">
                                                    🚫 Đang ẩn
                                                </span>
                                            @endif

                                            {{-- Visual Indicator: Free Trial --}}
                                            @if($act->is_free_trial)
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-teal-500/15 text-teal-300 border border-teal-500/30 flex items-center gap-1">
                                                    ✨ Học thử
                                                </span>
                                            @endif

                                            {{-- Visual Indicator: File info --}}
                                            @if($act->hasFile())
                                                <span class="text-[9px] font-mono px-2 py-0.5 rounded bg-orange-500/10 text-orange-300 border border-orange-500/20 flex items-center gap-1">
                                                    📎 {{ $act->getFileOriginalName() ?: 'Tệp tin' }}
                                                    ({{ $act->getFileSizeFormatted() }})
                                                </span>
                                            @endif

                                            {{-- Visual Indicator: Timing / Availability --}}
                                            @if($availStatus === 'not_yet')
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-amber-500/10 text-amber-300 border border-amber-500/20" title="Mở lúc: {{ $act->available_from?->format('d/m/Y H:i') }}">
                                                    ⏳ Mở: {{ $act->available_from?->format('d/m') }}
                                                </span>
                                            @elseif($availStatus === 'expired')
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-red-500/15 text-red-400 border border-red-500/30" title="Hết hạn: {{ $act->available_until?->format('d/m/Y H:i') }}">
                                                    🛑 Đã hết hạn
                                                </span>
                                            @elseif($act->available_until)
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/20" title="Hạn chót: {{ $act->available_until?->format('d/m/Y H:i') }}">
                                                    ⏰ Hạn: {{ $act->available_until?->format('d/m') }}
                                                </span>
                                            @endif

                                            {{-- Visual Indicator: Completion Rule --}}
                                            @if($act->completion_type === 'auto_view')
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-sky-500/10 text-sky-300 border border-sky-500/20" title="Tự động hoàn thành khi xem xong">
                                                    👁️ Xem xong
                                                </span>
                                            @elseif($act->completion_type === 'auto_grade')
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/20" title="Đạt điểm tối thiểu: {{ $act->passing_grade }}%">
                                                    🎯 Điểm ≥ {{ $act->passing_grade ?? 50 }}%
                                                </span>
                                            @elseif($act->completion_type === 'auto_submit')
                                                <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20" title="Nộp bài để hoàn thành">
                                                    📤 Nộp bài
                                                </span>
                                            @endif
                                        </div>

                                        @if($act->description)
                                            <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $act->description }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Activity Fast Actions --}}
                                <div class="flex items-center gap-1.5 self-end sm:self-auto flex-shrink-0">
                                    <span class="text-[10px] text-gray-500 font-mono mr-1">~{{ $act->estimated_minutes }}m</span>

                                    {{-- 1-Click Toggle Activity Visibility --}}
                                    <button type="button"
                                            @click="toggleActivityVisibility({{ $act->id }})"
                                            title="{{ $act->is_visible ? 'Ẩn với học viên' : 'Hiển thị với học viên' }}"
                                            class="p-1.5 rounded-lg border text-xs transition-colors {{ $act->is_visible ? 'bg-slate-800 text-gray-400 hover:text-white border-slate-700' : 'bg-rose-500/15 text-rose-300 border-rose-500/30' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($act->is_visible)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                            @endif
                                        </svg>
                                    </button>

                                    {{-- 1-Click Toggle Activity Free Trial --}}
                                    <button type="button"
                                            @click="toggleActivityTrial({{ $act->id }})"
                                            title="{{ $act->is_free_trial ? 'Học thử: Đang BẬT (Bấm để chuyển về chính thức)' : 'Học thử: Đang TẮT (Bấm để cho phép học thử)' }}"
                                            class="p-1.5 rounded-lg border text-xs transition-colors {{ $act->is_free_trial ? 'bg-teal-500/20 text-teal-300 border-teal-500/40 hover:bg-teal-500/30' : 'bg-slate-800 text-gray-500 hover:text-teal-400 border-slate-700' }}">
                                        <span class="text-[10px] font-bold">✨</span>
                                    </button>

                                    {{-- Open Settings Drawer --}}
                                    <button type="button"
                                            @click="openActivitySettings({{ json_encode($act) }})"
                                            title="Cấu hình chi tiết (Thời gian, Hoàn thành, Nội dung)"
                                            class="p-1.5 rounded-lg bg-slate-800 text-gray-400 hover:text-indigo-400 hover:bg-slate-700 border border-slate-700 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>

                                    {{-- 1-Click Duplicate Activity --}}
                                    <button type="button"
                                            @click="duplicateActivity({{ $act->id }})"
                                            title="Nhân bản hoạt động này"
                                            class="p-1.5 rounded-lg bg-slate-800 text-gray-400 hover:text-emerald-400 hover:bg-slate-700 border border-slate-700 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>

                                    {{-- Preview Activity --}}
                                    <a href="{{ route('activities.show', $act->id) }}" target="_blank" title="Xem trước giao diện học viên" class="p-1.5 rounded-lg bg-slate-800 text-gray-400 hover:text-fsel-teal hover:bg-slate-700 border border-slate-700 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>

                                    {{-- Delete Activity --}}
                                    <form method="POST" action="{{ route('admin.activities.destroy', $act->id) }}" onsubmit="return confirm('Xóa hoạt động này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Xóa học liệu" class="p-1.5 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/15 border border-transparent hover:border-red-500/30 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="empty-dropzone py-6 text-center border-2 border-dashed border-slate-800 rounded-xl text-gray-500 text-xs">
                                <span>Kéo thả hoạt động vào đây hoặc bấm </span>
                                <button type="button" @click="openActivityPalette({{ $lesson->id }}, '{{ addslashes($lesson->title) }}')" class="text-indigo-400 font-bold hover:underline">
                                    + Thêm Hoạt động
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="admin-card p-12 text-center text-gray-500">
                    Khóa học này chưa có bài học nào.
                </div>
            @endforelse
        </div>
    </div>

    {{-- TAB 2: ENROLLED LEARNERS & COURSE ROLES (MOODLE STYLE) --}}
    <div x-show="currentTab === 'students'" x-cloak class="space-y-5">

        {{-- Role System vs Course Role Info Banner --}}
        <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-950/40 via-purple-950/30 to-slate-900 border border-indigo-500/30 text-xs text-gray-300 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="text-base">🛡️</span>
                    <span class="font-bold text-white text-sm">Phân tách Quyền Hệ thống (System Role) & Quyền Khóa học (Course Role)</span>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/40">Moodle Standard</span>
                </div>
                <p class="text-gray-400 text-[11px] leading-relaxed">
                    Một người dùng có thể là <strong class="text-white">Học viên</strong> ở cấp hệ thống, nhưng khi được ghi danh vào khóa này có thể giữ vai trò <strong class="text-purple-300">Giáo viên phụ trách</strong>, <strong class="text-amber-300">Trợ giảng</strong> hoặc <strong class="text-indigo-300">Học viên</strong>.
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-[11px] font-mono bg-slate-900/90 border border-slate-700 px-3 py-1.5 rounded-xl text-fsel-teal font-bold">
                    👥 {{ count($enrolledStudents) }} Thành viên ghi danh
                </span>
            </div>
        </div>

        {{-- Manual Enrollment Form with Course Context Role & Expiration Selector --}}
        <div class="admin-card p-5" x-data="{ openEnroll: false, selectedRole: 'student', durationPreset: 'unlimited' }">
            <div class="flex items-center justify-between cursor-pointer" @click="openEnroll = !openEnroll">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center font-bold text-sm">+</span>
                    <div>
                        <h3 class="text-sm font-bold text-white">Ghi danh & Gán vai trò, Thời hạn khóa học (Enrolment Duration & Roles)</h3>
                        <p class="text-[11px] text-gray-400">Chọn người dùng, cấp vai trò riêng biệt và thiết lập thời hạn truy cập khóa học</p>
                    </div>
                </div>
                <button type="button" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 text-emerald-400 hover:bg-slate-700 transition-colors" x-text="openEnroll ? 'Thu gọn ▲' : 'Ghi danh thành viên mới ▼'"></button>
            </div>

            <form x-show="openEnroll" x-cloak method="POST" action="{{ route('admin.courses.manualEnroll', $course->id) }}" class="pt-5 mt-4 border-t border-slate-800 space-y-4">
                @csrf
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    {{-- Left Column: User & Expiration --}}
                    <div class="space-y-4">
                        {{-- 1. Select User --}}
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">1. Chọn tài khoản người dùng:</label>
                            <div class="relative" x-data="{
                                openUser: false,
                                selectedUser: '',
                                userOptions: {
                                    '': '-- Chọn thành viên từ danh sách hệ thống --',
                                    @foreach($unenrolledUsers as $u)
                                        '{{ $u->id }}': '{{ addslashes($u->name) }} ({{ $u->email }}) — [System: {{ ucfirst($u->role) }}]',
                                    @endforeach
                                }
                            }" @click.outside="openUser = false">
                                <input type="hidden" name="user_id" :value="selectedUser" required>
                                <button type="button" @click="openUser = !openUser" class="login-input !py-2 text-xs w-full bg-slate-900 border-slate-700 flex items-center justify-between cursor-pointer text-left">
                                    <span x-text="userOptions[selectedUser] || selectedUser" class="text-white truncate"></span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="openUser ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="openUser" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                                    <template x-for="(lbl, val) in userOptions" :key="val">
                                        <div @click="selectedUser = val; openUser = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selectedUser == val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                            <span x-text="lbl"></span>
                                            <span x-show="selectedUser == val" class="text-emerald-400 font-bold">✓</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1">Chỉ hiển thị các tài khoản chưa ghi danh vào khóa này.</p>
                        </div>

                        {{-- 3. Expiration / Duration Presets --}}
                        <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2.5">
                            <label class="block text-[11px] uppercase font-bold text-emerald-400">⏰ Thời hạn kết thúc ghi danh (Enrolment Duration):</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-1.5 text-xs">
                                <label class="p-2 rounded-lg border text-center cursor-pointer font-medium transition-all"
                                       :class="durationPreset === 'unlimited' ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/30' : 'bg-slate-900 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="duration_preset" value="unlimited" x-model="durationPreset" class="hidden">
                                    <span>♾️ Vô thời hạn</span>
                                </label>
                                <label class="p-2 rounded-lg border text-center cursor-pointer font-medium transition-all"
                                       :class="durationPreset === '30_days' ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/30' : 'bg-slate-900 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="duration_preset" value="30_days" x-model="durationPreset" class="hidden">
                                    <span>30 ngày</span>
                                </label>
                                <label class="p-2 rounded-lg border text-center cursor-pointer font-medium transition-all"
                                       :class="durationPreset === '90_days' ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/30' : 'bg-slate-900 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="duration_preset" value="90_days" x-model="durationPreset" class="hidden">
                                    <span>90 ngày</span>
                                </label>
                                <label class="p-2 rounded-lg border text-center cursor-pointer font-medium transition-all"
                                       :class="durationPreset === '180_days' ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/30' : 'bg-slate-900 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="duration_preset" value="180_days" x-model="durationPreset" class="hidden">
                                    <span>6 tháng</span>
                                </label>
                                <label class="p-2 rounded-lg border text-center cursor-pointer font-medium transition-all"
                                       :class="durationPreset === '365_days' ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/30' : 'bg-slate-900 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="duration_preset" value="365_days" x-model="durationPreset" class="hidden">
                                    <span>1 năm</span>
                                </label>
                                <label class="p-2 rounded-lg border text-center cursor-pointer font-medium transition-all col-span-2 sm:col-span-3"
                                       :class="durationPreset === 'custom_date' ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/30' : 'bg-slate-900 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="duration_preset" value="custom_date" x-model="durationPreset" class="hidden">
                                    <span>📅 Chọn ngày kết thúc cụ thể</span>
                                </label>
                            </div>

                            {{-- Custom Date Picker --}}
                            <div x-show="durationPreset === 'custom_date'" x-cloak class="pt-2">
                                <label class="block text-[10px] text-gray-400 mb-1">Ngày hết hạn truy cập:</label>
                                <input type="date" name="custom_expires_at" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="login-input !py-1.5 text-xs w-full bg-slate-900 border-slate-700 font-mono">
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Course Role Selector --}}
                    <div>
                        <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">2. Vai trò được gán trong khóa học này (Course Role):</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($courseRoles as $rKey => $rMeta)
                                <label class="p-2.5 rounded-xl border text-xs cursor-pointer transition-all flex items-start gap-2.5"
                                       :class="selectedRole === '{{ $rKey }}' ? 'bg-indigo-600/20 border-indigo-500 text-white ring-1 ring-indigo-500/40' : 'bg-slate-900/80 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="course_role" value="{{ $rKey }}" x-model="selectedRole" class="mt-0.5 text-indigo-600 bg-slate-800 border-slate-700">
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-[11px] block text-white">{{ $rMeta['icon'] }} {{ $rMeta['name'] }}</span>
                                        <span class="text-[10px] text-gray-400 block leading-tight">{{ $rMeta['desc'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-800/80">
                    <span class="text-[11px] text-gray-400 font-mono">
                        Quyền hạn và thời hạn truy cập có hiệu lực ngay lập tức sau khi xác nhận.
                    </span>

                    <button type="submit" class="btn-primary !w-auto !py-2.5 px-6 text-xs font-bold shadow-glow-blue flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <span>Xác nhận Ghi danh & Gán quyền</span>
                    </button>
                </div>

                @if($unenrolledUsers->isEmpty())
                    <p class="text-xs text-amber-400 mt-2 italic">Tất cả người dùng trong hệ thống đều đã được ghi danh vào khóa học này.</p>
                @endif
            </form>
        </div>

        {{-- Enrolled Students & Instructors Table --}}
        <div class="admin-card overflow-hidden">
            <div class="p-5 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight">Danh sách Thành viên theo học & Giảng dạy</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Quản lý vai trò khóa học, thời hạn truy cập và tiến độ hoàn thành</p>
                </div>
                <span class="text-xs font-mono font-bold px-3 py-1 rounded-full bg-slate-900 border border-slate-700 text-fsel-teal w-fit">
                    Tổng cộng: {{ count($enrolledStudents) }} thành viên
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-300">
                    <thead class="bg-slate-900/90 text-[11px] uppercase font-bold text-gray-400 border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3.5 whitespace-nowrap">Thành viên</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Vai trò Khóa học</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center">Trạng thái</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Hạn kết thúc</th>
                            <th class="px-5 py-3.5 whitespace-nowrap text-right" style="min-width: 170px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-sans">
                        @forelse($enrolledStudents as $item)
                            @php
                                $roleMeta = $item['enrollment']->role_meta;
                                $status = $item['enrollment']->status;
                                $expiryInfo = $item['enrollment']->expiry_status;
                            @endphp
                            <tr class="hover:bg-slate-800/30 transition-colors" x-data="{ openExpiryModal: false }">
                                {{-- User Info with Avatar --}}
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500/30 to-purple-500/30 border border-indigo-500/30 text-indigo-300 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            {{ mb_strtoupper(mb_substr($item['user']->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-white text-xs block leading-tight">{{ $item['user']->name }}</span>
                                            <span class="text-[10px] text-gray-400 font-mono block mt-0.5">{{ $item['user']->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Course Context Role with Native Styled Dropdown (Never clipped by table overflow) --}}
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.courses.updateEnrollmentRole', [$course->id, $item['user']->id]) }}">
                                        @csrf
                                        <select name="course_role" onchange="this.form.submit()" 
                                                class="text-xs font-semibold pl-3 pr-8 py-1.5 rounded-lg border bg-slate-900 text-white cursor-pointer focus:ring-1 focus:ring-indigo-500 border-slate-700 hover:border-slate-600 transition-colors outline-none">
                                            @foreach($courseRoles as $rKey => $rOpt)
                                                <option value="{{ $rKey }}" class="bg-slate-900 text-white py-1.5 font-sans" {{ $item['enrollment']->course_role === $rKey ? 'selected' : '' }}>
                                                    {{ $rOpt['icon'] }} {{ $rOpt['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>

                                {{-- Status Badges --}}
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    @if($item['enrollment']->isSuspended())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30 text-[10px] font-semibold whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            Tạm khóa
                                        </span>
                                    @elseif($item['enrollment']->isExpired())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-500/15 text-red-400 border border-red-500/30 text-[10px] font-semibold whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                            Hết hạn
                                        </span>
                                    @elseif($status === 'completed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-semibold whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            Hoàn thành
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-500/15 text-blue-400 border border-blue-500/30 text-[10px] font-semibold whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                            Đang học
                                        </span>
                                    @endif
                                </td>

                                {{-- Expiration Date & Extension Button --}}
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 whitespace-nowrap" style="white-space: nowrap;">
                                        <span class="text-[11px] font-mono px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700 text-gray-300 inline-block">
                                            {{ $expiryInfo['icon'] }} {{ $expiryInfo['short_text'] ?? $expiryInfo['text'] }}
                                        </span>

                                        {{-- Edit / Extend Expiry Trigger --}}
                                        <button type="button" @click="openExpiryModal = true" title="Gia hạn hoặc đổi ngày hết hạn" 
                                                class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-gray-400 hover:text-white border border-slate-700 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                    </div>

                                    {{-- Inline Expiry Modal --}}
                                    <div x-show="openExpiryModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
                                        <div class="admin-card max-w-md w-full p-6 space-y-4 border-indigo-500/50 shadow-2xl" @click.away="openExpiryModal = false">
                                            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                                <div>
                                                    <h3 class="text-sm font-bold text-white">Chỉnh sửa Hạn kết thúc ghi danh</h3>
                                                    <p class="text-xs text-gray-400">Học viên: <strong class="text-indigo-300">{{ $item['user']->name }}</strong></p>
                                                </div>
                                                <button type="button" @click="openExpiryModal = false" class="text-gray-400 hover:text-white text-lg">&times;</button>
                                            </div>

                                            <form method="POST" action="{{ route('admin.courses.updateEnrollmentExpiry', [$course->id, $item['user']->id]) }}" class="space-y-3">
                                                @csrf
                                                <div>
                                                    <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1.5">Chọn thời hạn mới:</label>
                                                    <div class="relative" x-data="{
                                                        open: false,
                                                        extPreset: '{{ $item['enrollment']->isUnlimited() ? 'unlimited' : 'custom_date' }}',
                                                        options: {
                                                            'unlimited': '♾️ Vô thời hạn (Unlimited)',
                                                            '30_days': '+ 30 ngày kể từ hôm nay',
                                                            '60_days': '+ 60 ngày kể từ hôm nay',
                                                            '90_days': '+ 90 ngày (3 tháng)',
                                                            '180_days': '+ 180 ngày (6 tháng)',
                                                            '365_days': '+ 365 ngày (1 năm)',
                                                            'custom_date': '📅 Chọn ngày cụ thể...'
                                                        }
                                                    }" @click.outside="open = false">
                                                        <input type="hidden" name="duration_preset" :value="extPreset">
                                                        <button type="button" @click="open = !open" class="login-input !py-2 text-xs w-full flex items-center justify-between cursor-pointer text-left">
                                                            <span x-text="options[extPreset] || extPreset" class="text-white truncate"></span>
                                                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                        </button>
                                                        <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                                            <template x-for="(lbl, val) in options" :key="val">
                                                                <div @click="extPreset = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="extPreset === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                                                    <span x-text="lbl"></span>
                                                                    <span x-show="extPreset === val" class="text-emerald-400 font-bold">✓</span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Hoặc nhập ngày kết thúc:</label>
                                                    <input type="date" name="custom_expires_at" value="{{ $item['enrollment']->expires_at ? $item['enrollment']->expires_at->format('Y-m-d') : '' }}" class="login-input !py-1.5 text-xs w-full font-mono bg-slate-900">
                                                </div>

                                                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                                                    <button type="button" @click="openExpiryModal = false" class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs text-gray-300">
                                                        Hủy
                                                    </button>
                                                    <button type="submit" class="btn-primary !w-auto !py-1.5 px-4 text-xs font-bold shadow-glow-blue bg-gradient-to-r from-emerald-600 to-teal-500">
                                                        Lưu hạn mới
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5 whitespace-nowrap" style="white-space: nowrap;">
                                        @if($item['percentage'] >= 100 && $item['enrollment']->isCourseStudent())
                                            <a href="{{ route('certificates.show', $course->id) }}" target="_blank" 
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-semibold transition-all" 
                                               title="Xem chứng chỉ">
                                                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                                <span>Chứng chỉ</span>
                                            </a>
                                        @endif

                                        {{-- 1-Click Suspend / Activate Button --}}
                                        <form method="POST" action="{{ route('admin.courses.toggleSuspendEnrollment', [$course->id, $item['user']->id]) }}">
                                            @csrf
                                            @if($item['enrollment']->isSuspended())
                                                <button type="submit" title="Kích hoạt lại quyền truy cập" 
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-[11px] font-semibold transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span>Kích hoạt</span>
                                                </button>
                                            @else
                                                <button type="submit" title="Tạm khóa quyền truy cập" 
                                                        onclick="return confirm('Tạm khóa ghi danh của {{ $item['user']->name }} trong khóa này?')"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-semibold transition-all">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span>Tạm khóa</span>
                                                </button>
                                            @endif
                                        </form>

                                        {{-- Unenroll Button --}}
                                        <form method="POST" action="{{ route('admin.courses.manualUnenroll', [$course->id, $item['user']->id]) }}" onsubmit="return confirm('Hủy ghi danh thành viên {{ $item['user']->name }} khỏi khóa học?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hủy ghi danh hoàn toàn" class="p-1.5 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/15 border border-transparent hover:border-red-500/30 transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                    Chưa có thành viên nào được ghi danh vào khóa học này. Bấm <strong class="text-emerald-400">+ Ghi danh & Gán vai trò</strong> ở trên để thêm.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3: COURSE QUESTION BANK (NGÂN HÀNG CÂU HỎI KHÓA HỌC)            --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="currentTab === 'question_bank'" class="space-y-6">
        
        {{-- Toolbar & Stats --}}
        <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-amber-950/20 to-slate-900 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <h3 class="text-base font-bold text-white tracking-tight">Ngân hàng câu hỏi riêng của khóa học</h3>
                    <span class="text-xs font-mono bg-amber-500/10 text-amber-400 px-2.5 py-0.5 rounded-full border border-amber-500/20 font-bold" x-text="courseQuestions.length + ' câu hỏi'"></span>
                </div>
                <p class="text-xs text-gray-400">Các câu hỏi trắc nghiệm được lưu trữ riêng cho khóa này để giáo viên dễ dàng tái sử dụng hoặc bốc ngẫu nhiên vào bài Quiz.</p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" @click="openImportModal()"
                        class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-cyan-300 border border-slate-700 hover:border-cyan-500/40 transition-all flex items-center gap-1.5 shadow-sm">
                    <span>📥</span>
                    <span>Lấy từ Ngân hàng chung</span>
                </button>

                <button type="button" @click="openAddQuestionModal()"
                        class="btn-primary !w-auto !py-2 px-4 text-xs font-semibold flex items-center gap-1.5 shadow-glow-blue bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400">
                    <span>+</span>
                    <span>Thêm Câu hỏi mới</span>
                </button>
            </div>
        </div>

        {{-- Filters Bar --}}
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3 relative z-30">
            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Tìm kiếm nội dung câu hỏi:</label>
                <input type="text" x-model="questionSearch" placeholder="Nhập từ khóa tìm kiếm..." class="login-input !py-1.5 text-xs">
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Kỹ năng (Skill):</label>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    options: {
                        'all': 'Tất cả kỹ năng',
                        'vocabulary': '📖 Từ vựng (Vocabulary)',
                        'grammar': '📐 Ngữ pháp (Grammar)',
                        'reading': '📰 Đọc hiểu (Reading)',
                        'listening': '🎧 Nghe hiểu (Listening)'
                    }
                }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                        <span x-text="options[questionSkillFilter] || questionSkillFilter" class="text-white truncate"></span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="questionSkillFilter = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="questionSkillFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="questionSkillFilter === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Độ khó CEFR:</label>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    options: {
                        'all': 'Tất cả trình độ',
                        'A1': 'Level A1 (Sơ cấp)',
                        'A2': 'Level A2 (Tiền trung cấp)',
                        'B1': 'Level B1 (Trung cấp)',
                        'B2': 'Level B2 (Trung cao cấp)'
                    }
                }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                        <span x-text="options[questionDifficultyFilter] || questionDifficultyFilter" class="text-white truncate"></span>
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="questionDifficultyFilter = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="questionDifficultyFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="questionDifficultyFilter === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Questions List --}}
        <div class="admin-card overflow-hidden border-slate-800 relative z-10">
            <template x-if="filteredCourseQuestions.length === 0">
                <div class="p-12 text-center space-y-3">
                    <span class="text-4xl block">❓</span>
                    <p class="text-sm font-semibold text-gray-300">Chưa có câu hỏi nào trong ngân hàng của khóa học này.</p>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">Bạn có thể tạo câu hỏi mới hoặc sao chép nhanh câu hỏi từ Ngân hàng chung của hệ thống.</p>
                    <div class="pt-2 flex items-center justify-center gap-3">
                        <button type="button" @click="openAddQuestionModal()" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition-colors">
                            + Thêm câu hỏi đầu tiên
                        </button>
                        <button type="button" @click="openImportModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-gray-300 text-xs font-semibold border border-slate-700 transition-colors">
                            📥 Lấy từ Ngân hàng chung
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="filteredCourseQuestions.length > 0">
                <div class="divide-y divide-slate-800/80">
                    <template x-for="(q, qidx) in filteredCourseQuestions" :key="q.id">
                        <div class="p-4 hover:bg-slate-800/30 transition-colors space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <span class="font-mono text-xs text-gray-500 font-bold mt-0.5" x-text="'#' + (qidx + 1)"></span>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            {{-- Skill Badge --}}
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                                  :class="{
                                                      'bg-purple-500/20 text-purple-300 border border-purple-500/30': q.skill === 'vocabulary' || q.skill === 'vocab',
                                                      'bg-blue-500/20 text-blue-300 border border-blue-500/30': q.skill === 'grammar',
                                                      'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': q.skill === 'reading',
                                                      'bg-teal-500/20 text-teal-300 border border-teal-500/30': q.skill === 'listening',
                                                  }"
                                                  x-text="q.skill"></span>

                                            {{-- Difficulty Badge --}}
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-800 text-amber-300 border border-slate-700"
                                                  x-text="q.difficulty"></span>

                                            {{-- Type Badge --}}
                                            <span class="text-[10px] text-gray-500 font-mono uppercase" x-text="q.question_type || 'mcq'"></span>
                                        </div>

                                        {{-- Question Text --}}
                                        <h4 class="text-sm font-semibold text-white leading-relaxed" x-text="q.question_text"></h4>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <button type="button" @click="openEditQuestionModal(q)"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-slate-800 text-xs transition-colors" title="Chỉnh sửa câu hỏi">
                                        ✏️ Sửa
                                    </button>
                                    <button type="button" @click="deleteQuestion(q.id)"
                                            class="p-1.5 rounded-lg text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 text-xs transition-colors" title="Xóa khỏi ngân hàng">
                                        🗑️
                                    </button>
                                </div>
                            </div>

                            {{-- Options / Answer Preview by Question Type --}}
                            <div class="pl-7">
                                {{-- 1. MCQ & Audio Listening --}}
                                <template x-if="q.question_type === 'mcq' || q.question_type === 'audio_listening' || !q.question_type">
                                    <div class="space-y-2">
                                        <template x-if="q.audio_url">
                                            <div class="text-[11px] text-amber-300 font-mono flex items-center gap-1.5 mb-1">
                                                <span>🎧 Audio:</span>
                                                <span class="truncate" x-text="q.audio_url"></span>
                                            </div>
                                        </template>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <template x-for="(opt, optIdx) in (Array.isArray(q.options) ? q.options : [])" :key="optIdx">
                                                <div class="px-3 py-1.5 rounded-lg text-xs flex items-center justify-between"
                                                     :class="String(opt).trim() === String(q.correct_answer).trim() || optIdx === parseInt(q.correct_answer)
                                                         ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 font-semibold'
                                                         : 'bg-slate-900 border border-slate-800 text-gray-400'">
                                                    <span class="truncate" x-text="String.fromCharCode(65 + optIdx) + '. ' + opt"></span>
                                                    <template x-if="String(opt).trim() === String(q.correct_answer).trim() || optIdx === parseInt(q.correct_answer)">
                                                        <span class="text-[10px] text-emerald-400 ml-1">✓</span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- 2. Multiple Select --}}
                                <template x-if="q.question_type === 'multiple_select'">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                        <template x-for="(opt, optIdx) in (Array.isArray(q.options) ? q.options : [])" :key="optIdx">
                                            <div class="px-3 py-1.5 rounded-lg text-xs flex items-center justify-between"
                                                 :class="(q.correct_answer && q.correct_answer.includes(opt))
                                                     ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 font-semibold'
                                                     : 'bg-slate-900 border border-slate-800 text-gray-400'">
                                                <span class="truncate" x-text="opt"></span>
                                                <template x-if="q.correct_answer && q.correct_answer.includes(opt)">
                                                    <span class="text-[10px] text-emerald-400 ml-1">✓</span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- 3. Fill in Blank --}}
                                <template x-if="q.question_type === 'fill_blank'">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-mono">
                                        <span>✍️ Từ cần điền:</span>
                                        <strong class="text-white" x-text="q.correct_answer"></strong>
                                    </div>
                                </template>

                                {{-- 4. Word Ordering --}}
                                <template x-if="q.question_type === 'word_ordering' || q.question_type === 'drag_drop'">
                                    <div class="space-y-1.5">
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs">
                                            <span class="text-gray-400">Câu hoàn chỉnh:</span>
                                            <strong class="text-emerald-300" x-text="q.correct_answer"></strong>
                                        </div>
                                        <template x-if="Array.isArray(q.options) && q.options.length > 0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-[10px] text-gray-500">Các từ:</span>
                                                <template x-for="(w, widx) in q.options" :key="widx">
                                                    <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-gray-300 text-xs font-mono" x-text="w"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- 5. Matching Pairs --}}
                                <template x-if="q.question_type === 'matching'">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs">
                                        <span>🔗 Cặp nối từ - nghĩa</span>
                                        <span class="text-gray-400 font-mono text-[10px]">(Đã lưu các cặp đối ứng)</span>
                                    </div>
                                </template>

                                {{-- 6. True / False --}}
                                <template x-if="q.question_type === 'true_false'">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs">
                                        <span class="text-gray-400">Đáp án:</span>
                                        <span class="font-bold text-amber-400" x-text="q.correct_answer || 'True'"></span>
                                    </div>
                                </template>

                                {{-- 8. Pronunciation Speech AI --}}
                                <template x-if="q.question_type === 'pronunciation_speech'">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-teal-500/10 border border-teal-500/30 text-teal-300 text-xs font-mono">
                                        <span>🎙️ Câu mẫu luyện phát âm:</span>
                                        <strong class="text-white" x-text="'\"' + q.correct_answer + '\"'"></strong>
                                    </div>
                                </template>
                            </div>

                            {{-- Explanation (if any) --}}
                            <template x-if="q.explanation">
                                <p class="text-xs text-gray-400 bg-slate-900/60 p-2 rounded-lg border border-slate-800/60 pl-7 italic">
                                    💡 <span class="font-semibold text-gray-300">Giải thích:</span> <span x-text="q.explanation"></span>
                                </p>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL 1: ADD LESSON MODAL                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="showAddLessonModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
        <div class="admin-card max-w-lg w-full p-6 space-y-4 border-indigo-500/40 shadow-2xl" @click.away="showAddLessonModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm">+</span>
                    <h3 class="text-sm font-bold text-white">Thêm Bài học (Section) mới</h3>
                </div>
                <button type="button" @click="showAddLessonModal = false" class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.courses.lessons.store', $course->id) }}" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Tiêu đề bài học:</label>
                    <input type="text" name="title" required placeholder="VD: Bài 5: Daily Routines & Activities" class="login-input !py-2 text-xs">
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Mô tả tóm tắt:</label>
                    <textarea name="description" rows="2" placeholder="Giới thiệu nội dung trọng tâm bài học..." class="login-input !py-1.5 text-xs"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Thứ tự bài:</label>
                        <input type="number" name="order" value="{{ $course->lessons->count() + 1 }}" min="1" class="login-input !py-1.5 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Thời lượng ước tính (phút):</label>
                        <input type="number" name="estimated_minutes" value="30" min="1" class="login-input !py-1.5 text-xs font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Điểm bài trước tối thiểu để mở khóa (%):</label>
                    <input type="number" name="unlock_condition_score" value="60" min="0" max="100" class="login-input !py-1.5 text-xs font-mono">
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-white">
                        <input type="checkbox" name="is_free_trial" value="1" checked class="rounded bg-slate-800 border-slate-700 text-indigo-600">
                        <span>Cho phép học thử miễn phí (Free Trial)</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="showAddLessonModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white">
                        Hủy
                    </button>
                    <button type="submit" class="btn-primary !w-auto !py-2 px-5 text-xs font-semibold shadow-glow-blue">
                        Tạo Bài học
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL 2: EDIT LESSON MODAL                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="showEditLessonModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
        <div class="admin-card max-w-lg w-full p-6 space-y-4 border-slate-700 shadow-2xl" @click.away="showEditLessonModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm">✏️</span>
                    <h3 class="text-sm font-bold text-white">Chỉnh sửa Bài học</h3>
                </div>
                <button type="button" @click="showEditLessonModal = false" class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>

            <form :action="'/admin/courses/{{ $course->id }}/lessons/' + editLessonData.id" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Tiêu đề bài học:</label>
                    <input type="text" name="title" x-model="editLessonData.title" required class="login-input !py-2 text-xs">
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Mô tả tóm tắt:</label>
                    <textarea name="description" x-model="editLessonData.description" rows="2" class="login-input !py-1.5 text-xs"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Thứ tự bài:</label>
                        <input type="number" name="order" x-model="editLessonData.order" min="1" class="login-input !py-1.5 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Thời lượng ước tính (phút):</label>
                        <input type="number" name="estimated_minutes" x-model="editLessonData.estimated_minutes" min="1" class="login-input !py-1.5 text-xs font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Điểm bài trước tối thiểu để mở (%):</label>
                    <input type="number" name="unlock_condition_score" x-model="editLessonData.unlock_condition_score" min="0" max="100" class="login-input !py-1.5 text-xs font-mono">
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-white">
                        <input type="checkbox" name="is_free_trial" value="1" :checked="editLessonData.is_free_trial" class="rounded bg-slate-800 border-slate-700 text-indigo-600">
                        <span>Cho phép học thử miễn phí (Free Trial)</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="showEditLessonModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white">
                        Hủy
                    </button>
                    <button type="submit" class="btn-primary !w-auto !py-2 px-5 text-xs font-semibold shadow-glow-blue">
                        Lưu Cập Nhật
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL 3: ACTIVITY TYPE PALETTE (MOODLE-STYLE ACTIVITY PICKER)       --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="showPaletteModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 overflow-y-auto">
        <div class="admin-card max-w-3xl w-full p-6 space-y-5 my-8 max-h-[90vh] overflow-y-auto border-indigo-500/40 shadow-2xl" @click.away="showPaletteModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🧩 Thêm Hoạt động hoặc Tài nguyên (Add an Activity or Resource)</span>
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Thêm vào bài học: <strong class="text-indigo-400" x-text="targetLessonTitle"></strong>
                    </p>
                </div>
                <button type="button" @click="showPaletteModal = false" class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>

            {{-- Activity Categories Grid --}}
            <div class="space-y-5">
                {{-- Group 1: Học liệu nội dung (Content) --}}
                <div>
                    <h4 class="text-[11px] uppercase font-bold text-purple-400 tracking-wider mb-2.5 flex items-center gap-1.5">
                        <span>📚</span> <span>Học liệu & Bài giảng Nội dung</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <button type="button" @click="selectTypeAndOpenDrawer('vocabulary')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-purple-500/50 hover:bg-purple-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-purple-500/10 border border-purple-500/20 flex-shrink-0">📖</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-purple-300">Từ vựng Flashcard</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Thẻ từ vựng có phiên âm, nghĩa & ví dụ</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('grammar')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-blue-500/50 hover:bg-blue-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-blue-500/10 border border-blue-500/20 flex-shrink-0">📐</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-blue-300">Bài giảng Ngữ pháp</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Cấu trúc, quy tắc ngữ pháp & câu ví dụ</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('video')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-red-500/50 hover:bg-red-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-red-500/10 border border-red-500/20 flex-shrink-0">🎬</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-red-300">Video Bài giảng</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Video nhúng YouTube hoặc đường dẫn URL</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('audio_listening')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-teal-500/50 hover:bg-teal-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-teal-500/10 border border-teal-500/20 flex-shrink-0">🎧</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-teal-300">Audio Podcast Luyện nghe</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">File âm thanh kèm transcript đồng bộ</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('pdf_document')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-amber-500/50 hover:bg-amber-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 flex-shrink-0">📑</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-amber-300">Tài liệu Slide / PDF</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Slide bài giảng, tài liệu đọc hiểu</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('text_page')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-slate-500/50 hover:bg-slate-800/40 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-slate-800 border border-slate-700 flex-shrink-0">📝</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-gray-200">Trang Nội dung (Page)</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Soạn bài viết và nội dung văn bản</div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Group 2: Tài nguyên & Tệp tin (Resources & Files) --}}
                <div>
                    <h4 class="text-[11px] uppercase font-bold text-orange-400 tracking-wider mb-2.5 flex items-center gap-1.5">
                        <span>📁</span> <span>Tài nguyên Tệp tin & Liên kết</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-2.5">
                        <button type="button" @click="selectTypeAndOpenDrawer('file')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-orange-500/50 hover:bg-orange-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-orange-500/10 border border-orange-500/20 flex-shrink-0">📁</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-orange-300 flex items-center gap-1.5">
                                    <span>Tải lên Tệp tin (File Upload)</span>
                                    <span class="text-[9px] bg-orange-500/20 text-orange-300 px-1.5 py-0.2 rounded font-mono">Moodle</span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Upload tài liệu PDF, DOCX, XLSX, PPTX, MP3, ZIP...</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('url')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-cyan-500/50 hover:bg-cyan-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-cyan-500/10 border border-cyan-500/20 flex-shrink-0">🔗</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-cyan-300">Liên kết URL ngoài</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Dẫn link tới trang web, bài báo, tài nguyên tham khảo</div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Group 3: Đánh giá & Khảo thí (Assessments) --}}
                <div>
                    <h4 class="text-[11px] uppercase font-bold text-emerald-400 tracking-wider mb-2.5 flex items-center gap-1.5">
                        <span>🎯</span> <span>Đánh giá & Khảo thí</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-2.5">
                        <button type="button" @click="selectTypeAndOpenDrawer('quiz')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex-shrink-0">🎯</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-emerald-300">Bài kiểm tra / Quiz</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Trắc nghiệm tự động chấm điểm, tính điều kiện hoàn thành</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('assignment')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-rose-500/50 hover:bg-rose-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-rose-500/10 border border-rose-500/20 flex-shrink-0">📋</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-rose-300">Bài tập nộp bài (Assignment)</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Học viên tải file bài làm lên để giáo viên chấm điểm</div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Group 4: Tương tác AI (AI Learning) --}}
                <div>
                    <h4 class="text-[11px] uppercase font-bold text-indigo-400 tracking-wider mb-2.5 flex items-center gap-1.5">
                        <span>🤖</span> <span>Luyện tập Trí tuệ nhân tạo (AI Drills)</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-2.5">
                        <button type="button" @click="selectTypeAndOpenDrawer('ai_speaking')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-indigo-500/50 hover:bg-indigo-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex-shrink-0">🎙️</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-indigo-300">AI Speaking Drill</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Luyện phát âm câu tiếng Anh với AI chấm điểm trực tiếp</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('ai_writing')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-indigo-500/50 hover:bg-indigo-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex-shrink-0">✍️</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-indigo-300">AI Writing Task</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Đề bài viết tự luận có AI phân tích và chấm điểm ngữ pháp</div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Group 5: Cộng tác & Cấu trúc (Structure & Collaboration) --}}
                <div>
                    <h4 class="text-[11px] uppercase font-bold text-gray-400 tracking-wider mb-2.5 flex items-center gap-1.5">
                        <span>🏷️</span> <span>Cộng tác & Phân cách Trình bày</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-2.5">
                        <button type="button" @click="selectTypeAndOpenDrawer('label')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-slate-600 hover:bg-slate-800/40 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-slate-800 border border-slate-700 flex-shrink-0">🏷️</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-gray-200">Nhãn phân cách / Tiêu đề (Label)</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Chèn tiêu đề hoặc hướng dẫn phân mục giữa các hoạt động</div>
                            </div>
                        </button>

                        <button type="button" @click="selectTypeAndOpenDrawer('forum')" class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-violet-500/50 hover:bg-violet-950/20 text-left transition-all group flex items-start gap-2.5">
                            <span class="text-2xl p-1.5 rounded-lg bg-violet-500/10 border border-violet-500/20 flex-shrink-0">💬</span>
                            <div>
                                <div class="font-bold text-xs text-white group-hover:text-violet-300">Diễn đàn trao đổi (Forum)</div>
                                <div class="text-[10px] text-gray-400 mt-0.5 leading-tight">Chủ đề thảo luận nhóm giữa giáo viên và học viên</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: ADD / EDIT COURSE QUESTION                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="showQuestionModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
        <div class="admin-card max-w-xl w-full p-6 space-y-4 border-amber-500/40 shadow-2xl overflow-y-auto max-h-[90vh]" @click.away="showQuestionModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">❓</span>
                    <h3 class="text-sm font-bold text-white" x-text="questionModalMode === 'edit' ? 'Chỉnh sửa câu hỏi khóa học' : 'Thêm câu hỏi mới vào khóa học'"></h3>
                </div>
                <button type="button" @click="showQuestionModal = false" class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>

            <form @submit.prevent="saveQuestionModal()" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Kỹ năng (Skill):</label>
                        <div :class="open ? 'relative z-50' : 'relative z-10'" x-data="{
                            open: false,
                            options: {
                                'vocabulary': '📖 Từ vựng (Vocabulary)',
                                'grammar': '📐 Ngữ pháp (Grammar)',
                                'reading': '📰 Đọc hiểu (Reading)',
                                'listening': '🎧 Nghe hiểu (Listening)'
                            }
                        }" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                                <span x-text="options[editingQuestion.skill] || editingQuestion.skill" class="text-white truncate"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="editingQuestion.skill = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="editingQuestion.skill === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="editingQuestion.skill === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Độ khó CEFR:</label>
                        <div :class="open ? 'relative z-50' : 'relative z-10'" x-data="{
                            open: false,
                            options: {
                                'A1': 'Level A1 (Sơ cấp)',
                                'A2': 'Level A2 (Tiền trung cấp)',
                                'B1': 'Level B1 (Trung cấp)',
                                'B2': 'Level B2 (Trung cao cấp)'
                            }
                        }" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                                <span x-text="options[editingQuestion.difficulty] || editingQuestion.difficulty" class="text-white truncate"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="editingQuestion.difficulty = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="editingQuestion.difficulty === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="editingQuestion.difficulty === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Dạng Câu Hỏi: <span class="text-red-400">*</span></label>
                        <div :class="open ? 'relative z-50' : 'relative z-10'" x-data="{
                            open: false,
                            options: {
                                'mcq': '🔘 1. Trắc nghiệm 1 đáp án (Single Choice)',
                                'multiple_select': '☑️ 2. Trắc nghiệm chọn nhiều (Multiple Select)',
                                'fill_blank': '✍️ 3. Điền từ vào chỗ trống (Fill in Blank)',
                                'word_ordering': '🔀 4. Sắp xếp từ thành câu (Sentence Reordering)',
                                'matching': '🔗 5. Nối cặp từ - nghĩa (Matching Pairs)',
                                'true_false': '⚖️ 6. Đúng / Sai / Không đề cập (True / False / Not Given)',
                                'audio_listening': '🎧 7. Nghe Audio & Trả lời (Audio Listening)',
                                'pronunciation_speech': '🎙️ 8. Luyện phát âm AI (Speech Pronunciation)'
                            }
                        }" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs font-bold text-indigo-300 flex items-center justify-between cursor-pointer text-left w-full">
                                <span x-text="options[editingQuestion.question_type] || editingQuestion.question_type" class="truncate"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="editingQuestion.question_type = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="editingQuestion.question_type === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="editingQuestion.question_type === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Nội dung câu hỏi / Đề bài: <span class="text-red-400">*</span></label>
                    <textarea x-model="editingQuestion.question_text" rows="2" required placeholder="Nhập nội dung câu hỏi hoặc đoạn văn đọc hiểu..." class="login-input !py-2 text-xs"></textarea>
                </div>

                {{-- ── CONDITIONAL PANELS FOR 8 QUESTION TYPES ── --}}

                {{-- Type 1: MCQ & Type 7: Audio Listening --}}
                <div x-show="editingQuestion.question_type === 'mcq' || editingQuestion.question_type === 'audio_listening'" class="space-y-3">
                    <div x-show="editingQuestion.question_type === 'audio_listening'">
                        <label class="block text-[10px] uppercase font-bold text-amber-300 mb-1">Đường dẫn tệp Audio mẫu (URL):</label>
                        <input type="text" x-model="editingQuestion.audio_url" placeholder="https://example.com/audio/track-01.mp3" class="login-input !py-1.5 text-xs font-mono">
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Các lựa chọn trắc nghiệm (Mỗi dòng 1 lựa chọn):</label>
                        <textarea x-model="editingQuestion.optionsText" rows="4" placeholder="Lựa chọn A&#10;Lựa chọn B&#10;Lựa chọn C&#10;Lựa chọn D" class="login-input !py-1.5 text-xs font-mono"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Đáp án đúng chính xác: <span class="text-red-400">*</span></label>
                        <input type="text" x-model="editingQuestion.correct_answer" placeholder="Khớp chính xác 1 trong các lựa chọn ở trên..." class="login-input !py-1.5 text-xs">
                    </div>
                </div>

                {{-- Type 2: Multiple Select --}}
                <div x-show="editingQuestion.question_type === 'multiple_select'" class="space-y-3" x-cloak>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Tất cả các lựa chọn (Mỗi dòng 1 lựa chọn):</label>
                        <textarea x-model="editingQuestion.optionsText" rows="4" placeholder="Lựa chọn 1&#10;Lựa chọn 2&#10;Lựa chọn 3&#10;Lựa chọn 4" class="login-input !py-1.5 text-xs font-mono"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-bold text-emerald-400 mb-1">Danh sách các đáp án đúng (Mỗi dòng 1 đáp án đúng): <span class="text-red-400">*</span></label>
                        <textarea x-model="editingQuestion.correct_answers_multi" rows="2" placeholder="Lựa chọn 1&#10;Lựa chọn 2" class="login-input !py-1.5 text-xs font-mono text-emerald-300"></textarea>
                    </div>
                </div>

                {{-- Type 3: Fill in the Blank --}}
                <div x-show="editingQuestion.question_type === 'fill_blank'" class="space-y-3" x-cloak>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Từ / Cụm từ cần điền chính xác: <span class="text-red-400">*</span></label>
                        <input type="text" x-model="editingQuestion.correct_answer" placeholder="Ví dụ: bought" class="login-input !py-1.5 text-xs font-mono">
                        <span class="text-[10px] text-gray-500 mt-1 block">Hệ thống tự động bỏ qua khoảng trắng và không phân biệt hoa thường khi chấm điểm.</span>
                    </div>
                </div>

                {{-- Type 4: Word Ordering --}}
                <div x-show="editingQuestion.question_type === 'word_ordering' || editingQuestion.question_type === 'drag_drop'" class="space-y-3" x-cloak>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Câu hoàn chỉnh đúng ngữ pháp: <span class="text-red-400">*</span></label>
                        <input type="text" x-model="editingQuestion.correct_answer" placeholder="Ví dụ: I study English every day" class="login-input !py-1.5 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Danh sách các từ xáo trộn (Cách nhau bởi dấu phẩy, bỏ trống để tự tách từ câu):</label>
                        <input type="text" x-model="editingQuestion.word_tiles" placeholder="English, every, I, study, day" class="login-input !py-1.5 text-xs font-mono">
                    </div>
                </div>

                {{-- Type 5: Matching Pairs --}}
                <div x-show="editingQuestion.question_type === 'matching'" class="space-y-3" x-cloak>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-indigo-300 mb-1">Cột A (Mỗi dòng 1 từ/khái niệm):</label>
                            <textarea x-model="editingQuestion.matching_left" rows="3" placeholder="Ecosystem&#10;Biodiversity&#10;Conservation" class="login-input !py-1.5 text-xs font-mono"></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-teal-300 mb-1">Cột B (Nghĩa tương ứng theo thứ tự dòng):</label>
                            <textarea x-model="editingQuestion.matching_right" rows="3" placeholder="Hệ sinh thái&#10;Đa dạng sinh học&#10;Bảo tồn thiên nhiên" class="login-input !py-1.5 text-xs font-mono"></textarea>
                        </div>
                    </div>
                    <span class="text-[10px] text-gray-500 block">Khi học viên làm bài, hệ thống sẽ tự động xáo trộn ngẫu nhiên vị trí các mục Cột B.</span>
                </div>

                {{-- Type 6: True / False / Not Given --}}
                <div x-show="editingQuestion.question_type === 'true_false'" class="space-y-3" x-cloak>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Đáp án đúng cho nhận định:</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="ans in ['True', 'False', 'Not Given']" :key="ans">
                                <button type="button" 
                                        @click="editingQuestion.true_false_answer = ans"
                                        :class="editingQuestion.true_false_answer === ans ? 'bg-indigo-600 text-white font-bold border-indigo-500 shadow-md shadow-indigo-900/30' : 'bg-slate-900 text-gray-400 border-slate-700 hover:text-white'"
                                        class="py-2 px-1 rounded-xl border text-xs font-mono font-bold text-center transition-all">
                                    <span x-text="ans"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Type 8: Pronunciation Speech AI --}}
                <div x-show="editingQuestion.question_type === 'pronunciation_speech'" class="space-y-3" x-cloak>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-teal-300 mb-1">Câu mẫu học viên cần thu âm phát âm: <span class="text-red-400">*</span></label>
                        <input type="text" x-model="editingQuestion.correct_answer" placeholder="Ví dụ: Good morning, nice to meet you" class="login-input !py-1.5 text-xs font-mono">
                        <span class="text-[10px] text-gray-500 mt-1 block">AI Web Speech Engine sẽ nhận diện giọng nói và chấm điểm độ tương đồng ngữ âm.</span>
                    </div>
                </div>

                {{-- Explanation --}}
                <div>
                    <label class="block text-[10px] uppercase font-bold text-gray-300 mb-1">Giải thích đáp án chi tiết (Hiển thị sau khi nộp bài):</label>
                    <textarea x-model="editingQuestion.explanation" rows="2" placeholder="Giải thích ngữ pháp, từ vựng hoặc dẫn chứng trong bài..." class="login-input !py-1.5 text-xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="showQuestionModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white">
                        Hủy
                    </button>
                    <button type="submit" :disabled="savingQuestion" class="btn-primary !w-auto !py-2 px-5 text-xs font-semibold shadow-glow-blue bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400">
                        <span x-text="savingQuestion ? 'Đang lưu...' : (questionModalMode === 'edit' ? 'Cập nhật câu hỏi' : 'Lưu vào khóa học')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: IMPORT QUESTIONS FROM GLOBAL BANK                           --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="showImportModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
        <div class="admin-card max-w-3xl w-full p-6 space-y-4 border-cyan-500/40 shadow-2xl flex flex-col max-h-[90vh]" @click.away="showImportModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-sm">📥</span>
                    <div>
                        <h3 class="text-sm font-bold text-white">Lấy câu hỏi từ Ngân hàng chung của hệ thống</h3>
                        <p class="text-[11px] text-gray-400">Chọn các câu hỏi có sẵn để sao chép vào ngân hàng riêng của khóa học này</p>
                    </div>
                </div>
                <button type="button" @click="showImportModal = false" class="text-gray-400 hover:text-white text-xl">&times;</button>
            </div>

            {{-- Filters Bar --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-shrink-0">
                <div>
                    <input type="text" x-model="importSearch" placeholder="Tìm câu hỏi..." class="login-input !py-1.5 text-xs">
                </div>
                <div>
                    <div class="relative" x-data="{
                        open: false,
                        options: {
                            'all': 'Tất cả kỹ năng',
                            'vocabulary': 'Từ vựng',
                            'grammar': 'Ngữ pháp',
                            'reading': 'Đọc hiểu',
                            'listening': 'Nghe hiểu'
                        }
                    }" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                            <span x-text="options[importSkillFilter] || importSkillFilter" class="text-white truncate"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                            <template x-for="(lbl, val) in options" :key="val">
                                <div @click="importSkillFilter = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="importSkillFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                    <span x-text="lbl"></span>
                                    <span x-show="importSkillFilter === val" class="text-emerald-400 font-bold">✓</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="relative" x-data="{
                        open: false,
                        options: {
                            'all': 'Tất cả trình độ',
                            'A1': 'Level A1',
                            'A2': 'Level A2',
                            'B1': 'Level B1',
                            'B2': 'Level B2'
                        }
                    }" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                            <span x-text="options[importDifficultyFilter] || importDifficultyFilter" class="text-white truncate"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                            <template x-for="(lbl, val) in options" :key="val">
                                <div @click="importDifficultyFilter = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="importDifficultyFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                    <span x-text="lbl"></span>
                                    <span x-show="importDifficultyFilter === val" class="text-emerald-400 font-bold">✓</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Selection Bar --}}
            <div class="flex items-center justify-between px-3 py-2 bg-slate-900/90 rounded-xl border border-slate-800 text-xs flex-shrink-0">
                <label class="inline-flex items-center gap-2 cursor-pointer text-gray-300">
                    <input type="checkbox" @click="toggleImportSelectAll()"
                           :checked="filteredGlobalQuestions.length > 0 && selectedImportIds.length === filteredGlobalQuestions.length"
                           class="rounded bg-slate-800 border-slate-700 text-cyan-500">
                    <span>Chọn tất cả (<span x-text="filteredGlobalQuestions.length"></span> câu phù hợp)</span>
                </label>
                <span class="font-mono text-cyan-300 font-bold" x-text="'Đã chọn: ' + selectedImportIds.length"></span>
            </div>

            {{-- Questions Table / Scroll Container --}}
            <div class="flex-1 overflow-y-auto min-h-0 border border-slate-800 rounded-xl divide-y divide-slate-800/80 bg-slate-950/50">
                <template x-if="filteredGlobalQuestions.length === 0">
                    <div class="p-8 text-center text-gray-500 text-xs">
                        Không tìm thấy câu hỏi nào trong ngân hàng chung phù hợp với bộ lọc.
                    </div>
                </template>

                <template x-for="gq in filteredGlobalQuestions" :key="gq.id">
                    <label class="p-3.5 hover:bg-slate-800/30 flex items-start gap-3 cursor-pointer transition-colors block">
                        <input type="checkbox" :value="gq.id" x-model.number="selectedImportIds"
                               class="mt-1 rounded bg-slate-800 border-slate-700 text-cyan-500 flex-shrink-0">
                        <div class="space-y-1 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-purple-500/20 text-purple-300" x-text="gq.skill"></span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold font-mono bg-slate-800 text-amber-300" x-text="gq.difficulty"></span>
                                <span class="text-[10px] text-gray-400 font-semibold truncate" x-text="gq.question_text"></span>
                            </div>
                            <template x-if="Array.isArray(gq.options) && gq.options.length > 0">
                                <p class="text-[11px] text-gray-500 truncate" x-text="gq.options.join(' | ')"></p>
                            </template>
                        </div>
                    </label>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between pt-3 border-t border-slate-800 flex-shrink-0">
                <span class="text-xs text-gray-400 font-mono" x-text="'Tổng câu hỏi chung: ' + globalQuestions.length"></span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white">
                        Đóng
                    </button>
                    <button type="button" @click="importSelectedQuestions()" :disabled="importing || selectedImportIds.length === 0"
                            class="btn-primary !w-auto !py-2 px-5 text-xs font-semibold shadow-glow-blue bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 disabled:opacity-50">
                        <span x-text="importing ? 'Đang nhập...' : 'Nhập (' + selectedImportIds.length + ') câu hỏi vào khóa'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- MODAL 4: ACTIVITY SETTINGS DRAWER (COMPREHENSIVE CONFIG ENGINE)   --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div x-show="showDrawer" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/80 flex justify-end">
        <div class="bg-[#0b101e] border-l border-slate-800 w-full max-w-2xl h-full flex flex-col shadow-2xl overflow-hidden"
             @click.away="showDrawer = false">
            
            {{-- Drawer Header --}}
            <div class="p-4 sm:p-5 border-b border-slate-800 bg-slate-900/90 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl p-2 rounded-xl bg-slate-800 border border-slate-700" x-text="drawerActivity.typeInfo?.icon || '⚙️'"></span>
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <span x-text="drawerMode === 'create' ? 'Thêm Hoạt động mới' : 'Cấu hình Hoạt động'"></span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300" x-text="drawerActivity.type"></span>
                        </h3>
                        <p class="text-[11px] text-gray-400">
                            Bài học: <strong class="text-indigo-400" x-text="targetLessonTitle"></strong>
                        </p>
                    </div>
                </div>

                <button type="button" @click="showDrawer = false" class="text-gray-400 hover:text-white p-1 text-xl">&times;</button>
            </div>

            {{-- Drawer Navigation Tabs --}}
            <div class="flex border-b border-slate-800 bg-slate-950/60 px-5 gap-4 text-xs font-semibold">
                <button type="button" @click="drawerTab = 'general'"
                        class="py-3 border-b-2 transition-all flex items-center gap-1.5"
                        :class="drawerTab === 'general' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400 hover:text-white'">
                    <span>⚙️ Cài đặt chung</span>
                </button>
                <button type="button" @click="drawerTab = 'content'"
                        class="py-3 border-b-2 transition-all flex items-center gap-1.5"
                        :class="drawerTab === 'content' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400 hover:text-white'">
                    <span>📄 Nội dung & Tệp tin</span>
                </button>
                <button type="button" @click="drawerTab = 'timing'"
                        class="py-3 border-b-2 transition-all flex items-center gap-1.5"
                        :class="drawerTab === 'timing' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400 hover:text-white'">
                    <span>⏰ Thời gian & Giới hạn</span>
                </button>
                <button type="button" @click="drawerTab = 'completion'"
                        class="py-3 border-b-2 transition-all flex items-center gap-1.5"
                        :class="drawerTab === 'completion' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400 hover:text-white'">
                    <span>🎯 Điều kiện Hoàn thành</span>
                </button>
            </div>

            {{-- Drawer Body: Form Content --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-5">
                
                {{-- TAB A: GENERAL SETTINGS --}}
                <div x-show="drawerTab === 'general'" class="space-y-4">
                    <div>
                        <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Tiêu đề hoạt động: <span class="text-rose-400">*</span></label>
                        <input type="text" x-model="drawerActivity.title" required placeholder="VD: Từ vựng chủ đề Nghề nghiệp / Video Bài giảng" class="login-input !py-2 text-xs">
                    </div>

                    <div>
                        <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Mô tả tóm tắt (Description):</label>
                        <textarea x-model="drawerActivity.description" rows="2" placeholder="Hiển thị tóm tắt ngắn gọn bên dưới tiêu đề..." class="login-input !py-1.5 text-xs"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Thời lượng ước tính (phút):</label>
                            <input type="number" x-model="drawerActivity.estimated_minutes" min="1" class="login-input !py-2 text-xs font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Thứ tự trong bài học:</label>
                            <input type="number" x-model="drawerActivity.order" min="1" class="login-input !py-2 text-xs font-mono">
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 space-y-2">
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="text-xs font-bold text-white">Hiển thị với học viên (Visibility)</span>
                            <input type="checkbox" x-model="drawerActivity.is_visible" class="rounded bg-slate-800 border-slate-700 text-indigo-600">
                        </label>
                        <p class="text-[10px] text-gray-400 leading-relaxed">
                            Nếu tắt, hoạt động này sẽ bị ẩn hoàn toàn đối với học viên cho đến khi bạn bật lại.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 space-y-2">
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                <span>✨ Cho phép học thử (Free Trial Preview)</span>
                            </span>
                            <input type="checkbox" x-model="drawerActivity.is_free_trial" class="rounded bg-slate-800 border-slate-700 text-teal-500">
                        </label>
                        <p class="text-[10px] text-gray-400 leading-relaxed">
                            Nếu bật, người dùng chưa ghi danh khóa học vẫn có thể trải nghiệm xem/làm thử hoạt động này (không ghi nhận tiến trình hay điểm số).
                        </p>
                    </div>
                </div>

                {{-- TAB B: CONTENT & FILES (DYNAMIC BASED ON TYPE) --}}
                <div x-show="drawerTab === 'content'" class="space-y-4">
                    
                    {{-- 1. FILE UPLOAD (Moodle File Resource) --}}
                    <div x-show="drawerActivity.type === 'file'" class="space-y-4">
                        <div class="p-4 rounded-xl bg-orange-500/10 border border-orange-500/30">
                            <h4 class="text-xs font-bold text-orange-300 mb-1">📁 Tải lên tệp tài liệu (PDF, Word, Excel, PPT, MP3, ZIP...)</h4>
                            <p class="text-[11px] text-gray-300 leading-relaxed">Học viên có thể tải về hoặc xem trực tuyến tài liệu đính kèm này.</p>
                        </div>

                        {{-- Drag & Drop Upload Zone --}}
                        <div class="border-2 border-dashed border-slate-700 hover:border-orange-400/80 rounded-2xl p-6 text-center transition-all bg-slate-950/60"
                             @dragover.prevent=""
                             @drop.prevent="handleFileDrop($event)">
                            
                            <input type="file" id="drawerFileInput" class="hidden" @change="handleFileSelect($event)">

                            <div x-show="!drawerActivity.file_id && !uploading">
                                <span class="text-3xl block mb-2">📤</span>
                                <p class="text-xs text-gray-300 font-semibold mb-1">Kéo thả tệp tin vào đây, hoặc</p>
                                <button type="button" @click="document.getElementById('drawerFileInput').click()" class="px-4 py-1.5 rounded-xl bg-orange-500/20 hover:bg-orange-500/30 text-orange-300 border border-orange-500/40 text-xs font-bold transition-all">
                                    Chọn tệp từ máy tính
                                </button>
                                <p class="text-[10px] text-gray-500 mt-2">Dung lượng tối đa 50MB. Hỗ trợ: PDF, Word, PowerPoint, Excel, Audio, Video, ZIP.</p>
                            </div>

                            {{-- Uploading State --}}
                            <div x-show="uploading" class="space-y-2 py-4">
                                <div class="w-8 h-8 border-2 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                <p class="text-xs text-orange-300 font-semibold">Đang tải tệp lên máy chủ...</p>
                            </div>

                            {{-- File Uploaded State --}}
                            <div x-show="drawerActivity.file_id && !uploading" class="p-3 bg-slate-900 rounded-xl border border-slate-800 text-left flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">📄</span>
                                    <div>
                                        <div class="text-xs font-bold text-white truncate max-w-sm" x-text="drawerActivity.file_original_name || 'Tệp đính kèm'"></div>
                                        <div class="text-[10px] font-mono text-gray-400 mt-0.5" x-text="formatFileSize(drawerActivity.file_size)"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="document.getElementById('drawerFileInput').click()" class="text-xs text-orange-400 hover:underline">
                                        Đổi tệp khác
                                    </button>
                                    <button type="button" @click="drawerActivity.file_id = null; drawerActivity.file_size = 0; drawerActivity.file_original_name = ''" class="text-xs text-rose-400 hover:underline">
                                        Xóa
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Ghi chú hướng dẫn cho học viên:</label>
                            <textarea x-model="drawerActivity.content.notes" rows="2" placeholder="VD: Tải file về và đọc kỹ trang 5-10 trước buổi học..." class="login-input !py-1.5 text-xs"></textarea>
                        </div>
                    </div>

                    {{-- 2. VIDEO --}}
                    <div x-show="drawerActivity.type === 'video'" class="space-y-3">
                        {{-- Source Tabs --}}
                        <div class="flex gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
                            <button type="button" @click="drawerActivity.content.source = 'url'"
                                :class="(drawerActivity.content.source || 'url') === 'url' ? 'bg-red-500/20 text-red-300 border-red-500/40' : 'text-gray-400 hover:text-gray-200 border-transparent'"
                                class="flex-1 text-[10px] font-bold py-1.5 px-3 rounded-lg border transition-all">
                                🔗 Nhập URL
                            </button>
                            <button type="button" @click="drawerActivity.content.source = 'upload'"
                                :class="drawerActivity.content.source === 'upload' ? 'bg-red-500/20 text-red-300 border-red-500/40' : 'text-gray-400 hover:text-gray-200 border-transparent'"
                                class="flex-1 text-[10px] font-bold py-1.5 px-3 rounded-lg border transition-all">
                                📤 Upload Video
                            </button>
                        </div>

                        {{-- URL Input --}}
                        <div x-show="(drawerActivity.content.source || 'url') === 'url'">
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Đường dẫn Video (YouTube / Vimeo / URL MP4):</label>
                            <input type="text" x-model="drawerActivity.content.video_url" placeholder="https://www.youtube.com/watch?v=... hoặc https://.../video.mp4" class="login-input !py-2 text-xs font-mono">
                        </div>

                        {{-- Upload Zone --}}
                        <div x-show="drawerActivity.content.source === 'upload'">
                            <div class="border-2 border-dashed border-slate-700 hover:border-red-400/80 rounded-2xl p-4 text-center transition-all bg-slate-950/60"
                                 @dragover.prevent="" @drop.prevent="handleMediaDrop($event, 'video')">
                                <input type="file" :id="'drawerVideoInput'" accept="video/mp4,video/webm,video/ogg" class="hidden" @change="handleMediaSelect($event, 'video')">

                                <div x-show="!drawerActivity.file_id && !uploading">
                                    <span class="text-2xl block mb-1">🎬</span>
                                    <p class="text-[10px] text-gray-300 font-semibold mb-1">Kéo thả file video vào đây</p>
                                    <button type="button" @click="document.getElementById('drawerVideoInput').click()" class="px-3 py-1 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/40 text-[10px] font-bold transition-all">
                                        Chọn file video
                                    </button>
                                    <p class="text-[9px] text-gray-500 mt-1">Hỗ trợ: MP4, WebM, OGG. Tối đa 50MB.</p>
                                </div>
                                <div x-show="uploading" class="py-3">
                                    <div class="w-6 h-6 border-2 border-red-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                    <p class="text-[10px] text-red-300 font-semibold mt-1">Đang tải video lên...</p>
                                </div>
                                <div x-show="drawerActivity.file_id && !uploading && drawerActivity.content.source === 'upload'" class="p-2.5 bg-slate-900 rounded-xl border border-slate-800 text-left flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🎬</span>
                                        <div>
                                            <div class="text-[10px] font-bold text-white truncate max-w-xs" x-text="drawerActivity.file_original_name || 'Video'"></div>
                                            <div class="text-[9px] font-mono text-gray-400" x-text="formatFileSize(drawerActivity.file_size)"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="document.getElementById('drawerVideoInput').click()" class="text-[10px] text-red-400 hover:underline">Đổi</button>
                                        <button type="button" @click="drawerActivity.file_id = null; drawerActivity.file_size = 0; drawerActivity.file_original_name = ''" class="text-[10px] text-rose-400 hover:underline">Xóa</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Thời lượng video:</label>
                                <input type="text" x-model="drawerActivity.content.duration" placeholder="10:00" class="login-input !py-2 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Mô tả nội dung video:</label>
                                <input type="text" x-model="drawerActivity.content.description" placeholder="Giới thiệu nội dung bài học..." class="login-input !py-2 text-xs">
                            </div>
                        </div>
                    </div>

                    {{-- 3. AUDIO LISTENING --}}
                    <div x-show="drawerActivity.type === 'audio_listening'" class="space-y-3">
                        {{-- Source Tabs --}}
                        <div class="flex gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
                            <button type="button" @click="drawerActivity.content.source = 'url'"
                                :class="(drawerActivity.content.source || 'url') === 'url' ? 'bg-teal-500/20 text-teal-300 border-teal-500/40' : 'text-gray-400 hover:text-gray-200 border-transparent'"
                                class="flex-1 text-[10px] font-bold py-1.5 px-3 rounded-lg border transition-all">
                                🔗 Nhập URL
                            </button>
                            <button type="button" @click="drawerActivity.content.source = 'upload'"
                                :class="drawerActivity.content.source === 'upload' ? 'bg-teal-500/20 text-teal-300 border-teal-500/40' : 'text-gray-400 hover:text-gray-200 border-transparent'"
                                class="flex-1 text-[10px] font-bold py-1.5 px-3 rounded-lg border transition-all">
                                📤 Upload Audio
                            </button>
                        </div>

                        {{-- URL Input --}}
                        <div x-show="(drawerActivity.content.source || 'url') === 'url'">
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Đường dẫn Audio (MP3 URL):</label>
                            <input type="text" x-model="drawerActivity.content.audio_url" placeholder="https://domain.com/audio.mp3" class="login-input !py-2 text-xs font-mono">
                        </div>

                        {{-- Upload Zone --}}
                        <div x-show="drawerActivity.content.source === 'upload'">
                            <div class="border-2 border-dashed border-slate-700 hover:border-teal-400/80 rounded-2xl p-4 text-center transition-all bg-slate-950/60"
                                 @dragover.prevent="" @drop.prevent="handleMediaDrop($event, 'audio')">
                                <input type="file" :id="'drawerAudioInput'" accept="audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/x-m4a" class="hidden" @change="handleMediaSelect($event, 'audio')">

                                <div x-show="!drawerActivity.file_id && !uploading">
                                    <span class="text-2xl block mb-1">🎧</span>
                                    <p class="text-[10px] text-gray-300 font-semibold mb-1">Kéo thả file audio vào đây</p>
                                    <button type="button" @click="document.getElementById('drawerAudioInput').click()" class="px-3 py-1 rounded-xl bg-teal-500/20 hover:bg-teal-500/30 text-teal-300 border border-teal-500/40 text-[10px] font-bold transition-all">
                                        Chọn file audio
                                    </button>
                                    <p class="text-[9px] text-gray-500 mt-1">Hỗ trợ: MP3, WAV, OGG, M4A. Tối đa 50MB.</p>
                                </div>
                                <div x-show="uploading" class="py-3">
                                    <div class="w-6 h-6 border-2 border-teal-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                    <p class="text-[10px] text-teal-300 font-semibold mt-1">Đang tải audio lên...</p>
                                </div>
                                <div x-show="drawerActivity.file_id && !uploading && drawerActivity.content.source === 'upload'" class="p-2.5 bg-slate-900 rounded-xl border border-slate-800 text-left flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🎵</span>
                                        <div>
                                            <div class="text-[10px] font-bold text-white truncate max-w-xs" x-text="drawerActivity.file_original_name || 'Audio'"></div>
                                            <div class="text-[9px] font-mono text-gray-400" x-text="formatFileSize(drawerActivity.file_size)"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="document.getElementById('drawerAudioInput').click()" class="text-[10px] text-teal-400 hover:underline">Đổi</button>
                                        <button type="button" @click="drawerActivity.file_id = null; drawerActivity.file_size = 0; drawerActivity.file_original_name = ''" class="text-[10px] text-rose-400 hover:underline">Xóa</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Transcript lời thoại đồng bộ:</label>
                            <textarea x-model="drawerActivity.content.transcript" rows="3" placeholder="Nhập toàn bộ nội dung lời thoại tiếng Anh..." class="login-input !py-2 text-xs"></textarea>
                        </div>
                    </div>

                    {{-- 4. PDF DOCUMENT / SLIDE --}}
                    <div x-show="drawerActivity.type === 'pdf_document'" class="space-y-3">
                        {{-- Source Tabs --}}
                        <div class="flex gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
                            <button type="button" @click="drawerActivity.content.source = 'url'"
                                :class="(drawerActivity.content.source || 'url') === 'url' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'text-gray-400 hover:text-gray-200 border-transparent'"
                                class="flex-1 text-[10px] font-bold py-1.5 px-3 rounded-lg border transition-all">
                                🔗 Nhập URL
                            </button>
                            <button type="button" @click="drawerActivity.content.source = 'upload'"
                                :class="drawerActivity.content.source === 'upload' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : 'text-gray-400 hover:text-gray-200 border-transparent'"
                                class="flex-1 text-[10px] font-bold py-1.5 px-3 rounded-lg border transition-all">
                                📤 Upload File
                            </button>
                        </div>

                        {{-- URL Input --}}
                        <div x-show="(drawerActivity.content.source || 'url') === 'url'">
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Đường dẫn Slide / File PDF trực tuyến:</label>
                            <input type="text" x-model="drawerActivity.content.document_url" placeholder="https://domain.com/document.pdf" class="login-input !py-2 text-xs font-mono">
                        </div>

                        {{-- Upload Zone --}}
                        <div x-show="drawerActivity.content.source === 'upload'">
                            <div class="border-2 border-dashed border-slate-700 hover:border-amber-400/80 rounded-2xl p-4 text-center transition-all bg-slate-950/60"
                                 @dragover.prevent="" @drop.prevent="handleMediaDrop($event, 'pdf')">
                                <input type="file" :id="'drawerPdfInput'" accept="application/pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx" class="hidden" @change="handleMediaSelect($event, 'pdf')">

                                <div x-show="!drawerActivity.file_id && !uploading">
                                    <span class="text-2xl block mb-1">📑</span>
                                    <p class="text-[10px] text-gray-300 font-semibold mb-1">Kéo thả file tài liệu vào đây</p>
                                    <button type="button" @click="document.getElementById('drawerPdfInput').click()" class="px-3 py-1 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-[10px] font-bold transition-all">
                                        Chọn file tài liệu
                                    </button>
                                    <p class="text-[9px] text-gray-500 mt-1">Hỗ trợ: PDF, Word, PowerPoint, Excel. Tối đa 50MB.</p>
                                </div>
                                <div x-show="uploading" class="py-3">
                                    <div class="w-6 h-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                    <p class="text-[10px] text-amber-300 font-semibold mt-1">Đang tải file lên...</p>
                                </div>
                                <div x-show="drawerActivity.file_id && !uploading && drawerActivity.content.source === 'upload'" class="p-2.5 bg-slate-900 rounded-xl border border-slate-800 text-left flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📑</span>
                                        <div>
                                            <div class="text-[10px] font-bold text-white truncate max-w-xs" x-text="drawerActivity.file_original_name || 'Tài liệu'"></div>
                                            <div class="text-[9px] font-mono text-gray-400" x-text="formatFileSize(drawerActivity.file_size)"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="document.getElementById('drawerPdfInput').click()" class="text-[10px] text-amber-400 hover:underline">Đổi</button>
                                        <button type="button" @click="drawerActivity.file_id = null; drawerActivity.file_size = 0; drawerActivity.file_original_name = ''" class="text-[10px] text-rose-400 hover:underline">Xóa</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1.5">Ghi chú & Tóm tắt trọng tâm:</label>
                            <textarea x-model="drawerActivity.content.notes" rows="2" placeholder="Các điểm ngữ pháp và từ vựng cốt lõi..." class="login-input !py-2 text-xs"></textarea>
                        </div>
                    </div>

                    {{-- 5. VOCABULARY FLASHCARDS --}}
                    <div x-show="drawerActivity.type === 'vocabulary'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-[11px] uppercase font-bold text-purple-300">Danh sách Từ vựng Flashcards:</label>
                            <button type="button" @click="addVocabWord()" class="text-xs text-purple-400 hover:underline font-bold">+ Thêm từ</button>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <template x-for="(w, idx) in drawerVocabList" :key="idx">
                                <div class="grid grid-cols-4 gap-2 bg-slate-900 p-2.5 rounded-xl border border-slate-800 items-center">
                                    <input type="text" x-model="w.word" placeholder="Từ (VD: diligent)" class="login-input !py-1 text-xs">
                                    <input type="text" x-model="w.meaning" placeholder="Nghĩa (chăm chỉ)" class="login-input !py-1 text-xs">
                                    <input type="text" x-model="w.phonetic" placeholder="/ˈdɪlɪdʒənt/" class="login-input !py-1 text-xs font-mono">
                                    <div class="flex items-center gap-1">
                                        <input type="text" x-model="w.example" placeholder="Ví dụ..." class="login-input !py-1 text-xs flex-1">
                                        <button type="button" @click="drawerVocabList.splice(idx, 1)" class="text-rose-400 hover:text-white p-1">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- 6. GRAMMAR --}}
                    <div x-show="drawerActivity.type === 'grammar'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Giải thích cấu trúc:</label>
                            <textarea x-model="drawerActivity.content.explanation" rows="2" placeholder="Giải thích công thức và ngữ cảnh sử dụng..." class="login-input !py-1.5 text-xs"></textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Các quy tắc chia (Mỗi dòng 1 quy tắc):</label>
                            <textarea x-model="drawerGrammarRules" rows="2" placeholder="S + V(s/es) + O&#10;S + don't/doesn't + V..." class="login-input !py-1.5 text-xs font-mono"></textarea>
                        </div>
                    </div>

                    {{-- 7. QUIZ BUILDER (3 MODES: INLINE, PICK FROM BANK, RANDOM POOL) --}}
                    <div x-show="drawerActivity.type === 'quiz'" class="space-y-4">
                        {{-- Mode Switcher Buttons --}}
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-400 mb-1.5">Nguồn câu hỏi cho bài Quiz:</label>
                            <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-950 rounded-xl border border-slate-800 text-xs">
                                <button type="button" @click="quizSourceMode = 'inline'"
                                    :class="quizSourceMode === 'inline' ? 'bg-emerald-600 text-white font-bold shadow' : 'text-gray-400 hover:text-white'"
                                    class="py-2 px-2 rounded-lg transition-all text-center flex items-center justify-center gap-1.5">
                                    <span>✍️</span>
                                    <span>Tự soạn</span>
                                </button>
                                <button type="button" @click="quizSourceMode = 'bank_manual'"
                                    :class="quizSourceMode === 'bank_manual' ? 'bg-emerald-600 text-white font-bold shadow' : 'text-gray-400 hover:text-white'"
                                    class="py-2 px-2 rounded-lg transition-all text-center flex items-center justify-center gap-1.5">
                                    <span>📋</span>
                                    <span>Chọn từ Ngân hàng</span>
                                </button>
                                <button type="button" @click="quizSourceMode = 'bank_random'"
                                    :class="quizSourceMode === 'bank_random' ? 'bg-emerald-600 text-white font-bold shadow' : 'text-gray-400 hover:text-white'"
                                    class="py-2 px-2 rounded-lg transition-all text-center flex items-center justify-center gap-1.5">
                                    <span>🎲</span>
                                    <span>Bốc ngẫu nhiên</span>
                                </button>
                            </div>
                        </div>

                        {{-- MODE 1: INLINE CUSTOM QUESTIONS --}}
                        <div x-show="quizSourceMode === 'inline'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Tự soạn các câu hỏi trắc nghiệm riêng cho bài học này:</span>
                                <button type="button" @click="addQuizQuestion()" class="text-xs text-emerald-400 hover:underline font-bold">+ Thêm câu hỏi</button>
                            </div>

                            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                <template x-for="(q, qidx) in drawerQuizQuestions" :key="qidx">
                                    <div class="p-3 bg-slate-900/90 rounded-xl border border-slate-800 space-y-2 text-xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-mono text-gray-400 font-bold" x-text="'Câu ' + (qidx + 1) + ':'"></span>
                                            <input type="text" x-model="q.question" placeholder="Nội dung câu hỏi..." class="login-input !py-1 text-xs flex-1">
                                            <button type="button" @click="drawerQuizQuestions.splice(qidx, 1)" class="text-rose-400 hover:text-white p-1">&times;</button>
                                        </div>
                                        <input type="text" x-model="q.optionsText" placeholder="Các lựa chọn (phân cách bằng dấu phẩy): Đáp án A, Đáp án B, Đáp án C, Đáp án D" class="login-input !py-1 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400 text-[10px]">Vị trí đáp án đúng:</span>
                                            <div class="flex items-center gap-1">
                                                <template x-for="(optLabel, optIdx) in ['A', 'B', 'C', 'D']" :key="optIdx">
                                                    <button type="button" 
                                                            @click="q.answer = optIdx"
                                                            :class="q.answer === optIdx ? 'bg-emerald-600 text-white font-bold border-emerald-500 shadow-sm' : 'bg-slate-900 text-gray-400 border-slate-700 hover:text-white'"
                                                            class="py-1 px-2.5 rounded-lg border text-[11px] font-mono transition-all">
                                                        <span x-text="'Đáp án ' + optLabel + ' (' + optIdx + ')'"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- MODE 2: PICK SPECIFIC QUESTIONS FROM COURSE BANK --}}
                        <div x-show="quizSourceMode === 'bank_manual'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Chọn các câu hỏi từ ngân hàng khóa học:</span>
                                <span class="text-xs font-mono px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-400 font-bold"
                                      x-text="'Đã chọn: ' + quizSelectedQuestionIds.length + ' câu'"></span>
                            </div>

                            {{-- Mini Filters --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <input type="text" x-model="quizBankSearch" placeholder="Tìm câu hỏi..." class="login-input !py-1 text-[11px]">
                                <div class="relative" x-data="{
                                    open: false,
                                    options: {
                                        'all': 'Tất cả kỹ năng',
                                        'vocabulary': 'Từ vựng',
                                        'grammar': 'Ngữ pháp',
                                        'reading': 'Đọc hiểu',
                                        'listening': 'Nghe hiểu'
                                    }
                                }" @click.outside="open = false">
                                    <button type="button" @click="open = !open" class="login-input !py-1 text-[11px] flex items-center justify-between cursor-pointer text-left w-full">
                                        <span x-text="options[quizBankSkillFilter] || quizBankSkillFilter" class="text-white truncate"></span>
                                        <svg class="w-3 h-3 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                        <template x-for="(lbl, val) in options" :key="val">
                                            <div @click="quizBankSkillFilter = val; open = false" class="px-2.5 py-1.5 text-[11px] font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="quizBankSkillFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                                <span x-text="lbl"></span>
                                                <span x-show="quizBankSkillFilter === val" class="text-emerald-400 font-bold">✓</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div class="relative" x-data="{
                                    open: false,
                                    options: {
                                        'all': 'Tất cả độ khó',
                                        'A1': 'Level A1',
                                        'A2': 'Level A2',
                                        'B1': 'Level B1',
                                        'B2': 'Level B2'
                                    }
                                }" @click.outside="open = false">
                                    <button type="button" @click="open = !open" class="login-input !py-1 text-[11px] flex items-center justify-between cursor-pointer text-left w-full">
                                        <span x-text="options[quizBankDifficultyFilter] || quizBankDifficultyFilter" class="text-white truncate"></span>
                                        <svg class="w-3 h-3 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                        <template x-for="(lbl, val) in options" :key="val">
                                            <div @click="quizBankDifficultyFilter = val; open = false" class="px-2.5 py-1.5 text-[11px] font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="quizBankDifficultyFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                                <span x-text="lbl"></span>
                                                <span x-show="quizBankDifficultyFilter === val" class="text-emerald-400 font-bold">✓</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Questions Checklist --}}
                            <div class="max-h-64 overflow-y-auto space-y-1.5 border border-slate-800 rounded-xl p-2 bg-slate-950/60">
                                <template x-if="filteredQuizBankQuestions.length === 0">
                                    <div class="p-6 text-center text-xs text-gray-500">
                                        Không có câu hỏi nào trong ngân hàng phù hợp. Hãy vào tab "Ngân hàng câu hỏi" để thêm câu hỏi trước.
                                    </div>
                                </template>

                                <template x-for="bq in filteredQuizBankQuestions" :key="bq.id">
                                    <label class="p-2.5 rounded-lg border flex items-start gap-2.5 cursor-pointer transition-colors"
                                           :class="quizSelectedQuestionIds.includes(bq.id)
                                               ? 'bg-emerald-500/10 border-emerald-500/40 text-white'
                                               : 'bg-slate-900 border-slate-800 hover:bg-slate-800/50 text-gray-400'">
                                        <input type="checkbox" :value="bq.id" x-model.number="quizSelectedQuestionIds"
                                               class="mt-1 rounded bg-slate-800 border-slate-700 text-emerald-500 flex-shrink-0">
                                        <div class="space-y-1 flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-purple-500/20 text-purple-300" x-text="bq.skill"></span>
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold font-mono bg-slate-800 text-amber-300" x-text="bq.difficulty"></span>
                                                <span class="text-xs font-semibold truncate block" x-text="bq.question_text"></span>
                                            </div>
                                            <template x-if="Array.isArray(bq.options) && bq.options.length > 0">
                                                <p class="text-[10px] text-gray-500 truncate" x-text="bq.options.join(' · ')"></p>
                                            </template>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- MODE 3: RANDOM DRAW FROM COURSE BANK --}}
                        <div x-show="quizSourceMode === 'bank_random'" class="space-y-3.5 p-4 rounded-xl bg-slate-950/70 border border-slate-800">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🎲</span>
                                <div>
                                    <h4 class="text-xs font-bold text-white">Bốc ngẫu nhiên từ Ngân hàng câu hỏi</h4>
                                    <p class="text-[10px] text-gray-400">Mỗi học viên khi làm bài Quiz sẽ được hệ thống bốc ngẫu nhiên các câu hỏi theo tiêu chí bên dưới.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Số câu hỏi cần bốc:</label>
                                    <input type="number" x-model.number="quizRandomCount" min="1" max="100" class="login-input !py-1.5 text-xs font-mono text-emerald-400 font-bold">
                                </div>

                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Bộ lọc kỹ năng (Skill):</label>
                                    <div class="relative" x-data="{
                                        open: false,
                                        options: {
                                            'all': '🎲 Tất cả kỹ năng',
                                            'vocabulary': '📖 Từ vựng (Vocabulary)',
                                            'grammar': '📐 Ngữ pháp (Grammar)',
                                            'reading': '📰 Đọc hiểu (Reading)',
                                            'listening': '🎧 Nghe hiểu (Listening)'
                                        }
                                    }" @click.outside="open = false">
                                        <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                                            <span x-text="options[quizSkillFilter] || quizSkillFilter" class="text-white truncate"></span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                            <template x-for="(lbl, val) in options" :key="val">
                                                <div @click="quizSkillFilter = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="quizSkillFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                                    <span x-text="lbl"></span>
                                                    <span x-show="quizSkillFilter === val" class="text-emerald-400 font-bold">✓</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Bộ lọc độ khó (CEFR):</label>
                                    <div class="relative" x-data="{
                                        open: false,
                                        options: {
                                            'all': '🎲 Tất cả độ khó',
                                            'A1': 'Level A1 (Sơ cấp)',
                                            'A2': 'Level A2 (Tiền trung cấp)',
                                            'B1': 'Level B1 (Trung cấp)',
                                            'B2': 'Level B2 (Trung cao cấp)'
                                        }
                                    }" @click.outside="open = false">
                                        <button type="button" @click="open = !open" class="login-input !py-1.5 text-xs flex items-center justify-between cursor-pointer text-left w-full">
                                            <span x-text="options[quizDifficultyFilter] || quizDifficultyFilter" class="text-white truncate"></span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 flex-shrink-0 ml-1.5" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-48 overflow-y-auto">
                                            <template x-for="(lbl, val) in options" :key="val">
                                                <div @click="quizDifficultyFilter = val; open = false" class="px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="quizDifficultyFilter === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                                    <span x-text="lbl"></span>
                                                    <span x-show="quizDifficultyFilter === val" class="text-emerald-400 font-bold">✓</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Live Pool Metrics --}}
                            <div class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-xs flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span>📊</span>
                                    <span class="text-emerald-300">
                                        Số lượng câu hỏi trong ngân hàng đáp ứng tiêu chí lọc:
                                        <strong class="text-white font-mono text-sm" x-text="quizRandomPoolCount"></strong> câu
                                    </span>
                                </div>
                                <template x-if="quizRandomPoolCount < quizRandomCount">
                                    <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                                        ⚠️ Ít hơn số câu yêu cầu
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- 8. AI SPEAKING DRILL --}}
                    <div x-show="drawerActivity.type === 'ai_speaking'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Câu mẫu tiếng Anh:</label>
                            <input type="text" x-model="drawerActivity.content.target_sentence" placeholder="VD: The weather in Hanoi is wonderful today." class="login-input !py-2 text-xs">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Phiên âm IPA:</label>
                                <input type="text" x-model="drawerActivity.content.phonetic_guide" placeholder="/ðə ˈweðər..." class="login-input !py-2 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Mẹo phát âm:</label>
                                <input type="text" x-model="drawerActivity.content.tip" placeholder="Chú ý âm đuôi..." class="login-input !py-2 text-xs">
                            </div>
                        </div>
                    </div>

                    {{-- 9. AI WRITING TASK --}}
                    <div x-show="drawerActivity.type === 'ai_writing'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Đề bài tự luận:</label>
                            <textarea x-model="drawerActivity.content.prompt" rows="2" placeholder="VD: Describe your favorite hobby and why you enjoy it..." class="login-input !py-2 text-xs"></textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Số từ tối thiểu yêu cầu:</label>
                            <input type="number" x-model="drawerActivity.content.min_words" min="20" class="login-input !py-2 text-xs font-mono w-32">
                        </div>
                    </div>

                    {{-- 10. URL LINK --}}
                    <div x-show="drawerActivity.type === 'url'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Đường dẫn liên kết URL: <span class="text-rose-400">*</span></label>
                            <input type="url" x-model="drawerActivity.content.url" placeholder="https://example.com/english-guide" class="login-input !py-2 text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Hướng dẫn cho học viên:</label>
                            <textarea x-model="drawerActivity.content.instructions" rows="2" placeholder="Đọc phần 1 và 2 của liên kết trước khi làm quiz..." class="login-input !py-1.5 text-xs"></textarea>
                        </div>
                    </div>

                    {{-- 11. TEXT PAGE --}}
                    <div x-show="drawerActivity.type === 'text_page'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Nội dung văn bản (Content Body):</label>
                            <textarea x-model="drawerActivity.content.body" rows="6" placeholder="Soạn nội dung bài đọc, kiến thức, lưu ý..." class="login-input !py-2 text-xs font-sans"></textarea>
                        </div>
                    </div>

                    {{-- 12. ASSIGNMENT --}}
                    <div x-show="drawerActivity.type === 'assignment'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Yêu cầu bài tập nộp bài (Instructions):</label>
                            <textarea x-model="drawerActivity.content.instructions" rows="3" placeholder="Học viên tải file bài làm PDF/Word lên đây trước ngày deadline..." class="login-input !py-2 text-xs"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Định dạng file cho phép:</label>
                                <input type="text" x-model="drawerActivity.content.allowed_extensions" placeholder="pdf,docx,zip" class="login-input !py-2 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Dung lượng tối đa (MB):</label>
                                <input type="number" x-model="drawerActivity.content.max_file_size_mb" min="1" max="100" class="login-input !py-2 text-xs font-mono">
                            </div>
                        </div>
                    </div>

                    {{-- 13. LABEL / HEADER --}}
                    <div x-show="drawerActivity.type === 'label'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Nội dung Nhãn phân cách:</label>
                            <input type="text" x-model="drawerActivity.content.text" placeholder="VD: --- PHẦN 2: BÀI TẬP VẬN DỤNG NÂNG CAO ---" class="login-input !py-2 text-xs">
                        </div>
                    </div>

                    {{-- 14. FORUM --}}
                    <div x-show="drawerActivity.type === 'forum'" class="space-y-3">
                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-300 mb-1">Chủ đề thảo luận:</label>
                            <input type="text" x-model="drawerActivity.content.topic" placeholder="VD: Chia sẻ phương pháp ghi nhớ từ vựng hiệu quả" class="login-input !py-2 text-xs">
                        </div>
                    </div>
                </div>

                {{-- TAB C: TIMING & AVAILABILITY --}}
                <div x-show="drawerTab === 'timing'" class="space-y-4">
                    <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-4">
                        <h4 class="text-xs font-bold text-white flex items-center gap-2">
                            <span>⏰ Thời gian mở & Hạn chót nộp bài (Availability Window)</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-400 mb-1">Mở từ ngày & giờ (Available From):</label>
                                <input type="datetime-local" x-model="drawerActivity.available_from" class="login-input !py-2 text-xs font-mono">
                                <p class="text-[10px] text-gray-500 mt-1">Để trống nếu mở ngay lập tức.</p>
                            </div>

                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-400 mb-1">Hạn chót đóng (Available Until):</label>
                                <input type="datetime-local" x-model="drawerActivity.available_until" class="login-input !py-2 text-xs font-mono">
                                <p class="text-[10px] text-gray-500 mt-1">Để trống nếu không giới hạn thời gian kết thúc.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-4">
                        <h4 class="text-xs font-bold text-white flex items-center gap-2">
                            <span>⏱️ Giới hạn thời gian làm bài & Số lần thử</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-400 mb-1">Giới hạn thời gian (phút):</label>
                                <input type="number" x-model="drawerActivity.time_limit_minutes" placeholder="Không giới hạn" min="1" max="600" class="login-input !py-2 text-xs font-mono">
                                <p class="text-[10px] text-gray-500 mt-1">Áp dụng cho Quiz hoặc bài kiểm tra tính giờ.</p>
                            </div>

                            <div>
                                <label class="block text-[11px] uppercase font-bold text-gray-400 mb-1">Số lần làm tối đa (Max attempts):</label>
                                <input type="number" x-model="drawerActivity.max_attempts" placeholder="Không giới hạn" min="1" max="99" class="login-input !py-2 text-xs font-mono">
                                <p class="text-[10px] text-gray-500 mt-1">Số lần học viên được phép làm lại bài.</p>
                            </div>

                            <div x-show="drawerActivity.type === 'quiz'">
                                <label class="block text-[11px] uppercase font-bold text-gray-400 mb-1">Cách tính điểm (Grading method):</label>
                                <select x-model="drawerActivity.grading_method" class="login-input !py-2 text-xs font-mono">
                                    <option value="highest">Điểm cao nhất (Highest)</option>
                                    <option value="last">Lần làm cuối (Last attempt)</option>
                                    <option value="average">Điểm trung bình (Average)</option>
                                    <option value="first">Lần làm đầu tiên (First)</option>
                                </select>
                                <p class="text-[10px] text-gray-500 mt-1">Quy tắc tính điểm tổng hợp Moodle.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB D: COMPLETION TRACKING (MOODLE CRITERIA) --}}
                <div x-show="drawerTab === 'completion'" class="space-y-4">
                    <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-4">
                        <h4 class="text-xs font-bold text-white flex items-center gap-2">
                            <span>🎯 Theo dõi Hoàn thành Hoạt động (Activity Completion Tracking)</span>
                        </h4>

                        <div>
                            <label class="block text-[11px] uppercase font-bold text-gray-400 mb-2">Phương thức xác định hoàn thành:</label>
                            <div class="space-y-2 text-xs">
                                <label class="p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all"
                                       :class="drawerActivity.completion_type === 'manual' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="completion_type" value="manual" x-model="drawerActivity.completion_type" class="hidden">
                                    <span class="text-lg">✋</span>
                                    <div>
                                        <div class="font-bold">Học viên tự đánh dấu hoàn thành (Manual)</div>
                                        <div class="text-[10px] text-gray-400">Học viên tự click vào ô đánh dấu khi đã học xong mục này.</div>
                                    </div>
                                </label>

                                <label class="p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all"
                                       :class="drawerActivity.completion_type === 'auto_view' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-gray-400 hover:border-slate-700'">
                                    <input type="radio" name="completion_type" value="auto_view" x-model="drawerActivity.completion_type" class="hidden">
                                    <span class="text-lg">👁️</span>
                                    <div>
                                        <div class="font-bold">Tự động hoàn thành khi xem (Must View)</div>
                                        <div class="text-[10px] text-gray-400">Hệ thống tự động ghi nhận khi học viên truy cập vào xem học liệu.</div>
                                    </div>
                                </label>

                                {{-- 3. Grade Required (Chỉ hiển thị với các hoạt động có chấm điểm: Quiz, Assignment, AI Speaking, AI Writing) --}}
                                <template x-if="['quiz', 'assignment', 'ai_speaking', 'ai_writing'].includes(drawerActivity.type)">
                                    <label class="p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all"
                                           :class="drawerActivity.completion_type === 'auto_grade' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-gray-400 hover:border-slate-700'">
                                        <input type="radio" name="completion_type" value="auto_grade" x-model="drawerActivity.completion_type" class="hidden">
                                        <span class="text-lg">🎯</span>
                                        <div>
                                            <div class="font-bold">Yêu cầu đạt điểm tối thiểu (Grade Required)</div>
                                            <div class="text-[10px] text-gray-400">Chỉ hoàn thành khi học viên làm bài đạt ngưỡng điểm yêu cầu.</div>
                                        </div>
                                    </label>
                                </template>

                                {{-- 4. Must Submit (Chỉ hiển thị với các hoạt động nộp bài: Assignment, AI Writing) --}}
                                <template x-if="['assignment', 'ai_writing'].includes(drawerActivity.type)">
                                    <label class="p-3 rounded-xl border flex items-center gap-3 cursor-pointer transition-all"
                                           :class="drawerActivity.completion_type === 'auto_submit' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-gray-400 hover:border-slate-700'">
                                        <input type="radio" name="completion_type" value="auto_submit" x-model="drawerActivity.completion_type" class="hidden">
                                        <span class="text-lg">📤</span>
                                        <div>
                                            <div class="font-bold">Yêu cầu nộp bài (Must Submit)</div>
                                            <div class="text-[10px] text-gray-400">Hoàn thành khi học viên đã tải tệp tin bài tập lên hệ thống.</div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Passing Grade Threshold (chỉ khi auto_grade và activity hỗ trợ chấm điểm) --}}
                        <div x-show="drawerActivity.completion_type === 'auto_grade' && ['quiz', 'assignment', 'ai_speaking', 'ai_writing'].includes(drawerActivity.type)" class="pt-2">
                            <label class="block text-[11px] uppercase font-bold text-emerald-400 mb-1.5">Ngưỡng điểm đạt yêu cầu (%):</label>
                            <input type="number" x-model="drawerActivity.passing_grade" placeholder="50" min="0" max="100" class="login-input !py-2 text-xs font-mono w-40">
                            <p class="text-[10px] text-gray-500 mt-1">Học viên phải đạt từ ngưỡng điểm này trở lên để được mở bài tiếp theo.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Drawer Footer Actions --}}
            <div class="p-4 border-t border-slate-800 bg-slate-900/90 flex items-center justify-between">
                <button type="button" @click="showDrawer = false" class="px-4 py-2 rounded-xl bg-slate-800 text-xs font-semibold text-gray-300 hover:text-white">
                    Đóng
                </button>
                <button type="button" @click="saveActivityDrawer()" class="btn-primary !w-auto !py-2 px-6 text-xs font-bold shadow-glow-blue flex items-center gap-2">
                    <span x-show="savingActivity" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span x-text="drawerMode === 'create' ? 'Tạo Hoạt động' : 'Lưu Thay Đổi'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SORTABLE.JS CDN & ALPINE.JS LMS ENGINE SCRIPT                       --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function curriculumStudioApp() {
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = '{{ session('active_tab') }}' || urlParams.get('tab') || '{{ request('tab', 'curriculum') }}';
    return {
        currentTab: ['curriculum', 'students', 'question_bank'].includes(initialTab) ? initialTab : 'curriculum',
        setTab(tab) {
            this.currentTab = tab;
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },
        expandAllLessons: null,
        
        // Modal states
        showAddLessonModal: false,
        showEditLessonModal: false,
        showPaletteModal: false,
        showDrawer: false,

        // Selection context
        targetLessonId: null,
        targetLessonTitle: '',
        
        // Drawer state
        drawerMode: 'create', // 'create' or 'edit'
        drawerTab: 'general', // 'general', 'content', 'timing', 'completion'
        savingActivity: false,
        uploading: false,

        // Drawer Activity Model
        drawerActivity: {
            id: null,
            lesson_id: null,
            title: '',
            description: '',
            type: 'file',
            order: 1,
            estimated_minutes: 10,
            is_visible: true,
            available_from: null,
            available_until: null,
            completion_type: 'manual',
            passing_grade: 60,
            max_attempts: null,
            grading_method: 'highest',
            time_limit_minutes: null,
            file_id: null,
            file_size: null,
            file_original_name: null,
            content: {},
            typeInfo: {}
        },

        // Edit Lesson Model
        editLessonData: {
            id: null,
            title: '',
            description: '',
            order: 1,
            estimated_minutes: 20,
            unlock_condition_score: 60,
            is_free_trial: false
        },

        // Temporary arrays for content builders
        drawerVocabList: [],
        drawerQuizQuestions: [],
        drawerGrammarRules: '',

        // Course Question Bank state
        courseQuestions: @json($courseQuestions),
        globalQuestions: @json($globalQuestions),
        questionSearch: '',
        questionSkillFilter: 'all',
        questionDifficultyFilter: 'all',
        
        // Add/Edit Question Modal
        showQuestionModal: false,
        questionModalMode: 'create', // 'create' or 'edit'
        savingQuestion: false,
        editingQuestion: {
            id: null,
            skill: 'vocabulary',
            difficulty: 'A1',
            question_type: 'mcq',
            question_text: '',
            optionsText: 'Đáp án A, Đáp án B, Đáp án C, Đáp án D',
            answer: 0,
            explanation: ''
        },

        // Import Global Modal
        showImportModal: false,
        importSearch: '',
        importSkillFilter: 'all',
        importDifficultyFilter: 'all',
        selectedImportIds: [],
        importing: false,

        // Quiz Drawer Builder Source Modes
        quizSourceMode: 'inline', // 'inline', 'bank_manual', 'bank_random'
        quizSelectedQuestionIds: [],
        quizRandomCount: 10,
        quizSkillFilter: 'all',
        quizDifficultyFilter: 'all',
        quizBankSearch: '',
        quizBankSkillFilter: 'all',
        quizBankDifficultyFilter: 'all',

        get filteredCourseQuestions() {
            return this.courseQuestions.filter(q => {
                const matchSkill = this.questionSkillFilter === 'all' || q.skill === this.questionSkillFilter;
                const matchDiff = this.questionDifficultyFilter === 'all' || q.difficulty === this.questionDifficultyFilter;
                const matchSearch = !this.questionSearch || q.question_text.toLowerCase().includes(this.questionSearch.toLowerCase());
                return matchSkill && matchDiff && matchSearch;
            });
        },

        get filteredGlobalQuestions() {
            return this.globalQuestions.filter(q => {
                const matchSkill = this.importSkillFilter === 'all' || q.skill === this.importSkillFilter;
                const matchDiff = this.importDifficultyFilter === 'all' || q.difficulty === this.importDifficultyFilter;
                const matchSearch = !this.importSearch || q.question_text.toLowerCase().includes(this.importSearch.toLowerCase());
                return matchSkill && matchDiff && matchSearch;
            });
        },

        get filteredQuizBankQuestions() {
            return this.courseQuestions.filter(q => {
                const matchSkill = this.quizBankSkillFilter === 'all' || q.skill === this.quizBankSkillFilter;
                const matchDiff = this.quizBankDifficultyFilter === 'all' || q.difficulty === this.quizBankDifficultyFilter;
                const matchSearch = !this.quizBankSearch || q.question_text.toLowerCase().includes(this.quizBankSearch.toLowerCase());
                return matchSkill && matchDiff && matchSearch;
            });
        },

        get quizRandomPoolCount() {
            return this.courseQuestions.filter(q => {
                const matchSkill = this.quizSkillFilter === 'all' || q.skill === this.quizSkillFilter;
                const matchDiff = this.quizDifficultyFilter === 'all' || q.difficulty === this.quizDifficultyFilter;
                return matchSkill && matchDiff;
            }).length;
        },

        init() {
            this.$nextTick(() => {
                this.initSortable();
            });
        },

        initSortable() {
            const courseId = {{ $course->id }};
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // 1. Sortable for Lessons
            const lessonsContainer = document.getElementById('lessons-sortable');
            if (lessonsContainer) {
                new Sortable(lessonsContainer, {
                    handle: '.lesson-handle',
                    animation: 200,
                    ghostClass: 'opacity-40',
                    onEnd: (evt) => {
                        const items = [];
                        lessonsContainer.querySelectorAll('.lesson-item').forEach((el, index) => {
                            items.push({
                                id: parseInt(el.getAttribute('data-lesson-id')),
                                order: index + 1
                            });
                        });

                        fetch(`/admin/courses/${courseId}/reorder-lessons`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ items })
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                // Update numbers in DOM
                                items.forEach(item => {
                                    const el = document.querySelector(`[data-lesson-id="${item.id}"] .lesson-handle + span`);
                                    if (el) el.textContent = item.order;
                                });
                            }
                        }).catch(console.error);
                    }
                });
            }

            // 2. Sortable for Activities within and between Lessons
            document.querySelectorAll('.activities-sortable').forEach(container => {
                new Sortable(container, {
                    group: 'activities-group',
                    handle: '.activity-handle',
                    animation: 200,
                    ghostClass: 'opacity-40',
                    onEnd: (evt) => {
                        const activityId = parseInt(evt.item.getAttribute('data-activity-id'));
                        const oldLessonId = parseInt(evt.from.getAttribute('data-lesson-id'));
                        const newLessonId = parseInt(evt.to.getAttribute('data-lesson-id'));

                        if (oldLessonId !== newLessonId) {
                            // Moved to another lesson
                            const newIndex = evt.newIndex + 1;
                            fetch(`/admin/activities/${activityId}/move`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ target_lesson_id: newLessonId, order: newIndex })
                            }).then(r => r.json()).then(data => {
                                evt.item.setAttribute('data-lesson-id', newLessonId);
                            }).catch(console.error);
                        } else {
                            // Reordered in same lesson
                            const items = [];
                            evt.to.querySelectorAll('.activity-card').forEach((el, idx) => {
                                items.push({
                                    id: parseInt(el.getAttribute('data-activity-id')),
                                    order: idx + 1
                                });
                            });

                            fetch(`/admin/lessons/${newLessonId}/reorder-activities`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ items })
                            }).catch(console.error);
                        }
                    }
                });
            });
        },

        openAddLessonModal() {
            this.showAddLessonModal = true;
        },

        openEditLessonModal(lesson) {
            this.editLessonData = {
                id: lesson.id,
                title: lesson.title,
                description: lesson.description || '',
                order: lesson.order,
                estimated_minutes: lesson.estimated_minutes,
                unlock_condition_score: lesson.unlock_condition_score,
                is_free_trial: !!lesson.is_free_trial
            };
            this.showEditLessonModal = true;
        },

        openActivityPalette(lessonId, lessonTitle) {
            this.targetLessonId = lessonId;
            this.targetLessonTitle = lessonTitle;
            this.showPaletteModal = true;
        },

        selectTypeAndOpenDrawer(type) {
            this.showPaletteModal = false;
            this.drawerMode = 'create';
            this.drawerTab = 'general';

            this.drawerActivity = {
                id: null,
                lesson_id: this.targetLessonId,
                title: '',
                description: '',
                type: type,
                order: 1,
                estimated_minutes: 10,
                is_visible: true,
                is_free_trial: false,
                available_from: null,
                available_until: null,
                completion_type: 'manual',
                passing_grade: 60,
                max_attempts: null,
                grading_method: 'highest',
                time_limit_minutes: null,
                file_id: null,
                file_size: null,
                file_original_name: null,
                content: {},
                typeInfo: {
                    file: { icon: '📁', label: 'Tệp tin' },
                    quiz: { icon: '🎯', label: 'Bài kiểm tra / Quiz' },
                    vocabulary: { icon: '📖', label: 'Từ vựng' },
                    grammar: { icon: '📐', label: 'Ngữ pháp' },
                    video: { icon: '🎬', label: 'Video' },
                    audio_listening: { icon: '🎧', label: 'Audio Podcast' },
                    pdf_document: { icon: '📑', label: 'Tài liệu / Slide' },
                    text_page: { icon: '📝', label: 'Trang nội dung' },
                    url: { icon: '🔗', label: 'Link URL' },
                    ai_speaking: { icon: '🎙️', label: 'AI Speaking' },
                    ai_writing: { icon: '✍️', label: 'AI Writing' },
                    assignment: { icon: '📋', label: 'Bài tập' },
                    forum: { icon: '💬', label: 'Diễn đàn' },
                    label: { icon: '🏷️', label: 'Nhãn phân cách' },
                }[type] || { icon: '📄', label: type }
            };

            this.drawerVocabList = [
                { word: '', meaning: '', phonetic: '', example: '' },
                { word: '', meaning: '', phonetic: '', example: '' }
            ];
            this.drawerQuizQuestions = [
                { question: '', optionsText: 'A, B, C, D', answer: 0 }
            ];
            this.quizSourceMode = 'inline';
            this.quizSelectedQuestionIds = [];
            this.quizRandomCount = 10;
            this.quizSkillFilter = 'all';
            this.quizDifficultyFilter = 'all';
            this.drawerGrammarRules = '';

            this.showDrawer = true;
        },

        openActivitySettings(activity) {
            this.drawerMode = 'edit';
            this.drawerTab = 'general';
            this.targetLessonId = activity.lesson_id;

            // Find lesson title
            const lessonEl = document.querySelector(`[data-lesson-id="${activity.lesson_id}"] h3`);
            this.targetLessonTitle = lessonEl ? lessonEl.textContent.trim() : '';

            // Format datetime-local if exists
            const fmtDate = (d) => d ? d.replace(' ', 'T').substring(0, 16) : null;

            this.drawerActivity = {
                id: activity.id,
                lesson_id: activity.lesson_id,
                title: activity.title,
                description: activity.description || '',
                type: activity.type,
                order: activity.order,
                estimated_minutes: activity.estimated_minutes,
                is_visible: !!activity.is_visible,
                is_free_trial: !!activity.is_free_trial,
                available_from: fmtDate(activity.available_from),
                available_until: fmtDate(activity.available_until),
                completion_type: activity.completion_type || 'manual',
                passing_grade: activity.passing_grade || 60,
                max_attempts: activity.max_attempts,
                grading_method: activity.grading_method || 'highest',
                time_limit_minutes: activity.time_limit_minutes,
                file_id: activity.file_id,
                file_size: activity.file ? activity.file.size : null,
                file_original_name: activity.file ? activity.file.original_name : null,
                content: activity.content || {},
                typeInfo: {}
            };

            if (['video', 'audio_listening', 'pdf_document'].includes(activity.type)) {
                if (!this.drawerActivity.content.source) {
                    this.drawerActivity.content.source = activity.file_id ? 'upload' : 'url';
                }
            }

            // Parse vocab
            if (activity.type === 'vocabulary' && Array.isArray(activity.content)) {
                this.drawerVocabList = activity.content.map(w => ({...w}));
            } else {
                this.drawerVocabList = [{ word: '', meaning: '', phonetic: '', example: '' }];
            }

            // Parse quiz
            if (activity.type === 'quiz' && activity.content) {
                this.quizSourceMode = activity.content.source_mode || 'inline';
                this.quizSelectedQuestionIds = Array.isArray(activity.content.question_ids) ? [...activity.content.question_ids] : [];
                this.quizRandomCount = activity.content.random_count || 10;
                this.quizSkillFilter = activity.content.skill_filter || 'all';
                this.quizDifficultyFilter = activity.content.difficulty_filter || 'all';

                if (Array.isArray(activity.content.questions)) {
                    this.drawerQuizQuestions = activity.content.questions.map(q => ({
                        question: q.question,
                        optionsText: Array.isArray(q.options) ? q.options.join(', ') : '',
                        answer: q.answer
                    }));
                } else {
                    this.drawerQuizQuestions = [{ question: '', optionsText: 'A, B, C, D', answer: 0 }];
                }
            } else {
                this.quizSourceMode = 'inline';
                this.quizSelectedQuestionIds = [];
                this.quizRandomCount = 10;
                this.quizSkillFilter = 'all';
                this.quizDifficultyFilter = 'all';
                this.drawerQuizQuestions = [{ question: '', optionsText: 'A, B, C, D', answer: 0 }];
            }

            // Parse grammar
            if (activity.type === 'grammar' && activity.content && Array.isArray(activity.content.rules)) {
                this.drawerGrammarRules = activity.content.rules.join('\n');
            } else {
                this.drawerGrammarRules = '';
            }

            this.showDrawer = true;
        },

        addVocabWord() {
            this.drawerVocabList.push({ word: '', meaning: '', phonetic: '', example: '' });
        },

        addQuizQuestion() {
            this.drawerQuizQuestions.push({ question: '', optionsText: 'A, B, C, D', answer: 0 });
        },

        handleFileDrop(e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.uploadFile(files[0]);
            }
        },

        handleFileSelect(e) {
            const files = e.target.files;
            if (files.length > 0) {
                this.uploadFile(files[0]);
            }
        },

        handleMediaDrop(e, mediaType) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.uploadFile(files[0]);
            }
        },

        handleMediaSelect(e, mediaType) {
            const files = e.target.files;
            if (files.length > 0) {
                this.uploadFile(files[0]);
            }
        },

        uploadFile(file) {
            this.uploading = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const formData = new FormData();
            formData.append('file', file);

            fetch('/admin/activities/upload-file', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                this.uploading = false;
                if (data.success) {
                    this.drawerActivity.file_id = data.file_id;
                    this.drawerActivity.file_size = data.file_size;
                    this.drawerActivity.file_original_name = data.file_original_name;
                    if (!this.drawerActivity.title) {
                        this.drawerActivity.title = data.file_original_name;
                    }
                } else {
                    alert('Lỗi tải tệp: ' + (data.message || 'Không thể upload'));
                }
            })
            .catch(err => {
                this.uploading = false;
                alert('Có lỗi khi tải tệp lên.');
                console.error(err);
            });
        },

        formatFileSize(bytes) {
            if (!bytes) return '';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return bytes + ' B';
        },

        saveActivityDrawer() {
            if (!this.drawerActivity.title) {
                this.drawerTab = 'general';
                alert('Vui lòng nhập tiêu đề hoạt động.');
                return;
            }

            this.savingActivity = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Format content by type
            let finalContent = { ...this.drawerActivity.content };

            if (this.drawerActivity.type === 'vocabulary') {
                finalContent = this.drawerVocabList.filter(w => w.word.trim() !== '');
            } else if (this.drawerActivity.type === 'quiz') {
                finalContent = {
                    source_mode: this.quizSourceMode,
                    question_ids: this.quizSelectedQuestionIds,
                    random_count: this.quizRandomCount,
                    skill_filter: this.quizSkillFilter,
                    difficulty_filter: this.quizDifficultyFilter,
                    questions: this.quizSourceMode === 'inline'
                        ? this.drawerQuizQuestions.map(q => ({
                            question: q.question,
                            options: q.optionsText.split(',').map(s => s.trim()).filter(Boolean),
                            answer: q.answer
                        }))
                        : []
                };
            } else if (this.drawerActivity.type === 'grammar') {
                finalContent.rules = this.drawerGrammarRules.split('\n').map(s => s.trim()).filter(Boolean);
            }

            const payload = {
                title: this.drawerActivity.title,
                description: this.drawerActivity.description,
                type: this.drawerActivity.type,
                order: this.drawerActivity.order,
                estimated_minutes: this.drawerActivity.estimated_minutes,
                is_visible: this.drawerActivity.is_visible,
                is_free_trial: this.drawerActivity.is_free_trial,
                available_from: this.drawerActivity.available_from,
                available_until: this.drawerActivity.available_until,
                completion_type: this.drawerActivity.completion_type,
                passing_grade: this.drawerActivity.passing_grade,
                max_attempts: this.drawerActivity.max_attempts,
                grading_method: this.drawerActivity.grading_method || 'highest',
                time_limit_minutes: this.drawerActivity.time_limit_minutes,
                file_id: this.drawerActivity.file_id,
                content: finalContent
            };

            const url = this.drawerMode === 'create'
                ? `/admin/lessons/${this.drawerActivity.lesson_id}/activities`
                : `/admin/activities/${this.drawerActivity.id}`;

            const method = this.drawerMode === 'create' ? 'POST' : 'PUT';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                this.savingActivity = false;
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Lỗi lưu hoạt động: ' + (data.message || 'Kiểm tra lại dữ liệu'));
                }
            })
            .catch(err => {
                this.savingActivity = false;
                alert('Có lỗi khi lưu hoạt động.');
                console.error(err);
            });
        },

        toggleActivityVisibility(activityId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch(`/admin/activities/${activityId}/toggle-visibility`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        toggleActivityTrial(activityId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch(`/admin/activities/${activityId}/toggle-trial`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        toggleLessonVisibility(lessonId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch(`/admin/lessons/${lessonId}/toggle-visibility`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        toggleLessonTrial(lessonId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch(`/admin/lessons/${lessonId}/toggle-trial`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        duplicateActivity(activityId) {
            if (!confirm('Nhân bản hoạt động này trong cùng bài học?')) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch(`/admin/activities/${activityId}/duplicate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        },

        // ── COURSE QUESTION BANK METHODS ──
        openAddQuestionModal() {
            this.questionModalMode = 'create';
            this.editingQuestion = {
                id: null,
                skill: 'vocabulary',
                difficulty: 'A1',
                question_type: 'mcq',
                question_text: '',
                audio_url: '',
                optionsText: "Lựa chọn A\nLựa chọn B\nLựa chọn C\nLựa chọn D",
                correct_answer: '',
                correct_answers_multi: '',
                word_tiles: '',
                matching_left: '',
                matching_right: '',
                true_false_answer: 'True',
                explanation: ''
            };
            this.showQuestionModal = true;
        },

        openEditQuestionModal(q) {
            this.questionModalMode = 'edit';
            const opts = Array.isArray(q.options) ? q.options : [];
            let optionsText = opts.join('\n');
            let matchingLeft = '';
            let matchingRight = '';

            if (q.question_type === 'matching' && q.options && typeof q.options === 'object') {
                if (Array.isArray(q.options.right)) {
                    matchingRight = q.options.right.join('\n');
                }
                if (q.options.left && typeof q.options.left === 'object') {
                    matchingLeft = Object.keys(q.options.left).join('\n');
                }
            }

            let multiAnswers = '';
            if (q.question_type === 'multiple_select' && q.correct_answer) {
                try {
                    const parsed = JSON.parse(q.correct_answer);
                    if (Array.isArray(parsed)) multiAnswers = parsed.join('\n');
                } catch(e) {
                    multiAnswers = q.correct_answer;
                }
            }

            this.editingQuestion = {
                id: q.id,
                skill: q.skill || 'vocabulary',
                difficulty: q.difficulty || 'A1',
                question_type: q.question_type || 'mcq',
                question_text: q.question_text,
                audio_url: q.audio_url || '',
                optionsText: optionsText,
                correct_answer: q.correct_answer || '',
                correct_answers_multi: multiAnswers,
                word_tiles: (q.question_type === 'word_ordering' && Array.isArray(q.options)) ? q.options.join(', ') : '',
                matching_left: matchingLeft,
                matching_right: matchingRight,
                true_false_answer: q.correct_answer || 'True',
                explanation: q.explanation || ''
            };
            this.showQuestionModal = true;
        },

        saveQuestionModal() {
            if (!this.editingQuestion.question_text.trim()) {
                alert('Vui lòng nhập nội dung câu hỏi.');
                return;
            }
            this.savingQuestion = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const isEdit = this.questionModalMode === 'edit';
            const url = isEdit
                ? `/admin/courses/{{ $course->id }}/questions/${this.editingQuestion.id}`
                : `/admin/courses/{{ $course->id }}/questions`;
            const method = isEdit ? 'PUT' : 'POST';

            const payload = {
                skill: this.editingQuestion.skill,
                difficulty: this.editingQuestion.difficulty,
                question_type: this.editingQuestion.question_type,
                question_text: this.editingQuestion.question_text,
                explanation: this.editingQuestion.explanation,
                audio_url: this.editingQuestion.audio_url,
                options: this.editingQuestion.optionsText,
                correct_answer: this.editingQuestion.correct_answer,
                correct_answers_multi: this.editingQuestion.correct_answers_multi,
                word_tiles: this.editingQuestion.word_tiles,
                matching_left: this.editingQuestion.matching_left,
                matching_right: this.editingQuestion.matching_right,
                true_false_answer: this.editingQuestion.true_false_answer
            };

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                this.savingQuestion = false;
                if (data.success) {
                    if (isEdit) {
                        const idx = this.courseQuestions.findIndex(q => q.id === data.question.id);
                        if (idx !== -1) this.courseQuestions[idx] = data.question;
                    } else {
                        this.courseQuestions.unshift(data.question);
                    }
                    this.showQuestionModal = false;
                } else {
                    alert(data.message || 'Có lỗi xảy ra.');
                }
            })
            .catch(err => {
                this.savingQuestion = false;
                alert('Có lỗi khi lưu câu hỏi.');
            });
        },

        deleteQuestion(questionId) {
            if (!confirm('Bạn có chắc chắn muốn xóa câu hỏi này khỏi ngân hàng khóa học?')) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/admin/courses/{{ $course->id }}/questions/${questionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.courseQuestions = this.courseQuestions.filter(q => q.id !== questionId);
                    this.quizSelectedQuestionIds = this.quizSelectedQuestionIds.filter(id => id !== questionId);
                } else {
                    alert(data.message || 'Không thể xóa câu hỏi.');
                }
            });
        },

        openImportModal() {
            this.selectedImportIds = [];
            this.showImportModal = true;
        },

        toggleImportSelectAll() {
            const filtered = this.filteredGlobalQuestions.map(q => q.id);
            if (this.selectedImportIds.length === filtered.length) {
                this.selectedImportIds = [];
            } else {
                this.selectedImportIds = [...filtered];
            }
        },

        importSelectedQuestions() {
            if (this.selectedImportIds.length === 0) {
                alert('Vui lòng chọn ít nhất một câu hỏi để nhập.');
                return;
            }
            this.importing = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(`/admin/courses/{{ $course->id }}/questions/import`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ question_ids: this.selectedImportIds })
            })
            .then(r => r.json())
            .then(data => {
                this.importing = false;
                if (data.success) {
                    if (Array.isArray(data.questions)) {
                        this.courseQuestions.unshift(...data.questions);
                    }
                    this.showImportModal = false;
                    alert(data.message);
                } else {
                    alert(data.message || 'Lỗi khi nhập câu hỏi.');
                }
            })
            .catch(err => {
                this.importing = false;
                alert('Lỗi khi nhập câu hỏi.');
            });
        },

        toggleQuizQuestionSelect(qId) {
            const idx = this.quizSelectedQuestionIds.indexOf(qId);
            if (idx === -1) {
                this.quizSelectedQuestionIds.push(qId);
            } else {
                this.quizSelectedQuestionIds.splice(idx, 1);
            }
        }
    };
}
</script>
@endsection

