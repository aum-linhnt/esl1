<?php

namespace TDSoft\AiTutor\Billing;

use TDSoft\AiTutor\Contracts\AiBillingDriver;
use TDSoft\AiTutor\Core\AiException;

final class BillingManager
{
    public function driver(?string $mode = null): AiBillingDriver
    {
        return match ($mode ?? config('ai-tutor.billing_mode')) {
            'customer_key' => app(CustomerKeyBillingDriver::class),
            'vendor_credit' => app(VendorCreditBillingDriver::class),
            default => throw new AiException('BILLING_MODE_NOT_SUPPORTED'),
        };
    }
}
