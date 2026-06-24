<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\SessionYear; // আপনার সেশন মডেলের নাম অনুযায়ী পরিবর্তন করে নেবেন
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        // সব সেশন এবং তৈরি করা পরীক্ষাগুলো ডাটাবেস থেকে নিয়ে আসছি
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $exams = Exam::with('sessionYear')->orderBy('id', 'desc')->get();
        
        return view('pages.exams.index', compact('sessions', 'exams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'session_year_id' => 'required|exists:session_years,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        Exam::create([
            'name' => $request->name,
            'session_year_id' => $request->session_year_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'upcoming', // ডিফল্ট স্ট্যাটাস
        ]);

        return back()->with('success', 'Exam created successfully!');
    }

    public function destroy($id)
    {
        Exam::findOrFail($id)->delete();
        return back()->with('success', 'Exam deleted successfully!');
    }
}