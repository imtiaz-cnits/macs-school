<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Mark;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamSchedule;

class ClassTenMarksSeeder extends Seeder
{
    /**
     * Run the database seeds for Class Ten Marks (Pre-Model Exam 2026).
     */
    public function run(): void
    {
        $sessionYearId = 1; // 2026
        $branchId = 3;      // Jalalpur Branch
        $classId = 12;      // Class Ten

        // Ensure Pre-Model Exam exists
        $exam = Exam::firstOrCreate(
            [
                'name'            => 'Pre-Model Exam 2026',
                'session_year_id' => $sessionYearId,
            ],
            [
                'status' => 'upcoming',
            ]
        );
        $examId = $exam->id;

        $subjects = [
            'bangla_1'    => 98,  // বাংলা ১ম পত্র
            'bangla_2'    => 99,  // বাংলা ২য় পত্র
            'english_1'   => 100, // ইংরেজী ১ম পত্র
            'english_2'   => 101, // ইংরেজী ২য় পত্র
            'math'        => 102, // সাধারণ গণিত
            'religion'    => 103, // ইসলাম শিক্ষা
            'bgs'         => 104, // বাংলাদেশ ও বিশ্বপরিচয় / সাধারণ বিজ্ঞান
            'physics'     => 107, // পদার্থ / ইতিহাস
            'chemistry'   => 106, // রসায়ন / অর্থনীতি
            'biology'     => 105, // জীববিজ্ঞান / ভূগোল
            'higher_math' => 108, // উচ্চতর গণিত / কৃষি শিক্ষা
            'ict'         => 109, // তথ্য ও যোগাযোগ প্রযুক্তি
        ];

        // Ensure Exam Schedules exist for Class Ten
        $scheduleConfigs = [
            98  => ['full' => 100, 'pass' => 33, 'written' => 70, 'mcq' => 30, 'ct' => 0],
            99  => ['full' => 100, 'pass' => 33, 'written' => 70, 'mcq' => 30, 'ct' => 0],
            100 => ['full' => 100, 'pass' => 33, 'written' => 100, 'mcq' => 0, 'ct' => 0],
            101 => ['full' => 100, 'pass' => 33, 'written' => 100, 'mcq' => 0, 'ct' => 0],
            102 => ['full' => 100, 'pass' => 33, 'written' => 70, 'mcq' => 30, 'ct' => 0],
            103 => ['full' => 100, 'pass' => 33, 'written' => 70, 'mcq' => 30, 'ct' => 0],
            104 => ['full' => 100, 'pass' => 33, 'written' => 70, 'mcq' => 30, 'ct' => 0],
            107 => ['full' => 100, 'pass' => 33, 'written' => 50, 'mcq' => 25, 'ct' => 25],
            106 => ['full' => 100, 'pass' => 33, 'written' => 50, 'mcq' => 25, 'ct' => 25],
            105 => ['full' => 100, 'pass' => 33, 'written' => 50, 'mcq' => 25, 'ct' => 25],
            108 => ['full' => 100, 'pass' => 33, 'written' => 50, 'mcq' => 25, 'ct' => 25],
            109 => ['full' => 50,  'pass' => 17, 'written' => 0,  'mcq' => 25, 'ct' => 25],
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

        // Ensure student for Roll 25 (Abdur Rahman) exists in Class Ten
        Student::firstOrCreate(
            [
                'class_id'    => $classId,
                'roll_number' => '25',
            ],
            [
                'student_identity'    => '26051200025',
                'student_name'        => 'Abdur Rahman',
                'name_in_bangla'      => 'আব্দুর রহমান',
                'branch_id'           => $branchId,
                'section_id'          => 1,
                'shift_id'            => 5,
                'session_year_id'     => $sessionYearId,
                'dob'                 => '2010-01-01',
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

        // Student marks dataset:
        // Format per subject: [CQ/Written, MCQ, Practical/CT]
        $studentMarks = [
            1 => [ // Rohan Hussain (Merit 1)
                'bangla_1'    => [44, 25, 0],
                'bangla_2'    => [44, 22, 0],
                'english_1'   => [85, 0, 0],
                'english_2'   => [68, 0, 0],
                'math'        => [48, 13, 0],
                'religion'    => [47, 24, 0],
                'bgs'         => [42, 23, 0],
                'physics'     => [25, 17, 25],
                'chemistry'   => [31, 12, 25],
                'biology'     => [37, 14, 25],
                'higher_math' => [42, 17, 25],
                'ict'         => [0, 22, 20],
            ],
            4 => [ // Md. Sadman Abdullah Rohan (Merit 2)
                'bangla_1'    => [38, 23, 0],
                'bangla_2'    => [51, 14, 0],
                'english_1'   => [87, 0, 0],
                'english_2'   => [73, 0, 0],
                'math'        => [32, 11, 0],
                'religion'    => [49, 23, 0],
                'bgs'         => [45, 21, 0],
                'physics'     => [31, 16, 25],
                'chemistry'   => [33, 12, 25],
                'biology'     => [44, 17, 25],
                'higher_math' => [29, 12, 25],
                'ict'         => [0, 21, 20],
            ],
            3 => [ // Jannatul Ferdous (Merit 3)
                'bangla_1'    => [17, 23, 0],
                'bangla_2'    => [52, 12, 0],
                'english_1'   => [85, 0, 0],
                'english_2'   => [52, 0, 0],
                'math'        => [32, 13, 0],
                'religion'    => [54, 20, 0],
                'bgs'         => [48, 18, 0],
                'physics'     => [18, 9, 25],
                'chemistry'   => [20, 9, 25],
                'biology'     => [37, 17, 25],
                'higher_math' => [31, 9, 25],
                'ict'         => [0, 18, 20],
            ],
            10 => [ // Md. Mizanur Rahman (Merit 4)
                'bangla_1'    => [33, 20, 0],
                'bangla_2'    => [46, 10, 0],
                'english_1'   => [59, 0, 0],
                'english_2'   => [35, 0, 0],
                'math'        => [34, 18, 0],
                'religion'    => [39, 16, 0],
                'bgs'         => [30, 16, 0],
                'physics'     => [18, 15, 25],
                'chemistry'   => [24, 8, 25],
                'biology'     => [36, 12, 20],
                'higher_math' => [24, 18, 25],
                'ict'         => [0, 17, 20],
            ],
            6 => [ // Riya Khatun (Merit 5)
                'bangla_1'    => [56, 20, 0],
                'bangla_2'    => [37, 11, 0],
                'english_1'   => [82, 0, 0],
                'english_2'   => [38, 0, 0],
                'math'        => [29, 18, 0],
                'religion'    => [47, 14, 0],
                'bgs'         => [36, 18, 0],
                'physics'     => [10, 12, 0],
                'chemistry'   => [22, 15, 25],
                'biology'     => [37, 13, 25],
                'higher_math' => [35, 21, 25],
                'ict'         => [0, 16, 20],
            ],
            5 => [ // Khandaker Wahedul Hasan (Merit 6)
                'bangla_1'    => [39, 20, 0],
                'bangla_2'    => [35, 12, 0],
                'english_1'   => [80, 0, 0],
                'english_2'   => [60, 0, 0],
                'math'        => [28, 13, 0],
                'religion'    => [27, 18, 0],
                'bgs'         => [34, 18, 0],
                'physics'     => [19, 11, 25],
                'chemistry'   => [15, 11, 0],
                'biology'     => [28, 14, 25],
                'higher_math' => [35, 13, 25],
                'ict'         => [0, 21, 15],
            ],
            15 => [ // Mst. Ayesha Siddique (Merit 7)
                'bangla_1'    => [60, 21, 0],
                'bangla_2'    => [49, 13, 0],
                'english_1'   => [65, 0, 0],
                'english_2'   => [39, 0, 0],
                'math'        => [24, 15, 0],
                'religion'    => [43, 17, 0],
                'bgs'         => [34, 19, 0],
                'physics'     => [10, 11, 0],
                'chemistry'   => [15, 8, 0],
                'biology'     => [30, 11, 25],
                'higher_math' => [36, 16, 25],
                'ict'         => [0, 12, 20],
            ],
            2 => [ // Sanjida Khatun (Merit 8)
                'bangla_1'    => [53, 22, 0],
                'bangla_2'    => [51, 12, 0],
                'english_1'   => [58, 0, 0],
                'english_2'   => [28, 0, 0],
                'math'        => [40, 14, 0],
                'religion'    => [46, 16, 0],
                'bgs'         => [45, 21, 0],
                'physics'     => [8, 9, 0],
                'chemistry'   => [21, 14, 25],
                'biology'     => [40, 13, 25],
                'higher_math' => [13, 4, 0],
                'ict'         => [0, 15, 20],
            ],
            11 => [ // Wafia Aliza Maryam (Merit 9)
                'bangla_1'    => [59, 25, 0],
                'bangla_2'    => [52, 14, 0],
                'english_1'   => [59, 0, 0],
                'english_2'   => [36, 0, 0],
                'math'        => [30, 13, 0],
                'religion'    => [40, 16, 0],
                'bgs'         => [28, 17, 0],
                'physics'     => [6, 8, 0],
                'chemistry'   => [18, 11, 25],
                'biology'     => [33, 16, 25],
                'higher_math' => [8, 8, 0],
                'ict'         => [0, 17, 20],
            ],
            17 => [ // Mst. Jannatul Ferdous Saima (Merit 10)
                'bangla_1'    => [52, 18, 0],
                'bangla_2'    => [36, 10, 0],
                'english_1'   => [43, 0, 0],
                'english_2'   => [30, 0, 0],
                'math'        => [19, 19, 0],
                'religion'    => [41, 14, 0],
                'bgs'         => [30, 11, 0],
                'physics'     => [32, 12, 0],
                'chemistry'   => [17, 12, 0],
                'biology'     => [34, 17, 0],
                'higher_math' => [30, 16, 25],
                'ict'         => [0, 14, 20],
            ],
            25 => [ // Abdur Rahman (Merit 11)
                'bangla_1'    => [43, 21, 0],
                'bangla_2'    => [45, 11, 0],
                'english_1'   => [55, 0, 0],
                'english_2'   => [33, 0, 0],
                'math'        => [33, 7, 0],
                'religion'    => [36, 20, 0],
                'bgs'         => [32, 15, 0],
                'physics'     => [23, 14, 25],
                'chemistry'   => [8, 5, 0],
                'biology'     => [19, 12, 0],
                'higher_math' => [14, 11, 0],
                'ict'         => [0, 17, 20],
            ],
            9 => [ // Mst. Tanima Khatun (Merit 12)
                'bangla_1'    => [41, 20, 0],
                'bangla_2'    => [38, 14, 0],
                'english_1'   => [52, 0, 0],
                'english_2'   => [20, 0, 0],
                'math'        => [19, 13, 0],
                'religion'    => [37, 9, 0],
                'bgs'         => [20, 10, 0],
                'physics'     => [25, 8, 0],
                'chemistry'   => [15, 12, 0],
                'biology'     => [26, 16, 0],
                'higher_math' => [23, 21, 25],
                'ict'         => [0, 15, 20],
            ],
            16 => [ // Md. Parvez Ahmed (Merit 13)
                'bangla_1'    => [37, 21, 0],
                'bangla_2'    => [36, 13, 0],
                'english_1'   => [46, 0, 0],
                'english_2'   => [24, 0, 0],
                'math'        => [23, 10, 0],
                'religion'    => [27, 18, 0],
                'bgs'         => [24, 15, 0],
                'physics'     => [12, 14, 0],
                'chemistry'   => [21, 7, 0],
                'biology'     => [25, 13, 25],
                'higher_math' => [15, 18, 0],
                'ict'         => [0, 20, 20],
            ],
            12 => [ // Suhan Ali (Merit 14)
                'bangla_1'    => [36, 17, 0],
                'bangla_2'    => [39, 11, 0],
                'english_1'   => [31, 0, 0],
                'english_2'   => [25, 0, 0],
                'math'        => [12, 11, 0],
                'religion'    => [23, 14, 0],
                'bgs'         => [23, 16, 0],
                'physics'     => [8, 11, 0],
                'chemistry'   => [5, 14, 0],
                'biology'     => [31, 14, 25],
                'higher_math' => [25, 14, 25],
                'ict'         => [0, 20, 20],
            ],
            18 => [ // Mst. Baishakhi Akhter (Merit 15)
                'bangla_1'    => [26, 23, 0],
                'bangla_2'    => [32, 10, 0],
                'english_1'   => [33, 0, 0],
                'english_2'   => [21, 0, 0],
                'math'        => [14, 7, 0],
                'religion'    => [28, 16, 0],
                'bgs'         => [20, 11, 0],
                'physics'     => [26, 13, 0],
                'chemistry'   => [14, 14, 0],
                'biology'     => [26, 19, 0],
                'higher_math' => [27, 17, 25],
                'ict'         => [0, 14, 20],
            ],
            13 => [ // Md. Samrat Hossain Sihab (Merit 16)
                'bangla_1'    => [26, 17, 0],
                'bangla_2'    => [38, 13, 0],
                'english_1'   => [34, 0, 0],
                'english_2'   => [28, 0, 0],
                'math'        => [20, 9, 0],
                'religion'    => [19, 19, 0],
                'bgs'         => [24, 17, 0],
                'physics'     => [9, 9, 0],
                'chemistry'   => [11, 14, 0],
                'biology'     => [22, 13, 25],
                'higher_math' => [21, 17, 25],
                'ict'         => [0, 9, 15],
            ],
            14 => [ // Emdadul Haque (Merit 17)
                'bangla_1'    => [25, 21, 0],
                'bangla_2'    => [38, 13, 0],
                'english_1'   => [49, 0, 0],
                'english_2'   => [28, 0, 0],
                'math'        => [20, 18, 0],
                'religion'    => [23, 12, 0],
                'bgs'         => [24, 17, 0],
                'physics'     => [11, 15, 0],
                'chemistry'   => [4, 9, 0],
                'biology'     => [18, 10, 25],
                'higher_math' => [9, 7, 0],
                'ict'         => [0, 17, 20],
            ],
            7 => [ // Md. Zubair Khandaker Bayazid (Merit 18)
                'bangla_1'    => [29, 21, 0],
                'bangla_2'    => [34, 10, 0],
                'english_1'   => [52, 0, 0],
                'english_2'   => [57, 0, 0],
                'math'        => [9, 10, 0],
                'religion'    => [25, 7, 0],
                'bgs'         => [20, 13, 0],
                'physics'     => [11, 10, 0],
                'chemistry'   => [11, 9, 0],
                'biology'     => [20, 12, 20],
                'higher_math' => [4, 12, 0],
                'ict'         => [0, 16, 20],
            ],
            8 => [ // Md. Fahim Khan (Merit 19)
                'bangla_1'    => [20, 21, 0],
                'bangla_2'    => [38, 10, 0],
                'english_1'   => [54, 0, 0],
                'english_2'   => [24, 0, 0],
                'math'        => [11, 12, 0],
                'religion'    => [22, 14, 0],
                'bgs'         => 'absent',
                'physics'     => [12, 12, 0],
                'chemistry'   => [17, 8, 25],
                'biology'     => [18, 9, 20],
                'higher_math' => [8, 6, 0],
                'ict'         => [0, 18, 20],
            ],
            20 => [ // Md. Yameen Sarkar Tushar (Merit 20)
                'bangla_1'    => [24, 28, 0],
                'bangla_2'    => [36, 10, 0],
                'english_1'   => [29, 0, 0],
                'english_2'   => [16, 0, 0],
                'math'        => [14, 8, 0],
                'religion'    => [18, 6, 0],
                'bgs'         => [14, 14, 0],
                'physics'     => [23, 11, 0],
                'chemistry'   => [14, 9, 0],
                'biology'     => [23, 12, 0],
                'higher_math' => [14, 13, 0],
                'ict'         => [0, 15, 20],
            ],
            19 => [ // Kazi Saif Ahmed Saifi (Merit 21)
                'bangla_1'    => [24, 19, 0],
                'bangla_2'    => [29, 11, 0],
                'english_1'   => [29, 0, 0],
                'english_2'   => [7, 0, 0],
                'math'        => [25, 12, 0],
                'religion'    => [5, 12, 0],
                'bgs'         => [14, 17, 0],
                'physics'     => [4, 14, 0],
                'chemistry'   => [15, 7, 0],
                'biology'     => [15, 15, 15],
                'higher_math' => [7, 7, 0],
                'ict'         => [0, 14, 15],
            ],
            22 => [ // Md. Shafiqul Hasan (Merit 22)
                'bangla_1'    => [19, 22, 0],
                'bangla_2'    => [40, 14, 0],
                'english_1'   => [23, 0, 0],
                'english_2'   => [9, 0, 0],
                'math'        => [4, 14, 0],
                'religion'    => [6, 20, 0],
                'bgs'         => [6, 9, 0],
                'physics'     => [9, 14, 0],
                'chemistry'   => [6, 8, 0],
                'biology'     => [8, 14, 0],
                'higher_math' => [16, 16, 0],
                'ict'         => [0, 13, 15],
            ],
            21 => [ // Md. Sabbir (Merit 23)
                'bangla_1'    => [17, 21, 0],
                'bangla_2'    => [31, 11, 0],
                'english_1'   => [24, 0, 0],
                'english_2'   => [16, 0, 0],
                'math'        => [4, 10, 0],
                'religion'    => [12, 10, 0],
                'bgs'         => [5, 9, 0],
                'physics'     => [14, 10, 0],
                'chemistry'   => [4, 9, 0],
                'biology'     => [12, 13, 0],
                'higher_math' => [11, 10, 0],
                'ict'         => [0, 11, 15],
            ],
            24 => [ // Md. Hamim Khan (Merit 24)
                'bangla_1'    => [23, 17, 0],
                'bangla_2'    => [0, 8, 0],
                'english_1'   => [24, 0, 0],
                'english_2'   => [30, 0, 0],
                'math'        => [8, 8, 0],
                'religion'    => [15, 9, 0],
                'bgs'         => [17, 11, 0],
                'physics'     => [16, 14, 0],
                'chemistry'   => [3, 12, 0],
                'biology'     => [14, 13, 0],
                'higher_math' => 'absent',
                'ict'         => [0, 9, 15],
            ],
            23 => [ // Simon Hassan (Merit 25)
                'bangla_1'    => 'absent',
                'bangla_2'    => [29, 14, 0],
                'english_1'   => [34, 0, 0],
                'english_2'   => [9, 0, 0],
                'math'        => 'absent',
                'religion'    => [21, 12, 0],
                'bgs'         => [17, 5, 0],
                'physics'     => [19, 19, 0],
                'chemistry'   => 'absent',
                'biology'     => [18, 12, 0],
                'higher_math' => [18, 12, 25],
                'ict'         => 'absent',
            ],
        ];

        DB::beginTransaction();

        try {
            $insertedCount = 0;

            foreach ($studentMarks as $roll => $data) {
                // Look up student by roll number in Class Ten
                $student = Student::where('class_id', $classId)
                    ->where('roll_number', (string)$roll)
                    ->first();

                if (!$student) {
                    $this->command->error("Student with roll $roll not found in class $classId!");
                    continue;
                }

                foreach ($subjects as $subjectKey => $subjectId) {
                    $scoreEntry = $data[$subjectKey] ?? [0, 0, 0];
                    $isAbsent = ($scoreEntry === 'absent');

                    if ($isAbsent) {
                        $written = 0.00;
                        $mcq = 0.00;
                        $ct = 0.00;
                        $total = 0.00;
                        $studentIsAbsent = 1;
                    } else {
                        $written = (float)($scoreEntry[0] ?? 0);
                        $mcq = (float)($scoreEntry[1] ?? 0);
                        $ct = (float)($scoreEntry[2] ?? 0);
                        $total = $written + $mcq + $ct;
                        $studentIsAbsent = 0;
                    }

                    // Compute grade & point based on schedule config
                    $fullMarks = $scheduleConfigs[$subjectId]['full'] ?? 100;
                    $passMarks = $scheduleConfigs[$subjectId]['pass'] ?? 33;
                    $pct = $fullMarks > 0 ? ($total / $fullMarks) * 100 : 0;

                    if ($studentIsAbsent || $total < $passMarks) {
                        $letterGrade = 'F';
                        $gradePoint = 0.00;
                    } elseif ($pct >= 80) {
                        $letterGrade = 'A+';
                        $gradePoint = 5.00;
                    } elseif ($pct >= 70) {
                        $letterGrade = 'A';
                        $gradePoint = 4.00;
                    } elseif ($pct >= 60) {
                        $letterGrade = 'A-';
                        $gradePoint = 3.50;
                    } elseif ($pct >= 50) {
                        $letterGrade = 'B';
                        $gradePoint = 3.00;
                    } elseif ($pct >= 40) {
                        $letterGrade = 'C';
                        $gradePoint = 2.00;
                    } elseif ($pct >= 33) {
                        $letterGrade = 'D';
                        $gradePoint = 1.00;
                    } else {
                        $letterGrade = 'F';
                        $gradePoint = 0.00;
                    }

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
                            'written_mark' => $written,
                            'mcq_mark'     => $mcq,
                            'ct_mark'      => $ct,
                            'total_mark'   => $total,
                            'letter_grade' => $letterGrade,
                            'grade_point'  => $gradePoint,
                            'is_absent'    => $studentIsAbsent,
                        ]
                    );

                    $insertedCount++;
                }
            }

            DB::commit();
            $this->command->info("Successfully processed $insertedCount marks records for Class Ten ($exam->name).");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error seeding marks: " . $e->getMessage());
            throw $e;
        }
    }
}
