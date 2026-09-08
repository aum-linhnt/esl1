@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 pb-12" x-data="{ 
    filterMode: 'all', 
    filterSkill: 'all',
    openPassage: {} 
}">
    
    {{-- 1. SCORECARD HERO BANNER --}}
    <div class="card-dark p-6 sm:p-8 text-center space-y-6 border-amber-500/30 relative overflow-hidden bg-gradient-to-b from-purple-950/40 via-indigo-950/30 to-[#0f172a]">
        <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="space-y-2 relative z-10">
            <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-amber-500 to-orange-400 flex items-center justify-center text-4xl shadow-xl shadow-orange-500/20 mx-auto">
                🏆
            </div>

            <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full border text-xs font-bold font-mono bg-amber-500/15 text-amber-300 border-amber-500/30">
                <span>⚡ ESL COMPUTERIZED ADAPTIVE TESTING (CAT) REPORT</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                Bảng Điểm Chẩn Đoán Năng Lực 4 Kỹ Năng
            </h1>
            <p class="text-xs sm:text-sm text-gray-300 max-w-xl mx-auto leading-relaxed">
                Hệ thống CAT đã phân tích thuật toán đa tầng và định vị chính xác khung năng lực Châu Âu CEFR của bạn qua {{ $session->total_questions_answered }} câu hỏi khảo thí toàn diện.
            </p>
        </div>

        {{-- CEFR Assessed Level Spotlight Box --}}
        <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-950/70 via-indigo-950/50 to-slate-900 border border-blue-500/40 max-w-lg mx-auto relative z-10 shadow-lg">
            <span class="text-[11px] text-gray-300 uppercase font-mono tracking-wider block">Trình độ CEFR Tổng Thể Xếp Loại:</span>
            <div class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-teal-300 to-emerald-400 my-1 font-mono">
                {{ $finalLevel }}
            </div>
            <p class="text-xs text-gray-300 leading-relaxed pt-1 border-t border-slate-700/60 mt-2">
                {{ $descriptor }}
            </p>
        </div>

        {{-- Score Highlights Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-3xl mx-auto pt-2 relative z-10">
            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner text-center">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">Tỷ lệ chính xác</span>
                <span class="text-3xl font-black font-mono text-emerald-400">
                    {{ round(($session->correct_count / max(1, $session->total_questions_answered)) * 100) }}%
                </span>
                <span class="text-[10px] text-gray-500 block mt-0.5">Chuẩn hóa thích ứng</span>
            </div>

            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner text-center">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">Số câu đúng</span>
                <span class="text-3xl font-black text-white font-mono">
                    {{ $session->correct_count }}<span class="text-base text-gray-500 font-normal">/{{ $session->total_questions_answered }}</span>
                </span>
                <span class="text-[10px] text-emerald-400/80 block mt-0.5">{{ $session->correct_count }}/{{ $session->total_questions_answered }} câu đạt</span>
            </div>

            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner text-center">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">Tổng Điểm XP</span>
                <span class="text-3xl font-black font-mono text-amber-400">
                    {{ $session->score }}
                </span>
                <span class="text-[10px] text-amber-400/80 block mt-0.5">XP Tích Lũy</span>
            </div>

            <div class="bg-slate-900/90 p-4 rounded-2xl border border-slate-800 shadow-inner text-center">
                <span class="text-[10px] text-gray-400 uppercase font-bold block">ESL Coins Thưởng</span>
                <span class="text-3xl font-black font-mono text-yellow-400">
                    +{{ ceil($session->score / 5) }}
                </span>
                <span class="text-[10px] text-yellow-400/80 block mt-0.5">Đã cộng vào ví</span>
            </div>
        </div>

        {{-- 4-SKILL COMPREHENSIVE BREAKDOWN MATRIX --}}
        @if(!empty($skillMatrix))
            <div class="pt-6 border-t border-slate-800/80 relative z-10 text-left">
                <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider mb-3 text-center">
                    Ma Trận Năng Lực Chi Tiết 4 Kỹ Năng
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach($skillMatrix as $skKey => $sData)
                        @php
                            $skillColor = match($skKey) {
                                'listening' => 'from-emerald-500 to-teal-400 text-emerald-400 border-emerald-500/30',
                                'reading' => 'from-purple-500 to-indigo-400 text-purple-400 border-purple-500/30',
                                'writing' => 'from-cyan-500 to-blue-400 text-cyan-400 border-cyan-500/30',
                                'speaking' => 'from-amber-500 to-orange-400 text-amber-400 border-amber-500/30',
                                default => 'from-blue-500 to-cyan-400 text-blue-400 border-blue-500/30'
                            };
                            $pct = $sData['total'] > 0 ? round(($sData['correct'] / $sData['total']) * 100) : 0;
                        @endphp
                        <div class="p-4 rounded-2xl bg-slate-900/80 border {{ explode(' ', $skillColor)[2] }} space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-white flex items-center gap-1.5 text-xs">
                                    <span class="text-lg">{{ $sData['emoji'] }}</span>
                                    <span>{{ $sData['short_name'] }}</span>
                                </span>
                                <span class="font-mono font-bold text-xs px-2 py-0.5 rounded bg-slate-800 border border-slate-700 {{ explode(' ', $skillColor)[1] }}">
                                    Level {{ $sData['assessed_level'] }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-[11px] font-mono">
                                    <span class="text-gray-400">Độ chính xác:</span>
                                    <span class="font-bold text-white">{{ $pct }}%</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-1.5 overflow-hidden border border-slate-800">
                                    <div class="h-full rounded-full bg-gradient-to-r {{ explode(' ', $skillColor)[0] }}" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[10px] text-gray-400 font-mono pt-1 border-t border-slate-800">
                                <span>Hoàn thành:</span>
                                <span class="text-gray-200 font-bold">{{ $sData['correct'] }}/{{ $sData['total'] }} {{ $skKey === 'writing' || $skKey === 'speaking' ? 'bài đạt' : 'câu đúng' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-slate-800/80 relative z-10">
            <a href="{{ route('practice.startAdaptive') }}" 
               class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-xs font-bold text-gray-300 hover:text-white border border-slate-700 transition-colors cursor-pointer flex items-center gap-2">
                <span>🔄</span>
                <span>Làm lại bài thi thích ứng</span>
            </a>

            <a href="{{ route('leaderboard.index') }}" 
               class="px-5 py-2.5 rounded-xl bg-yellow-500/20 text-yellow-300 border border-yellow-500/30 text-xs font-bold hover:bg-yellow-500/30 transition-all flex items-center gap-1.5 shadow-sm">
                <span>🏆</span>
                <span>Bảng Xếp Hạng XP</span>
            </a>

            <a href="{{ route('practice.index') }}" 
               class="btn-primary !w-auto !py-2.5 px-6 text-xs font-bold shadow-glow-blue flex items-center gap-1.5">
                <span>Về Cổng Luyện Đề</span>
            </a>
        </div>
    </div>


    {{-- 2. DETAILED QUESTION-BY-QUESTION REVIEW WITH FILTERS --}}
    <div class="space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>Phân Tích Chi Tiết {{ count($history) }} Câu Hỏi & Đáp Án (Khảo Thí Toàn Diện 4 Kỹ Năng)</span>
                    <span class="text-xs font-mono font-normal px-2.5 py-0.5 rounded-full bg-slate-800 text-gray-400 border border-slate-700">
                        {{ count($history) }} câu đã làm
                    </span>
                </h2>
                <p class="text-xs text-gray-400">Xem lại từng câu hỏi, bài nghe, bài đọc, câu trả lời của bạn và giải thích cặn kẽ từ chuyên gia</p>
            </div>

            {{-- Filter Controls --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Status Filter --}}
                <div class="flex items-center bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs font-medium">
                    <button type="button" @click="filterMode = 'all'" 
                            class="px-3 py-1 rounded-lg transition-colors cursor-pointer" 
                            :class="filterMode === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        Tất cả ({{ count($history) }})
                    </button>
                    <button type="button" @click="filterMode = 'correct'" 
                            class="px-3 py-1 rounded-lg transition-colors cursor-pointer" 
                            :class="filterMode === 'correct' ? 'bg-emerald-600 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        Đúng ({{ $session->correct_count }})
                    </button>
                    <button type="button" @click="filterMode = 'wrong'" 
                            class="px-3 py-1 rounded-lg transition-colors cursor-pointer" 
                            :class="filterMode === 'wrong' ? 'bg-red-600 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        Chưa đúng ({{ count($history) - $session->correct_count }})
                    </button>
                </div>

                {{-- Skill Filter --}}
                <div class="flex items-center bg-slate-900 p-1 rounded-xl border border-slate-800 text-xs font-medium">
                    <button type="button" @click="filterSkill = 'all'" 
                            class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                            :class="filterSkill === 'all' ? 'bg-slate-700 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        Tất cả kỹ năng
                    </button>
                    <button type="button" @click="filterSkill = 'listening'" 
                            class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                            :class="filterSkill === 'listening' ? 'bg-emerald-600/80 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        🎧 Nghe
                    </button>
                    <button type="button" @click="filterSkill = 'reading'" 
                            class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                            :class="filterSkill === 'reading' ? 'bg-purple-600/80 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        📖 Đọc
                    </button>
                    <button type="button" @click="filterSkill = 'writing'" 
                            class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                            :class="filterSkill === 'writing' ? 'bg-cyan-600/80 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        ✍️ Viết
                    </button>
                    <button type="button" @click="filterSkill = 'speaking'" 
                            class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                            :class="filterSkill === 'speaking' ? 'bg-amber-600/80 text-white font-bold' : 'text-gray-400 hover:text-white'">
                        🎙️ Nói
                    </button>
                </div>
            </div>
        </div>

        {{-- Question Cards List --}}
        <div class="space-y-4">
            @forelse($history as $idx => $item)
                @php
                    $isCorrect = !empty($item['is_correct']);
                    $itemSkill = $item['skill'] ?? 'listening';
                    $skillEmoji = match($itemSkill) {
                        'listening' => '🎧',
                        'reading' => '📖',
                        'writing' => '✍️',
                        'speaking' => '🎙️',
                        default => '⚡'
                    };
                @endphp
                <div class="card-dark p-6 space-y-4 border {{ $isCorrect ? 'border-emerald-500/30' : 'border-red-500/30' }}"
                     x-show="(filterMode === 'all' || (filterMode === 'correct' && {{ $isCorrect ? 'true' : 'false' }}) || (filterMode === 'wrong' && {{ !$isCorrect ? 'true' : 'false' }})) && (filterSkill === 'all' || filterSkill === '{{ $itemSkill }}')">
                    
                    {{-- Header Row: Index + Skill Badge + CEFR Level + Result Tag --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="w-8 h-8 rounded-xl text-xs font-bold font-mono flex items-center justify-center shadow-md flex-shrink-0 {{ $isCorrect ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-red-500/20 text-red-300 border border-red-500/40' }}">
                                {{ $idx + 1 }}
                            </span>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-slate-800 text-gray-200 border border-slate-700">
                                        Bậc: {{ $item['difficulty'] ?? 'A1' }}
                                    </span>
                                    <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-500/30 uppercase">
                                        {{ $skillEmoji }} {{ ucfirst($itemSkill) }}
                                    </span>
                                    @if(!empty($item['task_type']))
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-500/15 text-purple-300 border border-purple-500/30">
                                            {{ $item['task_type'] }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-sm sm:text-base font-semibold text-white leading-relaxed pt-1">
                                    {{ $item['question_text'] }}
                                </h3>
                            </div>
                        </div>

                        <span class="text-xs font-bold px-3 py-1 rounded-full border flex-shrink-0 {{ $isCorrect ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-red-500/10 text-red-400 border-red-500/30' }}">
                            {{ $isCorrect ? '✓ Đúng (+10đ)' : '✕ Chưa đúng' }}
                        </span>
                    </div>

                    {{-- SPECIALIZED CONTENT BY SKILL --}}

                    {{-- 1. READING PASSAGE ACCORDION (If reading question has passage) --}}
                    @if(!empty($item['passage']))
                        <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2">
                            <button type="button" 
                                    @click="openPassage['p_{{ $idx }}'] = !openPassage['p_{{ $idx }}']"
                                    class="w-full flex items-center justify-between text-xs font-bold text-purple-300 hover:text-purple-200 transition-colors cursor-pointer">
                                <span class="flex items-center gap-1.5">
                                    <span>📖</span>
                                    <span>{{ $item['passage_title'] ?: 'Xem đoạn văn đọc hiểu của câu này' }}</span>
                                </span>
                                <span class="font-mono text-gray-400" x-text="openPassage['p_{{ $idx }}'] ? '▲ Thu gọn' : '▼ Mở xem bài đọc'"></span>
                            </button>
                            <div x-show="openPassage['p_{{ $idx }}']" x-cloak
                                 class="pt-2 text-xs text-gray-300 leading-relaxed whitespace-pre-line border-t border-slate-800 font-sans max-h-64 overflow-y-auto cbt-scrollbar pr-2">
                                {{ $item['passage'] }}
                            </div>
                        </div>
                    @endif

                    {{-- 2. LISTENING AUDIO PLAYER (If listening question has audio) --}}
                    @if($itemSkill === 'listening' && !empty($item['audio_url']))
                        <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center gap-3">
                            <audio controls class="h-8 w-full max-w-md">
                                <source src="{{ $item['audio_url'] }}" type="audio/mpeg">
                                Trình duyệt không hỗ trợ thẻ audio.
                            </audio>
                            <span class="text-xs text-gray-400 font-mono hidden sm:inline">🎧 Nghe lại audio câu này</span>
                        </div>
                    @endif

                    {{-- 3. CANDIDATE'S ANSWER VS CORRECT ANSWER --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        {{-- Candidate's Answer --}}
                        <div class="p-3.5 rounded-xl border text-xs space-y-1 {{ $isCorrect ? 'bg-emerald-950/20 border-emerald-500/30' : 'bg-red-950/20 border-red-500/30' }}">
                            <span class="text-[10px] uppercase font-bold tracking-wider block {{ $isCorrect ? 'text-emerald-400' : 'text-red-400' }}">
                                Câu trả lời của bạn:
                            </span>
                            <div class="font-semibold text-white leading-relaxed">
                                @if(str_starts_with((string)$item['user_answer'], 'audio_recording'))
                                    <span class="flex items-center gap-1.5 text-amber-300">
                                        <span>🎙️ Bản ghi âm câu trả lời đã nộp</span>
                                    </span>
                                @elseif($itemSkill === 'writing')
                                    <p class="whitespace-pre-line text-gray-200 font-mono text-[11px] bg-slate-950 p-2.5 rounded-lg border border-slate-800 max-h-36 overflow-y-auto cbt-scrollbar">
                                        {{ $item['user_answer'] }}
                                    </p>
                                    <span class="text-[10px] text-gray-400 block mt-1">Độ dài: {{ str_word_count(strip_tags((string)$item['user_answer'])) }} từ</span>
                                @else
                                    {{ $item['user_answer'] ?: '(Bỏ trống - Chưa trả lời)' }}
                                @endif
                            </div>
                        </div>

                        {{-- Correct Answer --}}
                        <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 text-xs space-y-1">
                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider block">
                                Đáp án chuẩn xác / Tiêu chí hoàn thành:
                            </span>
                            <div class="font-semibold text-emerald-400 leading-relaxed">
                                @if($itemSkill === 'writing')
                                    <span>Hoàn thành đầy đủ các ý yêu cầu, tối thiểu {{ $item['min_words'] ?? 40 }} từ, cấu trúc câu rõ ràng.</span>
                                @elseif($itemSkill === 'speaking')
                                    <span>Ghi âm phát âm rõ ràng, phản xạ trôi chảy và trả lời đầy đủ các gợi ý đề bài.</span>
                                @else
                                    {{ $item['correct_answer'] }}
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 4. EXPLANATION BOX --}}
                    @if(!empty($item['explanation']) || !empty($item['feedback_note']))
                        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 text-xs leading-relaxed text-gray-300 space-y-1.5">
                            <span class="font-bold text-blue-400 flex items-center gap-1">
                                <span>💡</span>
                                <span>Phân tích & Giải thích chi tiết:</span>
                            </span>
                            <p class="text-gray-200 leading-relaxed">
                                {{ $item['explanation'] ?: $item['feedback_note'] }}
                            </p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="card-dark p-8 text-center space-y-3">
                    <p class="text-gray-400 text-sm">Chưa có câu hỏi nào được lưu lại trong phiên này.</p>
                </div>
            @endforelse
        </div>
    </div>


    {{-- 3. PERSONALIZED RECOMMENDED COURSES & NEXT STEPS --}}
    @if(!empty($recommendedCourses) && $recommendedCourses->count() > 0)
        <div class="card-dark p-6 sm:p-8 space-y-5 border-blue-500/30">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                        <span>🎓 Khóa Học Đề Xuất Dành Riêng Cho Trình Độ {{ $finalLevel }}</span>
                    </h3>
                    <p class="text-xs text-gray-400">Dựa trên kết quả bài thi thích ứng, hệ thống AI cá nhân hóa lộ trình tối ưu nhất cho bạn</p>
                </div>

                <a href="{{ route('courses.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-bold hidden sm:inline">
                    Xem toàn bộ khóa học
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($recommendedCourses as $course)
                    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 hover:border-blue-500/50 transition-all flex flex-col justify-between space-y-3 group">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold font-mono bg-blue-500/15 text-blue-300 border border-blue-500/30">
                                    Cấp độ: {{ $course->level }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-mono">
                                    {{ $course->estimated_hours ?? 20 }} giờ học
                                </span>
                            </div>

                            <h4 class="text-sm font-bold text-white group-hover:text-fsel-teal transition-colors line-clamp-2">
                                {{ $course->title }}
                            </h4>

                            <p class="text-xs text-gray-400 line-clamp-2 leading-relaxed">
                                {{ $course->description ?? 'Khóa học cung cấp kiến thức toàn diện, luyện tập phản xạ và bài tập tương tác theo chuẩn CEFR.' }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-xs text-emerald-400 font-bold">Khuyên dùng</span>
                            <a href="{{ route('courses.show', $course->id) }}" 
                               class="btn-primary !py-1.5 !px-4 text-xs font-bold shadow-glow-blue flex items-center gap-1">
                                <span>Vào học ngay</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
