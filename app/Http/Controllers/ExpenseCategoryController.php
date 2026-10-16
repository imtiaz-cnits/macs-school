<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryController extends Controller
{
    /**
     * Display listing of expense categories.
     */
    public function index(Request $request)
    {
        $query = ExpenseCategory::withCount('expenses')->with('creator');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['Active', 'Inactive'])) {
            $query->where('status', $request->status);
        }

        $categories = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        return view('pages.accounts.categories', compact('categories'));
    }

    /**
     * Store a new expense category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:Active,Inactive',
        ]);

        ExpenseCategory::create([
            'name' => trim($request->name),
            'description' => $request->filled('description') ? trim($request->description) : null,
            'status' => $request->status,
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('accounts.categories.index')
            ->with('success', 'Expense category created successfully.');
    }

    /**
     * Store a new expense category via AJAX (e.g. from the Expense modal).
     */
    public function storeAjax(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        $category = ExpenseCategory::create([
            'name' => trim($validated['name']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'status' => $validated['status'] ?? 'Active',
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'category' => $category,
        ], 201);
    }

    /**
     * Update an existing expense category.
     */
    public function update(Request $request, $id)
    {
        $category = ExpenseCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:Active,Inactive',
        ]);

        $category->update([
            'name' => trim($request->name),
            'description' => $request->filled('description') ? trim($request->description) : null,
            'status' => $request->status,
        ]);

        return redirect()->route('accounts.categories.index')
            ->with('success', 'Expense category updated successfully.');
    }

    /**
     * Delete an expense category safely.
     * Prevents deletion if any expenses are already associated with it.
     */
    public function destroy(Request $request, $id)
    {
        $category = ExpenseCategory::withCount('expenses')->findOrFail($id);

        if ($category->expenses_count > 0) {
            return redirect()->route('accounts.categories.index')
                ->with('error', "Cannot delete category '{$category->name}' because it has {$category->expenses_count} recorded expense(s). You can change its status to Inactive instead.");
        }

        $category->delete();

        return redirect()->route('accounts.categories.index')
            ->with('success', 'Expense category deleted successfully.');
    }
}
