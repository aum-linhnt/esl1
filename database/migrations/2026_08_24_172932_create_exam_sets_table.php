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
        Schema::create('exam_sets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('skill'); // 'full_mock', 'vocabulary', 'grammar', 'reading', 'listening'
            $table->string('difficulty')->default('A1'); // 'A1', 'A2', 'B1', 'B2', 'Mixed'
            $table->integer('question_count')->default(10);
            $table->integer('duration_minutes')->default(15);
            $table->integer('reward_coins')->default(20);
            $table->text('description')->nullable();
            $table->json('sections')->nullable(); // For 4-skill mock tests e.g. {"vocabulary": 3, "grammar": 3, "reading": 2, "listening": 2}
            $table->json('question_ids')->nullable(); // Explicit QuestionBank IDs if manually picked
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sets');
    }
};
