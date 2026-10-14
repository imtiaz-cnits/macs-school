<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\Branch;
use App\Models\Student;
use App\Models\SessionYear;
use App\Models\Section;
use App\Models\SmsLog;
use App\Models\Exam;
use App\Models\Mark;
use Carbon\Carbon;

class SmsController extends Controller
{
    // ১. জেনারেল নোটিশের ফর্ম পেজ দেখানোর মেথড (যেটি মিসিং হয়ে গিয়েছিল)
    public function generalNotice()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $classes = Classes::all();
        $branches = Branch::all();
        $sections = Section::all();
        
        return view('pages.sms.general_notice', compact('sessions', 'classes', 'branches', 'sections'));
    }

    // ২. এসএমএস সেন্ড এবং ডাটাবেসে সেভ করার মেথড
    // public function sendGeneralNotice(Request $request)
    // {
    //     $request->validate([
    //         'session_year_id' => 'required',
    //         'target_audience' => 'required',
    //         'message' => 'required|string',
    //     ]);

    //     // নির্দিষ্ট সেশন এবং অ্যাক্টিভ স্টুডেন্টদের ফিল্টার
    //     $query = Student::where('sms_status', 'Active')
    //                     ->where('session_year_id', $request->session_year_id);

    //     // অডিয়েন্স অনুযায়ী ডাইনামিক ফিল্টার
    //     if ($request->target_audience == 'branch_wise') {
    //         $request->validate(['branch_id' => 'required']);
    //         $query->where('branch_id', $request->branch_id);
            
    //     } elseif ($request->target_audience == 'class_wise') {
    //         $request->validate(['class_id' => 'required']);
    //         $query->where('class_id', $request->class_id);
            
    //     } elseif ($request->target_audience == 'section_wise') {
    //         $request->validate([
    //             'class_id' => 'required', 
    //             'section_id' => 'required'
    //         ]);
    //         $query->where('class_id', $request->class_id)
    //               ->where('section_id', $request->section_id);
    //     }

    //     $students = $query->get();

    //     if ($students->isEmpty()) {
    //         return back()->withErrors(['error' => 'No active students found for the selected session and criteria!']);
    //     }

    //     // বাবার মোবাইল নাম্বার কালেক্ট করা (যেগুলোতে নাম্বার আছে শুধু সেগুলো)
    //     $phoneNumbers = $students->pluck('guardian_mobile')->filter()->toArray();
        
    //     if (empty($phoneNumbers)) {
    //         return back()->withErrors(['error' => 'No valid guardian mobile numbers found!']);
    //     }

    //     $to = implode(',', $phoneNumbers);
    //     $messageBody = $request->message;
    //     $token = "107092350091707846609c0d7854830bcea7f322cd4a2f1b39a18";
    //     $url = "https://api.bdbulksms.net/api.php?json";

    //     // bdbulksms API Integration (cURL)
    //     $data = [
    //         'to' => $to,
    //         'message' => $messageBody,
    //         'token' => $token
    //     ]; 

    //     $ch = curl_init(); 
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //     curl_setopt($ch, CURLOPT_ENCODING, '');
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
    //     $smsresult = curl_exec($ch);
    //     $error = curl_error($ch);
    //     curl_close($ch);

    //     if ($error) {
    //         return back()->withErrors(['error' => 'Failed to send SMS. Error: ' . $error]);
    //     }

    //     // ==========================================
    //     // Data Store in Database for SMS Report
    //     // ==========================================
    //     $logData = [];
    //     $now = Carbon::now();

    //     foreach($students as $student) {
    //         if(!empty($student->guardian_mobile)){
    //             $logData[] = [
    //                 'student_id' => $student->id,
    //                 'mobile_number' => $student->guardian_mobile,
    //                 'message' => $messageBody,
    //                 'status' => 'Sent',
    //                 'created_at' => $now,
    //                 'updated_at' => $now,
    //             ];
    //         }
    //     }
        
    //     // Bulk Insert করা হলো
    //     SmsLog::insert($logData);
    //     // ==========================================

    //     return back()->with('success', 'Success! SMS Notice has been sent and logged successfully for ' . count($phoneNumbers) . ' guardians.');
    // }

    // এসএমএস রিপোর্ট ও কাউন্টার দেখানোর মেথড
    public function report(Request $request, \App\Services\SmsService $smsService)
    {
        $todayDhaka = Carbon::today('Asia/Dhaka');
        $nowDhaka = Carbon::now('Asia/Dhaka');

        // ১. কাউন্টারের জন্য রিয়েল ডাটা ক্যালকুলেশন (সফলভাবে সেন্ড হওয়া এসএমএস)
        $todaySent = SmsLog::where('status', 'Sent')
                           ->whereDate('created_at', $todayDhaka)
                           ->count();
        
        $monthSent = SmsLog::where('status', 'Sent')
                           ->whereYear('created_at', $nowDhaka->year)
                           ->whereMonth('created_at', $nowDhaka->month)
                           ->count();
                           
        $totalSent = SmsLog::where('status', 'Sent')->count();
        $totalFailed = SmsLog::whereIn('status', ['Failed', 'Blocked (Test Mode)', 'Ambiguous'])->count();

        // ২. MiM SMS লাইভ ব্যালেন্স চেক (gateway API)
        $forceRefresh = $request->boolean('refresh_balance');
        $gatewayBalance = $smsService->getBalance($forceRefresh);

        // ৩. টেবিলের জন্য ফিল্টারিং কোয়েরি
        $query = SmsLog::with(['student.schoolClass', 'student.section']);

        // সার্চ ফিল্টার (নাম, স্টুডেন্ট আইডি, মোবাইল নম্বর, মেসেজ টেক্সট)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('mobile_number', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('student_name', 'LIKE', "%{$search}%")
                         ->orWhere('student_identity', 'LIKE', "%{$search}%");
                  });
            });
        }

        // স্ট্যাটাস ফিল্টার
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        // ইভেন্ট টাইপ ফিল্টার (check_in, check_out, notice, result)
        if ($request->filled('event_type') && $request->event_type !== 'All') {
            $query->where('event_type', $request->event_type);
        }

        // ডেট ফিল্টার
        if ($request->filled('date_filter')) {
            if ($request->date_filter === 'today') {
                $query->whereDate('created_at', $todayDhaka);
            } elseif ($request->date_filter === 'yesterday') {
                $query->whereDate('created_at', Carbon::yesterday('Asia/Dhaka'));
            } elseif ($request->date_filter === 'last_7_days') {
                $query->where('created_at', '>=', Carbon::now('Asia/Dhaka')->subDays(7));
            } elseif ($request->date_filter === 'this_month') {
                $query->whereYear('created_at', $nowDhaka->year)
                      ->whereMonth('created_at', $nowDhaka->month);
            } elseif ($request->date_filter === 'custom') {
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }
            }
        }

        // ৪. টেবিলের জন্য লেটেস্ট এসএমএসগুলো আনা (পেজিনেশন সহ এবং ফিল্টার কোয়েরি স্ট্রিং প্রিজার্ভ করে)
        $logs = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();
        
        return view('pages.sms.report', compact(
            'logs', 
            'todaySent', 
            'monthSent', 
            'totalSent', 
            'totalFailed',
            'gatewayBalance'
        ));
    }

    // রেজাল্ট এসএমএস ফর্ম পেজ
    public function resultSms()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $exams = Exam::orderBy('name', 'asc')->get();
        $classes = Classes::all();
        $sections = Section::all();
        
        return view('pages.sms.result_sms', compact('sessions', 'exams', 'classes', 'sections'));
    }

    // স্মার্ট রেজাল্ট এসএমএস সেন্ড লজিক
    // public function sendResultSms(Request $request)
    // {
    //     $request->validate([
    //         'session_year_id' => 'required',
    //         'exam_id' => 'required',
    //         'class_id' => 'required',
    //         'message_template' => 'required|string',
    //     ]);

    //     $exam = Exam::find($request->exam_id);
        
    //     // সিলেক্ট করা ক্লাস ও সেশনের স্টুডেন্টদের আনা
    //     $query = Student::where('sms_status', 'Active')
    //                     ->where('session_year_id', $request->session_year_id)
    //                     ->where('class_id', $request->class_id);

    //     if ($request->section_id) {
    //         $query->where('section_id', $request->section_id);
    //     }

    //     $students = $query->get();

    //     if ($students->isEmpty()) {
    //         return back()->withErrors(['error' => 'No active students found!']);
    //     }

    //     $sentCount = 0;
    //     $logData = [];
    //     $now = Carbon::now();
    //     $token = "107092350091707846609c0d7854830bcea7f322cd4a2f1b39a18";
    //     $url = "https://api.bdbulksms.net/api.php?json";

    //     // প্রত্যেক স্টুডেন্টের জন্য আলাদা লুপ (কারণ সবার রেজাল্ট আলাদা)
    //     foreach ($students as $student) {
            
    //         // ওই স্টুডেন্ট এবং এক্সামের মার্কস আনা
    //         $marks = Mark::where('student_id', $student->id)
    //                      ->where('exam_id', $exam->id)
    //                      ->where('session_year_id', $request->session_year_id)
    //                      ->get();

    //         // যদি স্টুডেন্টের মার্কস এন্ট্রি করা থাকে, তবেই এসএমএস যাবে
    //         if ($marks->count() > 0 && !empty($student->guardian_mobile)) {
                
    //             // ক্যালকুলেশন
    //             $total_marks = $marks->sum('total_mark');
    //             $is_failed = $marks->contains(function ($mark) {
    //                 return $mark->letter_grade == 'F' || $mark->letter_grade == 'Fail';
    //             });
                
    //             $cgpa = number_format($marks->sum('grade_point') / $marks->count(), 2);
    //             $final_gpa = $is_failed ? '0.00 (Fail)' : $cgpa;
    //             $student_name = $student->student_name ?? $student->first_name;

    //             // স্মার্ট শর্টকোড রিপ্লেসমেন্ট
    //             $personalized_message = str_replace(
    //                 ['[name]', '[exam]', '[gpa]', '[marks]'],
    //                 [$student_name, $exam->name, $final_gpa, $total_marks],
    //                 $request->message_template
    //             );

    //             // API কল (Single Send)
    //             $data = [
    //                 'to' => $student->guardian_mobile,
    //                 'message' => $personalized_message,
    //                 'token' => $token
    //             ]; 

    //             $ch = curl_init(); 
    //             curl_setopt($ch, CURLOPT_URL, $url);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //             curl_setopt($ch, CURLOPT_ENCODING, '');
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             $smsresult = curl_exec($ch);
    //             curl_close($ch);

    //             // লগ সেভ করার জন্য এরে তৈরি
    //             $logData[] = [
    //                 'student_id' => $student->id,
    //                 'mobile_number' => $student->guardian_mobile,
    //                 'message' => $personalized_message,
    //                 'status' => 'Sent',
    //                 'created_at' => $now,
    //                 'updated_at' => $now,
    //             ];

    //             $sentCount++;
    //         }
    //     }

    //     // একসাথে ডাটাবেসে সেভ
    //     if(count($logData) > 0) {
    //         SmsLog::insert($logData);
    //     }

    //     if ($sentCount == 0) {
    //         return back()->withErrors(['error' => 'No marks found for the selected students in this exam. No SMS sent.']);
    //     }

    //     return back()->with('success', "Success! Personalized Result SMS sent and logged for {$sentCount} students.");
    // }

}