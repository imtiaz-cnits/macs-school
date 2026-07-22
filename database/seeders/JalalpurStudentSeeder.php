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
use Illuminate\Support\Facades\File;

class JalalpurStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/jalalpur_students.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("JSON data file not found at: {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $studentsData = json_decode($json, true);

        if (!$studentsData) {
            $this->command->error("Failed to parse JSON student data.");
            return;
        }

        // Get or create first user as author
        $admin = User::first() ?? User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Branch
        $branch = Branch::firstOrCreate(
            ['branch_name' => 'Jalalpur Branch'],
            ['user_id' => $admin->id]
        );

        // Shift
        $shift = Shift::firstOrCreate(
            ['shift_name' => 'Day Student'],
            ['user_id' => $admin->id]
        );

        // Session Year
        $sessionYear = SessionYear::firstOrCreate(
            ['session_name' => '2026'],
            ['user_id' => $admin->id]
        );

        // Section A
        $section = Section::firstOrCreate(
            ['section_name' => 'Section A'],
            ['user_id' => $admin->id]
        );

        $classMapping = [
            'প্লে' => 'Play',
            'নার্সারি' => 'Nursery',
            'প্রথম' => 'One',
            'দ্বিতীয়' => 'Two',
            'তৃতীয়' => 'Three',
            'চতুর্থ' => 'Four',
            'পঞ্চম' => 'Five',
            'ষষ্ঠ' => 'Six',
            'সপ্তম' => 'Seven',
            'অষ্টম' => 'Eight',
            'নবম' => 'Nine',
            'দশম' => 'Ten',
        ];

        $genderMapping = [
            'ছাত্র' => 'Male',
            'ছাত্রী' => 'Female',
        ];

        $insertedCount = 0;

        foreach ($studentsData as $data) {
            $rawClass = trim($data['Class'] ?? '');
            $className = $classMapping[$rawClass] ?? 'Play';

            $class = Classes::firstOrCreate(
                ['class_name' => $className],
                ['user_id' => $admin->id]
            );

            $rawGender = trim($data['Gender'] ?? '');
            $gender = $genderMapping[$rawGender] ?? 'Male';

            $studentIdentity = trim($data['Student ID'] ?? '');
            if (empty($studentIdentity)) {
                $studentIdentity = 'JL' . date('YmdHis') . rand(100, 999);
            }

            // Mobile number formatting: take the first one if multiple are separated by comma
            $rawMobile = trim($data['Mobile number'] ?? '');
            $mobiles = explode(',', $rawMobile);
            $primaryMobile = trim($mobiles[0] ?? '');
            // Clean dashes and spaces
            $primaryMobile = str_replace(['-', ' '], '', $primaryMobile);

            if (empty($primaryMobile)) {
                $primaryMobile = '01700000000';
            }

            $address = trim($data['Address'] ?? '');

            Student::updateOrCreate(
                ['student_identity' => $studentIdentity],
                [
                    'roll_number' => intval($data['Roll'] ?? 1),
                    'student_name' => trim($data['Student Name(English)'] ?? 'N/A'),
                    'name_in_bangla' => trim($data['Student Name(Bangla)'] ?? 'N/A'),
                    'birth_certificate' => 'BR' . rand(10000000, 99999999),
                    'blood_group' => 'O+',
                    'religion' => 'Islam',
                    'dob' => '2015-01-01',
                    'gender' => $gender,
                    'email' => strtolower(str_replace(' ', '', trim($data['Student Name(English)'] ?? ''))) . rand(10, 99) . '@example.com',
                    
                    'father_name' => trim($data["Father's Name(English)"] ?? ''),
                    'father_name_bn' => trim($data["Father's Name"] ?? ''),
                    'father_mobile' => $primaryMobile,
                    'father_occupation' => 'Occupation',
                    
                    'mother_name' => trim($data['Mother Name(English)'] ?? ''),
                    'mother_name_bn' => trim($data['Mother Name'] ?? ''),
                    'mother_mobile' => $primaryMobile,
                    'mother_occupation' => 'Housewife',
                    
                    'present_village' => $address ?: 'Jalalpur',
                    'present_post_office' => 'Jalalpur',
                    'present_district' => 'Pabna',
                    'present_division' => 'Rajshahi',
                    'present_post_code' => '6600',
                    
                    'permanent_village' => $address ?: 'Jalalpur',
                    'permanent_post_office' => 'Jalalpur',
                    'permanent_district' => 'Pabna',
                    'permanent_division' => 'Rajshahi',
                    'permanent_post_code' => '6600',
                    
                    'guardian_name' => trim($data["Father's Name(English)"] ?? '') ?: trim($data['Mother Name(English)'] ?? '') ?: 'Guardian',
                    'guardian_mobile' => $primaryMobile,
                    'guardian_occupation' => 'Occupation',
                    
                    'sms_status' => 'Active',
                    'photo' => $gender === 'Male' ? 'img/boy.png' : 'img/girl.png',
                    'branch_id' => $branch->id,
                    'class_id' => $class->id,
                    'section_id' => $section->id,
                    'shift_id' => $shift->id,
                    'session_year_id' => $sessionYear->id,
                    'user_id' => $admin->id,
                ]
            );

            $insertedCount++;
        }

        $this->command->info("Successfully seeded {$insertedCount} students from Jalalpur Branch CSV!");
    }
}
