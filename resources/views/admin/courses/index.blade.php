@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Quản lý khóa học</h1>
            <p class="text-xs text-gray-400">Thiết kế lộ trình đào tạo, quản lý chương trình bài giảng và tích hợp học liệu số đa phương tiện</p>
        </div>

        <a href="{{ route('admin.courses.create') }}" class="btn-primary !w-auto !py-2.5 px-5 text-xs font-semibold flex items-center gap-2 shadow-glow-blue">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Thêm khóa học mới</span>
        </a>
    </div>

    {{-- Level Filters --}}
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-400">Lọc theo cấp độ:</span>
        <a href="{{ route('admin.courses.index') }}"
           class="text-xs px-3 py-1 rounded-full transition-colors {{ !$level ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-800 text-gray-400 hover:text-white' }}">
            Tất cả ({{ $courses->count() }})
        </a>
        @foreach(['A1', 'A2', 'B1', 'B2', 'C1'] as $lvl)
            <a href="{{ route('admin.courses.index', ['level' => $lvl]) }}"
               class="text-xs px-3 py-1 rounded-full transition-colors {{ $level === $lvl ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-800 text-gray-400 hover:text-white' }}">
                {{ $lvl }}
            </a>
        @endforeach
    </div>

    {{-- Courses Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($courses as $c)
            @php
                $totalActivities = $c->lessons->sum(fn($l) => $l->activities->count());
                $totalMinutes = $c->lessons->sum('estimated_minutes');
            @endphp
            <div class="admin-card p-5 flex flex-col justify-between group hover:border-indigo-500/50 transition-all">
                <div class="space-y-3">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold font-mono px-2.5 py-0.5 rounded">
                                {{ $c->level }}
                            </span>
                            @if($c->allowsSelfEnrollment())
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                    🟢 Tự ghi danh
                                </span>
                            @else
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                    🔒 Thủ công
                                </span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.courses.togglePublish', $c->id) }}">
                            @csrf
                            <button type="submit" title="Bấm để Đổi trạng thái Ẩn / Xuất bản" class="text-[10px] font-bold px-2.5 py-1 rounded-lg transition-all flex items-center gap-1 {{ $c->is_published ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/25' : 'bg-slate-800 text-gray-400 border border-slate-700 hover:text-white' }}">
                                <span>{{ $c->is_published ? '👁️ Xuất bản' : '🕶️ Đang ẩn' }}</span>
                            </button>
                        </form>
                    </div>

                    <a href="{{ route('admin.courses.show', $c->id) }}" class="block">
                        <h3 class="text-base font-bold text-white group-hover:text-indigo-400 transition-colors line-clamp-1">
                            {{ $c->title }}
                        </h3>
                    </a>

                    <p class="text-xs text-gray-400 line-clamp-2 leading-relaxed">
                        {{ $c->description ?: 'Chưa có mô tả chi tiết cho khóa học này.' }}
                    </p>

                    <div class="flex items-center gap-2.5 text-[11px] text-gray-400 font-mono pt-1">
                        <span>📚 {{ $c->lessons_count }} bài học</span>
                        <span>·</span>
                        <span>⚡ {{ $totalActivities }} học liệu</span>
                        @if($c->enrollment_duration_days)
                            <span>·</span>
                            <span>⏰ {{ $c->enrollment_duration_days }}d</span>
                        @endif
                        <span>·</span>
                        @php $scaleMeta = $c->getGradingScaleMeta(); @endphp
                        <span class="text-purple-300">{{ $scaleMeta['icon'] }} {{ $scaleMeta['short'] }}</span>
                    </div>
                </div>
                </div>

                <div class="pt-4 mt-4 border-t border-slate-800 flex items-center justify-between text-xs">
                    <a href="{{ route('admin.courses.show', $c->id) }}" class="text-xs font-bold text-fsel-teal hover:underline flex items-center gap-1">
                        <span>Soạn giáo trình</span>
                    </a>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.courses.edit', $c->id) }}" title="Sửa thông tin khóa" class="text-xs text-gray-400 hover:text-white p-1 rounded hover:bg-slate-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>

                        <form method="POST" action="{{ route('admin.courses.destroy', $c->id) }}" onsubmit="return confirm('Xác nhận xóa khóa học này cùng toàn bộ bài học bên trong?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Xóa khóa học" class="text-xs text-red-400 hover:text-red-300 p-1 rounded hover:bg-red-500/10 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 admin-card p-12 text-center text-gray-500">
                Chưa có khóa học nào trong danh mục này.
            </div>
        @endforelse
    </div>
</div>
@endsection
