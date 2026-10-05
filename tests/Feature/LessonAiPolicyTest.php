<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonAiPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(User $owner): Lesson
    {
        $course = Course::create(['title' => 'Policy course', 'slug' => 'policy-'.uniqid(), 'created_by' => $owner->id, 'is_published' => true]);

        return Lesson::create(['course_id' => $course->id, 'title' => 'Policy lesson', 'order' => 1, 'is_free_trial' => true]);
    }

    private function settings(array $overrides = []): array
    {
        return array_merge(['ai_answer_policy' => 'teacher_controlled', 'ai_teacher_solution_allowed' => true, 'ai_exam_mode' => false], $overrides);
    }

    public function test_admin_and_owner_teacher_can_edit_policy_and_existing_lessons_default_to_hints_only(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $lesson = $this->lesson($teacher)->fresh();
        $this->assertSame('hints_only', $lesson->ai_answer_policy);
        $this->assertFalse($lesson->ai_teacher_solution_allowed);
        $this->assertFalse($lesson->ai_exam_mode);
        $url = route('courses.lessons.ai-policy.edit', [$lesson->course_id, $lesson->id]);
        $this->actingAs($teacher)->get($url)->assertOk()->assertSee('Chính sách Gia sư AI');
        $this->actingAs($teacher)->put($url, $this->settings())->assertRedirect($url);
        $this->assertTrue($lesson->fresh()->ai_teacher_solution_allowed);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put($url, $this->settings(['ai_exam_mode' => true]))->assertRedirect($url);
        $this->assertTrue($lesson->fresh()->ai_exam_mode);
    }

    public function test_student_unrelated_teacher_and_assistant_cannot_change_policy_through_either_endpoint(): void
    {
        $lesson = $this->lesson(User::factory()->create(['role' => 'teacher']));
        $url = route('courses.lessons.ai-policy.edit', [$lesson->course_id, $lesson->id]);
        $student = User::factory()->create();
        $outsider = User::factory()->create(['role' => 'teacher']);
        $assistant = User::factory()->create(['role' => 'teacher']);
        Enrollment::create(['user_id' => $assistant->id, 'course_id' => $lesson->course_id, 'course_role' => 'assistant', 'status' => 'active']);
        foreach ([$student, $outsider, $assistant] as $user) {
            $this->actingAs($user)->get($url)->assertForbidden();
            $this->actingAs($user)->put($url, $this->settings())->assertForbidden();
            $this->actingAs($user)->put(route('admin.courses.lessons.update', [$lesson->course_id, $lesson->id]), array_merge($this->settings(), [
                'title' => 'Policy lesson', 'order' => 1, 'estimated_minutes' => 20, 'unlock_condition_score' => 0,
            ]))->assertForbidden();
        }
        $this->assertSame('hints_only', $lesson->fresh()->ai_answer_policy);
    }

    public function test_assigned_teacher_can_edit_but_suspended_or_blocked_teacher_cannot(): void
    {
        $lesson = $this->lesson(User::factory()->create(['role' => 'teacher']));
        $teacher = User::factory()->create(['role' => 'teacher']);
        $enrollment = Enrollment::create(['user_id' => $teacher->id, 'course_id' => $lesson->course_id, 'course_role' => 'teacher', 'status' => 'active']);
        $url = route('courses.lessons.ai-policy.edit', [$lesson->course_id, $lesson->id]);
        $this->actingAs($teacher)->put($url, $this->settings())->assertRedirect($url);
        $enrollment->update(['status' => 'suspended']);
        $this->actingAs($teacher)->put($url, $this->settings())->assertForbidden();
        $enrollment->update(['status' => 'active']);
        $teacher->update(['status' => 'blocked']);
        $this->actingAs($teacher)->put($url, $this->settings())->assertForbidden();
    }

    public function test_invalid_policy_is_rejected_and_legacy_lesson_update_preserves_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lesson = $this->lesson($admin);
        $url = route('courses.lessons.ai-policy.edit', [$lesson->course_id, $lesson->id]);
        $this->actingAs($admin)->putJson($url, $this->settings(['ai_answer_policy' => 'invented']))->assertUnprocessable()->assertJsonValidationErrors('ai_answer_policy');
        $this->actingAs($admin)->put($url, $this->settings(['ai_exam_mode' => true]))->assertRedirect($url);
        $this->actingAs($admin)->put(route('admin.courses.lessons.update', [$lesson->course_id, $lesson->id]), [
            'title' => 'Renamed lesson', 'order' => 1, 'estimated_minutes' => 20, 'unlock_condition_score' => 0,
        ])->assertRedirect();
        $this->assertSame('teacher_controlled', $lesson->fresh()->ai_answer_policy);
        $this->assertTrue($lesson->fresh()->ai_exam_mode);
        $this->assertTrue($lesson->fresh()->ai_teacher_solution_allowed);
    }
}
