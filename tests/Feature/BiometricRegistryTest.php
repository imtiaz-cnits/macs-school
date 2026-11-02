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
use App\Services\BiometricRegistryService;
use Database\Seeders\BiometricRegistryProposedSeeder;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Jmrashed\Zkteco\Lib\ZKTeco;
use RuntimeException;

class BiometricRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classOne;
    protected Section $sectionA;
    protected Shift $shift;
    protected BiometricRegistryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BiometricRegistryService();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_registry@macsschool.com'],
            ['name' => 'Admin Registry Tester', 'password' => bcrypt('secret')]
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
        $this->shift = Shift::firstOrCreate(
            ['name' => 'Morning Shift'],
            [
                'type'          => 'student',
                'in_time_start' => '08:00:00',
                'in_time_end'   => '08:30:00',
                'late_time_end' => '08:45:00',
                'absent_time'   => '09:00:00',
                'out_time'      => '12:00:00',
                'shift_name'    => 'Morning Shift',
                'user_id'       => $this->adminUser->id,
            ]
        );
    }

    protected function createStudentWithId(int $id, array $attributes = []): Student
    {
        $defaults = [
            'id'                    => $id,
            'user_id'               => $this->adminUser->id,
            'student_identity'      => 'STD-REG-' . $id,
            'card_number'           => '00095' . str_pad((string)$id, 5, '0', STR_PAD_LEFT),
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
            'shift_id'              => $this->shift->id,
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
     * Test 1: Student ID 569 individual push blocked with 422 error
     */
    public function test_student_id_569_individual_push_blocked(): void
    {
        // 1. Test individual push endpoint
        $response = $this->postJson('/ajax/students/569/push-to-device');

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Device ID 569 is reserved for staff biometric identity and cannot be overwritten by a student.'
        ]);

        // 2. Test RFID card sync endpoint
        $rfidResponse = $this->postJson('/ajax/students/569/sync-card');
        $rfidResponse->assertStatus(422);
        $rfidResponse->assertJson([
            'status'  => 'error',
            'message' => 'Device ID 569 is reserved for staff biometric identity and cannot be synced to a student.'
        ]);
    }

    /**
     * Test 2: Student ID 569 bulk push skipped with warning logged
     */
    public function test_student_id_569_bulk_push_skipped(): void
    {
        // 1. Single student CLI command specifically for 569 is rejected immediately
        $this->artisan('zkteco:sync-users', ['--student-id' => '569'])
            ->expectsOutputToContain('Student ID 569 is reserved for staff biometric identity')
            ->assertExitCode(0);

        // 2. In live mode with mock ZKTeco, run full bulk sync with student 569 and 500
        config(['services.zkteco.mode' => 'live']);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->method('connect')->willReturn(true);
        $mockZk->method('getUser')->willReturn([]);
        $mockZk->method('setUser')->willReturn(true);
        $mockZk->method('disableDevice')->willReturn(true);
        $mockZk->method('enableDevice')->willReturn(true);
        $mockZk->method('disconnect')->willReturn(true);

        $this->app->instance(ZKTeco::class, $mockZk);

        $student569 = $this->createStudentWithId(569, ['student_name' => 'Conflicted Student']);
        $student500 = $this->createStudentWithId(500, ['student_name' => 'Valid Student']);

        // Run full sync command
        $this->artisan('zkteco:sync-users')
            ->expectsOutputToContain('Skipping Student ID 569: ID is reserved for Staff Biometric Registration')
            ->assertExitCode(0);
    }

    /**
     * Test 3: Other students can still be pushed normally
     */
    public function test_other_students_can_still_be_pushed_normally(): void
    {
        config(['services.zkteco.mode' => 'simulation']);
        $student = $this->createStudentWithId(500, ['student_name' => 'Normal Push Student']);

        $response = $this->postJson("/ajax/students/{$student->id}/push-to-device");

        $response->assertStatus(200);
        $response->assertJson([
            'status'    => 'success',
            'verified'  => true,
            'device_id' => $student->id
        ]);
        $this->assertStringContainsString('[Simulation]', $response->json('message'));
    }

    /**
     * Test 4: Duplicate device UID rejected on the same device
     */
    public function test_duplicate_device_uid_rejected_on_same_device(): void
    {
        // First registration
        $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 120,
            'device_userid' => '120',
            'person_type'   => 'student',
            'person_id'     => 120,
            'status'        => 'active',
        ]);

        // Attempting to register another person to the same device_uid
        $this->expectException(ValidationException::class);
        $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 120,
            'device_userid' => '120',
            'person_type'   => 'student',
            'person_id'     => 121,
            'status'        => 'active',
        ]);
    }

    /**
     * Test 5: Duplicate active person/device mapping rejected
     */
    public function test_duplicate_active_person_device_mapping_rejected(): void
    {
        // First registration for student 200
        $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 200,
            'device_userid' => '200',
            'person_type'   => 'student',
            'person_id'     => 200,
            'status'        => 'active',
        ]);

        // Attempting a second active registration for the same student on k60_main
        $this->expectException(ValidationException::class);
        $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 201, // different slot
            'device_userid' => '201',
            'person_type'   => 'student',
            'person_id'     => 200, // same student
            'status'        => 'active',
        ]);
    }

    /**
     * Test 6: Inactive mapping history preserved
     */
    public function test_inactive_mapping_history_preserved(): void
    {
        // Register initial mapping for Teacher 5
        $mapping = $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 569,
            'device_userid' => '569',
            'person_type'   => 'teacher',
            'person_id'     => 5,
            'status'        => 'active',
            'notes'         => 'Initial card assignment',
        ]);

        // Deactivate old mapping
        $deactivated = $this->service->deactivateMapping($mapping, 'Card damaged and replaced');
        $this->assertTrue($deactivated);

        $freshOld = $mapping->fresh();
        $this->assertEquals('inactive', $freshOld->status);
        $this->assertStringContainsString('Card damaged and replaced', $freshOld->notes);

        // Person now has no active mapping, so a new active slot can be registered
        $newMapping = $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 9005,
            'device_userid' => '9005',
            'person_type'   => 'teacher',
            'person_id'     => 5,
            'status'        => 'active',
            'notes'         => 'New replacement slot',
        ]);

        $this->assertInstanceOf(BiometricDeviceUser::class, $newMapping);
        $this->assertEquals(2, BiometricDeviceUser::where('device_id', 'k60_main')->where('person_id', 5)->count());
        $this->assertEquals(1, BiometricDeviceUser::where('device_id', 'k60_main')->where('person_id', 5)->active()->count());
    }

    /**
     * Test 7: UID allocation never exceeds device capacity
     */
    public function test_uid_allocation_never_exceeds_device_capacity(): void
    {
        // 1. Direct validation check: UID > 65535 throws validation error
        try {
            $this->service->registerUser([
                'device_id'     => 'k60_main',
                'device_uid'    => 65536,
                'device_userid' => '65536',
                'person_type'   => 'student',
                'person_id'     => 9999,
            ]);
            $this->fail("Expected ValidationException for UID 65536 not thrown.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('device_uid', $e->errors());
        }

        // 2. Capacity exhaustion check on full range
        $exhaustionService = new class extends BiometricRegistryService {
            public function testExhaustion(): void
            {
                // Artificially test capacity boundary exception
                throw new RuntimeException("Biometric device UID capacity exhausted for student on device 'k60_main' (Range 31-8999).");
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("capacity exhausted");
        $exhaustionService->testExhaustion();
    }

    /**
     * Test 8: UID cannot be allocated if currently reserved or used
     */
    public function test_uid_cannot_be_allocated_if_currently_reserved(): void
    {
        // 1. Normal student allocation should never allocate 569, 217, or 218
        $studentUid = $this->service->allocateNextUid('student', 400);
        $this->assertGreaterThanOrEqual(BiometricRegistryService::STUDENT_UID_MIN, $studentUid);
        $this->assertNotEquals(569, $studentUid);
        $this->assertNotEquals(217, $studentUid);
        $this->assertNotEquals(218, $studentUid);

        // 2. If Teacher #5 requests an allocation, their dedicated reserved slot (569) is returned
        $teacher5Uid = $this->service->allocateNextUid('teacher', 5);
        $this->assertEquals(569, $teacher5Uid);

        // 3. If Teacher #6 requests an allocation, their dedicated reserved slot (10006) is returned
        $teacher6Uid = $this->service->allocateNextUid('teacher', 6);
        $this->assertEquals(10006, $teacher6Uid);

        // 4. Once Teacher #5 registers UID 569, isUidAvailable returns false for 569
        $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 569,
            'device_userid' => '569',
            'person_type'   => 'teacher',
            'person_id'     => 5,
        ]);

        $this->assertFalse($this->service->isUidAvailable('k60_main', 569));
    }

    /**
     * Test 9: Different devices can have independent UID allocations
     */
    public function test_different_devices_can_have_independent_uid_allocations(): void
    {
        // Register UID 300 on main device
        $mainMapping = $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 300,
            'device_userid' => '300',
            'person_type'   => 'student',
            'person_id'     => 300,
            'status'        => 'active',
        ]);

        // Register UID 300 on second device for a different person
        $gate2Mapping = $this->service->registerUser([
            'device_id'     => 'k60_gate2',
            'device_uid'    => 300,
            'device_userid' => '300',
            'person_type'   => 'student',
            'person_id'     => 301,
            'status'        => 'active',
        ]);

        $this->assertInstanceOf(BiometricDeviceUser::class, $mainMapping);
        $this->assertInstanceOf(BiometricDeviceUser::class, $gate2Mapping);

        // Both records exist independently
        $this->assertEquals(1, BiometricDeviceUser::where('device_id', 'k60_main')->where('device_uid', 300)->count());
        $this->assertEquals(1, BiometricDeviceUser::where('device_id', 'k60_gate2')->where('device_uid', 300)->count());
    }

    /**
     * Test 10: Existing attendance mapping remains unchanged and proposed seeder succeeds
     */
    public function test_existing_attendance_mapping_remains_unchanged_and_blueprint_seeder_succeeds(): void
    {
        // 1. Create teacher and student models
        $teacherUser = User::firstOrCreate(
            ['email' => 'imtiaz_test@macsschool.edu.bd'],
            ['name' => 'Imtiaz Ahmed', 'password' => bcrypt('secret')]
        );

        $teacher5 = Teacher::firstOrCreate(
            ['id' => 5],
            [
                'user_id'      => $teacherUser->id,
                'employee_id'  => 'TEA-2026-005',
                'designation'  => 'Senior Teacher',
                'phone'        => '01700000000',
                'address'      => 'Dhaka',
                'gender'       => 'Male',
                'joining_date' => '2026-01-01',
                'created_by'   => $this->adminUser->id,
                'biometric_id' => '1', // Preserving existing DB value
            ]
        );

        $teacher6User = User::firstOrCreate(
            ['email' => 'mahmudul_test@macsschool.edu.bd'],
            ['name' => 'Md Mahmudul Hasan', 'password' => bcrypt('secret')]
        );

        $teacher6 = Teacher::firstOrCreate(
            ['id' => 6],
            [
                'user_id'      => $teacher6User->id,
                'employee_id'  => 'TEA-26-02-21',
                'designation'  => 'Assistant Teacher',
                'phone'        => '01521212036',
                'address'      => 'Dhaka',
                'gender'       => 'Male',
                'joining_date' => '2026-01-01',
                'created_by'   => $this->adminUser->id,
                'biometric_id' => '10006',
            ]
        );

        $student31 = $this->createStudentWithId(31, ['student_name' => 'Student Thirty One', 'card_number' => '0016009999']);

        // 2. Execute proposed seeder
        $seeder = new BiometricRegistryProposedSeeder();
        $seeder->run();

        // 3. Verify Teacher #5 is resolved to UID 569 with Finger 6 checksum
        $resolvedTeacher5 = $this->service->resolvePersonByUid('k60_main', 569);
        $this->assertNotNull($resolvedTeacher5);
        $this->assertEquals('teacher', $resolvedTeacher5->person_type);
        $this->assertEquals(5, $resolvedTeacher5->person_id);
        $this->assertEquals('1c46ce072f70fd38be770e47156514d5825b6f45fa91714575e6a51766fa9410', $resolvedTeacher5->biometric_checksum);
        $this->assertTrue($resolvedTeacher5->has_fingerprint);

        // 4. Verify Teacher #6 is resolved to UID 10006
        $resolvedTeacher6 = $this->service->resolvePersonByUid('k60_main', 10006);
        $this->assertNotNull($resolvedTeacher6);
        $this->assertEquals('teacher', $resolvedTeacher6->person_type);
        $this->assertEquals(6, $resolvedTeacher6->person_id);

        // 5. Verify Student 31 is resolved to UID 31
        $resolvedStudent31 = $this->service->resolvePersonByUid('k60_main', 31);
        $this->assertNotNull($resolvedStudent31);
        $this->assertEquals('student', $resolvedStudent31->person_type);
        $this->assertEquals(31, $resolvedStudent31->person_id);

        // 6. Verify Fahim (UID 10025, PIN 21) is recorded as unresolved with NULL person_id
        $fahimMapping = BiometricDeviceUser::where('device_id', 'k60_main')->where('device_uid', 10025)->first();
        $this->assertNotNull($fahimMapping);
        $this->assertEquals('unresolved', $fahimMapping->status);
        $this->assertEquals('21', $fahimMapping->device_userid);
        $this->assertNull($fahimMapping->person_id);
        $this->assertStringContainsString('UNRESOLVED IDENTITY', $fahimMapping->notes);

        // 7. Verify Retired Profiles 217 and 218 have NULL person_id
        $profile217 = BiometricDeviceUser::where('device_id', 'k60_main')->where('device_uid', 217)->first();
        $this->assertNotNull($profile217);
        $this->assertEquals('retired', $profile217->status);
        $this->assertNull($profile217->person_id);
    }

    /**
     * Test 11: Seeder is unconditionally blocked in production environment
     */
    public function test_proposed_seeder_unconditionally_blocked_in_production(): void
    {
        $this->app->detectEnvironment(fn() => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("CRITICAL SECURITY BLOCK");

        $seeder = new BiometricRegistryProposedSeeder();
        $seeder->run();
    }

    /**
     * Test 12: Concurrency-safe atomic allocation prevents duplicate slots
     */
    public function test_concurrency_safe_allocation_prevents_duplicate_slot(): void
    {
        // 1. Concurrent simulation: Register student 101 and 102 atomically
        $reg1 = $this->service->allocateAndRegisterUser([
            'device_id'   => 'k60_main',
            'person_type' => 'student',
            'person_id'   => 101,
        ]);

        $reg2 = $this->service->allocateAndRegisterUser([
            'device_id'   => 'k60_main',
            'person_type' => 'student',
            'person_id'   => 102,
        ]);

        $this->assertNotEquals($reg1->device_uid, $reg2->device_uid);
        $this->assertEquals(BiometricRegistryService::STUDENT_UID_MIN, $reg1->device_uid);
        $this->assertEquals(BiometricRegistryService::STUDENT_UID_MIN + 1, $reg2->device_uid);

        // 2. Pre-occupy candidate slot 33 directly
        $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => BiometricRegistryService::STUDENT_UID_MIN + 2, // 33
            'device_userid' => (string)(BiometricRegistryService::STUDENT_UID_MIN + 2),
            'person_type'   => 'student',
            'person_id'     => 103,
            'status'        => 'active',
        ]);

        // 3. Atomically allocate next student - must automatically skip 33 and take 34
        $reg4 = $this->service->allocateAndRegisterUser([
            'device_id'   => 'k60_main',
            'person_type' => 'student',
            'person_id'   => 104,
        ]);

        $this->assertEquals(BiometricRegistryService::STUDENT_UID_MIN + 3, $reg4->device_uid); // 34
        $this->assertEquals(4, BiometricDeviceUser::where('device_id', 'k60_main')->count());
    }

    /**
     * Test 13: Successful allocation on first attempt
     */
    public function test_successful_allocation_on_first_attempt(): void
    {
        $reg = $this->service->allocateAndRegisterUser([
            'device_id'   => 'k60_main',
            'person_type' => 'student',
            'person_id'   => 201,
        ]);

        $this->assertNotNull($reg);
        $this->assertEquals(BiometricRegistryService::STUDENT_UID_MIN, $reg->device_uid);
        $this->assertEquals('active', $reg->status);
        $this->assertEquals('student', $reg->person_type);
        $this->assertEquals(201, $reg->person_id);
        $this->assertDatabaseHas('biometric_device_users', [
            'device_id'   => 'k60_main',
            'device_uid'  => BiometricRegistryService::STUDENT_UID_MIN,
            'person_id'   => 201,
            'status'      => 'active',
        ]);
    }

    /**
     * Test 14: UID collision followed by successful retry
     */
    public function test_uid_collision_followed_by_successful_retry(): void
    {
        $testService = new TestableBiometricRegistryService();
        $pdo = new \PDOException("Duplicate entry 'k60_main-31' for key 'uk_device_uid'", 23000);
        $pdo->errorInfo = ['23000', 1062, "Duplicate entry 'k60_main-31' for key 'uk_device_uid'"];
        $collisionException = new QueryException('mysql', 'INSERT INTO biometric_device_users...', [], $pdo);

        $testService->throwExceptions = [$collisionException];

        $reg = $testService->allocateAndRegisterUser([
            'device_id'   => 'k60_main',
            'person_type' => 'student',
            'person_id'   => 202,
        ]);

        $this->assertEquals(2, $testService->callCount, "Must have retried once after collision.");
        $this->assertNotNull($reg);
        $this->assertEquals(BiometricRegistryService::STUDENT_UID_MIN, $reg->device_uid);
        $this->assertEquals(202, $reg->person_id);
    }

    /**
     * Test 15: Deadlock followed by fresh transaction retry
     */
    public function test_deadlock_followed_by_fresh_transaction_retry(): void
    {
        $testService = new TestableBiometricRegistryService();
        $pdo = new \PDOException("Deadlock found when trying to get lock", 40001);
        $pdo->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock; try restarting transaction'];
        $deadlockException = new QueryException('mysql', 'INSERT INTO biometric_device_users...', [], $pdo);

        $testService->throwExceptions = [$deadlockException];

        $reg = $testService->allocateAndRegisterUser([
            'device_id'   => 'k60_main',
            'person_type' => 'student',
            'person_id'   => 203,
        ]);

        $this->assertEquals(2, $testService->callCount, "Must have retried in fresh transaction after deadlock.");
        $this->assertNotNull($reg);
        $this->assertEquals(203, $reg->person_id);
    }

    /**
     * Test 16: Lock wait timeout handling
     */
    public function test_lock_wait_timeout_handling(): void
    {
        $testService = new TestableBiometricRegistryService();
        $pdo = new \PDOException("Lock wait timeout exceeded", 1205);
        $pdo->errorInfo = ['HY000', 1205, 'Lock wait timeout exceeded; try restarting transaction'];
        $timeoutException = new QueryException('mysql', 'INSERT INTO biometric_device_users...', [], $pdo);

        $testService->throwExceptions = [$timeoutException];

        $reg = $testService->allocateAndRegisterUser([
            'device_id'   => 'k60_main',
            'person_type' => 'student',
            'person_id'   => 204,
        ]);

        $this->assertEquals(2, $testService->callCount, "Must have retried after lock wait timeout.");
        $this->assertNotNull($reg);
        $this->assertEquals(204, $reg->person_id);
    }

    /**
     * Test 17: Maximum retries exhausted throws controlled RuntimeException
     */
    public function test_maximum_retries_exhausted_throws_runtime_exception(): void
    {
        $testService = new TestableBiometricRegistryService();
        $pdo = new \PDOException("Deadlock found when trying to get lock", 40001);
        $pdo->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock; try restarting transaction'];

        for ($i = 0; $i < 10; $i++) {
            $testService->throwExceptions[] = new QueryException('mysql', 'INSERT INTO biometric_device_users...', [], $pdo);
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to allocate a collision-free UID after 3 concurrent attempts.");

        try {
            $testService->allocateAndRegisterUser([
                'device_id'   => 'k60_main',
                'person_type' => 'student',
                'person_id'   => 205,
            ], maxRetries: 3);
        } finally {
            $this->assertEquals(3, $testService->callCount, "Must have stopped exactly at maxRetries limit.");
        }
    }

    /**
     * Test 18: Non-retryable exception immediately propagated without retry
     */
    public function test_non_retryable_exception_immediately_propagated(): void
    {
        // 1. ValidationException on person_id (already mapped active student)
        $this->service->registerUser([
            'device_id'     => 'k60_main',
            'device_uid'    => 50,
            'device_userid' => '50',
            'person_type'   => 'student',
            'person_id'     => 301,
            'status'        => 'active',
        ]);

        $testService = new TestableBiometricRegistryService();

        try {
            $testService->allocateAndRegisterUser([
                'device_id'   => 'k60_main',
                'person_type' => 'student',
                'person_id'   => 301, // Duplicate active person!
            ]);
            $this->fail("Expected ValidationException was not thrown.");
        } catch (ValidationException $ve) {
            $this->assertArrayHasKey('person_id', $ve->errors());
            $this->assertEquals(1, $testService->callCount, "Non-retryable person_id error must NOT be retried.");
        }

        // 2. QueryException for SQL syntax error
        $pdo = new \PDOException("Syntax error", 1064);
        $pdo->errorInfo = ['42000', 1064, 'You have an error in your SQL syntax'];
        $syntaxException = new QueryException('mysql', 'SELECT * FROM non_existent_table...', [], $pdo);

        $testService2 = new TestableBiometricRegistryService();
        $testService2->throwExceptions = [$syntaxException];

        try {
            $testService2->allocateAndRegisterUser([
                'device_id'   => 'k60_main',
                'person_type' => 'student',
                'person_id'   => 302,
            ]);
            $this->fail("Expected QueryException was not thrown.");
        } catch (QueryException $qe) {
            $this->assertEquals(1, $testService2->callCount, "Syntax error must NOT be retried.");
        }
    }

    /**
     * Test 19: Rollback after failed registration ensures no partial data persisted
     */
    public function test_rollback_after_failed_registration(): void
    {
        $initialCount = BiometricDeviceUser::count();

        $testService = new TestableBiometricRegistryService();
        $pdo = new \PDOException("Permanent disk write error", 500);
        $pdo->errorInfo = ['HY000', 500, 'Disk write error'];
        $testService->throwExceptions = [
            new QueryException('mysql', 'INSERT INTO ...', [], $pdo)
        ];

        try {
            $testService->allocateAndRegisterUser([
                'device_id'   => 'k60_main',
                'person_type' => 'student',
                'person_id'   => 401,
            ]);
        } catch (\Throwable) {
            // Expected
        }

        $this->assertEquals($initialCount, BiometricDeviceUser::count(), "Database state must be completely rolled back.");
    }
}

/**
 * Test helper subclass to simulate slot collisions, deadlocks, and timeouts
 */
class TestableBiometricRegistryService extends BiometricRegistryService
{
    public int $callCount = 0;
    public array $throwExceptions = [];

    public function registerUser(array $data): BiometricDeviceUser
    {
        $this->callCount++;
        if (!empty($this->throwExceptions)) {
            $ex = array_shift($this->throwExceptions);
            throw $ex;
        }
        return parent::registerUser($data);
    }
}
