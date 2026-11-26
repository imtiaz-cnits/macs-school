<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SessionYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClassWiseFinancialReportService
{
    /**
     * Category Classification buckets.
     */
    public const BUCKET_MONTHLY_FEE = 'monthly_fee';
    public const BUCKET_EXAM_FEES   = 'exam_fees';
    public const BUCKET_OTHER_FEES  = 'other_fees';

    protected FeeReceiptService $receiptService;
    protected AdmissionFeeReportService $admissionReportService;

    public function __construct(
        ?FeeReceiptService $receiptService = null,
        ?AdmissionFeeReportService $admissionReportService = null
    ) {
        $this->receiptService = $receiptService ?? app(FeeReceiptService::class);
        $this->admissionReportService = $admissionReportService ?? app(AdmissionFeeReportService::class);
    }

    /**
     * Classify a fee category name into one of the three authoritative groups:
     * 1. monthly_fee: Regular student monthly tuition fee.
     * 2. exam_fees: All exam-related student fees (e.g. Monthly Exam Fee, Term Exam Fee, Final Exam Fee).
     * 3. other_fees: All remaining student fees & charges (e.g. Admission, Session, Registration, Form Fill-up, Fine, Others).
     *
     * Staff/teacher salary and employee payroll are NEVER processed or included.
     */
    public function classifyCategory(?string $categoryName): string
    {
        $name = strtolower(trim((string) $categoryName));

        // 1. Regular Monthly Tuition Fee
        if ($name === 'monthly fee') {
            return self::BUCKET_MONTHLY_FEE;
        }

        // 2. Exam Fees (Monthly Exam Fee, Term Exam Fee, etc.)
        if (str_contains($name, 'exam')) {
            return self::BUCKET_EXAM_FEES;
        }

        // 3. All remaining student fee categories (Admission, Session, Registration, Form Fill-up, Fine, Others, etc.)
        return self::BUCKET_OTHER_FEES;
    }

    /**
     * Generate the complete class-wise financial report data based on requested filters.
     *
     * @param array $filters ['session_year_id' => int|null, 'period_type' => 'all'|'monthly'|'custom', 'month' => string|null, 'year' => int|string|null, 'start_date' => string|null, 'end_date' => string|null]
     * @return array
     */
    public function generateReport(array $filters = []): array
    {
        // 1. Resolve Academic Session
        $sessionId = !empty($filters['session_year_id']) ? (int) $filters['session_year_id'] : null;
        if (!$sessionId) {
            $latestSession = SessionYear::latest()->first();
            $sessionId = $latestSession ? (int) $latestSession->id : null;
        }
        $session = $sessionId ? SessionYear::find($sessionId) : null;
        $sessionName = $session ? $session->session_name : 'Current Session';

        // 2. Resolve Period Type & Dates
        $periodType = $filters['period_type'] ?? 'all';
        $startDate = null;
        $endDate = null;
        $periodLabel = 'Full Session';

        $monthNames = [
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
        ];

        if ($periodType === 'monthly' && !empty($filters['month'])) {
            $rawMonth = strtolower(trim((string) $filters['month']));
            $monthNum = is_numeric($rawMonth) ? str_pad($rawMonth, 2, '0', STR_PAD_LEFT) : null;

            if (!$monthNum) {
                foreach ($monthNames as $num => $name) {
                    if (strtolower($name) === $rawMonth) {
                        $monthNum = $num;
                        break;
                    }
                }
            }

            if ($monthNum && isset($monthNames[$monthNum])) {
                $year = !empty($filters['year']) && is_numeric($filters['year']) ? (int) $filters['year'] : (int) ($sessionName ?: date('Y'));
                $startDate = sprintf('%04d-%02d-01', $year, (int) $monthNum);
                $endDate = date('Y-m-t', strtotime($startDate));
                $periodLabel = $monthNames[$monthNum] . ' ' . $year;
            } else {
                $periodType = 'all';
            }
        } elseif ($periodType === 'custom' && !empty($filters['start_date']) && !empty($filters['end_date'])) {
            $startDate = $filters['start_date'];
            $endDate = $filters['end_date'];
            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
            $periodLabel = date('d M Y', strtotime($startDate)) . ' to ' . date('d M Y', strtotime($endDate));
        } else {
            $periodType = 'all';
            $periodLabel = "Full Session ({$sessionName})";
        }

        $isCustomRange = ($periodType === 'custom' && $startDate && $endDate);

        // 3. Build Query for Legacy Fee Invoices (Strictly Excluding Cancelled Invoices & Admission Invoices)
        // Historical Class Rule: Group by fee_setups.class_id (with students.class_id fallback) so year-end
        // student promotion never shifts historical session billing into the promoted class.
        $query = DB::table('fee_invoices')
            ->join('students', 'fee_invoices.student_id', '=', 'students.id')
            ->join('fee_setups', 'fee_invoices.fee_setup_id', '=', 'fee_setups.id')
            ->join('classes', DB::raw('COALESCE(fee_setups.class_id, students.class_id)'), '=', 'classes.id')
            ->join('fee_categories', 'fee_setups.fee_category_id', '=', 'fee_categories.id')
            ->whereNull('fee_invoices.admission_fee_structure_id')
            ->where('fee_invoices.status', '!=', 'Cancelled');

        if ($sessionId) {
            $query->where('fee_setups.session_year_id', $sessionId);
        }

        if ($startDate && $endDate) {
            if ($periodType === 'monthly' && isset($year) && isset($monthNum)) {
                $this->applyMonthlyLegacyInvoiceFilter($query, $year, $monthNum, $startDate, $endDate);
            } else {
                // Custom Date Range (Model 1): Period Billed includes invoices due OR created within the selected range
                $query->where(function ($q) use ($startDate, $endDate): void {
                    $q->whereBetween('fee_invoices.due_date', [$startDate, $endDate])
                      ->orWhereRaw('DATE(fee_invoices.created_at) BETWEEN ? AND ?', [$startDate, $endDate]);
                });
            }
        }

        $hasInvoiceFineCol = Schema::hasColumn('fee_invoices', 'fine_amount');

        $selectColumns = [
            'classes.id as class_id',
            'classes.class_name',
            'fee_categories.name as cat_name',
            DB::raw('SUM(fee_invoices.net_amount) as due'),
            DB::raw('SUM(fee_invoices.paid_amount) as collected'),
            DB::raw('SUM(fee_invoices.due_amount) as outstanding'),
        ];

        if ($hasInvoiceFineCol) {
            // Pure ANSI CASE WHEN expressions (100% portable across MySQL 8 and SQLite):
            // fine_collected = clamp(paid_amount - net_amount, 0, fine_amount)
            // fine_outstanding = min(due_amount, fine_amount - fine_collected)
            $fineCollectedExpr = "CASE " .
                "WHEN COALESCE(fee_invoices.fine_amount, 0) > 0 AND fee_invoices.paid_amount > fee_invoices.net_amount THEN " .
                "CASE WHEN (fee_invoices.paid_amount - fee_invoices.net_amount) <= COALESCE(fee_invoices.fine_amount, 0) " .
                "THEN (fee_invoices.paid_amount - fee_invoices.net_amount) ELSE COALESCE(fee_invoices.fine_amount, 0) END " .
                "ELSE 0 END";

            $fineUnpaidExpr = "(COALESCE(fee_invoices.fine_amount, 0) - ({$fineCollectedExpr}))";

            $fineOutstandingExpr = "CASE " .
                "WHEN COALESCE(fee_invoices.fine_amount, 0) > 0 AND fee_invoices.due_amount > 0 THEN " .
                "CASE WHEN {$fineUnpaidExpr} <= fee_invoices.due_amount THEN {$fineUnpaidExpr} ELSE fee_invoices.due_amount END " .
                "ELSE 0 END";

            $selectColumns[] = DB::raw('SUM(COALESCE(fee_invoices.fine_amount, 0)) as fine_due');
            $selectColumns[] = DB::raw("SUM({$fineCollectedExpr}) as fine_collected");
            $selectColumns[] = DB::raw("SUM({$fineOutstandingExpr}) as fine_outstanding");
        }

        $aggregatedRows = $query->select($selectColumns)
            ->groupBy('classes.id', 'classes.class_name', 'fee_categories.name')
            ->get();

        // 4. Initialize All Classes in Academic Order (Classes global scope: Play..Ten)
        $classes = Classes::all();

        $rows = [];
        foreach ($classes as $cls) {
            $rows[(int) $cls->id] = $this->makeEmptyClassRow((int) $cls->id, (string) $cls->class_name);
        }

        // 5. Populate Aggregated Legacy Amounts per Class and Category Group (Pure BCMath)
        foreach ($aggregatedRows as $r) {
            $clsId = (int) $r->class_id;
            if (!isset($rows[$clsId])) {
                $rows[$clsId] = $this->makeEmptyClassRow($clsId, (string) $r->class_name);
            }

            $bucket = $this->classifyCategory($r->cat_name);
            $baseDue = $this->receiptService->toDecimal($r->due);
            $totalCollected = $this->receiptService->toDecimal($r->collected);
            $totalOutstanding = $this->receiptService->toDecimal($r->outstanding);

            $fineDue = $hasInvoiceFineCol ? $this->receiptService->toDecimal($r->fine_due ?? '0.00') : '0.00';
            $fineCollected = $hasInvoiceFineCol ? $this->receiptService->toDecimal($r->fine_collected ?? '0.00') : '0.00';
            $fineOutstanding = $hasInvoiceFineCol ? $this->receiptService->toDecimal($r->fine_outstanding ?? '0.00') : '0.00';

            $baseCollected = bcsub($totalCollected, $fineCollected, 2);
            $baseOutstanding = bcsub($totalOutstanding, $fineOutstanding, 2);

            $this->accumulateBucketAmounts(
                $rows[$clsId],
                $bucket,
                $baseDue,
                $isCustomRange ? '0.00' : $baseCollected,
                $baseOutstanding
            );

            if (bccomp($fineDue, '0.00', 2) > 0
                || bccomp($fineCollected, '0.00', 2) > 0
                || bccomp($fineOutstanding, '0.00', 2) > 0
            ) {
                $this->accumulateBucketAmounts(
                    $rows[$clsId],
                    self::BUCKET_OTHER_FEES,
                    $fineDue,
                    $isCustomRange ? '0.00' : $fineCollected,
                    $fineOutstanding
                );
            }
        }

        // 5b. Include Admission Fee Invoices Using Strict Historical Structure Class & Active Head Allocations
        if ($this->receiptService->admissionTablesAvailable()) {
            $admQuery = FeeInvoice::query()
                ->with([
                    'admissionFeeStructure.schoolClass',
                    'admissionHeadAllocations.category',
                    'payments' => fn ($pq) => $pq->where('status', 'Active')->with('admissionHeadAllocations'),
                ])
                ->whereNotNull('fee_invoices.admission_fee_structure_id')
                ->where('fee_invoices.status', '!=', 'Cancelled');

            if ($sessionId) {
                $admQuery->where('fee_invoices.session_year_id', $sessionId);
            }

            if ($startDate && $endDate) {
                if ($periodType === 'monthly' && isset($year) && isset($monthNum)) {
                    $legacyPrefix = sprintf('INV-%04d%02d-', $year, (int) $monthNum);
                    $admYmPattern = sprintf('INV-ADM%%-%04d%02d-%%', $year, (int) $monthNum);
                    $admQuery->where(function ($q) use ($legacyPrefix, $admYmPattern, $startDate, $endDate): void {
                        $q->where('fee_invoices.invoice_no', 'like', $legacyPrefix . '%')
                          ->orWhere('fee_invoices.invoice_no', 'like', $admYmPattern)
                          ->orWhereRaw('COALESCE(fee_invoices.due_date, DATE(fee_invoices.created_at)) BETWEEN ? AND ?', [$startDate, $endDate]);
                    });
                } else {
                    // Custom Date Range (Model 1): Period Billed includes admission invoices due OR created within range
                    $admQuery->where(function ($q) use ($startDate, $endDate): void {
                        $q->whereBetween('fee_invoices.due_date', [$startDate, $endDate])
                          ->orWhereRaw('DATE(fee_invoices.created_at) BETWEEN ? AND ?', [$startDate, $endDate]);
                    });
                }
            }

            foreach ($admQuery->get() as $admInvoice) {
                if ($periodType === 'monthly' && isset($year) && isset($monthNum)) {
                    if (!$this->matchesMonthlyAdmissionInvoice($admInvoice, $year, $monthNum, $startDate, $endDate)) {
                        continue;
                    }
                }

                // Historical Class Rule: Always use admission_fee_structures.class_id, NEVER promoted students.class_id.
                $structureClassId = (int) ($admInvoice->admissionFeeStructure?->class_id ?? 0);
                if ($structureClassId <= 0) {
                    continue;
                }

                if (!isset($rows[$structureClassId])) {
                    $clsName = (string) ($admInvoice->admissionFeeStructure?->schoolClass?->class_name ?? 'N/A');
                    $rows[$structureClassId] = $this->makeEmptyClassRow($structureClassId, $clsName);
                }

                $headSummary = $this->admissionReportService->summarizeAdmissionInvoiceActiveHeads($admInvoice);
                foreach ($headSummary['heads'] as $headRow) {
                    $bucket = $headRow['is_tuition_head']
                        ? self::BUCKET_MONTHLY_FEE
                        : $this->classifyCategory($headRow['category_name']);

                    $this->accumulateBucketAmounts(
                        $rows[$structureClassId],
                        $bucket,
                        $headRow['net'],
                        $isCustomRange ? '0.00' : $headRow['collected'],
                        $headRow['due']
                    );
                }
            }
        }

        // 5c. Custom Date Range (Model 1): Independent Period Cash Collected strictly by Active FeePayment.payment_date
        if ($isCustomRange) {
            $admissionTablesAvailable = $this->receiptService->admissionTablesAvailable();

            $legacyPayQuery = DB::table('fee_payments')
                ->join('fee_invoices', 'fee_payments.fee_invoice_id', '=', 'fee_invoices.id')
                ->join('students', 'fee_invoices.student_id', '=', 'students.id')
                ->join('fee_setups', 'fee_invoices.fee_setup_id', '=', 'fee_setups.id')
                ->join('classes', DB::raw('COALESCE(fee_setups.class_id, students.class_id)'), '=', 'classes.id')
                ->join('fee_categories', 'fee_setups.fee_category_id', '=', 'fee_categories.id')
                ->whereNull('fee_invoices.admission_fee_structure_id')
                ->where('fee_invoices.status', '!=', 'Cancelled')
                ->whereBetween('fee_payments.payment_date', [$startDate, $endDate]);

            if ($admissionTablesAvailable) {
                $legacyPayQuery->where('fee_payments.status', 'Active');
            }

            if ($sessionId) {
                $legacyPayQuery->where('fee_setups.session_year_id', $sessionId);
            }

            $hasPaymentFineCol = Schema::hasColumn('fee_payments', 'fine_amount');

            $legacyPaySelect = [
                'classes.id as class_id',
                'classes.class_name',
                'fee_categories.name as cat_name',
            ];
            if ($hasPaymentFineCol) {
                $legacyPaySelect[] = DB::raw('SUM(fee_payments.paid_amount - COALESCE(fee_payments.fine_amount, 0)) as collected');
                $legacyPaySelect[] = DB::raw('SUM(COALESCE(fee_payments.fine_amount, 0)) as fine_collected');
            } else {
                $legacyPaySelect[] = DB::raw('SUM(fee_payments.paid_amount) as collected');
            }

            $legacyCollectedRows = $legacyPayQuery->select($legacyPaySelect)
                ->groupBy('classes.id', 'classes.class_name', 'fee_categories.name')
                ->get();

            foreach ($legacyCollectedRows as $r) {
                $clsId = (int) $r->class_id;
                if (!isset($rows[$clsId])) {
                    $rows[$clsId] = $this->makeEmptyClassRow($clsId, (string) $r->class_name);
                }

                $bucket = $this->classifyCategory($r->cat_name);
                $this->accumulateBucketAmounts(
                    $rows[$clsId],
                    $bucket,
                    '0.00',
                    $this->receiptService->toDecimal($r->collected),
                    '0.00'
                );

                $fineCollected = $hasPaymentFineCol
                    ? $this->receiptService->toDecimal($r->fine_collected ?? '0.00')
                    : '0.00';
                if (bccomp($fineCollected, '0.00', 2) > 0) {
                    $this->accumulateBucketAmounts(
                        $rows[$clsId],
                        self::BUCKET_OTHER_FEES,
                        '0.00',
                        $fineCollected,
                        '0.00'
                    );
                }
            }

            if ($admissionTablesAvailable) {
                $admPayQuery = FeePayment::query()
                    ->with([
                        'invoice.admissionFeeStructure.schoolClass',
                        'invoice.admissionHeadAllocations.category',
                        'admissionHeadAllocations',
                    ])
                    ->where('fee_payments.status', 'Active')
                    ->whereBetween('fee_payments.payment_date', [$startDate, $endDate])
                    ->whereHas('invoice', function ($iq) use ($sessionId): void {
                        $iq->whereNotNull('fee_invoices.admission_fee_structure_id')
                           ->where('fee_invoices.status', '!=', 'Cancelled');

                        if ($sessionId) {
                            $iq->where('fee_invoices.session_year_id', $sessionId);
                        }
                    });

                foreach ($admPayQuery->get() as $admPayment) {
                    if ($admPayment->status !== 'Active') {
                        continue;
                    }

                    $admInvoice = $admPayment->invoice;
                    $structureClassId = (int) ($admInvoice?->admissionFeeStructure?->class_id ?? 0);
                    if ($structureClassId <= 0) {
                        continue;
                    }

                    if (!isset($rows[$structureClassId])) {
                        $clsName = (string) ($admInvoice->admissionFeeStructure?->schoolClass?->class_name ?? 'N/A');
                        $rows[$structureClassId] = $this->makeEmptyClassRow($structureClassId, $clsName);
                    }

                    $catInfo = $this->admissionReportService->aggregateActivePaymentCategoryAmounts($admPayment);
                    foreach ($catInfo['head_details'] as $headDetail) {
                        $bucket = $headDetail['is_tuition_head']
                            ? self::BUCKET_MONTHLY_FEE
                            : $this->classifyCategory($headDetail['category_name']);

                        $this->accumulateBucketAmounts(
                            $rows[$structureClassId],
                            $bucket,
                            '0.00',
                            $headDetail['amount'],
                            '0.00'
                        );
                    }
                }
            }
        }

        // 6. Calculate Grand Totals across All Classes (Pure BCMath)
        $grandTotal = $this->makeZeroMetricsMatrix();

        foreach ($rows as $classRow) {
            foreach (['due', 'collected', 'outstanding'] as $group) {
                foreach (['monthly_fee', 'exam_fees', 'other_fees', 'total'] as $sub) {
                    $grandTotal[$group][$sub] = bcadd($grandTotal[$group][$sub], $classRow[$group][$sub], 2);
                }
            }
        }

        return [
            'rows'              => array_values($rows),
            'grand_total'       => $grandTotal,
            'session_id'        => $sessionId,
            'session_name'      => $sessionName,
            'period_type'       => $periodType,
            'period_label'      => $periodLabel,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'month'             => $filters['month'] ?? ($periodType === 'monthly' && isset($monthNum) ? $monthNames[$monthNum] : ''),
            'year'              => $filters['year'] ?? ($periodType === 'monthly' && isset($year) ? $year : date('Y')),
            'total_due'         => $grandTotal['due']['total'],
            'total_collected'   => $grandTotal['collected']['total'],
            'total_outstanding' => $grandTotal['outstanding']['total'],
            'generated_at'      => now()->format('d M Y, h:i A'),
        ];
    }

    /**
     * Apply mutually exclusive Monthly period filter for legacy fee invoices.
     *
     * Invoices with a canonical `INV-YYYYMM-` prefix belong strictly to that calendar month
     * (preventing cross-month double-counting when `due_date` drifts into another month).
     * Non-dated or NULL `invoice_no` records fall back cleanly to `due_date`.
     *
     * Portable across MySQL 8 and SQLite (uses standard LIKE / SUBSTR / IN predicates only).
     */
    private function applyMonthlyLegacyInvoiceFilter(
        $query,
        int $year,
        string $monthNum,
        string $startDate,
        string $endDate
    ): void {
        $prefix = sprintf('INV-%04d%02d-', $year, (int) $monthNum);

        $query->where(function ($q) use ($prefix, $startDate, $endDate): void {
            $q->where('fee_invoices.invoice_no', 'like', $prefix . '%')
              ->orWhere(function ($fallback) use ($startDate, $endDate): void {
                  $fallback->where(function ($nonDated): void {
                      $nonDated->whereNull('fee_invoices.invoice_no')
                          ->orWhereRaw(
                              "NOT (" .
                              "fee_invoices.invoice_no LIKE 'INV-______-%' " .
                              "AND SUBSTR(fee_invoices.invoice_no, 5, 1) IN ('1','2') " .
                              "AND SUBSTR(fee_invoices.invoice_no, 6, 1) IN ('0','1','2','3','4','5','6','7','8','9') " .
                              "AND SUBSTR(fee_invoices.invoice_no, 7, 1) IN ('0','1','2','3','4','5','6','7','8','9') " .
                              "AND SUBSTR(fee_invoices.invoice_no, 8, 1) IN ('0','1','2','3','4','5','6','7','8','9') " .
                              "AND SUBSTR(fee_invoices.invoice_no, 9, 2) IN ('01','02','03','04','05','06','07','08','09','10','11','12')" .
                              ")"
                          );
                  })
                  ->whereBetween('fee_invoices.due_date', [$startDate, $endDate]);
              });
        });
    }

    /**
     * Enforce mutually exclusive Monthly period matching for Admission invoices.
     *
     * If `invoice_no` encodes a canonical year-month (`INV-ADM-S{S}-YYYYMM-` or `INV-YYYYMM-`),
     * that month is authoritative and the invoice is never matched in another month by a drifted `due_date`.
     * Only non-dated Admission invoices fall back to `COALESCE(due_date, DATE(created_at))`.
     */
    private function matchesMonthlyAdmissionInvoice(
        FeeInvoice $admInvoice,
        int $year,
        string $monthNum,
        string $startDate,
        string $endDate
    ): bool {
        $extracted = FeeInvoice::extractYearMonthFromInvoiceNo($admInvoice->invoice_no);
        if ($extracted !== null) {
            return $extracted['year'] === sprintf('%04d', $year)
                && $extracted['month'] === $monthNum;
        }

        $effectiveDate = !empty($admInvoice->due_date)
            ? substr((string) $admInvoice->due_date, 0, 10)
            : ($admInvoice->created_at ? $admInvoice->created_at->format('Y-m-d') : null);

        return $effectiveDate !== null && $effectiveDate >= $startDate && $effectiveDate <= $endDate;
    }

    private function makeZeroMetricsMatrix(): array
    {
        $zeroGroup = [
            'monthly_fee' => '0.00',
            'exam_fees'   => '0.00',
            'other_fees'  => '0.00',
            'total'       => '0.00',
        ];

        return [
            'due'         => $zeroGroup,
            'collected'   => $zeroGroup,
            'outstanding' => $zeroGroup,
        ];
    }

    private function makeEmptyClassRow(int $classId, string $className): array
    {
        return array_merge([
            'class_id'   => $classId,
            'class_name' => $className,
        ], $this->makeZeroMetricsMatrix());
    }

    private function accumulateBucketAmounts(
        array &$row,
        string $bucket,
        string $dueAmount,
        string $collectedAmount,
        string $outstandingAmount
    ): void {
        $row['due'][$bucket]             = bcadd($row['due'][$bucket], $dueAmount, 2);
        $row['due']['total']             = bcadd($row['due']['total'], $dueAmount, 2);
        $row['collected'][$bucket]       = bcadd($row['collected'][$bucket], $collectedAmount, 2);
        $row['collected']['total']       = bcadd($row['collected']['total'], $collectedAmount, 2);
        $row['outstanding'][$bucket]     = bcadd($row['outstanding'][$bucket], $outstandingAmount, 2);
        $row['outstanding']['total']     = bcadd($row['outstanding']['total'], $outstandingAmount, 2);
    }
}
