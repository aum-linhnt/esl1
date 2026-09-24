<?php

namespace TDSoft\AiTutor\Knowledge;

use TDSoft\AiTutor\Core\AiException;

final class Vector
{
    public static function validate(array $values): array
    {
        if (! array_is_list($values) || count($values) < 1 || count($values) > 8192) {
            throw new AiException('AI_EMBEDDING_INVALID');
        }
        $norm = 0.0;
        foreach ($values as $value) {
            if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value) || abs($value) > 1e10) {
                throw new AiException('AI_EMBEDDING_INVALID');
            }
            $norm += $value * $value;
        }
        if ($norm <= 0) {
            throw new AiException('AI_EMBEDDING_INVALID');
        }

        return $values;
    }

    public static function cosine(array $a, array $b): float
    {
        self::validate($a);
        self::validate($b);
        if (count($a) !== count($b)) {
            return -1;
        }
        $dot = $aa = $bb = 0.0;
        foreach ($a as $i => $v) {
            $dot += $v * $b[$i];
            $aa += $v * $v;
            $bb += $b[$i] * $b[$i];
        }

        return $dot / sqrt($aa * $bb);
    }
}
