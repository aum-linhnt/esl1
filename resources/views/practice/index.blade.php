@extends('layouts.app')

@section('content')
<div class="w-full space-y-8" x-data="practiceHubApp()">
    
    {{-- 1. HERO BANNER: AI ADAPTIVE DIAGNOSTIC TESTING --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-fsel-navy via-indigo-950/80 to-purple-950/60 border border-fsel-border p-4 sm:p-6 lg:p-8 shadow-2xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5 sm:gap-6">
            <div class="max-w-xl min-w-0">
                <div class="flex items-center gap-2 mb-2 sm:mb-3 flex-wrap">
                    <span class="bg-gradient-to-r from-fsel-blue to-fsel-accent text-white text-[10px] sm:text-[11px] font-black tracking-wider px-2.5 py-0.5 sm:py-1 rounded-full uppercase shadow-glow-blue">
                        AI Adaptive Engine 2.0
                    </span>
                    <span class="text-[11px] sm:text-xs text-amber-300 font-mono font-bold">40 câu · 60 phút · 4 Kỹ năng</span>
                </div>
                <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white leading-tight mb-2">
                    Thi Thử & Đánh Giá Năng Lực Thích Ứng (Full Simulation CAT)
                </h2>
                <p class="text-xs sm:text-sm text-gray-300 leading-relaxed">
                    Thuật toán AI tự động điều chỉnh độ khó (A1 &harr; A2 &harr; B1 &harr; B2 &harr; C1) qua 40 câu hỏi đa tầng để chẩn đoán chính xác ma trận năng lực quốc tế của bạn.
                </p>
            </div>

            <div class="flex flex-col items-stretch sm:items-start md:items-end gap-3 flex-shrink-0 w-full md:w-auto">
                <form method="POST" action="{{ route('practice.startAdaptive') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="btn-primary w-full sm:w-auto !py-3 sm:!py-3.5 px-6 sm:!px-8 text-sm sm:text-base font-bold flex items-center justify-center gap-2.5 shadow-glow-blue hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        <span>Bắt đầu bài thi thích ứng (40 câu)</span>
                    </button>
                </form>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        <span>Bắt đầu bài thi thích ứng (40 câu)</span>
                    </button>
                </form>

                @if($lastSession && $lastSession->status === 'finished')
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <span class="text-xs text-gray-400">
                            Lần thi gần nhất: <strong class="text-fsel-teal">{{ $lastSession->final_level }}</strong> ({{ $lastSession->correct_count }}/{{ $lastSession->total_questions_answered ?: 40 }} câu đúng)
                        </span>
                        <a href="{{ route('practice.adaptive.scorecard', $lastSession->id) }}" class="text-xs text-amber-400 hover:text-amber-300 font-bold underline inline-flex items-center gap-1 transition-colors">
                            <span>Bảng điểm chi tiết</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Background space decoration --}}
        <div class="absolute -top-12 -right-12 w-64 h-64 rounded-full bg-purple-600/10 blur-3xl pointer-events-none"></div>
    </div>

    {{-- 2. 6-MODE SELECTION HUB (ADAPTIVE CAT + FULL 4-SKILL MOCK + 4 SKILLS) --}}
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-xl font-bold text-white tracking-tight">Chọn Chế Độ Luyện Đề & Thi Thử</h3>
                <p class="text-xs text-gray-400">Thi thích ứng AI định vị 4 kỹ năng, luyện tập chuyên sâu hoặc thi thử 4 kỹ năng chuẩn phòng thi</p>
            </div>
        </div>

        {{-- 6 Category Cards Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
            @foreach($skillsOverview as $skKey => $sk)
                @php $isActive = $currentSkill === $skKey; @endphp
                <a href="{{ route('practice.index', ['skill' => $skKey]) }}"
                   class="relative block p-3.5 sm:p-4 rounded-2xl border transition-all duration-200 group bg-gradient-to-b {{ $sk['gradient'] }} {{ $isActive ? $sk['border_active'] . ' shadow-xl bg-opacity-100 scale-[1.02]' : 'border-slate-800/80 hover:border-slate-700 hover:scale-[1.01]' }}">
                    
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xl sm:text-2xl p-1.5 rounded-xl bg-slate-900/80 border border-slate-800 shadow-inner">
                            {{ $sk['icon'] }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full border {{ $sk['badge_color'] }}">
                            Level {{ $sk['user_level'] }}
                        </span>
                    </div>

                    <h4 class="text-xs sm:text-sm font-bold text-white group-hover:text-fsel-teal transition-colors leading-snug truncate">
                        {{ $sk['title'] }}
                    </h4>
                    <p class="text-[9px] sm:text-[10px] text-gray-400 font-mono mb-2 truncate">{{ $sk['subtitle'] }}</p>

                    {{-- Mastery Progress Bar --}}
                    <div class="space-y-1 pt-1.5 border-t border-slate-800/60">
                        <div class="flex items-center justify-between text-[9px] sm:text-[10px]">
                            <span class="text-gray-400">Độ thuần thục:</span>
                            <span class="font-mono font-bold text-white">{{ $sk['mastery_score'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-fsel-blue to-teal-400" style="width: {{ $sk['mastery_score'] }}%;"></div>
                        </div>
                    </div>

                    <div class="mt-2 text-[9px] sm:text-[10px] text-gray-400 flex items-center justify-between font-mono">
                        <span>📚 {{ $sk['exam_count'] }} Đề thi</span>
                        <span class="{{ $isActive ? 'text-fsel-teal font-bold' : 'text-gray-500' }}">
                            {{ $isActive ? 'Đang chọn ●' : 'Chọn' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- 3. CURATED EXAM SETS LIST FOR THE SELECTED MODE --}}
    @php $activeSkillData = $skillsOverview[$currentSkill] ?? $skillsOverview['adaptive']; @endphp
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-3xl">{{ $activeSkillData['icon'] }}</span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-white">Danh Sách Đề Thi: {{ $activeSkillData['title'] }}</h3>
                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border {{ $activeSkillData['badge_color'] }}">
                            {{ count($examSets) }} Đề thi có sẵn
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $activeSkillData['description'] }}</p>
                </div>
            </div>
        </div>

        {{-- Exam Sets Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($examSets as $exam)
                <div class="card-dark p-6 flex flex-col justify-between space-y-4 hover:border-indigo-500/50 transition-all group">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold font-mono px-2.5 py-0.5 rounded-lg">
                                    {{ $exam['difficulty'] }}
                                </span>

                                @if($exam['skill'] === 'adaptive')
                                    <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-bold font-mono px-2 py-0.5 rounded flex items-center gap-1">
                                        ⚡ CAT Thích Ứng 4 Kỹ Năng
                                    </span>
                                @elseif(!empty($exam['sections']) || $exam['skill'] === 'full_mock')
                                    <span class="bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] font-bold font-mono px-2 py-0.5 rounded">
                                        🎯 Full 4 Kỹ Năng
                                    </span>
                                @endif

                                <span class="text-xs text-teal-300 font-bold flex items-center gap-1 font-mono">
                                    ⚡ +{{ $exam['reward_coins'] * 2 }} XP
                                </span>
                            </div>

                            <template x-if="hasDraft('{{ $exam['key'] }}')">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-blue-500/15 text-blue-300 border border-blue-500/30 animate-pulse font-mono">
                                    ⏳ Đang làm dở
                                </span>
                            </template>

                            <template x-if="!hasDraft('{{ $exam['key'] }}')">
                                @if($exam['is_completed'])
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $exam['is_passed'] ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/15 text-red-400 border border-red-500/30' }}">
                                        ✓ Đã làm ({{ $exam['highest_accuracy'] }}%)
                                    </span>
                                @else
                                    <span class="text-[10px] text-gray-500 font-mono">Chưa làm bài</span>
                                @endif
                            </template>
                        </div>

                        <h4 class="text-base font-bold text-white group-hover:text-fsel-teal transition-colors leading-snug">
                            {{ $exam['title'] }}
                        </h4>

                        <p class="text-xs text-gray-400 leading-relaxed">
                            {{ $exam['description'] }}
                        </p>

                        {{-- Section Breakdown Pills --}}
                        @if(!empty($exam['sections']))
                            <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px] font-mono">
                                @if(isset($exam['sections']['listening']))
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-emerald-300">
                                        🎧 {{ $exam['sections']['listening']['count'] ?? '' }} {{ $exam['sections']['listening']['name'] ?? 'Nghe' }}
                                    </span>
                                @endif
                                @if(isset($exam['sections']['reading']))
                                    <span class="px-2 py-0.5 rounded bg-purple-500/10 border border-purple-500/20 text-purple-300">
                                        📖 {{ $exam['sections']['reading']['count'] ?? '' }} {{ $exam['sections']['reading']['name'] ?? 'Đọc' }}
                                    </span>
                                @endif
                                @if(isset($exam['sections']['writing']))
                                    <span class="px-2 py-0.5 rounded bg-cyan-500/10 border border-cyan-500/20 text-cyan-300">
                                        ✍️ {{ $exam['sections']['writing']['count'] ?? '' }} {{ $exam['sections']['writing']['name'] ?? 'Viết' }}
                                    </span>
                                @endif
                                @if(isset($exam['sections']['speaking']))
                                    <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-300">
                                        🎙️ {{ $exam['sections']['speaking']['count'] ?? '' }} {{ $exam['sections']['speaking']['name'] ?? 'Nói' }}
                                    </span>
                                @endif
                            </div>
                        @elseif($exam['skill'] === 'full_mock')
                            <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px] font-mono">
                                <span class="px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-emerald-300">🎧 35 Nghe</span>
                                <span class="px-2 py-0.5 rounded bg-purple-500/10 border border-purple-500/20 text-purple-300">📖 40 Đọc</span>
                                <span class="px-2 py-0.5 rounded bg-cyan-500/10 border border-cyan-500/20 text-cyan-300">✍️ 2 Viết</span>
                                <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20 text-amber-300">🎙️ 3 Nói</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-4 text-xs text-gray-400 font-mono pt-1">
                            <span class="flex items-center gap-1">📝 <strong>{{ $exam['question_count'] }}</strong> câu hỏi</span>
                            <span>·</span>
                            <span class="flex items-center gap-1">⏱ <strong>{{ $exam['duration_minutes'] }}</strong> phút làm bài</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-fsel-border/30 flex items-center justify-between gap-3">
                        <span class="text-xs text-gray-500 font-mono">
                            Khung chuẩn CEFR {{ $exam['difficulty'] }}
                        </span>

                        <div class="flex items-center gap-2">
                            {{-- View scorecard button if completed adaptive --}}
                            @if($exam['is_completed'] && $exam['skill'] === 'adaptive' && $lastSession)
                                <a href="{{ route('practice.adaptive.scorecard', $lastSession->id) }}"
                                   class="px-2.5 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 hover:bg-amber-500/25 text-xs font-bold transition-all flex items-center gap-1"
                                   title="Xem bảng điểm chi tiết">
                                    <span>📊</span>
                                    <span>Bảng điểm</span>
                                </a>
                            @endif

                            {{-- Redo / Reset Button (Shown when exam has draft or is completed) --}}
                            <template x-if="hasDraft('{{ $exam['key'] }}') || {{ $exam['is_completed'] ? 'true' : 'false' }}">
                                <button type="button" 
                                        @click="openResetConfirm('{{ $exam['key'] }}', '{{ $exam['action_url'] ?? route('practice.exam', $exam['key']) }}')"
                                        class="w-9 h-9 rounded-xl bg-slate-800/90 border border-slate-700 hover:border-slate-500 text-gray-400 hover:text-white flex items-center justify-center transition-all shadow-sm hover:scale-105 cursor-pointer"
                                        title="Làm lại bài thi từ đầu">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </template>

                            {{-- Main Action Button: "Tiếp tục làm bài" if draft exists, else "Bắt đầu làm bài" --}}
                            <a :href="hasDraft('{{ $exam['key'] }}') ? '{{ $exam['action_url'] ?? route('practice.exam', $exam['key']) }}' : '{{ $exam['action_url'] ?? route('practice.exam', $exam['key']) }}'"
                               class="btn-primary !w-auto !py-2 px-5 text-xs font-bold flex items-center gap-1.5 shadow-glow-blue">
                                <span x-text="hasDraft('{{ $exam['key'] }}') ? 'Tiếp tục làm bài' : '{{ $exam['is_completed'] ? 'Làm lại bài thi' : 'Bắt đầu làm bài' }}'">Bắt đầu làm bài</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- MODAL: CONFIRM RESET / REDO TEST --}}
    <template x-teleport="body">
        <div x-show="showResetModal" x-cloak
             style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999;">
            {{-- Backdrop --}}
            <div @click="showResetModal = false"
                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 8, 18, 0.88); backdrop-filter: blur(8px);"></div>
            {{-- Centered Card Container --}}
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; padding: 1rem; pointer-events: none;">
                <div style="background: #151d32; border: 1px solid #283758; border-radius: 1.5rem; max-width: 440px; width: 100%; padding: 1.75rem; box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.8); text-align: left; pointer-events: auto;">
                    
                    <h3 style="font-size: 1.35rem; font-weight: 800; color: #ffffff; margin: 0 0 0.75rem 0; letter-spacing: -0.02em;">
                        Làm lại bài thi?
                    </h3>
                    
                    <p style="font-size: 0.875rem; color: #cbd5e1; line-height: 1.6; margin: 0 0 1.5rem 0;">
                        Nếu làm lại, toàn bộ tiến trình hoặc kết quả hiện tại sẽ bị xóa và bạn sẽ bắt đầu lại từ đầu. Bạn có chắc chắn muốn làm lại không?
                    </p>

                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem; padding-top: 0.5rem;">
                        <button type="button" 
                                @click="showResetModal = false" 
                                style="padding: 0.65rem 1.5rem; border-radius: 9999px; background: #222d48; border: 1px solid #38486f; color: #ffffff; font-size: 0.875rem; font-weight: 700; cursor: pointer; transition: background 0.2s;"
                                onmouseover="this.style.background='#2c3a5e'" 
                                onmouseout="this.style.background='#222d48'">
                            Hủy
                        </button>

                        <button type="button" 
                                @click="executeResetAndStart()"
                                style="padding: 0.65rem 1.75rem; border-radius: 9999px; background: #2563eb; color: #ffffff; font-size: 0.875rem; font-weight: 700; cursor: pointer; border: none; box-shadow: 0 4px 14px rgba(37, 99, 246, 0.5); transition: transform 0.2s, background 0.2s;"
                                onmouseover="this.style.background='#1d4ed8'; this.style.transform='scale(1.03)'" 
                                onmouseout="this.style.background='#2563eb'; this.style.transform='scale(1)'">
                            Thử lại
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function practiceHubApp() {
    return {
        draftsMap: {},
        showResetModal: false,
        targetExamKey: '',
        targetExamUrl: '',

        init() {
            // Automatically clear leftover drafts for exams that are already completed in the database
            const completedKeys = @json(collect($examSets ?? $exams ?? [])->where('is_completed', true)->pluck('key')->all());
            for (const cKey of completedKeys) {
                try {
                    localStorage.removeItem('cbt_draft_' + cKey);
                    localStorage.removeItem('cbt_answers_' + cKey);
                } catch(e) {}
            }
            this.scanDrafts();
        },

        scanDrafts() {
            try {
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key && key.startsWith('cbt_draft_')) {
                        const examKey = key.replace('cbt_draft_', '');
                        const val = localStorage.getItem(key);
                        if (val) {
                            this.draftsMap[examKey] = true;
                        }
                    }
                }
            } catch(e) {}
        },

        hasDraft(examKey) {
            return !!this.draftsMap[examKey];
        },

        openResetConfirm(examKey, examUrl) {
            this.targetExamKey = examKey;
            this.targetExamUrl = examUrl;
            this.showResetModal = true;
        },

        executeResetAndStart() {
            if (this.targetExamKey) {
                try {
                    localStorage.removeItem('cbt_draft_' + this.targetExamKey);
                    localStorage.removeItem('cbt_answers_' + this.targetExamKey);
                } catch(e) {}
            }
            this.showResetModal = false;
            window.location.href = this.targetExamUrl + '?reset=1';
        }
    };
}
</script>
@endsection
