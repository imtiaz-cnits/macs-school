<?php

namespace App\Services;

use App\Helpers\NumberToWords;
use App\Models\AdmissionFeeStructureItem;
use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\Classes;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Authoritative Multi-Mode Admission Fee Reporting & Financial Reconciliation Service (Subphase 4C).
 *
 * Guarantees:
 * - Pure BCMath decimal arithmetic (`bcadd`, `bcsub`, `bccomp`, `bcmul`, `bcdiv`) with zero float math.
 * - Strict active ledger isolation: counts ONLY `FeePayment.status === 'Active'` AND explicit
 *   `AdmissionPaymentHeadAllocation.status === 'Active'` (NULL, missing, Reversed, or other statuses
 *   are never treated as Active).
 * - Historical Admission Class attribution via `admission_fee_structures.class_id` (unaffected by later promotion).
 * - Distinct Option A Date Semantics:
 *     - Selected invoices filtered by `COALESCE(fee_invoices.due_date, DATE(fee_invoices.created_at))`
 *       drive Gross, Discount, Net Receivable, Cumulative Collected, and Due (`Net Receivable - Collected`).
 *     - `period_collected` independently sums Active cash collected by `FeePayment.payment_date` within range.
 * - Cancelled Admission Invoices are excluded from active totals and isolated in `cancelled_audit`.
 */
class AdmissionFeeReportService
{
    public const MODE_BRANCH = 'branch';
    public const MODE_CLASS = 'class';
    public const MODE_STUDENT = 'student';
    public const MODE_HEAD = 'head';

    public const VALID_MODES = [
        self::MODE_BRANCH,
        self::MODE_CLASS,
        self::MODE_STUDENT,
        self::MODE_HEAD,
    ];

    public const METRIC_GROSS = 'gross';
    public const METRIC_DISCOUNT = 'discount';
    public const METRIC_NET_RECEIVABLE = 'net_receivable';
    public const METRIC_COLLECTED = 'collected';
    public const METRIC_DUE = 'due';
    public const METRIC_PERIOD_COLLECTED = 'period_collected';

    public const VALID_MATRIX_METRICS = [
        self::METRIC_GROSS,
        self::METRIC_DISCOUNT,
        self::METRIC_NET_RECEIVABLE,
        self::METRIC_COLLECTED,
        self::METRIC_DUE,
        self::METRIC_PERIOD_COLLECTED,
    ];

    public const MATRIX_METRIC_LABELS = [
        self::METRIC_GROSS => 'Gross Billed',
        self::METRIC_DISCOUNT => 'Discount',
        self::METRIC_NET_RECEIVABLE => 'Net Receivable',
        self::METRIC_COLLECTED => 'Collected (Cumul.)',
        self::METRIC_DUE => 'Due',
        self::METRIC_PERIOD_COLLECTED => 'Period Collected',
    ];

    public function __construct(
        protected FeeReceiptService $receiptService
    ) {
    }

    public function admissionTablesAvailable(): bool
    {
        return $this->receiptService->admissionTablesAvailable();
    }

    public function toDecimal(mixed $value): string
    {
        return $this->receiptService->toDecimal($value);
    }

    public function clampNonNegative(string $value): string
    {
        $normalized = $this->toDecimal($value);
        return bccomp($normalized, '0.00', 2) < 0 ? '0.00' : $normalized;
    }

    public function percentage(string $numerator, string $denominator, int $precision = 1): string
    {
        $num = $this->toDecimal($numerator);
        $den = $this->toDecimal($denominator);

        if (bccomp($den, '0.00', 2) <= 0 || bccomp($num, '0.00', 2) <= 0) {
            return bcadd('0', '0', $precision);
        }

        $raw = bcdiv(bcmul($num, '100', $precision + 4), $den, $precision + 2);
        $halfStep = '0.' . str_repeat('0', $precision) . '5';
        $rounded = bcadd($raw, $halfStep, $precision);

        if (bccomp($rounded, '100', $precision) > 0) {
            return bcadd('100', '0', $precision);
        }

        return $rounded;
    }

    /**
     * Authoritative per-head and invoice-level active breakdown for a single Admission Invoice.
     * Reuses FeeReceiptService strict explicit `status === 'Active'` allocation selection.
     *
     * @return array{
     *     gross: string,
     *     discount: string,
     *     net: string,
     *     collected: string,
     *     due: string,
     *     heads: array<int, array<string, mixed>>
     * }
     */
    public function summarizeAdmissionInvoiceActiveHeads(
        FeeInvoice $invoice,
        ?int $feeCategoryId = null,
        string $headName = ''
    ): array {
        $invoiceHeads = $invoice->relationLoaded('admissionHeadAllocations')
            ? $invoice->admissionHeadAllocations
            : $invoice->admissionHeadAllocations()->with('category')->get();

        $payments = $invoice->relationLoaded('payments')
            ? $invoice->payments
            : $invoice->payments()->where('status', 'Active')->with('admissionHeadAllocations')->get();

        $collectedByHeadId = [];
        foreach ($payments as $payment) {
            // Strict explicit payment status check: NULL, Reversed, or invalid payments never contribute.
            if ($payment->status !== 'Active') {
                continue;
            }

            $payment->setRelation('invoice', $invoice);
            $selection = $this->receiptService->selectOrderedPaymentHeadAllocations($payment);

            foreach ($selection['allocations'] as $alloc) {
                if ($alloc->status !== 'Active') {
                    continue;
                }
                $invHeadId = (int) $alloc->admission_invoice_head_allocation_id;
                $current = $collectedByHeadId[$invHeadId] ?? '0.00';
                $collectedByHeadId[$invHeadId] = bcadd($current, $this->toDecimal($alloc->allocated_paid_amount), 2);
            }
        }

        $orderedHeads = $invoiceHeads->sort(
            fn (AdmissionInvoiceHeadAllocation $a, AdmissionInvoiceHeadAllocation $b): int => [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id]
        )->values();

        $headNameTrimmed = trim($headName);
        $heads = [];
        $invGross = '0.00';
        $invDiscount = '0.00';
        $invNet = '0.00';
        $invCollected = '0.00';
        $invDue = '0.00';

        foreach ($orderedHeads as $head) {
            if ($feeCategoryId && (int) $head->fee_category_id !== $feeCategoryId) {
                continue;
            }
            if ($headNameTrimmed !== '' && strcasecmp(trim((string) $head->head_name_snapshot), $headNameTrimmed) !== 0) {
                continue;
            }

            $gross = $this->toDecimal($head->amount);
            $discount = $this->toDecimal($head->discount);
            $net = $this->toDecimal($head->net_amount);
            $collected = $collectedByHeadId[(int) $head->id] ?? '0.00';
            $due = $this->clampNonNegative(bcsub($net, $collected, 2));

            $invGross = bcadd($invGross, $gross, 2);
            $invDiscount = bcadd($invDiscount, $discount, 2);
            $invNet = bcadd($invNet, $net, 2);
            $invCollected = bcadd($invCollected, $collected, 2);
            $invDue = bcadd($invDue, $due, 2);

            $heads[(int) $head->id] = [
                'invoice_head_id' => (int) $head->id,
                'fee_category_id' => (int) $head->fee_category_id,
                'category_name' => (string) ($head->fee_category_name_snapshot ?: ($head->category?->name ?? 'Admission Fee')),
                'head_name' => (string) $head->head_name_snapshot,
                'is_tuition_head' => (bool) $head->is_tuition_head,
                'sort_order' => (int) $head->sort_order,
                'gross' => $gross,
                'discount' => $discount,
                'net' => $net,
                'collected' => $collected,
                'due' => $due,
            ];
        }

        // Enforce Net Receivable - Collected = Due and never expose negative Due at invoice summary level.
        $reconciledDue = $this->clampNonNegative(bcsub($invNet, $invCollected, 2));
        if (bccomp($invDue, $reconciledDue, 2) !== 0 && count($heads) === 0) {
            $invDue = $reconciledDue;
        }

        return [
            'gross' => $invGross,
            'discount' => $invDiscount,
            'net' => $invNet,
            'collected' => $invCollected,
            'due' => $invDue,
            'heads' => $heads,
        ];
    }

    /**
     * Extract explicit Active category breakdown for a single FeePayment (used across Financial & Fee reports).
     * Non-Active payments or Admission allocations without explicit `status === 'Active'` return '0.00'.
     *
     * @return array{
     *     is_admission: bool,
     *     effective_total: string,
     *     by_category: array<string, string>,
     *     head_details: array<int, array{head_name: string, category_name: string, amount: string, is_tuition_head: bool}>
     * }
     */
    public function aggregateActivePaymentCategoryAmounts(
        FeePayment $payment,
        ?int $feeCategoryId = null,
        string $headName = ''
    ): array {
        $invoice = $payment->invoice;
        $isAdmission = $invoice && $invoice->isAdmissionInvoice();

        if ($payment->status !== 'Active') {
            return [
                'is_admission' => $isAdmission,
                'effective_total' => '0.00',
                'by_category' => [],
                'head_details' => [],
            ];
        }

        if (!$isAdmission) {
            $amount = $this->toDecimal($payment->paid_amount);
            $fineAmt = $payment->fineAmountDecimal();
            $catName = (string) ($invoice?->feeSetup?->category?->name ?? 'General Fee');

            if (bccomp($fineAmt, '0.00', 2) > 0) {
                $baseAmt = $this->clampNonNegative(bcsub($amount, $fineAmt, 2));
                $byCategory = [];
                if (bccomp($baseAmt, '0.00', 2) > 0) {
                    $byCategory[$catName] = $baseAmt;
                }
                $byCategory['Fine'] = bcadd($byCategory['Fine'] ?? '0.00', $fineAmt, 2);

                return [
                    'is_admission' => false,
                    'effective_total' => $amount,
                    'by_category' => $byCategory,
                    'head_details' => [],
                ];
            }

            return [
                'is_admission' => false,
                'effective_total' => $amount,
                'by_category' => [$catName => $amount],
                'head_details' => [],
            ];
        }

        $selection = $this->receiptService->selectOrderedPaymentHeadAllocations($payment);
        $headNameTrimmed = trim($headName);
        $byCategory = [];
        $headDetails = [];
        $effectiveTotal = '0.00';

        foreach ($selection['allocations'] as $alloc) {
            if ($alloc->status !== 'Active') {
                continue;
            }
            if ($feeCategoryId && (int) $alloc->fee_category_id !== $feeCategoryId) {
                continue;
            }
            if ($headNameTrimmed !== '' && strcasecmp(trim((string) $alloc->head_name_snapshot), $headNameTrimmed) !== 0) {
                continue;
            }

            $catName = (string) ($alloc->fee_category_name_snapshot ?: 'Admission Fee');
            $allocAmt = $this->toDecimal($alloc->allocated_paid_amount);
            $invHeadId = (int) $alloc->admission_invoice_head_allocation_id;

            $byCategory[$catName] = bcadd($byCategory[$catName] ?? '0.00', $allocAmt, 2);
            $effectiveTotal = bcadd($effectiveTotal, $allocAmt, 2);

            $headDetails[] = [
                'invoice_head_id' => $invHeadId,
                'fee_category_id' => (int) $alloc->fee_category_id,
                'head_name' => (string) $alloc->head_name_snapshot,
                'category_name' => $catName,
                'amount' => $allocAmt,
                'is_tuition_head' => (bool) ($selection['tuition_map'][$invHeadId] ?? false),
            ];
        }

        return [
            'is_admission' => true,
            'effective_total' => $effectiveTotal,
            'by_category' => $byCategory,
            'head_details' => $headDetails,
        ];
    }

    /**
     * Generate complete reconciled Admission Fee Report dataset for Branch, Class, Student, or Fee Head mode.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generateReport(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $mode = $normalized['mode'];

        $modeLabels = [
            self::MODE_BRANCH => 'Branch-wise Admission Fee Report',
            self::MODE_CLASS => 'Class-wise Admission Fee Report',
            self::MODE_STUDENT => 'Student-wise Admission Fee Report',
            self::MODE_HEAD => 'Fee Head-wise Admission Fee Report',
        ];

        $classOrderMap = [];
        $classesById = Classes::all();
        foreach ($classesById as $index => $cls) {
            $classOrderMap[(int) $cls->id] = [
                'order' => $index + 1,
                'name' => (string) $cls->class_name,
            ];
        }

        if (!$this->admissionTablesAvailable()) {
            return $this->emptyReportPayload($mode, $modeLabels[$mode], $normalized);
        }

        // ---------------------------------------------------------------------
        // 1. Query Selected Admission Invoices (Filtered by Invoice Date COALESCE)
        // ---------------------------------------------------------------------
        $invoiceQuery = $this->baseAdmissionInvoiceQuery($normalized, true);
        $rawInvoices = $invoiceQuery->get();

        $activeInvoiceEntries = [];
        $cancelledEntries = [];
        $cancelledGross = '0.00';
        $cancelledDiscount = '0.00';
        $cancelledNet = '0.00';

        $invTotalGross = '0.00';
        $invTotalDiscount = '0.00';
        $invTotalNet = '0.00';
        $invTotalCollected = '0.00';
        $invTotalDue = '0.00';

        $headTotalGross = '0.00';
        $headTotalDiscount = '0.00';
        $headTotalNet = '0.00';
        $headTotalCollected = '0.00';
        $headTotalDue = '0.00';

        $activeStudentIds = [];

        foreach ($rawInvoices as $invoice) {
            $headSummary = $this->summarizeAdmissionInvoiceActiveHeads(
                $invoice,
                $normalized['fee_category_id'],
                $normalized['head_name']
            );

            // If user filtered by fee_category_id or head_name and this invoice has zero matching heads, skip it.
            if (empty($headSummary['heads']) && ($normalized['fee_category_id'] || $normalized['head_name'] !== '')) {
                continue;
            }

            // Rule C.6 & C.7: Cancelled invoices ALWAYS go strictly to separate Cancelled Audit Summary.
            if ($invoice->isCancelled()) {
                $cGross = !empty($headSummary['heads']) ? $headSummary['gross'] : $this->toDecimal($invoice->amount);
                $cDisc = !empty($headSummary['heads']) ? $headSummary['discount'] : $this->toDecimal($invoice->discount);
                $cNet = !empty($headSummary['heads']) ? $headSummary['net'] : $this->toDecimal($invoice->net_amount);

                $cancelledGross = bcadd($cancelledGross, $cGross, 2);
                $cancelledDiscount = bcadd($cancelledDiscount, $cDisc, 2);
                $cancelledNet = bcadd($cancelledNet, $cNet, 2);

                $structureClassId = (int) ($invoice->admissionFeeStructure?->class_id ?? 0);
                $cancelledEntries[] = [
                    'invoice_id' => (int) $invoice->id,
                    'invoice_no' => (string) $invoice->invoice_no,
                    'student_id' => (int) $invoice->student_id,
                    'student_name' => (string) ($invoice->student?->student_name ?? 'N/A'),
                    'student_identity' => (string) ($invoice->student?->student_identity ?? 'N/A'),
                    'branch_id' => (int) $invoice->branch_id,
                    'branch_name' => (string) ($invoice->branch_name_snapshot ?: ($invoice->branch?->branch_name ?? 'N/A')),
                    'admission_class_id' => $structureClassId,
                    'admission_class_name' => (string) ($classOrderMap[$structureClassId]['name'] ?? ($invoice->admissionFeeStructure?->schoolClass?->class_name ?? 'N/A')),
                    'invoice_date' => $this->resolveInvoiceDateString($invoice),
                    'gross' => $cGross,
                    'discount' => $cDisc,
                    'net_amount' => $cNet,
                    'gross_formatted' => $this->formatAmount($cGross),
                    'discount_formatted' => $this->formatAmount($cDisc),
                    'net_amount_formatted' => $this->formatAmount($cNet),
                ];
                continue;
            }

            // If user explicitly requested only Cancelled invoices, exclude non-cancelled invoices from active list.
            if ($normalized['invoice_status'] === 'Cancelled') {
                continue;
            }

            $displayStatus = $this->resolveActiveDisplayStatus($invoice, $headSummary['collected'], $headSummary['due']);

            if (!$this->matchesStatusFilters($invoice, $displayStatus, $normalized['invoice_status'], $normalized['payment_status'])) {
                continue;
            }

            $activeStudentIds[(int) $invoice->student_id] = true;

            $invTotalGross = bcadd($invTotalGross, $headSummary['gross'], 2);
            $invTotalDiscount = bcadd($invTotalDiscount, $headSummary['discount'], 2);
            $invTotalNet = bcadd($invTotalNet, $headSummary['net'], 2);
            $invTotalCollected = bcadd($invTotalCollected, $headSummary['collected'], 2);
            $invTotalDue = bcadd($invTotalDue, $headSummary['due'], 2);

            foreach ($headSummary['heads'] as $headRow) {
                $headTotalGross = bcadd($headTotalGross, $headRow['gross'], 2);
                $headTotalDiscount = bcadd($headTotalDiscount, $headRow['discount'], 2);
                $headTotalNet = bcadd($headTotalNet, $headRow['net'], 2);
                $headTotalCollected = bcadd($headTotalCollected, $headRow['collected'], 2);
                $headTotalDue = bcadd($headTotalDue, $headRow['due'], 2);
            }

            $activeInvoiceEntries[(int) $invoice->id] = [
                'invoice' => $invoice,
                'head_summary' => $headSummary,
                'display_status' => $displayStatus,
            ];
        }

        // ---------------------------------------------------------------------
        // 2. Query Active Cash Collections by FeePayment.payment_date (period_collected)
        // ---------------------------------------------------------------------
        $periodData = $this->computePeriodCollectedBreakdown($normalized, $classOrderMap);

        // ---------------------------------------------------------------------
        // 3. Build Requested Mode Rows
        // ---------------------------------------------------------------------
        $rows = match ($mode) {
            self::MODE_CLASS => $this->buildClassRows($activeInvoiceEntries, $periodData, $classOrderMap),
            self::MODE_STUDENT => $this->buildStudentRows($activeInvoiceEntries, $periodData, $classOrderMap),
            self::MODE_HEAD => $this->buildHeadRows($activeInvoiceEntries, $periodData),
            default => $this->buildBranchRows($activeInvoiceEntries, $periodData),
        };

        $rowsTotalNet = '0.00';
        $rowsTotalCollected = '0.00';
        $rowsTotalDue = '0.00';
        $rowsTotalPeriodCollected = '0.00';

        foreach ($rows as $row) {
            $rowsTotalNet = bcadd($rowsTotalNet, $row['net_receivable'], 2);
            $rowsTotalCollected = bcadd($rowsTotalCollected, $row['collected'], 2);
            $rowsTotalDue = bcadd($rowsTotalDue, $row['due'], 2);
            $rowsTotalPeriodCollected = bcadd($rowsTotalPeriodCollected, $row['period_collected'], 2);
        }

        $grossDiff = bcsub($invTotalGross, $headTotalGross, 2);
        $discountDiff = bcsub($invTotalDiscount, $headTotalDiscount, 2);
        $netDiff = bcsub($invTotalNet, $headTotalNet, 2);
        $collectedDiff = bcsub($invTotalCollected, $headTotalCollected, 2);
        $dueDiff = bcsub($invTotalDue, $headTotalDue, 2);
        $periodDiff = bcsub($periodData['invoice_total'], $periodData['head_total'], 2);

        $netMinusCollected = bcsub($invTotalNet, $invTotalCollected, 2);
        $netMinusCollectedEqualsDue = bccomp(
            bccomp($netMinusCollected, '0.00', 2) < 0 ? '0.00' : $netMinusCollected,
            $invTotalDue,
            2
        ) === 0;

        $classMatrix = $mode === self::MODE_CLASS
            ? $this->buildClassHeadMatrix($activeInvoiceEntries, $periodData, $classOrderMap, $normalized)
            : self::emptyClassMatrixPayload($normalized['matrix_metric']);

        $isReconciled = bccomp($grossDiff, '0.00', 2) === 0
            && bccomp($discountDiff, '0.00', 2) === 0
            && bccomp($netDiff, '0.00', 2) === 0
            && bccomp($collectedDiff, '0.00', 2) === 0
            && bccomp($dueDiff, '0.00', 2) === 0
            && bccomp($periodDiff, '0.00', 2) === 0
            && bccomp(bcsub($invTotalNet, $rowsTotalNet, 2), '0.00', 2) === 0
            && bccomp(bcsub($invTotalCollected, $rowsTotalCollected, 2), '0.00', 2) === 0
            && bccomp(bcsub($invTotalDue, $rowsTotalDue, 2), '0.00', 2) === 0
            && bccomp(bcsub($periodData['invoice_total'], $rowsTotalPeriodCollected, 2), '0.00', 2) === 0
            && $netMinusCollectedEqualsDue
            && ($mode !== self::MODE_CLASS || (bool) ($classMatrix['is_reconciled'] ?? true));

        return [
            'mode' => $mode,
            'mode_label' => $modeLabels[$mode],
            'tables_available' => true,
            'filters' => $normalized,
            'summary' => [
                'active_invoice_count' => count($activeInvoiceEntries),
                'active_student_count' => count($activeStudentIds),
                'gross' => $invTotalGross,
                'gross_formatted' => $this->formatAmount($invTotalGross),
                'discount' => $invTotalDiscount,
                'discount_formatted' => $this->formatAmount($invTotalDiscount),
                'net_receivable' => $invTotalNet,
                'net_receivable_formatted' => $this->formatAmount($invTotalNet),
                'collected' => $invTotalCollected,
                'collected_formatted' => $this->formatAmount($invTotalCollected),
                'due' => $invTotalDue,
                'due_formatted' => $this->formatAmount($invTotalDue),
                'period_collected' => $periodData['invoice_total'],
                'period_collected_formatted' => $this->formatAmount($periodData['invoice_total']),
                'collection_rate' => $this->percentage($invTotalCollected, $invTotalNet),
            ],
            'cancelled_audit' => [
                'count' => count($cancelledEntries),
                'gross_amount' => $cancelledGross,
                'gross_amount_formatted' => $this->formatAmount($cancelledGross),
                'discount_amount' => $cancelledDiscount,
                'discount_amount_formatted' => $this->formatAmount($cancelledDiscount),
                'net_amount' => $cancelledNet,
                'net_amount_formatted' => $this->formatAmount($cancelledNet),
                'invoices' => $cancelledEntries,
            ],
            'reconciliation' => [
                'invoice_sum_gross' => $invTotalGross,
                'head_sum_gross' => $headTotalGross,
                'gross_diff' => $grossDiff,
                'invoice_sum_discount' => $invTotalDiscount,
                'head_sum_discount' => $headTotalDiscount,
                'discount_diff' => $discountDiff,
                'invoice_sum_net' => $invTotalNet,
                'head_sum_net' => $headTotalNet,
                'net_diff' => $netDiff,
                'invoice_sum_collected' => $invTotalCollected,
                'head_sum_collected' => $headTotalCollected,
                'collected_diff' => $collectedDiff,
                'invoice_sum_due' => $invTotalDue,
                'head_sum_due' => $headTotalDue,
                'due_diff' => $dueDiff,
                'invoice_sum_period_collected' => $periodData['invoice_total'],
                'head_sum_period_collected' => $periodData['head_total'],
                'period_collected_diff' => $periodDiff,
                'rows_sum_net' => $rowsTotalNet,
                'rows_sum_collected' => $rowsTotalCollected,
                'rows_sum_due' => $rowsTotalDue,
                'rows_sum_period_collected' => $rowsTotalPeriodCollected,
                'net_minus_collected_equals_due' => $netMinusCollectedEqualsDue,
                'is_reconciled' => $isReconciled,
            ],
            'rows' => $rows,
            'class_matrix' => $classMatrix,
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    /**
     * Build Excel matrix directly from `generateReport()` output using numeric BCMath decimal strings
     * (SimpleXLSXGen handles `^\d+\.\d\d?$` cleanly as Excel N_DEC without PHP float arithmetic).
     *
     * @param  array<string, mixed>  $reportData
     * @return array<int, array<int, string|int>>
     */
    public function buildExcelRows(array $reportData): array
    {
        $summary = $reportData['summary'];
        $audit = $reportData['cancelled_audit'];
        $filters = $reportData['filters'];
        $mode = $reportData['mode'];

        $rows = [];
        $rows[] = ['MACS SCHOOL & COLLEGE'];
        $rows[] = [$reportData['mode_label'] ?? 'Admission Fee Report'];
        $rows[] = [
            'Invoice Date From:', $filters['date_from'] ?: 'All Dates',
            'Invoice Date To:', $filters['date_to'] ?: 'All Dates',
            'Generated At:', $reportData['generated_at'] ?? now()->format('d M Y, h:i A'),
        ];
        $rows[] = [
            'Cancelled Audit Count:', $audit['count'] ?? 0,
            'Cancelled Historical Net:', $audit['net_amount'] ?? '0.00',
        ];
        $rows[] = [];

        if ($mode === self::MODE_BRANCH) {
            $rows[] = ['Branch', 'Invoices', 'Students', 'Gross', 'Discount', 'Net Receivable', 'Collected (Cumulative)', 'Due', 'Period Collected', 'Collection %'];
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $r['branch_name'],
                    $r['invoice_count'],
                    $r['student_count'],
                    $r['gross'],
                    $r['discount'],
                    $r['net_receivable'],
                    $r['collected'],
                    $r['due'],
                    $r['period_collected'],
                    $r['collection_rate'] . '%',
                ];
            }
            $rows[] = [
                'Grand Total',
                $summary['active_invoice_count'],
                $summary['active_student_count'],
                $summary['gross'],
                $summary['discount'],
                $summary['net_receivable'],
                $summary['collected'],
                $summary['due'],
                $summary['period_collected'],
                $summary['collection_rate'] . '%',
            ];
        } elseif ($mode === self::MODE_CLASS) {
            $classView = (string) ($filters['class_view'] ?? 'matrix');
            $matrix = $reportData['class_matrix'] ?? [];
            $headColumns = $matrix['head_columns'] ?? [];

            if ($classView !== 'summary' && !empty($headColumns)) {
                $metric = (string) ($matrix['metric'] ?? self::METRIC_NET_RECEIVABLE);
                $metricLabel = (string) ($matrix['metric_label'] ?? (self::MATRIX_METRIC_LABELS[$metric] ?? 'Net Receivable'));

                $rows[] = ['Class Matrix Metric:', $metricLabel];

                $headerRow = ['Class', 'Invoices', 'Students'];
                foreach ($headColumns as $col) {
                    $headerRow[] = $col['head_name'];
                }
                $headerRow = array_merge($headerRow, ['Gross', 'Discount', 'Net Receivable', 'Collected (Cumulative)', 'Due', 'Period Collected']);
                $rows[] = $headerRow;

                foreach (($matrix['rows'] ?? []) as $mRow) {
                    $line = [
                        $mRow['class_name'],
                        $mRow['invoice_count'],
                        $mRow['student_count'],
                    ];
                    foreach ($headColumns as $col) {
                        $hKey = $col['head_key'];
                        $line[] = $mRow['cells'][$hKey][$metric] ?? '0.00';
                    }
                    $line[] = $mRow['gross'];
                    $line[] = $mRow['discount'];
                    $line[] = $mRow['net_receivable'];
                    $line[] = $mRow['collected'];
                    $line[] = $mRow['due'];
                    $line[] = $mRow['period_collected'];
                    $rows[] = $line;
                }

                $colTotals = $matrix['column_totals'] ?? [];
                $totalLine = [
                    'Grand Total',
                    $summary['active_invoice_count'],
                    $summary['active_student_count'],
                ];
                foreach ($headColumns as $col) {
                    $hKey = $col['head_key'];
                    $totalLine[] = $colTotals[$hKey][$metric] ?? '0.00';
                }
                $totalLine[] = $summary['gross'];
                $totalLine[] = $summary['discount'];
                $totalLine[] = $summary['net_receivable'];
                $totalLine[] = $summary['collected'];
                $totalLine[] = $summary['due'];
                $totalLine[] = $summary['period_collected'];
                $rows[] = $totalLine;
            } else {
                $rows[] = ['Admission Class', 'Invoices', 'Students', 'Gross', 'Discount', 'Net Receivable', 'Collected (Cumulative)', 'Due', 'Period Collected', 'Collection %'];
                foreach ($reportData['rows'] as $r) {
                    $rows[] = [
                        $r['class_name'],
                        $r['invoice_count'],
                        $r['student_count'],
                        $r['gross'],
                        $r['discount'],
                        $r['net_receivable'],
                        $r['collected'],
                        $r['due'],
                        $r['period_collected'],
                        $r['collection_rate'] . '%',
                    ];
                }
                $rows[] = [
                    'Grand Total',
                    $summary['active_invoice_count'],
                    $summary['active_student_count'],
                    $summary['gross'],
                    $summary['discount'],
                    $summary['net_receivable'],
                    $summary['collected'],
                    $summary['due'],
                    $summary['period_collected'],
                    $summary['collection_rate'] . '%',
                ];
            }
        } elseif ($mode === self::MODE_HEAD) {
            $rows[] = ['Fee Category', 'Fee Head', 'Tuition Head', 'Invoices', 'Gross', 'Discount', 'Net Receivable', 'Collected (Cumulative)', 'Due', 'Period Collected', 'Collection %'];
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $r['category_name'],
                    $r['head_name'],
                    $r['is_tuition_head'] ? 'Yes' : 'No',
                    $r['invoice_count'],
                    $r['gross'],
                    $r['discount'],
                    $r['net_receivable'],
                    $r['collected'],
                    $r['due'],
                    $r['period_collected'],
                    $r['collection_rate'] . '%',
                ];
            }
            $rows[] = [
                'Grand Total', '', '',
                $summary['active_invoice_count'],
                $summary['gross'],
                $summary['discount'],
                $summary['net_receivable'],
                $summary['collected'],
                $summary['due'],
                $summary['period_collected'],
                $summary['collection_rate'] . '%',
            ];
        } else {
            $rows[] = ['Invoice No', 'Student ID', 'Student Name', 'Admission Class', 'Branch', 'Invoice Date', 'Status', 'Heads', 'Gross', 'Discount', 'Net Receivable', 'Collected (Cumulative)', 'Due', 'Period Collected'];
            foreach ($reportData['rows'] as $r) {
                $rows[] = [
                    $r['invoice_no'],
                    $r['student_identity'],
                    $r['student_name'],
                    $r['admission_class_name'],
                    $r['branch_name'],
                    $r['invoice_date'] ?? 'N/A',
                    $r['display_status'],
                    $r['head_count'],
                    $r['gross'],
                    $r['discount'],
                    $r['net_receivable'],
                    $r['collected'],
                    $r['due'],
                    $r['period_collected'],
                ];
            }
            $rows[] = [
                'Grand Total', '', '', '', '', '', '',
                $summary['active_invoice_count'],
                $summary['gross'],
                $summary['discount'],
                $summary['net_receivable'],
                $summary['collected'],
                $summary['due'],
                $summary['period_collected'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $rawMode = strtolower(trim((string) ($filters['mode'] ?? self::MODE_BRANCH)));
        $mode = in_array($rawMode, self::VALID_MODES, true) ? $rawMode : self::MODE_BRANCH;

        $rawMetric = strtolower(trim((string) ($filters['matrix_metric'] ?? self::METRIC_NET_RECEIVABLE)));
        $matrixMetric = in_array($rawMetric, self::VALID_MATRIX_METRICS, true) ? $rawMetric : self::METRIC_NET_RECEIVABLE;

        $rawClassView = strtolower(trim((string) ($filters['class_view'] ?? 'matrix')));
        $classView = in_array($rawClassView, ['matrix', 'summary'], true) ? $rawClassView : 'matrix';

        return [
            'mode' => $mode,
            'matrix_metric' => $matrixMetric,
            'class_view' => $classView,
            'branch_id' => !empty($filters['branch_id']) ? (int) $filters['branch_id'] : null,
            'session_year_id' => !empty($filters['session_year_id']) ? (int) $filters['session_year_id'] : null,
            'class_id' => !empty($filters['class_id']) ? (int) $filters['class_id'] : null,
            'fee_category_id' => !empty($filters['fee_category_id']) ? (int) $filters['fee_category_id'] : null,
            'head_name' => trim((string) ($filters['head_name'] ?? '')),
            'invoice_status' => !empty($filters['invoice_status']) ? trim((string) $filters['invoice_status']) : null,
            'payment_status' => !empty($filters['payment_status']) ? trim((string) $filters['payment_status']) : null,
            'date_from' => !empty($filters['date_from']) ? trim((string) $filters['date_from']) : null,
            'date_to' => !empty($filters['date_to']) ? trim((string) $filters['date_to']) : null,
            'search' => trim((string) ($filters['search'] ?? '')),
        ];
    }

    private function baseAdmissionInvoiceQuery(array $normalized, bool $applyInvoiceDates)
    {
        $query = FeeInvoice::query()
            ->with([
                'student.schoolClass',
                'branch',
                'sessionYear',
                'admissionFeeStructure.schoolClass',
                'admissionHeadAllocations.category',
                'payments' => fn ($pq) => $pq->where('status', 'Active')->with(['admissionHeadAllocations']),
            ])
            ->whereNotNull('fee_invoices.admission_fee_structure_id');

        if ($normalized['branch_id']) {
            $query->where('fee_invoices.branch_id', $normalized['branch_id']);
        }

        if ($normalized['session_year_id']) {
            $query->where('fee_invoices.session_year_id', $normalized['session_year_id']);
        }

        // Strict Historical Class Filter: Admission invoices belong to `admission_fee_structures.class_id`.
        if ($normalized['class_id']) {
            $classId = $normalized['class_id'];
            $query->whereHas('admissionFeeStructure', function ($sq) use ($classId): void {
                $sq->where('class_id', $classId);
            });
        }

        if ($normalized['search'] !== '') {
            $search = $normalized['search'];
            $query->where(function ($sq) use ($search): void {
                $sq->where('fee_invoices.invoice_no', 'like', "%{$search}%")
                   ->orWhereHas('student', function ($stq) use ($search): void {
                       $stq->where('student_name', 'like', "%{$search}%")
                           ->orWhere('student_identity', 'like', "%{$search}%");
                   })
                   ->orWhereHas('admissionFeeStructure', function ($afq) use ($search): void {
                       $afq->where('structure_name', 'like', "%{$search}%");
                   });
            });
        }

        // Option A Invoice Date Range Expression: COALESCE(due_date, DATE(created_at))
        if ($applyInvoiceDates) {
            if ($normalized['date_from']) {
                $query->whereRaw('COALESCE(fee_invoices.due_date, DATE(fee_invoices.created_at)) >= ?', [$normalized['date_from']]);
            }
            if ($normalized['date_to']) {
                $query->whereRaw('COALESCE(fee_invoices.due_date, DATE(fee_invoices.created_at)) <= ?', [$normalized['date_to']]);
            }
        }

        return $query->orderBy('fee_invoices.id', 'desc');
    }

    /**
     * Independently calculate `period_collected` from strictly Active FeePayment records
     * within `FeePayment.payment_date` range, reconciled across Branch, Class, Student/Invoice, and Head.
     *
     * @return array<string, mixed>
     */
    private function computePeriodCollectedBreakdown(array $normalized, array $classOrderMap): array
    {
        if ($normalized['invoice_status'] === 'Cancelled') {
            return [
                'invoice_total' => '0.00',
                'head_total' => '0.00',
                'by_branch' => [],
                'by_class' => [],
                'by_class_head' => [],
                'by_invoice' => [],
                'by_head' => [],
            ];
        }

        $paymentQuery = FeePayment::query()
            ->with([
                'student',
                'invoice.branch',
                'invoice.admissionFeeStructure.schoolClass',
                'invoice.admissionHeadAllocations.category',
                'invoice.payments' => fn ($pq) => $pq->where('status', 'Active')->with('admissionHeadAllocations'),
                'admissionHeadAllocations',
            ])
            ->where('fee_payments.status', 'Active')
            ->whereHas('invoice', function ($iq) use ($normalized): void {
                $iq->whereNotNull('fee_invoices.admission_fee_structure_id')
                   ->where('fee_invoices.status', '!=', 'Cancelled');

                if ($normalized['branch_id']) {
                    $iq->where('fee_invoices.branch_id', $normalized['branch_id']);
                }
                if ($normalized['session_year_id']) {
                    $iq->where('fee_invoices.session_year_id', $normalized['session_year_id']);
                }
                if ($normalized['class_id']) {
                    $classId = $normalized['class_id'];
                    $iq->whereHas('admissionFeeStructure', fn ($sq) => $sq->where('class_id', $classId));
                }
                if ($normalized['search'] !== '') {
                    $search = $normalized['search'];
                    $iq->where(function ($sq) use ($search): void {
                        $sq->where('fee_invoices.invoice_no', 'like', "%{$search}%")
                           ->orWhereHas('student', function ($stq) use ($search): void {
                               $stq->where('student_name', 'like', "%{$search}%")
                                   ->orWhere('student_identity', 'like', "%{$search}%");
                           })
                           ->orWhereHas('admissionFeeStructure', function ($afq) use ($search): void {
                               $afq->where('structure_name', 'like', "%{$search}%");
                           });
                    });
                }
            });

        if ($normalized['date_from']) {
            $paymentQuery->where('fee_payments.payment_date', '>=', $normalized['date_from']);
        }
        if ($normalized['date_to']) {
            $paymentQuery->where('fee_payments.payment_date', '<=', $normalized['date_to']);
        }

        $activePayments = $paymentQuery->get();

        $byBranch = [];
        $byClass = [];
        $byClassHead = [];
        $byInvoice = [];
        $byHead = [];
        $invoiceTotal = '0.00';
        $headTotal = '0.00';

        /** @var array<int, bool> $eligibleInvoiceCache */
        $eligibleInvoiceCache = [];

        foreach ($activePayments as $payment) {
            if ($payment->status !== 'Active') {
                continue;
            }

            $invoice = $payment->invoice;
            if (!$invoice || !$invoice->isAdmissionInvoice() || $invoice->isCancelled()) {
                continue;
            }

            $invoiceId = (int) $invoice->id;
            if (!array_key_exists($invoiceId, $eligibleInvoiceCache)) {
                if ($normalized['invoice_status'] || $normalized['payment_status']) {
                    $invHeadSummary = $this->summarizeAdmissionInvoiceActiveHeads(
                        $invoice,
                        $normalized['fee_category_id'],
                        $normalized['head_name']
                    );
                    $invDisplay = $this->resolveActiveDisplayStatus($invoice, $invHeadSummary['collected'], $invHeadSummary['due']);
                    $eligibleInvoiceCache[$invoiceId] = $this->matchesStatusFilters(
                        $invoice,
                        $invDisplay,
                        $normalized['invoice_status'],
                        $normalized['payment_status']
                    );
                } else {
                    $eligibleInvoiceCache[$invoiceId] = true;
                }
            }

            if (!$eligibleInvoiceCache[$invoiceId]) {
                continue;
            }

            $payCategories = $this->aggregateActivePaymentCategoryAmounts(
                $payment,
                $normalized['fee_category_id'],
                $normalized['head_name']
            );

            $effectivePayTotal = $payCategories['effective_total'];
            if (bccomp($effectivePayTotal, '0.00', 2) <= 0 || empty($payCategories['head_details'])) {
                continue;
            }

            $invoiceTotal = bcadd($invoiceTotal, $effectivePayTotal, 2);

            $branchId = (int) $invoice->branch_id;
            $branchName = (string) ($invoice->branch_name_snapshot ?: ($invoice->branch?->branch_name ?? 'N/A'));
            $classId = (int) ($invoice->admissionFeeStructure?->class_id ?? 0);
            $className = (string) ($classOrderMap[$classId]['name'] ?? ($invoice->admissionFeeStructure?->schoolClass?->class_name ?? 'N/A'));

            if (!isset($byBranch[$branchId])) {
                $byBranch[$branchId] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'amount' => '0.00',
                ];
            }
            $byBranch[$branchId]['amount'] = bcadd($byBranch[$branchId]['amount'], $effectivePayTotal, 2);

            if (!isset($byClass[$classId])) {
                $byClass[$classId] = [
                    'class_id' => $classId,
                    'class_name' => $className,
                    'class_order' => (int) ($classOrderMap[$classId]['order'] ?? 999),
                    'amount' => '0.00',
                ];
            }
            $byClass[$classId]['amount'] = bcadd($byClass[$classId]['amount'], $effectivePayTotal, 2);

            if (!isset($byInvoice[$invoiceId])) {
                $byInvoice[$invoiceId] = [
                    'invoice' => $invoice,
                    'amount' => '0.00',
                ];
            }
            $byInvoice[$invoiceId]['amount'] = bcadd($byInvoice[$invoiceId]['amount'], $effectivePayTotal, 2);

            foreach ($payCategories['head_details'] as $hd) {
                $headTotal = bcadd($headTotal, $hd['amount'], 2);
                $headKey = $this->makeHeadGroupKey($hd['fee_category_id'], $hd['head_name']);
                $invHeadObj = $invoice->admissionHeadAllocations->firstWhere('id', $hd['invoice_head_id']);
                $headSort = (int) ($invHeadObj?->sort_order ?? 999);

                if (!isset($byHead[$headKey])) {
                    $byHead[$headKey] = [
                        'fee_category_id' => $hd['fee_category_id'],
                        'category_name' => $hd['category_name'],
                        'head_name' => $hd['head_name'],
                        'is_tuition_head' => $hd['is_tuition_head'],
                        'sort_order' => $headSort,
                        'amount' => '0.00',
                    ];
                }
                $byHead[$headKey]['amount'] = bcadd($byHead[$headKey]['amount'], $hd['amount'], 2);

                if (!isset($byClassHead[$classId][$headKey])) {
                    $byClassHead[$classId][$headKey] = [
                        'fee_category_id' => $hd['fee_category_id'],
                        'category_name' => $hd['category_name'],
                        'head_name' => $hd['head_name'],
                        'is_tuition_head' => $hd['is_tuition_head'],
                        'sort_order' => $headSort,
                        'amount' => '0.00',
                    ];
                } else {
                    $byClassHead[$classId][$headKey]['sort_order'] = min(
                        $byClassHead[$classId][$headKey]['sort_order'],
                        $headSort
                    );
                }
                $byClassHead[$classId][$headKey]['amount'] = bcadd(
                    $byClassHead[$classId][$headKey]['amount'],
                    $hd['amount'],
                    2
                );
            }
        }

        return [
            'invoice_total' => $invoiceTotal,
            'head_total' => $headTotal,
            'by_branch' => $byBranch,
            'by_class' => $byClass,
            'by_class_head' => $byClassHead,
            'by_invoice' => $byInvoice,
            'by_head' => $byHead,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $activeInvoiceEntries
     * @param  array<string, mixed>  $periodData
     * @return array<int, array<string, mixed>>
     */
    private function buildBranchRows(array $activeInvoiceEntries, array $periodData): array
    {
        $groups = [];

        foreach ($activeInvoiceEntries as $entry) {
            /** @var FeeInvoice $invoice */
            $invoice = $entry['invoice'];
            $hs = $entry['head_summary'];
            $branchId = (int) $invoice->branch_id;
            $branchName = (string) ($invoice->branch_name_snapshot ?: ($invoice->branch?->branch_name ?? 'N/A'));

            if (!isset($groups[$branchId])) {
                $groups[$branchId] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'invoice_count' => 0,
                    'student_ids' => [],
                    'gross' => '0.00',
                    'discount' => '0.00',
                    'net_receivable' => '0.00',
                    'collected' => '0.00',
                    'due' => '0.00',
                    'period_collected' => '0.00',
                ];
            }

            $groups[$branchId]['invoice_count']++;
            $groups[$branchId]['student_ids'][(int) $invoice->student_id] = true;
            $groups[$branchId]['gross'] = bcadd($groups[$branchId]['gross'], $hs['gross'], 2);
            $groups[$branchId]['discount'] = bcadd($groups[$branchId]['discount'], $hs['discount'], 2);
            $groups[$branchId]['net_receivable'] = bcadd($groups[$branchId]['net_receivable'], $hs['net'], 2);
            $groups[$branchId]['collected'] = bcadd($groups[$branchId]['collected'], $hs['collected'], 2);
            $groups[$branchId]['due'] = bcadd($groups[$branchId]['due'], $hs['due'], 2);
        }

        foreach ($periodData['by_branch'] as $branchId => $pInfo) {
            if (!isset($groups[$branchId])) {
                $groups[$branchId] = [
                    'branch_id' => (int) $branchId,
                    'branch_name' => $pInfo['branch_name'],
                    'invoice_count' => 0,
                    'student_ids' => [],
                    'gross' => '0.00',
                    'discount' => '0.00',
                    'net_receivable' => '0.00',
                    'collected' => '0.00',
                    'due' => '0.00',
                    'period_collected' => '0.00',
                ];
            }
            $groups[$branchId]['period_collected'] = bcadd($groups[$branchId]['period_collected'], $pInfo['amount'], 2);
        }

        usort($groups, fn (array $a, array $b): int => [strtolower($a['branch_name']), $a['branch_id']] <=> [strtolower($b['branch_name']), $b['branch_id']]);

        return array_map(fn (array $g): array => $this->finalizeGroupRow([
            'branch_id' => $g['branch_id'],
            'branch_name' => $g['branch_name'],
            'invoice_count' => $g['invoice_count'],
            'student_count' => count($g['student_ids']),
            'gross' => $g['gross'],
            'discount' => $g['discount'],
            'net_receivable' => $g['net_receivable'],
            'collected' => $g['collected'],
            'due' => $g['due'],
            'period_collected' => $g['period_collected'],
        ]), $groups);
    }

    /**
     * Strictly uses `admission_fee_structures.class_id` and sorts in `Classes` global order (`Play..Ten`).
     *
     * @param  array<int, array<string, mixed>>  $activeInvoiceEntries
     * @param  array<string, mixed>  $periodData
     * @return array<int, array<string, mixed>>
     */
    private function buildClassRows(array $activeInvoiceEntries, array $periodData, array $classOrderMap): array
    {
        $groups = [];

        foreach ($activeInvoiceEntries as $entry) {
            /** @var FeeInvoice $invoice */
            $invoice = $entry['invoice'];
            $hs = $entry['head_summary'];
            $classId = (int) ($invoice->admissionFeeStructure?->class_id ?? 0);
            $className = (string) ($classOrderMap[$classId]['name'] ?? ($invoice->admissionFeeStructure?->schoolClass?->class_name ?? 'N/A'));
            $classOrder = (int) ($classOrderMap[$classId]['order'] ?? 999);

            if (!isset($groups[$classId])) {
                $groups[$classId] = [
                    'class_id' => $classId,
                    'class_name' => $className,
                    'class_order' => $classOrder,
                    'invoice_count' => 0,
                    'student_ids' => [],
                    'gross' => '0.00',
                    'discount' => '0.00',
                    'net_receivable' => '0.00',
                    'collected' => '0.00',
                    'due' => '0.00',
                    'period_collected' => '0.00',
                ];
            }

            $groups[$classId]['invoice_count']++;
            $groups[$classId]['student_ids'][(int) $invoice->student_id] = true;
            $groups[$classId]['gross'] = bcadd($groups[$classId]['gross'], $hs['gross'], 2);
            $groups[$classId]['discount'] = bcadd($groups[$classId]['discount'], $hs['discount'], 2);
            $groups[$classId]['net_receivable'] = bcadd($groups[$classId]['net_receivable'], $hs['net'], 2);
            $groups[$classId]['collected'] = bcadd($groups[$classId]['collected'], $hs['collected'], 2);
            $groups[$classId]['due'] = bcadd($groups[$classId]['due'], $hs['due'], 2);
        }

        foreach ($periodData['by_class'] as $classId => $pInfo) {
            if (!isset($groups[$classId])) {
                $groups[$classId] = [
                    'class_id' => (int) $classId,
                    'class_name' => $pInfo['class_name'],
                    'class_order' => (int) $pInfo['class_order'],
                    'invoice_count' => 0,
                    'student_ids' => [],
                    'gross' => '0.00',
                    'discount' => '0.00',
                    'net_receivable' => '0.00',
                    'collected' => '0.00',
                    'due' => '0.00',
                    'period_collected' => '0.00',
                ];
            }
            $groups[$classId]['period_collected'] = bcadd($groups[$classId]['period_collected'], $pInfo['amount'], 2);
        }

        usort($groups, fn (array $a, array $b): int => [$a['class_order'], $a['class_id']] <=> [$b['class_order'], $b['class_id']]);

        return array_map(fn (array $g): array => $this->finalizeGroupRow([
            'class_id' => $g['class_id'],
            'class_name' => $g['class_name'],
            'class_order' => $g['class_order'],
            'invoice_count' => $g['invoice_count'],
            'student_count' => count($g['student_ids']),
            'gross' => $g['gross'],
            'discount' => $g['discount'],
            'net_receivable' => $g['net_receivable'],
            'collected' => $g['collected'],
            'due' => $g['due'],
            'period_collected' => $g['period_collected'],
        ]), $groups);
    }

    /**
     * @param  array<int, array<string, mixed>>  $activeInvoiceEntries
     * @param  array<string, mixed>  $periodData
     * @return array<int, array<string, mixed>>
     */
    private function buildStudentRows(array $activeInvoiceEntries, array $periodData, array $classOrderMap): array
    {
        $rows = [];

        foreach ($activeInvoiceEntries as $invoiceId => $entry) {
            /** @var FeeInvoice $invoice */
            $invoice = $entry['invoice'];
            $hs = $entry['head_summary'];
            $periodAmt = $periodData['by_invoice'][$invoiceId]['amount'] ?? '0.00';

            $rows[$invoiceId] = $this->makeStudentRow($invoice, $hs, $entry['display_status'], $periodAmt, $classOrderMap, true);
        }

        // Include invoices that fell outside the invoice-date filter but had active cash collected during payment_date period.
        foreach ($periodData['by_invoice'] as $invoiceId => $pInfo) {
            if (isset($rows[$invoiceId])) {
                continue;
            }

            /** @var FeeInvoice $invoice */
            $invoice = $pInfo['invoice'];
            $zeroSummary = [
                'gross' => '0.00',
                'discount' => '0.00',
                'net' => '0.00',
                'collected' => '0.00',
                'due' => '0.00',
                'heads' => [],
            ];

            $rows[$invoiceId] = $this->makeStudentRow($invoice, $zeroSummary, $invoice->display_status, $pInfo['amount'], $classOrderMap, false);
        }

        return array_values($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $activeInvoiceEntries
     * @param  array<string, mixed>  $periodData
     * @return array<int, array<string, mixed>>
     */
    private function buildHeadRows(array $activeInvoiceEntries, array $periodData): array
    {
        $groups = [];

        foreach ($activeInvoiceEntries as $invoiceId => $entry) {
            foreach ($entry['head_summary']['heads'] as $head) {
                $key = $this->makeHeadGroupKey($head['fee_category_id'], $head['head_name']);

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'fee_category_id' => $head['fee_category_id'],
                        'category_name' => $head['category_name'],
                        'head_name' => $head['head_name'],
                        'is_tuition_head' => $head['is_tuition_head'],
                        'sort_order' => $head['sort_order'],
                        'invoice_ids' => [],
                        'gross' => '0.00',
                        'discount' => '0.00',
                        'net_receivable' => '0.00',
                        'collected' => '0.00',
                        'due' => '0.00',
                        'period_collected' => '0.00',
                    ];
                } else {
                    $groups[$key]['sort_order'] = min($groups[$key]['sort_order'], $head['sort_order']);
                }

                $groups[$key]['invoice_ids'][(int) $invoiceId] = true;
                $groups[$key]['gross'] = bcadd($groups[$key]['gross'], $head['gross'], 2);
                $groups[$key]['discount'] = bcadd($groups[$key]['discount'], $head['discount'], 2);
                $groups[$key]['net_receivable'] = bcadd($groups[$key]['net_receivable'], $head['net'], 2);
                $groups[$key]['collected'] = bcadd($groups[$key]['collected'], $head['collected'], 2);
                $groups[$key]['due'] = bcadd($groups[$key]['due'], $head['due'], 2);
            }
        }

        foreach ($periodData['by_head'] as $key => $pHead) {
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'fee_category_id' => $pHead['fee_category_id'],
                    'category_name' => $pHead['category_name'],
                    'head_name' => $pHead['head_name'],
                    'is_tuition_head' => $pHead['is_tuition_head'],
                    'sort_order' => $pHead['sort_order'],
                    'invoice_ids' => [],
                    'gross' => '0.00',
                    'discount' => '0.00',
                    'net_receivable' => '0.00',
                    'collected' => '0.00',
                    'due' => '0.00',
                    'period_collected' => '0.00',
                ];
            }
            $groups[$key]['period_collected'] = bcadd($groups[$key]['period_collected'], $pHead['amount'], 2);
        }

        usort($groups, fn (array $a, array $b): int => [$a['sort_order'], strtolower($a['head_name']), $a['fee_category_id']] <=> [$b['sort_order'], strtolower($b['head_name']), $b['fee_category_id']]);

        return array_map(fn (array $g): array => $this->finalizeGroupRow([
            'fee_category_id' => $g['fee_category_id'],
            'category_name' => $g['category_name'],
            'head_name' => $g['head_name'],
            'is_tuition_head' => $g['is_tuition_head'],
            'invoice_count' => count($g['invoice_ids']),
            'gross' => $g['gross'],
            'discount' => $g['discount'],
            'net_receivable' => $g['net_receivable'],
            'collected' => $g['collected'],
            'due' => $g['due'],
            'period_collected' => $g['period_collected'],
        ]), $groups);
    }

    private function makeStudentRow(
        FeeInvoice $invoice,
        array $headSummary,
        string $displayStatus,
        string $periodCollected,
        array $classOrderMap,
        bool $inInvoiceDateScope
    ): array {
        $structureClassId = (int) ($invoice->admissionFeeStructure?->class_id ?? 0);
        $structureClassName = (string) ($classOrderMap[$structureClassId]['name'] ?? ($invoice->admissionFeeStructure?->schoolClass?->class_name ?? 'N/A'));
        $activeReceiptNos = $invoice->payments
            ->filter(fn (FeePayment $p): bool => $p->status === 'Active')
            ->pluck('receipt_no')
            ->values()
            ->all();

        $formattedHeads = [];
        foreach ($headSummary['heads'] as $headRow) {
            $formattedHeads[] = array_merge($headRow, [
                'gross_formatted' => $this->formatAmount($headRow['gross']),
                'discount_formatted' => $this->formatAmount($headRow['discount']),
                'net_formatted' => $this->formatAmount($headRow['net']),
                'collected_formatted' => $this->formatAmount($headRow['collected']),
                'due_formatted' => $this->formatAmount($headRow['due']),
            ]);
        }

        return $this->finalizeGroupRow([
            'invoice_id' => (int) $invoice->id,
            'invoice_no' => (string) $invoice->invoice_no,
            'in_invoice_date_scope' => $inInvoiceDateScope,
            'student_id' => (int) $invoice->student_id,
            'student_name' => (string) ($invoice->student?->student_name ?? 'N/A'),
            'student_identity' => (string) ($invoice->student?->student_identity ?? 'N/A'),
            'structure_name' => (string) ($invoice->admissionFeeStructure?->structure_name ?? 'Admission Fee Package'),
            'branch_id' => (int) $invoice->branch_id,
            'branch_name' => (string) ($invoice->branch_name_snapshot ?: ($invoice->branch?->branch_name ?? 'N/A')),
            'admission_class_id' => $structureClassId,
            'admission_class_name' => $structureClassName,
            'current_student_class_id' => (int) ($invoice->student?->class_id ?? 0),
            'current_student_class_name' => (string) ($invoice->student?->schoolClass?->class_name ?? 'N/A'),
            'invoice_date' => $this->resolveInvoiceDateString($invoice),
            'display_status' => $displayStatus,
            'head_count' => count($formattedHeads),
            'receipt_nos' => $activeReceiptNos,
            'pos_print_url' => route('fees.invoice.pos_print', $invoice->id),
            'gross' => $headSummary['gross'],
            'discount' => $headSummary['discount'],
            'net_receivable' => $headSummary['net'],
            'collected' => $headSummary['collected'],
            'due' => $headSummary['due'],
            'period_collected' => $this->toDecimal($periodCollected),
            'heads' => $formattedHeads,
        ]);
    }

    private function finalizeGroupRow(array $row): array
    {
        $row['gross_formatted'] = $this->formatAmount($row['gross']);
        $row['discount_formatted'] = $this->formatAmount($row['discount']);
        $row['net_receivable_formatted'] = $this->formatAmount($row['net_receivable']);
        $row['collected_formatted'] = $this->formatAmount($row['collected']);
        $row['due_formatted'] = $this->formatAmount($row['due']);
        $row['period_collected_formatted'] = $this->formatAmount($row['period_collected']);
        $row['collection_rate'] = $this->percentage($row['collected'], $row['net_receivable']);

        return $row;
    }

    private function resolveActiveDisplayStatus(FeeInvoice $invoice, string $collected, string $due): string
    {
        $net = $this->toDecimal($invoice->net_amount);

        if ($invoice->isZeroPayableWaived() || bccomp($net, '0.00', 2) === 0) {
            return 'Waived';
        }
        if (bccomp($collected, '0.00', 2) === 0) {
            return 'Unpaid';
        }
        if (bccomp($due, '0.00', 2) === 0) {
            return 'Paid';
        }

        return 'Partial';
    }

    private function matchesStatusFilters(
        FeeInvoice $invoice,
        string $displayStatus,
        ?string $invoiceStatusFilter,
        ?string $paymentStatusFilter
    ): bool {
        if ($invoiceStatusFilter !== null && $invoiceStatusFilter !== '') {
            if (strcasecmp($displayStatus, $invoiceStatusFilter) !== 0) {
                return false;
            }
        }

        if ($paymentStatusFilter !== null && $paymentStatusFilter !== '') {
            if (strcasecmp($displayStatus, $paymentStatusFilter) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function resolveInvoiceDateString(FeeInvoice $invoice): ?string
    {
        if (!empty($invoice->due_date)) {
            return substr((string) $invoice->due_date, 0, 10);
        }
        if (!empty($invoice->created_at)) {
            return substr((string) $invoice->created_at, 0, 10);
        }

        return null;
    }

    private function makeHeadGroupKey(int $feeCategoryId, string $headName): string
    {
        return $feeCategoryId . '|' . mb_strtolower(trim($headName));
    }

    private function formatAmount(string $value): string
    {
        return NumberToWords::formatDecimal($this->toDecimal($value));
    }

    private function emptyReportPayload(string $mode, string $modeLabel, array $filters): array
    {
        return [
            'mode' => $mode,
            'mode_label' => $modeLabel,
            'tables_available' => false,
            'filters' => $filters,
            'summary' => [
                'active_invoice_count' => 0,
                'active_student_count' => 0,
                'gross' => '0.00',
                'gross_formatted' => '0.00',
                'discount' => '0.00',
                'discount_formatted' => '0.00',
                'net_receivable' => '0.00',
                'net_receivable_formatted' => '0.00',
                'collected' => '0.00',
                'collected_formatted' => '0.00',
                'due' => '0.00',
                'due_formatted' => '0.00',
                'period_collected' => '0.00',
                'period_collected_formatted' => '0.00',
                'collection_rate' => '0.0',
            ],
            'cancelled_audit' => [
                'count' => 0,
                'gross_amount' => '0.00',
                'gross_amount_formatted' => '0.00',
                'discount_amount' => '0.00',
                'discount_amount_formatted' => '0.00',
                'net_amount' => '0.00',
                'net_amount_formatted' => '0.00',
                'invoices' => [],
            ],
            'reconciliation' => [
                'invoice_sum_gross' => '0.00',
                'head_sum_gross' => '0.00',
                'gross_diff' => '0.00',
                'invoice_sum_discount' => '0.00',
                'head_sum_discount' => '0.00',
                'discount_diff' => '0.00',
                'invoice_sum_net' => '0.00',
                'head_sum_net' => '0.00',
                'net_diff' => '0.00',
                'invoice_sum_collected' => '0.00',
                'head_sum_collected' => '0.00',
                'collected_diff' => '0.00',
                'invoice_sum_due' => '0.00',
                'head_sum_due' => '0.00',
                'due_diff' => '0.00',
                'invoice_sum_period_collected' => '0.00',
                'head_sum_period_collected' => '0.00',
                'period_collected_diff' => '0.00',
                'rows_sum_net' => '0.00',
                'rows_sum_collected' => '0.00',
                'rows_sum_due' => '0.00',
                'rows_sum_period_collected' => '0.00',
                'net_minus_collected_equals_due' => true,
                'is_reconciled' => true,
            ],
            'rows' => [],
            'class_matrix' => self::emptyClassMatrixPayload($filters['matrix_metric'] ?? self::METRIC_NET_RECEIVABLE),
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    /**
     * Static empty class-wise fee-head matrix payload safe for pre-migration environments.
     *
     * @return array<string, mixed>
     */
    public static function emptyClassMatrixPayload(?string $metric = null): array
    {
        $resolvedMetric = in_array($metric, self::VALID_MATRIX_METRICS, true)
            ? (string) $metric
            : self::METRIC_NET_RECEIVABLE;

        return [
            'metric' => $resolvedMetric,
            'metric_label' => self::MATRIX_METRIC_LABELS[$resolvedMetric] ?? 'Net Receivable',
            'head_columns' => [],
            'rows' => [],
            'column_totals' => [],
            'is_reconciled' => true,
        ];
    }

    /**
     * Build horizontal Class × Fee Head cross-tab matrix across Play..Ten in academic sequence,
     * keeping pure BCMath decimal accuracy across all metrics (Gross, Discount, Net, Collected, Due, Period).
     *
     * @param  array<int, array<string, mixed>>  $activeInvoiceEntries
     * @param  array<string, mixed>  $periodData
     * @param  array<int, array<string, mixed>>  $classOrderMap
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    private function buildClassHeadMatrix(
        array $activeInvoiceEntries,
        array $periodData,
        array $classOrderMap,
        array $normalized
    ): array {
        $metric = $normalized['matrix_metric'] ?? self::METRIC_NET_RECEIVABLE;
        $columns = [];
        $classBuckets = [];

        $targetClassIds = $normalized['class_id']
            ? [(int) $normalized['class_id']]
            : array_keys($classOrderMap);

        foreach ($targetClassIds as $cid) {
            if (!isset($classOrderMap[$cid])) {
                continue;
            }
            $classBuckets[$cid] = [
                'class_id' => $cid,
                'class_name' => $classOrderMap[$cid]['name'],
                'class_order' => (int) $classOrderMap[$cid]['order'],
                'invoice_count' => 0,
                'student_ids' => [],
                'gross' => '0.00',
                'discount' => '0.00',
                'net_receivable' => '0.00',
                'collected' => '0.00',
                'due' => '0.00',
                'period_collected' => '0.00',
                'cells' => [],
            ];
        }

        // Seed column ordering from active structures in scope when not narrowed to Cancelled/search.
        if ($normalized['invoice_status'] !== 'Cancelled' && $normalized['search'] === '') {
            $itemQuery = AdmissionFeeStructureItem::query()
                ->with('category')
                ->whereHas('structure', function ($sq) use ($normalized): void {
                    $sq->where('status', 'Active');
                    if ($normalized['branch_id']) {
                        $sq->where('branch_id', $normalized['branch_id']);
                    }
                    if ($normalized['session_year_id']) {
                        $sq->where(function ($sub) use ($normalized): void {
                            $sub->where('session_year_id', $normalized['session_year_id'])
                                ->orWhereNull('session_year_id');
                        });
                    }
                    if ($normalized['class_id']) {
                        $sq->where('class_id', $normalized['class_id']);
                    }
                });

            if ($normalized['fee_category_id']) {
                $itemQuery->where('fee_category_id', $normalized['fee_category_id']);
            }
            if ($normalized['head_name'] !== '') {
                $itemQuery->whereRaw('LOWER(TRIM(head_name)) = ?', [mb_strtolower($normalized['head_name'])]);
            }

            foreach ($itemQuery->orderBy('sort_order')->orderBy('id')->get() as $item) {
                $catId = (int) $item->fee_category_id;
                $hName = (string) $item->head_name;
                $hKey = $this->makeHeadGroupKey($catId, $hName);
                $this->registerMatrixColumn($columns, $hKey, [
                    'head_key' => $hKey,
                    'fee_category_id' => $catId,
                    'category_name' => (string) ($item->category?->name ?? 'Others'),
                    'head_name' => $hName,
                    'is_tuition_head' => (bool) $item->is_tuition_head,
                    'sort_order' => (int) $item->sort_order,
                ]);
            }
        }

        foreach ($activeInvoiceEntries as $entry) {
            /** @var FeeInvoice $invoice */
            $invoice = $entry['invoice'];
            $hs = $entry['head_summary'];
            $classId = (int) ($invoice->admissionFeeStructure?->class_id ?? 0);
            $className = (string) ($classOrderMap[$classId]['name'] ?? ($invoice->admissionFeeStructure?->schoolClass?->class_name ?? 'N/A'));
            $classOrder = (int) ($classOrderMap[$classId]['order'] ?? 999);

            if (!isset($classBuckets[$classId])) {
                $classBuckets[$classId] = [
                    'class_id' => $classId,
                    'class_name' => $className,
                    'class_order' => $classOrder,
                    'invoice_count' => 0,
                    'student_ids' => [],
                    'gross' => '0.00',
                    'discount' => '0.00',
                    'net_receivable' => '0.00',
                    'collected' => '0.00',
                    'due' => '0.00',
                    'period_collected' => '0.00',
                    'cells' => [],
                ];
            }

            $classBuckets[$classId]['invoice_count']++;
            $classBuckets[$classId]['student_ids'][(int) $invoice->student_id] = true;
            $classBuckets[$classId]['gross'] = bcadd($classBuckets[$classId]['gross'], $hs['gross'], 2);
            $classBuckets[$classId]['discount'] = bcadd($classBuckets[$classId]['discount'], $hs['discount'], 2);
            $classBuckets[$classId]['net_receivable'] = bcadd($classBuckets[$classId]['net_receivable'], $hs['net'], 2);
            $classBuckets[$classId]['collected'] = bcadd($classBuckets[$classId]['collected'], $hs['collected'], 2);
            $classBuckets[$classId]['due'] = bcadd($classBuckets[$classId]['due'], $hs['due'], 2);

            foreach ($hs['heads'] as $head) {
                $hKey = $this->makeHeadGroupKey($head['fee_category_id'], $head['head_name']);
                $this->registerMatrixColumn($columns, $hKey, [
                    'head_key' => $hKey,
                    'fee_category_id' => (int) $head['fee_category_id'],
                    'category_name' => (string) $head['category_name'],
                    'head_name' => (string) $head['head_name'],
                    'is_tuition_head' => (bool) $head['is_tuition_head'],
                    'sort_order' => (int) $head['sort_order'],
                ]);

                if (!isset($classBuckets[$classId]['cells'][$hKey])) {
                    $classBuckets[$classId]['cells'][$hKey] = $this->emptyMatrixMetrics();
                }
                $cell = &$classBuckets[$classId]['cells'][$hKey];
                $cell[self::METRIC_GROSS] = bcadd($cell[self::METRIC_GROSS], $head['gross'], 2);
                $cell[self::METRIC_DISCOUNT] = bcadd($cell[self::METRIC_DISCOUNT], $head['discount'], 2);
                $cell[self::METRIC_NET_RECEIVABLE] = bcadd($cell[self::METRIC_NET_RECEIVABLE], $head['net'], 2);
                $cell[self::METRIC_COLLECTED] = bcadd($cell[self::METRIC_COLLECTED], $head['collected'], 2);
                $cell[self::METRIC_DUE] = bcadd($cell[self::METRIC_DUE], $head['due'], 2);
                unset($cell);
            }
        }

        foreach (($periodData['by_class'] ?? []) as $classId => $pInfo) {
            if (!isset($classBuckets[$classId])) {
                $classBuckets[$classId] = [
                    'class_id' => (int) $classId,
                    'class_name' => $pInfo['class_name'],
                    'class_order' => (int) $pInfo['class_order'],
                    'invoice_count' => 0,
                    'student_ids' => [],
                    'gross' => '0.00',
                    'discount' => '0.00',
                    'net_receivable' => '0.00',
                    'collected' => '0.00',
                    'due' => '0.00',
                    'period_collected' => '0.00',
                    'cells' => [],
                ];
            }
            $classBuckets[$classId]['period_collected'] = bcadd(
                $classBuckets[$classId]['period_collected'],
                $pInfo['amount'],
                2
            );
        }

        foreach (($periodData['by_class_head'] ?? []) as $classId => $headMap) {
            foreach ($headMap as $hKey => $pHead) {
                $this->registerMatrixColumn($columns, $hKey, [
                    'head_key' => $hKey,
                    'fee_category_id' => (int) $pHead['fee_category_id'],
                    'category_name' => (string) $pHead['category_name'],
                    'head_name' => (string) $pHead['head_name'],
                    'is_tuition_head' => (bool) $pHead['is_tuition_head'],
                    'sort_order' => (int) $pHead['sort_order'],
                ]);
                if (!isset($classBuckets[$classId]['cells'][$hKey])) {
                    $classBuckets[$classId]['cells'][$hKey] = $this->emptyMatrixMetrics();
                }
                $classBuckets[$classId]['cells'][$hKey][self::METRIC_PERIOD_COLLECTED] = bcadd(
                    $classBuckets[$classId]['cells'][$hKey][self::METRIC_PERIOD_COLLECTED],
                    $pHead['amount'],
                    2
                );
            }
        }

        usort(
            $columns,
            fn (array $a, array $b): int => [$a['sort_order'], strtolower($a['head_name']), $a['fee_category_id']]
                <=> [$b['sort_order'], strtolower($b['head_name']), $b['fee_category_id']]
        );
        usort(
            $classBuckets,
            fn (array $a, array $b): int => [$a['class_order'], $a['class_id']] <=> [$b['class_order'], $b['class_id']]
        );

        $columnTotals = [];
        foreach ($columns as $col) {
            $columnTotals[$col['head_key']] = $this->emptyMatrixMetrics();
        }

        $rowMetricSum = $this->emptyMatrixMetrics();
        $finalRows = [];

        foreach ($classBuckets as $bucket) {
            $formattedCells = [];
            foreach ($columns as $col) {
                $hKey = $col['head_key'];
                $rawCell = $bucket['cells'][$hKey] ?? $this->emptyMatrixMetrics();
                $formattedCell = [];
                foreach (self::VALID_MATRIX_METRICS as $m) {
                    $val = $rawCell[$m];
                    $formattedCell[$m] = $val;
                    $formattedCell[$m . '_formatted'] = $this->formatAmount($val);
                    $columnTotals[$hKey][$m] = bcadd($columnTotals[$hKey][$m], $val, 2);
                }
                $formattedCell['active_value'] = $formattedCell[$metric];
                $formattedCell['active_value_formatted'] = $formattedCell[$metric . '_formatted'];
                $formattedCell['is_zero'] = bccomp($formattedCell[$metric], '0.00', 2) === 0;
                $formattedCells[$hKey] = $formattedCell;
            }

            foreach (self::VALID_MATRIX_METRICS as $m) {
                $rowMetricSum[$m] = bcadd($rowMetricSum[$m], $bucket[$m], 2);
            }

            $finalRows[] = $this->finalizeGroupRow([
                'class_id' => $bucket['class_id'],
                'class_name' => $bucket['class_name'],
                'class_order' => $bucket['class_order'],
                'invoice_count' => $bucket['invoice_count'],
                'student_count' => count($bucket['student_ids']),
                'has_activity' => $bucket['invoice_count'] > 0 || bccomp($bucket['period_collected'], '0.00', 2) > 0,
                'gross' => $bucket['gross'],
                'discount' => $bucket['discount'],
                'net_receivable' => $bucket['net_receivable'],
                'collected' => $bucket['collected'],
                'due' => $bucket['due'],
                'period_collected' => $bucket['period_collected'],
                'cells' => $formattedCells,
            ]);
        }

        $finalColumnTotals = [];
        $colMetricSum = $this->emptyMatrixMetrics();
        foreach ($columns as $col) {
            $hKey = $col['head_key'];
            $formattedTotal = [];
            foreach (self::VALID_MATRIX_METRICS as $m) {
                $val = $columnTotals[$hKey][$m];
                $formattedTotal[$m] = $val;
                $formattedTotal[$m . '_formatted'] = $this->formatAmount($val);
                $colMetricSum[$m] = bcadd($colMetricSum[$m], $val, 2);
            }
            $formattedTotal['active_value'] = $formattedTotal[$metric];
            $formattedTotal['active_value_formatted'] = $formattedTotal[$metric . '_formatted'];
            $finalColumnTotals[$hKey] = $formattedTotal;
        }

        $matrixReconciled = true;
        foreach (self::VALID_MATRIX_METRICS as $m) {
            if (bccomp($rowMetricSum[$m], $colMetricSum[$m], 2) !== 0) {
                $matrixReconciled = false;
                break;
            }
        }

        return [
            'metric' => $metric,
            'metric_label' => self::MATRIX_METRIC_LABELS[$metric] ?? 'Net Receivable',
            'head_columns' => array_values($columns),
            'rows' => $finalRows,
            'column_totals' => $finalColumnTotals,
            'is_reconciled' => $matrixReconciled,
        ];
    }

    /**
     * Register or refine column metadata using the lowest explicit sort_order across matching items.
     *
     * @param  array<string, array<string, mixed>>  $columns
     * @param  array<string, mixed>  $candidate
     */
    private function registerMatrixColumn(array &$columns, string $headKey, array $candidate): void
    {
        if (!isset($columns[$headKey])) {
            $columns[$headKey] = $candidate;
            return;
        }

        $columns[$headKey]['sort_order'] = min(
            (int) $columns[$headKey]['sort_order'],
            (int) $candidate['sort_order']
        );
        if (!empty($candidate['is_tuition_head'])) {
            $columns[$headKey]['is_tuition_head'] = true;
        }
    }

    /**
     * @return array<string, string>
     */
    private function emptyMatrixMetrics(): array
    {
        return [
            self::METRIC_GROSS => '0.00',
            self::METRIC_DISCOUNT => '0.00',
            self::METRIC_NET_RECEIVABLE => '0.00',
            self::METRIC_COLLECTED => '0.00',
            self::METRIC_DUE => '0.00',
            self::METRIC_PERIOD_COLLECTED => '0.00',
        ];
    }
}
