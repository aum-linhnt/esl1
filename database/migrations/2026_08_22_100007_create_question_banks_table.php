<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->enum('skill', ['vocabulary', 'grammar', 'reading', 'listening', 'vocab'])->default('vocabulary');
            $table->enum('difficulty', ['A1', 'A2', 'B1'])->default('A1');
            $table->enum('question_type', ['mcq', 'fill_blank'])->default('mcq');
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->string('correct_answer');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
