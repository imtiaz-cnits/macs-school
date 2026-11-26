<?php

namespace App\Services;

use App\Models\FeeAdjustmentLog;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Manages embedded Late Fine operations strictly on eligible unpaid Monthly Fee invoices.
 *
 * Architectural guarantees:
 * - Late fines are embedded directly on the Monthly Fee `fee_invoices` row (`fine_amount`,
 *   `fine_source`, `fine_reason`, `auto_fine_applied_at`, `auto_fine_waived_at`) — never
 *   as separate `Fine` invoice rows.
 * - `fee_invoices.net_amount` remains 100% pure tuition (`amount - discount`).
 * - `fee_invoices.due_amount` equals `(net_amount + fine_amount) - paid_amount`.
 * - Strictly restricted to unpaid `Monthly Fee` invoices with `net_amount > 0` and zero active payments.
 */
class LateFineService
{
    public const SOURCE_MANUAL       = 'manual';
    public const SOURCE_AUTO         = 'auto';
    public const AUTO_FINE_AMOUNT    = '20.00';
    public const AUTO_FINE_MIN_YEAR  = 2027;
    public const AUTO_FINE_APPLY_DAY = 8;
    public const TIMEZONE            = 'Asia/Dhaka';
    public const AUTO_FINE_REASON    = 'Automatic monthly late fine (unpaid after 7th)';

    public function __construct(
        protected ?FinancialTransactionRunner $transactionRunner = null
    ) {
        $this->transactionRunner = $transactionRunner ?? app(FinancialTransactionRunner::class);
    }

    /**
     * Add or update a manual embedded Late Fine on an eligible unpaid `Monthly Fee` invoice.
     */
    public function applyManualLateFine(
        FeeInvoice $invoice,
        float|string $amount,
        string $reason,
        int $actorUserId
    ): FeeInvoice {
        $this->assertEmbeddedFineSchemaReady();

        $amountStr = $this->normalizePositiveFineAmount($amount);
        $cleanReason = $this->normalizeRequiredReason($reason);

        return $this->transactionRunner->runWithDeadlockRetry(function () use ($invoice, $amountStr, $cleanReason, $actorUserId): FeeInvoice {
            // Strict V8 Lock Order: Tier 1 Student -> Tier 2 FeeInvoice -> Tier 3 FeePayments ASC
            $lockedStudent = Student::where('id', (int) $invoice->student_id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedInvoice = FeeInvoice::with(['feeSetup.category', 'student'])
                ->where('id', (int) $invoice->id)
                ->where('student_id', (int) $lockedStudent->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $this->assertEligibleForEmbeddedLateFine($lockedInvoice, $lockedPayments, 'apply');

            $netStr = bcadd((string) $lockedInvoice->net_amount, '0.00', 2);
            $prevFine = $lockedInvoice->fineAmountDecimal();
            $prevDue = bcadd((string) $lockedInvoice->due_amount, '0.00', 2);
            $newDue = bcadd($netStr, $amountStr, 2);

            // Preserve any existing `auto_fine_applied_at` / `auto_fine_waived_at` timestamps untouched.
            $lockedInvoice->fine_amount = $amountStr;
            $lockedInvoice->fine_source = self::SOURCE_MANUAL;
            $lockedInvoice->fine_reason = $cleanReason;
            $lockedInvoice->due_amount = $newDue;
            $lockedInvoice->save();

            $fineDelta = bcsub($amountStr, $prevFine, 2);
            $absDelta = bccomp($fineDelta, '0.00', 2) < 0 ? bcmul($fineDelta, '-1', 2) : $fineDelta;
            $logMode = bccomp($prevFine, '0.00', 2) === 0
                ? 'increase'
                : (bccomp($fineDelta, '0.00', 2) > 0 ? 'increase' : (bccomp($fineDelta, '0.00', 2) < 0 ? 'decrease' : 'set_amount'));

            FeeAdjustmentLog::create([
                'fee_invoice_id'      => $lockedInvoice->id,
                'student_id'          => (int) $lockedStudent->id,
                'user_id'             => $actorUserId,
                'invoice_no'          => $lockedInvoice->invoice_no,
                'student_identity'    => $lockedStudent->student_identity ?? null,
                'mode'                => $logMode,
                'scope'               => 'single',
                'adjustment_amount'   => bccomp($absDelta, '0.00', 2) > 0 ? $absDelta : $amountStr,
                'previous_amount'     => bcadd((string) $lockedInvoice->amount, '0.00', 2),
                'previous_discount'   => bcadd((string) $lockedInvoice->discount, '0.00', 2),
                'previous_net_amount' => $netStr,
                'previous_due_amount' => $prevDue,
                'new_amount'          => bcadd((string) $lockedInvoice->amount, '0.00', 2),
                'new_discount'        => bcadd((string) $lockedInvoice->discount, '0.00', 2),
                'new_net_amount'      => $netStr,
                'new_due_amount'      => $newDue,
                'paid_amount'         => '0.00',
                'reason'              => mb_substr(
                    (bccomp($prevFine, '0.00', 2) > 0
                        ? "Manual Late Fine Updated (৳{$prevFine} -> ৳{$amountStr}) - {$cleanReason}"
                        : "Manual Late Fine (৳{$amountStr}) - {$cleanReason}"),
                    0,
                    255
                ),
            ]);

            return $lockedInvoice->fresh(['feeSetup.category', 'student']);
        });
    }

    /**
     * Waive an embedded Late Fine on an unpaid `Monthly Fee` invoice.
     *
     * Subtle Waiver Anti-Resurrection Rule:
     * - Stamps `auto_fine_waived_at = now('Asia/Dhaka')` ONLY when waiving an auto-applied fine
     *   (`fine_source === 'auto'` or `auto_fine_applied_at !== null`) OR when waiving on/after
     *   the 8th day of the invoice's billing month (`YYYY-MM-08`).
     * - If an admin simply undoes an accidental manual fine BEFORE the 8th, `auto_fine_waived_at`
     *   remains NULL so Step 3B's 8th-of-month automatic late fine is not prematurely disabled.
     */
    public function waiveFine(
        FeeInvoice $invoice,
        string $reason,
        int $actorUserId,
        ?Carbon $asOf = null
    ): FeeInvoice {
        $this->assertEmbeddedFineSchemaReady();

        $cleanReason = $this->normalizeRequiredReason($reason);

        return $this->transactionRunner->runWithDeadlockRetry(function () use ($invoice, $cleanReason, $actorUserId, $asOf): FeeInvoice {
            // Strict V8 Lock Order: Tier 1 Student -> Tier 2 FeeInvoice -> Tier 3 FeePayments ASC
            $lockedStudent = Student::where('id', (int) $invoice->student_id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedInvoice = FeeInvoice::with(['feeSetup.category', 'student'])
                ->where('id', (int) $invoice->id)
                ->where('student_id', (int) $lockedStudent->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $this->assertEligibleForEmbeddedLateFine($lockedInvoice, $lockedPayments, 'waive');

            if (!$lockedInvoice->hasLateFine()) {
                throw ValidationException::withMessages([
                    'invoice_id' => ["Invoice ({$lockedInvoice->invoice_no}) does not have an active Late Fine to waive."],
                ]);
            }

            $netStr = bcadd((string) $lockedInvoice->net_amount, '0.00', 2);
            $prevFine = $lockedInvoice->fineAmountDecimal();
            $prevDue = bcadd((string) $lockedInvoice->due_amount, '0.00', 2);

            $nowDhaka = ($asOf ?? Carbon::now('Asia/Dhaka'))->copy()->timezone('Asia/Dhaka');
            $billingPeriod = (string) $lockedInvoice->billing_period;
            $wasAutoFine = ($lockedInvoice->fine_source === self::SOURCE_AUTO)
                || ($lockedInvoice->auto_fine_applied_at !== null);
            $isOnOrAfterEighth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $billingPeriod) === 1
                && $nowDhaka->toDateString() >= ($billingPeriod . '-08');

            $lockedInvoice->fine_amount = '0.00';
            $lockedInvoice->fine_source = null;
            $lockedInvoice->fine_reason = null;
            $lockedInvoice->due_amount = $netStr;

            if ($wasAutoFine || $isOnOrAfterEighth) {
                $lockedInvoice->auto_fine_waived_at = $nowDhaka;
            }

            $lockedInvoice->save();

            FeeAdjustmentLog::create([
                'fee_invoice_id'      => $lockedInvoice->id,
                'student_id'          => (int) $lockedStudent->id,
                'user_id'             => $actorUserId,
                'invoice_no'          => $lockedInvoice->invoice_no,
                'student_identity'    => $lockedStudent->student_identity ?? null,
                'mode'                => 'decrease',
                'scope'               => 'single',
                'adjustment_amount'   => $prevFine,
                'previous_amount'     => bcadd((string) $lockedInvoice->amount, '0.00', 2),
                'previous_discount'   => bcadd((string) $lockedInvoice->discount, '0.00', 2),
                'previous_net_amount' => $netStr,
                'previous_due_amount' => $prevDue,
                'new_amount'          => bcadd((string) $lockedInvoice->amount, '0.00', 2),
                'new_discount'        => bcadd((string) $lockedInvoice->discount, '0.00', 2),
                'new_net_amount'      => $netStr,
                'new_due_amount'      => $netStr,
                'paid_amount'         => '0.00',
                'reason'              => mb_substr("Late Fine Waived (৳{$prevFine}) - {$cleanReason}", 0, 255),
            ]);

            return $lockedInvoice->fresh(['feeSetup.category', 'student']);
        });
    }

    /**
     * Apply the automatic ৳20.00 monthly late fine to eligible unpaid `Monthly Fee` invoices
     * strictly starting January 2027, after the 7th of each billing month (`Asia/Dhaka`).
     *
     * Safety & Business Invariants:
     * 1. Double-Gate 2027 Lock:
     *    - Gate 1 (Calendar Clock): Completely inactive before `2027-01-01` (`Asia/Dhaka`).
     *    - Gate 2 (Billing Month + Day-8 Cutoff): Only invoices with billing period `>= '2027-01'`
     *      and `$asOf->toDateString() >= "{$billingPeriod}-08"` are eligible.
     * 2. Mid-Month Admission & Late-Billing Exemption:
     *    - Skips invoices where student's effective admission date (`COALESCE(admission_date, DATE(created_at))`)
     *      or invoice creation date (`DATE(fee_invoices.created_at)`) in `Asia/Dhaka` is `> "{$billingPeriod}-07"`.
     * 3. Single-Month Isolation, Idempotency & Coexistence:
     *    - Embedded directly on that billing month's `Monthly Fee` row (`fine_amount = '20.00'`, `fine_source = 'auto'`).
     *    - Never overwrites/stacks on an existing fine (`fine_amount > 0` or `fine_source !== null`).
     *    - Never re-applies when `auto_fine_applied_at` or `auto_fine_waived_at` is set.
     * 4. Strict V8 Lock Re-Verification (`!$dryRun`):
     *    - Re-verifies every predicate under Tier 1 Student -> Tier 2 FeeInvoice -> Tier 3 FeePayment ASC locks.
     *    - Dry-run mode is 100% read-only with zero database mutations.
     *
     * @return array{
     *     as_of_date: string,
     *     as_of_timestamp: string,
     *     timezone: string,
     *     dry_run: bool,
     *     skipped_reason: ?string,
     *     eligible_count: int,
     *     applied_count: int,
     *     skipped_counts: array<string, int>,
     *     total_fine_amount: string,
     *     sample_invoices: array<int, array<string, mixed>>
     * }
     */
    public function applyAutomaticMonthlyLateFines(?Carbon $asOf = null, bool $dryRun = false): array
    {
        $this->assertEmbeddedFineSchemaReady();

        $asOfDhaka = ($asOf ? $asOf->copy() : Carbon::now(self::TIMEZONE))->timezone(self::TIMEZONE);
        $asOfDate = $asOfDhaka->toDateString();

        $summary = [
            'as_of_date'        => $asOfDate,
            'as_of_timestamp'   => $asOfDhaka->toIso8601String(),
            'timezone'          => self::TIMEZONE,
            'dry_run'           => $dryRun,
            'skipped_reason'    => null,
            'eligible_count'    => 0,
            'applied_count'     => 0,
            'skipped_counts'    => [
                'inactive_student'          => 0,
                'non_monthly_category'      => 0,
                'invalid_billing_period'    => 0,
                'pre_2027_invoice'          => 0,
                'before_day_8_cutoff'       => 0,
                'mid_month_admission'       => 0,
                'late_invoice_created'      => 0,
                'already_has_fine'          => 0,
                'already_auto_applied'      => 0,
                'auto_fine_waived'          => 0,
                'not_unpaid_or_has_payment' => 0,
                'zero_net_or_due'           => 0,
            ],
            'total_fine_amount' => '0.00',
            'sample_invoices'   => [],
        ];

        // Gate 1 (Calendar Clock): Strictly inactive before January 1, 2027 (Asia/Dhaka).
        if ((int) $asOfDhaka->format('Y') < self::AUTO_FINE_MIN_YEAR) {
            $summary['skipped_reason'] = 'pre_2027_calendar';

            return $summary;
        }

        $hasPaymentStatus = Schema::hasColumn('fee_payments', 'status');
        $paymentRelation = $hasPaymentStatus ? 'activePayments' : 'payments';

        // Uses composite index `idx_fi_auto_fine_scan (status, auto_fine_applied_at, auto_fine_waived_at)`.
        // Admission invoices (`admission_fee_structure_id IS NOT NULL`) are excluded at SQL level.
        FeeInvoice::query()
            ->with(['student', 'feeSetup.category', $paymentRelation])
            ->whereNull('admission_fee_structure_id')
            ->where('status', 'Unpaid')
            ->whereNull('auto_fine_applied_at')
            ->whereNull('auto_fine_waived_at')
            ->orderBy('id', 'asc')
            ->chunkById(200, function ($candidates) use (&$summary, $asOfDhaka, $dryRun, $hasPaymentStatus, $paymentRelation): void {
                foreach ($candidates as $candidate) {
                    $payments = $candidate->getRelation($paymentRelation);
                    $evaluation = $this->evaluateAutomaticLateFineEligibility(
                        $candidate,
                        $candidate->student,
                        $payments,
                        $asOfDhaka,
                        $hasPaymentStatus
                    );

                    if (!$evaluation['eligible']) {
                        $reason = $evaluation['reason'] ?? 'other';
                        $summary['skipped_counts'][$reason] = ($summary['skipped_counts'][$reason] ?? 0) + 1;
                        continue;
                    }

                    if ($dryRun) {
                        $netStr = bcadd((string) $candidate->net_amount, '0.00', 2);
                        $newDue = bcadd($netStr, self::AUTO_FINE_AMOUNT, 2);

                        $summary['eligible_count']++;
                        $summary['total_fine_amount'] = bcadd($summary['total_fine_amount'], self::AUTO_FINE_AMOUNT, 2);

                        if (count($summary['sample_invoices']) < 50) {
                            $summary['sample_invoices'][] = [
                                'invoice_id'     => (int) $candidate->id,
                                'invoice_no'     => (string) $candidate->invoice_no,
                                'billing_period' => $evaluation['billing_period'],
                                'student_id'     => (int) $candidate->student_id,
                                'student_name'   => (string) ($candidate->student?->student_name ?? ''),
                                'net_amount'     => $netStr,
                                'fine_amount'    => self::AUTO_FINE_AMOUNT,
                                'new_due_amount' => $newDue,
                            ];
                        }

                        continue;
                    }

                    $candidateId = (int) $candidate->id;
                    $candidateStudentId = (int) $candidate->student_id;

                    $outcome = $this->transactionRunner->runWithDeadlockRetry(
                        function () use ($candidateId, $candidateStudentId, $asOfDhaka, $hasPaymentStatus): array {
                            // Strict V8 Lock Order: Tier 1 Student -> Tier 2 FeeInvoice -> Tier 3 FeePayments ASC
                            $lockedStudent = Student::where('id', $candidateStudentId)
                                ->orderBy('id', 'asc')
                                ->lockForUpdate()
                                ->first();

                            $lockedInvoice = FeeInvoice::where('id', $candidateId)
                                ->where('student_id', $candidateStudentId)
                                ->lockForUpdate()
                                ->first();

                            if (!$lockedInvoice) {
                                return ['applied' => false, 'reason' => 'not_unpaid_or_has_payment'];
                            }

                            if (!$lockedInvoice->isAdmissionInvoice()) {
                                $lockedInvoice->load(['feeSetup.category']);
                            }

                            $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
                                ->orderBy('id', 'asc')
                                ->lockForUpdate()
                                ->get();

                            $recheck = $this->evaluateAutomaticLateFineEligibility(
                                $lockedInvoice,
                                $lockedStudent,
                                $lockedPayments,
                                $asOfDhaka,
                                $hasPaymentStatus
                            );

                            if (!$recheck['eligible']) {
                                return ['applied' => false, 'reason' => $recheck['reason'] ?? 'other'];
                            }

                            $netStr = bcadd((string) $lockedInvoice->net_amount, '0.00', 2);
                            $prevDue = bcadd((string) $lockedInvoice->due_amount, '0.00', 2);
                            $fineStr = self::AUTO_FINE_AMOUNT;
                            $newDue = bcadd($netStr, $fineStr, 2);

                            $lockedInvoice->fine_amount = $fineStr;
                            $lockedInvoice->fine_source = self::SOURCE_AUTO;
                            $lockedInvoice->fine_reason = self::AUTO_FINE_REASON;
                            $lockedInvoice->auto_fine_applied_at = $asOfDhaka;
                            $lockedInvoice->due_amount = $newDue;
                            $lockedInvoice->save();

                            FeeAdjustmentLog::create([
                                'fee_invoice_id'      => $lockedInvoice->id,
                                'student_id'          => (int) $lockedStudent->id,
                                'user_id'             => null,
                                'invoice_no'          => $lockedInvoice->invoice_no,
                                'student_identity'    => $lockedStudent->student_identity ?? null,
                                'mode'                => 'increase',
                                'scope'               => 'single',
                                'adjustment_amount'   => $fineStr,
                                'previous_amount'     => bcadd((string) $lockedInvoice->amount, '0.00', 2),
                                'previous_discount'   => bcadd((string) $lockedInvoice->discount, '0.00', 2),
                                'previous_net_amount' => $netStr,
                                'previous_due_amount' => $prevDue,
                                'new_amount'          => bcadd((string) $lockedInvoice->amount, '0.00', 2),
                                'new_discount'        => bcadd((string) $lockedInvoice->discount, '0.00', 2),
                                'new_net_amount'      => $netStr,
                                'new_due_amount'      => $newDue,
                                'paid_amount'         => '0.00',
                                'reason'              => mb_substr(
                                    "Automatic Monthly Late Fine (৳{$fineStr}, {$recheck['billing_period']}) - unpaid after 7th",
                                    0,
                                    255
                                ),
                            ]);

                            return [
                                'applied'        => true,
                                'reason'         => null,
                                'billing_period' => $recheck['billing_period'],
                                'invoice_id'     => (int) $lockedInvoice->id,
                                'invoice_no'     => (string) $lockedInvoice->invoice_no,
                                'student_id'     => (int) $lockedStudent->id,
                                'student_name'   => (string) ($lockedStudent->student_name ?? ''),
                                'net_amount'     => $netStr,
                                'fine_amount'    => $fineStr,
                                'new_due_amount' => $newDue,
                            ];
                        }
                    );

                    if (!$outcome['applied']) {
                        $reason = $outcome['reason'] ?? 'other';
                        $summary['skipped_counts'][$reason] = ($summary['skipped_counts'][$reason] ?? 0) + 1;
                        continue;
                    }

                    $summary['eligible_count']++;
                    $summary['applied_count']++;
                    $summary['total_fine_amount'] = bcadd($summary['total_fine_amount'], self::AUTO_FINE_AMOUNT, 2);

                    if (count($summary['sample_invoices']) < 50) {
                        $summary['sample_invoices'][] = [
                            'invoice_id'     => $outcome['invoice_id'],
                            'invoice_no'     => $outcome['invoice_no'],
                            'billing_period' => $outcome['billing_period'],
                            'student_id'     => $outcome['student_id'],
                            'student_name'   => $outcome['student_name'],
                            'net_amount'     => $outcome['net_amount'],
                            'fine_amount'    => $outcome['fine_amount'],
                            'new_due_amount' => $outcome['new_due_amount'],
                        ];
                    }
                }
            });

        return $summary;
    }

    /**
     * Authoritative eligibility predicate shared between read-only candidate evaluation
     * and locked per-invoice V8 transaction re-verification.
     *
     * @param  \Illuminate\Support\Collection<int, FeePayment>  $payments
     * @return array{eligible: bool, reason: ?string, billing_period: ?string}
     */
    private function evaluateAutomaticLateFineEligibility(
        FeeInvoice $invoice,
        ?Student $student,
        $payments,
        Carbon $asOfDhaka,
        bool $hasPaymentStatus
    ): array {
        if (!$student || trim((string) $student->status) !== 'Active') {
            return ['eligible' => false, 'reason' => 'inactive_student', 'billing_period' => null];
        }

        if ($invoice->isAdmissionInvoice() || $invoice->isAdmissionInvoiceNumber() || !$invoice->isMonthlyTuitionInvoice()) {
            return ['eligible' => false, 'reason' => 'non_monthly_category', 'billing_period' => null];
        }

        $activePayments = $hasPaymentStatus
            ? $payments->filter(fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active')
            : $payments;
        $paidStr = bcadd((string) ($invoice->paid_amount ?? '0'), '0.00', 2);

        if ($invoice->status !== 'Unpaid' || bccomp($paidStr, '0.00', 2) > 0 || $activePayments->isNotEmpty()) {
            return ['eligible' => false, 'reason' => 'not_unpaid_or_has_payment', 'billing_period' => null];
        }

        $netStr = bcadd((string) ($invoice->net_amount ?? '0'), '0.00', 2);
        $dueStr = bcadd((string) ($invoice->due_amount ?? '0'), '0.00', 2);
        if (bccomp($netStr, '0.00', 2) <= 0 || bccomp($dueStr, '0.00', 2) <= 0) {
            return ['eligible' => false, 'reason' => 'zero_net_or_due', 'billing_period' => null];
        }

        if ($invoice->auto_fine_applied_at !== null) {
            return ['eligible' => false, 'reason' => 'already_auto_applied', 'billing_period' => null];
        }

        if ($invoice->auto_fine_waived_at !== null) {
            return ['eligible' => false, 'reason' => 'auto_fine_waived', 'billing_period' => null];
        }

        if (bccomp($invoice->fineAmountDecimal(), '0.00', 2) > 0 || $invoice->fine_source !== null) {
            return ['eligible' => false, 'reason' => 'already_has_fine', 'billing_period' => null];
        }

        // Gate 2a: Canonical billing period must be >= 2027-01 (all 2026 invoices remain 100% immune forever).
        $billingPeriod = $this->resolveAutoFineBillingPeriod($invoice);
        if ($billingPeriod === null || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $billingPeriod) !== 1) {
            return ['eligible' => false, 'reason' => 'invalid_billing_period', 'billing_period' => null];
        }

        $minBillingPeriod = sprintf('%04d-01', self::AUTO_FINE_MIN_YEAR);
        if (strcmp($billingPeriod, $minBillingPeriod) < 0) {
            return ['eligible' => false, 'reason' => 'pre_2027_invoice', 'billing_period' => $billingPeriod];
        }

        // Gate 2b: Must be on or after the 8th day of the invoice's billing month in Asia/Dhaka.
        $applyFromDate = sprintf('%s-%02d', $billingPeriod, self::AUTO_FINE_APPLY_DAY);
        if (strcmp($asOfDhaka->toDateString(), $applyFromDate) < 0) {
            return ['eligible' => false, 'reason' => 'before_day_8_cutoff', 'billing_period' => $billingPeriod];
        }

        // Mid-month new admission & late-billing exemption (cutoff = YYYY-MM-07 Asia/Dhaka).
        $cutoffDate = sprintf('%s-%02d', $billingPeriod, self::AUTO_FINE_APPLY_DAY - 1);

        $effectiveAdmissionDate = !empty($student->admission_date)
            ? $this->resolveDhakaDate($student->admission_date)
            : $this->resolveDhakaDate($student->created_at);

        if ($effectiveAdmissionDate !== null && strcmp($effectiveAdmissionDate, $cutoffDate) > 0) {
            return ['eligible' => false, 'reason' => 'mid_month_admission', 'billing_period' => $billingPeriod];
        }

        $invoiceCreatedDate = $this->resolveDhakaDate($invoice->created_at);
        if ($invoiceCreatedDate !== null && strcmp($invoiceCreatedDate, $cutoffDate) > 0) {
            return ['eligible' => false, 'reason' => 'late_invoice_created', 'billing_period' => $billingPeriod];
        }

        return [
            'eligible'       => true,
            'reason'         => null,
            'billing_period' => $billingPeriod,
        ];
    }

    /**
     * Resolve canonical billing period (`YYYY-MM`) via `invoice_no`, falling back to `due_date`
     * strictly for non-dated invoice numbers. Never guesses the current calendar month.
     */
    private function resolveAutoFineBillingPeriod(FeeInvoice $invoice): ?string
    {
        $parsed = FeeInvoice::extractYearMonthFromInvoiceNo($invoice->invoice_no);
        if ($parsed !== null) {
            return $parsed['year'] . '-' . $parsed['month'];
        }

        if (!empty($invoice->due_date)) {
            try {
                return Carbon::parse((string) $invoice->due_date, self::TIMEZONE)
                    ->timezone(self::TIMEZONE)
                    ->format('Y-m');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function resolveDhakaDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->timezone(self::TIMEZONE)->toDateString();
            }

            $raw = trim((string) $value);
            if ($raw === '') {
                return null;
            }

            return Carbon::parse($raw, self::TIMEZONE)->timezone(self::TIMEZONE)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Strict shared eligibility validation for embedded Late Fine operations (`apply` / `waive`).
     *
     * @param  \Illuminate\Support\Collection<int, FeePayment>  $lockedPayments
     */
    private function assertEligibleForEmbeddedLateFine(
        FeeInvoice $lockedInvoice,
        $lockedPayments,
        string $action
    ): void {
        if ($lockedInvoice->isCancelled()) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Cannot {$action} a Late Fine on a cancelled invoice ({$lockedInvoice->invoice_no})."],
            ]);
        }

        if ($lockedInvoice->isAdmissionInvoice()) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Late Fine is strictly restricted to Monthly Fee invoices and cannot be applied to Admission invoices ({$lockedInvoice->invoice_no})."],
            ]);
        }

        if (!$lockedInvoice->isMonthlyTuitionInvoice()) {
            $catName = $lockedInvoice->feeSetup?->category?->name ?? 'Non-Monthly Fee';
            throw ValidationException::withMessages([
                'invoice_id' => ["Late Fine can only be applied to Monthly Fee invoices, not {$catName} ({$lockedInvoice->invoice_no})."],
            ]);
        }

        $netStr = bcadd((string) ($lockedInvoice->net_amount ?? '0'), '0.00', 2);
        if (bccomp($netStr, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Cannot {$action} a Late Fine on a zero-payable/waived invoice ({$lockedInvoice->invoice_no})."],
            ]);
        }

        $hasPaymentStatus = Schema::hasColumn('fee_payments', 'status');
        $activePayments = $hasPaymentStatus
            ? $lockedPayments->filter(fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active')
            : $lockedPayments;
        $paidStr = bcadd((string) ($lockedInvoice->paid_amount ?? '0'), '0.00', 2);

        if ($lockedInvoice->status !== 'Unpaid' || bccomp($paidStr, '0.00', 2) > 0 || $activePayments->isNotEmpty()) {
            $verb = $action === 'waive' ? 'waive Late Fine on' : 'apply Late Fine to';
            throw ValidationException::withMessages([
                'invoice_id' => [
                    "Cannot {$verb} invoice ({$lockedInvoice->invoice_no}) because payment has already been collected (status: {$lockedInvoice->status}). Please reverse the payment receipt first if needed.",
                ],
            ]);
        }
    }

    private function assertEmbeddedFineSchemaReady(): void
    {
        if (!Schema::hasColumns('fee_invoices', [
            'fine_amount',
            'fine_source',
            'fine_reason',
            'auto_fine_applied_at',
            'auto_fine_waived_at',
        ])) {
            throw ValidationException::withMessages([
                'invoice_id' => ['Embedded late fine database migration is pending. Please run migrations first.'],
            ]);
        }
    }

    private function normalizePositiveFineAmount(float|string $amount): string
    {
        if (!is_numeric($amount)) {
            throw ValidationException::withMessages([
                'amount' => ['Fine amount must be a valid number.'],
            ]);
        }

        $formatted = number_format((float) $amount, 2, '.', '');
        if (bccomp($formatted, '1.00', 2) < 0 || bccomp($formatted, '10000.00', 2) > 0) {
            throw ValidationException::withMessages([
                'amount' => ['Fine amount must be between ৳1.00 and ৳10,000.00.'],
            ]);
        }

        return $formatted;
    }

    private function normalizeRequiredReason(string $reason): string
    {
        $clean = trim($reason);
        if (mb_strlen($clean) < 3 || mb_strlen($clean) > 255) {
            throw ValidationException::withMessages([
                'reason' => ['A reason between 3 and 255 characters is required.'],
            ]);
        }

        return $clean;
    }
}
