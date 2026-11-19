<?php

namespace Tests\Feature;

use App\Models\AdmissionFeeStructure;
use App\Models\AdmissionFeeStructureItem;
use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionFeeStructureService;
use App\Services\FinancialTransactionRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class AdmissionFeeStructureTest extends TestCase
{
    use RefreshDatabase;

    protected AdmissionFeeStructureService $service;
    protected FinancialTransactionRunner $runner;
    protected User $admin;
    protected Branch $branchA;
    protected Branch $branchB;
    protected SessionYear $session2026;
    protected SessionYear $session2027;
    protected Classes $classTwo;
    protected Classes $classOne;
    protected Section $sectionA;
    protected FeeCategory $admissionCat;
    protected FeeCategory $sessionCat;
    protected FeeCategory $monthlyCat;
    protected FeeCategory $formCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runner = app(FinancialTransactionRunner::class);
        $this->service = app(AdmissionFeeStructureService::class);

        $this->admin = User::create([
            'name' => 'Admin Actor',
            'email' => 'structure_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);

        $this->branchA = Branch::create(['branch_name' => 'Campus Alpha', 'user_id' => $this->admin->id]);
        $this->branchB = Branch::create(['branch_name' => 'Campus Beta', 'user_id' => $this->admin->id]);

        $this->session2026 = SessionYear::create(['session_name' => '2026', 'user_id' => $this->admin->id]);
        $this->session2027 = SessionYear::create(['session_name' => '2027', 'user_id' => $this->admin->id]);

        // Create out of academic order to verify Play..Ten sorting on listStructures()
        $this->classTwo = Classes::create(['class_name' => 'Two', 'user_id' => $this->admin->id]);
        $this->classOne = Classes::create(['class_name' => 'One', 'user_id' => $this->admin->id]);

        $this->sectionA = Section::create([
            'section_name' => 'A',
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'user_id' => $this->admin->id,
        ]);

        $this->admissionCat = FeeCategory::create(['name' => 'Admission Fee', 'status' => 'Active', 'user_id' => $this->admin->id]);
        $this->sessionCat = FeeCategory::create(['name' => 'Session Fee', 'status' => 'Active', 'user_id' => $this->admin->id]);
        $this->monthlyCat = FeeCategory::create(['name' => 'Monthly Fee', 'status' => 'Active', 'user_id' => $this->admin->id]);
        $this->formCat = FeeCategory::create(['name' => 'Form Fill up Fee', 'status' => 'Active', 'user_id' => $this->admin->id]);
    }

    public function test_creates_multi_head_admission_structure_with_exact_bcmath_sum(): void
    {
        $structure = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Class Two Standard Admission 2026',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 1,
            'notes' => 'Includes January tuition head',
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Form & Reg',
                    'amount' => '2500.25',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->sessionCat->id,
                    'head_name' => 'Annual Session Charge',
                    'amount' => '1500.30',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
                [
                    'fee_category_id' => $this->monthlyCat->id,
                    'head_name' => 'January Tuition Fee',
                    'amount' => '1250',
                    'is_discountable' => false,
                    'is_tuition_head' => true,
                    'sort_order' => 30,
                ],
            ],
        ], $this->admin->id);

        $this->assertSame(1, $structure->version);
        $this->assertSame('Active', $structure->status);
        $this->assertSame('5250.55', (string) $structure->total_amount);
        $this->assertTrue($structure->includes_monthly_tuition);
        $this->assertSame(1, $structure->tuition_month_number);
        $this->assertCount(3, $structure->items);

        $resolved = $this->service->resolveActiveStructure($this->branchA->id, $this->classTwo->id, $this->session2026->id);
        $this->assertNotNull($resolved);
        $this->assertSame($structure->id, $resolved->id);
    }

    public function test_prevents_duplicate_active_structure_in_same_branch_class_session_scope(): void
    {
        $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Active V1',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '3000.00'],
            ],
        ], $this->admin->id);

        // Creating an Inactive draft version in the same scope succeeds cleanly (v2)
        $inactiveV2 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Draft V2',
            'status' => 'Inactive',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '3500.00'],
            ],
        ], $this->admin->id);
        $this->assertSame(2, $inactiveV2->version);
        $this->assertSame('Inactive', $inactiveV2->status);

        // Creating a second Active structure in the exact same scope must be rejected
        $this->expectException(ValidationException::class);
        $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Duplicate Active V3',
            'status' => 'Active',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '4000.00'],
            ],
        ], $this->admin->id);
    }

    public function test_enforces_branch_class_and_session_scope_isolation(): void
    {
        $basePayload = [
            'structure_name' => 'Scoped Package',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2000.00'],
            ],
        ];

        $s1 = $this->service->createStructure(array_merge($basePayload, [
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
        ]), $this->admin->id);

        $s2 = $this->service->createStructure(array_merge($basePayload, [
            'branch_id' => $this->branchB->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
        ]), $this->admin->id);

        $s3 = $this->service->createStructure(array_merge($basePayload, [
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
        ]), $this->admin->id);

        $s4 = $this->service->createStructure(array_merge($basePayload, [
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2027->id,
        ]), $this->admin->id);

        $this->assertSame(1, $s1->version);
        $this->assertSame(1, $s2->version);
        $this->assertSame(1, $s3->version);
        $this->assertSame(1, $s4->version);

        // Verify Play..Ten academic order on listStructures(): One comes before Two
        $listed = $this->service->listStructures();
        $classNamesInOrder = $listed->map(fn ($s) => $s->schoolClass->class_name)->all();
        $firstOneIdx = array_search('One', $classNamesInOrder, true);
        $firstTwoIdx = array_search('Two', $classNamesInOrder, true);
        $this->assertLessThan($firstTwoIdx, $firstOneIdx);
    }

    public function test_updating_structure_creates_new_version_and_protects_historical_snapshots(): void
    {
        $v1 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Initial Package V1',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '3000.00', 'is_discountable' => true],
                ['fee_category_id' => $this->sessionCat->id, 'head_name' => 'Lab Fee', 'amount' => '1000.00', 'is_discountable' => false],
            ],
        ], $this->admin->id);

        $v1FirstItem = $v1->items->first();

        // Create historical Student + FeeInvoice + AdmissionInvoiceHeadAllocation referencing V1
        $student = $this->createStudent('HIS-2026-001');
        $historicalInvoice = FeeInvoice::create([
            'invoice_no' => 'INV-ADM-S1-202601-1',
            'student_id' => $student->id,
            'branch_id' => $this->branchA->id,
            'session_year_id' => $this->session2026->id,
            'branch_name_snapshot' => $this->branchA->branch_name,
            'fee_setup_id' => null,
            'admission_fee_structure_id' => $v1->id,
            'amount' => '4000.00',
            'discount' => '0.00',
            'net_amount' => '4000.00',
            'paid_amount' => '0.00',
            'due_amount' => '4000.00',
            'status' => 'Unpaid',
            'due_date' => '2026-01-10',
            'user_id' => $this->admin->id,
        ]);

        AdmissionInvoiceHeadAllocation::create([
            'fee_invoice_id' => $historicalInvoice->id,
            'student_id' => $student->id,
            'branch_id' => $this->branchA->id,
            'admission_fee_structure_item_id' => $v1FirstItem->id,
            'fee_category_id' => $this->admissionCat->id,
            'fee_category_name_snapshot' => 'Admission Fee',
            'head_name_snapshot' => 'Admission Base',
            'branch_name_snapshot' => $this->branchA->branch_name,
            'amount' => '3000.00',
            'discount' => '0.00',
            'net_amount' => '3000.00',
            'allocated_paid_amount' => '0.00',
            'due_amount' => '3000.00',
            'is_discountable' => true,
            'is_tuition_head' => false,
            'sort_order' => 10,
        ]);

        // Now update structure with versioning -> creates V2 Active, archives V1
        $v2 = $this->service->updateStructureWithVersioning($v1->id, [
            'structure_name' => 'Revised Package V2',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 2,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Revised', 'amount' => '4500.00', 'is_discountable' => true],
                ['fee_category_id' => $this->monthlyCat->id, 'head_name' => 'February Tuition', 'amount' => '1500.00', 'is_discountable' => false, 'is_tuition_head' => true],
            ],
        ], $this->admin->id);

        $this->assertSame(2, $v2->version);
        $this->assertSame('Active', $v2->status);
        $this->assertSame('6000.00', (string) $v2->total_amount);

        // Reload V1 and assert zero structural mutation occurred
        $v1Reloaded = $this->service->findStructure($v1->id);
        $this->assertSame('Archived', $v1Reloaded->status);
        $this->assertSame('Initial Package V1', $v1Reloaded->structure_name);
        $this->assertSame('4000.00', (string) $v1Reloaded->total_amount);
        $this->assertFalse($v1Reloaded->includes_monthly_tuition);
        $this->assertCount(2, $v1Reloaded->items);
        $this->assertSame('3000.00', (string) $v1Reloaded->items->first()->amount);

        // Historical invoice still cleanly binds to V1 and original snapshot
        $historicalInvoice->refresh();
        $this->assertSame($v1->id, (int) $historicalInvoice->admission_fee_structure_id);
        $this->assertSame(0, bccomp('4000.00', (string) $historicalInvoice->net_amount, 2));

        // Deleting V1 referenced by historical invoice must be forbidden
        $this->expectException(ValidationException::class);
        $this->service->deleteUnusedDraftStructure($v1->id);
    }

    public function test_activate_and_deactivate_transitions_and_unused_draft_deletion(): void
    {
        $v1 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Version 1',
            'status' => 'Active',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2500.00'],
            ],
        ], $this->admin->id);

        $v2 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Version 2 Draft',
            'status' => 'Inactive',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2800.00'],
            ],
        ], $this->admin->id);

        // Activating V2 with deactivateExisting=false rejects collision cleanly
        try {
            $this->service->activateStructure($v2->id, $this->admin->id, deactivateExisting: false);
            $this->fail('Expected ValidationException when deactivateExisting=false.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // Activating V2 with deactivateExisting=true cleanly switches active structure
        $activatedV2 = $this->service->activateStructure($v2->id, $this->admin->id, deactivateExisting: true);
        $this->assertSame('Active', $activatedV2->status);
        $this->assertSame('Archived', $v1->fresh()->status);

        // Deactivate V2 and delete unreferenced V1 draft safely
        $deactivatedV2 = $this->service->deactivateStructure($v2->id, $this->admin->id);
        $this->assertSame('Inactive', $deactivatedV2->status);

        $this->service->deleteUnusedDraftStructure($v1->id);
        $this->assertDatabaseMissing('admission_fee_structures', ['id' => $v1->id]);
    }

    public function test_validates_monetary_inputs_tuition_consistency_and_unique_heads(): void
    {
        // Reject PHP float amount
        try {
            $this->service->validateMonetaryAmount(1250.5, 'amount');
            $this->fail('PHP float should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Reject includes_monthly_tuition=true without matching tuition head
        try {
            $this->service->createStructure([
                'branch_id' => $this->branchA->id,
                'class_id' => $this->classTwo->id,
                'session_year_id' => $this->session2026->id,
                'structure_name' => 'Broken Tuition Structure',
                'includes_monthly_tuition' => true,
                'tuition_month_number' => 1,
                'items' => [
                    ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission', 'amount' => '2000.00', 'is_tuition_head' => false],
                ],
            ], $this->admin->id);
            $this->fail('Expected validation failure for missing tuition head.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }

        // Reject duplicate head_name under same category (case-insensitive)
        $this->expectException(ValidationException::class);
        $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Duplicate Head Check',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Form Fee', 'amount' => '500.00'],
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'form fee', 'amount' => '500.00'],
            ],
        ], $this->admin->id);
    }

    public function test_transaction_composition_blocks_nested_tx_even_under_refresh_database_and_allows_outer_atomicity(): void
    {
        // Calling *WithinTransaction without outer application transaction must throw LogicException
        try {
            $this->service->createStructureWithinTransaction([
                'branch_id' => $this->branchA->id,
                'class_id' => $this->classTwo->id,
                'session_year_id' => $this->session2026->id,
                'structure_name' => 'Out of Tx',
                'includes_monthly_tuition' => false,
                'items' => [
                    ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Base', 'amount' => '1000.00'],
                ],
            ], $this->admin->id);
            $this->fail('Expected LogicException outside transaction.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('active database transaction', $e->getMessage());
        }

        // Calling runWithDeadlockRetry inside manual DB::beginTransaction() must still reject nested tx!
        DB::beginTransaction();
        try {
            $this->service->createStructure([
                'branch_id' => $this->branchA->id,
                'class_id' => $this->classTwo->id,
                'session_year_id' => $this->session2026->id,
                'structure_name' => 'Nested Rejected',
                'includes_monthly_tuition' => false,
                'items' => [
                    ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Base', 'amount' => '1000.00'],
                ],
            ], $this->admin->id);
            $this->fail('Expected LogicException for nested transaction.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('Nested transactions are strictly prohibited', $e->getMessage());
        } finally {
            DB::rollBack();
        }

        // Composing two service calls inside single outer runner rolls back atomically on failure
        try {
            $this->runner->runWithDeadlockRetry(function (): void {
                $this->service->createStructureWithinTransaction([
                    'branch_id' => $this->branchA->id,
                    'class_id' => $this->classTwo->id,
                    'session_year_id' => $this->session2026->id,
                    'structure_name' => 'Atomic Rolled Back Structure',
                    'includes_monthly_tuition' => false,
                    'items' => [
                        ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Base', 'amount' => '1000.00'],
                    ],
                ], $this->admin->id);

                // Force failure in second step within the same transaction
                throw new \RuntimeException('Simulated failure in composed workflow');
            });
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure in composed workflow', $e->getMessage());
        }

        $this->assertDatabaseMissing('admission_fee_structures', [
            'structure_name' => 'Atomic Rolled Back Structure',
        ]);
    }

    public function test_rejects_scope_change_during_versioning_total_mismatch_and_supports_filtered_listing(): void
    {
        $v1 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Filterable Package V1',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2200.00'],
            ],
        ], $this->admin->id);

        // Reject declared total_amount mismatch
        try {
            $this->service->createStructure([
                'branch_id' => $this->branchB->id,
                'class_id' => $this->classOne->id,
                'session_year_id' => $this->session2026->id,
                'structure_name' => 'Mismatched Total Package',
                'total_amount' => '2500.00',
                'includes_monthly_tuition' => false,
                'items' => [
                    ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2200.00'],
                ],
            ], $this->admin->id);
            $this->fail('Expected ValidationException on total mismatch.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('total_amount', $e->errors());
        }

        // Reject changing branch/class/session scope during versioning
        try {
            $this->service->updateStructureWithVersioning($v1->id, [
                'branch_id' => $this->branchB->id,
                'structure_name' => 'Attempt Scope Change',
                'includes_monthly_tuition' => false,
                'items' => [
                    ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2200.00'],
                ],
            ], $this->admin->id);
            $this->fail('Expected ValidationException on scope mutation.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('branch_id', $e->errors());
        }

        // Filtered listing
        $filtered = $this->service->listStructures([
            'branch_id' => $this->branchA->id,
            'status' => 'Active',
            'search' => 'Filterable',
        ]);
        $this->assertCount(1, $filtered);
        $this->assertSame($v1->id, $filtered->first()->id);

        // Reject DECIMAL(10,2) overflow cleanly
        try {
            $this->service->validateMonetaryAmount('100000000.00', 'amount');
            $this->fail('Expected ValidationException on DECIMAL(10,2) overflow.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Versioning an Inactive draft into Active when another Active exists requires replace_active=true
        $draftV2 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Draft V2',
            'status' => 'Inactive',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2300.00'],
            ],
        ], $this->admin->id);

        try {
            $this->service->updateStructureWithVersioning($draftV2->id, [
                'structure_name' => 'Promoted V3 Blocked',
                'status' => 'Active',
                'includes_monthly_tuition' => false,
                'items' => [
                    ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2400.00'],
                ],
            ], $this->admin->id);
            $this->fail('Expected ValidationException without replace_active.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $promotedV3 = $this->service->updateStructureWithVersioning($draftV2->id, [
            'structure_name' => 'Promoted V3 Explicit',
            'status' => 'Active',
            'replace_active' => true,
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Base', 'amount' => '2400.00'],
            ],
        ], $this->admin->id);
        $this->assertSame(3, $promotedV3->version);
        $this->assertSame('Active', $promotedV3->status);
        $this->assertSame('Archived', $v1->fresh()->status);
    }

    public function test_all_time_default_structure_applies_to_all_sessions_allows_override_and_preserves_historical_invoices(): void
    {
        $session2028 = SessionYear::create(['session_name' => '2028', 'user_id' => $this->admin->id]);
        $invoiceService = app(\App\Services\AdmissionInvoiceService::class);

        // 1. Create active All-Time structure (session_year_id = null) for Branch A + Class Two
        $allTimeV1 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => null,
            'structure_name' => 'Class Two Admission Fee Structure',
            'status' => 'Active',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Fee', 'amount' => '2000.00'],
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Development Fee', 'amount' => '1000.00'],
            ],
        ], $this->admin->id);

        $this->assertNull($allTimeV1->session_year_id);
        $this->assertSame('Active', $allTimeV1->status);
        $this->assertSame(1, $allTimeV1->version);

        // 2. Duplicate active All-Time structure in same Branch + Class is rejected
        try {
            $this->service->createStructure([
                'branch_id' => $this->branchA->id,
                'class_id' => $this->classTwo->id,
                'session_year_id' => null,
                'structure_name' => 'Duplicate All-Time Package',
                'status' => 'Active',
                'includes_monthly_tuition' => false,
                'items' => [
                    ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Fee', 'amount' => '3100.00'],
                ],
            ], $this->admin->id);
            $this->fail('Expected ValidationException on duplicate active All-Time structure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // 3. All sessions (2026, 2027, 2028) resolve automatically to All-Time v1 when no session override exists
        $this->assertSame($allTimeV1->id, $this->service->resolveActiveStructure($this->branchA->id, $this->classTwo->id, $this->session2026->id)?->id);
        $this->assertSame($allTimeV1->id, $this->service->resolveActiveStructure($this->branchA->id, $this->classTwo->id, $this->session2027->id)?->id);
        $this->assertSame($allTimeV1->id, $this->service->resolveActiveStructure($this->branchA->id, $this->classTwo->id, $session2028->id)?->id);

        // 4. Admit a 2026 student -> invoice snapshots v1 (3000.00)
        $stu2026 = $this->createStudent('STD-ALLTIME-2026', ['session_year_id' => $this->session2026->id]);
        $inv2026 = $invoiceService->generateForStudent($stu2026, [], $this->admin->id)['invoice'];
        $this->assertSame($allTimeV1->id, (int) $inv2026->admission_fee_structure_id);
        $this->assertSame(0, bccomp('3000.00', (string) $inv2026->net_amount, 2));

        // 5. Edit All-Time structure -> publishes v2 (3500.00) without affecting historical 2026 invoice
        $allTimeV2 = $this->service->updateStructureWithVersioning($allTimeV1->id, [
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => null,
            'structure_name' => 'Class Two Admission Fee Structure',
            'status' => 'Active',
            'replace_active' => true,
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Fee', 'amount' => '2500.00'],
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Development Fee', 'amount' => '1000.00'],
            ],
        ], $this->admin->id);

        $this->assertSame(2, $allTimeV2->version);
        $this->assertNull($allTimeV2->session_year_id);
        $this->assertSame('Archived', $allTimeV1->fresh()->status);

        // Past invoice stays untouched at 3000.00 under v1
        $inv2026Fresh = $inv2026->fresh();
        $this->assertSame($allTimeV1->id, (int) $inv2026Fresh->admission_fee_structure_id);
        $this->assertSame(0, bccomp('3000.00', (string) $inv2026Fresh->net_amount, 2));

        // New student in future session 2028 automatically gets All-Time v2 (3500.00)
        $stu2028 = $this->createStudent('STD-ALLTIME-2028', ['session_year_id' => $session2028->id]);
        $inv2028 = $invoiceService->generateForStudent($stu2028, [], $this->admin->id)['invoice'];
        $this->assertSame($allTimeV2->id, (int) $inv2028->admission_fee_structure_id);
        $this->assertSame(0, bccomp('3500.00', (string) $inv2028->net_amount, 2));

        // 6. Optional session-specific override for 2027 takes precedence over All-Time v2 for 2027 only
        $override2027 = $this->service->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2027->id,
            'structure_name' => 'Class Two 2027 Special',
            'status' => 'Active',
            'includes_monthly_tuition' => false,
            'items' => [
                ['fee_category_id' => $this->admissionCat->id, 'head_name' => 'Admission Fee', 'amount' => '3800.00'],
            ],
        ], $this->admin->id);

        $this->assertSame($override2027->id, $this->service->resolveActiveStructure($this->branchA->id, $this->classTwo->id, $this->session2027->id)?->id);
        $this->assertSame($allTimeV2->id, $this->service->resolveActiveStructure($this->branchA->id, $this->classTwo->id, $this->session2026->id)?->id);
        $this->assertSame($allTimeV2->id, $this->service->resolveActiveStructure($this->branchA->id, $this->classTwo->id, $session2028->id)?->id);

        // 7. Filtering by 'all_time' lists only NULL-session structures
        $allTimeListed = $this->service->listStructures(['session_year_id' => 'all_time']);
        $this->assertTrue($allTimeListed->every(fn ($s) => $s->session_year_id === null));
        $this->assertTrue($allTimeListed->contains('id', $allTimeV2->id));
        $this->assertFalse($allTimeListed->contains('id', $override2027->id));
    }

    private function createStudent(string $identity, array $overrides = []): Student
    {
        return Student::create(array_merge([
            'user_id' => $this->admin->id,
            'student_name' => 'Historical Student',
            'student_identity' => $identity,
            'roll_number' => '10',
            'dob' => '2016-01-01',
            'gender' => 'Male',
            'class_id' => $this->classTwo->id,
            'section_id' => $this->sectionA->id,
            'branch_id' => $this->branchA->id,
            'session_year_id' => $this->session2026->id,
            'father_name' => 'Father',
            'father_mobile' => '01700000001',
            'mother_name' => 'Mother',
            'mother_mobile' => '01700000002',
            'guardian_name' => 'Guardian',
            'guardian_mobile' => '01700000003',
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
        ], $overrides));
    }
}
