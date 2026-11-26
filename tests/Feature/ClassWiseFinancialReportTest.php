<?php

namespace Tests\Feature;

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
use App\Services\ClassWiseFinancialReportService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassWiseFinancialReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teacherUser;
    protected User $studentUser;
    protected Branch $branch;
    protected SessionYear $session2026;
    protected SessionYear $session2025;
    protected FeeCategory $catMonthlyFee;
    protected FeeCategory $catExamFee;
    protected FeeCategory $catAdmissionFee;
    protected FeeCategory $catFine;
    protected FeeCategory $catOthers;
    protected Classes $clsPlay;
    protected Classes $clsOne;
    protected Classes $clsTen;
    protected Section $secPlay;
    protected Section $secOne;
    protected Section $secTen;
    protected Student $stuPlay;
    protected Student $stuOne;
    protected Student $stuTen;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Roles & Users
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_fin_test@macsschool.com'],
            ['name' => 'Finance Admin', 'password' => bcrypt('password')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        $this->teacherUser = User::firstOrCreate(
            ['email' => 'teacher_test@macsschool.com'],
            ['name' => 'Teacher User', 'password' => bcrypt('password')]
        );
        $teacherRole = Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'Teacher']);
        $this->teacherUser->assignRole($teacherRole);

        $this->studentUser = User::firstOrCreate(
            ['email' => 'student_test@macsschool.com'],
            ['name' => 'Student User', 'password' => bcrypt('password')]
        );
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->studentUser->assignRole($studentRole);

        // 2. Academic Sessions & Branch
        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->session2026 = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026', 'user_id' => $this->adminUser->id]);
        $this->session2025 = SessionYear::firstOrCreate(['id' => 2], ['session_name' => '2025', 'user_id' => $this->adminUser->id]);

        // 3. Classes in Academic Order
        $this->clsPlay = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Play', 'user_id' => $this->adminUser->id]);
        $this->clsOne  = Classes::firstOrCreate(['id' => 3], ['class_name' => 'One', 'user_id' => $this->adminUser->id]);
        $this->clsTen  = Classes::firstOrCreate(['id' => 12], ['class_name' => 'Ten', 'user_id' => $this->adminUser->id]);

        // 4. Sections
        $this->secPlay = Section::firstOrCreate(['id' => 1], ['section_name' => 'A', 'class_id' => $this->clsPlay->id, 'session_year_id' => $this->session2026->id, 'user_id' => $this->adminUser->id]);
        $this->secOne  = Section::firstOrCreate(['id' => 2], ['section_name' => 'A', 'class_id' => $this->clsOne->id, 'session_year_id' => $this->session2026->id, 'user_id' => $this->adminUser->id]);
        $this->secTen  = Section::firstOrCreate(['id' => 3], ['section_name' => 'A', 'class_id' => $this->clsTen->id, 'session_year_id' => $this->session2026->id, 'user_id' => $this->adminUser->id]);

        // 5. Students
        $baseStudentData = [
            'user_id'                => $this->adminUser->id,
            'blood_group'            => 'A+',
            'religion'               => 'Islam',
            'father_name'            => 'Test Father',
            'father_mobile'          => '01711000001',
            'mother_name'            => 'Test Mother',
            'mother_mobile'          => '01711000002',
            'guardian_name'          => 'Test Guardian',
            'guardian_mobile'        => '01711000001',
            'present_village'        => 'Village',
            'present_post_office'    => 'PO',
            'present_district'       => 'Pabna',
            'present_division'       => 'Rajshahi',
            'permanent_village'      => 'Village',
            'permanent_post_office'  => 'PO',
            'permanent_district'     => 'Pabna',
            'permanent_division'     => 'Rajshahi',
            'branch_id'              => $this->branch->id,
            'session_year_id'        => $this->session2026->id,
            'status'                 => 'active',
        ];

        $this->stuPlay = Student::create(array_merge($baseStudentData, [
            'student_identity' => 'STU-PLAY-001',
            'student_name'     => 'Play Student',
            'roll_number'      => '1',
            'dob'              => '2021-01-01',
            'gender'           => 'Male',
            'class_id'         => $this->clsPlay->id,
            'section_id'       => $this->secPlay->id,
        ]));

        $this->stuOne = Student::create(array_merge($baseStudentData, [
            'student_identity' => 'STU-ONE-001',
            'student_name'     => 'One Student',
            'roll_number'      => '2',
            'dob'              => '2019-01-01',
            'gender'           => 'Female',
            'class_id'         => $this->clsOne->id,
            'section_id'       => $this->secOne->id,
        ]));

        $this->stuTen = Student::create(array_merge($baseStudentData, [
            'student_identity' => 'STU-TEN-001',
            'student_name'     => 'Ten Student',
            'roll_number'      => '3',
            'dob'              => '2010-01-01',
            'gender'           => 'Male',
            'class_id'         => $this->clsTen->id,
            'section_id'       => $this->secTen->id,
        ]));

        // 6. Fee Categories
        $this->catMonthlyFee   = FeeCategory::firstOrCreate(['name' => 'Monthly Fee'], ['user_id' => $this->adminUser->id]);
        $this->catExamFee      = FeeCategory::firstOrCreate(['name' => 'Term Exam Fee'], ['user_id' => $this->adminUser->id]);
        $this->catAdmissionFee = FeeCategory::firstOrCreate(['name' => 'Admission Fee'], ['user_id' => $this->adminUser->id]);
        $this->catFine         = FeeCategory::firstOrCreate(['name' => 'Fine'], ['user_id' => $this->adminUser->id]);
        $this->catOthers       = FeeCategory::firstOrCreate(['name' => 'Others'], ['user_id' => $this->adminUser->id]);
    }

    /**
     * Helper to create a fee invoice and its setup.
     */
    protected function createInvoice(
        Student $student,
        FeeCategory $category,
        SessionYear $session,
        float $netAmount,
        float $paidAmount,
        string $status = 'Paid',
        string $dueDate = '2026-03-15',
        string $invoiceNo = 'INV-202603-0001'
    ): FeeInvoice {
        $setup = FeeSetup::create([
            'session_year_id' => $session->id,
            'branch_id'       => $student->branch_id,
            'class_id'        => $student->class_id,
            'fee_category_id' => $category->id,
            'amount'          => $netAmount,
            'fee_month'       => date('F', strtotime($dueDate)),
            'user_id'         => $this->adminUser->id,
        ]);

        $dueAmount = max(0.0, $netAmount - $paidAmount);

        $invoice = FeeInvoice::create([
            'invoice_no'   => $invoiceNo,
            'student_id'   => $student->id,
            'fee_setup_id' => $setup->id,
            'amount'       => $netAmount,
            'discount'     => 0.0,
            'net_amount'   => $netAmount,
            'paid_amount'  => $paidAmount,
            'due_amount'   => $dueAmount,
            'status'       => $status,
            'due_date'     => $dueDate,
            'user_id'      => $this->adminUser->id,
        ]);

        if ($paidAmount > 0) {
            FeePayment::create([
                'fee_invoice_id' => $invoice->id,
                'student_id'     => $student->id,
                'paid_amount'    => $paidAmount,
                'payment_date'   => $dueDate,
                'payment_method' => 'Cash',
                'receipt_no'     => 'REC-' . uniqid(),
                'collected_by'   => $this->adminUser->id,
            ]);
        }

        return $invoice;
    }

    /**
     * Test 1: Unauthenticated guests cannot access the report.
     */
    public function test_unauthenticated_user_cannot_access_class_wise_report(): void
    {
        $response = $this->get(route('accounts.reports.class_wise'));
        $this->assertTrue(in_array($response->status(), [401, 302]));
        if ($response->status() === 302) {
            $this->assertNotEquals(route('accounts.reports.class_wise'), $response->headers->get('Location'));
        }
    }

    /**
     * Test 2: Unauthorized roles (students, teachers) receive 403 or redirect away.
     */
    public function test_unauthorized_user_without_admin_role_cannot_access_report(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('accounts.reports.class_wise'));
        $this->assertTrue(in_array($response->status(), [403, 302]));
        if ($response->status() === 302) {
            $this->assertNotEquals(route('accounts.reports.class_wise'), $response->headers->get('Location'));
        }

        $responseTeacher = $this->actingAs($this->teacherUser)->get(route('accounts.reports.class_wise'));
        $this->assertTrue(in_array($responseTeacher->status(), [403, 302]));
        if ($responseTeacher->status() === 302) {
            $this->assertNotEquals(route('accounts.reports.class_wise'), $responseTeacher->headers->get('Location'));
        }
    }

    /**
     * Test 3: Admin can access dashboard and view the report layout.
     */
    public function test_admin_can_access_class_wise_report(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.class_wise'));
        $response->assertStatus(200);
        $response->assertSee('Class-wise Financial Report');
        $response->assertSee('Due');
        $response->assertSee('Collected');
        $response->assertSee('Outstanding');
    }

    /**
     * Test 4: Category classification accurately separates Monthly Fee, Exam Fees, and Other Fees.
     */
    public function test_service_classify_category_correctly_categorizes_fee_types(): void
    {
        $service = new ClassWiseFinancialReportService();

        // 1. Monthly Fee -> monthly_fee
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_MONTHLY_FEE,
            $service->classifyCategory('Monthly Fee')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_MONTHLY_FEE,
            $service->classifyCategory('monthly fee')
        );

        // 2. Exam Fees -> exam_fees
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_EXAM_FEES,
            $service->classifyCategory('Monthly Exam Fee')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_EXAM_FEES,
            $service->classifyCategory('Term Exam Fee')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_EXAM_FEES,
            $service->classifyCategory('Final Exam Fee')
        );

        // 3. All other student fees & miscellaneous charges -> other_fees
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_OTHER_FEES,
            $service->classifyCategory('Admission Fee')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_OTHER_FEES,
            $service->classifyCategory('Session Fee')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_OTHER_FEES,
            $service->classifyCategory('Registration Fee')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_OTHER_FEES,
            $service->classifyCategory('Fine')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_OTHER_FEES,
            $service->classifyCategory('Others')
        );
        $this->assertEquals(
            ClassWiseFinancialReportService::BUCKET_OTHER_FEES,
            $service->classifyCategory('Miscellaneous')
        );
    }

    /**
     * Test 5: Cancelled invoices are strictly excluded from dues, collections, and outstandings.
     */
    public function test_cancelled_invoices_are_strictly_excluded(): void
    {
        // Active invoice: 1000 due, 600 collected, 400 outstanding
        $this->createInvoice(
            $this->stuPlay,
            $this->catMonthlyFee,
            $this->session2026,
            1000.0,
            600.0,
            'Partial',
            '2026-03-10',
            'INV-202603-0001'
        );

        // Cancelled invoice: 5000 due (MUST BE COMPLETELY IGNORED)
        $this->createInvoice(
            $this->stuPlay,
            $this->catMonthlyFee,
            $this->session2026,
            5000.0,
            0.0,
            'Cancelled',
            '2026-03-10',
            'INV-202603-0099'
        );

        $service = new ClassWiseFinancialReportService();
        $report = $service->generateReport(['session_year_id' => $this->session2026->id]);

        $playRow = collect($report['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertNotNull($playRow);

        // Check that cancelled invoice is NOT in the total due
        $this->assertEquals(1000.0, $playRow['due']['monthly_fee']);
        $this->assertEquals(600.0, $playRow['collected']['monthly_fee']);
        $this->assertEquals(400.0, $playRow['outstanding']['monthly_fee']);
        $this->assertEquals(1000.0, $report['total_due']);
        $this->assertEquals(600.0, $report['total_collected']);
        $this->assertEquals(400.0, $report['total_outstanding']);
    }

    /**
     * Test 6: Due, Collected, and Outstanding calculations balance perfectly.
     */
    public function test_due_collected_and_outstanding_calculations_balance(): void
    {
        // Monthly Fee: Due 1200, Paid 1000, Outstanding 200
        $this->createInvoice($this->stuOne, $this->catMonthlyFee, $this->session2026, 1200.0, 1000.0, 'Partial', '2026-04-10', 'INV-202604-0001');

        // Exam Fees: Due 500, Paid 300, Outstanding 200
        $this->createInvoice($this->stuOne, $this->catExamFee, $this->session2026, 500.0, 300.0, 'Partial', '2026-04-15', 'INV-202604-0002');

        // Other Fees (Fine + Admission): Due 100, Paid 100, Outstanding 0
        $this->createInvoice($this->stuOne, $this->catFine, $this->session2026, 100.0, 100.0, 'Paid', '2026-04-20', 'INV-202604-0003');

        $service = new ClassWiseFinancialReportService();
        $report = $service->generateReport(['session_year_id' => $this->session2026->id]);

        $oneRow = collect($report['rows'])->firstWhere('class_id', $this->clsOne->id);
        $this->assertNotNull($oneRow);

        // Due checks
        $this->assertEquals(1200.0, $oneRow['due']['monthly_fee']);
        $this->assertEquals(500.0, $oneRow['due']['exam_fees']);
        $this->assertEquals(100.0, $oneRow['due']['other_fees']);
        $this->assertEquals(1800.0, $oneRow['due']['total']);

        // Collected checks
        $this->assertEquals(1000.0, $oneRow['collected']['monthly_fee']);
        $this->assertEquals(300.0, $oneRow['collected']['exam_fees']);
        $this->assertEquals(100.0, $oneRow['collected']['other_fees']);
        $this->assertEquals(1400.0, $oneRow['collected']['total']);

        // Outstanding checks (Due - Collected = Outstanding)
        $this->assertEquals(200.0, $oneRow['outstanding']['monthly_fee']);
        $this->assertEquals(200.0, $oneRow['outstanding']['exam_fees']);
        $this->assertEquals(0.0, $oneRow['outstanding']['other_fees']);
        $this->assertEquals(400.0, $oneRow['outstanding']['total']);

        $this->assertEquals(
            $oneRow['due']['total'] - $oneRow['collected']['total'],
            $oneRow['outstanding']['total']
        );
    }

    /**
     * Test 7: Academic Session filtering filters out invoices from other sessions.
     */
    public function test_academic_session_filtering_works(): void
    {
        // 2026 Invoice
        $this->createInvoice($this->stuTen, $this->catMonthlyFee, $this->session2026, 2500.0, 2500.0, 'Paid', '2026-01-15', 'INV-202601-0001');

        // 2025 Invoice
        $this->createInvoice($this->stuTen, $this->catMonthlyFee, $this->session2025, 2000.0, 2000.0, 'Paid', '2025-01-15', 'INV-202501-0001');

        $service = new ClassWiseFinancialReportService();

        // Query session 2026
        $report2026 = $service->generateReport(['session_year_id' => $this->session2026->id]);
        $tenRow2026 = collect($report2026['rows'])->firstWhere('class_id', $this->clsTen->id);
        $this->assertEquals(2500.0, $tenRow2026['due']['monthly_fee']);

        // Query session 2025
        $report2025 = $service->generateReport(['session_year_id' => $this->session2025->id]);
        $tenRow2025 = collect($report2025['rows'])->firstWhere('class_id', $this->clsTen->id);
        $this->assertEquals(2000.0, $tenRow2025['due']['monthly_fee']);
    }

    /**
     * Test 8: Monthly period filtering filters by month correctly.
     */
    public function test_monthly_period_filtering(): void
    {
        // March 2026
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 1500.0, 1500.0, 'Paid', '2026-03-10', 'INV-202603-0005');

        // April 2026
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 1500.0, 1000.0, 'Partial', '2026-04-10', 'INV-202604-0005');

        $service = new ClassWiseFinancialReportService();

        // Filter March
        $marchReport = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'March',
            'year'            => 2026,
        ]);
        $playMarch = collect($marchReport['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertEquals(1500.0, $playMarch['due']['monthly_fee']);
        $this->assertEquals(1500.0, $playMarch['collected']['monthly_fee']);

        // Filter April
        $aprilReport = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'April',
            'year'            => 2026,
        ]);
        $playApril = collect($aprilReport['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertEquals(1500.0, $playApril['due']['monthly_fee']);
        $this->assertEquals(1000.0, $playApril['collected']['monthly_fee']);
        $this->assertEquals(500.0, $playApril['outstanding']['monthly_fee']);
    }

    /**
     * Test 9: Custom date range filtering.
     */
    public function test_custom_date_range_filtering(): void
    {
        // 2026-02-15
        $this->createInvoice($this->stuOne, $this->catMonthlyFee, $this->session2026, 800.0, 800.0, 'Paid', '2026-02-15', 'INV-202602-0008');

        // 2026-05-15
        $this->createInvoice($this->stuOne, $this->catMonthlyFee, $this->session2026, 900.0, 900.0, 'Paid', '2026-05-15', 'INV-202605-0008');

        $service = new ClassWiseFinancialReportService();

        $customReport = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-02-01',
            'end_date'        => '2026-02-28',
        ]);

        $oneRow = collect($customReport['rows'])->firstWhere('class_id', $this->clsOne->id);
        $this->assertEquals(800.0, $oneRow['due']['monthly_fee']);
        $this->assertEquals(800.0, $oneRow['collected']['monthly_fee']);
        $this->assertEquals(800.0, $customReport['total_due']);
        $this->assertEquals(800.0, $customReport['total_collected']);
    }

    /**
     * Test 9b: Model 1 Custom Date Range independently aggregates Active payment_date cash collections
     * and period-billed invoices (due_date OR created_at in range), including Admission head splits.
     */
    public function test_custom_date_range_model_1_independent_payment_date_and_billed_period(): void
    {
        // 1. Legacy Monthly Fee invoice due on 2026-11-10 (created 2026-11-01, outside 14..19 Nov),
        //    but paid on 2026-11-18 (INSIDE 14..19 Nov).
        $invPaidInRange = $this->createInvoice(
            $this->stuPlay,
            $this->catMonthlyFee,
            $this->session2026,
            700.0,
            0.0,
            'Paid',
            '2026-11-10',
            'INV-202611-7001'
        );
        \Illuminate\Support\Facades\DB::table('fee_invoices')
            ->where('id', $invPaidInRange->id)
            ->update([
                'paid_amount' => '700.00',
                'due_amount'  => '0.00',
                'created_at'  => '2026-11-01 09:00:00',
            ]);
        FeePayment::create([
            'fee_invoice_id' => $invPaidInRange->id,
            'student_id'     => $this->stuPlay->id,
            'branch_id'      => $this->branch->id,
            'paid_amount'    => '700.00',
            'payment_date'   => '2026-11-18',
            'payment_method' => 'Cash',
            'status'         => 'Active',
            'receipt_no'     => 'REC-M1-ACT-1',
            'collected_by'   => $this->adminUser->id,
        ]);

        // Reversed payment on 2026-11-16 MUST be excluded from Collected (reversal columns are guarded from mass-assignment)
        $revPayment = FeePayment::create([
            'fee_invoice_id' => $invPaidInRange->id,
            'student_id'     => $this->stuPlay->id,
            'branch_id'      => $this->branch->id,
            'paid_amount'    => '300.00',
            'payment_date'   => '2026-11-16',
            'payment_method' => 'Cash',
            'receipt_no'     => 'REC-M1-REV-1',
            'collected_by'   => $this->adminUser->id,
        ]);
        $revPayment->status = 'Reversed';
        $revPayment->reversed_amount = '300.00';
        $revPayment->reversed_at = '2026-11-16 12:00:00';
        $revPayment->reversed_by = $this->adminUser->id;
        $revPayment->reversal_reason = 'Test Reversal';
        $revPayment->reversal_reference = 'REV-M1-001';
        $revPayment->save();

        // 2. Legacy Exam Fee invoice due on 2026-11-10, created on 2026-11-17 (INSIDE 14..19 Nov), unpaid.
        $invCreatedInRange = $this->createInvoice(
            $this->stuOne,
            $this->catExamFee,
            $this->session2026,
            400.0,
            0.0,
            'Unpaid',
            '2026-11-10',
            'INV-202611-7002'
        );
        \Illuminate\Support\Facades\DB::table('fee_invoices')
            ->where('id', $invCreatedInRange->id)
            ->update(['created_at' => '2026-11-17 11:00:00']);

        // 3. Payment made OUTSIDE range (2026-11-05) on a 2026-11-10 invoice must NOT appear in 14..19 Nov
        $invOutside = $this->createInvoice(
            $this->stuTen,
            $this->catMonthlyFee,
            $this->session2026,
            900.0,
            900.0,
            'Paid',
            '2026-11-10',
            'INV-202611-7003'
        );
        \Illuminate\Support\Facades\DB::table('fee_invoices')
            ->where('id', $invOutside->id)
            ->update(['created_at' => '2026-11-01 09:00:00']);
        FeePayment::where('fee_invoice_id', $invOutside->id)->update(['payment_date' => '2026-11-05']);

        // 4. Admission Invoice (Play, due_date 2026-11-10, created 2026-11-01) with Active payment on 2026-11-15
        $structureService = app(\App\Services\AdmissionFeeStructureService::class);
        $invoiceService = app(\App\Services\AdmissionInvoiceService::class);
        $collectionService = app(\App\Services\AdmissionCollectionService::class);

        $structure = $structureService->createStructure([
            'branch_id'                => $this->branch->id,
            'class_id'                 => $this->clsPlay->id,
            'session_year_id'          => $this->session2026->id,
            'structure_name'           => 'Play Admission Fee',
            'status'                   => 'Active',
            'includes_monthly_tuition' => true,
            'tuition_month_number'     => 1,
            'items'                    => [
                ['fee_category_id' => $this->catMonthlyFee->id, 'head_name' => 'January Tuition', 'amount' => '500.00', 'is_discountable' => false, 'is_tuition_head' => true, 'sort_order' => 10],
                ['fee_category_id' => $this->catAdmissionFee->id, 'head_name' => 'Admission Fee', 'amount' => '1000.00', 'is_discountable' => true, 'is_tuition_head' => false, 'sort_order' => 20],
            ],
        ], $this->adminUser->id);

        $admInv = $invoiceService->generateForStudent($this->stuPlay, ['due_date' => '2026-11-10'], $this->adminUser->id)['invoice'];
        \Illuminate\Support\Facades\DB::table('fee_invoices')
            ->where('id', $admInv->id)
            ->update(['created_at' => '2026-11-01 10:00:00']);

        $admHeads = $admInv->admissionHeadAllocations()->orderBy('sort_order')->get();
        $collectionService->collectSinglePayment($admInv->id, [
            'pay_amount'       => '900.00',
            'payment_method'   => 'Cash',
            'payment_date'     => '2026-11-15',
            'head_allocations' => [
                $admHeads[0]->id => '300.00', // Monthly Fee (tuition head)
                $admHeads[1]->id => '600.00', // Other Fees (admission head)
            ],
        ], $this->adminUser->id);

        $service = new ClassWiseFinancialReportService();
        $report = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-11-14',
            'end_date'        => '2026-11-19',
        ]);

        $byClass = collect($report['rows'])->keyBy('class_id');

        // Play: 0 billed in 14..19 Nov, Collected = 700 (legacy monthly) + 300 (adm tuition) = 1000 monthly, 600 other_fees
        $playRow = $byClass[$this->clsPlay->id];
        $this->assertEquals('0.00', $playRow['due']['total']);
        $this->assertEquals('0.00', $playRow['outstanding']['total']);
        $this->assertEquals('1000.00', $playRow['collected']['monthly_fee']);
        $this->assertEquals('0.00', $playRow['collected']['exam_fees']);
        $this->assertEquals('600.00', $playRow['collected']['other_fees']);
        $this->assertEquals('1600.00', $playRow['collected']['total']);

        // One: 400 exam fee created on 2026-11-17 (Due = 400, Outstanding = 400, Collected = 0)
        $oneRow = $byClass[$this->clsOne->id];
        $this->assertEquals('400.00', $oneRow['due']['exam_fees']);
        $this->assertEquals('400.00', $oneRow['outstanding']['exam_fees']);
        $this->assertEquals('0.00', $oneRow['collected']['total']);

        // Ten: completely outside 14..19 Nov
        $tenRow = $byClass[$this->clsTen->id];
        $this->assertEquals('0.00', $tenRow['due']['total']);
        $this->assertEquals('0.00', $tenRow['collected']['total']);
        $this->assertEquals('0.00', $tenRow['outstanding']['total']);

        // Grand Totals
        $this->assertEquals('400.00', $report['total_due']);
        $this->assertEquals('1600.00', $report['total_collected']);
        $this->assertEquals('400.00', $report['total_outstanding']);

        // Inverted custom dates normalize cleanly to identical totals
        $inverted = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'custom',
            'start_date'      => '2026-11-19',
            'end_date'        => '2026-11-14',
        ]);
        $this->assertEquals('1600.00', $inverted['total_collected']);
        $this->assertEquals('400.00', $inverted['total_due']);
    }

    /**
     * Test 10: Grand totals accurately sum all classes.
     */
    public function test_grand_total_accurately_aggregates_all_classes(): void
    {
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 1000.0, 800.0, 'Partial', '2026-03-10', 'INV-202603-0101');
        $this->createInvoice($this->stuOne, $this->catExamFee, $this->session2026, 600.0, 500.0, 'Partial', '2026-03-10', 'INV-202603-0102');
        $this->createInvoice($this->stuTen, $this->catOthers, $this->session2026, 300.0, 300.0, 'Paid', '2026-03-10', 'INV-202603-0103');

        $service = new ClassWiseFinancialReportService();
        $report = $service->generateReport(['session_year_id' => $this->session2026->id]);

        $gt = $report['grand_total'];

        $this->assertEquals(1000.0, $gt['due']['monthly_fee']);
        $this->assertEquals(600.0, $gt['due']['exam_fees']);
        $this->assertEquals(300.0, $gt['due']['other_fees']);
        $this->assertEquals(1900.0, $gt['due']['total']);

        $this->assertEquals(800.0, $gt['collected']['monthly_fee']);
        $this->assertEquals(500.0, $gt['collected']['exam_fees']);
        $this->assertEquals(300.0, $gt['collected']['other_fees']);
        $this->assertEquals(1600.0, $gt['collected']['total']);

        $this->assertEquals(200.0, $gt['outstanding']['monthly_fee']);
        $this->assertEquals(100.0, $gt['outstanding']['exam_fees']);
        $this->assertEquals(0.0, $gt['outstanding']['other_fees']);
        $this->assertEquals(300.0, $gt['outstanding']['total']);

        $this->assertEquals(1900.0, $report['total_due']);
        $this->assertEquals(1600.0, $report['total_collected']);
        $this->assertEquals(300.0, $report['total_outstanding']);
    }

    /**
     * Test 11: Printable A4 Landscape view renders successfully.
     */
    public function test_print_view_renders_successfully(): void
    {
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 1000.0, 800.0, 'Partial', '2026-03-10', 'INV-202603-0201');

        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.class_wise_print', [
            'session_year_id' => $this->session2026->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Class-wise Financial Report');
        $response->assertSee('MACS School & College', false);
        $response->assertSee('Monthly Fee');
        $response->assertSee('Exam Fees');
        $response->assertSee('Other Fees');
        $response->assertSee('Outstanding');
    }

    /**
     * Test 12: Excel export returns a valid .xlsx spreadsheet download.
     */
    public function test_excel_export_returns_valid_spreadsheet(): void
    {
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 1000.0, 800.0, 'Partial', '2026-03-10', 'INV-202603-0301');

        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.class_wise_excel', [
            'session_year_id' => $this->session2026->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('class_wise_financial_report_', $response->headers->get('Content-Disposition'));
        // Check for genuine ZIP/XLSX magic bytes (PK..)
        $this->assertStringStartsWith("PK\x03\x04", $response->getContent());
    }

    /**
     * Test 13: PDF export returns a valid A4 Landscape PDF download.
     */
    public function test_pdf_export_returns_valid_download(): void
    {
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 1000.0, 800.0, 'Partial', '2026-03-10', 'INV-202603-0401');

        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.class_wise_pdf', [
            'session_year_id' => $this->session2026->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('class_wise_financial_report_', $response->headers->get('Content-Disposition'));
        // Check for genuine PDF magic bytes (%PDF)
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Test 14 (Bug #1 Regression): Legacy INV-YYYYMM- invoices with drifted due_date in another month
     * appear ONLY in their invoice_no month and are NEVER double-counted in the due_date month.
     */
    public function test_monthly_report_prevents_cross_month_double_counting_when_due_date_drifts(): void
    {
        // March 2026 invoices generated late with August 2026 due_dates (Partial + Paid)
        $this->createInvoice(
            $this->stuPlay,
            $this->catMonthlyFee,
            $this->session2026,
            1000.0,
            700.0,
            'Partial',
            '2026-08-02',
            'INV-202603-0001'
        );
        $this->createInvoice(
            $this->stuOne,
            $this->catMonthlyFee,
            $this->session2026,
            650.0,
            650.0,
            'Paid',
            '2026-08-03',
            'INV-202603-0002'
        );

        // Genuine August 2026 invoice
        $this->createInvoice(
            $this->stuPlay,
            $this->catMonthlyFee,
            $this->session2026,
            1200.0,
            1200.0,
            'Paid',
            '2026-08-10',
            'INV-202608-0001'
        );

        $service = new ClassWiseFinancialReportService();

        $march = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'March',
            'year'            => 2026,
        ]);
        $august = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'August',
            'year'            => 2026,
        ]);
        $fullSession = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'all',
        ]);

        // March contains both INV-202603-* invoices
        $this->assertEquals('1650.00', $march['total_due']);
        $this->assertEquals('1350.00', $march['total_collected']);
        $this->assertEquals('300.00', $march['total_outstanding']);

        // August contains ONLY INV-202608-0001 (ZERO double-counting from March due_date drift)
        $this->assertEquals('1200.00', $august['total_due']);
        $this->assertEquals('1200.00', $august['total_collected']);
        $this->assertEquals('0.00', $august['total_outstanding']);

        $oneAugust = collect($august['rows'])->firstWhere('class_id', $this->clsOne->id);
        $this->assertEquals('0.00', $oneAugust['due']['monthly_fee']);
        $this->assertEquals('0.00', $oneAugust['collected']['monthly_fee']);
        $this->assertEquals('0.00', $oneAugust['outstanding']['monthly_fee']);

        // Sum of March + August equals Full Session with 0.00 variance across Due, Collected, Outstanding
        $this->assertEquals($fullSession['total_due'], bcadd($march['total_due'], $august['total_due'], 2));
        $this->assertEquals($fullSession['total_collected'], bcadd($march['total_collected'], $august['total_collected'], 2));
        $this->assertEquals($fullSession['total_outstanding'], bcadd($march['total_outstanding'], $august['total_outstanding'], 2));
    }

    /**
     * Test 15 (Bug #1 Regression): Non-dated or malformed invoice numbers safely fall back to due_date
     * in Monthly mode without being misclassified as canonical INV-YYYYMM- invoices.
     */
    public function test_monthly_report_falls_back_to_due_date_for_non_dated_invoice_numbers(): void
    {
        // All four non-dated formats due in August 2026 MUST appear in August via due_date fallback
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 300.0, 300.0, 'Paid', '2026-08-10', 'INV-CUSTOM-0001');
        $this->createInvoice($this->stuPlay, $this->catMonthlyFee, $this->session2026, 400.0, 400.0, 'Paid', '2026-08-10', 'INV-202A08-0001');
        $this->createInvoice($this->stuOne, $this->catExamFee, $this->session2026, 500.0, 0.0, 'Unpaid', '2026-08-10', 'INV-202613-0001');
        $this->createInvoice($this->stuTen, $this->catOthers, $this->session2026, 600.0, 600.0, 'Paid', '2026-08-10', 'LEGACY-001');

        // Non-dated invoice due in July 2026 MUST NOT leak into August
        $this->createInvoice($this->stuTen, $this->catMonthlyFee, $this->session2026, 999.0, 999.0, 'Paid', '2026-07-10', 'LEGACY-JUL-001');

        $service = new ClassWiseFinancialReportService();

        $august = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'August',
            'year'            => 2026,
        ]);
        $july = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'July',
            'year'            => 2026,
        ]);

        $this->assertEquals('1800.00', $august['total_due']);
        $this->assertEquals('1300.00', $august['total_collected']);
        $this->assertEquals('500.00', $august['total_outstanding']);
        $this->assertEquals('700.00', $august['grand_total']['due']['monthly_fee']);
        $this->assertEquals('500.00', $august['grand_total']['due']['exam_fees']);
        $this->assertEquals('600.00', $august['grand_total']['due']['other_fees']);

        $this->assertEquals('999.00', $july['total_due']);
        $this->assertEquals('999.00', $july['total_collected']);
        $this->assertEquals('0.00', $july['total_outstanding']);
    }

    /**
     * Test 16 (Bug #1 Regression): Canonical Admission invoices (INV-ADM-S{S}-YYYYMM-*) with a drifted
     * due_date in another month appear ONLY in their invoice_no month and are NOT double-counted.
     */
    public function test_monthly_report_prevents_cross_month_double_counting_for_admission_invoices(): void
    {
        $structureService = app(\App\Services\AdmissionFeeStructureService::class);
        $invoiceService = app(\App\Services\AdmissionInvoiceService::class);
        $collectionService = app(\App\Services\AdmissionCollectionService::class);

        $structureService->createStructure([
            'branch_id'                => $this->branch->id,
            'class_id'                 => $this->clsPlay->id,
            'session_year_id'          => $this->session2026->id,
            'structure_name'           => 'Play Admission Pack',
            'status'                   => 'Active',
            'includes_monthly_tuition' => true,
            'tuition_month_number'     => 3,
            'items'                    => [
                ['fee_category_id' => $this->catMonthlyFee->id, 'head_name' => 'March Tuition', 'amount' => '500.00', 'is_discountable' => false, 'is_tuition_head' => true, 'sort_order' => 10],
                ['fee_category_id' => $this->catAdmissionFee->id, 'head_name' => 'Admission Fee', 'amount' => '1000.00', 'is_discountable' => true, 'is_tuition_head' => false, 'sort_order' => 20],
            ],
        ], $this->adminUser->id);

        $admInv = $invoiceService->generateForStudent($this->stuPlay, ['due_date' => '2026-08-10'], $this->adminUser->id)['invoice'];

        // Pin invoice_no to canonical March 2026 format while keeping due_date & created_at in August 2026
        \Illuminate\Support\Facades\DB::table('fee_invoices')
            ->where('id', $admInv->id)
            ->update([
                'invoice_no' => sprintf('INV-ADM-S%d-202603-%d', $this->session2026->id, $this->stuPlay->id),
                'due_date'   => '2026-08-10',
                'created_at' => '2026-08-10 10:00:00',
            ]);

        $admHeads = $admInv->admissionHeadAllocations()->orderBy('sort_order')->get();
        $collectionService->collectSinglePayment($admInv->id, [
            'pay_amount'       => '900.00',
            'payment_method'   => 'Cash',
            'payment_date'     => '2026-08-10',
            'head_allocations' => [
                $admHeads[0]->id => '300.00',
                $admHeads[1]->id => '600.00',
            ],
        ], $this->adminUser->id);

        $service = new ClassWiseFinancialReportService();

        $march = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'March',
            'year'            => 2026,
        ]);
        $august = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'monthly',
            'month'           => 'August',
            'year'            => 2026,
        ]);
        $fullSession = $service->generateReport([
            'session_year_id' => $this->session2026->id,
            'period_type'     => 'all',
        ]);

        // March captures the Admission invoice once
        $playMarch = collect($march['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertEquals('500.00', $playMarch['due']['monthly_fee']);
        $this->assertEquals('1000.00', $playMarch['due']['other_fees']);
        $this->assertEquals('1500.00', $march['total_due']);
        $this->assertEquals('900.00', $march['total_collected']);
        $this->assertEquals('600.00', $march['total_outstanding']);

        // August must NOT double-count it even though due_date is 2026-08-10
        $playAugust = collect($august['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertEquals('0.00', $playAugust['due']['total']);
        $this->assertEquals('0.00', $playAugust['collected']['total']);
        $this->assertEquals('0.00', $playAugust['outstanding']['total']);

        // March + August equals Full Session
        $this->assertEquals($fullSession['total_due'], bcadd($march['total_due'], $august['total_due'], 2));
        $this->assertEquals($fullSession['total_collected'], bcadd($march['total_collected'], $august['total_collected'], 2));
        $this->assertEquals($fullSession['total_outstanding'], bcadd($march['total_outstanding'], $august['total_outstanding'], 2));
    }

    /**
     * Test 17 (Bug #2 Regression): Legacy invoices remain attributed to their historical FeeSetup class
     * after student promotion across Full Session, Monthly, and Custom Date Range (Model 1) modes.
     */
    public function test_legacy_report_preserves_historical_invoice_class_after_student_promotion_across_all_modes(): void
    {
        // Create a March 2026 Play invoice (Due 1000, Paid 600 on 2026-03-15, Outstanding 400)
        $invoice = $this->createInvoice(
            $this->stuPlay,
            $this->catMonthlyFee,
            $this->session2026,
            1000.0,
            600.0,
            'Partial',
            '2026-03-10',
            'INV-202603-0888'
        );
        FeePayment::where('fee_invoice_id', $invoice->id)->update([
            'payment_date' => '2026-03-15',
            'status'       => 'Active',
        ]);

        // Promote student from Play -> Class One
        $this->stuPlay->update([
            'class_id'   => $this->clsOne->id,
            'section_id' => $this->secOne->id,
        ]);

        $service = new ClassWiseFinancialReportService();

        $modes = [
            'full_session' => [
                'session_year_id' => $this->session2026->id,
                'period_type'     => 'all',
            ],
            'monthly' => [
                'session_year_id' => $this->session2026->id,
                'period_type'     => 'monthly',
                'month'           => 'March',
                'year'            => 2026,
            ],
            'custom_model_1' => [
                'session_year_id' => $this->session2026->id,
                'period_type'     => 'custom',
                'start_date'      => '2026-03-01',
                'end_date'        => '2026-03-31',
            ],
        ];

        foreach ($modes as $modeName => $filters) {
            $report = $service->generateReport($filters);
            $byClass = collect($report['rows'])->keyBy('class_id');

            $playRow = $byClass[$this->clsPlay->id];
            $oneRow  = $byClass[$this->clsOne->id];

            // Historical Play invoice MUST stay under Play (DUE, COLLECTED, and OUTSTANDING)
            $this->assertEquals('1000.00', $playRow['due']['monthly_fee'], "Play DUE mismatch in {$modeName}");
            $this->assertEquals('600.00', $playRow['collected']['monthly_fee'], "Play COLLECTED mismatch in {$modeName}");
            $this->assertEquals('400.00', $playRow['outstanding']['monthly_fee'], "Play OUTSTANDING mismatch in {$modeName}");

            // Promoted Class One MUST NOT absorb Play's historical billing or collection
            $this->assertEquals('0.00', $oneRow['due']['total'], "One DUE leaked in {$modeName}");
            $this->assertEquals('0.00', $oneRow['collected']['total'], "One COLLECTED leaked in {$modeName}");
            $this->assertEquals('0.00', $oneRow['outstanding']['total'], "One OUTSTANDING leaked in {$modeName}");
        }
    }
}
