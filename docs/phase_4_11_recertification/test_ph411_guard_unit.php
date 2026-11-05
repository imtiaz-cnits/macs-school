<?php
/**
 * PH411-FIX-02 Guard Security Unit Test: Strict Fail-Closed Validation
 * 
 * Verifies that isTestFailureInjectionAllowed() strictly rejects any non-sandbox,
 * production-like, remote, or misconfigured environment.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/scripts/fee_reset_helpers.php';

// Load Laravel Bootstrap for app() helper
require_once $projectRoot . '/vendor/autoload.php';
$app = require_once $projectRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "====================================================================\n";
echo "PH411-FIX-02: FAILURE-INJECTION GUARD SECURITY AUDIT HARNESS\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "====================================================================\n\n";

$pass = 0;
$fail = 0;

function reportGuardCase(string $id, string $desc, bool $allowed, bool $expectedAllowed): void {
    global $pass, $fail;
    if ($allowed === $expectedAllowed) {
        $pass++;
        echo "{$id} [PASS]: {$desc} -> Correctly " . ($allowed ? "ALLOWED" : "REJECTED (FAIL-CLOSED)") . "\n";
    } else {
        $fail++;
        echo "{$id} [FAIL]: {$desc} -> Got " . ($allowed ? "ALLOWED" : "REJECTED") . ", expected " . ($expectedAllowed ? "ALLOWED" : "REJECTED") . "!\n";
    }
}

// 1. Test PDO connection to port 3306 (Host Dev DB macs_school)
$pdo3306 = new PDO("mysql:host=127.0.0.1;port=3306;dbname=macs_school", "root", "");

// Ensure env vars are clear
putenv("MACS_TEST_MODE=");
putenv("MACS_FAILURE_INJECTION_ENABLED=");

// G-01: When env vars are missing completely
$res = isTestFailureInjectionAllowed($pdo3306, '127.0.0.1', 3306, 'macs_school');
reportGuardCase("GUARD-01", "Missing test environment variables against port 3306 (macs_school)", $res, false);

// G-02: Even if env vars are spoofed, port 3306 must be rejected
putenv("MACS_TEST_MODE=1");
putenv("MACS_FAILURE_INJECTION_ENABLED=1");
$res = isTestFailureInjectionAllowed($pdo3306, '127.0.0.1', 3306, 'macs_school');
reportGuardCase("GUARD-02", "Port 3306 with spoofed env vars against macs_school", $res, false);

// G-03: Caller passes fake port 3308, but active PDO is connected to 3306
$res = isTestFailureInjectionAllowed($pdo3306, '127.0.0.1', 3308, 'macs_school_ph411_test');
reportGuardCase("GUARD-03", "Spoofed caller arguments (3308) but active PDO on port 3306", $res, false);

// G-04: Target DB name is macsinjaazhdb
$res = isTestFailureInjectionAllowed($pdo3306, '127.0.0.1', 3308, 'macsinjaazhdb');
reportGuardCase("GUARD-04", "Database name macsinjaazhdb", $res, false);

// G-05: Target DB name is preserved database macsinjaazhdb_restore_test_20261104
$res = isTestFailureInjectionAllowed($pdo3306, '127.0.0.1', 3308, 'macsinjaazhdb_restore_test_20261104');
reportGuardCase("GUARD-05", "Preserved database macsinjaazhdb_restore_test_20261104", $res, false);

// G-06: Remote IP host
$res = isTestFailureInjectionAllowed($pdo3306, '192.168.1.50', 3308, 'macs_school_ph411_test');
reportGuardCase("GUARD-06", "Remote host IP 192.168.1.50", $res, false);

// 2. Test against Port 3308 (MySQL 8.0.45)
$pdo3308Root = new PDO("mysql:host=127.0.0.1;port=3308", "root", "");

// G-07: Port 3308 without selecting sandbox database (active DB is null or empty)
$res = isTestFailureInjectionAllowed($pdo3308Root, '127.0.0.1', 3308, 'macs_school_ph411_test');
reportGuardCase("GUARD-07", "Port 3308 but active database is not macs_school_ph411_test", $res, false);

// Now create the sandbox database on 3308 for testing
$pdo3308Root->exec("CREATE DATABASE IF NOT EXISTS `macs_school_ph411_test`");
$pdo3308Sandbox = new PDO("mysql:host=127.0.0.1;port=3308;dbname=macs_school_ph411_test", "root", "");

// G-08: Sandbox DB on 3308, but MACS_TEST_MODE is missing
putenv("MACS_TEST_MODE=");
putenv("MACS_FAILURE_INJECTION_ENABLED=1");
$res = isTestFailureInjectionAllowed($pdo3308Sandbox, '127.0.0.1', 3308, 'macs_school_ph411_test');
reportGuardCase("GUARD-08", "Active 3308 sandbox DB but MACS_TEST_MODE missing", $res, false);

// G-09: Sandbox DB on 3308, but MACS_FAILURE_INJECTION_ENABLED is missing
putenv("MACS_TEST_MODE=1");
putenv("MACS_FAILURE_INJECTION_ENABLED=");
$res = isTestFailureInjectionAllowed($pdo3308Sandbox, '127.0.0.1', 3308, 'macs_school_ph411_test');
reportGuardCase("GUARD-09", "Active 3308 sandbox DB but MACS_FAILURE_INJECTION_ENABLED missing", $res, false);

// G-10: Perfect sandbox environment: 127.0.0.1, 3308, macs_school_ph411_test, MySQL 8.0.45, both env vars = '1'
putenv("MACS_TEST_MODE=1");
putenv("MACS_FAILURE_INJECTION_ENABLED=1");
$res = isTestFailureInjectionAllowed($pdo3308Sandbox, '127.0.0.1', 3308, 'macs_school_ph411_test');
reportGuardCase("GUARD-10", "Active 3308 sandbox DB with MySQL 8.0.45 and verified test env vars", $res, true);

// Reset env vars after test
putenv("MACS_TEST_MODE=");
putenv("MACS_FAILURE_INJECTION_ENABLED=");

echo "\n====================================================================\n";
echo "FAILURE-INJECTION GUARD AUDIT SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
echo "====================================================================\n";

if ($fail > 0) {
    exit(1);
}
exit(0);
