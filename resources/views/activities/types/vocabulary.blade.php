{{-- Activity Type: Vocabulary Flashcards --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        @foreach($content as $word)
            <div class="vocab-card group bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xs hover:border-teal-500/50 hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    {{-- Header: Word, Phonetic, and Audio Speaker Button --}}
                    <div class="flex items-start justify-between gap-3 mb-2.5">
                        <div class="space-y-1">
                            <h3 class="vocab-word text-lg sm:text-xl font-extrabold text-teal-600 dark:text-teal-400 tracking-tight">
                                {{ $word['word'] }}
                            </h3>
                            @if(!empty($word['phonetic']))
                                <span class="vocab-phonetic inline-block text-[11px] sm:text-xs text-slate-500 dark:text-gray-400 font-mono bg-slate-100 dark:bg-slate-800/80 px-2.5 py-0.5 rounded-md border border-slate-200/70 dark:border-slate-700/60">
                                    {{ $word['phonetic'] }}
                                </span>
                            @endif
                        </div>
                        <button type="button"
                                onclick="speakWord('{{ addslashes($word['word']) }}')" 
                                title="Nghe phát âm '{{ $word['word'] }}'" 
                                class="vocab-speaker-btn p-2.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-teal-400 border border-teal-200/70 dark:border-slate-700 shadow-2xs hover:scale-105 active:scale-95 transition-all cursor-pointer shrink-0"
                                aria-label="Phát âm">
                            <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Meaning --}}
                    <p class="vocab-meaning text-sm sm:text-base font-bold text-slate-800 dark:text-white mb-3 leading-snug">
                        {{ $word['meaning'] }}
                    </p>

                    {{-- Example Sentence --}}
                    @if(!empty($word['example']))
                        <div class="vocab-example bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 rounded-xl p-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 italic leading-relaxed flex items-start gap-2">
                            <span class="text-teal-500 font-serif font-black text-sm leading-none shrink-0 select-none">“</span>
                            <span class="flex-1">{{ $word['example'] }}</span>
                            <span class="text-teal-500 font-serif font-black text-sm leading-none shrink-0 select-none">”</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @include('activities._completion-button', ['label' => 'Đã học xong danh sách từ vựng ✓'])
</div>

<style>
/* Scoped Light Mode Overrides for Vocabulary Flashcards */
html.light .vocab-card {
    background-color: #ffffff !important;
    border-color: #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
}
html.light .vocab-card:hover {
    border-color: #0d9488 !important;
    box-shadow: 0 6px 16px -2px rgba(13, 148, 136, 0.12) !important;
}
html.light .vocab-word {
    color: #0d9488 !important;
}
html.light .vocab-phonetic {
    background-color: #f1f5f9 !important;
    color: #64748b !important;
    border-color: #e2e8f0 !important;
}
html.light .vocab-speaker-btn {
    background-color: #f0fdfa !important;
    border-color: #ccfbf1 !important;
    color: #0d9488 !important;
}
html.light .vocab-speaker-btn:hover {
    background-color: #ccfbf1 !important;
    color: #0f766e !important;
}
html.light .vocab-meaning {
    color: #0f172a !important;
}
html.light .vocab-example {
    background-color: #f8fafc !important;
    border-color: #e2e8f0 !important;
    color: #334155 !important;
}
</style>
