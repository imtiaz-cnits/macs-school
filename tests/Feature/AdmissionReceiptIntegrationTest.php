<?php

namespace Tests\Feature;

use App\Helpers\NumberToWords;
use App\Models\AdmissionFeeStructure;
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
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionFeeStructureService;
use App\Services\AdmissionInvoiceService;
use App\Services\AdmissionPaymentReversalService;
use App\Services\FeeReceiptService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdmissionReceiptIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classOne;
    protected Section $sectionA;
    protected FeeCategory $admissionCat;
    protected FeeCategory $sessionCat;
    protected FeeCategory $tuitionCat;
    protected FeeCategory $examCat;
    protected AdmissionFeeStructure $structure;
    protected AdmissionFeeStructureService $structureService;
    protected AdmissionInvoiceService $invoiceService;
    protected AdmissionCollectionService $collectionService;
    protected AdmissionPaymentReversalService $reversalService;
    protected FeeReceiptService $receiptService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->structureService = app(AdmissionFeeStructureService::class);
        $this->invoiceService = app(AdmissionInvoiceService::class);
        $this->collectionService = app(AdmissionCollectionService::class);
        $this->reversalService = app(AdmissionPaymentReversalService::class);
        $this->receiptService = app(FeeReceiptService::class);

        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);

        $this->superAdmin = User::create([
            'name' => 'Receipt Super Admin',
            'email' => 'rec_super@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->adminUser = User::create([
            'name' => 'Receipt Cashier',
            'email' => 'rec_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->branch = Branch::create(['branch_name' => 'Jalalpur Branch', 'user_id' => $this->superAdmin->id]);
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
        $this->examCat = FeeCategory::create(['name' => 'Monthly Exam Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);

        $this->structure = $this->structureService->createStructure([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'structure_name' => 'Class One 2026 Admission Pack',
            'status' => 'Active',
            'total_amount' => '3500.00',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 1,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Form & Registration',
                    'amount' => '1500.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->sessionCat->id,
                    'head_name' => 'Development & Lab Fee',
                    'amount' => '1000.00',
                    'is_discountable' => false,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
                [
                    'fee_category_id' => $this->tuitionCat->id,
                    'head_name' => 'January Monthly Tuition',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => true,
                    'sort_order' => 30,
                ],
            ],
        ], $this->superAdmin->id);
    }

    protected function createStudent(string $identity = 'STD-4B-001', string $roll = '01'): Student
    {
        return Student::create([
            'user_id' => $this->superAdmin->id,
            'student_identity' => $identity,
            'student_name' => 'Student ' . $identity,
            'roll_number' => $roll,
            'dob' => '2016-01-01',
            'gender' => 'Male',
            'father_name' => 'Father ' . $identity,
            'father_mobile' => '01711111111',
            'mother_name' => 'Mother ' . $identity,
            'mother_mobile' => '01722222222',
            'guardian_name' => 'Guardian ' . $identity,
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

    protected function createAdmissionInvoice(Student $student, array $discountOptions = []): FeeInvoice
    {
        $res = $this->invoiceService->generateForStudent($student, $discountOptions, $this->superAdmin->id);
        return $res['invoice'];
    }

    protected function createLegacyInvoice(
        Student $student,
        FeeCategory $category,
        string $amount,
        string $month = 'February',
        string $invoiceNo = 'INV-202602-0001'
    ): FeeInvoice {
        $setup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'fee_category_id' => $category->id,
            'fee_month' => $month,
            'amount' => $amount,
            'status' => 'Active',
            'user_id' => $this->superAdmin->id,
        ]);

        return FeeInvoice::create([
            'invoice_no' => $invoiceNo,
            'student_id' => $student->id,
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session->id,
            'branch_name_snapshot' => $this->branch->branch_name,
            'fee_setup_id' => $setup->id,
            'amount' => $amount,
            'discount' => '0.00',
            'net_amount' => $amount,
            'paid_amount' => '0.00',
            'due_amount' => $amount,
            'status' => 'Unpaid',
            'due_date' => '2026-02-01',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_single_admission_pos_invoice_renders_single_a5_copy_head_breakdown_and_tuition_tag(): void
    {
        $student = $this->createStudent('STD-4B-POS1');
        // Fixed 500.00 discount distributed across discountable heads (1500 + 1000 = 2500 -> 300.00 + 200.00), non-discountable 1000 untouched.
        $invoice = $this->createAdmissionInvoice($student, [
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('fees.invoice.pos_print', $invoice->id));

        $response->assertOk();
        $html = $response->getContent();

        $response->assertSee('size: A5 portrait');
        $response->assertDontSee('OFFICE COPY');
        $response->assertDontSee('STUDENT COPY');
        $response->assertSee('Admission Fee Package — Class One 2026 Admission Pack');
        $response->assertSee('Includes January Tuition');
        $response->assertSee('Admission Form &amp; Registration', false);
        $response->assertSee('Development &amp; Lab Fee', false);
        $response->assertSee('January Monthly Tuition');
        $response->assertSee('Tuition');

        // Both Gross/Discount/Net/Due totals appear cleanly in a single copy
        $response->assertSee('৳3,500.00');
        $response->assertSee('-৳500.00');
        $response->assertSee('৳3,000.00');
        $this->assertEquals(1, substr_count($html, 'Admission Fee Package — Class One 2026 Admission Pack'));
    }

    public function test_legacy_pos_invoice_and_master_receipt_preserve_regression_parity(): void
    {
        $student = $this->createStudent('STD-4B-LEG1');
        $febInvoice = $this->createLegacyInvoice($student, $this->tuitionCat, '1200.00', 'February', 'INV-202602-8801');
        $marInvoice = $this->createLegacyInvoice($student, $this->examCat, '450.00', 'March', 'INV-202603-8802');

        $this->actingAs($this->adminUser);
        $bulkRes = $this->postJson(route('fees.collection.bulk_store'), [
            'invoice_ids' => [$marInvoice->id, $febInvoice->id],
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'CASH-LEG-99',
        ])->assertOk();

        $masterReceiptNo = $bulkRes->json('master_receipt_no');

        // 1. Single Legacy POS invoice
        $singlePos = $this->get(route('fees.invoice.pos_print', $febInvoice->id));
        $singlePos->assertOk();
        $singlePos->assertSee('Monthly Fee');
        $singlePos->assertSee('February');
        $singlePos->assertSee('1,200.00');
        $singlePos->assertSee('Method: CASH');
        $singlePos->assertSee('Trx ID: CASH-LEG-99');
        $singlePos->assertSee('PAID');
        $singlePos->assertSee('In Words:');
        $singlePos->assertSee('One Thousand Two Hundred Taka Only.');
        $singlePos->assertSee('Authorized Cashier');
        $singlePos->assertDontSee('>Guardian<', false);
        $singlePos->assertDontSee('Admission Fee Package');

        // 2. Legacy Master Receipt A5
        $masterPos = $this->get(route('fees.receipt.pos_print', $masterReceiptNo));
        $masterPos->assertOk();
        $masterPos->assertSee('Master Receipt');
        $masterPos->assertSee($masterReceiptNo);
        $masterPos->assertSee('Monthly Fee');
        $masterPos->assertSee('Monthly Exam Fee');
        $masterPos->assertSee('৳1,650.00');
        $masterPos->assertSee('One Thousand Six Hundred Fifty Taka Only.');
        // Chronological order: February appears before March
        $content = $masterPos->getContent();
        $this->assertLessThan(strpos($content, 'March'), strpos($content, 'February'));

        // 3. Legacy Individual Bulk Slips
        $indPos = $this->get(route('fees.receipt.pos_print_individual', $masterReceiptNo));
        $indPos->assertOk();
        $indPos->assertSee($febInvoice->invoice_no);
        $indPos->assertSee($marInvoice->invoice_no);
    }

    public function test_partial_and_multiple_admission_payments_use_exact_receipt_head_allocations_not_invoice_accumulated(): void
    {
        $student = $this->createStudent('STD-4B-MULTI');
        $invoice = $this->createAdmissionInvoice($student); // Heads: 1500, 1000, 1000 (Total 3500)
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        // Receipt 1 pays ONLY Head 0 (1500.00)
        $r1 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1500.00',
                $heads[1]->id => '0.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        // Receipt 2 pays ONLY Head 1 (600.00) + Head 2 (400.00) = 1000.00
        $r2 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'BK-SECOND-55',
            'head_allocations' => [
                $heads[0]->id => '0.00',
                $heads[1]->id => '600.00',
                $heads[2]->id => '400.00',
            ],
        ], $this->adminUser->id);

        $this->actingAs($this->adminUser);

        // Master Receipt for Receipt 2 MUST show ONLY Head 1 (600.00) & Head 2 (400.00) -> 1,000.00 (NEVER Head 0's 1,500.00!)
        $masterR2 = $this->get(route('fees.receipt.pos_print', $r2['receipt_no']));
        $masterR2->assertOk();
        $masterR2->assertSee('Development &amp; Lab Fee', false);
        $masterR2->assertSee('৳600.00');
        $masterR2->assertSee('January Monthly Tuition');
        $masterR2->assertSee('৳400.00');
        $masterR2->assertSee('৳1,000.00');
        $masterR2->assertSee('One Thousand Taka Only.');
        $masterR2->assertDontSee('Admission Form &amp; Registration', false);
        $masterR2->assertDontSee('৳2,500.00');

        // Scoped Individual Slip for Receipt 2 MUST show This Receipt Paid = 1,000.00 (Head 0 paid = 0.00 on this receipt), Invoice Paid Total = 2,500.00, Remaining Due = 1,000.00
        $indR2 = $this->get(route('fees.receipt.pos_print_individual', $r2['receipt_no']));
        $indR2->assertOk();
        $indR2->assertSee('Receipt No: <strong>' . $r2['receipt_no'] . '</strong>', false);
        $indR2->assertSee('This Receipt Paid:');
        $indR2->assertSee('৳1,000.00');
        $indR2->assertSee('Invoice Paid Total:');
        $indR2->assertSee('৳2,500.00');
        $indR2->assertSee('Method: BKASH');
        $indR2->assertSee('Trx ID: BK-SECOND-55');

        // Invoice-level POS Print shows cumulative totals across R1+R2
        $invPos = $this->get(route('fees.invoice.pos_print', $invoice->id));
        $invPos->assertOk();
        $invPos->assertSee('Total Paid');
        $invPos->assertSee('৳2,500.00');
        $invPos->assertSee('Remaining Due:');
        $invPos->assertSee('৳1,000.00');
        $invPos->assertSee($r1['receipt_no']);
        $invPos->assertSee($r2['receipt_no']);
    }

    public function test_mixed_admission_and_legacy_master_receipt_reconciles_totals_without_snapshot_exception(): void
    {
        $student = $this->createStudent('STD-4B-MIX');
        $admInvoice = $this->createAdmissionInvoice($student); // Net 3,500.00
        $febLegacy = $this->createLegacyInvoice($student, $this->examCat, '725.50', 'February', 'INV-202602-7701');

        $this->actingAs($this->adminUser);
        $res = $this->postJson(route('fees.collection.bulk_store'), [
            'invoice_ids' => [$admInvoice->id, $febLegacy->id],
            'paying_amounts' => [
                $admInvoice->id => '2000.25',
                $febLegacy->id => '725.50',
            ],
            'payment_method' => 'Bank',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'BNK-MIX-10',
        ])->assertOk();

        $masterNo = $res->json('master_receipt_no');

        $response = $this->get(route('fees.receipt.pos_print', $masterNo));
        $response->assertOk();
        $response->assertSee('Monthly Exam Fee');
        $response->assertSee('February');
        $response->assertSee('৳725.50');
        $response->assertSee('Admission Fee Package — Class One 2026 Admission Pack');
        $response->assertSee('Includes January Tuition');
        $response->assertSee('৳2,000.25');
        $response->assertSee('৳2,725.75');
        $response->assertSee('Two Thousand Seven Hundred Twenty Five Taka and Seventy Five Paisa Only.');

        // Verify Admission head sub-rows in this payment sum strictly to 2000.25
        $paymentRow = FeePayment::where('receipt_no', $masterNo . '-' . $admInvoice->id)->firstOrFail();
        $allocSum = AdmissionPaymentHeadAllocation::where('fee_payment_id', $paymentRow->id)
            ->get()
            ->reduce(fn (string $acc, $row) => bcadd($acc, (string) $row->allocated_paid_amount, 2), '0.00');
        $this->assertEquals('2000.25', $allocSum);
    }

    public function test_collection_index_groups_rec_bulk_and_rec_receipts_distinctly_without_losing_serial(): void
    {
        $student = $this->createStudent('STD-4B-GRP');
        $invAdm = $this->createAdmissionInvoice($student);
        $invFeb = $this->createLegacyInvoice($student, $this->tuitionCat, '1000.00', 'February', 'INV-202602-6601');
        $invMar = $this->createLegacyInvoice($student, $this->examCat, '550.00', 'March', 'INV-202603-6602');

        // Two distinct same-day REC-BULK master receipts + one REC single receipt
        FeePayment::create([
            'receipt_no' => 'REC-BULK-20261118-0001-' . $invFeb->id,
            'fee_invoice_id' => $invFeb->id,
            'student_id' => $student->id,
            'branch_id' => $this->branch->id,
            'paid_amount' => '1000.00',
            'payment_date' => '2026-11-18',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);
        $invFeb->update(['paid_amount' => '1000.00', 'due_amount' => '0.00', 'status' => 'Paid']);

        FeePayment::create([
            'receipt_no' => 'REC-BULK-20261118-0002-' . $invMar->id,
            'fee_invoice_id' => $invMar->id,
            'student_id' => $student->id,
            'branch_id' => $this->branch->id,
            'paid_amount' => '550.00',
            'payment_date' => '2026-11-18',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);
        $invMar->update(['paid_amount' => '550.00', 'due_amount' => '0.00', 'status' => 'Paid']);

        $admRes = $this->collectionService->collectSinglePayment($invAdm->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ], $this->adminUser->id);

        $response = $this->actingAs($this->adminUser)
            ->get(route('fees.collection.index', ['student_identity' => $student->student_identity]));

        $response->assertOk();
        $history = $response->viewData('paymentHistory');

        $this->assertTrue($history->has('REC-BULK-20261118-0001'));
        $this->assertTrue($history->has('REC-BULK-20261118-0002'));
        $this->assertTrue($history->has($admRes['receipt_no']));
        $this->assertFalse($history->has('REC-BULK-20261118'));

        $response->assertSee('REC-BULK-20261118-0001');
        $response->assertSee('REC-BULK-20261118-0002');
        $response->assertSee('Admission Fee Package — Class One 2026 Admission Pack');
        $response->assertSee('৳ 1,000.00');
        $response->assertSee('৳ 550.00');
        $response->assertSee('৳ 1,500.00');
    }

    public function test_master_receipt_lookup_blocks_prefix_collisions_and_like_wildcards(): void
    {
        $student = $this->createStudent('STD-4B-SAFE');
        $invA = $this->createLegacyInvoice($student, $this->tuitionCat, '400.00', 'February', 'INV-202602-5501');
        $invB = $this->createLegacyInvoice($student, $this->examCat, '900.00', 'March', 'INV-202603-5502');

        FeePayment::create([
            'receipt_no' => 'REC-20261118-0001',
            'fee_invoice_id' => $invA->id,
            'student_id' => $student->id,
            'branch_id' => $this->branch->id,
            'paid_amount' => '400.00',
            'payment_date' => '2026-11-18',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-20261118-00010',
            'fee_invoice_id' => $invB->id,
            'student_id' => $student->id,
            'branch_id' => $this->branch->id,
            'paid_amount' => '900.00',
            'payment_date' => '2026-11-18',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        // Requesting 0001 MUST NOT leak 00010
        $resExact = $this->get(route('fees.receipt.pos_print', 'REC-20261118-0001'));
        $resExact->assertOk();
        $resExact->assertSee('৳400.00');
        $resExact->assertDontSee('৳900.00');
        $resExact->assertDontSee('Monthly Exam Fee');

        // Wildcards % and _ must NOT expand
        $this->get(route('fees.receipt.pos_print', 'REC-20261118-%'))->assertNotFound();
        $this->get(route('fees.receipt.pos_print', 'REC-20261118-000_'))->assertNotFound();
        $this->get(route('fees.receipt.pos_print_individual', 'REC-20261118-000_'))->assertNotFound();
    }

    public function test_fully_reversed_master_receipt_renders_void_audit_copy_with_zero_active_total(): void
    {
        $student = $this->createStudent('STD-4B-FULLREV');
        $invoice = $this->createAdmissionInvoice($student);

        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '3500.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'BK-VOID-01',
        ], $this->adminUser->id);

        $rev = $this->reversalService->reversePayment(
            $collected['payment']->id,
            'Wrong student billed by mistake',
            $this->superAdmin->id
        );

        $response = $this->actingAs($this->adminUser)
            ->get(route('fees.receipt.pos_print', $collected['receipt_no']));

        $response->assertOk();
        $response->assertSee('VOID / REVERSED RECEIPT');
        $response->assertSee($collected['receipt_no']);
        $response->assertSee($rev['reversal_reference']);
        $response->assertSee('Receipt Super Admin');
        $response->assertSee('Wrong student billed by mistake');
        $response->assertSee('[REVERSED]');
        $response->assertSee('line-through');
        $response->assertSee('ACTIVE COLLECTED TOTAL:');
        $response->assertSee('৳0.00');
        $response->assertSee('Zero Taka Only.');
        $response->assertDontSee('BK-VOID-01');
        $response->assertDontSee('Three Thousand Five Hundred Taka Only.');
    }

    public function test_partially_reversed_master_receipt_excludes_reversed_line_from_grand_total_and_collection_history(): void
    {
        $student = $this->createStudent('STD-4B-PARTREV');
        $admInv = $this->createAdmissionInvoice($student);
        $legInv = $this->createLegacyInvoice($student, $this->examCat, '750.00', 'March', 'INV-202603-4401');

        $bulk = $this->collectionService->collectBulkPayments([
            ['invoice_id' => $admInv->id, 'pay_amount' => '1500.00'],
            ['invoice_id' => $legInv->id, 'pay_full' => true],
        ], [
            'receipt_prefix' => 'REC',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ], $this->adminUser->id);

        $admPay = $bulk['payments']->firstWhere('fee_invoice_id', $admInv->id);
        $rev = $this->reversalService->reversePayment(
            $admPay->id,
            'Reversed admission portion only',
            $this->superAdmin->id
        );

        $this->actingAs($this->adminUser);

        // Master Receipt shows Active 750.00, excludes reversed 1500.00
        $resMaster = $this->get(route('fees.receipt.pos_print', $bulk['master_receipt_no']));
        $resMaster->assertOk();
        $resMaster->assertSee('PARTIAL REVERSAL NOTICE');
        $resMaster->assertSee($rev['reversal_reference']);
        $resMaster->assertSee('GRAND TOTAL Paid:');
        $resMaster->assertSee('৳750.00');
        $resMaster->assertSee('Seven Hundred Fifty Taka Only.');
        $resMaster->assertDontSee('Two Thousand Two Hundred Fifty');

        // Collection index Payment History sums strictly Active 750.00 and shows Reversed badge
        $resIndex = $this->get(route('fees.collection.index', ['student_identity' => $student->student_identity]));
        $resIndex->assertOk();
        $resIndex->assertSee('৳ 750.00');
        $resIndex->assertSee('Reversed: ৳1,500.00');
        $resIndex->assertSee($rev['reversal_reference']);
    }

    public function test_reversed_individual_slips_and_selected_payments_print_void_banner(): void
    {
        $student = $this->createStudent('STD-4B-INDREV');
        $admInv = $this->createAdmissionInvoice($student);

        $collected = $this->collectionService->collectSinglePayment($admInv->id, [
            'pay_amount' => '1200.00',
            'payment_method' => 'Nagad',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'NG-REV-99',
        ], $this->adminUser->id);

        $rev = $this->reversalService->reversePayment(
            $collected['payment']->id,
            'Reversal for individual slip audit',
            $this->superAdmin->id
        );

        $this->actingAs($this->adminUser);

        // 1. Bulk individual slip route for reversed receipt
        $resBulkInd = $this->get(route('fees.receipt.pos_print_individual', $collected['receipt_no']));
        $resBulkInd->assertOk();
        $resBulkInd->assertSee('VOID / REVERSED PAYMENT');
        $resBulkInd->assertSee($rev['reversal_reference']);
        $resBulkInd->assertSee('Reversal for individual slip audit');
        $resBulkInd->assertSee('Active Paid (Slip):');
        $resBulkInd->assertSee('৳0.00');
        $resBulkInd->assertDontSee('NG-REV-99');

        // 2. Selected payments POST print route
        $resSelected = $this->post(route('fees.payments.print_selected'), [
            'payment_ids' => [$collected['payment']->id],
        ]);
        $resSelected->assertOk();
        $resSelected->assertSee('VOID / REVERSED PAYMENT');
        $resSelected->assertSee($rev['reversal_reference']);
        $resSelected->assertDontSee('NG-REV-99');
    }

    public function test_waived_admission_invoice_pos_shows_waived_status_and_zero_fake_payment_method(): void
    {
        $student = $this->createStudent('STD-4B-WAIVED');

        // Create all-discountable 100% waived structure for Class Two
        $classTwo = Classes::create(['class_name' => 'Two', 'user_id' => $this->superAdmin->id]);
        $secB = Section::create([
            'section_name' => 'B',
            'class_id' => $classTwo->id,
            'session_year_id' => $this->session->id,
            'user_id' => $this->superAdmin->id,
        ]);
        $student->update(['class_id' => $classTwo->id, 'section_id' => $secB->id]);

        $this->structureService->createStructure([
            'branch_id' => $this->branch->id,
            'class_id' => $classTwo->id,
            'session_year_id' => $this->session->id,
            'structure_name' => 'Waivable Package Class Two',
            'status' => 'Active',
            'total_amount' => '2000.00',
            'includes_monthly_tuition' => false,
            'tuition_month_number' => null,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Charge',
                    'amount' => '2000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
            ],
        ], $this->superAdmin->id);

        $waivedInvoice = $this->createAdmissionInvoice($student, [
            'discount_type' => 'percentage',
            'discount_value' => '100',
        ]);

        $this->assertEquals('Unpaid', $waivedInvoice->status);
        $this->assertEquals('Waived', $waivedInvoice->display_status);
        $this->assertCount(0, $waivedInvoice->payments);

        $response = $this->actingAs($this->adminUser)
            ->get(route('fees.invoice.pos_print', $waivedInvoice->id));

        $response->assertOk();
        $response->assertSee('WAIVED');
        $response->assertSee('100% Discount Waived (No Payment Required)');
        $response->assertSee('৳0.00');
        $response->assertDontSee('Method: CASH');
        $response->assertDontSee('>UNPAID<', false);
    }

    public function test_cancelled_admission_invoice_pos_displays_cancelled_banner(): void
    {
        $student = $this->createStudent('STD-4B-CANCEL');
        $invoice = $this->createAdmissionInvoice($student);
        $invoice->update(['status' => 'Cancelled', 'due_amount' => '0.00']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('fees.invoice.pos_print', $invoice->id));

        $response->assertOk();
        $response->assertSee('CANCELLED INVOICE — VOID FOR COLLECTION');
        $response->assertSee('CANCELLED');
    }

    public function test_invoice_pos_ignores_reversed_payment_method_and_trx_id_and_uses_active_only(): void
    {
        $student = $this->createStudent('STD-4B-ACTMETH');
        $invoice = $this->createAdmissionInvoice($student);

        // Payment 1 with TRX-OLD-REV-999 -> Reversed
        $p1 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'TRX-OLD-REV-999',
        ], $this->adminUser->id);

        $rev = $this->reversalService->reversePayment($p1['payment']->id, 'Re-enter with Nagad', $this->superAdmin->id);

        $this->actingAs($this->adminUser);

        // Before new payment: must NOT leak TRX-OLD-REV-999 or Method: BKASH
        $beforeNew = $this->get(route('fees.invoice.pos_print', $invoice->id));
        $beforeNew->assertOk();
        $beforeNew->assertSee('Payment: No Active Payment');
        $beforeNew->assertSee($rev['reversal_reference']);
        $beforeNew->assertDontSee('TRX-OLD-REV-999');
        $beforeNew->assertDontSee('Method: BKASH');

        // Payment 2 active via Nagad
        $p2 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Nagad',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'TRX-ACTIVE-777',
        ], $this->adminUser->id);

        $afterNew = $this->get(route('fees.invoice.pos_print', $invoice->id));
        $afterNew->assertOk();
        $afterNew->assertSee('Method: NAGAD');
        $afterNew->assertSee('Trx ID: TRX-ACTIVE-777');
        $afterNew->assertSee($p2['receipt_no']);
        $afterNew->assertDontSee('TRX-OLD-REV-999');
        $afterNew->assertDontSee('Method: BKASH');
    }

    public function test_bcmath_exact_paisa_totals_and_number_to_words_on_master_receipt(): void
    {
        $student = $this->createStudent('STD-4B-PAISA');
        $admInv = $this->createAdmissionInvoice($student);
        $legInv = $this->createLegacyInvoice($student, $this->examCat, '6543.89', 'March', 'INV-202603-3301');

        $bulk = $this->collectionService->collectBulkPayments([
            ['invoice_id' => $admInv->id, 'pay_amount' => '1234.56'],
            ['invoice_id' => $legInv->id, 'pay_full' => true],
        ], [
            'receipt_prefix' => 'REC',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ], $this->adminUser->id);

        $expectedTotal = bcadd('1234.56', '6543.89', 2); // 7778.45
        $this->assertEquals('7778.45', $bulk['total_collected']);
        $this->assertEquals('7,778.45', NumberToWords::formatDecimal($expectedTotal));

        $response = $this->actingAs($this->adminUser)
            ->get(route('fees.receipt.pos_print', $bulk['master_receipt_no']));

        $response->assertOk();
        $response->assertSee('৳7,778.45');
        $response->assertSee('Seven Thousand Seven Hundred Seventy Eight Taka and Forty Five Paisa Only.');
    }

    public function test_bulk_students_collection_master_and_individual_receipts_support_mixed_students(): void
    {
        $studentA = $this->createStudent('STD-4B-BLKA', '01');
        $studentB = $this->createStudent('STD-4B-BLKB', '02');

        $admInvA = $this->createAdmissionInvoice($studentA); // 3,500.00
        $setupFeb = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session->id,
            'fee_category_id' => $this->examCat->id,
            'fee_month' => 'April',
            'amount' => '800.00',
            'status' => 'Active',
            'user_id' => $this->superAdmin->id,
        ]);
        $legInvB = FeeInvoice::create([
            'invoice_no' => 'INV-202604-9901',
            'student_id' => $studentB->id,
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session->id,
            'branch_name_snapshot' => $this->branch->branch_name,
            'fee_setup_id' => $setupFeb->id,
            'amount' => '800.00',
            'discount' => '0.00',
            'net_amount' => '800.00',
            'paid_amount' => '0.00',
            'due_amount' => '800.00',
            'status' => 'Unpaid',
            'due_date' => '2026-04-01',
            'user_id' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->adminUser);
        $res = $this->postJson(route('fees.collection.bulk_students_store'), [
            'invoice_ids' => [$admInvA->id, $legInvB->id],
            'paying_amounts' => [
                $admInvA->id => '2100.00',
                $legInvB->id => '800.00',
            ],
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ])->assertOk();

        $masterReceiptNo = $res->json('master_receipt_no');
        $this->assertStringStartsWith('REC-BULK-', $masterReceiptNo);

        // Master Receipt spans both students with Admission head breakdown under Student A
        $resMaster = $this->get(route('fees.receipt.pos_print', $masterReceiptNo));
        $resMaster->assertOk();
        $resMaster->assertSee('Multiple Students');
        $resMaster->assertSee($studentA->student_identity);
        $resMaster->assertSee($studentB->student_identity);
        $resMaster->assertSee('Admission Fee Package — Class One 2026 Admission Pack');
        $resMaster->assertSee('৳2,900.00');
        $resMaster->assertSee('Two Thousand Nine Hundred Taka Only.');

        // Bulk Individual Slips render 1 single-copy A5 portrait receipt per invoice
        $resInd = $this->get(route('fees.receipt.pos_print_individual', $masterReceiptNo));
        $resInd->assertOk();
        $resInd->assertSee('size: A5 portrait');
        $resInd->assertDontSee('OFFICE COPY');
        $resInd->assertDontSee('STUDENT COPY');
        $resInd->assertSee($admInvA->invoice_no);
        $resInd->assertSee($legInvB->invoice_no);
        $resInd->assertSee('This Receipt Paid:');
        $resInd->assertSee('৳2,100.00');
        $resInd->assertSee('৳800.00');
    }

    public function test_unauthorized_roles_are_blocked_from_pos_and_master_receipt_routes(): void
    {
        $student = $this->createStudent('STD-4B-RBAC');
        $invoice = $this->createAdmissionInvoice($student);
        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ], $this->adminUser->id);

        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $editorUser = User::create([
            'name' => 'Receipt Editor',
            'email' => 'rec_editor@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $editorUser->assignRole($editorRole);

        $this->actingAs($editorUser);

        $this->assertNotEquals(200, $this->get(route('fees.invoice.pos_print', $invoice->id))->getStatusCode());
        $this->assertNotEquals(200, $this->get(route('fees.receipt.pos_print', $collected['receipt_no']))->getStatusCode());
        $this->assertNotEquals(200, $this->get(route('fees.receipt.pos_print_individual', $collected['receipt_no']))->getStatusCode());
        $this->assertNotEquals(200, $this->post(route('fees.payments.print_selected'), [
            'payment_ids' => [$collected['payment']->id],
        ])->getStatusCode());
    }

    public function test_head_allocation_isolation_active_payment_uses_strictly_active_allocations(): void
    {
        $student = $this->createStudent('STD-4B-ISO-ACT');
        $invoice = $this->createAdmissionInvoice($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1750.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1250.00',
                $heads[1]->id => '500.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        $payment = $collected['payment']->fresh('admissionHeadAllocations');
        $selection = $this->receiptService->selectOrderedPaymentHeadAllocations($payment);

        $this->assertFalse($selection['is_payment_reversed']);
        $this->assertFalse($selection['used_provenance_fallback']);
        $this->assertFalse($selection['has_status_mismatch']);
        $this->assertFalse($selection['missing_expected_allocations']);
        $this->assertCount(2, $selection['allocations']);
        $this->assertEquals(
            ['1250.00', '500.00'],
            $selection['allocations']->pluck('allocated_paid_amount')->map(fn ($v) => bcadd((string) $v, '0.00', 2))->all()
        );

        $this->actingAs($this->adminUser);
        $this->get(route('fees.receipt.pos_print', $collected['receipt_no']))
            ->assertOk()
            ->assertSee('৳1,250.00')
            ->assertSee('৳500.00')
            ->assertSee('৳1,750.00')
            ->assertDontSee('Active head allocation breakdown unavailable');

        $this->get(route('fees.collection.index', ['student_identity' => $student->student_identity]))
            ->assertOk()
            ->assertSee('Admission Form &amp; Registration: ৳1,250.00', false)
            ->assertSee('Development &amp; Lab Fee: ৳500.00', false);
    }

    public function test_head_allocation_isolation_reversed_payment_uses_strictly_reversed_allocations(): void
    {
        $student = $this->createStudent('STD-4B-ISO-REV');
        $invoice = $this->createAdmissionInvoice($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1400.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '900.00',
                $heads[1]->id => '500.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        $rev = $this->reversalService->reversePayment(
            $collected['payment']->id,
            'Full audit copy reversal check',
            $this->superAdmin->id
        );

        $reversedPayment = FeePayment::with(['invoice.admissionHeadAllocations', 'admissionHeadAllocations'])
            ->findOrFail($collected['payment']->id);
        $selection = $this->receiptService->selectOrderedPaymentHeadAllocations($reversedPayment);

        $this->assertTrue($selection['is_payment_reversed']);
        $this->assertFalse($selection['used_provenance_fallback']);
        $this->assertFalse($selection['missing_expected_allocations']);
        $this->assertCount(2, $selection['allocations']);

        $this->actingAs($this->adminUser);
        $resMaster = $this->get(route('fees.receipt.pos_print', $collected['receipt_no']));
        $resMaster->assertOk();
        $resMaster->assertSee('VOID / REVERSED RECEIPT');
        $resMaster->assertSee($rev['reversal_reference']);
        $resMaster->assertSee('৳900.00');
        $resMaster->assertSee('৳500.00');
        $resMaster->assertSee('ACTIVE COLLECTED TOTAL:');
        $resMaster->assertSee('৳0.00');
        $resMaster->assertSee('Zero Taka Only.');
        $resMaster->assertDontSee('[ORIG ALLOCATION — REVERSAL STATUS MISSING]');

        $this->get(route('fees.collection.index', ['student_identity' => $student->student_identity]))
            ->assertOk()
            ->assertSee('৳ 0.00')
            ->assertSee('Reversed: ৳1,400.00')
            ->assertDontSee('(Orig. Unsynced)');
    }

    public function test_active_payment_with_only_reversed_allocations_never_falls_back_to_reversed_rows(): void
    {
        $student = $this->createStudent('STD-4B-CORR-ACT');
        $invoice = $this->createAdmissionInvoice($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1000.00',
                $heads[1]->id => '500.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        // Simulate inconsistent historical corruption: payment remains Active while its allocation rows were flipped to Reversed
        DB::table('admission_payment_head_allocations')
            ->where('fee_payment_id', $collected['payment']->id)
            ->update([
                'status' => 'Reversed',
                'reversed_at' => '2026-11-18 10:00:00',
                'reversal_reference' => 'REV-CORRUPT-001',
            ]);

        $payment = FeePayment::with(['invoice.admissionHeadAllocations', 'admissionHeadAllocations'])
            ->findOrFail($collected['payment']->id);

        $selection = $this->receiptService->selectOrderedPaymentHeadAllocations($payment);
        $this->assertFalse($selection['is_payment_reversed']);
        $this->assertFalse($selection['used_provenance_fallback']);
        $this->assertTrue($selection['has_status_mismatch']);
        $this->assertTrue($selection['missing_expected_allocations']);
        $this->assertCount(0, $selection['allocations']);

        $this->actingAs($this->adminUser);

        // Master Receipt MUST NOT render the reversed 1,000.00 or 500.00 head rows as active collection
        $masterRes = $this->get(route('fees.receipt.pos_print', $collected['receipt_no']));
        $masterRes->assertOk();
        $masterRes->assertSee('Active head allocation breakdown unavailable (no Active head allocations matched).');
        $masterRes->assertDontSee('৳1,000.00');
        $masterRes->assertDontSee('৳500.00');

        // Scoped Individual POS MUST display 0.00 per head (NEVER substituting Reversed allocations or invoice totals)
        $indRes = $this->get(route('fees.receipt.pos_print_individual', $collected['receipt_no']));
        $indRes->assertOk();
        $indRes->assertSee('Active head allocation breakdown unavailable (no Active head allocations matched for this receipt).');
        $indData = $indRes->viewData('individualReceiptData')['slip_cards']->first();
        foreach ($indData['head_rows'] as $row) {
            $this->assertEquals('0.00', $row['paid']);
            $this->assertEquals('0.00', $row['reversed_paid']);
        }

        // Collection History MUST NOT display reversed head chips under this Active payment
        $colRes = $this->get(route('fees.collection.index', ['student_identity' => $student->student_identity]));
        $colRes->assertOk();
        $colRes->assertSee('Active head allocation breakdown unavailable (no Active head allocations matched).');
        $colRes->assertDontSee('Admission Form &amp; Registration: ৳1,000.00', false);
        $colRes->assertDontSee('Development &amp; Lab Fee: ৳500.00', false);
    }

    public function test_reversed_payment_with_missing_reversed_allocations_handles_unsynced_and_absent_rows_without_active_leak(): void
    {
        // Case A: Reversed FeePayment whose child allocations still say Active (unsynced provenance fallback on audit copy only)
        $studentA = $this->createStudent('STD-4B-MISSING-REV-A', '01');
        $invoiceA = $this->createAdmissionInvoice($studentA);
        $headsA = $invoiceA->admissionHeadAllocations()->orderBy('sort_order')->get();

        $colA = $this->collectionService->collectSinglePayment($invoiceA->id, [
            'pay_amount' => '1100.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $headsA[0]->id => '700.00',
                $headsA[1]->id => '400.00',
                $headsA[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        // Reversed payment status on fee_payments ONLY (missing Reversed child rows)
        DB::table('fee_payments')->where('id', $colA['payment']->id)->update([
            'status' => 'Reversed',
            'reversed_amount' => '1100.00',
            'reversed_at' => '2026-11-18 11:00:00',
            'reversed_by' => $this->superAdmin->id,
            'reversal_reason' => 'Unsynced legacy reversal test',
            'reversal_reference' => 'REV-UNSYNC-101',
        ]);

        $paymentA = FeePayment::with(['invoice.admissionHeadAllocations', 'admissionHeadAllocations'])->findOrFail($colA['payment']->id);
        $selA = $this->receiptService->selectOrderedPaymentHeadAllocations($paymentA);
        $this->assertTrue($selA['is_payment_reversed']);
        $this->assertTrue($selA['missing_expected_allocations']);
        $this->assertTrue($selA['used_provenance_fallback']);
        $this->assertTrue($selA['has_status_mismatch']);
        $this->assertCount(2, $selA['allocations']);

        $this->actingAs($this->adminUser);
        $masterA = $this->get(route('fees.receipt.pos_print', $colA['receipt_no']));
        $masterA->assertOk();
        $masterA->assertSee('VOID / REVERSED RECEIPT');
        $masterA->assertSee('REV-UNSYNC-101');
        $masterA->assertSee('[ORIG ALLOCATION — REVERSAL STATUS MISSING]');
        $masterA->assertSee('ACTIVE COLLECTED TOTAL:');
        $masterA->assertSee('৳0.00');
        $masterA->assertSee('Zero Taka Only.');

        $indA = $this->get(route('fees.receipt.pos_print_individual', $colA['receipt_no']));
        $indA->assertOk();
        $indA->assertSee('VOID / REVERSED PAYMENT');
        $indA->assertSee('[ORIG UNSYNCED]');
        $indA->assertSee('Active Paid (Slip):');
        $indA->assertSee('৳0.00');
        $cardA = $indA->viewData('individualReceiptData')['slip_cards']->first();
        foreach ($cardA['head_rows'] as $row) {
            $this->assertEquals('0.00', $row['paid']);
        }

        $colIndexA = $this->get(route('fees.collection.index', ['student_identity' => $studentA->student_identity]));
        $colIndexA->assertOk();
        $colIndexA->assertSee('৳ 0.00');
        $colIndexA->assertSee('(Orig. Unsynced)');

        // Case B: Reversed FeePayment with ZERO allocation rows at all
        $studentB = $this->createStudent('STD-4B-MISSING-REV-B', '02');
        $invoiceB = $this->createAdmissionInvoice($studentB);
        $orphanReversedPayment = FeePayment::create([
            'receipt_no' => 'REC-20261118-9944',
            'fee_invoice_id' => $invoiceB->id,
            'student_id' => $studentB->id,
            'branch_id' => $this->branch->id,
            'paid_amount' => '1000.00',
            'payment_date' => '2026-11-18',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);
        DB::table('fee_payments')->where('id', $orphanReversedPayment->id)->update([
            'status' => 'Reversed',
            'reversed_amount' => '1000.00',
            'reversed_at' => '2026-11-18 11:30:00',
            'reversed_by' => $this->superAdmin->id,
            'reversal_reason' => 'Missing child head allocations completely',
            'reversal_reference' => 'REV-ABSENT-202',
        ]);

        $masterB = $this->get(route('fees.receipt.pos_print', 'REC-20261118-9944'));
        $masterB->assertOk();
        $masterB->assertSee('VOID / REVERSED RECEIPT');
        $masterB->assertSee('REV-ABSENT-202');
        $masterB->assertSee('Reversed head allocation rows missing from ledger (active collected: ৳0.00).');
        $masterB->assertSee('ACTIVE COLLECTED TOTAL:');
        $masterB->assertSee('৳0.00');
        $masterB->assertSee('Zero Taka Only.');

        $indB = $this->get(route('fees.receipt.pos_print_individual', 'REC-20261118-9944'));
        $indB->assertOk();
        $indB->assertSee('Reversed head allocation rows missing from ledger (active collected: ৳0.00).');

        $this->get(route('fees.collection.index', ['student_identity' => $studentB->student_identity]))
            ->assertOk()
            ->assertSee('৳ 0.00')
            ->assertSee('Reversed head allocation details missing from ledger.');
    }

    public function test_mixed_active_and_reversed_payments_on_same_admission_invoice_stay_strictly_isolated(): void
    {
        $student = $this->createStudent('STD-4B-MIX-SAMEINV');
        $invoice = $this->createAdmissionInvoice($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        // Payment 1: pays Head 0 (1500.00) -> later REVERSED
        $p1 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'transaction_id' => 'BK-MIX-REV-1',
            'head_allocations' => [
                $heads[0]->id => '1500.00',
                $heads[1]->id => '0.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        // Payment 2: pays Head 1 (700.00) + Head 2 (500.00) = 1200.00 -> stays ACTIVE
        $p2 = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1200.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '0.00',
                $heads[1]->id => '700.00',
                $heads[2]->id => '500.00',
            ],
        ], $this->adminUser->id);

        $rev1 = $this->reversalService->reversePayment(
            $p1['payment']->id,
            'Reverse first receipt while keeping second active',
            $this->superAdmin->id
        );

        $this->actingAs($this->adminUser);

        // Selecting BOTH P1 (Reversed) and P2 (Active) in printSelectedIndividualPos renders 2 distinct cards with exact status & head isolation
        $selRes = $this->post(route('fees.payments.print_selected'), [
            'payment_ids' => [$p1['payment']->id, $p2['payment']->id],
        ]);
        $selRes->assertOk();

        $cards = $selRes->viewData('individualReceiptData')['slip_cards'];
        $this->assertCount(2, $cards);

        $cardRev = $cards[0];
        $this->assertTrue($cardRev['scoped_is_reversed']);
        $this->assertEquals('0.00', $cardRev['scoped_paid_total']);
        $this->assertEquals('1500.00', $cardRev['scoped_reversed_total']);
        $this->assertEquals('1500.00', $cardRev['head_rows'][0]['reversed_paid']);
        $this->assertEquals('0.00', $cardRev['head_rows'][0]['paid']);
        $this->assertEquals('0.00', $cardRev['head_rows'][1]['reversed_paid']);

        $cardAct = $cards[1];
        $this->assertFalse($cardAct['scoped_is_reversed']);
        $this->assertEquals('1200.00', $cardAct['scoped_paid_total']);
        $this->assertEquals('0.00', $cardAct['head_rows'][0]['paid']);
        $this->assertEquals('700.00', $cardAct['head_rows'][1]['paid']);
        $this->assertEquals('500.00', $cardAct['head_rows'][2]['paid']);

        // Active Master Receipt for P2 never sees P1's 1,500.00 or REV reference
        $masterAct = $this->get(route('fees.receipt.pos_print', $p2['receipt_no']));
        $masterAct->assertOk();
        $masterAct->assertSee('৳1,200.00');
        $masterAct->assertDontSee($rev1['reversal_reference']);
        $masterAct->assertDontSee('BK-MIX-REV-1');

        // Reversed Master Receipt for P1 totals 0.00 active and excludes P2
        $masterRev = $this->get(route('fees.receipt.pos_print', $p1['receipt_no']));
        $masterRev->assertOk();
        $masterRev->assertSee('VOID / REVERSED RECEIPT');
        $masterRev->assertSee('৳0.00');
        $masterRev->assertDontSee('৳1,200.00');

        // Collection History isolates each receipt cleanly
        $colRes = $this->get(route('fees.collection.index', ['student_identity' => $student->student_identity]));
        $colRes->assertOk();
        $colRes->assertSee('৳ 1,200.00');
        $colRes->assertSee('Reversed: ৳1,500.00');
    }

    public function test_master_receipt_and_individual_pos_head_totals_reconcile_identically_across_collections(): void
    {
        $student = $this->createStudent('STD-4B-CONSIST');
        $invoice = $this->createAdmissionInvoice($student, [
            'discount_type' => 'fixed',
            'discount_value' => '250.00',
        ]);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        // Active partial payment allocating exact odd paisa across all 3 heads
        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1825.75',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '725.25',
                $heads[1]->id => '600.00',
                $heads[2]->id => '500.50',
            ],
        ], $this->adminUser->id);

        $payment = FeePayment::with($this->receiptService->masterPaymentRelations())
            ->findOrFail($collected['payment']->id);

        $masterData = $this->receiptService->buildMasterReceiptData(collect([$payment]), $collected['receipt_no']);
        $indData = $this->receiptService->buildBulkIndividualReceiptData(collect([$payment]), $collected['receipt_no'], false);
        $slipCard = $indData['slip_cards']->first();

        $masterHeadSum = '0.00';
        foreach ($masterData['active_items'][0]['head_lines'] as $hl) {
            $masterHeadSum = bcadd($masterHeadSum, $hl['allocated_paid'], 2);
        }

        $slipHeadSum = '0.00';
        foreach ($slipCard['head_rows'] as $hr) {
            $slipHeadSum = bcadd($slipHeadSum, $hr['paid'], 2);
        }

        $this->assertEquals('1825.75', $masterData['active_grand_total']);
        $this->assertEquals('1825.75', $slipCard['scoped_paid_total']);
        $this->assertEquals($masterHeadSum, $slipHeadSum);
        $this->assertEquals('1825.75', $masterHeadSum);

        // Verify HTTP outputs match identically
        $this->actingAs($this->adminUser);
        $this->get(route('fees.receipt.pos_print', $collected['receipt_no']))
            ->assertOk()
            ->assertSee('৳1,825.75')
            ->assertSee('৳725.25')
            ->assertSee('৳600.00')
            ->assertSee('৳500.50');

        $this->get(route('fees.receipt.pos_print_individual', $collected['receipt_no']))
            ->assertOk()
            ->assertSee('৳1,825.75')
            ->assertSee('725.25')
            ->assertSee('600.00')
            ->assertSee('500.50');
    }

    public function test_active_payment_with_null_or_missing_allocation_status_is_never_treated_as_active(): void
    {
        $student = $this->createStudent('STD-4B-NULL-ALL');
        $invoice = $this->createAdmissionInvoice($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1800.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1200.00',
                $heads[1]->id => '600.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        // Case 1: Real DB partial-column select omitting `status` (missing attribute -> $alloc->status is null)
        $paymentOmittedStatus = FeePayment::with([
            'student.schoolClass',
            'student.section',
            'student.sessionYear',
            'student.branch',
            'invoice.admissionFeeStructure',
            'invoice.admissionHeadAllocations.category',
            'collector',
            'reversedBy',
            'admissionHeadAllocations' => fn ($q) => $q->select([
                'id',
                'fee_payment_id',
                'fee_invoice_id',
                'student_id',
                'branch_id',
                'admission_invoice_head_allocation_id',
                'fee_category_id',
                'fee_category_name_snapshot',
                'head_name_snapshot',
                'allocated_paid_amount',
            ]),
        ])->findOrFail($collected['payment']->id);

        // Case 2: Explicit `status = null` and non-Active `'Pending'` hydrated on in-memory allocations
        $paymentExplicitNull = FeePayment::with($this->receiptService->masterPaymentRelations())
            ->findOrFail($collected['payment']->id);
        $paymentExplicitNull->admissionHeadAllocations[0]->setRawAttributes(
            array_merge($paymentExplicitNull->admissionHeadAllocations[0]->getAttributes(), ['status' => null])
        );
        $paymentExplicitNull->admissionHeadAllocations[1]->setRawAttributes(
            array_merge($paymentExplicitNull->admissionHeadAllocations[1]->getAttributes(), ['status' => 'Pending'])
        );

        $this->actingAs($this->adminUser);

        foreach ([$paymentOmittedStatus, $paymentExplicitNull] as $paymentCase) {
            $selection = $this->receiptService->selectOrderedPaymentHeadAllocations($paymentCase);
            $this->assertFalse($selection['is_payment_reversed']);
            $this->assertFalse($selection['used_provenance_fallback']);
            $this->assertTrue($selection['has_status_mismatch']);
            $this->assertTrue($selection['missing_expected_allocations']);
            $this->assertCount(0, $selection['allocations']);

            // 1. Master Receipt service + view
            $masterReceipt = $this->receiptService->buildMasterReceiptData(collect([$paymentCase]), $collected['receipt_no']);
            $this->assertEmpty($masterReceipt['active_items'][0]['head_lines']);
            $this->assertTrue($masterReceipt['active_items'][0]['has_alloc_status_mismatch']);

            $masterHtml = view('pages.fees.pos_bulk_receipt', [
                'payments' => $masterReceipt['sorted_payments'],
                'receipt_no' => $collected['receipt_no'],
                'student' => $masterReceipt['student'],
                'collector' => $masterReceipt['collector'],
                'date' => $masterReceipt['date'],
                'isMultipleStudents' => $masterReceipt['is_multiple_students'],
                'masterReceipt' => $masterReceipt,
            ])->render();
            $this->assertStringContainsString('Active head allocation breakdown unavailable (no Active head allocations matched).', $masterHtml);
            $this->assertStringNotContainsString('৳1,200.00', $masterHtml);
            $this->assertStringNotContainsString('৳600.00', $masterHtml);

            // 2. Scoped Single POS service + view (_pos_invoice_card)
            $indData = $this->receiptService->buildBulkIndividualReceiptData(collect([$paymentCase]), $collected['receipt_no'], false);
            $slipCard = $indData['slip_cards']->first();
            $this->assertTrue($slipCard['head_allocation_audit']['has_status_mismatch']);
            foreach ($slipCard['head_rows'] as $row) {
                $this->assertEquals('0.00', $row['paid']);
                $this->assertEquals('0.00', $row['reversed_paid']);
            }

            $indHtml = view('pages.fees.pos_invoice_individual_bulk', [
                'invoices' => $indData['invoices'],
                'receipt_no' => $collected['receipt_no'],
                'slipCards' => $indData['slip_cards'],
                'individualReceiptData' => $indData,
            ])->render();
            $this->assertStringContainsString('Active head allocation breakdown unavailable (no Active head allocations matched for this receipt).', $indHtml);

            // 3. Collection Payment History view
            $historyHtml = view('pages.fees.collection', [
                'errors' => new ViewErrorBag(),
                'student' => $paymentCase->student,
                'invoices' => collect(),
                'paymentHistory' => collect([$collected['receipt_no'] => collect([$paymentCase])]),
                'branches' => collect([$this->branch]),
                'classes' => collect([$this->classOne]),
                'sections' => collect([$this->sectionA]),
                'sessions' => collect([$this->session]),
                'categories' => collect([$this->admissionCat]),
                'bulkInvoices' => collect(),
                'dueStudents' => collect(),
            ])->render();
            $this->assertStringContainsString('Active head allocation breakdown unavailable (no Active head allocations matched).', $historyHtml);
            $this->assertStringNotContainsString('Admission Form &amp; Registration: ৳1,200.00', $historyHtml);
            $this->assertStringNotContainsString('Development &amp; Lab Fee: ৳600.00', $historyHtml);
        }
    }

    public function test_active_payment_with_mixed_explicit_active_and_null_allocations_counts_only_explicit_active_rows(): void
    {
        $student = $this->createStudent('STD-4B-MIX-NULL');
        $invoice = $this->createAdmissionInvoice($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1750.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1000.00',
                $heads[1]->id => '500.00',
                $heads[2]->id => '250.00',
            ],
        ], $this->adminUser->id);

        $payment = FeePayment::with($this->receiptService->masterPaymentRelations())
            ->findOrFail($collected['payment']->id);

        // Head 0 stays explicit 'Active'; Head 1 becomes explicit null; Head 2 omits 'status' attribute completely
        $alloc0 = $payment->admissionHeadAllocations[0];
        $alloc1 = $payment->admissionHeadAllocations[1];
        $alloc2 = $payment->admissionHeadAllocations[2];

        $alloc1->setRawAttributes(array_merge($alloc1->getAttributes(), ['status' => null]));
        $attrs2 = $alloc2->getAttributes();
        unset($attrs2['status']);
        $alloc2->setRawAttributes($attrs2);

        $selection = $this->receiptService->selectOrderedPaymentHeadAllocations($payment);
        $this->assertFalse($selection['is_payment_reversed']);
        $this->assertCount(1, $selection['allocations']);
        $this->assertEquals($alloc0->id, $selection['allocations'][0]->id);
        $this->assertEquals('1000.00', bcadd((string) $selection['allocations'][0]->allocated_paid_amount, '0.00', 2));

        // Master Receipt must include only Head 0 (1,000.00), strictly excluding Head 1 (500.00) and Head 2 (250.00)
        $masterReceipt = $this->receiptService->buildMasterReceiptData(collect([$payment]), $collected['receipt_no']);
        $headLines = $masterReceipt['active_items'][0]['head_lines'];
        $this->assertCount(1, $headLines);
        $this->assertEquals('Admission Form & Registration', $headLines[0]['head_name']);
        $this->assertEquals('Active', $headLines[0]['actual_status']);
        $this->assertEquals('1000.00', $headLines[0]['allocated_paid']);

        $masterHtml = view('pages.fees.pos_bulk_receipt', [
            'payments' => $masterReceipt['sorted_payments'],
            'receipt_no' => $collected['receipt_no'],
            'student' => $masterReceipt['student'],
            'collector' => $masterReceipt['collector'],
            'date' => $masterReceipt['date'],
            'isMultipleStudents' => $masterReceipt['is_multiple_students'],
            'masterReceipt' => $masterReceipt,
        ])->render();
        $this->assertStringContainsString('৳1,000.00', $masterHtml);
        $this->assertStringNotContainsString('৳500.00', $masterHtml);
        $this->assertStringNotContainsString('৳250.00', $masterHtml);

        // Single POS slip must count only Head 0 (1,000.00) and report 0.00 on NULL/missing heads
        $indData = $this->receiptService->buildBulkIndividualReceiptData(collect([$payment]), $collected['receipt_no'], false);
        $slipCard = $indData['slip_cards']->first();
        $this->assertEquals('1000.00', $slipCard['head_rows'][0]['paid']);
        $this->assertEquals('0.00', $slipCard['head_rows'][1]['paid']);
        $this->assertEquals('0.00', $slipCard['head_rows'][2]['paid']);

        $indHtml = view('pages.fees.pos_invoice_individual_bulk', [
            'invoices' => $indData['invoices'],
            'receipt_no' => $collected['receipt_no'],
            'slipCards' => $indData['slip_cards'],
            'individualReceiptData' => $indData,
        ])->render();
        $this->assertStringContainsString('<span class="text-green-700">1,000.00</span>', $indHtml);
        $this->assertStringNotContainsString('<span class="text-green-700">500.00</span>', $indHtml);
        $this->assertStringNotContainsString('<span class="text-green-700">250.00</span>', $indHtml);

        // Payment History must display only Head 0 chip
        $this->actingAs($this->adminUser);
        $historyHtml = view('pages.fees.collection', [
            'errors' => new ViewErrorBag(),
            'student' => $payment->student,
            'invoices' => collect(),
            'paymentHistory' => collect([$collected['receipt_no'] => collect([$payment])]),
            'branches' => collect([$this->branch]),
            'classes' => collect([$this->classOne]),
            'sections' => collect([$this->sectionA]),
            'sessions' => collect([$this->session]),
            'categories' => collect([$this->admissionCat]),
            'bulkInvoices' => collect(),
            'dueStudents' => collect(),
        ])->render();
        $this->assertStringContainsString('Admission Form &amp; Registration: ৳1,000.00', $historyHtml);
        $this->assertStringNotContainsString('Development &amp; Lab Fee: ৳500.00', $historyHtml);
        $this->assertStringNotContainsString('January Tuition Fee: ৳250.00', $historyHtml);
    }

    public function test_reversed_payment_and_schema_non_null_default_behavior_remain_consistent_with_null_guard(): void
    {
        // 1. Verify schema constraint/nullability/default directly without modifying schema
        $columns = collect(Schema::getColumns('admission_payment_head_allocations'))->keyBy('name');
        $statusColumn = $columns->get('status');
        $this->assertNotNull($statusColumn);
        $this->assertFalse((bool) ($statusColumn['nullable'] ?? true));
        $this->assertStringContainsString('Active', (string) ($statusColumn['default'] ?? ''));

        $student = $this->createStudent('STD-4B-REV-NULLGUARD');
        $invoice = $this->createAdmissionInvoice($student);
        $heads = $invoice->admissionHeadAllocations()->orderBy('sort_order')->get();

        $collected = $this->collectionService->collectSinglePayment($invoice->id, [
            'pay_amount' => '1300.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '900.00',
                $heads[1]->id => '400.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        $this->reversalService->reversePayment(
            $collected['payment']->id,
            'Verify mixed Reversed + NULL isolation and clean Reversed audit behavior',
            $this->superAdmin->id
        );

        // 2. Mixed Reversed (900.00) + NULL (400.00) on Reversed payment: accepts ONLY explicit Reversed row
        $mixedRevPayment = FeePayment::with($this->receiptService->masterPaymentRelations())
            ->findOrFail($collected['payment']->id);
        $revAlloc1 = $mixedRevPayment->admissionHeadAllocations[1];
        $revAlloc1->setRawAttributes(array_merge($revAlloc1->getAttributes(), ['status' => null]));

        $selMixed = $this->receiptService->selectOrderedPaymentHeadAllocations($mixedRevPayment);
        $this->assertTrue($selMixed['is_payment_reversed']);
        $this->assertFalse($selMixed['used_provenance_fallback']);
        $this->assertCount(1, $selMixed['allocations']);
        $this->assertEquals('900.00', bcadd((string) $selMixed['allocations'][0]->allocated_paid_amount, '0.00', 2));

        $masterMixed = $this->receiptService->buildMasterReceiptData(collect([$mixedRevPayment]), $collected['receipt_no']);
        $this->assertEquals('0.00', $masterMixed['active_grand_total']);
        $this->assertCount(1, $masterMixed['reversed_items'][0]['head_lines']);

        $indMixed = $this->receiptService->buildBulkIndividualReceiptData(collect([$mixedRevPayment]), $collected['receipt_no'], false);
        $cardMixed = $indMixed['slip_cards']->first();
        $this->assertTrue($cardMixed['scoped_is_reversed']);
        $this->assertEquals('0.00', $cardMixed['head_rows'][0]['paid']);
        $this->assertEquals('900.00', $cardMixed['head_rows'][0]['reversed_paid']);
        $this->assertEquals('0.00', $cardMixed['head_rows'][1]['reversed_paid']);

        // 3. Reversed payment with ONLY NULL-status allocations never gets treated as unsynced Active provenance
        $onlyNullRevPayment = FeePayment::with($this->receiptService->masterPaymentRelations())
            ->findOrFail($collected['payment']->id);
        foreach ($onlyNullRevPayment->admissionHeadAllocations as $a) {
            $a->setRawAttributes(array_merge($a->getAttributes(), ['status' => null]));
        }

        $selOnlyNull = $this->receiptService->selectOrderedPaymentHeadAllocations($onlyNullRevPayment);
        $this->assertTrue($selOnlyNull['is_payment_reversed']);
        $this->assertTrue($selOnlyNull['missing_expected_allocations']);
        $this->assertFalse($selOnlyNull['used_provenance_fallback']);
        $this->assertCount(0, $selOnlyNull['allocations']);

        $this->actingAs($this->adminUser);
        $masterNullRev = $this->receiptService->buildMasterReceiptData(collect([$onlyNullRevPayment]), $collected['receipt_no']);
        $masterNullRevHtml = view('pages.fees.pos_bulk_receipt', [
            'payments' => $masterNullRev['sorted_payments'],
            'receipt_no' => $collected['receipt_no'],
            'student' => $masterNullRev['student'],
            'collector' => $masterNullRev['collector'],
            'date' => $masterNullRev['date'],
            'isMultipleStudents' => $masterNullRev['is_multiple_students'],
            'masterReceipt' => $masterNullRev,
        ])->render();
        $this->assertStringContainsString('Reversed head allocation rows missing from ledger (active collected: ৳0.00).', $masterNullRevHtml);
        $this->assertStringNotContainsString('[ORIG ALLOCATION — REVERSAL STATUS MISSING]', $masterNullRevHtml);

        $indNullRev = $this->receiptService->buildBulkIndividualReceiptData(collect([$onlyNullRevPayment]), $collected['receipt_no'], false);
        $indNullRevHtml = view('pages.fees.pos_invoice_individual_bulk', [
            'invoices' => $indNullRev['invoices'],
            'receipt_no' => $collected['receipt_no'],
            'slipCards' => $indNullRev['slip_cards'],
            'individualReceiptData' => $indNullRev,
        ])->render();
        $this->assertStringContainsString('Reversed head allocation rows missing from ledger (active collected: ৳0.00).', $indNullRevHtml);
        $this->assertStringNotContainsString('[ORIG UNSYNCED]', $indNullRevHtml);

        $historyNullRevHtml = view('pages.fees.collection', [
            'errors' => new ViewErrorBag(),
            'student' => $onlyNullRevPayment->student,
            'invoices' => collect(),
            'paymentHistory' => collect([$collected['receipt_no'] => collect([$onlyNullRevPayment])]),
            'branches' => collect([$this->branch]),
            'classes' => collect([$this->classOne]),
            'sections' => collect([$this->sectionA]),
            'sessions' => collect([$this->session]),
            'categories' => collect([$this->admissionCat]),
            'bulkInvoices' => collect(),
            'dueStudents' => collect(),
        ])->render();
        $this->assertStringContainsString('Reversed head allocation details missing from ledger.', $historyNullRevHtml);
        $this->assertStringNotContainsString('(Orig. Unsynced)', $historyNullRevHtml);
    }
}
