<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class ShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $shifts = Shift::latest()->get();
            return response()->json(['status' => 'success', 'shiftData' => $shifts], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['shift_name' => 'required|string|max:255']);
        try {
            Shift::create([
                'shift_name' => $request->shift_name,
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Shift Created'], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Creation Failed'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $data = Shift::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate(['shift_name' => 'required|string|max:255']);
        try {
            $shift = Shift::findOrFail($id);
            $shift->update(['shift_name' => $request->shift_name]);
            return response()->json(['status' => 'success', 'message' => 'Update Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Update Failed'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $shift = Shift::findOrFail($id);
            $shift->delete();
            return response()->json(['status' => 'success', 'message' => 'Delete Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Delete Failed'], 500);
        }
    }
}
