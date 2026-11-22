<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SessionYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $query = DB::table('fee_invoices')
            ->join('students', 'fee_invoices.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->join('fee_setups', 'fee_invoices.fee_setup_id', '=', 'fee_setups.id')
            ->join('fee_categories', 'fee_setups.fee_category_id', '=', 'fee_categories.id')
            ->whereNull('fee_invoices.admission_fee_structure_id')
            ->where('fee_invoices.status', '!=', 'Cancelled');

        if ($sessionId) {
            $query->where('fee_setups.session_year_id', $sessionId);
        }

        if ($startDate && $endDate) {
            if ($periodType === 'monthly' && isset($year) && isset($monthNum)) {
                $prefix = sprintf('INV-%04d%02d-', $year, (int) $monthNum);
                $query->where(function ($q) use ($prefix, $startDate, $endDate) {
                    $q->where('fee_invoices.invoice_no', 'like', $prefix . '%')
                      ->orWhereBetween('fee_invoices.due_date', [$startDate, $endDate]);
                });
            } else {
                // Custom Date Range (Model 1): Period Billed includes invoices due OR created within the selected range
                $query->where(function ($q) use ($startDate, $endDate): void {
                    $q->whereBetween('fee_invoices.due_date', [$startDate, $endDate])
                      ->orWhereRaw('DATE(fee_invoices.created_at) BETWEEN ? AND ?', [$startDate, $endDate]);
                });
            }
        }

        $aggregatedRows = $query->select(
            'classes.id as class_id',
            'classes.class_name',
            'fee_categories.name as cat_name',
            DB::raw('SUM(fee_invoices.net_amount) as due'),
            DB::raw('SUM(fee_invoices.paid_amount) as collected'),
            DB::raw('SUM(fee_invoices.due_amount) as outstanding')
        )
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
            $this->accumulateBucketAmounts(
                $rows[$clsId],
                $bucket,
                $this->receiptService->toDecimal($r->due),
                $isCustomRange ? '0.00' : $this->receiptService->toDecimal($r->collected),
                $this->receiptService->toDecimal($r->outstanding)
            );
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
                ->join('classes', 'students.class_id', '=', 'classes.id')
                ->join('fee_setups', 'fee_invoices.fee_setup_id', '=', 'fee_setups.id')
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

            $legacyCollectedRows = $legacyPayQuery->select(
                'classes.id as class_id',
                'classes.class_name',
                'fee_categories.name as cat_name',
                DB::raw('SUM(fee_payments.paid_amount) as collected')
            )
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
