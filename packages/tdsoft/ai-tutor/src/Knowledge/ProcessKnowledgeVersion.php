<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Contracts\BackgroundActor;
use TDSoft\AiTutor\Core\AiException;
use Throwable;

final class ProcessKnowledgeVersion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $versionId)
    {
        $this->onQueue('ai-tutor-knowledge');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(BackgroundActor $actors, KnowledgeService $knowledge): void
    {
        $record = DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $this->versionId)->first();
        if (! $record) {
            throw new AiException('AI_DOCUMENT_NOT_FOUND');
        }
        try {
            $actors->run($record->actor_id, fn () => $knowledge->process($this->versionId, 1));
            if (DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $this->versionId)->value('status') === 'pending') {
                self::dispatch($this->versionId);
            }
        } catch (Throwable $error) {
            // Do not serialize raw transport errors into failed_jobs.
            throw new AiException($error instanceof AiException ? $error->errorCode : 'AI_KNOWLEDGE_PROCESSING_FAILED');
        }
    }

    public function failed(?Throwable $error): void
    {
        DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $this->versionId)->where('status', '!=', 'completed')
            ->update(['status' => 'failed', 'error_code' => DB::raw("COALESCE(error_code, 'AI_KNOWLEDGE_PROCESSING_FAILED')"), 'updated_at' => now()]);
    }
}
