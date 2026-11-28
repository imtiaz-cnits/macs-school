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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ReconcileClassOneMarch250Invoices extends Command
{
    /**
     * Exact authority-approved whitelist of the 27 regular active Class One students
     * whose March 2026 Monthly Fee invoices were generated at ৳250.00 on 2026-08-03
     * (before fee_setups.id = 3 was corrected to ৳700.00) and bulk-collected at ৳250.00
     * on 2026-09-21 despite guardians paying ৳700.00 cash.
     *
     * Strictly excludes Roll 1 (INV-202603-0001), Roll 10 (legitimate ৳250 custom fee),
     * Roll 2, Roll 45, Roll 19, Roll 46, and all other classes.
     *
     * @var array<int, string>
     */
    public const TARGET_INVOICE_NUMBERS = [
        'INV-202603-0003',
        'INV-202603-0004',
        'INV-202603-0005',
        'INV-202603-0006',
        'INV-202603-0007',
        'INV-202603-0008',
        'INV-202603-0009',
        'INV-202603-0013',
        'INV-202603-0014',
        'INV-202603-0016',
        'INV-202603-0018',
        'INV-202603-0020',
        'INV-202603-0021',
        'INV-202603-0022',
        'INV-202603-0025',
        'INV-202603-0027',
        'INV-202603-0028',
        'INV-202603-0030',
        'INV-202603-0032',
        'INV-202603-0033',
        'INV-202603-0034',
        'INV-202603-0035',
        'INV-202603-0037',
        'INV-202603-0039',
        'INV-202603-0040',
        'INV-202603-0042',
        'INV-202603-0044',
    ];

    public const OLD_AMOUNT = '250.00';
    public const NEW_AMOUNT = '700.00';
    public const DELTA_AMOUNT = '450.00';
    public const REVERSAL_REASON = 'Authority-approved reconciliation: March 2026 Class One setup error (250 -> 700 cash received)';
    public const ADJUSTMENT_REASON = 'Authority-approved reconciliation: March 2026 Class One setup error (250.00 -> 700.00)';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:reconcile-class-one-march-250
                            {--apply : Execute reversal, adjustment, and 700.00 re-collection atomically (omit for read-only dry-run)}
                            {--actor-id= : Optional admin user ID for reversal/adjustment audit logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dry-run-first atomic reconciliation for the 27 approved Class One March 2026 invoices (reverse ৳250, adjust to ৳700, re-collect ৳700 preserving original payment_date, zero SMS).';

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

        $this->info('====================================================================');
        $this->info('   MACS School — Issue #1 Reconciliation (Class One Mar 250 -> 700) ');
        $this->info('====================================================================');

        if (!$apply) {
            $this->warn('NOTICE: DRY-RUN MODE ACTIVE (Default). Zero database rows will be modified.');
        } else {
            $this->warn('APPLY MODE ACTIVE: Executing atomic reversal, adjustment (250 -> 700), and re-collection (zero SMS).');
        }

        $invoices = FeeInvoice::query()
            ->with(['student.schoolClass', 'student.customFees', 'feeSetup.category', 'payments'])
            ->whereIn('invoice_no', self::TARGET_INVOICE_NUMBERS)
            ->orderBy('student_id', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $eligible = [];
        $alreadyReconciled = [];
        $unexpected = [];

        $oldTotal = '0.00';
        $newTotal = '0.00';
        $tableRows = [];

        foreach ($invoices as $invoice) {
            $check = $this->evaluateInvoice($invoice);
            $student = $invoice->student;
            $roll = $student ? (string) ($student->roll_number ?? '-') : '-';
            $name = $student ? (string) ($student->student_name ?? 'Unknown') : 'Missing Student';
            $activePay = $invoice->payments->first(fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active');

            $tableRows[] = [
                $invoice->invoice_no,
                $roll,
                $name,
                bcadd((string) $invoice->net_amount, '0.00', 2),
                bcadd((string) $invoice->paid_amount, '0.00', 2),
                $activePay ? (string) $activePay->receipt_no : '-',
                $activePay ? (string) $activePay->payment_date : '-',
                strtoupper($check['bucket']),
                $check['detail'],
            ];

            if ($check['bucket'] === 'eligible') {
                $eligible[] = $invoice;
                $oldTotal = bcadd($oldTotal, self::OLD_AMOUNT, 2);
                $newTotal = bcadd($newTotal, self::NEW_AMOUNT, 2);
            } elseif ($check['bucket'] === 'already_reconciled') {
                $alreadyReconciled[] = $invoice;
            } else {
                $unexpected[] = [
                    'invoice_no' => $invoice->invoice_no,
                    'detail'     => $check['detail'],
                ];
            }
        }

        if (!empty($tableRows)) {
            $this->table(
                ['Invoice No', 'Roll', 'Student Name', 'Net', 'Paid', 'Active Receipt', 'Pay Date', 'State', 'Detail'],
                $tableRows
            );
        }

        $netDelta = bcsub($newTotal, $oldTotal, 2);

        $this->table(
            ['Summary Metric', 'Value'],
            [
                ['Whitelisted Invoices Found', (string) $invoices->count() . ' / ' . count(self::TARGET_INVOICE_NUMBERS)],
                ['Eligible for Reconciliation (250 -> 700)', (string) count($eligible)],
                ['Already Reconciled (700.00 Paid)', (string) count($alreadyReconciled)],
                ['Unexpected / Blocked Rows', (string) count($unexpected)],
                ['Old Total (Eligible)', '৳' . $oldTotal],
                ['New Total (Eligible)', '৳' . $newTotal],
                ['Net Financial Delta', '+৳' . $netDelta],
            ]
        );

        if (!empty($unexpected)) {
            foreach ($unexpected as $u) {
                $this->error("UNEXPECTED STATE [{$u['invoice_no']}]: {$u['detail']}");
            }
            $this->error('ABORTED: Unexpected state detected on one or more whitelisted invoices. Zero rows modified.');

            return self::FAILURE;
        }

        if (empty($eligible)) {
            $this->info('No eligible ৳250.00 invoices require reconciliation (0 rows modified).');

            return self::SUCCESS;
        }

        if (!$apply) {
            $this->warn('DRY-RUN MODE: 0 rows modified. Re-run with --apply to execute atomic reconciliation.');

            return self::SUCCESS;
        }

        $eligibleIds = array_map(static fn (FeeInvoice $inv): int => (int) $inv->id, $eligible);

        try {
            $appliedSummary = $transactionRunner->runWithDeadlockRetry(
                function () use ($eligibleIds, $explicitActor, $reversalService, $collectionService): array {
                    $reconciledRows = [];
                    $hasBranchCol = Schema::hasColumn('fee_payments', 'branch_id');
                    $hasFineCol = Schema::hasColumn('fee_payments', 'fine_amount');

                    foreach ($eligibleIds as $invoiceId) {
                        // Re-load and lock under V8 order (Student -> Invoice -> Payments) before mutating.
                        $lookup = FeeInvoice::select('id', 'student_id', 'invoice_no')->findOrFail($invoiceId);

                        $lockedStudent = Student::with(['schoolClass', 'customFees'])
                            ->where('id', (int) $lookup->student_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $lockedInvoice = FeeInvoice::with(['feeSetup.category'])
                            ->where('id', (int) $lookup->id)
                            ->where('student_id', (int) $lockedStudent->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->get();

                        $lockedInvoice->setRelation('student', $lockedStudent);
                        $lockedInvoice->setRelation('payments', $lockedPayments);

                        $recheck = $this->evaluateInvoice($lockedInvoice);
                        if ($recheck['bucket'] !== 'eligible') {
                            throw new RuntimeException(
                                "Concurrent state change on {$lockedInvoice->invoice_no}: {$recheck['bucket']} ({$recheck['detail']}). Rolling back batch."
                            );
                        }

                        /** @var FeePayment $origPayment */
                        $origPayment = $lockedPayments->first(
                            fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active'
                        );

                        $actor = $this->resolveAuthorizedActor($explicitActor, $origPayment, $collectionService);

                        // Capture original historical payment metadata prior to reversal.
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

                        // STEP 1: Immutable full reversal of the ৳250.00 payment via AdmissionPaymentReversalService.
                        $idempotencyKey = 'RECON-C1-202603-' . $lockedInvoice->invoice_no;
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
                        /** @var FeeInvoice $postReversalInvoice */
                        $postReversalInvoice = FeeInvoice::where('id', $lockedInvoice->id)->lockForUpdate()->firstOrFail();

                        if (
                            $reversedPayment->status !== 'Reversed'
                            || $postReversalInvoice->status !== 'Unpaid'
                            || bccomp(bcadd((string) $postReversalInvoice->paid_amount, '0.00', 2), '0.00', 2) !== 0
                            || bccomp(bcadd((string) $postReversalInvoice->due_amount, '0.00', 2), self::OLD_AMOUNT, 2) !== 0
                        ) {
                            throw new RuntimeException(
                                "Post-reversal invariant check failed on {$lockedInvoice->invoice_no}."
                            );
                        }

                        // STEP 2: Adjust invoice ৳250.00 -> ৳700.00 with full FeeAdjustmentLog audit trail.
                        $postReversalInvoice->amount = self::NEW_AMOUNT;
                        $postReversalInvoice->discount = '0.00';
                        $postReversalInvoice->net_amount = self::NEW_AMOUNT;
                        $postReversalInvoice->paid_amount = '0.00';
                        $postReversalInvoice->due_amount = self::NEW_AMOUNT;
                        $postReversalInvoice->status = 'Unpaid';
                        $postReversalInvoice->save();

                        FeeAdjustmentLog::create([
                            'fee_invoice_id'      => $postReversalInvoice->id,
                            'student_id'          => (int) $lockedStudent->id,
                            'user_id'             => (int) $actor->id,
                            'invoice_no'          => $postReversalInvoice->invoice_no,
                            'student_identity'    => $lockedStudent->student_identity ?? null,
                            'mode'                => 'increase',
                            'scope'               => 'single',
                            'adjustment_amount'   => self::DELTA_AMOUNT,
                            'previous_amount'     => self::OLD_AMOUNT,
                            'previous_discount'   => '0.00',
                            'previous_net_amount' => self::OLD_AMOUNT,
                            'previous_due_amount' => self::OLD_AMOUNT,
                            'new_amount'          => self::NEW_AMOUNT,
                            'new_discount'        => '0.00',
                            'new_net_amount'      => self::NEW_AMOUNT,
                            'new_due_amount'      => self::NEW_AMOUNT,
                            'paid_amount'         => '0.00',
                            'reason'              => self::ADJUSTMENT_REASON,
                        ]);

                        // STEP 3: Re-collect ৳700.00 preserving original historical payment_date, method, branch, and collector (ZERO SMS).
                        $dateCompact = Carbon::parse($origPaymentDate)->format('Ymd');
                        $newReceiptNo = "REC-{$dateCompact}-RECON-{$postReversalInvoice->id}";

                        $paymentPayload = [
                            'receipt_no'     => $newReceiptNo,
                            'fee_invoice_id' => $postReversalInvoice->id,
                            'student_id'     => (int) $lockedStudent->id,
                            'paid_amount'    => self::NEW_AMOUNT,
                            'payment_date'   => $origPaymentDate,
                            'payment_method' => $origPaymentMethod,
                            'note'           => "Authority-approved reconciliation (250->700); replaces reversed receipt {$origReceiptNo} ({$reversalResult['reversal_reference']})",
                            'collected_by'   => $origCollectedBy,
                        ];

                        if ($hasBranchCol && $origBranchId !== null) {
                            $paymentPayload['branch_id'] = $origBranchId;
                        }
                        if ($hasFineCol) {
                            $paymentPayload['fine_amount'] = '0.00';
                        }

                        $newPayment = FeePayment::create($paymentPayload);

                        $postReversalInvoice->paid_amount = self::NEW_AMOUNT;
                        $postReversalInvoice->due_amount = '0.00';
                        $postReversalInvoice->status = 'Paid';
                        $postReversalInvoice->save();

                        $reconciledRows[] = [
                            'invoice_id'         => (int) $postReversalInvoice->id,
                            'invoice_no'         => (string) $postReversalInvoice->invoice_no,
                            'student_id'         => (int) $lockedStudent->id,
                            'old_receipt_no'     => $origReceiptNo,
                            'reversal_reference' => (string) $reversalResult['reversal_reference'],
                            'new_receipt_no'     => (string) $newPayment->receipt_no,
                            'payment_date'       => $origPaymentDate,
                            'payment_method'     => $origPaymentMethod,
                            'actor_user_id'      => (int) $actor->id,
                        ];
                    }

                    return $reconciledRows;
                }
            );
        } catch (\Throwable $e) {
            $this->error('RECONCILIATION FAILED (transaction rolled back cleanly): ' . $e->getMessage());

            return self::FAILURE;
        }

        $count = count($appliedSummary);
        $appliedOldTotal = bcmul((string) $count, self::OLD_AMOUNT, 2);
        $appliedNewTotal = bcmul((string) $count, self::NEW_AMOUNT, 2);
        $appliedDelta = bcsub($appliedNewTotal, $appliedOldTotal, 2);

        Log::info('fees:reconcile-class-one-march-250 applied', [
            'reconciled_count' => $count,
            'old_total'        => $appliedOldTotal,
            'new_total'        => $appliedNewTotal,
            'net_delta'        => $appliedDelta,
            'invoices'         => $appliedSummary,
        ]);

        $this->info(
            "APPLIED SUCCESSFULLY: Reconciled {$count} Class One March 2026 invoice(s) (৳{$appliedOldTotal} -> ৳{$appliedNewTotal}, Net Delta: +৳{$appliedDelta}). Zero SMS notifications were sent."
        );

        return self::SUCCESS;
    }

    /**
     * Evaluate strict live eligibility predicates for a whitelisted invoice.
     *
     * @return array{bucket: 'eligible'|'already_reconciled'|'unexpected', detail: string}
     */
    private function evaluateInvoice(FeeInvoice $invoice): array
    {
        if (!in_array($invoice->invoice_no, self::TARGET_INVOICE_NUMBERS, true)) {
            return ['bucket' => 'unexpected', 'detail' => 'Invoice is not in the approved 27-invoice whitelist'];
        }

        if ($invoice->isAdmissionInvoice() || $invoice->isAdmissionInvoiceNumber() || !$invoice->isMonthlyTuitionInvoice()) {
            return ['bucket' => 'unexpected', 'detail' => 'Not a legacy Monthly Fee invoice'];
        }

        $student = $invoice->student;
        if (!$student) {
            return ['bucket' => 'unexpected', 'detail' => 'Student record missing'];
        }

        if (trim((string) $student->status) !== 'Active') {
            return ['bucket' => 'unexpected', 'detail' => "Student status is '{$student->status}' (expected Active)"];
        }

        $className = strtolower(trim((string) ($student->schoolClass?->class_name ?? '')));
        if ($className !== 'one') {
            return ['bucket' => 'unexpected', 'detail' => "Student class is '{$student->schoolClass?->class_name}' (expected One)"];
        }

        $monthlyCatId = (int) ($invoice->feeSetup?->fee_category_id ?? 0);
        $hasMonthlyCustomFee = $student->customFees->contains(
            fn ($cf): bool => (int) $cf->fee_category_id === $monthlyCatId
        );
        if ($hasMonthlyCustomFee) {
            return ['bucket' => 'unexpected', 'detail' => 'Student has a Monthly Fee custom fee override'];
        }

        $amount = bcadd((string) ($invoice->amount ?? '0'), '0.00', 2);
        $discount = bcadd((string) ($invoice->discount ?? '0'), '0.00', 2);
        $net = bcadd((string) ($invoice->net_amount ?? '0'), '0.00', 2);
        $fine = $invoice->fineAmountDecimal();
        $paid = bcadd((string) ($invoice->paid_amount ?? '0'), '0.00', 2);
        $due = bcadd((string) ($invoice->due_amount ?? '0'), '0.00', 2);
        $status = (string) ($invoice->status ?? '');

        $activePayments = $invoice->payments->filter(
            fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active'
        )->values();
        $reversedPayments = $invoice->payments->filter(
            fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Reversed'
        )->values();

        // Check idempotent already-reconciled state first (৳700 Paid with 1 Active ৳700 + >=1 Reversed ৳250).
        if (
            $status === 'Paid'
            && bccomp($amount, self::NEW_AMOUNT, 2) === 0
            && bccomp($discount, '0.00', 2) === 0
            && bccomp($net, self::NEW_AMOUNT, 2) === 0
            && bccomp($fine, '0.00', 2) === 0
            && bccomp($paid, self::NEW_AMOUNT, 2) === 0
            && bccomp($due, '0.00', 2) === 0
            && $activePayments->count() === 1
            && bccomp(bcadd((string) $activePayments->first()->paid_amount, '0.00', 2), self::NEW_AMOUNT, 2) === 0
            && $reversedPayments->contains(
                fn (FeePayment $p): bool => bccomp(bcadd((string) $p->paid_amount, '0.00', 2), self::OLD_AMOUNT, 2) === 0
            )
        ) {
            return ['bucket' => 'already_reconciled', 'detail' => 'Already reconciled to ৳700.00 Paid'];
        }

        // Strict eligibility predicates for ৳250 -> ৳700 reconciliation.
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
                'bucket' => 'unexpected',
                'detail' => "Invoice state mismatch (status={$status}, amt={$amount}, disc={$discount}, net={$net}, fine={$fine}, paid={$paid}, due={$due})",
            ];
        }

        if ($activePayments->count() !== 1) {
            return [
                'bucket' => 'unexpected',
                'detail' => "Expected exactly 1 Active payment, found {$activePayments->count()}",
            ];
        }

        $activePaid = bcadd((string) $activePayments->first()->paid_amount, '0.00', 2);
        if (bccomp($activePaid, self::OLD_AMOUNT, 2) !== 0) {
            return [
                'bucket' => 'unexpected',
                'detail' => "Active payment amount is {$activePaid} (expected 250.00)",
            ];
        }

        return ['bucket' => 'eligible', 'detail' => 'Ready: ৳250.00 -> ৳700.00 (+৳450.00)'];
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
}
