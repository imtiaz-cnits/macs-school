<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentAttendanceSchedule;
use App\Models\StudentAttendanceScheduleSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Single Source of Truth for resolving student biometric attendance schedules by Class and Date.
 *
 * Resolution Priority:
 *   1. Active non-template Exam Campaign (`mode = 'exam'`, `is_active = true`, `is_template = false`,
 *      `start_date <= date <= end_date`, matching branch or global) mapped to the target `class_id`,
 *      ordered by branch-specific first, then `priority DESC`, then `id DESC`.
 *   2. Active non-template default Normal School Time (`mode = 'normal'`, `is_default = true`,
 *      `is_active = true`, `is_template = false`, matching branch or global) mapped to `class_id`,
 *      ordered by branch-specific first, then `priority DESC`, then `id DESC`.
 *   3. Legacy fallback (ONLY when no matching schedule exists in the new schedule tables):
 *      adapts `$fallbackStudent->shift ?? Shift::where('type', 'student')->first()`.
 */
class StudentAttendanceScheduleResolver
{
    public const TIMEZONE = 'Asia/Dhaka';

    public function resolveForStudentOnDate(Student $student, Carbon|string $date): ?ResolvedStudentSchedule
    {
        return $this->resolveForClassOnDate(
            (int) $student->class_id,
            $date,
            $student->branch_id !== null ? (int) $student->branch_id : null,
            $student
        );
    }

    public function resolveForClassOnDate(
        int $classId,
        Carbon|string $date,
        ?int $branchId = null,
        ?Student $fallbackStudent = null
    ): ?ResolvedStudentSchedule {
        $dateStr = $this->normalizeDate($date);

        if ($this->scheduleTablesAvailable()) {
            // 1. Active Exam Campaign override for this class on this date
            $examSchedule = StudentAttendanceSchedule::query()
                ->where('mode', StudentAttendanceSchedule::MODE_EXAM)
                ->where('is_active', true)
                ->where('is_template', false)
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->whereDate('start_date', '<=', $dateStr)
                ->whereDate('end_date', '>=', $dateStr)
                ->forBranchOrGlobal($branchId)
                ->whereHas('slotClasses', fn ($q) => $q->where('class_id', $classId))
                ->orderByRaw('CASE WHEN branch_id IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('priority')
                ->orderByDesc('id')
                ->first();

            if ($examSchedule) {
                $slot = $this->findSlotForClass($examSchedule, $classId);
                if ($slot) {
                    return $this->fromScheduleSlot(
                        $examSchedule,
                        $slot,
                        ResolvedStudentSchedule::SOURCE_EXAM_SCHEDULE
                    );
                }
            }

            // 2. Active Default Normal School Time for this class
            $normalSchedule = StudentAttendanceSchedule::query()
                ->defaultNormal()
                ->forBranchOrGlobal($branchId)
                ->whereHas('slotClasses', fn ($q) => $q->where('class_id', $classId))
                ->orderByRaw('CASE WHEN branch_id IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('priority')
                ->orderByDesc('id')
                ->first();

            if ($normalSchedule) {
                $slot = $this->findSlotForClass($normalSchedule, $classId);
                if ($slot) {
                    return $this->fromScheduleSlot(
                        $normalSchedule,
                        $slot,
                        ResolvedStudentSchedule::SOURCE_NORMAL_SCHEDULE
                    );
                }
            }
        }

        // 3. Legacy fallback (only when no matching rule exists in the new schedule tables)
        return $this->resolveLegacyShiftFallback($fallbackStudent);
    }

    /**
     * Build the effective student attendance time-table for a specific date (and optional branch),
     * grouped by resolved slot in academic class order (Play -> Ten).
     *
     * @return array{
     *     date: string,
     *     branch_id: ?int,
     *     active_mode: string,
     *     exam_campaigns: list<array{id: int, name: string, start_date: ?string, end_date: ?string}>,
     *     slots: list<array<string, mixed>>
     * }
     */
    public function getEffectiveScheduleOverview(Carbon|string $date, ?int $branchId = null): array
    {
        $dateStr = $this->normalizeDate($date);
        $classes = Classes::all();
        $academicPosition = [];
        foreach ($classes->values() as $pos => $cls) {
            $academicPosition[(int) $cls->id] = $pos;
        }

        $grouped = [];
        $examCampaigns = [];
        $modesSeen = [];

        foreach ($classes as $classModel) {
            $resolved = $this->resolveForClassOnDate((int) $classModel->id, $dateStr, $branchId);
            if (!$resolved) {
                continue;
            }

            $modesSeen[$resolved->mode] = true;

            if ($resolved->isExamMode() && $resolved->scheduleId !== null) {
                $examCampaigns[$resolved->scheduleId] = [
                    'id'         => $resolved->scheduleId,
                    'name'       => $resolved->scheduleName,
                    'start_date' => $resolved->startDate,
                    'end_date'   => $resolved->endDate,
                ];
            }

            $groupKey = $resolved->slotId !== null
                ? "{$resolved->source}:slot:{$resolved->slotId}"
                : "{$resolved->source}:legacy:{$resolved->slotName}:{$resolved->onTimeStart}:{$resolved->onTimeEnd}";

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = array_merge($resolved->toArray(), [
                    'classes'       => [],
                    'class_summary' => '',
                ]);
            }

            $grouped[$groupKey]['classes'][] = [
                'id'         => (int) $classModel->id,
                'class_name' => (string) $classModel->class_name,
            ];
        }

        $slots = [];
        foreach ($grouped as $slotEntry) {
            $slotEntry['class_summary'] = $this->formatClassRangeSummary($slotEntry['classes'], $academicPosition);
            $slots[] = $slotEntry;
        }

        usort($slots, static function (array $a, array $b): int {
            $shiftOrder = static fn (string $g): int => strtolower($g) === 'morning' ? 0 : 1;
            $cmp = $shiftOrder((string) ($a['shift_group'] ?? '')) <=> $shiftOrder((string) ($b['shift_group'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $timeCmp = strcmp((string) ($a['on_time_start'] ?? ''), (string) ($b['on_time_start'] ?? ''));
            if ($timeCmp !== 0) {
                return $timeCmp;
            }
            return ($a['slot_id'] ?? 0) <=> ($b['slot_id'] ?? 0);
        });

        $activeMode = 'normal';
        if (isset($modesSeen['exam']) && isset($modesSeen['normal'])) {
            $activeMode = 'mixed';
        } elseif (isset($modesSeen['exam'])) {
            $activeMode = 'exam';
        }

        return [
            'date'           => $dateStr,
            'branch_id'      => $branchId,
            'active_mode'    => $activeMode,
            'exam_campaigns' => array_values($examCampaigns),
            'slots'          => $slots,
        ];
    }

    public function scheduleTablesAvailable(): bool
    {
        return Schema::hasTable('student_attendance_schedules')
            && Schema::hasTable('student_attendance_schedule_slots')
            && Schema::hasTable('student_attendance_schedule_slot_classes');
    }

    private function findSlotForClass(
        StudentAttendanceSchedule $schedule,
        int $classId
    ): ?StudentAttendanceScheduleSlot {
        return StudentAttendanceScheduleSlot::query()
            ->where('schedule_id', $schedule->id)
            ->whereHas('slotClasses', fn ($q) => $q->where('class_id', $classId))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    private function fromScheduleSlot(
        StudentAttendanceSchedule $schedule,
        StudentAttendanceScheduleSlot $slot,
        string $source
    ): ResolvedStudentSchedule {
        return new ResolvedStudentSchedule(
            mode: (string) $schedule->mode,
            scheduleId: (int) $schedule->id,
            scheduleName: (string) $schedule->name,
            slotId: (int) $slot->id,
            slotName: (string) $slot->slot_name,
            shiftGroup: (string) $slot->shift_group,
            onTimeStart: $this->normalizeTime((string) $slot->on_time_start),
            onTimeEnd: $this->normalizeTime((string) $slot->on_time_end),
            lateEnd: $this->normalizeTime((string) $slot->late_end),
            absentFrom: $this->normalizeTime((string) $slot->absent_from),
            checkOutFrom: $this->normalizeTime((string) $slot->check_out_from),
            earlyGraceMinutes: max(0, (int) $slot->early_grace_minutes),
            debounceMinutes: max(0, (int) $slot->debounce_minutes),
            minCheckoutGapMinutes: max(0, (int) $slot->min_checkout_gap_minutes),
            source: $source,
            startDate: $schedule->start_date ? $schedule->start_date->format('Y-m-d') : null,
            endDate: $schedule->end_date ? $schedule->end_date->format('Y-m-d') : null,
        );
    }

    private function resolveLegacyShiftFallback(?Student $fallbackStudent): ?ResolvedStudentSchedule
    {
        $shift = $fallbackStudent?->shift;
        if (!$shift && Schema::hasTable('shifts')) {
            $shift = Shift::where('type', 'student')->orderBy('id')->first();
        }

        if (!$shift) {
            return null;
        }

        $onTimeStart = $this->normalizeTime((string) ($shift->in_time_start ?: '08:00:00'));
        $onTimeEnd = $this->normalizeTime((string) ($shift->in_time_end ?: '09:00:00'));
        $lateEnd = $this->normalizeTime((string) ($shift->late_time_end ?: $onTimeEnd));

        if (!empty($shift->absent_time)) {
            $absentFrom = $this->normalizeTime((string) $shift->absent_time);
        } else {
            $absentFrom = Carbon::createFromFormat('H:i:s', $lateEnd, self::TIMEZONE)
                ->addMinute()
                ->setSecond(0)
                ->format('H:i:s');
        }

        $checkOutFrom = $this->normalizeTime((string) ($shift->out_time ?: '12:00:00'));
        $name = (string) ($shift->name ?: $shift->shift_name ?: 'Student Shift');
        $shiftGroup = stripos($name, 'day') !== false
            ? StudentAttendanceScheduleSlot::SHIFT_DAY
            : StudentAttendanceScheduleSlot::SHIFT_MORNING;

        return new ResolvedStudentSchedule(
            mode: StudentAttendanceSchedule::MODE_NORMAL,
            scheduleId: null,
            scheduleName: 'Legacy Shift Fallback',
            slotId: null,
            slotName: $name,
            shiftGroup: $shiftGroup,
            onTimeStart: $onTimeStart,
            onTimeEnd: $onTimeEnd,
            lateEnd: $lateEnd,
            absentFrom: $absentFrom,
            checkOutFrom: $checkOutFrom,
            earlyGraceMinutes: 60,
            debounceMinutes: 10,
            minCheckoutGapMinutes: 15,
            source: ResolvedStudentSchedule::SOURCE_LEGACY_SHIFT,
        );
    }

    private function normalizeDate(Carbon|string $date): string
    {
        if ($date instanceof Carbon) {
            return $date->copy()->timezone(self::TIMEZONE)->toDateString();
        }

        return Carbon::parse($date, self::TIMEZONE)->toDateString();
    }

    private function normalizeTime(string $time): string
    {
        $trimmed = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $trimmed, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
        }

        return Carbon::parse($trimmed, self::TIMEZONE)->format('H:i:s');
    }

    /**
     * Format class names cleanly: contiguous ranges as "Play - Three", non-contiguous as "Play, Five, Ten".
     *
     * @param  list<array{id: int, class_name: string}>  $slotClasses
     * @param  array<int, int>  $academicPosition
     */
    private function formatClassRangeSummary(array $slotClasses, array $academicPosition): string
    {
        $count = count($slotClasses);
        if ($count === 0) {
            return 'No Classes';
        }
        if ($count === 1) {
            return $slotClasses[0]['class_name'];
        }
        if ($count === 2) {
            return "{$slotClasses[0]['class_name']}, {$slotClasses[1]['class_name']}";
        }

        $isContiguous = true;
        for ($i = 1; $i < $count; $i++) {
            $prevPos = $academicPosition[$slotClasses[$i - 1]['id']] ?? null;
            $currPos = $academicPosition[$slotClasses[$i]['id']] ?? null;
            if ($prevPos === null || $currPos === null || $currPos !== $prevPos + 1) {
                $isContiguous = false;
                break;
            }
        }

        if ($isContiguous) {
            return "{$slotClasses[0]['class_name']} - {$slotClasses[$count - 1]['class_name']}";
        }

        return implode(', ', array_column($slotClasses, 'class_name'));
    }
}
