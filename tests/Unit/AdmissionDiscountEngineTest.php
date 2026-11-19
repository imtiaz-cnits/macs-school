<?php

namespace Tests\Unit;

use App\Services\AdmissionDiscountEngine;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Isolated BCMath unit tests for AdmissionDiscountEngine.
 * Pure domain tests with zero database dependency.
 */
class AdmissionDiscountEngineTest extends TestCase
{
    public function test_no_discount_mode_and_explicit_zero_discounts_preserve_exact_gross(): void
    {
        $heads = [
            ['id' => 1, 'sort_order' => 10, 'amount' => '2500.00', 'is_discountable' => true],
            ['id' => 2, 'sort_order' => 20, 'amount' => '1500.5', 'is_discountable' => false],
        ];

        $noneResult = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_NONE);
        $this->assertSame('none', $noneResult['discount_type']);
        $this->assertSame('4000.50', $noneResult['total_gross']);
        $this->assertSame('2500.00', $noneResult['eligible_gross']);
        $this->assertSame('1500.50', $noneResult['non_discountable_gross']);
        $this->assertSame('0.00', $noneResult['total_discount']);
        $this->assertSame('4000.50', $noneResult['total_net']);
        $this->assertReconciliationInvariants($noneResult);

        $zeroFixed = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_FIXED, '0');
        $this->assertSame('0.00', $zeroFixed['total_discount']);
        $this->assertSame('4000.50', $zeroFixed['total_net']);
        $this->assertReconciliationInvariants($zeroFixed);

        $zeroPct = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_PERCENTAGE, '0.00');
        $this->assertSame('0.00', $zeroPct['total_discount']);
        $this->assertSame('4000.50', $zeroPct['total_net']);
        $this->assertReconciliationInvariants($zeroPct);
    }

    public function test_percentage_discount_with_mixed_heads_and_half_up_target_rounding(): void
    {
        $heads = [
            ['id' => 1, 'sort_order' => 10, 'amount' => '3000.00', 'is_discountable' => true],
            ['id' => 2, 'sort_order' => 20, 'amount' => '2000.00', 'is_discountable' => true],
            ['id' => 3, 'sort_order' => 30, 'amount' => '1500.00', 'is_discountable' => false],
        ];

        // 15% applies strictly to eligible 5000.00 -> 750.00
        $result = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_PERCENTAGE, '15');

        $this->assertSame('6500.00', $result['total_gross']);
        $this->assertSame('5000.00', $result['eligible_gross']);
        $this->assertSame('750.00', $result['total_discount']);
        $this->assertSame('5750.00', $result['total_net']);

        $this->assertSame('450.00', $result['heads'][0]['discount']);
        $this->assertSame('2550.00', $result['heads'][0]['net_amount']);
        $this->assertSame('300.00', $result['heads'][1]['discount']);
        $this->assertSame('1700.00', $result['heads'][1]['net_amount']);
        $this->assertSame('0.00', $result['heads'][2]['discount']);
        $this->assertSame('1500.00', $result['heads'][2]['net_amount']);
        $this->assertReconciliationInvariants($result);

        // Commercial half-up target rounding check: 50% of 33.33 = 16.665 -> 16.67 (never 16.66 truncation)
        $halfUp = AdmissionDiscountEngine::calculate([
            ['id' => 1, 'sort_order' => 10, 'amount' => '33.33', 'is_discountable' => true],
        ], AdmissionDiscountEngine::TYPE_PERCENTAGE, '50.00');

        $this->assertSame('16.67', $halfUp['total_discount']);
        $this->assertSame('16.66', $halfUp['total_net']);
        $this->assertReconciliationInvariants($halfUp);
    }

    public function test_fixed_discount_and_mixed_discountable_isolation(): void
    {
        $heads = [
            ['head_id' => 101, 'sort_order' => 10, 'amount' => '3000.00', 'is_discountable' => true],
            ['head_id' => 102, 'sort_order' => 20, 'amount' => '1000.00', 'is_discountable' => true],
            ['head_id' => 103, 'sort_order' => 30, 'amount' => '2000.00', 'is_discountable' => false],
        ];

        $allocated = AdmissionDiscountEngine::allocate($heads, '400');
        $this->assertSame('300.00', $allocated[0]['discount']);
        $this->assertSame('2700.00', $allocated[0]['net_amount']);
        $this->assertSame('100.00', $allocated[1]['discount']);
        $this->assertSame('900.00', $allocated[1]['net_amount']);
        $this->assertSame('0.00', $allocated[2]['discount']);
        $this->assertSame('2000.00', $allocated[2]['net_amount']);

        $summary = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_FIXED, '400');
        $this->assertReconciliationInvariants($summary);
    }

    public function test_100_percent_discount_zeroes_only_discountable_heads(): void
    {
        $heads = [
            ['id' => 1, 'sort_order' => 10, 'amount' => '2500.75', 'is_discountable' => true],
            ['id' => 2, 'sort_order' => 20, 'amount' => '1200.25', 'is_discountable' => true],
            ['id' => 3, 'sort_order' => 30, 'amount' => '1500.00', 'is_discountable' => false],
        ];

        $pct100 = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_PERCENTAGE, '100.00');
        $this->assertSame('5201.00', $pct100['total_gross']);
        $this->assertSame('3701.00', $pct100['eligible_gross']);
        $this->assertSame('3701.00', $pct100['total_discount']);
        $this->assertSame('1500.00', $pct100['total_net']);

        $this->assertSame('0.00', $pct100['heads'][0]['net_amount']);
        $this->assertSame('0.00', $pct100['heads'][1]['net_amount']);
        $this->assertSame('0.00', $pct100['heads'][2]['discount']);
        $this->assertSame('1500.00', $pct100['heads'][2]['net_amount']);
        $this->assertReconciliationInvariants($pct100);

        // Exact 100% via fixed amount equally supported
        $fixedFull = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_FIXED, '3701.00');
        $this->assertSame('1500.00', $fixedFull['total_net']);
        $this->assertReconciliationInvariants($fixedFull);
    }

    public function test_exact_paisa_remainder_distribution_across_equal_heads_and_tie_breakers(): void
    {
        // Case A: Three equal 100.00 heads split 10.00 -> 3.33 each + 0.01 remainder.
        // Remainders tie equally; sort_order ASC picks sort_order=5 first regardless of array position/id!
        $equalSortBreak = [
            ['id' => 10, 'sort_order' => 30, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 99, 'sort_order' => 5,  'amount' => '100.00', 'is_discountable' => true],
            ['id' => 20, 'sort_order' => 20, 'amount' => '100.00', 'is_discountable' => true],
        ];

        $resA = AdmissionDiscountEngine::calculate($equalSortBreak, AdmissionDiscountEngine::TYPE_FIXED, '10.00');
        $this->assertSame('3.33', $resA['heads'][0]['discount']);
        $this->assertSame('3.34', $resA['heads'][1]['discount']); // sort_order=5 won the 0.01 remainder
        $this->assertSame('3.33', $resA['heads'][2]['discount']);
        $this->assertSame('10.00', $resA['total_discount']);
        $this->assertReconciliationInvariants($resA);

        // Case B: Equal remainders AND equal sort_order -> id ASC wins cleanly regardless of input order!
        $equalSortSame = [
            ['id' => 50, 'sort_order' => 10, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 12, 'sort_order' => 10, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 30, 'sort_order' => 10, 'amount' => '100.00', 'is_discountable' => true],
        ];

        $resB = AdmissionDiscountEngine::calculate($equalSortSame, AdmissionDiscountEngine::TYPE_FIXED, '10.00');
        $this->assertSame('3.33', $resB['heads'][0]['discount']);
        $this->assertSame('3.34', $resB['heads'][1]['discount']); // id=12 won tie
        $this->assertSame('3.33', $resB['heads'][2]['discount']);
        $this->assertReconciliationInvariants($resB);

        // Case C: Remainders 0.02 unallocated across 3 heads (`100.00 * 3` sharing `20.00` fixed = 6.66 each + 0.02 rem -> top 2 get +0.01)
        $resTwoPaisa = AdmissionDiscountEngine::calculate($equalSortBreak, AdmissionDiscountEngine::TYPE_FIXED, '20.00');
        $this->assertSame('6.66', $resTwoPaisa['heads'][0]['discount']); // sort_order=30 -> no bonus
        $this->assertSame('6.67', $resTwoPaisa['heads'][1]['discount']); // sort_order=5 -> +0.01
        $this->assertSame('6.67', $resTwoPaisa['heads'][2]['discount']); // sort_order=20 -> +0.01
        $this->assertReconciliationInvariants($resTwoPaisa);
    }

    public function test_exact_paisa_remainder_distribution_across_unequal_heads_prioritizes_larger_bcmod_remainder(): void
    {
        // Head 0 (discountable): 100.00 (10000 paisa)
        // Head 1 (non-discountable): 5000.00 -> must receive 0.00 and NEVER enter remainder queue
        // Head 2 (discountable, lower sort_order=1): 200.00 (20000 paisa)
        // Head 3 (discountable, higher sort_order=99): 300.00 (30000 paisa)
        // Eligible = 600.00 (60000 paisa). Discount = 1.00 (100 paisa):
        //   Head 0: 10000*100 / 60000 = 16 rem 40000
        //   Head 2: 20000*100 / 60000 = 33 rem 20000
        //   Head 3: 30000*100 / 60000 = 50 rem 0
        // Floor sum = 16 + 33 + 50 = 99 paisa -> 1 remainder paisa MUST go to Head 0 (remainder 40000 > 20000 > 0)
        // AND test a case with larger prime eligible base where 2 remainders win over sort_order:
        //   Heads: 101.00 (id=1, sort=1), 203.00 (id=2, sort=2), 307.00 (id=3, sort=3). Total eligible = 611.00 (61100 paisa)
        //   Discount = 50.00 (5000 paisa):
        //     Head 1 (10100 * 5000 = 50,500,000): floor = 826, rem = 31,400
        //     Head 2 (20300 * 5000 = 101,500,000): floor = 1661, rem = 12,900
        //     Head 3 (30700 * 5000 = 153,500,000): floor = 2512, rem = 16,800
        //   Floor sum = 826 + 1661 + 2512 = 4999 -> remaining = 1 paisa -> MUST go to Head 1 (rem 31,400)
        //   Discount = 50.02 (5002 paisa):
        //     Head 1: 10100 * 5002 = 50,520,200 -> floor 826, rem 51,600
        //     Head 2: 20300 * 5002 = 101,540,600 -> floor 1661, rem 53,500
        //     Head 3: 30700 * 5002 = 153,561,400 -> floor 2513, rem 17,100
        //   Floor sum = 826 + 1661 + 2513 = 5000 -> remaining = 2 paisa -> Head 2 (rem 53,500) & Head 1 (rem 51,600) beat Head 3!
        $unequalHeads = [
            ['id' => 1, 'sort_order' => 1,  'amount' => '101.00', 'is_discountable' => true],
            ['id' => 2, 'sort_order' => 99, 'amount' => '203.00', 'is_discountable' => true],
            ['id' => 3, 'sort_order' => 2,  'amount' => '307.00', 'is_discountable' => true],
            ['id' => 4, 'sort_order' => 0,  'amount' => '5000.00', 'is_discountable' => false],
        ];

        // Even with sort_order=99, Head 2 (id=2) has largest remainder 53,500 and MUST receive 16.62 (+0.01),
        // and Head 1 (rem 51,600) MUST receive 8.27 (+0.01), while Head 3 (rem 17,100) stays at 25.13.
        $res = AdmissionDiscountEngine::calculate($unequalHeads, AdmissionDiscountEngine::TYPE_FIXED, '50.02');
        $this->assertSame('8.27', $res['heads'][0]['discount']);
        $this->assertSame('16.62', $res['heads'][1]['discount']);
        $this->assertSame('25.13', $res['heads'][2]['discount']);
        $this->assertSame('0.00', $res['heads'][3]['discount']);
        $this->assertSame('50.02', $res['total_discount']);
        $this->assertReconciliationInvariants($res);

        // Also verify 33.33% fractional percentage over prime unequal heads reconciles to the exact paisa
        $pctUnequal = AdmissionDiscountEngine::calculate($unequalHeads, AdmissionDiscountEngine::TYPE_PERCENTAGE, '33.33');
        $this->assertReconciliationInvariants($pctUnequal);
    }

    public function test_zero_eligible_gross_rejects_any_nonzero_fixed_or_percentage_discount(): void
    {
        $nonDiscountableOnly = [
            ['id' => 1, 'sort_order' => 10, 'amount' => '2500.00', 'is_discountable' => false],
            ['id' => 2, 'sort_order' => 20, 'amount' => '1500.00', 'is_discountable' => false],
        ];

        // Zero fixed discount on zero eligible gross IS valid
        $zeroOk = AdmissionDiscountEngine::calculate($nonDiscountableOnly, AdmissionDiscountEngine::TYPE_FIXED, '0.00');
        $this->assertSame('0.00', $zeroOk['total_discount']);
        $this->assertReconciliationInvariants($zeroOk);

        // Non-zero fixed discount must throw
        try {
            AdmissionDiscountEngine::calculate($nonDiscountableOnly, AdmissionDiscountEngine::TYPE_FIXED, '100.00');
            $this->fail('Expected InvalidArgumentException when fixed discount given to zero eligible gross.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('cannot exceed total discountable amount', $e->getMessage());
        }

        // Non-zero percentage discount must also throw (never silently become 0.00)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('eligible discountable gross amount is 0.00');
        AdmissionDiscountEngine::calculate($nonDiscountableOnly, AdmissionDiscountEngine::TYPE_PERCENTAGE, '10.00');
    }

    public function test_rejects_discount_greater_than_eligible_gross_even_when_total_gross_is_larger(): void
    {
        $heads = [
            ['id' => 1, 'sort_order' => 10, 'amount' => '1000.00', 'is_discountable' => true],
            ['id' => 2, 'sort_order' => 20, 'amount' => '5000.00', 'is_discountable' => false],
        ];

        // Total gross = 6000.00, eligible gross = 1000.00 -> 1500.00 fixed discount MUST be rejected!
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed total discountable amount');
        AdmissionDiscountEngine::allocate($heads, '1500.00');
    }

    #[DataProvider('malformedMonetaryInputsProvider')]
    public function test_rejects_invalid_monetary_strings_and_negative_values(mixed $badValue): void
    {
        $heads = [
            ['id' => 1, 'amount' => '1000.00', 'is_discountable' => true],
        ];

        $this->expectException(InvalidArgumentException::class);
        AdmissionDiscountEngine::allocate($heads, $badValue);
    }

    public static function malformedMonetaryInputsProvider(): array
    {
        return [
            'php float' => [100.5],
            'php int' => [100],
            'null' => [null],
            'boolean true' => [true],
            'array' => [['100.00']],
            'empty string' => [''],
            'leading space' => [' 100.00'],
            'trailing space' => ['100.00 '],
            'negative value' => ['-5.00'],
            'negative zero' => ['-0.01'],
            'plus prefix' => ['+10.00'],
            'three decimal places' => ['100.999'],
            'four decimal places' => ['10.0000'],
            'scientific notation' => ['1e2'],
            'leading zero integer' => ['010.00'],
            'leading dot' => ['.50'],
            'trailing dot' => ['10.'],
            'comma separator' => ['1,000.00'],
            'alphabetic string' => ['abc'],
        ];
    }

    public function test_rejects_negative_or_malformed_head_amount_empty_heads_and_invalid_modes(): void
    {
        // Empty heads list
        try {
            AdmissionDiscountEngine::calculate([], AdmissionDiscountEngine::TYPE_NONE);
            $this->fail('Expected InvalidArgumentException for empty heads.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('empty head list', $e->getMessage());
        }

        // Unsupported discount type
        try {
            AdmissionDiscountEngine::calculate([
                ['id' => 1, 'amount' => '500.00', 'is_discountable' => true],
            ], 'coupon', '10.00');
            $this->fail('Expected InvalidArgumentException for unsupported discount type.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Unsupported discount type', $e->getMessage());
        }

        // Non-zero value supplied with `none` mode
        try {
            AdmissionDiscountEngine::calculate([
                ['id' => 1, 'amount' => '500.00', 'is_discountable' => true],
            ], AdmissionDiscountEngine::TYPE_NONE, '10.00');
            $this->fail('Expected InvalidArgumentException when none mode has non-zero value.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("when discount type is 'none'", $e->getMessage());
        }

        // Malformed head amount
        $this->expectException(InvalidArgumentException::class);
        AdmissionDiscountEngine::calculate([
            ['id' => 1, 'amount' => '-100.00', 'is_discountable' => true],
        ], AdmissionDiscountEngine::TYPE_NONE);
    }

    public function test_rejects_percentage_greater_than_100(): void
    {
        $heads = [
            ['id' => 1, 'amount' => '1000.00', 'is_discountable' => true],
        ];

        try {
            AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_PERCENTAGE, '100.01');
            $this->fail('Expected InvalidArgumentException for percentage 100.01.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('cannot exceed 100.00%', $e->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_PERCENTAGE, '150');
    }

    public function test_validates_decimal_10_2_boundaries_without_overflow(): void
    {
        // Max valid DECIMAL(10,2) single head with 50% discount executes cleanly in BCMath (zero 64-bit overflow)
        $maxHeads = [
            ['id' => 1, 'sort_order' => 10, 'amount' => '99999999.99', 'is_discountable' => true],
        ];

        $maxHalf = AdmissionDiscountEngine::calculate($maxHeads, AdmissionDiscountEngine::TYPE_PERCENTAGE, '50.00');
        $this->assertSame('99999999.99', $maxHalf['total_gross']);
        $this->assertSame('50000000.00', $maxHalf['total_discount']); // 99999999.99 * 0.50 = 49999999.995 -> half-up 50000000.00
        $this->assertSame('49999999.99', $maxHalf['total_net']);
        $this->assertReconciliationInvariants($maxHalf);

        // Multi-head split near DECIMAL(10,2) ceiling (60000000.00 + 39999999.99 = 99999999.99)
        $splitMax = AdmissionDiscountEngine::calculate([
            ['id' => 1, 'sort_order' => 10, 'amount' => '60000000.00', 'is_discountable' => true],
            ['id' => 2, 'sort_order' => 20, 'amount' => '39999999.99', 'is_discountable' => true],
        ], AdmissionDiscountEngine::TYPE_FIXED, '99999999.99');
        $this->assertSame('0.00', $splitMax['total_net']);
        $this->assertReconciliationInvariants($splitMax);

        // Reject single value above DECIMAL(10,2) limit
        try {
            AdmissionDiscountEngine::validateMonetaryString('100000000.00', 'amount');
            $this->fail('Expected InvalidArgumentException above DECIMAL(10,2) ceiling.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('DECIMAL(10,2)', $e->getMessage());
        }

        // Reject sum of multiple valid heads overflowing DECIMAL(10,2) total invoice gross
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DECIMAL(10,2)');
        AdmissionDiscountEngine::calculate([
            ['id' => 1, 'amount' => '60000000.00', 'is_discountable' => true],
            ['id' => 2, 'amount' => '40000000.00', 'is_discountable' => false],
        ], AdmissionDiscountEngine::TYPE_NONE);
    }

    public function test_handles_zero_amount_discountable_head_safely_alongside_positive_heads(): void
    {
        $heads = [
            ['id' => 1, 'sort_order' => 10, 'amount' => '0.00', 'is_discountable' => true],
            ['id' => 2, 'sort_order' => 20, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 3, 'sort_order' => 30, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 4, 'sort_order' => 40, 'amount' => '100.00', 'is_discountable' => true],
        ];

        $res = AdmissionDiscountEngine::calculate($heads, AdmissionDiscountEngine::TYPE_FIXED, '10.00');
        $this->assertSame('0.00', $res['heads'][0]['discount']);
        $this->assertSame('0.00', $res['heads'][0]['net_amount']);
        $this->assertSame('3.34', $res['heads'][1]['discount']);
        $this->assertSame('3.33', $res['heads'][2]['discount']);
        $this->assertSame('3.33', $res['heads'][3]['discount']);
        $this->assertReconciliationInvariants($res);
    }

    /**
     * Explicit verification of input-array order independence when explicit `sort_order` and unique `id`s are supplied.
     * (Limitation: omitting `sort_order` falls back to input sequence `$position`, so explicit `sort_order` is required.)
     */
    public function test_allocation_is_deterministic_by_head_id_regardless_of_input_array_order(): void
    {
        // Case 1: Equal remainders requiring BOTH sort_order ASC (5 beats 10) AND id ASC (102 beats 103 at sort_order=10)
        $equalOriginal = [
            ['id' => 101, 'sort_order' => 20, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 103, 'sort_order' => 10, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 104, 'sort_order' => 5,  'amount' => '100.00', 'is_discountable' => true],
            ['id' => 102, 'sort_order' => 10, 'amount' => '100.00', 'is_discountable' => true],
            ['id' => 105, 'sort_order' => 1,  'amount' => '500.00', 'is_discountable' => false],
        ];
        $equalReversed = array_reverse($equalOriginal);
        $equalShuffled = [
            $equalOriginal[3], // id 102
            $equalOriginal[4], // id 105
            $equalOriginal[0], // id 101
            $equalOriginal[2], // id 104
            $equalOriginal[1], // id 103
        ];

        $this->assertIdenticalByHeadIdAcrossOrderings(
            [$equalOriginal, $equalReversed, $equalShuffled],
            AdmissionDiscountEngine::TYPE_FIXED,
            '10.02',
            expectedDiscountsById: [
                104 => '2.51', // sort_order=5 won 1st remainder paisa
                102 => '2.51', // sort_order=10 & lower id=102 won 2nd remainder paisa
                103 => '2.50',
                101 => '2.50',
                105 => '0.00',
            ]
        );

        // Case 2: Unequal remainders across prime amounts (fixed and fractional percentage modes)
        $unequalOriginal = [
            ['id' => 201, 'sort_order' => 1,  'amount' => '101.00', 'is_discountable' => true],
            ['id' => 202, 'sort_order' => 99, 'amount' => '203.00', 'is_discountable' => true],
            ['id' => 203, 'sort_order' => 2,  'amount' => '307.00', 'is_discountable' => true],
            ['id' => 204, 'sort_order' => 0,  'amount' => '5000.00', 'is_discountable' => false],
        ];
        $unequalReversed = array_reverse($unequalOriginal);
        $unequalShuffled = [
            $unequalOriginal[2], // id 203
            $unequalOriginal[0], // id 201
            $unequalOriginal[3], // id 204
            $unequalOriginal[1], // id 202
        ];

        $this->assertIdenticalByHeadIdAcrossOrderings(
            [$unequalOriginal, $unequalReversed, $unequalShuffled],
            AdmissionDiscountEngine::TYPE_FIXED,
            '50.02',
            expectedDiscountsById: [
                201 => '8.27',
                202 => '16.62',
                203 => '25.13',
                204 => '0.00',
            ]
        );

        $this->assertIdenticalByHeadIdAcrossOrderings(
            [$unequalOriginal, $unequalReversed, $unequalShuffled],
            AdmissionDiscountEngine::TYPE_PERCENTAGE,
            '33.33'
        );
    }

    /**
     * Execute calculate() across multiple orderings and assert every head ID receives identical
     * discount and net_amount totals across every permutation.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $orderings
     * @param  array<int, string>|null  $expectedDiscountsById
     */
    private function assertIdenticalByHeadIdAcrossOrderings(
        array $orderings,
        string $discountType,
        string $discountValue,
        ?array $expectedDiscountsById = null
    ): void {
        $baselineById = null;

        foreach ($orderings as $orderingIndex => $heads) {
            $result = AdmissionDiscountEngine::calculate($heads, $discountType, $discountValue);
            $this->assertReconciliationInvariants($result);

            $mappedById = [];
            foreach ($result['heads'] as $head) {
                $mappedById[(int) $head['id']] = [
                    'amount' => $head['amount'],
                    'discount' => $head['discount'],
                    'net_amount' => $head['net_amount'],
                ];
            }
            ksort($mappedById);

            if ($expectedDiscountsById !== null) {
                foreach ($expectedDiscountsById as $id => $expectedDiscount) {
                    $this->assertSame(
                        $expectedDiscount,
                        $mappedById[$id]['discount'],
                        "Ordering #{$orderingIndex}: unexpected discount for head ID {$id}"
                    );
                }
            }

            if ($baselineById === null) {
                $baselineById = $mappedById;
            } else {
                $this->assertSame(
                    $baselineById,
                    $mappedById,
                    "Ordering #{$orderingIndex} diverged from original ordering when compared by head ID."
                );
            }
        }
    }

    /**
     * Assert full 2-decimal reconciliation across every allocated output.
     *
     * @param  array<string, mixed>  $summary
     */
    private function assertReconciliationInvariants(array $summary): void
    {
        $sumGross = '0.00';
        $sumDiscount = '0.00';
        $sumNet = '0.00';

        foreach ($summary['heads'] as $head) {
            $sumGross = bcadd($sumGross, $head['amount'], 2);
            $sumDiscount = bcadd($sumDiscount, $head['discount'], 2);
            $sumNet = bcadd($sumNet, $head['net_amount'], 2);

            if (!$head['is_discountable']) {
                $this->assertSame('0.00', $head['discount']);
            }
            $this->assertGreaterThanOrEqual(0, bccomp($head['net_amount'], '0.00', 2));
            $this->assertGreaterThanOrEqual(0, bccomp($head['discount'], '0.00', 2));
            $this->assertLessThanOrEqual(0, bccomp($head['discount'], $head['amount'], 2));
            $this->assertSame(0, bccomp($head['net_amount'], bcsub($head['amount'], $head['discount'], 2), 2));
        }

        $this->assertSame($summary['total_gross'], $sumGross);
        $this->assertSame($summary['total_discount'], $sumDiscount);
        $this->assertSame($summary['total_net'], $sumNet);
        $this->assertSame(0, bccomp($summary['total_net'], bcsub($summary['total_gross'], $summary['total_discount'], 2), 2));
    }
}
