<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonitorAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor ZKteco biometric device and record card swipes in real time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================");
        $this->info("   MACS SCHOOL - REALTIME BIOMETRIC ATTENDANCE    ");
        $this->info("==================================================");
        
        $zkService = app(ZktecoService::class);

        if ($zkService->getMode() === 'simulation' && app()->isProduction()) {
            $this->error("CRITICAL CONFIGURATION ERROR: ZKTeco biometric device mode is 'simulation' in production.");
            $this->error("Simulation mode is strictly blocked in production to prevent synthetic attendance creation.");
            $this->error("Please configure ZKTECO_MODE=live and verify device network parameters in .env.");
            return 1;
        }

        $this->info("Biometric Device IP: " . $zkService->getIp());
        $this->info("Biometric Device Port: " . $zkService->getPort());
        $this->info("Monitoring Mode: " . $zkService->getMode());
        $this->info("Press Ctrl+C to stop monitoring.");
        $this->info("--------------------------------------------------");
        
        // Define default settings in case student fields are missing
        $branchId = 1;
        $sessionYearId = 1;
        $classId = 1;
        $sectionId = 1;
        $teacherId = null;
        $creatorId = 1;
        
        // Loop persistently
        while (true) {
            $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');
            
            try {
                // Get raw card swipes for today from the device
                $cardSwipes = $zkService->getRawLogsByCard($today);
                
                $this->info("[" . date('H:i:s') . "] Checked device: found " . count($cardSwipes) . " active user logs for today (" . $today . ").");
                if (count($cardSwipes) > 0) {
                    $this->info("  --> Raw keys detected: " . implode(', ', array_keys($cardSwipes)));
                }
                
                foreach ($cardSwipes as $key => $times) {
                    if (empty($key)) continue;
                    
                    $student = null;
                    if (str_starts_with($key, 'id:')) {
                        $studentId = substr($key, 3);
                        $student = Student::find($studentId);
                    } else {
                        $student = Student::where('card_number', $key)->first();
                    }
                    
                    if (!$student) {
                        continue;
                    }
                    
                    // Check if they already have attendance recorded for today
                    $attRecord = Attendance::where('student_id', $student->id)
                        ->where('attendance_date', $today)
                        ->first();
                        
                    sort($times);
                    $checkInTime = $times[0];
                    $checkOutTime = count($times) > 1 ? end($times) : null;
                    
                    $attendanceService = app(\App\Services\AttendanceService::class);
                    $shift = $student->shift;
                    if (!$shift) {
                        $shift = \App\Models\Shift::where('type', 'student')->first();
                    }

                    $status = 'Present';
                    if ($shift) {
                        $punchCarbon = Carbon::parse($today . ' ' . $checkInTime, 'Asia/Dhaka');
                        $status = $attendanceService->calculateStatus($punchCarbon, $shift);
                    }
                    
                    if ($checkOutTime) {
                        $remarks = "Card Swiped (In: {$checkInTime}, Out: {$checkOutTime})";
                    } else {
                        $remarks = "Card Swiped (In: {$checkInTime})";
                    }
                    
                    // Determine if we need to update/insert the record
                    $shouldUpdate = false;
                    if (!$attRecord) {
                        $shouldUpdate = true;
                    } else {
                        if ($attRecord->status === 'Absent') {
                            $shouldUpdate = true;
                        } elseif ($checkOutTime && !str_contains($attRecord->remarks, "Out: {$checkOutTime}")) {
                            $shouldUpdate = true;
                        }
                    }
                    
                    if ($shouldUpdate) {
                        // Insert/Update attendance record
                        $persistedAttendance = Attendance::updateOrCreate(
                            [
                                'student_id'      => $student->id,
                                'attendance_date' => $today,
                            ],
                            [
                                'branch_id'       => $student->branch_id ?? $branchId,
                                'session_year_id' => $student->session_year_id ?? $sessionYearId,
                                'class_id'        => $student->class_id ?? $classId,
                                'section_id'      => $student->section_id ?? $sectionId,
                                'teacher_id'      => $teacherId,
                                'user_id'         => $creatorId,
                                'status'          => $status,
                                'remarks'         => $remarks
                            ]
                        );
                        
                        $this->info("[" . date('H:i:s') . "] PUNCH RECORD UPDATED: Student '{$student->student_name}' (Card: {$key}) remarks set to: {$remarks}.");

                        // Trigger immediate Guardian SMS notification for valid persisted punch
                        $notifier = app(\App\Services\AttendanceSmsNotifier::class);
                        $notifier->notifyPunch($student, $persistedAttendance, $times, 'k60_live');
                    }
                }
            } catch (\Exception $e) {
                $this->error("[" . date('H:i:s') . "] Connection/Read Error: " . $e->getMessage());
            }
            
            // Sleep for 3 seconds before polling again
            sleep(3);
        }
    }
}
