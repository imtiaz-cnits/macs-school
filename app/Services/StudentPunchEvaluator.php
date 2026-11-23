<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Single Source of Truth for evaluating raw student RFID punches against a ResolvedStudentSchedule.
 *
 * Approved Policy Rules (Asia/Dhaka timezone):
 *   1. Hard Noise Floor: 06:00:00.
 *      `earliest_valid_in = max('06:00:00', on_time_start - early_grace_minutes)`.
 *      Punches strictly before `earliest_valid_in` are discarded as out-of-window noise.
 *   2. Double-Tap Debounce: After ascending sort, collapse any consecutive punch whose gap
 *      from the previous kept punch is `< debounce_minutes`.
 *   3. Exact Minute/Second Boundaries (no :01..:59 gap):
 *      - `late_start = on_time_end + 1 minute` (seconds = 00).
 *      - Present: `checkIn >= earliest_valid_in && checkIn < late_start` (e.g., 08:15:59 = Present).
 *      - Late:    `checkIn >= late_start && checkIn < absent_from`       (e.g., 08:16:00..08:30:59 = Late).
 *      - Absent:  `checkIn >= absent_from`                               (e.g., >= 08:31:00 = Absent).
 *   4. Check-Out Qualification: Valid ONLY when ALL three hold:
 *      - At least 2 distinct punches remain after debounce.
 *      - Latest punch `>= check_out_from`.
 *      - `latestPunch - checkIn >= min_checkout_gap_minutes`.
 *   5. Late-Absent Traceability: If `checkIn >= absent_from`, status stays `'Absent'` even if a
 *      valid check-out occurs later; both `checkIn` and `checkOut` are preserved for safety remarks.
 */
class StudentPunchEvaluator
{
    public const TIMEZONE = 'Asia/Dhaka';
    public const HARD_NOISE_FLOOR = '06:00:00';

    /**
     * @param  array<int, string|Carbon>  $rawTimestamps
     */
    public function evaluate(
        array $rawTimestamps,
        ResolvedStudentSchedule $schedule,
        Carbon|string $date
    ): StudentPunchEvaluation {
        $dateStr = $date instanceof Carbon
            ? $date->copy()->timezone(self::TIMEZONE)->toDateString()
            : Carbon::parse($date, self::TIMEZONE)->toDateString();

        $hardFloor = $this->anchorTime($dateStr, self::HARD_NOISE_FLOOR);
        $onTimeStart = $this->anchorTime($dateStr, $schedule->onTimeStart);
        $graceFloor = $onTimeStart->copy()->subMinutes(max(0, $schedule->earlyGraceMinutes));
        $earliestValidIn = $graceFloor->greaterThan($hardFloor) ? $graceFloor : $hardFloor;

        $validParsed = [];
        $discarded = [];

        foreach ($rawTimestamps as $raw) {
            $punch = $this->parsePunchOnDate($raw, $dateStr);
            if ($punch === null) {
                continue;
            }

            if ($punch->lessThan($earliestValidIn)) {
                $discarded[] = $punch->format('H:i:s');
                continue;
            }

            $validParsed[] = $punch;
        }

        if (empty($validParsed)) {
            return new StudentPunchEvaluation(
                hasValidPunch: false,
                status: 'Absent',
                checkIn: null,
                checkOut: null,
                earliestValidIn: $earliestValidIn->format('H:i:s'),
                validPunches: [],
                discardedPunches: $discarded,
                schedule: $schedule,
            );
        }

        // Ascending chronological sort
        usort($validParsed, static fn (Carbon $a, Carbon $b): int => $a->getTimestamp() <=> $b->getTimestamp());

        // Double-tap debounce: collapse consecutive punches within < debounce_minutes of previous kept punch
        $debounceSeconds = max(0, $schedule->debounceMinutes) * 60;
        $kept = [$validParsed[0]];

        for ($i = 1, $total = count($validParsed); $i < $total; $i++) {
            $candidate = $validParsed[$i];
            $prevKept = $kept[count($kept) - 1];

            if (($candidate->getTimestamp() - $prevKept->getTimestamp()) < $debounceSeconds) {
                continue;
            }

            $kept[] = $candidate;
        }

        $checkInCarbon = $kept[0];
        $status = $this->classifyCheckInStatus($checkInCarbon, $schedule, $dateStr);

        $checkOut = null;
        if (count($kept) >= 2) {
            $latestCarbon = $kept[count($kept) - 1];

            if ($schedule->source === ResolvedStudentSchedule::SOURCE_LEGACY_SHIFT) {
                // Backward-compatible legacy Shift contract (only used when no student_attendance_schedules exist):
                // requires >= 30 min stay after check-in and strictly after late_end.
                $lateEndCarbon = $this->anchorTime($dateStr, $schedule->lateEnd);
                if (
                    $latestCarbon->greaterThan($lateEndCarbon)
                    && ($latestCarbon->getTimestamp() - $checkInCarbon->getTimestamp()) >= (30 * 60)
                ) {
                    $checkOut = $latestCarbon->format('H:i:s');
                }
            } else {
                $checkOutFrom = $this->anchorTime($dateStr, $schedule->checkOutFrom);
                $minGapSeconds = max(0, $schedule->minCheckoutGapMinutes) * 60;

                if (
                    $latestCarbon->greaterThanOrEqualTo($checkOutFrom)
                    && ($latestCarbon->getTimestamp() - $checkInCarbon->getTimestamp()) >= $minGapSeconds
                ) {
                    $checkOut = $latestCarbon->format('H:i:s');
                }
            }
        }

        return new StudentPunchEvaluation(
            hasValidPunch: true,
            status: $status,
            checkIn: $checkInCarbon->format('H:i:s'),
            checkOut: $checkOut,
            earliestValidIn: $earliestValidIn->format('H:i:s'),
            validPunches: array_map(static fn (Carbon $c): string => $c->format('H:i:s'), $kept),
            discardedPunches: $discarded,
            schedule: $schedule,
        );
    }

    private function classifyCheckInStatus(
        Carbon $checkIn,
        ResolvedStudentSchedule $schedule,
        string $dateStr
    ): string {
        $onTimeEnd = $this->anchorTime($dateStr, $schedule->onTimeEnd);
        $lateStart = $onTimeEnd->copy()->addMinute()->setSecond(0);
        $absentFrom = $this->anchorTime($dateStr, $schedule->absentFrom);

        if ($checkIn->lessThan($lateStart)) {
            return 'Present';
        }

        if ($checkIn->lessThan($absentFrom)) {
            return 'Late';
        }

        return 'Absent';
    }

    private function anchorTime(string $dateStr, string $timeStr): Carbon
    {
        $trimmed = trim($timeStr);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $trimmed, $m)) {
            $normalized = sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);

            return Carbon::createFromFormat('Y-m-d H:i:s', "{$dateStr} {$normalized}", self::TIMEZONE);
        }

        $parsed = Carbon::parse($trimmed, self::TIMEZONE);

        return Carbon::createFromFormat('Y-m-d H:i:s', "{$dateStr} " . $parsed->format('H:i:s'), self::TIMEZONE);
    }

    private function parsePunchOnDate(mixed $raw, string $dateStr): ?Carbon
    {
        if ($raw instanceof Carbon) {
            $local = $raw->copy()->timezone(self::TIMEZONE);

            return Carbon::createFromFormat('Y-m-d H:i:s', "{$dateStr} " . $local->format('H:i:s'), self::TIMEZONE);
        }

        if (!is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $trimmed, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            $s = isset($m[3]) ? (int) $m[3] : 0;
            if ($h > 23 || $i > 59 || $s > 59) {
                return null;
            }

            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                sprintf('%s %02d:%02d:%02d', $dateStr, $h, $i, $s),
                self::TIMEZONE
            );
        }

        try {
            $parsed = Carbon::parse($trimmed, self::TIMEZONE);

            return Carbon::createFromFormat('Y-m-d H:i:s', "{$dateStr} " . $parsed->format('H:i:s'), self::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }
}
