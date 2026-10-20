<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Core\AiException;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_ai_writing_submissions', function (Blueprint $t) {
            $t->longText('encrypted_payload');
            $t->string('feature', 32)->default('writing_assessment');
            $t->uuid('retry_of_submission_id')->nullable()->unique('tai_ws_retry_uq');
            $t->foreign('retry_of_submission_id', 'tai_ws_retry_fk')->references('id')->on('tutor_ai_writing_submissions');
        });
    }

    public function down(): void
    {
        throw new AiException('AI_DESTRUCTIVE_ROLLBACK_DISABLED');
    }
};
