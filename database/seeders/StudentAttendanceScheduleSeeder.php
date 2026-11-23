<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\StudentAttendanceSchedule;
use App\Models\StudentAttendanceScheduleSlot;
use App\Models\StudentAttendanceScheduleSlotClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Idempotent seeder for MACS School & College Student Attendance Schedules:
 *   1. Default Normal School Time (2 slots: Morning Play-Three, Day Four-Ten)
 *   2. Reusable Standard Exam Time Template (3 slots: Morning Play-Four, Day Five-Nine, Day Class Ten)
 *
 * Safe to run repeatedly; never mutates `shifts`, `students.shift_id`, or `teachers.shift_id`.
 */
class StudentAttendanceScheduleSeeder extends Seeder
{
    /**
     * Canonical academic class keys mapped to flexible lowercase aliases.
     *
     * @var array<string, list<string>>
     */
    private const CLASS_ALIASES = [
        'play'    => ['play', 'playgroup', 'play group', 'class play'],
        'nursery' => ['nursery', 'class nursery'],
        'kg'      => ['kg', 'k.g', 'k.g.', 'kindergarten', 'prep', 'class kg'],
        'one'     => ['one', '1', 'class 1', 'class one', 'i', 'class i'],
        'two'     => ['two', '2', 'class 2', 'class two', 'ii', 'class ii'],
        'three'   => ['three', '3', 'class 3', 'class three', 'iii', 'class iii'],
        'four'    => ['four', '4', 'class 4', 'class four', 'iv', 'class iv'],
        'five'    => ['five', '5', 'class 5', 'class five', 'v', 'class v'],
        'six'     => ['six', '6', 'class 6', 'class six', 'vi', 'class vi'],
        'seven'   => ['seven', '7', 'class 7', 'class seven', 'vii', 'class vii'],
        'eight'   => ['eight', '8', 'class 8', 'class eight', 'viii', 'class viii'],
        'nine'    => ['nine', '9', 'class 9', 'class nine', 'ix', 'class ix'],
        'ten'     => ['ten', '10', 'class 10', 'class ten', 'x', 'class x'],
    ];

    public function run(): void
    {
        if (
            !Schema::hasTable('student_attendance_schedules')
            || !Schema::hasTable('student_attendance_schedule_slots')
            || !Schema::hasTable('student_attendance_schedule_slot_classes')
        ) {
            throw new RuntimeException(
                'Student attendance schedule migrations must be executed before running StudentAttendanceScheduleSeeder.'
            );
        }

        $actorId = $this->resolveAdminActorId();
        $classLookup = $this->buildCanonicalClassLookup();

        DB::transaction(function () use ($actorId, $classLookup): void {
            // 1. Default Normal School Time
            $normalSchedule = StudentAttendanceSchedule::updateOrCreate(
                [
                    'mode'        => StudentAttendanceSchedule::MODE_NORMAL,
                    'is_default'  => true,
                    'is_template' => false,
                    'branch_id'   => null,
                ],
                [
                    'name'       => 'Normal School Time',
                    'is_active'  => true,
                    'start_date' => null,
                    'end_date'   => null,
                    'exam_id'    => null,
                    'priority'   => 10,
                    'notes'      => 'Default student biometric RFID attendance schedule (Morning: Play-Three, Day: Four-Ten).',
                    'created_by' => $actorId,
                ]
            );

            $normalSummary = $this->syncScheduleSlots($normalSchedule, [
                [
                    'slot_name'                => 'Morning (Play - Three)',
                    'shift_group'              => StudentAttendanceScheduleSlot::SHIFT_MORNING,
                    'on_time_start'            => '07:40:00',
                    'on_time_end'              => '08:15:00',
                    'late_end'                 => '08:30:00',
                    'absent_from'              => '08:31:00',
                    'check_out_from'           => '11:00:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'sort_order'               => 10,
                    'class_keys'               => ['play', 'nursery', 'kg', 'one', 'two', 'three'],
                ],
                [
                    'slot_name'                => 'Day (Four - Ten)',
                    'shift_group'              => StudentAttendanceScheduleSlot::SHIFT_DAY,
                    'on_time_start'            => '11:30:00',
                    'on_time_end'              => '12:15:00',
                    'late_end'                 => '12:20:00',
                    'absent_from'              => '12:21:00',
                    'check_out_from'           => '15:30:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'sort_order'               => 20,
                    'class_keys'               => ['four', 'five', 'six', 'seven', 'eight', 'nine', 'ten'],
                ],
            ], $classLookup);

            // 2. Reusable Standard Exam Time Template (inactive template; never activates automatically)
            $examTemplate = StudentAttendanceSchedule::updateOrCreate(
                [
                    'mode'        => StudentAttendanceSchedule::MODE_EXAM,
                    'is_template' => true,
                    'branch_id'   => null,
                    'name'        => 'Standard Exam Time Template',
                ],
                [
                    'is_default' => false,
                    'is_active'  => false,
                    'start_date' => null,
                    'end_date'   => null,
                    'exam_id'    => null,
                    'priority'   => 20,
                    'notes'      => 'Reusable inactive Exam Time template (Morning: Play-Four, Day: Five-Nine, Dedicated Class Ten).',
                    'created_by' => $actorId,
                ]
            );

            $examSummary = $this->syncScheduleSlots($examTemplate, [
                [
                    'slot_name'                => 'Morning Exam (Play - Four)',
                    'shift_group'              => StudentAttendanceScheduleSlot::SHIFT_MORNING,
                    'on_time_start'            => '08:00:00',
                    'on_time_end'              => '09:00:00',
                    'late_end'                 => '09:10:00',
                    'absent_from'              => '09:11:00',
                    'check_out_from'           => '09:40:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'sort_order'               => 10,
                    'class_keys'               => ['play', 'nursery', 'kg', 'one', 'two', 'three', 'four'],
                ],
                [
                    'slot_name'                => 'Day Exam (Five - Nine)',
                    'shift_group'              => StudentAttendanceScheduleSlot::SHIFT_DAY,
                    'on_time_start'            => '11:30:00',
                    'on_time_end'              => '12:00:00',
                    'late_end'                 => '12:10:00',
                    'absent_from'              => '12:11:00',
                    'check_out_from'           => '13:00:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'sort_order'               => 20,
                    'class_keys'               => ['five', 'six', 'seven', 'eight', 'nine'],
                ],
                [
                    'slot_name'                => 'Day Exam (Class Ten)',
                    'shift_group'              => StudentAttendanceScheduleSlot::SHIFT_DAY,
                    'on_time_start'            => '09:00:00',
                    'on_time_end'              => '09:20:00',
                    'late_end'                 => '09:30:00',
                    'absent_from'              => '09:31:00',
                    'check_out_from'           => '12:50:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'sort_order'               => 30,
                    'class_keys'               => ['ten'],
                ],
            ], $classLookup);

            $this->logInfo(sprintf(
                'StudentAttendanceScheduleSeeder: Normal School Time synced (%d slots, %d class mappings, unmapped keys: %s).',
                $normalSummary['slots'],
                $normalSummary['mapped_count'],
                empty($normalSummary['missing_keys']) ? 'none' : implode(', ', $normalSummary['missing_keys'])
            ));

            $this->logInfo(sprintf(
                'StudentAttendanceScheduleSeeder: Standard Exam Time Template synced (%d slots, %d class mappings, unmapped keys: %s).',
                $examSummary['slots'],
                $examSummary['mapped_count'],
                empty($examSummary['missing_keys']) ? 'none' : implode(', ', $examSummary['missing_keys'])
            ));

            if (!empty($classLookup['unrecognized'])) {
                $this->logInfo(
                    'StudentAttendanceScheduleSeeder: Unrecognized classes in DB (not auto-mapped): '
                    . implode(', ', $classLookup['unrecognized'])
                );
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $slotDefinitions
     * @param  array{by_key: array<string, list<Classes>>, unrecognized: list<string>}  $classLookup
     * @return array{slots: int, mapped_count: int, missing_keys: list<string>}
     */
    private function syncScheduleSlots(
        StudentAttendanceSchedule $schedule,
        array $slotDefinitions,
        array $classLookup
    ): array {
        $keptSlotIds = [];
        $mappedClassIds = [];
        $missingKeys = [];

        foreach ($slotDefinitions as $def) {
            $classKeys = $def['class_keys'];
            unset($def['class_keys']);

            $slot = StudentAttendanceScheduleSlot::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'slot_name'   => $def['slot_name'],
                ],
                $def
            );

            $keptSlotIds[] = $slot->id;

            foreach ($classKeys as $key) {
                $matchedClasses = $classLookup['by_key'][$key] ?? [];
                if (empty($matchedClasses)) {
                    $missingKeys[] = $key;
                    continue;
                }

                foreach ($matchedClasses as $classModel) {
                    StudentAttendanceScheduleSlotClass::updateOrCreate(
                        [
                            'schedule_id' => $schedule->id,
                            'class_id'    => $classModel->id,
                        ],
                        [
                            'slot_id' => $slot->id,
                        ]
                    );
                    $mappedClassIds[$classModel->id] = true;
                }
            }
        }

        // Remove stale slots or mappings belonging only to this seeded schedule if definitions changed
        StudentAttendanceScheduleSlot::where('schedule_id', $schedule->id)
            ->whereNotIn('id', $keptSlotIds)
            ->delete();

        if (!empty($mappedClassIds)) {
            StudentAttendanceScheduleSlotClass::where('schedule_id', $schedule->id)
                ->whereNotIn('class_id', array_keys($mappedClassIds))
                ->delete();
        }

        return [
            'slots'        => count($keptSlotIds),
            'mapped_count' => count($mappedClassIds),
            'missing_keys' => array_values(array_unique($missingKeys)),
        ];
    }

    /**
     * Build a flexible, case-insensitive lookup mapping canonical keys ('play'..'ten') to Classes models.
     *
     * @return array{by_key: array<string, list<Classes>>, unrecognized: list<string>}
     */
    private function buildCanonicalClassLookup(): array
    {
        $aliasToCanonical = [];
        foreach (self::CLASS_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $aliasToCanonical[strtolower(trim($alias))] = $canonical;
            }
        }

        $byKey = [];
        $unrecognized = [];

        foreach (Classes::all() as $classModel) {
            $rawName = trim((string) ($classModel->class_name ?? ''));
            $normalized = strtolower(preg_replace('/\s+/', ' ', $rawName) ?? $rawName);

            if (isset($aliasToCanonical[$normalized])) {
                $canonical = $aliasToCanonical[$normalized];
                $byKey[$canonical][] = $classModel;
            } else {
                $unrecognized[] = $rawName !== '' ? $rawName : ("#{$classModel->id}");
            }
        }

        return [
            'by_key'       => $byKey,
            'unrecognized' => $unrecognized,
        ];
    }

    private function resolveAdminActorId(): ?int
    {
        $users = User::orderBy('id')->get();
        foreach (['super-admin', 'admin'] as $roleSlug) {
            foreach ($users as $user) {
                if (method_exists($user, 'hasRole') && $user->hasRole($roleSlug)) {
                    return (int) $user->id;
                }
            }
        }

        $first = $users->first();

        return $first ? (int) $first->id : null;
    }

    private function logInfo(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }
}
