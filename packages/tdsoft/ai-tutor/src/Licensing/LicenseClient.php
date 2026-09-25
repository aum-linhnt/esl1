<?php

namespace TDSoft\AiTutor\Licensing;

use Composer\InstalledVersions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class LicenseClient
{
    public function __construct(
        private InstallationIdentity $identity,
        private SignatureVerifier $verifier,
        private LicenseReader $reader,
        private CanonicalDocument $canonical,
    ) {}

    public function activate(#[\SensitiveParameter] string $licenseKey, ?string $actorId = null): void
    {
        (new LicenseMode)->requireServer();
        if ($licenseKey === '' || strlen($licenseKey) > 512) {
            throw new LicenseException('LICENSE_KEY_REQUIRED');
        }
        $this->exchange('activate', $licenseKey, true, $actorId);
    }

    public function refresh(bool $force = false, ?string $actorId = null): void
    {
        (new LicenseMode)->requireServer();
        $this->exchange('refresh', null, $force, $actorId);
    }

    private function exchange(string $operation, #[\SensitiveParameter] ?string $licenseKey, bool $force, ?string $actorId): void
    {
        $state = $this->identity->initialize();
        if ($operation === 'refresh') {
            if (! $state->encrypted_envelope) {
                throw new LicenseException('LICENSE_NOT_ACTIVATED');
            }
            if (! $force && $state->next_attempt_at && now()->lt(Carbon::parse($state->next_attempt_at))) {
                return;
            }
        }
        $token = (string) Str::uuid();
        $state = DB::transaction(function () use ($token, $operation, $actorId, $force) {
            $locked = DB::table('tutor_ai_license_state')->where('id', 1)->lockForUpdate()->first();
            if ($operation === 'refresh' && ! $force && $locked->next_attempt_at && now()->lt(Carbon::parse($locked->next_attempt_at))) {
                return null;
            }
            if ($locked->refresh_lease_until && now()->lt(Carbon::parse($locked->refresh_lease_until))) {
                throw new LicenseException('LICENSE_REFRESH_BUSY');
            }
            DB::table('tutor_ai_license_state')->where('id', 1)->update([
                'refresh_token' => $token, 'refresh_lease_until' => now()->addMinutes(2), 'updated_at' => now(),
            ]);
            DB::table('tutor_ai_license_refresh_attempts')->insert([
                'request_id' => $token, 'operation' => $operation, 'actor_id' => $actorId,
                'status' => 'processing', 'started_at' => now(),
            ]);

            return $locked;
        });
        if ($state === null) {
            return;
        }
        try {
            $baseUrl = rtrim((string) config('ai-tutor.license.server_url'), '/');
            $parsed = parse_url($baseUrl);
            if (! is_array($parsed) || ($parsed['scheme'] ?? '') !== 'https' || empty($parsed['host'])
                || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['query']) || isset($parsed['fragment'])) {
                throw new LicenseException('LICENSE_SERVER_NOT_CONFIGURED');
            }
            if ($licenseKey === null) {
                $licenseKey = $state->encrypted_license_key
                    ? Crypt::decryptString($state->encrypted_license_key)
                    : config('ai-tutor.license.key');
            }
            if (! is_string($licenseKey) || $licenseKey === '') {
                throw new LicenseException('LICENSE_KEY_REQUIRED');
            }
            $payload = [
                'license_key' => $licenseKey,
                'domain' => $this->identity->domain(),
                'installation_id' => $state->installation_id,
                'app_version' => InstalledVersions::getPrettyVersion('tdsoft/ai-tutor') ?? 'dev',
            ];
            if ($operation === 'refresh') {
                $payload['license_id'] = $this->reader->document($state)['license_id'];
            }
            $response = Http::acceptJson()->asJson()->connectTimeout(5)->timeout(15)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['X-Request-ID' => $token, 'Idempotency-Key' => $token])
                ->post($baseUrl.'/api/v1/licenses/'.$operation, $payload);
            if (! $response->successful()) {
                // An unsigned HTTP 403/410 is NOT proof of a signed revocation.
                throw new LicenseException('LICENSE_SERVER_UNAVAILABLE');
            }
            if (strlen($response->body()) > 65536) {
                throw new LicenseException('LICENSE_INVALID');
            }
            $envelope = json_decode($response->body(), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($envelope)) {
                throw new LicenseException('LICENSE_INVALID');
            }
            $document = $this->verifier->verify($envelope);
            $this->identity->assertDocument($document, $state);
            if (($document['status'] ?? 'active') !== 'revoked' && now()->gte(Carbon::parse($document['expires_at']))) {
                throw new LicenseException('LICENSE_EXPIRED');
            }
            $this->accept($token, $envelope, $document, $licenseKey);
        } catch (Throwable $error) {
            $code = $error instanceof LicenseException ? $error->errorCode : 'LICENSE_SERVER_UNAVAILABLE';
            $this->failure($token, $code);
            throw new LicenseException($code);
        }
    }

    private function accept(string $token, array $envelope, array $document, #[\SensitiveParameter] string $key): void
    {
        DB::transaction(function () use ($token, $envelope, $document, $key) {
            $state = DB::table('tutor_ai_license_state')->where('id', 1)->lockForUpdate()->first();
            if ($state->refresh_token !== $token) {
                throw new LicenseException('LICENSE_REFRESH_BUSY');
            }
            if ($state->encrypted_envelope) {
                // Preserve issuance monotonicity, even if deployment domain was changed.
                $previous = json_decode(Crypt::decryptString($state->encrypted_envelope), true, 32, JSON_THROW_ON_ERROR)['document'];
                if ($document['issued_at'] <= $previous['issued_at']) {
                    throw new LicenseException('LICENSE_STALE_DOCUMENT');
                }
            }
            $hours = max(1, min(24, (int) config('ai-tutor.license.refresh_hours', 24)));
            $next = now()->addHours($hours)->min(Carbon::parse($document['refresh_after']));
            if ($next->lte(now())) {
                $next = now()->addHour();
            }
            DB::table('tutor_ai_license_state')->where('id', 1)->update([
                'encrypted_envelope' => Crypt::encryptString(json_encode($envelope, JSON_THROW_ON_ERROR)),
                'encrypted_license_key' => Crypt::encryptString($key),
                'last_verified_at' => now(), 'last_refreshed_at' => now(), 'next_attempt_at' => $next,
                'failure_count' => 0, 'error_code' => null, 'refresh_token' => null,
                'refresh_lease_until' => null, 'updated_at' => now(),
            ]);
            DB::table('tutor_ai_license_refresh_attempts')->where('request_id', $token)->update([
                'status' => 'completed', 'completed_at' => now(),
            ]);
        });
    }

    private function failure(string $token, string $code): void
    {
        DB::transaction(function () use ($token, $code) {
            $state = DB::table('tutor_ai_license_state')->where('id', 1)->lockForUpdate()->first();
            if ($state->refresh_token === $token) {
                $count = min(1000000, $state->failure_count + 1);
                $hours = [1, 6, 24][min($count - 1, 2)];
                DB::table('tutor_ai_license_state')->where('id', 1)->update([
                    'failure_count' => $count, 'error_code' => $code, 'next_attempt_at' => now()->addHours($hours),
                    'refresh_token' => null, 'refresh_lease_until' => null, 'updated_at' => now(),
                ]);
            }
            DB::table('tutor_ai_license_refresh_attempts')->where('request_id', $token)->update([
                'status' => 'failed', 'error_code' => $code, 'completed_at' => now(),
            ]);
        });
    }
}
