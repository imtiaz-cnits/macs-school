<?php

namespace App\Services;

use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\FeeAdjustmentLog;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

/**
 * Coordinates atomic single and bulk fee payment collection under the V8 5-tier global
 * lock hierarchy using pure BCMath accounting and database-backed idempotency.
 */
class AdmissionCollectionService
{
    public function __construct(
        protected FinancialTransactionRunner $transactionRunner
    ) {
    }

    /**
     * Collect a payment for a multi-head Admission Invoice inside its own retryable outer transaction.
     *
     * @param  array<string, mixed>  $payload  Keys: `pay_amount`, `head_allocations`, `payment_method`, `payment_date`, `transaction_id`, `note`, `idempotency_key`, `receipt_no`
     * @return array{
     *     success: bool,
     *     receipt_no: string,
     *     paid_amount: string,
     *     payment: FeePayment,
     *     invoice: FeeInvoice,
     *     head_allocations: Collection<int, AdmissionInvoiceHeadAllocation>,
     *     payment_allocations: Collection<int, AdmissionPaymentHeadAllocation>
     * }
     *
     * @throws AuthorizationException|ValidationException
     */
    public function collectSinglePayment(int $invoiceId, array $payload, int $actorUserId): array
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->collectSinglePaymentWithinTransaction($invoiceId, $payload, $actorUserId)
        );
    }

    /**
     * Execute single Admission Invoice payment collection inside an active outer transaction.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $preLockedContext
     * @return array{
     *     success: bool,
     *     receipt_no: string,
     *     paid_amount: string,
     *     payment: FeePayment,
     *     invoice: FeeInvoice,
     *     head_allocations: Collection<int, AdmissionInvoiceHeadAllocation>,
     *     payment_allocations: Collection<int, AdmissionPaymentHeadAllocation>
     * }
     *
     * @throws AuthorizationException|ValidationException
     */
    public function collectSinglePaymentWithinTransaction(
        int $invoiceId,
        array $payload,
        int $actorUserId,
        ?array $preLockedContext = null
    ): array {
        $this->transactionRunner->assertInTransaction('AdmissionCollectionService::collectSinglePaymentWithinTransaction');
        $actor = $this->resolveAndAuthorizeAdminActor($actorUserId);
        $this->assertAdmissionPaymentSchemaReady();

        $directDiscountAmount = (array_key_exists('direct_discount_amount', $payload) && $payload['direct_discount_amount'] !== null && $payload['direct_discount_amount'] !== '')
            ? $this->validateNonNegativeDecimal($payload['direct_discount_amount'], 'direct_discount_amount')
            : '0.00';
        $hasDirectDiscount = bccomp($directDiscountAmount, '0.00', 2) > 0;
        $hasCashPayment = $this->payloadHasPositiveCashIntent($payload);

        $paymentMethod = trim((string) ($payload['payment_method'] ?? ''));
        if ($hasCashPayment || !$hasDirectDiscount) {
            if ($paymentMethod === '' || mb_strlen($paymentMethod) > 100) {
                throw ValidationException::withMessages([
                    'payment_method' => ['Payment method is required and must not exceed 100 characters.'],
                ]);
            }
        } elseif ($paymentMethod !== '' && mb_strlen($paymentMethod) > 100) {
            throw ValidationException::withMessages([
                'payment_method' => ['Payment method must not exceed 100 characters.'],
            ]);
        }

        $paymentDate = $this->normalizePaymentDate($payload['payment_date'] ?? null);

        if ($preLockedContext !== null) {
            /** @var FeeInvoice $lockedInvoice */
            $lockedInvoice = $preLockedContext['invoices']->get($invoiceId)
                ?? throw ValidationException::withMessages(['invoice_id' => ['Target invoice could not be locked.']]);
            /** @var Student $lockedStudent */
            $lockedStudent = $preLockedContext['students']->get((int) $lockedInvoice->student_id)
                ?? throw ValidationException::withMessages(['student_id' => ['Associated student record could not be locked.']]);
            /** @var Collection<int, FeePayment> $lockedPayments */
            $lockedPayments = $preLockedContext['payments_by_invoice']->get($invoiceId, collect());
            /** @var Collection<int, AdmissionInvoiceHeadAllocation> $lockedInvoiceHeads */
            $lockedInvoiceHeads = $preLockedContext['invoice_heads_by_invoice']->get($invoiceId, collect());
            /** @var Collection<int, AdmissionPaymentHeadAllocation> $lockedPaymentHeads */
            $lockedPaymentHeads = $preLockedContext['payment_heads_by_invoice']->get($invoiceId, collect());
        } else {
            $invoiceLookup = FeeInvoice::select('id', 'student_id', 'branch_id')->findOrFail($invoiceId);

            // V8 5-Tier Lock Order: Tier 1 (Student) -> Tier 2 (Invoice) -> Tier 3 (Payments) -> Tier 4 (Invoice Heads) -> Tier 5 (Payment Heads)
            $lockedStudent = Student::where('id', $invoiceLookup->student_id)->lockForUpdate()->firstOrFail();
            $lockedInvoice = FeeInvoice::where('id', $invoiceId)->orderBy('id', 'asc')->lockForUpdate()->firstOrFail();

            if ((int) $lockedInvoice->student_id !== (int) $lockedStudent->id) {
                throw new LogicException("Student mismatch while locking invoice {$lockedInvoice->invoice_no}.");
            }

            $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $lockedInvoiceHeads = AdmissionInvoiceHeadAllocation::where('fee_invoice_id', $lockedInvoice->id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $lockedPaymentHeads = AdmissionPaymentHeadAllocation::where('fee_invoice_id', $lockedInvoice->id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();
        }

        if (!empty($payload['idempotency_key'])) {
            $this->claimDatabaseIdempotencyKey(
                'collect_single',
                (string) $payload['idempotency_key'],
                $actor->id,
                (string) $lockedInvoice->id
            );
        }

        $this->assertInvoicePayableForAdmission($lockedInvoice, $lockedStudent);

        if ($lockedInvoiceHeads->isEmpty()) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Admission invoice ({$lockedInvoice->invoice_no}) has no head allocation snapshots."],
            ]);
        }

        [$verifiedActivePaid, $verifiedDue] = $this->assertAdmissionLedgerConsistencyBeforePayment(
            $lockedInvoice,
            $lockedStudent,
            $lockedPayments,
            $lockedInvoiceHeads,
            $lockedPaymentHeads
        );

        $orderedHeads = $lockedInvoiceHeads->sort(function (AdmissionInvoiceHeadAllocation $a, AdmissionInvoiceHeadAllocation $b): int {
            $sortCmp = ((int) $a->sort_order) <=> ((int) $b->sort_order);
            return $sortCmp !== 0 ? $sortCmp : ((int) $a->id) <=> ((int) $b->id);
        })->values();

        if ($hasDirectDiscount) {
            $this->applyDirectCollectionDiscount(
                $lockedInvoice,
                $lockedStudent,
                $orderedHeads,
                $directDiscountAmount,
                $verifiedActivePaid,
                $verifiedDue,
                $actor,
                isset($payload['discount_reason']) ? (string) $payload['discount_reason'] : ($payload['note'] ?? null)
            );
            $verifiedDue = $this->toDecimalString($lockedInvoice->due_amount);
        }

        if ($hasDirectDiscount && !$hasCashPayment) {
            $this->assertPostDiscountInvariants($lockedInvoice, $orderedHeads, $verifiedActivePaid);
            $freshInvoice = $lockedInvoice->fresh(['admissionFeeStructure', 'admissionHeadAllocations', 'activePayments']);

            return [
                'success' => true,
                'discount_applied' => $directDiscountAmount,
                'receipt_no' => null,
                'paid_amount' => '0.00',
                'payment' => null,
                'invoice' => $freshInvoice,
                'head_allocations' => $freshInvoice->admissionHeadAllocations,
                'payment_allocations' => collect(),
            ];
        }

        [$totalPayAmount, $perHeadPaymentMap] = $this->resolveHeadPaymentBreakdown(
            $orderedHeads,
            $verifiedDue,
            $payload
        );

        $receiptNo = !empty($payload['receipt_no'])
            ? trim((string) $payload['receipt_no'])
            : $this->allocateNextDatedSequence('receipt_no', 'REC', Carbon::parse($paymentDate)->format('Ymd'), true);

        $payment = FeePayment::create([
            'receipt_no' => $receiptNo,
            'fee_invoice_id' => $lockedInvoice->id,
            'student_id' => $lockedStudent->id,
            'branch_id' => $lockedInvoice->branch_id,
            'paid_amount' => $totalPayAmount,
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod,
            'transaction_id' => isset($payload['transaction_id']) ? trim((string) $payload['transaction_id']) ?: null : null,
            'note' => isset($payload['note']) ? trim((string) $payload['note']) ?: null : null,
            'collected_by' => $actor->id,
        ]);

        $createdPaymentHeads = collect();
        $allocatedSum = '0.00';

        foreach ($orderedHeads as $head) {
            $headPay = $perHeadPaymentMap[(int) $head->id] ?? '0.00';
            if (bccomp($headPay, '0.00', 2) <= 0) {
                continue;
            }

            $paymentHead = AdmissionPaymentHeadAllocation::create([
                'fee_payment_id' => $payment->id,
                'fee_invoice_id' => $lockedInvoice->id,
                'student_id' => $lockedStudent->id,
                'branch_id' => $lockedInvoice->branch_id,
                'admission_invoice_head_allocation_id' => $head->id,
                'fee_category_id' => $head->fee_category_id,
                'fee_category_name_snapshot' => $head->fee_category_name_snapshot,
                'head_name_snapshot' => $head->head_name_snapshot,
                'allocated_paid_amount' => $headPay,
                'status' => 'Active',
            ]);
            $createdPaymentHeads->push($paymentHead);

            $newHeadPaid = bcadd($this->toDecimalString($head->allocated_paid_amount), $headPay, 2);
            $headNet = $this->toDecimalString($head->net_amount);
            $newHeadDue = bcsub($headNet, $newHeadPaid, 2);

            if (bccomp($newHeadDue, '0.00', 2) < 0) {
                throw new LogicException("Invariant Violation: Head ID {$head->id} became overpaid.");
            }

            $head->allocated_paid_amount = $newHeadPaid;
            $head->due_amount = $newHeadDue;
            $head->save();

            $allocatedSum = bcadd($allocatedSum, $headPay, 2);
        }

        if (bccomp($allocatedSum, $totalPayAmount, 2) !== 0) {
            throw new LogicException(
                "Invariant Violation: Payment allocation sum ({$allocatedSum}) does not equal payment amount ({$totalPayAmount})."
            );
        }

        $invoiceNet = $this->toDecimalString($lockedInvoice->net_amount);
        $newInvoicePaid = bcadd($verifiedActivePaid, $totalPayAmount, 2);
        $newInvoiceDue = bcsub($invoiceNet, $newInvoicePaid, 2);

        if (bccomp($newInvoiceDue, '0.00', 2) < 0) {
            throw new LogicException("Invariant Violation: Invoice {$lockedInvoice->invoice_no} total paid exceeded net amount.");
        }

        $lockedInvoice->paid_amount = $newInvoicePaid;
        $lockedInvoice->due_amount = $newInvoiceDue;
        $lockedInvoice->status = bccomp($newInvoiceDue, '0.00', 2) === 0 ? 'Paid' : 'Partial';
        $lockedInvoice->save();

        $this->assertPostPaymentInvariants($lockedInvoice, $orderedHeads, $createdPaymentHeads, $totalPayAmount);

        $freshInvoice = $lockedInvoice->fresh(['admissionFeeStructure', 'admissionHeadAllocations', 'activePayments']);

        return [
            'success' => true,
            'discount_applied' => $directDiscountAmount,
            'receipt_no' => $payment->receipt_no,
            'paid_amount' => $totalPayAmount,
            'payment' => $payment->fresh(['admissionHeadAllocations']),
            'invoice' => $freshInvoice,
            'head_allocations' => $freshInvoice->admissionHeadAllocations,
            'payment_allocations' => $createdPaymentHeads,
        ];
    }

    /**
     * Collect payments for a list of invoices (Admission, Legacy, or Mixed) inside a deterministic
     * V8 5-tier locked outer transaction.
     *
     * @param  array<int, array{invoice_id: int, pay_amount?: ?string, pay_full?: bool, head_allocations?: array<int|string, mixed>}>  $items
     * @param  array{receipt_prefix?: string, payment_method: string, payment_date?: ?string, transaction_id?: ?string, note?: ?string, idempotency_key?: ?string, allow_skip_unpayable?: bool, master_receipt_no?: ?string}  $meta
     * @return array{
     *     success: bool,
     *     master_receipt_no: string,
     *     total_collected: string,
     *     collected_count: int,
     *     primary_student_id: int,
     *     payments: Collection<int, FeePayment>,
     *     invoices: Collection<int, FeeInvoice>
     * }
     *
     * @throws AuthorizationException|ValidationException
     */
    public function collectBulkPayments(array $items, array $meta, int $actorUserId): array
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->collectBulkPaymentsWithinTransaction($items, $meta, $actorUserId)
        );
    }

    /**
     * Execute bulk (single-student or multi-student, mixed Admission + Legacy) payment collection
     * inside an already-open outer transaction following strict sorted ASC 5-tier lock hierarchy.
     *
     * @param  array<int, array{invoice_id: int, pay_amount?: ?string, pay_full?: bool, head_allocations?: array<int|string, mixed>}>  $items
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     *
     * @throws AuthorizationException|ValidationException
     */
    public function collectBulkPaymentsWithinTransaction(array $items, array $meta, int $actorUserId): array
    {
        $this->transactionRunner->assertInTransaction('AdmissionCollectionService::collectBulkPaymentsWithinTransaction');
        $actor = $this->resolveAndAuthorizeAdminActor($actorUserId);

        if (empty($items)) {
            throw ValidationException::withMessages([
                'invoice_ids' => ['At least one invoice must be selected for bulk collection.'],
            ]);
        }

        $paymentMethod = trim((string) ($meta['payment_method'] ?? ''));
        if ($paymentMethod === '' || mb_strlen($paymentMethod) > 100) {
            throw ValidationException::withMessages([
                'payment_method' => ['Payment method is required and must not exceed 100 characters.'],
            ]);
        }

        $paymentDate = $this->normalizePaymentDate($meta['payment_date'] ?? null);
        $receiptPrefix = trim((string) ($meta['receipt_prefix'] ?? 'REC'));
        $allowSkipUnpayable = (bool) ($meta['allow_skip_unpayable'] ?? false);

        $itemsByInvoiceId = [];
        foreach ($items as $entry) {
            $invId = (int) ($entry['invoice_id'] ?? 0);
            if ($invId <= 0) {
                throw ValidationException::withMessages([
                    'invoice_ids' => ['Invalid invoice identifier provided in bulk payment list.'],
                ]);
            }
            if (isset($itemsByInvoiceId[$invId])) {
                throw ValidationException::withMessages([
                    'invoice_ids' => ["Duplicate invoice ID {$invId} detected in bulk payment request."],
                ]);
            }
            $itemsByInvoiceId[$invId] = $entry;
        }

        ksort($itemsByInvoiceId, SORT_NUMERIC);
        $sortedInvoiceIds = array_keys($itemsByInvoiceId);

        $lockContext = $this->acquireGlobalLocksForInvoices($sortedInvoiceIds);

        if (!empty($meta['idempotency_key'])) {
            $this->claimDatabaseIdempotencyKey(
                'collect_bulk',
                (string) $meta['idempotency_key'],
                $actor->id,
                implode(',', $sortedInvoiceIds)
            );
        }

        $masterReceiptNo = !empty($meta['master_receipt_no'])
            ? trim((string) $meta['master_receipt_no'])
            : $this->allocateNextDatedSequence('receipt_no', $receiptPrefix, Carbon::parse($paymentDate)->format('Ymd'), true);

        $collectedPayments = collect();
        $updatedInvoices = collect();
        $totalCollected = '0.00';
        $primaryStudentId = null;

        foreach ($sortedInvoiceIds as $invId) {
            /** @var FeeInvoice $lockedInvoice */
            $lockedInvoice = $lockContext['invoices']->get($invId)
                ?? throw ValidationException::withMessages([
                    'invoice_ids' => ["Invoice ID {$invId} could not be found."],
                ]);
            /** @var Student $lockedStudent */
            $lockedStudent = $lockContext['students']->get((int) $lockedInvoice->student_id)
                ?? throw ValidationException::withMessages([
                    'invoice_ids' => ["Student record for invoice {$lockedInvoice->invoice_no} could not be found."],
                ]);

            $entry = $itemsByInvoiceId[$invId];
            $payFull = (bool) ($entry['pay_full'] ?? false);

            if ($lockedInvoice->isCancelled() || $lockedInvoice->isZeroPayableWaived() || bccomp($this->toDecimalString($lockedInvoice->due_amount), '0.00', 2) <= 0) {
                if ($allowSkipUnpayable) {
                    continue;
                }

                throw ValidationException::withMessages([
                    'invoice_ids' => [
                        $lockedInvoice->isCancelled()
                            ? "Cannot collect payment for a cancelled invoice ({$lockedInvoice->invoice_no})."
                            : "Invoice ({$lockedInvoice->invoice_no}) has no payable balance due.",
                    ],
                ]);
            }

            /** @var Collection<int, FeePayment> $invPayments */
            $invPayments = $lockContext['payments_by_invoice']->get($invId, collect());
            $currentActivePaid = $this->sumActivePayments($invPayments);
            $invoiceTotalPayable = $lockedInvoice->isAdmissionInvoice()
                ? $this->toDecimalString($lockedInvoice->net_amount)
                : $lockedInvoice->totalPayableAmount();
            $currentDue = bcsub($invoiceTotalPayable, $currentActivePaid, 2);

            if (bccomp($currentDue, '0.00', 2) <= 0) {
                if ($allowSkipUnpayable) {
                    continue;
                }

                throw ValidationException::withMessages([
                    'invoice_ids' => ["Invoice ({$lockedInvoice->invoice_no}) has already been settled."],
                ]);
            }

            $childReceiptNo = $masterReceiptNo . '-' . $lockedInvoice->id;

            if ($lockedInvoice->isAdmissionInvoice()) {
                $childPayload = [
                    'payment_method' => $paymentMethod,
                    'payment_date' => $paymentDate,
                    'transaction_id' => $meta['transaction_id'] ?? null,
                    'note' => $meta['note'] ?? null,
                    'receipt_no' => $childReceiptNo,
                ];

                if (!empty($entry['head_allocations'])) {
                    $childPayload['head_allocations'] = $entry['head_allocations'];
                    if (array_key_exists('pay_amount', $entry) && $entry['pay_amount'] !== null) {
                        $childPayload['pay_amount'] = $entry['pay_amount'];
                    }
                } else {
                    $childPayload['pay_amount'] = $payFull ? $currentDue : ($entry['pay_amount'] ?? $currentDue);
                }

                $result = $this->collectSinglePaymentWithinTransaction($invId, $childPayload, $actor->id, $lockContext);
                $payment = $result['payment'];
                $savedInvoice = $result['invoice'];
                $paidAmount = $result['paid_amount'];
            } else {
                $rawAmount = $payFull ? $currentDue : ($entry['pay_amount'] ?? null);
                $paidAmount = $this->validatePositivePaymentAmount($rawAmount, "paying_amounts.{$invId}");

                if (bccomp($paidAmount, $currentDue, 2) > 0) {
                    throw ValidationException::withMessages([
                        'pay_amount' => [
                            "Paying amount (৳{$paidAmount}) for invoice {$lockedInvoice->invoice_no} cannot exceed remaining due (৳{$currentDue}).",
                        ],
                    ]);
                }

                $invoiceNet = $this->toDecimalString($lockedInvoice->net_amount);
                $invoiceFine = $lockedInvoice->fineAmountDecimal();
                $finePaidNow = FeePayment::computeFinePortionForPayment(
                    $invoiceNet,
                    $invoiceFine,
                    $currentActivePaid,
                    $paidAmount
                );

                $paymentAttrs = [
                    'receipt_no' => $childReceiptNo,
                    'fee_invoice_id' => $lockedInvoice->id,
                    'student_id' => $lockedStudent->id,
                    'branch_id' => $lockedInvoice->branch_id,
                    'paid_amount' => $paidAmount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $paymentMethod,
                    'transaction_id' => isset($meta['transaction_id']) ? trim((string) $meta['transaction_id']) ?: null : null,
                    'note' => isset($meta['note']) ? trim((string) $meta['note']) ?: null : null,
                    'collected_by' => $actor->id,
                ];
                if (Schema::hasColumn('fee_payments', 'fine_amount')) {
                    $paymentAttrs['fine_amount'] = $finePaidNow;
                }

                $payment = FeePayment::create($paymentAttrs);

                $newPaid = bcadd($currentActivePaid, $paidAmount, 2);
                $newDue = bcsub($invoiceTotalPayable, $newPaid, 2);

                $lockedInvoice->paid_amount = $newPaid;
                $lockedInvoice->due_amount = $newDue;
                $lockedInvoice->status = bccomp($newDue, '0.00', 2) === 0 ? 'Paid' : 'Partial';
                $lockedInvoice->save();

                $savedInvoice = $lockedInvoice->fresh();
            }

            if ($primaryStudentId === null) {
                $primaryStudentId = (int) $lockedStudent->id;
            }

            $totalCollected = bcadd($totalCollected, $paidAmount, 2);
            $collectedPayments->push($payment);
            $updatedInvoices->push($savedInvoice);
        }

        if ($collectedPayments->isEmpty() || bccomp($totalCollected, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'invoice_ids' => ['No payable invoices with remaining balance due were selected for collection.'],
            ]);
        }

        return [
            'success' => true,
            'master_receipt_no' => $masterReceiptNo,
            'total_collected' => $totalCollected,
            'collected_count' => $collectedPayments->count(),
            'primary_student_id' => $primaryStudentId,
            'payments' => $collectedPayments,
            'invoices' => $updatedInvoices,
        ];
    }

    /**
     * Acquire V8 5-tier exclusive locks across an arbitrary set of invoices in strict ASC order.
     *
     * @param  array<int, int>  $sortedInvoiceIds
     * @return array{
     *     students: Collection<int, Student>,
     *     invoices: Collection<int, FeeInvoice>,
     *     payments_by_invoice: Collection<int, Collection<int, FeePayment>>,
     *     invoice_heads_by_invoice: Collection<int, Collection<int, AdmissionInvoiceHeadAllocation>>,
     *     payment_heads_by_invoice: Collection<int, Collection<int, AdmissionPaymentHeadAllocation>>
     * }
     *
     * @throws ValidationException
     */
    public function acquireGlobalLocksForInvoices(array $sortedInvoiceIds): array
    {
        $sortedIds = collect($sortedInvoiceIds)->map(static fn ($id): int => (int) $id)->unique()->sort()->values()->all();

        $studentIds = FeeInvoice::whereIn('id', $sortedIds)
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($studentIds)) {
            throw ValidationException::withMessages([
                'invoice_ids' => ['Selected invoice records could not be found.'],
            ]);
        }

        // Tier 1: Students ASC
        $lockedStudents = Student::whereIn('id', $studentIds)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Tier 2: Fee Invoices ASC
        $lockedInvoices = FeeInvoice::whereIn('id', $sortedIds)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Re-verify any dynamically needed student IDs obey strict order
        foreach ($lockedInvoices as $inv) {
            if (!$lockedStudents->has((int) $inv->student_id)) {
                throw new LogicException("Lock ordering consistency check failed: Student {$inv->student_id} not pre-locked.");
            }
        }

        // Tier 3: Fee Payments ASC
        $lockedPayments = FeePayment::whereIn('fee_invoice_id', $sortedIds)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get()
            ->groupBy('fee_invoice_id');

        $admissionInvoiceIds = $lockedInvoices
            ->filter(static fn (FeeInvoice $inv): bool => $inv->isAdmissionInvoice())
            ->keys()
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $lockedInvoiceHeads = collect();
        $lockedPaymentHeads = collect();

        if (!empty($admissionInvoiceIds)) {
            if (Schema::hasTable('admission_invoice_head_allocations')) {
                // Tier 4: Admission Invoice Head Allocations ASC
                $lockedInvoiceHeads = AdmissionInvoiceHeadAllocation::whereIn('fee_invoice_id', $admissionInvoiceIds)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get()
                    ->groupBy('fee_invoice_id');
            }

            if (Schema::hasTable('admission_payment_head_allocations')) {
                // Tier 5: Admission Payment Head Allocations ASC
                $lockedPaymentHeads = AdmissionPaymentHeadAllocation::whereIn('fee_invoice_id', $admissionInvoiceIds)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get()
                    ->groupBy('fee_invoice_id');
            }
        }

        return [
            'students' => $lockedStudents,
            'invoices' => $lockedInvoices,
            'payments_by_invoice' => $lockedPayments,
            'invoice_heads_by_invoice' => $lockedInvoiceHeads,
            'payment_heads_by_invoice' => $lockedPaymentHeads,
        ];
    }

    /**
     * Claim a unique database-enforced idempotency token inside the dedicated `fee_idempotency_keys` table
     * within the active SQL transaction. Never relies on shared Laravel `cache` storage, so cache flushes
     * cannot delete financial idempotency records, while rollbacks/deadlock retries clean up uncommitted claims.
     *
     * @throws ValidationException
     */
    public function claimDatabaseIdempotencyKey(
        string $operation,
        string $rawKey,
        int $actorUserId,
        string $scopeTarget
    ): void {
        $trimmed = trim($rawKey);
        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => ['Idempotency key cannot be blank when provided.'],
            ]);
        }

        if (mb_strlen($trimmed) > 120) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['Idempotency key must not exceed 120 characters.'],
            ]);
        }

        if (!Schema::hasTable('fee_idempotency_keys')) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['Dedicated financial idempotency table (fee_idempotency_keys) is not migrated yet.'],
            ]);
        }

        $scopedKey = hash('sha256', $operation . '|' . $actorUserId . '|' . $scopeTarget . '|' . $trimmed);
        $now = now();
        $nowStr = $now->toDateTimeString();
        $expiresAtStr = $now->copy()->addHours(24)->toDateTimeString();
        $boundedTarget = mb_strlen($scopeTarget) > 255
            ? mb_substr($scopeTarget, 0, 200) . '#' . sha1($scopeTarget)
            : $scopeTarget;

        $existing = DB::table('fee_idempotency_keys')
            ->where('scoped_key', $scopedKey)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            if (Carbon::parse((string) $existing->expires_at)->gt($now)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => ['Duplicate submission prevented: This payment request has already been processed.'],
                ]);
            }

            // Refresh expired claim in place while continuously holding the X-lock (zero delete/insert gap).
            DB::table('fee_idempotency_keys')
                ->where('id', $existing->id)
                ->update([
                    'operation' => $operation,
                    'actor_user_id' => $actorUserId,
                    'target_scope' => $boundedTarget,
                    'idempotency_key' => $trimmed,
                    'metadata_json' => json_encode([
                        'operation' => $operation,
                        'actor_id' => $actorUserId,
                        'target' => $boundedTarget,
                        'reclaimed_at' => $nowStr,
                    ]),
                    'expires_at' => $expiresAtStr,
                    'updated_at' => $nowStr,
                ]);

            return;
        }

        try {
            DB::table('fee_idempotency_keys')->insert([
                'scoped_key' => $scopedKey,
                'operation' => $operation,
                'actor_user_id' => $actorUserId,
                'target_scope' => $boundedTarget,
                'idempotency_key' => $trimmed,
                'metadata_json' => json_encode([
                    'operation' => $operation,
                    'actor_id' => $actorUserId,
                    'target' => $boundedTarget,
                    'claimed_at' => $nowStr,
                ]),
                'expires_at' => $expiresAtStr,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => ['Duplicate submission prevented: Concurrent identical request already committed or in progress.'],
                ]);
            }

            throw $e;
        }
    }

    /**
     * Allocate a unique sequential dated code (`REC-YYYYMMDD-XXXX`, `REC-BULK-YYYYMMDD-XXXX`,
     * or `REV-YYYYMMDD-XXXX`) from the dedicated `fee_daily_sequences` table.
     *
     * Requests the exclusive `FOR UPDATE` row lock first (`SELECT ... FOR UPDATE`) to eliminate
     * InnoDB `INSERT IGNORE -> FOR UPDATE` S->X lock-upgrade deadlocks under steady-state traffic,
     * falling back to plain `INSERT` + unique-key race recovery only when initializing a missing day row.
     */
    public function allocateNextDatedSequence(
        string $column,
        string $prefix,
        string $ymd,
        bool $checkDashChildren = true
    ): string {
        $cleanPrefix = trim($prefix, '-');
        $datePrefix = "{$cleanPrefix}-{$ymd}-";
        $existingMaxSeq = $this->resolveExistingMaxSequence($column, $datePrefix);

        if (!Schema::hasTable('fee_daily_sequences')) {
            return $this->findAvailableSequenceCandidate($column, $datePrefix, $existingMaxSeq + 1, $checkDashChildren);
        }

        $scopeKey = "{$column}:{$cleanPrefix}:{$ymd}";
        $nowStr = now()->toDateTimeString();

        $row = DB::table('fee_daily_sequences')
            ->where('scope_key', $scopeKey)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            try {
                DB::table('fee_daily_sequences')->insert([
                    'scope_key' => $scopeKey,
                    'last_seq' => $existingMaxSeq,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);
            } catch (QueryException $e) {
                if (!$this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }
            }

            $row = DB::table('fee_daily_sequences')
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                throw new LogicException("Daily sequence counter '{$scopeKey}' could not be acquired after initialization.");
            }
        }

        $currentSeq = max($existingMaxSeq, (int) $row->last_seq);
        $candidate = $this->findAvailableSequenceCandidate($column, $datePrefix, $currentSeq + 1, $checkDashChildren);
        $assignedSeq = $this->extractSequenceTail($candidate, $datePrefix) ?? ($currentSeq + 1);

        DB::table('fee_daily_sequences')
            ->where('id', $row->id)
            ->update([
                'last_seq' => $assignedSeq,
                'updated_at' => $nowStr,
            ]);

        return $candidate;
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $errorInfo = property_exists($e, 'errorInfo') && is_array($e->errorInfo) ? $e->errorInfo : [];
        $sqlState = isset($errorInfo[0]) ? (string) $errorInfo[0] : (string) $e->getCode();
        $driverCode = isset($errorInfo[1]) ? (int) $errorInfo[1] : null;
        $message = strtolower($e->getMessage());

        if ($sqlState === '23505' || $driverCode === 1062) {
            return true;
        }

        return $sqlState === '23000'
            && (str_contains($message, 'unique') || str_contains($message, 'duplicate'));
    }

    /**
     * Convert HTTP request scalar (`int`/`float` JSON number or trimmed `string`) at Controller boundary
     * into a string before passing to strict string-only BCMath service methods.
     */
    public static function normalizeHttpMonetaryInput(mixed $rawValue): mixed
    {
        if (is_int($rawValue)) {
            return (string) $rawValue;
        }

        if (is_float($rawValue)) {
            $formatted = rtrim(rtrim(sprintf('%.6F', $rawValue), '0'), '.');
            return $formatted === '' ? '0' : $formatted;
        }

        if (is_string($rawValue)) {
            return trim($rawValue);
        }

        return $rawValue;
    }

    /**
     * Resolve and authorize active Admin / Super Admin actor.
     *
     * @throws AuthorizationException
     */
    public function resolveAndAuthorizeAdminActor(int $actorUserId): User
    {
        $actor = $actorUserId > 0 ? User::with('roles')->find($actorUserId) : null;

        if (!AdmissionInvoiceService::userCanGrantDiscount($actor)) {
            throw new AuthorizationException(
                'Unauthorized. Only active Admin and Super Admin users may collect fee payments.'
            );
        }

        return $actor;
    }

    /**
     * Normalize HTTP or string input into a 2-decimal string without unsafe PHP float drift.
     *
     * @throws ValidationException
     */
    public static function parseStrictPositiveAmount(mixed $rawValue, string $fieldName): string
    {
        return (new self(app(FinancialTransactionRunner::class)))->validatePositivePaymentAmount($rawValue, $fieldName);
    }

    /**
     * @throws ValidationException
     */
    private function assertAdmissionPaymentSchemaReady(): void
    {
        if (
            !Schema::hasTable('admission_invoice_head_allocations')
            || !Schema::hasTable('admission_payment_head_allocations')
            || !Schema::hasColumns('fee_payments', ['branch_id', 'status'])
        ) {
            throw ValidationException::withMessages([
                'invoice_id' => ['Admission payment allocation tables or columns are not migrated yet.'],
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertInvoicePayableForAdmission(FeeInvoice $invoice, Student $student): void
    {
        if (!$invoice->isAdmissionInvoice()) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Invoice ({$invoice->invoice_no}) is not a multi-head Admission Fee invoice."],
            ]);
        }

        if ((int) $invoice->student_id !== (int) $student->id) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Invoice ({$invoice->invoice_no}) does not belong to student ID {$student->id}."],
            ]);
        }

        if ($invoice->isCancelled()) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Cannot collect payment for a cancelled invoice ({$invoice->invoice_no})."],
            ]);
        }

        $due = $this->toDecimalString($invoice->due_amount);
        if ($invoice->isZeroPayableWaived() || bccomp($due, '0.00', 2) <= 0 || ($invoice->status ?? '') === 'Paid') {
            throw ValidationException::withMessages([
                'invoice_id' => ["Invoice ({$invoice->invoice_no}) has no payable balance due."],
            ]);
        }
    }

    /**
     * Verify active payment ledger matches head and invoice totals before collecting.
     *
     * @param  Collection<int, FeePayment>  $lockedPayments
     * @param  Collection<int, AdmissionInvoiceHeadAllocation>  $lockedInvoiceHeads
     * @param  Collection<int, AdmissionPaymentHeadAllocation>  $lockedPaymentHeads
     * @return array{0: string, 1: string}
     */
    private function assertAdmissionLedgerConsistencyBeforePayment(
        FeeInvoice $invoice,
        Student $student,
        Collection $lockedPayments,
        Collection $lockedInvoiceHeads,
        Collection $lockedPaymentHeads
    ): array {
        $invoiceNet = $this->toDecimalString($invoice->net_amount);
        $activePaidFromPayments = $this->sumActivePayments($lockedPayments);
        $verifiedDue = bcsub($invoiceNet, $activePaidFromPayments, 2);

        $activeHeadPaidByHeadId = [];
        foreach ($lockedPaymentHeads as $ph) {
            if (($ph->status ?? 'Active') !== 'Active') {
                continue;
            }
            $headId = (int) $ph->admission_invoice_head_allocation_id;
            $activeHeadPaidByHeadId[$headId] = bcadd(
                $activeHeadPaidByHeadId[$headId] ?? '0.00',
                $this->toDecimalString($ph->allocated_paid_amount),
                2
            );
        }

        $sumHeadsNet = '0.00';
        $sumHeadsPaid = '0.00';
        $sumHeadsDue = '0.00';

        foreach ($lockedInvoiceHeads as $head) {
            if ((int) $head->fee_invoice_id !== (int) $invoice->id
                || (int) $head->student_id !== (int) $student->id
                || (int) $head->branch_id !== (int) $invoice->branch_id
            ) {
                throw new LogicException("Head ownership mismatch detected on head ID {$head->id}.");
            }

            $headNet = $this->toDecimalString($head->net_amount);
            $headPaid = $this->toDecimalString($head->allocated_paid_amount);
            $headDue = $this->toDecimalString($head->due_amount);

            if (bccomp(bcsub($headNet, $headPaid, 2), $headDue, 2) !== 0) {
                throw new LogicException("Head math integrity violation on head ID {$head->id}.");
            }

            $expectedActiveHeadPaid = $activeHeadPaidByHeadId[(int) $head->id] ?? '0.00';
            if (bccomp($expectedActiveHeadPaid, $headPaid, 2) !== 0) {
                throw new LogicException(
                    "Active payment head sum ({$expectedActiveHeadPaid}) does not match head ID {$head->id} allocated_paid_amount ({$headPaid})."
                );
            }

            $sumHeadsNet = bcadd($sumHeadsNet, $headNet, 2);
            $sumHeadsPaid = bcadd($sumHeadsPaid, $headPaid, 2);
            $sumHeadsDue = bcadd($sumHeadsDue, $headDue, 2);
        }

        if (bccomp($sumHeadsNet, $invoiceNet, 2) !== 0) {
            throw new LogicException("Invoice net ({$invoiceNet}) mismatches sum of head nets ({$sumHeadsNet}).");
        }

        if (bccomp($sumHeadsPaid, $activePaidFromPayments, 2) !== 0) {
            throw new LogicException("Invoice active payment sum ({$activePaidFromPayments}) mismatches head paid sum ({$sumHeadsPaid}).");
        }

        if (bccomp($sumHeadsDue, $verifiedDue, 2) !== 0) {
            throw new LogicException("Invoice due ({$verifiedDue}) mismatches head due sum ({$sumHeadsDue}).");
        }

        return [$activePaidFromPayments, $verifiedDue];
    }

    /**
     * Resolve exact per-head payments from explicit `head_allocations` map or proportional BCMath split.
     *
     * @param  Collection<int, AdmissionInvoiceHeadAllocation>  $orderedHeads
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: array<int, string>}
     *
     * @throws ValidationException
     */
    private function resolveHeadPaymentBreakdown(
        Collection $orderedHeads,
        string $invoiceDue,
        array $payload
    ): array {
        $rawHeadMap = $payload['head_allocations'] ?? null;

        if (is_array($rawHeadMap) && count($rawHeadMap) > 0) {
            $headsById = $orderedHeads->keyBy('id');
            $perHead = [];
            $sum = '0.00';

            foreach ($rawHeadMap as $headIdKey => $rawAmt) {
                $headId = (int) $headIdKey;
                /** @var AdmissionInvoiceHeadAllocation|null $head */
                $head = $headsById->get($headId);
                if (!$head) {
                    throw ValidationException::withMessages([
                        "head_allocations.{$headIdKey}" => ["Fee head ID {$headIdKey} does not belong to this invoice."],
                    ]);
                }

                $amtStr = $this->validateNonNegativeDecimal($rawAmt, "head_allocations.{$headIdKey}");
                $headDue = $this->toDecimalString($head->due_amount);

                if (bccomp($amtStr, $headDue, 2) > 0) {
                    throw ValidationException::withMessages([
                        "head_allocations.{$headIdKey}" => [
                            "Paying amount (৳{$amtStr}) for '{$head->head_name_snapshot}' exceeds its remaining due (৳{$headDue}).",
                        ],
                    ]);
                }

                $perHead[$headId] = $amtStr;
                $sum = bcadd($sum, $amtStr, 2);
            }

            if (bccomp($sum, '0.00', 2) <= 0) {
                throw ValidationException::withMessages([
                    'head_allocations' => ['Total allocated payment across fee heads must be greater than 0.00.'],
                ]);
            }

            if (array_key_exists('pay_amount', $payload) && $payload['pay_amount'] !== null && $payload['pay_amount'] !== '') {
                $declaredPayAmount = $this->validatePositivePaymentAmount($payload['pay_amount'], 'pay_amount');
                if (bccomp($declaredPayAmount, $sum, 2) !== 0) {
                    throw ValidationException::withMessages([
                        'pay_amount' => [
                            "Submitted pay_amount (৳{$declaredPayAmount}) does not match sum of head allocations (৳{$sum}).",
                        ],
                    ]);
                }
            }

            if (bccomp($sum, $invoiceDue, 2) > 0) {
                throw ValidationException::withMessages([
                    'pay_amount' => ["Total payment (৳{$sum}) exceeds remaining invoice due (৳{$invoiceDue})."],
                ]);
            }

            return [$sum, $perHead];
        }

        $payAmount = $this->validatePositivePaymentAmount($payload['pay_amount'] ?? null, 'pay_amount');

        if (bccomp($payAmount, $invoiceDue, 2) > 0) {
            throw ValidationException::withMessages([
                'pay_amount' => ["Paying amount (৳{$payAmount}) cannot exceed remaining invoice due (৳{$invoiceDue})."],
            ]);
        }

        $includeTuition = !array_key_exists('include_tuition_in_auto_split', $payload)
            || filter_var($payload['include_tuition_in_auto_split'], FILTER_VALIDATE_BOOLEAN);

        $targetHeads = $includeTuition
            ? $orderedHeads
            : $orderedHeads->filter(static fn (AdmissionInvoiceHeadAllocation $h): bool => !(bool) $h->is_tuition_head)->values();

        $eligibleDue = '0.00';
        foreach ($targetHeads as $head) {
            $headDue = $this->toDecimalString($head->due_amount);
            if (bccomp($headDue, '0.00', 2) > 0) {
                $eligibleDue = bcadd($eligibleDue, $headDue, 2);
            }
        }

        if (!$includeTuition) {
            if (bccomp($eligibleDue, '0.00', 2) <= 0) {
                throw ValidationException::withMessages([
                    'pay_amount' => ['No pending non-tuition admission heads remain to auto-split. Select Monthly Tuition Fee to collect remaining tuition due.'],
                ]);
            }

            if (bccomp($payAmount, $eligibleDue, 2) > 0) {
                throw ValidationException::withMessages([
                    'pay_amount' => ["Paying amount (৳{$payAmount}) exceeds eligible non-tuition due (৳{$eligibleDue}). Select Monthly Tuition Fee to collect more."],
                ]);
            }
        }

        if (bccomp($payAmount, $eligibleDue, 2) === 0) {
            $perHead = [];
            foreach ($targetHeads as $head) {
                $headDue = $this->toDecimalString($head->due_amount);
                if (bccomp($headDue, '0.00', 2) > 0) {
                    $perHead[(int) $head->id] = $headDue;
                }
            }

            return [$payAmount, $perHead];
        }

        $dueHeadsPayload = [];
        foreach ($targetHeads as $head) {
            $headDue = $this->toDecimalString($head->due_amount);
            if (bccomp($headDue, '0.00', 2) > 0) {
                $dueHeadsPayload[] = [
                    'id' => (int) $head->id,
                    'sort_order' => (int) $head->sort_order,
                    'amount' => $headDue,
                    'is_discountable' => true,
                ];
            }
        }

        // Reuse verified exact integer-paisa `bcmod` remainder distribution on eligible pending head dues.
        $allocated = AdmissionDiscountEngine::allocate($dueHeadsPayload, $payAmount);

        $perHead = [];
        foreach ($allocated as $row) {
            $perHead[(int) $row['id']] = $row['discount'];
        }

        return [$payAmount, $perHead];
    }

    /**
     * Assert strict post-payment accounting invariants before transaction commit.
     *
     * @param  Collection<int, AdmissionInvoiceHeadAllocation>  $orderedHeads
     * @param  Collection<int, AdmissionPaymentHeadAllocation>  $createdPaymentHeads
     */
    private function assertPostPaymentInvariants(
        FeeInvoice $invoice,
        Collection $orderedHeads,
        Collection $createdPaymentHeads,
        string $expectedPaymentTotal
    ): void {
        $sumPaymentHeads = '0.00';
        foreach ($createdPaymentHeads as $ph) {
            $allocAmt = $this->toDecimalString($ph->allocated_paid_amount);
            if (bccomp($allocAmt, '0.00', 2) <= 0) {
                throw new LogicException('Invariant Violation: Non-positive AdmissionPaymentHeadAllocation inserted.');
            }
            $sumPaymentHeads = bcadd($sumPaymentHeads, $allocAmt, 2);
        }

        if (bccomp($sumPaymentHeads, $expectedPaymentTotal, 2) !== 0) {
            throw new LogicException("Invariant Violation: Sum of payment head allocations ({$sumPaymentHeads}) != paid_amount ({$expectedPaymentTotal}).");
        }

        $sumHeadPaid = '0.00';
        $sumHeadDue = '0.00';
        foreach ($orderedHeads as $head) {
            $sumHeadPaid = bcadd($sumHeadPaid, $this->toDecimalString($head->allocated_paid_amount), 2);
            $sumHeadDue = bcadd($sumHeadDue, $this->toDecimalString($head->due_amount), 2);
        }

        if (bccomp($sumHeadPaid, $this->toDecimalString($invoice->paid_amount), 2) !== 0) {
            throw new LogicException('Invariant Violation: Sum of invoice head paid amounts disagrees with invoice paid_amount.');
        }

        if (bccomp($sumHeadDue, $this->toDecimalString($invoice->due_amount), 2) !== 0) {
            throw new LogicException('Invariant Violation: Sum of invoice head due amounts disagrees with invoice due_amount.');
        }
    }

    /**
     * Determine whether the request payload declares any positive cash collection amount.
     *
     * @param  array<string, mixed>  $payload
     */
    private function payloadHasPositiveCashIntent(array $payload): bool
    {
        $rawHeadMap = $payload['head_allocations'] ?? null;
        if (is_array($rawHeadMap) && count($rawHeadMap) > 0) {
            foreach ($rawHeadMap as $rawAmt) {
                if ($rawAmt === null || $rawAmt === '') {
                    continue;
                }
                if (is_numeric($rawAmt) && bccomp($this->toDecimalString($rawAmt), '0.00', 2) > 0) {
                    return true;
                }
                if (is_string($rawAmt) && trim($rawAmt) !== '' && bccomp($this->validateNonNegativeDecimal($rawAmt, 'head_allocations'), '0.00', 2) > 0) {
                    return true;
                }
            }

            return false;
        }

        if (array_key_exists('pay_amount', $payload) && $payload['pay_amount'] !== null && $payload['pay_amount'] !== '') {
            $amt = $this->validateNonNegativeDecimal($payload['pay_amount'], 'pay_amount');
            return bccomp($amt, '0.00', 2) > 0;
        }

        return false;
    }

    /**
     * Apply a collection-time fixed Direct Discount strictly across unpaid `is_discountable`
     * head allocations, keeping all BCMath invariants, CHECK constraints, and audit logs consistent.
     *
     * @param  Collection<int, AdmissionInvoiceHeadAllocation>  $orderedHeads
     *
     * @throws ValidationException
     */
    private function applyDirectCollectionDiscount(
        FeeInvoice $lockedInvoice,
        Student $lockedStudent,
        Collection $orderedHeads,
        string $directDiscountAmount,
        string $verifiedActivePaid,
        string $verifiedDue,
        User $actor,
        ?string $rawReason = null
    ): void {
        $discountableHeadsPayload = [];
        $eligibleDiscountableDue = '0.00';

        foreach ($orderedHeads as $head) {
            $headDue = $this->toDecimalString($head->due_amount);
            if ($head->is_discountable && bccomp($headDue, '0.00', 2) > 0) {
                $discountableHeadsPayload[] = [
                    'id' => (int) $head->id,
                    'sort_order' => (int) $head->sort_order,
                    'amount' => $headDue,
                    'is_discountable' => true,
                ];
                $eligibleDiscountableDue = bcadd($eligibleDiscountableDue, $headDue, 2);
            }
        }

        if (bccomp($eligibleDiscountableDue, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'direct_discount_amount' => [
                    "No eligible discountable fee head balance remains on invoice {$lockedInvoice->invoice_no}.",
                ],
            ]);
        }

        if (bccomp($directDiscountAmount, $eligibleDiscountableDue, 2) > 0) {
            throw ValidationException::withMessages([
                'direct_discount_amount' => [
                    "Direct discount (৳{$directDiscountAmount}) exceeds eligible discountable due balance (৳{$eligibleDiscountableDue}).",
                ],
            ]);
        }

        $allocatedDiscountRows = AdmissionDiscountEngine::allocate($discountableHeadsPayload, $directDiscountAmount);
        $addDiscountByHeadId = [];
        foreach ($allocatedDiscountRows as $row) {
            $addDiscountByHeadId[(int) $row['id']] = $row['discount'];
        }

        foreach ($orderedHeads as $head) {
            $addDisc = $addDiscountByHeadId[(int) $head->id] ?? '0.00';
            if (bccomp($addDisc, '0.00', 2) <= 0) {
                continue;
            }

            if (!$head->is_discountable) {
                throw new LogicException("Invariant Violation: Cannot allocate direct discount to non-discountable head ID {$head->id}.");
            }

            $headGross = $this->toDecimalString($head->amount);
            $headPaid = $this->toDecimalString($head->allocated_paid_amount);
            $newHeadDiscount = bcadd($this->toDecimalString($head->discount), $addDisc, 2);
            $newHeadNet = bcsub($headGross, $newHeadDiscount, 2);
            $newHeadDue = bcsub($newHeadNet, $headPaid, 2);

            if (bccomp($newHeadDue, '0.00', 2) < 0 || bccomp($newHeadNet, $headPaid, 2) < 0) {
                throw new LogicException("Invariant Violation: Direct discount made head ID {$head->id} net below paid amount.");
            }

            $head->discount = $newHeadDiscount;
            $head->net_amount = $newHeadNet;
            $head->due_amount = $newHeadDue;
            $head->save();
        }

        $invoiceGross = $this->toDecimalString($lockedInvoice->amount);
        $prevDiscount = $this->toDecimalString($lockedInvoice->discount);
        $prevNet = $this->toDecimalString($lockedInvoice->net_amount);
        $newInvoiceDiscount = bcadd($prevDiscount, $directDiscountAmount, 2);
        $newInvoiceNet = bcsub($invoiceGross, $newInvoiceDiscount, 2);
        $newInvoiceDue = bcsub($newInvoiceNet, $verifiedActivePaid, 2);

        if (bccomp($newInvoiceDue, '0.00', 2) < 0 || bccomp($newInvoiceNet, $verifiedActivePaid, 2) < 0) {
            throw new LogicException("Invariant Violation: Invoice {$lockedInvoice->invoice_no} net fell below active paid balance.");
        }

        $lockedInvoice->discount = $newInvoiceDiscount;
        $lockedInvoice->net_amount = $newInvoiceNet;
        $lockedInvoice->due_amount = $newInvoiceDue;
        $lockedInvoice->status = bccomp($newInvoiceDue, '0.00', 2) === 0
            ? (bccomp($verifiedActivePaid, '0.00', 2) > 0 ? 'Paid' : 'Unpaid')
            : (bccomp($verifiedActivePaid, '0.00', 2) > 0 ? 'Partial' : 'Unpaid');
        $lockedInvoice->save();

        $trimmedReason = trim((string) ($rawReason ?? ''));
        FeeAdjustmentLog::create([
            'fee_invoice_id' => $lockedInvoice->id,
            'student_id' => $lockedStudent->id,
            'user_id' => $actor->id,
            'invoice_no' => $lockedInvoice->invoice_no,
            'student_identity' => $lockedStudent->student_identity,
            'mode' => 'decrease',
            'scope' => 'single',
            'adjustment_amount' => $directDiscountAmount,
            'previous_amount' => $invoiceGross,
            'previous_discount' => $prevDiscount,
            'previous_net_amount' => $prevNet,
            'previous_due_amount' => $verifiedDue,
            'new_amount' => $invoiceGross,
            'new_discount' => $newInvoiceDiscount,
            'new_net_amount' => $newInvoiceNet,
            'new_due_amount' => $newInvoiceDue,
            'paid_amount' => $verifiedActivePaid,
            'reason' => $trimmedReason !== '' ? $trimmedReason : 'Direct admission discount applied during collection',
        ]);
    }

    /**
     * Assert strict post-discount head vs invoice mathematical invariants for Discount-Only transactions.
     *
     * @param  Collection<int, AdmissionInvoiceHeadAllocation>  $orderedHeads
     */
    private function assertPostDiscountInvariants(
        FeeInvoice $invoice,
        Collection $orderedHeads,
        string $verifiedActivePaid
    ): void {
        $sumGross = '0.00';
        $sumDiscount = '0.00';
        $sumNet = '0.00';
        $sumPaid = '0.00';
        $sumDue = '0.00';

        foreach ($orderedHeads as $head) {
            $gross = $this->toDecimalString($head->amount);
            $disc = $this->toDecimalString($head->discount);
            $net = $this->toDecimalString($head->net_amount);
            $paid = $this->toDecimalString($head->allocated_paid_amount);
            $due = $this->toDecimalString($head->due_amount);

            if (!$head->is_discountable && bccomp($disc, '0.00', 2) !== 0) {
                throw new LogicException("Invariant Violation: Non-discountable head ID {$head->id} has non-zero discount.");
            }
            if (bccomp(bcsub($gross, $disc, 2), $net, 2) !== 0 || bccomp(bcsub($net, $paid, 2), $due, 2) !== 0) {
                throw new LogicException("Invariant Violation: Head ID {$head->id} math mismatch after direct discount.");
            }

            $sumGross = bcadd($sumGross, $gross, 2);
            $sumDiscount = bcadd($sumDiscount, $disc, 2);
            $sumNet = bcadd($sumNet, $net, 2);
            $sumPaid = bcadd($sumPaid, $paid, 2);
            $sumDue = bcadd($sumDue, $due, 2);
        }

        if (
            bccomp($sumGross, $this->toDecimalString($invoice->amount), 2) !== 0
            || bccomp($sumDiscount, $this->toDecimalString($invoice->discount), 2) !== 0
            || bccomp($sumNet, $this->toDecimalString($invoice->net_amount), 2) !== 0
            || bccomp($sumPaid, $verifiedActivePaid, 2) !== 0
            || bccomp($sumDue, $this->toDecimalString($invoice->due_amount), 2) !== 0
        ) {
            throw new LogicException("Invariant Violation: Invoice {$invoice->invoice_no} totals disagree with head sums after direct discount.");
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateNonNegativeDecimal(mixed $rawValue, string $fieldName): string
    {
        if (is_string($rawValue)) {
            $rawValue = trim($rawValue);
        }

        try {
            return AdmissionDiscountEngine::validateMonetaryString($rawValue, $fieldName);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                $fieldName => [$e->getMessage()],
            ]);
        }
    }

    public function toDecimalString(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match('/^-?\d+(\.\d+)?$/', $trimmed)) {
                return bcadd($trimmed, '0.00', 2);
            }
        }

        return bcadd((string) $value, '0.00', 2);
    }

    public function sumActivePayments(Collection $payments): string
    {
        $total = '0.00';
        foreach ($payments as $p) {
            if (($p->status ?? 'Active') === 'Active') {
                $total = bcadd($total, $this->toDecimalString($p->paid_amount), 2);
            }
        }

        return $total;
    }

    /**
     * @throws ValidationException
     */
    private function normalizePaymentDate(mixed $rawDate): string
    {
        if ($rawDate === null || trim((string) $rawDate) === '') {
            return now()->toDateString();
        }

        try {
            return Carbon::parse((string) $rawDate)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'payment_date' => ['Invalid payment date format.'],
            ]);
        }
    }

    private function resolveExistingMaxSequence(string $column, string $datePrefix): int
    {
        $existing = FeePayment::where($column, 'like', $datePrefix . '%')->pluck($column);
        $max = 0;

        foreach ($existing as $code) {
            $seq = $this->extractSequenceTail((string) $code, $datePrefix);
            if ($seq !== null && $seq > $max) {
                $max = $seq;
            }
        }

        return $max;
    }

    private function extractSequenceTail(string $value, string $datePrefix): ?int
    {
        if (!str_starts_with($value, $datePrefix)) {
            return null;
        }

        $rest = substr($value, strlen($datePrefix));
        if (preg_match('/^(\d+)(?:-\d+)?$/', $rest, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * @throws ValidationException
     */
    private function validatePositivePaymentAmount(mixed $rawValue, string $fieldName): string
    {
        $normalized = $this->validateNonNegativeDecimal($rawValue, $fieldName);
        if (bccomp($normalized, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                $fieldName => ['Payment amount must be greater than 0.00.'],
            ]);
        }

        return $normalized;
    }

    private function findAvailableSequenceCandidate(
        string $column,
        string $datePrefix,
        int $startSeq,
        bool $checkDashChildren
    ): string {
        $seq = max(1, $startSeq);

        while (true) {
            $candidate = sprintf('%s%04d', $datePrefix, $seq);
            $existsExact = FeePayment::where($column, $candidate)->exists();
            $existsChildren = $checkDashChildren
                ? FeePayment::where($column, 'like', $candidate . '-%')->exists()
                : false;

            if (!$existsExact && !$existsChildren) {
                return $candidate;
            }

            $seq++;
        }
    }
}
