<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class AssignmentSubmissionPolicy
{
    /**
     * Determine whether the user can view the submission.
     */
    public function view(User $user, AssignmentSubmission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($submission->user_id === $user->id) {
            return true;
        }

        return $this->isTeacherOfSubmissionCourse($user, $submission);
    }

    /**
     * Determine whether the user can grade the submission.
     */
    public function grade(User $user, AssignmentSubmission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->isTeacherOfSubmissionCourse($user, $submission);
    }

    /**
     * Determine whether the user can update/re-submit the submission.
     */
    public function update(User $user, AssignmentSubmission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($submission->user_id === $user->id) {
            return in_array($submission->status, [
                AssignmentSubmission::STATUS_DRAFT,
                AssignmentSubmission::STATUS_RETURNED,
            ], true);
        }

        return false;
    }

    private function isTeacherOfSubmissionCourse(User $user, AssignmentSubmission $submission): bool
    {
        if (!$user->isTeacher()) {
            return false;
        }

        $courseId = $submission->activity?->lesson?->course_id;
        if (!$courseId) {
            return false;
        }

        // Check if teacher created the course
        if (Course::where('id', $courseId)->where('created_by', $user->id)->exists()) {
            return true;
        }

        // Check if teacher is enrolled with teacher/manager role
        return Enrollment::where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->whereIn('course_role', [Enrollment::ROLE_TEACHER, Enrollment::ROLE_ASSISTANT, Enrollment::ROLE_MANAGER])
            ->where('status', 'active')
            ->exists();
    }
}
