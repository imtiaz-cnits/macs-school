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

class ReconcileClassOneRoll1March200 extends Command
{
    public const TARGET_STUDENT_IDENTITY = '23120P00092';
    public const TARGET_INVOICE_NO = 'INV-202603-0001';
    public const PROTECTED_EXAM_INVOICE_NO = 'INV-202611-0616';

    public const OLD_AMOUNT = '250.00';
    public const STANDARD_GROSS_AMOUNT = '700.00';
    public const CUSTOM_DISCOUNT_AMOUNT = '500.00';
    public const TARGET_NET_AMOUNT = '200.00';
    public const DELTA_AMOUNT = '50.00';

    public const REVERSAL_REASON = 'Authority-approved reconciliation: Class One Roll 1 March 2026 setup error (250.00 -> 200.00 custom fee)';
    public const ADJUSTMENT_REASON = 'RECON-C1-R1-202603: Authority-approved March 2026 normalization for Class One Roll 1 (250.00 -> 700.00 - 500.00 = 200.00 custom fee)';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:reconcile-class-one-roll1-march-200
                            {--apply : Execute atomic reversal, adjustment, and 200.00 re-collection (omit for read-only dry-run)}
                            {--actor-id= : Optional admin user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dry-run-first atomic reconciliation for Issue #2 (Class One Roll 1 Moriom Akhter Sinha): reverse March ৳250 receipt, normalize INV-202603-0001 to 700-500=৳200, and re-collect ৳200 preserving original payment_date (leaving Nov Monthly & Nov Exam Fee untouched, zero SMS).';

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
        $this->info('   MACS School — Issue #2 Reconciliation (Roll 1 March 250 -> 200)  ');
        $this->info('====================================================================');

        if (!$apply) {
            $this->warn('NOTICE: DRY-RUN MODE ACTIVE (Default). Zero database rows will be modified.');
        } else {
            $this->warn('APPLY MODE ACTIVE: Reversing March ৳250 receipt, adjusting INV-202603-0001 to 700-500=৳200, and re-collecting ৳200 (zero SMS).');
        }

        $student = Student::with(['schoolClass', 'section', 'customFees'])
            ->where('student_identity', self::TARGET_STUDENT_IDENTITY)
            ->first();

        $invoice = FeeInvoice::with(['feeSetup.category', 'payments'])
            ->where('invoice_no', self::TARGET_INVOICE_NO)
            ->first();

        $examInvoice = FeeInvoice::where('invoice_no', self::PROTECTED_EXAM_INVOICE_NO)->first();

        $payments = $invoice ? $invoice->payments : collect();
        $evaluation = $this->evaluateState($student, $invoice, $payments);

        $activePay = $payments->first(fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active');
        $examSummary = $examInvoice
            ? "Untouched ৳" . bcadd((string) $examInvoice->net_amount, '0.00', 2) . " ({$examInvoice->status})"
            : 'Not Present (Untouched)';

        $this->table(
            ['Field', 'Value'],
            [
                ['Student', $student ? "{$student->student_name} (ID {$student->id}, {$student->student_identity}, Class {$student->schoolClass?->class_name}, Roll {$student->roll_number}, {$student->status})" : 'NOT FOUND'],
                ['Target Invoice', $invoice ? "{$invoice->invoice_no} — Gross ৳" . bcadd((string) $invoice->amount, '0.00', 2) . ", Disc ৳" . bcadd((string) $invoice->discount, '0.00', 2) . ", Net ৳" . bcadd((string) $invoice->net_amount, '0.00', 2) . ", Paid ৳" . bcadd((string) $invoice->paid_amount, '0.00', 2) . " ({$invoice->status})" : 'NOT FOUND'],
                ['Active Receipt / Pay Date', $activePay ? "{$activePay->receipt_no} ({$activePay->payment_date}, {$activePay->payment_method})" : '-'],
                ['Planned Normalization', $evaluation['state'] === 'eligible' ? '250.00 -> 700.00 - 500.00 = 200.00 (Net Delta: -50.00, Collected Delta: -50.00)' : 'None'],
                ['Payment Date Preserved', $activePay ? (string) $activePay->payment_date : '-'],
                ['November Exam Fee (' . self::PROTECTED_EXAM_INVOICE_NO . ')', $examSummary],
                ['Status', strtoupper($evaluation['state'])],
                ['Detail', $evaluation['detail']],
            ]
        );

        if ($evaluation['state'] === 'blocked') {
            $this->error('ABORTED: ' . $evaluation['detail'] . ' Zero rows modified.');

            return self::FAILURE;
        }

        if ($evaluation['state'] === 'already_reconciled') {
            $this->info('No action required: INV-202603-0001 is already reconciled to 700.00 - 500.00 = ৳200.00 Paid (0 rows modified).');

            return self::SUCCESS;
        }

        if (!$apply) {
            $this->warn('DRY-RUN MODE: 0 rows modified. Re-run with --apply to execute atomic reconciliation.');

            return self::SUCCESS;
        }

        $studentId = (int) $student->id;
        $invoiceId = (int) $invoice->id;

        try {
            $applied = $transactionRunner->runWithDeadlockRetry(
                function () use ($studentId, $invoiceId, $explicitActor, $reversalService, $collectionService): array {
                    // Strict V8 Lock Order: Tier 1 Student -> Tier 2 FeeInvoice -> Tier 3 FeePayments ASC
                    $lockedStudent = Student::with(['schoolClass', 'section', 'customFees'])
                        ->where('id', $studentId)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->firstOrFail();

                    $lockedInvoice = FeeInvoice::with(['feeSetup.category'])
                        ->where('id', $invoiceId)
                        ->where('student_id', (int) $lockedStudent->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $recheck = $this->evaluateState($lockedStudent, $lockedInvoice, $lockedPayments);
                    if ($recheck['state'] === 'already_reconciled') {
                        return ['already_reconciled' => true];
                    }
                    if ($recheck['state'] !== 'eligible') {
                        throw new RuntimeException(
                            "Precondition changed under lock on {$lockedInvoice->invoice_no}: {$recheck['detail']}"
                        );
                    }

                    /** @var FeePayment $origPayment */
                    $origPayment = $lockedPayments->first(
                        fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active'
                    );

                    $actor = $this->resolveAuthorizedActor($explicitActor, $origPayment, $collectionService);

                    // Preserve exact original payment metadata before reversal.
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

                    // STEP 1: Reverse ONLY the March ৳250.00 payment row (sibling November payment remains untouched Active).
                    $idempotencyKey = 'RECON-C1-R1-202603-' . $lockedInvoice->invoice_no;
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

                    // STEP 2: Normalize invoice to match Jan/Feb/Apr-Dec custom fee structure (700.00 - 500.00 = 200.00).
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
                        'adjustment_amount'   => self::DELTA_AMOUNT,
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

                    // STEP 3: Re-collect ৳200.00 preserving original payment_date (2026-11-18), method, branch, collector (ZERO SMS).
                    $dateCompact = Carbon::parse($origPaymentDate)->format('Ymd');
                    $newReceiptNo = "REC-{$dateCompact}-RECON-{$postRevInvoice->id}";

                    $paymentPayload = [
                        'receipt_no'     => $newReceiptNo,
                        'fee_invoice_id' => $postRevInvoice->id,
                        'student_id'     => (int) $lockedStudent->id,
                        'paid_amount'    => self::TARGET_NET_AMOUNT,
                        'payment_date'   => $origPaymentDate,
                        'payment_method' => $origPaymentMethod,
                        'note'           => "Authority-approved reconciliation (250->200 custom fee); replaces reversed receipt {$origReceiptNo} ({$reversalResult['reversal_reference']})",
                        'collected_by'   => $origCollectedBy,
                    ];

                    if (Schema::hasColumn('fee_payments', 'branch_id') && $origBranchId !== null) {
                        $paymentPayload['branch_id'] = $origBranchId;
                    }
                    if (Schema::hasColumn('fee_payments', 'fine_amount')) {
                        $paymentPayload['fine_amount'] = '0.00';
                    }

                    $newPayment = FeePayment::create($paymentPayload);

                    $postRevInvoice->paid_amount = self::TARGET_NET_AMOUNT;
                    $postRevInvoice->due_amount = '0.00';
                    $postRevInvoice->status = 'Paid';
                    $postRevInvoice->save();

                    return [
                        'already_reconciled' => false,
                        'student_id'         => (int) $lockedStudent->id,
                        'student_identity'   => (string) $lockedStudent->student_identity,
                        'invoice_id'         => (int) $postRevInvoice->id,
                        'invoice_no'         => (string) $postRevInvoice->invoice_no,
                        'old_receipt_no'     => $origReceiptNo,
                        'reversal_reference' => (string) $reversalResult['reversal_reference'],
                        'new_receipt_no'     => (string) $newPayment->receipt_no,
                        'payment_date'       => $origPaymentDate,
                        'payment_method'     => $origPaymentMethod,
                        'old_net'            => self::OLD_AMOUNT,
                        'new_net'            => self::TARGET_NET_AMOUNT,
                        'net_delta'          => '-' . self::DELTA_AMOUNT,
                        'adjustment_log_id'  => (int) $adjLog->id,
                        'actor_user_id'      => (int) $actor->id,
                    ];
                }
            );
        } catch (\Throwable $e) {
            $this->error('RECONCILIATION FAILED (transaction rolled back cleanly): ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($applied['already_reconciled']) {
            $this->info('No action required: INV-202603-0001 was already reconciled (0 rows modified).');

            return self::SUCCESS;
        }

        Log::info('fees:reconcile-class-one-roll1-march-200 applied', $applied);

        $this->info(
            "APPLIED SUCCESSFULLY: Reconciled {$applied['invoice_no']} (৳250.00 -> 700.00 - 500.00 = ৳200.00 Paid, Receipt: {$applied['new_receipt_no']}, Date: {$applied['payment_date']}, Net Delta: -৳50.00). Sibling November payment, November Exam Fee, and SMS logs were not touched."
        );

        return self::SUCCESS;
    }

    /**
     * Evaluate strict student, custom fee, setup baseline, invoice, and idempotency predicates.
     *
     * @param  Collection<int, FeePayment>  $payments
     * @return array{state: 'eligible'|'already_reconciled'|'blocked', detail: string}
     */
    private function evaluateState(?Student $student, ?FeeInvoice $invoice, Collection $payments): array
    {
        if (!$student) {
            return ['state' => 'blocked', 'detail' => 'Target student (' . self::TARGET_STUDENT_IDENTITY . ') not found.'];
        }

        if (trim((string) $student->status) !== 'Active') {
            return ['state' => 'blocked', 'detail' => "Student status is '{$student->status}' (expected Active)."];
        }

        if (strtolower(trim((string) ($student->schoolClass?->class_name ?? ''))) !== 'one') {
            return ['state' => 'blocked', 'detail' => "Student class is '{$student->schoolClass?->class_name}' (expected One)."];
        }

        if ((int) ($student->roll_number ?? 0) !== 1) {
            return ['state' => 'blocked', 'detail' => "Student roll is '{$student->roll_number}' (expected 1)."];
        }

        if (!$invoice) {
            return ['state' => 'blocked', 'detail' => 'Target invoice ' . self::TARGET_INVOICE_NO . ' not found.'];
        }

        if ((int) $invoice->student_id !== (int) $student->id) {
            return ['state' => 'blocked', 'detail' => 'Invoice ' . self::TARGET_INVOICE_NO . ' does not belong to student ' . self::TARGET_STUDENT_IDENTITY . '.'];
        }

        if ($invoice->isAdmissionInvoice() || $invoice->isAdmissionInvoiceNumber() || !$invoice->isMonthlyTuitionInvoice()) {
            return ['state' => 'blocked', 'detail' => 'Invoice ' . self::TARGET_INVOICE_NO . ' is not a legacy Monthly Fee invoice.'];
        }

        $setupAmount = bcadd((string) ($invoice->feeSetup?->amount ?? '0'), '0.00', 2);
        if (bccomp($setupAmount, self::STANDARD_GROSS_AMOUNT, 2) !== 0) {
            return ['state' => 'blocked', 'detail' => "Class One Monthly Fee setup amount is {$setupAmount} (expected 700.00)."];
        }

        $monthlyCatId = (int) ($invoice->feeSetup?->fee_category_id ?? 0);
        $monthlyCustomFee = $student->customFees->first(
            fn ($cf): bool => (int) $cf->fee_category_id === $monthlyCatId
        );
        if (!$monthlyCustomFee || bccomp(bcadd((string) $monthlyCustomFee->amount, '0.00', 2), self::TARGET_NET_AMOUNT, 2) !== 0) {
            $actualCf = $monthlyCustomFee ? bcadd((string) $monthlyCustomFee->amount, '0.00', 2) : 'NONE';

            return ['state' => 'blocked', 'detail' => "Student Monthly Fee custom rate is {$actualCf} (expected 200.00)."];
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

        // Check strict idempotency first (already normalized to 700 - 500 = 200 Paid with reversed 250 + active 200).
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
            && $reversedPayments->contains(
                fn (FeePayment $p): bool => bccomp(bcadd((string) $p->paid_amount, '0.00', 2), self::OLD_AMOUNT, 2) === 0
            )
        ) {
            return ['state' => 'already_reconciled', 'detail' => 'Already reconciled to 700.00 - 500.00 = ৳200.00 Paid'];
        }

        // Strict eligibility predicates for 250 -> 200 reconciliation.
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
                'detail' => "Invoice state mismatch (status={$status}, amt={$amount}, disc={$discount}, net={$net}, fine={$fine}, paid={$paid}, due={$due}; expected Paid 250.00).",
            ];
        }

        if ($activePayments->count() !== 1) {
            return [
                'state'  => 'blocked',
                'detail' => "Expected exactly 1 Active payment on INV-202603-0001, found {$activePayments->count()}.",
            ];
        }

        $activePaid = bcadd((string) $activePayments->first()->paid_amount, '0.00', 2);
        if (bccomp($activePaid, self::OLD_AMOUNT, 2) !== 0) {
            return [
                'state'  => 'blocked',
                'detail' => "Active payment amount is {$activePaid} (expected 250.00).",
            ];
        }

        return ['state' => 'eligible', 'detail' => 'Ready: ৳250.00 -> 700.00 - 500.00 = ৳200.00 (Net/Collected Delta: -৳50.00)'];
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
