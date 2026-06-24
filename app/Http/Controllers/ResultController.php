<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Student;
use App\Models\Mark;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\ExamSchedule;
use Illuminate\Http\Request;
use PDF; // DomPDF

class ResultController extends Controller
{
   // মার্কশিট সার্চ করার পেজ
    public function index()
    {
        $exams = Exam::orderBy('id', 'desc')->get();
        $sessions = \App\Models\SessionYear::orderBy('session_name', 'desc')->get(); // সেশন ডাটা আনা হলো
        
        return view('pages.results.index', compact('exams', 'sessions')); // sessions পাস করা হলো
    }

    // পিডিএফ জেনারেট এবং ক্যালকুলেশন লজিক
    public function generate(Request $request)
    {
        $request->validate([
            'session_year_id' => 'required', // সেশন রিকোয়ার্ড করা হলো
            'exam_id' => 'required',
            'student_identity' => 'required'
        ]);

        $student = Student::with(['schoolClass', 'branch', 'section'])->where('student_identity', $request->student_identity)->first();

        if (!$student) {
            return back()->withErrors(['error' => 'Student not found. Please check the ID!']);
        }

        $exam = Exam::find($request->exam_id);

        // ওই স্টুডেন্টের নির্দিষ্ট সেশন এবং পরীক্ষার সব বিষয়ের নম্বর নিয়ে আসা
        $marks = Mark::with('subject')
            ->where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('session_year_id', $request->session_year_id) // সেশন দিয়ে ফিল্টার করা হলো
            ->get();

        if ($marks->isEmpty()) {
            return back()->withErrors(['error' => 'No marks entered for this student in the selected exam and session.']);
        }

        // --- রেজাল্ট প্রসেসিং অ্যালগরিদম (বাকি সব আগের মতোই থাকবে) ---
        $total_marks = $marks->sum('total_mark');
        $total_grade_points = $marks->sum('grade_point');
        $subject_count = $marks->count();

        $is_failed = $marks->contains(function ($mark) {
            return $mark->letter_grade == 'F' || $mark->letter_grade == 'Fail';
        });

        $cgpa = 0.00;
        $final_grade = 'F';

        if (!$is_failed && $subject_count > 0) {
            $cgpa = number_format($total_grade_points / $subject_count, 2);
            $final_grade = $this->getFinalGrade($cgpa);
        }

        $data = [
            'student' => $student,
            'exam' => $exam,
            'marks' => $marks,
            'total_marks' => $total_marks,
            'cgpa' => $is_failed ? '0.00' : $cgpa,
            'final_grade' => $final_grade
        ];

        $pdf = PDF::loadView('pages.results.marksheet_pdf', $data)->setPaper('A4', 'portrait');
        return $pdf->stream('Marksheet_'.$student->student_identity.'.pdf');
    }

    // জিপিএ অনুযায়ী ফাইনাল লেটার গ্রেড বের করার হেল্পার মেথড
    private function getFinalGrade($cgpa) {
        if ($cgpa >= 5.0) return 'A+';
        if ($cgpa >= 4.0) return 'A';
        if ($cgpa >= 3.5) return 'A-';
        if ($cgpa >= 3.0) return 'B';
        if ($cgpa >= 2.0) return 'C';
        if ($cgpa >= 1.0) return 'D';
        return 'F';
    }


    // ট্যাবুলেশন শিট সার্চ পেজ
    public function tabulationIndex()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $branches = Branch::all();
        $exams = Exam::orderBy('id', 'desc')->get();
        $classes = Classes::all();
        return view('pages.results.tabulation_index', compact('sessions', 'branches', 'exams', 'classes'));
    }

    // ট্যাবুলেশন শিট পিডিএফ জেনারেট লজিক
    public function tabulationGenerate(Request $request)
    {
        $request->validate([
            'session_year_id' => 'required',
            'branch_id' => 'required',
            'exam_id' => 'required',
            'class_id' => 'required',
        ]);

        $exam = Exam::find($request->exam_id);
        $schoolClass = Classes::find($request->class_id);
        $branch = Branch::find($request->branch_id);

        // ওই ক্লাস এবং এক্সামের জন্য কী কী সাবজেক্ট শিডিউল করা আছে
        $schedules = ExamSchedule::with('subject')
            ->where('exam_id', $exam->id)
            ->where('class_id', $schoolClass->id)
            ->get();

        if ($schedules->isEmpty()) {
            return back()->withErrors(['error' => 'No subjects found for this class and exam.']);
        }

        // নির্দিষ্ট সেশন, ব্রাঞ্চ এবং ক্লাসের সব স্টুডেন্ট
        $students = Student::where('session_year_id', $request->session_year_id)
            ->where('branch_id', $branch->id)
            ->where('class_id', $schoolClass->id)
            ->get();

        // সব মার্কস
        $allMarks = Mark::where('session_year_id', $request->session_year_id)
            ->where('branch_id', $branch->id)
            ->where('exam_id', $exam->id)
            ->where('class_id', $schoolClass->id)
            ->get();

        $studentData = [];

        foreach ($students as $student) {
            $studentMarks = $allMarks->where('student_id', $student->id);
            $grandTotal = $studentMarks->sum('total_mark');
            $totalGradePoints = $studentMarks->sum('grade_point');
            $subjectCount = $studentMarks->count();

            // ফেইল করেছে কি না চেক
            $is_failed = $studentMarks->contains(function ($m) {
                return $m->letter_grade == 'F' || $m->letter_grade == 'Fail';
            });

            $cgpa = 0.00;
            if (!$is_failed && $subjectCount > 0) {
                $cgpa = number_format($totalGradePoints / $subjectCount, 2);
            }
            $finalGrade = $is_failed ? 'F' : $this->getFinalGrade($cgpa);

            $studentData[] = (object)[
                'student' => $student,
                'marks' => $studentMarks->keyBy('subject_id'), // সাবজেক্ট আইডি দিয়ে মার্কস খোঁজার সুবিধার জন্য
                'grand_total' => $grandTotal,
                'cgpa' => $is_failed ? '0.00' : $cgpa,
                'final_grade' => $finalGrade
            ];
        }

        // মেধাক্রম (Position) বের করার জন্য সর্টিং (প্রথমে CGPA, তারপর Grand Total)
        usort($studentData, function($a, $b) {
            if ($a->cgpa == $b->cgpa) {
                return $b->grand_total <=> $a->grand_total; // CGPA সমান হলে Total Mark দিয়ে সর্ট
            }
            return $b->cgpa <=> $a->cgpa; // CGPA দিয়ে সর্ট
        });

        $data = [
            'exam' => $exam,
            'schoolClass' => $schoolClass,
            'branch' => $branch,
            'schedules' => $schedules,
            'studentData' => $studentData
        ];

        // Landscape এবং Legal সাইজে পিডিএফ লোড করা
        $pdf = PDF::loadView('pages.results.tabulation_pdf', $data)->setPaper('legal', 'landscape');
        return $pdf->stream('Tabulation_Sheet_'.$schoolClass->class_name.'.pdf');
    }






}