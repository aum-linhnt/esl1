<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;

return new class extends Migration
{
    public function up(): void
    {
        $existing = array_filter(array_keys(PhaseThreeSchema::COLUMNS), fn ($table) => Schema::hasTable($table));
        if ($existing) {
            throw new RuntimeException('AI Tutor Phase 3 collision / partial installation: '.implode(', ', $existing));
        }
        Schema::create('tutor_ai_knowledge_documents', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('title', 191);
            $t->string('course_id', 191);
            $t->string('lesson_id', 191);
            $t->string('subject', 64);
            $t->string('level', 64)->default('');
            $t->string('visibility', 16)->default('private');
            $t->boolean('contains_answers')->default(false);
            $t->uuid('published_version_id')->nullable();
            $t->string('created_by', 191);
            $t->timestamps();
            $t->index(['course_id', 'lesson_id'], 'tai_doc_context_idx');
        });
        Schema::create('tutor_ai_knowledge_document_versions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('document_id');
            $t->string('status', 16)->default('draft');
            $t->string('format', 16);
            $t->longText('content');
            $t->string('checksum', 64);
            $t->string('created_by', 191);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->foreign('document_id', 'tai_ver_doc_fk')->references('id')->on('tutor_ai_knowledge_documents');
        });
        Schema::create('tutor_ai_knowledge_chunks', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('version_id');
            $t->unsignedInteger('position');
            $t->text('content');
            $t->json('embedding')->nullable();
            $t->boolean('indexed')->default(false);
            $t->string('embedding_model', 191)->nullable();
            $t->unique(['version_id', 'position'], 'tai_chunk_pos_uq');
            $t->foreign('version_id', 'tai_chunk_ver_fk')->references('id')->on('tutor_ai_knowledge_document_versions');
        });
        Schema::create('tutor_ai_knowledge_processing_jobs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('version_id')->unique('tai_kjob_version_uq');
            $t->string('actor_id', 191);
            $t->string('status', 16)->default('pending');
            $t->unsignedInteger('attempts')->default(0);
            $t->string('error_code', 64)->nullable();
            $t->timestamps();
            $t->foreign('version_id', 'tai_kjob_ver_fk')->references('id')->on('tutor_ai_knowledge_document_versions');
        });
        Schema::create('tutor_ai_conversations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('user_id', 191);
            $t->string('course_id', 191);
            $t->string('lesson_id', 191);
            $t->string('question_id', 191)->nullable();
            $t->string('teaching_mode', 32);
            $t->timestamps();
            $t->index(['user_id', 'created_at'], 'tai_conv_owner_idx');
        });
        Schema::create('tutor_ai_conversation_messages', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('conversation_id');
            $t->string('idempotency_key', 191)->unique('tai_msg_key_uq');
            $t->string('fingerprint', 64);
            $t->uuid('request_id')->unique('tai_msg_req_uq');
            $t->uuid('embedding_request_id');
            $t->text('user_content');
            $t->longText('content')->nullable();
            $t->longText('encrypted_payload')->nullable();
            $t->string('status', 16)->default('pending');
            $t->string('error_code', 64)->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->foreign('conversation_id', 'tai_msg_conv_fk')->references('id')->on('tutor_ai_conversations');
            $t->index(['conversation_id', 'created_at'], 'tai_msg_conv_time_idx');
        });
        Schema::create('tutor_ai_message_sources', function (Blueprint $t) {
            $t->id();
            $t->uuid('message_id');
            $t->uuid('chunk_id');
            $t->unsignedInteger('citation');
            $t->unique(['message_id', 'chunk_id'], 'tai_source_chunk_uq');
            $t->foreign('message_id', 'tai_source_msg_fk')->references('id')->on('tutor_ai_conversation_messages');
            $t->foreign('chunk_id', 'tai_source_chunk_fk')->references('id')->on('tutor_ai_knowledge_chunks');
        });
        Schema::create('tutor_ai_message_feedback', function (Blueprint $t) {
            $t->id();
            $t->uuid('message_id');
            $t->string('user_id', 191);
            $t->string('rating', 16);
            $t->timestamps();
            $t->unique(['message_id', 'user_id'], 'tai_feedback_user_uq');
            $t->foreign('message_id', 'tai_feedback_msg_fk')->references('id')->on('tutor_ai_conversation_messages');
        });
    }

    public function down(): void
    {
        throw new AiException('AI_DESTRUCTIVE_ROLLBACK_DISABLED');
    }
};
