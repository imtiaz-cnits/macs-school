<?php

namespace App\Services;

use App\Helpers\NumberToWords;
use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Shared read-only presenter and BCMath aggregator for Single Invoice POS,
 * A5 Master Receipts, and Bulk Individual POS Slips across both
 * Multi-Head Admission Packages and Legacy Fee Invoices.
 */
class FeeReceiptService
{
    private const MONTH_ORDER_MAP = [
        'january' => 1,
        'february' => 2,
        'march' => 3,
        'april' => 4,
        'may' => 5,
        'june' => 6,
        'july' => 7,
        'august' => 8,
        'september' => 9,
        'october' => 10,
        'november' => 11,
        'december' => 12,
    ];

    public function admissionTablesAvailable(): bool
    {
        return Schema::hasTable('admission_fee_structures')
            && Schema::hasTable('admission_invoice_head_allocations')
            && Schema::hasTable('admission_payment_head_allocations');
    }

    /**
     * Eager-load graph required for Single Invoice POS receipts.
     *
     * @return array<int, string>
     */
    public function invoiceReceiptRelations(): array
    {
        $relations = [
            'student.schoolClass',
            'student.section',
            'student.sessionYear',
            'student.branch',
            'feeSetup.category',
            'user',
            'payments.collector',
            'payments.reversedBy',
        ];

        if ($this->admissionTablesAvailable()) {
            $relations = array_merge($relations, [
                'branch',
                'sessionYear',
                'admissionFeeStructure',
                'admissionHeadAllocations.category',
                'payments.admissionHeadAllocations',
            ]);
        }

        return $relations;
    }

    /**
     * Eager-load graph required for Master Receipts and Bulk Individual POS Slips.
     *
     * @return array<int, string>
     */
    public function masterPaymentRelations(): array
    {
        $relations = [
            'invoice.student.schoolClass',
            'invoice.student.section',
            'invoice.student.sessionYear',
            'invoice.student.branch',
            'invoice.feeSetup.category',
            'invoice.user',
            'invoice.payments.collector',
            'invoice.payments.reversedBy',
            'student.schoolClass',
            'student.section',
            'student.sessionYear',
            'student.branch',
            'collector',
            'reversedBy',
        ];

        if ($this->admissionTablesAvailable()) {
            $relations = array_merge($relations, [
                'invoice.branch',
                'invoice.sessionYear',
                'invoice.admissionFeeStructure',
                'invoice.admissionHeadAllocations.category',
                'admissionHeadAllocations',
            ]);
        }

        return $relations;
    }

    /**
     * Fetch all payments strictly matching a master receipt or exact child receipt
     * without LIKE wildcard exposure or prefix collision.
     *
     * @return Collection<int, FeePayment>
     */
    public function fetchPaymentsForMasterReceipt(string $receiptNo): Collection
    {
        return FeePayment::with($this->masterPaymentRelations())
            ->forMasterReceipt($receiptNo)
            ->orderBy('id', 'asc')
            ->get()
            ->filter(fn (FeePayment $payment): bool => $payment->belongsToMasterReceipt($receiptNo))
            ->values();
    }

    /**
     * Fetch selected payments by ID with full receipt eager loads.
     *
     * @param  array<int, int|string>  $paymentIds
     * @return Collection<int, FeePayment>
     */
    public function fetchPaymentsByIds(array $paymentIds): Collection
    {
        $cleanIds = collect($paymentIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($cleanIds)) {
            return collect();
        }

        return FeePayment::with($this->masterPaymentRelations())
            ->whereIn('id', $cleanIds)
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Sort payment lines deterministically across Legacy Monthly, Non-Monthly,
     * and Multi-Head Admission Invoices without triggering HistoricalSnapshotCorruptException.
     *
     * @param  Collection<int, FeePayment>  $payments
     * @return Collection<int, FeePayment>
     */
    public function sortPaymentsForReceipt(Collection $payments): Collection
    {
        return $payments->sort(function (FeePayment $a, FeePayment $b): int {
            $monthA = $this->resolvePaymentSortMonthOrder($a);
            $monthB = $this->resolvePaymentSortMonthOrder($b);
            if ($monthA !== $monthB) {
                return $monthA <=> $monthB;
            }

            $catA = $this->resolvePaymentSortLabel($a);
            $catB = $this->resolvePaymentSortLabel($b);
            $catCmp = strcasecmp($catA, $catB);
            if ($catCmp !== 0) {
                return $catCmp;
            }

            $invA = (int) ($a->fee_invoice_id ?? ($a->invoice->id ?? 0));
            $invB = (int) ($b->fee_invoice_id ?? ($b->invoice->id ?? 0));
            if ($invA !== $invB) {
                return $invA <=> $invB;
            }

            return (int) ($a->id ?? 0) <=> (int) ($b->id ?? 0);
        })->values();
    }

    /**
     * Build presentation data for a Single Invoice POS card (supports both Invoice-wide POS
     * and Receipt-Scoped Individual Slips).
     *
     * @return array<string, mixed>
     */
    public function buildSingleInvoiceReceiptData(FeeInvoice $invoice, ?FeePayment $scopedPayment = null): array
    {
        $isAdmission = $invoice->isAdmissionInvoice();
        $isWaived = $invoice->isZeroPayableWaived();
        $isCancelled = $invoice->isCancelled();
        $scopedIsReversed = $scopedPayment ? $scopedPayment->isReversed() : false;

        /** @var Collection<int, FeePayment> $allPayments */
        $allPayments = $invoice->relationLoaded('payments')
            ? $invoice->payments
            : $invoice->payments()->with(['collector', 'reversedBy', 'admissionHeadAllocations'])->get();

        $activePayments = $allPayments
            ->filter(fn (FeePayment $p): bool => !$p->isReversed())
            ->sort(fn (FeePayment $a, FeePayment $b): int => [$b->payment_date ?? '', $b->id ?? 0] <=> [$a->payment_date ?? '', $a->id ?? 0])
            ->values();

        $reversedPayments = $allPayments
            ->filter(fn (FeePayment $p): bool => $p->isReversed())
            ->sort(fn (FeePayment $a, FeePayment $b): int => [(string) ($b->reversed_at ?? ''), $b->id ?? 0] <=> [(string) ($a->reversed_at ?? ''), $a->id ?? 0])
            ->values();

        $primaryActivePayment = $scopedPayment
            ? ($scopedIsReversed ? null : $scopedPayment)
            : $activePayments->first();

        $grossTotal = $this->toDecimal($invoice->amount);
        $discountTotal = $this->toDecimal($invoice->discount);
        $netTotal = $this->toDecimal($invoice->net_amount);
        $fineTotal = $isAdmission ? '0.00' : $invoice->fineAmountDecimal();
        $hasFine = bccomp($fineTotal, '0.00', 2) > 0;
        $totalPayable = bcadd($netTotal, $fineTotal, 2);

        // Compute authoritative active paid amount from active ledger rows when available.
        $activeLedgerPaid = '0.00';
        $activeLedgerFinePaid = '0.00';
        foreach ($activePayments as $ap) {
            $activeLedgerPaid = bcadd($activeLedgerPaid, $this->toDecimal($ap->paid_amount), 2);
            if (!$isAdmission) {
                $activeLedgerFinePaid = bcadd($activeLedgerFinePaid, $ap->fineAmountDecimal(), 2);
            }
        }
        $invoicePaidTotal = $activePayments->isNotEmpty() || $reversedPayments->isNotEmpty()
            ? $activeLedgerPaid
            : $this->toDecimal($invoice->paid_amount);

        $rawInvoiceDue = bcsub($totalPayable, $invoicePaidTotal, 2);
        $invoiceDueTotal = bccomp($rawInvoiceDue, '0.00', 2) > 0 ? $rawInvoiceDue : '0.00';

        $scopedPaidTotal = $scopedPayment
            ? ($scopedIsReversed ? '0.00' : $this->toDecimal($scopedPayment->paid_amount))
            : $invoicePaidTotal;

        $scopedReversedTotal = ($scopedPayment && $scopedIsReversed)
            ? $this->toDecimal($scopedPayment->reversed_amount ?: $scopedPayment->paid_amount)
            : '0.00';

        // Compute itemized tuition vs fine paid amounts (pure BCMath)
        if ($scopedPayment) {
            if ($scopedIsReversed) {
                $displayFinePaid = '0.00';
                $displayTuitionPaid = '0.00';
            } else {
                $displayFinePaid = $isAdmission ? '0.00' : $scopedPayment->fineAmountDecimal();
                $displayTuitionPaid = $isAdmission ? $scopedPaidTotal : $scopedPayment->tuitionAmountDecimal();
            }
        } else {
            if ($hasFine) {
                if (bccomp($activeLedgerFinePaid, '0.00', 2) > 0) {
                    $displayFinePaid = bccomp($activeLedgerFinePaid, $fineTotal, 2) <= 0 ? $activeLedgerFinePaid : $fineTotal;
                } elseif (bccomp($invoicePaidTotal, $netTotal, 2) > 0) {
                    $overNet = bcsub($invoicePaidTotal, $netTotal, 2);
                    $displayFinePaid = bccomp($overNet, $fineTotal, 2) <= 0 ? $overNet : $fineTotal;
                } else {
                    $displayFinePaid = '0.00';
                }
            } else {
                $displayFinePaid = '0.00';
            }
            $tuitionPaidRaw = bcsub($invoicePaidTotal, $displayFinePaid, 2);
            $displayTuitionPaid = bccomp($tuitionPaidRaw, '0.00', 2) > 0 ? $tuitionPaidRaw : '0.00';
        }

        $structure = $isAdmission ? $invoice->admissionFeeStructure : null;
        $structureName = trim((string) ($structure?->structure_name ?? '')) !== ''
            ? trim((string) $structure->structure_name)
            : 'Admission Fee Package';

        $tuitionMonthLabel = null;
        if ($isAdmission && $structure && $structure->includes_monthly_tuition && !empty($structure->tuition_month_number)) {
            $mNum = (int) $structure->tuition_month_number;
            if ($mNum >= 1 && $mNum <= 12) {
                $tuitionMonthLabel = Carbon::createFromDate(2026, $mNum, 1)->format('F');
            }
        }

        $branchName = trim((string) ($invoice->branch_name_snapshot ?? ''));
        if ($branchName === '') {
            $branchName = trim((string) ($invoice->branch?->branch_name ?? $invoice->student?->branch?->branch_name ?? 'Jalalpur Branch'));
        }
        if ($branchName === '') {
            $branchName = 'Jalalpur Branch';
        }

        $monthLabel = $this->resolveLegacyOrAdmissionMonth($invoice);

        // Status label strictly respects REVERSED (on scoped reversed slip), CANCELLED, WAIVED, and PAID/PARTIAL/UNPAID.
        if ($scopedIsReversed) {
            $statusLabel = 'REVERSED';
        } elseif ($isCancelled) {
            $statusLabel = 'CANCELLED';
        } else {
            $statusLabel = strtoupper((string) $invoice->display_status);
        }

        // Strict Payment Metadata Rule: never show fake Cash method on Waived invoices or expose Reversed metadata as Active.
        $paymentMeta = $this->resolveSinglePaymentMeta(
            $invoice,
            $isAdmission,
            $isWaived,
            $scopedPayment,
            $scopedIsReversed,
            $primaryActivePayment,
            $activePayments,
            $reversedPayments
        );

        // Head breakdown for Admission Invoices.
        $headRows = [];
        $headAllocationAudit = [
            'has_status_mismatch' => false,
            'missing_expected_allocations' => false,
            'used_provenance_fallback' => false,
        ];
        if ($isAdmission) {
            $headBreakdown = $this->buildAdmissionInvoiceHeadRows($invoice, $scopedPayment);
            $headRows = $headBreakdown['rows'];
            $headAllocationAudit = $headBreakdown['audit'];
        }

        $dateRaw = $scopedPayment
            ? ($scopedPayment->payment_date ?: $scopedPayment->created_at ?: $invoice->created_at)
            : $invoice->created_at;

        $paidAmountForWords = !is_null($scopedPayment)
            ? ($scopedIsReversed ? '0.00' : $scopedPaidTotal)
            : $invoicePaidTotal;

        return [
            'invoice' => $invoice,
            'is_admission' => $isAdmission,
            'is_scoped_payment' => !is_null($scopedPayment),
            'scoped_payment' => $scopedPayment,
            'scoped_is_reversed' => $scopedIsReversed,
            'is_cancelled' => $isCancelled,
            'is_waived' => $isWaived,
            'status_label' => $statusLabel,
            'branch_name' => $branchName,
            'structure_name' => $structureName,
            'tuition_month_label' => $tuitionMonthLabel,
            'month_label' => $monthLabel,
            'legacy_category_name' => !$isAdmission ? ($invoice->feeSetup->category->name ?? 'Fee Payment') : $structureName,
            'head_rows' => $headRows,
            'head_allocation_audit' => $headAllocationAudit,
            'has_discount' => bccomp($discountTotal, '0.00', 2) > 0,
            'gross_total' => $grossTotal,
            'gross_total_formatted' => $this->formatAmount($grossTotal),
            'discount_total' => $discountTotal,
            'discount_total_formatted' => $this->formatAmount($discountTotal),
            'net_total' => $netTotal,
            'net_total_formatted' => $this->formatAmount($netTotal),
            'has_fine' => $hasFine,
            'fine_total' => $fineTotal,
            'fine_total_formatted' => $this->formatAmount($fineTotal),
            'fine_source' => $invoice->fine_source ?? null,
            'fine_reason' => $invoice->fine_reason ?? null,
            'total_payable' => $totalPayable,
            'total_payable_formatted' => $this->formatAmount($totalPayable),
            'tuition_paid_total' => $displayTuitionPaid,
            'tuition_paid_total_formatted' => $this->formatAmount($displayTuitionPaid),
            'fine_paid_total' => $displayFinePaid,
            'fine_paid_total_formatted' => $this->formatAmount($displayFinePaid),
            'invoice_paid_total' => $invoicePaidTotal,
            'invoice_paid_total_formatted' => $this->formatAmount($invoicePaidTotal),
            'invoice_due_total' => $invoiceDueTotal,
            'invoice_due_total_formatted' => $this->formatAmount($invoiceDueTotal),
            'scoped_paid_total' => $scopedPaidTotal,
            'scoped_paid_total_formatted' => $this->formatAmount($scopedPaidTotal),
            'scoped_reversed_total' => $scopedReversedTotal,
            'scoped_reversed_total_formatted' => $this->formatAmount($scopedReversedTotal),
            'paid_in_words' => NumberToWords::convert($paidAmountForWords),
            'active_payments' => $activePayments,
            'reversed_payments' => $reversedPayments,
            'primary_active_payment' => $primaryActivePayment,
            'payment_meta' => $paymentMeta,
            'date_display' => $dateRaw ? date('d-M-Y', strtotime((string) $dateRaw)) : 'N/A',
        ];
    }

    /**
     * Build presentation data for an A5 Master Receipt supporting Legacy, Admission,
     * Mixed Admission + Legacy, Partial Reversals, and Full VOID / Reversed Audit Receipts.
     *
     * @param  Collection<int, FeePayment>  $payments
     * @return array<string, mixed>
     */
    public function buildMasterReceiptData(Collection $payments, string $receiptNo): array
    {
        $sortedAll = $this->sortPaymentsForReceipt($payments);

        $activePayments = $sortedAll->filter(fn (FeePayment $p): bool => !$p->isReversed())->values();
        $reversedPayments = $sortedAll->filter(fn (FeePayment $p): bool => $p->isReversed())->values();

        $activeItems = $activePayments->map(fn (FeePayment $p): array => $this->formatMasterReceiptItem($p))->values();
        $reversedItems = $reversedPayments->map(fn (FeePayment $p): array => $this->formatMasterReceiptItem($p))->values();

        $activeGrandTotal = '0.00';
        foreach ($activePayments as $ap) {
            $activeGrandTotal = bcadd($activeGrandTotal, $this->toDecimal($ap->paid_amount), 2);
        }

        $reversedGrandTotal = '0.00';
        foreach ($reversedPayments as $rp) {
            $reversedGrandTotal = bcadd($reversedGrandTotal, $this->toDecimal($rp->reversed_amount ?: $rp->paid_amount), 2);
        }

        $isFullyReversed = $activePayments->isEmpty() && $reversedPayments->isNotEmpty();
        $hasPartialReversals = $activePayments->isNotEmpty() && $reversedPayments->isNotEmpty();

        $primaryPayment = $activePayments->first() ?? $sortedAll->first();
        $scopeCollection = $activePayments->isNotEmpty() ? $activePayments : $sortedAll;

        $student = $primaryPayment?->student;
        $collector = $primaryPayment?->collector;
        $date = $primaryPayment?->created_at ?: $primaryPayment?->payment_date;
        $isMultipleStudents = $sortedAll->pluck('student_id')->unique()->count() > 1;

        $branchName = trim((string) ($primaryPayment?->invoice?->branch_name_snapshot ?? ''));
        if ($branchName === '') {
            $branchName = trim((string) ($primaryPayment?->branch?->branch_name ?? $student?->branch?->branch_name ?? 'Jalalpur Branch'));
        }
        if ($branchName === '') {
            $branchName = 'Jalalpur Branch';
        }

        // Reversal audit metadata (sorted newest reversal first for top banner summary)
        $latestReversed = $reversedPayments
            ->sort(fn (FeePayment $a, FeePayment $b): int => [(string) ($b->reversed_at ?? ''), $b->id ?? 0] <=> [(string) ($a->reversed_at ?? ''), $a->id ?? 0])
            ->first();

        $reversalRefs = $reversedPayments
            ->pluck('reversal_reference')
            ->filter()
            ->unique()
            ->values();

        $primaryActiveMethod = $activePayments->isNotEmpty()
            ? strtoupper((string) ($activePayments->first()->payment_method ?: 'Cash'))
            : null;

        $primaryActiveTrxId = $activePayments->isNotEmpty()
            ? trim((string) ($activePayments->first()->transaction_id ?? ''))
            : '';

        return [
            'receipt_no' => $receiptNo,
            'sorted_payments' => $sortedAll,
            'active_payments' => $activePayments,
            'reversed_payments' => $reversedPayments,
            'active_items' => $activeItems,
            'reversed_items' => $reversedItems,
            'is_fully_reversed' => $isFullyReversed,
            'has_partial_reversals' => $hasPartialReversals,
            'active_grand_total' => $activeGrandTotal,
            'active_grand_total_formatted' => $this->formatAmount($activeGrandTotal),
            'active_grand_total_words' => NumberToWords::convert($activeGrandTotal),
            'reversed_grand_total' => $reversedGrandTotal,
            'reversed_grand_total_formatted' => $this->formatAmount($reversedGrandTotal),
            'student' => $student,
            'collector' => $collector,
            'date' => $date,
            'branch_name' => $branchName,
            'is_multiple_students' => $isMultipleStudents,
            'scope_count' => $scopeCollection->count(),
            'primary_active_method' => $primaryActiveMethod,
            'primary_active_trx_id' => $primaryActiveTrxId !== '' ? $primaryActiveTrxId : null,
            'latest_reversed_payment' => $latestReversed,
            'reversal_references' => $reversalRefs,
            'reversed_at_formatted' => $latestReversed?->reversed_at
                ? Carbon::parse($latestReversed->reversed_at)->timezone('Asia/Dhaka')->format('d-M-Y h:i A')
                : ($latestReversed?->payment_date ? date('d-M-Y', strtotime((string) $latestReversed->payment_date)) : 'N/A'),
            'reversed_by_name' => $latestReversed?->reversedBy?->name ?? ($latestReversed?->reversed_by ? 'Admin #' . $latestReversed->reversed_by : 'Authorized Admin'),
            'reversal_reason' => trim((string) ($latestReversed?->reversal_reason ?? '')) ?: 'No reason recorded',
        ];
    }

    /**
     * Build slip items for Bulk Individual POS or Selected Payments Individual POS.
     *
     * @param  Collection<int, FeePayment>  $payments
     * @return array<string, mixed>
     */
    public function buildBulkIndividualReceiptData(Collection $payments, string $receiptNo, bool $isSelectedMode = false): array
    {
        $validPayments = $payments
            ->filter(fn (FeePayment $p): bool => $p->invoice !== null)
            ->values();

        $orderedPayments = $isSelectedMode
            ? $validPayments->sortBy(fn (FeePayment $p): int => (int) $p->id)->values()
            : $this->sortPaymentsForReceipt($validPayments);

        $slipCards = $orderedPayments
            ->map(fn (FeePayment $payment): array => $this->buildSingleInvoiceReceiptData($payment->invoice, $payment))
            ->values();

        $invoices = $orderedPayments
            ->map(fn (FeePayment $payment): FeeInvoice => $payment->invoice)
            ->unique('id')
            ->values();

        return [
            'receipt_no' => $receiptNo,
            'slip_cards' => $slipCards,
            'invoices' => $invoices,
            'is_selected_mode' => $isSelectedMode,
        ];
    }

    /**
     * Format one Master Receipt payment line using strictly that payment's own
     * AdmissionPaymentHeadAllocation records (never invoice-level cumulative totals).
     *
     * @return array<string, mixed>
     */
    private function formatMasterReceiptItem(FeePayment $payment): array
    {
        $invoice = $payment->invoice;
        $isAdmission = $invoice ? $invoice->isAdmissionInvoice() : false;
        $isReversed = $payment->isReversed();

        $tuitionMonthLabel = null;
        if ($isAdmission && $invoice->admissionFeeStructure?->includes_monthly_tuition && !empty($invoice->admissionFeeStructure->tuition_month_number)) {
            $mNum = (int) $invoice->admissionFeeStructure->tuition_month_number;
            if ($mNum >= 1 && $mNum <= 12) {
                $tuitionMonthLabel = Carbon::createFromDate(2026, $mNum, 1)->format('F');
            }
        }

        if ($isAdmission) {
            $structureName = trim((string) ($invoice->admissionFeeStructure?->structure_name ?? ''));
            $label = $structureName !== ''
                ? "Admission Fee Package — {$structureName}"
                : 'Admission Fee Package';
            $monthLabel = $invoice->month_name ?: 'One Time';
        } else {
            $label = $invoice?->feeSetup?->category?->name ?? 'Fee Payment';
            $monthLabel = $invoice ? $this->resolveLegacyOrAdmissionMonth($invoice) : 'One Time';
        }

        $headLines = [];
        $headSelection = [
            'has_status_mismatch' => false,
            'missing_expected_allocations' => false,
            'used_provenance_fallback' => false,
        ];

        if ($isAdmission) {
            $headSelection = $this->selectOrderedPaymentHeadAllocations($payment);

            foreach ($headSelection['allocations'] as $alloc) {
                $allocDecimal = $this->toDecimal($alloc->allocated_paid_amount);
                $invHeadId = (int) $alloc->admission_invoice_head_allocation_id;
                $actualStatus = $alloc->status !== null ? (string) $alloc->status : null;
                $allocIsReversed = $alloc->isReversed();

                $headLines[] = [
                    'head_name' => $alloc->head_name_snapshot,
                    'category_name' => $alloc->fee_category_name_snapshot,
                    'is_tuition_head' => (bool) ($headSelection['tuition_map'][$invHeadId] ?? false),
                    'allocated_paid' => $allocDecimal,
                    'allocated_paid_formatted' => $this->formatAmount($allocDecimal),
                    'actual_status' => $actualStatus,
                    'is_reversed' => $allocIsReversed,
                    'is_unsynced_original' => $isReversed && !$allocIsReversed,
                ];
            }
        }

        $paidAmount = $this->toDecimal($payment->paid_amount);
        $reversedAmount = $isReversed
            ? $this->toDecimal($payment->reversed_amount ?: $payment->paid_amount)
            : '0.00';
        $finePaid = !$isAdmission ? $payment->fineAmountDecimal() : '0.00';
        $hasFine = bccomp($finePaid, '0.00', 2) > 0;
        $tuitionPaid = $hasFine ? $payment->tuitionAmountDecimal() : $paidAmount;

        return [
            'payment' => $payment,
            'invoice' => $invoice,
            'student' => $payment->student,
            'is_admission' => $isAdmission,
            'is_reversed' => $isReversed,
            'receipt_no' => $payment->receipt_no,
            'invoice_no' => $invoice?->invoice_no ?? 'N/A',
            'label' => $label,
            'tuition_month_label' => $tuitionMonthLabel,
            'month_label' => $monthLabel,
            'has_fine' => $hasFine,
            'fine_source' => $invoice?->fine_source ?? null,
            'tuition_paid' => $tuitionPaid,
            'tuition_paid_formatted' => $this->formatAmount($tuitionPaid),
            'fine_paid' => $finePaid,
            'fine_paid_formatted' => $this->formatAmount($finePaid),
            'paid_amount' => $paidAmount,
            'paid_amount_formatted' => $this->formatAmount($paidAmount),
            'reversed_amount' => $reversedAmount,
            'reversed_amount_formatted' => $this->formatAmount($reversedAmount),
            'reversal_reference' => $payment->reversal_reference,
            'reversal_reason' => $payment->reversal_reason,
            'reversed_at_formatted' => $payment->reversed_at
                ? Carbon::parse($payment->reversed_at)->timezone('Asia/Dhaka')->format('d-M-Y h:i A')
                : null,
            'reversed_by_name' => $payment->reversedBy?->name,
            'head_lines' => $headLines,
            'has_alloc_status_mismatch' => $headSelection['has_status_mismatch'],
            'missing_expected_allocations' => $headSelection['missing_expected_allocations'],
            'used_alloc_provenance_fallback' => $headSelection['used_provenance_fallback'],
        ];
    }

    /**
     * Select and deterministically order a single FeePayment's AdmissionPaymentHeadAllocation
     * rows with strict status isolation:
     * - Active payments accept ONLY explicit `status === 'Active'` rows (NULL, missing,
     *   Reversed, or any other status is never treated as Active).
     * - Reversed payments use explicit `status === 'Reversed'` rows, and may surface
     *   clear-provenance unsynced explicit `status === 'Active'` original rows
     *   (`fee_payment_id` + `fee_invoice_id` match) only as explicitly tagged audit
     *   provenance (never classified as active collected amounts or fake Reversed).
     *
     * @return array{
     *     allocations: Collection<int, AdmissionPaymentHeadAllocation>,
     *     is_payment_reversed: bool,
     *     used_provenance_fallback: bool,
     *     has_status_mismatch: bool,
     *     missing_expected_allocations: bool,
     *     tuition_map: array<int, bool>
     * }
     */
    public function selectOrderedPaymentHeadAllocations(FeePayment $payment): array
    {
        $invoice = $payment->invoice;
        $sortOrderByInvoiceHeadId = [];
        $tuitionMap = [];

        if ($invoice) {
            /** @var Collection<int, AdmissionInvoiceHeadAllocation> $invHeads */
            $invHeads = $invoice->relationLoaded('admissionHeadAllocations')
                ? $invoice->admissionHeadAllocations
                : $invoice->admissionHeadAllocations()->get();

            foreach ($invHeads as $invHead) {
                $headId = (int) $invHead->id;
                $sortOrderByInvoiceHeadId[$headId] = (int) $invHead->sort_order;
                $tuitionMap[$headId] = (bool) $invHead->is_tuition_head;
            }
        }

        /** @var Collection<int, AdmissionPaymentHeadAllocation> $payAllocs */
        $payAllocs = $payment->relationLoaded('admissionHeadAllocations')
            ? $payment->admissionHeadAllocations
            : $payment->admissionHeadAllocations()->get();

        // Restrict strictly to proven child rows of this exact payment & invoice.
        $provenanceScoped = $payAllocs->filter(
            fn (AdmissionPaymentHeadAllocation $alloc): bool => (int) $alloc->fee_payment_id === (int) $payment->id
                && (int) $alloc->fee_invoice_id === (int) $payment->fee_invoice_id
        )->values();

        $isPaymentReversed = $payment->isReversed();
        $usedProvenanceFallback = false;
        $hasStatusMismatch = false;
        $missingExpectedAllocations = false;

        if (!$isPaymentReversed) {
            // Active payment: strictly explicit Active allocations ONLY (NULL, missing, or Reversed never count).
            $selected = $provenanceScoped
                ->filter(fn (AdmissionPaymentHeadAllocation $alloc): bool => $alloc->status === 'Active')
                ->values();

            if ($selected->isEmpty()) {
                $missingExpectedAllocations = true;
                $hasStatusMismatch = $provenanceScoped->isNotEmpty();
            }
        } else {
            // Reversed payment: prefer strictly explicit Reversed allocations.
            $selected = $provenanceScoped
                ->filter(fn (AdmissionPaymentHeadAllocation $alloc): bool => $alloc->status === 'Reversed')
                ->values();

            if ($selected->isEmpty()) {
                $missingExpectedAllocations = true;
                // Only preserve unambiguous unsynced explicit Active original rows on the Reversed audit path.
                $unsyncedOriginals = $provenanceScoped
                    ->filter(fn (AdmissionPaymentHeadAllocation $alloc): bool => $alloc->status === 'Active')
                    ->values();

                if ($unsyncedOriginals->isNotEmpty()) {
                    $selected = $unsyncedOriginals;
                    $usedProvenanceFallback = true;
                    $hasStatusMismatch = true;
                }
            }
        }

        $ordered = $selected->sort(function (AdmissionPaymentHeadAllocation $a, AdmissionPaymentHeadAllocation $b) use ($sortOrderByInvoiceHeadId): int {
            $invHeadA = (int) $a->admission_invoice_head_allocation_id;
            $invHeadB = (int) $b->admission_invoice_head_allocation_id;

            $orderA = $sortOrderByInvoiceHeadId[$invHeadA] ?? PHP_INT_MAX;
            $orderB = $sortOrderByInvoiceHeadId[$invHeadB] ?? PHP_INT_MAX;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            if ($invHeadA !== $invHeadB) {
                return $invHeadA <=> $invHeadB;
            }

            return (int) $a->id <=> (int) $b->id;
        })->values();

        return [
            'allocations' => $ordered,
            'is_payment_reversed' => $isPaymentReversed,
            'used_provenance_fallback' => $usedProvenanceFallback,
            'has_status_mismatch' => $hasStatusMismatch,
            'missing_expected_allocations' => $missingExpectedAllocations,
            'tuition_map' => $tuitionMap,
        ];
    }

    /**
     * Build head-wise breakdown rows for an Admission Invoice POS or Receipt-Scoped Slip.
     *
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     audit: array{has_status_mismatch: bool, missing_expected_allocations: bool, used_provenance_fallback: bool}
     * }
     */
    private function buildAdmissionInvoiceHeadRows(FeeInvoice $invoice, ?FeePayment $scopedPayment): array
    {
        /** @var Collection<int, AdmissionInvoiceHeadAllocation> $invoiceHeads */
        $invoiceHeads = $invoice->relationLoaded('admissionHeadAllocations')
            ? $invoice->admissionHeadAllocations
            : $invoice->admissionHeadAllocations()->with('category')->get();

        $orderedHeads = $invoiceHeads
            ->sort(fn (AdmissionInvoiceHeadAllocation $a, AdmissionInvoiceHeadAllocation $b): int => [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id])
            ->values();

        $scopedAllocByInvHeadId = collect();
        $audit = [
            'has_status_mismatch' => false,
            'missing_expected_allocations' => false,
            'used_provenance_fallback' => false,
        ];

        if ($scopedPayment) {
            $selection = $this->selectOrderedPaymentHeadAllocations($scopedPayment);
            $scopedAllocByInvHeadId = $selection['allocations']->groupBy('admission_invoice_head_allocation_id');
            $audit = [
                'has_status_mismatch' => $selection['has_status_mismatch'],
                'missing_expected_allocations' => $selection['missing_expected_allocations'],
                'used_provenance_fallback' => $selection['used_provenance_fallback'],
            ];
        }

        $rows = [];
        foreach ($orderedHeads as $head) {
            $gross = $this->toDecimal($head->amount);
            $discount = $this->toDecimal($head->discount);
            $net = $this->toDecimal($head->net_amount);
            $due = $this->toDecimal($head->due_amount);

            if ($scopedPayment) {
                $matched = $scopedAllocByInvHeadId->get($head->id, collect());
                $scopedSum = '0.00';
                foreach ($matched as $allocRow) {
                    $scopedSum = bcadd($scopedSum, $this->toDecimal($allocRow->allocated_paid_amount), 2);
                }

                $paid = $scopedPayment->isReversed() ? '0.00' : $scopedSum;
                $reversedPaid = $scopedPayment->isReversed() ? $scopedSum : '0.00';
            } else {
                $paid = $this->toDecimal($head->allocated_paid_amount);
                $reversedPaid = '0.00';
            }

            $rows[] = [
                'head_name' => $head->head_name_snapshot,
                'category_name' => $head->fee_category_name_snapshot,
                'is_tuition_head' => (bool) $head->is_tuition_head,
                'is_discountable' => (bool) $head->is_discountable,
                'gross' => $gross,
                'gross_formatted' => $this->formatAmount($gross),
                'discount' => $discount,
                'discount_formatted' => $this->formatAmount($discount),
                'net' => $net,
                'net_formatted' => $this->formatAmount($net),
                'paid' => $paid,
                'paid_formatted' => $this->formatAmount($paid),
                'reversed_paid' => $reversedPaid,
                'reversed_paid_formatted' => $this->formatAmount($reversedPaid),
                'due' => $due,
                'due_formatted' => $this->formatAmount($due),
            ];
        }

        return [
            'rows' => $rows,
            'audit' => $audit,
        ];
    }

    /**
     * Resolve authoritative payment metadata without ever exposing a Reversed payment
     * as active tender or synthesizing a fake method on a Waived invoice.
     *
     * @param  Collection<int, FeePayment>  $activePayments
     * @param  Collection<int, FeePayment>  $reversedPayments
     * @return array<string, mixed>
     */
    private function resolveSinglePaymentMeta(
        FeeInvoice $invoice,
        bool $isAdmission,
        bool $isWaived,
        ?FeePayment $scopedPayment,
        bool $scopedIsReversed,
        ?FeePayment $primaryActivePayment,
        Collection $activePayments,
        Collection $reversedPayments
    ): array {
        if ($scopedPayment && $scopedIsReversed) {
            return [
                'mode' => 'scoped_reversed',
                'method' => null,
                'trx_id' => null,
                'receipt_no' => $scopedPayment->receipt_no,
                'active_receipt_nos' => [],
                'collector_name' => $scopedPayment->collector?->name,
                'reversal_reference' => $scopedPayment->reversal_reference,
                'reversal_reason' => trim((string) ($scopedPayment->reversal_reason ?? '')) ?: 'No reason recorded',
                'reversed_by_name' => $scopedPayment->reversedBy?->name ?? ($scopedPayment->reversed_by ? 'Admin #' . $scopedPayment->reversed_by : 'Authorized Admin'),
                'reversed_at_formatted' => $scopedPayment->reversed_at
                    ? Carbon::parse($scopedPayment->reversed_at)->timezone('Asia/Dhaka')->format('d-M-Y h:i A')
                    : null,
            ];
        }

        if ($isWaived && $activePayments->isEmpty()) {
            return [
                'mode' => 'waived',
                'method' => null,
                'trx_id' => null,
                'receipt_no' => null,
                'active_receipt_nos' => [],
                'collector_name' => null,
                'settlement_text' => '100% Discount Waived (No Payment Required)',
            ];
        }

        if ($primaryActivePayment) {
            $trxId = trim((string) ($primaryActivePayment->transaction_id ?? ''));
            return [
                'mode' => 'active',
                'method' => strtoupper((string) ($primaryActivePayment->payment_method ?: 'Cash')),
                'trx_id' => $trxId !== '' ? $trxId : null,
                'receipt_no' => $primaryActivePayment->receipt_no,
                'active_receipt_nos' => $scopedPayment
                    ? [$primaryActivePayment->receipt_no]
                    : $activePayments->pluck('receipt_no')->filter()->values()->all(),
                'collector_name' => $primaryActivePayment->collector?->name,
            ];
        }

        // Preserve original Legacy unpaid behavior strictly when Legacy has zero reversals.
        if (!$isAdmission && $reversedPayments->isEmpty()) {
            return [
                'mode' => 'legacy_unpaid',
                'method' => 'CASH',
                'trx_id' => null,
                'receipt_no' => null,
                'active_receipt_nos' => [],
                'collector_name' => null,
            ];
        }

        return [
            'mode' => 'no_active_payment',
            'method' => null,
            'trx_id' => null,
            'receipt_no' => null,
            'active_receipt_nos' => [],
            'collector_name' => null,
        ];
    }

    private function resolveLegacyOrAdmissionMonth(FeeInvoice $invoice): string
    {
        if ($invoice->isAdmissionInvoice() || $invoice->isAdmissionInvoiceNumber()) {
            return 'One Time';
        }

        $monthName = 'One Time';
        if ($invoice->feeSetup) {
            if ($invoice->feeSetup->fee_month && strtolower((string) $invoice->feeSetup->fee_month) !== 'monthly') {
                $monthName = (string) $invoice->feeSetup->fee_month;
            } elseif ($invoice->due_date) {
                $monthName = date('F', strtotime((string) $invoice->due_date));
            } else {
                $monthName = date('F', strtotime((string) $invoice->created_at));
            }
        }

        return $monthName;
    }

    private function resolvePaymentSortMonthOrder(FeePayment $payment): int
    {
        $invoice = $payment->invoice;
        if (!$invoice || $invoice->isAdmissionInvoice()) {
            return 99;
        }

        $setupMonth = strtolower(trim((string) ($invoice->feeSetup->fee_month ?? '')));
        if ($setupMonth !== '' && isset(self::MONTH_ORDER_MAP[$setupMonth])) {
            return self::MONTH_ORDER_MAP[$setupMonth];
        }

        if ($invoice->invoice_no && preg_match('/^INV-\d{4}(0[1-9]|1[0-2])-/i', (string) $invoice->invoice_no, $matches)) {
            return (int) $matches[1];
        }

        $monthName = strtolower(trim((string) ($invoice->month_name ?? '')));
        if ($monthName !== '' && isset(self::MONTH_ORDER_MAP[$monthName])) {
            return self::MONTH_ORDER_MAP[$monthName];
        }

        return 99;
    }

    private function resolvePaymentSortLabel(FeePayment $payment): string
    {
        $invoice = $payment->invoice;
        if (!$invoice) {
            return '';
        }

        if ($invoice->isAdmissionInvoice()) {
            return trim((string) ($invoice->admissionFeeStructure?->structure_name ?? 'Admission Fee Package'));
        }

        return trim((string) ($invoice->feeSetup?->category?->name ?? ''));
    }

    public function toDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        $cleaned = trim(str_replace([',', ' '], '', (string) $value));
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return '0.00';
        }

        return bcadd($cleaned, '0.00', 2);
    }

    public function formatAmount(mixed $value): string
    {
        return NumberToWords::formatDecimal($this->toDecimal($value));
    }
}
