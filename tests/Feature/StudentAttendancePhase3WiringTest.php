<?php

namespace Tests\Feature;

use App\Console\Commands\MonitorAttendance;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\StudentAttendanceSchedule;
use App\Models\StudentAttendanceScheduleSlot;
use App\Models\User;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Database\Seeders\StudentAttendanceScheduleSeeder;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentAttendancePhase3WiringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private SessionYear $session;
    private Section $section;
    /** @var array<string, Classes> */
    private array $classes = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Config::set('services.zkteco.mode', 'live');
        Config::set('services.mim_sms.test_mode', false);
        Config::set('services.mim_sms.username', 'macs_user');
        Config::set('services.mim_sms.api_key', 'macs_key');
        Config::set('services.mim_sms.sender_id', 'MACS_SCHOOL');

        $this->admin = User::create([
            'name'     => 'Phase 3 Admin',
            'email'    => 'phase3_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);

        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->admin->assignRole($superAdminRole);
        $this->actingAs($this->admin);

        $this->branch = Branch::create(['branch_name' => 'Jalalpur Branch', 'user_id' => $this->admin->id]);
        $this->session = SessionYear::create(['session_name' => '2026', 'user_id' => $this->admin->id]);
        $this->section = Section::create(['section_name' => 'Section A', 'user_id' => $this->admin->id]);

        foreach (['Play', 'Nursery', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'] as $name) {
            $this->classes[$name] = Classes::create(['class_name' => $name, 'user_id' => $this->admin->id]);
        }

        $this->seed(StudentAttendanceScheduleSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createStudent(string $className, array $overrides = []): Student
    {
        static $seq = 1;
        $num = $seq++;

        return Student::create(array_merge([
            'user_id'               => $this->admin->id,
            'student_identity'      => sprintf('STD-P3-%04d', $num),
            'card_number'           => sprintf('009900%04d', $num),
            'student_name'          => "Student {$className} {$num}",
            'roll_number'           => (string) $num,
            'dob'                   => '2015-01-01',
            'gender'                => 'Male',
            'father_name'           => "Father {$num}",
            'father_mobile'         => '0171000' . str_pad((string) $num, 4, '0', STR_PAD_LEFT),
            'mother_name'           => "Mother {$num}",
            'mother_mobile'         => '0172000' . str_pad((string) $num, 4, '0', STR_PAD_LEFT),
            'guardian_name'         => "Guardian {$num}",
            'guardian_mobile'       => '0173000' . str_pad((string) $num, 4, '0', STR_PAD_LEFT),
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session->id,
            'class_id'              => $this->classes[$className]->id,
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
        ], $overrides));
    }

    private function fakeSmsSuccess(): void
    {
        Http::fake([
            'https://api.mimsms.com/*' => Http::response([
                'StatusCode' => '200',
                'StatusName' => 'Successful',
                'trxnId'     => 'TXN-P3-OK',
            ], 200),
        ]);
    }

    private function createExamCampaignFromTemplate(string $name, string $startDate, string $endDate): StudentAttendanceSchedule
    {
        $template = StudentAttendanceSchedule::templates()->with('slots.classes')->firstOrFail();

        $campaign = StudentAttendanceSchedule::create([
            'name'        => $name,
            'mode'        => StudentAttendanceSchedule::MODE_EXAM,
            'branch_id'   => null,
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

        return $campaign->fresh('slots.classes');
    }

    /**
     * 1. Registry key parity & deduplication across MonitorAttendance and AttendanceController::syncBiometric.
     */
    public function test_registry_key_parity_and_deduplication_across_monitor_and_manual_sync(): void
    {
        $this->fakeSmsSuccess();
        $date = '2026-11-24';

        $studentA = $this->createStudent('One', ['card_number' => 'CARD_A_001']);
        $studentB = $this->createStudent('Two', ['card_number' => 'CARD_B_002']);
        $studentC = $this->createStudent('Three', ['card_number' => 'CARD_C_003']);
        $studentD = $this->createStudent('One', ['card_number' => 'CARD_D_004']);
        $inactive = $this->createStudent('One', ['card_number' => 'CARD_INACT', 'status' => 'Inactive']);
        $student569 = $this->createStudent('One', ['id' => 569, 'card_number' => 'CARD_569']);

        // Raw device logs with overlapping keys for Student A + all 4 key styles + reserved id:569 + inactive student
        $rawLogs = [
            "student:{$studentA->id}" => ['07:55:00'],
            "card:CARD_A_001"         => ['07:55:00', '11:10:00'], // Overlapping with student:{id} -> must merge & dedupe
            "card:CARD_B_002"         => ['08:05:00'],
            'CARD_C_003'              => ['08:20:00'], // Late in Normal Morning (08:16-08:30)
            "id:{$studentD->id}"      => ['08:10:00'],
            'id:569'                  => ['08:00:00'], // Reserved staff PIN 569 must be ignored!
            'CARD_INACT'              => ['08:00:00'], // Inactive student must be ignored!
        ];

        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->with($date)->willReturn($rawLogs);

        $monitor = app(MonitorAttendance::class);
        $updated = $monitor->processPollingCycle($zkMock, $date);

        $this->assertSame(4, $updated);

        // Student A merged both keys into a single row with In & Out
        $attA = Attendance::where('student_id', $studentA->id)->where('attendance_date', $date)->first();
        $this->assertNotNull($attA);
        $this->assertSame('Present', $attA->status);
        $this->assertSame('Card Swiped (In: 07:55:00, Out: 11:10:00)', $attA->remarks);
        $this->assertSame('normal', $attA->schedule_mode);
        $this->assertSame('Morning (Play - Three)', $attA->schedule_slot_name);

        // Student B (`card:` key)
        $attB = Attendance::where('student_id', $studentB->id)->where('attendance_date', $date)->first();
        $this->assertNotNull($attB);
        $this->assertSame('Present', $attB->status);
        $this->assertSame('Card Swiped (In: 08:05:00)', $attB->remarks);

        // Student C (raw card key, Late at 08:20:00)
        $attC = Attendance::where('student_id', $studentC->id)->where('attendance_date', $date)->first();
        $this->assertNotNull($attC);
        $this->assertSame('Late', $attC->status);
        $this->assertSame('Card Swiped (In: 08:20:00)', $attC->remarks);

        // Student D (`id:` key)
        $attD = Attendance::where('student_id', $studentD->id)->where('attendance_date', $date)->first();
        $this->assertNotNull($attD);
        $this->assertSame('Present', $attD->status);

        // Reserved id:569 and Inactive student must have ZERO attendance rows from MonitorAttendance
        $this->assertDatabaseMissing('attendances', ['student_id' => $student569->id, 'attendance_date' => $date]);
        $this->assertDatabaseMissing('attendances', ['student_id' => $inactive->id, 'attendance_date' => $date]);

        // Second identical polling cycle must do zero updates and send zero extra SMS
        $smsCountAfterPoll1 = SmsLog::where('status', 'Sent')->count();
        $this->assertSame(5, $smsCountAfterPoll1); // A: in+out (2), B: in (1), C: in (1), D: in (1)

        $updatedAgain = $monitor->processPollingCycle($zkMock, $date);
        $this->assertSame(0, $updatedAgain);
        $this->assertSame($smsCountAfterPoll1, SmsLog::where('status', 'Sent')->count());

        // Manual sync (`syncBiometric`) on the exact same logs must produce identical statuses/remarks & zero duplicate SMS
        $this->app->instance(ZktecoService::class, $zkMock);
        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $date,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'status'       => 'success',
            'synced_count' => 4,
            'absent_count' => 1, // student569 is Active but id:569 was excluded -> marked Absent (Biometric Check)
        ]);

        $this->assertSame('Absent', Attendance::where('student_id', $student569->id)->where('attendance_date', $date)->value('status'));
        $this->assertSame($smsCountAfterPoll1, SmsLog::where('status', 'Sent')->count());
    }

    /**
     * 2. Double-tap debounce (07:50:00, 07:55:00) records In only (no Out) and sends zero Check-Out SMS.
     */
    public function test_double_tap_debounce_records_in_only_and_suppresses_checkout_sms(): void
    {
        $this->fakeSmsSuccess();
        $date = '2026-11-24';

        $studentMonitor = $this->createStudent('Two', ['card_number' => 'DEBOUNCE_MON']);
        $studentSync = $this->createStudent('Two', ['card_number' => 'DEBOUNCE_SYNC']);

        // Poll 1: single check-in at 07:50:00
        $zkPoll1 = $this->createMock(ZktecoService::class);
        $zkPoll1->method('getRawLogsByCard')->with($date)->willReturn([
            "student:{$studentMonitor->id}" => ['07:50:00'],
        ]);

        $monitor = app(MonitorAttendance::class);
        $this->assertSame(1, $monitor->processPollingCycle($zkPoll1, $date));

        // Poll 2: double-tap 5 minutes later (07:55:00) -> debounced; no DB update, no Out:, no Check-Out SMS
        $zkPoll2 = $this->createMock(ZktecoService::class);
        $zkPoll2->method('getRawLogsByCard')->with($date)->willReturn([
            "student:{$studentMonitor->id}" => ['07:50:00', '07:55:00'],
        ]);
        $this->assertSame(0, $monitor->processPollingCycle($zkPoll2, $date));

        $attMonitor = Attendance::where('student_id', $studentMonitor->id)->where('attendance_date', $date)->firstOrFail();
        $this->assertSame('Present', $attMonitor->status);
        $this->assertSame('Card Swiped (In: 07:50:00)', $attMonitor->remarks);
        $this->assertStringNotContainsString('Out:', $attMonitor->remarks);
        $this->assertSame(1, SmsLog::where('student_id', $studentMonitor->id)->where('status', 'Sent')->count());

        // Same scenario via AttendanceController::syncBiometric()
        $zkSync = $this->createMock(ZktecoService::class);
        $zkSync->method('getRawLogsByCard')->with($date)->willReturn([
            'DEBOUNCE_SYNC' => ['07:50:00', '07:55:00'],
        ]);
        $this->app->instance(ZktecoService::class, $zkSync);

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $date,
            'class_id'        => $this->classes['Two']->id,
        ]);
        $response->assertStatus(200);

        $attSync = Attendance::where('student_id', $studentSync->id)->where('attendance_date', $date)->firstOrFail();
        $this->assertSame('Present', $attSync->status);
        $this->assertSame('Card Swiped (In: 07:50:00)', $attSync->remarks);
        $this->assertStringNotContainsString('Out:', $attSync->remarks);
        $this->assertSame(1, SmsLog::where('student_id', $studentSync->id)->where('status', 'Sent')->count());
        $this->assertSame(0, SmsLog::where('event_key', 'like', 'att:check_out:%')->count());
    }

    /**
     * 3. Pre-check-out gating in Exam Morning (Play-Four, check_out_from = 09:40:00):
     *    08:10:00 + 09:25:00 (75 min gap, but < 09:40:00) -> In only + zero Check-Out SMS.
     */
    public function test_exam_morning_pre_checkout_gating_suppresses_early_checkout_in_db_and_sms(): void
    {
        $this->fakeSmsSuccess();
        $campaign = $this->createExamCampaignFromTemplate('1st Term Exam 2026', '2026-12-01', '2026-12-10');
        $morningExamSlot = $campaign->slots->firstWhere('shift_group', 'Morning');
        $examDate = '2026-12-03';

        $studentMon = $this->createStudent('Four', ['card_number' => 'EXAM_PRE_MON']);
        $studentSync = $this->createStudent('Four', ['card_number' => 'EXAM_PRE_SYNC']);

        // MonitorAttendance path
        $zkMon = $this->createMock(ZktecoService::class);
        $zkMon->method('getRawLogsByCard')->with($examDate)->willReturn([
            "student:{$studentMon->id}" => ['08:10:00', '09:25:00'],
        ]);
        app(MonitorAttendance::class)->processPollingCycle($zkMon, $examDate);

        $attMon = Attendance::where('student_id', $studentMon->id)->where('attendance_date', $examDate)->firstOrFail();
        $this->assertSame('Present', $attMon->status);
        $this->assertSame('Card Swiped (In: 08:10:00)', $attMon->remarks);
        $this->assertSame('exam', $attMon->schedule_mode);
        $this->assertSame($morningExamSlot->id, $attMon->schedule_slot_id);
        $this->assertSame('Morning Exam (Play - Four)', $attMon->schedule_slot_name);

        // Manual sync path
        $zkSync = $this->createMock(ZktecoService::class);
        $zkSync->method('getRawLogsByCard')->with($examDate)->willReturn([
            'EXAM_PRE_SYNC' => ['08:10:00', '09:25:00'],
        ]);
        $this->app->instance(ZktecoService::class, $zkSync);

        $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $examDate,
            'class_id'        => $this->classes['Four']->id,
        ])->assertStatus(200);

        $attSync = Attendance::where('student_id', $studentSync->id)->where('attendance_date', $examDate)->firstOrFail();
        $this->assertSame('Present', $attSync->status);
        $this->assertSame('Card Swiped (In: 08:10:00)', $attSync->remarks);
        $this->assertSame('exam', $attSync->schedule_mode);
        $this->assertSame($morningExamSlot->id, $attSync->schedule_slot_id);

        // Exactly 2 Check-In SMS (one per student) and ZERO Check-Out SMS
        $this->assertSame(2, SmsLog::where('event_key', 'like', 'att:check_in:%')->where('status', 'Sent')->count());
        $this->assertSame(0, SmsLog::where('event_key', 'like', 'att:check_out:%')->count());
    }

    /**
     * 4. Tight Exam Morning valid check-out + snapshot:
     *    09:10:00 (Late) + 09:40:00 (30-min gap >= 15 min & == check_out_from 09:40:00)
     *    -> Late, In & Out persisted, exam snapshot saved, Check-In + Check-Out SMS sent once each.
     */
    public function test_tight_exam_morning_valid_checkout_persists_snapshot_and_dispatches_both_sms(): void
    {
        $this->fakeSmsSuccess();
        $campaign = $this->createExamCampaignFromTemplate('2nd Term Exam 2026', '2026-12-01', '2026-12-10');
        $morningExamSlot = $campaign->slots->firstWhere('shift_group', 'Morning');
        $examDate = '2026-12-04';

        $studentMon = $this->createStudent('Four', ['card_number' => 'EXAM_TIGHT_MON']);
        $studentSync = $this->createStudent('Play', ['card_number' => 'EXAM_TIGHT_SYNC']);

        $monitor = app(MonitorAttendance::class);

        // Poll 1: Check-In at 09:10:00 (Exam Morning Late window 09:01-09:10)
        $zkPoll1 = $this->createMock(ZktecoService::class);
        $zkPoll1->method('getRawLogsByCard')->with($examDate)->willReturn([
            "student:{$studentMon->id}" => ['09:10:00'],
        ]);
        $this->assertSame(1, $monitor->processPollingCycle($zkPoll1, $examDate));

        $attMon = Attendance::where('student_id', $studentMon->id)->where('attendance_date', $examDate)->firstOrFail();
        $this->assertSame('Late', $attMon->status);
        $this->assertSame('Card Swiped (In: 09:10:00)', $attMon->remarks);
        $this->assertSame('exam', $attMon->schedule_mode);
        $this->assertSame($morningExamSlot->id, $attMon->schedule_slot_id);
        $this->assertSame('Morning Exam (Play - Four)', $attMon->schedule_slot_name);
        $this->assertSame(1, SmsLog::where('event_key', "att:check_in:{$studentMon->id}:{$examDate}")->where('status', 'Sent')->count());
        $this->assertSame(0, SmsLog::where('event_key', "att:check_out:{$studentMon->id}:{$examDate}")->count());

        // Poll 2: Check-Out at 09:40:00 (30-min gap & == check_out_from 09:40:00)
        $zkPoll2 = $this->createMock(ZktecoService::class);
        $zkPoll2->method('getRawLogsByCard')->with($examDate)->willReturn([
            "student:{$studentMon->id}" => ['09:10:00', '09:40:00'],
        ]);
        $this->assertSame(1, $monitor->processPollingCycle($zkPoll2, $examDate));

        $attMon->refresh();
        $this->assertSame('Late', $attMon->status);
        $this->assertSame('Card Swiped (In: 09:10:00, Out: 09:40:00)', $attMon->remarks);
        $this->assertSame(1, SmsLog::where('event_key', "att:check_in:{$studentMon->id}:{$examDate}")->where('status', 'Sent')->count());
        $this->assertSame(1, SmsLog::where('event_key', "att:check_out:{$studentMon->id}:{$examDate}")->where('status', 'Sent')->count());

        // Poll 3: Repeat same punches -> zero updates, zero duplicate SMS
        $this->assertSame(0, $monitor->processPollingCycle($zkPoll2, $examDate));
        $this->assertSame(2, SmsLog::count());

        // Manual sync path for Play student with same 09:10:00 + 09:40:00 punches
        $zkSync = $this->createMock(ZktecoService::class);
        $zkSync->method('getRawLogsByCard')->with($examDate)->willReturn([
            "card:EXAM_TIGHT_SYNC" => ['09:10:00', '09:40:00'],
        ]);
        $this->app->instance(ZktecoService::class, $zkSync);

        $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $examDate,
            'class_id'        => $this->classes['Play']->id,
        ])->assertStatus(200)->assertJson([
            'status'       => 'success',
            'synced_count' => 1,
            'absent_count' => 0,
        ]);

        $attSync = Attendance::where('student_id', $studentSync->id)->where('attendance_date', $examDate)->firstOrFail();
        $this->assertSame('Late', $attSync->status);
        $this->assertSame('Card Swiped (In: 09:10:00, Out: 09:40:00)', $attSync->remarks);
        $this->assertSame('exam', $attSync->schedule_mode);
        $this->assertSame($morningExamSlot->id, $attSync->schedule_slot_id);
        $this->assertSame(1, SmsLog::where('event_key', "att:check_in:{$studentSync->id}:{$examDate}")->where('status', 'Sent')->count());
        $this->assertSame(1, SmsLog::where('event_key', "att:check_out:{$studentSync->id}:{$examDate}")->where('status', 'Sent')->count());
    }

    /**
     * 5. Late-Absent consistency (08:35:00 + 11:15:00 in Normal Morning >= 08:31:00):
     *    Persists status = Absent, records BOTH In & Out, never upgrades to Present, sends ZERO SMS.
     */
    public function test_late_absent_preserves_in_and_out_never_upgrades_and_sends_zero_sms(): void
    {
        $this->fakeSmsSuccess();
        $date = '2026-11-24';

        $studentMon = $this->createStudent('One', ['card_number' => 'LATE_ABS_MON']);
        $studentSync = $this->createStudent('One', ['card_number' => 'LATE_ABS_SYNC']);

        $monitor = app(MonitorAttendance::class);

        // Poll 1: First punch at 08:35:00 (>= absent_from 08:31:00)
        $zkPoll1 = $this->createMock(ZktecoService::class);
        $zkPoll1->method('getRawLogsByCard')->with($date)->willReturn([
            "student:{$studentMon->id}" => ['08:35:00'],
        ]);
        $this->assertSame(1, $monitor->processPollingCycle($zkPoll1, $date));

        $attMon = Attendance::where('student_id', $studentMon->id)->where('attendance_date', $date)->firstOrFail();
        $this->assertSame('Absent', $attMon->status);
        $this->assertSame('Card Swiped (In: 08:35:00)', $attMon->remarks);
        $this->assertSame('normal', $attMon->schedule_mode);
        $this->assertSame('Morning (Play - Three)', $attMon->schedule_slot_name);

        // Poll 2: Leaves at 11:15:00 -> MUST stay Absent (never upgrade to Present), MUST record Out: 11:15:00
        $zkPoll2 = $this->createMock(ZktecoService::class);
        $zkPoll2->method('getRawLogsByCard')->with($date)->willReturn([
            "student:{$studentMon->id}" => ['08:35:00', '11:15:00'],
        ]);
        $this->assertSame(1, $monitor->processPollingCycle($zkPoll2, $date));

        $attMon->refresh();
        $this->assertSame('Absent', $attMon->status);
        $this->assertSame('Card Swiped (In: 08:35:00, Out: 11:15:00)', $attMon->remarks);

        // Manual sync path for both students with identical Late-Absent punches
        $zkSync = $this->createMock(ZktecoService::class);
        $zkSync->method('getRawLogsByCard')->with($date)->willReturn([
            "student:{$studentMon->id}" => ['08:35:00', '11:15:00'],
            'LATE_ABS_SYNC'             => ['08:35:00', '11:15:00'],
        ]);
        $this->app->instance(ZktecoService::class, $zkSync);

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $date,
            'class_id'        => $this->classes['One']->id,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'status'       => 'success',
            'synced_count' => 0,
            'absent_count' => 2,
        ]);

        $attSync = Attendance::where('student_id', $studentSync->id)->where('attendance_date', $date)->firstOrFail();
        $this->assertSame('Absent', $attSync->status);
        $this->assertSame('Card Swiped (In: 08:35:00, Out: 11:15:00)', $attSync->remarks);
        $this->assertSame('normal', $attSync->schedule_mode);

        // ZERO SMS sent across both MonitorAttendance and syncBiometric
        $this->assertSame(0, SmsLog::count());
        Http::assertNothingSent();
    }

    /**
     * 6. Noise-only punches below the 06:00:00 noise floor are discarded cleanly without SMS.
     */
    public function test_noise_floor_punches_are_ignored_in_monitor_and_marked_absent_in_manual_sync(): void
    {
        $this->fakeSmsSuccess();
        $date = '2026-11-24';
        $student = $this->createStudent('One', ['card_number' => 'NOISE_CARD_01']);

        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->with($date)->willReturn([
            'NOISE_CARD_01' => ['05:30:00', '05:45:00'],
        ]);

        // MonitorAttendance skips noise completely (no row created)
        $updated = app(MonitorAttendance::class)->processPollingCycle($zkMock, $date);
        $this->assertSame(0, $updated);
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'attendance_date' => $date]);

        // Manual sync marks student Absent (Biometric Check) with normal schedule snapshot and zero SMS
        $this->app->instance(ZktecoService::class, $zkMock);
        $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $date,
            'class_id'        => $this->classes['One']->id,
        ])->assertStatus(200)->assertJson([
            'synced_count' => 0,
            'absent_count' => 1,
        ]);

        $att = Attendance::where('student_id', $student->id)->where('attendance_date', $date)->firstOrFail();
        $this->assertSame('Absent', $att->status);
        $this->assertSame('Absent (Biometric Check)', $att->remarks);
        $this->assertSame('normal', $att->schedule_mode);
        $this->assertSame(0, SmsLog::count());
        Http::assertNothingSent();
    }

    /**
     * 7. Historical date re-sync fidelity:
     *    Syncing a past Exam Campaign date while today's clock is on a Normal School Time date
     *    resolves the Exam schedule for Class Four (Morning Exam), Five-Nine (Day Exam), and Class Ten (Dedicated Slot).
     */
    public function test_historical_date_resync_uses_exam_campaign_even_when_current_clock_is_normal_time(): void
    {
        $this->fakeSmsSuccess();
        $campaign = $this->createExamCampaignFromTemplate('Annual Exam 2026', '2026-11-10', '2026-11-20');
        $morningExamSlot = $campaign->slots->firstWhere('shift_group', 'Morning');
        $fiveNineExamSlot = $campaign->slots->firstWhere('slot_name', 'Day Exam (Five - Nine)');
        $tenExamSlot = $campaign->slots->firstWhere('slot_name', 'Day Exam (Class Ten)');

        // Current wall clock is AFTER exam campaign ended (Normal School Time)
        Carbon::setTestNow(Carbon::parse('2026-11-26 10:00:00', 'Asia/Dhaka'));

        $studentFour = $this->createStudent('Four', ['card_number' => 'HIST_FOUR']);
        $studentFive = $this->createStudent('Five', ['card_number' => 'HIST_FIVE']);
        $studentTen = $this->createStudent('Ten', ['card_number' => 'HIST_TEN']);

        $historicalExamDate = '2026-11-15';

        $zkHist = $this->createMock(ZktecoService::class);
        $zkHist->method('getRawLogsByCard')->with($historicalExamDate)->willReturn([
            'HIST_FOUR' => ['08:25:00', '10:00:00'], // Morning Exam (Play-Four): 08:00-09:00 Present, 09:40+ Out
            'HIST_FIVE' => ['12:05:00', '13:05:00'], // Day Exam (Five-Nine): 12:01-12:10 Late, 13:00+ Out
            'HIST_TEN'  => ['09:25:00', '12:55:00'], // Day Exam (Class Ten): 09:21-09:30 Late, 12:50+ Out
        ]);
        $this->app->instance(ZktecoService::class, $zkHist);

        $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $historicalExamDate,
        ])->assertStatus(200)->assertJson([
            'status'       => 'success',
            'synced_count' => 3,
            'absent_count' => 0,
        ]);

        $attFourExam = Attendance::where('student_id', $studentFour->id)->where('attendance_date', $historicalExamDate)->firstOrFail();
        $this->assertSame('Present', $attFourExam->status);
        $this->assertSame('Card Swiped (In: 08:25:00, Out: 10:00:00)', $attFourExam->remarks);
        $this->assertSame('exam', $attFourExam->schedule_mode);
        $this->assertSame($morningExamSlot->id, $attFourExam->schedule_slot_id);
        $this->assertSame('Morning Exam (Play - Four)', $attFourExam->schedule_slot_name);

        $attFiveExam = Attendance::where('student_id', $studentFive->id)->where('attendance_date', $historicalExamDate)->firstOrFail();
        $this->assertSame('Late', $attFiveExam->status);
        $this->assertSame('Card Swiped (In: 12:05:00, Out: 13:05:00)', $attFiveExam->remarks);
        $this->assertSame('exam', $attFiveExam->schedule_mode);
        $this->assertSame($fiveNineExamSlot->id, $attFiveExam->schedule_slot_id);

        $attTenExam = Attendance::where('student_id', $studentTen->id)->where('attendance_date', $historicalExamDate)->firstOrFail();
        $this->assertSame('Late', $attTenExam->status);
        $this->assertSame('Card Swiped (In: 09:25:00, Out: 12:55:00)', $attTenExam->remarks);
        $this->assertSame('exam', $attTenExam->schedule_mode);
        $this->assertSame($tenExamSlot->id, $attTenExam->schedule_slot_id);
        $this->assertSame('Day Exam (Class Ten)', $attTenExam->schedule_slot_name);

        // Now sync TODAY (2026-11-26, Normal School Time) -> Class Four automatically uses Day (Four - Ten)
        $normalDate = '2026-11-26';
        $zkNormal = $this->createMock(ZktecoService::class);
        $zkNormal->method('getRawLogsByCard')->with($normalDate)->willReturn([
            'HIST_FOUR' => ['12:18:00', '15:40:00'], // Day Normal (Four-Ten): 12:16-12:20 Late, 15:30+ Out
        ]);
        $this->app->instance(ZktecoService::class, $zkNormal);

        $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $normalDate,
            'class_id'        => $this->classes['Four']->id,
        ])->assertStatus(200);

        $attFourNormal = Attendance::where('student_id', $studentFour->id)->where('attendance_date', $normalDate)->firstOrFail();
        $this->assertSame('Late', $attFourNormal->status);
        $this->assertSame('Card Swiped (In: 12:18:00, Out: 15:40:00)', $attFourNormal->remarks);
        $this->assertSame('normal', $attFourNormal->schedule_mode);
        $this->assertSame('Day (Four - Ten)', $attFourNormal->schedule_slot_name);

        // Historical exam snapshot remains intact
        $attFourExam->refresh();
        $this->assertSame('exam', $attFourExam->schedule_mode);
        $this->assertSame('Morning Exam (Play - Four)', $attFourExam->schedule_slot_name);
    }

    /**
     * 8. SimulateSwipe uses resolved schedule (no hardcoded 09:00 threshold), saves snapshot,
     *    never sends SMS, and is blocked in production.
     */
    public function test_simulate_swipe_uses_active_schedule_saves_snapshot_and_blocks_production(): void
    {
        $student = $this->createStudent('Two', ['card_number' => 'SIM_SCHED_01']);

        // 08:20:00 is Late under Normal Morning (08:16-08:30), whereas legacy hardcoded 09:00:00 would mark Present
        Carbon::setTestNow(Carbon::parse('2026-11-24 08:20:00', 'Asia/Dhaka'));
        $exitIn = Artisan::call('attendance:simulate-swipe', ['card_number' => 'SIM_SCHED_01']);
        $this->assertSame(0, $exitIn);

        $att = Attendance::where('student_id', $student->id)->where('attendance_date', '2026-11-24')->firstOrFail();
        $this->assertSame('Late', $att->status);
        $this->assertSame('Card Swiped (In: 08:20:00) [Simulated]', $att->remarks);
        $this->assertSame('normal', $att->schedule_mode);
        $this->assertSame('Morning (Play - Three)', $att->schedule_slot_name);

        // Second simulated swipe at 11:10:00 records simulated Out preserving In: 08:20:00
        Carbon::setTestNow(Carbon::parse('2026-11-24 11:10:00', 'Asia/Dhaka'));
        $exitOut = Artisan::call('attendance:simulate-swipe', ['card_number' => 'SIM_SCHED_01']);
        $this->assertSame(0, $exitOut);

        $att->refresh();
        $this->assertSame('Late', $att->status);
        $this->assertSame('Card Swiped (In: 08:20:00, Out: 11:10:00) [Simulated]', $att->remarks);
        $this->assertSame(0, SmsLog::count());
        Http::assertNothingSent();

        // Production environment must block SimulateSwipe with exit code 1
        $this->app->detectEnvironment(fn () => 'production');
        $exitProd = Artisan::call('attendance:simulate-swipe', ['card_number' => 'SIM_SCHED_01']);
        $this->assertSame(1, $exitProd);
    }
}
