<?php

namespace Tests\Feature;

use App\Models\{Course, Enrollment, User};
use App\Services\Learning\TeacherTutorOverview;
use Database\Seeders\AiTutorDemo\AiTutorManagementDemoSeeder;
use Database\Seeders\AiTutorDemo\AiTutorIeltsDemoSeeder;
use Database\Seeders\AiTutorDemo\AiTutorToeicDemoSeeder;
use Database\Seeders\AiTutorDemo\AiTutorToeicFourSkillsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiTutorManagementDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_repeatable_and_populates_all_management_panels_without_spending_credit(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $password = $teacher->password;
        $this->seed(AiTutorManagementDemoSeeder::class);
        $course = Course::where('slug', AiTutorManagementDemoSeeder::SLUG)->firstOrFail();
        $this->assertFalse($course->is_published);
        $this->assertDatabaseHas('enrollments', ['course_id' => $course->id, 'user_id' => $teacher->id, 'course_role' => 'manager']);
        $tables = ['users', 'courses', 'lessons', 'activities', 'enrollments', 'quiz_attempts', 'assignment_submissions', 'tutor_ai_conversations', 'tutor_ai_conversation_messages', 'tutor_ai_requests', 'tutor_ai_usage_records', 'tutor_ai_message_feedback'];
        $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
        $this->seed(AiTutorManagementDemoSeeder::class);
        foreach ($counts as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table);
        $this->assertSame($password, $teacher->fresh()->password);
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
        $report = app(TeacherTutorOverview::class)->report($course, 30);
        $this->assertGreaterThan(500, $report['questions']);
        $this->assertGreaterThan(100, $report['previousQuestions']);
        $this->assertSame(12, $report['failed']);
        $this->assertCount(5, $report['learners']);
        $this->assertCount(5, $report['popular']);
        $this->assertCount(2, $report['costs']);
        $this->assertGreaterThan(0, $report['costs']->sum('unpriced'));
        $this->assertGreaterThan(80, $report['helpful']);
        $url = '/teacher/ai-tutor?course_id='.$course->id;
        $this->actingAs($teacher)->get($url)->assertOk()->assertSee('dữ liệu mô phỏng')->assertSee('Trần Hoàng Nam');
        $this->get($url.'&section=errors')->assertOk()->assertSee('Không đủ credit')->assertSee('Nguồn kiến thức cần kiểm tra');
        $student = User::where('email', 'ai-tutor-demo-student-1@example.test')->firstOrFail();
        $response = $this->get($url.'&section=learners&learner_id='.$student->id)->assertOk()->assertSee('Chờ chấm');
        $this->assertGreaterThan(0, $response->viewData('learnerSupport')['weak']->count());
    }
    public function test_ielts_demo_has_distinct_course_users_and_conversations_and_is_repeatable(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->seed(AiTutorManagementDemoSeeder::class);
        $oldCourse = Course::where('slug', AiTutorManagementDemoSeeder::SLUG)->firstOrFail();
        $oldMessages = DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->pluck('id');
        $this->seed(AiTutorIeltsDemoSeeder::class);
        $course = Course::where('slug', AiTutorIeltsDemoSeeder::SLUG)->firstOrFail();
        $this->assertNotSame($oldCourse->id, $course->id);
        $this->assertCount(5, $course->lessons);
        $this->assertSame(8, $course->enrollments()->where('course_role', 'student')->count());
        $this->assertSame($oldMessages->count(), DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->count());
        $this->assertSame(0, DB::table('tutor_ai_conversations')->where('course_id', (string) $course->id)->whereIn('id', $oldMessages)->count());
        $messages = DB::table('tutor_ai_conversation_messages')->count();
        $users = User::count();
        $this->seed(AiTutorIeltsDemoSeeder::class);
        $this->assertSame($messages, DB::table('tutor_ai_conversation_messages')->count());
        $this->assertSame($users, User::count());
        $response = $this->actingAs($teacher)->get('/teacher/ai-tutor?course_id='.$course->id);
        $response->assertOk()->assertSee('IELTS 6.5')->assertSee('overview cho Writing Task 1')->assertSee('không phải band IELTS')->assertDontSee('Ma trận Eisenhower là gì');
        $student = User::where('email', 'ai-tutor-demo-ielts-student-1@example.test')->firstOrFail();
        $this->get('/teacher/ai-tutor?course_id='.$course->id.'&section=learners&learner_id='.$student->id)->assertOk()->assertSee('IELTS Writing Task 1')->assertSee('Chờ chấm');
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }
    public function test_toeic_four_skills_demo_coexists_with_two_skills_and_is_repeatable(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->seed(AiTutorToeicDemoSeeder::class);
        $oldCourse = Course::where('slug', AiTutorToeicDemoSeeder::SLUG)->firstOrFail();
        $oldConversations = DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->pluck('id');
        $this->seed(AiTutorToeicFourSkillsDemoSeeder::class);
        $course = Course::where('slug', AiTutorToeicFourSkillsDemoSeeder::SLUG)->firstOrFail();
        $this->assertNotSame($oldCourse->id, $course->id);
        $this->assertFalse($course->is_published);
        $this->assertSame(8, $course->enrollments()->where('course_role', 'student')->count());
        $this->assertCount(4, $course->lessons);
        foreach (['Listening', 'Reading', 'Speaking', 'Writing'] as $skill) {
            $this->assertTrue($course->lessons->contains(fn ($lesson) => str_contains($lesson->title, $skill)));
        }
        $this->assertSame($oldConversations->count(), DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->count());
        $this->assertSame(0, DB::table('tutor_ai_conversations')->where('course_id', (string) $course->id)->whereIn('id', $oldConversations)->count());
        $tables = ['users', 'courses', 'lessons', 'activities', 'enrollments', 'quiz_attempts', 'assignment_submissions', 'tutor_ai_conversations', 'tutor_ai_conversation_messages', 'tutor_ai_requests', 'tutor_ai_usage_records', 'tutor_ai_message_feedback'];
        $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
        $this->seed(AiTutorToeicFourSkillsDemoSeeder::class);
        foreach ($counts as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table);
        $url = '/teacher/ai-tutor?course_id='.$course->id;
        $this->actingAs($teacher)->get($url)->assertOk()->assertSee('TOEIC — Luyện 4 kỹ năng')->assertSee('không phải điểm TOEIC chính thức');
        $student = User::where('email', 'ai-tutor-demo-toeic-four-skills-student-1@example.test')->firstOrFail();
        $this->get($url.'&section=learners&learner_id='.$student->id)->assertOk()
            ->assertSee('TOEIC Listening')->assertSee('TOEIC Reading')->assertSee('TOEIC Speaking')->assertSee('TOEIC Writing');
        $this->assertSame(12, app(TeacherTutorOverview::class)->report($course, 30)['failed']);
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }

    public function test_toeic_demo_only_covers_listening_and_reading_and_can_be_seeded_again(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->seed(AiTutorToeicDemoSeeder::class);
        $course = Course::where('slug', AiTutorToeicDemoSeeder::SLUG)->firstOrFail();
        $this->assertCount(4, $course->lessons);
        foreach ($course->lessons as $lesson) {
            $this->assertTrue(str_contains($lesson->title, 'Listening') || str_contains($lesson->title, 'Reading'));
            $this->assertSame(2, $lesson->activities()->count());
        }
        $counts = [User::count(), DB::table('tutor_ai_conversations')->count(), DB::table('tutor_ai_usage_records')->count()];
        $this->seed(AiTutorToeicDemoSeeder::class);
        $this->assertSame($counts, [User::count(), DB::table('tutor_ai_conversations')->count(), DB::table('tutor_ai_usage_records')->count()]);
        $url = '/teacher/ai-tutor?course_id='.$course->id;
        $this->actingAs($teacher)->get($url)->assertOk()->assertSee('TOEIC 750+')->assertSee('không phải điểm TOEIC chính thức');
        $student = User::where('email', 'ai-tutor-demo-toeic-student-1@example.test')->firstOrFail();
        $this->get($url.'&section=learners&learner_id='.$student->id)->assertOk()->assertSee('TOEIC Listening')->assertSee('TOEIC Reading')->assertDontSee('IELTS Writing');
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }
}
