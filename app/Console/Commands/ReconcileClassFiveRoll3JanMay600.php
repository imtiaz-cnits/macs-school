<?php

namespace App\Console\Commands;

use App\Models\FeeAdjustmentLog;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionInvoiceService;
use App\Services\AdmissionPaymentReversalService;
use App\Services\FinancialTransactionRunner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ReconcileClassFiveRoll3JanMay600 extends Command
{
    public const TARGET_STUDENT_IDENTITY = '23010200282';

    /**
     * Exact authority-approved whitelist of the 5 Jan-May 2026 Monthly Fee invoices
     * for Class Five, Section A, Roll 3 (Nurjahan Tihan, student_id = 314) that were
     * billed & bulk-collected at ৳800.00 on 2026-11-17 under REC-20261117-4260-*
     * despite her active ৳600.00 custom fee and ৳3,000.00 (600 x 5) actual cash received.
     *
     * Strictly excludes Jun-Dec Monthly Fees, all Exam Fees, student_custom_fees,
     * and Class Five Roll 6 (Issue #3 — ON HOLD).
     *
     * @var array<int, string>
     */
    public const TARGET_INVOICE_NUMBERS = [
        'INV-202601-0181',
        'INV-202602-0282',
        'INV-202603-0327',
        'INV-202604-0282',
        'INV-202605-0282',
    ];

    public const EXPECTED_PAYMENT_DATE = '2026-11-17';

    /**
     * Numeric batch token compatible with FeePayment::extractMasterReceiptNo():
     * `REC-BULK-20261117-9314-{invoice_id}` extracts cleanly to master receipt
     * `REC-BULK-20261117-9314` so all 5 payments render both individually (৳600.00)
     * and together as ONE Master A5 POS receipt (৳3,000.00).
     */
    public const MASTER_RECEIPT_BATCH_CODE = '9314';

    public const OLD_AMOUNT = '800.00';
    public const STANDARD_GROSS_AMOUNT = '800.00';
    public const CUSTOM_DISCOUNT_AMOUNT = '200.00';
    public const TARGET_NET_AMOUNT = '600.00';
    public const DELTA_PER_MONTH = '200.00';

    public const REVERSAL_REASON = 'Authority-approved reconciliation: Class Five Roll 3 Jan-May 2026 custom fee error (800.00 -> 600.00 cash received)';
    public const ADJUSTMENT_REASON = 'RECON-C5-R3-2026: Authority-approved Jan-May 2026 custom fee normalization for Class Five Roll 3 (800.00 -> 800.00 - 200.00 = 600.00)';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:reconcile-class-five-roll3-jan-may-600
                            {--apply : Execute atomic reversal, adjustment, and 600.00 x 5 re-collection (omit for read-only dry-run)}
                            {--actor-id= : Optional admin user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dry-run-first atomic reconciliation for Issue #4 (Class Five Roll 3 Nurjahan Tihan): reverse Jan-May ৳800x5 receipts, normalize all 5 invoices to 800-200=৳600, and re-collect ৳600x5 preserving payment_date 2026-11-17 (zero SMS).';

    public function handle(
        FinancialTransactionRunner $transactionRunner,
        AdmissionPaymentReversalService $reversalService,
        AdmissionCollectionService $collectionService
    ): int {
        $apply = (bool) $this->option('apply');
        $rawActorId = $this->option('actor-id');

        $explicitActor = null;
        if ($rawActorId !== null && trim((string) $rawActorId) !== '') {
            if (!ctype_digit(trim((string) $rawActorId)) || (int) $rawActorId <= 0) {
                $this->error("Invalid --actor-id value '{$rawActorId}'. Expected a positive integer user ID.");

                return self::FAILURE;
            }
            try {
                $explicitActor = $collectionService->resolveAndAuthorizeAdminActor((int) $rawActorId);
            } catch (\Throwable $e) {
                $this->error('Invalid --actor-id: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        $this->info('========================================================================');
        $this->info('  MACS School — Issue #4 Reconciliation (Class Five Roll 3 Jan-May 600) ');
        $this->info('========================================================================');

        if (!$apply) {
            $this->warn('NOTICE: DRY-RUN MODE ACTIVE (Default). Zero database rows will be modified.');
        } else {
            $this->warn('APPLY MODE ACTIVE: Atomically reversing Jan-May ৳800x5, adjusting to 800-200=৳600, and re-collecting ৳600x5 on 2026-11-17 (zero SMS).');
        }

        $student = Student::with(['schoolClass', 'section', 'customFees'])
            ->where('student_identity', self::TARGET_STUDENT_IDENTITY)
            ->first();

        $invoices = FeeInvoice::with(['feeSetup.category', 'payments'])
            ->whereIn('invoice_no', self::TARGET_INVOICE_NUMBERS)
            ->orderBy('id', 'asc')
            ->get();

        $invoicesByNo = $invoices->keyBy('invoice_no');
        $expectedCount = count(self::TARGET_INVOICE_NUMBERS);

        $eligible = [];
        $alreadyReconciled = [];
        $blocked = [];
        $oldTotal = '0.00';
        $newTotal = '0.00';
        $tableRows = [];

        foreach (self::TARGET_INVOICE_NUMBERS as $invNo) {
            /** @var FeeInvoice|null $invoice */
            $invoice = $invoicesByNo->get($invNo);
            $payments = $invoice ? $invoice->payments : collect();
            $check = $this->evaluateInvoice($student, $invoice, $payments);

            $activePay = $payments->first(fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active');
            $monthLabel = $invoice ? (string) ($invoice->month_name ?: $this->monthFromInvoiceNo($invNo)) : $this->monthFromInvoiceNo($invNo);

            $tableRows[] = [
                $invNo,
                $monthLabel,
                $invoice ? bcadd((string) $invoice->amount, '0.00', 2) : '-',
                $invoice ? bcadd((string) $invoice->discount, '0.00', 2) : '-',
                $invoice ? bcadd((string) $invoice->net_amount, '0.00', 2) : '-',
                $invoice ? bcadd((string) $invoice->paid_amount, '0.00', 2) : '-',
                $activePay ? (string) $activePay->receipt_no : '-',
                $activePay ? (string) $activePay->payment_date : '-',
                strtoupper($check['state']),
                $check['detail'],
            ];

            if ($check['state'] === 'eligible') {
                $eligible[] = $invoice;
                $oldTotal = bcadd($oldTotal, self::OLD_AMOUNT, 2);
                $newTotal = bcadd($newTotal, self::TARGET_NET_AMOUNT, 2);
            } elseif ($check['state'] === 'already_reconciled') {
                $alreadyReconciled[] = $invoice;
            } else {
                $blocked[] = [
                    'invoice_no' => $invNo,
                    'detail'     => $check['detail'],
                ];
            }
        }

        // Enforce strict all-or-nothing batch consistency: mixed eligible + already_reconciled is blocked.
        if (!empty($eligible) && !empty($alreadyReconciled)) {
            $blocked[] = [
                'invoice_no' => 'BATCH',
                'detail'     => 'Partial reconciliation state detected (' . count($eligible) . ' eligible, ' . count($alreadyReconciled) . ' already reconciled). Expected all 5 eligible or all 5 already reconciled.',
            ];
        }

        $this->table(
            ['Invoice No', 'Month', 'Gross', 'Disc', 'Net', 'Paid', 'Active Receipt', 'Pay Date', 'Status', 'Detail'],
            $tableRows
        );

        $netDelta = bcsub($newTotal, $oldTotal, 2);
        $batchStatus = !empty($blocked)
            ? 'BLOCKED'
            : (count($alreadyReconciled) === $expectedCount ? 'ALREADY_RECONCILED' : 'ELIGIBLE');

        $this->table(
            ['Summary Metric', 'Value'],
            [
                ['Student', $student ? "{$student->student_name} (ID {$student->id}, {$student->student_identity}, Class {$student->schoolClass?->class_name}, Roll {$student->roll_number}, {$student->status})" : 'NOT FOUND'],
                ['Whitelisted Invoices Found', (string) $invoices->count() . ' / ' . $expectedCount],
                ['Eligible (800 -> 800 - 200 = 600)', (string) count($eligible)],
                ['Already Reconciled (600.00 Paid)', (string) count($alreadyReconciled)],
                ['Blocked / Unexpected', (string) count($blocked)],
                ['Old Total -> New Total (Eligible)', "৳{$oldTotal} -> ৳{$newTotal}"],
                ['Net/Collected Delta', '৳' . $netDelta],
                ['Payment Date Preserved', self::EXPECTED_PAYMENT_DATE],
                ['Replacement Master Receipt', $this->buildMasterReceiptNo(self::EXPECTED_PAYMENT_DATE)],
                ['Status', $batchStatus],
            ]
        );

        if (!empty($blocked)) {
            foreach ($blocked as $b) {
                $this->error("BLOCKED [{$b['invoice_no']}]: {$b['detail']}");
            }
            $this->error('ABORTED: One or more safety predicates failed. Zero rows modified.');

            return self::FAILURE;
        }

        if (count($alreadyReconciled) === $expectedCount) {
            $this->info('No action required: All 5 Jan-May 2026 invoices are already reconciled to 800.00 - 200.00 = ৳600.00 Paid (0 rows modified).');

            return self::SUCCESS;
        }

        if (!$apply) {
            $this->warn('DRY-RUN MODE: 0 rows modified. Re-run with --apply to execute atomic reconciliation.');

            return self::SUCCESS;
        }

        $studentId = (int) $student->id;
        $eligibleIds = collect($eligible)
            ->map(static fn (FeeInvoice $inv): int => (int) $inv->id)
            ->sort()
            ->values()
            ->all();

        try {
            $applied = $transactionRunner->runWithDeadlockRetry(
                function () use ($studentId, $eligibleIds, $expectedCount, $explicitActor, $reversalService, $collectionService): array {
                    // Strict V8 Lock Order: Tier 1 Student -> Tier 2 FeeInvoices id ASC -> Tier 3 FeePayments id ASC
                    $lockedStudent = Student::with(['schoolClass', 'section', 'customFees'])
                        ->where('id', $studentId)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->firstOrFail();

                    $lockedInvoices = FeeInvoice::with(['feeSetup.category'])
                        ->whereIn('id', $eligibleIds)
                        ->where('student_id', (int) $lockedStudent->id)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($lockedInvoices->count() !== $expectedCount) {
                        throw new RuntimeException(
                            "Expected {$expectedCount} locked invoices for student {$lockedStudent->student_identity}, found {$lockedInvoices->count()}."
                        );
                    }

                    $lockedPaymentsAll = FeePayment::whereIn('fee_invoice_id', $lockedInvoices->pluck('id')->all())
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $paymentsByInvoiceId = $lockedPaymentsAll->groupBy('fee_invoice_id');

                    // Re-verify ALL 5 invoices under lock BEFORE mutating any single row (strict all-or-nothing).
                    $alreadyCount = 0;
                    $eligibleLocked = [];
                    foreach ($lockedInvoices as $lockedInvoice) {
                        $invPays = $paymentsByInvoiceId->get($lockedInvoice->id, collect());
                        $recheck = $this->evaluateInvoice($lockedStudent, $lockedInvoice, $invPays);

                        if ($recheck['state'] === 'already_reconciled') {
                            $alreadyCount++;
                            continue;
                        }
                        if ($recheck['state'] !== 'eligible') {
                            throw new RuntimeException(
                                "Precondition changed under lock on {$lockedInvoice->invoice_no}: {$recheck['detail']}"
                            );
                        }
                        $eligibleLocked[] = $lockedInvoice;
                    }

                    if ($alreadyCount === $expectedCount) {
                        return ['already_reconciled' => true];
                    }
                    if (count($eligibleLocked) !== $expectedCount) {
                        throw new RuntimeException(
                            'Partial state detected under lock (' . count($eligibleLocked) . ' eligible, ' . $alreadyCount . ' already reconciled). Rolling back.'
                        );
                    }

                    $hasBranchCol = Schema::hasColumn('fee_payments', 'branch_id');
                    $hasFineCol = Schema::hasColumn('fee_payments', 'fine_amount');

                    $masterReceiptNo = $this->buildMasterReceiptNo(self::EXPECTED_PAYMENT_DATE);
                    $reconciledRows = [];

                    foreach ($eligibleLocked as $lockedInvoice) {
                        $invPays = $paymentsByInvoiceId->get($lockedInvoice->id, collect());
                        /** @var FeePayment $origPayment */
                        $origPayment = $invPays->first(
                            fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active'
                        );

                        $actor = $this->resolveAuthorizedActor($explicitActor, $origPayment, $collectionService);

                        // Capture original historical payment metadata before reversal.
                        $origPaymentId = (int) $origPayment->id;
                        $origReceiptNo = (string) $origPayment->receipt_no;
                        $origPaymentDate = Carbon::parse((string) $origPayment->payment_date)->toDateString();
                        $origPaymentMethod = trim((string) ($origPayment->payment_method ?? '')) ?: 'Cash';
                        $origBranchId = !empty($origPayment->branch_id)
                            ? (int) $origPayment->branch_id
                            : (!empty($lockedInvoice->branch_id) ? (int) $lockedInvoice->branch_id : null);
                        $origCollectedBy = !empty($origPayment->collected_by)
                            ? (int) $origPayment->collected_by
                            : (int) $actor->id;

                        // STEP 1: Immutable full reversal of ONLY this month's ৳800.00 payment row.
                        $idempotencyKey = 'RECON-C5-R3-2026-' . $lockedInvoice->invoice_no;
                        $reversalResult = $reversalService->reversePaymentWithinTransaction(
                            $origPaymentId,
                            self::REVERSAL_REASON,
                            (int) $actor->id,
                            $idempotencyKey,
                            [
                                'student_id'     => (int) $lockedStudent->id,
                                'fee_invoice_id' => (int) $lockedInvoice->id,
                            ]
                        );

                        /** @var FeePayment $reversedPayment */
                        $reversedPayment = $reversalResult['payment'];
                        /** @var FeeInvoice $postRevInvoice */
                        $postRevInvoice = FeeInvoice::where('id', $lockedInvoice->id)->lockForUpdate()->firstOrFail();

                        if (
                            $reversedPayment->status !== 'Reversed'
                            || $postRevInvoice->status !== 'Unpaid'
                            || bccomp(bcadd((string) $postRevInvoice->paid_amount, '0.00', 2), '0.00', 2) !== 0
                            || bccomp(bcadd((string) $postRevInvoice->due_amount, '0.00', 2), self::OLD_AMOUNT, 2) !== 0
                        ) {
                            throw new RuntimeException(
                                "Post-reversal invariant check failed on {$lockedInvoice->invoice_no}."
                            );
                        }

                        // STEP 2: Normalize invoice to match Jun-Dec custom fee structure (800.00 - 200.00 = 600.00).
                        $postRevInvoice->amount = self::STANDARD_GROSS_AMOUNT;
                        $postRevInvoice->discount = self::CUSTOM_DISCOUNT_AMOUNT;
                        $postRevInvoice->net_amount = self::TARGET_NET_AMOUNT;
                        $postRevInvoice->paid_amount = '0.00';
                        $postRevInvoice->due_amount = self::TARGET_NET_AMOUNT;
                        $postRevInvoice->status = 'Unpaid';
                        $postRevInvoice->save();

                        $adjLog = FeeAdjustmentLog::create([
                            'fee_invoice_id'      => $postRevInvoice->id,
                            'student_id'          => (int) $lockedStudent->id,
                            'user_id'             => (int) $actor->id,
                            'invoice_no'          => $postRevInvoice->invoice_no,
                            'student_identity'    => $lockedStudent->student_identity,
                            'mode'                => 'decrease',
                            'scope'               => 'single',
                            'adjustment_amount'   => self::DELTA_PER_MONTH,
                            'previous_amount'     => self::OLD_AMOUNT,
                            'previous_discount'   => '0.00',
                            'previous_net_amount' => self::OLD_AMOUNT,
                            'previous_due_amount' => self::OLD_AMOUNT,
                            'new_amount'          => self::STANDARD_GROSS_AMOUNT,
                            'new_discount'        => self::CUSTOM_DISCOUNT_AMOUNT,
                            'new_net_amount'      => self::TARGET_NET_AMOUNT,
                            'new_due_amount'      => self::TARGET_NET_AMOUNT,
                            'paid_amount'         => '0.00',
                            'reason'              => self::ADJUSTMENT_REASON,
                        ]);

                        // STEP 3: Re-collect ৳600.00 preserving original payment_date ('2026-11-17'),
                        // method, branch, and collector, grouped cleanly under one Master Receipt (ZERO SMS).
                        $newReceiptNo = $this->buildChildReceiptNo($origPaymentDate, (int) $postRevInvoice->id);
                        if (FeePayment::where('receipt_no', $newReceiptNo)->exists()) {
                            throw new RuntimeException(
                                "Replacement receipt number collision detected: {$newReceiptNo}."
                            );
                        }

                        $paymentPayload = [
                            'receipt_no'     => $newReceiptNo,
                            'fee_invoice_id' => $postRevInvoice->id,
                            'student_id'     => (int) $lockedStudent->id,
                            'paid_amount'    => self::TARGET_NET_AMOUNT,
                            'payment_date'   => $origPaymentDate,
                            'payment_method' => $origPaymentMethod,
                            'note'           => "Authority-approved reconciliation (RECON-C5-R3-2026: 800->600 custom fee); replaces reversed receipt {$origReceiptNo} ({$reversalResult['reversal_reference']})",
                            'collected_by'   => $origCollectedBy,
                        ];

                        if ($hasBranchCol && $origBranchId !== null) {
                            $paymentPayload['branch_id'] = $origBranchId;
                        }
                        if ($hasFineCol) {
                            $paymentPayload['fine_amount'] = '0.00';
                        }

                        $newPayment = FeePayment::create($paymentPayload);

                        $postRevInvoice->paid_amount = self::TARGET_NET_AMOUNT;
                        $postRevInvoice->due_amount = '0.00';
                        $postRevInvoice->status = 'Paid';
                        $postRevInvoice->save();

                        $reconciledRows[] = [
                            'invoice_id'         => (int) $postRevInvoice->id,
                            'invoice_no'         => (string) $postRevInvoice->invoice_no,
                            'month'              => (string) ($postRevInvoice->month_name ?: $this->monthFromInvoiceNo($postRevInvoice->invoice_no)),
                            'old_receipt_no'     => $origReceiptNo,
                            'reversal_reference' => (string) $reversalResult['reversal_reference'],
                            'new_receipt_no'     => (string) $newPayment->receipt_no,
                            'payment_date'       => $origPaymentDate,
                            'payment_method'     => $origPaymentMethod,
                            'adjustment_log_id'  => (int) $adjLog->id,
                            'actor_user_id'      => (int) $actor->id,
                        ];
                    }

                    $count = count($reconciledRows);
                    $appliedOldTotal = bcmul((string) $count, self::OLD_AMOUNT, 2);
                    $appliedNewTotal = bcmul((string) $count, self::TARGET_NET_AMOUNT, 2);
                    $appliedDelta = bcsub($appliedNewTotal, $appliedOldTotal, 2);

                    return [
                        'already_reconciled' => false,
                        'student_id'         => (int) $lockedStudent->id,
                        'student_identity'   => (string) $lockedStudent->student_identity,
                        'reconciled_count'   => $count,
                        'master_receipt_no'  => $masterReceiptNo,
                        'payment_date'       => self::EXPECTED_PAYMENT_DATE,
                        'old_total'          => $appliedOldTotal,
                        'new_total'          => $appliedNewTotal,
                        'net_delta'          => $appliedDelta,
                        'invoices'           => $reconciledRows,
                    ];
                }
            );
        } catch (\Throwable $e) {
            $this->error('RECONCILIATION FAILED (transaction rolled back cleanly): ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($applied['already_reconciled']) {
            $this->info('No action required: All 5 Jan-May 2026 invoices were already reconciled (0 rows modified).');

            return self::SUCCESS;
        }

        Log::info('fees:reconcile-class-five-roll3-jan-may-600 applied', $applied);

        $this->info(
            "APPLIED SUCCESSFULLY: Reconciled {$applied['reconciled_count']} Jan-May 2026 invoice(s) for Nurjahan Tihan (৳{$applied['old_total']} -> ৳{$applied['new_total']}, Net/Collected Delta: ৳{$applied['net_delta']}, Master Receipt: {$applied['master_receipt_no']}, Payment Date: {$applied['payment_date']}). Jun-Dec Monthly Fees, Exam Fees, custom fees, Class Five Roll 6, and SMS logs were not touched."
        );

        return self::SUCCESS;
    }

    public function buildMasterReceiptNo(string $paymentDate): string
    {
        $dateCompact = Carbon::parse($paymentDate)->format('Ymd');

        return "REC-BULK-{$dateCompact}-" . self::MASTER_RECEIPT_BATCH_CODE;
    }

    public function buildChildReceiptNo(string $paymentDate, int $invoiceId): string
    {
        return $this->buildMasterReceiptNo($paymentDate) . "-{$invoiceId}";
    }

    /**
     * Evaluate strict student, custom fee, setup baseline, invoice, and idempotency predicates.
     *
     * @param  Collection<int, FeePayment>  $payments
     * @return array{state: 'eligible'|'already_reconciled'|'blocked', detail: string}
     */
    private function evaluateInvoice(?Student $student, ?FeeInvoice $invoice, Collection $payments): array
    {
        if (!$student) {
            return ['state' => 'blocked', 'detail' => 'Target student (' . self::TARGET_STUDENT_IDENTITY . ') not found.'];
        }

        if (trim((string) $student->status) !== 'Active') {
            return ['state' => 'blocked', 'detail' => "Student status is '{$student->status}' (expected Active)."];
        }

        if (strtolower(trim((string) ($student->schoolClass?->class_name ?? ''))) !== 'five') {
            return ['state' => 'blocked', 'detail' => "Student class is '{$student->schoolClass?->class_name}' (expected Five)."];
        }

        if ((int) ($student->roll_number ?? 0) !== 3) {
            return ['state' => 'blocked', 'detail' => "Student roll is '{$student->roll_number}' (expected 3)."];
        }

        if (!$invoice) {
            return ['state' => 'blocked', 'detail' => 'Whitelisted invoice not found in database.'];
        }

        if (!in_array($invoice->invoice_no, self::TARGET_INVOICE_NUMBERS, true)) {
            return ['state' => 'blocked', 'detail' => "Invoice {$invoice->invoice_no} is not in the approved 5-invoice whitelist."];
        }

        if ((int) $invoice->student_id !== (int) $student->id) {
            return ['state' => 'blocked', 'detail' => "Invoice {$invoice->invoice_no} does not belong to student " . self::TARGET_STUDENT_IDENTITY . '.'];
        }

        if ($invoice->isAdmissionInvoice() || $invoice->isAdmissionInvoiceNumber() || !$invoice->isMonthlyTuitionInvoice()) {
            return ['state' => 'blocked', 'detail' => "Invoice {$invoice->invoice_no} is not a legacy Monthly Fee invoice."];
        }

        $setupAmount = bcadd((string) ($invoice->feeSetup?->amount ?? '0'), '0.00', 2);
        if (bccomp($setupAmount, self::STANDARD_GROSS_AMOUNT, 2) !== 0) {
            return ['state' => 'blocked', 'detail' => "Class Five Monthly Fee setup amount is {$setupAmount} (expected 800.00)."];
        }

        $monthlyCatId = (int) ($invoice->feeSetup?->fee_category_id ?? 0);
        $monthlyCustomFee = $student->customFees->first(
            fn ($cf): bool => (int) $cf->fee_category_id === $monthlyCatId
        );
        if (!$monthlyCustomFee || bccomp(bcadd((string) $monthlyCustomFee->amount, '0.00', 2), self::TARGET_NET_AMOUNT, 2) !== 0) {
            $actualCf = $monthlyCustomFee ? bcadd((string) $monthlyCustomFee->amount, '0.00', 2) : 'NONE';

            return ['state' => 'blocked', 'detail' => "Student Monthly Fee custom rate is {$actualCf} (expected 600.00)."];
        }

        $amount = bcadd((string) ($invoice->amount ?? '0'), '0.00', 2);
        $discount = bcadd((string) ($invoice->discount ?? '0'), '0.00', 2);
        $net = bcadd((string) ($invoice->net_amount ?? '0'), '0.00', 2);
        $fine = $invoice->fineAmountDecimal();
        $paid = bcadd((string) ($invoice->paid_amount ?? '0'), '0.00', 2);
        $due = bcadd((string) ($invoice->due_amount ?? '0'), '0.00', 2);
        $status = (string) ($invoice->status ?? '');

        $activePayments = $payments->filter(
            fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active'
        )->values();
        $reversedPayments = $payments->filter(
            fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Reversed'
        )->values();

        $expectedMaster = $this->buildMasterReceiptNo(self::EXPECTED_PAYMENT_DATE);

        // Strict idempotency check first: 800 - 200 = 600 Paid with 1 Active 600.00 reconciliation payment + Reversed 800.00 payment.
        if (
            $status === 'Paid'
            && bccomp($amount, self::STANDARD_GROSS_AMOUNT, 2) === 0
            && bccomp($discount, self::CUSTOM_DISCOUNT_AMOUNT, 2) === 0
            && bccomp($net, self::TARGET_NET_AMOUNT, 2) === 0
            && bccomp($fine, '0.00', 2) === 0
            && bccomp($paid, self::TARGET_NET_AMOUNT, 2) === 0
            && bccomp($due, '0.00', 2) === 0
            && $activePayments->count() === 1
            && bccomp(bcadd((string) $activePayments->first()->paid_amount, '0.00', 2), self::TARGET_NET_AMOUNT, 2) === 0
            && $activePayments->first()->belongsToMasterReceipt($expectedMaster)
            && Carbon::parse((string) $activePayments->first()->payment_date)->toDateString() === self::EXPECTED_PAYMENT_DATE
            && $reversedPayments->contains(
                fn (FeePayment $p): bool => bccomp(bcadd((string) $p->paid_amount, '0.00', 2), self::OLD_AMOUNT, 2) === 0
            )
        ) {
            return ['state' => 'already_reconciled', 'detail' => 'Already reconciled to 800.00 - 200.00 = ৳600.00 Paid'];
        }

        // Strict eligibility predicates for 800 -> 600 reconciliation.
        if (
            $status !== 'Paid'
            || bccomp($amount, self::OLD_AMOUNT, 2) !== 0
            || bccomp($discount, '0.00', 2) !== 0
            || bccomp($net, self::OLD_AMOUNT, 2) !== 0
            || bccomp($fine, '0.00', 2) !== 0
            || bccomp($paid, self::OLD_AMOUNT, 2) !== 0
            || bccomp($due, '0.00', 2) !== 0
        ) {
            return [
                'state'  => 'blocked',
                'detail' => "Invoice state mismatch (status={$status}, amt={$amount}, disc={$discount}, net={$net}, fine={$fine}, paid={$paid}, due={$due}; expected Paid 800.00).",
            ];
        }

        if ($activePayments->count() !== 1) {
            return [
                'state'  => 'blocked',
                'detail' => "Expected exactly 1 Active payment on {$invoice->invoice_no}, found {$activePayments->count()}.",
            ];
        }

        /** @var FeePayment $onlyActive */
        $onlyActive = $activePayments->first();
        $activePaid = bcadd((string) $onlyActive->paid_amount, '0.00', 2);
        if (bccomp($activePaid, self::OLD_AMOUNT, 2) !== 0) {
            return [
                'state'  => 'blocked',
                'detail' => "Active payment amount is {$activePaid} (expected 800.00).",
            ];
        }

        $activePayDate = Carbon::parse((string) $onlyActive->payment_date)->toDateString();
        if ($activePayDate !== self::EXPECTED_PAYMENT_DATE) {
            return [
                'state'  => 'blocked',
                'detail' => "Active payment date is {$activePayDate} (expected " . self::EXPECTED_PAYMENT_DATE . ').',
            ];
        }

        return ['state' => 'eligible', 'detail' => 'Ready: ৳800.00 -> 800.00 - 200.00 = ৳600.00 (-৳200.00)'];
    }

    private function resolveAuthorizedActor(
        ?User $explicitActor,
        FeePayment $origPayment,
        AdmissionCollectionService $collectionService
    ): User {
        if ($explicitActor !== null) {
            return $explicitActor;
        }

        if (!empty($origPayment->collected_by)) {
            $collector = User::with('roles')->find((int) $origPayment->collected_by);
            if ($collector && AdmissionInvoiceService::userCanGrantDiscount($collector)) {
                return $collector;
            }
        }

        $fallbackAdmin = User::with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['super-admin', 'admin']))
            ->orderBy('id', 'asc')
            ->first();

        if (!$fallbackAdmin) {
            throw new RuntimeException('Unable to resolve an authorized Admin/Super Admin actor user. Please supply --actor-id=.');
        }

        return $collectionService->resolveAndAuthorizeAdminActor((int) $fallbackAdmin->id);
    }

    private function monthFromInvoiceNo(string $invoiceNo): string
    {
        if (preg_match('/^INV-\d{4}(\d{2})-/i', $invoiceNo, $m)) {
            $mNum = (int) $m[1];
            if ($mNum >= 1 && $mNum <= 12) {
                return Carbon::createFromDate(2026, $mNum, 1)->format('F');
            }
        }

        return 'Unknown';
    }
}
