<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Classes; 
use App\Models\Subject;
use App\Models\Student; 
use App\Models\Mark;
use App\Models\Grade;
use App\Models\ExamSchedule;
use App\Models\Branch;        
use App\Models\SessionYear;   
use Illuminate\Http\Request;

class MarkController extends Controller
{
    public function index(Request $request)
    {
        // ড্রপডাউনের জন্য সব ডাটা নিয়ে আসা হচ্ছে
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $branches = Branch::all();
        $exams = Exam::orderBy('name', 'asc')->get();
        $classes = Classes::all();
        $subjects = Subject::all();

        $students = [];
        $exam_schedule = null;

        // ৫টি ফিল্ডই ফিলাপ করা থাকলে তবেই স্টুডেন্ট লোড হবে
        if ($request->filled(['session_year_id', 'branch_id', 'exam_id', 'class_id', 'subject_id'])) {
            
            // বিষয়টির মার্কস ডিস্ট্রিবিউশন আনা (ব্রাঞ্চ, ক্লাস ও সাবজেক্ট অনুযায়ী)
            $exam_schedule = $this->getExamSchedule(
                $request->class_id,
                $request->subject_id,
                $request->branch_id,
                $request->exam_id
            );

            // নির্দিষ্ট সেশন, ব্রাঞ্চ এবং ক্লাসের স্টুডেন্টদের আনা হচ্ছে
            $students = Student::where('session_year_id', $request->session_year_id)
                ->where('branch_id', $request->branch_id)
                ->where('class_id', $request->class_id)
                ->get()
                ->map(function ($student) use ($request, $exam_schedule) {
                    $mark = Mark::where('session_year_id', $request->session_year_id)
                        ->where('branch_id', $request->branch_id)
                        ->where('exam_id', $request->exam_id)
                        ->where('class_id', $request->class_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('student_id', $student->id) 
                        ->first();

                    // পূর্বে সংরক্ষিত কিন্তু ভুলবশত F হওয়া গ্রেড স্বয়ংক্রিয়ভাবে ঠিক করা
                    if ($mark && $exam_schedule) {
                        $fullMarks = (float)($exam_schedule->full_marks ?? 100);
                        $passMarks = (float)($exam_schedule->pass_marks ?? 33);
                        if ($mark->total_mark >= $passMarks && ($mark->letter_grade === 'F' || $mark->grade_point == 0)) {
                            $calc = $this->calculateGradeAndPoint($mark->total_mark, $fullMarks, $passMarks);
                            $mark->letter_grade = $calc['letter_grade'];
                            $mark->grade_point = $calc['grade_point'];
                            $mark->save();
                        }
                    }

                    $student->mark = $mark;
                    return $student;
                });
        }

        return view('pages.marks.index', compact('sessions', 'branches', 'exams', 'classes', 'subjects', 'students', 'exam_schedule'));
    }

    // AJAX এর মাধ্যমে রিয়েল-টাইম ডাটা সেভ করার ফাংশন
    public function storeAjax(Request $request)
    {
        $exam_schedule = $this->getExamSchedule(
            $request->class_id,
            $request->subject_id,
            $request->branch_id,
            $request->exam_id
        );

        $ct = (float)($request->ct_mark ?? 0);
        $written = (float)($request->written_mark ?? 0);
        $mcq = (float)($request->mcq_mark ?? 0);

        if ($ct < 0 || $written < 0 || $mcq < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Marks cannot be negative!'
            ], 422);
        }

        // বেস মার্কস ভ্যালিডেশন চেক
        if ($exam_schedule) {
            $isTen = $this->isClassTen($request->class_id);
            if ($isTen) {
                $maxMcq = (float)($exam_schedule->mcq_marks ?? 0);
                $maxWritten = (float)($exam_schedule->written_marks ?? 0);
                $maxPractical = (float)($exam_schedule->ct_marks ?? 0);

                if ($maxMcq <= 0) {
                    $mcq = 0;
                } elseif ($mcq > $maxMcq) {
                    return response()->json([
                        'success' => false,
                        'message' => "MCQ mark ({$mcq}) cannot exceed base mark of {$maxMcq}!"
                    ], 422);
                }

                if ($maxWritten <= 0) {
                    $written = 0;
                } elseif ($written > $maxWritten) {
                    return response()->json([
                        'success' => false,
                        'message' => "Written CQ mark ({$written}) cannot exceed base mark of {$maxWritten}!"
                    ], 422);
                }

                if ($maxPractical <= 0) {
                    $ct = 0;
                } elseif ($ct > $maxPractical) {
                    return response()->json([
                        'success' => false,
                        'message' => "Practical mark ({$ct}) cannot exceed base mark of {$maxPractical}!"
                    ], 422);
                }
            } else {
                $maxCt = (float)($exam_schedule->ct_marks ?? 0);
                $maxMt = (float)($exam_schedule->mcq_marks ?? 0);
                $maxTerminal = (float)($exam_schedule->written_marks ?? 0);

                if ($maxCt <= 0) {
                    $ct = 0;
                } elseif ($ct > $maxCt) {
                    return response()->json([
                        'success' => false,
                        'message' => "CT mark ({$ct}) cannot exceed base mark of {$maxCt}!"
                    ], 422);
                }

                if ($maxMt <= 0) {
                    $mcq = 0;
                } elseif ($mcq > $maxMt) {
                    return response()->json([
                        'success' => false,
                        'message' => "MT mark ({$mcq}) cannot exceed base mark of {$maxMt}!"
                    ], 422);
                }

                if ($maxTerminal <= 0) {
                    $written = 0;
                } elseif ($written > $maxTerminal) {
                    return response()->json([
                        'success' => false,
                        'message' => "Terminal mark ({$written}) cannot exceed base mark of {$maxTerminal}!"
                    ], 422);
                }
            }
        }

        // টোটাল মার্কস ক্যালকুলেট
        $total = $ct + $written + $mcq;

        // ফুল মার্কস ও পাস মার্কস নির্ধারণ
        $fullMarks = $exam_schedule && $exam_schedule->full_marks > 0 ? (float)$exam_schedule->full_marks : 100.00;
        $passMarks = $exam_schedule && $exam_schedule->pass_marks > 0 ? (float)$exam_schedule->pass_marks : 33.00;

        // গ্রেড ও গ্রেড পয়েন্ট ক্যালকুলেট
        $gradeResult = $this->calculateGradeAndPoint($total, $fullMarks, $passMarks);

        // স্টুডেন্টের সেকশন আইডি বের করা
        $student = Student::find($request->student_id);

        // ডাটাবেসে সেভ বা আপডেট করা
        $mark = Mark::updateOrCreate(
            [
                'session_year_id' => $request->session_year_id,
                'branch_id'       => $request->branch_id,
                'exam_id'         => $request->exam_id,
                'class_id'        => $request->class_id,
                'subject_id'      => $request->subject_id,
                'student_id'      => $request->student_id,
            ],
            [
                'section_id'   => $student->section_id ?? null,
                'ct_mark'      => $ct,
                'written_mark' => $written,
                'mcq_mark'     => $mcq,
                'total_mark'   => $total,
                'letter_grade' => $gradeResult['letter_grade'],
                'grade_point'  => $gradeResult['grade_point'],
            ]
        );

        // সফল হলে JSON রেসপন্স রিটার্ন
        return response()->json([
            'success'      => true, 
            'total'        => $total, 
            'letter_grade' => $mark->letter_grade,
            'grade_point'  => $mark->grade_point
        ]);
    }

    /**
     * Get Exam Schedule helper
     */
    protected function getExamSchedule($classId, $subjectId, $branchId, $examId = null)
    {
        $query = ExamSchedule::where('class_id', $classId)
            ->where('subject_id', $subjectId);

        if ($examId) {
            $schedule = (clone $query)->where('exam_id', $examId)
                ->where('branch_id', $branchId)
                ->first();
            if ($schedule) return $schedule;

            $schedule = (clone $query)->where('exam_id', $examId)->first();
            if ($schedule) return $schedule;
        }

        $schedule = (clone $query)->where('branch_id', $branchId)->first();
        if ($schedule) return $schedule;

        return $query->first();
    }

    /**
     * Check if class is Class Ten
     */
    protected function isClassTen($classId)
    {
        $class = Classes::find($classId);
        if (!$class) return false;
        $name = strtolower(trim($class->class_name));
        return in_array($name, ['ten', 'class ten', '10', 'class 10']);
    }

    /**
     * Calculate letter grade and grade point based on total, full_marks, and pass_marks
     */
    protected function calculateGradeAndPoint($total, $fullMarks = 100.00, $passMarks = 33.00)
    {
        $total = (float)$total;
        $fullMarks = (float)$fullMarks > 0 ? (float)$fullMarks : 100.00;
        $passMarks = (float)$passMarks;

        // পাস মার্কের কম পেলে নিশ্চিত ফেইল (F / 0.00)
        if ($total < $passMarks) {
            return [
                'letter_grade' => 'F',
                'grade_point'  => 0.00,
            ];
        }

        // ফুল মার্কসের সাথে পার্সেন্টেজ হিসাব
        $percentage = ($total / $fullMarks) * 100.0;

        // ডায়নামিক Grade টেবিল সেটআপ থাকলে চেক করা
        $grades = Grade::orderBy('min_mark', 'desc')->get();
        if ($grades->isNotEmpty()) {
            foreach ($grades as $g) {
                if ($percentage >= (float)$g->min_mark) {
                    return [
                        'letter_grade' => $g->grade_name,
                        'grade_point'  => (float)$g->grade_point,
                    ];
                }
            }
        }

        // স্ট্যান্ডার্ড বাংলাদেশ / MACS গ্রেডিং স্কেল ফলব্যাক
        if ($percentage >= 80) {
            return ['letter_grade' => 'A+', 'grade_point' => 5.00];
        } elseif ($percentage >= 70) {
            return ['letter_grade' => 'A',  'grade_point' => 4.00];
        } elseif ($percentage >= 60) {
            return ['letter_grade' => 'A-', 'grade_point' => 3.50];
        } elseif ($percentage >= 50) {
            return ['letter_grade' => 'B',  'grade_point' => 3.00];
        } elseif ($percentage >= 40) {
            return ['letter_grade' => 'C',  'grade_point' => 2.00];
        } elseif ($percentage >= 33) {
            return ['letter_grade' => 'D',  'grade_point' => 1.00];
        } else {
            return ['letter_grade' => 'F',  'grade_point' => 0.00];
        }
    }
}