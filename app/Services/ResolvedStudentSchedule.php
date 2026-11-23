<?php

namespace App\Services;

/**
 * Immutable Value Object representing the resolved student biometric attendance schedule
 * for a given class and date.
 */
final class ResolvedStudentSchedule
{
    public const SOURCE_EXAM_SCHEDULE   = 'exam_schedule';
    public const SOURCE_NORMAL_SCHEDULE = 'normal_schedule';
    public const SOURCE_LEGACY_SHIFT    = 'legacy_shift';

    public function __construct(
        public readonly string $mode,
        public readonly ?int $scheduleId,
        public readonly string $scheduleName,
        public readonly ?int $slotId,
        public readonly string $slotName,
        public readonly string $shiftGroup,
        public readonly string $onTimeStart,
        public readonly string $onTimeEnd,
        public readonly string $lateEnd,
        public readonly string $absentFrom,
        public readonly string $checkOutFrom,
        public readonly int $earlyGraceMinutes,
        public readonly int $debounceMinutes,
        public readonly int $minCheckoutGapMinutes,
        public readonly string $source,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
    ) {
    }

    public function isExamMode(): bool
    {
        return $this->mode === 'exam';
    }

    /**
     * Snapshot payload suitable for storing on `attendances` rows.
     *
     * @return array{schedule_mode: string, schedule_slot_id: ?int, schedule_slot_name: string}
     */
    public function toAttendanceSnapshot(): array
    {
        return [
            'schedule_mode'      => $this->mode,
            'schedule_slot_id'   => $this->slotId,
            'schedule_slot_name' => $this->slotName,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mode'                     => $this->mode,
            'schedule_id'              => $this->scheduleId,
            'schedule_name'            => $this->scheduleName,
            'slot_id'                  => $this->slotId,
            'slot_name'                => $this->slotName,
            'shift_group'              => $this->shiftGroup,
            'on_time_start'            => $this->onTimeStart,
            'on_time_end'              => $this->onTimeEnd,
            'late_end'                 => $this->lateEnd,
            'absent_from'              => $this->absentFrom,
            'check_out_from'           => $this->checkOutFrom,
            'early_grace_minutes'      => $this->earlyGraceMinutes,
            'debounce_minutes'         => $this->debounceMinutes,
            'min_checkout_gap_minutes' => $this->minCheckoutGapMinutes,
            'source'                   => $this->source,
            'start_date'               => $this->startDate,
            'end_date'                 => $this->endDate,
        ];
    }
}
