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
        Schema::table('files', function (Blueprint $table) {
            $table->string('folder', 50)->default('general')->after('path');
            $table->boolean('is_temp')->default(false)->after('reference_count');
            $table->timestamp('expires_at')->nullable()->after('is_temp');
            $table->json('metadata')->nullable()->after('expires_at');

            $table->index('folder');
            $table->index('is_temp');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['folder']);
            $table->dropIndex(['is_temp']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['folder', 'is_temp', 'expires_at', 'metadata']);
        });
    }
};
