<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('allow_self_enrollment')->default(true)->after('is_published');
            $table->string('enrollment_key', 100)->nullable()->after('allow_self_enrollment');
            $table->integer('enrollment_duration_days')->nullable()->after('enrollment_key');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['allow_self_enrollment', 'enrollment_key', 'enrollment_duration_days']);
        });
    }
};
