<?php

namespace App\Http\Controllers;

use App\Models\ExamSchedule;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\Branch;
use Illuminate\Http\Request;

class ExamScheduleController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        $classes = Classes::all(); 
        $subjects = Subject::all();

        // টেবিলের জন্য ডাটা (রিলেশনশিপসহ)
        $schedules = ExamSchedule::with(['branch', 'classes', 'subject'])
            ->orderBy('branch_id', 'asc')
            ->orderBy('class_id', 'asc')
            ->get();

        return view('pages.exam_schedules.index', compact('branches', 'classes', 'subjects', 'schedules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'full_marks' => 'required|numeric|min:1',
            'pass_marks' => 'required|numeric|min:1|lte:full_marks',
            'ct_marks' => 'nullable|numeric|min:0',
            'written_marks' => 'nullable|numeric|min:0',
            'mcq_marks' => 'nullable|numeric|min:0',
        ]);

        // একই শাখায় (Branch), একই ক্লাসে, একই সাবজেক্ট ২ বার অ্যাড করা ঠেকাতে চেক করা হচ্ছে
        $exists = ExamSchedule::where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id)
            ->where('subject_id', $request->subject_id)
            ->first();

        if ($exists) {
            return back()->withErrors(['error' => 'This subject mark distribution is already configured for the selected branch and class.']);
        }

        ExamSchedule::create([
            'branch_id' => $request->branch_id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'full_marks' => $request->full_marks,
            'pass_marks' => $request->pass_marks,
            'ct_marks' => $request->ct_marks ?? 0,
            'written_marks' => $request->written_marks ?? 0,
            'mcq_marks' => $request->mcq_marks ?? 0,
        ]);

        return back()->with('success', 'Subject marks distribution saved successfully!');
    }

    public function destroy($id)
    {
        ExamSchedule::findOrFail($id)->delete();
        return back()->with('success', 'Subject setup deleted successfully!');
    }
}