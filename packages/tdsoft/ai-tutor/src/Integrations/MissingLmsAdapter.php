<?php

namespace TDSoft\AiTutor\Integrations;

use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Core\{AiException, LessonContext, QuestionContext};

final class MissingLmsAdapter implements LmsContextAdapter
{
    public function canAccessLesson(string $userId, string $lessonId): bool
    {
        throw new AiException('AI_ADAPTER_NOT_CONFIGURED');
    }

    public function getLessonContext(string $userId, string $lessonId): LessonContext
    {
        throw new AiException('AI_ADAPTER_NOT_CONFIGURED');
    }

    public function getQuestionContext(string $userId, string $questionId, string $lessonId): QuestionContext
    {
        throw new AiException('AI_ADAPTER_NOT_CONFIGURED');
    }
}
