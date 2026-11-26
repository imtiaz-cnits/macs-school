<?php

namespace Tests\Feature;

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
use App\Models\StudentCustomFee;
use App\Models\User;
use App\Services\AdmissionFeeReportService;
use App\Services\ClassWiseFinancialReportService;
use App\Services\LateFineService;
use Carbon\Carbon;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LateFineAndBulkCollectionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Branch $branch;
    private SessionYear $session2026;
    private SessionYear $session2027;
    private Classes $classPlay;
    private Section $sectionA;
    private FeeCategory $monthlyCat;
    private FeeCategory $termExamCat;
    private FeeCategory $monthlyExamCat;
    private FeeSetup $monthlySetup2026;
    private FeeSetup $monthlySetup2027;
    private FeeSetup $termExamSetup2026;
    private FeeSetup $monthlyExamSetup2026;
    private Student $studentA;
    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'Admin Tester',
            'email'    => 'admin_embedded_lf@macsschool.com',
            'password' => bcrypt('secret'),
        ]);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $this->adminUser->assignRole($adminRole);
        $this->actingAs($this->adminUser);

        $this->branch = Branch::create([
            'branch_name' => 'Jalalpur Branch',
            'status'      => 'Active',
            'user_id'     => $this->adminUser->id,
        ]);

        $this->session2026 = SessionYear::create([
            'session_name' => '2026',
            'status'       => 'Active',
            'user_id'      => $this->adminUser->id,
        ]);

        $this->session2027 = SessionYear::create([
            'session_name' => '2027',
            'status'       => 'Active',
            'user_id'      => $this->adminUser->id,
        ]);

        $this->classPlay = Classes::create([
            'class_name' => 'Play',
            'status'     => 'Active',
            'user_id'    => $this->adminUser->id,
        ]);

        $this->sectionA = Section::create([
            'section_name' => 'A',
            'class_id'     => $this->classPlay->id,
            'status'       => 'Active',
            'user_id'      => $this->adminUser->id,
        ]);

        $this->monthlyCat = FeeCategory::create([
            'name'    => 'Monthly Fee',
            'status'  => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->termExamCat = FeeCategory::create([
            'name'    => 'Term Exam Fee',
            'status'  => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->monthlyExamCat = FeeCategory::create([
            'name'    => 'Monthly Exam Fee',
            'status'  => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->monthlySetup2026 = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classPlay->id,
            'fee_category_id' => $this->monthlyCat->id,
            'fee_month'       => 'Monthly',
            'amount'          => 900.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->monthlySetup2027 = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2027->id,
            'class_id'        => $this->classPlay->id,
            'fee_category_id' => $this->monthlyCat->id,
            'fee_month'       => 'Monthly',
            'amount'          => 900.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->termExamSetup2026 = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classPlay->id,
            'fee_category_id' => $this->termExamCat->id,
            'fee_month'       => 'November',
            'amount'          => 400.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->monthlyExamSetup2026 = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classPlay->id,
            'fee_category_id' => $this->monthlyExamCat->id,
            'fee_month'       => 'March',
            'amount'          => 200.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->studentA = $this->createStudent('26010100001', 'Azmira Khatun', 1);
        $this->studentB = $this->createStudent('26010100002', 'Nusrat Jahan', 2);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createStudent(string $identity, string $name, int $roll): Student
    {
        return Student::create([
            'student_identity'       => $identity,
            'student_name'           => $name,
            'roll_number'            => $roll,
            'dob'                    => '2018-01-01',
            'gender'                 => 'Female',
            'religion'               => 'Islam',
            'father_name'            => 'Father ' . $roll,
            'father_mobile'          => '0171000000' . $roll,
            'mother_name'            => 'Mother ' . $roll,
            'mother_mobile'          => '0172000000' . $roll,
            'guardian_name'          => 'Guardian ' . $roll,
            'guardian_mobile'        => '0171000000' . $roll,
            'branch_id'              => $this->branch->id,
            'session_year_id'        => $this->session2026->id,
            'class_id'               => $this->classPlay->id,
            'section_id'             => $this->sectionA->id,
            'status'                 => 'Active',
            'sms_status'             => 'Active',
            'present_village'        => 'Jalalpur',
            'present_post_office'    => 'Jalalpur',
            'present_district'       => 'Pabna',
            'present_division'       => 'Rajshahi',
            'permanent_village'      => 'Jalalpur',
            'permanent_post_office'  => 'Jalalpur',
            'permanent_district'     => 'Pabna',
            'permanent_division'     => 'Rajshahi',
            'user_id'                => $this->adminUser->id,
        ]);
    }

    private function createInvoice(
        Student $student,
        FeeSetup $setup,
        string $invoiceNo,
        string $dueDate,
        float $amount = 900.00,
        array $overrides = []
    ): FeeInvoice {
        return FeeInvoice::create(array_merge([
            'invoice_no'      => $invoiceNo,
            'student_id'      => $student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $setup->session_year_id,
            'fee_setup_id'    => $setup->id,
            'amount'          => $amount,
            'discount'        => 0.00,
            'net_amount'      => $amount,
            'fine_amount'     => 0.00,
            'paid_amount'     => 0.00,
            'due_amount'      => $amount,
            'status'          => 'Unpaid',
            'due_date'        => $dueDate,
            'user_id'         => $this->adminUser->id,
        ], $overrides));
    }

    public function test_non_monthly_paid_partial_cancelled_and_zero_net_invoices_reject_manual_late_fine_with_422(): void
    {
        $unpaidMonthly = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202611-0001', '2026-11-10', 900.00);
        $termExam = $this->createInvoice($this->studentA, $this->termExamSetup2026, 'INV-202611-0002', '2026-11-10', 400.00);
        $monthlyExam = $this->createInvoice($this->studentA, $this->monthlyExamSetup2026, 'INV-202603-0003', '2026-03-10', 200.00);
        $paidMonthly = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202610-0001', '2026-10-10', 900.00, [
            'paid_amount' => 900.00,
            'due_amount'  => 0.00,
            'status'      => 'Paid',
        ]);
        $partialMonthly = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202609-0001', '2026-09-10', 900.00, [
            'paid_amount' => 500.00,
            'due_amount'  => 400.00,
            'status'      => 'Partial',
        ]);
        $cancelledMonthly = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202608-0001', '2026-08-10', 900.00, [
            'due_amount' => 0.00,
            'status'     => 'Cancelled',
        ]);
        $zeroNetMonthly = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202607-0001', '2026-07-10', 900.00, [
            'discount'   => 900.00,
            'net_amount' => 0.00,
            'due_amount' => 0.00,
            'status'     => 'Unpaid',
        ]);

        foreach ([$termExam, $monthlyExam, $paidMonthly, $partialMonthly, $cancelledMonthly, $zeroNetMonthly] as $blockedInv) {
            $res = $this->postJson(route('fees.invoice.late_fine', $blockedInv->id), [
                'amount' => 20.00,
                'reason' => 'Attempt on ineligible invoice',
            ]);
            $res->assertStatus(422)->assertJsonPath('success', false);
            $blockedInv->refresh();
            $this->assertEquals('0.00', $blockedInv->fineAmountDecimal());
        }

        // Collection UI hides Late Fine option on Term Exam Fee & Partial Monthly, and enables it on Unpaid Monthly Fee
        $pageRes = $this->get(route('fees.collection.index', [
            'student_identity' => $this->studentA->student_identity,
        ]));
        $pageRes->assertOk();
        $html = $pageRes->getContent();
        $this->assertStringContainsString('id="adjust-btn-' . $unpaidMonthly->id . '"', $html);
        $this->assertMatchesRegularExpression(
            '/id="adjust-btn-' . $unpaidMonthly->id . '"[^>]*data-can-manage-late-fine="1"/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/id="adjust-btn-' . $termExam->id . '"[^>]*data-can-manage-late-fine="0"/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/id="adjust-btn-' . $partialMonthly->id . '"[^>]*data-can-manage-late-fine="0"/s',
            $html
        );
    }

    public function test_adding_and_updating_manual_late_fine_embeds_on_monthly_fee_without_creating_separate_invoice(): void
    {
        $marchInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202603-0001', '2026-03-10', 900.00);
        $initialCount = FeeInvoice::count();

        // 1. Add embedded manual late fine (৳20)
        $addRes = $this->postJson(route('fees.invoice.late_fine', $marchInv->id), [
            'amount' => 20.00,
            'reason' => 'Late payment after 7th',
        ]);
        $addRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invoice.net_amount', '900.00')
            ->assertJsonPath('invoice.fine_amount', '20.00')
            ->assertJsonPath('invoice.due_amount', '920.00')
            ->assertJsonPath('invoice.fine_source', 'manual');

        // Strictly NO separate Fine invoice created
        $this->assertSame($initialCount, FeeInvoice::count());

        $marchInv->refresh();
        $this->assertEquals('900.00', bcadd((string) $marchInv->net_amount, '0.00', 2));
        $this->assertEquals('20.00', $marchInv->fineAmountDecimal());
        $this->assertEquals('920.00', bcadd((string) $marchInv->due_amount, '0.00', 2));
        $this->assertSame('manual', $marchInv->fine_source);
        $this->assertSame('Late payment after 7th', $marchInv->fine_reason);

        // 2. Update embedded manual late fine (৳40)
        $updRes = $this->postJson(route('fees.invoice.late_fine', $marchInv->id), [
            'amount' => 40.00,
            'reason' => 'Revised manual fine',
        ]);
        $updRes->assertOk()
            ->assertJsonPath('invoice.net_amount', '900.00')
            ->assertJsonPath('invoice.fine_amount', '40.00')
            ->assertJsonPath('invoice.due_amount', '940.00');

        $this->assertSame($initialCount, FeeInvoice::count());

        // 3. Inline UI shows Late Fine badge & Waive Fine button on the Monthly Fee row itself
        $pageRes = $this->get(route('fees.collection.index', [
            'student_identity' => $this->studentA->student_identity,
        ]));
        $pageRes->assertOk();
        $pageRes->assertSee('+ ৳40.00 Late Fine');
        $pageRes->assertSee('+ ৳40.00 Fine');
        $pageRes->assertSee('940.00');
        $pageRes->assertSee('Waive Fine');
    }

    public function test_single_click_and_bulk_payment_split_fine_amount_and_itemize_single_and_master_pos_receipts(): void
    {
        $marchInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202603-0001', '2026-03-10', 900.00);
        $aprilInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202604-0001', '2026-04-10', 900.00);

        $lateFineService = app(LateFineService::class);
        $lateFineService->applyManualLateFine($marchInv, 20.00, 'March late fine', $this->adminUser->id);
        $lateFineService->applyManualLateFine($aprilInv, 30.00, 'April late fine', $this->adminUser->id);

        // 1. Single-click payment via fees.collection.store (৳920.00)
        $singleRes = $this->postJson(route('fees.collection.store'), [
            'invoice_id'     => $marchInv->id,
            'pay_amount'     => '920.00',
            'payment_method' => 'Cash',
            'payment_date'   => '2026-03-12',
        ]);
        $singleRes->assertOk()->assertJsonPath('success', true);
        $singleReceiptNo = $singleRes->json('receipt_no');

        $marchInv->refresh();
        $this->assertSame('Paid', $marchInv->status);
        $this->assertEquals('900.00', bcadd((string) $marchInv->net_amount, '0.00', 2));
        $this->assertEquals('20.00', $marchInv->fineAmountDecimal());
        $this->assertEquals('920.00', bcadd((string) $marchInv->paid_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $marchInv->due_amount, '0.00', 2));

        $singlePayment = FeePayment::where('fee_invoice_id', $marchInv->id)->firstOrFail();
        $this->assertEquals('920.00', bcadd((string) $singlePayment->paid_amount, '0.00', 2));
        $this->assertEquals('20.00', $singlePayment->fineAmountDecimal());
        $this->assertEquals('900.00', $singlePayment->tuitionAmountDecimal());

        // Single POS Receipt itemizes Monthly Fee (900.00) and Late Fine (20.00) totaling ৳920.00
        $posSingle = $this->get(route('fees.invoice.pos_print', $marchInv->id));
        $posSingle->assertOk();
        $posSingle->assertSee('Monthly Fee');
        $posSingle->assertSee('Late Fine');
        $posSingle->assertSee('900.00');
        $posSingle->assertSee('20.00');
        $posSingle->assertSee('920.00');
        $posSingle->assertSee('Nine Hundred Twenty Taka Only.');

        // Master POS Receipt for single payment also itemizes Monthly Fee (৳900.00) & Late Fine (৳20.00)
        $posMasterSingle = $this->get(route('fees.receipt.pos_print', $singleReceiptNo));
        $posMasterSingle->assertOk();
        $posMasterSingle->assertSee('Monthly Fee');
        $posMasterSingle->assertSee('Late Fine');
        $posMasterSingle->assertSee('900.00');
        $posMasterSingle->assertSee('20.00');
        $posMasterSingle->assertSee('920.00');

        // 2. Bulk single-student collection via fees.collection.bulk_store (AdmissionCollectionService legacy path)
        $bulkRes = $this->postJson(route('fees.collection.bulk_store'), [
            'invoice_ids'    => [$aprilInv->id],
            'payment_method' => 'bKash',
            'payment_date'   => '2026-04-12',
        ]);
        $bulkRes->assertOk()->assertJsonPath('success', true);
        $masterReceiptNo = $bulkRes->json('master_receipt_no');

        $aprilInv->refresh();
        $this->assertSame('Paid', $aprilInv->status);
        $this->assertEquals('930.00', bcadd((string) $aprilInv->paid_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $aprilInv->due_amount, '0.00', 2));

        $aprilPayment = FeePayment::where('fee_invoice_id', $aprilInv->id)->firstOrFail();
        $this->assertEquals('930.00', bcadd((string) $aprilPayment->paid_amount, '0.00', 2));
        $this->assertEquals('30.00', $aprilPayment->fineAmountDecimal());

        $posMasterBulk = $this->get(route('fees.receipt.pos_print', $masterReceiptNo));
        $posMasterBulk->assertOk();
        $posMasterBulk->assertSee('Monthly Fee');
        $posMasterBulk->assertSee('Late Fine');
        $posMasterBulk->assertSee('900.00');
        $posMasterBulk->assertSee('30.00');
        $posMasterBulk->assertSee('930.00');
    }

    public function test_payment_reversal_discontinuation_and_invoice_restoration_preserve_embedded_fine_invariants(): void
    {
        $marchInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202603-0001', '2026-03-10', 900.00);
        $aprilInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202604-0001', '2026-04-10', 900.00);

        $lateFineService = app(LateFineService::class);
        $lateFineService->applyManualLateFine($marchInv, 20.00, 'March late fine', $this->adminUser->id);
        $lateFineService->applyManualLateFine($aprilInv, 20.00, 'April late fine', $this->adminUser->id);

        // 1. Pay March (৳920.00) then reverse it — must cleanly restore due_amount = 920.00 without LogicException
        $this->postJson(route('fees.collection.store'), [
            'invoice_id'     => $marchInv->id,
            'pay_amount'     => '920.00',
            'payment_method' => 'Cash',
            'payment_date'   => '2026-03-12',
        ])->assertOk();

        $payment = FeePayment::where('fee_invoice_id', $marchInv->id)->firstOrFail();

        $revRes = $this->postJson(route('fees.payments.reverse', $payment->id), [
            'reason' => 'Cashier entered wrong receipt',
        ]);
        $revRes->assertOk()->assertJsonPath('success', true);

        $marchInv->refresh();
        $payment->refresh();
        $this->assertSame('Reversed', $payment->status);
        $this->assertEquals('20.00', $payment->fineAmountDecimal());
        $this->assertSame('Unpaid', $marchInv->status);
        $this->assertEquals('0.00', bcadd((string) $marchInv->paid_amount, '0.00', 2));
        $this->assertEquals('20.00', $marchInv->fineAmountDecimal());
        $this->assertEquals('920.00', bcadd((string) $marchInv->due_amount, '0.00', 2));

        // 2. Discontinue student after March 2026 — cancels April invoice (due 920.00 -> 0.00) with zero orphan fines
        $discRes = $this->postJson(route('students.discontinue.reconcile', ['id' => $this->studentA->id]), [
            'last_active_month' => '2026-03',
            'reason'            => 'Transferred out',
            'confirm'           => true,
        ]);
        $discRes->assertOk()
            ->assertJson([
                'status'           => 'success',
                'cancelled_count'  => 1,
                'cancelled_amount' => 920.00,
            ]);

        $aprilInv->refresh();
        $this->assertSame('Cancelled', $aprilInv->status);
        $this->assertEquals('0.00', bcadd((string) $aprilInv->due_amount, '0.00', 2));
        $this->assertEquals('20.00', $aprilInv->fineAmountDecimal());

        // 3. Restore cancelled April invoice — restores exact due_amount = 920.00 (net 900 + fine 20)
        $restoreRes = $this->postJson(route('fees.invoice.restore', $aprilInv->id), [
            'reason'     => 'Student returned to school',
            'confirm'    => true,
            'student_id' => $this->studentA->id,
        ]);
        $restoreRes->assertOk()->assertJsonPath('success', true);

        $aprilInv->refresh();
        $this->assertSame('Unpaid', $aprilInv->status);
        $this->assertEquals('900.00', bcadd((string) $aprilInv->net_amount, '0.00', 2));
        $this->assertEquals('20.00', $aprilInv->fineAmountDecimal());
        $this->assertEquals('920.00', bcadd((string) $aprilInv->due_amount, '0.00', 2));
    }

    public function test_waiving_fine_requires_reason_respects_pre_vs_post_8th_auto_waive_rule_and_blocks_paid_fine(): void
    {
        $jan2027Inv = $this->createInvoice($this->studentA, $this->monthlySetup2027, 'INV-202701-0001', '2027-01-10', 900.00);
        $lateFineService = app(LateFineService::class);

        // 1. Pre-8th accidental manual fine waiver (2027-01-04) MUST leave `auto_fine_waived_at` NULL
        Carbon::setTestNow(Carbon::create(2027, 1, 4, 10, 0, 0, 'Asia/Dhaka'));
        $lateFineService->applyManualLateFine($jan2027Inv, 20.00, 'Accidental manual fine', $this->adminUser->id);

        // Short reason returns 422
        $this->postJson(route('fees.invoice.waive_fine', $jan2027Inv->id), [
            'reason' => 'no',
        ])->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->postJson(route('fees.invoice.waive_fine', $jan2027Inv->id), [
            'reason' => 'Undo accidental fine before due date',
        ])->assertOk()->assertJsonPath('success', true);

        $jan2027Inv->refresh();
        $this->assertEquals('0.00', $jan2027Inv->fineAmountDecimal());
        $this->assertNull($jan2027Inv->fine_source);
        $this->assertNull($jan2027Inv->fine_reason);
        $this->assertEquals('900.00', bcadd((string) $jan2027Inv->due_amount, '0.00', 2));
        $this->assertNull($jan2027Inv->auto_fine_waived_at);

        // 2. Post-8th manual fine waiver (2027-01-09) MUST stamp `auto_fine_waived_at`
        Carbon::setTestNow(Carbon::create(2027, 1, 9, 10, 0, 0, 'Asia/Dhaka'));
        $lateFineService->applyManualLateFine($jan2027Inv, 20.00, 'Post-8th manual fine', $this->adminUser->id);

        $this->postJson(route('fees.invoice.waive_fine', $jan2027Inv->id), [
            'reason' => 'Principal approved late fine waiver',
        ])->assertOk()->assertJsonPath('success', true);

        $jan2027Inv->refresh();
        $this->assertEquals('0.00', $jan2027Inv->fineAmountDecimal());
        $this->assertEquals('900.00', bcadd((string) $jan2027Inv->due_amount, '0.00', 2));
        $this->assertNotNull($jan2027Inv->auto_fine_waived_at);

        // 3. Auto-applied fine waiver always stamps `auto_fine_waived_at`
        $feb2027Inv = $this->createInvoice($this->studentA, $this->monthlySetup2027, 'INV-202702-0001', '2027-02-10', 900.00);
        DB::table('fee_invoices')->where('id', $feb2027Inv->id)->update([
            'fine_amount'          => 20.00,
            'fine_source'          => 'auto',
            'fine_reason'          => 'Automatic monthly late fine',
            'auto_fine_applied_at' => '2027-02-08 00:15:00',
            'due_amount'           => 920.00,
        ]);
        Carbon::setTestNow(Carbon::create(2027, 2, 8, 9, 0, 0, 'Asia/Dhaka'));
        $this->postJson(route('fees.invoice.waive_fine', $feb2027Inv->id), [
            'reason' => 'Medical emergency exemption',
        ])->assertOk();

        $feb2027Inv->refresh();
        $this->assertEquals('0.00', $feb2027Inv->fineAmountDecimal());
        $this->assertNotNull($feb2027Inv->auto_fine_waived_at);

        // 4. Paid invoice with fine CANNOT be waived (returns 422)
        $lateFineService->applyManualLateFine($feb2027Inv, 20.00, 'Re-applied fine', $this->adminUser->id);
        $this->postJson(route('fees.collection.store'), [
            'invoice_id'     => $feb2027Inv->id,
            'pay_amount'     => '920.00',
            'payment_method' => 'Cash',
            'payment_date'   => '2027-02-10',
        ])->assertOk();

        $this->postJson(route('fees.invoice.waive_fine', $feb2027Inv->id), [
            'reason' => 'Attempt waiver after payment',
        ])->assertStatus(422)->assertJsonPath('success', false);

        $feb2027Inv->refresh();
        $this->assertSame('Paid', $feb2027Inv->status);
        $this->assertEquals('20.00', $feb2027Inv->fineAmountDecimal());
    }

    public function test_financial_reports_keep_monthly_fee_pure_and_route_embedded_fines_to_other_fees_and_fine(): void
    {
        $marchInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202603-0001', '2026-03-10', 900.00);
        $aprilInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202604-0001', '2026-04-10', 900.00);

        $lateFineService = app(LateFineService::class);
        $lateFineService->applyManualLateFine($marchInv, 20.00, 'March late fine', $this->adminUser->id);
        $lateFineService->applyManualLateFine($aprilInv, 30.00, 'April late fine', $this->adminUser->id);

        // Collect March in full (৳920 = 900 tuition + 20 fine); leave April unpaid (৳930 = 900 tuition + 30 fine)
        $this->postJson(route('fees.collection.store'), [
            'invoice_id'     => $marchInv->id,
            'pay_amount'     => '920.00',
            'payment_method' => 'Cash',
            'payment_date'   => '2026-03-12',
        ])->assertOk();

        $reportService = app(ClassWiseFinancialReportService::class);

        // 1. Class-wise Monthly Report (March 2026 — Paid)
        $marchReport = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'March',
            'year'            => 2026,
        ]);
        $this->assertEquals('900.00', $marchReport['grand_total']['due']['monthly_fee']);
        $this->assertEquals('20.00', $marchReport['grand_total']['due']['other_fees']);
        $this->assertEquals('920.00', $marchReport['grand_total']['due']['total']);
        $this->assertEquals('900.00', $marchReport['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('20.00', $marchReport['grand_total']['collected']['other_fees']);
        $this->assertEquals('920.00', $marchReport['grand_total']['collected']['total']);
        $this->assertEquals('0.00', $marchReport['grand_total']['outstanding']['total']);

        // 2. Class-wise Monthly Report (April 2026 — Unpaid)
        $aprilReport = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'April',
            'year'            => 2026,
        ]);
        $this->assertEquals('900.00', $aprilReport['grand_total']['due']['monthly_fee']);
        $this->assertEquals('30.00', $aprilReport['grand_total']['due']['other_fees']);
        $this->assertEquals('930.00', $aprilReport['grand_total']['due']['total']);
        $this->assertEquals('0.00', $aprilReport['grand_total']['collected']['total']);
        $this->assertEquals('900.00', $aprilReport['grand_total']['outstanding']['monthly_fee']);
        $this->assertEquals('30.00', $aprilReport['grand_total']['outstanding']['other_fees']);
        $this->assertEquals('930.00', $aprilReport['grand_total']['outstanding']['total']);

        // 3. Class-wise Full Session Report (Reconciles identically with March + April!)
        $fullReport = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'all',
        ]);
        $this->assertEquals('1800.00', $fullReport['grand_total']['due']['monthly_fee']);
        $this->assertEquals('50.00', $fullReport['grand_total']['due']['other_fees']);
        $this->assertEquals('1850.00', $fullReport['grand_total']['due']['total']);
        $this->assertEquals('900.00', $fullReport['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('20.00', $fullReport['grand_total']['collected']['other_fees']);
        $this->assertEquals('920.00', $fullReport['grand_total']['collected']['total']);
        $this->assertEquals('900.00', $fullReport['grand_total']['outstanding']['monthly_fee']);
        $this->assertEquals('30.00', $fullReport['grand_total']['outstanding']['other_fees']);
        $this->assertEquals('930.00', $fullReport['grand_total']['outstanding']['total']);

        // 4. Class-wise Custom Date Range Model 1 (2026-03-01 to 2026-03-31)
        $customReport = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-03-01',
            'end_date'        => '2026-03-31',
        ]);
        $this->assertEquals('900.00', $customReport['grand_total']['due']['monthly_fee']);
        $this->assertEquals('20.00', $customReport['grand_total']['due']['other_fees']);
        $this->assertEquals('900.00', $customReport['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('20.00', $customReport['grand_total']['collected']['other_fees']);

        // 5. AdmissionFeeReportService category split (drives FeeReportController & FinancialReportController)
        $marchPayment = FeePayment::where('fee_invoice_id', $marchInv->id)->with('invoice.feeSetup.category')->firstOrFail();
        $catSplit = app(AdmissionFeeReportService::class)->aggregateActivePaymentCategoryAmounts($marchPayment);
        $this->assertEquals('920.00', $catSplit['effective_total']);
        $this->assertEquals('900.00', $catSplit['by_category']['Monthly Fee'] ?? '0.00');
        $this->assertEquals('20.00', $catSplit['by_category']['Fine'] ?? '0.00');

        // 6. FeeReportController::summaryReport splits Monthly Fee & Fine cleanly
        $summaryRes = $this->get(route('fees.reports.summary', [
            'session_year_id' => $this->session2026->id,
        ]));
        $summaryRes->assertOk();
        $categorySummary = $summaryRes->viewData('categorySummary');
        $this->assertEquals('1800.00', $categorySummary->get('Monthly Fee')->total_net);
        $this->assertEquals('900.00', $categorySummary->get('Monthly Fee')->total_paid);
        $this->assertEquals('900.00', $categorySummary->get('Monthly Fee')->total_due);
        $this->assertEquals('50.00', $categorySummary->get('Fine')->total_net);
        $this->assertEquals('20.00', $categorySummary->get('Fine')->total_paid);
        $this->assertEquals('30.00', $categorySummary->get('Fine')->total_due);

        // 7. FinancialReportController includes fine in incomeByCategory & totalBilledNet
        $finRes = $this->get(url('/accounts/reports?start_date=2026-03-01&end_date=2026-03-31'));
        $finRes->assertOk();
        $this->assertEquals('920.00', $finRes->viewData('totalIncome'));
        $incomeByCat = collect($finRes->viewData('incomeByCategory'))->keyBy('name');
        $this->assertEquals('900.00', $incomeByCat['Monthly Fee']['amount']);
        $this->assertEquals('20.00', $incomeByCat['Fine']['amount']);
    }

    public function test_bulk_students_store_partial_payment_and_adjust_invoice_fee_preserve_embedded_fine(): void
    {
        $invA = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202603-0001', '2026-03-10', 900.00);
        $invB = $this->createInvoice($this->studentB, $this->monthlySetup2026, 'INV-202603-0002', '2026-03-10', 900.00);
        $invA2 = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202604-0001', '2026-04-10', 900.00);

        // Embed ৳20 fine on invA (total due 920.00)
        app(LateFineService::class)->applyManualLateFine($invA, 20.00, 'March fine', $this->adminUser->id);

        // 1. adjustInvoiceFee (continuous decrease by 100 -> net 800.00) preserves embedded ৳20 fine on invA (due 820.00)
        $adjRes = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invA->id,
            'mode'       => 'decrease',
            'amount'     => 100.00,
            'scope'      => 'continuous',
            'reason'     => 'Sibling scholarship discount',
        ]);
        $adjRes->assertOk()->assertJsonPath('success', true);

        $invA->refresh();
        $invA2->refresh();
        $this->assertEquals('800.00', bcadd((string) $invA->net_amount, '0.00', 2));
        $this->assertEquals('20.00', $invA->fineAmountDecimal());
        $this->assertEquals('820.00', bcadd((string) $invA->due_amount, '0.00', 2));
        $this->assertEquals('800.00', bcadd((string) $invA2->due_amount, '0.00', 2));

        // StudentCustomFee stores strictly pure tuition (800.00, NEVER 820.00!)
        $customFee = StudentCustomFee::where('student_id', $this->studentA->id)
            ->where('fee_category_id', $this->monthlyCat->id)
            ->firstOrFail();
        $this->assertEquals('800.00', bcadd((string) $customFee->amount, '0.00', 2));

        // 2. bulkStudentsStore partial payment (500 on invA due 820 -> due 320; full 900 on invB)
        $res1 = $this->postJson(route('fees.collection.bulk_students_store'), [
            'invoice_ids'    => [$invA->id, $invB->id],
            'paying_amounts' => [
                $invA->id => '500.00',
                $invB->id => '900.00',
            ],
            'payment_method' => 'Cash',
            'payment_date'   => '2026-03-12',
        ]);
        $res1->assertOk()->assertJsonPath('success', true);

        $invA->refresh();
        $this->assertSame('Partial', $invA->status);
        $this->assertEquals('800.00', bcadd((string) $invA->net_amount, '0.00', 2));
        $this->assertEquals('20.00', $invA->fineAmountDecimal());
        $this->assertEquals('500.00', bcadd((string) $invA->paid_amount, '0.00', 2));
        $this->assertEquals('320.00', bcadd((string) $invA->due_amount, '0.00', 2));

        // Tuition-First split on first partial payment: fine_amount = 0.00
        $pay1 = FeePayment::where('fee_invoice_id', $invA->id)->firstOrFail();
        $this->assertEquals('0.00', $pay1->fineAmountDecimal());

        // StudentCustomFee still strictly untouched (800.00)
        $customFee->refresh();
        $this->assertEquals('800.00', bcadd((string) $customFee->amount, '0.00', 2));

        // 3. Subsequent settlement of remaining 320.00 allocates exact 20.00 fine_amount and marks Paid
        $res2 = $this->postJson(route('fees.collection.bulk_students_store'), [
            'invoice_ids'    => [$invA->id],
            'paying_amounts' => [$invA->id => '320.00'],
            'payment_method' => 'Cash',
            'payment_date'   => '2026-03-15',
        ]);
        $res2->assertOk()->assertJsonPath('success', true);

        $invA->refresh();
        $this->assertSame('Paid', $invA->status);
        $this->assertEquals('820.00', bcadd((string) $invA->paid_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $invA->due_amount, '0.00', 2));

        $pay2 = FeePayment::where('fee_invoice_id', $invA->id)->orderBy('id', 'desc')->firstOrFail();
        $this->assertEquals('320.00', bcadd((string) $pay2->paid_amount, '0.00', 2));
        $this->assertEquals('20.00', $pay2->fineAmountDecimal());
    }

    public function test_unauthorized_roles_cannot_add_or_waive_late_fines(): void
    {
        $marchInv = $this->createInvoice($this->studentA, $this->monthlySetup2026, 'INV-202603-0001', '2026-03-10', 900.00);
        app(LateFineService::class)->applyManualLateFine($marchInv, 20.00, 'Admin fine', $this->adminUser->id);

        // Guest
        auth()->logout();
        $guestAdd = $this->postJson(route('fees.invoice.late_fine', $marchInv->id), [
            'amount' => 20,
            'reason' => 'Guest attempt',
        ]);
        $this->assertTrue(in_array($guestAdd->status(), [401, 302], true));

        // Student role
        $studentUser = User::create([
            'name'     => 'Student User',
            'email'    => 'student_embedded_lf@macsschool.com',
            'password' => bcrypt('secret'),
        ]);
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $studentUser->assignRole($studentRole);

        $this->actingAs($studentUser);
        $unauthAdd = $this->postJson(route('fees.invoice.late_fine', $marchInv->id), [
            'amount' => 20,
            'reason' => 'Unauthorized add',
        ]);
        $this->assertTrue(in_array($unauthAdd->status(), [403, 302], true));

        $unauthWaive = $this->postJson(route('fees.invoice.waive_fine', $marchInv->id), [
            'reason' => 'Unauthorized waive',
        ]);
        $this->assertTrue(in_array($unauthWaive->status(), [403, 302], true));

        // Editor role (also blocked by authorizeFeeAdmin)
        $editorUser = User::create([
            'name'     => 'Editor User',
            'email'    => 'editor_embedded_lf@macsschool.com',
            'password' => bcrypt('secret'),
        ]);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $editorUser->assignRole($editorRole);

        $this->actingAs($editorUser);
        $this->assertTrue(in_array(
            $this->postJson(route('fees.invoice.late_fine', $marchInv->id), [
                'amount' => 20,
                'reason' => 'Editor attempt',
            ])->status(),
            [403, 302],
            true
        ));
    }
}
