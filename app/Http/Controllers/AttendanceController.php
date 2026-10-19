<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // ডেট এবং টাইমের জন্য
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function getStudents(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'       => 'nullable|exists:branches,id',
            'session_year_id' => 'nullable|exists:session_years,id',
            'class_id'        => 'nullable|exists:classes,id',
            'section_id'      => 'nullable|exists:sections,id',
            'attendance_date' => 'nullable|date',
            'search'          => 'nullable|string',
        ]);

        try {
            $query = Student::where('students.status', 'Active');

            if ($request->filled('branch_id')) $query->where('students.branch_id', $request->branch_id);
            if ($request->filled('session_year_id')) $query->where('students.session_year_id', $request->session_year_id);
            if ($request->filled('class_id')) $query->where('students.class_id', $request->class_id);
            if ($request->filled('section_id')) $query->where('students.section_id', $request->section_id);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('students.student_name', 'like', "%{$search}%")
                      ->orWhere('students.student_identity', 'like', "%{$search}%")
                      ->orWhere('students.guardian_mobile', 'like', "%{$search}%")
                      ->orWhere('students.roll_number', 'like', "%{$search}%");
                });
            }

            // Calculate stats based on the filtered query (cloning the query before leftJoin and pagination)
            $allStudentIds = (clone $query)->pluck('students.id');
            $totalCount = $allStudentIds->count();
            
            $date = $request->input('attendance_date', Carbon::today('Asia/Dhaka')->toDateString());
            
            $presentCount = Attendance::whereIn('student_id', $allStudentIds)
                ->where('attendance_date', $date)
                ->whereIn('status', ['Present', 'Late'])
                ->count();
                
            $absentCount = Attendance::whereIn('student_id', $allStudentIds)
                ->where('attendance_date', $date)
                ->where('status', 'Absent')
                ->count();

            $orderBy = 'attendances.updated_at DESC, CAST(students.roll_number AS UNSIGNED) ASC, students.roll_number ASC';
            if ($request->filled('class_id')) {
                $orderBy = 'CAST(students.roll_number AS UNSIGNED) ASC, students.roll_number ASC';
            }

            $students = $query->select('students.id', 'students.student_name', 'students.roll_number', 'students.class_id', 'students.section_id')
                              ->leftJoin('attendances', function($join) use ($date) {
                                  $join->on('students.id', '=', 'attendances.student_id')
                                       ->where('attendances.attendance_date', '=', $date);
                              })
                              ->selectRaw('attendances.status as attendance_status, attendances.remarks, attendances.updated_at as attendance_updated_at')
                              ->with(['schoolClass', 'section'])
                              ->orderByRaw($orderBy)
                              ->paginate(15);

            foreach ($students as $student) {
                $className = $student->schoolClass ? $student->schoolClass->class_name : 'N/A';
                $sectionName = $student->section ? $student->section->section_name : 'N/A';
                $student->class_section_name = "{$className} - {$sectionName}";
            }

            return response()->json([
                'status' => 'success', 
                'students' => $students,
                'stats' => [
                    'total' => $totalCount,
                    'present' => $presentCount,
                    'absent' => $absentCount
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Database Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncBiometric(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'       => 'nullable|exists:branches,id',
            'session_year_id' => 'nullable|exists:session_years,id',
            'class_id'        => 'nullable|exists:classes,id',
            'section_id'      => 'nullable|exists:sections,id',
            'teacher_id'      => 'nullable|exists:teachers,id',
            'attendance_date' => 'required|date',
        ]);

        try {
            $date = $request->attendance_date;
            $classId = $request->class_id;
            $sectionId = $request->section_id;
            $branchId = $request->branch_id;
            $sessionYearId = $request->session_year_id;
            $teacherId = $request->teacher_id;
            $creatorId = Auth::id() ?? 1;

            // 1. Get raw logs grouped by card number
            $zkService = app(\App\Services\ZktecoService::class);
            $cardSwipes = $zkService->getRawLogsByCard($date);

            // 2. Fetch all active students in this class/section
            $studentQuery = Student::where('status', 'Active');
            if ($classId) $studentQuery->where('class_id', $classId);
            if ($sectionId) $studentQuery->where('section_id', $sectionId);
            if ($branchId) $studentQuery->where('branch_id', $branchId);
            if ($sessionYearId) $studentQuery->where('session_year_id', $sessionYearId);
            
            $students = $studentQuery->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found matching the selected filters.'
                ], 400);
            }

            DB::beginTransaction();

            $syncedCount = 0;
            $absentStudentIds = [];
            $persistedPunches = [];

            foreach ($students as $student) {
                $status = 'Absent';
                $remarks = 'Absent (Biometric Check)';
                
                $times = null;
                if (!empty($student->card_number) && isset($cardSwipes[$student->card_number])) {
                    $times = $cardSwipes[$student->card_number];
                } elseif (isset($cardSwipes["id:{$student->id}"])) {
                    $times = $cardSwipes["id:{$student->id}"];
                }

                if ($times) {
                    sort($times);
                    $checkIn = $times[0];
                    $checkOut = count($times) > 1 ? end($times) : null;
                    
                    $attendanceService = app(\App\Services\AttendanceService::class);
                    $shift = $student->shift;
                    if (!$shift) {
                        $shift = \App\Models\Shift::where('type', 'student')->first();
                    }

                    $status = 'Present';
                    if ($shift) {
                        $punchCarbon = Carbon::parse($date . ' ' . $checkIn, 'Asia/Dhaka');
                        $status = $attendanceService->calculateStatus($punchCarbon, $shift);
                    }
                    
                    if ($checkOut) {
                        $remarks = "Card Swiped (In: {$checkIn}, Out: {$checkOut})";
                    } else {
                        $remarks = "Card Swiped (In: {$checkIn})";
                    }
                    $syncedCount++;
                } else {
                    $absentStudentIds[] = $student->id;
                }

                $persisted = Attendance::updateOrCreate(
                    [
                        'student_id'      => $student->id,
                        'attendance_date' => $date,
                    ],
                    [
                        'branch_id'       => $student->branch_id,
                        'session_year_id' => $student->session_year_id,
                        'class_id'        => $student->class_id,
                        'section_id'      => $student->section_id,
                        'teacher_id'      => $teacherId,
                        'user_id'         => $creatorId,
                        'status'          => $status,
                        'remarks'         => $remarks,
                    ]
                );

                if ($times && in_array($status, ['Present', 'Late'])) {
                    $persistedPunches[] = [
                        'student'    => $student,
                        'attendance' => $persisted,
                        'times'      => $times,
                    ];
                }
            }

            DB::commit();

            // Trigger guardian notification only after successful DB persistence for real device punches
            $notifier = app(\App\Services\AttendanceSmsNotifier::class);
            foreach ($persistedPunches as $punchItem) {
                $notifier->notifyPunch($punchItem['student'], $punchItem['attendance'], $punchItem['times'], 'k60_live');
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully synced attendance logs. Marked {$syncedCount} Present/Late, " . count($absentStudentIds) . " Absent.",
                'synced_count' => $syncedCount,
                'absent_count' => count($absentStudentIds)
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Biometric sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reportIndex(Request $request)
    {
        $branches = Branch::all();
        $sessions = SessionYear::orderBy('id', 'desc')->get();
        $classes = Classes::all(); // Ordered academically via global scope
        $sections = Section::all();

        $macsLogoPath = public_path('img/macs_logo.jpeg');
        $macsLogoSrc = file_exists($macsLogoPath) ? asset('img/macs_logo.jpeg') : (file_exists(public_path('img/logo.png')) ? asset('img/logo.png') : '');

        return view('pages.attendance.report', compact('branches', 'sessions', 'classes', 'sections', 'macsLogoSrc'));
    }

    /**
     * Resolve and validate request filters for attendance reporting
     */
    protected function resolveReportFilters(Request $request): array
    {
        $request->validate([
            'report_type'     => 'nullable|string|in:daily,monthly,student_history,absentee',
            'branch_id'       => 'nullable|exists:branches,id',
            'session_year_id' => 'nullable|exists:session_years,id',
            'class_id'        => 'nullable|exists:classes,id',
            'section_id'      => 'nullable|exists:sections,id',
            'date_from'       => 'nullable|date',
            'date_to'         => 'nullable|date',
            'attendance_date' => 'nullable|date',
            'student_id'      => 'nullable|exists:students,id',
            'search'          => 'nullable|string|max:100',
        ]);

        $reportType = $request->input('report_type', 'daily');
        if (!in_array($reportType, ['daily', 'monthly', 'student_history', 'absentee'])) {
            $reportType = 'daily';
        }

        $dateTo = $request->input('date_to');
        $dateFrom = $request->input('date_from');

        // Backward compatibility for single date input
        if (empty($dateFrom) && $request->filled('attendance_date')) {
            $dateFrom = $request->input('attendance_date');
        }

        if (empty($dateTo)) {
            $dateTo = Carbon::today('Asia/Dhaka')->toDateString();
        }

        if (empty($dateFrom)) {
            if (in_array($reportType, ['monthly', 'student_history'])) {
                $dateFrom = Carbon::parse($dateTo)->startOfMonth()->toDateString();
            } else {
                $dateFrom = $dateTo;
            }
        }

        // Chronological order safeguard
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            $temp = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo = $temp;
        }

        // Cap range to 366 days max
        if (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) > 366) {
            $dateFrom = Carbon::parse($dateTo)->subDays(366)->toDateString();
        }

        return [
            'report_type'     => $reportType,
            'branch_id'       => $request->input('branch_id'),
            'session_year_id' => $request->input('session_year_id'),
            'class_id'        => $request->input('class_id'),
            'section_id'      => $request->input('section_id'),
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
            'student_id'      => $request->input('student_id'),
            'search'          => trim((string)$request->input('search', '')),
        ];
    }

    /**
     * Build attendance reporting dataset and summary statistics
     */
    protected function buildReportQueryData(array $filters): array
    {
        // 1. Build Student Query in scope
        $studentQuery = Student::where('students.status', 'Active');
        if (!empty($filters['branch_id'])) $studentQuery->where('students.branch_id', $filters['branch_id']);
        if (!empty($filters['session_year_id'])) $studentQuery->where('students.session_year_id', $filters['session_year_id']);
        if (!empty($filters['class_id'])) $studentQuery->where('students.class_id', $filters['class_id']);
        if (!empty($filters['section_id'])) $studentQuery->where('students.section_id', $filters['section_id']);
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $studentQuery->where(function ($q) use ($search) {
                $q->where('students.student_name', 'like', "%{$search}%")
                  ->orWhere('students.student_identity', 'like', "%{$search}%")
                  ->orWhere('students.roll_number', 'like', "%{$search}%")
                  ->orWhere('students.guardian_mobile', 'like', "%{$search}%");
            });
        }

        $allMatchingStudentIds = (clone $studentQuery)->pluck('students.id');
        $totalStudents = $allMatchingStudentIds->count();

        // 2. Attendance base stats for date range
        $attBaseQuery = Attendance::whereBetween('attendance_date', [$filters['date_from'], $filters['date_to']]);
        if ($allMatchingStudentIds->isNotEmpty()) {
            $attBaseQuery->whereIn('student_id', $allMatchingStudentIds);
        } else {
            $attBaseQuery->whereRaw('1 = 0');
        }

        $presentCount = (clone $attBaseQuery)->where('status', 'Present')->count();
        $lateCount    = (clone $attBaseQuery)->where('status', 'Late')->count();
        $absentCount  = (clone $attBaseQuery)->where('status', 'Absent')->count();
        $totalRecords = (clone $attBaseQuery)->count();
        $totalAttended = $presentCount + $lateCount;
        $attendancePercentage = $totalRecords > 0 ? round(($totalAttended / $totalRecords) * 100, 1) : 0.0;

        $stats = [
            'total_students'        => $totalStudents,
            'total_records'         => $totalRecords,
            'present_count'         => $presentCount,
            'late_count'            => $lateCount,
            'absent_count'          => $absentCount,
            'total_attended'        => $totalAttended,
            'attendance_percentage' => $attendancePercentage,
        ];

        $reportType = $filters['report_type'];
        $rows = [];
        $selectedStudent = null;
        $studentStats = null;
        $candidateStudents = [];

        // 3. Handle Specific Report Type
        switch ($reportType) {
            case 'daily':
                if ($allMatchingStudentIds->isNotEmpty()) {
                    $attendances = Attendance::with([
                            'student.schoolClass',
                            'student.section',
                            'student.branch',
                            'student.sessionYear',
                            'class',
                            'section',
                            'teacher.user'
                        ])
                        ->whereBetween('attendance_date', [$filters['date_from'], $filters['date_to']])
                        ->whereIn('student_id', $allMatchingStudentIds)
                        ->orderBy('attendance_date', 'desc')
                        ->get();

                    // Sort by roll number naturally
                    $attendances = $attendances->sortBy([
                        ['attendance_date', 'desc'],
                        fn ($a, $b) => ((int)($a->student->roll_number ?? 999999)) <=> ((int)($b->student->roll_number ?? 999999)),
                    ])->values();

                    foreach ($attendances as $idx => $att) {
                        $st = $att->student;
                        $className = $att->class->class_name ?? ($st->schoolClass->class_name ?? 'N/A');
                        $sectionName = $att->section->section_name ?? ($st->section->section_name ?? 'N/A');
                        $teacherName = $att->teacher && $att->teacher->user ? $att->teacher->user->name : 'Biometric System';

                        $rows[] = [
                            'sl'                        => $idx + 1,
                            'id'                        => $att->id,
                            'student_id'                => $att->student_id,
                            'student_name'              => $st ? $st->student_name : 'N/A',
                            'student_identity'          => $st ? $st->student_identity : 'N/A',
                            'roll_number'               => $st ? ($st->roll_number ?? 'N/A') : 'N/A',
                            'class_name'                => $className,
                            'section_name'              => $sectionName,
                            'class_section_name'        => "{$className} - {$sectionName}",
                            'attendance_date'           => $att->attendance_date,
                            'attendance_date_formatted' => Carbon::parse($att->attendance_date)->format('d M, Y'),
                            'status'                    => $att->status,
                            'in_time'                   => $att->in_time ?? '—',
                            'out_time'                  => $att->out_time ?? '—',
                            'remarks'                   => $att->remarks ?? '—',
                            'teacher_name'              => $teacherName,
                        ];
                    }
                }
                break;

            case 'monthly':
                if ($allMatchingStudentIds->isNotEmpty()) {
                    $students = $studentQuery->with(['schoolClass', 'section', 'branch', 'sessionYear'])->get();

                    // Sort students by class order and roll number
                    $students = $students->sortBy([
                        fn ($a, $b) => ((int)($a->roll_number ?? 999999)) <=> ((int)($b->roll_number ?? 999999)),
                    ])->values();

                    $monthlyAggregates = Attendance::select(
                            'student_id',
                            DB::raw("COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_days"),
                            DB::raw("COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_days"),
                            DB::raw("COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_days"),
                            DB::raw("COUNT(id) as total_recorded")
                        )
                        ->whereBetween('attendance_date', [$filters['date_from'], $filters['date_to']])
                        ->whereIn('student_id', $allMatchingStudentIds)
                        ->groupBy('student_id')
                        ->get()
                        ->keyBy('student_id');

                    foreach ($students as $idx => $st) {
                        $stat = $monthlyAggregates[$st->id] ?? null;
                        $presentDays = $stat ? (int)$stat->present_days : 0;
                        $lateDays    = $stat ? (int)$stat->late_days : 0;
                        $absentDays  = $stat ? (int)$stat->absent_days : 0;
                        $totalRec    = $stat ? (int)$stat->total_recorded : 0;
                        $attended    = $presentDays + $lateDays;
                        $pct         = $totalRec > 0 ? round(($attended / $totalRec) * 100, 1) : 0.0;

                        $className = $st->schoolClass->class_name ?? 'N/A';
                        $sectionName = $st->section->section_name ?? 'N/A';

                        $rows[] = [
                            'sl'                    => $idx + 1,
                            'student_id'            => $st->id,
                            'student_name'          => $st->student_name,
                            'student_identity'      => $st->student_identity,
                            'roll_number'           => $st->roll_number ?? 'N/A',
                            'class_name'            => $className,
                            'section_name'          => $sectionName,
                            'class_section_name'    => "{$className} - {$sectionName}",
                            'present_days'          => $presentDays,
                            'late_days'             => $lateDays,
                            'absent_days'           => $absentDays,
                            'total_recorded'        => $totalRec,
                            'attended_days'         => $attended,
                            'attendance_percentage' => $pct,
                        ];
                    }
                }
                break;

            case 'student_history':
                // Check if a specific student is requested
                if (!empty($filters['student_id'])) {
                    $st = Student::with(['schoolClass', 'section', 'branch', 'sessionYear'])->find($filters['student_id']);
                    if ($st) {
                        $selectedStudent = [
                            'id'               => $st->id,
                            'student_name'     => $st->student_name,
                            'student_identity' => $st->student_identity,
                            'roll_number'      => $st->roll_number ?? 'N/A',
                            'class_name'       => $st->schoolClass->class_name ?? 'N/A',
                            'section_name'     => $st->section->section_name ?? 'N/A',
                            'branch_name'      => $st->branch->branch_name ?? 'Main Branch',
                            'session_name'     => $st->sessionYear->session_name ?? 'Current Session',
                            'guardian_mobile'  => $st->guardian_mobile ?? 'N/A',
                        ];

                        $records = Attendance::with(['class', 'section', 'teacher.user'])
                            ->where('student_id', $st->id)
                            ->whereBetween('attendance_date', [$filters['date_from'], $filters['date_to']])
                            ->orderBy('attendance_date', 'desc')
                            ->get();

                        $stPresent = $records->where('status', 'Present')->count();
                        $stLate    = $records->where('status', 'Late')->count();
                        $stAbsent  = $records->where('status', 'Absent')->count();
                        $stTotal   = $records->count();
                        $stAttended = $stPresent + $stLate;
                        $stPct     = $stTotal > 0 ? round(($stAttended / $stTotal) * 100, 1) : 0.0;

                        $studentStats = [
                            'total_recorded'        => $stTotal,
                            'present_days'          => $stPresent,
                            'late_days'             => $stLate,
                            'absent_days'           => $stAbsent,
                            'attended_days'         => $stAttended,
                            'attendance_percentage' => $stPct,
                        ];

                        foreach ($records as $idx => $att) {
                            $rows[] = [
                                'sl'                        => $idx + 1,
                                'id'                        => $att->id,
                                'attendance_date'           => $att->attendance_date,
                                'attendance_date_formatted' => Carbon::parse($att->attendance_date)->format('d M, Y'),
                                'status'                    => $att->status,
                                'in_time'                   => $att->in_time ?? '—',
                                'out_time'                  => $att->out_time ?? '—',
                                'remarks'                   => $att->remarks ?? '—',
                                'teacher_name'              => $att->teacher && $att->teacher->user ? $att->teacher->user->name : 'Biometric System',
                            ];
                        }
                    }
                } else {
                    // Provide list of candidate students matching search/class/section
                    $candidates = (clone $studentQuery)->with(['schoolClass', 'section'])
                        ->orderByRaw('CAST(roll_number AS UNSIGNED) ASC, roll_number ASC')
                        ->take(50)
                        ->get();

                    foreach ($candidates as $c) {
                        $candidateStudents[] = [
                            'id'               => $c->id,
                            'student_name'     => $c->student_name,
                            'student_identity' => $c->student_identity,
                            'roll_number'      => $c->roll_number ?? 'N/A',
                            'class_name'       => $c->schoolClass->class_name ?? 'N/A',
                            'section_name'     => $c->section->section_name ?? 'N/A',
                        ];
                    }
                }
                break;

            case 'absentee':
                if ($allMatchingStudentIds->isNotEmpty()) {
                    $absentees = Attendance::with([
                            'student.schoolClass',
                            'student.section',
                            'student.branch',
                            'student.sessionYear',
                            'class',
                            'section'
                        ])
                        ->where('status', 'Absent')
                        ->whereBetween('attendance_date', [$filters['date_from'], $filters['date_to']])
                        ->whereIn('student_id', $allMatchingStudentIds)
                        ->orderBy('attendance_date', 'desc')
                        ->get();

                    $absentees = $absentees->sortBy([
                        ['attendance_date', 'desc'],
                        fn ($a, $b) => ((int)($a->student->roll_number ?? 999999)) <=> ((int)($b->student->roll_number ?? 999999)),
                    ])->values();

                    foreach ($absentees as $idx => $att) {
                        $st = $att->student;
                        $className = $att->class->class_name ?? ($st->schoolClass->class_name ?? 'N/A');
                        $sectionName = $att->section->section_name ?? ($st->section->section_name ?? 'N/A');

                        $rows[] = [
                            'sl'                        => $idx + 1,
                            'id'                        => $att->id,
                            'student_id'                => $att->student_id,
                            'student_name'              => $st ? $st->student_name : 'N/A',
                            'student_identity'          => $st ? $st->student_identity : 'N/A',
                            'roll_number'               => $st ? ($st->roll_number ?? 'N/A') : 'N/A',
                            'class_name'                => $className,
                            'section_name'              => $sectionName,
                            'class_section_name'        => "{$className} - {$sectionName}",
                            'attendance_date'           => $att->attendance_date,
                            'attendance_date_formatted' => Carbon::parse($att->attendance_date)->format('d M, Y'),
                            'guardian_mobile'           => $st ? ($st->guardian_mobile ?? 'N/A') : 'N/A',
                            'status'                    => 'Absent',
                            'remarks'                   => $att->remarks ?? 'Absent (Biometric Check)',
                        ];
                    }
                }
                break;
        }

        return [
            'report_type'        => $reportType,
            'filters'            => $filters,
            'stats'              => $stats,
            'data'               => $rows,
            'student'            => $selectedStudent,
            'student_stats'      => $studentStats,
            'candidate_students' => $candidateStudents,
        ];
    }

    /**
     * AJAX endpoint returning JSON report data and statistics
     */
    public function getReportData(Request $request): JsonResponse
    {
        try {
            $filters = $this->resolveReportFilters($request);
            $reportData = $this->buildReportQueryData($filters);

            return response()->json([
                'status'             => 'success',
                'report_type'        => $reportData['report_type'],
                'filters'            => $reportData['filters'],
                'stats'              => $reportData['stats'],
                'data'               => $reportData['data'],
                'student'            => $reportData['student'],
                'student_stats'      => $reportData['student_stats'],
                'candidate_students' => $reportData['candidate_students'],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Attendance Report Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance report data as Excel-compatible CSV with UTF-8 BOM
     */
    public function exportReport(Request $request): StreamedResponse
    {
        $filters = $this->resolveReportFilters($request);
        $reportData = $this->buildReportQueryData($filters);

        $reportType = $filters['report_type'];
        $cleanDateFrom = Carbon::parse($filters['date_from'])->format('Ymd');
        $cleanDateTo   = Carbon::parse($filters['date_to'])->format('Ymd');
        $filename = "attendance_{$reportType}_report_{$cleanDateFrom}_{$cleanDateTo}.csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($reportData, $filters) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // School & Report Metadata Header Banner
            fputcsv($file, ['MACS SCHOOL & COLLEGE']);
            fputcsv($file, ['Report Title:', strtoupper(str_replace('_', ' ', $filters['report_type'])) . ' ATTENDANCE REPORT']);
            fputcsv($file, ['Date Range:', $filters['date_from'] . ' to ' . $filters['date_to']]);
            fputcsv($file, ['Generated At:', Carbon::now('Asia/Dhaka')->format('d M, Y h:i A')]);
            fputcsv($file, ['Summary Stats:', "Students: {$reportData['stats']['total_students']}", "Present: {$reportData['stats']['present_count']}", "Late: {$reportData['stats']['late_count']}", "Absent: {$reportData['stats']['absent_count']}", "Attendance Rate: {$reportData['stats']['attendance_percentage']}%"]);
            fputcsv($file, []); // Blank separator row

            switch ($filters['report_type']) {
                case 'daily':
                    fputcsv($file, ['SL', 'Student ID', 'Roll', 'Student Name', 'Class', 'Section', 'Date', 'Status', 'IN Time', 'OUT Time', 'Remarks', 'Recorded By']);
                    foreach ($reportData['data'] as $idx => $row) {
                        fputcsv($file, [
                            $idx + 1,
                            $row['student_identity'],
                            $row['roll_number'],
                            $row['student_name'],
                            $row['class_name'],
                            $row['section_name'],
                            $row['attendance_date'],
                            $row['status'],
                            $row['in_time'],
                            $row['out_time'],
                            $row['remarks'],
                            $row['teacher_name'],
                        ]);
                    }
                    break;

                case 'monthly':
                    fputcsv($file, ['SL', 'Student ID', 'Roll', 'Student Name', 'Class', 'Section', 'Present Days', 'Late Days', 'Absent Days', 'Total Recorded Days', 'Attended Days', 'Attendance %']);
                    foreach ($reportData['data'] as $idx => $row) {
                        fputcsv($file, [
                            $idx + 1,
                            $row['student_identity'],
                            $row['roll_number'],
                            $row['student_name'],
                            $row['class_name'],
                            $row['section_name'],
                            $row['present_days'],
                            $row['late_days'],
                            $row['absent_days'],
                            $row['total_recorded'],
                            $row['attended_days'],
                            $row['attendance_percentage'] . '%',
                        ]);
                    }
                    break;

                case 'student_history':
                    if (!empty($reportData['student'])) {
                        $st = $reportData['student'];
                        fputcsv($file, ['Student Name:', $st['student_name'], 'Student ID:', $st['student_identity'], 'Roll:', $st['roll_number']]);
                        fputcsv($file, ['Class:', $st['class_name'], 'Section:', $st['section_name'], 'Guardian Mobile:', $st['guardian_mobile']]);
                        if (!empty($reportData['student_stats'])) {
                            $stStats = $reportData['student_stats'];
                            fputcsv($file, [
                                'Summary:',
                                "Present: {$stStats['present_days']}",
                                "Late: {$stStats['late_days']}",
                                "Absent: {$stStats['absent_days']}",
                                "Recorded Days: {$stStats['total_recorded']}",
                                "Attendance Rate: {$stStats['attendance_percentage']}%"
                            ]);
                        }
                        fputcsv($file, []);
                    }
                    fputcsv($file, ['SL', 'Date', 'Status', 'IN Time', 'OUT Time', 'Remarks', 'Recorded By']);
                    foreach ($reportData['data'] as $idx => $row) {
                        fputcsv($file, [
                            $idx + 1,
                            $row['attendance_date'],
                            $row['status'],
                            $row['in_time'],
                            $row['out_time'],
                            $row['remarks'],
                            $row['teacher_name'] ?? 'Biometric System',
                        ]);
                    }
                    break;

                case 'absentee':
                    fputcsv($file, ['SL', 'Student ID', 'Roll', 'Student Name', 'Class', 'Section', 'Absent Date', 'Guardian Mobile', 'Remarks']);
                    foreach ($reportData['data'] as $idx => $row) {
                        fputcsv($file, [
                            $idx + 1,
                            $row['student_identity'],
                            $row['roll_number'],
                            $row['student_name'],
                            $row['class_name'],
                            $row['section_name'],
                            $row['attendance_date'],
                            $row['guardian_mobile'],
                            $row['remarks'],
                        ]);
                    }
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get the latest 15 attendance logs for the dashboard default view
     */
    public function getRecentLogs(): JsonResponse
    {
        try {
            $logs = Attendance::with(['student', 'class', 'section'])
                ->orderBy('created_at', 'desc')
                ->take(15)
                ->get();
                
            return response()->json([
                'status' => 'success',
                'logs' => $logs
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ZKTeco biometric machine connection status
     */
    public function getDeviceStatus(): JsonResponse
    {
        try {
            $zkService = app(\App\Services\ZktecoService::class);
            $status = $zkService->getConnectionStatus();
            
            return response()->json([
                'status' => 'success',
                'device' => $status
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
