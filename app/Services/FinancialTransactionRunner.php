<?php

namespace App\Services;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactionsManager as TestingDatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use PDOException;
use Throwable;

/**
 * Coordinates single outer database transaction boundaries with explicit rollback
 * and bounded deadlock/lock-wait retries for financial write operations.
 */
class FinancialTransactionRunner
{
    public const DEFAULT_MAX_ATTEMPTS = 3;
    public const DEFAULT_BASE_DELAY_MS = 25;

    /**
     * SQLSTATE and MySQL driver error codes eligible for bounded transaction retry.
     */
    private const RETRYABLE_SQLSTATES = ['40001'];
    private const RETRYABLE_DRIVER_CODES = [1213, 1205];

    /**
     * Execute a financial callback inside a single atomic transaction with bounded deadlock retries.
     *
     * Rejects nested application transactions (`transactionLevel > 0`) rather than using
     * implicit savepoints, guaranteeing full rollback & retry integrity.
     *
     * @template TReturn
     * @param  Closure(): TReturn  $callback
     * @param  int|null  $maxAttempts
     * @param  int|null  $baseDelayMs
     * @param  string|null  $connectionName
     * @return TReturn
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function runWithDeadlockRetry(
        callable $callback,
        ?int $maxAttempts = null,
        ?int $baseDelayMs = null,
        ?string $connectionName = null
    ): mixed {
        $attempts = $maxAttempts ?? self::DEFAULT_MAX_ATTEMPTS;
        $delayMs = $baseDelayMs ?? self::DEFAULT_BASE_DELAY_MS;

        if ($attempts < 1) {
            throw new InvalidArgumentException('maxAttempts must be at least 1.');
        }

        if ($delayMs < 0) {
            throw new InvalidArgumentException('baseDelayMs cannot be negative.');
        }

        $connection = DB::connection($connectionName);

        if ($this->effectiveApplicationTransactionLevel($connection) > 0) {
            throw new LogicException(
                'Nested transactions are strictly prohibited inside FinancialTransactionRunner.'
            );
        }

        $entryLevel = $connection->transactionLevel();

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $connection->beginTransaction();

            try {
                $result = $callback($connection);

                if ($connection->transactionLevel() !== $entryLevel + 1) {
                    throw new LogicException(
                        sprintf(
                            'Unbalanced transaction level detected inside FinancialTransactionRunner (expected %d, got %d).',
                            $entryLevel + 1,
                            $connection->transactionLevel()
                        )
                    );
                }

                $connection->commit();

                return $result;
            } catch (Throwable $e) {
                $this->rollbackSafely($connection, $entryLevel);

                if ($attempt < $attempts && $this->isRetryableDeadlock($e)) {
                    $this->backoffBeforeRetry($attempt, $delayMs);
                    continue;
                }

                throw $e;
            }
        }

        throw new LogicException('Unreachable retry state reached in FinancialTransactionRunner.');
    }

    /**
     * Assert that execution is currently inside an active application transaction.
     *
     * @throws LogicException
     */
    public function assertInTransaction(string $context = 'Financial operation', ?string $connectionName = null): void
    {
        $connection = DB::connection($connectionName);

        if ($this->effectiveApplicationTransactionLevel($connection) < 1) {
            throw new LogicException("{$context} must execute inside an active database transaction.");
        }
    }

    /**
     * Determine whether an exception represents a retryable SQLSTATE 40001 or MySQL deadlock/lock-wait.
     */
    public function isRetryableDeadlock(Throwable $e): bool
    {
        $current = $e;

        while ($current !== null) {
            if ($current instanceof QueryException || $current instanceof PDOException) {
                $errorInfo = property_exists($current, 'errorInfo') && is_array($current->errorInfo)
                    ? $current->errorInfo
                    : [];

                $sqlState = isset($errorInfo[0]) ? (string) $errorInfo[0] : (string) $current->getCode();
                $driverCode = isset($errorInfo[1]) ? (int) $errorInfo[1] : null;

                if (in_array($sqlState, self::RETRYABLE_SQLSTATES, true)) {
                    return true;
                }

                if ($driverCode !== null && in_array($driverCode, self::RETRYABLE_DRIVER_CODES, true)) {
                    return true;
                }
            }

            $current = $current->getPrevious();
        }

        return false;
    }

    /**
     * Compute active application transaction depth, excluding Laravel's RefreshDatabase
     * single wrapper transaction solely during unit/feature test execution.
     */
    protected function effectiveApplicationTransactionLevel(ConnectionInterface $connection): int
    {
        $level = $connection->transactionLevel();

        if ($level === 0) {
            return 0;
        }

        if ($this->isWrappedByTestHarnessTransaction($connection)) {
            return max(0, $level - 1);
        }

        return $level;
    }

    /**
     * Detect whether the connection is currently wrapped exclusively in Laravel's test
     * isolation harness (`RefreshDatabase` / `DatabaseTransactions`). Never active outside tests.
     */
    private function isWrappedByTestHarnessTransaction(ConnectionInterface $connection): bool
    {
        if (!function_exists('app') || !app()->runningUnitTests() || !app()->bound('db.transactions')) {
            return false;
        }

        $manager = app('db.transactions');
        if (!$manager instanceof TestingDatabaseTransactionsManager) {
            return false;
        }

        $connName = method_exists($connection, 'getName') ? $connection->getName() : null;

        return $manager->getPendingTransactions()->contains(
            fn ($record): bool => $record->connection === $connName && (int) $record->level === 1
        );
    }

    /**
     * Explicitly roll back all transaction work opened in this attempt back to entry level.
     */
    private function rollbackSafely(ConnectionInterface $connection, int $entryLevel): void
    {
        if ($connection->transactionLevel() > $entryLevel) {
            $connection->rollBack($entryLevel);
        }
    }

    /**
     * Apply bounded exponential backoff with jitter between deadlock retries.
     */
    private function backoffBeforeRetry(int $attempt, int $baseDelayMs): void
    {
        if ($baseDelayMs <= 0) {
            return;
        }

        $exponentialMs = $baseDelayMs * (2 ** ($attempt - 1));
        $jitterMs = random_int(0, $baseDelayMs);
        usleep(($exponentialMs + $jitterMs) * 1000);
    }
}
