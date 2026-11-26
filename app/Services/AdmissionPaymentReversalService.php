<?php

namespace App\Services;

use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\FeeAdjustmentLog;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Executes immutable, full-receipt payment reversals for both multi-head Admission Fee
 * invoices and legacy single-head Fee invoices under the V8 5-tier global lock hierarchy.
 */
class AdmissionPaymentReversalService
{
    public function __construct(
        protected FinancialTransactionRunner $transactionRunner,
        protected AdmissionCollectionService $collectionService
    ) {
    }

    /**
     * Fully reverse an active FeePayment receipt inside a bounded deadlock-retryable transaction.
     *
     * @param  array{student_id?: int|null, fee_invoice_id?: int|null}  $verificationHints
     * @return array{
     *     success: bool,
     *     is_admission_invoice: bool,
     *     reversal_reference: string,
     *     reversed_amount: string,
     *     payment: FeePayment,
     *     invoice: FeeInvoice,
     *     head_allocations: Collection<int, AdmissionInvoiceHeadAllocation>
     * }
     *
     * @throws AuthorizationException|ValidationException
     */
    public function reversePayment(
        int $paymentId,
        string $reason,
        int $actorUserId,
        ?string $idempotencyKey = null,
        array $verificationHints = []
    ): array {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->reversePaymentWithinTransaction(
                $paymentId,
                $reason,
                $actorUserId,
                $idempotencyKey,
                $verificationHints
            )
        );
    }

    /**
     * Execute full immutable payment reversal inside an already active outer transaction.
     *
     * @param  array{student_id?: int|null, fee_invoice_id?: int|null}  $verificationHints
     * @return array{
     *     success: bool,
     *     is_admission_invoice: bool,
     *     reversal_reference: string,
     *     reversed_amount: string,
     *     payment: FeePayment,
     *     invoice: FeeInvoice,
     *     head_allocations: Collection<int, AdmissionInvoiceHeadAllocation>
     * }
     *
     * @throws AuthorizationException|ValidationException
     */
    public function reversePaymentWithinTransaction(
        int $paymentId,
        string $reason,
        int $actorUserId,
        ?string $idempotencyKey = null,
        array $verificationHints = []
    ): array {
        $this->transactionRunner->assertInTransaction('AdmissionPaymentReversalService::reversePaymentWithinTransaction');

        $actor = $this->collectionService->resolveAndAuthorizeAdminActor($actorUserId);
        $this->assertReversalSchemaReady();

        $trimmedReason = trim($reason);
        if ($trimmedReason === '' || mb_strlen($trimmedReason) < 3 || mb_strlen($trimmedReason) > 255) {
            throw ValidationException::withMessages([
                'reason' => ['A clear reversal reason (between 3 and 255 characters) is required.'],
            ]);
        }

        // Lookup routing keys only — never mutate or trust external client IDs.
        $paymentLookup = FeePayment::select('id', 'fee_invoice_id', 'student_id', 'branch_id')->findOrFail($paymentId);

        // V8 5-Tier Lock Hierarchy:
        // Tier 1: Students -> Tier 2: Fee Invoices -> Tier 3: Fee Payments -> Tier 4: Invoice Heads -> Tier 5: Payment Heads
        $lockedStudent = Student::where('id', $paymentLookup->student_id)
            ->lockForUpdate()
            ->firstOrFail();

        $lockedInvoice = FeeInvoice::where('id', $paymentLookup->fee_invoice_id)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->firstOrFail();

        $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $targetPayment = $lockedPayments->firstWhere('id', $paymentId);
        if (!$targetPayment) {
            throw ValidationException::withMessages([
                'payment_id' => ['Target payment record could not be located inside the locked invoice scope.'],
            ]);
        }

        $isAdmissionInvoice = $lockedInvoice->isAdmissionInvoice();

        $lockedInvoiceHeads = collect();
        $lockedPaymentHeads = collect();

        if (Schema::hasTable('admission_invoice_head_allocations')) {
            $lockedInvoiceHeads = AdmissionInvoiceHeadAllocation::where('fee_invoice_id', $lockedInvoice->id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();
        }

        if (Schema::hasTable('admission_payment_head_allocations')) {
            $lockedPaymentHeads = AdmissionPaymentHeadAllocation::where('fee_invoice_id', $lockedInvoice->id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();
        }

        $this->assertLockedOwnershipIntegrity(
            $targetPayment,
            $lockedInvoice,
            $lockedStudent,
            $verificationHints
        );

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            $this->collectionService->claimDatabaseIdempotencyKey(
                'reverse_payment',
                $idempotencyKey,
                $actor->id,
                (string) $targetPayment->id
            );
        }

        if ($targetPayment->isReversed()) {
            throw ValidationException::withMessages([
                'payment_id' => [
                    "Payment receipt ({$targetPayment->receipt_no}) has already been reversed (Reference: {$targetPayment->reversal_reference}).",
                ],
            ]);
        }

        if (($targetPayment->status ?? 'Active') !== 'Active') {
            throw ValidationException::withMessages([
                'payment_id' => ["Only Active payments can be reversed (current status: '{$targetPayment->status}')."],
            ]);
        }

        if ($lockedInvoice->isCancelled()) {
            throw ValidationException::withMessages([
                'fee_invoice_id' => [
                    "Cannot reverse payment ({$targetPayment->receipt_no}) because invoice {$lockedInvoice->invoice_no} is currently Cancelled.",
                ],
            ]);
        }

        $reversedAmount = $this->collectionService->toDecimalString($targetPayment->paid_amount);
        if (bccomp($reversedAmount, '0.00', 2) <= 0) {
            throw new LogicException("Cannot reverse non-positive payment ID {$targetPayment->id}.");
        }

        $now = now();
        $reversalReference = $this->collectionService->allocateNextDatedSequence(
            'reversal_reference',
            'REV',
            $now->format('Ymd'),
            false
        );

        // Immutable transition on original FeePayment row (paid_amount stays untouched for historical ledger accuracy).
        $targetPayment->status = 'Reversed';
        $targetPayment->reversed_amount = $reversedAmount;
        $targetPayment->reversed_at = $now;
        $targetPayment->reversed_by = $actor->id;
        $targetPayment->reversal_reason = $trimmedReason;
        $targetPayment->reversal_reference = $reversalReference;
        $targetPayment->save();

        if ($isAdmissionInvoice) {
            $this->reverseAdmissionHeadAllocationsAndRecalculateHeads(
                $targetPayment,
                $lockedInvoice,
                $lockedStudent,
                $lockedInvoiceHeads,
                $lockedPaymentHeads,
                $reversedAmount,
                $now,
                $reversalReference
            );
        } else {
            $unexpectedAllocations = $lockedPaymentHeads->where('fee_payment_id', $targetPayment->id);
            if ($unexpectedAllocations->isNotEmpty() || $lockedInvoiceHeads->isNotEmpty()) {
                throw new LogicException(
                    "Ledger Integrity Violation: Legacy invoice {$lockedInvoice->invoice_no} unexpectedly contains Admission head allocation rows."
                );
            }
        }

        // Recalculate invoice balances strictly from remaining Active FeePayment rows.
        $remainingActivePaid = '0.00';
        foreach ($lockedPayments as $paymentRow) {
            if ((int) $paymentRow->id === (int) $targetPayment->id) {
                continue;
            }
            if (($paymentRow->status ?? 'Active') === 'Active') {
                $remainingActivePaid = bcadd(
                    $remainingActivePaid,
                    $this->collectionService->toDecimalString($paymentRow->paid_amount),
                    2
                );
            }
        }

        $invoiceTotalPayable = $isAdmissionInvoice
            ? $this->collectionService->toDecimalString($lockedInvoice->net_amount)
            : $lockedInvoice->totalPayableAmount();
        if (bccomp($remainingActivePaid, $invoiceTotalPayable, 2) > 0) {
            throw new LogicException(
                "Ledger Integrity Violation: Remaining active payments ({$remainingActivePaid}) exceed invoice total payable ({$invoiceTotalPayable})."
            );
        }

        $newDue = bcsub($invoiceTotalPayable, $remainingActivePaid, 2);
        $newStatus = bccomp($newDue, '0.00', 2) === 0
            ? 'Paid'
            : (bccomp($remainingActivePaid, '0.00', 2) > 0 ? 'Partial' : 'Unpaid');

        $prevDue = $this->collectionService->toDecimalString($lockedInvoice->due_amount);

        $lockedInvoice->paid_amount = $remainingActivePaid;
        $lockedInvoice->due_amount = $newDue;
        $lockedInvoice->status = $newStatus;
        $lockedInvoice->save();

        if ($isAdmissionInvoice) {
            $this->assertAdmissionHeadTotalsMatchInvoice($lockedInvoiceHeads, $remainingActivePaid, $newDue);
        }

        $logReason = mb_substr(
            "Payment Reversal ({$reversalReference} | {$targetPayment->receipt_no}): {$trimmedReason}",
            0,
            255
        );

        FeeAdjustmentLog::create([
            'fee_invoice_id' => $lockedInvoice->id,
            'student_id' => $lockedStudent->id,
            'user_id' => $actor->id,
            'invoice_no' => $lockedInvoice->invoice_no,
            'student_identity' => $lockedStudent->student_identity,
            'mode' => 'set_amount',
            'scope' => 'single',
            'adjustment_amount' => $reversedAmount,
            'previous_amount' => $lockedInvoice->amount,
            'previous_discount' => $lockedInvoice->discount,
            'previous_net_amount' => $lockedInvoice->net_amount,
            'previous_due_amount' => $prevDue,
            'new_amount' => $lockedInvoice->amount,
            'new_discount' => $lockedInvoice->discount,
            'new_net_amount' => $lockedInvoice->net_amount,
            'new_due_amount' => $newDue,
            'paid_amount' => $remainingActivePaid,
            'reason' => $logReason,
        ]);

        $freshInvoice = $isAdmissionInvoice
            ? $lockedInvoice->fresh(['admissionFeeStructure', 'admissionHeadAllocations', 'activePayments'])
            : $lockedInvoice->fresh(['activePayments']);

        return [
            'success' => true,
            'is_admission_invoice' => $isAdmissionInvoice,
            'reversal_reference' => $reversalReference,
            'reversed_amount' => $reversedAmount,
            'payment' => $targetPayment->fresh(['admissionHeadAllocations']),
            'invoice' => $freshInvoice,
            'head_allocations' => $isAdmissionInvoice ? $freshInvoice->admissionHeadAllocations : collect(),
        ];
    }

    /**
     * @throws ValidationException
     */
    private function assertReversalSchemaReady(): void
    {
        if (!Schema::hasColumns('fee_payments', [
            'status',
            'reversed_amount',
            'reversed_at',
            'reversed_by',
            'reversal_reason',
            'reversal_reference',
        ])) {
            throw ValidationException::withMessages([
                'payment_id' => ['Payment reversal schema columns are not migrated yet.'],
            ]);
        }
    }

    /**
     * Verify locked payment -> invoice -> student -> branch linkage before any write.
     *
     * @param  array{student_id?: int|null, fee_invoice_id?: int|null}  $verificationHints
     * @throws ValidationException|LogicException
     */
    private function assertLockedOwnershipIntegrity(
        FeePayment $payment,
        FeeInvoice $invoice,
        Student $student,
        array $verificationHints
    ): void {
        if ((int) $payment->fee_invoice_id !== (int) $invoice->id
            || (int) $payment->student_id !== (int) $student->id
            || (int) $invoice->student_id !== (int) $student->id
        ) {
            throw new LogicException(
                "Cross-entity relationship mismatch detected for payment ID {$payment->id}, invoice ID {$invoice->id}, student ID {$student->id}."
            );
        }

        if (!empty($payment->branch_id) && !empty($invoice->branch_id) && (int) $payment->branch_id !== (int) $invoice->branch_id) {
            throw new LogicException(
                "Branch integrity mismatch between payment ID {$payment->id} ({$payment->branch_id}) and invoice ID {$invoice->id} ({$invoice->branch_id})."
            );
        }

        if (isset($verificationHints['student_id']) && $verificationHints['student_id'] !== null) {
            if ((int) $verificationHints['student_id'] !== (int) $student->id) {
                throw ValidationException::withMessages([
                    'student_id' => ['Payment does not belong to the specified student.'],
                ]);
            }
        }

        if (isset($verificationHints['fee_invoice_id']) && $verificationHints['fee_invoice_id'] !== null) {
            if ((int) $verificationHints['fee_invoice_id'] !== (int) $invoice->id) {
                throw ValidationException::withMessages([
                    'fee_invoice_id' => ['Payment does not belong to the specified invoice.'],
                ]);
            }
        }
    }

    /**
     * Mark AdmissionPaymentHeadAllocation children as Reversed and recalculate head-level
     * paid/due amounts strictly from remaining Active payment head allocations.
     *
     * @param  Collection<int, AdmissionInvoiceHeadAllocation>  $lockedInvoiceHeads
     * @param  Collection<int, AdmissionPaymentHeadAllocation>  $lockedPaymentHeads
     */
    private function reverseAdmissionHeadAllocationsAndRecalculateHeads(
        FeePayment $targetPayment,
        FeeInvoice $lockedInvoice,
        Student $lockedStudent,
        Collection $lockedInvoiceHeads,
        Collection $lockedPaymentHeads,
        string $expectedReversedAmount,
        \Carbon\CarbonInterface $now,
        string $reversalReference
    ): void {
        if ($lockedInvoiceHeads->isEmpty()) {
            throw new LogicException("Admission invoice {$lockedInvoice->invoice_no} is missing invoice head allocations.");
        }

        $headsById = $lockedInvoiceHeads->keyBy('id');
        $targetAllocations = $lockedPaymentHeads
            ->where('fee_payment_id', $targetPayment->id)
            ->values();

        if ($targetAllocations->isEmpty()) {
            throw new LogicException(
                "Admission payment receipt ({$targetPayment->receipt_no}) has no AdmissionPaymentHeadAllocation rows to reverse."
            );
        }

        $targetSum = '0.00';
        foreach ($targetAllocations as $alloc) {
            if (($alloc->status ?? 'Active') !== 'Active') {
                throw new LogicException("Child payment head allocation ID {$alloc->id} is not Active.");
            }

            if ((int) $alloc->fee_invoice_id !== (int) $lockedInvoice->id
                || (int) $alloc->student_id !== (int) $lockedStudent->id
                || (int) $alloc->branch_id !== (int) $lockedInvoice->branch_id
                || !$headsById->has((int) $alloc->admission_invoice_head_allocation_id)
            ) {
                throw new LogicException("Child payment head allocation ID {$alloc->id} violates invoice/student/branch linkage.");
            }

            $targetSum = bcadd(
                $targetSum,
                $this->collectionService->toDecimalString($alloc->allocated_paid_amount),
                2
            );

            $alloc->status = 'Reversed';
            $alloc->reversed_at = $now;
            $alloc->reversal_reference = $reversalReference;
            $alloc->save();
        }

        if (bccomp($targetSum, $expectedReversedAmount, 2) !== 0) {
            throw new LogicException(
                "Payment head allocation sum ({$targetSum}) does not match FeePayment paid_amount ({$expectedReversedAmount})."
            );
        }

        $remainingActiveByHeadId = [];
        foreach ($lockedPaymentHeads as $alloc) {
            if ((int) $alloc->fee_payment_id === (int) $targetPayment->id) {
                continue;
            }
            if (($alloc->status ?? 'Active') !== 'Active') {
                continue;
            }
            $headId = (int) $alloc->admission_invoice_head_allocation_id;
            $remainingActiveByHeadId[$headId] = bcadd(
                $remainingActiveByHeadId[$headId] ?? '0.00',
                $this->collectionService->toDecimalString($alloc->allocated_paid_amount),
                2
            );
        }

        foreach ($lockedInvoiceHeads as $head) {
            $headNet = $this->collectionService->toDecimalString($head->net_amount);
            $newHeadPaid = $remainingActiveByHeadId[(int) $head->id] ?? '0.00';

            if (bccomp($newHeadPaid, $headNet, 2) > 0) {
                throw new LogicException(
                    "Invariant Violation: Remaining active paid ({$newHeadPaid}) exceeds head net ({$headNet}) on head ID {$head->id}."
                );
            }

            $head->allocated_paid_amount = $newHeadPaid;
            $head->due_amount = bcsub($headNet, $newHeadPaid, 2);
            $head->save();
        }
    }

    /**
     * Verify head sums agree exactly with recalculated invoice paid and due balances.
     *
     * @param  Collection<int, AdmissionInvoiceHeadAllocation>  $lockedInvoiceHeads
     */
    private function assertAdmissionHeadTotalsMatchInvoice(
        Collection $lockedInvoiceHeads,
        string $expectedInvoicePaid,
        string $expectedInvoiceDue
    ): void {
        $sumPaid = '0.00';
        $sumDue = '0.00';

        foreach ($lockedInvoiceHeads as $head) {
            $sumPaid = bcadd($sumPaid, $this->collectionService->toDecimalString($head->allocated_paid_amount), 2);
            $sumDue = bcadd($sumDue, $this->collectionService->toDecimalString($head->due_amount), 2);
        }

        if (bccomp($sumPaid, $expectedInvoicePaid, 2) !== 0) {
            throw new LogicException("Post-reversal head paid sum ({$sumPaid}) != invoice paid_amount ({$expectedInvoicePaid}).");
        }

        if (bccomp($sumDue, $expectedInvoiceDue, 2) !== 0) {
            throw new LogicException("Post-reversal head due sum ({$sumDue}) != invoice due_amount ({$expectedInvoiceDue}).");
        }
    }
}
