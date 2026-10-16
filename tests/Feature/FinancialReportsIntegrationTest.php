<?php

namespace Tests\Feature;

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
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $studentUser;
    protected Branch $branchMain;
    protected Branch $branchNorth;
    protected FeeCategory $tuitionCategory;
    protected FeeCategory $examCategory;
    protected ExpenseCategory $utilityCategory;
    protected ExpenseCategory $salaryCategory;
    protected Student $studentMain;
    protected Student $studentNorth;
    protected FeeSetup $tuitionSetup;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Users
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_fin@macsschool.com'],
            ['name' => 'Finance Director', 'password' => bcrypt('password')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        $this->studentUser = User::firstOrCreate(
            ['email' => 'student_fin@macsschool.com'],
            ['name' => 'Student Tester', 'password' => bcrypt('password')]
        );
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->studentUser->assignRole($studentRole);

        // 2. Academic & Branch Setup
        $this->branchMain = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->branchNorth = Branch::firstOrCreate(['id' => 2], ['branch_name' => 'North Campus', 'user_id' => $this->adminUser->id]);

        $session = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026', 'user_id' => $this->adminUser->id]);
        $class = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Six', 'numeric_value' => 6, 'user_id' => $this->adminUser->id]);
        $section = Section::firstOrCreate(['id' => 1], ['section_name' => 'A', 'class_id' => $class->id, 'user_id' => $this->adminUser->id]);

        $this->studentMain = Student::create([
            'user_id' => $this->adminUser->id,
            'student_identity' => 'STU-001',
            'student_name' => 'Karim Ahmed',
            'roll_number' => '1',
            'dob' => '2012-01-01',
            'gender' => 'Male',
            'blood_group' => 'A+',
            'religion' => 'Islam',
            'father_name' => 'Abul Ahmed',
            'father_mobile' => '01711000001',
            'mother_name' => 'Fatema Ahmed',
            'mother_mobile' => '01711000002',
            'guardian_name' => 'Abul Ahmed',
            'guardian_mobile' => '01711000001',
            'present_village' => 'Village Main',
            'present_post_office' => 'PO Main',
            'present_district' => 'Dhaka',
            'present_division' => 'Dhaka',
            'permanent_village' => 'Village Main',
            'permanent_post_office' => 'PO Main',
            'permanent_district' => 'Dhaka',
            'permanent_division' => 'Dhaka',
            'branch_id' => $this->branchMain->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_year_id' => $session->id,
        ]);

        $this->studentNorth = Student::create([
            'user_id' => $this->adminUser->id,
            'student_identity' => 'STU-002',
            'student_name' => 'Fatima Begum',
            'roll_number' => '2',
            'dob' => '2012-05-15',
            'gender' => 'Female',
            'blood_group' => 'B+',
            'religion' => 'Islam',
            'father_name' => 'Kamal Begum',
            'father_mobile' => '01811000001',
            'mother_name' => 'Nasima Begum',
            'mother_mobile' => '01811000002',
            'guardian_name' => 'Kamal Begum',
            'guardian_mobile' => '01811000001',
            'present_village' => 'Village North',
            'present_post_office' => 'PO North',
            'present_district' => 'Chittagong',
            'present_division' => 'Chittagong',
            'permanent_village' => 'Village North',
            'permanent_post_office' => 'PO North',
            'permanent_district' => 'Chittagong',
            'permanent_division' => 'Chittagong',
            'branch_id' => $this->branchNorth->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'session_year_id' => $session->id,
        ]);

        // 3. Categories
        $this->tuitionCategory = FeeCategory::create(['name' => 'Tuition Fee', 'user_id' => $this->adminUser->id]);
        $this->examCategory = FeeCategory::create(['name' => 'Exam Fee', 'user_id' => $this->adminUser->id]);

        $this->utilityCategory = ExpenseCategory::create(['name' => 'Utilities', 'status' => 'Active', 'user_id' => $this->adminUser->id]);
        $this->salaryCategory = ExpenseCategory::create(['name' => 'Teacher Salaries', 'status' => 'Active', 'user_id' => $this->adminUser->id]);

        // 4. Fee Setup
        $this->tuitionSetup = FeeSetup::create([
            'branch_id' => $this->branchMain->id,
            'session_year_id' => $session->id,
            'class_id' => $class->id,
            'fee_category_id' => $this->tuitionCategory->id,
            'fee_type' => 'monthly',
            'fee_month' => 'October',
            'amount' => 2000.00,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_unpaid_invoices_are_never_counted_as_income(): void
    {
        // Invoice generated for 5000 BDT, but 0 payments made
        FeeInvoice::create([
            'invoice_no' => 'INV-2026-TEST01',
            'student_id' => $this->studentMain->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 5000.00,
            'discount' => 500.00,
            'net_amount' => 4500.00,
            'paid_amount' => 0.00,
            'due_amount' => 4500.00,
            'status' => 'Unpaid',
            'due_date' => '2026-10-25',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        // Total collected income MUST BE ZERO!
        $response->assertViewHas('totalIncome', 0.00);
        $response->assertViewHas('totalBilledNet', 4500.00);
        $response->assertViewHas('totalOutstandingDue', 4500.00);
    }

    public function test_actual_collections_are_counted_accurately_from_fee_payments(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_no' => 'INV-2026-TEST02',
            'student_id' => $this->studentMain->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 3000.00,
            'discount' => 0.00,
            'net_amount' => 3000.00,
            'paid_amount' => 1200.00,
            'due_amount' => 1800.00,
            'status' => 'Partial',
            'user_id' => $this->adminUser->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-20261016-0001',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 1200.00,
            'payment_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        // Only the actual 1200.00 BDT paid amount is recognized as income
        $response->assertViewHas('totalIncome', 1200.00);
    }

    public function test_physical_cash_movement_subtracts_only_cash_expenses_from_cash_collections(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_no' => 'INV-2026-TEST03',
            'student_id' => $this->studentMain->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 10000.00,
            'discount' => 0.00,
            'net_amount' => 10000.00,
            'paid_amount' => 10000.00,
            'due_amount' => 0.00,
            'status' => 'Paid',
            'user_id' => $this->adminUser->id,
        ]);

        // Collection 1: Cash 6000
        FeePayment::create([
            'receipt_no' => 'REC-20261016-0002',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 6000.00,
            'payment_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        // Collection 2: Bank 4000
        FeePayment::create([
            'receipt_no' => 'REC-20261016-0003',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 4000.00,
            'payment_date' => '2026-10-16',
            'payment_method' => 'Bank',
            'collected_by' => $this->adminUser->id,
        ]);

        // Expense 1: Cash 2500 (Electricity bill)
        Expense::create([
            'voucher_no' => 'EXP-20261016-000001',
            'expense_category_id' => $this->utilityCategory->id,
            'branch_id' => $this->branchMain->id,
            'expense_date' => '2026-10-16',
            'amount' => 2500.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Pabna Electric Supply',
            'user_id' => $this->adminUser->id,
        ]);

        // Expense 2: Bank 5000 (Teacher Salary via EFT)
        Expense::create([
            'voucher_no' => 'EXP-20261016-000002',
            'expense_category_id' => $this->salaryCategory->id,
            'branch_id' => $this->branchMain->id,
            'expense_date' => '2026-10-16',
            'amount' => 5000.00,
            'payment_method' => 'Bank',
            'paid_to' => 'Bank Payroll Account',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);

        // Total Income = 6000 (Cash) + 4000 (Bank) = 10000
        $response->assertViewHas('totalIncome', 10000.00);

        // Total Expenses = 2500 (Cash) + 5000 (Bank) = 7500
        $response->assertViewHas('totalExpense', 7500.00);

        // Operating Net Surplus = 10000 - 7500 = 2500
        $response->assertViewHas('netSurplus', 2500.00);

        // CRUCIAL: Physical Cash Movement = Cash In (6000) - Cash Out (2500) = 3500!
        // Bank expense of 5000 MUST NOT reduce physical cash in hand!
        $response->assertViewHas('totalCashIn', 6000.00);
        $response->assertViewHas('totalCashOut', 2500.00);
        $response->assertViewHas('netCashMovement', 3500.00);

        // Bank channel reconciliation: In (4000) - Out (5000) = -1000
        $methodSummary = $response->viewData('methodSummary');
        $this->assertEquals(4000.00, $methodSummary['Bank']['in']);
        $this->assertEquals(5000.00, $methodSummary['Bank']['out']);
        $this->assertEquals(-1000.00, $methodSummary['Bank']['net']);
    }

    public function test_branch_filtering_scopes_both_income_and_expenses_properly(): void
    {
        $invoiceMain = FeeInvoice::create([
            'invoice_no' => 'INV-BRANCH-MAIN',
            'student_id' => $this->studentMain->id, // Branch 1
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 1000.00,
            'net_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0.00,
            'status' => 'Paid',
            'user_id' => $this->adminUser->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-MAIN-01',
            'fee_invoice_id' => $invoiceMain->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 1000.00,
            'payment_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $invoiceNorth = FeeInvoice::create([
            'invoice_no' => 'INV-BRANCH-NORTH',
            'student_id' => $this->studentNorth->id, // Branch 2
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 2000.00,
            'net_amount' => 2000.00,
            'paid_amount' => 2000.00,
            'due_amount' => 0.00,
            'status' => 'Paid',
            'user_id' => $this->adminUser->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-NORTH-01',
            'fee_invoice_id' => $invoiceNorth->id,
            'student_id' => $this->studentNorth->id,
            'paid_amount' => 2000.00,
            'payment_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        // Expense for Main branch
        Expense::create([
            'voucher_no' => 'EXP-MAIN-01',
            'expense_category_id' => $this->utilityCategory->id,
            'branch_id' => $this->branchMain->id,
            'expense_date' => '2026-10-16',
            'amount' => 400.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Local Main Vendor',
            'user_id' => $this->adminUser->id,
        ]);

        // Expense for North branch
        Expense::create([
            'voucher_no' => 'EXP-NORTH-01',
            'expense_category_id' => $this->utilityCategory->id,
            'branch_id' => $this->branchNorth->id,
            'expense_date' => '2026-10-16',
            'amount' => 900.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Local North Vendor',
            'user_id' => $this->adminUser->id,
        ]);

        // Filter by North Branch only
        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'branch_id' => $this->branchNorth->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        // Only North branch collections = 2000
        $response->assertViewHas('totalIncome', 2000.00);
        // Only North branch expenses = 900
        $response->assertViewHas('totalExpense', 900.00);
        // Net surplus = 2000 - 900 = 1100
        $response->assertViewHas('netSurplus', 1100.00);
    }

    public function test_payment_method_filtering_isolates_specific_channel(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_no' => 'INV-MFS-TEST',
            'student_id' => $this->studentMain->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 5000.00,
            'net_amount' => 5000.00,
            'paid_amount' => 5000.00,
            'due_amount' => 0.00,
            'status' => 'Paid',
            'user_id' => $this->adminUser->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-BKASH-01',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 3000.00,
            'payment_date' => '2026-10-16',
            'payment_method' => 'bKash',
            'collected_by' => $this->adminUser->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-CASH-01',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 2000.00,
            'payment_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        // Filter exclusively for bKash
        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'payment_method' => 'bKash',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 3000.00);
    }

    public function test_unauthorized_user_is_forbidden_from_financial_statements(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('accounts.reports.index'));

        $this->assertTrue(in_array($response->status(), [403, 302]));
        if ($response->status() === 302) {
            $this->assertNotEquals(route('accounts.reports.index'), $response->headers->get('Location'));
        }
    }

    public function test_printable_statement_renders_properly_with_signature_blocks(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.print', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('pages.accounts.statement_print');
        $response->assertSee('MACS School and College');
        $response->assertSee('Income & Expense Statement', false);
        $response->assertSee('Prepared By');
        $response->assertSee('Accountant');
        $response->assertSee('Headmaster / Principal');
        $response->assertSee('Chairman / Governing Body');
    }

    public function test_initial_page_load_without_query_params_applies_default_date_range_to_receivables(): void
    {
        // 1. Current month invoice (within default range)
        FeeInvoice::create([
            'invoice_no' => 'INV-CURRENT-MONTH',
            'student_id' => $this->studentMain->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 2500.00,
            'discount' => 0.00,
            'net_amount' => 2500.00,
            'paid_amount' => 0.00,
            'due_amount' => 2500.00,
            'status' => 'Unpaid',
            'due_date' => date('Y-m-10'),
            'created_at' => date('Y-m-05 10:00:00'),
            'user_id' => $this->adminUser->id,
        ]);

        // 2. Past month invoice (outside default range, 3 months ago)
        $pastInvoice = FeeInvoice::create([
            'invoice_no' => 'INV-PAST-MONTH',
            'student_id' => $this->studentMain->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 7000.00,
            'discount' => 0.00,
            'net_amount' => 7000.00,
            'paid_amount' => 0.00,
            'due_amount' => 7000.00,
            'status' => 'Unpaid',
            'due_date' => date('Y-m-d', strtotime('-3 months')),
            'user_id' => $this->adminUser->id,
        ]);
        \Illuminate\Support\Facades\DB::table('fee_invoices')->where('id', $pastInvoice->id)->update([
            'created_at' => date('Y-m-d 10:00:00', strtotime('-3 months')),
            'updated_at' => date('Y-m-d 10:00:00', strtotime('-3 months')),
        ]);

        // Request without ANY query parameters
        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index'));

        $response->assertStatus(200);
        // Receivables must strictly reflect the current default month (2500) and NOT lifetime (9500)
        $response->assertViewHas('totalBilledNet', 2500.00);
        $response->assertViewHas('totalOutstandingDue', 2500.00);
    }

    public function test_case_insensitive_payment_method_values_are_grouped_into_correct_channels(): void
    {
        $invoice = FeeInvoice::create([
            'invoice_no' => 'INV-CASE-SENSITIVE',
            'student_id' => $this->studentMain->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 10000.00,
            'discount' => 0.00,
            'net_amount' => 10000.00,
            'paid_amount' => 8000.00,
            'due_amount' => 2000.00,
            'status' => 'Partial',
            'due_date' => '2026-10-15',
            'user_id' => $this->adminUser->id,
        ]);

        // Cash lowercase
        FeePayment::create([
            'receipt_no' => 'REC-CASE-01',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 1500.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'cash',
            'collected_by' => $this->adminUser->id,
        ]);

        // Cash uppercase
        FeePayment::create([
            'receipt_no' => 'REC-CASE-02',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 2500.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'CASH',
            'collected_by' => $this->adminUser->id,
        ]);

        // bKash lowercase
        FeePayment::create([
            'receipt_no' => 'REC-CASE-03',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 1800.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'bkash',
            'collected_by' => $this->adminUser->id,
        ]);

        // bKash uppercase
        FeePayment::create([
            'receipt_no' => 'REC-CASE-04',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentMain->id,
            'paid_amount' => 2200.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'BKASH',
            'collected_by' => $this->adminUser->id,
        ]);

        // Expense cash lowercase
        Expense::create([
            'voucher_no' => 'EXP-CASE-01',
            'expense_category_id' => $this->utilityCategory->id,
            'branch_id' => $this->branchMain->id,
            'expense_date' => '2026-10-15',
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'paid_to' => 'Electric Supply',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $methodSummary = $response->viewData('methodSummary');

        // Cash reconciliation
        $this->assertEquals(4000.00, $methodSummary['Cash']['in']);
        $this->assertEquals(1000.00, $methodSummary['Cash']['out']);
        $this->assertEquals(3000.00, $methodSummary['Cash']['net']);

        // bKash reconciliation
        $this->assertEquals(4000.00, $methodSummary['bKash']['in']);
        $this->assertEquals(0.00, $methodSummary['bKash']['out']);
        $this->assertEquals(4000.00, $methodSummary['bKash']['net']);

        // Physical Cash Drawer metrics
        $this->assertEquals(4000.00, $response->viewData('totalCashIn'));
        $this->assertEquals(1000.00, $response->viewData('totalCashOut'));
        $this->assertEquals(3000.00, $response->viewData('netCashMovement'));
        $this->assertArrayNotHasKey('Other', $methodSummary);
    }

    public function test_unassigned_expenses_are_included_in_consolidated_and_excluded_from_individual_branches(): void
    {
        // Branch-assigned expense (3000)
        Expense::create([
            'voucher_no' => 'EXP-ASSIGNED-01',
            'expense_category_id' => $this->utilityCategory->id,
            'branch_id' => $this->branchMain->id,
            'expense_date' => '2026-10-16',
            'amount' => 3000.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Main Vendor',
            'user_id' => $this->adminUser->id,
        ]);

        // Central unassigned expense (branch_id = null) (1200)
        Expense::create([
            'voucher_no' => 'EXP-UNASSIGNED-01',
            'expense_category_id' => $this->salaryCategory->id,
            'branch_id' => null,
            'expense_date' => '2026-10-16',
            'amount' => 1200.00,
            'payment_method' => 'Bank',
            'paid_to' => 'Central Auditor',
            'user_id' => $this->adminUser->id,
        ]);

        // 1. Consolidated Report (no branch_id): Must include both = 4200.00
        $consolidated = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
        $consolidated->assertStatus(200);
        $consolidated->assertViewHas('totalExpense', 4200.00);

        // 2. Branch-specific Report (branch_id = branchMain->id): Must strictly exclude unassigned = 3000.00
        $branchScoped = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'branch_id' => $this->branchMain->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
        $branchScoped->assertStatus(200);
        $branchScoped->assertViewHas('totalExpense', 3000.00);
    }

    public function test_zero_transaction_period_renders_safely_without_division_by_zero(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2035-01-01',
            'end_date' => '2035-01-31',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 0.00);
        $response->assertViewHas('totalExpense', 0.00);
        $response->assertViewHas('netSurplus', 0.00);
        $response->assertViewHas('netCashMovement', 0.00);
        $response->assertSee('No collection records in this period');
        $response->assertSee('No expenses recorded for this period');
    }
}
