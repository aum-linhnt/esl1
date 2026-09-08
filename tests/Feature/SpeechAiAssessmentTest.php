<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpeechAiAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_api_v1_assess_multipart_endpoint(): void
    {
        Http::fake([
            'https://speechai.lmsviet.com/api/v1/assess' => Http::response([
                'success' => true,
                'score' => 88.5,
                'accuracy' => 89.2,
                'fluency' => 81.0,
                'completeness' => 100.0,
                'prosody_score' => 93.4,
                'transcribe' => 'HELLO HOW ARE YOU',
                'feedback' => 'Phát âm xuất sắc!',
                'ielts_cefr' => [
                    'ielts_band' => '7.5 - 8.0',
                    'cefr_level' => 'C1',
                    'level_title' => 'C1 Advanced',
                    'summary' => 'Phát âm rất rõ ràng.'
                ],
                'words_detail' => [
                    [
                        'word' => 'Hello',
                        'score' => 88.0,
                        'status' => 'correct',
                        'phonemes' => [
                            ['phoneme' => 'h', 'score' => 88.0, 'status' => 'good', 'tip' => 'Thở nhẹ'],
                        ],
                    ]
                ],
                'actionable_tips' => [],
            ], 200),
        ]);

        $audioFile = UploadedFile::fake()->create('record.webm', 100, 'audio/webm');

        $response = $this->post('/api/v1/assess', [
            'audio_file' => $audioFile,
            'text' => 'Hello how are you',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 88.5,
            'accuracy' => 89.2,
            'fluency' => 81.0,
            'completeness' => 100.0,
            'prosody_score' => 93.4,
            'transcribe' => 'HELLO HOW ARE YOU',
        ]);
        $response->assertJsonStructure([
            'ielts_cefr' => ['ielts_band', 'cefr_level', 'level_title', 'summary'],
            'words_detail',
        ]);
    }

    public function test_api_v1_assess_base64_endpoint(): void
    {
        Http::fake([
            'https://speechai.lmsviet.com/api/v1/assess-base64' => Http::response([
                'success' => true,
                'score' => 92.0,
                'accuracy' => 94.0,
                'fluency' => 90.0,
                'completeness' => 100.0,
                'prosody_score' => 88.0,
                'transcribe' => 'THE COMPUTER IS VERY IMPORTANT',
                'feedback' => 'Rất tốt!',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/assess-base64', [
            'audio_base64' => 'UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=',
            'text' => 'The computer is very important',
            'audio_format' => 'wav',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 92.0,
            'accuracy' => 94.0,
        ]);
    }

    public function test_api_v1_assess_url_endpoint(): void
    {
        Http::fake([
            'https://speechai.lmsviet.com/api/v1/assess-url' => Http::response([
                'success' => true,
                'score' => 85.0,
                'accuracy' => 86.0,
                'fluency' => 84.0,
                'completeness' => 100.0,
                'prosody_score' => 85.0,
                'transcribe' => 'SAMPLE RECORDING',
                'feedback' => 'Khá tốt!',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/assess-url', [
            'audio_url' => 'https://example.com/audio/sample.mp3',
            'text' => 'Sample recording',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 85.0,
        ]);
    }

    public function test_web_ai_speaking_evaluate_with_audio_file(): void
    {
        Http::fake([
            'https://speechai.lmsviet.com/api/v1/assess' => Http::response([
                'success' => true,
                'score' => 86.5,
                'accuracy' => 88.0,
                'fluency' => 85.0,
                'completeness' => 100.0,
                'prosody_score' => 86.0,
                'transcribe' => 'GOOD MORNING',
                'feedback' => 'Phát âm tuyệt vời!',
            ], 200),
        ]);

        $student = User::where('username', 'tuanlinh')->first();
        $initialCoins = $student->coins;

        $audioFile = UploadedFile::fake()->create('speech.webm', 80, 'audio/webm');

        $response = $this->actingAs($student)->post('/ai/speaking/evaluate', [
            'audio_file' => $audioFile,
            'reference_sentence' => 'Good morning, nice to meet you here today.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'score' => 86.5,
            'xp_earned' => 10,
        ]);

        // Verify gamification rewards
        $this->assertEquals($initialCoins + 2, $student->fresh()->coins);
    }
}
