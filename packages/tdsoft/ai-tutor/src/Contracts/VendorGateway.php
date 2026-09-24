<?php

namespace TDSoft\AiTutor\Contracts;

use TDSoft\AiTutor\Core\AiResponse;
use TDSoft\AiTutor\Billing\GatewayRequest;

interface VendorGateway
{
    // Phase 6: installation-authenticated, idempotent execution. No customer API key.
    public function execute(GatewayRequest $request): AiResponse;
}
