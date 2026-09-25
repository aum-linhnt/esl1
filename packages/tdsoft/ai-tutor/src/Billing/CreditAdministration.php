<?php

namespace TDSoft\AiTutor\Billing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Core\AiException;

final class CreditAdministration
{
    public function __construct(private CreditAdministrator $admin, private CreditAccounts $accounts,
        private CreditLedger $ledger, private CreditCalculator $calculator) {}

    public function authorize(): string
    {
        $actor = $this->admin->actorId();
        if ($actor === null || $actor === '') {
            throw new AiException('AI_CREDIT_ADMIN_FORBIDDEN');
        }

        return $actor;
    }

    public function ruleToken(?object $rule): string
    {
        return hash('sha256', json_encode($rule ? (array) $rule : null, JSON_THROW_ON_ERROR));
    }

    public function saveRule(string $feature, int $base, int $max, bool $enabled, string $expected, string $operation): void
    {
        $this->authorize();
        if (! array_key_exists($feature, config('ai-tutor.features', []))) {
            throw new AiException('AI_FEATURE_INVALID');
        }
        $this->operation($operation, 'credit_rule.update', $feature, [$base, $max, $enabled, $expected],
            function () use ($feature, $base, $max, $enabled, $expected) {
                $old = DB::table('tutor_ai_credit_rules')->where('feature', $feature)->lockForUpdate()->first();
                if (! hash_equals($this->ruleToken($old), $expected)) {
                    throw new AiException('AI_CREDIT_RULE_CHANGED');
                }
                $rule = ['base_units' => $base, 'max_units_per_request' => $max,
                    'blocks' => $old?->blocks ? json_decode($old->blocks, true, flags: JSON_THROW_ON_ERROR) : [],
                    'cost_rates' => $old?->cost_rates ? json_decode($old->cost_rates, true, flags: JSON_THROW_ON_ERROR) : null,
                    'currency' => $old?->currency ?? 'USD'];
                $this->calculator->validate($rule);
                $values = ['base_units' => $base, 'max_units_per_request' => $max, 'enabled' => $enabled, 'updated_at' => now()];
                if ($old) {
                    DB::table('tutor_ai_credit_rules')->where('id', $old->id)->update($values);
                } else {
                    DB::table('tutor_ai_credit_rules')->insert($values + ['feature' => $feature, 'created_at' => now()]);
                }

                return ['before' => $old, 'after' => DB::table('tutor_ai_credit_rules')->where('feature', $feature)->first()];
            });
    }

    public function grant(string $recipient, int $units, string $reason, string $operation): void
    {
        $this->authorize();
        $person = $this->admin->recipient($recipient);
        if ($person['id'] !== $recipient || $units < 1 || $units > 1000000 || trim($reason) === '' || mb_strlen($reason) > 500) {
            throw new AiException('AI_CREDIT_GRANT_INVALID');
        }
        $this->operation($operation, 'credit.grant', $recipient, [$units, $reason], function () use ($recipient, $units, $reason, $operation) {
            $accountId = $this->accounts->openLearner($recipient);
            $account = DB::table('tutor_ai_credit_accounts')->where('id', $accountId)->lockForUpdate()->first();
            if ($account->status !== 'active' || $account->balance === null) {
                throw new AiException('AI_RESERVATION_INVALID');
            }
            $this->ledger->grant($recipient, $units, 'admin:'.$operation);

            return ['account_id' => $accountId, 'units' => $units, 'reason' => $reason,
                'balance_before' => $account->balance,
                'balance_after' => DB::table('tutor_ai_credit_accounts')->where('id', $accountId)->value('balance')];
        });
    }

    private function operation(string $id, string $action, string $target, array $input, callable $work): void
    {
        $actor = $this->authorize();
        if (! Str::isUuid($id)) {
            throw new AiException('AI_REQUEST_INVALID');
        }
        if (! Schema::hasTable(CreditAdminSchema::TABLE)) {
            throw new AiException('AI_CREDIT_ADMIN_MIGRATION_REQUIRED');
        }
        $fingerprint = hash('sha256', json_encode([$actor, $action, $target, $input], JSON_THROW_ON_ERROR));
        DB::transaction(function () use ($id, $action, $target, $actor, $fingerprint, $work) {
            $inserted = DB::table(CreditAdminSchema::TABLE)->insertOrIgnore([
                'operation_id' => $id, 'actor_id' => $actor, 'action' => $action, 'target_id' => $target,
                'fingerprint' => $fingerprint, 'details' => '{}', 'created_at' => now(),
            ]);
            $record = DB::table(CreditAdminSchema::TABLE)->where('operation_id', $id)->lockForUpdate()->first();
            if (! $record || ! hash_equals($record->fingerprint, $fingerprint)) {
                throw new AiException('AI_REQUEST_DUPLICATE');
            }
            if (! $inserted) {
                return;
            }
            $details = $work();
            // Finalize new audit within this transaction; committed entries are never edited.
            DB::table(CreditAdminSchema::TABLE)->where('id', $record->id)->update(['details' => json_encode($details, JSON_THROW_ON_ERROR)]);
        }, 3);
    }
}
