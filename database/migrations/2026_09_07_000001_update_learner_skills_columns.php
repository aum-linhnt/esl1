<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE learner_skills MODIFY COLUMN skill_type VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE learner_skills MODIFY COLUMN assessed_level VARCHAR(20) NOT NULL DEFAULT 'A1'");
    }

    public function down(): void
    {
        // No-op or keep as varchar
    }
};
