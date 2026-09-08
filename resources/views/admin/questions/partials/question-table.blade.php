{{-- Questions Table View --}}
<div class="card-dark overflow-hidden border-slate-800 shadow-xl relative z-10">
    {{-- Bulk Actions Toolbar --}}
    <div x-show="selectedQuestions.length > 0" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="bg-indigo-950/90 border-b border-indigo-500/30 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs"
         style="display: none;">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white font-mono text-xs font-bold" x-text="selectedQuestions.length"></span>
            <span class="text-indigo-200 font-medium">
                Đang chọn <b class="text-white font-bold" x-text="selectedQuestions.length"></b> câu hỏi
            </span>
            <button type="button" 
                    @click="clearSelection()"
                    class="text-xs text-indigo-300 hover:text-white underline cursor-pointer">
                Bỏ chọn tất cả
            </button>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    @click="executeBulkDelete()"
                    :disabled="bulkDeleting"
                    class="px-3.5 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/30 flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50">
                <template x-if="!bulkDeleting">
                    <span class="flex items-center gap-1.5">
                        <span>🗑️</span>
                        <span>Xóa <span x-text="selectedQuestions.length"></span> câu hỏi đã chọn</span>
                    </span>
                </template>
                <template x-if="bulkDeleting">
                    <span class="flex items-center gap-1.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Đang xóa...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-gray-300">
            <thead class="bg-slate-900/90 text-gray-400 uppercase font-mono text-[10px] border-b border-slate-800">
                <tr>
                    {{-- Select All Checkbox --}}
                    <th class="px-3 py-3.5 w-10 text-center">
                        <input type="checkbox" 
                               @change="toggleSelectAll($event)" 
                               :checked="isAllSelected"
                               class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 cursor-pointer"
                               title="Chọn tất cả câu hỏi trên trang này">
                    </th>
                    <th class="px-4 py-3.5 w-14">ID</th>
                    <th class="px-4 py-3.5 w-28">Kỹ năng / CEFR</th>
                    <th class="px-4 py-3.5 w-32">Dạng câu hỏi</th>
                    <th class="px-4 py-3.5">Nội dung câu hỏi & Lựa chọn</th>
                    <th class="px-4 py-3.5 w-44">Đáp án chính xác</th>
                    <th class="px-4 py-3.5 text-right w-36">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($questions as $q)
                    @php
                        $meta = $q->meta_data ?? [];
                        $hasPassage = !empty($meta['passage_title']);
                    @endphp
                    <tr :class="selectedQuestions.includes({{ $q->id }}) ? 'bg-indigo-950/30 hover:bg-indigo-900/40' : 'hover:bg-slate-900/40'"
                        class="transition-colors">
                        {{-- Row Checkbox --}}
                        <td class="px-3 py-3.5 text-center">
                            <input type="checkbox" 
                                   :value="{{ $q->id }}" 
                                   x-model.number="selectedQuestions"
                                   class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 cursor-pointer">
                        </td>

                        {{-- ID --}}
                        <td class="px-4 py-3.5 font-mono text-[11px] text-gray-500 font-bold">
                            #{{ $q->id }}
                        </td>

                        {{-- Skill / CEFR --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-block px-2 py-0.5 rounded font-mono font-bold text-[10px] 
                                {{ match($q->difficulty) {
                                    'A1' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                                    'A2' => 'bg-teal-500/20 text-teal-300 border border-teal-500/30',
                                    'B1' => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
                                    'B2' => 'bg-purple-500/20 text-purple-300 border border-purple-500/30',
                                    'C1' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                                    default => 'bg-slate-700 text-gray-300'
                                } }}">
                                {{ $q->difficulty }}
                            </span>
                            <span class="text-[11px] text-gray-400 capitalize block mt-1 font-medium">
                                {{ match($q->skill) {
                                    'listening' => '🎧 Nghe',
                                    'reading' => '📖 Đọc',
                                    'writing' => '✍️ Viết',
                                    'speaking' => '🎙️ Nói',
                                    default => $q->skill
                                } }}
                            </span>
                        </td>

                        {{-- Type & Testlet Badge --}}
                        <td class="px-4 py-3.5 space-y-1">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-800 text-gray-300 border border-slate-700 inline-block">
                                {{ $q->question_type }}
                            </span>

                            @if($hasPassage)
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-purple-950/80 text-purple-300 border border-purple-600/40 block truncate max-w-[130px]"
                                      title="{{ $meta['passage_title'] }}">
                                    📦 {{ Str::limit($meta['passage_title'], 18) }}
                                </span>
                            @endif
                        </td>

                        {{-- Content & Options --}}
                        <td class="px-4 py-3.5 max-w-md">
                            <div class="font-semibold text-white leading-relaxed line-clamp-2">
                                {{ $q->question_text }}
                            </div>

                            @if(!empty($q->options) && is_array($q->options))
                                <div class="text-[11px] text-gray-400 mt-1 flex flex-wrap gap-1.5 font-mono">
                                    @foreach(array_slice($q->options, 0, 4) as $opt)
                                        <span class="bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800 text-gray-400 truncate max-w-[160px]">
                                            {{ is_string($opt) ? $opt : json_encode($opt) }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        {{-- Correct Answer --}}
                        <td class="px-4 py-3.5">
                            <div class="text-xs text-emerald-400 font-mono font-bold leading-relaxed break-words">
                                ✓ {{ Str::limit($q->correct_answer, 40) }}
                            </div>
                            @if($q->explanation)
                                <div class="text-[10px] text-gray-500 mt-0.5 truncate max-w-xs" title="{{ $q->explanation }}">
                                    💡 {{ $q->explanation }}
                                </div>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Preview Button --}}
                                <button type="button" 
                                        @click="openPreviewModal({{ $q->id }})"
                                        class="px-2 py-1 rounded bg-slate-800 text-blue-400 hover:text-blue-300 text-[11px] font-bold transition-colors cursor-pointer"
                                        title="Xem trước câu hỏi">
                                    👁️
                                </button>

                                {{-- Edit Button --}}
                                <button type="button" 
                                        @click="openEditModal({{ $q->id }})"
                                        class="px-2 py-1 rounded bg-slate-800 text-amber-400 hover:text-amber-300 text-[11px] font-bold transition-colors cursor-pointer"
                                        title="Chỉnh sửa câu hỏi">
                                    ✏️
                                </button>

                                {{-- Delete Button --}}
                                <form method="POST" action="{{ route('admin.questions.destroy', $q->id) }}" onsubmit="return confirm('Xác nhận xóa câu hỏi #{{ $q->id }} khỏi ngân hàng?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 rounded bg-red-500/15 text-red-400 hover:bg-red-500/25 text-[11px] font-bold transition-colors" title="Xóa câu hỏi">
                                        🗑
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-500">
                            <span class="text-2xl block mb-2">🔍</span>
                            <span>Không tìm thấy câu hỏi nào phù hợp với bộ lọc.</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($questions->hasPages())
        <div class="px-5 py-3.5 border-t border-slate-800 bg-slate-900/40">
            {{ $questions->links() }}
        </div>
    @endif
</div>
