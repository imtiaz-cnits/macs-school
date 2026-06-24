<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        // ডাটাবেস থেকে সব গ্রেড নিয়ে আসছি (পয়েন্টের ভিত্তিতে বড় থেকে ছোট সাজানো)
        $grades = Grade::orderBy('grade_point', 'desc')->get();
        return view('pages.grades.index', compact('grades'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grade_name' => 'required|string|max:5', // Ex: A+
            'grade_point' => 'required|numeric|min:0|max:5', // Ex: 5.00
            'min_mark' => 'required|integer|min:0|max:100', // Ex: 80
            'max_mark' => 'required|integer|min:0|max:100|gte:min_mark', // Ex: 100
            'remarks' => 'nullable|string|max:255', // Ex: Outstanding
        ]);

        Grade::create([
            'grade_name' => $request->grade_name,
            'grade_point' => $request->grade_point,
            'min_mark' => $request->min_mark,
            'max_mark' => $request->max_mark,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Grade added successfully!');
    }

    public function destroy($id)
    {
        Grade::findOrFail($id)->delete();
        return back()->with('success', 'Grade deleted successfully!');
    }
}