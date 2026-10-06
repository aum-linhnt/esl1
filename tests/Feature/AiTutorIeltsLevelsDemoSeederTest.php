<?php

namespace Tests\Feature;

use App\Models\{Course, User};
use App\Services\Learning\TeacherTutorOverview;
use Database\Seeders\AiTutorDemo\{AiTutorIeltsDemoSeeder, AiTutorIeltsLevelsDemoSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiTutorIeltsLevelsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_levels_have_distinct_content_and_repeatable_data_without_affecting_existing_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->seed(AiTutorIeltsDemoSeeder::class);
        $oldCourse = Course::where('slug', AiTutorIeltsDemoSeeder::SLUG)->firstOrFail();
        $oldConversations = DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->pluck('id');
        $this->seed(AiTutorIeltsLevelsDemoSeeder::class);
        $targets = ['Foundation', '4.5', '5.5', '7.0', '7.5+'];
        $quizContents = [];
        foreach (AiTutorIeltsLevelsDemoSeeder::SEEDERS as $i => $seeder) {
            $course = Course::where('slug', $seeder::SLUG)->firstOrFail();
            $this->assertFalse($course->is_published);
            $this->assertStringContainsString('IELTS '.$targets[$i], $course->title);
            $this->assertCount(5, $course->lessons);
            $students = $course->enrollments()->where('course_role', 'student')->pluck('user_id');
            $this->assertCount(8, $students);
            $this->assertSame(0, $oldCourse->enrollments()->where('course_role', 'student')->whereIn('user_id', $students)->count());
            foreach (['Listening', 'Reading', 'Writing Task 1', 'Writing Task 2', 'Speaking'] as $skill) {
                $lesson = $course->lessons->first(fn ($lesson) => str_contains($lesson->title, $skill));
                $this->assertNotNull($lesson);
                $this->assertSame(2, $lesson->activities()->count());
                $quizContents[] = $lesson->activities()->where('type', 'quiz')->firstOrFail()->content;
            }
            $this->assertSame(0, DB::table('tutor_ai_conversations')->where('course_id', (string) $course->id)->whereIn('id', $oldConversations)->count());
            $report = app(TeacherTutorOverview::class)->report($course, 30);
            $this->assertGreaterThan(500, $report['questions']);
            $this->assertSame(12, $report['failed']);
            $url = '/teacher/ai-tutor?course_id='.$course->id;
            $this->actingAs($teacher)->get($url)->assertOk()->assertSee('không phải band IELTS');
            $this->get($url.'&section=learners&learner_id='.$students->first())->assertOk()
                ->assertSee('IELTS Listening')->assertSee('IELTS Reading')->assertSee('IELTS Speaking')->assertSee('IELTS Writing Task 1')->assertSee('IELTS Writing Task 2');
        }
        $this->assertCount(25, array_unique(array_map('json_encode', $quizContents)));
        $tables = ['users', 'courses', 'lessons', 'activities', 'enrollments', 'quiz_attempts', 'assignment_submissions', 'tutor_ai_conversations', 'tutor_ai_conversation_messages', 'tutor_ai_requests', 'tutor_ai_usage_records', 'tutor_ai_message_feedback'];
        $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
        $this->seed(AiTutorIeltsLevelsDemoSeeder::class);
        foreach ($counts as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table);
        $this->assertSame($oldConversations->count(), DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->count());
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }
}
