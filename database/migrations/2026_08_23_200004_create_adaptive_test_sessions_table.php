<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adaptive_test_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('current_difficulty', 10)->default('A1');
            $table->integer('consecutive_correct')->default(0);
            $table->integer('consecutive_wrong')->default(0);
            $table->json('question_history')->nullable(); // Array of question IDs answered
            $table->json('answers_history')->nullable();  // Detailed logs per answered question
            $table->integer('total_questions_answered')->default(0);
            $table->integer('correct_count')->default(0);
            $table->integer('score')->default(0);
            $table->enum('status', ['active', 'finished'])->default('active');
            $table->string('final_level')->nullable(); // A1, A2, B1, B2
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adaptive_test_sessions');
    }
};
