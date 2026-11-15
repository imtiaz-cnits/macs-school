<?php

namespace App\Helpers;

class NumberToWords
{
    private static array $units = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
    ];

    private static array $tens = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    ];

    /**
     * Convert numeric monetary amount into South Asian English words.
     * Currency format: Taka and Paisa with "Only." termination.
     *
     * Processes decimal strings directly to avoid IEEE 754 binary floating-point representation bugs.
     *
     * @param float|int|string $amount
     * @return string
     */
    public static function convert(float|int|string $amount): string
    {
        // 1. Sanitize input: remove commas, spaces, and currency symbols
        $str = trim(str_replace([',', ' ', '৳', 'Tk', 'tk', '$'], '', (string)$amount));

        // 2. Validate numeric input
        if ($str === '' || !is_numeric($str)) {
            return 'Zero Taka Only.';
        }

        // 3. Documented Negative Amount Policy:
        // In school fee receipts, negative values indicate invalid/credit amounts.
        // For audit security and presentation integrity, negative values return 'Zero Taka Only.'.
        if (str_starts_with($str, '-') || (float)$str < 0) {
            return 'Zero Taka Only.';
        }

        // 4. Safe float normalization:
        // If an actual float primitive is received from a legacy caller, format to 2 decimal places.
        if (is_float($amount)) {
            $str = number_format($amount, 2, '.', '');
        }

        // 5. Exact Decimal Splitting (Zero float multiplication / pure integer extraction)
        if (strpos($str, '.') !== false) {
            list($takaStr, $paisaStr) = explode('.', $str, 2);
            $taka = (int)$takaStr;
            $cleanPaisa = substr(str_pad(rtrim($paisaStr), 2, '0'), 0, 2);
            $paisa = (int)$cleanPaisa;
        } else {
            $taka = (int)$str;
            $paisa = 0;
        }

        // 6. Zero check after parsing
        if ($taka === 0 && $paisa === 0) {
            return 'Zero Taka Only.';
        }

        // 7. Generate South Asian words
        $takaWords = self::convertIntegerToWords($taka);
        $result = $takaWords ? $takaWords . ' Taka' : '';

        if ($paisa > 0) {
            $paisaWords = self::convertIntegerToWords($paisa);
            $result .= ($result ? ' and ' : '') . $paisaWords . ' Paisa';
        }

        return trim($result) . ' Only.';
    }

    /**
     * Exact decimal addition of two monetary values.
     * Uses BCMath if available; falls back to pure 64-bit integer paisa arithmetic.
     * Zero floating-point arithmetic is used.
     *
     * @param string|int|float $a
     * @param string|int|float $b
     * @return string Exact decimal string with 2 decimal places (e.g. "6060.00")
     */
    public static function add(string|int|float $a, string|int|float $b): string
    {
        $cleanA = trim(str_replace([',', ' '], '', (string)$a));
        $cleanB = trim(str_replace([',', ' '], '', (string)$b));

        if ($cleanA === '' || !is_numeric($cleanA)) $cleanA = '0.00';
        if ($cleanB === '' || !is_numeric($cleanB)) $cleanB = '0.00';

        // 1. If BCMath extension is loaded, use bcadd
        if (extension_loaded('bcmath') && function_exists('bcadd')) {
            return bcadd($cleanA, $cleanB, 2);
        }

        // 2. Pure PHP integer-paisa fallback (zero external dependencies)
        $toPaisa = function (string $val): int {
            if (strpos($val, '.') !== false) {
                list($t, $p) = explode('.', $val, 2);
                $p = substr(str_pad(rtrim($p), 2, '0'), 0, 2);
                return ((int)$t * 100) + (int)$p;
            }
            return (int)$val * 100;
        };

        $totalPaisa = $toPaisa($cleanA) + $toPaisa($cleanB);
        $taka = intdiv($totalPaisa, 100);
        $paisa = abs($totalPaisa % 100);

        return sprintf('%d.%02d', $taka, $paisa);
    }

    /**
     * Format an exact decimal string with thousands grouping commas without floating-point conversion.
     *
     * @param string|int|float $amount
     * @return string Formatted currency string (e.g. "6,060.00")
     */
    public static function formatDecimal(string|int|float $amount): string
    {
        $str = trim(str_replace([',', ' '], '', (string)$amount));
        if ($str === '' || !is_numeric($str)) {
            return '0.00';
        }

        if (strpos($str, '.') !== false) {
            list($takaStr, $paisaStr) = explode('.', $str, 2);
            $paisa = substr(str_pad(rtrim($paisaStr), 2, '0'), 0, 2);
            return number_format((int)$takaStr) . '.' . $paisa;
        }

        return number_format((int)$str) . '.00';
    }

    /**
     * Recursively convert integer numbers according to the South Asian numbering system
     * (Units, Tens, Hundreds, Thousands, Lakhs, Crores).
     *
     * @param int $num
     * @return string
     */
    private static function convertIntegerToWords(int $num): string
    {
        if ($num === 0) {
            return '';
        }

        if ($num < 20) {
            return self::$units[$num];
        }

        if ($num < 100) {
            $ten = (int)($num / 10);
            $rest = $num % 10;
            return self::$tens[$ten] . ($rest > 0 ? ' ' . self::$units[$rest] : '');
        }

        if ($num < 1000) {
            $hundred = (int)($num / 100);
            $rest = $num % 100;
            return self::$units[$hundred] . ' Hundred' . ($rest > 0 ? ' ' . self::convertIntegerToWords($rest) : '');
        }

        // South Asian: 1,000 to 99,999 (Thousand)
        if ($num < 100000) {
            $thousand = (int)($num / 1000);
            $rest = $num % 1000;
            return self::convertIntegerToWords($thousand) . ' Thousand' . ($rest > 0 ? ' ' . self::convertIntegerToWords($rest) : '');
        }

        // South Asian: 1,00,000 to 99,99,999 (Lakh)
        if ($num < 10000000) {
            $lakh = (int)($num / 100000);
            $rest = $num % 100000;
            return self::convertIntegerToWords($lakh) . ' Lakh' . ($rest > 0 ? ' ' . self::convertIntegerToWords($rest) : '');
        }

        // South Asian: 1,00,00,000+ (Crore)
        $crore = (int)($num / 10000000);
        $rest = $num % 10000000;
        return self::convertIntegerToWords($crore) . ' Crore' . ($rest > 0 ? ' ' . self::convertIntegerToWords($rest) : '');
    }
}
