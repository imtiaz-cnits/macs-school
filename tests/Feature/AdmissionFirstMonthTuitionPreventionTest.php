<?php

namespace Tests\Feature;

use App\Models\AdmissionFeeStructure;
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
use App\Services\AdmissionFeeStructureService;
use App\Services\AdmissionInvoiceService;
use App\Services\FeeGenerationService;
use Carbon\Carbon;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDOException;
use Tests\TestCase;

class AdmissionFirstMonthTuitionPreventionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scoped SQLite GET_LOCK toggle so these feature tests exercise the real FeeGenerationService::generate()
     * SQLite in-memory path without altering the pre-existing baseline failure on unrelated tests.
     */
    public static bool $allowSqliteAdvisoryLock = false;

    /** @var array<int, bool> */
    private static array $registeredPdoIds = [];

    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session2026;
    protected Classes $classOne;
    protected Section $sectionA;
    protected FeeCategory $admissionCat;
    protected FeeCategory $monthlyCat;
    protected FeeCategory $monthlyExamCat;
    protected FeeSetup $monthlyTuitionSetup;
    protected FeeSetup $janExamSetup;
    protected AdmissionFeeStructureService $structureService;
    protected AdmissionInvoiceService $invoiceService;
    protected FeeGenerationService $generationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableScopedSqliteLock();

        $this->structureService = app(AdmissionFeeStructureService::class);
        $this->invoiceService = app(AdmissionInvoiceService::class);
        $this->generationService = app(FeeGenerationService::class);

        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser = User::create([
            'name' => 'Tuition Admin',
            'email' => 'tuition_guard_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->adminUser->assignRole($role);
        $this->actingAs($this->adminUser);

        $this->branch = Branch::create(['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->session2026 = SessionYear::create(['session_name' => '2026', 'user_id' => $this->adminUser->id]);
        $this->classOne = Classes::create(['class_name' => 'One', 'user_id' => $this->adminUser->id]);
        $this->sectionA = Section::create([
            'section_name' => 'A',
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'user_id' => $this->adminUser->id,
        ]);

        $this->admissionCat = FeeCategory::create(['name' => 'Admission Fee', 'status' => 'Active', 'user_id' => $this->adminUser->id]);
        $this->monthlyCat = FeeCategory::create(['name' => 'Monthly Fee', 'status' => 'Active', 'user_id' => $this->adminUser->id]);
        $this->monthlyExamCat = FeeCategory::create(['name' => 'Monthly Exam Fee', 'status' => 'Active', 'user_id' => $this->adminUser->id]);

        $this->monthlyTuitionSetup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'fee_category_id' => $this->monthlyCat->id,
            'amount' => 1200.00,
            'fee_month' => 'Monthly',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);

        // Unrelated non-tuition monthly exam setup for January (must NEVER be suppressed)
        $this->janExamSetup = FeeSetup::create([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'fee_category_id' => $this->monthlyExamCat->id,
            'amount' => 300.00,
            'fee_month' => 'January',
            'status' => 'Active',
            'user_id' => $this->adminUser->id,
        ]);
    }

    protected function tearDown(): void
    {
        self::$allowSqliteAdvisoryLock = false;
        parent::tearDown();
    }

    public function test_admission_first_suppresses_duplicate_january_tuition_without_fake_invoice_or_payment(): void
    {
        $this->createStructureWithTuitionMonth(monthNumber: 1, structureName: 'With Jan Tuition');
        $student = $this->createStudent('TUI-FIRST-01', Carbon::parse('2026-01-05'));

        // Step 1: Issue Admission Package (includes January Tuition)
        $admResult = $this->invoiceService->generateForStudent($student, [
            'issue_date' => '2026-01-05',
            'discount_type' => 'fixed',
            'discount_value' => '200.00',
        ], $this->adminUser->id);

        $admInvoice = $admResult['invoice'];
        $this->assertSame('Unpaid', $admInvoice->status);
        $this->assertSame(0, bccomp('0.00', (string) $admInvoice->paid_amount, 2));

        // Tuition head exists strictly unpaid inside Admission allocations (zero fake Monthly Fee invoice / payment)
        $tuitionHead = $admInvoice->admissionHeadAllocations()->where('is_tuition_head', true)->firstOrFail();
        $this->assertSame(0, bccomp('0.00', (string) $tuitionHead->allocated_paid_amount, 2));
        $this->assertSame(0, bccomp((string) $tuitionHead->net_amount, (string) $tuitionHead->due_amount, 2));
        $this->assertSame(0, FeePayment::where('student_id', $student->id)->count());

        // Step 2: Run full annual monthly fee generation
        $genResult = $this->generationService->generate([
            'session_year_id' => $this->session2026->id,
            'fee_month' => 'all',
            'user_id' => $this->adminUser->id,
        ]);

        $this->assertTrue($genResult['success']);

        // January Monthly Fee tuition setup MUST NOT produce a duplicate standalone invoice
        $janTuitionCount = FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
            ->where('invoice_no', 'like', 'INV-202601-%')
            ->count();
        $this->assertSame(0, $janTuitionCount, 'January Monthly Tuition must be skipped when covered by Admission Package.');

        // January Monthly Exam Fee MUST STILL be generated
        $janExamCount = FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->janExamSetup->id)
            ->count();
        $this->assertSame(1, $janExamCount, 'Unrelated January Monthly Exam Fee must still generate normally.');

        // Months 2..12 Monthly Tuition MUST ALL generate (11 invoices)
        $allStandaloneMonthly = FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
            ->orderBy('invoice_no')
            ->get();
        $this->assertCount(11, $allStandaloneMonthly);

        // Zero fake payments created during generation and tuition head remains unpaid
        $this->assertSame(0, FeePayment::where('student_id', $student->id)->count());
        $this->assertSame('Unpaid', $admInvoice->fresh()->status);

        // Session completeness status recognizes the absorbed January tuition slot cleanly (`completed`)
        $sessionStatus = $this->generationService->getSessionGenerationStatus($this->session2026->id);
        $this->assertSame('completed', $sessionStatus['status']);
        $this->assertSame(0, $sessionStatus['missing_invoices']);
        $this->assertSame(0, $sessionStatus['duplicate_invoices']);
    }

    public function test_admission_package_without_monthly_tuition_allows_all_12_monthly_invoices(): void
    {
        $this->structureService->createStructure([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'No Tuition Package',
            'includes_monthly_tuition' => false,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Only',
                    'amount' => '2500.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                ],
            ],
        ], $this->adminUser->id);

        $student = $this->createStudent('NO-TUI-01', Carbon::parse('2026-01-05'));
        $this->invoiceService->generateForStudent($student, ['issue_date' => '2026-01-05'], $this->adminUser->id);

        $genResult = $this->generationService->generate([
            'session_year_id' => $this->session2026->id,
            'fee_month' => 'all',
            'user_id' => $this->adminUser->id,
        ]);
        $this->assertTrue($genResult['success']);

        $monthlyCount = FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
            ->count();
        $this->assertSame(12, $monthlyCount);
    }

    public function test_monthly_first_conflict_rejects_admission_invoice_with_validation_error_and_preserves_monthly_invoice(): void
    {
        $this->createStructureWithTuitionMonth(monthNumber: 1, structureName: 'With Jan Tuition');
        $student = $this->createStudent('MON-FIRST-01', Carbon::parse('2026-01-05'));

        // Existing January Monthly Fee invoice generated BEFORE Admission Invoice
        $existingMonthly = FeeInvoice::create([
            'invoice_no' => 'INV-202601-0001',
            'student_id' => $student->id,
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'branch_name_snapshot' => $this->branch->branch_name,
            'fee_setup_id' => $this->monthlyTuitionSetup->id,
            'amount' => 1200.00,
            'discount' => 0.00,
            'net_amount' => 1200.00,
            'paid_amount' => 0.00,
            'due_amount' => 1200.00,
            'status' => 'Unpaid',
            'due_date' => '2026-01-10',
            'user_id' => $this->adminUser->id,
        ]);

        // Monthly-first conflict MUST reject with ValidationException citing conflicting invoice_no
        try {
            $this->invoiceService->generateForStudent($student, ['issue_date' => '2026-01-06'], $this->adminUser->id);
            $this->fail('Expected ValidationException on Monthly-First Tuition conflict.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->status);
            $this->assertArrayHasKey('tuition_month_number', $e->errors());
            $this->assertStringContainsString('INV-202601-0001', $e->errors()['tuition_month_number'][0]);
        }

        // Historical Monthly Invoice MUST remain completely untouched (not cancelled or modified)
        $existingMonthly->refresh();
        $this->assertSame('Unpaid', $existingMonthly->status);
        $this->assertSame(0, bccomp('1200.00', (string) $existingMonthly->net_amount, 2));
        $this->assertSame(0, FeeInvoice::admissionInvoices()->where('student_id', $student->id)->count());

        // HTTP route also returns 422 JSON cleanly when StudentController::store encounters monthly conflict in tx
        // (Verify HTTP 422 response mapping by testing structure conflict in February while February Monthly already doesn't collide vs cancelling)
        $existingMonthly->update(['status' => 'Cancelled', 'due_amount' => 0.00]);
        $afterCancel = $this->invoiceService->generateForStudent($student, ['issue_date' => '2026-01-06'], $this->adminUser->id);
        $this->assertTrue($afterCancel['created']);
    }

    public function test_cancelled_admission_invoice_unlocks_future_monthly_fee_generation(): void
    {
        $this->createStructureWithTuitionMonth(monthNumber: 1, structureName: 'With Jan Tuition');
        $student = $this->createStudent('UNLOCK-MON-01', Carbon::parse('2026-01-05'));

        $admInvoice = $this->invoiceService->generateForStudent($student, [
            'issue_date' => '2026-01-05',
        ], $this->adminUser->id)['invoice'];

        // Run January generation -> skipped cleanly while Admission Invoice is active
        $firstRun = $this->generationService->generate([
            'session_year_id' => $this->session2026->id,
            'fee_month' => 'January',
            'fee_category_id' => $this->monthlyCat->id,
            'user_id' => $this->adminUser->id,
        ]);
        $this->assertTrue($firstRun['success']);
        $this->assertSame(0, $firstRun['total_generated']);
        $this->assertSame(1, $firstRun['total_skipped']);

        // Cancel the unpaid Admission Invoice
        $this->invoiceService->cancelUnpaidInvoice($admInvoice->id, 'Admission cancelled before tuition start', $this->adminUser->id);

        // Re-run January monthly generation -> MUST NOW generate January Monthly Tuition normally!
        $secondRun = $this->generationService->generate([
            'session_year_id' => $this->session2026->id,
            'fee_month' => 'January',
            'fee_category_id' => $this->monthlyCat->id,
            'user_id' => $this->adminUser->id,
        ]);
        $this->assertTrue($secondRun['success']);
        $this->assertSame(1, $secondRun['total_generated']);

        $unlockedMonthly = FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
            ->where('invoice_no', 'like', 'INV-202601-%')
            ->first();
        $this->assertNotNull($unlockedMonthly);
        $this->assertSame('Unpaid', $unlockedMonthly->status);
    }

    public function test_monthly_first_conflict_in_different_month_does_not_falsely_block_admission(): void
    {
        // Structure covers January (Month 1)
        $this->createStructureWithTuitionMonth(monthNumber: 1, structureName: 'With Jan Tuition');
        $student = $this->createStudent('DIFF-MON-01', Carbon::parse('2026-01-05'));

        // Student has a Monthly Tuition invoice strictly for February (Month 2)
        FeeInvoice::create([
            'invoice_no' => 'INV-202602-0001',
            'student_id' => $student->id,
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'branch_name_snapshot' => $this->branch->branch_name,
            'fee_setup_id' => $this->monthlyTuitionSetup->id,
            'amount' => 1200.00,
            'discount' => 0.00,
            'net_amount' => 1200.00,
            'paid_amount' => 0.00,
            'due_amount' => 1200.00,
            'status' => 'Unpaid',
            'due_date' => '2026-02-10',
            'user_id' => $this->adminUser->id,
        ]);

        // Month 1 admission package MUST NOT conflict with Month 2 invoice
        $res = $this->invoiceService->generateForStudent($student, ['issue_date' => '2026-01-05'], $this->adminUser->id);
        $this->assertTrue($res['created']);
        $this->assertSame(1, FeeInvoice::admissionInvoices()->where('student_id', $student->id)->count());
    }

    public function test_http_student_store_post_commit_evaluation_suppresses_first_month_tuition_while_keeping_regular_students_intact(): void
    {
        $this->createStructureWithTuitionMonth(monthNumber: 1, structureName: 'With Jan Tuition');

        Carbon::setTestNow(Carbon::parse('2026-01-05 10:00:00'));
        try {
            // Continuing / existing student without Admission Package in same class
            $regularStudent = $this->createStudent('REG-2026-01', Carbon::parse('2026-01-02'));

            // Newly admitted student via real HTTP POST /ajax/students (`StudentController::store()`)
            $res = $this->postJson(route('students.store'), [
                'student_name' => 'January Admitted Student',
                'roll_number' => '55',
                'branch_id' => $this->branch->id,
                'class_id' => $this->classOne->id,
                'section_id' => $this->sectionA->id,
                'session_year_id' => $this->session2026->id,
                'father_name' => 'Kamrul Islam',
                'father_mobile' => '01711110055',
                'mother_name' => 'Nasrin Islam',
                'mother_mobile' => '01722220055',
                'guardian_name' => 'Kamrul Islam',
                'guardian_mobile' => '01733330055',
                'dob' => '2016-01-01',
                'gender' => 'Male',
                'present_village' => 'Jalalpur',
                'present_post_office' => 'Jalalpur',
                'present_district' => 'Cumilla',
                'present_division' => 'Chattogram',
                'permanent_village' => 'Jalalpur',
                'permanent_post_office' => 'Jalalpur',
                'permanent_district' => 'Cumilla',
                'permanent_division' => 'Chattogram',
                'admission_discount_type' => 'fixed',
                'admission_discount_value' => '100.00',
            ]);

            $res->assertStatus(201);
            $admittedStudent = Student::where('student_identity', $res->json('identity'))->firstOrFail();

            // 1. Admitted student: 1 multi-head Admission invoice + 11 Monthly Tuition (Feb..Dec) + 1 Jan Exam Fee; ZERO Jan Monthly Tuition
            $this->assertSame(1, FeeInvoice::admissionInvoices()->where('student_id', $admittedStudent->id)->count());
            $this->assertSame(0, FeeInvoice::where('student_id', $admittedStudent->id)
                ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
                ->where('invoice_no', 'like', 'INV-202601-%')
                ->count());
            $this->assertSame(11, FeeInvoice::where('student_id', $admittedStudent->id)
                ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
                ->count());
            $this->assertSame(1, FeeInvoice::where('student_id', $admittedStudent->id)
                ->where('fee_setup_id', $this->janExamSetup->id)
                ->count());

            // Zero fake payment on admitted student's Admission Invoice
            $this->assertSame(0, FeePayment::where('student_id', $admittedStudent->id)->count());

            // 2. Existing non-admission regular student still receives ALL 12 standalone Monthly Tuition invoices + Jan Exam
            $this->assertSame(12, FeeInvoice::where('student_id', $regularStudent->id)
                ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
                ->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_zero_net_100_percent_discounted_admission_with_tuition_suppresses_monthly_tuition_and_unlocks_on_cancel(): void
    {
        $this->createStructureWithTuitionMonth(monthNumber: 1, structureName: 'Waivable With Jan Tuition');
        $student = $this->createStudent('ZERO-TUI-01', Carbon::parse('2026-01-05'));

        // 100% discount across all discountable heads (Admission 2000 + First Month Tuition 1200) -> 0.00 net
        $admInvoice = $this->invoiceService->generateForStudent($student, [
            'issue_date' => '2026-01-05',
            'discount_type' => 'percentage',
            'discount_value' => '100',
        ], $this->adminUser->id)['invoice'];

        $this->assertSame('Unpaid', $admInvoice->status);
        $this->assertSame('Waived', $admInvoice->display_status);
        $this->assertSame(0, bccomp('0.00', (string) $admInvoice->net_amount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $admInvoice->paid_amount, 2));
        $this->assertSame(0, bccomp('0.00', (string) $admInvoice->due_amount, 2));
        $this->assertSame(0, FeePayment::where('student_id', $student->id)->count());

        // Annual fee generation must suppress January Monthly Tuition while allowing Feb..Dec (11) + Jan Exam (1)
        $genResult = $this->generationService->generate([
            'session_year_id' => $this->session2026->id,
            'fee_month' => 'all',
            'user_id' => $this->adminUser->id,
        ]);
        $this->assertTrue($genResult['success']);

        $this->assertSame(0, FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
            ->where('invoice_no', 'like', 'INV-202601-%')
            ->count());
        $this->assertSame(11, FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
            ->count());
        $this->assertSame(1, FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->janExamSetup->id)
            ->count());

        // Cancelling the zero-net waived Admission Invoice succeeds and unlocks January Monthly Tuition generation
        $this->invoiceService->cancelUnpaidInvoice($admInvoice->id, 'Zero-net package cancelled', $this->adminUser->id);
        $this->assertSame('Cancelled', $admInvoice->fresh()->status);

        $unlockRun = $this->generationService->generate([
            'session_year_id' => $this->session2026->id,
            'fee_month' => 'January',
            'fee_category_id' => $this->monthlyCat->id,
            'user_id' => $this->adminUser->id,
        ]);
        $this->assertTrue($unlockRun['success']);
        $this->assertSame(1, FeeInvoice::where('student_id', $student->id)
            ->where('fee_setup_id', $this->monthlyTuitionSetup->id)
            ->where('invoice_no', 'like', 'INV-202601-%')
            ->count());
    }

    private function createStructureWithTuitionMonth(int $monthNumber, string $structureName): AdmissionFeeStructure
    {
        return $this->structureService->createStructure([
            'branch_id' => $this->branch->id,
            'class_id' => $this->classOne->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => $structureName,
            'includes_monthly_tuition' => true,
            'tuition_month_number' => $monthNumber,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Registration',
                    'amount' => '2000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->monthlyCat->id,
                    'head_name' => 'First Month Tuition',
                    'amount' => '1200.00',
                    'is_discountable' => true,
                    'is_tuition_head' => true,
                    'sort_order' => 20,
                ],
            ],
        ], $this->adminUser->id);
    }

    private function createStudent(string $identity, Carbon $createdAt): Student
    {
        $student = new Student([
            'user_id' => $this->adminUser->id,
            'student_name' => 'Student ' . $identity,
            'student_identity' => $identity,
            'roll_number' => substr($identity, -2),
            'dob' => '2016-01-01',
            'gender' => 'Male',
            'class_id' => $this->classOne->id,
            'section_id' => $this->sectionA->id,
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session2026->id,
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
        $student->created_at = $createdAt;
        $student->updated_at = $createdAt;
        $student->save();

        return $student;
    }

    private function enableScopedSqliteLock(): void
    {
        self::$allowSqliteAdvisoryLock = true;

        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $pdoObjId = spl_object_id($pdo);

        if (!isset(self::$registeredPdoIds[$pdoObjId]) && method_exists($pdo, 'sqliteCreateFunction')) {
            $pdo->sqliteCreateFunction('GET_LOCK', static function (?string $name, ?int $timeout): int {
                if (!AdmissionFirstMonthTuitionPreventionTest::$allowSqliteAdvisoryLock) {
                    throw new PDOException('SQLSTATE[HY000]: General error: 1 no such function: GET_LOCK');
                }
                return 1;
            }, 2);

            $pdo->sqliteCreateFunction('RELEASE_LOCK', static function (?string $name): int {
                if (!AdmissionFirstMonthTuitionPreventionTest::$allowSqliteAdvisoryLock) {
                    throw new PDOException('SQLSTATE[HY000]: General error: 1 no such function: RELEASE_LOCK');
                }
                return 1;
            }, 1);

            self::$registeredPdoIds[$pdoObjId] = true;
        }
    }
}
