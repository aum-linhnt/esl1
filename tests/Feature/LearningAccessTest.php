<?php

namespace Tests\Feature;

use App\Http\Controllers\LessonController;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LearningAccessTest extends TestCase
{
    private function check(?Enrollment $enrollment, Activity $activity, bool $unlocked = true, bool $lessonVisible = true, bool $lessonOnly = false, string $role = 'student', ?int $creatorId = null, bool $published = true): mixed
    {
        $user = new User(['role' => $role]);
        $user->id = 1;
        $course = new Course(['created_by' => $creatorId, 'is_published' => $published]);
        $course->id = 1;
        if ($enrollment) {
            $enrollment->user_id = 1;
            $enrollment->course_id = 1;
        }
        $lesson = Mockery::mock(Lesson::class)->makePartial();
        $lesson->course_id = 1;
        $lesson->is_visible = $lessonVisible;
        $lesson->setRelation('course', $course);
        $lesson->shouldReceive('isUnlockedFor')->andReturn($unlocked);
        $controller = new class extends LessonController {
            public function check($user, $lesson, $enrollment, $activity) { return $this->checkLearningAccess($user, $lesson, $enrollment, $activity); }
        };

        return $controller->check($user, $lesson, $enrollment, $lessonOnly ? null : $activity);
    }

    private function activity(array $attributes = []): Activity
    {
        return new Activity(array_merge(['is_visible' => true, 'is_free_trial' => true], $attributes));
    }

    public function test_revoked_enrollment_cannot_fall_back_to_trial_in_either_entry_point(): void
    {
        foreach ([['status' => 'suspended'], ['status' => 'dropped'], ['status' => 'active', 'expires_at' => now()->subDay()]] as $attributes) {
            foreach ([false, true] as $lessonOnly) {
                $response = $this->check(new Enrollment($attributes), $this->activity(), lessonOnly: $lessonOnly);
                $this->assertSame(302, $response->getStatusCode());
                $this->assertSame(route('courses.show', 1), $response->getTargetUrl());
            }
        }
    }

    public function test_prerequisite_lock_blocks_lesson_and_activity_even_if_trial(): void
    {
        foreach ([false, true] as $lessonOnly) {
            $response = $this->check(new Enrollment(['status' => 'active', 'course_role' => 'student']), $this->activity(), false, lessonOnly: $lessonOnly);
            $this->assertSame(302, $response->getStatusCode());
        }
    }

    public function test_future_and_closed_activities_are_denied(): void
    {
        foreach ([['available_from' => now()->addDay()], ['available_until' => now()->subDay()]] as $attributes) {
            try {
                $this->check(null, $this->activity($attributes));
                $this->fail('Scheduled activity content was accessible.');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
    }

    public function test_hidden_lesson_or_activity_cannot_be_opened_directly(): void
    {
        foreach ([[false, true], [true, false]] as [$activityVisible, $lessonVisible]) {
            try {
                $this->check(null, $this->activity(['is_visible' => $activityVisible]), lessonVisible: $lessonVisible);
                $this->fail('Hidden content was accessible.');
            } catch (HttpException $error) {
                $this->assertSame(404, $error->getStatusCode());
            }
        }
    }

    public function test_trial_and_enrolled_access_remain_available_when_allowed(): void
    {
        $this->assertNull($this->check(null, $this->activity()));
        $this->assertSame(302, $this->check(null, $this->activity(['is_free_trial' => false]))->getStatusCode());
        $this->assertNull($this->check(new Enrollment(['status' => 'active', 'course_role' => 'student']), $this->activity(['is_free_trial' => false])));
    }

    public function test_admin_and_creator_can_preview_paid_and_scheduled_activities_without_enrollment(): void
    {
        foreach (['admin', 'teacher'] as $role) {
            $activity = $this->activity(['is_free_trial' => false, 'available_from' => now()->addDay()]);
            $this->assertNull($this->check(null, $activity, unlocked: false, role: $role, creatorId: 1, published: false));
            $this->assertNull($this->check(null, $activity, unlocked: false, lessonOnly: true, role: $role, creatorId: 1, published: false));
        }
    }

    public function test_unrelated_teacher_has_learner_access(): void
    {
        $this->assertNull($this->check(null, $this->activity(), role: 'teacher', creatorId: 2));
        $this->assertSame(302, $this->check(null, $this->activity(['is_free_trial' => false]), role: 'teacher', creatorId: 2)->getStatusCode());
        $enrollment = new Enrollment(['status' => 'active', 'course_role' => 'student']);
        $this->assertNull($this->check($enrollment, $this->activity(['is_free_trial' => false]), role: 'teacher', creatorId: 2));
        $this->assertSame(302, $this->check($enrollment, $this->activity(), unlocked: false, role: 'teacher', creatorId: 2)->getStatusCode());
    }

    public function test_assigned_staff_can_preview_only_with_valid_enrollment(): void
    {
        foreach (['teacher', 'assistant', 'manager'] as $courseRole) {
            $enrollment = new Enrollment(['status' => 'active', 'course_role' => $courseRole]);
            $activity = $this->activity(['is_free_trial' => false, 'available_from' => now()->addDay()]);
            $this->assertNull($this->check($enrollment, $activity, unlocked: false, role: 'teacher', creatorId: 2));
            $enrollment->status = 'suspended';
            $this->assertSame(302, $this->check($enrollment, $this->activity(), role: 'teacher', creatorId: 2)->getStatusCode());
        }
    }

    public function test_unrelated_teacher_cannot_preview_unpublished_course(): void
    {
        try {
            $this->check(null, $this->activity(), role: 'teacher', creatorId: 2, published: false);
            $this->fail('Unpublished course was accessible.');
        } catch (HttpException $error) {
            $this->assertSame(404, $error->getStatusCode());
        }
    }
}
