<?php

namespace TDSoft\AiTutor\Assessment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PhaseFourSchema
{
    public const MIGRATION = '2026_10_05_000007_create_tutor_ai_english_assessments';

    public const COLUMNS = [
        'tutor_ai_assessment_rubrics' => ['id', 'key', 'skill', 'task', 'created_at', 'updated_at'],
        'tutor_ai_assessment_rubric_versions' => ['id', 'rubric_id', 'version', 'prompt_version', 'criteria', 'fingerprint', 'created_at'],
        'tutor_ai_writing_drafts' => ['id', 'actor_id', 'course_id', 'lesson_id', 'profile', 'task', 'topic', 'revision', 'content', 'created_at', 'updated_at'],
        'tutor_ai_writing_revisions' => ['id', 'draft_id', 'revision', 'content', 'created_at'],
        'tutor_ai_writing_submissions' => ['id', 'draft_id', 'actor_id', 'course_id', 'lesson_id', 'revision', 'original', 'profile', 'task', 'topic', 'rubric_version_id', 'rubric_snapshot', 'request_id', 'idempotency_key', 'fingerprint', 'status', 'error_code', 'result', 'provider', 'model', 'completed_at', 'created_at', 'updated_at'],
        'tutor_ai_writing_issues' => ['id', 'submission_id', 'category', 'start_utf16', 'end_utf16', 'original', 'replacement', 'explanation', 'created_at'],
        'tutor_ai_speaking_attempts' => ['id', 'actor_id', 'course_id', 'lesson_id', 'profile', 'task', 'topic', 'rubric_version_id', 'rubric_snapshot', 'status', 'audio_path', 'audio_mime', 'audio_bytes', 'audio_duration_ms', 'audio_checksum', 'transcript', 'transcription_request_id', 'assessment_request_id', 'error_code', 'result', 'provider', 'model', 'completed_at', 'created_at', 'updated_at'],
        'tutor_ai_speaking_segments' => ['id', 'attempt_id', 'position', 'start_ms', 'end_ms', 'text', 'evidence', 'created_at'],
        'tutor_ai_assessment_scores' => ['id', 'writing_submission_id', 'speaking_attempt_id', 'criterion', 'status', 'score', 'evidence', 'created_at'],
    ];

    public const UNIQUE_INDEXES = [
        'tutor_ai_assessment_rubrics' => ['tai_rub_key_uq'],
        'tutor_ai_assessment_rubric_versions' => ['tai_rub_ver_uq', 'tai_rub_fp_uq'],
        'tutor_ai_writing_revisions' => ['tai_wr_rev_uq'],
        'tutor_ai_writing_submissions' => ['tai_ws_req_uq', 'tai_ws_key_uq'],
        'tutor_ai_speaking_attempts' => ['tai_sp_stt_uq', 'tai_sp_req_uq'],
        'tutor_ai_speaking_segments' => ['tai_sp_seg_uq'],
        'tutor_ai_assessment_scores' => ['tai_score_wr_uq', 'tai_score_sp_uq'],
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
            if (! Schema::hasIndex($table, ['id'], 'primary')) {
                $problems[] = $table.' primary key missing';
            }
            foreach (self::UNIQUE_INDEXES[$table] ?? [] as $index) {
                if (! Schema::hasIndex($table, $index, 'unique')) {
                    $problems[] = $table.'.'.$index.' missing or not unique';
                }
            }
        }

        return ['state' => $problems ? 'schema_mismatch' : 'installed', 'problems' => $problems];
    }
}
