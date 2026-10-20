<?php

namespace Tests\Feature;

use App\Models\LearningGoal;
use App\Models\LearnerSkill;
use App\Models\User;
use App\Models\Course;
use App\Services\Learning\LearnerOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningGoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_form_and_update_require_login(): void
    {
        $this->get('/dashboard-v2/goals')->assertRedirect('/login');
        $this->put('/dashboard-v2/goals', ['framework' => 'cefr', 'target' => 'B1'])->assertRedirect('/login');
    }

    public function test_saving_and_switching_goal_preserves_ownership_and_clears_optional_date(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $date = today()->addMonths(3)->format('Y-m-d');
        $this->actingAs($user)->get('/dashboard-v2/goals')->assertOk()->assertSee('Mục tiêu học tập');
        $this->get('/dashboard-v2/goals?framework=toeic')->assertOk()->assertSee('750+');
        $this->put('/dashboard-v2/goals', ['framework' => 'cefr', 'target' => 'B2', 'target_date' => $date, 'user_id' => $other->id])
            ->assertRedirect('/dashboard-v2')->assertSessionHas('learning-goal-saved');
        $this->assertDatabaseHas('learner_learning_goals', ['user_id' => $user->id, 'framework' => 'cefr', 'target' => 'B2']);
        $this->assertSame($date, LearningGoal::where('user_id', $user->id)->first()->target_date->toDateString());
        $this->assertDatabaseMissing('learner_learning_goals', ['user_id' => $other->id]);
        $this->assertSame('B2', $user->fresh()->target_level);
        $this->put('/dashboard-v2/goals', ['framework' => 'ielts', 'target' => '6.5'])->assertRedirect('/dashboard-v2');
        $this->assertDatabaseCount('learner_learning_goals', 1);
        $this->assertDatabaseHas('learner_learning_goals', ['user_id' => $user->id, 'framework' => 'ielts', 'target' => '6.5', 'target_date' => null]);
        $this->get('/dashboard-v2')->assertOk()->assertSee('Chinh phục IELTS Band 6.5')->assertSee('Mục tiêu đang chọn');
        $this->actingAs($other)->get('/dashboard-v2')->assertOk()->assertDontSee('Chinh phục IELTS Band 6.5');
    }

    public function test_invalid_target_dates_and_frameworks_do_not_modify_goal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put('/dashboard-v2/goals', ['framework' => 'toeic', 'target' => '6.5'])
            ->assertSessionHasErrorsIn('learningGoal', ['target']);
        $this->put('/dashboard-v2/goals', ['framework' => 'ielts', 'target' => '99'])
            ->assertSessionHasErrorsIn('learningGoal', ['target']);
        $this->put('/dashboard-v2/goals', ['framework' => ['invalid'], 'target' => 'B2'])
            ->assertSessionHasErrorsIn('learningGoal', ['framework']);
        $this->put('/dashboard-v2/goals', ['framework' => 'cefr', 'target' => 'B1', 'target_date' => today()->subDay()->format('Y-m-d')])
            ->assertSessionHasErrorsIn('learningGoal', ['target_date']);
        $this->assertDatabaseCount('learner_learning_goals', 0);
        $this->get('/dashboard-v2')->assertOk();
    }

    public function test_goal_is_present_among_recommendations_and_uses_supported_writing_preset(): void
    {
        $user = User::factory()->create(['last_active_date' => today()]);
        LearnerSkill::create(['user_id' => $user->id, 'skill_type' => 'reading', 'mastery_score' => 45]);
        $goal = LearningGoal::create(['user_id' => $user->id, 'framework' => 'ielts', 'target' => '8.0']);
        $overview = app(LearnerOverview::class);
        $items = collect($overview->recommendations($user, $overview->skills($user), null, $goal));
        $recommendation = $items->firstWhere('rule', 'goal_ielts');
        $this->assertNotNull($recommendation);
        $this->assertStringContainsString('target=7.0%2B', $recommendation['url']);
        $this->assertStringContainsString('IELTS Band 8.0', $recommendation['reason']);
        $this->assertLessThanOrEqual(3, $items->count());
        $this->assertSame($items->count(), $items->unique('url')->count());
        $goal->update(['framework' => 'toeic', 'target' => '750']);
        $items = collect($overview->recommendations($user, $overview->skills($user), null, $goal));
        $this->assertSame(route('practice.index', ['skill' => 'listening']), $items->firstWhere('rule', 'goal_toeic')['url']);
    }

    public function test_advanced_cefr_goal_links_to_courses_of_correct_level(): void
    {
        $user = User::factory()->create();
        foreach (['B1', 'C1'] as $level) Course::create(['title' => 'Course '.$level, 'slug' => 'course-'.strtolower($level), 'level' => $level, 'is_published' => true, 'created_by' => $user->id]);
        $goal = LearningGoal::create(['user_id' => $user->id, 'framework' => 'cefr', 'target' => 'C1']);
        $overview = app(LearnerOverview::class);
        $items = collect($overview->recommendations($user, collect(), null, $goal));
        $this->assertSame(route('courses.index', ['level' => 'C1']), $items->firstWhere('rule', 'goal_cefr')['url']);
        $this->actingAs($user)->get('/courses?level=C1')->assertOk()->assertSee('Course C1')->assertDontSee('Course B1');
    }
}
