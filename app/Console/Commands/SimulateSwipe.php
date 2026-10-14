<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;

class SimulateSwipe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:simulate-swipe {card_number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate a real-time card swipe of a student on the device';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cardNo = $this->argument('card_number');
        $this->info("Simulating card swipe for Card: {$cardNo}...");

        $student = Student::where('card_number', $cardNo)->first();
        if (!$student) {
            $this->error("Error: Student with card number {$cardNo} not found in database.");
            return 1;
        }

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');
        $currentTime = Carbon::now('Asia/Dhaka')->format('H:i:s');
        $timeFormatted = Carbon::now('Asia/Dhaka')->format('h:i A');
        $attendanceDateFormatted = Carbon::parse($today)->format('d-M-Y');
        
        $existing = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today)
            ->first();
            
        if ($existing && in_array($existing->status, ['Present', 'Late'])) {
            // CHECK-OUT SIMULATION
            preg_match('/In:\s*(\d{2}:\d{2}:\d{2})/', $existing->remarks, $inMatches);
            $checkInTime = isset($inMatches[1]) ? $inMatches[1] : '08:30:00';
            
            $remarks = "Card Swiped (In: {$checkInTime}, Out: {$currentTime}) [Simulated]";
            $existing->update(['remarks' => $remarks]);
            
            $this->info("SUCCESS: Simulated CHECK-OUT for {$student->student_name} at {$currentTime}.");
        } else {
            // CHECK-IN SIMULATION
            $status = 'Present';
            if ($currentTime > '09:00:00') {
                $status = 'Late';
            }
            $remarks = "Card Swiped (In: {$currentTime}) [Simulated]";
            
            Attendance::updateOrCreate(
                [
                    'student_id'      => $student->id,
                    'attendance_date' => $today,
                ],
                [
                    'branch_id'       => $student->branch_id ?? 1,
                    'session_year_id' => $student->session_year_id ?? 1,
                    'class_id'        => $student->class_id ?? 1,
                    'section_id'      => $student->section_id ?? 1,
                    'teacher_id'      => null,
                    'user_id'         => 1,
                    'status'          => $status,
                    'remarks'         => $remarks
                ]
            );
            
            $this->info("SUCCESS: Simulated CHECK-IN for {$student->student_name} as {$status} at {$currentTime}.");
        }

        return 0;
    }
}
