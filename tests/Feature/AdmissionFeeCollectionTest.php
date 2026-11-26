<?php

namespace Tests\Feature;

use App\Models\AdmissionFeeStructure;
use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeSetup;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\StudentCustomFee;
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionFeeStructureService;
use App\Services\AdmissionInvoiceService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdmissionFeeCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminUser;
    protected User $editorUser;
    protected User $teacherUser;
    protected User $regularUser;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classOne;
    protected Section $sectionA;
    protected FeeCategory $admissionCat;
    protected FeeCategory $sessionCat;
    protected FeeCategory $tuitionCat;
    protected AdmissionFeeStructure $structure;
    protected AdmissionCollectionService $collectionService;
    protected AdmissionInvoiceService $invoiceService;
    protected AdmissionFeeStructureService $structureService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collectionService = app(AdmissionCollectionService::class);
        $this->invoiceService = app(AdmissionInvoiceService::class);
        $this->structureService = app(AdmissionFeeStructureService::class);

        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $teacherRole = Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'Teacher']);
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'col_super@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->adminUser = User::create([
            'name' => 'Admin Cashier',
            'email' => 'col_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->editorUser = User::create([
            'name' => 'Editor User',
            'email' => 'col_editor@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->editorUser->assignRole($editorRole);

        $this->teacherUser = User::create([
            'name' => 'Teacher User',
            'email' => 'col_teacher@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->teacherUser->assignRole($teacherRole);

        $this->regularUser = User::create([
            'name' => 'Normal User',
            'email' => 'col_user@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->regularUser->assignRole($userRole);

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
        $this->sessionCat = FeeCategory::create(['name' => 'Session Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $this->tuitionCat = FeeCategory::create(['name' => 'Monthly Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);

        $this->structure = $this->structureService->createStructure([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'structure_name' => 'Class One Admission Package',
            'status' => 'Active',
            'total_amount' => '3000.00',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 1,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Charge',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->sessionCat->id,
                    'head_name' => 'Development & ID',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
                [
                    'fee_category_id' => $this->tuitionCat->id,
                    'head_name' => 'January Tuition',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => true,
                    'sort_order' => 30,
                ],
            ],
        ], $this->superAdmin->id);
    }

    protected function createStudent(string $identity = 'STD-4A-001'): Student
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

    protected function createAdmissionInvoiceForStudent(Student $student, array $discountOptions = []): FeeInvoice
    {
        $res = $this->invoiceService->generateForStudent($student, $discountOptions, $this->superAdmin->id);
        return $res['invoice'];
    }

    public function test_explicit_head_wise_partial_collection_updates_heads_and_omits_zero_allocations(): void
    {
        $student = $this->createStudent('STD-4A-EXPLICIT');
        $invoice = $this->createAdmissionInvoiceForStudent($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->actingAs($this->adminUser);
        $response = $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $invoice->id,
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1000.00',
                $heads[1]->id => '500.00',
                $heads[2]->id => '0.00',
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $invoice->refresh();
        $this->assertEquals('Partial', $invoice->status);
        $this->assertEquals('1500.00', $invoice->paid_amount);
        $this->assertEquals('1500.00', $invoice->due_amount);

        $payment = FeePayment::where('fee_invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals('Active', $payment->status);
        $this->assertEquals($this->branch->id, $payment->branch_id);
        $this->assertStringStartsWith('REC-20261118-', $payment->receipt_no);

        // Only the 2 heads with > 0.00 allocations get rows (`chk_apha_positive`)
        $paymentHeads = AdmissionPaymentHeadAllocation::where('fee_payment_id', $payment->id)
            ->orderBy('admission_invoice_head_allocation_id')
            ->get();
        $this->assertCount(2, $paymentHeads);
        $this->assertEquals('1000.00', $paymentHeads[0]->allocated_paid_amount);
        $this->assertEquals('500.00', $paymentHeads[1]->allocated_paid_amount);

        $heads[0]->refresh();
        $heads[1]->refresh();
        $heads[2]->refresh();
        $this->assertEquals('1000.00', $heads[0]->allocated_paid_amount);
        $this->assertEquals('0.00', $heads[0]->due_amount);
        $this->assertEquals('500.00', $heads[1]->allocated_paid_amount);
        $this->assertEquals('500.00', $heads[1]->due_amount);
        $this->assertEquals('0.00', $heads[2]->allocated_paid_amount);
        $this->assertEquals('1000.00', $heads[2]->due_amount);
    }

    public function test_lump_sum_proportional_split_uses_exact_bcmod_remainder_and_settles_on_second_installment(): void
    {
        $student = $this->createStudent('STD-4A-PROP');
        $invoice = $this->createAdmissionInvoiceForStudent($student);

        // First partial payment: 1000.00 across three equal 1000.00 due heads => 333.34, 333.33, 333.33
        $res1 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
        ], $this->superAdmin->id);

        $this->assertTrue($res1['success']);
        $heads = $res1['head_allocations'];
        $this->assertEquals('333.34', $heads[0]->allocated_paid_amount);
        $this->assertEquals('666.66', $heads[0]->due_amount);
        $this->assertEquals('333.33', $heads[1]->allocated_paid_amount);
        $this->assertEquals('666.67', $heads[1]->due_amount);
        $this->assertEquals('333.33', $heads[2]->allocated_paid_amount);
        $this->assertEquals('666.67', $heads[2]->due_amount);

        // Second settlement payment: remaining 2000.00
        $res2 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '2000.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-19',
        ], $this->adminUser->id);

        $invoice->refresh();
        $this->assertEquals('Paid', $invoice->status);
        $this->assertEquals('3000.00', $invoice->paid_amount);
        $this->assertEquals('0.00', $invoice->due_amount);

        foreach ($res2['head_allocations'] as $head) {
            $this->assertEquals('1000.00', $head->allocated_paid_amount);
            $this->assertEquals('0.00', $head->due_amount);
        }
    }

    public function test_tiny_single_paisa_partial_payment_respects_positive_allocation_constraint(): void
    {
        $student = $this->createStudent('STD-4A-PAISA');
        $invoice = $this->createAdmissionInvoiceForStudent($student);

        $res = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '0.01',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id);

        $this->assertEquals('0.01', $res['payment']->paid_amount);
        $this->assertCount(1, $res['payment_allocations']);
        $this->assertEquals('0.01', $res['payment_allocations']->first()->allocated_paid_amount);
        $this->assertEquals('2999.99', $res['invoice']->due_amount);
    }

    public function test_overpayment_foreign_head_and_sum_mismatch_are_rejected_with_422(): void
    {
        $studentA = $this->createStudent('STD-4A-OV1');
        $studentB = $this->createStudent('STD-4A-OV2');
        $invoiceA = $this->createAdmissionInvoiceForStudent($studentA);
        $invoiceB = $this->createAdmissionInvoiceForStudent($studentB);

        $headsA = $invoiceA->admissionHeadAllocations()->orderBy('sort_order')->get();
        $headsB = $invoiceB->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->actingAs($this->adminUser);

        // 1. Per-head overpayment (1000.01 on 1000.00 head)
        $resPerHead = $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $invoiceA->id,
            'payment_method' => 'Cash',
            'head_allocations' => [$headsA[0]->id => '1000.01'],
        ]);
        $resPerHead->assertStatus(422);

        // 2. Total invoice overpayment
        $resTotal = $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $invoiceA->id,
            'pay_amount' => '3000.01',
            'payment_method' => 'Cash',
        ]);
        $resTotal->assertStatus(422);

        // 3. Foreign head ID from student B's invoice
        $resForeign = $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $invoiceA->id,
            'payment_method' => 'Cash',
            'head_allocations' => [$headsB[0]->id => '100.00'],
        ]);
        $resForeign->assertStatus(422);

        // 4. Declared pay_amount mismatches head sum
        $resMismatch = $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $invoiceA->id,
            'pay_amount' => '500.00',
            'payment_method' => 'Cash',
            'head_allocations' => [$headsA[0]->id => '400.00'],
        ]);
        $resMismatch->assertStatus(422);

        $this->assertEquals(0, FeePayment::where('fee_invoice_id', $invoiceA->id)->count());
    }

    public function test_zero_payable_waived_and_cancelled_invoices_cannot_receive_payments_across_all_endpoints(): void
    {
        $waivedStudent = $this->createStudent('STD-4A-WAIVED');
        $waivedInvoice = $this->createAdmissionInvoiceForStudent($waivedStudent, [
            'discount_type' => 'percentage',
            'discount_value' => '100.00',
        ]);
        $this->assertEquals('Waived', $waivedInvoice->display_status);
        $this->assertEquals('Unpaid', $waivedInvoice->status);

        $cancelledStudent = $this->createStudent('STD-4A-CANC');
        $cancelledInvoice = $this->createAdmissionInvoiceForStudent($cancelledStudent);
        $this->invoiceService->cancelUnpaidInvoice($cancelledInvoice->id, 'Cancelled admission', $this->superAdmin->id);
        $cancelledInvoice->refresh();

        $this->actingAs($this->superAdmin);

        foreach ([$waivedInvoice, $cancelledInvoice] as $blockedInv) {
            // Single collection
            $this->postJson(route('fees.collection.store'), [
                'invoice_id' => $blockedInv->id,
                'pay_amount' => '100.00',
                'payment_method' => 'Cash',
            ])->assertStatus(422);

            // Bulk store
            $this->postJson(route('fees.collection.bulk_store'), [
                'invoice_ids' => [$blockedInv->id],
                'payment_method' => 'Cash',
            ])->assertStatus(422);

            // Bulk students store
            $this->postJson(route('fees.collection.bulk_students_store'), [
                'invoice_ids' => [$blockedInv->id],
                'paying_amounts' => [$blockedInv->id => '100.00'],
                'payment_method' => 'Cash',
            ])->assertStatus(422);
        }

        $this->assertEquals(0, FeePayment::whereIn('fee_invoice_id', [$waivedInvoice->id, $cancelledInvoice->id])->count());
    }

    public function test_database_idempotency_key_blocks_duplicate_submit_and_allows_retry_after_failed_attempt(): void
    {
        $student = $this->createStudent('STD-4A-IDEMP');
        $invoice = $this->createAdmissionInvoiceForStudent($student);
        $idempKey = 'req-idemp-unique-20261118-01';

        // Sanity: test harness runs under array cache store, proving dedicated DB table `fee_idempotency_keys` enforces idempotency!
        $this->assertSame('array', config('cache.default'));

        // Failed overpayment using idempKey must roll back idempotency row too!
        try {
            $this->collectionService->collectSinglePayment($invoice->id, [
                'pay_amount' => '9999.00',
                'payment_method' => 'Cash',
                'idempotency_key' => $idempKey,
            ], $this->superAdmin->id);
            $this->fail('Expected ValidationException for overpayment');
        } catch (ValidationException) {
            // Expected rollback
        }

        $this->assertEquals(0, DB::table('fee_idempotency_keys')->count());

        // Valid attempt with same key now succeeds once
        $res = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
            'idempotency_key' => $idempKey,
        ], $this->superAdmin->id);
        $this->assertTrue($res['success']);
        $this->assertEquals(1, FeePayment::where('fee_invoice_id', $invoice->id)->count());
        $this->assertEquals(1, DB::table('fee_idempotency_keys')->count());

        // Flush Laravel cache AND wipe `cache` DB table: idempotency must persist completely untouched!
        Cache::flush();
        DB::table('cache')->delete();
        $this->assertEquals(1, DB::table('fee_idempotency_keys')->count());

        // Second identical submission with same key is strictly blocked even on open partial invoice & empty cache
        $this->expectException(ValidationException::class);
        $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
            'idempotency_key' => $idempKey,
        ], $this->superAdmin->id);
    }

    public function test_expired_idempotency_claim_can_be_reclaimed_in_place_without_errors(): void
    {
        $student = $this->createStudent('STD-4A-IDEMP-EXP');
        $invoice = $this->createAdmissionInvoiceForStudent($student);
        $idempKey = 'req-idemp-exp-20261118-02';

        $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
            'idempotency_key' => $idempKey,
        ], $this->superAdmin->id);

        // Simulate claim passing 24-hour TTL expiration
        DB::table('fee_idempotency_keys')->update([
            'expires_at' => now()->subMinute()->toDateTimeString(),
        ]);

        // Re-submitting after expiration reclaims row in place and settles another partial installment cleanly
        $res2 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '500.00',
            'payment_method' => 'Cash',
            'idempotency_key' => $idempKey,
        ], $this->superAdmin->id);

        $this->assertTrue($res2['success']);
        $this->assertEquals(2, FeePayment::where('fee_invoice_id', $invoice->id)->count());
        $this->assertEquals(1, DB::table('fee_idempotency_keys')->count());
        $this->assertEquals('1500.00', $invoice->fresh()->paid_amount);
    }

    public function test_mixed_bulk_store_handles_legacy_and_admission_invoices_atomically(): void
    {
        $student = $this->createStudent('STD-4A-MIXED');
        $admissionInvoice = $this->createAdmissionInvoiceForStudent($student);

        $examCategory = FeeCategory::create(['name' => 'Monthly Exam Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $examSetup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'fee_category_id' => $examCategory->id,
            'fee_month' => 'March',
            'amount' => 500.00,
            'user_id' => $this->superAdmin->id,
        ]);

        $legacyInvoice = FeeInvoice::create([
            'invoice_no' => 'INV-202603-0099',
            'student_id' => $student->id,
            'fee_setup_id' => $examSetup->id,
            'amount' => 500.00,
            'discount' => 0.00,
            'net_amount' => 500.00,
            'paid_amount' => 0.00,
            'due_amount' => 500.00,
            'status' => 'Unpaid',
            'due_date' => '2026-03-10',
            'user_id' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->adminUser);

        // Pass unsorted IDs intentionally
        $res = $this->postJson(route('fees.collection.bulk_store'), [
            'invoice_ids' => [$legacyInvoice->id, $admissionInvoice->id],
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_collected', '3500.00')
            ->assertJsonPath('collected_count', 2);

        $admissionInvoice->refresh();
        $legacyInvoice->refresh();
        $this->assertEquals('Paid', $admissionInvoice->status);
        $this->assertEquals('Paid', $legacyInvoice->status);
        $this->assertEquals(3, AdmissionPaymentHeadAllocation::where('fee_invoice_id', $admissionInvoice->id)->where('status', 'Active')->count());
    }

    public function test_bulk_students_store_never_mutates_custom_fees_for_admission_invoices(): void
    {
        $studentA = $this->createStudent('STD-4A-BST1');
        $admInv = $this->createAdmissionInvoiceForStudent($studentA);

        $this->actingAs($this->adminUser);
        $res = $this->postJson(route('fees.collection.bulk_students_store'), [
            'invoice_ids' => [$admInv->id],
            'paying_amounts' => [$admInv->id => '1200.00'],
            'payment_method' => 'Cash',
        ]);

        $res->assertOk()->assertJsonPath('success', true);

        $admInv->refresh();
        $this->assertEquals('3000.00', $admInv->net_amount);
        $this->assertEquals('0.00', $admInv->discount);
        $this->assertEquals('1200.00', $admInv->paid_amount);
        $this->assertEquals('1800.00', $admInv->due_amount);
        $this->assertEquals('Partial', $admInv->status);
        $this->assertEquals(0, StudentCustomFee::count());
    }

    public function test_legacy_adjustment_and_inline_custom_fee_endpoints_block_admission_invoices_with_422(): void
    {
        $student = $this->createStudent('STD-4A-BLOCKADJ');
        $admInv = $this->createAdmissionInvoiceForStudent($student);

        $this->actingAs($this->superAdmin);

        $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $admInv->id,
            'mode' => 'decrease',
            'amount' => 200,
            'scope' => 'single',
            'reason' => 'Attempt adjust admission invoice',
        ])->assertStatus(422);

        $this->postJson(route('fees.custom_fee.update_inline'), [
            'invoice_id' => $admInv->id,
            'amount' => 2500,
        ])->assertStatus(422);

        $admInv->refresh();
        $this->assertEquals('3000.00', $admInv->net_amount);
        $this->assertEquals('0.00', $admInv->discount);
    }

    public function test_rbac_blocks_editor_teacher_and_user_from_collection_at_controller_and_service_levels(): void
    {
        $student = $this->createStudent('STD-4A-RBAC');
        $admInv = $this->createAdmissionInvoiceForStudent($student);

        foreach ([$this->editorUser, $this->teacherUser, $this->regularUser] as $unauthorizedUser) {
            // Route / Middleware + Controller layer check
            $this->actingAs($unauthorizedUser);
            $httpRes = $this->postJson(route('fees.collection.store'), [
                'invoice_id' => $admInv->id,
                'pay_amount' => '100.00',
                'payment_method' => 'Cash',
            ]);
            $this->assertTrue(in_array($httpRes->status(), [403, 302], true));

            // Direct Service-layer authorization check (defense-in-depth)
            try {
                $this->collectionService->collectSinglePayment($admInv->id, [
                    'pay_amount' => '100.00',
                    'payment_method' => 'Cash',
                ], $unauthorizedUser->id);
                $this->fail('Expected AuthorizationException at service level for role user ID ' . $unauthorizedUser->id);
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertEquals(0, FeePayment::where('fee_invoice_id', $admInv->id)->count());
    }

    public function test_fee_setup_and_category_destroy_return_422_when_referenced_under_restrict_constraints(): void
    {
        $student = $this->createStudent('STD-4A-RESTRICT');
        $this->createAdmissionInvoiceForStudent($student);

        $examCategory = FeeCategory::create(['name' => 'Lab Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $examSetup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'fee_category_id' => $examCategory->id,
            'fee_month' => 'April',
            'amount' => 300.00,
            'user_id' => $this->superAdmin->id,
        ]);
        FeeInvoice::create([
            'invoice_no' => 'INV-202604-8888',
            'student_id' => $student->id,
            'fee_setup_id' => $examSetup->id,
            'amount' => 300.00,
            'discount' => 0.00,
            'net_amount' => 300.00,
            'paid_amount' => 0.00,
            'due_amount' => 300.00,
            'status' => 'Unpaid',
            'due_date' => '2026-04-10',
            'user_id' => $this->superAdmin->id,
        ]);

        $unusedCat = FeeCategory::create(['name' => 'Temporary Unused Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);

        $this->actingAs($this->superAdmin);

        // Referenced by AdmissionFeeStructureItem & AdmissionInvoiceHeadAllocation
        $this->deleteJson(route('fees.categories.destroy', $this->admissionCat->id))
            ->assertStatus(422);

        // Referenced by FeeInvoice via FeeSetup
        $this->deleteJson(route('fees.setup.destroy', $examSetup->id))
            ->assertStatus(422);

        // Unreferenced category can be deleted cleanly
        $this->deleteJson(route('fees.categories.destroy', $unusedCat->id))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_fee_invoice_generate_index_handles_null_setup_admission_invoices_without_snapshot_error(): void
    {
        $student = $this->createStudent('STD-4A-IDX');
        $this->createAdmissionInvoiceForStudent($student);

        $this->actingAs($this->superAdmin);
        $this->get(route('fees.invoice.generate'))->assertOk();
    }
}
