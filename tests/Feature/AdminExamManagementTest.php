<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ExamSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_exams_list(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/exams');

        $response->assertStatus(200);
        $response->assertSee('Quản Lý Đề Thi');
        $response->assertSee('Tạo Đề Thi Mới');
        $response->assertSee('Đề Thi Thử Toàn Diện 4 Kỹ Năng A1');
    }

    public function test_admin_can_create_new_4_skill_mock_exam(): void
    {
        $admin = User::where('username', 'admin')->first();

        $createRes = $this->actingAs($admin)->get('/admin/exams/create');
        $createRes->assertStatus(200);
        $createRes->assertSee('Cấu hình Thông tin Đề thi');

        $postRes = $this->actingAs($admin)->post('/admin/exams', [
            'title' => 'Đề Thi Thử Đột Phá B2 - 4 Kỹ Năng 2026',
            'key' => 'mock_test_b2_2026',
            'skill' => 'full_mock',
            'difficulty' => 'B1',
            'duration_minutes' => 30,
            'reward_coins' => 50,
            'description' => 'Đề thi thử toàn diện mô phỏng 100% đề thi quốc tế.',
            'is_published' => 1,
            'sections' => [
                'vocabulary' => 3,
                'grammar' => 3,
                'reading' => 2,
                'listening' => 2,
            ],
        ]);

        $postRes->assertRedirect(route('admin.exams.index'));
        $this->assertDatabaseHas('exam_sets', [
            'key' => 'mock_test_b2_2026',
            'title' => 'Đề Thi Thử Đột Phá B2 - 4 Kỹ Năng 2026',
            'question_count' => 10,
        ]);
    }

    public function test_admin_can_edit_and_update_exam(): void
    {
        $admin = User::where('username', 'admin')->first();
        $exam = ExamSet::where('key', 'vocab_test_01')->first();

        $editRes = $this->actingAs($admin)->get('/admin/exams/' . $exam->id . '/edit');
        $editRes->assertStatus(200);
        $editRes->assertSee('Chỉnh Sửa Đề Thi');

        $putRes = $this->actingAs($admin)->put('/admin/exams/' . $exam->id, [
            'title' => 'Đề 01: Từ vựng Cơ bản & Chào hỏi Nâng Cao',
            'key' => $exam->key,
            'skill' => 'vocabulary',
            'difficulty' => 'A1',
            'duration_minutes' => 7,
            'reward_coins' => 12,
            'question_count' => 3,
            'description' => 'Mô tả cập nhật mới.',
            'is_published' => 1,
        ]);

        $putRes->assertRedirect(route('admin.exams.index'));
        $this->assertDatabaseHas('exam_sets', [
            'id' => $exam->id,
            'title' => 'Đề 01: Từ vựng Cơ bản & Chào hỏi Nâng Cao',
            'duration_minutes' => 7,
            'reward_coins' => 12,
        ]);
    }

    public function test_admin_can_toggle_publish_and_delete_exam(): void
    {
        $admin = User::where('username', 'admin')->first();
        $exam = ExamSet::where('key', 'vocab_test_02')->first();

        // 1. Toggle Publish
        $this->actingAs($admin)->post('/admin/exams/' . $exam->id . '/toggle-publish');
        $this->assertDatabaseHas('exam_sets', [
            'id' => $exam->id,
            'is_published' => false,
        ]);

        // 2. Delete Exam
        $delRes = $this->actingAs($admin)->delete('/admin/exams/' . $exam->id);
        $delRes->assertRedirect(route('admin.exams.index'));
        $this->assertDatabaseMissing('exam_sets', [
            'id' => $exam->id,
        ]);
    }
}
