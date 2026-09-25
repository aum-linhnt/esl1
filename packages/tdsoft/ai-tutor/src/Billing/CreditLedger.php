<?php

namespace TDSoft\AiTutor\Billing;

use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiRequest;

final class CreditLedger
{
    public function reserve(AiRequest $request): BillingAuthorization
    {
        return DB::transaction(function () use ($request) {
            $record = DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->lockForUpdate()->first();
            if (! $record || $record->status !== 'pending') {
                throw new AiException('AI_REQUEST_DUPLICATE');
            }
            $rule = json_decode($record->rule_snapshot, true, flags: JSON_THROW_ON_ERROR);
            $units = $rule['max_units_per_request'];
            $account = DB::table('tutor_ai_credit_accounts')->where([
                'owner_type' => 'learner', 'owner_id' => $request->actor->id, 'scope' => 'system',
            ])->lockForUpdate()->first();
            if (! $account || $account->status !== 'active') {
                throw new AiException('AI_CREDIT_INSUFFICIENT');
            }
            if ($account->balance !== null && $account->balance < $units) {
                throw new AiException('AI_CREDIT_INSUFFICIENT');
            }
            foreach (['daily_limit' => now()->startOfDay(), 'weekly_limit' => now()->startOfWeek(), 'monthly_limit' => now()->startOfMonth()] as $field => $start) {
                $limit = $account->$field;
                if ($limit === null) {
                    continue;
                }
                // Outstanding reservations count, including those from a previous quota window.
                $spent = DB::table('tutor_ai_credit_transactions')->where('account_id', $account->id)
                    ->where('type', 'commit')->where('created_at', '>=', $start)->sum('units');
                $held = DB::table('tutor_ai_credit_transactions as r')->where('r.account_id', $account->id)->where('r.type', 'reserve')
                    ->whereNotExists(function ($query) {
                        $query->selectRaw('1')->from('tutor_ai_credit_transactions as s')
                            ->whereColumn('s.request_id', 'r.request_id')->whereIn('s.type', ['commit', 'release']);
                    })->sum('r.units');
                if ($spent + $held + $units > $limit) {
                    throw new AiException('AI_DAILY_LIMIT_REACHED');
                }
            }
            $after = $account->balance === null ? null : $account->balance - $units;
            $this->append($account, $request, 'reserve', $units, $after);
            DB::table('tutor_ai_credit_accounts')->where('id', $account->id)->update(['balance' => $after, 'updated_at' => now()]);
            DB::table('tutor_ai_requests')->where('id', $record->id)->update(['status' => 'authorized', 'reserved_units' => $units, 'updated_at' => now()]);

            return new BillingAuthorization($account->id, $units);
        }, 3);
    }

    public function settle(AiRequest $request, BillingAuthorization $authorization, int $actual): void
    {
        $this->finish($request, $authorization, $actual);
    }

    public function release(AiRequest $request, BillingAuthorization $authorization): void
    {
        $this->finish($request, $authorization, null);
    }

    private function finish(AiRequest $request, BillingAuthorization $authorization, ?int $actual): void
    {
        DB::transaction(function () use ($request, $authorization, $actual) {
            $record = DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->lockForUpdate()->first();
            $account = DB::table('tutor_ai_credit_accounts')->where('id', $authorization->accountId)->lockForUpdate()->first();
            $reservation = DB::table('tutor_ai_credit_transactions')->where('request_id', $request->requestId)->where('type', 'reserve')->first();
            if (! $record || ! $account || ! $reservation || $reservation->account_id !== $account->id
                || (int) $reservation->units !== $authorization->reservedUnits || ($actual !== null && ($actual < 0 || $actual > $reservation->units))) {
                throw new AiException('AI_RESERVATION_INVALID');
            }
            if (DB::table('tutor_ai_credit_transactions')->where('request_id', $request->requestId)->whereIn('type', ['commit', 'release'])->exists()) {
                return;
            }
            $refund = $authorization->reservedUnits - ($actual ?? 0);
            if ($actual !== null) {
                // Reservation already removed available units; commit never debits them twice.
                $this->append($account, $request, 'commit', $actual, $account->balance);
            }
            if ($refund > 0 || $actual === null) {
                $after = $account->balance === null ? null : $account->balance + $refund;
                $this->append($account, $request, 'release', $refund, $after);
                DB::table('tutor_ai_credit_accounts')->where('id', $account->id)->update(['balance' => $after, 'updated_at' => now()]);
            }
        }, 3);
    }

    public function grant(string $ownerId, int $units, string $key): void
    {
        if ($units <= 0 || $units > 1000000000 || $key === '' || strlen($key) > 180) {
            throw new AiException('AI_CREDIT_RULE_INVALID');
        }
        DB::transaction(function () use ($ownerId, $units, $key) {
            $account = DB::table('tutor_ai_credit_accounts')->where(['owner_type' => 'learner', 'owner_id' => $ownerId, 'scope' => 'system'])->lockForUpdate()->first();
            if (! $account || $account->balance === null) {
                throw new AiException('AI_RESERVATION_INVALID');
            }
            $existing = DB::table('tutor_ai_credit_transactions')->where('idempotency_key', 'grant:'.$key)->first();
            if ($existing) {
                if ((int) $existing->account_id !== (int) $account->id || (int) $existing->units !== $units) {
                    throw new AiException('AI_REQUEST_DUPLICATE');
                }

                return;
            }
            DB::table('tutor_ai_credit_transactions')->insert([
                'account_id' => $account->id, 'type' => 'grant', 'units' => $units, 'idempotency_key' => 'grant:'.$key,
                'balance_before' => $account->balance, 'balance_after' => $account->balance + $units, 'created_at' => now(),
            ]);
            DB::table('tutor_ai_credit_accounts')->where('id', $account->id)->update(['balance' => $account->balance + $units, 'updated_at' => now()]);
        }, 3);
    }

    private function append(object $account, AiRequest $request, string $type, int $units, ?int $after): void
    {
        DB::table('tutor_ai_credit_transactions')->insert([
            'account_id' => $account->id, 'request_id' => $request->requestId, 'type' => $type,
            'units' => $units, 'feature' => $request->feature, 'balance_before' => $account->balance,
            'balance_after' => $after, 'idempotency_key' => $request->requestId.':'.$type, 'created_at' => now(),
        ]);
    }
}
