<?php

namespace TDSoft\AiTutor\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SchemaInspector
{
    public const MIGRATION = '2026_09_24_000001_create_tutor_ai_foundation';

    public const COLUMNS = [
        'tutor_ai_requests' => ['request_id', 'idempotency_key', 'fingerprint', 'user_id', 'feature', 'billing_mode', 'provider', 'model', 'status', 'reserved_units', 'actual_units', 'remote_request_id', 'error_code', 'rule_snapshot', 'encrypted_result', 'started_at', 'completed_at', 'failed_at', 'created_at', 'updated_at'],
        'tutor_ai_credit_accounts' => ['owner_type', 'owner_id', 'scope', 'balance', 'daily_limit', 'weekly_limit', 'monthly_limit', 'status', 'created_at', 'updated_at'],
        'tutor_ai_credit_rules' => ['feature', 'base_units', 'max_units_per_request', 'blocks', 'cost_rates', 'currency', 'enabled', 'created_at', 'updated_at'],
        'tutor_ai_credit_transactions' => ['account_id', 'request_id', 'type', 'units', 'feature', 'balance_before', 'balance_after', 'idempotency_key', 'reference_type', 'reference_id', 'metadata', 'created_at'],
        'tutor_ai_usage_records' => ['request_id', 'user_id', 'feature', 'billing_mode', 'provider', 'model', 'input_tokens', 'output_tokens', 'cached_tokens', 'audio_seconds', 'image_count', 'document_pages', 'credit_units', 'estimated_cost', 'currency', 'provider_request_id', 'remote_request_id', 'latency_ms', 'status', 'metadata', 'created_at', 'updated_at'],
        'tutor_ai_cost_snapshots' => ['request_id', 'rates', 'currency', 'estimated_cost', 'created_at'],
    ];

    public function inspect(): array
    {
        $history = Schema::hasTable('migrations') && DB::table('migrations')->where('migration', self::MIGRATION)->exists();
        $existing = array_values(array_filter(array_keys(self::COLUMNS), fn ($table) => Schema::hasTable($table)));
        if (! $history) {
            return ['state' => $existing === [] ? 'fresh' : 'conflict_or_partial', 'problems' => $existing];
        }
        $problems = [];
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $problems[] = $table.' missing';

                continue;
            }
            foreach (array_diff(['id', ...$columns], Schema::getColumnListing($table)) as $column) {
                $problems[] = $table.'.'.$column.' missing';
            }
        }
        foreach ([
            'tutor_ai_requests' => ['tai_req_id_uq', 'tai_req_key_uq'],
            'tutor_ai_credit_accounts' => ['tai_account_owner_uq'],
            'tutor_ai_credit_rules' => ['tai_rule_feature_uq'],
            'tutor_ai_credit_transactions' => ['tai_tx_key_uq'],
            'tutor_ai_usage_records' => ['tai_usage_request_uq'],
            'tutor_ai_cost_snapshots' => ['tai_cost_request_uq'],
        ] as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $index) {
                if (! Schema::hasIndex($table, $index, 'unique')) {
                    $problems[] = $table.'.'.$index.' missing or not unique';
                }
            }
        }

        return ['state' => $problems === [] ? 'installed' : 'schema_mismatch', 'problems' => $problems];
    }
}
