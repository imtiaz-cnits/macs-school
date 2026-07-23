<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SmsLog;
use App\Services\ZktecoService;
use App\Services\SmsService;
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
        $smsService = app(SmsService::class);
        
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
        $teacherId = 1;
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
                        Attendance::updateOrCreate(
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
                        
                        // Send SMS instantly to guardian if SMS is Active
                        if ($student->sms_status === 'Active' && !empty($student->guardian_mobile)) {
                            $attendanceDateFormatted = Carbon::parse($today)->format('d-M-Y');
                            
                            // 1. Check-In SMS
                            $alreadySentToday = SmsLog::where('student_id', $student->id)
                                ->where('message', 'like', "%{$attendanceDateFormatted}%")
                                ->where('message', 'like', '%entered the school%')
                                ->exists();
                                
                            if (!$alreadySentToday) {
                                $timeFormatted = Carbon::parse($checkInTime)->format('h:i A');
                                $statusLabel = $status === 'Late' ? ' (Late)' : '';
                                
                                $msg = "Dear Guardian, your child {$student->student_name} has entered the school at {$timeFormatted} on {$attendanceDateFormatted}{$statusLabel}. - MACS School";
                                $smsService->sendSms($student->guardian_mobile, $msg, $student->id);
                                $this->info("  --> Check-In SMS fired to guardian's number: {$student->guardian_mobile}");
                            }
                            
                            // 2. Check-Out SMS
                            if ($checkOutTime) {
                                $alreadySentOutToday = SmsLog::where('student_id', $student->id)
                                    ->where('message', 'like', "%{$attendanceDateFormatted}%")
                                    ->where('message', 'like', '%left the school%')
                                    ->exists();
                                    
                                if (!$alreadySentOutToday) {
                                    $outTimeFormatted = Carbon::parse($checkOutTime)->format('h:i A');
                                    
                                    $msg = "Dear Guardian, your child {$student->student_name} has left the school at {$outTimeFormatted} on {$attendanceDateFormatted}. - MACS School";
                                    $smsService->sendSms($student->guardian_mobile, $msg, $student->id);
                                    $this->info("  --> Check-Out SMS fired to guardian's number: {$student->guardian_mobile}");
                                }
                            }
                        }
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
