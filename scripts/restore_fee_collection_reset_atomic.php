<?php
/**
 * MACS School Management System — Production Fee Collection Restoration Controller
 * 
 * Target Boundary: Reset all fee collections with payment_date < '2026-09-21 00:00:00'
 * Architecture: Phase 4.10 Independent Audit Finding Remediated Implementation
 * 
 * REMEDIATED SAFETY MEASURES:
 * - Finding 1: MySQL 8.0.45 compatibility & transaction isolation verification.
 * - Finding 2: Explicit separation of operator authorization from verified isolation.
 * - Finding 3: Explicit InnoDB row-level locking on archive tables (FOR UPDATE) inside transaction,
 *   with row count, exact primary-key sets, and checksum revalidation under lock.
 * - Finding 4: Full reproducibility across isolated sandbox databases.
 * - Finding 5: Explicit pre-flight and dry-run execution with zero database mutations.
 * - Strict Commit Uncertainty: Zero automatic retries on commit failure; Exit 101.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "FATAL: This script must only be run from the command line.\n");
    exit(100);
}

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// -----------------------------------------------------------------------------
// PH411-FIX-01 & PH411-FIX-02: SHARED HELPERS (STRICT NULL-SAFE & HARDENED GUARD)
// -----------------------------------------------------------------------------
require_once __DIR__ . '/fee_reset_helpers.php';

// -----------------------------------------------------------------------------
// R2: BYTE-EXACT DETERMINISTIC LENGTH-PREFIXED ROW SERIALIZER
// -----------------------------------------------------------------------------
function serializeDeterministicRow(array $row): string {
    ksort($row);
    $serialized = '';
    foreach ($row as $col => $val) {
        $colName = (string)$col;
        $serialized .= 'k:' . strlen($colName) . ':' . $colName . ';';
        if ($val === null) {
            $serialized .= 'v:N;';
        } elseif (is_int($val)) {
            $serialized .= 'v:i:' . $val . ';';
        } elseif (is_bool($val)) {
            $serialized .= 'v:b:' . ($val ? '1' : '0') . ';';
        } else {
            $strVal = (string)$val;
            $serialized .= 'v:s:' . strlen($strVal) . ':' . $strVal . ';';
        }
    }
    return $serialized;
}

function computeDeterministicDatasetChecksum(array $rows): string {
    usort($rows, function ($a, $b) {
        return (int)$a['id'] <=> (int)$b['id'];
    });
    $count = count($rows);
    $pks = array_column($rows, 'id');
    $pkList = implode(',', $pks);
    $header = "dataset:count={$count};pks={$pkList}\n";
    $body = '';
    foreach ($rows as $r) {
        $body .= 'row_id:' . $r['id'] . "\n" . serializeDeterministicRow($r) . "\n";
    }
    return hash('sha256', $header . $body);
}

function getTableColumns(PDO $pdo, string $tableName): array {
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? 
        ORDER BY ORDINAL_POSITION ASC
    ");
    $stmt->execute([$tableName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

const ADVISORY_LOCK_NAME = 'macs_school_fee_collection_reset_lock';
const ARCHIVE_TABLE_PAYMENTS = '_archive_reset_fee_payments_20261104';
const ARCHIVE_TABLE_INVOICES = '_archive_reset_fee_invoices_20261104';

// Approved Baselines (Deterministic SHA-256)
const BASELINE_CANDIDATE_PMT_CHECKSUM = '1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038';
const BASELINE_CANDIDATE_INV_CHECKSUM = '3e7dcb1990ea343a7e6fd83c442a0345c0ad295f3840803a8ca1cf861429f68d';

const BASELINE_TARGET_GROSS = 28050.00;
const BASELINE_TARGET_DISCOUNT = 450.00;
const BASELINE_TARGET_NET = 27600.00;
const BASELINE_CANDIDATE_SUM = 27600.00;

const TARGET_INVOICE_IDS = [
    31, 32, 75, 121, 126, 127, 135, 357, 571, 589, 
    658, 659, 660, 661, 663, 664, 765, 766, 995, 1227, 
    1431, 1663, 1738, 1739, 1968, 2275, 2276, 2505, 2812, 2813, 
    3349, 3350, 3886, 3887, 4423, 4424, 5015, 5016, 9837, 9838, 
    10379, 10380, 10921, 10922
];

const CANDIDATE_PAYMENT_IDS = [
    2, 4, 5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47
];

// -----------------------------------------------------------------------------
// BLOCKER D & E: COMMAND-LINE EXECUTION & OPERATOR AUTHORIZATION VALIDATION
// -----------------------------------------------------------------------------
global $argv;
$hasForceRestore = in_array('--force-restore', $argv ?? []);
$hasAuthConfirm = in_array('--confirm-restoration-authorized', $argv ?? []);

if ($hasForceRestore && !$hasAuthConfirm) {
    fwrite(STDERR, "====================================================================\n");
    fwrite(STDERR, "FATAL SECURITY ABORT: RESTORATION NOT FULLY AUTHORIZED!\n");
    fwrite(STDERR, "====================================================================\n");
    fwrite(STDERR, "Restoration modifies financial records and re-credits fee invoices.\n");
    fwrite(STDERR, "It requires explicit operator authorization acknowledgment.\n\n");
    fwrite(STDERR, "ISOLATION STATUS CLASSIFICATION (BLOCKER E):\n");
    fwrite(STDERR, "1. Operator-Declared Authorization: Acknowledged via --confirm-restoration-authorized.\n");
    fwrite(STDERR, "2. Independently Verified: MySQL advisory lock, in-transaction FOR UPDATE locks,\n");
    fwrite(STDERR, "   primary key vacancy checks, and active invoice payment assertions.\n");
    fwrite(STDERR, "3. Un-Established / Non-Automated: Application HTTP traffic, background workers, and schedulers.\n");
    fwrite(STDERR, "   Human operational verification of writer isolation is strictly required!\n\n");
    fwrite(STDERR, "To execute live, the operator must explicitly acknowledge authorization by passing:\n");
    fwrite(STDERR, "  php scripts/restore_fee_collection_reset_atomic.php --force-restore --confirm-restoration-authorized\n");
    fwrite(STDERR, "====================================================================\n");
    exit(103);
}

$isDryRun = !$hasForceRestore || !$hasAuthConfirm;

echo "====================================================================\n";
echo "MACS FEE COLLECTION RESTORATION CONTROLLER (PHASE 4.10 REMEDIATED)\n";
echo "Execution Mode: " . ($isDryRun ? "READ-ONLY PREFLIGHT RESTORATION DRY-RUN" : "LIVE ATOMIC RESTORATION") . "\n";
echo "Timestamp:      " . date('Y-m-d H:i:s T') . "\n";
echo "====================================================================\n\n";

if ($isDryRun) {
    echo "NOTICE: Running in default READ-ONLY mode. Pass '--force-restore --confirm-restoration-authorized' to restore live.\n";
    echo "Zero writes will be performed.\n\n";
} else {
    echo "WARNING: LIVE RESTORATION MODE ACTIVE! Explicit operator authorization confirmed.\n\n";
}

$dbConfig = config('database.connections.mysql');
$dbHost = getenv('DB_HOST') ?: ($dbConfig['host'] ?? '127.0.0.1');
$dbPort = getenv('DB_PORT') ?: ($dbConfig['port'] ?? 3306);
$dbName = getenv('DB_DATABASE') ?: ($dbConfig['database'] ?? 'macs_school');
$dbUser = getenv('DB_USERNAME') ?: ($dbConfig['username'] ?? 'root');
$dbPass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($dbConfig['password'] ?? '');

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_FOUND_ROWS   => true,
]);

$targetInvoicesList = implode(',', TARGET_INVOICE_IDS);
$expectedTargetInvoiceIds = TARGET_INVOICE_IDS;
sort($expectedTargetInvoiceIds);
$hasAdvisoryLock = false;

try {
    // -------------------------------------------------------------------------
    // ACQUIRE NAMED ADVISORY LOCK
    // -------------------------------------------------------------------------
    echo "[LOCK] Acquiring named MySQL advisory lock ('" . ADVISORY_LOCK_NAME . "')...\n";
    $lockStmt = $pdo->query("SELECT GET_LOCK('" . ADVISORY_LOCK_NAME . "', 0)");
    if ((int)$lockStmt->fetchColumn() !== 1) {
        fwrite(STDERR, "FATAL: Failed to acquire advisory lock. Another process is running. Aborting.\n");
        exit(108);
    }
    $hasAdvisoryLock = true;
    echo "  -> Lock acquired successfully.\n\n";

    // -------------------------------------------------------------------------
    // RESTORATION PRECONDITION VERIFICATIONS
    // -------------------------------------------------------------------------
    echo "[PRE-RESTORATION] Verifying restoration preconditions...\n";

    // 1. Verify Archive Tables Exist and Are Complete
    $archiveTables = $pdo->query("
        SELECT TABLE_NAME FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME IN ('" . ARCHIVE_TABLE_PAYMENTS . "', '" . ARCHIVE_TABLE_INVOICES . "')
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (count($archiveTables) !== 2) {
        throw new RuntimeException("RESTORATION ABORT: Archive tables do not exist in database! Automatic restore impossible.");
    }

    $archPmts = $pdo->query("SELECT * FROM `" . ARCHIVE_TABLE_PAYMENTS . "` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $archInvs = $pdo->query("SELECT * FROM `" . ARCHIVE_TABLE_INVOICES . "` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

    if (count($archPmts) !== 44 || count($archInvs) !== 44) {
        throw new RuntimeException("RESTORATION ABORT: Archive tables do not contain exactly 44 rows (Payments: " . count($archPmts) . ", Invoices: " . count($archInvs) . ")");
    }

    $archPmtChecksum = computeDeterministicDatasetChecksum($archPmts);
    $archInvChecksum = computeDeterministicDatasetChecksum($archInvs);

    if ($archPmtChecksum !== BASELINE_CANDIDATE_PMT_CHECKSUM) {
        throw new RuntimeException("RESTORATION ABORT: Archive payments checksum mismatch! Expected: " . BASELINE_CANDIDATE_PMT_CHECKSUM . " Got: {$archPmtChecksum}");
    }
    if ($archInvChecksum !== BASELINE_CANDIDATE_INV_CHECKSUM) {
        throw new RuntimeException("RESTORATION ABORT: Archive invoices checksum mismatch! Expected: " . BASELINE_CANDIDATE_INV_CHECKSUM . " Got: {$archInvChecksum}");
    }

    $archInvIds = array_column($archInvs, 'id');
    $archInvIds = array_map('intval', $archInvIds);
    sort($archInvIds);
    if ($archInvIds !== $expectedTargetInvoiceIds) {
        throw new RuntimeException("RESTORATION ABORT (BLOCKER G): Archive invoice IDs do not match approved target set!");
    }
    echo "  -> Precondition 1 PASSED: Archive tables verified with 100% SHA-256 bit-match.\n";

    // 2. Strict Zero Post-Reset Activity Guard on Target Invoices
    $activePaymentCount = (int)$pdo->query("
        SELECT COUNT(*) FROM fee_payments 
        WHERE fee_invoice_id IN ({$targetInvoicesList})
    ")->fetchColumn();

    if ($activePaymentCount !== 0) {
        throw new RuntimeException("RESTORATION ABORT: {$activePaymentCount} active payments currently exist on the 44 target invoices! New financial activity detected. Blind restore is unsafe to prevent double-crediting.");
    }
    echo "  -> Precondition 2 PASSED: Exactly 0 payments currently reference the 44 target invoices.\n";

    // 3. Primary Key Conflict Guard in fee_payments
    $candPmtIds = array_column($archPmts, 'id');
    $candPmtIdsList = implode(',', $candPmtIds);
    $conflictingPmts = (int)$pdo->query("SELECT COUNT(*) FROM fee_payments WHERE id IN ({$candPmtIdsList})")->fetchColumn();
    if ($conflictingPmts !== 0) {
        throw new RuntimeException("RESTORATION ABORT: {$conflictingPmts} primary key IDs already exist in fee_payments! Key collision detected.");
    }
    echo "  -> Precondition 3 PASSED: All 44 primary key IDs (2..47) are completely vacant.\n";

    // 4. F3: SCHEMA-AWARE FULL ROW-BY-ROW PRE-RESTORATION SNAPSHOT COMPARISON
    $liveColumns = getTableColumns($pdo, 'fee_invoices');
    $archColumns = getTableColumns($pdo, ARCHIVE_TABLE_INVOICES);

    $diffA = array_diff($liveColumns, $archColumns);
    $diffB = array_diff($archColumns, $liveColumns);
    if (!empty($diffA) || !empty($diffB)) {
        throw new RuntimeException("RESTORATION ABORT (F3): Schema mismatch detected between fee_invoices and archive table (Diff: " . implode(', ', array_merge($diffA, $diffB)) . ")!");
    }

    $livePaymentCols = getTableColumns($pdo, 'fee_payments');
    $archPaymentCols = getTableColumns($pdo, ARCHIVE_TABLE_PAYMENTS);
    $diffPmtA = array_diff($livePaymentCols, $archPaymentCols);
    $diffPmtB = array_diff($archPaymentCols, $livePaymentCols);
    if (!empty($diffPmtA) || !empty($diffPmtB)) {
        throw new RuntimeException("RESTORATION ABORT (F3): Schema mismatch detected between fee_payments and archive table (Diff: " . implode(', ', array_merge($diffPmtA, $diffPmtB)) . ")!");
    }

    $resetControlledFields = ['paid_amount', 'due_amount', 'status', 'updated_at'];

    $liveInvs = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($liveInvs) !== 44) {
        throw new RuntimeException("RESTORATION ABORT: Expected 44 target invoices, found " . count($liveInvs));
    }

    $archInvsById = array_column($archInvs, null, 'id');
    $sumGross = 0.0;
    $sumDiscount = 0.0;
    $sumNet = 0.0;
    $sumPaid = 0.0;
    $sumDue = 0.0;

    foreach ($liveInvs as $liveInv) {
        $id = $liveInv['id'];
        if (!isset($archInvsById[$id])) {
            throw new RuntimeException("RESTORATION ABORT: Live invoice ID {$id} has no archived reference!");
        }
        $archInv = $archInvsById[$id];

        // Assert expected post-reset state
        if ($liveInv['status'] !== 'Unpaid') {
            throw new RuntimeException("RESTORATION ABORT (F3): Invoice ID {$id} status is '{$liveInv['status']}', expected 'Unpaid'");
        }
        if (abs((float)$liveInv['paid_amount'] - 0.00) > 0.001) {
            throw new RuntimeException("RESTORATION ABORT (F3): Invoice ID {$id} paid_amount is '{$liveInv['paid_amount']}', expected '0.00'");
        }
        if (abs((float)$liveInv['due_amount'] - (float)$liveInv['net_amount']) > 0.001) {
            throw new RuntimeException("RESTORATION ABORT (F3): Invoice ID {$id} due_amount does not equal net_amount");
        }

        // Schema-Aware Check (F3/PH411-FIX-01): ALL columns outside the 4 reset-controlled fields must match archive exactly
        foreach ($liveColumns as $col) {
            if (in_array($col, $resetControlledFields, true)) {
                continue;
            }
            if (!valuesMatchExactly($liveInv[$col], $archInv[$col])) {
                $liveValStr = $liveInv[$col] === null ? 'NULL' : "'{$liveInv[$col]}'";
                $archValStr = $archInv[$col] === null ? 'NULL' : "'{$archInv[$col]}'";
                throw new RuntimeException("RESTORATION ABORT (F3): Invoice ID {$id} column '{$col}' mismatch with archive snapshot (Live: {$liveValStr}, Archive: {$archValStr})!");
            }
        }

        $sumGross += (float)$liveInv['amount'];
        $sumDiscount += (float)$liveInv['discount'];
        $sumNet += (float)$liveInv['net_amount'];
        $sumPaid += (float)$liveInv['paid_amount'];
        $sumDue += (float)$liveInv['due_amount'];
    }

    if (abs($sumGross - BASELINE_TARGET_GROSS) > 0.01 ||
        abs($sumDiscount - BASELINE_TARGET_DISCOUNT) > 0.01 ||
        abs($sumNet - BASELINE_TARGET_NET) > 0.01 ||
        abs($sumDue - BASELINE_TARGET_NET) > 0.01 ||
        abs($sumPaid - 0.00) > 0.01) {
        throw new RuntimeException("RESTORATION ABORT (F3): Aggregate invoice totals mismatch (Gross: {$sumGross}, Disc: {$sumDiscount}, Net: {$sumNet}, Due: {$sumDue})");
    }
    echo "  -> Precondition 4 PASSED: Schema-aware row-by-row snapshot comparison confirmed (All 44 invoices match exact post-reset state across all columns).\n\n";

    if ($isDryRun) {
        echo "====================================================================\n";
        echo "RESTORATION DRY-RUN COMPLETED SUCCESSFULLY.\n";
        echo "All restoration preconditions are 100% satisfied.\n";
        echo "Zero writes executed. Database left untouched.\n";
        echo "====================================================================\n";
        exit(0);
    }

    // -------------------------------------------------------------------------
    // ATOMIC RESTORATION EXECUTION (STAGE R) - BLOCKERS A, B, C, F7
    // -------------------------------------------------------------------------
    echo "[STAGE R] Executing atomic restoration transaction...\n";

    // State Machine Initialization (BLOCKER C)
    $txState = [
        'transaction_started'       => false,
        'commit_attempted'          => false,
        'commit_confirmed'          => false,
        'rollback_attempted'        => false,
        'rollback_confirmed'        => false,
        'commit_outcome_uncertain'  => false,
    ];

    $pdo->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
    $pdo->beginTransaction();
    $txState['transaction_started'] = true;

    // Lock candidate invoices (BLOCKER A: proper query + row fetch)
    echo "  -> Acquiring clustered PRIMARY lock on candidate invoices...\n";
    $stmtLockInvs = $pdo->query("SELECT id FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC FOR UPDATE");
    $lockedInvIds = $stmtLockInvs->fetchAll(PDO::FETCH_COLUMN);
    $lockedInvIds = array_map('intval', $lockedInvIds);
    sort($lockedInvIds);
    if (count($lockedInvIds) !== 44 || $lockedInvIds !== $expectedTargetInvoiceIds) {
        throw new RuntimeException("LOCKING FAILURE (BLOCKER A): Target invoices lock did not match expected set (Count: " . count($lockedInvIds) . ")!");
    }

    echo "  -> Acquiring clustered PRIMARY lock on candidate payment IDs (asserting vacancy)...\n";
    $stmtLockPks = $pdo->query("SELECT id FROM fee_payments WHERE id IN ({$candPmtIdsList}) ORDER BY id ASC FOR UPDATE");
    $lockedPks = $stmtLockPks->fetchAll(PDO::FETCH_COLUMN);
    if (count($lockedPks) !== 0) {
        throw new RuntimeException("F4 REVALIDATION FAILED (BLOCKER B): Primary key collision detected under lock! (" . count($lockedPks) . " IDs occupied)");
    }

    echo "  -> Acquiring B-tree gap lock on fee_payments for target invoices (asserting zero active payments)...\n";
    $stmtLockAct = $pdo->query("SELECT id FROM fee_payments WHERE fee_invoice_id IN ({$targetInvoicesList}) ORDER BY fee_invoice_id ASC, id ASC FOR UPDATE");
    $lockedAct = $stmtLockAct->fetchAll(PDO::FETCH_COLUMN);
    if (count($lockedAct) !== 0) {
        throw new RuntimeException("F4 REVALIDATION FAILED (BLOCKER B): Active payments appeared on target invoices inside transaction! (" . count($lockedAct) . " active payments)");
    }

    // =========================================================================
    // F4: REVALIDATION INSIDE SERIALIZABLE TRANSACTION UNDER LOCKS
    // =========================================================================
    echo "  -> [F4] Revalidating preconditions under transaction locks...\n";

    // 1. Explicitly Lock Archive Tables inside transaction (FINDING 3)
    echo "  -> Acquiring clustered PRIMARY lock on archive payments (asserting explicit row-level lock)...\n";
    $stmtLockArchPmts = $pdo->query("SELECT * FROM `" . ARCHIVE_TABLE_PAYMENTS . "` ORDER BY id ASC FOR UPDATE");
    $txArchPmts = $stmtLockArchPmts->fetchAll(PDO::FETCH_ASSOC);

    echo "  -> Acquiring clustered PRIMARY lock on archive invoices (asserting explicit row-level lock)...\n";
    $stmtLockArchInvs = $pdo->query("SELECT * FROM `" . ARCHIVE_TABLE_INVOICES . "` ORDER BY id ASC FOR UPDATE");
    $txArchInvs = $stmtLockArchInvs->fetchAll(PDO::FETCH_ASSOC);

    // Verify row counts under lock
    if (count($txArchPmts) !== 44 || count($txArchInvs) !== 44) {
        throw new RuntimeException("F4 REVALIDATION FAILED (FINDING 3): Archive tables do not contain exactly 44 rows under lock (Payments: " . count($txArchPmts) . ", Invoices: " . count($txArchInvs) . ")");
    }

    // Verify primary key sets under lock
    $txArchPmtIds = array_map('intval', array_column($txArchPmts, 'id'));
    sort($txArchPmtIds);
    $expectedPmtIds = CANDIDATE_PAYMENT_IDS;
    if ($txArchPmtIds !== $expectedPmtIds) {
        throw new RuntimeException("F4 REVALIDATION FAILED (FINDING 3): Archive payment primary keys under lock do not match expected candidate payment IDs!");
    }

    $txArchInvIds = array_map('intval', array_column($txArchInvs, 'id'));
    sort($txArchInvIds);
    if ($txArchInvIds !== $expectedTargetInvoiceIds) {
        throw new RuntimeException("F4 REVALIDATION FAILED (FINDING 3): Archive invoice primary keys under lock do not match expected target invoices!");
    }

    // Verify checksums under lock
    $archPmtChkUnderLock = computeDeterministicDatasetChecksum($txArchPmts);
    $archInvChkUnderLock = computeDeterministicDatasetChecksum($txArchInvs);
    if ($archPmtChkUnderLock !== BASELINE_CANDIDATE_PMT_CHECKSUM ||
        $archInvChkUnderLock !== BASELINE_CANDIDATE_INV_CHECKSUM) {
        throw new RuntimeException("F4 REVALIDATION FAILED (FINDING 3): Archive checksum mismatch under transaction lock!");
    }

    // 2. Recheck active payment count on target invoices
    $txActivePmts = (int)$pdo->query("SELECT COUNT(*) FROM fee_payments WHERE fee_invoice_id IN ({$targetInvoicesList})")->fetchColumn();
    if ($txActivePmts !== 0) {
        throw new RuntimeException("F4 REVALIDATION FAILED: Active payments appeared on target invoices inside transaction!");
    }

    // 3. Recheck original payment primary-key vacancy
    $txConflictPmts = (int)$pdo->query("SELECT COUNT(*) FROM fee_payments WHERE id IN ({$candPmtIdsList})")->fetchColumn();
    if ($txConflictPmts !== 0) {
        throw new RuntimeException("F4 REVALIDATION FAILED: Primary key collision detected under lock!");
    }

    // 4. Recheck target invoices post-reset state & schema-aware comparison under lock
    $txLiveInvs = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($txLiveInvs) !== 44) {
        throw new RuntimeException("F4 REVALIDATION FAILED: Expected 44 target invoices under lock!");
    }
    foreach ($txLiveInvs as $tLive) {
        $tId = $tLive['id'];
        $tArch = $archInvsById[$tId];
        if ($tLive['status'] !== 'Unpaid' || (float)$tLive['paid_amount'] !== 0.00 || (float)$tLive['due_amount'] !== (float)$tLive['net_amount']) {
            throw new RuntimeException("F4 REVALIDATION FAILED: Invoice ID {$tId} state mutated under lock!");
        }
        foreach ($liveColumns as $col) {
            if (in_array($col, $resetControlledFields, true)) {
                continue;
            }
            if (!valuesMatchExactly($tLive[$col], $tArch[$col])) {
                $tLiveValStr = $tLive[$col] === null ? 'NULL' : "'{$tLive[$col]}'";
                $tArchValStr = $tArch[$col] === null ? 'NULL' : "'{$tArch[$col]}'";
                throw new RuntimeException("F4 REVALIDATION FAILED: Invoice ID {$tId} column '{$col}' mutated under lock (Live: {$tLiveValStr}, Archive: {$tArchValStr})!");
            }
        }
    }
    echo "  -> [F4] In-transaction revalidation passed 100% under locks.\n";

    // -------------------------------------------------------------------------
    // DML EXECUTION
    // -------------------------------------------------------------------------
    // 1. Re-insert 44 original payments from archive (schema-aware column mapping)
    $escapedPmtCols = implode(', ', array_map(fn($col) => "`$col`", $livePaymentCols));
    $insertStmt = $pdo->prepare("
        INSERT INTO fee_payments ({$escapedPmtCols})
        SELECT {$escapedPmtCols}
        FROM `" . ARCHIVE_TABLE_PAYMENTS . "`
    ");
    $insertStmt->execute();
    $restoredPayments = $insertStmt->rowCount();
    if ($restoredPayments !== 44) {
        throw new RuntimeException("RESTORATION FAILURE: Expected 44 re-inserted payments, got {$restoredPayments}. Rolling back!");
    }

    // 2. Restore 44 invoices to exact pre-reset state
    $updateStmt = $pdo->prepare("
        UPDATE fee_invoices i
        JOIN `" . ARCHIVE_TABLE_INVOICES . "` a ON i.id = a.id
        SET 
            i.paid_amount = a.paid_amount,
            i.due_amount  = a.due_amount,
            i.status      = a.status,
            i.updated_at  = a.updated_at
        WHERE i.id IN ({$targetInvoicesList})
    ");
    $updateStmt->execute();
    $restoredInvoices = $updateStmt->rowCount();
    if ($restoredInvoices !== 44) {
        throw new RuntimeException("RESTORATION FAILURE: Expected 44 restored invoices, got {$restoredInvoices}. Rolling back!");
    }

    // =========================================================================
    // F5: PRE-COMMIT COMPREHENSIVE VERIFICATION BEFORE COMMIT
    // =========================================================================
    echo "  -> [F5] Running comprehensive pre-commit assertions before commit...\n";

    // 1. Verify exactly 44 payments exist and match original archive row-by-row
    $txInsertedPmts = $pdo->query("SELECT * FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($txInsertedPmts) !== 44) {
        throw new RuntimeException("F5 PRE-COMMIT FAILED: Expected 44 inserted payments, found " . count($txInsertedPmts));
    }
    $archPmtsById = array_column($archPmts, null, 'id');
    foreach ($txInsertedPmts as $insPmt) {
        $pId = $insPmt['id'];
        if (!isset($archPmtsById[$pId])) {
            throw new RuntimeException("F5 PRE-COMMIT FAILED: Restored payment ID {$pId} not in archive!");
        }
        if (serializeDeterministicRow($insPmt) !== serializeDeterministicRow($archPmtsById[$pId])) {
            throw new RuntimeException("F5 PRE-COMMIT FAILED: Restored payment ID {$pId} row serialization mismatch with archive!");
        }
    }

    // 2. Full payment checksum matches baseline
    $txPmtChecksumAfter = computeDeterministicDatasetChecksum($txInsertedPmts);
    if ($txPmtChecksumAfter !== BASELINE_CANDIDATE_PMT_CHECKSUM) {
        throw new RuntimeException("F5 PRE-COMMIT FAILED: Payment dataset checksum mismatch before commit! Expected: " . BASELINE_CANDIDATE_PMT_CHECKSUM . " Got: {$txPmtChecksumAfter}");
    }

    // 3. Verify all 44 invoices match original archived state column-by-column
    $txRestInvs = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($txRestInvs) !== 44) {
        throw new RuntimeException("F5 PRE-COMMIT FAILED: Expected 44 restored invoices, found " . count($txRestInvs));
    }

    $preCommitGross = 0.0;
    $preCommitDisc = 0.0;
    $preCommitNet = 0.0;
    $preCommitPaid = 0.0;
    $preCommitDue = 0.0;

    foreach ($txRestInvs as $rInv) {
        $rId = $rInv['id'];
        $aInv = $archInvsById[$rId];

        // Every column must match archive (F5/PH411-FIX-01)
        foreach ($liveColumns as $col) {
            if (!valuesMatchExactly($rInv[$col], $aInv[$col])) {
                $rValStr = $rInv[$col] === null ? 'NULL' : "'{$rInv[$col]}'";
                $aValStr = $aInv[$col] === null ? 'NULL' : "'{$aInv[$col]}'";
                throw new RuntimeException("F5 PRE-COMMIT FAILED: Invoice ID {$rId} column '{$col}' does not match archive original (Restored: {$rValStr}, Archive: {$aValStr})!");
            }
        }

        $preCommitGross += (float)$rInv['amount'];
        $preCommitDisc += (float)$rInv['discount'];
        $preCommitNet += (float)$rInv['net_amount'];
        $preCommitPaid += (float)$rInv['paid_amount'];
        $preCommitDue += (float)$rInv['due_amount'];
    }

    // 4. Full invoice checksum matches baseline
    $txInvChecksumAfter = computeDeterministicDatasetChecksum($txRestInvs);
    if ($txInvChecksumAfter !== BASELINE_CANDIDATE_INV_CHECKSUM) {
        throw new RuntimeException("F5 PRE-COMMIT FAILED: Invoice dataset checksum mismatch before commit! Expected: " . BASELINE_CANDIDATE_INV_CHECKSUM . " Got: {$txInvChecksumAfter}");
    }

    // 5. Financial aggregates match
    if (abs($preCommitGross - BASELINE_TARGET_GROSS) > 0.01 ||
        abs($preCommitDisc - BASELINE_TARGET_DISCOUNT) > 0.01 ||
        abs($preCommitNet - BASELINE_TARGET_NET) > 0.01 ||
        abs($preCommitPaid - BASELINE_CANDIDATE_SUM) > 0.01 ||
        abs($preCommitDue - 0.00) > 0.01) {
        throw new RuntimeException("F5 PRE-COMMIT FAILED: Aggregate financial values mismatch before commit!");
    }
    echo "  -> [F5] Pre-commit verification passed 100%. All candidate data & checksums certified.\n";

    // -------------------------------------------------------------------------
    // COMMIT TRANSACTION (BLOCKER C: Explicit State Tracking)
    // -------------------------------------------------------------------------
    // PH411-FIX-02: Isolated Test-Only Failure Injection Point
    $testFailurePoint = getenv('MACS_TEST_FAILURE_POINT');
    if (!empty($testFailurePoint)) {
        if (!isTestFailureInjectionAllowed($pdo, $dbHost, $dbPort, $dbName)) {
            fwrite(STDERR, "SECURITY ALERT: MACS_TEST_FAILURE_POINT is strictly forbidden in this environment/database!\n");
            exit(100);
        }
        if ($testFailurePoint === 'restore_before_commit') {
            throw new RuntimeException("[TEST-FAILURE-INJECTION] Injected failure before commit in Restore controller (TEST-02)");
        }
    }

    $restorationCommitted = false;
    $txState['commit_attempted'] = true;
    try {
        if (!empty($testFailurePoint) && $testFailurePoint === 'restore_commit_uncertainty') {
            throw new PDOException("[TEST-FAILURE-INJECTION] Simulated commit transport failure / lost acknowledgement (TEST-06)", 40001);
        }
        $pdo->commit();
        $txState['commit_confirmed'] = true;
        $restorationCommitted = true;
        echo "  -> Stage R committed successfully.\n\n";
    } catch (PDOException $commitEx) {
        $txState['commit_outcome_uncertain'] = true;
        fwrite(STDERR, "\n====================================================================\n");
        fwrite(STDERR, "[CRITICAL INCIDENT: COMMIT_OUTCOME_UNCERTAIN (BLOCKER C)]\n");
        fwrite(STDERR, "Restoration COMMIT failed or status uncertain: " . $commitEx->getMessage() . "\n");
        fwrite(STDERR, "Database outcome cannot be established. The transaction may or may not have committed on MySQL.\n");
        fwrite(STDERR, "Zero automatic retries will be attempted. Manual database inspection required immediately.\n");
        fwrite(STDERR, "====================================================================\n");
        exit(101);
    }

    // Post-commit independent confirmation
    echo "[POST-RESTORATION] Post-commit independent confirmation...\n";
    $finalPmts = $pdo->query("SELECT * FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll();
    $finalInvs = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll();
    if (computeDeterministicDatasetChecksum($finalPmts) !== BASELINE_CANDIDATE_PMT_CHECKSUM ||
        computeDeterministicDatasetChecksum($finalInvs) !== BASELINE_CANDIDATE_INV_CHECKSUM) {
        fwrite(STDERR, "CRITICAL ALERT (F7): Post-commit checksum mismatch detected! Immediate investigation required!\n");
        exit(109);
    }

    // PH411-FIX-01: Post-restore independent NULL-safe column-by-column snapshot comparison
    $finalInvsById = array_column($finalInvs, null, 'id');
    foreach ($archInvs as $aInv) {
        $id = $aInv['id'];
        if (!isset($finalInvsById[$id])) {
            fwrite(STDERR, "CRITICAL ALERT (F7): Post-commit restored invoice ID {$id} missing!\n");
            exit(109);
        }
        foreach ($liveColumns as $col) {
            if (!valuesMatchExactly($finalInvsById[$id][$col], $aInv[$col])) {
                $fVal = $finalInvsById[$id][$col] === null ? 'NULL' : "'{$finalInvsById[$id][$col]}'";
                $aVal = $aInv[$col] === null ? 'NULL' : "'{$aInv[$col]}'";
                fwrite(STDERR, "CRITICAL ALERT (F7): Post-commit invoice ID {$id} column '{$col}' mismatch (Final: {$fVal}, Archive: {$aVal})!\n");
                exit(109);
            }
        }
    }

    $finalPmtsById = array_column($finalPmts, null, 'id');
    foreach ($archPmts as $aPmt) {
        $id = $aPmt['id'];
        if (!isset($finalPmtsById[$id])) {
            fwrite(STDERR, "CRITICAL ALERT (F7): Post-commit restored payment ID {$id} missing!\n");
            exit(109);
        }
        foreach ($livePaymentCols as $col) {
            if (!valuesMatchExactly($finalPmtsById[$id][$col], $aPmt[$col])) {
                $fVal = $finalPmtsById[$id][$col] === null ? 'NULL' : "'{$finalPmtsById[$id][$col]}'";
                $aVal = $aPmt[$col] === null ? 'NULL' : "'{$aPmt[$col]}'";
                fwrite(STDERR, "CRITICAL ALERT (F7): Post-commit payment ID {$id} column '{$col}' mismatch (Final: {$fVal}, Archive: {$aVal})!\n");
                exit(109);
            }
        }
    }

    echo "====================================================================\n";
    echo "SUCCESS: RESTORATION FULLY EXECUTED & CERTIFIED.\n";
    echo "  - Re-inserted Payments: 44 (IDs 2..47, Total: ৳27,600.00)\n";
    echo "  - Restored Invoices:    44 (All 'Paid', Due: ৳0.00, Gross: ৳28,050.00)\n";
    echo "  - Full-Row Checksums:   100% Deterministic Bit-Match with Pre-Reset State\n";
    echo "  - Exit Code: 0\n";
    echo "====================================================================\n";
    exit(0);

} catch (Throwable $e) {
    if (isset($txState) && $txState['commit_attempted'] && !$txState['commit_confirmed']) {
        $txState['commit_outcome_uncertain'] = true;
        fwrite(STDERR, "[CRITICAL INCIDENT: COMMIT_OUTCOME_UNCERTAIN] Commit outcome indeterminate.\n");
        exit(101);
    }

    if (isset($txState)) {
        $txState['rollback_attempted'] = true;
    }
    if (isset($pdo) && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
            if (isset($txState)) {
                $txState['rollback_confirmed'] = true;
            }
            echo "\n>>> RESTORATION TRANSACTION ROLLED BACK! <<<\n";
        } catch (Throwable $rbEx) {
            if (isset($txState)) {
                $txState['rollback_confirmed'] = false;
            }
            fwrite(STDERR, "CRITICAL ERROR (F7): Rollback failed: " . $rbEx->getMessage() . "\n");
        }
    }
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    if (isset($txState) && $txState['transaction_started']) {
        fwrite(STDERR, "Rollback status: " . ($txState['rollback_confirmed'] ? "CONFIRMED" : "UNCONFIRMED / FAILED") . "\n");
    } else {
        fwrite(STDERR, "Transaction status: NOT STARTED (Read-only preflight abort. Zero database mutations attempted).\n");
    }
    exit(107);
} finally {
    if ($hasAdvisoryLock && isset($pdo)) {
        try {
            $pdo->query("SELECT RELEASE_LOCK('" . ADVISORY_LOCK_NAME . "')");
        } catch (Throwable $t) {}
    }
}
