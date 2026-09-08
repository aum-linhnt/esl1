<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('set null');
            $table->string('test_type')->default('lesson_quiz'); // lesson_quiz, adaptive_test, practice
            $table->integer('total_score')->default(0);
            $table->integer('max_score')->default(0);
            $table->float('accuracy_rate')->default(0.0); // e.g. 85.5%
            $table->boolean('is_passed')->default(false);
            $table->json('answers_payload')->nullable(); // detailed answers array
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_submissions');
    }
};
