<?php

declare(strict_types=1);

/**
 * =============================================================================
 * MACS SCHOOL - CLASS PLAY MARCH FEE ADJUSTMENT CONTROLLER (700 -> 650 BDT)
 * =============================================================================
 * 
 * Target Scope:
 *   - Class: Play (class_id = 5)
 *   - Fee Setup: Monthly Fee (fee_setup_id = 6)
 *   - Billing Month: March 2026 (invoice_no LIKE 'INV-202603-%')
 *   - Invoices with Amount = 700.00 BDT
 * 
 * Safety Constraints:
 *   1. Exclude invoices that already have 50.00 BDT discount & 650.00 BDT collection (IDs 163, 169).
 *   2. For Unpaid candidate invoices: Adjust amount = 650.00, net_amount = 650.00, due_amount = 650.00, paid_amount = 0.00.
 *   3. For Paid candidate invoices: Adjust amount = 650.00, net_amount = 650.00, paid_amount = 650.00, due_amount = 0.00.
 *      Synchronize matching fee_payments row to paid_amount = 650.00.
 *   4. Zero artificial discount added (discount remains untouched).
 *   5. Full pre-modification dedicated archive tables created.
 *   6. Strict advisory lock and ACID SERIALIZABLE transaction.
 *   7. Default mode: READ-ONLY DRY-RUN. Live mutation requires explicit CLI flags.
 * =============================================================================
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$args = $argv ?? [];
$hasForceExecute = in_array('--force-execute', $args, true);
$hasDryRun = in_array('--dry-run', $args, true);

$isDryRun = !$hasForceExecute;

echo "====================================================================\n";
echo "MACS SCHOOL: CLASS PLAY MARCH FEE ADJUSTMENT CONTROLLER\n";
echo "Execution Mode: " . ($isDryRun ? "STRICT READ-ONLY AUDIT & PREFLIGHT DRY-RUN" : "LIVE ATOMIC PRODUCTION EXECUTION") . "\n";
echo "Timestamp:      " . date('Y-m-d H:i:s T') . "\n";
echo "====================================================================\n\n";

if ($isDryRun) {
    echo "NOTICE: Running in default READ-ONLY mode. Pass '--force-execute' to run live.\n";
    echo "Zero database modifications will be executed.\n\n";
} else {
    echo "WARNING: LIVE EXECUTION MODE ACTIVE! --force-execute supplied.\n";
    echo "Database modifications will be committed.\n\n";
}

// -----------------------------------------------------------------------------
// DEDICATED PDO CONNECTION (NO RECONNECT, CANONICAL TIMEZONE)
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
    PDO::MYSQL_ATTR_FOUND_ROWS   => true,
]);

// Canonical timezone enforcement
$pdo->exec("SET time_zone = '+06:00'");

const ADVISORY_LOCK_NAME = 'macs_school_play_march_fee_adjust_lock';
const ARCHIVE_TABLE_INVOICES = '_archive_adjust_play_march_invoices_20261105';
const ARCHIVE_TABLE_PAYMENTS = '_archive_adjust_play_march_payments_20261105';
const EXCLUDED_INVOICE_IDS = [163, 169]; // Already handled with 50 discount & 650 collection

$hasAdvisoryLock = false;

try {
    // -------------------------------------------------------------------------
    // STEP 1: ACQUIRE MYSQL ADVISORY LOCK
    // -------------------------------------------------------------------------
    echo "[LOCK] Acquiring named MySQL advisory lock ('" . ADVISORY_LOCK_NAME . "')...\n";
    $lockStmt = $pdo->prepare("SELECT GET_LOCK(?, 10)");
    $lockStmt->execute([ADVISORY_LOCK_NAME]);
    if ($lockStmt->fetchColumn() != 1) {
        throw new RuntimeException("CRITICAL: Failed to acquire advisory lock. Another process may be running.");
    }
    $hasAdvisoryLock = true;
    echo "  -> Lock acquired successfully.\n\n";

    // -------------------------------------------------------------------------
    // STEP 2: PREFLIGHT AUDIT & INVARIANT VERIFICATION
    // -------------------------------------------------------------------------
    echo "[PRE-FLIGHT] Auditing Class Play March 2026 Monthly Invoices...\n";

    // Total March Monthly Fee invoices for Class Play
    $allMarchStmt = $pdo->query("
        SELECT fi.*, s.student_name, s.roll_number 
        FROM fee_invoices fi
        JOIN students s ON s.id = fi.student_id
        WHERE s.class_id = 5 
          AND fi.fee_setup_id = 6 
          AND fi.invoice_no LIKE 'INV-202603-%'
        ORDER BY fi.id ASC
    ");
    $allMarchInvoices = $allMarchStmt->fetchAll();
    $totalCount = count($allMarchInvoices);

    echo "  -> Total Class Play March Monthly Invoices: {$totalCount}\n";
    if ($totalCount !== 56) {
        throw new RuntimeException("PREFLIGHT FAILED: Expected exactly 56 March Monthly invoices for Class Play, found: {$totalCount}");
    }

    // Categorize
    $already650 = [];
    $excluded50Disc = [];
    $candidates = [];

    foreach ($allMarchInvoices as $inv) {
        $invId = (int)$inv['id'];
        $amt = (float)$inv['amount'];
        $disc = (float)$inv['discount'];
        $paid = (float)$inv['paid_amount'];

        if (in_array($invId, EXCLUDED_INVOICE_IDS, true)) {
            $excluded50Disc[] = $inv;
        } elseif (abs($amt - 650.00) < 0.01) {
            $already650[] = $inv;
        } elseif (abs($amt - 700.00) < 0.01) {
            $candidates[] = $inv;
        } else {
            throw new RuntimeException("PREFLIGHT FAILED: Unexpected invoice amount ({$amt}) for invoice ID {$invId}!");
        }
    }

    echo "  -> Invoices already at 650.00 BDT: " . count($already650) . " (Untouched)\n";
    echo "  -> Invoices with 50.00 BDT discount & 650.00 BDT collection: " . count($excluded50Disc) . " (Explicitly Preserved: IDs " . implode(',', EXCLUDED_INVOICE_IDS) . ")\n";
    echo "  -> Candidate Invoices to Adjust (700.00 -> 650.00 BDT): " . count($candidates) . "\n\n";

    if (count($candidates) !== 39) {
        throw new RuntimeException("PREFLIGHT FAILED: Expected exactly 39 candidate invoices to adjust, found: " . count($candidates));
    }

    $candidateIds = array_column($candidates, 'id');
    $candidateIdsList = implode(',', $candidateIds);

    // Analyze candidates breakdown (Unpaid vs Paid)
    $unpaidCandidates = [];
    $paidCandidates = [];

    foreach ($candidates as $c) {
        if ($c['status'] === 'Unpaid' || (float)$c['paid_amount'] == 0.0) {
            $unpaidCandidates[] = $c;
        } else {
            $paidCandidates[] = $c;
        }
    }

    echo "  -> Candidates Breakdown:\n";
    echo "     * Unpaid Candidates: " . count($unpaidCandidates) . " invoices\n";
    echo "     * Paid Candidates:   " . count($paidCandidates) . " invoices\n\n";

    // Detailed Candidate Summary
    echo "  -> Candidate Invoices List:\n";
    foreach ($candidates as $c) {
        $pmt = $pdo->query("SELECT id, paid_amount, payment_date FROM fee_payments WHERE fee_invoice_id = {$c['id']}")->fetch();
        $pmtStr = $pmt ? "Pmt ID: {$pmt['id']}, Paid: ৳{$pmt['paid_amount']}, Date: {$pmt['payment_date']}" : "No Payment Record";
        echo sprintf(
            "     * ID: %-4d | Inv: %-15s | Roll: %-3s | Student: %-20s | Status: %-7s | Gross: %-6s | Net: %-6s | Paid: %-6s | %s\n",
            $c['id'],
            $c['invoice_no'],
            $c['roll_number'] ?? 'N/A',
            mb_substr($c['student_name'], 0, 20),
            $c['status'],
            $c['amount'],
            $c['net_amount'],
            $c['paid_amount'],
            $pmtStr
        );
    }
    echo "\n";

    if ($isDryRun) {
        if ($hasAdvisoryLock) {
            echo "[LOCK] Releasing MySQL advisory lock...\n";
            $unlockStmt = $pdo->prepare("SELECT RELEASE_LOCK(?)");
            $unlockStmt->execute([ADVISORY_LOCK_NAME]);
            $hasAdvisoryLock = false;
            echo "  -> Lock released cleanly.\n\n";
        }
        echo "====================================================================\n";
        echo "DRY-RUN PREFLIGHT COMPLETE.\n";
        echo "All 39 candidate invoices validated. Excluded invoices 163 & 169 protected.\n";
        echo "Zero database modifications executed. System is completely safe.\n";
        echo "To apply changes live, execute:\n";
        echo "  php scripts/adjust_class_play_march_fee.php --force-execute\n";
        echo "====================================================================\n";
        exit(0);
    }

    // =========================================================================
    // LIVE EXECUTION (STAGE A: ARCHIVE CREATION & POPULATION)
    // =========================================================================
    echo "[STAGE A] Creating and populating dedicated archive tables...\n";
    
    // Create archive tables if not exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `" . ARCHIVE_TABLE_INVOICES . "` LIKE `fee_invoices`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `" . ARCHIVE_TABLE_PAYMENTS . "` LIKE `fee_payments`");

    // Clear prior snapshot for these candidate IDs if any
    $pdo->exec("DELETE FROM `" . ARCHIVE_TABLE_INVOICES . "` WHERE `id` IN ({$candidateIdsList})");
    $pdo->exec("DELETE FROM `" . ARCHIVE_TABLE_PAYMENTS . "` WHERE `fee_invoice_id` IN ({$candidateIdsList})");

    // Populate archives
    $pdo->exec("INSERT INTO `" . ARCHIVE_TABLE_INVOICES . "` SELECT * FROM `fee_invoices` WHERE `id` IN ({$candidateIdsList})");
    $pdo->exec("INSERT INTO `" . ARCHIVE_TABLE_PAYMENTS . "` SELECT * FROM `fee_payments` WHERE `fee_invoice_id` IN ({$candidateIdsList})");

    $archInvCount = (int)$pdo->query("SELECT COUNT(*) FROM `" . ARCHIVE_TABLE_INVOICES . "` WHERE `id` IN ({$candidateIdsList})")->fetchColumn();
    echo "  -> Archived candidate invoices count: {$archInvCount} of 39\n";
    if ($archInvCount !== 39) {
        throw new RuntimeException("ARCHIVE FAILED: Expected 39 archived invoices, found {$archInvCount}");
    }
    echo "  -> Stage A Archive successfully created.\n\n";

    // =========================================================================
    // LIVE EXECUTION (STAGE B: ATOMIC TRANSACTION DML)
    // =========================================================================
    echo "[STAGE B] Executing atomic database update in transaction...\n";

    $pdo->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
    $pdo->beginTransaction();

    try {
        // Lock rows
        $pdo->query("SELECT * FROM `fee_invoices` WHERE `id` IN ({$candidateIdsList}) FOR UPDATE")->fetchAll();
        $pdo->query("SELECT * FROM `fee_payments` WHERE `fee_invoice_id` IN ({$candidateIdsList}) FOR UPDATE")->fetchAll();

        $updatedInvoicesCount = 0;
        $updatedPaymentsCount = 0;

        foreach ($candidates as $cand) {
            $invId = (int)$cand['id'];
            $disc = (float)$cand['discount'];
            $targetGross = 650.00;
            $targetNet = $targetGross - $disc;

            $isUnpaid = ($cand['status'] === 'Unpaid' || (float)$cand['paid_amount'] == 0.0);

            if ($isUnpaid) {
                // Unpaid invoice update
                $updStmt = $pdo->prepare("
                    UPDATE `fee_invoices`
                    SET `amount` = :amt,
                        `net_amount` = :net,
                        `due_amount` = :due,
                        `paid_amount` = 0.00,
                        `status` = 'Unpaid',
                        `updated_at` = NOW()
                    WHERE `id` = :id
                ");
                $updStmt->execute([
                    ':amt' => number_format($targetGross, 2, '.', ''),
                    ':net' => number_format($targetNet, 2, '.', ''),
                    ':due' => number_format($targetNet, 2, '.', ''),
                    ':id'  => $invId,
                ]);
                $updatedInvoicesCount++;
            } else {
                // Paid invoice update
                $updStmt = $pdo->prepare("
                    UPDATE `fee_invoices`
                    SET `amount` = :amt,
                        `net_amount` = :net,
                        `paid_amount` = :paid,
                        `due_amount` = 0.00,
                        `status` = 'Paid',
                        `updated_at` = NOW()
                    WHERE `id` = :id
                ");
                $updStmt->execute([
                    ':amt'  => number_format($targetGross, 2, '.', ''),
                    ':net'  => number_format($targetNet, 2, '.', ''),
                    ':paid' => number_format($targetNet, 2, '.', ''),
                    ':id'   => $invId,
                ]);
                $updatedInvoicesCount++;

                // Synchronize fee_payments row
                $updPmtStmt = $pdo->prepare("
                    UPDATE `fee_payments`
                    SET `paid_amount` = :paid,
                        `updated_at` = NOW()
                    WHERE `fee_invoice_id` = :id
                ");
                $updPmtStmt->execute([
                    ':paid' => number_format($targetNet, 2, '.', ''),
                    ':id'   => $invId,
                ]);
                $updatedPaymentsCount += $updPmtStmt->rowCount();
            }
        }

        echo "  -> Invoices updated: {$updatedInvoicesCount} (Target: 39)\n";
        echo "  -> Payment records synchronized: {$updatedPaymentsCount}\n";

        // In-transaction verification assertions
        $check700 = (int)$pdo->query("SELECT COUNT(*) FROM `fee_invoices` WHERE `id` IN ({$candidateIdsList}) AND `amount` = '700.00'")->fetchColumn();
        if ($check700 !== 0) {
            throw new RuntimeException("POST-DML FAILED: Candidate invoices still have amount = 700.00 ({$check700} remaining)!");
        }

        $checkNegDue = (int)$pdo->query("SELECT COUNT(*) FROM `fee_invoices` WHERE `id` IN ({$candidateIdsList}) AND `due_amount` < 0")->fetchColumn();
        if ($checkNegDue !== 0) {
            throw new RuntimeException("POST-DML FAILED: Negative due amount detected ({$checkNegDue} invoices)!");
        }

        $pdo->commit();
        echo "  -> Transaction committed successfully.\n\n";

    } catch (Throwable $dmlEx) {
        $pdo->rollBack();
        throw new RuntimeException("DML EXECUTION FAILED! Transaction rolled back completely: " . $dmlEx->getMessage(), 0, $dmlEx);
    }

    // =========================================================================
    // STAGE C: POST-EXECUTION INDEPENDENT AUDIT
    // =========================================================================
    echo "[STAGE C] Running post-execution verification on Class Play March invoices...\n";

    $postMarchStmt = $pdo->query("
        SELECT fi.* 
        FROM fee_invoices fi
        JOIN students s ON s.id = fi.student_id
        WHERE s.class_id = 5 
          AND fi.fee_setup_id = 6 
          AND fi.invoice_no LIKE 'INV-202603-%'
        ORDER BY fi.id ASC
    ");
    $postMarchInvoices = $postMarchStmt->fetchAll();

    $post700 = array_filter($postMarchInvoices, fn($r) => abs((float)$r['amount'] - 700.00) < 0.01);
    $post650 = array_filter($postMarchInvoices, fn($r) => abs((float)$r['amount'] - 650.00) < 0.01);

    echo "  -> Total March Play Invoices: " . count($postMarchInvoices) . "\n";
    echo "  -> Invoices with Amount 700.00 BDT: " . count($post700) . " (Expected: exactly 2 - IDs 163, 169)\n";
    echo "  -> Invoices with Amount 650.00 BDT: " . count($post650) . " (Expected: exactly 54)\n";

    $post700Ids = array_column($post700, 'id');
    sort($post700Ids);
    if ($post700Ids !== EXCLUDED_INVOICE_IDS) {
        throw new RuntimeException("POST-EXECUTION FAILED: Invoices with 700 amount do not match excluded IDs!");
    }
    if (count($post650) !== 54) {
        throw new RuntimeException("POST-EXECUTION FAILED: Invoices with 650 amount count mismatch!");
    }

    echo "\n====================================================================\n";
    echo "SUCCESS: CLASS PLAY MARCH FEE ADJUSTMENT FULLY EXECUTED & CERTIFIED.\n";
    echo "  - Invoices Adjusted to 650 BDT: 39\n";
    echo "  - Excluded Invoices Preserved:  2 (IDs 163, 169)\n";
    echo "  - Total 650 BDT Invoices Now:   54\n";
    echo "  - Zero Negative Due Balances.\n";
    echo "  - Archive Tables Intact: " . ARCHIVE_TABLE_INVOICES . ", " . ARCHIVE_TABLE_PAYMENTS . "\n";
    echo "  - Exit Code: 0\n";
    echo "====================================================================\n";

} finally {
    if ($hasAdvisoryLock) {
        echo "[LOCK] Releasing MySQL advisory lock...\n";
        $unlockStmt = $pdo->prepare("SELECT RELEASE_LOCK(?)");
        $unlockStmt->execute([ADVISORY_LOCK_NAME]);
        echo "  -> Lock released cleanly.\n";
    }
}
