<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Models\FeeCategory;
use App\Models\FeeSetup;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\StudentCustomFee;
use App\Models\FeeAdjustmentLog;
use App\Models\StudentStatusLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class StudentDiscontinuationAndFeeReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Student $student;
    protected FeeSetup $tuitionSetup;
    protected FeeCategory $tuitionCategory;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $class;
    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_discontinue@macsschool.com'],
            ['name' => 'Admin Discontinue', 'password' => bcrypt('secret')]
        );

        $role = \HasinHayder\Tyro\Models\Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->adminUser->assignRole($role);

        $this->actingAs($this->adminUser);

        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main', 'user_id' => $this->adminUser->id]);
        $this->session = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026-2027', 'user_id' => $this->adminUser->id]);
        $this->class = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Ten', 'user_id' => $this->adminUser->id]);
        $this->section = Section::firstOrCreate(['id' => 1], ['section_name' => 'A', 'user_id' => $this->adminUser->id]);

        $this->student = Student::create([
            'user_id'                => $this->adminUser->id,
            'student_identity'       => 'STD-202601',
            'student_name'           => 'Rahim Ahmed',
            'roll_number'            => '01',
            'dob'                    => '2010-01-01',
            'gender'                 => 'Male',
            'father_name'            => 'Father',
            'father_mobile'          => '01711111111',
            'mother_name'            => 'Mother',
            'mother_mobile'          => '01722222222',
            'guardian_name'          => 'Guardian',
            'guardian_mobile'        => '01712345678',
            'branch_id'              => $this->branch->id,
            'session_year_id'        => $this->session->id,
            'class_id'               => $this->class->id,
            'section_id'             => $this->section->id,
            'status'                 => 'Active',
            'sms_status'             => 'Active',
            'present_village'        => 'V',
            'present_post_office'    => 'P',
            'present_district'       => 'D',
            'present_division'       => 'Div',
            'permanent_village'      => 'V',
            'permanent_post_office'  => 'P',
            'permanent_district'     => 'D',
            'permanent_division'     => 'Div',
        ]);

        $this->tuitionCategory = FeeCategory::create([
            'name'    => 'Monthly Tuition Fee',
            'status'  => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->tuitionSetup = FeeSetup::create([
            'fee_category_id' => $this->tuitionCategory->id,
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session->id,
            'class_id'        => $this->class->id,
            'fee_month'       => 'Monthly',
            'amount'          => 1000.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);
    }

    protected function createInvoice(string $invoiceNo, string $dueDate, array $attributes = []): FeeInvoice
    {
        return FeeInvoice::create(array_merge([
            'invoice_no'   => $invoiceNo,
            'student_id'   => $this->student->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount'       => 1000.00,
            'discount'     => 100.00,
            'net_amount'   => 900.00,
            'paid_amount'  => 0.00,
            'due_amount'   => 900.00,
            'status'       => 'Unpaid',
            'due_date'     => $dueDate,
            'user_id'      => $this->adminUser->id,
        ], $attributes));
    }

    public function test_discontinuation_preview_categorizes_invoices_properly(): void
    {
        // 1. January (Historical valid, paid)
        $invJan = $this->createInvoice('INV-202601-0001', '2026-01-10', [
            'paid_amount' => 900.00,
            'due_amount' => 0.00,
            'status' => 'Paid'
        ]);
        FeePayment::create([
            'receipt_no' => 'REC-20260110-0001',
            'fee_invoice_id' => $invJan->id,
            'student_id' => $this->student->id,
            'paid_amount' => 900.00,
            'payment_date' => '2026-01-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        // 2. February (Historical valid, unpaid due)
        $invFeb = $this->createInvoice('INV-202602-0001', '2026-02-10', ['due_amount' => 900.00, 'status' => 'Unpaid']);

        // 3. March (Historical valid, discontinuation month)
        $invMar = $this->createInvoice('INV-202603-0001', '2026-03-10', ['due_amount' => 900.00, 'status' => 'Unpaid']);

        // 4. April (Post-discontinuation, strictly unpaid)
        $invApr = $this->createInvoice('INV-202604-0001', '2026-04-10', ['due_amount' => 900.00, 'status' => 'Unpaid']);

        // 5. May (Post-discontinuation, partially paid)
        $invMay = $this->createInvoice('INV-202605-0001', '2026-05-10', [
            'paid_amount' => 400.00,
            'due_amount' => 500.00,
            'status' => 'Partial'
        ]);
        FeePayment::create([
            'receipt_no' => 'REC-20260510-0001',
            'fee_invoice_id' => $invMay->id,
            'student_id' => $this->student->id,
            'paid_amount' => 400.00,
            'payment_date' => '2026-05-10',
            'payment_method' => 'bKash',
            'collected_by' => $this->adminUser->id,
        ]);

        // Preview with last active month = March 2026 ('2026-03')
        $response = $this->getJson(route('students.discontinuation.preview', [
            'id' => $this->student->id,
            'last_active_month' => '2026-03',
        ]));

        $response->assertOk()
            ->assertJson(['status' => 'success'])
            ->assertJsonPath('historical_category.count', 3)
            ->assertJsonPath('cancellation_category.count', 1)
            ->assertJsonPath('review_category.count', 1);

        $data = $response->json();
        $this->assertEquals('2026-04-01', $data['discontinuation']['effective_date']);
        $this->assertEquals($invApr->id, $data['cancellation_category']['invoices'][0]['id']);
        $this->assertEquals($invMay->id, $data['review_category']['invoices'][0]['id']);
        $this->assertEquals('REC-20260510-0001', $data['review_category']['invoices'][0]['receipts'][0]['receipt_no']);
    }

    public function test_safe_reconciliation_cancels_unpaid_invoices_and_preserves_paid_ones(): void
    {
        $invMar = $this->createInvoice('INV-202603-0001', '2026-03-10');
        $invApr = $this->createInvoice('INV-202604-0001', '2026-04-10', ['amount' => 1000.00, 'discount' => 100.00, 'net_amount' => 900.00, 'due_amount' => 900.00]);
        $invMay = $this->createInvoice('INV-202605-0001', '2026-05-10', [
            'paid_amount' => 400.00,
            'due_amount' => 500.00,
            'status' => 'Partial'
        ]);
        FeePayment::create([
            'receipt_no' => 'REC-20260510-0001',
            'fee_invoice_id' => $invMay->id,
            'student_id' => $this->student->id,
            'paid_amount' => 400.00,
            'payment_date' => '2026-05-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $response = $this->postJson(route('students.discontinue.reconcile', ['id' => $this->student->id]), [
            'last_active_month' => '2026-03',
            'reason' => 'Family relocated to another district',
            'confirm' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'cancelled_count' => 1,
                'cancelled_amount' => 900.00,
            ]);

        // Student status verified
        $this->student->refresh();
        $this->assertEquals('Inactive', $this->student->status);
        $this->assertEquals('2026-03', $this->student->discontinued_month);
        $this->assertNotNull($this->student->inactive_at);
        $this->assertEquals('Family relocated to another district', $this->student->inactive_reason);

        // March invoice (historical) is untouched
        $invMar->refresh();
        $this->assertEquals('Unpaid', $invMar->status);
        $this->assertEquals(900.00, (float) $invMar->due_amount);

        // April invoice is cancelled, due cleared to 0.00, original amounts preserved
        $invApr->refresh();
        $this->assertEquals('Cancelled', $invApr->status);
        $this->assertEquals(0.00, (float) $invApr->due_amount);
        $this->assertEquals(1000.00, (float) $invApr->amount); // Preserved!
        $this->assertEquals(100.00, (float) $invApr->discount); // Preserved! NEVER discount = amount!
        $this->assertEquals(900.00, (float) $invApr->net_amount); // Preserved!

        // May invoice (partial) is preserved untouched
        $invMay->refresh();
        $this->assertEquals('Partial', $invMay->status);
        $this->assertEquals(400.00, (float) $invMay->paid_amount);
        $this->assertEquals(500.00, (float) $invMay->due_amount);

        // StudentStatusLog audit verified
        $this->assertDatabaseHas('student_status_logs', [
            'student_id' => $this->student->id,
            'action_type' => 'discontinuation',
            'previous_status' => 'Active',
            'new_status' => 'Inactive',
            'last_active_month' => '2026-03',
            'reconciled_amount' => 900.00,
        ]);

        // FeeAdjustmentLog verified
        $this->assertDatabaseHas('fee_adjustment_logs', [
            'fee_invoice_id' => $invApr->id,
            'student_id' => $this->student->id,
            'new_due_amount' => 0.00,
            'new_amount' => 1000.00,
            'new_discount' => 100.00,
        ]);
    }

    public function test_fee_auto_generate_excludes_inactive_student_for_future_months(): void
    {
        // Mark student discontinued in March 2026
        $this->student->update([
            'status' => 'Inactive',
            'discontinued_month' => '2026-03',
            'inactive_at' => now(),
        ]);

        // Run auto generator for April 2026
        Artisan::call('fees:auto-generate', [
            '--month' => 'April',
            '--year' => '2026',
        ]);

        // Invoices for April must NOT be generated for this student
        $aprilInvoice = FeeInvoice::where('student_id', $this->student->id)
            ->where('invoice_no', 'like', 'INV-202604-%')
            ->first();

        $this->assertNull($aprilInvoice, 'Discontinued student must not receive invoices for April 2026.');
    }

    public function test_fee_auto_generate_allows_backfill_for_historical_active_months(): void
    {
        // Student was active up to March 2026
        $this->student->update([
            'status' => 'Inactive',
            'discontinued_month' => '2026-03',
            'inactive_at' => now(),
        ]);

        // Backfill missing invoices for February 2026
        Artisan::call('fees:auto-generate', [
            '--month' => 'February',
            '--year' => '2026',
        ]);

        // Invoice for February must be generated because the student was enrolled in February
        $febInvoice = FeeInvoice::where('student_id', $this->student->id)
            ->where('invoice_no', 'like', 'INV-202602-%')
            ->first();

        $this->assertNotNull($febInvoice, 'Historical invoice for February must be generated for student discontinued in March.');
    }

    public function test_manual_generation_excludes_discontinued_student(): void
    {
        $this->student->update([
            'status' => 'Inactive',
            'discontinued_month' => '2026-03',
            'inactive_at' => now(),
        ]);

        // Attempt manual generate for April 2026
        $response = $this->post(route('fees.invoice.generate'), [
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session->id,
            'class_id' => $this->class->id,
            'fee_month' => 'April',
            'due_date' => '2026-04-10',
        ]);

        $aprilInvoice = FeeInvoice::where('student_id', $this->student->id)
            ->where('invoice_no', 'like', 'INV-202604-%')
            ->first();

        $this->assertNull($aprilInvoice);
    }

    public function test_financial_reports_and_due_queries_exclude_cancelled_invoices(): void
    {
        // Active student with valid invoice (Gross 1000, Discount 100, Net 900, Due 900)
        $studentAttributes = collect($this->student->toArray())->except(['id', 'student_identity'])->all();
        $activeStudent = Student::create(array_merge($studentAttributes, [
            'student_identity' => 'STD-ACTIVE-99',
            'status' => 'Active',
        ]));

        $activeInv = FeeInvoice::create([
            'invoice_no' => 'INV-202604-0099',
            'student_id' => $activeStudent->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 1000.00,
            'discount' => 100.00,
            'net_amount' => 900.00,
            'paid_amount' => 0.00,
            'due_amount' => 900.00,
            'status' => 'Unpaid',
            'due_date' => '2026-04-10',
            'user_id' => $this->adminUser->id,
        ]);

        // Cancelled invoice (Gross 1000, Discount 100, Net 900, Due 0.00, status Cancelled)
        $cancelledInv = FeeInvoice::create([
            'invoice_no' => 'INV-202604-0010',
            'student_id' => $this->student->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'amount' => 1000.00,
            'discount' => 100.00,
            'net_amount' => 900.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
            'due_date' => '2026-04-10',
            'user_id' => $this->adminUser->id,
        ]);

        // Test Financial Report Statement
        $response = $this->get(route('accounts.reports.index', [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
        ]));

        $response->assertOk();

        // View data assertions: Cancelled invoice must be strictly excluded from billed metrics
        $this->assertEquals(1000.00, (float) $response->viewData('totalBilledGross'));
        $this->assertEquals(100.00, (float) $response->viewData('totalBilledDiscount'));
        $this->assertEquals(900.00, (float) $response->viewData('totalBilledNet'));
        $this->assertEquals(900.00, (float) $response->viewData('totalOutstandingDue'));

        // Test FeeReportController dues query
        $duesResponse = $this->get(route('fees.reports.index', [
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
        ]));
        $duesResponse->assertOk();

        $duesList = $duesResponse->viewData('dues');
        $this->assertCount(1, $duesList);
        $this->assertEquals($activeInv->id, $duesList->first()->id);
    }

    public function test_reactivation_preserves_historical_logs_and_resumes_active_status(): void
    {
        // 1. Discontinue student
        $this->student->update([
            'status' => 'Inactive',
            'discontinued_month' => '2026-03',
            'inactive_at' => now(),
            'inactive_reason' => 'Left school',
        ]);

        StudentStatusLog::create([
            'student_id' => $this->student->id,
            'user_id' => $this->adminUser->id,
            'previous_status' => 'Active',
            'new_status' => 'Inactive',
            'action_type' => 'discontinuation',
            'last_active_month' => '2026-03',
            'effective_date' => '2026-04-01',
            'reason' => 'Left school',
        ]);

        // 2. Reactivate student
        $response = $this->postJson(route('students.reactivate', ['id' => $this->student->id]), [
            'reason' => 'Student returned and re-enrolled',
        ]);

        $response->assertOk()
            ->assertJson(['status' => 'success', 'new_status' => 'Active']);

        $this->student->refresh();
        $this->assertEquals('Active', $this->student->status);
        $this->assertNull($this->student->inactive_at);
        $this->assertEquals('2026-03', $this->student->discontinued_month); // Historical month preserved

        // Verify that 2 status logs exist (both history items intact)
        $logs = StudentStatusLog::where('student_id', $this->student->id)->orderBy('id', 'asc')->get();
        $this->assertCount(2, $logs);
        $this->assertEquals('discontinuation', $logs[0]->action_type);
        $this->assertEquals('reactivation', $logs[1]->action_type);
        $this->assertEquals('Student returned and re-enrolled', $logs[1]->reason);
    }

    public function test_custom_fee_operations_ignore_cancelled_invoices(): void
    {
        $cancelledInv = $this->createInvoice('INV-202604-0050', '2026-04-10', [
            'amount' => 1000.00,
            'discount' => 100.00,
            'net_amount' => 900.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        // Save a custom fee for this student and category (e.g. 700 instead of 1000)
        $response = $this->postJson(route('students.custom-fees.store', ['id' => $this->student->id]), [
            'fee_category_id' => $this->tuitionCategory->id,
            'amount' => 700.00,
        ]);

        $response->assertOk();

        // The cancelled invoice must remain untouched
        $cancelledInv->refresh();
        $this->assertEquals('Cancelled', $cancelledInv->status);
        $this->assertEquals(0.00, (float) $cancelledInv->due_amount);
        $this->assertEquals(1000.00, (float) $cancelledInv->amount);
        $this->assertEquals(100.00, (float) $cancelledInv->discount);
        $this->assertEquals(900.00, (float) $cancelledInv->net_amount);
    }

    public function test_reconciliation_is_atomic_and_rolls_back_on_failure(): void
    {
        $invApr = $this->createInvoice('INV-202604-0001', '2026-04-10');

        // Force an exception during status log creation to verify transactional rollback
        StudentStatusLog::creating(function () {
            throw new \Exception('Simulated database error during audit logging');
        });

        $response = $this->postJson(route('students.discontinue.reconcile', ['id' => $this->student->id]), [
            'last_active_month' => '2026-03',
            'reason' => 'Test Rollback',
            'confirm' => true,
        ]);

        $response->assertStatus(500);

        StudentStatusLog::flushEventListeners();

        // Student and invoice must be completely rolled back
        $this->student->refresh();
        $this->assertEquals('Active', $this->student->status);
        $this->assertNull($this->student->discontinued_month);

        $invApr->refresh();
        $this->assertEquals('Unpaid', $invApr->status);
        $this->assertEquals(900.00, (float) $invApr->due_amount);
    }

    public function test_month_and_year_parameters_combine_into_correct_billing_period_and_handle_december_rollover(): void
    {
        // Test preview with separate month and year parameters (e.g. month=12, year=2026)
        $response = $this->getJson(route('students.discontinuation.preview', [
            'id' => $this->student->id,
            'month' => 12,
            'year' => 2026,
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals('2026-12', $data['discontinuation']['last_active_month']);
        // December to January rollover: effective inactive date is 2027-01-01
        $this->assertEquals('2027-01-01', $data['discontinuation']['effective_date']);
        $this->assertEquals('December 2026', $data['discontinuation']['last_active_month_name']);

        // Normal month preview (e.g. month=3, year=2026 -> 2026-04-01)
        $responseNormal = $this->getJson(route('students.discontinuation.preview', [
            'id' => $this->student->id,
            'month' => 3,
            'year' => 2026,
        ]));
        $responseNormal->assertOk();
        $this->assertEquals('2026-03', $responseNormal->json('discontinuation.last_active_month'));
        $this->assertEquals('2026-04-01', $responseNormal->json('discontinuation.effective_date'));
    }

    public function test_invalid_or_missing_month_year_input_is_rejected_server_side(): void
    {
        // 1. Missing both
        $resMissing = $this->getJson(route('students.discontinuation.preview', [
            'id' => $this->student->id,
        ]));
        $resMissing->assertStatus(422);

        // 2. Invalid month (> 12)
        $resInvalidMonth = $this->getJson(route('students.discontinuation.preview', [
            'id' => $this->student->id,
            'month' => 13,
            'year' => 2026,
        ]));
        $resInvalidMonth->assertStatus(422);

        // 3. Malformed format
        $resMalformed = $this->getJson(route('students.discontinuation.preview', [
            'id' => $this->student->id,
            'last_active_month' => '2026-99',
        ]));
        $resMalformed->assertStatus(422);
    }

    public function test_student_edit_submission_cannot_change_status_through_client_payload(): void
    {
        $this->assertEquals('Active', $this->student->status);

        // Attempt to change status to 'Inactive' via student edit PUT endpoint
        $response = $this->putJson(route('students.update', ['student' => $this->student->id]), [
            'student_name' => 'Rahim Ahmed Updated',
            'roll_number' => '01',
            'status' => 'Inactive', // Forged or stale status field
            'discontinued_month' => '2026-01',
            'branch_id' => $this->branch->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'session_year_id' => $this->session->id,
        ]);

        $response->assertOk();

        $this->student->refresh();
        $this->assertEquals('Rahim Ahmed Updated', $this->student->student_name);
        // Student status MUST remain Active and not be overwritten!
        $this->assertEquals('Active', $this->student->status);
        $this->assertNull($this->student->discontinued_month);
    }

    public function test_adjusting_cancelled_invoice_returns_422_and_leaves_financial_data_unchanged(): void
    {
        $cancelledInv = FeeInvoice::create([
            'invoice_no' => 'INV-202605-0099',
            'student_id' => $this->student->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'user_id' => $this->adminUser->id,
            'due_date' => '2026-05-10',
            'amount' => 1000.00,
            'discount' => 100.00,
            'net_amount' => 900.00,
            'due_amount' => 0.00,
            'paid_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        // Attempt single adjustment
        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $cancelledInv->id,
            'mode' => 'decrease',
            'amount' => 200.00,
            'scope' => 'single',
            'reason' => 'Testing Cancelled Guard',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('cancelled invoice', strtolower($response->json('message')));

        // Verify cancelled invoice data is 100% unchanged
        $cancelledInv->refresh();
        $this->assertEquals('Cancelled', $cancelledInv->status);
        $this->assertEquals(1000.00, (float) $cancelledInv->amount);
        $this->assertEquals(100.00, (float) $cancelledInv->discount);
        $this->assertEquals(900.00, (float) $cancelledInv->net_amount);
        $this->assertEquals(0.00, (float) $cancelledInv->due_amount);
        $this->assertEquals(0.00, (float) $cancelledInv->paid_amount);
    }

    public function test_fee_adjustment_for_valid_unpaid_and_partial_invoices_works(): void
    {
        $unpaidInv = $this->createInvoice('INV-202604-0010', '2026-04-10');

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $unpaidInv->id,
            'mode' => 'decrease',
            'amount' => 200.00,
            'scope' => 'single',
            'reason' => 'Valid Unpaid Discount',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $unpaidInv->refresh();
        $this->assertEquals(300.00, (float) $unpaidInv->discount);
        $this->assertEquals(700.00, (float) $unpaidInv->net_amount);
        $this->assertEquals(700.00, (float) $unpaidInv->due_amount);
        $this->assertEquals('Unpaid', $unpaidInv->status);
    }

    public function test_quick_toggle_on_active_student_is_rejected_to_prevent_bypassing_reconciliation(): void
    {
        $this->assertEquals('Active', $this->student->status);

        $response = $this->postJson(route('students.toggle-status', ['id' => $this->student->id]));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'requires_reconciliation' => true,
        ]);
        $this->assertStringContainsString('discontinuation & fee reconciliation', strtolower($response->json('message')));

        $this->student->refresh();
        $this->assertEquals('Active', $this->student->status);
    }

    public function test_quick_toggle_on_inactive_student_safely_reactivates_them(): void
    {
        $this->student->update([
            'status' => 'Inactive',
            'discontinued_month' => '2026-03',
            'inactive_at' => now(),
        ]);

        $response = $this->postJson(route('students.toggle-status', ['id' => $this->student->id]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'new_status' => 'Active',
        ]);

        $this->student->refresh();
        $this->assertEquals('Active', $this->student->status);
        $this->assertNull($this->student->inactive_at);
        $this->assertDatabaseHas('student_status_logs', [
            'student_id' => $this->student->id,
            'action_type' => 'reactivation',
            'new_status' => 'Active',
        ]);
    }

    public function test_migration_down_safely_aborts_rollback_if_cancelled_invoices_exist(): void
    {
        FeeInvoice::create([
            'invoice_no' => 'INV-202606-0099',
            'student_id' => $this->student->id,
            'fee_setup_id' => $this->tuitionSetup->id,
            'user_id' => $this->adminUser->id,
            'due_date' => '2026-06-10',
            'amount' => 1000.00,
            'discount' => 100.00,
            'net_amount' => 900.00,
            'due_amount' => 0.00,
            'paid_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        $migration = require database_path('migrations/2026_10_17_100001_add_discontinuation_to_students_and_invoices_table.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot rollback migration");

        $migration->down();
    }

    public function test_inactive_student_with_unpaid_and_partial_invoices_retains_exact_balances_across_reports_and_collection(): void
    {
        // 1. Paid historical invoice (January)
        $paidInv = $this->createInvoice('INV-202601-0010', '2026-01-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0.00,
            'status' => 'Paid',
        ]);
        $receipt = FeePayment::create([
            'receipt_no' => 'REC-20260110-1001',
            'fee_invoice_id' => $paidInv->id,
            'student_id' => $this->student->id,
            'paid_amount' => 1000.00,
            'payment_date' => '2026-01-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        // 2. Partial historical invoice (February)
        $partialInv = $this->createInvoice('INV-202602-0010', '2026-02-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 400.00,
            'due_amount' => 600.00,
            'status' => 'Partial',
        ]);
        FeePayment::create([
            'receipt_no' => 'REC-20260210-1002',
            'fee_invoice_id' => $partialInv->id,
            'student_id' => $this->student->id,
            'paid_amount' => 400.00,
            'payment_date' => '2026-02-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        // 3. Unpaid historical invoice (March)
        $unpaidMarch = $this->createInvoice('INV-202603-0010', '2026-03-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 1000.00,
            'status' => 'Unpaid',
        ]);

        // 4. Post-discontinuation invoice (April)
        $postApril = $this->createInvoice('INV-202604-0010', '2026-04-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 1000.00,
            'status' => 'Unpaid',
        ]);

        // Discontinue student effective through March 2026
        $discontinueRes = $this->postJson(route('students.discontinue.reconcile', ['id' => $this->student->id]), [
            'month' => 3,
            'year' => 2026,
            'last_active_month' => '2026-03',
            'confirm' => true,
        ]);
        $discontinueRes->assertOk();

        $this->student->refresh();
        $this->assertEquals('Inactive', $this->student->status);
        $this->assertEquals('2026-03', $this->student->discontinued_month);

        // Verify post-April invoice cancelled and others unchanged
        $postApril->refresh();
        $this->assertEquals('Cancelled', $postApril->status);
        $this->assertEquals(0.00, (float) $postApril->due_amount);

        $paidInv->refresh();
        $this->assertEquals('Paid', $paidInv->status);
        $this->assertEquals(0.00, (float) $paidInv->due_amount);
        $this->assertEquals(1000.00, (float) $paidInv->paid_amount);

        $partialInv->refresh();
        $this->assertEquals('Partial', $partialInv->status);
        $this->assertEquals(600.00, (float) $partialInv->due_amount);
        $this->assertEquals(400.00, (float) $partialInv->paid_amount);

        $unpaidMarch->refresh();
        $this->assertEquals('Unpaid', $unpaidMarch->status);
        $this->assertEquals(1000.00, (float) $unpaidMarch->due_amount);

        // A. Verify Fee Report includes the exact historical dues (600 + 1000 = 1600) and excludes Cancelled
        $reportRes = $this->get(route('fees.reports.index', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-01',
        ]));
        $reportRes->assertOk();
        $dues = $reportRes->viewData('dues');
        $studentDues = $dues->where('student_id', $this->student->id);
        $this->assertEquals(2, $studentDues->count()); // February Partial + March Unpaid
        $this->assertEquals(1600.00, (float) $studentDues->sum('due_amount'));
        $this->assertFalse($studentDues->contains('status', 'Cancelled'));

        // B. Verify Financial Report Accounts Receivable excludes Cancelled and includes Inactive student dues
        $finRes = $this->get(route('accounts.reports.index', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-01',
        ]));
        $finRes->assertOk();
        $this->assertEquals(1600.00, (float) $finRes->viewData('totalOutstandingDue'));

        // C. Verify Fee Collection screen finds the inactive student and lists their pending dues
        $collectionRes = $this->get(route('fees.collection.index', [
            'student_identity' => $this->student->student_identity,
        ]));
        $collectionRes->assertOk();
        $collectionInvoices = $collectionRes->viewData('invoices');
        $this->assertEquals(2, $collectionInvoices->count());
        $this->assertEquals(1600.00, (float) $collectionInvoices->sum('due_amount'));

        // D. Verify paying an invoice for the inactive student works correctly
        $payRes = $this->post(route('fees.collection.store'), [
            'invoice_id' => $unpaidMarch->id,
            'pay_amount' => 1000.00,
            'payment_method' => 'Cash',
        ]);
        $payRes->assertRedirect();

        $unpaidMarch->refresh();
        $this->assertEquals('Paid', $unpaidMarch->status);
        $this->assertEquals(0.00, (float) $unpaidMarch->due_amount);
        $this->assertEquals(1000.00, (float) $unpaidMarch->paid_amount);

        // E. Verify paying a Cancelled invoice is strictly REJECTED
        $badPayRes = $this->post(route('fees.collection.store'), [
            'invoice_id' => $postApril->id,
            'pay_amount' => 100.00,
            'payment_method' => 'Cash',
        ]);
        $badPayRes->assertSessionHas('error');
        $postApril->refresh();
        $this->assertEquals('Cancelled', $postApril->status);
        $this->assertEquals(0.00, (float) $postApril->due_amount);
        $this->assertEquals(0.00, (float) $postApril->paid_amount);
    }

    public function test_reactivation_does_not_clear_historical_dues_and_does_not_restore_cancelled_invoices(): void
    {
        // 1. Partial historical invoice (February, due: 600)
        $partialInv = $this->createInvoice('INV-202602-0020', '2026-02-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 400.00,
            'due_amount' => 600.00,
            'status' => 'Partial',
        ]);

        // 2. Post-discontinuation invoice (April, Cancelled)
        $postApril = $this->createInvoice('INV-202604-0020', '2026-04-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        $this->student->update([
            'status' => 'Inactive',
            'discontinued_month' => '2026-03',
            'inactive_at' => now(),
        ]);

        // Reactivate student
        $reactivateRes = $this->postJson(route('students.reactivate', ['id' => $this->student->id]), [
            'reason' => 'Student re-enrolled for academic year',
        ]);
        $reactivateRes->assertOk();
        $reactivateRes->assertJson(['status' => 'success', 'new_status' => 'Active']);

        $this->student->refresh();
        $this->assertEquals('Active', $this->student->status);
        $this->assertNull($this->student->inactive_at);

        // Historical partial invoice retains exact remaining balance
        $partialInv->refresh();
        $this->assertEquals('Partial', $partialInv->status);
        $this->assertEquals(600.00, (float) $partialInv->due_amount);
        $this->assertEquals(400.00, (float) $partialInv->paid_amount);

        // Cancelled invoice is NEVER restored to Unpaid
        $postApril->refresh();
        $this->assertEquals('Cancelled', $postApril->status);
        $this->assertEquals(0.00, (float) $postApril->due_amount);
        $this->assertEquals(0.00, (float) $postApril->paid_amount);
    }

    public function test_student_profile_show_api_returns_accurate_invoice_data(): void
    {
        $paidInv = $this->createInvoice('INV-202601-0030', '2026-01-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0.00,
            'status' => 'Paid',
        ]);
        $cancelledInv = $this->createInvoice('INV-202604-0030', '2026-04-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        $res = $this->getJson("/ajax/students/{$this->student->id}");
        $res->assertOk();
        $invoices = $res->json('invoices');

        $this->assertCount(2, $invoices);
        $statuses = collect($invoices)->pluck('status')->all();
        $this->assertContains('Paid', $statuses);
        $this->assertContains('Cancelled', $statuses);

        $cancelledItem = collect($invoices)->firstWhere('status', 'Cancelled');
        $this->assertEquals(0.0, (float)$cancelledItem['due_amount']);
    }

    public function test_student_profile_view_renders_restore_modal_and_controls_properly(): void
    {
        $res = $this->get("/student/view/{$this->student->id}");
        $res->assertOk();
        $res->assertSee('id="restoreInvoiceModal"', false);
        $res->assertSee('id="restore_reason_input"', false);
        $res->assertSee('id="confirm_restore_btn"', false);
        $res->assertSee('openRestoreInvoiceModal', false);
        $res->assertSee('window.canRestoreInvoices = true', false);
    }

    public function test_mistakenly_cancelled_unpaid_invoice_can_be_restored_with_exact_financial_integrity(): void
    {
        $cancelledInv = $this->createInvoice('INV-202610-0040', '2026-10-10', [
            'amount' => 1200.00,
            'discount' => 200.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        $res = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => 'Student was discontinued by mistake; restoring October fee invoice',
            'confirm' => true,
            'student_id' => $this->student->id,
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('invoice.status', 'Unpaid');
        $this->assertEquals(1000.00, (float) $res->json('invoice.due_amount'));
        $this->assertEquals(1200.00, (float) $res->json('invoice.amount'));
        $this->assertEquals(200.00, (float) $res->json('invoice.discount'));
        $this->assertEquals(1000.00, (float) $res->json('invoice.net_amount'));

        $cancelledInv->refresh();
        $this->assertEquals('Unpaid', $cancelledInv->status);
        $this->assertEquals(1200.00, (float) $cancelledInv->amount);
        $this->assertEquals(200.00, (float) $cancelledInv->discount);
        $this->assertEquals(1000.00, (float) $cancelledInv->net_amount);
        $this->assertEquals(0.00, (float) $cancelledInv->paid_amount);
        $this->assertEquals(1000.00, (float) $cancelledInv->due_amount);
    }

    public function test_cancelled_invoice_with_partial_payment_recalculates_remaining_due_and_partial_status(): void
    {
        $partialInv = $this->createInvoice('INV-202610-0041', '2026-10-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 400.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-20261010-0041',
            'fee_invoice_id' => $partialInv->id,
            'student_id' => $this->student->id,
            'paid_amount' => 400.00,
            'payment_date' => '2026-10-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $res = $this->postJson(route('fees.invoice.restore', $partialInv->id), [
            'reason' => 'Restoring partial invoice after reconciliation audit',
            'confirm' => true,
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('invoice.status', 'Partial');
        $this->assertEquals(600.00, (float) $res->json('invoice.due_amount'));
        $this->assertEquals(400.00, (float) $res->json('invoice.paid_amount'));

        $partialInv->refresh();
        $this->assertEquals('Partial', $partialInv->status);
        $this->assertEquals(400.00, (float) $partialInv->paid_amount);
        $this->assertEquals(600.00, (float) $partialInv->due_amount);
    }

    public function test_cancelled_invoice_with_full_payment_restores_to_paid_status(): void
    {
        $paidInv = $this->createInvoice('INV-202610-0042', '2026-10-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-20261010-0042',
            'fee_invoice_id' => $paidInv->id,
            'student_id' => $this->student->id,
            'paid_amount' => 1000.00,
            'payment_date' => '2026-10-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $res = $this->postJson(route('fees.invoice.restore', $paidInv->id), [
            'reason' => 'Restoring fully paid invoice',
            'confirm' => true,
        ]);

        $res->assertOk();
        $res->assertJsonPath('invoice.status', 'Paid');
        $this->assertEquals(0.00, (float) $res->json('invoice.due_amount'));

        $paidInv->refresh();
        $this->assertEquals('Paid', $paidInv->status);
        $this->assertEquals(0.00, (float) $paidInv->due_amount);
        $this->assertEquals(1000.00, (float) $paidInv->paid_amount);
    }

    public function test_restored_invoice_dues_immediately_reflect_in_fee_collection_and_financial_reports(): void
    {
        $cancelledInv = $this->createInvoice('INV-202611-0043', '2026-11-10', [
            'amount' => 1000.00,
            'discount' => 100.00,
            'net_amount' => 900.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        // Before restore: report dues do NOT include cancelledInv
        $beforeRes = $this->get(route('fees.reports.index', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $beforeRes->assertOk();
        $duesBefore = $beforeRes->viewData('dues')->where('id', $cancelledInv->id);
        $this->assertCount(0, $duesBefore);

        // Restore invoice
        $restoreRes = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => 'Restoring valid November tuition fee',
            'confirm' => true,
        ]);
        $restoreRes->assertOk();

        // After restore: Fee Reports immediately lists 900 due
        $afterRes = $this->get(route('fees.reports.index', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $afterRes->assertOk();
        $duesAfter = $afterRes->viewData('dues')->where('id', $cancelledInv->id);
        $this->assertCount(1, $duesAfter);
        $this->assertEquals(900.00, (float) $duesAfter->first()->due_amount);

        // Accounts Financial Report immediately accounts for 900 outstanding receivable
        $finRes = $this->get(route('accounts.reports.index', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $finRes->assertOk();
        $this->assertEquals(900.00, (float) $finRes->viewData('totalOutstandingDue'));

        // Fee Collection screen lists restored invoice for collection
        $collectRes = $this->get(route('fees.collection.index', [
            'student_identity' => $this->student->student_identity,
        ]));
        $collectRes->assertOk();
        $collectRes->assertSee($cancelledInv->invoice_no);
    }

    public function test_reactivation_alone_never_restores_any_invoice(): void
    {
        $this->student->update(['status' => 'Inactive', 'discontinued_month' => '2026-03']);

        $cancelledInv = $this->createInvoice('INV-202610-0044', '2026-10-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        $reactivateRes = $this->postJson(route('students.reactivate', $this->student->id), [
            'reason' => 'Student reactivated for new term',
        ]);
        $reactivateRes->assertOk();

        $this->student->refresh();
        $this->assertEquals('Active', $this->student->status);

        $cancelledInv->refresh();
        $this->assertEquals('Cancelled', $cancelledInv->status);
        $this->assertEquals(0.00, (float) $cancelledInv->due_amount);
    }

    public function test_duplicate_invoice_for_same_period_and_fee_type_blocks_unsafe_restoration(): void
    {
        // 1. Existing Cancelled invoice for 2026-10
        $cancelledInv = $this->createInvoice('INV-202610-0045', '2026-10-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        // 2. Active invoice generated/existing for same student, same setup, same 2026-10 period
        $activeInv = $this->createInvoice('INV-202610-0046', '2026-10-15', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 1000.00,
            'status' => 'Unpaid',
        ]);

        // 3. Attempting to restore the cancelled invoice must be rejected to prevent duplicate billing
        $restoreRes = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => 'Attempting duplicate restoration',
            'confirm' => true,
        ]);

        $restoreRes->assertStatus(422);
        $restoreRes->assertJsonPath('success', false);
        $this->assertStringContainsString('An active invoice', $restoreRes->json('message'));
        $this->assertStringContainsString('already exists', $restoreRes->json('message'));

        $cancelledInv->refresh();
        $this->assertEquals('Cancelled', $cancelledInv->status);
        $this->assertEquals(0.00, (float) $cancelledInv->due_amount);
    }

    public function test_inconsistent_payment_records_block_restoration_for_manual_review(): void
    {
        $cancelledInv = $this->createInvoice('INV-202610-0047', '2026-10-10', [
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        // Payment record of 1500 exceeds invoice net amount of 1000
        FeePayment::create([
            'receipt_no' => 'REC-20261010-0047',
            'fee_invoice_id' => $cancelledInv->id,
            'student_id' => $this->student->id,
            'paid_amount' => 1500.00,
            'payment_date' => '2026-10-10',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $res = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => 'Restoring corrupt invoice',
            'confirm' => true,
        ]);

        $res->assertStatus(422);
        $res->assertJsonPath('success', false);
        $this->assertStringContainsString('Inconsistent payment records', $res->json('message'));

        $cancelledInv->refresh();
        $this->assertEquals('Cancelled', $cancelledInv->status);
    }

    public function test_restoration_requires_reason_and_rejects_non_cancelled_invoices(): void
    {
        $unpaidInv = $this->createInvoice('INV-202610-0048', '2026-10-10', [
            'amount' => 1000.00,
            'net_amount' => 1000.00,
            'due_amount' => 1000.00,
            'status' => 'Unpaid',
        ]);

        // A. Attempting to restore already Unpaid invoice
        $resNonCancelled = $this->postJson(route('fees.invoice.restore', $unpaidInv->id), [
            'reason' => 'Already active invoice',
            'confirm' => true,
        ]);
        $resNonCancelled->assertStatus(422);
        $this->assertStringContainsString('Only cancelled invoices can be restored', $resNonCancelled->json('message'));

        // B. Attempting to restore with missing or too-short reason
        $cancelledInv = $this->createInvoice('INV-202610-0049', '2026-10-10', [
            'status' => 'Cancelled',
            'due_amount' => 0.00,
        ]);
        $resNoReason = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => '',
            'confirm' => true,
        ]);
        $resNoReason->assertStatus(422);
        $resNoReason->assertJsonValidationErrors(['reason']);
    }

    public function test_unauthenticated_and_unauthorized_users_are_forbidden_from_restoring_invoices(): void
    {
        $cancelledInv = $this->createInvoice('INV-202610-0050', '2026-10-10', [
            'status' => 'Cancelled',
            'due_amount' => 0.00,
        ]);

        // 1. Unauthenticated (Guest)
        auth()->logout();
        $guestRes = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => 'Unauthorized restoration attempt',
            'confirm' => true,
        ]);
        $this->assertTrue(in_array($guestRes->status(), [401, 302]));

        // 2. Unauthorized role (Student/Viewer)
        $studentUser = User::create([
            'name' => 'Regular Student',
            'email' => 'student_regular@macsschool.com',
            'password' => bcrypt('secret'),
        ]);
        $studentRole = \HasinHayder\Tyro\Models\Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $studentUser->assignRole($studentRole);

        $this->actingAs($studentUser);
        $unauthRes = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => 'Unauthorized student role',
            'confirm' => true,
        ]);
        $this->assertTrue(in_array($unauthRes->status(), [403, 302]));
    }

    public function test_restoration_records_audit_trail_in_fee_adjustment_and_student_status_logs(): void
    {
        $cancelledInv = $this->createInvoice('INV-202610-0051', '2026-10-10', [
            'amount' => 1500.00,
            'discount' => 300.00,
            'net_amount' => 1200.00,
            'paid_amount' => 0.00,
            'due_amount' => 0.00,
            'status' => 'Cancelled',
        ]);

        $res = $this->postJson(route('fees.invoice.restore', $cancelledInv->id), [
            'reason' => 'Audited reinstatement for 2026 academic term',
            'confirm' => true,
        ]);
        $res->assertOk();

        // 1. FeeAdjustmentLog check
        $adjustmentLog = \App\Models\FeeAdjustmentLog::where('fee_invoice_id', $cancelledInv->id)
            ->latest()
            ->first();

        $this->assertNotNull($adjustmentLog);
        $this->assertEquals($this->adminUser->id, $adjustmentLog->user_id);
        $this->assertEquals($this->student->id, $adjustmentLog->student_id);
        $this->assertEquals(0.00, (float) $adjustmentLog->previous_due_amount);
        $this->assertEquals(1200.00, (float) $adjustmentLog->new_due_amount);
        $this->assertEquals(1200.00, (float) $adjustmentLog->new_net_amount);
        $this->assertStringContainsString('Audited reinstatement for 2026 academic term', $adjustmentLog->reason);

        // 2. StudentStatusLog check
        $statusLog = \App\Models\StudentStatusLog::where('student_id', $this->student->id)
            ->where('action_type', 'invoice_restoration')
            ->latest()
            ->first();

        $this->assertNotNull($statusLog);
        $this->assertEquals($this->adminUser->id, $statusLog->user_id);
        $this->assertEquals(1200.00, (float) $statusLog->reconciled_amount);
        $this->assertContains($cancelledInv->id, $statusLog->reconciled_invoice_ids);
        $this->assertStringContainsString('Audited reinstatement', $statusLog->reason);
    }
}

