@extends('layouts.app')
@section('content')
<x-ai-tutor::widget :course-id="(string) $course->id" :lesson-id="(string) $lesson->id" />
<div class="w-full space-y-6">
    <a href="{{ route('courses.show', $course->id) }}" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white bg-slate-900/80 border border-slate-800 px-3.5 py-1.5 rounded-full transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <span class="truncate">{{ $course->title }}</span>
    </a>

    @if($isTrialMode ?? false)
        <div class="p-4 rounded-2xl bg-gradient-to-r from-teal-500/15 via-indigo-500/15 to-blue-500/15 border border-teal-500/30 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3">
                <span class="text-2xl sm:text-3xl">✨</span>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-teal-300">Chế độ học thử (Free Trial Preview)</h3>
                    <p class="text-[11px] sm:text-xs text-gray-300">Bạn đang xem thử bài học này. Hãy ghi danh để lưu kết quả và tính điểm XP!</p>
                </div>
            </div>
            <a href="{{ route('courses.show', $course->id) }}" class="bg-gradient-to-r from-fsel-blue to-indigo-600 hover:from-fsel-blue/90 hover:to-indigo-500 text-white text-xs font-bold px-4 py-2 rounded-xl transition-all shadow-md whitespace-nowrap self-stretch sm:self-auto text-center">
                Ghi danh ngay
            </a>
        </div>
    @endif

    {{-- LESSON HEADER CARD --}}
    <div class="card-dark p-4 sm:p-6 rounded-2xl sm:rounded-3xl border-slate-800/80 bg-gradient-to-br from-slate-900 via-slate-900/90 to-indigo-950/40 shadow-xl">
        <div class="space-y-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="bg-gradient-to-r from-fsel-blue to-indigo-600 text-white text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-lg shadow-sm">
                    Bài {{ sprintf('%02d', $lesson->order) }}
                </span>
                @if($lesson->is_free_trial)
                    <span class="text-[10px] font-bold text-teal-300 bg-teal-500/15 border border-teal-500/30 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <span>✨</span>
                        <span>Bài học thử</span>
                    </span>
                @endif
                @if($lesson->estimated_minutes)
                    <span class="text-[11px] text-gray-400 font-mono flex items-center gap-1 bg-slate-800/80 px-2 py-0.5 rounded-lg">
                        <span>⏱️</span>
                        <span>{{ $lesson->estimated_minutes }} phút</span>
                    </span>
                @endif
            </div>

            <h1 class="text-lg sm:text-2xl font-black text-white tracking-tight leading-snug">
                {{ $lesson->title }}
            </h1>

            @if($lesson->description)
                <p class="text-xs sm:text-sm text-gray-300 leading-relaxed">{{ $lesson->description }}</p>
            @endif

            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono">
                <span class="text-gray-400">
                    @if($isTrialMode ?? false)
                        ✨ {{ $trialActivitiesCount }}/{{ $totalActivities }} hoạt động học thử
                    @else
                        {{ $completedCount }}/{{ $totalActivities }} hoạt động hoàn thành
                    @endif
                </span>
                @if($isLessonCompleted && $totalActivities > 0)
                    <span class="text-emerald-400 font-bold flex items-center gap-1 bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20 text-[11px]">
                        <span>✓</span>
                        <span>Hoàn thành bài học</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ACTIVITIES LIST --}}
    <div class="space-y-2.5 sm:space-y-3">
        @if(($isTrialMode ?? false) && $trialActivitiesCount === 0 && $totalActivities > 0)
            <div class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-amber-500/10 border border-amber-500/25 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-300 flex items-center justify-center flex-shrink-0 text-base">
                        🔒
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs sm:text-sm font-bold text-amber-300">Bài học này chưa mở học thử</h4>
                        <p class="text-[11px] text-gray-400 mt-0.5">Bạn đang xem trước danh mục các hoạt động. Vui lòng ghi danh khóa học để mở khóa toàn bộ nội dung bài học này.</p>
                    </div>
                </div>
                <a href="{{ route('courses.show', $course->id) }}" class="flex-shrink-0 px-4 py-2 rounded-xl bg-gradient-to-r from-fsel-blue to-indigo-600 text-white font-bold text-xs shadow-md shadow-fsel-blue/20 hover:scale-105 transition-all">
                    Ghi danh ngay
                </a>
            </div>
        @elseif(($isTrialMode ?? false) && $totalActivities === 0)
            <div class="card-dark p-8 text-center rounded-2xl border border-slate-800 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-300 flex items-center justify-center mx-auto text-xl">
                    🔒
                </div>
                <h4 class="text-sm sm:text-base font-bold text-white">Bài học này chưa có hoạt động nào</h4>
                <p class="text-xs text-gray-400 max-w-md mx-auto">Vui lòng quay lại sau hoặc tham khảo các bài học khác.</p>
            </div>
        @endif

        @foreach($activities as $activity)
            @php
                $isActDone = in_array($activity->id, $completedActivityIds ?? []);
                $canTrial = (bool) $activity->is_free_trial;
                $isLocked = ($isTrialMode ?? false) && !$canTrial;
            @endphp
            @if($isLocked)
                {{-- Visible but unclickable locked activity --}}
                <div class="card-dark p-3.5 sm:p-4 flex items-center justify-between gap-3 rounded-xl sm:rounded-2xl border border-slate-800/80 bg-slate-900/40 opacity-60 cursor-not-allowed select-none"
                     title="Hoạt động này không mở học thử. Vui lòng ghi danh khóa học để tham gia.">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        {{-- Activity Type Icon (Locked) --}}
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm bg-slate-800/90 text-gray-400 border border-slate-700/60">
                            🔒
                        </div>

                        {{-- Activity Title & Status --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h4 class="text-xs sm:text-sm font-semibold text-gray-300 truncate">{{ $activity->title }}</h4>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-slate-800 text-gray-400 border border-slate-700">🔒 Cần ghi danh</span>
                            </div>
                            <span class="text-[10px] text-gray-500 capitalize font-mono block mt-0.5">{{ $activity->type }} · {{ $activity->estimated_minutes ?? 5 }}m</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span class="text-xs font-semibold px-3 py-1.5 rounded-xl bg-slate-800/80 text-gray-400 border border-slate-700/60 flex items-center gap-1">
                            <span>🔒</span>
                            <span>Khóa</span>
                        </span>
                    </div>
                </div>
            @else
                <a href="{{ route('activities.show', $activity->id) }}"
                   class="card-dark p-3.5 sm:p-4 flex items-center justify-between gap-3 transition-all group cursor-pointer rounded-xl sm:rounded-2xl border {{ $isActDone ? 'border-emerald-500/30 bg-emerald-500/5 hover:border-emerald-500/50' : 'border-slate-800/80 hover:border-fsel-blue/40' }}">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        {{-- Activity Type Icon --}}
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm
                            {{ $activity->type === 'vocabulary' ? 'bg-purple-500/20 text-purple-400 border border-purple-500/30' : '' }}
                            {{ $activity->type === 'grammar' ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : '' }}
                            {{ $activity->type === 'video' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : '' }}
                            {{ $activity->type === 'quiz' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : '' }}
                            {{ !in_array($activity->type, ['vocabulary','grammar','video','quiz']) ? 'bg-slate-800 text-gray-300 border border-slate-700' : '' }}">
                            @if($activity->type === 'vocabulary')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                            @elseif($activity->type === 'grammar')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @elseif($activity->type === 'video')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif($activity->type === 'quiz')
                                <span class="text-base">❓</span>
                            @elseif($activity->type === 'assignment')
                                <span class="text-base">📋</span>
                            @else
                                <span class="text-base">⚡</span>
                            @endif
                        </div>

                        {{-- Activity Title & Status --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h4 class="text-xs sm:text-sm font-bold text-white group-hover:text-fsel-accent transition-colors truncate">{{ $activity->title }}</h4>
                                @if($isTrialMode ?? false)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-teal-500/20 text-teal-300 border border-teal-500/30">✨ Học thử</span>
                                @elseif($isActDone)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">✓ Đã xong</span>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400 capitalize font-mono block mt-0.5">{{ $activity->type }} · {{ $activity->estimated_minutes ?? 5 }}m</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span class="text-xs font-bold px-3 py-1.5 rounded-xl {{ $isActDone ? 'bg-emerald-500/20 text-emerald-300' : 'bg-fsel-blue/20 text-fsel-blue group-hover:bg-fsel-blue/30' }} transition-colors">
                            {{ $isActDone ? 'Ôn tập' : 'Bắt đầu' }}
                        </span>
                        <svg class="w-4 h-4 text-gray-500 group-hover:text-fsel-accent transition-colors hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @endif
        @endforeach
    </div>

    {{-- NAVIGATION CONTROLS --}}
    <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between gap-3">
        @if($prevLesson)
            <a href="{{ route('lessons.show', $prevLesson->id) }}" class="inline-flex items-center gap-1.5 text-xs text-gray-300 hover:text-white transition-colors px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Bài trước</span>
            </a>
        @else
            <div></div>
        @endif

        <div>
            @if($nextLesson)
                @if($isLessonCompleted || $nextLesson->isUnlockedFor(auth()->user()))
                    <a href="{{ route('lessons.show', $nextLesson->id) }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-white px-4 sm:px-6 py-2.5 rounded-xl bg-gradient-to-r from-fsel-blue to-indigo-500 hover:from-fsel-blue/80 hover:to-indigo-400 transition-all shadow-md">
                        <span>Bài tiếp theo</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs text-gray-400 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800" title="Cần hoàn thành các hoạt động trước">
                        <span class="text-amber-400">🔒</span>
                        <span class="hidden sm:inline">Hoàn thành bài để mở tiếp (còn {{ $totalActivities - $completedCount }})</span>
                        <span class="sm:hidden">Còn {{ $totalActivities - $completedCount }} bài</span>
                    </span>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
