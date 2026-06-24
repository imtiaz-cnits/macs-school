<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class IdCardController extends Controller
{
    public function index()
    {
        // এটি শুধু ফিল্টার পেজ লোড করবে
        return view('pages.students.id-card-index'); 
    }

    public function generatePDF(Request $request)
    {
        $query = Student::query();

        // ১. যদি সার্চ বক্সে কিছু লেখা থাকে (সিঙ্গেল বা স্পেসিফিক আইডি কার্ডের জন্য)
        if ($request->filled('student_id_search')) {
            $search = $request->student_id_search;
            
            $query->where(function($q) use ($search) {
                $q->where('student_identity', $search)
                  ->orWhere('roll_number', $search)
                  ->orWhere('id', $search);
            });
        } 
        // ২. যদি সার্চ বক্স ফাঁকা থাকে (বাল্ক প্রিন্টের জন্য ড্রপডাউন ফিল্টারিং)
        else {
            // বাল্ক প্রিন্টের জন্য ক্লাস সিলেক্ট করা বাধ্যতামূলক
            $request->validate([
                'class_id' => 'required'
            ], [
                'class_id.required' => 'Please select at least a Class for bulk ID card generation.'
            ]);

            // ড্রপডাউন থেকে যা যা সিলেক্ট করা হবে, ঠিক সেগুলোই ফিল্টার হবে
            $query->where('class_id', $request->class_id);

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('session_year_id')) {
                $query->where('session_year_id', $request->session_year_id);
            }
            if ($request->filled('shift_id')) {
                $query->where('shift_id', $request->shift_id);
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
        }

        // রিলেশনশিপগুলো আগে থেকেই লোড করে নেওয়া হলো যাতে PDF দ্রুত জেনারেট হয় (N+1 Query Issue সলভড)
        $students = $query->with(['branch', 'schoolClass', 'shift', 'sessionYear'])->get();

        // যদি এই ফিল্টারে কোনো স্টুডেন্ট না পাওয়া যায়
        if ($students->isEmpty()) {
            return back()->with('error', 'No students found matching your selected criteria!');
        }

        // পিডিএফ জেনারেট করা
        $pdf = Pdf::loadView('pages.templates.id-card', compact('students'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Student_ID_Cards.pdf');
    }
}