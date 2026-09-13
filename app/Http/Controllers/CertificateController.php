<?php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Classes;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function index()
    {
        $classes = Classes::all();

        // Get sections linked to classes via students
        $studentSectionPairs = Student::select('class_id', 'section_id')
            ->whereNotNull('section_id')
            ->distinct()
            ->with('section')
            ->get();

        $classSections = [];
        foreach ($studentSectionPairs as $pair) {
            if ($pair->section) {
                $classSections[$pair->class_id][] = [
                    'id'           => $pair->section->id,
                    'section_name' => $pair->section->section_name,
                ];
            }
        }

        return view('pages.certificates.index', compact('classes', 'classSections'));
    }

    public function getStudents(Request $request)
    {
        $query = Student::with(['schoolClass', 'section']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('roll_number')) {
            $query->where('roll_number', $request->roll_number);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('roll_number', $search)
                  ->orWhere('student_identity', 'like', "%{$search}%");
            });
        }

        $students = $query->orderByRaw('CAST(roll_number AS UNSIGNED) ASC')
            ->get()
            ->map(function ($s) {
                return [
                    'id'               => $s->id,
                    'student_identity' => $s->student_identity,
                    'student_name'     => $s->student_name,
                    'roll_number'      => $s->roll_number,
                    'class_id'         => $s->class_id,
                    'class_name'       => $s->schoolClass->class_name ?? '',
                    'section_id'       => $s->section_id,
                    'section_name'     => $s->section->section_name ?? '',
                    'father_name'      => $s->father_name ?? '',
                    'mother_name'      => $s->mother_name ?? '',
                ];
            });

        return response()->json($students);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required',
        ]);

        $student = null;

        // 1. Search by direct student_id (Identity or DB id)
        if ($request->filled('student_id')) {
            $student = Student::with(['schoolClass', 'sessionYear', 'branch'])
                ->where('student_identity', $request->student_id)
                ->orWhere('id', $request->student_id)
                ->first();
        }
        // 2. Search by class_id and roll_number
        elseif ($request->filled(['class_id', 'roll_number'])) {
            $query = Student::with(['schoolClass', 'sessionYear', 'branch'])
                ->where('class_id', $request->class_id)
                ->where('roll_number', $request->roll_number);

            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }

            $student = $query->first();
        }

        if (!$student) {
            return back()->with('error', 'Student not found with the provided details! Please check Class, Roll Number, or Student ID.');
        }

        $type = $request->type;
        $date = $request->issue_date ?? date('Y-m-d');
        
        // Extra TC details
        $leaving_reason = $request->leaving_reason ?? 'To study in another institution';
        $last_exam_result = $request->last_exam_result ?? 'Passed Successfully';

        // Assets and metadata for certificate
        $logoPath = public_path('img/macs_logo.jpeg');
        $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $signaturePath = public_path('img/signature.png');
        $signatureSrc = file_exists($signaturePath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath)) : '';

        $prefix = match($type) {
            'tc' => 'TC',
            'testimonial' => 'TEST',
            default => 'GC',
        };
        $sessionName = $student->sessionYear->session_name ?? date('Y');
        $certNo = 'MACS/' . $prefix . '/' . $sessionName . '/' . str_pad($student->id, 4, '0', STR_PAD_LEFT);

        // Load view and generate PDF
        $pdf = Pdf::loadView("pages.templates.certificates.{$type}", compact(
            'student', 'date', 'leaving_reason', 'last_exam_result', 'logoSrc', 'signatureSrc', 'certNo'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream("{$type}_{$student->student_name}.pdf");
    }
}