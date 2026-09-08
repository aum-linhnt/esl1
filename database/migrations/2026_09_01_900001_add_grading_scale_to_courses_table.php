<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds configurable grading scale and passing grade to courses table.
     * Supports: scale_100 (Percentage), scale_10 (Vietnam 10-point), scale_4 (GPA 4.0),
     *           scale_ielts (IELTS Band 1-9), scale_pass_fail (Competency-based Pass/Fail).
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('grading_scale', 20)->default('scale_100')->after('enrollment_duration_days');
            $table->decimal('passing_grade', 5, 2)->default(50.00)->after('grading_scale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['grading_scale', 'passing_grade']);
        });
    }
};
