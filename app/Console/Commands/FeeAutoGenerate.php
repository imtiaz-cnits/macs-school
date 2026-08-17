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
    protected $signature = 'fees:auto-generate';

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

        $currentMonth = now()->format('F'); // Returns e.g. "January", "February", etc.
        $this->info("Target Month: {$currentMonth}");

        // Find all active fee setups for the current month
        $feeSetups = \App\Models\FeeSetup::where('fee_month', $currentMonth)
            ->where('status', 'Active')
            ->get();

        if ($feeSetups->isEmpty()) {
            $this->warn("No active Fee Setups found for {$currentMonth}.");
            return 0;
        }

        $this->info("Found " . $feeSetups->count() . " active Fee Setups. Starting generation...");

        $totalGenerated = 0;

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
            $yearMonth = now()->format('Ym'); // e.g. 202608
            $prefix = "INV-{$yearMonth}-";

            // Find the last invoice for the prefix to compute serial
            $lastInvoice = \App\Models\FeeInvoice::where('invoice_no', 'like', $prefix . '%')
                                                 ->orderBy('id', 'desc')
                                                 ->first();

            $lastSerial = 0;
            if ($lastInvoice) {
                $lastSerial = (int) str_replace($prefix, '', $lastInvoice->invoice_no);
            }

            // Set due date to 10th of current month
            $dueDate = now()->day(10)->toDateString();

            \DB::beginTransaction();
            try {
                $generatedCount = 0;
                foreach ($students as $student) {
                    // Check if invoice already exists
                    $exists = \App\Models\FeeInvoice::where('student_id', $student->id)
                                                    ->where('fee_setup_id', $feeSetup->id)
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
                $this->info(" - Generated {$generatedCount} invoices successfully.");
                $totalGenerated += $generatedCount;
            } catch (\Exception $e) {
                \DB::rollBack();
                $this->error(" - Error occurred: " . $e->getMessage());
            }
        }

        $this->info("==================================================");
        $this->info("Generation complete. Total invoices created: {$totalGenerated}");
        $this->info("==================================================");

        return 0;
    }
}
