<?php
/**
 * PH411-FIX-01 Unit Tests: Strict NULL-Safe Comparison & Shared Helper Verification
 * 
 * Executes the EXACT includable helper implementation from scripts/fee_reset_helpers.php
 * across the complete audit matrix and database edge cases.
 * 
 * Verifies both controller integrations and static code paths.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

// 1. Require the actual shared helper file directly (No eval, no duplicate logic)
require_once $projectRoot . '/scripts/fee_reset_helpers.php';

// Verify function exists from the included file
if (!function_exists('valuesMatchExactly')) {
    echo "[FAIL] valuesMatchExactly() function not defined by scripts/fee_reset_helpers.php!\n";
    exit(1);
}

// 2. Verify controller integrations (both controllers must require fee_reset_helpers.php)
$resetScript = (string)file_get_contents($projectRoot . '/scripts/execute_fee_collection_reset_atomic.php');
$restoreScript = (string)file_get_contents($projectRoot . '/scripts/restore_fee_collection_reset_atomic.php');

$resetRequiresHelper = str_contains($resetScript, "require_once __DIR__ . '/fee_reset_helpers.php'");
$restoreRequiresHelper = str_contains($restoreScript, "require_once __DIR__ . '/fee_reset_helpers.php'");

echo "====================================================================\n";
echo "PH411-FIX-01: STRICT NULL-SAFE VALUE COMPARISON UNIT TEST HARNESS\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "Helper File: scripts/fee_reset_helpers.php\n";
echo "====================================================================\n\n";

$pass = 0;
$fail = 0;

// Test Controller Inclusions
if ($resetRequiresHelper) {
    $pass++;
    echo "INTEGRATION-01 [PASS]: Reset controller requires scripts/fee_reset_helpers.php\n";
} else {
    $fail++;
    echo "INTEGRATION-01 [FAIL]: Reset controller does NOT require scripts/fee_reset_helpers.php!\n";
}

if ($restoreRequiresHelper) {
    $pass++;
    echo "INTEGRATION-02 [PASS]: Restore controller requires scripts/fee_reset_helpers.php\n";
} else {
    $fail++;
    echo "INTEGRATION-02 [FAIL]: Restore controller does NOT require scripts/fee_reset_helpers.php!\n";
}

// Full Audit Matrix & Database Edge Cases
$testCases = [
    // Mandatory Audit Matrix (Section 3.2)
    ['a' => null,       'b' => null,       'expected' => true,  'desc' => 'NULL vs NULL = MATCH'],
    ['a' => null,       'b' => '',         'expected' => false, 'desc' => 'NULL vs empty string = MISMATCH'],
    ['a' => '',         'b' => null,       'expected' => false, 'desc' => 'empty string vs NULL = MISMATCH'],
    ['a' => '',         'b' => '',         'expected' => true,  'desc' => 'empty string vs empty string = MATCH'],
    ['a' => 0,          'b' => '0',        'expected' => true,  'desc' => 'integer 0 vs string "0" = MATCH (database scalar normalization)'],
    ['a' => '0',        'b' => 0,          'expected' => true,  'desc' => 'string "0" vs integer 0 = MATCH (database scalar normalization)'],
    ['a' => '27600.00', 'b' => '27600.00', 'expected' => true,  'desc' => 'decimal string comparisons (identical: "27600.00" vs "27600.00") = MATCH'],
    ['a' => '27600.00', 'b' => '27600.01', 'expected' => false, 'desc' => 'decimal string comparisons (unequal: "27600.00" vs "27600.01") = MISMATCH'],
    ['a' => 'Paid',     'b' => 'Paid',     'expected' => true,  'desc' => 'ordinary equal values ("Paid" vs "Paid") = MATCH'],
    ['a' => 'Paid',     'b' => 'Unpaid',   'expected' => false, 'desc' => 'ordinary unequal values ("Paid" vs "Unpaid") = MISMATCH'],

    // Additional Database Column Edge Cases & Scalar Normalization
    ['a' => null,       'b' => 0,          'expected' => false, 'desc' => 'NULL vs 0 = MISMATCH'],
    ['a' => null,       'b' => '0',        'expected' => false, 'desc' => 'NULL vs "0" = MISMATCH'],
    ['a' => null,       'b' => '0.00',     'expected' => false, 'desc' => 'NULL vs "0.00" = MISMATCH'],
    ['a' => '',         'b' => '0',        'expected' => false, 'desc' => 'empty string vs "0" = MISMATCH'],
    ['a' => '0',        'b' => '1',        'expected' => false, 'desc' => '"0" vs "1" = MISMATCH'],
    ['a' => 31,         'b' => '31',       'expected' => true,  'desc' => 'integer PK 31 vs string PK "31" = MATCH'],
    ['a' => '450.00',   'b' => '450.00',   'expected' => true,  'desc' => 'discount "450.00" vs "450.00" = MATCH'],
    ['a' => '28050.00', 'b' => '28050.00', 'expected' => true,  'desc' => 'gross "28050.00" vs "28050.00" = MATCH'],
    ['a' => null,       'b' => 'Paid',     'expected' => false, 'desc' => 'NULL vs "Paid" = MISMATCH'],
    ['a' => '2026-09-20 14:30:00', 'b' => '2026-09-20 14:30:00', 'expected' => true,  'desc' => 'datetime timestamp match = MATCH'],
    ['a' => '2026-09-20 14:30:00', 'b' => '2026-09-20 14:30:01', 'expected' => false, 'desc' => 'datetime timestamp mismatch = MISMATCH'],
    ['a' => null,       'b' => '2026-09-20 14:30:00', 'expected' => false, 'desc' => 'NULL vs datetime timestamp = MISMATCH'],
];

echo "\n--- UNIT TEST MATRIX EXECUTION ---\n";
foreach ($testCases as $idx => $tc) {
    $num = $idx + 1;
    $actual = valuesMatchExactly($tc['a'], $tc['b']);
    $expected = $tc['expected'];

    // Demonstrate contrast against flawed legacy logic ((string)$a === (string)$b)
    $oldFlawed = ((string)$tc['a'] === (string)$tc['b']);

    if ($actual === $expected) {
        $pass++;
        echo "CASE {$num} [PASS]: {$tc['desc']}\n";
        if ($oldFlawed !== $actual) {
            echo "  -> REMEDIATION PROVEN: Legacy logic collapsed to " . ($oldFlawed ? 'TRUE' : 'FALSE') . "; valuesMatchExactly() correctly returned " . ($actual ? 'TRUE' : 'FALSE') . "\n";
        }
    } else {
        $fail++;
        echo "CASE {$num} [FAIL]: {$tc['desc']} (Expected: " . ($expected ? 'true' : 'false') . ", Got: " . ($actual ? 'true' : 'false') . ")\n";
    }
}

// 3. Static Code Audit: Verify Zero Lingering Unsafe Comparisons
echo "\n--- STATIC AUDIT OF CONTROLLER SOURCES ---\n";
$weakInReset = preg_match_all('/\(string\)\$[a-zA-Z0-9_]+\[\$col\]\s*!==\s*\(string\)/', $resetScript, $m1);
$weakInRestore = preg_match_all('/\(string\)\$[a-zA-Z0-9_]+\[\$col\]\s*!==\s*\(string\)/', $restoreScript, $m2);

if ($weakInReset === 0 && $weakInRestore === 0) {
    echo "STATIC-01 [PASS]: Zero weak comparisons remaining in invoice/payment snapshot loops across both controllers.\n";
    $pass++;
} else {
    echo "STATIC-01 [FAIL]: Lingering weak comparisons found (Reset: {$weakInReset}, Restore: {$weakInRestore})!\n";
    $fail++;
}

// Check that valuesMatchExactly is called in all 5 required verification locations
$vmeInReset = preg_match_all('/valuesMatchExactly\(/', $resetScript, $m3);
$vmeInRestore = preg_match_all('/valuesMatchExactly\(/', $restoreScript, $m4);

echo "STATIC-02 [INFO]: valuesMatchExactly() invocations in Reset: {$vmeInReset}, Restore: {$vmeInRestore}\n";
if ($vmeInReset >= 2 && $vmeInRestore >= 4) {
    echo "STATIC-02 [PASS]: All snapshot loops in Reset and Restore use valuesMatchExactly().\n";
    $pass++;
} else {
    echo "STATIC-02 [FAIL]: Insufficient valuesMatchExactly() invocations in controllers!\n";
    $fail++;
}

echo "\n====================================================================\n";
echo "PH411-FIX-01 UNIT TEST SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
exit(0);
