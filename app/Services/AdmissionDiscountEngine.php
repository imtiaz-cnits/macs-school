<?php

namespace App\Services;

use InvalidArgumentException;
use LogicException;

/**
 * Pure BCMath monetary discount calculator for multi-head Admission Fee invoices.
 *
 * Strictly forbids PHP float arithmetic and enforces exact 2-decimal reconciliation
 * using integer-paisa `bcmod` distribution with deterministic remainder tie-breaking.
 */
class AdmissionDiscountEngine
{
    public const TYPE_NONE = 'none';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percentage';

    public const VALID_TYPES = [
        self::TYPE_NONE,
        self::TYPE_FIXED,
        self::TYPE_PERCENTAGE,
    ];

    public const MAX_DECIMAL_10_2 = '99999999.99';
    private const MONETARY_REGEX = '/^(0|[1-9]\d*)(\.\d{1,2})?$/';

    /**
     * Validate a strict decimal string without rounding, silent truncation, or PHP float conversion.
     *
     * @throws InvalidArgumentException
     */
    public static function validateMonetaryString(mixed $value, string $fieldName): string
    {
        if (!is_string($value)) {
            $type = gettype($value);
            throw new InvalidArgumentException(
                "Monetary validation failed for '{$fieldName}': Expected strict decimal string, got {$type}."
            );
        }

        if ($value === '') {
            throw new InvalidArgumentException(
                "Monetary validation failed for '{$fieldName}': Empty string is not permitted."
            );
        }

        if ($value !== trim($value)) {
            throw new InvalidArgumentException(
                "Monetary validation failed for '{$fieldName}': Leading or trailing whitespace is not permitted."
            );
        }

        if (!preg_match(self::MONETARY_REGEX, $value)) {
            throw new InvalidArgumentException(
                "Monetary validation failed for '{$fieldName}': Malformed monetary string '{$value}'. " .
                "Must be a non-negative decimal string with at most 2 decimal places."
            );
        }

        $normalized = self::padToTwoDecimals($value);

        if (bccomp($normalized, self::MAX_DECIMAL_10_2, 2) > 0) {
            throw new InvalidArgumentException(
                "Monetary validation failed for '{$fieldName}': Value '{$normalized}' exceeds DECIMAL(10,2) limit (" . self::MAX_DECIMAL_10_2 . ').'
            );
        }

        return $normalized;
    }

    /**
     * Calculate invoice-level totals and deterministic per-head discount allocations
     * for `none`, `fixed`, or `percentage` discount modes.
     *
     * @param  array<int|string, array<string, mixed>>  $heads
     * @param  string  $discountType  One of `none`, `fixed`, `percentage`
     * @param  mixed  $discountValue  Strict decimal string (e.g. `'500.00'` or `'15.00'`)
     * @return array{
     *     discount_type: string,
     *     discount_value: string,
     *     total_gross: string,
     *     eligible_gross: string,
     *     non_discountable_gross: string,
     *     total_discount: string,
     *     total_net: string,
     *     heads: array<int|string, array<string, mixed>>
     * }
     * @throws InvalidArgumentException|LogicException
     */
    public static function calculate(
        array $heads,
        string $discountType = self::TYPE_NONE,
        mixed $discountValue = '0.00'
    ): array {
        [$normalizedHeads, $totalGross, $eligibleGross, $nonDiscountableGross] = self::normalizeHeads($heads);

        [$normalizedType, $normalizedValue, $totalDiscount] = self::resolveTargetDiscount(
            $discountType,
            $discountValue,
            $eligibleGross
        );

        $allocated = self::distributeProportionalDiscount($normalizedHeads, $totalGross, $eligibleGross, $totalDiscount);
        $totalNet = bcsub($totalGross, $totalDiscount, 2);

        return [
            'discount_type' => $normalizedType,
            'discount_value' => $normalizedValue,
            'total_gross' => $totalGross,
            'eligible_gross' => $eligibleGross,
            'non_discountable_gross' => $nonDiscountableGross,
            'total_discount' => $totalDiscount,
            'total_net' => $totalNet,
            'heads' => $allocated,
        ];
    }

    /**
     * Distribute a fixed total invoice discount across fee heads using exact integer-paisa BCMath modulo.
     *
     * @param  array<int|string, array<string, mixed>>  $heads
     * @param  mixed  $rawTotalDiscount  Strict decimal string
     * @return array<int|string, array<string, mixed>>
     * @throws InvalidArgumentException|LogicException
     */
    public static function allocate(array $heads, mixed $rawTotalDiscount): array
    {
        $summary = self::calculate($heads, self::TYPE_FIXED, $rawTotalDiscount);

        return $summary['heads'];
    }

    /**
     * Validate and normalize head rows, summing gross and eligible totals cleanly.
     *
     * @param  array<int|string, array<string, mixed>>  $heads
     * @return array{0: array<int|string, array<string, mixed>>, 1: string, 2: string, 3: string}
     * @throws InvalidArgumentException
     */
    private static function normalizeHeads(array $heads): array
    {
        if (empty($heads)) {
            throw new InvalidArgumentException('Cannot allocate discount across an empty head list.');
        }

        $totalGross = '0.00';
        $eligibleGross = '0.00';
        $nonDiscountableGross = '0.00';
        $normalized = [];
        $position = 0;

        foreach ($heads as $idx => $head) {
            if (!is_array($head) || !array_key_exists('amount', $head) || !array_key_exists('is_discountable', $head)) {
                throw new InvalidArgumentException("Malformed head structure at index {$idx}.");
            }

            if (!array_key_exists('id', $head) && !array_key_exists('head_id', $head)) {
                throw new InvalidArgumentException("Missing head identifier ('id' or 'head_id') at index {$idx}.");
            }

            if (!is_bool($head['is_discountable']) && !in_array($head['is_discountable'], [0, 1], true)) {
                throw new InvalidArgumentException("Invalid is_discountable flag at index {$idx}.");
            }

            $rawId = $head['id'] ?? $head['head_id'];
            if (!is_int($rawId) && !(is_string($rawId) && ctype_digit($rawId))) {
                throw new InvalidArgumentException("Invalid integer head identifier at index {$idx}.");
            }
            $id = (int) $rawId;

            $sortOrder = $position;
            if (array_key_exists('sort_order', $head)) {
                if (!is_int($head['sort_order']) && !(is_string($head['sort_order']) && ctype_digit($head['sort_order']))) {
                    throw new InvalidArgumentException("Invalid sort_order at index {$idx}.");
                }
                $sortOrder = (int) $head['sort_order'];
            }

            $amount = self::validateMonetaryString($head['amount'], "heads[{$idx}].amount");
            $isDiscountable = (bool) $head['is_discountable'];

            $normalized[$idx] = [
                'id' => $id,
                'head_id' => $head['head_id'] ?? $id,
                'sort_order' => $sortOrder,
                'amount' => $amount,
                'is_discountable' => $isDiscountable,
            ];

            $totalGross = bcadd($totalGross, $amount, 2);
            if ($isDiscountable) {
                $eligibleGross = bcadd($eligibleGross, $amount, 2);
            } else {
                $nonDiscountableGross = bcadd($nonDiscountableGross, $amount, 2);
            }

            $position++;
        }

        if (bccomp($totalGross, self::MAX_DECIMAL_10_2, 2) > 0) {
            throw new InvalidArgumentException(
                "Total invoice gross ({$totalGross}) exceeds DECIMAL(10,2) limit (" . self::MAX_DECIMAL_10_2 . ').'
            );
        }

        return [$normalized, $totalGross, $eligibleGross, $nonDiscountableGross];
    }

    /**
     * Validate discount mode/value and resolve target discount amount before head allocation.
     *
     * @return array{0: string, 1: string, 2: string}
     * @throws InvalidArgumentException
     */
    private static function resolveTargetDiscount(
        string $discountType,
        mixed $discountValue,
        string $eligibleGross
    ): array {
        if (!in_array($discountType, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                "Unsupported discount type '{$discountType}'. Expected one of: none, fixed, percentage."
            );
        }

        if ($discountType === self::TYPE_NONE) {
            $normalizedValue = self::validateMonetaryString($discountValue, 'discount_value');
            if (bccomp($normalizedValue, '0.00', 2) !== 0) {
                throw new InvalidArgumentException(
                    "Discount value must be '0.00' when discount type is 'none'."
                );
            }

            return [self::TYPE_NONE, '0.00', '0.00'];
        }

        if ($discountType === self::TYPE_PERCENTAGE) {
            $percentage = self::validateMonetaryString($discountValue, 'discount_value');

            if (bccomp($percentage, '100.00', 2) > 0) {
                throw new InvalidArgumentException(
                    "Percentage discount ({$percentage}%) cannot exceed 100.00%."
                );
            }

            // Reject non-zero percentage discount whenever no discountable gross exists.
            if (bccomp($percentage, '0.00', 2) > 0 && bccomp($eligibleGross, '0.00', 2) === 0) {
                throw new InvalidArgumentException(
                    'Cannot apply a non-zero percentage discount when eligible discountable gross amount is 0.00.'
                );
            }

            $targetDiscount = self::calculatePercentageAmount($eligibleGross, $percentage);

            return [self::TYPE_PERCENTAGE, $percentage, $targetDiscount];
        }

        $fixedDiscount = self::validateMonetaryString($discountValue, 'total_discount');

        // CRITICAL: Must validate limit BEFORE zero-discount early return (V6 Contract).
        if (bccomp($fixedDiscount, $eligibleGross, 2) > 0) {
            throw new InvalidArgumentException(
                "Total discount ({$fixedDiscount}) cannot exceed total discountable amount ({$eligibleGross})."
            );
        }

        return [self::TYPE_FIXED, $fixedDiscount, $fixedDiscount];
    }

    /**
     * Calculate exact percentage discount of eligible gross rounded half-up to nearest paisa without floats.
     */
    private static function calculatePercentageAmount(string $eligibleGross, string $percentage): string
    {
        if (bccomp($percentage, '0.00', 2) === 0 || bccomp($eligibleGross, '0.00', 2) === 0) {
            return '0.00';
        }

        if (bccomp($percentage, '100.00', 2) === 0) {
            return $eligibleGross;
        }

        $eligiblePaisa = bcmul($eligibleGross, '100', 0);
        $basisPoints = bcmul($percentage, '100', 0);
        $numerator = bcmul($eligiblePaisa, $basisPoints, 0);

        $floorPaisa = bcdiv($numerator, '10000', 0);
        $remainder = bcmod($numerator, '10000');

        if (bccomp($remainder, '5000', 0) >= 0) {
            $floorPaisa = bcadd($floorPaisa, '1', 0);
        }

        return bcdiv($floorPaisa, '100', 2);
    }

    /**
     * Distribute target discount proportionally across discountable heads using integer-paisa `bcmod`.
     *
     * @param  array<int|string, array<string, mixed>>  $normalizedHeads
     * @return array<int|string, array<string, mixed>>
     * @throws LogicException
     */
    private static function distributeProportionalDiscount(
        array $normalizedHeads,
        string $totalGross,
        string $eligibleGross,
        string $totalDiscount
    ): array {
        if (bccomp($totalDiscount, '0.00', 2) === 0) {
            $result = [];
            foreach ($normalizedHeads as $idx => $head) {
                $result[$idx] = self::buildAllocatedHead($head, '0.00');
            }

            self::assertInvariants($result, $totalGross, $totalDiscount);

            return $result;
        }

        $eligibleTotalPaisa = bcmul($eligibleGross, '100', 0);
        $targetDiscountPaisa = bcmul($totalDiscount, '100', 0);

        $allocated = [];
        $remainders = [];
        $distributedFloorPaisaSum = '0';

        foreach ($normalizedHeads as $idx => $head) {
            $amount = $head['amount'];

            if (!$head['is_discountable']) {
                $allocated[$idx] = self::buildAllocatedHead($head, '0.00');
                continue;
            }

            $headPaisa = bcmul($amount, '100', 0);
            $product = bcmul($headPaisa, $targetDiscountPaisa, 0);

            $floorPaisa = bcdiv($product, $eligibleTotalPaisa, 0);
            $exactRemainder = bcmod($product, $eligibleTotalPaisa);
            $floorDiscount = bcdiv($floorPaisa, '100', 2);

            $allocated[$idx] = self::buildAllocatedHead($head, $floorDiscount);

            $distributedFloorPaisaSum = bcadd($distributedFloorPaisaSum, $floorPaisa, 0);

            // Exclude 0.00 gross heads from bonus paisa distribution so they can never go negative.
            if (bccomp($amount, '0.00', 2) > 0) {
                $remainders[] = [
                    'idx' => $idx,
                    'id' => $head['id'],
                    'sort_order' => $head['sort_order'],
                    'remainder_exact' => $exactRemainder,
                ];
            }
        }

        $remainingPaisa = bcsub($targetDiscountPaisa, $distributedFloorPaisaSum, 0);
        self::distributeRemainderPaisa($allocated, $remainders, $remainingPaisa);
        self::assertInvariants($allocated, $totalGross, $totalDiscount);

        return $allocated;
    }

    /**
     * Allocate remaining 0.01 paisa units deterministically by:
     * remainder DESC -> sort_order ASC -> id ASC.
     *
     * @param  array<int|string, array<string, mixed>>  $allocated
     * @param  array<int, array<string, mixed>>  $remainders
     */
    private static function distributeRemainderPaisa(array &$allocated, array $remainders, string $remainingPaisa): void
    {
        if (bccomp($remainingPaisa, '0', 0) <= 0) {
            return;
        }

        usort($remainders, static function (array $a, array $b): int {
            $cmpRem = bccomp($b['remainder_exact'], $a['remainder_exact'], 0);
            if ($cmpRem !== 0) {
                return $cmpRem;
            }

            $cmpSort = $a['sort_order'] <=> $b['sort_order'];
            if ($cmpSort !== 0) {
                return $cmpSort;
            }

            return $a['id'] <=> $b['id'];
        });

        foreach ($remainders as $candidate) {
            if (bccomp($remainingPaisa, '0', 0) <= 0) {
                break;
            }

            $idx = $candidate['idx'];
            if (bccomp($allocated[$idx]['discount'], $allocated[$idx]['amount'], 2) >= 0) {
                continue;
            }

            $allocated[$idx]['discount'] = bcadd($allocated[$idx]['discount'], '0.01', 2);
            $allocated[$idx]['net_amount'] = bcsub($allocated[$idx]['amount'], $allocated[$idx]['discount'], 2);
            $remainingPaisa = bcsub($remainingPaisa, '1', 0);
        }
    }

    /**
     * Assert strict post-allocation financial reconciliation invariants.
     *
     * @param  array<int|string, array<string, mixed>>  $allocated
     * @throws LogicException
     */
    private static function assertInvariants(array $allocated, string $totalGross, string $totalDiscount): void
    {
        $sumGross = '0.00';
        $sumDiscount = '0.00';
        $sumNet = '0.00';

        foreach ($allocated as $idx => $item) {
            $sumGross = bcadd($sumGross, $item['amount'], 2);
            $sumDiscount = bcadd($sumDiscount, $item['discount'], 2);
            $sumNet = bcadd($sumNet, $item['net_amount'], 2);

            if (!$item['is_discountable'] && bccomp($item['discount'], '0.00', 2) !== 0) {
                throw new LogicException("Invariant Violation: Non-discountable head received discount at index {$idx}.");
            }

            if (bccomp($item['discount'], '0.00', 2) < 0) {
                throw new LogicException("Invariant Violation: Negative head discount at index {$idx}.");
            }

            if (bccomp($item['net_amount'], '0.00', 2) < 0) {
                throw new LogicException("Invariant Violation: Negative head net amount at index {$idx}.");
            }

            if (bccomp($item['discount'], $item['amount'], 2) > 0) {
                throw new LogicException("Invariant Violation: Head discount exceeds gross amount at index {$idx}.");
            }

            if (bccomp($item['net_amount'], bcsub($item['amount'], $item['discount'], 2), 2) !== 0) {
                throw new LogicException("Invariant Violation: Net arithmetic mismatch at index {$idx}.");
            }
        }

        if (bccomp($sumGross, $totalGross, 2) !== 0) {
            throw new LogicException("Invariant Violation: Sum of head gross ({$sumGross}) != invoice gross ({$totalGross}).");
        }

        if (bccomp($sumDiscount, $totalDiscount, 2) !== 0) {
            throw new LogicException("Invariant Violation: Sum of discounts ({$sumDiscount}) != expected discount ({$totalDiscount}).");
        }

        if (bccomp($sumNet, bcsub($totalGross, $totalDiscount, 2), 2) !== 0) {
            throw new LogicException("Invariant Violation: Sum of net amounts ({$sumNet}) != expected invoice net.");
        }
    }

    /**
     * Construct a normalized allocated head entry with exact BCMath net calculation.
     *
     * @param  array<string, mixed>  $head
     * @return array<string, mixed>
     */
    private static function buildAllocatedHead(array $head, string $discount): array
    {
        return [
            'id' => $head['id'],
            'head_id' => $head['head_id'],
            'sort_order' => $head['sort_order'],
            'amount' => $head['amount'],
            'discount' => $discount,
            'net_amount' => bcsub($head['amount'], $discount, 2),
            'is_discountable' => $head['is_discountable'],
        ];
    }

    /**
     * Pad a regex-validated decimal string cleanly to exact `.XX` representation via string operations.
     */
    private static function padToTwoDecimals(string $value): string
    {
        if (!str_contains($value, '.')) {
            return $value . '.00';
        }

        [$whole, $frac] = explode('.', $value, 2);

        return $whole . '.' . str_pad($frac, 2, '0', STR_PAD_RIGHT);
    }
}
