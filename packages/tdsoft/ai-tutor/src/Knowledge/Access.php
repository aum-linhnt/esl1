<?php

namespace TDSoft\AiTutor\Knowledge;

use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\LessonContext;

final class Access
{
    public function __construct(
        public ActorResolver $actors,
        private Entitlements $entitlements,
        private KnowledgeAdministrator $administrators,
        private LmsContextAdapter $lms,
    ) {}

    public function module(string $module): void
    {
        if (! config('ai-tutor.enabled')) {
            throw new AiException('AI_DISABLED');
        }
        $this->actors->resolve();
        if (! $this->entitlements->allows($module)) {
            throw new AiException('LICENSE_MODULE_NOT_ALLOWED');
        }
    }

    public function administrator(): void
    {
        $this->module('ai_tutor_knowledge');
        if (! $this->administrators->allows()) {
            throw new AiException('AI_KNOWLEDGE_FORBIDDEN');
        }
    }

    public function lesson(string $lessonId, ?string $courseId = null): LessonContext
    {
        $actor = $this->actors->resolve();
        if (! $this->lms->canAccessLesson($actor->id, $lessonId)) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        $context = $this->lms->getLessonContext($actor->id, $lessonId);
        if ($context->lessonId !== $lessonId || ($courseId !== null && $context->courseId !== $courseId)) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }

        return $context;
    }
}
