<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $envHashBefore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->envHashBefore = $this->envHash();
    }

    protected function tearDown(): void
    {
        // Guard: no test may ever rewrite the developer's real .env file.
        $this->assertSame($this->envHashBefore, $this->envHash(), '.env was modified during the test.');
        parent::tearDown();
    }

    private function envHash(): string
    {
        $path = base_path('.env');

        return file_exists($path) ? md5_file($path) : 'missing';
    }

    public function test_api_key_is_encrypted_at_rest_and_applied_to_config(): void
    {
        $service = app(SettingService::class);
        $service->set('gemini.api_key', 'AIzaTestKey-abcdefghijkl');

        $row = Setting::where('key', 'gemini.api_key')->first();
        $this->assertTrue($row->is_encrypted);
        $this->assertNotSame('AIzaTestKey-abcdefghijkl', $row->value);

        $this->assertSame('AIzaTestKey-abcdefghijkl', $service->get('gemini.api_key'));
        $this->assertSame('AIzaTestKey-abcdefghijkl', config('services.gemini.api_key'));
    }

    public function test_bool_settings_round_trip(): void
    {
        $service = app(SettingService::class);

        $service->set('theme.glow', false);
        $this->assertFalse($service->get('theme.glow'));
        $this->assertFalse(config('theme.glow'));

        $service->set('theme.glow', true);
        $this->assertTrue(config('theme.glow'));
    }

    public function test_settings_survive_a_fresh_service_instance_via_cache_or_db(): void
    {
        app(SettingService::class)->set('theme.accent', 'rose');

        $fresh = new SettingService();
        $this->assertSame('rose', $fresh->get('theme.accent'));

        $fresh->flush();
        $this->assertSame('rose', $fresh->get('theme.accent'));
    }

    public function test_admin_update_api_key_saves_to_db_not_env(): void
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post(route('admin.settings.updateApiKey'), [
            'api_key' => 'AIzaSyDemoSampleKeyForTesting123456',
            'model_flash' => 'gemini-2.0-flash',
        ])->assertRedirect(route('admin.settings.index'));

        $service = app(SettingService::class);
        $service->flush();
        $this->assertSame('AIzaSyDemoSampleKeyForTesting123456', $service->get('gemini.api_key'));
        $this->assertSame('gemini-2.0-flash', $service->get('gemini.model_flash'));
        $this->assertNull($service->get('gemini.model_pro'), 'Omitted model must not be overwritten.');
    }

    public function test_admin_update_theme_saves_to_db_not_env(): void
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post(route('admin.settings.updateTheme'), [
            'theme_mode' => 'auto',
            'theme_accent' => 'emerald',
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertSame('auto', config('theme.mode'));
        $this->assertSame('emerald', config('theme.accent'));
        $this->assertFalse(config('theme.glow'));
    }

    public function test_teacher_cannot_update_settings(): void
    {
        $teacher = User::where('role', 'teacher')->first();

        $this->actingAs($teacher)->post(route('admin.settings.updateApiKey'), [
            'api_key' => 'AIzaShouldNotBeSaved-123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('settings', ['key' => 'gemini.api_key']);
    }
}
