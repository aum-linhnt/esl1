<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Assessment\AssessmentService;
use App\Models\QuestionBank;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakAndLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_earns_xp_and_increments_streak_when_grading_exam(): void
    {
        $user = User::where('username', 'tuanlinh')->first();
        $initialXp = $user->xp;

        $question = QuestionBank::first();
        $service = app(AssessmentService::class);

        $result = $service->gradeSubmission(
            $user->id,
            null,
            [$question->id => $question->correct_answer],
            'test_streak_exam'
        );

        $user->refresh();
        $this->assertGreaterThan($initialXp, $user->xp);
        $this->assertGreaterThanOrEqual(1, $user->streak_count);
        $this->assertNotNull($user->last_active_date);
        $this->assertEquals(50, $result['xp_earned']); // 100% accuracy = 50 XP
    }

    public function test_daily_study_maintains_and_increments_streak(): void
    {
        $user = User::where('username', 'tuanlinh')->first();
        $user->streak_count = 3;
        $user->last_active_date = Carbon::yesterday();
        $user->save();

        $info = $user->recordDailyStudy();

        $this->assertEquals(4, $info['streak_count']);
        $this->assertTrue($info['streak_incremented']);
        $this->assertGreaterThan(0, $info['bonus_xp']);
    }

    public function test_learner_can_view_xp_leaderboard(): void
    {
        $user = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($user)->get('/leaderboard');

        $response->assertStatus(200);
        $response->assertSee('Bảng Xếp Hạng Điểm XP');
        $response->assertSee('ESL Champions League');
        $response->assertSee('XP');
        $response->assertSee('Streak');
    }
}
