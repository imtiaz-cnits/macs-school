<?php
/**
 * MACS School - Live Server Checksum Diagnostic Tool
 * 
 * Inspects fee_payments and fee_invoices ground-truth on live server
 * to identify exact column, type, or row differences.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

require_once __DIR__ . '/fee_reset_helpers.php';

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
]);

// Enforce canonical application timezone (Asia/Dhaka / +06:00) for deterministic TIMESTAMP retrieval
$pdo->exec("SET time_zone = '+06:00'");

echo "====================================================================\n";
echo "MACS FEE COLLECTION LIVE DIAGNOSTIC TOOL\n";
echo "Active Database: {$dbName} on {$dbHost}:{$dbPort}\n";
echo "MySQL Version:   " . $pdo->query("SELECT VERSION()")->fetchColumn() . "\n";
echo "Timestamp:       " . date('Y-m-d H:i:s T') . "\n";
echo "====================================================================\n\n";

// 1. Table Schema
echo "[1. FEE_PAYMENTS COLUMNS & TYPES]\n";
$cols = $pdo->query("SHOW FULL COLUMNS FROM fee_payments")->fetchAll();
foreach ($cols as $c) {
    echo sprintf("  - %-20s | %-15s | Null: %-4s | Default: %s\n", $c['Field'], $c['Type'], $c['Null'], $c['Default'] ?? 'NULL');
}

// 2. Candidate Payments Check
echo "\n[2. CANDIDATE PAYMENTS (< '2026-09-21')]\n";
$candPmts = $pdo->query("SELECT * FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll();
$candCount = count($candPmts);
$candSum = array_reduce($candPmts, fn($carry, $r) => $carry + (float)$r['paid_amount'], 0.0);
echo "  - Count: {$candCount} (Expected: 44)\n";
echo "  - Sum:   ৳" . number_format($candSum, 2) . " (Expected: ৳27,600.00)\n";
if ($candCount > 0) {
    // Checksum
    usort($candPmts, fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);
    $candPks = implode(',', array_column($candPmts, 'id'));
    $header = "dataset:count={$candCount};pks={$candPks}\n";
    $body = '';
    foreach ($candPmts as $r) {
        ksort($r);
        $s = '';
        foreach ($r as $col => $val) {
            $cn = (string)$col;
            $s .= 'k:' . strlen($cn) . ':' . $cn . ';';
            if ($val === null) $s .= 'v:N;';
            elseif (is_int($val)) $s .= 'v:i:' . $val . ';';
            elseif (is_bool($val)) $s .= 'v:b:' . ($val ? '1' : '0') . ';';
            else { $sv = (string)$val; $s .= 'v:s:' . strlen($sv) . ':' . $sv . ';'; }
        }
        $body .= 'row_id:' . $r['id'] . "\n" . $s . "\n";
    }
    $candChk = hash('sha256', $header . $body);
    echo "  - Checksum: {$candChk}\n";
    echo "  - Expected: 1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038\n";
    echo "  - Match:    " . ($candChk === '1e3aae2afc63603f83ccca80e8a9df13fc45ae09e6e1dd9239b897daf155f038' ? 'YES (100% BIT-MATCH)' : 'MISMATCH') . "\n";
}

// 3. Target Invoices Check
const TARGET_INVOICE_IDS = [
    31, 32, 75, 121, 126, 127, 135, 357, 571, 589, 
    658, 659, 660, 661, 663, 664, 765, 766, 995, 1227, 
    1431, 1663, 1738, 1739, 1968, 2275, 2276, 2505, 2812, 2813, 
    3349, 3350, 3886, 3887, 4423, 4424, 5015, 5016, 9837, 9838, 
    10379, 10380, 10921, 10922
];
echo "\n[3. TARGET INVOICES (44 Target IDs)]\n";
$targetList = implode(',', TARGET_INVOICE_IDS);
$invs = $pdo->query("SELECT * FROM fee_invoices WHERE id IN ({$targetList}) ORDER BY id ASC")->fetchAll();
$invCount = count($invs);
$invGross = array_reduce($invs, fn($carry, $r) => $carry + (float)$r['amount'], 0.0);
$invNet = array_reduce($invs, fn($carry, $r) => $carry + (float)$r['net_amount'], 0.0);
echo "  - Count: {$invCount} (Expected: 44)\n";
echo "  - Gross: ৳" . number_format($invGross, 2) . " (Expected: ৳28,050.00)\n";
echo "  - Net:   ৳" . number_format($invNet, 2) . " (Expected: ৳27,600.00)\n";
if ($invCount > 0) {
    usort($invs, fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);
    $invPks = implode(',', array_column($invs, 'id'));
    $header = "dataset:count={$invCount};pks={$invPks}\n";
    $body = '';
    foreach ($invs as $r) {
        ksort($r);
        $s = '';
        foreach ($r as $col => $val) {
            $cn = (string)$col;
            $s .= 'k:' . strlen($cn) . ':' . $cn . ';';
            if ($val === null) $s .= 'v:N;';
            elseif (is_int($val)) $s .= 'v:i:' . $val . ';';
            elseif (is_bool($val)) $s .= 'v:b:' . ($val ? '1' : '0') . ';';
            else { $sv = (string)$val; $s .= 'v:s:' . strlen($sv) . ':' . $sv . ';'; }
        }
        $body .= 'row_id:' . $r['id'] . "\n" . $s . "\n";
    }
    $invChk = hash('sha256', $header . $body);
    echo "  - Checksum: {$invChk}\n";
    echo "  - Expected: 3e7dcb1990ea343a7e6fd83c442a0345c0ad295f3840803a8ca1cf861429f68d\n";
    echo "  - Match:    " . ($invChk === '3e7dcb1990ea343a7e6fd83c442a0345c0ad295f3840803a8ca1cf861429f68d' ? 'YES (100% BIT-MATCH)' : 'MISMATCH') . "\n";
}

// 4. Preserved Payments Sample & Type Inspection
echo "\n[4. PRESERVED PAYMENTS DATASET A (>= '2026-09-21')]\n";
$presPmts = $pdo->query("SELECT * FROM fee_payments WHERE payment_date >= '2026-09-21 00:00:00' ORDER BY id ASC")->fetchAll();
$presCount = count($presPmts);
$presSum = array_reduce($presPmts, fn($carry, $r) => $carry + (float)$r['paid_amount'], 0.0);
echo "  - Count: {$presCount} (Expected: 7122)\n";
echo "  - Sum:   ৳" . number_format($presSum, 2) . " (Expected: ৳3,851,290.00)\n";

if ($presCount > 0) {
    echo "\n  First Preserved Row (ID: {$presPmts[0]['id']}) Types & Values:\n";
    foreach ($presPmts[0] as $k => $v) {
        $type = gettype($v);
        $valDisplay = $v === null ? 'NULL' : (string)$v;
        echo sprintf("    %-18s: (%-7s) %s\n", $k, $type, $valDisplay);
    }
}

