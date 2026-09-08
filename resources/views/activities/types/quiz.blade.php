{{-- Activity Type: Interactive Quiz (Synchronized with CBT Exam & Practice Room Style) --}}
@php
    $rawQuestions = $content['questions'] ?? [];
    $timeLimitMinutes = (int) ($activity->time_limit_minutes ?? 0);
    $passingGrade = (float) ($activity->passing_grade ?? 80);
@endphp

@if(empty($rawQuestions))
    <div class="p-8 text-center bg-[#151c30] border border-[#263353] rounded-2xl">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-2xl">
            🎯
        </div>
        <h3 class="text-base font-bold text-white mb-2">Chưa có câu hỏi trong bài kiểm tra</h3>
        <p class="text-xs text-gray-400 max-w-md mx-auto mb-5">
            Nội dung bài kiểm tra này đang được cập nhật. Bạn có thể quay lại bài học hoặc liên hệ giáo viên hỗ trợ.
        </p>
        <a href="{{ route('lessons.show', $lesson->id) }}" class="btn-primary !w-auto !py-2.5 px-6 text-xs inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Quay lại bài học
        </a>
    </div>
@else

<style>
    [x-cloak] {
        display: none !important;
    }
    /* Scoped CBT Exam & Practice Room Styles */
    .cbt-quiz-card {
        background-color: #151c30;
        border: 1px solid #253252;
    }
    .cbt-subcard {
        background-color: #1a223a;
        border: 1px solid #2b395b;
    }
    .cbt-option {
        background-color: #13192c;
        border: 1px solid #233050;
        color: #cbd5e1;
        transition: all 0.2s ease;
    }
    .cbt-option:hover {
        background-color: #1b243d;
        border-color: #3b82f6;
        color: #ffffff;
    }
    .cbt-option.selected {
        background-color: #1e2c4f;
        border-color: #3b82f6;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.4);
        color: #ffffff;
    }
    .cbt-radio-circle {
        border: 2px solid #43557d;
        background-color: #161e33;
        transition: all 0.2s ease;
    }
    .cbt-option:hover .cbt-radio-circle {
        border-color: #3b82f6;
    }
    .cbt-option.selected .cbt-radio-circle {
        border-color: #60a5fa;
        background-color: #2563eb;
    }
    .cbt-q-pill {
        background-color: #151d33;
        border: 1px solid #263353;
        color: #94a3b8;
        transition: all 0.15s ease;
    }
    .cbt-q-pill:hover {
        background-color: #1f2a48;
        color: #ffffff;
        border-color: #3b82f6;
    }
    .cbt-q-pill.active {
        background-color: #2563eb !important;
        border-color: #60a5fa !important;
        color: #ffffff !important;
        font-weight: 700;
        box-shadow: 0 0 12px rgba(37, 99, 246, 0.5);
    }
    .cbt-q-pill.answered {
        background-color: rgba(16, 185, 129, 0.15) !important;
        border-color: rgba(16, 185, 129, 0.45) !important;
        color: #34d399 !important;
    }
    .cbt-q-pill.flagged {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.5);
    }
    .cbt-input {
        background-color: #111625 !important;
        color: #ffffff !important;
        border: 1px solid #253252 !important;
        caret-color: #38bdf8 !important;
    }
    .cbt-input:focus {
        background-color: #151c30 !important;
        border-color: #3b82f6 !important;
        color: #ffffff !important;
        outline: none !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4) !important;
    }
    .cbt-input::placeholder {
        color: #64748b !important;
    }
</style>

<div x-data="courseQuizApp()" x-init="init()" class="space-y-6">

    {{-- ========================================================================= --}}
    {{-- SCREEN 0: MOODLE ATTEMPT SUMMARY & LANDING SCREEN                         --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'landing'" class="space-y-6">
        
        {{-- Quiz Header Card --}}
        <div class="cbt-quiz-card rounded-2xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-5 border-b border-[#232f4e]">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-2xl">
                        🎯
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            {{ $activity->title ?: 'Bài kiểm tra kiến thức' }}
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $lesson->title }} · Khóa học {{ $course->title }}
                        </p>
                    </div>
                </div>

                @if($activity->description)
                    <div class="text-xs text-gray-300 max-w-md bg-[#121727] p-3 rounded-xl border border-[#232f4e]">
                        {{ $activity->description }}
                    </div>
                @endif
            </div>

            {{-- Rules / Settings Grid (Moodle Standard) --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-5">
                <div class="bg-[#121727] p-3.5 rounded-xl border border-[#232f4e] text-center">
                    <span class="text-[10px] text-gray-400 uppercase font-mono font-bold block">Số lần làm bài</span>
                    <span class="text-sm sm:text-base font-bold text-white mt-1 block" x-text="maxAttempts > 0 ? (maxAttempts + ' lần') : 'Không giới hạn'"></span>
                </div>
                <div class="bg-[#121727] p-3.5 rounded-xl border border-[#232f4e] text-center">
                    <span class="text-[10px] text-gray-400 uppercase font-mono font-bold block">Thời gian giới hạn</span>
                    <span class="text-sm sm:text-base font-bold text-white mt-1 block" x-text="timeLimitMinutes > 0 ? (timeLimitMinutes + ' phút') : 'Không giới hạn'"></span>
                </div>
                <div class="bg-[#121727] p-3.5 rounded-xl border border-[#232f4e] text-center">
                    <span class="text-[10px] text-gray-400 uppercase font-mono font-bold block">Điểm đạt yêu cầu</span>
                    <span class="text-sm sm:text-base font-bold text-emerald-400 mt-1 block" x-text="passingGrade + '%'"></span>
                </div>
                <div class="bg-[#121727] p-3.5 rounded-xl border border-[#232f4e] text-center">
                    <span class="text-[10px] text-gray-400 uppercase font-mono font-bold block">Cách tính điểm</span>
                    <span class="text-sm sm:text-base font-bold text-indigo-300 mt-1 block" x-text="gradingMethodLabel"></span>
                </div>
            </div>
        </div>

        {{-- Summary of Previous Attempts Table (Moodle Standard) --}}
        <template x-if="attempts.length > 0">
            <div class="cbt-quiz-card rounded-2xl p-5 sm:p-7 shadow-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#232f4e]">
                    <div class="flex items-center gap-2">
                        <span class="text-sm sm:text-base font-bold text-white">📊 Bảng tổng hợp các lần làm bài trước (Summary of attempts)</span>
                        <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30" x-text="attempts.length + ' lượt'"></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-300">
                        <thead class="bg-[#121727] text-[11px] uppercase font-bold text-gray-400 border-b border-[#232f4e]">
                            <tr>
                                <th class="px-4 py-3">Lần làm</th>
                                <th class="px-4 py-3">Trạng thái</th>
                                <th class="px-4 py-3">Điểm số</th>
                                <th class="px-4 py-3">Tỷ lệ</th>
                                <th class="px-4 py-3">Thời gian làm</th>
                                <th class="px-4 py-3">Ngày nộp</th>
                                <th class="px-4 py-3 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e2944]">
                            <template x-for="(att, aIdx) in attempts" :key="'att_' + att.id">
                                <tr class="hover:bg-[#182239] transition-colors">
                                    <td class="px-4 py-3.5 font-bold text-white font-mono">
                                        <span class="w-6 h-6 rounded-lg bg-blue-500/15 text-blue-400 border border-blue-500/30 inline-flex items-center justify-center mr-1.5" x-text="att.attempt_number || (aIdx + 1)"></span>
                                        <span x-text="'Lần ' + (att.attempt_number || (aIdx + 1))"></span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                                              :class="att.is_passed ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-300 border border-amber-500/30'">
                                            <span x-text="att.is_passed ? '✓ Hoàn thành' : '⚠️ Chưa đạt'"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 font-mono font-bold text-white">
                                        <span x-text="att.score"></span><span class="text-gray-500" x-text="'/' + (att.max_score || 100)"></span>
                                    </td>
                                    <td class="px-4 py-3.5 font-mono font-bold"
                                        :class="att.is_passed ? 'text-emerald-400' : 'text-amber-400'"
                                        x-text="(att.percentage || att.score) + '%'">
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-gray-400" x-text="formatSeconds(att.time_spent_seconds)"></td>
                                    <td class="px-4 py-3.5 text-gray-400 text-[11px]" x-text="formatDate(att.completed_at || att.created_at)"></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <button type="button" 
                                                @click="openReview(att)" 
                                                class="px-3 py-1.5 rounded-lg bg-blue-500/15 hover:bg-blue-500/25 text-blue-300 hover:text-white border border-blue-500/30 font-semibold transition-all inline-flex items-center gap-1 cursor-pointer text-xs">
                                            <span>Xem lại</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Final Grade Moodle Banner --}}
                <div class="p-4 rounded-xl bg-gradient-to-r from-blue-950/40 via-indigo-950/30 to-purple-950/20 border border-blue-500/30 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl font-bold"
                             :class="isFinalGradePassed ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'">
                            <span x-text="isFinalGradePassed ? '🏆' : '📖'"></span>
                        </div>
                        <div>
                            <span class="text-[11px] text-gray-400 uppercase font-mono font-bold block" x-text="'Điểm tổng kết (' + gradingMethodLabel + '):'"></span>
                            <span class="text-xl font-black font-mono"
                                  :class="isFinalGradePassed ? 'text-emerald-400' : 'text-amber-400'"
                                  x-text="finalGrade + ' / 100 (' + finalGrade + '%)'"></span>
                        </div>
                    </div>
                    <div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold"
                              :class="isFinalGradePassed ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'"
                              x-text="isFinalGradePassed ? '✓ ĐẠT YÊU CẦU BÀI HỌC' : '⚠️ CHƯA ĐẠT ĐIỂM YÊU CẦU'"></span>
                    </div>
                </div>
            </div>
        </template>

        {{-- Action Buttons & Notices --}}
        <div class="cbt-quiz-card rounded-2xl p-6 text-center space-y-4 shadow-xl">
            <template x-if="canAttempt">
                <div class="space-y-3">
                    <button type="button" 
                            @click="startQuiz()" 
                            class="btn-primary !w-auto !py-3 px-8 text-sm font-bold inline-flex items-center gap-2 shadow-glow-blue cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="attempts.length === 0 ? 'Bắt đầu làm bài kiểm tra' : 'Làm lại bài kiểm tra (Re-attempt)'"></span>
                    </button>
                    <p class="text-xs text-gray-400" x-text="remainingAttemptsText"></p>
                </div>
            </template>

            <template x-if="!canAttempt">
                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs sm:text-sm max-w-md mx-auto space-y-2">
                    <div class="text-base font-bold">⚠️ Bạn đã hết số lần làm bài cho phép</div>
                    <p class="text-gray-400 text-xs" x-text="'Bạn đã hoàn thành ' + attempts.length + '/' + maxAttempts + ' lần làm bài cho phép của hoạt động này.'"></p>
                </div>
            </template>

            <div class="pt-2">
                <a href="{{ route('lessons.show', $lesson->id) }}" class="text-xs text-gray-400 hover:text-white inline-flex items-center gap-1.5 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Quay lại danh mục bài học
                </a>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- SCREEN 1: TAKING QUIZ (CBT Practice Room Mode)                            --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'taking'" x-cloak style="display: none;" class="space-y-5">
        
        {{-- HEADER BAR: Progress, Timer, Live Counters & Flagging --}}
        <div class="cbt-quiz-card rounded-2xl p-4 sm:p-5 shadow-xl">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center font-bold text-xs font-mono">
                        <span x-text="currentIndex + 1">1</span>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-white">
                                <span>Câu hỏi </span>
                                <span class="text-blue-400" x-text="(currentIndex + 1) + ' / ' + questions.length"></span>
                            </h3>
                            <template x-if="currentQuestion.skill">
                                <span class="text-[10px] uppercase font-mono font-bold px-2 py-0.5 rounded-full bg-teal-500/15 text-teal-300 border border-teal-500/30"
                                      x-text="currentQuestion.skill"></span>
                            </template>
                            <template x-if="currentQuestion.difficulty">
                                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-purple-500/15 text-purple-300 border border-purple-500/30"
                                      x-text="currentQuestion.difficulty"></span>
                            </template>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Đã làm: <strong class="text-white font-mono" x-text="answeredCount + '/' + questions.length"></strong> câu
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Live Timer Badge --}}
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl cbt-subcard font-mono text-xs sm:text-sm font-semibold text-white shadow-inner">
                        <svg class="w-4 h-4 text-teal-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="formattedTimer">00:00</span>
                        <template x-if="timeLimitMinutes > 0">
                            <span class="text-[10px] text-gray-400 ml-1">(còn lại)</span>
                        </template>
                    </div>

                    {{-- Flag Question Button --}}
                    <button type="button" 
                            @click="toggleFlag(currentIndex)"
                            class="px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all flex items-center gap-1.5 shadow-sm cursor-pointer"
                            :class="flagged[currentIndex] 
                                ? 'bg-amber-500/20 text-amber-300 border-amber-500/60 ring-1 ring-amber-500/40' 
                                : 'cbt-subcard text-gray-300 hover:text-white hover:border-gray-500'">
                        <svg class="w-3.5 h-3.5" :class="flagged[currentIndex] ? 'text-amber-400 fill-current' : 'text-gray-400'" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                        </svg>
                        <span class="hidden sm:inline" x-text="flagged[currentIndex] ? 'Đã gắn cờ' : 'Gắn cờ'">Gắn cờ</span>
                    </button>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="w-full bg-[#111625] rounded-full h-2 overflow-hidden border border-[#232f4e]">
                <div class="h-full bg-gradient-to-r from-blue-600 via-teal-400 to-indigo-500 transition-all duration-300 rounded-full"
                     :style="'width: ' + (((currentIndex + 1) / questions.length) * 100) + '%'"></div>
            </div>
        </div>

        {{-- MAIN QUESTION CARD --}}
        <div class="cbt-quiz-card rounded-2xl p-6 sm:p-8 space-y-6 shadow-xl relative min-h-[320px] flex flex-col justify-between">
            
            <div class="space-y-5">
                {{-- Question Type and Audio Controls --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-[#232f4e]">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20"
                              x-text="getQuestionTypeLabel(currentQuestion.question_type)">
                            Trắc nghiệm
                        </span>
                        <span class="text-xs text-gray-400" x-text="getQuestionTypeHint(currentQuestion.question_type)"></span>
                    </div>

                    {{-- Audio speaker button if question text is available or custom audio_url --}}
                    <div class="flex items-center gap-2">
                        <template x-if="currentQuestion.audio_url">
                            <button type="button" 
                                    @click="playCustomAudio(currentQuestion.audio_url)"
                                    class="px-3 py-1.5 rounded-lg bg-teal-500/15 border border-teal-500/30 text-teal-300 text-xs font-semibold hover:bg-teal-500/25 transition-colors flex items-center gap-1.5">
                                <span>🎧</span>
                                <span x-text="isPlayingAudio ? 'Dừng audio' : 'Nghe audio'">Nghe audio</span>
                            </button>
                        </template>
                        <button type="button" 
                                @click="speakQuestion(currentQuestion.question)"
                                class="p-1.5 rounded-lg bg-[#1a223a] border border-[#2b395b] text-gray-300 hover:text-white hover:border-blue-500 transition-colors"
                                title="Phát âm câu hỏi">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Question Prompt --}}
                <div class="text-base sm:text-lg lg:text-xl font-bold text-white leading-relaxed">
                    <span class="text-blue-400 font-mono mr-2" x-text="'Câu ' + (currentIndex + 1) + ':'"></span>
                    <span class="whitespace-pre-line" x-text="currentQuestion.question"></span>
                </div>

                {{-- ───────────────────────────────────────────────────────────── --}}
                {{-- 1. MCQ & AUDIO LISTENING OPTIONS LIST (Single Choice)          --}}
                {{-- ───────────────────────────────────────────────────────────── --}}
                <template x-if="(!currentQuestion.question_type || currentQuestion.question_type === 'mcq' || currentQuestion.question_type === 'audio_listening' || currentQuestion.question_type === 'true_false') && (currentQuestion.options && currentQuestion.options.length > 0)">
                    <div class="space-y-3 pt-2">
                        <template x-for="(opt, optIdx) in currentQuestion.options" :key="currentIndex + '_opt_' + optIdx">
                            <div @click="selectAnswer(optIdx, opt)"
                                 class="cbt-option flex items-center justify-between p-3.5 sm:p-4 rounded-xl cursor-pointer select-none group"
                                 :class="isAnswerSelected(optIdx, opt) ? 'selected' : ''">
                                
                                <div class="flex items-center gap-3.5 flex-1 pr-3">
                                    {{-- Option Letter Box A, B, C, D --}}
                                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center font-bold text-xs sm:text-sm font-mono border flex-shrink-0 transition-colors"
                                         :class="isAnswerSelected(optIdx, opt) 
                                             ? 'bg-blue-600 text-white border-blue-400 shadow-md' 
                                             : 'bg-[#182035] text-gray-400 border-[#2b395b] group-hover:border-blue-500/50 group-hover:text-white'"
                                         x-text="String.fromCharCode(65 + optIdx)">
                                    </div>
                                    <span class="text-sm sm:text-base leading-relaxed text-gray-200 group-hover:text-white" x-text="opt"></span>
                                </div>

                                {{-- Radio Circle Indicator --}}
                                <div class="cbt-radio-circle w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0">
                                    <div x-show="isAnswerSelected(optIdx, opt)" class="w-2 h-2 rounded-full bg-white"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- ───────────────────────────────────────────────────────────── --}}
                {{-- 2. MULTIPLE SELECT (Checkboxes)                                --}}
                {{-- ───────────────────────────────────────────────────────────── --}}
                <template x-if="currentQuestion.question_type === 'multiple_select' && (currentQuestion.options && currentQuestion.options.length > 0)">
                    <div class="space-y-3 pt-2">
                        <template x-for="(opt, optIdx) in currentQuestion.options" :key="currentIndex + '_multi_' + optIdx">
                            <div @click="toggleMultiSelect(opt)"
                                 class="cbt-option flex items-center justify-between p-3.5 sm:p-4 rounded-xl cursor-pointer select-none group"
                                 :class="isMultiSelected(opt) ? 'selected' : ''">
                                <div class="flex items-center gap-3.5 flex-1 pr-3">
                                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center font-bold text-xs sm:text-sm font-mono border flex-shrink-0 transition-colors"
                                         :class="isMultiSelected(opt) ? 'bg-blue-600 text-white border-blue-400 shadow-md' : 'bg-[#182035] text-gray-400 border-[#2b395b]'"
                                         x-text="String.fromCharCode(65 + optIdx)">
                                    </div>
                                    <span class="text-sm sm:text-base leading-relaxed text-gray-200 group-hover:text-white" x-text="opt"></span>
                                </div>
                                <div class="w-5 h-5 rounded-md border flex items-center justify-center flex-shrink-0 transition-colors"
                                     :class="isMultiSelected(opt) ? 'bg-blue-600 border-blue-400 text-white' : 'border-[#43557d] bg-[#161e33]'">
                                    <svg x-show="isMultiSelected(opt)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- ───────────────────────────────────────────────────────────── --}}
                {{-- 3. MATCHING PAIRS (Nối cặp từ - nghĩa)                         --}}
                {{-- ───────────────────────────────────────────────────────────── --}}
                <template x-if="currentQuestion.question_type === 'matching'">
                    <div class="space-y-4 pt-2">
                        <div class="p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs text-blue-300 flex items-center gap-2">
                            <span class="text-base">🔗</span>
                            <span>Bấm chọn đáp án ghép đôi tương ứng bên phải cho từng mục bên trái:</span>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(leftKey, lIdx) in currentQuestion.matching_left" :key="currentIndex + '_match_' + lIdx">
                                <div class="cbt-subcard p-4 sm:p-5 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 border border-[#2b395b] shadow-md">
                                    {{-- Left Key Term --}}
                                    <div class="flex items-center gap-3 min-w-[140px]">
                                        <span class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 font-bold font-mono text-xs flex items-center justify-center border border-blue-500/30 flex-shrink-0" x-text="lIdx + 1"></span>
                                        <span class="text-white font-bold text-base sm:text-lg tracking-wide" x-text="leftKey"></span>
                                    </div>

                                    {{-- Arrow Separator --}}
                                    <div class="hidden md:flex items-center text-gray-500 text-sm">
                                        <span>➜</span>
                                    </div>

                                    {{-- Right Choices as Clickable Interactive Pills --}}
                                    <div class="flex flex-wrap items-center gap-2.5 flex-1 md:justify-end">
                                        <template x-for="rOpt in currentQuestion.matching_right" :key="rOpt">
                                            <button type="button"
                                                    @click="setMatchingAnswer(leftKey, rOpt)"
                                                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold border transition-all duration-150 flex items-center gap-2 cursor-pointer select-none"
                                                    :class="(answers[currentIndex] || {})[leftKey] === rOpt
                                                        ? 'bg-blue-600 border-blue-400 text-white shadow-lg shadow-blue-600/30 ring-2 ring-blue-400/40 scale-[1.02]'
                                                        : 'bg-[#13192c] border-[#253353] text-gray-300 hover:text-white hover:border-blue-500/60 hover:bg-[#1b2540]'">
                                                <span x-text="rOpt"></span>
                                                <span x-show="(answers[currentIndex] || {})[leftKey] === rOpt" class="text-xs font-bold text-blue-200">✓</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- ───────────────────────────────────────────────────────────── --}}
                {{-- 4. WORD ORDERING / DRAG & DROP (Sắp xếp từ)                    --}}
                {{-- ───────────────────────────────────────────────────────────── --}}
                <template x-if="currentQuestion.question_type === 'word_ordering' || currentQuestion.question_type === 'drag_drop'">
                    <div class="space-y-4 pt-2">
                        <div class="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-xs text-indigo-300">
                            Bấm vào các từ bên dưới theo đúng thứ tự để tạo thành câu hoàn chỉnh. Bấm vào từ đã chọn để gỡ bỏ.
                        </div>

                        {{-- Sentence Target Construction Area --}}
                        <div class="min-h-[64px] p-4 rounded-xl bg-[#111625] border-2 border-dashed border-[#2b395b] flex flex-wrap items-center gap-2">
                            <template x-if="!answers[currentIndex] || answers[currentIndex].length === 0">
                                <span class="text-xs text-gray-500 italic">Câu của bạn sẽ xuất hiện tại đây...</span>
                            </template>
                            <template x-for="(tile, tIdx) in (answers[currentIndex] || [])" :key="'chosen_' + tIdx">
                                <button type="button" 
                                        @click="removeWordTile(tIdx)"
                                        class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-rose-600 text-white text-xs sm:text-sm font-semibold transition-colors flex items-center gap-1.5 shadow-md group">
                                    <span x-text="tile.word"></span>
                                    <span class="text-[10px] text-blue-200 group-hover:text-white">✕</span>
                                </button>
                            </template>
                        </div>

                        {{-- Available Word Tiles Bank --}}
                        <div class="pt-2">
                            <span class="text-xs text-gray-400 font-semibold block mb-2">Kho từ có sẵn:</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <template x-for="(w, wIdx) in currentQuestion.word_tiles" :key="'bank_' + wIdx">
                                    <button type="button" 
                                            @click="addWordTile(w, wIdx)"
                                            :disabled="isWordTileUsed(wIdx)"
                                            class="px-3.5 py-2 rounded-xl border text-xs sm:text-sm font-semibold transition-all shadow-sm"
                                            :class="isWordTileUsed(wIdx) 
                                                ? 'bg-[#151a2b] border-[#222b44] text-gray-600 opacity-40 cursor-not-allowed' 
                                                : 'cbt-subcard text-white hover:border-blue-500 hover:bg-[#202b48] cursor-pointer'">
                                        <span x-text="w"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ───────────────────────────────────────────────────────────── --}}
                {{-- 5. FILL IN THE BLANK / PRONUNCIATION / FALLBACK INPUT          --}}
                {{-- ───────────────────────────────────────────────────────────── --}}
                <template x-if="currentQuestion.question_type === 'fill_blank' || currentQuestion.question_type === 'pronunciation_speech' || (!currentQuestion.options || currentQuestion.options.length === 0) && currentQuestion.question_type !== 'matching' && currentQuestion.question_type !== 'word_ordering'">
                    <div class="pt-3 space-y-3">
                        <label class="block text-xs font-semibold text-gray-400">Nhập đáp án của bạn vào ô dưới đây:</label>
                        <div class="relative max-w-lg">
                            <input type="text"
                                   :value="answers[currentIndex] || ''"
                                   @input="answers[currentIndex] = $event.target.value"
                                   placeholder="Gõ câu trả lời của bạn..."
                                   style="background-color: #111625 !important; color: #ffffff !important; caret-color: #38bdf8 !important;"
                                   class="cbt-input w-full px-4 py-3.5 rounded-xl border border-[#253252] text-sm sm:text-base outline-none transition-all">
                            <template x-if="answers[currentIndex]">
                                <button type="button" 
                                        @click="answers[currentIndex] = ''" 
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white text-xs p-1 cursor-pointer">
                                    ✕
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Question Card Footer Controls --}}
            <div class="pt-5 border-t border-[#232f4e] flex items-center justify-between">
                <span class="text-xs text-gray-400 font-mono">
                    Tiến độ: <strong class="text-white" x-text="answeredCount"></strong> / <span x-text="questions.length"></span> câu đã trả lời
                </span>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="prevQuestion()" 
                            :disabled="currentIndex === 0"
                            class="px-4 py-2 rounded-xl cbt-subcard text-xs sm:text-sm font-semibold text-gray-300 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span>Câu trước</span>
                    </button>

                    <button type="button" 
                            @click="nextQuestion()" 
                            :disabled="currentIndex === questions.length - 1"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-semibold disabled:opacity-30 disabled:cursor-not-allowed transition-colors flex items-center gap-1.5 shadow-md">
                        <span>Câu sau</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- BOTTOM BAR: QUESTION PALETTE & SUBMIT ACTION --}}
        <div class="cbt-quiz-card rounded-2xl p-4 sm:p-5 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-white uppercase tracking-wider">Bảng câu hỏi:</span>
                    <span class="text-[11px] text-gray-400">(Bấm số để chuyển nhanh)</span>
                </div>

                {{-- Status Legend --}}
                <div class="flex items-center gap-3 text-[11px] font-mono text-gray-400 flex-wrap">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-blue-600 inline-block"></span>
                        <span>Đang làm</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-emerald-500/40 border border-emerald-500 inline-block"></span>
                        <span>Đã làm</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-amber-500/40 border border-amber-500 inline-block"></span>
                        <span>Gắn cờ</span>
                    </div>
                </div>
            </div>

            {{-- Question Pills Row / Grid --}}
            <div class="flex flex-wrap items-center gap-2 max-h-40 overflow-y-auto pr-1">
                <template x-for="(q, qIdx) in questions" :key="'palette_' + qIdx">
                    <button type="button" 
                            @click="goTo(qIdx)"
                            class="cbt-q-pill w-8 h-8 sm:w-9 sm:h-9 rounded-xl text-xs font-mono font-bold flex items-center justify-center relative cursor-pointer"
                            :class="getQuestionPillClass(qIdx)"
                            :title="'Chuyển đến câu ' + (qIdx + 1)">
                        <span x-text="qIdx + 1"></span>

                        {{-- Flag indicator tag --}}
                        <template x-if="flagged[qIdx]">
                            <span class="absolute -top-1 -right-1 flex items-center justify-center">
                                <svg class="w-3 h-3 text-amber-400 drop-shadow" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                                </svg>
                            </span>
                        </template>
                    </button>
                </template>
            </div>

            {{-- Submit Action Bar --}}
            <div class="pt-3 border-t border-[#232f4e] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs text-gray-400">
                    <template x-if="unansweredCount > 0">
                        <span class="text-amber-400 font-semibold">⚠️ Còn <strong x-text="unansweredCount"></strong> câu chưa trả lời.</span>
                    </template>
                    <template x-if="unansweredCount === 0">
                        <span class="text-emerald-400 font-semibold">✓ Bạn đã trả lời toàn bộ câu hỏi. Sẵn sàng nộp bài!</span>
                    </template>
                </div>

                <button type="button" 
                        @click="submitQuiz()"
                        class="btn-primary !w-auto !py-2.5 px-8 text-sm font-bold shadow-glow-blue flex items-center justify-center gap-2">
                    <span>Nộp bài Quiz</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
        </div>

        {{-- CONFIRMATION MODAL IF UNANSWERED QUESTIONS REMAIN --}}
        <div x-show="showConfirmModal" 
             x-cloak 
             style="display: none;"
             x-transition.opacity
             class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-[#182035] border border-[#2b395b] rounded-2xl max-w-md w-full p-6 text-center space-y-4 shadow-2xl"
                 @click.outside="showConfirmModal = false">
                <div class="w-14 h-14 mx-auto rounded-full bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-2xl">
                    ⚠️
                </div>
                <h3 class="text-lg font-bold text-white">Xác nhận nộp bài?</h3>
                <p class="text-xs sm:text-sm text-gray-300 leading-relaxed">
                    Bạn hiện còn <strong class="text-amber-400" x-text="unansweredCount"></strong> câu hỏi chưa trả lời
                    <template x-if="flaggedCount > 0">
                        <span> và <strong class="text-amber-300" x-text="flaggedCount"></strong> câu đang gắn cờ</span>
                    </template>. 
                    Bạn có chắc chắn muốn nộp bài để chấm điểm ngay bây giờ không?
                </p>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" 
                            @click="showConfirmModal = false" 
                            class="px-5 py-2.5 rounded-xl bg-[#141a2c] border border-[#222c48] text-gray-300 hover:text-white text-xs font-semibold">
                        Xem lại câu hỏi
                    </button>
                    <button type="button" 
                            @click="confirmSubmit()" 
                            class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg">
                        Xác nhận nộp bài
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- ========================================================================= --}}
    {{-- SCREEN 2: POST-SUBMISSION SCORECARD & DETAILED REVIEW (Practice Standard) --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'review'" x-cloak style="display: none;" class="space-y-6">
        
        {{-- Celebration / Score Diagnostic Hero Card --}}
        <div class="cbt-quiz-card rounded-2xl p-6 sm:p-10 text-center space-y-6 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Icon Badge --}}
            <div class="relative">
                <div class="w-20 h-20 mx-auto rounded-full flex items-center justify-center text-3xl shadow-xl transition-all"
                     :class="reviewPassed ? 'bg-gradient-to-tr from-teal-500 to-emerald-400 text-white shadow-emerald-500/20' : 'bg-gradient-to-tr from-amber-500 to-orange-400 text-white shadow-orange-500/20'">
                    <span x-text="reviewPassed ? '🎉' : '🎯'"></span>
                </div>
            </div>

            {{-- Headline --}}
            <div class="space-y-1 relative">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 text-xs font-mono font-bold mb-2">
                    <span x-text="'LẦN LÀM BÀI #' + (currentReviewAttempt ? (currentReviewAttempt.attempt_number || 1) : attempts.length)"></span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white" 
                    x-text="reviewPassed ? 'Chúc mừng bạn đã đạt bài kiểm tra!' : 'Kết quả bài kiểm tra của bạn'">
                </h2>
                <p class="text-xs sm:text-sm text-gray-400"
                   x-text="reviewPassed ? 'Bạn đã xuất sắc vượt qua điểm số yêu cầu của bài học này.' : 'Hãy xem lại các câu chưa chính xác và luyện tập thêm nhé!'">
                </p>
            </div>

            {{-- Diagnostic Stats Grid (Matches Practice Player) --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 max-w-3xl mx-auto pt-2">
                {{-- Score --}}
                <div class="bg-[#121727] border border-[#232f4e] p-4 rounded-xl text-center">
                    <span class="text-[11px] text-gray-400 uppercase font-mono font-semibold">Điểm số</span>
                    <p class="text-2xl sm:text-3xl font-black text-white mt-1">
                        <span class="text-blue-400" x-text="reviewScore"></span>
                        <span class="text-sm text-gray-500" x-text="'/' + reviewMaxScore"></span>
                    </p>
                    <span class="text-[10px] text-gray-400" x-text="reviewCorrectCount + ' câu trả lời đúng'"></span>
                </div>

                {{-- Accuracy % --}}
                <div class="bg-[#121727] border border-[#232f4e] p-4 rounded-xl text-center">
                    <span class="text-[11px] text-gray-400 uppercase font-mono font-semibold">Độ chính xác</span>
                    <p class="text-2xl sm:text-3xl font-black mt-1"
                       :class="reviewPassed ? 'text-teal-400' : 'text-amber-400'" 
                       x-text="reviewPercentage + '%'"></p>
                    <span class="text-[10px]" :class="reviewPassed ? 'text-teal-500' : 'text-amber-500'" 
                          x-text="'Yêu cầu: ' + passingGrade + '%'"></span>
                </div>

                {{-- Result Status --}}
                <div class="bg-[#121727] border border-[#232f4e] p-4 rounded-xl text-center">
                    <span class="text-[11px] text-gray-400 uppercase font-mono font-semibold">Kết quả</span>
                    <p class="text-lg sm:text-xl font-bold mt-2"
                       :class="reviewPassed ? 'text-emerald-400' : 'text-rose-400'"
                       x-text="reviewPassed ? 'ĐẠT CHUẨN' : 'CHƯA ĐẠT'"></p>
                    <span class="text-[10px] text-gray-400" x-text="reviewPassed ? '✓ Đã ghi nhận' : 'Cần rèn luyện thêm'"></span>
                </div>

                {{-- Time Spent --}}
                <div class="bg-[#121727] border border-[#232f4e] p-4 rounded-xl text-center">
                    <span class="text-[11px] text-gray-400 uppercase font-mono font-semibold">Thời gian</span>
                    <p class="text-2xl sm:text-3xl font-black text-indigo-400 font-mono mt-1" x-text="reviewFormattedDuration">00:00</p>
                    <span class="text-[10px] text-gray-400">Thời gian làm bài</span>
                </div>
            </div>

            {{-- Action Controls --}}
            <div class="pt-3 flex flex-wrap items-center justify-center gap-3">
                <button type="button" 
                        @click="finishReview()" 
                        class="px-5 py-2.5 rounded-xl bg-[#1a223a] border border-[#2b395b] text-white hover:border-blue-500 text-xs sm:text-sm font-bold transition-all flex items-center gap-2 cursor-pointer shadow-md">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Bảng tóm tắt các lần làm (Finish review)</span>
                </button>

                <template x-if="canAttempt">
                    <button type="button" 
                            @click="startQuiz()" 
                            class="btn-primary !w-auto !py-2.5 px-6 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-glow-blue cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Làm lại bài Quiz</span>
                    </button>
                </template>

                <a href="{{ route('lessons.show', $lesson->id) }}" 
                   class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-gray-300 hover:text-white text-xs sm:text-sm font-semibold transition-all flex items-center gap-2">
                    <span>Quay lại bài học</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>

        {{-- DETAILED ANSWER REVIEW (Accurate CBT Breakdown) --}}
        <div class="cbt-quiz-card rounded-2xl p-5 sm:p-7 shadow-xl space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#232f4e]">
                <div class="flex items-center gap-2">
                    <span class="text-base sm:text-lg font-bold text-white">📋 Chi tiết từng câu hỏi</span>
                    <span class="text-xs font-mono text-gray-400" x-text="'(' + reviewCorrectCount + '/' + reviewQuestions.length + ' câu đúng)'"></span>
                </div>
                <div class="text-xs text-gray-400">
                    Bấm để xem lại đáp án và giải thích chi tiết
                </div>
            </div>

            <div class="space-y-4">
                <template x-for="(q, idx) in reviewQuestions" :key="'review_' + idx">
                    <div class="bg-[#121727] border rounded-xl p-4 sm:p-5 transition-all"
                         :class="q.is_correct ? 'border-emerald-500/30' : 'border-rose-500/30'">
                        
                        {{-- Question Header & Status --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs font-mono"
                                      :class="q.is_correct ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-400 border border-rose-500/40'"
                                      x-text="idx + 1"></span>
                                <span class="text-xs font-bold font-mono px-2 py-0.5 rounded-full"
                                      :class="q.is_correct ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300'"
                                      x-text="q.is_correct ? '✓ Đúng' : '✗ Chưa đúng'"></span>
                                <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded-full bg-slate-800 text-gray-400 border border-slate-700"
                                      x-text="getQuestionTypeLabel(q.question_type)"></span>
                                <template x-if="q.version">
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-500/30" x-text="'v' + q.version"></span>
                                </template>
                            </div>

                            <button type="button" 
                                    @click="speakQuestion(q.question_text || q.question)"
                                    class="text-gray-400 hover:text-white p-1 text-xs cursor-pointer"
                                    title="Nghe câu hỏi">
                                🔊
                            </button>
                        </div>

                        {{-- Question Text --}}
                        <p class="text-sm sm:text-base font-semibold text-white mb-4 leading-relaxed" x-text="q.question_text || q.question"></p>

                        {{-- Answers Comparison --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            {{-- User Choice --}}
                            <div class="p-3 rounded-lg border"
                                 :class="q.is_correct ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300' : 'bg-rose-500/10 border-rose-500/30 text-rose-300'">
                                <span class="text-[10px] uppercase font-mono block text-gray-400 mb-1">Đáp án của bạn:</span>
                                <p class="font-bold text-sm" x-text="q.user_answer_display || '(Chưa trả lời)'"></p>
                            </div>

                            {{-- Correct Answer --}}
                            <div class="p-3 rounded-lg bg-teal-500/10 border border-teal-500/30 text-teal-300">
                                <span class="text-[10px] uppercase font-mono block text-gray-400 mb-1">Đáp án chính xác:</span>
                                <p class="font-bold text-sm text-teal-200" x-text="q.correct_answer_display"></p>
                            </div>
                        </div>

                        {{-- Explanation Box --}}
                        <template x-if="q.explanation">
                            <div class="mt-3 p-3 rounded-lg bg-[#182035] border border-[#2b395b] text-xs text-gray-300 flex items-start gap-2">
                                <span class="text-base flex-shrink-0">💡</span>
                                <div>
                                    <span class="font-bold text-blue-300 block mb-0.5">Giải thích:</span>
                                    <p class="leading-relaxed" x-text="q.explanation"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

    </div>

</div>

<script>
function courseQuizApp() {
    return {
        rawQuestions: @json($rawQuestions),
        questions: [],
        currentIndex: 0,
        answers: {},
        flagged: {},
        viewMode: 'landing', // 'landing', 'taking', 'review'
        showConfirmModal: false,
        timeLimitMinutes: {{ $timeLimitMinutes }},
        passingGrade: {{ $passingGrade }},
        maxAttempts: {{ (int) ($activity->max_attempts ?? 0) }},
        gradingMethod: '{{ $activity->grading_method ?? 'highest' }}',
        attempts: @json($quizAttempts ?? []),
        canAttempt: {{ ($canAttemptQuiz ?? true) ? 'true' : 'false' }},
        remainingAttempts: {{ json_encode($remainingQuizAttempts ?? null) }},
        finalGrade: {{ (float) ($quizFinalGrade ?? 0) }},
        currentReviewAttempt: null,
        startedAtIso: null,
        submitting: false,
        timeRemaining: {{ $timeLimitMinutes > 0 ? $timeLimitMinutes * 60 : 0 }},
        elapsedSeconds: 0,
        timerInterval: null,
        isPlayingAudio: false,
        audioElement: null,

        get gradingMethodLabel() {
            switch(this.gradingMethod) {
                case 'last': return 'Lần làm cuối cùng';
                case 'first': return 'Lần làm đầu tiên';
                case 'average': return 'Điểm trung bình';
                case 'highest':
                default: return 'Điểm cao nhất';
            }
        },

        get isFinalGradePassed() {
            return this.finalGrade >= this.passingGrade;
        },

        get remainingAttemptsText() {
            if (this.maxAttempts <= 0) return 'Số lần làm bài không giới hạn';
            const left = this.remainingAttempts !== null ? this.remainingAttempts : Math.max(0, this.maxAttempts - this.attempts.length);
            return `Bạn còn ${left}/${this.maxAttempts} lượt làm bài`;
        },

        get reviewScore() {
            if (this.currentReviewAttempt) return Math.round(this.currentReviewAttempt.score);
            return this.score;
        },

        get reviewMaxScore() {
            if (this.currentReviewAttempt) return Math.round(this.currentReviewAttempt.max_score || 100);
            return this.questions.length;
        },

        get reviewPercentage() {
            if (this.currentReviewAttempt) return Math.round(this.currentReviewAttempt.percentage || this.currentReviewAttempt.score);
            return this.percentage;
        },

        get reviewPassed() {
            return this.reviewPercentage >= this.passingGrade;
        },

        get reviewCorrectCount() {
            if (this.currentReviewAttempt && Array.isArray(this.currentReviewAttempt.answers_payload)) {
                return this.currentReviewAttempt.answers_payload.filter(p => p.is_correct).length;
            }
            return this.score;
        },

        get reviewFormattedDuration() {
            const secs = this.currentReviewAttempt ? (this.currentReviewAttempt.time_spent_seconds || 0) : this.elapsedSeconds;
            const m = String(Math.floor(secs / 60)).padStart(2, '0');
            const s = String(secs % 60).padStart(2, '0');
            return `${m}:${s}`;
        },

        get reviewQuestions() {
            if (this.currentReviewAttempt && Array.isArray(this.currentReviewAttempt.answers_payload) && this.currentReviewAttempt.answers_payload.length > 0) {
                return this.currentReviewAttempt.answers_payload.map(p => ({
                    id: p.question_id,
                    version: p.version || 1,
                    question_text: p.question_text || p.question,
                    question: p.question_text || p.question,
                    question_type: p.question_type || 'mcq',
                    options: p.options || [],
                    user_answer_display: p.user_answer_display || (p.user_answer !== null && p.user_answer !== undefined ? String(p.user_answer) : '(Chưa trả lời)'),
                    correct_answer_display: p.correct_answer_display || String(p.correct_answer ?? ''),
                    is_correct: !!p.is_correct,
                    explanation: p.explanation || ''
                }));
            }
            return this.questions.map((q, idx) => ({
                id: q.id,
                version: q.version || 1,
                question_text: q.question,
                question: q.question,
                question_type: q.question_type,
                options: q.options || [],
                user_answer_display: this.getUserAnswerDisplay(idx),
                correct_answer_display: this.getCorrectAnswerDisplay(idx),
                is_correct: this.isQuestionCorrect(idx),
                explanation: q.explanation || ''
            }));
        },

        init() {
            // Normalize raw questions data structure
            this.questions = (this.rawQuestions || []).map((q, idx) => {
                const questionText = q.question || q.question_text || `Câu hỏi ${idx + 1}`;
                let qType = q.question_type || 'mcq';
                let options = [];
                let matchingLeft = [];
                let matchingRight = [];
                let wordTiles = [];

                // 1. MATCHING PAIRS
                if (qType === 'matching') {
                    if (q.options && typeof q.options === 'object') {
                        if (q.options.left) {
                            matchingLeft = Array.isArray(q.options.left) ? q.options.left : Object.keys(q.options.left);
                        }
                        if (q.options.right) {
                            matchingRight = Array.isArray(q.options.right) ? q.options.right : Object.values(q.options.right);
                        }
                    }
                    if (matchingLeft.length === 0 && q.correct_answer) {
                        try {
                            const parsedPairs = typeof q.correct_answer === 'string' ? JSON.parse(q.correct_answer) : q.correct_answer;
                            matchingLeft = Object.keys(parsedPairs);
                            matchingRight = Object.values(parsedPairs);
                        } catch(e) {}
                    }
                }
                // 2. WORD ORDERING
                else if (qType === 'word_ordering' || qType === 'drag_drop') {
                    if (Array.isArray(q.options)) {
                        wordTiles = q.options;
                    } else if (typeof q.options === 'string') {
                        wordTiles = q.options.split(',').map(s => s.trim()).filter(Boolean);
                    } else if (q.correct_answer) {
                        wordTiles = String(q.correct_answer).split(' ').map(s => s.trim()).filter(Boolean);
                    }
                }
                // 3. MCQ, MULTIPLE SELECT, TRUE/FALSE, AUDIO LISTENING
                else {
                    if (Array.isArray(q.options)) {
                        options = q.options;
                    } else if (typeof q.options === 'string') {
                        try {
                            const parsed = JSON.parse(q.options);
                            options = Array.isArray(parsed) ? parsed : [];
                        } catch (e) {
                            options = q.options.split(',').map(s => s.trim()).filter(Boolean);
                        }
                    } else if (q.options && typeof q.options === 'object') {
                        options = Object.values(q.options);
                    }

                    if (qType === 'true_false' && (!options || options.length === 0)) {
                        options = ['True', 'False'];
                    }
                }

                let correctAnswer = q.correct_answer;
                if (correctAnswer === undefined || correctAnswer === null) {
                    if (typeof q.answer === 'number' && options[q.answer] !== undefined) {
                        correctAnswer = options[q.answer];
                    } else if (q.answer !== undefined) {
                        correctAnswer = q.answer;
                    }
                }

                return {
                    id: q.id || (idx + 1),
                    version: q.version || 1,
                    question: questionText,
                    question_type: qType,
                    options: options,
                    matching_left: matchingLeft,
                    matching_right: matchingRight,
                    word_tiles: wordTiles,
                    answer: q.answer,
                    correct_answer: correctAnswer,
                    explanation: q.explanation || '',
                    skill: q.skill || '',
                    difficulty: q.difficulty || '',
                    audio_url: q.audio_url || ''
                };
            });

            // Initialize answers map
            this.questions.forEach((q, idx) => {
                if (q.question_type === 'matching') {
                    this.answers[idx] = {};
                } else if (q.question_type === 'multiple_select') {
                    this.answers[idx] = [];
                } else if (q.question_type === 'word_ordering' || q.question_type === 'drag_drop') {
                    this.answers[idx] = [];
                } else {
                    this.answers[idx] = null;
                }
                this.flagged[idx] = false;
            });

            this.viewMode = 'landing';
        },

        get currentQuestion() {
            return this.questions[this.currentIndex] || {};
        },

        isQuestionAnswered(idx) {
            const q = this.questions[idx];
            if (!q) return false;
            const ans = this.answers[idx];
            if (ans === null || ans === undefined) return false;

            if (q.question_type === 'matching') {
                if (!ans || typeof ans !== 'object') return false;
                const leftKeys = q.matching_left || [];
                if (leftKeys.length === 0) return false;
                return leftKeys.every(k => ans[k] && String(ans[k]).trim() !== '');
            }

            if (q.question_type === 'multiple_select') {
                return Array.isArray(ans) && ans.length > 0;
            }

            if (q.question_type === 'word_ordering' || q.question_type === 'drag_drop') {
                if (Array.isArray(ans)) return ans.length > 0;
                return String(ans).trim() !== '';
            }

            return String(ans).trim() !== '';
        },

        get answeredCount() {
            return this.questions.filter((_, idx) => this.isQuestionAnswered(idx)).length;
        },

        get unansweredCount() {
            return this.questions.length - this.answeredCount;
        },

        get flaggedCount() {
            return Object.values(this.flagged).filter(Boolean).length;
        },

        get score() {
            return this.questions.reduce((acc, _, idx) => {
                return acc + (this.isQuestionCorrect(idx) ? 1 : 0);
            }, 0);
        },

        get percentage() {
            if (this.questions.length === 0) return 0;
            return Math.round((this.score / this.questions.length) * 100);
        },

        get passed() {
            return this.percentage >= this.passingGrade;
        },

        get formattedTimer() {
            const secs = this.timeLimitMinutes > 0 ? this.timeRemaining : this.elapsedSeconds;
            const m = String(Math.floor(secs / 60)).padStart(2, '0');
            const s = String(secs % 60).padStart(2, '0');
            return `${m}:${s}`;
        },

        get formattedDuration() {
            const m = String(Math.floor(this.elapsedSeconds / 60)).padStart(2, '0');
            const s = String(this.elapsedSeconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        },

        startTimer() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                this.elapsedSeconds++;
                if (this.timeLimitMinutes > 0) {
                    if (this.timeRemaining > 0) {
                        this.timeRemaining--;
                    } else {
                        clearInterval(this.timerInterval);
                        alert('⏱ Thời gian làm bài đã kết thúc! Hệ thống sẽ nộp bài tự động.');
                        this.confirmSubmit();
                    }
                }
            }, 1000);
        },

        selectAnswer(optIdx, optValue) {
            this.answers[this.currentIndex] = optIdx;
        },

        isAnswerSelected(optIdx, optValue) {
            const val = this.answers[this.currentIndex];
            return val === optIdx || val === optValue;
        },

        toggleMultiSelect(opt) {
            let current = Array.isArray(this.answers[this.currentIndex]) ? [...this.answers[this.currentIndex]] : [];
            const idx = current.indexOf(opt);
            if (idx === -1) {
                current.push(opt);
            } else {
                current.splice(idx, 1);
            }
            this.answers[this.currentIndex] = current;
        },

        isMultiSelected(opt) {
            const current = this.answers[this.currentIndex];
            return Array.isArray(current) && current.includes(opt);
        },

        setMatchingAnswer(leftKey, val) {
            if (!this.answers[this.currentIndex] || typeof this.answers[this.currentIndex] !== 'object') {
                this.answers[this.currentIndex] = {};
            }
            this.answers[this.currentIndex][leftKey] = val;
        },

        addWordTile(word, wIdx) {
            let current = Array.isArray(this.answers[this.currentIndex]) ? [...this.answers[this.currentIndex]] : [];
            current.push({ word, id: wIdx });
            this.answers[this.currentIndex] = current;
        },

        removeWordTile(idx) {
            let current = Array.isArray(this.answers[this.currentIndex]) ? [...this.answers[this.currentIndex]] : [];
            current.splice(idx, 1);
            this.answers[this.currentIndex] = current;
        },

        isWordTileUsed(wIdx) {
            const current = this.answers[this.currentIndex];
            return Array.isArray(current) && current.some(t => t.id === wIdx);
        },

        toggleFlag(idx) {
            this.flagged[idx] = !this.flagged[idx];
        },

        goTo(idx) {
            if (idx >= 0 && idx < this.questions.length) {
                this.currentIndex = idx;
            }
        },

        prevQuestion() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
            }
        },

        nextQuestion() {
            if (this.currentIndex < this.questions.length - 1) {
                this.currentIndex++;
            }
        },

        getQuestionTypeLabel(type) {
            switch(type) {
                case 'matching': return 'Nối cặp từ - nghĩa';
                case 'word_ordering':
                case 'drag_drop': return 'Sắp xếp từ';
                case 'true_false': return 'Đúng / Sai';
                case 'fill_blank': return 'Điền từ';
                case 'multiple_select': return 'Nhiều đáp án';
                case 'audio_listening': return 'Nghe hiểu';
                case 'pronunciation_speech': return 'Luyện phát âm';
                default: return 'Trắc nghiệm';
            }
        },

        getQuestionTypeHint(type) {
            switch(type) {
                case 'matching': return 'Ghép từng mục bên trái với đáp án đúng bên phải';
                case 'word_ordering':
                case 'drag_drop': return 'Bấm chọn các từ theo đúng trật tự';
                case 'true_false': return 'Chọn Đúng (True) hoặc Sai (False)';
                case 'multiple_select': return 'Có thể chọn nhiều hơn một đáp án';
                case 'fill_blank': return 'Điền từ thích hợp vào ô trống';
                default: return 'Chọn một đáp án đúng nhất';
            }
        },

        getQuestionPillClass(idx) {
            if (this.currentIndex === idx) {
                return 'active';
            }
            const isAnswered = this.isQuestionAnswered(idx);
            let cls = '';
            if (isAnswered) cls += ' answered';
            if (this.flagged[idx]) cls += ' flagged';
            return cls;
        },

        isQuestionCorrect(idx) {
            const q = this.questions[idx];
            if (!q) return false;
            const userAns = this.answers[idx];
            if (!this.isQuestionAnswered(idx)) return false;

            // 1. MATCHING
            if (q.question_type === 'matching') {
                const userPairs = userAns || {};
                let corrPairs = q.correct_answer;
                if (typeof corrPairs === 'string') {
                    try { corrPairs = JSON.parse(corrPairs); } catch(e) { corrPairs = {}; }
                }
                if (!corrPairs || typeof corrPairs !== 'object') return false;
                const keys = Object.keys(corrPairs);
                if (keys.length === 0) return false;
                for (let k of keys) {
                    const uVal = String(userPairs[k] || '').trim().toLowerCase();
                    const cVal = String(corrPairs[k] || '').trim().toLowerCase();
                    if (uVal !== cVal) return false;
                }
                return true;
            }

            // 2. MULTIPLE SELECT
            if (q.question_type === 'multiple_select') {
                let userArr = Array.isArray(userAns) ? userAns : [userAns];
                let corrArr = q.correct_answer;
                if (typeof corrArr === 'string') {
                    try { corrArr = JSON.parse(corrArr); } catch(e) { corrArr = corrArr.split(',').map(s => s.trim()); }
                }
                if (!Array.isArray(corrArr)) corrArr = [corrArr];
                const userNorm = userArr.map(s => String(s).trim().toLowerCase()).sort();
                const corrNorm = corrArr.map(s => String(s).trim().toLowerCase()).sort();
                return JSON.stringify(userNorm) === JSON.stringify(corrNorm);
            }

            // 3. WORD ORDERING
            if (q.question_type === 'word_ordering' || q.question_type === 'drag_drop') {
                let userSentence = Array.isArray(userAns) ? userAns.map(t => typeof t === 'object' ? t.word : t).join(' ') : String(userAns);
                const userClean = userSentence.replace(/\s+/g, ' ').trim().toLowerCase();
                const corrClean = String(q.correct_answer || '').replace(/\s+/g, ' ').trim().toLowerCase();
                return userClean === corrClean;
            }

            // 4. TRUE / FALSE
            if (q.question_type === 'true_false') {
                const userClean = String(typeof userAns === 'number' && q.options[userAns] ? q.options[userAns] : userAns).trim().toLowerCase();
                const corrClean = String(q.correct_answer ?? '').trim().toLowerCase();
                return userClean === corrClean;
            }

            // 5. MCQ / AUDIO LISTENING / DEFAULT
            if (typeof userAns === 'number') {
                if (q.answer !== undefined && userAns === Number(q.answer)) return true;
                if (q.options && q.options[userAns] !== undefined) {
                    const optText = String(q.options[userAns]).trim().toLowerCase();
                    const correctText = String(q.correct_answer ?? '').trim().toLowerCase();
                    if (optText === correctText) return true;
                }
            }

            const cleanUser = String(userAns).trim().toLowerCase();
            const cleanCorrect = String(q.correct_answer ?? '').trim().toLowerCase();
            if (cleanCorrect && cleanUser === cleanCorrect) return true;

            if (q.options && typeof q.answer === 'number' && q.options[q.answer] !== undefined) {
                if (cleanUser === String(q.options[q.answer]).trim().toLowerCase()) return true;
            }

            return false;
        },

        getUserAnswerDisplay(idx) {
            const q = this.questions[idx];
            const ans = this.answers[idx];
            if (!this.isQuestionAnswered(idx)) return '(Chưa trả lời)';

            if (q.question_type === 'matching') {
                return Object.entries(ans || {}).map(([k, v]) => `${k} ➜ ${v}`).join(' | ');
            }

            if (q.question_type === 'multiple_select') {
                return Array.isArray(ans) ? ans.join(', ') : String(ans);
            }

            if (q.question_type === 'word_ordering' || q.question_type === 'drag_drop') {
                return Array.isArray(ans) ? ans.map(t => typeof t === 'object' ? t.word : t).join(' ') : String(ans);
            }

            if (typeof ans === 'number' && q.options && q.options[ans] !== undefined) {
                return String.fromCharCode(65 + ans) + '. ' + q.options[ans];
            }

            return String(ans);
        },

        getCorrectAnswerDisplay(idx) {
            const q = this.questions[idx];
            if (q.question_type === 'matching') {
                let corr = q.correct_answer;
                if (typeof corr === 'string') {
                    try { corr = JSON.parse(corr); } catch(e) {}
                }
                if (corr && typeof corr === 'object') {
                    return Object.entries(corr).map(([k, v]) => `${k} ➜ ${v}`).join(' | ');
                }
            }

            if (q.question_type === 'multiple_select') {
                let corr = q.correct_answer;
                if (typeof corr === 'string') {
                    try { corr = JSON.parse(corr); } catch(e) {}
                }
                return Array.isArray(corr) ? corr.join(', ') : String(corr);
            }

            if (q.options && typeof q.answer === 'number' && q.options[q.answer] !== undefined) {
                return String.fromCharCode(65 + q.answer) + '. ' + q.options[q.answer];
            }

            if (q.correct_answer !== undefined && q.correct_answer !== null) {
                if (Array.isArray(q.options)) {
                    const foundIdx = q.options.findIndex(o => String(o).trim().toLowerCase() === String(q.correct_answer).trim().toLowerCase());
                    if (foundIdx !== -1) {
                        return String.fromCharCode(65 + foundIdx) + '. ' + q.options[foundIdx];
                    }
                }
                return String(q.correct_answer);
            }
            return 'Đang cập nhật';
        },

        startQuiz() {
            if (!this.canAttempt) {
                alert(`Bạn đã hết số lần làm bài cho phép (${this.maxAttempts} lần).`);
                return;
            }
            this.questions.forEach((q, idx) => {
                if (q.question_type === 'matching') {
                    this.answers[idx] = {};
                } else if (q.question_type === 'multiple_select') {
                    this.answers[idx] = [];
                } else if (q.question_type === 'word_ordering' || q.question_type === 'drag_drop') {
                    this.answers[idx] = [];
                } else {
                    this.answers[idx] = null;
                }
                this.flagged[idx] = false;
            });
            this.currentIndex = 0;
            this.elapsedSeconds = 0;
            this.startedAtIso = new Date().toISOString();
            if (this.timeLimitMinutes > 0) {
                this.timeRemaining = this.timeLimitMinutes * 60;
            }
            this.currentReviewAttempt = null;
            this.viewMode = 'taking';
            this.startTimer();
        },

        openReview(attempt) {
            this.currentReviewAttempt = attempt;
            this.viewMode = 'review';
        },

        finishReview() {
            this.viewMode = 'landing';
        },

        formatSeconds(sec) {
            sec = sec || 0;
            const m = String(Math.floor(sec / 60)).padStart(2, '0');
            const s = String(sec % 60).padStart(2, '0');
            return `${m}:${s}`;
        },

        formatDate(dtStr) {
            if (!dtStr) return '-';
            try {
                const d = new Date(dtStr);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                const hours = String(d.getHours()).padStart(2, '0');
                const mins = String(d.getMinutes()).padStart(2, '0');
                return `${day}/${month}/${year} ${hours}:${mins}`;
            } catch(e) {
                return dtStr;
            }
        },

        submitQuiz() {
            if (this.unansweredCount > 0) {
                this.showConfirmModal = true;
            } else {
                this.confirmSubmit();
            }
        },

        confirmSubmit() {
            this.showConfirmModal = false;
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.submitting = true;

            const payload = this.questions.map((q, idx) => {
                return {
                    question_id: q.id,
                    version: q.version || 1,
                    question_text: q.question,
                    question_type: q.question_type,
                    options: q.options || [],
                    user_answer: this.answers[idx],
                    user_answer_display: this.getUserAnswerDisplay(idx),
                    correct_answer: q.correct_answer ?? q.answer,
                    correct_answer_display: this.getCorrectAnswerDisplay(idx),
                    is_correct: this.isQuestionCorrect(idx),
                    is_answered: this.isQuestionAnswered(idx),
                    explanation: q.explanation || ''
                };
            });

            const percent = this.percentage;

            fetch('{{ route("activities.complete", $activity->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    score: percent,
                    max_score: 100,
                    time_spent_seconds: this.elapsedSeconds,
                    answers_payload: payload,
                    started_at: this.startedAtIso
                })
            })
            .then(r => r.json())
            .then(data => {
                this.submitting = false;
                if (data.trial_mode) {
                    alert('✨ Bạn đang ở chế độ học thử. Hãy ghi danh vào khóa học để lưu tiến trình!');
                    this.viewMode = 'review';
                    return;
                }
                if (data.success) {
                    if (data.attempts) {
                        this.attempts = data.attempts;
                    } else if (data.attempt) {
                        this.attempts.push(data.attempt);
                    }
                    if (data.final_grade !== undefined) {
                        this.finalGrade = data.final_grade;
                    }
                    if (data.can_reattempt !== undefined) {
                        this.canAttempt = data.can_reattempt;
                    }
                    if (data.remaining_attempts !== undefined) {
                        this.remainingAttempts = data.remaining_attempts;
                    }
                    this.currentReviewAttempt = data.attempt || {
                        attempt_number: this.attempts.length,
                        score: percent,
                        max_score: 100,
                        percentage: percent,
                        is_passed: percent >= this.passingGrade,
                        time_spent_seconds: this.elapsedSeconds,
                        answers_payload: payload,
                        completed_at: new Date().toISOString()
                    };
                    this.viewMode = 'review';

                    // Synchronize telemetry
                    const tel = Alpine.$data(document.querySelector('[x-data^="activityTelemetry"]'));
                    if (tel && data.is_completed) {
                        tel.isCompleted = true;
                    }
                    if (data.reward && data.reward.xp_earned) {
                        let msg = '🎉 ' + (data.message || 'Hoàn thành lượt làm bài kiểm tra!');
                        msg += `\n⚡ +${data.reward.xp_earned} XP | +${data.reward.coins_earned} Coins | 🔥 Streak: ${data.reward.streak_count} ngày`;
                        if (data.lesson_completed) {
                            msg += '\n\n✨ Xuất sắc! Bạn đã hoàn thành toàn bộ bài học này và mở khóa bài học kế tiếp!';
                        }
                        alert(msg);
                    }
                } else {
                    alert(data.message || 'Không thể lưu bài kiểm tra.');
                    this.viewMode = 'landing';
                }
            })
            .catch(err => {
                console.error('Quiz submit error:', err);
                this.submitting = false;
                this.viewMode = 'review';
            });
        },

        retakeQuiz() {
            this.startQuiz();
        },

        speakQuestion(text) {
            if ('speechSynthesis' in window && text) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                utterance.rate = 0.95;
                window.speechSynthesis.speak(utterance);
            }
        },

        playCustomAudio(url) {
            if (!url) return;
            if (this.audioElement) {
                this.audioElement.pause();
                this.audioElement = null;
                this.isPlayingAudio = false;
                return;
            }
            this.audioElement = new Audio(url);
            this.isPlayingAudio = true;
            this.audioElement.onended = () => {
                this.isPlayingAudio = false;
                this.audioElement = null;
            };
            this.audioElement.play().catch(() => {
                this.isPlayingAudio = false;
                this.audioElement = null;
            });
        }
    };
}
</script>
@endif
