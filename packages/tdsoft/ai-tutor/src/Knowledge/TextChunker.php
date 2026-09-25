<?php

namespace TDSoft\AiTutor\Knowledge;

use TDSoft\AiTutor\Core\AiException;

final class TextChunker
{
    public function split(string $text, string $format = 'text'): array
    {
        if (! in_array($format, ['text', 'markdown', 'html'], true)) {
            throw new AiException('AI_DOCUMENT_TYPE_NOT_SUPPORTED');
        }
        if (! mb_check_encoding($text, 'UTF-8') || strlen($text) > 200000 || str_contains($text, "\0")) {
            throw new AiException('AI_DOCUMENT_INVALID');
        }
        if ($format === 'html') {
            $text = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $text);
            $text = preg_replace('~</?(?:p|div|br|h[1-6]|li)[^>]*>~i', "\n", $text);
            $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') {
            throw new AiException('AI_DOCUMENT_EMPTY');
        }
        $chunks = [];
        $current = '';
        foreach (preg_split('/\n\s*\n/u', $text) as $paragraph) {
            while (mb_strlen($paragraph) > 1600) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = '';
                }
                $chunks[] = mb_substr($paragraph, 0, 1600);
                $paragraph = mb_substr($paragraph, 1600);
            }
            if (mb_strlen($current."\n\n".$paragraph) > 1600) {
                $chunks[] = $current;
                $current = '';
            }
            $current = trim($current."\n\n".$paragraph);
        }
        if ($current !== '') {
            $chunks[] = $current;
        }
        if (count($chunks) > 100) {
            throw new AiException('AI_DOCUMENT_TOO_LARGE');
        }

        return $chunks;
    }
}
