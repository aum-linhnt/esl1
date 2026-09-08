@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="aiGeneratorApp()">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full shadow-glow-blue tracking-wide">
                    Admin AI Tools
                </span>
                <span class="text-xs text-gray-400">Sinh đề thi trắc nghiệm chuẩn hóa quốc tế với Gemini AI</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                <span>🪄</span>
                <span>Trình Tạo Đề & Câu Hỏi Tự Động (AI Question Generator)</span>
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.questions.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-xs font-bold text-gray-300 hover:text-white transition-all flex items-center gap-2">
                <span>📚</span>
                <span>Xem Ngân hàng câu hỏi</span>
            </a>
        </div>
    </div>

    {{-- Anti-duplication & Standard Banner --}}
    <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-950/40 via-blue-950/20 to-purple-950/40 border border-indigo-500/30 flex items-center gap-3 shadow-lg">
        <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/40 flex items-center justify-center text-xl flex-shrink-0">
            🛡️
        </div>
        <div class="text-xs text-gray-300 leading-relaxed">
            <span class="font-bold text-indigo-300 block mb-0.5">Thuật toán Kiểm định Chuẩn hóa & Khử Trùng Lặp Tự Động:</span>
            Mọi câu hỏi được sinh tối đa lên tới <strong class="text-emerald-400 font-mono">50 câu</strong>, đảm bảo chuẩn khung tham chiếu CEFR, kiểm định tính duy nhất của đáp án và tự động đối soát loại bỏ 100% các câu hỏi trùng lặp với Ngân hàng dữ liệu.
        </div>
    </div>

    {{-- Generator Input Card --}}
    <div class="card-dark p-6 lg:p-7 space-y-6 border-slate-800 shadow-2xl">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-800 pb-3">
            <span>⚙️ Thiết lập yêu cầu tạo đề</span>
        </h2>

        <div class="space-y-5">
            {{-- Row 1: Topic --}}
            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1.5">
                    Chủ đề bài thi / Ngữ cảnh thực tế (Topic): <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-500">
                        🎯
                    </span>
                    <input
                        type="text"
                        x-model="topic"
                        placeholder="VD: Job Interview & Resume, Climate Change, Daily Workplace Travel, Technology & AI..."
                        class="login-input pl-10 text-xs font-semibold">
                </div>
            </div>

            {{-- Row 2: Skill Selection & CEFR Level --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Skill Selection --}}
                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1.5">Kỹ năng muốn tạo:</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                        <template x-for="sk in [
                            { key: 'all', label: '🌟 Tất cả (4 KN)' },
                            { key: 'reading', label: '📖 Đọc hiểu' },
                            { key: 'listening', label: '🎧 Nghe hiểu' },
                            { key: 'writing', label: '✍️ Viết' },
                            { key: 'speaking', label: '🎙️ Nói' }
                        ]" :key="sk.key">
                            <button type="button"
                                    @click="skill = sk.key"
                                    :class="skill === sk.key ? 'bg-blue-600 text-white font-bold border-blue-500 shadow-md shadow-blue-900/30' : 'bg-slate-900 text-gray-400 border-slate-700 hover:text-white'"
                                    class="py-2.5 px-2 rounded-xl border text-xs font-bold text-center transition-all">
                                <span x-text="sk.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- CEFR Level --}}
                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1.5">Cấp độ CEFR:</label>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5">
                        <template x-for="lvl in ['A1', 'A2', 'B1', 'B2', 'C1', 'Mixed']" :key="lvl">
                            <button type="button" 
                                    @click="difficulty = lvl"
                                    :class="difficulty === lvl ? 'bg-indigo-600 text-white font-bold border-indigo-500 shadow-md shadow-indigo-900/30' : 'bg-slate-900 text-gray-400 border-slate-700 hover:text-white'"
                                    class="py-2.5 px-1 rounded-xl border text-xs font-mono font-bold text-center transition-all">
                                <span x-text="lvl"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Row 3: Number of questions (1 - 50) --}}
            <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <label class="text-xs font-bold text-gray-300 block">
                            Số lượng câu hỏi cần tạo (Tối đa 50 câu):
                        </label>
                        <span class="text-[11px] text-gray-400">Chọn nhanh hoặc nhập số lượng mong muốn từ 1 đến 50</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            type="number"
                            min="1"
                            max="50"
                            x-model.number="count"
                            class="login-input !w-24 text-center font-mono font-bold text-sm text-indigo-300 !py-1.5">
                        <span class="text-xs text-gray-400 font-bold">câu</span>
                    </div>
                </div>

                {{-- Quick Count Pills --}}
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="text-[11px] text-gray-500 font-bold mr-1">Chọn nhanh:</span>
                    <template x-for="n in [5, 10, 20, 30, 50]" :key="n">
                        <button
                            @click="count = n"
                            type="button"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-mono font-bold border transition-all cursor-pointer"
                            :class="count === n ? 'bg-indigo-600 border-indigo-500 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-900 border-slate-700 text-gray-400 hover:text-white hover:bg-slate-800'"
                            x-text="n + ' câu'"></button>
                    </template>
                </div>
            </div>

            {{-- Action Button & Loading Status --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 border-t border-slate-800/80">
                <div class="text-xs text-gray-400 font-medium">
                    <span>Trạng thái: </span>
                    <span x-show="!generating" class="text-emerald-400 font-bold">Sẵn sàng</span>
                    <span x-show="generating" class="text-amber-300 font-bold flex items-center gap-1.5">
                        <span class="inline-block w-3.5 h-3.5 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span>
                        <span x-text="'Đang sinh ' + count + ' câu hỏi & khử trùng lặp...'"></span>
                    </span>
                </div>

                <button
                    @click="generateQuestions()"
                    :disabled="generating || !topic.trim()"
                    type="button"
                    class="btn-primary !w-auto !py-3 px-8 text-xs font-bold flex items-center gap-2 shadow-glow-blue cursor-pointer transition-all"
                    :class="(generating || !topic.trim()) && 'opacity-40 cursor-not-allowed'">
                    <span x-show="!generating" class="flex items-center gap-2">
                        <span>✨</span>
                        <span x-text="'Sinh ' + count + ' Câu Hỏi Bằng Gemini AI'"></span>
                    </span>
                    <span x-show="generating" class="flex items-center gap-2">
                        <span>Đang xử lý & kiểm định...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Questions Preview Section --}}
    <div id="questions-preview-section" x-show="questions.length > 0" x-cloak class="space-y-4 animate-fadeIn">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span>📋 Danh Sách Câu Hỏi Đã Tạo</span>
                </h2>
                <span class="text-xs font-mono font-bold bg-blue-600/20 text-blue-300 border border-blue-500/30 px-3 py-0.5 rounded-full" x-text="questions.length + ' câu đã chuẩn hóa & lọc trùng'"></span>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="questions = []"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold text-gray-400 hover:text-red-400 transition-colors">
                    Xóa danh sách này
                </button>

                <button
                    @click="saveQuestions()"
                    :disabled="saving"
                    type="button"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-2.5 rounded-xl text-xs transition-all shadow-lg flex items-center gap-2 cursor-pointer shadow-emerald-900/40"
                    :class="saving && 'opacity-40 cursor-not-allowed'">
                    <span x-show="!saving" class="flex items-center gap-1.5">
                        <span>💾</span>
                        <span x-text="'Lưu ' + questions.length + ' câu vào Ngân hàng câu hỏi'"></span>
                    </span>
                    <span x-show="saving" class="flex items-center gap-2">
                        <span class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>Đang lưu vào Ngân hàng...</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- Question Cards List --}}
        <div class="space-y-4">
            <template x-for="(q, idx) in questions" :key="'q_' + idx">
                <div class="card-dark p-5 space-y-3.5 border-slate-800 relative group transition-all hover:border-indigo-500/40">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 text-xs font-mono font-bold px-2.5 py-0.5 rounded-lg" x-text="'Câu #' + (idx + 1)"></span>
                            <span class="bg-slate-800 text-gray-300 text-xs px-2.5 py-0.5 rounded-lg uppercase font-mono font-bold" x-text="q.skill"></span>
                            <span class="bg-purple-900/30 text-purple-300 border border-purple-500/30 text-xs font-bold font-mono px-2 py-0.5 rounded-lg" x-text="q.difficulty"></span>
                            <span class="text-[11px] text-gray-500 font-mono" x-text="q.question_type"></span>
                        </div>

                        <button @click="removeQuestion(idx)" class="text-xs text-red-400 hover:text-red-300 font-bold px-2 py-1 rounded hover:bg-red-500/10 transition-colors">
                            &times; Xóa câu này
                        </button>
                    </div>

                    {{-- Question Text Input --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 mb-1">Nội dung câu hỏi:</label>
                        <textarea rows="2" x-model="q.question_text" class="login-input text-xs font-sans font-semibold leading-relaxed"></textarea>
                    </div>

                    {{-- Options grid --}}
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 mb-1">Các lựa chọn đáp án:</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="(opt, optIdx) in q.options" :key="'opt_' + optIdx">
                                <div class="flex items-center gap-2 p-2 rounded-xl border transition-colors"
                                     :class="isCorrectOption(opt, q.correct_answer) ? 'bg-emerald-950/30 border-emerald-500/40' : 'bg-slate-900 border-slate-800'">
                                    <span class="font-bold text-xs font-mono"
                                          :class="isCorrectOption(opt, q.correct_answer) ? 'text-emerald-400' : 'text-gray-400'"
                                          x-text="String.fromCharCode(65 + optIdx) + '.'"></span>
                                    <input type="text" x-model="q.options[optIdx]" 
                                           class="bg-transparent text-xs text-white flex-1 focus:outline-none"
                                           :class="isCorrectOption(opt, q.correct_answer) && 'text-emerald-300 font-bold'">
                                    <span x-show="isCorrectOption(opt, q.correct_answer)" class="text-emerald-400 text-xs font-bold">✓</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Correct Answer & Explanation --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div>
                            <label class="text-[11px] font-bold text-emerald-400 block mb-1">Đáp án đúng chính xác:</label>
                            <input type="text" x-model="q.correct_answer" class="login-input !py-1 text-xs text-emerald-300 font-mono font-bold">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-bold text-gray-400 block mb-1">Giải thích đáp án (Tiếng Việt):</label>
                            <input type="text" x-model="q.explanation" class="login-input !py-1 text-xs text-gray-300 font-sans">
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function aiGeneratorApp() {
    return {
        topic: 'Job Interview & Workplace Communication',
        difficulty: 'B1',
        skill: 'all',
        count: 10,
        generating: false,
        saving: false,
        questions: [],

        isCorrectOption(opt, correct) {
            if (!opt || !correct) return false;
            const cleanOpt = String(opt).trim().toLowerCase();
            const cleanCor = String(correct).trim().toLowerCase();
            return cleanOpt === cleanCor || cleanOpt.startsWith(cleanCor + '.') || cleanCor.startsWith(cleanOpt + '.');
        },

        generateQuestions() {
            if (!this.topic || !this.topic.trim()) {
                alert('Vui lòng nhập chủ đề bài thi (Topic)!');
                return;
            }
            if (this.generating) return;
            this.generating = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch('{{ route("teacher.ai_generator.generate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    topic: this.topic,
                    difficulty: this.difficulty,
                    skill: this.skill,
                    count: parseInt(this.count) || 10
                })
            })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Lỗi từ máy chủ (' + response.status + ')');
                }
                return data;
            })
            .then(res => {
                this.generating = false;
                if (res.success && Array.isArray(res.questions) && res.questions.length > 0) {
                    this.questions = res.questions;
                    setTimeout(() => {
                        const target = document.querySelector('#questions-preview-section');
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 150);
                } else {
                    alert(res.message || 'Không có câu hỏi nào được sinh ra. Vui lòng thử lại!');
                }
            })
            .catch(err => {
                this.generating = false;
                console.error('Error generating questions:', err);
                alert('Có lỗi khi sinh câu hỏi: ' + (err.message || 'Vui lòng kiểm tra lại kết nối mạng'));
            });
        },

        removeQuestion(index) {
            this.questions.splice(index, 1);
        },

        saveQuestions() {
            if (!this.questions.length || this.saving) return;
            this.saving = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch('{{ route("teacher.ai_generator.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    questions: this.questions
                })
            })
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Lỗi từ máy chủ (' + response.status + ')');
                }
                return data;
            })
            .then(res => {
                this.saving = false;
                if (res.success) {
                    alert(res.message);
                    window.location.href = '{{ route("admin.questions.index") }}';
                } else {
                    alert(res.message || 'Không thể lưu câu hỏi. Vui lòng thử lại.');
                }
            })
            .catch(err => {
                this.saving = false;
                console.error('Error saving questions:', err);
                alert('Lỗi khi lưu câu hỏi: ' + (err.message || 'Vui lòng thử lại'));
            });
        }
    };
}
</script>
@endsection
