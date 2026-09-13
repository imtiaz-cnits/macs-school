<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $classSeven = DB::table('classes')->where('class_name', 'Seven')->first();
        if (!$classSeven) {
            return;
        }

        $branches = DB::table('branches')->get();
        if ($branches->isEmpty()) {
            return;
        }

        // Subject configurations for Class Seven
        // 1. Bangla 1st (100)
        // 2. Bangla 2nd (50)
        // 3. English 1st (100)
        // 4. English 2nd (50)
        // 5. Math (100)
        // 6. Science (100)
        // 7. Agricultural Science (50)
        // 8. Islam and Moral Education (100)
        // 9. Bangladesh and Global Studies / Social Science (100)
        // 10. ICT (50)
        // 11. SBA (100)
        $subjectConfigs = [
            [
                'names' => ['বাংলা ১ম পত্র', 'Bangla 1st', 'Bangla 1st Paper'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['বাংলা ২য় পত্র', 'Bangla 2nd', 'Bangla 2nd Paper'],
                'full_marks' => 50.00,
                'pass_marks' => 17.00,
                'ct_marks' => 5.00,
                'mcq_marks' => 10.00,
                'written_marks' => 35.00,
            ],
            [
                'names' => ['ইংরেজী ১ম পত্র', 'English 1st', 'English 1st Paper'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['ইংরেজী ২য় পত্র', 'English 2nd', 'English 2nd Paper'],
                'full_marks' => 50.00,
                'pass_marks' => 17.00,
                'ct_marks' => 5.00,
                'mcq_marks' => 10.00,
                'written_marks' => 35.00,
            ],
            [
                'names' => ['গণিত', 'Math', 'Mathematics'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['বিজ্ঞান', 'Science', 'General Science'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['কৃষি শিক্ষা', 'Agricultural Science', 'Agriculture'],
                'full_marks' => 50.00,
                'pass_marks' => 17.00,
                'ct_marks' => 5.00,
                'mcq_marks' => 10.00,
                'written_marks' => 35.00,
            ],
            [
                'names' => ['ইসলাম শিক্ষা', 'ইসলাম ও নৈতিক শিক্ষা', 'Islam and Moral Education', 'Islam Education'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['সামাজিক বিজ্ঞান', 'বাংলাদেশ ও বিশ্বপরিচয়', 'Bangladesh and Global Studies', 'Social Science'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['তথ্য ও যোগাযোগ প্রযুক্তি', 'ICT', 'Information and Communication Technology'],
                'full_marks' => 50.00,
                'pass_marks' => 17.00,
                'ct_marks' => 5.00,
                'mcq_marks' => 10.00,
                'written_marks' => 35.00,
            ],
            [
                'names' => ['S.B.A', 'SBA'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 0.00,
                'mcq_marks' => 0.00,
                'written_marks' => 100.00,
            ],
        ];

        // Fetch subjects for Class Seven
        $classSubjects = DB::table('subjects')->where('class_id', $classSeven->id)->get();

        foreach ($branches as $branch) {
            // Clean any old or duplicate schedules for Class Seven in this branch
            DB::table('exam_schedules')
                ->where('branch_id', $branch->id)
                ->where('class_id', $classSeven->id)
                ->delete();

            foreach ($subjectConfigs as $cfg) {
                // Find matching subject
                $subject = $classSubjects->first(function ($s) use ($cfg) {
                    foreach ($cfg['names'] as $name) {
                        if (trim($s->subject_name) === $name) {
                            return true;
                        }
                    }
                    return false;
                });

                if ($subject) {
                    DB::table('exam_schedules')->insert([
                        'branch_id' => $branch->id,
                        'class_id' => $classSeven->id,
                        'subject_id' => $subject->id,
                        'exam_id' => null,
                        'full_marks' => $cfg['full_marks'],
                        'pass_marks' => $cfg['pass_marks'],
                        'ct_marks' => $cfg['ct_marks'],
                        'mcq_marks' => $cfg['mcq_marks'],
                        'written_marks' => $cfg['written_marks'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $classSeven = DB::table('classes')->where('class_name', 'Seven')->first();
        if ($classSeven) {
            DB::table('exam_schedules')->where('class_id', $classSeven->id)->delete();
        }
    }
};
