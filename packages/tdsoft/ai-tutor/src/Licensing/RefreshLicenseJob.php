<?php

namespace TDSoft\AiTutor\Licensing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

final class RefreshLicenseJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public int $uniqueFor = 120;

    // No key, document, student data or actor object in serialized job payload.
    public function uniqueId(): string
    {
        return 'ai-tutor-license-refresh';
    }

    public function handle(LicenseClient $client): void
    {
        if (! (new LicenseMode)->shouldRefresh()) {
            return;
        }
        try {
            $client->refresh();
        } catch (LicenseException) {
            // Safe error/backoff stored by client; scheduler enqueues the next due attempt.
        }
    }
}
