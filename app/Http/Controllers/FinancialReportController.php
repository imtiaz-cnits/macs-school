<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\VendorOpeningBalance;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $startDate = $request->filled('start_date') ? $request->start_date : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->end_date : date('Y-m-d');
        $branchId = $request->branch_id;
        $paymentMethod = $request->payment_method;

        // 1. Fee Payments Query (ACTUAL COLLECTED INCOME ONLY)
        $paymentsQuery = FeePayment::with(['student.schoolClass', 'student.branch', 'invoice.feeSetup.category', 'collector'])
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($branchId) {
            $paymentsQuery->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        if ($paymentMethod) {
            $paymentsQuery->where('payment_method', 'like', $paymentMethod);
        }

        $payments = $paymentsQuery->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get();
        $totalIncome = (float) $payments->sum('paid_amount');
        $totalCollectionsCount = $payments->count();

        // 2. Expenses Query (ACTUAL INCURRED EXPENSES RECORDED IN PERIOD)
        $expensesQuery = Expense::with(['category', 'branch', 'creator', 'items.category', 'vendor'])
            ->whereBetween('expense_date', [$startDate, $endDate]);

        if ($branchId) {
            $expensesQuery->where('branch_id', $branchId);
        }

        if ($paymentMethod) {
            $expensesQuery->where('payment_method', 'like', $paymentMethod);
        }

        $expenses = $expensesQuery->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();
        $totalExpense = (float) $expenses->sum('amount');
        $totalExpensesCount = $expenses->count();

        // 3. Overall Net Surplus / Deficit (Total Income - Total Expense)
        $netSurplus = $totalIncome - $totalExpense;

        // 4. Actual Cash Outflow & Method Breakdown:
        // Cash outflow occurs on payment settlement, not on unpaid bills.
        // A. Vendor payments recorded during the period
        $vendorPaymentsQuery = VendorPayment::whereBetween('payment_date', [$startDate, $endDate]);
        if ($branchId) {
            $vendorPaymentsQuery->where('branch_id', $branchId);
        }
        if ($paymentMethod) {
            $vendorPaymentsQuery->where('payment_method', 'like', $paymentMethod);
        }
        $vendorPayments = $vendorPaymentsQuery->get();

        // B. Backward-compatible legacy paid expenses or ad-hoc non-vendor paid expenses
        // Only include expenses that do NOT have any canonical payments or payment allocations
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

        $outflowItems = collect();
        foreach ($vendorPayments as $vp) {
            $outflowItems->push([
                'method' => $vp->payment_method,
                'amount' => (float) $vp->amount,
            ]);
        }
        foreach ($legacyPaidExpenses as $le) {
            $outflowItems->push([
                'method' => $le->payment_method,
                'amount' => (float) $le->amount,
            ]);
        }

        // Method Breakdown and Pure Physical Cash Flow (Cash Collections - Cash Expenses)
        $methodSummary = [];
        $totalCashIn = 0;
        $totalCashOut = 0;

        foreach ($this->supportedMethods as $method) {
            $in = (float) $payments->filter(fn($p) => strcasecmp(trim($p->payment_method ?? ''), $method) === 0)->sum('paid_amount');
            $out = (float) $outflowItems->filter(fn($e) => strcasecmp(trim($e['method'] ?? ''), $method) === 0)->sum('amount');
            $net = $in - $out;

            $methodSummary[$method] = [
                'method' => $method,
                'in' => $in,
                'out' => $out,
                'net' => $net,
            ];

            if (strtolower($method) === 'cash') {
                $totalCashIn = $in;
                $totalCashOut = $out;
            }
        }

        // Unclassified / Custom payment methods if any exist
        $knownMethodsLower = array_map('strtolower', $this->supportedMethods);
        $otherIn = (float) $payments->filter(fn($p) => !in_array(strtolower(trim($p->payment_method ?? '')), $knownMethodsLower))->sum('paid_amount');
        $otherOut = (float) $outflowItems->filter(fn($e) => !in_array(strtolower(trim($e['method'] ?? '')), $knownMethodsLower))->sum('amount');
        if ($otherIn > 0 || $otherOut > 0) {
            $methodSummary['Other'] = [
                'method' => 'Other',
                'in' => $otherIn,
                'out' => $otherOut,
                'net' => $otherIn - $otherOut,
            ];
        }

        // Physical Cash In Hand Movement = Cash Collections - Cash Outflow
        $netCashMovement = $totalCashIn - $totalCashOut;

        // 5. Income by Fee Category
        $incomeByCategory = $payments->groupBy(function ($p) {
            return $p->invoice?->feeSetup?->category?->name ?? 'General Fee';
        })->map(function ($group, $name) use ($totalIncome) {
            $amt = (float) $group->sum('paid_amount');
            $pct = $totalIncome > 0 ? round(($amt / $totalIncome) * 100, 1) : 0;
            return [
                'name' => $name,
                'amount' => $amt,
                'count' => $group->count(),
                'percentage' => $pct,
            ];
        })->sortByDesc('amount')->values();

        // 6. Expense by Expense Category (Item-level category attribution with legacy voucher fallback)
        $categoryBreakdown = [];
        foreach ($expenses as $expense) {
            if ($expense->items && $expense->items->isNotEmpty()) {
                foreach ($expense->items as $item) {
                    $catName = $item->category?->name ?? ($expense->category?->name ?? 'Uncategorized Expense');
                    if (!isset($categoryBreakdown[$catName])) {
                        $categoryBreakdown[$catName] = ['name' => $catName, 'amount' => 0.0, 'count' => 0];
                    }
                    $categoryBreakdown[$catName]['amount'] += (float) $item->line_total;
                    $categoryBreakdown[$catName]['count']++;
                }
            } else {
                $catName = $expense->category?->name ?? 'Uncategorized Expense';
                if (!isset($categoryBreakdown[$catName])) {
                    $categoryBreakdown[$catName] = ['name' => $catName, 'amount' => 0.0, 'count' => 0];
                }
                $categoryBreakdown[$catName]['amount'] += (float) $expense->amount;
                $categoryBreakdown[$catName]['count']++;
            }
        }

        $expenseByCategory = collect($categoryBreakdown)->map(function ($item) use ($totalExpense) {
            $amt = round((float) $item['amount'], 2);
            $pct = $totalExpense > 0 ? round(($amt / $totalExpense) * 100, 1) : 0;
            return [
                'name' => $item['name'],
                'amount' => $amt,
                'count' => $item['count'],
                'percentage' => $pct,
            ];
        })->sortByDesc('amount')->values();

        // 7. Contextual Billed Invoices & Accounts Receivable (NOT counted as income)
        // Cancelled invoices are strictly excluded so they do not artificially distort billed totals or discounts
        $invoicesQuery = FeeInvoice::where('status', '!=', 'Cancelled');
        if ($branchId) {
            $invoicesQuery->whereHas('student', fn($q) => $q->where('branch_id', $branchId));
        }
        $invoicesQuery->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('due_date', [$startDate, $endDate])
              ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        });
        $totalBilledGross = (float) $invoicesQuery->sum('amount');
        $totalBilledDiscount = (float) $invoicesQuery->sum('discount');
        $totalBilledNet = (float) $invoicesQuery->sum('net_amount');
        $totalOutstandingDue = (float) $invoicesQuery->sum('due_amount');

        // 8. Contextual Accounts Payable & Unsettled Liabilities (NOT counted as cash outflow)
        $billsDueQuery = Expense::outstanding();
        $obDueQuery = VendorOpeningBalance::whereIn('status', ['Unpaid', 'Partially Paid']);
        if ($branchId) {
            $billsDueQuery->where('branch_id', $branchId);
            $obDueQuery->where('branch_id', $branchId);
        }
        $totalPayableBillsDue = (float) $billsDueQuery->get()->sum('due_amount');
        $totalPayableOBDue = (float) $obDueQuery->get()->sum('due_amount');
        $totalPayableDue = round($totalPayableBillsDue + $totalPayableOBDue, 2);

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
