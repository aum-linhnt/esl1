<?php

namespace TDSoft\AiTutor\Core;

final readonly class LessonContext
{
    public function __construct(
        public string $courseId,
        public string $lessonId,
        public string $content,
        public string $subject = 'english',
        public string $level = '',
        public string $answerPolicy = 'hints_only',
    ) {}
}
