@extends('layouts.app')
@section('content')
<div class="w-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
        <h2 class="text-xl sm:text-2xl font-bold text-white">Khóa học của bạn</h2>
        <span class="text-xs sm:text-sm text-gray-400">Trình độ hiện tại: <span class="text-fsel-teal font-semibold font-mono">{{ $userLevel }}</span></span>
    </div>

    @if($courses->isEmpty())
        <div class="card-dark p-8 sm:p-12 text-center">
            <p class="text-gray-400">Chưa có khóa học nào được xuất bản.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
            @foreach($courses as $course)
                <a href="{{ route('courses.show', $course->id) }}" class="card-dark p-4 sm:p-6 hover:border-fsel-accent/50 transition-all duration-300 group cursor-pointer">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="bg-fsel-blue/20 text-fsel-blue text-xs font-bold px-2.5 py-1 rounded-full">{{ $course->level }}</span>
                            @if($course->allowsSelfEnrollment())
                                @if($course->requiresEnrollmentKey())
                                    <span class="bg-indigo-500/20 text-indigo-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-indigo-500/30 flex items-center gap-1">
                                        <span>🔑</span>
                                        <span>Cần mật khẩu</span>
                                    </span>
                                @else
                                    <span class="bg-green-500/20 text-green-400 text-[10px] font-bold px-2 py-0.5 rounded-full border border-green-500/30">
                                        🟢 Tự ghi danh
                                    </span>
                                @endif
                            @else
                                <span class="bg-amber-500/20 text-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-500/30 flex items-center gap-1">
                                    <span>🔒</span>
                                    <span>Ghi danh bởi Admin</span>
                                </span>
                            @endif
                        </div>
                        <svg class="w-5 h-5 text-gray-500 group-hover:text-fsel-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2 group-hover:text-fsel-accent transition-colors">{{ $course->title }}</h3>
                    <p class="text-sm text-gray-400 mb-4 line-clamp-2">{{ $course->description }}</p>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
                            {{ $course->lessons_count }} bài học
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $course->creator->name ?? 'ESL' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
