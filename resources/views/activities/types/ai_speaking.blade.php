{{-- Activity Type: AI Speaking Drill --}}
<div class="space-y-5" x-data="inlineSpeakingApp()">
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 space-y-4 text-center">
        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">🎙️ Luyện phát âm AI</span>
        <p class="text-lg font-bold text-white leading-relaxed">"{{ $content['target_sentence'] ?? 'The quick brown fox jumps over the lazy dog.' }}"</p>
        
        @if(!empty($content['phonetic_guide']))
            <p class="text-xs text-fsel-teal font-mono">{{ $content['phonetic_guide'] }}</p>
        @endif

        <div class="pt-2 flex items-center justify-center gap-3">
            <button type="button" @click="toggleRecord()" class="px-6 py-3 rounded-full text-xs font-bold transition-all shadow-lg flex items-center gap-2"
                    :class="isRecording ? 'bg-red-500 text-white animate-pulse' : 'bg-emerald-600 hover:bg-emerald-500 text-white'">
                <span x-text="isRecording ? '⏹ Đang nghe... Bấm để dừng' : '🎙️ Bấm để đọc phát âm'"></span>
            </button>
        </div>

        <p x-show="recognizedText" class="text-xs text-gray-300 font-mono mt-2" x-text="'Đã nhận diện: ' + recognizedText"></p>
    </div>

    @include('activities._completion-button', ['label' => 'Hoàn thành bài luyện nói ✓'])
</div>

<script>
function inlineSpeakingApp() {
    return {
        isRecording: false,
        recognizedText: '',
        recognition: null,

        init() {
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SpeechRec) {
                this.recognition = new SpeechRec();
                this.recognition.lang = 'en-US';
                this.recognition.onresult = (e) => {
                    this.recognizedText = e.results[0][0].transcript;
                    this.isRecording = false;
                };
                this.recognition.onerror = () => { this.isRecording = false; };
            }
        },

        toggleRecord() {
            if (!this.recognition) {
                alert('Trình duyệt không hỗ trợ Web Speech API. Hãy nhập âm thanh trên Chrome!');
                return;
            }
            if (this.isRecording) {
                this.recognition.stop();
                this.isRecording = false;
            } else {
                this.recognizedText = '';
                this.recognition.start();
                this.isRecording = true;
            }
        }
    };
}
</script>
