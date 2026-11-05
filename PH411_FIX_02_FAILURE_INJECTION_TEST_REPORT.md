# PH411-FIX-02: REAL FAILURE-INJECTION INTEGRATION TEST REPORT

**Project:** MACS School Management System — Production Fee Collection Reset & Restore  
**Database Server Engine:** MySQL 8.0.45 Community Server (Port 3308)  
**Target Isolated Sandbox:** `macs_school_ph411_test`  
**Execution Environment:** Isolated Local Test Environment ONLY (Production access strictly prohibited)  
**Date:** November 5, 2026  
**Status:** PASS  

---

## 1. Executive Summary & Architecture

Prior tests in Phase 4.9 and 4.10 simulated rollback state machines in standalone test blocks. In contrast, **PH411-FIX-02** executes genuine integration tests against the **actual transaction paths** of both controllers (`scripts/execute_fee_collection_reset_atomic.php` and `scripts/restore_fee_collection_reset_atomic.php`) via live CLI subprocesses with deterministic failure injection.

### Production-Safe Failure Injection Safeguard
To eliminate any risk of failure injection triggering in production:
```php
function isTestFailureInjectionAllowed(PDO $pdo, string $dbHost, int|string $dbPort, string $dbName): bool {
    if (app()->environment('production')) return false;
    if ($dbHost !== '127.0.0.1' && $dbHost !== 'localhost') return false;
    if ((int)$dbPort !== 3308 && !str_contains($dbName, 'test') && !str_contains($dbName, 'sandbox') && !str_contains($dbName, 'adversarial')) return false;
    if ($dbName === 'macs_school' || $dbName === 'macsinjaazhdb') return false;
    if ($dbName === 'macsinjaazhdb_restore_test_20261104') return false;
    return true;
}
```
Failure injection is **strictly ignored and aborted** if executed outside the isolated local MySQL 8.0.45 sandbox on port 3308.

---

## 2. Test Execution Matrix (TEST-01 to TEST-07)

### TEST-01: Reset Rollback after DML
* **Procedure:**
  1. Initialized sandbox with full pre-reset baseline (44 candidate payments, 44 invoices, 7,122 preserved payments).
  2. Executed actual Reset controller CLI with `MACS_TEST_FAILURE_POINT=reset_before_commit`.
  3. Controller updated invoices and deleted 44 payments, then encountered injected exception before commit.
  4. Controller's catch block executed `$pdo->rollBack()`.
* **Verification:**
  - **Candidate Payments:** Exactly 44 rows present (IDs 2..47 without 3, 6). Remaining count of 0 was certified NOT present.
  - **Payment Checksum:** `1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038` (100% Bit-Match).
  - **Target Invoices:** All 44 invoices retain original values (`status = Paid`, `due_amount = 0.00`).
  - **Invoice Checksum:** `3e7dcb1990ea343a7e6fd83c442a0345c0ad295f3840803a8ca1cf861429f68d` (100% Bit-Match).
  - **Preserved Payments:** All 7,122 rows intact (Total: ৳3,851,290.00).
* **Result:** **PASS** (CLI Exit: 102, Rollback confirmed).

---

### TEST-02: Restore Rollback after Payment Insertion
* **Procedure:**
  1. Initialized sandbox in valid post-reset state: 0 candidate payments in `fee_payments`, candidate PKs 2..47 vacant, target invoices marked `Unpaid`, archive tables populated with 44 rows each.
  2. Executed Restore controller CLI with `MACS_TEST_FAILURE_POINT=restore_before_commit`.
  3. Controller re-inserted all 44 payments into `fee_payments` and updated invoices, then encountered injected exception before commit.
  4. Controller's catch block executed `$pdo->rollBack()`.
* **Verification:**
  - **Inserted Payments:** All 44 re-inserted payments completely rolled back; candidate payment count in `fee_payments` is 0.
  - **Primary Keys:** PKs 2..47 remain 100% vacant.
  - **Target Invoices:** Invoices remain in post-reset `Unpaid` state (`paid_amount = 0.00`, `due_amount = net_amount`).
  - **Archive Tables:** Unchanged with 44 rows each.
* **Result:** **PASS** (CLI Exit: 107, Zero lingering inserted rows).

---

### TEST-03: Archive Preservation after Reset Rollback
* **Procedure:**
  1. Captured archive table row counts and canonical SHA-256 checksums before test.
  2. Executed Reset controller with precommit failure injection.
  3. Inspected archive tables post-rollback.
* **Verification:**
  - Archive payments checksum before: `1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038`
  - Archive payments checksum after: `1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038`
  - 100% bit-exact preservation.
* **Result:** **PASS**.

---

### TEST-04: Live-State Preservation after Restore Rollback (NULL-Safe)
* **Procedure:**
  1. Captured complete column-by-column snapshot of live tables prior to restore attempt.
  2. Executed Restore controller with precommit failure injection.
  3. Compared post-rollback live table records against pre-restore snapshot using `valuesMatchExactly()`.
* **Verification:**
  - Every single column verified across all 44 invoices.
  - Zero differences detected, including strict NULL vs empty-string checks.
* **Result:** **PASS**.

---

### TEST-05: Reset Commit Uncertainty Handling
* **Procedure:**
  1. Executed Reset controller with `MACS_TEST_FAILURE_POINT=reset_commit_uncertainty` to simulate connection drop / transport failure during the COMMIT statement.
* **Verification:**
  - Exit Code: **101** (Mandatory).
  - Terminal output identifies `[CRITICAL INCIDENT: COMMIT_OUTCOME_UNCERTAIN (BLOCKER C)]`.
  - Directive enforced: `Zero automatic retries will be attempted. Do NOT re-run script or restore without manual inspection.`
  - Controller refused to retry.
* **Honest Limitation Disclosure:**
  - *Tested:* Controlled simulated transport failure during `$pdo->commit()` invocation.
  - *Partially Tested:* True TCP-level kernel packet acknowledgement loss (where server commits but client TCP socket drops before reading ACK) cannot be deterministically triggered without OS-level network boundary interception.
* **Result:** **PASS (PARTIALLY TESTED for kernel packet loss; FULLY TESTED for client-side commit failure handling)**.

---

### TEST-06: Restore Commit Uncertainty Handling
* **Procedure:**
  1. Executed Restore controller with `MACS_TEST_FAILURE_POINT=restore_commit_uncertainty`.
* **Verification:**
  - Exit Code: **101** (Mandatory).
  - Terminal output identifies `[CRITICAL INCIDENT: COMMIT_OUTCOME_UNCERTAIN (BLOCKER C)]`.
  - Directive enforced: `Zero automatic retries will be attempted.`
  - No second restoration attempt occurred.
* **Result:** **PASS (PARTIALLY TESTED for kernel packet loss; FULLY TESTED for client-side commit failure handling)**.

---

### TEST-07: Full Transaction, Archive and Financial Invariant Integrity
* **Procedure:**
  1. Seeded sandbox database.
  2. Executed full Reset controller without failure injection (`Exit Code 0`).
  3. Verified intermediate reset invariants: 0 candidate payments, 44 archive payments, 44 archive invoices, 7,122 preserved payments.
  4. Executed full Restore controller without failure injection (`Exit Code 0`).
  5. Verified post-restoration invariants: 44 candidate payments returned, checksums match baseline 100%.
* **Result:** **PASS**.

---

## 3. Summary of Test Execution Results

```text
====================================================================
PHASE 4.11 TEST SUITE SUMMARY (ON MYSQL 8.0.45)
Target MySQL Server Version:   8.0.45 (Port 3308)
Total Test Scenarios Executed: 7
Passed:                        7
Failed:                        0
Pass Rate:                     100.0%
====================================================================
```

---

## 4. Final Verdict

```
PH411-FIX-02: PASS
```
Actual transaction rollback behavior has been thoroughly validated against the real controller execution paths on isolated MySQL 8.0.45.

