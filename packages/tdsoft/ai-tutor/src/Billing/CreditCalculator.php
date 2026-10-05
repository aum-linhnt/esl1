<?php

namespace TDSoft\AiTutor\Billing;

use TDSoft\AiTutor\Core\AiException;

final class CreditCalculator
{
    public function validate(array $rule): void
    {
        foreach (['base_units', 'max_units_per_request'] as $key) {
            if (! isset($rule[$key]) || ! is_int($rule[$key]) || $rule[$key] < 0 || $rule[$key] > 1000000) {
                throw new AiException('AI_CREDIT_RULE_INVALID');
            }
        }
        if ($rule['max_units_per_request'] < $rule['base_units']) {
            throw new AiException('AI_CREDIT_RULE_INVALID');
        }
        foreach ($rule['blocks'] ?? [] as $metric => $block) {
            if (! in_array($metric, ['input_tokens', 'output_tokens', 'audio_seconds', 'image_count', 'document_pages'], true)
                || ! is_int($block['size'] ?? null) || $block['size'] < 1
                || ! is_int($block['units'] ?? null) || $block['units'] < 0 || $block['units'] > 1000000) {
                throw new AiException('AI_CREDIT_RULE_INVALID');
            }
        }
        foreach ($rule['cost_rates'] ?? [] as $metric => $rate) {
            if (! in_array($metric, ['input_tokens', 'output_tokens', 'cached_tokens', 'audio_seconds', 'image_count', 'document_pages'], true)
                || ! is_int($rate) || $rate < 0 || $rate > 1000000000) {
                throw new AiException('AI_CREDIT_RULE_INVALID');
            }
        }
        if (! preg_match('/^[A-Z]{3}$/', $rule['currency'] ?? '')) {
            throw new AiException('AI_CREDIT_RULE_INVALID');
        }
    }

    public function units(array $rule, array $usage): int
    {
        $this->validate($rule);
        $units = $rule['base_units'];
        foreach ($rule['blocks'] ?? [] as $metric => $block) {
            $units += intdiv(($usage[$metric] ?? 0) + $block['size'] - 1, $block['size']) * $block['units'];
        }

        // Explicit product ceiling, not a provider-cost ceiling. Usage is never truncated.
        return min($units, $rule['max_units_per_request']);
    }

    public function cost(array $rule, array $usage): ?string
    {
        $rates = $rule['cost_rates'] ?? null;
        if ($rates === null) {
            return null; // Unknown pricing must not be presented as zero cost.
        }
        $micros = 0;
        foreach ($usage as $metric => $quantity) {
            if ($metric === 'input_tokens') {
                $quantity -= $usage['cached_tokens'];
            }
            if ($quantity && ! array_key_exists($metric, $rates)) {
                return null;
            }
            $denominator = str_ends_with($metric, '_tokens') ? 1000000 : 1;
            $micros += intdiv($quantity * ($rates[$metric] ?? 0) + $denominator - 1, $denominator);
        }

        return intdiv($micros, 1000000).'.'.str_pad((string) ($micros % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
