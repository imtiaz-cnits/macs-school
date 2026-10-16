<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseAuditLog;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\ExpenseReceiptService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $editorUser;
    protected User $studentUser;
    protected Branch $branch;
    protected ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Super Admin User
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_test@macsschool.com'],
            ['name' => 'Admin Tester', 'password' => bcrypt('password')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        // Editor User
        $this->editorUser = User::firstOrCreate(
            ['email' => 'editor_test@macsschool.com'],
            ['name' => 'Editor Tester', 'password' => bcrypt('password')]
        );
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $this->editorUser->assignRole($editorRole);

        // Unauthorized Student User
        $this->studentUser = User::firstOrCreate(
            ['email' => 'student_test@macsschool.com'],
            ['name' => 'Student Tester', 'password' => bcrypt('password')]
        );
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->studentUser->assignRole($studentRole);

        // Sample Branch
        $this->branch = Branch::firstOrCreate(
            ['id' => 1],
            ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]
        );

        // Sample Category
        $this->category = ExpenseCategory::create([
            'name' => 'Office Supplies',
            'description' => 'Paper, toner and pens',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Expense Category Tests
    |--------------------------------------------------------------------------
    */

    public function test_authorized_user_can_view_expense_categories_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('accounts.categories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.accounts.categories');
        $response->assertSee('Office Supplies');
    }

    public function test_unauthorized_user_is_forbidden_from_expense_categories(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('accounts.categories.index'));

        // Tyro role middleware blocks unauthorized users by returning 403 or redirecting away
        $this->assertTrue(in_array($response->status(), [403, 302]));
        if ($response->status() === 302) {
            $this->assertNotEquals(route('accounts.categories.index'), $response->headers->get('Location'));
        }
    }

    public function test_authorized_user_can_create_expense_category(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('accounts.categories.store'), [
            'name' => 'Utility Bills',
            'description' => 'Electricity, Water, Internet',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('accounts.categories.index'));
        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Utility Bills',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_category_creation_fails_on_duplicate_name(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('accounts.categories.store'), [
            'name' => 'Office Supplies',
            'status' => 'Active',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_authorized_user_can_update_expense_category(): void
    {
        $response = $this->actingAs($this->adminUser)->put(route('accounts.categories.update', $this->category->id), [
            'name' => 'General Stationery',
            'description' => 'Updated description',
            'status' => 'Inactive',
        ]);

        $response->assertRedirect(route('accounts.categories.index'));
        $this->assertDatabaseHas('expense_categories', [
            'id' => $this->category->id,
            'name' => 'General Stationery',
            'status' => 'Inactive',
        ]);
    }

    public function test_category_cannot_be_deleted_if_expenses_are_attached(): void
    {
        // Attach an expense to category
        Expense::create([
            'voucher_no' => 'EXP-20261015-000001',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 500.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Local Shop',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('accounts.categories.destroy', $this->category->id));

        $response->assertRedirect(route('accounts.categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('expense_categories', ['id' => $this->category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $unusedCategory = ExpenseCategory::create([
            'name' => 'Unused Category',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('accounts.categories.destroy', $unusedCategory->id));

        $response->assertRedirect(route('accounts.categories.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('expense_categories', ['id' => $unusedCategory->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Expense CRUD, Transactions & Audit Logs
    |--------------------------------------------------------------------------
    */

    public function test_authorized_user_can_view_expenses_list_with_summary_totals(): void
    {
        // Create an expense for today
        Expense::create([
            'voucher_no' => 'EXP-20261015-111111',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => date('Y-m-d'),
            'amount' => 1250.00,
            'payment_method' => 'Cash',
            'paid_to' => 'City Hardware',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('accounts.expenses.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.accounts.expenses');
        $response->assertViewHas('todayTotal', 1250.00);
        $response->assertViewHas('filteredTotal', 1250.00);
        $response->assertSee('EXP-20261015-111111');
        $response->assertSee('City Hardware');
    }

    public function test_expense_can_be_created_with_audit_log_and_unique_voucher(): void
    {
        $file = UploadedFile::fake()->create('bill.jpg', 300, 'image/jpeg');

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 3500.50,
            'payment_method' => 'Bank',
            'paid_to' => 'Rahman Printers',
            'reference_no' => 'CHQ-987654',
            'receipt' => $file,
            'note' => 'Exam question paper printing',
        ]);

        $response->assertRedirect(route('accounts.expenses.index'));
        $response->assertSessionHas('success');

        $expense = Expense::where('paid_to', 'Rahman Printers')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(3500.50, (float) $expense->amount);
        $this->assertStringStartsWith('EXP-', $expense->voucher_no);
        $this->assertNotNull($expense->receipt_file);
        Storage::disk('local')->assertExists($expense->receipt_file);

        // Verify creation audit log
        $this->assertDatabaseHas('expense_audit_logs', [
            'expense_id' => $expense->id,
            'voucher_no' => $expense->voucher_no,
            'action' => 'created',
            'operator_name' => $this->adminUser->name,
        ]);
    }

    public function test_expense_update_replaces_receipt_safely_and_creates_audit_log(): void
    {
        $oldFile = UploadedFile::fake()->create('old_receipt.jpg', 200, 'image/jpeg');
        $oldStoredPath = (new ExpenseReceiptService())->storeReceipt($oldFile);

        $expense = Expense::create([
            'voucher_no' => 'EXP-20261015-222222',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 800.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Shop A',
            'receipt_file' => $oldStoredPath,
            'user_id' => $this->adminUser->id,
        ]);

        Storage::disk('local')->assertExists($oldStoredPath);

        $newFile = UploadedFile::fake()->create('new_receipt.pdf', 400, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->put(route('accounts.expenses.update', $expense->id), [
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-16',
            'amount' => 950.00,
            'payment_method' => 'bKash',
            'paid_to' => 'Shop A Prime',
            'receipt' => $newFile,
            'edit_reason' => 'Updated bill amount and attached PDF invoice',
        ]);

        $response->assertRedirect(route('accounts.expenses.index'));

        $expense->refresh();
        $this->assertEquals(950.00, (float) $expense->amount);
        $this->assertEquals('Shop A Prime', $expense->paid_to);
        $this->assertNotEquals($oldStoredPath, $expense->receipt_file);

        // Old file must be deleted from storage
        Storage::disk('local')->assertMissing($oldStoredPath);
        // New file must exist
        Storage::disk('local')->assertExists($expense->receipt_file);

        // Audit log must track update snapshot with reason
        $this->assertDatabaseHas('expense_audit_logs', [
            'expense_id' => $expense->id,
            'voucher_no' => 'EXP-20261015-222222',
            'action' => 'updated',
            'reason' => 'Updated bill amount and attached PDF invoice',
            'operator_name' => $this->adminUser->name,
        ]);
    }

    public function test_expense_deletion_preserves_snapshot_in_audit_logs(): void
    {
        $file = UploadedFile::fake()->create('receipt.png', 200, 'image/png');
        $storedPath = (new ExpenseReceiptService())->storeReceipt($file);

        $expense = Expense::create([
            'voucher_no' => 'EXP-20261015-333333',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 2400.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Electrician Rafiq',
            'receipt_file' => $storedPath,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('accounts.expenses.destroy', $expense->id), [
            'delete_reason' => 'Erroneously recorded twice',
        ]);

        $response->assertRedirect(route('accounts.expenses.index'));

        // Expense record is deleted from expenses table
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);

        // File is cleaned up
        Storage::disk('local')->assertMissing($storedPath);

        // Permanent audit log snapshot persists
        $this->assertDatabaseHas('expense_audit_logs', [
            'voucher_no' => 'EXP-20261015-333333',
            'action' => 'deleted',
            'category_name' => $this->category->name,
            'amount' => 2400.00,
            'reason' => 'Erroneously recorded twice',
            'operator_name' => $this->adminUser->name,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Filtering & Printing
    |--------------------------------------------------------------------------
    */

    public function test_expenses_filter_by_date_category_and_method(): void
    {
        $otherCategory = ExpenseCategory::create([
            'name' => 'Transport',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        Expense::create([
            'voucher_no' => 'EXP-20261001-000001',
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-10-01',
            'amount' => 100.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Target 1',
            'user_id' => $this->adminUser->id,
        ]);

        Expense::create([
            'voucher_no' => 'EXP-20261010-000002',
            'expense_category_id' => $otherCategory->id,
            'expense_date' => '2026-10-10',
            'amount' => 500.00,
            'payment_method' => 'Bank',
            'paid_to' => 'Target 2',
            'user_id' => $this->adminUser->id,
        ]);

        // Query filtering only Transport category
        $response = $this->actingAs($this->adminUser)->get(route('accounts.expenses.index', [
            'expense_category_id' => $otherCategory->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Target 2');
        $response->assertDontSee('Target 1');
        $response->assertViewHas('filteredTotal', 500.00);
    }

    public function test_expense_creation_cleans_up_uploaded_file_if_database_transaction_fails(): void
    {
        $file = UploadedFile::fake()->create('bill_fail.jpg', 250, 'image/jpeg');

        // Hook into model event to simulate unexpected database/logic exception
        Expense::creating(function () {
            throw new \RuntimeException('Simulated database write failure');
        });

        $response = $this->actingAs($this->adminUser)->post(route('accounts.expenses.store'), [
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 500.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Failure Test Store',
            'receipt' => $file,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('expenses', ['paid_to' => 'Failure Test Store']);

        // Assert all files in local storage are cleaned up (no orphaned files left)
        $files = Storage::disk('local')->allFiles('expense_receipts');
        $this->assertEmpty($files, 'Orphaned receipt file was not cleaned up during rollback.');
    }

    public function test_expense_update_cleans_up_new_file_and_preserves_old_file_if_database_fails(): void
    {
        $oldFile = UploadedFile::fake()->create('original.jpg', 200, 'image/jpeg');
        $oldStoredPath = (new ExpenseReceiptService())->storeReceipt($oldFile);

        $expense = Expense::create([
            'voucher_no' => 'EXP-20261015-444444',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 600.00,
            'payment_method' => 'Cash',
            'paid_to' => 'Initial Recipient',
            'receipt_file' => $oldStoredPath,
            'user_id' => $this->adminUser->id,
        ]);

        Storage::disk('local')->assertExists($oldStoredPath);

        // Hook into model updating event to simulate database failure
        Expense::updating(function () {
            throw new \RuntimeException('Simulated database update failure');
        });

        $newFile = UploadedFile::fake()->create('replacement.pdf', 350, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->put(route('accounts.expenses.update', $expense->id), [
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-16',
            'amount' => 850.00,
            'payment_method' => 'Bank',
            'paid_to' => 'Changed Recipient',
            'edit_reason' => 'Testing file cleanup on DB failure',
            'receipt' => $newFile,
        ]);

        $response->assertSessionHas('error');

        // Old file must still exist
        Storage::disk('local')->assertExists($oldStoredPath);

        // Expense record in database must remain unmodified
        $expense->refresh();
        $this->assertEquals('Initial Recipient', $expense->paid_to);
        $this->assertEquals($oldStoredPath, $expense->receipt_file);
    }

    public function test_voucher_print_view_renders_correctly(): void
    {
        $expense = Expense::create([
            'voucher_no' => 'EXP-20261015-999999',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 1750.00,
            'payment_method' => 'Nagad',
            'paid_to' => 'Internet Service Provider',
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('accounts.expenses.voucher_print', $expense->id));

        $response->assertStatus(200);
        $response->assertViewIs('pages.accounts.voucher_print');
        $response->assertSee('EXP-20261015-999999');
        $response->assertSee('Internet Service Provider');
        $response->assertSee('OFFICE COPY');
        $response->assertSee('PAYEE COPY');
    }
}
