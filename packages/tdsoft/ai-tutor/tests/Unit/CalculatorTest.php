<?php

namespace TDSoft\AiTutor\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TDSoft\AiTutor\Billing\CreditCalculator;
use TDSoft\AiTutor\Core\AiException;

final class CalculatorTest extends TestCase
{
    public function test_blocks_round_up_and_price_is_capped(): void
    {
        $calculator = new CreditCalculator;
        $rule = ['base_units' => 1, 'max_units_per_request' => 5, 'currency' => 'USD',
            'blocks' => ['input_tokens' => ['size' => 2000, 'units' => 1]]];
        $this->assertSame(1, $calculator->units($rule, []));
        $this->assertSame(2, $calculator->units($rule, ['input_tokens' => 2000]));
        $this->assertSame(3, $calculator->units($rule, ['input_tokens' => 2001]));
        $this->assertSame(5, $calculator->units($rule, ['input_tokens' => 999999]));
    }

    public function test_unknown_price_is_null_and_cached_tokens_are_not_counted_twice(): void
    {
        $calculator = new CreditCalculator;
        $usage = ['input_tokens' => 100, 'cached_tokens' => 50, 'output_tokens' => 10];
        $this->assertNull($calculator->cost([], $usage));
        $this->assertSame('0.000075', $calculator->cost(['cost_rates' => [
            'input_tokens' => 1000000, 'cached_tokens' => 100000, 'output_tokens' => 2000000,
        ]], $usage));
    }

    public function test_invalid_rule_is_rejected(): void
    {
        $this->expectException(AiException::class);
        (new CreditCalculator)->units(['base_units' => 2, 'max_units_per_request' => 1, 'currency' => 'USD'], []);
    }
}
