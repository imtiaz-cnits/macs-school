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
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\StudentCustomFee;
use App\Models\User;
use App\Services\ClassWiseFinancialReportService;
use App\Services\FeeReceiptService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReconcileClassOneMarch250CommandTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Branch $branch;
    private SessionYear $session2026;
    private Classes $classOne;
    private Section $sectionA;
    private FeeCategory $monthlyCat;
    private FeeSetup $oneMonthlySetup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'MACS Super Admin',
            'email'    => 'admin_recon_c1@macsschool.com',
            'password' => bcrypt('secret'),
        ]);
        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($superAdminRole);
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

        $this->classOne = Classes::create([
            'class_name' => 'One',
            'status'     => 'Active',
            'user_id'    => $this->adminUser->id,
        ]);

        $this->sectionA = Section::create([
            'section_name' => 'A',
            'class_id'     => $this->classOne->id,
            'status'       => 'Active',
            'user_id'      => $this->adminUser->id,
        ]);

        $this->monthlyCat = FeeCategory::create([
            'name'    => 'Monthly Fee',
            'status'  => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->oneMonthlySetup = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classOne->id,
            'fee_category_id' => $this->monthlyCat->id,
            'fee_month'       => 'Monthly',
            'amount'          => 700.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);
    }

    private function createClassOneStudent(string $identity, string $name, int $roll, string $status = 'Active'): Student
    {
        return Student::create([
            'student_identity'      => $identity,
            'student_name'          => $name,
            'roll_number'           => $roll,
            'dob'                   => '2019-01-01',
            'gender'                => 'Female',
            'religion'              => 'Islam',
            'father_name'           => 'Father ' . $roll,
            'father_mobile'         => '017100000' . str_pad((string) $roll, 2, '0', STR_PAD_LEFT),
            'mother_name'           => 'Mother ' . $roll,
            'mother_mobile'         => '017200000' . str_pad((string) $roll, 2, '0', STR_PAD_LEFT),
            'guardian_name'         => 'Guardian ' . $roll,
            'guardian_mobile'       => '017100000' . str_pad((string) $roll, 2, '0', STR_PAD_LEFT),
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session2026->id,
            'class_id'              => $this->classOne->id,
            'section_id'            => $this->sectionA->id,
            'status'                => $status,
            'sms_status'            => 'Active',
            'present_village'       => 'Jalalpur',
            'present_post_office'   => 'Jalalpur',
            'present_district'      => 'Pabna',
            'present_division'      => 'Rajshahi',
            'permanent_village'     => 'Jalalpur',
            'permanent_post_office' => 'Jalalpur',
            'permanent_district'    => 'Pabna',
            'permanent_division'    => 'Rajshahi',
            'user_id'               => $this->adminUser->id,
        ]);
    }

    /**
     * @return array{invoice: FeeInvoice, payment: FeePayment}
     */
    private function createPaidMonthlyInvoice(
        Student $student,
        string $invoiceNo,
        string $dueDate,
        float $amount,
        float $discount,
        string $receiptNo,
        string $paymentDate = '2026-09-21',
        string $paymentMethod = 'Cash'
    ): array {
        $net = round($amount - $discount, 2);

        $invoice = FeeInvoice::create([
            'invoice_no'      => $invoiceNo,
            'student_id'      => $student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'fee_setup_id'    => $this->oneMonthlySetup->id,
            'amount'          => $amount,
            'discount'        => $discount,
            'net_amount'      => $net,
            'fine_amount'     => 0.00,
            'paid_amount'     => $net,
            'due_amount'      => 0.00,
            'status'          => 'Paid',
            'due_date'        => $dueDate,
            'user_id'         => $this->adminUser->id,
        ]);

        $payment = FeePayment::create([
            'receipt_no'     => $receiptNo,
            'fee_invoice_id' => $invoice->id,
            'student_id'     => $student->id,
            'branch_id'      => $this->branch->id,
            'paid_amount'    => $net,
            'fine_amount'    => 0.00,
            'payment_date'   => $paymentDate,
            'payment_method' => $paymentMethod,
            'collected_by'   => $this->adminUser->id,
        ]);

        return ['invoice' => $invoice, 'payment' => $payment];
    }

    /**
     * 1. Dry-Run Default: Detects eligible whitelisted invoices (250 -> 700) with ZERO DB writes.
     */
    public function test_dry_run_default_detects_eligible_invoices_with_zero_database_writes(): void
    {
        $stu3 = $this->createClassOneStudent('25010N00094', 'KM Suwaibah Bint Faria', 3);
        $stu4 = $this->createClassOneStudent('24020P00095', 'Mst. Samiha Siddika', 4);

        $pair3 = $this->createPaidMonthlyInvoice($stu3, 'INV-202603-0003', '2026-03-10', 250.00, 0.00, 'REC-20260921-1030-33');
        $pair4 = $this->createPaidMonthlyInvoice($stu4, 'INV-202603-0004', '2026-03-10', 250.00, 0.00, 'REC-20260921-1267-34');

        $exitCode = Artisan::call('fees:reconcile-class-one-march-250');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('DRY-RUN MODE ACTIVE', $output);
        $this->assertStringContainsString('INV-202603-0003', $output);
        $this->assertStringContainsString('INV-202603-0004', $output);
        $this->assertStringContainsString('+৳900.00', $output);
        $this->assertStringContainsString('DRY-RUN MODE: 0 rows modified.', $output);

        foreach ([$pair3, $pair4] as $pair) {
            $inv = $pair['invoice']->fresh();
            $pay = $pair['payment']->fresh();
            $this->assertEquals('250.00', bcadd((string) $inv->net_amount, '0.00', 2));
            $this->assertEquals('250.00', bcadd((string) $inv->paid_amount, '0.00', 2));
            $this->assertSame('Active', $pay->status);
        }

        $this->assertSame(2, FeePayment::count());
        $this->assertSame(0, FeeAdjustmentLog::count());
        $this->assertSame(0, SmsLog::count());
    }

    /**
     * 2. --apply Full Atomic Flow, Receipt Rendering & Zero SMS Notifications.
     */
    public function test_apply_executes_atomic_reversal_adjustment_recollection_and_zero_sms(): void
    {
        Log::spy();

        $stu3 = $this->createClassOneStudent('25010N00094', 'KM Suwaibah Bint Faria', 3);
        $pair3 = $this->createPaidMonthlyInvoice($stu3, 'INV-202603-0003', '2026-03-10', 250.00, 0.00, 'REC-20260921-1030-33', '2026-09-21', 'Cash');

        $exitCode = Artisan::call('fees:reconcile-class-one-march-250', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('APPLIED SUCCESSFULLY: Reconciled 1 Class One March 2026 invoice(s)', $output);
        $this->assertStringContainsString('+৳450.00', $output);

        // Old ৳250 payment must be immutably Reversed
        $oldPayment = $pair3['payment']->fresh();
        $this->assertSame('Reversed', $oldPayment->status);
        $this->assertEquals('250.00', bcadd((string) $oldPayment->paid_amount, '0.00', 2));
        $this->assertEquals('250.00', bcadd((string) $oldPayment->reversed_amount, '0.00', 2));
        $this->assertSame($this->adminUser->id, (int) $oldPayment->reversed_by);
        $this->assertNotNull($oldPayment->reversed_at);
        $this->assertNotEmpty($oldPayment->reversal_reference);
        $this->assertStringContainsString('Authority-approved reconciliation', (string) $oldPayment->reversal_reason);

        // Invoice must be ৳700 Paid with ৳0 due
        $invoice = $pair3['invoice']->fresh();
        $this->assertEquals('700.00', bcadd((string) $invoice->amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $invoice->discount, '0.00', 2));
        $this->assertEquals('700.00', bcadd((string) $invoice->net_amount, '0.00', 2));
        $this->assertEquals('0.00', $invoice->fineAmountDecimal());
        $this->assertEquals('700.00', bcadd((string) $invoice->paid_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $invoice->due_amount, '0.00', 2));
        $this->assertSame('Paid', $invoice->status);

        // New Active ৳700 payment preserves original payment_date ('2026-09-21'), method, branch, collector
        $newPayment = FeePayment::where('fee_invoice_id', $invoice->id)
            ->where('status', 'Active')
            ->first();
        $this->assertNotNull($newPayment);
        $this->assertSame("REC-20260921-RECON-{$invoice->id}", $newPayment->receipt_no);
        $this->assertEquals('700.00', bcadd((string) $newPayment->paid_amount, '0.00', 2));
        $this->assertEquals('0.00', $newPayment->fineAmountDecimal());
        $this->assertSame('2026-09-21', (string) $newPayment->payment_date);
        $this->assertSame('Cash', $newPayment->payment_method);
        $this->assertSame($this->branch->id, (int) $newPayment->branch_id);
        $this->assertSame($this->adminUser->id, (int) $newPayment->collected_by);
        $this->assertStringContainsString('REC-20260921-1030-33', (string) $newPayment->note);

        // Audit logs: 1 reversal log + 1 increase (+450) adjustment log
        $logs = FeeAdjustmentLog::where('fee_invoice_id', $invoice->id)->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $increaseLog = $logs->firstWhere('mode', 'increase');
        $this->assertNotNull($increaseLog);
        $this->assertEquals('250.00', bcadd((string) $increaseLog->previous_net_amount, '0.00', 2));
        $this->assertEquals('700.00', bcadd((string) $increaseLog->new_net_amount, '0.00', 2));
        $this->assertEquals('450.00', bcadd((string) $increaseLog->adjustment_amount, '0.00', 2));
        $this->assertSame($this->adminUser->id, (int) $increaseLog->user_id);

        // FeeReceiptService resolves the reconciled receipt cleanly on both Single POS & Master POS routes
        $receiptService = app(FeeReceiptService::class);
        $fetched = $receiptService->fetchPaymentsForMasterReceipt($newPayment->receipt_no);
        $this->assertCount(1, $fetched);
        $this->assertSame($newPayment->id, $fetched->first()->id);

        $singleRes = $this->get(route('fees.invoice.pos_print', $invoice->id));
        $singleRes->assertOk();
        $singleRes->assertSee('700.00');

        $masterRes = $this->get(route('fees.receipt.pos_print', $newPayment->receipt_no));
        $masterRes->assertOk();
        $masterRes->assertSee('700.00');

        // ZERO SMS notifications queued/sent
        $this->assertSame(0, SmsLog::count());

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $msg, array $ctx): bool => $msg === 'fees:reconcile-class-one-march-250 applied'
                && $ctx['reconciled_count'] === 1
                && $ctx['net_delta'] === '450.00');
    }

    /**
     * 3. Strict Idempotency: Second --apply finds 0 eligible invoices and performs 0 DB writes.
     */
    public function test_apply_is_strictly_idempotent(): void
    {
        $stu3 = $this->createClassOneStudent('25010N00094', 'KM Suwaibah Bint Faria', 3);
        $this->createPaidMonthlyInvoice($stu3, 'INV-202603-0003', '2026-03-10', 250.00, 0.00, 'REC-20260921-1030-33');

        $this->artisan('fees:reconcile-class-one-march-250', ['--apply' => true])->assertExitCode(0);

        $payCountAfterFirst = FeePayment::count();
        $logCountAfterFirst = FeeAdjustmentLog::count();

        $exitCode2 = Artisan::call('fees:reconcile-class-one-march-250', ['--apply' => true]);
        $output2 = Artisan::output();

        $this->assertSame(0, $exitCode2);
        $this->assertStringContainsString('ALREADY_RECONCILED', $output2);
        $this->assertStringContainsString('No eligible ৳250.00 invoices require reconciliation (0 rows modified).', $output2);
        $this->assertSame($payCountAfterFirst, FeePayment::count());
        $this->assertSame($logCountAfterFirst, FeeAdjustmentLog::count());
    }

    /**
     * 4. Strict Exclusions: Roll 1 (INV-202603-0001), Roll 10 (custom ৳250), and non-whitelisted rows stay untouched.
     */
    public function test_strict_exclusions_never_touch_roll_1_roll_10_or_non_whitelisted_invoices(): void
    {
        // Roll 1: Moriom Akhter Sinha (INV-202603-0001, Issue #2 — NOT whitelisted)
        $roll1 = $this->createClassOneStudent('23120P00092', 'Moriom Akhter Sinha', 1);
        StudentCustomFee::create(['student_id' => $roll1->id, 'fee_category_id' => $this->monthlyCat->id, 'amount' => 200.00]);
        $invRoll1 = $this->createPaidMonthlyInvoice($roll1, 'INV-202603-0001', '2026-03-10', 250.00, 0.00, 'REC-20261118-3786-31', '2026-11-18');

        // Roll 10: Md. Abu Talha Ayan (legitimate ৳250 custom fee — NOT whitelisted)
        $roll10 = $this->createClassOneStudent('25010P00101', 'Md. Abu Talha Ayan', 10);
        StudentCustomFee::create(['student_id' => $roll10->id, 'fee_category_id' => $this->monthlyCat->id, 'amount' => 250.00]);
        $invRoll10 = $this->createPaidMonthlyInvoice($roll10, 'INV-202603-0010', '2026-03-10', 700.00, 450.00, 'REC-20260922-6156-40', '2026-09-22');

        // Whitelisted eligible Roll 3
        $roll3 = $this->createClassOneStudent('25010N00094', 'KM Suwaibah Bint Faria', 3);
        $invRoll3 = $this->createPaidMonthlyInvoice($roll3, 'INV-202603-0003', '2026-03-10', 250.00, 0.00, 'REC-20260921-1030-33');

        $this->artisan('fees:reconcile-class-one-march-250', ['--apply' => true])->assertExitCode(0);

        // Roll 3 reconciled to 700
        $this->assertEquals('700.00', bcadd((string) $invRoll3['invoice']->fresh()->net_amount, '0.00', 2));
        $this->assertSame('Reversed', $invRoll3['payment']->fresh()->status);

        // Roll 1 untouched (250 Paid, Active payment)
        $this->assertEquals('250.00', bcadd((string) $invRoll1['invoice']->fresh()->net_amount, '0.00', 2));
        $this->assertSame('Active', $invRoll1['payment']->fresh()->status);

        // Roll 10 untouched (250 Paid, Active payment)
        $this->assertEquals('250.00', bcadd((string) $invRoll10['invoice']->fresh()->net_amount, '0.00', 2));
        $this->assertSame('Active', $invRoll10['payment']->fresh()->status);
    }

    /**
     * 5. Financial Report Parity: ClassWiseFinancialReportService, FeeReportController, and FinancialReportController.
     */
    public function test_financial_reports_reconcile_exact_plus_450_per_student_with_zero_delta(): void
    {
        $stu3 = $this->createClassOneStudent('25010N00094', 'KM Suwaibah Bint Faria', 3);
        $stu4 = $this->createClassOneStudent('24020P00095', 'Mst. Samiha Siddika', 4);

        // March (250 buggy) + February (700 clean) for both students
        $this->createPaidMonthlyInvoice($stu3, 'INV-202602-0003', '2026-02-10', 700.00, 0.00, 'REC-20260921-1030-100', '2026-09-21');
        $this->createPaidMonthlyInvoice($stu3, 'INV-202603-0003', '2026-03-10', 250.00, 0.00, 'REC-20260921-1030-33', '2026-09-21');
        $this->createPaidMonthlyInvoice($stu4, 'INV-202602-0004', '2026-02-10', 700.00, 0.00, 'REC-20260921-1267-101', '2026-09-21');
        $this->createPaidMonthlyInvoice($stu4, 'INV-202603-0004', '2026-03-10', 250.00, 0.00, 'REC-20260921-1267-34', '2026-09-21');

        $this->artisan('fees:reconcile-class-one-march-250', ['--apply' => true])->assertExitCode(0);

        $reportService = app(ClassWiseFinancialReportService::class);

        // A. March 2026 Monthly Report: 2 x 700 = 1,400.00 pure Monthly Fee, 0 Other, 0 Outstanding
        $marReport = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'March',
            'year'            => 2026,
        ]);
        $this->assertEquals('1400.00', $marReport['grand_total']['due']['monthly_fee']);
        $this->assertEquals('0.00', $marReport['grand_total']['due']['other_fees']);
        $this->assertEquals('1400.00', $marReport['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('0.00', $marReport['grand_total']['outstanding']['total']);

        // B. Full Session 2026 === Feb (1,400) + Mar (1,400) = 2,800.00 (0.00 delta)
        $febReport = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'February',
            'year'            => 2026,
        ]);
        $fullReport = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'all',
        ]);
        $this->assertEquals(
            bcadd((string) $febReport['grand_total']['due']['total'], (string) $marReport['grand_total']['due']['total'], 2),
            $fullReport['grand_total']['due']['total']
        );
        $this->assertEquals('2800.00', $fullReport['grand_total']['collected']['total']);
        $this->assertEquals('0.00', $fullReport['grand_total']['outstanding']['total']);

        // C. Custom Date Range on original payment_date (2026-09-21): collects full 2,800.00 (excludes Reversed 500.00!)
        $customSep21 = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-09-21',
            'end_date'        => '2026-09-21',
        ]);
        $this->assertEquals('2800.00', $customSep21['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('2800.00', $customSep21['grand_total']['collected']['total']);

        // D. Today's date (2026-11-27) shows 0.00 collection (no false inflation on today's cashbook!)
        $customToday = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-11-27',
            'end_date'        => '2026-11-27',
        ]);
        $this->assertEquals('0.00', $customToday['grand_total']['collected']['total']);

        // E. FeeReportController (index & summary) & FinancialReportController reflect exact 2,800.00 Active collections
        $feeRepRes = $this->get(route('fees.reports.index', [
            'start_date' => '2026-09-21',
            'end_date'   => '2026-09-21',
        ]));
        $feeRepRes->assertOk();
        $this->assertEquals(2800.00, (float) $feeRepRes->viewData('totalCollected'));

        $feeSumRes = $this->get(route('fees.reports.summary', [
            'session_year_id' => $this->session2026->id,
        ]));
        $feeSumRes->assertOk();
        $categorySummary = $feeSumRes->viewData('categorySummary');
        $this->assertEquals('2800.00', $categorySummary->get('Monthly Fee')->total_net);
        $this->assertEquals('2800.00', $categorySummary->get('Monthly Fee')->total_paid);
        $this->assertEquals('0.00', $categorySummary->get('Monthly Fee')->total_due);

        $finRepRes = $this->get(url('/accounts/reports?start_date=2026-09-21&end_date=2026-09-21'));
        $finRepRes->assertOk();
        $this->assertEquals(2800.00, (float) $finRepRes->viewData('totalIncome'));

        // And FinancialReportController on today's date (2026-11-27) reflects 0.00 income
        $finTodayRes = $this->get(url('/accounts/reports?start_date=2026-11-27&end_date=2026-11-27'));
        $finTodayRes->assertOk();
        $this->assertEquals(0.00, (float) $finTodayRes->viewData('totalIncome'));
    }
}
