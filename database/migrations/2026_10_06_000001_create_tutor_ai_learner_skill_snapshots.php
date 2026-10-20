<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_ai_learner_skill_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill', 16);
            $table->string('source', 32);
            $table->string('assessment_id', 191);
            $table->decimal('score', 6, 2);
            $table->string('score_scale', 32);
            $table->string('rubric_version', 191)->nullable();
            $table->json('criteria')->nullable();
            $table->json('issues')->nullable();
            $table->timestamp('assessed_at');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'source', 'assessment_id'], 'tai_skill_source_unique');
            $table->index(['user_id', 'skill', 'assessed_at'], 'tai_skill_history_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_ai_learner_skill_snapshots');
    }
};
