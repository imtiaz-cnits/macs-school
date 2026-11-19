<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentController;
use App\Models\AdmissionFeeStructure;
use App\Models\AdmissionInvoiceHeadAllocation;
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
use App\Services\AdmissionFeeStructureService;
use App\Services\AdmissionInvoiceService;
use App\Services\FeeGenerationService;
use App\Services\FinancialTransactionRunner;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PDOException;
use Tests\TestCase;

class AdmissionInvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    public static bool $allowSqliteIdentityLock = false;

    /** @var array<int, bool> */
    private static array $registeredLockPdoIds = [];

    /** @var callable|null */
    public static $advisoryLockObserver = null;

    /** @var array<int, string> */
    public static array $advisoryLockEvents = [];

    protected User $adminUser;
    protected User $editorUser;
    protected Branch $branchA;
    protected Branch $branchB;
    protected SessionYear $session2026;
    protected SessionYear $session2027;
    protected Classes $classOne;
    protected Classes $classTwo;
    protected Section $sectionA;
    protected FeeCategory $admissionCat;
    protected FeeCategory $sessionCat;
    protected FeeCategory $monthlyCat;
    protected AdmissionFeeStructureService $structureService;
    protected AdmissionInvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->structureService = app(AdmissionFeeStructureService::class);
        $this->invoiceService = app(AdmissionInvoiceService::class);

        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);

        $this->adminUser = User::create([
            'name' => 'Admission Admin',
            'email' => 'adm_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->editorUser = User::create([
            'name' => 'Admission Editor',
            'email' => 'adm_editor@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->editorUser->assignRole($editorRole);

        $this->branchA = Branch::create(['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->branchB = Branch::create(['branch_name' => 'City Campus', 'user_id' => $this->adminUser->id]);

        $this->session2026 = SessionYear::create(['session_name' => '2026', 'user_id' => $this->adminUser->id]);
        $this->session2027 = SessionYear::create(['session_name' => '2027', 'user_id' => $this->adminUser->id]);

        $this->classOne = Classes::create(['class_name' => 'One', 'user_id' => $this->adminUser->id]);
        $this->classTwo = Classes::create(['class_name' => 'Two', 'user_id' => $this->adminUser->id]);

        $this->sectionA = Section::create([
            'section_name' => 'A',
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'user_id' => $this->adminUser->id,
        ]);

        $this->admissionCat = FeeCategory::create(['name' => 'Admission Fee', 'status' => 'Active', 'user_id' => $this->adminUser->id]);
        $this->sessionCat = FeeCategory::create(['name' => 'Session Fee', 'status' => 'Active', 'user_id' => $this->adminUser->id]);
        $this->monthlyCat = FeeCategory::create(['name' => 'Monthly Fee', 'status' => 'Active', 'user_id' => $this->adminUser->id]);
    }

    protected function tearDown(): void
    {
        self::$allowSqliteIdentityLock = false;
        self::$advisoryLockObserver = null;
        self::$advisoryLockEvents = [];
        StudentController::$enableTestAdvisoryIdentityLock = false;

        parent::tearDown();
    }

    public function test_student_admission_selects_active_structure_and_generates_single_multi_head_invoice_atomically(): void
    {
        $structure = $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        // Active structure in a different branch must never interfere
        $this->createMultiHeadStructure($this->branchB, $this->classOne, $this->session2026, '4999.00');

        $this->actingAs($this->adminUser);

        $response = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '101',
        ]));

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $student = Student::where('student_identity', $response->json('identity'))->firstOrFail();

        $invoices = FeeInvoice::where('student_id', $student->id)->get();
        $this->assertCount(1, $invoices, 'Admission must generate exactly ONE multi-head Admission invoice.');

        $invoice = $invoices->first();
        $this->assertNull($invoice->fee_setup_id);
        $this->assertSame($structure->id, (int) $invoice->admission_fee_structure_id);
        $this->assertSame($this->branchA->id, (int) $invoice->branch_id);
        $this->assertSame($this->session2026->id, (int) $invoice->session_year_id);
        $this->assertSame('Main Campus', $invoice->branch_name_snapshot);
        $this->assertMatchesRegularExpression(FeeInvoice::ADMISSION_INVOICE_PATTERN, $invoice->invoice_no);
        $this->assertSame(0, bccomp('4500.00', (string) $invoice->amount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $invoice->discount, 2));
        $this->assertSame(0, bccomp('4500.00', (string) $invoice->net_amount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $invoice->paid_amount, 2));
        $this->assertSame(0, bccomp('4500.00', (string) $invoice->due_amount, 2));
        $this->assertSame('Unpaid', $invoice->status);
        $this->assertSame('One Time', $invoice->month_name);
        $this->assertSame(date('Y-m'), $invoice->billing_period);

        // Head allocations created atomically
        $heads = $invoice->admissionHeadAllocations()->get();
        $this->assertCount(3, $heads);
        $this->assertSame(0, FeePayment::where('student_id', $student->id)->count());
    }

    public function test_falls_back_to_legacy_fee_setup_when_no_active_admission_structure_exists(): void
    {
        // Only legacy Admission Fee setup exists for Class Two
        $legacySetup = FeeSetup::create([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classTwo->id,
            'session_year_id' => $this->session2026->id,
            'fee_category_id' => $this->admissionCat->id,
            'amount' => 1200.00,
            'fee_month' => null,
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'class_id' => $this->classTwo->id,
            'roll_number' => '102',
        ]));

        $response->assertStatus(201);
        $student = Student::where('student_identity', $response->json('identity'))->firstOrFail();

        $invoice = FeeInvoice::where('student_id', $student->id)->firstOrFail();
        $this->assertSame($legacySetup->id, (int) $invoice->fee_setup_id);
        $this->assertNull($invoice->admission_fee_structure_id);
        $this->assertSame(0, bccomp('1200.00', (string) $invoice->net_amount, 2));
        $this->assertCount(0, $invoice->admissionHeadAllocations);
    }

    public function test_prevents_duplicate_active_multi_head_admission_invoice_in_same_scope(): void
    {
        $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        $student = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'DUP-001');

        $first = $this->invoiceService->generateForStudent($student, [], $this->adminUser->id);
        $this->assertTrue($first['created']);

        try {
            $this->invoiceService->generateForStudent($student, [], $this->adminUser->id);
            $this->fail('Expected ValidationException on duplicate active Admission invoice.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('admission_invoice', $e->errors());
        }

        $this->assertSame(1, FeeInvoice::where('student_id', $student->id)->count());
    }

    public function test_prevents_multi_head_admission_invoice_while_active_legacy_admission_invoice_exists(): void
    {
        $legacySetup = FeeSetup::create([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'fee_category_id' => $this->admissionCat->id,
            'amount' => 1000.00,
            'fee_month' => null,
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        $student = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'LEG-OVR-001');

        $legacyRes = app(FeeGenerationService::class)->generateAdmissionFeeForNewStudent($student, $this->adminUser->id);
        $this->assertTrue($legacyRes['created']);
        $legacyInvoice = $legacyRes['invoice'];

        // Now activate multi-head structure in the exact same scope
        $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);

        // Policy 3: Blocked while legacy invoice is active
        try {
            $this->invoiceService->generateForStudent($student, [], $this->adminUser->id);
            $this->fail('Expected ValidationException due to active legacy Admission Fee overlap.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('admission_invoice', $e->errors());
            $this->assertStringContainsString($legacyInvoice->invoice_no, $e->errors()['admission_invoice'][0]);
        }

        // Cancel legacy invoice -> now multi-head generation succeeds
        $legacyInvoice->update(['status' => 'Cancelled', 'due_amount' => 0.00]);
        $newRes = $this->invoiceService->generateForStudent($student, [], $this->adminUser->id);
        $this->assertTrue($newRes['created']);
        $this->assertNotNull($newRes['invoice']->admission_fee_structure_id);
    }

    public function test_invoice_and_head_snapshots_remain_immutable_after_structure_version_update(): void
    {
        $v1 = $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        $student1 = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'SNAP-001');

        $res1 = $this->invoiceService->generateForStudent($student1, [
            'discount_type' => 'fixed',
            'discount_value' => '300.00',
        ], $this->adminUser->id);
        $inv1 = $res1['invoice'];

        // Update structure to V2 with completely different head amounts & names
        $v2 = $this->structureService->updateStructureWithVersioning($v1->id, [
            'structure_name' => 'Revised V2 Package',
            'includes_monthly_tuition' => false,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Revised Admission Base V2',
                    'amount' => '8000.00',
                    'is_discountable' => true,
                    'sort_order' => 10,
                ],
            ],
        ], $this->adminUser->id);

        // Also rename master category/branch after invoice issuance
        $this->admissionCat->update(['name' => 'Renamed Admission Master']);
        $this->branchA->update(['branch_name' => 'Renamed Campus Alpha']);

        // Reload V1 historical invoice: amounts, snapshots, and feeSetup->withDefault() stay frozen
        $inv1Fresh = FeeInvoice::with(['admissionHeadAllocations', 'admissionFeeStructure'])
            ->findOrFail($inv1->id);

        $this->assertSame($v1->id, (int) $inv1Fresh->admission_fee_structure_id);
        $this->assertSame(0, bccomp('4500.00', (string) $inv1Fresh->amount, 2));
        $this->assertSame(0, bccomp('300.00', (string) $inv1Fresh->discount, 2));
        $this->assertSame(0, bccomp('4200.00', (string) $inv1Fresh->net_amount, 2));
        $this->assertSame('Main Campus', $inv1Fresh->branch_name_snapshot);
        $this->assertSame('Main Campus', $inv1Fresh->feeSetup->branch->branch_name);
        $this->assertSame('Admission Fee', $inv1Fresh->feeSetup->category->name);
        $this->assertCount(3, $inv1Fresh->admissionHeadAllocations);
        $this->assertSame('Admission Registration', $inv1Fresh->admissionHeadAllocations[0]->head_name_snapshot);

        // New student picks up V2
        $student2 = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'SNAP-002');
        $res2 = $this->invoiceService->generateForStudent($student2, [], $this->adminUser->id);
        $this->assertSame($v2->id, (int) $res2['invoice']->admission_fee_structure_id);
        $this->assertSame(0, bccomp('8000.00', (string) $res2['invoice']->net_amount, 2));
    }

    public function test_cancelled_admission_invoice_regenerates_with_v2_and_v3_suffixes(): void
    {
        $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        $student = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'REGEN-001');

        $inv1 = $this->invoiceService->generateForStudent($student, [
            'issue_date' => '2026-01-15',
        ], $this->adminUser->id)['invoice'];

        $expectedBase = "INV-ADM-S{$this->session2026->id}-202601-{$student->id}";
        $this->assertSame($expectedBase, $inv1->invoice_no);

        // Cancel unpaid V1
        $cancelled1 = $this->invoiceService->cancelUnpaidInvoice($inv1->id, 'Structure change requested', $this->adminUser->id);
        $this->assertSame('Cancelled', $cancelled1->status);
        $this->assertSame(0, bccomp('0.00', (string) $cancelled1->due_amount, 2));
        $this->assertDatabaseHas('fee_adjustment_logs', [
            'fee_invoice_id' => $inv1->id,
            'mode' => 'set_amount',
            'scope' => 'single',
        ]);

        // Regenerate -> gets -V2
        $inv2 = $this->invoiceService->generateForStudent($student, [
            'issue_date' => '2026-01-20',
        ], $this->adminUser->id)['invoice'];

        $this->assertSame("{$expectedBase}-V2", $inv2->invoice_no);
        $this->assertSame('2026-01', $inv2->billing_period);
        $this->assertSame('One Time', $inv2->month_name);

        // Cancel -V2 and regenerate -> gets -V3
        $this->invoiceService->cancelUnpaidInvoice($inv2->id, 'Second cancellation', $this->adminUser->id);
        $inv3 = $this->invoiceService->generateForStudent($student, [
            'issue_date' => '2026-01-25',
        ], $this->adminUser->id)['invoice'];

        $this->assertSame("{$expectedBase}-V3", $inv3->invoice_no);
    }

    public function test_enforces_editor_versus_admin_discount_boundary_and_cleans_up_media(): void
    {
        Storage::fake('public');
        $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);

        // 1. Editor WITHOUT discount succeeds cleanly with 0.00 discount
        $this->actingAs($this->editorUser);
        $okRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '201',
        ]));
        $okRes->assertStatus(201);
        $editorStudent = Student::where('student_identity', $okRes->json('identity'))->firstOrFail();
        $editorInvoice = FeeInvoice::where('student_id', $editorStudent->id)->firstOrFail();
        $this->assertSame(0, bccomp('0.00', (string) $editorInvoice->discount, 2));
        $this->assertSame(0, bccomp('4500.00', (string) $editorInvoice->net_amount, 2));

        // 2. Editor WITH non-zero fixed discount gets 403, zero student created, uploaded file clean
        $blockedPhoto = UploadedFile::fake()->image('editor_blocked.jpg');
        $blockedRes = $this->post(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '202',
            'photo' => $blockedPhoto,
            'admission_discount_type' => 'fixed',
            'admission_discount_value' => '200.00',
        ]), ['Accept' => 'application/json']);

        $blockedRes->assertStatus(403);
        $this->assertDatabaseMissing('students', ['roll_number' => '202']);
        $this->assertEmpty(Storage::disk('public')->allFiles());

        // 3. Service-level defense in depth also blocks Editor discount directly
        try {
            $this->invoiceService->generateForStudent($this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'ED-SVC-01'), [
                'discount_type' => 'percentage',
                'discount_value' => '10',
            ], $this->editorUser->id);
            $this->fail('Expected AuthorizationException from AdmissionInvoiceService for Editor discount.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Only Admin and Super Admin', $e->getMessage());
        }

        // 4. Admin WITH valid percentage discount succeeds cleanly (10% on 3000.00 discountable = 300.00)
        $this->actingAs($this->adminUser);
        $adminRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '203',
            'admission_discount_type' => 'percentage',
            'admission_discount_value' => '10.00',
        ]));
        $adminRes->assertStatus(201);
        $adminStudent = Student::where('student_identity', $adminRes->json('identity'))->firstOrFail();
        $adminInvoice = FeeInvoice::where('student_id', $adminStudent->id)->firstOrFail();
        $this->assertSame(0, bccomp('300.00', (string) $adminInvoice->discount, 2));
        $this->assertSame(0, bccomp('4200.00', (string) $adminInvoice->net_amount, 2));
    }

    public function test_student_and_invoice_creation_rolls_back_atomically_and_retries_deadlocks(): void
    {
        Storage::fake('public');
        $structure = $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        // Eligible discountable = 3000.00, total gross = 4500.00 -> fixed 3500.00 fails in AdmissionInvoiceService AFTER Student::create!

        $this->actingAs($this->adminUser);
        $photo = UploadedFile::fake()->image('rollback_avatar.jpg');

        $failedRes = $this->post(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '301',
            'photo' => $photo,
            'admission_discount_type' => 'fixed',
            'admission_discount_value' => '3500.00',
        ]), ['Accept' => 'application/json']);

        $failedRes->assertStatus(422);
        // Student must NOT exist (atomically rolled back with invoice failure) and uploaded photo deleted
        $this->assertDatabaseMissing('students', ['roll_number' => '301']);
        $this->assertSame(0, FeeInvoice::where('admission_fee_structure_id', $structure->id)->count());
        $this->assertEmpty(Storage::disk('public')->allFiles());

        // Verify deadlock retry atomicity inside FinancialTransactionRunner for composite Student + AdmissionInvoice
        $attempts = 0;
        $runner = app(FinancialTransactionRunner::class);
        $invoice = $runner->runWithDeadlockRetry(function () use (&$attempts): FeeInvoice {
            $attempts++;

            $s = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'DLK-ATOMIC-01');
            $res = $this->invoiceService->generateForStudentWithinTransaction($s, [], $this->adminUser->id);

            if ($attempts === 1) {
                $pdoEx = new PDOException('Deadlock found when trying to get lock', 40001);
                $pdoEx->errorInfo = ['40001', 1213, 'Deadlock'];
                throw $pdoEx;
            }

            return $res['invoice'];
        }, maxAttempts: 3, baseDelayMs: 0);

        $this->assertSame(2, $attempts);
        $this->assertSame(1, Student::where('student_identity', 'DLK-ATOMIC-01')->count());
        $this->assertSame(1, FeeInvoice::where('student_id', $invoice->student_id)->count());
        $this->assertSame(3, AdmissionInvoiceHeadAllocation::where('fee_invoice_id', $invoice->id)->count());
    }

    public function test_student_profile_view_and_discontinuation_handle_admission_invoices_safely(): void
    {
        $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        $student = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'DISC-ADM-01');

        $invoice = $this->invoiceService->generateForStudent($student, [
            'issue_date' => '2026-04-12',
        ], $this->adminUser->id)['invoice'];

        $this->actingAs($this->adminUser);

        // 1. Show endpoint exposes admission_head_allocations & snapshot fee_setup cleanly
        $showRes = $this->getJson(route('students.show', $student->id));
        $showRes->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'invoices')
            ->assertJsonCount(3, 'invoices.0.admission_head_allocations');

        // 2. Discontinue effective 2026-03 cancels unpaid 2026-04 Admission Invoice & keeps head math intact
        $discRes = $this->postJson(route('students.discontinue.reconcile', $student->id), [
            'last_active_month' => '2026-03',
            'reason' => 'Transferred out before April',
            'confirm' => true,
        ]);

        $discRes->assertOk()
            ->assertJsonPath('cancelled_count', 1);

        $invoice->refresh();
        $this->assertSame('Cancelled', $invoice->status);
        $this->assertSame(0, bccomp('0.00', (string) $invoice->due_amount, 2));
        // Head allocations retain net_amount === due_amount (chk_aiha_math invariant intact)
        foreach ($invoice->admissionHeadAllocations as $head) {
            $this->assertSame(0, bccomp((string) $head->net_amount, (string) $head->due_amount, 2));
        }

        // 3. Paid/Partial Admission Invoice must NEVER be cancelled by discontinuation
        $studentPaid = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'DISC-ADM-02');
        $paidInvoice = $this->invoiceService->generateForStudent($studentPaid, [
            'issue_date' => '2026-04-12',
        ], $this->adminUser->id)['invoice'];

        $paidInvoice->update(['status' => 'Partial', 'paid_amount' => '1000.00', 'due_amount' => '3500.00']);
        FeePayment::create([
            'receipt_no' => 'REC-20260412-0001',
            'fee_invoice_id' => $paidInvoice->id,
            'student_id' => $studentPaid->id,
            'branch_id' => $this->branchA->id,
            'paid_amount' => '1000.00',
            'payment_date' => '2026-04-12',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);

        $discPaidRes = $this->postJson(route('students.discontinue.reconcile', $studentPaid->id), [
            'last_active_month' => '2026-03',
            'reason' => 'Partial transfer',
            'confirm' => true,
        ]);

        $discPaidRes->assertOk()
            ->assertJsonPath('cancelled_count', 0)
            ->assertJsonCount(1, 'skipped_paid_invoices')
            ->assertJsonPath('skipped_paid_invoices.0.receipts.0', 'REC-20260412-0001');

        $this->assertSame('Partial', $paidInvoice->fresh()->status);
    }

    public function test_zero_net_100_percent_discounted_admission_invoice_is_not_marked_paid_and_supports_full_lifecycle(): void
    {
        // All heads discountable (2000 + 1000 = 3000.00 gross) -> 100% discount produces 0.00 net
        $structure = $this->structureService->createStructure([
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Full Waiver Package',
            'includes_monthly_tuition' => false,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Fee Waivable',
                    'amount' => '2000.00',
                    'is_discountable' => true,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->sessionCat->id,
                    'head_name' => 'Session Fee Waivable',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'sort_order' => 20,
                ],
            ],
        ], $this->adminUser->id);

        $this->actingAs($this->adminUser);

        $res = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '401',
            'admission_discount_type' => 'percentage',
            'admission_discount_value' => '100',
        ]));

        $res->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $student = Student::where('student_identity', $res->json('identity'))->firstOrFail();
        $invoice = FeeInvoice::with('admissionHeadAllocations')
            ->where('student_id', $student->id)
            ->firstOrFail();

        // 1. Must NOT be marked Paid and must create ZERO fabricated FeePayment rows
        $this->assertSame($structure->id, (int) $invoice->admission_fee_structure_id);
        $this->assertSame(0, bccomp('3000.00', (string) $invoice->amount, 2));
        $this->assertSame(0, bccomp('3000.00', (string) $invoice->discount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $invoice->net_amount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $invoice->paid_amount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $invoice->due_amount, 2));
        $this->assertSame('Unpaid', $invoice->status);
        $this->assertTrue($invoice->isZeroPayableWaived());
        $this->assertSame('Waived', $invoice->display_status);
        $this->assertSame(0, FeePayment::where('student_id', $student->id)->count());

        foreach ($invoice->admissionHeadAllocations as $head) {
            $this->assertSame(0, bccomp((string) $head->amount, (string) $head->discount, 2));
            $this->assertSame(0, bccomp('0.00', (string) $head->net_amount, 2));
            $this->assertSame(0, bccomp('0.00', (string) $head->allocated_paid_amount, 2));
            $this->assertSame(0, bccomp('0.00', (string) $head->due_amount, 2));
        }

        // 2. Student Profile JSON + UI status representation
        $showRes = $this->getJson(route('students.show', $student->id));
        $showRes->assertOk()
            ->assertJsonPath('invoices.0.status', 'Unpaid')
            ->assertJsonPath('invoices.0.display_status', 'Waived');
        $this->assertSame(0, bccomp('0.00', (string) $showRes->json('invoices.0.paid_amount'), 2));
        $this->assertSame(0, bccomp('0.00', (string) $showRes->json('invoices.0.due_amount'), 2));

        // 3. Collection workflows must NOT list zero-due waived invoice as pending or accept payment
        $colStudentRes = $this->get(route('fees.collection.index', ['student_identity' => $student->student_identity]));
        $colStudentRes->assertOk();
        $this->assertCount(0, $colStudentRes->viewData('invoices'));

        $colQueueRes = $this->get(route('fees.collection.index'));
        $colQueueRes->assertOk();
        $this->assertFalse(
            $colQueueRes->viewData('dueStudents')->getCollection()->contains('id', $student->id)
        );

        $badCollectRes = $this->post(route('fees.collection.store'), [
            'invoice_id' => $invoice->id,
            'pay_amount' => 10.00,
            'payment_method' => 'Cash',
        ]);
        $badCollectRes->assertSessionHas('error');
        $this->assertSame(0, FeePayment::where('student_id', $student->id)->count());

        // 4. Reporting workflows must NEVER count zero-net waived invoice as collected, paid, or overdue
        $today = date('Y-m-d');
        $feeReportRes = $this->get(route('fees.reports.index', ['start_date' => $today, 'end_date' => $today]));
        $feeReportRes->assertOk();
        $this->assertSame(0.0, (float) $feeReportRes->viewData('totalCollected'));
        $this->assertSame(0.0, (float) $feeReportRes->viewData('totalDue'));
        $this->assertCount(0, $feeReportRes->viewData('payments'));
        $this->assertCount(0, $feeReportRes->viewData('dues'));

        $summaryReportRes = $this->get(route('fees.reports.summary'));
        $summaryReportRes->assertOk();
        $this->assertSame(0.0, (float) $summaryReportRes->viewData('overallNet'));
        $this->assertSame(0.0, (float) $summaryReportRes->viewData('overallPaid'));
        $summaryReportRes->assertViewHas('overallDue', fn ($v): bool => (float) $v === 0.0);

        $finReportRes = $this->get(route('accounts.reports.index', ['start_date' => $today, 'end_date' => $today]));
        $finReportRes->assertOk();
        $this->assertSame(0.0, (float) $finReportRes->viewData('totalIncome'));
        $this->assertSame(3000.0, (float) $finReportRes->viewData('totalBilledGross'));
        $this->assertSame(3000.0, (float) $finReportRes->viewData('totalBilledDiscount'));
        $this->assertSame(0.0, (float) $finReportRes->viewData('totalBilledNet'));
        $this->assertSame(0.0, (float) $finReportRes->viewData('totalOutstandingDue'));

        $classReportRes = $this->get(route('accounts.reports.class_wise', [
            'session_year_id' => $this->session2026->id,
        ]));
        $classReportRes->assertOk();
        $this->assertSame(0.0, (float) $classReportRes->viewData('total_collected'));
        $this->assertSame(0.0, (float) $classReportRes->viewData('total_due'));
        $this->assertSame(0.0, (float) $classReportRes->viewData('total_outstanding'));

        $paymentsIndexRes = $this->get(route('fees.payments.index'));
        $paymentsIndexRes->assertOk();
        $this->assertSame(0, $paymentsIndexRes->viewData('payments')->total());

        // 5. Cancellation & -V2 regeneration lifecycle for zero-net invoice
        $cancelled = $this->invoiceService->cancelUnpaidInvoice(
            $invoice->id,
            'Waiver revoked during re-assessment',
            $this->adminUser->id
        );
        $this->assertSame('Cancelled', $cancelled->status);
        $this->assertSame('Cancelled', $cancelled->display_status);
        $this->assertSame(0, bccomp('0.00', (string) $cancelled->net_amount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $cancelled->due_amount, 2));

        // Regenerate with partial fixed discount -> -V2 succeeds
        $v2Res = $this->invoiceService->generateForStudent($student, [
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ], $this->adminUser->id);
        $v2Invoice = $v2Res['invoice'];
        $expectedBase = sprintf('INV-ADM-S%d-%s-%d', $this->session2026->id, date('Ym'), $student->id);
        $this->assertSame("{$expectedBase}-V2", $v2Invoice->invoice_no);
        $this->assertSame('Unpaid', $v2Invoice->status);
        $this->assertSame('Unpaid', $v2Invoice->display_status);
        $this->assertSame(0, bccomp('2500.00', (string) $v2Invoice->net_amount, 2));
        $this->assertSame(0, bccomp('2500.00', (string) $v2Invoice->due_amount, 2));

        // Discontinuation safely cancels a zero-net future-dated invoice as well
        $studentDisc = $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, 'ZERO-DISC-01');
        $zeroDiscInv = $this->invoiceService->generateForStudent($studentDisc, [
            'issue_date' => '2026-04-12',
            'discount_type' => 'percentage',
            'discount_value' => '100',
        ], $this->adminUser->id)['invoice'];

        $discRes = $this->postJson(route('students.discontinue.reconcile', $studentDisc->id), [
            'last_active_month' => '2026-03',
            'reason' => 'Transferred after 100% waiver',
            'confirm' => true,
        ]);
        $discRes->assertOk()
            ->assertJsonPath('cancelled_count', 1)
            ->assertJsonCount(0, 'skipped_paid_invoices');
        $this->assertSame('Cancelled', $zeroDiscInv->fresh()->status);
    }

    public function test_student_identity_allocation_preserves_format_manual_mode_and_gap_resilience(): void
    {
        $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        $this->actingAs($this->adminUser);

        // 1. Explicit manual identity is preserved verbatim
        $manualRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '501',
            'student_identity' => 'MANUAL-2026-0099',
        ]));
        $manualRes->assertStatus(201)
            ->assertJsonPath('identity', 'MANUAL-2026-0099');
        $this->assertDatabaseHas('students', ['student_identity' => 'MANUAL-2026-0099']);

        // Duplicate manual identity rejects with 422 and never reallocates
        $dupManualRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '502',
            'student_identity' => 'MANUAL-2026-0099',
        ]));
        $dupManualRes->assertStatus(422);

        // 2. Gap resilience: existing higher serial 00545 forces monotonic jump to 00546 even when total count is tiny
        $prefix = date('ym') . '01';
        $this->createRawStudent($this->branchA, $this->classOne, $this->session2026, $prefix . '00545');

        $gapRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '503',
            // omitted student_identity -> backend auto-generates
        ]));
        $gapRes->assertStatus(201)
            ->assertJsonPath('identity', $prefix . '00546');

        // 3. Placeholder UI template triggers next monotonic serial 00547
        $placeholderRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '504',
            'student_identity' => 'YYYY-MM-CLASS-XXXX',
        ]));
        $placeholderRes->assertStatus(201)
            ->assertJsonPath('identity', $prefix . '00547');
    }

    public function test_student_identity_advisory_lock_lifecycle_and_auto_collision_reallocation_simulation(): void
    {
        Storage::fake('public');
        $this->enableScopedSqliteIdentityLock();
        StudentController::$enableTestAdvisoryIdentityLock = true;
        $this->createMultiHeadStructure($this->branchA, $this->classOne, $this->session2026);
        $this->actingAs($this->adminUser);

        $prefix = date('ym') . '01';

        // Part A: Advisory lock wraps DB transaction and releases BEFORE response / post-commit evaluation
        $okRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
            'roll_number' => '601',
        ]));
        $okRes->assertStatus(201)
            ->assertJsonPath('identity', $prefix . '00539');

        $getIdx = array_search('GET_LOCK:' . StudentController::IDENTITY_LOCK_NAME, self::$advisoryLockEvents, true);
        $relIdx = array_search('RELEASE_LOCK:' . StudentController::IDENTITY_LOCK_NAME, self::$advisoryLockEvents, true);
        $this->assertIsInt($getIdx);
        $this->assertIsInt($relIdx);
        $this->assertLessThan($relIdx, $getIdx);

        // Part B: Simulated auto-identity SQLSTATE 23000 collision (SQLite single-connection simulation;
        // not live MySQL multi-connection contention). Verifies atomic rollback, no duplicate file upload,
        // and clean sequential identity reallocation without conflating generic 23000 with deadlocks.
        StudentController::$enableTestAdvisoryIdentityLock = false;
        $injectedAutoCollision = false;
        Student::creating(function (Student $s) use (&$injectedAutoCollision, $prefix): void {
            if (!$injectedAutoCollision && $s->student_identity === $prefix . '00540') {
                $injectedAutoCollision = true;
                throw new QueryException(
                    'sqlite',
                    'insert into "students" ...',
                    [],
                    new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: students.student_identity', 23000)
                );
            }
        });

        try {
            $retryPhoto = UploadedFile::fake()->image('collision_retry.jpg');
            $retryRes = $this->post(route('students.store'), $this->validAdmissionPayload([
                'roll_number' => '602',
                'photo' => $retryPhoto,
            ]), ['Accept' => 'application/json']);

            $retryRes->assertStatus(201)
                ->assertJsonPath('identity', $prefix . '00541');
            $this->assertTrue($injectedAutoCollision);
            $this->assertDatabaseMissing('students', ['student_identity' => $prefix . '00540']);
            $this->assertDatabaseHas('students', ['student_identity' => $prefix . '00541', 'roll_number' => '602']);
            // Exactly 1 uploaded file stored (no repeated uploads across identity reallocation attempts)
            $this->assertCount(1, Storage::disk('public')->allFiles());
            $this->assertSame(1, FeeInvoice::whereHas('student', fn ($q) => $q->where('student_identity', $prefix . '00541'))->count());

            // Part C: Concurrent manual identity unique violation rejects 422 without reallocating,
            // and non-identity 23000 (e.g. duplicate card_number) rejects 500 without retrying/reallocating.
            $manualHookFired = false;
            Student::creating(function (Student $s) use (&$manualHookFired): void {
                if ($s->student_identity === 'RACE-MANUAL-01') {
                    $manualHookFired = true;
                    throw new QueryException(
                        'sqlite',
                        'insert into "students" ...',
                        [],
                        new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: students.student_identity', 23000)
                    );
                }
                if ($s->roll_number === '604') {
                    throw new QueryException(
                        'sqlite',
                        'insert into "students" ...',
                        [],
                        new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: students.card_number', 23000)
                    );
                }
            });

            $manualRaceRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
                'roll_number' => '603',
                'student_identity' => 'RACE-MANUAL-01',
            ]));
            $manualRaceRes->assertStatus(422)
                ->assertJsonValidationErrors(['student_identity']);
            $this->assertTrue($manualHookFired);
            $this->assertDatabaseMissing('students', ['roll_number' => '603']);

            $cardConstraintRes = $this->postJson(route('students.store'), $this->validAdmissionPayload([
                'roll_number' => '604',
            ]));
            $cardConstraintRes->assertStatus(500);
            $this->assertDatabaseMissing('students', ['roll_number' => '604']);
        } finally {
            Student::flushEventListeners();
        }
    }

    private function enableScopedSqliteIdentityLock(): void
    {
        self::$allowSqliteIdentityLock = true;

        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $pdoObjId = spl_object_id($pdo);

        if (!isset(self::$registeredLockPdoIds[$pdoObjId]) && method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('GET_LOCK', static function (?string $name, ?int $timeout): int {
                if (!AdmissionInvoiceGenerationTest::$allowSqliteIdentityLock) {
                    throw new PDOException('SQLSTATE[HY000]: General error: 1 no such function: GET_LOCK');
                }
                AdmissionInvoiceGenerationTest::$advisoryLockEvents[] = 'GET_LOCK:' . $name;

                return 1;
            }, 2);

            $pdo->sqliteCreateFunction('RELEASE_LOCK', static function (?string $name): int {
                if (!AdmissionInvoiceGenerationTest::$allowSqliteIdentityLock) {
                    throw new PDOException('SQLSTATE[HY000]: General error: 1 no such function: RELEASE_LOCK');
                }
                AdmissionInvoiceGenerationTest::$advisoryLockEvents[] = 'RELEASE_LOCK:' . $name;

                return 1;
            }, 1);

            self::$registeredLockPdoIds[$pdoObjId] = true;
        }
    }

    /**
     * Create a standard 3-head AdmissionFeeStructure (Discountable = 3000.00, Non-discountable = 1500.00, Total = 4500.00).
     */
    private function createMultiHeadStructure(
        Branch $branch,
        Classes $class,
        SessionYear $session,
        string $regAmount = '2000.00'
    ): AdmissionFeeStructure {
        return $this->structureService->createStructure([
            'branch_id' => $branch->id,
            'class_id' => $class->id,
            'session_year_id' => $session->id,
            'structure_name' => "Package {$class->class_name}",
            'includes_monthly_tuition' => false,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Registration',
                    'amount' => $regAmount,
                    'is_discountable' => true,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->sessionCat->id,
                    'head_name' => 'Session Development',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'sort_order' => 20,
                ],
                [
                    'fee_category_id' => $this->monthlyCat->id,
                    'head_name' => 'ID Card & Diary',
                    'amount' => '1500.00',
                    'is_discountable' => false,
                    'sort_order' => 30,
                ],
            ],
        ], $this->adminUser->id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validAdmissionPayload(array $overrides = []): array
    {
        return array_merge([
            'student_name' => 'Nadia Hasan',
            'roll_number' => '11',
            'branch_id' => $this->branchA->id,
            'class_id' => $this->classOne->id,
            'section_id' => $this->sectionA->id,
            'session_year_id' => $this->session2026->id,
            'father_name' => 'Hasan Ali',
            'father_mobile' => '01711000011',
            'mother_name' => 'Salma Hasan',
            'mother_mobile' => '01722000011',
            'guardian_name' => 'Hasan Ali',
            'guardian_mobile' => '01711000011',
            'dob' => '2016-05-15',
            'gender' => 'Female',
            'present_village' => 'Jalalpur',
            'present_post_office' => 'Jalalpur',
            'present_district' => 'Cumilla',
            'present_division' => 'Chattogram',
            'permanent_village' => 'Jalalpur',
            'permanent_post_office' => 'Jalalpur',
            'permanent_district' => 'Cumilla',
            'permanent_division' => 'Chattogram',
        ], $overrides);
    }

    private function createRawStudent(
        Branch $branch,
        Classes $class,
        SessionYear $session,
        string $identity
    ): Student {
        return Student::create([
            'user_id' => $this->adminUser->id,
            'student_name' => 'Student ' . $identity,
            'student_identity' => $identity,
            'roll_number' => substr($identity, -3),
            'dob' => '2016-02-01',
            'gender' => 'Male',
            'class_id' => $class->id,
            'section_id' => $this->sectionA->id,
            'branch_id' => $branch->id,
            'session_year_id' => $session->id,
            'father_name' => 'Father',
            'father_mobile' => '01700000001',
            'mother_name' => 'Mother',
            'mother_mobile' => '01700000002',
            'guardian_name' => 'Guardian',
            'guardian_mobile' => '01700000003',
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
}
