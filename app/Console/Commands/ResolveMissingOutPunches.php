<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResolveMissingOutPunches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:resolve-missing-out-punches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve missing check-out punches for student and staff attendance records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = \Carbon\Carbon::today('Asia/Dhaka')->format('Y-m-d');
        
        $this->info("Resolving missing check-out punches for date: {$today}");

        // 1. Students (where status is Present/Late but remarks do not contain "Out:")
        $studentRecords = \App\Models\Attendance::where('attendance_date', $today)
            ->whereIn('status', ['Present', 'Late'])
            ->where('remarks', 'not like', '%Out: %')
            ->get();

        $studentCount = 0;
        foreach ($studentRecords as $record) {
            $record->update([
                'status' => 'Pending',
                'remarks' => $record->remarks . ' | [Resolve: Missing Out Punch]'
            ]);
            $studentCount++;
        }
        $this->info("Resolved {$studentCount} student records to 'Pending'.");

        // 2. Staff / Teachers (where check_in is set but check_out is null)
        $staffRecords = \App\Models\StaffAttendance::where('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->whereIn('status', ['Present', 'Late'])
            ->get();

        $staffCount = 0;
        foreach ($staffRecords as $record) {
            $record->update([
                'status' => 'Pending',
                'remarks' => ($record->remarks ? $record->remarks . ' | ' : '') . '[Resolve: Missing Out Punch]'
            ]);
            $staffCount++;
        }
        $this->info("Resolved {$staffCount} staff records to 'Pending'.");
    }
}
