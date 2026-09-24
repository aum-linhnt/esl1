<?php

namespace TDSoft\AiTutor\Contracts;

use TDSoft\AiTutor\Core\{LessonContext, QuestionContext};

interface LmsContextAdapter
{
    public function canAccessLesson(string $userId, string $lessonId): bool;

    public function getLessonContext(string $userId, string $lessonId): LessonContext;

    // Explicit lesson binding prevents cross-lesson/question substitution.
    public function getQuestionContext(string $userId, string $questionId, string $lessonId): QuestionContext;
}
