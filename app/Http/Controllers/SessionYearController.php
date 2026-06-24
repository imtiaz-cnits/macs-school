<?php

namespace App\Http\Controllers;

use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class SessionYearController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $sessions = SessionYear::latest()->get();
            return response()->json(['status' => 'success', 'sessionData' => $sessions], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['session_name' => 'required|string|max:255']);
        try {
            SessionYear::create([
                'session_name' => $request->session_name,
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Session Created'], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Creation Failed'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $data = SessionYear::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Not Found'], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate(['session_name' => 'required|string|max:255']);
        try {
            $session = SessionYear::findOrFail($id);
            $session->update(['session_name' => $request->session_name]);
            return response()->json(['status' => 'success', 'message' => 'Update Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Update Failed'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $session = SessionYear::findOrFail($id);
            $session->delete();
            return response()->json(['status' => 'success', 'message' => 'Delete Successful'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Delete Failed'], 500);
        }
    }
}