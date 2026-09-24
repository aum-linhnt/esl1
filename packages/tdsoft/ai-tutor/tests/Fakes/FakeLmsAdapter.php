<?php

namespace TDSoft\AiTutor\Tests\Fakes;

use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Core\{AiException, LessonContext, QuestionContext};

final class FakeLmsAdapter implements LmsContextAdapter
{
    public bool $allowed = true;

    public function canAccessLesson(string $userId, string $lessonId): bool { return $this->allowed; }

    public function getLessonContext(string $userId, string $lessonId): LessonContext
    {
        if (! $this->allowed) { throw new AiException('AI_CONTEXT_FORBIDDEN'); }
        return new LessonContext('course-1', $lessonId, 'Safe lesson context');
    }

    public function getQuestionContext(string $userId, string $questionId, string $lessonId): QuestionContext
    {
        return new QuestionContext($questionId, $this->getLessonContext($userId, $lessonId), 'Safe question');
    }
}
