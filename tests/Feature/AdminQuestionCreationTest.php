<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QuestionBank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuestionCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_multiple_select_question(): void
    {
        $admin = User::where('username', 'admin')->first();

        $res = $this->actingAs($admin)->post('/admin/questions', [
            'skill' => 'vocabulary',
            'difficulty' => 'B1',
            'question_type' => 'multiple_select',
            'question_text' => 'Chọn 2 từ đồng nghĩa với "Enormous":',
            'options' => "Huge\nGigantic\nTiny\nSmall",
            'correct_answers_multi' => "Huge\nGigantic",
            'explanation' => 'Huge và Gigantic đều có nghĩa là khổng lồ.',
        ]);

        $res->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('question_banks', [
            'question_type' => 'multiple_select',
            'skill' => 'vocabulary',
            'question_text' => 'Chọn 2 từ đồng nghĩa với "Enormous":',
        ]);
    }

    public function test_admin_can_create_matching_pairs_question(): void
    {
        $admin = User::where('username', 'admin')->first();

        $res = $this->actingAs($admin)->post('/admin/questions', [
            'skill' => 'vocabulary',
            'difficulty' => 'A2',
            'question_type' => 'matching',
            'question_text' => 'Nối cặp từ trái nghĩa:',
            'matching_left' => "Hot\nFast\nHigh",
            'matching_right' => "Cold\nSlow\nLow",
            'explanation' => 'Các cặp từ trái nghĩa cơ bản.',
        ]);

        $res->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('question_banks', [
            'question_type' => 'matching',
            'question_text' => 'Nối cặp từ trái nghĩa:',
        ]);
    }

    public function test_admin_can_create_speech_pronunciation_question(): void
    {
        $admin = User::where('username', 'admin')->first();

        $res = $this->actingAs($admin)->post('/admin/questions', [
            'skill' => 'listening',
            'difficulty' => 'A1',
            'question_type' => 'pronunciation_speech',
            'question_text' => 'Luyện phát âm câu sau:',
            'correct_answer' => 'How are you today',
            'explanation' => 'Phát âm rõ ràng các âm tiết.',
        ]);

        $res->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('question_banks', [
            'question_type' => 'pronunciation_speech',
            'correct_answer' => 'How are you today',
        ]);
    }
}
