# MACS SCHOOL — PHASE 4.11 REMEDIATION CHANGELOG

**Project:** MACS School Management System — Production Fee Collection Reset & Restore  
**Phase:** 4.11 Remediation & Re-Certification  
**Date:** November 5, 2026  
**Author:** Senior Laravel/PHP Engineer, MySQL Transaction Specialist & Independent QA Auditor  
**Production Status:** STRICTLY ON HOLD (Zero Production Mutations, Zero Remote Operations)  

---

## 1. Overview of Changes

Following the Phase 4.11 Independent Audit findings, this remediation cycle corrected test-design weaknesses, hardened failure-injection security guards, eliminated helper code drift, corrected TEST-03 archive preservation testing, implemented full-dataset payment verification in TEST-04, and transparently disclosed COMMIT uncertainty limitations.

---

## 2. File-by-File Detailed Changelog

### A. New File: `scripts/fee_reset_helpers.php`
- **Creation of Single Source of Truth:** Created a shared, safely includable library containing:
  - `valuesMatchExactly(mixed $a, mixed $b): bool`: Strict NULL-safe value comparison logic.
  - `isTestFailureInjectionAllowed(PDO $pdo, ?string $dbHost = null, int|string|null $dbPort = null, ?string $dbName = null): bool`: Hardened fail-closed runtime safety guard.
- **Elimination of Duplication & Code Drift:** Both controllers (`execute_fee_collection_reset_atomic.php` and `restore_fee_collection_reset_atomic.php`) and all test harnesses include this exact file. Replaced previous `eval()` test logic.

### B. Controller: `scripts/execute_fee_collection_reset_atomic.php`
- Replaced inline helper declarations with `require_once __DIR__ . '/fee_reset_helpers.php'`.
- Updated test failure injection point (line 667) to pass active `$pdo` to the hardened `isTestFailureInjectionAllowed($pdo, $dbHost, $dbPort, $dbName)` guard.
- Preserved all existing 44 candidate payment and 44 target invoice invariants, advisory locking, dry-run guards, and transactional rollback mechanisms.

### C. Controller: `scripts/restore_fee_collection_reset_atomic.php`
- Replaced inline helper declarations with `require_once __DIR__ . '/fee_reset_helpers.php'`.
- Updated test failure injection point (line 565) to call the hardened fail-closed guard.
- Added post-commit independent row-by-row and column-by-column verification using `valuesMatchExactly()` across all 44 target invoices and all 44 restored candidate payments against archive snapshots (lines 603–638).

### D. Test Harness: `scratch/test_ph411_fix01_unit_retest.php`
- Tests the actual includable implementation from `scripts/fee_reset_helpers.php` (no `eval()` or re-implementation).
- Executes 22 audit matrix cases covering NULL vs NULL, NULL vs empty string, 0 vs "0", decimal strings, datetime strings, and scalar normalization.
- Static regex audit verifying zero weak `(string)$a !== (string)$b` comparisons remain in controllers.

### E. Security Harness: `scratch/test_ph411_guard_unit.php`
- Verifies 10 security attack/bypass scenarios against `isTestFailureInjectionAllowed()`.
- Validates active PDO connection checks (`SELECT DATABASE()`, `SELECT @@port`, `SELECT VERSION()`, `SELECT USER()`).
- Confirms immediate fail-closed rejection when any environment variable is missing or remote host/production DB is targeted.

### F. Integration Suite: `scratch/test_phase411_integration_suite_retest.php`
- **TEST-01:** Verified real Reset Controller rollback after actual transactional DML. Candidate count returns to 44 (IDs: 2..47 without 3, 6); invoices restored; 7,122 preserved payments unchanged.
- **TEST-02:** Verified real Restore Controller rollback after actual payment insertion. All inserted rows rolled back; candidate PKs 100% vacant; invoices remain Unpaid.
- **TEST-03 (Redesigned):** Commenced from clean sandbox with NO archive tables. Controller executed Stage A/B (creating archive tables via `CREATE TABLE ... LIKE` and populating them) before Stage C DML. Stage C DML rolled back upon injected failure, while archive tables persisted due to MySQL DDL implicit commit.
- **TEST-04:** Expanded beyond invoice checks to perform complete row-by-row and column-by-column NULL-safe comparisons of BOTH invoices AND payments against pre-restore state.
- **TEST-05 & TEST-06 (Commit Uncertainty):** Formally separated Level A (COMMIT invocation failure, verified Exit 101, zero retries, manual directive) from Level B (Commit acknowledgement uncertainty, reported as PARTIALLY TESTED).
- **TEST-07:** Full Reset -> Restore round-trip lifecycle verification with 100% bit-exact SHA-256 checksum match.

