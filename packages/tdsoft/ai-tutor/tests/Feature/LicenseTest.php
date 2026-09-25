<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use TDSoft\AiTutor\Licensing\CanonicalDocument;
use TDSoft\AiTutor\Licensing\InstallationIdentity;
use TDSoft\AiTutor\Licensing\LicenseClient;
use TDSoft\AiTutor\Licensing\LicenseException;
use TDSoft\AiTutor\Licensing\LicenseReader;
use TDSoft\AiTutor\Licensing\OfflineEntitlements;
use TDSoft\AiTutor\Licensing\PublicKeyRing;
use TDSoft\AiTutor\Licensing\RefreshLicenseJob;
use TDSoft\AiTutor\Licensing\SignatureVerifier;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class LicenseTest extends FoundationTestCase
{
    private string $secretKey;

    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 00:00:00 UTC');
        (require __DIR__.'/../../database/migrations/2026_09_24_000002_create_tutor_ai_license_tables.php')->up();
        config([
            'app.url' => 'https://lms.example.test',
            'ai-tutor.license.server_url' => 'https://license.example.test',
            'ai-tutor.license.installation_id' => 'install-test-1',
        ]);
        $pair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($pair);
        $this->publicKey = sodium_crypto_sign_publickey($pair);
        $this->app->instance(PublicKeyRing::class, new class($this->publicKey) extends PublicKeyRing
        {
            public function __construct(private string $publicKey) {}

            public function key(string $keyId): string
            {
                if ($keyId !== 'test-key') {
                    throw new LicenseException('LICENSE_PUBLIC_KEY_NOT_CONFIGURED');
                }

                return $this->publicKey;
            }
        });
        Http::swap(new Factory);
        Http::preventStrayRequests();
    }

    private function envelope(array $changes = []): array
    {
        $document = array_replace([
            'license_id' => 'lic-test-1', 'installation_id' => 'install-test-1', 'domain' => 'lms.example.test',
            'modules' => ['ai_tutor_core', 'ai_tutor_writing'], 'student_limit' => 100,
            'issued_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'expires_at' => '2027-09-30T23:59:59Z', 'refresh_after' => now()->addDay()->utc()->format('Y-m-d\TH:i:s\Z'),
            'grace_until' => '2027-10-07T23:59:59Z',
        ], $changes);

        return [
            'document' => $document, 'key_id' => 'test-key',
            'signature' => base64_encode(sodium_crypto_sign_detached((new CanonicalDocument)->encode($document), $this->secretKey)),
        ];
    }

    private function fake(array $responses): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake($responses);
    }

    private function activate(array $changes = []): void
    {
        $this->fake(['https://license.example.test/*' => Http::response($this->envelope($changes))]);
        app(LicenseClient::class)->activate('LIC-SECRET-TEST', 'admin-1');
    }

    private function error(string $code, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected '.$code);
        } catch (LicenseException $error) {
            $this->assertSame($code, $error->errorCode);
        }
    }

    public function test_signed_activation_encryption_and_minimal_payload(): void
    {
        $this->activate();
        $this->assertSame('active', app(LicenseReader::class)->status()->state);
        $this->assertTrue(app(OfflineEntitlements::class)->allows('ai_tutor_core'));
        $this->assertFalse(app(OfflineEntitlements::class)->allows('ai_tutor_speaking'));
        $state = DB::table('tutor_ai_license_state')->first();
        $this->assertStringNotContainsString('LIC-SECRET-TEST', json_encode($state));
        $this->assertSame('LIC-SECRET-TEST', Crypt::decryptString($state->encrypted_license_key));
        $this->assertStringNotContainsString('LIC-SECRET-TEST', json_encode(DB::table('tutor_ai_license_refresh_attempts')->get()));
        Http::assertSent(function ($request) {
            $keys = array_keys($request->data());
            sort($keys);

            return $keys === ['app_version', 'domain', 'installation_id', 'license_key']
                && $request->hasHeader('Idempotency-Key') && $request->hasHeader('X-Request-ID');
        });
        Http::assertSentCount(1); // Entitlement reads did not call the license API.
    }

    public function test_reordered_keys_verify_but_tampered_module_does_not(): void
    {
        $envelope = $this->envelope();
        $envelope['document'] = array_reverse($envelope['document'], true);
        $this->assertSame('lic-test-1', app(SignatureVerifier::class)->verify($envelope)['license_id']);
        $envelope['document']['modules'][] = 'ai_tutor_speaking';
        $this->error('LICENSE_INVALID_SIGNATURE', fn () => app(SignatureVerifier::class)->verify($envelope));
        $envelope = $this->envelope();
        $envelope['key_id'] = 'untrusted';
        $this->error('LICENSE_PUBLIC_KEY_NOT_CONFIGURED', fn () => app(SignatureVerifier::class)->verify($envelope));
    }

    public function test_canonical_encoding_is_explicit_and_strict(): void
    {
        $document = $this->envelope()['document'];
        $expected = '{"domain":"lms.example.test","expires_at":"2027-09-30T23:59:59Z","grace_until":"2027-10-07T23:59:59Z","installation_id":"install-test-1","issued_at":"2026-09-24T00:00:00Z","license_id":"lic-test-1","modules":["ai_tutor_core","ai_tutor_writing"],"refresh_after":"2026-09-25T00:00:00Z","student_limit":100}';
        $this->assertSame($expected, (new CanonicalDocument)->encode($document));
        $document['student_limit'] = 1.5;
        $this->error('LICENSE_INVALID', fn () => (new CanonicalDocument)->encode($document));
    }

    public function test_wrong_domain_or_installation_is_rejected(): void
    {
        $this->fake(['*' => Http::response($this->envelope(['domain' => 'another.example.test']))]);
        $this->error('LICENSE_DOMAIN_MISMATCH', fn () => app(LicenseClient::class)->activate('key'));
        $this->fake(['*' => Http::response($this->envelope(['installation_id' => 'another-install']))]);
        $this->error('LICENSE_INSTALLATION_MISMATCH', fn () => app(LicenseClient::class)->activate('key'));
        $this->assertFalse(app(OfflineEntitlements::class)->allows('ai_tutor_core'));
    }

    public function test_offline_grace_and_retry_schedule_do_not_extend_deadline(): void
    {
        $this->activate();
        Carbon::setTestNow('2026-09-25 00:00:00 UTC');
        $this->assertSame('refresh_due', app(LicenseReader::class)->status()->state);
        $this->fake(['*' => Http::response([], 503)]);
        $client = app(LicenseClient::class);
        foreach ([1, 6, 24] as $hours) {
            $this->error('LICENSE_SERVER_UNAVAILABLE', fn () => $client->refresh(true));
            $this->assertSame(now()->addHours($hours)->toDateTimeString(), DB::table('tutor_ai_license_state')->value('next_attempt_at'));
        }
        $status = app(LicenseReader::class)->status();
        $this->assertSame('grace', $status->state);
        $this->assertTrue($status->usable());
        Carbon::setTestNow('2026-10-01 00:00:00 UTC');
        $this->assertSame('LICENSE_GRACE_EXPIRED', app(LicenseReader::class)->status()->errorCode);
        $this->assertFalse(app(OfflineEntitlements::class)->allows('ai_tutor_core'));
    }

    public function test_grace_does_not_exceed_signed_grace_until(): void
    {
        $this->activate(['expires_at' => '2026-09-25T00:00:00Z', 'grace_until' => '2026-09-26T00:00:00Z']);
        Carbon::setTestNow('2026-09-25 00:00:00 UTC');
        $this->assertSame('LICENSE_EXPIRED', app(LicenseReader::class)->status()->errorCode);
        $this->fake(['*' => Http::response([], 503)]);
        $this->error('LICENSE_SERVER_UNAVAILABLE', fn () => app(LicenseClient::class)->refresh(true));
        $this->assertSame('grace', app(LicenseReader::class)->status()->state);
        Carbon::setTestNow('2026-09-26 00:00:00 UTC');
        $this->assertFalse(app(LicenseReader::class)->status()->usable());
    }

    public function test_new_signed_refresh_restores_active_but_stale_response_does_not_reset_contact(): void
    {
        $old = $this->envelope();
        $this->activate();
        Carbon::setTestNow('2026-09-25 00:00:00 UTC');
        $this->fake(['*' => Http::response($old)]);
        $this->error('LICENSE_STALE_DOCUMENT', fn () => app(LicenseClient::class)->refresh(true));
        $this->assertSame('2026-09-24 00:00:00', DB::table('tutor_ai_license_state')->value('last_refreshed_at'));
        $this->fake(['*' => Http::response($this->envelope())]);
        app(LicenseClient::class)->refresh(true);
        $this->assertSame('active', app(LicenseReader::class)->status()->state);
        $this->assertSame(0, DB::table('tutor_ai_license_state')->value('failure_count'));
    }

    public function test_signed_revocation_wins_but_unsigned_http_error_does_not_revoke(): void
    {
        $this->activate();
        Carbon::setTestNow('2026-09-25 00:00:00 UTC');
        $this->fake(['*' => Http::response(['status' => 'revoked'], 403)]);
        $this->error('LICENSE_SERVER_UNAVAILABLE', fn () => app(LicenseClient::class)->refresh(true));
        $this->assertTrue(app(OfflineEntitlements::class)->allows('ai_tutor_core'));
        $this->fake(['*' => Http::response($this->envelope(['status' => 'revoked']))]);
        app(LicenseClient::class)->refresh(true);
        $this->assertSame('revoked', app(LicenseReader::class)->status()->state);
        $this->assertFalse(app(OfflineEntitlements::class)->allows('ai_tutor_core'));
    }

    public function test_invalid_refresh_preserves_last_verified_cache(): void
    {
        $this->activate();
        $previous = DB::table('tutor_ai_license_state')->value('encrypted_envelope');
        $bad = $this->envelope();
        $bad['signature'] = base64_encode(random_bytes(64));
        $this->fake(['*' => Http::response($bad)]);
        $this->error('LICENSE_INVALID_SIGNATURE', fn () => app(LicenseClient::class)->refresh(true));
        $this->assertSame($previous, DB::table('tutor_ai_license_state')->value('encrypted_envelope'));
        $this->assertTrue(app(OfflineEntitlements::class)->allows('ai_tutor_core'));
    }

    public function test_next_attempt_and_refresh_lease_prevent_duplicate_calls(): void
    {
        $this->activate();
        app(LicenseClient::class)->refresh();
        Http::assertSentCount(1);
        DB::table('tutor_ai_license_state')->update(['refresh_lease_until' => now()->addMinute()]);
        $this->error('LICENSE_REFRESH_BUSY', fn () => app(LicenseClient::class)->refresh(true));
        Http::assertSentCount(1);
        $this->assertStringNotContainsString('LIC-SECRET-TEST', serialize(new RefreshLicenseJob));
    }

    public function test_unconfigured_server_makes_no_network_request_and_identity_is_stable(): void
    {
        config(['ai-tutor.license.server_url' => '']);
        $this->error('LICENSE_SERVER_NOT_CONFIGURED', fn () => app(LicenseClient::class)->activate('key'));
        $this->assertSame('install-test-1', app(InstallationIdentity::class)->initialize()->installation_id);
        Http::assertNothingSent();
        config(['ai-tutor.license.installation_id' => 'changed']);
        $this->error('LICENSE_INSTALLATION_MISMATCH', fn () => app(InstallationIdentity::class)->initialize());
    }

    public function test_cached_license_is_reverified_and_domain_changes_are_detected(): void
    {
        $this->activate();
        config(['ai-tutor.license.domain' => 'elsewhere.test']);
        $this->assertSame('domain_mismatch', app(LicenseReader::class)->status()->state);
        config(['ai-tutor.license.domain' => '']);
        DB::table('tutor_ai_license_state')->update(['encrypted_envelope' => 'tampered']);
        $this->assertFalse(app(LicenseReader::class)->status()->usable());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        sodium_memzero($this->secretKey);
        parent::tearDown();
    }
}
