<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Licensing\Http\RequireModule;
use TDSoft\AiTutor\Licensing\InstallationIdentity;
use TDSoft\AiTutor\Licensing\LicenseClient;
use TDSoft\AiTutor\Licensing\LicenseException;
use TDSoft\AiTutor\Licensing\LicenseMode;
use TDSoft\AiTutor\Licensing\OfflineEntitlements;
use TDSoft\AiTutor\Licensing\RefreshLicenseJob;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class LicenseModeTest extends FoundationTestCase
{
    public function test_default_and_invalid_modes_never_grant_source_rights(): void
    {
        $mode = new LicenseMode;
        $this->assertSame('server', $mode->value());
        $entitlements = $this->app->make(OfflineEntitlements::class);
        foreach (['', null, false, 'SOURCE_OWNED', 'source_owned ', 'typo'] as $value) {
            config(['ai-tutor.license.mode' => $value, 'ai-tutor.license.source_modules' => ['ai_tutor_core']]);
            $this->assertFalse($entitlements->allows('ai_tutor_core'));
            $this->assertFalse($mode->shouldRefresh());
            try {
                $mode->value();
                $this->fail('Invalid mode accepted');
            } catch (LicenseException $error) {
                $this->assertSame('LICENSE_MODE_INVALID', $error->errorCode);
            }
        }
    }

    public function test_source_mode_has_explicit_module_allowlist_and_no_identity_requirement(): void
    {
        config(['ai-tutor.license.mode' => 'source_owned', 'ai-tutor.license.source_modules' => ['ai_tutor_core']]);
        $entitlements = $this->app->make(OfflineEntitlements::class);
        $this->assertTrue($entitlements->allows('ai_tutor_core'));
        $this->assertFalse($entitlements->allows('ai_tutor_knowledge'));
        $middleware = new RequireModule($entitlements, new InstallationIdentity);
        $this->assertSame('ok', $middleware->handle(Request::create('https://different.example/tutor'), fn () => 'ok', 'ai_tutor_core'));
        config(['ai-tutor.license.source_modules' => []]);
        $this->assertFalse($entitlements->allows('ai_tutor_core'));
        config(['ai-tutor.license.source_modules' => ['*']]);
        $this->assertFalse($entitlements->allows('ai_tutor_core'));
        config(['ai-tutor.license.mode' => 'server', 'ai-tutor.license.source_modules' => ['ai_tutor_core']]);
        $this->assertFalse($entitlements->allows('ai_tutor_core')); // No signed license installed.
    }

    public function test_invalid_mode_middleware_denies_even_with_custom_entitlements(): void
    {
        config(['ai-tutor.license.mode' => 'invalid']);
        try {
            (new RequireModule($this->app->make(Entitlements::class), new InstallationIdentity))
                ->handle(Request::create('/ai-tutor'), fn () => $this->fail('Guard bypassed'), 'ai_tutor_core');
            $this->fail('Expected deny');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
            $this->assertSame('LICENSE_MODE_INVALID', $error->getMessage());
        }
    }

    public function test_client_and_queued_refresh_never_touch_license_storage_in_source_mode(): void
    {
        config(['ai-tutor.license.mode' => 'source_owned']);
        $client = $this->app->make(LicenseClient::class);
        foreach ([fn () => $client->activate('key'), fn () => $client->refresh(true)] as $action) {
            try {
                $action();
                $this->fail('License operation allowed');
            } catch (LicenseException $error) {
                $this->assertSame('LICENSE_NOT_REQUIRED', $error->errorCode);
            }
        }
        // Fixture has no license tables: any accidental storage access fails this test.
        (new RefreshLicenseJob)->handle($client);
        $this->assertFalse((new LicenseMode)->shouldRefresh());
    }

    public function test_source_mode_still_enforces_lms_and_credit(): void
    {
        config(['ai-tutor.license.mode' => 'source_owned', 'ai-tutor.license.source_modules' => ['ai_tutor_core']]);
        $this->app->instance(Entitlements::class, $this->app->make(OfflineEntitlements::class));
        $service = $this->app->make(AiExecutionService::class);
        $this->lms->allowed = false;
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $service->execute($this->request()));
        $this->lms->allowed = true;
        DB::table('tutor_ai_credit_accounts')->update(['balance' => 0]);
        $this->assertError('AI_CREDIT_INSUFFICIENT', fn () => $service->execute($this->request('no-credit')));
        $this->assertSame(0, $this->provider->calls);
    }
}
