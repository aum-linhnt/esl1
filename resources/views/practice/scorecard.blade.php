@extends('layouts.app')

@section('content')
<div class="w-full space-y-6" x-data="{ filterMode: 'all' }">
    
@if(!empty($is_review) || !empty($result['is_review']))
        {{-- Review Mode Alert Banner --}}
        <div class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 text-sm text-indigo-200">
                <span class="text-2xl">📋</span>
                <div>
                    <span class="font-bold text-white">Chế độ xem lại bài làm: Lần #{{ $result['attempt_number'] ?? ($submission->attempt_number ?? 1) }}</span>
                    <p class="text-xs text-indigo-300/80">Hoàn thành lúc {{ $result['completed_at'] ?? (isset($submission) && $submission->completed_at ? $submission->completed_at->format('d/m/Y H:i') : (isset($submission) && $submission->created_at ? $submission->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'))) }} @if(!empty($result['time_spent_seconds']))· Thời gian: {{ floor($result['time_spent_seconds'] / 60) }} phút {{ $result['time_spent_seconds'] % 60 }}s @endif</p>
                </div>
            </div>
            <a href="{{ route('practice.exam', $exam['key']) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-sm whitespace-nowrap">
                Về trang tóm tắt & Lần làm bài
            </a>
        </div>
    @endif

    {{-- 1. SCORECARD HERO BANNER --}}
    <div class="card-dark p-6 sm:p-8 text-center space-y-5 border-indigo-500/40 relative overflow-hidden bg-gradient-to-b from-indigo-950/40 to-[#0f172a]">
        <div class="space-y-2">
            <span class="text-5xl block animate-bounce">{{ !empty($result['is_passed']) ? '🏆' : '📖' }}</span>
            <div class="inline-flex items-center gap-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-bold font-mono {{ !empty($result['is_passed']) ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border-amber-500/30' }}">
                    <span>{{ !empty($result['is_passed']) ? '✓ ĐẠT CHUẨN ĐÁNH GIÁ (PASSED)' : '⚠️ CẦN LUYỆN TẬP THÊM (RETAKE)' }}</span>
                </div>
                @if(!empty($result['attempt_number']) || !empty($submission->attempt_number))
                    <span class="px-3 py-1 rounded-full bg-slate-800 text-indigo-300 border border-indigo-500/30 text-xs font-bold font-mono">
                        Lần làm #{{ $result['attempt_number'] ?? ($submission->attempt_number ?? 1) }}
                    </span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ $exam['title'] }}</h1>
            <p class="text-xs text-gray-400">Khung chuẩn năng lực Châu Âu CEFR · {{ !empty($exam['sections']) ? 'Đề Thi Thử Tổng Hợp 4 Kỹ Năng' : 'Kỹ năng ' . ucfirst($exam['skill']) }}</p>
        </div>

        {{-- Score Highlights Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-2xl mx-auto pt-2">
            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">Tỷ lệ chính xác</span>
                <span class="text-3xl font-black font-mono {{ !empty($result['is_passed']) ? 'text-emerald-400' : 'text-yellow-400' }}">
                    {{ $result['accuracy_rate'] ?? 0 }}%
                </span>
            </div>

            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">Số câu đúng</span>
                <span class="text-3xl font-black text-white font-mono">
                    {{ $result['correct_count'] ?? 0 }}<span class="text-base text-gray-500 font-normal">/{{ $result['total_questions'] ?? 0 }}</span>
                </span>
            </div>

            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">Điểm số</span>
                <span class="text-2xl font-black font-mono text-fsel-teal mt-1 block">
                    {{ $result['total_score'] ?? 0 }}<span class="text-xs text-gray-500">/{{ $result['max_score'] ?? 0 }}</span>
                </span>
            </div>

            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">XP Thưởng</span>
                <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-cyan-200 font-mono flex items-center justify-center gap-1 mt-1">
                    ⚡ +{{ $result['xp_earned'] ?? (!empty($result['coins_earned']) ? ($result['coins_earned'] * 5) : ($result['total_score'] ?? 0)) }} XP
                </span>
            </div>
        </div>

        {{-- 4-SKILL BREAKDOWN MATRIX (If available in result) --}}
        @if(!empty($result['skill_breakdown']) && count($result['skill_breakdown']) > 1)
            <div class="pt-4 border-t border-slate-800/80">
                <h4 class="text-xs font-bold text-gray-300 uppercase tracking-wider mb-3">Ma Trận Điểm Số 4 Kỹ Năng Thực Tế</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-left">
                    @php
                        $skillIcons = [
                            'listening' => ['icon' => '🎧', 'name' => 'Nghe hiểu', 'color' => 'from-amber-500 to-orange-500'],
                            'speaking' => ['icon' => '🎙️', 'name' => 'Nói', 'color' => 'from-rose-500 to-pink-500'],
                            'reading' => ['icon' => '📖', 'name' => 'Đọc hiểu', 'color' => 'from-teal-500 to-emerald-500'],
                            'writing' => ['icon' => '✍️', 'name' => 'Viết', 'color' => 'from-cyan-500 to-blue-500'],
                            'vocabulary' => ['icon' => '📖', 'name' => 'Từ vựng', 'color' => 'from-cyan-500 to-blue-500'],
                            'grammar' => ['icon' => '🧩', 'name' => 'Ngữ pháp', 'color' => 'from-purple-500 to-indigo-500'],
                        ];
                    @endphp

                    @foreach($result['skill_breakdown'] as $sName => $sData)
                        @php 
                            $meta = $skillIcons[$sName] ?? ['icon' => '📝', 'name' => ucfirst($sName), 'color' => 'from-indigo-500 to-blue-500'];
                            $pct = $sData['total'] > 0 ? round(($sData['correct'] / $sData['total']) * 100) : 0;
                        @endphp
                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-white flex items-center gap-1">
                                    <span>{{ $meta['icon'] }}</span>
                                    <span>{{ $meta['name'] }}</span>
                                </span>
                                <span class="font-mono font-bold {{ $pct >= 70 ? 'text-emerald-400' : 'text-amber-400' }}">{{ $pct }}%</span>
                            </div>
                            <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r {{ $meta['color'] }}" style="width: {{ $pct }}%;"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 font-mono block text-right">Đúng {{ $sData['correct'] }}/{{ $sData['total'] }} câu</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-center gap-3 pt-3 border-t border-slate-800/80">
            <a href="{{ route('practice.exam', $exam['key']) }}" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-xs font-bold text-gray-300 hover:text-white border border-slate-700 transition-colors">
                🔄 Làm lại đề thi này
            </a>

            <a href="{{ route('leaderboard.index') }}" class="px-5 py-2.5 rounded-xl bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 text-xs font-bold hover:bg-yellow-500/30 transition-all flex items-center gap-1.5 shadow-sm">
                <span>🏆</span>
                <span>Bảng Xếp Hạng XP</span>
            </a>

            <a href="{{ route('practice.index', ['skill' => $exam['skill'] === 'full_mock' ? 'full_mock' : $exam['skill']]) }}" class="btn-primary !w-auto !py-2.5 px-6 text-xs font-bold shadow-glow-blue flex items-center gap-1.5">
                <span>Quay lại Danh sách Đề thi</span>
            </a>
        </div>
    </div>

    {{-- 2. DETAILED QUESTION-BY-QUESTION REVIEW WITH FILTER --}}
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-white tracking-tight">Phân tích Đáp án & Giải thích Chi tiết</h3>
                <p class="text-xs text-gray-400">Xem lại từng câu trả lời để rút kinh nghiệm và củng cố kiến thức</p>
            </div>

            {{-- Review Filter Tabs --}}
            <div class="flex items-center gap-1.5 bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs font-medium">
                <button type="button" @click="filterMode = 'all'" class="px-3 py-1 rounded-lg transition-colors" :class="filterMode === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-gray-400 hover:text-white'">
                    Tất cả ({{ count($result['details']) }})
                </button>
                <button type="button" @click="filterMode = 'correct'" class="px-3 py-1 rounded-lg transition-colors" :class="filterMode === 'correct' ? 'bg-emerald-600 text-white font-bold' : 'text-gray-400 hover:text-white'">
                    Đúng ({{ $result['correct_count'] }})
                </button>
                <button type="button" @click="filterMode = 'wrong'" class="px-3 py-1 rounded-lg transition-colors" :class="filterMode === 'wrong' ? 'bg-red-600 text-white font-bold' : 'text-gray-400 hover:text-white'">
                    Sai ({{ $result['total_questions'] - $result['correct_count'] }})
                </button>
            </div>
        </div>

        {{-- Question Cards List --}}
        <div class="space-y-4">
            @foreach($result['details'] as $idx => $item)
                <div class="card-dark p-6 space-y-4 border {{ $item['is_correct'] ? 'border-emerald-500/30' : 'border-red-500/30' }}"
                     x-show="filterMode === 'all' || (filterMode === 'correct' && {{ $item['is_correct'] ? 'true' : 'false' }}) || (filterMode === 'wrong' && {{ !$item['is_correct'] ? 'true' : 'false' }})">
                    
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg text-xs font-bold font-mono flex items-center justify-center shadow-md {{ $item['is_correct'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' }}">
                                {{ $idx + 1 }}
                            </span>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-slate-800 text-gray-300">
                                        {{ $item['difficulty'] }}
                                    </span>
                                    <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 capitalize">
                                        Section: {{ $item['skill'] }}
                                    </span>

                                    @php
                                        $userAns = (string)($item['user_answer'] ?? '');
                                        $skill = strtolower(trim($item['skill'] ?? ''));
                                        $isSpeaking = ($skill === 'speaking')
                                            || in_array($item['question_type'] ?? '', ['speaking', 'pronunciation_speech', 'audio_recording'])
                                            || str_starts_with($userAns, 'audio_recorded_')
                                            || str_starts_with($userAns, 'data:audio');
                                        $isWriting = ($skill === 'writing') || in_array($item['question_type'] ?? '', ['essay', 'writing']);
                                        $aiEval = $item['ai_evaluation'] ?? null;
                                    @endphp

                                    @if($isWriting)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold font-mono px-2.5 py-0.5 rounded-full bg-purple-500/20 border border-purple-500/30 text-purple-300">
                                            <svg class="w-3 h-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            <span>AI Writing (Chấm luận)</span>
                                            @if(!empty($aiEval['overall_score']))
                                                <span class="text-white font-bold ml-1 font-mono bg-purple-900/60 px-1.5 py-0.2 rounded">{{ $aiEval['overall_score'] }}/100</span>
                                                <span class="text-fsel-teal font-mono font-black">CEFR {{ $aiEval['cefr_level'] ?? 'B1' }}</span>
                                            @endif
                                        </span>
                                    @elseif($isSpeaking)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold font-mono px-2.5 py-0.5 rounded-full bg-teal-500/20 border border-teal-500/30 text-teal-300">
                                            <svg class="w-3 h-3 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                            <span>AI Speaking (Phát âm)</span>
                                            @if(!empty($aiEval['score']))
                                                <span class="text-white font-bold ml-1 font-mono bg-teal-900/60 px-1.5 py-0.2 rounded">{{ round($aiEval['score']) }}/100</span>
                                                <span class="text-indigo-300 font-mono font-black">IELTS {{ $aiEval['ielts_cefr']['ielts_band'] ?? '6.5' }}</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm font-semibold text-white leading-relaxed pt-1">{{ $item['question_text'] }}</p>
                            </div>
                        </div>

                        <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border flex-shrink-0 {{ $item['is_correct'] ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-red-500/10 text-red-400 border-red-500/30' }}">
                            {{ $item['is_correct'] ? '✓ Chính xác (+10đ)' : '✕ Chưa đúng' }}
                        </span>
                    </div>

                    {{-- ───────────────────────────────────────────────────────────── --}}
                    {{-- CASE 1: SPEAKING QUESTION (AUDIO PLAYER + AI SPEAKING REPORT) --}}
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    @if($isSpeaking)
                        <div class="space-y-3 ml-0 sm:ml-10">
                            {{-- Audio Compare Boxes --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                {{-- Speaking Answer Box with Replay Button --}}
                                <div class="p-3.5 rounded-xl border {{ $item['is_correct'] ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-slate-900 border-slate-800' }}"
                                     x-data="{
                                         qId: {{ $item['question_id'] }},
                                         isPlaying: false,
                                         audioObj: null,
                                         getAudioSrc() {
                                             const local = localStorage.getItem('cbt_recorded_audio_' + this.qId);
                                             if (local) return local;
                                             @if(str_starts_with($userAns, 'data:audio') || filter_var($userAns, FILTER_VALIDATE_URL))
                                                 return '{{ $userAns }}';
                                             @endif
                                             return 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                                         },
                                         playRecorded() {
                                             if (this.isPlaying && this.audioObj) {
                                                 this.audioObj.pause();
                                                 this.isPlaying = false;
                                                 return;
                                             }
                                             const src = this.getAudioSrc();
                                             if (!this.audioObj || this.audioObj.src !== src) {
                                                 this.audioObj = new Audio(src);
                                                 this.audioObj.onended = () => { this.isPlaying = false; };
                                             }
                                             this.audioObj.play().then(() => {
                                                 this.isPlaying = true;
                                             }).catch(() => {
                                                 this.isPlaying = false;
                                             });
                                         }
                                     }">
                                    <span class="text-gray-400 text-[10px] block mb-1 font-semibold">Bản ghi âm bài nói của bạn:</span>
                                    @if(!empty($item['user_answer']))
                                        <div class="flex items-center gap-3 mt-2">
                                            <button type="button" @click="playRecorded()" 
                                                    class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center gap-2 transition-all shadow-md shadow-indigo-600/30 cursor-pointer">
                                                <span x-text="isPlaying ? '⏸ Tạm dừng' : '▶ Nghe lại bài nói'">▶ Nghe lại bài nói</span>
                                            </button>
                                            <div class="text-[11px] font-mono text-indigo-300 flex items-center gap-1.5 bg-indigo-500/10 px-2.5 py-1 rounded-lg border border-indigo-500/20">
                                                <span>🎙️</span>
                                                <span>
                                                    @if(preg_match('/audio_recorded_(\d+)s/', $item['user_answer'], $m))
                                                        {{ $m[1] }}s thu âm
                                                    @else
                                                        Đã thu âm
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-500 italic text-xs block mt-1">(Bỏ trống chưa ghi âm)</span>
                                    @endif
                                </div>

                                {{-- Speaking Correct / Target Audio Box --}}
                                <div class="p-3.5 rounded-xl bg-emerald-950/20 border border-emerald-500/30"
                                     x-data="{
                                         speakTarget() {
                                             if ('speechSynthesis' in window) {
                                                 window.speechSynthesis.cancel();
                                                 const u = new SpeechSynthesisUtterance('{{ addslashes($item['correct_answer'] ?: $item['question_text']) }}');
                                                 u.lang = 'en-US';
                                                 u.rate = 0.9;
                                                 window.speechSynthesis.speak(u);
                                             }
                                         }
                                     }">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-gray-400 text-[10px] font-semibold">Mẫu phát âm chuẩn:</span>
                                        <button type="button" @click="speakTarget()" 
                                                class="text-[10px] font-bold text-emerald-300 hover:text-white flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-500/20 hover:bg-emerald-500/30 transition-colors cursor-pointer"
                                                title="Nghe máy phát âm câu mẫu">
                                            <span>🔊</span>
                                            <span>Nghe phát âm mẫu</span>
                                        </button>
                                    </div>
                                    <span class="font-bold font-mono text-sm text-emerald-400 block mt-1">
                                        {{ $item['correct_answer'] ?: $item['question_text'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- RICH AI SPEAKING ASSESSMENT CARD --}}
                            @if(!empty($aiEval))
                                <div class="p-5 rounded-2xl bg-[#0e1626] border border-teal-500/30 space-y-4 shadow-xl">
                                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                                            <span class="text-xs font-bold text-teal-300 uppercase tracking-wider">🎯 Kết Quả Đánh Giá Phát Âm AI Speaking</span>
                                        </div>
                                        <span class="text-[11px] font-mono text-gray-500">Chuẩn CEFR / IELTS</span>
                                    </div>

                                    {{-- 3 Hero Scores --}}
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        {{-- Score --}}
                                        <div class="p-4 rounded-xl bg-slate-900/90 border border-teal-500/20 text-center flex flex-col items-center justify-center space-y-1">
                                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Điểm phát âm</span>
                                            <div class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-indigo-300 font-mono">
                                                {{ round($aiEval['score'] ?? 80) }}/100
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ ($aiEval['score'] ?? 80) >= 80 ? 'bg-emerald-500/20 text-emerald-300' : (($aiEval['score'] ?? 80) >= 60 ? 'bg-amber-500/20 text-amber-300' : 'bg-red-500/20 text-red-300') }}">
                                                {{ ($aiEval['score'] ?? 80) >= 80 ? '✓ Xuất sắc' : (($aiEval['score'] ?? 80) >= 60 ? '⚡ Đạt chuẩn' : '⚠ Cần cải thiện') }}
                                            </span>
                                        </div>

                                        {{-- IELTS & CEFR --}}
                                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 flex flex-col justify-center space-y-2 text-xs">
                                            <div>
                                                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">IELTS Speaking Band:</span>
                                                <span class="text-lg font-extrabold text-white font-mono">{{ $aiEval['ielts_cefr']['ielts_band'] ?? '6.0 - 6.5' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Khung Năng Lực CEFR:</span>
                                                <span class="font-bold text-teal-400 font-mono">{{ $aiEval['ielts_cefr']['level_title'] ?? ($aiEval['ielts_cefr']['cefr_level'] ?? 'B2') }}</span>
                                            </div>
                                        </div>

                                        {{-- AI Summary --}}
                                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 flex flex-col justify-center text-xs text-gray-300 leading-relaxed">
                                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-1">Nhận xét tổng thể:</span>
                                            <p class="italic text-gray-300">{{ $aiEval['ielts_cefr']['summary'] ?? ($aiEval['feedback'] ?? 'Phát âm tốt, tiếp tục duy trì đều đặn.') }}</p>
                                        </div>
                                    </div>

                                    {{-- 4 Core Competency Progress Bars --}}
                                    <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-3">
                                        <span class="text-[11px] font-bold text-gray-300 uppercase tracking-wider block">4 Tiêu Chí Chấm Điểm Phát Âm Cốt Lõi:</span>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                            {{-- Accuracy --}}
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between text-[11px]">
                                                    <span class="text-gray-300 font-medium">🎯 Độ chuẩn xác âm vị (Accuracy)</span>
                                                    <span class="font-mono font-bold text-teal-300">{{ round($aiEval['accuracy'] ?? 82) }}%</span>
                                                </div>
                                                <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-teal-500 rounded-full" style="width: {{ min(100, max(0, $aiEval['accuracy'] ?? 82)) }}%;"></div>
                                                </div>
                                            </div>

                                            {{-- Fluency --}}
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between text-[11px]">
                                                    <span class="text-gray-300 font-medium">⚡ Độ lưu loát & nhịp điệu (Fluency)</span>
                                                    <span class="font-mono font-bold text-blue-300">{{ round($aiEval['fluency'] ?? 80) }}%</span>
                                                </div>
                                                <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 rounded-full" style="width: {{ min(100, max(0, $aiEval['fluency'] ?? 80)) }}%;"></div>
                                                </div>
                                            </div>

                                            {{-- Completeness --}}
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between text-[11px]">
                                                    <span class="text-gray-300 font-medium">📖 Độ đầy đủ từ ngữ (Completeness)</span>
                                                    <span class="font-mono font-bold text-purple-300">{{ round($aiEval['completeness'] ?? 100) }}%</span>
                                                </div>
                                                <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-purple-500 rounded-full" style="width: {{ min(100, max(0, $aiEval['completeness'] ?? 100)) }}%;"></div>
                                                </div>
                                            </div>

                                            {{-- Prosody --}}
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between text-[11px]">
                                                    <span class="text-gray-300 font-medium">🎶 Ngữ điệu & trọng âm (Prosody)</span>
                                                    <span class="font-mono font-bold text-amber-300">{{ round($aiEval['prosody_score'] ?? 78) }}%</span>
                                                </div>
                                                <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, max(0, $aiEval['prosody_score'] ?? 78)) }}%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    {{-- ───────────────────────────────────────────────────────────── --}}
                    {{-- CASE 2: WRITING QUESTION (ESSAY DISPLAY + AI WRITING REPORT)  --}}
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    @elseif($isWriting)
                        <div class="space-y-4 ml-0 sm:ml-10">
                            {{-- User Essay Box --}}
                            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                                <div class="flex items-center justify-between text-xs text-gray-400">
                                    <span class="font-bold text-indigo-300 flex items-center gap-1.5">
                                        <span>📝</span>
                                        <span>Nội dung bài viết của bạn:</span>
                                    </span>
                                    <span class="font-mono font-bold text-gray-400">
                                        {{ str_word_count(strip_tags($userAns)) }} từ
                                    </span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-xs sm:text-sm text-gray-200 leading-relaxed font-sans whitespace-pre-line">
                                    {{ $userAns ?: '(Học viên bỏ trống chưa hoàn thành bài viết)' }}
                                </div>
                            </div>

                            {{-- RICH AI WRITING ASSESSMENT CARD --}}
                            @if(!empty($aiEval))
                                <div class="p-5 rounded-2xl bg-[#0f172a] border border-purple-500/30 space-y-4 shadow-xl">
                                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                                            <span class="text-xs font-bold text-purple-300 uppercase tracking-wider">✍️ Kết Quả Chấm Bài Luận AI Writing</span>
                                        </div>
                                        <span class="text-[11px] font-mono text-gray-500">IELTS / CEFR Aligned</span>
                                    </div>

                                    {{-- 3 Hero Scores --}}
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        {{-- Score --}}
                                        <div class="p-4 rounded-xl bg-slate-900/90 border border-purple-500/20 text-center flex flex-col items-center justify-center space-y-1">
                                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Điểm tổng quan</span>
                                            <div class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-indigo-300 font-mono">
                                                {{ $aiEval['overall_score'] ?? 80 }}/100
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ ($aiEval['overall_score'] ?? 80) >= 80 ? 'bg-emerald-500/20 text-emerald-300' : (($aiEval['overall_score'] ?? 80) >= 60 ? 'bg-amber-500/20 text-amber-300' : 'bg-red-500/20 text-red-300') }}">
                                                {{ ($aiEval['overall_score'] ?? 80) >= 80 ? '✓ Xuất sắc' : (($aiEval['overall_score'] ?? 80) >= 60 ? '⚡ Đạt yêu cầu' : '⚠ Cần cải thiện') }}
                                            </span>
                                        </div>

                                        {{-- CEFR Level --}}
                                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 flex flex-col justify-center space-y-2 text-xs">
                                            <div>
                                                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Trình độ ước tính:</span>
                                                <span class="text-2xl font-black text-fsel-teal font-mono">Level {{ $aiEval['cefr_level'] ?? 'B1' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">Độ Mạch Lạc (Coherence):</span>
                                                <span class="font-bold text-indigo-300 font-mono">{{ $aiEval['coherence_score'] ?? 75 }}/100</span>
                                            </div>
                                        </div>

                                        {{-- Structure Feedback --}}
                                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 flex flex-col justify-center text-xs text-gray-300 leading-relaxed">
                                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-1">Góp ý cấu trúc bài viết:</span>
                                            <p class="text-gray-300 leading-relaxed">{{ $aiEval['structure_feedback'] ?? 'Cấu trúc bài viết mạch lạc, bố cục rõ ràng.' }}</p>
                                        </div>
                                    </div>

                                    {{-- Grammar Errors List --}}
                                    <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-2.5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-red-400 uppercase tracking-wider flex items-center gap-1.5">
                                                <span>❌ Lỗi ngữ pháp & Diễn đạt</span>
                                                <span class="bg-red-500/20 text-red-300 px-2 py-0.5 rounded-full text-[10px] font-mono">{{ count($aiEval['grammar_errors'] ?? []) }}</span>
                                            </span>
                                        </div>

                                        @if(!empty($aiEval['grammar_errors']) && count($aiEval['grammar_errors']) > 0)
                                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                                @foreach($aiEval['grammar_errors'] as $err)
                                                    <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs space-y-1">
                                                        <div class="flex flex-wrap items-center gap-2 text-red-300">
                                                            <span class="line-through font-mono text-gray-400">{{ $err['original'] ?? '' }}</span>
                                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                            <span class="font-bold text-emerald-400 font-mono">{{ $err['fix'] ?? '' }}</span>
                                                        </div>
                                                        <p class="text-gray-400 text-[11px] leading-relaxed">{{ $err['reason'] ?? '' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-xs text-emerald-400 italic py-1">
                                                ✓ Không phát hiện lỗi ngữ pháp nghiêm trọng trong bài viết!
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Vocabulary Improvements --}}
                                    @if(!empty($aiEval['vocabulary_improvements']) && count($aiEval['vocabulary_improvements']) > 0)
                                        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-2.5">
                                            <span class="text-xs font-bold text-fsel-gold uppercase tracking-wider flex items-center gap-1.5">
                                                <span>💡 Gợi ý nâng cấp từ vựng Band cao</span>
                                            </span>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                @foreach($aiEval['vocabulary_improvements'] as $vocab)
                                                    <div class="p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-xs space-y-1">
                                                        <div class="flex items-baseline gap-2">
                                                            <span class="text-gray-400 line-through text-[11px]">{{ $vocab['original'] ?? '' }}</span>
                                                            <svg class="w-3 h-3 text-yellow-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                            <span class="text-fsel-gold font-bold font-mono">{{ $vocab['suggestion'] ?? '' }}</span>
                                                        </div>
                                                        <p class="text-gray-300 text-[11px]">{{ $vocab['explanation'] ?? '' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Model Essay --}}
                                    @if(!empty($aiEval['model_essay']))
                                        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/80 space-y-2">
                                            <span class="text-xs font-bold text-fsel-teal uppercase tracking-wider block">
                                                ✨ Phiên bản hoàn thiện gợi ý (Model Essay)
                                            </span>
                                            <p class="text-xs sm:text-sm text-gray-300 leading-relaxed italic bg-[#141d33] p-4 rounded-xl border border-indigo-500/20 whitespace-pre-line">
                                                {{ $aiEval['model_essay'] }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                    {{-- ───────────────────────────────────────────────────────────── --}}
                    {{-- CASE 3: STANDARD QUESTIONS (MCQ, FILL, MATCH, ETC.)           --}}
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    @else
                        {{-- Standard Non-speaking/Non-writing questions --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-1 ml-0 sm:ml-10">
                            <div class="p-3.5 rounded-xl border {{ $item['is_correct'] ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-red-950/20 border-red-500/30' }}">
                                <span class="text-gray-400 text-[10px] block mb-1">Đáp án bạn đã chọn:</span>
                                <span class="font-bold font-mono text-sm {{ $item['is_correct'] ? 'text-emerald-400' : 'text-red-400' }}">
                                    {{ $item['user_answer'] ?: '(Bỏ trống chưa trả lời)' }}
                                </span>
                            </div>

                            <div class="p-3.5 rounded-xl bg-emerald-950/20 border border-emerald-500/30">
                                <span class="text-gray-400 text-[10px] block mb-1">Đáp án chính xác:</span>
                                <span class="font-bold font-mono text-sm text-emerald-400">
                                    {{ $item['correct_answer'] }}
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Explanation Box --}}
                    @if(!empty($item['explanation']))
                        <div class="text-xs text-gray-200 bg-slate-950/70 p-4 rounded-xl border border-slate-800 ml-0 sm:ml-10 leading-relaxed font-sans">
                            <span class="font-bold text-fsel-teal block mb-1">💡 Hướng dẫn & Giải thích chi tiết:</span>
                            {{ $item['explanation'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    try {
        @if(!empty($exam['key']))
            localStorage.removeItem('cbt_draft_{{ $exam['key'] }}');
            localStorage.removeItem('cbt_answers_{{ $exam['key'] }}');
        @endif
    } catch(e) {}
</script>
@endsection
