<?php

namespace Tests\Feature;

use App\Models\{Activity, AssignmentSubmission, Course, Enrollment, LearningGoal, User};
use Database\Seeders\AiTutorDemo\{AiTutorIeltsDemoSeeder, AiTutorIeltsShowcaseDemoSeeder, AiTutorToeicDemoSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiTutorIeltsShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_showcase_is_repeatable_preserves_submitted_work_and_opens_only_the_selected_course(): void
    {
        $this->seed(AiTutorToeicDemoSeeder::class);
        $other = Course::where('slug', AiTutorToeicDemoSeeder::SLUG)->firstOrFail();
        $this->seed(AiTutorIeltsShowcaseDemoSeeder::class);
        $course = Course::where('slug', AiTutorIeltsDemoSeeder::SLUG)->firstOrFail();
        $this->assertTrue($course->is_published);
        $this->assertFalse($course->allow_self_enrollment);
        $this->assertFalse($other->fresh()->is_published);
        $this->assertCount(5, $course->lessons);
        foreach ($course->lessons as $lesson) {
            $this->assertNotEmpty($lesson->summary);
            $this->assertSame(3, $lesson->activities()->count());
            $this->assertSame('text_page', $lesson->activities->first()->type);
            $this->assertGreaterThan(200, strlen($lesson->activities->first()->content['body']));
            $this->assertCount(3, $lesson->activities->firstWhere('type', 'quiz')->content['questions']);
            $this->assertStringContainsString('[DEMO', $lesson->activities->firstWhere('type', 'assignment')->content['instructions']);
        }
        $submission = AssignmentSubmission::whereIn('activity_id', $course->lessons->flatMap->activities->pluck('id'))->firstOrFail();
        $submission->update(['text_content' => 'Customer test: preserve my edited work.']);
        $counts = [Activity::count(), AssignmentSubmission::count(), DB::table('tutor_ai_conversation_messages')->count()];
        $this->seed(AiTutorIeltsShowcaseDemoSeeder::class);
        $this->assertSame($counts, [Activity::count(), AssignmentSubmission::count(), DB::table('tutor_ai_conversation_messages')->count()]);
        $this->assertSame('Customer test: preserve my edited work.', $submission->fresh()->text_content);
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }

    public function test_demo_student_can_read_complete_quiz_submit_assignment_and_unlock_next_lesson(): void
    {
        $this->seed(AiTutorIeltsShowcaseDemoSeeder::class);
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $course = Course::where('slug', AiTutorIeltsDemoSeeder::SLUG)->firstOrFail();
        $password = $student->password;
        $this->artisan('demo:ielts-student', ['email' => $student->email])->assertSuccessful();
        $this->assertSame($password, $student->fresh()->password);
        $this->assertSame('6.5', LearningGoal::where('user_id', $student->id)->firstOrFail()->target);
        $lessons = $course->lessons()->orderBy('order')->get();
        $lesson = $lessons->first();
        $material = $lesson->activities()->where('type', 'text_page')->firstOrFail();
        $quiz = $lesson->activities()->where('type', 'quiz')->firstOrFail();
        $assignment = $lesson->activities()->where('type', 'assignment')->firstOrFail();
        $this->actingAs($student)->get('/courses/'.$course->id)->assertOk()->assertSee('IELTS 6.5');
        $this->get('/lessons/'.$lesson->id)->assertOk();
        $this->get('/activities/'.$material->id)->assertOk()->assertSee('COURSE REGISTRATION');
        $this->postJson('/activities/'.$material->id.'/complete')->assertOk()->assertJsonPath('success', true);
        $started = $this->postJson('/activities/'.$quiz->id.'/attempts')->assertCreated();
        $this->assertCount(3, $started->json('questions'));
        $this->postJson('/activities/'.$quiz->id.'/complete', ['attempt_id' => $started->json('attempt_id'), 'score' => 100, 'max_score' => 100, 'time_spent_seconds' => 90])
            ->assertOk()->assertJsonPath('success', true);
        $this->post('/activities/'.$assignment->id.'/submit-assignment', ['text_content' => 'Maya Chen; Tuesday 6:30 p.m.; room 12; fee £80; register by Friday.'])->assertRedirect();
        $this->assertDatabaseHas('assignment_submissions', ['user_id' => $student->id, 'activity_id' => $assignment->id, 'status' => 'submitted', 'grade' => null]);
        $this->assertTrue($lesson->fresh()->isFullyCompletedFor($student));
        $this->assertTrue($lessons[1]->isUnlockedFor($student));
        $this->get('/lessons/'.$lessons[1]->id)->assertOk();
        $this->artisan('demo:ielts-student', ['email' => $student->email])->assertSuccessful();
        $this->assertSame($password, $student->fresh()->password);
        $this->assertSame(1, Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->count());
    }

    public function test_student_setup_creates_a_local_demo_account_without_resetting_it_on_repeat(): void
    {
        $this->seed(AiTutorIeltsShowcaseDemoSeeder::class);
        $this->artisan('demo:ielts-student', ['email' => 'customer@example.test'])->assertSuccessful();
        $student = User::where('email', 'customer@example.test')->firstOrFail();
        $this->assertTrue($student->isStudent());
        $this->assertNotNull($student->email_verified_at);
        $password = $student->password;
        $this->artisan('demo:ielts-student', ['email' => $student->email])->assertSuccessful();
        $this->assertSame($password, $student->fresh()->password);
        $this->assertDatabaseCount('learner_learning_goals', 1);
    }
}
