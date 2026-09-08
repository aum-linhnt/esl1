{{-- Speaking & Pronunciation Question Module --}}
<div x-show="singleQType === 'pronunciation_speech' || singleQType === 'audio_recording'" class="space-y-3" x-cloak>
    <div class="p-3.5 rounded-xl bg-amber-950/20 border border-amber-500/30 space-y-2">
        <div class="flex items-center gap-2 text-amber-300 font-bold text-xs">
            <span>🎙️</span>
            <span>Cấu hình câu hỏi Nói / Luyện phát âm AI</span>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-300 mb-1">
                Văn bản mẫu / Câu cần luyện phát âm chính xác (Target Sentence):
            </label>
            <input type="text" name="correct_answer" placeholder="Ví dụ: Good morning, how can I help you today?" class="login-input text-xs font-sans text-amber-200 font-semibold">
            <p class="text-[10px] text-gray-500 mt-1">Hệ thống AI Speech Recognition sẽ so khớp giọng phát âm của học viên với văn bản mẫu này.</p>
        </div>
    </div>
</div>
