<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\VendorOpeningBalance;
use App\Models\VendorPayment;
use App\Services\AdmissionFeeReportService;
use App\Services\FeeReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialReportController extends Controller
{
    /**
     * Supported standard payment methods across accounts.
     */
    protected array $supportedMethods = ['Cash', 'Bank', 'bKash', 'Nagad', 'Cheque', 'Rocket'];

    /**
     * Display the comprehensive Financial Statement report.
     */
    public function index(Request $request)
    {
        $data = $this->prepareReportData($request);

        return view('pages.accounts.financial_report', $data);
    }

    /**
     * Display printable Income vs Expense Statement.
     */
    public function printStatement(Request $request)
    {
        $data = $this->prepareReportData($request);

        return view('pages.accounts.statement_print', $data);
    }

    /**
     * Compile authoritative financial data, adhering strictly to real cash flow and collections rules.
     */
    protected function prepareReportData(Request $request): array
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'payment_method' => 'nullable|string|max:50',
        ]);

        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);
        /** @var AdmissionFeeReportService $admissionReportService */
        $admissionReportService = app(AdmissionFeeReportService::class);
        $admissionAvailable = $receiptService->admissionTablesAvailable();

        $startDate = $request->filled('start_date') ? $request->start_date : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->end_date : date('Y-m-d');
        $branchId = $request->branch_id;
        $paymentMethod = $request->payment_method;

        $paymentRelations = ['student.schoolClass', 'student.branch', 'invoice.feeSetup.category', 'collector'];
        if ($admissionAvailable) {
            $paymentRelations = array_merge($paymentRelations, [
                'invoice.admissionFeeStructure',
                'invoice.admissionHeadAllocations.category',
                'admissionHeadAllocations',
            ]);
        }

        // 1. Fee Payments Query (STRICTLY ACTIVE COLLECTED INCOME ONLY)
        $paymentsQuery = FeePayment::with($paymentRelations)
            ->where('fee_payments.status', 'Active')
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($branchId) {
            $paymentsQuery->where(function ($bq) use ($branchId, $admissionAvailable): void {
                if ($admissionAvailable) {
                    $bq->where(function ($admQ) use ($branchId): void {
                        $admQ->whereHas('invoice', function ($iq) use ($branchId): void {
                            $iq->whereNotNull('admission_fee_structure_id')
                               ->where('branch_id', $branchId);
                        });
                    })->orWhere(function ($legQ) use ($branchId): void {
                        $legQ->whereHas('invoice', function ($iq): void {
                            $iq->whereNull('admission_fee_structure_id');
                        })->whereHas('student', function ($sq) use ($branchId): void {
                            $sq->where('branch_id', $branchId);
                        });
                    });
                } else {
                    $bq->whereHas('student', function ($q) use ($branchId): void {
                        $q->where('branch_id', $branchId);
                    });
                }
            });
        }

        if ($paymentMethod) {
            $paymentsQuery->where('payment_method', 'like', $paymentMethod);
        }

        $payments = $paymentsQuery->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get();

        // Pure BCMath Aggregation over Strict Active Payments & Explicit Active Admission Head Allocations
        $totalIncome = '0.00';
        $methodInTotals = [];
        $incomeCategoryTotals = [];
        $incomeCategoryPaymentIds = [];

        foreach ($payments as $p) {
            $catInfo = $admissionReportService->aggregateActivePaymentCategoryAmounts($p);
            $effectiveAmt = $catInfo['effective_total'];
            $p->report_effective_amount = $effectiveAmt;

            $totalIncome = bcadd($totalIncome, $effectiveAmt, 2);

            $mKey = strtolower(trim((string) ($p->payment_method ?? '')));
            $methodInTotals[$mKey] = bcadd($methodInTotals[$mKey] ?? '0.00', $effectiveAmt, 2);

            if ($catInfo['is_admission']) {
                $structureName = trim((string) ($p->invoice?->admissionFeeStructure?->structure_name ?? 'Admission Fee Package'));
                $catList = array_keys($catInfo['by_category']);
                $p->report_category_label = !empty($catList)
                    ? "{$structureName} (" . implode(', ', $catList) . ')'
                    : "{$structureName} (No active heads matched)";
            } else {
                $p->report_category_label = (string) ($p->invoice?->feeSetup?->category?->name ?? 'Fee');
            }

            foreach ($catInfo['by_category'] as $catName => $catAmount) {
                $incomeCategoryTotals[$catName] = bcadd($incomeCategoryTotals[$catName] ?? '0.00', $catAmount, 2);
                $incomeCategoryPaymentIds[$catName][(int) $p->id] = true;
            }
        }
        $totalCollectionsCount = $payments->count();

        // 2. Expenses Query (ACTUAL INCURRED EXPENSES RECORDED IN PERIOD - Pure BCMath)
        $expensesQuery = Expense::with(['category', 'branch', 'creator', 'items.category', 'vendor'])
            ->whereBetween('expense_date', [$startDate, $endDate]);

        if ($branchId) {
            $expensesQuery->where('branch_id', $branchId);
        }

        if ($paymentMethod) {
            $expensesQuery->where('payment_method', 'like', $paymentMethod);
        }

        $expenses = $expensesQuery->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();
        $totalExpense = '0.00';
        foreach ($expenses as $exp) {
            $totalExpense = bcadd($totalExpense, $admissionReportService->toDecimal($exp->amount), 2);
        }
        $totalExpensesCount = $expenses->count();

        // 3. Overall Net Surplus / Deficit (Total Income - Total Expense)
        $netSurplus = bcsub($totalIncome, $totalExpense, 2);

        // 4. Actual Cash Outflow & Method Breakdown (Pure BCMath)
        $vendorPaymentsQuery = VendorPayment::whereBetween('payment_date', [$startDate, $endDate]);
        if ($branchId) {
            $vendorPaymentsQuery->where('branch_id', $branchId);
        }
        if ($paymentMethod) {
            $vendorPaymentsQuery->where('payment_method', 'like', $paymentMethod);
        }
        $vendorPayments = $vendorPaymentsQuery->get();

        $legacyPaidQuery = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('payment_status', 'Paid')
            ->whereDoesntHave('payments')
            ->whereDoesntHave('paymentAllocations');
        if ($branchId) {
            $legacyPaidQuery->where('branch_id', $branchId);
        }
        if ($paymentMethod) {
            $legacyPaidQuery->where('payment_method', 'like', $paymentMethod);
        }
        $legacyPaidExpenses = $legacyPaidQuery->get();

        $methodOutTotals = [];
        foreach ($vendorPayments as $vp) {
            $mKey = strtolower(trim((string) ($vp->payment_method ?? '')));
            $methodOutTotals[$mKey] = bcadd($methodOutTotals[$mKey] ?? '0.00', $admissionReportService->toDecimal($vp->amount), 2);
        }
        foreach ($legacyPaidExpenses as $le) {
            $mKey = strtolower(trim((string) ($le->payment_method ?? '')));
            $methodOutTotals[$mKey] = bcadd($methodOutTotals[$mKey] ?? '0.00', $admissionReportService->toDecimal($le->amount), 2);
        }

        // Method Breakdown and Pure Physical Cash Flow (Cash Collections - Cash Outflow)
        $methodSummary = [];
        $totalCashIn = '0.00';
        $totalCashOut = '0.00';

        foreach ($this->supportedMethods as $method) {
            $lowerMethod = strtolower($method);
            $in = $methodInTotals[$lowerMethod] ?? '0.00';
            $out = $methodOutTotals[$lowerMethod] ?? '0.00';
            $net = bcsub($in, $out, 2);

            $methodSummary[$method] = [
                'method' => $method,
                'in' => $in,
                'out' => $out,
                'net' => $net,
            ];

            if ($lowerMethod === 'cash') {
                $totalCashIn = $in;
                $totalCashOut = $out;
            }
        }

        // Unclassified / Custom payment methods if any exist
        $knownMethodsLower = array_map('strtolower', $this->supportedMethods);
        $otherIn = '0.00';
        foreach ($methodInTotals as $mKey => $mAmt) {
            if (!in_array($mKey, $knownMethodsLower, true)) {
                $otherIn = bcadd($otherIn, $mAmt, 2);
            }
        }
        $otherOut = '0.00';
        foreach ($methodOutTotals as $mKey => $mAmt) {
            if (!in_array($mKey, $knownMethodsLower, true)) {
                $otherOut = bcadd($otherOut, $mAmt, 2);
            }
        }
        if (bccomp($otherIn, '0.00', 2) > 0 || bccomp($otherOut, '0.00', 2) > 0) {
            $methodSummary['Other'] = [
                'method' => 'Other',
                'in' => $otherIn,
                'out' => $otherOut,
                'net' => bcsub($otherIn, $otherOut, 2),
            ];
        }

        // Physical Cash In Hand Movement = Cash Collections - Cash Outflow
        $netCashMovement = bcsub($totalCashIn, $totalCashOut, 2);

        // 5. Income by Fee Category (Head-Decomposed for Admission Invoices, Pure BCMath)
        $incomeByCategory = collect($incomeCategoryTotals)->map(function (string $amt, string $name) use ($totalIncome, $incomeCategoryPaymentIds, $admissionReportService) {
            return [
                'name' => $name,
                'amount' => $amt,
                'count' => count($incomeCategoryPaymentIds[$name] ?? []),
                'percentage' => $admissionReportService->percentage($amt, $totalIncome),
            ];
        })->sort(function (array $a, array $b): int {
            $cmp = bccomp($b['amount'], $a['amount'], 2);
            return $cmp !== 0 ? $cmp : strcmp($a['name'], $b['name']);
        })->values();

        // 6. Expense by Expense Category (Pure BCMath)
        $categoryBreakdown = [];
        foreach ($expenses as $expense) {
            if ($expense->items && $expense->items->isNotEmpty()) {
                foreach ($expense->items as $item) {
                    $catName = $item->category?->name ?? ($expense->category?->name ?? 'Uncategorized Expense');
                    if (!isset($categoryBreakdown[$catName])) {
                        $categoryBreakdown[$catName] = ['name' => $catName, 'amount' => '0.00', 'count' => 0];
                    }
                    $categoryBreakdown[$catName]['amount'] = bcadd($categoryBreakdown[$catName]['amount'], $admissionReportService->toDecimal($item->line_total), 2);
                    $categoryBreakdown[$catName]['count']++;
                }
            } else {
                $catName = $expense->category?->name ?? 'Uncategorized Expense';
                if (!isset($categoryBreakdown[$catName])) {
                    $categoryBreakdown[$catName] = ['name' => $catName, 'amount' => '0.00', 'count' => 0];
                }
                $categoryBreakdown[$catName]['amount'] = bcadd($categoryBreakdown[$catName]['amount'], $admissionReportService->toDecimal($expense->amount), 2);
                $categoryBreakdown[$catName]['count']++;
            }
        }

        $expenseByCategory = collect($categoryBreakdown)->map(function (array $item) use ($totalExpense, $admissionReportService) {
            $amt = $item['amount'];
            return [
                'name' => $item['name'],
                'amount' => $amt,
                'count' => $item['count'],
                'percentage' => $admissionReportService->percentage($amt, $totalExpense),
            ];
        })->sort(function (array $a, array $b): int {
            $cmp = bccomp($b['amount'], $a['amount'], 2);
            return $cmp !== 0 ? $cmp : strcmp($a['name'], $b['name']);
        })->values();

        // 7. Contextual Billed Invoices & Accounts Receivable (Cancelled Strictly Excluded, Pure BCMath)
        $invoicesQuery = FeeInvoice::where('status', '!=', 'Cancelled');
        if ($branchId) {
            $invoicesQuery->where(function ($bq) use ($branchId, $admissionAvailable): void {
                if ($admissionAvailable) {
                    $bq->where(function ($admQ) use ($branchId): void {
                        $admQ->whereNotNull('admission_fee_structure_id')
                             ->where('branch_id', $branchId);
                    })->orWhere(function ($legQ) use ($branchId): void {
                        $legQ->whereNull('admission_fee_structure_id')
                             ->whereHas('student', fn ($q) => $q->where('branch_id', $branchId));
                    });
                } else {
                    $bq->whereHas('student', fn ($q) => $q->where('branch_id', $branchId));
                }
            });
        }
        $invoicesQuery->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('due_date', [$startDate, $endDate])
              ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        });
        $billedCols = ['id', 'amount', 'discount', 'net_amount', 'due_amount'];
        if (Schema::hasColumn('fee_invoices', 'fine_amount')) {
            $billedCols[] = 'fine_amount';
        }
        $billedInvoices = $invoicesQuery->get($billedCols);
        $totalBilledGross = '0.00';
        $totalBilledDiscount = '0.00';
        $totalBilledNet = '0.00';
        $totalOutstandingDue = '0.00';
        foreach ($billedInvoices as $inv) {
            $invFine = $inv->fineAmountDecimal();
            $totalBilledGross = bcadd($totalBilledGross, bcadd($admissionReportService->toDecimal($inv->amount), $invFine, 2), 2);
            $totalBilledDiscount = bcadd($totalBilledDiscount, $admissionReportService->toDecimal($inv->discount), 2);
            $totalBilledNet = bcadd($totalBilledNet, bcadd($admissionReportService->toDecimal($inv->net_amount), $invFine, 2), 2);
            $totalOutstandingDue = bcadd($totalOutstandingDue, $admissionReportService->clampNonNegative($admissionReportService->toDecimal($inv->due_amount)), 2);
        }

        // 8. Contextual Accounts Payable & Unsettled Liabilities (Pure BCMath)
        $billsDueQuery = Expense::outstanding();
        $obDueQuery = VendorOpeningBalance::whereIn('status', ['Unpaid', 'Partially Paid']);
        if ($branchId) {
            $billsDueQuery->where('branch_id', $branchId);
            $obDueQuery->where('branch_id', $branchId);
        }
        $totalPayableBillsDue = '0.00';
        foreach ($billsDueQuery->get() as $bill) {
            $totalPayableBillsDue = bcadd($totalPayableBillsDue, $admissionReportService->toDecimal($bill->due_amount), 2);
        }
        $totalPayableOBDue = '0.00';
        foreach ($obDueQuery->get() as $ob) {
            $totalPayableOBDue = bcadd($totalPayableOBDue, $admissionReportService->toDecimal($ob->due_amount), 2);
        }
        $totalPayableDue = bcadd($totalPayableBillsDue, $totalPayableOBDue, 2);

        // Lookup data for filters
        $branches = Branch::all();
        $selectedBranch = $branchId ? $branches->firstWhere('id', $branchId) : null;

        return compact(
            'startDate',
            'endDate',
            'branchId',
            'paymentMethod',
            'branches',
            'selectedBranch',
            'payments',
            'expenses',
            'totalIncome',
            'totalExpense',
            'netSurplus',
            'netCashMovement',
            'totalCashIn',
            'totalCashOut',
            'totalCollectionsCount',
            'totalExpensesCount',
            'methodSummary',
            'incomeByCategory',
            'expenseByCategory',
            'totalBilledGross',
            'totalBilledDiscount',
            'totalBilledNet',
            'totalOutstandingDue',
            'totalPayableBillsDue',
            'totalPayableOBDue',
            'totalPayableDue'
        );
    }
}
