<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Mark;
use App\Models\Grade;
use App\Models\Student;
use App\Models\ExamSchedule;

class ClassSixMarksSeeder extends Seeder
{
    /**
     * Run the database seeds for Class Six Marks (2nd Term Exam 2026).
     */
    public function run(): void
    {
        $sessionYearId = 1; // 2026
        $branchId = 3;      // Jalalpur Branch
        $examId = 2;        // 2nd Term Exam 2026
        $classId = 8;       // Six

        $subjects = [
            'bangla_1'    => 55, // বাংলা ১ম পত্র
            'bangla_2'    => 56, // বাংলা ২য় পত্র
            'english_1'   => 57, // ইংরেজী ১ম পত্র
            'english_2'   => 58, // ইংরেজী ২য় পত্র
            'math'        => 59, // গণিত
            'religion'    => 60, // ইসলাম শিক্ষা
            'science'     => 61, // বিজ্ঞান
            'bgs'         => 62, // বাংলাদেশ ও বিশ্বপরিচয়
            'ict'         => 63, // তথ্য ও যোগাযোগ প্রযুক্তি
            'agriculture' => 64, // কৃষি শিক্ষা
        ];

        // Ensure Exam Schedules exist for Class Six
        $scheduleConfigs = [
            55 => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            56 => ['full' => 50,  'pass' => 17, 'ct' => 10, 'mcq' => 10, 'written' => 30],
            57 => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            58 => ['full' => 50,  'pass' => 17, 'ct' => 10, 'mcq' => 10, 'written' => 30],
            59 => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            60 => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            61 => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            62 => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            63 => ['full' => 50,  'pass' => 17, 'ct' => 5,  'mcq' => 15, 'written' => 30],
            64 => ['full' => 50,  'pass' => 17, 'ct' => 10, 'mcq' => 10, 'written' => 30],
        ];

        foreach ($scheduleConfigs as $subId => $cfg) {
            ExamSchedule::firstOrCreate(
                [
                    'branch_id'  => $branchId,
                    'class_id'   => $classId,
                    'subject_id' => $subId,
                    'exam_id'    => $examId,
                ],
                [
                    'full_marks'    => $cfg['full'],
                    'pass_marks'    => $cfg['pass'],
                    'ct_marks'      => $cfg['ct'],
                    'written_marks' => $cfg['written'],
                    'mcq_marks'     => $cfg['mcq'],
                ]
            );
        }

        // Ensure students for Roll 55 and 56 exist in Class Six
        Student::firstOrCreate(
            [
                'class_id'    => $classId,
                'roll_number' => '55',
            ],
            [
                'student_identity'    => '26050600374',
                'student_name'        => 'Md. Nazat Aleem',
                'name_in_bangla'      => 'মোঃ নাজাত আলীম',
                'branch_id'           => $branchId,
                'section_id'          => 1,
                'shift_id'            => 5,
                'session_year_id'     => $sessionYearId,
                'dob'                 => '2015-01-01',
                'gender'              => 'Male',
                'religion'            => 'Islam',
                'photo'               => 'img/boy.png',
                'father_name'         => 'Father',
                'father_mobile'       => '01708118159',
                'mother_name'         => 'Mother',
                'mother_mobile'       => '01708118159',
                'guardian_name'       => 'Guardian',
                'guardian_mobile'     => '01708118159',
                'present_village'     => 'Jalalpur',
                'present_post_office' => 'Jalalpur',
                'present_district'    => 'Pabna',
                'present_post_code'   => '6600',
                'present_division'    => 'Rajshahi',
                'permanent_village'   => 'Jalalpur',
                'permanent_post_office' => 'Jalalpur',
                'permanent_district'  => 'Pabna',
                'permanent_post_code' => '6600',
                'permanent_division'  => 'Rajshahi',
                'sms_status'          => 'Active',
                'user_id'             => 1,
            ]
        );

        Student::firstOrCreate(
            [
                'class_id'    => $classId,
                'roll_number' => '56',
            ],
            [
                'student_identity'    => '26050600375',
                'student_name'        => 'Ali Walid',
                'name_in_bangla'      => 'আলী ওয়ায়লিদ',
                'branch_id'           => $branchId,
                'section_id'          => 1,
                'shift_id'            => 5,
                'session_year_id'     => $sessionYearId,
                'dob'                 => '2015-01-01',
                'gender'              => 'Male',
                'religion'            => 'Islam',
                'photo'               => 'img/boy.png',
                'father_name'         => 'Father',
                'father_mobile'       => '01708118159',
                'mother_name'         => 'Mother',
                'mother_mobile'       => '01708118159',
                'guardian_name'       => 'Guardian',
                'guardian_mobile'     => '01708118159',
                'present_village'     => 'Jalalpur',
                'present_post_office' => 'Jalalpur',
                'present_district'    => 'Pabna',
                'present_post_code'   => '6600',
                'present_division'    => 'Rajshahi',
                'permanent_village'   => 'Jalalpur',
                'permanent_post_office' => 'Jalalpur',
                'permanent_district'  => 'Pabna',
                'permanent_post_code' => '6600',
                'permanent_division'  => 'Rajshahi',
                'sms_status'          => 'Active',
                'user_id'             => 1,
            ]
        );

        // Student marks dataset: [CT, MT]
        $studentMarks = [
            1  => [
                'bangla_1' => [8, 16], 'bangla_2' => [4, 9], 'english_1' => [10, 20], 'english_2' => [5, 10], 'math' => [7, 19],
                'religion' => [9, 18], 'science' => [10, 18], 'bgs' => [10, 16], 'ict' => [5, 10], 'agriculture' => [5, 9]
            ],
            2  => [
                'bangla_1' => [0, 13], 'bangla_2' => [0, 6], 'english_1' => [0, 17], 'english_2' => [0, 8], 'math' => [0, 14],
                'religion' => [0, 15], 'science' => [0, 19], 'bgs' => [0, 16], 'ict' => [5, 10], 'agriculture' => [5, 9]
            ],
            3  => [
                'bangla_1' => [8, 15], 'bangla_2' => [4, 8], 'english_1' => [10, 16], 'english_2' => [5, 5], 'math' => [6, 18],
                'religion' => [6, 15], 'science' => [0, 14], 'bgs' => [10, 13], 'ict' => [5, 10], 'agriculture' => [5, 8]
            ],
            4  => [
                'bangla_1' => [10, 17], 'bangla_2' => [4, 9], 'english_1' => [8, 19], 'english_2' => [5, 6], 'math' => [7, 10],
                'religion' => [8, 17], 'science' => [10, 18], 'bgs' => [10, 15], 'ict' => [5, 10], 'agriculture' => [5, 6]
            ],
            5  => [
                'bangla_1' => [10, 18], 'bangla_2' => [4, 9], 'english_1' => [8, 16], 'english_2' => [5, 7], 'math' => [3, 13],
                'religion' => [8, 17], 'science' => [9, 17], 'bgs' => [10, 14], 'ict' => [5, 10], 'agriculture' => [5, 9]
            ],
            6  => [
                'bangla_1' => [9, 17], 'bangla_2' => [4, 9], 'english_1' => [10, 20], 'english_2' => [5, 6], 'math' => [8, 20],
                'religion' => [9, 15], 'science' => [10, 17], 'bgs' => [10, 18], 'ict' => [5, 9], 'agriculture' => [5, 10]
            ],
            7  => [
                'bangla_1' => [7, 11], 'bangla_2' => [4, 7], 'english_1' => [10, 12], 'english_2' => [5, 3], 'math' => [8, 8],
                'religion' => [9, 9], 'science' => [10, 7], 'bgs' => [10, 9], 'ict' => [5, 10], 'agriculture' => [5, 4]
            ],
            8  => [
                'bangla_1' => [6, 11], 'bangla_2' => [4, 8], 'english_1' => [10, 19], 'english_2' => [5, 3], 'math' => [5, 15],
                'religion' => [9, 13], 'science' => [10, 12], 'bgs' => [10, 14], 'ict' => [5, 9], 'agriculture' => [5, 7]
            ],
            9  => [
                'bangla_1' => [8, 11], 'bangla_2' => [4, 8], 'english_1' => [10, 10], 'english_2' => [5, 4], 'math' => [6, 13],
                'religion' => [9, 15], 'science' => [8, 9], 'bgs' => [9, 11], 'ict' => [5, 5], 'agriculture' => [5, 6]
            ],
            10 => [
                'bangla_1' => [3, 10], 'bangla_2' => [4, 4], 'english_1' => [8, 7], 'english_2' => [5, 4], 'math' => [4, 14],
                'religion' => [5, 12], 'science' => [9, 9], 'bgs' => [10, 11], 'ict' => [5, 5], 'agriculture' => [5, 4]
            ],
            11 => [
                'bangla_1' => [7, 11], 'bangla_2' => [4, 6], 'english_1' => [10, 9], 'english_2' => [5, 4], 'math' => [7, 10],
                'religion' => [5, 14], 'science' => [10, 11], 'bgs' => [6, 10], 'ict' => [5, 10], 'agriculture' => [5, 6]
            ],
            12 => [
                'bangla_1' => [4, 9], 'bangla_2' => [4, 4], 'english_1' => [10, 9], 'english_2' => [5, 3], 'math' => [8, 6],
                'religion' => [7, 13], 'science' => [9, 6], 'bgs' => [6, 7], 'ict' => [5, 5], 'agriculture' => [5, 4]
            ],
            13 => [
                'bangla_1' => [5, 12], 'bangla_2' => [4, 4], 'english_1' => [8, 13], 'english_2' => [5, 3], 'math' => [5, 13],
                'religion' => [4, 11], 'science' => [9, 12], 'bgs' => [6, 10], 'ict' => [5, 4], 'agriculture' => [5, 3]
            ],
            14 => [
                'bangla_1' => [5, 13], 'bangla_2' => [5, 6], 'english_1' => [5, 13], 'english_2' => [3, 1], 'math' => [5, 9],
                'religion' => [0, 12], 'science' => [0, 9], 'bgs' => [0, 6], 'ict' => [5, 6], 'agriculture' => [5, 3]
            ],
            15 => [
                'bangla_1' => [8, 11], 'bangla_2' => [4, 5], 'english_1' => [7, 6], 'english_2' => [4, 1], 'math' => [4, 9],
                'religion' => [6, 4], 'science' => [10, 11], 'bgs' => [5, 5], 'ict' => [5, 1], 'agriculture' => [5, 2]
            ],
            16 => [
                'bangla_1' => [3, 8], 'bangla_2' => [4, 5], 'english_1' => [5, 9], 'english_2' => [5, 1], 'math' => [0, 10],
                'religion' => [4, 11], 'science' => [7, 9], 'bgs' => [0, 4], 'ict' => [5, 0], 'agriculture' => [5, 2]
            ],
            17 => [
                'bangla_1' => [6, 9], 'bangla_2' => [4, 5], 'english_1' => [6, 8], 'english_2' => [5, 1], 'math' => [7, 10],
                'religion' => [8, 9], 'science' => [10, 7], 'bgs' => [6, 7], 'ict' => [5, 7], 'agriculture' => [5, 4]
            ],
            18 => [
                'bangla_1' => [6, 11], 'bangla_2' => [4, 4], 'english_1' => [8, 11], 'english_2' => [5, 1], 'math' => [7, 8],
                'religion' => [6, 11], 'science' => [10, 9], 'bgs' => [6, 9], 'ict' => [5, 4], 'agriculture' => [5, 4]
            ],
            19 => [
                'bangla_1' => [6, 9], 'bangla_2' => [5, 5], 'english_1' => [10, 10], 'english_2' => [5, 2], 'math' => [8, 3],
                'religion' => [5, 11], 'science' => [8, 11], 'bgs' => [9, 8], 'ict' => [5, 4], 'agriculture' => [5, 5]
            ],
            20 => [
                'bangla_1' => [5, 14], 'bangla_2' => [4, 5], 'english_1' => [10, 13], 'english_2' => [5, 0], 'math' => [4, 7],
                'religion' => [9, 7], 'science' => [10, 10], 'bgs' => [10, 9], 'ict' => [5, 4], 'agriculture' => [5, 5]
            ],
            21 => [
                'bangla_1' => [3, 11], 'bangla_2' => [5, 6], 'english_1' => [4, 11], 'english_2' => [5, 1], 'math' => [0, 2],
                'religion' => [1, 12], 'science' => [9, 9], 'bgs' => [2, 10], 'ict' => [5, 4], 'agriculture' => [5, 3]
            ],
            22 => [
                'bangla_1' => [8, 15], 'bangla_2' => [4, 7], 'english_1' => [10, 10], 'english_2' => [5, 1], 'math' => [7, 5],
                'religion' => [6, 7], 'science' => [9, 13], 'bgs' => [10, 11], 'ict' => [5, 4], 'agriculture' => [5, 5]
            ],
            23 => [
                'bangla_1' => [5, 14], 'bangla_2' => [4, 7], 'english_1' => [9, 8], 'english_2' => [2, 1], 'math' => [0, 5],
                'religion' => [2, 11], 'science' => [8, 12], 'bgs' => [1, 9], 'ict' => [5, 2], 'agriculture' => [5, 4]
            ],
            24 => [
                'bangla_1' => [3, 11], 'bangla_2' => [1, 3], 'english_1' => [9, 6], 'english_2' => [1, 1], 'math' => [1, 5],
                'religion' => [6, 7], 'science' => [7, 7], 'bgs' => [1, 8], 'ict' => [5, 2], 'agriculture' => [5, 2]
            ],
            25 => [
                'bangla_1' => [3, 9], 'bangla_2' => [4, 3], 'english_1' => [10, 6], 'english_2' => [5, 1], 'math' => [5, 7],
                'religion' => [4, 6], 'science' => [7, 7], 'bgs' => [4, 6], 'ict' => [5, 4], 'agriculture' => [5, 2]
            ],
            26 => [
                'bangla_1' => [2, 5], 'bangla_2' => [2, 2], 'english_1' => [8, 4], 'english_2' => [2, 0], 'math' => [2, 0],
                'religion' => [0, 7], 'science' => [0, 7], 'bgs' => [3, 6], 'ict' => [5, 0], 'agriculture' => [5, 0]
            ],
            27 => [
                'bangla_1' => [2, 6], 'bangla_2' => [5, 3], 'english_1' => [8, 4], 'english_2' => [5, 1], 'math' => [5, 3],
                'religion' => [4, 5], 'science' => [8, 5], 'bgs' => [4, 8], 'ict' => [5, 0], 'agriculture' => [5, 4]
            ],
            28 => [ // In DB Roll 28: Talha Sheikh Badhan -> Sheet Row 29 marks
                'bangla_1' => [0, 8], 'bangla_2' => [0, 3], 'english_1' => [0, 8], 'english_2' => [0, 1], 'math' => [0, 5],
                'religion' => [0, 11], 'science' => [0, 6], 'bgs' => [0, 5], 'ict' => [5, 5], 'agriculture' => [5, 2]
            ],
            29 => [ // In DB Roll 29: Md. Siam Chowdhury -> Sheet Row 28 marks
                'bangla_1' => [4, 6], 'bangla_2' => [4, 3], 'english_1' => [8, 3], 'english_2' => [5, 1], 'math' => [3, 2],
                'religion' => [5, 6], 'science' => [7, 3], 'bgs' => [5, 4], 'ict' => [5, 1], 'agriculture' => [5, 1]
            ],
            30 => [
                'bangla_1' => [3, 5], 'bangla_2' => [2, 3], 'english_1' => [5, 3], 'english_2' => [3, 1], 'math' => [0, 1],
                'religion' => [3, 5], 'science' => [0, 1], 'bgs' => [0, 2], 'ict' => [5, 1], 'agriculture' => [5, 0]
            ],
            31 => [
                'bangla_1' => [4, 7], 'bangla_2' => [2, 4], 'english_1' => [4, 8], 'english_2' => [4, 1], 'math' => [0, 3],
                'religion' => [3, 7], 'science' => [8, 3], 'bgs' => [9, 3], 'ict' => [5, 2], 'agriculture' => [5, 2]
            ],
            32 => 'absent', // Mainul Haque Nirob
            33 => [
                'bangla_1' => [6, 8], 'bangla_2' => [3, 8], 'english_1' => [6, 6], 'english_2' => [5, 1], 'math' => [4, 12],
                'religion' => [5, 10], 'science' => [9, 8], 'bgs' => [8, 10], 'ict' => [5, 6], 'agriculture' => [5, 5]
            ],
            34 => [
                'bangla_1' => [6, 11], 'bangla_2' => [4, 5], 'english_1' => [10, 8], 'english_2' => [5, 0], 'math' => [7, 8],
                'religion' => [9, 10], 'science' => [10, 11], 'bgs' => [10, 8], 'ict' => [5, 4], 'agriculture' => [5, 4]
            ],
            35 => [
                'bangla_1' => [8, 5], 'bangla_2' => [4, 8], 'english_1' => [8, 3], 'english_2' => [5, 0], 'math' => [2, 0],
                'religion' => [7, 4], 'science' => [8, 6], 'bgs' => [10, 5], 'ict' => [5, 6], 'agriculture' => [5, 7]
            ],
            36 => [
                'bangla_1' => [8, 6], 'bangla_2' => [3, 3], 'english_1' => [5, 7], 'english_2' => [2, 0], 'math' => [3, 2],
                'religion' => [6, 8], 'science' => [9, 7], 'bgs' => [1, 5], 'ict' => [5, 2], 'agriculture' => [5, 3]
            ],
            37 => [
                'bangla_1' => [5, 6], 'bangla_2' => [3, 4], 'english_1' => [4, 6], 'english_2' => [5, 0], 'math' => [5, 6],
                'religion' => [4, 9], 'science' => [8, 8], 'bgs' => [6, 7], 'ict' => [5, 2], 'agriculture' => [5, 2]
            ],
            38 => [
                'bangla_1' => [2, 7], 'bangla_2' => [4, 5], 'english_1' => [2, 5], 'english_2' => [4, 1], 'math' => [0, 0],
                'religion' => [2, 8], 'science' => [0, 3], 'bgs' => [3, 3], 'ict' => [5, 0], 'agriculture' => [5, 2]
            ],
            39 => [
                'bangla_1' => [0, 5], 'bangla_2' => [2, 5], 'english_1' => [0, 8], 'english_2' => [4, 1], 'math' => [0, 1],
                'religion' => [3, 4], 'science' => [7, 4], 'bgs' => [0, 3], 'ict' => [5, 0], 'agriculture' => [5, 3]
            ],
            40 => [
                'bangla_1' => [6, 14], 'bangla_2' => [5, 8], 'english_1' => [10, 13], 'english_2' => [5, 4], 'math' => [9, 15],
                'religion' => [7, 14], 'science' => [10, 12], 'bgs' => [10, 13], 'ict' => [5, 10], 'agriculture' => [5, 5]
            ],
            41 => [
                'bangla_1' => [3, 10], 'bangla_2' => [2, 4], 'english_1' => [2, 7], 'english_2' => [3, 0], 'math' => [0, 2],
                'religion' => [5, 5], 'science' => [8, 4], 'bgs' => [1, 2], 'ict' => [5, 0], 'agriculture' => [5, 2]
            ],
            42 => [
                'bangla_1' => [2, 10], 'bangla_2' => [5, 7], 'english_1' => [9, 11], 'english_2' => [5, 1], 'math' => [1, 7],
                'religion' => [5, 9], 'science' => [7, 10], 'bgs' => [0, 10], 'ict' => [5, 1], 'agriculture' => [5, 1]
            ],
            43 => 'absent', // Mushfiqur Rahman Muhin
            44 => [
                'bangla_1' => [7, 14], 'bangla_2' => [4, 5], 'english_1' => [8, 9], 'english_2' => [5, 1], 'math' => [0, 10],
                'religion' => [7, 9], 'science' => [10, 9], 'bgs' => [6, 12], 'ict' => [5, 7], 'agriculture' => [5, 3]
            ],
            45 => [
                'bangla_1' => [4, 17], 'bangla_2' => [5, 8], 'english_1' => [10, 14], 'english_2' => [5, 3], 'math' => [6, 10],
                'religion' => [4, 11], 'science' => [8, 10], 'bgs' => [5, 12], 'ict' => [5, 10], 'agriculture' => [5, 6]
            ],
            46 => 'absent', // Abu Sahab Saif
            47 => 'absent', // Md. Abdul Momin
            48 => [
                'bangla_1' => [0, 2], 'bangla_2' => [2, 3], 'english_1' => [3, 2], 'english_2' => [2, 0], 'math' => [2, 2],
                'religion' => [2, 7], 'science' => [1, 3], 'bgs' => [0, 3], 'ict' => [5, 0], 'agriculture' => [5, 0]
            ],
            49 => [
                'bangla_1' => [5, 10], 'bangla_2' => [5, 4], 'english_1' => [9, 10], 'english_2' => [5, 4], 'math' => [4, 7],
                'religion' => [4, 12], 'science' => [8, 9], 'bgs' => [6, 15], 'ict' => [5, 5], 'agriculture' => [5, 4]
            ],
            50 => [
                'bangla_1' => [7, 16], 'bangla_2' => [5, 9], 'english_1' => [10, 6], 'english_2' => [5, 3], 'math' => [6, 11],
                'religion' => [8, 12], 'science' => [10, 9], 'bgs' => [9, 14], 'ict' => [5, 4], 'agriculture' => [5, 3]
            ],
            51 => [
                'bangla_1' => [3, 6], 'bangla_2' => [5, 7], 'english_1' => [10, 4], 'english_2' => [5, 3], 'math' => [6, 11],
                'religion' => [4, 9], 'science' => [8, 5], 'bgs' => [9, 10], 'ict' => [5, 7], 'agriculture' => [5, 1]
            ],
            52 => [
                'bangla_1' => [5, 6], 'bangla_2' => [5, 0], 'english_1' => [8, 4], 'english_2' => [5, 1], 'math' => [3, 7],
                'religion' => [3, 10], 'science' => [8, 5], 'bgs' => [6, 6], 'ict' => [5, 0], 'agriculture' => [5, 3]
            ],
            53 => [
                'bangla_1' => [2, 7], 'bangla_2' => [5, 4], 'english_1' => [1, 7], 'english_2' => [4, 0], 'math' => [2, 1],
                'religion' => [2, 3], 'science' => [2, 3], 'bgs' => [0, 6], 'ict' => [5, 0], 'agriculture' => [5, 3]
            ],
            54 => [
                'bangla_1' => [6, 12], 'bangla_2' => [4, 6], 'english_1' => [7, 14], 'english_2' => [5, 7], 'math' => [5, 8],
                'religion' => [5, 11], 'science' => [9, 9], 'bgs' => [10, 9], 'ict' => [5, 5], 'agriculture' => [5, 3]
            ],
            55 => [
                'bangla_1' => [0, 7], 'bangla_2' => [0, 9], 'english_1' => [0, 11], 'english_2' => [0, 6], 'math' => [0, 15],
                'religion' => [0, 10], 'science' => [0, 12], 'bgs' => [0, 12], 'ict' => [5, 6], 'agriculture' => [0, 5]
            ],
            56 => [
                'bangla_1' => [0, 7], 'bangla_2' => [0, 3], 'english_1' => [0, 5], 'english_2' => [0, 1], 'math' => [0, 7],
                'religion' => [0, 9], 'science' => [0, 6], 'bgs' => [0, 8], 'ict' => [5, 2], 'agriculture' => [0, 4]
            ],
        ];

        DB::beginTransaction();

        try {
            $insertedCount = 0;

            foreach ($studentMarks as $roll => $data) {
                // Look up student by roll number in Class Six
                $student = Student::where('class_id', $classId)
                    ->where('roll_number', (string)$roll)
                    ->first();

                if (!$student) {
                    $this->command->error("Student with roll $roll not found in class $classId!");
                    continue;
                }

                $isAbsent = ($data === 'absent');

                foreach ($subjects as $subjectKey => $subjectId) {
                    if ($isAbsent) {
                        $ct = 0.00;
                        $mcq = 0.00;
                        $written = 0.00;
                        $total = 0.00;
                        $studentIsAbsent = 1;
                    } else {
                        $scores = $data[$subjectKey] ?? [0, 0];
                        $ct = (float)($scores[0] ?? 0);
                        $mcq = (float)($scores[1] ?? 0);
                        $written = 0.00;
                        $total = $ct + $mcq + $written;
                        $studentIsAbsent = 0;
                    }

                    // Find grade if available
                    $grade = Grade::where('min_mark', '<=', $total)
                        ->where('max_mark', '>=', $total)
                        ->first();

                    Mark::updateOrCreate(
                        [
                            'session_year_id' => $sessionYearId,
                            'branch_id'       => $branchId,
                            'exam_id'         => $examId,
                            'class_id'        => $classId,
                            'subject_id'      => $subjectId,
                            'student_id'      => $student->id,
                        ],
                        [
                            'section_id'   => $student->section_id ?? 1,
                            'ct_mark'      => $ct,
                            'mcq_mark'     => $mcq,
                            'written_mark' => $written,
                            'total_mark'   => $total,
                            'letter_grade' => $grade ? $grade->grade_name : 'F',
                            'grade_point'  => $grade ? $grade->grade_point : 0.00,
                            'is_absent'    => $studentIsAbsent,
                        ]
                    );

                    $insertedCount++;
                }
            }

            DB::commit();
            $this->command->info("Successfully processed $insertedCount marks records for Class Six.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error seeding marks: " . $e->getMessage());
            throw $e;
        }
    }
}
