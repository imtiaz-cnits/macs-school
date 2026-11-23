<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\StudentAttendanceSchedule;
use App\Models\StudentAttendanceScheduleSlot;
use App\Models\User;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Database\Seeders\StudentAttendanceScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Jmrashed\Zkteco\Lib\ZKTeco;
use Tests\TestCase;

class VerifyAttendanceScheduleCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branchMain;
    private Branch $branchTown;
    private SessionYear $session;
    private Section $section;
    /** @var array<string, Classes> */
    private array $classes = [];

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.timezone', 'Asia/Dhaka');

        $this->admin = User::create([
            'name'     => 'Verify Admin',
            'email'    => 'verify_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);

        $this->branchMain = Branch::create(['branch_name' => 'Jalalpur Branch', 'user_id' => $this->admin->id]);
        $this->branchTown = Branch::create(['branch_name' => 'Town Branch', 'user_id' => $this->admin->id]);
        $this->session = SessionYear::create(['session_name' => '2026', 'user_id' => $this->admin->id]);
        $this->section = Section::create(['section_name' => 'Section A', 'user_id' => $this->admin->id]);

        foreach (['Play', 'Nursery', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'] as $name) {
            $this->classes[$name] = Classes::create(['class_name' => $name, 'user_id' => $this->admin->id]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createExamCampaignFromTemplate(string $name, string $startDate, string $endDate, ?int $branchId = null): StudentAttendanceSchedule
    {
        $template = StudentAttendanceSchedule::templates()->with('slots.classes')->firstOrFail();

        $campaign = StudentAttendanceSchedule::create([
            'name'        => $name,
            'mode'        => StudentAttendanceSchedule::MODE_EXAM,
            'branch_id'   => $branchId,
            'is_default'  => false,
            'is_template' => false,
            'is_active'   => true,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'priority'    => 20,
            'created_by'  => $this->admin->id,
        ]);

        foreach ($template->slots as $slot) {
            $clonedSlot = StudentAttendanceScheduleSlot::create([
                'schedule_id'              => $campaign->id,
                'shift_group'              => $slot->shift_group,
                'slot_name'                => $slot->slot_name,
                'on_time_start'            => $slot->on_time_start,
                'on_time_end'              => $slot->on_time_end,
                'late_end'                 => $slot->late_end,
                'absent_from'              => $slot->absent_from,
                'check_out_from'           => $slot->check_out_from,
                'early_grace_minutes'      => $slot->early_grace_minutes,
                'debounce_minutes'         => $slot->debounce_minutes,
                'min_checkout_gap_minutes' => $slot->min_checkout_gap_minutes,
                'sort_order'               => $slot->sort_order,
            ]);

            foreach ($slot->classes as $classModel) {
                $clonedSlot->slotClasses()->create([
                    'schedule_id' => $campaign->id,
                    'class_id'    => $classModel->id,
                ]);
            }
        }

        return $campaign;
    }

    /**
     * 1. Healthy Normal Day:
     *    Exits 0, prints Normal slots, reports 0 unmapped classes, and NEVER invokes ZktecoService without --check-device.
     */
    public function test_healthy_normal_day_exits_zero_and_never_touches_zkteco_without_flag(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->expects($this->never())->method('getDeviceClockInfo');
        $zkMock->expects($this->never())->method('getConnectionStatus');
        $this->app->instance(ZktecoService::class, $zkMock);

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24'])
            ->expectsOutputToContain('Effective Mode on 2026-11-24: NORMAL')
            ->expectsOutputToContain('Morning (Play - Three)')
            ->expectsOutputToContain('Day (Four - Ten)')
            ->expectsOutputToContain('12/12 mapped | 0 unmapped / legacy fallback')
            ->expectsOutputToContain('Device check skipped')
            ->expectsOutputToContain('VERDICT: PASS')
            ->assertExitCode(0);
    }

    /**
     * 2. Active Exam Date & Partial Monthly Exam Date (plus branch-scoped verification).
     */
    public function test_active_exam_date_and_partial_monthly_exam_date_report_accurately(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        // Full 3-slot Exam Campaign: 2026-12-01..2026-12-10
        $this->createExamCampaignFromTemplate('1st Term Exam 2026', '2026-12-01', '2026-12-10');

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-12-05'])
            ->expectsOutputToContain('Effective Mode on 2026-12-05: EXAM')
            ->expectsOutputToContain('1st Term Exam 2026')
            ->expectsOutputToContain('Morning Exam (Play - Four)')
            ->expectsOutputToContain('Day Exam (Five - Nine)')
            ->expectsOutputToContain('Day Exam (Class Ten)')
            ->expectsOutputToContain('12/12 mapped | 0 unmapped / legacy fallback')
            ->expectsOutputToContain('VERDICT: PASS')
            ->assertExitCode(0);

        // Partial Monthly Exam (Classes Nine & Ten only): 2026-12-15..2026-12-18
        $partial = StudentAttendanceSchedule::create([
            'name'        => 'Monthly Test (9-10)',
            'mode'        => StudentAttendanceSchedule::MODE_EXAM,
            'is_default'  => false,
            'is_template' => false,
            'is_active'   => true,
            'start_date'  => '2026-12-15',
            'end_date'    => '2026-12-18',
            'priority'    => 25,
            'created_by'  => $this->admin->id,
        ]);

        $partialSlot = StudentAttendanceScheduleSlot::create([
            'schedule_id'              => $partial->id,
            'shift_group'              => 'Morning',
            'slot_name'                => 'Monthly Exam (Nine - Ten)',
            'on_time_start'            => '08:30:00',
            'on_time_end'              => '09:00:00',
            'late_end'                 => '09:10:00',
            'absent_from'              => '09:11:00',
            'check_out_from'           => '11:00:00',
            'early_grace_minutes'      => 60,
            'debounce_minutes'         => 10,
            'min_checkout_gap_minutes' => 15,
            'sort_order'               => 10,
        ]);

        foreach (['Nine', 'Ten'] as $clsName) {
            $partialSlot->slotClasses()->create([
                'schedule_id' => $partial->id,
                'class_id'    => $this->classes[$clsName]->id,
            ]);
        }

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-12-16'])
            ->expectsOutputToContain('Effective Mode on 2026-12-16: MIXED')
            ->expectsOutputToContain('Monthly Test (9-10)')
            ->expectsOutputToContain('Four - Eight')
            ->expectsOutputToContain('Nine, Ten')
            ->expectsOutputToContain('12/12 mapped | 0 unmapped / legacy fallback')
            ->expectsOutputToContain('VERDICT: PASS')
            ->assertExitCode(0);
    }

    /**
     * 3. Unmapped Class Detection:
     *    Adding an unmapped class causes command to name it and exit 1 (FAILURE).
     */
    public function test_unmapped_class_causes_critical_failure_and_exits_one(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        // Add an unmapped class after seeding
        Classes::create(['class_name' => 'Pre-Play', 'user_id' => $this->admin->id]);

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24'])
            ->expectsOutputToContain('12/13 mapped | 1 unmapped / legacy fallback')
            ->expectsOutputToContain('Pre-Play')
            ->expectsOutputToContain('VERDICT: FAIL')
            ->assertExitCode(1);
    }

    /**
     * 4. Missing Default Normal Schedule:
     *    Without seeded schedules, command reports critical error and exits 1.
     */
    public function test_missing_default_normal_schedule_exits_one(): void
    {
        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24'])
            ->expectsOutputToContain('Active default Normal School Time schedule NOT found')
            ->expectsOutputToContain('VERDICT: FAIL')
            ->assertExitCode(1);
    }

    /**
     * 5. Mocked --check-device Clock Drift Thresholds:
     *    15s -> PASS (0), 90s -> PASS WITH WARNINGS (0), 240s -> FAIL (1), Unreachable -> FAIL (1).
     */
    public function test_check_device_clock_drift_thresholds_and_unreachable_handling(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        // 5a. Drift 15s -> OK, exits 0
        $zkOk = $this->createMock(ZktecoService::class);
        $zkOk->expects($this->once())->method('getDeviceClockInfo')->willReturn([
            'reachable'       => true,
            'mode'            => 'live',
            'ip'              => '103.190.229.86',
            'port'            => 4370,
            'device_time'     => '2026-11-24 08:00:15',
            'server_time'     => '2026-11-24 08:00:00',
            'drift_seconds'   => 15,
            'clock_supported' => true,
            'error'           => null,
        ]);
        $this->app->instance(ZktecoService::class, $zkOk);

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24', '--check-device' => true])
            ->expectsOutputToContain('Clock Drift:   15s')
            ->expectsOutputToContain('K60 clock alignment is healthy')
            ->expectsOutputToContain('VERDICT: PASS')
            ->assertExitCode(0);

        // 5b. Drift 90s -> Warning, exits 0
        $zkWarn = $this->createMock(ZktecoService::class);
        $zkWarn->expects($this->once())->method('getDeviceClockInfo')->willReturn([
            'reachable'       => true,
            'mode'            => 'live',
            'ip'              => '103.190.229.86',
            'port'            => 4370,
            'device_time'     => '2026-11-24 08:01:30',
            'server_time'     => '2026-11-24 08:00:00',
            'drift_seconds'   => 90,
            'clock_supported' => true,
            'error'           => null,
        ]);
        $this->app->instance(ZktecoService::class, $zkWarn);

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24', '--check-device' => true])
            ->expectsOutputToContain('Clock Drift:   90s')
            ->expectsOutputToContain('Moderate K60 clock drift')
            ->expectsOutputToContain('VERDICT: PASS WITH WARNINGS')
            ->assertExitCode(0);

        // 5c. Drift 240s -> Critical failure, exits 1
        $zkCrit = $this->createMock(ZktecoService::class);
        $zkCrit->expects($this->once())->method('getDeviceClockInfo')->willReturn([
            'reachable'       => true,
            'mode'            => 'live',
            'ip'              => '103.190.229.86',
            'port'            => 4370,
            'device_time'     => '2026-11-24 08:04:00',
            'server_time'     => '2026-11-24 08:00:00',
            'drift_seconds'   => 240,
            'clock_supported' => true,
            'error'           => null,
        ]);
        $this->app->instance(ZktecoService::class, $zkCrit);

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24', '--check-device' => true])
            ->expectsOutputToContain('Clock Drift:   240s')
            ->expectsOutputToContain('Critical K60 clock drift')
            ->expectsOutputToContain('VERDICT: FAIL')
            ->assertExitCode(1);

        // 5d. Device unreachable -> Critical failure, exits 1
        $zkDown = $this->createMock(ZktecoService::class);
        $zkDown->expects($this->once())->method('getDeviceClockInfo')->willReturn([
            'reachable'       => false,
            'mode'            => 'live',
            'ip'              => '103.190.229.86',
            'port'            => 4370,
            'device_time'     => null,
            'server_time'     => '2026-11-24 08:00:00',
            'drift_seconds'   => null,
            'clock_supported' => false,
            'error'           => 'Unable to establish connection with ZKTeco device.',
        ]);
        $this->app->instance(ZktecoService::class, $zkDown);

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24', '--check-device' => true])
            ->expectsOutputToContain('Reachable:     NO')
            ->expectsOutputToContain('VERDICT: FAIL')
            ->assertExitCode(1);
    }

    /**
     * 6. Direct read-only test of ZktecoService::getDeviceClockInfo() & NULL snapshot audit warning.
     */
    public function test_zkteco_get_device_clock_info_is_read_only_and_snapshot_null_triggers_warning(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        // Test ZktecoService::getDeviceClockInfo() with mocked ZKTeco SDK
        Config::set('services.zkteco.mode', 'live');
        Config::set('services.zkteco.ip', '103.190.229.86');
        Config::set('services.zkteco.port', 4370);
        Carbon::setTestNow(Carbon::parse('2026-11-24 08:15:00', 'Asia/Dhaka'));

        $sdkMock = $this->createMock(ZKTeco::class);
        $sdkMock->expects($this->once())->method('connect')->willReturn(true);
        $sdkMock->expects($this->once())->method('getTime')->willReturn('2026-11-24 08:15:42');
        $sdkMock->expects($this->once())->method('disconnect')->willReturn(true);
        $sdkMock->expects($this->never())->method('setTime');
        $sdkMock->expects($this->never())->method('clearAttendance');
        $sdkMock->expects($this->never())->method('setUser');
        $this->app->instance(ZKTeco::class, $sdkMock);

        $service = new ZktecoService();
        $clockInfo = $service->getDeviceClockInfo();

        $this->assertTrue($clockInfo['reachable']);
        $this->assertTrue($clockInfo['clock_supported']);
        $this->assertSame('2026-11-24 08:15:42', $clockInfo['device_time']);
        $this->assertSame('2026-11-24 08:15:00', $clockInfo['server_time']);
        $this->assertSame(42, $clockInfo['drift_seconds']);
        $this->assertNull($clockInfo['error']);

        // Test attendance snapshot warning when a biometric row has schedule_mode = NULL
        $student = Student::create([
            'user_id'               => $this->admin->id,
            'student_identity'      => 'STD-VER-01',
            'card_number'           => 'VERCARD01',
            'student_name'          => 'Audit Student',
            'roll_number'           => '1',
            'dob'                   => '2016-01-01',
            'gender'                => 'Male',
            'father_name'           => 'Father',
            'father_mobile'         => '01710000001',
            'mother_name'           => 'Mother',
            'mother_mobile'         => '01710000002',
            'guardian_mobile'       => '01710000001',
            'branch_id'             => $this->branchMain->id,
            'session_year_id'       => $this->session->id,
            'class_id'              => $this->classes['One']->id,
            'section_id'            => $this->section->id,
            'status'                => 'Active',
            'sms_status'            => 'Active',
            'present_village'       => 'Jalalpur',
            'present_post_office'   => 'Jalalpur',
            'present_district'      => 'Sylhet',
            'present_division'      => 'Sylhet',
            'permanent_village'     => 'Jalalpur',
            'permanent_post_office' => 'Jalalpur',
            'permanent_district'    => 'Sylhet',
            'permanent_division'    => 'Sylhet',
        ]);

        Attendance::create([
            'student_id'      => $student->id,
            'attendance_date' => '2026-11-24',
            'branch_id'       => $this->branchMain->id,
            'session_year_id' => $this->session->id,
            'class_id'        => $this->classes['One']->id,
            'section_id'      => $this->section->id,
            'user_id'         => $this->admin->id,
            'status'          => 'Present',
            'remarks'         => 'Card Swiped (In: 08:05:00)',
            'schedule_mode'   => null,
        ]);

        $this->artisan('attendance:verify-schedule', ['--date' => '2026-11-24'])
            ->expectsOutputToContain('1 biometric attendance row(s) on 2026-11-24 have schedule_mode = NULL')
            ->expectsOutputToContain('VERDICT: PASS WITH WARNINGS')
            ->assertExitCode(0);
    }
}
