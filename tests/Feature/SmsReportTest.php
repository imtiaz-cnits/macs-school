<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Student;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class SmsReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_report@macsschool.com'],
            ['name' => 'Admin Report', 'password' => bcrypt('secret')]
        );

        $role = \HasinHayder\Tyro\Models\Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->adminUser->assignRole($role);

        $this->actingAs($this->adminUser);
    }

    protected function createStudent(): Student
    {
        $user = User::firstOrCreate(
            ['email' => 'admin_report@macsschool.com'],
            ['name' => 'Admin Report', 'password' => bcrypt('secret')]
        );

        $branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main', 'user_id' => $user->id]);
        $session = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026-2027', 'user_id' => $user->id]);
        $class = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Ten', 'user_id' => $user->id]);
        $section = Section::firstOrCreate(['id' => 1], ['section_name' => 'A', 'user_id' => $user->id]);

        return Student::create([
            'user_id'                => $user->id,
            'student_identity'       => 'STD-' . rand(1000, 9999),
            'student_name'           => 'Sakib Al Hasan',
            'roll_number'            => '75',
            'dob'                    => '2010-01-01',
            'gender'                 => 'Male',
            'father_name'            => 'Father',
            'father_mobile'          => '01711111111',
            'mother_name'            => 'Mother',
            'mother_mobile'          => '01722222222',
            'guardian_name'          => 'Guardian',
            'guardian_mobile'        => '01712345678',
            'branch_id'              => $branch->id,
            'session_year_id'        => $session->id,
            'class_id'               => $class->id,
            'section_id'             => $section->id,
            'status'                 => 'Active',
            'sms_status'             => 'Active',
            'present_village'        => 'V',
            'present_post_office'    => 'P',
            'present_district'       => 'D',
            'present_division'       => 'Div',
            'permanent_village'      => 'V',
            'permanent_post_office'  => 'P',
            'permanent_district'     => 'D',
            'permanent_division'     => 'Div',
        ]);
    }

    public function test_sms_report_renders_with_accurate_counters_and_balance(): void
    {
        Http::fake([
            'https://api.mimsms.com/api/V2/BalanceCheck' => Http::response([
                'status' => 'success',
                'balance' => 2450.50,
                'currency' => 'BDT'
            ], 200),
        ]);

        $student = $this->createStudent();

        // 1 Sent today
        SmsLog::create([
            'student_id'    => $student->id,
            'mobile_number' => '01712345678',
            'message'       => 'Test check-in',
            'status'        => 'Sent',
            'event_type'    => 'check_in',
            'created_at'    => Carbon::now('Asia/Dhaka'),
            'updated_at'    => Carbon::now('Asia/Dhaka'),
        ]);

        // 1 Blocked today (should NOT increment todaySent counter)
        SmsLog::create([
            'student_id'    => $student->id,
            'mobile_number' => '01899999999',
            'message'       => 'Blocked log',
            'status'        => 'Blocked (Test Mode)',
            'created_at'    => Carbon::now('Asia/Dhaka'),
            'updated_at'    => Carbon::now('Asia/Dhaka'),
        ]);

        $response = $this->get('/sms/report');

        $response->assertStatus(200);
        $response->assertViewHas('todaySent', 1);
        $response->assertViewHas('totalSent', 1);
        $response->assertViewHas('totalFailed', 1);
        $response->assertSee('Sakib Al Hasan');
        $response->assertSee('2,450.50');
    }

    public function test_sms_report_filters_by_search_and_status(): void
    {
        Http::fake([
            'https://api.mimsms.com/*' => Http::response(['balance' => 100], 200),
        ]);

        $student = $this->createStudent();

        SmsLog::create([
            'student_id'    => $student->id,
            'mobile_number' => '01712345678',
            'message'       => 'Checkin Sakib',
            'status'        => 'Sent',
            'created_at'    => Carbon::now('Asia/Dhaka'),
            'updated_at'    => Carbon::now('Asia/Dhaka'),
        ]);

        SmsLog::create([
            'student_id'    => null,
            'mobile_number' => '01988888888',
            'message'       => 'Notice message',
            'status'        => 'Failed',
            'created_at'    => Carbon::now('Asia/Dhaka'),
            'updated_at'    => Carbon::now('Asia/Dhaka'),
        ]);

        // Filter status=Failed
        $response = $this->get('/sms/report?status=Failed');
        $response->assertStatus(200);
        $response->assertSee('01988888888');
        $response->assertDontSee('01712345678');

        // Search for student name
        $searchResponse = $this->get('/sms/report?search=Sakib');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('01712345678');
        $searchResponse->assertDontSee('01988888888');
    }
}
