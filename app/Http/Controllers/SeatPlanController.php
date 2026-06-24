<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Shift;
use App\Models\Section;
use PDF; // DomPDF

class SeatPlanController extends Controller
{
   // সিট প্ল্যান সার্চ ফর্ম পেজ
    public function index()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $branches = Branch::all();
        $exams = Exam::orderBy('id', 'desc')->get();
        $classes = Classes::all();
        
        // 🆕 এই দুটি নতুন যুক্ত করা হলো
        $shifts = Shift::all();     
        $sections = Section::all(); 
        
        // 🆕 compact এর ভেতরে shifts এবং sections পাস করা হলো
        return view('pages.seat_plans.index', compact('sessions', 'branches', 'exams', 'classes', 'shifts', 'sections'));
    }
    // পিডিএফ জেনারেট করার লজিক
   public function generate(Request $request)
    {
        ini_set('memory_limit', '512M');
        // 🚨 ১. ফর্ম থেকে আসা সব ডেটা (Shift ও Section সহ) রিকোয়ার্ড করা হলো
        $request->validate([
            'session_year_id' => 'required',
            'branch_id'       => 'required',
            'exam_id'         => 'required',
            'class_id'        => 'required',
            'shift_id'        => 'required', // 🆕 যুক্ত করা হয়েছে
            'section_id'      => 'required', // 🆕 যুক্ত করা হয়েছে
        ]);

        $exam = Exam::find($request->exam_id);
        $schoolClass = Classes::find($request->class_id);
        $branch = Branch::find($request->branch_id);
        $session = SessionYear::find($request->session_year_id);

        // 🚨 ২. একদম স্ট্রং ফিল্টারিং (যা সিলেক্ট করবেন, শুধু তারাই আসবে)
        $students = Student::with(['schoolClass', 'branch', 'section', 'shift']) // 🆕 shift রিলেশন যুক্ত করা হলো
            ->where('session_year_id', $request->session_year_id)
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id)
            ->where('shift_id', $request->shift_id)       // 🆕 যুক্ত করা হয়েছে
            ->where('section_id', $request->section_id)   // 🆕 যুক্ত করা হয়েছে
            ->orderBy('student_identity', 'asc')          // সিরিয়াল ঠিক রাখার জন্য
            ->get();

        if ($students->isEmpty()) {
            return back()->withErrors(['error' => 'No students found for the selected criteria!']);
        }

        $data = [
            'exam' => $exam,
            'schoolClass' => $schoolClass,
            'branch' => $branch,
            'session' => $session,
            'students' => $students
        ];

        // A4 সাইজ এবং Portrait মোডে পিডিএফ লোড করা
        $pdf = PDF::loadView('pages.seat_plans.seat_plan_pdf', $data)->setPaper('A4', 'portrait');
        return $pdf->stream('Seat_Plan_' . $schoolClass->class_name . '.pdf');
    }
}