<?php

namespace TDSoft\AiTutor\Billing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\AiResponse;

final class UsageRecorder
{
    public function __construct(private CreditCalculator $calculator) {}

    public function record(AiRequest $request, AiResponse $response): void
    {
        $record = DB::table('tutor_ai_requests')->where('request_id', $request->requestId)->first();
        $rule = json_decode($record->rule_snapshot, true, flags: JSON_THROW_ON_ERROR);
        $cost = $this->calculator->cost($rule, $response->usage);
        DB::table('tutor_ai_usage_records')->insert([
            'request_id' => $request->requestId, 'user_id' => $request->actor->id, 'feature' => $request->feature,
            'billing_mode' => $record->billing_mode, 'provider' => $response->provider, 'model' => $response->model,
            ...$response->usage, 'credit_units' => $this->calculator->units($rule, $response->usage),
            'estimated_cost' => $cost, 'currency' => $rule['currency'],
            'provider_request_id' => $response->providerRequestId, 'remote_request_id' => $response->remoteRequestId,
            'latency_ms' => max(0, (int) Carbon::parse($record->started_at)->diffInMilliseconds(now())),
            'status' => 'completed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('tutor_ai_cost_snapshots')->insert([
            'request_id' => $request->requestId, 'rates' => isset($rule['cost_rates']) ? json_encode($rule['cost_rates'], JSON_THROW_ON_ERROR) : null,
            'currency' => $rule['currency'], 'estimated_cost' => $cost, 'created_at' => now(),
        ]);
    }
}
