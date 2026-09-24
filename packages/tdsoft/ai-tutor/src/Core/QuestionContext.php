<?php

namespace TDSoft\AiTutor\Core;

final readonly class QuestionContext
{
    public function __construct(
        public string $questionId,
        public LessonContext $lesson,
        public string $content,
        public array $options = [],
    ) {}
}
