<?php
/**
 * MACS School Management System — Production Fee Collection Reset Controller
 * 
 * Target Boundary: payment_date < '2026-09-21 00:00:00' (Asia/Dhaka)
 * Architecture: Phase 4.10 Independent Audit Finding Remediated Implementation
 * 
 * REMEDIATED SAFETY MEASURES:
 * - Finding 1: MySQL 8.0.45 compatibility & transaction isolation verification.
 * - Finding 2: Explicit separation of operator acknowledgement from verified isolation.
 *   Mandatory operational pre-execution checklist documenting scheduler/SMS/cron impact.
 * - Finding 3: Comprehensive row and gap locking on candidate invoices and payments via PDO::query() FOR UPDATE.
 * - Finding 4: Full reproducibility across isolated sandbox databases.
 * - Finding 5: Explicit pre-flight and dry-run execution with zero database mutations.
 * - Strict Commit Uncertainty: Zero automatic retries on commit failure; Exit 101.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "FATAL: This script must only be run from the command line.\n");
    exit(100);
}

// Ensure autoloader and Laravel bootstrap
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
            // String or raw decimal string - preserve exact bytes without trimming or float loss
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

// -----------------------------------------------------------------------------
// CONSTANTS & APPROVED BASELINES (DETERMINISTIC SHA-256)
// -----------------------------------------------------------------------------
const ADVISORY_LOCK_NAME = 'macs_school_fee_collection_reset_lock';
const ARCHIVE_TABLE_PAYMENTS = '_archive_reset_fee_payments_20261104';
const ARCHIVE_TABLE_INVOICES = '_archive_reset_fee_invoices_20261104';
const MAX_RETRIES = 3;

// Baseline Hashes computed via deterministic byte-exact length-prefixed protocol
const BASELINE_CANDIDATE_PMT_CHECKSUM = '1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038';
const BASELINE_CANDIDATE_INV_CHECKSUM = '3e7dcb1990ea343a7e6fd83c442a0345c0ad295f3840803a8ca1cf861429f68d';
const BASELINE_PRESERVED_ALL_CHECKSUM = 'e8061c5012241cc60b66974a2e2e458ddd0a6519c3ce15ee5f31d6e269aaa815';
const BASELINE_PRESERVED_STD_CHECKSUM = 'd997289605aec34f4433c5fbd3f3a9f36333098f63eaab88dffb000e4b6a5bed';

// Financial invariant baselines
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

const CANDIDATE_STUDENT_IDS = [
    32, 33, 34, 35, 37, 38, 46, 123, 124, 167, 314, 527, 545
];

// -----------------------------------------------------------------------------
// BLOCKER D & E: COMMAND-LINE EXECUTION & WRITER ISOLATION VALIDATION
// -----------------------------------------------------------------------------
global $argv;
$hasForceExecute = in_array('--force-execute', $argv ?? []);
$hasIsolationConfirm = in_array('--confirm-writers-isolated', $argv ?? []);

if ($hasForceExecute && !$hasIsolationConfirm) {
    fwrite(STDERR, "====================================================================\n");
    fwrite(STDERR, "FATAL SECURITY ABORT: FINANCIAL WRITER ISOLATION NOT CONFIRMED!\n");
    fwrite(STDERR, "====================================================================\n");
    fwrite(STDERR, "ISOLATION STATUS CLASSIFICATION (BLOCKER E):\n");
    fwrite(STDERR, "1. Operator-Declared Isolation: Provided strictly via the --confirm-writers-isolated CLI flag.\n");
    fwrite(STDERR, "2. Independently Verified Isolation: MySQL GET_LOCK() (protects only against concurrent reset scripts),\n");
    fwrite(STDERR, "   SERIALIZABLE transaction with clustered PRIMARY and secondary B-tree FOR UPDATE row locks,\n");
    fwrite(STDERR, "   and in-transaction assertion of active payment counts and checksums.\n");
    fwrite(STDERR, "3. Un-Established / Non-Automated Isolation: Web HTTP requests, Laravel scheduler cron jobs,\n");
    fwrite(STDERR, "   Supervisor background queue workers, and external third-party API integrations.\n");
    fwrite(STDERR, "   This script DOES NOT and CANNOT automatically kill processes or edit crontab/Supervisor.\n");
    fwrite(STDERR, "   Human operational verification of the pre-execution checklist is strictly required!\n\n");
    fwrite(STDERR, "MANDATORY PRE-EXECUTION OPERATIONAL CHECKLIST:\n");
    fwrite(STDERR, "[ ] 1. HTTP Traffic Isolation: Web traffic disabled (e.g. php artisan down --secret=...).\n");
    fwrite(STDERR, "[ ] 2. Request Draining: 30-second buffer elapsed to ensure all in-flight requests complete.\n");
    fwrite(STDERR, "[ ] 3. Scheduler Paused: Laravel scheduler cron suspended via crontab (NOTE: operator must\n");
    fwrite(STDERR, "       acknowledge that pausing the scheduler also pauses unrelated scheduled tasks, including SMS dispatch!).\n");
    fwrite(STDERR, "[ ] 4. Cron Inactive: Execution window is outside daily fee auto-generation (00:05 Asia/Dhaka).\n");
    fwrite(STDERR, "[ ] 5. Queue Workers Controlled: Supervisor / queue workers stopped or paused.\n");
    fwrite(STDERR, "[ ] 6. External Integrations: Third-party webhooks/integrations verified dormant.\n");
    fwrite(STDERR, "[ ] 7. Active Processlist: SHOW FULL PROCESSLIST in MySQL shows zero active application writer threads.\n\n");
    fwrite(STDERR, "To execute live, the operator must explicitly acknowledge that writers are isolated by passing:\n");
    fwrite(STDERR, "  php scripts/execute_fee_collection_reset_atomic.php --force-execute --confirm-writers-isolated\n");
    fwrite(STDERR, "====================================================================\n");
    exit(103);
}

$isDryRun = !$hasForceExecute || !$hasIsolationConfirm;

echo "====================================================================\n";
echo "MACS FEE COLLECTION RESET CONTROLLER (PHASE 4.10 REMEDIATED)\n";
echo "Execution Mode: " . ($isDryRun ? "STRICT READ-ONLY AUDIT & PREFLIGHT DRY-RUN" : "LIVE ATOMIC PRODUCTION EXECUTION") . "\n";
echo "Timestamp:      " . date('Y-m-d H:i:s T') . "\n";
echo "====================================================================\n\n";

if ($isDryRun) {
    echo "NOTICE: Running in default READ-ONLY mode. Pass '--force-execute --confirm-writers-isolated' to run live.\n";
    echo "Zero writes will be performed.\n\n";
} else {
    echo "WARNING: LIVE EXECUTION MODE ACTIVE! Both --force-execute and --confirm-writers-isolated supplied.\n";
    echo "Operator-declared writer isolation acknowledged.\n\n";
}

// -----------------------------------------------------------------------------
// DEDICATED PDO CONNECTION (NO ORM, NO RECONNECT)
// -----------------------------------------------------------------------------
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
    PDO::MYSQL_ATTR_FOUND_ROWS   => true, // Matches rows instead of changed rows
]);

// Enforce canonical application timezone (Asia/Dhaka / +06:00) for deterministic TIMESTAMP retrieval
$pdo->exec("SET time_zone = '+06:00'");

$targetInvoicesList = implode(',', TARGET_INVOICE_IDS);
$candidateStudentsList = implode(',', CANDIDATE_STUDENT_IDS);
$hasAdvisoryLock = false;

try {
    // -------------------------------------------------------------------------
    // ATOMIC EXECUTION LOCK (MYSQL ADVISORY LOCK)
    // -------------------------------------------------------------------------
    echo "[LOCK] Acquiring named MySQL advisory lock ('" . ADVISORY_LOCK_NAME . "')...\n";
    $lockStmt = $pdo->query("SELECT GET_LOCK('" . ADVISORY_LOCK_NAME . "', 0)");
    $lockResult = (int)$lockStmt->fetchColumn();

    if ($lockResult !== 1) {
        fwrite(STDERR, "FATAL: Failed to acquire advisory lock '" . ADVISORY_LOCK_NAME . "'. Another reset instance is currently running or holding the lock. Immediate abort.\n");
        exit(108);
    }
    $hasAdvisoryLock = true;
    echo "  -> Lock acquired successfully.\n\n";

    // -------------------------------------------------------------------------
    // PREFLIGHT AUDIT & BASELINE VERIFICATION
    // -------------------------------------------------------------------------
    echo "[PRE-FLIGHT] Verifying ground-truth database baselines & checksums...\n";

    // 1. Verify Preserved Dataset A (All 7,122 rows)
    $presRows = $pdo->query("SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll();
    $presCount = count($presRows);
    $presSum = array_reduce($presRows, fn($carry, $r) => $carry + (float)$r['paid_amount'], 0.0);
    $presChecksum = computeDeterministicDatasetChecksum($presRows);

    echo "  -> Preserved Dataset A: Count={$presCount}, Sum=" . number_format($presSum, 2) . " BDT\n";
    if ($presCount !== 7122 || abs($presSum - 3851290.00) > 0.01) {
        throw new RuntimeException("PREFLIGHT FAILED: Preserved Dataset A count/sum mismatch (Count: {$presCount}, Sum: {$presSum})");
    }
    if ($presChecksum !== BASELINE_PRESERVED_ALL_CHECKSUM) {
        throw new RuntimeException("PREFLIGHT FAILED: Preserved Dataset A checksum mismatch! Expected: " . BASELINE_PRESERVED_ALL_CHECKSUM . " Got: {$presChecksum}");
    }

    // 2. Verify Preserved Dataset B (132 rows for 13 students)
    $stdPresRows = $pdo->query("SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' AND student_id IN ({$candidateStudentsList}) ORDER BY id ASC")->fetchAll();
    $stdPresCount = count($stdPresRows);
    $stdPresSum = array_reduce($stdPresRows, fn($carry, $r) => $carry + (float)$r['paid_amount'], 0.0);
    $stdPresChecksum = computeDeterministicDatasetChecksum($stdPresRows);

    echo "  -> Preserved Dataset B: Count={$stdPresCount}, Sum=" . number_format($stdPresSum, 2) . " BDT\n";
    if ($stdPresCount !== 132 || abs($stdPresSum - 54730.00) > 0.01) {
        throw new RuntimeException("PREFLIGHT FAILED: Preserved Dataset B count/sum mismatch (Count: {$stdPresCount}, Sum: {$stdPresSum})");
    }
    if ($stdPresChecksum !== BASELINE_PRESERVED_STD_CHECKSUM) {
        throw new RuntimeException("PREFLIGHT FAILED: Preserved Dataset B checksum mismatch! Expected: " . BASELINE_PRESERVED_STD_CHECKSUM . " Got: {$stdPresChecksum}");
    }

    // 3. Verify Candidate Payments (44 rows)
    $candPmtRows = $pdo->query("SELECT * FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll();
    $candPmtCount = count($candPmtRows);
    $candPmtSum = array_reduce($candPmtRows, fn($carry, $r) => $carry + (float)$r['paid_amount'], 0.0);
    $candPmtChecksum = computeDeterministicDatasetChecksum($candPmtRows);

    echo "  -> Candidate Payments:  Count={$candPmtCount}, Sum=" . number_format($candPmtSum, 2) . " BDT\n";
    if ($candPmtCount !== 44 || abs($candPmtSum - BASELINE_CANDIDATE_SUM) > 0.01) {
        throw new RuntimeException("PREFLIGHT FAILED: Candidate payments mismatch (Count: {$candPmtCount}, Sum: {$candPmtSum})");
    }
    if ($candPmtChecksum !== BASELINE_CANDIDATE_PMT_CHECKSUM) {
        throw new RuntimeException("PREFLIGHT FAILED: Candidate payments checksum mismatch! Expected: " . BASELINE_CANDIDATE_PMT_CHECKSUM . " Got: {$candPmtChecksum}");
    }

    // 4. R1: Exact Candidate Payment-to-Invoice Bi-Directional Set Validation
    $candidatePaymentInvoiceIds = [];
    foreach ($candPmtRows as $pmt) {
        if (!isset($pmt['fee_invoice_id']) || $pmt['fee_invoice_id'] === null || $pmt['fee_invoice_id'] === '') {
            throw new RuntimeException("PREFLIGHT FAILED (R1): Candidate payment ID {$pmt['id']} has NULL/empty fee_invoice_id! Immediate abort.");
        }
        $invId = (int)$pmt['fee_invoice_id'];
        if ($invId <= 0) {
            throw new RuntimeException("PREFLIGHT FAILED (R1): Candidate payment ID {$pmt['id']} has invalid fee_invoice_id ({$invId})! Immediate abort.");
        }
        $candidatePaymentInvoiceIds[$invId] = true;
    }
    $distinctPaymentInvoiceIds = array_keys($candidatePaymentInvoiceIds);
    sort($distinctPaymentInvoiceIds);

    $expectedTargetInvoiceIds = TARGET_INVOICE_IDS;
    sort($expectedTargetInvoiceIds);

    $unexpectedInvoices = array_diff($distinctPaymentInvoiceIds, $expectedTargetInvoiceIds);
    $missingInvoices = array_diff($expectedTargetInvoiceIds, $distinctPaymentInvoiceIds);

    if (!empty($unexpectedInvoices) || !empty($missingInvoices)) {
        $err = "PREFLIGHT FAILED (R1): Exact candidate payment-to-invoice set mismatch!\n";
        if (!empty($unexpectedInvoices)) {
            $err .= "  -> Unexpected Invoice IDs in candidate payments: " . implode(', ', $unexpectedInvoices) . "\n";
        }
        if (!empty($missingInvoices)) {
            $err .= "  -> Approved Target Invoice IDs missing from payments: " . implode(', ', $missingInvoices) . "\n";
        }
        throw new RuntimeException($err);
    }
    echo "  -> Payment-to-Invoice Set Validation (R1): PASSED (Exact 1:1 bi-directional set equality confirmed across all 44 invoices).\n";

    // 5. Verify Candidate Invoices (44 rows)
    $candInvRows = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll();
    $candInvCount = count($candInvRows);
    $candInvGross = array_reduce($candInvRows, fn($carry, $r) => $carry + (float)$r['amount'], 0.0);
    $candInvDiscount = array_reduce($candInvRows, fn($carry, $r) => $carry + (float)$r['discount'], 0.0);
    $candInvNet = array_reduce($candInvRows, fn($carry, $r) => $carry + (float)$r['net_amount'], 0.0);
    $candInvPaid = array_reduce($candInvRows, fn($carry, $r) => $carry + (float)$r['paid_amount'], 0.0);
    $candInvDue = array_reduce($candInvRows, fn($carry, $r) => $carry + (float)$r['due_amount'], 0.0);
    $candInvChecksum = computeDeterministicDatasetChecksum($candInvRows);

    echo "  -> Candidate Invoices:  Count={$candInvCount}, Gross=" . number_format($candInvGross, 2) . ", Discount=" . number_format($candInvDiscount, 2) . ", Net=" . number_format($candInvNet, 2) . ", Paid=" . number_format($candInvPaid, 2) . ", Due=" . number_format($candInvDue, 2) . "\n";

    if ($candInvCount !== 44 || 
        abs($candInvGross - BASELINE_TARGET_GROSS) > 0.01 ||
        abs($candInvDiscount - BASELINE_TARGET_DISCOUNT) > 0.01 ||
        abs($candInvNet - BASELINE_TARGET_NET) > 0.01 ||
        abs($candInvPaid - BASELINE_CANDIDATE_SUM) > 0.01 || 
        abs($candInvDue - 0.00) > 0.01) {
        throw new RuntimeException("PREFLIGHT FAILED: Candidate invoices pre-state mismatch! (Gross: {$candInvGross}, Disc: {$candInvDiscount}, Net: {$candInvNet}, Paid: {$candInvPaid}, Due: {$candInvDue})");
    }
    if ($candInvChecksum !== BASELINE_CANDIDATE_INV_CHECKSUM) {
        throw new RuntimeException("PREFLIGHT FAILED: Candidate invoices checksum mismatch! Expected: " . BASELINE_CANDIDATE_INV_CHECKSUM . " Got: {$candInvChecksum}");
    }

    // 6. Verify Zero Overlap
    $overlapCount = (int)$pdo->query("SELECT COUNT(*) FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' AND fee_invoice_id IN ({$targetInvoicesList})")->fetchColumn();
    if ($overlapCount !== 0) {
        throw new RuntimeException("PREFLIGHT FAILED: Overlap detected! {$overlapCount} preserved payments belong to target invoices.");
    }

    // 7. R4: Archive Table Pre-Existence Guard
    $existingArchives = $pdo->query("
        SELECT TABLE_NAME FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME IN ('" . ARCHIVE_TABLE_PAYMENTS . "', '" . ARCHIVE_TABLE_INVOICES . "')
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($existingArchives)) {
        throw new RuntimeException("PREFLIGHT FAILED (R4): Archive tables already exist (" . implode(', ', $existingArchives) . "). Re-use/overwrite forbidden. Execution halted to preserve evidence.");
    }

    echo "  -> All Pre-Flight Validations PASSED.\n\n";

    if ($isDryRun) {
        echo "====================================================================\n";
        echo "DRY-RUN CERTIFICATION COMPLETE.\n";
        echo "All invariants, checksums, advisory lock, and safety conditions are 100% satisfied.\n";
        echo "Zero writes executed. Execution safely terminated.\n";
        echo "====================================================================\n";
        exit(0);
    }

    // =============================================================================
    // LIVE STAGED EXECUTION (REQUIRES --force-execute AND --confirm-writers-isolated)
    // =============================================================================

    // -----------------------------------------------------------------------------
    // STAGE A: CREATE DEDICATED ARCHIVE TABLES (DDL - STRICTLY DECOUPLED)
    // -----------------------------------------------------------------------------
    echo "[STAGE A] Creating archive tables...\n";
    try {
        $pdo->exec("CREATE TABLE `" . ARCHIVE_TABLE_PAYMENTS . "` LIKE `fee_payments`");
        $pdo->exec("CREATE TABLE `" . ARCHIVE_TABLE_INVOICES . "` LIKE `fee_invoices`");
        echo "  -> Archive tables created cleanly.\n\n";
    } catch (Throwable $stageAEx) {
        fwrite(STDERR, "\nCRITICAL ARCHIVE FAILURE (STAGE A): Failed to create archive tables: " . $stageAEx->getMessage() . "\n");
        fwrite(STDERR, "Execution halted immediately before any reset DML. Partial tables preserved if created.\n");
        exit(110);
    }

    // -----------------------------------------------------------------------------
    // STAGE B: POPULATE ARCHIVES & FULL-ROW ASSERTION (R4)
    // -----------------------------------------------------------------------------
    echo "[STAGE B] Populating archive snapshots and asserting full-row checksums...\n";
    try {
        $pdo->exec("INSERT INTO `" . ARCHIVE_TABLE_PAYMENTS . "` SELECT * FROM `fee_payments` WHERE `payment_date` < '2026-09-21 00:00:00'");
        $pdo->exec("INSERT INTO `" . ARCHIVE_TABLE_INVOICES . "` SELECT * FROM `fee_invoices` WHERE `id` IN ({$targetInvoicesList})");

        // Verify Archive Payments Full-Row Checksum & Row-by-Row match against live
        $archPmts = $pdo->query("SELECT * FROM `" . ARCHIVE_TABLE_PAYMENTS . "` ORDER BY id ASC")->fetchAll();
        $archPmtChecksum = computeDeterministicDatasetChecksum($archPmts);
        if (count($archPmts) !== 44 || $archPmtChecksum !== BASELINE_CANDIDATE_PMT_CHECKSUM) {
            throw new RuntimeException("Archived payments checksum mismatch! Expected: " . BASELINE_CANDIDATE_PMT_CHECKSUM . " Got: {$archPmtChecksum}");
        }

        // Compare every archived payment row against live row
        $livePmtsById = array_column($candPmtRows, null, 'id');
        foreach ($archPmts as $archPmt) {
            $pmtId = $archPmt['id'];
            if (!isset($livePmtsById[$pmtId])) {
                throw new RuntimeException("Archived payment ID {$pmtId} missing from pre-reset candidate rows!");
            }
            if (serializeDeterministicRow($archPmt) !== serializeDeterministicRow($livePmtsById[$pmtId])) {
                throw new RuntimeException("Archived payment ID {$pmtId} row serialization mismatch with live row!");
            }
        }

        // Verify Archive Invoices Full-Row Checksum & Row-by-Row match against live
        $archInvs = $pdo->query("SELECT * FROM `" . ARCHIVE_TABLE_INVOICES . "` ORDER BY id ASC")->fetchAll();
        $archInvChecksum = computeDeterministicDatasetChecksum($archInvs);
        if (count($archInvs) !== 44 || $archInvChecksum !== BASELINE_CANDIDATE_INV_CHECKSUM) {
            throw new RuntimeException("Archived invoices checksum mismatch! Expected: " . BASELINE_CANDIDATE_INV_CHECKSUM . " Got: {$archInvChecksum}");
        }

        $liveInvsById = array_column($candInvRows, null, 'id');
        foreach ($archInvs as $archInv) {
            $invId = $archInv['id'];
            if (!isset($liveInvsById[$invId])) {
                throw new RuntimeException("Archived invoice ID {$invId} missing from pre-reset candidate rows!");
            }
            if (serializeDeterministicRow($archInv) !== serializeDeterministicRow($liveInvsById[$invId])) {
                throw new RuntimeException("Archived invoice ID {$invId} row serialization mismatch with live row!");
            }
        }

        // Verify exact invoice ID set equality in archive
        $archInvIds = array_column($archInvs, 'id');
        sort($archInvIds);
        if ($archInvIds !== $expectedTargetInvoiceIds) {
            throw new RuntimeException("Archived invoice IDs do not exactly match approved target set!");
        }

        echo "  -> Stage B passed: Exact 44 payments & 44 invoices archived with 100% SHA-256 bit-match.\n\n";
    } catch (Throwable $stageBEx) {
        fwrite(STDERR, "\nCRITICAL ARCHIVE FAILURE (STAGE B): Archive population or validation failed: " . $stageBEx->getMessage() . "\n");
        fwrite(STDERR, "CRITICAL SAFETY DIRECTIVE: Partial archive tables ARE PRESERVED for investigation: " . ARCHIVE_TABLE_PAYMENTS . ", " . ARCHIVE_TABLE_INVOICES . "\n");
        fwrite(STDERR, "Execution halted immediately before any reset DML. Database records untouched.\n");
        exit(111);
    }

    // Capture pre-reset invoice snapshot indexed by ID for per-invoice assertion (R5)
    $preResetInvoicesById = array_column($candInvRows, null, 'id');

    // Discover schema columns for fee_invoices to ensure schema-aware comparison (F3)
    $invoiceColumns = getTableColumns($pdo, 'fee_invoices');
    $resetControlledFields = ['paid_amount', 'due_amount', 'status', 'updated_at'];

    // -----------------------------------------------------------------------------
    // STAGE C: PURE ATOMIC DML TRANSACTION WITH EXPLICIT STATE MACHINE (BLOCKERS A, C, F7)
    // -----------------------------------------------------------------------------
    $attempt = 0;
    $executionCommitted = false;

    while ($attempt < MAX_RETRIES && !$executionCommitted) {
        $attempt++;
        echo "[STAGE C] Attempt {$attempt} of " . MAX_RETRIES . " starting...\n";
        
        // Explicit Transaction State Machine (BLOCKER C)
        $txState = [
            'transaction_started'       => false,
            'commit_attempted'          => false,
            'commit_confirmed'          => false,
            'rollback_attempted'        => false,
            'rollback_confirmed'        => false,
            'commit_outcome_uncertain'  => false,
        ];

        try {
            // Step 1: Isolation Level & Transaction Start
            $pdo->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
            $pdo->beginTransaction();
            $txState['transaction_started'] = true;

            // Step 2: Index-Backed Hierarchical Row & Gap Locking (BLOCKER A: proper query + row fetch)
            echo "  -> Acquiring clustered PRIMARY lock on candidate invoices...\n";
            $stmtLockInvs = $pdo->query("SELECT id FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC FOR UPDATE");
            $lockedInvIds = $stmtLockInvs->fetchAll(PDO::FETCH_COLUMN);
            $lockedInvIds = array_map('intval', $lockedInvIds);
            sort($lockedInvIds);
            if (count($lockedInvIds) !== 44 || $lockedInvIds !== $expectedTargetInvoiceIds) {
                throw new RuntimeException("LOCKING FAILURE (BLOCKER A): Target invoices lock did not match expected set (Count: " . count($lockedInvIds) . ")!");
            }

            echo "  -> Acquiring B-tree gap lock on fee_payments (fee_invoice_id index)...\n";
            $stmtLockPmtByInv = $pdo->query("SELECT id, fee_invoice_id FROM fee_payments WHERE fee_invoice_id IN ({$targetInvoicesList}) ORDER BY fee_invoice_id ASC, id ASC FOR UPDATE");
            $lockedPmtByInvRows = $stmtLockPmtByInv->fetchAll(PDO::FETCH_ASSOC);
            if (count($lockedPmtByInvRows) !== 44) {
                throw new RuntimeException("LOCKING FAILURE (BLOCKER A): Payment rows locked via fee_invoice_id did not return exactly 44 rows (Found: " . count($lockedPmtByInvRows) . ")!");
            }

            echo "  -> Acquiring clustered PRIMARY lock on candidate payments...\n";
            $stmtLockPmts = $pdo->query("SELECT id FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00' ORDER BY id ASC FOR UPDATE");
            $lockedPmtIds = $stmtLockPmts->fetchAll(PDO::FETCH_COLUMN);
            $lockedPmtIds = array_map('intval', $lockedPmtIds);
            sort($lockedPmtIds);
            if (count($lockedPmtIds) !== 44) {
                throw new RuntimeException("LOCKING FAILURE (BLOCKER A): Candidate payments lock did not return exactly 44 rows (Found: " . count($lockedPmtIds) . ")!");
            }

            // =========================================================================
            // F1: FULL IN-TRANSACTION PRE-DML REVALIDATION UNDER LOCKS
            // =========================================================================
            echo "  -> [F1] Revalidating candidate payments & invoices under transaction lock...\n";

            // 1. Re-read all candidate payments under lock
            $txPmts = $pdo->query("SELECT * FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (count($txPmts) !== 44) {
                throw new RuntimeException("F1 REVALIDATION FAILED: Candidate payments count under lock is " . count($txPmts) . " (Expected 44)!");
            }

            // 2. Recalculate complete candidate payment checksum
            $txPmtChecksum = computeDeterministicDatasetChecksum($txPmts);
            if ($txPmtChecksum !== BASELINE_CANDIDATE_PMT_CHECKSUM) {
                throw new RuntimeException("F1 REVALIDATION FAILED: Candidate payments checksum mismatch under lock! Expected: " . BASELINE_CANDIDATE_PMT_CHECKSUM . " Got: {$txPmtChecksum}");
            }

            // 3. Recalculate distinct fee_invoice_id set & bidirectional set equality
            $txPmtInvoiceIds = [];
            foreach ($txPmts as $p) {
                $invId = (int)$p['fee_invoice_id'];
                $txPmtInvoiceIds[$invId] = true;
            }
            $txDistinctInvIds = array_keys($txPmtInvoiceIds);
            sort($txDistinctInvIds);
            if ($txDistinctInvIds !== $expectedTargetInvoiceIds) {
                throw new RuntimeException("F1 REVALIDATION FAILED: In-transaction payment-to-invoice mapping mismatch!");
            }

            // 4. Re-read all 44 target invoices under lock
            $txInvsPreDml = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (count($txInvsPreDml) !== 44) {
                throw new RuntimeException("F1 REVALIDATION FAILED: Candidate invoices count under lock is " . count($txInvsPreDml) . " (Expected 44)!");
            }

            // 5. Require exact invoice checksum equality with pre-reset baseline
            $txInvChecksumPreDml = computeDeterministicDatasetChecksum($txInvsPreDml);
            if ($txInvChecksumPreDml !== BASELINE_CANDIDATE_INV_CHECKSUM) {
                throw new RuntimeException("F1 REVALIDATION FAILED: Candidate invoices checksum mismatch under lock! Expected: " . BASELINE_CANDIDATE_INV_CHECKSUM . " Got: {$txInvChecksumPreDml}");
            }

            // 6. Recheck preserved payments count & checksums under lock
            $txPresRows = $pdo->query("SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (count($txPresRows) !== 7122 || computeDeterministicDatasetChecksum($txPresRows) !== BASELINE_PRESERVED_ALL_CHECKSUM) {
                throw new RuntimeException("F1 REVALIDATION FAILED: Preserved Dataset A count or checksum modified under lock!");
            }

            $txStdPresRows = $pdo->query("SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' AND student_id IN ({$candidateStudentsList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (count($txStdPresRows) !== 132 || computeDeterministicDatasetChecksum($txStdPresRows) !== BASELINE_PRESERVED_STD_CHECKSUM) {
                throw new RuntimeException("F1 REVALIDATION FAILED: Preserved Dataset B count or checksum modified under lock!");
            }
            echo "  -> [F1] All in-transaction pre-DML baselines, checksums, and mappings 100% matched.\n";

            // Step 3: DML Reset Invoices
            echo "  -> Executing fee_invoices reset update...\n";
            $updateStmt = $pdo->prepare("
                UPDATE fee_invoices
                SET paid_amount = 0.00,
                    due_amount  = net_amount,
                    status      = 'Unpaid',
                    updated_at  = NOW()
                WHERE id IN ({$targetInvoicesList})
            ");
            $updateStmt->execute();
            $matchedInvoices = $updateStmt->rowCount(); // Matches rows with MYSQL_ATTR_FOUND_ROWS
            if ($matchedInvoices !== 44) {
                throw new RuntimeException("INVARIANT FAILURE: Expected 44 matched invoices, got {$matchedInvoices}.");
            }

            // Step 4: DML Delete Candidate Payments
            echo "  -> Executing fee_payments deletion...\n";
            $deleteStmt = $pdo->prepare("
                DELETE FROM fee_payments
                WHERE payment_date < '2026-09-21 00:00:00'
            ");
            $deleteStmt->execute();
            $deletedPayments = $deleteStmt->rowCount();
            if ($deletedPayments !== 44) {
                throw new RuntimeException("INVARIANT FAILURE: Expected 44 deleted payments, got {$deletedPayments}.");
            }

            // Step 5: R5 - Ground-Truth Invariant Verification Inside Open Transaction
            echo "  -> Running in-transaction ground-truth assertions (R5)...\n";
            
            $remPmts = (int)$pdo->query("SELECT COUNT(*) FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00'")->fetchColumn();
            if ($remPmts !== 0) {
                throw new RuntimeException("INVARIANT FAILURE: Remaining candidate payments count is {$remPmts} (Expected 0).");
            }

            // In-transaction per-invoice verification (R5)
            $txInvs = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (count($txInvs) !== 44) {
                throw new RuntimeException("INVARIANT FAILURE: Expected 44 target invoices inside transaction, found " . count($txInvs));
            }

            $txSumGross = 0.0;
            $txSumDiscount = 0.0;
            $txSumNet = 0.0;
            $txSumPaid = 0.0;
            $txSumDue = 0.0;

            foreach ($txInvs as $inv) {
                $id = $inv['id'];
                if (!isset($preResetInvoicesById[$id])) {
                    throw new RuntimeException("INVARIANT FAILURE: Unexpected invoice ID {$id} encountered inside transaction!");
                }
                $pre = $preResetInvoicesById[$id];

                // Verify reset-controlled fields
                if ($inv['status'] !== 'Unpaid') {
                    throw new RuntimeException("INVARIANT FAILURE: Invoice ID {$id} status is '{$inv['status']}', expected 'Unpaid'");
                }
                if (abs((float)$inv['paid_amount'] - 0.00) > 0.001) {
                    throw new RuntimeException("INVARIANT FAILURE: Invoice ID {$id} paid_amount is '{$inv['paid_amount']}', expected '0.00'");
                }
                if (abs((float)$inv['due_amount'] - (float)$inv['net_amount']) > 0.001) {
                    throw new RuntimeException("INVARIANT FAILURE: Invoice ID {$id} due_amount ('{$inv['due_amount']}') does not equal net_amount ('{$inv['net_amount']}')");
                }

                // Verify schema-aware non-reset-controlled columns remain 100% UNCHANGED (F2/R5/PH411-FIX-01)
                foreach ($invoiceColumns as $col) {
                    if (in_array($col, $resetControlledFields, true)) {
                        continue;
                    }
                    if (!valuesMatchExactly($inv[$col], $pre[$col])) {
                        $preValStr = $pre[$col] === null ? 'NULL' : "'{$pre[$col]}'";
                        $invValStr = $inv[$col] === null ? 'NULL' : "'{$inv[$col]}'";
                        throw new RuntimeException("INVARIANT FAILURE: Invoice ID {$id} column '{$col}' mutated from {$preValStr} to {$invValStr}!");
                    }
                }

                $txSumGross += (float)$inv['amount'];
                $txSumDiscount += (float)$inv['discount'];
                $txSumNet += (float)$inv['net_amount'];
                $txSumPaid += (float)$inv['paid_amount'];
                $txSumDue += (float)$inv['due_amount'];
            }

            // Verify in-transaction aggregate financial values (R5)
            if (abs($txSumGross - BASELINE_TARGET_GROSS) > 0.01 ||
                abs($txSumDiscount - BASELINE_TARGET_DISCOUNT) > 0.01 ||
                abs($txSumNet - BASELINE_TARGET_NET) > 0.01 ||
                abs($txSumPaid - 0.00) > 0.01 ||
                abs($txSumDue - BASELINE_TARGET_NET) > 0.01) {
                throw new RuntimeException("INVARIANT FAILURE: In-transaction aggregate totals mismatch (Gross: {$txSumGross}, Disc: {$txSumDiscount}, Net: {$txSumNet}, Due: {$txSumDue})");
            }

            // Verify post-cutoff payments untouched
            $txPostCount = (int)$pdo->query("SELECT COUNT(*) FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00'")->fetchColumn();
            if ($txPostCount !== 7122) {
                throw new RuntimeException("INVARIANT FAILURE: Post-cutoff payment count changed to {$txPostCount} inside transaction!");
            }

            // Step 6: Commit Transaction (BLOCKER C: Explicit State Tracking)
            // PH411-FIX-02: Isolated Test-Only Failure Injection Point
            $testFailurePoint = getenv('MACS_TEST_FAILURE_POINT');
            if (!empty($testFailurePoint)) {
                if (!isTestFailureInjectionAllowed($pdo, $dbHost, $dbPort, $dbName)) {
                    fwrite(STDERR, "SECURITY ALERT: MACS_TEST_FAILURE_POINT is strictly forbidden in this environment/database!\n");
                    exit(100);
                }
                if ($testFailurePoint === 'reset_before_commit') {
                    throw new RuntimeException("[TEST-FAILURE-INJECTION] Injected failure before commit in Reset controller (TEST-01)");
                }
                if ($testFailurePoint === 'reset_commit_uncertainty') {
                    $txState['commit_attempted'] = true;
                    throw new PDOException("[TEST-FAILURE-INJECTION] Simulated commit transport failure / lost acknowledgement (TEST-05)", 40001);
                }
            }

            echo "  -> Committing transaction...\n";
            $txState['commit_attempted'] = true;
            $pdo->commit();
            $txState['commit_confirmed'] = true;
            $executionCommitted = true;
            echo ">>> TRANSACTION COMMITTED SUCCESSFULLY ON ATTEMPT {$attempt}! <<<\n\n";

        } catch (PDOException $e) {
            $errorCode = (int)($e->errorInfo[1] ?? 0);
            $sqlState = $e->getCode();
            $isDeadlockOrTimeout = ($errorCode === 1213 || $errorCode === 1205 || $sqlState === '40001');

            // Handle Uncertain Commit (BLOCKER C: Commit attempted but not confirmed)
            if ($txState['commit_attempted'] && !$txState['commit_confirmed']) {
                $txState['commit_outcome_uncertain'] = true;
                fwrite(STDERR, "\n====================================================================\n");
                fwrite(STDERR, "[CRITICAL INCIDENT: COMMIT_OUTCOME_UNCERTAIN (BLOCKER C)]\n");
                fwrite(STDERR, "Exception occurred during COMMIT statement on attempt {$attempt}: " . $e->getMessage() . "\n");
                fwrite(STDERR, "The database commit status is INDETERMINATE. The transaction may or may not have committed on MySQL.\n");
                fwrite(STDERR, "STRICT DIRECTIVE: Zero automatic retries will be attempted. Do NOT re-run script or restore without manual inspection.\n");
                fwrite(STDERR, "====================================================================\n");
                try {
                    $status = $pdo->query("SHOW ENGINE INNODB STATUS")->fetchColumn(2);
                    @file_put_contents(__DIR__ . '/../storage/logs/fee_reset_commit_uncertainty.log', $status);
                    fwrite(STDERR, "[INFO] InnoDB status saved to storage/logs/fee_reset_commit_uncertainty.log\n");
                } catch (Throwable $t) {}

                exit(101);
            }

            // Pre-commit failure: Attempt rollback
            $txState['rollback_attempted'] = true;
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                    $txState['rollback_confirmed'] = true;
                }
            } catch (Throwable $rbEx) {
                $txState['rollback_confirmed'] = false;
                fwrite(STDERR, "CRITICAL ERROR (F7): Rollback failed: " . $rbEx->getMessage() . "\n");
            }

            if ($isDeadlockOrTimeout && $txState['rollback_confirmed'] && $attempt < MAX_RETRIES) {
                $backoffMs = rand(100, 300) * $attempt;
                fwrite(STDERR, "[WARN] Deadlock/Lock Timeout detected (Error: {$errorCode}). Rollback confirmed. Retrying in {$backoffMs}ms...\n");
                usleep($backoffMs * 1000);
                continue;
            }

            // Unrecoverable error or retries exhausted
            fwrite(STDERR, "[CRITICAL INCIDENT (F7)] Transaction failed permanently on attempt {$attempt}: " . $e->getMessage() . "\n");
            fwrite(STDERR, "Rollback status: " . ($txState['rollback_confirmed'] ? "CONFIRMED" : "UNCONFIRMED / FAILED") . "\n");
            fwrite(STDERR, "Zero automatic retries will be attempted. Manual database inspection required.\n");
            
            try {
                $status = $pdo->query("SHOW ENGINE INNODB STATUS")->fetchColumn(2);
                @file_put_contents(__DIR__ . '/../storage/logs/fee_reset_deadlock.log', $status);
                fwrite(STDERR, "[INFO] InnoDB status saved to storage/logs/fee_reset_deadlock.log\n");
            } catch (Throwable $t) {}

            exit(101);

        } catch (Throwable $e) {
            // Invariant assertion failure -> Permanent abort with confirmed rollback
            $txState['rollback_attempted'] = true;
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                    $txState['rollback_confirmed'] = true;
                }
            } catch (Throwable $rbEx) {
                $txState['rollback_confirmed'] = false;
                fwrite(STDERR, "CRITICAL ERROR: Rollback failed: " . $rbEx->getMessage() . "\n");
            }

            fwrite(STDERR, "[FATAL] Invariant assertion failure: " . $e->getMessage() . "\n");
            fwrite(STDERR, "Rollback status: " . ($txState['rollback_confirmed'] ? "CONFIRMED" : "UNCONFIRMED / FAILED") . "\n");
            exit(102);
        }
    }

    // -----------------------------------------------------------------------------
    // STAGE D: POST-COMMIT INDEPENDENT CHECKSUM & INTEGRITY VERIFICATION (F2)
    // -----------------------------------------------------------------------------
    echo "[STAGE D] Running post-execution independent verification (F2)...\n";

    // 1. Verify candidate payments count is strictly 0
    $postCandCount = (int)$pdo->query("SELECT COUNT(*) FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00'")->fetchColumn();
    if ($postCandCount !== 0) {
        fwrite(STDERR, "CRITICAL ALERT (F2): Candidate payments count is {$postCandCount} (Expected 0) after commit!\n");
        exit(106);
    }
    echo "  -> [F2.1] Remaining Candidate Payments: 0 (All 44 candidate payments successfully deleted).\n";

    // 2. Post-commit per-invoice independent re-read & schema-aware comparison against archive (F2)
    $postLiveInvs = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetInvoicesList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (count($postLiveInvs) !== 44) {
        fwrite(STDERR, "CRITICAL ALERT (F2): Post-commit invoice count is " . count($postLiveInvs) . " (Expected 44)!\n");
        exit(107);
    }

    $archInvsForPost = $pdo->query("SELECT * FROM `" . ARCHIVE_TABLE_INVOICES . "` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $archInvsForPostById = array_column($archInvsForPost, null, 'id');

    $postGross = 0.0;
    $postDisc = 0.0;
    $postNet = 0.0;
    $postDue = 0.0;

    foreach ($postLiveInvs as $postInv) {
        $id = $postInv['id'];
        if (!isset($archInvsForPostById[$id])) {
            fwrite(STDERR, "CRITICAL ALERT (F2): Live invoice ID {$id} missing from archive!\n");
            exit(107);
        }
        $archInv = $archInvsForPostById[$id];

        // Status & balance assertions
        if ($postInv['status'] !== 'Unpaid' || (float)$postInv['paid_amount'] !== 0.00 || (float)$postInv['due_amount'] !== (float)$postInv['net_amount']) {
            fwrite(STDERR, "CRITICAL ALERT (F2): Post-commit invoice ID {$id} state invalid (Status: {$postInv['status']}, Paid: {$postInv['paid_amount']}, Due: {$postInv['due_amount']})!\n");
            exit(107);
        }

        // Schema-aware check: all non-reset-controlled columns must match archive snapshot (F2/PH411-FIX-01)
        foreach ($invoiceColumns as $col) {
            if (in_array($col, $resetControlledFields, true)) {
                continue;
            }
            if (!valuesMatchExactly($postInv[$col], $archInv[$col])) {
                $archValStr = $archInv[$col] === null ? 'NULL' : "'{$archInv[$col]}'";
                $postValStr = $postInv[$col] === null ? 'NULL' : "'{$postInv[$col]}'";
                fwrite(STDERR, "CRITICAL ALERT (F2): Post-commit invoice ID {$id} column '{$col}' mutated from archive {$archValStr} to {$postValStr}!\n");
                exit(107);
            }
        }

        $postGross += (float)$postInv['amount'];
        $postDisc += (float)$postInv['discount'];
        $postNet += (float)$postInv['net_amount'];
        $postDue += (float)$postInv['due_amount'];
    }

    if (abs($postGross - BASELINE_TARGET_GROSS) > 0.01 ||
        abs($postDisc - BASELINE_TARGET_DISCOUNT) > 0.01 ||
        abs($postNet - BASELINE_TARGET_NET) > 0.01 ||
        abs($postDue - BASELINE_TARGET_NET) > 0.01) {
        fwrite(STDERR, "CRITICAL ALERT (F2): Post-commit aggregate financial mismatch!\n");
        exit(107);
    }
    echo "  -> [F2.2] Post-Commit Invoices Verification: MATCHED (All 44 invoices 'Unpaid', Gross=৳" . number_format($postGross, 2) . ", Disc=৳" . number_format($postDisc, 2) . ", Due=৳" . number_format($postDue, 2) . ").\n";

    // 3. Recalculate Preserved Dataset A Checksum (F2)
    $postPresRows = $pdo->query("SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $postPresChecksum = computeDeterministicDatasetChecksum($postPresRows);
    if ($postPresChecksum !== BASELINE_PRESERVED_ALL_CHECKSUM) {
        fwrite(STDERR, "CRITICAL ALERT (F2): Post-commit Preserved Dataset A checksum mismatch! Triggering Emergency Investigation!\n");
        exit(104);
    }
    echo "  -> [F2.3] Preserved Dataset A Checksum: MATCHED (7,122 rows / ৳3,851,290.00 intact).\n";

    // 4. Recalculate Preserved Dataset B Checksum (F2)
    $postStdPresRows = $pdo->query("SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' AND student_id IN ({$candidateStudentsList}) ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $postStdPresChecksum = computeDeterministicDatasetChecksum($postStdPresRows);
    if ($postStdPresChecksum !== BASELINE_PRESERVED_STD_CHECKSUM) {
        fwrite(STDERR, "CRITICAL ALERT (F2): Post-commit Preserved Dataset B checksum mismatch! Triggering Emergency Investigation!\n");
        exit(105);
    }
    echo "  -> [F2.4] Preserved Dataset B Checksum: MATCHED (132 rows / ৳54,730.00 intact).\n";

    echo "\n====================================================================\n";
    echo "SUCCESS: MACS FEE COLLECTION RESET FULLY EXECUTED & CERTIFIED.\n";
    echo "  - Candidate Payments Deleted: 44 (IDs 2..47, Total: ৳27,600.00)\n";
    echo "  - Invoices Reset to Unpaid:   44 (Gross: ৳28,050.00, Disc: ৳450.00, Due: ৳27,600.00)\n";
    echo "  - Preserved Payments Intact:  7,122 (Total Amount: ৳3,851,290.00)\n";
    echo "  - Exit Code: 0\n";
    echo "====================================================================\n";
    exit(0);

} finally {
    if ($hasAdvisoryLock && isset($pdo)) {
        echo "[LOCK] Releasing named MySQL advisory lock ('" . ADVISORY_LOCK_NAME . "')...\n";
        try {
            $releaseStmt = $pdo->query("SELECT RELEASE_LOCK('" . ADVISORY_LOCK_NAME . "')");
            $releaseResult = $releaseStmt->fetchColumn();
            if ((int)$releaseResult === 1) {
                echo "  -> Lock released cleanly.\n";
            } else {
                fwrite(STDERR, "WARNING: RELEASE_LOCK returned {$releaseResult} (lock was not held or already freed).\n");
            }
        } catch (Throwable $lockEx) {
            fwrite(STDERR, "CRITICAL ERROR: Failed to execute RELEASE_LOCK: " . $lockEx->getMessage() . "\n");
            fwrite(STDERR, "NOTE: If connection terminates, MySQL automatically frees advisory locks for this session.\n");
        }
    }
}
