# PH411-FIX-01: STRICT NULL-SAFE VALUE COMPARISON RETEST REPORT

**Project:** MACS School Management System — Production Fee Collection Reset & Restore  
**Scope:** Remediation and Re-Verification of PH411-FIX-01  
**Execution Date:** November 5, 2026  
**Status:** PASS  
**Test Harness:** `scratch/test_ph411_fix01_unit_retest.php`  
**Shared Source:** `scripts/fee_reset_helpers.php`  

---

## 1. Remediation Objective

The Phase 4.11 Independent Audit noted that the previous unit test harness evaluated a duplicated, extracted `eval()` version of `valuesMatchExactly()`, rather than the actual includable implementation.

In this remediation:
1. `valuesMatchExactly(mixed $a, mixed $b): bool` was extracted to a shared, safely includable library file: `scripts/fee_reset_helpers.php`.
2. Both controllers (`scripts/execute_fee_collection_reset_atomic.php` and `scripts/restore_fee_collection_reset_atomic.php`) include this exact file.
3. The unit test harness includes and executes the exact same function directly.

---

## 2. Core Implementation (`scripts/fee_reset_helpers.php`)

```php
if (!function_exists('valuesMatchExactly')) {
    function valuesMatchExactly(mixed $a, mixed $b): bool {
        if ($a === null || $b === null) {
            return $a === $b;
        }
        return (string)$a === (string)$b;
    }
}
```

### Mathematical & Database Normalization Properties:
- `NULL === NULL` evaluates to `true`.
- `NULL === ''` evaluates to `false` (Fixes the legacy PHP type-juggling collapse where `(string)null === ""` evaluated to `true`).
- `'' === NULL` evaluates to `false`.
- `'' === ''` evaluates to `true`.
- `0 === "0"` evaluates to `true` (Supports MySQL PDO string mode normalization).
- `'27600.00' === '27600.00'` evaluates to `true`.
- `'27600.00' === '27600.01'` evaluates to `false`.

---

## 3. Unit Test Execution Results (26 / 26 PASS)

| Test ID | Input A | Input B | Expected | Actual | Status | Audit Note |
|---|---|---|---|---|---|---|
| CASE 1 | `null` | `null` | true | true | PASS | Audit Matrix: NULL vs NULL = MATCH |
| CASE 2 | `null` | `""` | false | false | PASS | Audit Matrix: NULL vs "" = MISMATCH (Legacy collapsed to true) |
| CASE 3 | `""` | `null` | false | false | PASS | Audit Matrix: "" vs NULL = MISMATCH (Legacy collapsed to true) |
| CASE 4 | `""` | `""` | true | true | PASS | Audit Matrix: "" vs "" = MATCH |
| CASE 5 | `0` | `"0"` | true | true | PASS | Audit Matrix: int 0 vs "0" = MATCH (Scalar normalization) |
| CASE 6 | `"0"` | `0` | true | true | PASS | Scalar normalization commutative check |
| CASE 7 | `"27600.00"` | `"27600.00"` | true | true | PASS | Decimal string comparison: MATCH |
| CASE 8 | `"27600.00"` | `"27600.01"` | false | false | PASS | Decimal string comparison: MISMATCH |
| CASE 9 | `"Paid"` | `"Paid"` | true | true | PASS | Ordinary equal strings = MATCH |
| CASE 10 | `"Paid"` | `"Unpaid"` | false | false | PASS | Ordinary unequal strings = MISMATCH |
| CASE 11 | `null` | `0` | false | false | PASS | NULL vs 0 = MISMATCH |
| CASE 12 | `null` | `"0"` | false | false | PASS | NULL vs "0" = MISMATCH |
| CASE 13 | `null` | `"0.00"` | false | false | PASS | NULL vs "0.00" = MISMATCH |
| CASE 14 | `""` | `"0"` | false | false | PASS | "" vs "0" = MISMATCH |
| CASE 15 | `"0"` | `"1"` | false | false | PASS | "0" vs "1" = MISMATCH |
| CASE 16 | `31` | `"31"` | true | true | PASS | Integer PK 31 vs string PK "31" = MATCH |
| CASE 17 | `"450.00"` | `"450.00"` | true | true | PASS | Discount decimal match = MATCH |
| CASE 18 | `"28050.00"` | `"28050.00"` | true | true | PASS | Gross decimal match = MATCH |
| CASE 19 | `null` | `"Paid"` | false | false | PASS | NULL vs string = MISMATCH |
| CASE 20 | `"2026-09-20 14:30:00"` | `"2026-09-20 14:30:00"` | true | true | PASS | Datetime timestamp match = MATCH |
| CASE 21 | `"2026-09-20 14:30:00"` | `"2026-09-20 14:30:01"` | false | false | PASS | Datetime timestamp mismatch = MISMATCH |
| CASE 22 | `null` | `"2026-09-20 14:30:00"` | false | false | PASS | NULL vs Datetime timestamp = MISMATCH |

---

## 4. Controller Integration & Static Source Audit

1. **INTEGRATION-01:** Verified `scripts/execute_fee_collection_reset_atomic.php` includes `fee_reset_helpers.php` (PASS).
2. **INTEGRATION-02:** Verified `scripts/restore_fee_collection_reset_atomic.php` includes `fee_reset_helpers.php` (PASS).
3. **STATIC-01:** Static regex search across both controllers certified **0 remaining weak comparisons** (`(string)$inv[$col] !== (string)$pre[$col]`) in snapshot verification loops.
4. **STATIC-02:** All 5 required comparison locations across Reset and Restore invoke `valuesMatchExactly()`:
   - Reset Controller: Precommit invoice snapshot comparison (Line 634)
   - Reset Controller: Postcommit invoice/archive comparison (Line 808)
   - Restore Controller: Preflight invoice/archive comparison (Line 297)
   - Restore Controller: Locked transaction snapshot comparison (Line 440)
   - Restore Controller: Pre-commit restored invoice comparison (Line 527)
   - Restore Controller: Post-commit restored invoice & payment comparison (Lines 612, 630)

**Final PH411-FIX-01 Verdict: PASS**

