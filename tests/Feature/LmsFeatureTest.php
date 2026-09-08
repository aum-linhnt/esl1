<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QuestionBank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_student_can_access_dashboard_and_see_trial_expired(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Tài khoản học thử của bạn đã hết hạn');
    }

    public function test_student_can_access_courses_page(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->get('/courses');

        $response->assertStatus(200);
        $response->assertSee('English Basics');
    }

    public function test_student_can_access_practice_page(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->get('/practice');

        $response->assertStatus(200);
        $response->assertSee('Chọn Chế Độ Luyện Đề');
    }

    public function test_student_can_check_practice_answer_and_earn_coins(): void
    {
        $student = User::where('username', 'tuanlinh')->first();
        $initialCoins = $student->coins;

        $question = QuestionBank::first();

        $response = $this->actingAs($student)->postJson('/practice/check', [
            'question_id' => $question->id,
            'answer' => $question->correct_answer,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'correct' => true,
        ]);
        $this->assertGreaterThanOrEqual($initialCoins, $student->fresh()->coins);
    }

    public function test_student_can_access_progress_page(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->get('/progress');

        $response->assertStatus(200);
        $response->assertSee('Tiến trình');
    }

    public function test_student_can_access_marketplace_page(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->get('/marketplace');

        $response->assertStatus(200);
        $response->assertSee('Marketplace');
    }

    public function test_admin_can_access_admin_users(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Quản lý người dùng');
    }

    public function test_admin_can_access_admin_courses(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/courses');

        $response->assertStatus(200);
        $response->assertSee('Quản lý khóa học');
    }

    public function test_admin_can_access_admin_questions(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/questions');

        $response->assertStatus(200);
        $response->assertSee('Ngân Hàng Câu Hỏi');
    }
}
