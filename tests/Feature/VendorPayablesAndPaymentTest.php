<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseAuditLog;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\ExpenseVendor;
use App\Models\User;
use App\Models\VendorAuditLog;
use App\Models\VendorOpeningBalance;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use App\Services\VendorPayableService;
use Exception;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorPayablesAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Branch $branch;
    protected ExpenseCategory $category;
    protected ExpenseVendor $vendor;
    protected VendorPayableService $payableService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin User (Super Admin)
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_payable_test@macsschool.com'],
            ['name' => 'Payable Admin Tester', 'password' => bcrypt('password')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        // 2. Unauthorized Student User
        $this->unauthorizedUser = User::firstOrCreate(
            ['email' => 'student_payable_test@macsschool.com'],
            ['name' => 'Payable Student Tester', 'password' => bcrypt('password')]
        );
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->unauthorizedUser->assignRole($studentRole);

        // 3. Branch
        $this->branch = Branch::firstOrCreate(
            ['id' => 1],
            ['branch_name' => 'Pabna Main Campus', 'user_id' => $this->adminUser->id]
        );

        // 4. Expense Category
        $this->category = ExpenseCategory::create([
            'name' => 'Hardware & IT Equipment',
            'description' => 'Computers and networking items',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        // 5. Vendor
        $this->vendor = ExpenseVendor::create([
            'name' => 'Dhaka Tech Solutions',
            'company_name' => 'Dhaka Tech Ltd',
            'contact_person' => 'Kamal Hossain',
            'phone' => '01700112233',
            'address' => 'Multiplan Center, Dhaka',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->payableService = app(VendorPayableService::class);
    }

    /**
     * 1. Creating a vendor/payee and viewing its ledger.
     */
    public function test_01_can_view_vendor_ledger_and_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('accounts.payables.index'));
        $response->assertStatus(200);
        $response->assertSee('Payables & Due');

        $ledgerResponse = $this->actingAs($this->adminUser)->get(route('accounts.payables.ledger', $this->vendor->id));
        $ledgerResponse->assertStatus(200);
        $ledgerResponse->assertSee($this->vendor->name);
        $ledgerResponse->assertSee('Closing Payable Balance');
    }

    /**
     * 2. Creating a payable bill with no immediate payment (Credit / Unpaid Bill).
     */
    public function test_02_can_create_unpaid_payable_bill(): void
    {
        $payload = [
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-10',
            'due_date'            => '2026-10-25',
            'payment_type'        => 'unpaid',
            'payment_method'      => 'Credit / Payable',
            'reference_no'        => 'INV-1001',
            'items'               => [
                [
                    'item_name'           => 'Router & Cabling',
                    'expense_category_id' => $this->category->id,
                    'quantity'            => 2,
                    'unit_rate'           => 2500,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);
        $response->assertRedirect(route('accounts.expenses.index'));

        $expense = Expense::where('reference_no', 'INV-1001')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(5000.00, (float) $expense->amount);
        $this->assertEquals(0.00, (float) $expense->paid_amount);
        $this->assertEquals(5000.00, (float) $expense->due_amount);
        $this->assertEquals('Unpaid', $expense->payment_status);
        $this->assertEquals('2026-10-25', $expense->due_date->format('Y-m-d'));

        // Crucial: No cash outflow recorded for unpaid bill
        $this->assertEquals(0, VendorPayment::where('vendor_id', $this->vendor->id)->count());
    }

    /**
     * 3. Creating a fully paid expense voucher (payment_type = full).
     */
    public function test_03_can_create_fully_paid_expense_voucher(): void
    {
        $payload = [
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-12',
            'payment_type'        => 'full',
            'payment_method'      => 'Cash',
            'reference_no'        => 'INV-1002',
            'items'               => [
                [
                    'item_name'           => 'Cat6 Cable Roll',
                    'expense_category_id' => $this->category->id,
                    'quantity'            => 1,
                    'unit_rate'           => 3000,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);
        $response->assertRedirect(route('accounts.expenses.index'));

        $expense = Expense::where('reference_no', 'INV-1002')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(3000.00, (float) $expense->amount);
        $this->assertEquals(3000.00, (float) $expense->paid_amount);
        $this->assertEquals(0.00, (float) $expense->due_amount);
        $this->assertEquals('Paid', $expense->payment_status);

        // Canonical VendorPayment must be recorded
        $payment = VendorPayment::where('expense_id', $expense->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(3000.00, (float) $payment->amount);
        $this->assertEquals('Cash', $payment->payment_method);

        // Allocation must point to the expense
        $allocation = VendorPaymentAllocation::where('vendor_payment_id', $payment->id)->first();
        $this->assertNotNull($allocation);
        $this->assertEquals($expense->id, $allocation->allocatable_id);
        $this->assertEquals(3000.00, (float) $allocation->allocated_amount);
    }

    /**
     * 4. Creating a partially paid voucher at entry.
     */
    public function test_04_can_create_partially_paid_voucher(): void
    {
        $payload = [
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-14',
            'due_date'            => '2026-10-30',
            'payment_type'        => 'partial',
            'initial_paid_amount' => 4000.00,
            'payment_method'      => 'Bank',
            'reference_no'        => 'INV-1003',
            'items'               => [
                [
                    'item_name'           => 'Server UPS System',
                    'expense_category_id' => $this->category->id,
                    'quantity'            => 1,
                    'unit_rate'           => 10000,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);
        $response->assertRedirect(route('accounts.expenses.index'));

        $expense = Expense::where('reference_no', 'INV-1003')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(10000.00, (float) $expense->amount);
        $this->assertEquals(4000.00, (float) $expense->paid_amount);
        $this->assertEquals(6000.00, (float) $expense->due_amount);
        $this->assertEquals('Partially Paid', $expense->payment_status);

        // Payment record of 4,000 created
        $payment = VendorPayment::where('expense_id', $expense->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(4000.00, (float) $payment->amount);
        $this->assertEquals('Bank', $payment->payment_method);
    }

    /**
     * 5. Recording a subsequent partial payment against an existing payable.
     */
    public function test_05_can_record_subsequent_partial_payment(): void
    {
        // Create an unpaid bill for 8,000
        $expense = Expense::create([
            'voucher_no'          => 'EXP-TEST-005',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'amount'              => 8000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'reference_no'        => 'BILL-005',
            'user_id'             => $this->adminUser->id,
        ]);

        $paymentPayload = [
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-05',
            'payment_method' => 'bKash',
            'amount'         => 3500.00,
            'reference_no'   => 'TRX-BKASH-7788',
            'allocations'    => [
                [
                    'type'   => 'bill',
                    'id'     => $expense->id,
                    'amount' => 3500.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.vendor-payments.store'), $paymentPayload);
        $response->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals(3500.00, (float) $expense->paid_amount);
        $this->assertEquals(4500.00, (float) $expense->due_amount);
        $this->assertEquals('Partially Paid', $expense->payment_status);
    }

    /**
     * 6. Settling the remaining balance completely.
     */
    public function test_06_settling_remaining_balance_marks_bill_paid(): void
    {
        $expense = Expense::create([
            'voucher_no'          => 'EXP-TEST-006',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'amount'              => 4500.00,
            'paid_amount'         => 3500.00,
            'payment_status'      => 'Partially Paid',
            'payment_method'      => 'bKash',
            'user_id'             => $this->adminUser->id,
        ]);

        $this->assertEquals(1000.00, (float) $expense->due_amount);

        // Pay final 1000
        $paymentPayload = [
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-10',
            'payment_method' => 'Cash',
            'amount'         => 1000.00,
            'allocations'    => [
                [
                    'type'   => 'bill',
                    'id'     => $expense->id,
                    'amount' => 1000.00,
                ]
            ]
        ];

        $this->actingAs($this->adminUser)->post(route('accounts.vendor-payments.store'), $paymentPayload);

        $expense->refresh();
        $this->assertEquals(4500.00, (float) $expense->paid_amount);
        $this->assertEquals(0.00, (float) $expense->due_amount);
        $this->assertEquals('Paid', $expense->payment_status);
    }

    /**
     * 7. Paying multiple bills for one vendor in a single payment.
     */
    public function test_07_can_pay_multiple_bills_in_single_disbursement(): void
    {
        $bill1 = Expense::create([
            'voucher_no'          => 'EXP-MULT-1',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-02',
            'amount'              => 2000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        $bill2 = Expense::create([
            'voucher_no'          => 'EXP-MULT-2',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-03',
            'amount'              => 3000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        $combinedPayment = [
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-06',
            'payment_method' => 'Bank',
            'amount'         => 5000.00,
            'reference_no'   => 'CHQ-991100',
            'allocations'    => [
                ['type' => 'bill', 'id' => $bill1->id, 'amount' => 2000.00],
                ['type' => 'bill', 'id' => $bill2->id, 'amount' => 3000.00],
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.vendor-payments.store'), $combinedPayment);
        $response->assertSessionHas('success');

        $bill1->refresh();
        $bill2->refresh();

        $this->assertEquals('Paid', $bill1->payment_status);
        $this->assertEquals('Paid', $bill2->payment_status);
        $this->assertEquals(0.00, (float) $bill1->due_amount);
        $this->assertEquals(0.00, (float) $bill2->due_amount);
    }

    /**
     * 8. Rejecting payment amounts that exceed allocated outstanding balances.
     */
    public function test_08_rejects_overpayment_exceeding_outstanding_due(): void
    {
        $expense = Expense::create([
            'voucher_no'          => 'EXP-OVERPAY',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'amount'              => 1000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        // Attempting to pay 1,500 on a 1,000 due
        $overpayPayload = [
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-05',
            'payment_method' => 'Cash',
            'amount'         => 1500.00,
            'allocations'    => [
                ['type' => 'bill', 'id' => $expense->id, 'amount' => 1500.00],
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.vendor-payments.store'), $overpayPayload);
        $response->assertSessionHasErrors();

        $expense->refresh();
        $this->assertEquals(0.00, (float) $expense->paid_amount);
        $this->assertEquals(1000.00, (float) $expense->due_amount);
    }

    /**
     * 9. Creating an opening payable balance (Golden Rule 1: Opening Due != New Expense).
     */
    public function test_09_opening_balance_creates_liability_without_inflating_expenses(): void
    {
        $expensesCountBefore = Expense::count();

        $payload = [
            'vendor_id'       => $this->vendor->id,
            'opening_balance' => 15000.00,
            'effective_date'  => '2026-01-01',
            'reference_no'    => 'OLD-BILL-2025',
            'reference_date'  => '2025-12-15',
            'note'            => 'Previous year outstanding server setup balance',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.payables.opening_balance.store'), $payload);
        $response->assertSessionHas('success');

        $ob = VendorOpeningBalance::where('reference_no', 'OLD-BILL-2025')->first();
        $this->assertNotNull($ob);
        $this->assertEquals(15000.00, (float) $ob->opening_balance);
        $this->assertEquals(0.00, (float) $ob->paid_amount);
        $this->assertEquals(15000.00, (float) $ob->due_amount);
        $this->assertEquals('Unpaid', $ob->status);

        // Verification of Rule 1: No new Expense was created
        $this->assertEquals($expensesCountBefore, Expense::count());

        // Audit log created
        $audit = VendorAuditLog::where('vendor_id', $this->vendor->id)->where('action', 'opening_balance_created')->first();
        $this->assertNotNull($audit);
    }

    /**
     * 10. Recording a payment against an opening balance (Golden Rule 2: Payment != Expense).
     */
    public function test_10_payment_against_opening_balance_reduces_liability_without_creating_expense(): void
    {
        $ob = VendorOpeningBalance::create([
            'vendor_id'       => $this->vendor->id,
            'branch_id'       => $this->branch->id,
            'opening_balance' => 6000.00,
            'paid_amount'     => 0.00,
            'effective_date'  => '2026-01-01',
            'reference_no'    => 'OB-TEST-10',
            'status'          => 'Unpaid',
            'user_id'         => $this->adminUser->id,
        ]);

        $expensesCountBefore = Expense::count();

        $paymentPayload = [
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-15',
            'payment_method' => 'Bank',
            'amount'         => 6000.00,
            'reference_no'   => 'CHQ-OB-SETTLE',
            'allocations'    => [
                ['type' => 'opening_balance', 'id' => $ob->id, 'amount' => 6000.00],
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.vendor-payments.store'), $paymentPayload);
        $response->assertSessionHas('success');

        $ob->refresh();
        $this->assertEquals(6000.00, (float) $ob->paid_amount);
        $this->assertEquals(0.00, (float) $ob->due_amount);
        $this->assertEquals('Paid', $ob->status);

        // Rule 2 Check: Expense table untouched
        $this->assertEquals($expensesCountBefore, Expense::count());
    }

    /**
     * 11. Preventing duplicate opening-balance entries for the same reference.
     */
    public function test_11_duplicate_opening_balance_reference_is_rejected(): void
    {
        VendorOpeningBalance::create([
            'vendor_id'       => $this->vendor->id,
            'opening_balance' => 5000.00,
            'paid_amount'     => 0.00,
            'effective_date'  => '2026-01-01',
            'reference_no'    => 'UNIQUE-REF-001',
            'status'          => 'Unpaid',
            'user_id'         => $this->adminUser->id,
        ]);

        $duplicatePayload = [
            'vendor_id'       => $this->vendor->id,
            'opening_balance' => 5000.00,
            'effective_date'  => '2026-01-01',
            'reference_no'    => 'UNIQUE-REF-001',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.payables.opening_balance.store'), $duplicatePayload);
        $response->assertSessionHas('error');
    }

    /**
     * 12. Date-range calculations for opening balances and payments in ledger.
     */
    public function test_12_vendor_ledger_calculates_running_balance_across_date_range(): void
    {
        // 1. Prior bill before period (Aug 2026) for 5,000, paid 2,000 -> Prior due = 3,000
        Expense::create([
            'voucher_no'          => 'EXP-PRIOR',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-08-15',
            'amount'              => 5000.00,
            'paid_amount'         => 2000.00,
            'payment_status'      => 'Partially Paid',
            'payment_method'      => 'Cash',
            'user_id'             => $this->adminUser->id,
        ]);

        // 2. Bill within period (October 2026) for 4,000
        Expense::create([
            'voucher_no'          => 'EXP-IN-PERIOD',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-05',
            'amount'              => 4000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        // 3. Payment within period (October 2026) for 1,500
        VendorPayment::create([
            'payment_no'     => 'PMT-TEST-12',
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-10',
            'payment_method' => 'Cash',
            'amount'         => 1500.00,
            'user_id'        => $this->adminUser->id,
        ]);

        $ledger = $this->payableService->getVendorLedger(
            vendorId: $this->vendor->id,
            startDate: '2026-10-01',
            endDate: '2026-10-31'
        );

        $this->assertEquals(3000.00, (float) $ledger['prior_due']);
        $this->assertEquals(4000.00, (float) $ledger['billed_in_period']);
        $this->assertEquals(1500.00, (float) $ledger['paid_in_period']);
        // Closing: 3,000 + 4,000 - 1,500 = 5,500
        $this->assertEquals(5500.00, (float) $ledger['closing_payable']);
    }

    /**
     * 13. Correct overdue classification and due-date boundaries.
     */
    public function test_13_overdue_scope_and_attribute_detection(): void
    {
        // Expired due date with remaining due
        $overdueExpense = Expense::create([
            'voucher_no'          => 'EXP-OVERDUE-1',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-09-01',
            'due_date'            => '2026-09-20',
            'amount'              => 5000.00,
            'paid_amount'         => 1000.00,
            'payment_status'      => 'Partially Paid',
            'payment_method'      => 'Cash',
            'user_id'             => $this->adminUser->id,
        ]);

        // Future due date
        $futureExpense = Expense::create([
            'voucher_no'          => 'EXP-FUTURE-1',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'due_date'            => '2026-12-31',
            'amount'              => 3000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        $this->assertTrue($overdueExpense->is_overdue);
        $this->assertFalse($futureExpense->is_overdue);

        $overdueCount = Expense::overdue()->where('vendor_id', $this->vendor->id)->count();
        $this->assertEquals(1, $overdueCount);
    }

    /**
     * 14. Branch and payment-method filtering on payments.
     */
    public function test_14_vendor_payments_filtering(): void
    {
        VendorPayment::create([
            'payment_no'     => 'PMT-FILTER-1',
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-05',
            'payment_method' => 'Cash',
            'amount'         => 1200.00,
            'user_id'        => $this->adminUser->id,
        ]);

        VendorPayment::create([
            'payment_no'     => 'PMT-FILTER-2',
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => null,
            'payment_date'   => '2026-10-06',
            'payment_method' => 'Bank',
            'amount'         => 2500.00,
            'user_id'        => $this->adminUser->id,
        ]);

        // Filter by Cash
        $resCash = $this->actingAs($this->adminUser)->get(route('accounts.vendor-payments.index', ['payment_method' => 'Cash']));
        $resCash->assertSee('PMT-FILTER-1');
        $resCash->assertDontSee('PMT-FILTER-2');

        // Filter by Branch
        $resBranch = $this->actingAs($this->adminUser)->get(route('accounts.vendor-payments.index', ['branch_id' => $this->branch->id]));
        $resBranch->assertSee('PMT-FILTER-1');
    }

    /**
     * 15. Transaction rollback if payment allocation fails.
     */
    public function test_15_transaction_rolls_back_completely_on_allocation_error(): void
    {
        $expense = Expense::create([
            'voucher_no'          => 'EXP-ROLLBACK',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'amount'              => 5000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        $paymentsCountBefore = VendorPayment::count();

        // Pass invalid allocation item id 999999
        $invalidPayload = [
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-10',
            'payment_method' => 'Cash',
            'amount'         => 2000.00,
            'allocations'    => [
                ['type' => 'bill', 'id' => 999999, 'amount' => 2000.00],
            ]
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.vendor-payments.store'), $invalidPayload);
        $response->assertSessionHasErrors();

        // Neither payment nor allocation persisted
        $this->assertEquals($paymentsCountBefore, VendorPayment::count());
        $expense->refresh();
        $this->assertEquals(0.00, (float) $expense->paid_amount);
    }

    /**
     * 16. Audit trail logged for payments and balance updates.
     */
    public function test_16_audit_trail_recorded_on_payment_creation(): void
    {
        $expense = Expense::create([
            'voucher_no'          => 'EXP-AUDIT-TEST',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'amount'              => 2000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 2000.00,
            paymentDate: '2026-10-10',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'bill', 'id' => $expense->id, 'amount' => 2000.00]
            ],
            userId: $this->adminUser->id
        );

        $log = VendorAuditLog::where('vendor_id', $this->vendor->id)
                             ->where('action', 'payment_recorded')
                             ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->adminUser->id, $log->user_id);
        $this->assertArrayHasKey('amount', $log->new_data);
        $this->assertEquals(2000.00, $log->new_data['amount']);
    }

    /**
     * 17. Preventing unauthorized access to payables and vendor payments.
     */
    public function test_17_unauthorized_user_cannot_access_or_post_payables(): void
    {
        // Student role forbidden / redirected
        $res1 = $this->actingAs($this->unauthorizedUser)->get(route('accounts.payables.index'));
        $this->assertTrue(in_array($res1->status(), [403, 302]));

        $res2 = $this->actingAs($this->unauthorizedUser)->get(route('accounts.vendor-payments.index'));
        $this->assertTrue(in_array($res2->status(), [403, 302]));

        $res3 = $this->actingAs($this->unauthorizedUser)->post(route('accounts.vendor-payments.store'), [
            'vendor_id' => $this->vendor->id,
            'amount' => 500,
        ]);
        $this->assertTrue(in_array($res3->status(), [403, 302]));
    }

    /**
     * 18. Correct Financial Report reconciliation without double-counting (Rule 3).
     */
    public function test_18_financial_reports_do_not_double_count_expenses_and_payments(): void
    {
        // 1. Create a partial expense of 10,000 with 4,000 paid immediately
        $payload = [
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'payment_type'        => 'partial',
            'initial_paid_amount' => 4000.00,
            'payment_method'      => 'Cash',
            'reference_no'        => 'REC-TEST-1',
            'items'               => [
                [
                    'item_name'           => 'CCTV Camera Setup',
                    'expense_category_id' => $this->category->id,
                    'quantity'            => 1,
                    'unit_rate'           => 10000,
                ]
            ]
        ];
        $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);

        $expense = Expense::where('reference_no', 'REC-TEST-1')->first();

        // 2. Subsequent payment of 6,000 to settle the bill
        $subsequentPayload = [
            'vendor_id'      => $this->vendor->id,
            'branch_id'      => $this->branch->id,
            'payment_date'   => '2026-10-10',
            'payment_method' => 'Cash',
            'amount'         => 6000.00,
            'allocations'    => [
                ['type' => 'bill', 'id' => $expense->id, 'amount' => 6000.00],
            ]
        ];
        $this->actingAs($this->adminUser)->post(route('accounts.vendor-payments.store'), $subsequentPayload);

        // Request financial statement for October 2026
        $reportRes = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
        ]));
        $reportRes->assertStatus(200);

        // Expense recognition: Must be exactly 10,000 (NOT 10,000 + 4,000 + 6,000 = 20,000)
        $expenseTotal = Expense::whereBetween('expense_date', ['2026-10-01', '2026-10-31'])->sum('amount');
        $this->assertEquals(10000.00, (float) $expenseTotal);

        // Physical Cash Outflow: Exactly 10,000 (4,000 from initial + 6,000 from subsequent)
        $cashDisbursements = VendorPayment::whereBetween('payment_date', ['2026-10-01', '2026-10-31'])
            ->where('payment_method', 'Cash')
            ->sum('amount');
        $this->assertEquals(10000.00, (float) $cashDisbursements);
    }

    /**
     * 19. Historical voucher backward compatibility.
     */
    public function test_19_historical_vouchers_without_vendor_payment_records_remain_valid(): void
    {
        // Legacy voucher created without vendor_id and without vendor_payments rows
        $legacyExpense = Expense::create([
            'voucher_no'          => 'EXP-LEGACY-999',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => null,
            'paid_to'             => 'Local Hardware Shop',
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2025-05-10',
            'amount'              => 1250.00,
            'paid_amount'         => 1250.00,
            'payment_status'      => 'Paid',
            'payment_method'      => 'Cash',
            'user_id'             => $this->adminUser->id,
        ]);

        $this->assertEquals(0.00, (float) $legacyExpense->due_amount);
        $this->assertFalse($legacyExpense->is_overdue);

        $viewRes = $this->actingAs($this->adminUser)->get(route('accounts.expenses.voucher_print', $legacyExpense->id));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('EXP-LEGACY-999');
    }

    /**
     * 20. AJAX Bills endpoint returns accurate outstanding dues.
     */
    public function test_20_ajax_endpoint_returns_correct_vendor_bills(): void
    {
        $expense = Expense::create([
            'voucher_no'          => 'EXP-AJAX-01',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-01',
            'amount'              => 7500.00,
            'paid_amount'         => 2500.00,
            'payment_status'      => 'Partially Paid',
            'payment_method'      => 'Bank',
            'user_id'             => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('accounts.vendor-payments.bills_ajax', $this->vendor->id));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total_outstanding' => 5000.00,
        ]);
    }

    /**
     * 21. Scenario 1: A bill of ৳500 paid once through the Vendor Payments page, with closing due ৳0.
     * Prevents duplicate debit in vendor ledger.
     */
    public function test_21_bill_of_500_paid_once_via_vendor_payments_has_closing_due_zero(): void
    {
        $bill = Expense::create([
            'voucher_no'          => 'EXP-500-PAID',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-05',
            'amount'              => 500.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        $this->assertEquals(500.00, (float) $bill->due_amount);

        // Direct payment through Vendor Payments of ৳500
        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 500.00,
            paymentDate: '2026-10-10',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'bill', 'id' => $bill->id, 'amount' => 500.00]
            ],
            userId: $this->adminUser->id
        );

        $bill->refresh();
        $this->assertEquals(0.00, (float) $bill->due_amount);
        $this->assertEquals('Paid', $bill->payment_status);

        // Verify Vendor Ledger: Must close at exactly ৳0.00 (not -৳500 due to duplicate debit)
        $ledger = $this->payableService->getVendorLedger($this->vendor->id, '2026-10-01', '2026-10-31');
        $this->assertEquals(500.00, (float) $ledger['billed_in_period']);
        $this->assertEquals(500.00, (float) $ledger['paid_in_period']);
        $this->assertEquals(0.00, (float) $ledger['closing_payable']);
    }

    /**
     * 22. Scenario 2: A ৳500 bill with a ৳200 payment, with closing due ৳300.
     */
    public function test_22_bill_of_500_with_200_payment_has_closing_due_300(): void
    {
        $bill = Expense::create([
            'voucher_no'          => 'EXP-500-PARTIAL',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-05',
            'amount'              => 500.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        // Direct payment of ৳200
        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 200.00,
            paymentDate: '2026-10-10',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'bill', 'id' => $bill->id, 'amount' => 200.00]
            ],
            userId: $this->adminUser->id
        );

        $bill->refresh();
        $this->assertEquals(300.00, (float) $bill->due_amount);
        $this->assertEquals('Partially Paid', $bill->payment_status);

        // Verify Vendor Ledger: Billed 500, Paid 200, Closing 300 (not 100 due to double debit)
        $ledger = $this->payableService->getVendorLedger($this->vendor->id, '2026-10-01', '2026-10-31');
        $this->assertEquals(500.00, (float) $ledger['billed_in_period']);
        $this->assertEquals(200.00, (float) $ledger['paid_in_period']);
        $this->assertEquals(300.00, (float) $ledger['closing_payable']);
    }

    /**
     * 23. Scenario 3: A legacy fully paid expense without a canonical payment record, preserving historical ledger behavior.
     */
    public function test_23_legacy_fully_paid_expense_without_canonical_payment_preserves_historical_ledger(): void
    {
        Expense::create([
            'voucher_no'          => 'EXP-LEGACY-PAID',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-02',
            'amount'              => 1000.00,
            'paid_amount'         => 1000.00,
            'payment_status'      => 'Paid',
            'payment_method'      => 'Cash',
            'user_id'             => $this->adminUser->id,
        ]);

        // No VendorPayment or VendorPaymentAllocation exists
        $ledger = $this->payableService->getVendorLedger($this->vendor->id, '2026-10-01', '2026-10-31');
        $this->assertEquals(1000.00, (float) $ledger['billed_in_period']);
        $this->assertEquals(1000.00, (float) $ledger['paid_in_period']);
        $this->assertEquals(0.00, (float) $ledger['closing_payable']);
    }

    /**
     * 24. Scenario 4: An opening balance paid partially and then fully.
     */
    public function test_24_opening_balance_paid_partially_and_then_fully(): void
    {
        $ob = VendorOpeningBalance::create([
            'vendor_id'       => $this->vendor->id,
            'opening_balance' => 1500.00,
            'paid_amount'     => 0.00,
            'effective_date'  => '2026-10-01',
            'reference_no'    => 'OB-TEST-24',
            'status'          => 'Unpaid',
            'user_id'         => $this->adminUser->id,
        ]);

        // Partial payment ৳500
        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 500.00,
            paymentDate: '2026-10-05',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'opening_balance', 'id' => $ob->id, 'amount' => 500.00]
            ],
            userId: $this->adminUser->id
        );

        $ob->refresh();
        $this->assertEquals(1000.00, (float) $ob->due_amount);
        $this->assertEquals('Partially Paid', $ob->status);

        $ledger1 = $this->payableService->getVendorLedger($this->vendor->id, '2026-10-01', '2026-10-31');
        $this->assertEquals(1000.00, (float) $ledger1['closing_payable']);

        // Full remaining payment ৳1000
        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 1000.00,
            paymentDate: '2026-10-15',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'opening_balance', 'id' => $ob->id, 'amount' => 1000.00]
            ],
            userId: $this->adminUser->id
        );

        $ob->refresh();
        $this->assertEquals(0.00, (float) $ob->due_amount);
        $this->assertEquals('Paid', $ob->status);

        $ledger2 = $this->payableService->getVendorLedger($this->vendor->id, '2026-10-01', '2026-10-31');
        $this->assertEquals(0.00, (float) $ledger2['closing_payable']);
    }

    /**
     * 25. Scenario 5: Payment allocation across multiple bills without double-counting.
     */
    public function test_25_payment_allocation_across_multiple_bills_without_double_counting(): void
    {
        $bill1 = Expense::create([
            'voucher_no'          => 'EXP-SPLIT-1',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-02',
            'amount'              => 400.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        $bill2 = Expense::create([
            'voucher_no'          => 'EXP-SPLIT-2',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-03',
            'amount'              => 600.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        // Single payment of ৳700 allocated to both bills
        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 700.00,
            paymentDate: '2026-10-10',
            paymentMethod: 'Bank',
            allocations: [
                ['type' => 'bill', 'id' => $bill1->id, 'amount' => 400.00],
                ['type' => 'bill', 'id' => $bill2->id, 'amount' => 300.00],
            ],
            userId: $this->adminUser->id
        );

        $bill1->refresh();
        $bill2->refresh();

        $this->assertEquals(0.00, (float) $bill1->due_amount);
        $this->assertEquals('Paid', $bill1->payment_status);

        $this->assertEquals(300.00, (float) $bill2->due_amount);
        $this->assertEquals('Partially Paid', $bill2->payment_status);

        $ledger = $this->payableService->getVendorLedger($this->vendor->id, '2026-10-01', '2026-10-31');
        $this->assertEquals(1000.00, (float) $ledger['billed_in_period']);
        $this->assertEquals(700.00, (float) $ledger['paid_in_period']);
        $this->assertEquals(300.00, (float) $ledger['closing_payable']);
    }

    /**
     * 26. Cash outflow reconciliation: Financial report does not double count direct vendor payments.
     */
    public function test_26_financial_report_cash_outflow_does_not_double_count_direct_payments(): void
    {
        // Bill created as unpaid
        $bill = Expense::create([
            'voucher_no'          => 'EXP-CASH-OUTFLOW',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-05',
            'amount'              => 5000.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        // Direct payment of ৳5,000 via Cash
        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 5000.00,
            paymentDate: '2026-10-10',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'bill', 'id' => $bill->id, 'amount' => 5000.00]
            ],
            userId: $this->adminUser->id
        );

        $bill->refresh();
        $this->assertEquals('Paid', $bill->payment_status);

        // Request financial report
        $reportRes = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
        ]));
        $reportRes->assertStatus(200);

        // Report passes totalExpense = 5000 and totalCashOut = 5000 (NOT 10,000)
        $reportRes->assertViewHas('totalExpense', 5000.00);
        $reportRes->assertViewHas('totalCashOut', 5000.00);
    }

    /**
     * 27. Unpaid or partial voucher requires a registered active vendor.
     */
    public function test_27_unpaid_or_partial_voucher_requires_valid_active_vendor(): void
    {
        // 1. Unpaid voucher without vendor must be rejected
        $unpaidPayload = [
            'paid_to'        => 'Random Supplier',
            'branch_id'      => $this->branch->id,
            'expense_date'   => '2026-10-05',
            'payment_type'   => 'unpaid',
            'payment_method' => 'Due',
            'items'          => [
                [
                    'item_name'           => 'Desks',
                    'expense_category_id' => $this->category->id,
                    'quantity'            => 2,
                    'unit_rate'           => 1500,
                ]
            ]
        ];

        $res1 = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $unpaidPayload);
        $res1->assertSessionHasErrors(['vendor_id']);
        $this->assertEquals(0, Expense::where('paid_to', 'Random Supplier')->count());

        // 2. Inactive vendor must be rejected
        $inactiveVendor = ExpenseVendor::create([
            'name'   => 'Inactive Supplies Ltd',
            'status' => 'Inactive',
        ]);

        $inactivePayload = [
            'vendor_id'           => $inactiveVendor->id,
            'paid_to'             => $inactiveVendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-05',
            'payment_type'        => 'partial',
            'initial_paid_amount' => 500.00,
            'payment_method'      => 'Cash',
            'items'               => [
                [
                    'item_name'           => 'Paper Reams',
                    'expense_category_id' => $this->category->id,
                    'quantity'            => 5,
                    'unit_rate'           => 200,
                ]
            ]
        ];

        $res2 = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $inactivePayload);
        $res2->assertSessionHasErrors(['vendor_id']);
        $this->assertEquals(0, Expense::where('vendor_id', $inactiveVendor->id)->count());
    }

    /**
     * 28. Full cash purchase without vendor is allowed and counted once in cash outflow.
     */
    public function test_28_full_cash_purchase_without_vendor_is_allowed(): void
    {
        $payload = [
            'paid_to'        => 'Local Hardware Store',
            'branch_id'      => $this->branch->id,
            'expense_date'   => '2026-10-05',
            'payment_type'   => 'full',
            'payment_method' => 'Cash',
            'items'          => [
                [
                    'item_name'           => 'Light Bulbs',
                    'expense_category_id' => $this->category->id,
                    'quantity'            => 10,
                    'unit_rate'           => 35,
                ]
            ]
        ];

        $res = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);
        $res->assertSessionHasNoErrors();

        $expense = Expense::where('paid_to', 'Local Hardware Store')->first();
        $this->assertNotNull($expense);
        $this->assertNull($expense->vendor_id);
        $this->assertEquals(350.00, (float) $expense->amount);
        $this->assertEquals(350.00, (float) $expense->paid_amount);
        $this->assertEquals('Paid', $expense->payment_status);

        // In financial report, this legacy paid expense is counted once (350.00)
        $reportRes = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
        ]));
        $reportRes->assertViewHas('totalExpense', 350.00);
        $reportRes->assertViewHas('totalCashOut', 350.00);
    }

    /**
     * 29. Idempotency lock prevents duplicate rapid payment submissions.
     */
    public function test_29_idempotency_lock_prevents_duplicate_payment_submission(): void
    {
        $bill = Expense::create([
            'voucher_no'          => 'EXP-IDEMPOTENCY',
            'expense_category_id' => $this->category->id,
            'vendor_id'           => $this->vendor->id,
            'paid_to'             => $this->vendor->name,
            'branch_id'           => $this->branch->id,
            'expense_date'        => '2026-10-05',
            'amount'              => 800.00,
            'paid_amount'         => 0.00,
            'payment_status'      => 'Unpaid',
            'payment_method'      => 'Credit / Payable',
            'user_id'             => $this->adminUser->id,
        ]);

        // First submission succeeds
        $payment = $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 400.00,
            paymentDate: '2026-10-10',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'bill', 'id' => $bill->id, 'amount' => 400.00]
            ],
            userId: $this->adminUser->id
        );
        $this->assertNotNull($payment);

        // Immediate duplicate submission with identical fingerprint is blocked by idempotency lock
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->payableService->recordPayment(
            vendorId: $this->vendor->id,
            totalAmount: 400.00,
            paymentDate: '2026-10-10',
            paymentMethod: 'Cash',
            allocations: [
                ['type' => 'bill', 'id' => $bill->id, 'amount' => 400.00]
            ],
            userId: $this->adminUser->id
        );
    }
}
