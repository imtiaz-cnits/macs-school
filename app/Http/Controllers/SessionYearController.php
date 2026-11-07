<?php

namespace App\Http\Controllers;

use App\Models\SessionYear;
use App\Services\FeeGenerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class SessionYearController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $sessions = SessionYear::latest()->get();
            $service = app(FeeGenerationService::class);
            $sessions->transform(function ($s) use ($service) {
                try {
                    $statusInfo = $service->getSessionGenerationStatus((int)$s->id);
                    $s->generation_status = $statusInfo['status'] ?? 'pending';
                    $s->status_details = $statusInfo;
                } catch (\Throwable $e) {
                    $s->generation_status = 'pending';
                    $s->status_details = null;
                }
                return $s;
            });
            return response()->json(['status' => 'success', 'sessionData' => $sessions], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch data'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['session_name' => 'required|string|max:255']);
        try {
            $actorId = $request->user()?->id ?? \Illuminate\Support\Facades\Auth::id();
            $session = SessionYear::create([
                'session_name' => $request->session_name,
                'user_id' => $actorId,
            ]);

            // Readiness-aware generation evaluation
            $evalResult = null;
            try {
                $evalResult = app(FeeGenerationService::class)->evaluateAndGenerateForSession((int)$session->id, $actorId);
            } catch (\Throwable $e) {
                Log::warning("Fee generation initial evaluation failed for session {$session->id}: " . $e->getMessage());
                $evalResult = ['evaluated' => false, 'status' => 'failed', 'reason' => $e->getMessage()];
            }

            return response()->json([
                'status'             => 'success',
                'message'            => 'Session Created',
                'id'                 => $session->id,
                'generation_status'  => $evalResult['status'] ?? 'pending',
                'generation_details' => $evalResult,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Creation Failed'], 500);
        }
    }

    public function generateAnnualFees(Request $request, $id): JsonResponse
    {
        try {
            $session = SessionYear::findOrFail($id);
            $userId = $request->user()?->id ?? \Illuminate\Support\Facades\Auth::id();
            $result = app(FeeGenerationService::class)->evaluateAndGenerateForSession((int)$session->id, $userId);

            if (($result['status'] ?? '') === 'setup_conflict' || ($result['has_conflict'] ?? false)) {
                return response()->json([
                    'status'  => 'conflict',
                    'message' => $result['reason'] ?? 'Inspection Required: Play Setup Conflict Detected. Generation rejected.',
                    'details' => $result,
                ], 422);
            }

            if (!$result['evaluated']) {
                return response()->json([
                    'status'  => 'pending',
                    'message' => $result['reason'] ?? 'Prerequisites not met for annual invoice generation.',
                    'details' => $result,
                ], 200);
            }

            if (isset($result['result']) && !($result['result']['success'] ?? false)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $result['result']['error'] ?? 'Invoice generation failed.',
                    'details' => $result,
                ], 500);
            }

            $generated = $result['generated'] ?? 0;
            $skipped   = $result['skipped'] ?? 0;

            return response()->json([
                'status'  => 'success',
                'message' => "Annual Fee Generation completed for session '{$session->session_name}'. Generated: {$generated}, Skipped: {$skipped}.",
                'details' => $result,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'not_found',
                'message' => "Academic session ID {$id} was not found.",
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to process annual fee generation: ' . $e->getMessage(),
            ], 500);
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