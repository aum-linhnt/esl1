<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Core\AiException;

return new class extends Migration
{
    public function up(): void
    {
        $existing = array_filter(array_keys(PhaseFourSchema::COLUMNS), fn ($table) => Schema::hasTable($table));
        if ($existing) {
            throw new RuntimeException('AI Tutor Phase 4 collision / partial installation: '.implode(', ', $existing));
        }
        Schema::create('tutor_ai_assessment_rubrics', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('key', 64)->unique('tai_rub_key_uq');
            $t->string('skill', 16);
            $t->string('task', 32);
            $t->timestamps();
        });
        Schema::create('tutor_ai_assessment_rubric_versions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('rubric_id');
            $t->unsignedInteger('version');
            $t->string('prompt_version', 64);
            $t->json('criteria');
            $t->string('fingerprint', 64);
            $t->timestamp('created_at');
            $t->unique(['rubric_id', 'version'], 'tai_rub_ver_uq');
            $t->unique(['rubric_id', 'fingerprint'], 'tai_rub_fp_uq');
            $t->foreign('rubric_id', 'tai_rub_ver_fk')->references('id')->on('tutor_ai_assessment_rubrics');
        });
        Schema::create('tutor_ai_writing_drafts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('actor_id', 191);
            $t->string('course_id', 191)->nullable();
            $t->string('lesson_id', 191)->nullable();
            $t->json('profile');
            $t->string('task', 32);
            $t->text('topic');
            $t->unsignedInteger('revision')->default(1);
            $t->longText('content');
            $t->timestamps();
            $t->index(['actor_id', 'updated_at'], 'tai_wr_owner_idx');
        });
        Schema::create('tutor_ai_writing_revisions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('draft_id');
            $t->unsignedInteger('revision');
            $t->longText('content');
            $t->timestamp('created_at');
            $t->unique(['draft_id', 'revision'], 'tai_wr_rev_uq');
            $t->foreign('draft_id', 'tai_wr_rev_fk')->references('id')->on('tutor_ai_writing_drafts');
        });
        Schema::create('tutor_ai_writing_submissions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('draft_id');
            $t->string('actor_id', 191);
            $t->string('course_id', 191)->nullable();
            $t->string('lesson_id', 191)->nullable();
            $t->unsignedInteger('revision');
            $t->longText('original');
            $t->json('profile');
            $t->string('task', 32);
            $t->text('topic');
            $t->uuid('rubric_version_id');
            $t->json('rubric_snapshot');
            $t->uuid('request_id')->unique('tai_ws_req_uq');
            $t->string('idempotency_key', 191)->unique('tai_ws_key_uq');
            $t->string('fingerprint', 64);
            $t->string('status', 32)->default('queued');
            $t->string('error_code', 64)->nullable();
            $t->json('result')->nullable();
            $t->string('provider', 64)->nullable();
            $t->string('model', 191)->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->foreign('draft_id', 'tai_ws_draft_fk')->references('id')->on('tutor_ai_writing_drafts');
            $t->foreign('rubric_version_id', 'tai_ws_rub_fk')->references('id')->on('tutor_ai_assessment_rubric_versions');
            $t->index(['actor_id', 'created_at'], 'tai_ws_owner_idx');
        });
        Schema::create('tutor_ai_writing_issues', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('submission_id');
            $t->string('category', 32);
            $t->unsignedInteger('start_utf16');
            $t->unsignedInteger('end_utf16');
            $t->text('original');
            $t->text('replacement');
            $t->text('explanation');
            $t->timestamp('created_at');
            $t->foreign('submission_id', 'tai_wi_sub_fk')->references('id')->on('tutor_ai_writing_submissions');
        });
        Schema::create('tutor_ai_speaking_attempts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('actor_id', 191);
            $t->string('course_id', 191)->nullable();
            $t->string('lesson_id', 191)->nullable();
            $t->json('profile');
            $t->string('task', 32);
            $t->text('topic');
            $t->uuid('rubric_version_id');
            $t->json('rubric_snapshot');
            $t->string('status', 32)->default('draft');
            $t->string('audio_path', 191)->nullable();
            $t->string('audio_mime', 64)->nullable();
            $t->unsignedBigInteger('audio_bytes')->nullable();
            $t->unsignedInteger('audio_duration_ms')->nullable();
            $t->string('audio_checksum', 64)->nullable();
            $t->longText('transcript')->nullable();
            $t->uuid('transcription_request_id')->nullable()->unique('tai_sp_stt_uq');
            $t->uuid('assessment_request_id')->nullable()->unique('tai_sp_req_uq');
            $t->string('error_code', 64)->nullable();
            $t->json('result')->nullable();
            $t->string('provider', 64)->nullable();
            $t->string('model', 191)->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->foreign('rubric_version_id', 'tai_sp_rub_fk')->references('id')->on('tutor_ai_assessment_rubric_versions');
            $t->index(['actor_id', 'created_at'], 'tai_sp_owner_idx');
        });
        Schema::create('tutor_ai_speaking_segments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('attempt_id');
            $t->unsignedInteger('position');
            $t->unsignedInteger('start_ms')->nullable();
            $t->unsignedInteger('end_ms')->nullable();
            $t->text('text');
            $t->json('evidence')->nullable();
            $t->timestamp('created_at');
            $t->unique(['attempt_id', 'position'], 'tai_sp_seg_uq');
            $t->foreign('attempt_id', 'tai_sp_seg_fk')->references('id')->on('tutor_ai_speaking_attempts');
        });
        Schema::create('tutor_ai_assessment_scores', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('writing_submission_id')->nullable();
            $t->uuid('speaking_attempt_id')->nullable();
            $t->string('criterion', 48);
            $t->string('status', 16);
            $t->decimal('score', 5, 2)->nullable();
            $t->json('evidence');
            $t->timestamp('created_at');
            $t->unique(['writing_submission_id', 'criterion'], 'tai_score_wr_uq');
            $t->unique(['speaking_attempt_id', 'criterion'], 'tai_score_sp_uq');
            $t->foreign('writing_submission_id', 'tai_score_wr_fk')->references('id')->on('tutor_ai_writing_submissions');
            $t->foreign('speaking_attempt_id', 'tai_score_sp_fk')->references('id')->on('tutor_ai_speaking_attempts');
        });
    }

    public function down(): void
    {
        throw new AiException('AI_DESTRUCTIVE_ROLLBACK_DISABLED');
    }
};
