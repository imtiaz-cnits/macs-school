<?php

namespace App\Console\Commands;

use App\Models\FeeInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileCrossMonthDueDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:reconcile-cross-month-due-dates
                            {--month= : Optional YYYY-MM billing month filter (e.g. 2026-03)}
                            {--apply : Actually apply due_date normalization inside a single DB transaction (omit for read-only dry-run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and safely normalize legacy fee invoices whose due_date falls outside their canonical INV-YYYYMM billing month (dry-run by default).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawMonth = $this->option('month');
        $monthFilter = null;

        if ($rawMonth !== null && trim((string) $rawMonth) !== '') {
            $monthFilter = trim((string) $rawMonth);
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthFilter) !== 1) {
                $this->error("Invalid --month value '{$monthFilter}'. Expected strict YYYY-MM format (e.g., 2026-03).");

                return self::FAILURE;
            }
        }

        $apply = (bool) $this->option('apply');

        $this->info('====================================================================');
        $this->info('   MACS School — Cross-Month Invoice due_date Reconciliation        ');
        $this->info('====================================================================');

        if (!$apply) {
            $this->warn('NOTICE: DRY-RUN MODE ACTIVE (Default). Zero database rows will be modified.');
        } else {
            $this->warn('APPLY MODE ACTIVE: Only fee_invoices.due_date will be normalized inside a single DB transaction.');
        }

        if ($monthFilter !== null) {
            $this->line("Billing Month Filter: {$monthFilter}");
        }

        $mismatches = [];
        $grouped = [];
        $totals = [
            'rows'          => 0,
            'active_rows'   => 0,
            'cancelled_rows'=> 0,
            'net_amount'    => '0.00',
            'paid_amount'   => '0.00',
            'due_amount'    => '0.00',
        ];
        $minOldDate = null;
        $maxOldDate = null;
        $billingMonths = [];

        FeeInvoice::query()
            ->whereNull('admission_fee_structure_id')
            ->with(['student.schoolClass'])
            ->select([
                'id',
                'invoice_no',
                'student_id',
                'admission_fee_structure_id',
                'net_amount',
                'paid_amount',
                'due_amount',
                'status',
                'due_date',
            ])
            ->orderBy('id', 'asc')
            ->chunkById(500, function ($invoices) use (
                $monthFilter,
                &$mismatches,
                &$grouped,
                &$totals,
                &$minOldDate,
                &$maxOldDate,
                &$billingMonths
            ): void {
                foreach ($invoices as $invoice) {
                    // Strict Exclusion 1: Never touch Admission invoices.
                    if ($invoice->isAdmissionInvoice() || $invoice->isAdmissionInvoiceNumber()) {
                        continue;
                    }

                    // Strict Exclusion 2: Only canonical INV-YYYYMM-* invoices.
                    $parsed = FeeInvoice::extractYearMonthFromInvoiceNo($invoice->invoice_no);
                    if ($parsed === null) {
                        continue;
                    }

                    $billingPeriod = $parsed['year'] . '-' . $parsed['month'];

                    if ($monthFilter !== null && $billingPeriod !== $monthFilter) {
                        continue;
                    }

                    // Strict Exclusion 3: Leave invoices whose due_date is already within the same YYYY-MM month untouched.
                    $currentDueDate = null;
                    $currentDueMonth = 'NULL';

                    if (!empty($invoice->due_date)) {
                        try {
                            $parsedDue = Carbon::parse((string) $invoice->due_date);
                            $currentDueDate = $parsedDue->toDateString();
                            $currentDueMonth = $parsedDue->format('Y-m');
                        } catch (\Throwable) {
                            $currentDueDate = (string) $invoice->due_date;
                            $currentDueMonth = 'INVALID';
                        }
                    }

                    if ($currentDueMonth === $billingPeriod) {
                        continue;
                    }

                    $targetDueDate = "{$billingPeriod}-10";
                    $className = $invoice->student?->schoolClass?->class_name ?? 'Unknown';
                    $status = (string) ($invoice->status ?? 'Unpaid');
                    $statusBucket = $status === 'Cancelled' ? 'Cancelled' : "Active ({$status})";

                    $netStr = bcadd((string) ($invoice->net_amount ?? '0'), '0.00', 2);
                    $paidStr = bcadd((string) ($invoice->paid_amount ?? '0'), '0.00', 2);
                    $dueStr = bcadd((string) ($invoice->due_amount ?? '0'), '0.00', 2);

                    $mismatches[] = [
                        'id'                => (int) $invoice->id,
                        'invoice_no'        => (string) $invoice->invoice_no,
                        'billing_period'    => $billingPeriod,
                        'class_name'        => $className,
                        'current_due_date'  => $currentDueDate,
                        'current_due_month' => $currentDueMonth,
                        'target_due_date'   => $targetDueDate,
                        'status'            => $status,
                        'status_bucket'     => $statusBucket,
                        'net_amount'        => $netStr,
                        'paid_amount'       => $paidStr,
                        'due_amount'        => $dueStr,
                    ];

                    $billingMonths[$billingPeriod] = true;

                    if ($currentDueDate !== null) {
                        if ($minOldDate === null || strcmp($currentDueDate, $minOldDate) < 0) {
                            $minOldDate = $currentDueDate;
                        }
                        if ($maxOldDate === null || strcmp($currentDueDate, $maxOldDate) > 0) {
                            $maxOldDate = $currentDueDate;
                        }
                    }

                    $totals['rows']++;
                    if ($status === 'Cancelled') {
                        $totals['cancelled_rows']++;
                    } else {
                        $totals['active_rows']++;
                    }
                    $totals['net_amount'] = bcadd($totals['net_amount'], $netStr, 2);
                    $totals['paid_amount'] = bcadd($totals['paid_amount'], $paidStr, 2);
                    $totals['due_amount'] = bcadd($totals['due_amount'], $dueStr, 2);

                    $groupKey = "{$billingPeriod}|{$className}|{$currentDueMonth}|{$statusBucket}";
                    if (!isset($grouped[$groupKey])) {
                        $grouped[$groupKey] = [
                            'billing_period'    => $billingPeriod,
                            'class_name'        => $className,
                            'current_due_month' => $currentDueMonth,
                            'status_bucket'     => $statusBucket,
                            'count'             => 0,
                            'min_old_due'       => $currentDueDate,
                            'max_old_due'       => $currentDueDate,
                            'target_due_date'   => $targetDueDate,
                            'net_amount'        => '0.00',
                            'paid_amount'       => '0.00',
                            'due_amount'        => '0.00',
                        ];
                    }

                    $grouped[$groupKey]['count']++;
                    if ($currentDueDate !== null) {
                        if ($grouped[$groupKey]['min_old_due'] === null || strcmp($currentDueDate, $grouped[$groupKey]['min_old_due']) < 0) {
                            $grouped[$groupKey]['min_old_due'] = $currentDueDate;
                        }
                        if ($grouped[$groupKey]['max_old_due'] === null || strcmp($currentDueDate, $grouped[$groupKey]['max_old_due']) > 0) {
                            $grouped[$groupKey]['max_old_due'] = $currentDueDate;
                        }
                    }
                    $grouped[$groupKey]['net_amount'] = bcadd($grouped[$groupKey]['net_amount'], $netStr, 2);
                    $grouped[$groupKey]['paid_amount'] = bcadd($grouped[$groupKey]['paid_amount'], $paidStr, 2);
                    $grouped[$groupKey]['due_amount'] = bcadd($grouped[$groupKey]['due_amount'], $dueStr, 2);
                }
            });

        if (empty($mismatches)) {
            $this->info('No cross-month due_date mismatches found (0 rows require normalization).');

            return self::SUCCESS;
        }

        ksort($grouped);

        $tableRows = [];
        foreach ($grouped as $g) {
            $oldRange = $g['min_old_due'] === null
                ? 'NULL'
                : ($g['min_old_due'] === $g['max_old_due']
                    ? $g['min_old_due']
                    : "{$g['min_old_due']} .. {$g['max_old_due']}");

            $tableRows[] = [
                $g['billing_period'],
                $g['class_name'],
                $g['current_due_month'],
                $g['status_bucket'],
                (string) $g['count'],
                $oldRange,
                $g['target_due_date'],
                $g['net_amount'],
                $g['paid_amount'],
                $g['due_amount'],
            ];
        }

        $tableRows[] = [
            'TOTAL',
            '-',
            '-',
            "Active: {$totals['active_rows']} / Cancelled: {$totals['cancelled_rows']}",
            (string) $totals['rows'],
            $minOldDate === null ? 'NULL' : ($minOldDate === $maxOldDate ? $minOldDate : "{$minOldDate} .. {$maxOldDate}"),
            count($billingMonths) === 1 ? (array_key_first($billingMonths) . '-10') : 'YYYY-MM-10',
            $totals['net_amount'],
            $totals['paid_amount'],
            $totals['due_amount'],
        ];

        $this->table(
            ['Billing Period', 'Class', 'Current Due Month', 'Status Bucket', 'Rows', 'Old Due Date Range', 'Target Due Date', 'Total Net', 'Total Paid', 'Total Due'],
            $tableRows
        );

        if (!$apply) {
            $this->warn('DRY-RUN MODE: 0 rows modified. Re-run with --apply to normalize due_date values.');

            return self::SUCCESS;
        }

        // Group IDs by normalized target due_date so updates stay atomic inside a single DB transaction.
        $idsByTargetDate = [];
        foreach ($mismatches as $row) {
            $idsByTargetDate[$row['target_due_date']][] = $row['id'];
        }

        $now = now();
        $expectedCount = count($mismatches);

        $affectedCount = DB::transaction(function () use ($idsByTargetDate, $now, $expectedCount): int {
            $updated = 0;

            foreach ($idsByTargetDate as $targetDueDate => $ids) {
                foreach (array_chunk($ids, 500) as $idChunk) {
                    $updated += DB::table('fee_invoices')
                        ->whereIn('id', $idChunk)
                        ->whereNull('admission_fee_structure_id')
                        ->update([
                            'due_date'   => $targetDueDate,
                            'updated_at' => $now,
                        ]);
                }
            }

            if ($updated !== $expectedCount) {
                throw new \RuntimeException(
                    "Cross-month due_date reconciliation affected count mismatch: expected {$expectedCount}, updated {$updated}. Transaction rolled back."
                );
            }

            return $updated;
        });

        $billingMonthList = array_keys($billingMonths);
        sort($billingMonthList);
        $targetDatesList = array_keys($idsByTargetDate);
        sort($targetDatesList);

        Log::info('fees:reconcile-cross-month-due-dates applied', [
            'affected_count'   => $affectedCount,
            'active_rows'      => $totals['active_rows'],
            'cancelled_rows'   => $totals['cancelled_rows'],
            'month_filter'     => $monthFilter,
            'billing_months'   => $billingMonthList,
            'old_due_date_min' => $minOldDate,
            'old_due_date_max' => $maxOldDate,
            'target_due_dates' => $targetDatesList,
            'total_net_amount' => $totals['net_amount'],
            'total_paid_amount'=> $totals['paid_amount'],
            'total_due_amount' => $totals['due_amount'],
        ]);

        $this->info("APPLIED SUCCESSFULLY: Normalized due_date on {$affectedCount} invoice(s). Zero monetary or status fields were modified.");

        return self::SUCCESS;
    }
}
