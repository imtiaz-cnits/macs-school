<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
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

        // 2. Expenses Query (ACTUAL SAVED EXPENSES)
        $expensesQuery = Expense::with(['category', 'branch', 'creator'])
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

        // 4. Method Breakdown and Pure Physical Cash Flow (Cash Collections - Cash Expenses)
        $methodSummary = [];
        $totalCashIn = 0;
        $totalCashOut = 0;

        foreach ($this->supportedMethods as $method) {
            $in = (float) $payments->filter(fn($p) => strcasecmp(trim($p->payment_method ?? ''), $method) === 0)->sum('paid_amount');
            $out = (float) $expenses->filter(fn($e) => strcasecmp(trim($e->payment_method ?? ''), $method) === 0)->sum('amount');
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
        $otherOut = (float) $expenses->filter(fn($e) => !in_array(strtolower(trim($e->payment_method ?? '')), $knownMethodsLower))->sum('amount');
        if ($otherIn > 0 || $otherOut > 0) {
            $methodSummary['Other'] = [
                'method' => 'Other',
                'in' => $otherIn,
                'out' => $otherOut,
                'net' => $otherIn - $otherOut,
            ];
        }

        // Physical Cash In Hand Movement = Cash Collections - Cash Expenses
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

        // 6. Expense by Expense Category
        $expenseByCategory = $expenses->groupBy(function ($e) {
            return $e->category?->name ?? 'Uncategorized Expense';
        })->map(function ($group, $name) use ($totalExpense) {
            $amt = (float) $group->sum('amount');
            $pct = $totalExpense > 0 ? round(($amt / $totalExpense) * 100, 1) : 0;
            return [
                'name' => $name,
                'amount' => $amt,
                'count' => $group->count(),
                'percentage' => $pct,
            ];
        })->sortByDesc('amount')->values();

        // 7. Contextual Billed Invoices & Accounts Receivable (NOT counted as income)
        $invoicesQuery = FeeInvoice::query();
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
            'totalOutstandingDue'
        );
    }
}
