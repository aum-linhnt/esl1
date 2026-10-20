<?php

namespace App\Services\Learning;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AiCreditOverview
{
    public function account(User $user): ?array
    {
        if (! Schema::hasTable('tutor_ai_credit_accounts') || ! Schema::hasTable('tutor_ai_credit_transactions')) {
            return null;
        }
        $account = DB::table('tutor_ai_credit_accounts')->where([
            'owner_type' => 'learner', 'owner_id' => (string) $user->id, 'scope' => 'system',
        ])->first();
        if (! $account) {
            return null;
        }
        $held = (int) DB::table('tutor_ai_credit_transactions as r')->where('r.account_id', $account->id)->where('r.type', 'reserve')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('tutor_ai_credit_transactions as s')
                    ->whereColumn('s.account_id', 'r.account_id')->whereColumn('s.request_id', 'r.request_id')
                    ->whereIn('s.type', ['commit', 'release']);
            })->sum('r.units');
        $quotas = [];
        foreach (['daily_limit' => now()->startOfDay(), 'weekly_limit' => now()->startOfWeek(), 'monthly_limit' => now()->startOfMonth()] as $field => $start) {
            $spent = (int) DB::table('tutor_ai_credit_transactions')->where('account_id', $account->id)
                ->where('type', 'commit')->where('created_at', '>=', $start)->sum('units');
            $quotas[$field] = ['limit' => $account->$field, 'spent' => $spent,
                'remaining' => $account->$field === null ? null : max(0, (int) $account->$field - $spent - $held)];
        }
        return ['id' => $account->id, 'balance' => $account->balance, 'status' => $account->status, 'held' => $held, 'quotas' => $quotas];
    }

    public function history(User $user)
    {
        $account = $this->account($user);
        return $account ? DB::table('tutor_ai_credit_transactions')->where('account_id', $account['id'])
            ->select('type', 'units', 'feature', 'balance_after', 'created_at')->orderByDesc('created_at')->orderByDesc('id')->paginate(20) : null;
    }
}
