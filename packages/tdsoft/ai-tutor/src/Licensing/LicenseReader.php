<?php

namespace TDSoft\AiTutor\Licensing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Throwable;

final class LicenseReader
{
    public function __construct(private InstallationIdentity $identity, private SignatureVerifier $verifier) {}

    public function document(object $state): array
    {
        try {
            $envelope = json_decode(Crypt::decryptString($state->encrypted_envelope), true, 32, JSON_THROW_ON_ERROR);
            $document = $this->verifier->verify($envelope);
            $this->identity->assertDocument($document, $state);

            return $document;
        } catch (LicenseException $error) {
            throw $error;
        } catch (Throwable) {
            throw new LicenseException('LICENSE_INVALID');
        }
    }

    public function status(): LicenseStatus
    {
        $state = $this->identity->initialized();
        if (! $state || ! $state->encrypted_envelope) {
            return new LicenseStatus('unconfigured', 'LICENSE_NOT_ACTIVATED');
        }
        try {
            $document = $this->document($state);
            if (($document['status'] ?? 'active') === 'revoked') {
                return new LicenseStatus('revoked', 'LICENSE_REVOKED', $document);
            }
            $signedDeadline = Carbon::parse($document['grace_until']);
            // Only accepting a newly issued signed document updates this timestamp.
            if (! $state->last_refreshed_at) {
                return new LicenseStatus('invalid_signature', 'LICENSE_INVALID');
            }
            $anchor = Carbon::parse($state->last_refreshed_at);
            $days = max(1, min(7, (int) config('ai-tutor.license.grace_days', 7)));
            $offlineUntil = $anchor->copy()->addDays($days)->min($signedDeadline);
            $expires = Carbon::parse($document['expires_at']);
            $warning = now()->gte($offlineUntil->copy()->subDays(3)) || now()->gte($expires->copy()->subDays(3));
            if (now()->gte($offlineUntil)) {
                return new LicenseStatus('expired', 'LICENSE_GRACE_EXPIRED', $document, $offlineUntil->toIso8601String(), true);
            }
            $due = now()->gte(Carbon::parse($document['refresh_after']));
            if ($state->last_refreshed_at) {
                $due = $due || now()->gte(Carbon::parse($state->last_refreshed_at)->addHours(
                    max(1, min(24, (int) config('ai-tutor.license.refresh_hours', 24)))
                ));
            }
            if (now()->gte($expires)) {
                // Expiration grace is for a failed refresh, not a license extension.
                return $state->failure_count > 0
                    ? new LicenseStatus('grace', null, $document, $offlineUntil->toIso8601String(), true)
                    : new LicenseStatus('expired', 'LICENSE_EXPIRED', $document, $offlineUntil->toIso8601String(), true);
            }

            return new LicenseStatus($state->failure_count > 0 ? 'grace' : ($due ? 'refresh_due' : 'active'),
                null, $document, $offlineUntil->toIso8601String(), $warning);
        } catch (LicenseException $error) {
            $stateName = match ($error->errorCode) {
                'LICENSE_DOMAIN_MISMATCH' => 'domain_mismatch',
                'LICENSE_INSTALLATION_MISMATCH' => 'installation_mismatch',
                default => 'invalid_signature',
            };

            return new LicenseStatus($stateName, $error->errorCode);
        }
    }
}
