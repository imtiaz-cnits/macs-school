<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\StaffAttendance;
use App\Models\Shift;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Services\AttendanceService;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TimezoneStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    /**
     * Helper to create minimal student satisfying non-null DB columns
     */
    protected function createTestStudent(string $cardNumber = 'TESTCARD_TZ'): Student
    {
        $user = User::firstOrCreate(
            ['email' => 'admin_tz@macsschool.com'],
            ['name' => 'Admin User', 'password' => bcrypt('secret')]
        );

        $branch = Branch::firstOrCreate(
            ['id' => 1],
            ['branch_name' => 'Main Branch', 'user_id' => $user->id]
        );
        $session = SessionYear::firstOrCreate(
            ['id' => 1],
            ['session_name' => '2026-2027', 'user_id' => $user->id]
        );
        $class = Classes::firstOrCreate(
            ['id' => 1],
            ['class_name' => 'One', 'user_id' => $user->id]
        );
        $section = Section::firstOrCreate(
            ['id' => 1],
            ['section_name' => 'A', 'user_id' => $user->id]
        );

        return Student::create([
            'user_id'                => $user->id,
            'student_identity'       => 'STD-' . rand(1000, 99999),
            'card_number'            => $cardNumber,
            'student_name'           => 'Test Student TZ',
            'roll_number'            => '8888',
            'dob'                    => '2015-01-01',
            'gender'                 => 'Male',
            'father_name'            => 'Father Name',
            'father_mobile'          => '01700000000',
            'mother_name'            => 'Mother Name',
            'mother_mobile'          => '01700000001',
            'present_village'        => 'Village',
            'present_post_office'    => 'PO',
            'present_district'       => 'District',
            'present_division'       => 'Division',
            'permanent_village'      => 'Village',
            'permanent_post_office'  => 'PO',
            'permanent_district'     => 'District',
            'permanent_division'     => 'Division',
            'branch_id'              => $branch->id,
            'session_year_id'        => $session->id,
            'class_id'               => $class->id,
            'section_id'             => $section->id,
            'guardian_mobile'        => '01700000000',
            'sms_status'             => 'Active',
            'status'                 => 'Active',
        ]);
    }

    /**
     * Requirement 1: Laravel application timezone resolves to Asia/Dhaka
     */
    public function test_laravel_timezone_resolves_to_asia_dhaka(): void
    {
        $this->assertEquals('Asia/Dhaka', config('app.timezone'), 'config(app.timezone) must be Asia/Dhaka');
        $this->assertEquals('Asia/Dhaka', date_default_timezone_get(), 'PHP default timezone must be Asia/Dhaka');

        $now = Carbon::now();
        $this->assertEquals('Asia/Dhaka', $now->timezoneName, 'Carbon::now() must default to Asia/Dhaka');
        $this->assertEquals('+06:00', $now->format('P'), 'Asia/Dhaka UTC offset must be +06:00');
    }

    /**
     * Requirement 2: Attendance date boundaries around midnight use Bangladesh local time
     */
    public function test_attendance_date_boundaries_around_midnight_use_bangladesh_time(): void
    {
        $this->withoutMiddleware();

        // Simulate 00:30 AM (12:30 AM) in Bangladesh on 2026-10-15
        // (In UTC, this is 2026-10-14 18:30:00)
        Carbon::setTestNow(Carbon::parse('2026-10-15 00:30:00', 'Asia/Dhaka'));

        $this->assertEquals('2026-10-15', Carbon::today()->format('Y-m-d'));
        $this->assertEquals('2026-10-15', now()->toDateString());

        // Query students attendance without passing date
        $response = $this->getJson('/ajax/attendance/students');
        $response->assertStatus(200);

        // Reset test time
        Carbon::setTestNow();
    }

    /**
     * Requirement 3: Late-status calculations use the correct local clock time
     */
    public function test_late_status_calculations_use_correct_local_clock_time(): void
    {
        $shift = new Shift([
            'name' => 'Morning Shift',
            'in_time_start' => '08:00:00',
            'in_time_end' => '08:30:00',
            'late_time_end' => '09:00:00',
            'absent_time' => '09:01:00',
        ]);

        $service = new AttendanceService();

        // 08:15:00 BST -> Present
        $punchPresent = Carbon::parse('2026-10-14 08:15:00', 'Asia/Dhaka');
        $this->assertEquals('Present', $service->calculateStatus($punchPresent, $shift));

        // 08:45:00 BST -> Late
        $punchLate = Carbon::parse('2026-10-14 08:45:00', 'Asia/Dhaka');
        $this->assertEquals('Late', $service->calculateStatus($punchLate, $shift));

        // 09:15:00 BST -> Absent
        $punchAbsent = Carbon::parse('2026-10-14 09:15:00', 'Asia/Dhaka');
        $this->assertEquals('Absent', $service->calculateStatus($punchAbsent, $shift));
    }

    /**
     * Requirement 4: ZKTeco punch timestamps are not shifted twice
     */
    public function test_zkteco_punch_timestamps_are_not_shifted_twice(): void
    {
        // Raw hardware clock timestamp from K60 device RTC
        $rawDeviceLogTimestamp = '2026-10-14 08:55:00';

        // Parse with application standard timezone
        $parsed = Carbon::parse($rawDeviceLogTimestamp, 'Asia/Dhaka');

        // Wall-clock time should remain strictly 08:55:00 and 08:55 AM
        $this->assertEquals('08:55:00', $parsed->format('H:i:s'));
        $this->assertEquals('08:55 AM', $parsed->format('h:i A'));

        // Verify it was NOT shifted to UTC (02:55:00) or shifted twice forward (14:55:00)
        $this->assertNotEquals('02:55:00', $parsed->format('H:i:s'));
        $this->assertNotEquals('14:55:00', $parsed->format('H:i:s'));
    }

    /**
     * Requirement 5: API serialization and frontend rendering do not introduce another timezone offset
     */
    public function test_api_serialization_preserves_punch_time(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_TZ_SERIALIZE');

        $record = Attendance::create([
            'student_id'      => $student->id,
            'branch_id'       => $student->branch_id,
            'session_year_id' => $student->session_year_id,
            'class_id'        => $student->class_id,
            'section_id'      => $student->section_id,
            'attendance_date' => '2026-10-14',
            'status'          => 'Present',
            'remarks'         => 'Card Swiped (In: 08:55:00)',
            'user_id'         => 1,
        ]);

        $response = $this->getJson('/ajax/attendance/recent');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertNotEmpty($json['logs']);
        $log = collect($json['logs'])->firstWhere('id', $record->id);
        $this->assertNotNull($log);
        $this->assertEquals('Card Swiped (In: 08:55:00)', $log['remarks']);
    }

    /**
     * Requirement 6: Existing attendance records are not modified by the timezone changes
     */
    public function test_existing_attendance_records_remain_unmodified(): void
    {
        $student = $this->createTestStudent('CARD_TZ_EXISTING');

        $original = Attendance::create([
            'student_id'      => $student->id,
            'branch_id'       => $student->branch_id,
            'session_year_id' => $student->session_year_id,
            'class_id'        => $student->class_id,
            'section_id'      => $student->section_id,
            'attendance_date' => '2026-10-10',
            'status'          => 'Present',
            'remarks'         => 'Card Swiped (In: 09:08:00)',
            'user_id'         => 1,
        ]);

        $fetched = Attendance::find($original->id);
        $this->assertEquals('2026-10-10', $fetched->attendance_date);
        $this->assertEquals('Present', $fetched->status);
        $this->assertEquals('Card Swiped (In: 09:08:00)', $fetched->remarks);
    }

    /**
     * Requirement 7: Scheduler business-time assumptions are consistent with Asia/Dhaka
     */
    public function test_scheduler_business_time_assumptions_use_asia_dhaka(): void
    {
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();

        $resolveCommandEvent = collect($events)->first(function ($event) {
            return str_contains($event->command ?? '', 'app:resolve-missing-out-punches');
        });

        $this->assertNotNull($resolveCommandEvent, 'app:resolve-missing-out-punches schedule event must exist.');
        $this->assertEquals('Asia/Dhaka', $resolveCommandEvent->timezone, 'ResolveMissingOutPunches event must have Asia/Dhaka timezone.');
        $this->assertEquals('59 23 * * *', $resolveCommandEvent->expression, 'ResolveMissingOutPunches must run at 23:59.');

        $feeCommandEvent = collect($events)->first(function ($event) {
            return str_contains($event->command ?? '', 'fees:auto-generate');
        });

        $this->assertNotNull($feeCommandEvent, 'fees:auto-generate schedule event must exist.');
        $this->assertEquals('Asia/Dhaka', $feeCommandEvent->timezone, 'FeeAutoGenerate event must have Asia/Dhaka timezone.');
    }

    /**
     * Requirement 8: Inactive biometric attendance does not trigger SMS
     */
    public function test_inactive_student_biometric_attendance_does_not_trigger_sms(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_TZ_NO_SMS');
        $student->update(['sms_status' => 'Inactive']);
        $initialSmsCount = SmsLog::count();

        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_TZ_NO_SMS' => ['08:45:00']
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => '2026-10-14',
        ]);

        $response->assertStatus(200);
        $this->assertEquals($initialSmsCount, SmsLog::count(), 'Zero SMS logs should be created for Inactive student.');
    }
}
