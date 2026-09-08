@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="examBuilderApp({{ json_encode([
    'skill' => old('skill', $exam->skill ?? 'full_mock'),
    'difficulty' => old('difficulty', $exam->difficulty ?? 'B2'),
    'title' => old('title', $exam->title ?? ''),
    'key' => old('key', $exam->key ?? ''),
    'duration_minutes' => old('duration_minutes', $exam->duration_minutes ?? 177),
    'reward_coins' => old('reward_coins', $exam->reward_coins ?? 250),
    'description' => old('description', $exam->description ?? ''),
    'sections' => old('sections', $exam->sections ?? ['listening' => 35, 'reading' => 40, 'writing' => 2, 'speaking' => 3]),
    'question_count' => old('question_count', $exam->question_count ?? 80),
    'selectedQuestions' => old('question_ids', $exam->question_ids ?? []),
]) }})">

    {{-- Top Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.exams.index') }}" class="text-xs text-gray-400 hover:text-white flex items-center gap-1 mb-1 transition-colors">
                <span>&larr;</span> Quay lại Quản lý đề thi
            </a>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>{{ $isEdit ? '✏️ Chỉnh Sửa Đề Thi' : '🎯 Tạo Đề Thi Mới' }}</span>
            </h1>
        </div>

        <a href="{{ route('teacher.ai_generator.index') }}" class="px-3.5 py-2 rounded-xl bg-purple-600/20 text-purple-300 border border-purple-500/30 text-xs font-bold hover:bg-purple-600/30 transition-all flex items-center gap-1.5">
            <span>🪄 AI Sinh câu hỏi</span>
        </a>
    </div>

    {{-- Main Form --}}
    <form method="POST" action="{{ $isEdit ? route('admin.exams.update', $exam->id) : route('admin.exams.store') }}" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- 1. BASIC EXAM CONFIG --}}
        <div class="card-dark p-6 space-y-5 border-slate-800">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
                <span>1. Cấu hình Thông tin Đề thi</span>
            </h3>

            <div class="space-y-4">
                {{-- Title --}}
                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Tên Đề Thi <span class="text-red-400">*</span></label>
                    <input type="text" name="title" x-model="form.title" @input="autoSlug()" required placeholder="Ví dụ: VSTEP (Full 4 Kỹ Năng) - Đề Thi Thử Số 01" class="login-input text-xs">
                    @error('title') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Key & Category --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Mã Key Đề Thi (Slug duy nhất)</label>
                        <input type="text" name="key" x-model="form.key" placeholder="vstep_full_mock_01" class="login-input text-xs font-mono">
                        <span class="text-[10px] text-gray-500 mt-1 block">Dùng làm đường dẫn URL vào phòng thi: /practice/exam/[key]</span>
                        @error('key') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Loại Đề Thi / Kỹ Năng <span class="text-red-400">*</span></label>
                        <div class="relative" x-data="{
                            open: false,
                            options: {
                                'full_mock': '🎯 Thi Thử Đầy Đủ 4 Kỹ Năng (Listening • Reading • Writing • Speaking)',
                                'listening': '🎧 Đề Thi Kỹ Năng Nghe (Listening - 35 câu)',
                                'reading': '📖 Đề Thi Kỹ Năng Đọc (Reading - 40 câu)',
                                'writing': '✍️ Đề Thi Kỹ Năng Viết (Writing - 2 bài)',
                                'speaking': '🎙️ Đề Thi Kỹ Năng Nói (Speaking - 3 phần)'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="skill" :value="form.skill">
                            <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="options[form.skill] || form.skill" class="text-white truncate"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="form.skill = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="form.skill === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="form.skill === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CEFR, Duration, Coins --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Cấp độ CEFR <span class="text-red-400">*</span></label>
                        <div class="relative" x-data="{
                            open: false,
                            options: {
                                'B1': 'B1 - Trung cấp (Intermediate)',
                                'B2': 'B2 - Trung cao cấp (Upper Intermediate)',
                                'C1': 'C1 - Cao cấp (Advanced)',
                                'A2': 'A2 - Tiền trung cấp (Elementary)',
                                'Mixed': 'Mixed - Tổng hợp (Placement)'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="difficulty" :value="form.difficulty">
                            <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="options[form.difficulty] || form.difficulty" class="text-white truncate"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="form.difficulty = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="form.difficulty === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="form.difficulty === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Thời lượng (Phút) <span class="text-red-400">*</span></label>
                        <input type="number" name="duration_minutes" x-model="form.duration_minutes" min="1" max="240" required class="login-input text-xs font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Coins Thưởng Hoàn Thành</label>
                        <input type="number" name="reward_coins" x-model="form.reward_coins" min="0" max="1000" required class="login-input text-xs font-mono">
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Mô tả tóm tắt nội dung & Mục tiêu đề thi</label>
                    <textarea name="description" x-model="form.description" rows="3" placeholder="Bộ đề thi thử chuẩn quốc gia gồm trọn vẹn 4 Kỹ năng: Nghe (35 câu), Đọc (40 câu), Viết (2 bài), Nói (3 phần)..." class="login-input text-xs"></textarea>
                </div>

                {{-- Publish Status Toggle --}}
                <div class="flex items-center gap-3 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $exam->is_published ?? true) ? 'checked' : '' }} class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-white">Xuất bản đề thi này ngay lập tức (Học viên có thể nhìn thấy và làm bài)</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- 2. STRUCTURE & QUESTION COMPILATION (4 CORE SKILLS) --}}
        <div class="card-dark p-6 space-y-5 border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <span>2. Cấu Trúc Đề & Phân Bổ Câu Hỏi (4 Kỹ Năng)</span>
                </h3>
                <span class="text-xs font-mono text-fsel-teal font-bold" x-text="'Tổng: ' + computedTotalQuestions + ' câu hỏi / phần thi'"></span>
            </div>

            {{-- Mode A: When Full 4-Skill Mock Test is Selected --}}
            <div x-show="form.skill === 'full_mock'" class="space-y-4">
                <p class="text-xs text-gray-400">Thiết lập số lượng câu hỏi/nội dung lấy tự động từ Ngân hàng câu hỏi cho 4 Kỹ năng:</p>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-xl bg-emerald-950/20 border border-emerald-500/30 space-y-1">
                        <span class="text-xs font-bold text-emerald-300 block">🎧 Nghe (Listening)</span>
                        <input type="number" name="sections[listening]" x-model.number="form.sections.listening" min="1" max="50" class="login-input text-xs font-mono">
                        <span class="text-[10px] text-gray-400 block">Chuẩn: 35 câu (3 Phần)</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-purple-950/20 border border-purple-500/30 space-y-1">
                        <span class="text-xs font-bold text-purple-300 block">📖 Đọc (Reading)</span>
                        <input type="number" name="sections[reading]" x-model.number="form.sections.reading" min="1" max="50" class="login-input text-xs font-mono">
                        <span class="text-[10px] text-gray-400 block">Chuẩn: 40 câu (4 Bài đọc)</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-cyan-950/20 border border-cyan-500/30 space-y-1">
                        <span class="text-xs font-bold text-cyan-300 block">✍️ Viết (Writing)</span>
                        <input type="number" name="sections[writing]" x-model.number="form.sections.writing" min="1" max="10" class="login-input text-xs font-mono">
                        <span class="text-[10px] text-gray-400 block">Chuẩn: 2 bài (Task 1 + 2)</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-amber-950/20 border border-amber-500/30 space-y-1">
                        <span class="text-xs font-bold text-amber-300 block">🎙️ Nói (Speaking)</span>
                        <input type="number" name="sections[speaking]" x-model.number="form.sections.speaking" min="1" max="10" class="login-input text-xs font-mono">
                        <span class="text-[10px] text-gray-400 block">Chuẩn: 3 phần phỏng vấn</span>
                    </div>
                </div>
            </div>

            {{-- Mode B: When Single Skill is Selected --}}
            <div x-show="form.skill !== 'full_mock'" class="space-y-4" x-cloak>
                <div class="max-w-xs">
                    <label class="block text-xs font-bold text-gray-300 mb-1">Số lượng câu hỏi / phần thi trong đề</label>
                    <input type="number" name="question_count" x-model.number="form.question_count" min="1" max="60" class="login-input text-xs font-mono">
                    <span class="text-[10px] text-gray-500 mt-1 block">Hệ thống sẽ tự động lấy các câu hỏi từ ngân hàng phù hợp theo kỹ năng và độ khó CEFR.</span>
                </div>
            </div>
        </div>

        {{-- 3. LIVE PREVIEW CARD --}}
        <div class="card-dark p-6 space-y-4 border-indigo-500/30 bg-gradient-to-b from-indigo-950/20 to-slate-900/60">
            <h3 class="text-xs font-bold text-indigo-300 uppercase tracking-wider flex items-center gap-2">
                <span>👁 Xem trước thẻ đề thi (Student View Preview)</span>
            </h3>

            <div class="card-dark p-5 space-y-3 max-w-md border-indigo-500/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold font-mono px-2.5 py-0.5 rounded-lg" x-text="form.difficulty"></span>
                        <span class="text-xs text-fsel-gold font-bold font-mono" x-text="'🪙 +' + form.reward_coins + ' Coins'"></span>
                    </div>
                    <span class="text-[10px] text-gray-500 font-mono">Chưa làm bài</span>
                </div>

                <h4 class="text-base font-bold text-white leading-snug" x-text="form.title || 'Tên đề thi của bạn...'"></h4>
                <p class="text-xs text-gray-400 leading-relaxed" x-text="form.description || 'Mô tả tóm tắt nội dung đề thi...'"></p>

                <div class="flex items-center gap-4 text-xs text-gray-400 font-mono pt-1">
                    <span x-text="'📝 ' + computedTotalQuestions + ' câu hỏi'"></span>
                    <span>·</span>
                    <span x-text="'⏱ ' + form.duration_minutes + ' phút'"></span>
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-3">
            <a href="{{ route('admin.exams.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-xs font-bold text-gray-300 hover:text-white transition-colors">
                Hủy bỏ
            </a>

            <button type="submit" class="btn-primary !w-auto !py-3 px-8 text-xs font-bold shadow-glow-blue flex items-center gap-2">
                <span>{{ $isEdit ? 'Lưu Thay Đổi Đề Thi' : 'Tạo & Xuất Bản Đề Thi' }}</span>
            </button>
        </div>
    </form>
</div>

<script>
function examBuilderApp(initialData) {
    return {
        form: {
            title: initialData.title || '',
            key: initialData.key || '',
            skill: initialData.skill || 'full_mock',
            difficulty: initialData.difficulty || 'B2',
            duration_minutes: initialData.duration_minutes || 177,
            reward_coins: initialData.reward_coins || 250,
            description: initialData.description || '',
            sections: initialData.sections || { listening: 35, reading: 40, writing: 2, speaking: 3 },
            question_count: initialData.question_count || 80,
        },

        autoSlug() {
            if (!this.form.key || this.form.key.startsWith('de-thi-') || this.form.key.length < 5) {
                const slug = this.form.title
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
                this.form.key = slug;
            }
        },

        get computedTotalQuestions() {
            if (this.form.skill === 'full_mock') {
                const s = this.form.sections || {};
                return (Number(s.listening) || 0) + (Number(s.reading) || 0) + (Number(s.writing) || 0) + (Number(s.speaking) || 0);
            }
            return Number(this.form.question_count) || 10;
        }
    };
}
</script>
@endsection
