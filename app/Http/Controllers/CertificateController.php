<?php
namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function index() {
        return view('pages.certificates.index');
    }

    public function generate(Request $request) {
        $request->validate(['student_id' => 'required', 'type' => 'required']);

        $student = Student::with(['schoolClass', 'sessionYear', 'branch'])
            ->where('student_identity', $request->student_id)
            ->orWhere('id', $request->student_id)
            ->first();

        if (!$student) {
            return back()->with('error', 'Student not found with this ID!');
        }

        $type = $request->type;
        $date = $request->issue_date ?? date('Y-m-d');
        
        // TC এর জন্য নতুন ফিল্ডের ডাটা রিসিভ করা হচ্ছে
        $leaving_reason = $request->leaving_reason ?? 'To study in another institution';
        $last_exam_result = $request->last_exam_result ?? 'Passed Successfully';

        // ডাটাগুলো ভিউতে পাস করা হলো
        $pdf = Pdf::loadView("pages.templates.certificates.{$type}", compact('student', 'date', 'leaving_reason', 'last_exam_result'))
                  ->setPaper('a4', 'landscape');

        return $pdf->stream("{$type}_{$student->student_name}.pdf");
    }
}