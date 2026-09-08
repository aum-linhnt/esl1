<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Services\AI\AiTutorService;
use App\Services\AI\AiWritingService;
use App\Services\AI\AiSpeakingService;
use App\Services\AI\AiExerciseGeneratorService;
use App\Services\LMS\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3AiAndGamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_ai_tutor_service_returns_tutor_response(): void
    {
        $service = app(AiTutorService::class);
        $response = $service->askTutor('What is the difference between present simple and continuous?', 'Grammar Lesson 2', 'A2');

        $this->assertNotEmpty($response);
        $this->assertIsString($response);
    }

    public function test_ai_tutor_chat_api_endpoint(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->postJson('/api/ai/chat', [
            'message' => 'Làm thế nào để phân biệt How much và How many?',
            'context' => 'Lesson 3: Shopping',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'reply', 'timestamp', 'remaining', 'max']);
        $this->assertEquals(9, $response->json('remaining'));
        $this->assertEquals(10, $response->json('max'));
    }

    public function test_ai_tutor_chat_enforces_max_10_questions_daily(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        // Simulate 9 more questions (total 10)
        for ($i = 0; $i < 10; $i++) {
            $res = $this->actingAs($student)->postJson('/api/ai/chat', [
                'message' => "Câu hỏi số " . ($i + 1),
            ]);
            $res->assertStatus(200);
        }

        // 11th question should exceed quota
        $overLimitRes = $this->actingAs($student)->postJson('/api/ai/chat', [
            'message' => 'Câu hỏi vượt hạn mức 10 câu',
        ]);

        $overLimitRes->assertStatus(200);
        $this->assertFalse($overLimitRes->json('success'));
        $this->assertTrue($overLimitRes->json('exceeded'));
        $this->assertEquals(0, $overLimitRes->json('remaining'));
        $this->assertStringContainsString('10/10', $overLimitRes->json('reply'));
    }

    public function test_ai_writing_service_and_endpoint(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $initialCoins = $student->coins;

        // View page
        $pageRes = $this->withoutExceptionHandling()->actingAs($student)->get(route('ai.writing.index'));
        $pageRes->assertStatus(200);
        $pageRes->assertSee('AI Writing Assistant');

        // Analyze essay endpoint
        $analyzeRes = $this->actingAs($student)->postJson(route('api.ai.writing.analyze'), [
            'essay' => 'I like play football very much. Yesterday I go to the stadium with my friends.',
            'topic' => 'Hobbies',
            'level' => 'B1',
        ]);

        $analyzeRes->assertStatus(200);
        $analyzeRes->assertJsonStructure([
            'success',
            'data' => ['overall_score', 'cefr_level', 'grammar_errors', 'vocabulary_improvements', 'model_essay'],
        ]);

        // Student earned coins for writing practice
        $this->assertEquals($initialCoins + 3, $student->fresh()->coins);
    }

    public function test_ai_speaking_service_and_endpoint(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        // View speaking page
        $pageRes = $this->actingAs($student)->get(route('ai.speaking.index'));
        $pageRes->assertStatus(200);
        $pageRes->assertSee('AI Speaking');

        // Evaluate pronunciation endpoint
        $evalRes = $this->actingAs($student)->postJson(route('api.ai.speaking.evaluate'), [
            'recognized_text' => 'Good morning, nice to meet you here today.',
            'reference_sentence' => 'Good morning, nice to meet you here today.',
        ]);

        $evalRes->assertStatus(200);
        $evalRes->assertJsonStructure([
            'success',
            'data' => ['pronunciation_score', 'fluency_score', 'mispronounced_words', 'feedback'],
        ]);
    }

    public function test_teacher_ai_generator_flow(): void
    {
        $admin = User::where('username', 'admin')->first();
        $initialCount = QuestionBank::count();

        // 1. Generate questions
        $genRes = $this->actingAs($admin)->postJson('/teacher/ai-generator/generate', [
            'topic' => 'Travel & Tourism',
            'difficulty' => 'B1',
            'count' => 3,
        ]);

        $genRes->assertStatus(200);
        $questions = $genRes->json('questions');
        $this->assertNotEmpty($questions);

        // 2. Save generated questions into QuestionBank
        $saveRes = $this->actingAs($admin)->postJson('/teacher/ai-generator/save', [
            'questions' => $questions,
        ]);

        $saveRes->assertStatus(200);
        $this->assertGreaterThan($initialCount, QuestionBank::count());
    }

    public function test_gamification_streak_and_badge_unlocks(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $service = app(GamificationService::class);

        $streak = $service->updateDailyStreak($student->id);
        $this->assertGreaterThanOrEqual(1, $streak);

        $badges = $service->checkAndAwardBadges($student->id);
        $this->assertIsArray($badges);
    }

    public function test_certificate_generation_and_view(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $course = Course::first();

        $response = $this->actingAs($student)->get("/certificates/{$course->id}");

        $response->assertStatus(200);
        $response->assertSee('CHỨNG NHẬN HOÀN THÀNH');
        $response->assertSee($student->name);
        $response->assertSee($course->title);
    }
}
