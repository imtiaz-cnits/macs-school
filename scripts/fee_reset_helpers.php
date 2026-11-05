<?php
declare(strict_types=1);

/**
 * MACS School Management System — Production Fee Collection Reset & Restore Helper Library
 * 
 * PH411-FIX-01: Shared Strict NULL-Safe Value Comparison
 * PH411-FIX-02: Hardened Fail-Closed Failure-Injection Guard
 * 
 * This file is safely includable across controllers, unit test harnesses, and integration tests
 * without code drift or behavioral deviations.
 */

if (!function_exists('valuesMatchExactly')) {
    /**
     * Strict NULL-safe value comparison helper.
     * 
     * Semantics:
     * - NULL === NULL => true
     * - NULL === '' => false
     * - '' === NULL => false
     * - '' === '' => true
     * - '0' === 0 => true (database scalar normalization)
     * - (string)$a === (string)$b for all non-null values
     */
    function valuesMatchExactly(mixed $a, mixed $b): bool {
        if ($a === null || $b === null) {
            return $a === $b;
        }
        return (string)$a === (string)$b;
    }
}

if (!function_exists('isTestFailureInjectionAllowed')) {
    /**
     * Hardened Fail-Closed Runtime Safety Guard for Test-Only Failure Injection.
     * 
     * Validates BOTH caller environment variables AND active PDO connection ground truth.
     * Guaranteed fail-closed: If any single invariant or query fails, returns FALSE.
     * 
     * Requirements:
     * 1. Exact host allowlist ('127.0.0.1' or 'localhost')
     * 2. Exact port allowlist (3308 only)
     * 3. Exact sandbox database name allowlist ('macs_school_ph411_test' only)
     * 4. Exact MySQL version validation (MySQL 8.0.45 only)
     * 5. Explicit test-mode environment variable (MACS_TEST_MODE === '1')
     * 6. Explicit failure-injection opt-in (MACS_FAILURE_INJECTION_ENABLED === '1')
     * 7. Active PDO connection validation via SELECT DATABASE(), SELECT @@port, SELECT VERSION(), SELECT USER()
     * 8. Rejection of remote hosts, production database names, and preserved databases
     */
    function isTestFailureInjectionAllowed(PDO $pdo, ?string $dbHost = null, int|string|null $dbPort = null, ?string $dbName = null): bool {
        // 1. Production environment check (strictly forbidden in production)
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
}

