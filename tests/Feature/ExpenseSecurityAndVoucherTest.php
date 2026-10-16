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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExpenseSecurityAndVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $studentUser;
    protected Branch $branch;
    protected ExpenseCategory $category;
    protected ExpenseReceiptService $receiptService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->receiptService = new ExpenseReceiptService();

        // Admin User
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_accounts@macsschool.com'],
            ['name' => 'Accounts Admin', 'password' => bcrypt('secret')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        // Regular Student/Viewer User (Unauthorized for finances)
        $this->studentUser = User::firstOrCreate(
            ['email' => 'student_viewer@macsschool.com'],
            ['name' => 'Student Viewer', 'password' => bcrypt('secret')]
        );
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);
        $this->studentUser->assignRole($studentRole);

        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Branch', 'user_id' => $this->adminUser->id]);

        $this->category = ExpenseCategory::create([
            'name' => 'Office Stationery',
            'description' => 'Printing papers and supplies',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * Test 1: Voucher number format matches EXP-YYYYMMDD-XXXXXX
     */
    public function test_voucher_number_has_expected_format(): void
    {
        $voucherNo = Expense::generateVoucherNo();

        $today = date('Ymd');
        $this->assertMatchesRegularExpression("/^EXP-{$today}-[A-F0-9]{6}$/", $voucherNo);
    }

    /**
     * Test 2: Consecutively generated voucher numbers are strictly unique
     */
    public function test_consecutive_voucher_numbers_are_all_unique(): void
    {
        $vouchers = [];
        for ($i = 0; $i < 30; $i++) {
            $voucherNo = Expense::generateVoucherNo();
            $this->assertNotContains($voucherNo, $vouchers);
            $vouchers[] = $voucherNo;

            // Save to DB to test collision avoidance against existing DB records
            Expense::create([
                'voucher_no' => $voucherNo,
                'expense_category_id' => $this->category->id,
                'branch_id' => $this->branch->id,
                'expense_date' => date('Y-m-d'),
                'amount' => 150.00,
                'payment_method' => 'Cash',
                'paid_to' => 'Office Shop',
                'user_id' => $this->adminUser->id,
            ]);
        }

        $this->assertCount(30, array_unique($vouchers));
    }

    /**
     * Test 3: Generator retries on collision and generates a unique voucher
     */
    public function test_voucher_generator_retries_on_collision_and_succeeds(): void
    {
        // Pre-create an expense with a fixed voucher number
        $fixedVoucher = 'EXP-' . date('Ymd') . '-A1B2C3';
        Expense::create([
            'voucher_no' => $fixedVoucher,
            'expense_category_id' => $this->category->id,
            'expense_date' => date('Y-m-d'),
            'amount' => 500.00,
            'paid_to' => 'Vendor',
            'user_id' => $this->adminUser->id,
        ]);

        $newVoucher = Expense::generateVoucherNo(10);
        $this->assertNotEquals($fixedVoucher, $newVoucher);
        $this->assertDatabaseMissing('expenses', ['voucher_no' => $newVoucher]);
    }

    /**
     * Test 4: Generator throws RuntimeException if bounded retries are exhausted
     */
    public function test_voucher_generator_throws_exception_when_retries_exhausted(): void
    {
        // Force collision by passing maxAttempts = 0
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate a unique voucher number after 0 attempts.');

        Expense::generateVoucherNo(0);
    }

    /**
     * Test 5: Valid receipt files (JPG, PNG, WebP, PDF) are stored in private storage
     */
    public function test_valid_receipt_files_are_stored_in_private_storage(): void
    {
        $files = [
            'receipt.jpg' => UploadedFile::fake()->image('receipt.jpg', 600, 800)->size(1500),
            'receipt.png' => UploadedFile::fake()->image('receipt.png', 600, 800)->size(2000),
            'receipt.webp' => UploadedFile::fake()->image('receipt.webp', 600, 800)->size(1000),
            'bill.pdf' => UploadedFile::fake()->create('bill.pdf', 3000, 'application/pdf'),
        ];

        foreach ($files as $name => $file) {
            $path = $this->receiptService->storeReceipt($file);

            // Stored in private 'local' disk
            Storage::disk('local')->assertExists($path);

            // NEVER in 'public' disk
            Storage::disk('public')->assertMissing($path);

            // Stored under expense_receipts directory
            $this->assertStringStartsWith('expense_receipts/', $path);

            // Randomized name does not match original name (prevents path traversal)
            $this->assertStringNotContainsString($name, $path);
        }
    }

    /**
     * Test 6: Rejects invalid file types (e.g. PHP scripts, executables, or disguised extensions)
     */
    public function test_receipt_upload_rejects_invalid_file_types(): void
    {
        $invalidFiles = [
            'malicious.php' => UploadedFile::fake()->create('malicious.php', 100, 'application/x-php'),
            'script.sh'     => UploadedFile::fake()->create('script.sh', 50, 'text/x-shellscript'),
            'disguised.jpg' => UploadedFile::fake()->create('disguised.jpg', 50, 'text/plain'), // extension jpg but mime text
            'document.exe'  => UploadedFile::fake()->create('document.exe', 500, 'application/x-msdownload'),
        ];

        foreach ($invalidFiles as $file) {
            try {
                $this->receiptService->storeReceipt($file);
                $this->fail("Expected ValidationException for {$file->getClientOriginalName()}");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('receipt', $e->errors());
            }
        }
    }

    /**
     * Test 7: Rejects oversized files (> 5 MB)
     */
    public function test_receipt_upload_rejects_files_larger_than_5mb(): void
    {
        $oversizedFile = UploadedFile::fake()->create('large_bill.pdf', 5121, 'application/pdf'); // 5121 KB > 5120 KB

        $this->expectException(ValidationException::class);
        $this->receiptService->storeReceipt($oversizedFile);
    }

    /**
     * Test 8: Authorized users can stream private receipts
     */
    public function test_authorized_user_can_stream_private_receipt(): void
    {
        $file = UploadedFile::fake()->image('valid_receipt.png', 400, 400);
        $path = $this->receiptService->storeReceipt($file);

        $expense = Expense::create([
            'voucher_no' => 'EXP-' . date('Ymd') . '-112233',
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => date('Y-m-d'),
            'amount' => 750.00,
            'payment_method' => 'Bank',
            'paid_to' => 'Electricity Board',
            'receipt_file' => $path,
            'user_id' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get(route('expenses.receipt.view', $expense->id));

        $response->assertOk();
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    /**
     * Test 9: Unauthenticated guest is blocked from streaming receipts
     */
    public function test_unauthenticated_user_is_blocked_from_streaming_receipts(): void
    {
        $expense = Expense::create([
            'voucher_no' => 'EXP-' . date('Ymd') . '-445566',
            'expense_category_id' => $this->category->id,
            'expense_date' => date('Y-m-d'),
            'amount' => 200.00,
            'paid_to' => 'Shop',
            'receipt_file' => 'expense_receipts/dummy.png',
            'user_id' => $this->adminUser->id,
        ]);

        auth()->logout();

        $response = $this->get(route('expenses.receipt.view', $expense->id));

        // Redirects unauthenticated users to login/tyro-login
        $this->assertTrue($response->isRedirect());
        $this->assertTrue(in_array($response->headers->get('Location'), [route('login'), url('/tyro-login'), url('/login')]));
    }

    /**
     * Test 10: Unauthorized role (student) is forbidden from viewing receipts
     */
    public function test_unauthorized_role_is_forbidden_from_viewing_receipts(): void
    {
        $expense = Expense::create([
            'voucher_no' => 'EXP-' . date('Ymd') . '-778899',
            'expense_category_id' => $this->category->id,
            'expense_date' => date('Y-m-d'),
            'amount' => 300.00,
            'paid_to' => 'Shop',
            'receipt_file' => 'expense_receipts/dummy.png',
            'user_id' => $this->adminUser->id,
        ]);

        $this->actingAs($this->studentUser);

        $response = $this->get(route('expenses.receipt.view', $expense->id));

        // Forbidden or redirected by Tyro roles middleware
        $this->assertTrue(in_array($response->status(), [403, 302]));
    }

    /**
     * Test 11: Missing receipt file returns 404
     */
    public function test_missing_receipt_file_returns_404(): void
    {
        $expense = Expense::create([
            'voucher_no' => 'EXP-' . date('Ymd') . '-001122',
            'expense_category_id' => $this->category->id,
            'expense_date' => date('Y-m-d'),
            'amount' => 100.00,
            'paid_to' => 'Shop',
            'receipt_file' => 'expense_receipts/non_existent.pdf',
            'user_id' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get(route('expenses.receipt.view', $expense->id));

        $response->assertNotFound();
    }

    /**
     * Test 12: Safely deletes receipt file from private storage
     */
    public function test_delete_receipt_file_removes_it_from_private_storage(): void
    {
        $file = UploadedFile::fake()->create('delete_me.pdf', 500, 'application/pdf');
        $path = $this->receiptService->storeReceipt($file);

        Storage::disk('local')->assertExists($path);

        $deleted = $this->receiptService->deleteReceiptFile($path);

        $this->assertTrue($deleted);
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Test 13: Audit log captures permanent snapshot and retains voucher after expense is deleted
     */
    public function test_audit_log_persists_voucher_and_operator_after_expense_is_deleted(): void
    {
        $voucherNo = 'EXP-' . date('Ymd') . '-AUDIT1';
        $expense = Expense::create([
            'voucher_no' => $voucherNo,
            'expense_category_id' => $this->category->id,
            'branch_id' => $this->branch->id,
            'expense_date' => '2026-10-15',
            'amount' => 1250.75,
            'payment_method' => 'bKash',
            'paid_to' => 'Internet ISP Provider',
            'user_id' => $this->adminUser->id,
        ]);

        // 1. Record creation audit log
        $auditLog = ExpenseAuditLog::record(
            expense: $expense,
            action: 'created',
            operator: $this->adminUser,
            reason: 'Monthly ISP bill payment'
        );

        $this->assertDatabaseHas('expense_audit_logs', [
            'id' => $auditLog->id,
            'expense_id' => $expense->id,
            'voucher_no' => $voucherNo,
            'action' => 'created',
            'amount' => 1250.75,
            'payment_method' => 'bKash',
            'paid_to' => 'Internet ISP Provider',
            'operator_name' => 'Accounts Admin',
            'reason' => 'Monthly ISP bill payment',
        ]);

        // 2. Delete the expense
        $expense->delete();

        // 3. Audit log MUST still exist, with expense_id set to NULL but voucher_no & operator_name intact
        $auditLog->refresh();
        $this->assertNull($auditLog->expense_id);
        $this->assertEquals($voucherNo, $auditLog->voucher_no);
        $this->assertEquals('Accounts Admin', $auditLog->operator_name);
        $this->assertEquals(1250.75, (float) $auditLog->amount);
    }
}
