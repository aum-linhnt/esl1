@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="aiGeneratorApp()" x-init="init()">
    
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-600 text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full shadow-glow-blue tracking-wide">
                    Admin AI Examination Studio
                </span>
                <span class="text-xs text-slate-400">Sinh đề thi khảo thí chuẩn hóa theo đúng cấu trúc đề thi thật</span>
            </div>
            <h1 class="text-2xl lg:text-3xl font-black text-white tracking-tight flex items-center gap-3">
                <span class="text-3xl">🪄</span>
                <span>Hệ Thống Sinh Đề Thi AI Chuẩn Khảo Thí</span>
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.exams.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-xs font-bold text-slate-300 hover:text-white transition-all flex items-center gap-2">
                <span>📝</span>
                <span>Quản Lý Đề Thi (Exam Sets)</span>
            </a>
            <a href="{{ route('admin.questions.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-xs font-bold text-slate-300 hover:text-white transition-all flex items-center gap-2">
                <span>📚</span>
                <span>Ngân Hàng Câu Hỏi</span>
            </a>
        </div>
    </div>

    {{-- Exam Standards Selector (Tabs) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <template x-for="std in examStandards" :key="std.key">
            <button type="button"
                    @click="selectStandard(std.key)"
                    :class="examStandard === std.key 
                        ? 'border-indigo-500 bg-indigo-950/40 text-white ring-2 ring-indigo-500/50 shadow-lg shadow-indigo-950/50' 
                        : 'border-slate-800 bg-slate-900/80 text-slate-400 hover:text-white hover:border-slate-700'"
                    class="p-4 rounded-2xl border text-left transition-all duration-200 cursor-pointer flex flex-col justify-between group relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-2xl" x-text="std.icon"></span>
                    <span x-show="examStandard === std.key" class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                </div>
                <div>
                    <h3 class="text-xs sm:text-sm font-black group-hover:text-indigo-300 transition-colors" x-text="std.title"></h3>
                    <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5" x-text="std.subtitle"></p>
                </div>
            </button>
        </template>
    </div>

    {{-- Main Control Card --}}
    <div class="card-dark p-6 lg:p-8 space-y-6 border-slate-800 shadow-2xl relative">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/80 pb-4">
            <div>
                <h2 class="text-base font-black text-white flex items-center gap-2">
                    <span x-text="currentStandardData.icon"></span>
                    <span>Cấu hình Ma trận Đề thi: <span class="text-indigo-400 font-mono" x-text="currentStandardData.title"></span></span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5" x-text="currentStandardData.description"></p>
            </div>

            {{-- Mode Badges --}}
            <div class="flex items-center gap-2 bg-slate-900/90 p-1.5 rounded-xl border border-slate-800">
                <button type="button"
                        @click="mode = 'full_exam'"
                        :class="mode === 'full_exam' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                    🎯 Trọn bộ đề thi thật
                </button>
                <button type="button"
                        @click="mode = 'custom'"
                        :class="mode === 'custom' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                    ⚙️ Tùy chỉnh câu hỏi
                </button>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Topic with Quick Context Suggestions --}}
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-200">
                    Chủ đề bài thi / Ngữ cảnh khảo thí (Topic): <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        🎯
                    </span>
                    <input
                        type="text"
                        x-model="topic"
                        :placeholder="currentStandardData.placeholder"
                        class="login-input pl-10 text-xs sm:text-sm font-semibold text-white">
                </div>

                {{-- Suggested Topic Pills --}}
                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                    <span class="text-[11px] font-bold text-slate-500 mr-1">Gợi ý chủ đề:</span>
                    <template x-for="(sug, sIdx) in currentStandardData.suggestedTopics" :key="sIdx">
                        <button type="button"
                                @click="topic = sug"
                                class="text-[11px] px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-indigo-300 hover:border-indigo-500/50 transition-colors">
                            <span x-text="sug"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Row: Skills, CEFR & Exam Presets --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                
                {{-- Skill Selection --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Phân loại Kỹ năng:</label>
                    <select x-model="skill" class="login-input text-xs font-semibold">
                        <option value="all">🌟 Toàn diện / Ma trận tổng hợp</option>
                        <option value="reading">📖 Đọc hiểu (Reading)</option>
                        <option value="listening">🎧 Nghe hiểu (Listening)</option>
                        <option value="writing">✍️ Viết (Writing)</option>
                        <option value="speaking">🎙️ Nói (Speaking)</option>
                        <option value="grammar">🧩 Ngữ pháp & Từ vựng</option>
                    </select>
                </div>

                {{-- CEFR Level --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Cấp độ CEFR / Độ khó:</label>
                    <div class="grid grid-cols-5 gap-1">
                        <template x-for="lvl in ['A2', 'B1', 'B2', 'C1', 'Mixed']" :key="lvl">
                            <button type="button" 
                                    @click="difficulty = lvl"
                                    :class="difficulty === lvl ? 'bg-indigo-600 text-white font-bold border-indigo-500 shadow-md shadow-indigo-900/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                                    class="py-2.5 px-1 rounded-xl border text-xs font-mono font-bold text-center transition-all">
                                <span x-text="lvl"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Exam Presets (Quick Matrix) --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Định dạng số câu & Thời gian:</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        <template x-for="preset in currentStandardData.presets" :key="preset.count">
                            <button type="button"
                                    @click="applyPreset(preset)"
                                    :class="count === preset.count ? 'bg-blue-600 text-white font-bold border-blue-500 shadow-md' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                                    class="py-2 px-1.5 rounded-xl border text-center transition-all">
                                <span class="block text-xs font-mono font-bold" x-text="preset.count + ' câu'"></span>
                                <span class="block text-[10px] text-slate-400" x-text="preset.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Custom Count Bar if mode === 'custom' --}}
            <div x-show="mode === 'custom'" class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 flex items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-slate-200 block">Tự chọn số lượng câu hỏi (1 - 50 câu):</span>
                    <span class="text-[11px] text-slate-400">Chọn số lượng câu theo nhu cầu soạn thảo của bạn</span>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        type="number"
                        min="1"
                        max="50"
                        x-model.number="count"
                        class="login-input !w-24 text-center font-mono font-bold text-sm text-indigo-300 !py-1.5">
                    <span class="text-xs text-slate-400 font-bold">câu</span>
                </div>
            </div>

            {{-- Bottom Action Row --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-3 border-t border-slate-800">
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span>Trạng thái Engine: </span>
                    <span x-show="!generating" class="text-emerald-400 font-bold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>Sẵn sàng sinh đề chuẩn hóa</span>
                    </span>
                    <span x-show="generating" class="text-amber-300 font-bold flex items-center gap-1.5">
                        <span class="inline-block w-3.5 h-3.5 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span>
                        <span x-text="'Đang sinh đề thi ' + currentStandardData.title + ' (' + count + ' câu)...'"></span>
                    </span>
                </div>

                <button
                    @click="generateQuestions()"
                    :disabled="generating || !topic.trim()"
                    type="button"
                    class="btn-primary !w-auto !py-3 px-8 text-xs font-bold flex items-center gap-2 shadow-glow-blue cursor-pointer transition-all hover:scale-[1.02]"
                    :class="(generating || !topic.trim()) && 'opacity-40 cursor-not-allowed'">
                    <span x-show="!generating" class="flex items-center gap-2">
                        <span>✨</span>
                        <span x-text="'Sinh Đề Thi Chuẩn Hóa (' + count + ' Câu)'"></span>
                    </span>
                    <span x-show="generating" class="flex items-center gap-2">
                        <span>Đang xử lý ma trận & đối soát...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Questions Preview Section --}}
    <div id="questions-preview-section" x-show="questions.length > 0" x-cloak class="space-y-5 animate-fadeIn">
        
        {{-- Section Action Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/90 p-5 rounded-2xl border border-slate-800 shadow-xl">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-black text-white flex items-center gap-2">
                        <span>📋 Đề Thi Đã Sinh Chuẩn Khảo Thí</span>
                    </h2>
                    <span class="text-xs font-mono font-bold bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 px-3 py-0.5 rounded-full" 
                          x-text="questions.length + ' câu hỏi đã hoàn tất'"></span>
                </div>
                <p class="text-xs text-slate-400">
                    Chuẩn khảo thí: <strong class="text-slate-200" x-text="currentStandardData.title"></strong> — Bạn có thể chỉnh sửa nội dung trực tiếp trước khi lưu.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    @click="questions = []"
                    class="px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                    Hủy & Làm mới
                </button>

                {{-- Action 1: Save to Question Bank --}}
                <button
                    @click="saveQuestionsToBank()"
                    :disabled="saving"
                    type="button"
                    class="px-4 py-2.5 rounded-xl border border-slate-700 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 transition-all flex items-center gap-2 cursor-pointer"
                    :class="saving && 'opacity-40 cursor-not-allowed'">
                    <span>📚</span>
                    <span>Lưu vào Ngân Hàng</span>
                </button>

                {{-- Action 2: Save as Direct Exam Set --}}
                <button
                    @click="openSaveExamModal = true"
                    :disabled="saving"
                    type="button"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold px-6 py-2.5 rounded-xl text-xs transition-all shadow-lg shadow-emerald-900/40 flex items-center gap-2 cursor-pointer hover:scale-105"
                    :class="saving && 'opacity-40 cursor-not-allowed'">
                    <span>🏆</span>
                    <span>Lưu Thành Đề Thi CBT (Luyện Ngay)</span>
                </button>
            </div>
        </div>

        {{-- Question Cards List --}}
        <div class="space-y-5">
            <template x-for="(q, idx) in questions" :key="'q_' + idx">
                <div class="card-dark p-6 space-y-4 border-slate-800 relative group transition-all hover:border-indigo-500/40">
                    
                    {{-- Card Top Badge Bar --}}
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 text-xs font-mono font-bold px-2.5 py-0.5 rounded-lg" x-text="'Câu #' + (idx + 1)"></span>
                            <span x-show="q.part_name" class="bg-slate-800 text-slate-300 text-xs px-2.5 py-0.5 rounded-lg font-semibold" x-text="q.part_name"></span>
                            <span class="bg-slate-800 text-slate-300 text-xs px-2.5 py-0.5 rounded-lg uppercase font-mono font-bold" x-text="q.skill"></span>
                            <span class="bg-purple-900/30 text-purple-300 border border-purple-500/30 text-xs font-bold font-mono px-2 py-0.5 rounded-lg" x-text="q.difficulty"></span>
                            <span class="text-[11px] text-slate-500 font-mono" x-text="q.question_type"></span>
                        </div>

                        <button @click="removeQuestion(idx)" class="text-xs text-red-400 hover:text-red-300 font-bold px-2 py-1 rounded hover:bg-red-500/10 transition-colors">
                            &times; Xóa câu này
                        </button>
                    </div>

                    {{-- If question belongs to a Reading Passage --}}
                    <template x-if="q.passage_content || (q.meta_data && q.meta_data.passage_content)">
                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-700/60 space-y-2">
                            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                <span class="text-xs font-bold text-amber-300 flex items-center gap-1.5">
                                    <span>📖</span>
                                    <span x-text="q.passage_title || (q.meta_data && q.meta_data.passage_title) || 'Đoạn Văn Bài Đọc'"></span>
                                </span>
                                <span class="text-[10px] text-slate-400 uppercase font-mono">Reading Passage Text</span>
                            </div>
                            <div class="text-xs text-slate-300 leading-relaxed font-sans max-h-48 overflow-y-auto pr-2 whitespace-pre-line"
                                 x-text="q.passage_content || (q.meta_data && q.meta_data.passage_content)">
                            </div>
                        </div>
                    </template>

                    {{-- Question Text Input --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Nội dung câu hỏi / Yêu cầu:</label>
                        <textarea rows="2" x-model="q.question_text" class="login-input text-xs font-sans font-semibold leading-relaxed"></textarea>
                    </div>

                    {{-- Options Grid (for MCQ / True-False) --}}
                    <template x-if="q.options && q.options.length > 0">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 mb-1">Các lựa chọn đáp án:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <template x-for="(opt, optIdx) in q.options" :key="'opt_' + optIdx">
                                    <div class="flex items-center gap-2 p-2 rounded-xl border transition-colors"
                                         :class="isCorrectOption(opt, q.correct_answer) ? 'bg-emerald-950/30 border-emerald-500/40 ring-1 ring-emerald-500/30' : 'bg-slate-900 border-slate-800'">
                                        <span class="font-bold text-xs font-mono"
                                              :class="isCorrectOption(opt, q.correct_answer) ? 'text-emerald-400' : 'text-slate-400'"
                                              x-text="String.fromCharCode(65 + optIdx) + '.'"></span>
                                        <input type="text" x-model="q.options[optIdx]" 
                                               class="bg-transparent text-xs text-white flex-1 focus:outline-none"
                                               :class="isCorrectOption(opt, q.correct_answer) && 'text-emerald-300 font-bold'">
                                        <span x-show="isCorrectOption(opt, q.correct_answer)" class="text-emerald-400 text-xs font-bold">✓ Đáp án đúng</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Correct Answer & Explanation --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div>
                            <label class="text-[11px] font-bold text-emerald-400 block mb-1">Đáp án đúng chính xác:</label>
                            <input type="text" x-model="q.correct_answer" class="login-input !py-1.5 text-xs text-emerald-300 font-mono font-bold">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-bold text-slate-400 block mb-1">Giải thích chi tiết (Tiếng Việt):</label>
                            <textarea rows="2" x-model="q.explanation" class="login-input !py-1.5 text-xs text-slate-300 font-sans"></textarea>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- MODAL: Save As Direct Exam Set (Tạo bài thi CBT) --}}
    <div x-show="openSaveExamModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fadeIn">
        <div class="card-dark w-full max-w-lg p-6 lg:p-7 space-y-5 border-slate-700 shadow-2xl relative" @click.outside="openSaveExamModal = false">
            
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🏆</span>
                    <h3 class="text-base font-black text-white">Xuất Bản Thành Đề Thi CBT</h3>
                </div>
                <button type="button" @click="openSaveExamModal = false" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed">
                Đề thi sẽ được tạo ngay lập tức vào hệ thống Khảo thí. Học viên có thể mở phòng thi trực tuyến CBT và làm bài thi tính điểm ngay lập tức.
            </p>

            <div class="space-y-4">
                {{-- Exam Title --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Tên Đề Thi: <span class="text-red-400">*</span></label>
                    <input type="text" x-model="examTitle" class="login-input text-xs font-bold text-white">
                </div>

                {{-- Duration & Reward Coins --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Thời gian làm bài:</label>
                        <div class="flex items-center gap-1.5">
                            <input type="number" min="5" max="180" x-model.number="examDuration" class="login-input text-center font-mono font-bold text-xs">
                            <span class="text-xs text-slate-400">phút</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Coin thưởng hoàn thành:</label>
                        <div class="flex items-center gap-1.5">
                            <input type="number" min="0" max="500" x-model.number="examCoins" class="login-input text-center font-mono font-bold text-xs text-amber-300">
                            <span class="text-xs text-slate-400">xu</span>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Mô tả bài thi:</label>
                    <textarea rows="2" x-model="examDescription" class="login-input text-xs text-slate-300"></textarea>
                </div>

                {{-- Published Switch --}}
                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-900 border border-slate-800">
                    <input type="checkbox" id="exam_published" x-model="examPublished" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                    <label for="exam_published" class="text-xs font-bold text-slate-200 cursor-pointer">
                        Xuất bản công khai ngay cho học viên làm bài
                    </label>
                </div>
            </div>

            {{-- Modal Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-800">
                <button type="button" @click="openSaveExamModal = false" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white">
                    Hủy
                </button>
                <button type="button" 
                        @click="saveAsExamSet()" 
                        :disabled="saving"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold px-6 py-2.5 rounded-xl text-xs transition-all shadow-lg flex items-center gap-2 cursor-pointer">
                    <span x-show="!saving">Tạo Đề Thi Ngay ➔</span>
                    <span x-show="saving">Đang xử lý...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL: Exam Created Success Celebration --}}
    <div x-show="createdExam" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md animate-fadeIn">
        <div class="card-dark w-full max-w-md p-6 lg:p-7 text-center space-y-5 border-emerald-500/50 shadow-2xl relative">
            <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-3xl flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/20 animate-bounce">
                🎉
            </div>

            <div class="space-y-1.5">
                <h3 class="text-xl font-black text-white">Xuất Bản Đề Thi Thành Công!</h3>
                <p class="text-xs text-slate-300 leading-relaxed font-semibold" x-text="createdExam?.title"></p>
                <p class="text-xs text-slate-400">
                    Đã lưu <span class="text-emerald-400 font-mono font-bold" x-text="createdExam?.count"></span> câu hỏi theo chuẩn khảo thí.
                </p>
            </div>

            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-slate-400 text-left space-y-1">
                <div>Mã đề thi (Key): <strong class="text-indigo-400" x-text="createdExam?.key"></strong></div>
                <div>Đường dẫn thi: <span class="text-emerald-400 underline break-all" x-text="createdExam?.url"></span></div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <a :href="createdExam?.url" target="_blank" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 cursor-pointer transition-transform hover:scale-105">
                    <span>🎯 Làm Thử Đề Thi Ngay (CBT)</span>
                </a>
                <button type="button" @click="createdExam = null" class="w-full sm:w-auto px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition-colors">
                    Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function aiGeneratorApp() {
    return {
        examStandard: 'thpt_qg',
        mode: 'full_exam',
        topic: 'Bảo vệ Môi trường, Năng lượng tái tạo & Biến đổi Khí hậu',
        difficulty: 'B1',
        skill: 'all',
        count: 50,
        generating: false,
        saving: false,
        questions: [],
        openSaveExamModal: false,
        createdExam: null,

        // Modal exam data
        examTitle: 'Đề Thi Thử THPT Quốc Gia - Môn Tiếng Anh 2026',
        examDuration: 60,
        examCoins: 30,
        examDescription: 'Đề thi trắc nghiệm tốt nghiệp THPT môn Tiếng Anh chuẩn cấu trúc Bộ GD&ĐT gồm 50 câu hỏi đầy đủ các chuyên đề.',
        examPublished: true,

        examStandards: [
            {
                key: 'thpt_qg',
                icon: '🎓',
                title: 'THPT Quốc Gia',
                subtitle: 'Bộ Giáo dục & Đào tạo',
                description: 'Cấu trúc chuẩn 50 câu: Ngữ âm, Trọng âm, Ngữ pháp, Từ vựng, Đồng nghĩa, Trái nghĩa, Đọc điền, Đọc hiểu, Tìm lỗi sai, Viết lại câu.',
                placeholder: 'VD: Môi trường & Năng lượng, Chuyển đổi số, Giáo dục & Nghề nghiệp...',
                suggestedTopics: [
                    'Bảo vệ Môi trường & Năng lượng tái tạo',
                    'Chuyển đổi số & Trí tuệ nhân tạo trong Giáo dục',
                    'Định hướng nghề nghiệp & Kỹ năng thế kỷ 21',
                    'Bảo tồn di sản văn hóa & Du lịch sinh thái'
                ],
                presets: [
                    { count: 50, duration: 60, label: 'Đề Chuẩn (60p)' },
                    { count: 25, duration: 30, label: 'Rút gọn (30p)' },
                    { count: 15, duration: 20, label: 'Mini test (20p)' }
                ],
                defaultCount: 50,
                defaultDuration: 60,
                defaultTitle: 'Đề Thi Thử THPT Quốc Gia - Môn Tiếng Anh 2026'
            },
            {
                key: 'ielts',
                icon: '🎯',
                title: 'IELTS Standard',
                subtitle: 'Academic & General',
                description: 'Chuẩn khảo thí Cambridge: Reading Passages học thuật (True/False/Not Given, MCQ), Listening Transcripts, Writing Task 1-2, Speaking Cue Cards.',
                placeholder: 'VD: Climate Action & Technology, Psychological Behavior, Urban Planning...',
                suggestedTopics: [
                    'Environmental Sustainability & Innovations',
                    'Urban Planning & Modern Architecture',
                    'Psychology of Human Motivation',
                    'Digital Education & Artificial Intelligence'
                ],
                presets: [
                    { count: 40, duration: 60, label: 'Reading (60p)' },
                    { count: 20, duration: 35, label: '2 Passages (35p)' },
                    { count: 10, duration: 20, label: 'Practice (20p)' }
                ],
                defaultCount: 40,
                defaultDuration: 60,
                defaultTitle: 'IELTS Academic Reading & Language Practice Test'
            },
            {
                key: 'toeic',
                icon: '💼',
                title: 'TOEIC Standard',
                subtitle: 'Business & Workplace',
                description: 'Chuẩn ETS TOEIC: Part 5 Incomplete Sentences, Part 6 Text Completion, Part 7 Reading Comprehension văn bản thương mại.',
                placeholder: 'VD: Corporate Communications, Product Launch, Business Negotiations...',
                suggestedTopics: [
                    'Corporate Management & HR Policies',
                    'Global Logistics, Supply Chain & Shipping',
                    'Business Contracts, Negotiations & Sales',
                    'Customer Relations & Service Quality'
                ],
                presets: [
                    { count: 30, duration: 40, label: 'Part 5 & 7 (40p)' },
                    { count: 20, duration: 25, label: 'Part 5 Focus (25p)' },
                    { count: 10, duration: 15, label: 'Quick Test (15p)' }
                ],
                defaultCount: 30,
                defaultDuration: 40,
                defaultTitle: 'TOEIC Business Reading & Vocabulary Test'
            },
            {
                key: 'vstep',
                icon: '🏛️',
                title: 'VSTEP Standard',
                subtitle: 'Khung 6 Bậc B1 - B2 - C1',
                description: 'Chuẩn Khung năng lực Ngoại ngữ Việt Nam: Bài đọc hiểu đời sống & học thuật, bài luận xã hội, thảo luận giải pháp.',
                placeholder: 'VD: Higher Education in Vietnam, Digital Lifestyle, Eco-tourism...',
                suggestedTopics: [
                    'Higher Education in Vietnam & Career Prospects',
                    'Community-Based Ecotourism & Sustainability',
                    'Social Media Impacts on Adolescent Psychology',
                    'Online Learning in the Post-Pandemic Era'
                ],
                presets: [
                    { count: 40, duration: 60, label: 'Reading 40 câu' },
                    { count: 20, duration: 30, label: 'Rút gọn 20 câu' },
                    { count: 10, duration: 15, label: 'Thử sức 10 câu' }
                ],
                defaultCount: 40,
                defaultDuration: 60,
                defaultTitle: 'Đề Khảo Thí VSTEP B1-B2 Toàn Diện'
            },
            {
                key: 'cefr',
                icon: '🌐',
                title: 'CEFR Quốc Tế',
                subtitle: 'Khung Châu Âu A1 - C1',
                description: 'Chuẩn khảo thí CEFR tổng quát: Đánh giá ngữ pháp, từ vựng theo tần suất từ và năng lực ngôn ngữ Can-do.',
                placeholder: 'VD: Workplace Communication, Travel & Culture, Science...',
                suggestedTopics: [
                    'Everyday Professional Communication',
                    'Global Cultural Exchanges & Etiquette',
                    'Healthy Living & Modern Nutrition',
                    'Scientific Discoveries & Daily Applications'
                ],
                presets: [
                    { count: 25, duration: 35, label: 'Đề chuẩn 25 câu' },
                    { count: 15, duration: 20, label: 'Đề ngắn 15 câu' },
                    { count: 50, duration: 60, label: 'Tổng hợp 50 câu' }
                ],
                defaultCount: 25,
                defaultDuration: 35,
                defaultTitle: 'CEFR Standardized English Assessment Test'
            }
        ],

        get currentStandardData() {
            return this.examStandards.find(s => s.key === this.examStandard) || this.examStandards[0];
        },

        init() {
            this.selectStandard('thpt_qg');
        },

        selectStandard(key) {
            this.examStandard = key;
            const data = this.currentStandardData;
            if (data) {
                if (data.suggestedTopics && data.suggestedTopics.length > 0) {
                    this.topic = data.suggestedTopics[0];
                }
                this.count = data.defaultCount;
                this.examDuration = data.defaultDuration;
                this.examTitle = data.defaultTitle;
            }
        },

        applyPreset(preset) {
            this.count = preset.count;
            if (preset.duration) {
                this.examDuration = preset.duration;
            }
        },

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
                    count: parseInt(this.count) || 10,
                    exam_standard: this.examStandard,
                    mode: this.mode
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
                console.error('Error generating exam:', err);
                alert('Có lỗi khi sinh đề thi: ' + (err.message || 'Vui lòng kiểm tra lại'));
            });
        },

        removeQuestion(index) {
            this.questions.splice(index, 1);
        },

        saveQuestionsToBank() {
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
                    alert(res.message || 'Không thể lưu câu hỏi.');
                }
            })
            .catch(err => {
                this.saving = false;
                console.error('Error saving questions to bank:', err);
                alert('Lỗi khi lưu: ' + (err.message || 'Vui lòng thử lại'));
            });
        },

        saveAsExamSet() {
            if (!this.questions.length || this.saving) return;
            this.saving = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch('{{ route("teacher.ai_generator.save_exam") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    questions: this.questions,
                    title: this.examTitle,
                    duration_minutes: this.examDuration,
                    reward_coins: this.examCoins,
                    difficulty: this.difficulty,
                    description: this.examDescription,
                    is_published: this.examPublished
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
                this.openSaveExamModal = false;
                if (res.success) {
                    this.createdExam = {
                        title: res.exam_title,
                        key: res.exam_key,
                        url: res.exam_url,
                        count: res.question_count
                    };
                } else {
                    alert(res.message || 'Không thể tạo đề thi.');
                }
            })
            .catch(err => {
                this.saving = false;
                console.error('Error saving exam set:', err);
                alert('Lỗi khi tạo đề thi: ' + (err.message || 'Vui lòng thử lại'));
            });
        }
    };
}
</script>
@endsection
