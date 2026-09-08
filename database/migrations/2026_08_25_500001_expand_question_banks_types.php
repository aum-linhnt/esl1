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
        Schema::table('question_banks', function (Blueprint $table) {
            $table->string('question_type', 50)->default('mcq')->change();
            $table->string('difficulty', 20)->default('A1')->change();
            $table->string('skill', 50)->default('vocabulary')->change();
            $table->text('correct_answer')->change();
            $table->string('audio_url')->nullable()->after('options');
            $table->json('meta_data')->nullable()->after('explanation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn(['audio_url', 'meta_data']);
        });
    }
};
