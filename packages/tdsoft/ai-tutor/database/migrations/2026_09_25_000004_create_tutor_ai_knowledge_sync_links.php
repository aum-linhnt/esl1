<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Knowledge\SyncSchema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(SyncSchema::TABLE)) {
            throw new RuntimeException('AI Tutor sync schema collision / partial installation: '.SyncSchema::TABLE);
        }
        Schema::create(SyncSchema::TABLE, function (Blueprint $t) {
            $t->string('id', 64)->primary();
            $t->uuid('document_id')->nullable();
            $t->uuid('version_id')->nullable();
            $t->string('fingerprint', 64)->nullable();
            $t->timestamps();
            $t->foreign('document_id', 'tai_sync_doc_fk')->references('id')->on('tutor_ai_knowledge_documents');
            $t->foreign('version_id', 'tai_sync_ver_fk')->references('id')->on('tutor_ai_knowledge_document_versions');
        });
    }

    public function down(): void
    {
        throw new AiException('AI_DESTRUCTIVE_ROLLBACK_DISABLED');
    }
};
