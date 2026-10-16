<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseAuditLog;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\ExpenseVendor;
use App\Services\ExpenseReceiptService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\VendorPayableService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    protected ExpenseReceiptService $receiptService;
    protected VendorPayableService $payableService;

    public function __construct(
        ExpenseReceiptService $receiptService,
        VendorPayableService $payableService
    ) {
        $this->receiptService = $receiptService;
        $this->payableService = $payableService;
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
        $query = Expense::with(['category', 'branch', 'creator', 'auditLogs', 'vendor', 'items.category']);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $query->where('expense_date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $query->where('expense_date', '<=', $endDate);
        }

        if ($categoryId) {
            $query->where(function ($q) use ($categoryId) {
                $q->where('expense_category_id', $categoryId)
                  ->orWhereHas('items', function ($iq) use ($categoryId) {
                      $iq->where('expense_category_id', $categoryId);
                  });
            });
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
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
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
        $vendors = ExpenseVendor::active()->orderBy('name', 'asc')->get();
        $branches = Branch::all();

        return view('pages.accounts.expenses', compact(
            'expenses',
            'categories',
            'activeCategories',
            'vendors',
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
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'vendor_id' => 'nullable|exists:expense_vendors,id',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_type' => 'nullable|in:full,partial,unpaid',
            'initial_paid_amount' => 'nullable|numeric|gte:0',
            'payment_method' => 'nullable|string|in:Cash,Bank,bKash,Nagad,Cheque,Rocket,Credit / Payable,Due',
            'paid_to' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'note' => 'nullable|string|max:1000',
            'items' => 'nullable|array|min:1',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.expense_category_id' => 'nullable|exists:expense_categories,id',
            'items.*.quantity' => 'required_with:items|numeric|gt:0|max:999999.99',
            'items.*.unit_rate' => 'required_with:items|numeric|gte:0|max:99999999.99',
            'items.*.note' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        $paymentType = $request->input('payment_type', 'full');
        if ($paymentType !== 'unpaid' && empty($request->payment_method)) {
            throw ValidationException::withMessages([
                'payment_method' => ['Please select a payment method for immediate payment.'],
            ]);
        }

        // Resolve vendor and recipient name (One vendor per voucher rule)
        $vendorId = $request->filled('vendor_id') ? (int) $request->vendor_id : null;
        $vendor = null;

        // Credit / Partial / Unpaid vouchers strictly require an active registered vendor
        if (in_array($paymentType, ['unpaid', 'partial'], true)) {
            if (!$vendorId) {
                throw ValidationException::withMessages([
                    'vendor_id' => ['A registered active vendor is required for unpaid and partially paid vouchers.'],
                ]);
            }
        }

        if ($vendorId) {
            $vendor = ExpenseVendor::find($vendorId);
            if (!$vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => ['The selected vendor does not exist.'],
                ]);
            }
            if ($vendor->status !== 'Active') {
                throw ValidationException::withMessages([
                    'vendor_id' => ['The selected vendor is inactive and cannot be selected for new vouchers.'],
                ]);
            }
        }

        $paidTo = $request->filled('paid_to') ? trim($request->paid_to) : ($vendor?->name ?? '');
        if (empty($paidTo)) {
            throw ValidationException::withMessages([
                'paid_to' => ['The recipient or vendor name is required.'],
            ]);
        }

        // Resolve line items and authoritative voucher total server-side
        $itemsData = [];
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            foreach ($request->items as $index => $rawItem) {
                $itemName = trim($rawItem['item_name'] ?? '');
                $qty = round((float) ($rawItem['quantity'] ?? 0), 2);
                $rate = round((float) ($rawItem['unit_rate'] ?? 0), 2);
                $itemCatId = !empty($rawItem['expense_category_id'])
                    ? (int) $rawItem['expense_category_id']
                    : ($request->filled('expense_category_id') ? (int) $request->expense_category_id : null);

                if (empty($itemName)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.item_name" => ['Item description is required.'],
                    ]);
                }
                if (!$itemCatId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.expense_category_id" => ['Please select an expense category for this item.'],
                    ]);
                }
                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => ['Item quantity must be greater than zero.'],
                    ]);
                }
                if ($rate < 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_rate" => ['Item unit rate cannot be negative.'],
                    ]);
                }

                $lineTotal = round($qty * $rate, 2);
                $itemsData[] = [
                    'expense_category_id' => $itemCatId,
                    'item_name' => $itemName,
                    'quantity' => $qty,
                    'unit_rate' => $rate,
                    'line_total' => $lineTotal,
                    'note' => !empty($rawItem['note']) ? trim($rawItem['note']) : null,
                ];
            }

            $voucherTotal = round(array_sum(array_column($itemsData, 'line_total')), 2);
            if ($voucherTotal <= 0) {
                throw ValidationException::withMessages([
                    'items' => ['Total voucher amount must be greater than zero.'],
                ]);
            }

            // Server-side rejection of manipulated totals
            if ($request->filled('amount') && abs(((float) $request->amount) - $voucherTotal) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => ["Submitted voucher total ({$request->amount}) does not match calculated line items total ({$voucherTotal})."],
                ]);
            }
        } elseif ($request->filled('amount') && (float) $request->amount > 0) {
            // Backward-compatible fallback for legacy single amount payload
            $voucherTotal = round((float) $request->amount, 2);
            $legacyCatId = $request->filled('expense_category_id') ? (int) $request->expense_category_id : null;
            $itemsData[] = [
                'expense_category_id' => $legacyCatId,
                'item_name' => $request->filled('note') ? trim($request->note) : 'General Expense',
                'quantity' => 1.00,
                'unit_rate' => $voucherTotal,
                'line_total' => $voucherTotal,
                'note' => null,
            ];
        } else {
            throw ValidationException::withMessages([
                'items' => ['At least one valid line item is required.'],
            ]);
        }

        // Determine payment status and settlement amounts
        $initialPaidInput = $request->filled('initial_paid_amount') ? round((float) $request->initial_paid_amount, 2) : 0.00;
        if ($paymentType === 'unpaid') {
            $paidAmount = 0.00;
            $paymentStatus = 'Unpaid';
            $finalPaymentMethod = $request->payment_method ?: 'Due';
        } elseif ($paymentType === 'partial') {
            if ($initialPaidInput <= 0) {
                throw ValidationException::withMessages([
                    'initial_paid_amount' => ['Initial payment amount must be greater than zero for partial payment.'],
                ]);
            }
            if ($initialPaidInput >= $voucherTotal) {
                throw ValidationException::withMessages([
                    'initial_paid_amount' => ['Initial payment cannot equal or exceed total voucher amount. Use Full Payment instead.'],
                ]);
            }
            $paidAmount = $initialPaidInput;
            $paymentStatus = 'Partially Paid';
            $finalPaymentMethod = $request->payment_method;
        } else {
            // full payment
            $paidAmount = $voucherTotal;
            $paymentStatus = 'Paid';
            $finalPaymentMethod = $request->payment_method;
        }

        $storedReceiptPath = null;

        // 1. If receipt file uploaded, store into private storage first
        if ($request->hasFile('receipt')) {
            $storedReceiptPath = $this->receiptService->storeReceipt($request->file('receipt'));
        }

        DB::beginTransaction();
        try {
            // 2. Generate unique voucher number safely
            $voucherNo = Expense::generateVoucherNo();

            $headerCategoryId = $request->filled('expense_category_id')
                ? (int) $request->expense_category_id
                : ($itemsData[0]['expense_category_id'] ?? null);

            // 3. Create expense record with authoritative voucher total & payables status
            $expense = Expense::create([
                'voucher_no'          => $voucherNo,
                'expense_category_id' => $headerCategoryId,
                'vendor_id'           => $vendorId,
                'branch_id'           => $request->branch_id ?: null,
                'expense_date'        => $request->expense_date,
                'due_date'            => $request->due_date ?: null,
                'amount'              => $voucherTotal,
                'paid_amount'         => $paidAmount,
                'payment_status'      => $paymentStatus,
                'payment_method'      => $finalPaymentMethod,
                'paid_to'             => $paidTo,
                'reference_no'        => $request->filled('reference_no') ? trim($request->reference_no) : null,
                'receipt_file'        => $storedReceiptPath,
                'note'                => $request->filled('note') ? trim($request->note) : null,
                'user_id'             => Auth::id(),
            ]);

            // 4. Create line items
            foreach ($itemsData as $item) {
                $expense->items()->create($item);
            }

            // 5. If immediate payment was recorded and vendor is specified, record canonical vendor payment
            if ($paidAmount > 0 && $vendorId) {
                $this->payableService->recordVoucherPayment(
                    expense: $expense,
                    paidAmount: $paidAmount,
                    paymentMethod: $finalPaymentMethod,
                    refNo: $expense->reference_no,
                    receiptPath: $expense->receipt_file,
                    note: $expense->note,
                    userId: Auth::id()
                );
            }

            // 6. Create creation audit log capturing items snapshot
            $newSnapshot = $expense->fresh(['items.category', 'vendor', 'category'])->toArray();
            ExpenseAuditLog::record(
                expense: $expense,
                action: 'created',
                operator: Auth::user(),
                reason: 'Initial expense entry (' . $paymentStatus . ') with ' . count($itemsData) . ' item(s)',
                newData: $newSnapshot
            );

            DB::commit();

            return redirect()->route('accounts.expenses.index')
                ->with('success', "Expense voucher {$voucherNo} recorded successfully (" . $paymentStatus . ") with ৳ " . number_format($voucherTotal, 2) . ".");

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
        $expense = Expense::with(['category', 'items', 'vendor'])->findOrFail($id);

        $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'branch_id' => 'nullable|exists:branches,id',
            'vendor_id' => 'nullable|exists:expense_vendors,id',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date',
            'payment_method' => 'required|string|in:Cash,Bank,bKash,Nagad,Cheque,Rocket',
            'paid_to' => 'nullable|string|max:150',
            'reference_no' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'remove_receipt' => 'nullable|boolean',
            'edit_reason' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'items' => 'nullable|array|min:1',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.expense_category_id' => 'nullable|exists:expense_categories,id',
            'items.*.quantity' => 'required_with:items|numeric|gt:0|max:999999.99',
            'items.*.unit_rate' => 'required_with:items|numeric|gte:0|max:99999999.99',
            'items.*.note' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        // Resolve vendor and recipient name
        $vendorId = $request->filled('vendor_id') ? (int) $request->vendor_id : null;
        $vendor = null;

        // Unpaid or partially paid vouchers must retain a registered vendor
        if ($expense->payment_status !== 'Paid' && !$vendorId) {
            throw ValidationException::withMessages([
                'vendor_id' => ['A registered active vendor is required for unpaid and partially paid vouchers.'],
            ]);
        }

        if ($vendorId) {
            $vendor = ExpenseVendor::find($vendorId);
            if (!$vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => ['The selected vendor does not exist.'],
                ]);
            }
            // If vendor is being changed to a new vendor, ensure active
            if ($vendor->id !== $expense->vendor_id && $vendor->status !== 'Active') {
                throw ValidationException::withMessages([
                    'vendor_id' => ['The selected vendor is inactive and cannot be selected.'],
                ]);
            }
        }

        $paidTo = $request->filled('paid_to') ? trim($request->paid_to) : ($vendor?->name ?? $expense->paid_to);
        if (empty($paidTo)) {
            throw ValidationException::withMessages([
                'paid_to' => ['The recipient or vendor name is required.'],
            ]);
        }

        // Enforce vendor change protection: changing vendor on existing voucher requires explicit reason
        $isVendorChanging = false;
        if ($expense->vendor_id !== null && $vendorId !== null && (int) $expense->vendor_id !== (int) $vendorId) {
            $isVendorChanging = true;
        } elseif ($expense->vendor_id === null && $vendorId !== null) {
            $isVendorChanging = true;
        } elseif ($vendorId === null && $expense->vendor_id !== null) {
            $isVendorChanging = true;
        } elseif (!empty($expense->paid_to) && $paidTo !== $expense->paid_to) {
            $isVendorChanging = true;
        }

        if ($isVendorChanging && empty($request->edit_reason)) {
            throw ValidationException::withMessages([
                'edit_reason' => ['Changing the vendor or payee on an existing voucher requires providing a reason for the audit log.'],
            ]);
        }

        // Resolve line items and authoritative voucher total server-side
        $itemsData = [];
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            foreach ($request->items as $index => $rawItem) {
                $itemName = trim($rawItem['item_name'] ?? '');
                $qty = round((float) ($rawItem['quantity'] ?? 0), 2);
                $rate = round((float) ($rawItem['unit_rate'] ?? 0), 2);
                $itemCatId = !empty($rawItem['expense_category_id'])
                    ? (int) $rawItem['expense_category_id']
                    : ($request->filled('expense_category_id') ? (int) $request->expense_category_id : null);

                if (empty($itemName)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.item_name" => ['Item description is required.'],
                    ]);
                }
                if (!$itemCatId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.expense_category_id" => ['Please select an expense category for this item.'],
                    ]);
                }
                if ($qty <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => ['Item quantity must be greater than zero.'],
                    ]);
                }
                if ($rate < 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit_rate" => ['Item unit rate cannot be negative.'],
                    ]);
                }

                $lineTotal = round($qty * $rate, 2);
                $itemsData[] = [
                    'expense_category_id' => $itemCatId,
                    'item_name' => $itemName,
                    'quantity' => $qty,
                    'unit_rate' => $rate,
                    'line_total' => $lineTotal,
                    'note' => !empty($rawItem['note']) ? trim($rawItem['note']) : null,
                ];
            }

            $voucherTotal = round(array_sum(array_column($itemsData, 'line_total')), 2);
            if ($voucherTotal <= 0) {
                throw ValidationException::withMessages([
                    'items' => ['Total voucher amount must be greater than zero.'],
                ]);
            }

            // Server-side rejection of manipulated totals
            if ($request->filled('amount') && abs(((float) $request->amount) - $voucherTotal) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => ["Submitted voucher total ({$request->amount}) does not match calculated line items total ({$voucherTotal})."],
                ]);
            }
        } elseif ($request->filled('amount') && (float) $request->amount > 0) {
            $voucherTotal = round((float) $request->amount, 2);
            $legacyCatId = $request->filled('expense_category_id') ? (int) $request->expense_category_id : null;
            $itemsData[] = [
                'expense_category_id' => $legacyCatId,
                'item_name' => $request->filled('note') ? trim($request->note) : 'General Expense',
                'quantity' => 1.00,
                'unit_rate' => $voucherTotal,
                'line_total' => $voucherTotal,
                'note' => null,
            ];
        } else {
            throw ValidationException::withMessages([
                'items' => ['At least one valid line item is required.'],
            ]);
        }

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
            $previousData['items'] = $expense->items->toArray();

            $headerCategoryId = $request->filled('expense_category_id')
                ? (int) $request->expense_category_id
                : ($itemsData[0]['expense_category_id'] ?? null);

            $expense->expense_category_id = $headerCategoryId;
            $expense->vendor_id = $vendorId;
            $expense->branch_id = $request->branch_id ?: null;
            $expense->expense_date = $request->expense_date;
            if ($request->has('due_date')) {
                $expense->due_date = $request->due_date ?: null;
            }
            $expense->amount = $voucherTotal;
            $expense->payment_method = $request->payment_method;
            $expense->paid_to = $paidTo;
            $expense->reference_no = $request->filled('reference_no') ? trim($request->reference_no) : null;
            $expense->note = $request->filled('note') ? trim($request->note) : null;

            if ($newReceiptPath) {
                $expense->receipt_file = $newReceiptPath;
            } elseif ($request->boolean('remove_receipt')) {
                $expense->receipt_file = null;
            }

            $expense->updatePaymentStatus();

            // Replace line items atomically
            $expense->items()->delete();
            foreach ($itemsData as $item) {
                $expense->items()->create($item);
            }

            $newData = $expense->fresh(['items.category', 'vendor', 'category'])->toArray();

            // Record update audit log with items snapshot
            ExpenseAuditLog::record(
                expense: $expense,
                action: 'updated',
                operator: Auth::user(),
                reason: $request->filled('edit_reason') ? trim($request->edit_reason) : 'Expense details and line items updated',
                previousData: $previousData,
                newData: $newData
            );

            DB::commit();

            // Safely delete old receipt file only after DB commit succeeds
            if ($shouldDeleteOldReceipt) {
                $this->receiptService->deleteReceiptFile($oldReceiptPath);
            }

            return redirect()->route('accounts.expenses.index')
                ->with('success', "Expense voucher {$expense->voucher_no} updated successfully with ৳ " . number_format($voucherTotal, 2) . ".");

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
        $expense = Expense::with(['category', 'items', 'vendor', 'paymentAllocations.payment'])->findOrFail($id);
        $voucherNo = $expense->voucher_no;
        $receiptFile = $expense->receipt_file;
        $reason = $request->filled('delete_reason') ? trim($request->delete_reason) : 'Expense deleted by administrator';

        // Prevent deletion if subsequent payments were made against this voucher
        $hasSubsequentPayments = $expense->paymentAllocations
            ->contains(fn($alloc) => $alloc->payment && $alloc->payment->created_from !== 'voucher');

        if ($hasSubsequentPayments) {
            return redirect()->back()
                ->with('error', "Cannot delete voucher {$expense->voucher_no} because subsequent payments have already been allocated to it. Please adjust or delete those payments first.");
        }

        DB::beginTransaction();
        try {
            $previousData = $expense->toArray();
            $previousData['items'] = $expense->items->toArray();

            // Record permanent delete audit log with full snapshot before deletion
            ExpenseAuditLog::record(
                expense: $expense,
                action: 'deleted',
                operator: Auth::user(),
                reason: $reason,
                previousData: $previousData
            );

            $expense->items()->delete();
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
        $expense = Expense::with(['category', 'branch', 'creator', 'vendor', 'items.category'])->findOrFail($id);

        return view('pages.accounts.voucher_print', compact('expense'));
    }
}
