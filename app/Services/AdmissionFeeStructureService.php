<?php

namespace App\Services;

use App\Models\AdmissionFeeStructure;
use App\Models\AdmissionFeeStructureItem;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\SessionYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Manages versioned Admission Fee Structures and Fee Head items per Branch, Class, and Session.
 */
class AdmissionFeeStructureService
{
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_ARCHIVED = 'Archived';

    private const MONETARY_REGEX = '/^(0|[1-9]\d*)(\.\d{1,2})?$/';

    public function __construct(
        protected FinancialTransactionRunner $transactionRunner
    ) {
    }

    /**
     * Create a new Admission Fee Structure (version 1 or next sequential version in scope).
     *
     * @param  array<string, mixed>  $payload
     * @throws ValidationException
     */
    public function createStructure(array $payload, int $actorUserId): AdmissionFeeStructure
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->createStructureWithinTransaction($payload, $actorUserId)
        );
    }

    /**
     * Create a new Admission Fee Structure inside an already active outer transaction.
     *
     * @param  array<string, mixed>  $payload
     * @throws ValidationException
     */
    public function createStructureWithinTransaction(array $payload, int $actorUserId): AdmissionFeeStructure
    {
        $this->transactionRunner->assertInTransaction('AdmissionFeeStructureService::createStructureWithinTransaction');

        $validated = $this->validateAndNormalizePayload($payload, $actorUserId, allowArchivedStatus: false);
        $replaceActive = (bool) ($payload['replace_active'] ?? false);

        $scopeStructures = $this->lockScopeStructures(
            $validated['branch_id'],
            $validated['class_id'],
            $validated['session_year_id']
        );

        if ($validated['status'] === self::STATUS_ACTIVE) {
            $this->handleExistingActiveStructures($scopeStructures, $replaceActive);
        }

        $nextVersion = ((int) ($scopeStructures->max('version') ?? 0)) + 1;

        return $this->persistStructureWithItems($validated, $nextVersion, $actorUserId);
    }

    /**
     * Update an Admission Fee Structure cleanly through immutable versioning (creates N+1 version).
     * Never overwrites existing structure metadata or historical invoice snapshot items.
     *
     * @param  array<string, mixed>  $payload
     * @throws ValidationException
     */
    public function updateStructureWithVersioning(int $structureId, array $payload, int $actorUserId): AdmissionFeeStructure
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->updateStructureWithVersioningWithinTransaction($structureId, $payload, $actorUserId)
        );
    }

    /**
     * Versioned update executed inside an active outer transaction.
     *
     * @param  array<string, mixed>  $payload
     * @throws ValidationException
     */
    public function updateStructureWithVersioningWithinTransaction(
        int $structureId,
        array $payload,
        int $actorUserId
    ): AdmissionFeeStructure {
        $this->transactionRunner->assertInTransaction(
            'AdmissionFeeStructureService::updateStructureWithVersioningWithinTransaction'
        );

        $baseLookup = AdmissionFeeStructure::findOrFail($structureId);
        $this->assertScopeUnchanged($baseLookup, $payload);

        $scopeStructures = $this->lockScopeStructures(
            (int) $baseLookup->branch_id,
            (int) $baseLookup->class_id,
            $baseLookup->session_year_id !== null ? (int) $baseLookup->session_year_id : null
        );

        $lockedBase = $scopeStructures->firstWhere('id', $structureId);
        if (!$lockedBase) {
            throw ValidationException::withMessages([
                'structure_id' => ['The target Admission Fee Structure could not be found.'],
            ]);
        }

        $defaultNewStatus = $lockedBase->status === self::STATUS_ACTIVE
            ? self::STATUS_ACTIVE
            : self::STATUS_INACTIVE;

        $normalizedPayload = array_merge($payload, [
            'branch_id' => (int) $lockedBase->branch_id,
            'class_id' => (int) $lockedBase->class_id,
            'session_year_id' => $lockedBase->session_year_id !== null ? (int) $lockedBase->session_year_id : null,
            'status' => $payload['status'] ?? $defaultNewStatus,
        ]);

        $validated = $this->validateAndNormalizePayload($normalizedPayload, $actorUserId, allowArchivedStatus: false);
        $replaceActive = (bool) ($payload['replace_active'] ?? false);

        if ($validated['status'] === self::STATUS_ACTIVE) {
            $this->handleExistingActiveStructures($scopeStructures, $replaceActive, allowSupersedeId: (int) $lockedBase->id);
        }

        $nextVersion = ((int) ($scopeStructures->max('version') ?? 0)) + 1;

        return $this->persistStructureWithItems($validated, $nextVersion, $actorUserId);
    }

    /**
     * Activate a specific structure version within its Branch + Class + Session scope.
     *
     * @throws ValidationException
     */
    public function activateStructure(int $structureId, int $actorUserId, bool $deactivateExisting = true): AdmissionFeeStructure
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->activateStructureWithinTransaction($structureId, $actorUserId, $deactivateExisting)
        );
    }

    /**
     * Activate a structure version within an active transaction.
     *
     * @throws ValidationException
     */
    public function activateStructureWithinTransaction(
        int $structureId,
        int $actorUserId,
        bool $deactivateExisting = true
    ): AdmissionFeeStructure {
        $this->transactionRunner->assertInTransaction(
            'AdmissionFeeStructureService::activateStructureWithinTransaction'
        );

        $this->assertUserExists($actorUserId);

        $lookup = AdmissionFeeStructure::findOrFail($structureId);
        $scopeStructures = $this->lockScopeStructures(
            (int) $lookup->branch_id,
            (int) $lookup->class_id,
            $lookup->session_year_id !== null ? (int) $lookup->session_year_id : null
        );

        $target = $scopeStructures->firstWhere('id', $structureId);
        if (!$target) {
            throw ValidationException::withMessages([
                'structure_id' => ['Target Admission Fee Structure does not exist.'],
            ]);
        }

        if ($target->status === self::STATUS_ACTIVE) {
            return $this->findStructure((int) $target->id);
        }

        $this->handleExistingActiveStructures($scopeStructures, $deactivateExisting, allowSupersedeId: (int) $target->id);

        $target->status = self::STATUS_ACTIVE;
        $target->save();

        return $this->findStructure((int) $target->id);
    }

    /**
     * Deactivate or archive a structure without altering any historical invoices or structure items.
     */
    public function deactivateStructure(int $structureId, int $actorUserId, bool $archive = false): AdmissionFeeStructure
    {
        return $this->transactionRunner->runWithDeadlockRetry(
            fn () => $this->deactivateStructureWithinTransaction($structureId, $actorUserId, $archive)
        );
    }

    /**
     * Deactivate or archive a structure inside an active transaction.
     */
    public function deactivateStructureWithinTransaction(
        int $structureId,
        int $actorUserId,
        bool $archive = false
    ): AdmissionFeeStructure {
        $this->transactionRunner->assertInTransaction(
            'AdmissionFeeStructureService::deactivateStructureWithinTransaction'
        );

        $this->assertUserExists($actorUserId);

        $structure = AdmissionFeeStructure::lockForUpdate()->findOrFail($structureId);
        $structure->status = $archive ? self::STATUS_ARCHIVED : self::STATUS_INACTIVE;
        $structure->save();

        return $this->findStructure((int) $structure->id);
    }

    /**
     * Delete a draft structure ONLY when Inactive/Archived AND unreferenced by any invoice.
     *
     * @throws ValidationException
     */
    public function deleteUnusedDraftStructure(int $structureId): void
    {
        $this->transactionRunner->runWithDeadlockRetry(function () use ($structureId): void {
            $structure = AdmissionFeeStructure::lockForUpdate()->findOrFail($structureId);

            if ($structure->status === self::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'structure_id' => ['Active Admission Fee Structures cannot be deleted. Deactivate first.'],
                ]);
            }

            if ($structure->invoices()->exists() || $structure->items()->whereHas('invoiceAllocations')->exists()) {
                throw ValidationException::withMessages([
                    'structure_id' => ['Historical structures referenced by invoices cannot be deleted.'],
                ]);
            }

            $structure->items()->delete();
            $structure->delete();
        });
    }

    /**
     * Look up the single Active Admission Fee Structure for a Branch + Class + Session scope.
     */
    /**
     * Resolve the active Admission Fee Structure for a given Branch + Class + Session scope,
     * falling back to the active All-Time (NULL session_year_id) default package when no
     * session-specific override exists.
     */
    public function resolveActiveStructure(
        int $branchId,
        int $classId,
        ?int $sessionYearId = null,
        bool $lockForUpdate = false
    ): ?AdmissionFeeStructure {
        if ($sessionYearId !== null) {
            $sessionQuery = AdmissionFeeStructure::active()
                ->forScope($branchId, $classId, $sessionYearId)
                ->with(['items.category', 'branch', 'schoolClass', 'sessionYear']);

            if ($lockForUpdate) {
                $sessionQuery->lockForUpdate();
            }

            $exact = $sessionQuery->first();
            if ($exact) {
                return $exact;
            }
        }

        $allTimeQuery = AdmissionFeeStructure::active()
            ->forScope($branchId, $classId, null)
            ->with(['items.category', 'branch', 'schoolClass', 'sessionYear']);

        if ($lockForUpdate) {
            $allTimeQuery->lockForUpdate();
        }

        return $allTimeQuery->first();
    }

    /**
     * Fetch filtered structures ordered canonically (academic class order Play..Ten, then version DESC).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AdmissionFeeStructure>
     */
    public function listStructures(array $filters = []): Collection
    {
        $query = AdmissionFeeStructure::with(['branch', 'schoolClass', 'sessionYear', 'items.category', 'creator'])
            ->withCount(['items', 'invoices']);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', (int) $filters['class_id']);
        }
        if (!empty($filters['session_year_id'])) {
            if ($filters['session_year_id'] === 'all_time') {
                $query->whereNull('session_year_id');
            } else {
                $query->where('session_year_id', (int) $filters['session_year_id']);
            }
        }
        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where('structure_name', 'like', "%{$term}%");
        }

        $results = $query->orderBy('version', 'desc')->orderBy('id', 'desc')->get();

        // Preserve Play..Ten academic order using Classes global scope sequence.
        $classOrder = Classes::pluck('id')->flip();

        return $results->sort(function (AdmissionFeeStructure $a, AdmissionFeeStructure $b) use ($classOrder): int {
            $rankA = $classOrder[$a->class_id] ?? PHP_INT_MAX;
            $rankB = $classOrder[$b->class_id] ?? PHP_INT_MAX;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            if ((int) ($a->session_year_id ?? 0) !== (int) ($b->session_year_id ?? 0)) {
                return (int) ($b->session_year_id ?? 0) <=> (int) ($a->session_year_id ?? 0);
            }

            return (int) $b->version <=> (int) $a->version;
        })->values();
    }

    /**
     * Fetch a single structure with complete head item and scope relations.
     */
    public function findStructure(int $structureId): AdmissionFeeStructure
    {
        return AdmissionFeeStructure::with(['branch', 'schoolClass', 'sessionYear', 'items.category', 'creator'])
            ->withCount(['items', 'invoices'])
            ->findOrFail($structureId);
    }

    /**
     * Validate and normalize a monetary amount strictly without PHP floats.
     *
     * @throws ValidationException
     */
    public function validateMonetaryAmount(mixed $value, string $field = 'amount'): string
    {
        if (is_float($value) || is_bool($value) || is_array($value) || is_object($value) || $value === null) {
            throw ValidationException::withMessages([
                $field => ["The {$field} must be a valid non-negative decimal string with up to 2 decimal places."],
            ]);
        }

        $normalized = is_int($value) ? (string) $value : trim((string) $value);

        if (!preg_match(self::MONETARY_REGEX, $normalized)) {
            throw ValidationException::withMessages([
                $field => ["The {$field} value '{$normalized}' is invalid. Provide a non-negative amount up to 2 decimal places."],
            ]);
        }

        $scaled = bcadd($normalized, '0.00', 2);
        if (bccomp($scaled, '99999999.99', 2) > 0) {
            throw ValidationException::withMessages([
                $field => ["The {$field} exceeds maximum supported DECIMAL(10,2) precision (99999999.99)."],
            ]);
        }

        return $scaled;
    }

    /**
     * Lock all existing structure versions for a Branch + Class + Session scope in ID ASC order.
     *
     * @return Collection<int, AdmissionFeeStructure>
     */
    private function lockScopeStructures(int $branchId, int $classId, ?int $sessionYearId): Collection
    {
        return AdmissionFeeStructure::forScope($branchId, $classId, $sessionYearId)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Enforce single-active-structure invariant during creation, versioning, or activation.
     *
     * @param  Collection<int, AdmissionFeeStructure>  $scopeStructures
     * @throws ValidationException
     */
    private function handleExistingActiveStructures(
        Collection $scopeStructures,
        bool $replaceActive,
        ?int $allowSupersedeId = null
    ): void {
        $actives = $scopeStructures->where('status', self::STATUS_ACTIVE);

        if ($actives->isEmpty()) {
            return;
        }

        $blockingActive = $actives->first(
            fn (AdmissionFeeStructure $s): bool => $allowSupersedeId === null || (int) $s->id !== $allowSupersedeId
        );

        if ($blockingActive && !$replaceActive) {
            throw ValidationException::withMessages([
                'status' => [
                    "An active Admission Fee Structure (v{$blockingActive->version}: {$blockingActive->structure_name}) already exists for this Branch, Class, and Session scope.",
                ],
            ]);
        }

        foreach ($actives as $active) {
            $active->status = self::STATUS_ARCHIVED;
            $active->save();
        }
    }

    /**
     * Reject attempts to mutate Branch, Class, or Session scope during versioning.
     *
     * @param  array<string, mixed>  $payload
     * @throws ValidationException
     */
    private function assertScopeUnchanged(AdmissionFeeStructure $base, array $payload): void
    {
        foreach (['branch_id', 'class_id'] as $scopeCol) {
            if (array_key_exists($scopeCol, $payload) && (int) $payload[$scopeCol] !== (int) $base->{$scopeCol}) {
                throw ValidationException::withMessages([
                    $scopeCol => ["Cannot change {$scopeCol} during versioned update. Create a separate structure for the new scope."],
                ]);
            }
        }

        if (array_key_exists('session_year_id', $payload)) {
            $incomingSession = ($payload['session_year_id'] === null || $payload['session_year_id'] === '' || $payload['session_year_id'] === 0 || $payload['session_year_id'] === '0')
                ? null
                : (int) $payload['session_year_id'];
            $baseSession = $base->session_year_id !== null ? (int) $base->session_year_id : null;

            if ($incomingSession !== $baseSession) {
                throw ValidationException::withMessages([
                    'session_year_id' => ['Cannot change session_year_id during versioned update. Create a separate structure for the new scope.'],
                ]);
            }
        }
    }

    /**
     * Validate structural integrity, relational references, tuition flags, and BCMath head sums.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function validateAndNormalizePayload(array $payload, int $actorUserId, bool $allowArchivedStatus): array
    {
        $this->assertUserExists($actorUserId);

        $branchId = (int) ($payload['branch_id'] ?? 0);
        $classId = (int) ($payload['class_id'] ?? 0);
        $rawSession = $payload['session_year_id'] ?? null;
        $sessionYearId = ($rawSession === null || $rawSession === '' || $rawSession === 0 || $rawSession === '0')
            ? null
            : (int) $rawSession;

        if ($branchId <= 0 || !Branch::whereKey($branchId)->exists()) {
            throw ValidationException::withMessages(['branch_id' => ['Selected branch does not exist.']]);
        }
        if ($classId <= 0 || !Classes::whereKey($classId)->exists()) {
            throw ValidationException::withMessages(['class_id' => ['Selected class does not exist.']]);
        }
        if ($sessionYearId !== null && ($sessionYearId < 0 || !SessionYear::whereKey($sessionYearId)->exists())) {
            throw ValidationException::withMessages(['session_year_id' => ['Selected session year does not exist.']]);
        }

        $structureName = trim((string) ($payload['structure_name'] ?? ''));
        if ($structureName === '' || mb_strlen($structureName) > 150) {
            throw ValidationException::withMessages([
                'structure_name' => ['Structure name is required and may not exceed 150 characters.'],
            ]);
        }

        $status = (string) ($payload['status'] ?? self::STATUS_ACTIVE);
        $allowedStatuses = $allowArchivedStatus
            ? [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_ARCHIVED]
            : [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

        if (!in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid structure status '{$status}'."],
            ]);
        }

        $includesTuition = (bool) ($payload['includes_monthly_tuition'] ?? false);
        $rawMonth = $payload['tuition_month_number'] ?? null;
        $tuitionMonth = ($rawMonth === '' || $rawMonth === null) ? null : (int) $rawMonth;

        if ($includesTuition) {
            if ($tuitionMonth === null || $tuitionMonth < 1 || $tuitionMonth > 12) {
                throw ValidationException::withMessages([
                    'tuition_month_number' => ['Tuition month number (1–12) is required when monthly tuition is included.'],
                ]);
            }
        } elseif ($tuitionMonth !== null) {
            throw ValidationException::withMessages([
                'tuition_month_number' => ['Tuition month number must be null when monthly tuition is not included.'],
            ]);
        }

        $rawItems = $payload['items'] ?? [];
        if (!is_array($rawItems) || count($rawItems) < 1) {
            throw ValidationException::withMessages([
                'items' => ['At least one Fee Head item is required in an Admission Fee Structure.'],
            ]);
        }

        $normalizedItems = $this->validateAndNormalizeItems($rawItems, $includesTuition);

        $computedTotal = '0.00';
        foreach ($normalizedItems as $item) {
            $computedTotal = bcadd($computedTotal, $item['amount'], 2);
        }

        if (array_key_exists('total_amount', $payload) && $payload['total_amount'] !== null && $payload['total_amount'] !== '') {
            $providedTotal = $this->validateMonetaryAmount($payload['total_amount'], 'total_amount');
            if (bccomp($providedTotal, $computedTotal, 2) !== 0) {
                throw ValidationException::withMessages([
                    'total_amount' => [
                        "Declared structure total ({$providedTotal}) does not match exact sum of fee heads ({$computedTotal}).",
                    ],
                ]);
            }
        }

        return [
            'branch_id' => $branchId,
            'class_id' => $classId,
            'session_year_id' => $sessionYearId,
            'structure_name' => $structureName,
            'status' => $status,
            'total_amount' => $computedTotal,
            'includes_monthly_tuition' => $includesTuition,
            'tuition_month_number' => $tuitionMonth,
            'notes' => isset($payload['notes']) ? trim((string) $payload['notes']) ?: null : null,
            'items' => $normalizedItems,
        ];
    }

    /**
     * Validate and normalize Fee Head item rows, uniqueness, categories, and tuition-head consistency.
     *
     * @param  array<int, mixed>  $rawItems
     * @return array<int, array<string, mixed>>
     * @throws ValidationException
     */
    private function validateAndNormalizeItems(array $rawItems, bool $includesTuition): array
    {
        $categoryIds = [];
        foreach ($rawItems as $rawItem) {
            if (!is_array($rawItem)) {
                throw ValidationException::withMessages(['items' => ['Each fee head item must be an array.']]);
            }
            $categoryIds[] = (int) ($rawItem['fee_category_id'] ?? 0);
        }

        $categories = FeeCategory::whereIn('id', array_unique(array_filter($categoryIds)))->get()->keyBy('id');

        $seenHeadKeys = [];
        $tuitionHeadCount = 0;
        $normalized = [];

        foreach (array_values($rawItems) as $index => $rawItem) {
            $catId = (int) ($rawItem['fee_category_id'] ?? 0);
            $category = $categories->get($catId);
            if (!$category) {
                throw ValidationException::withMessages([
                    "items.{$index}.fee_category_id" => ['Selected fee category does not exist.'],
                ]);
            }

            if (isset($category->status) && strtolower((string) $category->status) !== 'active') {
                throw ValidationException::withMessages([
                    "items.{$index}.fee_category_id" => ["Fee category '{$category->name}' is not Active."],
                ]);
            }

            $headName = trim((string) ($rawItem['head_name'] ?? ''));
            if ($headName === '' || mb_strlen($headName) > 150) {
                throw ValidationException::withMessages([
                    "items.{$index}.head_name" => ['Fee head name is required and may not exceed 150 characters.'],
                ]);
            }

            $dedupeKey = $catId . '::' . mb_strtolower($headName);
            if (isset($seenHeadKeys[$dedupeKey])) {
                throw ValidationException::withMessages([
                    "items.{$index}.head_name" => ["Duplicate fee head '{$headName}' within the same fee category."],
                ]);
            }
            $seenHeadKeys[$dedupeKey] = true;

            $amount = $this->validateMonetaryAmount($rawItem['amount'] ?? null, "items.{$index}.amount");
            $isDiscountable = (bool) ($rawItem['is_discountable'] ?? true);
            $isTuitionHead = (bool) ($rawItem['is_tuition_head'] ?? false);

            if ($isTuitionHead) {
                $tuitionHeadCount++;
            }

            $sortOrder = array_key_exists('sort_order', $rawItem) && $rawItem['sort_order'] !== null
                ? max(0, (int) $rawItem['sort_order'])
                : (($index + 1) * 10);

            $normalized[] = [
                'fee_category_id' => $catId,
                'head_name' => $headName,
                'amount' => $amount,
                'is_discountable' => $isDiscountable,
                'is_tuition_head' => $isTuitionHead,
                'sort_order' => $sortOrder,
            ];
        }

        if ($includesTuition && $tuitionHeadCount !== 1) {
            throw ValidationException::withMessages([
                'items' => [
                    'Structures with includes_monthly_tuition enabled must designate exactly one Fee Head with is_tuition_head=true.',
                ],
            ]);
        }

        if (!$includesTuition && $tuitionHeadCount > 0) {
            throw ValidationException::withMessages([
                'items' => [
                    'Structures without monthly tuition included cannot mark any Fee Head as is_tuition_head=true.',
                ],
            ]);
        }

        return $normalized;
    }

    /**
     * Persist new structure version and all immutable item rows.
     *
     * @param  array<string, mixed>  $validated
     */
    private function persistStructureWithItems(array $validated, int $version, int $actorUserId): AdmissionFeeStructure
    {
        $structure = AdmissionFeeStructure::create([
            'branch_id' => $validated['branch_id'],
            'class_id' => $validated['class_id'],
            'session_year_id' => $validated['session_year_id'],
            'structure_name' => $validated['structure_name'],
            'version' => $version,
            'status' => $validated['status'],
            'total_amount' => $validated['total_amount'],
            'includes_monthly_tuition' => $validated['includes_monthly_tuition'],
            'tuition_month_number' => $validated['tuition_month_number'],
            'notes' => $validated['notes'],
            'created_by' => $actorUserId,
        ]);

        foreach ($validated['items'] as $item) {
            AdmissionFeeStructureItem::create([
                'admission_fee_structure_id' => $structure->id,
                'fee_category_id' => $item['fee_category_id'],
                'head_name' => $item['head_name'],
                'amount' => $item['amount'],
                'is_discountable' => $item['is_discountable'],
                'is_tuition_head' => $item['is_tuition_head'],
                'sort_order' => $item['sort_order'],
            ]);
        }

        return $this->findStructure((int) $structure->id);
    }

    /**
     * Ensure actor user exists before assigning `created_by` FK (`ON DELETE RESTRICT`).
     *
     * @throws ValidationException
     */
    private function assertUserExists(int $actorUserId): void
    {
        if ($actorUserId <= 0 || !User::whereKey($actorUserId)->exists()) {
            throw ValidationException::withMessages([
                'created_by' => ['Valid authenticated actor user ID is required.'],
            ]);
        }
    }
}
