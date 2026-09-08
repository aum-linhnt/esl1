<?php

namespace App\Services\Storage;

use App\Models\User;
use App\Models\Activity;
use App\Models\Lesson;
use App\Models\UserActivityLog;
use App\Models\UserProgress;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ActivityTrackerService
{
    /**
     * Log an activity step/telemetry and track real learning duration.
     *
     * @param int $userId
     * @param int $activityId
     * @param int $duration Duration in seconds spent on this step
     * @param array $stepData Telemetry data (click, current_step, total_steps, answers, etc.)
     * @return UserActivityLog
     */
    public function logStep(int $userId, int $activityId, int $duration, array $stepData = []): UserActivityLog
    {
        $activity = Activity::findOrFail($activityId);
        $sessionId = $stepData['session_id'] ?? (string) Str::uuid();
        $isCompleted = !empty($stepData['completed']);

        // Find or create current activity session log
        $log = UserActivityLog::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if (!$log) {
            $log = UserActivityLog::create([
                'user_id' => $userId,
                'activity_id' => $activityId,
                'session_id' => $sessionId,
                'started_at' => Carbon::now()->subSeconds(max(0, $duration)),
                'duration_seconds' => $duration,
                'status' => $isCompleted ? 'completed' : 'in_progress',
                'meta_data' => $stepData,
                'completed_at' => $isCompleted ? Carbon::now() : null,
            ]);
        } else {
            $log->update([
                'duration_seconds' => $log->duration_seconds + $duration,
                'status' => $isCompleted ? 'completed' : 'in_progress',
                'completed_at' => $isCompleted ? Carbon::now() : $log->completed_at,
                'meta_data' => array_merge($log->meta_data ?? [], $stepData),
            ]);
        }

        return $log;
    }

    /**
     * Check if a lesson is unlocked for a user based on previous lesson completion & score.
     *
     * @param int $userId
     * @param int $lessonId
     * @return bool
     */
    public function checkUnlockCondition(int $userId, int $lessonId): bool
    {
        $lesson = Lesson::findOrFail($lessonId);

        // Lesson 1 is always unlocked
        if ($lesson->order <= 1) {
            return true;
        }

        // Find the immediately preceding lesson in the same course
        $prevLesson = Lesson::where('course_id', $lesson->course_id)
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        if (!$prevLesson) {
            return true;
        }

        $progress = UserProgress::where('user_id', $userId)
            ->where('lesson_id', $prevLesson->id)
            ->first();

        if (!$progress) {
            return false;
        }

        // User must meet or exceed the unlock condition score
        return $progress->completed || $progress->score >= $lesson->unlock_condition_score;
    }
}
