{{-- ─── MODAL EDIT SINGLE QUESTION (CHỈNH SỬA CÂU HỎI) ──────────── --}}
<div x-show="editModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div @click.away="editModal.open = false" 
         class="card-dark max-w-2xl w-full border-slate-800 p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto cbt-scrollbar animate-fadeIn">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-base">✏️</span>
                <h3 class="text-sm font-bold text-white">Chỉnh Sửa Câu Hỏi #<span x-text="editModal.data.id"></span></h3>
            </div>
            <button type="button" @click="editModal.open = false" class="text-gray-400 hover:text-white text-base font-bold">&times;</button>
        </div>

        <form :action="'/admin/questions/' + editModal.data.id" method="POST" enctype="multipart/form-data" class="space-y-3.5" x-data="{
            editAudioPreview: null,
            handleEditAudio(e) {
                const file = e.target.files[0];
                if (file) {
                    this.editAudioPreview = URL.createObjectURL(file);
                }
            }
        }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-300 mb-1">Cấp độ CEFR:</label>
                    <input type="hidden" name="difficulty" :value="editModal.data.difficulty">
                    <div class="grid grid-cols-6 gap-1">
                        <template x-for="lvl in ['A1', 'A2', 'B1', 'B2', 'C1', 'Mixed']" :key="lvl">
                            <button type="button" 
                                    @click="editModal.data.difficulty = lvl"
                                    :class="editModal.data.difficulty === lvl ? 'bg-indigo-600 text-white font-bold border-indigo-500 shadow' : 'bg-slate-900 text-gray-400 border-slate-700 hover:text-white'"
                                    class="py-2 px-1 rounded-lg border text-xs font-mono font-bold text-center transition-all">
                                <span x-text="lvl"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-300">Tệp Audio / URL:</label>
                    <input type="file" name="audio_file" accept="audio/*" @change="handleEditAudio($event)"
                           class="block w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-amber-600/30 file:text-amber-200 hover:file:bg-amber-600/50 cursor-pointer">
                    <input type="text" name="audio_url" x-model="editModal.data.audio_url" placeholder="Hoặc dán URL audio..." class="login-input text-xs font-mono !py-1">
                    <template x-if="editAudioPreview">
                        <div class="pt-1">
                            <audio controls class="w-full h-7" :src="editAudioPreview"></audio>
                        </div>
                    </template>
                </div>
            </div>


            {{-- Reading Passage Editor in Modal --}}
            <div class="p-3.5 rounded-xl bg-purple-950/20 border border-purple-500/30 space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-purple-300">
                        📖 Bài Đọc / Đoạn Văn Đọc Hiểu (Reading Passage)
                    </label>
                    <span class="text-[10px] text-purple-400 font-mono">Chỉnh sửa bài đọc đi kèm câu hỏi</span>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-300 mb-1">Tiêu đề bài đọc (Passage Title):</label>
                    <input type="text" 
                           name="passage_title" 
                           x-model="editModal.data.passage_title" 
                           placeholder="Ví dụ: Reading Passage 1: The Economics of Renewable Energy Transition" 
                           class="login-input text-xs font-bold text-purple-200">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-300 mb-1">Nội dung bài đọc (Passage Content):</label>
                    <textarea name="passage_content" 
                              rows="6" 
                              x-model="editModal.data.passage_content" 
                              placeholder="Nhập nội dung đoạn văn đọc hiểu (bỏ trống nếu câu hỏi không cần bài đọc)..." 
                              class="login-input text-xs font-sans leading-relaxed"></textarea>
                </div>

                <template x-if="editModal.data.passage_title">
                    <label class="flex items-center gap-2 pt-1 text-xs text-purple-300 cursor-pointer select-none">
                        <input type="checkbox" name="sync_passage_to_cluster" value="1" class="rounded border-purple-500 text-purple-600 focus:ring-purple-500">
                        <span>Đồng bộ tiêu đề & nội dung bài đọc này sang <strong>toàn bộ các câu hỏi khác trong cùng cụm</strong></span>
                    </label>
                </template>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Nội dung câu hỏi:</label>
                <textarea name="question_text" rows="2" x-model="editModal.data.question_text" required class="login-input text-xs font-sans"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Các lựa chọn trắc nghiệm (Mỗi dòng 1 lựa chọn):</label>
                <textarea name="options" rows="4" x-model="editModal.optionsText" class="login-input text-xs font-mono"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-emerald-400 mb-1">Đáp án đúng chính xác:</label>
                <input type="text" name="correct_answer" x-model="editModal.data.correct_answer" required class="login-input text-xs font-mono text-emerald-300 font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Giải thích đáp án:</label>
                <textarea name="explanation" rows="2" x-model="editModal.data.explanation" class="login-input text-xs"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" @click="editModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-400 hover:text-white">
                    Hủy bỏ
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md cursor-pointer">
                    Lưu Cập Nhật
                </button>
            </div>
        </form>
    </div>
</div>
