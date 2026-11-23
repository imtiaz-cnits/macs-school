<?php

namespace App\Services;

/**
 * Immutable Value Object representing the evaluated result of a student's raw biometric punches
 * against a ResolvedStudentSchedule on a specific attendance date.
 */
final class StudentPunchEvaluation
{
    /**
     * @param  list<string>  $validPunches      Sorted H:i:s punches kept after early-floor and double-tap debounce
     * @param  list<string>  $discardedPunches  Out-of-window noise punches discarded below earliest_valid_in
     */
    public function __construct(
        public readonly bool $hasValidPunch,
        public readonly string $status,
        public readonly ?string $checkIn,
        public readonly ?string $checkOut,
        public readonly string $earliestValidIn,
        public readonly array $validPunches,
        public readonly array $discardedPunches,
        public readonly ResolvedStudentSchedule $schedule,
    ) {
    }

    /**
     * Format attendance remarks strictly compatible with `Attendance::getInTimeAttribute()`,
     * `Attendance::getOutTimeAttribute()`, and Daily Attendance UI regex parsing.
     */
    public function toRemarks(bool $simulated = false): string
    {
        if (!$this->hasValidPunch || $this->checkIn === null) {
            return $simulated ? 'Absent (Simulated)' : 'Absent (Biometric Check)';
        }

        $base = $this->checkOut !== null
            ? "Card Swiped (In: {$this->checkIn}, Out: {$this->checkOut})"
            : "Card Swiped (In: {$this->checkIn})";

        return $simulated ? "{$base} [Simulated]" : $base;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'has_valid_punch'    => $this->hasValidPunch,
            'status'             => $this->status,
            'check_in'           => $this->checkIn,
            'check_out'          => $this->checkOut,
            'earliest_valid_in'  => $this->earliestValidIn,
            'valid_punches'      => $this->validPunches,
            'discarded_punches'  => $this->discardedPunches,
            'remarks'            => $this->toRemarks(),
            'schedule_snapshot'  => $this->schedule->toAttendanceSnapshot(),
        ];
    }
}
