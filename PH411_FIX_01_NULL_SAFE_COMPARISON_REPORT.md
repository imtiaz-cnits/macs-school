# PH411-FIX-01: STRICT NULL-SAFE VALUE COMPARISON REPORT

**Project:** MACS School Management System — Production Fee Collection Reset & Restore  
**Phase:** Phase 4.11 Remediation  
**Scope:** Strict NULL-Safe Value Comparison Implementation & Verification  
**Date:** November 5, 2026  
**Status:** PASS  

---

## 1. Executive Summary

In previous implementations, invoice snapshot comparisons across both the Reset and Restore controllers utilized PHP string casting:
```php
(string)$a !== (string)$b
```
In PHP, casting `null` to a string yields an empty string `""` (`(string)null === ""`). Consequently, if an invoice snapshot column held a database `SQL NULL` while the comparing live column held `""`, the comparison treated them as equal. For an exact financial audit and restoration integrity, this NULL-collapsing behavior is unacceptable.

Under **PH411-FIX-01**, both controllers were completely audited, and all snapshot comparisons were remediated with a strict NULL-safe helper:
```php
function valuesMatchExactly(mixed $a, mixed $b): bool
{
    if ($a === null || $b === null) {
        return $a === $b;
    }

    return (string)$a === (string)$b;
}
```

---

## 2. Controller Source Code Audit & Remediations

### 1. Reset Controller (`scripts/execute_fee_collection_reset_atomic.php`)
* **Line 34:** Introduced `valuesMatchExactly(mixed $a, mixed $b): bool` wrapped in `if (!function_exists('valuesMatchExactly'))`.
* **Line 630:** Remediated in-transaction pre-commit non-reset-controlled column snapshot validation:
  ```php
  // BEFORE:
  if ((string)$inv[$col] !== (string)$pre[$col])
  // AFTER:
  if (!valuesMatchExactly($inv[$col], $pre[$col]))
  ```
* **Line 802:** Remediated post-commit independent invoice snapshot comparison against archive:
  ```php
  // BEFORE:
  if ((string)$postInv[$col] !== (string)$archInv[$col])
  // AFTER:
  if (!valuesMatchExactly($postInv[$col], $archInv[$col]))
  ```

### 2. Restore Controller (`scripts/restore_fee_collection_reset_atomic.php`)
* **Line 33:** Introduced `valuesMatchExactly(mixed $a, mixed $b): bool` wrapped in `if (!function_exists('valuesMatchExactly'))`.
* **Line 324:** Remediated pre-restoration schema-aware comparison against archive:
  ```php
  // BEFORE:
  if ((string)$liveInv[$col] !== (string)$archInv[$col])
  // AFTER:
  if (!valuesMatchExactly($liveInv[$col], $archInv[$col]))
  ```
* **Line 470:** Remediated in-transaction locked invoice snapshot comparison:
  ```php
  // BEFORE:
  if ((string)$tLive[$col] !== (string)$tArch[$col])
  // AFTER:
  if (!valuesMatchExactly($tLive[$col], $tArch[$col]))
  ```
* **Line 556:** Remediated in-transaction pre-commit post-restoration invoice snapshot check:
  ```php
  // BEFORE:
  if ((string)$rInv[$col] !== (string)$aInv[$col])
  // AFTER:
  if (!valuesMatchExactly($rInv[$col], $aInv[$col]))
  ```

---

## 3. Unit Test Verification Matrix

A dedicated unit test suite (`scratch/test_ph411_fix01_unit.php`) was executed to verify all mandatory values and database column types:

| Test # | Value A | Value B | Expected | Actual Result | Status |
| :---: | :--- | :--- | :---: | :---: | :---: |
| **01** | `NULL` | `NULL` | MATCH (true) | `true` | **PASS** |
| **02** | `NULL` | `""` (empty string) | MISMATCH (false) | `false` (Old logic: `true` [FLAWED]) | **PASS** |
| **03** | `""` (empty string) | `NULL` | MISMATCH (false) | `false` (Old logic: `true` [FLAWED]) | **PASS** |
| **04** | `""` (empty string) | `""` (empty string) | MATCH (true) | `true` | **PASS** |
| **05** | `"0"` | `"0"` | MATCH (true) | `true` | **PASS** |
| **06** | `"0"` | `"1"` | MISMATCH (false) | `false` | **PASS** |
| **07** | `0` (integer) | `"0"` (string) | MATCH (true) | `true` | **PASS** |
| **08** | `"450.00"` | `"450.00"` | MATCH (true) | `true` | **PASS** |
| **09** | `NULL` | `0` (integer) | MISMATCH (false) | `false` | **PASS** |
| **10** | `NULL` | `"0"` (string) | MISMATCH (false) | `false` | **PASS** |
| **11** | `NULL` | `"0.00"` (decimal) | MISMATCH (false) | `false` | **PASS** |
| **12** | `""` (empty string) | `"0"` (string) | MISMATCH (false) | `false` | **PASS** |
| **13** | `31` (int PK) | `"31"` (string PK) | MATCH (true) | `true` | **PASS** |
| **14** | `"28050.00"` | `"28050.00"` | MATCH (true) | `true` | **PASS** |
| **15** | `"Paid"` | `"Paid"` | MATCH (true) | `true` | **PASS** |
| **16** | `"Paid"` | `"Unpaid"` | MISMATCH (false) | `false` | **PASS** |
| **17** | `NULL` | `"Paid"` | MISMATCH (false) | `false` | **PASS** |
| **18** | `"2026-09-20 14:30:00"` | `"2026-09-20 14:30:00"` | MATCH (true) | `true` | **PASS** |
| **19** | `"2026-09-20 14:30:00"` | `"2026-09-20 14:30:01"` | MISMATCH (false) | `false` | **PASS** |
| **20** | `NULL` | `"2026-09-20 14:30:00"` | MISMATCH (false) | `false` | **PASS** |
| **21** | Static Regex Audit | Zero weak comparisons | 0 weak matches | 0 weak matches found | **PASS** |

---

## 4. Final Verdict

```
PH411-FIX-01: PASS
```
All relevant weak comparison locations have been remediated. Both controllers strictly differentiate between `SQL NULL` and empty string `""`.

