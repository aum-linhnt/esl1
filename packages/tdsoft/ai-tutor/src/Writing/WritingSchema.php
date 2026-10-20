<?php

namespace TDSoft\AiTutor\Writing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WritingSchema
{
    public const MIGRATION = '2026_10_05_000008_add_writing_execution_snapshot';

    public function inspect(): array
    {
        $table = 'tutor_ai_writing_submissions';
        $columns = ['encrypted_payload', 'feature', 'retry_of_submission_id'];
        $history = Schema::hasTable('migrations') && DB::table('migrations')->where('migration', self::MIGRATION)->exists();
        $exists = Schema::hasTable($table);
        $found = $exists ? array_intersect($columns, Schema::getColumnListing($table)) : [];
        if (! $history) {
            return ['state' => $found ? 'conflict_or_partial' : 'pending', 'problems' => array_values($found)];
        }
        $problems = array_map(fn ($column) => $table.'.'.$column.' missing', array_diff($columns, $found));
        if (! $exists || ! Schema::hasIndex($table, 'tai_ws_retry_uq', 'unique')) {
            $problems[] = $table.'.tai_ws_retry_uq missing';
        }

        return ['state' => $problems ? 'schema_mismatch' : 'installed', 'problems' => $problems];
    }
}
