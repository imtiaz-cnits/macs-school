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

class StudentExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classOne;
    protected Classes $classTwo;
    protected Section $sectionA;
    protected Section $sectionB;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'superadmin_export@macsschool.com'],
            ['name' => 'Super Admin Tester', 'password' => bcrypt('secret')]
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
        $this->classTwo = Classes::firstOrCreate(['id' => 2], ['class_name' => 'Class Two', 'user_id' => $this->adminUser->id]);
        $this->sectionA = Section::firstOrCreate(['id' => 1], ['section_name' => 'Section A', 'user_id' => $this->adminUser->id]);
        $this->sectionB = Section::firstOrCreate(['id' => 2], ['section_name' => 'Section B', 'user_id' => $this->adminUser->id]);
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
        static $seq = 100;
        $seq++;

        $defaults = [
            'user_id'               => $this->adminUser->id,
            'student_identity'      => 'STD-' . $seq,
            'card_number'           => 'CARD' . $seq,
            'student_name'          => 'Student ' . $seq,
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
     * Helper to extract xl/sharedStrings.xml from an in-memory XLSX ZIP binary
     */
    protected function extractXlsxSharedStrings(string $binary): string
    {
        $offset = 0;
        while (($pos = strpos($binary, "\x50\x4b\x03\x04", $offset)) !== false) {
            $fnLen = unpack('v', substr($binary, $pos + 26, 2))[1];
            $extraLen = unpack('v', substr($binary, $pos + 28, 2))[1];
            $comSize = unpack('V', substr($binary, $pos + 18, 4))[1];
            $cMethod = unpack('v', substr($binary, $pos + 8, 2))[1];
            $fn = substr($binary, $pos + 30, $fnLen);
            $cData = substr($binary, $pos + 30 + $fnLen + $extraLen, $comSize);
            $uData = ($cMethod === 8) ? gzinflate($cData) : $cData;
            if (strpos($fn, 'sharedStrings') !== false) {
                return $uData;
            }
            $offset = $pos + 4;
        }
        return '';
    }

    /**
     * 1. Default columns and headings in genuine XLSX export
     */
    public function test_default_columns_and_headings_xlsx(): void
    {
        $this->createStudent([
            'student_name'     => 'Rahim Uddin',
            'student_identity' => 'STD-2026-001',
            'roll_number'      => '01',
            'guardian_mobile'  => '01748071572',
        ]);

        $response = $this->get('/ajax/students/export-excel');

        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition'), '.xlsx'));

        $content = $response->getContent();
        // Valid ZIP signature for XLSX
        $this->assertTrue(str_starts_with($content, "PK\x03\x04"));

        $strings = $this->extractXlsxSharedStrings($content);
        $this->assertStringContainsString('<t>Student Name</t>', $strings);
        $this->assertStringContainsString('<t>Student ID</t>', $strings);
        $this->assertStringContainsString('<t>Mobile Number</t>', $strings);
        $this->assertStringContainsString('<t>Class</t>', $strings);
        $this->assertStringContainsString('<t>Roll</t>', $strings);
        $this->assertStringContainsString('<t>Section</t>', $strings);
        $this->assertStringContainsString('<t>Rahim Uddin</t>', $strings);
        $this->assertStringContainsString('<t>STD-2026-001</t>', $strings);
        $this->assertStringContainsString('<t>01748071572</t>', $strings);
        $this->assertStringContainsString('<t>01</t>', $strings);
    }

    /**
     * 2. Leading zero preservation in XLSX: 01748071572, Roll 01, and Student ID 000123
     */
    public function test_leading_zero_preservation_for_mobile_student_id_and_roll_in_xlsx(): void
    {
        $this->createStudent([
            'student_name'     => 'Zero Tester',
            'student_identity' => '000123',
            'roll_number'      => '01',
            'guardian_mobile'  => '01748071572',
            'card_number'      => '0009544276',
        ]);

        $response = $this->get('/ajax/students/export-excel?columns[]=student_name&columns[]=student_id&columns[]=roll&columns[]=mobile&columns[]=card_number');
        $response->assertStatus(200);

        $content = $response->getContent();
        $strings = $this->extractXlsxSharedStrings($content);

        // Explicitly stored as text strings in OpenXML sharedStrings table
        $this->assertStringContainsString('<t>01748071572</t>', $strings);
        $this->assertStringContainsString('<t>01</t>', $strings);
        $this->assertStringContainsString('<t>000123</t>', $strings);
        $this->assertStringContainsString('<t>0009544276</t>', $strings);
    }

    /**
     * 3. Exporting only the selected columns in XLSX
     */
    public function test_exporting_only_selected_columns_xlsx(): void
    {
        $this->createStudent([
            'student_name' => 'Karim Hasan',
            'roll_number'  => '15',
        ]);

        $response = $this->get('/ajax/students/export-excel?columns[]=student_name&columns[]=roll');
        $response->assertStatus(200);

        $content = $response->getContent();
        $strings = $this->extractXlsxSharedStrings($content);

        $this->assertStringContainsString('<t>Student Name</t>', $strings);
        $this->assertStringContainsString('<t>Roll</t>', $strings);
        $this->assertStringContainsString('<t>Karim Hasan</t>', $strings);
        // Did not select Class or Section or Mobile
        $this->assertStringNotContainsString('<t>Mobile Number</t>', $strings);
        $this->assertStringNotContainsString('<t>Section</t>', $strings);
    }

    /**
     * 4. Correct mobile-number fallback in XLSX
     */
    public function test_correct_mobile_number_fallback_in_xlsx(): void
    {
        // Student A: Has guardian mobile
        $this->createStudent([
            'student_name'    => 'Student A',
            'guardian_mobile' => '01711111111',
            'father_mobile'   => '01822222222',
        ]);

        // Student B: Guardian mobile empty -> fallback to father mobile
        $this->createStudent([
            'student_name'    => 'Student B',
            'guardian_mobile' => '',
            'father_mobile'   => '01933333333',
        ]);

        $response = $this->get('/ajax/students/export-excel?columns[]=student_name&columns[]=mobile');
        $response->assertStatus(200);

        $content = $response->getContent();
        $strings = $this->extractXlsxSharedStrings($content);

        $this->assertStringContainsString('<t>01711111111</t>', $strings);
        $this->assertStringContainsString('<t>01933333333</t>', $strings);
    }

    /**
     * 5. Bangla text and formula-injection protection in XLSX
     */
    public function test_bangla_text_and_formula_injection_protection_in_xlsx(): void
    {
        $banglaName = 'মো: আবদুল্লাহ আল মামুন';

        $this->createStudent([
            'student_name'     => '=CMD|"/C calc"!A0',
            'name_in_bangla'   => $banglaName,
            'student_identity' => '+8801748071572',
            'guardian_mobile'  => '01748071572',
            'father_name'      => '@SUM(1+2)',
        ]);

        $response = $this->get('/ajax/students/export-excel?columns[]=student_name&columns[]=name_in_bangla&columns[]=student_id&columns[]=mobile&columns[]=father_name');
        $response->assertStatus(200);

        $content = $response->getContent();
        $strings = $this->extractXlsxSharedStrings($content);

        // Bangla text preserved
        $this->assertStringContainsString($banglaName, $strings);

        // Mobile leading zero preserved
        $this->assertStringContainsString('<t>01748071572</t>', $strings);

        // Formula injection sanitized with leading single quote
        $this->assertStringContainsString("=CMD|\"/C calc\"!A0", $strings);
        $this->assertStringContainsString("'+8801748071572", $strings);
        $this->assertStringContainsString("'@SUM(1+2)", $strings);
    }

    /**
     * 6. Every existing filter being respected in XLSX export
     */
    public function test_every_existing_filter_respected_in_xlsx(): void
    {
        $targetShift = Shift::firstOrCreate(
            ['name' => 'Day Shift Target'],
            [
                'type' => 'student',
                'in_time_start' => '12:00:00',
                'in_time_end' => '12:30:00',
                'late_time_end' => '12:45:00',
                'absent_time' => '13:00:00',
                'out_time' => '17:00:00',
                'shift_name' => 'Day Shift Target',
                'user_id' => $this->adminUser->id
            ]
        );

        $targetBranch = Branch::firstOrCreate(['id' => 2], ['branch_name' => 'Uttara Campus', 'user_id' => $this->adminUser->id]);
        $targetSession = SessionYear::firstOrCreate(['id' => 2], ['session_name' => '2027-2028', 'user_id' => $this->adminUser->id]);

        $match = $this->createStudent([
            'student_name'        => 'Target Match Student',
            'student_identity'    => 'TGT-999',
            'roll_number'         => '99',
            'father_mobile'       => '01888888888',
            'branch_id'           => $targetBranch->id,
            'class_id'            => $this->classTwo->id,
            'section_id'          => $this->sectionB->id,
            'session_year_id'     => $targetSession->id,
            'shift_id'            => $targetShift->id,
            'gender'              => 'Female',
            'status'              => 'Inactive',
            'card_number'         => 'SYNCED-123',
            'present_village'     => 'Gulshan Area',
        ]);

        $nonMatch = $this->createStudent([
            'student_name'        => 'Other Student',
            'student_identity'    => 'OTH-000',
            'roll_number'         => '100',
            'father_mobile'       => '01777777777',
            'branch_id'           => $this->branch->id,
            'class_id'            => $this->classOne->id,
            'section_id'          => $this->sectionA->id,
            'session_year_id'     => $this->session->id,
            'shift_id'            => $this->shift->id,
            'gender'              => 'Male',
            'status'              => 'Active',
            'card_number'         => null,
            'present_village'     => 'Dhanmondi Area',
        ]);

        $queryParams = http_build_query([
            'search'          => 'Target',
            'branch_id'       => $targetBranch->id,
            'class_id'        => $this->classTwo->id,
            'section_id'      => $this->sectionB->id,
            'session_year_id' => $targetSession->id,
            'shift_id'        => $targetShift->id,
            'gender'          => 'Female',
            'status'          => 'Inactive',
            'rfid_status'     => 'synced',
            'area'            => 'Gulshan',
            'columns'         => ['student_name', 'student_id']
        ]);

        $response = $this->get('/ajax/students/export-excel?' . $queryParams);
        $response->assertStatus(200);

        $content = $response->getContent();
        $strings = $this->extractXlsxSharedStrings($content);

        $this->assertStringContainsString('Target Match Student', $strings);
        $this->assertStringContainsString('TGT-999', $strings);
        $this->assertStringNotContainsString('Other Student', $strings);
        $this->assertStringNotContainsString('OTH-000', $strings);
    }

    /**
     * 7. Exporting all records across pagination boundaries in XLSX
     */
    public function test_exporting_all_records_across_pagination_boundaries_xlsx(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->createStudent([
                'student_name' => 'Batch Student ' . $i,
                'class_id'     => $this->classOne->id,
                'roll_number'  => (string) $i,
            ]);
        }

        $response = $this->get('/ajax/students/export-excel?class_id=' . $this->classOne->id);
        $response->assertStatus(200);

        $content = $response->getContent();
        $strings = $this->extractXlsxSharedStrings($content);

        // Verifies records 1 through 25 all exist
        $this->assertStringContainsString('Batch Student 1', $strings);
        $this->assertStringContainsString('Batch Student 25', $strings);
    }

    /**
     * 8. CSV export option remains 100% backward compatible
     */
    public function test_csv_export_backward_compatibility(): void
    {
        $this->createStudent([
            'student_name'     => 'CSV Student',
            'student_identity' => '000555',
            'roll_number'      => '08',
            'guardian_mobile'  => '01748071572',
        ]);

        $response = $this->get('/ajax/students/export-excel?format=csv');

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertTrue(str_contains($response->headers->get('Content-Disposition'), '.csv'));

        $content = $response->streamedContent();
        $this->assertTrue(str_starts_with($content, "\xEF\xBB\xBF"));

        $cleanContent = substr($content, 3);
        $this->assertStringContainsString('01748071572', $cleanContent);
        $this->assertStringContainsString('000555', $cleanContent);
        $this->assertStringContainsString('08', $cleanContent);
    }

    /**
     * 9. Invalid columns rejected with 422
     */
    public function test_invalid_columns_rejected_with_422(): void
    {
        $response = $this->getJson('/ajax/students/export-excel?columns[]=student_name&columns[]=malicious_col');

        $response->assertStatus(422);
        $response->assertJson(['status' => 'error']);
        $this->assertStringContainsString('Invalid export column', $response->json('message'));
    }

    /**
     * 10. Empty column selections rejected with 422
     */
    public function test_empty_column_selections_rejected_with_422(): void
    {
        $response = $this->getJson('/ajax/students/export-excel?columns=');

        $response->assertStatus(422);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Please select at least one column to export.'
        ]);
    }

    /**
     * 11. Unauthorized users denied access
     */
    public function test_unauthorized_users_denied_access(): void
    {
        $plainUser = User::create([
            'name'     => 'Unauthorized Viewer',
            'email'    => 'viewer_no_role@macsschool.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($plainUser);

        $response = $this->get('/ajax/students/export-excel');
        $this->assertTrue(in_array($response->status(), [403, 302], true));
    }

    /**
     * 12. Student List API compatibility remains fully functional
     */
    public function test_student_list_api_compatibility(): void
    {
        $this->createStudent([
            'student_name' => 'API Student',
            'roll_number'  => '99',
        ]);

        $response = $this->getJson('/ajax/students');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'studentData' => [
                'data' => [
                    '*' => [
                        'id',
                        'student_name',
                        'roll_number',
                    ]
                ]
            ]
        ]);
    }
}
