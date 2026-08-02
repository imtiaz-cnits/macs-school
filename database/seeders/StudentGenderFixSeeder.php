<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentGenderFixSeeder extends Seeder
{
    public function run(): void
    {
        $femaleKeywords = ['mst.', 'miss', 'begum', 'khatun', 'jannat', 'hafsa', 'sultana', 'akhter', 'afrin', 'nusrat', 'binte', 'bint', 'maria', 'tisha', 'mimi', 'poly', 'akter', 'ferdous', 'saima', 'baishakhi', 'aisha', 'ayesha', 'maryam', 'mehjabin', 'medha', 'yasmin', 'nazifa', 'tania', 'anika', 'meem', 'richi', 'ajmira', 'sonali', 'halima', 'tapsia', 'bushra', 'tahmina', 'sanjida', 'riya', 'wafia', 'aliza', 'tithi', 'parveen', 'sadia', 'tanni', 'priya', 'nice', 'zakia', 'lamia', 'ruqaiya', 'tasmia', 'tama', 'rumi', 'josna', 'naeem', 'nisa', 'richy', 'Richi'];

        $maleKeywords = ['md.', 'mohammad', 'muhammad', 'hasan', 'hassan', 'islam', 'ahmed', 'rahman', 'sarkar', 'hossain', 'ali', 'khan', 'sabbir', 'sajid', 'saifi', 'tushar', 'sihaab', 'shihab', 'hamim', 'shafiqul', 'simon', 'rifat', 'yassin', 'arafat', 'belal', 'saif', 'rokanuzzaman', 'hamza', 'rohan', 'tanzir', 'samiul', 'muktar', 'turjoy', 'nafiz', 'chayan', 'zubair', 'siam', 'gifari', 'mahim', 'masum', 'sadad', 'naeem', 'minhajul', 'rifad', 'nirab', 'mostafizur', 'tanzil', 'sohanur', 'sunny', 'redwan', 'tausin', 'farzan', 'mizan', 'yamin', 'junaid', 'rupak', 'sarwar', 'muhin', 'tanzimul', 'ehsan', 'saad', 'shakib', 'muktadir', 'sourav', 'abir', 'rafin', 'rakibul', 'salman', 'kawsar', 'suwaif', 'saiful', 'azizul', 'adnan', 'jihad', 'arid', 'alif', 'parvez', 'habibur', 'mehdi', 'tauseef', 'tanvir', 'walid', 'niloy', 'sajib', 'tanjid', 'badhan'];

        DB::transaction(function() use ($femaleKeywords, $maleKeywords) {
            // chunk() ব্যবহার করা হয়েছে যাতে লাইভ সার্ভারে মেমরি লিমিট এরর না আসে
            $updatedCount = 0;
            
            Student::chunk(200, function ($students) use ($femaleKeywords, $maleKeywords, &$updatedCount) {
                foreach ($students as $student) {
                    $nameLower = strtolower($student->student_name);
                    $newGender = null;

                    foreach ($femaleKeywords as $keyword) {
                        if (str_contains($nameLower, $keyword)) {
                            $newGender = 'Female';
                            break;
                        }
                    }

                    if (empty($newGender)) {
                        foreach ($maleKeywords as $keyword) {
                            if (str_contains($nameLower, $keyword)) {
                                $newGender = 'Male';
                                break;
                            }
                        }
                    }

                    if ($newGender && $student->gender !== $newGender) {
                        $student->update(['gender' => $newGender]);
                        $updatedCount++;
                    }
                }
            });

            $this->command->info("Production Sync Complete: Successfully updated {$updatedCount} students' gender.");
        });
    }
}