# PH411: FAILURE-INJECTION GUARD SECURITY AUDIT REPORT

**Project:** MACS School Management System — Production Fee Collection Reset & Restore  
**Scope:** Hardened Fail-Closed Failure-Injection Guard Validation  
**Execution Date:** November 5, 2026  
**Auditor:** Senior Database Reliability Engineer & Independent Security Auditor  
**Status:** PASS (10 / 10 Scenarios Certified)  

---

## 1. Executive Summary & Audit Mandate

The Phase 4.11 Independent Audit noted that previous failure-injection guards in both controllers relied on permissive combinations of substring matches and client-supplied parameters:
```php
// FLAWED PREVIOUS GUARD: Permissive substring and caller argument trust
if ((int)$dbPort !== 3308 && !str_contains($dbName, 'test') && !str_contains($dbName, 'sandbox') ...) {
    return false;
}
```

This permissive design created a critical vulnerability: if an operator accidentally passed `MACS_TEST_FAILURE_POINT` in production or staging, the script might have triggered failure injection if the database name happened to contain the substring `test`.

---

## 2. Remediated Architecture (`scripts/fee_reset_helpers.php`)

To eliminate any possibility of accidental or unauthorized failure injection in production, the guard was completely re-architected with **fail-closed multi-tier validation**:

```php
function isTestFailureInjectionAllowed(PDO $pdo, ?string $dbHost = null, int|string|null $dbPort = null, ?string $dbName = null): bool {
    // 1. Strictly forbidden in production environment
    if (function_exists('app')) {
        try {
            if (app()->environment('production')) {
                return false;
            }
        } catch (Throwable $t) {
            return false;
        }
    }

    // 2. Explicit environment variable requirements (must be explicitly '1')
    if (getenv('MACS_TEST_MODE') !== '1') {
        return false;
    }
    if (getenv('MACS_FAILURE_INJECTION_ENABLED') !== '1') {
        return false;
    }

    // 3. Validate caller parameters if provided
    if ($dbHost !== null && $dbHost !== '127.0.0.1' && $dbHost !== 'localhost') {
        return false;
    }
    if ($dbPort !== null && (int)$dbPort !== 3308) {
        return false;
    }
    if ($dbName !== null && $dbName !== 'macs_school_ph411_test') {
        return false;
    }

    // 4. Validate active live PDO connection directly against MySQL engine
    try {
        $activeDb = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
        $activePort = (int)$pdo->query("SELECT @@port")->fetchColumn();
        $activeVersion = (string)$pdo->query("SELECT VERSION()")->fetchColumn();
        $activeUser = (string)$pdo->query("SELECT USER()")->fetchColumn();

        // Ground-truth database name must match sandbox allowlist exactly
        if ($activeDb !== 'macs_school_ph411_test') {
            return false;
        }

        // Ground-truth port must be 3308 (isolated MySQL test instance)
        if ($activePort !== 3308) {
            return false;
        }

        // Ground-truth MySQL version must be 8.0.45
        if (!str_contains($activeVersion, '8.0.45')) {
            return false;
        }

        // Ground-truth connection must be loopback only
        if (!str_contains($activeUser, '@localhost') && !str_contains($activeUser, '@127.0.0.1')) {
            return false;
        }

        // Explicit rejection of production-like and preserved database names
        $forbiddenDbs = [
            'macs_school',
            'macsinjaazhdb',
            'macsinjaazhdb_restore_test_20261104',
            'production',
            'live'
        ];
        if (in_array(strtolower($activeDb), $forbiddenDbs, true)) {
            return false;
        }

        return true;
    } catch (Throwable $e) {
        // Fail closed on any PDO query error
        return false;
    }
}
```

---

## 3. Security Verification Matrix (`scratch/test_ph411_guard_unit.php`)

All 10 attack and bypass scenarios were independently executed and certified:

| Case ID | Test Scenario | Injection Allowed? | Result | Security Assessment |
|---|---|---|---|---|
| **GUARD-01** | Missing test env vars against port 3306 (`macs_school`) | `false` | **PASS** | Default fail-closed when env vars are absent. |
| **GUARD-02** | Spoofed env vars (`MACS_TEST_MODE=1`) against port 3306 | `false` | **PASS** | Port 3306 rejected even with active test flags. |
| **GUARD-03** | Spoofed CLI arguments (`3308`) but active PDO on port 3306 | `false` | **PASS** | Active PDO query `SELECT @@port` detected 3306; rejected. |
| **GUARD-04** | Targeted database `macsinjaazhdb` | `false` | **PASS** | Production DB explicitly blacklisted; rejected. |
| **GUARD-05** | Targeted database `macsinjaazhdb_restore_test_20261104` | `false` | **PASS** | Preserved test database explicitly protected; rejected. |
| **GUARD-06** | Remote host IP (`192.168.1.50`) | `false` | **PASS** | Non-loopback host strictly rejected. |
| **GUARD-07** | Port 3308 but active DB is `information_schema` / empty | `false` | **PASS** | Database allowlist enforced (`macs_school_ph411_test` only). |
| **GUARD-08** | Sandbox DB on 3308, but `MACS_TEST_MODE` is missing | `false` | **PASS** | Dual-flag opt-in enforced. |
| **GUARD-09** | Sandbox DB on 3308, but `MACS_FAILURE_INJECTION_ENABLED` missing | `false` | **PASS** | Dual-flag opt-in enforced. |
| **GUARD-10** | Sandbox DB on 3308, MySQL 8.0.45, both env vars = '1' | `true` | **PASS** | Authenticated test execution authorized. |

---

## 4. Production Fail-Closed Behavior

In both controllers (`execute_fee_collection_reset_atomic.php` line 667 and `restore_fee_collection_reset_atomic.php` line 565):
```php
$testFailurePoint = getenv('MACS_TEST_FAILURE_POINT');
if (!empty($testFailurePoint)) {
    if (!isTestFailureInjectionAllowed($pdo, $dbHost, $dbPort, $dbName)) {
        fwrite(STDERR, "SECURITY ALERT: MACS_TEST_FAILURE_POINT is strictly forbidden in this environment/database!\n");
        exit(100);
    }
    // Only reachable on authenticated sandbox
}
```

If anyone sets `MACS_TEST_FAILURE_POINT` in production:
1. `isTestFailureInjectionAllowed()` executes `SELECT DATABASE()` and `SELECT @@port` on the production connection.
2. Checks fail immediately.
3. The script prints a security alert and calls `exit(100)`.
4. **Zero database mutations occur; zero injected exceptions are triggered.**

