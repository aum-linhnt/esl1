<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Enhance activities table with LMS fields ──
        Schema::table('activities', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->boolean('is_visible')->default(true)->after('estimated_minutes');
            $table->timestamp('available_from')->nullable()->after('is_visible');
            $table->timestamp('available_until')->nullable()->after('available_from');
            $table->string('completion_type', 30)->default('manual')->after('available_until'); // manual, auto_view, auto_grade, auto_submit
            $table->decimal('passing_grade', 5, 2)->nullable()->after('completion_type');
            $table->integer('max_attempts')->nullable()->after('passing_grade');
            $table->integer('time_limit_minutes')->nullable()->after('max_attempts');
            $table->string('file_path')->nullable()->after('time_limit_minutes');
            $table->integer('file_size')->nullable()->after('file_path');
            $table->string('file_original_name')->nullable()->after('file_size');
        });

        // ── Enhance lessons table with visibility & summary ──
        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('is_free_trial');
            $table->text('summary')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'is_visible', 'available_from', 'available_until',
                'completion_type', 'passing_grade', 'max_attempts', 'time_limit_minutes',
                'file_path', 'file_size', 'file_original_name',
            ]);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['is_visible', 'summary']);
        });
    }
};
