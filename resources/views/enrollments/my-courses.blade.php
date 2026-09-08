@extends('layouts.app')
@section('content')
<div class="w-full">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white">Khóa học của tôi</h2>
            <p class="text-sm text-gray-400 mt-1">Theo dõi tiến độ học tập của bạn</p>
        </div>
        <a href="{{ route('courses.index') }}" class="bg-fsel-blue hover:bg-fsel-blue/80 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Ghi danh khóa mới
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card-dark p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-fsel-blue/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-fsel-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $activeCount }}</p>
                    <p class="text-xs text-gray-400">Đang học</p>
                </div>
            </div>
        </div>
        <div class="card-dark p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $completedCount }}</p>
                    <p class="text-xs text-gray-400">Đã hoàn thành</p>
                </div>
            </div>
        </div>
        <div class="card-dark p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-fsel-purple/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-fsel-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $avgProgress }}%</p>
                    <p class="text-xs text-gray-400">Tiến độ TB</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Enrolled Courses List --}}
    @if($enrollments->isEmpty())
        <div class="card-dark p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-fsel-blue/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-fsel-blue/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Chưa ghi danh khóa học nào</h3>
            <p class="text-sm text-gray-400 mb-4">Hãy khám phá và ghi danh vào các khóa học để bắt đầu hành trình học tập!</p>
            <a href="{{ route('courses.index') }}" class="bg-fsel-blue hover:bg-fsel-blue/80 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors inline-flex items-center gap-2">
                Khám phá khóa học
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($enrollments as $enrollment)
                @php $course = $enrollment->course; @endphp
                <div class="card-dark p-5 hover:border-fsel-accent/30 transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="bg-fsel-blue/20 text-fsel-blue text-xs font-bold px-2 py-0.5 rounded-full">{{ $course->level }}</span>
                                @if($enrollment->status === 'completed')
                                    <span class="bg-green-500/20 text-green-400 text-xs font-medium px-2 py-0.5 rounded-full flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                        Hoàn thành
                                    </span>
                                @else
                                    <span class="bg-yellow-500/20 text-yellow-400 text-xs px-2 py-0.5 rounded-full">Đang học</span>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-white">{{ $course->title }}</h3>
                            <p class="text-sm text-gray-400 mt-1 line-clamp-1">{{ $course->description }}</p>
                        </div>
                        <div class="flex items-center gap-2 ml-4">
                            <a href="{{ route('courses.show', $course->id) }}" class="bg-fsel-blue/20 hover:bg-fsel-blue/30 text-fsel-blue text-xs font-semibold px-4 py-2 rounded-lg transition-colors">
                                Tiếp tục học
                            </a>
                            <a href="{{ route('gradebook.show', $course->id) }}" class="bg-fsel-purple/20 hover:bg-fsel-purple/30 text-fsel-purple text-xs font-semibold px-3 py-2 rounded-lg transition-colors">
                                Xem điểm
                            </a>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs text-gray-400">Tiến độ hoàn thành</span>
                            <span class="text-xs font-semibold {{ $enrollment->progress_percentage >= 100 ? 'text-green-400' : 'text-fsel-accent' }}">{{ $enrollment->progress_percentage }}%</span>
                        </div>
                        <div class="w-full bg-fsel-navy rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $enrollment->progress_percentage >= 100 ? 'bg-gradient-to-r from-green-500 to-emerald-400' : 'bg-gradient-to-r from-fsel-blue to-fsel-accent' }}"
                                 style="width: {{ min($enrollment->progress_percentage, 100) }}%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                            <span>{{ $course->lessons->count() }} bài học</span>
                            <span>Vai trò: <strong class="text-white font-mono">{{ $enrollment->role_meta['name'] }}</strong></span>
                            <span>Hạn: <strong class="{{ $enrollment->isExpired() ? 'text-red-400 font-bold' : ($enrollment->remainingDays() !== null && $enrollment->remainingDays() <= 7 ? 'text-amber-400 font-bold' : 'text-gray-300') }}">{{ $enrollment->expiry_status['text'] }}</strong></span>
                            @if($enrollment->final_grade)
                                <span class="text-fsel-gold font-semibold">Điểm: {{ number_format($enrollment->final_grade, 1) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
