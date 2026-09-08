@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto">
    {{-- Breadcrumb --}}
    <a href="{{ route('gradebook.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-fsel-teal mb-4 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Quay lại sổ điểm
    </a>

    @php $cf = $courseFormatted; @endphp

    {{-- Course Header with Grade --}}
    <div class="card-dark p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="bg-fsel-blue/20 text-fsel-blue text-xs font-bold px-2.5 py-1 rounded-full">{{ $course->level }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20">
                        {{ $cf['scale_icon'] }} {{ $course->getGradingScaleMeta()['short'] }}
                    </span>
                    <h2 class="text-xl font-bold text-white">{{ $course->title }}</h2>
                </div>
                @if($course->description)
                    <p class="text-sm text-gray-400">{{ $course->description }}</p>
                @endif
                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 flex-wrap">
                    <span>Ghi danh: {{ $enrollment->enrolled_at->format('d/m/Y') }}</span>
                    @if($enrollment->status === 'completed')
                        <span class="text-green-400">Hoàn thành: {{ $enrollment->completed_at->format('d/m/Y') }}</span>
                    @endif
                    <span>Điểm chuẩn qua môn: <strong class="text-gray-300">{{ $course->getPassingGradeLabel() }}</strong></span>
                </div>
            </div>

            {{-- Grade Circle --}}
            <div class="text-center flex-shrink-0 ml-4">
                <div class="w-28 h-28 rounded-full border-4 flex flex-col items-center justify-center
                    border-{{ $cf['badge_color'] }}-500">
                    <span class="text-2xl font-bold text-white leading-tight">{{ $cf['score_display'] }}</span>
                    @if($cf['score_suffix'])
                        <span class="text-[10px] text-gray-400 font-mono">{{ $cf['score_suffix'] }}</span>
                    @endif
                    <span class="text-xs font-semibold mt-0.5 text-{{ $cf['badge_color'] }}-400">{{ $cf['grade_letter'] }}</span>
                </div>
                <div class="mt-2">
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-{{ $cf['badge_color'] }}-500/15 text-{{ $cf['badge_color'] }}-400 border border-{{ $cf['badge_color'] }}-500/30">
                        {{ $cf['rank_label'] }}
                    </span>
                </div>
                @if($cf['is_passed'])
                    <span class="mt-1.5 inline-flex items-center gap-1 text-[10px] text-emerald-400">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        Đạt điểm chuẩn
                    </span>
                @else
                    <span class="mt-1.5 inline-flex items-center gap-1 text-[10px] text-red-400">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                        Chưa đạt điểm chuẩn
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Lesson Grades Detail --}}
    <h3 class="text-lg font-semibold text-white mb-4">Chi tiết điểm theo bài học</h3>

    <div class="space-y-3">
        @foreach($lessonGrades as $item)
            @php
                $lesson = $item['lesson'];
                $lf = $item['formatted'];
            @endphp
            <div class="card-dark p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                            {{ $item['is_completed'] ? 'bg-green-500/20 text-green-400' : 'bg-fsel-blue/20 text-fsel-blue' }}">
                            @if($item['is_completed'])
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                            @else
                                <span class="text-sm font-bold">{{ $lesson->order }}</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-white">{{ $lesson->title }}</h4>
                            <div class="flex items-center gap-4 mt-1 text-xs text-gray-500">
                                <span>{{ $item['completed_activities'] }}/{{ $item['total_activities'] }} hoạt động</span>
                                @if($item['time_spent'] > 0)
                                    <span>⏱ {{ gmdate('H:i:s', $item['time_spent']) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        {{-- Progress --}}
                        <div class="w-24">
                            <div class="w-full bg-fsel-navy rounded-full h-1.5">
                                @php $pct = $item['total_activities'] > 0 ? round(($item['completed_activities'] / $item['total_activities']) * 100) : 0; @endphp
                                <div class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-green-500' : 'bg-fsel-blue' }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        {{-- Grade --}}
                        <div class="text-right w-20">
                            <span class="text-lg font-bold text-{{ $lf['badge_color'] }}-400">
                                {{ $lf['score_display'] }}
                            </span>
                            @if($lf['score_suffix'])
                                <span class="text-[10px] text-gray-500">{{ $lf['score_suffix'] }}</span>
                            @endif
                            <div class="text-[10px] text-gray-500 font-mono">{{ $lf['grade_letter'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
