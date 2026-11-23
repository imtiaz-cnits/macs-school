<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\StudentBiometricAttendanceProcessor;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonitorAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor ZKteco biometric device and record card swipes in real time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================");
        $this->info("   MACS SCHOOL - REALTIME BIOMETRIC ATTENDANCE    ");
        $this->info("==================================================");

        $zkService = app(ZktecoService::class);

        if ($zkService->getMode() === 'simulation' && app()->isProduction()) {
            $this->error("CRITICAL CONFIGURATION ERROR: ZKTeco biometric device mode is 'simulation' in production.");
            $this->error("Simulation mode is strictly blocked in production to prevent synthetic attendance creation.");
            $this->error("Please configure ZKTECO_MODE=live and verify device network parameters in .env.");
            return 1;
        }

        $this->info("Biometric Device IP: " . $zkService->getIp());
        $this->info("Biometric Device Port: " . $zkService->getPort());
        $this->info("Monitoring Mode: " . $zkService->getMode());
        $this->info("Press Ctrl+C to stop monitoring.");
        $this->info("--------------------------------------------------");

        // Loop persistently
        while (true) {
            $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

            try {
                $this->processPollingCycle($zkService, $today);
            } catch (\Exception $e) {
                $this->error("[" . Carbon::now('Asia/Dhaka')->format('H:i:s') . "] Connection/Read Error: " . $e->getMessage());
            }

            // Sleep for 3 seconds before polling again
            sleep(3);
        }
    }

    /**
     * Execute a single polling cycle against the biometric device for the target date.
     * Extracted for direct testability and shared single-source-of-truth parity.
     *
     * @return int Number of student attendance rows inserted or updated during this cycle.
     */
    public function processPollingCycle(ZktecoService $zkService, ?string $date = null): int
    {
        $targetDate = $date !== null
            ? Carbon::parse($date, 'Asia/Dhaka')->toDateString()
            : Carbon::today('Asia/Dhaka')->toDateString();

        // Get raw device punches for target date (read-only)
        $cardSwipes = $zkService->getRawLogsByCard($targetDate);
        $nowTime = Carbon::now('Asia/Dhaka')->format('H:i:s');

        $this->logInfo("[{$nowTime}] Checked device: found " . count($cardSwipes) . " active user logs for today ({$targetDate}).");
        if (count($cardSwipes) > 0) {
            $this->logInfo("  --> Raw keys detected: " . implode(', ', array_keys($cardSwipes)));
        }

        $processor = app(StudentBiometricAttendanceProcessor::class);
        $punchesByStudent = $processor->extractStudentPunches($cardSwipes);

        if (empty($punchesByStudent)) {
            return 0;
        }

        $students = Student::with(['schoolClass', 'shift'])
            ->whereIn('id', array_keys($punchesByStudent))
            ->get()
            ->keyBy('id');

        $updatedCount = 0;

        foreach ($punchesByStudent as $studentId => $times) {
            /** @var Student|null $student */
            $student = $students->get($studentId);
            if (!$student || !$student->isActive()) {
                continue;
            }

            $result = $processor->processStudentPunches($student, $times, $targetDate, [
                'only_if_changed'          => true,
                'teacher_id'               => null,
                'user_id'                  => 1,
                'fallback_branch_id'       => 1,
                'fallback_session_year_id' => 1,
                'fallback_class_id'        => 1,
                'fallback_section_id'      => 1,
            ]);

            if ($result['persisted'] && $result['attendance'] !== null) {
                $updatedCount++;

                $this->logInfo(
                    "[{$nowTime}] PUNCH RECORD UPDATED: Student '{$student->student_name}' (ID: {$student->id}) "
                    . "status set to {$result['status']}, remarks set to: {$result['remarks']}."
                );

                // Trigger immediate Guardian SMS notification ONLY for Present/Late persisted punches
                if ($result['sms_eligible']) {
                    $processor->dispatchGuardianSms(
                        $student,
                        $result['attendance'],
                        $times,
                        $result['schedule'],
                        $result['evaluation'],
                        'k60_live'
                    );
                }
            }
        }

        return $updatedCount;
    }

    private function logInfo(string $message): void
    {
        if ($this->output !== null) {
            $this->info($message);
        }
    }
}
