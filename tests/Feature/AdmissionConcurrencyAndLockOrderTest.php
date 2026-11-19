<?php

namespace Tests\Feature;

use App\Models\AdmissionFeeStructure;
use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeAdjustmentLog;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeSetup;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionFeeStructureService;
use App\Services\AdmissionInvoiceService;
use App\Services\AdmissionPaymentReversalService;
use App\Services\FinancialTransactionRunner;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDOException;
use Tests\TestCase;

/**
 * Verifies deterministic V8 5-tier SQL lock acquisition order, batch rollback atomicity,
 * and simulated SQLSTATE 40001 / MySQL 1213 retry behavior on SQLite :memory:.
 * Real MySQL InnoDB multi-connection contention remains required in staging before deploy.
 */
class AdmissionConcurrencyAndLockOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classOne;
    protected Section $sectionA;
    protected FeeCategory $admissionCat;
    protected FeeCategory $tuitionCat;
    protected AdmissionFeeStructure $structure;
    protected AdmissionCollectionService $collectionService;
    protected AdmissionPaymentReversalService $reversalService;
    protected AdmissionInvoiceService $invoiceService;
    protected AdmissionFeeStructureService $structureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collectionService = app(AdmissionCollectionService::class);
        $this->reversalService = app(AdmissionPaymentReversalService::class);
        $this->invoiceService = app(AdmissionInvoiceService::class);
        $this->structureService = app(AdmissionFeeStructureService::class);

        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->superAdmin = User::create([
            'name' => 'Conc Super',
            'email' => 'conc_super@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->branch = Branch::create(['branch_name' => 'Main Campus', 'user_id' => $this->superAdmin->id]);
        $this->session = SessionYear::create(['session_name' => '2026', 'user_id' => $this->superAdmin->id]);
        $this->classOne = Classes::create(['class_name' => 'One', 'user_id' => $this->superAdmin->id]);
        $this->sectionA = Section::create([
            'section_name' => 'A',
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'user_id' => $this->superAdmin->id,
        ]);

        $this->admissionCat = FeeCategory::create(['name' => 'Admission Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $this->tuitionCat = FeeCategory::create(['name' => 'Monthly Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);

        $this->structure = $this->structureService->createStructure([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'structure_name' => 'Class One Lock Pkg',
            'status' => 'Active',
            'total_amount' => '2500.00',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 1,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Charge',
                    'amount' => '1500.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->tuitionCat->id,
                    'head_name' => 'January Tuition',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => true,
                    'sort_order' => 20,
                ],
            ],
        ], $this->superAdmin->id);
    }

    protected function createStudent(string $identity): Student
    {
        return Student::create([
            'user_id' => $this->superAdmin->id,
            'student_identity' => $identity,
            'student_name' => 'Student ' . $identity,
            'roll_number' => '01',
            'dob' => '2016-01-01',
            'gender' => 'Male',
            'father_name' => 'Father',
            'father_mobile' => '01711111111',
            'mother_name' => 'Mother',
            'mother_mobile' => '01722222222',
            'guardian_name' => 'Guardian',
            'guardian_mobile' => '01733333333',
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session->id,
            'class_id' => $this->classOne->id,
            'section_id' => $this->sectionA->id,
            'status' => 'Active',
            'sms_status' => 'Active',
            'present_village' => 'V',
            'present_post_office' => 'P',
            'present_district' => 'D',
            'present_division' => 'Div',
            'permanent_village' => 'V',
            'permanent_post_office' => 'P',
            'permanent_district' => 'D',
            'permanent_division' => 'Div',
        ]);
    }

    public function test_bulk_collection_enforces_sorted_asc_5_tier_lock_hierarchy_regardless_of_input_order(): void
    {
        $student1 = $this->createStudent('STD-LOCK-01');
        $student2 = $this->createStudent('STD-LOCK-02');

        $inv1 = $this->invoiceService->generateForStudent($student1, [], $this->superAdmin->id)['invoice'];
        $inv2 = $this->invoiceService->generateForStudent($student2, [], $this->superAdmin->id)['invoice'];

        // Reverse order on purpose: Student 2 / Invoice 2 submitted before Student 1 / Invoice 1
        $capturedSql = [];
        DB::listen(function ($query) use (&$capturedSql): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'from "students"') || str_contains($sql, 'from "fee_invoices"')
                || str_contains($sql, 'from "fee_payments"') || str_contains($sql, 'from "admission_invoice_head_allocations"')
                || str_contains($sql, 'from "admission_payment_head_allocations"')
                || str_contains($sql, 'from `students`') || str_contains($sql, 'from `fee_invoices`')
                || str_contains($sql, 'from `fee_payments`') || str_contains($sql, 'from `admission_invoice_head_allocations`')
                || str_contains($sql, 'from `admission_payment_head_allocations`')
            ) {
                $capturedSql[] = [
                    'sql' => $sql,
                    'bindings' => $query->bindings,
                ];
            }
        });

        $this->actingAs($this->superAdmin);
        $res = $this->postJson(route('fees.collection.bulk_students_store'), [
            'invoice_ids' => [$inv2->id, $inv1->id],
            'paying_amounts' => [
                $inv2->id => '1000.00',
                $inv1->id => '1500.00',
            ],
            'payment_method' => 'Cash',
        ]);
        $res->assertOk()->assertJsonPath('success', true);

        // Locate lock query indexes (ORDER BY id ASC queries in tier order)
        $findIndex = function (string $tableFragment) use ($capturedSql): int {
            foreach ($capturedSql as $idx => $entry) {
                if (str_contains($entry['sql'], $tableFragment) && str_contains($entry['sql'], 'order by')) {
                    return $idx;
                }
            }
            return -1;
        };

        $t1StudentIdx = $findIndex('students');
        $t2InvoiceIdx = $findIndex('fee_invoices');
        $t3PaymentIdx = $findIndex('fee_payments');
        $t4InvHeadIdx = $findIndex('admission_invoice_head_allocations');
        $t5PayHeadIdx = $findIndex('admission_payment_head_allocations');

        $this->assertGreaterThanOrEqual(0, $t1StudentIdx);
        $this->assertGreaterThan($t1StudentIdx, $t2InvoiceIdx);
        $this->assertGreaterThan($t2InvoiceIdx, $t3PaymentIdx);
        $this->assertGreaterThan($t3PaymentIdx, $t4InvHeadIdx);
        $this->assertGreaterThan($t4InvHeadIdx, $t5PayHeadIdx);

        // Bindings for students & fee_invoices must be strictly ASC sorted regardless of reverse request order!
        $this->assertSame([$student1->id, $student2->id], array_map('intval', $capturedSql[$t1StudentIdx]['bindings']));
        $this->assertSame([$inv1->id, $inv2->id], array_map('intval', $capturedSql[$t2InvoiceIdx]['bindings']));
    }

    public function test_reversal_and_restore_paths_strictly_obey_5_tier_lock_hierarchy(): void
    {
        $student = $this->createStudent('STD-LOCK-REV');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $payment = $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id)['payment'];

        $capturedSql = [];
        DB::listen(function ($query) use (&$capturedSql): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'pragma_') || str_contains($sql, 'sqlite_master')) {
                return;
            }
            if (str_contains($sql, 'from "students"') || str_contains($sql, 'from "fee_invoices"')
                || str_contains($sql, 'from "fee_payments"') || str_contains($sql, 'from "admission_invoice_head_allocations"')
                || str_contains($sql, 'from "admission_payment_head_allocations"')
                || str_contains($sql, 'from `students`') || str_contains($sql, 'from `fee_invoices`')
                || str_contains($sql, 'from `fee_payments`') || str_contains($sql, 'from `admission_invoice_head_allocations`')
                || str_contains($sql, 'from `admission_payment_head_allocations`')
            ) {
                $capturedSql[] = $sql;
            }
        });

        $this->reversalService->reversePayment($payment->id, 'Lock hierarchy check', $this->superAdmin->id);

        // Index 0 is routing lookup (`select id, fee_invoice_id, student_id, branch_id from fee_payments`),
        // then Tier 1 Student -> Tier 2 Invoice -> Tier 3 Payments -> Tier 4 Inv Heads -> Tier 5 Pay Heads
        $tierPositions = [];
        foreach ($capturedSql as $idx => $sql) {
            if ($idx === 0) {
                continue;
            }
            if (!isset($tierPositions['students']) && str_contains($sql, 'students')) {
                $tierPositions['students'] = $idx;
            }
            if (!isset($tierPositions['fee_invoices']) && str_contains($sql, 'fee_invoices')) {
                $tierPositions['fee_invoices'] = $idx;
            }
            if (!isset($tierPositions['fee_payments']) && str_contains($sql, 'fee_payments')) {
                $tierPositions['fee_payments'] = $idx;
            }
            if (!isset($tierPositions['inv_heads']) && str_contains($sql, 'admission_invoice_head_allocations')) {
                $tierPositions['inv_heads'] = $idx;
            }
            if (!isset($tierPositions['pay_heads']) && str_contains($sql, 'admission_payment_head_allocations')) {
                $tierPositions['pay_heads'] = $idx;
            }
        }

        $this->assertLessThan($tierPositions['fee_invoices'], $tierPositions['students']);
        $this->assertLessThan($tierPositions['fee_payments'], $tierPositions['fee_invoices']);
        $this->assertLessThan($tierPositions['inv_heads'], $tierPositions['fee_payments']);
        $this->assertLessThan($tierPositions['pay_heads'], $tierPositions['inv_heads']);

        // Also assert Tier 1 Student -> Tier 2 FeeInvoice -> Tier 3 FeePayment on restoreInvoice()
        $this->invoiceService->cancelUnpaidInvoice($inv->id, 'Cancel to inspect restore locks', $this->superAdmin->id);
        $capturedSql = [];
        $this->actingAs($this->superAdmin);
        $this->postJson(route('fees.invoice.restore', $inv->id), [
            'reason' => 'Inspect restore lock order',
            'confirm' => true,
        ])->assertOk();

        $restoreTiers = [];
        foreach ($capturedSql as $idx => $sql) {
            if ($idx === 0) {
                continue; // routing lookup (`select id, student_id from fee_invoices`)
            }
            if (!isset($restoreTiers['students']) && str_contains($sql, 'students')) {
                $restoreTiers['students'] = $idx;
            }
            if (!isset($restoreTiers['fee_invoices']) && str_contains($sql, 'fee_invoices')) {
                $restoreTiers['fee_invoices'] = $idx;
            }
            if (!isset($restoreTiers['fee_payments']) && str_contains($sql, 'fee_payments')) {
                $restoreTiers['fee_payments'] = $idx;
            }
            if (!isset($restoreTiers['inv_heads']) && str_contains($sql, 'admission_invoice_head_allocations')) {
                $restoreTiers['inv_heads'] = $idx;
            }
            if (!isset($restoreTiers['pay_heads']) && str_contains($sql, 'admission_payment_head_allocations')) {
                $restoreTiers['pay_heads'] = $idx;
            }
        }

        $this->assertLessThan($restoreTiers['fee_invoices'], $restoreTiers['students']);
        $this->assertLessThan($restoreTiers['fee_payments'], $restoreTiers['fee_invoices']);
        $this->assertLessThan($restoreTiers['inv_heads'], $restoreTiers['fee_payments']);
        $this->assertLessThan($restoreTiers['pay_heads'], $restoreTiers['inv_heads']);
    }

    public function test_deadlock_retry_rolls_back_attempt_one_including_idempotency_key_and_succeeds_cleanly_on_retry(): void
    {
        $student = $this->createStudent('STD-CONC-DL');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $idempKey = 'deadlock-retry-key-20261118';

        // Simulate transient MySQL SQLSTATE 40001 / code 1213 deadlock on attempt 1 during payment commit
        $attempt = 0;
        $realRunner = app(FinancialTransactionRunner::class);
        $retryingService = new AdmissionCollectionService(new class($realRunner, $attempt) extends FinancialTransactionRunner {
            private int $counter;
            public function __construct(private FinancialTransactionRunner $inner, int &$ref)
            {
                $this->counter = &$ref;
            }
            public function runWithDeadlockRetry(
                callable $callback,
                ?int $maxAttempts = null,
                ?int $baseDelayMs = null,
                ?string $connectionName = null
            ): mixed {
                return $this->inner->runWithDeadlockRetry(function (ConnectionInterface $conn) use ($callback) {
                    $this->counter++;
                    $res = $callback($conn);
                    if ($this->counter === 1) {
                        $pdoEx = new PDOException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found', 40001);
                        $pdoEx->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock'];
                        throw new QueryException('sqlite', 'COMMIT', [], $pdoEx);
                    }
                    return $res;
                }, 3, 0, $connectionName);
            }
        });

        $result = $retryingService->collectSinglePayment($inv->id, [
            'pay_amount' => '2500.00',
            'payment_method' => 'Cash',
            'idempotency_key' => $idempKey,
        ], $this->superAdmin->id);

        $this->assertSame(2, $attempt);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, FeePayment::where('fee_invoice_id', $inv->id)->count());
        $this->assertEquals('Paid', $inv->fresh()->status);

        // And replaying the exact same idempotency_key AFTER the retry committed MUST fail with 422
        $this->expectException(ValidationException::class);
        $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '2500.00',
            'payment_method' => 'Cash',
            'idempotency_key' => $idempKey,
        ], $this->superAdmin->id);
    }

    public function test_bulk_batch_rolls_back_atomically_if_second_invoice_fails_validation(): void
    {
        $student1 = $this->createStudent('STD-BULK-ROLL1');
        $student2 = $this->createStudent('STD-BULK-ROLL2');

        $inv1 = $this->invoiceService->generateForStudent($student1, [], $this->superAdmin->id)['invoice'];
        $inv2 = $this->invoiceService->generateForStudent($student2, [], $this->superAdmin->id)['invoice'];

        $batchKey = 'batch-rollback-atomicity-01';

        try {
            $this->collectionService->collectBulkPayments([
                ['invoice_id' => $inv1->id, 'pay_amount' => '1000.00'],
                ['invoice_id' => $inv2->id, 'pay_amount' => '99999.00'], // Overpayment triggers rollback!
            ], [
                'payment_method' => 'Cash',
                'idempotency_key' => $batchKey,
            ], $this->superAdmin->id);
            $this->fail('Expected ValidationException for invoice 2 overpayment');
        } catch (ValidationException) {
            // Expected rollback
        }

        // Invoice 1 must have ZERO partial payment rows or head updates left behind
        $inv1->refresh();
        $inv2->refresh();
        $this->assertEquals('0.00', $inv1->paid_amount);
        $this->assertEquals('Unpaid', $inv1->status);
        $this->assertEquals(0, FeePayment::whereIn('fee_invoice_id', [$inv1->id, $inv2->id])->count());
        $this->assertEquals(0, AdmissionPaymentHeadAllocation::whereIn('fee_invoice_id', [$inv1->id, $inv2->id])->count());

        // Retrying the same batchKey cleanly now succeeds atomically
        $ok = $this->collectionService->collectBulkPayments([
            ['invoice_id' => $inv1->id, 'pay_amount' => '1000.00'],
            ['invoice_id' => $inv2->id, 'pay_amount' => '2500.00'],
        ], [
            'payment_method' => 'Cash',
            'idempotency_key' => $batchKey,
        ], $this->superAdmin->id);

        $this->assertTrue($ok['success']);
        $this->assertEquals('3500.00', $ok['total_collected']);
    }

    public function test_sequential_double_settlement_blocks_second_attempt_via_db_row_lock_even_without_idempotency_key(): void
    {
        $student = $this->createStudent('STD-DOUBLE-NOKEY');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];

        $this->actingAs($this->superAdmin);
        $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv->id,
            'pay_amount' => '2500.00',
            'payment_method' => 'Cash',
        ])->assertOk();

        // Second submit without idempotency_key blocked cleanly inside locked due check
        $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv->id,
            'pay_amount' => '2500.00',
            'payment_method' => 'Cash',
        ])->assertStatus(422);

        $this->assertEquals(1, FeePayment::where('fee_invoice_id', $inv->id)->count());
    }

    public function test_multi_cycle_collection_and_reversal_keeps_cross_ledger_invariants_exact(): void
    {
        $student = $this->createStudent('STD-CYCLE-INV');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];

        $p1 = $this->collectionService->collectSinglePayment($inv->id, ['pay_amount' => '1000.01', 'payment_method' => 'Cash'], $this->superAdmin->id)['payment'];
        $p2 = $this->collectionService->collectSinglePayment($inv->id, ['pay_amount' => '500.02', 'payment_method' => 'bKash'], $this->superAdmin->id)['payment'];
        $this->reversalService->reversePayment($p1->id, 'Reversing first odd-paisa installment', $this->superAdmin->id);
        $p3 = $this->collectionService->collectSinglePayment($inv->id, ['pay_amount' => '1999.98', 'payment_method' => 'Nagad'], $this->superAdmin->id)['payment'];

        $inv->refresh();
        $this->assertEquals('Paid', $inv->status);
        $this->assertEquals('2500.00', $inv->paid_amount);
        $this->assertEquals('0.00', $inv->due_amount);

        // Sum active FeePayments === invoice paid_amount
        $activePaySum = FeePayment::where('fee_invoice_id', $inv->id)
            ->where('status', 'Active')
            ->get()
            ->reduce(fn ($carry, $p) => bcadd($carry, $p->paid_amount, 2), '0.00');
        $this->assertEquals('2500.00', $activePaySum);

        // Sum active AdmissionPaymentHeadAllocation === invoice paid_amount === sum(head.allocated_paid_amount)
        $activeHeadPaySum = AdmissionPaymentHeadAllocation::where('fee_invoice_id', $inv->id)
            ->where('status', 'Active')
            ->get()
            ->reduce(fn ($carry, $a) => bcadd($carry, $a->allocated_paid_amount, 2), '0.00');
        $this->assertEquals('2500.00', $activeHeadPaySum);

        $headPaidSum = AdmissionInvoiceHeadAllocation::where('fee_invoice_id', $inv->id)
            ->get()
            ->reduce(fn ($carry, $h) => bcadd($carry, $h->allocated_paid_amount, 2), '0.00');
        $this->assertEquals('2500.00', $headPaidSum);
        $this->assertEquals(1, FeeAdjustmentLog::where('fee_invoice_id', $inv->id)->count());
        $this->assertEquals('Active', $p2->fresh()->status);
        $this->assertEquals('Active', $p3->fresh()->status);
        $this->assertEquals('Reversed', $p1->fresh()->status);
    }

    public function test_concurrent_duplicate_idempotency_key_unique_race_converts_cleanly_to_422(): void
    {
        $student = $this->createStudent('STD-RACE-IDEMP');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $idempKey = 'race-idemp-unique-collision-01';
        $scopedKey = hash('sha256', 'collect_single|' . $this->superAdmin->id . '|' . $inv->id . '|' . $idempKey);

        $injected = false;
        DB::listen(function ($query) use (&$injected, $scopedKey, $inv, $idempKey): void {
            $sql = strtolower($query->sql);
            if (!$injected && str_contains($sql, 'fee_idempotency_keys') && str_starts_with($sql, 'select')) {
                $injected = true;
                // Simulate a racing transaction inserting the identical scoped_key right after our empty lock check
                DB::table('fee_idempotency_keys')->insert([
                    'scoped_key' => $scopedKey,
                    'operation' => 'collect_single',
                    'actor_user_id' => $this->superAdmin->id,
                    'target_scope' => (string) $inv->id,
                    'idempotency_key' => $idempKey,
                    'metadata_json' => json_encode(['race' => true]),
                    'expires_at' => now()->addHours(24)->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]);
            }
        });

        try {
            $this->collectionService->collectSinglePayment($inv->id, [
                'pay_amount' => '1000.00',
                'payment_method' => 'Cash',
                'idempotency_key' => $idempKey,
            ], $this->superAdmin->id);
            $this->fail('Expected ValidationException on unique constraint idempotency race');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('idempotency_key', $e->errors());
        }

        // Invoice payment MUST have rolled back cleanly without partial records
        $this->assertEquals(0, FeePayment::where('fee_invoice_id', $inv->id)->count());
        $this->assertEquals('0.00', $inv->fresh()->paid_amount);
    }

    public function test_daily_receipt_sequence_avoids_insert_ignore_and_recovers_from_missing_row_creation_race(): void
    {
        $student1 = $this->createStudent('STD-SEQ-01');
        $student2 = $this->createStudent('STD-SEQ-02');
        $student3 = $this->createStudent('STD-SEQ-03');

        $inv1 = $this->invoiceService->generateForStudent($student1, [], $this->superAdmin->id)['invoice'];
        $inv2 = $this->invoiceService->generateForStudent($student2, [], $this->superAdmin->id)['invoice'];
        $inv3 = $this->invoiceService->generateForStudent($student3, [], $this->superAdmin->id)['invoice'];

        $observedInsertIgnore = false;
        $raceInjected = false;

        DB::listen(function ($query) use (&$observedInsertIgnore, &$raceInjected): void {
            $sql = strtolower($query->sql);
            if (str_contains($sql, 'fee_daily_sequences') && str_contains($sql, 'ignore')) {
                $observedInsertIgnore = true;
            }

            // Simulate concurrent first-time initialization race on missing day row '20261201'
            if (
                !$raceInjected
                && str_contains($sql, 'fee_daily_sequences')
                && str_starts_with($sql, 'select')
                && in_array('receipt_no:REC:20261201', $query->bindings, true)
            ) {
                $raceInjected = true;
                DB::table('fee_daily_sequences')->insert([
                    'scope_key' => 'receipt_no:REC:20261201',
                    'last_seq' => 5,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]);
            }
        });

        // 1. Cold-start creation race: recovers cleanly from SQLSTATE 23000 via locking read and picks #0006
        $r1 = $this->collectionService->collectSinglePayment($inv1->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-12-01',
        ], $this->superAdmin->id);
        $this->assertEquals('REC-20261201-0006', $r1['receipt_no']);

        // 2. General Laravel cache flush must NOT reset or corrupt fee_daily_sequences
        Cache::flush();
        DB::table('cache')->delete();

        // 3. Steady-state subsequent collections increment deterministically via direct FOR UPDATE
        $r2 = $this->collectionService->collectSinglePayment($inv2->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-12-01',
        ], $this->superAdmin->id);
        $r3 = $this->collectionService->collectSinglePayment($inv3->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-12-01',
        ], $this->superAdmin->id);

        $this->assertFalse($observedInsertIgnore, 'INSERT IGNORE pattern must never be used on fee_daily_sequences.');
        $this->assertEquals('REC-20261201-0007', $r2['receipt_no']);
        $this->assertEquals('REC-20261201-0008', $r3['receipt_no']);
    }

    public function test_continuous_adjustment_and_inline_custom_fee_acquire_all_tier2_locks_before_tier3(): void
    {
        $student = $this->createStudent('STD-ADJ-LOCK');

        // Also give student an Admission invoice to prove Admission snapshots are untouched
        $admInv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];

        $monthlySetup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'fee_category_id' => $this->tuitionCat->id,
            'fee_month' => 'May',
            'amount' => 1000.00,
            'user_id' => $this->superAdmin->id,
        ]);

        $invMay = FeeInvoice::create([
            'invoice_no' => 'INV-202605-3001',
            'student_id' => $student->id,
            'fee_setup_id' => $monthlySetup->id,
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 1000.00,
            'status' => 'Unpaid',
            'due_date' => '2026-05-10',
            'user_id' => $this->superAdmin->id,
        ]);

        $invJune = FeeInvoice::create([
            'invoice_no' => 'INV-202606-3001',
            'student_id' => $student->id,
            'fee_setup_id' => $monthlySetup->id,
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 1000.00,
            'status' => 'Unpaid',
            'due_date' => '2026-06-10',
            'user_id' => $this->superAdmin->id,
        ]);

        $invJulyPartial = FeeInvoice::create([
            'invoice_no' => 'INV-202607-3001',
            'student_id' => $student->id,
            'fee_setup_id' => $monthlySetup->id,
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 200.00,
            'due_amount' => 800.00,
            'status' => 'Partial',
            'due_date' => '2026-07-10',
            'user_id' => $this->superAdmin->id,
        ]);
        FeePayment::create([
            'receipt_no' => 'REC-20260710-3001',
            'fee_invoice_id' => $invJulyPartial->id,
            'student_id' => $student->id,
            'paid_amount' => 200.00,
            'payment_date' => '2026-07-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->superAdmin->id,
        ]);

        $trace = [];
        $recording = false;
        DB::listen(function ($query) use (&$trace, &$recording): void {
            if (!$recording) {
                return;
            }
            $sql = strtolower($query->sql);
            if (!str_starts_with($sql, 'select') || str_contains($sql, 'pragma_') || str_contains($sql, 'sqlite_master')) {
                return;
            }
            // Capture transactional row-locking queries (`ORDER BY id ASC`) across Tiers 1-3,
            // excluding Laravel's `exists` count(*) validation and the read-only `student_id` routing lookup.
            if (!str_contains($sql, 'order by')) {
                return;
            }
            if (str_contains($sql, 'students')) {
                $trace[] = ['tier' => 1, 'sql' => $sql];
            } elseif (str_contains($sql, 'fee_invoices')) {
                $trace[] = ['tier' => 2, 'sql' => $sql];
            } elseif (str_contains($sql, 'fee_payments')) {
                $trace[] = ['tier' => 3, 'sql' => $sql];
            }
        });

        $this->actingAs($this->superAdmin);

        // 1. Trace adjustInvoiceFee in continuous mode
        $trace = [];
        $recording = true;
        $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invMay->id,
            'mode' => 'set_amount',
            'amount' => 850.00,
            'scope' => 'continuous',
            'reason' => 'Verify strict Tier 1->2->3 lock ordering',
        ])->assertOk()->assertJsonPath('success', true);
        $recording = false;

        $this->assertStrictTier123Sequence($trace);

        // 2. Trace updateCustomFeeAjax inline custom fee update
        $trace = [];
        $recording = true;
        $this->postJson(route('fees.custom_fee.update_inline'), [
            'invoice_id' => $invMay->id,
            'amount' => 800.00,
        ])->assertOk()->assertJsonPath('success', true);
        $recording = false;

        $this->assertStrictTier123Sequence($trace);

        // Admission invoice remained completely immune
        $this->assertEquals('2500.00', $admInv->fresh()->net_amount);
        // Unpaid invoices updated to 800.00
        $this->assertEquals(800.00, (float) $invMay->fresh()->net_amount);
        $this->assertEquals(800.00, (float) $invJune->fresh()->net_amount);
    }

    /**
     * Assert locking tiers strictly obey Tier 1 -> Tier 2 -> Tier 3 with zero Tier regressions.
     */
    private function assertStrictTier123Sequence(array $trace): void
    {
        $seenTier1 = false;
        $seenTier2 = false;
        $seenTier3 = false;

        foreach ($trace as $entry) {
            if ($entry['tier'] === 1) {
                $seenTier1 = true;
                $this->assertFalse($seenTier2, 'Tier 1 Student lock must precede Tier 2 FeeInvoice locks.');
                $this->assertFalse($seenTier3, 'Tier 1 Student lock must precede Tier 3 FeePayment locks.');
            } elseif ($entry['tier'] === 2) {
                $seenTier2 = true;
                $this->assertTrue($seenTier1, 'Tier 1 Student lock must execute before Tier 2 FeeInvoice lock.');
                $this->assertFalse($seenTier3, 'All Tier 2 FeeInvoice locks must precede Tier 3 FeePayment locks.');
            } elseif ($entry['tier'] === 3) {
                $seenTier3 = true;
                $this->assertTrue($seenTier2, 'Tier 2 FeeInvoice locks must precede Tier 3 FeePayment locks.');
            }
        }

        $this->assertTrue($seenTier1, 'Expected Tier 1 Student lock query in trace.');
        $this->assertTrue($seenTier2, 'Expected Tier 2 FeeInvoice lock query in trace.');
        $this->assertTrue($seenTier3, 'Expected Tier 3 FeePayment lock query in trace.');
    }
}
