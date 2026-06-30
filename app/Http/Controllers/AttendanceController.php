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
                    $now = Carbon::now();

                    // শুধু অ্যাবসেন্ট স্টুডেন্টদের ডাটা আনা
                    $absentStudents = Student::whereIn('id', $absentStudentIds)
                                             ->where('sms_status', 'Active')
                                             ->whereNotNull('guardian_mobile')
                                             ->get();

                    $logData = [];
                    $token = "107092350091707846609c0d7854830bcea7f322cd4a2f1b39a18";
                    $url = "https://api.bdbulksms.net/api.php?json";

                    foreach ($absentStudents as $student) {
                        
                        // Smart Check: আজকে কি এই স্টুডেন্টকে আগেই অ্যাবসেন্ট মেসেজ পাঠানো হয়েছে?
                        $alreadySentToday = SmsLog::where('student_id', $student->id)
                                                  ->whereDate('created_at', Carbon::today())
                                                  ->where('message', 'like', '%ABSENT today%')
                                                  ->exists();

                        if (!$alreadySentToday) {
                            $student_name = $student->student_name ?? $student->first_name;
                            
                            $messageBody = "Dear Guardian, your child {$student_name} is ABSENT today ({$attendanceDateFormatted}). Please contact the school authority. - PIS";

                            // API কল
                            $data = [
                                'to' => $student->guardian_mobile,
                                'message' => $messageBody,
                                'token' => $token
                            ]; 

                            $ch = curl_init(); 
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            curl_setopt($ch, CURLOPT_ENCODING, '');
                            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            $smsresult = curl_exec($ch);
                            curl_close($ch);

                            // লগের জন্য ডাটা পুশ
                            $logData[] = [
                                'student_id' => $student->id,
                                'mobile_number' => $student->guardian_mobile,
                                'message' => $messageBody,
                                'status' => 'Sent',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];

                            $sentCount++;
                        }
                    }

                    // একসাথে লগ সেভ করা
                    if(count($logData) > 0) {
                        SmsLog::insert($logData);
                    }

                    $smsMessage = " and SMS sent to {$sentCount} absent student(s)!";

                } catch (\Exception $smsEx) {
                    // SMS পাঠাতে সমস্যা হলেও এটেন্ডেন্স সেভ দেখাবে
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
}