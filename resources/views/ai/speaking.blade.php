@extends('layouts.app')

@section('content')
<div class="w-full space-y-6" x-data="aiSpeakingApp()">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-gradient-to-r from-teal-500 to-emerald-500 text-white text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full shadow-glow-teal">
                    AI Speech Assessment
                </span>
                <span class="text-xs text-gray-400">Chấm điểm phát âm chuẩn Quốc tế & Phân tích âm vị IPA</span>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Luyện Nói & Chấm Điểm AI Speaking</h2>
        </div>

        <div class="flex items-center gap-2 text-xs text-gray-400 bg-slate-900/90 border border-slate-800 px-3.5 py-2 rounded-xl">
            <span>Thưởng luyện nói:</span>
            <span class="text-teal-300 font-bold flex items-center gap-1 font-mono">
                ⚡ +10 XP & 🪙 +2 Coins
            </span>
        </div>
    </div>

    {{-- Practice Studio Card --}}
    <div class="card-dark p-6 lg:p-8 space-y-6 text-center">
        {{-- Level & Sentence Selector --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
            <span class="text-xs text-gray-400 font-medium">Chọn câu mẫu để luyện:</span>
            <div class="flex flex-wrap items-center justify-center gap-2">
                @foreach($practiceSentences as $idx => $s)
                    <button
                        @click="selectSentence({{ $idx }})"
                        type="button"
                        class="text-xs px-3 py-1.5 rounded-xl font-mono font-bold transition-all cursor-pointer"
                        :class="selectedIndex === {{ $idx }} ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-1 ring-indigo-400' : 'bg-slate-800/80 text-gray-400 hover:text-white border border-slate-700/60'">
                        {{ $s['level'] }} #{{ $idx + 1 }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Target Sentence Display --}}
        <div class="py-4 space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/60 border border-slate-700 text-xs text-gray-400 mb-1">
                <span>Trình độ:</span>
                <span class="font-bold text-indigo-300 font-mono" x-text="currentSentence.level"></span>
            </div>
            <p class="text-xl lg:text-3xl font-black text-white leading-relaxed tracking-wide select-all" x-text="currentSentence.text"></p>
            <p class="text-xs lg:text-sm text-fsel-teal font-mono tracking-wider" x-text="currentSentence.phonetic"></p>
            
            {{-- Listen Model Audio Button --}}
            <div class="pt-2">
                <button
                    @click="playModelAudio()"
                    type="button"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-gray-300 hover:text-white bg-slate-800/80 hover:bg-slate-700 border border-slate-700 px-4 py-2 rounded-full transition-all cursor-pointer">
                    <svg class="w-4 h-4 text-fsel-teal" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
                    <span>Nghe giọng đọc mẫu (Native Audio)</span>
                </button>
            </div>
        </div>

        {{-- Audio Recording Controls --}}
        <div class="py-6 border-t border-slate-800 flex flex-col items-center gap-4">
            {{-- Big Mic Button --}}
            <button
                @click="toggleRecording()"
                type="button"
                class="w-20 h-20 rounded-full flex items-center justify-center transition-all duration-300 shadow-xl cursor-pointer"
                :class="isRecording ? 'bg-red-500 shadow-glow-red animate-pulse scale-110 ring-4 ring-red-500/40' : 'bg-gradient-to-tr from-teal-500 via-indigo-600 to-blue-600 hover:scale-105 shadow-glow-blue'">
                <svg x-show="!isRecording" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                </svg>
                <svg x-show="isRecording" class="w-8 h-8 text-white animate-bounce" fill="currentColor" viewBox="0 0 24 24">
                    <rect x="6" y="6" width="12" height="12" rx="2" />
                </svg>
            </button>

            {{-- Recording State Message & Timer --}}
            <div class="space-y-1">
                <p class="text-xs font-medium text-gray-300" x-text="isRecording ? '🎙️ Đang thu âm... Hãy đọc câu trên thật rõ ràng!' : (recordedAudioUrl ? '✓ Đã thu âm xong. Bạn có thể nghe lại hoặc gửi chấm điểm!' : 'Bấm micro để bắt đầu thu âm')"></p>
                <div x-show="isRecording" class="flex items-center justify-center gap-1.5 text-xs text-red-400 font-mono font-bold">
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                    <span x-text="formatTime(recordingSeconds)"></span>
                </div>
            </div>

            {{-- Audio Playback Preview (when recorded) --}}
            <div x-show="recordedAudioUrl && !isRecording" x-cloak class="w-full max-w-md p-3.5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span class="font-bold flex items-center gap-1.5 text-indigo-300">
                        <span>🎧</span>
                        <span>Bản ghi âm giọng của bạn:</span>
                    </span>
                    <button type="button" @click="toggleRecording()" class="text-xs text-gray-400 hover:text-white underline cursor-pointer">
                        Thu âm lại
                    </button>
                </div>
                <audio x-ref="audioPlayer" :src="recordedAudioUrl" controls class="w-full h-9 rounded-lg"></audio>
            </div>

            {{-- Fallback Live Transcript Preview --}}
            <div x-show="recognizedText && isRecording" class="w-full max-w-lg p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs text-left">
                <span class="text-gray-500 block text-[10px] uppercase font-bold mb-0.5">Lời thoại nhận diện tạm:</span>
                <p class="text-white font-medium italic" x-text="recognizedText"></p>
            </div>

            {{-- Evaluate Button --}}
            <div x-show="(recordedAudioBlob || recognizedText) && !isRecording" class="pt-2">
                <button
                    @click="evaluateSpeech()"
                    :disabled="evaluating"
                    type="button"
                    class="btn-primary !w-auto !py-3 px-8 text-sm font-bold flex items-center gap-2 mx-auto cursor-pointer shadow-xl shadow-indigo-600/30">
                    <span x-show="!evaluating">✨ Phân Tích & Chấm Điểm Phát Âm AI</span>
                    <span x-show="evaluating" class="flex items-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>Đang phân tích âm vị & ngữ điệu (AI Processing)...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Error Alert --}}
    <div x-show="errorMessage" x-cloak class="p-4 rounded-xl bg-red-500/15 border border-red-500/30 text-red-300 text-xs flex items-center justify-between">
        <span x-text="errorMessage"></span>
        <button @click="errorMessage = ''" class="text-red-400 hover:text-white">&times;</button>
    </div>

    {{-- Results Dashboard Section --}}
    <div x-show="result" x-cloak style="display: none;" class="card-dark p-6 lg:p-8 space-y-6">
        
        {{-- Section Title & Time --}}
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-lg font-black text-white flex items-center gap-2">
                <span>🎯 Kết Quả Đánh Giá Phát Âm AI</span>
            </h3>
            <span class="text-[11px] font-mono text-gray-500" x-text="'Thời gian xử lý: ' + (result?.processing_time_ms ? Math.round(result.processing_time_ms) + 'ms' : 'N/A')"></span>
        </div>

        {{-- Hero Scores Card --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Overall Score --}}
            <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-900/30 via-slate-900 to-slate-900 border border-indigo-500/30 text-center flex flex-col items-center justify-center space-y-1">
                <span class="text-xs uppercase font-bold text-gray-400 tracking-wider">Điểm tổng quát</span>
                <div class="text-4xl lg:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-indigo-300 font-mono" x-text="result?.score + '/100'"></div>
                <div class="pt-1">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase font-mono"
                          :class="result?.score >= 80 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : (result?.score >= 60 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30')"
                          x-text="result?.score >= 80 ? '✓ Xuất sắc' : (result?.score >= 60 ? '⚡ Đạt yêu cầu' : '⚠ Cần luyện lại')">
                    </span>
                </div>
            </div>

            {{-- IELTS & CEFR Level --}}
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 flex flex-col justify-center space-y-3">
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Ước tính IELTS Band:</span>
                    <span class="text-xl font-extrabold text-white font-mono" x-text="result?.ielts_cefr?.ielts_band || '6.0 - 6.5'"></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Khung Năng Lực CEFR:</span>
                    <span class="text-sm font-bold text-indigo-400" x-text="result?.ielts_cefr?.level_title || (result?.ielts_cefr?.cefr_level + ' Level')"></span>
                </div>
            </div>

            {{-- Summary Note --}}
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 flex flex-col justify-center text-xs text-gray-300 leading-relaxed">
                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-1">Nhận xét tổng thể:</span>
                <p x-text="result?.ielts_cefr?.summary || result?.feedback"></p>
            </div>
        </div>

        {{-- 4 Core Competency Progress Bars --}}
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
            <h4 class="text-xs font-bold text-gray-300 uppercase tracking-wider">Chi Tiết 4 Tiêu Chí Chấm Điểm:</h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- 1. Accuracy --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-300 font-semibold flex items-center gap-1.5">
                            <span>🎯</span>
                            <span>Độ chuẩn xác âm vị (Accuracy)</span>
                        </span>
                        <span class="font-mono font-bold text-teal-300" x-text="result?.accuracy + '%'"></span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-teal-500 rounded-full transition-all duration-700" :style="'width: ' + Math.min(100, Math.max(0, result?.accuracy || 0)) + '%'"></div>
                    </div>
                </div>

                {{-- 2. Fluency --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-300 font-semibold flex items-center gap-1.5">
                            <span>⚡</span>
                            <span>Độ lưu loát & Nhịp điệu (Fluency)</span>
                        </span>
                        <span class="font-mono font-bold text-blue-300" x-text="result?.fluency + '%'"></span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-700" :style="'width: ' + Math.min(100, Math.max(0, result?.fluency || 0)) + '%'"></div>
                    </div>
                </div>

                {{-- 3. Completeness --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-300 font-semibold flex items-center gap-1.5">
                            <span>📖</span>
                            <span>Độ đầy đủ từ ngữ (Completeness)</span>
                        </span>
                        <span class="font-mono font-bold text-purple-300" x-text="result?.completeness + '%'"></span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 rounded-full transition-all duration-700" :style="'width: ' + Math.min(100, Math.max(0, result?.completeness || 0)) + '%'"></div>
                    </div>
                </div>

                {{-- 4. Prosody --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-300 font-semibold flex items-center gap-1.5">
                            <span>🎶</span>
                            <span>Ngữ điệu & Trọng âm (Prosody)</span>
                        </span>
                        <span class="font-mono font-bold text-amber-300" x-text="result?.prosody_score + '%'"></span>
                    </div>
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-full transition-all duration-700" :style="'width: ' + Math.min(100, Math.max(0, result?.prosody_score || 0)) + '%'"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Transcription Comparison --}}
        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 text-xs space-y-1.5">
            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Nội dung âm thanh nhận diện được:</span>
            <p class="font-mono text-white font-medium text-sm leading-relaxed" x-text="result?.transcribe || currentSentence.text"></p>
        </div>

        {{-- Interactive Word-by-Word Phoneme Inspector --}}
        <div x-show="result?.words_detail?.length" class="space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold text-gray-300 uppercase tracking-wider">Phân Tích Từng Từ & Âm Vị IPA (Bấm vào từ để xem chi tiết):</h4>
                <div class="flex items-center gap-3 text-[10px]">
                    <span class="flex items-center gap-1 text-emerald-400 font-semibold"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Chuẩn (≥80)</span>
                    <span class="flex items-center gap-1 text-amber-400 font-semibold"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Tạm ổn (60-79)</span>
                    <span class="flex items-center gap-1 text-red-400 font-semibold"><span class="w-2 h-2 rounded-full bg-red-400"></span> Cần sửa (&lt;60)</span>
                </div>
            </div>

            {{-- Word Chips Bar --}}
            <div class="flex flex-wrap items-center gap-2 p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <template x-for="(w, widx) in result?.words_detail" :key="widx">
                    <button
                        type="button"
                        @click="selectedWordIndex = (selectedWordIndex === widx ? null : widx)"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 border"
                        :class="[
                            selectedWordIndex === widx ? 'ring-2 ring-indigo-400 scale-105' : '',
                            w.score >= 80 ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25' : 
                            (w.score >= 60 ? 'bg-amber-500/15 text-amber-300 border-amber-500/30 hover:bg-amber-500/25' : 'bg-red-500/15 text-red-300 border-red-500/30 hover:bg-red-500/25')
                        ]">
                        <span x-text="w.word"></span>
                        <span class="text-[10px] font-mono opacity-80" x-text="Math.round(w.score)"></span>
                    </button>
                </template>
            </div>

            {{-- Detailed Phoneme Card for Selected Word --}}
            <template x-if="selectedWordIndex !== null && result?.words_detail[selectedWordIndex]">
                <div class="p-4 rounded-2xl bg-slate-900 border border-indigo-500/40 space-y-3 animate-fadeIn">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-base font-black text-white" x-text="result.words_detail[selectedWordIndex].word"></span>
                            <span class="text-xs font-mono text-gray-400" x-text="'Điểm: ' + Math.round(result.words_detail[selectedWordIndex].score) + '/100'"></span>
                            <span x-show="result.words_detail[selectedWordIndex].stress?.syllable_display" class="px-2 py-0.5 rounded text-[10px] font-mono bg-indigo-500/20 text-indigo-300 font-bold" x-text="'Trọng âm: ' + result.words_detail[selectedWordIndex].stress?.syllable_display"></span>
                        </div>
                        <button type="button" @click="selectedWordIndex = null" class="text-gray-400 hover:text-white text-xs">&times; Đóng</button>
                    </div>

                    {{-- Phonemes Breakdown --}}
                    <div class="space-y-2">
                        <span class="text-[10px] uppercase font-bold text-gray-400">Các âm vị cấu thành (Phonemes):</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <template x-for="(ph, phidx) in (result.words_detail[selectedWordIndex].phonemes || [])" :key="phidx">
                                <div class="p-2.5 rounded-xl border text-xs space-y-1"
                                     :class="ph.score >= 80 ? 'bg-emerald-500/10 border-emerald-500/20' : (ph.score >= 60 ? 'bg-amber-500/10 border-amber-500/20' : 'bg-red-500/10 border-red-500/20')">
                                    <div class="flex items-center justify-between font-mono">
                                        <span class="text-sm font-bold text-white" x-text="'/' + ph.phoneme + '/'"></span>
                                        <span class="text-[11px] font-bold" :class="ph.score >= 80 ? 'text-emerald-400' : (ph.score >= 60 ? 'text-amber-400' : 'text-red-400')" x-text="Math.round(ph.score)"></span>
                                    </div>
                                    <p x-show="ph.tip" class="text-[10px] text-gray-400 leading-tight" x-text="ph.tip"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Actionable Tips Box --}}
        <div x-show="result?.actionable_tips?.length" class="space-y-2.5">
            <h4 class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                <span>💡</span>
                <span>Lời Khuyên Cải Thiện Âm Vị Trực Tiếp (Actionable Tips):</span>
            </h4>
            <div class="space-y-2">
                <template x-for="(tip, tidx) in result?.actionable_tips" :key="tidx">
                    <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs flex items-start gap-3">
                        <span class="px-2 py-0.5 rounded font-mono font-bold text-[11px] bg-amber-500/20 text-amber-300 mt-0.5 flex-shrink-0" x-text="'/' + tip.phoneme + '/' + (tip.word ? ' (' + tip.word + ')' : '')"></span>
                        <p class="text-gray-300 leading-relaxed" x-text="tip.tip"></p>
                    </div>
                </template>
            </div>
        </div>

        {{-- AI Coach General Feedback --}}
        <div class="p-4 rounded-xl bg-gradient-to-r from-indigo-900/30 to-purple-900/20 border border-indigo-500/30 text-xs text-gray-200 leading-relaxed flex items-start gap-3">
            <span class="text-2xl flex-shrink-0">🤖</span>
            <div>
                <span class="font-bold text-white block mb-0.5">Lời khuyên từ Huấn Luyện Viên AI:</span>
                <p x-text="result?.feedback"></p>
            </div>
        </div>
    </div>
</div>

<script>
function aiSpeakingApp() {
    return {
        sentences: @json($practiceSentences),
        selectedIndex: 0,
        isRecording: false,
        recordingSeconds: 0,
        recordingTimer: null,
        mediaRecorder: null,
        audioChunks: [],
        recordedAudioBlob: null,
        recordedAudioUrl: null,
        recognizedText: '',
        evaluating: false,
        result: null,
        selectedWordIndex: null,
        errorMessage: '',
        speechRecognition: null,

        get currentSentence() {
            return this.sentences[this.selectedIndex] || this.sentences[0];
        },

        selectSentence(index) {
            if (this.isRecording) {
                this.stopRecording();
            }
            this.selectedIndex = index;
            this.resetRecordingState();
        },

        resetRecordingState() {
            this.recordedAudioBlob = null;
            if (this.recordedAudioUrl) {
                URL.revokeObjectURL(this.recordedAudioUrl);
                this.recordedAudioUrl = null;
            }
            this.recognizedText = '';
            this.result = null;
            this.selectedWordIndex = null;
            this.errorMessage = '';
            this.recordingSeconds = 0;
        },

        playModelAudio() {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(this.currentSentence.text);
                utterance.lang = 'en-US';
                utterance.rate = 0.88;
                window.speechSynthesis.speak(utterance);
            }
        },

        formatTime(seconds) {
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        async toggleRecording() {
            if (this.isRecording) {
                this.stopRecording();
            } else {
                await this.startRecording();
            }
        },

        async startRecording() {
            this.resetRecordingState();

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    } 
                });

                this.audioChunks = [];
                
                // Determine best supported mime type
                let mimeType = 'audio/webm';
                if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                    mimeType = 'audio/webm;codecs=opus';
                } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
                    mimeType = 'audio/mp4';
                } else if (MediaRecorder.isTypeSupported('audio/ogg')) {
                    mimeType = 'audio/ogg';
                }

                this.mediaRecorder = new MediaRecorder(stream, { mimeType });

                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data && event.data.size > 0) {
                        this.audioChunks.push(event.data);
                    }
                };

                this.mediaRecorder.onstop = () => {
                    this.recordedAudioBlob = new Blob(this.audioChunks, { type: mimeType });
                    this.recordedAudioUrl = URL.createObjectURL(this.recordedAudioBlob);
                    
                    // Stop all audio tracks to turn off mic indicator
                    stream.getTracks().forEach(t => t.stop());
                };

                this.mediaRecorder.start(250); // Slice every 250ms
                this.isRecording = true;
                this.recordingSeconds = 0;

                this.recordingTimer = setInterval(() => {
                    this.recordingSeconds++;
                    // Auto stop after 45 seconds max
                    if (this.recordingSeconds >= 45) {
                        this.stopRecording();
                    }
                }, 1000);

                // Optional live speech recognition for real-time text feedback
                this.startLiveSpeechRecognition();

            } catch (err) {
                console.error('Microphone access error:', err);
                this.errorMessage = 'Không thể truy cập microphone. Vui lòng cho phép quyền truy cập mic trên trình duyệt!';
            }
        },

        stopRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
            if (this.recordingTimer) {
                clearInterval(this.recordingTimer);
                this.recordingTimer = null;
            }
            this.isRecording = false;

            if (this.speechRecognition) {
                try { this.speechRecognition.stop(); } catch(e) {}
            }
        },

        startLiveSpeechRecognition() {
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRec) return;

            try {
                this.speechRecognition = new SpeechRec();
                this.speechRecognition.lang = 'en-US';
                this.speechRecognition.interimResults = true;
                this.speechRecognition.continuous = true;

                this.speechRecognition.onresult = (event) => {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        transcript += event.results[i][0].transcript;
                    }
                    if (transcript) {
                        this.recognizedText = transcript;
                    }
                };

                this.speechRecognition.start();
            } catch (e) {
                // Ignore fallback speech recognition errors
            }
        },

        async evaluateSpeech() {
            if (this.evaluating) return;
            if (!this.recordedAudioBlob && !this.recognizedText) {
                this.errorMessage = 'Vui lòng thu âm giọng nói của bạn trước khi chấm điểm!';
                return;
            }

            this.evaluating = true;
            this.errorMessage = '';

            try {
                const formData = new FormData();
                formData.append('text', this.currentSentence.text);
                formData.append('reference_sentence', this.currentSentence.text);

                if (this.recordedAudioBlob) {
                    formData.append('audio_file', this.recordedAudioBlob, 'recording.webm');
                } else if (this.recognizedText) {
                    formData.append('recognized_text', this.recognizedText);
                }

                const response = await fetch('{{ route("api.ai.speaking.evaluate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const res = await response.json();
                this.evaluating = false;

                if (res.success) {
                    this.result = res.data || res;
                    this.selectedWordIndex = null;

                    // Update XP header counter if element exists
                    const xpEl = document.getElementById('xp-count');
                    if (xpEl && (res.total_xp || res.data?.total_xp)) {
                        const totalXp = res.total_xp || res.data?.total_xp;
                        xpEl.textContent = new Intl.NumberFormat().format(totalXp) + ' XP';
                    }
                } else {
                    this.errorMessage = res.message || 'Chấm điểm chưa thành công. Vui lòng thử lại!';
                }
            } catch (err) {
                console.error('Evaluate speech error:', err);
                this.evaluating = false;
                this.errorMessage = 'Lỗi kết nối máy chủ chấm phát âm AI. Vui lòng kiểm tra lại đường truyền!';
            }
        }
    };
}
</script>
@endsection
