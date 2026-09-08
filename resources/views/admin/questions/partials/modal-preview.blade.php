{{-- ─── MODAL PREVIEW QUESTION (XEM TRƯỚC CÂU HỎI) ──────────────── --}}
<div x-show="previewModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div @click.away="previewModal.open = false" 
         class="card-dark max-w-3xl w-full border-slate-800 p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto cbt-scrollbar animate-fadeIn">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-lg">👁️</span>
                <h3 class="text-sm font-bold text-white">Xem Trước Câu Hỏi #<span x-text="previewModal.data.id"></span></h3>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-500/20 text-blue-300" x-text="previewModal.data.difficulty"></span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-gray-300 uppercase" x-text="previewModal.data.skill"></span>
            </div>
            <button type="button" @click="previewModal.open = false" class="text-gray-400 hover:text-white text-base font-bold">&times;</button>
        </div>

        {{-- Passage Preview (If question has passage) --}}
        <template x-if="previewModal.data.passage_content">
            <div class="p-4 rounded-xl bg-purple-950/20 border border-purple-500/30 space-y-2">
                <span class="text-xs font-bold text-purple-300 block" x-text="previewModal.data.passage_title || 'Đoạn Văn Đọc Hiểu'"></span>
                <p class="text-xs text-gray-300 font-sans leading-relaxed whitespace-pre-line max-h-48 overflow-y-auto cbt-scrollbar pr-1"
                   x-text="previewModal.data.passage_content"></p>
            </div>
        </template>

        {{-- Audio Preview (If question has audio) --}}
        <template x-if="previewModal.data.audio_url">
            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center gap-3">
                <span class="text-sm">🎧</span>
                <audio controls class="w-full h-8" :src="previewModal.data.audio_url"></audio>
            </div>
        </template>

        {{-- Question Text --}}
        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-3">
            <div class="text-sm font-bold text-white leading-relaxed" x-text="previewModal.data.question_text"></div>

            {{-- Options List --}}
            <template x-if="previewModal.data.options && previewModal.data.options.length">
                <div class="space-y-2 pt-1">
                    <template x-for="(opt, oIdx) in previewModal.data.options" :key="'popt_' + oIdx">
                        <div class="p-2.5 rounded-lg border text-xs flex items-center gap-3 transition-colors"
                             :class="isOptionCorrect(opt, previewModal.data.correct_answer) ? 'bg-emerald-950/40 border-emerald-500/50 text-emerald-300 font-bold' : 'bg-slate-900 border-slate-800 text-gray-300'">
                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-mono"
                                  :class="isOptionCorrect(opt, previewModal.data.correct_answer) ? 'bg-emerald-500 text-white' : 'bg-slate-800 text-gray-400'">
                                <span x-text="isOptionCorrect(opt, previewModal.data.correct_answer) ? '✓' : ''"></span>
                            </span>
                            <span x-text="typeof opt === 'string' ? opt : JSON.stringify(opt)"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Correct Answer & Explanation --}}
        <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2 text-xs">
            <div>
                <span class="text-gray-400">Đáp án chính xác: </span>
                <strong class="text-emerald-400 font-mono" x-text="previewModal.data.correct_answer"></strong>
            </div>
            <template x-if="previewModal.data.explanation">
                <div class="pt-1 text-gray-300 border-t border-slate-800">
                    <span class="text-gray-500">💡 Giải thích: </span>
                    <span x-text="previewModal.data.explanation"></span>
                </div>
            </template>
        </div>

        <div class="flex justify-end pt-1">
            <button type="button" @click="previewModal.open = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold">
                Đóng
            </button>
        </div>
    </div>
</div>
