<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_study_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 16);
            $table->unsignedBigInteger('context_id')->nullable();
            $table->unsignedInteger('claimed_seconds')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('last_reported_at');
            $table->index(['user_id', 'started_at'], 'study_session_owner_index');
        });
        Schema::create('learner_study_intervals', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->foreign('session_id')->references('id')->on('learner_study_sessions')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
            $table->index(['session_id', 'started_at'], 'study_interval_session_index');
            $table->index('ended_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_study_intervals');
        Schema::dropIfExists('learner_study_sessions');
    }
};
