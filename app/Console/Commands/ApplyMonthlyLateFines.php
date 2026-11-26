<?php

namespace App\Console\Commands;

use App\Services\LateFineService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ApplyMonthlyLateFines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:apply-late-fines
                            {--date= : Optional YYYY-MM-DD date in Asia/Dhaka for dry-run/testing}
                            {--dry-run : Preview eligible invoices without writing to DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply automatic ৳20 monthly late fine to eligible unpaid Monthly Fee invoices after the 7th (effective January 2027, Asia/Dhaka).';

    /**
     * Execute the console command.
     */
    public function handle(LateFineService $lateFineService): int
    {
        $dateOption = $this->option('date');
        $dryRun = (bool) $this->option('dry-run');

        $asOf = Carbon::now(LateFineService::TIMEZONE);

        if ($dateOption !== null && trim((string) $dateOption) !== '') {
            $rawDate = trim((string) $dateOption);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) !== 1) {
                $this->error("Invalid --date value '{$rawDate}'. Expected strict YYYY-MM-DD format (e.g., 2027-01-08).");

                return self::FAILURE;
            }

            try {
                $parsed = Carbon::createFromFormat('!Y-m-d', $rawDate, LateFineService::TIMEZONE);
            } catch (\Throwable) {
                $parsed = false;
            }

            if (!$parsed || $parsed->format('Y-m-d') !== $rawDate) {
                $this->error("Invalid calendar date '{$rawDate}' for --date option.");

                return self::FAILURE;
            }

            // Safety Guard: Block mutating backdated/future-dated overrides in production unless --dry-run is enabled.
            if (app()->environment('production') && !$dryRun) {
                $this->error('Refusing to run fees:apply-late-fines with --date override in production without --dry-run.');
                $this->line('Use --dry-run to preview a specific date safely, or omit --date to run against the live Asia/Dhaka clock.');

                return self::FAILURE;
            }

            $asOf = $parsed->setTime(0, 15, 0);
        }

        $this->info('====================================================================');
        $this->info('   MACS School — Automatic Monthly Late Fine Engine (2027+)         ');
        $this->info('====================================================================');

        if ($dryRun) {
            $this->warn('NOTICE: DRY-RUN MODE ACTIVE. Zero database rows will be modified.');
        }

        $result = $lateFineService->applyAutomaticMonthlyLateFines($asOf, $dryRun);

        if ($result['skipped_reason'] === 'pre_2027_calendar') {
            $this->warn("LOCKED (pre_2027_calendar): Automatic late fine engine is inactive before 2027-01-01 (as-of: {$result['as_of_date']} {$result['timezone']}).");
        }

        $totalSkipped = array_sum($result['skipped_counts']);

        $this->table(
            ['Metric', 'Value'],
            [
                ['As-Of Date (Asia/Dhaka)', $result['as_of_date']],
                ['Mode', $dryRun ? 'DRY-RUN (Read-Only)' : 'LIVE'],
                ['Engine Status', $result['skipped_reason'] ? "LOCKED ({$result['skipped_reason']})" : 'ACTIVE'],
                ['Eligible Invoices', (string) $result['eligible_count']],
                ['Applied Invoices', (string) $result['applied_count']],
                ['Total Fine Amount', '৳' . $result['total_fine_amount']],
                ['Candidate Rows Skipped', (string) $totalSkipped],
            ]
        );

        $nonZeroSkips = array_filter($result['skipped_counts'], static fn (int $count): bool => $count > 0);
        if (!empty($nonZeroSkips)) {
            $skipRows = [];
            foreach ($nonZeroSkips as $reason => $count) {
                $skipRows[] = [$reason, (string) $count];
            }
            $this->table(['Skip Reason', 'Count'], $skipRows);
        }

        if (!empty($result['sample_invoices'])) {
            $sampleRows = [];
            foreach (array_slice($result['sample_invoices'], 0, 20) as $sample) {
                $sampleRows[] = [
                    $sample['invoice_no'],
                    $sample['billing_period'],
                    $sample['student_name'],
                    '৳' . $sample['net_amount'],
                    '৳' . $sample['fine_amount'],
                    '৳' . $sample['new_due_amount'],
                ];
            }
            $this->table(
                ['Invoice No', 'Period', 'Student', 'Tuition Net', 'Auto Fine', 'New Due'],
                $sampleRows
            );
        }

        return self::SUCCESS;
    }
}
