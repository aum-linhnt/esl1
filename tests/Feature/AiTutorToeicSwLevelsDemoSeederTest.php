<?php

namespace Tests\Feature;

use App\Models\{Course, User};
use App\Services\Learning\TeacherTutorOverview;
use Database\Seeders\AiTutorDemo\{AiTutorToeicFourSkillsDemoSeeder, AiTutorToeicSwLevelsDemoSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiTutorToeicSwLevelsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sw_levels_are_separate_repeatable_and_show_individual_targets_without_lr_goals(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->seed(AiTutorToeicFourSkillsDemoSeeder::class);
        $oldCourse = Course::where('slug', AiTutorToeicFourSkillsDemoSeeder::SLUG)->firstOrFail();
        $oldConversations = DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->pluck('id');
        $oldStudents = $oldCourse->enrollments()->where('course_role', 'student')->pluck('user_id');
        $this->seed(AiTutorToeicSwLevelsDemoSeeder::class);
        $targets = [80, 110, 140, 160, 180];
        $quizContents = [];
        $seenStudents = $oldStudents;
        foreach (AiTutorToeicSwLevelsDemoSeeder::SEEDERS as $i => $seeder) {
            $course = Course::where('slug', $seeder::SLUG)->firstOrFail();
            $this->assertFalse($course->is_published);
            $this->assertStringContainsString('Speaking '.$targets[$i].'+ / Writing '.$targets[$i].'+', $course->title);
            $this->assertCount(4, $course->lessons);
            $this->assertSame(2, $course->lessons->filter(fn ($lesson) => str_contains($lesson->title, 'Speaking'))->count());
            $this->assertSame(2, $course->lessons->filter(fn ($lesson) => str_contains($lesson->title, 'Writing'))->count());
            $students = $course->enrollments()->where('course_role', 'student')->pluck('user_id');
            $this->assertCount(8, $students);
            $this->assertCount(0, $students->intersect($seenStudents));
            $seenStudents = $seenStudents->merge($students);
            foreach ($course->lessons as $lesson) {
                $this->assertStringNotContainsString('Listening', $lesson->title);
                $this->assertStringNotContainsString('Reading', $lesson->title);
                $this->assertSame(2, $lesson->activities()->count());
                $quizContents[] = $lesson->activities()->where('type', 'quiz')->firstOrFail()->content;
                if (str_contains($lesson->title, 'Speaking')) {
                    $this->assertStringContainsString('chưa có bản ghi âm', $lesson->activities()->where('type', 'assignment')->firstOrFail()->content['instructions']);
                }
            }
            $this->assertSame(0, DB::table('tutor_ai_conversations')->where('course_id', (string) $course->id)->whereIn('id', $oldConversations)->count());
            $report = app(TeacherTutorOverview::class)->report($course, 30);
            $this->assertGreaterThan(500, $report['questions']);
            $this->assertSame(12, $report['failed']);
            $url = '/teacher/ai-tutor?course_id='.$course->id;
            $this->actingAs($teacher)->get($url)->assertOk()->assertSee('không phải điểm TOEIC chính thức')
                ->assertSee('Mục tiêu Speaking và Writing được đặt riêng')->assertDontSee('Mục tiêu L&amp;R áp dụng', false);
            $this->get($url.'&section=learners&learner_id='.$students->first())->assertOk()
                ->assertSee('TOEIC Speaking')->assertSee('TOEIC Writing')->assertDontSee('TOEIC Listening —')->assertDontSee('TOEIC Reading —');
        }
        $this->assertCount(20, array_unique(array_map('json_encode', $quizContents)));
        $tables = ['users', 'courses', 'lessons', 'activities', 'enrollments', 'quiz_attempts', 'assignment_submissions', 'tutor_ai_conversations', 'tutor_ai_conversation_messages', 'tutor_ai_requests', 'tutor_ai_usage_records', 'tutor_ai_message_feedback'];
        $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
        $this->seed(AiTutorToeicSwLevelsDemoSeeder::class);
        foreach ($counts as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table);
        $this->assertSame($oldConversations->count(), DB::table('tutor_ai_conversations')->where('course_id', (string) $oldCourse->id)->count());
        $this->assertDatabaseCount('tutor_ai_credit_transactions', 0);
    }
}
