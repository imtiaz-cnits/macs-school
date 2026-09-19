<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FeeInvoice;
use App\Models\FeeSetup;
use Illuminate\Support\Facades\DB;

class FeeCleanupDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:cleanup-duplicates {--dry-run : Preview invalid duplicate invoices without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely remove invalid duplicate fee invoices that do not match the designated setup month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("==========================================================");
        $this->info("   MACS School - Fee Duplicate Invoices Cleanup Utility   ");
        $this->info("   Mode: " . ($isDryRun ? "PREVIEW / DRY-RUN (No data deleted)" : "ACTIVE DELETION"));
        $this->info("==========================================================");

        $validMonths = [
            'january' => '01', 'february' => '02', 'march' => '03',
            'april' => '04', 'may' => '05', 'june' => '06',
            'july' => '07', 'august' => '08', 'september' => '09',
            'october' => '10', 'november' => '11', 'december' => '12'
        ];

        // 1. Find all fee setups that have a specific month assigned
        $specificMonthSetups = FeeSetup::with(['category', 'schoolClass'])
            ->whereNotNull('fee_month')
            ->where('fee_month', '!=', '')
            ->where('fee_month', '!=', 'Monthly')
            ->get();

        $invoicesToDelete = collect();
        $reasons = [];

        foreach ($specificMonthSetups as $setup) {
            $monthKey = strtolower(trim($setup->fee_month));
            if (!isset($validMonths[$monthKey])) {
                continue;
            }

            $expectedMonthNum = $validMonths[$monthKey]; // e.g. "03" for March

            // All invoices linked to this specific month's setup
            $invoices = FeeInvoice::with(['student.schoolClass', 'payments'])
                ->where('fee_setup_id', $setup->id)
                ->orderBy('id', 'asc')
                ->get();

            // Group by student to detect duplicates
            $groupedByStudent = $invoices->groupBy('student_id');

            foreach ($groupedByStudent as $studentId => $studentInvoices) {
                // Determine valid matching invoice if any (prefix matching the expected month)
                $matchingInvoices = $studentInvoices->filter(function ($inv) use ($expectedMonthNum) {
                    if (preg_match('/INV-\d{4}(0[1-9]|1[0-2])-/i', $inv->invoice_no, $m)) {
                        return $m[1] === $expectedMonthNum;
                    }
                    return false;
                });

                // Pick the earliest valid matching invoice as the one to keep
                $primaryInvoiceId = $matchingInvoices->first()->id ?? null;

                foreach ($studentInvoices as $inv) {
                    // Check safety: Only delete if completely unpaid and has no payment records
                    $isSafe = ($inv->status === 'Unpaid' || $inv->paid_amount == 0) && $inv->payments->isEmpty();

                    if (!$isSafe) {
                        continue; // Skip any invoice with collected payments
                    }

                    // Check month mismatch
                    $hasMonthPrefix = preg_match('/INV-\d{4}(0[1-9]|1[0-2])-/i', $inv->invoice_no, $matches);
                    $invoiceMonthNum = $hasMonthPrefix ? $matches[1] : null;

                    $shouldDelete = false;
                    $reason = '';

                    if ($invoiceMonthNum && $invoiceMonthNum !== $expectedMonthNum) {
                        // Month in invoice number doesn't match setup month (e.g. INV-202601-... for March setup)
                        $shouldDelete = true;
                        $monthNames = array_flip($validMonths);
                        $invMonthName = ucfirst($monthNames[$invoiceMonthNum] ?? $invoiceMonthNum);
                        $reason = "Month Mismatch (Invoice: {$invMonthName} vs Setup: " . ucfirst($monthKey) . ")";
                    } elseif ($primaryInvoiceId && $inv->id !== $primaryInvoiceId) {
                        // Multiple invoices for the same student and setup
                        $shouldDelete = true;
                        $reason = "Duplicate invoice for {$setup->fee_month} (Keeping Primary ID: {$primaryInvoiceId})";
                    }

                    if ($shouldDelete) {
                        $invoicesToDelete->push($inv);
                        $reasons[$inv->id] = $reason;
                    }
                }
            }
        }

        if ($invoicesToDelete->isEmpty()) {
            $this->info("\nGreat! No invalid or duplicate invoices found.");
            return 0;
        }

        $this->warn("\nFound " . $invoicesToDelete->count() . " invalid / duplicate invoices eligible for cleanup:\n");

        $tableRows = [];
        foreach ($invoicesToDelete->take(30) as $inv) {
            $tableRows[] = [
                $inv->id,
                $inv->invoice_no,
                $inv->student->student_name ?? 'N/A',
                $inv->student->schoolClass->class_name ?? 'N/A',
                $inv->feeSetup->category->name ?? 'N/A',
                $inv->feeSetup->fee_month ?? 'N/A',
                number_format($inv->net_amount, 2),
                $reasons[$inv->id] ?? 'Duplicate'
            ];
        }

        $this->table(
            ['ID', 'Invoice No', 'Student', 'Class', 'Category', 'Setup Month', 'Amount (৳)', 'Reason'],
            $tableRows
        );

        if ($invoicesToDelete->count() > 30) {
            $this->line("... and " . ($invoicesToDelete->count() - 30) . " more rows.\n");
        }

        if ($isDryRun) {
            $this->info("DRY-RUN COMPLETE: Total {$invoicesToDelete->count()} invalid invoices identified.");
            $this->info("To permanently remove them, run: php artisan fees:cleanup-duplicates");
            return 0;
        }

        // Active Deletion
        $idsToDelete = $invoicesToDelete->pluck('id')->toArray();
        DB::beginTransaction();
        try {
            $deletedCount = FeeInvoice::whereIn('id', $idsToDelete)->delete();
            DB::commit();

            $this->info("\nSUCCESS: {$deletedCount} invalid duplicate invoices have been safely removed from the database!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to delete invoices: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
