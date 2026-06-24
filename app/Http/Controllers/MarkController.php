<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Classes; 
use App\Models\Subject;
use App\Models\Student; 
use App\Models\Mark;
use App\Models\Grade;
use App\Models\ExamSchedule;
use App\Models\Branch;        
use App\Models\SessionYear;   
use Illuminate\Http\Request;

class MarkController extends Controller
{
    public function index(Request $request)
    {
        // ড্রপডাউনের জন্য সব ডাটা নিয়ে আসা হচ্ছে
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $branches = Branch::all();
        $exams = Exam::orderBy('id', 'desc')->get();
        $classes = Classes::all();
        $subjects = Subject::all();

        $students = [];
        $exam_schedule = null;

        // ৫টি ফিল্ডই ফিলাপ করা থাকলে তবেই স্টুডেন্ট লোড হবে
        if ($request->filled(['session_year_id', 'branch_id', 'exam_id', 'class_id', 'subject_id'])) {
            
            // ওই নির্দিষ্ট পরীক্ষার শিডিউল চেক করা
            $exam_schedule = ExamSchedule::where('exam_id', $request->exam_id)
                ->where('class_id', $request->class_id)
                ->where('subject_id', $request->subject_id)
                ->first();

            // নির্দিষ্ট সেশন, ব্রাঞ্চ এবং ক্লাসের স্টুডেন্টদের আনা হচ্ছে
            $students = Student::where('session_year_id', $request->session_year_id)
                ->where('branch_id', $request->branch_id)
                ->where('class_id', $request->class_id)
                ->get()
                ->map(function ($student) use ($request) {
                    $student->mark = Mark::where('session_year_id', $request->session_year_id)
                        ->where('branch_id', $request->branch_id)
                        ->where('exam_id', $request->exam_id)
                        ->where('class_id', $request->class_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('student_id', $student->id) 
                        ->first();
                    return $student;
                });
        }

        return view('pages.marks.index', compact('sessions', 'branches', 'exams', 'classes', 'subjects', 'students', 'exam_schedule'));
    }

   // AJAX এর মাধ্যমে রিয়েল-টাইম ডাটা সেভ করার ফাংশন
    public function storeAjax(Request $request)
    {
        // টোটাল মার্কস ক্যালকুলেট
        $total = ($request->ct_mark ?? 0) + ($request->written_mark ?? 0) + ($request->mcq_mark ?? 0);

        // টোটাল মার্কস অনুযায়ী গ্রেড বের করা
        $grade = Grade::where('min_mark', '<=', $total)
            ->where('max_mark', '>=', $total)
            ->first();

        // স্টুডেন্টের প্রোফাইল থেকে তার সেকশন আইডি (section_id) বের করে আনা হচ্ছে
        $student = Student::find($request->student_id);

        // ডাটাবেসে সেভ বা আপডেট করা
        $mark = Mark::updateOrCreate(
            [
                'session_year_id' => $request->session_year_id,
                'branch_id' => $request->branch_id,
                'exam_id' => $request->exam_id,
                'class_id' => $request->class_id,
                'subject_id' => $request->subject_id,
                'student_id' => $request->student_id,
            ],
            [
                'section_id' => $student->section_id ?? null, // <-- এই লাইনটি যুক্ত করা হলো
                'ct_mark' => $request->ct_mark ?? 0,
                'written_mark' => $request->written_mark ?? 0,
                'mcq_mark' => $request->mcq_mark ?? 0,
                'total_mark' => $total,
                'letter_grade' => $grade ? $grade->grade_name : 'F',
                'grade_point' => $grade ? $grade->grade_point : 0,
            ]
        );

        // সফল হলে JSON রেসপন্স রিটার্ন
        return response()->json([
            'success' => true, 
            'total' => $total, 
            'letter_grade' => $mark->letter_grade,
            'grade_point' => $mark->grade_point
        ]);
    }
   
}