<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZktecoService;

class ZKtecoPing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zkteco:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test network connection with the biometric ZKteco attendance machine';

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
        $this->info("      ZKTeco Device Connection Diagnostics        ");
        $this->info("==================================================");
        $this->info("Target IP:    {$ip}");
        $this->info("Target Port:  {$port}");
        $this->info("Current Mode: " . strtoupper($mode));
        $this->info("--------------------------------------------------");
        $this->info("Attempting socket ping connection...");

        $status = $zkService->getConnectionStatus();

        if ($status['connected']) {
            $this->info("==================================================");
            $this->info(" 🎉 [SUCCESS] Connection established successfully!");
            $this->info(" Message: " . $status['message']);
            $this->info("==================================================");
        } else {
            $this->error("==================================================");
            $this->error(" ❌ [ERROR] Connection Failed!");
            $this->error(" Message: " . $status['message']);
            $this->error("==================================================");
            $this->comment("Troubleshooting Checklist:");
            $this->comment("1. Verify the biometric machine is powered ON.");
            $this->comment("2. Ensure the router's Port Forwarding rule (4370 -> local IP) is active.");
            $this->comment("3. Verify that your ISP/Network allows public inbound connections.");
            $this->comment("4. Verify that ZKTECO_IP matches the WAN / Public IP address.");
        }
    }
}
