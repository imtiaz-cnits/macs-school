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
use App\Services\LateFineService;
use Carbon\Carbon;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutomaticMonthlyLateFineTest extends TestCase
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
    private FeeSetup $termExamSetup2027;
    private FeeSetup $monthlyExamSetup2027;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name'     => 'Admin Auto Fine Tester',
            'email'    => 'admin_auto_lf@macsschool.com',
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

        $this->termExamSetup2027 = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2027->id,
            'class_id'        => $this->classPlay->id,
            'fee_category_id' => $this->termExamCat->id,
            'fee_month'       => 'January',
            'amount'          => 400.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->monthlyExamSetup2027 = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2027->id,
            'class_id'        => $this->classPlay->id,
            'fee_category_id' => $this->monthlyExamCat->id,
            'fee_month'       => 'January',
            'amount'          => 200.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createStudent(
        string $identity,
        string $name,
        int $roll,
        ?string $admissionDate = '2026-01-01',
        string $createdAt = '2026-01-01 09:00:00',
        ?int $sessionYearId = null,
        string $status = 'Active'
    ): Student {
        $student = Student::create([
            'student_identity'      => $identity,
            'student_name'          => $name,
            'roll_number'           => $roll,
            'dob'                   => '2018-01-01',
            'gender'                => 'Female',
            'religion'              => 'Islam',
            'father_name'           => 'Father ' . $roll,
            'father_mobile'         => '017100000' . str_pad((string) $roll, 2, '0', STR_PAD_LEFT),
            'mother_name'           => 'Mother ' . $roll,
            'mother_mobile'         => '017200000' . str_pad((string) $roll, 2, '0', STR_PAD_LEFT),
            'guardian_name'         => 'Guardian ' . $roll,
            'guardian_mobile'       => '017100000' . str_pad((string) $roll, 2, '0', STR_PAD_LEFT),
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $sessionYearId ?? $this->session2027->id,
            'class_id'              => $this->classPlay->id,
            'section_id'            => $this->sectionA->id,
            'admission_date'        => $admissionDate,
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

        DB::table('students')->where('id', $student->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $student->fresh();
    }

    private function createInvoice(
        Student $student,
        ?FeeSetup $setup,
        string $invoiceNo,
        string $dueDate,
        float $amount = 900.00,
        string $createdAt = '2027-01-01 00:05:00',
        array $overrides = []
    ): FeeInvoice {
        $invoice = FeeInvoice::create(array_merge([
            'invoice_no'      => $invoiceNo,
            'student_id'      => $student->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $setup?->session_year_id ?? $this->session2027->id,
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

        DB::table('fee_invoices')->where('id', $invoice->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $invoice->fresh();
    }

    /**
     * 1. 2026 Calendar Lock: Running in 2026 (including Nov 26 & Dec 10) applies ZERO fines.
     */
    public function test_2026_calendar_lock_blocks_auto_fine_completely(): void
    {
        $student = $this->createStudent('26010100001', 'Azmira Khatun', 1, '2026-01-01', '2026-01-01 09:00:00', $this->session2026->id);

        $nov2026 = $this->createInvoice($student, $this->monthlySetup2026, 'INV-202611-0001', '2026-11-10', 900.00, '2026-11-01 00:05:00');
        $dec2026 = $this->createInvoice($student, $this->monthlySetup2026, 'INV-202612-0001', '2026-12-10', 900.00, '2026-12-01 00:05:00');
        $jan2027PreGen = $this->createInvoice($student, $this->monthlySetup2027, 'INV-202701-0001', '2027-01-10', 900.00, '2026-12-01 00:05:00');

        $service = app(LateFineService::class);

        $resNov = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2026-11-26 09:00:00', 'Asia/Dhaka'));
        $this->assertSame('pre_2027_calendar', $resNov['skipped_reason']);
        $this->assertSame(0, $resNov['eligible_count']);
        $this->assertSame(0, $resNov['applied_count']);
        $this->assertSame('0.00', $resNov['total_fine_amount']);

        $resDec = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2026-12-10 00:15:00', 'Asia/Dhaka'));
        $this->assertSame('pre_2027_calendar', $resDec['skipped_reason']);
        $this->assertSame(0, $resDec['applied_count']);

        $this->artisan('fees:apply-late-fines', ['--date' => '2026-12-10'])
            ->expectsOutputToContain('pre_2027_calendar')
            ->assertExitCode(0);

        foreach ([$nov2026, $dec2026, $jan2027PreGen] as $inv) {
            $inv->refresh();
            $this->assertSame('0.00', $inv->fineAmountDecimal());
            $this->assertNull($inv->fine_source);
            $this->assertNull($inv->auto_fine_applied_at);
            $this->assertEquals('900.00', bcadd((string) $inv->due_amount, '0.00', 2));
        }

        $this->assertSame(0, FeeAdjustmentLog::count());
    }

    /**
     * 2. Double-Gate 2027 Lock: Even on 2027-01-08, unpaid 2026 Monthly Fee invoices stay 100% immune.
     */
    public function test_double_gate_2027_lock_protects_unpaid_2026_invoices_even_in_2027(): void
    {
        $student = $this->createStudent('26010100002', 'Nusrat Jahan', 2, '2026-01-01', '2026-01-01 09:00:00');

        $jan2026 = $this->createInvoice($student, $this->monthlySetup2026, 'INV-202601-0001', '2026-01-10', 900.00, '2026-01-01 00:05:00');
        $dec2026 = $this->createInvoice($student, $this->monthlySetup2026, 'INV-202612-0001', '2026-12-10', 900.00, '2026-12-01 00:05:00');
        $jan2027 = $this->createInvoice($student, $this->monthlySetup2027, 'INV-202701-0001', '2027-01-10', 900.00, '2027-01-01 00:05:00');

        $result = app(LateFineService::class)->applyAutomaticMonthlyLateFines(
            Carbon::parse('2027-01-08 00:15:00', 'Asia/Dhaka')
        );

        $this->assertNull($result['skipped_reason']);
        $this->assertSame(1, $result['eligible_count']);
        $this->assertSame(1, $result['applied_count']);
        $this->assertSame('20.00', $result['total_fine_amount']);
        $this->assertSame(2, $result['skipped_counts']['pre_2027_invoice']);

        foreach ([$jan2026, $dec2026] as $oldInv) {
            $oldInv->refresh();
            $this->assertSame('0.00', $oldInv->fineAmountDecimal());
            $this->assertNull($oldInv->fine_source);
            $this->assertNull($oldInv->auto_fine_applied_at);
            $this->assertEquals('900.00', bcadd((string) $oldInv->due_amount, '0.00', 2));
        }

        $jan2027->refresh();
        $this->assertSame('20.00', $jan2027->fineAmountDecimal());
        $this->assertSame(LateFineService::SOURCE_AUTO, $jan2027->fine_source);
        $this->assertNotNull($jan2027->auto_fine_applied_at);
        $this->assertEquals('900.00', bcadd((string) $jan2027->net_amount, '0.00', 2));
        $this->assertEquals('920.00', bcadd((string) $jan2027->due_amount, '0.00', 2));
    }

    /**
     * 3. Day-7 vs Day-8 Boundary (`Asia/Dhaka`): 23:59:59 on 7th -> 0 applied; 00:00:01 on 8th -> ৳20 applied.
     */
    public function test_day_7_vs_day_8_asia_dhaka_boundary_and_system_audit_log(): void
    {
        $studentA = $this->createStudent('27010100001', 'Sadia Islam', 1, '2026-01-01', '2026-01-01 09:00:00');
        $studentB = $this->createStudent('27010100002', 'Mim Akter', 2, '2026-01-01', '2026-01-01 09:00:00');

        $janInvA = $this->createInvoice($studentA, $this->monthlySetup2027, 'INV-202701-0001', '2027-01-10', 900.00, '2027-01-01 00:05:00');
        $febInvA = $this->createInvoice($studentA, $this->monthlySetup2027, 'INV-202702-0001', '2027-02-10', 900.00, '2027-01-01 00:05:00');

        $service = app(LateFineService::class);

        // 2027-01-07 23:59:59 Asia/Dhaka -> strictly 0 applied
        $resDay7 = $service->applyAutomaticMonthlyLateFines(
            Carbon::parse('2027-01-07 23:59:59', 'Asia/Dhaka')
        );
        $this->assertSame(0, $resDay7['applied_count']);
        $this->assertSame(2, $resDay7['skipped_counts']['before_day_8_cutoff']);
        $this->assertSame('0.00', $janInvA->fresh()->fineAmountDecimal());
        $this->assertSame('0.00', $febInvA->fresh()->fineAmountDecimal());

        // 2027-01-08 00:00:01 Asia/Dhaka -> Jan gets ৳20.00, Feb stays 0.00
        $resDay8 = $service->applyAutomaticMonthlyLateFines(
            Carbon::parse('2027-01-08 00:00:01', 'Asia/Dhaka')
        );
        $this->assertSame(1, $resDay8['applied_count']);
        $this->assertSame(1, $resDay8['skipped_counts']['before_day_8_cutoff']);

        $janInvA->refresh();
        $febInvA->refresh();

        $this->assertSame('20.00', $janInvA->fineAmountDecimal());
        $this->assertSame('auto', $janInvA->fine_source);
        $this->assertSame(LateFineService::AUTO_FINE_REASON, $janInvA->fine_reason);
        $this->assertEquals('900.00', bcadd((string) $janInvA->net_amount, '0.00', 2));
        $this->assertEquals('920.00', bcadd((string) $janInvA->due_amount, '0.00', 2));
        $this->assertNotNull($janInvA->auto_fine_applied_at);

        $this->assertSame('0.00', $febInvA->fineAmountDecimal());
        $this->assertNull($febInvA->fine_source);

        // Audit log must record user_id = null (automated system action)
        $log = FeeAdjustmentLog::where('fee_invoice_id', $janInvA->id)->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertSame('increase', $log->mode);
        $this->assertEquals('20.00', bcadd((string) $log->adjustment_amount, '0.00', 2));
        $this->assertEquals('900.00', bcadd((string) $log->previous_net_amount, '0.00', 2));
        $this->assertEquals('900.00', bcadd((string) $log->new_net_amount, '0.00', 2));
        $this->assertEquals('900.00', bcadd((string) $log->previous_due_amount, '0.00', 2));
        $this->assertEquals('920.00', bcadd((string) $log->new_due_amount, '0.00', 2));

        // Verify explicit Asia/Dhaka conversion when caller passes a UTC Carbon instance:
        // 2027-01-07 18:00:01 UTC == 2027-01-08 00:00:01 Asia/Dhaka
        $janInvB = $this->createInvoice($studentB, $this->monthlySetup2027, 'INV-202701-0002', '2027-01-10', 900.00, '2027-01-01 00:05:00');
        $resUtcConverted = $service->applyAutomaticMonthlyLateFines(
            Carbon::create(2027, 1, 7, 18, 0, 1, 'UTC')
        );
        $this->assertSame('2027-01-08', $resUtcConverted['as_of_date']);
        $this->assertSame(1, $resUtcConverted['applied_count']);
        $this->assertSame('20.00', $janInvB->fresh()->fineAmountDecimal());
    }

    /**
     * 4. Idempotency: Running twice on 2027-01-08 and again on 2027-01-15 applies ৳20.00 exactly once.
     */
    public function test_idempotency_across_repeated_runs_never_stacks_fine(): void
    {
        $student = $this->createStudent('27010100003', 'Tahmina Akter', 3);
        $janInv = $this->createInvoice($student, $this->monthlySetup2027, 'INV-202701-0003', '2027-01-10', 900.00, '2027-01-01 00:05:00');

        $service = app(LateFineService::class);

        $run1 = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-01-08 00:15:00', 'Asia/Dhaka'));
        $run2 = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-01-08 14:30:00', 'Asia/Dhaka'));
        $this->artisan('fees:apply-late-fines', ['--date' => '2027-01-15'])->assertExitCode(0);

        $this->assertSame(1, $run1['applied_count']);
        $this->assertSame(0, $run2['applied_count']);

        $janInv->refresh();
        $this->assertSame('20.00', $janInv->fineAmountDecimal());
        $this->assertEquals('920.00', bcadd((string) $janInv->due_amount, '0.00', 2));
        $this->assertSame(1, FeeAdjustmentLog::where('fee_invoice_id', $janInv->id)->count());
    }

    /**
     * 5. Mid-Month Admission Exemption (explicit `admission_date`, `NULL` fallback to `created_at`, and late invoice `created_at`).
     */
    public function test_mid_month_admission_and_late_invoice_exemptions_protect_first_month_only(): void
    {
        // A: Explicit admission_date = 2027-01-08 (> 2027-01-07)
        $stuMidDate = $this->createStudent('27010100010', 'Mid Date Student', 10, '2027-01-08', '2027-01-08 10:00:00');
        // B: admission_date = NULL, created_at = 2027-01-10 (> 2027-01-07)
        $stuNullAdm = $this->createStudent('27010100011', 'Null Admission Student', 11, null, '2027-01-10 09:00:00');
        // C: Old student, but Jan invoice generated late on 2027-01-09 (> 2027-01-07)
        $stuLateInv = $this->createStudent('27010100012', 'Late Invoice Student', 12, '2026-01-01', '2026-01-01 09:00:00');
        // D: Control student admitted ON the 7th (2027-01-07) with invoice created ON the 7th -> NOT exempt
        $stuOn7th = $this->createStudent('27010100013', 'Admitted On 7th', 13, '2027-01-07', '2027-01-07 15:00:00');

        $janMidDate = $this->createInvoice($stuMidDate, $this->monthlySetup2027, 'INV-202701-0010', '2027-01-10', 900.00, '2027-01-01 00:05:00');
        $janNullAdm = $this->createInvoice($stuNullAdm, $this->monthlySetup2027, 'INV-202701-0011', '2027-01-10', 900.00, '2027-01-05 00:05:00');
        $janLateInv = $this->createInvoice($stuLateInv, $this->monthlySetup2027, 'INV-202701-0012', '2027-01-10', 900.00, '2027-01-09 11:00:00');
        $janOn7th   = $this->createInvoice($stuOn7th, $this->monthlySetup2027, 'INV-202701-0013', '2027-01-10', 900.00, '2027-01-07 16:00:00');

        // Also generate Feb 2027 invoices for the 3 exempted students on Feb 1
        $febMidDate = $this->createInvoice($stuMidDate, $this->monthlySetup2027, 'INV-202702-0010', '2027-02-10', 900.00, '2027-02-01 00:05:00');
        $febNullAdm = $this->createInvoice($stuNullAdm, $this->monthlySetup2027, 'INV-202702-0011', '2027-02-10', 900.00, '2027-02-01 00:05:00');
        $febLateInv = $this->createInvoice($stuLateInv, $this->monthlySetup2027, 'INV-202702-0012', '2027-02-10', 900.00, '2027-02-01 00:05:00');

        $service = app(LateFineService::class);

        // Run on Jan 15, 2027: ONLY control student admitted on 7th gets Jan fine
        $resJan = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-01-15 00:15:00', 'Asia/Dhaka'));
        $this->assertSame(1, $resJan['applied_count']);
        $this->assertSame(2, $resJan['skipped_counts']['mid_month_admission']);
        $this->assertSame(1, $resJan['skipped_counts']['late_invoice_created']);

        $this->assertSame('0.00', $janMidDate->fresh()->fineAmountDecimal());
        $this->assertSame('0.00', $janNullAdm->fresh()->fineAmountDecimal());
        $this->assertSame('0.00', $janLateInv->fresh()->fineAmountDecimal());
        $this->assertSame('20.00', $janOn7th->fresh()->fineAmountDecimal());

        // Run on Feb 8, 2027: all 3 exempted students now receive Feb 2027 auto-fine, while their Jan 2027 remains 0.00
        $resFeb = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-02-08 00:15:00', 'Asia/Dhaka'));
        $this->assertSame(3, $resFeb['applied_count']);

        $this->assertSame('0.00', $janMidDate->fresh()->fineAmountDecimal());
        $this->assertSame('0.00', $janNullAdm->fresh()->fineAmountDecimal());
        $this->assertSame('0.00', $janLateInv->fresh()->fineAmountDecimal());

        $this->assertSame('20.00', $febMidDate->fresh()->fineAmountDecimal());
        $this->assertSame('20.00', $febNullAdm->fresh()->fineAmountDecimal());
        $this->assertSame('20.00', $febLateInv->fresh()->fineAmountDecimal());
    }

    /**
     * 6. Zero Cross-Month Rollover & Pure Class-wise Financial Report Separation.
     */
    public function test_zero_cross_month_rollover_and_class_wise_financial_report_isolation(): void
    {
        $student = $this->createStudent('27010100020', 'Farhana Yeasmin', 20, '2026-01-01', '2026-01-01 09:00:00', $this->session2027->id);

        $janInv = $this->createInvoice($student, $this->monthlySetup2027, 'INV-202701-0020', '2027-01-10', 900.00, '2027-01-01 00:05:00');
        $febInv = $this->createInvoice($student, $this->monthlySetup2027, 'INV-202702-0020', '2027-02-10', 900.00, '2027-02-01 00:05:00');

        $service = app(LateFineService::class);

        $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-01-08 00:15:00', 'Asia/Dhaka'));
        $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-02-08 00:15:00', 'Asia/Dhaka'));

        $janInv->refresh();
        $febInv->refresh();

        // Strictly ৳20.00 per month — NEVER rolled over into ৳40.00 on February!
        $this->assertSame('20.00', $janInv->fineAmountDecimal());
        $this->assertEquals('920.00', bcadd((string) $janInv->due_amount, '0.00', 2));
        $this->assertSame('20.00', $febInv->fineAmountDecimal());
        $this->assertEquals('920.00', bcadd((string) $febInv->due_amount, '0.00', 2));

        $reportService = app(ClassWiseFinancialReportService::class);

        $janReport = $reportService->generateReport([
            'session_year_id' => $this->session2027->id,
            'period_type'     => 'monthly',
            'month'           => 'January',
            'year'            => 2027,
        ]);
        $this->assertEquals('900.00', $janReport['grand_total']['due']['monthly_fee']);
        $this->assertEquals('20.00', $janReport['grand_total']['due']['other_fees']);
        $this->assertEquals('920.00', $janReport['grand_total']['due']['total']);

        $febReport = $reportService->generateReport([
            'session_year_id' => $this->session2027->id,
            'period_type'     => 'monthly',
            'month'           => 'February',
            'year'            => 2027,
        ]);
        $this->assertEquals('900.00', $febReport['grand_total']['due']['monthly_fee']);
        $this->assertEquals('20.00', $febReport['grand_total']['due']['other_fees']);
        $this->assertEquals('920.00', $febReport['grand_total']['due']['total']);

        $full2027Report = $reportService->generateReport([
            'session_year_id' => $this->session2027->id,
            'period_type'     => 'all',
        ]);
        $this->assertEquals('1800.00', $full2027Report['grand_total']['due']['monthly_fee']);
        $this->assertEquals('40.00', $full2027Report['grand_total']['due']['other_fees']);
        $this->assertEquals('1840.00', $full2027Report['grand_total']['due']['total']);
    }

    /**
     * 7. Manual + Auto Coexistence (2027+) & Pre-8th vs Post-8th Waiver Anti-Resurrection.
     */
    public function test_manual_auto_coexistence_and_pre_vs_post_8th_waiver_anti_resurrection(): void
    {
        $stuManual = $this->createStudent('27010100030', 'Manual Preserved', 30);
        $stuPreWaive = $this->createStudent('27010100031', 'Pre-8th Waived', 31);
        $stuAutoWaive = $this->createStudent('27010100032', 'Auto Waived', 32);

        $invManual = $this->createInvoice($stuManual, $this->monthlySetup2027, 'INV-202701-0030', '2027-01-10', 900.00, '2027-01-01 00:05:00');
        $invPreWaive = $this->createInvoice($stuPreWaive, $this->monthlySetup2027, 'INV-202701-0031', '2027-01-10', 900.00, '2027-01-01 00:05:00');
        $invAutoWaive = $this->createInvoice($stuAutoWaive, $this->monthlySetup2027, 'INV-202701-0032', '2027-01-10', 900.00, '2027-01-01 00:05:00');

        $service = app(LateFineService::class);

        // Scenario A: Manual fine ৳35.00 applied on Jan 6 -> untouched on Jan 8
        Carbon::setTestNow(Carbon::parse('2027-01-06 10:00:00', 'Asia/Dhaka'));
        $service->applyManualLateFine($invManual, 35.00, 'Special manual late fee', $this->adminUser->id);

        // Scenario B: Accidental manual fine added Jan 5 and waived Jan 6 -> auto_fine_waived_at stays NULL
        Carbon::setTestNow(Carbon::parse('2027-01-05 10:00:00', 'Asia/Dhaka'));
        $service->applyManualLateFine($invPreWaive, 20.00, 'Accidental early manual fine', $this->adminUser->id);
        $service->waiveFine($invPreWaive->fresh(), 'Undo accidental fine before 8th', $this->adminUser->id, Carbon::parse('2027-01-06 11:00:00', 'Asia/Dhaka'));
        $this->assertNull($invPreWaive->fresh()->auto_fine_waived_at);

        // Run auto-fine on Jan 8:
        // - invManual stays ৳35.00 manual (skipped: already_has_fine)
        // - invPreWaive DOES receive ৳20.00 auto fine
        // - invAutoWaive DOES receive ৳20.00 auto fine
        Carbon::setTestNow(Carbon::parse('2027-01-08 00:15:00', 'Asia/Dhaka'));
        $resJan8 = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-01-08 00:15:00', 'Asia/Dhaka'));
        $this->assertSame(2, $resJan8['applied_count']);
        $this->assertSame(1, $resJan8['skipped_counts']['already_has_fine']);

        $invManual->refresh();
        $invPreWaive->refresh();
        $invAutoWaive->refresh();

        $this->assertSame('35.00', $invManual->fineAmountDecimal());
        $this->assertSame('manual', $invManual->fine_source);
        $this->assertNull($invManual->auto_fine_applied_at);
        $this->assertEquals('935.00', bcadd((string) $invManual->due_amount, '0.00', 2));

        $this->assertSame('20.00', $invPreWaive->fineAmountDecimal());
        $this->assertSame('auto', $invPreWaive->fine_source);
        $this->assertNotNull($invPreWaive->auto_fine_applied_at);
        $this->assertEquals('920.00', bcadd((string) $invPreWaive->due_amount, '0.00', 2));

        $this->assertSame('20.00', $invAutoWaive->fineAmountDecimal());
        $this->assertSame('auto', $invAutoWaive->fine_source);

        // Scenario C: Admin waives invAutoWaive on Jan 9 -> auto_fine_waived_at stamped -> NEVER resurrected on Jan 10
        $service->waiveFine($invAutoWaive, 'Principal approved hardship waiver', $this->adminUser->id, Carbon::parse('2027-01-09 10:00:00', 'Asia/Dhaka'));
        $invAutoWaive->refresh();
        $this->assertSame('0.00', $invAutoWaive->fineAmountDecimal());
        $this->assertNull($invAutoWaive->fine_source);
        $this->assertNotNull($invAutoWaive->auto_fine_waived_at);
        $this->assertEquals('900.00', bcadd((string) $invAutoWaive->due_amount, '0.00', 2));

        $resJan10 = $service->applyAutomaticMonthlyLateFines(Carbon::parse('2027-01-10 00:15:00', 'Asia/Dhaka'));
        $this->assertSame(0, $resJan10['applied_count']);

        $invAutoWaive->refresh();
        $this->assertSame('0.00', $invAutoWaive->fineAmountDecimal());
        $this->assertNull($invAutoWaive->fine_source);
        $this->assertEquals('900.00', bcadd((string) $invAutoWaive->due_amount, '0.00', 2));
    }

    /**
     * 8. Strict Exclusions: Non-monthly fees, Admission, Paid, Partial, Cancelled, 0.00 Scholarship, and Inactive students.
     */
    public function test_strict_exclusions_for_non_monthly_statuses_scholarship_and_inactive_students(): void
    {
        $activeStu = $this->createStudent('27010100040', 'Active Student', 40);
        $inactiveStu = $this->createStudent('27010100041', 'Inactive Student', 41, '2026-01-01', '2026-01-01 09:00:00', $this->session2027->id, 'Inactive');

        // 1. Term Exam Fee (Unpaid)
        $termExam = $this->createInvoice($activeStu, $this->termExamSetup2027, 'INV-202701-0041', '2027-01-10', 400.00);
        // 2. Monthly Exam Fee (Unpaid)
        $monthlyExam = $this->createInvoice($activeStu, $this->monthlyExamSetup2027, 'INV-202701-0042', '2027-01-10', 200.00);
        // 3. Admission Invoice (Unpaid)
        $admStruct = AdmissionFeeStructure::create([
            'branch_id'                => $this->branch->id,
            'class_id'                 => $this->classPlay->id,
            'session_year_id'          => $this->session2027->id,
            'structure_name'           => 'Play 2027 Admission',
            'version'                  => 1,
            'status'                   => 'Active',
            'total_amount'             => 2500.00,
            'includes_monthly_tuition' => false,
            'created_by'               => $this->adminUser->id,
        ]);
        $admissionInv = $this->createInvoice($activeStu, null, 'INV-ADM-S2-202701-0043', '2027-01-10', 2500.00, '2027-01-01 00:05:00', [
            'fee_setup_id'               => null,
            'admission_fee_structure_id' => $admStruct->id,
            'branch_name_snapshot'       => $this->branch->branch_name,
        ]);
        // 4. Paid Monthly Fee
        $paidMonthly = $this->createInvoice($activeStu, $this->monthlySetup2027, 'INV-202701-0044', '2027-01-10', 900.00, '2027-01-01 00:05:00', [
            'paid_amount' => 900.00,
            'due_amount'  => 0.00,
            'status'      => 'Paid',
        ]);
        FeePayment::create([
            'fee_invoice_id' => $paidMonthly->id,
            'student_id'     => $activeStu->id,
            'receipt_no'     => 'RCP-202701-0044',
            'paid_amount'    => 900.00,
            'fine_amount'    => 0.00,
            'payment_method' => 'Cash',
            'payment_date'   => '2027-01-05',
            'status'         => 'Active',
            'collected_by'   => $this->adminUser->id,
        ]);
        // 5. Partial Monthly Fee
        $partialMonthly = $this->createInvoice($activeStu, $this->monthlySetup2027, 'INV-202701-0045', '2027-01-10', 900.00, '2027-01-01 00:05:00', [
            'paid_amount' => 400.00,
            'due_amount'  => 500.00,
            'status'      => 'Partial',
        ]);
        FeePayment::create([
            'fee_invoice_id' => $partialMonthly->id,
            'student_id'     => $activeStu->id,
            'receipt_no'     => 'RCP-202701-0045',
            'paid_amount'    => 400.00,
            'fine_amount'    => 0.00,
            'payment_method' => 'Cash',
            'payment_date'   => '2027-01-05',
            'status'         => 'Active',
            'collected_by'   => $this->adminUser->id,
        ]);
        // 6. Cancelled Monthly Fee
        $cancelledMonthly = $this->createInvoice($activeStu, $this->monthlySetup2027, 'INV-202701-0046', '2027-01-10', 900.00, '2027-01-01 00:05:00', [
            'due_amount' => 0.00,
            'status'     => 'Cancelled',
        ]);
        // 7. 0.00-net Scholarship Monthly Fee (status = 'Unpaid')
        $scholarshipMonthly = $this->createInvoice($activeStu, $this->monthlySetup2027, 'INV-202701-0047', '2027-01-10', 900.00, '2027-01-01 00:05:00', [
            'discount'   => 900.00,
            'net_amount' => 0.00,
            'due_amount' => 0.00,
            'status'     => 'Unpaid',
        ]);
        // 8. Inactive student's Unpaid Monthly Fee
        $inactiveMonthly = $this->createInvoice($inactiveStu, $this->monthlySetup2027, 'INV-202701-0048', '2027-01-10', 900.00);
        // 9. Positive Control: Active student's eligible Unpaid Monthly Fee
        $eligibleMonthly = $this->createInvoice($activeStu, $this->monthlySetup2027, 'INV-202701-0049', '2027-01-10', 900.00);

        $result = app(LateFineService::class)->applyAutomaticMonthlyLateFines(
            Carbon::parse('2027-01-08 00:15:00', 'Asia/Dhaka')
        );

        $this->assertSame(1, $result['applied_count']);
        $this->assertSame('20.00', $result['total_fine_amount']);

        foreach ([
            $termExam,
            $monthlyExam,
            $admissionInv,
            $paidMonthly,
            $partialMonthly,
            $cancelledMonthly,
            $scholarshipMonthly,
            $inactiveMonthly,
        ] as $excluded) {
            $excluded->refresh();
            $this->assertSame('0.00', $excluded->fineAmountDecimal(), "Invoice {$excluded->invoice_no} must remain 0.00 fine");
            $this->assertNull($excluded->fine_source);
            $this->assertNull($excluded->auto_fine_applied_at);
        }

        $eligibleMonthly->refresh();
        $this->assertSame('20.00', $eligibleMonthly->fineAmountDecimal());
        $this->assertSame('auto', $eligibleMonthly->fine_source);
        $this->assertEquals('920.00', bcadd((string) $eligibleMonthly->due_amount, '0.00', 2));
    }

    /**
     * 9. `--dry-run` Safety (zero DB writes) & Production `--date=` Override Guard.
     */
    public function test_dry_run_safety_and_production_date_override_guard(): void
    {
        $student = $this->createStudent('27010100050', 'Dry Run Student', 50);
        $janInv = $this->createInvoice($student, $this->monthlySetup2027, 'INV-202701-0050', '2027-01-10', 900.00);

        // 1. --dry-run previews accurately with ZERO DB writes
        $this->artisan('fees:apply-late-fines', ['--date' => '2027-01-08', '--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN MODE ACTIVE')
            ->expectsOutputToContain('INV-202701-0050')
            ->assertExitCode(0);

        $janInv->refresh();
        $this->assertSame('0.00', $janInv->fineAmountDecimal());
        $this->assertNull($janInv->fine_source);
        $this->assertNull($janInv->auto_fine_applied_at);
        $this->assertEquals('900.00', bcadd((string) $janInv->due_amount, '0.00', 2));
        $this->assertSame(0, FeeAdjustmentLog::count());

        // 2. Malformed / impossible calendar --date values fail with exit code 1
        $this->artisan('fees:apply-late-fines', ['--date' => '2027-02-30', '--dry-run' => true])
            ->assertExitCode(1);
        $this->artisan('fees:apply-late-fines', ['--date' => 'not-a-date', '--dry-run' => true])
            ->assertExitCode(1);

        // 3. Production guard: block mutating --date= without --dry-run; permit --date= WITH --dry-run
        $originalEnv = $this->app['env'];
        try {
            $this->app['env'] = 'production';

            $this->artisan('fees:apply-late-fines', ['--date' => '2027-01-08'])
                ->expectsOutputToContain('Refusing to run fees:apply-late-fines with --date override in production without --dry-run.')
                ->assertExitCode(1);

            $this->assertSame('0.00', $janInv->fresh()->fineAmountDecimal());
            $this->assertSame(0, FeeAdjustmentLog::count());

            // Read-only --dry-run with --date= IS allowed in production
            $this->artisan('fees:apply-late-fines', ['--date' => '2027-01-08', '--dry-run' => true])
                ->expectsOutputToContain('DRY-RUN MODE ACTIVE')
                ->assertExitCode(0);

            $this->assertSame('0.00', $janInv->fresh()->fineAmountDecimal());
            $this->assertSame(0, FeeAdjustmentLog::count());
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }
}
