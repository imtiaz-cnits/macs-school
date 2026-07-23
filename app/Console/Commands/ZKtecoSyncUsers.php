<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\ZktecoService;
use App\Models\Student;
use App\Models\Teacher;

class ZKtecoSyncUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zkteco:sync-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all students and teachers from Laravel database to ZKTeco biometric device';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zkService = app(ZktecoService::class);
        $ip = $zkService->getIp();
        $port = $zkService->getPort();
        $mode = $zkService->getMode();

        $this->info("==================================================");
        $this->info("   ZKTeco Biometric User Synchronization Tool     ");
        $this->info("==================================================");
        $this->info("Target Device: {$ip}:{$port}");
        $this->info("Device Mode:   " . strtoupper($mode));
        $this->info("--------------------------------------------------");

        if ($mode === 'simulation') {
            $this->warn("Device is in SIMULATION mode. Sync skipped.");
            return;
        }

        try {
            $zk = new \Jmrashed\Zkteco\Lib\ZKTeco($ip, $port);
            if (!$zk->connect()) {
                $this->error("❌ Unable to connect to biometric device.");
                return;
            }

            $zk->disableDevice();

            $this->info("Fetching database records...");
            $students = Student::all();
            $teachers = Teacher::all();

            $this->info("Uploading " . $students->count() . " students to device...");
            $studentCount = 0;
            foreach ($students as $student) {
                $uid = $student->id;
                $userid = $student->id;
                
                // Clean name for ZKTeco screen compatibility (alphanumeric only, max 24 chars)
                $cleanName = substr(preg_replace('/[^A-Za-z0-9\s]/', '', $student->student_name), 0, 24);
                if (empty($cleanName)) {
                    $cleanName = "Student " . $student->id;
                }

                // Format card number to numeric/integer representation
                $cardno = $student->card_number ? preg_replace('/[^0-9]/', '', $student->card_number) : 0;
                if (empty($cardno)) {
                    $cardno = 0;
                }

                // Upload student
                $zk->setUser($uid, $userid, $cleanName, '', 0, $cardno);
                $studentCount++;
            }
            $this->info("✅ Successfully synced {$studentCount} students to device.");

            $this->info("Uploading " . $teachers->count() . " teachers/staff to device...");
            $teacherCount = 0;
            foreach ($teachers as $teacher) {
                // To avoid overlap with students (ID 1-1000), teachers get uid starting at 10000
                $uid = 10000 + $teacher->id;
                $userid = $teacher->biometric_id ? preg_replace('/[^0-9]/', '', $teacher->biometric_id) : (10000 + $teacher->id);
                
                $cleanName = substr(preg_replace('/[^A-Za-z0-9\s]/', '', $teacher->name), 0, 24);
                if (empty($cleanName)) {
                    $cleanName = "Staff " . $teacher->id;
                }

                $cardno = $teacher->card_number ? preg_replace('/[^0-9]/', '', $teacher->card_number) : 0;
                if (empty($cardno)) {
                    $cardno = 0;
                }

                // Upload teacher
                $zk->setUser($uid, $userid, $cleanName, '', 0, $cardno);
                $teacherCount++;
            }
            $this->info("✅ Successfully synced {$teacherCount} teachers/staff to device.");

            $zk->enableDevice();
            $zk->disconnect();
            $this->info("==================================================");
            $this->info(" 🎉 [SUCCESS] User synchronization complete!");
            $this->info("==================================================");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
