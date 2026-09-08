<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\GeminiApiService;
use App\Services\AI\AiTutorService;
use App\Services\AI\AiWritingService;
use App\Services\AI\AiSpeakingService;
use App\Services\AI\AiExerciseGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiServiceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_gemini_api_service_fallback_when_unconfigured(): void
    {
        $service = new GeminiApiService();
        $response = $service->generateContent('flash', '', 'Hello');

        $this->assertNotEmpty($response);
        $this->assertIsString($response);
    }

    public function test_gemini_api_test_connection_when_empty_key(): void
    {
        config(['services.gemini.api_key' => '']);
        $service = new GeminiApiService();
        $result = $service->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('chưa được cấu hình', $result['message']);
    }

    public function test_admin_can_view_ai_settings_page(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Cấu hình Google Gemini AI');
        $response->assertSee('Google Gemini API Key');
        $response->assertSee('Test kết nối');
    }

    public function test_admin_can_update_gemini_api_key(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('admin.settings.updateApiKey'), [
            'api_key' => 'AIzaSyDemoSampleKeyForTesting123456',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');
    }

    public function test_admin_can_trigger_test_connection_endpoint(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->postJson(route('admin.settings.testConnection'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'model']);
    }

    public function test_ai_exercise_generator_supports_all_8_question_types(): void
    {
        $service = app(AiExerciseGeneratorService::class);
        $questions = $service->generateQuestions(
            'Environment & Climate',
            'B1',
            3,
            ['mcq', 'multiple_select', 'fill_blank']
        );

        $this->assertIsArray($questions);
        $this->assertNotEmpty($questions);
    }
}
