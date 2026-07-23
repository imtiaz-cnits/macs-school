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

    public function getIp() { return $this->ip; }
    public function getPort() { return $this->port; }
    public function getMode() { return $this->mode; }

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

        // Live connection logic using ZKTeco library
        try {
            $zk = new \Jmrashed\Zkteco\Lib\ZKTeco($this->ip, $this->port);
            
            if (!$zk->connect()) {
                throw new \Exception("Unable to establish connection with Biometric Device at {$this->ip}:{$this->port}");
            }
            
            // Disable device to prevent changes while downloading log
            $zk->disableDevice();
            
            $allLogs = $zk->getAttendance();
            
            // Re-enable device immediately
            $zk->enableDevice();
            $zk->disconnect();
            
            if (!is_array($allLogs)) {
                return 0;
            }
            
            $targetDateStr = $date->format('Y-m-d');
            $filteredLogs = [];
            
            // Filter logs matching target date
            foreach ($allLogs as $log) {
                // Log contains keys: 'id', 'state', 'timestamp', 'type'
                if (isset($log['timestamp']) && str_starts_with($log['timestamp'], $targetDateStr)) {
                    $filteredLogs[] = $log;
                }
            }
            
            // Group by biometric user ID
            $groupedLogs = [];
            foreach ($filteredLogs as $log) {
                $uid = $log['id'];
                $time = Carbon::parse($log['timestamp'])->format('H:i:s');
                if (!isset($groupedLogs[$uid])) {
                    $groupedLogs[$uid] = [];
                }
                $groupedLogs[$uid][] = $time;
            }
            
            $synced = 0;
            
            foreach ($groupedLogs as $biometricId => $times) {
                // Find teacher matching biometric ID
                $teacher = Teacher::where('biometric_id', $biometricId)->first();
                if (!$teacher) {
                    // Fallback: match teacher directly by ID (if biometricId - 10000 is a valid ID)
                    if ($biometricId > 10000) {
                        $teacherId = $biometricId - 10000;
                        $teacher = Teacher::find($teacherId);
                    }
                }
                
                if (!$teacher) {
                    continue; // Skip logs for unregistered device IDs
                }
                
                sort($times);
                $checkIn = $times[0];
                $checkOut = count($times) > 1 ? end($times) : null;
                
                $attendanceService = app(\App\Services\AttendanceService::class);
                $shift = $teacher->shift;
                if (!$shift) {
                    $shift = \App\Models\Shift::where('type', 'staff')->first();
                }

                $status = 'Present';
                if ($shift) {
                    $punchCarbon = Carbon::parse($targetDateStr . ' ' . $checkIn, 'Asia/Dhaka');
                    $status = $attendanceService->calculateStatus($punchCarbon, $shift);
                }
                
                StaffAttendance::updateOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'date' => $targetDateStr
                    ],
                    [
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'status' => $status,
                        'remarks' => 'Biometric Swipe (Live)'
                    ]
                );
                
                $synced++;
            }
            
            return $synced;
            
        } catch (\Exception $e) {
            throw new \Exception("Biometric sync failed: " . $e->getMessage());
        }
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

            $attendanceService = app(\App\Services\AttendanceService::class);
            $shift = $teacher->shift;
            if (!$shift) {
                $shift = \App\Models\Shift::where('type', 'staff')->first();
            }

            $status = 'Present';
            if ($shift) {
                $status = $attendanceService->calculateStatus($checkInTime, $shift);
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

    /**
     * Get raw attendance logs grouped by card number
     */
    public function getRawLogsByCard($dateString = null)
    {
        $date = $dateString ? Carbon::parse($dateString) : Carbon::today();

        if ($this->mode === 'simulation') {
            $cards = [];
            // Get all students with card numbers
            $students = \App\Models\Student::whereNotNull('card_number')->get();
            foreach ($students as $student) {
                // 85% chance of card swipe present
                if (rand(1, 100) <= 85) {
                    $checkInHour = rand(8, 9);
                    $checkInMin = rand(0, 59);
                    if ($checkInHour === 9 && $checkInMin > 30) {
                        $checkInMin = rand(0, 20); // Limit late range
                    }
                    $checkIn = Carbon::create($date->year, $date->month, $date->day, $checkInHour, $checkInMin, 0)->format('H:i:s');
                    $cards[$student->card_number] = [$checkIn];
                }
            }
            return $cards;
        }

        try {
            $zk = new \Jmrashed\Zkteco\Lib\ZKTeco($this->ip, $this->port);
            
            if (!$zk->connect()) {
                throw new \Exception("Unable to establish connection with Biometric Device at {$this->ip}:{$this->port}");
            }
            
            $zk->disableDevice();
            $allLogs = $zk->getAttendance();
            $users = $zk->getUser();
            $zk->enableDevice();
            $zk->disconnect();
            
            if (!is_array($allLogs) || !is_array($users)) {
                return [];
            }
            
            $userCardMap = [];
            foreach ($users as $user) {
                if (isset($user['userid']) && !empty($user['cardno'])) {
                    $userCardMap[$user['userid']] = trim($user['cardno']);
                }
            }
            
            $targetDateStr = $date->format('Y-m-d');
            $cards = [];
            
            foreach ($allLogs as $log) {
                if (isset($log['timestamp']) && str_starts_with($log['timestamp'], $targetDateStr)) {
                    $uid = $log['id'];
                    $time = Carbon::parse($log['timestamp'])->format('H:i:s');
                    
                    // 1. Always map by direct user ID key (fallback/fingerprint support)
                    $idKey = "id:{$uid}";
                    if (!isset($cards[$idKey])) {
                        $cards[$idKey] = [];
                    }
                    $cards[$idKey][] = $time;
                    
                    // 2. Map by card number if present and not zero (RFID support)
                    if (isset($userCardMap[$uid]) && trim($userCardMap[$uid]) !== '' && trim($userCardMap[$uid]) !== '0') {
                        $cardNo = trim($userCardMap[$uid]);
                        if (!isset($cards[$cardNo])) {
                            $cards[$cardNo] = [];
                        }
                        $cards[$cardNo][] = $time;
                    }
                }
            }
            
            return $cards;
            
        } catch (\Exception $e) {
            throw new \Exception("Biometric sync failed: " . $e->getMessage());
        }
    }
}
