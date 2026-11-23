<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceSmsNotifier
{
    protected SmsService $smsService;
    protected StudentAttendanceScheduleResolver $scheduleResolver;
    protected StudentPunchEvaluator $punchEvaluator;

    public function __construct(
        SmsService $smsService,
        ?StudentAttendanceScheduleResolver $scheduleResolver = null,
        ?StudentPunchEvaluator $punchEvaluator = null
    ) {
        $this->smsService = $smsService;
        $this->scheduleResolver = $scheduleResolver ?? app(StudentAttendanceScheduleResolver::class);
        $this->punchEvaluator = $punchEvaluator ?? app(StudentPunchEvaluator::class);
    }

    /**
     * Notify guardian of student attendance punch.
     *
     * @param  Student  $student
     * @param  Attendance  $attendance  Persisted Attendance record
     * @param  array<int, string>  $times  Raw punch times array for the day e.g. ['08:33:11', '11:44:25']
     * @param  string  $source  Must be 'k60_live' to allow sending
     * @param  ?ResolvedStudentSchedule  $resolvedSchedule  Optional pre-resolved schedule to avoid redundant lookup
     * @param  ?StudentPunchEvaluation  $evaluation  Optional pre-computed punch evaluation
     * @return array{check_in: bool, check_out: bool}
     */
    public function notifyPunch(
        Student $student,
        Attendance $attendance,
        array $times,
        string $source = 'k60_live',
        ?ResolvedStudentSchedule $resolvedSchedule = null,
        ?StudentPunchEvaluation $evaluation = null
    ): array {
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

        // 2. Student eligibility & Attendance status guard (never send arrival/departure SMS for Absent records)
        if (
            $student->status !== 'Active'
            || $student->sms_status !== 'Active'
            || empty($student->guardian_mobile)
            || !in_array($attendance->status, ['Present', 'Late'], true)
        ) {
            return ['check_in' => false, 'check_out' => false];
        }

        $date = $attendance->attendance_date;
        $attendanceDateFormatted = Carbon::parse($date, 'Asia/Dhaka')->format('d-M-Y');

        // 3. Unified Schedule Resolution & Punch Evaluation
        $schedule = $resolvedSchedule ?? $this->scheduleResolver->resolveForStudentOnDate($student, $date);
        $punchEval = $evaluation;

        if ($punchEval === null && $schedule !== null && !empty($times)) {
            $punchEval = $this->punchEvaluator->evaluate($times, $schedule, $date);
        }

        $checkInTime = null;
        $checkOutTime = null;

        if ($punchEval !== null) {
            if (!$punchEval->hasValidPunch || !in_array($punchEval->status, ['Present', 'Late'], true)) {
                return ['check_in' => false, 'check_out' => false];
            }
            $checkInTime = $punchEval->checkIn;
            $checkOutTime = $punchEval->checkOut;
        } elseif (!empty($times)) {
            sort($times);
            $checkInTime = $times[0];
            $checkOutTime = $this->classifyCheckOutTime($times, $student->shift, null, $date);
        }

        $results = ['check_in' => false, 'check_out' => false];

        // 4. Process Check-In
        if ($checkInTime !== null) {
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

        // 5. Process Check-Out (Schedule-Aware, Debounce & Check-Out Window Protected)
        if ($checkOutTime !== null) {
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
     * Unified Check-Out Classification Rule:
     * - When `$resolvedSchedule` is provided, delegates directly to `StudentPunchEvaluator`
     *   (enforcing `debounce_minutes`, `check_out_from`, and `min_checkout_gap_minutes`).
     * - Backward-compatible legacy signature when called directly with `?Shift $shift` only:
     *   requires >= 2 punches, >= 30 minutes interval, and punch strictly after `$shift->late_time_end`.
     *
     * @param  array<int, string>  $times
     */
    public function classifyCheckOutTime(
        array $times,
        ?Shift $shift = null,
        ?ResolvedStudentSchedule $resolvedSchedule = null,
        Carbon|string|null $date = null
    ): ?string {
        if (count($times) < 2) {
            return null;
        }

        if ($resolvedSchedule !== null) {
            $evalDate = $date ?? Carbon::today('Asia/Dhaka')->toDateString();

            return $this->punchEvaluator->evaluate($times, $resolvedSchedule, $evalDate)->checkOut;
        }

        // Backward-compatible direct Shift-only fallback
        sort($times);
        $firstPunch = $times[0];
        $latestPunch = end($times);

        $firstCarbon = Carbon::parse($firstPunch, 'Asia/Dhaka');
        $latestCarbon = Carbon::parse($latestPunch, 'Asia/Dhaka');

        if ($firstCarbon->diffInMinutes($latestCarbon) < 30) {
            return null;
        }

        if ($shift && !empty($shift->late_time_end)) {
            $latestTimeStr = $latestCarbon->format('H:i:s');
            if ($latestTimeStr <= $shift->late_time_end) {
                return null;
            }
        }

        return $latestPunch;
    }
}
