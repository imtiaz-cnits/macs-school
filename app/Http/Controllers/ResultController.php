<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Student;
use App\Models\Mark;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\ExamSchedule;
use App\Models\Attendance;
use Illuminate\Http\Request;
use PDF; // DomPDF

class ResultController extends Controller
{
    // Search page for marksheets
    public function index()
    {
        $exams = Exam::orderBy('name', 'asc')->get();
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $classes = Classes::all();
        $branches = Branch::all();
        
        return view('pages.results.index', compact('exams', 'sessions', 'classes', 'branches'));
    }

    // PDF generation router method: delegates to Single or Combined marksheet
    public function generate(Request $request)
    {
        $reportType = $request->input('report_type', 'single');

        if ($reportType === 'combined') {
            return $this->generateCombinedMarksheet($request);
        }

        return $this->generateSingleMarksheet($request);
    }

    /**
     * Generate Single Exam Marksheet PDF (Landscape A4)
     */
    protected function generateSingleMarksheet(Request $request)
    {
        $request->validate([
            'session_year_id'  => 'required',
            'exam_id'          => 'required',
            'student_identity' => 'required',
        ]);

        $student = $this->findStudent($request);

        if (!$student) {
            return back()->withErrors(['error' => 'Student not found. Please verify the Student ID or Roll number!']);
        }

        $exam = Exam::findOrFail($request->exam_id);
        $sessionYear = SessionYear::findOrFail($request->session_year_id);

        // Fetch student's marks with subjects
        $marks = Mark::with('subject')
            ->where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('session_year_id', $request->session_year_id)
            ->whereHas('subject')
            ->get();

        if ($marks->isEmpty()) {
            return back()->withErrors(['error' => 'No marks entered for this student in the selected exam and session.']);
        }

        // Exam schedules for full marks reference
        $schedules = ExamSchedule::where('class_id', $student->class_id)
            ->when($student->branch_id, fn($q) => $q->where('branch_id', $student->branch_id))
            ->get()
            ->keyBy('subject_id');

        if ($schedules->isEmpty()) {
            $schedules = ExamSchedule::where('class_id', $student->class_id)->get()->keyBy('subject_id');
        }

        // Calculate highest mark for each subject in this class/exam
        $topMarks = Mark::where('session_year_id', $request->session_year_id)
            ->where('class_id', $student->class_id)
            ->where('exam_id', $exam->id)
            ->groupBy('subject_id')
            ->selectRaw('subject_id, MAX(total_mark) as top_mark')
            ->pluck('top_mark', 'subject_id');

        $subjectResults = [];
        $totalMarks = 0;
        $totalGradePoints = 0;
        $hasFailed = false;

        foreach ($marks as $mark) {
            $subId = $mark->subject_id;
            $fullMarks = isset($schedules[$subId]) ? (float)$schedules[$subId]->full_marks : 100.00;
            if ($fullMarks <= 0) $fullMarks = 100.00;

            $total = (float)$mark->total_mark;
            $gradeInfo = $this->getGradeAndPoint($total, $fullMarks);

            if ($gradeInfo['grade'] === 'F') {
                $hasFailed = true;
            }

            $subjectResults[] = [
                'subject_name'  => self::formatSubjectName($mark->subject->subject_name),
                'subject_code'  => $mark->subject->subject_code ?? '',
                'full_marks'    => (int)$fullMarks,
                'ct_mark'       => (float)$mark->ct_mark,
                'mcq_mark'      => (float)$mark->mcq_mark,
                'written_mark'  => (float)$mark->written_mark,
                'total_mark'    => $total,
                'letter_grade'  => $gradeInfo['grade'],
                'grade_point'   => $gradeInfo['point'],
                'top_mark'      => (float)($topMarks[$subId] ?? $total),
            ];

            $totalMarks += $total;
            $totalGradePoints += $gradeInfo['point'];
        }

        $subjectCount = count($subjectResults);
        $cgpa = (!$hasFailed && $subjectCount > 0) ? round($totalGradePoints / $subjectCount, 2) : 0.00;
        $finalGrade = $hasFailed ? 'F' : $this->getFinalGrade($cgpa);
        $remark = $this->getRemark($cgpa, $hasFailed);

        // Merit Position calculations
        $meritPosition = $this->calculateMeritPositions(
            $request->session_year_id,
            $student->branch_id,
            $student->class_id,
            $exam->id,
            $student->id,
            $student->section_id,
            $student->shift_id
        );

        // Attendance counts
        $attendance = $this->getAttendanceForStudent($student->id, $request->session_year_id);

        // Total students in this class
        $totalClassStudents = Student::where('class_id', $student->class_id)
            ->where('session_year_id', $request->session_year_id)
            ->count();

        // Assets base64
        $logoSrc = $this->getLogoBase64();
        $photoSrc = $this->getPhotoBase64($student);
        $signatureSrc = $this->getSignatureBase64();

        $data = array_merge([
            'student'            => $student,
            'exam'               => $exam,
            'sessionYear'        => $sessionYear,
            'subjectResults'     => $subjectResults,
            'totalMarks'         => $totalMarks,
            'cgpa'               => $cgpa,
            'finalGrade'         => $finalGrade,
            'remark'             => $remark,
            'meritPosition'      => $meritPosition,
            'attendance'         => $attendance,
            'totalClassStudents' => $totalClassStudents,
            'logoSrc'            => $logoSrc,
            'photoSrc'           => $photoSrc,
            'signatureSrc'       => $signatureSrc,
        ], self::getFontPaths());

        $pdf = PDF::setPaper('a4', 'portrait')
            ->loadView('pages.results.marksheet_single_pdf', $data)
            ->setPaper('a4', 'portrait');
        return $pdf->stream('Progress_Report_'.$student->student_identity.'.pdf');
    }

    /**
     * Generate Combined 3-Term Marksheet PDF (Landscape A4)
     */
    protected function generateCombinedMarksheet(Request $request)
    {
        $request->validate([
            'session_year_id'  => 'required',
            'student_identity' => 'required',
        ]);

        $student = $this->findStudent($request);

        if (!$student) {
            return back()->withErrors(['error' => 'Student not found. Please check Student ID or Roll number!']);
        }

        $sessionYear = SessionYear::findOrFail($request->session_year_id);

        // Find the 3 terminal exams
        $allExams = Exam::all();
        $exam1 = $allExams->first(fn($e) => stripos($e->name, '1st') !== false && stripos($e->name, 'term') !== false)
                 ?? $allExams->first(fn($e) => stripos($e->name, '1st') !== false);
        $exam2 = $allExams->first(fn($e) => stripos($e->name, '2nd') !== false && stripos($e->name, 'term') !== false)
                 ?? $allExams->first(fn($e) => stripos($e->name, '2nd') !== false);
        $exam3 = $allExams->first(fn($e) => stripos($e->name, 'annual') !== false || stripos($e->name, '3rd') !== false)
                 ?? $allExams->first(fn($e) => stripos($e->name, 'final') !== false);

        // Fallback to first 3 exams if naming pattern differs
        if (!$exam1) $exam1 = $allExams->get(0);
        if (!$exam2) $exam2 = $allExams->get(1);
        if (!$exam3) $exam3 = $allExams->get(2);

        $exam1Id = $exam1 ? $exam1->id : 0;
        $exam2Id = $exam2 ? $exam2->id : 0;
        $exam3Id = $exam3 ? $exam3->id : 0;

        // Fetch marks for all exams
        $allStudentMarks = Mark::with('subject')
            ->where('student_id', $student->id)
            ->where('session_year_id', $request->session_year_id)
            ->whereHas('subject')
            ->get();

        $marks1 = $allStudentMarks->where('exam_id', $exam1Id)->keyBy('subject_id');
        $marks2 = $allStudentMarks->where('exam_id', $exam2Id)->keyBy('subject_id');
        $marks3 = $allStudentMarks->where('exam_id', $exam3Id)->keyBy('subject_id');

        if ($allStudentMarks->isEmpty()) {
            return back()->withErrors(['error' => 'No marks found for this student across terms in the selected session.']);
        }

        // Distinct subjects
        $subjectIds = $allStudentMarks->pluck('subject_id')->unique();
        $subjects = \App\Models\Subject::whereIn('id', $subjectIds)->get()->keyBy('id');

        // Schedules for full marks
        $schedules = ExamSchedule::where('class_id', $student->class_id)
            ->when($student->branch_id, fn($q) => $q->where('branch_id', $student->branch_id))
            ->get()
            ->keyBy('subject_id');

        // Top marks for Term 3
        $topMarksTerm3 = Mark::where('session_year_id', $request->session_year_id)
            ->where('class_id', $student->class_id)
            ->where('exam_id', $exam3Id)
            ->groupBy('subject_id')
            ->selectRaw('subject_id, MAX(total_mark) as top_mark')
            ->pluck('top_mark', 'subject_id');

        // Combined top marks across all 3 terms per subject
        $combinedTopMarks = Mark::where('session_year_id', $request->session_year_id)
            ->where('class_id', $student->class_id)
            ->whereIn('exam_id', array_filter([$exam1Id, $exam2Id, $exam3Id]))
            ->groupBy('student_id', 'subject_id')
            ->selectRaw('subject_id, SUM(total_mark) as sum_total')
            ->get()
            ->groupBy('subject_id')
            ->map(fn($group) => $group->max('sum_total'));

        $combinedSubjectResults = [];
        $grandTotalMarks = 0;
        $totalGradePoints = 0;
        $hasFailed = false;

        foreach ($subjects as $subId => $subject) {
            $m1 = $marks1->get($subId);
            $m2 = $marks2->get($subId);
            $m3 = $marks3->get($subId);

            $fullMark = isset($schedules[$subId]) ? (float)$schedules[$subId]->full_marks : 100.00;
            if ($fullMark <= 0) $fullMark = 100.00;

            $t1_total = $m1 ? (float)$m1->total_mark : 0.00;
            $t2_total = $m2 ? (float)$m2->total_mark : 0.00;
            $t3_total = $m3 ? (float)$m3->total_mark : 0.00;

            $term3Grade = $this->getGradeAndPoint($t3_total, $fullMark);

            // Final total = sum of totals of 3 terms
            $finalTotal = $t1_total + $t2_total + $t3_total;
            $finalFullMarks = $fullMark * 3;
            $finalGradeInfo = $this->getGradeAndPoint($finalTotal, $finalFullMarks);

            if ($finalGradeInfo['grade'] === 'F') {
                $hasFailed = true;
            }

            $combinedSubjectResults[] = [
                'subject_name' => self::formatSubjectName($subject->subject_name),
                'full_marks'   => (int)$fullMark,
                'term1' => [
                    'ct'       => $m1 ? (float)$m1->ct_mark : 0.00,
                    'mt'       => $m1 ? (float)$m1->mcq_mark : 0.00,
                    'terminal' => $m1 ? (float)$m1->written_mark : 0.00,
                    'total'    => $t1_total,
                ],
                'term2' => [
                    'ct'       => $m2 ? (float)$m2->ct_mark : 0.00,
                    'mt'       => $m2 ? (float)$m2->mcq_mark : 0.00,
                    'terminal' => $m2 ? (float)$m2->written_mark : 0.00,
                    'total'    => $t2_total,
                ],
                'term3' => [
                    'ct'           => $m3 ? (float)$m3->ct_mark : 0.00,
                    'mt'           => $m3 ? (float)$m3->mcq_mark : 0.00,
                    'terminal'     => $m3 ? (float)$m3->written_mark : 0.00,
                    'total'        => $t3_total,
                    'letter_grade' => $term3Grade['grade'],
                    'grade_point'  => $term3Grade['point'],
                    'top_mark'     => (float)($topMarksTerm3[$subId] ?? $t3_total),
                ],
                'final' => [
                    'total'        => $finalTotal,
                    'letter_grade' => $finalGradeInfo['grade'],
                    'grade_point'  => $finalGradeInfo['point'],
                    'top_mark'     => (float)($combinedTopMarks[$subId] ?? $finalTotal),
                ],
            ];

            $grandTotalMarks += $finalTotal;
            $totalGradePoints += $finalGradeInfo['point'];
        }

        $subjectCount = count($combinedSubjectResults);
        $cgpa = (!$hasFailed && $subjectCount > 0) ? round($totalGradePoints / $subjectCount, 2) : 0.00;
        $finalGrade = $hasFailed ? 'F' : $this->getFinalGrade($cgpa);
        $remark = $this->getRemark($cgpa, $hasFailed);

        // Multi-row merit positions & attendance for the 3 terms
        $examMerits = [];
        $activeTerms = [
            ['exam' => $exam3, 'id' => $exam3Id],
            ['exam' => $exam2, 'id' => $exam2Id],
            ['exam' => $exam1, 'id' => $exam1Id],
        ];

        foreach ($activeTerms as $t) {
            if (!$t['exam']) continue;
            $pos = $this->calculateMeritPositions(
                $request->session_year_id,
                $student->branch_id,
                $student->class_id,
                $t['id'],
                $student->id,
                $student->section_id,
                $student->shift_id
            );
            $examMerits[] = [
                'exam_name'    => $t['exam']->name,
                'section_wise' => $pos['section_wise'],
                'shift_wise'   => $pos['shift_wise'],
                'class_wise'   => $pos['class_wise'],
                'working_days' => '',
                'present'      => '',
                'absent'       => '',
            ];
        }

        $totalClassStudents = Student::where('class_id', $student->class_id)
            ->where('session_year_id', $request->session_year_id)
            ->count();

        // Assets base64
        $logoSrc = $this->getLogoBase64();
        $photoSrc = $this->getPhotoBase64($student);
        $signatureSrc = $this->getSignatureBase64();

        $data = array_merge([
            'student'                => $student,
            'sessionYear'            => $sessionYear,
            'finalExamName'          => $exam3 ? $exam3->name : 'Annual Exam',
            'combinedSubjectResults' => $combinedSubjectResults,
            'grandTotalMarks'        => $grandTotalMarks,
            'cgpa'                   => $cgpa,
            'finalGrade'             => $finalGrade,
            'remark'                 => $remark,
            'examMerits'             => $examMerits,
            'totalClassStudents'     => $totalClassStudents,
            'logoSrc'                => $logoSrc,
            'photoSrc'               => $photoSrc,
            'signatureSrc'           => $signatureSrc,
        ], self::getFontPaths());

        $pdf = PDF::loadView('pages.results.marksheet_combined_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('Progress_Report_Combined_'.$student->student_identity.'.pdf');
    }

    /**
     * Locate student by ID, Identity, or Class + Roll
     */
    protected function findStudent(Request $request)
    {
        return Student::with(['schoolClass', 'branch', 'section', 'shift', 'sessionYear'])
            ->where(function($q) use ($request) {
                $q->where('student_identity', $request->student_identity)
                  ->orWhere('id', $request->student_identity);
                if ($request->filled('class_id')) {
                    $q->orWhere(function($sub) use ($request) {
                        $sub->where('class_id', $request->class_id)
                            ->where('roll_number', (string)$request->student_identity);
                    });
                }
            })
            ->first();
    }

    /**
     * Convert obtained marks to standard Grade and Grade Point
     */
    protected function getGradeAndPoint($obtainedMarks, $fullMarks)
    {
        if ($fullMarks <= 0) $fullMarks = 100.00;
        $pct = ($obtainedMarks / $fullMarks) * 100;

        if ($pct >= 80) return ['grade' => 'A+', 'point' => 5.0, 'comment' => 'Excellent'];
        if ($pct >= 70) return ['grade' => 'A',  'point' => 4.0, 'comment' => 'Very Good'];
        if ($pct >= 60) return ['grade' => 'A-', 'point' => 3.5, 'comment' => 'Good'];
        if ($pct >= 50) return ['grade' => 'B',  'point' => 3.0, 'comment' => 'Average'];
        if ($pct >= 40) return ['grade' => 'C',  'point' => 2.0, 'comment' => 'Poor'];
        if ($pct >= 33) return ['grade' => 'D',  'point' => 1.0, 'comment' => 'Very Poor'];
        return ['grade' => 'F', 'point' => 0.0, 'comment' => 'Fail'];
    }

    /**
     * Convert GPA to Letter Grade
     */
    private function getFinalGrade($cgpa)
    {
        if ($cgpa >= 5.0) return 'A+';
        if ($cgpa >= 4.0) return 'A';
        if ($cgpa >= 3.5) return 'A-';
        if ($cgpa >= 3.0) return 'B';
        if ($cgpa >= 2.0) return 'C';
        if ($cgpa >= 1.0) return 'D';
        return 'F';
    }

    /**
     * Generate Comment / Remark based on GPA
     */
    protected function getRemark($gpa, $isFailed)
    {
        if ($isFailed || $gpa == 0) return 'Fail';
        if ($gpa >= 5.0) return 'Excellent';
        if ($gpa >= 4.0) return 'Very Good';
        if ($gpa >= 3.5) return 'Good';
        if ($gpa >= 3.0) return 'Average';
        if ($gpa >= 2.0) return 'Poor';
        return 'Very Poor';
    }

    /**
     * Compute Section-wise, Shift-wise, and Class-wise merit ranks
     */
    protected function calculateMeritPositions($sessionYearId, $branchId, $classId, $examId, $studentId, $sectionId, $shiftId)
    {
        $allMarks = Mark::where('session_year_id', $sessionYearId)
            ->where('class_id', $classId)
            ->where('exam_id', $examId)
            ->get();

        if ($allMarks->isEmpty()) {
            return ['class_wise' => '-', 'section_wise' => '-', 'shift_wise' => '-'];
        }

        $students = Student::where('class_id', $classId)
            ->where('session_year_id', $sessionYearId)
            ->get();

        $scores = [];
        foreach ($students as $st) {
            $stMarks = $allMarks->where('student_id', $st->id);
            if ($stMarks->isEmpty()) continue;

            $total = $stMarks->sum('total_mark');
            $points = $stMarks->sum('grade_point');
            $count = $stMarks->count();
            $failed = $stMarks->contains(fn($m) => $m->letter_grade === 'F' || $m->letter_grade === 'Fail');

            $gpa = (!$failed && $count > 0) ? ($points / $count) : 0.00;

            $scores[] = [
                'student_id' => $st->id,
                'section_id' => $st->section_id,
                'shift_id'   => $st->shift_id,
                'gpa'        => $gpa,
                'total'      => $total,
            ];
        }

        // Sort descending: GPA first, then Total marks
        usort($scores, function($a, $b) {
            if ($a['gpa'] == $b['gpa']) {
                return $b['total'] <=> $a['total'];
            }
            return $b['gpa'] <=> $a['gpa'];
        });

        $classRank = 0;
        $sectionRank = 0;
        $shiftRank = 0;

        $secCount = 0;
        $shiftCount = 0;

        foreach ($scores as $idx => $s) {
            if ($s['section_id'] == $sectionId) {
                $secCount++;
                if ($s['student_id'] == $studentId) $sectionRank = $secCount;
            }
            if ($s['shift_id'] == $shiftId) {
                $shiftCount++;
                if ($s['student_id'] == $studentId) $shiftRank = $shiftCount;
            }
            if ($s['student_id'] == $studentId) {
                $classRank = $idx + 1;
            }
        }

        return [
            'class_wise'   => $classRank > 0 ? $classRank : '-',
            'section_wise' => $sectionRank > 0 ? $sectionRank : '-',
            'shift_wise'   => $shiftRank > 0 ? $shiftRank : '-',
        ];
    }

    /**
     * Fetch Attendance counts for student
     */
    protected function getAttendanceForStudent($studentId, $sessionYearId)
    {
        $records = Attendance::where('student_id', $studentId)
            ->where('session_year_id', $sessionYearId)
            ->get();

        if ($records->isEmpty()) {
            return ['working_days' => '-', 'present' => '-', 'absent' => '-'];
        }

        $present = $records->whereIn('status', ['Present', 'present', '1', 1])->count();
        $absent = $records->whereIn('status', ['Absent', 'absent', '0', 0])->count();

        return [
            'working_days' => $records->count(),
            'present'      => $present,
            'absent'       => $absent,
        ];
    }

    /**
     * Base64 helpers for Logo, Photo, Signature
     */
    protected function getLogoBase64()
    {
        $logoPath = public_path('img/macs_logo.jpeg');
        if (file_exists($logoPath)) {
            return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }
        $fallbackPng = public_path('img/logo.png');
        if (file_exists($fallbackPng)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($fallbackPng));
        }
        return '';
    }

    protected function getPhotoBase64($student)
    {
        if (!empty($student->photo)) {
            $path = public_path($student->photo);
            if (file_exists($path) && is_file($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $mime = strtolower($ext) === 'png' ? 'image/png' : 'image/jpeg';
                return "data:{$mime};base64," . base64_encode(file_get_contents($path));
            }
        }
        $default = public_path('img/boy.png');
        if (file_exists($default)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($default));
        }
        return '';
    }

    protected function getSignatureBase64()
    {
        $path = public_path('img/signature.png');
        if (file_exists($path)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
        }
        return '';
    }

    // Tabulation sheet search page
    public function tabulationIndex()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $branches = Branch::all();
        $exams = Exam::orderBy('name', 'asc')->get();
        $classes = Classes::all();
        return view('pages.results.tabulation_index', compact('sessions', 'branches', 'exams', 'classes'));
    }

    // Tabulation sheet PDF generation logic
    public function tabulationGenerate(Request $request)
    {
        $request->validate([
            'session_year_id' => 'required',
            'branch_id'       => 'required',
            'exam_id'         => 'required',
            'class_id'        => 'required',
        ]);

        $exam = Exam::find($request->exam_id);
        $schoolClass = Classes::find($request->class_id);
        $branch = Branch::find($request->branch_id);

        $schedules = ExamSchedule::with('subject')
            ->where('class_id', $schoolClass->id)
            ->when($branch, fn($q) => $q->where('branch_id', $branch->id))
            ->get();

        if ($schedules->isEmpty()) {
            $schedules = ExamSchedule::with('subject')
                ->where('class_id', $schoolClass->id)
                ->get();
        }

        if ($schedules->isEmpty()) {
            return back()->withErrors(['error' => 'No subjects found for this class in Exam Subject Setup.']);
        }

        $students = Student::where('session_year_id', $request->session_year_id)
            ->where('branch_id', $branch->id)
            ->where('class_id', $schoolClass->id)
            ->get();

        $allMarks = Mark::where('session_year_id', $request->session_year_id)
            ->where('branch_id', $branch->id)
            ->where('exam_id', $exam->id)
            ->where('class_id', $schoolClass->id)
            ->get();

        $studentData = [];

        foreach ($students as $student) {
            $studentMarks = $allMarks->where('student_id', $student->id);
            $grandTotal = $studentMarks->sum('total_mark');
            $totalGradePoints = $studentMarks->sum('grade_point');
            $subjectCount = $studentMarks->count();

            $is_failed = $studentMarks->contains(function ($m) {
                return $m->letter_grade == 'F' || $m->letter_grade == 'Fail';
            });

            $cgpa = 0.00;
            if (!$is_failed && $subjectCount > 0) {
                $cgpa = number_format($totalGradePoints / $subjectCount, 2);
            }
            $finalGrade = $is_failed ? 'F' : $this->getFinalGrade($cgpa);

            $studentData[] = (object)[
                'student'     => $student,
                'marks'       => $studentMarks->keyBy('subject_id'),
                'grand_total' => $grandTotal,
                'cgpa'        => $is_failed ? '0.00' : $cgpa,
                'final_grade' => $finalGrade
            ];
        }

        usort($studentData, function($a, $b) {
            if ($a->cgpa == $b->cgpa) {
                return $b->grand_total <=> $a->grand_total;
            }
            return $b->cgpa <=> $a->cgpa;
        });

        $data = [
            'exam'        => $exam,
            'schoolClass' => $schoolClass,
            'branch'      => $branch,
            'schedules'   => $schedules,
            'studentData' => $studentData
        ];

        $pdf = PDF::loadView('pages.results.tabulation_pdf', $data)->setPaper('legal', 'landscape');
        return $pdf->stream('Tabulation_Sheet_'.$schoolClass->class_name.'.pdf');
    }

    /**
     * Helper to return absolute TrueType font paths formatted for DomPDF CSS
     */
    public static function getFontPaths(): array
    {
        return [
            'fontInterRegular'   => str_replace('\\', '/', public_path('fonts/Inter-Regular.ttf')),
            'fontInterSemiBold'  => str_replace('\\', '/', public_path('fonts/Inter-SemiBold.ttf')),
            'fontInterBold'      => str_replace('\\', '/', public_path('fonts/Inter-Bold.ttf')),
            'fontInterExtraBold' => str_replace('\\', '/', public_path('fonts/Inter-ExtraBold.ttf')),
        ];
    }

    /**
     * Map database Bengali subject names to clean English titles for marksheet rendering
     */
    public static function formatSubjectName(?string $name): string
    {
        if (empty($name)) return '';

        $map = [
            'বাংলা' => 'Bangla',
            'বাংলা ১ম পত্র' => 'Bangla 1st Paper',
            'বাংলা ২য় পত্র' => 'Bangla 2nd Paper',
            'বাংলা ২য় পত্র' => 'Bangla 2nd Paper',
            'ইংরেজি' => 'English',
            'ইংরেজী' => 'English',
            'ইংরেজি ১ম পত্র' => 'English 1st Paper',
            'ইংরেজী ১ম পত্র' => 'English 1st Paper',
            'ইংরেজি ২য় পত্র' => 'English 2nd Paper',
            'ইংরেজী ২য় পত্র' => 'English 2nd Paper',
            'গণিত' => 'Mathematics',
            'সাধারণ গণিত' => 'Mathematics',
            'আরবী / ধর্মশিক্ষা' => 'Arabic & Islamic Studies',
            'আরবি / ধর্মশিক্ষা' => 'Arabic & Islamic Studies',
            'ড্রইং' => 'Drawing',
            'সাধারণ জ্ঞান' => 'General Knowledge',
            'সমাজ' => 'Social Studies',
            'সামাজিক বিজ্ঞান' => 'Social Science',
            'বিজ্ঞান' => 'General Science',
            'বাংলাদেশ ও বিশ্বপরিচয়' => 'Bangladesh & Global Studies',
            'বাংলাদেশ ও বিশ্বপরিচয়' => 'Bangladesh & Global Studies',
            'বাংলাদেশ ও বিশ্বপরিচয় / সাধারণ বিজ্ঞান' => 'Bangladesh & Global Studies',
            'বাংলাদেশ ও বিশ্বপরিচয় / সাধারণ বিজ্ঞান' => 'Bangladesh & Global Studies',
            'ইসলাম / হিন্দু শিক্ষা' => 'Islamic / Hindu Studies',
            'ইসলাম ও নৈতিক শিক্ষা' => 'Islamic & Moral Studies',
            'ইসলাম শিক্ষা' => 'Islamic Studies',
            'শারীরিক শিক্ষা' => 'Physical Education',
            'তথ্য ও যোগাযোগ প্রযুক্তি' => 'Information & Communication Tech (ICT)',
            'কৃষি শিক্ষা' => 'Agriculture Studies',
            'জীববিজ্ঞান / ভূগোল' => 'Biology / Geography',
            'রসায়ন / অর্থনীতি' => 'Chemistry / Economics',
            'রসায়ন / অর্থনীতি' => 'Chemistry / Economics',
            'পদার্থ / ইতিহাস' => 'Physics / History',
            'উচ্চতর গণিত / কৃষি শিক্ষা' => 'Higher Math / Agriculture',
            'উচ্চতর গণিত' => 'Higher Mathematics',
            'S.B.A' => 'S.B.A',
        ];

        $trimmed = trim($name);
        if (isset($map[$trimmed])) {
            return $map[$trimmed];
        }

        // Partial match check
        foreach ($map as $bn => $en) {
            if (mb_strpos($trimmed, $bn) !== false) {
                return $en;
            }
        }

        return $trimmed;
    }
}