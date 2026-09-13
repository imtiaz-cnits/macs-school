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
        $classFour = DB::table('classes')->where('class_name', 'Four')->first();
        if (!$classFour) {
            return;
        }

        $branches = DB::table('branches')->get();
        if ($branches->isEmpty()) {
            return;
        }

        // Subject configurations for Class Four
        // Bangla (100), English (100), Math (100), Elementary Science (100),
        // Bangladesh & Global Studies (100), Islam & Moral Education (100),
        // SBA (100), Physical Education (35)
        $subjectConfigs = [
            [
                'names' => ['বাংলা', 'Bangla'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['ইংরেজী', 'English'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
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
                'names' => ['বিজ্ঞান', 'Science', 'Elementary Science'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['বাংলাদেশ ও বিশ্বপরিচয়', 'Bangladesh and Global Studies', 'BGS'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['ইসলাম ও নৈতিক শিক্ষা', 'Islam and Moral Education', 'Islam Education'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 10.00,
                'mcq_marks' => 20.00,
                'written_marks' => 70.00,
            ],
            [
                'names' => ['S.B.A', 'SBA'],
                'full_marks' => 100.00,
                'pass_marks' => 33.00,
                'ct_marks' => 0.00,
                'mcq_marks' => 0.00,
                'written_marks' => 100.00,
            ],
            [
                'names' => ['শারীরিক শিক্ষা', 'Physical Education'],
                'full_marks' => 35.00,
                'pass_marks' => 12.00,
                'ct_marks' => 0.00,
                'mcq_marks' => 0.00,
                'written_marks' => 35.00,
            ],
        ];

        // Fetch subjects for Class Four
        $classSubjects = DB::table('subjects')->where('class_id', $classFour->id)->get();

        foreach ($branches as $branch) {
            // Clean any old or duplicate schedules for Class Four in this branch
            DB::table('exam_schedules')
                ->where('branch_id', $branch->id)
                ->where('class_id', $classFour->id)
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
                        'class_id' => $classFour->id,
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
        $classFour = DB::table('classes')->where('class_name', 'Four')->first();
        if ($classFour) {
            DB::table('exam_schedules')->where('class_id', $classFour->id)->delete();
        }
    }
};
