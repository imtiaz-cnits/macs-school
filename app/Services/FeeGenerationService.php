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

                if ($isAnnual) {
                    // Annual loop strictly generates recurring Monthly Fee setups
                    $setupQuery->where('fee_month', 'Monthly')
                        ->whereHas('category', function ($cq) {
                            $cq->whereRaw('LOWER(TRIM(name)) = ?', ['monthly fee']);
                        });
                } else {
                    // Single month or specific category setup
                    if (!empty($params['fee_category_id'])) {
                        $setupQuery->where('fee_category_id', (int)$params['fee_category_id']);
                    }

                    $setupQuery->where(function ($query) use ($currentMonth) {
                        $query->whereRaw('LOWER(TRIM(fee_month)) = ?', [strtolower($currentMonth)])
                              ->orWhere('fee_month', 'Monthly');
                    });
                }

                if (!empty($params['class_id'])) {
                    $setupQuery->where('class_id', (int)$params['class_id']);
                }

                if (!empty($params['branch_id'])) {
                    $setupQuery->where('branch_id', (int)$params['branch_id']);
                }

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
                    ])->get();

                    if ($students->isEmpty()) {
                        continue;
                    }

                    // 2. Filter Eligible Students via 3-Tier Model
                    $eligibleStudents = $students->filter(function ($student) use ($targetYear, $paddedMonth) {
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

        // 1. Fetch active Monthly Fee setups strictly for this session and category
        $monthlySetups = FeeSetup::where('session_year_id', $sessionYearId)
            ->where('status', 'Active')
            ->where('fee_month', 'Monthly')
            ->whereHas('category', function ($q) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', ['monthly fee']);
            })
            ->with(['category', 'schoolClass', 'branch'])
            ->get();

        $monthlySetupsCount = $monthlySetups->count();
        $totalSessionStudentsCount = Student::where('session_year_id', $sessionYearId)->count();

        if ($monthlySetupsCount === 0 && $totalSessionStudentsCount === 0) {
            return [
                'session_id'            => $sessionYearId,
                'session_name'          => $session->session_name,
                'year'                  => $targetYear,
                'is_ready'              => false,
                'status'                => 'pending_both',
                'active_students_count' => 0,
                'monthly_setups_count'  => 0,
                'expected_invoices'     => 0,
                'existing_invoices'     => 0,
                'reason'                => "No students and no active Monthly Fee setups configured for session '{$session->session_name}'.",
            ];
        }

        if ($monthlySetupsCount === 0) {
            return [
                'session_id'            => $sessionYearId,
                'session_name'          => $session->session_name,
                'year'                  => $targetYear,
                'is_ready'              => false,
                'status'                => 'pending_fee_setup',
                'active_students_count' => $totalSessionStudentsCount,
                'monthly_setups_count'  => 0,
                'expected_invoices'     => 0,
                'existing_invoices'     => 0,
                'reason'                => "No active Monthly Fee setups configured for session '{$session->session_name}'.",
            ];
        }

        // 2. Fetch candidate students matching setup class and branch scopes
        $candidateClasses = $monthlySetups->pluck('class_id')->unique()->toArray();
        $candidateBranches = $monthlySetups->pluck('branch_id')->unique()->toArray();

        $candidateStudents = Student::where('session_year_id', $sessionYearId)
            ->whereIn('class_id', $candidateClasses)
            ->whereIn('branch_id', $candidateBranches)
            ->get();

        if ($candidateStudents->isEmpty()) {
            return [
                'session_id'            => $sessionYearId,
                'session_name'          => $session->session_name,
                'year'                  => $targetYear,
                'is_ready'              => false,
                'status'                => 'pending_students',
                'active_students_count' => 0,
                'monthly_setups_count'  => $monthlySetupsCount,
                'expected_invoices'     => 0,
                'existing_invoices'     => 0,
                'reason'                => "No students enrolled in the classes and branches matching active Monthly Fee setups for session '{$session->session_name}'.",
            ];
        }

        // 3. Fetch existing invoices strictly for these Monthly Fee setups and target billing year
        $setupIds = $monthlySetups->pluck('id')->toArray();
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

        foreach ($monthlySetups as $feeSetup) {
            $scopeKey = $feeSetup->branch_id . '_' . $feeSetup->class_id;
            $studentsInScope = $studentsByScope->get($scopeKey, collect());

            foreach ($studentsInScope as $student) {
                if (($student->status ?? 'Active') === 'Active') {
                    $activeMatchedStudentIds[$student->id] = true;
                }

                for ($m = 1; $m <= 12; $m++) {
                    $paddedMonth = str_pad((string)$m, 2, '0', STR_PAD_LEFT);

                    if ($this->isStudentEligibleForMonth($student, (int)$targetYear, $paddedMonth)) {
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
        if ($expectedCount === 0) {
            $status = 'pending_students';
            $reason = "No eligible students found for the 12 calendar months of session '{$session->session_name}'.";
        } elseif ($totalExistingCount === 0) {
            $status = 'ready';
            $reason = "Prerequisites met. Ready for annual invoice generation (Expected: {$expectedCount} eligible invoices across {$monthlySetupsCount} monthly setup(s)).";
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
            $reason = "Partial annual coverage: {$matchedExistingCount} of {$expectedCount} invoices present{$cancelledNote}. Retry will generate remaining {$missingKeysCount} missing invoice(s).";
        } elseif ($missingKeysCount === 0 && $extraKeysCount === 0 && $duplicateInvoicesCount === 0 && $invalidMonthInvoicesCount === 0 && $totalActiveExistingCount === $expectedCount) {
            $status = 'completed';
            $reason = "Full annual invoice coverage verified: All {$expectedCount} expected Monthly Fee invoices are present.";
        } else {
            $status = 'partial';
            $reason = "Partial annual coverage: {$matchedExistingCount} of {$expectedCount} invoices present.";
        }

        return [
            'session_id'              => $sessionYearId,
            'session_name'            => $session->session_name,
            'year'                    => $targetYear,
            'is_ready'                => ($eligibleMatchedStudentsCount > 0 && $monthlySetupsCount > 0 && $expectedCount > 0),
            'status'                  => $status,
            'active_students_count'   => $activeMatchedStudentsCount,
            'eligible_students_count' => $eligibleMatchedStudentsCount,
            'monthly_setups_count'    => $monthlySetupsCount,
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

        if (!$statusBefore['is_ready']) {
            return [
                'evaluated'         => false,
                'status'            => $statusBefore['status'],
                'session_id'        => $sessionYearId,
                'reason'            => $statusBefore['reason'],
                'generated'         => 0,
                'skipped'           => 0,
                'execution_success' => true,
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

