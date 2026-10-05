<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('ai_answer_policy', 32)->default('hints_only');
            $table->boolean('ai_teacher_solution_allowed')->default(false);
            $table->boolean('ai_exam_mode')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['ai_answer_policy', 'ai_teacher_solution_allowed', 'ai_exam_mode']);
        });
    }
};
