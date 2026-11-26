<?php

namespace Tests\Feature;

use App\Models\AdmissionFeeStructure;
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
use App\Services\ClassWiseFinancialReportService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReconcileCrossMonthDueDatesCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Branch $branch;
    private SessionYear $session2026;
    private Classes $classPlay;
    private Classes $classOne;
    private Section $sectionPlayA;
    private Section $sectionOneA;
    private FeeCategory $monthlyCat;
    private FeeSetup $playMonthlySetup;
    private FeeSetup $oneMonthlySetup;
    private Student $playStudent;
    private Student $oneStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'Admin Reconcile Tester',
            'email'    => 'admin_reconcile_dd@macsschool.com',
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

        $this->classPlay = Classes::create([
            'class_name' => 'Play',
            'status'     => 'Active',
            'user_id'    => $this->adminUser->id,
        ]);

        $this->classOne = Classes::create([
            'class_name' => 'One',
            'status'     => 'Active',
            'user_id'    => $this->adminUser->id,
        ]);

        $this->sectionPlayA = Section::create([
            'section_name' => 'A',
            'class_id'     => $this->classPlay->id,
            'status'       => 'Active',
            'user_id'      => $this->adminUser->id,
        ]);

        $this->sectionOneA = Section::create([
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

        $this->playMonthlySetup = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classPlay->id,
            'fee_category_id' => $this->monthlyCat->id,
            'fee_month'       => 'Monthly',
            'amount'          => 650.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
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

        $this->playStudent = $this->createStudent('26010P00001', 'Play Student', 1, $this->classPlay, $this->sectionPlayA);
        $this->oneStudent  = $this->createStudent('26010100001', 'One Student', 1, $this->classOne, $this->sectionOneA);
    }

    private function createStudent(
        string $identity,
        string $name,
        int $roll,
        Classes $class,
        Section $section
    ): Student {
        return Student::create([
            'student_identity'      => $identity,
            'student_name'          => $name,
            'roll_number'           => $roll,
            'dob'                   => '2019-01-01',
            'gender'                => 'Female',
            'religion'              => 'Islam',
            'father_name'           => 'Father ' . $roll,
            'father_mobile'         => '0171000000' . $roll,
            'mother_name'           => 'Mother ' . $roll,
            'mother_mobile'         => '0172000000' . $roll,
            'guardian_name'         => 'Guardian ' . $roll,
            'guardian_mobile'       => '0171000000' . $roll,
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session2026->id,
            'class_id'              => $class->id,
            'section_id'            => $section->id,
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
    }

    private function createInvoice(
        Student $student,
        ?FeeSetup $setup,
        string $invoiceNo,
        ?string $dueDate,
        float $amount = 650.00,
        array $overrides = []
    ): FeeInvoice {
        return FeeInvoice::create(array_merge([
            'invoice_no'      => $invoiceNo,
            'student_id'      => $student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'fee_setup_id'    => $setup?->id,
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

    /**
     * 1. Dry-Run Default Safety: Detects cross-month INV-202603-* with ZERO DB writes.
     */
    public function test_dry_run_default_detects_mismatches_with_zero_database_writes(): void
    {
        $unpaid = $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202603-0001', '2026-08-02', 650.00);
        $paid = $this->createInvoice($this->oneStudent, $this->oneMonthlySetup, 'INV-202603-0002', '2026-08-02', 700.00, [
            'paid_amount' => 700.00,
            'due_amount'  => 0.00,
            'status'      => 'Paid',
        ]);
        $cancelled = $this->createInvoice($this->oneStudent, $this->oneMonthlySetup, 'INV-202603-0003', '2026-08-02', 700.00, [
            'due_amount' => 0.00,
            'status'     => 'Cancelled',
        ]);

        $exitCode = Artisan::call('fees:reconcile-cross-month-due-dates');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('2026-03', $output);
        $this->assertStringContainsString('2026-08', $output);
        $this->assertStringContainsString('2026-08-02', $output);
        $this->assertStringContainsString('2026-03-10', $output);
        $this->assertStringContainsString('Active (Unpaid)', $output);
        $this->assertStringContainsString('Active (Paid)', $output);
        $this->assertStringContainsString('Cancelled', $output);
        $this->assertStringContainsString('DRY-RUN MODE: 0 rows modified. Re-run with --apply to normalize due_date values.', $output);

        foreach ([$unpaid, $paid, $cancelled] as $inv) {
            $this->assertSame('2026-08-02', $inv->fresh()->due_date);
        }
    }

    /**
     * 2. --apply Normalization & Zero Monetary / Payment / Status Mutation.
     */
    public function test_apply_normalizes_due_date_only_with_zero_monetary_or_payment_mutations(): void
    {
        Log::spy();

        $unpaidWithFine = $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202603-0010', '2026-08-02', 650.00, [
            'discount'    => 100.00,
            'net_amount'  => 550.00,
            'fine_amount' => 20.00,
            'fine_source' => 'manual',
            'fine_reason' => 'Late fee',
            'due_amount'  => 570.00,
            'status'      => 'Unpaid',
        ]);

        $paid = $this->createInvoice($this->oneStudent, $this->oneMonthlySetup, 'INV-202603-0011', '2026-08-02', 250.00, [
            'paid_amount' => 250.00,
            'due_amount'  => 0.00,
            'status'      => 'Paid',
        ]);
        $payment = FeePayment::create([
            'fee_invoice_id' => $paid->id,
            'student_id'     => $this->oneStudent->id,
            'receipt_no'     => 'REC-20260921-0011',
            'paid_amount'    => 250.00,
            'fine_amount'    => 0.00,
            'payment_method' => 'Cash',
            'payment_date'   => '2026-09-21',
            'status'         => 'Active',
            'collected_by'   => $this->adminUser->id,
        ]);

        $nullDue = $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202603-0012', null, 650.00);

        $beforeSnapshots = [];
        foreach ([$unpaidWithFine, $paid, $nullDue] as $inv) {
            $beforeSnapshots[$inv->id] = $inv->fresh()->only([
                'invoice_no', 'amount', 'discount', 'net_amount', 'fine_amount',
                'fine_source', 'fine_reason', 'paid_amount', 'due_amount', 'status',
            ]);
        }
        $paymentBefore = $payment->fresh()->only([
            'receipt_no', 'paid_amount', 'fine_amount', 'payment_method', 'payment_date', 'status',
        ]);

        $this->artisan('fees:reconcile-cross-month-due-dates', ['--apply' => true])
            ->expectsOutputToContain('APPLIED SUCCESSFULLY: Normalized due_date on 3 invoice(s).')
            ->assertExitCode(0);

        foreach ([$unpaidWithFine, $paid, $nullDue] as $inv) {
            $fresh = $inv->fresh();
            $this->assertSame('2026-03-10', $fresh->due_date);
            $this->assertSame($beforeSnapshots[$inv->id], $fresh->only([
                'invoice_no', 'amount', 'discount', 'net_amount', 'fine_amount',
                'fine_source', 'fine_reason', 'paid_amount', 'due_amount', 'status',
            ]));
        }

        $this->assertSame($paymentBefore, $payment->fresh()->only([
            'receipt_no', 'paid_amount', 'fine_amount', 'payment_method', 'payment_date', 'status',
        ]));
        $this->assertSame(0, FeeAdjustmentLog::count());

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'fees:reconcile-cross-month-due-dates applied'
                    && $context['affected_count'] === 3
                    && $context['billing_months'] === ['2026-03']
                    && $context['target_due_dates'] === ['2026-03-10'];
            });
    }

    /**
     * 3. Strict Idempotency: Second --apply finds 0 mismatches and performs 0 writes.
     */
    public function test_apply_is_strictly_idempotent(): void
    {
        $inv1 = $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202603-0020', '2026-08-02', 650.00);
        $inv2 = $this->createInvoice($this->oneStudent, $this->oneMonthlySetup, 'INV-202603-0021', '2026-08-02', 700.00);

        $this->artisan('fees:reconcile-cross-month-due-dates', ['--apply' => true])
            ->expectsOutputToContain('Normalized due_date on 2 invoice(s).')
            ->assertExitCode(0);

        DB::table('fee_invoices')->whereIn('id', [$inv1->id, $inv2->id])->update([
            'updated_at' => '2026-03-10 12:00:00',
        ]);

        $this->artisan('fees:reconcile-cross-month-due-dates', ['--apply' => true])
            ->expectsOutputToContain('No cross-month due_date mismatches found (0 rows require normalization).')
            ->assertExitCode(0);

        $this->assertSame('2026-03-10 12:00:00', (string) DB::table('fee_invoices')->where('id', $inv1->id)->value('updated_at'));
        $this->assertSame('2026-03-10 12:00:00', (string) DB::table('fee_invoices')->where('id', $inv2->id)->value('updated_at'));
    }

    /**
     * 4. Strict Exclusions (in-month date, Admission, non-dated, --month filter, invalid --month).
     */
    public function test_strict_exclusions_and_month_filter_validation(): void
    {
        // In-month due_date -> MUST remain untouched (2026-03-22)
        $inMonth = $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202603-0030', '2026-03-22', 650.00);

        // Admission invoice -> MUST be skipped
        $admStruct = AdmissionFeeStructure::create([
            'branch_id'                => $this->branch->id,
            'class_id'                 => $this->classPlay->id,
            'session_year_id'          => $this->session2026->id,
            'structure_name'           => 'Play Admission',
            'version'                  => 1,
            'status'                   => 'Active',
            'total_amount'             => 3950.00,
            'includes_monthly_tuition' => false,
            'created_by'               => $this->adminUser->id,
        ]);
        $admissionInv = $this->createInvoice($this->playStudent, null, 'INV-ADM-S1-202603-0001', '2026-08-02', 3950.00, [
            'fee_setup_id'               => null,
            'admission_fee_structure_id' => $admStruct->id,
            'branch_name_snapshot'       => $this->branch->branch_name,
        ]);

        // Non-dated invoice -> MUST be skipped
        $nonDated = $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-CUSTOM-0001', '2026-08-02', 650.00);

        // Mismatched March vs Mismatched April
        $marchBad = $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202603-0031', '2026-08-02', 650.00);
        $aprilBad = $this->createInvoice($this->oneStudent, $this->oneMonthlySetup, 'INV-202604-0031', '2026-08-02', 700.00);

        // Invalid --month values fail with exit code 1
        $this->artisan('fees:reconcile-cross-month-due-dates', ['--month' => '2026-13'])->assertExitCode(1);
        $this->artisan('fees:reconcile-cross-month-due-dates', ['--month' => 'March'])->assertExitCode(1);

        // --month=2026-03 --apply touches ONLY $marchBad
        $this->artisan('fees:reconcile-cross-month-due-dates', ['--month' => '2026-03', '--apply' => true])
            ->expectsOutputToContain('Normalized due_date on 1 invoice(s).')
            ->assertExitCode(0);

        $this->assertSame('2026-03-10', $marchBad->fresh()->due_date);
        $this->assertSame('2026-03-22', $inMonth->fresh()->due_date);
        $this->assertSame('2026-08-02', $admissionInv->fresh()->due_date);
        $this->assertSame('2026-08-02', $nonDated->fresh()->due_date);
        $this->assertSame('2026-08-02', $aprilBad->fresh()->due_date);
    }

    /**
     * 5. Cross-Report Consistency: ClassWiseFinancialReportService has 0.00 delta AND due_date queries shift cleanly to March.
     */
    public function test_cross_report_consistency_and_due_date_outstanding_shift(): void
    {
        // March Paid (Play) + March Unpaid (One) both carrying buggy August due_date, plus clean August Paid (Play)
        $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202603-0040', '2026-08-02', 650.00, [
            'paid_amount' => 650.00,
            'due_amount'  => 0.00,
            'status'      => 'Paid',
        ]);
        $this->createInvoice($this->oneStudent, $this->oneMonthlySetup, 'INV-202603-0041', '2026-08-02', 390.00, [
            'due_amount' => 390.00,
            'status'     => 'Unpaid',
        ]);
        $this->createInvoice($this->playStudent, $this->playMonthlySetup, 'INV-202608-0040', '2026-08-10', 650.00, [
            'paid_amount' => 650.00,
            'due_amount'  => 0.00,
            'status'      => 'Paid',
        ]);

        $reportService = app(ClassWiseFinancialReportService::class);
        $marBefore = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'March', 'year' => 2026]);
        $augBefore = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'August', 'year' => 2026]);
        $allBefore = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'all']);

        // Before --apply, due_date-filtered August outstanding wrongly shows 390.00 and March shows 0.00
        $dueDateAugBefore = (float) DB::table('fee_invoices')->whereBetween('due_date', ['2026-08-01', '2026-08-31'])->sum('due_amount');
        $dueDateMarBefore = (float) DB::table('fee_invoices')->whereBetween('due_date', ['2026-03-01', '2026-03-31'])->sum('due_amount');
        $this->assertEquals(390.00, $dueDateAugBefore);
        $this->assertEquals(0.00, $dueDateMarBefore);

        $this->artisan('fees:reconcile-cross-month-due-dates', ['--apply' => true])
            ->expectsOutputToContain('Normalized due_date on 2 invoice(s).')
            ->assertExitCode(0);

        $marAfter = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'March', 'year' => 2026]);
        $augAfter = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'monthly', 'month' => 'August', 'year' => 2026]);
        $allAfter = $reportService->generateReport(['session_year_id' => $this->session2026->id, 'period_type' => 'all']);

        // ClassWiseFinancialReportService stays exact 0.00 delta across March, August, and Full Session
        $this->assertSame($marBefore['grand_total'], $marAfter['grand_total']);
        $this->assertSame($augBefore['grand_total'], $augAfter['grand_total']);
        $this->assertSame($allBefore['grand_total'], $allAfter['grand_total']);

        // Direct due_date-filtered outstanding shifts cleanly from August (0.00) to March (390.00)
        $dueDateAugAfter = (float) DB::table('fee_invoices')->whereBetween('due_date', ['2026-08-01', '2026-08-31'])->sum('due_amount');
        $dueDateMarAfter = (float) DB::table('fee_invoices')->whereBetween('due_date', ['2026-03-01', '2026-03-31'])->sum('due_amount');
        $this->assertEquals(0.00, $dueDateAugAfter);
        $this->assertEquals(390.00, $dueDateMarAfter);
    }
}
