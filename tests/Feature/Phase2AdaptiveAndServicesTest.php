<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use App\Models\QuestionBank;
use App\Models\AdaptiveTestSession;
use App\Services\Storage\ActivityTrackerService;
use App\Services\Assessment\AssessmentService;
use App\Services\Assessment\AdaptiveTestingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2AdaptiveAndServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_activity_tracker_logs_step_telemetry(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $activity = Activity::first();
        $service = app(ActivityTrackerService::class);

        $log = $service->logStep($student->id, $activity->id, 30, [
            'completed' => true,
            'interactions' => 5,
        ]);

        $this->assertNotNull($log);
        $this->assertEquals($student->id, $log->user_id);
        $this->assertEquals(30, $log->duration_seconds);
        $this->assertEquals('completed', $log->status);
    }

    public function test_activity_tracker_check_unlock_condition(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $lesson1 = Lesson::where('order', 1)->first();
        $lesson2 = Lesson::where('order', 2)->first();
        $service = app(ActivityTrackerService::class);

        // Lesson 1 is always unlocked
        $this->assertTrue($service->checkUnlockCondition($student->id, $lesson1->id));

        // Lesson 2 is initially locked if no progress on Lesson 1
        $this->assertFalse($service->checkUnlockCondition($student->id, $lesson2->id));
    }

    public function test_assessment_service_grades_and_rewards_coins(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $initialCoins = $student->coins;
        $service = app(AssessmentService::class);

        $q1 = QuestionBank::where('difficulty', 'A1')->first();
        $q2 = QuestionBank::where('difficulty', 'A2')->first();

        $result = $service->gradeSubmission($student->id, null, [
            $q1->id => $q1->correct_answer,
            $q2->id => $q2->correct_answer,
        ]);

        $this->assertEquals(100.0, $result['accuracy_rate']);
        $this->assertTrue($result['is_passed']);
        $this->assertEquals(10, $result['coins_earned']); // 100% accuracy -> 10 coins
        $this->assertEquals($initialCoins + 10, $student->fresh()->coins);
    }

    public function test_adaptive_testing_branching_up_and_down(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $service = app(AdaptiveTestingService::class);

        $session = $service->initializeSession($student->id, 'A1');
        $this->assertEquals('A1', $session->current_difficulty);

        // 1. Correct answer 1
        $q1 = QuestionBank::where('difficulty', 'A1')->first();
        $res1 = $service->processAnswer($session->id, $q1->id, $q1->correct_answer);
        $this->assertTrue($res1['is_correct']);
        $this->assertFalse($res1['difficulty_changed']);

        // 2. Correct answer 2 -> Branch up to A2!
        $q2 = QuestionBank::where('difficulty', 'A1')->where('id', '!=', $q1->id)->first();
        $res2 = $service->processAnswer($session->id, $q2->id, $q2->correct_answer);
        $this->assertTrue($res2['is_correct']);
        $this->assertTrue($res2['difficulty_changed']);
        $this->assertEquals('A2', $res2['current_difficulty']);
        $this->assertEquals('up', $res2['difficulty_direction']);
    }

    public function test_adaptive_player_http_flow(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        // 1. Start adaptive session via POST
        $startResponse = $this->actingAs($student)->post('/practice/start-adaptive');
        $startResponse->assertRedirect();

        $session = AdaptiveTestSession::where('user_id', $student->id)->latest()->first();
        $this->assertNotNull($session);

        // 2. Access Player page
        $playerResponse = $this->actingAs($student)->get("/practice/player/{$session->id}");
        $playerResponse->assertStatus(200);
        $playerResponse->assertSee('Bài Thi Thích Ứng AI');

        // 3. API Next Question
        $apiNext = $this->actingAs($student)->postJson('/api/adaptive/next-question', [
            'session_id' => $session->id,
        ]);
        $apiNext->assertStatus(200);
        $apiNext->assertJson(['finished' => false]);
        $questionId = $apiNext->json('question.id');

        // 4. API Submit Answer
        $apiSubmit = $this->actingAs($student)->postJson('/api/adaptive/submit-answer', [
            'session_id' => $session->id,
            'question_id' => $questionId,
            'answer' => 'any_answer',
        ]);
        $apiSubmit->assertStatus(200);
        $this->assertArrayHasKey('is_correct', $apiSubmit->json());

        // 5. API Log Step Telemetry
        $activity = Activity::first();
        $apiLog = $this->actingAs($student)->postJson('/api/activity/log-step', [
            'activity_id' => $activity->id,
            'duration' => 20,
            'step_data' => ['progress' => 100],
        ]);
        $apiLog->assertStatus(200);
        $apiLog->assertJson(['success' => true]);
    }
}
