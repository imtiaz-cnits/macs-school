<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StaffAttendance;
use App\Services\ZktecoService;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    protected $zktecoService;

    public function __construct(ZktecoService $zktecoService)
    {
        $this->zktecoService = $zktecoService;
    }

    /**
     * Display the staff attendance dashboard
     */
    public function index(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $search = $request->input('search');

        // Check current biometric machine connection details
        $connection = $this->zktecoService->getConnectionStatus();

        // Query daily staff attendances
        $query = StaffAttendance::with('teacher.user')->where('date', $date);

        if ($search) {
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->get()->map(function ($log) {
            // Formatted check-in and check-out values
            $log->formatted_in = $log->check_in ? Carbon::parse($log->check_in)->format('h:i A') : '--';
            $log->formatted_out = $log->check_out ? Carbon::parse($log->check_out)->format('h:i A') : '--';
            return $log;
        });

        // Statistics
        $totalPresent = $logs->whereIn('status', ['Present', 'Late'])->count();
        $totalLate = $logs->where('status', 'Late')->count();
        $totalAbsent = $logs->where('status', 'Absent')->count();

        return view('pages.staff_attendance.index', compact('logs', 'connection', 'date', 'search', 'totalPresent', 'totalLate', 'totalAbsent'));
    }

    /**
     * AJAX action to sync logs from device
     */
    public function sync(Request $request)
    {
        try {
            $date = $request->input('date', date('Y-m-d'));
            $count = $this->zktecoService->syncLogs($date);

            return response()->json([
                'success' => true,
                'message' => "Synced {$count} staff attendance logs."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force generate simulated dummy logs for the day
     */
    public function simulate(Request $request)
    {
        try {
            $date = Carbon::parse($request->input('date', date('Y-m-d')));
            $count = $this->zktecoService->generateSimulationLogs($date);

            return response()->json([
                'success' => true,
                'message' => "Simulated swipe logs for {$count} staff members."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
