<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $exam['title'] ?? 'Phòng Thi Trực Tuyến' }} - ESL CBT Exam</title>
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
        .cbt-option:hover {
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
    </style>
</head>
<body class="cbt-page min-h-screen select-none font-sans overflow-hidden antialiased"
      x-data="vstepExamApp()" 
      x-init="initApp()">

    {{-- ========================================================================= --}}
    {{-- SCREEN 1: SKILL INTRO / BRIEFING SCREEN (Màn hình Chờ cho từng Kỹ Năng)   --}}
    {{-- ========================================================================= --}}
    <div x-show="currentScreen === 'intro'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-98"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 cbt-page flex flex-col overflow-y-auto">
        
        {{-- Intro Top Bar --}}
        <header class="h-16 px-6 sm:px-10 border-b cbt-topbar flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <span class="text-base sm:text-lg font-bold text-white tracking-wide">
                    {{ $exam['title'] ?? 'VSTEP (Full) - 01' }}
                </span>
                <span class="text-[11px] font-mono px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 font-semibold uppercase"
                      x-text="'Kỹ năng ' + (currentSkillIndex + 1) + '/' + skillsList.length">
                    Kỹ năng 1/4
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
                
                {{-- Skill Icon Circle (Changes color per skill) --}}
                <div class="flex justify-center">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full flex items-center justify-center shadow-xl transition-all"
                         :class="currentSkill.iconBgClass">
                        <span class="text-3xl sm:text-4xl" x-text="currentSkill.emoji">🎧</span>
                    </div>
                </div>

                {{-- Skill Title & Duration --}}
                <div class="space-y-1.5">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight" x-text="currentSkill.displayName">
                        Kỹ năng nghe
                    </h1>
                    <div class="flex items-center justify-center gap-2 text-sm sm:text-base font-semibold text-gray-300">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Thời gian: <strong class="text-white font-mono" x-text="currentSkill.durationMinutes + ' phút'"></strong></span>
                    </div>
                </div>

                {{-- Headphone Sound Check Box (Only shown for Listening Skill) --}}
                <template x-if="currentSkill.key === 'listening'">
                    <div class="cbt-subcard rounded-2xl p-5 text-left space-y-3 shadow-inner">
                        <div class="flex items-center gap-2 text-sm font-bold text-white">
                            <span class="text-base">🎧</span>
                            <span>Kiểm tra tai nghe</span>
                        </div>
                        <p class="text-xs text-gray-300 leading-relaxed">
                            Hãy đảm bảo rằng âm thanh tai nghe của bạn đủ tốt trước khi làm bài kiểm tra. Vui lòng nhấp vào biểu tượng để kiểm tra chất lượng âm thanh.
                        </p>

                        {{-- Headphone Test Audio Player --}}
                        <div class="bg-[#151c2f] rounded-xl p-3 flex items-center gap-3 border border-slate-800">
                            <button type="button" 
                                    @click="toggleTestAudio()" 
                                    class="w-9 h-9 rounded-full cbt-btn-blue text-white flex items-center justify-center transition-transform hover:scale-105 flex-shrink-0 shadow-md">
                                <span class="text-xs font-bold" x-text="isTestAudioPlaying ? '⏸' : '▶'">▶</span>
                            </button>

                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>

                            <div class="flex-1 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full transition-all duration-200" 
                                     :style="'width: ' + testAudioProgress + '%;'"></div>
                            </div>

                            <span class="text-[11px] font-mono text-gray-400 flex-shrink-0" x-text="testAudioCurrentTime + ' / 00:14'">00:14 / 00:14</span>
                        </div>
                    </div>
                </template>

                {{-- Skill Instructions Block --}}
                <div class="flex items-start gap-3 text-left text-xs sm:text-sm text-gray-300 leading-relaxed cbt-inner-box rounded-2xl p-5">
                    <span class="text-blue-400 text-lg flex-shrink-0">✦</span>
                    <p x-text="currentSkill.instructions">
                        Bài thi kiểm tra toàn diện năng lực ngoại ngữ theo cấu trúc chuẩn. Bạn hãy giữ tập trung cao độ, phân bổ thời gian hợp lý cho từng phần thi và trả lời đầy đủ các câu hỏi.
                    </p>
                </div>

                {{-- Previous Attempts Table (Moodle Practice Standard) --}}
                @if(isset($previousAttempts) && $previousAttempts->isNotEmpty())
                    <div class="rounded-2xl p-5 sm:p-6 text-left space-y-4 border border-slate-700/60 bg-gradient-to-b from-[#18223c] to-[#131b31] shadow-2xl relative overflow-hidden">
                        {{-- Header with Summary Badge --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-700/60 pb-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-blue-400 text-sm shadow-inner">
                                    📊
                                </div>
                                <div>
                                    <h3 class="text-sm sm:text-base font-bold text-white tracking-tight flex items-center gap-1.5">
                                        <span>Lịch sử các lần thi trước</span>
                                        <span class="text-xs font-normal text-slate-400">({{ $previousAttempts->count() }} lần)</span>
                                    </h3>
                                    <p class="text-[11px] text-slate-400">Xem lại kết quả chi tiết và theo dõi tiến độ cải thiện điểm số</p>
                                </div>
                            </div>

                            @if($previousAttempts->max('accuracy_rate') !== null)
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-mono font-bold shadow-sm">
                                    <span>🏆 Điểm cao nhất:</span>
                                    <span class="text-emerald-400 text-sm font-extrabold">{{ $previousAttempts->max('accuracy_rate') }}%</span>
                                </div>
                            @endif
                        </div>

                        {{-- Table Container --}}
                        <div class="overflow-x-auto -mx-1 sm:mx-0">
                            <table class="w-full text-left text-xs text-gray-300">
                                <thead>
                                    <tr class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-700/60">
                                        <th class="py-3 px-3">Lần thi</th>
                                        <th class="py-3 px-3">Kết quả</th>
                                        <th class="py-3 px-3">Điểm / Tỷ lệ</th>
                                        <th class="py-3 px-3">Thời gian</th>
                                        <th class="py-3 px-3">Ngày thi</th>
                                        <th class="py-3 px-3 text-right w-28 whitespace-nowrap">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/80">
                                    @foreach($previousAttempts as $pAtt)
                                        <tr class="hover:bg-slate-800/50 transition-colors group">
                                            {{-- Lần thi --}}
                                            <td class="py-3 px-3">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700/60 font-mono font-bold text-xs text-white shadow-sm">
                                                    Lần #{{ $pAtt->attempt_number ?? $loop->iteration }}
                                                </span>
                                            </td>

                                            {{-- Kết quả Đạt / Chưa đạt --}}
                                            <td class="py-3 px-3">
                                                @if($pAtt->is_passed)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 shadow-sm whitespace-nowrap">
                                                        <span>✓</span>
                                                        <span>Đạt tiêu chuẩn</span>
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 shadow-sm whitespace-nowrap">
                                                        <span>✕</span>
                                                        <span>Chưa đạt</span>
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Điểm / Tỷ lệ --}}
                                            <td class="py-3 px-3">
                                                <div class="flex items-baseline gap-1.5 font-mono">
                                                    <span class="text-sm font-extrabold {{ $pAtt->is_passed ? 'text-emerald-400' : 'text-amber-400' }}">
                                                        {{ $pAtt->accuracy_rate }}%
                                                    </span>
                                                    <span class="text-[11px] text-slate-400 font-normal">
                                                        ({{ $pAtt->total_score }}/{{ $pAtt->max_score }})
                                                    </span>
                                                </div>
                                            </td>

                                            {{-- Thời gian --}}
                                            <td class="py-3 px-3">
                                                <span class="inline-flex items-center gap-1 font-mono text-xs text-slate-300">
                                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <span>{{ $pAtt->time_spent_seconds ? floor($pAtt->time_spent_seconds / 60) . ':' . str_pad($pAtt->time_spent_seconds % 60, 2, '0', STR_PAD_LEFT) : '--:--' }}</span>
                                                </span>
                                            </td>

                                            {{-- Ngày thi --}}
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <div class="text-xs text-slate-200 font-medium">
                                                    {{ $pAtt->created_at->format('d/m/Y') }}
                                                </div>
                                                <div class="text-[10px] font-mono text-slate-400">
                                                    {{ $pAtt->created_at->format('H:i') }}
                                                </div>
                                            </td>

                                            {{-- Nút Xem lại (không wrap, hover effect đẹp) --}}
                                            <td class="py-3 px-3 text-right whitespace-nowrap">
                                                <a href="{{ route('practice.exam.attempt.review', [$exam['key'], $pAtt->id]) }}" 
                                                   class="whitespace-nowrap px-3.5 py-1.5 rounded-xl bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white border border-blue-500/30 hover:border-blue-500 font-bold transition-all duration-150 inline-flex items-center justify-center text-xs shadow-sm hover:shadow-blue-500/25">
                                                    <span>Xem lại</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Start Exam Action Button --}}
                <div class="pt-4 flex justify-center">
                    <button type="button" 
                            @click="startSkillPlaying()" 
                            class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-500 hover:from-blue-500 hover:via-indigo-500 hover:to-blue-400 text-white font-extrabold text-sm sm:text-base shadow-xl shadow-blue-600/25 hover:shadow-blue-600/40 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 flex items-center justify-center gap-2.5 min-w-[240px] cursor-pointer group">
                        @if(isset($previousAttempts) && $previousAttempts->isNotEmpty())
                            <span class="text-base group-hover:rotate-180 transition-transform duration-500">🔄</span>
                            <span>Thi lại đề này (Lần #{{ $previousAttempts->count() + 1 }})</span>
                        @else
                            <span class="text-base">🚀</span>
                            <span>Bắt đầu làm bài</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        {{-- Intro Footer Note --}}
        <footer class="py-3 text-center text-xs text-gray-400 font-mono border-t cbt-topbar">
            Hệ thống Khảo thí Trực tuyến ESL CBT Exam Engine &copy; 2026
        </footer>
    </div>


    {{-- ========================================================================= --}}
    {{-- SCREEN 2: MAIN EXAM ROOM PLAYER (Làm bài các Phần của Kỹ Năng Hiện Tại)   --}}
    {{-- ========================================================================= --}}
    <div x-show="currentScreen === 'playing'" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-98"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 cbt-page flex flex-col overflow-hidden">

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
                         :class="currentSkill.iconBgClass">
                        <span x-text="currentSkill.emoji">📖</span>
                    </div>
                    <span class="text-sm sm:text-base font-extrabold text-white tracking-tight" x-text="currentSkill.displayName">
                        Kỹ năng đọc
                    </span>
                </div>
            </div>

            {{-- Center: Big Live Countdown Timer (For Current Skill) --}}
            <div class="flex items-center gap-2 font-mono font-extrabold text-lg sm:text-xl text-white tracking-wider"
                 :class="skillRemainingSeconds < 300 ? 'text-red-400 animate-pulse' : 'text-white'">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="formattedTime">59:19</span>
            </div>

            {{-- Right: Next Skill / Final Submit Button --}}
            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="openSummaryModal = true" 
                        class="px-5 sm:px-6 py-2 rounded-xl cbt-btn-blue text-white font-bold text-xs sm:text-sm shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2">
                    <span x-text="isLastSkill ? 'Nộp bài thi' : 'Hoàn thành kỹ năng ➔'">Nộp bài</span>
                    <svg class="w-4 h-4 transform rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
        </header>

        {{-- 2. LISTENING AUDIO BAR (Only displayed for Listening Skill) --}}
        <template x-if="currentSkill.key === 'listening' || currentQ.question_type === 'audio_listening'">
            <div class="px-4 sm:px-8 py-2.5 bg-[#172037] border-b border-[#232f4e] flex items-center gap-4 flex-shrink-0 z-10">
                {{-- Play / Pause Button --}}
                <button type="button" 
                        @click="toggleListeningAudio()"
                        class="w-9 h-9 rounded-xl cbt-btn-blue text-white flex items-center justify-center flex-shrink-0 shadow-md transition-transform hover:scale-105">
                    <span class="text-xs font-bold" x-text="isExamAudioPlaying ? '⏸' : '▶'">⏸</span>
                </button>

                {{-- Volume Icon --}}
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>

                {{-- Audio Seekbar Track --}}
                <div class="flex-1 bg-[#101524] rounded-full h-2 overflow-hidden border border-slate-800">
                    <div class="h-full bg-blue-500 rounded-full transition-all duration-300" 
                         :style="'width: ' + examAudioProgress + '%;'"></div>
                </div>

                {{-- Duration Counter --}}
                <span class="text-xs font-mono text-gray-300 flex-shrink-0" x-text="examAudioTimeFormatted">00:19 / 21:05</span>
            </div>
        </template>

        {{-- 3. MAIN WORKSPACE CONTENT AREA (SPECIALIZED BY SKILL TYPE) --}}
        <main class="flex-1 px-4 sm:px-8 py-4 min-h-0 overflow-hidden flex flex-col">
            
            {{-- ───────────────────────────────────────────────────────────── --}}
            {{-- CASE A: READING SKILL (Split 2 Columns: Left Passage, Right Questions) --}}
            {{-- ───────────────────────────────────────────────────────────── --}}
            <template x-if="currentSkill.key === 'reading'">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-full min-h-0 overflow-hidden">
                    
                    {{-- LEFT COLUMN: READING PASSAGE --}}
                    <div class="lg:col-span-5 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl">
                        {{-- Passage Header --}}
                        <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0">
                            <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight" x-text="currentPassageTitle">
                                Reading Passage 1
                            </h2>
                            <span class="text-[11px] uppercase tracking-wider font-mono font-bold text-gray-400 mt-1 block" 
                                  x-text="'PASSAGE ' + (currentPartIndex + 1) + ' — Questions ' + currentPartQuestionRange">
                                PASSAGE 1 — Questions 1–10
                            </span>
                        </div>

                        {{-- Passage Scrollable Body --}}
                        <div class="flex-1 overflow-y-auto pr-2 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed font-sans cbt-scrollbar">
                            <div class="whitespace-pre-line leading-relaxed" x-text="currentPassageText">
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT COLUMN: QUESTIONS LIST & RADIO CHOICES --}}
                    <div class="lg:col-span-7 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                        
                        {{-- Right Header --}}
                        <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0 flex items-center justify-between">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-white" x-text="'Câu hỏi ' + currentPartQuestionRange">
                                    Câu hỏi 1 - 10
                                </h3>
                                <p class="text-[11px] text-gray-400 mt-0.5" x-text="currentPart.instructions || 'In this section you will read the passage. For each question, choose the best answer A, B, C, or D.'">
                                    For questions 1–10, choose the best answer A, B, C, or D for each question.
                                </p>
                            </div>

                            <button type="button" 
                                    @click="toggleFlag(currentQ.id)" 
                                    class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-2 select-none shadow-sm cursor-pointer"
                                    :class="flaggedQuestions[currentQ.id] 
                                        ? 'bg-amber-500/20 text-amber-300 border-amber-500/50 ring-1 ring-amber-500/30' 
                                        : 'cbt-btn-icon text-gray-300 hover:text-white'">
                                <svg class="w-3.5 h-3.5 transition-transform" 
                                     :class="flaggedQuestions[currentQ.id] ? 'text-amber-400 fill-current' : 'text-gray-400'" 
                                     viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                                </svg>
                                <span x-text="flaggedQuestions[currentQ.id] ? 'Đã gắn cờ' : 'Gắn cờ'"></span>
                            </button>
                        </div>

                        {{-- Questions Content Area (Scrollable) --}}
                        <div class="flex-1 overflow-y-auto pr-2 space-y-5 cbt-scrollbar pb-2">
                            
                            {{-- Question Title --}}
                            <div class="text-sm sm:text-base font-bold text-white leading-relaxed">
                                <span class="text-blue-400 font-mono mr-1" x-text="(currentGlobalIndex + 1) + '.'">1.</span>
                                <span x-text="currentQ.question_text"></span>
                            </div>

                            {{-- Options List (Radio choices styling) --}}
                            <div class="space-y-2.5 pt-1">
                                <template x-for="(opt, optIdx) in (currentQ.options || [])" :key="currentQ.id + '_opt_' + optIdx">
                                    <div @click="selectAnswer(currentQ.id, opt)"
                                         class="cbt-option flex items-center gap-3.5 p-3.5 rounded-xl cursor-pointer select-none group"
                                         :class="selectedAnswers[currentQ.id] === opt ? 'selected' : ''">
                                        
                                        {{-- Round Radio Icon --}}
                                        <div class="cbt-radio-circle w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 transition-colors">
                                            <div x-show="selectedAnswers[currentQ.id] === opt" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>

                                        {{-- Option Text --}}
                                        <span class="text-xs sm:text-sm flex-1 leading-relaxed" x-text="opt"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Fixed Bottom Controls Footer inside Question Card --}}
                        <div class="pt-3 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                            <span class="text-xs text-gray-400 font-mono">
                                Câu <strong class="text-white font-bold" x-text="currentGlobalIndex + 1"></strong> / <span x-text="currentSkillTotalQuestions"></span> (Kỹ năng: <span x-text="currentSkill.displayName"></span>)
                            </span>

                            <div class="flex items-center gap-2.5">
                                <button type="button" 
                                        @click="prevQuestion()" 
                                        :disabled="currentSkillQuestionIndex === 0"
                                        class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                        title="Câu trước">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>

                                <button type="button" 
                                        @click="nextQuestion()" 
                                        :disabled="currentSkillQuestionIndex === currentSkillTotalQuestions - 1"
                                        class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                        title="Câu tiếp theo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>


            {{-- ───────────────────────────────────────────────────────────── --}}
            {{-- CASE B: WRITING SKILL (Prompt on Left/Top, Live Textarea on Right/Bottom) --}}
            {{-- ───────────────────────────────────────────────────────────── --}}
            <template x-if="currentSkill.key === 'writing'">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-full min-h-0 overflow-hidden">
                    
                    {{-- LEFT: WRITING TASK PROMPT --}}
                    <div class="lg:col-span-5 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl">
                        <div class="border-b border-[#253252] pb-3 mb-4 flex-shrink-0">
                            <span class="text-[11px] uppercase tracking-wider font-mono font-bold text-cyan-400 bg-cyan-500/10 px-2.5 py-1 rounded-md border border-cyan-500/20">
                                <span x-text="'KỸ NĂNG VIẾT — ' + (currentSkillQuestionIndex === 0 ? 'TASK 1 (Thư/Email)' : 'TASK 2 (Bài luận)')"></span>
                            </span>
                            <h2 class="text-lg font-bold text-white mt-2">
                                <span x-text="'Đề bài ' + (currentSkillQuestionIndex + 1)"></span>
                            </h2>
                        </div>

                        <div class="flex-1 overflow-y-auto pr-2 space-y-4 text-xs sm:text-sm text-gray-300 leading-relaxed font-sans cbt-scrollbar">
                            <div class="whitespace-pre-line leading-relaxed bg-[#141b2d] p-4 rounded-xl border border-[#1f2942]" x-text="currentQ.question_text">
                            </div>

                            <div class="p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs text-blue-300 space-y-1">
                                <span class="font-bold block text-blue-200">💡 Hướng dẫn làm bài:</span>
                                <p>• Hãy chia bài viết thành các đoạn văn rõ ràng (Mở bài, Thân bài, Kết bài).</p>
                                <p>• Hệ thống tự động đếm số từ và lưu bài viết theo thời gian thực.</p>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: TEXTAREA EDITOR --}}
                    <div class="lg:col-span-7 cbt-card rounded-2xl p-6 sm:p-7 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                        <div class="border-b border-[#253252] pb-3 mb-3 flex items-center justify-between flex-shrink-0">
                            <span class="text-xs font-bold text-gray-300">Khung Soạn Thảo Bài Viết</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono px-3 py-1 rounded-lg bg-[#141b2d] border border-[#253252] font-bold text-emerald-400">
                                    Số từ: <strong class="text-white" x-text="getWordCount(selectedAnswers[currentQ.id] || '')">0</strong> từ
                                </span>
                            </div>
                        </div>

                        <div class="flex-1 min-h-0 flex flex-col">
                            <textarea 
                                class="w-full flex-1 p-4 rounded-xl bg-[#141b2d] border border-[#253252] text-gray-200 text-sm leading-relaxed font-sans focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none resize-none cbt-scrollbar"
                                placeholder="Bắt đầu gõ bài viết của bạn tại đây..."
                                :value="selectedAnswers[currentQ.id] || ''"
                                @input="selectAnswer(currentQ.id, $event.target.value)"></textarea>
                        </div>

                        {{-- Footer Controls --}}
                        <div class="pt-3 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                            <span class="text-xs text-gray-400 font-mono">
                                Task <strong class="text-white font-bold" x-text="currentSkillQuestionIndex + 1"></strong> / <span x-text="currentSkillTotalQuestions"></span>
                            </span>

                            <div class="flex items-center gap-2.5">
                                <button type="button" 
                                        @click="prevQuestion()" 
                                        :disabled="currentSkillQuestionIndex === 0"
                                        class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>

                                <button type="button" 
                                        @click="nextQuestion()" 
                                        :disabled="currentSkillQuestionIndex === currentSkillTotalQuestions - 1"
                                        class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>


            {{-- ───────────────────────────────────────────────────────────── --}}
            {{-- CASE C: SPEAKING SKILL (Topic & AI Voice Recording Studio) --}}
            {{-- ───────────────────────────────────────────────────────────── --}}
            <template x-if="currentSkill.key === 'speaking'">
                <div class="max-w-4xl mx-auto w-full cbt-card rounded-2xl p-6 sm:p-8 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                    
                    {{-- Header --}}
                    <div class="border-b border-[#253252] pb-4 mb-4 flex-shrink-0 flex items-start justify-between gap-4">
                        <div>
                            <span class="text-[11px] uppercase tracking-wider font-mono font-bold text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-md border border-amber-500/20">
                                <span x-text="'KỸ NĂNG NÓI — PHẦN ' + (currentSkillQuestionIndex + 1)"></span>
                            </span>
                            <h3 class="text-lg sm:text-xl font-bold text-white mt-1.5" x-text="currentPart.title || ('Phần thi nói số ' + (currentSkillQuestionIndex + 1))">
                                Part 1: Social Interaction
                            </h3>
                        </div>

                        <span class="px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-mono font-bold">
                            🎙️ AI Voice Studio
                        </span>
                    </div>

                    {{-- Prompt Content --}}
                    <div class="flex-1 overflow-y-auto pr-2 space-y-6 cbt-scrollbar pb-2">
                        <div class="p-5 rounded-2xl bg-[#141b2d] border border-[#1f2942] text-sm sm:text-base font-sans text-gray-200 leading-relaxed whitespace-pre-line"
                             x-text="currentQ.question_text">
                        </div>

                        {{-- Recording Studio Controls --}}
                        <div class="p-6 rounded-2xl bg-gradient-to-b from-[#161f36] to-[#111728] border border-[#253252] text-center space-y-5 shadow-2xl">
                            {{-- Mic status & Wave animation --}}
                            <div class="flex items-center justify-center gap-3">
                                <div class="relative flex items-center justify-center">
                                    <div class="w-3.5 h-3.5 rounded-full" :class="isRecording ? 'bg-red-500 animate-ping absolute' : ''"></div>
                                    <div class="w-3.5 h-3.5 rounded-full z-10" :class="isRecording ? 'bg-red-500 shadow-lg shadow-red-500/50' : 'bg-slate-600'"></div>
                                </div>
                                <span class="font-mono text-sm font-bold tracking-wide" 
                                      :class="isRecording ? 'text-red-400' : 'text-gray-300'" 
                                      x-text="isRecording ? 'Đang ghi âm bài nói... ' + recordingTimer + 's' : (selectedAnswers[currentQ.id] ? 'Bản ghi âm đã sẵn sàng' : 'Micro sẵn sàng để thu âm')">
                                </span>
                            </div>

                            {{-- Live Audio Wave Effect when recording --}}
                            <div x-show="isRecording" class="flex items-center justify-center gap-1.5 py-2">
                                <span class="w-1.5 h-6 bg-red-500 rounded-full animate-pulse"></span>
                                <span class="w-1.5 h-10 bg-red-400 rounded-full animate-bounce"></span>
                                <span class="w-1.5 h-8 bg-red-500 rounded-full animate-pulse" style="animation-delay: 150ms"></span>
                                <span class="w-1.5 h-12 bg-red-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                <span class="w-1.5 h-7 bg-red-500 rounded-full animate-pulse" style="animation-delay: 200ms"></span>
                                <span class="w-1.5 h-4 bg-red-400 rounded-full animate-pulse" style="animation-delay: 400ms"></span>
                            </div>

                            {{-- Audio Playback Player (shown when recorded) --}}
                            <div x-show="!isRecording && (selectedAnswers[currentQ.id] || recordedAudioUrls[currentQ.id])" 
                                 x-transition
                                 class="p-4 rounded-xl bg-[#0e1424] border border-indigo-500/40 space-y-3 text-left max-w-lg mx-auto shadow-inner">
                                <div class="flex items-center justify-between text-xs text-indigo-200">
                                    <span class="font-bold flex items-center gap-2">
                                        <span class="text-base">🎧</span>
                                        <span>Nghe lại bài nói vừa ghi âm:</span>
                                    </span>
                                    <span class="font-mono text-[11px] text-gray-400 font-bold" 
                                          x-text="playingAudioId === currentQ.id ? (playbackCurrentTime + ' / ' + playbackTotalDuration) : (selectedAnswers[currentQ.id] ? 'Bấm ▶ để phát' : '')">
                                    </span>
                                </div>

                                {{-- Player Control Bar --}}
                                <div class="flex items-center gap-3">
                                    <button type="button" 
                                            @click="togglePlayAudio(currentQ.id)"
                                            class="w-10 h-10 rounded-full bg-indigo-600 hover:bg-indigo-500 text-white flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-600/30 transition-transform hover:scale-105 cursor-pointer">
                                        <span class="text-sm font-bold" x-text="playingAudioId === currentQ.id ? '⏸' : '▶'">▶</span>
                                    </button>

                                    {{-- Custom Progress Bar --}}
                                    <div class="flex-1 space-y-1">
                                        <div class="h-2.5 bg-slate-800 rounded-full overflow-hidden cursor-pointer relative"
                                             @click="seekAudioPlayback($event)">
                                            <div class="h-full bg-gradient-to-r from-blue-500 to-teal-400 rounded-full transition-all duration-150"
                                                 :style="'width: ' + (playingAudioId === currentQ.id ? playbackProgress : 0) + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-[11px] text-emerald-400 flex items-center gap-1.5 font-medium pt-1">
                                    <span>✓ Đã lưu bản ghi âm cho phần thi này. Bạn có thể nghe lại để kiểm tra hoặc bấm nút bên dưới để ghi âm lại.</span>
                                </div>
                            </div>

                            {{-- Record / Stop Button --}}
                            <div class="flex justify-center pt-1">
                                <button type="button" 
                                        @click="toggleRecording(currentQ.id)"
                                        class="px-8 py-3.5 rounded-2xl font-bold text-sm shadow-xl transition-all transform hover:scale-[1.02] flex items-center gap-3 cursor-pointer"
                                        :class="isRecording ? 'bg-red-600 hover:bg-red-500 text-white shadow-red-600/40' : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white shadow-indigo-600/30'">
                                    <span class="text-lg" x-text="isRecording ? '⏹' : '🎙️'">🎙️</span>
                                    <span x-text="isRecording ? 'Dừng ghi âm & Lưu' : (selectedAnswers[currentQ.id] ? 'Ghi âm lại bài nói' : 'Bắt đầu ghi âm câu trả lời')">Bắt đầu ghi âm</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Controls Footer --}}
                    <div class="pt-4 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                        <span class="text-xs text-gray-400 font-mono">
                            Phần <strong class="text-white font-bold" x-text="currentSkillQuestionIndex + 1"></strong> / <span x-text="currentSkillTotalQuestions"></span>
                        </span>

                        <div class="flex items-center gap-2.5">
                            <button type="button" 
                                    @click="prevQuestion()" 
                                    :disabled="currentSkillQuestionIndex === 0"
                                    class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>

                            <button type="button" 
                                    @click="nextQuestion()" 
                                    :disabled="currentSkillQuestionIndex === currentSkillTotalQuestions - 1"
                                    class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>


            {{-- ───────────────────────────────────────────────────────────── --}}
            {{-- CASE D: LISTENING SKILL (Standard Single Card Layout) --}}
            {{-- ───────────────────────────────────────────────────────────── --}}
            <template x-if="currentSkill.key === 'listening'">
                <div class="max-w-4xl mx-auto w-full cbt-card rounded-2xl p-6 sm:p-8 flex flex-col h-full min-h-0 overflow-hidden shadow-xl relative">
                    
                    {{-- Section Header & Instructions --}}
                    <div class="border-b border-[#253252] pb-4 mb-4 flex-shrink-0 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-white" x-text="'Câu hỏi ' + currentPartQuestionRange">
                                Câu hỏi 1 - 8
                            </h3>
                            <p class="text-xs text-gray-400 mt-1 uppercase tracking-wider font-mono" x-text="currentPart.instructions || ('PART ' + (currentPartIndex + 1) + ' – QUESTIONS ' + currentPartQuestionRange + ': In this part, you will hear announcements or conversations...')">
                                PART 1 – QUESTIONS 1-8: In this part, you will hear short announcements or instructions...
                            </p>
                        </div>

                        <button type="button" 
                                @click="toggleFlag(currentQ.id)" 
                                class="px-3.5 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-2 select-none shadow-sm cursor-pointer"
                                :class="flaggedQuestions[currentQ.id] 
                                    ? 'bg-amber-500/20 text-amber-300 border-amber-500/50 ring-1 ring-amber-500/30' 
                                    : 'cbt-btn-icon text-gray-300 hover:text-white'">
                            <svg class="w-3.5 h-3.5 transition-transform" 
                                 :class="flaggedQuestions[currentQ.id] ? 'text-amber-400 fill-current' : 'text-gray-400'" 
                                 viewBox="0 0 24 24" fill="currentColor">
                                <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                            </svg>
                            <span x-text="flaggedQuestions[currentQ.id] ? 'Đã gắn cờ' : 'Gắn cờ'"></span>
                        </button>
                    </div>

                    {{-- Question Content --}}
                    <div class="flex-1 overflow-y-auto pr-2 space-y-6 cbt-scrollbar pb-2">
                        <div class="text-base sm:text-lg font-bold text-white leading-relaxed">
                            <span class="text-blue-400 font-mono mr-1" x-text="(currentGlobalIndex + 1) + '.'">1.</span>
                            <span x-text="currentQ.question_text"></span>
                        </div>

                        {{-- Options List --}}
                        <div class="space-y-3">
                            <template x-for="(opt, optIdx) in (currentQ.options || [])" :key="currentQ.id + '_single_opt_' + optIdx">
                                <div @click="selectAnswer(currentQ.id, opt)"
                                     class="cbt-option flex items-center gap-3.5 p-4 rounded-xl cursor-pointer select-none group"
                                     :class="selectedAnswers[currentQ.id] === opt ? 'selected' : ''">
                                    
                                    {{-- Round Radio Button --}}
                                    <div class="cbt-radio-circle w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 transition-colors">
                                        <div x-show="selectedAnswers[currentQ.id] === opt" class="w-2 h-2 rounded-full bg-white"></div>
                                    </div>

                                    <span class="text-xs sm:text-sm flex-1 leading-relaxed" x-text="opt"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Fixed Bottom Controls Footer inside Question Card --}}
                    <div class="pt-4 border-t border-[#253252] flex items-center justify-between mt-auto flex-shrink-0">
                        <span class="text-xs text-gray-400 font-mono">
                            Câu <strong class="text-white font-bold" x-text="currentGlobalIndex + 1"></strong> / <span x-text="currentSkillTotalQuestions"></span> (Kỹ năng: <span x-text="currentSkill.displayName"></span>)
                        </span>

                        <div class="flex items-center gap-2.5">
                            <button type="button" 
                                    @click="prevQuestion()" 
                                    :disabled="currentSkillQuestionIndex === 0"
                                    class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                    title="Câu trước">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>

                            <button type="button" 
                                    @click="nextQuestion()" 
                                    :disabled="currentSkillQuestionIndex === currentSkillTotalQuestions - 1"
                                    class="w-10 h-10 rounded-full cbt-btn-blue disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 cursor-pointer"
                                    title="Câu tiếp theo">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </main>


        {{-- 4. BOTTOM BAR (ACTIVE PART FITS QUESTIONS, INACTIVE PARTS EXPAND TO FILL FULL WIDTH) --}}
        <footer class="h-16 flex-shrink-0 cbt-bottombar px-4 sm:px-8 flex items-center justify-between gap-3 z-40">
            
            {{-- Parts & Question Palette Boxes for CURRENT SKILL --}}
            <div class="flex items-center gap-3 w-full py-1 flex-1 h-12">
                <template x-for="(part, pIdx) in currentSkill.parts" :key="'part_tab_' + pIdx">
                    <div class="flex items-center gap-2 px-4 py-1.5 rounded-xl border text-xs font-semibold transition-all h-full"
                         :class="currentPartIndex === pIdx 
                             ? 'flex-shrink-0 w-auto bg-[#1b253f] border-blue-500 text-white shadow-inner justify-start' 
                             : 'flex-1 min-w-0 bg-[#141a2c] border-[#222c48] text-gray-400 hover:border-slate-700 cursor-pointer justify-center text-center'"
                         @click="goToPart(pIdx)">
                        
                        {{-- Active Part: Tightly Hugs Question Pills Without Blank Space --}}
                        <template x-if="currentPartIndex === pIdx">
                            <div class="flex items-center gap-2.5 flex-shrink-0">
                                <span class="font-bold text-white whitespace-nowrap flex-shrink-0" x-text="part.name + ':'">Phần 1:</span>
                                
                                <div class="flex items-center gap-1.5 flex-nowrap flex-shrink-0">
                                    <template x-for="item in part.items" :key="item.id">
                                        <button type="button" 
                                                @click.stop="goToQuestionInSkill(item.skillIndex)"
                                                class="cbt-q-pill w-7 h-7 sm:w-8 sm:h-8 rounded-lg text-xs font-mono font-bold flex items-center justify-center transition-all relative select-none flex-shrink-0"
                                                :class="getQuestionPillClass(item)">
                                            <span x-text="item.globalIndex + 1" class="relative z-10"></span>
                                            
                                            {{-- Elegant Corner Flag Tag --}}
                                            <template x-if="flaggedQuestions[item.id]">
                                                <span class="absolute -top-1 -right-1 z-20 flex items-center justify-center pointer-events-none">
                                                    <svg class="w-3 h-3 text-amber-400 filter drop-shadow" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/>
                                                    </svg>
                                                </span>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Inactive Part: Expands (flex-1) to Fill Remaining Full Screen Width --}}
                        <template x-if="currentPartIndex !== pIdx">
                            <div class="flex items-center justify-center gap-2 whitespace-nowrap text-center w-full">
                                <span class="font-bold text-gray-200" x-text="part.name + ':'">Phần 2:</span>
                                <span class="font-mono text-gray-300 font-semibold" x-text="getPartAnsweredCount(part) + ' / ' + part.items.length + ' question'">0 / 10 question</span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Floating Assistant Chat Bubble at Bottom Right --}}
            <div class="flex-shrink-0 pl-1">
                <button type="button" 
                        class="w-10 h-10 rounded-full cbt-btn-blue text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105"
                        title="Trợ lý ESL">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </button>
            </div>
        </footer>
    </div>


    {{-- ========================================================================= --}}
    {{-- MODAL: CONFIRM END SKILL OR SUBMIT (Chuyển sang Kỹ Năng tiếp theo / Nộp) --}}
    {{-- ========================================================================= --}}
    <div x-show="openSummaryModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="cbt-card rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-5 shadow-2xl text-center relative"
             @click.away="openSummaryModal = false">
            
            <div class="w-14 h-14 rounded-full bg-blue-600/20 border border-blue-500/40 text-blue-400 text-2xl flex items-center justify-center mx-auto">
                📝
            </div>

            <div class="space-y-1">
                <h3 class="text-xl font-bold text-white" x-text="isLastSkill ? 'Xác nhận Nộp toàn bộ bài thi' : ('Xác nhận Hoàn thành: ' + currentSkill.displayName)">
                    Xác nhận hoàn thành phần thi
                </h3>
                <p class="text-xs text-gray-400" x-text="isLastSkill ? 'Bạn đang ở phần thi cuối cùng. Nộp bài để xem bảng điểm kết quả.' : 'Sau khi nộp, bạn sẽ chuyển sang kỹ năng tiếp theo và không thể quay lại phần thi này.'">
                    Sau khi nộp, bạn sẽ chuyển sang phần thi tiếp theo và không thể quay lại.
                </p>
            </div>

            {{-- Metrics Grid of Current Skill --}}
            <div class="grid grid-cols-3 gap-2.5">
                <div class="p-3 bg-[#13192a] rounded-xl border border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Đã làm</span>
                    <span class="text-xl font-black text-emerald-400 font-mono" x-text="currentSkillAnsweredCount"></span>
                </div>
                <div class="p-3 bg-[#13192a] rounded-xl border border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Chưa làm</span>
                    <span class="text-xl font-black text-red-400 font-mono" x-text="currentSkillUnansweredCount"></span>
                </div>
                <div class="p-3 bg-[#13192a] rounded-xl border border-slate-800 text-center">
                    <span class="text-[10px] uppercase font-bold text-gray-400 block">Cần xem 🚩</span>
                    <span class="text-xl font-black text-yellow-400 font-mono" x-text="currentSkillFlaggedCount"></span>
                </div>
            </div>

            <template x-if="currentSkillUnansweredCount > 0">
                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs text-left">
                    ⚠️ Bạn vẫn còn <strong x-text="currentSkillUnansweredCount"></strong> câu chưa làm trong <strong x-text="currentSkill.displayName"></strong>. Bạn có chắc muốn nộp phần thi này?
                </div>
            </template>

            {{-- Modal Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" 
                        @click="openSummaryModal = false" 
                        class="px-4 py-2.5 rounded-xl bg-[#1f2944] hover:bg-[#283556] text-xs font-bold text-gray-300 transition-colors">
                    Tiếp tục làm bài
                </button>

                {{-- If Not Last Skill -> Proceed to Next Skill --}}
                <template x-if="!isLastSkill">
                    <button type="button" 
                            @click="completeCurrentSkillAndGoNext()"
                            class="px-6 py-2.5 rounded-xl cbt-btn-blue text-white text-xs font-bold shadow-lg shadow-blue-600/30">
                        Sang kỹ năng tiếp theo
                    </button>
                </template>

                {{-- If Last Skill -> Submit Exam Form to Scorecard --}}
                <template x-if="isLastSkill">
                    <form method="POST" action="{{ route('practice.exam.submit', $exam['key']) }}" id="finalSubmitForm" @submit="clearDraft()">
                        @csrf
                        <input type="hidden" name="time_spent_seconds" :value="totalElapsedSeconds">
                        <input type="hidden" name="started_at" :value="examStartedAt">
                        <template x-for="(val, qId) in selectedAnswers" :key="qId">
                            <input type="hidden" :name="'answers[' + qId + ']'" :value="val">
                            <input type="hidden" :name="'audio_recordings[' + qId + ']'" :value="getRecordedAudioPayload(qId)">
                        </template>

                        <button type="submit" 
                                @click="clearDraft()"
                                class="px-6 py-2.5 rounded-xl cbt-btn-blue text-white text-xs font-bold shadow-lg shadow-blue-600/30 cursor-pointer">
                            Nộp bài & Xem kết quả
                        </button>
                    </form>
                </template>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODAL: CONFIRM SAVE DRAFT & EXIT (Thoát & Lưu Tiến Trình Làm Tiếp)       --}}
    {{-- ========================================================================= --}}
    <div x-show="openExitModal" x-cloak style="display: none;" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="cbt-card rounded-3xl max-w-md w-full p-6 sm:p-8 space-y-6 shadow-2xl text-center relative animate-in fade-in zoom-in-95 duration-200"
             @click.away="openExitModal = false">
            
            {{-- Clipboard Graphic Icon --}}
            <div class="w-20 h-20 rounded-3xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mx-auto text-4xl shadow-inner relative">
                <span>📋</span>
                <span class="absolute -top-1.5 -right-1.5 text-lg">🔔</span>
            </div>

            <div class="space-y-2">
                <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
                    Có vẻ như bạn chưa hoàn thành tất cả các câu hỏi trong bài kiểm tra.
                </h3>
                <p class="text-xs sm:text-sm text-gray-400 leading-relaxed px-2">
                    Bạn có thể thoát và hệ thống sẽ lưu lại bài làm hiện tại dưới dạng bản nháp. Khi quay lại, bạn có thể tiếp tục làm từ phần đã dừng để đạt kết quả tốt nhất nhé!
                </p>
            </div>

            {{-- 2 Modal Buttons --}}
            <div class="flex items-center justify-center gap-3 pt-2">
                <button type="button" 
                        @click="saveDraftAndExit()"
                        class="flex-1 px-5 py-3 rounded-xl bg-[#1f2944] hover:bg-[#28365a] border border-[#2d3b61] text-xs sm:text-sm font-bold text-gray-200 hover:text-white transition-all shadow-md cursor-pointer">
                    Thoát và Lưu nháp
                </button>

                <button type="button" 
                        @click="openExitModal = false" 
                        class="flex-1 px-5 py-3 rounded-xl cbt-btn-blue text-xs sm:text-sm font-bold text-white shadow-lg shadow-blue-600/30 hover:scale-[1.02] transition-transform cursor-pointer">
                    Tiếp tục làm bài
                </button>
            </div>
        </div>
    </div>


    {{-- PASS DATA TO JAVASCRIPT --}}
    <script>
    const CBT_EXAM_DATA = @json($exam);
    const CBT_QUESTIONS = @json($questions);

    function vstepExamApp() {
        return {
            currentScreen: 'intro', // 'intro' or 'playing'
            questionsRawList: CBT_QUESTIONS,
            skillsList: [],
            currentSkillIndex: 0,
            currentSkillQuestionIndex: 0, // 0 to N within current skill
            skillRemainingSeconds: 0,
            totalElapsedSeconds: 0,
            examStartedAt: new Date().toISOString(),
            timerInterval: null,
            selectedAnswers: {},
            flaggedQuestions: {},
            completedSkills: {}, // Track skills already submitted (cannot go back)
            openSummaryModal: false,
            openExitModal: false,
            isSubmitted: false,

            // Test Headphone Audio State
            isTestAudioPlaying: false,
            testAudioProgress: 0,
            testAudioCurrentTime: '00:00',
            testAudioInterval: null,

            // Voice Recording Studio State
            isRecording: false,
            recordingTimer: 0,
            recordingInterval: null,
            mediaRecorder: null,
            mediaRecorderMimeType: 'audio/webm',
            audioChunks: [],
            recordedAudioUrls: {},
            playingAudioId: null,
            previewAudio: null,
            playbackProgress: 0,
            playbackCurrentTime: '00:00',
            playbackTotalDuration: '00:00',

            // Audio Controls State
            isExamAudioPlaying: false,
            examAudioProgress: 25,
            examAudioSeconds: 19,
            examAudioTotalSeconds: 1265, // 21:05
            examAudioInterval: null,

            initApp() {
                this.buildExamSkillsAndParts();

                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('reset') === '1') {
                    this.clearDraft();
                } else {
                    // Load saved draft if user resumes previous progress
                    this.loadSavedDraft();
                }

                // Set initial skill timer if not loaded from draft
                if (this.currentSkill && (!this.skillRemainingSeconds || this.skillRemainingSeconds <= 0)) {
                    this.skillRemainingSeconds = (this.currentSkill.durationMinutes || 60) * 60;
                }
            },

            // Group questions into 4 Standard Skills (Listening, Reading, Writing, Speaking) -> Each Skill contains multiple Parts (Phần 1, Phần 2, Phần 3, Phần 4...)
            buildExamSkillsAndParts() {
                const skillConfig = {
                    'listening': {
                        order: 1,
                        displayName: 'Kỹ năng Nghe (Listening)',
                        emoji: '🎧',
                        iconBgClass: 'bg-[#4ade80] text-slate-950',
                        durationMinutes: 45,
                        instructions: 'Phần Nghe của bài thi gồm 3 phần với tổng cộng 35 câu hỏi. Trong quá trình làm bài, bạn sẽ được nghe các đoạn ghi âm và trả lời câu hỏi tương ứng, đòi hỏi sự tập trung cao độ và khả năng nắm bắt thông tin nhanh chóng. Tổng thời gian cho phần Nghe là 45 phút.'
                    },
                    'reading': {
                        order: 2,
                        displayName: 'Kỹ năng Đọc (Reading)',
                        emoji: '📖',
                        iconBgClass: 'bg-[#a855f7] text-white',
                        durationMinutes: 60,
                        instructions: 'Phần Đọc của bài thi được thiết kế nhằm đánh giá khả năng hiểu văn bản tiếng Anh của bạn thông qua nhiều dạng câu hỏi khác nhau. Bài thi bao gồm 4 phần riêng biệt, mỗi phần có 10 câu hỏi, tổng cộng là 40 câu hỏi. Bạn sẽ có tổng cộng 60 phút để hoàn thành toàn bộ phần thi Đọc.'
                    },
                    'writing': {
                        order: 3,
                        displayName: 'Kỹ năng Viết (Writing)',
                        emoji: '✍️',
                        iconBgClass: 'bg-[#06b6d4] text-white',
                        durationMinutes: 60,
                        instructions: 'Phần Viết của bài thi được chia thành 2 phần: Phần 1 viết email/thư tín ngắn (20 phút) và Phần 2 viết bài luận học thuật (40 phút). Tổng thời gian dành cho cả hai phần là 60 phút.'
                    },
                    'speaking': {
                        order: 4,
                        displayName: 'Kỹ năng Nói (Speaking)',
                        emoji: '🎙️',
                        iconBgClass: 'bg-[#f97316] text-white',
                        durationMinutes: 12,
                        instructions: 'Phần Nói của bài thi gồm 3 phần: Phần 1 Tương tác xã hội, Phần 2 Thảo luận giải pháp và Phần 3 Phát triển chủ đề thuyết trình (Tổng thời gian 12 phút).'
                    }
                };

                // If questionsRawList is empty, seed a default skill object based on CBT_EXAM_DATA
                if (!this.questionsRawList || this.questionsRawList.length === 0) {
                    const fallbackSkill = (CBT_EXAM_DATA.skill || 'reading').toLowerCase();
                    const cfg = skillConfig[fallbackSkill] || skillConfig['reading'];
                    
                    const defaultQuestions = [
                        {
                            id: 9901,
                            skill: fallbackSkill,
                            question_text: fallbackSkill === 'writing' 
                                ? 'TASK 1: Write an email/letter (at least 120 words) according to the given scenario.'
                                : (fallbackSkill === 'speaking' 
                                    ? 'PART 1: Social Interaction. Answer questions about your daily routine and hobbies.' 
                                    : 'Question 1: Choose the best answer A, B, C, or D.'),
                            options: ['Option A', 'Option B', 'Option C', 'Option D'],
                            globalIndex: 0
                        },
                        {
                            id: 9902,
                            skill: fallbackSkill,
                            question_text: fallbackSkill === 'writing' 
                                ? 'TASK 2: Write an academic essay (at least 250 words) discussing both views.'
                                : (fallbackSkill === 'speaking' 
                                    ? 'PART 2: Solution Discussion. Present and justify your decision.' 
                                    : 'Question 2: Choose the best answer A, B, C, or D.'),
                            options: ['Option A', 'Option B', 'Option C', 'Option D'],
                            globalIndex: 1
                        }
                    ];

                    this.skillsList = [this.buildPartsForSkill({
                        key: fallbackSkill,
                        order: cfg.order,
                        displayName: cfg.displayName,
                        emoji: cfg.emoji,
                        iconBgClass: cfg.iconBgClass,
                        durationMinutes: cfg.durationMinutes || (CBT_EXAM_DATA.duration_minutes || 60),
                        instructions: cfg.instructions,
                        questions: defaultQuestions
                    })];
                    return;
                }

                // Group raw questions by skill
                const skillMap = {};
                let globalIndexCounter = 0;

                this.questionsRawList.forEach(q => {
                    const sk = (q.skill || CBT_EXAM_DATA.skill || 'reading').toLowerCase();
                    if (!skillMap[sk]) {
                        skillMap[sk] = {
                            key: sk,
                            order: skillConfig[sk]?.order || 99,
                            displayName: skillConfig[sk]?.displayName || ('Kỹ năng ' + sk),
                            emoji: skillConfig[sk]?.emoji || '📝',
                            iconBgClass: skillConfig[sk]?.iconBgClass || 'bg-[#3b82f6] text-white',
                            durationMinutes: skillConfig[sk]?.durationMinutes || (CBT_EXAM_DATA.duration_minutes || 60),
                            instructions: skillConfig[sk]?.instructions || 'Hãy hoàn thành đầy đủ các câu hỏi trong phần thi này.',
                            questions: []
                        };
                    }
                    skillMap[sk].questions.push({
                        ...q,
                        globalIndex: globalIndexCounter++
                    });
                });

                // If this is a Full Mock exam and some of the 4 core skills are missing from DB questions, map them gracefully
                if (CBT_EXAM_DATA.skill === 'full_mock' && Object.keys(skillMap).length === 1) {
                    const primaryKey = Object.keys(skillMap)[0];
                    const allQ = skillMap[primaryKey].questions;
                    const count = allQ.length;
                    
                    // Split questions among standard 4 skills: Listening, Reading, Writing, Speaking
                    const coreKeys = ['listening', 'reading', 'writing', 'speaking'];
                    const chunkSize = Math.max(1, Math.ceil(count / 4));
                    
                    const newSkillsList = [];
                    coreKeys.forEach((ck, idx) => {
                        const start = idx * chunkSize;
                        const end = Math.min(start + chunkSize, count);
                        const sliceQ = (start < count) ? allQ.slice(start, end) : allQ.slice(0, Math.min(3, count));
                        
                        newSkillsList.push({
                            key: ck,
                            order: skillConfig[ck].order,
                            displayName: skillConfig[ck].displayName,
                            emoji: skillConfig[ck].emoji,
                            iconBgClass: skillConfig[ck].iconBgClass,
                            durationMinutes: skillConfig[ck].durationMinutes,
                            instructions: skillConfig[ck].instructions,
                            questions: sliceQ.map((item, qIdx) => ({ ...item, globalIndex: start + qIdx }))
                        });
                    });

                    this.skillsList = newSkillsList.map(skillObj => this.buildPartsForSkill(skillObj));
                    return;
                }

                // Sort skills by standard order (Listening -> Reading -> Writing -> Speaking)
                const sortedSkills = Object.values(skillMap).sort((a, b) => (a.order || 99) - (b.order || 99));

                // For each skill, break down into structured Parts (Phần 1, Phần 2, Phần 3, Phần 4...)
                this.skillsList = sortedSkills.map(skillObj => this.buildPartsForSkill(skillObj));
            },

            buildPartsForSkill(skillObj) {
                const totalQ = skillObj.questions.length;
                let partCount = 4;
                if (skillObj.key === 'listening') partCount = 3;
                if (skillObj.key === 'writing') partCount = 2;
                if (skillObj.key === 'speaking') partCount = 3;
                if (totalQ <= 8) partCount = Math.max(1, Math.ceil(totalQ / 4));

                const partSize = Math.max(1, Math.ceil(totalQ / partCount));
                const parts = [];

                for (let p = 0; p < partCount; p++) {
                    const start = p * partSize;
                    const end = Math.min(start + partSize, totalQ);
                    if (start >= totalQ) break;

                    const partItems = [];
                    for (let i = start; i < end; i++) {
                        const qItem = skillObj.questions[i];
                        partItems.push({
                            id: qItem.id,
                            skillIndex: i, // Index within this skill
                            globalIndex: qItem.globalIndex,
                            data: qItem
                        });
                    }

                    const rangeStart = start + 1;
                    const rangeEnd = end;
                    const rangeStr = rangeStart === rangeEnd ? `${rangeStart}` : `${rangeStart} – ${rangeEnd}`;

                    parts.push({
                        index: p,
                        name: `Phần ${p + 1}`,
                        title: (skillObj.key === 'reading') ? `Reading Passage ${p + 1}` : `Phần ${p + 1}`,
                        rangeString: rangeStr,
                        instructions: (skillObj.key === 'reading')
                            ? `In this section you will read Passage ${p + 1}. For questions ${rangeStr}, choose the best answer A, B, C, or D.` 
                            : (skillObj.key === 'listening')
                                ? `PART ${p + 1} – QUESTIONS ${rangeStr}: Listen carefully and choose the best answer (A, B, C, or D).`
                                : `PHẦN ${p + 1} – CÂU HỎI ${rangeStr}: Hoàn thành nội dung theo yêu cầu.`,
                        passage: this.getSamplePassageForPart(p + 1),
                        items: partItems
                    });
                }

                return {
                    ...skillObj,
                    parts: parts,
                    totalQuestions: totalQ
                };
            },

            getSamplePassageForPart(partNum) {
                const passages = {
                    1: `A new study, conducted by scientists from Oxford University, the Chinese Academy of Medical Sciences and the Chinese Centre for Disease Control, has warned that a third of all men currently under the age of 20 in China will eventually die prematurely if they do not give up smoking.\n\nThe research, published in The Lancet medical journal, says two-thirds of men in China now start to smoke before 20. Around half of those men will die from the habit, it concludes. In 2010, around one million people in China died from tobacco usage. But researchers say that if current trends continue, that will double to two million people - mostly men - dying every year by 2030.\n\nGlobally, tobacco kills up to half of its users, according to the World Health Organization (WHO). However, while smoking rates have fallen in developed countries, they are rising in many developing nations, including China. Researchers noted that the number of young Chinese men smoking has reached unprecedented highs, while the rate among young women remains very low.`,
                    2: `The rise of artificial intelligence in modern education has sparked both immense enthusiasm and cautious skepticism among educators worldwide. Intelligent tutoring systems can now adapt to the individual learning pace of each student, diagnosing misconceptions in real-time and providing customized exercises.\n\nRecent data from pedagogical research institutions indicates that students utilizing adaptive AI tools demonstrated a 28% improvement in retention rates compared to traditional textbook methods. However, sociologists caution against over-reliance on automated instruction, emphasizing that critical thinking, social collaboration, and empathetic mentorship remain intrinsically human domains that algorithms cannot replicate.`,
                    3: `Urbanization in the 21st century presents unprecedented environmental challenges, particularly regarding urban heat islands and sustainable water resource management. As concrete and asphalt replace natural vegetation, metropolitan areas absorb and retain solar radiation significantly longer than rural surroundings.\n\nTo counteract these microclimate anomalies, architects and city planners are pioneering 'biophilic infrastructure'—incorporating vertical gardens, permeable pavements, and rooftop wetland ecosystems. These innovations not only reduce building cooling energy consumption by up to 35% but also enhance urban biodiversity and residents' psychological well-being.`,
                    4: `Marine biologists exploring the abyssal zones of the Pacific Ocean have uncovered thriving hydrothermal vent ecosystems that challenge conventional assumptions about biological survival. Operating in complete darkness under immense hydrostatic pressure, these organisms do not depend on photosynthetic primary production.\n\nInstead, chemotrophic bacteria oxidize hydrogen sulfide emitted from subterranean vents, converting inorganic chemical energy into organic biomass. This discovery has profound implications not only for understanding the origin of life on early Earth but also for astrobiological models seeking life in the subterranean oceans of Jovian and Saturnian moons like Europa and Enceladus.`
                };
                return passages[partNum] || passages[1];
            },

            // Current Skill
            get currentSkill() {
                return this.skillsList[this.currentSkillIndex] || this.skillsList[0] || {};
            },

            get currentSkillTotalQuestions() {
                return this.currentSkill.questions ? this.currentSkill.questions.length : 0;
            },

            get currentQ() {
                if (!this.currentSkill.questions) return {};
                return this.currentSkill.questions[this.currentSkillQuestionIndex] || {};
            },

            get currentGlobalIndex() {
                return this.currentQ.globalIndex !== undefined ? this.currentQ.globalIndex : this.currentSkillQuestionIndex;
            },

            // Current Part within the Current Skill
            get currentPartIndex() {
                if (!this.currentSkill.parts) return 0;
                for (let i = 0; i < this.currentSkill.parts.length; i++) {
                    const p = this.currentSkill.parts[i];
                    if (p.items.some(item => item.skillIndex === this.currentSkillQuestionIndex)) {
                        return i;
                    }
                }
                return 0;
            },

            get currentPart() {
                return (this.currentSkill.parts && this.currentSkill.parts[this.currentPartIndex]) || {};
            },

            get currentPartQuestionRange() {
                return this.currentPart.rangeString || '1 - 10';
            },

            get currentPassageTitle() {
                if (this.currentQ && this.currentQ.meta_data && this.currentQ.meta_data.passage_title) {
                    return this.currentQ.meta_data.passage_title;
                }
                return this.currentPart.title || ('Reading Passage ' + (this.currentPartIndex + 1));
            },

            get currentPassageText() {
                if (this.currentQ && this.currentQ.meta_data && this.currentQ.meta_data.passage_content) {
                    return this.currentQ.meta_data.passage_content;
                }
                if (this.currentQ && this.currentQ.passage) {
                    return this.currentQ.passage;
                }
                return this.currentPart.passage || this.getSamplePassageForPart(this.currentPartIndex + 1);
            },

            getWordCount(text) {
                if (!text || typeof text !== 'string') return 0;
                const words = text.trim().split(/\s+/).filter(w => w.length > 0);
                return words.length;
            },

            async toggleRecording(questionId) {
                if (this.isRecording) {
                    this.isRecording = false;
                    if (this.recordingInterval) clearInterval(this.recordingInterval);

                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                    } else {
                        this.selectAnswer(questionId, 'audio_recorded_' + Math.max(1, this.recordingTimer) + 's');
                    }
                } else {
                    this.stopAudioPlayback();
                    this.isRecording = true;
                    this.recordingTimer = 0;
                    this.audioChunks = [];

                    if (this.recordingInterval) clearInterval(this.recordingInterval);
                    this.recordingInterval = setInterval(() => {
                        this.recordingTimer++;
                    }, 1000);

                    try {
                        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                            let mimeType = 'audio/webm';
                            if (window.MediaRecorder) {
                                if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) mimeType = 'audio/webm;codecs=opus';
                                else if (MediaRecorder.isTypeSupported('audio/mp4')) mimeType = 'audio/mp4';
                                else if (MediaRecorder.isTypeSupported('audio/ogg')) mimeType = 'audio/ogg';
                            }
                            this.mediaRecorderMimeType = mimeType;
                            this.mediaRecorder = new MediaRecorder(stream, { mimeType });

                            this.mediaRecorder.ondataavailable = (e) => {
                                if (e.data && e.data.size > 0) {
                                    this.audioChunks.push(e.data);
                                }
                            };

                            this.mediaRecorder.onstop = () => {
                                const blob = new Blob(this.audioChunks, { type: this.mediaRecorderMimeType });
                                const url = URL.createObjectURL(blob);
                                this.recordedAudioUrls[questionId] = url;

                                try {
                                    const reader = new FileReader();
                                    reader.onloadend = () => {
                                        try {
                                            localStorage.setItem('cbt_recorded_audio_' + questionId, reader.result);
                                        } catch(e) {}
                                    };
                                    reader.readAsDataURL(blob);
                                } catch(e) {}

                                this.selectAnswer(questionId, 'audio_recorded_' + Math.max(1, this.recordingTimer) + 's');
                                if (stream) {
                                    stream.getTracks().forEach(t => t.stop());
                                }
                            };

                            this.mediaRecorder.start(250);
                        } else {
                            this.selectAnswer(questionId, 'audio_recorded_simulated');
                            this.recordedAudioUrls[questionId] = 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                            try { localStorage.setItem('cbt_recorded_audio_' + questionId, 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'); } catch(e) {}
                        }
                    } catch (err) {
                        console.warn('Microphone error or permission denied:', err);
                        this.selectAnswer(questionId, 'audio_recorded_granted');
                        this.recordedAudioUrls[questionId] = 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                        try { localStorage.setItem('cbt_recorded_audio_' + questionId, 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'); } catch(e) {}
                    }
                }
            },

            togglePlayAudio(questionId) {
                const url = this.recordedAudioUrls[questionId] || 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';
                this.recordedAudioUrls[questionId] = url;

                if (this.playingAudioId === questionId && this.previewAudio && !this.previewAudio.paused) {
                    this.previewAudio.pause();
                    this.playingAudioId = null;
                    return;
                }

                if (this.previewAudio) {
                    this.previewAudio.pause();
                }

                this.previewAudio = new Audio(url);
                this.playingAudioId = questionId;

                this.previewAudio.ontimeupdate = () => {
                    if (this.previewAudio && this.previewAudio.duration) {
                        this.playbackProgress = (this.previewAudio.currentTime / this.previewAudio.duration) * 100;
                        const curM = String(Math.floor(this.previewAudio.currentTime / 60)).padStart(2, '0');
                        const curS = String(Math.floor(this.previewAudio.currentTime % 60)).padStart(2, '0');
                        const durM = String(Math.floor(this.previewAudio.duration / 60)).padStart(2, '0');
                        const durS = String(Math.floor(this.previewAudio.duration % 60)).padStart(2, '0');
                        this.playbackCurrentTime = `${curM}:${curS}`;
                        this.playbackTotalDuration = `${durM}:${durS}`;
                    }
                };

                this.previewAudio.onended = () => {
                    this.playingAudioId = null;
                    this.playbackProgress = 0;
                    this.playbackCurrentTime = '00:00';
                };

                this.previewAudio.play().catch(e => {
                    console.error('Audio play error:', e);
                    this.playingAudioId = null;
                });
            },

            seekAudioPlayback(e) {
                if (!this.previewAudio || !this.previewAudio.duration) return;
                const rect = e.currentTarget.getBoundingClientRect();
                const pos = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                this.previewAudio.currentTime = pos * this.previewAudio.duration;
            },

            stopAudioPlayback() {
                if (this.previewAudio) {
                    this.previewAudio.pause();
                    this.previewAudio = null;
                }
                this.playingAudioId = null;
                this.playbackProgress = 0;
                this.playbackCurrentTime = '00:00';
            },

            get isLastSkill() {
                return this.currentSkillIndex === this.skillsList.length - 1;
            },

            get formattedTime() {
                const m = String(Math.floor(this.skillRemainingSeconds / 60)).padStart(2, '0');
                const s = String(this.skillRemainingSeconds % 60).padStart(2, '0');
                return `${m}:${s}`;
            },

            get examAudioTimeFormatted() {
                const cm = String(Math.floor(this.examAudioSeconds / 60)).padStart(2, '0');
                const cs = String(this.examAudioSeconds % 60).padStart(2, '0');
                const tm = String(Math.floor(this.examAudioTotalSeconds / 60)).padStart(2, '0');
                const ts = String(this.examAudioTotalSeconds % 60).padStart(2, '0');
                return `${cm}:${cs} / ${tm}:${ts}`;
            },

            // Stats for Current Skill
            get currentSkillAnsweredCount() {
                if (!this.currentSkill.questions) return 0;
                return this.currentSkill.questions.filter(q => this.selectedAnswers[q.id]).length;
            },

            get currentSkillUnansweredCount() {
                return this.currentSkillTotalQuestions - this.currentSkillAnsweredCount;
            },

            get currentSkillFlaggedCount() {
                if (!this.currentSkill.questions) return 0;
                return this.currentSkill.questions.filter(q => this.flaggedQuestions[q.id]).length;
            },

            // Actions & Navigation
            startSkillPlaying() {
                this.currentScreen = 'playing';
                this.startSkillTimer();
                if (this.currentSkill.key === 'listening') {
                    this.startExamAudio();
                }
                this.saveDraft();
            },

            startSkillTimer() {
                if (this.timerInterval) clearInterval(this.timerInterval);
                this.timerInterval = setInterval(() => {
                    this.totalElapsedSeconds++;
                    if (this.skillRemainingSeconds > 0) {
                        this.skillRemainingSeconds--;
                    } else {
                        clearInterval(this.timerInterval);
                        alert(`Đã hết thời gian làm bài phần: ${this.currentSkill.displayName}! Hệ thống sẽ tự động chuyển tiếp.`);
                        if (this.isLastSkill) {
                            this.clearDraft();
                            document.getElementById('finalSubmitForm').submit();
                        } else {
                            this.completeCurrentSkillAndGoNext();
                        }
                    }
                }, 1000);
            },

            completeCurrentSkillAndGoNext() {
                // Mark current skill as completed (locked forever, cannot return)
                this.completedSkills[this.currentSkillIndex] = true;
                this.openSummaryModal = false;

                if (this.isLastSkill) {
                    this.clearDraft();
                    document.getElementById('finalSubmitForm').submit();
                    return;
                }

                // Advance to next skill
                this.currentSkillIndex++;
                this.currentSkillQuestionIndex = 0;
                this.skillRemainingSeconds = (this.currentSkill.durationMinutes || 60) * 60;
                
                // Stop previous audio if any
                if (this.examAudioInterval) clearInterval(this.examAudioInterval);
                this.isExamAudioPlaying = false;
                this.examAudioSeconds = 0;

                // Show Intro screen for the next skill (as in user screenshot)
                this.currentScreen = 'intro';
                this.saveDraft();
            },

            selectAnswer(questionId, option) {
                this.selectedAnswers[questionId] = option;
                this.saveDraft();
            },

            toggleFlag(questionId) {
                this.flaggedQuestions[questionId] = !this.flaggedQuestions[questionId];
                this.saveDraft();
            },

            // Save Draft & Resume State
            saveDraftAndExit() {
                this.saveDraft();
                this.openExitModal = false;
                window.location.href = "{{ route('practice.index', ['skill' => $exam['skill'] === 'full_mock' ? 'full_mock' : $exam['skill']]) }}";
            },

            saveDraft() {
                if (this.isSubmitted) return;
                try {
                    const draft = {
                        key: CBT_EXAM_DATA.key,
                        selectedAnswers: this.selectedAnswers,
                        flaggedQuestions: this.flaggedQuestions,
                        completedSkills: this.completedSkills,
                        currentSkillIndex: this.currentSkillIndex,
                        currentSkillQuestionIndex: this.currentSkillQuestionIndex,
                        skillRemainingSeconds: this.skillRemainingSeconds,
                        currentScreen: this.currentScreen,
                        updatedAt: new Date().toISOString()
                    };
                    localStorage.setItem('cbt_draft_' + CBT_EXAM_DATA.key, JSON.stringify(draft));
                } catch(e) {}
            },

            loadSavedDraft() {
                try {
                    const raw = localStorage.getItem('cbt_draft_' + CBT_EXAM_DATA.key);
                    if (!raw) return;
                    const draft = JSON.parse(raw);
                    if (draft && draft.key === CBT_EXAM_DATA.key) {
                        if (draft.selectedAnswers) this.selectedAnswers = draft.selectedAnswers;
                        if (draft.flaggedQuestions) this.flaggedQuestions = draft.flaggedQuestions;
                        if (draft.completedSkills) this.completedSkills = draft.completedSkills;
                        if (typeof draft.currentSkillIndex === 'number' && draft.currentSkillIndex < this.skillsList.length) {
                            this.currentSkillIndex = draft.currentSkillIndex;
                        }
                        if (typeof draft.currentSkillQuestionIndex === 'number') {
                            this.currentSkillQuestionIndex = draft.currentSkillQuestionIndex;
                        }
                        if (typeof draft.skillRemainingSeconds === 'number' && draft.skillRemainingSeconds > 0) {
                            this.skillRemainingSeconds = draft.skillRemainingSeconds;
                        }
                        if (draft.currentScreen === 'playing') {
                            this.currentScreen = 'playing';
                            this.startSkillTimer();
                            if (this.currentSkill.key === 'listening') {
                                this.startExamAudio();
                            }
                        }
                    }
                } catch(e) {}
            },

            clearDraft() {
                this.isSubmitted = true;
                try {
                    localStorage.removeItem('cbt_draft_' + CBT_EXAM_DATA.key);
                    localStorage.removeItem('cbt_answers_' + CBT_EXAM_DATA.key);
                } catch(e) {}
            },

            getRecordedAudioPayload(qId) {
                try {
                    return localStorage.getItem('cbt_recorded_audio_' + qId) || '';
                } catch(e) {
                    return '';
                }
            },

            goToQuestionInSkill(skillQIndex) {
                this.stopAudioPlayback();
                if (skillQIndex >= 0 && skillQIndex < this.currentSkillTotalQuestions) {
                    this.currentSkillQuestionIndex = skillQIndex;
                    this.saveDraft();
                }
            },

            nextQuestion() {
                this.stopAudioPlayback();
                if (this.currentSkillQuestionIndex < this.currentSkillTotalQuestions - 1) {
                    this.currentSkillQuestionIndex++;
                }
            },

            prevQuestion() {
                this.stopAudioPlayback();
                if (this.currentSkillQuestionIndex > 0) {
                    this.currentSkillQuestionIndex--;
                }
            },

            goToPart(partIdx) {
                const part = this.currentSkill.parts && this.currentSkill.parts[partIdx];
                if (part && part.items.length > 0) {
                    this.currentSkillQuestionIndex = part.items[0].skillIndex;
                }
            },

            getPartAnsweredCount(part) {
                return part.items.filter(item => this.selectedAnswers[item.id]).length;
            },

            getQuestionPillClass(item) {
                const classes = [];
                if (this.currentSkillQuestionIndex === item.skillIndex) {
                    classes.push('active');
                }
                if (this.selectedAnswers[item.id]) {
                    classes.push('answered');
                }
                if (this.flaggedQuestions[item.id]) {
                    classes.push('flagged');
                }
                return classes.join(' ');
            },

            returnToIntroNotice() {
                alert(`Bạn đang làm bài: ${this.currentSkill.displayName}. Thời gian còn lại: ${this.formattedTime}. Hãy tiếp tục hoàn thành các câu hỏi.`);
            },

            // Audio Controls
            startExamAudio() {
                this.isExamAudioPlaying = true;
                if (this.examAudioInterval) clearInterval(this.examAudioInterval);
                this.examAudioInterval = setInterval(() => {
                    if (this.isExamAudioPlaying && this.examAudioSeconds < this.examAudioTotalSeconds) {
                        this.examAudioSeconds++;
                        this.examAudioProgress = (this.examAudioSeconds / this.examAudioTotalSeconds) * 100;
                    }
                }, 1000);
            },

            toggleListeningAudio() {
                this.isExamAudioPlaying = !this.isExamAudioPlaying;
            },

            toggleTestAudio() {
                this.isTestAudioPlaying = !this.isTestAudioPlaying;
                if (this.isTestAudioPlaying) {
                    let sec = 0;
                    this.testAudioInterval = setInterval(() => {
                        sec++;
                        this.testAudioProgress = (sec / 14) * 100;
                        this.testAudioCurrentTime = '00:' + String(sec).padStart(2, '0');
                        if (sec >= 14) {
                            clearInterval(this.testAudioInterval);
                            this.isTestAudioPlaying = false;
                            this.testAudioProgress = 100;
                        }
                    }, 1000);
                } else {
                    clearInterval(this.testAudioInterval);
                }
            }
        };
    }
    </script>
</body>
</html>
