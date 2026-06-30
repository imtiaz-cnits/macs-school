<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;

class RoutineTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get the admin/first user for creator / user_id fallback
        $admin = User::first() ?? User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Seed Subjects if they don't exist
        $subjectsData = [
            ['subject_name' => 'English', 'subject_code' => 'ENG-101', 'subject_type' => 'Theory'],
            ['subject_name' => 'Mathematics', 'subject_code' => 'MAT-102', 'subject_type' => 'Theory'],
            ['subject_name' => 'Physics', 'subject_code' => 'PHY-103', 'subject_type' => 'Theory'],
            ['subject_name' => 'Chemistry', 'subject_code' => 'CHE-104', 'subject_type' => 'Theory'],
            ['subject_name' => 'ICT', 'subject_code' => 'ICT-105', 'subject_type' => 'Theory'],
            ['subject_name' => 'Biology', 'subject_code' => 'BIO-106', 'subject_type' => 'Theory'],
        ];

        foreach ($subjectsData as $sub) {
            Subject::firstOrCreate(
                ['subject_code' => $sub['subject_code']],
                [
                    'subject_name' => $sub['subject_name'],
                    'subject_type' => $sub['subject_type'],
                    'status' => 'Active',
                    'user_id' => $admin->id
                ]
            );
        }

        // 3. Seed Teachers and User accounts
        $teachersData = [
            [
                'name' => 'Dr. Rahman Khan',
                'email' => 'rahman@school.com',
                'employee_id' => 'TEA-2026-001',
                'designation' => 'Senior Lecturer',
                'department' => 'Science',
                'phone' => '01711223344',
                'gender' => 'Male',
            ],
            [
                'name' => 'Jannatul Ferdous',
                'email' => 'jannat@school.com',
                'employee_id' => 'TEA-2026-002',
                'designation' => 'Assistant Teacher',
                'department' => 'English',
                'phone' => '01811223344',
                'gender' => 'Female',
            ],
            [
                'name' => 'Kamrul Hasan',
                'email' => 'kamrul@school.com',
                'employee_id' => 'TEA-2026-003',
                'designation' => 'Senior Teacher',
                'department' => 'Mathematics',
                'phone' => '01911223344',
                'gender' => 'Male',
            ],
            [
                'name' => 'Tasnim Ahmed',
                'email' => 'tasnim@school.com',
                'employee_id' => 'TEA-2026-004',
                'designation' => 'Lecturer',
                'department' => 'ICT',
                'phone' => '01611223344',
                'gender' => 'Female',
            ]
        ];

        foreach ($teachersData as $teacher) {
            // Find or create User
            $user = User::firstOrCreate(
                ['email' => $teacher['email']],
                [
                    'name' => $teacher['name'],
                    'password' => bcrypt('password'),
                ]
            );

            // Find or create Teacher profile
            Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_id' => $teacher['employee_id'],
                    'designation' => $teacher['designation'],
                    'department' => $teacher['department'],
                    'phone' => $teacher['phone'],
                    'address' => 'Pabna School St, Pabna',
                    'gender' => $teacher['gender'],
                    'joining_date' => '2026-01-01',
                    'created_by' => $admin->id
                ]
            );
        }
    }
}
