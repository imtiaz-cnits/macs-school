<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ExpenseVendor;
use App\Models\VendorPayment;
use App\Services\ExpenseReceiptService;
use App\Services\VendorPayableService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorPaymentController extends Controller
{
    protected VendorPayableService $payableService;
    protected ExpenseReceiptService $receiptService;

    public function __construct(
        VendorPayableService $payableService,
        ExpenseReceiptService $receiptService
    ) {
        $this->payableService = $payableService;
        $this->receiptService = $receiptService;
    }

    /**
     * Display a listing of recorded vendor payments.
     */
    public function index(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        $vendorId = $request->vendor_id;
        $branchId = $request->branch_id;
        $paymentMethod = $request->payment_method;
        $search = trim($request->search ?? '');

        $query = VendorPayment::with(['vendor', 'branch', 'creator', 'allocations.allocatable']);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        } elseif (!empty($startDate)) {
            $query->where('payment_date', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $query->where('payment_date', '<=', $endDate);
        }

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_no', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Summary Aggregations
        $today = date('Y-m-d');
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');

        $todayTotal = (float) VendorPayment::where('payment_date', $today)->sum('amount');
        $thisMonthTotal = (float) VendorPayment::whereBetween('payment_date', [$currentMonthStart, $currentMonthEnd])->sum('amount');

        $filteredQuery = clone $query;
        $filteredTotal = (float) $filteredQuery->sum('amount');
        $filteredCount = $filteredQuery->count();

        // Payment Method breakdown on filtered dataset
        $methodBreakdown = (clone $query)->select('payment_method', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payment_method')
            ->pluck('total_amount', 'payment_method')
            ->toArray();

        $payments = $query->orderBy('payment_date', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate(15)
                          ->withQueryString();

        $vendors = ExpenseVendor::orderBy('name', 'asc')->get();
        $branches = Branch::all();

        return view('pages.accounts.vendor_payments.index', compact(
            'payments',
            'vendors',
            'branches',
            'todayTotal',
            'thisMonthTotal',
            'filteredTotal',
            'filteredCount',
            'methodBreakdown',
            'startDate',
            'endDate',
            'vendorId',
            'branchId',
            'paymentMethod',
            'search'
        ));
    }

    /**
     * Show the form for creating a new vendor payment with dynamic bill allocations.
     */
    public function create(Request $request)
    {
        $vendors = ExpenseVendor::active()->orderBy('name', 'asc')->get();
        $branches = Branch::all();
        $preselectedVendorId = $request->query('vendor_id');

        return view('pages.accounts.vendor_payments.create', compact('vendors', 'branches', 'preselectedVendorId'));
    }

    /**
     * Store a newly created vendor payment and its bill allocations atomically.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vendor_id'      => 'required|exists:expense_vendors,id',
            'branch_id'      => 'nullable|exists:branches,id',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string|in:Cash,Bank,bKash,Nagad,Cheque,Rocket',
            'amount'         => 'required|numeric|min:0.01',
            'reference_no'   => 'nullable|string|max:100',
            'receipt'        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'note'           => 'nullable|string|max:1000',
            'allocations'    => 'required|array|min:1',
            'allocations.*.type'   => 'required|in:bill,opening_balance',
            'allocations.*.id'     => 'required|integer',
            'allocations.*.amount' => 'required|numeric|gt:0',
        ]);

        $storedReceiptPath = null;
        if ($request->hasFile('receipt')) {
            $storedReceiptPath = $this->receiptService->storeReceipt($request->file('receipt'));
        }

        try {
            $payment = $this->payableService->recordPayment(
                vendorId: (int) $request->vendor_id,
                totalAmount: (float) $request->amount,
                paymentDate: $request->payment_date,
                paymentMethod: $request->payment_method,
                refNo: $request->reference_no,
                receiptPath: $storedReceiptPath,
                note: $request->note,
                allocations: $request->allocations,
                branchId: $request->branch_id ? (int) $request->branch_id : null,
                userId: Auth::id()
            );

            return redirect()->route('accounts.vendor-payments.show', $payment->id)
                ->with('success', "Payment {$payment->payment_no} recorded successfully with ৳ " . number_format($payment->amount, 2) . ".");

        } catch (ValidationException $ve) {
            if ($storedReceiptPath) {
                $this->receiptService->deleteReceiptFile($storedReceiptPath);
            }
            throw $ve;
        } catch (Exception $e) {
            if ($storedReceiptPath) {
                $this->receiptService->deleteReceiptFile($storedReceiptPath);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified payment transaction with allocation details.
     */
    public function show($id)
    {
        $payment = VendorPayment::with(['vendor', 'branch', 'creator', 'allocations.allocatable'])->findOrFail($id);

        return view('pages.accounts.vendor_payments.show', compact('payment'));
    }

    /**
     * AJAX endpoint: return outstanding bills and opening balances for a vendor.
     */
    public function getVendorBillsAjax($vendorId)
    {
        $vendor = ExpenseVendor::findOrFail($vendorId);
        $data = $this->payableService->getVendorOutstandingItems((int) $vendorId);

        return response()->json([
            'success'           => true,
            'vendor'            => [
                'id'             => $vendor->id,
                'name'           => $vendor->name,
                'contact_person' => $vendor->contact_person,
                'phone'          => $vendor->phone,
            ],
            'items'             => $data['items'],
            'total_outstanding' => $data['total_outstanding'],
        ]);
    }
}
