<?php

namespace TDSoft\AiTutor\Writing;

use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Core\AiException;

final class WritingAccess
{
    public function __construct(public ActorResolver $actors, private Entitlements $entitlements, private LmsContextAdapter $lms) {}

    public function check(?string $lessonId = null, ?string $courseId = null, bool $assess = false): string
    {
        if (! config('ai-tutor.enabled')) {
            throw new AiException('AI_DISABLED');
        }
        $actor = $this->actors->resolve();
        if (! $this->entitlements->allows('ai_tutor_writing')) {
            throw new AiException('LICENSE_MODULE_NOT_ALLOWED');
        }
        if ($courseId !== null && $lessonId === null) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        if ($lessonId !== null) {
            if (! $this->lms->canAccessLesson($actor->id, $lessonId)) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
            $lesson = $this->lms->getLessonContext($actor->id, $lessonId);
            if ($lesson->lessonId !== $lessonId || ($courseId !== null && $lesson->courseId !== $courseId)) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
            if ($assess && ($lesson->isExam || $lesson->answerPolicy === 'no_answer')) {
                throw new AiException('AI_WRITING_ASSESSMENT_FORBIDDEN');
            }
        }

        return $actor->id;
    }

    public function owned(object $record, bool $assess = false): void
    {
        $actor = $this->check($record->lesson_id, $record->course_id, $assess);
        if ($record->actor_id !== $actor) {
            throw new AiException('AI_WRITING_FORBIDDEN');
        }
    }
}
