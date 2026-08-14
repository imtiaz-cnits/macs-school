<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZktecoService;

class ZKtecoCheckDeviceUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zkteco:check-device-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect all user profiles on the ZKTeco biometric machine and report their card registration status';

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
        $this->info("   ZKTeco Biometric Device User Inspector         ");
        $this->info("==================================================");
        $this->info("Target Device: {$ip}:{$port}");
        $this->info("Device Mode:   " . strtoupper($mode));
        $this->info("--------------------------------------------------");

        if ($mode === 'simulation') {
            $this->warn("Device is in SIMULATION mode. Cannot read real device users.");
            return;
        }

        try {
            $zk = new \Jmrashed\Zkteco\Lib\ZKTeco($ip, $port);
            if (!$zk->connect()) {
                $this->error("❌ Unable to connect to biometric device at {$ip}:{$port}.");
                return;
            }

            $this->info("Connecting to machine and fetching user list...");
            $users = $zk->getUser();
            $zk->disconnect();

            if (!is_array($users) || empty($users)) {
                $this->error("❌ No users found on device or failed to retrieve data.");
                return;
            }

            $totalUsers = count($users);
            $withCards = 0;
            $withoutCards = 0;
            $cardList = [];

            foreach ($users as $user) {
                $cardNo = trim($user['cardno'] ?? '');
                if ($cardNo !== '' && $cardNo !== '0' && $cardNo !== '0000000000') {
                    $withCards++;
                    $cardList[] = [
                        'userid' => $user['userid'],
                        'name' => $user['name'],
                        'cardno' => $cardNo
                    ];
                } else {
                    $withoutCards++;
                }
            }

            $this->info("\n================ INSPECTION REPORT ================");
            $this->info("Total Users Registered on Device: {$totalUsers}");
            $this->info("Users WITH valid RFID cards:     {$withCards} (" . round(($withCards / $totalUsers) * 100, 1) . "%)");
            $this->info("Users WITHOUT RFID cards:        {$withoutCards} (" . round(($withoutCards / $totalUsers) * 100, 1) . "%)");
            $this->info("===================================================\n");

            if ($withCards > 0) {
                $this->info("List of Enrolled Card Users on the Machine:");
                foreach ($cardList as $item) {
                    $this->line(" - UserID (PIN): " . str_pad($item['userid'], 8) . " | Name: " . str_pad($item['name'], 25) . " | Card No: " . $item['cardno']);
                }
            } else {
                $this->warn("⚠️ No users with card numbers were found on the device. All user profiles have 0/empty card numbers.");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error running inspection: " . $e->getMessage());
        }
    }
}
