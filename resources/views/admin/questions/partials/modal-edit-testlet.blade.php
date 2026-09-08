{{-- ─── MODAL EDIT TESTLET PASSAGE (CHỈNH SỬA CẢ CỤM BÀI ĐỌC) ──── --}}
<div x-show="editTestletModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div @click.away="editTestletModal.open = false" 
         class="card-dark max-w-2xl w-full border-slate-800 p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto cbt-scrollbar animate-fadeIn">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-base">📦</span>
                <h3 class="text-sm font-bold text-white">Chỉnh Sửa Văn Bản Cụm Bài Đọc</h3>
            </div>
            <button type="button" @click="editTestletModal.open = false" class="text-gray-400 hover:text-white text-base font-bold">&times;</button>
        </div>

        <form action="{{ route('admin.questions.updateTestlet') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="old_passage_title" x-model="editTestletModal.oldTitle">

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Tiêu đề bài đọc:</label>
                <input type="text" name="passage_title" x-model="editTestletModal.title" required class="login-input text-xs font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Cấp độ CEFR mục tiêu cho cả cụm:</label>
                <input type="hidden" name="difficulty" :value="editTestletModal.difficulty">
                <div class="grid grid-cols-5 gap-1.5">
                    <template x-for="lvl in ['A1', 'A2', 'B1', 'B2', 'C1']" :key="lvl">
                        <button type="button" 
                                @click="editTestletModal.difficulty = lvl"
                                :class="editTestletModal.difficulty === lvl ? 'bg-purple-600 text-white font-bold border-purple-500 shadow' : 'bg-slate-900 text-gray-400 border-slate-700 hover:text-white'"
                                class="py-2 px-1 rounded-lg border text-xs font-mono font-bold text-center transition-all">
                            <span x-text="lvl"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-300 mb-1">Nội dung văn bản đoạn văn (Passage Content):</label>
                <textarea name="passage_content" rows="10" x-model="editTestletModal.content" required class="login-input text-xs font-sans leading-relaxed"></textarea>
                <span class="text-[10px] text-gray-500 mt-1 block">Nội dung mới sẽ được tự động đồng bộ hóa sang toàn bộ các câu hỏi con thuộc cụm này.</span>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" @click="editTestletModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-400 hover:text-white">
                    Hủy
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md cursor-pointer">
                    Đồng Bộ Sang Toàn Bộ Cụm
                </button>
            </div>
        </form>
    </div>
</div>
