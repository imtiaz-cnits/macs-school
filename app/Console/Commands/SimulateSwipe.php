<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\StudentAttendanceScheduleResolver;
use App\Services\StudentPunchEvaluator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

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
        if (app()->isProduction()) {
            $this->error('Simulation swipe command is strictly blocked in production.');
            return 1;
        }

        $cardNo = $this->argument('card_number');
        $this->info("Simulating card swipe for Card: {$cardNo}...");

        $student = Student::with(['schoolClass', 'shift'])->where('card_number', $cardNo)->first();
        if (!$student) {
            $this->error("Error: Student with card number {$cardNo} not found in database.");
            return 1;
        }

        $now = Carbon::now('Asia/Dhaka');
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $resolver = app(StudentAttendanceScheduleResolver::class);
        $evaluator = app(StudentPunchEvaluator::class);
        $schedule = $resolver->resolveForStudentOnDate($student, $today);

        $snapshot = [];
        if (
            Schema::hasColumn('attendances', 'schedule_mode')
            && Schema::hasColumn('attendances', 'schedule_slot_id')
            && Schema::hasColumn('attendances', 'schedule_slot_name')
        ) {
            $snapshot = $schedule !== null
                ? $schedule->toAttendanceSnapshot()
                : ['schedule_mode' => null, 'schedule_slot_id' => null, 'schedule_slot_name' => null];
        }

        $existing = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $today)
            ->first();

        if ($existing && str_contains((string) $existing->remarks, 'In:')) {
            // CHECK-OUT SIMULATION (never triggers SMS)
            preg_match('/In:\s*(\d{2}:\d{2}:\d{2})/', (string) $existing->remarks, $inMatches);
            $checkInTime = $inMatches[1] ?? ($schedule?->onTimeStart ?? $currentTime);

            $remarks = "Card Swiped (In: {$checkInTime}, Out: {$currentTime}) [Simulated]";
            $updatePayload = ['remarks' => $remarks];

            if (!empty($snapshot) && $existing->schedule_mode === null) {
                $updatePayload = array_merge($updatePayload, $snapshot);
            }

            $existing->update($updatePayload);

            $this->info("SUCCESS: Simulated CHECK-OUT for {$student->student_name} at {$currentTime}.");
        } else {
            // CHECK-IN SIMULATION using active schedule + evaluator (never triggers SMS)
            $status = 'Present';
            if ($schedule !== null) {
                $evaluation = $evaluator->evaluate([$currentTime], $schedule, $today);
                if ($evaluation->hasValidPunch) {
                    $status = $evaluation->status;
                }
            }

            $remarks = "Card Swiped (In: {$currentTime}) [Simulated]";

            $attributes = array_merge([
                'branch_id'       => $student->branch_id ?? 1,
                'session_year_id' => $student->session_year_id ?? 1,
                'class_id'        => $student->class_id ?? 1,
                'section_id'      => $student->section_id ?? 1,
                'teacher_id'      => null,
                'user_id'         => 1,
                'status'          => $status,
                'remarks'         => $remarks,
            ], $snapshot);

            Attendance::updateOrCreate(
                [
                    'student_id'      => $student->id,
                    'attendance_date' => $today,
                ],
                $attributes
            );

            $this->info("SUCCESS: Simulated CHECK-IN for {$student->student_name} as {$status} at {$currentTime}.");
        }

        return 0;
    }
}
