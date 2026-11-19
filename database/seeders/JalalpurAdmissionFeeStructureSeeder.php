<?php

namespace Database\Seeders;

use App\Models\AdmissionFeeStructure;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\User;
use App\Services\AdmissionFeeStructureService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Production-safe, strictly idempotent seeder for Jalalpur Branch All-Time Admission Fee Structures (Play through Ten).
 *
 * Usage on live server:
 *   php artisan db:seed --class=JalalpurAdmissionFeeStructureSeeder --force
 */
class JalalpurAdmissionFeeStructureSeeder extends Seeder
{
    /**
     * Authoritative Jalalpur Branch class-wise admission configurations in academic sequence (Play -> Ten).
     *
     * @var array<int, array{class: string, tuition: string, include_admission_fee: bool, expected_total: string}>
     */
    private const CLASS_PACKAGES = [
        ['class' => 'Play',    'tuition' => '570.00',  'include_admission_fee' => true,  'expected_total' => '3950.00'],
        ['class' => 'Nursery', 'tuition' => '570.00',  'include_admission_fee' => true,  'expected_total' => '3950.00'],
        ['class' => 'One',     'tuition' => '620.00',  'include_admission_fee' => true,  'expected_total' => '4000.00'],
        ['class' => 'Two',     'tuition' => '620.00',  'include_admission_fee' => true,  'expected_total' => '4000.00'],
        ['class' => 'Three',   'tuition' => '620.00',  'include_admission_fee' => true,  'expected_total' => '4000.00'],
        ['class' => 'Four',    'tuition' => '670.00',  'include_admission_fee' => true,  'expected_total' => '4050.00'],
        ['class' => 'Five',    'tuition' => '720.00',  'include_admission_fee' => true,  'expected_total' => '4100.00'],
        ['class' => 'Six',     'tuition' => '770.00',  'include_admission_fee' => true,  'expected_total' => '4150.00'],
        ['class' => 'Seven',   'tuition' => '770.00',  'include_admission_fee' => true,  'expected_total' => '4150.00'],
        ['class' => 'Eight',   'tuition' => '820.00',  'include_admission_fee' => true,  'expected_total' => '4200.00'],
        ['class' => 'Nine',    'tuition' => '1020.00', 'include_admission_fee' => true,  'expected_total' => '4400.00'],
        ['class' => 'Ten',     'tuition' => '1020.00', 'include_admission_fee' => false, 'expected_total' => '3400.00'],
    ];

    public function run(): void
    {
        if (
            !Schema::hasTable('admission_fee_structures')
            || !Schema::hasTable('admission_fee_structure_items')
            || !Schema::hasColumn('admission_fee_structures', 'scope_session_key')
        ) {
            throw new RuntimeException(
                'Admission Fee Structure migrations (including nullable session_year_id / scope_session_key) must be executed before running JalalpurAdmissionFeeStructureSeeder.'
            );
        }

        $branch = Branch::query()
            ->whereRaw('LOWER(branch_name) LIKE ?', ['%jalalpur%'])
            ->orderBy('id')
            ->first();

        if (!$branch) {
            throw new RuntimeException('Jalalpur Branch was not found in the branches table. Aborting safely.');
        }

        $actor = $this->resolveAdminActor();
        if (!$actor) {
            throw new RuntimeException('No valid user account found in users table to assign as created_by. Aborting safely.');
        }

        // Resolve or ensure required Fee Categories (case-insensitive lookup to avoid duplicates)
        $categories = [
            'Monthly Fee'   => $this->resolveOrCreateCategory('Monthly Fee', (int) $actor->id),
            'Session Fee'   => $this->resolveOrCreateCategory('Session Fee', (int) $actor->id),
            'Admission Fee' => $this->resolveOrCreateCategory('Admission Fee', (int) $actor->id),
            'Others'        => $this->resolveOrCreateCategory('Others', (int) $actor->id),
        ];

        // Map existing classes case-insensitively
        $classLookup = Classes::all()->keyBy(static fn (Classes $c): string => strtolower(trim((string) $c->class_name)));

        $missingClasses = [];
        foreach (self::CLASS_PACKAGES as $pkg) {
            if (!$classLookup->has(strtolower($pkg['class']))) {
                $missingClasses[] = $pkg['class'];
            }
        }
        if (!empty($missingClasses)) {
            throw new RuntimeException(
                'Missing required class(es) in classes table: ' . implode(', ', $missingClasses) . '. Aborting safely.'
            );
        }

        /** @var AdmissionFeeStructureService $structureService */
        $structureService = app(AdmissionFeeStructureService::class);

        $createdCount = 0;
        $skippedCount = 0;

        foreach (self::CLASS_PACKAGES as $pkg) {
            /** @var Classes $class */
            $class = $classLookup->get(strtolower($pkg['class']));

            // Strictly idempotent: skip if ANY All-Time structure already exists for this Branch + Class
            $existing = AdmissionFeeStructure::query()
                ->where('branch_id', $branch->id)
                ->where('class_id', $class->id)
                ->whereNull('session_year_id')
                ->orderByDesc('version')
                ->first();

            if ($existing) {
                $skippedCount++;
                $this->logInfo(sprintf(
                    '[SKIPPED] Class %s - All-Time structure already exists (v%d, %s, BDT %s)',
                    $pkg['class'],
                    (int) $existing->version,
                    $existing->status,
                    number_format((float) $existing->total_amount, 2)
                ));
                continue;
            }

            $items = $this->buildHeads($pkg['tuition'], $pkg['include_admission_fee'], $categories);
            $withoutAdmissionTotal = $pkg['include_admission_fee']
                ? bcsub($pkg['expected_total'], '1000.00', 2)
                : $pkg['expected_total'];

            $structure = $structureService->createStructure([
                'branch_id'                => (int) $branch->id,
                'class_id'                 => (int) $class->id,
                'session_year_id'          => null, // All-Time Default across all sessions
                'structure_name'           => "Class {$pkg['class']} Admission Fee Structure",
                'status'                   => AdmissionFeeStructureService::STATUS_ACTIVE,
                'total_amount'             => $pkg['expected_total'],
                'includes_monthly_tuition' => true,
                'tuition_month_number'     => 1,
                'notes'                    => "Jalalpur Branch All-Time Admission Fee Rule. Total without Admission Fee (existing/promoted reference): {$withoutAdmissionTotal} BDT.",
                'items'                    => $items,
            ], (int) $actor->id);

            $createdCount++;
            $this->logInfo(sprintf(
                '[CREATED] Class %s - %s (%d heads, BDT %s, All-Time Active)',
                $pkg['class'],
                $structure->structure_name,
                count($items),
                number_format((float) $structure->total_amount, 2)
            ));
        }

        $this->logInfo(sprintf(
            'JalalpurAdmissionFeeStructureSeeder finished for %s (Branch #%d): Created %d, Skipped %d.',
            $branch->branch_name,
            (int) $branch->id,
            $createdCount,
            $skippedCount
        ));
    }

    /**
     * Build the exact ordered Admission Fee Head items for a class package.
     *
     * @param  array<string, FeeCategory>  $categories
     * @return array<int, array<string, mixed>>
     */
    private function buildHeads(string $tuitionAmount, bool $includeAdmissionFee, array $categories): array
    {
        $items = [
            [
                'head_name'       => 'Monthly Tuition Fee (January)',
                'fee_category_id' => (int) $categories['Monthly Fee']->id,
                'amount'          => $tuitionAmount,
                'is_discountable' => false,
                'is_tuition_head' => true,
                'sort_order'      => 10,
            ],
            [
                'head_name'       => 'ICT Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '60.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 20,
            ],
            [
                'head_name'       => 'Syllabus Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '100.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 30,
            ],
            [
                'head_name'       => 'Sports Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '200.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 40,
            ],
            [
                'head_name'       => 'Welfare Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '100.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 50,
            ],
            [
                'head_name'       => 'Electricity Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '20.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 60,
            ],
            [
                'head_name'       => 'Session Fee',
                'fee_category_id' => (int) $categories['Session Fee']->id,
                'amount'          => '1200.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 70,
            ],
            [
                'head_name'       => 'Development Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '100.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 80,
            ],
            [
                'head_name'       => 'ID Card Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '100.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 90,
            ],
            [
                'head_name'       => 'Milad Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '100.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 100,
            ],
            [
                'head_name'       => 'Cultural Fee',
                'fee_category_id' => (int) $categories['Others']->id,
                'amount'          => '200.00',
                'is_discountable' => false,
                'is_tuition_head' => false,
                'sort_order'      => 110,
            ],
        ];

        if ($includeAdmissionFee) {
            $items[] = [
                'head_name'       => 'Admission Fee',
                'fee_category_id' => (int) $categories['Admission Fee']->id,
                'amount'          => '1000.00',
                'is_discountable' => true,
                'is_tuition_head' => false,
                'sort_order'      => 120,
            ];
        }

        $items[] = [
            'head_name'       => 'Scout Fee',
            'fee_category_id' => (int) $categories['Others']->id,
            'amount'          => '100.00',
            'is_discountable' => false,
            'is_tuition_head' => false,
            'sort_order'      => 130,
        ];

        $items[] = [
            'head_name'       => 'Science Fee',
            'fee_category_id' => (int) $categories['Others']->id,
            'amount'          => '100.00',
            'is_discountable' => false,
            'is_tuition_head' => false,
            'sort_order'      => 140,
        ];

        return $items;
    }

    private function resolveAdminActor(): ?User
    {
        $users = User::orderBy('id')->get();
        foreach (['super-admin', 'admin'] as $roleSlug) {
            foreach ($users as $user) {
                if (method_exists($user, 'hasRole') && $user->hasRole($roleSlug)) {
                    return $user;
                }
            }
        }

        return $users->first();
    }

    private function resolveOrCreateCategory(string $name, int $actorUserId): FeeCategory
    {
        $existing = FeeCategory::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return FeeCategory::create([
            'name'    => $name,
            'status'  => 'Active',
            'user_id' => $actorUserId,
        ]);
    }

    private function logInfo(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }
}
