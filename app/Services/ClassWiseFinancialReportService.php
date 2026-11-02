<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\SessionYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassWiseFinancialReportService
{
    /**
     * Category Classification buckets.
     */
    public const BUCKET_MONTHLY_FEE = 'monthly_fee';
    public const BUCKET_OTHER_FEES  = 'other_fees';
    public const BUCKET_OTHER       = 'other';

    /**
     * Classify a fee category name into one of the three authoritative groups:
     * 1. monthly_fee: Regular student monthly tuition fee.
     * 2. other: Miscellaneous student-related financial charges (e.g. Fine, Others, Misc).
     * 3. other_fees: All remaining configured student fees (e.g. Admission, Exams, Session, Reg, Form Fill-up).
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

        // 2. Miscellaneous Student Charges
        if (in_array($name, ['others', 'other', 'fine', 'miscellaneous', 'misc', 'penalty'])) {
            return self::BUCKET_OTHER;
        }

        // 3. All remaining student fee categories (Exam, Admission, Session, Registration, etc.)
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
            $periodLabel = date('d M Y', strtotime($startDate)) . ' to ' . date('d M Y', strtotime($endDate));
        } else {
            $periodType = 'all';
            $periodLabel = "Full Session ({$sessionName})";
        }

        // 3. Build Query for Fee Invoices (Strictly Excluding Cancelled Invoices)
        $query = DB::table('fee_invoices')
            ->join('students', 'fee_invoices.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->join('fee_setups', 'fee_invoices.fee_setup_id', '=', 'fee_setups.id')
            ->join('fee_categories', 'fee_setups.fee_category_id', '=', 'fee_categories.id')
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
                $query->whereBetween('fee_invoices.due_date', [$startDate, $endDate]);
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

        // 4. Initialize All Classes in Academic Order
        // Classes model uses global scope 'custom_order' (Play, Nursery, One, Two, ..., Ten)
        $classes = Classes::all();

        $rows = [];
        foreach ($classes as $cls) {
            $rows[$cls->id] = [
                'class_id'   => $cls->id,
                'class_name' => $cls->class_name,
                'due' => [
                    'monthly_fee' => 0.0,
                    'other_fees'  => 0.0,
                    'other'       => 0.0,
                    'total'       => 0.0,
                ],
                'collected' => [
                    'monthly_fee' => 0.0,
                    'other_fees'  => 0.0,
                    'other'       => 0.0,
                    'total'       => 0.0,
                ],
                'outstanding' => [
                    'monthly_fee' => 0.0,
                    'other_fees'  => 0.0,
                    'other'       => 0.0,
                    'total'       => 0.0,
                ],
            ];
        }

        // 5. Populate Aggregated Amounts per Class and Category Group
        foreach ($aggregatedRows as $r) {
            if (!isset($rows[$r->class_id])) {
                $rows[$r->class_id] = [
                    'class_id'   => $r->class_id,
                    'class_name' => $r->class_name,
                    'due' => ['monthly_fee' => 0.0, 'other_fees' => 0.0, 'other' => 0.0, 'total' => 0.0],
                    'collected' => ['monthly_fee' => 0.0, 'other_fees' => 0.0, 'other' => 0.0, 'total' => 0.0],
                    'outstanding' => ['monthly_fee' => 0.0, 'other_fees' => 0.0, 'other' => 0.0, 'total' => 0.0],
                ];
            }

            $bucket = $this->classifyCategory($r->cat_name);

            $dueAmount         = (float) $r->due;
            $collectedAmount   = (float) $r->collected;
            $outstandingAmount = (float) $r->outstanding;

            $rows[$r->class_id]['due'][$bucket]         += $dueAmount;
            $rows[$r->class_id]['due']['total']         += $dueAmount;

            $rows[$r->class_id]['collected'][$bucket]   += $collectedAmount;
            $rows[$r->class_id]['collected']['total']   += $collectedAmount;

            $rows[$r->class_id]['outstanding'][$bucket] += $outstandingAmount;
            $rows[$r->class_id]['outstanding']['total'] += $outstandingAmount;
        }

        // 6. Calculate Grand Totals across All Classes
        $grandTotal = [
            'due' => [
                'monthly_fee' => 0.0,
                'other_fees'  => 0.0,
                'other'       => 0.0,
                'total'       => 0.0,
            ],
            'collected' => [
                'monthly_fee' => 0.0,
                'other_fees'  => 0.0,
                'other'       => 0.0,
                'total'       => 0.0,
            ],
            'outstanding' => [
                'monthly_fee' => 0.0,
                'other_fees'  => 0.0,
                'other'       => 0.0,
                'total'       => 0.0,
            ],
        ];

        foreach ($rows as $classRow) {
            foreach (['due', 'collected', 'outstanding'] as $group) {
                foreach (['monthly_fee', 'other_fees', 'other', 'total'] as $sub) {
                    $grandTotal[$group][$sub] += $classRow[$group][$sub];
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
}
