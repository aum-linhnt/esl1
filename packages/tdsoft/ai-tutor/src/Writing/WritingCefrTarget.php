<?php

namespace TDSoft\AiTutor\Writing;

use TDSoft\AiTutor\Core\AiException;

final class WritingCefrTarget
{
    // Practice summaries, adapted from the CEFR Writing self-assessment grid.
    // https://europass.europa.eu/en/common-european-framework-reference-language-skills
    public const EXPECTATIONS = [
        'A1' => 'Viết thông tin cá nhân và lời nhắn rất ngắn, đơn giản.',
        'A2' => 'Viết lời nhắn hoặc thư cá nhân đơn giản về nhu cầu và tình huống quen thuộc.',
        'B1' => 'Viết văn bản có liên kết về chủ đề quen thuộc, mô tả trải nghiệm và cảm nhận.',
        'B2' => 'Viết rõ ràng, chi tiết; truyền đạt thông tin hoặc nêu lý do cho quan điểm khi đề yêu cầu.',
    ];

    public const ASPECTS = ['communication', 'development', 'language'];

    public static function expectation(array $profile): ?string
    {
        return ($profile['framework'] ?? null) === 'cefr' ? (self::EXPECTATIONS[$profile['target'] ?? ''] ?? null) : null;
    }

    public static function validate(mixed $items, string $original, array $profile): ?array
    {
        if (($profile['framework'] ?? null) !== 'cefr' || $items === null) {
            return null;
        }
        $expectation = self::expectation($profile);
        if ($expectation === null || ! is_array($items) || ! array_is_list($items) || count($items) > 3) {
            throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
        }
        $aspects = [];
        foreach ($items as $item) {
            $aspect = is_array($item) ? ($item['aspect'] ?? null) : null;
            if (! in_array($aspect, self::ASPECTS, true) || isset($aspects[$aspect])
                || ! is_string($item['next_step'] ?? null) || trim($item['next_step']) === '' || strlen($item['next_step']) > 2000) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            $verified = WritingRequirements::validate([[
                'requirement' => $aspect, 'status' => $item['status'] ?? null,
                'comment' => $item['comment'] ?? null, 'evidence' => $item['evidence'] ?? null,
            ]], $original)[0];
            if ($verified['status'] === 'not_met' && $verified['evidence'] === []) {
                $verified['status'] = 'not_available';
                $verified['comment'] = 'Chưa có dẫn chứng khớp bài viết để đối chiếu mục tiêu này.';
            }
            $aspects[$aspect] = ['aspect' => $aspect, 'status' => $verified['status'],
                'comment' => $verified['comment'], 'evidence' => $verified['evidence'],
                'next_step' => $verified['status'] === 'not_available' ? '' : trim($item['next_step'])];
        }

        $ordered = [];
        foreach (self::ASPECTS as $aspect) {
            $ordered[] = $aspects[$aspect] ?? ['aspect' => $aspect, 'status' => 'not_available',
                'comment' => 'Lượt chấm chưa có đủ dữ liệu để đối chiếu mục tiêu này.', 'evidence' => [], 'next_step' => ''];
        }

        return ['target' => $profile['target'], 'descriptor_version' => 'cefr-writing-practice-v1',
            'expectation' => $expectation, 'aspects' => $ordered];
    }
}
