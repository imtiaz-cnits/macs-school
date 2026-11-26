<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeeSetup;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use App\Services\FeeGenerationService;
use Carbon\Carbon;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeGenerationDueDateGuardTest extends TestCase
{
    use RefreshDatabase;

    public static bool $allowSqliteAdvisoryLock = false;

    /** @var array<int, bool> */
    private static array $registeredPdoIds = [];

    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session2026;
    protected Classes $clsPlay;
    protected Section $secPlay;
    protected Student $student;
    protected FeeCategory $catMonthly;
    protected FeeCategory $catExam;
    protected FeeSetup $monthlySetup;
    protected FeeSetup $marchExamSetup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableScopedSqliteLock();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_duedate_guard@macsschool.com'],
            ['name' => 'DueDate Guard Admin', 'password' => bcrypt('secret')]
        );
        $role = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($role);
        $this->actingAs($this->adminUser);

        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->session2026 = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026', 'user_id' => $this->adminUser->id]);
        $this->clsPlay = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Play', 'user_id' => $this->adminUser->id]);
        $this->secPlay = Section::firstOrCreate([
            'id' => 1,
        ], [
            'section_name' => 'A',
            'class_id' => $this->clsPlay->id,
            'session_year_id' => $this->session2026->id,
            'user_id' => $this->adminUser->id,
        ]);

        $this->student = Student::create([
            'user_id'               => $this->adminUser->id,
            'student_identity'      => 'STD-GUARD-01',
            'student_name'          => 'Guard Test Student',
            'roll_number'           => '01',
            'dob'                   => '2020-01-01',
            'gender'                => 'Male',
            'father_name'           => 'Father',
            'father_mobile'         => '01711000111',
            'mother_name'           => 'Mother',
            'mother_mobile'         => '01711000222',
            'guardian_name'         => 'Guardian',
            'guardian_mobile'       => '01711000111',
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session2026->id,
            'class_id'              => $this->clsPlay->id,
            'section_id'            => $this->secPlay->id,
            'status'                => 'Active',
            'sms_status'            => 'Active',
            'present_village'       => 'V',
            'present_post_office'   => 'P',
            'present_district'      => 'D',
            'present_division'      => 'Div',
            'permanent_village'     => 'V',
            'permanent_post_office' => 'P',
            'permanent_district'    => 'D',
            'permanent_division'    => 'Div',
        ]);
        // Mark as legacy session student created at start of year so all 12 months are eligible
        DB::table('students')->where('id', $this->student->id)->update(['created_at' => '2026-01-01 08:00:00']);
        $this->student->refresh();

        $this->catMonthly = FeeCategory::firstOrCreate(['name' => 'Monthly Fee'], ['status' => 'Active', 'user_id' => $this->adminUser->id]);
        $this->catExam = FeeCategory::firstOrCreate(['name' => 'Monthly Exam Fee'], ['status' => 'Active', 'user_id' => $this->adminUser->id]);

        $this->monthlySetup = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->clsPlay->id,
            'fee_category_id' => $this->catMonthly->id,
            'fee_month'       => 'Monthly',
            'amount'          => 650.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->marchExamSetup = FeeSetup::create([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->clsPlay->id,
            'fee_category_id' => $this->catExam->id,
            'fee_month'       => 'March',
            'amount'          => 200.00,
            'status'          => 'Active',
            'user_id'         => $this->adminUser->id,
        ]);
    }

    protected function tearDown(): void
    {
        self::$allowSqliteAdvisoryLock = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function enableScopedSqliteLock(): void
    {
        self::$allowSqliteAdvisoryLock = true;
        $pdo = DB::connection()->getPdo();
        if (DB::connection()->getDriverName() === 'sqlite' && method_exists($pdo, 'sqliteCreateFunction')) {
            $pdoId = spl_object_id($pdo);
            if (!isset(self::$registeredPdoIds[$pdoId])) {
                $pdo->sqliteCreateFunction('GET_LOCK', static function (): int {
                    return self::$allowSqliteAdvisoryLock ? 1 : 0;
                }, 2);
                $pdo->sqliteCreateFunction('RELEASE_LOCK', static fn (): int => 1, 1);
                self::$registeredPdoIds[$pdoId] = true;
            }
        }
    }

    public function test_out_of_month_due_date_is_auto_normalized_to_target_month_for_monthly_and_exam_fees(): void
    {
        // 1. Manual web POST for March Monthly Fee with August due_date (reproducing the Aug 3 production bug)
        $resMonthly = $this->post(route('fees.invoice.store'), [
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->clsPlay->id,
            'fee_category_id' => $this->catMonthly->id,
            'fee_month'       => 'March',
            'due_date'        => '2026-08-02',
        ]);
        $resMonthly->assertRedirect();
        $resMonthly->assertSessionHas('success');

        $monthlyInv = FeeInvoice::where('student_id', $this->student->id)
            ->where('fee_setup_id', $this->monthlySetup->id)
            ->firstOrFail();

        $this->assertStringStartsWith('INV-202603-', $monthlyInv->invoice_no);
        $this->assertSame('2026-03-10', substr((string) $monthlyInv->due_date, 0, 10));

        // 2. Manual web POST for March Exam Fee with August due_date
        $resExam = $this->post(route('fees.invoice.store'), [
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->clsPlay->id,
            'fee_category_id' => $this->catExam->id,
            'fee_month'       => 'March',
            'due_date'        => '2026-08-03',
        ]);
        $resExam->assertRedirect();
        $resExam->assertSessionHas('success');

        $examInv = FeeInvoice::where('student_id', $this->student->id)
            ->where('fee_setup_id', $this->marchExamSetup->id)
            ->firstOrFail();

        $this->assertStringStartsWith('INV-202603-', $examInv->invoice_no);
        $this->assertSame('2026-03-10', substr((string) $examInv->due_date, 0, 10));
    }

    public function test_valid_in_month_due_date_is_preserved_during_single_month_generation(): void
    {
        $service = app(FeeGenerationService::class);
        $result = $service->generate([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->clsPlay->id,
            'fee_category_id' => $this->catMonthly->id,
            'fee_month'       => 'March',
            'due_date'        => '2026-03-22',
            'user_id'         => $this->adminUser->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['total_generated']);

        $inv = FeeInvoice::where('student_id', $this->student->id)
            ->where('fee_setup_id', $this->monthlySetup->id)
            ->firstOrFail();

        $this->assertStringStartsWith('INV-202603-', $inv->invoice_no);
        $this->assertSame('2026-03-22', substr((string) $inv->due_date, 0, 10));
    }

    public function test_multi_month_all_past_and_annual_runs_enforce_per_month_due_dates_even_when_due_date_supplied(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 10:00:00', 'Asia/Dhaka'));
        $service = app(FeeGenerationService::class);

        // 1. all-past with an explicit out-of-month due_date supplied -> Jan..Apr MUST each get YYYY-MM-10
        $allPastResult = $service->generate([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->clsPlay->id,
            'fee_category_id' => $this->catMonthly->id,
            'fee_month'       => 'all-past',
            'due_date'        => '2026-08-02',
            'user_id'         => $this->adminUser->id,
        ]);
        $this->assertTrue($allPastResult['success']);
        $this->assertSame(4, $allPastResult['total_generated']);

        // 2. all (annual) with an explicit due_date supplied -> May..Dec MUST each get YYYY-MM-10
        $annualResult = $service->generate([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->clsPlay->id,
            'fee_category_id' => $this->catMonthly->id,
            'fee_month'       => 'all',
            'due_date'        => '2026-08-02',
            'user_id'         => $this->adminUser->id,
        ]);
        $this->assertTrue($annualResult['success']);
        $this->assertSame(8, $annualResult['total_generated']);

        $invoices = FeeInvoice::where('student_id', $this->student->id)
            ->where('fee_setup_id', $this->monthlySetup->id)
            ->orderBy('invoice_no')
            ->get();

        $this->assertCount(12, $invoices);
        foreach (range(1, 12) as $m) {
            $mm = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $inv = $invoices->first(fn (FeeInvoice $i): bool => str_starts_with($i->invoice_no, "INV-2026{$mm}-"));
            $this->assertNotNull($inv, "Missing invoice for month {$mm}");
            $this->assertSame("2026-{$mm}-10", substr((string) $inv->due_date, 0, 10), "Wrong due_date for month {$mm}");
        }
    }
}
