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
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeeInvoiceAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Student $student;
    protected FeeSetup $feeSetup;
    protected FeeCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_fees@macsschool.com'],
            ['name' => 'Admin Fees', 'password' => bcrypt('secret')]
        );

        $role = \HasinHayder\Tyro\Models\Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->adminUser->assignRole($role);

        $this->actingAs($this->adminUser);

        $branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main', 'user_id' => $this->adminUser->id]);
        $session = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026-2027', 'user_id' => $this->adminUser->id]);
        $class = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Ten', 'user_id' => $this->adminUser->id]);
        $section = Section::firstOrCreate(['id' => 1], ['section_name' => 'A', 'user_id' => $this->adminUser->id]);

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
            'branch_id'              => $branch->id,
            'session_year_id'        => $session->id,
            'class_id'               => $class->id,
            'section_id'             => $section->id,
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

        $this->category = FeeCategory::create([
            'name'    => 'Monthly Tuition Fee',
            'status'  => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->feeSetup = FeeSetup::create([
            'fee_category_id' => $this->category->id,
            'branch_id'       => $branch->id,
            'session_year_id' => $session->id,
            'class_id'        => $class->id,
            'fee_month'       => 'Monthly',
            'amount'          => 1000.00,
            'user_id'         => $this->adminUser->id,
        ]);
    }

    protected function createInvoice(array $attributes = []): FeeInvoice
    {
        return FeeInvoice::create(array_merge([
            'invoice_no'   => 'INV-202601-' . rand(1000, 9999),
            'student_id'   => $this->student->id,
            'fee_setup_id' => $this->feeSetup->id,
            'amount'       => 1000.00,
            'discount'     => 0.00,
            'net_amount'   => 1000.00,
            'paid_amount'  => 0.00,
            'due_amount'   => 1000.00,
            'status'       => 'Unpaid',
            'due_date'     => '2026-01-10',
            'user_id'      => $this->adminUser->id,
        ], $attributes));
    }

    public function test_operator_can_increase_invoice_fee_for_single_month(): void
    {
        $invoice = $this->createInvoice(['amount' => 1000.00, 'discount' => 0.00, 'net_amount' => 1000.00, 'due_amount' => 1000.00]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'increase',
            'amount'     => 200.00,
            'scope'      => 'single',
            'reason'     => 'Extra coaching fee',
        ]);

        $response->assertOk()
                 ->assertJson(['success' => true]);

        $invoice->refresh();
        $this->assertEquals(1200.00, (float) $invoice->net_amount);
        $this->assertEquals(1200.00, (float) $invoice->due_amount);
        $this->assertEquals(1200.00, (float) $invoice->amount);
        $this->assertEquals(0.00, (float) $invoice->discount);
        $this->assertEquals('Unpaid', $invoice->status);

        $this->assertDatabaseHas('fee_adjustment_logs', [
            'fee_invoice_id' => $invoice->id,
            'mode'           => 'increase',
            'scope'          => 'single',
            'adjustment_amount' => 200.00,
            'previous_net_amount' => 1000.00,
            'new_net_amount' => 1200.00,
            'reason'         => 'Extra coaching fee',
        ]);
    }

    public function test_invoice_with_existing_discount_preserves_discount_on_increase(): void
    {
        // Invoice with 200 discount: gross 1000, discount 200, net 800
        $invoice = $this->createInvoice([
            'amount'     => 1000.00,
            'discount'   => 200.00,
            'net_amount' => 800.00,
            'due_amount' => 800.00,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'increase',
            'amount'     => 150.00,
            'scope'      => 'single',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $invoice->refresh();
        // New net = 800 + 150 = 950. Preserved discount = 200. New gross = 950 + 200 = 1150.
        $this->assertEquals(950.00, (float) $invoice->net_amount);
        $this->assertEquals(200.00, (float) $invoice->discount);
        $this->assertEquals(1150.00, (float) $invoice->amount);
        $this->assertEquals(950.00, (float) $invoice->due_amount);
        $this->assertEquals(950.00, round((float)$invoice->amount - (float)$invoice->discount, 2));
    }

    public function test_operator_can_decrease_invoice_fee_and_discount_increases(): void
    {
        $invoice = $this->createInvoice([
            'amount'     => 1000.00,
            'discount'   => 0.00,
            'net_amount' => 1000.00,
            'due_amount' => 1000.00,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'decrease',
            'amount'     => 300.00,
            'scope'      => 'single',
            'reason'     => 'Principal waiver',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $invoice->refresh();
        // Gross amount remains unchanged (1000), discount increases to 300, net becomes 700
        $this->assertEquals(1000.00, (float) $invoice->amount);
        $this->assertEquals(300.00, (float) $invoice->discount);
        $this->assertEquals(700.00, (float) $invoice->net_amount);
        $this->assertEquals(700.00, (float) $invoice->due_amount);
        $this->assertEquals('Unpaid', $invoice->status);
    }

    public function test_operator_can_set_exact_net_fee_amount(): void
    {
        $invoice = $this->createInvoice([
            'amount'     => 1000.00,
            'discount'   => 0.00,
            'net_amount' => 1000.00,
            'due_amount' => 1000.00,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'set_amount',
            'amount'     => 850.00,
            'scope'      => 'single',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->amount);
        $this->assertEquals(150.00, (float) $invoice->discount);
        $this->assertEquals(850.00, (float) $invoice->net_amount);
        $this->assertEquals(850.00, (float) $invoice->due_amount);
    }

    public function test_operator_can_set_exact_above_gross_amount(): void
    {
        $invoice = $this->createInvoice([
            'amount'     => 1000.00,
            'discount'   => 0.00,
            'net_amount' => 1000.00,
            'due_amount' => 1000.00,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'set_amount',
            'amount'     => 1300.00,
            'scope'      => 'single',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $invoice->refresh();
        $this->assertEquals(1300.00, (float) $invoice->amount);
        $this->assertEquals(0.00, (float) $invoice->discount);
        $this->assertEquals(1300.00, (float) $invoice->net_amount);
        $this->assertEquals(1300.00, (float) $invoice->due_amount);
    }

    public function test_reduction_below_paid_amount_is_rejected_with_422(): void
    {
        // Invoice with 500 paid, net 1500, due 1000
        $invoice = $this->createInvoice([
            'amount'      => 1500.00,
            'discount'    => 0.00,
            'net_amount'  => 1500.00,
            'paid_amount' => 500.00,
            'due_amount'  => 1000.00,
            'status'      => 'Partial',
        ]);

        // Attempting to decrease by 1100 (net would be 400 < 500 paid)
        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'decrease',
            'amount'     => 1100.00,
            'scope'      => 'single',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['success' => false]);

        $invoice->refresh();
        // Values must remain completely unchanged (never silently clamped to 500)
        $this->assertEquals(1500.00, (float) $invoice->net_amount);
        $this->assertEquals(1000.00, (float) $invoice->due_amount);
        $this->assertEquals(500.00, (float) $invoice->paid_amount);
    }

    public function test_set_exact_below_paid_amount_is_rejected_with_422(): void
    {
        $invoice = $this->createInvoice([
            'amount'      => 1500.00,
            'discount'    => 0.00,
            'net_amount'  => 1500.00,
            'paid_amount' => 500.00,
            'due_amount'  => 1000.00,
            'status'      => 'Partial',
        ]);

        // Attempting to set exact 400 (< 500 paid)
        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'set_amount',
            'amount'     => 400.00,
            'scope'      => 'single',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['success' => false]);

        $invoice->refresh();
        $this->assertEquals(1500.00, (float) $invoice->net_amount);
    }

    public function test_negative_adjustment_amount_is_rejected(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'increase',
            'amount'     => -50.00,
            'scope'      => 'single',
        ]);

        $response->assertStatus(422);
    }

    public function test_fee_payments_records_remain_completely_untouched(): void
    {
        $invoice = $this->createInvoice([
            'amount'      => 1000.00,
            'net_amount'  => 1000.00,
            'paid_amount' => 400.00,
            'due_amount'  => 600.00,
            'status'      => 'Partial',
        ]);

        $payment = FeePayment::create([
            'receipt_no'     => 'REC-20260101-1111',
            'fee_invoice_id' => $invoice->id,
            'student_id'     => $this->student->id,
            'paid_amount'    => 400.00,
            'payment_date'   => '2026-01-05',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);

        // Adjust fee by increasing 100
        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'increase',
            'amount'     => 100.00,
            'scope'      => 'single',
        ]);

        $response->assertOk();

        // Payment record must be completely identical
        $payment->refresh();
        $this->assertEquals(400.00, (float) $payment->paid_amount);
        $this->assertEquals('REC-20260101-1111', $payment->receipt_no);
        $this->assertEquals('2026-01-05', $payment->payment_date);
    }

    public function test_this_month_only_does_not_affect_other_months_invoices(): void
    {
        $invoiceJan = $this->createInvoice(['invoice_no' => 'INV-202601-0001', 'net_amount' => 1000.00, 'due_amount' => 1000.00]);
        $invoiceFeb = $this->createInvoice(['invoice_no' => 'INV-202602-0001', 'net_amount' => 1000.00, 'due_amount' => 1000.00]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoiceJan->id,
            'mode'       => 'increase',
            'amount'     => 300.00,
            'scope'      => 'single',
        ]);

        $response->assertOk();

        $invoiceJan->refresh();
        $invoiceFeb->refresh();

        $this->assertEquals(1300.00, (float) $invoiceJan->net_amount);
        $this->assertEquals(1000.00, (float) $invoiceFeb->net_amount); // completely unchanged!
    }

    public function test_this_month_only_does_not_affect_custom_fee_table(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'increase',
            'amount'     => 250.00,
            'scope'      => 'single',
        ]);

        $response->assertOk();
        $this->assertEquals(0, StudentCustomFee::count());
    }

    public function test_continuous_adjustment_updates_custom_fee_and_eligible_unpaid_invoices(): void
    {
        $inv1 = $this->createInvoice(['invoice_no' => 'INV-202601-0010', 'net_amount' => 1000.00, 'due_amount' => 1000.00]);
        $inv2 = $this->createInvoice(['invoice_no' => 'INV-202602-0010', 'net_amount' => 1000.00, 'due_amount' => 1000.00]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv1->id,
            'mode'       => 'set_amount',
            'amount'     => 800.00,
            'scope'      => 'continuous',
            'reason'     => 'Permanent scholarship',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        // StudentCustomFee must be updated
        $customFee = StudentCustomFee::where('student_id', $this->student->id)
            ->where('fee_category_id', $this->category->id)
            ->first();
        $this->assertNotNull($customFee);
        $this->assertEquals(800.00, (float) $customFee->amount);

        // Both invoices must be updated
        $inv1->refresh();
        $inv2->refresh();
        $this->assertEquals(800.00, (float) $inv1->net_amount);
        $this->assertEquals(800.00, (float) $inv2->net_amount);
        $this->assertEquals(800.00, (float) $inv1->due_amount);
        $this->assertEquals(800.00, (float) $inv2->due_amount);

        // 2 audit logs recorded
        $this->assertEquals(2, FeeAdjustmentLog::where('scope', 'continuous')->count());
    }

    public function test_continuous_adjustment_skips_partial_and_paid_invoices(): void
    {
        // Invoice 1: eligible zero-paid unpaid invoice
        $inv1 = $this->createInvoice(['invoice_no' => 'INV-202601-0020', 'net_amount' => 1000.00, 'due_amount' => 1000.00, 'paid_amount' => 0.00]);
        
        // Invoice 2: partially paid invoice
        $inv2 = $this->createInvoice(['invoice_no' => 'INV-202602-0020', 'net_amount' => 1000.00, 'due_amount' => 600.00, 'paid_amount' => 400.00, 'status' => 'Partial']);
        FeePayment::create([
            'receipt_no'     => 'REC-20260201-2222',
            'fee_invoice_id' => $inv2->id,
            'student_id'     => $this->student->id,
            'paid_amount'    => 400.00,
            'payment_date'   => '2026-02-05',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv1->id,
            'mode'       => 'set_amount',
            'amount'     => 750.00,
            'scope'      => 'continuous',
        ]);

        $response->assertOk();

        $inv1->refresh();
        $inv2->refresh();

        $this->assertEquals(750.00, (float) $inv1->net_amount);
        // Partially paid invoice MUST remain completely untouched!
        $this->assertEquals(1000.00, (float) $inv2->net_amount);
        $this->assertEquals(600.00, (float) $inv2->due_amount);
        $this->assertEquals(400.00, (float) $inv2->paid_amount);
        $this->assertEquals('Partial', $inv2->status);
    }

    public function test_continuous_adjustment_cannot_be_initiated_from_invoice_with_payments(): void
    {
        $inv = $this->createInvoice(['paid_amount' => 300.00, 'due_amount' => 700.00, 'status' => 'Partial']);
        FeePayment::create([
            'receipt_no'     => 'REC-20260301-3333',
            'fee_invoice_id' => $inv->id,
            'student_id'     => $this->student->id,
            'paid_amount'    => 300.00,
            'payment_date'   => '2026-03-05',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv->id,
            'mode'       => 'set_amount',
            'amount'     => 800.00,
            'scope'      => 'continuous',
        ]);

        $response->assertStatus(422)
                 ->assertJson(['success' => false]);
    }

    public function test_optional_reason_is_stored_when_supplied_and_works_when_omitted(): void
    {
        // 1. With reason
        $inv1 = $this->createInvoice();
        $res1 = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv1->id,
            'mode'       => 'increase',
            'amount'     => 50.00,
            'scope'      => 'single',
            'reason'     => 'Special reason test',
        ]);
        $res1->assertOk();
        $this->assertDatabaseHas('fee_adjustment_logs', [
            'fee_invoice_id' => $inv1->id,
            'reason'         => 'Special reason test',
        ]);

        // 2. Without reason (omitted)
        $inv2 = $this->createInvoice();
        $res2 = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv2->id,
            'mode'       => 'increase',
            'amount'     => 50.00,
            'scope'      => 'single',
        ]);
        $res2->assertOk();
        $this->assertDatabaseHas('fee_adjustment_logs', [
            'fee_invoice_id' => $inv2->id,
            'reason'         => null,
        ]);
    }

    public function test_unauthenticated_and_unauthorized_users_are_forbidden(): void
    {
        $invoice = $this->createInvoice();

        // 1. Unauthenticated (Guest)
        auth()->logout();
        $guestResponse = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'increase',
            'amount'     => 50.00,
            'scope'      => 'single',
        ]);
        // Expect 401 or redirect to login
        $this->assertTrue(in_array($guestResponse->status(), [401, 302]));

        // 2. Unauthorized role
        $viewerUser = User::create([
            'name'     => 'Viewer User',
            'email'    => 'viewer@macsschool.com',
            'password' => bcrypt('secret'),
        ]);
        $viewerRole = \HasinHayder\Tyro\Models\Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $viewerUser->assignRole($viewerRole);

        $this->actingAs($viewerUser);
        $unauthResponse = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'increase',
            'amount'     => 50.00,
            'scope'      => 'single',
        ]);
        // Expect 403 or 302 redirect by Tyro role middleware
        $this->assertTrue(in_array($unauthResponse->status(), [403, 302]));
    }

    public function test_audit_log_captures_complete_before_after_amounts_and_operator_id(): void
    {
        $invoice = $this->createInvoice([
            'amount'     => 1000.00,
            'discount'   => 100.00,
            'net_amount' => 900.00,
            'due_amount' => 900.00,
        ]);

        $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'decrease',
            'amount'     => 200.00,
            'scope'      => 'single',
            'reason'     => 'Scholarship audit check',
        ]);

        $this->assertDatabaseHas('fee_adjustment_logs', [
            'fee_invoice_id'      => $invoice->id,
            'user_id'             => $this->adminUser->id,
            'mode'                => 'decrease',
            'scope'               => 'single',
            'adjustment_amount'   => 200.00,
            'previous_amount'     => 1000.00,
            'previous_discount'   => 100.00,
            'previous_net_amount' => 900.00,
            'previous_due_amount' => 900.00,
            'new_amount'          => 1000.00,
            'new_discount'        => 300.00,
            'new_net_amount'      => 700.00,
            'new_due_amount'      => 700.00,
            'paid_amount'         => 0.00,
            'reason'              => 'Scholarship audit check',
        ]);
    }

    public function test_ajax_response_returns_authoritative_values_and_summary_totals(): void
    {
        $inv1 = $this->createInvoice(['net_amount' => 1000.00, 'due_amount' => 1000.00]);
        $inv2 = $this->createInvoice(['net_amount' => 800.00, 'due_amount' => 800.00]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv1->id,
            'mode'       => 'decrease',
            'amount'     => 200.00,
            'scope'      => 'single',
        ]);

        $response->assertOk()
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'scope',
                     'updated_invoices' => [
                         ['id', 'amount', 'discount', 'net_amount', 'due_amount', 'paid_amount', 'status']
                     ],
                     'summary' => [
                         'invoices_count',
                         'total_bill',
                         'total_due',
                         'total_discount'
                     ]
                 ]);

        $data = $response->json();
        $this->assertEquals(2, $data['summary']['invoices_count']);
        // Total bill: 800 (adjusted inv1) + 800 (inv2) = 1,600.00
        $this->assertEquals('1,600.00', $data['summary']['total_bill']);
        $this->assertEquals('1,600.00', $data['summary']['total_due']);
        $this->assertEquals('200.00', $data['summary']['total_discount']);
    }

    public function test_continuous_mode_skips_invoice_with_zero_paid_amount_when_payment_record_exists(): void
    {
        // Invoice 1: completely clean unpaid invoice (eligible)
        $inv1 = $this->createInvoice(['invoice_no' => 'INV-202601-ZERO1', 'net_amount' => 1000.00, 'due_amount' => 1000.00, 'paid_amount' => 0.00]);

        // Invoice 2: paid_amount is 0.00, but an anomaly/zero-waiver payment record exists
        $inv2 = $this->createInvoice(['invoice_no' => 'INV-202602-ZERO2', 'net_amount' => 1000.00, 'due_amount' => 1000.00, 'paid_amount' => 0.00]);
        FeePayment::create([
            'receipt_no'     => 'REC-ZERO-0001',
            'fee_invoice_id' => $inv2->id,
            'student_id'     => $this->student->id,
            'paid_amount'    => 0.00,
            'payment_date'   => '2026-02-01',
            'payment_method' => 'Cash',
            'collected_by'   => $this->adminUser->id,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv1->id,
            'mode'       => 'set_amount',
            'amount'     => 750.00,
            'scope'      => 'continuous',
            'reason'     => 'Continuous discount test',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $inv1->refresh();
        $inv2->refresh();

        // Invoice 1 MUST be updated
        $this->assertEquals(750.00, (float) $inv1->net_amount);
        $this->assertEquals(750.00, (float) $inv1->due_amount);

        // Invoice 2 MUST be skipped completely because payments()->exists() is true, even with paid_amount = 0
        $this->assertEquals(1000.00, (float) $inv2->net_amount);
        $this->assertEquals(1000.00, (float) $inv2->due_amount);
        $this->assertEquals(0.00, (float) $inv2->paid_amount);

        // Only 1 audit log created (for inv1)
        $this->assertDatabaseHas('fee_adjustment_logs', ['fee_invoice_id' => $inv1->id]);
        $this->assertDatabaseMissing('fee_adjustment_logs', ['fee_invoice_id' => $inv2->id]);
    }

    public function test_atomic_rollback_reverts_custom_fee_and_invoices_when_audit_logging_fails(): void
    {
        $inv1 = $this->createInvoice(['invoice_no' => 'INV-202601-ROLL1', 'net_amount' => 1000.00, 'due_amount' => 1000.00]);
        $inv2 = $this->createInvoice(['invoice_no' => 'INV-202602-ROLL2', 'net_amount' => 1000.00, 'due_amount' => 1000.00]);

        $this->assertEquals(0, StudentCustomFee::count());

        // Intercept audit log creation to simulate a database constraint/disk error
        FeeAdjustmentLog::creating(function () {
            throw new \Exception('Simulated database crash during audit logging');
        });

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $inv1->id,
            'mode'       => 'set_amount',
            'amount'     => 600.00,
            'scope'      => 'continuous',
        ]);

        $response->assertStatus(500)
                 ->assertJson(['success' => false]);

        // Clear the model listener for subsequent tests
        FeeAdjustmentLog::flushEventListeners();

        // Verify total rollback
        $inv1->refresh();
        $inv2->refresh();
        $this->assertEquals(1000.00, (float) $inv1->net_amount);
        $this->assertEquals(1000.00, (float) $inv2->net_amount);
        $this->assertEquals(0, StudentCustomFee::count());
        $this->assertEquals(0, FeeAdjustmentLog::count());
    }

    public function test_boundary_precision_cannot_reduce_net_below_paid_amount_by_one_cent(): void
    {
        // Invoice with net 1000.00, paid 450.50, due 549.50
        $invoice = $this->createInvoice([
            'amount'      => 1000.00,
            'discount'    => 0.00,
            'net_amount'  => 1000.00,
            'paid_amount' => 450.50,
            'due_amount'  => 549.50,
            'status'      => 'Partial',
        ]);

        // Max allowable decrease is 549.50. Reducing by 549.51 must be rejected!
        $response1 = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'decrease',
            'amount'     => 549.51,
            'scope'      => 'single',
        ]);
        $response1->assertStatus(422)->assertJson(['success' => false]);

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->net_amount);
        $this->assertEquals(549.50, (float) $invoice->due_amount);

        // Setting exact amount to 450.49 (0.01 below paid 450.50) must be rejected!
        $response2 = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'set_amount',
            'amount'     => 450.49,
            'scope'      => 'single',
        ]);
        $response2->assertStatus(422)->assertJson(['success' => false]);

        $invoice->refresh();
        $this->assertEquals(1000.00, (float) $invoice->net_amount);
        $this->assertEquals(549.50, (float) $invoice->due_amount);

        // Reducing by exactly 549.50 is allowed and clears the due balance to 0.00
        $response3 = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'decrease',
            'amount'     => 549.50,
            'scope'      => 'single',
        ]);
        $response3->assertOk()->assertJson(['success' => true]);

        $invoice->refresh();
        $this->assertEquals(450.50, (float) $invoice->net_amount);
        $this->assertEquals(0.00, (float) $invoice->due_amount);
        $this->assertEquals(549.50, (float) $invoice->discount);
        $this->assertEquals('Paid', $invoice->status);
    }

    public function test_regression_payment_records_and_custom_fees_remain_untouched_on_decrease(): void
    {
        $invoice = $this->createInvoice([
            'amount'      => 1200.00,
            'net_amount'  => 1200.00,
            'paid_amount' => 500.00,
            'due_amount'  => 700.00,
            'status'      => 'Partial',
        ]);

        $payment = FeePayment::create([
            'receipt_no'     => 'REC-REGRESSION-9999',
            'fee_invoice_id' => $invoice->id,
            'student_id'     => $this->student->id,
            'paid_amount'    => 500.00,
            'payment_date'   => '2026-01-15',
            'payment_method' => 'bKash',
            'transaction_id' => 'TRX-BKASH-7788',
            'collected_by'   => $this->adminUser->id,
        ]);

        $response = $this->postJson(route('fees.invoice.adjust'), [
            'invoice_id' => $invoice->id,
            'mode'       => 'decrease',
            'amount'     => 200.00,
            'scope'      => 'single',
        ]);

        $response->assertOk();

        // Payment attributes must be 100% intact
        $payment->refresh();
        $this->assertEquals(500.00, (float) $payment->paid_amount);
        $this->assertEquals('REC-REGRESSION-9999', $payment->receipt_no);
        $this->assertEquals('bKash', $payment->payment_method);
        $this->assertEquals('TRX-BKASH-7788', $payment->transaction_id);
        $this->assertEquals('2026-01-15', $payment->payment_date);

        // StudentCustomFee must remain completely untouched
        $this->assertEquals(0, StudentCustomFee::count());
    }
}

