<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\ClassRoutine;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\SessionYear;
use App\Models\Branch; // ব্রাঞ্চ মডেল যুক্ত করা হলো
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClassRoutineController extends Controller
{
    public function index()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $branches = Branch::all(); // ব্রাঞ্চের ডাটা
        $classes = Classes::all();
        $sections = Section::all();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();
        
        return view('pages.routine.index', compact('sessions', 'branches', 'classes', 'sections', 'subjects', 'teachers'));
    }

    public function getRoutine(Request $request): JsonResponse
    {
        $routine = ClassRoutine::with(['subject', 'teacher.user'])
            ->where('session_year_id', $request->session_year_id)
            ->where('class_id', $request->class_id)
            ->when($request->branch_id, function($query) use ($request) {
                return $query->where('branch_id', $request->branch_id); // ব্রাঞ্চ ফিল্টার
            })
            ->when($request->section_id, function($query) use ($request) {
                return $query->where('section_id', $request->section_id);
            })
            ->orderBy('start_time')
            ->get()
            ->groupBy('day');
            
        return response()->json(['status' => 'success', 'routine' => $routine]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'session_year_id' => 'required',
            'branch_id'       => 'nullable',
            'class_id'        => 'required',
            'subject_id'      => 'required',
            'teacher_id'      => 'required',
            'day'             => 'required',
            'start_time'      => 'required',
            'end_time'        => 'required|after:start_time',
        ]);

        $start = $request->start_time;
        $end = $request->end_time;

        // চেক ১: ওই ক্লাসে/ব্রাঞ্চে একই সময়ে অন্য ক্লাস আছে কি না?
        $classConflict = ClassRoutine::where('session_year_id', $request->session_year_id)
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('day', $request->day)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                      ->where('end_time', '>', $start);
            })->first();

        if ($classConflict) {
            return response()->json(['status' => 'error', 'message' => 'এই সময়ে এই ক্লাসের অন্য একটি রুটিন আগে থেকেই তৈরি করা আছে!']);
        }

        // চেক ২: শিক্ষকের ওভারল্যাপ চেক
        $teacherConflict = ClassRoutine::where('session_year_id', $request->session_year_id)
            ->where('teacher_id', $request->teacher_id)
            ->where('day', $request->day)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                      ->where('end_time', '>', $start);
            })->first();

        if ($teacherConflict) {
            return response()->json(['status' => 'error', 'message' => 'এই শিক্ষক উক্ত সময়ে অন্য একটি ক্লাসে ব্যস্ত আছেন! দয়া করে সময় পরিবর্তন করুন।']);
        }

        ClassRoutine::create($request->all());

        return response()->json(['status' => 'success', 'message' => 'আলহামদুলিল্লাহ! রুটিন সফলভাবে সেভ হয়েছে।']);
    }

    public function destroy($id): JsonResponse
    {
        ClassRoutine::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'রুটিন ডিলিট করা হয়েছে!']);
    }
}