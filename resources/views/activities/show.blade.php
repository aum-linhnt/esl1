@extends('layouts.app')

@section('content')
<div class="w-full space-y-4 sm:space-y-6" x-data="activityTelemetry({{ $activity->id }}, {{ ($isActivityCompleted ?? false) ? 'true' : 'false' }}, {{ ($isTrialMode ?? false) ? 'true' : 'false' }})" x-init="startTracking()">
    <a href="{{ route('lessons.show', $lesson->id) }}" class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-white bg-slate-900/80 border border-slate-800 px-3.5 py-1.5 rounded-full transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <span class="truncate max-w-xs">{{ $lesson->title }}</span>
    </a>

    @if($isTrialMode ?? false)
        <div class="p-4 rounded-2xl bg-gradient-to-r from-teal-500/15 via-indigo-500/15 to-blue-500/15 border border-teal-500/30 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-lg backdrop-blur-md">
            <div class="flex items-center gap-3">
                <span class="text-2xl sm:text-3xl">✨</span>
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-teal-300">Chế độ học thử (Free Trial Preview)</h3>
                    <p class="text-[11px] sm:text-xs text-gray-300">Bạn đang trải nghiệm học thử hoạt động này. Hệ thống không ghi nhận tiến trình hay điểm số cho đến khi bạn ghi danh chính thức.</p>
                </div>
            </div>
            <a href="{{ route('courses.show', $course->id) }}" class="bg-gradient-to-r from-fsel-blue to-indigo-600 hover:from-fsel-blue/90 hover:to-indigo-500 text-white text-xs font-bold px-4 py-2 rounded-xl transition-all shadow-md whitespace-nowrap self-stretch sm:self-auto text-center">
                Ghi danh khóa học
            </a>
        </div>
    @endif

    <div class="card-dark p-4 sm:p-6 lg:p-8 space-y-6 rounded-2xl sm:rounded-3xl border-slate-800/80">
        
        {{-- Activity Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800/80">
            <div class="flex items-center gap-2.5 flex-wrap min-w-0">
                @php
                    $badgeStyle = match($activity->type) {
                        'vocabulary' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
                        'grammar' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                        'video' => 'bg-red-500/20 text-red-400 border-red-500/30',
                        'audio_listening' => 'bg-teal-500/20 text-teal-400 border-teal-500/30',
                        'pdf_document' => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                        'ai_speaking' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                        'ai_writing' => 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30',
                        'quiz' => 'bg-green-500/20 text-green-400 border-green-500/30',
                        default => 'bg-slate-800 text-gray-300 border-slate-700',
                    };
                @endphp
                <span class="px-2.5 py-1 rounded-lg text-[10px] sm:text-xs font-bold capitalize border font-mono {{ $badgeStyle }}">
                    {{ str_replace('_', ' ', $activity->type) }}
                </span>
                <h1 class="text-base sm:text-xl font-bold text-white tracking-tight">{{ $activity->title }}</h1>
            </div>
            
            <div class="flex items-center gap-2 text-xs text-gray-400 font-mono bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800 self-start sm:self-auto">
                <span class="flex items-center gap-1 text-teal-300">⏱ <span x-text="formattedTime">00:00</span></span>
                <span>·</span>
                <span>~{{ $activity->estimated_minutes ?? 5 }}m</span>
            </div>
        </div>

        @php $content = $activity->content; @endphp

        {{-- Activity Type Content Dispatcher (Moodle-style) --}}
        @includeFirst(
            ['activities.types.' . $activity->type, 'activities.types._default']
        )
    </div>
</div>

<script>
function speakWord(text) {
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        window.speechSynthesis.speak(utterance);
    }
}

function activityTelemetry(activityId, initialCompleted, isTrialMode = false) {
    return {
        activityId: activityId,
        seconds: 0,
        timer: null,
        isCompleted: initialCompleted,
        saving: false,
        isTrialMode: isTrialMode,

        get formattedTime() {
            const m = String(Math.floor(this.seconds / 60)).padStart(2, '0');
            const s = String(this.seconds % 60).padStart(2, '0');
            return `${m}:${s}`;
        },

        startTracking() {
            this.timer = setInterval(() => {
                this.seconds++;
            }, 1000);
        },

        markCompleted(customScore = 100) {
            if (this.isTrialMode) {
                alert('✨ Bạn đang ở chế độ học thử. Hãy ghi danh vào khóa học để lưu tiến trình học tập và nhận điểm!');
                return;
            }
            if (this.saving) return;
            this.saving = true;

            fetch('{{ route("activities.complete", $activity->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    time_spent_seconds: this.seconds,
                    score: customScore,
                    max_score: 100
                })
            })
            .then(r => r.json())
            .then(data => {
                this.saving = false;
                if (data.trial_mode) {
                    alert('✨ Bạn đang ở chế độ học thử. Hãy ghi danh vào khóa học để lưu tiến trình học tập!');
                    return;
                }
                if (data.success) {
                    this.isCompleted = true;
                    let msg = '🎉 ' + (data.message || 'Hoạt động đã được đánh dấu hoàn thành!');
                    if (data.reward && data.reward.xp_earned) {
                        msg += `\n⚡ +${data.reward.xp_earned} XP | +${data.reward.coins_earned} Coins | 🔥 Streak: ${data.reward.streak_count} ngày`;
                    }
                    if (data.reward && data.reward.new_badges && data.reward.new_badges.length > 0) {
                        data.reward.new_badges.forEach(b => {
                            msg += `\n🏆 HUY HIỆU MỚI: ${b.icon} ${b.name} (${b.description})`;
                        });
                    }
                    if (data.lesson_completed) {
                        msg += '\n\n✨ Xuất sắc! Bạn đã hoàn thành toàn bộ bài học này và mở khóa bài học kế tiếp!';
                    }
                    alert(msg);
                } else {
                    alert(data.message || 'Không thể lưu tiến trình.');
                }
            })
            .catch(err => {
                this.saving = false;
                console.error(err);
                alert('Có lỗi khi lưu tiến trình học tập.');
            });
        }
    };
}
</script>
@endsection
