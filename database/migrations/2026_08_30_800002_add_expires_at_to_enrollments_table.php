<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('completed_at');
            $table->index(['course_id', 'expires_at']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'expires_at']);
            $table->dropIndex(['user_id', 'expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
