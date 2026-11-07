<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\FeeInvoice;
use App\Models\FeeSetup;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\StudentCustomFee;
use Carbon\Carbon;
use Throwable;
use InvalidArgumentException;
use RuntimeException;

class FeeGenerationService
{
    /**
     * Canonical Global MySQL Advisory Lock Name.
     * Shared across CLI, Daily Scheduler, and Web Controllers.
     */
    public const ADVISORY_LOCK_NAME = 'macs_school_fee_invoice_generation_lock';
    public const ADVISORY_LOCK_TIMEOUT = 15; // 15 seconds wait

    /**
     * All 12 calendar month names in academic order.
     */
    public const ALL_MONTHS = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    /**
     * Execute invoice generation under the global advisory lock mutex.
     *
     * @param array $params [
     *     'session_year_id' => int|string|null,
     *     'year'            => int|string|null,
     *     'class_id'        => int|null,
     *     'branch_id'       => int|null,
     *     'fee_category_id' => int|null,
     *     'fee_month'       => string|null,   // 'all', 'all-year', 'all-past', or specific month name
     *     'due_date'        => string|null,   // optional override (YYYY-MM-DD)
     *     'dry_run'         => bool,          // preview mode
     *     'user_id'         => int|null,      // operator / system user ID
     * ]
     * @param callable|null $progressCallback Optional callback for real-time logging: function(string $type, string $message, array $context = [])
     * @return array
     */
    public function generate(array $params = [], ?callable $progressCallback = null): array
    {
        $log = $progressCallback ?? function (string $type, string $message, array $context = []) {};

        $isDryRun = (bool) ($params['dry_run'] ?? false);
        $userId = $this->resolveActorUserId($params['user_id'] ?? null);
        $actorUser = \App\Models\User::find($userId);
        $actorName = $actorUser ? $actorUser->name : "User #{$userId}";
        $isDirectAuth = \Illuminate\Support\Facades\Auth::check() && (int)\Illuminate\Support\Facades\Auth::id() === (int)$userId;
        $initiatorSuffix = (\Illuminate\Support\Facades\Auth::check() && !$isDirectAuth)
            ? " [Initiated by Session User: " . \Illuminate\Support\Facades\Auth::user()->name . " (ID: " . \Illuminate\Support\Facades\Auth::id() . ")]"
            : "";
        $actorDescription = $isDirectAuth
            ? "Authenticated Administrator: {$actorName} (ID: {$userId})"
            : "Resolved System Administrator: {$actorName} (ID: {$userId}){$initiatorSuffix}";
        $log('info', "[ACTOR] Attributed generation to {$actorDescription}");

        // ---------------------------------------------------------------------
        // 1. GLOBAL CONCURRENCY ACQUISITION (DEDICATED PDO CONNECTION)
        // ---------------------------------------------------------------------
        $pdo = DB::connection()->getPdo();
        $hasLock = false;

        try {
            $log('info', "[LOCK] Acquiring global advisory lock ('" . self::ADVISORY_LOCK_NAME . "')...");
            $lockStmt = $pdo->prepare("SELECT GET_LOCK(?, ?)");
            $lockStmt->execute([self::ADVISORY_LOCK_NAME, self::ADVISORY_LOCK_TIMEOUT]);
            $hasLock = (int) $lockStmt->fetchColumn() === 1;

            if (!$hasLock) {
                $msg = "Could not acquire lock ('" . self::ADVISORY_LOCK_NAME . "') within " . self::ADVISORY_LOCK_TIMEOUT . "s. Another generation process is actively running.";
                $log('error', "ABORT: {$msg}");
                return [
                    'success'     => false,
                    'lock_failed' => true,
                    'error'       => $msg,
                ];
            }
            $log('info', "  -> Global Advisory Lock acquired successfully.");

            // -----------------------------------------------------------------
            // 2. SESSION & CALENDAR YEAR RESOLUTION & VALIDATION
            // -----------------------------------------------------------------
            $session = $this->resolveSession($params['session_year_id'] ?? null, $params['year'] ?? null);
            if (!$session) {
                return [
                    'success'     => false,
                    'lock_failed' => false,
                    'error'       => "Failed to resolve target Academic Session.",
                ];
            }

            $targetYear = $this->resolveYear($session, $params['year'] ?? null);
            if ($targetYear === null) {
                return [
                    'success'     => false,
                    'lock_failed' => false,
                    'error'       => "Calendar year conflict with Session '{$session->session_name}' (ID: {$session->id}).",
                ];
            }

            $log('info', "Academic Session: '{$session->session_name}' (ID: {$session->id}) | Billing Year: {$targetYear}");

            // -----------------------------------------------------------------
            // 2.1 SHARED EXECUTION BOUNDARY SAFETY GUARD: DETECT SETUP CONFLICTS
            // -----------------------------------------------------------------
            $setupConflicts = $this->detectSetupConflicts((int)$session->id, (int)$targetYear);

            if (!empty($setupConflicts)) {
                $requestedClassId = !empty($params['class_id']) ? (int)$params['class_id'] : null;
                $requestedCategoryId = !empty($params['fee_category_id']) ? (int)$params['fee_category_id'] : null;

                $conflictInScope = false;
                $conflictingScopeDescriptions = [];

                foreach ($setupConflicts as $conflict) {
                    $scopeClassId = (int)($conflict['class_id'] ?? 0);
                    $scopeCatId = (int)($conflict['fee_category_id'] ?? 0);

                    // Broad generation (no class specified, e.g. CLI all-year or scheduler): any conflict blocks generation
                    if ($requestedClassId === null) {
                        $conflictInScope = true;
                        $conflictingScopeDescriptions[] = $conflict['description'];
                    } elseif ($scopeClassId === $requestedClassId && ($requestedCategoryId === null || $scopeCatId === $requestedCategoryId)) {
                        // Narrow generation specifically requesting the conflicting class and category
                        $conflictInScope = true;
                        $conflictingScopeDescriptions[] = $conflict['description'];
                    }
                }

                if ($conflictInScope) {
                    $conflictMsg = "Inspection Required: Play Setup Conflict Detected. Overlapping active setups exist (" . implode('; ', array_unique($conflictingScopeDescriptions)) . "). Automated generation is locked until resolved in Fee Setup.";
                    $log('error', "ABORT: {$conflictMsg}");
                    return [
                        'success'     => false,
                        'lock_failed' => false,
                        'has_conflict'=> true,
                        'error'       => $conflictMsg,
                    ];
                }
            }

            // -----------------------------------------------------------------
            // 3. TARGET MONTHS RESOLUTION
            // -----------------------------------------------------------------
            $feeMonthInput = trim((string)($params['fee_month'] ?? ''));
            $isAnnual = in_array(strtolower($feeMonthInput), ['all', 'all-year', 'yearly'], true)
                        || !empty($params['all_year']);
            $isAllPast = strtolower($feeMonthInput) === 'all-past' || !empty($params['all_past']);

            $monthsToProcess = [];
            if ($isAnnual) {
                $monthsToProcess = self::ALL_MONTHS;
                $log('info', "Execution Scope: Full Annual (All 12 Months: January to December)");
            } elseif ($isAllPast) {
                $currentMonthName = now()->format('F');
                foreach (self::ALL_MONTHS as $m) {
                    $monthsToProcess[] = $m;
                    if ($m === $currentMonthName) {
                        break;
                    }
                }
                $log('info', "Execution Scope: All Past Months (January to {$currentMonthName})");
            } elseif ($feeMonthInput !== '') {
                $mNormalized = ucfirst(strtolower($feeMonthInput));
                if (!in_array($mNormalized, self::ALL_MONTHS, true)) {
                    // Check if it's a one-time non-monthly category setup
                    $monthsToProcess = [$feeMonthInput];
                } else {
                    $monthsToProcess = [$mNormalized];
                }
                $log('info', "Execution Scope: Single Month ({$monthsToProcess[0]})");
            } else {
                $currentMonthName = now()->format('F');
                $monthsToProcess = [$currentMonthName];
                $log('info', "Execution Scope: Default Current Month ({$currentMonthName})");
            }

            // -----------------------------------------------------------------
            // 4. GENERATION LOOP (MONTH BY MONTH, SETUP BY SETUP)
            // -----------------------------------------------------------------
            $totalGenerated = 0;
            $totalSkipped = 0;
            $batches = [];

            foreach ($monthsToProcess as $currentMonth) {
                $log('info', "Processing Month: {$currentMonth} {$targetYear}");

                $monthIndex = array_search($currentMonth, self::ALL_MONTHS, true);
                if ($monthIndex !== false) {
                    $paddedMonth = str_pad((string)($monthIndex + 1), 2, '0', STR_PAD_LEFT);
                } else {
                    $paddedMonth = date('m', strtotime($params['due_date'] ?? 'now'));
                }

                $prefix = "INV-{$targetYear}{$paddedMonth}-";

                // Base Fee Setup Query
                $setupQuery = FeeSetup::where('session_year_id', $session->id)
                    ->where('status', 'Active');

                if (!empty($params['fee_category_id'])) {
                    $setupQuery->where('fee_category_id', (int)$params['fee_category_id']);
                } else {
                    // Universal bulk generation strictly excludes Point-of-Admission (Admission Fee) and manual/incident (Fine)
                    $setupQuery->whereHas('category', function ($cq) {
                        $cq->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(name))'), ['admission fee', 'fine']);
                    });
                }

                if (!empty($params['class_id'])) {
                    $setupQuery->where('class_id', (int)$params['class_id']);
                }

                if (!empty($params['branch_id'])) {
                    $setupQuery->where('branch_id', (int)$params['branch_id']);
                }

                // Month filter: Match recurring monthly setups, explicit month setups, or one-time setups (in Jan)
                $setupQuery->where(function ($query) use ($currentMonth) {
                    $query->where('fee_month', 'Monthly')
                          ->orWhereRaw('LOWER(TRIM(fee_month)) = ?', [strtolower($currentMonth)]);

                    if (strtolower($currentMonth) === 'january') {
                        $query->orWhere(function ($oq) {
                            $oq->where(function ($q) {
                                $q->whereNull('fee_month')
                                  ->orWhere('fee_month', '')
                                  ->orWhereRaw('LOWER(TRIM(fee_month)) IN (?, ?)', ['one time', 'yearly']);
                            })->whereHas('category', function ($cq) {
                                // Unscheduled Others with empty fee_month must NOT be automatically generated in January
                                $cq->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(name))'), ['admission fee', 'fine', 'others']);
                            });
                        });
                    }
                });

                $feeSetups = $setupQuery->with(['category', 'schoolClass', 'branch'])->get();

                if ($feeSetups->isEmpty()) {
                    $log('line', "  [INFO] No active matching Fee Setups found for {$currentMonth}.");
                    continue;
                }

                $log('line', "  [FOUND] {$feeSetups->count()} active Fee Setup(s) to process for {$currentMonth}.");

                foreach ($feeSetups as $feeSetup) {
                    $className = $feeSetup->schoolClass->class_name ?? "Class ID {$feeSetup->class_id}";
                    $catName = $feeSetup->category->name ?? "Category ID {$feeSetup->fee_category_id}";
                    $branchName = $feeSetup->branch->branch_name ?? "Branch ID {$feeSetup->branch_id}";

                    // 1. Fetch Candidate Students
                    $students = Student::where([
                        'branch_id'       => $feeSetup->branch_id,
                        'class_id'        => $feeSetup->class_id,
                        'session_year_id' => $feeSetup->session_year_id,
                    ])->with('academicHistories')->get();

                    if ($students->isEmpty()) {
                        continue;
                    }

                    $isAdmissionCategory = strtolower(trim($feeSetup->category->name ?? '')) === 'admission fee';

                    // 2. Filter Eligible Students via Category-Aware 3-Tier Model
                    $eligibleStudents = $students->filter(function ($student) use ($targetYear, $paddedMonth, $feeSetup, $isAdmissionCategory) {
                        if ($isAdmissionCategory) {
                            return $this->isStudentEligibleForAdmission($student, (int)$feeSetup->session_year_id, (int)$targetYear);
                        }
                        return $this->isStudentEligibleForMonth($student, (int)$targetYear, $paddedMonth);
                    });

                    if ($eligibleStudents->isEmpty()) {
                        continue;
                    }

                    // Determine Due Date
                    $computedDueDate = $params['due_date'] ?? null;
                    if (!$computedDueDate || $isAnnual) {
                        $computedDueDate = Carbon::parse("10 {$currentMonth} {$targetYear}")->toDateString();
                    }

                    $isMonthlyRecurring = strtolower(trim($feeSetup->fee_month ?? '')) === 'monthly';

                    // 3. In-Transaction Serial Calculation & Insertion
                    DB::beginTransaction();
                    try {
                        // Serial locked via lockForUpdate() under the global advisory lock
                        $lastInvoice = FeeInvoice::where('invoice_no', 'like', $prefix . '%')
                            ->lockForUpdate()
                            ->orderBy('id', 'desc')
                            ->first();

                        $lastSerial = 0;
                        if ($lastInvoice) {
                            $lastSerial = (int) str_replace($prefix, '', $lastInvoice->invoice_no);
                        }

                        $batchGenerated = 0;
                        $batchSkipped = 0;

                        foreach ($eligibleStudents as $student) {
                            // Idempotency: Skip if invoice already exists
                            if ($isMonthlyRecurring) {
                                $exists = FeeInvoice::where('student_id', $student->id)
                                    ->where('fee_setup_id', $feeSetup->id)
                                    ->where('invoice_no', 'like', $prefix . '%')
                                    ->exists();
                            } else {
                                $exists = FeeInvoice::where('student_id', $student->id)
                                    ->where('fee_setup_id', $feeSetup->id)
                                    ->exists();
                            }

                            if ($exists) {
                                $batchSkipped++;
                                continue;
                            }

                            $lastSerial++;
                            $invoiceNo = $prefix . str_pad((string)$lastSerial, 4, '0', STR_PAD_LEFT);

                            // Student Custom Fee Waiver Snapshot
                            $customFee = StudentCustomFee::where('student_id', $student->id)
                                ->where('fee_category_id', $feeSetup->fee_category_id)
                                ->first();

                            $setupAmount = round((float)$feeSetup->amount, 2);
                            $netAmount = $setupAmount;
                            $discount = 0.00;

                            if ($customFee) {
                                $netAmount = round((float)$customFee->amount, 2);
                                $discount = max(0.00, round($setupAmount - $netAmount, 2));
                            }

                            if (!$isDryRun) {
                                FeeInvoice::create([
                                    'invoice_no'   => $invoiceNo,
                                    'student_id'   => $student->id,
                                    'fee_setup_id' => $feeSetup->id,
                                    'amount'       => $setupAmount,
                                    'discount'     => $discount,
                                    'net_amount'   => $netAmount,
                                    'due_amount'   => $netAmount,
                                    'paid_amount'  => 0.00,
                                    'status'       => 'Unpaid',
                                    'due_date'     => $computedDueDate,
                                    'user_id'      => $userId,
                                ]);
                            }

                            $batchGenerated++;
                        }

                        DB::commit();

                        $totalGenerated += $batchGenerated;
                        $totalSkipped += $batchSkipped;

                        $batches[] = [
                            'month'     => $currentMonth,
                            'class'     => $className,
                            'category'  => $catName,
                            'generated' => $batchGenerated,
                            'skipped'   => $batchSkipped,
                        ];

                        $actionLabel = $isDryRun ? "Would Generate" : "Generated";
                        $log('line', "  -> {$className} ({$catName}): {$actionLabel} {$batchGenerated}, Skipped {$batchSkipped}.");

                    } catch (Throwable $e) {
                        DB::rollBack();
                        $log('error', "  -> Batch failed for {$className}: " . $e->getMessage());
                        throw $e;
                    }
                }
            }

            return [
                'success'         => true,
                'total_generated' => $totalGenerated,
                'total_skipped'   => $totalSkipped,
                'session_id'      => $session->id,
                'session_name'    => $session->session_name,
                'year'            => $targetYear,
                'is_dry_run'      => $isDryRun,
                'batches'         => $batches,
            ];

        } catch (Throwable $e) {
            $log('error', "FATAL EXECUTION EXCEPTION: " . $e->getMessage());
            return [
                'success'     => false,
                'lock_failed' => false,
                'error'       => $e->getMessage(),
            ];
        } finally {
            if ($hasLock) {
                $releaseStmt = $pdo->prepare("SELECT RELEASE_LOCK(?)");
                $releaseStmt->execute([self::ADVISORY_LOCK_NAME]);
                $log('info', "[LOCK] Global advisory lock released cleanly.");
            }
        }
    }

    /**
     * Resolve the target SessionYear model unambiguously:
     * 1. Exact match on session_name (e.g. "2026", "2027") prevents collision with primary key IDs.
     * 2. Numeric primary key ID lookup (if no session has this exact name).
     * 3. Substring match on session_name.
     */
    public function resolveSession(int|string|null $sessionOpt, int|string|null $yearOpt): ?SessionYear
    {
        if ($sessionOpt) {
            $opt = trim((string)$sessionOpt);

            // 1. Exact session_name match (e.g., '2026' matches Session with session_name='2026')
            $session = SessionYear::where('session_name', $opt)->first();
            if ($session) {
                return $session;
            }

            // 2. Numeric Primary Key ID lookup (only if no session has this exact name)
            if (is_numeric($opt)) {
                $session = SessionYear::find((int)$opt);
                if ($session) {
                    return $session;
                }
            }

            // 3. Substring match
            return SessionYear::where('session_name', 'like', "%{$opt}%")->first();
        }

        if ($yearOpt) {
            $reqYear = trim((string)$yearOpt);
            return SessionYear::where('session_name', $reqYear)->first()
                ?: SessionYear::where('session_name', 'like', "%{$reqYear}%")->first()
                ?: SessionYear::latest()->first();
        }

        return SessionYear::latest()->first();
    }

    /**
     * Resolve canonical 4-digit calendar year with strict conflict validation.
     */
    public function resolveYear(SessionYear $session, int|string|null $cliYear): ?int
    {
        $sessionName = trim($session->session_name);
        $sessionNumericYear = null;

        if (preg_match('/^\d{4}$/', $sessionName)) {
            $sessionNumericYear = (int)$sessionName;
        } elseif (preg_match('/(\d{4})/', $sessionName, $m)) {
            $sessionNumericYear = (int)$m[1];
        }

        $explicitYear = $cliYear ? (int)$cliYear : null;

        if ($explicitYear && $sessionNumericYear && $explicitYear !== $sessionNumericYear) {
            if (strpos($sessionName, (string)$explicitYear) === false) {
                return null; // Mismatch / Conflict
            }
        }

        return $explicitYear ?: ($sessionNumericYear ?: (int)now()->format('Y'));
    }

    /**
     * Resolve canonical billing period(s) for a given FeeSetup.
     *
     * @param FeeSetup $setup
     * @param int|null $targetYear
     * @return array Array of billing period definitions:
     *               [
     *                   [
     *                       'month_name'   => 'January',
     *                       'month_num'    => '01',
     *                       'period'       => '2026-01',
     *                       'prefix'       => 'INV-202601-',
     *                       'is_recurring' => true,
     *                       'default_due'  => '2026-01-10',
     *                   ],
     *                   ...
     *               ]
     */
    public function resolveSetupBillingPeriods(FeeSetup $setup, ?int $targetYear = null): array
    {
        $year = $targetYear ?: (int)now()->format('Y');
        $feeMonth = trim((string)($setup->fee_month ?? ''));
        $catName = strtolower(trim($setup->category->name ?? ''));

        // Point-of-Admission Fee and manual incident Fine are NOT scheduled cohort billing periods
        if (in_array($catName, ['admission fee', 'fine'], true)) {
            return [];
        }

        // Unscheduled Others without explicit month are manual/ambiguous and have no scheduled periods
        if ($catName === 'others' && empty($feeMonth)) {
            return [];
        }

        // 1. Recurring Monthly Fee: All 12 calendar months
        if (strtolower($feeMonth) === 'monthly') {
            $periods = [];
            foreach (self::ALL_MONTHS as $index => $monthName) {
                $monthNum = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
                $periods[] = [
                    'month_name'   => $monthName,
                    'month_num'    => $monthNum,
                    'period'       => sprintf('%04d-%s', $year, $monthNum),
                    'prefix'       => sprintf('INV-%04d%s-', $year, $monthNum),
                    'is_recurring' => true,
                    'default_due'  => sprintf('%04d-%s-10', $year, $monthNum),
                ];
            }
            return $periods;
        }

        // 2. Explicit Month (e.g. 'March', 'April', 'July', etc.)
        $monthIndex = array_search(ucfirst(strtolower($feeMonth)), self::ALL_MONTHS, true);
        if ($monthIndex !== false) {
            $monthNum = str_pad((string)($monthIndex + 1), 2, '0', STR_PAD_LEFT);
            $monthName = self::ALL_MONTHS[$monthIndex];
            return [[
                'month_name'   => $monthName,
                'month_num'    => $monthNum,
                'period'       => sprintf('%04d-%s', $year, $monthNum),
                'prefix'       => sprintf('INV-%04d%s-', $year, $monthNum),
                'is_recurring' => false,
                'default_due'  => sprintf('%04d-%s-10', $year, $monthNum),
            ]];
        }

        // 3. One Time / Yearly Fee: Default to Month 01 (January)
        return [[
            'month_name'   => 'January',
            'month_num'    => '01',
            'period'       => sprintf('%04d-01', $year),
            'prefix'       => sprintf('INV-%04d01-', $year),
            'is_recurring' => false,
            'default_due'  => sprintf('%04d-01-10', $year),
        ]];
    }

    /**
     * Detect overlapping active Fee Setups for the same scope (branch, class, category, and month).
     *
     * @param int $sessionYearId
     * @param int|null $targetYear
     * @return array
     */
    public function detectSetupConflicts(int $sessionYearId, ?int $targetYear = null): array
    {
        $session = SessionYear::find($sessionYearId);
        if (!$session) {
            return [];
        }

        $year = $this->resolveYear($session, $targetYear);

        $setups = FeeSetup::where('session_year_id', $sessionYearId)
            ->where('status', 'Active')
            ->whereHas('category', function ($cq) {
                $cq->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(name))'), ['admission fee', 'fine']);
            })
            ->with(['category', 'schoolClass'])
            ->get();

        $conflicts = [];
        $grouped = $setups->groupBy(function ($s) {
            return $s->branch_id . '_' . $s->class_id . '_' . $s->fee_category_id;
        });

        foreach ($grouped as $scope => $items) {
            if ($items->count() <= 1) {
                continue;
            }

            $monthToSetups = [];
            foreach ($items as $setup) {
                $periods = $this->resolveSetupBillingPeriods($setup, (int)$year);
                foreach ($periods as $p) {
                    $monthNum = $p['month_num'];
                    $monthToSetups[$monthNum][] = $setup;
                }
            }

            foreach ($monthToSetups as $m => $matchedSetups) {
                if (count($matchedSetups) > 1) {
                    $setupIds = array_map(fn($s) => $s->id, $matchedSetups);
                    $className = $matchedSetups[0]->schoolClass->class_name ?? "Class {$matchedSetups[0]->class_id}";
                    $catName = $matchedSetups[0]->category->name ?? "Category {$matchedSetups[0]->fee_category_id}";
                    $conflicts[] = [
                        'scope'           => $scope,
                        'class_id'        => (int)$matchedSetups[0]->class_id,
                        'fee_category_id' => (int)$matchedSetups[0]->fee_category_id,
                        'class_name'      => $className,
                        'category_name'   => $catName,
                        'month'           => $m,
                        'setup_ids'       => $setupIds,
                        'description'     => "Overlapping active setups for {$className} in {$catName} for month {$m} (Setup IDs: " . implode(', ', $setupIds) . ")",
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Generate Admission Fee Invoice strictly at Point-of-Admission for a newly created student.
     *
     * @param Student $student
     * @param int|null $actorUserId
     * @return array ['success' => bool, 'created' => bool, 'invoice' => FeeInvoice|null, 'message' => string]
     */
    public function generateAdmissionFeeForNewStudent(Student $student, ?int $actorUserId = null): array
    {
        if (empty($student->id) || empty($student->class_id) || empty($student->session_year_id) || empty($student->branch_id)) {
            return [
                'success' => false,
                'created' => false,
                'invoice' => null,
                'message' => 'Student record is missing essential academic association attributes.',
            ];
        }

        // 1. Locate active Admission Fee setup for student's branch, class, and session
        $admissionSetup = FeeSetup::where([
            'session_year_id' => $student->session_year_id,
            'branch_id'       => $student->branch_id,
            'class_id'        => $student->class_id,
            'status'          => 'Active',
        ])->whereHas('category', function ($cq) {
            $cq->whereRaw('LOWER(TRIM(name)) = ?', ['admission fee']);
        })->first();

        if (!$admissionSetup) {
            return [
                'success' => true,
                'created' => false,
                'invoice' => null,
                'message' => "No active Admission Fee setup found for Class ID {$student->class_id} in Session ID {$student->session_year_id}.",
            ];
        }

        // 2. Check Idempotency: Does an invoice already exist for this student and admission setup?
        $existingInvoice = FeeInvoice::where([
            'student_id'   => $student->id,
            'fee_setup_id' => $admissionSetup->id,
        ])->first();

        if ($existingInvoice) {
            return [
                'success' => true,
                'created' => false,
                'invoice' => $existingInvoice,
                'message' => "Admission Fee invoice already exists (Invoice: {$existingInvoice->invoice_no}).",
            ];
        }

        // 3. Resolve Amount & Custom Fees
        $amount = (float)$admissionSetup->amount;
        $customFee = \Illuminate\Support\Facades\DB::table('student_custom_fees')
            ->where('student_id', $student->id)
            ->where('fee_category_id', $admissionSetup->fee_category_id)
            ->value('amount');

        if ($customFee !== null && (float)$customFee >= 0) {
            $amount = (float)$customFee;
        }

        // 4. Resolve Target Year & Month
        $targetYear = (int)($student->created_at ? $student->created_at->format('Y') : date('Y'));
        $targetMonth = $student->created_at ? $student->created_at->format('m') : date('m');

        // Invoice Number: standard convention INV-{YEAR}{MONTH}-{STUDENT_ID}-{SETUP_ID}
        $prefix = sprintf('INV-%04d%s-', $targetYear, $targetMonth);
        $invoiceNumber = sprintf('%s%d-%d', $prefix, $student->id, $admissionSetup->id);

        // Due Date: 10th of admission month
        $dueDate = sprintf('%04d-%s-10', $targetYear, $targetMonth);

        // 5. Create Fee Invoice
        $resolvedUserId = $this->resolveActorUserId($actorUserId);

        $invoice = FeeInvoice::create([
            'invoice_no'   => $invoiceNumber,
            'student_id'   => $student->id,
            'fee_setup_id' => $admissionSetup->id,
            'amount'       => $amount,
            'discount'     => 0.00,
            'net_amount'   => $amount,
            'due_amount'   => $amount,
            'paid_amount'  => 0.00,
            'status'       => 'Unpaid',
            'due_date'     => $dueDate,
            'user_id'      => $resolvedUserId,
        ]);

        return [
            'success' => true,
            'created' => true,
            'invoice' => $invoice,
            'message' => "Admission Fee invoice '{$invoiceNumber}' generated successfully.",
        ];
    }

    /**
     * Category-Specific Eligibility Rule: Admission Fee.
     * Admission Fee must be generated ONLY for newly admitted students in the target Academic Session.
     * Promoted students from earlier sessions and continuing legacy students are strictly excluded.
     *
     * @param Student $student
     * @param int $sessionYearId
     * @param int $targetYear
     * @return bool
     */
    public function isStudentEligibleForAdmission(Student $student, int $sessionYearId, int $targetYear): bool
    {
        // 1. Inactive students are ineligible for new admission fee
        if (($student->status ?? 'Active') === 'Inactive') {
            return false;
        }

        // 2. Must belong to the target session
        if ((int)$student->session_year_id !== $sessionYearId) {
            return false;
        }

        // 3. Promoted students have academic histories from previous sessions -> strictly excluded
        if ($student->relationLoaded('academicHistories')) {
            if ($student->academicHistories->isNotEmpty()) {
                return false;
            }
        } else {
            if ($student->academicHistories()->exists()) {
                return false;
            }
        }

        // 4. Continuing / legacy students created prior to target session year -> strictly excluded
        if ($student->created_at && (int)$student->created_at->format('Y') < $targetYear) {
            return false;
        }

        // 5. If explicit admission_date exists, its year must match target year
        if (!empty($student->admission_date)) {
            $admYear = (int)date('Y', strtotime($student->admission_date));
            if ($admYear !== $targetYear) {
                return false;
            }
        }

        // 6. Historical legacy batch check: For initial session (2026), students imported in the
        // legacy bulk import (2026-07-22) into higher classes (not entry-level Play) were existing continuing students.
        if ($targetYear === 2026 && $student->created_at && $student->created_at->toDateString() <= '2026-07-31') {
            $className = strtolower($student->schoolClass->class_name ?? '');
            if ($className !== 'play' && (int)($student->class_id ?? 0) > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * 3-Tier Student Eligibility Model.
     *
     * 1. Inactive Students: Excluded beyond their discontinued_month.
     * 2. Legacy / Promoted Students: Eligible for all 12 months.
     * 3. Mid-Session New Admissions: Eligible strictly from admission creation month onwards.
     */
    public function isStudentEligibleForMonth(Student $student, int $year, string $paddedMonth): bool
    {
        $targetPeriod = sprintf('%04d-%02d', $year, (int)$paddedMonth);

        // Tier 1: Inactive / Discontinued student evaluation
        if (($student->status ?? 'Active') === 'Inactive') {
            if (!empty($student->discontinued_month)) {
                $disc = trim($student->discontinued_month);
                if (preg_match('/^\d{4}-\d{2}$/', $disc)) {
                    return $targetPeriod <= $disc;
                }
                if (preg_match('/^(\d{4}-\d{2})-\d{2}$/', $disc, $m)) {
                    return $targetPeriod <= $m[1];
                }
            }
            return false;
        }

        // Tier 2: Active Student Evaluation
        if (!$student->created_at) {
            return true;
        }

        // Legacy check:
        // A. Created prior to target year (e.g. created in 2026, billing for 2027) -> Promoted / Existing student
        if ($student->created_at->format('Y') < (string)$year) {
            return true;
        }

        // B. Historical bulk import boundary for 2026 session (legacy cohort imported 2026-07-22)
        if ($year === 2026 && $student->created_at->toDateString() <= '2026-07-31') {
            return true;
        }

        // C. Student created during initial session intake window (Month 01 of target year)
        $studentCreationPeriod = $student->created_at->format('Y-m');
        if ($studentCreationPeriod === sprintf('%04d-01', $year)) {
            return true;
        }

        // Tier 3: Mid-Session New Admission (created after Month 01 of target year)
        // Eligible only if the target billing month is on or after the enrollment month
        return $targetPeriod >= $studentCreationPeriod;
    }

    /**
     * Get readiness and generation status for a specific academic session.
     * Accurately calculates expected invoices using identical student eligibility
     * and class/branch partitioning rules as the annual generation engine.
     * Excludes unrelated fee categories and unrelated billing years.
     *
     * @param int $sessionYearId
     * @return array [
     *     'session_id'            => int,
     *     'session_name'          => string|null,
     *     'year'                  => int|null,
     *     'is_ready'              => bool,
     *     'status'                => 'ready'|'partial'|'completed'|'pending_students'|'pending_fee_setup'|'pending_both'|'not_found',
     *     'active_students_count' => int,
     *     'monthly_setups_count'  => int,
     *     'expected_invoices'     => int,
     *     'existing_invoices'     => int,
     *     'reason'                => string,
     * ]
     */
    public function getSessionGenerationStatus(int $sessionYearId): array
    {
        $session = SessionYear::find($sessionYearId);
        if (!$session) {
            return [
                'session_id'            => $sessionYearId,
                'session_name'          => null,
                'year'                  => null,
                'is_ready'              => false,
                'status'                => 'not_found',
                'active_students_count' => 0,
                'monthly_setups_count'  => 0,
                'expected_invoices'     => 0,
                'existing_invoices'     => 0,
                'reason'                => "Session ID {$sessionYearId} not found.",
            ];
        }

        $targetYear = $this->resolveYear($session, null);

        // 1. Fetch ALL active scheduled fee setups strictly for this session (excluding Point-of-Admission & manual Fine)
        $activeSetups = FeeSetup::where('session_year_id', $sessionYearId)
            ->where('status', 'Active')
            ->whereHas('category', function ($cq) {
                $cq->whereNotIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(name))'), ['admission fee', 'fine']);
            })
            ->with(['category', 'schoolClass', 'branch'])
            ->get();

        $activeSetupsCount = $activeSetups->count();
        $totalSessionStudentsCount = Student::where('session_year_id', $sessionYearId)->count();

        if ($activeSetupsCount === 0 && $totalSessionStudentsCount === 0) {
            return [
                'session_id'            => $sessionYearId,
                'session_name'          => $session->session_name,
                'year'                  => $targetYear,
                'is_ready'              => false,
                'status'                => 'pending_both',
                'active_students_count' => 0,
                'active_setups_count'   => 0,
                'monthly_setups_count'  => 0,
                'expected_invoices'     => 0,
                'existing_invoices'     => 0,
                'reason'                => "No students and no active Fee Setups configured for session '{$session->session_name}'.",
            ];
        }

        if ($activeSetupsCount === 0) {
            return [
                'session_id'            => $sessionYearId,
                'session_name'          => $session->session_name,
                'year'                  => $targetYear,
                'is_ready'              => false,
                'status'                => 'pending_fee_setup',
                'active_students_count' => $totalSessionStudentsCount,
                'active_setups_count'   => 0,
                'monthly_setups_count'  => 0,
                'expected_invoices'     => 0,
                'existing_invoices'     => 0,
                'reason'                => "No active Fee Setups configured for session '{$session->session_name}'.",
            ];
        }

        // 2. Fetch candidate students matching setup class and branch scopes
        $candidateClasses = $activeSetups->pluck('class_id')->unique()->toArray();
        $candidateBranches = $activeSetups->pluck('branch_id')->unique()->toArray();

        $candidateStudents = Student::where('session_year_id', $sessionYearId)
            ->whereIn('class_id', $candidateClasses)
            ->whereIn('branch_id', $candidateBranches)
            ->with('academicHistories')
            ->get();

        if ($candidateStudents->isEmpty()) {
            return [
                'session_id'            => $sessionYearId,
                'session_name'          => $session->session_name,
                'year'                  => $targetYear,
                'is_ready'              => false,
                'status'                => 'pending_students',
                'active_students_count' => 0,
                'active_setups_count'   => $activeSetupsCount,
                'monthly_setups_count'  => $activeSetupsCount,
                'expected_invoices'     => 0,
                'existing_invoices'     => 0,
                'reason'                => "No students enrolled in the classes and branches matching active Fee Setups for session '{$session->session_name}'.",
            ];
        }

        // 3. Fetch existing invoices strictly for these Fee Setups and target billing year
        $setupIds = $activeSetups->pluck('id')->toArray();
        $existingInvoices = FeeInvoice::whereIn('fee_setup_id', $setupIds)
            ->where('invoice_no', 'like', "INV-{$targetYear}%")
            ->select('id', 'student_id', 'fee_setup_id', 'invoice_no', 'status')
            ->get();

        // Build active key index, cancelled index, and track malformed month anomalies
        $activeKeyCounts = [];
        $cancelledKeyCounts = [];
        $cancelledCount = 0;
        $invalidMonthInvoicesCount = 0;

        foreach ($existingInvoices as $inv) {
            $isCancelled = ($inv->status === 'Cancelled');
            if ($isCancelled) {
                $cancelledCount++;
            }

            // Strict regex: invoice month identifier must be exactly 01 through 12
            if (preg_match('/^INV-\d{4}(0[1-9]|1[0-2])-/', $inv->invoice_no, $matches)) {
                $monthKey = $matches[1];
                $key = $inv->student_id . '_' . $inv->fee_setup_id . '_' . $monthKey;
                if ($isCancelled) {
                    $cancelledKeyCounts[$key] = ($cancelledKeyCounts[$key] ?? 0) + 1;
                } else {
                    $activeKeyCounts[$key] = ($activeKeyCounts[$key] ?? 0) + 1;
                }
            } else {
                // Out-of-range or malformed month (e.g. INV-YYYY99-XXXX or INV-YYYY00-XXXX)
                $invalidMonthInvoicesCount++;
            }
        }

        // 4. Calculate exact expected keys and verify individual key-level coverage
        $studentsByScope = $candidateStudents->groupBy(function ($s) {
            return $s->branch_id . '_' . $s->class_id;
        });

        $expectedKeys = [];
        $matchedExistingCount = 0;
        $activeMatchedStudentIds = [];
        $eligibleMatchedStudentIds = [];
        $cancelledExpectedSlotsCount = 0;

        foreach ($activeSetups as $feeSetup) {
            $scopeKey = $feeSetup->branch_id . '_' . $feeSetup->class_id;
            $studentsInScope = $studentsByScope->get($scopeKey, collect());
            $isAdmissionCategory = strtolower(trim($feeSetup->category->name ?? '')) === 'admission fee';

            // Resolve billing periods for this specific setup
            $periods = $this->resolveSetupBillingPeriods($feeSetup, (int)$targetYear);

            foreach ($studentsInScope as $student) {
                if (($student->status ?? 'Active') === 'Active') {
                    $activeMatchedStudentIds[$student->id] = true;
                }

                foreach ($periods as $periodDef) {
                    $paddedMonth = $periodDef['month_num'];

                    $isEligible = $isAdmissionCategory
                        ? $this->isStudentEligibleForAdmission($student, (int)$feeSetup->session_year_id, (int)$targetYear)
                        : $this->isStudentEligibleForMonth($student, (int)$targetYear, $paddedMonth);

                    if ($isEligible) {
                        $key = $student->id . '_' . $feeSetup->id . '_' . $paddedMonth;
                        $expectedKeys[$key] = true;
                        $eligibleMatchedStudentIds[$student->id] = true;

                        if (!empty($activeKeyCounts[$key])) {
                            $matchedExistingCount++;
                        } elseif (!empty($cancelledKeyCounts[$key])) {
                            $cancelledExpectedSlotsCount++;
                        }
                    }
                }
            }
        }

        $expectedCount = count($expectedKeys);
        $totalActiveExistingCount = count($existingInvoices->where('status', '!=', 'Cancelled'));
        $totalExistingCount = $existingInvoices->count();
        $activeMatchedStudentsCount = count($activeMatchedStudentIds);
        $eligibleMatchedStudentsCount = count($eligibleMatchedStudentIds);

        // Analyze key-level discrepancies: missing, extra/unexpected, and duplicate invoices
        $missingKeysCount = 0;
        foreach ($expectedKeys as $key => $_) {
            if (empty($activeKeyCounts[$key])) {
                $missingKeysCount++;
            }
        }

        $extraKeysCount = 0;
        $duplicateInvoicesCount = 0;
        foreach ($activeKeyCounts as $key => $count) {
            if (!isset($expectedKeys[$key])) {
                $extraKeysCount += $count;
            }
            if ($count > 1) {
                $duplicateInvoicesCount += ($count - 1);
            }
        }

        // 5. Determine precise lifecycle readiness status
        $conflicts = $this->detectSetupConflicts($sessionYearId, (int)$targetYear);
        $hasSetupConflict = !empty($conflicts);

        if ($hasSetupConflict) {
            $conflictSetupIds = array_unique(array_merge(...array_column($conflicts, 'setup_ids')));
            $status = 'setup_conflict';
            $reason = "Inspection Required: Play Setup Conflict Detected. Overlapping active setups exist for Class {$conflicts[0]['class_name']} in {$conflicts[0]['category_name']} (Setup IDs: " . implode(', ', $conflictSetupIds) . "). Automated bulk generation is locked until resolved in Fee Setup.";
        } elseif ($expectedCount === 0) {
            $status = 'pending_students';
            $reason = "No eligible students found for the applicable billing periods of session '{$session->session_name}'.";
        } elseif ($totalExistingCount === 0) {
            $status = 'ready';
            $reason = "Prerequisites met. Ready for universal invoice generation (Expected: {$expectedCount} eligible invoices across {$activeSetupsCount} active setup(s)).";
        } elseif ($invalidMonthInvoicesCount > 0 || $extraKeysCount > 0 || $duplicateInvoicesCount > 0 || $totalActiveExistingCount > $expectedCount) {
            // Anomaly detected: duplicate invoices, invalid months, extra invoices outside eligibility, or count mismatch
            $status = 'partial';
            $anomalyParts = [];
            if ($invalidMonthInvoicesCount > 0) {
                $anomalyParts[] = "{$invalidMonthInvoicesCount} invoice(s) with invalid month identifiers (outside 01-12)";
            }
            if ($duplicateInvoicesCount > 0) {
                $anomalyParts[] = "{$duplicateInvoicesCount} duplicate active invoice(s)";
            }
            if ($extraKeysCount > 0) {
                $anomalyParts[] = "{$extraKeysCount} active invoice(s) outside expected eligibility";
            }
            if ($totalActiveExistingCount > $expectedCount) {
                $anomalyParts[] = "total active count ({$totalActiveExistingCount}) exceeds expected ({$expectedCount})";
            }
            $reason = "Invoice count mismatch: Found {$totalActiveExistingCount} active invoices vs {$expectedCount} expected eligible invoices (" . implode(', ', $anomalyParts) . "). Manual inspection required.";
        } elseif ($missingKeysCount > 0) {
            $status = 'partial';
            $cancelledNote = $cancelledExpectedSlotsCount > 0
                ? " ({$cancelledExpectedSlotsCount} cancelled invoice(s) require restoration via Restore Invoice)"
                : "";
            $reason = "Partial session coverage: {$matchedExistingCount} of {$expectedCount} invoices present{$cancelledNote}. Retry will generate remaining {$missingKeysCount} missing invoice(s).";
        } elseif ($missingKeysCount === 0 && $extraKeysCount === 0 && $duplicateInvoicesCount === 0 && $invalidMonthInvoicesCount === 0 && $totalActiveExistingCount === $expectedCount) {
            $status = 'completed';
            $reason = "Full session invoice coverage verified: All {$expectedCount} expected invoices across {$activeSetupsCount} active setup(s) are present.";
        } else {
            $status = 'partial';
            $reason = "Partial session coverage: {$matchedExistingCount} of {$expectedCount} invoices present.";
        }

        return [
            'session_id'              => $sessionYearId,
            'session_name'            => $session->session_name,
            'year'                    => $targetYear,
            'is_ready'                => ($eligibleMatchedStudentsCount > 0 && $activeSetupsCount > 0 && $expectedCount > 0 && !$hasSetupConflict),
            'status'                  => $status,
            'has_setup_conflict'      => $hasSetupConflict,
            'setup_conflicts'         => $conflicts,
            'active_students_count'   => $activeMatchedStudentsCount,
            'eligible_students_count' => $eligibleMatchedStudentsCount,
            'active_setups_count'     => $activeSetupsCount,
            'monthly_setups_count'    => $activeSetupsCount, // Backward compatibility alias
            'expected_invoices'      => $expectedCount,
            'existing_invoices'      => $totalActiveExistingCount,
            'total_invoices'         => $totalExistingCount,
            'matched_invoices'       => $matchedExistingCount,
            'missing_invoices'       => $missingKeysCount,
            'duplicate_invoices'     => $duplicateInvoicesCount,
            'extra_invoices'         => $extraKeysCount,
            'cancelled_invoices'     => $cancelledCount,
            'invalid_month_invoices' => $invalidMonthInvoicesCount,
            'reason'                 => $reason,
        ];
    }

    /**
     * Resolve valid, active User ID for audit attribution according to explicit administrative policy:
     * 1. Validated explicit User ID passed by caller (must exist, be active, and hold an authorized administrative role).
     * 2. Authenticated user ID (Auth::id()) if currently logged in, active, and authorized. Non-admin users fall through.
     * 3. Config-driven system actor: config('fees.system_actor_id') (must be active and authorized).
     * 4. Dedicated institutional admin email: config('fees.system_actor_email') (must be active and authorized).
     * 5. First active Super Admin / Admin user.
     * Throws RuntimeException if all administrative candidate accounts are missing, inactive, or suspended.
     */
    public function resolveActorUserId(?int $explicitUserId = null): int
    {
        $allowedRoles = config('fees.authorized_roles', ['Super Admin', 'Admin', 'super-admin', 'admin']);

        // 1. Explicitly validated User ID (must be active AND hold an authorized administrative role)
        if (!empty($explicitUserId)) {
            $explicitUser = \App\Models\User::where('id', $explicitUserId)
                ->whereNull('suspended_at')
                ->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles))
                ->first();
            if ($explicitUser) {
                return (int) $explicitUser->id;
            }
        }

        // 2. Currently Authenticated Actor (must be active AND hold an authorized administrative role)
        if (\Illuminate\Support\Facades\Auth::check()) {
            $authUser = \Illuminate\Support\Facades\Auth::user();
            if ($authUser && empty($authUser->suspended_at)) {
                $isAuthorized = $authUser->roles()
                    ->whereIn('name', $allowedRoles)
                    ->exists();
                if ($isAuthorized) {
                    return (int) $authUser->id;
                }
                // Non-admin authenticated user (e.g. Teacher, Student, or ordinary staff)
                // is NOT accepted as the audit actor for invoice generation.
                // Execution safely falls through to configured institutional administrator.
            }
        }

        // 3. Configuration-driven System Actor ID (must be active AND hold an authorized administrative role)
        $configId = config('fees.system_actor_id');
        if (!empty($configId)) {
            $configuredUser = \App\Models\User::where('id', $configId)
                ->whereNull('suspended_at')
                ->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles))
                ->first();
            if ($configuredUser) {
                return (int) $configuredUser->id;
            }
        }

        // 4. Configuration-driven Institutional System Admin Email (must be active AND hold an authorized administrative role)
        $systemEmail = config('fees.system_actor_email', 'admin@macsschool.edu.bd');
        if (!empty($systemEmail)) {
            $systemUser = \App\Models\User::where('email', $systemEmail)
                ->whereNull('suspended_at')
                ->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles))
                ->first();
            if ($systemUser) {
                return (int) $systemUser->id;
            }
        }

        // 5. First active Super Admin / Admin user
        $adminUser = \App\Models\User::whereNull('suspended_at')
            ->whereHas('roles', fn($q) => $q->whereIn('name', $allowedRoles))
            ->orderBy('id')
            ->first();
        if ($adminUser) {
            return (int) $adminUser->id;
        }

        // Strictly NO fallback to arbitrary non-admin user
        throw new RuntimeException("Invoice generation aborted: No valid, active system administrator could be resolved for audit attribution. All administrative accounts are missing, inactive, or suspended.");
    }

    /**
     * Readiness-aware annual fee generation trigger for an academic session.
     * Evaluates prerequisites (eligible enrolled/discontinued students matching setup scope + active monthly fee setups).
     * If prerequisites are satisfied (is_ready === true), triggers annual generation (all 12 months) under the global advisory lock.
     *
     * @param int $sessionYearId
     * @param int|null $userId
     * @return array
     */
    public function evaluateAndGenerateForSession(int $sessionYearId, ?int $userId = null): array
    {
        $statusBefore = $this->getSessionGenerationStatus($sessionYearId);

        if (!$statusBefore['is_ready'] || ($statusBefore['status'] ?? '') === 'setup_conflict' || !empty($statusBefore['has_setup_conflict'])) {
            return [
                'evaluated'         => false,
                'status'            => $statusBefore['status'],
                'session_id'        => $sessionYearId,
                'reason'            => $statusBefore['reason'],
                'generated'         => 0,
                'skipped'           => 0,
                'execution_success' => false,
                'has_conflict'      => !empty($statusBefore['has_setup_conflict']),
                'conflicts'         => $statusBefore['setup_conflicts'] ?? [],
                'details'           => $statusBefore,
            ];
        }

        // If already genuinely completed, return early without generating
        if ($statusBefore['status'] === 'completed') {
            return [
                'evaluated'         => true,
                'status'            => 'completed',
                'session_id'        => $sessionYearId,
                'reason'            => $statusBefore['reason'],
                'generated'         => 0,
                'skipped'           => $statusBefore['existing_invoices'],
                'execution_success' => true,
                'details'           => $statusBefore,
            ];
        }

        // Resolve actor User ID strictly via audit attribution policy (never arbitrary fallback)
        $resolvedUserId = $this->resolveActorUserId($userId);

        // Prerequisites satisfied! Automatically invoke 12-month annual generation
        $result = $this->generate([
            'session_year_id' => $sessionYearId,
            'fee_month'       => 'all',
            'user_id'         => $resolvedUserId,
        ]);

        $isExecutionSuccess = (bool)($result['success'] ?? false);

        if (!$isExecutionSuccess) {
            return [
                'evaluated'         => true,
                'status'            => 'failed',
                'session_id'        => $sessionYearId,
                'reason'            => $result['error'] ?? "Invoice generation execution failed.",
                'generated'         => 0,
                'skipped'           => 0,
                'execution_success' => false,
                'result'            => $result,
                'details'           => $statusBefore,
            ];
        }

        // Post-generation verification: Re-run getSessionGenerationStatus() to verify true coverage
        $statusAfter = $this->getSessionGenerationStatus($sessionYearId);
        $finalCoverageStatus = $statusAfter['status'];

        $generated = $result['total_generated'] ?? 0;
        $skipped = $result['total_skipped'] ?? 0;

        return [
            'evaluated'         => true,
            'status'            => $finalCoverageStatus,
            'session_id'        => $sessionYearId,
            'reason'            => $statusAfter['reason'],
            'generated'         => $generated,
            'skipped'           => $skipped,
            'execution_success' => true,
            'result'            => $result,
            'details'           => $statusAfter,
        ];
    }
}

