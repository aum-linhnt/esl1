<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('target_audience')->nullable()->after('level');
            $table->boolean('certificate_enabled')->default(true)->after('is_published');
            $table->string('badge_reward')->nullable()->after('certificate_enabled');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->integer('estimated_minutes')->default(20)->after('order');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->string('type', 50)->default('vocabulary')->change();
            $table->integer('estimated_minutes')->default(5)->after('order');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['target_audience', 'certificate_enabled', 'badge_reward']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['description', 'estimated_minutes']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['estimated_minutes']);
        });
    }
};
