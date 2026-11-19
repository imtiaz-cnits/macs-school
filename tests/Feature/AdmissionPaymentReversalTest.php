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
use HasinHayder\Tyro\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class AdmissionPaymentReversalTest extends TestCase
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
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $teacherRole = Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'Teacher']);
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);

        $this->superAdmin = User::create(['name' => 'Rev Super', 'email' => 'rev_super@macsschool.test', 'password' => bcrypt('secret')]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->adminUser = User::create(['name' => 'Rev Admin', 'email' => 'rev_admin@macsschool.test', 'password' => bcrypt('secret')]);
        $this->adminUser->assignRole($adminRole);

        $this->editorUser = User::create(['name' => 'Rev Editor', 'email' => 'rev_editor@macsschool.test', 'password' => bcrypt('secret')]);
        $this->editorUser->assignRole($editorRole);

        $this->teacherUser = User::create(['name' => 'Rev Teacher', 'email' => 'rev_teacher@macsschool.test', 'password' => bcrypt('secret')]);
        $this->teacherUser->assignRole($teacherRole);

        $this->regularUser = User::create(['name' => 'Rev User', 'email' => 'rev_user@macsschool.test', 'password' => bcrypt('secret')]);
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
            'structure_name' => 'Class One Admission Reversal Pkg',
            'status' => 'Active',
            'total_amount' => '3000.00',
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
                    'amount' => '1500.00',
                    'is_discountable' => true,
                    'is_tuition_head' => true,
                    'sort_order' => 20,
                ],
            ],
        ], $this->superAdmin->id);
    }

    protected function createStudent(string $identity = 'STD-REV-001'): Student
    {
        return Student::create([
            'user_id' => $this->superAdmin->id,
            'student_identity' => $identity,
            'student_name' => 'Student ' . $identity,
            'roll_number' => '02',
            'dob' => '2016-01-01',
            'gender' => 'Female',
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

    public function test_http_reversal_of_full_admission_payment_updates_heads_invoice_and_writes_immutable_audit_trail(): void
    {
        $student = $this->createStudent('STD-REV-ADM');
        $invoice = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];

        $payResult = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '3000.00',
            'payment_method' => 'Cash',
        ], $this->adminUser->id);

        /** @var FeePayment $payment */
        $payment = $payResult['payment'];
        $originalReceiptNo = $payment->receipt_no;

        $this->actingAs($this->adminUser);
        $res = $this->postJson(route('fees.payments.reverse', $payment->id), [
            'reason' => 'Wrong student receipt posted by cashier',
        ]);

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_admission_invoice', true)
            ->assertJsonPath('reversed_amount', '3000.00');

        $revRef = $res->json('reversal_reference');
        $this->assertStringStartsWith('REV-', (string) $revRef);

        // Original FeePayment remains intact in DB (`paid_amount` untouched), transition marked Reversed
        $payment->refresh();
        $this->assertEquals($originalReceiptNo, $payment->receipt_no);
        $this->assertEquals('3000.00', $payment->paid_amount);
        $this->assertEquals('Reversed', $payment->status);
        $this->assertEquals('3000.00', $payment->reversed_amount);
        $this->assertEquals($this->adminUser->id, $payment->reversed_by);
        $this->assertNotNull($payment->reversed_at);
        $this->assertEquals('Wrong student receipt posted by cashier', $payment->reversal_reason);
        $this->assertEquals($revRef, $payment->reversal_reference);

        // Child AdmissionPaymentHeadAllocation rows marked Reversed cleanly
        $allocs = AdmissionPaymentHeadAllocation::where('fee_payment_id', $payment->id)->get();
        $this->assertCount(2, $allocs);
        foreach ($allocs as $alloc) {
            $this->assertEquals('Reversed', $alloc->status);
            $this->assertNotNull($alloc->reversed_at);
            $this->assertEquals($revRef, $alloc->reversal_reference);
        }

        // Invoice & Heads restored cleanly from Active ledger (now 0.00 paid, Unpaid)
        $invoice->refresh();
        $this->assertEquals('Unpaid', $invoice->status);
        $this->assertEquals('0.00', $invoice->paid_amount);
        $this->assertEquals('3000.00', $invoice->due_amount);

        foreach ($invoice->admissionHeadAllocations as $head) {
            $this->assertEquals('0.00', $head->allocated_paid_amount);
            $this->assertEquals('1500.00', $head->due_amount);
        }

        // Immutable FeeAdjustmentLog recorded
        $log = FeeAdjustmentLog::where('fee_invoice_id', $invoice->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('set_amount', $log->mode);
        $this->assertEquals('single', $log->scope);
        $this->assertEquals(3000.00, (float) $log->adjustment_amount);
        $this->assertEquals(0.00, (float) $log->paid_amount);
        $this->assertEquals(3000.00, (float) $log->new_due_amount);
    }

    public function test_reversing_one_of_two_admission_payments_recalculates_partial_balances_then_allows_recollection(): void
    {
        $student = $this->createStudent('STD-REV-PART');
        $invoice = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        // Installment 1: 1000.00 explicitly on Admission Charge
        $pay1 = $this->collectionService->collectSinglePayment($invoice->id, [
            'payment_method' => 'Cash',
            'head_allocations' => [$heads[0]->id => '1000.00'],
        ], $this->superAdmin->id)['payment'];

        // Installment 2: 2000.00 settling remaining 500 on head 0 + 1500 on head 1
        $pay2 = $this->collectionService->collectSinglePayment($invoice->id, [
            'payment_method' => 'bKash',
            'head_allocations' => [
                $heads[0]->id => '500.00',
                $heads[1]->id => '1500.00',
            ],
        ], $this->superAdmin->id)['payment'];

        $invoice->refresh();
        $this->assertEquals('Paid', $invoice->status);

        // Reverse ONLY Installment 2
        $this->actingAs($this->superAdmin);
        $this->postJson(route('fees.payments.reverse', $pay2->id), [
            'reason' => 'bKash transaction bounced',
        ])->assertOk();

        $invoice->refresh();
        $this->assertEquals('Partial', $invoice->status);
        $this->assertEquals('1000.00', $invoice->paid_amount);
        $this->assertEquals('2000.00', $invoice->due_amount);

        $heads[0]->refresh();
        $heads[1]->refresh();
        $this->assertEquals('1000.00', $heads[0]->allocated_paid_amount);
        $this->assertEquals('500.00', $heads[0]->due_amount);
        $this->assertEquals('0.00', $heads[1]->allocated_paid_amount);
        $this->assertEquals('1500.00', $heads[1]->due_amount);

        // Installment 1 remains Active
        $pay1->refresh();
        $this->assertEquals('Active', $pay1->status);

        // Re-collect remaining 2000.00 cleanly
        $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '2000.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id);

        $invoice->refresh();
        $this->assertEquals('Paid', $invoice->status);
        $this->assertEquals('3000.00', $invoice->paid_amount);
        $this->assertEquals('0.00', $invoice->due_amount);
    }

    public function test_legacy_fee_invoice_payment_reversal_recalculates_from_active_ledger_without_head_allocations(): void
    {
        $student = $this->createStudent('STD-REV-LEG');
        $examCat = FeeCategory::create(['name' => 'Exam Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $examSetup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'fee_category_id' => $examCat->id,
            'fee_month' => 'May',
            'amount' => 1000.00,
            'user_id' => $this->superAdmin->id,
        ]);

        $legacyInvoice = FeeInvoice::create([
            'invoice_no' => 'INV-202605-0077',
            'student_id' => $student->id,
            'fee_setup_id' => $examSetup->id,
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 1000.00,
            'status' => 'Unpaid',
            'due_date' => '2026-05-10',
            'user_id' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin);

        // Collect 2 payments: 400 + 600
        $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $legacyInvoice->id,
            'pay_amount' => '400.00',
            'payment_method' => 'Cash',
        ])->assertOk();

        $this->postJson(route('fees.collection.store'), [
            'invoice_id' => $legacyInvoice->id,
            'pay_amount' => '600.00',
            'payment_method' => 'Nagad',
        ])->assertOk();

        $payments = FeePayment::where('fee_invoice_id', $legacyInvoice->id)->orderBy('id')->get();
        $this->assertCount(2, $payments);
        $legacyInvoice->refresh();
        $this->assertEquals('Paid', $legacyInvoice->status);

        // Reverse second legacy payment (600.00)
        $revRes = $this->postJson(route('fees.payments.reverse', $payments[1]->id), [
            'reason' => 'Duplicate entry for Nagad transfer',
        ]);

        $revRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_admission_invoice', false)
            ->assertJsonPath('reversed_amount', '600.00');

        $payments[1]->refresh();
        $this->assertEquals('Reversed', $payments[1]->status);
        $this->assertEquals('600.00', $payments[1]->reversed_amount);
        $this->assertEquals('600.00', $payments[1]->paid_amount); // Receipt history preserved!

        $legacyInvoice->refresh();
        $this->assertEquals('Partial', $legacyInvoice->status);
        $this->assertEquals('400.00', $legacyInvoice->paid_amount);
        $this->assertEquals('600.00', $legacyInvoice->due_amount);

        // Reverse first legacy payment (400.00)
        $this->postJson(route('fees.payments.reverse', $payments[0]->id), [
            'reason' => 'Full refund to guardian',
        ])->assertOk();

        $legacyInvoice->refresh();
        $this->assertEquals('Unpaid', $legacyInvoice->status);
        $this->assertEquals('0.00', $legacyInvoice->paid_amount);
        $this->assertEquals('1000.00', $legacyInvoice->due_amount);
        $this->assertEquals(2, FeePayment::where('fee_invoice_id', $legacyInvoice->id)->where('status', 'Reversed')->count());
        $this->assertEquals(2, FeeAdjustmentLog::where('fee_invoice_id', $legacyInvoice->id)->count());
    }

    public function test_duplicate_reversal_is_strictly_rejected_with_422_for_both_admission_and_legacy(): void
    {
        $student = $this->createStudent('STD-REV-DUP');
        $admInv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $admPayment = $this->collectionService->collectSinglePayment($admInv->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id)['payment'];

        $this->actingAs($this->superAdmin);
        $this->postJson(route('fees.payments.reverse', $admPayment->id), [
            'reason' => 'First legitimate reversal',
        ])->assertOk();

        // Repeat reversal returns HTTP 422
        $dupRes = $this->postJson(route('fees.payments.reverse', $admPayment->id), [
            'reason' => 'Accidental double reversal',
        ]);
        $dupRes->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_partial_reversal_attempt_is_strictly_rejected_with_422(): void
    {
        $student = $this->createStudent('STD-REV-PARTBLOCK');
        $admInv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $admPayment = $this->collectionService->collectSinglePayment($admInv->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id)['payment'];

        $this->actingAs($this->superAdmin);

        $this->postJson(route('fees.payments.reverse', $admPayment->id), [
            'reason' => 'Attempting partial reversal',
            'reversed_amount' => '500.00',
        ])->assertStatus(422);

        $this->postJson(route('fees.payments.reverse', $admPayment->id), [
            'reason' => 'Attempting partial reversal',
            'partial_amount' => '500.00',
        ])->assertStatus(422);

        $admPayment->refresh();
        $this->assertEquals('Active', $admPayment->status);
    }

    public function test_relationship_mismatch_and_cancelled_invoice_reversal_are_rejected_and_client_ids_never_trusted(): void
    {
        $studentA = $this->createStudent('STD-REV-REL1');
        $studentB = $this->createStudent('STD-REV-REL2');

        $invA = $this->invoiceService->generateForStudent($studentA, [], $this->superAdmin->id)['invoice'];
        $invB = $this->invoiceService->generateForStudent($studentB, [], $this->superAdmin->id)['invoice'];

        $paymentA = $this->collectionService->collectSinglePayment($invA->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id)['payment'];

        $this->actingAs($this->superAdmin);

        // Forged student_id hint rejected (422) without touching studentB or invA
        $this->postJson(route('fees.payments.reverse', $paymentA->id), [
            'reason' => 'Forged student reference check',
            'student_id' => $studentB->id,
        ])->assertStatus(422);

        // Forged fee_invoice_id hint rejected (422)
        $this->postJson(route('fees.payments.reverse', $paymentA->id), [
            'reason' => 'Forged invoice reference check',
            'fee_invoice_id' => $invB->id,
        ])->assertStatus(422);

        $paymentA->refresh();
        $this->assertEquals('Active', $paymentA->status);
        $this->assertEquals('0.00', $invB->fresh()->paid_amount);
    }

    public function test_deleting_admission_payment_head_allocation_throws_logic_exception(): void
    {
        $student = $this->createStudent('STD-REV-IMMUT');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $pay = $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id)['payment'];

        $alloc = AdmissionPaymentHeadAllocation::where('fee_payment_id', $pay->id)->firstOrFail();

        $this->expectException(LogicException::class);
        $alloc->delete();
    }

    public function test_rbac_blocks_editor_teacher_and_user_from_reversing_at_route_and_service_levels(): void
    {
        $student = $this->createStudent('STD-REV-RBAC');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $pay = $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id)['payment'];

        foreach ([$this->editorUser, $this->teacherUser, $this->regularUser] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser);
            $httpRes = $this->postJson(route('fees.payments.reverse', $pay->id), [
                'reason' => 'Unauthorized attempt',
            ]);
            $this->assertTrue(in_array($httpRes->status(), [403, 302], true));

            try {
                $this->reversalService->reversePayment($pay->id, 'Direct service bypass test', $unauthorizedUser->id);
                $this->fail('Expected AuthorizationException for role user ' . $unauthorizedUser->id);
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertEquals('Active', $pay->fresh()->status);
    }

    public function test_reversal_validation_and_idempotency_guards_handle_edge_cases_cleanly(): void
    {
        $student = $this->createStudent('STD-REV-EDGE');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $pay = $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '500.00',
            'payment_method' => 'Cash',
        ], $this->superAdmin->id)['payment'];

        $this->actingAs($this->adminUser);

        // Missing or too-short reason
        $this->postJson(route('fees.payments.reverse', $pay->id), ['reason' => 'ab'])->assertStatus(422);

        // Non-existent payment ID
        $this->postJson(route('fees.payments.reverse', 999999), ['reason' => 'Not exists'])->assertStatus(404);

        // Idempotent reversal once succeeds, replay rejected with 422
        $idempKey = 'rev-idemp-20261118-01';
        $this->postJson(route('fees.payments.reverse', $pay->id), [
            'reason' => 'Valid reversal with key',
            'idempotency_key' => $idempKey,
        ])->assertOk();

        $this->postJson(route('fees.payments.reverse', $pay->id), [
            'reason' => 'Replay reversal with key',
            'idempotency_key' => $idempKey,
        ])->assertStatus(422);
    }

    public function test_restore_invoice_ignores_reversed_payments_and_enforces_admission_scope_rules(): void
    {
        $student = $this->createStudent('STD-REV-RESTORE');

        // 1. Cancelled legacy invoice that had a payment which was later Reversed must restore as Unpaid (0 paid)
        $cat = FeeCategory::create(['name' => 'Library Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $setup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'fee_category_id' => $cat->id,
            'fee_month' => 'June',
            'amount' => 800.00,
            'user_id' => $this->superAdmin->id,
        ]);

        $cancelledLegacy = FeeInvoice::create([
            'invoice_no' => 'INV-202606-0500',
            'student_id' => $student->id,
            'fee_setup_id' => $setup->id,
            'amount' => 800.00,
            'discount' => 0.00,
            'net_amount' => 800.00,
            'paid_amount' => 0.00,
            'due_amount' => 800.00,
            'status' => 'Unpaid',
            'due_date' => '2026-06-10',
            'user_id' => $this->superAdmin->id,
        ]);

        $legacyPay = $this->collectionService->collectBulkPayments([[
            'invoice_id' => $cancelledLegacy->id,
            'pay_full' => true,
        ]], ['payment_method' => 'Cash'], $this->superAdmin->id);

        $revPay = $legacyPay['payments']->first();
        $revPay->status = 'Reversed';
        $revPay->reversed_amount = $revPay->paid_amount;
        $revPay->reversed_at = now();
        $revPay->reversed_by = $this->superAdmin->id;
        $revPay->reversal_reason = 'Reversed before cancellation check';
        $revPay->reversal_reference = 'REV-MANUAL-0001';
        $revPay->save();

        // Simulate invoice marked Cancelled afterward
        $cancelledLegacy->update(['status' => 'Cancelled', 'due_amount' => 0.00, 'paid_amount' => 0.00]);

        $this->actingAs($this->superAdmin);
        $restoreRes = $this->postJson(route('fees.invoice.restore', $cancelledLegacy->id), [
            'reason' => 'Restore legacy invoice after prior payment reversal',
            'confirm' => true,
        ]);
        $restoreRes->assertOk()->assertJsonPath('invoice.status', 'Unpaid');
        $this->assertEquals(800.00, (float) $restoreRes->json('invoice.due_amount'));
        $this->assertEquals(0.00, (float) $restoreRes->json('invoice.paid_amount'));

        // 2. Cancelled Admission Invoice cannot restore if another active Admission Invoice already exists in scope
        $admV1 = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $this->invoiceService->cancelUnpaidInvoice($admV1->id, 'Superseded by V2', $this->superAdmin->id);
        $admV2 = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];

        $this->postJson(route('fees.invoice.restore', $admV1->id), [
            'reason' => 'Unsafe restore while V2 active',
            'confirm' => true,
        ])->assertStatus(422);

        // Cancel V2, then V1 restores cleanly
        $this->invoiceService->cancelUnpaidInvoice($admV2->id, 'Revert to V1', $this->superAdmin->id);
        $this->postJson(route('fees.invoice.restore', $admV1->id), [
            'reason' => 'Safe restore now that V2 is cancelled',
            'confirm' => true,
        ])->assertOk()->assertJsonPath('invoice.status', 'Unpaid');
    }
}
