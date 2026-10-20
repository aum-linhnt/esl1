<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\Learning\StudyTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\Entitlements;
use Tests\TestCase;

class StudyTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_owned_idempotent_and_bounded_by_server_elapsed_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-06 10:00:00'));
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->get('/dashboard-v2/study-progress')->assertRedirect('/login');
        $id = $this->actingAs($user)->postJson('/dashboard-v2/study-sessions', ['source' => 'speaking'])->assertCreated()->json('id');
        $this->travel(15)->seconds();
        $this->patchJson('/dashboard-v2/study-sessions/'.$id, ['active_seconds' => 15])->assertOk()->assertJson(['recorded_seconds' => 15]);
        $this->patchJson('/dashboard-v2/study-sessions/'.$id, ['active_seconds' => 15])->assertOk()->assertJson(['recorded_seconds' => 0]);
        $this->patchJson('/dashboard-v2/study-sessions/'.$id, ['active_seconds' => 10])->assertOk()->assertJson(['recorded_seconds' => 0]);
        $this->actingAs($other)->patchJson('/dashboard-v2/study-sessions/'.$id, ['active_seconds' => 20])->assertNotFound();
        $this->travel(120)->seconds();
        $this->actingAs($user)->patchJson('/dashboard-v2/study-sessions/'.$id, ['active_seconds' => 1000])->assertOk()->assertJson(['recorded_seconds' => 60]);
        $this->assertSame(75, app(StudyTime::class)->week($user)->sum('seconds'));
        $this->assertSame(0, app(StudyTime::class)->week($other)->sum('seconds'));
        $items = app(\App\Services\Learning\LearnerOverview::class)->recommendations($user, collect(), null);
        $this->assertNotContains('return_to_study', array_column($items, 'rule'));
        $this->get('/dashboard-v2/study-progress')->assertOk()->assertSee('1p 15s');
        $this->travelBack();
    }

    public function test_tracking_respects_lesson_enrollment_visibility_and_revocation(): void
    {
        $user = User::factory()->create();
        $course = Course::create(['title' => 'English', 'slug' => 'english', 'is_published' => true, 'created_by' => $user->id]);
        $lesson = $course->lessons()->create(['title' => 'First lesson', 'is_visible' => true, 'order' => 1]);
        $activity = $lesson->activities()->create(['title' => 'Quiz', 'type' => 'quiz', 'is_visible' => true]);
        $this->actingAs($user)->postJson('/dashboard-v2/study-sessions', ['source' => 'lesson', 'context_id' => $lesson->id])->assertForbidden();
        $enrollment = $user->enrollments()->create(['course_id' => $course->id, 'status' => 'active']);
        $id = $this->postJson('/dashboard-v2/study-sessions', ['source' => 'activity', 'context_id' => $activity->id])->assertCreated()->json('id');
        $enrollment->update(['status' => 'suspended']);
        $this->patchJson('/dashboard-v2/study-sessions/'.$id, ['active_seconds' => 15])->assertForbidden();
        $enrollment->update(['status' => 'active']);
        $activity->update(['is_visible' => false]);
        $this->postJson('/dashboard-v2/study-sessions', ['source' => 'activity', 'context_id' => $activity->id])->assertForbidden();
        $this->postJson('/dashboard-v2/study-sessions', ['source' => 'lesson'])->assertUnprocessable();
    }

    public function test_overlap_is_counted_once_and_midnight_is_split_by_local_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-06 10:00:00', 'Asia/Ho_Chi_Minh'));
        $user = User::factory()->create();
        foreach ([['speaking', '2026-10-05 23:59:30', '2026-10-06 00:00:30'],
                  ['writing', '2026-10-06 00:00:00', '2026-10-06 00:01:00'],
                  ['lesson', '2026-10-06 00:00:00', '2026-10-06 00:01:00']] as [$source, $start, $end]) {
            $id = (string) Str::uuid();
            DB::table('learner_study_sessions')->insert(['id' => $id, 'user_id' => $user->id, 'source' => $source, 'started_at' => $start, 'last_reported_at' => $end]);
            DB::table('learner_study_intervals')->insert(['session_id' => $id, 'started_at' => $start, 'ended_at' => $end]);
        }
        $days = app(StudyTime::class)->week($user);
        $this->assertSame(90, $days->sum('seconds'));
        $this->assertSame(30, $days->firstWhere('iso_date', '2026-10-05')['seconds']);
        $this->assertSame(60, $days->firstWhere('iso_date', '2026-10-06')['seconds']);
        $this->assertSame(60, $days->last()['sources']['writing']);
        $this->assertSame(0, $days->last()['sources']['lesson']);
        $this->travelBack();
    }

    public function test_vietnam_day_boundaries_when_storage_timezone_is_utc(): void
    {
        $originalTimezone = date_default_timezone_get();
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
        try {
            $this->travelTo(Carbon::parse('2026-10-06 10:00:00', 'Asia/Ho_Chi_Minh'));
            $user = User::factory()->create();
            $id = (string) Str::uuid();
            DB::table('learner_study_sessions')->insert(['id' => $id, 'user_id' => $user->id, 'source' => 'speaking',
                'started_at' => '2026-10-05 16:59:30', 'last_reported_at' => '2026-10-05 17:00:30']);
            DB::table('learner_study_intervals')->insert(['session_id' => $id,
                'started_at' => '2026-10-05 16:59:30', 'ended_at' => '2026-10-05 17:00:30']);
            $days = app(StudyTime::class)->week($user);
            $this->assertSame(30, $days->firstWhere('iso_date', '2026-10-05')['seconds']);
            $this->assertSame(30, $days->firstWhere('iso_date', '2026-10-06')['seconds']);
        } finally {
            $this->travelBack();
            date_default_timezone_set($originalTimezone);
        }
    }

    public function test_legacy_logs_and_new_intervals_merge_without_double_counting(): void
    {
        $this->travelTo(Carbon::parse('2026-10-06 10:00:00'));
        $user = User::factory()->create();
        $course = Course::create(['title' => 'English', 'slug' => 'english', 'is_published' => true, 'created_by' => $user->id]);
        $lesson = $course->lessons()->create(['title' => 'Lesson', 'is_visible' => true, 'order' => 1]);
        $activity = $lesson->activities()->create(['title' => 'Video', 'type' => 'video']);
        UserActivityLog::create(['user_id' => $user->id, 'activity_id' => $activity->id,
            'started_at' => now()->subMinutes(10), 'duration_seconds' => 600]);
        $id = (string) Str::uuid();
        DB::table('learner_study_sessions')->insert(['id' => $id, 'user_id' => $user->id, 'source' => 'speaking',
            'started_at' => now()->subMinutes(5), 'last_reported_at' => now()]);
        DB::table('learner_study_intervals')->insert(['session_id' => $id, 'started_at' => now()->subMinutes(5), 'ended_at' => now()]);
        $day = app(StudyTime::class)->week($user)->last();
        $this->assertSame(600, $day['seconds']);
        $this->assertSame(300, $day['sources']['lesson']);
        $this->assertSame(300, $day['sources']['speaking']);
        $this->travelBack();
    }

    public function test_writing_requires_module_access_and_session_expires(): void
    {
        $user = User::factory()->create();
        config(['ai-tutor.enabled' => true]);
        $this->app->instance(Entitlements::class, new class implements Entitlements { public function allows(string $module): bool { return false; } });
        $this->actingAs($user)->postJson('/dashboard-v2/study-sessions', ['source' => 'writing'])->assertForbidden();
        $this->app->instance(Entitlements::class, new class implements Entitlements { public function allows(string $module): bool { return true; } });
        $this->postJson('/dashboard-v2/study-sessions', ['source' => 'writing'])->assertCreated();
        $id = $this->postJson('/dashboard-v2/study-sessions', ['source' => 'speaking'])->assertCreated()->json('id');
        $this->travel(25)->hours();
        $this->patchJson('/dashboard-v2/study-sessions/'.$id, ['active_seconds' => 15])->assertGone();
        $this->travelBack();
    }
}
