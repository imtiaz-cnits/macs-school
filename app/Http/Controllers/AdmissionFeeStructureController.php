<?php

namespace App\Http\Controllers;

use App\Models\AdmissionFeeStructure;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\FeeSetup;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionDiscountEngine;
use App\Services\AdmissionFeeStructureService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Operational UI & API Controller for Versioned Admission Fee Structures,
 * Read-Only Admission Package Preview, and Admin-Only BCMath Discount Preview (Subphase 4E).
 */
class AdmissionFeeStructureController extends Controller
{
    public const PENDING_SCHEMA_WARNING = 'Admission schema migrations are pending. Please contact the system administrator.';

    public function __construct(
        protected AdmissionFeeStructureService $structureService
    ) {
    }

    /**
     * Enforce strict admin/super-admin access for structure management and discount preview.
     */
    protected function authorizeStructureAdmin(): User
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (!method_exists($user, 'hasRole') || (!$user->hasRole('admin') && !$user->hasRole('super-admin'))) {
            abort(403, 'Unauthorized access to Admission Fee Structures.');
        }

        return $user;
    }

    /**
     * Authorize read-only active package lookup during student admission (`editor`, `admin`, `super-admin`).
     */
    protected function authorizeReadScope(): User
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (
            !method_exists($user, 'hasRole')
            || (!$user->hasRole('editor') && !$user->hasRole('admin') && !$user->hasRole('super-admin'))
        ) {
            abort(403, 'Unauthorized access.');
        }

        return $user;
    }

    /**
     * Metadata-only schema readiness check (executes zero data queries against pending tables).
     */
    protected function admissionSchemaReady(): bool
    {
        return Schema::hasTable('admission_fee_structures')
            && Schema::hasTable('admission_fee_structure_items')
            && Schema::hasTable('admission_invoice_head_allocations')
            && Schema::hasTable('admission_payment_head_allocations')
            && Schema::hasColumn('fee_invoices', 'admission_fee_structure_id')
            && Schema::hasColumn('fee_payments', 'status');
    }

    protected function pendingSchemaJsonResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'tables_available' => false,
            'message' => self::PENDING_SCHEMA_WARNING,
        ], 422);
    }

    /**
     * Display Admission Fee Structures Console or JSON listing.
     */
    public function index(Request $request): JsonResponse|View
    {
        $this->authorizeStructureAdmin();

        $schemaReady = $this->admissionSchemaReady();
        $filters = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'class_id' => 'nullable|exists:classes,id',
            'session_year_id' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '' || $value === 'all_time') {
                        return;
                    }
                    if (!ctype_digit((string) $value) || !SessionYear::whereKey((int) $value)->exists()) {
                        $fail('Selected session year filter is invalid.');
                    }
                },
            ],
            'status' => 'nullable|in:Active,Inactive,Archived',
            'search' => 'nullable|string|max:120',
        ]);

        if (!$schemaReady) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'tables_available' => false,
                    'schema_warning' => self::PENDING_SCHEMA_WARNING,
                    'structures' => [],
                ]);
            }

            $options = $this->loadDropdownOptions();

            return view('pages.fees.admission_structures_index', [
                'schemaReady' => false,
                'schemaWarning' => self::PENDING_SCHEMA_WARNING,
                'structuresPayload' => [],
                'filters' => $filters,
                'branches' => $options['branches'],
                'classes' => $options['classes'],
                'sessions' => $options['sessions'],
                'feeCategories' => $options['feeCategories'],
            ]);
        }

        $structures = $this->structureService->listStructures($filters);
        $structuresPayload = $structures->map(fn (AdmissionFeeStructure $s): array => $this->formatStructureDto($s))->values()->all();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'tables_available' => true,
                'schema_warning' => null,
                'structures' => $structuresPayload,
            ]);
        }

        $options = $this->loadDropdownOptions();

        return view('pages.fees.admission_structures_index', [
            'schemaReady' => true,
            'schemaWarning' => null,
            'structuresPayload' => $structuresPayload,
            'filters' => $filters,
            'branches' => $options['branches'],
            'classes' => $options['classes'],
            'sessions' => $options['sessions'],
            'feeCategories' => $options['feeCategories'],
        ]);
    }

    /**
     * Create a new Admission Fee Structure (version 1 or next sequential version in scope).
     */
    public function store(Request $request): JsonResponse
    {
        $actor = $this->authorizeStructureAdmin();
        if (!$this->admissionSchemaReady()) {
            return $this->pendingSchemaJsonResponse();
        }

        try {
            $payload = $this->extractValidatedPayload($request);
            $created = $this->structureService->createStructure($payload, (int) $actor->id);
            $hydrated = $this->structureService->findStructure((int) $created->id);

            return response()->json([
                'success' => true,
                'message' => "Admission Fee Structure '{$hydrated->structure_name}' (v{$hydrated->version}) created successfully.",
                'structure' => $this->formatStructureDto($hydrated),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorJson($e);
        }
    }

    /**
     * Create a new immutable version (vN+1) from an existing Admission Fee Structure.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $actor = $this->authorizeStructureAdmin();
        if (!$this->admissionSchemaReady()) {
            return $this->pendingSchemaJsonResponse();
        }

        try {
            $payload = $this->extractValidatedPayload($request);
            $versioned = $this->structureService->updateStructureWithVersioning($id, $payload, (int) $actor->id);
            $hydrated = $this->structureService->findStructure((int) $versioned->id);

            return response()->json([
                'success' => true,
                'message' => "Created version v{$hydrated->version} for '{$hydrated->structure_name}'. Previous versions remain immutable.",
                'structure' => $this->formatStructureDto($hydrated),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Admission Fee Structure not found.'], 404);
        } catch (ValidationException $e) {
            return $this->validationErrorJson($e);
        }
    }

    /**
     * Activate a specific Admission Fee Structure version.
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $actor = $this->authorizeStructureAdmin();
        if (!$this->admissionSchemaReady()) {
            return $this->pendingSchemaJsonResponse();
        }

        try {
            $deactivateExisting = $request->boolean('deactivate_existing', true);
            $activated = $this->structureService->activateStructure($id, (int) $actor->id, $deactivateExisting);
            $hydrated = $this->structureService->findStructure((int) $activated->id);

            return response()->json([
                'success' => true,
                'message' => "Structure '{$hydrated->structure_name}' (v{$hydrated->version}) is now Active.",
                'structure' => $this->formatStructureDto($hydrated),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Admission Fee Structure not found.'], 404);
        } catch (ValidationException $e) {
            return $this->validationErrorJson($e);
        }
    }

    /**
     * Deactivate or archive an Admission Fee Structure version.
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        $actor = $this->authorizeStructureAdmin();
        if (!$this->admissionSchemaReady()) {
            return $this->pendingSchemaJsonResponse();
        }

        try {
            $archive = $request->boolean('archive', false);
            $updated = $this->structureService->deactivateStructure($id, (int) $actor->id, $archive);
            $hydrated = $this->structureService->findStructure((int) $updated->id);

            return response()->json([
                'success' => true,
                'message' => "Structure '{$hydrated->structure_name}' (v{$hydrated->version}) marked {$hydrated->status}.",
                'structure' => $this->formatStructureDto($hydrated),
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Admission Fee Structure not found.'], 404);
        } catch (ValidationException $e) {
            return $this->validationErrorJson($e);
        }
    }

    /**
     * Permanently delete an unused Inactive draft structure (0 generated invoices).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorizeStructureAdmin();
        if (!$this->admissionSchemaReady()) {
            return $this->pendingSchemaJsonResponse();
        }

        try {
            $this->structureService->deleteUnusedDraftStructure($id);

            return response()->json([
                'success' => true,
                'message' => 'Unused draft Admission Fee Structure deleted.',
            ]);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Admission Fee Structure not found.'], 404);
        } catch (ValidationException $e) {
            return $this->validationErrorJson($e);
        }
    }

    /**
     * Read-only active package resolver for Student Admission Form (Editor, Admin, Super Admin).
     */
    public function activeForScope(Request $request): JsonResponse
    {
        $this->authorizeReadScope();

        if (!$this->admissionSchemaReady()) {
            return response()->json([
                'success' => true,
                'tables_available' => false,
                'schema_warning' => self::PENDING_SCHEMA_WARNING,
                'structure' => null,
                'legacy_fallback' => null,
            ]);
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'session_year_id' => 'required|exists:session_years,id',
        ]);

        $structure = $this->structureService->resolveActiveStructure(
            (int) $validated['branch_id'],
            (int) $validated['class_id'],
            (int) $validated['session_year_id']
        );

        if ($structure) {
            return response()->json([
                'success' => true,
                'tables_available' => true,
                'schema_warning' => null,
                'structure' => $this->formatStructureDto($structure),
                'legacy_fallback' => null,
            ]);
        }

        // Non-blocking informational check on existing legacy Admission Fee Setup fallback
        $legacyAdmissionSetup = FeeSetup::with('category')
            ->where('branch_id', (int) $validated['branch_id'])
            ->where('class_id', (int) $validated['class_id'])
            ->where('session_year_id', (int) $validated['session_year_id'])
            ->where('status', 'Active')
            ->whereHas('category', function ($q): void {
                $q->whereRaw('LOWER(name) = ?', ['admission fee']);
            })
            ->first();

        return response()->json([
            'success' => true,
            'tables_available' => true,
            'schema_warning' => null,
            'structure' => null,
            'legacy_fallback' => $legacyAdmissionSetup ? [
                'fee_setup_id' => (int) $legacyAdmissionSetup->id,
                'category_name' => (string) ($legacyAdmissionSetup->category?->name ?? 'Admission Fee'),
                'amount' => bcadd((string) $legacyAdmissionSetup->amount, '0.00', 2),
            ] : null,
        ]);
    }

    /**
     * Exact server-side BCMath discount preview (Admin / Super Admin ONLY).
     */
    public function discountPreview(Request $request): JsonResponse
    {
        $this->authorizeStructureAdmin();
        if (!$this->admissionSchemaReady()) {
            return $this->pendingSchemaJsonResponse();
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'session_year_id' => 'required|exists:session_years,id',
            'discount_type' => 'nullable|in:none,fixed,percentage',
            'discount_value' => 'nullable|max:20',
        ]);

        $structure = $this->structureService->resolveActiveStructure(
            (int) $validated['branch_id'],
            (int) $validated['class_id'],
            (int) $validated['session_year_id']
        );

        if (!$structure) {
            return response()->json([
                'success' => false,
                'message' => 'No active Admission Fee Structure found for this scope.',
            ], 404);
        }

        try {
            $discountType = trim((string) ($validated['discount_type'] ?? AdmissionDiscountEngine::TYPE_NONE));
            if ($discountType === '') {
                $discountType = AdmissionDiscountEngine::TYPE_NONE;
            }

            $rawDiscVal = $validated['discount_value'] ?? '0.00';
            $discountValue = $discountType === AdmissionDiscountEngine::TYPE_NONE
                ? '0.00'
                : AdmissionCollectionService::normalizeHttpMonetaryInput(
                    ($rawDiscVal === '' || $rawDiscVal === null) ? '0.00' : $rawDiscVal
                );

            $headsInput = $structure->items->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'sort_order' => (int) $item->sort_order,
                'amount' => bcadd((string) $item->amount, '0.00', 2),
                'is_discountable' => (bool) $item->is_discountable,
                'head_name' => (string) $item->head_name,
                'category_name' => (string) ($item->category?->name ?? 'Admission Fee'),
                'is_tuition_head' => (bool) $item->is_tuition_head,
            ])->values()->all();

            $calculation = AdmissionDiscountEngine::calculate($headsInput, $discountType, $discountValue);

            return response()->json([
                'success' => true,
                'preview' => $calculation,
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorJson($e);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Normalize HTTP input into BCMath-safe payload consumed by AdmissionFeeStructureService.
     *
     * @return array<string, mixed>
     * @throws ValidationException
     */
    protected function extractValidatedPayload(Request $request): array
    {
        $raw = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'class_id' => 'required|integer|exists:classes,id',
            'session_year_id' => 'nullable|integer|exists:session_years,id',
            'structure_name' => 'required|string|max:150',
            'status' => 'nullable|in:Active,Inactive',
            'replace_active' => 'nullable|boolean',
            'total_amount' => 'required',
            'includes_monthly_tuition' => 'nullable|boolean',
            'tuition_month_number' => 'nullable|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.fee_category_id' => 'required|integer|exists:fee_categories,id',
            'items.*.head_name' => 'required|string|max:120',
            'items.*.amount' => 'required',
            'items.*.is_discountable' => 'nullable|boolean',
            'items.*.is_tuition_head' => 'nullable|boolean',
            'items.*.sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        $normalizedItems = [];
        foreach ($raw['items'] as $idx => $item) {
            $itemAmtStr = AdmissionCollectionService::normalizeHttpMonetaryInput($item['amount']);
            $normalizedItems[] = [
                'fee_category_id' => (int) $item['fee_category_id'],
                'head_name' => trim((string) $item['head_name']),
                'amount' => $this->structureService->validateMonetaryAmount($itemAmtStr, "items.{$idx}.amount"),
                'is_discountable' => (bool) ($item['is_discountable'] ?? true),
                'is_tuition_head' => (bool) ($item['is_tuition_head'] ?? false),
                'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : (($idx + 1) * 10),
            ];
        }

        $includesTuition = (bool) ($raw['includes_monthly_tuition'] ?? false);
        $totalAmtStr = AdmissionCollectionService::normalizeHttpMonetaryInput($raw['total_amount']);

        return [
            'branch_id' => (int) $raw['branch_id'],
            'class_id' => (int) $raw['class_id'],
            'session_year_id' => !empty($raw['session_year_id']) ? (int) $raw['session_year_id'] : null,
            'structure_name' => trim((string) $raw['structure_name']),
            'status' => (string) ($raw['status'] ?? AdmissionFeeStructureService::STATUS_ACTIVE),
            'replace_active' => (bool) ($raw['replace_active'] ?? false),
            'total_amount' => $this->structureService->validateMonetaryAmount($totalAmtStr, 'total_amount'),
            'includes_monthly_tuition' => $includesTuition,
            'tuition_month_number' => $includesTuition ? ($raw['tuition_month_number'] ?? null) : null,
            'notes' => isset($raw['notes']) ? trim((string) $raw['notes']) : null,
            'items' => $normalizedItems,
        ];
    }

    /**
     * Prepare clean presentation DTO for controllers/views (Condition 3 Blade Separation).
     *
     * @return array<string, mixed>
     */
    protected function formatStructureDto(AdmissionFeeStructure $structure): array
    {
        $items = $structure->items->map(static function ($item): array {
            return [
                'id' => (int) $item->id,
                'fee_category_id' => (int) $item->fee_category_id,
                'category_name' => (string) ($item->category?->name ?? 'N/A'),
                'head_name' => (string) $item->head_name,
                'amount' => bcadd((string) $item->amount, '0.00', 2),
                'is_discountable' => (bool) $item->is_discountable,
                'is_tuition_head' => (bool) $item->is_tuition_head,
                'sort_order' => (int) $item->sort_order,
            ];
        })->values()->all();

        $eligibleGross = '0.00';
        foreach ($items as $it) {
            if ($it['is_discountable']) {
                $eligibleGross = bcadd($eligibleGross, $it['amount'], 2);
            }
        }

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        return [
            'id' => (int) $structure->id,
            'branch_id' => (int) $structure->branch_id,
            'branch_name' => (string) ($structure->branch?->branch_name ?? 'N/A'),
            'class_id' => (int) $structure->class_id,
            'class_name' => (string) ($structure->schoolClass?->class_name ?? 'N/A'),
            'session_year_id' => $structure->session_year_id ? (int) $structure->session_year_id : null,
            'session_name' => $structure->session_year_id ? (string) ($structure->sessionYear?->session_name ?? 'N/A') : 'All Sessions',
            'is_all_time' => $structure->session_year_id === null,
            'structure_name' => (string) $structure->structure_name,
            'version' => (int) $structure->version,
            'status' => (string) $structure->status,
            'total_amount' => bcadd((string) $structure->total_amount, '0.00', 2),
            'eligible_gross' => $eligibleGross,
            'includes_monthly_tuition' => (bool) $structure->includes_monthly_tuition,
            'tuition_month_number' => $structure->tuition_month_number ? (int) $structure->tuition_month_number : null,
            'tuition_month_name' => $structure->includes_monthly_tuition && $structure->tuition_month_number
                ? ($monthNames[(int) $structure->tuition_month_number] ?? 'Month #' . $structure->tuition_month_number)
                : 'Excluded',
            'notes' => (string) ($structure->notes ?? ''),
            'items_count' => (int) ($structure->items_count ?? count($items)),
            'invoices_count' => (int) ($structure->invoices_count ?? 0),
            'created_by_name' => (string) ($structure->creator?->name ?? 'System'),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     branches: \Illuminate\Database\Eloquent\Collection,
     *     classes: \Illuminate\Database\Eloquent\Collection,
     *     sessions: \Illuminate\Database\Eloquent\Collection,
     *     feeCategories: \Illuminate\Database\Eloquent\Collection
     * }
     */
    protected function loadDropdownOptions(): array
    {
        return [
            'branches' => Branch::orderBy('branch_name')->get(),
            'classes' => Classes::all(),
            'sessions' => SessionYear::orderByDesc('id')->get(),
            'feeCategories' => FeeCategory::where('status', 'Active')->orderBy('name')->get(),
        ];
    }

    protected function validationErrorJson(ValidationException $e): JsonResponse
    {
        $first = collect($e->errors())->flatten()->first() ?? $e->getMessage();

        return response()->json([
            'success' => false,
            'message' => $first,
            'errors' => $e->errors(),
        ], 422);
    }
}
