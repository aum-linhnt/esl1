<?php

namespace TDSoft\AiTutor\Licensing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class LicenseSchemaInspector
{
    public const MIGRATION = '2026_09_24_000002_create_tutor_ai_license_tables';

    private const COLUMNS = [
        'tutor_ai_license_state' => ['id', 'installation_id', 'encrypted_envelope', 'encrypted_license_key', 'last_verified_at', 'last_refreshed_at', 'next_attempt_at', 'failure_count', 'error_code', 'refresh_token', 'refresh_lease_until', 'created_at', 'updated_at'],
        'tutor_ai_license_refresh_attempts' => ['id', 'request_id', 'operation', 'actor_id', 'status', 'error_code', 'started_at', 'completed_at'],
    ];

    public function inspect(): array
    {
        $history = Schema::hasTable('migrations') && DB::table('migrations')->where('migration', self::MIGRATION)->exists();
        $existing = array_values(array_filter(array_keys(self::COLUMNS), fn ($table) => Schema::hasTable($table)));
        if (! $history) {
            return ['state' => $existing === [] ? 'pending' : 'conflict_or_partial', 'problems' => $existing];
        }
        $problems = [];
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $problems[] = $table.' missing';

                continue;
            }
            foreach (array_diff($columns, Schema::getColumnListing($table)) as $column) {
                $problems[] = $table.'.'.$column.' missing';
            }
        }
        foreach (['tutor_ai_license_state' => 'tai_lic_install_uq', 'tutor_ai_license_refresh_attempts' => 'tai_lic_attempt_req_uq'] as $table => $index) {
            if (Schema::hasTable($table) && ! Schema::hasIndex($table, $index, 'unique')) {
                $problems[] = $table.'.'.$index.' missing';
            }
        }

        return ['state' => $problems === [] ? 'installed' : 'schema_mismatch', 'problems' => $problems];
    }
}
