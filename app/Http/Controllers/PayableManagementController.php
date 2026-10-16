<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ExpenseVendor;
use App\Services\VendorPayableService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayableManagementController extends Controller
{
    protected VendorPayableService $payableService;

    public function __construct(VendorPayableService $payableService)
    {
        $this->payableService = $payableService;
    }

    /**
     * Display the comprehensive Payables & Due Management Dashboard.
     */
    public function index(Request $request)
    {
        $branchId = $request->branch_id;
        $dashboardData = $this->payableService->getPayablesDashboardData($branchId ? (int) $branchId : null);

        $vendors = ExpenseVendor::active()->orderBy('name', 'asc')->get();
        $branches = Branch::all();

        return view('pages.accounts.payables.index', array_merge($dashboardData, [
            'vendors'  => $vendors,
            'branches' => $branches,
            'branchId' => $branchId,
        ]));
    }

    /**
     * Display a specific vendor's chronological ledger with running balance.
     */
    public function ledger(Request $request, $vendorId)
    {
        $vendor = ExpenseVendor::findOrFail($vendorId);
        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        $branchId = $request->branch_id;

        $ledgerData = $this->payableService->getVendorLedger(
            vendorId: (int) $vendorId,
            startDate: $startDate,
            endDate: $endDate,
            branchId: $branchId ? (int) $branchId : null
        );

        $branches = Branch::all();

        return view('pages.accounts.payables.ledger', array_merge($ledgerData, [
            'vendor'    => $vendor,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'branchId'  => $branchId,
            'branches'  => $branches,
        ]));
    }

    /**
     * Display printable vendor statement / ledger.
     */
    public function printLedger(Request $request, $vendorId)
    {
        $vendor = ExpenseVendor::findOrFail($vendorId);
        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        $branchId = $request->branch_id;

        $ledgerData = $this->payableService->getVendorLedger(
            vendorId: (int) $vendorId,
            startDate: $startDate,
            endDate: $endDate,
            branchId: $branchId ? (int) $branchId : null
        );

        return view('pages.accounts.payables.ledger_print', array_merge($ledgerData, [
            'vendor'    => $vendor,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]));
    }

    /**
     * Store a pre-existing vendor opening balance.
     */
    public function storeOpeningBalance(Request $request)
    {
        $request->validate([
            'vendor_id'       => 'required|exists:expense_vendors,id',
            'opening_balance' => 'required|numeric|min:0.01',
            'effective_date'  => 'required|date',
            'reference_no'    => 'nullable|string|max:100',
            'reference_date'  => 'nullable|date',
            'branch_id'       => 'nullable|exists:branches,id',
            'note'            => 'nullable|string|max:1000',
        ]);

        try {
            $ob = $this->payableService->recordOpeningBalance(
                vendorId: (int) $request->vendor_id,
                amount: (float) $request->opening_balance,
                effectiveDate: $request->effective_date,
                refNo: $request->reference_no,
                refDate: $request->reference_date,
                branchId: $request->branch_id ? (int) $request->branch_id : null,
                note: $request->note,
                userId: Auth::id()
            );

            return redirect()->back()
                ->with('success', "Opening balance of ৳ " . number_format($ob->opening_balance, 2) . " registered for {$ob->vendor->name}.");

        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to register opening balance: ' . $e->getMessage());
        }
    }
}
