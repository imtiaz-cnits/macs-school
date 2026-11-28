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

class ReconcileClassFiveRoll3JanMay600CommandTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Branch $branch;
    private SessionYear $session2026;
    private Classes $classFive;
    private Section $sectionA;
    private FeeCategory $monthlyCat;
    private FeeCategory $termExamCat;
    private FeeSetup $monthlySetup;
    private FeeSetup $termExamSetup;
    private Student $roll3Student;
    private StudentCustomFee $roll3CustomFee;
    private Student $roll6Student;

    /** @var array<int, FeeInvoice> */
    private array $janMayInvoices = [];

    /** @var array<int, FeePayment> */
    private array $janMayPayments = [];

    /** @var array<int, FeeInvoice> */
    private array $junDecInvoices = [];

    private FeeInvoice $siblingExamInvoice;
    private FeePayment $siblingExamPayment;
    private FeeInvoice $roll6Invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'MACS Super Admin',
            'email'    => 'admin_recon_c5_r3@macsschool.com',
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

        $this->classFive = Classes::create([
            'class_name' => 'Five',
            'status'     => 'Active',
            'user_id'    => $this->adminUser->id,
        ]);

        $this->sectionA = Section::create([
            'section_name' => 'Section A',
            'class_id'     => $this->classFive->id,
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
            'class_id'        => $this->classFive->id,
            'fee_category_id' => $this->monthlyCat->id,
            'fee_month'       => 'Monthly',
            'amount'          => 800.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->termExamSetup = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classFive->id,
            'fee_category_id' => $this->termExamCat->id,
            'fee_month'       => 'November',
            'amount'          => 330.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->roll3Student = Student::create([
            'student_identity'      => '23010200282',
            'student_name'          => 'Nurjahan Tihan',
            'roll_number'           => 3,
            'dob'                   => '2015-01-01',
            'gender'                => 'Female',
            'religion'              => 'Islam',
            'father_name'           => 'Father 3',
            'father_mobile'         => '01710000003',
            'mother_name'           => 'Mother 3',
            'mother_mobile'         => '01720000003',
            'guardian_name'         => 'Guardian 3',
            'guardian_mobile'       => '01710000003',
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session2026->id,
            'class_id'              => $this->classFive->id,
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

        $this->roll3CustomFee = StudentCustomFee::create([
            'student_id'      => $this->roll3Student->id,
            'fee_category_id' => $this->monthlyCat->id,
            'amount'          => 600.00,
        ]);

        // 5 target Jan-May 2026 Monthly Fee invoices erroneously billed & collected at 800.00 on 2026-11-17
        $janMaySpecs = [
            ['no' => 'INV-202601-0181', 'month' => 'January',  'due' => '2026-01-10'],
            ['no' => 'INV-202602-0282', 'month' => 'February', 'due' => '2026-02-10'],
            ['no' => 'INV-202603-0327', 'month' => 'March',    'due' => '2026-03-10'],
            ['no' => 'INV-202604-0282', 'month' => 'April',    'due' => '2026-04-10'],
            ['no' => 'INV-202605-0282', 'month' => 'May',      'due' => '2026-05-10'],
        ];

        foreach ($janMaySpecs as $spec) {
            $inv = FeeInvoice::create([
                'invoice_no'      => $spec['no'],
                'student_id'      => $this->roll3Student->id,
                'branch_id'       => $this->branch->id,
                'session_year_id' => $this->session2026->id,
                'fee_setup_id'    => $this->monthlySetup->id,
                'amount'          => 800.00,
                'discount'        => 0.00,
                'net_amount'      => 800.00,
                'fine_amount'     => 0.00,
                'paid_amount'     => 800.00,
                'due_amount'      => 0.00,
                'status'          => 'Paid',
                'due_date'        => $spec['due'],
                'user_id'         => $this->adminUser->id,
            ]);

            $pay = FeePayment::create([
                'receipt_no'     => "REC-20261117-4260-{$inv->id}",
                'fee_invoice_id' => $inv->id,
                'student_id'     => $this->roll3Student->id,
                'branch_id'      => $this->branch->id,
                'paid_amount'    => 800.00,
                'fine_amount'    => 0.00,
                'payment_date'   => '2026-11-17',
                'payment_method' => 'Cash',
                'collected_by'   => $this->adminUser->id,
            ]);

            $this->janMayInvoices[] = $inv;
            $this->janMayPayments[] = $pay;
        }

        // Jun-Dec 2026 Monthly Fee invoices already normalized to 800 - 200 = 600 Paid (MUST STAY UNTOUCHED)
        $junDecSpecs = [
            ['no' => 'INV-202606-0282', 'due' => '2026-06-10', 'pdate' => '2026-09-27'],
            ['no' => 'INV-202607-0282', 'due' => '2026-07-10', 'pdate' => '2026-09-27'],
            ['no' => 'INV-202608-0282', 'due' => '2026-08-10', 'pdate' => '2026-09-27'],
            ['no' => 'INV-202609-0282', 'due' => '2026-09-10', 'pdate' => '2026-09-27'],
            ['no' => 'INV-202610-0284', 'due' => '2026-10-10', 'pdate' => '2026-10-29'],
            ['no' => 'INV-202611-0274', 'due' => '2026-11-10', 'pdate' => '2026-11-18'],
            ['no' => 'INV-202612-0274', 'due' => '2026-12-10', 'pdate' => '2026-11-21'],
        ];

        foreach ($junDecSpecs as $idx => $spec) {
            $inv = FeeInvoice::create([
                'invoice_no'      => $spec['no'],
                'student_id'      => $this->roll3Student->id,
                'branch_id'       => $this->branch->id,
                'session_year_id' => $this->session2026->id,
                'fee_setup_id'    => $this->monthlySetup->id,
                'amount'          => 800.00,
                'discount'        => 200.00,
                'net_amount'      => 600.00,
                'fine_amount'     => 0.00,
                'paid_amount'     => 600.00,
                'due_amount'      => 0.00,
                'status'          => 'Paid',
                'due_date'        => $spec['due'],
                'user_id'         => $this->adminUser->id,
            ]);

            FeePayment::create([
                'receipt_no'     => "REC-20260927-667{$idx}-{$inv->id}",
                'fee_invoice_id' => $inv->id,
                'student_id'     => $this->roll3Student->id,
                'branch_id'      => $this->branch->id,
                'paid_amount'    => 600.00,
                'fine_amount'    => 0.00,
                'payment_date'   => $spec['pdate'],
                'payment_method' => 'Cash',
                'collected_by'   => $this->adminUser->id,
            ]);

            $this->junDecInvoices[] = $inv;
        }

        // Simulated sibling Exam Fee payment sharing the SAME master receipt prefix REC-20261117-4260 (MUST STAY ACTIVE & UNTOUCHED)
        $this->siblingExamInvoice = FeeInvoice::create([
            'invoice_no'      => 'INV-202611-0804',
            'student_id'      => $this->roll3Student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'fee_setup_id'    => $this->termExamSetup->id,
            'amount'          => 330.00,
            'discount'        => 0.00,
            'net_amount'      => 330.00,
            'fine_amount'     => 0.00,
            'paid_amount'     => 330.00,
            'due_amount'      => 0.00,
            'status'          => 'Paid',
            'due_date'        => '2026-11-10',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->siblingExamPayment = FeePayment::create([
            'receipt_no'     => "REC-20261117-4260-{$this->siblingExamInvoice->id}",
            'fee_invoice_id' => $this->siblingExamInvoice->id,
            'student_id'     => $this->roll3Student->id,
            'branch_id'      => $this->branch->id,
            'paid_amount'    => 330.00,
            'fine_amount'    => 0.00,
            'payment_date'   => '2026-11-17',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);

        // Protected student: Class Five Roll 6 (Issue #3 ON HOLD — MUST NEVER BE TOUCHED)
        $this->roll6Student = Student::create([
            'student_identity'      => '25010400285',
            'student_name'          => 'Md. Junayed Hossain',
            'roll_number'           => 6,
            'dob'                   => '2015-02-01',
            'gender'                => 'Male',
            'religion'              => 'Islam',
            'father_name'           => 'Father 6',
            'father_mobile'         => '01710000006',
            'mother_name'           => 'Mother 6',
            'mother_mobile'         => '01720000006',
            'guardian_name'         => 'Guardian 6',
            'guardian_mobile'       => '01710000006',
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session2026->id,
            'class_id'              => $this->classFive->id,
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

        $this->roll6Invoice = FeeInvoice::create([
            'invoice_no'      => 'INV-202601-0184',
            'student_id'      => $this->roll6Student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'fee_setup_id'    => $this->monthlySetup->id,
            'amount'          => 800.00,
            'discount'        => 0.00,
            'net_amount'      => 800.00,
            'fine_amount'     => 0.00,
            'paid_amount'     => 800.00,
            'due_amount'      => 0.00,
            'status'          => 'Paid',
            'due_date'        => '2026-01-10',
            'user_id'         => $this->adminUser->id,
        ]);

        FeePayment::create([
            'receipt_no'     => "REC-20261117-5555-{$this->roll6Invoice->id}",
            'fee_invoice_id' => $this->roll6Invoice->id,
            'student_id'     => $this->roll6Student->id,
            'branch_id'      => $this->branch->id,
            'paid_amount'    => 800.00,
            'fine_amount'    => 0.00,
            'payment_date'   => '2026-11-17',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);
    }

    /**
     * Test 1 (Dry-Run Default): Detects all 5 eligible Jan-May invoices (4,000 -> 3,000) with 0 DB writes.
     */
    public function test_dry_run_default_detects_all_5_eligible_invoices_with_zero_database_writes(): void
    {
        $payCountBefore = FeePayment::count();

        $exitCode = Artisan::call('fees:reconcile-class-five-roll3-jan-may-600');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('DRY-RUN MODE ACTIVE', $output);
        $this->assertStringContainsString('5 / 5', $output);
        $this->assertStringContainsString('4000.00 -> ৳3000.00', $output);
        $this->assertStringContainsString('-1000.00', $output);
        $this->assertStringContainsString('2026-11-17', $output);
        $this->assertStringContainsString('REC-BULK-20261117-9314', $output);
        $this->assertStringContainsString('DRY-RUN MODE: 0 rows modified.', $output);

        foreach ($this->janMayInvoices as $inv) {
            $fresh = $inv->fresh();
            $this->assertEquals('800.00', bcadd((string) $fresh->net_amount, '0.00', 2));
            $this->assertEquals('0.00', bcadd((string) $fresh->discount, '0.00', 2));
            $this->assertEquals('800.00', bcadd((string) $fresh->paid_amount, '0.00', 2));
        }
        foreach ($this->janMayPayments as $pay) {
            $this->assertSame('Active', $pay->fresh()->status);
        }

        $this->assertSame($payCountBefore, FeePayment::count());
        $this->assertSame(0, FeeAdjustmentLog::count());
        $this->assertSame(0, SmsLog::count());
    }

    /**
     * Test 2 (--apply Atomic Flow, Sibling Isolation, Single & Master POS Receipts, and 0 SMS).
     */
    public function test_apply_reconciles_jan_may_isolates_siblings_renders_single_and_master_pos_and_sends_zero_sms(): void
    {
        Log::spy();

        $junDecBefore = collect($this->junDecInvoices)
            ->mapWithKeys(fn (FeeInvoice $i): array => [
                $i->invoice_no => $i->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']),
            ])
            ->all();
        $siblingInvBefore = $this->siblingExamInvoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']);
        $siblingPayBefore = $this->siblingExamPayment->fresh()->only(['receipt_no', 'paid_amount', 'payment_date', 'status']);
        $roll6Before = $this->roll6Invoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']);

        $exitCode = Artisan::call('fees:reconcile-class-five-roll3-jan-may-600', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('APPLIED SUCCESSFULLY: Reconciled 5 Jan-May 2026 invoice(s)', $output);
        $this->assertStringContainsString('REC-BULK-20261117-9314', $output);

        // Verify all 5 old payments Reversed, all 5 invoices normalized 800-200=600 Paid, and 5 new Active 600 payments created
        foreach ($this->janMayInvoices as $idx => $inv) {
            $oldPay = $this->janMayPayments[$idx]->fresh();
            $this->assertSame('Reversed', $oldPay->status);
            $this->assertEquals('800.00', bcadd((string) $oldPay->reversed_amount, '0.00', 2));
            $this->assertSame($this->adminUser->id, (int) $oldPay->reversed_by);
            $this->assertNotEmpty($oldPay->reversal_reference);

            $freshInv = $inv->fresh();
            $this->assertEquals('800.00', bcadd((string) $freshInv->amount, '0.00', 2));
            $this->assertEquals('200.00', bcadd((string) $freshInv->discount, '0.00', 2));
            $this->assertEquals('600.00', bcadd((string) $freshInv->net_amount, '0.00', 2));
            $this->assertEquals('600.00', bcadd((string) $freshInv->paid_amount, '0.00', 2));
            $this->assertEquals('0.00', bcadd((string) $freshInv->due_amount, '0.00', 2));
            $this->assertSame('Paid', $freshInv->status);

            $newPay = FeePayment::where('fee_invoice_id', $freshInv->id)
                ->where('status', 'Active')
                ->firstOrFail();
            $this->assertSame("REC-BULK-20261117-9314-{$freshInv->id}", $newPay->receipt_no);
            $this->assertSame('REC-BULK-20261117-9314', FeePayment::extractMasterReceiptNo($newPay->receipt_no));
            $this->assertEquals('600.00', bcadd((string) $newPay->paid_amount, '0.00', 2));
            $this->assertSame('2026-11-17', (string) $newPay->payment_date);
            $this->assertSame('Cash', $newPay->payment_method);
            $this->assertStringContainsString($oldPay->receipt_no, (string) $newPay->note);

            // Audit logs per invoice: 1 reversal + 1 decrease (-200)
            $invLogs = FeeAdjustmentLog::where('fee_invoice_id', $freshInv->id)->orderBy('id')->get();
            $this->assertCount(2, $invLogs);
            $decLog = $invLogs->firstWhere('mode', 'decrease');
            $this->assertNotNull($decLog);
            $this->assertEquals('200.00', bcadd((string) $decLog->adjustment_amount, '0.00', 2));
            $this->assertEquals('800.00', bcadd((string) $decLog->new_amount, '0.00', 2));
            $this->assertEquals('200.00', bcadd((string) $decLog->new_discount, '0.00', 2));
            $this->assertEquals('600.00', bcadd((string) $decLog->new_net_amount, '0.00', 2));
            $this->assertStringContainsString('RECON-C5-R3-2026', (string) $decLog->reason);
        }

        // Strict isolation: Jun-Dec Monthly Fees, sibling Exam payment on REC-20261117-4260, custom fee, and Roll 6 untouched
        foreach ($this->junDecInvoices as $jdInv) {
            $this->assertSame(
                $junDecBefore[$jdInv->invoice_no],
                $jdInv->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status'])
            );
        }
        $this->assertSame($siblingInvBefore, $this->siblingExamInvoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']));
        $this->assertSame($siblingPayBefore, $this->siblingExamPayment->fresh()->only(['receipt_no', 'paid_amount', 'payment_date', 'status']));
        $this->assertSame($roll6Before, $this->roll6Invoice->fresh()->only(['amount', 'discount', 'net_amount', 'paid_amount', 'due_amount', 'status']));
        $this->assertEquals('600.00', bcadd((string) $this->roll3CustomFee->fresh()->amount, '0.00', 2));

        // Verify FeeReceiptService: new Master Receipt groups all 5 -> 3,000.00 active total
        $receiptService = app(FeeReceiptService::class);
        $newMasterPays = $receiptService->fetchPaymentsForMasterReceipt('REC-BULK-20261117-9314');
        $this->assertCount(5, $newMasterPays);
        $newMasterData = $receiptService->buildMasterReceiptData($newMasterPays, 'REC-BULK-20261117-9314');
        $this->assertFalse($newMasterData['is_fully_reversed']);
        $this->assertFalse($newMasterData['has_partial_reversals']);
        $this->assertSame('3000.00', $newMasterData['active_grand_total']);
        $this->assertSame('0.00', $newMasterData['reversed_grand_total']);
        $this->assertCount(5, $newMasterData['active_items']);

        // Old Master Receipt REC-20261117-4260 keeps the sibling Exam Fee active (330.00) and shows 4,000.00 reversed
        $oldMasterPays = $receiptService->fetchPaymentsForMasterReceipt('REC-20261117-4260');
        $this->assertCount(6, $oldMasterPays);
        $oldMasterData = $receiptService->buildMasterReceiptData($oldMasterPays, 'REC-20261117-4260');
        $this->assertTrue($oldMasterData['has_partial_reversals']);
        $this->assertSame('330.00', $oldMasterData['active_grand_total']);
        $this->assertSame('4000.00', $oldMasterData['reversed_grand_total']);

        // Both Single Invoice POS (৳600.00) and Master POS (৳3,000.00) HTTP routes render 200 OK
        $this->get(route('fees.receipt.pos_print', 'REC-BULK-20261117-9314'))
            ->assertOk()
            ->assertSee('3,000.00')
            ->assertSee('600.00');

        $firstJanInv = $this->janMayInvoices[0]->fresh();
        $this->get(route('fees.invoice.pos_print', $firstJanInv->id))
            ->assertOk()
            ->assertSee('600.00');

        $this->get(route('fees.receipt.pos_print', "REC-BULK-20261117-9314-{$firstJanInv->id}"))
            ->assertOk()
            ->assertSee('600.00');

        // ZERO SMS sent
        $this->assertSame(0, SmsLog::count());

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $msg, array $ctx): bool => $msg === 'fees:reconcile-class-five-roll3-jan-may-600 applied'
                && $ctx['reconciled_count'] === 5
                && $ctx['master_receipt_no'] === 'REC-BULK-20261117-9314'
                && $ctx['net_delta'] === '-1000.00');
    }

    /**
     * Test 3 (Strict Idempotency): Second --apply finds 0 eligible rows (5 already reconciled) and performs 0 DB writes.
     */
    public function test_apply_is_strictly_idempotent(): void
    {
        $this->artisan('fees:reconcile-class-five-roll3-jan-may-600', ['--apply' => true])->assertExitCode(0);

        $payCount = FeePayment::count();
        $logCount = FeeAdjustmentLog::count();

        $code2 = Artisan::call('fees:reconcile-class-five-roll3-jan-may-600', ['--apply' => true]);
        $out2 = Artisan::output();

        $this->assertSame(0, $code2);
        $this->assertStringContainsString('ALREADY_RECONCILED', $out2);
        $this->assertStringContainsString('0 rows modified', $out2);
        $this->assertSame($payCount, FeePayment::count());
        $this->assertSame($logCount, FeeAdjustmentLog::count());
    }

    /**
     * Test 4 (All-or-Nothing Safety Guard): If 1 of 5 has unexpected state OR custom fee != 600, exits 1 and writes 0 rows.
     */
    public function test_all_or_nothing_safety_guard_blocks_on_single_mismatch_or_custom_fee_change(): void
    {
        $payCountBefore = FeePayment::count();

        // Case A: Custom fee changed from 600.00 to 500.00 -> aborts with 0 writes across all 5
        $this->roll3CustomFee->update(['amount' => 500.00]);
        $codeA = Artisan::call('fees:reconcile-class-five-roll3-jan-may-600', ['--apply' => true]);
        $this->assertSame(1, $codeA);
        $this->assertSame($payCountBefore, FeePayment::count());
        $this->assertSame(0, FeeAdjustmentLog::count());
        foreach ($this->janMayPayments as $p) {
            $this->assertSame('Active', $p->fresh()->status);
        }

        // Case B: Restore custom fee, corrupt ONLY May invoice (5th of 5) -> none of Jan-Apr are mutated!
        $this->roll3CustomFee->update(['amount' => 600.00]);
        $this->janMayInvoices[4]->update(['paid_amount' => 400.00, 'due_amount' => 400.00, 'status' => 'Partial']);

        $codeB = Artisan::call('fees:reconcile-class-five-roll3-jan-may-600', ['--apply' => true]);
        $this->assertSame(1, $codeB);
        $this->assertSame($payCountBefore, FeePayment::count());
        $this->assertSame(0, FeeAdjustmentLog::count());

        for ($i = 0; $i < 4; $i++) {
            $this->assertSame('Active', $this->janMayPayments[$i]->fresh()->status);
            $this->assertEquals('800.00', bcadd((string) $this->janMayInvoices[$i]->fresh()->net_amount, '0.00', 2));
        }
    }

    /**
     * Test 5 (Financial Report Parity): -200/month on Jan-May, 0.00 on Jun-Dec & Exam, -1,000 on 2026-11-17 & Full Session, 12-Month Sum === Full Session.
     */
    public function test_financial_report_parity_across_monthly_custom_range_and_full_session(): void
    {
        $reportService = app(ClassWiseFinancialReportService::class);
        $allMonths = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
        $janMay = ['January', 'February', 'March', 'April', 'May'];

        $beforeMonthly = [];
        foreach ($allMonths as $m) {
            $beforeMonthly[$m] = $reportService->generateReport([
                'session_year_id' => $this->session2026->id,
                'period_type'     => 'monthly',
                'month'           => $m,
                'year'            => 2026,
            ]);
        }
        $beforeCustom = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-11-17',
            'end_date'        => '2026-11-17',
        ]);
        $beforeFull = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'all',
        ]);

        $this->artisan('fees:reconcile-class-five-roll3-jan-may-600', ['--apply' => true])->assertExitCode(0);

        $sum12Due = '0.00';
        $sum12Collected = '0.00';
        $sum12Outstanding = '0.00';

        foreach ($allMonths as $m) {
            $after = $reportService->generateReport([
                'session_year_id' => $this->session2026->id,
                'period_type'     => 'monthly',
                'month'           => $m,
                'year'            => 2026,
            ]);

            $dueDelta = bcsub($after['grand_total']['due']['monthly_fee'], $beforeMonthly[$m]['grand_total']['due']['monthly_fee'], 2);
            $colDelta = bcsub($after['grand_total']['collected']['monthly_fee'], $beforeMonthly[$m]['grand_total']['collected']['monthly_fee'], 2);
            $examDueDelta = bcsub($after['grand_total']['due']['exam_fees'], $beforeMonthly[$m]['grand_total']['due']['exam_fees'], 2);
            $examColDelta = bcsub($after['grand_total']['collected']['exam_fees'], $beforeMonthly[$m]['grand_total']['exam_fees'] ?? $beforeMonthly[$m]['grand_total']['collected']['exam_fees'], 2);
            $outDelta = bcsub($after['grand_total']['outstanding']['total'], $beforeMonthly[$m]['grand_total']['outstanding']['total'], 2);

            if (in_array($m, $janMay, true)) {
                $this->assertSame('-200.00', $dueDelta, "Expected -200.00 Monthly Due delta in {$m}");
                $this->assertSame('-200.00', $colDelta, "Expected -200.00 Monthly Collected delta in {$m}");
            } else {
                $this->assertSame('0.00', $dueDelta, "Expected 0.00 Monthly Due delta in {$m}");
                $this->assertSame('0.00', $colDelta, "Expected 0.00 Monthly Collected delta in {$m}");
            }

            $this->assertSame('0.00', $examDueDelta, "Expected 0.00 Exam Due delta in {$m}");
            $this->assertSame('0.00', $examColDelta, "Expected 0.00 Exam Collected delta in {$m}");
            $this->assertSame('0.00', $outDelta, "Expected 0.00 Outstanding delta in {$m}");

            $sum12Due = bcadd($sum12Due, $after['grand_total']['due']['total'], 2);
            $sum12Collected = bcadd($sum12Collected, $after['grand_total']['collected']['total'], 2);
            $sum12Outstanding = bcadd($sum12Outstanding, $after['grand_total']['outstanding']['total'], 2);
        }

        // Custom Range 2026-11-17: exact -1,000.00 Collected Monthly Fee (Exam Fees & Roll 6 untouched!)
        $afterCustom = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-11-17',
            'end_date'        => '2026-11-17',
        ]);
        $this->assertSame(
            '-1000.00',
            bcsub($afterCustom['grand_total']['collected']['monthly_fee'], $beforeCustom['grand_total']['collected']['monthly_fee'], 2)
        );
        $this->assertSame(
            '-1000.00',
            bcsub($afterCustom['grand_total']['collected']['total'], $beforeCustom['grand_total']['collected']['total'], 2)
        );
        $this->assertSame(
            '0.00',
            bcsub($afterCustom['grand_total']['collected']['exam_fees'], $beforeCustom['grand_total']['collected']['exam_fees'], 2)
        );

        // Full Session: exact -1,000.00 Due & Collected, 0.00 Outstanding, and 12-Month Sum === Full Session (0.00 delta)
        $afterFull = $reportService->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'all',
        ]);
        $this->assertSame(
            '-1000.00',
            bcsub($afterFull['grand_total']['due']['total'], $beforeFull['grand_total']['due']['total'], 2)
        );
        $this->assertSame(
            '-1000.00',
            bcsub($afterFull['grand_total']['collected']['total'], $beforeFull['grand_total']['collected']['total'], 2)
        );
        $this->assertSame(
            '0.00',
            bcsub($afterFull['grand_total']['outstanding']['total'], $beforeFull['grand_total']['outstanding']['total'], 2)
        );

        $this->assertSame('0.00', bcsub($sum12Due, $afterFull['grand_total']['due']['total'], 2));
        $this->assertSame('0.00', bcsub($sum12Collected, $afterFull['grand_total']['collected']['total'], 2));
        $this->assertSame('0.00', bcsub($sum12Outstanding, $afterFull['grand_total']['outstanding']['total'], 2));
    }
}
