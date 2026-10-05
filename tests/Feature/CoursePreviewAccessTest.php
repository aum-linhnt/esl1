<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LMS\EnrollmentService;
use Mockery;
use Tests\TestCase;

class CoursePreviewAccessTest extends TestCase
{
    private function course(): Course
    {
        $course = new Course(['created_by' => 2, 'is_published' => true]);
        $course->id = 10;
        return $course;
    }

    public function test_preview_requires_admin_ownership_or_valid_course_assignment(): void
    {
        $course = $this->course();
        foreach ([['admin', 1, true], ['teacher', 2, true], ['teacher', 1, false], ['student', 2, false]] as [$role, $id, $expected]) {
            $user = new User(['role' => $role]);
            $user->id = $id;
            $this->assertSame($expected, $course->canPreviewFor($user));
        }
        $user = new User(['role' => 'teacher']);
        $user->id = 1;
        foreach (['teacher', 'assistant', 'manager'] as $role) {
            $enrollment = new Enrollment(['user_id' => 1, 'course_id' => 10, 'course_role' => $role, 'status' => 'active']);
            $this->assertTrue($course->canPreviewFor($user, $enrollment));
            foreach ([['status' => 'suspended'], ['status' => 'dropped'], ['expires_at' => now()->subDay()], ['course_id' => 11], ['user_id' => 3], ['course_role' => 'student']] as $change) {
                $invalid = clone $enrollment;
                $invalid->forceFill($change);
                $this->assertFalse($course->canPreviewFor($user, $invalid));
            }
        }
    }

    public function test_unrelated_teacher_cannot_bypass_lesson_or_course_access(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 1, 'role' => 'teacher']);
        $user->shouldReceive('getEnrollment')->with(10)->andReturn(null);
        $course = $this->course();
        $lesson = new Lesson(['course_id' => 10, 'order' => 1, 'is_visible' => true]);
        $lesson->setRelation('course', $course);
        $this->assertFalse($lesson->isUnlockedFor($user));
        $access = (new EnrollmentService)->checkAccess($user, $course);
        $this->assertFalse($access['has_access']);
        $this->assertFalse($access['is_manager']);
        $course->created_by = 1;
        $this->assertTrue($lesson->isUnlockedFor($user));
        $this->assertTrue((new EnrollmentService)->checkAccess($user, $course)['is_manager']);
    }
}
