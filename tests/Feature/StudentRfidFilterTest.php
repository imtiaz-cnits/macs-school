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
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentRfidFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function createStudentWithCard(?string $card, string $name = 'Test Student'): Student
    {
        $user = User::firstOrCreate(
            ['email' => 'admin_filter@macsschool.com'],
            ['name' => 'Admin Filter', 'password' => bcrypt('secret')]
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
            'card_number'            => $card,
            'student_name'           => $name,
            'roll_number'            => (string) rand(1, 999),
            'dob'                    => '2015-01-01',
            'gender'                 => 'Male',
            'father_name'            => 'Father',
            'father_mobile'          => '01711111111',
            'mother_name'            => 'Mother',
            'mother_mobile'          => '01722222222',
            'guardian_name'          => 'Guardian',
            'guardian_mobile'        => '01733333333',
            'branch_id'              => $branch->id,
            'session_year_id'        => $session->id,
            'class_id'               => $class->id,
            'section_id'             => $section->id,
            'shift_id'               => $shift->id,
            'status'                 => 'Active',
            'present_village'        => 'Village',
            'present_post_office'    => 'PO',
            'present_district'       => 'District',
            'present_division'       => 'Division',
            'permanent_village'      => 'Village',
            'permanent_post_office'  => 'PO',
            'permanent_district'     => 'District',
            'permanent_division'     => 'Division',
        ]);
    }

    public function test_filter_synced_returns_only_students_with_valid_card(): void
    {
        $this->withoutMiddleware();

        $synced = $this->createStudentWithCard('0009544276', 'Synced Student');
        $notSyncedNull = $this->createStudentWithCard(null, 'No Card Null Student');
        $notSyncedEmpty = $this->createStudentWithCard('', 'No Card Empty Student');
        $notSyncedNA = $this->createStudentWithCard('N/A', 'No Card NA Student');

        $response = $this->getJson('/ajax/students?rfid_status=synced');

        $response->assertStatus(200);
        $data = $response->json('studentData.data');
        $ids = collect($data)->pluck('id')->all();

        $this->assertContains($synced->id, $ids);
        $this->assertNotContains($notSyncedNull->id, $ids);
        $this->assertNotContains($notSyncedEmpty->id, $ids);
        $this->assertNotContains($notSyncedNA->id, $ids);
    }

    public function test_filter_not_synced_returns_only_students_without_card(): void
    {
        $this->withoutMiddleware();

        $synced = $this->createStudentWithCard('0009544276', 'Synced Student');
        $notSyncedNull = $this->createStudentWithCard(null, 'No Card Null Student');
        $notSyncedEmpty = $this->createStudentWithCard('', 'No Card Empty Student');
        $notSyncedNA = $this->createStudentWithCard('N/A', 'No Card NA Student');

        $response = $this->getJson('/ajax/students?rfid_status=not_synced');

        $response->assertStatus(200);
        $data = $response->json('studentData.data');
        $ids = collect($data)->pluck('id')->all();

        $this->assertNotContains($synced->id, $ids);
        $this->assertContains($notSyncedNull->id, $ids);
        $this->assertContains($notSyncedEmpty->id, $ids);
        $this->assertContains($notSyncedNA->id, $ids);
    }

    public function test_no_rfid_filter_returns_all_students(): void
    {
        $this->withoutMiddleware();

        $synced = $this->createStudentWithCard('0009544276', 'Synced Student');
        $notSynced = $this->createStudentWithCard(null, 'No Card Null Student');

        $response = $this->getJson('/ajax/students');

        $response->assertStatus(200);
        $data = $response->json('studentData.data');
        $ids = collect($data)->pluck('id')->all();

        $this->assertContains($synced->id, $ids);
        $this->assertContains($notSynced->id, $ids);
    }
}
