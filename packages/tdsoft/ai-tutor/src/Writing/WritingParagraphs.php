<?php

namespace TDSoft\AiTutor\Writing;

use TDSoft\AiTutor\Core\AiException;

final class WritingParagraphs
{
    public static function source(string $text): array
    {
        $paragraphs = preg_split('/\r?\n[\t ]*\r?\n(?:[\t ]*\r?\n)*/u', $text);

        return array_values(array_filter(array_map('trim', $paragraphs), fn ($value) => $value !== ''));
    }

    public static function validate(mixed $data, string $original): array
    {
        if (! is_array($data) || ! array_is_list($data) || count($data) > 8) {
            throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
        }
        $paragraphs = self::source($original);
        $result = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            $number = $item['paragraph_number'] ?? null;
            if (! is_int($number) || $number < 1 || $number > count($paragraphs) || isset($result[$number])) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            foreach (['comment', 'next_step'] as $field) {
                if (! is_string($item[$field] ?? null) || trim($item[$field]) === '' || strlen($item[$field]) > 2000) {
                    throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
                }
            }
            // The excerpt always comes from the immutable submitted text.
            $text = $paragraphs[$number - 1];
            $result[$number] = ['paragraph_number' => $number,
                'excerpt' => mb_substr($text, 0, 300).(mb_strlen($text) > 300 ? '…' : ''),
                'comment' => trim($item['comment']), 'next_step' => trim($item['next_step'])];
        }
        ksort($result);

        return array_values($result);
    }
}
