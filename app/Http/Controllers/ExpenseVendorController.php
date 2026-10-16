<?php

namespace App\Http\Controllers;

use App\Models\ExpenseVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseVendorController extends Controller
{
    /**
     * Return active vendors for dropdown selection via AJAX or API.
     */
    public function index(Request $request)
    {
        $vendors = ExpenseVendor::active()->orderBy('name', 'asc')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'vendors' => $vendors,
            ]);
        }

        return response()->json(['vendors' => $vendors]);
    }

    /**
     * Store a new vendor via AJAX (e.g. from the Expense modal).
     */
    public function storeAjax(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:expense_vendors,name',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        $vendor = ExpenseVendor::create([
            'name' => trim($validated['name']),
            'contact_person' => !empty($validated['contact_person']) ? trim($validated['contact_person']) : null,
            'phone' => !empty($validated['phone']) ? trim($validated['phone']) : null,
            'address' => !empty($validated['address']) ? trim($validated['address']) : null,
            'status' => $validated['status'] ?? 'Active',
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor created successfully.',
            'vendor' => $vendor,
        ], 201);
    }
}
