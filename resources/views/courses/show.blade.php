@extends('layouts.app')
@section('content')
<div class="w-full space-y-5 sm:space-y-6 min-w-0">
    <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white bg-slate-900/80 border border-slate-800 px-3.5 py-1.5 rounded-full transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <span>Danh sách khóa học</span>
    </a>

    {{-- COURSE HERO CARD --}}
    <div class="card-dark p-3.5 sm:p-7 rounded-2xl sm:rounded-3xl border-slate-800/80 relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-900/90 to-indigo-950/40 shadow-xl min-w-0 max-w-full">
        {{-- Level & Meta Badges --}}
        <div class="flex items-center gap-2 flex-wrap mb-3">
            <span class="bg-gradient-to-r from-fsel-blue to-indigo-600 text-white text-[11px] font-black px-2.5 py-1 rounded-lg font-mono flex items-center gap-1 shadow-sm">
                <span>🎯</span>
                <span>{{ $course->level }}</span>
            </span>
            <span class="bg-slate-800/80 text-gray-300 border border-slate-700/60 text-[11px] font-medium px-2.5 py-1 rounded-lg flex items-center gap-1 font-mono">
                <span>📚</span>
                <span>{{ $lessonsWithStatus->count() }} bài học</span>
            </span>
            @php $totalMins = $course->lessons->sum('estimated_minutes'); @endphp
            @if($totalMins > 0)
                <span class="bg-slate-800/80 text-gray-300 border border-slate-700/60 text-[11px] font-medium px-2.5 py-1 rounded-lg flex items-center gap-1 font-mono">
                    <span>⏱️</span>
                    <span>~{{ $totalMins }} phút</span>
                </span>
            @endif
        </div>

        {{-- Title --}}
        <h1 class="text-lg sm:text-2xl lg:text-3xl font-black text-white tracking-tight leading-snug mb-2.5 break-words">
            {{ $course->title }}
        </h1>

        {{-- Description --}}
        @if($course->description)
            <p class="text-xs sm:text-sm text-gray-300 leading-relaxed mb-4 break-words">
                {{ $course->description }}
            </p>
        @endif

        {{-- Enrollment Actions & Status Box --}}
        @php
            $user = auth()->user();
            $enrollment = $user->getEnrollment($course->id);
            $isEnrolled = $enrollment && in_array($enrollment->status, ['active', 'completed']);
        @endphp

        <div class="pt-4 border-t border-slate-800/80">
            @if($isEnrolled)
                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="bg-fsel-teal/20 text-fsel-teal border border-fsel-teal/30 text-xs font-bold px-3 py-1 rounded-lg flex items-center gap-1">
                                <span>✓</span>
                                <span>Đã ghi danh</span>
                            </span>
                            @if(isset($enrollment->role_meta))
                                <span class="font-bold px-2.5 py-1 rounded-lg border text-[11px] {{ $enrollment->role_meta['badge_class'] }}">
                                    {{ $enrollment->role_meta['icon'] }} {{ $enrollment->role_meta['name'] }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            @if(isset($enrollment->expiry_status))
                                <span class="font-mono text-[11px] text-gray-400">
                                    {{ $enrollment->expiry_status['icon'] }} {{ $enrollment->expiry_status['text'] }}
                                </span>
                            @endif
                            <form action="{{ route('courses.unenroll', $course->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy ghi danh?')">
                                @csrf
                                <button type="submit" class="text-[11px] text-gray-500 hover:text-red-400 transition-colors">Hủy ghi danh</button>
                            </form>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    @if($enrollment->status === 'active' && !$enrollment->isExpired())
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400">Tiến độ hoàn thành:</span>
                                <span class="font-mono font-bold text-teal-300">{{ $enrollment->progress_percentage }}%</span>
                            </div>
                            <div class="w-full bg-slate-950 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-fsel-blue via-indigo-500 to-teal-400 transition-all duration-500"
                                     style="width: {{ min($enrollment->progress_percentage, 100) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Not enrolled --}}
                @if($course->allowsSelfEnrollment())
                    @if($course->requiresEnrollmentKey())
                        {{-- Enrollment Key Form --}}
                        <form action="{{ route('courses.enroll', $course->id) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                            @csrf
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs text-gray-300 font-medium mb-1">Mật khẩu ghi danh (Enrollment Key):</label>
                                <input type="password" name="enrollment_key" required placeholder="Nhập mã khóa học..." class="login-input !py-2.5 text-xs w-full font-mono">
                            </div>
                            <button type="submit" class="bg-gradient-to-r from-fsel-blue to-indigo-600 hover:from-fsel-blue/90 hover:to-indigo-500 text-white text-xs font-bold px-6 py-2.5 rounded-xl transition-all shadow-lg shadow-fsel-blue/20 flex items-center justify-center gap-1.5 flex-shrink-0">
                                <span>⚡</span>
                                <span>Ghi danh học ngay</span>
                            </button>
                        </form>
                    @else
                        {{-- Open Self Enrollment --}}
                        <form action="{{ route('courses.enroll', $course->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-fsel-blue to-indigo-600 hover:from-fsel-blue/90 hover:to-indigo-500 text-white text-sm font-bold px-8 py-3 rounded-xl transition-all shadow-lg shadow-fsel-blue/20 flex items-center justify-center gap-2">
                                <span>⚡</span>
                                <span>Ghi danh học ngay (Miễn phí)</span>
                            </button>
                        </form>
                    @endif
                @else
                    {{-- Admin Enrollment Only Notice --}}
                    <div class="p-3.5 sm:p-4 rounded-xl bg-amber-500/10 border border-amber-500/25 flex items-start gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-300 flex items-center justify-center flex-shrink-0 text-base">
                            🔒
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs sm:text-sm font-bold text-amber-300">Ghi danh bởi Quản trị viên</h4>
                            <p class="text-[11px] text-gray-400 mt-0.5 leading-relaxed break-words">Khóa học này do Giảng viên / Quản trị viên phân bổ trực tiếp. Hãy liên hệ hotline hoặc bộ phận học vụ để được cấp quyền tham gia.</p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- LESSONS LIST --}}
    <div class="space-y-3 sm:space-y-4 min-w-0 max-w-full">
        <div class="flex items-center justify-between">
            <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                <span>📚</span>
                <span>Nội dung bài học</span>
            </h3>
            <span class="text-xs font-mono text-gray-400 bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-800">
                {{ $lessonsWithStatus->count() }} bài
            </span>
        </div>

        <div class="space-y-2.5 sm:space-y-3 min-w-0 max-w-full">
            @foreach($lessonsWithStatus as $item)
                @php
                    $lesson = $item['lesson'];
                    $unlocked = $item['unlocked'];
                    $completed = $item['completed'];
                    $score = $item['score'];
                    $isTrial = $item['is_trial'] ?? false;
                @endphp
                <div class="card-dark p-3 sm:p-4 flex items-center justify-between gap-2.5 sm:gap-3 rounded-xl sm:rounded-2xl border-slate-800/80 hover:border-slate-700 transition-all min-w-0 max-w-full overflow-hidden {{ ($isEnrolled && !$unlocked) ? 'opacity-60' : '' }}">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1 overflow-hidden">
                        {{-- Lesson Order Number Avatar --}}
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0 font-bold font-mono text-xs sm:text-sm shadow-sm
                            {{ $completed ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($unlocked ? 'bg-fsel-blue/20 text-fsel-blue border border-fsel-blue/30' : ($isTrial ? 'bg-teal-500/20 text-teal-300 border border-teal-500/30' : 'bg-slate-800 text-gray-400 border border-slate-700')) }}">
                            @if($completed)
                                <span>✓</span>
                            @elseif($unlocked || $isTrial)
                                <span>{{ sprintf('%02d', $lesson->order) }}</span>
                            @elseif(!$isEnrolled)
                                <span>{{ sprintf('%02d', $lesson->order) }}</span>
                            @else
                                <span>🔒</span>
                            @endif
                        </div>

                        {{-- Lesson Info --}}
                        <div class="min-w-0 flex-1 overflow-hidden">
                            <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                                @if($unlocked || !$isEnrolled)
                                    <a href="{{ route('lessons.show', $lesson->id) }}" class="text-xs sm:text-sm font-bold text-white hover:text-fsel-accent transition-colors truncate max-w-full leading-tight">
                                        {{ $lesson->title }}
                                    </a>
                                @else
                                    <h4 class="text-xs sm:text-sm font-bold text-white truncate max-w-full leading-tight">{{ $lesson->title }}</h4>
                                @endif
                                @if($isTrial)
                                    <span class="text-[9px] font-bold text-teal-300 bg-teal-500/15 border border-teal-500/30 px-1.5 py-0.2 rounded-full flex items-center gap-0.5 flex-shrink-0">
                                        <span>✨</span>
                                        <span>Học thử</span>
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] text-gray-400 font-mono flex-wrap">
                                @if($isEnrolled)
                                    <span>{{ $item['completed_activities'] }}/{{ $item['total_activities'] }} hoạt động</span>
                                @elseif($isTrial)
                                    <span>{{ $item['trial_activities'] }}/{{ $item['total_activities'] }} hoạt động học thử</span>
                                @else
                                    <span>{{ $item['total_activities'] }} hoạt động</span>
                                @endif
                                @if($lesson->estimated_minutes)
                                    <span>·</span>
                                    <span>{{ $lesson->estimated_minutes }}m</span>
                                @endif
                                @if($completed && $item['total_activities'] > 0)
                                    <span>·</span>
                                    <span class="text-emerald-400 font-bold">Xong</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Action CTA --}}
                    <div class="flex-shrink-0">
                        @if($isEnrolled)
                            @if($unlocked)
                                <a href="{{ route('lessons.show', $lesson->id) }}" class="bg-gradient-to-r from-fsel-blue to-indigo-600 hover:from-fsel-blue/80 hover:to-indigo-500 text-white text-[11px] sm:text-xs font-bold px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl transition-all shadow-sm flex items-center gap-1 whitespace-nowrap">
                                    <span>{{ $completed ? 'Ôn tập' : 'Vào học' }}</span>
                                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <span class="text-xs text-gray-500 bg-slate-900 border border-slate-800 px-2.5 py-1.5 rounded-lg flex items-center gap-1 font-mono">
                                    <span>🔒</span>
                                    <span class="hidden sm:inline">Chưa mở</span>
                                </span>
                            @endif
                        @else
                            @if($isTrial)
                                <a href="{{ route('lessons.show', $lesson->id) }}" class="bg-teal-500/20 hover:bg-teal-500/30 text-teal-300 border border-teal-500/40 text-[11px] sm:text-xs font-bold px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl transition-all flex items-center gap-1 shadow-sm whitespace-nowrap">
                                    <span>✨</span>
                                    <span>Học thử</span>
                                </a>
                            @else
                                <a href="{{ route('lessons.show', $lesson->id) }}" class="bg-slate-800 hover:bg-slate-700 text-gray-300 hover:text-white border border-slate-700 text-[11px] sm:text-xs font-semibold px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl transition-all flex items-center gap-1 shadow-sm whitespace-nowrap" title="Xem danh sách hoạt động">
                                    <span>🔒</span>
                                    <span>Xem nội dung</span>
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
