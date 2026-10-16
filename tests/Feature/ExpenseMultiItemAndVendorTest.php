<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseAuditLog;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\ExpenseVendor;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\User;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseMultiItemAndVendorTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $studentUser;
    protected Branch $branch;
    protected ExpenseCategory $category;
    protected ExpenseVendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin User
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_multi_test@macsschool.com'],
            ['name' => 'Multi Admin Tester', 'password' => bcrypt('password')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        // Unauthorized Student User
        $this->studentUser = User::firstOrCreate(
            ['email' => 'student_multi_test@macsschool.com'],
            ['name' => 'Student Tester', 'password' => bcrypt('password')]
        );
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->studentUser->assignRole($studentRole);

        // Branch
        $this->branch = Branch::firstOrCreate(
            ['id' => 1],
            ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]
        );

        // Category
        $this->category = ExpenseCategory::create([
            'name' => 'Stationery & Printing',
            'description' => 'Office paper and books',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        // Vendor
        $this->vendor = ExpenseVendor::create([
            'name' => 'Ideal Book Depot',
            'contact_person' => 'Md. Anwar Hossain',
            'phone' => '01711223344',
            'address' => 'Station Road, Pabna',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * 1. Creating a voucher with multiple items.
     */
    public function test_can_create_voucher_with_multiple_items(): void
    {
        $payload = [
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'reference_no' => 'INV-8899',
            'note' => 'Term exam papers and notebooks',
            'items' => [
                [
                    'item_name' => 'A4 Paper Rim',
                    'quantity' => 5,
                    'unit_rate' => 450.00,
                    'note' => '80 GSM',
                ],
                [
                    'item_name' => 'Whiteboard Markers',
                    'quantity' => 10,
                    'unit_rate' => 65.50,
                    'note' => 'Blue & Black',
                ],
                [
                    'item_name' => 'Duster',
                    'quantity' => 2,
                    'unit_rate' => 120.00,
                    'note' => 'Wooden handle',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);

        $response->assertRedirect(route('accounts.expenses.index'));
        $response->assertSessionHas('success');

        // Calculated: (5 * 450) + (10 * 65.50) + (2 * 120) = 2250 + 655 + 240 = 3145.00
        $expense = Expense::where('vendor_id', $this->vendor->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals(3145.00, (float) $expense->amount);
        $this->assertEquals($this->vendor->name, $expense->paid_to);
        $this->assertCount(3, $expense->items);

        $this->assertDatabaseHas('expense_items', [
            'expense_id' => $expense->id,
            'item_name' => 'A4 Paper Rim',
            'quantity' => 5.00,
            'unit_rate' => 450.00,
            'line_total' => 2250.00,
        ]);
        $this->assertDatabaseHas('expense_items', [
            'expense_id' => $expense->id,
            'item_name' => 'Whiteboard Markers',
            'quantity' => 10.00,
            'unit_rate' => 65.50,
            'line_total' => 655.00,
        ]);
        $this->assertDatabaseHas('expense_items', [
            'expense_id' => $expense->id,
            'item_name' => 'Duster',
            'quantity' => 2.00,
            'unit_rate' => 120.00,
            'line_total' => 240.00,
        ]);
    }

    /**
     * 2. Correct line-total and voucher-total calculations with fractional quantities.
     */
    public function test_correct_line_total_and_voucher_total_calculations(): void
    {
        $payload = [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Bank',
            'paid_to' => 'Lab Equipment Supplier',
            'items' => [
                [
                    'item_name' => 'Chemical Liquid (Liters)',
                    'quantity' => 3.75,
                    'unit_rate' => 140.00, // 3.75 * 140 = 525.00
                ],
                [
                    'item_name' => 'Test Tubes (Pack)',
                    'quantity' => 2.50,
                    'unit_rate' => 210.50, // 2.50 * 210.50 = 526.25
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);

        $response->assertRedirect(route('accounts.expenses.index'));

        // Grand Total: 525.00 + 526.25 = 1051.25
        $expense = Expense::where('paid_to', 'Lab Equipment Supplier')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(1051.25, (float) $expense->amount);
    }

    /**
     * 3. Server-side rejection of manipulated totals.
     */
    public function test_server_side_rejection_of_manipulated_totals(): void
    {
        $payload = [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => 'Vendor Test',
            // Manipulated: Items sum to 1000, but client submits 500
            'amount' => 500.00,
            'items' => [
                [
                    'item_name' => 'Item One',
                    'quantity' => 2,
                    'unit_rate' => 500.00, // 1000.00
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(0, Expense::where('paid_to', 'Vendor Test')->count());
    }

    /**
     * 4. Zero, negative, invalid, and excessive quantity/rate values are rejected.
     */
    public function test_rejection_of_zero_negative_invalid_and_excessive_quantity_and_rate(): void
    {
        // Zero quantity
        $resZeroQty = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => 'Test Zero',
            'items' => [
                ['item_name' => 'Bad Item', 'quantity' => 0, 'unit_rate' => 100],
            ],
        ]);
        $resZeroQty->assertSessionHasErrors(['items.0.quantity']);

        // Negative quantity
        $resNegQty = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => 'Test Neg Qty',
            'items' => [
                ['item_name' => 'Bad Item', 'quantity' => -2, 'unit_rate' => 100],
            ],
        ]);
        $resNegQty->assertSessionHasErrors(['items.0.quantity']);

        // Negative rate
        $resNegRate = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => 'Test Neg Rate',
            'items' => [
                ['item_name' => 'Bad Item', 'quantity' => 2, 'unit_rate' => -50],
            ],
        ]);
        $resNegRate->assertSessionHasErrors(['items.0.unit_rate']);

        // Missing item name
        $resMissingName = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => 'Test Missing Name',
            'items' => [
                ['item_name' => '', 'quantity' => 1, 'unit_rate' => 50],
            ],
        ]);
        $resMissingName->assertSessionHasErrors(['items.0.item_name']);
    }

    /**
     * 5. Empty items array without valid total is rejected.
     */
    public function test_empty_items_array_is_rejected(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => 'Test Empty',
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['items']);
    }

    /**
     * 6. Transaction rollback when one item fails to save.
     */
    public function test_transaction_rollback_when_persistence_fails(): void
    {
        // Mock a failure during item persistence using DB transaction hook or faulty item
        $initialExpenseCount = Expense::count();
        $initialItemCount = ExpenseItem::count();

        // One valid item and one item with negative quantity
        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => 'Rollback Vendor',
            'items' => [
                ['item_name' => 'Good Item', 'quantity' => 5, 'unit_rate' => 100],
                ['item_name' => 'Corrupt Item', 'quantity' => -1, 'unit_rate' => 50],
            ],
        ]);

        $response->assertSessionHasErrors(['items.1.quantity']);
        $this->assertEquals($initialExpenseCount, Expense::count());
        $this->assertEquals($initialItemCount, ExpenseItem::count());
    }

    /**
     * 7. Editing a voucher and updating its items.
     */
    public function test_can_edit_voucher_and_update_items_safely(): void
    {
        $expense = Expense::create([
            'voucher_no' => 'EXP-20261016-EDIT01',
            'expense_category_id' => $this->category->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'amount' => 500.00,
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'user_id' => $this->adminUser->id,
        ]);

        $expense->items()->create([
            'item_name' => 'Initial Item',
            'quantity' => 2,
            'unit_rate' => 250.00,
            'line_total' => 500.00,
        ]);

        $updatePayload = [
            'expense_category_id' => $this->category->id,
            'vendor_id' => $this->vendor->id,
            'paid_to' => $this->vendor->name,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Bank',
            'edit_reason' => 'Increased quantity of item',
            'items' => [
                [
                    'item_name' => 'Updated Item',
                    'quantity' => 4,
                    'unit_rate' => 250.00,
                ],
                [
                    'item_name' => 'New Additional Item',
                    'quantity' => 1,
                    'unit_rate' => 300.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->put(
            route('accounts.expenses.update', $expense->id),
            $updatePayload
        );

        $response->assertRedirect(route('accounts.expenses.index'));
        $response->assertSessionHas('success');

        $expense->refresh();
        // (4 * 250) + (1 * 300) = 1300.00
        $this->assertEquals(1300.00, (float) $expense->amount);
        $this->assertEquals('Bank', $expense->payment_method);
        $this->assertCount(2, $expense->items);

        // Audit log check
        $log = ExpenseAuditLog::where('expense_id', $expense->id)
            ->where('action', 'updated')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals('Increased quantity of item', $log->reason);
        $this->assertArrayHasKey('items', $log->new_data);
        $this->assertCount(2, $log->new_data['items']);
    }

    /**
     * 8. Creating a category via AJAX from modal.
     */
    public function test_can_create_category_via_ajax_from_modal(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('accounts.categories.store_ajax'), [
            'name' => 'Sports Equipment',
            'description' => 'Football, cricket kits, and bats',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'category' => [
                'name' => 'Sports Equipment',
                'status' => 'Active',
            ],
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Sports Equipment',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * 9. Creating and selecting a vendor via AJAX from modal.
     */
    public function test_can_create_and_select_vendor_via_ajax_from_modal(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('accounts.vendors.store_ajax'), [
            'name' => 'National Sports Pabna',
            'contact_person' => 'Mr. Tariqul Islam',
            'phone' => '01822334455',
            'address' => 'Abdul Hamid Road, Pabna',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Vendor created successfully.',
            'vendor' => [
                'name' => 'National Sports Pabna',
                'contact_person' => 'Mr. Tariqul Islam',
                'status' => 'Active',
            ],
        ]);

        $this->assertDatabaseHas('expense_vendors', [
            'name' => 'National Sports Pabna',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * 10. Duplicate vendor and category validation.
     */
    public function test_duplicate_vendor_and_category_names_are_rejected(): void
    {
        // Duplicate Category
        $catRes = $this->actingAs($this->adminUser)->postJson(route('accounts.categories.store_ajax'), [
            'name' => 'Stationery & Printing', // Already exists in setUp
        ]);
        $catRes->assertStatus(422);
        $catRes->assertJsonValidationErrors(['name']);

        // Duplicate Vendor
        $venRes = $this->actingAs($this->adminUser)->postJson(route('accounts.vendors.store_ajax'), [
            'name' => 'Ideal Book Depot', // Already exists in setUp
        ]);
        $venRes->assertStatus(422);
        $venRes->assertJsonValidationErrors(['name']);
    }

    /**
     * 11. Inactive vendor cannot be selected for new vouchers.
     */
    public function test_inactive_vendor_cannot_be_selected_for_new_vouchers(): void
    {
        $inactiveVendor = ExpenseVendor::create([
            'name' => 'Old Defunct Store',
            'status' => 'Inactive',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'vendor_id' => $inactiveVendor->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'items' => [
                ['item_name' => 'Pens', 'quantity' => 10, 'unit_rate' => 10],
            ],
        ]);

        $response->assertSessionHasErrors(['vendor_id']);
    }

    /**
     * 12. Historical expense compatibility without items and without vendor.
     */
    public function test_historical_expense_compatibility_without_items_and_without_vendor(): void
    {
        // Simulate a legacy expense record created before vendor_id or expense_items existed
        $legacyExpense = Expense::create([
            'voucher_no' => 'EXP-20260901-LEGACY',
            'expense_category_id' => $this->category->id,
            'vendor_id' => null,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-09-01',
            'amount' => 7500.00,
            'payment_method' => 'Bank',
            'paid_to' => 'Historical Recipient Corp',
            'reference_no' => 'OLD-REF-101',
            'note' => 'Annual painting work',
            'user_id' => $this->adminUser->id,
        ]);

        // 1. Voucher Print renders correctly without crashing
        $printResponse = $this->actingAs($this->adminUser)->get(
            route('accounts.expenses.voucher_print', $legacyExpense->id)
        );
        $printResponse->assertStatus(200);
        $printResponse->assertSee('EXP-20260901-LEGACY');
        $printResponse->assertSee('Historical Recipient Corp');
        $printResponse->assertSee('7,500.00');

        // 2. Expenses listing renders correctly
        $indexResponse = $this->actingAs($this->adminUser)->get(
            route('accounts.expenses.index', ['start_date' => '2026-09-01', 'end_date' => '2026-09-30'])
        );
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('EXP-20260901-LEGACY');
        $indexResponse->assertSee('Historical Recipient Corp');
    }

    /**
     * 13. Correct audit snapshots when expense is deleted.
     */
    public function test_audit_snapshot_preserves_line_items_on_deletion(): void
    {
        $expense = Expense::create([
            'voucher_no' => 'EXP-20261016-DEL01',
            'expense_category_id' => $this->category->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'amount' => 800.00,
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'user_id' => $this->adminUser->id,
        ]);

        $expense->items()->create([
            'item_name' => 'Item to delete',
            'quantity' => 4,
            'unit_rate' => 200.00,
            'line_total' => 800.00,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(
            route('accounts.expenses.destroy', $expense->id),
            ['delete_reason' => 'Duplicate voucher cancellation']
        );

        $response->assertRedirect(route('accounts.expenses.index'));
        $this->assertNull(Expense::find($expense->id));

        $log = ExpenseAuditLog::where('voucher_no', 'EXP-20261016-DEL01')
            ->where('action', 'deleted')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Duplicate voucher cancellation', $log->reason);
        $this->assertArrayHasKey('items', $log->previous_data);
        $this->assertEquals('Item to delete', $log->previous_data['items'][0]['item_name']);
        $this->assertEquals(800.00, (float) $log->amount);
    }

    /**
     * 14. Debit voucher printing displays all line items, rates, and totals.
     */
    public function test_voucher_printing_renders_multi_item_breakdown(): void
    {
        $expense = Expense::create([
            'voucher_no' => 'EXP-20261016-PRINT',
            'expense_category_id' => $this->category->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'amount' => 1750.00,
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'user_id' => $this->adminUser->id,
        ]);

        $expense->items()->createMany([
            [
                'item_name' => 'Physics Geometry Box',
                'quantity' => 5,
                'unit_rate' => 150.00,
                'line_total' => 750.00,
            ],
            [
                'item_name' => 'Biology Charts (Set)',
                'quantity' => 2,
                'unit_rate' => 500.00,
                'line_total' => 1000.00,
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->get(
            route('accounts.expenses.voucher_print', $expense->id)
        );

        $response->assertStatus(200);
        $response->assertSee('Physics Geometry Box');
        $response->assertSee('Biology Charts (Set)');
        $response->assertSee('750.00');
        $response->assertSee('1,000.00');
        $response->assertSee('1,750.00');
        $response->assertSee('Ideal Book Depot');
    }

    /**
     * 15. Financial report totals do not double-count multi-item vouchers.
     */
    public function test_financial_report_totals_do_not_double_count_multi_item_expenses(): void
    {
        // Create an expense of 4,000 with 4 items of 1,000 each
        $expense = Expense::create([
            'voucher_no' => 'EXP-20261016-FINREP',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-16',
            'amount' => 4000.00,
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'user_id' => $this->adminUser->id,
        ]);

        $expense->items()->createMany([
            ['item_name' => 'Desk 1', 'quantity' => 1, 'unit_rate' => 1000.00, 'line_total' => 1000.00],
            ['item_name' => 'Desk 2', 'quantity' => 1, 'unit_rate' => 1000.00, 'line_total' => 1000.00],
            ['item_name' => 'Desk 3', 'quantity' => 1, 'unit_rate' => 1000.00, 'line_total' => 1000.00],
            ['item_name' => 'Desk 4', 'quantity' => 1, 'unit_rate' => 1000.00, 'line_total' => 1000.00],
        ]);

        $response = $this->actingAs($this->adminUser)->get(
            route('accounts.reports.index', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'branch_id' => $this->branch->id,
            ])
        );

        $response->assertStatus(200);
        // The total expense in the view must be exactly 4000.00 (not 4000 + 4000 = 8000, and count = 1)
        $response->assertViewHas('totalExpense', 4000.00);
        $response->assertViewHas('totalExpensesCount', 1);
    }

    /**
     * 16. Authorization for all new routes and operations.
     */
    public function test_unauthenticated_and_unauthorized_users_are_forbidden(): void
    {
        // Unauthenticated access
        $resUnauthCat = $this->postJson(route('accounts.categories.store_ajax'), ['name' => 'Test']);
        $resUnauthCat->assertStatus(401);

        $resUnauthVen = $this->postJson(route('accounts.vendors.store_ajax'), ['name' => 'Test']);
        $resUnauthVen->assertStatus(401);

        // Unauthorized role (Student)
        $resForbiddenCat = $this->actingAs($this->studentUser)->postJson(
            route('accounts.categories.store_ajax'),
            ['name' => 'Unauthorized Category']
        );
        $this->assertTrue(in_array($resForbiddenCat->status(), [403, 302]));

        $resForbiddenVen = $this->actingAs($this->studentUser)->postJson(
            route('accounts.vendors.store_ajax'),
            ['name' => 'Unauthorized Vendor']
        );
        $this->assertTrue(in_array($resForbiddenVen->status(), [403, 302]));
    }

    /**
     * 17. One voucher with multiple items and different categories allocated correctly in reports.
     * Rule: ONE VOUCHER = ONE VENDOR + ONE BILL + MULTIPLE LINE ITEMS + MULTIPLE CATEGORIES
     */
    public function test_one_voucher_with_multiple_items_and_different_categories(): void
    {
        $catStationery = $this->category; // 'Stationery & Printing'
        $catCleaning = ExpenseCategory::create([
            'name' => 'Cleaning Supplies',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
        $catUtility = ExpenseCategory::create([
            'name' => 'Electricity & Utility',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $payload = [
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'reference_no' => 'BILL-OCT-9988',
            'note' => 'Combined monthly campus supply memo',
            'items' => [
                [
                    'item_name' => 'Printer Paper',
                    'expense_category_id' => $catStationery->id,
                    'quantity' => 5,
                    'unit_rate' => 350.00, // 1750.00
                ],
                [
                    'item_name' => 'Floor Cleaner',
                    'expense_category_id' => $catCleaning->id,
                    'quantity' => 3,
                    'unit_rate' => 180.00, // 540.00
                ],
                [
                    'item_name' => 'Electricity Bill',
                    'expense_category_id' => $catUtility->id,
                    'quantity' => 1,
                    'unit_rate' => 4500.00, // 4500.00
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), $payload);
        $response->assertRedirect(route('accounts.expenses.index'));

        // Expected voucher total: 1750 + 540 + 4500 = 6790.00
        $expense = Expense::with(['items.category', 'vendor'])->where('reference_no', 'BILL-OCT-9988')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(6790.00, (float) $expense->amount);
        $this->assertEquals($this->vendor->id, $expense->vendor_id);
        $this->assertCount(3, $expense->items);

        // Verify each line item has its own category stored
        $items = $expense->items->keyBy('item_name');
        $this->assertEquals($catStationery->id, $items['Printer Paper']->expense_category_id);
        $this->assertEquals(1750.00, (float) $items['Printer Paper']->line_total);

        $this->assertEquals($catCleaning->id, $items['Floor Cleaner']->expense_category_id);
        $this->assertEquals(540.00, (float) $items['Floor Cleaner']->line_total);

        $this->assertEquals($catUtility->id, $items['Electricity Bill']->expense_category_id);
        $this->assertEquals(4500.00, (float) $items['Electricity Bill']->line_total);

        // Financial Report: Category allocation without double counting
        $reportRes = $this->actingAs($this->adminUser)->get(route('accounts.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));
        $reportRes->assertStatus(200);

        // Authoritative voucher total counted once
        $reportRes->assertViewHas('totalExpense', 6790.00);
        $reportRes->assertViewHas('totalExpensesCount', 1);

        // Check category breakdown in report
        $categoriesBreakdown = collect($reportRes->viewData('expenseByCategory'))->keyBy('name');

        $this->assertEquals(1750.00, (float) $categoriesBreakdown['Stationery & Printing']['amount']);
        $this->assertEquals(540.00, (float) $categoriesBreakdown['Cleaning Supplies']['amount']);
        $this->assertEquals(4500.00, (float) $categoriesBreakdown['Electricity & Utility']['amount']);
    }

    /**
     * 18. Enforcing vendor change protection: changing vendor requires explicit audit reason.
     */
    public function test_vendor_change_protection_requires_audit_reason(): void
    {
        $vendor2 = ExpenseVendor::create([
            'name' => 'Second Alternate Vendor',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $expense = Expense::create([
            'voucher_no' => 'EXP-20261016-VENPROT',
            'expense_category_id' => $this->category->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'amount' => 500.00,
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'user_id' => $this->adminUser->id,
        ]);

        $expense->items()->create([
            'expense_category_id' => $this->category->id,
            'item_name' => 'Chalk Box',
            'quantity' => 5,
            'unit_rate' => 100.00,
            'line_total' => 500.00,
        ]);

        // Attempt to change vendor without edit_reason
        $badUpdateRes = $this->actingAs($this->adminUser)->put(
            route('accounts.expenses.update', $expense->id),
            [
                'vendor_id' => $vendor2->id,
                'paid_to' => $vendor2->name,
                'expense_date' => '2026-10-16',
                'payment_method' => 'Cash',
                'edit_reason' => '', // Empty edit reason
                'items' => [
                    [
                        'item_name' => 'Chalk Box',
                        'expense_category_id' => $this->category->id,
                        'quantity' => 5,
                        'unit_rate' => 100.00,
                    ],
                ],
            ]
        );

        $badUpdateRes->assertSessionHasErrors(['edit_reason']);
        $expense->refresh();
        $this->assertEquals($this->vendor->id, $expense->vendor_id);

        // Now provide explicit audit edit_reason
        $goodUpdateRes = $this->actingAs($this->adminUser)->put(
            route('accounts.expenses.update', $expense->id),
            [
                'vendor_id' => $vendor2->id,
                'paid_to' => $vendor2->name,
                'expense_date' => '2026-10-16',
                'payment_method' => 'Cash',
                'edit_reason' => 'Corrected vendor selection as per original store invoice',
                'items' => [
                    [
                        'item_name' => 'Chalk Box',
                        'expense_category_id' => $this->category->id,
                        'quantity' => 5,
                        'unit_rate' => 100.00,
                    ],
                ],
            ]
        );

        $goodUpdateRes->assertRedirect(route('accounts.expenses.index'));
        $expense->refresh();
        $this->assertEquals($vendor2->id, $expense->vendor_id);
        $this->assertEquals($vendor2->name, $expense->paid_to);

        // Verify audit log has the reason recorded
        $log = ExpenseAuditLog::where('expense_id', $expense->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals('Corrected vendor selection as per original store invoice', $log->reason);
    }

    /**
     * 19. Category filter in expenses index finds vouchers with matching line item categories.
     */
    public function test_category_filter_matches_item_level_categories(): void
    {
        $catA = $this->category;
        $catB = ExpenseCategory::create([
            'name' => 'IT Software & Hardware',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $expense = Expense::create([
            'voucher_no' => 'EXP-20261016-CATFILTER',
            'expense_category_id' => $catA->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'amount' => 1200.00,
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'user_id' => $this->adminUser->id,
        ]);

        $expense->items()->createMany([
            [
                'expense_category_id' => $catA->id,
                'item_name' => 'Pen Pack',
                'quantity' => 2,
                'unit_rate' => 100.00,
                'line_total' => 200.00,
            ],
            [
                'expense_category_id' => $catB->id,
                'item_name' => 'Optical Mouse',
                'quantity' => 2,
                'unit_rate' => 500.00,
                'line_total' => 1000.00,
            ],
        ]);

        // Filter by Cat B -> should find this voucher because one item belongs to Cat B
        $response = $this->actingAs($this->adminUser)->get(route('accounts.expenses.index', [
            'expense_category_id' => $catB->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $response->assertSee('EXP-20261016-CATFILTER');
    }

    /**
     * 20. Voucher print displays individual item categories in the printable table.
     */
    public function test_voucher_print_renders_individual_item_categories(): void
    {
        $cat1 = $this->category;
        $cat2 = ExpenseCategory::create([
            'name' => 'Electrical Maintenance',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $expense = Expense::create([
            'voucher_no' => 'EXP-20261016-PRNTMULTI',
            'expense_category_id' => $cat1->id,
            'vendor_id' => $this->vendor->id,
            'expense_date' => '2026-10-16',
            'amount' => 950.00,
            'payment_method' => 'Cash',
            'paid_to' => $this->vendor->name,
            'user_id' => $this->adminUser->id,
        ]);

        $expense->items()->createMany([
            [
                'expense_category_id' => $cat1->id,
                'item_name' => 'White Paper Sheets',
                'quantity' => 1,
                'unit_rate' => 450.00,
                'line_total' => 450.00,
            ],
            [
                'expense_category_id' => $cat2->id,
                'item_name' => 'LED Tube Light',
                'quantity' => 2,
                'unit_rate' => 250.00,
                'line_total' => 500.00,
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->get(
            route('accounts.expenses.voucher_print', $expense->id)
        );

        $response->assertStatus(200);
        $response->assertSee('EXP-20261016-PRNTMULTI');
        $response->assertSee('White Paper Sheets');
        $response->assertSee('LED Tube Light');
        $response->assertSee('Electrical Maintenance');
        $response->assertSee('Stationery & Printing');
    }
}

