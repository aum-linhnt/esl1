<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PhaseThreeSchema
{
    public const MIGRATION = '2026_09_25_000003_create_tutor_ai_knowledge_conversations';

    public const COLUMNS = [
        'tutor_ai_knowledge_documents' => ['id', 'title', 'course_id', 'lesson_id', 'subject', 'level', 'visibility', 'contains_answers', 'published_version_id', 'created_by'],
        'tutor_ai_knowledge_document_versions' => ['id', 'document_id', 'status', 'format', 'content', 'checksum', 'created_by', 'published_at'],
        'tutor_ai_knowledge_chunks' => ['id', 'version_id', 'position', 'content', 'embedding', 'indexed', 'embedding_model'],
        'tutor_ai_knowledge_processing_jobs' => ['id', 'version_id', 'actor_id', 'status', 'attempts', 'error_code'],
        'tutor_ai_conversations' => ['id', 'user_id', 'course_id', 'lesson_id', 'question_id', 'teaching_mode'],
        'tutor_ai_conversation_messages' => ['id', 'conversation_id', 'idempotency_key', 'fingerprint', 'request_id', 'embedding_request_id', 'user_content', 'content', 'encrypted_payload', 'status', 'error_code', 'metadata'],
        'tutor_ai_message_sources' => ['id', 'message_id', 'chunk_id', 'citation'],
        'tutor_ai_message_feedback' => ['id', 'message_id', 'user_id', 'rating'],
    ];

    public function inspect(): array
    {
        $history = Schema::hasTable('migrations') && DB::table('migrations')->where('migration', self::MIGRATION)->exists();
        $existing = array_values(array_filter(array_keys(self::COLUMNS), fn ($table) => Schema::hasTable($table)));
        if (! $history) {
            return ['state' => $existing ? 'conflict_or_partial' : 'pending', 'problems' => $existing];
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
        foreach ([
            'tutor_ai_knowledge_chunks' => ['tai_chunk_pos_uq'],
            'tutor_ai_knowledge_processing_jobs' => ['tai_kjob_version_uq'],
            'tutor_ai_conversation_messages' => ['tai_msg_key_uq', 'tai_msg_req_uq'],
            'tutor_ai_message_sources' => ['tai_source_chunk_uq'],
            'tutor_ai_message_feedback' => ['tai_feedback_user_uq'],
        ] as $table => $indexes) {
            foreach ($indexes as $index) {
                if (Schema::hasTable($table) && ! Schema::hasIndex($table, $index, 'unique')) {
                    $problems[] = $table.'.'.$index.' missing';
                }
            }
        }

        return ['state' => $problems ? 'schema_mismatch' : 'installed', 'problems' => $problems];
    }
}
