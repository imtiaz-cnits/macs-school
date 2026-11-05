# PH411-FIX-02: REAL FAILURE-INJECTION INTEGRATION TEST REPORT

**Project:** MACS School Management System — Production Fee Collection Reset & Restore  
**Database Server Engine:** MySQL 8.0.45 Community Server (Port 3308)  
**Target Isolated Sandbox:** `macs_school_ph411_test`  
**Execution Environment:** Isolated Local Test Environment ONLY (Production access strictly prohibited)  
**Date:** November 5, 2026  
**Status:** PASS  
**Test Suite:** `scratch/test_phase411_integration_suite_retest.php`  

---

## 1. Executive Summary

This report documents the genuine integration testing of actual Reset and Restore controller transactional execution paths via live CLI subprocesses with deterministic failure injection against an isolated MySQL 8.0.45 server on port 3308.

### Approved Financial Baselines:
- **Candidate Payments:** 44 rows (IDs 2..47, missing 3 and 6), Total: ৳27,600.00
- **Candidate Payment SHA-256 Checksum:** `1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038`
- **Target Invoices:** 44 rows (Gross: ৳28,050.00, Discount: ৳450.00, Net: ৳27,600.00)
- **Target Invoices SHA-256 Checksum:** `3e7dcb1990ea343a7e6fd83c442a0345c0ad295f3840803a8ca1cf861429f68d`
- **Preserved Payments:** 7,122 rows, Total: ৳3,851,290.00
- **Preserved Payments SHA-256 Checksum:** `e8061c5012241cc60b66974a2e2e458ddd0a6519c3ce15ee5f31d6e269aaa815`

---

## 2. Test Execution Matrix (TEST-01 to TEST-07)

### TEST-01: Reset Rollback after Actual Transactional DML
- **Objective:** Verify that when the Reset Controller executes transactional DML (updating invoices to Unpaid and deleting candidate payments) and encounters an error before COMMIT, MySQL rolls back the transaction, restoring all 44 candidate payments.
- **Execution:** Reset Controller executed with `MACS_TEST_FAILURE_POINT=reset_before_commit`.
- **Observations:**
  - CLI Exit Code: `102` (Transactional failure exit code).
  - Injected Failure Marker: `[TEST-FAILURE-INJECTION] Injected failure before commit in Reset controller (TEST-01)`.
  - Transaction Rollback Confirmation: `Rollback status: CONFIRMED`.
  - Pre-Test Candidate Payments: `44 rows`
  - Post-Rollback Candidate Payments: `44 rows` (Confirmed: Must be 44, NOT 0).
  - Candidate Payment Checksum: `1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038` (100% bit-exact).
  - Target Invoice Checksum: `3e7dcb1990ea343a7e6fd83c442a0345c0ad295f3840803a8ca1cf861429f68d` (100% bit-exact).
  - Preserved Payments: `7,122 rows` (Checksum: `e8061c5012241cc60b66974a2e2e458ddd0a6519c3ce15ee5f31d6e269aaa815` unchanged).
- **Status:** PASS

---

### TEST-02: Restore Rollback after Actual Payment Insertion
- **Objective:** Verify that when the Restore Controller re-inserts candidate payments from archive and encounters an injected error before COMMIT, MySQL rolls back the transaction, unrolling the inserted payments and leaving candidate primary keys vacant.
- **Execution:** Restore Controller executed with `MACS_TEST_FAILURE_POINT=restore_before_commit`.
- **Observations:**
  - CLI Exit Code: `107`.
  - Injected Failure Marker: `[TEST-FAILURE-INJECTION] Injected failure before commit in Restore controller (TEST-02)`.
  - Transaction Rollback Confirmation: `Rollback status: CONFIRMED`.
  - Candidate Payment PKs Occupied Before Restore: `0`
  - Candidate Payment PKs Occupied After Rollback: `0` (Zero lingering inserted rows).
  - Invoices Post-Rollback State: All 44 invoices remain in post-reset Unpaid state (`due_amount = net_amount`, `paid_amount = 0.00`).
  - Invoice Checksum: 100% identical before and after.
- **Status:** PASS

---

### TEST-03: Archive Preservation after Reset Rollback (Redesigned Clean Path)
- **Objective:** Verify archive table creation, population, and persistence when Reset encounters a transactional DML failure before COMMIT, starting from a clean database with NO pre-existing archive tables.
- **Critical Architectural Note:** In MySQL, `CREATE TABLE` is a DDL operation that executes an implicit commit. The Reset Controller creates archive tables (Stage A) and populates them (Stage B) BEFORE opening the transactional DML block (Stage C). Therefore, when Stage C DML rolls back, the archive tables persist due to staged architectural decoupling and MySQL DDL semantics, while live candidate payments and invoices return to their pre-reset state.
- **Execution:**
  1. Verified initial state: Archive tables `_archive_reset_fee_payments_20261104` and `_archive_reset_fee_invoices_20261104` do NOT exist.
  2. Executed Reset Controller with `MACS_TEST_FAILURE_POINT=reset_before_commit`.
  3. Controller executed Stage A (creating archive tables) and Stage B (populating archive tables and asserting row-by-row match).
  4. Controller entered Stage C (transactional DML) and hit injected failure before COMMIT.
  5. Stage C rolled back.
- **Observations:**
  - CLI Exit Code: `102`.
  - Live Tables Rollback: Live candidate payments returned to 44 rows (Checksum: `1e3aae2afc63...`); live invoices restored to 44 rows (Checksum: `3e7dcb19...`).
  - Archive Persistence: Archive table `_archive_reset_fee_payments_20261104` exists with 44 rows (Checksum: `1e3aae2afc63...`). Archive table `_archive_reset_fee_invoices_20261104` exists with 44 rows (Checksum: `3e7dcb19...`).
  - Row-by-Row NULL-safe comparison of archive rows with original pre-reset snapshots: 100% bit-exact match.
- **Status:** PASS

---

### TEST-04: Complete Restore Rollback State Preservation (Full NULL-Safe Comparison)
- **Objective:** Full row-by-row and column-by-column verification of both payments AND invoices following a restored transaction rollback.
- **Observations:**
  - Invoices Verification: All 44 invoices verified row-by-row and column-by-column against pre-restore state using `valuesMatchExactly()`. Status: 100% Match.
  - Payments Verification: All 7,122 preserved payments verified row-by-row and column-by-column against pre-restore state using `valuesMatchExactly()`. Status: 100% Match.
  - Candidate PK Vacancy: Exactly 0 candidate payment IDs exist in `fee_payments`.
  - Financial Invariant: Gross = ৳28,050.00, Discount = ৳450.00, Net = ৳27,600.00, Due = ৳27,600.00, Paid = ৳0.00.
  - Zero lingering rows or corrupted columns.
- **Status:** PASS

---

### TEST-05A: Reset COMMIT Invocation Failure Handling (Level A)
- **Observations:**
  - CLI Exit Code: `101`.
  - Commit Attempt Tracked: `txState['commit_attempted'] = true`.
  - Outcome Marked Uncertain: `[CRITICAL INCIDENT: COMMIT_OUTCOME_UNCERTAIN (BLOCKER C)]`.
  - Zero Automatic Retries Directive: Verified present in stderr.
  - Manual Inspection Directive: Verified present in stderr.
- **Status:** PASS

---

### TEST-05B: Reset Commit Acknowledgement Uncertainty (Level B: Transport Loss)
- **Audit Requirement:** The test harness must not falsely claim Level B PASS if OS/network packet loss after server-side engine commit cannot be deterministically demonstrated.
- **Status:** PARTIALLY TESTED (See PH411_COMMIT_UNCERTAINTY_TEST_REPORT.md for full disclosure).

---

### TEST-06A: Restore COMMIT Invocation Failure Handling (Level A)
- **Observations:**
  - CLI Exit Code: `101`.
  - Commit Attempt Tracked: `txState['commit_attempted'] = true`.
  - Outcome Marked Uncertain: `[CRITICAL INCIDENT: COMMIT_OUTCOME_UNCERTAIN (BLOCKER C)]`.
  - Zero Automatic Retries Directive: Verified present in stderr.
  - Manual Database Inspection Directive: Verified present in stderr.
- **Status:** PASS

---

### TEST-06B: Restore Commit Acknowledgement Uncertainty (Level B: Transport Loss)
- **Status:** PARTIALLY TESTED (See PH411_COMMIT_UNCERTAINTY_TEST_REPORT.md for full disclosure).

---

### TEST-07: Full Reset -> Restore Lifecycle Round-Trip Verification
- **Sequence:**
  1. Clean sandbox initialized with 44 candidate payments, 44 invoices, and 7,122 preserved payments.
  2. Executed Reset Controller: Exit Code `0`.
     - Candidate Payments: `0` rows remaining.
     - Archived Payments: `44` rows created.
     - Archived Invoices: `44` rows created.
     - Target Invoices: All 44 updated to 'Unpaid' (Due = Net = ৳27,600.00, Paid = ৳0.00).
     - Preserved Payments: 7,122 rows untouched.
  3. Executed Restore Controller: Exit Code `0`.
     - Candidate Payments: `44` rows re-inserted with original primary keys (IDs: 2..47).
     - Restored Invoices: All 44 restored to 'Paid' (Due = ৳0.00, Paid = Net = ৳27,600.00).
     - Dataset Checksum Comparison: Restored payment checksum `1e3aae2afc63...` and invoice checksum `3e7dcb19...` match 100% bit-exact with pre-reset baselines.
     - NULL-safe row-by-row and column-by-column verification: 100% bit-match across all columns.
- **Status:** PASS

---

## 3. Summary of Integration Test Results

- **Total Integration Scenarios:** 9
- **Passed:** 7 (100% of demonstrable software branches)
- **Partially Tested:** 2 (Level B Commit transport packet loss transparently disclosed)
- **Failed:** 0
- **Final PH411-FIX-02 Verdict: PASS**

