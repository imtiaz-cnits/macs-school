<?php

namespace Tests\Feature;

use App\Models\AdmissionFeeStructure;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeSetup;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionFeeReportService;
use App\Services\AdmissionFeeStructureService;
use App\Services\AdmissionInvoiceService;
use App\Services\AdmissionPaymentReversalService;
use App\Services\ClassWiseFinancialReportService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdmissionFeeReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminUser;
    protected User $editorUser;
    protected User $teacherUser;
    protected User $studentUser;

    protected Branch $branchMain;
    protected Branch $branchCity;
    protected SessionYear $session2026;

    protected Classes $clsTen;
    protected Classes $clsOne;
    protected Classes $clsPlay;
    protected Section $secPlay;
    protected Section $secOne;
    protected Section $secTen;

    protected FeeCategory $admissionCat;
    protected FeeCategory $sessionCat;
    protected FeeCategory $tuitionCat;
    protected FeeCategory $examCat;

    protected AdmissionFeeStructure $playStructure;
    protected AdmissionFeeStructure $oneStructure;

    protected AdmissionFeeStructureService $structureService;
    protected AdmissionInvoiceService $invoiceService;
    protected AdmissionCollectionService $collectionService;
    protected AdmissionPaymentReversalService $reversalService;
    protected AdmissionFeeReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->structureService = app(AdmissionFeeStructureService::class);
        $this->invoiceService = app(AdmissionInvoiceService::class);
        $this->collectionService = app(AdmissionCollectionService::class);
        $this->reversalService = app(AdmissionPaymentReversalService::class);
        $this->reportService = app(AdmissionFeeReportService::class);

        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $teacherRole = Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'Teacher']);
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $this->superAdmin = User::create(['name' => 'Report Super', 'email' => 'rep_super@macs.test', 'password' => bcrypt('secret')]);
        $this->superAdmin->assignRole($superRole);

        $this->adminUser = User::create(['name' => 'Report Admin', 'email' => 'rep_admin@macs.test', 'password' => bcrypt('secret')]);
        $this->adminUser->assignRole($adminRole);

        $this->editorUser = User::create(['name' => 'Report Editor', 'email' => 'rep_editor@macs.test', 'password' => bcrypt('secret')]);
        $this->editorUser->assignRole($editorRole);

        $this->teacherUser = User::create(['name' => 'Report Teacher', 'email' => 'rep_teacher@macs.test', 'password' => bcrypt('secret')]);
        $this->teacherUser->assignRole($teacherRole);

        $this->studentUser = User::create(['name' => 'Report Student', 'email' => 'rep_student@macs.test', 'password' => bcrypt('secret')]);
        $this->studentUser->assignRole($studentRole);

        $this->branchMain = Branch::create(['branch_name' => 'Jalalpur Branch', 'user_id' => $this->superAdmin->id]);
        $this->branchCity = Branch::create(['branch_name' => 'City Campus', 'user_id' => $this->superAdmin->id]);
        $this->session2026 = SessionYear::create(['session_name' => '2026', 'user_id' => $this->superAdmin->id]);

        // Create classes intentionally out of ID order (Ten first, One second, Play last) to test `Play..Ten` sorting
        $this->clsTen = Classes::create(['class_name' => 'Ten', 'user_id' => $this->superAdmin->id]);
        $this->clsOne = Classes::create(['class_name' => 'One', 'user_id' => $this->superAdmin->id]);
        $this->clsPlay = Classes::create(['class_name' => 'Play', 'user_id' => $this->superAdmin->id]);

        $this->secPlay = Section::create(['section_name' => 'A', 'class_id' => $this->clsPlay->id, 'session_year_id' => $this->session2026->id, 'user_id' => $this->superAdmin->id]);
        $this->secOne = Section::create(['section_name' => 'A', 'class_id' => $this->clsOne->id, 'session_year_id' => $this->session2026->id, 'user_id' => $this->superAdmin->id]);
        $this->secTen = Section::create(['section_name' => 'A', 'class_id' => $this->clsTen->id, 'session_year_id' => $this->session2026->id, 'user_id' => $this->superAdmin->id]);

        $this->admissionCat = FeeCategory::create(['name' => 'Admission Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $this->sessionCat = FeeCategory::create(['name' => 'Session Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $this->tuitionCat = FeeCategory::create(['name' => 'Monthly Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $this->examCat = FeeCategory::create(['name' => 'Term Exam Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);

        $this->playStructure = $this->createAdmissionStructure($this->branchMain, $this->clsPlay, 'Play 2026 Main Pack');
        $this->oneStructure = $this->createAdmissionStructure($this->branchCity, $this->clsOne, 'One 2026 City Pack');
    }

    protected function createAdmissionStructure(Branch $branch, Classes $class, string $name): AdmissionFeeStructure
    {
        return $this->structureService->createStructure([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => $name,
            'status' => 'Active',
            'total_amount' => '3500.00',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 1,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Registration',
                    'amount' => '1500.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->sessionCat->id,
                    'head_name' => 'Session Development',
                    'amount' => '1000.00',
                    'is_discountable' => false,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
                [
                    'fee_category_id' => $this->tuitionCat->id,
                    'head_name' => 'January Tuition Fee',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => true,
                    'sort_order' => 30,
                ],
            ],
        ], $this->superAdmin->id);
    }

    protected function createStudent(Branch $branch, Classes $class, Section $section, string $identity): Student
    {
        return Student::create([
            'user_id' => $this->superAdmin->id,
            'student_identity' => $identity,
            'student_name' => 'Student ' . $identity,
            'roll_number' => substr($identity, -2),
            'dob' => '2018-01-01',
            'gender' => 'Male',
            'father_name' => 'Father ' . $identity,
            'father_mobile' => '01711111111',
            'mother_name' => 'Mother ' . $identity,
            'mother_mobile' => '01722222222',
            'guardian_name' => 'Guardian ' . $identity,
            'guardian_mobile' => '01733333333',
            'branch_id' => $branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
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

    public function test_all_four_admission_report_modes_and_head_vs_invoice_reconciliation(): void
    {
        // Play Student in Main Branch: 3500 gross - 300 discount = 3200 net; pays 1850.25 -> due 1349.75
        $stuPlay = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-01');
        $invPlay = $this->invoiceService->generateForStudent($stuPlay, [
            'discount_type' => 'fixed',
            'discount_value' => '300.00',
        ], $this->superAdmin->id)['invoice'];
        $playHeads = $invPlay->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->collectionService->collectSinglePayment($invPlay->id, [
            'pay_amount' => '1850.25',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $playHeads[0]->id => '1000.25',
                $playHeads[1]->id => '500.00',
                $playHeads[2]->id => '350.00',
            ],
        ], $this->adminUser->id);

        // One Student in City Branch: 3500 gross - 0 discount = 3500 net; unpaid -> due 3500.00
        $stuOne = $this->createStudent($this->branchCity, $this->clsOne, $this->secOne, 'STD-4C-02');
        $this->invoiceService->generateForStudent($stuOne, [], $this->superAdmin->id);

        // Ten Student in Main Branch (cancelled invoice): 3500 gross - 500 disc = 3000 cancelled net
        $this->createAdmissionStructure($this->branchMain, $this->clsTen, 'Ten 2026 Main Pack');
        $stuTen = $this->createStudent($this->branchMain, $this->clsTen, $this->secTen, 'STD-4C-03');
        $invTenCancelled = $this->invoiceService->generateForStudent($stuTen, [
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ], $this->superAdmin->id)['invoice'];
        $this->invoiceService->cancelUnpaidInvoice($invTenCancelled->id, 'Withdrawn admission', $this->superAdmin->id);

        $this->actingAs($this->adminUser);

        foreach (['branch', 'class', 'student', 'head'] as $mode) {
            $res = $this->getJson(route('fees.admission.reports.index', ['mode' => $mode]));
            $res->assertOk();
            $data = $res->json();

            $this->assertEquals($mode, $data['mode']);
            $this->assertEquals('7000.00', $data['summary']['gross']);
            $this->assertEquals('300.00', $data['summary']['discount']);
            $this->assertEquals('6700.00', $data['summary']['net_receivable']);
            $this->assertEquals('1850.25', $data['summary']['collected']);
            $this->assertEquals('4849.75', $data['summary']['due']);
            $this->assertEquals('1850.25', $data['summary']['period_collected']);
            $this->assertEquals(2, $data['summary']['active_invoice_count']);

            // Cancelled invoice isolated in audit summary
            $this->assertEquals(1, $data['cancelled_audit']['count']);
            $this->assertEquals('3500.00', $data['cancelled_audit']['gross_amount']);
            $this->assertEquals('500.00', $data['cancelled_audit']['discount_amount']);
            $this->assertEquals('3000.00', $data['cancelled_audit']['net_amount']);

            // Mathematical head-sum === invoice-sum === row-sum reconciliation
            $this->assertTrue($data['reconciliation']['is_reconciled']);
            $this->assertTrue($data['reconciliation']['net_minus_collected_equals_due']);
            $this->assertEquals('0.00', $data['reconciliation']['gross_diff']);
            $this->assertEquals('0.00', $data['reconciliation']['discount_diff']);
            $this->assertEquals('0.00', $data['reconciliation']['net_diff']);
            $this->assertEquals('0.00', $data['reconciliation']['collected_diff']);
            $this->assertEquals('0.00', $data['reconciliation']['due_diff']);
            $this->assertEquals('0.00', $data['reconciliation']['period_collected_diff']);
        }

        // Verify Play..Ten order in class mode (Play must precede One even though One ID < Play ID)
        $classRows = $this->getJson(route('fees.admission.reports.index', ['mode' => 'class']))->json('rows');
        $this->assertCount(2, $classRows);
        $this->assertEquals('Play', $classRows[0]['class_name']);
        $this->assertEquals('One', $classRows[1]['class_name']);

        // Verify head mode groups exact 3 fee heads
        $headRows = $this->getJson(route('fees.admission.reports.index', ['mode' => 'head']))->json('rows');
        $this->assertCount(3, $headRows);
        $this->assertEquals('Admission Registration', $headRows[0]['head_name']);
        $this->assertEquals('3000.00', $headRows[0]['gross']);
        $this->assertEquals('1000.25', $headRows[0]['collected']);
    }

    public function test_cancelled_admission_invoices_excluded_even_under_cancelled_status_filter(): void
    {
        $stuPlay = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-CANC');
        $inv = $this->invoiceService->generateForStudent($stuPlay, [], $this->superAdmin->id)['invoice'];
        $this->invoiceService->cancelUnpaidInvoice($inv->id, 'Cancelled audit check', $this->superAdmin->id);

        $this->actingAs($this->adminUser);
        $res = $this->getJson(route('fees.admission.reports.index', [
            'mode' => 'student',
            'invoice_status' => 'Cancelled',
        ]));

        $res->assertOk();
        $data = $res->json();

        // Active receivables and dues MUST stay strictly zero
        $this->assertEquals(0, $data['summary']['active_invoice_count']);
        $this->assertEquals('0.00', $data['summary']['gross']);
        $this->assertEquals('0.00', $data['summary']['net_receivable']);
        $this->assertEquals('0.00', $data['summary']['collected']);
        $this->assertEquals('0.00', $data['summary']['due']);
        $this->assertCount(0, $data['rows']);

        // Audit summary preserves full historical cancelled totals
        $this->assertEquals(1, $data['cancelled_audit']['count']);
        $this->assertEquals('3500.00', $data['cancelled_audit']['net_amount']);
        $this->assertCount(1, $data['cancelled_audit']['invoices']);
        $this->assertEquals($inv->invoice_no, $data['cancelled_audit']['invoices'][0]['invoice_no']);
        $this->assertTrue($data['reconciliation']['is_reconciled']);
    }

    public function test_reversed_payments_excluded_from_admission_and_all_existing_reports(): void
    {
        $stuPlay = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-REV');
        $inv = $this->invoiceService->generateForStudent($stuPlay, [], $this->superAdmin->id)['invoice'];
        $heads = $inv->admissionHeadAllocations()->orderBy('sort_order')->get();

        $col = $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '2100.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1500.00',
                $heads[1]->id => '600.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        $this->reversalService->reversePayment(
            $col['payment']->id,
            'Wrong amount entered on bKash admission slip',
            $this->superAdmin->id
        );

        // 1. AdmissionFeeReportService (all 4 modes)
        foreach (['branch', 'class', 'student', 'head'] as $mode) {
            $report = $this->reportService->generateReport(['mode' => $mode]);
            $this->assertEquals('3500.00', $report['summary']['net_receivable']);
            $this->assertEquals('0.00', $report['summary']['collected']);
            $this->assertEquals('3500.00', $report['summary']['due']);
            $this->assertEquals('0.00', $report['summary']['period_collected']);
            $this->assertTrue($report['reconciliation']['is_reconciled']);
        }

        $this->actingAs($this->adminUser);

        // 2. FeeReportController::index()
        $feeRepRes = $this->get(route('fees.reports.index', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $feeRepRes->assertOk();
        $this->assertEquals('0.00', $feeRepRes->viewData('totalCollected'));
        $this->assertCount(0, $feeRepRes->viewData('collections'));
        $this->assertEquals('3500.00', $feeRepRes->viewData('totalDue'));

        // 3. FeeReportController::summaryReport()
        $summaryRes = $this->get(route('fees.reports.summary', [
            'branch_id' => $this->branchMain->id,
            'session_year_id' => $this->session2026->id,
            'class_id' => $this->clsPlay->id,
        ]));
        $summaryRes->assertOk();
        $this->assertEquals('3500.00', $summaryRes->viewData('overallNet'));
        $this->assertEquals('0.00', $summaryRes->viewData('overallPaid'));
        $this->assertEquals('3500.00', $summaryRes->viewData('overallDue'));

        // 4. FinancialReportController::index()
        $finRes = $this->get(route('accounts.reports.index', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $finRes->assertOk();
        $this->assertEquals('0.00', $finRes->viewData('totalIncome'));
        $this->assertEquals('0.00', $finRes->viewData('methodSummary')['bKash']['in']);
        $this->assertEmpty($finRes->viewData('incomeByCategory'));

        // 5. ClassWiseFinancialReportService
        $cw = app(ClassWiseFinancialReportService::class)->generateReport([
            'session_year_id' => $this->session2026->id,
        ]);
        $playRow = collect($cw['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertEquals('3500.00', $playRow['due']['total']);
        $this->assertEquals('0.00', $playRow['collected']['total']);
        $this->assertEquals('3500.00', $playRow['outstanding']['total']);
    }

    public function test_null_missing_and_mixed_allocation_status_isolation_across_reports(): void
    {
        $stu = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-ISO');
        $inv = $this->invoiceService->generateForStudent($stu, [], $this->superAdmin->id)['invoice'];
        $heads = $inv->admissionHeadAllocations()->orderBy('sort_order')->get();

        $col = $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '1750.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1000.00',
                $heads[1]->id => '500.00',
                $heads[2]->id => '250.00',
            ],
        ], $this->adminUser->id);

        // A. Database-persisted status mismatch: Head 1 flipped to Reversed while payment remains Active -> strictly excluded
        DB::table('admission_payment_head_allocations')
            ->where('fee_payment_id', $col['payment']->id)
            ->where('admission_invoice_head_allocation_id', $heads[1]->id)
            ->update([
                'status' => 'Reversed',
                'reversed_at' => '2026-11-18 11:00:00',
                'reversal_reference' => 'REV-MIX-ALLOC-1',
            ]);

        // Only Head 0 (1000.00) + Head 2 (250.00) = 1250.00 are explicit 'Active'
        $admReport = $this->reportService->generateReport(['mode' => 'head']);
        $this->assertEquals('1250.00', $admReport['summary']['collected']);
        $this->assertEquals('1250.00', $admReport['summary']['period_collected']);
        $this->assertEquals('2250.00', $admReport['summary']['due']);
        $this->assertTrue($admReport['reconciliation']['is_reconciled']);

        $this->actingAs($this->adminUser);
        $finRes = $this->get(route('accounts.reports.index', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $this->assertEquals('1250.00', $finRes->viewData('totalIncome'));
        $incomeCats = collect($finRes->viewData('incomeByCategory'))->keyBy('name');
        $this->assertFalse($incomeCats->has('Session Fee'));
        $this->assertEquals('1000.00', $incomeCats['Admission Fee']['amount']);
        $this->assertEquals('250.00', $incomeCats['Monthly Fee']['amount']);

        $cw = app(ClassWiseFinancialReportService::class)->generateReport(['session_year_id' => $this->session2026->id]);
        $playRow = collect($cw['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertEquals('250.00', $playRow['collected']['monthly_fee']);
        $this->assertEquals('1000.00', $playRow['collected']['other_fees']);
        $this->assertEquals('1250.00', $playRow['collected']['total']);

        // B. Direct NULL & missing-attribute isolation on hydrated AdmissionPaymentHeadAllocation
        $freshInvoice = FeeInvoice::with([
            'admissionFeeStructure',
            'admissionHeadAllocations.category',
            'payments' => fn ($q) => $q->with('admissionHeadAllocations'),
        ])->findOrFail($inv->id);

        $pay = $freshInvoice->payments->first();
        // Keep Head 0 explicit 'Active' (1000.00); make Head 1 null; unset 'status' on Head 2
        $alloc0 = $pay->admissionHeadAllocations->firstWhere('admission_invoice_head_allocation_id', $heads[0]->id);
        $alloc1 = $pay->admissionHeadAllocations->firstWhere('admission_invoice_head_allocation_id', $heads[1]->id);
        $alloc2 = $pay->admissionHeadAllocations->firstWhere('admission_invoice_head_allocation_id', $heads[2]->id);

        $alloc1->setRawAttributes(array_merge($alloc1->getAttributes(), ['status' => null]));
        $attrs2 = $alloc2->getAttributes();
        unset($attrs2['status']);
        $alloc2->setRawAttributes($attrs2);

        $headSummary = $this->reportService->summarizeAdmissionInvoiceActiveHeads($freshInvoice);
        $this->assertEquals('1000.00', $headSummary['collected']);
        $this->assertEquals('2500.00', $headSummary['due']);
        $this->assertEquals('1000.00', $headSummary['heads'][$heads[0]->id]['collected']);
        $this->assertEquals('0.00', $headSummary['heads'][$heads[1]->id]['collected']);
        $this->assertEquals('0.00', $headSummary['heads'][$heads[2]->id]['collected']);

        // If Payment status itself is NULL (non-'Active'), zero collected even if allocations say 'Active'
        $pay->setRawAttributes(array_merge($pay->getAttributes(), ['status' => null]));
        $nullPaymentSummary = $this->reportService->summarizeAdmissionInvoiceActiveHeads($freshInvoice);
        $this->assertEquals('0.00', $nullPaymentSummary['collected']);
        $this->assertEquals('3500.00', $nullPaymentSummary['due']);
        $alloc0Check = $this->reportService->aggregateActivePaymentCategoryAmounts($pay);
        $this->assertEquals('0.00', $alloc0Check['effective_total']);
    }

    public function test_invoice_date_versus_payment_date_and_cumulative_collected_vs_period_collected(): void
    {
        $stu1 = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-OCT');
        $inv1 = $this->invoiceService->generateForStudent($stu1, [], $this->superAdmin->id)['invoice'];
        DB::table('fee_invoices')->where('id', $inv1->id)->update(['due_date' => '2026-10-15']);
        $heads1 = $inv1->admissionHeadAllocations()->orderBy('sort_order')->get();

        // October payment (1200.00) + November payment (800.00) against October invoice
        $this->collectionService->collectSinglePayment($inv1->id, [
            'pay_amount' => '1200.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-10-20',
            'head_allocations' => [
                $heads1[0]->id => '1200.00',
                $heads1[1]->id => '0.00',
                $heads1[2]->id => '0.00',
            ],
        ], $this->adminUser->id);
        $this->collectionService->collectSinglePayment($inv1->id, [
            'pay_amount' => '800.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-12',
            'head_allocations' => [
                $heads1[0]->id => '300.00',
                $heads1[1]->id => '500.00',
                $heads1[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        // Invoice 2: NULL due_date, created_at = '2026-11-05 10:00:00' -> tests COALESCE(due_date, DATE(created_at))
        // Pre-paid in October (payment_date = '2026-10-28') for 900.00 so cumulative Collected = 900.00 while Nov period_collected = 0.00 for Inv 2
        $stu2 = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-NOV');
        $inv2 = $this->invoiceService->generateForStudent($stu2, [
            'discount_type' => 'fixed',
            'discount_value' => '200.00',
        ], $this->superAdmin->id)['invoice'];
        DB::table('fee_invoices')->where('id', $inv2->id)->update([
            'due_date' => null,
            'created_at' => '2026-11-05 10:00:00',
        ]);
        $heads2 = $inv2->admissionHeadAllocations()->orderBy('sort_order')->get();
        $this->collectionService->collectSinglePayment($inv2->id, [
            'pay_amount' => '900.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-10-28',
            'head_allocations' => [
                $heads2[0]->id => '900.00',
                $heads2[1]->id => '0.00',
                $heads2[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        // Query strictly November [2026-11-01 .. 2026-11-30] across ALL 4 modes
        foreach (['branch', 'class', 'student', 'head'] as $mode) {
            $report = $this->reportService->generateReport([
                'mode' => $mode,
                'date_from' => '2026-11-01',
                'date_to' => '2026-11-30',
            ]);

            // Only Inv 2 matches COALESCE(due_date, DATE(created_at)) in November (Gross 3500, Disc 200, Net 3300, Cumulative Collected 900, Due 2400)
            $this->assertEquals('3500.00', $report['summary']['gross']);
            $this->assertEquals('200.00', $report['summary']['discount']);
            $this->assertEquals('3300.00', $report['summary']['net_receivable']);
            $this->assertEquals('900.00', $report['summary']['collected']);
            $this->assertEquals('2400.00', $report['summary']['due']);

            // period_collected independently counts November FeePayment.payment_date (Inv 1's 800.00!)
            $this->assertEquals('800.00', $report['summary']['period_collected']);
            $this->assertTrue($report['reconciliation']['is_reconciled']);
            $this->assertTrue($report['reconciliation']['net_minus_collected_equals_due']);
        }
    }

    public function test_historical_admission_class_preserved_after_student_promotion_across_all_reports(): void
    {
        // Admitted to Class Play with Play Admission Package
        $student = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-PROMO');
        $admInv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $heads = $admInv->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->collectionService->collectSinglePayment($admInv->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1000.00',
                $heads[1]->id => '0.00',
                $heads[2]->id => '500.00',
            ],
        ], $this->adminUser->id);

        // Later student promotes to Class One AND gets a Class One Legacy Monthly Fee invoice
        $student->update([
            'class_id' => $this->clsOne->id,
            'section_id' => $this->secOne->id,
        ]);

        $oneSetup = FeeSetup::create([
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsOne->id,
            'session_year_id' => $this->session2026->id,
            'fee_category_id' => $this->tuitionCat->id,
            'fee_month' => 'February',
            'amount' => '1200.00',
            'status' => 'Active',
            'user_id' => $this->superAdmin->id,
        ]);
        $legacyOneInv = FeeInvoice::create([
            'invoice_no' => 'INV-202602-7777',
            'student_id' => $student->id,
            'branch_id' => $this->branchMain->id,
            'session_year_id' => $this->session2026->id,
            'branch_name_snapshot' => $this->branchMain->branch_name,
            'fee_setup_id' => $oneSetup->id,
            'amount' => '1200.00',
            'discount' => '0.00',
            'net_amount' => '1200.00',
            'paid_amount' => '1200.00',
            'due_amount' => '0.00',
            'status' => 'Paid',
            'due_date' => '2026-02-10',
            'user_id' => $this->superAdmin->id,
        ]);
        FeePayment::create([
            'receipt_no' => 'REC-20260210-7777',
            'fee_invoice_id' => $legacyOneInv->id,
            'student_id' => $student->id,
            'branch_id' => $this->branchMain->id,
            'paid_amount' => '1200.00',
            'payment_date' => '2026-02-10',
            'payment_method' => 'Cash',
            'status' => 'Active',
            'collected_by' => $this->adminUser->id,
        ]);

        // 1. AdmissionFeeReportService must attribute Admission Invoice ONLY to Play (never promoted Class One)
        $admClassReport = $this->reportService->generateReport(['mode' => 'class']);
        $this->assertCount(1, $admClassReport['rows']);
        $this->assertEquals($this->clsPlay->id, $admClassReport['rows'][0]['class_id']);
        $this->assertEquals('Play', $admClassReport['rows'][0]['class_name']);
        $this->assertEquals('3500.00', $admClassReport['rows'][0]['net_receivable']);
        $this->assertEquals('1500.00', $admClassReport['rows'][0]['collected']);

        $oneFilteredReport = $this->reportService->generateReport(['mode' => 'class', 'class_id' => $this->clsOne->id]);
        $this->assertCount(0, $oneFilteredReport['rows']);
        $this->assertEquals('0.00', $oneFilteredReport['summary']['net_receivable']);

        // 2. ClassWiseFinancialReportService keeps Admission in Play & Legacy Monthly Fee in One
        $cwReport = app(ClassWiseFinancialReportService::class)->generateReport([
            'session_year_id' => $this->session2026->id,
        ]);
        $cwByClass = collect($cwReport['rows'])->keyBy('class_id');

        $playRow = $cwByClass[$this->clsPlay->id];
        $this->assertEquals('1000.00', $playRow['due']['monthly_fee']);
        $this->assertEquals('2500.00', $playRow['due']['other_fees']);
        $this->assertEquals('3500.00', $playRow['due']['total']);
        $this->assertEquals('500.00', $playRow['collected']['monthly_fee']);
        $this->assertEquals('1000.00', $playRow['collected']['other_fees']);
        $this->assertEquals('1500.00', $playRow['collected']['total']);
        $this->assertEquals('2000.00', $playRow['outstanding']['total']);

        $oneRow = $cwByClass[$this->clsOne->id];
        $this->assertEquals('1200.00', $oneRow['due']['monthly_fee']);
        $this->assertEquals('0.00', $oneRow['due']['other_fees']);
        $this->assertEquals('1200.00', $oneRow['collected']['monthly_fee']);
        $this->assertEquals('0.00', $oneRow['outstanding']['total']);

        // 3. FeeReportController::summaryReport filtered by Play includes historical Play Admission invoice
        $this->actingAs($this->adminUser);
        $summaryPlay = $this->get(route('fees.reports.summary', [
            'session_year_id' => $this->session2026->id,
            'class_id' => $this->clsPlay->id,
        ]));
        $summaryPlay->assertOk();
        $this->assertEquals('3500.00', $summaryPlay->viewData('overallNet'));
        $this->assertEquals('1500.00', $summaryPlay->viewData('overallPaid'));
        $this->assertEquals('2000.00', $summaryPlay->viewData('overallDue'));
    }

    public function test_excel_export_returns_valid_xlsx_with_exact_reconciled_numbers(): void
    {
        $stuPlay = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-XLSX');
        $inv = $this->invoiceService->generateForStudent($stuPlay, [], $this->superAdmin->id)['invoice'];
        $heads = $inv->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '1625.50',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '1000.50',
                $heads[1]->id => '625.00',
                $heads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        $this->actingAs($this->adminUser);

        foreach (['branch', 'class', 'student', 'head'] as $mode) {
            $res = $this->get(route('fees.admission.reports.export_excel', ['mode' => $mode]));
            $res->assertOk();
            $res->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->assertStringContainsString("admission_fee_report_{$mode}_", $res->headers->get('Content-Disposition'));

            $binary = $res->getContent();
            $this->assertStringStartsWith("PK\x03\x04", $binary);

            $entries = $this->extractZipEntries($binary);
            $this->assertArrayHasKey('xl/worksheets/sheet1.xml', $entries);
            $this->assertArrayHasKey('xl/sharedStrings.xml', $entries);

            $combined = $entries['xl/worksheets/sheet1.xml'] . $entries['xl/sharedStrings.xml'];
            $this->assertStringContainsString('MACS SCHOOL', $combined);
            $this->assertStringContainsString('1625.50', $combined);
            $this->assertStringContainsString('3500.00', $combined);
            $this->assertStringContainsString('1874.50', $combined);
        }
    }

    /**
     * Pure-PHP ZIP parser to verify genuine .xlsx worksheet XML contents without requiring `ext-zip`.
     *
     * @return array<string, string>
     */
    protected function extractZipEntries(string $binary): array
    {
        $entries = [];
        $offset = 0;
        $len = strlen($binary);

        while ($offset + 30 <= $len) {
            if (substr($binary, $offset, 4) !== "PK\x03\x04") {
                break;
            }
            $header = unpack('vversion/vflag/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vfnlen/vexlen', substr($binary, $offset + 4, 26));
            $nameStart = $offset + 30;
            $name = substr($binary, $nameStart, $header['fnlen']);
            $dataStart = $nameStart + $header['fnlen'] + $header['exlen'];
            $payload = substr($binary, $dataStart, $header['csize']);

            if ($header['method'] === 8) {
                $inflated = @gzinflate($payload);
                $entries[$name] = $inflated !== false ? $inflated : '';
            } else {
                $entries[$name] = $payload;
            }

            $offset = $dataStart + $header['csize'];
        }

        return $entries;
    }

    public function test_rbac_blocks_unauthorized_roles_on_admission_report_endpoints(): void
    {
        $endpoints = [
            route('fees.admission.reports.index'),
            route('fees.admission.reports.export_excel'),
        ];

        // Guest
        foreach ($endpoints as $url) {
            $res = $this->get($url);
            $this->assertContains($res->getStatusCode(), [302, 401, 403]);
        }

        // Editor, Teacher, Student
        foreach ([$this->editorUser, $this->teacherUser, $this->studentUser] as $blockedUser) {
            foreach ($endpoints as $url) {
                $res = $this->actingAs($blockedUser)->get($url);
                $this->assertContains($res->getStatusCode(), [302, 403]);
            }
        }

        // Admin & Super Admin succeed
        foreach ([$this->adminUser, $this->superAdmin] as $allowedUser) {
            foreach ($endpoints as $url) {
                $this->actingAs($allowedUser)->get($url)->assertOk();
            }
        }
    }

    public function test_existing_reports_reconcile_mixed_admission_and_legacy_invoices_with_zero_uncategorized_or_general_fallback(): void
    {
        $student = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-PARITY');

        // 1. Admission Invoice (3500 gross - 500 disc = 3000 net); partial payment 1700.00 across 3 heads
        $admInv = $this->invoiceService->generateForStudent($student, [
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ], $this->superAdmin->id)['invoice'];
        $admHeads = $admInv->admissionHeadAllocations()->orderBy('sort_order')->get();
        $admCol = $this->collectionService->collectSinglePayment($admInv->id, [
            'pay_amount' => '1700.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $admHeads[0]->id => '700.00',
                $admHeads[1]->id => '600.00',
                $admHeads[2]->id => '400.00',
            ],
        ], $this->adminUser->id);

        // 2. Legacy Invoice (Term Exam Fee = 500.00, fully paid on 2026-11-18 via Bank)
        $legacySetup = FeeSetup::create([
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsPlay->id,
            'session_year_id' => $this->session2026->id,
            'fee_category_id' => $this->examCat->id,
            'fee_month' => 'November',
            'amount' => '500.00',
            'status' => 'Active',
            'user_id' => $this->superAdmin->id,
        ]);
        $legacyInv = FeeInvoice::create([
            'invoice_no' => 'INV-202611-8888',
            'student_id' => $student->id,
            'branch_id' => $this->branchMain->id,
            'session_year_id' => $this->session2026->id,
            'branch_name_snapshot' => $this->branchMain->branch_name,
            'fee_setup_id' => $legacySetup->id,
            'amount' => '500.00',
            'discount' => '0.00',
            'net_amount' => '500.00',
            'paid_amount' => '500.00',
            'due_amount' => '0.00',
            'status' => 'Paid',
            'due_date' => '2026-11-15',
            'user_id' => $this->superAdmin->id,
        ]);
        FeePayment::create([
            'receipt_no' => 'REC-20261118-8888',
            'fee_invoice_id' => $legacyInv->id,
            'student_id' => $student->id,
            'branch_id' => $this->branchMain->id,
            'paid_amount' => '500.00',
            'payment_date' => '2026-11-18',
            'payment_method' => 'Bank',
            'status' => 'Active',
            'collected_by' => $this->adminUser->id,
        ]);

        // Expense 250.00 Cash to check Net Cash Movement = 1700.00 - 250.00 = 1450.00
        $expCat = ExpenseCategory::create(['name' => 'Office', 'status' => 'Active', 'user_id' => $this->adminUser->id]);
        Expense::create([
            'voucher_no' => 'EXP-4C-01',
            'expense_category_id' => $expCat->id,
            'branch_id' => $this->branchMain->id,
            'expense_date' => '2026-11-18',
            'amount' => '250.00',
            'payment_method' => 'Cash',
            'paid_to' => 'Stationery',
            'user_id' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        // A. FeeReportController::summaryReport (Net 3000 + 500 = 3500; Paid 1700 + 500 = 2200; Due = 1300)
        $summaryRes = $this->get(route('fees.reports.summary', [
            'branch_id' => $this->branchMain->id,
            'session_year_id' => $this->session2026->id,
            'class_id' => $this->clsPlay->id,
        ]));
        $summaryRes->assertOk();
        $this->assertEquals('3500.00', $summaryRes->viewData('overallNet'));
        $this->assertEquals('2200.00', $summaryRes->viewData('overallPaid'));
        $this->assertEquals('1300.00', $summaryRes->viewData('overallDue'));

        $catSummary = $summaryRes->viewData('categorySummary');
        $this->assertFalse($catSummary->has('Uncategorized'));
        $this->assertEquals('700.00', $catSummary['Admission Fee']->total_paid);
        $this->assertEquals('600.00', $catSummary['Session Fee']->total_paid);
        $this->assertEquals('400.00', $catSummary['Monthly Fee']->total_paid);
        $this->assertEquals('500.00', $catSummary['Term Exam Fee']->total_paid);

        // B. FeeReportController::index (Collection History report)
        $historyRes = $this->get(route('fees.reports.index', [
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsPlay->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $historyRes->assertOk();
        $this->assertEquals('2200.00', $historyRes->viewData('totalCollected'));
        $this->assertEquals('1300.00', $historyRes->viewData('totalDue'));
        $historyRes->assertSee($admCol['receipt_no']);

        // C. FinancialReportController (index & printStatement)
        $finRes = $this->get(route('accounts.reports.index', [
            'branch_id' => $this->branchMain->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $finRes->assertOk();
        $this->assertEquals('2200.00', $finRes->viewData('totalIncome'));
        $this->assertEquals('1450.00', $finRes->viewData('netCashMovement'));
        $incomeByCat = collect($finRes->viewData('incomeByCategory'))->keyBy('name');
        $this->assertFalse($incomeByCat->has('General Fee'));
        $this->assertEquals('700.00', $incomeByCat['Admission Fee']['amount']);
        $this->assertEquals('600.00', $incomeByCat['Session Fee']['amount']);
        $this->assertEquals('400.00', $incomeByCat['Monthly Fee']['amount']);
        $this->assertEquals('500.00', $incomeByCat['Term Exam Fee']['amount']);
        $finRes->assertSee('Play 2026 Main Pack');

        $this->get(route('accounts.reports.print', [
            'branch_id' => $this->branchMain->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]))->assertOk()->assertSee('Admission Fee')->assertSee('Term Exam Fee');

        // D. ClassWiseFinancialReportController (Web, Print, Excel, PDF)
        $cw = app(ClassWiseFinancialReportService::class)->generateReport([
            'session_year_id' => $this->session2026->id,
        ]);
        $playRow = collect($cw['rows'])->firstWhere('class_id', $this->clsPlay->id);
        $this->assertEquals('3500.00', $playRow['due']['total']);
        $this->assertEquals('2200.00', $playRow['collected']['total']);
        $this->assertEquals('1300.00', $playRow['outstanding']['total']);
        $this->assertEquals('400.00', $playRow['collected']['monthly_fee']);
        $this->assertEquals('500.00', $playRow['collected']['exam_fees']);  // 500 Exam
        $this->assertEquals('1300.00', $playRow['collected']['other_fees']); // 700 Adm + 600 Sess

        $this->get(route('accounts.reports.class_wise', ['session_year_id' => $this->session2026->id]))->assertOk();
        $this->get(route('accounts.reports.class_wise_print', ['session_year_id' => $this->session2026->id]))->assertOk();
        $this->get(route('accounts.reports.class_wise_excel', ['session_year_id' => $this->session2026->id]))->assertOk();
        $this->get(route('accounts.reports.class_wise_pdf', ['session_year_id' => $this->session2026->id]))->assertOk();
    }

    public function test_structural_category_filters_and_non_negative_due_guard(): void
    {
        $stuMain = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-4C-FILT-1');
        $invMain = $this->invoiceService->generateForStudent($stuMain, [], $this->superAdmin->id)['invoice'];
        $headsMain = $invMain->admissionHeadAllocations()->orderBy('sort_order')->get();
        $this->collectionService->collectSinglePayment($invMain->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $headsMain[0]->id => '1500.00',
                $headsMain[1]->id => '0.00',
                $headsMain[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        $stuCity = $this->createStudent($this->branchCity, $this->clsOne, $this->secOne, 'STD-4C-FILT-2');
        $this->invoiceService->generateForStudent($stuCity, [], $this->superAdmin->id);

        $this->actingAs($this->adminUser);

        // 1. Branch + Fee Category filter keeps head-sum === invoice-sum reconciled
        $catFiltered = $this->getJson(route('fees.admission.reports.index', [
            'mode' => 'student',
            'branch_id' => $this->branchMain->id,
            'fee_category_id' => $this->admissionCat->id,
        ]));
        $catFiltered->assertOk();
        $d = $catFiltered->json();
        $this->assertEquals(1, $d['summary']['active_invoice_count']);
        $this->assertEquals('1500.00', $d['summary']['net_receivable']);
        $this->assertEquals('1500.00', $d['summary']['collected']);
        $this->assertEquals('0.00', $d['summary']['due']);
        $this->assertTrue($d['reconciliation']['is_reconciled']);

        // 2. Payment status Partial vs Unpaid + search filter
        $unpaidCity = $this->getJson(route('fees.admission.reports.index', [
            'mode' => 'branch',
            'payment_status' => 'Unpaid',
            'search' => 'STD-4C-FILT-2',
        ]))->json();
        $this->assertEquals(1, $unpaidCity['summary']['active_invoice_count']);
        $this->assertEquals('3500.00', $unpaidCity['summary']['due']);
        $this->assertTrue($unpaidCity['reconciliation']['is_reconciled']);

        // 3. Non-negative due guard even on hypothetical over-allocated hydrated head
        $freshInv = FeeInvoice::with(['admissionHeadAllocations.category', 'payments.admissionHeadAllocations'])
            ->findOrFail($invMain->id);
        $pay = $freshInv->payments->first();
        $payAlloc0 = $pay->admissionHeadAllocations->firstWhere('admission_invoice_head_allocation_id', $headsMain[0]->id);
        $payAlloc0->setRawAttributes(array_merge($payAlloc0->getAttributes(), ['allocated_paid_amount' => '1900.00']));

        $overPaidSummary = $this->reportService->summarizeAdmissionInvoiceActiveHeads($freshInv);
        $this->assertEquals('0.00', $overPaidSummary['heads'][$headsMain[0]->id]['due']);
        $this->assertGreaterThanOrEqual(0, bccomp($overPaidSummary['due'], '0.00', 2));
    }

    public function test_class_wise_head_matrix_metrics_academic_order_option_a_and_excel_export(): void
    {
        $this->createAdmissionStructure($this->branchMain, $this->clsTen, 'Ten 2026 Main Pack');

        // 1. Play Student (Main Branch): 3500 gross - 500 discount = 3000 net (300 Adm + 200 Tuition disc)
        // Active payments: 1100.00 on 2026-10-15 + 750.25 on 2026-11-18 = 1850.25 collected; 400.00 reversed
        $stuPlay = $this->createStudent($this->branchMain, $this->clsPlay, $this->secPlay, 'STD-MX-PLAY');
        $invPlay = $this->invoiceService->generateForStudent($stuPlay, [
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ], $this->superAdmin->id)['invoice'];
        \Illuminate\Support\Facades\DB::table('fee_invoices')->where('id', $invPlay->id)->update(['due_date' => '2026-11-18']);
        $playHeads = $invPlay->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->collectionService->collectSinglePayment($invPlay->id, [
            'pay_amount' => '1100.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-10-15',
            'head_allocations' => [
                $playHeads[0]->id => '600.00',
                $playHeads[1]->id => '300.00',
                $playHeads[2]->id => '200.00',
            ],
        ], $this->adminUser->id);

        $reversedPlayCol = $this->collectionService->collectSinglePayment($invPlay->id, [
            'pay_amount' => '400.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $playHeads[0]->id => '400.00',
                $playHeads[1]->id => '0.00',
                $playHeads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);
        $this->reversalService->reversePayment(
            $reversedPlayCol['payment']->id,
            'Duplicate test slip reversal',
            $this->superAdmin->id
        );

        $this->collectionService->collectSinglePayment($invPlay->id, [
            'pay_amount' => '750.25',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $playHeads[0]->id => '200.25',
                $playHeads[1]->id => '350.00',
                $playHeads[2]->id => '200.00',
            ],
        ], $this->adminUser->id);

        // 2. Class One Student (City Branch): 3500 gross - 0 discount = 3500 net; unpaid -> 3500 due
        $stuOne = $this->createStudent($this->branchCity, $this->clsOne, $this->secOne, 'STD-MX-ONE');
        $invOne = $this->invoiceService->generateForStudent($stuOne, [], $this->superAdmin->id)['invoice'];
        \Illuminate\Support\Facades\DB::table('fee_invoices')->where('id', $invOne->id)->update(['due_date' => '2026-11-18']);

        // 3. Class Ten Student (Main Branch): 3500 gross - 0 discount = 3500 net; 1000 collected -> 2500 due
        $stuTen = $this->createStudent($this->branchMain, $this->clsTen, $this->secTen, 'STD-MX-TEN');
        $invTen = $this->invoiceService->generateForStudent($stuTen, [], $this->superAdmin->id)['invoice'];
        \Illuminate\Support\Facades\DB::table('fee_invoices')->where('id', $invTen->id)->update(['due_date' => '2026-11-18']);
        $tenHeads = $invTen->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->collectionService->collectSinglePayment($invTen->id, [
            'pay_amount' => '1000.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $tenHeads[0]->id => '1000.00',
                $tenHeads[1]->id => '0.00',
                $tenHeads[2]->id => '0.00',
            ],
        ], $this->adminUser->id);

        $admKey = $this->admissionCat->id . '|admission registration';
        $sessKey = $this->sessionCat->id . '|session development';
        $tuiKey = $this->tuitionCat->id . '|january tuition fee';

        // Default Net Receivable matrix checks column order, academic class order, cell values, and reconciliation
        $netReport = $this->reportService->generateReport(['mode' => 'class']);
        $netMatrix = $netReport['class_matrix'];
        $this->assertEquals('net_receivable', $netMatrix['metric']);
        $this->assertEquals('Net Receivable', $netMatrix['metric_label']);
        $this->assertTrue($netMatrix['is_reconciled']);
        $this->assertTrue($netReport['reconciliation']['is_reconciled']);

        $this->assertSame(
            [$admKey, $sessKey, $tuiKey],
            array_column($netMatrix['head_columns'], 'head_key')
        );
        $this->assertSame(
            ['Play', 'One', 'Ten'],
            array_column($netMatrix['rows'], 'class_name')
        );

        $rowsByName = collect($netMatrix['rows'])->keyBy('class_name');
        $this->assertEquals('3000.00', $rowsByName['Play']['net_receivable']);
        $this->assertEquals('1850.25', $rowsByName['Play']['collected']);
        $this->assertEquals('1149.75', $rowsByName['Play']['due']);
        $this->assertEquals('1500.00', $rowsByName['Play']['cells'][$admKey]['gross']);
        $this->assertEquals('300.00', $rowsByName['Play']['cells'][$admKey]['discount']);
        $this->assertEquals('1200.00', $rowsByName['Play']['cells'][$admKey]['net_receivable']);
        $this->assertEquals('800.25', $rowsByName['Play']['cells'][$admKey]['collected']);
        $this->assertEquals('399.75', $rowsByName['Play']['cells'][$admKey]['due']);
        $this->assertEquals('1000.00', $rowsByName['Play']['cells'][$sessKey]['net_receivable']);
        $this->assertEquals('800.00', $rowsByName['Play']['cells'][$tuiKey]['net_receivable']);

        // Grand totals across Play (3000) + One (3500) + Ten (3500) = 10000.00 net
        $this->assertEquals('4200.00', $netMatrix['column_totals'][$admKey]['net_receivable']);
        $this->assertEquals('3000.00', $netMatrix['column_totals'][$sessKey]['net_receivable']);
        $this->assertEquals('2800.00', $netMatrix['column_totals'][$tuiKey]['net_receivable']);
        $this->assertEquals('1800.25', $netMatrix['column_totals'][$admKey]['collected']);
        $this->assertEquals('650.00', $netMatrix['column_totals'][$sessKey]['collected']);
        $this->assertEquals('400.00', $netMatrix['column_totals'][$tuiKey]['collected']);
        $this->assertEquals('2399.75', $netMatrix['column_totals'][$admKey]['due']);

        // Switch through remaining metrics and verify active_value & reconciliation
        $expectedTotalsByMetric = [
            'collected' => [$admKey => '1800.25', $sessKey => '650.00', $tuiKey => '400.00'],
            'gross' => [$admKey => '4500.00', $sessKey => '3000.00', $tuiKey => '3000.00'],
            'discount' => [$admKey => '300.00', $sessKey => '0.00', $tuiKey => '200.00'],
            'due' => [$admKey => '2399.75', $sessKey => '2350.00', $tuiKey => '2400.00'],
            'period_collected' => [$admKey => '1800.25', $sessKey => '650.00', $tuiKey => '400.00'],
        ];
        foreach ($expectedTotalsByMetric as $metric => $colExpectations) {
            $metricRep = $this->reportService->generateReport([
                'mode' => 'class',
                'matrix_metric' => $metric,
            ]);
            $mx = $metricRep['class_matrix'];
            $this->assertEquals($metric, $mx['metric']);
            $this->assertTrue($mx['is_reconciled']);
            foreach ($colExpectations as $hKey => $expectedAmt) {
                $this->assertEquals($expectedAmt, $mx['column_totals'][$hKey]['active_value']);
            }
        }

        // Option A date filter (Oct 2026 only): invoices are due in Nov 2026 -> cohort net/due = 0.00,
        // while Oct 15 cash receipt (1100.00) appears accurately in Period Collected matrix cells
        $octPeriodRep = $this->reportService->generateReport([
            'mode' => 'class',
            'matrix_metric' => 'period_collected',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-31',
        ]);
        $octMx = $octPeriodRep['class_matrix'];
        $this->assertTrue($octMx['is_reconciled']);
        $this->assertEquals('0.00', $octPeriodRep['summary']['net_receivable']);
        $this->assertEquals('1100.00', $octPeriodRep['summary']['period_collected']);
        $octRows = collect($octMx['rows'])->keyBy('class_name');
        $this->assertEquals('1100.00', $octRows['Play']['period_collected']);
        $this->assertEquals('600.00', $octRows['Play']['cells'][$admKey]['active_value']);
        $this->assertEquals('300.00', $octRows['Play']['cells'][$sessKey]['active_value']);
        $this->assertEquals('200.00', $octRows['Play']['cells'][$tuiKey]['active_value']);
        $this->assertEquals('0.00', $octRows['One']['cells'][$admKey]['active_value']);
        $this->assertEquals('600.00', $octMx['column_totals'][$admKey]['period_collected']);

        // Category filter narrows matrix columns cleanly and preserves reconciliation
        $catRep = $this->reportService->generateReport([
            'mode' => 'class',
            'fee_category_id' => $this->admissionCat->id,
        ]);
        $this->assertCount(1, $catRep['class_matrix']['head_columns']);
        $this->assertEquals($admKey, $catRep['class_matrix']['head_columns'][0]['head_key']);
        $this->assertTrue($catRep['class_matrix']['is_reconciled']);
        $this->assertEquals('4200.00', $catRep['class_matrix']['column_totals'][$admKey]['net_receivable']);

        // Class Matrix Excel export in collected mode
        $this->actingAs($this->adminUser);
        $excelRes = $this->get(route('fees.admission.reports.export_excel', [
            'mode' => 'class',
            'class_view' => 'matrix',
            'matrix_metric' => 'collected',
        ]));
        $excelRes->assertOk();
        $entries = $this->extractZipEntries((string) $excelRes->getContent());
        $this->assertArrayHasKey('xl/worksheets/sheet1.xml', $entries);
        $sheetXml = $entries['xl/worksheets/sheet1.xml'];
        $sharedStringsXml = $entries['xl/sharedStrings.xml'] ?? $sheetXml;
        $this->assertStringContainsString('Class Matrix Metric:', $sharedStringsXml);
        $this->assertStringContainsString('Collected (Cumul.)', $sharedStringsXml);
        $this->assertStringContainsString('Admission Registration', $sharedStringsXml);
        $this->assertStringContainsString('Session Development', $sharedStringsXml);
        $this->assertStringContainsString('January Tuition Fee', $sharedStringsXml);
        $this->assertStringContainsString('Play', $sharedStringsXml);
        $this->assertStringContainsString('One', $sharedStringsXml);
        $this->assertStringContainsString('Ten', $sharedStringsXml);
        $this->assertStringContainsString('Grand Total', $sharedStringsXml);
        $this->assertStringNotContainsString('Collection %', $sharedStringsXml);
        $this->assertStringContainsString('1850.25', $sheetXml);
        $this->assertStringContainsString('800.25', $sheetXml);
        $this->assertStringContainsString('1800.25', $sheetXml);
    }
}
