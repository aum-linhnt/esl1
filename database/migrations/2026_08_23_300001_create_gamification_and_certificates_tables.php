<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('streak_count')->default(1)->after('coins');
            $table->date('last_active_date')->nullable()->after('streak_count');
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('badge_key'); // e.g. first_step, streak_7, grammar_guru, ai_scholar
            $table->string('badge_name');
            $table->string('badge_icon')->default('🏆');
            $table->text('description')->nullable();
            $table->timestamp('unlocked_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'badge_key']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('certificate_code')->unique(); // e.g. ESL-2026-A2-XXXXXX
            $table->string('level')->default('A2');
            $table->integer('final_score')->default(100);
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('user_badges');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['streak_count', 'last_active_date']);
        });
    }
};
