<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\Branch;
use App\Models\Student;
use App\Models\SessionYear;
use App\Models\Section;
use App\Models\SmsLog;
use App\Models\SmsSchedule;
use App\Models\Exam;
use App\Models\Mark;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmsController extends Controller
{
    /**
     * Display the General Notice SMS broadcast page.
     */
    public function generalNotice(\App\Services\SmsService $smsService)
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $classes = Classes::all(); // custom_order global scope applied automatically
        $branches = Branch::all();
        $sections = Section::all();
        
        $smsRate = (float) config('services.mim_sms.rate_per_sms', 0.33);
        $testMode = (bool) config('services.mim_sms.test_mode', false);
        $balanceInfo = $smsService->getBalance();
        $balanceVal = is_array($balanceInfo) ? ($balanceInfo['balance'] ?? null) : $balanceInfo;
        $currentBalance = is_numeric($balanceVal) ? (float) $balanceVal : null;

        $classSections = Student::where('status', 'Active')
            ->where('sms_status', 'Active')
            ->select('class_id', 'section_id')
            ->distinct()
            ->get()
            ->groupBy('class_id')
            ->map(function ($items) {
                return $items->pluck('section_id')->map(fn($id) => (int)$id)->values();
            });

        return view('pages.sms.general_notice', compact(
            'sessions',
            'classes',
            'branches',
            'sections',
            'classSections',
            'smsRate',
            'testMode',
            'currentBalance'
        ));
    }

    /**
     * Build the query for recipient students based on current filter criteria.
     * Enforces strict AND logic across all provided filters.
     */
    public function buildNoticeRecipientQuery(Request $request)
    {
        $query = Student::with(['schoolClass', 'section'])
            ->where('status', 'Active')
            ->where('sms_status', 'Active');

        // 1. Academic Session filter (Required)
        if ($request->filled('session_year_id')) {
            $query->where('session_year_id', $request->session_year_id);
        }

        // 2. Class filter: can be 'all' or specific class id
        if ($request->filled('class_id') && $request->class_id !== 'all') {
            if (is_array($request->class_id)) {
                $query->whereIn('class_id', $request->class_id);
            } else {
                $query->where('class_id', $request->class_id);
            }
        }

        // 3. Section filter: can be 'all' or specific section id
        if ($request->filled('section_id') && $request->section_id !== 'all') {
            if (is_array($request->section_id)) {
                $query->whereIn('section_id', $request->section_id);
            } else {
                $query->where('section_id', $request->section_id);
            }
        }

        // 4. Branch filter
        if ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        // 5. Gender filter: 'all', 'Male', or 'Female' (supports 'boys', 'girls')
        if ($request->filled('gender') && $request->gender !== 'all') {
            $genderVal = match (strtolower($request->gender)) {
                'male', 'boys', 'boy' => 'Male',
                'female', 'girls', 'girl' => 'Female',
                default => $request->gender,
            };
            $query->where('gender', $genderVal);
        }

        // 6. Selective Students mode: explicit selection of student IDs
        if ($request->input('recipient_selection') === 'selective') {
            $selectedIds = $request->input('selected_student_ids');
            if (is_string($selectedIds)) {
                $selectedIds = array_filter(array_map('trim', explode(',', $selectedIds)));
            }
            if (is_array($selectedIds) && !empty($selectedIds)) {
                $selectedIds = array_map('intval', $selectedIds);
                $query->whereIn('id', $selectedIds);
            } else {
                // If selective mode is enabled without any selected students, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    /**
     * Return live recipient summary counts, SMS segments, and estimated cost.
     */
    public function recipientSummary(Request $request, \App\Services\SmsService $smsService)
    {
        $recipientSource = $request->input('recipient_source', 'students');
        if (!in_array($recipientSource, ['students', 'custom', 'both'], true)) {
            $recipientSource = 'students';
        }

        $students = collect();
        $studentValidPhones = [];
        $studentMissingOrInvalid = 0;

        // Process student filters if source is students or both
        if ($recipientSource !== 'custom') {
            if ($request->filled('session_year_id')) {
                $students = $this->buildNoticeRecipientQuery($request)
                    ->select(['id', 'guardian_mobile'])
                    ->get();

                foreach ($students as $student) {
                    $normalized = self::normalizeBangladeshiPhone($student->guardian_mobile);
                    if ($normalized) {
                        $studentValidPhones[] = $normalized;
                    } else {
                        $studentMissingOrInvalid++;
                    }
                }
            } elseif ($recipientSource === 'students') {
                return response()->json([
                    'success'               => true,
                    'recipient_source'      => $recipientSource,
                    'total_students'        => 0,
                    'custom_tokens_count'   => 0,
                    'custom_valid_count'    => 0,
                    'custom_invalid_count'  => 0,
                    'custom_duplicate_count'=> 0,
                    'valid_numbers_count'   => 0,
                    'unique_numbers_count'  => 0,
                    'missing_invalid_count' => 0,
                    'sms_segments'          => 0,
                    'encoding'              => 'GSM',
                    'characters'            => 0,
                    'rate_per_sms'          => (float) config('services.mim_sms.rate_per_sms', 0.33),
                    'estimated_cost'        => 0,
                    'duplicate_phone_count' => 0,
                ]);
            }
        }

        // Process custom numbers if source is custom or both
        $customParsed = [
            'tokens_count'    => 0,
            'valid_count'     => 0,
            'unique_count'    => 0,
            'duplicate_count' => 0,
            'invalid_count'   => 0,
            'valid_numbers'   => [],
            'invalid_numbers' => [],
        ];

        if ($recipientSource !== 'students') {
            $customParsed = self::parseCustomPhoneNumbers($request->input('custom_numbers'));
        }

        // Determine combined or individual numbers
        if ($recipientSource === 'students') {
            $totalStudents = $students->count();
            $validCount = count($studentValidPhones);
            $uniquePhones = array_values(array_unique($studentValidPhones));
            $uniqueCount = count($uniquePhones);
            $missingOrInvalid = $studentMissingOrInvalid;
            $duplicateCount = max(0, $validCount - $uniqueCount);
        } elseif ($recipientSource === 'custom') {
            $totalStudents = 0;
            $validCount = $customParsed['valid_count'];
            $uniquePhones = $customParsed['valid_numbers'];
            $uniqueCount = count($uniquePhones);
            $missingOrInvalid = $customParsed['invalid_count'];
            $duplicateCount = $customParsed['duplicate_count'];
        } else { // 'both'
            $totalStudents = $students->count();
            $allValidInstances = array_merge($studentValidPhones, $customParsed['valid_numbers']);
            $validCount = count($allValidInstances);
            $uniquePhones = array_values(array_unique($allValidInstances));
            $uniqueCount = count($uniquePhones);
            $missingOrInvalid = $studentMissingOrInvalid + $customParsed['invalid_count'];
            // Total duplicates = (student sibling duplicates) + (custom duplicates) + (overlap duplicates)
            $duplicateCount = max(0, (count($studentValidPhones) + $customParsed['valid_count']) - $uniqueCount);
        }

        $message = (string) $request->input('message', '');
        $segmentInfo = self::calculateSmsSegments($message);
        $segments = $segmentInfo['segments'];
        $rate = (float) config('services.mim_sms.rate_per_sms', 0.33);
        $estimatedCost = round($uniqueCount * $segments * $rate, 2);

        return response()->json([
            'success'                => true,
            'recipient_source'       => $recipientSource,
            'total_students'         => $totalStudents,
            'custom_tokens_count'    => $customParsed['tokens_count'],
            'custom_valid_count'     => $customParsed['unique_count'],
            'custom_invalid_count'   => $customParsed['invalid_count'],
            'custom_duplicate_count' => $customParsed['duplicate_count'],
            'valid_numbers_count'    => $validCount,
            'unique_numbers_count'   => $uniqueCount,
            'missing_invalid_count'  => $missingOrInvalid,
            'sms_segments'           => $segments,
            'encoding'               => $segmentInfo['encoding'],
            'characters'             => $segmentInfo['characters'],
            'rate_per_sms'           => $rate,
            'estimated_cost'         => $estimatedCost,
            'duplicate_phone_count'  => $duplicateCount,
        ]);
    }

    /**
     * Return paginated and searchable recipient contacts for preview and selection modal.
     */
    public function previewRecipients(Request $request)
    {
        $recipientSource = $request->input('recipient_source', 'students');
        if (!in_array($recipientSource, ['students', 'custom', 'both'], true)) {
            $recipientSource = 'students';
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 15;
        }
        $currentPage = (int) $request->input('page', 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }

        // Case A: Custom Mobile Numbers only
        if ($recipientSource === 'custom') {
            $customParsed = self::parseCustomPhoneNumbers($request->input('custom_numbers'));
            $items = collect($customParsed['items']);

            if ($request->filled('search')) {
                $search = strtolower(trim($request->search));
                $items = $items->filter(function ($item) use ($search) {
                    return str_contains(strtolower($item['raw']), $search)
                        || ($item['normalized'] && str_contains($item['normalized'], $search));
                });
            }

            $total = $items->count();
            $lastPage = max(1, (int) ceil($total / $perPage));
            $pagedItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values()->map(function ($item, $idx) use ($currentPage, $perPage) {
                $seq = ($currentPage - 1) * $perPage + $idx + 1;
                return [
                    'id'               => 'custom_' . $seq,
                    'student_name'     => 'Custom Contact #' . $seq,
                    'student_identity' => 'MANUAL',
                    'roll_number'      => '—',
                    'class_name'       => 'Custom Number',
                    'section_name'     => $item['is_duplicate'] ? 'Duplicate Skipped' : 'Direct SMS',
                    'gender'           => 'N/A',
                    'raw_phone'        => $item['raw'],
                    'masked_phone'     => self::maskPhoneNumber($item['raw']),
                    'phone_status'     => $item['status'],
                    'is_custom'        => true,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $pagedItems,
                'pagination' => [
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                ]
            ]);
        }

        // Case B: Filtered Students only
        if ($recipientSource === 'students') {
            $request->validate([
                'session_year_id' => 'required',
            ]);

            $query = $this->buildNoticeRecipientQuery($request);

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('student_name', 'like', "%{$search}%")
                      ->orWhere('student_identity', 'like', "%{$search}%")
                      ->orWhere('roll_number', 'like', "%{$search}%")
                      ->orWhere('guardian_mobile', 'like', "%{$search}%");
                });
            }

            $paginated = $query->orderBy('class_id')->orderBy('roll_number')->paginate($perPage);

            $items = collect($paginated->items())->map(function ($student) {
                $rawPhone = $student->guardian_mobile;
                $normalized = self::normalizeBangladeshiPhone($rawPhone);

                $phoneStatus = 'valid';
                if (empty($rawPhone)) {
                    $phoneStatus = 'missing';
                } elseif (!$normalized) {
                    $phoneStatus = 'invalid';
                }

                return [
                    'id'               => $student->id,
                    'student_name'     => $student->student_name ?? 'N/A',
                    'student_identity' => $student->student_identity ?? 'N/A',
                    'roll_number'      => $student->roll_number ?? 'N/A',
                    'class_name'       => $student->schoolClass->class_name ?? 'N/A',
                    'section_name'     => $student->section->section_name ?? 'N/A',
                    'gender'           => $student->gender ?? 'N/A',
                    'raw_phone'        => $rawPhone ?? '',
                    'masked_phone'     => self::maskPhoneNumber($rawPhone),
                    'phone_status'     => $phoneStatus,
                    'is_custom'        => false,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ]
            ]);
        }

        // Case C: Both (Students & Custom Numbers)
        $request->validate([
            'session_year_id' => 'required',
        ]);

        $studentQuery = $this->buildNoticeRecipientQuery($request);
        $students = $studentQuery->orderBy('class_id')->orderBy('roll_number')->get();

        $studentItems = $students->map(function ($student) {
            $rawPhone = $student->guardian_mobile;
            $normalized = self::normalizeBangladeshiPhone($rawPhone);

            $phoneStatus = 'valid';
            if (empty($rawPhone)) {
                $phoneStatus = 'missing';
            } elseif (!$normalized) {
                $phoneStatus = 'invalid';
            }

            return [
                'id'               => $student->id,
                'student_name'     => $student->student_name ?? 'N/A',
                'student_identity' => $student->student_identity ?? 'N/A',
                'roll_number'      => $student->roll_number ?? 'N/A',
                'class_name'       => $student->schoolClass->class_name ?? 'N/A',
                'section_name'     => $student->section->section_name ?? 'N/A',
                'gender'           => $student->gender ?? 'N/A',
                'raw_phone'        => $rawPhone ?? '',
                'masked_phone'     => self::maskPhoneNumber($rawPhone),
                'phone_status'     => $phoneStatus,
                'is_custom'        => false,
            ];
        });

        $customParsed = self::parseCustomPhoneNumbers($request->input('custom_numbers'));
        $customItems = collect($customParsed['items'])->map(function ($item, $idx) {
            $seq = $idx + 1;
            return [
                'id'               => 'custom_' . $seq,
                'student_name'     => 'Custom Contact #' . $seq,
                'student_identity' => 'MANUAL',
                'roll_number'      => '—',
                'class_name'       => 'Custom Number',
                'section_name'     => $item['is_duplicate'] ? 'Duplicate Skipped' : 'Direct SMS',
                'gender'           => 'N/A',
                'raw_phone'        => $item['raw'],
                'masked_phone'     => self::maskPhoneNumber($item['raw']),
                'phone_status'     => $item['status'],
                'is_custom'        => true,
            ];
        });

        $combined = $studentItems->concat($customItems);

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $combined = $combined->filter(function ($row) use ($search) {
                return str_contains(strtolower($row['student_name']), $search)
                    || str_contains(strtolower($row['student_identity']), $search)
                    || str_contains(strtolower((string)$row['roll_number']), $search)
                    || str_contains(strtolower($row['raw_phone']), $search);
            });
        }

        $total = $combined->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $paged = $combined->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paged,
            'pagination' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ]
        ]);
    }

    /**
     * Dispatch general notice SMS with sibling deduplication, custom number support, and audit logging.
     */
    public function sendGeneralNotice(Request $request, \App\Services\SmsService $smsService)
    {
        $recipientSource = $request->input('recipient_source', 'students');
        if (!in_array($recipientSource, ['students', 'custom', 'both'], true)) {
            $recipientSource = 'students';
        }

        $rules = [
            'recipient_source' => 'nullable|in:students,custom,both',
            'message'          => 'required|string|min:1',
        ];

        if ($recipientSource === 'students' || $recipientSource === 'both') {
            $rules['session_year_id'] = 'required|exists:session_years,id';
        }

        $request->validate($rules);

        $message = trim($request->message);
        if ($message === '') {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Notice message cannot be empty.'], 422);
            }
            return back()->withErrors(['error' => 'Notice message cannot be empty.'])->withInput();
        }

        // Concurrency lock against rapid repeated submissions
        $lockKey = 'general_notice_broadcast_' . (auth()->id() ?? 'sys');
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            $msg = 'Another broadcast is currently processing. Please wait a moment.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 429);
            }
            return back()->withErrors(['error' => $msg])->withInput();
        }

        try {
            $students = collect();
            $phoneGroups = []; // normalizedPhone => [ student1, student2, ... ]
            $skippedInvalidStudents = [];

            if ($recipientSource !== 'custom') {
                $students = $this->buildNoticeRecipientQuery($request)->get();

                if ($students->isEmpty() && $recipientSource === 'students') {
                    $msg = 'No active students found matching the selected filter criteria.';
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['error' => $msg])->withInput();
                }

                foreach ($students as $student) {
                    $rawPhone = $student->guardian_mobile;
                    $normalized = self::normalizeBangladeshiPhone($rawPhone);

                    if (!$normalized) {
                        $skippedInvalidStudents[] = [
                            'student_id' => $student->id,
                            'name' => $student->student_name,
                            'raw_phone' => $rawPhone,
                        ];
                        continue;
                    }

                    $phoneGroups[$normalized][] = $student;
                }
            }

            $customParsed = [
                'tokens_count'    => 0,
                'valid_count'     => 0,
                'unique_count'    => 0,
                'duplicate_count' => 0,
                'invalid_count'   => 0,
                'valid_numbers'   => [],
                'invalid_numbers' => [],
            ];
            $standaloneCustomPhones = [];
            $overlappingCustomCount = 0;

            if ($recipientSource !== 'students') {
                $customParsed = self::parseCustomPhoneNumbers($request->input('custom_numbers'));

                if ($recipientSource === 'custom' && $customParsed['unique_count'] === 0) {
                    $msg = 'No valid recipient mobile numbers found in the custom numbers entered.';
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return back()->withErrors(['error' => $msg])->withInput();
                }

                foreach ($customParsed['valid_numbers'] as $cPhone) {
                    if (isset($phoneGroups[$cPhone])) {
                        // Already covered by student recipient
                        $overlappingCustomCount++;
                    } else {
                        $standaloneCustomPhones[] = $cPhone;
                    }
                }
            }

            $uniqueNumbersCount = count($phoneGroups) + count($standaloneCustomPhones);
            if ($uniqueNumbersCount === 0) {
                $msg = 'No valid mobile numbers found among the selected recipients.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withErrors(['error' => $msg])->withInput();
            }

            // Calculate segments and total estimated cost
            $segmentInfo = self::calculateSmsSegments($message);
            $segmentsPerMsg = max(1, $segmentInfo['segments']);
            $ratePerSms = (float) config('services.mim_sms.rate_per_sms', 0.33);
            $totalEstimatedCost = round($uniqueNumbersCount * $segmentsPerMsg * $ratePerSms, 2);

            // Verify live balance if available
            $balanceInfo = $smsService->getBalance();
            $balance = is_array($balanceInfo) ? ($balanceInfo['balance'] ?? null) : $balanceInfo;
            if (is_numeric($balance) && (float) $balance < $totalEstimatedCost) {
                $msg = "Insufficient SMS balance. Required: ৳{$totalEstimatedCost}, Available: ৳{$balance}. Please recharge.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withErrors(['error' => $msg])->withInput();
            }

            $broadcastBatchId = 'nb_' . Str::random(8) . '_' . time();
            $sentCount = 0;
            $failedCount = 0;
            $siblingSharedCount = 0;
            $customSentCount = 0;

            // 1. Dispatch to student guardian phone groups
            foreach ($phoneGroups as $normalizedPhone => $groupStudents) {
                $primaryStudent = $groupStudents[0];
                $eventKey = $broadcastBatchId . '_' . $normalizedPhone;

                $sent = $smsService->sendSms(
                    $normalizedPhone,
                    $message,
                    $primaryStudent->id,
                    $eventKey,
                    'general_notice'
                );

                $logStatus = SmsLog::where('event_key', $eventKey)->value('status');
                $isProcessed = $sent || ($logStatus === 'Blocked (Test Mode)');

                if ($isProcessed) {
                    $sentCount++;

                    // Log sibling student deliveries so academic records reflect the notice
                    if (count($groupStudents) > 1) {
                        for ($i = 1; $i < count($groupStudents); $i++) {
                            $siblingStudent = $groupStudents[$i];
                            $siblingSharedCount++;
                            try {
                                SmsLog::create([
                                    'student_id'    => $siblingStudent->id,
                                    'event_key'     => $eventKey . '_std_' . $siblingStudent->id,
                                    'event_type'    => 'general_notice',
                                    'mobile_number' => $siblingStudent->guardian_mobile,
                                    'message'       => $message,
                                    'status'        => 'Sent (Sibling Shared)',
                                    'created_at'    => Carbon::now('Asia/Dhaka'),
                                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                                ]);
                            } catch (\Exception $e) {
                                Log::warning("Could not create sibling log for student {$siblingStudent->id}: " . $e->getMessage());
                            }
                        }
                    }
                } else {
                    $failedCount++;
                }
            }

            // 2. Dispatch to standalone custom recipient numbers
            foreach ($standaloneCustomPhones as $cPhone) {
                $eventKey = $broadcastBatchId . '_cust_' . $cPhone;

                $sent = $smsService->sendSms(
                    $cPhone,
                    $message,
                    null,
                    $eventKey,
                    'general_notice'
                );

                $logStatus = SmsLog::where('event_key', $eventKey)->value('status');
                $isProcessed = $sent || ($logStatus === 'Blocked (Test Mode)');

                if ($isProcessed) {
                    $sentCount++;
                    $customSentCount++;
                } else {
                    $failedCount++;
                }
            }

            // 3. Log skipped invalid student records for audit trail
            foreach ($skippedInvalidStudents as $skipped) {
                try {
                    SmsLog::create([
                        'student_id'    => $skipped['student_id'],
                        'event_key'     => $broadcastBatchId . '_inv_' . $skipped['student_id'],
                        'event_type'    => 'general_notice',
                        'mobile_number' => $skipped['raw_phone'] ?: 'N/A',
                        'message'       => $message,
                        'status'        => 'Failed (Invalid Number)',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                } catch (\Exception $e) {
                    // Ignore duplicate key collision on log
                }
            }

            // 4. Log skipped invalid custom numbers for audit trail
            if (!empty($customParsed['invalid_numbers'])) {
                foreach ($customParsed['invalid_numbers'] as $invIdx => $invNumber) {
                    try {
                        SmsLog::create([
                            'student_id'    => null,
                            'event_key'     => $broadcastBatchId . '_cinv_' . $invIdx,
                            'event_type'    => 'general_notice',
                            'mobile_number' => $invNumber ?: 'N/A',
                            'message'       => $message,
                            'status'        => 'Failed (Invalid Number)',
                            'created_at'    => Carbon::now('Asia/Dhaka'),
                            'updated_at'    => Carbon::now('Asia/Dhaka'),
                        ]);
                    } catch (\Exception $e) {
                        // Ignore
                    }
                }
            }

            $successMsg = "Success! Notice SMS broadcast dispatched to {$sentCount} unique recipient numbers.";
            if ($siblingSharedCount > 0) {
                $successMsg .= " ({$siblingSharedCount} sibling duplicate messages avoided).";
            }
            if ($overlappingCustomCount > 0) {
                $successMsg .= " ({$overlappingCustomCount} custom numbers overlapping student guardians merged).";
            }
            $totalSkippedInvalid = count($skippedInvalidStudents) + $customParsed['invalid_count'];
            if ($totalSkippedInvalid > 0) {
                $successMsg .= " {$totalSkippedInvalid} contact(s) skipped due to missing/invalid numbers.";
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'stats' => [
                        'sent_count'                 => $sentCount,
                        'failed_count'               => $failedCount,
                        'total_students'             => count($students),
                        'sibling_shared_count'       => $siblingSharedCount,
                        'custom_numbers_count'       => $customParsed['tokens_count'],
                        'custom_sent_count'          => $customSentCount,
                        'custom_overlap_dedup_count' => $overlappingCustomCount,
                        'skipped_invalid_count'      => $totalSkippedInvalid,
                        'segments'                   => $segmentsPerMsg,
                        'estimated_cost'             => $totalEstimatedCost,
                    ]
                ]);
            }

            return back()->with('success', $successMsg);

        } finally {
            $lock->release();
        }
    }

    /**
     * Parse raw custom phone numbers from comma, semicolon, tab, or newline separated text.
     * Normalizes and validates Bangladeshi mobile numbers, deduplicates entries,
     * and separates invalid tokens.
     */
    public static function parseCustomPhoneNumbers(?string $input): array
    {
        if (!$input) {
            return [
                'raw_tokens'      => [],
                'tokens_count'    => 0,
                'valid_count'     => 0,
                'unique_count'    => 0,
                'duplicate_count' => 0,
                'invalid_count'   => 0,
                'valid_numbers'   => [],
                'invalid_numbers' => [],
                'items'           => [],
            ];
        }

        $rawTokens = preg_split('/[,\r\n;\t]+/', $input, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = [];
        foreach ($rawTokens as $tok) {
            $t = trim($tok);
            if ($t !== '') {
                $tokens[] = $t;
            }
        }

        $items = [];
        $validUnique = [];
        $invalidNumbers = [];
        $duplicateCount = 0;

        foreach ($tokens as $index => $token) {
            $normalized = self::normalizeBangladeshiPhone($token);
            if ($normalized) {
                if (in_array($normalized, $validUnique, true)) {
                    $duplicateCount++;
                    $items[] = [
                        'index'        => $index,
                        'raw'          => $token,
                        'normalized'   => $normalized,
                        'status'       => 'valid',
                        'is_duplicate' => true,
                    ];
                } else {
                    $validUnique[] = $normalized;
                    $items[] = [
                        'index'        => $index,
                        'raw'          => $token,
                        'normalized'   => $normalized,
                        'status'       => 'valid',
                        'is_duplicate' => false,
                    ];
                }
            } else {
                $invalidNumbers[] = $token;
                $items[] = [
                    'index'        => $index,
                    'raw'          => $token,
                    'normalized'   => null,
                    'status'       => 'invalid',
                    'is_duplicate' => false,
                ];
            }
        }

        return [
            'raw_tokens'      => $tokens,
            'tokens_count'    => count($tokens),
            'valid_count'     => count($validUnique) + $duplicateCount,
            'unique_count'    => count($validUnique),
            'duplicate_count' => $duplicateCount,
            'invalid_count'   => count($invalidNumbers),
            'valid_numbers'   => $validUnique,
            'invalid_numbers' => $invalidNumbers,
            'items'           => $items,
        ];
    }

    /**
     * Normalize phone number to 13-digit Bangladeshi format (8801XXXXXXXXX).
     */
    public static function normalizeBangladeshiPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($clean) === 13 && str_starts_with($clean, '8801')) {
            $prefix = substr($clean, 4, 1);
            if (in_array($prefix, ['3', '4', '5', '6', '7', '8', '9'], true)) {
                return $clean;
            }
            return null;
        }

        if (strlen($clean) === 11 && str_starts_with($clean, '01')) {
            $prefix = substr($clean, 2, 1);
            if (in_array($prefix, ['3', '4', '5', '6', '7', '8', '9'], true)) {
                return '88' . $clean;
            }
            return null;
        }

        if (strlen($clean) === 10 && str_starts_with($clean, '1')) {
            $prefix = substr($clean, 1, 1);
            if (in_array($prefix, ['3', '4', '5', '6', '7', '8', '9'], true)) {
                return '880' . $clean;
            }
            return null;
        }

        return null;
    }

    /**
     * Mask phone number for preview display (e.g., 017****8107).
     */
    public static function maskPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return 'N/A';
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) === 13 && str_starts_with($clean, '8801')) {
            $clean = substr($clean, 2);
        }

        if (strlen($clean) === 11) {
            return substr($clean, 0, 3) . '****' . substr($clean, 7);
        }

        if (strlen($clean) >= 6) {
            return substr($clean, 0, 2) . '***' . substr($clean, -2);
        }

        return $phone;
    }

    /**
     * Calculate SMS segments, encoding (GSM vs Unicode), and character length.
     */
    public static function calculateSmsSegments(string $message): array
    {
        $length = mb_strlen($message, 'UTF-8');
        if ($length === 0) {
            return [
                'characters' => 0,
                'segments' => 0,
                'encoding' => 'GSM',
            ];
        }

        $isUnicode = (bool) preg_match('/[^\x20-\x7E\r\n\t]/u', $message);

        if ($isUnicode) {
            $encoding = 'Unicode';
            $segments = $length <= 70 ? 1 : (int) ceil($length / 67);
        } else {
            $encoding = 'GSM';
            $segments = $length <= 160 ? 1 : (int) ceil($length / 153);
        }

        return [
            'characters' => $length,
            'segments' => $segments,
            'encoding' => $encoding,
        ];
    }


    // এসএমএস রিপোর্ট ও কাউন্টার দেখানোর মেথড
    public function report(Request $request, \App\Services\SmsService $smsService)
    {
        $todayDhaka = Carbon::today('Asia/Dhaka');
        $nowDhaka = Carbon::now('Asia/Dhaka');

        // ১. কাউন্টারের জন্য রিয়েল ডাটা ক্যালকুলেশন (সফলভাবে সেন্ড হওয়া এসএমএস)
        $todaySent = SmsLog::where('status', 'Sent')
                           ->whereDate('created_at', $todayDhaka)
                           ->count();
        
        $monthSent = SmsLog::where('status', 'Sent')
                           ->whereYear('created_at', $nowDhaka->year)
                           ->whereMonth('created_at', $nowDhaka->month)
                           ->count();
                           
        $totalSent = SmsLog::where('status', 'Sent')->count();
        $totalFailed = SmsLog::whereIn('status', ['Failed', 'Blocked (Test Mode)', 'Ambiguous'])->count();

        // ২. MiM SMS লাইভ ব্যালেন্স চেক (gateway API)
        $forceRefresh = $request->boolean('refresh_balance');
        $gatewayBalance = $smsService->getBalance($forceRefresh);

        // ৩. টেবিলের জন্য ফিল্টারিং কোয়েরি
        $query = $this->buildSmsReportQuery($request);

        // ৪. টেবিলের জন্য লেটেস্ট এসএমএসগুলো আনা (পেজিনেশন সহ এবং ফিল্টার কোয়েরি স্ট্রিং প্রিজার্ভ করে)
        $logs = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        return view('pages.sms.report', compact(
            'logs',
            'todaySent',
            'monthSent',
            'totalSent',
            'totalFailed',
            'gatewayBalance'
        ));
    }

    /**
     * Helper to build filtered SMS report query
     */
    protected function buildSmsReportQuery(Request $request)
    {
        $todayDhaka = Carbon::today('Asia/Dhaka');
        $nowDhaka = Carbon::now('Asia/Dhaka');

        $query = SmsLog::with(['student.schoolClass', 'student.section']);

        // সার্চ ফিল্টার (নাম, স্টুডেন্ট আইডি, মোবাইল নম্বর, মেসেজ টেক্সট)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('mobile_number', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('student_name', 'LIKE', "%{$search}%")
                         ->orWhere('student_identity', 'LIKE', "%{$search}%");
                  });
            });
        }

        // স্ট্যাটাস ফিল্টার
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        // ইভেন্ট টাইপ ফিল্টার (check_in, check_out, general_notice, result)
        if ($request->filled('event_type') && $request->event_type !== 'All') {
            $query->where('event_type', $request->event_type);
        }

        // ডেট ফিল্টার
        if ($request->filled('date_filter')) {
            if ($request->date_filter === 'today') {
                $query->whereDate('created_at', $todayDhaka);
            } elseif ($request->date_filter === 'yesterday') {
                $query->whereDate('created_at', Carbon::yesterday('Asia/Dhaka'));
            } elseif ($request->date_filter === 'last_7_days') {
                $query->where('created_at', '>=', Carbon::now('Asia/Dhaka')->subDays(7));
            } elseif ($request->date_filter === 'this_month') {
                $query->whereYear('created_at', $nowDhaka->year)
                      ->whereMonth('created_at', $nowDhaka->month);
            } elseif ($request->date_filter === 'custom') {
                if ($request->filled('start_date')) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }
            }
        }

        return $query;
    }

    /**
     * AJAX endpoint: Fetch all matching SMS records and criteria metrics for Print Report
     */
    public function reportPrintData(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $query = $this->buildSmsReportQuery($request);

            $totalCount = (clone $query)->count();
            $sentCount = (clone $query)->where('status', 'Sent')->count();
            $failedCount = (clone $query)->whereIn('status', ['Failed', 'Blocked (Test Mode)', 'Ambiguous'])->count();
            $otherCount = max(0, $totalCount - $sentCount - $failedCount);

            // Fetch all matching records in descending order
            $records = $query->orderBy('created_at', 'desc')->get()->map(function ($log) {
                $createdAt = Carbon::parse($log->created_at, 'Asia/Dhaka');

                $eventTypeLabel = match($log->event_type) {
                    'check_in' => 'Check-In',
                    'check_out' => 'Check-Out',
                    'result' => 'Result',
                    'general_notice' => 'Notice',
                    default => !empty($log->event_type) ? ucfirst(str_replace('_', ' ', $log->event_type)) : 'Notice',
                };

                return [
                    'id' => $log->id,
                    'date' => $createdAt->format('d M, Y'),
                    'time' => $createdAt->format('h:i A'),
                    'recipient_name' => $log->student ? $log->student->student_name : 'General Recipient',
                    'student_identity' => $log->student ? ($log->student->student_identity ?? 'N/A') : 'N/A',
                    'class_name' => $log->student && $log->student->schoolClass ? $log->student->schoolClass->class_name : null,
                    'section_name' => $log->student && $log->student->section ? $log->student->section->section_name : null,
                    'mobile_number' => $log->mobile_number,
                    'event_type' => $eventTypeLabel,
                    'message' => $log->message,
                    'status' => $log->status,
                ];
            });

            $todayDhaka = Carbon::today('Asia/Dhaka');
            $nowDhaka = Carbon::now('Asia/Dhaka');

            $dateFilterLabel = match($request->date_filter) {
                'today' => 'Today (' . $todayDhaka->format('d M, Y') . ')',
                'yesterday' => 'Yesterday (' . Carbon::yesterday('Asia/Dhaka')->format('d M, Y') . ')',
                'last_7_days' => 'Last 7 Days',
                'this_month' => 'This Month (' . $nowDhaka->format('F Y') . ')',
                'custom' => ($request->filled('start_date') ? Carbon::parse($request->start_date)->format('d M, Y') : 'Start') . ' - ' . ($request->filled('end_date') ? Carbon::parse($request->end_date)->format('d M, Y') : 'End'),
                default => 'All Dates',
            };

            $statusLabel = match($request->status) {
                'Sent' => 'Sent',
                'Failed' => 'Failed',
                'Blocked (Test Mode)' => 'Blocked (Test Mode)',
                'Ambiguous' => 'Ambiguous',
                default => 'All',
            };

            $typeLabel = match($request->event_type) {
                'check_in' => 'Attendance Check-In',
                'check_out' => 'Attendance Check-Out',
                'general_notice' => 'General Notice',
                'result' => 'Exam Result',
                default => 'All',
            };

            return response()->json([
                'status' => 'success',
                'criteria' => [
                    'date_range' => $dateFilterLabel,
                    'status' => $statusLabel,
                    'type' => $typeLabel,
                    'event_type' => $typeLabel,
                    'search' => $request->filled('search') ? trim($request->search) : null,
                ],
                'stats' => [
                    'total' => $totalCount,
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'other' => $otherCount,
                ],
                'records' => $records,
                'generated_at' => [
                    'date' => $nowDhaka->format('d M, Y'),
                    'time' => $nowDhaka->format('h:i A'),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // রেজাল্ট এসএমএস ফর্ম পেজ
    public function resultSms()
    {
        $sessions = SessionYear::orderBy('session_name', 'desc')->get();
        $exams = Exam::orderBy('name', 'asc')->get();
        $classes = Classes::all();
        $sections = Section::all();
        
        return view('pages.sms.result_sms', compact('sessions', 'exams', 'classes', 'sections'));
    }

    // স্মার্ট রেজাল্ট এসএমএস সেন্ড লজিক
    // public function sendResultSms(Request $request)
    // {
    //     $request->validate([
    //         'session_year_id' => 'required',
    //         'exam_id' => 'required',
    //         'class_id' => 'required',
    //         'message_template' => 'required|string',
    //     ]);

    //     $exam = Exam::find($request->exam_id);
        
    //     // সিলেক্ট করা ক্লাস ও সেশনের স্টুডেন্টদের আনা
    //     $query = Student::where('sms_status', 'Active')
    //                     ->where('session_year_id', $request->session_year_id)
    //                     ->where('class_id', $request->class_id);

    //     if ($request->section_id) {
    //         $query->where('section_id', $request->section_id);
    //     }

    //     $students = $query->get();

    //     if ($students->isEmpty()) {
    //         return back()->withErrors(['error' => 'No active students found!']);
    //     }

    //     $sentCount = 0;
    //     $logData = [];
    //     $now = Carbon::now();
    //     $token = "107092350091707846609c0d7854830bcea7f322cd4a2f1b39a18";
    //     $url = "https://api.bdbulksms.net/api.php?json";

    //     // প্রত্যেক স্টুডেন্টের জন্য আলাদা লুপ (কারণ সবার রেজাল্ট আলাদা)
    //     foreach ($students as $student) {
            
    //         // ওই স্টুডেন্ট এবং এক্সামের মার্কস আনা
    //         $marks = Mark::where('student_id', $student->id)
    //                      ->where('exam_id', $exam->id)
    //                      ->where('session_year_id', $request->session_year_id)
    //                      ->get();

    //         // যদি স্টুডেন্টের মার্কস এন্ট্রি করা থাকে, তবেই এসএমএস যাবে
    //         if ($marks->count() > 0 && !empty($student->guardian_mobile)) {
                
    //             // ক্যালকুলেশন
    //             $total_marks = $marks->sum('total_mark');
    //             $is_failed = $marks->contains(function ($mark) {
    //                 return $mark->letter_grade == 'F' || $mark->letter_grade == 'Fail';
    //             });
                
    //             $cgpa = number_format($marks->sum('grade_point') / $marks->count(), 2);
    //             $final_gpa = $is_failed ? '0.00 (Fail)' : $cgpa;
    //             $student_name = $student->student_name ?? $student->first_name;

    //             // স্মার্ট শর্টকোড রিপ্লেসমেন্ট
    //             $personalized_message = str_replace(
    //                 ['[name]', '[exam]', '[gpa]', '[marks]'],
    //                 [$student_name, $exam->name, $final_gpa, $total_marks],
    //                 $request->message_template
    //             );

    //             // API কল (Single Send)
    //             $data = [
    //                 'to' => $student->guardian_mobile,
    //                 'message' => $personalized_message,
    //                 'token' => $token
    //             ]; 

    //             $ch = curl_init(); 
    //             curl_setopt($ch, CURLOPT_URL, $url);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //             curl_setopt($ch, CURLOPT_ENCODING, '');
    //             curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    //             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //             $smsresult = curl_exec($ch);
    //             curl_close($ch);

    //             // লগ সেভ করার জন্য এরে তৈরি
    //             $logData[] = [
    //                 'student_id' => $student->id,
    //                 'mobile_number' => $student->guardian_mobile,
    //                 'message' => $personalized_message,
    //                 'status' => 'Sent',
    //                 'created_at' => $now,
    //                 'updated_at' => $now,
    //             ];

    //             $sentCount++;
    //         }
    //     }

    //     // একসাথে ডাটাবেসে সেভ
    //     if(count($logData) > 0) {
    //         SmsLog::insert($logData);
    //     }

    //     if ($sentCount == 0) {
    //         return back()->withErrors(['error' => 'No marks found for the selected students in this exam. No SMS sent.']);
    //     }

    //     return back()->with('success', "Success! Personalized Result SMS sent and logged for {$sentCount} students.");
    // }

    /**
     * Store a scheduled SMS broadcast.
     */
    public function storeSchedule(Request $request, \App\Services\SmsService $smsService)
    {
        $recipientSource = $request->input('recipient_source', 'students');
        if (!in_array($recipientSource, ['students', 'custom', 'both'], true)) {
            $recipientSource = 'students';
        }

        $rules = [
            'recipient_source' => 'nullable|in:students,custom,both',
            'message'          => 'required|string|min:1',
            'scheduled_date'   => 'required|date_format:Y-m-d',
            'scheduled_time'   => 'required|date_format:H:i',
        ];

        if ($recipientSource === 'students' || $recipientSource === 'both') {
            $rules['session_year_id'] = 'required|exists:session_years,id';
        }

        $request->validate($rules);

        $message = trim($request->message);
        if ($message === '') {
            return response()->json(['success' => false, 'message' => 'Notice message cannot be empty.'], 422);
        }

        // Parse and validate schedule time in Asia/Dhaka
        try {
            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date . ' ' . $request->scheduled_time, 'Asia/Dhaka');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid scheduled date or time format.'], 422);
        }

        $now = Carbon::now('Asia/Dhaka');
        if ($scheduledAt->lessThanOrEqualTo($now->copy()->addMinute())) {
            return response()->json([
                'success' => false,
                'message' => 'Scheduled time must be at least 2 minutes in the future (Asia/Dhaka timezone).'
            ], 422);
        }

        // Build recipient groups and custom numbers snapshot
        $students = collect();
        $phoneGroups = [];
        $skippedInvalidStudents = [];

        if ($recipientSource !== 'custom') {
            $students = $this->buildNoticeRecipientQuery($request)->get();

            if ($students->isEmpty() && $recipientSource === 'students') {
                return response()->json(['success' => false, 'message' => 'No active students found matching the selected filter criteria.'], 422);
            }

            foreach ($students as $student) {
                $rawPhone = $student->guardian_mobile;
                $normalized = self::normalizeBangladeshiPhone($rawPhone);

                if (!$normalized) {
                    $skippedInvalidStudents[] = [
                        'student_id' => $student->id,
                        'name'       => $student->student_name,
                        'raw_phone'  => $rawPhone,
                    ];
                    continue;
                }

                $phoneGroups[$normalized][] = $student->id;
            }
        }

        $customParsed = [
            'tokens_count'    => 0,
            'valid_count'     => 0,
            'unique_count'    => 0,
            'duplicate_count' => 0,
            'invalid_count'   => 0,
            'valid_numbers'   => [],
            'invalid_numbers' => [],
        ];
        $standaloneCustomPhones = [];

        if ($recipientSource !== 'students') {
            $customParsed = self::parseCustomPhoneNumbers($request->input('custom_numbers'));

            if ($recipientSource === 'custom' && $customParsed['unique_count'] === 0) {
                return response()->json(['success' => false, 'message' => 'No valid recipient mobile numbers found in the custom numbers entered.'], 422);
            }

            foreach ($customParsed['valid_numbers'] as $cPhone) {
                if (!isset($phoneGroups[$cPhone])) {
                    $standaloneCustomPhones[] = $cPhone;
                }
            }
        }

        $uniqueNumbersCount = count($phoneGroups) + count($standaloneCustomPhones);
        if ($uniqueNumbersCount === 0) {
            return response()->json(['success' => false, 'message' => 'No valid mobile numbers found among the selected recipients.'], 422);
        }

        // Calculate segments and total estimated cost
        $segmentInfo = self::calculateSmsSegments($message);
        $segmentsPerMsg = max(1, $segmentInfo['segments']);
        $ratePerSms = (float) config('services.mim_sms.rate_per_sms', 0.33);
        $totalEstimatedCost = round($uniqueNumbersCount * $segmentsPerMsg * $ratePerSms, 2);

        $snapshot = [
            'students_map'             => $phoneGroups,
            'standalone_custom_phones' => array_values(array_unique($standaloneCustomPhones)),
            'total_unique'             => $uniqueNumbersCount,
            'summary'                  => [
                'students_count'  => count($phoneGroups),
                'custom_count'    => count($standaloneCustomPhones),
                'skipped_invalid' => count($skippedInvalidStudents),
            ]
        ];

        $schedule = SmsSchedule::create([
            'schedule_uuid'      => (string) Str::uuid(),
            'user_id'            => auth()->id(),
            'title'              => $request->input('title') ?: ('Notice Broadcast - ' . $scheduledAt->format('M d, Y h:i A')),
            'message'            => $message,
            'recipient_source'   => $recipientSource,
            'filter_criteria'    => [
                'session_year_id'      => $request->session_year_id,
                'class_id'             => $request->class_id,
                'section_id'           => $request->section_id,
                'gender'               => $request->gender,
                'selected_student_ids' => $request->student_ids,
            ],
            'recipient_snapshot' => $snapshot,
            'scheduled_at'       => $scheduledAt,
            'status'             => 'pending',
            'total_recipients'   => $uniqueNumbersCount,
            'total_segments'     => $segmentsPerMsg,
            'encoding'           => $segmentInfo['encoding'] ?? 'GSM-7',
            'estimated_cost'     => $totalEstimatedCost,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'SMS Broadcast successfully scheduled for ' . $scheduledAt->format('M d, Y h:i A') . ' (Asia/Dhaka).',
            'schedule' => $schedule,
        ]);
    }

    /**
     * List scheduled SMS broadcasts with filters and pagination.
     */
    public function listSchedules(Request $request)
    {
        $query = SmsSchedule::with(['user:id,name,email'])
            ->orderBy('scheduled_at', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('schedule_uuid', 'like', "%{$search}%");
            });
        }

        $schedules = $query->paginate(10);

        $counts = [
            'all'             => SmsSchedule::count(),
            'pending'         => SmsSchedule::where('status', 'pending')->count(),
            'completed'       => SmsSchedule::where('status', 'completed')->count(),
            'partial_failure' => SmsSchedule::where('status', 'partial_failure')->count(),
            'failed'          => SmsSchedule::where('status', 'failed')->count(),
            'cancelled'       => SmsSchedule::where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success'   => true,
            'schedules' => $schedules,
            'counts'    => $counts,
        ]);
    }

    /**
     * Update a pending SMS schedule.
     */
    public function updateSchedule(Request $request, $id)
    {
        $schedule = SmsSchedule::findOrFail($id);

        if (!$schedule->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending schedules can be edited. This schedule is already ' . $schedule->status . '.'
            ], 422);
        }

        $request->validate([
            'message'        => 'required|string|min:1',
            'scheduled_date' => 'required|date_format:Y-m-d',
            'scheduled_time' => 'required|date_format:H:i',
        ]);

        try {
            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date . ' ' . $request->scheduled_time, 'Asia/Dhaka');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid scheduled date or time format.'], 422);
        }

        $now = Carbon::now('Asia/Dhaka');
        if ($scheduledAt->lessThanOrEqualTo($now->copy()->addMinute())) {
            return response()->json([
                'success' => false,
                'message' => 'Scheduled time must be at least 2 minutes in the future (Asia/Dhaka timezone).'
            ], 422);
        }

        $message = trim($request->message);
        $segmentInfo = self::calculateSmsSegments($message);
        $segmentsPerMsg = max(1, $segmentInfo['segments']);
        $ratePerSms = (float) config('services.mim_sms.rate_per_sms', 0.33);
        $totalEstimatedCost = round($schedule->total_recipients * $segmentsPerMsg * $ratePerSms, 2);

        $schedule->update([
            'title'          => $request->input('title') ?: $schedule->title,
            'message'        => $message,
            'scheduled_at'   => $scheduledAt,
            'total_segments' => $segmentsPerMsg,
            'encoding'       => $segmentInfo['encoding'] ?? 'GSM-7',
            'estimated_cost' => $totalEstimatedCost,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Scheduled broadcast updated successfully.',
            'schedule' => $schedule->fresh(),
        ]);
    }

    /**
     * Cancel a pending SMS schedule.
     */
    public function cancelSchedule(Request $request, $id)
    {
        $schedule = SmsSchedule::findOrFail($id);

        if (!$schedule->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a schedule that is already ' . $schedule->status . '.'
            ], 422);
        }

        $schedule->update([
            'status'       => 'cancelled',
            'cancelled_at' => Carbon::now('Asia/Dhaka'),
            'cancelled_by' => auth()->id(),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Scheduled broadcast cancelled successfully.',
            'schedule' => $schedule->fresh(),
        ]);
    }
}