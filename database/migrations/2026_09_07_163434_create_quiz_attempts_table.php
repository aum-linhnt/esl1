<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedInteger('attempt_number')->default(1);
                $table->string('status', 30)->default('completed'); // in_progress, completed, abandoned, timed_out
                $table->decimal('score', 5, 2)->default(0.00);
                $table->decimal('max_score', 5, 2)->default(100.00);
                $table->decimal('percentage', 5, 2)->default(0.00);
                $table->boolean('is_passed')->default(false);
                $table->unsignedInteger('time_spent_seconds')->default(0);
                $table->json('answers_payload')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->index(['activity_id', 'user_id', 'attempt_number']);
            });
        }

        if (Schema::hasTable('activities') && !Schema::hasColumn('activities', 'grading_method')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->string('grading_method', 20)->default('highest')->after('passing_grade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');

        if (Schema::hasTable('activities') && Schema::hasColumn('activities', 'grading_method')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropColumn('grading_method');
            });
        }
    }
};
