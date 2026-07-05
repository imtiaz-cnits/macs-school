<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\SmsLog; // এসএমএস লগের জন্য
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // ডেট এবং টাইমের জন্য

class AttendanceController extends Controller
{
    public function getTeachers(): JsonResponse
    {
        try {
            $teachers = Teacher::with('user:id,name')
                ->get()
                ->map(function ($teacher) {
                    return [
                        'id'   => $teacher->id,
                        'name' => $teacher->user->name ?? 'No Name'
                    ];
                });

            return response()->json([
                'status' => 'success',
                'teacherData' => $teachers
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Teachers load failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStudents(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'       => 'nullable|exists:branches,id',
            'session_year_id' => 'nullable|exists:session_years,id',
            'class_id'        => 'required|exists:classes,id',
            'section_id'      => 'nullable|exists:sections,id',
            'attendance_date' => 'nullable|date',
        ]);

        try {
            $query = Student::query();

            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
            if ($request->filled('session_year_id')) $query->where('session_year_id', $request->session_year_id);
            if ($request->filled('class_id')) $query->where('class_id', $request->class_id);
            if ($request->filled('section_id')) $query->where('section_id', $request->section_id);

            $students = $query->select('id', 'student_name', 'roll_number')
                              ->orderBy('roll_number', 'asc')
                              ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'success', 
                    'students' => [], 
                    'message' => 'এই ক্রাইটেরিয়ায় কোনো স্টুডেন্ট পাওয়া যায়নি।'
                ], 200);
            }

            if ($request->filled('attendance_date')) {
                $attendances = Attendance::whereIn('student_id', $students->pluck('id'))
                    ->where('attendance_date', $request->attendance_date)
                    ->get()
                    ->pluck('status', 'student_id');

                foreach ($students as $student) {
                    $student->attendance_status = $attendances[$student->id] ?? null;
                }
            }

            return response()->json(['status' => 'success', 'students' => $students], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Database Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'       => 'nullable|exists:branches,id',
            'session_year_id' => 'nullable|exists:session_years,id',
            'class_id'        => 'required|exists:classes,id',
            'section_id'      => 'nullable|exists:sections,id',
            'teacher_id'      => 'required|exists:teachers,id',
            'attendance_date' => 'required|date',
            'attendance_data' => 'required|array',
        ]);

        try {
            // ১. ডাটাবেস ট্রানজ্যাকশন শুরু
            DB::beginTransaction();

            $date = $request->attendance_date;
            $creator_id = Auth::id();

            // অ্যাবসেন্ট স্টুডেন্টদের আইডি কালেক্ট করার জন্য অ্যারে
            $absentStudentIds = [];

            foreach ($request->attendance_data as $student_id => $status) {
                Attendance::updateOrCreate(
                    [
                        'student_id'      => $student_id,
                        'attendance_date' => $date,
                    ],
                    [
                        'branch_id'       => $request->branch_id,
                        'session_year_id' => $request->session_year_id,
                        'class_id'        => $request->class_id,
                        'section_id'      => $request->section_id,
                        'teacher_id'      => $request->teacher_id,
                        'user_id'         => $creator_id,
                        'status'          => $status,
                    ]
                );

                // যদি স্ট্যাটাস অ্যাবসেন্ট হয়, তবে লিস্টে আইডি অ্যাড হবে
                if ($status == 'Absent') {
                    $absentStudentIds[] = $student_id;
                }
            }

            // ২. এটেন্ডেন্স সফলভাবে সেভ হলো
            DB::commit();

            // ==========================================
            // SMART ATTENDANCE SMS MODULE (Starts Here)
            // ==========================================
            $sentCount = 0;
            $smsMessage = "";

            if (count($absentStudentIds) > 0) {
                try {
                    $attendanceDateFormatted = Carbon::parse($date)->format('d-M-Y');
                    $smsService = app(\App\Services\SmsService::class);

                    // Fetch active students with valid mobile numbers
                    $absentStudents = Student::whereIn('id', $absentStudentIds)
                                             ->where('sms_status', 'Active')
                                             ->whereNotNull('guardian_mobile')
                                             ->get();

                    foreach ($absentStudents as $student) {
                        // Smart Check: Check if duplicate absent message was already sent today
                        $alreadySentToday = SmsLog::where('student_id', $student->id)
                                                  ->whereDate('created_at', Carbon::today())
                                                  ->where('message', 'like', '%ABSENT today%')
                                                  ->exists();

                        if (!$alreadySentToday) {
                            $student_name = $student->student_name ?? $student->first_name;
                            $messageBody = "Dear Guardian, your child {$student_name} is ABSENT today ({$attendanceDateFormatted}). Please contact the school. - MACS School";

                            $smsService->sendSms($student->guardian_mobile, $messageBody, $student->id);
                            $sentCount++;
                        }
                    }

                    $smsMessage = " and SMS sent to {$sentCount} absent student(s)!";

                } catch (\Exception $smsEx) {
                    $smsMessage = " (however, SMS delivery encountered a server issue)";
                }
            }
            // ==========================================

            return response()->json([
                'status' => 'success', 
                'message' => 'Alhamdulillah! Attendance saved successfully.' . $smsMessage
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error', 
                'message' => 'সেভ করতে সমস্যা হয়েছে: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncBiometric(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id'       => 'nullable|exists:branches,id',
            'session_year_id' => 'nullable|exists:session_years,id',
            'class_id'        => 'required|exists:classes,id',
            'section_id'      => 'nullable|exists:sections,id',
            'teacher_id'      => 'required|exists:teachers,id',
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

            // 2. Fetch all students in this class/section
            $studentQuery = Student::where('class_id', $classId);
            if ($sectionId) $studentQuery->where('section_id', $sectionId);
            if ($branchId) $studentQuery->where('branch_id', $branchId);
            if ($sessionYearId) $studentQuery->where('session_year_id', $sessionYearId);
            
            $students = $studentQuery->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found in the selected Class/Section.'
                ], 400);
            }

            DB::beginTransaction();

            $syncedCount = 0;
            $absentStudentIds = [];

            foreach ($students as $student) {
                $status = 'Absent';
                $remarks = 'Absent (Biometric Check)';
                
                // Check if student has card number and has swiped
                if (!empty($student->card_number) && isset($cardSwipes[$student->card_number])) {
                    $times = $cardSwipes[$student->card_number];
                    sort($times);
                    $checkIn = $times[0];
                    $checkOut = count($times) > 1 ? end($times) : null;
                    
                    // Late limit (e.g. 9:00 AM)
                    $status = 'Present';
                    if ($checkIn > '09:00:00') {
                        $status = 'Late';
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

                Attendance::updateOrCreate(
                    [
                        'student_id'      => $student->id,
                        'attendance_date' => $date,
                    ],
                    [
                        'branch_id'       => $branchId,
                        'session_year_id' => $sessionYearId,
                        'class_id'        => $classId,
                        'section_id'      => $sectionId,
                        'teacher_id'      => $teacherId,
                        'user_id'         => $creatorId,
                        'status'          => $status,
                        'remarks'         => $remarks,
                    ]
                );
            }

            DB::commit();

            // ==========================================
            // SMART ATTENDANCE SMS MODULE FOR BIOMETRIC
            // ==========================================
            $smsSentCount = 0;
            try {
                $smsService = app(\App\Services\SmsService::class);
                $attendanceDateFormatted = Carbon::parse($date)->format('d-M-Y');
                
                foreach ($students as $student) {
                    if ($student->sms_status !== 'Active' || empty($student->guardian_mobile)) {
                        continue;
                    }
                    
                    // Fetch the updated attendance status
                    $att = Attendance::where('student_id', $student->id)
                        ->where('attendance_date', $date)
                        ->first();
                        
                    if (!$att) continue;
                    
                    if (in_array($att->status, ['Present', 'Late'])) {
                        // 1. Check-In SMS
                        $alreadySentToday = SmsLog::where('student_id', $student->id)
                            ->where('message', 'like', "%{$attendanceDateFormatted}%")
                            ->where('message', 'like', '%entered the school%')
                            ->exists();
                            
                        if (!$alreadySentToday) {
                            preg_match('/In:\s*(\d{2}:\d{2}:\d{2})/', $att->remarks, $inMatches);
                            $timeStr = isset($inMatches[1]) ? $inMatches[1] : '';
                            if (empty($timeStr)) {
                                preg_match('/\((.*?)\)/', $att->remarks, $matches);
                                $timeStr = isset($matches[1]) ? $matches[1] : '';
                            }
                            $timeFormatted = !empty($timeStr) ? Carbon::parse($timeStr)->format('h:i A') : '';
                            
                            $timeMessage = !empty($timeFormatted) ? " at {$timeFormatted}" : "";
                            $statusLabel = $att->status === 'Late' ? ' (Late)' : '';
                            
                            $msg = "Dear Guardian, your child {$student->student_name} has entered the school{$timeMessage} on {$attendanceDateFormatted}{$statusLabel}. - MACS School";
                            $smsService->sendSms($student->guardian_mobile, $msg, $student->id);
                            $smsSentCount++;
                        }
                        
                        // 2. Check-Out SMS
                        if (str_contains($att->remarks, 'Out:')) {
                            $alreadySentOutToday = SmsLog::where('student_id', $student->id)
                                ->where('message', 'like', "%{$attendanceDateFormatted}%")
                                ->where('message', 'like', '%left the school%')
                                ->exists();
                                
                            if (!$alreadySentOutToday) {
                                preg_match('/Out:\s*(\d{2}:\d{2}:\d{2})/', $att->remarks, $outMatches);
                                $outTimeStr = isset($outMatches[1]) ? $outMatches[1] : '';
                                if (!empty($outTimeStr)) {
                                    $outTimeFormatted = Carbon::parse($outTimeStr)->format('h:i A');
                                    
                                    $msg = "Dear Guardian, your child {$student->student_name} has left the school at {$outTimeFormatted} on {$attendanceDateFormatted}. - MACS School";
                                    $smsService->sendSms($student->guardian_mobile, $msg, $student->id);
                                    $smsSentCount++;
                                }
                            }
                        }
                    } elseif ($att->status === 'Absent') {
                        $alreadySentToday = SmsLog::where('student_id', $student->id)
                            ->where('message', 'like', "%{$attendanceDateFormatted}%")
                            ->where('message', 'like', '%ABSENT today%')
                            ->exists();
                            
                        if (!$alreadySentToday) {
                            $msg = "Dear Guardian, your child {$student->student_name} is ABSENT today ({$attendanceDateFormatted}). Please contact the school. - MACS School";
                            $smsService->sendSms($student->guardian_mobile, $msg, $student->id);
                            $smsSentCount++;
                        }
                    }
                }
            } catch (\Exception $smsEx) {
                \Log::error("Biometric Sync SMS Notification Failed: " . $smsEx->getMessage());
            }
            // ==========================================

            $smsNotice = $smsSentCount > 0 ? " and sent {$smsSentCount} SMS to guardian(s)!" : "";

            return response()->json([
                'status' => 'success',
                'message' => "Successfully synced attendance logs. Marked {$syncedCount} Present/Late, " . count($absentStudentIds) . " Absent" . $smsNotice . ".",
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

    public function reportIndex()
    {
        return view('pages.attendance.report');
    }

    public function getReportData(Request $request): JsonResponse
    {
        try {
            $query = Attendance::with(['student', 'class', 'section', 'teacher.user']);

            if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);
            if ($request->filled('session_year_id')) $query->where('session_year_id', $request->session_year_id);
            if ($request->filled('class_id')) $query->where('class_id', $request->class_id);
            if ($request->filled('section_id')) $query->where('section_id', $request->section_id);
            if ($request->filled('attendance_date')) $query->where('attendance_date', $request->attendance_date);

            $reportData = $query->latest('attendance_date')->get();

            return response()->json([
                'status' => 'success',
                'data' => $reportData
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
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
}