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
            return response()->json(['status' => 'error', 'message' => 'Another exam is already scheduled for this class at the same time!']);
        }

        ExamRoutine::create($request->all());

        return response()->json(['status' => 'success', 'message' => 'Exam routine saved successfully!']);
    }

    // পূর্ববর্তী রুটিনের তালিকা আনা
    public function listRoutines(): JsonResponse
    {
        $routines = ExamRoutine::select('session_year_id', 'exam_id')
            ->groupBy('session_year_id', 'exam_id')
            ->with(['sessionYear', 'exam'])
            ->get()
            ->map(function ($item) {
                $classIds = ExamRoutine::where('session_year_id', $item->session_year_id)
                    ->where('exam_id', $item->exam_id)
                    ->distinct()
                    ->pluck('class_id');
                
                $classNames = Classes::whereIn('id', $classIds)->pluck('class_name')->toArray();
                
                $minDate = ExamRoutine::where('session_year_id', $item->session_year_id)
                    ->where('exam_id', $item->exam_id)
                    ->min('exam_date');
                $maxDate = ExamRoutine::where('session_year_id', $item->session_year_id)
                    ->where('exam_id', $item->exam_id)
                    ->max('exam_date');

                return [
                    'session_year_id' => $item->session_year_id,
                    'session_name' => $item->sessionYear->session_name ?? 'N/A',
                    'exam_id' => $item->exam_id,
                    'exam_name' => $item->exam->name ?? 'N/A',
                    'classes' => implode(', ', $classNames),
                    'date_range' => $minDate && $maxDate ? date('d M, Y', strtotime($minDate)) . ' - ' . date('d M, Y', strtotime($maxDate)) : 'N/A'
                ];
            });

        return response()->json(['status' => 'success', 'routines' => $routines]);
    }

    // রুটিনের স্লট আপডেট করা
    public function update(Request $request, $id): JsonResponse
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

        $conflict = ExamRoutine::where('id', '!=', $id)
            ->where('session_year_id', $request->session_year_id)
            ->where('exam_id', $request->exam_id)
            ->where('class_id', $request->class_id)
            ->where('exam_date', $request->exam_date)
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
            })->first();

        if ($conflict) {
            return response()->json(['status' => 'error', 'message' => 'Another exam is already scheduled for this class at the same time!']);
        }

        $routine = ExamRoutine::findOrFail($id);
        $routine->update($request->all());

        return response()->json(['status' => 'success', 'message' => 'Exam routine slot updated successfully!']);
    }

    // পুরো রুটিন ডিলিট করা
    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'session_year_id' => 'required',
            'exam_id'         => 'required',
        ]);

        ExamRoutine::where('session_year_id', $request->session_year_id)
            ->where('exam_id', $request->exam_id)
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Exam routine deleted successfully!']);
    }

    // ডিলিট করা
    public function destroy($id): JsonResponse
    {
        ExamRoutine::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Routine deleted successfully!']);
    }
}