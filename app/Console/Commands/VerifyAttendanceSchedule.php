<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\StudentAttendanceSchedule;
use App\Services\ResolvedStudentSchedule;
use App\Services\StudentAttendanceScheduleResolver;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VerifyAttendanceSchedule extends Command
{
    private const TIMEZONE = 'Asia/Dhaka';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:verify-schedule
        {--date= : Target date in YYYY-MM-DD format (defaults to today in Asia/Dhaka)}
        {--branch= : Optional branch ID to verify branch-scoped schedules}
        {--check-device : Perform a read-only ZKTeco K60 reachability and clock-drift check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Strictly read-only diagnostic check for student attendance schedules, class coverage, snapshots, and optional ZKTeco K60 clock alignment';

    /**
     * Execute the console command.
     */
    public function handle(StudentAttendanceScheduleResolver $resolver): int
    {
        $dateOption = $this->option('date');
        $branchOption = $this->option('branch');
        $checkDevice = (bool) $this->option('check-device');

        // Validate target date option
        if ($dateOption !== null && trim((string) $dateOption) !== '') {
            $rawDate = trim((string) $dateOption);
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $rawDate, self::TIMEZONE);
                if (!$parsed || $parsed->format('Y-m-d') !== $rawDate) {
                    $this->error("Invalid --date value '{$rawDate}'. Expected strict YYYY-MM-DD format (e.g. 2026-12-01).");
                    return self::FAILURE;
                }
                $targetDate = $rawDate;
            } catch (\Throwable) {
                $this->error("Invalid --date value '{$rawDate}'. Expected strict YYYY-MM-DD format (e.g. 2026-12-01).");
                return self::FAILURE;
            }
        } else {
            $targetDate = Carbon::today(self::TIMEZONE)->toDateString();
        }

        // Validate optional branch option
        $branchId = null;
        $branchLabel = 'All Branches (Global)';
        if ($branchOption !== null && trim((string) $branchOption) !== '') {
            if (!ctype_digit(trim((string) $branchOption))) {
                $this->error("Invalid --branch value '{$branchOption}'. Must be a valid numeric branch ID.");
                return self::FAILURE;
            }
            $branchId = (int) $branchOption;
            $branchModel = Branch::find($branchId);
            if (!$branchModel) {
                $this->error("Branch ID {$branchId} was not found in the branches table.");
                return self::FAILURE;
            }
            $branchLabel = "{$branchModel->branch_name} (ID: {$branchId})";
        }

        $criticalIssues = [];
        $warnings = [];
        $recommendations = [];

        $this->info('====================================================================');
        $this->info('   MACS SCHOOL - STUDENT ATTENDANCE SCHEDULE DIAGNOSTIC (READ-ONLY) ');
        $this->info('====================================================================');

        // -----------------------------------------------------------------
        // 1. Environment & Timezone Check
        // -----------------------------------------------------------------
        $appEnv = (string) config('app.env', 'unknown');
        $appTimezone = (string) config('app.timezone', 'UTC');
        $phpTimezone = date_default_timezone_get();
        $dhakaNow = Carbon::now(self::TIMEZONE)->format('Y-m-d H:i:s');

        $this->newLine();
        $this->info('[1/5] Environment & Timezone Check');
        $this->line("  APP_ENV:             {$appEnv}");
        $this->line("  config(app.timezone):{$appTimezone}");
        $this->line("  PHP default timezone:{$phpTimezone}");
        $this->line("  Current Asia/Dhaka:  {$dhakaNow}");
        $this->line("  Target Audit Date:   {$targetDate}");
        $this->line("  Branch Scope:        {$branchLabel}");

        if ($appTimezone !== self::TIMEZONE) {
            $warnings[] = "config('app.timezone') is '{$appTimezone}' instead of 'Asia/Dhaka'.";
            $recommendations[] = "Set APP_TIMEZONE=Asia/Dhaka in .env and run: php artisan config:cache";
            $this->warn("  [WARN] config('app.timezone') is '{$appTimezone}'; expected 'Asia/Dhaka'.");
        } else {
            $this->info('  [OK] Application timezone is configured to Asia/Dhaka.');
        }

        if ($phpTimezone !== self::TIMEZONE) {
            $warnings[] = "PHP date_default_timezone_get() is '{$phpTimezone}' instead of 'Asia/Dhaka'.";
        }

        // -----------------------------------------------------------------
        // 2. Schedule Configuration Integrity
        // -----------------------------------------------------------------
        $this->newLine();
        $this->info('[2/5] Schedule Configuration Integrity');

        $tablesAvailable = $resolver->scheduleTablesAvailable();
        if (!$tablesAvailable) {
            $criticalIssues[] = 'Student attendance schedule tables are missing from the database.';
            $recommendations[] = 'Run: php artisan migrate --force && php artisan db:seed --class=StudentAttendanceScheduleSeeder --force';
            $this->error('  [FAIL] Required schedule tables do not exist.');
        } else {
            // Check default Normal School Time
            $defaultNormal = StudentAttendanceSchedule::defaultNormal()->with('slots')->first();
            if (!$defaultNormal) {
                $criticalIssues[] = 'Active default Normal School Time schedule is missing.';
                $recommendations[] = 'Run: php artisan db:seed --class=StudentAttendanceScheduleSeeder --force';
                $this->error('  [FAIL] Active default Normal School Time schedule NOT found.');
            } else {
                $this->info("  [OK] Default Normal Schedule: '{$defaultNormal->name}' (ID: {$defaultNormal->id}, Slots: {$defaultNormal->slots->count()}).");
            }

            // Check System Exam Template
            $examTemplate = StudentAttendanceSchedule::templates()->with('slots')->first();
            if (!$examTemplate) {
                $warnings[] = 'System Standard Exam Time Template (is_template = true) is missing.';
                $recommendations[] = 'Run: php artisan db:seed --class=StudentAttendanceScheduleSeeder --force';
                $this->warn('  [WARN] Standard Exam Time Template NOT found.');
            } elseif ($examTemplate->is_active) {
                $warnings[] = "System Exam Template '{$examTemplate->name}' is marked is_active = true (templates must stay inactive).";
                $this->warn("  [WARN] Standard Exam Time Template '{$examTemplate->name}' has is_active = true.");
            } else {
                $this->info("  [OK] System Exam Template: '{$examTemplate->name}' (inactive template, Slots: {$examTemplate->slots->count()}).");
            }

            // Active / overlapping Exam Campaigns matching target date (& branch)
            $activeExamCampaigns = StudentAttendanceSchedule::query()
                ->with(['branch', 'slots'])
                ->where('mode', StudentAttendanceSchedule::MODE_EXAM)
                ->where('is_active', true)
                ->where('is_template', false)
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->whereDate('start_date', '<=', $targetDate)
                ->whereDate('end_date', '>=', $targetDate)
                ->forBranchOrGlobal($branchId)
                ->orderByRaw('CASE WHEN branch_id IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('priority')
                ->orderByDesc('id')
                ->get();

            if ($activeExamCampaigns->isEmpty()) {
                $this->line("  Active Exam Campaigns on {$targetDate}: None (Normal School Time applies).");
            } else {
                $this->line("  Active Exam Campaigns matching {$targetDate}: {$activeExamCampaigns->count()}");
                foreach ($activeExamCampaigns as $camp) {
                    $scopeText = $camp->branch ? $camp->branch->branch_name : 'All Branches';
                    $rangeText = ($camp->start_date ? $camp->start_date->format('Y-m-d') : '?')
                        . ' to '
                        . ($camp->end_date ? $camp->end_date->format('Y-m-d') : '?');
                    $this->line("    - {$camp->name} | Dates: {$rangeText} | Scope: {$scopeText} | Priority: {$camp->priority}");
                }
                if ($activeExamCampaigns->count() > 1) {
                    $this->comment('  [NOTE] Multiple exam campaigns overlap on this date; branch-specific & highest priority wins per class.');
                }
            }
        }

        // -----------------------------------------------------------------
        // 3. Effective Schedule Table & 100% Class Coverage Audit
        // -----------------------------------------------------------------
        $this->newLine();
        $this->info('[3/5] Effective Schedule Table & 100% Class Coverage Audit');

        $overview = $resolver->getEffectiveScheduleOverview($targetDate, $branchId);
        $activeModeLabel = strtoupper((string) ($overview['active_mode'] ?? 'normal'));
        $this->line("  Effective Mode on {$targetDate}: {$activeModeLabel}");

        $effectiveSlots = $overview['slots'] ?? [];
        if (empty($effectiveSlots)) {
            $this->warn('  No effective student schedule slots resolved for this date.');
        } else {
            $tableRows = [];
            foreach ($effectiveSlots as $slot) {
                $onStart = (string) ($slot['on_time_start'] ?? '');
                $onEnd = (string) ($slot['on_time_end'] ?? '');
                $lateEnd = (string) ($slot['late_end'] ?? '');
                $absentFrom = (string) ($slot['absent_from'] ?? '');
                $checkOutFrom = (string) ($slot['check_out_from'] ?? '');
                $slotName = (string) ($slot['slot_name'] ?? 'Unnamed Slot');

                // Validate chronological timing order on every active effective slot
                if (
                    $onStart === '' || $onEnd === '' || $lateEnd === '' || $absentFrom === '' || $checkOutFrom === ''
                    || !($onStart < $onEnd && $onEnd < $lateEnd && $lateEnd < $absentFrom && $absentFrom < $checkOutFrom)
                ) {
                    $criticalIssues[] = "Slot '{$slotName}' has invalid chronological timing order ({$onStart} < {$onEnd} < {$lateEnd} < {$absentFrom} < {$checkOutFrom}).";
                    $recommendations[] = "Fix timing boundaries for '{$slotName}' in /attendance/schedules.";
                }

                $lateStart = $this->computeLateStart($onEnd, $lateEnd);

                $tableRows[] = [
                    strtoupper((string) ($slot['mode'] ?? 'normal')),
                    $slotName,
                    (string) ($slot['shift_group'] ?? '-'),
                    (string) ($slot['class_summary'] ?? '-'),
                    $this->format12h($onStart) . ' - ' . $this->format12h($onEnd),
                    $this->format12h($lateStart) . ' - ' . $this->format12h($lateEnd),
                    $this->format12h($absentFrom) . '+',
                    $this->format12h($checkOutFrom) . '+',
                ];
            }

            $this->table(
                ['Mode', 'Slot Name', 'Shift Group', 'Classes', 'On-Time', 'Late', 'Absent From', 'Check-Out'],
                $tableRows
            );
        }

        // Audit every class in `classes`
        $allClasses = Classes::all();
        $mappedCount = 0;
        $unmappedDetails = [];

        foreach ($allClasses as $classModel) {
            $resolved = $resolver->resolveForClassOnDate((int) $classModel->id, $targetDate, $branchId);
            if ($resolved === null) {
                $unmappedDetails[] = "{$classModel->class_name} (ID: {$classModel->id}) — no schedule resolved";
            } elseif ($resolved->source === ResolvedStudentSchedule::SOURCE_LEGACY_SHIFT) {
                $unmappedDetails[] = "{$classModel->class_name} (ID: {$classModel->id}) — fell back to legacy_shift";
            } else {
                $mappedCount++;
            }
        }

        $totalClasses = $allClasses->count();
        $unmappedCount = count($unmappedDetails);
        $this->line("  Class Coverage Summary: {$mappedCount}/{$totalClasses} mapped | {$unmappedCount} unmapped / legacy fallback");

        if ($totalClasses === 0) {
            $warnings[] = 'No classes exist in the classes table to audit.';
            $this->warn('  [WARN] No classes found in database.');
        } elseif ($unmappedCount > 0) {
            $this->error('  [FAIL] Unmapped or legacy-fallback classes detected:');
            foreach ($unmappedDetails as $detail) {
                $this->error("    - {$detail}");
            }
            $criticalIssues[] = "Found {$unmappedCount} unmapped / legacy-fallback class(es): " . implode('; ', $unmappedDetails);
            $recommendations[] = 'Map all missing classes in the Attendance Schedules UI (/attendance/schedules) or re-run StudentAttendanceScheduleSeeder.';
        } else {
            $this->info("  [OK] 100% class coverage verified ({$mappedCount}/{$totalClasses} classes mapped to dynamic schedules).");
        }

        // -----------------------------------------------------------------
        // 4. Target Date Attendance Snapshot Audit (Read-Only)
        // -----------------------------------------------------------------
        $this->newLine();
        $this->info("[4/5] Attendance Snapshot Audit ({$targetDate})");

        if (!Schema::hasTable('attendances')) {
            $warnings[] = 'Attendances table is not available.';
            $this->warn('  [WARN] Attendances table not found.');
        } else {
            $hasSnapshotCol = Schema::hasColumn('attendances', 'schedule_mode');
            $attQuery = Attendance::where('attendance_date', $targetDate);
            if ($branchId !== null) {
                $attQuery->where('branch_id', $branchId);
            }

            $totalAttendanceRows = (clone $attQuery)->count();

            if (!$hasSnapshotCol) {
                $criticalIssues[] = 'attendances.schedule_mode snapshot column is missing.';
                $recommendations[] = 'Run: php artisan migrate --force';
                $this->error('  [FAIL] Snapshot column attendances.schedule_mode is missing.');
            } else {
                $normalSnapshotCount = (clone $attQuery)->where('schedule_mode', 'normal')->count();
                $examSnapshotCount = (clone $attQuery)->where('schedule_mode', 'exam')->count();
                $nullSnapshotCount = (clone $attQuery)->whereNull('schedule_mode')->count();
                $nullBiometricCount = (clone $attQuery)
                    ->whereNull('schedule_mode')
                    ->where('remarks', 'like', 'Card Swiped%')
                    ->count();

                $this->line("  Total Attendance Rows: {$totalAttendanceRows} (normal: {$normalSnapshotCount}, exam: {$examSnapshotCount}, NULL/legacy: {$nullSnapshotCount})");

                if ($nullBiometricCount > 0) {
                    $warnings[] = "Found {$nullBiometricCount} biometric attendance row(s) on {$targetDate} with schedule_mode = NULL (possible legacy fallback or un-restarted attendance:monitor worker).";
                    $recommendations[] = 'Restart the background `php artisan attendance:monitor` worker process and verify schedules.';
                    $this->warn("  [WARN] {$nullBiometricCount} biometric attendance row(s) on {$targetDate} have schedule_mode = NULL.");
                } else {
                    $this->info('  [OK] Zero legacy/NULL biometric attendance snapshots on target date.');
                }
            }
        }

        // -----------------------------------------------------------------
        // 5. Optional ZKTeco K60 Reachability & Clock Drift Check
        // -----------------------------------------------------------------
        $this->newLine();
        $this->info('[5/5] ZKTeco K60 Device & Clock Drift Check');

        if (!$checkDevice) {
            $this->comment('  Device check skipped (offline-safe default). Re-run with --check-device to inspect K60 reachability & clock drift.');
        } else {
            /** @var ZktecoService $zkService */
            $zkService = app(ZktecoService::class);
            $clock = $zkService->getDeviceClockInfo();

            $endpoint = ($clock['ip'] ?? 'unknown') . ':' . ($clock['port'] ?? 'unknown');
            $this->line('  Device Mode:   ' . strtoupper((string) ($clock['mode'] ?? 'unknown')));
            $this->line("  Endpoint:      {$endpoint}");
            $this->line('  Reachable:     ' . (!empty($clock['reachable']) ? 'YES' : 'NO'));
            $this->line('  Server Time:   ' . ($clock['server_time'] ?? Carbon::now(self::TIMEZONE)->format('Y-m-d H:i:s')) . ' (Asia/Dhaka)');
            $this->line('  Device Time:   ' . ($clock['device_time'] ?? 'N/A'));
            $this->line('  Clock Drift:   ' . ($clock['drift_seconds'] !== null ? ($clock['drift_seconds'] . 's') : 'N/A'));

            if (empty($clock['reachable'])) {
                $errDetail = $clock['error'] ?? 'Connection failed.';
                $criticalIssues[] = "ZKTeco K60 device at {$endpoint} is unreachable ({$errDetail}).";
                $recommendations[] = 'Check K60 power, network/port-forwarding (port 4370), and ZKTECO_IP / ZKTECO_MODE in .env.';
                $this->error("  [FAIL] Device unreachable: {$errDetail}");
            } elseif ($clock['drift_seconds'] === null) {
                $errDetail = $clock['error'] ?? 'Clock reading unavailable.';
                $warnings[] = "ZKTeco K60 connected, but clock could not be read ({$errDetail}).";
                $this->warn("  [WARN] {$errDetail}");
            } else {
                $drift = (int) $clock['drift_seconds'];
                if ($drift <= 60) {
                    $this->info("  [OK] K60 clock alignment is healthy (drift {$drift}s <= 60s).");
                } elseif ($drift <= 120) {
                    $warnings[] = "ZKTeco K60 clock drift is {$drift}s (61s-120s warning band).";
                    $recommendations[] = 'Sync the ZKTeco K60 clock via Menu -> System -> Date/Time before the next exam.';
                    $this->warn("  [WARN] Moderate K60 clock drift ({$drift}s). Recommend syncing K60 clock before next exam.");
                } else {
                    $criticalIssues[] = "Critical ZKTeco K60 clock drift detected ({$drift}s > 120s). Risk of inaccurate Present/Late/Absent boundaries.";
                    $recommendations[] = 'Immediately adjust K60 device time via Menu -> System -> Date/Time to match Asia/Dhaka server time.';
                    $this->error("  [FAIL] Critical K60 clock drift ({$drift}s > 120s).");
                }
            }
        }

        // -----------------------------------------------------------------
        // 6. Final Verdict & Exit Code
        // -----------------------------------------------------------------
        $this->newLine();
        $this->info('====================================================================');

        $uniqueRecommendations = array_values(array_unique($recommendations));

        if (!empty($criticalIssues)) {
            $this->error('  VERDICT: FAIL (' . count($criticalIssues) . ' critical issue(s), ' . count($warnings) . ' warning(s))');
            $this->info('====================================================================');
            foreach ($criticalIssues as $issue) {
                $this->error("  [CRITICAL] {$issue}");
            }
            foreach ($warnings as $warn) {
                $this->warn("  [WARNING]  {$warn}");
            }
            if (!empty($uniqueRecommendations)) {
                $this->newLine();
                $this->comment('Recommended Next Steps:');
                foreach ($uniqueRecommendations as $step) {
                    $this->line("  -> {$step}");
                }
            }

            return self::FAILURE;
        }

        if (!empty($warnings)) {
            $this->warn('  VERDICT: PASS WITH WARNINGS (' . count($warnings) . ' warning(s))');
            $this->info('====================================================================');
            foreach ($warnings as $warn) {
                $this->warn("  [WARNING] {$warn}");
            }
            if (!empty($uniqueRecommendations)) {
                $this->newLine();
                $this->comment('Recommended Next Steps:');
                foreach ($uniqueRecommendations as $step) {
                    $this->line("  -> {$step}");
                }
            }

            return self::SUCCESS;
        }

        $this->info('  VERDICT: PASS — All attendance schedules & class mappings are healthy.');
        $this->info('====================================================================');

        return self::SUCCESS;
    }

    private function computeLateStart(string $onTimeEnd, string $lateEnd): string
    {
        if ($onTimeEnd === '') {
            return $lateEnd;
        }

        return Carbon::parse($onTimeEnd, self::TIMEZONE)
            ->addMinute()
            ->setSecond(0)
            ->format('H:i:s');
    }

    private function format12h(string $timeStr): string
    {
        if (trim($timeStr) === '') {
            return '—';
        }

        return Carbon::parse($timeStr, self::TIMEZONE)->format('h:i A');
    }
}
