<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\Classes; // আপনার Class মডেল
use App\Models\Subject;     // আপনার Subject মডেল
use App\Models\Branch;      // নতুন যুক্ত করা Branch মডেল
use Illuminate\Http\Request;

class ExamScheduleController extends Controller
{
    public function index()
    {
        // ড্রপডাউনের জন্য ডাটা নিয়ে আসছি
        $branches = Branch::all(); // ব্রাঞ্চের ডাটা
        $exams = Exam::orderBy('id', 'desc')->get();
        $classes = Classes::all(); 
        $subjects = Subject::all();

        // টেবিলের জন্য ডাটা (রিলেশনশিপসহ)
        $schedules = ExamSchedule::with(['branch', 'exam', 'classes', 'subject'])
            ->orderBy('exam_id', 'desc')
            ->orderBy('branch_id', 'asc') // ব্রাঞ্চ অনুযায়ী সাজানো
            ->orderBy('class_id', 'asc')
            ->get();

        return view('pages.exam_schedules.index', compact('branches', 'exams', 'classes', 'subjects', 'schedules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id', // ব্রাঞ্চ ভ্যালিডেশন
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required',
            'subject_id' => 'required',
            'full_marks' => 'required|numeric|min:1',
            'pass_marks' => 'required|numeric|min:1|lte:full_marks',
            'ct_marks' => 'nullable|numeric|min:0',
            'written_marks' => 'nullable|numeric|min:0',
            'mcq_marks' => 'nullable|numeric|min:0',
            'exam_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
        ]);

        // একই পরীক্ষায়, একই শাখায় (Branch), একই ক্লাসে, একই সাবজেক্ট ২ বার অ্যাড করা ঠেকাতে চেক করা হচ্ছে
        $exists = ExamSchedule::where('exam_id', $request->exam_id)
            ->where('branch_id', $request->branch_id) // ব্রাঞ্চ চেকিং যুক্ত করা হলো
            ->where('class_id', $request->class_id)
            ->where('subject_id', $request->subject_id)
            ->first();

        if ($exists) {
            return back()->withErrors(['error' => 'This subject is already scheduled for the selected branch, exam and class.']);
        }

        ExamSchedule::create($request->except('_token'));

        return back()->with('success', 'Subject scheduled successfully!');
    }

    public function destroy($id)
    {
        ExamSchedule::findOrFail($id)->delete();
        return back()->with('success', 'Schedule deleted successfully!');
    }
}