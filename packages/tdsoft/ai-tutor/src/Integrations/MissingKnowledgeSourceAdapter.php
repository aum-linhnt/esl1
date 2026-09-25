<?php

namespace TDSoft\AiTutor\Integrations;

use TDSoft\AiTutor\Contracts\KnowledgeSourceAdapter;
use TDSoft\AiTutor\Core\AiException;

final class MissingKnowledgeSourceAdapter implements KnowledgeSourceAdapter
{
    public function canReadLesson(string $actorId, string $lessonId): bool
    {
        return false;
    }

    public function courses(string $actorId): array
    {
        throw new AiException('AI_KNOWLEDGE_SYNC_NOT_CONFIGURED');
    }

    public function lessons(string $actorId, string $courseId): array
    {
        throw new AiException('AI_KNOWLEDGE_SYNC_NOT_CONFIGURED');
    }
}
