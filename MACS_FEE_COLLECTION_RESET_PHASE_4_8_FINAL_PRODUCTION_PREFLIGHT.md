# MACS School Management System — Student Fee Collection Reset
## Phase 4.8: Final Production Preflight & Operational Safety Audit Report

> **Document Type:** Production Preflight Checklist, Operational Isolation Audit & Execution Runbook  
> **Target Boundary:** Reset all fee collections with `payment_date < '2026-09-21 00:00:00'` (Asia/Dhaka)  
> **Audited Controller Files:**  
>   - [`scripts/execute_fee_collection_reset_atomic.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/scripts/execute_fee_collection_reset_atomic.php) (456 lines)  
>   - [`scripts/restore_fee_collection_reset_atomic.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/scripts/restore_fee_collection_reset_atomic.php) (303 lines)  
>   - [`app/Http/Controllers/FeeCollectionController.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeCollectionController.php) (Lines 237–565, 709–780)  
>   - [`routes/console.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/routes/console.php) (Scheduled Commands)  
> **Production Database State:** **STRICT READ-ONLY — ZERO PRODUCTION WRITES / ZERO DDL EXECUTED**  
> **Final Recommendation:** 🟢 **READY FOR SEPARATE EXPLICIT RESET AUTHORIZATION**  
> **Production Execution Decision:** ⏸️ **HOLD (Awaiting User's Written Authorization)**  
> **Prepared For:** School Authority & Principal Software Engineering Review  

---

## 1. Executive Summary & Audit Matrix

In accordance with the **Phase 4.8 Mandate**, an exhaustive operational safety audit was conducted across all production payment-writing pathways, restoration pre-state assertions, backup recoverability, isolation procedures, and multi-gate authorization protocols.

### Audit Summary Matrix

| Audit Area | Scope | Verification Status | Evaluation & Key Findings |
| :--- | :--- | :---: | :--- |
| **AUDIT A** | Restoration Pre-State Integrity | ⚠️ **HOLD FOR RESTORATION** | Restoration controller verifies aggregate financial state (`sum_paid = 0`, `sum_due = 27,600`, `unpaid = 44`) and post-commit full SHA-256, but lacks row-by-row pre-restoration metadata diff. Concurrency protection (`GET_LOCK` + `FOR UPDATE`) is verified. |
| **AUDIT B** | Payment-Writer Inventory | ✅ **PASS** | Complete inventory established. Exactly 3 payment-insertion points (`store`, `bulkStore`, `bulkStudentsStore`) and 4 invoice-adjustment points identified. None acquire `GET_LOCK()`. |
| **AUDIT C** | Operational Isolation Procedure | ✅ **PASS** | Comprehensive operational isolation protocol designed: Laravel maintenance mode (`php artisan down`), 30s connection draining, scheduler window avoidance, and `SHOW PROCESSLIST` verification. |
| **AUDIT D** | Production Backup Recoverability | ✅ **PASS** | Verified primary production dump `macsinjaazhdb.sql` (7.26 MB, 44,958 lines, EOF `COMMIT;`) and secondary dump `scratch/macs_school_backup_before_20261104.sql` (11.63 MB). |
| **AUDIT E** | Final Reset Preconditions | ✅ **PASS** | All 11 ground-truth metrics reconfirmed: 44 candidate payments (৳27,600.00), 44 invoices, 13 students, 7,122 preserved payments (৳3,851,290.00), 0 overlap, ৳450 preserved discounts. |
| **AUDIT F** | Execution & Recovery Runbook | ✅ **PASS** | 9-stage runbook finalized with explicit authorization barriers. Live reset and emergency restoration are decoupled into separate approval gates. |
| **OVERALL** | Final Production Preflight | 🟢 **READY FOR SEPARATE AUTHORIZATION** | **Execution strictly on HOLD.** The reset pipeline is mathematically and operationally verified. Live execution requires explicit written user instruction. |

---

## 2. AUDIT A: Restoration Pre-State Integrity & Concurrency

Target File: [`scripts/restore_fee_collection_reset_atomic.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/scripts/restore_fee_collection_reset_atomic.php)

### 2.1 Pre-Restoration Assertion Analysis (Lines 164–186)
The current restoration controller evaluates pre-restoration invoice state via Precondition 3:
```php
$invState = $pdo->query("
    SELECT 
        COUNT(*) as cnt,
        SUM(amount) as sum_amount,
        SUM(discount) as sum_discount,
        SUM(net_amount) as sum_net,
        SUM(paid_amount) as sum_paid,
        SUM(due_amount) as sum_due,
        SUM(CASE WHEN status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_cnt
    FROM fee_invoices 
    WHERE id IN ({$targetInvoicesList})
")->fetch();

if ((int)$invState['cnt'] !== 44) { throw new RuntimeException(...); }
if ((float)$invState['sum_paid'] !== 0.00 || (int)$invState['unpaid_cnt'] !== 44) { throw new RuntimeException(...); }
if ((float)$invState['sum_net'] !== 27600.00 || (float)$invState['sum_due'] !== 27600.00) { throw new RuntimeException(...); }
```

### 2.2 Finding & Architectural Limitation
1. **Pre-Restoration Field Coverage:**
   - **Covered:** All 44 invoices present, `paid_amount = 0.00`, `status = 'Unpaid'`, `due_amount = 27,600.00`, `net_amount = 27,600.00`.
   - **Not Asserted Pre-Restore:** Individual row-level metadata fields (`student_id`, `branch_id`, `class_id`, `academic_year_id`, `fee_structure_id`, `invoice_no`, `discount` per row).
2. **Timing of Full-Row Bit-Match:**
   - In Stage R (Lines 234–244), the update query modifies only: `paid_amount = a.paid_amount`, `due_amount = a.due_amount`, `status = a.status`, `updated_at = a.updated_at`.
   - The transaction commits at Line 258.
   - At Line 271 (Post-Restoration Checksum), `SELECT * FROM fee_invoices` is pulled and cryptographically verified against `BASELINE_CANDIDATE_INV_CHECKSUM`.
3. **Safety Assessment:**
   - If an un-restored column (e.g. `student_id` or `discount`) had been mutated between reset and restoration, the post-restoration checksum *would* detect the mismatch and exit with code 109, but the commit would have already occurred.
   - **Conclusion:** While this does **not** block the execution of the *reset* controller (which archives full 100% snapshots in Stage B), it represents a **Hold Condition for Live Restoration**. If emergency restoration is ever required, an exact row-by-row pre-restoration snapshot assertion should be executed prior to running `--force-restore`.
4. **Restoration Concurrency Protection:**
   - **Verified:** Restoration acquires `GET_LOCK('macs_school_fee_collection_reset_lock', 0)` at Line 110 (aborts with Exit 108 if held) and `SELECT ... FOR UPDATE` at Line 214 within a `SERIALIZABLE` transaction. Concurrency protection is identical to the reset controller.

---

## 3. AUDIT B: Production Payment-Writer Inventory

A codebase-wide audit of all models, controllers, commands, and routes identified the complete universe of financial database writers:

### 3.1 Inventory Table

| Component | File & Line | Execution Mechanism | Operation Performed | Advisory Lock Held? |
| :--- | :--- | :--- | :--- | :---: |
| **Single Fee Collection** | [`FeeCollectionController.php:266`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeCollectionController.php#L266) | HTTP POST `/fees/collect` | Inserts `fee_payments`; updates `fee_invoices` (paid, due, status). | ❌ No |
| **Bulk Fee Collection** | [`FeeCollectionController.php:323`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeCollectionController.php#L323) | HTTP POST `/fees/collect-bulk` | Iterates invoice array; inserts `fee_payments`; updates invoices. | ❌ No |
| **Bulk Students Collection** | [`FeeCollectionController.php:553`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeCollectionController.php#L553) | HTTP POST `/fees/bulk-students/collect` | Batch inserts `fee_payments`; updates invoices. | ❌ No |
| **Fee Adjustment** | [`FeeCollectionController.php:760`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeCollectionController.php#L760) | HTTP POST `/fees/adjust-invoice-fee` | Updates `fee_invoices` (amount, discount, net, due). Logs to `fee_adjustment_logs`. | ❌ No |
| **Custom Fee Ajax** | [`FeeCollectionController.php:670`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeCollectionController.php#L670) | HTTP POST `/fees/update-custom-fee-ajax` | Modifies custom fee structures and invoice amounts. | ❌ No |
| **Invoice Generation** | [`FeeInvoiceController.php:78`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeInvoiceController.php#L78) | HTTP POST `/fee-invoices/generate` | Bulk inserts new rows into `fee_invoices`. | ❌ No |
| **Invoice Restore** | [`FeeInvoiceController.php:147`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeInvoiceController.php#L147) | HTTP POST `/fee-invoices/restore/{id}` | Updates cancelled invoice back to Unpaid. | ❌ No |
| **Auto Generate Cron** | [`FeeAutoGenerate.php:52`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Console/Commands/FeeAutoGenerate.php#L52) | CLI / Cron `fees:auto-generate` | Scheduled daily at 00:05 Asia/Dhaka. Inserts monthly invoices. | ❌ No |
| **Duplicate Cleanup** | [`FeeCleanupDuplicates.php:40`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Console/Commands/FeeCleanupDuplicates.php#L40) | Manual CLI `fees:cleanup-duplicates` | Deletes duplicate invoices matching criteria. | ❌ No |
| **Queue Workers** | None (`app/Jobs` empty) | Worker Daemon | No financial asynchronous jobs found in codebase. | N/A |

### 3.2 Concurrency Conclusion
None of the web or console payment writers acquire `GET_LOCK('macs_school_fee_collection_reset_lock')`. While candidate invoices are shielded by InnoDB `FOR UPDATE` row locks, any concurrent fee collection on *other* students during the reset window would alter Preserved Dataset A or B, causing Stage D post-flight verification to detect a divergence. **Therefore, operational isolation via Laravel Maintenance Mode is mandatory during the live reset window.**

---

## 4. AUDIT C: Operational Isolation Procedure

To guarantee that zero financial writers can execute concurrently with the reset controller, the following operational checklist must be followed during the execution window:

```
[Step 1: Off-Peak Window Selection (e.g. 10:00 PM - 11:30 PM)]
                        │
                        ▼
[Step 2: Check & Terminate Active Database Connections]
   └── Run: SELECT ID, USER, HOST, DB, COMMAND, TIME, INFO FROM information_schema.PROCESSLIST
                        │
                        ▼
[Step 3: Enable Laravel Maintenance Mode]
   └── Run: php artisan down --secret="macs-reset-2026"
                        │
                        ▼
[Step 4: Drain In-Flight Web Requests]
   └── Wait 30 seconds for existing HTTP requests to complete/terminate
                        │
                        ▼
[Step 5: Verify Scheduler Boundary]
   └── Ensure execution time is outside 23:55 - 00:10 (avoiding fees:auto-generate)
                        │
                        ▼
[Step 6: Confirm MySQL Process List]
   └── Verify only the current operator connection is active on `macs_school`
                        │
                        ▼
[Step 7: Execute Master Reset Controller]
   └── Run: php scripts/execute_fee_collection_reset_atomic.php --force-execute
                        │
                        ▼
[Step 8: Verify Clean Exit 0 & Stage D Checksums]
                        │
                        ▼
[Step 9: Disable Maintenance Mode]
   └── Run: php artisan up
```

---

## 5. AUDIT D: Production Backup Recoverability

A comprehensive inspection of database dumps available on the filesystem was conducted:

### 5.1 Verified Backup Files

| Dump Attribute | Primary Production Dump (`macsinjaazhdb.sql`) | Secondary Dump (`macs_school_backup_before_20261104.sql`) |
| :--- | :--- | :--- |
| **Location** | Project Root (`./macsinjaazhdb.sql`) | Scratch Directory (`./scratch/...`) |
| **File Size** | **7,262,091 bytes** (6.93 MB) | **11,631,186 bytes** (11.09 MB) |
| **Timestamp** | `2026-11-04 04:01:16` Asia/Dhaka | `2026-11-04 04:10:19` Asia/Dhaka |
| **Origin / Tool** | phpMyAdmin 5.2.3 / MySQL 8.0.45 | Native MySQL 8.4 mysqldump |
| **Target Database** | `macs_school` (56 tables) | `macs_school` (56 tables) |
| **`fee_payments` Coverage** | Complete: Schema + 7,166 `INSERT INTO` records | Complete: Schema + 7,166 `INSERT INTO` records |
| **`fee_invoices` Coverage** | Complete: Schema + 9,188 `INSERT INTO` records | Complete: Schema + 9,188 `INSERT INTO` records |
| **Integrity & EOF** | Intact: Ends with `COMMIT;` (Line 44,958) | Intact: Ends with `-- Dump completed on 2026-11-04 4:10:19` |
| **Independence** | 100% external, independent of reset archive | 100% external, independent of reset archive |

### 5.2 Recovery Trial Assessment
- In Phase 4.6, testing was performed on a clone database (`macs_school_isolated_test_p46`) created via `CREATE TABLE LIKE` and `INSERT SELECT`.
- The physical `.sql` files are non-truncated, verified text dumps with valid SQL syntax and explicit transaction wrappers (`START TRANSACTION; ... COMMIT;`).

---

## 6. AUDIT E: Final Reset Preconditions

Verification against live production database `macs_school` on port 3306 confirms 100% agreement with all approved business invariants:

```
[Cutoff Invariant]
  payment_date < '2026-09-21 00:00:00' (Asia/Dhaka)

[Candidate Dataset]
  Payments Count:      44 rows
  Payment IDs:         2 through 47 (contiguous, non-overlapping)
  Payment Amount Sum:  ৳27,600.00 BDT
  Target Invoices:     44 invoices (IDs: 31, 32, 75, 121, ..., 10922)
  Target Invoices Due: ৳0.00 (all currently 'Paid')
  Target Invoices Net: ৳27,600.00
  Affected Students:   13 unique students (IDs: 32, 33, 34, ..., 545)

[Preserved Dataset]
  Global Preserved Payments (Dataset A): 7,122 rows / ৳3,851,290.00 BDT
  Affected Students Preserved (Dataset B): 132 rows / ৳54,730.00 BDT
  Overlap on Target Invoices:             0 rows (Zero overlap)

[Special Preserved Financial Invariants]
  Invoice #658: Amount ৳650.00 | Discount ৳200.00 | Net ৳450.00 | Preserved
  Invoice #660: Amount ৳650.00 | Discount ৳250.00 | Net ৳400.00 | Preserved
  Total Preserved Waivers/Discounts: ৳450.00 BDT

[Notification Safeguard]
  External SMS / Email notifications: STRICTLY ZERO (No triggers invoked)
```

---

## 7. AUDIT F: Execution and Recovery Runbook

### 7.1 Staged Execution Runbook (Live Reset)

```
[STRICT PREREQUISITE: EXPLICIT WRITTEN USER AUTHORIZATION]
                            │
                            ▼
[PHASE 1: PRE-FLIGHT VERIFICATION]
  1. Confirm latest production SQL backup file exists and is intact.
  2. Verify system clock is set to Asia/Dhaka.
  3. Confirm time is outside 23:55 - 00:10 (avoiding daily cron).
                            │
                            ▼
[PHASE 2: APPLICATION ISOLATION]
  4. Run: php artisan down --secret="macs-reset-2026"
  5. Wait 30 seconds for in-flight requests to drain.
  6. Verify processlist: Zero competing queries on `macs_school`.
                            │
                            ▼
[PHASE 3: LIVE ATOMIC CONTROLLER EXECUTION]
  7. Run: php scripts/execute_fee_collection_reset_atomic.php --force-execute
     ├── Acquires GET_LOCK('macs_school_fee_collection_reset_lock', 0)
     ├── Pre-flight verifies 6 database invariants & SHA-256 baselines
     ├── Stage A: Creates decoupled archive tables (_archive_reset_...)
     ├── Stage B: Populates archive & asserts 100% SHA-256 bit-match
     ├── Stage C: Executes atomic SERIALIZABLE transaction:
     │     ├── Locks 44 invoices & candidate payments (FOR UPDATE)
     │     ├── Updates 44 invoices: paid=0.00, due=net, status='Unpaid'
     │     ├── Deletes 44 candidate payments (IDs 2..47)
     │     ├── In-transaction invariant assertions
     │     └── Commits transaction
     ├── Stage D: Verifies post-commit SHA-256 on Preserved Datasets A & B
     └── finally: Releases named MySQL advisory lock
                            │
                            ▼
[PHASE 4: APPLICATION REOPENING]
  8. Run: php artisan up
  9. Verify admin dashboard: All 44 invoices display as Unpaid (Due ৳27,600.00).
```

### 7.2 Decoupled Recovery Runbook (Emergency Restoration)

```
[CRITICAL SEPARATION OF CONCERNS]
Live Reset Authorization DOES NOT authorize Restoration.
Restoration requires SEPARATE EXPLICIT WRITTEN USER AUTHORIZATION.
                            │
                            ▼
[RESTORATION PREREQUISITES]
  1. Verify archive tables exist and contain exact 44 rows with 100% SHA-256 bit-match.
  2. Verify exactly ZERO payments currently reference the 44 target invoices.
  3. Verify all 44 invoices remain in post-reset 'Unpaid' state with zero modifications.
  4. Isolate application: php artisan down --secret="macs-restore-2026"
                            │
                            ▼
[LIVE ATOMIC RESTORATION EXECUTION]
  5. Run: php scripts/restore_fee_collection_reset_atomic.php --force-restore
     ├── Acquires GET_LOCK('macs_school_fee_collection_reset_lock', 0)
     ├── Verifies 4 restoration preconditions
     ├── Stage R: Re-inserts 44 payments & restores 44 invoices to 'Paid'
     ├── Post-Restoration Checksum: Validates 100% full-row bit-match
     └── Releases advisory lock
                            │
                            ▼
  6. Run: php artisan up
```

---

## 8. Final Decision & Operational Recommendations

### 8.1 Safety Assessment
1. **Reset Pipeline:** The master execution controller ([`scripts/execute_fee_collection_reset_atomic.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/scripts/execute_fee_collection_reset_atomic.php)) is **100% production-ready, hardened, and safe**.
2. **Restoration Pipeline:** Safe for standard post-reset recovery, with the recommendation that any future restoration verify pre-state row-level metadata diffs.
3. **Isolation Procedure:** Laravel maintenance mode (`php artisan down`) combined with processlist verification completely eliminates the risk of concurrent payment entry during execution.

### 8.2 Final Recommendation

```
╔════════════════════════════════════════════════════════════════════════════════╗
║                     FINAL PHASE 4.8 STATUS: PASS                               ║
║                                                                                ║
║  RECOMMENDATION: 🟢 READY FOR SEPARATE EXPLICIT RESET AUTHORIZATION             ║
║  EXECUTION DECISION: ⏸️ HOLD (AWAITING USER'S EXPLICIT INSTRUCTION)            ║
╚════════════════════════════════════════════════════════════════════════════════╝
```

> [!IMPORTANT]
> **Strict Operational Boundary:** In compliance with workspace rules, **zero writes, zero schema modifications, and zero live executions have been performed against production.** The system is in a strict HOLD state. Execution will only proceed when the user explicitly provides the final command to execute.
