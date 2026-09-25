<?php

namespace TDSoft\AiTutor\Billing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CreditAdminSchema
{
    public const MIGRATION = '2026_09_25_000005_create_tutor_ai_audit_logs';

    public const TABLE = 'tutor_ai_audit_logs';

    public function inspect(): array
    {
        $exists = Schema::hasTable(self::TABLE);
        $history = Schema::hasTable('migrations') && DB::table('migrations')->where('migration', self::MIGRATION)->exists();
        if (! $history) {
            return ['state' => $exists ? 'conflict_or_partial' : 'pending', 'problems' => $exists ? [self::TABLE] : []];
        }
        $problems = $exists ? [] : [self::TABLE.' missing'];
        if ($exists) {
            foreach (array_diff(['id', 'operation_id', 'actor_id', 'action', 'target_id', 'fingerprint', 'details', 'created_at'], Schema::getColumnListing(self::TABLE)) as $column) {
                $problems[] = self::TABLE.'.'.$column.' missing';
            }
            if (! Schema::hasIndex(self::TABLE, 'tai_audit_operation_uq', 'unique')) {
                $problems[] = 'tai_audit_operation_uq missing';
            }
        }

        return ['state' => $problems ? 'schema_mismatch' : 'installed', 'problems' => $problems];
    }
}
