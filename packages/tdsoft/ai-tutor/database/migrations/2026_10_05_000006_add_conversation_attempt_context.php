<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_ai_conversations', function (Blueprint $table) {
            $table->string('attempt_id', 191)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tutor_ai_conversations', fn (Blueprint $table) => $table->dropColumn('attempt_id'));
    }
};
