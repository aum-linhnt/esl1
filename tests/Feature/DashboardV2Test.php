<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerSkill;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_v2_requires_authentication_and_renders_empty_learning_state(): void
    {
        $this->get('/dashboard-v2')->assertRedirect('/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard-v2')->assertOk()
            ->assertViewIs('dashboard-v2')->assertSee('Chưa đánh giá')
            ->assertSee('Dashboard cũ')->assertSee('Khám phá khóa học');
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertViewIs('dashboard');
    }

    public function test_v2_uses_only_current_learners_data_and_visible_unlocked_lessons(): void
    {
        $user = User::factory()->create(['current_level' => 'B1', 'streak_count' => 3, 'last_active_date' => today()]);
        $other = User::factory()->create();
        LearnerSkill::create(['user_id' => $user->id, 'skill_type' => 'reading', 'mastery_score' => 58]);
        LearnerSkill::create(['user_id' => $other->id, 'skill_type' => 'reading', 'mastery_score' => 99]);
        $course = Course::create(['title' => 'English B1', 'slug' => 'english-b1', 'level' => 'B1', 'is_published' => true, 'created_by' => $other->id]);
        $lesson = $course->lessons()->create(['title' => 'Visible lesson', 'order' => 1, 'is_visible' => true]);
        $user->enrollments()->create(['course_id' => $course->id, 'status' => 'active', 'progress_percentage' => 64]);
        $activity = $lesson->activities()->create(['title' => 'Practice', 'type' => 'quiz']);
        UserActivityLog::create(['activity_id' => $activity->id, 'user_id' => $user->id, 'started_at' => now()->subMinutes(10), 'duration_seconds' => 600]);
        UserActivityLog::create(['activity_id' => $activity->id, 'user_id' => $other->id, 'started_at' => now()->subMinutes(10), 'duration_seconds' => 3600]);
        $response = $this->actingAs($user)->get('/dashboard-v2');
        $response->assertOk()->assertViewHas('nextLesson', fn ($next) => $next->id === $lesson->id)
            ->assertViewHas('skills', fn ($skills) => $skills->count() === 1 && $skills['reading']->mastery_score === 58)
            ->assertViewHas('studyDays', fn ($days) => $days->sum('minutes') === 10)
            ->assertViewHas('streak', 3)->assertSee('64%');
        $lesson->update(['is_visible' => false]);
        $this->get('/dashboard-v2')->assertOk()->assertViewHas('nextLesson', null);
    }
}
