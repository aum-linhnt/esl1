@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    {{-- Header & Create Button --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span>🎯</span>
                <span>Quản Lý Đề Thi & Khảo Thí (Exam Studio)</span>
            </h1>
            <p class="text-xs text-gray-400 mt-1">Biên soạn, cấu hình bộ đề thi 4 kỹ năng và phân phối đề thi cho học viên ESL</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('teacher.ai_generator.index') }}" class="px-4 py-2.5 rounded-xl bg-purple-600/20 text-purple-300 border border-purple-500/30 hover:bg-purple-600/30 text-xs font-bold transition-all flex items-center gap-2">
                <span>🪄</span>
                <span>Tạo bằng AI Gemini</span>
            </a>

            <a href="{{ route('admin.exams.create') }}" class="btn-primary !w-auto !py-2.5 px-5 text-xs font-bold flex items-center gap-2 shadow-glow-blue">
                <span>+ Tạo Đề Thi Mới</span>
            </a>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card-dark p-5 space-y-2 border-slate-800">
            <span class="text-xs text-gray-400 font-mono uppercase block">Tổng số Đề thi</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-black text-white font-mono">{{ $totalExams }}</span>
                <span class="text-xs text-indigo-400 font-bold">Tất cả danh mục</span>
            </div>
        </div>

        <div class="card-dark p-5 space-y-2 border-slate-800">
            <span class="text-xs text-gray-400 font-mono uppercase block">Đã Xuất bản</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-black text-emerald-400 font-mono">{{ $publishedCount }}</span>
                <span class="text-xs text-emerald-500 font-mono">Đang mở thi</span>
            </div>
        </div>

        <div class="card-dark p-5 space-y-2 border-slate-800">
            <span class="text-xs text-gray-400 font-mono uppercase block">Đề Thi 4 Kỹ Năng</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-black text-rose-400 font-mono">{{ $fullMockCount }}</span>
                <span class="text-xs text-rose-400 font-mono">Full Mock Tests</span>
            </div>
        </div>

        <div class="card-dark p-5 space-y-2 border-slate-800">
            <span class="text-xs text-gray-400 font-mono uppercase block">Lượt Nộp Bài Thi</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-black text-fsel-gold font-mono">{{ $totalSubmissions }}</span>
                <span class="text-xs text-gray-400 font-mono">Đã chấm điểm</span>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card-dark p-5 border-slate-800 relative z-30">
        <form method="GET" action="{{ route('admin.exams.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Search Keyword --}}
            <div class="lg:col-span-2">
                <input type="text" name="search" value="{{ $searchKeyword }}" placeholder="Tìm kiếm tên đề thi, mã key, mô tả..." class="login-input text-xs">
            </div>

            {{-- Skill Category Filter --}}
            <div>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    selected: '{{ $currentSkill }}',
                    options: {
                        'all': 'Tất cả Kỹ năng',
                        'full_mock': '🎯 Thi Thử 4 Kỹ Năng (Full Mock)',
                        'listening': '🎧 Nghe (Listening)',
                        'reading': '📖 Đọc (Reading)',
                        'writing': '✍️ Viết (Writing)',
                        'speaking': '🎙️ Nói (Speaking)'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="skill" :value="selected">
                    <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                        <span x-text="options[selected] || selected" class="text-white truncate"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Difficulty Filter --}}
            <div>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    selected: '{{ $currentDifficulty }}',
                    options: {
                        'all': 'Tất cả Cấp độ (CEFR)',
                        'A1': 'A1 - Sơ cấp',
                        'A2': 'A2 - Tiền trung cấp',
                        'B1': 'B1 - Trung cấp',
                        'B2': 'B2 - Trung cao cấp',
                        'C1': 'C1 - Cao cấp',
                        'Mixed': 'Mixed - Tổng hợp'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="difficulty" :value="selected">
                    <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                        <span x-text="options[selected] || selected" class="text-white truncate"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in options" :key="val">
                            <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Submit & Reset --}}
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary !py-2.5 px-4 text-xs font-bold flex-1 shadow-glow-blue">
                    Lọc đề thi
                </button>
                <a href="{{ route('admin.exams.index') }}" class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-gray-400 hover:text-white" title="Đặt lại">
                    ✕
                </a>
            </div>
        </form>
    </div>

    {{-- Exam Sets Table --}}
    <div class="card-dark overflow-hidden border-slate-800 relative z-10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-300">
                <thead class="bg-slate-900/90 text-gray-400 uppercase font-mono text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">Đề Thi & Danh mục</th>
                        <th class="py-3.5 px-4">Phân loại / CEFR</th>
                        <th class="py-3.5 px-4">Cấu trúc & Thời lượng</th>
                        <th class="py-3.5 px-4">Thưởng Coins</th>
                        <th class="py-3.5 px-4">Lượt thi</th>
                        <th class="py-3.5 px-4">Trạng thái</th>
                        <th class="py-3.5 px-4 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($exams as $exam)
                        <tr class="hover:bg-slate-900/40 transition-colors">
                            {{-- Title & Key --}}
                            <td class="py-4 px-4 max-w-xs">
                                <div class="space-y-1">
                                    <span class="font-bold text-white block truncate leading-tight">{{ $exam->title }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-mono text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20">
                                            {{ $exam->key }}
                                        </span>
                                        @if($exam->creator)
                                            <span class="text-[10px] text-gray-500">bởi {{ $exam->creator->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Skill & Difficulty --}}
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    @if($exam->skill === 'full_mock')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-rose-500/15 text-rose-300 border border-rose-500/30">
                                            🎯 Full 4 Kỹ Năng
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-slate-800 text-gray-300 capitalize">
                                            {{ $exam->skill }}
                                        </span>
                                    @endif
                                    <span class="text-[10px] font-mono block text-gray-400">Level: <strong class="text-white">{{ $exam->difficulty }}</strong></span>
                                </div>
                            </td>

                            {{-- Structure & Duration --}}
                            <td class="py-4 px-4 font-mono">
                                <div class="space-y-0.5">
                                    <span class="text-white font-bold block">📝 {{ $exam->question_count }} câu hỏi</span>
                                    <span class="text-gray-400 text-[11px] block">⏱ {{ $exam->duration_minutes }} phút</span>
                                </div>
                            </td>

                            {{-- Coins --}}
                            <td class="py-4 px-4 font-mono font-bold text-fsel-gold">
                                +{{ $exam->reward_coins }} 🪙
                            </td>

                            {{-- Submissions --}}
                            <td class="py-4 px-4 font-mono">
                                <span class="text-indigo-400 font-bold">{{ $exam->submissions_count }}</span> lượt
                            </td>

                            {{-- Status Toggle --}}
                            <td class="py-4 px-4">
                                <form method="POST" action="{{ route('admin.exams.togglePublish', $exam->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full border transition-all {{ $exam->is_published ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-slate-800 text-gray-400 border-slate-700 hover:bg-slate-700' }}">
                                        <span>{{ $exam->is_published ? '✓ Xuất bản' : '○ Đang ẩn' }}</span>
                                    </button>
                                </form>
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('practice.exam', $exam->key) }}" target="_blank" class="p-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-gray-300 hover:text-white transition-colors" title="Xem phòng thi">
                                        👁
                                    </a>

                                    <a href="{{ route('admin.exams.edit', $exam->id) }}" class="p-2 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 transition-colors" title="Chỉnh sửa">
                                        ✏️
                                    </a>

                                    <form method="POST" action="{{ route('admin.exams.destroy', $exam->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đề thi này không?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg bg-red-500/15 hover:bg-red-500/30 text-red-400 transition-colors" title="Xóa">
                                            🗑
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                Chưa tìm thấy đề thi nào phù hợp với bộ lọc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($exams->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $exams->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
