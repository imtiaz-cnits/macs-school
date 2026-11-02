<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\User;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Shift;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmrashed\Zkteco\Lib\ZKTeco;

class StudentDeviceSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classOne;
    protected Section $sectionA;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_devicesync@macsschool.com'],
            ['name' => 'Admin Sync Tester', 'password' => bcrypt('secret')]
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
                'type' => 'student',
                'in_time_start' => '08:00:00',
                'in_time_end' => '08:30:00',
                'late_time_end' => '08:45:00',
                'absent_time' => '09:00:00',
                'out_time' => '12:00:00',
                'shift_name' => 'Morning Shift',
                'user_id' => $this->adminUser->id
            ]
        );
    }

    protected function createStudent(array $attributes = []): Student
    {
        static $seq = 500;
        $seq++;

        $defaults = [
            'user_id'               => $this->adminUser->id,
            'student_identity'      => 'STD-TEST-' . $seq,
            'card_number'           => '0009544' . $seq,
            'student_name'          => 'Student Test ' . $seq,
            'roll_number'           => (string) $seq,
            'dob'                   => '2015-05-10',
            'gender'                => 'Male',
            'father_name'           => 'Father ' . $seq,
            'father_mobile'         => '0171000' . $seq,
            'mother_name'           => 'Mother ' . $seq,
            'mother_mobile'         => '0172000' . $seq,
            'guardian_name'         => 'Guardian ' . $seq,
            'guardian_mobile'       => '0173000' . $seq,
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
     * 1. Simulation mode returns instant success without initiating network sockets
     */
    public function test_push_to_device_simulation_mode(): void
    {
        config(['services.zkteco.mode' => 'simulation']);
        $student = $this->createStudent(['student_name' => 'Simulation Student']);

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
     * 2. Non-existent student returns 404
     */
    public function test_push_to_device_student_not_found(): void
    {
        config(['services.zkteco.mode' => 'live']);

        $response = $this->postJson('/ajax/students/999999/push-to-device');

        $response->assertStatus(404);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Student not found.'
        ]);
    }

    /**
     * 3. Connection failure returns 500 error
     */
    public function test_push_to_device_connection_failure(): void
    {
        config(['services.zkteco.mode' => 'live']);
        $student = $this->createStudent();

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->expects($this->once())
            ->method('connect')
            ->willReturn(false);

        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson("/ajax/students/{$student->id}/push-to-device");

        $response->assertStatus(500);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Unable to establish connection with ZKTeco biometric device.'
        ]);
    }

    /**
     * 4. When setUser() returns false (rejected by device), return 422 error
     */
    public function test_push_to_device_rejected_by_device(): void
    {
        config(['services.zkteco.mode' => 'live']);
        $student = $this->createStudent([
            'student_name' => 'Rejected Student',
            'card_number'  => '1234567890'
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->expects($this->once())->method('connect')->willReturn(true);
        $mockZk->expects($this->once())->method('disableDevice');
        $mockZk->expects($this->once())
            ->method('setUser')
            ->with($student->id, $student->id, 'Rejected Student', '', 0, 1234567890)
            ->willReturn(false);
        $mockZk->expects($this->once())->method('enableDevice');
        $mockZk->expects($this->once())->method('disconnect');

        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson("/ajax/students/{$student->id}/push-to-device");

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error'
        ]);
        $this->assertStringContainsString('rejected user payload', $response->json('message'));
    }

    /**
     * 5. Successful push with verified read-back
     */
    public function test_push_to_device_success_with_verified_read_back(): void
    {
        config(['services.zkteco.mode' => 'live']);
        $student = $this->createStudent([
            'student_name' => 'Md. Shakib Al Hasan',
            'card_number'  => '0009876543'
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->expects($this->once())->method('connect')->willReturn(true);
        $mockZk->expects($this->once())->method('disableDevice');
        $mockZk->expects($this->once())
            ->method('setUser')
            ->with($student->id, $student->id, 'Md. Shakib Al Hasan', '', 0, 9876543)
            ->willReturn(true);
        $mockZk->expects($this->once())
            ->method('getUser')
            ->willReturn([
                (string)$student->id => [
                    'uid'      => $student->id,
                    'userid'   => (string)$student->id,
                    'name'     => 'Md. Shakib Al Hasan',
                    'cardno'   => '0009876543',
                    'role'     => 0,
                    'password' => ''
                ]
            ]);
        $mockZk->expects($this->once())->method('enableDevice');
        $mockZk->expects($this->once())->method('disconnect');

        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson("/ajax/students/{$student->id}/push-to-device");

        $response->assertStatus(200);
        $response->assertJson([
            'status'         => 'success',
            'verified'       => true,
            'device_id'      => $student->id,
            'name_on_device' => 'Md. Shakib Al Hasan',
            'card_on_device' => 9876543
        ]);
        $this->assertStringContainsString('successfully pushed and verified', $response->json('message'));
    }

    /**
     * 6. Successful push with unconfirmed read-back
     */
    public function test_push_to_device_success_with_unconfirmed_read_back(): void
    {
        config(['services.zkteco.mode' => 'live']);
        $student = $this->createStudent([
            'student_name' => 'Tamim Iqbal',
            'card_number'  => '112233'
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->expects($this->once())->method('connect')->willReturn(true);
        $mockZk->expects($this->once())->method('disableDevice');
        $mockZk->expects($this->once())
            ->method('setUser')
            ->willReturn(true);
        $mockZk->expects($this->once())
            ->method('getUser')
            ->willReturn([]); // Empty or record missing in read-back
        $mockZk->expects($this->once())->method('enableDevice');
        $mockZk->expects($this->once())->method('disconnect');

        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson("/ajax/students/{$student->id}/push-to-device");

        $response->assertStatus(200);
        $response->assertJson([
            'status'    => 'success',
            'verified'  => false,
            'device_id' => $student->id
        ]);
        $this->assertStringContainsString('unconfirmed', $response->json('message'));
    }

    /**
     * 7. Safe ASCII fallback when student name is entirely in Bengali
     */
    public function test_push_to_device_handles_bengali_name_with_ascii_fallback(): void
    {
        config(['services.zkteco.mode' => 'live']);
        $student = $this->createStudent([
            'student_name' => 'মোঃ তানভীর হাসান',
            'roll_number'  => '07',
            'card_number'  => '554433'
        ]);

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->expects($this->once())->method('connect')->willReturn(true);
        $mockZk->expects($this->once())->method('disableDevice');
        $mockZk->expects($this->once())
            ->method('setUser')
            ->with($student->id, $student->id, 'Student R07', '', 0, 554433)
            ->willReturn(true);
        $mockZk->expects($this->once())
            ->method('getUser')
            ->willReturn([
                (string)$student->id => [
                    'uid'      => $student->id,
                    'userid'   => (string)$student->id,
                    'name'     => 'Student R07',
                    'cardno'   => '554433',
                ]
            ]);
        $mockZk->expects($this->once())->method('enableDevice');
        $mockZk->expects($this->once())->method('disconnect');

        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson("/ajax/students/{$student->id}/push-to-device");

        $response->assertStatus(200);
        $response->assertJson([
            'status'         => 'success',
            'verified'       => true,
            'name_on_device' => 'Student R07'
        ]);
        $this->assertStringContainsString('Bengali name used fallback', $response->json('message'));
    }

    /**
     * 8. Socket exception during connection or transaction is caught cleanly
     */
    public function test_push_to_device_catches_socket_exception(): void
    {
        config(['services.zkteco.mode' => 'live']);
        $student = $this->createStudent();

        $mockZk = $this->createMock(ZKTeco::class);
        $mockZk->expects($this->once())
            ->method('connect')
            ->willThrowException(new \Exception('UDP socket timeout on port 4370'));

        $this->app->instance(ZKTeco::class, $mockZk);

        $response = $this->postJson("/ajax/students/{$student->id}/push-to-device");

        $response->assertStatus(500);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Failed to push student to device: UDP socket timeout on port 4370'
        ]);
    }
}
