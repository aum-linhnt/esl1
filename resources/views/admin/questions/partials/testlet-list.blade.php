{{-- Testlet Clusters Management Grid --}}
<div class="space-y-4">
    <div class="flex items-center justify-between bg-purple-950/20 p-4 rounded-xl border border-purple-900/40">
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>📚 Danh Sách Các Cụm Bài Đọc & Bài Nghe Lớn (Testlets)</span>
            </h2>
            <p class="text-xs text-gray-400 mt-0.5">
                Mỗi cụm là một bài đọc hoặc bài nghe dùng chung, phục vụ thi Thử phân Part và thi Thích ứng CAT neo cố định
            </p>
        </div>

        <button type="button" 
                @click="toggleTestletCreate()"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold flex items-center gap-2 shadow-lg shadow-purple-600/30 cursor-pointer">
            <span>+ Biên Soạn Cụm Mới</span>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($testletGroups as $t)
            <div class="card-dark p-5 border-slate-800 hover:border-purple-500/40 transition-all flex flex-col justify-between space-y-4 relative group">
                <div class="space-y-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ $t->skill === 'listening' ? '🎧' : '📖' }}</span>
                                <h3 class="text-sm font-bold text-white tracking-tight leading-snug">
                                    {{ $t->title }}
                                </h3>
                            </div>
                            <div class="flex items-center gap-2 mt-1 font-mono text-[11px]">
                                <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold border border-purple-500/30">
                                    Bậc {{ $t->difficulty }}
                                </span>
                                <span class="text-gray-400">
                                    Kỹ năng: <strong class="text-gray-200 capitalize">{{ $t->skill }}</strong>
                                </span>
                            </div>
                        </div>

                        <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-purple-600/20 text-purple-300 border border-purple-500/40 flex-shrink-0">
                            {{ $t->question_count }} câu hỏi
                        </span>
                    </div>

                    {{-- Passage Snippet --}}
                    <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800/80 text-xs text-gray-300 font-sans leading-relaxed line-clamp-4">
                        {{ $t->content }}
                    </div>

                    {{-- Sample Question --}}
                    <div class="text-[11px] text-gray-400 truncate">
                        ✦ Câu mở đầu: <strong class="text-gray-300 font-normal font-sans">{{ $t->sample_question }}</strong>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
                    <a href="{{ route('admin.questions.index', ['tab' => 'questions', 'search' => $t->title]) }}" 
                       class="text-xs font-bold text-blue-400 hover:text-blue-300 flex items-center gap-1 font-mono">
                        <span>Xem {{ $t->question_count }} câu trong cụm</span>
                    </a>

                    <div class="flex items-center gap-2">
                        {{-- Edit Passage Button --}}
                        <button type="button" 
                                @click="openEditTestletModal('{{ addslashes($t->title) }}', '{{ addslashes($t->content) }}', '{{ $t->difficulty }}')"
                                class="px-2.5 py-1 rounded bg-slate-800 text-amber-400 hover:text-amber-300 text-xs font-bold cursor-pointer">
                            ✏️ Sửa bài đọc
                        </button>

                        {{-- Delete Testlet Form --}}
                        <form method="POST" action="{{ route('admin.questions.destroyTestlet') }}" onsubmit="return confirm('XÁC NHẬN: Bạn có chắc muốn xóa Cụm bài đọc này cùng toàn bộ {{ $t->question_count }} câu hỏi con?')">
                            @csrf
                            <input type="hidden" name="passage_title" value="{{ $t->title }}">
                            <button type="submit" class="px-2.5 py-1 rounded bg-red-500/15 text-red-400 hover:bg-red-500/25 text-xs font-bold cursor-pointer">
                                🗑 Xóa cả cụm
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 p-12 text-center text-gray-500 card-dark border-slate-800">
                <span class="text-3xl block mb-2">📦</span>
                <span>Chưa có Cụm bài đọc nào trong ngân hàng. Hãy bấm nút "+ Biên Soạn Cụm Mới" ở trên để tạo cụm đầu tiên!</span>
            </div>
        @endforelse
    </div>
</div>
