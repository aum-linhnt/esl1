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
        if (Schema::hasTable('question_banks')) {
            Schema::table('question_banks', function (Blueprint $table) {
                if (!Schema::hasColumn('question_banks', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('difficulty');
                }
                if (!Schema::hasColumn('question_banks', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('version');
                    $table->foreign('parent_id')->references('id')->on('question_banks')->onDelete('set null');
                }
                if (!Schema::hasColumn('question_banks', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        if (Schema::hasTable('assessment_submissions')) {
            Schema::table('assessment_submissions', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_submissions', 'attempt_number')) {
                    $table->unsignedInteger('attempt_number')->default(1)->after('test_type');
                }
                if (!Schema::hasColumn('assessment_submissions', 'time_spent_seconds')) {
                    $table->unsignedInteger('time_spent_seconds')->default(0)->after('attempt_number');
                }
                if (!Schema::hasColumn('assessment_submissions', 'status')) {
                    $table->string('status', 30)->default('completed')->after('time_spent_seconds');
                }
                if (!Schema::hasColumn('assessment_submissions', 'started_at')) {
                    $table->dateTime('started_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('assessment_submissions', 'completed_at')) {
                    $table->dateTime('completed_at')->nullable()->after('started_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('question_banks')) {
            Schema::table('question_banks', function (Blueprint $table) {
                if (Schema::hasColumn('question_banks', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn('question_banks', 'parent_id')) {
                    $table->dropForeign(['parent_id']);
                    $table->dropColumn('parent_id');
                }
                if (Schema::hasColumn('question_banks', 'version')) {
                    $table->dropColumn('version');
                }
            });
        }

        if (Schema::hasTable('assessment_submissions')) {
            Schema::table('assessment_submissions', function (Blueprint $table) {
                $columns = ['attempt_number', 'time_spent_seconds', 'status', 'started_at', 'completed_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('assessment_submissions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
