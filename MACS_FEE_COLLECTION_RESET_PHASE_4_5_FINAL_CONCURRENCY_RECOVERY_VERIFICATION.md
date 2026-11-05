# MACS School Management System — Student Fee Collection Reset
## Phase 4.5: Final Concurrency & Recovery Verification Audit

> **Document Type:** Production Readiness, Code Audit & Concurrency Verification  
> **Target Boundary:** Reset all fee collections with `payment_date < '2026-09-21 00:00:00'` (Asia/Dhaka)  
> **Audited Script:** `scripts/execute_fee_collection_reset_atomic.php` (419 lines)  
> **Execution Mode:** **STRICT READ-ONLY INDEPENDENT AUDIT — ZERO PRODUCTION WRITES**  
> **Current Recommendation:** 🛑 **HOLD — BLOCKING ISSUES IDENTIFIED PRIOR TO EXECUTION**  
> **Prepared For:** School Authority & Principal Software Engineering Review  

---

## 1. Executive Summary & Audit Matrix

In accordance with the **Phase 4.5 Mandate**, an exhaustive, independent read-only audit of the master execution controller ([`scripts/execute_fee_collection_reset_atomic.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/scripts/execute_fee_collection_reset_atomic.php)) and associated disaster recovery specifications was conducted.

### Audit Summary Matrix

| Audit Area | Scope | Verification Status | Evaluation |
| :--- | :--- | :---: | :--- |
| **AUDIT A** | Concurrent Execution Protection | ❌ **FAIL** | **BLOCKING ISSUE:** Missing MySQL `GET_LOCK()` atomic mutex. Race condition possible if two processes launch simultaneously. |
| **AUDIT B** | Checksum Reconciliation Proof | ✅ **PASS** | Proven mathematically on identical 7,122 and 132 underlying records. Character-level divergence fully documented. |
| **AUDIT C** | Restoration Safety & Preconditions | ❌ **FAIL** | **BLOCKING ISSUE:** Flawed `AND id > 47` filter in Stage R; missing pre-restoration invoice state assertion. |
| **AUDIT D** | Uncertain Commit Reconciliation | ✅ **PASS** | Refined protocol relies exclusively on durable persistent database facts, eliminating volatile `INNODB_TRX`. |
| **OVERALL** | Final Production Readiness | 🛑 **HOLD** | Execution must remain paused until Audit A and Audit C remediations are implemented and reviewed. |

---

## 2. AUDIT A — Concurrent Execution Protection

### 2.1 Inspection of `scripts/execute_fee_collection_reset_atomic.php`
A line-by-line inspection of the script's entry point and pre-flight logic (Lines 23–214) was conducted to verify protection against simultaneous process execution:

```php
// Lines 25-28: CLI Only check
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "FATAL: This script must only be run from the command line.\n");
    exit(100);
}

// Lines 98-99: Argument check
global $argv;
$isDryRun = !in_array('--force-execute', $argv ?? []);

// Lines 117-121: Single PDO connection instantiated
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [...]);

// Lines 129-204: Pre-flight queries executed outside of transaction
```

### 2.2 Findings & Vulnerability Analysis
1. **Missing Atomic Mutex:** There is **no MySQL advisory lock (`GET_LOCK()`)** or OS-level file lock (`flock`) implemented in the controller.
2. **Pre-Flight Race Condition:** If an operator or automated webhook mistakenly invokes two instances of `execute_fee_collection_reset_atomic.php --force-execute` concurrently:
   - **Process 1** checks `information_schema.TABLES` (Line 195) -> Archive tables do NOT exist.
   - **Process 2** checks `information_schema.TABLES` (Line 195) -> Archive tables do NOT exist.
   - **Process 1** passes Pre-Flight and proceeds to Stage A.
   - **Process 2** passes Pre-Flight and proceeds to Stage A.
   - **Process 1** executes `CREATE TABLE _archive_reset_fee_payments_...` (Line 224).
   - **Process 2** executes `CREATE TABLE _archive_reset_fee_payments_...` (Line 224) -> Fails with unhandled `PDOException: Table already exists` (MySQL Error 1050).
3. **Stage B Interleaving Risk:** While Process 2 fails on the DDL, relying on DDL failure as a concurrency barrier is unsafe because DDL execution in MySQL is non-transactional and issues implicit commits.
4. **Lock Release Lifecycle:** Because no execution lock exists, there is neither lock acquisition nor release handling.

### 2.3 Required Remediation (Blocking Issue)
Before any execution authorization, `scripts/execute_fee_collection_reset_atomic.php` must incorporate an atomic **MySQL Advisory Lock** at the very top of execution:

```php
// Required Implementation (Specification):
$lockName = 'macs_school_fee_collection_reset_lock';
$lockAcquired = (int)$pdo->query("SELECT GET_LOCK('{$lockName}', 0)")->fetchColumn();

if ($lockAcquired !== 1) {
    fwrite(STDERR, "FATAL: Another reset process is already running or holding the lock. Immediate abort.\n");
    exit(108);
}

// Guaranteed release on shutdown/exit:
register_shutdown_function(function() use ($pdo, $lockName) {
    try {
        $pdo->query("SELECT RELEASE_LOCK('{$lockName}')");
    } catch (Throwable $t) {}
});
```

---

## 3. AUDIT B — Checksum Reconciliation Proof

### 3.1 Verification of Source Datasets
An independent audit verified the underlying rows processed by both the Phase 4.2 Legacy Serializer and the Phase 4.3/4.4 Hardened Serializer:

#### Dataset A: All Active Preserved Collections (`payment_date >= '2026-09-21'`)
- **Query Filter:** `SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' ORDER BY id ASC`
- **Total Records:** Exactly **7,122 rows** (Zero rows omitted or added).
- **Primary Key Set:** Strictly contiguous IDs from `48` to `7169`.
- **Total Collection Sum:** Exactly **৳3,851,290.00 BDT**.

#### Dataset B: 13 Candidate Students' Preserved Collections
- **Query Filter:** `SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' AND student_id IN (32, 33, 34, 35, 37, 38, 46, 123, 124, 167, 314, 527, 545) ORDER BY id ASC`
- **Total Records:** Exactly **132 rows** (Zero rows omitted or added).
- **Primary Key Set:** Strictly identical IDs from `668` to `7023`.
- **Total Collection Sum:** Exactly **৳54,730.00 BDT**.

---

### 3.2 Mathematical Proof of Serializer Divergence
Both serializers were executed against the **exact same in-memory PDO result arrays** on the live database:

```
+-----------------------------------+-----------------------------------+-----------------------------------+
| Parameter                         | Dataset A: All Active Collections | Dataset B: 13 Affected Students   |
+-----------------------------------+-----------------------------------+-----------------------------------+
| Underlying Database Records       | 7,122 rows (100% Identical)       | 132 rows (100% Identical)         |
| Underlying Collection Sum         | ৳3,851,290.00 BDT (Identical)     | ৳54,730.00 BDT (Identical)        |
| Underlying ID Set                 | IDs: 48 through 7169 (Identical)  | IDs: 668 through 7023 (Identical) |
| Phase 4.2 Legacy Checksum         | d7d38690a923f3066ec6f6b4aad4da40  | ba9b526b9656453d62c6938851ab0381  |
|                                   | 9e25a98f5293b3a1bc4299406b2bd7ea  | 635aab89bc3ba14f4d964ac36e56dad4  |
| Phase 4.3/4.4 Hardened Checksum   | 25447b45890ad8c1a4b1c1f138102d4c  | 345b447450f31b36ccb2635207294177  |
|                                   | 2fea40d52ac7443c56205783e93e5ad8  | d7c5fe12cd96410cbea6d1a085dcdd56  |
| Source Code Location              | Lines 39-70                       | Lines 39-70                       |
+-----------------------------------+-----------------------------------+-----------------------------------+
```

#### Exact Character-Level Differentiation (Row #48):
- **Legacy Serializer:** Uses delimiter `|`, formats decimals via `number_format((float)$val, 2, '.', '')`, and uses string `__NULL__`.
  ```text
  collected_by=6|created_at=2026-09-21 07:34:47|fee_invoice_id=10923|id=48|note=__NULL__|paid_amount=250.00|payment_date=2026-09-21|payment_method=Cash|receipt_no=REC-20260921-1403-10923|student_id=39|transaction_id=__NULL__|updated_at=2026-09-21 07:34:47
  ```
- **Hardened Serializer:** Uses ASCII Unit Separator `\x1F`, preserves exact native database strings without float conversion, and wraps NULLs with binary sentinel `\x00__NULL__\x00`.
  ```text
  collected_by=6<US>created_at=2026-09-21 07:34:47<US>fee_invoice_id=10923<US>id=48<US>note=<NUL>__NULL__<NUL><US>paid_amount=250.00<US>payment_date=2026-09-21<US>payment_method=Cash<US>receipt_no=REC-20260921-1403-10923<US>student_id=39<US>transaction_id=<NUL>__NULL__<NUL><US>updated_at=2026-09-21 07:34:47
  ```

> **Audit B Verdict:** **PASS**. The source data is 100% identical and intact. The checksum difference is mathematically proven to stem solely from canonical serializer hardening.

---

## 4. AUDIT C — Restoration Safety

### 4.1 Inspection of Proposed Restoration Logic (Stage R)
The proposed Stage R SQL script was audited against potential data corruption risks:

```sql
-- Audited from Phase 4.4 Section 6.2:
SELECT COUNT(*) INTO @new_activity_count 
FROM `fee_payments` 
WHERE `fee_invoice_id` IN (31, 32, ... 10922)
  AND `id` > 47;
```

### 4.2 Critical Deficiencies Identified (Blocking Issue)
1. **Flawed Filter (`AND id > 47`):**  
   Restricting the post-reset activity check to `id > 47` makes an unverified assumption that new auto-increment IDs will strictly be `> 47`. In environments where primary keys are explicitly specified, backfilled, or restored from backups, an unexpected payment could exist with an ID `<= 47`.
   - **Required Standard:** Any payment row in `fee_payments` that references any of the 44 target invoices represents unexpected post-reset activity.
   - **Corrected Query:**
     ```sql
     SELECT COUNT(*) FROM fee_payments WHERE fee_invoice_id IN (31, 32, ... 10922);
     -- If count > 0, restoration MUST HALT IMMEDIATELY.
     ```
2. **Missing Pre-Restoration Invoice State Verification:**  
   The audited script did not verify that the 44 target invoices currently have `status = 'Unpaid'` and `paid_amount = 0.00` before running the update:
   ```sql
   UPDATE fee_invoices i
   JOIN _archive_reset_fee_invoices_20261104 a ON i.id = a.id
   SET i.paid_amount = a.paid_amount ...
   ```
   If an administrator or cashier manually modified an invoice's discount or due date after the reset, a blind restoration would overwrite those changes without warning.

> **Audit C Verdict:** **FAIL (BLOCKING ISSUE)**. The restoration script must be hardened to check for ANY payment referencing the target invoices and assert pre-restoration invoice states before executing.

---

## 5. AUDIT D — Uncertain Commit Reconciliation (Case F)

### 5.1 Inspection of Case F Recovery Design
In Phase 4.4 Section 5, `information_schema.INNODB_TRX` was listed as a dimension for determining commit outcome.

### 5.2 Elimination of `INNODB_TRX` Reliance
- **Technical Vulnerability:** `INNODB_TRX` reflects only *currently executing* transactions. Once a transaction either commits or rolls back, its row in `INNODB_TRX` is pruned immediately. It provides zero historical proof of past commit outcomes.
- **Hardened Specification:** Conclusive determination of transaction outcome following connection severance must rely solely on **durable, persistent database facts**:

```
+-----------------------------------+-----------------------------------+-----------------------------------+
| Durable Database Fact             | State Indicating Clean Rollback   | State Indicating Clean Commit     |
+-----------------------------------+-----------------------------------+-----------------------------------+
| 1. Candidate Payments Existence   | Exactly 44 rows exist (IDs 2..47) | Exactly 0 rows exist              |
| 2. Target Invoices Status         | All 44 invoices status = 'Paid'   | All 44 invoices status = 'Unpaid' |
| 3. Target Invoices Due Amount     | All 44 invoices due_amount = 0.00 | Sum of due_amount = ৳27,600.00    |
| 4. Preserved Dataset A Checksum   | Checksum matches baseline         | Checksum matches baseline         |
| 5. Preserved Dataset B Checksum   | Checksum matches baseline         | Checksum matches baseline         |
| 6. Archive Tables Completeness    | Both tables exist & populated     | Both tables exist & populated     |
+-----------------------------------+-----------------------------------+-----------------------------------+
```

### 5.3 Ambiguity Trap:
If a fresh connection observes:
- Some invoices 'Paid' and some 'Unpaid', OR
- Candidate payment count is between 1 and 43, OR
- Preserved dataset checksum fails:
**The state is definitively declared AMBIGUOUS.** Automatic action, automated restoration, or script re-execution is **STRICTLY PROHIBITED**. Manual investigation is required.

> **Audit D Verdict:** **PASS (WITH REFINEMENT)**. Durable state reconnaissance replaces volatile `INNODB_TRX` completely.

---

## 6. Audit Summary & Required Remediations

To graduate from **HOLD** to **READY FOR EXPLICIT AUTHORIZATION**, the following two code remediations must be approved:

| Blocking Issue | File Affected | Remediation Required |
| :--- | :--- | :--- |
| **Issue 1: Missing Concurrency Lock** | `scripts/execute_fee_collection_reset_atomic.php` (Line 122) | Add MySQL `GET_LOCK('macs_school_fee_collection_reset_lock', 0)` before Pre-Flight, with automatic release on shutdown. |
| **Issue 2: Flawed Restoration Filter** | Stage R SQL Script | Change `fee_invoice_id IN (...) AND id > 47` to `fee_invoice_id IN (...)` (count must be strictly 0). Add pre-state assertion on invoices (`status = 'Unpaid'`). |

---

## 7. Final Recommendation

### Final Decision: 🛑 **HOLD**

1. **Strict Read-Only Mode Maintained:** No database records have been modified, no tables created, and no DDL/DML executed.
2. **Current Readiness Assessment:** While the core atomic transaction and checksum protocols are mathematically verified, **the lack of an atomic MySQL execution mutex (`GET_LOCK`) and the flaws in the restoration preconditions represent legitimate blocking risks**.
3. **Next Step:** Maintain **HOLD** until the user reviews these findings and explicitly authorizes whether to apply the two remediations.

