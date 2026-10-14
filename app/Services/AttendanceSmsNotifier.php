<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceSmsNotifier
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Notify guardian of student attendance punch
     *
     * @param Student $student
     * @param Attendance $attendance Persisted Attendance record
     * @param array $times Sorted raw times array for the day e.g. ['08:33:11', '11:44:25']
     * @param string $source Must be 'k60_live' to allow sending
     * @return array Results summary ['check_in' => bool, 'check_out' => bool]
     */
    public function notifyPunch(Student $student, Attendance $attendance, array $times, string $source = 'k60_live'): array
    {
        // 1. Strict real device source check
        if ($source !== 'k60_live') {
            Log::info("AttendanceSmsNotifier: skipped, source is not 'k60_live' (received '{$source}').");
            return ['check_in' => false, 'check_out' => false];
        }

        // If device mode is explicitly configured as simulation, refuse to send guardian notifications
        if (config('services.zkteco.mode') === 'simulation') {
            Log::info("AttendanceSmsNotifier: skipped, device mode is 'simulation'.");
            return ['check_in' => false, 'check_out' => false];
        }

        // 2. Student eligibility
        if ($student->status !== 'Active' || $student->sms_status !== 'Active' || empty($student->guardian_mobile)) {
            return ['check_in' => false, 'check_out' => false];
        }

        // 3. Shift resolution
        $shift = $student->shift;
        if (!$shift) {
            $shift = Shift::where('type', 'student')->first();
        }

        $date = $attendance->attendance_date;
        $attendanceDateFormatted = Carbon::parse($date, 'Asia/Dhaka')->format('d-M-Y');

        $results = ['check_in' => false, 'check_out' => false];

        // 4. Process Check-In
        if (!empty($times)) {
            sort($times);
            $checkInTime = $times[0];
            $eventKeyIn = "att:check_in:{$student->id}:{$date}";

            $timeFormatted = Carbon::parse($checkInTime, 'Asia/Dhaka')->format('h:i A');
            $statusLabel = $attendance->status === 'Late' ? ' (Late)' : '';
            $msg = "Dear Guardian, your child {$student->student_name} has entered the school at {$timeFormatted} on {$attendanceDateFormatted}{$statusLabel}. - MACS School";

            $results['check_in'] = $this->smsService->sendSms(
                $student->guardian_mobile,
                $msg,
                $student->id,
                $eventKeyIn,
                'check_in'
            );
        }

        // 5. Process Check-Out (Shift-Aware & Double-Tap Protected)
        $checkOutTime = $this->classifyCheckOutTime($times, $shift);
        if ($checkOutTime) {
            $eventKeyOut = "att:check_out:{$student->id}:{$date}";
            $outTimeFormatted = Carbon::parse($checkOutTime, 'Asia/Dhaka')->format('h:i A');
            $outMsg = "Dear Guardian, your child {$student->student_name} has left the school at {$outTimeFormatted} on {$attendanceDateFormatted}. - MACS School";

            $results['check_out'] = $this->smsService->sendSms(
                $student->guardian_mobile,
                $outMsg,
                $student->id,
                $eventKeyOut,
                'check_out'
            );
        }

        return $results;
    }

    /**
     * Defensible Check-Out Classification Rule:
     * - Requires at least 2 punches.
     * - The latest punch must be at least 30 minutes after the earliest punch (double-tap cooldown).
     * - If shift defines late_time_end, the punch must occur after late_time_end (cannot check out while entry is still open).
     */
    public function classifyCheckOutTime(array $times, ?Shift $shift = null): ?string
    {
        if (count($times) < 2) {
            return null;
        }

        sort($times);
        $firstPunch = $times[0];
        $latestPunch = end($times);

        $firstCarbon = Carbon::parse($firstPunch, 'Asia/Dhaka');
        $latestCarbon = Carbon::parse($latestPunch, 'Asia/Dhaka');

        // 1. Double-tap / minimum interval check: at least 30 minutes
        if ($firstCarbon->diffInMinutes($latestCarbon) < 30) {
            return null;
        }

        // 2. Shift late_time_end check: cannot leave during the morning arrival window
        if ($shift && !empty($shift->late_time_end)) {
            $latestTimeStr = $latestCarbon->format('H:i:s');
            if ($latestTimeStr <= $shift->late_time_end) {
                return null;
            }
        }

        return $latestPunch;
    }
}
