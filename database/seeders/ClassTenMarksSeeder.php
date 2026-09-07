<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\ExamSchedule;

class ClassTenMarksSeeder extends Seeder
{
    /**
     * Seed and map Class Ten marks and schedules to 2nd Term Exam 2026.
     */
    public function run(): void
    {
        // 1. Find or verify 2nd Term Exam 2026
        $exam = Exam::where('name', 'like', '%2nd%')
            ->where('name', 'like', '%Term%')
            ->first() ?? Exam::find(2);

        if (!$exam) {
            $exam = Exam::firstOrCreate(
                ['name' => '2nd Term Exam 2026'],
                ['session_year_id' => 1, 'status' => 'upcoming']
            );
        }

        $examId = $exam->id;

        // 2. If any Class 10 marks exist under exam_id = 5 (Pre-Model), update them to 2nd Term Exam
        Mark::where('class_id', 12)
            ->where('exam_id', 5)
            ->update(['exam_id' => $examId]);

        ExamSchedule::where('class_id', 12)
            ->where('exam_id', 5)
            ->update(['exam_id' => $examId]);

        // 3. Run the SQL dump to ensure all 300 records and schedules are in DB
        $sqlPath = database_path('seeders/class_ten_marks_dump.sql');
        if (File::exists($sqlPath)) {
            $sql = File::get($sqlPath);
            DB::unprepared($sql);
        }

        // 4. Verify total marks for Class 10 under 2nd Term Exam
        $totalMarks = Mark::where('class_id', 12)->where('exam_id', $examId)->count();
        $totalSchedules = ExamSchedule::where('class_id', 12)->where('exam_id', $examId)->count();

        $this->command->info("Successfully processed {$totalMarks} marks records and {$totalSchedules} schedules for Class Ten ({$exam->name}).");
    }
}
