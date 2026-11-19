<?php

namespace App\Services;

use App\Models\AdmissionFeeStructure;
use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\Branch;
use App\Models\FeeAdjustmentLog;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Generates atomic, snapshot-immutable multi-head Admission Fee invoices using the approved
 * V8 5-tier global lock hierarchy (`Student ASC -> Invoice ASC -> ...`).
 */
class AdmissionInvoiceService
{
    public function __construct(
        protected FinancialTransactionRunner $transactionRunner,
        protected AdmissionFeeStructureService $structureService
    ) {
    }

    /**
     * Generate an Admission Fee invoice inside its own outer transaction with bounded deadlock retries.
     *
     * @param  array<string, mixed>  $options  Optional keys: `discount_type`, `discount_value`, `due_date`, `issue_date`, `fallback_to_legacy`
     * @return array{success: bool, created: bool, legacy: bool, invoice: ?FeeInvoice, message: string}
     *
     * @throws AuthorizationException|ValidationException
     */
    public function generateForStudent(Student $student, array $options = [], ?int $actorUserId = null): array
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->generateForStudentWithinTransaction($student, $options, $actorUserId)
        );
    }

    /**
     * Generate an Admission Fee invoice inside an already-open application transaction
     * (used by composite orchestrators such as `StudentController::store()`).
     *
     * @param  array<string, mixed>  $options
     * @return array{success: bool, created: bool, legacy: bool, invoice: ?FeeInvoice, message: string}
     *
     * @throws AuthorizationException|ValidationException
     */
    public function generateForStudentWithinTransaction(
        Student $student,
        array $options = [],
        ?int $actorUserId = null
    ): array {
        $this->transactionRunner->assertInTransaction('AdmissionInvoiceService::generateForStudentWithinTransaction');

        [$discountType, $discountValue, $hasNonZeroDiscount] = self::normalizeAndValidateDiscountInputs($options);
        $resolvedActorId = $this->resolveAndAuthorizeActor($actorUserId, $hasNonZeroDiscount);

        // Tier 1: Lock the Student row FIRST.
        $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->firstOrFail();

        if (
            empty($lockedStudent->branch_id)
            || empty($lockedStudent->class_id)
            || empty($lockedStudent->session_year_id)
        ) {
            throw ValidationException::withMessages([
                'student_id' => ['Student record is missing required branch, class, or session assignment.'],
            ]);
        }

        if (($lockedStudent->status ?? 'Active') === 'Inactive') {
            throw ValidationException::withMessages([
                'student_id' => ['Cannot generate an Admission Fee invoice for an Inactive student.'],
            ]);
        }

        $fallbackToLegacy = (bool) ($options['fallback_to_legacy'] ?? false);

        // Graceful compatibility if Phase 2 migrations are still pending on an environment.
        if (
            !Schema::hasTable('admission_fee_structures')
            || !Schema::hasTable('admission_invoice_head_allocations')
            || !Schema::hasColumn('fee_invoices', 'admission_fee_structure_id')
        ) {
            return $this->handleMissingStructureOrPendingSchema(
                $lockedStudent,
                $resolvedActorId,
                $fallbackToLegacy,
                $hasNonZeroDiscount,
                'Admission structure tables are not migrated yet.'
            );
        }

        // Tier 2: Lock all existing invoices for this student in deterministic ID ASC order.
        $lockedInvoices = FeeInvoice::where('student_id', $lockedStudent->id)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        // Load feeSetup.category strictly ONLY for legacy setup invoices (`fee_setup_id !== null`)
        // to avoid triggering HistoricalSnapshotCorruptException on incomplete rows.
        $legacyInvoices = $lockedInvoices->filter(fn (FeeInvoice $inv): bool => !is_null($inv->fee_setup_id));
        if ($legacyInvoices->isNotEmpty()) {
            $legacyInvoices->load('feeSetup.category');
        }

        $activeStructure = $this->structureService->resolveActiveStructure(
            (int) $lockedStudent->branch_id,
            (int) $lockedStudent->class_id,
            (int) $lockedStudent->session_year_id,
            lockForUpdate: true
        );

        if (!$activeStructure) {
            return $this->handleMissingStructureOrPendingSchema(
                $lockedStudent,
                $resolvedActorId,
                $fallbackToLegacy,
                $hasNonZeroDiscount,
                "No active Admission Fee Structure found for Branch ID {$lockedStudent->branch_id}, Class ID {$lockedStudent->class_id}, Session ID {$lockedStudent->session_year_id}."
            );
        }

        $this->assertNoDuplicateActiveAdmissionInvoice($lockedStudent, $lockedInvoices);
        $this->assertNoActiveLegacyAdmissionOverlap($lockedStudent, $legacyInvoices);
        $this->assertNoMonthlyFirstTuitionConflict($lockedStudent, $activeStructure, $legacyInvoices);

        $calculation = $this->calculateAllocations($activeStructure, $discountType, $discountValue);

        $branchName = Branch::whereKey($lockedStudent->branch_id)->value('branch_name');
        if (empty($branchName)) {
            throw ValidationException::withMessages([
                'branch_id' => ['Selected branch could not be resolved for invoice snapshot.'],
            ]);
        }

        [$invoiceNo, $dueDate] = $this->resolveInvoiceNumberAndDueDate($lockedStudent, $options);

        // Newly generated active invoices must always begin as 'Unpaid' with paid_amount = '0.00'
        // even when 100% discounted (total_net = '0.00', due_amount = '0.00'); never mark Paid without payment.
        $invoice = FeeInvoice::create([
            'invoice_no' => $invoiceNo,
            'student_id' => $lockedStudent->id,
            'branch_id' => $lockedStudent->branch_id,
            'session_year_id' => $lockedStudent->session_year_id,
            'branch_name_snapshot' => $branchName,
            'fee_setup_id' => null,
            'admission_fee_structure_id' => $activeStructure->id,
            'amount' => $calculation['total_gross'],
            'discount' => $calculation['total_discount'],
            'net_amount' => $calculation['total_net'],
            'paid_amount' => '0.00',
            'due_amount' => $calculation['total_net'],
            'status' => 'Unpaid',
            'due_date' => $dueDate,
            'user_id' => $resolvedActorId,
        ]);

        $itemsById = $activeStructure->items->keyBy('id');

        foreach ($calculation['heads'] as $headCalc) {
            $structureItem = $itemsById->get((int) $headCalc['id']);
            if (!$structureItem || empty($structureItem->category?->name)) {
                throw ValidationException::withMessages([
                    'items' => ['Structure fee head item or category snapshot could not be resolved.'],
                ]);
            }

            AdmissionInvoiceHeadAllocation::create([
                'fee_invoice_id' => $invoice->id,
                'student_id' => $lockedStudent->id,
                'branch_id' => $lockedStudent->branch_id,
                'admission_fee_structure_item_id' => $structureItem->id,
                'fee_category_id' => $structureItem->fee_category_id,
                'fee_category_name_snapshot' => $structureItem->category->name,
                'head_name_snapshot' => $structureItem->head_name,
                'branch_name_snapshot' => $branchName,
                'amount' => $headCalc['amount'],
                'discount' => $headCalc['discount'],
                'net_amount' => $headCalc['net_amount'],
                'allocated_paid_amount' => '0.00',
                'due_amount' => $headCalc['net_amount'],
                'is_discountable' => (bool) $structureItem->is_discountable,
                'is_tuition_head' => (bool) $structureItem->is_tuition_head,
                'sort_order' => (int) $structureItem->sort_order,
            ]);
        }

        // Pre-load relations immediately so any downstream feeSetup()->withDefault() access sees complete snapshots.
        $invoice->load(['admissionFeeStructure', 'admissionHeadAllocations', 'branch', 'sessionYear']);

        return [
            'success' => true,
            'created' => true,
            'legacy' => false,
            'invoice' => $invoice,
            'message' => "Admission Fee invoice '{$invoiceNo}' generated successfully.",
        ];
    }

    /**
     * Cancel an unpaid multi-head Admission Invoice cleanly, unlocking re-issuance (`-V2`)
     * and future Monthly Fee generation while preserving immutable head snapshots (`chk_aiha_math`).
     *
     * @throws ValidationException
     */
    public function cancelUnpaidInvoice(int $feeInvoiceId, string $reason, int $actorUserId): FeeInvoice
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->cancelUnpaidInvoiceWithinTransaction($feeInvoiceId, $reason, $actorUserId)
        );
    }

    /**
     * Execute unpaid Admission Invoice cancellation inside an active outer transaction.
     *
     * @throws ValidationException
     */
    public function cancelUnpaidInvoiceWithinTransaction(int $feeInvoiceId, string $reason, int $actorUserId): FeeInvoice
    {
        $this->transactionRunner->assertInTransaction('AdmissionInvoiceService::cancelUnpaidInvoiceWithinTransaction');

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages(['reason' => ['Cancellation reason is required.']]);
        }

        $lookup = FeeInvoice::findOrFail($feeInvoiceId);

        // Enforce 5-tier lock order: Tier 1 Student -> Tier 2 Invoice -> Tier 3 Payments
        $student = Student::where('id', $lookup->student_id)->lockForUpdate()->firstOrFail();
        $invoice = FeeInvoice::where('id', $feeInvoiceId)->lockForUpdate()->firstOrFail();

        if (!$invoice->isAdmissionInvoice()) {
            throw ValidationException::withMessages([
                'fee_invoice_id' => ['Only multi-head Admission Fee invoices may be cancelled via AdmissionInvoiceService.'],
            ]);
        }

        if ($invoice->status === 'Cancelled') {
            throw ValidationException::withMessages([
                'fee_invoice_id' => ["Invoice {$invoice->invoice_no} is already Cancelled."],
            ]);
        }

        $hasActivePayments = FeePayment::where('fee_invoice_id', $invoice->id)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->where('status', 'Active')
            ->exists();

        if ($hasActivePayments || $invoice->status !== 'Unpaid' || bccomp((string) $invoice->paid_amount, '0.00', 2) > 0) {
            throw ValidationException::withMessages([
                'fee_invoice_id' => ["Invoice {$invoice->invoice_no} has active payments or non-Unpaid status and cannot be cancelled directly."],
            ]);
        }

        $prevDue = bcadd((string) $invoice->due_amount, '0.00', 2);

        $invoice->status = 'Cancelled';
        $invoice->due_amount = '0.00';
        $invoice->save();

        FeeAdjustmentLog::create([
            'fee_invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'user_id' => $actorUserId,
            'invoice_no' => $invoice->invoice_no,
            'student_identity' => $student->student_identity,
            'mode' => 'set_amount',
            'scope' => 'single',
            'adjustment_amount' => $prevDue,
            'previous_amount' => $invoice->amount,
            'previous_discount' => $invoice->discount,
            'previous_net_amount' => $invoice->net_amount,
            'previous_due_amount' => $prevDue,
            'new_amount' => $invoice->amount,
            'new_discount' => $invoice->discount,
            'new_net_amount' => $invoice->net_amount,
            'new_due_amount' => '0.00',
            'paid_amount' => '0.00',
            'reason' => $trimmedReason,
        ]);

        return $invoice->fresh(['admissionFeeStructure', 'admissionHeadAllocations']);
    }

    /**
     * Assert that non-admin roles (`editor`, etc.) cannot supply non-zero Admission Discounts.
     * Shared across Controller pre-flight and Service defense-in-depth guards.
     *
     * @throws AuthorizationException|ValidationException
     */
    public static function assertDiscountPermission(?User $user, ?string $discountType, mixed $discountValue): void
    {
        [, , $hasNonZeroDiscount] = self::normalizeAndValidateDiscountInputs([
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
        ]);

        if ($hasNonZeroDiscount && !self::userCanGrantDiscount($user)) {
            throw new AuthorizationException('Only Admin and Super Admin users may apply Admission Discounts.');
        }
    }

    /**
     * Determine whether the provided user holds an Admin or Super Admin role.
     */
    public static function userCanGrantDiscount(?User $user): bool
    {
        if (!$user || !empty($user->suspended_at)) {
            return false;
        }

        $adminSlugs = config('tyro-dashboard.admin_roles', ['admin', 'super-admin']);
        if (method_exists($user, 'tyroRoleSlugs')) {
            $slugs = $user->tyroRoleSlugs();
            if (count(array_intersect($adminSlugs, $slugs)) > 0) {
                return true;
            }
        }

        $allowedNames = config('fees.authorized_roles', ['Super Admin', 'Admin', 'super-admin', 'admin']);

        return $user->roles()
            ->where(function ($q) use ($allowedNames, $adminSlugs): void {
                $q->whereIn('slug', $adminSlugs)->orWhereIn('name', $allowedNames);
            })
            ->exists();
    }

    /**
     * Validate discount option keys without mutating database state.
     *
     * @param  array<string, mixed>  $options
     * @return array{0: string, 1: string, 2: bool}
     *
     * @throws ValidationException
     */
    private static function normalizeAndValidateDiscountInputs(array $options): array
    {
        $rawType = $options['discount_type'] ?? AdmissionDiscountEngine::TYPE_NONE;
        $discountType = ($rawType === null || trim((string) $rawType) === '')
            ? AdmissionDiscountEngine::TYPE_NONE
            : trim((string) $rawType);

        if (!in_array($discountType, AdmissionDiscountEngine::VALID_TYPES, true)) {
            throw ValidationException::withMessages([
                'admission_discount_type' => ["Invalid discount type '{$discountType}'. Expected none, fixed, or percentage."],
            ]);
        }

        $rawValue = $options['discount_value'] ?? '0.00';
        $normalizedInput = ($rawValue === null || trim((string) $rawValue) === '')
            ? '0.00'
            : (is_string($rawValue) ? trim($rawValue) : $rawValue);

        try {
            $discountValue = AdmissionDiscountEngine::validateMonetaryString($normalizedInput, 'admission_discount_value');
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'admission_discount_value' => [$e->getMessage()],
            ]);
        }

        if ($discountType === AdmissionDiscountEngine::TYPE_NONE && bccomp($discountValue, '0.00', 2) > 0) {
            throw ValidationException::withMessages([
                'admission_discount_value' => ["Discount value must be '0.00' when discount type is 'none'."],
            ]);
        }

        $hasNonZeroDiscount = $discountType !== AdmissionDiscountEngine::TYPE_NONE
            && bccomp($discountValue, '0.00', 2) > 0;

        return [$discountType, $discountValue, $hasNonZeroDiscount];
    }

    /**
     * Resolve audit actor user ID and enforce Policy 1 when a non-zero discount is requested.
     *
     * @throws AuthorizationException|ValidationException
     */
    private function resolveAndAuthorizeActor(?int $actorUserId, bool $hasNonZeroDiscount): int
    {
        $actor = $actorUserId !== null ? User::with('roles')->find($actorUserId) : null;

        if ($hasNonZeroDiscount) {
            if (!self::userCanGrantDiscount($actor)) {
                throw new AuthorizationException('Only Admin and Super Admin users may apply Admission Discounts.');
            }

            return (int) $actor->id;
        }

        if ($actor && empty($actor->suspended_at)) {
            return (int) $actor->id;
        }

        return app(FeeGenerationService::class)->resolveActorUserId($actorUserId);
    }

    /**
     * Fallback to legacy FeeSetup Admission Fee when no active structure exists (if permitted),
     * or raise clear validation error when explicitly requesting multi-head package generation.
     *
     * @return array{success: bool, created: bool, legacy: bool, invoice: ?FeeInvoice, message: string}
     *
     * @throws ValidationException
     */
    private function handleMissingStructureOrPendingSchema(
        Student $lockedStudent,
        int $actorUserId,
        bool $fallbackToLegacy,
        bool $hasNonZeroDiscount,
        string $reason
    ): array {
        if ($hasNonZeroDiscount) {
            throw ValidationException::withMessages([
                'admission_discount_value' => [
                    'Cannot apply a multi-head Admission Discount because no active Admission Fee Structure exists for this Branch, Class, and Session.',
                ],
            ]);
        }

        if (!$fallbackToLegacy) {
            throw ValidationException::withMessages([
                'admission_fee_structure_id' => [$reason],
            ]);
        }

        $legacyResult = app(FeeGenerationService::class)->generateAdmissionFeeForNewStudent(
            $lockedStudent,
            $actorUserId
        );

        return [
            'success' => (bool) ($legacyResult['success'] ?? false),
            'created' => (bool) ($legacyResult['created'] ?? false),
            'legacy' => true,
            'invoice' => $legacyResult['invoice'] ?? null,
            'message' => (string) ($legacyResult['message'] ?? $reason),
        ];
    }

    /**
     * Guard against generating a second active multi-head Admission Invoice in the same scope.
     *
     * @param  Collection<int, FeeInvoice>  $lockedInvoices
     * @throws ValidationException
     */
    private function assertNoDuplicateActiveAdmissionInvoice(Student $student, Collection $lockedInvoices): void
    {
        $existingActive = $lockedInvoices->first(function (FeeInvoice $inv) use ($student): bool {
            return !is_null($inv->admission_fee_structure_id)
                && (int) $inv->branch_id === (int) $student->branch_id
                && (int) $inv->session_year_id === (int) $student->session_year_id
                && $inv->status !== 'Cancelled';
        });

        if ($existingActive) {
            throw ValidationException::withMessages([
                'admission_invoice' => [
                    "Duplicate Admission Invoice prevented: Student already has an active Admission Fee invoice ({$existingActive->invoice_no}) for this Branch and Session.",
                ],
            ]);
        }
    }

    /**
     * Policy 3: Block multi-head Admission Invoice while an active legacy Admission FeeSetup invoice exists.
     *
     * @param  Collection<int, FeeInvoice>  $legacyInvoices
     * @throws ValidationException
     */
    private function assertNoActiveLegacyAdmissionOverlap(Student $student, Collection $legacyInvoices): void
    {
        $activeLegacyAdmission = $legacyInvoices->first(function (FeeInvoice $inv) use ($student): bool {
            if ($inv->status === 'Cancelled' || !$inv->feeSetup) {
                return false;
            }

            $invBranchId = (int) ($inv->branch_id ?? $inv->feeSetup->branch_id);
            $invSessionId = (int) ($inv->session_year_id ?? $inv->feeSetup->session_year_id);
            $catName = strtolower(trim((string) ($inv->feeSetup->category?->name ?? '')));

            return $invBranchId === (int) $student->branch_id
                && $invSessionId === (int) $student->session_year_id
                && $catName === 'admission fee';
        });

        if ($activeLegacyAdmission) {
            throw ValidationException::withMessages([
                'admission_invoice' => [
                    "Legacy overlap detected: Student already has an active legacy Admission Fee invoice ({$activeLegacyAdmission->invoice_no}). Cancel the legacy invoice before generating a multi-head Admission invoice.",
                ],
            ]);
        }
    }

    /**
     * Policy 2: Monthly-First Tuition Conflict Check (HTTP 422).
     * Never silently cancel or alter existing historical Monthly Fee invoices.
     *
     * @param  Collection<int, FeeInvoice>  $legacyInvoices
     * @throws ValidationException
     */
    private function assertNoMonthlyFirstTuitionConflict(
        Student $student,
        AdmissionFeeStructure $structure,
        Collection $legacyInvoices
    ): void {
        if (!(bool) $structure->includes_monthly_tuition || empty($structure->tuition_month_number)) {
            return;
        }

        $targetMonthNum = (int) $structure->tuition_month_number;

        $conflictingMonthly = $legacyInvoices->first(function (FeeInvoice $inv) use ($student, $targetMonthNum): bool {
            if ($inv->status === 'Cancelled' || !$inv->feeSetup) {
                return false;
            }

            $invSessionId = (int) ($inv->session_year_id ?? $inv->feeSetup->session_year_id);
            if ($invSessionId !== (int) $student->session_year_id) {
                return false;
            }

            if (!FeeGenerationService::isMonthlyTuitionCategoryName($inv->feeSetup->category?->name)) {
                return false;
            }

            $parsedPeriod = FeeInvoice::extractYearMonthFromInvoiceNo($inv->invoice_no);
            $resolvedMonth = $parsedPeriod
                ? (int) $parsedPeriod['month']
                : Student::resolveMonthNumber((string) ($inv->feeSetup->fee_month ?? ''));

            if (!$resolvedMonth && !empty($inv->due_date)) {
                $resolvedMonth = (int) date('n', strtotime((string) $inv->due_date));
            }

            return $resolvedMonth === $targetMonthNum;
        });

        if ($conflictingMonthly) {
            $monthName = date('F', mktime(0, 0, 0, $targetMonthNum, 10));
            throw ValidationException::withMessages([
                'tuition_month_number' => [
                    "Monthly-First Conflict: A non-cancelled Monthly Fee invoice ({$conflictingMonthly->invoice_no}) already exists for {$monthName} in this academic session. Historical Monthly Invoices cannot be silently modified or cancelled.",
                ],
            ]);
        }
    }

    /**
     * Run pure BCMath AdmissionDiscountEngine calculations for the locked structure heads.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function calculateAllocations(
        AdmissionFeeStructure $structure,
        string $discountType,
        string $discountValue
    ): array {
        if ($structure->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['Active Admission Fee Structure has no configured fee heads.'],
            ]);
        }

        $headsPayload = $structure->items->map(static fn ($item): array => [
            'id' => (int) $item->id,
            'sort_order' => (int) $item->sort_order,
            'amount' => bcadd((string) $item->amount, '0.00', 2),
            'is_discountable' => (bool) $item->is_discountable,
        ])->values()->all();

        try {
            return AdmissionDiscountEngine::calculate($headsPayload, $discountType, $discountValue);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'admission_discount_value' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Resolve deterministic canonical `INV-ADM-S{S}-{YYYY}{MM}-{STUDENT_ID}[-Vn]` number and due date
     * under Tier 2 invoice locking.
     *
     * @param  array<string, mixed>  $options
     * @return array{0: string, 1: string}
     */
    private function resolveInvoiceNumberAndDueDate(Student $lockedStudent, array $options): array
    {
        SessionYear::findOrFail($lockedStudent->session_year_id);

        $issueCarbon = !empty($options['issue_date'])
            ? Carbon::parse((string) $options['issue_date'])
            : ($lockedStudent->created_at ?? now());

        $year = (int) $issueCarbon->format('Y');
        $month = (int) $issueCarbon->format('m');

        $dueDate = !empty($options['due_date'])
            ? Carbon::parse((string) $options['due_date'])->toDateString()
            : sprintf('%04d-%02d-10', $year, $month);

        $baseNo = sprintf(
            'INV-ADM-S%d-%04d%02d-%d',
            (int) $lockedStudent->session_year_id,
            $year,
            $month,
            (int) $lockedStudent->id
        );

        $existingNumbers = FeeInvoice::where('invoice_no', $baseNo)
            ->orWhere('invoice_no', 'like', $baseNo . '-V%')
            ->lockForUpdate()
            ->pluck('invoice_no')
            ->all();

        if (!in_array($baseNo, $existingNumbers, true)) {
            return [$baseNo, $dueDate];
        }

        $version = 2;
        while (in_array("{$baseNo}-V{$version}", $existingNumbers, true)) {
            $version++;
        }

        return ["{$baseNo}-V{$version}", $dueDate];
    }
}
