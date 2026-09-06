<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Mark;
use App\Models\Grade;
use App\Models\Student;
use App\Models\ExamSchedule;

class ClassTwoMarksSeeder extends Seeder
{
    /**
     * Run the database seeds for Class Two Marks.
     */
    public function run(): void
    {
        $sessionYearId = 1; // 2026
        $branchId = 3;      // Jalalpur Branch
        $examId = 2;        // 2nd Term Exam 2026
        $classId = 3;       // Two

        $subjects = [
            'bangla'   => 131, // বাংলা
            'english'  => 26,  // ইংরেজী
            'math'     => 29,  // গণিত
            'gk'       => 30,  // সাধারণ জ্ঞান
            'social'   => 31,  // সমাজ
            'religion' => 32,  // আরবী / ধর্মশিক্ষা (ইসলাম শিক্ষা)
            'drawing'  => 33,  // ড্রইং
        ];

        // Ensure Exam Schedules exist for Class Two so Tabulation and Marks page work smoothly
        $scheduleConfigs = [
            131 => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            26  => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            29  => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            30  => ['full' => 50,  'pass' => 17, 'ct' => 10, 'mcq' => 10, 'written' => 30],
            31  => ['full' => 50,  'pass' => 17, 'ct' => 10, 'mcq' => 10, 'written' => 30],
            32  => ['full' => 100, 'pass' => 33, 'ct' => 10, 'mcq' => 20, 'written' => 70],
            33  => ['full' => 50,  'pass' => 17, 'ct' => 10, 'mcq' => 10, 'written' => 30],
        ];

        foreach ($scheduleConfigs as $subId => $cfg) {
            ExamSchedule::firstOrCreate(
                [
                    'branch_id'  => $branchId,
                    'class_id'   => $classId,
                    'subject_id' => $subId,
                ],
                [
                    'exam_id'       => $examId,
                    'full_marks'    => $cfg['full'],
                    'pass_marks'    => $cfg['pass'],
                    'ct_marks'      => $cfg['ct'],
                    'written_marks' => $cfg['written'],
                    'mcq_marks'     => $cfg['mcq'],
                ]
            );
        }

        // Student marks dataset: [CT, MT]
        $studentMarks = [
            1  => ['bangla' => [10, 19],   'english' => [10, 20],   'math' => [10, 20], 'gk' => [10, 10],  'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            2  => ['bangla' => [10, 19],   'english' => [10, 20],   'math' => [10, 20], 'gk' => [10, 10],  'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            3  => ['bangla' => [10, 19],   'english' => [10, 20],   'math' => [10, 20], 'gk' => [10, 9],   'social' => [10, 9],  'religion' => [10, 20], 'drawing' => [10, 10]],
            4  => ['bangla' => [10, 19],   'english' => [10, 20],   'math' => [10, 20], 'gk' => [10, 10],  'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            5  => ['bangla' => [10, 17.5], 'english' => [10, 19.5], 'math' => [10, 19], 'gk' => [10, 9],   'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            6  => ['bangla' => [10, 19],   'english' => [10, 20],   'math' => [10, 20], 'gk' => [10, 8],   'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            7  => ['bangla' => [10, 19],   'english' => [10, 20],   'math' => [10, 18], 'gk' => [10, 10],  'social' => [10, 9],  'religion' => [10, 19], 'drawing' => [10, 10]],
            8  => ['bangla' => [10, 18],   'english' => [10, 20],   'math' => [10, 20], 'gk' => [10, 9],   'social' => [4, 10],  'religion' => [10, 20], 'drawing' => [10, 10]],
            9  => ['bangla' => [10, 16.5], 'english' => [10, 20],   'math' => [10, 20], 'gk' => [10, 7],   'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            10 => ['bangla' => [9, 17.5],  'english' => [10, 19],   'math' => [10, 17], 'gk' => [3, 8],    'social' => [10, 9],  'religion' => [10, 19], 'drawing' => [10, 9]],
            11 => ['bangla' => [10, 19],   'english' => [10, 18.5], 'math' => [10, 20], 'gk' => [10, 7],   'social' => [10, 9],  'religion' => [10, 17], 'drawing' => [10, 10]],
            12 => ['bangla' => [8, 17.5],  'english' => [10, 18.5], 'math' => [10, 20], 'gk' => [10, 5],   'social' => [4, 10],  'religion' => [10, 19], 'drawing' => [10, 10]],
            13 => ['bangla' => [9, 18],    'english' => [10, 20],   'math' => [10, 20], 'gk' => [4, 8],    'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            14 => ['bangla' => [10, 16],   'english' => [10, 18.5], 'math' => [10, 18], 'gk' => [10, 7.5], 'social' => [10, 10], 'religion' => [10, 19], 'drawing' => [10, 10]],
            15 => ['bangla' => [8, 17],    'english' => [10, 15.5], 'math' => [10, 20], 'gk' => [10, 10],  'social' => [4, 9],   'religion' => [10, 20], 'drawing' => [10, 10]],
            16 => ['bangla' => [10, 16.5], 'english' => [10, 14.5], 'math' => [10, 18], 'gk' => [10, 7],   'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            17 => 'absent', // Blank on sheets
            18 => ['bangla' => [10, 16.5], 'english' => [10, 13.5], 'math' => [10, 20], 'gk' => [10, 9],   'social' => [10, 7],  'religion' => [10, 20], 'drawing' => [10, 10]],
            19 => ['bangla' => [8, 19],    'english' => [10, 18.5], 'math' => [10, 16], 'gk' => [10, 5],   'social' => [4, 7],   'religion' => [10, 16], 'drawing' => [10, 10]],
            20 => ['bangla' => [10, 18],   'english' => [10, 17.5], 'math' => [7, 18],  'gk' => [10, 8],   'social' => [10, 7],  'religion' => [7, 16],  'drawing' => [10, 10]],
            21 => ['bangla' => [9, 16],    'english' => [10, 17],   'math' => [10, 20], 'gk' => [10, 10],  'social' => [10, 9],  'religion' => [10, 20], 'drawing' => [10, 10]],
            22 => ['bangla' => [9, 19],    'english' => [10, 18.5], 'math' => [10, 20], 'gk' => [10, 8],   'social' => [10, 5],  'religion' => [10, 19], 'drawing' => [4, 9]],
            23 => ['bangla' => [8, 19],    'english' => [10, 15],   'math' => [10, 15], 'gk' => [3, 4],    'social' => [4, 8],   'religion' => [3, 17],  'drawing' => [4, 10]],
            24 => ['bangla' => [10, 17],   'english' => [10, 18],   'math' => [10, 19], 'gk' => [10, 9],   'social' => [10, 9],  'religion' => [10, 18], 'drawing' => [10, 10]],
            25 => ['bangla' => [9, 18.5],  'english' => [10, 17],   'math' => [10, 20], 'gk' => [10, 9],   'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 9]],
            26 => ['bangla' => [8, 18],    'english' => [10, 18.5], 'math' => [10, 13], 'gk' => [10, 9],   'social' => [4, 6],   'religion' => [10, 17], 'drawing' => [10, 9]],
            27 => ['bangla' => [8, 13.5],  'english' => [10, 11],   'math' => [10, 14], 'gk' => [10, 7],   'social' => [4, 7],   'religion' => [4, 17],  'drawing' => [10, 10]],
            28 => ['bangla' => [8, 18],    'english' => [10, 19],   'math' => [10, 19], 'gk' => [10, 9],   'social' => [4, 10],  'religion' => [10, 17], 'drawing' => [10, 9]],
            29 => ['bangla' => [10, 18.5], 'english' => [10, 18],   'math' => [10, 16], 'gk' => [4, 8],    'social' => [10, 8],  'religion' => [10, 19], 'drawing' => [10, 9]],
            30 => ['bangla' => [8, 16],    'english' => [10, 12],   'math' => [10, 13], 'gk' => [10, 7],   'social' => [4, 6],   'religion' => [10, 15], 'drawing' => [10, 9]],
            31 => ['bangla' => [3, 17],    'english' => [10, 12.5], 'math' => [10, 16], 'gk' => [10, 5],   'social' => [3, 8],   'religion' => [0, 16],  'drawing' => [10, 9]],
            32 => ['bangla' => [8, 15],    'english' => [10, 14],   'math' => [10, 16], 'gk' => [10, 8],   'social' => [4, 4],   'religion' => [10, 14], 'drawing' => [10, 10]],
            33 => ['bangla' => [8, 14],    'english' => [10, 12],   'math' => [10, 15], 'gk' => [10, 5],   'social' => [4, 6],   'religion' => [10, 13], 'drawing' => [4, 10]],
            34 => ['bangla' => [8, 12],    'english' => [10, 4.5],  'math' => [10, 11], 'gk' => [10, 3],   'social' => [4, 3],   'religion' => [3, 7],   'drawing' => [10, 10]],
            35 => ['bangla' => [8, 16.5],  'english' => [10, 14],   'math' => [10, 16], 'gk' => [10, 8],   'social' => [4, 10],  'religion' => [4, 19],  'drawing' => [4, 10]],
            36 => ['bangla' => [8, 16],    'english' => [10, 14],   'math' => [10, 18], 'gk' => [10, 5],   'social' => [4, 8],   'religion' => [10, 16], 'drawing' => [10, 10]],
            37 => ['bangla' => [8, 11],    'english' => [10, 6],    'math' => [10, 12], 'gk' => [10, 5],   'social' => [4, 3],   'religion' => [10, 9],   'drawing' => [10, 8]],
            38 => ['bangla' => [6, 10],    'english' => [10, 9],    'math' => [10, 14], 'gk' => [10, 2],   'social' => [4, 0],   'religion' => [10, 7],   'drawing' => [4, 9]],
            39 => ['bangla' => [3, 11.5],  'english' => [10, 2.5],  'math' => [3, 15],  'gk' => [10, 4],   'social' => [4, 0],   'religion' => [2, 9],   'drawing' => [3, 9]],
            40 => ['bangla' => [3, 10],    'english' => [10, 8],    'math' => [6, 7],   'gk' => [10, 2],   'social' => [4, 1],   'religion' => [1, 3],   'drawing' => [4, 4]],
            41 => ['bangla' => [8, 16.5],  'english' => [10, 13],   'math' => [10, 15], 'gk' => [10, 8],   'social' => [4, 7],   'religion' => [4, 16],  'drawing' => [4, 8]],
            42 => ['bangla' => [9, 19],    'english' => [10, 18],   'math' => [10, 17], 'gk' => [10, 9],   'social' => [4, 8],   'religion' => [8, 19],  'drawing' => [10, 10]],
            43 => ['bangla' => [10, 16],   'english' => [10, 17],   'math' => [10, 20], 'gk' => [10, 10],  'social' => [10, 6],  'religion' => [10, 18], 'drawing' => [10, 10]],
            44 => ['bangla' => [8, 16],    'english' => [10, 8.5],  'math' => [10, 12], 'gk' => [10, 3],   'social' => [4, 1],   'religion' => [1, 11],  'drawing' => [10, 8]],
            45 => ['bangla' => [8, 12.5],  'english' => [10, 15],   'math' => [10, 7],  'gk' => [10, 6],   'social' => [4, 6],   'religion' => [10, 14], 'drawing' => [4, 9]],
            46 => ['bangla' => [9, 19.5],  'english' => [10, 19],   'math' => [10, 15], 'gk' => [3, 9],    'social' => [10, 9],  'religion' => [10, 20], 'drawing' => [10, 10]],
            47 => ['bangla' => [10, 17.5], 'english' => [10, 13.5], 'math' => [10, 17], 'gk' => [10, 8],   'social' => [10, 9],  'religion' => [6, 17],  'drawing' => [4, 8]],
            48 => ['bangla' => [3, 11.5],  'english' => [10, 11.5], 'math' => [10, 18], 'gk' => [10, 7],   'social' => [4, 1],   'religion' => [4, 19],  'drawing' => [10, 8]],
            49 => ['bangla' => [8, 18],    'english' => [10, 18],   'math' => [10, 20], 'gk' => [10, 8],   'social' => [4, 8],   'religion' => [10, 19], 'drawing' => [4, 9]],
            50 => ['bangla' => [10, 19.5], 'english' => [10, 17.5], 'math' => [10, 20], 'gk' => [10, 8],   'social' => [10, 10], 'religion' => [10, 20], 'drawing' => [10, 10]],
            51 => ['bangla' => [8, 18],    'english' => [10, 11.5], 'math' => [10, 18], 'gk' => [10, 7],   'social' => [4, 9],   'religion' => [10, 18], 'drawing' => [4, 9]],
            52 => ['bangla' => [8, 19],    'english' => [10, 19.5], 'math' => [10, 17], 'gk' => [10, 9],   'social' => [4, 10],  'religion' => [4, 19],  'drawing' => [10, 10]],
            53 => ['bangla' => [10, 18.5], 'english' => [10, 18],   'math' => [10, 18], 'gk' => [10, 8],   'social' => [4, 8],   'religion' => [10, 20], 'drawing' => [4, 10]],
            54 => ['bangla' => [10, 17.5], 'english' => [10, 17],   'math' => [10, 20], 'gk' => [10, 10],  'social' => [10, 10], 'religion' => [9, 20],  'drawing' => [10, 10]],
            55 => ['bangla' => [8, 14],    'english' => [10, 9],    'math' => [10, 16], 'gk' => [10, 4],   'social' => [4, 1],   'religion' => [10, 7],   'drawing' => [10, 10]],
            56 => ['bangla' => [10, 19],   'english' => [10, 19],   'math' => [10, 19], 'gk' => [4, 9],    'social' => [10, 9],  'religion' => [10, 19], 'drawing' => [4, 10]],
            57 => ['bangla' => [8, 17],    'english' => [10, 16],   'math' => [10, 20], 'gk' => [10, 10],  'social' => [4, 9],   'religion' => [10, 20], 'drawing' => [4, 10]],
        ];

        DB::beginTransaction();

        try {
            $insertedCount = 0;

            foreach ($studentMarks as $roll => $data) {
                // Find student by roll_number in Class Two
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
            $this->command->info("Successfully processed $insertedCount marks records for Class Two.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error seeding marks: " . $e->getMessage());
            throw $e;
        }
    }
}
