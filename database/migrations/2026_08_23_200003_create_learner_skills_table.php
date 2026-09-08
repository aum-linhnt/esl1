<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('skill_type', ['vocabulary', 'grammar', 'listening', 'reading']);
            $table->integer('mastery_score')->default(0); // 0-100
            $table->enum('assessed_level', ['A1', 'A2', 'B1', 'B2'])->default('A1');
            $table->timestamp('last_assessed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'skill_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_skills');
    }
};
