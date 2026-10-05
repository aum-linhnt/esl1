<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'course_id', 'title', 'description', 'summary', 'order',
        'estimated_minutes', 'unlock_condition_score', 'is_free_trial', 'is_visible',
        'ai_answer_policy', 'ai_teacher_solution_allowed', 'ai_exam_mode',
    ];

    protected function casts(): array
    {
        return [
            'is_free_trial' => 'boolean',
            'is_visible' => 'boolean',
            'order' => 'integer',
            'unlock_condition_score' => 'integer',
            'ai_teacher_solution_allowed' => 'boolean',
            'ai_exam_mode' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function canManageAiTutorPolicy(User $user): bool
    {
        return $this->course !== null && self::canManageAiTutorForCourse($user, $this->course);
    }

    public static function canManageAiTutorForCourse(User $user, Course $course): bool
    {
        if ($user->isBlocked() || $user->isTrialExpired()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher() && (string) $course->created_by === (string) $user->id) {
            return true;
        }

        $enrollment = $user->getEnrollment($course->id);

        return $enrollment && $enrollment->hasValidAccess() && $enrollment->canManageCourseContent();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('order');
    }

    /**
     * Get only visible activities (for student-facing views).
     */
    public function visibleActivities(): HasMany
    {
        return $this->hasMany(Activity::class)
            ->where('is_visible', true)
            ->orderBy('order');
    }

    public function isFullyCompletedFor(User $user): bool
    {
        $visibleActivities = $this->activities()->where('is_visible', true)->get();
        $totalActivities = $visibleActivities->count();
        if ($totalActivities === 0) {
            return true;
        }

        $completedCount = ActivityCompletion::where('user_id', $user->id)
            ->where('lesson_id', $this->id)
            ->whereIn('activity_id', $visibleActivities->pluck('id'))
            ->count();

        return $completedCount >= $totalActivities;
    }

    public function isUnlockedFor(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $enrollment = $user->getEnrollment($this->course_id);

        if ($this->course->canPreviewFor($user, $enrollment)) {
            return true;
        }

        if (!$this->course->is_published || !$this->is_visible) {
            return false;
        }

        // Learners must be enrolled with valid access.
        if (!$enrollment || !$enrollment->hasValidAccess()) {
            return false;
        }

        // First lesson is unlocked once enrolled
        if ($this->order <= 1) {
            return true;
        }

        // Check previous lesson completion based on activities
        $previousLesson = Lesson::where('course_id', $this->course_id)
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();

        if (!$previousLesson) {
            return true;
        }

        return $previousLesson->isFullyCompletedFor($user);
    }

    /**
     * Check if this lesson is marked as a free trial.
     */
    public function isTrial(): bool
    {
        return (bool) $this->is_free_trial;
    }

    /**
     * Check if this lesson contains any visible activities marked as trial.
     */
    public function hasTrialActivities(): bool
    {
        return $this->activities()->where('is_visible', true)->where('is_free_trial', true)->exists();
    }
}
