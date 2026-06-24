<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Exam;
use App\Models\Shift;
use App\Models\Section;
use App\Models\Student;
use App\Models\ExamSchedule;
use PDF; // DomPDF

class AdmitCardController extends Controller
{
  // পেজ লোড করার ফাংশন (আপনার কন্ট্রোলারে চেক করুন)
    public function index()
    {
        $sessions = SessionYear::all(); // বা আপনার সিস্টেমে যেভাবে আনা আছে
        $classes  = Classes::all();
        $branches = Branch::all();
        $exams    = Exam::all();
        
        // 🚨 এই দুটি লাইন আপনার কন্ট্রোলারে মিসিং আছে, এগুলো যোগ করুন 🚨
        $shifts   = Shift::all();     // Shift মডেল ইম্পোর্ট করতে ভুলবেন না
        $sections = Section::all();   // Section মডেল ইম্পোর্ট করতে ভুলবেন না

        // compact এর ভেতরে shifts এবং sections পাঠাতে হবে
        return view('pages.admit_cards.index', compact('sessions', 'classes', 'branches', 'exams', 'shifts', 'sections'));
    }

  // পিডিএফ জেনারেট করার লজিক
    public function generate(Request $request)
    {
        // মেমরি লিমিট বাড়ানো হয়েছে যাতে একসাথে অনেক স্টুডেন্টের পিডিএফ ক্র্যাশ না করে
        ini_set('memory_limit', '512M');
        
        // 🚨 নতুন যুক্ত হওয়া ফিল্ডগুলোর ভ্যালিডেশন
        $request->validate([
            'session_year_id' => 'required',
            'branch_id'       => 'required',
            'exam_id'         => 'required',
            'class_id'        => 'required',
            'shift_id'        => 'required',  // 🆕
            'section_id'      => 'required',  // 🆕
        ]);

        $exam        = Exam::find($request->exam_id);
        $schoolClass = Classes::find($request->class_id);
        $branch      = Branch::find($request->branch_id);
        $session     = SessionYear::find($request->session_year_id);

        // অ্যাডমিট কার্ডে দেখানোর জন্য ওই ক্লাসের পরীক্ষার রুটিন আনা হচ্ছে
        $routines = ExamSchedule::with('subject')
            ->where('exam_id', $exam->id)
            ->where('class_id', $schoolClass->id)
            ->orderBy('exam_date', 'asc') // তারিখ অনুযায়ী সাজানো
            ->get();

        // 🚨 নির্দিষ্ট ক্লাস, সেশন, ব্রাঞ্চ, শিফট ও সেকশনের সব স্টুডেন্ট আনা হচ্ছে
        $students = Student::with(['schoolClass', 'branch', 'section']) 
            ->where('session_year_id', $request->session_year_id)
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id)
            ->where('shift_id', $request->shift_id)     // 🆕 শিফট ফিল্টার
            ->where('section_id', $request->section_id) // 🆕 সেকশন ফিল্টার
            ->get();

        if ($students->isEmpty()) {
            return back()->withErrors(['error' => 'No students found for the selected criteria!']);
        }

        $data = [
            'exam'        => $exam,
            'schoolClass' => $schoolClass,
            'branch'      => $branch,
            'session'     => $session,
            'routines'    => $routines,
            'students'    => $students
        ];

        // A4 সাইজ এবং Portrait মোডে পিডিএফ লোড করা
        $pdf = PDF::loadView('pages.admit_cards.admit_card_pdf', $data)->setPaper('A4', 'portrait');
        
        // 🆕 পিডিএফ এর নামে সেকশন আইডি যুক্ত করে দেওয়া হলো, যাতে ফাইলগুলো আলাদা থাকে
        return $pdf->stream('Admit_Cards_' . $schoolClass->class_name . '_Section_' . $request->section_id . '.pdf');
    }
}