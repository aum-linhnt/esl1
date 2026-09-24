<?php

namespace TDSoft\AiTutor\Billing;

use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\AiException;

final class CreditAccounts
{
    public function openLearner(string $actorId, ?int $dailyLimit = null): int
    {
        if ($actorId === '' || strlen($actorId) > 191 || ($dailyLimit !== null && $dailyLimit < 0)) {
            throw new AiException('AI_ACTOR_INVALID');
        }
        // Provisioning is a trusted server-side integration action, never a public endpoint.
        DB::table('tutor_ai_credit_accounts')->insertOrIgnore([
            'owner_type' => 'learner', 'owner_id' => $actorId, 'scope' => 'system', 'balance' => 0,
            'daily_limit' => $dailyLimit ?? config('ai-tutor.daily_credits', 10),
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return (int) DB::table('tutor_ai_credit_accounts')->where([
            'owner_type' => 'learner', 'owner_id' => $actorId, 'scope' => 'system',
        ])->value('id');
    }
}
