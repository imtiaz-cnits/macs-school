<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $branches = Branch::latest()->get();
            return response()->json(['status' => 'success', 'branchData' => $branches], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['branch_name' => 'required|string|max:255']);
        try {
            Branch::create([
                'branch_name' => $request->branch_name,
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Branch Created'], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Creation Failed'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $data = Branch::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate(['branch_name' => 'required|string|max:255']);
        try {
            $branch = Branch::findOrFail($id);
            $branch->update(['branch_name' => $request->branch_name]);
            return response()->json(['status' => 'success', 'message' => 'Update Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Update Failed'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $branch = Branch::findOrFail($id);
            $branch->delete();
            return response()->json(['status' => 'success', 'message' => 'Delete Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Delete Failed'], 500);
        }
    }
}