<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classPlay;
    protected Classes $classOne;
    protected Section $sectionA;
    protected Student $student1;
    protected Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_att@macsschool.com'],
            ['name' => 'Attendance Admin', 'password' => bcrypt('password')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->session = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026', 'user_id' => $this->adminUser->id]);

        $this->classPlay = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Play', 'user_id' => $this->adminUser->id]);
        $this->classOne  = Classes::firstOrCreate(['id' => 2], ['class_name' => 'One', 'user_id' => $this->adminUser->id]);

        $this->sectionA = Section::firstOrCreate(['id' => 1], ['section_name' => 'A', 'user_id' => $this->adminUser->id]);

        $this->student1 = Student::create([
            'user_id'             => $this->adminUser->id,
            'branch_id'           => $this->branch->id,
            'session_year_id'     => $this->session->id,
            'class_id'            => $this->classPlay->id,
            'section_id'          => $this->sectionA->id,
            'student_identity'    => 'STD-1001',
            'student_name'        => 'Rahim Uddin',
            'roll_number'         => '1',
            'father_name'         => 'Father Rahim',
            'father_mobile'       => '01711000011',
            'mother_name'         => 'Mother Rahim',
            'mother_mobile'       => '01711000021',
            'guardian_name'       => 'Guardian Rahim',
            'guardian_mobile'     => '01711000001',
            'status'              => 'Active',
            'sms_status'          => 'Active',
            'dob'                 => '2020-01-01',
            'gender'              => 'Male',
            'present_village'     => 'V',
            'present_post_office' => 'P',
            'present_district'    => 'D',
            'present_division'    => 'Div',
            'permanent_village'   => 'V',
            'permanent_post_office' => 'P',
            'permanent_district'  => 'D',
            'permanent_division'  => 'Div',
        ]);

        $this->student2 = Student::create([
            'user_id'             => $this->adminUser->id,
            'branch_id'           => $this->branch->id,
            'session_year_id'     => $this->session->id,
            'class_id'            => $this->classOne->id,
            'section_id'          => $this->sectionA->id,
            'student_identity'    => 'STD-2002',
            'student_name'        => 'Fatima Khan',
            'roll_number'         => '2',
            'father_name'         => 'Father Fatima',
            'father_mobile'       => '01811000012',
            'mother_name'         => 'Mother Fatima',
            'mother_mobile'       => '01811000022',
            'guardian_name'       => 'Guardian Fatima',
            'guardian_mobile'     => '01811000002',
            'status'              => 'Active',
            'sms_status'          => 'Active',
            'dob'                 => '2019-01-01',
            'gender'              => 'Female',
            'present_village'     => 'V',
            'present_post_office' => 'P',
            'present_district'    => 'D',
            'present_division'    => 'Div',
            'permanent_village'   => 'V',
            'permanent_post_office' => 'P',
            'permanent_district'  => 'D',
            'permanent_division'  => 'Div',
        ]);
    }

    protected function createAttendance(array $attributes): Attendance
    {
        return Attendance::create(array_merge([
            'branch_id'       => $this->branch->id,
            'session_year_id' => $this->session->id,
            'class_id'        => $this->classPlay->id,
            'section_id'      => $this->sectionA->id,
            'user_id'         => $this->adminUser->id,
            'status'          => 'Present',
        ], $attributes));
    }

    /**
     * Test 1: Guest is redirected to login
     */
    public function test_guest_is_redirected_from_attendance_report(): void
    {
        $response = $this->get('/attendance/report');
        $response->assertRedirect('/tyro-login');

        $ajaxResponse = $this->getJson('/ajax/attendance/report-data');
        $ajaxResponse->assertStatus(401);
    }

    /**
     * Test 2: Authorized admin can view attendance report page with preloaded filter lists
     */
    public function test_authorized_admin_can_view_attendance_report(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/attendance/report');
        $response->assertStatus(200);
        $response->assertViewIs('pages.attendance.report');
        $response->assertViewHasAll(['branches', 'sessions', 'classes', 'sections']);
        $response->assertSee('Attendance Report');
        $response->assertSee('MACS School');
    }

    /**
     * Test 3: Daily attendance report filters and returns correct student status and IN/OUT logs
     */
    public function test_daily_attendance_report_filtering(): void
    {
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-10',
            'status'          => 'Present',
            'remarks'         => 'Card Swiped (In: 08:15:00, Out: 13:30:00)',
        ]);

        $this->createAttendance([
            'student_id'      => $this->student2->id,
            'class_id'        => $this->classOne->id,
            'attendance_date' => '2026-08-10',
            'status'          => 'Absent',
            'remarks'         => 'Absent (Biometric Check)',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/ajax/attendance/report-data?' . http_build_query([
            'report_type' => 'daily',
            'date_from'   => '2026-08-10',
            'date_to'     => '2026-08-10',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'report_type' => 'daily',
            'stats' => [
                'total_students' => 2,
                'total_records'  => 2,
                'present_count'  => 1,
                'late_count'     => 0,
                'absent_count'   => 1,
            ]
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Check IN/OUT parsing
        $presentRow = collect($data)->firstWhere('student_id', $this->student1->id);
        $this->assertEquals('Present', $presentRow['status']);
        $this->assertEquals('08:15 AM', $presentRow['in_time']);
        $this->assertEquals('01:30 PM', $presentRow['out_time']);
    }

    /**
     * Test 4: Monthly attendance summary calculates correct present, late, absent, and recorded days without duplicate counting
     */
    public function test_monthly_summary_aggregates_without_duplicate_counting(): void
    {
        // Student 1: 3 days in August: 1 Present, 1 Late, 1 Absent
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-01',
            'status'          => 'Present',
        ]);
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-02',
            'status'          => 'Late',
        ]);
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-03',
            'status'          => 'Absent',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/ajax/attendance/report-data?' . http_build_query([
            'report_type' => 'monthly',
            'date_from'   => '2026-08-01',
            'date_to'     => '2026-08-31',
            'class_id'    => $this->classPlay->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);

        $studentRow = $data[0];
        $this->assertEquals($this->student1->id, $studentRow['student_id']);
        $this->assertEquals(1, $studentRow['present_days']);
        $this->assertEquals(1, $studentRow['late_days']);
        $this->assertEquals(1, $studentRow['absent_days']);
        $this->assertEquals(3, $studentRow['total_recorded']);
        $this->assertEquals(2, $studentRow['attended_days']); // Present + Late
        // Attendance % = (2 / 3) * 100 = 66.7%
        $this->assertEquals(66.7, $studentRow['attendance_percentage']);
    }

    /**
     * Test 5: Days with no attendance records are NOT falsely counted as absences
     */
    public function test_unrecorded_days_not_counted_as_absences(): void
    {
        // Student has only 1 recorded day in entire month
        $this->createAttendance([
            'student_id'      => $this->student2->id,
            'class_id'        => $this->classOne->id,
            'attendance_date' => '2026-08-15',
            'status'          => 'Present',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/ajax/attendance/report-data?' . http_build_query([
            'report_type' => 'monthly',
            'date_from'   => '2026-08-01',
            'date_to'     => '2026-08-31',
            'class_id'    => $this->classOne->id,
        ]));

        $response->assertStatus(200);
        $studentRow = $response->json('data.0');
        $this->assertEquals(1, $studentRow['present_days']);
        $this->assertEquals(0, $studentRow['absent_days']); // 30 unrecorded days are NOT absences
        $this->assertEquals(1, $studentRow['total_recorded']);
        $this->assertEquals(100.0, $studentRow['attendance_percentage']);
    }

    /**
     * Test 6: Student attendance history drilldown returns student profile, stats, and chronological records
     */
    public function test_student_attendance_history_drilldown(): void
    {
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-01',
            'status'          => 'Present',
            'remarks'         => 'Card Swiped (In: 09:00:00)',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/ajax/attendance/report-data?' . http_build_query([
            'report_type' => 'student_history',
            'student_id'  => $this->student1->id,
            'date_from'   => '2026-08-01',
            'date_to'     => '2026-08-31',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'student' => [
                'id'               => $this->student1->id,
                'student_name'     => 'Rahim Uddin',
                'student_identity' => 'STD-1001',
            ],
            'student_stats' => [
                'total_recorded'        => 1,
                'present_days'          => 1,
                'late_days'             => 0,
                'absent_days'           => 0,
                'attendance_percentage' => 100.0,
            ]
        ]);

        $records = $response->json('data');
        $this->assertCount(1, $records);
        $this->assertEquals('2026-08-01', $records[0]['attendance_date']);
        $this->assertEquals('09:00 AM', $records[0]['in_time']);
    }

    /**
     * Test 7: Absentee report returns strictly confirmed absent records
     */
    public function test_absentee_report_filtering(): void
    {
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-05',
            'status'          => 'Present',
        ]);

        $this->createAttendance([
            'student_id'      => $this->student2->id,
            'class_id'        => $this->classOne->id,
            'attendance_date' => '2026-08-05',
            'status'          => 'Absent',
            'remarks'         => 'Absent (Biometric Check)',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/ajax/attendance/report-data?' . http_build_query([
            'report_type' => 'absentee',
            'date_from'   => '2026-08-05',
            'date_to'     => '2026-08-05',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->student2->id, $data[0]['student_id']);
        $this->assertEquals('Absent', $data[0]['status']);
        $this->assertEquals('01811000002', $data[0]['guardian_mobile']);
    }

    /**
     * Test 8: Student search filtering by name, student ID, or roll number
     */
    public function test_student_search_filtering(): void
    {
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-01',
            'status'          => 'Present',
        ]);
        $this->createAttendance([
            'student_id'      => $this->student2->id,
            'class_id'        => $this->classOne->id,
            'attendance_date' => '2026-08-01',
            'status'          => 'Present',
        ]);

        // Search by identity 'STD-1001'
        $response = $this->actingAs($this->adminUser)->getJson('/ajax/attendance/report-data?' . http_build_query([
            'report_type' => 'daily',
            'date_from'   => '2026-08-01',
            'date_to'     => '2026-08-01',
            'search'      => 'STD-1001',
        ]));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->student1->id, $response->json('data.0.student_id'));
    }

    /**
     * Test 9: Streamed CSV export returns valid headers and UTF-8 BOM
     */
    public function test_csv_export_returns_streamed_file_matching_filters(): void
    {
        $this->createAttendance([
            'student_id'      => $this->student1->id,
            'attendance_date' => '2026-08-01',
            'status'          => 'Present',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/attendance/report/export?' . http_build_query([
            'report_type' => 'daily',
            'date_from'   => '2026-08-01',
            'date_to'     => '2026-08-01',
        ]));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition') ?? '', 'attendance_daily_report_'));
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-type'));

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check UTF-8 BOM (\xEF\xBB\xBF)
        $this->assertStringStartsWith(chr(0xEF) . chr(0xBB) . chr(0xBF), $content);
        $this->assertStringContainsString('MACS SCHOOL & COLLEGE', $content);
        $this->assertStringContainsString('Rahim Uddin', $content);
        $this->assertStringContainsString('STD-1001', $content);
    }

    /**
     * Test 10: Date normalization ensures date_from <= date_to and capped to 366 days
     */
    public function test_date_normalization_handles_inverted_dates(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson('/ajax/attendance/report-data?' . http_build_query([
            'report_type' => 'daily',
            'date_from'   => '2026-08-15',
            'date_to'     => '2026-08-01', // Inverted
        ]));

        $response->assertStatus(200);
        $filters = $response->json('filters');
        $this->assertEquals('2026-08-01', $filters['date_from']);
        $this->assertEquals('2026-08-15', $filters['date_to']);
    }

    /**
     * Test 11: Print layout refinement elements, Alpine helpers, and footer cleanup
     */
    public function test_print_layout_refinement_structure_and_helpers_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/attendance/report');
        $response->assertStatus(200);

        // Alpine helper functions
        $response->assertSee('formatShortSection', false);
        $response->assertSee('formatDisplaySection', false);
        $response->assertSee('formatClassSection', false);
        $response->assertSee('shouldShowSectionInHeader', false);
        $response->assertSee('headerSectionText', false);

        // Separate Class & Section columns and single-line classes
        $response->assertSee('col-daily-class', false);
        $response->assertSee('col-daily-section', false);
        $response->assertSee('whitespace-nowrap', false);

        // Responsive class & section column toggling & container spacing
        $response->assertSee('has-no-class-filter', false);
        $response->assertSee('has-class-filter', false);
        $response->assertSee('!mt-0', false);

        // Signatures block present
        $response->assertSee('Prepared By', false);
        $response->assertSee('Class Teacher', false);
        $response->assertSee('Principal / Head of School', false);

        // Removed brand footer line and unwanted texts
        $response->assertDontSee('MACS School Management System &bull; Attendance Reporting Module', false);
        $response->assertDontSee('Generated: {{ date(\'d M, Y h:i A\') }}', false);
    }
}

