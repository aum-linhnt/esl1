<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action', 50)->index(); // login, logout, view_course, view_lesson, view_activity, view_exam, submit_exam, enroll, unenroll, complete_activity, complete_lesson
            $table->text('description')->nullable();
            $table->nullableMorphs('loggable'); // loggable_type, loggable_id — polymorphic to Course, Lesson, Activity, ExamSet, etc.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // extra context data
            $table->timestamp('created_at')->useCurrent()->index();

            // Composite indexes for common queries
            $table->index(['user_id', 'action']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_action_logs');
    }
};
