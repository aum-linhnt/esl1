<?php

namespace TDSoft\AiTutor\Billing;

use Throwable;
use TDSoft\AiTutor\Contracts\AiBillingDriver;
use TDSoft\AiTutor\Core\{AiRequest, AiResponse};
use TDSoft\AiTutor\Providers\ProviderManager;

final class CustomerKeyBillingDriver implements AiBillingDriver
{
    public function __construct(private CreditLedger $ledger, private ProviderManager $providers, private UsageRecorder $usage) {}

    public function authorize(AiRequest $request): BillingAuthorization
    {
        $this->providers->driver();
        return $this->ledger->reserve($request);
    }

    public function execute(AiRequest $request, BillingAuthorization $authorization): AiResponse
    {
        return $this->providers->driver()->execute($request);
    }

    public function recordUsage(AiRequest $request, AiResponse $response): void
    {
        $this->usage->record($request, $response);
    }

    public function release(AiRequest $request, BillingAuthorization $authorization, ?Throwable $error = null): void
    {
        $this->ledger->release($request, $authorization);
    }
}
