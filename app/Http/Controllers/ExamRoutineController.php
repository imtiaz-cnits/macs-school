<?php

namespace App\Http\Controllers;

use App\Models\ExamRoutine;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\SessionYear;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamRoutineController extends Controller
{
    // ড্যাশবোর্ড লোড করা
    public function index()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $classes = Classes::all();
        $subjects = Subject::all();
        $exams = Exam::orderBy('id', 'desc')->get(); 
        
        return view('pages.exam_routine.index', compact('sessions', 'classes', 'subjects', 'exams'));
    }

    // ডাটাবেস থেকে রুটিন আনা
    public function getRoutine(Request $request): JsonResponse
    {
        $routine = ExamRoutine::with(['subject'])
            ->where('session_year_id', $request->session_year_id)
            ->where('exam_id', $request->exam_id)
            ->where('class_id', $request->class_id)
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
            
        return response()->json(['status' => 'success', 'routine' => $routine]);
    }

    // নতুন রুটিন সেভ করা
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'session_year_id' => 'required',
            'exam_id'         => 'required',
            'class_id'        => 'required',
            'subject_id'      => 'required',
            'exam_date'       => 'required|date',
            'start_time'      => 'required',
            'end_time'        => 'required|after:start_time',
        ]);

        // কনফ্লিক্ট চেক (একই দিনে একই সময়ে অন্য পরীক্ষা আছে কি না)
        $conflict = ExamRoutine::where('session_year_id', $request->session_year_id)
            ->where('exam_id', $request->exam_id)
            ->where('class_id', $request->class_id)
            ->where('exam_date', $request->exam_date)
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
            })->first();

        if ($conflict) {
            return response()->json(['status' => 'error', 'message' => 'এই সময়ে এই ক্লাসের অন্য একটি পরীক্ষা আগে থেকেই সেট করা আছে!']);
        }

        ExamRoutine::create($request->all());

        return response()->json(['status' => 'success', 'message' => 'পরীক্ষার রুটিন সফলভাবে সেভ হয়েছে!']);
    }

    // ডিলিট করা
    public function destroy($id): JsonResponse
    {
        ExamRoutine::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'রুটিন ডিলিট করা হয়েছে!']);
    }
}