<?php

namespace TDSoft\AiTutor\Contracts;

use TDSoft\AiTutor\Core\LessonContext;

interface VectorStore
{
    /** Idempotent upsert by chunk ID. Throw on failure; publication happens only after success. */
    public function put(string $chunkId, array $vector, string $model): void;

    /** Return chunk IDs and scores only; retrieval rechecks visibility independently. */
    public function search(array $vector, string $model, LessonContext $context, int $limit): array;
}
