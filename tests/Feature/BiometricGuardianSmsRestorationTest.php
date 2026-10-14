<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\Shift;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Services\AttendanceSmsNotifier;
use App\Services\SmsService;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BiometricGuardianSmsRestorationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        // Enable test mode with clean whitelisted mock recipient
        Config::set('services.mim_sms.test_mode', false);
        Config::set('services.mim_sms.username', 'mock_user');
        Config::set('services.mim_sms.api_key', 'mock_key');
        Config::set('services.mim_sms.sender_id', 'MACS_TEST');
        Config::set('services.zkteco.mode', 'live');
    }

    protected function createTestStudent(string $cardNumber = 'TESTCARD_SMS', string $smsStatus = 'Active', string $mobile = '01712345678'): Student
    {
        $user = User::firstOrCreate(
            ['email' => 'admin_test@macsschool.com'],
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

        $shift = Shift::firstOrCreate(
            ['name' => 'Morning Student'],
            [
                'type' => 'student',
                'in_time_start' => '09:00:00',
                'in_time_end' => '09:30:00',
                'late_time_end' => '09:40:00',
                'absent_time' => '09:41:00',
                'out_time' => '11:50:00',
                'shift_name' => 'Morning Student',
                'user_id' => $user->id
            ]
        );

        return Student::create([
            'user_id'                => $user->id,
            'student_identity'       => 'STD-' . rand(1000, 99999),
            'card_number'            => $cardNumber,
            'student_name'           => 'Rahim Uddin',
            'roll_number'            => '101',
            'dob'                    => '2015-01-01',
            'gender'                 => 'Male',
            'father_name'            => 'Karim Uddin',
            'father_mobile'          => $mobile,
            'mother_name'            => 'Fatema Begum',
            'mother_mobile'          => $mobile,
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
            'shift_id'               => $shift->id,
            'guardian_mobile'        => $mobile,
            'sms_status'             => $smsStatus,
            'status'                 => 'Active',
        ]);
    }

    /**
     * Requirement 1: A valid real check-in triggers exactly one guardian SMS.
     */
    public function test_valid_real_check_in_triggers_exactly_one_guardian_sms(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_CHECKIN_01');

        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200', 'StatusName' => 'Successful'], 200),
        ]);

        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_CHECKIN_01' => ['08:45:00']
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $today,
        ]);

        $response->assertStatus(200);

        // Assert exactly 1 SMS was dispatched
        $this->assertEquals(1, SmsLog::count());
        $log = SmsLog::first();
        $this->assertEquals($student->id, $log->student_id);
        $this->assertEquals("att:check_in:{$student->id}:{$today}", $log->event_key);
        $this->assertEquals('check_in', $log->event_type);
        $this->assertEquals('Sent', $log->status);
        $this->assertStringContainsString('entered the school at 08:45 AM', $log->message);

        Http::assertSentCount(1);
    }

    /**
     * Requirement 2: A genuine check-out triggers exactly one check-out SMS.
     */
    public function test_genuine_check_out_triggers_exactly_one_check_out_sms(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_CHECKOUT_01');

        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200', 'StatusName' => 'Successful'], 200),
        ]);

        // Student entered at 08:30:00 and left at 11:45:00 (>30 mins & after late_time_end 09:40:00)
        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_CHECKOUT_01' => ['08:30:00', '11:45:00']
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $today,
        ]);

        $response->assertStatus(200);

        // Assert exactly 2 SMS were dispatched: 1 Check-In and 1 Check-Out
        $this->assertEquals(2, SmsLog::count());

        $inLog = SmsLog::where('event_type', 'check_in')->first();
        $this->assertNotNull($inLog);
        $this->assertEquals("att:check_in:{$student->id}:{$today}", $inLog->event_key);
        $this->assertStringContainsString('entered the school at 08:30 AM', $inLog->message);

        $outLog = SmsLog::where('event_type', 'check_out')->first();
        $this->assertNotNull($outLog);
        $this->assertEquals("att:check_out:{$student->id}:{$today}", $outLog->event_key);
        $this->assertStringContainsString('left the school at 11:45 AM', $outLog->message);

        Http::assertSentCount(2);
    }

    /**
     * Requirement 3: A rapid double-tap does not generate a false check-out SMS.
     */
    public function test_rapid_double_tap_does_not_generate_false_check_out_sms(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_DOUBLE_TAP');

        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200', 'StatusName' => 'Successful'], 200),
        ]);

        // Student tapped card twice in 8 seconds (08:30:00 and 08:30:08)
        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_DOUBLE_TAP' => ['08:30:00', '08:30:08']
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $today,
        ]);

        $response->assertStatus(200);

        // Assert ONLY Check-In was sent; NO Check-Out SMS
        $this->assertEquals(1, SmsLog::count());
        $this->assertEquals('check_in', SmsLog::first()->event_type);
        $this->assertEquals(0, SmsLog::where('event_type', 'check_out')->count());

        Http::assertSentCount(1);
    }

    /**
     * Requirement 4: Repeated polling does not resend an existing event's SMS.
     */
    public function test_repeated_polling_does_not_resend_existing_event_sms(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_REPEAT_POLL');

        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200', 'StatusName' => 'Successful'], 200),
        ]);

        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_REPEAT_POLL' => ['08:35:00']
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        // First poll iteration
        $this->postJson('/ajax/attendance/sync-biometric', ['attendance_date' => $today]);
        $this->assertEquals(1, SmsLog::count());
        Http::assertSentCount(1);

        // Second poll iteration 3 seconds later (device still has the 08:35:00 punch)
        $this->postJson('/ajax/attendance/sync-biometric', ['attendance_date' => $today]);
        $this->assertEquals(1, SmsLog::count(), 'Repeated poll must not create duplicate SMS log.');
        Http::assertSentCount(1);

        // Third poll iteration
        $this->postJson('/ajax/attendance/sync-biometric', ['attendance_date' => $today]);
        $this->assertEquals(1, SmsLog::count());
        Http::assertSentCount(1);
    }

    /**
     * Requirement 5: Concurrent monitor/manual-sync processing does not create duplicate notification events.
     */
    public function test_concurrent_processing_collision_is_prevented_by_unique_constraint(): void
    {
        $student = $this->createTestStudent('CARD_CONCURRENT');
        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');
        $eventKey = "att:check_in:{$student->id}:{$today}";

        // Thread 1 atomically reserves the event_key in database
        SmsLog::create([
            'student_id'    => $student->id,
            'event_key'     => $eventKey,
            'event_type'    => 'check_in',
            'mobile_number' => $student->guardian_mobile,
            'message'       => 'Check in message',
            'status'        => 'Sent',
        ]);

        // Thread 2 attempts to send SMS with the identical eventKey
        $smsService = app(SmsService::class);
        $result = $smsService->sendSms(
            $student->guardian_mobile,
            'Duplicate check in attempt',
            $student->id,
            $eventKey,
            'check_in'
        );

        // Must reject cleanly and return false
        $this->assertFalse($result, 'Duplicate event key must be rejected by atomic constraint.');
        $this->assertEquals(1, SmsLog::where('event_key', $eventKey)->count());
    }

    /**
     * Requirement 6: A failed attendance persistence operation never triggers SMS.
     */
    public function test_failed_attendance_persistence_never_triggers_sms(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_FAIL_PERSIST');

        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200'], 200),
        ]);

        // Pass invalid attendance_date to cause validation/persistence failure
        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => 'invalid-date-format',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, SmsLog::count(), 'Persistence failure must never invoke SMS.');
        Http::assertNothingSent();
    }

    /**
     * Requirement 7: Simulated punches never trigger guardian SMS.
     */
    public function test_simulated_punches_never_trigger_guardian_sms(): void
    {
        $student = $this->createTestStudent('CARD_SIMULATED_SAFE');
        $initialSmsCount = SmsLog::count();

        // Run CLI SimulateSwipe command
        $exitCode = Artisan::call('attendance:simulate-swipe', [
            'card_number' => 'CARD_SIMULATED_SAFE'
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertEquals($initialSmsCount, SmsLog::count(), 'SimulateSwipe must never trigger SMS.');

        // Test notifier directly with non-live source
        $notifier = app(AttendanceSmsNotifier::class);
        $attendance = Attendance::firstOrCreate([
            'student_id' => $student->id,
            'attendance_date' => Carbon::today('Asia/Dhaka')->format('Y-m-d')
        ], [
            'status' => 'Present',
            'remarks' => 'Simulated',
            'user_id' => 1
        ]);

        $results = $notifier->notifyPunch($student, $attendance, ['09:00:00'], 'simulation');
        $this->assertFalse($results['check_in']);
        $this->assertFalse($results['check_out']);
        $this->assertEquals($initialSmsCount, SmsLog::count());
    }

    /**
     * Requirement 8: Unknown/unmapped cards and inactive or invalid guardian numbers are handled safely.
     */
    public function test_unknown_cards_and_inactive_guardians_handled_safely(): void
    {
        $this->withoutMiddleware();

        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200'], 200),
        ]);

        // Student with inactive SMS
        $inactiveStudent = $this->createTestStudent('CARD_INACTIVE', 'Inactive');

        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_INACTIVE' => ['08:40:00'],
            'CARD_UNKNOWN_999' => ['08:42:00'] // Unmapped card
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');
        $response = $this->postJson('/ajax/attendance/sync-biometric', ['attendance_date' => $today]);

        $response->assertStatus(200);
        $this->assertEquals(0, SmsLog::count(), 'Inactive SMS or unmapped cards must not trigger SMS.');
        Http::assertNothingSent();
    }

    /**
     * Requirement 9: Provider failure states are recorded without unsafe duplicate retries.
     */
    public function test_provider_failure_is_recorded_safely_without_unsafe_retries(): void
    {
        $student = $this->createTestStudent('CARD_PROVIDER_FAIL');
        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');
        $eventKey = "att:check_in:{$student->id}:{$today}";

        // Simulate provider 500 error
        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '500', 'StatusName' => 'Gateway Error'], 500),
        ]);

        $smsService = app(SmsService::class);
        $sent = $smsService->sendSms(
            $student->guardian_mobile,
            'Check in message',
            $student->id,
            $eventKey,
            'check_in'
        );

        $this->assertFalse($sent);

        // Record exists with status 'Failed'
        $log = SmsLog::where('event_key', $eventKey)->first();
        $this->assertNotNull($log);
        $this->assertEquals('Failed', $log->status);

        // Subsequent poll attempts to send the same event
        $secondAttempt = $smsService->sendSms(
            $student->guardian_mobile,
            'Check in message',
            $student->id,
            $eventKey,
            'check_in'
        );

        $this->assertFalse($secondAttempt, 'Failed event key must not be re-fired without explicit resolution.');
        $this->assertEquals(1, SmsLog::where('event_key', $eventKey)->count());
        Http::assertSentCount(1);
    }

    /**
     * Requirement 10: Biometric sync never sends bulk absence SMS.
     */
    public function test_biometric_sync_never_sends_bulk_absence_sms(): void
    {
        $this->withoutMiddleware();

        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200'], 200),
        ]);

        // 3 active students
        $student1 = $this->createTestStudent('CARD_PRESENT_1');
        $student2 = $this->createTestStudent('CARD_ABSENT_2');
        $student3 = $this->createTestStudent('CARD_ABSENT_3');

        // Only student 1 swiped their card
        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_PRESENT_1' => ['08:45:00']
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');
        $response = $this->postJson('/ajax/attendance/sync-biometric', ['attendance_date' => $today]);

        $response->assertStatus(200);

        // Exactly 1 SMS sent (for student1). ZERO SMS for student2 and student3!
        $this->assertEquals(1, SmsLog::count());
        $this->assertEquals($student1->id, SmsLog::first()->student_id);

        // Verify student2 and student3 were marked Absent in database without any SMS
        $att2 = Attendance::where('student_id', $student2->id)->where('attendance_date', $today)->first();
        $this->assertEquals('Absent', $att2->status);

        $att3 = Attendance::where('student_id', $student3->id)->where('attendance_date', $today)->first();
        $this->assertEquals('Absent', $att3->status);

        Http::assertSentCount(1);
    }
}
