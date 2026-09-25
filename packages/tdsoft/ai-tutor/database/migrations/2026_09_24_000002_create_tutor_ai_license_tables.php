<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Core\AiException;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['tutor_ai_license_state', 'tutor_ai_license_refresh_attempts'];
        $existing = array_values(array_filter($tables, fn ($table) => Schema::hasTable($table)));
        if ($existing !== []) {
            throw new RuntimeException('AI license schema collision / partial installation: '.implode(', ', $existing));
        }
        Schema::create('tutor_ai_license_state', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->string('installation_id', 191)->unique('tai_lic_install_uq');
            $t->longText('encrypted_envelope')->nullable();
            $t->text('encrypted_license_key')->nullable();
            $t->timestamp('last_verified_at')->nullable();
            $t->timestamp('last_refreshed_at')->nullable();
            $t->timestamp('next_attempt_at')->nullable();
            $t->unsignedInteger('failure_count')->default(0);
            $t->string('error_code', 64)->nullable();
            $t->string('refresh_token', 36)->nullable();
            $t->timestamp('refresh_lease_until')->nullable();
            $t->timestamps();
        });
        Schema::create('tutor_ai_license_refresh_attempts', function (Blueprint $t) {
            $t->id();
            $t->string('request_id', 36)->unique('tai_lic_attempt_req_uq');
            $t->string('operation', 16);
            $t->string('actor_id', 191)->nullable();
            $t->string('status', 16);
            $t->string('error_code', 64)->nullable();
            $t->timestamp('started_at');
            $t->timestamp('completed_at')->nullable();
            $t->index('started_at', 'tai_lic_attempt_time_idx');
        });
    }

    public function down(): void
    {
        throw new AiException('AI_DESTRUCTIVE_ROLLBACK_DISABLED');
    }
};
