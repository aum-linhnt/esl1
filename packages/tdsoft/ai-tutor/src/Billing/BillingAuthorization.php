<?php

namespace TDSoft\AiTutor\Billing;

final readonly class BillingAuthorization
{
    public function __construct(public int $accountId, public int $reservedUnits) {}
}
