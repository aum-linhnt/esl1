<?php

namespace Tests\Feature;

use App\Models\{Course, User};
use App\Services\Learning\TeacherTutorOverview;
use Database\Seeders\AiTutorDemo\{AiTutorToeicFourSkillsDemoSeeder, AiTutorToeicLevelsDemoSeeder, AiTutorToeicTargetsDemoSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiTutorToeicLevelsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_levels_have_distinct_content_and_repeatable_data_without_affecting_existing_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->seed(AiTutorToeicFourSkillsDemoSeeder::class);
        $oldCourse = Course::where('slug', AiTutorToeicFourSkillsDemoSeeder::SLUG)->firstOrFail();
        $oldConversations = DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->pluck('id');
        $this->seed(AiTutorToeicLevelsDemoSeeder::class);
        $targets = [350, 500, 650, 800, 900];
        $swTargets = [80, 110, 140, 160, 180];
        $quizContents = [];
        foreach (AiTutorToeicLevelsDemoSeeder::SEEDERS as $i => $seeder) {
            $course = Course::where('slug', $seeder::SLUG)->firstOrFail();
            $this->assertFalse($course->is_published);
            $this->assertStringContainsString('L&R '.$targets[$i].'+', $course->title);
            $this->assertStringContainsString('Speaking '.$swTargets[$i].'+ / Writing '.$swTargets[$i].'+', $course->title);
            $this->assertCount(4, $course->lessons);
            $students = $course->enrollments()->where('course_role', 'student')->pluck('user_id');
            $this->assertCount(8, $students);
            $this->assertSame(0, $oldCourse->enrollments()->where('course_role', 'student')->whereIn('user_id', $students)->count());
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
            $this->actingAs($teacher)->get($url)->assertOk()->assertSee('không phải điểm TOEIC chính thức')->assertSee('Speaking &amp; Writing luyện riêng', false)
                ->assertSee('Speaking '.$swTargets[$i].'+ / Writing '.$swTargets[$i].'+');
            $this->get($url.'&section=learners&learner_id='.$students->first())->assertOk()
                ->assertSee('TOEIC Listening')->assertSee('TOEIC Reading')->assertSee('TOEIC Speaking')->assertSee('TOEIC Writing');
        }
        $this->assertCount(20, array_unique(array_map('json_encode', $quizContents)));
        $legacy = Course::where('slug', AiTutorToeicLevelsDemoSeeder::SEEDERS[0]::SLUG)->firstOrFail();
        $legacy->update(['title' => '[DEMO] TOEIC Starter — Mục tiêu L&R 350+ — 4 kỹ năng']);
        $before = DB::table('quiz_attempts')->get()->toJson();
        $conversations = DB::table('tutor_ai_conversation_messages')->get()->toJson();
        $this->seed(AiTutorToeicTargetsDemoSeeder::class);
        $this->assertStringContainsString('Speaking 80+ / Writing 80+', $legacy->fresh()->title);
        $this->assertSame($before, DB::table('quiz_attempts')->get()->toJson());
        $this->assertSame($conversations, DB::table('tutor_ai_conversation_messages')->get()->toJson());
        $tables = ['users', 'courses', 'lessons', 'activities', 'enrollments', 'quiz_attempts', 'assignment_submissions', 'tutor_ai_conversations', 'tutor_ai_conversation_messages', 'tutor_ai_requests', 'tutor_ai_usage_records', 'tutor_ai_message_feedback'];
        $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
        $this->seed(AiTutorToeicLevelsDemoSeeder::class);
        foreach ($counts as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table);
        $this->assertSame($oldConversations->count(), DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->count());
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }
}
