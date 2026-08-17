<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FeeAutoGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:auto-generate {--month= : Specific month to generate (e.g. January)} {--year= : Specific year to generate} {--all-past : Generate missing invoices for all past months of the current year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate monthly fee invoices for active students based on active fee setups.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================");
        $this->info("   Monthly Automated Fee Invoice Generator        ");
        $this->info("==================================================");

        $targetYear = $this->option('year') ?: now()->format('Y');
        
        $monthsToProcess = [];
        if ($this->option('all-past')) {
            $allMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $currentMonthName = now()->format('F');
            foreach ($allMonths as $m) {
                $monthsToProcess[] = $m;
                if ($m === $currentMonthName) {
                    break;
                }
            }
        } elseif ($this->option('month')) {
            $monthsToProcess[] = ucfirst(strtolower($this->option('month')));
        } else {
            $monthsToProcess[] = now()->format('F');
        }

        $totalGenerated = 0;

        foreach ($monthsToProcess as $currentMonth) {
            $this->info("--------------------------------------------------");
            $this->info("Processing Month: {$currentMonth} | Year: {$targetYear}");
            $this->info("--------------------------------------------------");

            // Find all active fee setups for the target month (specific month OR marked as 'Monthly')
            $feeSetups = \App\Models\FeeSetup::where(function ($query) use ($currentMonth) {
                $query->where('fee_month', $currentMonth)
                      ->orWhere('fee_month', 'Monthly');
            })
            ->where('status', 'Active')
            ->get();

            if ($feeSetups->isEmpty()) {
                $this->warn("No active Fee Setups found for {$currentMonth}.");
                continue;
            }

            $this->info("Found " . $feeSetups->count() . " active Fee Setups. Starting generation...");

            foreach ($feeSetups as $feeSetup) {
                $this->info("Processing: Branch ID {$feeSetup->branch_id} | Class ID {$feeSetup->class_id} | Session ID {$feeSetup->session_year_id} | Category ID {$feeSetup->fee_category_id}");

                // Find all active students for this branch, class, and session
                $students = \App\Models\Student::where([
                    'branch_id' => $feeSetup->branch_id,
                    'class_id' => $feeSetup->class_id,
                    'session_year_id' => $feeSetup->session_year_id,
                    'sms_status' => 'Active'
                ])->get();

                if ($students->isEmpty()) {
                    $this->line(" - No active students found in this group.");
                    continue;
                }

                // Determine serials and invoice prefix
                $monthIndex = array_search($currentMonth, ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);
                $paddedMonth = str_pad($monthIndex + 1, 2, '0', STR_PAD_LEFT);
                $prefix = "INV-{$targetYear}{$paddedMonth}-";

                // Find the last invoice for the prefix to compute serial
                $lastInvoice = \App\Models\FeeInvoice::where('invoice_no', 'like', $prefix . '%')
                                                     ->orderBy('id', 'desc')
                                                     ->first();

                $lastSerial = 0;
                if ($lastInvoice) {
                    $lastSerial = (int) str_replace($prefix, '', $lastInvoice->invoice_no);
                }

                // Set due date to 10th of that month and year
                $dueDate = \Carbon\Carbon::parse("10 {$currentMonth} {$targetYear}")->toDateString();

                \DB::beginTransaction();
                try {
                    $generatedCount = 0;
                    foreach ($students as $student) {
                        // Check if invoice already exists for this specific month/year
                        $exists = \App\Models\FeeInvoice::where('student_id', $student->id)
                                                        ->where('fee_setup_id', $feeSetup->id)
                                                        ->where('invoice_no', 'like', $prefix . '%')
                                                        ->exists();

                        if (!$exists) {
                            $lastSerial++;
                            $invoiceNo = $prefix . str_pad($lastSerial, 4, '0', STR_PAD_LEFT);

                            // Check customized fee
                            $customFee = \App\Models\StudentCustomFee::where('student_id', $student->id)
                                                                     ->where('fee_category_id', $feeSetup->fee_category_id)
                                                                     ->first();

                            $amount = $feeSetup->amount;
                            $discount = 0;

                            if ($customFee) {
                                $amount = $customFee->amount;
                                  $discount = max(0, $feeSetup->amount - $customFee->amount);
                            }

                            \App\Models\FeeInvoice::create([
                                'invoice_no' => $invoiceNo,
                                'student_id' => $student->id,
                                'fee_setup_id' => $feeSetup->id,
                                'amount' => $feeSetup->amount,
                                'discount' => $discount,
                                'net_amount' => $amount,
                                'due_amount' => $amount,
                                'status' => 'Unpaid',
                                'due_date' => $dueDate,
                                'user_id' => 1 // Default to admin / system user ID
                            ]);

                            $generatedCount++;
                        }
                    }
                    \DB::commit();
                    if ($generatedCount > 0) {
                        $this->info(" - Generated {$generatedCount} invoices successfully.");
                    }
                    $totalGenerated += $generatedCount;
                } catch (\Exception $e) {
                    \DB::rollBack();
                    $this->error(" - Error occurred: " . $e->getMessage());
                }
            }
        }

        $this->info("==================================================");
        $this->info("Generation complete. Total invoices created: {$totalGenerated}");
        $this->info("==================================================");

        return 0;
    }
}
