{{-- ─── STUDIO FORM 1: BIÊN SOẠN CÂU HỎI ĐƠN LẺ ─── --}}
<div x-show="openSingleCreate" x-cloak x-collapse class="card-dark p-6 border-indigo-500/30 bg-[#121829] space-y-5 shadow-2xl">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div class="flex items-center gap-2.5">
            <span class="text-lg">➕</span>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                Biên Soạn Câu Hỏi Đơn Lẻ (Single Question Studio)
            </h3>
        </div>
        <button type="button" @click="openSingleCreate = false" class="text-gray-400 hover:text-white text-sm font-bold">&times; Đóng</button>
    </div>

    <form method="POST" action="{{ route('admin.questions.store') }}" enctype="multipart/form-data" class="space-y-4">

        @csrf
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Skill Dropdown --}}
            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Kỹ năng (Skill): <span class="text-red-400">*</span></label>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    skills: {
                        'reading': '📖 Đọc hiểu (Reading)',
                        'listening': '🎧 Nghe hiểu (Listening)',
                        'writing': '✍️ Viết (Writing)',
                        'speaking': '🎙️ Nói (Speaking)'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="skill" :value="singleSkill">
                    <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                        <span x-text="skills[singleSkill] || singleSkill" class="text-white truncate"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in skills" :key="val">
                            <div @click="singleSkill = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="singleSkill === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="singleSkill === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Difficulty Dropdown --}}
            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Cấp độ CEFR: <span class="text-red-400">*</span></label>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    selectedDiff: 'B1',
                    diffs: {
                        'A1': 'Level A1 (Căn bản)',
                        'A2': 'Level A2 (Tiền trung cấp)',
                        'B1': 'Level B1 (Trung cấp)',
                        'B2': 'Level B2 (Trung cao cấp)',
                        'C1': 'Level C1 (Cao cấp)',
                        'Mixed': 'Mixed (Tổng hợp)'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="difficulty" :value="selectedDiff">
                    <button type="button" @click="open = !open" class="login-input text-xs flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                        <span x-text="diffs[selectedDiff] || selectedDiff" class="text-white truncate"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-56 overflow-y-auto">
                        <template x-for="(lbl, val) in diffs" :key="val">
                            <div @click="selectedDiff = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="selectedDiff === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="selectedDiff === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Question Type Dropdown --}}
            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Dạng Câu Hỏi: <span class="text-red-400">*</span></label>
                <div :class="open ? 'relative z-50' : 'relative z-20'" x-data="{
                    open: false,
                    types: {
                        'mcq': '🔘 1. Trắc nghiệm 1 đáp án (Single Choice)',
                        'multiple_select': '☑️ 2. Trắc nghiệm chọn nhiều (Multiple Select)',
                        'fill_blank': '✍️ 3. Điền từ vào chỗ trống (Fill in the Blank)',
                        'word_ordering': '🔀 4. Sắp xếp từ thành câu (Word Ordering)',
                        'matching': '🔗 5. Nối cặp từ - nghĩa (Matching Pairs)',
                        'true_false': '⚖️ 6. True / False / Not Given',
                        'audio_listening': '🎧 7. Nghe Audio & Trả lời',
                        'pronunciation_speech': '🎙️ 8. Luyện phát âm giọng nói AI',
                        'essay_writing': '✍️ 9. Viết luận / Soạn thảo bài viết',
                        'audio_recording': '🎙️ 10. Ghi âm câu trả lời Speaking'
                    }
                }" @click.outside="open = false">
                    <input type="hidden" name="question_type" :value="singleQType">
                    <button type="button" @click="open = !open" class="login-input text-xs font-bold text-indigo-300 flex items-center justify-between cursor-pointer text-left w-full !py-2.5">
                        <span x-text="types[singleQType] || singleQType" class="truncate"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180 text-fsel-teal' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl py-1 overflow-hidden backdrop-blur-xl max-h-64 overflow-y-auto">
                        <template x-for="(lbl, val) in types" :key="val">
                            <div @click="singleQType = val; open = false" class="px-3 py-2 text-xs font-medium cursor-pointer transition-colors flex items-center justify-between hover:bg-indigo-600/30 hover:text-white" :class="singleQType === val ? 'bg-indigo-600/20 text-indigo-300 font-bold' : 'text-gray-300'">
                                <span x-text="lbl"></span>
                                <span x-show="singleQType === val" class="text-emerald-400 font-bold">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Optional Passage / Stimulus Section --}}
        <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-purple-300">✦ Đoạn văn đọc hiểu hoặc bối cảnh (Tùy chọn cho bài Đọc):</span>
                <span class="text-[10px] text-gray-500">Nếu câu hỏi thuộc bài đọc ngắn 1 câu</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <input type="text" name="passage_title" placeholder="Tiêu đề đoạn văn (VD: Notice: Library Hours)" class="login-input text-xs">
                </div>
                <div class="md:col-span-2">
                    <textarea name="passage_content" rows="2" placeholder="Nội dung đoạn văn hoặc bối cảnh câu hỏi..." class="login-input text-xs font-sans"></textarea>
                </div>
            </div>
        </div>

        {{-- Audio & Media Stimulus (For Listening / Illustrated Questions) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{
            audioPreview: null,
            imagePreview: null,
            handleAudioSelect(e) {
                const file = e.target.files[0];
                if (file) {
                    this.audioPreview = URL.createObjectURL(file);
                }
            },
            handleImageSelect(e) {
                const file = e.target.files[0];
                if (file) {
                    this.imagePreview = URL.createObjectURL(file);
                }
            }
        }">
            {{-- Audio File or URL --}}
            <div x-show="singleSkill === 'listening' || singleQType === 'audio_listening'" x-cloak class="space-y-2 p-3 rounded-xl bg-amber-950/20 border border-amber-500/30">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-amber-300">🎧 Tệp Âm thanh (Audio File / URL):</label>
                    <span class="text-[10px] text-amber-400 font-mono">MP3, WAV, M4A, OGG</span>
                </div>
                <div class="space-y-2">
                    <input type="file" name="audio_file" accept="audio/*" @change="handleAudioSelect($event)" 
                           class="block w-full text-xs text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-600/30 file:text-amber-200 hover:file:bg-amber-600/50 cursor-pointer">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-gray-500 font-bold uppercase">Hoặc dán URL:</span>
                        <input type="text" name="audio_url" placeholder="https://domain.com/audio.mp3" class="login-input text-xs font-mono !py-1">
                    </div>
                </div>
                <template x-if="audioPreview">
                    <div class="pt-1">
                        <span class="text-[10px] text-amber-300 font-semibold block mb-1">▶ Nghe thử file vừa chọn:</span>
                        <audio controls class="w-full h-8" :src="audioPreview"></audio>
                    </div>
                </template>
            </div>

            {{-- Illustration Image File --}}
            <div class="space-y-2 p-3 rounded-xl bg-indigo-950/20 border border-indigo-500/30">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-indigo-300">🖼️ Hình ảnh minh họa (Tùy chọn):</label>
                    <span class="text-[10px] text-indigo-400 font-mono">PNG, JPG, WEBP</span>
                </div>
                <div class="space-y-2">
                    <input type="file" name="image_file" accept="image/*" @change="handleImageSelect($event)" 
                           class="block w-full text-xs text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600/30 file:text-indigo-200 hover:file:bg-indigo-600/50 cursor-pointer">
                </div>
                <template x-if="imagePreview">
                    <div class="pt-1 flex items-center gap-3">
                        <img :src="imagePreview" class="w-16 h-16 object-cover rounded-lg border border-indigo-500/40">
                        <span class="text-[10px] text-indigo-300">Ảnh xem trước</span>
                    </div>
                </template>
            </div>
        </div>


        {{-- Question Text --}}
        <div>
            <label class="block text-xs font-bold text-gray-300 mb-1">Nội dung câu hỏi / Đề bài: <span class="text-red-400">*</span></label>
            <textarea name="question_text" rows="2" required placeholder="Nhập nội dung câu hỏi hoặc nhiệm vụ cần thực hiện..." class="login-input text-xs font-sans"></textarea>
        </div>

        {{-- ── MODULAR DYNAMIC CONTROLS BY QUESTION TYPE ── --}}
        @include('admin.questions.types.mcq')
        @include('admin.questions.types.multiple_select')
        @include('admin.questions.types.fill_blank')
        @include('admin.questions.types.word_ordering')
        @include('admin.questions.types.matching')
        @include('admin.questions.types.true_false')
        @include('admin.questions.types.essay_writing')
        @include('admin.questions.types.speaking')

        {{-- Explanation --}}
        <div>
            <label class="block text-xs font-bold text-gray-300 mb-1">Giải thích đáp án chi tiết & Trích dẫn dẫn chứng:</label>
            <textarea name="explanation" rows="2" placeholder="Giải thích vì sao đáp án này đúng, phân tích từ vựng hoặc chỉ ra dẫn chứng trong bài..." class="login-input text-xs"></textarea>
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" @click="openSingleCreate = false" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-400 hover:text-white">
                Hủy bỏ
            </button>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 flex items-center gap-2 cursor-pointer">
                <span>Lưu Câu Hỏi Vào Ngân Hàng</span>
                <span>✓</span>
            </button>
        </div>
    </form>
</div>
