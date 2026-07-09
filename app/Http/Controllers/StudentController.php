<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\StudentAcademicHistory;
use Illuminate\Support\Facades\DB;
use Exception;

class StudentController extends Controller
{
  /**
     * স্টুডেন্ট লিস্ট এবং সার্চ ফিল্টার
     */
  public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $query = \App\Models\Student::with(['branch', 'schoolClass', 'section', 'shift', 'sessionYear'])->latest();

            // 🔍 ১. টেক্সট সার্চ লজিক
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('student_name', 'LIKE', "%{$search}%")
                      ->orWhere('student_identity', 'LIKE', "%{$search}%")
                      ->orWhere('roll_number', 'LIKE', "%{$search}%")
                      ->orWhere('father_mobile', 'LIKE', "%{$search}%");
                });
            }

            // 🔍 ২. ড্রপডাউন ফিল্টার (সবগুলো AND কন্ডিশন হিসেবে কাজ করবে)
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            if ($request->filled('session_year_id')) {
                $query->where('session_year_id', $request->session_year_id);
            }
            if ($request->filled('shift_id')) {
                $query->where('shift_id', $request->shift_id);
            }
            
            // 🚨 ৩. জেন্ডার ফিল্টার 🚨
            // (চেক করুন আপনার ডাটাবেস টেবিলের কলামের নাম 'gender' কি না)
            if ($request->filled('gender')) {
                $query->where('gender', $request->gender);
            }

            // পেজিনেশন
            $students = $query->paginate(15);
            
            return response()->json(['status' => 'success', 'studentData' => $students], 200);
            
        } catch (\Exception $e) {
            // যদি কোনো কলাম না মেলে বা এরর হয়, তাহলে আপনি ব্রাউজারের Network ট্যাবে আসল এররটা দেখতে পাবেন
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * নতুন স্টুডেন্ট ভর্তি (Admission)
     */
    public function store(Request $request): JsonResponse
    {
        // আপনার ডাটাবেস কলাম অনুযায়ী ভ্যালিডেশন আপডেট করা হয়েছে
        $request->validate([
            'student_name'          => 'required|string|max:255',
            'roll_number'           => 'required|string|max:50',
            'branch_id'             => 'required|exists:branches,id',
            'class_id'              => 'required|exists:classes,id',
            'section_id'            => 'required|exists:sections,id',
            'session_year_id'       => 'required|exists:session_years,id',
            'father_mobile'         => 'required|string|max:20',
            'mother_mobile'         => 'required|string|max:20',
            'guardian_mobile'       => 'required|string|max:20',
            'dob'                   => 'required|date',
            'gender'                => 'required|string',
            
            // নতুন অ্যাড্রেস ফিল্ডগুলো (আপনার টেবিল অনুযায়ী)
            'present_village'       => 'required|string|max:255',
            'present_post_office'   => 'required|string|max:255',
            'present_district'      => 'required|string|max:255',
            'present_division'      => 'required|string|max:255',
            'present_post_code'     => 'nullable|string|max:255',
            
            'permanent_village'     => 'required|string|max:255',
            'permanent_post_office' => 'required|string|max:255',
            'permanent_district'    => 'required|string|max:255',
            'permanent_division'    => 'required|string|max:255',
            'permanent_post_code'   => 'nullable|string|max:255',
            
            'photo'                 => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
            'document_file'         => 'nullable|mimes:pdf,jpeg,png,jpg|max:2048',   
            'card_number'           => 'nullable|string|unique:students,card_number',
            'father_name_bn'        => 'nullable|string|max:255',
            'mother_name_bn'        => 'nullable|string|max:255',
            'student_identity'      => 'nullable|string|max:255|unique:students,student_identity',
        ]);

        try {
            if ($request->filled('student_identity') && 
                !str_contains($request->student_identity, 'YYYY') && 
                !str_contains($request->student_identity, 'MM') && 
                !str_contains($request->student_identity, 'CLASS') && 
                !str_contains($request->student_identity, 'XXXX')) {
                $studentIdentity = $request->student_identity;
            } else {
                $year = date('Y');
                $month = date('m');
                
                $class = \App\Models\Classes::find($request->class_id);
                $className = $class ? $class->class_name : 'Class';
                $classShort = 'XX';
                $nameLower = strtolower($className);
                if (str_contains($nameLower, 'one') || str_contains($nameLower, '1')) $classShort = 'C1';
                elseif (str_contains($nameLower, 'two') || str_contains($nameLower, '2')) $classShort = 'C2';
                elseif (str_contains($nameLower, 'three') || str_contains($nameLower, '3')) $classShort = 'C3';
                elseif (str_contains($nameLower, 'four') || str_contains($nameLower, '4')) $classShort = 'C4';
                elseif (str_contains($nameLower, 'five') || str_contains($nameLower, '5')) $classShort = 'C5';
                elseif (str_contains($nameLower, 'six') || str_contains($nameLower, '6')) $classShort = 'C6';
                elseif (str_contains($nameLower, 'seven') || str_contains($nameLower, '7')) $classShort = 'C7';
                elseif (str_contains($nameLower, 'eight') || str_contains($nameLower, '8')) $classShort = 'C8';
                elseif (str_contains($nameLower, 'nine') || str_contains($nameLower, '9')) $classShort = 'C9';
                elseif (str_contains($nameLower, 'ten') || str_contains($nameLower, '10')) $classShort = 'C10';
                elseif (str_contains($nameLower, 'nursery')) $classShort = 'NUR';
                elseif (str_contains($nameLower, 'play')) $classShort = 'PLAY';
                elseif (str_contains($nameLower, 'baby')) $classShort = 'BABY';
                else {
                    $words = explode(' ', preg_replace('/[^a-zA-Z0-9\s]/', '', $className));
                    if (count($words) === 1) {
                        $classShort = strtoupper(substr($words[0], 0, 3));
                    } else {
                        $classShort = '';
                        foreach ($words as $w) {
                            if (!empty($w)) $classShort .= $w[0];
                        }
                        $classShort = strtoupper($classShort);
                    }
                }
                
                do {
                    $randomId = rand(1000, 9999);
                    $studentIdentity = "{$year}-{$month}-{$classShort}-{$randomId}";
                } while (Student::where('student_identity', $studentIdentity)->exists());
            }

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('student_photos', 'public');
            } else {
                if ($request->gender === 'Male') {
                    $photoPath = 'img/boy.png';
                } elseif ($request->gender === 'Female') {
                    $photoPath = 'img/girl.png';
                } else {
                    $photoPath = 'img/default-student.png';
                }
            }

            $documentPath = null;
            if ($request->hasFile('document_file')) {
                $documentPath = $request->file('document_file')->store('student_documents', 'public');
            }

            $student = Student::create([
                'student_identity'      => $studentIdentity,
                'card_number'           => $request->card_number,
                'roll_number'           => $request->roll_number,
                'student_name'          => $request->student_name,
                'name_in_bangla'        => $request->name_in_bangla,
                'birth_certificate'     => $request->birth_certificate,
                'blood_group'           => $request->blood_group,
                'religion'              => $request->religion,
                'dob'                   => $request->dob,
                'gender'                => $request->gender,
                'email'                 => $request->email,
                
                'father_name'           => $request->father_name,
                'father_name_bn'        => $request->father_name_bn,
                'father_nid'            => $request->father_nid,
                'father_mobile'         => $request->father_mobile,
                'father_occupation'     => $request->father_occupation,
                
                'mother_name'           => $request->mother_name,
                'mother_name_bn'        => $request->mother_name_bn,
                'mother_nid'            => $request->mother_nid,
                'mother_mobile'         => $request->mother_mobile,
                'mother_occupation'     => $request->mother_occupation,
                
                'guardian_name'         => $request->guardian_name,
                'guardian_mobile'       => $request->guardian_mobile,
                'guardian_occupation'   => $request->guardian_occupation,
                
                // ঠিকানা সমূহ (আপনার টেবিল অনুযায়ী)
                'present_village'       => $request->present_village,
                'present_post_office'   => $request->present_post_office,
                'present_district'      => $request->present_district,
                'present_division'      => $request->present_division,
                'present_post_code'     => $request->present_post_code,
                
                'permanent_village'     => $request->permanent_village,
                'permanent_post_office' => $request->permanent_post_office,
                'permanent_district'    => $request->permanent_district,
                'permanent_division'    => $request->permanent_division,
                'permanent_post_code'   => $request->permanent_post_code,
                
                'sms_status'            => $request->sms_status ?? 'Active',
                'photo'                 => $photoPath,
                'document_file'         => $documentPath,
                'branch_id'             => $request->branch_id,
                'class_id'              => $request->class_id,
                'section_id'            => $request->section_id,
                'shift_id'              => $request->shift_id,
                'session_year_id'       => $request->session_year_id,
                'user_id'               => Auth::id() ?? 1,
            ]);

            return response()->json(['status' => 'success', 'identity' => $studentIdentity], 201);
            
        } catch (Exception $e) {
            Log::error("Admission Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * স্টুডেন্টের বিস্তারিত ডাটা (Profile View)
     */
    public function show($id): JsonResponse
    {
        try {
            $student = Student::with(['branch', 'schoolClass', 'section', 'shift', 'sessionYear'])->findOrFail($id);
            
            // Attendance Summary Statistics
            $attendanceCount = \App\Models\Attendance::where('student_id', $id)->count();
            $presentCount = \App\Models\Attendance::where('student_id', $id)->where('status', 'Present')->count();
            $absentCount = \App\Models\Attendance::where('student_id', $id)->where('status', 'Absent')->count();
            $lateCount = \App\Models\Attendance::where('student_id', $id)->where('status', 'Late')->count();
            $attendancePercentage = $attendanceCount > 0 ? round(($presentCount / $attendanceCount) * 100, 1) : 100;
            
            // Exam Marks History
            $marks = \App\Models\Mark::with(['exam', 'subject'])
                ->where('student_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success', 
                'data' => $student,
                'attendance' => [
                    'total' => $attendanceCount,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'late' => $lateCount,
                    'percentage' => $attendancePercentage
                ],
                'marks' => $marks
            ], 200);
        } catch (Exception $e) {
            Log::error("Profile View Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }
    }

    /**
     * স্টুডেন্ট ডাটা আপডেট
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $student = Student::findOrFail($id);
            
            $defaultPhotos = ['img/boy.png', 'img/girl.png', 'img/default-student.png'];

            $photoPath = $student->getRawOriginal('photo'); 
            
            if ($request->hasFile('photo')) {
                if ($photoPath && !in_array($photoPath, $defaultPhotos) && Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $request->file('photo')->store('student_photos', 'public');
            } else {
                if (in_array($photoPath, $defaultPhotos)) {
                    if ($request->gender === 'Male') {
                        $photoPath = 'img/boy.png';
                    } elseif ($request->gender === 'Female') {
                        $photoPath = 'img/girl.png';
                    } else {
                        $photoPath = 'img/default-student.png';
                    }
                }
            }

            $documentPath = $student->getRawOriginal('document_file');
            if ($request->hasFile('document_file')) {
                if ($documentPath && Storage::disk('public')->exists($documentPath)) {
                    Storage::disk('public')->delete($documentPath);
                }
                $documentPath = $request->file('document_file')->store('student_documents', 'public');
            }

            $request->validate([
                'student_identity' => 'nullable|string|max:255|unique:students,student_identity,' . $id,
                'card_number'      => 'nullable|string|unique:students,card_number,' . $id,
            ]);

            // Security: allow updating student_identity if it is custom set and valid
            $excepts = ['photo', 'document_file', 'admission_number', 'admission_date'];
            if (!$request->filled('student_identity') || 
                str_contains($request->student_identity, 'YYYY') || 
                str_contains($request->student_identity, 'MM') || 
                str_contains($request->student_identity, 'CLASS') || 
                str_contains($request->student_identity, 'XXXX')) {
                $excepts[] = 'student_identity';
            }

            $data = $request->except($excepts);
            $data['photo'] = $photoPath;
            $data['document_file'] = $documentPath;

            $student->update($data);

            return response()->json(['status' => 'success', 'message' => 'Student updated successfully!'], 200);
        } catch (Exception $e) {
            Log::error("Update Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Update Failed!'], 500);
        }
    }

    /**
     * স্টুডেন্ট ডিলিট
     */
    public function destroy($id): JsonResponse
    {
        try {
            $student = Student::findOrFail($id);
            
            $defaultPhotos = ['img/boy.png', 'img/girl.png', 'img/default-student.png'];

            $rawPhoto = $student->getRawOriginal('photo');
            if ($rawPhoto && !in_array($rawPhoto, $defaultPhotos) && Storage::disk('public')->exists($rawPhoto)) {
                Storage::disk('public')->delete($rawPhoto);
            }

            $rawDoc = $student->getRawOriginal('document_file');
            if ($rawDoc && Storage::disk('public')->exists($rawDoc)) {
                Storage::disk('public')->delete($rawDoc);
            }
            
            $student->delete();
            return response()->json(['status' => 'success', 'message' => 'Student deleted successfully!'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Delete Failed!'], 500);
        }
    }


    public function promoteStudents(Request $request)
    {
        $request->validate([
            'to_session' => 'required',
            'to_class'   => 'required',
            'to_section' => 'required',
            'promote_student_ids' => 'required|array',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $studentIds = $request->promote_student_ids; 
            $newRolls   = $request->new_rolls; 
            $totalMarks = $request->total_marks; // 🆕 ফর্ম থেকে আসা মার্কস
            $grades     = $request->grades;      // 🆕 ফর্ম থেকে আসা গ্রেড

            foreach ($studentIds as $id) {
                $student = \App\Models\Student::find($id);
                
                if ($student) {
                    // ১. হিস্ট্রি টেবিলে রেকর্ড সেভ করা
                    \App\Models\StudentAcademicHistory::create([
                        'student_id'      => $student->id,
                        'session_year_id' => $student->session_year_id,
                        'class_id'        => $student->class_id,
                        'section_id'      => $student->section_id,
                        'roll_number'     => $student->roll_number,
                        'total_marks'     => $totalMarks[$id] ?? null, // 🆕 মার্কস সেভ হচ্ছে
                        'cgpa_or_grade'   => $grades[$id] ?? null,     // 🆕 গ্রেড সেভ হচ্ছে
                        'status'          => 'Promoted'
                    ]);

                    // ২. স্টুডেন্টের মেইন ডাটা আপডেট করা
                    $student->update([
                        'branch_id'       => $request->to_branch ?? $student->branch_id,
                        'session_year_id' => $request->to_session,
                        'class_id'        => $request->to_class,
                        'shift_id'        => $request->to_shift ?? $student->shift_id,
                        'section_id'      => $request->to_section,
                        'roll_number'     => $newRolls[$id] ?? $student->roll_number,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Students successfully promoted!'], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Promotion failed!', 'error' => $e->getMessage()], 500);
        }
    }

public function detectStudentInfo(Request $request)
    {
        $identity = $request->query('identity');
        
        // with('class') মুছে দিয়ে শুধু নিচের টুকু রাখুন
        $student = Student::where('student_identity', $identity)->first();

        if ($student) {
            return response()->json([
                'status' => 'success', 
                'student' => $student
            ], 200);
        }

        return response()->json([
            'status' => 'error', 
            'message' => 'Student not found'
        ], 404);
    }

    // প্রমোশনের জন্য নির্দিষ্ট স্টুডেন্ট লিস্ট আনার ফাংশন
    public function getStudentsForPromotion(Request $request)
    {
        $query = \App\Models\Student::query();

        // ড্রপডাউন থেকে আসা ফিল্টারগুলো
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('session_year_id')) {
            $query->where('session_year_id', $request->session_year_id);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        // ডাটাবেস থেকে স্টুডেন্ট নিয়ে আসা
        $students = $query->get();

        // একদম পরিচ্ছন্ন JSON রেসপন্স
        return response()->json([
            'status' => 'success',
            'data'   => $students
        ]);
    }

    /**
     * স্টুডেন্ট লিস্ট এক্সেল (CSV) ডাউনলোড
     */
    public function exportExcel(Request $request)
    {
        try {
            $query = Student::with(['branch', 'schoolClass', 'section', 'shift', 'sessionYear'])->latest();

            // apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('student_name', 'LIKE', "%{$search}%")
                      ->orWhere('student_identity', 'LIKE', "%{$search}%")
                      ->orWhere('roll_number', 'LIKE', "%{$search}%")
                      ->orWhere('father_mobile', 'LIKE', "%{$search}%");
                });
            }
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            if ($request->filled('session_year_id')) {
                $query->where('session_year_id', $request->session_year_id);
            }
            if ($request->filled('shift_id')) {
                $query->where('shift_id', $request->shift_id);
            }
            if ($request->filled('gender')) {
                $query->where('gender', $request->gender);
            }

            $students = $query->get();

            $filename = "student_list_" . date('Ymd_His') . ".csv";
            
            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $columns = [
                'SL', 'Student ID', 'Student Name', 'Class', 'Roll', 
                'Section', 'Shift', 'Branch', 'Session', 'Gender', 
                'Guardian Mobile', 'Father Mobile', 'Mother Mobile', 'Date of Birth'
            ];

            $callback = function() use($students, $columns) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM for Excel to recognize Bangla characters
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($file, $columns);

                foreach ($students as $key => $student) {
                    fputcsv($file, [
                        $key + 1,
                        $student->student_identity,
                        $student->student_name,
                        $student->schoolClass->class_name ?? 'N/A',
                        $student->roll_number ?? 'N/A',
                        $student->section->section_name ?? 'N/A',
                        $student->shift->shift_name ?? 'N/A',
                        $student->branch->branch_name ?? 'N/A',
                        $student->sessionYear->session_name ?? 'N/A',
                        $student->gender ?? 'N/A',
                        $student->guardian_mobile ?? 'N/A',
                        $student->father_mobile ?? 'N/A',
                        $student->mother_mobile ?? 'N/A',
                        $student->dob ? date('d-m-Y', strtotime($student->dob)) : 'N/A',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * স্টুডেন্ট লিস্ট পিডিএফ ডাউনলোড
     */
    public function exportPDF(Request $request)
    {
        try {
            // Increase memory and execution time limits to prevent exhaustion errors on large datasets
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '300');

            $query = Student::with(['branch', 'schoolClass', 'section', 'shift', 'sessionYear'])->latest();

            // apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('student_name', 'LIKE', "%{$search}%")
                      ->orWhere('student_identity', 'LIKE', "%{$search}%")
                      ->orWhere('roll_number', 'LIKE', "%{$search}%")
                      ->orWhere('father_mobile', 'LIKE', "%{$search}%");
                });
            }
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }
            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }
            if ($request->filled('session_year_id')) {
                $query->where('session_year_id', $request->session_year_id);
            }
            if ($request->filled('shift_id')) {
                $query->where('shift_id', $request->shift_id);
            }
            if ($request->filled('gender')) {
                $query->where('gender', $request->gender);
            }

            $students = $query->get();

            // Dynamic Branch Name
            $branchName = 'Pabna International School';
            if ($request->filled('branch_id')) {
                $branch = \App\Models\Branch::find($request->branch_id);
                if ($branch) {
                    $branchName = $branch->branch_name;
                }
            } elseif ($students->isNotEmpty() && $students->first()->branch) {
                $branchName = $students->first()->branch->branch_name;
            }

            // Load filters name for display in PDF
            $filters = [
                'branch'  => $request->filled('branch_id') ? (\App\Models\Branch::find($request->branch_id)->branch_name ?? 'N/A') : 'All Branches',
                'class'   => $request->filled('class_id') ? (\App\Models\Classes::find($request->class_id)->class_name ?? 'N/A') : 'All Classes',
                'section' => $request->filled('section_id') ? (\App\Models\Section::find($request->section_id)->section_name ?? 'N/A') : 'All Sections',
                'session' => $request->filled('session_year_id') ? (\App\Models\SessionYear::find($request->session_year_id)->session_name ?? 'N/A') : 'All Sessions',
                'shift'   => $request->filled('shift_id') ? (\App\Models\Shift::find($request->shift_id)->shift_name ?? 'N/A') : 'All Shifts',
                'gender'  => $request->filled('gender') ? $request->gender : 'All Genders',
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.students.pdf', compact('students', 'filters', 'branchName'))
                      ->setPaper('a4', 'portrait');

            return $pdf->stream('Student_List_Report.pdf');

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Detect raw RFID card number from the biometric device logs
     */
    public function scanRfidCard(Request $request): JsonResponse
    {
        try {
            $zkService = app(\App\Services\ZktecoService::class);
            
            if ($zkService->getMode() === 'simulation') {
                // Generate a mockup simulated card number
                $simulatedCard = '0010' . rand(100000, 999999);
                return response()->json([
                    'status' => 'success',
                    'card_number' => $simulatedCard,
                    'message' => 'Simulated card detected successfully: ' . $simulatedCard
                ], 200);
            }
            
            $zk = new \Jmrashed\Zkteco\Lib\ZKTeco($zkService->getIp(), $zkService->getPort());
            if (!$zk->connect()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to establish connection with ZKTeco biometric device.'
                ], 500);
            }
            
            $zk->disableDevice();
            $allLogs = $zk->getAttendance();
            $users = $zk->getUser();
            $zk->enableDevice();
            $zk->disconnect();
            
            if (empty($allLogs) || empty($users)) {
                $logCount = is_array($allLogs) ? count($allLogs) : 0;
                $userCount = is_array($users) ? count($users) : 0;
                return response()->json([
                    'status' => 'error',
                    'message' => "Scan failed. Device returned {$logCount} swipe logs and {$userCount} users. Make sure cards are successfully enrolled/verified on the physical machine first."
                ], 400);
            }
            
            // Map userid -> cardno
            $userCardMap = [];
            foreach ($users as $user) {
                if (isset($user['userid']) && !empty($user['cardno'])) {
                    $userCardMap[$user['userid']] = trim($user['cardno']);
                }
            }
            
            // Sort logs by timestamp descending to find the absolute newest entry
            usort($allLogs, function($a, $b) {
                return strcmp($b['timestamp'], $a['timestamp']);
            });
            
            $newestLog = $allLogs[0];
            $uid = $newestLog['id'];
            
            if (empty($userCardMap[$uid])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The most recent swipe record on the device has no RFID card number mapped in the device memory.'
                ], 400);
            }
            
            return response()->json([
                'status' => 'success',
                'card_number' => $userCardMap[$uid],
                'message' => 'Card swipe detected: ' . $userCardMap[$uid]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Biometric scan failed: ' . $e->getMessage()
            ], 500);
        }
    }
}