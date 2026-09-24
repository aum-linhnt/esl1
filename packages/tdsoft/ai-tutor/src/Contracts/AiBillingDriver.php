<?php

namespace TDSoft\AiTutor\Contracts;

use Throwable;
use TDSoft\AiTutor\Billing\BillingAuthorization;
use TDSoft\AiTutor\Core\{AiRequest, AiResponse};

interface AiBillingDriver
{
    public function authorize(AiRequest $request): BillingAuthorization;
    public function execute(AiRequest $request, BillingAuthorization $authorization): AiResponse;
    public function recordUsage(AiRequest $request, AiResponse $response): void;
    public function release(AiRequest $request, BillingAuthorization $authorization, ?Throwable $error = null): void;
}
