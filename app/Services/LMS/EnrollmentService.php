<?php

namespace App\Services\LMS;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ActivityCompletion;
use App\Models\UserProgress;
use Carbon\Carbon;

class EnrollmentService
{
    /**
     * Enroll a user into a course with a specific course context role and optional expiration date (Moodle-style).
     */
    public function enrollUser(User $user, Course $course, string $courseRole = 'student', ?Carbon $expiresAt = null): Enrollment
    {
        $existing = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            // Re-activate if previously dropped or update role & expiration
            $updates = [
                'course_role' => $courseRole,
                'expires_at' => $expiresAt,
            ];
            if ($existing->status === 'dropped' || $existing->status === Enrollment::STATUS_SUSPENDED) {
                $updates['status'] = Enrollment::STATUS_ACTIVE;
                $updates['enrolled_at'] = Carbon::now();
                $updates['completed_at'] = null;
                $updates['final_grade'] = null;
            }
            $existing->update($updates);
            $this->recalculateProgress($existing);
            return $existing;
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_role' => $courseRole,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => Carbon::now(),
            'expires_at' => $expiresAt,
            'progress_percentage' => 0,
        ]);

        return $enrollment;
    }

    /**
     * Update user's role within a course context.
     */
    public function updateCourseRole(Enrollment $enrollment, string $newRole): bool
    {
        if (!in_array($newRole, array_keys(Enrollment::$roleDefinitions))) {
            return false;
        }

        $enrollment->update(['course_role' => $newRole]);
        return true;
    }

    /**
     * Extend or update enrollment expiration date.
     */
    public function updateExpiry(Enrollment $enrollment, ?Carbon $expiresAt): void
    {
        $enrollment->update(['expires_at' => $expiresAt]);
    }

    /**
     * 1-Click Toggle Suspend / Active status for an enrollment.
     */
    public function toggleSuspend(Enrollment $enrollment): bool
    {
        if ($enrollment->status === Enrollment::STATUS_SUSPENDED) {
            $enrollment->update(['status' => Enrollment::STATUS_ACTIVE]);
            return true; // now active
        } else {
            $enrollment->update(['status' => Enrollment::STATUS_SUSPENDED]);
            return false; // now suspended
        }
    }

    /**
     * Unenroll (drop or delete) a user from a course.
     */
    public function unenrollUser(User $user, Course $course, bool $forceDelete = false): bool
    {
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return false;
        }

        if ($forceDelete) {
            $enrollment->delete();
            return true;
        }

        if ($enrollment->status === 'completed') {
            return false;
        }

        $enrollment->update(['status' => 'dropped']);
        return true;
    }

    /**
     * Check course access permissions and status for a user.
     */
    public function checkAccess(User $user, Course|int $course): array
    {
        $courseId = $course instanceof Course ? $course->id : $course;
        $enrollment = $user->getEnrollment($courseId);

        if ($user->isAdmin() || $user->isTeacher()) {
            return [
                'has_access' => true,
                'is_enrolled' => (bool) $enrollment,
                'is_manager' => true,
                'is_suspended' => false,
                'is_expired' => false,
                'enrollment' => $enrollment,
            ];
        }

        $isEnrolled = $enrollment !== null;
        $isSuspended = $enrollment?->isSuspended() ?? false;
        $isExpired = $enrollment?->isExpired() ?? false;
        $hasAccess = $isEnrolled && !$isSuspended && !$isExpired;

        return [
            'has_access' => $hasAccess,
            'is_enrolled' => $isEnrolled,
            'is_manager' => $enrollment?->canGradeStudents() ?? false,
            'is_suspended' => $isSuspended,
            'is_expired' => $isExpired,
            'enrollment' => $enrollment,
        ];
    }

    /**
     * Recalculate progress percentage for an enrollment based on individual activity completions.
     */
    public function recalculateProgress(Enrollment $enrollment): void
    {
        $course = $enrollment->course;
        $user = $enrollment->user;

        // Query all visible activity IDs across lessons in this course
        $activityIds = \App\Models\Activity::whereHas('lesson', function ($q) use ($course) {
            $q->where('course_id', $course->id)->where('is_visible', true);
        })->where('is_visible', true)->pluck('id');

        $totalActivities = $activityIds->count();

        if ($totalActivities === 0) {
            $enrollment->update(['progress_percentage' => 0]);
            return;
        }

        // Count completed activities for this user
        $completedActivities = ActivityCompletion::where('user_id', $user->id)
            ->whereIn('activity_id', $activityIds)
            ->count();

        $percentage = (int) round(($completedActivities / $totalActivities) * 100);

        $enrollment->update(['progress_percentage' => min($percentage, 100)]);

        // Auto-complete course if 100%
        if ($percentage >= 100) {
            $this->completeCourse($enrollment);
        }
    }

    /**
     * Mark a course enrollment as completed and calculate final grade.
     */
    public function completeCourse(Enrollment $enrollment): void
    {
        if ($enrollment->status === 'completed') {
            return;
        }

        $finalGrade = app(GradebookService::class)->getCourseGrade(
            $enrollment->user,
            $enrollment->course
        );

        $enrollment->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
            'final_grade' => $finalGrade,
            'progress_percentage' => 100,
        ]);
    }

    /**
     * Get enrollment statistics for admin reports.
     */
    public function getEnrollmentStats(): array
    {
        return [
            'total' => Enrollment::count(),
            'active' => Enrollment::where('status', 'active')->count(),
            'completed' => Enrollment::where('status', 'completed')->count(),
            'dropped' => Enrollment::where('status', 'dropped')->count(),
            'avg_progress' => (float) Enrollment::where('status', 'active')->avg('progress_percentage') ?? 0,
            'completion_rate' => Enrollment::count() > 0
                ? round((Enrollment::where('status', 'completed')->count() / Enrollment::count()) * 100, 1)
                : 0,
        ];
    }
}
