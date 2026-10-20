<?php

namespace TDSoft\AiTutor\Writing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Contracts\BackgroundActor;
use TDSoft\AiTutor\Core\AiException;
use Throwable;

final class ProcessWritingSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $submissionId)
    {
        $this->onQueue('ai-tutor-assessments');
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(BackgroundActor $actors, WritingSubmissions $writing): void
    {
        $record = $writing->record($this->submissionId);
        try {
            $actors->run($record->actor_id, fn () => $writing->process($record->id));
        } catch (Throwable $error) {
            throw new AiException($error instanceof AiException ? $error->errorCode : 'AI_WRITING_PROCESSING_FAILED');
        }
    }

    public function failed(?Throwable $error): void
    {
        $record = DB::table('tutor_ai_writing_submissions')->where('id', $this->submissionId)->first();
        if (! $record || in_array($record->status, ['completed', 'failed'], true)) {
            return;
        }
        $billing = DB::table('tutor_ai_requests')->where('request_id', $record->request_id)->first();
        $uncertain = $billing && in_array($billing->status, ['pending', 'processing', 'completed'], true);
        DB::table('tutor_ai_writing_submissions')->where('id', $record->id)->where('status', '!=', 'completed')->update([
            'status' => $uncertain ? 'reconciliation_required' : 'failed',
            'error_code' => $uncertain ? 'AI_REQUEST_RECONCILIATION_REQUIRED'
                : ($error instanceof AiException ? $error->errorCode : 'AI_WRITING_PROCESSING_FAILED'), 'updated_at' => now(),
        ]);
    }
}
