<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_skills', function (Blueprint $table) {
            $table->string('skill_type', 50)->change();
            $table->string('assessed_level', 20)->default('A1')->change();
        });
    }

    public function down(): void
    {
        // No-op or keep as varchar
    }
};
