<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Shift;

class AttendanceService
{
    /**
     * Calculate Attendance Status based on Shift Rules
     */
    public function calculateStatus(Carbon $punchTime, Shift $shift): string
    {
        $time = $punchTime->format('H:i:s');

        // Check if punch is within Present timeframe
        if ($time <= $shift->in_time_end) {
            return 'Present';
        }

        // Check if punch is within Late grace period
        if ($time > $shift->in_time_end && $time <= $shift->late_time_end) {
            return 'Late';
        }

        // If punched at or after Absent threshold / late_time_end
        return 'Absent';
    }
}
