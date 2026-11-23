<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Shared Single Source of Truth for processing student RFID / biometric punches across
 * both real-time monitoring (`MonitorAttendance`) and manual sync (`AttendanceController::syncBiometric`).
 *
 * Guarantees:
 * - Identical device-key resolution (`student:{id}` -> `card:{no}` -> `{no}` -> `id:{uid}`) with per-student deduplication.
 * - Date-aware schedule resolution in `Asia/Dhaka` via `StudentAttendanceScheduleResolver`.
 * - Unified punch evaluation (noise floor, debounce, :59 boundaries, check_out_from gating) via `StudentPunchEvaluator`.
 * - Consistent attendance persistence with schedule snapshot columns (`schedule_mode`, `schedule_slot_id`, `schedule_slot_name`).
 * - Unified Guardian SMS eligibility: ONLY `Present` and `Late` trigger Check-In / Check-Out SMS; `Absent` never does.
 */
class StudentBiometricAttendanceProcessor
{
    public const TIMEZONE = 'Asia/Dhaka';

    public function __construct(
        private readonly StudentAttendanceScheduleResolver $scheduleResolver,
        private readonly StudentPunchEvaluator $punchEvaluator,
        private readonly AttendanceSmsNotifier $smsNotifier,
    ) {
    }

    /**
     * Extract and deduplicate raw device punches per `student_id` from `ZktecoService::getRawLogsByCard()`.
     *
     * Supports all 4 key formats in priority order:
     *   1. `student:{id}` (registry-resolved student PIN / RFID)
     *   2. `card:{cardNo}` (registry-resolved or legacy card key)
     *   3. `{cardNo}` (raw card key for legacy & test fixtures)
     *   4. `id:{uid}` (legacy student ID fallback, excluding reserved staff PIN 569)
     *
     * @param  array<string, array<int, string>>  $rawDeviceLogs
     * @return array<int, list<string>>  Map of `student_id => unique sorted raw timestamp strings`
     */
    public function extractStudentPunches(array $rawDeviceLogs): array
    {
        /** @var array<int, array<string, true>> $byStudent */
        $byStudent = [];
        /** @var array<string, ?int> $cardToStudentCache */
        $cardToStudentCache = [];
        /** @var array<int, bool> $studentExistsCache */
        $studentExistsCache = [];

        $appendTimes = static function (int $studentId, mixed $times) use (&$byStudent): void {
            if (!is_array($times)) {
                return;
            }
            foreach ($times as $t) {
                $str = trim((string) $t);
                if ($str !== '') {
                    $byStudent[$studentId][$str] = true;
                }
            }
        };

        $studentExists = static function (int $studentId) use (&$studentExistsCache): bool {
            if ($studentId <= 0) {
                return false;
            }
            if (!array_key_exists($studentId, $studentExistsCache)) {
                $studentExistsCache[$studentId] = Student::where('id', $studentId)->exists();
            }

            return $studentExistsCache[$studentId];
        };

        $resolveByCard = static function (string $cardNo) use (&$cardToStudentCache): ?int {
            $cardNo = trim($cardNo);
            if ($cardNo === '') {
                return null;
            }
            if (!array_key_exists($cardNo, $cardToStudentCache)) {
                $foundId = Student::where('card_number', $cardNo)->value('id');
                $cardToStudentCache[$cardNo] = $foundId !== null ? (int) $foundId : null;
            }

            return $cardToStudentCache[$cardNo];
        };

        // Pass 1: Explicit registry student keys (`student:{id}`)
        foreach ($rawDeviceLogs as $key => $times) {
            $keyStr = (string) $key;
            if (str_starts_with($keyStr, 'student:')) {
                $studentId = (int) substr($keyStr, 8);
                if ($studentExists($studentId)) {
                    $appendTimes($studentId, $times);
                }
            }
        }

        // Pass 2: Prefixed card keys (`card:{cardNo}`)
        foreach ($rawDeviceLogs as $key => $times) {
            $keyStr = (string) $key;
            if (str_starts_with($keyStr, 'card:')) {
                $studentId = $resolveByCard(substr($keyStr, 5));
                if ($studentId !== null) {
                    $appendTimes($studentId, $times);
                }
            }
        }

        // Pass 3: Raw card keys (no prefix)
        foreach ($rawDeviceLogs as $key => $times) {
            $keyStr = (string) $key;
            if (
                $keyStr === ''
                || str_starts_with($keyStr, 'student:')
                || str_starts_with($keyStr, 'card:')
                || str_starts_with($keyStr, 'id:')
            ) {
                continue;
            }
            $studentId = $resolveByCard($keyStr);
            if ($studentId !== null) {
                $appendTimes($studentId, $times);
            }
        }

        // Pass 4: Legacy numeric ID keys (`id:{uid}`, excluding reserved staff PIN 569)
        foreach ($rawDeviceLogs as $key => $times) {
            $keyStr = (string) $key;
            if (str_starts_with($keyStr, 'id:')) {
                $studentId = (int) substr($keyStr, 3);
                if ($studentId !== 569 && $studentExists($studentId)) {
                    $appendTimes($studentId, $times);
                }
            }
        }

        $result = [];
        foreach ($byStudent as $studentId => $timeSet) {
            $list = array_keys($timeSet);
            sort($list);
            $result[$studentId] = $list;
        }

        return $result;
    }

    /**
     * Resolve and deduplicate all raw punch timestamps matching a specific Student from device logs.
     *
     * @param  array<string, array<int, string>>  $rawDeviceLogs
     * @return list<string>
     */
    public function resolveTimesForStudent(Student $student, array $rawDeviceLogs): array
    {
        $collected = [];

        $addAll = static function (mixed $times) use (&$collected): void {
            if (!is_array($times)) {
                return;
            }
            foreach ($times as $t) {
                $str = trim((string) $t);
                if ($str !== '') {
                    $collected[$str] = true;
                }
            }
        };

        // 1. Registry-resolved student ID key
        if (isset($rawDeviceLogs["student:{$student->id}"])) {
            $addAll($rawDeviceLogs["student:{$student->id}"]);
        }

        // 2. Registry-resolved card key
        if (!empty($student->card_number) && isset($rawDeviceLogs["card:{$student->card_number}"])) {
            $addAll($rawDeviceLogs["card:{$student->card_number}"]);
        }

        // 3. Raw card key (legacy / test fixtures)
        if (!empty($student->card_number) && isset($rawDeviceLogs[$student->card_number])) {
            $addAll($rawDeviceLogs[$student->card_number]);
        }

        // 4. Legacy raw ID key (excluding reserved staff PIN 569)
        if ((int) $student->id !== 569 && isset($rawDeviceLogs["id:{$student->id}"])) {
            $addAll($rawDeviceLogs["id:{$student->id}"]);
        }

        $list = array_keys($collected);
        sort($list);

        return $list;
    }

    /**
     * Evaluate and persist attendance for a single student who has raw punch timestamps on `$date`.
     *
     * @param  list<string>  $rawTimestamps
     * @param  array<string, mixed>  $options
     * @return array{
     *     persisted: bool,
     *     skipped_noise: bool,
     *     status: string,
     *     remarks: string,
     *     attendance: ?Attendance,
     *     schedule: ?ResolvedStudentSchedule,
     *     evaluation: ?StudentPunchEvaluation,
     *     sms_eligible: bool
     * }
     */
    public function processStudentPunches(
        Student $student,
        array $rawTimestamps,
        Carbon|string $date,
        array $options = []
    ): array {
        $dateStr = $this->normalizeDate($date);
        $onlyIfChanged = (bool) ($options['only_if_changed'] ?? false);
        $teacherId = $options['teacher_id'] ?? null;
        $creatorId = (int) ($options['user_id'] ?? 1);

        $schedule = $this->scheduleResolver->resolveForStudentOnDate($student, $dateStr);
        $evaluation = null;

        if ($schedule !== null) {
            $evaluation = $this->punchEvaluator->evaluate($rawTimestamps, $schedule, $dateStr);

            if (!$evaluation->hasValidPunch) {
                return [
                    'persisted'     => false,
                    'skipped_noise' => true,
                    'status'        => 'Absent',
                    'remarks'       => $evaluation->toRemarks(),
                    'attendance'    => null,
                    'schedule'      => $schedule,
                    'evaluation'    => $evaluation,
                    'sms_eligible'  => false,
                ];
            }

            $status = $evaluation->status;
            $remarks = $evaluation->toRemarks();
        } else {
            // Bare-schema fallback (no schedules and no legacy shifts configured, e.g., minimal unit/feature fixtures)
            $cleanTimes = array_values(array_unique(array_filter(array_map('trim', $rawTimestamps))));
            sort($cleanTimes);

            if (empty($cleanTimes)) {
                return [
                    'persisted'     => false,
                    'skipped_noise' => true,
                    'status'        => 'Absent',
                    'remarks'       => 'Absent (Biometric Check)',
                    'attendance'    => null,
                    'schedule'      => null,
                    'evaluation'    => null,
                    'sms_eligible'  => false,
                ];
            }

            $checkIn = $cleanTimes[0];
            $checkOut = null;
            if (count($cleanTimes) >= 2) {
                $firstCarbon = Carbon::parse("{$dateStr} {$checkIn}", self::TIMEZONE);
                $lastStr = $cleanTimes[count($cleanTimes) - 1];
                $lastCarbon = Carbon::parse("{$dateStr} {$lastStr}", self::TIMEZONE);
                if (($lastCarbon->getTimestamp() - $firstCarbon->getTimestamp()) >= (30 * 60)) {
                    $checkOut = $lastStr;
                }
            }

            $status = 'Present';
            $remarks = $checkOut !== null
                ? "Card Swiped (In: {$checkIn}, Out: {$checkOut})"
                : "Card Swiped (In: {$checkIn})";
        }

        $existing = Attendance::where('student_id', $student->id)
            ->where('attendance_date', $dateStr)
            ->first();

        if ($onlyIfChanged && $existing !== null && !$this->shouldUpdateExisting($existing, $status, $remarks, $schedule)) {
            return [
                'persisted'     => false,
                'skipped_noise' => false,
                'status'        => $existing->status,
                'remarks'       => (string) $existing->remarks,
                'attendance'    => $existing,
                'schedule'      => $schedule,
                'evaluation'    => $evaluation,
                'sms_eligible'  => false,
            ];
        }

        $attributes = [
            'branch_id'       => $student->branch_id ?? ($options['fallback_branch_id'] ?? 1),
            'session_year_id' => $student->session_year_id ?? ($options['fallback_session_year_id'] ?? 1),
            'class_id'        => $student->class_id ?? ($options['fallback_class_id'] ?? 1),
            'section_id'      => $student->section_id ?? ($options['fallback_section_id'] ?? 1),
            'teacher_id'      => $teacherId,
            'user_id'         => $creatorId,
            'status'          => $status,
            'remarks'         => $remarks,
        ];

        if ($this->hasSnapshotColumns()) {
            $snapshot = $schedule !== null
                ? $schedule->toAttendanceSnapshot()
                : ['schedule_mode' => null, 'schedule_slot_id' => null, 'schedule_slot_name' => null];
            $attributes = array_merge($attributes, $snapshot);
        }

        $persisted = Attendance::updateOrCreate(
            [
                'student_id'      => $student->id,
                'attendance_date' => $dateStr,
            ],
            $attributes
        );

        return [
            'persisted'     => true,
            'skipped_noise' => false,
            'status'        => $status,
            'remarks'       => $remarks,
            'attendance'    => $persisted,
            'schedule'      => $schedule,
            'evaluation'    => $evaluation,
            'sms_eligible'  => in_array($status, ['Present', 'Late'], true),
        ];
    }

    /**
     * Persist an Absent record for a student with zero valid biometric punches on `$date` (manual sync workflow).
     *
     * @param  array<string, mixed>  $options
     */
    public function markStudentAbsent(
        Student $student,
        Carbon|string $date,
        array $options = []
    ): Attendance {
        $dateStr = $this->normalizeDate($date);
        $schedule = $this->scheduleResolver->resolveForStudentOnDate($student, $dateStr);

        $attributes = [
            'branch_id'       => $student->branch_id ?? ($options['fallback_branch_id'] ?? 1),
            'session_year_id' => $student->session_year_id ?? ($options['fallback_session_year_id'] ?? 1),
            'class_id'        => $student->class_id ?? ($options['fallback_class_id'] ?? 1),
            'section_id'      => $student->section_id ?? ($options['fallback_section_id'] ?? 1),
            'teacher_id'      => $options['teacher_id'] ?? null,
            'user_id'         => (int) ($options['user_id'] ?? 1),
            'status'          => 'Absent',
            'remarks'         => 'Absent (Biometric Check)',
        ];

        if ($this->hasSnapshotColumns()) {
            $snapshot = $schedule !== null
                ? $schedule->toAttendanceSnapshot()
                : ['schedule_mode' => null, 'schedule_slot_id' => null, 'schedule_slot_name' => null];
            $attributes = array_merge($attributes, $snapshot);
        }

        return Attendance::updateOrCreate(
            [
                'student_id'      => $student->id,
                'attendance_date' => $dateStr,
            ],
            $attributes
        );
    }

    /**
     * Dispatch guardian Check-In / Check-Out SMS strictly for persisted `Present` or `Late` attendance.
     *
     * @param  list<string>  $rawTimestamps
     * @return array{check_in: bool, check_out: bool}
     */
    public function dispatchGuardianSms(
        Student $student,
        Attendance $attendance,
        array $rawTimestamps,
        ?ResolvedStudentSchedule $schedule = null,
        ?StudentPunchEvaluation $evaluation = null,
        string $source = 'k60_live'
    ): array {
        if (!in_array($attendance->status, ['Present', 'Late'], true)) {
            return ['check_in' => false, 'check_out' => false];
        }

        if ($evaluation !== null && !in_array($evaluation->status, ['Present', 'Late'], true)) {
            return ['check_in' => false, 'check_out' => false];
        }

        return $this->smsNotifier->notifyPunch(
            $student,
            $attendance,
            $rawTimestamps,
            $source,
            $schedule,
            $evaluation
        );
    }

    private function shouldUpdateExisting(
        Attendance $existing,
        string $newStatus,
        string $newRemarks,
        ?ResolvedStudentSchedule $schedule
    ): bool {
        if ($existing->status !== $newStatus || (string) $existing->remarks !== $newRemarks) {
            return true;
        }

        if ($schedule !== null && $this->hasSnapshotColumns()) {
            $snapshot = $schedule->toAttendanceSnapshot();
            if (
                $existing->schedule_mode !== $snapshot['schedule_mode']
                || $existing->schedule_slot_id !== $snapshot['schedule_slot_id']
                || $existing->schedule_slot_name !== $snapshot['schedule_slot_name']
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasSnapshotColumns(): bool
    {
        return Schema::hasColumn('attendances', 'schedule_mode')
            && Schema::hasColumn('attendances', 'schedule_slot_id')
            && Schema::hasColumn('attendances', 'schedule_slot_name');
    }

    private function normalizeDate(Carbon|string $date): string
    {
        if ($date instanceof Carbon) {
            return $date->copy()->timezone(self::TIMEZONE)->toDateString();
        }

        return Carbon::parse($date, self::TIMEZONE)->toDateString();
    }
}
