<?php

namespace TDSoft\AiTutor\Core;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Billing\BillingManager;
use TDSoft\AiTutor\Billing\CreditCalculator;
use TDSoft\AiTutor\Billing\CreditLedger;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use Throwable;

final class AiExecutionService
{
    public function __construct(
        private ActorResolver $actors,
        private LmsContextAdapter $lms,
        private Entitlements $entitlements,
        private BillingManager $billing,
        private CreditLedger $ledger,
        private CreditCalculator $calculator,
    ) {}

    public function execute(AiRequest $request): AiResponse
    {
        if (! config('ai-tutor.enabled')) {
            throw new AiException('AI_DISABLED');
        }
        $actor = $this->actors->resolve();
        if ($actor->id !== $request->actor->id) {
            throw new AiException('AI_ACTOR_INVALID');
        }
        $request = new AiRequest($request->feature, $actor, $request->payload, $request->requestId,
            $request->idempotencyKey, $request->courseId, $request->lessonId, $request->questionId);
        $module = config('ai-tutor.features.'.$request->feature);
        if (! is_string($module)) {
            throw new AiException('AI_FEATURE_INVALID');
        }
        if (! $this->entitlements->allows($module)) {
            throw new AiException('LICENSE_MODULE_NOT_ALLOWED');
        }
        // Always rebuild context, including on replay. A DTO supplied by a caller is not authority.
        $context = null;
        if ($request->lessonId !== null) {
            if (! $this->lms->canAccessLesson($request->actor->id, $request->lessonId)) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
            $lesson = $this->lms->getLessonContext($request->actor->id, $request->lessonId);
            if ($lesson->lessonId !== $request->lessonId || ($request->courseId !== null && $lesson->courseId !== $request->courseId)) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
            $context = $lesson;
            if ($request->questionId !== null) {
                $context = $this->lms->getQuestionContext($request->actor->id, $request->questionId, $request->lessonId);
                if ($context->questionId !== $request->questionId || $context->lesson->lessonId !== $lesson->lessonId || $context->lesson->courseId !== $lesson->courseId) {
                    throw new AiException('AI_CONTEXT_FORBIDDEN');
                }
            }
        }
        $request = $request->withContext($context);
        $existing = DB::table('tutor_ai_requests')->where('idempotency_key', $request->idempotencyKey)->first();
        if ($existing) {
            return $this->replay($request, $existing);
        }
        $rule = DB::table('tutor_ai_credit_rules')->where('feature', $request->feature)->where('enabled', true)->first();
        if (! $rule) {
            throw new AiException('AI_CREDIT_RULE_INVALID');
        }
        $snapshot = [
            'base_units' => (int) $rule->base_units, 'max_units_per_request' => (int) $rule->max_units_per_request,
            'blocks' => $rule->blocks ? json_decode($rule->blocks, true, flags: JSON_THROW_ON_ERROR) : [],
            'cost_rates' => $rule->cost_rates ? json_decode($rule->cost_rates, true, flags: JSON_THROW_ON_ERROR) : null,
            'currency' => $rule->currency,
        ];
        $this->calculator->validate($snapshot);
        $mode = config('ai-tutor.billing_mode');
        try {
            DB::table('tutor_ai_requests')->insert([
                'request_id' => $request->requestId, 'idempotency_key' => $request->idempotencyKey,
                'fingerprint' => $request->fingerprint(), 'user_id' => $request->actor->id, 'feature' => $request->feature,
                'billing_mode' => $mode, 'rule_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'status' => 'pending', 'started_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $existing = DB::table('tutor_ai_requests')->where('idempotency_key', $request->idempotencyKey)->first();
            if (! $existing) {
                throw new AiException('AI_REQUEST_DUPLICATE');
            }

            return $this->replay($request, $existing);
        }

        $authorization = null;
        $driver = null;
        $response = null;
        $providerStarted = false;
        try {
            $driver = $this->billing->driver($mode);
            $authorization = $driver->authorize($request);
            DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->update(['status' => 'processing', 'updated_at' => now()]);
            $providerStarted = true;
            $response = $driver->execute($request, $authorization);
            $encrypted = Crypt::encryptString(json_encode($response->toArray(), JSON_THROW_ON_ERROR));
            // Durable recovery evidence, separate from the settlement transaction.
            DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->update([
                'encrypted_result' => $encrypted, 'provider' => $response->provider, 'model' => $response->model,
                'remote_request_id' => $response->remoteRequestId, 'updated_at' => now(),
            ]);
            DB::transaction(function () use ($driver, $request, $response, $authorization, $snapshot, $encrypted) {
                DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->lockForUpdate()->first();
                $actual = $this->calculator->units($snapshot, $response->usage);
                $driver->recordUsage($request, $response);
                $this->ledger->settle($request, $authorization, $actual);
                DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->update([
                    'status' => 'completed', 'actual_units' => $actual, 'provider' => $response->provider, 'model' => $response->model,
                    'remote_request_id' => $response->remoteRequestId, 'encrypted_result' => $encrypted,
                    'completed_at' => now(), 'updated_at' => now(),
                ]);
            }, 3);

            return $response;
        } catch (Throwable $error) {
            // Only known no-result failures can release safely. Unknown transport failures,
            // process crashes and settlement failures require reconciliation, never AI retry.
            $safeFailure = ! $providerStarted || ($response === null && $error instanceof AiException && in_array($error->errorCode, [
                'AI_PROVIDER_AUTH_FAILED', 'AI_PROVIDER_RATE_LIMITED', 'AI_PROVIDER_UNAVAILABLE', 'AI_PROVIDER_NOT_CONFIGURED',
            ], true));
            if (! $safeFailure) {
                try {
                    DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->update([
                        'error_code' => 'AI_REQUEST_RECONCILIATION_REQUIRED', 'updated_at' => now(),
                    ]);
                } catch (Throwable) {
                    // Preserve the original processing state if the DB itself is unavailable.
                }
                throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
            }
            $code = $error instanceof AiException ? $error->errorCode : 'AI_PROVIDER_UNAVAILABLE';
            DB::transaction(function () use ($request, $authorization, $driver, $code) {
                if ($authorization !== null) {
                    $driver->release($request, $authorization);
                }
                DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->update([
                    'status' => 'failed', 'error_code' => $code, 'failed_at' => now(), 'updated_at' => now(),
                ]);
            }, 3);
            throw new AiException($code);
        }
    }

    private function replay(AiRequest $request, object $record): AiResponse
    {
        if (! hash_equals($record->fingerprint, $request->fingerprint()) || $record->user_id !== $request->actor->id) {
            throw new AiException('AI_REQUEST_DUPLICATE');
        }
        if ($record->status === 'completed' && $record->encrypted_result !== null) {
            return AiResponse::fromArray(json_decode(Crypt::decryptString($record->encrypted_result), true, flags: JSON_THROW_ON_ERROR));
        }
        throw new AiException($record->error_code ?? 'AI_REQUEST_DUPLICATE');
    }
}
