<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\StaffAttendance;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Services\SmsService;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BiometricAttendancePhase1Test extends TestCase
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
    protected function createTestStudent(string $cardNumber = 'TESTCARD123'): Student
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@macsschool.com'],
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

        $teacherUser = User::firstOrCreate(
            ['email' => 'teacher@macsschool.com'],
            ['name' => 'Teacher User', 'password' => bcrypt('secret')]
        );

        Teacher::firstOrCreate(
            ['id' => 1],
            [
                'user_id'      => $teacherUser->id,
                'created_by'   => $user->id,
                'employee_id'  => 'TEA-001',
                'designation'  => 'Teacher',
                'phone'        => '01711111111',
                'address'      => 'School Address',
                'gender'       => 'Male',
                'joining_date' => '2020-01-01',
            ]
        );

        return Student::create([
            'user_id'                => $user->id,
            'student_identity'       => 'STD-' . rand(1000, 99999),
            'card_number'            => $cardNumber,
            'student_name'           => 'Test Student',
            'roll_number'            => '9999',
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
     * Test 1: Processing biometric punch for student with Inactive SMS status does not invoke SMS sending
     */
    public function test_biometric_student_sync_does_not_invoke_sms_when_inactive(): void
    {
        $this->withoutMiddleware();
        $student = $this->createTestStudent('CARD_STUDENT_01');
        $student->update(['sms_status' => 'Inactive']);
        $initialSmsCount = SmsLog::count();

        // Mock ZktecoService to return raw card swipe
        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('getRawLogsByCard')->willReturn([
            'CARD_STUDENT_01' => ['08:15:00', '16:00:00']
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => $today,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Assert attendance was updated/created
        $attendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today)
            ->first();

        $this->assertNotNull($attendance, 'Attendance record should be created/updated.');
        $this->assertStringContainsString('In: 08:15:00', $attendance->remarks);

        // Assert NO SMS was triggered for inactive student
        $this->assertEquals($initialSmsCount, SmsLog::count(), 'SmsLog table count must not increase for Inactive student.');
    }

    /**
     * Test 2: Processing a biometric staff punch does not invoke SMS sending
     */
    public function test_biometric_staff_sync_does_not_invoke_sms(): void
    {
        $this->withoutMiddleware();
        $initialSmsCount = SmsLog::count();
        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        // Mock ZktecoService for staff sync
        $zkMock = $this->createMock(ZktecoService::class);
        $zkMock->method('syncLogs')->willReturn(5);
        $zkMock->method('getConnectionStatus')->willReturn([
            'connected' => true,
            'status' => 'Connected',
            'message' => 'OK'
        ]);
        $this->app->instance(ZktecoService::class, $zkMock);

        $response = $this->postJson(route('staff-attendance.sync'), [
            'date' => $today,
        ]);

        $response->assertStatus(200);

        // Assert NO SMS was sent or logged
        $this->assertEquals($initialSmsCount, SmsLog::count(), 'SmsLog table count must not increase after staff biometric sync.');
    }

    /**
     * Test 3: SimulateSwipe command does not invoke SMS sending
     */
    public function test_simulate_swipe_does_not_invoke_sms(): void
    {
        $student = $this->createTestStudent('CARD_SIMULATE_99');
        $initialSmsCount = SmsLog::count();

        // 1. Simulate check-in
        $exitCode = Artisan::call('attendance:simulate-swipe', [
            'card_number' => 'CARD_SIMULATE_99'
        ]);

        $this->assertEquals(0, $exitCode);
        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        $attendance = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertStringContainsString('Card Swiped (In:', $attendance->remarks);

        // 2. Simulate check-out
        $exitCode2 = Artisan::call('attendance:simulate-swipe', [
            'card_number' => 'CARD_SIMULATE_99'
        ]);
        $this->assertEquals(0, $exitCode2);

        $attendance->refresh();
        $this->assertStringContainsString('Out:', $attendance->remarks);

        // Assert zero SMS logged
        $this->assertEquals($initialSmsCount, SmsLog::count(), 'SimulateSwipe must never trigger SMS.');
    }

    /**
     * Test 4: Manual attendance endpoints and shared SmsService remain functional
     */
    public function test_manual_attendance_and_shared_sms_service_remain_available(): void
    {
        $this->withoutMiddleware();

        // Manual attendance recent logs endpoint
        $response = $this->getJson('/ajax/attendance/recent');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Manual attendance device status endpoint
        $statusResponse = $this->getJson('/ajax/attendance/device-status');
        $statusResponse->assertStatus(200);
        $statusResponse->assertJson(['status' => 'success']);

        // Unrelated shared SmsService remains directly callable
        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['StatusCode' => '200', 'StatusName' => 'Successful'], 200),
        ]);

        Config::set('services.mim_sms.test_mode', false);
        Config::set('services.mim_sms.username', 'test_user');
        Config::set('services.mim_sms.api_key', 'test_key');
        Config::set('services.mim_sms.sender_id', 'TEST_SENDER');

        $smsService = new SmsService();
        $sent = $smsService->sendSms('01712345678', 'General school notification test');
        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api/V2/SMS')
                && $request['mobileNumber'] === '8801712345678';
        });
    }

    /**
     * Test 5: Production safety guard: missing or simulation mode does not create synthetic attendance in production
     */
    public function test_production_environment_blocks_synthetic_attendance(): void
    {
        // Force application to report as production
        $this->app->detectEnvironment(fn () => 'production');
        $this->assertTrue($this->app->isProduction());

        // 1. When mode is missing, ZktecoService defaults to 'live' and does not simulate
        Config::set('services.zkteco.mode', null);
        $service = new ZktecoService();
        $this->assertEquals('live', $service->getMode(), 'In production, unset mode must default to live.');

        // 2. Even if someone explicitly sets mode to simulation in production, it refuses to generate synthetic logs
        Config::set('services.zkteco.mode', 'simulation');
        $simulationService = new ZktecoService();
        $this->assertEquals('simulation', $simulationService->getMode());

        $rawLogs = $simulationService->getRawLogsByCard();
        $this->assertEmpty($rawLogs, 'Raw logs must be empty in production even if simulation mode is specified.');

        $synced = $simulationService->syncLogs();
        $this->assertEquals(0, $synced, 'syncLogs must return 0 in production when simulation is requested.');

        // 3. MonitorAttendance command must exit with error 1 if mode is simulation in production
        $exitCode = Artisan::call('attendance:monitor');
        $this->assertEquals(1, $exitCode, 'MonitorAttendance must terminate with error 1 in production simulation mode.');
    }

    /**
     * Test 6: Attendance duplicate prevention works correctly
     */
    public function test_attendance_duplicate_prevention_works(): void
    {
        $student = $this->createTestStudent('CARD_DUP_TEST');
        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        // First punch
        Artisan::call('attendance:simulate-swipe', ['card_number' => 'CARD_DUP_TEST']);
        $countAfter1 = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today)
            ->count();
        $this->assertEquals(1, $countAfter1);

        // Duplicate punch on same day
        Artisan::call('attendance:simulate-swipe', ['card_number' => 'CARD_DUP_TEST']);
        $countAfter2 = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today)
            ->count();
        $this->assertEquals(1, $countAfter2, 'Duplicate swipe on same day must not create a duplicate row.');
    }
}
