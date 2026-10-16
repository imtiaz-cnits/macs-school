<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseAuditLog;
use App\Models\ExpenseCategory;
use App\Services\ExpenseReceiptService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    protected ExpenseReceiptService $receiptService;

    public function __construct(ExpenseReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Display a listing of expenses with date range, category, branch, and method filters.
     */
    public function index(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        $categoryId = $request->expense_category_id;
        $branchId = $request->branch_id;
        $paymentMethod = $request->payment_method;
        $search = trim($request->search ?? '');

        // Base Query with relationships
        $query = Expense::with(['category', 'branch', 'creator', 'auditLogs']);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $query->where('expense_date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $query->where('expense_date', '<=', $endDate);
        }

        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                  ->orWhere('paid_to', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%");
            });
        }

        // Summary Calculations for Cards
        $today = date('Y-m-d');
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');

        $todayTotal = (float) Expense::where('expense_date', $today)->sum('amount');
        $thisMonthTotal = (float) Expense::whereBetween('expense_date', [$currentMonthStart, $currentMonthEnd])->sum('amount');

        // Filtered Results Clone for Aggregations
        $filteredQuery = clone $query;
        $filteredTotal = (float) $filteredQuery->sum('amount');
        $filteredCount = $filteredQuery->count();

        // Method Breakdown on filtered dataset
        $methodBreakdown = (clone $query)->select('payment_method', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payment_method')
            ->pluck('total_amount', 'payment_method')
            ->toArray();

        $expenses = $query->orderBy('expense_date', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate(15)
                          ->withQueryString();

        $categories = ExpenseCategory::orderBy('name', 'asc')->get();
        $activeCategories = ExpenseCategory::active()->orderBy('name', 'asc')->get();
        $branches = Branch::all();

        return view('pages.accounts.expenses', compact(
            'expenses',
            'categories',
            'activeCategories',
            'branches',
            'todayTotal',
            'thisMonthTotal',
            'filteredTotal',
            'filteredCount',
            'methodBreakdown',
            'startDate',
            'endDate',
            'categoryId',
            'branchId',
            'paymentMethod',
            'search'
        ));
    }

    /**
     * Store a new expense entry with voucher number and optional private receipt.
     */
    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Bank,bKash,Nagad,Cheque',
            'paid_to' => 'required|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'note' => 'nullable|string|max:1000',
        ]);

        $storedReceiptPath = null;

        // 1. If receipt file uploaded, store into private storage first
        if ($request->hasFile('receipt')) {
            $storedReceiptPath = $this->receiptService->storeReceipt($request->file('receipt'));
        }

        DB::beginTransaction();
        try {
            // 2. Generate unique voucher number safely
            $voucherNo = Expense::generateVoucherNo();

            // 3. Create expense record
            $expense = Expense::create([
                'voucher_no' => $voucherNo,
                'expense_category_id' => $request->expense_category_id,
                'branch_id' => $request->branch_id ?: null,
                'expense_date' => $request->expense_date,
                'amount' => round((float) $request->amount, 2),
                'payment_method' => $request->payment_method,
                'paid_to' => trim($request->paid_to),
                'reference_no' => $request->filled('reference_no') ? trim($request->reference_no) : null,
                'receipt_file' => $storedReceiptPath,
                'note' => $request->filled('note') ? trim($request->note) : null,
                'user_id' => Auth::id(),
            ]);

            // 4. Create creation audit log
            ExpenseAuditLog::record(
                expense: $expense,
                action: 'created',
                operator: Auth::user(),
                reason: 'Initial expense entry'
            );

            DB::commit();

            return redirect()->route('accounts.expenses.index')
                ->with('success', "Expense voucher {$voucherNo} recorded successfully.");

        } catch (Exception $e) {
            DB::rollBack();

            // Clean up newly uploaded receipt file to avoid storage orphan
            if ($storedReceiptPath) {
                $this->receiptService->deleteReceiptFile($storedReceiptPath);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to save expense: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing expense entry with audit tracking and safe file replacement.
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::with('category')->findOrFail($id);

        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,Bank,bKash,Nagad,Cheque',
            'paid_to' => 'required|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'remove_receipt' => 'nullable|boolean',
            'edit_reason' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        $oldReceiptPath = $expense->receipt_file;
        $newReceiptPath = null;
        $shouldDeleteOldReceipt = false;

        if ($request->hasFile('receipt')) {
            $newReceiptPath = $this->receiptService->storeReceipt($request->file('receipt'));
            $shouldDeleteOldReceipt = !empty($oldReceiptPath);
        } elseif ($request->boolean('remove_receipt')) {
            $shouldDeleteOldReceipt = !empty($oldReceiptPath);
        }

        DB::beginTransaction();
        try {
            $previousData = $expense->toArray();

            $expense->expense_category_id = $request->expense_category_id;
            $expense->branch_id = $request->branch_id ?: null;
            $expense->expense_date = $request->expense_date;
            $expense->amount = round((float) $request->amount, 2);
            $expense->payment_method = $request->payment_method;
            $expense->paid_to = trim($request->paid_to);
            $expense->reference_no = $request->filled('reference_no') ? trim($request->reference_no) : null;
            $expense->note = $request->filled('note') ? trim($request->note) : null;

            if ($newReceiptPath) {
                $expense->receipt_file = $newReceiptPath;
            } elseif ($request->boolean('remove_receipt')) {
                $expense->receipt_file = null;
            }

            $expense->save();

            // Record update audit log
            ExpenseAuditLog::record(
                expense: $expense,
                action: 'updated',
                operator: Auth::user(),
                reason: $request->filled('edit_reason') ? trim($request->edit_reason) : 'Expense details updated',
                previousData: $previousData,
                newData: $expense->fresh()->toArray()
            );

            DB::commit();

            // Safely delete old receipt file only after DB commit succeeds
            if ($shouldDeleteOldReceipt) {
                $this->receiptService->deleteReceiptFile($oldReceiptPath);
            }

            return redirect()->route('accounts.expenses.index')
                ->with('success', "Expense voucher {$expense->voucher_no} updated successfully.");

        } catch (Exception $e) {
            DB::rollBack();

            // Clean up newly uploaded receipt file on transaction failure
            if ($newReceiptPath) {
                $this->receiptService->deleteReceiptFile($newReceiptPath);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update expense: ' . $e->getMessage());
        }
    }

    /**
     * Delete an expense entry safely while preserving permanent audit logs.
     */
    public function destroy(Request $request, $id)
    {
        $expense = Expense::with('category')->findOrFail($id);
        $voucherNo = $expense->voucher_no;
        $receiptFile = $expense->receipt_file;
        $reason = $request->filled('delete_reason') ? trim($request->delete_reason) : 'Expense deleted by administrator';

        DB::beginTransaction();
        try {
            // Record permanent delete audit log with full snapshot before deletion
            ExpenseAuditLog::record(
                expense: $expense,
                action: 'deleted',
                operator: Auth::user(),
                reason: $reason,
                previousData: $expense->toArray()
            );

            $expense->delete();

            DB::commit();

            // Clean up receipt file only after DB commit succeeds
            if ($receiptFile) {
                $this->receiptService->deleteReceiptFile($receiptFile);
            }

            return redirect()->route('accounts.expenses.index')
                ->with('success', "Expense voucher {$voucherNo} deleted and recorded in audit log.");

        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }

    /**
     * Render printable debit voucher slip.
     */
    public function printVoucher($id)
    {
        $expense = Expense::with(['category', 'branch', 'creator'])->findOrFail($id);

        return view('pages.accounts.voucher_print', compact('expense'));
    }
}
