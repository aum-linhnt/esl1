<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Billing\CreditAccounts;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Core\Models\AiRequestRecord;
use TDSoft\AiTutor\Core\Models\CreditTransaction;
use TDSoft\AiTutor\Core\Models\UsageRecord;
use TDSoft\AiTutor\Providers\CredentialResolver;
use TDSoft\AiTutor\Providers\ProviderManager;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class ModelsTest extends FoundationTestCase
{
    public function test_history_models_reject_mutation_and_request_model_hides_result(): void
    {
        app(AiExecutionService::class)->execute($this->request());
        $usage = UsageRecord::firstOrFail();
        $usage->credit_units = 999;
        $this->assertError('AI_HISTORY_IMMUTABLE', fn () => $usage->save());
        $this->assertError('AI_HISTORY_IMMUTABLE', fn () => CreditTransaction::firstOrFail()->delete());
        $this->assertArrayNotHasKey('encrypted_result', AiRequestRecord::firstOrFail()->toArray());
    }

    public function test_provisioning_does_not_reset_existing_balance_or_limits(): void
    {
        $id = app(CreditAccounts::class)->openLearner('learner-1', 1);
        $account = DB::table('tutor_ai_credit_accounts')->find($id);
        $this->assertSame(100, $account->balance);
        $this->assertSame(50, $account->daily_limit);
    }

    public function test_credentials_are_provider_scoped_and_mock_is_denied_in_production(): void
    {
        config(['ai-tutor.credentials' => ['gemini' => 'unit-test-secret']]);
        $this->assertSame('unit-test-secret', app(CredentialResolver::class)->resolve('gemini'));
        $this->assertError('AI_PROVIDER_NOT_CONFIGURED', fn () => app(CredentialResolver::class)->resolve('openai'));
        $this->app->instance('env', 'production');
        $this->assertError('AI_PROVIDER_NOT_CONFIGURED', fn () => app(ProviderManager::class)->driver());
        $this->assertError('AI_PROVIDER_NOT_CONFIGURED', fn () => $this->provider->execute($this->request()));
    }
}
