<?php

namespace App\Services\Learning;

use App\Models\Activity;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class StudyTime
{
    public function ready(): bool
    {
        return Schema::hasTable('learner_study_sessions') && Schema::hasTable('learner_study_intervals');
    }

    public function start(User $user, string $source, ?int $context): string
    {
        abort_unless($this->ready(), 503);
        $this->access($user, $source, $context);
        $id = (string) Str::uuid();
        DB::table('learner_study_sessions')->insert(['id' => $id, 'user_id' => $user->id, 'source' => $source,
            'context_id' => $context, 'started_at' => now(), 'last_reported_at' => now(), 'claimed_seconds' => 0]);
        return $id;
    }

    public function report(User $user, string $id, int $claimed): int
    {
        abort_unless($this->ready(), 503);
        return DB::transaction(function () use ($user, $id, $claimed) {
            $session = DB::table('learner_study_sessions')->where('id', $id)->where('user_id', $user->id)->lockForUpdate()->first();
            abort_unless($session, 404);
            $this->access($user, $session->source, $session->context_id);
            abort_if(Carbon::parse($session->started_at, config('app.timezone'))->lt(now()->subDay()), 410);
            // A retried or out-of-order cumulative report cannot append another interval.
            if ($claimed <= $session->claimed_seconds) return 0;
            $end = now()->startOfSecond();
            $elapsed = max(0, (int) Carbon::parse($session->last_reported_at, config('app.timezone'))->diffInSeconds($end, false));
            $seconds = min(60, $elapsed, $claimed - $session->claimed_seconds);
            DB::table('learner_study_sessions')->where('id', $id)->update(['claimed_seconds' => $claimed, 'last_reported_at' => $end]);
            if ($seconds > 0) DB::table('learner_study_intervals')->insert(['session_id' => $id,
                'started_at' => $end->copy()->subSeconds($seconds), 'ended_at' => $end]);
            return $seconds;
        }, 3);
    }

    public function week(User $user): Collection
    {
        $today = Carbon::today('Asia/Ho_Chi_Minh');
        $start = $today->copy()->subDays(6);
        $finish = $today->copy()->addDay();
        $databaseStart = $start->copy()->setTimezone(config('app.timezone'))->toDateTimeString();
        $databaseFinish = $finish->copy()->setTimezone(config('app.timezone'))->toDateTimeString();
        $ranges = [];
        // Legacy logs remain available; overlapping new telemetry is merged below.
        $logs = $user->activityLogs()->where('started_at', '<', $databaseFinish)
            ->where('started_at', '>=', $start->copy()->subDay()->setTimezone(config('app.timezone'))->toDateTimeString())->get();
        foreach ($logs as $log) {
            if (! $log->started_at || $log->duration_seconds <= 0) continue;
            $a = $log->started_at->getTimestamp();
            $ranges[] = [$a, min(now()->getTimestamp(), $a + min(86400, $log->duration_seconds)), 'lesson'];
        }
        if ($this->ready()) {
            $intervals = DB::table('learner_study_intervals as i')->join('learner_study_sessions as s', 's.id', '=', 'i.session_id')
                ->where('s.user_id', $user->id)->where('i.ended_at', '>', $databaseStart)->where('i.started_at', '<', $databaseFinish)
                ->get(['i.started_at', 'i.ended_at', 's.source']);
            foreach ($intervals as $interval) $ranges[] = [Carbon::parse($interval->started_at, config('app.timezone'))->getTimestamp(),
                Carbon::parse($interval->ended_at, config('app.timezone'))->getTimestamp(), $interval->source];
        }
        return collect(range(0, 6))->map(function ($offset) use ($start, $ranges) {
            $date = $start->copy()->addDays($offset);
            $sources = $this->partition($ranges, $date->getTimestamp(), $date->copy()->addDay()->getTimestamp());
            $seconds = array_sum($sources);
            return ['label' => $date->dayOfWeekIso === 7 ? 'CN' : 'Th '.($date->dayOfWeekIso + 1),
                'date' => $date->format('d/m'), 'iso_date' => $date->toDateString(), 'seconds' => $seconds,
                'minutes' => (int) round($seconds / 60), 'sources' => $sources];
        });
    }

    public function lastActivity(User $user): ?Carbon
    {
        $dates = collect([$user->last_active_date]);
        $legacy = $user->activityLogs()->where('duration_seconds', '>', 0)->where('started_at', '<=', now())->max('started_at');
        if ($legacy) $dates->push(Carbon::parse($legacy, config('app.timezone')));
        if ($this->ready()) {
            $latest = DB::table('learner_study_intervals as i')->join('learner_study_sessions as s', 's.id', '=', 'i.session_id')
                ->where('s.user_id', $user->id)->where('i.ended_at', '<=', now())->max('i.ended_at');
            if ($latest) $dates->push(Carbon::parse($latest, config('app.timezone')));
        }
        return $dates->filter()->sortByDesc(fn ($date) => $date->getTimestamp())->first();
    }

    private function partition(array $ranges, int $start, int $end): array
    {
        $edges = [];
        foreach ($ranges as [$a, $b, $source]) {
            $a = max($a, $start); $b = min($b, $end);
            if ($a >= $b) continue;
            $edges[$a][$source] = ($edges[$a][$source] ?? 0) + 1;
            $edges[$b][$source] = ($edges[$b][$source] ?? 0) - 1;
        }
        ksort($edges, SORT_NUMERIC);
        $active = []; $last = null;
        $totals = ['lesson' => 0, 'speaking' => 0, 'writing' => 0];
        foreach ($edges as $time => $changes) {
            if ($last !== null) {
                // Embedded activity wins over its surrounding lesson page.
                foreach (['activity', 'writing', 'speaking', 'lesson'] as $source) {
                    if (($active[$source] ?? 0) > 0) {
                        $totals[$source === 'activity' ? 'lesson' : $source] += $time - $last;
                        break;
                    }
                }
            }
            foreach ($changes as $source => $delta) $active[$source] = ($active[$source] ?? 0) + $delta;
            $last = $time;
        }
        return $totals;
    }

    private function access(User $user, string $source, ?int $context): void
    {
        abort_if($user->isBlocked() || $user->isTrialExpired(), 403);
        if ($source === 'speaking') return;
        if ($source === 'writing') {
            try { app(\TDSoft\AiTutor\Writing\WritingAccess::class)->check(); }
            catch (\TDSoft\AiTutor\Core\AiException) { abort(403); }
            return;
        }
        $activity = $source === 'activity' ? Activity::with('lesson.course')->findOrFail($context) : null;
        $lesson = $activity?->lesson ?? Lesson::with('course')->findOrFail($context);
        $enrollment = $user->getEnrollment($lesson->course_id);
        if ($lesson->course->canPreviewFor($user, $enrollment)) return;
        abort_unless($lesson->is_visible && $lesson->course->is_published && $enrollment && $enrollment->hasValidAccess()
            && $lesson->isUnlockedFor($user), 403);
        if ($activity) abort_unless($activity->is_visible && $activity->isAvailable(), 403);
    }
}
