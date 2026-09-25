<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Core\AiException;

return new class extends Migration
{
    public function up(): void
    {
        // Preflight every target before the first DDL statement (MySQL DDL is not transactional).
        $targets = ['requests', 'credit_accounts', 'credit_rules', 'credit_transactions', 'usage_records', 'cost_snapshots'];
        $collisions = array_filter($targets, fn ($name) => Schema::hasTable('tutor_ai_'.$name));
        if ($collisions !== []) {
            throw new RuntimeException('AI schema collision / partial installation: '.implode(', ', array_map(fn ($name) => 'tutor_ai_'.$name, $collisions)));
        }

        Schema::create('tutor_ai_requests', function (Blueprint $t) {
            $t->id();
            $t->string('request_id', 36)->unique('tai_req_id_uq');
            $t->string('idempotency_key', 191)->unique('tai_req_key_uq');
            $t->string('fingerprint', 64);
            $t->string('user_id', 191)->nullable();
            $t->string('feature', 64);
            $t->string('billing_mode', 32);
            $t->string('provider', 64)->nullable();
            $t->string('model', 191)->nullable();
            $t->string('status', 24)->default('pending');
            $t->unsignedBigInteger('reserved_units')->default(0);
            $t->unsignedBigInteger('actual_units')->default(0);
            $t->string('remote_request_id', 191)->nullable();
            $t->string('error_code', 64)->nullable();
            $t->json('rule_snapshot')->nullable();
            $t->longText('encrypted_result')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamp('failed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'created_at'], 'tai_req_user_time_idx');
        });
        Schema::create('tutor_ai_credit_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('owner_type', 32);
            $t->string('owner_id', 191);
            $t->string('scope', 64)->default('system');
            // balance is available credit; reserved credit has already been deducted.
            $t->unsignedBigInteger('balance')->nullable();
            $t->unsignedBigInteger('daily_limit')->nullable();
            $t->unsignedBigInteger('weekly_limit')->nullable();
            $t->unsignedBigInteger('monthly_limit')->nullable();
            $t->string('status', 24)->default('active');
            $t->timestamps();
            $t->unique(['owner_type', 'owner_id', 'scope'], 'tai_account_owner_uq');
        });
        Schema::create('tutor_ai_credit_rules', function (Blueprint $t) {
            $t->id();
            $t->string('feature', 64)->unique('tai_rule_feature_uq');
            $t->unsignedInteger('base_units')->default(1);
            $t->unsignedInteger('max_units_per_request')->default(1);
            $t->json('blocks')->nullable();
            // Rate in micro-currency units per million tokens / audio second / item.
            $t->json('cost_rates')->nullable();
            $t->char('currency', 3)->default('USD');
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
        Schema::create('tutor_ai_credit_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id');
            $t->string('request_id', 36)->nullable();
            $t->string('type', 24);
            $t->unsignedBigInteger('units');
            $t->string('feature', 64)->nullable();
            $t->unsignedBigInteger('balance_before')->nullable();
            $t->unsignedBigInteger('balance_after')->nullable();
            $t->string('idempotency_key', 191)->unique('tai_tx_key_uq');
            $t->string('reference_type', 64)->nullable();
            $t->string('reference_id', 191)->nullable();
            $t->json('metadata')->nullable();
            $t->timestamp('created_at');
            $t->foreign('account_id', 'tai_tx_account_fk')->references('id')->on('tutor_ai_credit_accounts')->restrictOnDelete();
            $t->foreign('request_id', 'tai_tx_request_fk')->references('request_id')->on('tutor_ai_requests')->restrictOnDelete();
            $t->index(['account_id', 'created_at'], 'tai_tx_account_time_idx');
        });
        Schema::create('tutor_ai_usage_records', function (Blueprint $t) {
            $t->id();
            $t->string('request_id', 36)->unique('tai_usage_request_uq');
            $t->string('user_id', 191)->nullable();
            $t->string('feature', 64);
            $t->string('billing_mode', 32);
            $t->string('provider', 64);
            $t->string('model', 191);
            foreach (['input_tokens', 'output_tokens', 'cached_tokens', 'audio_seconds', 'image_count', 'document_pages', 'credit_units'] as $column) {
                $t->unsignedBigInteger($column)->default(0);
            }
            $t->decimal('estimated_cost', 20, 6)->nullable();
            $t->char('currency', 3);
            $t->string('provider_request_id', 191)->nullable();
            $t->string('remote_request_id', 191)->nullable();
            $t->unsignedBigInteger('latency_ms')->nullable();
            $t->string('status', 24);
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->foreign('request_id', 'tai_usage_request_fk')->references('request_id')->on('tutor_ai_requests')->restrictOnDelete();
            $t->index(['user_id', 'created_at'], 'tai_usage_user_time_idx');
        });
        Schema::create('tutor_ai_cost_snapshots', function (Blueprint $t) {
            $t->id();
            $t->string('request_id', 36)->unique('tai_cost_request_uq');
            $t->json('rates')->nullable();
            $t->char('currency', 3);
            $t->decimal('estimated_cost', 20, 6)->nullable();
            $t->timestamp('created_at');
            $t->foreign('request_id', 'tai_cost_request_fk')->references('request_id')->on('tutor_ai_requests')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new AiException('AI_DESTRUCTIVE_ROLLBACK_DISABLED');
    }
};
