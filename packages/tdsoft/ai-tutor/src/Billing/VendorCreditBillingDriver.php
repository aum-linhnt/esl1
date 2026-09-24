<?php

namespace TDSoft\AiTutor\Billing;

use Throwable;
use TDSoft\AiTutor\Contracts\AiBillingDriver;
use TDSoft\AiTutor\Core\{AiException, AiRequest, AiResponse};

final class VendorCreditBillingDriver implements AiBillingDriver
{
    public function authorize(AiRequest $request): BillingAuthorization
    {
        throw new AiException('BILLING_MODE_NOT_SUPPORTED');
    }

    public function execute(AiRequest $request, BillingAuthorization $authorization): AiResponse
    {
        throw new AiException('BILLING_MODE_NOT_SUPPORTED');
    }

    public function recordUsage(AiRequest $request, AiResponse $response): void
    {
        throw new AiException('BILLING_MODE_NOT_SUPPORTED');
    }

    public function release(AiRequest $request, BillingAuthorization $authorization, ?Throwable $error = null): void
    {
        throw new AiException('BILLING_MODE_NOT_SUPPORTED');
    }
}
