<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class CoursePolicy
{
    /**
     * Determine whether the user can view any courses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the specific course.
     */
    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($course->created_by === $user->id) {
            return true;
        }

        if ($course->isPublished()) {
            return true;
        }

        return $course->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    /**
     * Determine whether the user can create courses.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    /**
     * Determine whether the user can update the course.
     */
    public function update(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($course->created_by === $user->id) {
            return true;
        }

        return $course->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('course_role', [Enrollment::ROLE_TEACHER, Enrollment::ROLE_MANAGER])
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can delete the course.
     */
    public function delete(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $course->created_by === $user->id;
    }

    /**
     * Determine whether the user can manage course enrollments.
     */
    public function manageEnrollments(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($course->created_by === $user->id) {
            return true;
        }

        return $course->enrollments()
            ->where('user_id', $user->id)
            ->whereIn('course_role', [Enrollment::ROLE_TEACHER, Enrollment::ROLE_MANAGER])
            ->where('status', 'active')
            ->exists();
    }
}
