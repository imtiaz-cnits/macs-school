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

class ReconcileClassOneRoll1March200CommandTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Branch $branch;
    private SessionYear $session2026;
    private Classes $classOne;
    private Section $sectionA;
    private FeeCategory $monthlyCat;
    private FeeCategory $termExamCat;
    private FeeSetup $monthlySetup;
    private FeeSetup $termExamSetup;
    private Student $roll1Student;
    private StudentCustomFee $roll1CustomFee;
    private FeeInvoice $marchInvoice;
    private FeePayment $marchPayment;
    private FeeInvoice $novMonthlyInvoice;
    private FeePayment $novMonthlyPayment;
    private FeeInvoice $novExamInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'MACS Super Admin',
            'email'    => 'admin_recon_r1_mar@macsschool.com',
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

        $this->termExamCat = FeeCategory::create([
            'name'    => 'Term Exam Fee',
            'status'  => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->monthlySetup = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classOne->id,
            'fee_category_id' => $this->monthlyCat->id,
            'fee_month'       => 'Monthly',
            'amount'          => 700.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->termExamSetup = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classOne->id,
            'fee_category_id' => $this->termExamCat->id,
            'fee_month'       => 'November',
            'amount'          => 290.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->roll1Student = Student::create([
            'student_identity'      => '23120P00092',
            'student_name'          => 'Moriom Akhter Sinha',
            'roll_number'           => 1,
            'dob'                   => '2019-01-01',
            'gender'                => 'Female',
            'religion'              => 'Islam',
            'father_name'           => 'Father 1',
            'father_mobile'         => '01710000001',
            'mother_name'           => 'Mother 1',
            'mother_mobile'         => '01720000001',
            'guardian_name'         => 'Guardian 1',
            'guardian_mobile'       => '01710000001',
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session2026->id,
            'class_id'              => $this->classOne->id,
            'section_id'            => $this->sectionA->id,
            'status'                => 'Active',
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

        $this->roll1CustomFee = StudentCustomFee::create([
            'student_id'      => $this->roll1Student->id,
            'fee_category_id' => $this->monthlyCat->id,
            'amount'          => 200.00,
        ]);

        // March Monthly Fee erroneously billed & paid at 250.00 under master receipt REC-20261118-3786
        $this->marchInvoice = FeeInvoice::create([
            'invoice_no'      => 'INV-202603-0001',
            'student_id'      => $this->roll1Student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'fee_setup_id'    => $this->monthlySetup->id,
            'amount'          => 250.00,
            'discount'        => 0.00,
            'net_amount'      => 250.00,
            'fine_amount'     => 0.00,
            'paid_amount'     => 250.00,
            'due_amount'      => 0.00,
            'status'          => 'Paid',
            'due_date'        => '2026-03-10',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->marchPayment = FeePayment::create([
            'receipt_no'     => 'REC-20261118-3786-31',
            'fee_invoice_id' => $this->marchInvoice->id,
            'student_id'     => $this->roll1Student->id,
            'branch_id'      => $this->branch->id,
            'paid_amount'    => 250.00,
            'fine_amount'    => 0.00,
            'payment_date'   => '2026-11-18',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);

        // Sibling November Monthly Fee paid on the SAME master receipt prefix REC-20261118-3786
        $this->novMonthlyInvoice = FeeInvoice::create([
            'invoice_no'      => 'INV-202611-0001',
            'student_id'      => $this->roll1Student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'fee_setup_id'    => $this->monthlySetup->id,
            'amount'          => 700.00,
            'discount'        => 500.00,
            'net_amount'      => 200.00,
            'fine_amount'     => 0.00,
            'paid_amount'     => 200.00,
            'due_amount'      => 0.00,
            'status'          => 'Paid',
            'due_date'        => '2026-11-10',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->novMonthlyPayment = FeePayment::create([
            'receipt_no'     => 'REC-20261118-3786-12542',
            'fee_invoice_id' => $this->novMonthlyInvoice->id,
            'student_id'     => $this->roll1Student->id,
            'branch_id'      => $this->branch->id,
            'paid_amount'    => 200.00,
            'fine_amount'    => 0.00,
            'payment_date'   => '2026-11-18',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);

        // November Term Exam Fee (290.00 Unpaid — MUST STAY 100% UNTOUCHED!)
        $this->novExamInvoice = FeeInvoice::create([
            'invoice_no'      => 'INV-202611-0616',
            'student_id'      => $this->roll1Student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'fee_setup_id'    => $this->termExamSetup->id,
            'amount'          => 290.00,
            'discount'        => 0.00,
            'net_amount'      => 290.00,
            'fine_amount'     => 0.00,
            'paid_amount'     => 0.00,
            'due_amount'      => 290.00,
            'status'          => 'Unpaid',
            'due_date'        => '2026-11-10',
            'user_id'         => $this->adminUser->id,
        ]);
    }

    /**
     * 1. Dry-Run Default: Detects eligible INV-202603-0001 (250 -> 200) with ZERO DB writes.
     */
    public function test_dry_run_default_detects_eligible_invoice_with_zero_database_writes(): void
    {
        $exitCode = Artisan::call('fees:reconcile-class-one-roll1-march-200');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('DRY-RUN MODE ACTIVE', $output);
        $this->assertStringContainsString('INV-202603-0001', $output);
        $this->assertStringContainsString('ELIGIBLE', $output);
        $this->assertStringContainsString('250.00 -> 700.00 - 500.00 = 200.00', $output);
        $this->assertStringContainsString('DRY-RUN MODE: 0 rows modified.', $output);

        $this->assertEquals('250.00', bcadd((string) $this->marchInvoice->fresh()->net_amount, '0.00', 2));
        $this->assertSame('Active', $this->marchPayment->fresh()->status);
        $this->assertSame(2, FeePayment::count());
        $this->assertSame(0, FeeAdjustmentLog::count());
        $this->assertSame(0, SmsLog::count());
    }

    /**
     * 2. --apply Full Atomic Flow, Master-Receipt Isolation, POS Rendering & Zero SMS.
     */
    public function test_apply_reconciles_march_isolates_master_receipt_sibling_and_keeps_nov_exam_untouched(): void
    {
        Log::spy();

        $novMonthlyBefore = $this->novMonthlyInvoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']);
        $novPayBefore = $this->novMonthlyPayment->fresh()->only(['receipt_no', 'paid_amount', 'payment_date', 'status']);
        $novExamBefore = $this->novExamInvoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']);

        $exitCode = Artisan::call('fees:reconcile-class-one-roll1-march-200', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('APPLIED SUCCESSFULLY: Reconciled INV-202603-0001', $output);

        // Old 250 March payment immutably Reversed
        $oldMarchPay = $this->marchPayment->fresh();
        $this->assertSame('Reversed', $oldMarchPay->status);
        $this->assertEquals('250.00', bcadd((string) $oldMarchPay->reversed_amount, '0.00', 2));
        $this->assertSame($this->adminUser->id, (int) $oldMarchPay->reversed_by);
        $this->assertNotEmpty($oldMarchPay->reversal_reference);

        // March invoice normalized cleanly to 700.00 - 500.00 = 200.00 Paid
        $freshMarch = $this->marchInvoice->fresh();
        $this->assertEquals('700.00', bcadd((string) $freshMarch->amount, '0.00', 2));
        $this->assertEquals('500.00', bcadd((string) $freshMarch->discount, '0.00', 2));
        $this->assertEquals('200.00', bcadd((string) $freshMarch->net_amount, '0.00', 2));
        $this->assertEquals('200.00', bcadd((string) $freshMarch->paid_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $freshMarch->due_amount, '0.00', 2));
        $this->assertSame('Paid', $freshMarch->status);

        // New Active 200.00 March payment preserves original payment_date ('2026-11-18')
        $newMarchPay = FeePayment::where('fee_invoice_id', $freshMarch->id)
            ->where('status', 'Active')
            ->firstOrFail();
        $this->assertSame("REC-20261118-RECON-{$freshMarch->id}", $newMarchPay->receipt_no);
        $this->assertEquals('200.00', bcadd((string) $newMarchPay->paid_amount, '0.00', 2));
        $this->assertSame('2026-11-18', (string) $newMarchPay->payment_date);
        $this->assertSame('Cash', $newMarchPay->payment_method);
        $this->assertStringContainsString('REC-20261118-3786-31', (string) $newMarchPay->note);

        // Audit logs: 1 reversal log + 1 decrease (-50) normalization log
        $logs = FeeAdjustmentLog::where('fee_invoice_id', $freshMarch->id)->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $decLog = $logs->firstWhere('mode', 'decrease');
        $this->assertNotNull($decLog);
        $this->assertEquals('50.00', bcadd((string) $decLog->adjustment_amount, '0.00', 2));
        $this->assertEquals('700.00', bcadd((string) $decLog->new_amount, '0.00', 2));
        $this->assertEquals('500.00', bcadd((string) $decLog->new_discount, '0.00', 2));
        $this->assertEquals('200.00', bcadd((string) $decLog->new_net_amount, '0.00', 2));
        $this->assertStringContainsString('RECON-C1-R1-202603', (string) $decLog->reason);

        // Sibling November Monthly payment & invoice AND November Term Exam Fee 100% UNTOUCHED
        $this->assertSame($novMonthlyBefore, $this->novMonthlyInvoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']));
        $this->assertSame($novPayBefore, $this->novMonthlyPayment->fresh()->only(['receipt_no', 'paid_amount', 'payment_date', 'status']));
        $this->assertSame($novExamBefore, $this->novExamInvoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']));
        $this->assertEquals('200.00', bcadd((string) $this->roll1CustomFee->fresh()->amount, '0.00', 2));

        // Master receipt REC-20261118-3786 shows partial reversal cleanly (active total = 200.00 Nov, reversed total = 250.00 Mar)
        $receiptService = app(FeeReceiptService::class);
        $masterPayments = $receiptService->fetchPaymentsForMasterReceipt('REC-20261118-3786');
        $this->assertCount(2, $masterPayments);
        $masterData = $receiptService->buildMasterReceiptData($masterPayments, 'REC-20261118-3786');
        $this->assertTrue($masterData['has_partial_reversals']);
        $this->assertFalse($masterData['is_fully_reversed']);
        $this->assertSame('200.00', $masterData['active_grand_total']);
        $this->assertSame('250.00', $masterData['reversed_grand_total']);

        // Both POS print routes render cleanly with HTTP 200
        $this->get(route('fees.receipt.pos_print', 'REC-20261118-3786'))->assertOk()->assertSee('200.00');
        $this->get(route('fees.receipt.pos_print', $newMarchPay->receipt_no))->assertOk()->assertSee('200.00');
        $this->get(route('fees.invoice.pos_print', $freshMarch->id))->assertOk()->assertSee('200.00');

        // ZERO SMS sent
        $this->assertSame(0, SmsLog::count());

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $msg, array $ctx): bool => $msg === 'fees:reconcile-class-one-roll1-march-200 applied'
                && $ctx['invoice_no'] === 'INV-202603-0001'
                && $ctx['net_delta'] === '-50.00');
    }

    /**
     * 3. Strict Idempotency: Second --apply finds 0 eligible rows and performs 0 DB writes.
     */
    public function test_apply_is_strictly_idempotent(): void
    {
        $this->artisan('fees:reconcile-class-one-roll1-march-200', ['--apply' => true])->assertExitCode(0);

        $payCount = FeePayment::count();
        $logCount = FeeAdjustmentLog::count();

        $code2 = Artisan::call('fees:reconcile-class-one-roll1-march-200', ['--apply' => true]);
        $out2 = Artisan::output();

        $this->assertSame(0, $code2);
        $this->assertStringContainsString('ALREADY_RECONCILED', $out2);
        $this->assertSame($payCount, FeePayment::count());
        $this->assertSame($logCount, FeeAdjustmentLog::count());
    }

    /**
     * 4. Safety Guard: Aborts with exit 1 if INV-202603-0001 is unpaid/different amount or custom fee != 200.
     */
    public function test_safety_guard_blocks_unexpected_invoice_or_custom_fee_state(): void
    {
        // Case A: Student custom fee changed from 200.00 to 300.00
        $this->roll1CustomFee->update(['amount' => 300.00]);
        $codeA = Artisan::call('fees:reconcile-class-one-roll1-march-200', ['--apply' => true]);
        $this->assertSame(1, $codeA);
        $this->assertSame('Active', $this->marchPayment->fresh()->status);
        $this->assertSame(0, FeeAdjustmentLog::count());

        // Restore custom fee, break invoice state (Unpaid)
        $this->roll1CustomFee->update(['amount' => 200.00]);
        $this->marchInvoice->update(['paid_amount' => 0.00, 'due_amount' => 250.00, 'status' => 'Unpaid']);
        $codeB = Artisan::call('fees:reconcile-class-one-roll1-march-200', ['--apply' => true]);
        $this->assertSame(1, $codeB);
        $this->assertSame('Active', $this->marchPayment->fresh()->status);
        $this->assertSame(0, FeeAdjustmentLog::count());
    }

    /**
     * 5. Financial Report Parity: Exact -50.00 on March Monthly Fee & 2026-11-18, 0.00 change on Nov Exam Fee.
     */
    public function test_financial_reports_reflect_minus_50_march_and_keep_nov_exam_290_intact(): void
    {
        $reportService = app(ClassWiseFinancialReportService::class);
        $marBefore = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'March', 'year' => 2026]);
        $novBefore = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'November', 'year' => 2026]);
        $cusBefore = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'custom', 'start_date' => '2026-11-18', 'end_date' => '2026-11-18']);

        $this->assertEquals('250.00', $marBefore['grand_total']['due']['monthly_fee']);
        $this->assertEquals('250.00', $marBefore['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('290.00', $novBefore['grand_total']['due']['exam_fees']);
        $this->assertEquals('450.00', $cusBefore['grand_total']['collected']['monthly_fee']); // 250 Mar + 200 Nov

        $this->artisan('fees:reconcile-class-one-roll1-march-200', ['--apply' => true])->assertExitCode(0);

        $marAfter = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'March', 'year' => 2026]);
        $novAfter = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'November', 'year' => 2026]);
        $fullAfter = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'all']);
        $cusAfter = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'custom', 'start_date' => '2026-11-18', 'end_date' => '2026-11-18']);

        // March Monthly Fee dropped 250 -> 200 (Due & Collected)
        $this->assertEquals('200.00', $marAfter['grand_total']['due']['monthly_fee']);
        $this->assertEquals('200.00', $marAfter['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('0.00', $marAfter['grand_total']['outstanding']['total']);

        // November Report completely unchanged (Monthly 200 Paid + Exam 290 Unpaid)
        $this->assertSame($novBefore['grand_total'], $novAfter['grand_total']);

        // Custom Range 2026-11-18 dropped 450 -> 400 (200 Mar + 200 Nov)
        $this->assertEquals('400.00', $cusAfter['grand_total']['collected']['monthly_fee']);
        $this->assertEquals('400.00', $cusAfter['grand_total']['collected']['total']);

        // Full Session === Mar (200) + Nov (490) = 690 Due / 400 Collected / 290 Outstanding (0.00 delta)
        $this->assertEquals('690.00', $fullAfter['grand_total']['due']['total']);
        $this->assertEquals('400.00', $fullAfter['grand_total']['collected']['total']);
        $this->assertEquals('290.00', $fullAfter['grand_total']['outstanding']['total']);
        $this->assertEquals(
            bcadd((string) $marAfter['grand_total']['due']['total'], (string) $novAfter['grand_total']['due']['total'], 2),
            $fullAfter['grand_total']['due']['total']
        );
    }
}
