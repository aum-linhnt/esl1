{{-- ─── STUDIO FORM 2: BIÊN SOẠN CỤM BÀI ĐỌC (TESTLET STUDIO) ─── --}}
<div x-show="openTestletCreate" x-cloak x-collapse class="card-dark p-6 border-purple-500/40 bg-[#161226] space-y-5 shadow-2xl">
    <div class="flex items-center justify-between border-b border-purple-900/40 pb-3">
        <div class="flex items-center gap-2.5">
            <span class="text-xl">📦</span>
            <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                    Studio Biên Soạn Cụm Bài Đọc / Bài Nghe Đa Câu Hỏi (Testlet Studio)
                </h3>
                <p class="text-[11px] text-purple-300">
                    Nhập 1 văn bản bài đọc (hoặc audio) và tạo trọn bộ 3 – 10 câu hỏi con gắn liền với bài đọc đó chỉ trong 1 lần lưu!
                </p>
            </div>
        </div>
        <button type="button" @click="openTestletCreate = false" class="text-gray-400 hover:text-white text-sm font-bold">&times; Đóng</button>
    </div>

    <form method="POST" action="{{ route('admin.questions.storeTestlet') }}" enctype="multipart/form-data" class="space-y-6">

        @csrf

        {{-- Stimulus Area: Left-Right Split for Easy Authoring --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            
            {{-- LEFT COLUMN: PASSAGE / AUDIO STIMULUS --}}
            <div class="lg:col-span-5 space-y-3.5 bg-slate-900/60 p-4 rounded-2xl border border-purple-500/20">
                <h4 class="text-xs font-bold text-purple-300 uppercase tracking-wider flex items-center gap-2">
                    <span>1. Văn Bản Bài Đọc Dùng Chung (Stimulus)</span>
                </h4>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Kỹ năng: <span class="text-red-400">*</span></label>
                        <div class="relative" x-data="{
                            open: false,
                            selected: 'reading',
                            options: {
                                'reading': '📖 Đọc (Reading)',
                                'listening': '🎧 Nghe (Listening)'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="skill" :value="selected">
                            <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="options[selected] || selected" class="text-white truncate"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-300 mb-1">Bậc CEFR: <span class="text-red-400">*</span></label>
                        <div class="relative" x-data="{
                            open: false,
                            selected: 'B2',
                            options: {
                                'A1': 'A1 (Căn bản)',
                                'A2': 'A2 (Tiền trung cấp)',
                                'B1': 'B1 (Trung cấp)',
                                'B2': 'B2 (Trung cao cấp)',
                                'C1': 'C1 (Cao cấp)'
                            }
                        }" @click.outside="open = false">
                            <input type="hidden" name="difficulty" :value="selected">
                            <button type="button" @click="open = !open" class="login-input text-xs font-bold text-purple-300 flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                                <span x-text="options[selected] || selected" class="truncate"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl">
                                <template x-for="(lbl, val) in options" :key="val">
                                    <div @click="selected = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selected === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                        <span x-text="lbl"></span>
                                        <span x-show="selected === val" class="text-emerald-400 font-bold">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Tiêu đề bài đọc (Passage Title): <span class="text-red-400">*</span></label>
                    <input type="text" name="passage_title" required placeholder="Ví dụ: Reading Passage: The Future of Quantum Computing" class="login-input text-xs font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Nội dung bài đọc hoàn chỉnh (Passage Content): <span class="text-red-400">*</span></label>
                    <textarea name="passage_content" rows="12" required placeholder="Dán văn bản bài đọc học thuật tại đây (300 - 600 từ)... Văn bản này sẽ được neo cố định bên cột trái cho thí sinh làm cả cụm câu hỏi." class="login-input text-xs font-sans leading-relaxed"></textarea>
                </div>

                <div class="space-y-2 p-3 rounded-xl bg-purple-950/30 border border-purple-500/30" x-data="{
                    testletAudioPreview: null,
                    handleTestletAudio(e) {
                        const file = e.target.files[0];
                        if (file) {
                            this.testletAudioPreview = URL.createObjectURL(file);
                        }
                    }
                }">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-purple-300">🎧 Tệp Audio bài nghe (Tùy chọn):</label>
                        <span class="text-[10px] text-purple-400 font-mono">MP3, WAV, M4A</span>
                    </div>
                    <input type="file" name="audio_file" accept="audio/*" @change="handleTestletAudio($event)"
                           class="block w-full text-xs text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-600/30 file:text-purple-200 hover:file:bg-purple-600/50 cursor-pointer">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-gray-500 font-bold uppercase">Hoặc URL:</span>
                        <input type="text" name="audio_url" placeholder="https://example.com/audio/passage-01.mp3" class="login-input text-xs font-mono !py-1">
                    </div>
                    <template x-if="testletAudioPreview">
                        <div class="pt-1">
                            <span class="text-[10px] text-purple-300 font-semibold block mb-1">▶ Nghe thử audio đã chọn:</span>
                            <audio controls class="w-full h-8" :src="testletAudioPreview"></audio>
                        </div>
                    </template>
                </div>

            </div>

            {{-- RIGHT COLUMN: DYNAMIC SUB-QUESTIONS ARRAY --}}
            <div class="lg:col-span-7 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <span>2. Danh Sách Câu Hỏi Con Của Cụm</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-purple-500/20 text-purple-300 border border-purple-500/40" 
                                  x-text="testletQuestions.length + ' câu hỏi'"></span>
                        </h4>
                        <p class="text-[11px] text-gray-400">Mỗi câu hỏi gồm 4 lựa chọn A, B, C, D và chọn 1 đáp án đúng</p>
                    </div>

                    <button type="button" 
                            @click="addTestletQuestion()" 
                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 shadow-md cursor-pointer transition-all">
                        <span>+ Thêm câu hỏi con</span>
                    </button>
                </div>

                {{-- Questions Container Scrollable --}}
                <div class="space-y-3.5 max-h-[520px] overflow-y-auto pr-1 cbt-scrollbar">
                    <template x-for="(q, qIdx) in testletQuestions" :key="'tq_' + qIdx">
                        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-3 relative group hover:border-purple-500/40 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-purple-600/30 text-purple-300 border border-purple-500/40 flex items-center justify-center text-xs font-bold font-mono" 
                                          x-text="qIdx + 1"></span>
                                    <span class="text-xs font-bold text-white" x-text="'Câu hỏi số ' + (qIdx + 1)"></span>
                                </div>

                                <button type="button" 
                                        x-show="testletQuestions.length > 1"
                                        @click="removeTestletQuestion(qIdx)" 
                                        class="text-gray-500 hover:text-red-400 text-xs font-bold transition-colors cursor-pointer px-2 py-0.5 rounded hover:bg-red-500/10">
                                    &times; Xóa câu này
                                </button>
                            </div>

                            {{-- Question Text --}}
                            <div>
                                <input type="text" 
                                       :name="'questions[' + qIdx + '][question_text]'" 
                                       x-model="q.question_text" 
                                       required 
                                       placeholder="Nhập nội dung câu hỏi..." 
                                       class="login-input text-xs font-medium">
                            </div>

                            {{-- 4 Options A, B, C, D --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-purple-400 font-mono text-xs w-4">A.</span>
                                    <input type="text" :name="'questions[' + qIdx + '][opt_a]'" x-model="q.opt_a" required placeholder="Nội dung lựa chọn A" class="login-input text-xs">
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-purple-400 font-mono text-xs w-4">B.</span>
                                    <input type="text" :name="'questions[' + qIdx + '][opt_b]'" x-model="q.opt_b" required placeholder="Nội dung lựa chọn B" class="login-input text-xs">
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-purple-400 font-mono text-xs w-4">C.</span>
                                    <input type="text" :name="'questions[' + qIdx + '][opt_c]'" x-model="q.opt_c" required placeholder="Nội dung lựa chọn C" class="login-input text-xs">
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-purple-400 font-mono text-xs w-4">D.</span>
                                    <input type="text" :name="'questions[' + qIdx + '][opt_d]'" x-model="q.opt_d" required placeholder="Nội dung lựa chọn D" class="login-input text-xs">
                                </div>
                            </div>

                            {{-- Correct Answer & Explanation --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                                <div>
                                    <label class="block text-[11px] font-bold text-emerald-400 mb-1">Đáp án đúng:</label>
                                    <input type="hidden" :name="'questions[' + qIdx + '][correct_answer]'" :value="q.correct_answer">
                                    <div class="grid grid-cols-4 gap-1">
                                        <template x-for="opt in ['A', 'B', 'C', 'D']" :key="opt">
                                            <button type="button" 
                                                    @click="q.correct_answer = opt"
                                                    :class="q.correct_answer === opt ? 'bg-emerald-600 text-white font-bold border-emerald-500 shadow-md shadow-emerald-900/30' : 'bg-slate-900 text-gray-400 border-slate-700 hover:text-white'"
                                                    class="py-1.5 px-2 rounded-lg border text-xs font-mono font-bold text-center transition-all">
                                                <span x-text="opt"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-400 mb-1">Giải thích chi tiết (Tùy chọn):</label>
                                    <input type="text" :name="'questions[' + qIdx + '][explanation]'" x-model="q.explanation" placeholder="Dẫn chứng trong bài đọc hoặc giải thích từ vựng..." class="login-input text-xs">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Bottom Testlet Actions --}}
                <div class="flex items-center justify-between pt-2 border-t border-purple-900/40">
                    <button type="button" 
                            @click="addTestletQuestion()" 
                            class="text-xs font-bold text-purple-300 hover:text-white flex items-center gap-1.5 cursor-pointer">
                        <span>+ Thêm câu hỏi con</span>
                    </button>

                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 flex items-center gap-2 cursor-pointer">
                        <span>Lưu Trọn Bộ Cụm Testlet (<span x-text="testletQuestions.length"></span> câu)</span>
                        <span>✓</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
