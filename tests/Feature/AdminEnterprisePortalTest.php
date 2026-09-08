<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Models\AssessmentSubmission;
use App\Models\UserBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEnterprisePortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_access_admin_dashboard_with_kpis(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Trung tâm Quản trị LMS');
        $response->assertSee('Phân bổ Trình độ Học viên');
    }

    public function test_admin_can_access_submissions_and_view_details(): void
    {
        $admin = User::where('username', 'admin')->first();
        $student = User::where('username', 'tuanlinh')->first();

        // Create sample submission
        $sub = AssessmentSubmission::create([
            'user_id' => $student->id,
            'test_type' => 'adaptive_diagnostic',
            'total_score' => 8,
            'max_score' => 10,
            'accuracy_rate' => 80.0,
            'is_passed' => true,
            'answers_payload' => ['q1' => 'correct'],
        ]);

        // Submissions index
        $indexRes = $this->actingAs($admin)->get('/admin/submissions');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Lịch sử Bài nộp');

        // Submissions detail
        $showRes = $this->actingAs($admin)->get("/admin/submissions/{$sub->id}");
        $showRes->assertStatus(200);
        $showRes->assertSee("Chi tiết Bài nộp #{$sub->id}");
    }

    public function test_admin_can_access_gamification_and_award_badge(): void
    {
        $admin = User::where('username', 'admin')->first();
        $student = User::where('username', 'tuanlinh')->first();

        // Gamification index
        $indexRes = $this->actingAs($admin)->get('/admin/gamification');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Gamification, Streak');

        // Award badge
        $awardRes = $this->actingAs($admin)->post('/admin/gamification/award', [
            'user_id' => $student->id,
            'badge_key' => 'ai_scholar',
            'badge_name' => 'Học Giả AI',
            'badge_icon' => '🤖',
            'description' => 'Hoàn thành xuất sắc bài tập AI',
        ]);

        $awardRes->assertRedirect(route('admin.gamification.index'));
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $student->id,
            'badge_key' => 'ai_scholar',
        ]);
    }

    public function test_admin_can_access_settings_and_clear_cache(): void
    {
        $admin = User::where('username', 'admin')->first();

        $indexRes = $this->actingAs($admin)->get('/admin/settings');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Cấu hình Hệ thống LMS');

        $clearRes = $this->actingAs($admin)->post('/admin/settings/clear-cache');
        $clearRes->assertRedirect(route('admin.settings.index'));
    }
}
