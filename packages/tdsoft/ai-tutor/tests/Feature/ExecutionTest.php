<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Billing\BillingManager;
use TDSoft\AiTutor\Billing\CreditLedger;
use TDSoft\AiTutor\Billing\VendorCreditBillingDriver;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\AiResponse;
use TDSoft\AiTutor\Core\LearnerIdentity;
use TDSoft\AiTutor\Licensing\UnavailableEntitlements;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class ExecutionTest extends FoundationTestCase
{
    public function test_success_records_usage_and_settles_only_actual_units(): void
    {
        $request = $this->request();
        $response = app(AiExecutionService::class)->execute($request);
        $this->assertSame('Mock response', $response->content);
        $this->assertSame(1, $this->provider->calls);
        $this->assertSame(98, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(['reserve', 'commit', 'release'], DB::table('tutor_ai_credit_transactions')->orderBy('id')->pluck('type')->all());
        $this->assertSame([5, 2, 3], DB::table('tutor_ai_credit_transactions')->orderBy('id')->pluck('units')->all());
        $record = DB::table('tutor_ai_requests')->first();
        $this->assertSame('completed', $record->status);
        $this->assertStringNotContainsString('Mock response', $record->encrypted_result);
        $this->assertStringContainsString('Mock response', Crypt::decryptString($record->encrypted_result));
        $this->assertSame('customer_key', DB::table('tutor_ai_usage_records')->value('billing_mode'));
        $this->assertEquals('0.000020', DB::table('tutor_ai_cost_snapshots')->value('estimated_cost'));
        $this->assertSame('Safe lesson context', $this->provider->lastRequest->context->content);
    }

    public function test_duplicate_replays_encrypted_result_without_another_charge(): void
    {
        $service = app(AiExecutionService::class);
        $first = $service->execute($this->request());
        $again = $service->execute($this->request());
        $this->assertEquals($first, $again);
        $this->assertSame(1, $this->provider->calls);
        $this->assertSame(1, DB::table('tutor_ai_usage_records')->count());
        $this->assertSame(3, DB::table('tutor_ai_credit_transactions')->count());
        $this->assertError('AI_REQUEST_DUPLICATE', fn () => $service->execute($this->request(payload: ['message' => 'Changed'])));
    }

    public function test_same_request_id_with_another_key_cannot_run_twice(): void
    {
        $request = $this->request();
        $service = app(AiExecutionService::class);
        $service->execute($request);
        $other = new AiRequest($request->feature, $request->actor, $request->payload, $request->requestId, 'another-key');
        $this->assertError('AI_REQUEST_DUPLICATE', fn () => $service->execute($other));
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_quota_or_balance_failure_never_calls_provider(): void
    {
        DB::table('tutor_ai_credit_accounts')->update(['daily_limit' => 4]);
        $this->assertError('AI_DAILY_LIMIT_REACHED', fn () => app(AiExecutionService::class)->execute($this->request()));
        DB::table('tutor_ai_credit_accounts')->update(['daily_limit' => 50, 'balance' => 4]);
        $this->assertError('AI_CREDIT_INSUFFICIENT', fn () => app(AiExecutionService::class)->execute($this->request('second')));
        $this->assertSame(0, $this->provider->calls);
        $this->assertSame(0, DB::table('tutor_ai_credit_transactions')->count());
    }

    public function test_known_provider_failure_releases_reservation_once(): void
    {
        $this->provider->failure = new AiException('AI_PROVIDER_AUTH_FAILED');
        $service = app(AiExecutionService::class);
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $service->execute($this->request()));
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $service->execute($this->request()));
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(['reserve', 'release'], DB::table('tutor_ai_credit_transactions')->pluck('type')->all());
        $this->assertSame(0, DB::table('tutor_ai_usage_records')->count());
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_settlement_failure_holds_credit_and_blocks_automatic_retry(): void
    {
        // Force a local settlement failure after the provider has returned a result.
        DB::unprepared("CREATE TRIGGER fail_usage BEFORE INSERT ON tutor_ai_usage_records BEGIN SELECT RAISE(ABORT, 'fixture failure'); END");
        $service = app(AiExecutionService::class);
        $this->assertError('AI_REQUEST_RECONCILIATION_REQUIRED', fn () => $service->execute($this->request()));
        $this->assertError('AI_REQUEST_RECONCILIATION_REQUIRED', fn () => $service->execute($this->request()));
        $this->assertSame(1, $this->provider->calls);
        $this->assertSame(95, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(0, DB::table('tutor_ai_usage_records')->count());
        $this->assertSame('processing', DB::table('tutor_ai_requests')->value('status'));
        $this->assertStringContainsString('Mock response', Crypt::decryptString(DB::table('tutor_ai_requests')->value('encrypted_result')));
    }

    public function test_processing_request_and_outstanding_hold_count_against_quota(): void
    {
        $this->provider->failure = new AiException('AI_PROVIDER_OUTCOME_UNKNOWN');
        $this->assertError('AI_REQUEST_RECONCILIATION_REQUIRED', fn () => app(AiExecutionService::class)->execute($this->request()));
        DB::table('tutor_ai_credit_accounts')->update(['daily_limit' => 6]);
        $this->assertError('AI_DAILY_LIMIT_REACHED', fn () => app(AiExecutionService::class)->execute($this->request('next')));
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_vendor_mode_and_unknown_mode_are_rejected_without_reserving(): void
    {
        $this->assertInstanceOf(VendorCreditBillingDriver::class, app(BillingManager::class)->driver('vendor_credit'));
        foreach (['vendor_credit', 'invalid'] as $mode) {
            config(['ai-tutor.billing_mode' => $mode]);
            $this->assertError('BILLING_MODE_NOT_SUPPORTED', fn () => app(AiExecutionService::class)->execute($this->request($mode)));
        }
        $this->assertSame(0, $this->provider->calls);
        $this->assertSame(0, DB::table('tutor_ai_credit_transactions')->count());
    }

    public function test_disabled_entitlement_missing_provider_and_forbidden_context_fail_closed(): void
    {
        config(['ai-tutor.enabled' => false]);
        $this->assertError('AI_DISABLED', fn () => app(AiExecutionService::class)->execute($this->request()));
        config(['ai-tutor.enabled' => true]);
        $this->lms->allowed = false;
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => app(AiExecutionService::class)->execute($this->request()));
        $this->lms->allowed = true;
        config(['ai-tutor.providers' => []]);
        $this->assertError('AI_PROVIDER_NOT_CONFIGURED', fn () => app(AiExecutionService::class)->execute($this->request()));
        $this->app->instance(Entitlements::class, new UnavailableEntitlements);
        $this->assertError('LICENSE_MODULE_NOT_ALLOWED', fn () => app(AiExecutionService::class)->execute($this->request('denied')));
        $this->assertSame(0, $this->provider->calls);
    }

    public function test_replay_rechecks_current_lms_authorization(): void
    {
        $service = app(AiExecutionService::class);
        $service->execute($this->request());
        $this->lms->allowed = false;
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $service->execute($this->request()));
    }

    public function test_actor_and_course_substitution_are_rejected(): void
    {
        $request = $this->request();
        $spoof = new AiRequest('tutor_message', new LearnerIdentity('other'), [], $request->requestId, 'spoof');
        $this->assertError('AI_ACTOR_INVALID', fn () => app(AiExecutionService::class)->execute($spoof));
        $wrong = new AiRequest('tutor_message', $request->actor, [], $request->requestId, 'wrong', 'other-course', 'lesson-1');
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => app(AiExecutionService::class)->execute($wrong));
        $this->assertSame(0, $this->provider->calls);
    }

    public function test_maximum_credit_cap_does_not_discard_usage(): void
    {
        $this->provider->response = new AiResponse('Done', 'mock', 'mock-v1', ['input_tokens' => 100000]);
        app(AiExecutionService::class)->execute($this->request());
        $this->assertSame(95, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(100000, DB::table('tutor_ai_usage_records')->value('input_tokens'));
        $this->assertSame(5, DB::table('tutor_ai_usage_records')->value('credit_units'));
    }

    public function test_credit_grants_are_idempotent_and_ledger_is_preserved(): void
    {
        $ledger = app(CreditLedger::class);
        $ledger->grant('learner-1', 10, 'admin-grant-1');
        $ledger->grant('learner-1', 10, 'admin-grant-1');
        $this->assertSame(110, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(1, DB::table('tutor_ai_credit_transactions')->count());
        $this->assertError('AI_REQUEST_DUPLICATE', fn () => $ledger->grant('learner-1', 20, 'admin-grant-1'));
    }
}
