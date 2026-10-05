<?php

namespace TDSoft\AiTutor\Contracts;

interface KnowledgeSourceAdapter
{
    /** Authorized course choices, no LMS models. */
    public function courses(string $actorId): array;

    /** Safe lesson snapshots: lesson_id, title, content (plain text), warning. */
    public function lessons(string $actorId, string $courseId): array;

    /** Current learner rights to the full summary, not just access to trial activities. */
    public function canReadLesson(string $actorId, string $lessonId): bool;
}
