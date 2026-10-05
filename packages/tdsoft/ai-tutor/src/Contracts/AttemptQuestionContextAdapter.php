<?php

namespace TDSoft\AiTutor\Contracts;

use TDSoft\AiTutor\Core\QuestionContext;

interface AttemptQuestionContextAdapter
{
    public function getAttemptQuestionContext(string $userId, string $questionId, string $lessonId, string $attemptId): QuestionContext;
}
