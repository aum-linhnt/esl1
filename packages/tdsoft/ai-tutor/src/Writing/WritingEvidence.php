<?php

namespace TDSoft\AiTutor\Writing;

use TDSoft\AiTutor\Core\AiException;

final class WritingEvidence
{
    public static function validate(array $criteria, string $original): array
    {
        $supported = false;
        $claimed = false;
        foreach ($criteria as &$criterion) {
            if ($criterion['status'] !== 'assessed') {
                continue;
            }
            $claimed = true;
            $quotes = [];
            foreach ($criterion['evidence'] as $evidence) {
                if (str_contains($original, $evidence)) {
                    $quotes[] = $evidence;
                    continue;
                }
                // Recover literal quotations from explanatory wrappers only.
                // Never accept paraphrases or fuzzy/case-insensitive matches.
                $quotationText = preg_split('/\(\s*(?:should be|corrected|correction|suggested)\b/i', $evidence, 2)[0];
                preg_match_all('/"([^"\r\n]+)"|“([^”\r\n]+)”|\x{2018}([^\x{2019}\r\n]+)\x{2019}|\x27([^\x27\r\n]+)\x27/u', $quotationText, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $quote = null;
                    foreach (array_slice($match, 1) as $value) {
                        if ($value !== '') {
                            $quote = $value;
                            break;
                        }
                    }
                    if (is_string($quote) && trim($quote) !== '' && str_contains($original, $quote)) {
                        $quotes[] = $quote;
                    }
                }
            }
            $quotes = array_values(array_unique($quotes));
            if ($quotes === []) {
                $criterion = ['status' => 'not_available', 'score' => null, 'evidence' => []];
            } else {
                $criterion['evidence'] = array_slice($quotes, 0, 20);
                $supported = true;
            }
        }
        unset($criterion);
        if ($claimed && ! $supported) {
            throw new AiException('AI_ASSESSMENT_EVIDENCE_INVALID');
        }

        return $criteria;
    }
}
