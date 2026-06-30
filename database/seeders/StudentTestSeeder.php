<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Shift;
use App\Models\SessionYear;
use App\Models\User;

class StudentTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $branch = Branch::first() ?? Branch::create(['branch_name' => 'Main Branch', 'user_id' => $user->id]);
        $class = Classes::first() ?? Classes::create(['class_name' => 'Class One', 'user_id' => $user->id]);
        $section = Section::first() ?? Section::create(['section_name' => 'Section A', 'user_id' => $user->id]);
        $shift = Shift::first() ?? Shift::create(['shift_name' => 'Morning Shift', 'user_id' => $user->id]);
        $session = SessionYear::first() ?? SessionYear::create(['session_name' => '2026', 'user_id' => $user->id]);

        $names = [
            'Arafat Rahman', 'Tasnim Ahmed', 'Jannatul Ferdous', 'Sadia Islam', 'Nayeem Hasan',
            'Fahim Chowdhury', 'Mehadi Hasan', 'Anika Tabassum', 'Sumaiya Yasmin', 'Sabbir Hossain',
            'Rakib Khan', 'Mim Akter', 'Farhan Zaman', 'Tisha Rahman', 'Rifat Islam',
            'Nabila Yeasmin', 'Imtiaz Ahmed', 'Fariha Kabir', 'Tanvir Rahman', 'Mitu Akter',
            'Asif Iqbal', 'Shahed Alam', 'Mitu Chowdhury', 'Riyad Hasan', 'Lamiyea Islam',
            'Kamrul Hasan', 'Fiza Rahman', 'Arifur Rahman', 'Tasnuva Ahmed', 'Sakib Al Hasan'
        ];

        $genders = [
            'Male', 'Female', 'Female', 'Female', 'Male',
            'Male', 'Male', 'Female', 'Female', 'Male',
            'Male', 'Female', 'Male', 'Female', 'Male',
            'Female', 'Male', 'Female', 'Male', 'Female',
            'Male', 'Male', 'Female', 'Male', 'Female',
            'Male', 'Female', 'Male', 'Female', 'Male'
        ];

        for ($i = 0; $i < 30; $i++) {
            $gender = $genders[$i];
            $photoPath = $gender === 'Male' ? 'img/boy.png' : 'img/girl.png';
            
            Student::create([
                'student_identity' => 'STD2026' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'roll_number' => $i + 1,
                'student_name' => $names[$i],
                'name_in_bangla' => $names[$i] . ' (Bangla)',
                'birth_certificate' => 'BR' . rand(10000000, 99999999),
                'blood_group' => ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'][rand(0, 7)],
                'religion' => 'Islam',
                'dob' => '2015-05-12',
                'gender' => $gender,
                'email' => strtolower(str_replace(' ', '', $names[$i])) . '@example.com',
                
                'father_name' => 'Father of ' . $names[$i],
                'father_nid' => 'FNID' . rand(10000000, 99999999),
                'father_mobile' => '017' . rand(10000000, 99999999),
                'father_occupation' => 'Business',
                
                'mother_name' => 'Mother of ' . $names[$i],
                'mother_nid' => 'MNID' . rand(10000000, 99999999),
                'mother_mobile' => '018' . rand(10000000, 99999999),
                'mother_occupation' => 'Housewife',
                
                'present_village' => 'Dhaka',
                'present_post_office' => 'Dhaka',
                'present_district' => 'Dhaka',
                'present_division' => 'Dhaka',
                'present_post_code' => '1200',
                
                'permanent_village' => 'Dhaka',
                'permanent_post_office' => 'Dhaka',
                'permanent_district' => 'Dhaka',
                'permanent_division' => 'Dhaka',
                'permanent_post_code' => '1200',
                
                'guardian_name' => 'Guardian of ' . $names[$i],
                'guardian_occupation' => 'Business',
                'guardian_mobile' => '019' . rand(10000000, 99999999),
                
                'sms_status' => 'Active',
                'photo' => $photoPath,
                'branch_id' => $branch->id,
                'class_id' => $class->id,
                'section_id' => $section->id,
                'shift_id' => $shift->id,
                'session_year_id' => $session->id,
                'user_id' => $user->id,
            ]);
        }
    }
}
