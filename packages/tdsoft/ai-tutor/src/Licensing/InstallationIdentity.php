<?php

namespace TDSoft\AiTutor\Licensing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class InstallationIdentity
{
    public function initialized(): ?object
    {
        return Schema::hasTable('tutor_ai_license_state') ? DB::table('tutor_ai_license_state')->where('id', 1)->first() : null;
    }

    public function initialize(): object
    {
        if (! Schema::hasTable('tutor_ai_license_state') || ! Schema::hasTable('tutor_ai_license_refresh_attempts')) {
            throw new LicenseException('LICENSE_MIGRATION_REQUIRED');
        }
        $configured = config('ai-tutor.license.installation_id');
        $id = $configured ?: 'install_'.Str::uuid();
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_.-]{1,191}$/D', $id)) {
            throw new LicenseException('LICENSE_INSTALLATION_MISMATCH');
        }
        DB::table('tutor_ai_license_state')->insertOrIgnore([
            'id' => 1, 'installation_id' => $id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $state = $this->initialized();
        $this->assertMatches($state);

        return $state;
    }

    public function assertMatches(object $state): void
    {
        $configured = config('ai-tutor.license.installation_id');
        if ($configured && $configured !== $state->installation_id) {
            throw new LicenseException('LICENSE_INSTALLATION_MISMATCH');
        }
    }

    public function domain(): string
    {
        $domain = config('ai-tutor.license.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($domain)) {
            throw new LicenseException('LICENSE_DOMAIN_MISMATCH');
        }
        $domain = strtolower(rtrim($domain, '.'));
        if (! preg_match('/^[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?$/D', $domain) || str_contains($domain, '..')) {
            throw new LicenseException('LICENSE_DOMAIN_MISMATCH');
        }

        return $domain;
    }

    public function assertDocument(array $document, object $state): void
    {
        $this->assertMatches($state);
        if ($document['installation_id'] !== $state->installation_id) {
            throw new LicenseException('LICENSE_INSTALLATION_MISMATCH');
        }
        if ($document['domain'] !== $this->domain()) {
            throw new LicenseException('LICENSE_DOMAIN_MISMATCH');
        }
    }
}
