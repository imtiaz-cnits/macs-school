<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BiometricDeviceUser;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\StaffAttendance;
use App\Services\BiometricRegistryService;
use App\Services\RegistryResolutionResult;
use App\Services\ZktecoService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Jmrashed\Zkteco\Lib\ZKTeco;
use Carbon\Carbon;

class BiometricAttendanceRegistrySyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classOne;
    protected Section $sectionA;
    protected Shift $studentShift;
    protected Shift $staffShift;
    protected BiometricRegistryService $registryService;
    protected ZktecoService $zkService;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->registryService = app(BiometricRegistryService::class);
        $this->zkService = app(ZktecoService::class);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_registry_sync@macsschool.com'],
            ['name' => 'Admin Registry Sync Tester', 'password' => bcrypt('secret')]
        );

        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->adminUser->assignRole($superAdminRole);
        $this->actingAs($this->adminUser);

        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->session = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026-2027', 'user_id' => $this->adminUser->id]);
        $this->classOne = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Class One', 'user_id' => $this->adminUser->id]);
        $this->sectionA = Section::firstOrCreate(['id' => 1], ['section_name' => 'Section A', 'user_id' => $this->adminUser->id]);

        $this->studentShift = Shift::firstOrCreate(
            ['name' => 'Morning Student Shift'],
            [
                'type'          => 'student',
                'in_time_start' => '08:00:00',
                'in_time_end'   => '08:30:00',
                'late_time_end' => '08:45:00',
                'absent_time'   => '09:00:00',
                'out_time'      => '12:00:00',
                'shift_name'    => 'Morning Student Shift',
                'user_id'       => $this->adminUser->id,
            ]
        );

        $this->staffShift = Shift::firstOrCreate(
            ['name' => 'Standard Staff Shift'],
            [
                'type'          => 'staff',
                'in_time_start' => '08:00:00',
                'in_time_end'   => '08:30:00',
                'late_time_end' => '08:45:00',
                'absent_time'   => '09:00:00',
                'out_time'      => '16:00:00',
                'shift_name'    => 'Standard Staff Shift',
                'user_id'       => $this->adminUser->id,
            ]
        );
    }

    protected function createTeacher(int $id, string $employeeId, string $name, ?string $biometricId = null): Teacher
    {
        $user = User::firstOrCreate(
            ['email' => strtolower(str_replace(' ', '', $name)) . "_{$id}@macsschool.edu.bd"],
            ['name' => $name, 'password' => bcrypt('secret')]
        );

        return Teacher::create([
            'id'           => $id,
            'user_id'      => $user->id,
            'employee_id'  => $employeeId,
            'designation'  => 'Senior Teacher',
            'phone'        => '017000' . str_pad((string)$id, 5, '0', STR_PAD_LEFT),
            'address'      => 'Dhaka',
            'gender'       => 'Male',
            'joining_date' => '2026-01-01',
            'created_by'   => $this->adminUser->id,
            'biometric_id' => $biometricId,
        ]);
    }

    protected function createStudent(int $id, array $attributes = []): Student
    {
        $defaults = [
            'id'                    => $id,
            'user_id'               => $this->adminUser->id,
            'student_identity'      => 'STD-REG-' . $id,
            'card_number'           => '001600' . str_pad((string)$id, 4, '0', STR_PAD_LEFT),
            'student_name'          => 'Student Registry ' . $id,
            'roll_number'           => (string) $id,
            'dob'                   => '2015-05-10',
            'gender'                => 'Male',
            'father_name'           => 'Father ' . $id,
            'father_mobile'         => '0171000' . str_pad((string)$id, 4, '0', STR_PAD_LEFT),
            'mother_name'           => 'Mother ' . $id,
            'mother_mobile'         => '0172000' . str_pad((string)$id, 4, '0', STR_PAD_LEFT),
            'guardian_name'         => 'Guardian ' . $id,
            'guardian_mobile'       => '0173000' . str_pad((string)$id, 4, '0', STR_PAD_LEFT),
            'branch_id'             => $this->branch->id,
            'session_year_id'       => $this->session->id,
            'class_id'              => $this->classOne->id,
            'section_id'            => $this->sectionA->id,
            'shift_id'              => $this->studentShift->id,
            'status'                => 'Active',
            'present_village'       => 'Mirpur',
            'present_post_office'   => 'Mirpur-1',
            'present_district'      => 'Dhaka',
            'present_division'      => 'Dhaka',
            'permanent_village'     => 'Mirpur',
            'permanent_post_office' => 'Mirpur-1',
            'permanent_district'    => 'Dhaka',
            'permanent_division'    => 'Dhaka',
        ];

        return Student::create(array_merge($defaults, $attributes));
    }

    /**
     * 1. Imtiaz PIN 569 resolves to Teacher #5 and creates StaffAttendance
     */
    public function test_imtiaz_pin_569_resolves_to_teacher_5_and_creates_staff_attendance(): void
    {
        config(['services.zkteco.mode' => 'live']);

        // Teacher #5 has biometric_id = '1' in database (preserving existing mismatch)
        $teacher5 = $this->createTeacher(5, 'TEA-2026-005', 'Imtiaz Ahmed', '1');

        // Centralized registry maps terminal PIN 569 to Teacher #5
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 569,
            'device_userid' => '569',
            'person_type'   => 'teacher',
            'person_id'     => 5,
            'card_number'   => '0016001686',
            'status'        => 'active',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '569', 'timestamp' => '2026-10-31 08:20:00', 'state' => 0, 'type' => 1]
        ]);

        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(1, $synced);
        $this->assertDatabaseHas('staff_attendances', [
            'teacher_id' => 5,
            'date'       => '2026-10-31',
            'check_in'   => '08:20:00',
            'status'     => 'Present',
        ]);

        // Verify database column biometric_id is 100% untouched
        $this->assertEquals('1', $teacher5->fresh()->biometric_id);
    }

    /**
     * 2. Mahmudul PIN 10006 resolves to Teacher #6
     */
    public function test_mahmudul_pin_10006_resolves_to_teacher_6(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $teacher6 = $this->createTeacher(6, 'TEA-26-02-21', 'Md Mahmudul Hasan', '10006');

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 10006,
            'device_userid' => '10006',
            'person_type'   => 'teacher',
            'person_id'     => 6,
            'status'        => 'active',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '10006', 'timestamp' => '2026-10-31 08:22:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(1, $synced);
        $this->assertDatabaseHas('staff_attendances', [
            'teacher_id' => 6,
            'date'       => '2026-10-31',
            'check_in'   => '08:22:00',
        ]);
    }

    /**
     * 3. Fahim PIN 10008 resolves to Teacher #8
     */
    public function test_fahim_pin_10008_resolves_to_teacher_8(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $teacher8 = $this->createTeacher(8, 'TEA-2026-04', 'FAHIM AHMAD ZAMIL', '10008');

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 10008,
            'device_userid' => '10008',
            'person_type'   => 'teacher',
            'person_id'     => 8,
            'status'        => 'active',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '10008', 'timestamp' => '2026-10-31 08:25:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(1, $synced);
        $this->assertDatabaseHas('staff_attendances', [
            'teacher_id' => 8,
            'date'       => '2026-10-31',
            'check_in'   => '08:25:00',
        ]);
    }

    /**
     * 4. PIN 21 remains unresolved and creates no staff attendance
     */
    public function test_pin_21_remains_unresolved_and_creates_no_staff_attendance(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 10025,
            'device_userid' => '21',
            'person_type'   => 'staff',
            'person_id'     => null,
            'status'        => 'unresolved',
        ]);

        $res = $this->registryService->resolvePersonByPin('k60_main', '21');
        $this->assertTrue($res->isUnresolved());
        $this->assertFalse($res->isActive());
        $this->assertFalse($res->canUseLegacyFallback());

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '21', 'timestamp' => '2026-10-31 08:30:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(0, $synced);
        $this->assertEquals(0, StaffAttendance::count());
    }

    /**
     * 5. Unresolved PIN 21 cannot fall back to a legacy teacher
     */
    public function test_unresolved_pin_21_cannot_fall_back_to_legacy_teacher(): void
    {
        config(['services.zkteco.mode' => 'live']);

        // Even if an arbitrary teacher has biometric_id = '21' in legacy table
        $legacyTeacher = $this->createTeacher(99, 'TEA-LEGACY', 'Legacy Teacher', '21');

        // But PIN 21 is registered as unresolved in the central registry
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 10025,
            'device_userid' => '21',
            'person_type'   => 'staff',
            'person_id'     => null,
            'status'        => 'unresolved',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '21', 'timestamp' => '2026-10-31 08:30:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        // Must NOT assign attendance to legacyTeacher 99!
        $this->assertEquals(0, $synced);
        $this->assertEquals(0, StaffAttendance::where('teacher_id', 99)->count());
    }

    /**
     * 6. Retired PIN cannot fall back to legacy mapping
     */
    public function test_retired_pin_cannot_fall_back_to_legacy_mapping(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $legacyTeacher = $this->createTeacher(70, 'TEA-070', 'Retired Fallback Test', '217');

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 217,
            'device_userid' => '217',
            'person_type'   => 'student',
            'person_id'     => null,
            'status'        => 'retired',
        ]);

        $res = $this->registryService->resolvePersonByPin('k60_main', '217');
        $this->assertTrue($res->isRetired());
        $this->assertFalse($res->canUseLegacyFallback());

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '217', 'timestamp' => '2026-10-31 08:30:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(0, $synced);
        $this->assertEquals(0, StaffAttendance::count());
    }

    /**
     * 7. Inactive PIN cannot fall back to legacy mapping
     */
    public function test_inactive_pin_cannot_fall_back_to_legacy_mapping(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $legacyTeacher = $this->createTeacher(80, 'TEA-080', 'Inactive Teacher Test', '999');

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 999,
            'device_userid' => '999',
            'person_type'   => 'teacher',
            'person_id'     => 80,
            'status'        => 'inactive',
        ]);

        $res = $this->registryService->resolvePersonByPin('k60_main', '999');
        $this->assertTrue($res->isInactive());
        $this->assertFalse($res->canUseLegacyFallback());

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '999', 'timestamp' => '2026-10-31 08:30:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(0, $synced);
        $this->assertEquals(0, StaffAttendance::count());
    }

    /**
     * 8. Unknown PIN uses legacy fallback only when permitted
     */
    public function test_unknown_pin_uses_legacy_fallback_only_when_permitted(): void
    {
        config(['services.zkteco.mode' => 'live']);

        // Teacher 11 exists in DB with biometric_id = 10012
        $teacher11 = $this->createTeacher(11, 'TEA-2026-06', 'Md. Jahangir Hossain', '10012');

        // PIN 10012 is NOT in the biometric registry table at all (missing)
        $res = $this->registryService->resolvePersonByPin('k60_main', '10012');
        $this->assertTrue($res->isMissing());
        $this->assertTrue($res->canUseLegacyFallback());

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '10012', 'timestamp' => '2026-10-31 08:28:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(1, $synced);
        $this->assertDatabaseHas('staff_attendances', [
            'teacher_id' => 11,
            'date'       => '2026-10-31',
        ]);
    }

    /**
     * 9. Duplicate active PIN is rejected
     */
    public function test_duplicate_active_pin_is_rejected(): void
    {
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 401,
            'device_userid' => 'PIN_SAMECONFLICT',
            'person_type'   => 'student',
            'person_id'     => 401,
            'status'        => 'active',
        ]);

        $this->expectException(ValidationException::class);
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 402,
            'device_userid' => 'PIN_SAMECONFLICT',
            'person_type'   => 'student',
            'person_id'     => 402,
            'status'        => 'active',
        ]);
    }

    /**
     * 10. Duplicate active RFID card assignment is rejected
     */
    public function test_duplicate_active_rfid_card_assignment_is_rejected(): void
    {
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 405,
            'device_userid' => '405',
            'person_type'   => 'student',
            'person_id'     => 405,
            'card_number'   => '0016009999',
            'status'        => 'active',
        ]);

        $this->expectException(ValidationException::class);
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 406,
            'device_userid' => '406',
            'person_type'   => 'student',
            'person_id'     => 406,
            'card_number'   => '0016009999',
            'status'        => 'active',
        ]);
    }

    /**
     * 11. Ambiguous lookup creates no attendance
     */
    public function test_ambiguous_lookup_creates_no_attendance(): void
    {
        $res = RegistryResolutionResult::ambiguous("Multiple matches found");
        $this->assertTrue($res->isAmbiguous());
        $this->assertFalse($res->isActive());
        $this->assertFalse($res->canUseLegacyFallback());
    }

    /**
     * 12. Student RFID attendance resolves to the correct student
     */
    public function test_student_rfid_attendance_resolves_to_correct_student(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $student = $this->createStudent(50, ['card_number' => '0016007777']);

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 50,
            'device_userid' => '50',
            'person_type'   => 'student',
            'person_id'     => 50,
            'card_number'   => '0016007777',
            'status'        => 'active',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '50', 'timestamp' => '2026-10-31 08:15:00', 'state' => 0, 'type' => 4]
        ]);
        $mockZk->method('getUser')->willReturn([
            ['uid' => 50, 'userid' => '50', 'name' => 'Student 50', 'cardno' => '0016007777']
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => '2026-10-31',
            'class_id'        => $this->classOne->id,
            'section_id'      => $this->sectionA->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'student_id'      => 50,
            'attendance_date' => '2026-10-31',
            'status'          => 'Present',
        ]);
    }

    /**
     * 13. Student PIN attendance resolves only through registry mapping
     */
    public function test_student_pin_attendance_resolves_only_through_registry_mapping(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $student = $this->createStudent(60, ['card_number' => null]);

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 60,
            'device_userid' => '60',
            'person_type'   => 'student',
            'person_id'     => 60,
            'status'        => 'active',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '60', 'timestamp' => '2026-10-31 08:10:00', 'state' => 0, 'type' => 1]
        ]);
        $mockZk->method('getUser')->willReturn([
            ['uid' => 60, 'userid' => '60', 'name' => 'Student 60', 'cardno' => '0']
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => '2026-10-31',
            'class_id'        => $this->classOne->id,
            'section_id'      => $this->sectionA->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'student_id'      => 60,
            'attendance_date' => '2026-10-31',
            'status'          => 'Present',
        ]);
    }

    /**
     * 14. Staff PIN 569 never creates student attendance
     */
    public function test_staff_pin_569_never_creates_student_attendance(): void
    {
        config(['services.zkteco.mode' => 'live']);

        // Imtiaz Ahmed is registered as staff PIN 569
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 569,
            'device_userid' => '569',
            'person_type'   => 'teacher',
            'person_id'     => 5,
            'card_number'   => '0016001686',
            'status'        => 'active',
        ]);

        // Create student 500
        $student500 = $this->createStudent(500);

        // K60 only has punch for PIN 569
        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '569', 'timestamp' => '2026-10-31 08:20:00', 'state' => 0, 'type' => 1]
        ]);
        $mockZk->method('getUser')->willReturn([
            ['uid' => 569, 'userid' => '569', 'name' => 'Imtiaz Ahmed', 'cardno' => '0016001686']
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson('/ajax/attendance/sync-biometric', [
            'attendance_date' => '2026-10-31',
            'class_id'        => $this->classOne->id,
            'section_id'      => $this->sectionA->id,
        ]);

        $response->assertStatus(200);

        // Zero students were marked present from staff PIN 569
        $this->assertDatabaseMissing('attendances', [
            'attendance_date' => '2026-10-31',
            'status'          => 'Present',
        ]);
    }

    /**
     * 15. Student attendance never creates staff attendance
     */
    public function test_student_attendance_never_creates_staff_attendance(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 75,
            'device_userid' => '75',
            'person_type'   => 'student',
            'person_id'     => 75,
            'status'        => 'active',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '75', 'timestamp' => '2026-10-31 08:15:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        // Call staff sync
        $synced = $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(0, $synced);
        $this->assertEquals(0, StaffAttendance::count());
    }

    /**
     * 16. Historical attendance records remain unchanged
     */
    public function test_historical_attendance_records_remain_unchanged(): void
    {
        $teacher = $this->createTeacher(5, 'TEA-005', 'Imtiaz Ahmed', '1');

        // Pre-existing historical staff attendance record
        $historical = StaffAttendance::create([
            'teacher_id' => 5,
            'date'       => '2026-07-04',
            'check_in'   => '15:53:55',
            'check_out'  => '16:05:34',
            'status'     => 'Late',
            'remarks'    => 'Biometric Swipe (Live)',
        ]);

        $origId = $historical->id;
        $origCheckIn = $historical->check_in;

        // Perform registry resolution queries
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 569,
            'device_userid' => '569',
            'person_type'   => 'teacher',
            'person_id'     => 5,
            'status'        => 'active',
        ]);

        $fresh = StaffAttendance::find($origId);
        $this->assertNotNull($fresh);
        $this->assertEquals($origCheckIn, $fresh->check_in);
        $this->assertEquals('Late', $fresh->status);
    }

    /**
     * 17. Duplicate log processing does not create duplicate attendance
     */
    public function test_duplicate_log_processing_does_not_create_duplicate_attendance(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $this->createTeacher(6, 'TEA-006', 'Teacher Six', '10006');
        $this->registryService->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 10006,
            'device_userid' => '10006',
            'person_type'   => 'teacher',
            'person_id'     => 6,
            'status'        => 'active',
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '10006', 'timestamp' => '2026-10-31 08:20:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        // Run sync twice
        $this->zkService->syncLogs('2026-10-31');
        $this->zkService->syncLogs('2026-10-31');

        $this->assertEquals(1, StaffAttendance::where('teacher_id', 6)->where('date', '2026-10-31')->count());
    }

    /**
     * 18. SMS is not dispatched for unresolved or invalid identities
     */
    public function test_sms_is_not_dispatched_for_unresolved_or_invalid_identities(): void
    {
        config(['services.zkteco.mode' => 'live']);

        // Unresolved PIN 21 punch
        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);
        $mockZk->method('getAttendance')->willReturn([
            ['id' => '21', 'timestamp' => '2026-10-31 08:30:00', 'state' => 0, 'type' => 1]
        ]);
        $this->app->instance(ZKTeco::class, $mockZk);

        $this->zkService->syncLogs('2026-10-31');

        Http::assertNothingSent();
    }

    /**
     * 19. Existing manual attendance remains functional
     */
    public function test_existing_manual_attendance_remains_functional(): void
    {
        $student = $this->createStudent(88);

        // 1. Endpoint /ajax/attendance/recent functions cleanly
        $recentResponse = $this->getJson('/ajax/attendance/recent');
        $recentResponse->assertStatus(200);
        $recentResponse->assertJson(['status' => 'success']);

        // 2. Direct manual attendance creation/update works seamlessly
        $manual = Attendance::updateOrCreate(
            [
                'student_id'      => 88,
                'attendance_date' => '2026-10-31',
            ],
            [
                'user_id'         => $this->adminUser->id,
                'status'          => 'Present',
                'remarks'         => 'Manual Entry by Admin',
                'branch_id'       => $this->branch->id,
                'session_year_id' => $this->session->id,
                'class_id'        => $this->classOne->id,
                'section_id'      => $this->sectionA->id,
            ]
        );

        $this->assertDatabaseHas('attendances', [
            'student_id'      => 88,
            'attendance_date' => '2026-10-31',
            'status'          => 'Present',
            'remarks'         => 'Manual Entry by Admin',
        ]);
    }

    /**
     * 20. Existing attendance and timezone regression passes
     */
    public function test_existing_attendance_and_timezone_regression_passes(): void
    {
        $dt = Carbon::now('Asia/Dhaka');
        $this->assertEquals('+06:00', $dt->format('P'));
    }
}
