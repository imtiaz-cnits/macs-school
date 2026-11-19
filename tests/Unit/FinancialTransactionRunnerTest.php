<?php

namespace Tests\Unit;

use App\Services\FinancialTransactionRunner;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use PDOException;
use RuntimeException;
use Tests\TestCase;

/**
 * Isolated root-transaction tests for FinancialTransactionRunner (no RefreshDatabase wrapper
 * so real level-0 transaction commit, rollback, and nested-transaction guards are exercised).
 */
class FinancialTransactionRunnerTest extends TestCase
{
    private FinancialTransactionRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runner = new FinancialTransactionRunner();

        // Ensure a fresh in-memory schema at true transactionLevel() === 0.
        Artisan::call('migrate:fresh');
    }

    public function test_executes_and_commits_root_transaction_cleanly(): void
    {
        $this->assertSame(0, DB::transactionLevel());

        $userId = $this->runner->runWithDeadlockRetry(function () {
            $this->runner->assertInTransaction('Test commit check');

            return DB::table('users')->insertGetId([
                'name' => 'Tx Commit User',
                'email' => 'tx_commit@example.com',
                'password' => bcrypt('secret'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->assertGreaterThan(0, $userId);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertDatabaseHas('users', ['email' => 'tx_commit@example.com']);
    }

    public function test_rejects_nested_transaction_when_outer_transaction_is_open(): void
    {
        $this->assertSame(0, DB::transactionLevel());

        DB::beginTransaction();
        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Nested transactions are strictly prohibited');

            $this->runner->runWithDeadlockRetry(fn () => 'never-reached');
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    public function test_rejects_unbalanced_inner_transaction_leak(): void
    {
        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Unbalanced transaction level');

            $this->runner->runWithDeadlockRetry(function () {
                DB::beginTransaction();
                return 'leaked';
            });
        } finally {
            $this->assertSame(0, DB::transactionLevel());
        }
    }

    public function test_assert_in_transaction_throws_when_called_outside_transaction(): void
    {
        $this->assertSame(0, DB::transactionLevel());
        $this->expectException(LogicException::class);
        $this->runner->assertInTransaction('Standalone service call');
    }

    public function test_retries_on_sqlstate_40001_and_rolls_back_failed_attempts_atomically(): void
    {
        $attempts = 0;

        $result = $this->runner->runWithDeadlockRetry(function () use (&$attempts) {
            $attempts++;

            DB::table('users')->insert([
                'name' => "Attempt {$attempts}",
                'email' => 'retry_atomic@example.com',
                'password' => bcrypt('secret'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($attempts < 3) {
                $pdoEx = new PDOException('Serialization failure', 40001);
                $pdoEx->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock'];
                throw new QueryException('sqlite', 'INSERT INTO users ...', [], $pdoEx);
            }

            return 'committed-on-attempt-3';
        }, maxAttempts: 3, baseDelayMs: 0);

        $this->assertSame('committed-on-attempt-3', $result);
        $this->assertSame(3, $attempts);
        $this->assertSame(0, DB::transactionLevel());
        // Must have rolled back attempts 1 and 2 cleanly: only Attempt 3 persists (unique email proves no duplicate!).
        $this->assertDatabaseHas('users', ['email' => 'retry_atomic@example.com', 'name' => 'Attempt 3']);
        $this->assertSame(1, DB::table('users')->where('email', 'retry_atomic@example.com')->count());
    }

    public function test_retries_on_mysql_lock_wait_timeout_1205(): void
    {
        $attempts = 0;

        $result = $this->runner->runWithDeadlockRetry(function () use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                $pdoEx = new PDOException('Lock wait timeout exceeded', 0);
                $pdoEx->errorInfo = ['HY000', 1205, 'Lock wait timeout exceeded; try restarting transaction'];
                throw $pdoEx;
            }
            return 'ok';
        }, maxAttempts: 3, baseDelayMs: 0);

        $this->assertSame('ok', $result);
        $this->assertSame(2, $attempts);
    }

    public function test_throws_immediately_without_retry_on_non_deadlock_error_and_rolls_back(): void
    {
        $attempts = 0;

        try {
            $this->runner->runWithDeadlockRetry(function () use (&$attempts) {
                $attempts++;

                DB::table('users')->insert([
                    'name' => 'Rolled Back User',
                    'email' => 'non_retryable@example.com',
                    'password' => bcrypt('secret'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                throw new RuntimeException('Validation or domain failure');
            }, maxAttempts: 3, baseDelayMs: 0);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Validation or domain failure', $e->getMessage());
        }

        $this->assertSame(1, $attempts);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertDatabaseMissing('users', ['email' => 'non_retryable@example.com']);
    }

    public function test_rethrows_after_exhausting_max_deadlock_attempts(): void
    {
        $attempts = 0;

        try {
            $this->runner->runWithDeadlockRetry(function () use (&$attempts) {
                $attempts++;
                $pdoEx = new PDOException('Persistent deadlock');
                $pdoEx->errorInfo = ['40001', 1213, 'Deadlock found'];
                throw $pdoEx;
            }, maxAttempts: 2, baseDelayMs: 0);

            $this->fail('Expected PDOException was not thrown after retries exhausted.');
        } catch (PDOException $e) {
            $this->assertSame('Persistent deadlock', $e->getMessage());
        }

        $this->assertSame(2, $attempts);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_validates_retry_configuration_arguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->runner->runWithDeadlockRetry(fn () => true, maxAttempts: 0);
    }
}
