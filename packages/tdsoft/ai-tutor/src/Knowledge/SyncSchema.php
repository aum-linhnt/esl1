<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SyncSchema
{
    public const MIGRATION = '2026_09_25_000004_create_tutor_ai_knowledge_sync_links';

    public const TABLE = 'tutor_ai_knowledge_sync_links';

    public function inspect(): array
    {
        $exists = Schema::hasTable(self::TABLE);
        $history = Schema::hasTable('migrations') && DB::table('migrations')->where('migration', self::MIGRATION)->exists();
        if (! $history) {
            return ['state' => $exists ? 'conflict_or_partial' : 'pending', 'problems' => $exists ? [self::TABLE] : []];
        }
        $problems = ! $exists ? [self::TABLE.' missing'] : [];
        if ($exists) {
            foreach (array_diff(['id', 'document_id', 'version_id', 'fingerprint', 'created_at', 'updated_at'], Schema::getColumnListing(self::TABLE)) as $column) {
                $problems[] = self::TABLE.'.'.$column.' missing';
            }
            if (! Schema::hasIndex(self::TABLE, ['id'], 'primary')) {
                $problems[] = self::TABLE.' primary key missing';
            }
        }

        return ['state' => $problems ? 'schema_mismatch' : 'installed', 'problems' => $problems];
    }
}
