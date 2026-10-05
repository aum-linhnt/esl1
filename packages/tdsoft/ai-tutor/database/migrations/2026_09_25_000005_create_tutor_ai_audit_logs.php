<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Core\AiException;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(CreditAdminSchema::TABLE)) {
            throw new RuntimeException('AI Tutor audit schema collision / partial installation');
        }
        Schema::create(CreditAdminSchema::TABLE, function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_id')->unique('tai_audit_operation_uq');
            $t->string('actor_id', 191);
            $t->string('action', 64);
            $t->string('target_id', 191);
            $t->string('fingerprint', 64);
            $t->json('details');
            $t->timestamp('created_at');
            $t->index(['actor_id', 'created_at'], 'tai_audit_actor_time_idx');
        });
    }

    public function down(): void
    {
        throw new AiException('AI_DESTRUCTIVE_ROLLBACK_DISABLED');
    }
};
