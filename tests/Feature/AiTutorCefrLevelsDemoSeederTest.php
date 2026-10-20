<?php

namespace Tests\Feature;

use App\Models\{Course, User};
use App\Services\Learning\TeacherTutorOverview;
use Database\Seeders\AiTutorDemo\{AiTutorIeltsDemoSeeder, AiTutorCefrLevelsDemoSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiTutorCefrLevelsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_six_levels_have_distinct_content_and_repeatable_data_without_affecting_existing_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->seed(AiTutorIeltsDemoSeeder::class);
        $oldCourse = Course::where('slug', AiTutorIeltsDemoSeeder::SLUG)->firstOrFail();
        $oldConversations = DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->pluck('id');
        $this->seed(AiTutorCefrLevelsDemoSeeder::class);
        $targets = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $seenStudents = $oldCourse->enrollments()->where('course_role', 'student')->pluck('user_id');
        $quizContents = [];
        foreach (AiTutorCefrLevelsDemoSeeder::SEEDERS as $i => $seeder) {
            $course = Course::where('slug', $seeder::SLUG)->firstOrFail();
            $this->assertFalse($course->is_published);
            $this->assertStringContainsString('English '.$targets[$i], $course->title);
            $this->assertSame($targets[$i], $course->level);
            $this->assertCount(4, $course->lessons);
            $students = $course->enrollments()->where('course_role', 'student')->pluck('user_id');
            $this->assertCount(8, $students);
            $this->assertCount(0, $students->intersect($seenStudents));
            $seenStudents = $seenStudents->merge($students);
            foreach (['Listening', 'Reading', 'Speaking', 'Writing'] as $skill) {
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
            $this->actingAs($teacher)->get($url)->assertOk()->assertSee('không xác nhận trình độ CEFR thực tế')->assertDontSee('không phải band IELTS')->assertDontSee('Mục tiêu L&amp;R áp dụng', false);
            $this->get($url.'&section=learners&learner_id='.$students->first())->assertOk()
                ->assertSee('English '.$targets[$i].' Listening')->assertSee('English '.$targets[$i].' Reading')->assertSee('English '.$targets[$i].' Speaking')->assertSee('English '.$targets[$i].' Writing');
        }
        $this->assertCount(24, array_unique(array_map('json_encode', $quizContents)));
        $tables = ['users', 'courses', 'lessons', 'activities', 'enrollments', 'quiz_attempts', 'assignment_submissions', 'tutor_ai_conversations', 'tutor_ai_conversation_messages', 'tutor_ai_requests', 'tutor_ai_usage_records', 'tutor_ai_message_feedback'];
        $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
        $this->seed(AiTutorCefrLevelsDemoSeeder::class);
        foreach ($counts as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table);
        $this->assertSame($oldConversations->count(), DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->count());
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }
}
