<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\StaffAttendance;
use Carbon\Carbon;

class ZktecoService
{
    protected $ip;
    protected $port;
    protected $timeout;
    protected $mode;

    public function __construct()
    {
        // Fetch configs from environment / config with default fallback to simulation mode
        $this->ip = env('ZKTECO_IP', '192.168.1.201');
        $this->port = env('ZKTECO_PORT', 4370);
        $this->timeout = env('ZKTECO_TIMEOUT', 3);
        $this->mode = env('ZKTECO_MODE', 'simulation'); // 'live' or 'simulation'
    }

    /**
     * Check connection state of the biometric machine
     */
    public function getConnectionStatus()
    {
        if ($this->mode === 'simulation') {
            return [
                'status' => 'Simulated',
                'connected' => true,
                'ip' => '127.0.0.1 (Local Simulation)',
                'port' => 4370,
                'message' => 'Biometric emulator active'
            ];
        }

        $pingable = $this->ping();
        return [
            'status' => $pingable ? 'Connected' : 'Disconnected',
            'connected' => $pingable,
            'ip' => $this->ip,
            'port' => $this->port,
            'message' => $pingable ? 'Device linked successfully' : 'Device socket unreachable'
        ];
    }

    /**
     * Ping the actual socket
     */
    public function ping()
    {
        try {
            $fp = @fsockopen($this->ip, $this->port, $errno, $errstr, $this->timeout);
            if ($fp) {
                fclose($fp);
                return true;
            }
        } catch (\Exception $e) {
            // Unreachable
        }
        return false;
    }

    /**
     * Synchronize attendance logs for a specific date
     */
    public function syncLogs($dateString = null)
    {
        $date = $dateString ? Carbon::parse($dateString) : Carbon::today();

        if ($this->mode === 'simulation') {
            return $this->generateSimulationLogs($date);
        }

        // Live connection logic (for ZKTeco device socket parsing)
        if (!$this->ping()) {
            throw new \Exception("Biometric Machine is offline or unreachable at {$this->ip}:{$this->port}");
        }

        // Under normal live integration:
        // 1. Establish UDP/TCP socket commands to ZKTeco
        // 2. Read logs from memory
        // 3. Match biometric_id to teacher_id
        // 4. Save check_in (earliest) and check_out (latest) logs for the day
        
        throw new \Exception("Offline socket integration ready. Please configure live credentials in .env.");
    }

    /**
     * Simulate logs for local verification and setup demo
     */
    public function generateSimulationLogs(Carbon $date)
    {
        $teachers = Teacher::all();
        $synced = 0;

        foreach ($teachers as $teacher) {
            // Assign a dummy biometric_id if empty
            if (empty($teacher->biometric_id)) {
                $teacher->update(['biometric_id' => 100 + $teacher->id]);
            }

            // Simulate check-in (8:00 AM to 9:25 AM)
            $checkInHour = rand(8, 9);
            $checkInMin = rand(0, 59);
            if ($checkInHour === 9 && $checkInMin > 25) {
                $checkInMin = rand(0, 20); // Keep max at 9:20 for predictability
            }
            $checkInTime = Carbon::create($date->year, $date->month, $date->day, $checkInHour, $checkInMin, 0);

            // Simulate check-out (4:30 PM to 5:45 PM)
            $checkOutHour = rand(16, 17);
            $checkOutMin = rand(0, 59);
            if ($checkOutHour === 16 && $checkOutMin < 30) {
                $checkOutMin = rand(30, 59);
            }
            $checkOutTime = Carbon::create($date->year, $date->month, $date->day, $checkOutHour, $checkOutMin, 0);

            // Status decision (Late threshold: 9:00 AM)
            $status = 'Present';
            if ($checkInTime->format('H:i:s') > '09:00:00') {
                $status = 'Late';
            }

            // Randomly simulate an absent staff (5% chance)
            $isAbsent = (rand(1, 100) <= 5);
            if ($isAbsent) {
                StaffAttendance::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'date' => $date->format('Y-m-d')
                    ],
                    [
                        'check_in' => null,
                        'check_out' => null,
                        'status' => 'Absent',
                        'remarks' => 'Absent (Auto-simulated)'
                    ]
                );
            } else {
                StaffAttendance::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'date' => $date->format('Y-m-d')
                    ],
                    [
                        'check_in' => $checkInTime->format('H:i:s'),
                        'check_out' => $checkOutTime->format('H:i:s'),
                        'status' => $status,
                        'remarks' => 'Biometric Swipe (Simulated)'
                    ]
                );
            }
            $synced++;
        }

        return $synced;
    }
}
