<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QuestionBank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FourSkillPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_view_4_skill_practice_hub(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->get('/practice');

        $response->assertStatus(200);
        $response->assertSee('Thi Thử');
        $response->assertSee('Nghe (Listening)');
        $response->assertSee('Đọc (Reading)');
        $response->assertSee('Viết (Writing)');
        $response->assertSee('Nói (Speaking)');
        $response->assertSee('Danh Sách Đề Thi');
    }

    public function test_user_can_switch_skill_and_see_prebuilt_exams(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        $response = $this->actingAs($student)->get('/practice?skill=listening');

        $response->assertStatus(200);
        $response->assertSee('Audio Comprehension');
        $response->assertSee('Đề 01: Giao tiếp Cơ bản - Lời cảm ơn');
        $response->assertSee('Đề 02: Tình huống Thực tế - Chỉ đường');
    }

    public function test_user_can_take_and_submit_prebuilt_exam(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        // 1. Open exam player
        $playerRes = $this->actingAs($student)->get('/practice/exam/vocab_test_01');
        $playerRes->assertStatus(200);
        $playerRes->assertSee('Đề 01: Từ vựng Cơ bản');
        $playerRes->assertSee('Nộp bài thi');

        // 2. Submit answers
        $firstQ = QuestionBank::where('skill', 'vocabulary')->where('difficulty', 'A1')->first();
        $submitRes = $this->actingAs($student)->post('/practice/exam/vocab_test_01/submit', [
            'answers' => [
                $firstQ->id => $firstQ->correct_answer,
            ]
        ]);

        $submitRes->assertStatus(200);
        $submitRes->assertSee('Tỷ lệ chính xác');
        $submitRes->assertSee('Số câu đúng');
        $this->assertDatabaseHas('assessment_submissions', [
            'user_id' => $student->id,
            'test_type' => 'vocab_test_01',
        ]);
    }

    public function test_user_can_take_full_4_skill_mock_exam(): void
    {
        $student = User::where('username', 'tuanlinh')->first();

        // 1. Open Full 4-Skill Mock Exam
        $res = $this->actingAs($student)->get('/practice/exam/mock_test_a1');
        $res->assertStatus(200);
        $res->assertSee('Đề Thi Thử Toàn Diện 4 Kỹ Năng A1');

        // 2. Submit answers across skills
        $vQ = QuestionBank::where('skill', 'vocabulary')->where('difficulty', 'A1')->first();
        $gQ = QuestionBank::where('skill', 'grammar')->where('difficulty', 'A1')->first();
        $rQ = QuestionBank::where('skill', 'reading')->where('difficulty', 'A1')->first();
        $lQ = QuestionBank::where('skill', 'listening')->where('difficulty', 'A1')->first();

        $submitRes = $this->actingAs($student)->post('/practice/exam/mock_test_a1/submit', [
            'answers' => [
                $vQ->id => $vQ->correct_answer,
                $gQ->id => $gQ->correct_answer,
                $rQ->id => $rQ->correct_answer,
                $lQ->id => $lQ->correct_answer,
            ]
        ]);

        $submitRes->assertStatus(200);
        $submitRes->assertSee('Ma Trận Điểm Số 4 Kỹ Năng Thực Tế');
        $submitRes->assertSee('Từ vựng');
        $submitRes->assertSee('Ngữ pháp');
        $submitRes->assertSee('Đọc hiểu');
        $submitRes->assertSee('Nghe hiểu');
        $this->assertDatabaseHas('assessment_submissions', [
            'user_id' => $student->id,
            'test_type' => 'mock_test_a1',
        ]);
    }
}
