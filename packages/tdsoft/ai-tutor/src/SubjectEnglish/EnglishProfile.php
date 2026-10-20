<?php

namespace TDSoft\AiTutor\SubjectEnglish;

use TDSoft\AiTutor\Core\AiException;

final readonly class EnglishProfile
{
    public const TARGETS = [
        'cefr' => ['A1', 'A2', 'B1', 'B2'],
        'toeic' => ['450+', '650+', '800+'],
        'ielts' => ['Foundation', '5.5', '6.5', '7.0+'],
    ];

    public const TASKS = [
        'writing' => ['cefr_writing', 'ielts_task_1', 'ielts_task_2'],
        'speaking' => ['cefr_conversation', 'ielts_part_1', 'ielts_part_2', 'ielts_part_3'],
    ];

    public function __construct(
        public string $framework,
        public string $target,
        public string $feedbackLanguage = 'vi',
    ) {
        if (! in_array($target, self::TARGETS[$framework] ?? [], true)
            || ! in_array($feedbackLanguage, ['vi', 'en', 'bilingual'], true)) {
            throw new AiException('AI_ENGLISH_PROFILE_INVALID');
        }
    }

    public function validateTask(string $skill, string $task): void
    {
        if (! in_array($task, self::TASKS[$skill] ?? [], true)
            || (str_starts_with($task, 'ielts_') && $this->framework !== 'ielts')
            || (str_starts_with($task, 'cefr_') && $this->framework === 'ielts')) {
            throw new AiException('AI_ENGLISH_TASK_INVALID');
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
