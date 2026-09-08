<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Phòng Thi Thích Ứng AI 4 Kỹ Năng - ESL CBT Room</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        /* Standalone CBT Exam Room Strict Color System */
        body, .cbt-page {
            background-color: #111625 !important;
            color: #f1f5f9 !important;
            font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif;
        }

        .cbt-topbar {
            background-color: #161d31 !important;
            border-color: #1f2942 !important;
        }

        .cbt-card {
            background-color: #182035 !important;
            border: 1px solid #253252 !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5) !important;
        }

        .cbt-subcard {
            background-color: #1f2944 !important;
            border: 1px solid #2b395b !important;
        }

        .cbt-inner-box {
            background-color: #141b2d !important;
            border: 1px solid #1f2942 !important;
        }

        .cbt-bottombar {
            background-color: #161d31 !important;
            border-top: 1px solid #232f4e !important;
        }

        .cbt-btn-blue {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        .cbt-btn-blue:hover {
            background-color: #1d4ed8 !important;
        }

        .cbt-btn-icon {
            background-color: #1f2944 !important;
            border: 1px solid #2b3a5e !important;
            color: #cbd5e1 !important;
        }
        .cbt-btn-icon:hover {
            background-color: #273456 !important;
            color: #ffffff !important;
        }

        /* Question Options */
        .cbt-option {
            background-color: #151c30 !important;
            border: 1px solid #263353 !important;
            color: #cbd5e1 !important;
            transition: all 0.2s ease;
        }
        .cbt-option:hover:not(.disabled) {
            background-color: #1e2741 !important;
            border-color: #374974 !important;
            color: #ffffff !important;
        }
        .cbt-option.selected {
            background-color: #243358 !important;
            border: 1px solid #3b82f6 !important;
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.5) !important;
            color: #ffffff !important;
        }

        .cbt-radio-circle {
            border: 2px solid #43557d !important;
            background-color: #1a233a !important;
        }
        .cbt-option:hover .cbt-radio-circle {
            border-color: #3b82f6 !important;
        }
        .cbt-option.selected .cbt-radio-circle {
            border-color: #60a5fa !important;
            background-color: #2563eb !important;
        }

        /* Bottom Question Pills */
        .cbt-q-pill {
            background-color: #1c243a !important;
            border: 1px solid #2b395b !important;
            color: #cbd5e1 !important;
        }
        .cbt-q-pill:hover {
            background-color: #25304e !important;
            color: #ffffff !important;
        }
        .cbt-q-pill.active {
            background-color: #2563eb !important;
            border-color: #60a5fa !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            box-shadow: 0 0 12px rgba(37, 99, 246, 0.6) !important;
        }
        .cbt-q-pill.answered {
            background-color: rgba(16, 185, 129, 0.2) !important;
            border-color: rgba(16, 185, 129, 0.5) !important;
            color: #34d399 !important;
        }
        .cbt-q-pill.flagged {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.4) !important;
        }

        /* Custom sleek scrollbar */
        .cbt-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .cbt-scrollbar::-webkit-scrollbar-track {
            background: #111625;
            border-radius: 8px;
        }
        .cbt-scrollbar::-webkit-scrollbar-thumb {
            background: #2563eb;
            border-radius: 8px;
        }
        .cbt-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
        }

        /* Textarea Dark Styling */
        textarea {
            background-color: #141b2d !important;
            color: #f1f5f9 !important;
            caret-color: #38bdf8 !important;
        }
        textarea::placeholder {
            color: #64748b !important;
        }

        /* Pulse wave animation for recording */
        @keyframes pulse-wave {
            0%, 100% { height: 8px; }
            50% { height: 28px; }
        }
        .wave-bar {
            animation: pulse-wave 1s ease-in-out infinite;
        }
        .wave-bar:nth-child(2) { animation-delay: 0.15s; }
        .wave-bar:nth-child(3) { animation-delay: 0.3s; }
        .wave-bar:nth-child(4) { animation-delay: 0.45s; }
        .wave-bar:nth-child(5) { animation-delay: 0.6s; }
    </style>
</head>
<body class="cbt-page min-h-screen select-none font-sans overflow-hidden antialiased"
      x-data="adaptiveCbtApp({{ $session->id }})" 
      x-init="initApp()"
      @keydown.window="handleGlobalKey($event)">

    {{-- ========================================================================= --}}
    {{-- SCREEN 1: SKILL INTRO / BRIEFING SCREEN (Màn hình Chờ cho từng Kỹ Năng)   --}}
    {{-- ========================================================================= --}}
    <div x-show="currentScreen === 'intro' && !isFinished" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-98"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 cbt-page flex flex-col overflow-y-auto">
        
        {{-- Intro Top Bar --}}
        <header class="h-16 px-6 sm:px-10 border-b cbt-topbar flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <span class="text-base sm:text-lg font-bold text-white tracking-wide">
                    Bài Thi Thích Ứng AI (4 Kỹ Năng)
                </span>
                <span class="text-[11px] font-mono px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 font-semibold uppercase"
                      x-text="'Kỹ năng ' + currentSkillOrder + '/4: ' + currentSkillDisplayName">
                    Kỹ năng 1/4: Kỹ năng Nghe
                </span>
            </div>

            <button type="button" 
                    @click="openExitModal = true" 
                    class="text-xs sm:text-sm font-bold text-gray-300 hover:text-white flex items-center gap-2 px-3.5 py-1.5 rounded-xl hover:bg-slate-800 transition-all border border-transparent hover:border-slate-700 cursor-pointer"
                    title="Thoát phòng thi">
                <span>Thoát</span>
                <span class="text-base font-bold">✕</span>
            </button>
        </header>

        {{-- Intro Center Card Container --}}
        <div class="flex-1 flex items-center justify-center p-4 sm:p-8">
            <div class="max-w-2xl w-full cbt-card rounded-3xl p-6 sm:p-10 text-center space-y-6 relative shadow-2xl">
                
                {{-- Skill Icon Circle --}}
                <div class="flex justify-center">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full flex items-center justify-center shadow-xl transition-all"
                         :class="currentSkillIconBg">
                        <span class="text-3xl sm:text-4xl" x-text="currentSkillEmoji">🎧</span>
                    </div>
                </div>

                {{-- Skill Title & Time --}}
                <div class="space-y-1.5">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight" x-text="currentSkillDisplayName">
                        Kỹ năng Nghe (Listening)
                    </h1>
                    <div class="flex items-center justify-center gap-2 text-sm sm:text-base font-semibold text-gray-300">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Thời lượng đề xuất: <strong class="text-white font-mono" x-text="currentSkillDurationText">15 phút</strong></span>
                        <span class="text-gray-500">&bull;</span>
                        <span class="text-blue-300 font-mono text-xs font-bold" x-text="'Câu ' + currentSkillStepRange">Câu 1 – 3</span>
                    </div>
                </div>

                {{-- Headphone Sound Check (Listening Only) --}}
                <template x-if="currentSkillKey === 'listening'">
                    <div class="cbt-subcard rounded-2xl p-5 text-left space-y-3 shadow-inner">
                        <div class="flex items-center gap-2 text-sm font-bold text-white">
                            <span class="text-base">🎧</span>
                            <span>Kiểm tra âm thanh tai nghe</span>
                        </div>
                        <p class="text-xs text-gray-300 leading-relaxed">
                            Hãy đảm bảo rằng âm thanh tai nghe của bạn đủ rõ ràng trước khi bắt đầu phần thi Nghe. Nhấp vào nút phát dưới đây để kiểm tra chất lượng âm thanh:
                        </p>

                        {{-- Headphone Test Audio Player --}}
                        <div class="bg-[#151c2f] rounded-xl p-3 flex items-center gap-3 border border-slate-800">
                            <button type="button" 
                                    @click="toggleTestAudio()" 
                                    class="w-9 h-9 rounded-full cbt-btn-blue text-white flex items-center justify-center transition-transform hover:scale-105 flex-shrink-0 shadow-md cursor-pointer">
                                <span class="text-xs font-bold" x-text="isTestAudioPlaying ? '⏸' : '▶'">▶</span>
                            </button>

                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>

                            <div class="flex-1 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full transition-all duration-200" 
                                     :style="'width: ' + testAudioProgress + '%;'"></div>
                            </div>

                            <span class="text-[11px] font-mono text-gray-400 flex-shrink-0" x-text="testAudioCurrentTimeFormatted">00:00 / 00:14</span>
                        </div>
                    </div>
                </template>

                {{-- Skill Instructions Block --}}
                <div class="flex items-start gap-3 text-left text-xs sm:text-sm text-gray-300 leading-relaxed cbt-inner-box rounded-2xl p-5">
                    <span class="text-blue-400 text-lg flex-shrink-0">✦</span>
                    <p x-text="currentSkillInstructions">
                        Thuật toán Computerized Adaptive Testing (CAT) tự động chọn lọc câu hỏi từ Ngân hàng câu hỏi theo năng lực làm bài thực tế của bạn. Hãy giữ tập trung cao độ và trả lời tốt nhất có thể!
                    </p>
                </div>

                {{-- Start Exam Action Button --}}
                <div class="pt-2 flex justify-center">
                    <button type="button" 
                            @click="startSkillPlaying()" 
                            class="px-10 py-3.5 rounded-xl cbt-btn-blue font-bold text-base shadow-xl transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2 min-w-[220px] cursor-pointer">
                        <span>Bắt đầu làm bài</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Intro Footer Note --}}
        <footer class="py-3 text-center text-xs text-gray-400 font-mono border-t cbt-topbar">
            Hệ thống Khảo thí Trực tuyến ESL CBT Adaptive Engine &copy; 2026
        </footer>
    </div>


    {{-- ========================================================================= --}}
    {{-- SCREEN 2: MAIN CBT EXAM ROOM PLAYER (Phòng Thi Trực Tuyến Chuẩn 4 Kỹ Năng)--}}
    {{-- ========================================================================= --}}
    <div x-show="currentScreen === 'playing' && !isFinished" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-98"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-10 cbt-page flex flex-col overflow-hidden">
        
        {{-- 1. TOP HEADER BAR --}}
        <header class="h-14 sm:h-16 px-4 sm:px-8 border-b cbt-topbar flex items-center justify-between flex-shrink-0 z-20">
            {{-- Left: Back Arrow Icon + Skill Badge & Name --}}
            <div class="flex items-center gap-3 sm:gap-4">
                <button type="button" 
                        @click="openExitModal = true" 
                        class="p-2 rounded-xl cbt-btn-icon transition-colors cursor-pointer hover:bg-slate-800"
                        title="Thoát và lưu tiến trình">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </button>

                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm shadow-md"
                         :class="currentSkillIconBg">
                        <span x-text="currentSkillEmoji">🎧</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm sm:text-base font-extrabold text-white tracking-tight" x-text="currentSkillDisplayName">
                                Kỹ năng Nghe
                            </span>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-md font-bold uppercase bg-slate-800 text-amber-300 border border-slate-700"
                                  x-text="'Cấp độ: ' + currentDifficulty">
                                Cấp độ: A1
                            </span>
                        </div>
                        <span class="text-[10px] text-gray-400 font-mono hidden sm:block">
                            Câu <strong class="text-white" x-text="currentStep">1</strong> / 40 &bull; Chuỗi: 🎧 Nghe (1-12) &bull; 📖 Đọc (13-26) &bull; ✍️ Viết (27-33) &bull; 🎙️ Nói (34-40)
                        </span>
                    </div>
                </div>
            </div>

            {{-- Center: Live Countdown Timer --}}
            <div class="flex items-center gap-2 font-mono font-extrabold text-base sm:text-xl text-white tracking-wider"
                 :class="remainingSeconds < 300 ? 'text-red-400 animate-pulse' : 'text-white'">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="formattedTimer">15:00</span>
            </div>

            {{-- Right: Submit / Finish Exam Button --}}
            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="openSummaryModal = true" 
                        class="px-4 sm:px-6 py-2 rounded-xl cbt-btn-blue text-white font-bold text-xs sm:text-sm shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 cursor-pointer">
                    <span x-text="currentStep >= totalTestQuestions ? 'Nộp bài thi' : 'Nộp bài & Tổng kết'">Nộp bài</span>
                    <svg class="w-4 h-4 transform rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        </header>

        {{-- 2. DIFFICULTY SHIFT NOTIFICATION BANNER --}}
        <div x-show="difficultyAlert.show" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="px-4 sm:px-8 py-2 text-xs font-semibold flex items-center justify-between z-20 flex-shrink-0"
             :class="difficultyAlert.type === 'up' ? 'bg-emerald-500/20 text-emerald-300 border-b border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border-b border-amber-500/40'">
            <div class="flex items-center gap-2">
                <span class="text-sm" x-text="difficultyAlert.type === 'up' ? '🚀' : '⚖️'"></span>
                <span x-text="difficultyAlert.message"></span>
            </div>
            <button @click="difficultyAlert.show = false" class="text-white/60 hover:text-white text-base font-bold">&times;</button>
        </div>

        {{-- 3. LISTENING AUDIO BAR (Only displayed for Listening Skill) --}}
        <template x-if="currentQuestion && (currentQuestion.skill === 'listening' || currentQuestion.question_type === 'audio_listening')">
            <div class="px-4 sm:px-8 py-2.5 bg-[#172037] border-b border-[#232f4e] flex items-center gap-4 flex-shrink-0 z-10">
                <button type="button" 
                        @click="toggleListeningAudio()"
                        class="w-9 h-9 rounded-xl cbt-btn-blue text-white flex items-center justify-center flex-shrink-0 shadow-md transition-transform hover:scale-105 cursor-pointer">
                    <span class="text-xs font-bold" x-text="isAudioPlaying ? '⏸' : '▶'">▶</span>
                </button>

                <div class="flex items-center gap-2 text-xs text-gray-300 font-medium">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                    <span>Âm thanh bài nghe</span>
                </div>

                {{-- Audio Seekbar Track --}}
                <div class="flex-1 bg-[#101524] rounded-full h-2 overflow-hidden border border-slate-800">
                    <div class="h-full bg-blue-500 rounded-full transition-all duration-300" 
                         :style="'width: ' + audioProgress + '%;'"></div>
                </div>

                {{-- Duration Counter --}}
                <span class="text-xs font-mono text-gray-300 flex-shrink-0" x-text="audioTimeFormatted">00:00 / 00:30</span>
            </div>
        </template>

        {{-- 4. MAIN WORKSPACE CONTENT AREA (SPECIALIZED BY SKILL TYPE) --}}
        <main class="flex-1 px-4 sm:px-8 py-4 min-h-0 overflow-hidden flex flex-col">
            
            {{-- Loading State --}}
            <template x-if="loading && !currentQuestion">
                <div class="h-full flex items-center justify-center">
                    <div class="cbt-card p-8 rounded-2xl text-center space-y-4 max-w-sm w-full">
                        <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                        <h4 class="text-white font-bold text-base">Hệ thống CAT đang chọn câu hỏi...</h4>
                        <p class="text-xs text-gray-400">Đang truy xuất câu hỏi thích ứng từ Ngân hàng câu hỏi chuẩn CEFR.</p>
                    </div>
                </div>
            </template>

            {{-- Question Loaded --}}
            <template x-if="currentQuestion">
                <div class="h-full min-h-0">
                    
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    {{-- CASE A: READING SKILL (Questions 4 - 6) --}}
                    {{-- Split 2 Columns: Left Passage, Right Questions --}}
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    <template x-if="currentQuestion.skill === 'reading'">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-full min-h-0 overflow-hidden">
                            
                            {{-- LEFT COLUMN: READING PASSAGE --}}
                            <div class="lg:col-span-5 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0">
                                    <h2 class="text-base sm:text-lg font-bold text-white tracking-tight" x-text="currentQuestion.passage_title || 'Đoạn Văn Đọc Hiểu'">
                                        Reading Passage
                                    </h2>
                                    <span class="text-[11px] uppercase tracking-wider font-mono font-bold text-purple-400 mt-1 block" 
                                          x-text="'KỸ NĂNG ĐỌC — BẬC ' + (currentQuestion.difficulty || 'B1') + (currentQuestion.testlet_badge ? ' · ' + currentQuestion.testlet_badge : ' (Câu 13–26)')">
                                        PASSAGE — Questions 13–26
                                    </span>
                                </div>

                                {{-- Passage Scrollable Body --}}
                                <div class="flex-1 overflow-y-auto pr-2 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed font-sans cbt-scrollbar">
                                    <div class="whitespace-pre-line leading-relaxed" x-text="currentQuestion.passage || 'Đọc đoạn văn và chọn đáp án chính xác nhất cho câu hỏi bên phải.'">
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN: QUESTIONS LIST & RADIO CHOICES --}}
                            <div class="lg:col-span-7 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0 flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2.5">
                                            <h3 class="text-base sm:text-lg font-bold text-white" x-text="'Câu hỏi ' + currentStep + ' / ' + totalTestQuestions">
                                                Câu hỏi 13 / 40
                                            </h3>
                                            <template x-if="currentQuestion.testlet_badge">
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30"
                                                      x-text="currentQuestion.testlet_badge"></span>
                                            </template>
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-0.5" x-text="currentQuestion.testlet_info || 'Chọn 1 đáp án chính xác nhất A, B, C hoặc D.'">
                                            Chọn 1 đáp án chính xác nhất A, B, C hoặc D.
                                        </p>
                                    </div>

                                    <button type="button" 
                                            @click="toggleFlag(currentQuestion.id)" 
                                            class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-2 select-none shadow-sm cursor-pointer"
                                            :class="flaggedQuestions[currentQuestion.id] 
                                                ? 'bg-amber-500/20 text-amber-300 border-amber-500/50 ring-1 ring-amber-500/30' 
                                                : 'cbt-btn-icon text-gray-300 hover:text-white'">
                                        <svg class="w-3.5 h-3.5 transition-transform" 
                                             :class="flaggedQuestions[currentQuestion.id] ? 'text-amber-400 fill-current' : 'text-gray-400'" 
                                             viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                                        </svg>
                                        <span x-text="flaggedQuestions[currentQuestion.id] ? 'Đã gắn cờ' : 'Gắn cờ'"></span>
                                    </button>
                                </div>

                                {{-- Question Content Area --}}
                                <div class="flex-1 overflow-y-auto pr-2 space-y-5 cbt-scrollbar pb-2">
                                    <div class="text-sm sm:text-base font-bold text-white leading-relaxed">
                                        <span class="text-purple-400 font-mono mr-1" x-text="currentStep + '.'"></span>
                                        <span x-text="currentQuestion.question_text"></span>
                                    </div>

                                    {{-- Options List --}}
                                    <div class="space-y-2.5 pt-1">
                                        <template x-for="(opt, optIdx) in (currentQuestion.options || [])" :key="currentQuestion.id + '_opt_' + optIdx">
                                            <div @click="selectAnswer(opt)"
                                                 @dblclick="selectAnswer(opt); nextQuestionOrSubmit()"
                                                 class="cbt-option flex items-center gap-3.5 p-3.5 rounded-xl cursor-pointer select-none group transition-all"
                                                 :class="selectedAnswer === getOptionValue(opt) ? 'selected' : ''">
                                                
                                                {{-- Letter Badge (A, B, C, D) --}}
                                                <span class="w-6 h-6 rounded-lg font-mono font-bold text-xs flex items-center justify-center flex-shrink-0 transition-colors"
                                                      :class="selectedAnswer === getOptionValue(opt)
                                                          ? 'bg-blue-500 text-white shadow-sm'
                                                          : 'bg-slate-800 text-gray-400 border border-slate-700 group-hover:border-blue-500/50 group-hover:text-blue-300'"
                                                      x-text="['A','B','C','D','E','F'][optIdx] || (optIdx + 1)">
                                                </span>

                                                <div class="cbt-radio-circle w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 transition-colors">
                                                    <div x-show="selectedAnswer === getOptionValue(opt)" class="w-2 h-2 rounded-full bg-white"></div>
                                                </div>

                                                <span class="text-xs sm:text-sm flex-1 leading-relaxed" x-text="getOptionText(opt)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Bottom Controls inside Question Card --}}
                                <div class="pt-3 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                                    <span class="text-xs text-gray-400 font-mono">
                                        Câu <strong class="text-white font-bold" x-text="currentStep"></strong> / <span x-text="totalTestQuestions"></span> (Kỹ năng: Đọc)
                                    </span>

                                    <div class="flex items-center gap-2.5">
                                        <button type="button" 
                                                @click="nextQuestionOrSubmit()" 
                                                :disabled="!selectedAnswer || submitting"
                                                class="px-6 py-2.5 rounded-xl cbt-btn-blue disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold shadow-lg transition-transform hover:scale-105 cursor-pointer flex items-center gap-2">
                                            <span x-text="submitting ? 'Đang lưu...' : (currentStep === totalTestQuestions ? 'Hoàn thành bài thi' : 'Câu tiếp theo')"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>


                    {{-- ───────────────────────────────────────────────────────────── --}}
                    {{-- CASE B: LISTENING SKILL (Questions 1 - 3) --}}
                    {{-- Split 2 Columns: Left Audio & Guidance, Right Questions --}}
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    <template x-if="currentQuestion.skill === 'listening'">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-full min-h-0 overflow-hidden">
                            
                            {{-- LEFT COLUMN: LISTENING GUIDANCE & SCENARIO --}}
                            <div class="lg:col-span-5 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0">
                                    <h2 class="text-base sm:text-lg font-bold text-white tracking-tight">
                                        Hướng Dẫn Phần Nghe
                                    </h2>
                                    <span class="text-[11px] uppercase tracking-wider font-mono font-bold text-emerald-400 mt-1 block"
                                          x-text="'KỸ NĂNG NGHE — BẬC ' + currentQuestion.difficulty + ' (Câu 1–12)'">
                                        LISTENING — Questions 1–12
                                    </span>
                                </div>

                                <div class="flex-1 overflow-y-auto pr-2 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed font-sans cbt-scrollbar">
                                    <div class="cbt-subcard rounded-xl p-4 space-y-2 border border-emerald-500/20">
                                        <span class="text-xs font-bold text-emerald-300 block">🎧 Thao tác bài thi:</span>
                                        <p class="text-xs text-gray-300 leading-relaxed">
                                            Bấm nút <strong class="text-white">▶ Play</strong> trên thanh âm thanh phía trên để nghe đoạn phát biểu hoặc hội thoại. Bạn có thể nghe lại để chọn câu trả lời chính xác nhất.
                                        </p>
                                    </div>

                                    <div class="cbt-inner-box rounded-xl p-4 space-y-2">
                                        <span class="text-xs font-bold text-gray-300 block">✦ Ngữ cảnh câu hỏi:</span>
                                        <p class="text-xs text-gray-400 leading-relaxed" x-text="currentQuestion.part_name || 'Hội thoại & Thông báo đời sống hàng ngày / Công sở'">
                                        </p>
                                    </div>

                                    {{-- CEFR Ladder Spotlight --}}
                                    <div class="cbt-subcard p-3 rounded-xl border border-blue-500/20 space-y-1.5">
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-400">Độ khó thích ứng hiện tại:</span>
                                            <span class="font-mono font-bold text-blue-400" x-text="'Bậc ' + currentDifficulty"></span>
                                        </div>
                                        <div class="grid grid-cols-5 gap-1 pt-1">
                                            <template x-for="lvl in ['A1', 'A2', 'B1', 'B2', 'C1']" :key="lvl">
                                                <div class="text-center py-1 rounded text-[10px] font-mono font-bold border transition-all"
                                                     :class="currentDifficulty === lvl 
                                                        ? 'bg-blue-600 text-white border-blue-400 shadow-md shadow-blue-500/30 ring-1 ring-blue-400' 
                                                        : 'bg-[#151c30] text-gray-500 border-slate-800'">
                                                    <span x-text="lvl"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN: QUESTIONS LIST & RADIO CHOICES --}}
                            <div class="lg:col-span-7 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base sm:text-lg font-bold text-white" x-text="'Câu hỏi ' + currentStep + ' / ' + totalTestQuestions">
                                            Câu hỏi 1 / 40
                                        </h3>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            Nghe đoạn băng và chọn 1 đáp án chính xác nhất A, B, C hoặc D.
                                        </p>
                                    </div>

                                    <button type="button" 
                                            @click="toggleFlag(currentQuestion.id)" 
                                            class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-2 select-none shadow-sm cursor-pointer"
                                            :class="flaggedQuestions[currentQuestion.id] 
                                                ? 'bg-amber-500/20 text-amber-300 border-amber-500/50 ring-1 ring-amber-500/30' 
                                                : 'cbt-btn-icon text-gray-300 hover:text-white'">
                                        <svg class="w-3.5 h-3.5 transition-transform" 
                                             :class="flaggedQuestions[currentQuestion.id] ? 'text-amber-400 fill-current' : 'text-gray-400'" 
                                             viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                                        </svg>
                                        <span x-text="flaggedQuestions[currentQuestion.id] ? 'Đã gắn cờ' : 'Gắn cờ'"></span>
                                    </button>
                                </div>

                                {{-- Question Content Area --}}
                                <div class="flex-1 overflow-y-auto pr-2 space-y-5 cbt-scrollbar pb-2">
                                    <div class="text-sm sm:text-base font-bold text-white leading-relaxed">
                                        <span class="text-emerald-400 font-mono mr-1" x-text="currentStep + '.'"></span>
                                        <span x-text="currentQuestion.question_text"></span>
                                    </div>

                                    {{-- Options List --}}
                                    <div class="space-y-2.5 pt-1">
                                        <template x-for="(opt, optIdx) in (currentQuestion.options || [])" :key="currentQuestion.id + '_opt_' + optIdx">
                                            <div @click="selectAnswer(opt)"
                                                 @dblclick="selectAnswer(opt); nextQuestionOrSubmit()"
                                                 class="cbt-option flex items-center gap-3.5 p-3.5 rounded-xl cursor-pointer select-none group transition-all"
                                                 :class="selectedAnswer === getOptionValue(opt) ? 'selected' : ''">
                                                
                                                {{-- Letter Badge (A, B, C, D) --}}
                                                <span class="w-6 h-6 rounded-lg font-mono font-bold text-xs flex items-center justify-center flex-shrink-0 transition-colors"
                                                      :class="selectedAnswer === getOptionValue(opt)
                                                          ? 'bg-blue-500 text-white shadow-sm'
                                                          : 'bg-slate-800 text-gray-400 border border-slate-700 group-hover:border-blue-500/50 group-hover:text-blue-300'"
                                                      x-text="['A','B','C','D','E','F'][optIdx] || (optIdx + 1)">
                                                </span>

                                                <div class="cbt-radio-circle w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 transition-colors">
                                                    <div x-show="selectedAnswer === getOptionValue(opt)" class="w-2 h-2 rounded-full bg-white"></div>
                                                </div>

                                                <span class="text-xs sm:text-sm flex-1 leading-relaxed" x-text="getOptionText(opt)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Bottom Controls --}}
                                <div class="pt-3 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                                    <span class="text-xs text-gray-400 font-mono">
                                        Câu <strong class="text-white font-bold" x-text="currentStep"></strong> / <span x-text="totalTestQuestions"></span> (Kỹ năng: Nghe)
                                    </span>

                                    <div class="flex items-center gap-2.5">
                                        <button type="button" 
                                                @click="nextQuestionOrSubmit()" 
                                                :disabled="!selectedAnswer || submitting"
                                                class="px-6 py-2.5 rounded-xl cbt-btn-blue disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold shadow-lg transition-transform hover:scale-105 cursor-pointer flex items-center gap-2">
                                            <span x-text="submitting ? 'Đang lưu...' : (currentStep === totalTestQuestions ? 'Hoàn thành bài thi' : 'Câu tiếp theo')"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>


                    {{-- ───────────────────────────────────────────────────────────── --}}
                    {{-- CASE C: WRITING SKILL (Questions 7 - 8) --}}
                    {{-- Prompt on Left, Live Textarea & Word Count on Right --}}
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    <template x-if="currentQuestion.skill === 'writing'">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-full min-h-0 overflow-hidden">
                            
                            {{-- LEFT: WRITING TASK PROMPT --}}
                            <div class="lg:col-span-5 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0">
                                    <span class="text-[11px] uppercase tracking-wider font-mono font-bold text-cyan-400 bg-cyan-500/10 px-2.5 py-1 rounded-md border border-cyan-500/20">
                                        <span x-text="'KỸ NĂNG VIẾT — BẬC ' + currentQuestion.difficulty + ' (Câu ' + currentStep + '/' + totalTestQuestions + ' · Chặng 27–33)'"></span>
                                    </span>
                                    <h2 class="text-base sm:text-lg font-bold text-white tracking-tight mt-2.5">
                                        Yêu Cầu Đề Bài Viết
                                    </h2>
                                </div>

                                <div class="flex-1 overflow-y-auto pr-2 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed font-sans cbt-scrollbar">
                                    <div class="cbt-subcard rounded-xl p-4 space-y-2 border border-cyan-500/20">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-cyan-300">Quy định số từ:</span>
                                            <span class="font-mono text-xs font-bold text-cyan-400" x-text="'Tối thiểu ' + (currentQuestion.min_words || 40) + ' từ'"></span>
                                        </div>
                                    </div>

                                    <div class="cbt-inner-box rounded-xl p-4 text-gray-200 whitespace-pre-line leading-relaxed font-sans"
                                         x-text="currentQuestion.question_text">
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT: LIVE TEXTAREA WITH WORD COUNTER --}}
                            <div class="lg:col-span-7 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <h3 class="text-base sm:text-lg font-bold text-white">
                                            Soạn thảo bài viết của bạn
                                        </h3>
                                        <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg border"
                                              :class="currentWordCount >= (currentQuestion.min_words || 40) 
                                                  ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' 
                                                  : 'bg-amber-500/15 text-amber-400 border-amber-500/30'"
                                              x-text="'Số từ: ' + currentWordCount + ' / ' + (currentQuestion.min_words || 40) + ' từ'">
                                        </span>
                                    </div>

                                    <button type="button" 
                                            @click="toggleFlag(currentQuestion.id)" 
                                            class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-2 select-none shadow-sm cursor-pointer"
                                            :class="flaggedQuestions[currentQuestion.id] 
                                                ? 'bg-amber-500/20 text-amber-300 border-amber-500/50 ring-1 ring-amber-500/30' 
                                                : 'cbt-btn-icon text-gray-300 hover:text-white'">
                                        <svg class="w-3.5 h-3.5" :class="flaggedQuestions[currentQuestion.id] ? 'text-amber-400 fill-current' : 'text-gray-400'" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                                        </svg>
                                        <span x-text="flaggedQuestions[currentQuestion.id] ? 'Đã cờ' : 'Cờ'"></span>
                                    </button>
                                </div>

                                {{-- Textarea Writing Editor --}}
                                <div class="flex-1 min-h-0 flex flex-col pb-2">
                                    <textarea x-model="selectedAnswer" 
                                              placeholder="Nhập bài viết của bạn tại đây... Hãy chú ý bố cục các đoạn văn và sử dụng từ nối phù hợp."
                                              class="flex-1 w-full p-4 rounded-xl border border-[#263353] focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 outline-none text-xs sm:text-sm text-gray-100 font-sans resize-none leading-relaxed transition-all cbt-scrollbar">
                                    </textarea>
                                </div>

                                {{-- Controls Footer --}}
                                <div class="pt-3 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                                    <span class="text-xs text-gray-400 font-mono">
                                        Câu <strong class="text-white font-bold" x-text="currentStep"></strong> / <span x-text="totalTestQuestions"></span> (Kỹ năng: Viết)
                                    </span>

                                    <div class="flex items-center gap-2.5">
                                        <button type="button" 
                                                @click="nextQuestionOrSubmit()" 
                                                :disabled="!selectedAnswer || selectedAnswer.trim().length === 0 || submitting"
                                                class="px-6 py-2.5 rounded-xl cbt-btn-blue disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold shadow-lg transition-transform hover:scale-105 cursor-pointer flex items-center gap-2">
                                            <span x-text="submitting ? 'Đang lưu...' : (currentStep === totalTestQuestions ? 'Hoàn thành bài thi' : 'Câu tiếp theo')"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>


                    {{-- ───────────────────────────────────────────────────────────── --}}
                    {{-- CASE D: SPEAKING SKILL (Questions 9 - 10) --}}
                    {{-- Prompt on Left, Voice Recording Studio on Right --}}
                    {{-- ───────────────────────────────────────────────────────────── --}}
                    <template x-if="currentQuestion.skill === 'speaking'">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-full min-h-0 overflow-hidden">
                            
                            {{-- LEFT: SPEAKING PROMPT & TOPIC --}}
                            <div class="lg:col-span-5 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0">
                                    <span class="text-[11px] uppercase tracking-wider font-mono font-bold text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-md border border-amber-500/20">
                                        <span x-text="'KỸ NĂNG NÓI — BẬC ' + currentQuestion.difficulty + ' (Câu ' + currentStep + '/' + totalTestQuestions + ' · Chặng 34–40)'"></span>
                                    </span>
                                    <h2 class="text-base sm:text-lg font-bold text-white tracking-tight mt-2.5">
                                        Chủ Đề Nói Trực Tiếp
                                    </h2>
                                </div>

                                <div class="flex-1 overflow-y-auto pr-2 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed font-sans cbt-scrollbar">
                                    <div class="cbt-subcard rounded-xl p-4 space-y-2 border border-amber-500/20">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-bold text-amber-300">Thời gian chuẩn bị & nói:</span>
                                            <span class="font-mono text-gray-300">~ 2 - 3 phút</span>
                                        </div>
                                    </div>

                                    <div class="cbt-inner-box rounded-xl p-4 text-gray-200 whitespace-pre-line leading-relaxed font-sans"
                                         x-text="currentQuestion.question_text">
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT: AUDIO RECORDING STUDIO --}}
                            <div class="lg:col-span-7 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                                <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base sm:text-lg font-bold text-white">
                                            Phòng Thu Âm Câu Trả Lời (AI Speaking Studio)
                                        </h3>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            Nhấp vào biểu tượng Micro để bắt đầu ghi âm và nói câu trả lời của bạn.
                                        </p>
                                    </div>

                                    <button type="button" 
                                            @click="toggleFlag(currentQuestion.id)" 
                                            class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-2 select-none shadow-sm cursor-pointer"
                                            :class="flaggedQuestions[currentQuestion.id] 
                                                ? 'bg-amber-500/20 text-amber-300 border-amber-500/50 ring-1 ring-amber-500/30' 
                                                : 'cbt-btn-icon text-gray-300 hover:text-white'">
                                        <svg class="w-3.5 h-3.5" :class="flaggedQuestions[currentQuestion.id] ? 'text-amber-400 fill-current' : 'text-gray-400'" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                                        </svg>
                                        <span x-text="flaggedQuestions[currentQuestion.id] ? 'Đã cờ' : 'Cờ'"></span>
                                    </button>
                                </div>

                                {{-- Studio Interactive Center --}}
                                <div class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 space-y-6 text-center">
                                    
                                    {{-- Animated Recording Waves --}}
                                    <div class="h-12 flex items-center justify-center gap-1.5">
                                        <template x-if="isRecording">
                                            <div class="flex items-center gap-1.5">
                                                <div class="wave-bar w-1.5 bg-amber-400 rounded-full"></div>
                                                <div class="wave-bar w-1.5 bg-amber-400 rounded-full"></div>
                                                <div class="wave-bar w-1.5 bg-amber-400 rounded-full"></div>
                                                <div class="wave-bar w-1.5 bg-amber-400 rounded-full"></div>
                                                <div class="wave-bar w-1.5 bg-amber-400 rounded-full"></div>
                                            </div>
                                        </template>
                                        <template x-if="!isRecording && !recordedAudioUrl">
                                            <span class="text-xs text-gray-500 font-mono">Microphone sẵn sàng</span>
                                        </template>
                                        <template x-if="!isRecording && recordedAudioUrl">
                                            <span class="text-xs text-emerald-400 font-bold flex items-center gap-1">
                                                <span>✓ Đã lưu bản ghi âm câu trả lời</span>
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Big Center Record Button --}}
                                    <div>
                                        <button type="button" 
                                                @click="toggleRecording()" 
                                                class="w-20 h-20 sm:w-24 sm:h-24 rounded-full flex items-center justify-center shadow-2xl transition-all transform hover:scale-105 cursor-pointer relative"
                                                :class="isRecording ? 'bg-red-600 text-white animate-pulse ring-4 ring-red-500/40' : (recordedAudioUrl ? 'bg-emerald-600 text-white shadow-emerald-600/30' : 'bg-amber-600 hover:bg-amber-500 text-white shadow-amber-600/30')">
                                            <span class="text-3xl" x-text="isRecording ? '⏹' : '🎙️'">🎙️</span>
                                        </button>
                                        <div class="mt-2.5">
                                            <span class="text-xs font-bold block"
                                                  :class="isRecording ? 'text-red-400' : 'text-gray-300'"
                                                  x-text="isRecording ? 'Đang ghi âm... Nhấp để dừng' : (recordedAudioUrl ? 'Nhấp để thu âm lại' : 'Bắt đầu ghi âm')">
                                            </span>
                                            <span class="text-[11px] font-mono text-gray-400" x-text="recordingSecondsFormatted">00:00</span>
                                        </div>
                                    </div>

                                    {{-- Audio Preview Playback --}}
                                    <template x-if="recordedAudioUrl">
                                        <div class="cbt-inner-box p-3 rounded-xl flex items-center gap-3 w-full max-w-sm border border-emerald-500/30">
                                            <button type="button" 
                                                    @click="playPreviewAudio()" 
                                                    class="w-8 h-8 rounded-full cbt-btn-blue text-white flex items-center justify-center shadow flex-shrink-0 cursor-pointer">
                                                <span class="text-xs font-bold" x-text="isPlayingPreview ? '⏸' : '▶'">▶</span>
                                            </button>
                                            <span class="text-xs text-gray-300 flex-1 text-left">Nghe lại bản ghi âm vừa thu</span>
                                            <span class="text-[11px] font-mono text-emerald-400 font-bold" x-text="recordingSeconds + 's'"></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Controls Footer --}}
                                <div class="pt-3 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                                    <span class="text-xs text-gray-400 font-mono">
                                        Câu <strong class="text-white font-bold" x-text="currentStep"></strong> / <span x-text="totalTestQuestions"></span> (Kỹ năng: Nói)
                                    </span>

                                    <div class="flex items-center gap-2.5">
                                        <button type="button" 
                                                @click="nextQuestionOrSubmit()" 
                                                :disabled="!selectedAnswer || submitting"
                                                class="px-6 py-2.5 rounded-xl cbt-btn-blue disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold shadow-lg transition-transform hover:scale-105 cursor-pointer flex items-center gap-2">
                                            <span x-text="submitting ? 'Đang lưu...' : (currentStep === totalTestQuestions ? 'Hoàn thành bài thi' : 'Câu tiếp theo')"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </main>


        {{-- 5. BOTTOM BAR (PARTS & QUESTION PALETTE BOXES) --}}
        <footer class="h-16 flex-shrink-0 cbt-bottombar px-4 sm:px-8 flex items-center justify-between gap-3 z-40">
            
            {{-- 4 Parts Navigation Tabs with Question Pills (40 Questions) --}}
            <div class="flex items-center gap-2 sm:gap-3 w-full py-1 flex-1 h-12 overflow-x-auto cbt-scrollbar">
                
                {{-- Part 1: Nghe (1 - 12) --}}
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all h-full flex-shrink-0"
                     :class="currentSkillKey === 'listening' ? 'bg-[#1b253f] border-emerald-500 text-white shadow-inner' : 'bg-[#141a2c] border-[#222c48] text-gray-400'">
                    <span class="font-bold text-emerald-400 whitespace-nowrap flex-shrink-0">🎧 Nghe (1–12):</span>
                    <div class="flex items-center gap-1.5 flex-nowrap flex-shrink-0">
                        <template x-for="qNum in listeningQuestions" :key="qNum">
                            <button type="button" 
                                    @click="goToStep(qNum)"
                                    class="cbt-q-pill w-7 h-7 rounded-lg text-xs font-mono font-bold flex items-center justify-center transition-all relative select-none flex-shrink-0 cursor-pointer"
                                    :class="{
                                        'active': currentStep === qNum,
                                        'answered': answeredSteps[qNum],
                                        'flagged': flaggedQuestions['q_' + qNum]
                                    }">
                                <span x-text="qNum" class="relative z-10"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Part 2: Đọc (13 - 26) --}}
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all h-full flex-shrink-0"
                     :class="currentSkillKey === 'reading' ? 'bg-[#1b253f] border-purple-500 text-white shadow-inner' : 'bg-[#141a2c] border-[#222c48] text-gray-400'">
                    <span class="font-bold text-purple-400 whitespace-nowrap flex-shrink-0">📖 Đọc (13–26):</span>
                    <div class="flex items-center gap-1.5 flex-nowrap flex-shrink-0">
                        <template x-for="qNum in readingQuestions" :key="qNum">
                            <button type="button" 
                                    @click="goToStep(qNum)"
                                    class="cbt-q-pill w-7 h-7 rounded-lg text-xs font-mono font-bold flex items-center justify-center transition-all relative select-none flex-shrink-0 cursor-pointer"
                                    :class="{
                                        'active': currentStep === qNum,
                                        'answered': answeredSteps[qNum],
                                        'flagged': flaggedQuestions['q_' + qNum]
                                    }">
                                <span x-text="qNum" class="relative z-10"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Part 3: Viết (27 - 33) --}}
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all h-full flex-shrink-0"
                     :class="currentSkillKey === 'writing' ? 'bg-[#1b253f] border-cyan-500 text-white shadow-inner' : 'bg-[#141a2c] border-[#222c48] text-gray-400'">
                    <span class="font-bold text-cyan-400 whitespace-nowrap flex-shrink-0">✍️ Viết (27–33):</span>
                    <div class="flex items-center gap-1.5 flex-nowrap flex-shrink-0">
                        <template x-for="qNum in writingQuestions" :key="qNum">
                            <button type="button" 
                                    @click="goToStep(qNum)"
                                    class="cbt-q-pill w-7 h-7 rounded-lg text-xs font-mono font-bold flex items-center justify-center transition-all relative select-none flex-shrink-0 cursor-pointer"
                                    :class="{
                                        'active': currentStep === qNum,
                                        'answered': answeredSteps[qNum],
                                        'flagged': flaggedQuestions['q_' + qNum]
                                    }">
                                <span x-text="qNum" class="relative z-10"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Part 4: Nói (34 - 40) --}}
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all h-full flex-shrink-0"
                     :class="currentSkillKey === 'speaking' ? 'bg-[#1b253f] border-amber-500 text-white shadow-inner' : 'bg-[#141a2c] border-[#222c48] text-gray-400'">
                    <span class="font-bold text-amber-400 whitespace-nowrap flex-shrink-0">🎙️ Nói (34–40):</span>
                    <div class="flex items-center gap-1.5 flex-nowrap flex-shrink-0">
                        <template x-for="qNum in speakingQuestions" :key="qNum">
                            <button type="button" 
                                    @click="goToStep(qNum)"
                                    class="cbt-q-pill w-7 h-7 rounded-lg text-xs font-mono font-bold flex items-center justify-center transition-all relative select-none flex-shrink-0 cursor-pointer"
                                    :class="{
                                        'active': currentStep === qNum,
                                        'answered': answeredSteps[qNum],
                                        'flagged': flaggedQuestions['q_' + qNum]
                                    }">
                                <span x-text="qNum" class="relative z-10"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Floating Assistant Chat Bubble at Bottom Right --}}
            <div class="flex-shrink-0 pl-1">
                <button type="button" 
                        class="w-10 h-10 rounded-full cbt-btn-blue text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer"
                        title="Trợ lý AI Khảo thí">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </button>
            </div>
        </footer>
    </div>


    {{-- ========================================================================= --}}
    {{-- MODAL 1: CONFIRM SAVE DRAFT & EXIT                                        --}}
    {{-- ========================================================================= --}}
    <div x-show="openExitModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="cbt-card rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl text-center relative animate-in fade-in zoom-in-95 duration-200"
             @click.away="openExitModal = false">
            <div class="w-16 h-16 rounded-3xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mx-auto text-3xl shadow-inner">
                <span>⚠️</span>
            </div>

            <div class="space-y-2">
                <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight leading-snug">
                    Bạn có chắc muốn thoát phòng thi?
                </h3>
                <p class="text-xs sm:text-sm text-gray-400 leading-relaxed px-2">
                    Bạn hiện đang làm câu <strong class="text-amber-400" x-text="currentStep"></strong> / <span x-text="totalTestQuestions">40</span>. Điểm số và câu trả lời của các câu đã hoàn thành sẽ được hệ thống lưu lại an toàn.
                </p>
            </div>

            <div class="flex items-center justify-center gap-3 pt-2">
                <button type="button" 
                        @click="openExitModal = false" 
                        class="flex-1 px-5 py-2.5 rounded-xl bg-[#1f2944] hover:bg-[#28365a] border border-[#2d3b61] text-xs sm:text-sm font-bold text-gray-200 hover:text-white transition-all cursor-pointer">
                    Tiếp tục làm bài
                </button>

                <a href="{{ route('practice.index') }}" 
                   class="flex-1 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs sm:text-sm font-bold text-white shadow-lg cursor-pointer">
                    Xác nhận Thoát
                </a>
            </div>
        </div>
    </div>


    {{-- ========================================================================= --}}
    {{-- MODAL 2: CONFIRM SUBMISSION                                               --}}
    {{-- ========================================================================= --}}
    <div x-show="openSummaryModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="cbt-card rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-5 shadow-2xl text-center relative"
             @click.away="openSummaryModal = false">
            <div class="w-14 h-14 rounded-full bg-blue-600/20 border border-blue-500/40 text-blue-400 text-2xl flex items-center justify-center mx-auto">
                📝
            </div>

            <div class="space-y-1">
                <h3 class="text-xl font-bold text-white">Xác nhận nộp bài thi thích ứng</h3>
                <p class="text-xs text-gray-400">
                    Hệ thống sẽ tổng hợp kết quả 4 kỹ năng và chấm điểm ma trận năng lực CEFR của bạn.
                </p>
            </div>

            {{-- Metrics Grid --}}
            <div class="grid grid-cols-3 gap-2.5">
                <div class="p-3 bg-[#13192a] rounded-xl border border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Đã làm</span>
                    <span class="text-xl font-black text-emerald-400 font-mono" x-text="Object.keys(answeredSteps).length"></span>
                </div>
                <div class="p-3 bg-[#13192a] rounded-xl border border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Chưa làm</span>
                    <span class="text-xl font-black text-red-400 font-mono" x-text="totalTestQuestions - Object.keys(answeredSteps).length"></span>
                </div>
                <div class="p-3 bg-[#13192a] rounded-xl border border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Gắn cờ 🚩</span>
                    <span class="text-xl font-black text-yellow-400 font-mono" x-text="Object.keys(flaggedQuestions).length"></span>
                </div>
            </div>

            <template x-if="totalTestQuestions - Object.keys(answeredSteps).length > 0">
                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs text-left">
                    ⚠️ Bạn vẫn còn <strong x-text="totalTestQuestions - Object.keys(answeredSteps).length"></strong> câu chưa làm. Bạn có chắc chắn muốn nộp bài ngay bây giờ?
                </div>
            </template>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" 
                        @click="openSummaryModal = false" 
                        class="px-4 py-2.5 rounded-xl bg-[#1f2944] hover:bg-[#283556] text-xs font-bold text-gray-300 transition-colors cursor-pointer">
                    Tiếp tục làm bài
                </button>

                <button type="button" 
                        @click="finalizeExam()"
                        class="px-6 py-2.5 rounded-xl cbt-btn-blue text-white text-xs font-bold shadow-lg shadow-blue-600/30 cursor-pointer">
                    Nộp bài & Xem kết quả
                </button>
            </div>
        </div>
    </div>


    {{-- ========================================================================= --}}
    {{-- SCREEN 3: FINAL REPORT / SCORECARD SCREEN (Full 4-Skill Matrix Diagnostic)--}}
    {{-- ========================================================================= --}}
    <div x-show="isFinished" 
         x-cloak 
         style="display: none;"
         class="fixed inset-0 z-50 cbt-page flex flex-col items-center justify-start p-4 sm:p-6 overflow-y-auto cbt-scrollbar">
        
        <div class="cbt-card max-w-4xl w-full p-6 sm:p-8 rounded-3xl space-y-6 shadow-2xl text-center border-blue-500/30 relative overflow-hidden my-auto">
            <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Trophy Badge --}}
            <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-amber-500 to-orange-400 flex items-center justify-center text-4xl shadow-xl shadow-orange-500/20 mx-auto">
                🏆
            </div>

            <div class="space-y-1 relative z-10">
                <span class="text-xs font-mono uppercase tracking-widest text-teal-400 font-bold">ESL Computerized Adaptive Testing (CAT)</span>
                <h2 class="text-2xl sm:text-3xl font-black text-white">Kết Quả Đánh Giá Năng Lực 4 Kỹ Năng</h2>
                <p class="text-xs text-gray-400">Bạn đã hoàn thành trọn vẹn bài thi thích ứng chuẩn 4 Kỹ năng: Nghe &bull; Đọc &bull; Viết &bull; Nói</p>
            </div>

            {{-- Final Assessed Level Spotlight --}}
            <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-950/70 via-indigo-950/50 to-slate-900 border border-blue-500/40 max-w-xl mx-auto text-center space-y-2 relative z-10 shadow-lg">
                <span class="text-[11px] text-gray-300 uppercase tracking-wider font-mono block">TRÌNH ĐỘ CEFR TỔNG THỂ XẾP LOẠI:</span>
                <div class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-teal-300 to-emerald-400 font-mono"
                     x-text="finalReport?.final_level || currentDifficulty">
                    B1
                </div>
                <p class="text-xs text-gray-300 leading-relaxed pt-2 border-t border-slate-700/60"
                   x-text="cefrDescriptor">
                    Hiểu và sử dụng các cấu trúc ngôn ngữ thực tế theo khung tham chiếu Châu Âu CEFR.
                </p>
                <span class="text-[11px] text-teal-300/80 font-mono block">✓ Tự động đồng bộ với lộ trình học tập cá nhân</span>
            </div>

            {{-- 4-SKILL DIAGNOSTIC MATRIX GRID --}}
            <div class="space-y-2.5 text-left relative z-10">
                <span class="text-xs font-bold text-gray-300 uppercase tracking-wider block text-center font-mono">
                    Ma Trận Năng Lực Chi Tiết 4 Kỹ Năng
                </span>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {{-- Listening --}}
                    <div class="cbt-subcard p-3.5 rounded-2xl border border-emerald-500/30 text-center space-y-1.5 shadow-sm">
                        <span class="text-2xl block">🎧</span>
                        <span class="text-xs font-bold text-white block">Nghe (Listening)</span>
                        <div class="text-lg font-mono font-black text-emerald-400" 
                             x-text="skillMatrix?.listening?.assessed_level || 'A2'">A2</div>
                        <span class="text-[11px] text-gray-400 block font-mono" 
                              x-text="(skillMatrix?.listening?.correct || 0) + '/' + (skillMatrix?.listening?.total || 12) + ' câu đúng'"></span>
                    </div>

                    {{-- Reading --}}
                    <div class="cbt-subcard p-3.5 rounded-2xl border border-purple-500/30 text-center space-y-1.5 shadow-sm">
                        <span class="text-2xl block">📖</span>
                        <span class="text-xs font-bold text-white block">Đọc (Reading)</span>
                        <div class="text-lg font-mono font-black text-purple-400"
                             x-text="skillMatrix?.reading?.assessed_level || 'B1'">B1</div>
                        <span class="text-[11px] text-gray-400 block font-mono"
                              x-text="(skillMatrix?.reading?.correct || 0) + '/' + (skillMatrix?.reading?.total || 14) + ' câu đúng'"></span>
                    </div>

                    {{-- Writing --}}
                    <div class="cbt-subcard p-3.5 rounded-2xl border border-cyan-500/30 text-center space-y-1.5 shadow-sm">
                        <span class="text-2xl block">✍️</span>
                        <span class="text-xs font-bold text-white block">Viết (Writing)</span>
                        <div class="text-lg font-mono font-black text-cyan-400"
                             x-text="skillMatrix?.writing?.assessed_level || 'B1'">B1</div>
                        <span class="text-[11px] text-gray-400 block font-mono"
                              x-text="(skillMatrix?.writing?.correct || 0) + '/' + (skillMatrix?.writing?.total || 7) + ' bài đạt'"></span>
                    </div>

                    {{-- Speaking --}}
                    <div class="cbt-subcard p-3.5 rounded-2xl border border-amber-500/30 text-center space-y-1.5 shadow-sm">
                        <span class="text-2xl block">🎙️</span>
                        <span class="text-xs font-bold text-white block">Nói (Speaking)</span>
                        <div class="text-lg font-mono font-black text-amber-400"
                             x-text="skillMatrix?.speaking?.assessed_level || 'B1'">B1</div>
                        <span class="text-[11px] text-gray-400 block font-mono"
                              x-text="(skillMatrix?.speaking?.correct || 0) + '/' + (skillMatrix?.speaking?.total || 7) + ' bài đạt'"></span>
                    </div>
                </div>
            </div>

            {{-- Metrics Grid --}}
            <div class="grid grid-cols-3 gap-3 relative z-10">
                <div class="cbt-subcard p-3.5 rounded-2xl">
                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Tổng Điểm XP</span>
                    <span class="text-2xl sm:text-3xl font-black text-amber-400 font-mono" x-text="score"></span>
                    <span class="text-[10px] text-gray-500 block">XP Tích Lũy</span>
                </div>
                <div class="cbt-subcard p-3.5 rounded-2xl">
                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Độ Chính Xác</span>
                    <span class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono" x-text="Math.round((correctCount / totalTestQuestions) * 100) + '%'"></span>
                    <span class="text-[10px] text-emerald-400/80 block font-mono" x-text="correctCount + '/' + totalTestQuestions + ' câu đúng'"></span>
                </div>
                <div class="cbt-subcard p-3.5 rounded-2xl">
                    <span class="text-[10px] text-gray-400 block uppercase font-bold">ESL Coins</span>
                    <span class="text-2xl sm:text-3xl font-black text-yellow-400 font-mono" x-text="'+' + (finalReport?.coins_earned || Math.ceil(score / 5))"></span>
                    <span class="text-[10px] text-yellow-400/80 block font-mono">Đã cộng vào ví</span>
                </div>
            </div>

            {{-- PRIMARY SCORECARD CTA CALLOUT --}}
            <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-900/40 via-indigo-900/30 to-purple-900/40 border border-blue-500/40 text-center space-y-3 relative z-10 shadow-lg">
                <div class="flex items-center justify-center gap-2 text-xs font-bold text-teal-300 font-mono">
                    <span>⚡ BẢNG ĐIỂM CHẨN ĐOÁN & GIẢI THÍCH CHI TIẾT ĐÃ SẴN SÀNG</span>
                </div>
                <p class="text-xs text-gray-300 max-w-lg mx-auto leading-relaxed">
                    Hệ thống đã biên soạn báo cáo chi tiết toàn diện 40 câu hỏi, đoạn văn đọc hiểu, audio nghe lại, bài viết và lời giải cặn kẽ chuẩn CEFR.
                </p>
                <div class="pt-1">
                    <a :href="'/practice/adaptive/' + sessionId + '/scorecard'" 
                       class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-teal-500 text-white font-extrabold text-sm shadow-xl shadow-blue-500/30 hover:scale-105 hover:brightness-110 transition-all cursor-pointer">
                        <span>🔍 Xem Toàn Bộ Bảng Điểm & Phân Tích 40 Câu Hỏi</span>
                    </a>
                </div>
            </div>

            {{-- QUICK QUESTION REVIEW ACCORDION --}}
            <div class="pt-2 text-left space-y-3 border-t border-slate-800 relative z-10" x-data="{ showQuickReview: false }">
                <div class="flex items-center justify-between">
                    <button type="button" 
                            @click="showQuickReview = !showQuickReview"
                            class="flex items-center gap-2 text-xs font-bold text-blue-400 hover:text-blue-300 transition-colors cursor-pointer">
                        <span x-text="showQuickReview ? '▲ Thu gọn danh sách câu hỏi' : '▼ Xem nhanh danh sách ' + totalTestQuestions + ' câu hỏi đã làm'"></span>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-gray-300" x-text="(answersHistoryReview ? answersHistoryReview.length : 0) + ' câu'"></span>
                    </button>
                    <a :href="'/practice/adaptive/' + sessionId + '/scorecard'" 
                       class="text-xs text-amber-400 hover:text-amber-300 font-bold underline flex items-center gap-1 font-mono">
                        <span>Trang phân tích chi tiết</span>
                    </a>
                </div>

                <div x-show="showQuickReview" x-cloak class="space-y-2.5 max-h-96 overflow-y-auto cbt-scrollbar pr-1">
                    <template x-for="(item, qIdx) in (answersHistoryReview || [])" :key="qIdx">
                        <div class="p-3.5 rounded-xl bg-slate-900/90 border text-xs space-y-2 shadow-sm"
                             :class="item.is_correct ? 'border-emerald-500/30' : 'border-red-500/30'">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-white flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-mono font-bold"
                                          :class="item.is_correct ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30'"
                                          x-text="qIdx + 1"></span>
                                    <span class="uppercase text-[10px] px-2 py-0.5 rounded bg-slate-800 text-gray-300 font-mono font-bold" x-text="item.skill"></span>
                                    <span class="text-[10px] text-gray-400 font-mono" x-text="'Bậc: ' + (item.difficulty || 'A1')"></span>
                                </span>
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full font-mono"
                                      :class="item.is_correct ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/10 text-red-400 border border-red-500/30'"
                                      x-text="item.is_correct ? '✓ Đúng (+10 XP)' : '✕ Chưa đúng'"></span>
                            </div>

                            <p class="text-gray-200 text-xs font-semibold leading-relaxed" x-text="item.question_text"></p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-slate-800/80 text-[11px]">
                                <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800">
                                    <span class="text-gray-400 block text-[10px]">Đã trả lời:</span>
                                    <span class="font-semibold text-white truncate block" x-text="item.user_answer || '(Bỏ trống)'"></span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800">
                                    <span class="text-gray-400 block text-[10px]">Đáp án chuẩn:</span>
                                    <span class="font-semibold text-emerald-400 truncate block" x-text="item.correct_answer || 'Hoàn thành yêu cầu đề bài'"></span>
                                </div>
                            </div>

                            <div x-show="item.explanation" class="text-[11px] text-gray-300 pt-1 leading-relaxed bg-slate-950/40 p-2 rounded-lg border border-slate-800/60">
                                <span class="text-teal-400 font-bold">💡 Giải thích: </span>
                                <span x-text="item.explanation"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="pt-3 flex flex-wrap items-center justify-center gap-3 border-t border-slate-800/80 relative z-10">
                <a :href="'/practice/adaptive/' + sessionId + '/scorecard'" 
                   class="px-5 py-2.5 rounded-xl cbt-btn-blue text-white font-bold text-xs sm:text-sm shadow-lg shadow-blue-500/25 transition-transform hover:scale-105 flex items-center gap-2">
                    <span>📊 Xem Bảng Điểm Toàn Diện</span>
                </a>
                <a href="{{ route('practice.index', ['skill' => 'adaptive']) }}" 
                   class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-gray-200 hover:text-white font-semibold text-xs sm:text-sm border border-slate-700 transition-colors flex items-center gap-1.5">
                    <span>🔄 Thi Bộ Đề Khác</span>
                </a>
                <a href="{{ route('courses.index') }}" 
                   class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-gray-200 hover:text-white font-semibold text-xs sm:text-sm border border-slate-700 transition-colors flex items-center gap-1.5">
                    <span>🎓 Khóa Học Lộ Trình</span>
                </a>
                <a href="{{ route('practice.index') }}" 
                   class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-gray-400 hover:text-white font-semibold text-xs sm:text-sm border border-slate-700 transition-colors">
                    Về Cổng Luyện Đề
                </a>
            </div>
        </div>
    </div>


    {{-- ========================================================================= --}}
    {{-- ALPINE.JS CBT ADAPTIVE ROOM ENGINE                                        --}}
    {{-- ========================================================================= --}}
    <script>
    function adaptiveCbtApp(sessionId) {
        return {
            sessionId: sessionId,
            currentScreen: 'intro', // 'intro' or 'playing'
            currentStep: 1,
            currentDifficulty: 'A1',
            score: 0,
            correctCount: 0,
            currentQuestion: null,
            selectedAnswer: null,
            submitting: false,
            loading: true,
            isFinished: false,
            openExitModal: false,
            openSummaryModal: false,
            answeredSteps: {}, // Track steps completed
            flaggedQuestions: {},
            lastResult: {},
            finalReport: null,
            skillMatrix: null,
            answersHistoryReview: @json($history ?? []),
            difficultyAlert: {
                show: false,
                type: 'up',
                message: ''
            },

            get cefrDescriptor() {
                const lvl = this.finalReport?.final_level || this.currentDifficulty || 'A1';
                const map = {
                    'A1': 'Căn bản (Breakthrough): Nhận biết & sử dụng từ vựng quen thuộc, giới thiệu bản thân và thông tin đơn giản hàng ngày.',
                    'A2': 'Sơ cấp (Waystage): Giao tiếp tình huống thường nhật, hiểu biển báo thông tin, trao đổi sở thích và gia đình.',
                    'B1': 'Trung cấp (Threshold): Tự tin trao đổi học tập, công việc, du lịch; viết thư, bài luận và bày tỏ quan điểm rõ ràng.',
                    'B2': 'Trung cao cấp (Vantage): Đọc hiểu văn bản học thuật chuyên sâu; giao tiếp tự nhiên trôi chảy và tranh luận thuyết phục.',
                    'C1': 'Cao cấp (Effective Operational): Làm chủ tiếng Anh linh hoạt, chuẩn xác, điêu luyện trong môi trường học thuật & quốc tế.',
                };
                return map[lvl] || map['A1'];
            },

            // Countdown Timer (60 minutes for 40 Full Simulation CAT questions)
            remainingSeconds: 60 * 60,
            timerInterval: null,
            totalTestQuestions: 40,
            listeningQuestions: Array.from({length: 12}, (_, i) => i + 1), // 1..12
            readingQuestions: Array.from({length: 14}, (_, i) => i + 13), // 13..26
            writingQuestions: Array.from({length: 7}, (_, i) => i + 27), // 27..33
            speakingQuestions: Array.from({length: 7}, (_, i) => i + 34), // 34..40

            // Audio Player state
            isAudioPlaying: false,
            audioPlayer: null,
            audioProgress: 0,
            audioCurrentTime: 0,
            audioDuration: 30,
            audioProgressInterval: null,

            // Headphone sound check audio state
            isTestAudioPlaying: false,
            testAudioPlayer: null,
            testAudioProgress: 0,
            testAudioCurrentTime: 0,
            testAudioDuration: 14,
            testAudioInterval: null,

            // Voice Recording state
            isRecording: false,
            mediaRecorder: null,
            audioChunks: [],
            recordedAudioUrl: null,
            recordingSeconds: 0,
            recordingInterval: null,
            isPlayingPreview: false,
            previewAudio: null,

            // Computed skill info for current step (Full Simulation 40 Questions)
            get currentSkillKey() {
                if (this.currentStep <= 12) return 'listening';
                if (this.currentStep <= 26) return 'reading';
                if (this.currentStep <= 33) return 'writing';
                return 'speaking';
            },

            get currentSkillOrder() {
                if (this.currentStep <= 12) return 1;
                if (this.currentStep <= 26) return 2;
                if (this.currentStep <= 33) return 3;
                return 4;
            },

            get currentSkillDisplayName() {
                const map = {
                    'listening': 'Kỹ năng Nghe (Listening)',
                    'reading': 'Kỹ năng Đọc (Reading)',
                    'writing': 'Kỹ năng Viết (Writing)',
                    'speaking': 'Kỹ năng Nói (Speaking)'
                };
                return map[this.currentSkillKey] || 'Kỹ năng Nghe (Listening)';
            },

            get currentSkillEmoji() {
                const map = {
                    'listening': '🎧',
                    'reading': '📖',
                    'writing': '✍️',
                    'speaking': '🎙️'
                };
                return map[this.currentSkillKey] || '🎧';
            },

            get currentSkillIconBg() {
                const map = {
                    'listening': 'bg-[#4ade80] text-slate-950',
                    'reading': 'bg-[#a855f7] text-white',
                    'writing': 'bg-[#06b6d4] text-white',
                    'speaking': 'bg-[#f97316] text-white'
                };
                return map[this.currentSkillKey] || 'bg-[#4ade80] text-slate-950';
            },

            get currentSkillDurationText() {
                const map = {
                    'listening': '18 phút',
                    'reading': '22 phút',
                    'writing': '12 phút',
                    'speaking': '8 phút'
                };
                return map[this.currentSkillKey] || '18 phút';
            },

            get currentSkillStepRange() {
                const map = {
                    'listening': '1 – 12',
                    'reading': '13 – 26',
                    'writing': '27 – 33',
                    'speaking': '34 – 40'
                };
                return map[this.currentSkillKey] || '1 – 12';
            },

            get currentSkillInstructions() {
                const map = {
                    'listening': 'Phần thi Nghe gồm 12 câu hỏi thích ứng kiểm tra phản xạ thông báo, hội thoại và bài giảng học thuật. Bấm nút phát trên thanh điều khiển để nghe âm thanh và chọn câu trả lời chính xác.',
                    'reading': 'Phần thi Đọc gồm 14 câu hỏi thích ứng kèm đoạn văn học thuật chuyên sâu. Đọc kỹ văn bản bên cột trái và chọn đáp án chính xác nhất cho các câu hỏi bên cột phải.',
                    'writing': 'Phần thi Viết gồm 7 bài tập thích ứng (xây dựng câu, viết email và bài luận phân tích). Đọc kỹ tình huống đề bài và soạn thảo bài viết hoàn chỉnh với số từ yêu cầu.',
                    'speaking': 'Phần thi Nói gồm 7 bài tập thích ứng (đọc to phản xạ, xử lý tình huống giao tiếp và thuyết trình học thuật). Nhấn nút Micro để ghi âm câu trả lời trực tiếp.'
                };
                return map[this.currentSkillKey] || '';
            },

            get formattedTimer() {
                const m = Math.floor(this.remainingSeconds / 60);
                const s = this.remainingSeconds % 60;
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            },

            get audioTimeFormatted() {
                const curM = Math.floor(this.audioCurrentTime / 60);
                const curS = this.audioCurrentTime % 60;
                const durM = Math.floor(this.audioDuration / 60);
                const durS = this.audioDuration % 60;
                return `${String(curM).padStart(2, '0')}:${String(curS).padStart(2, '0')} / ${String(durM).padStart(2, '0')}:${String(durS).padStart(2, '0')}`;
            },

            get testAudioCurrentTimeFormatted() {
                const curM = Math.floor(this.testAudioCurrentTime / 60);
                const curS = this.testAudioCurrentTime % 60;
                const durM = Math.floor(this.testAudioDuration / 60);
                const durS = this.testAudioDuration % 60;
                return `${String(curM).padStart(2, '0')}:${String(curS).padStart(2, '0')} / ${String(durM).padStart(2, '0')}:${String(durS).padStart(2, '0')}`;
            },

            get recordingSecondsFormatted() {
                const m = Math.floor(this.recordingSeconds / 60);
                const s = this.recordingSeconds % 60;
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            },

            get currentWordCount() {
                if (!this.selectedAnswer || typeof this.selectedAnswer !== 'string') return 0;
                const clean = this.selectedAnswer.trim().replace(/\s+/g, ' ');
                if (!clean) return 0;
                return clean.split(' ').length;
            },

            initApp() {
                @if($session->status === 'finished')
                    this.isFinished = true;
                    this.score = {{ $session->score }};
                    this.correctCount = {{ $session->correct_count }};
                    this.currentDifficulty = '{{ $session->final_level ?: $session->current_difficulty }}';
                    this.skillMatrix = @json($skillMatrix ?? null);
                    this.answersHistoryReview = @json($history ?? []);
                    this.finalReport = {
                        final_level: '{{ $finalLevel }}',
                        coins_earned: {{ ceil($session->score / 5) }},
                        score: {{ $session->score }},
                        correct_count: {{ $session->correct_count }},
                        answers_history: @json($history ?? [])
                    };
                    this.loading = false;
                @else
                    this.loadNextQuestion();
                @endif
            },

            startSkillPlaying() {
                // Stop test audio if playing
                if (this.testAudioPlayer) {
                    this.testAudioPlayer.pause();
                    this.isTestAudioPlaying = false;
                    clearInterval(this.testAudioInterval);
                }

                this.currentScreen = 'playing';

                // Start master countdown timer
                if (!this.timerInterval) {
                    this.timerInterval = setInterval(() => {
                        if (this.remainingSeconds > 0) {
                            this.remainingSeconds--;
                        } else {
                            clearInterval(this.timerInterval);
                            this.finalizeExam();
                        }
                    }, 1000);
                }
            },

            toggleTestAudio() {
                if (this.isTestAudioPlaying) {
                    if (this.testAudioPlayer) this.testAudioPlayer.pause();
                    clearInterval(this.testAudioInterval);
                    this.isTestAudioPlaying = false;
                } else {
                    const testUrl = 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                    if (!this.testAudioPlayer) {
                        this.testAudioPlayer = new Audio(testUrl);
                        this.testAudioPlayer.onended = () => {
                            this.isTestAudioPlaying = false;
                            this.testAudioProgress = 100;
                            clearInterval(this.testAudioInterval);
                        };
                    }
                    this.testAudioPlayer.play().catch(() => {});
                    this.isTestAudioPlaying = true;
                    this.testAudioInterval = setInterval(() => {
                        if (this.testAudioPlayer && this.testAudioPlayer.duration) {
                            this.testAudioProgress = (this.testAudioPlayer.currentTime / this.testAudioPlayer.duration) * 100;
                            this.testAudioCurrentTime = Math.floor(this.testAudioPlayer.currentTime);
                            this.testAudioDuration = Math.floor(this.testAudioPlayer.duration);
                        }
                    }, 250);
                }
            },

            loadNextQuestion() {
                this.loading = true;
                this.selectedAnswer = null;
                this.recordedAudioUrl = null;
                this.recordingSeconds = 0;

                fetch('{{ route("api.adaptive.next") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ session_id: this.sessionId })
                })
                .then(r => r.json())
                .then(data => {
                    this.loading = false;
                    if (data.finished) {
                        this.isFinished = true;
                        clearInterval(this.timerInterval);
                    } else {
                        const prevSkill = this.currentQuestion ? this.currentQuestion.skill : null;
                        this.currentQuestion = data.question;
                        this.currentStep = data.session.current_step;
                        this.currentDifficulty = data.session.current_difficulty;
                        this.score = data.session.score || 0;
                        if (data.session.correct_count !== undefined) {
                            this.correctCount = data.session.correct_count;
                        }

                        // If skill changed to a new skill and not step 1, we show intro briefing screen
                        if (prevSkill && prevSkill !== this.currentQuestion.skill) {
                            this.currentScreen = 'intro';
                        }

                        // Auto initialize audio player if listening
                        if (this.currentQuestion.skill === 'listening') {
                            this.audioProgress = 0;
                            this.audioCurrentTime = 0;
                            this.audioDuration = 30;
                            this.isAudioPlaying = false;
                        }
                    }
                })
                .catch(() => {
                    this.loading = false;
                });
            },

            toggleListeningAudio() {
                if (this.isAudioPlaying) {
                    if (this.audioPlayer) this.audioPlayer.pause();
                    clearInterval(this.audioProgressInterval);
                    this.isAudioPlaying = false;
                } else {
                    const url = this.currentQuestion?.audio_url || 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                    if (!this.audioPlayer || this.audioPlayer.src !== url) {
                        this.audioPlayer = new Audio(url);
                        this.audioPlayer.onloadedmetadata = () => {
                            if (this.audioPlayer.duration && !isNaN(this.audioPlayer.duration)) {
                                this.audioDuration = Math.floor(this.audioPlayer.duration);
                            }
                        };
                        this.audioPlayer.onended = () => {
                            this.isAudioPlaying = false;
                            this.audioProgress = 100;
                            clearInterval(this.audioProgressInterval);
                        };
                    }
                    this.audioPlayer.play().catch(() => {});
                    this.isAudioPlaying = true;
                    this.audioProgressInterval = setInterval(() => {
                        if (this.audioPlayer && this.audioPlayer.duration) {
                            this.audioProgress = (this.audioPlayer.currentTime / this.audioPlayer.duration) * 100;
                            this.audioCurrentTime = Math.floor(this.audioPlayer.currentTime);
                            this.audioDuration = Math.floor(this.audioPlayer.duration);
                        }
                    }, 250);
                }
            },

            async toggleRecording() {
                if (this.isRecording) {
                    this.isRecording = false;
                    clearInterval(this.recordingInterval);

                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                        this.mediaRecorder.stream.getTracks().forEach(t => t.stop());
                    } else {
                        this.selectedAnswer = 'audio_recording_' + this.recordingSeconds + 's';
                    }
                } else {
                    this.isRecording = true;
                    this.recordingSeconds = 0;
                    this.recordingInterval = setInterval(() => {
                        this.recordingSeconds++;
                    }, 1000);

                    try {
                        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                            this.mediaRecorder = new MediaRecorder(stream);
                            this.audioChunks = [];
                            this.mediaRecorder.ondataavailable = (e) => {
                                if (e.data.size > 0) this.audioChunks.push(e.data);
                            };
                            this.mediaRecorder.onstop = () => {
                                const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
                                this.recordedAudioUrl = URL.createObjectURL(blob);
                                this.selectedAnswer = 'audio_recording_' + Math.max(1, this.recordingSeconds) + 's';
                            };
                            this.mediaRecorder.start();
                        } else {
                            this.selectedAnswer = 'audio_recording_simulated';
                            this.recordedAudioUrl = 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                        }
                    } catch (err) {
                        this.selectedAnswer = 'audio_recording_mic_granted';
                        this.recordedAudioUrl = 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                    }
                }
            },

            playPreviewAudio() {
                if (!this.recordedAudioUrl) return;
                if (this.previewAudio) {
                    this.previewAudio.pause();
                }
                this.previewAudio = new Audio(this.recordedAudioUrl);
                this.isPlayingPreview = true;
                this.previewAudio.play().catch(() => {});
                this.previewAudio.onended = () => {
                    this.isPlayingPreview = false;
                };
            },

            getOptionText(opt) {
                if (typeof opt === 'string') return opt;
                if (typeof opt === 'object' && opt !== null) {
                    return opt.text || opt.label || opt.title || opt.content || JSON.stringify(opt);
                }
                return String(opt);
            },

            getOptionValue(opt) {
                if (typeof opt === 'string') return opt;
                if (typeof opt === 'object' && opt !== null) {
                    return opt.value || opt.text || opt.label || JSON.stringify(opt);
                }
                return String(opt);
            },

            selectAnswer(opt) {
                if (this.submitting) return;
                this.selectedAnswer = this.getOptionValue(opt);
            },

            toggleFlag(qId) {
                const key = 'q_' + this.currentStep;
                this.flaggedQuestions[key] = !this.flaggedQuestions[key];
            },

            goToStep(stepNum) {
                if (stepNum === this.currentStep) return;
            },

            nextQuestionOrSubmit() {
                if (!this.selectedAnswer || this.submitting) return;
                this.submitting = true;

                // Stop audio if playing
                if (this.audioPlayer) {
                    this.audioPlayer.pause();
                    this.isAudioPlaying = false;
                }

                fetch('{{ route("api.adaptive.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        question_id: this.currentQuestion.id,
                        answer: this.selectedAnswer
                    })
                })
                .then(r => r.json())
                .then(data => {
                    this.submitting = false;
                    this.answeredSteps[this.currentStep] = true;
                    this.lastResult = data;
                    this.score = data.score;
                    this.correctCount = data.correct_count;
                    this.currentDifficulty = data.current_difficulty;

                    // Show difficulty alert if shifted
                    if (data.difficulty_changed) {
                        if (data.difficulty_direction === 'up') {
                            this.difficultyAlert = {
                                show: true,
                                type: 'up',
                                message: `🌟 Xuất sắc! Hệ thống nâng cấp độ khó lên bậc ${data.current_difficulty}!`
                            };
                        } else {
                            this.difficultyAlert = {
                                show: true,
                                type: 'down',
                                message: `💡 Hệ thống điều chỉnh câu hỏi về bậc ${data.current_difficulty} để phù hợp với bạn.`
                            };
                        }
                    }

                    if (data.is_finished) {
                        this.isFinished = true;
                        this.finalReport = data.final_report;
                        this.skillMatrix = data.skill_matrix || (data.final_report ? data.final_report.skill_matrix : null);
                        if (data.answers_history) {
                            this.answersHistoryReview = data.answers_history;
                        } else if (data.final_report && data.final_report.answers_history) {
                            this.answersHistoryReview = data.final_report.answers_history;
                        }
                        clearInterval(this.timerInterval);
                    } else {
                        // Load next adapted question from Question Bank
                        this.loadNextQuestion();
                    }
                })
                .catch(() => {
                    this.submitting = false;
                });
            },

            finalizeExam() {
                this.openSummaryModal = false;
                this.isFinished = true;
                clearInterval(this.timerInterval);

                if (!this.finalReport) {
                    fetch('{{ route("api.adaptive.next") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ session_id: this.sessionId })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.session) {
                            this.score = data.session.score || 0;
                            this.correctCount = data.session.correct_count || 0;
                        }
                    });
                }
            },

            handleGlobalKey(e) {
                if (['input', 'textarea'].includes(e.target.tagName.toLowerCase())) return;

                if (this.currentScreen === 'playing' && this.currentQuestion) {
                    const keyStr = (e.key || '').toUpperCase();
                    const codeStr = (e.code || '').toUpperCase();

                    let optionIdx = -1;
                    if (codeStr === 'KEYA' || keyStr === 'A' || keyStr === '1') optionIdx = 0;
                    else if (codeStr === 'KEYB' || keyStr === 'B' || keyStr === '2') optionIdx = 1;
                    else if (codeStr === 'KEYC' || keyStr === 'C' || keyStr === '3') optionIdx = 2;
                    else if (codeStr === 'KEYD' || keyStr === 'D' || keyStr === '4') optionIdx = 3;

                    if (optionIdx !== -1 && this.currentQuestion.options && this.currentQuestion.options[optionIdx]) {
                        this.selectAnswer(this.currentQuestion.options[optionIdx]);
                    } else if (e.key === 'Enter' && this.selectedAnswer && !this.submitting) {
                        this.nextQuestionOrSubmit();
                    }
                }
            }
        };
    }
    </script>
</body>
</html>
