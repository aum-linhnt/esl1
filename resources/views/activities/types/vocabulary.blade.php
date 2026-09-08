{{-- Activity Type: Vocabulary Flashcards --}}
<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($content as $word)
            <div class="bg-fsel-navy/50 border border-fsel-border/30 rounded-xl p-4 hover:border-fsel-accent/40 transition-all flex flex-col justify-between">
                <div>
                    <div class="flex items-baseline justify-between mb-1">
                        <div class="flex items-baseline gap-2">
                            <span class="text-lg font-bold text-fsel-teal">{{ $word['word'] }}</span>
                            <span class="text-xs text-gray-400 font-mono">{{ $word['phonetic'] ?? '' }}</span>
                        </div>
                        <button onclick="speakWord('{{ addslashes($word['word']) }}')" title="Nghe phát âm" class="p-1.5 rounded-lg bg-fsel-dark hover:bg-slate-700 text-fsel-teal">
                            🔊
                        </button>
                    </div>
                    <p class="text-sm font-semibold text-white mb-2">{{ $word['meaning'] }}</p>
                    @if(!empty($word['example']))
                        <p class="text-xs text-gray-400 italic bg-fsel-dark/40 p-2 rounded-lg">"{{ $word['example'] }}"</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @include('activities._completion-button', ['label' => 'Đã học xong danh sách từ vựng ✓'])
</div>
