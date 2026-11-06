<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FeeGenerationService;

class FeeAutoGenerate extends Command
{
    /**
     * Canonical Global Advisory Lock Name (Forwarded to FeeGenerationService).
     */
    public const ADVISORY_LOCK_NAME = FeeGenerationService::ADVISORY_LOCK_NAME;
    public const ADVISORY_LOCK_TIMEOUT = FeeGenerationService::ADVISORY_LOCK_TIMEOUT;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:auto-generate 
                            {--month= : Specific month to generate (e.g. January)} 
                            {--year= : Specific 4-digit calendar year to generate} 
                            {--session= : Specific academic session year ID or session name} 
                            {--class= : Optional class ID to limit generation} 
                            {--all-year : Generate monthly invoices for all 12 months (January-December) of the session} 
                            {--all-past : Generate missing invoices for all past months of the current year} 
                            {--dry-run : Preview generation without writing invoices to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly fee invoices for active students using the unified FeeGenerationService.';

    /**
     * Execute the console command.
     */
    public function handle(FeeGenerationService $generationService): int
    {
        $this->info("====================================================================");
        $this->info("   MACS School — Monthly / Annual Fee Invoice Generator             ");
        $this->info("====================================================================");

        $isDryRun = (bool) $this->option('dry-run');
        if ($isDryRun) {
            $this->warn("NOTICE: DRY-RUN MODE ACTIVE. Zero database rows will be created.");
        }

        $params = [
            'session_year_id' => $this->option('session'),
            'year'            => $this->option('year'),
            'class_id'        => $this->option('class'),
            'fee_month'       => $this->option('all-year') ? 'all' : ($this->option('all-past') ? 'all-past' : $this->option('month')),
            'all_year'        => (bool) $this->option('all-year'),
            'all_past'        => (bool) $this->option('all-past'),
            'dry_run'         => $isDryRun,
            'user_id'         => null, // Resolved via FeeGenerationService::resolveActorUserId() policy
        ];

        // Real-time output callback to pipe service output to CLI
        $callback = function (string $type, string $message, array $context = []) {
            switch ($type) {
                case 'error':
                    $this->error($message);
                    break;
                case 'warn':
                    $this->warn($message);
                    break;
                case 'line':
                    $this->line($message);
                    break;
                case 'info':
                default:
                    $this->info($message);
                    break;
            }
        };

        $result = $generationService->generate($params, $callback);

        if (!$result['success']) {
            $this->error("\nGENERATION FAILED: " . ($result['error'] ?? 'Unknown error occurred.'));
            return 1;
        }

        $this->info("\n====================================================================");
        $finalLabel = $isDryRun ? "Simulated Invoices Created" : "Total Invoices Created";
        $this->info("{$finalLabel}: {$result['total_generated']} | Invoices Skipped (Existing): {$result['total_skipped']}");
        $this->info("====================================================================");

        return 0;
    }
}
