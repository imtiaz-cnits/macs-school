<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\Shift;
use App\Models\StudentAttendanceSchedule;
use App\Models\StudentAttendanceScheduleSlot;
use App\Models\StudentAttendanceScheduleSlotClass;
use App\Services\StudentAttendanceScheduleResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentAttendanceScheduleController extends Controller
{
    private const TIMEZONE = 'Asia/Dhaka';

    public function __construct(
        private readonly StudentAttendanceScheduleResolver $resolver,
    ) {
    }

    /**
     * Display the Admin Student Attendance Schedule Manager page.
     */
    public function index(): View
    {
        $today = Carbon::today(self::TIMEZONE);
        $todayStr = $today->toDateString();

        $classes = Classes::all(); // Academically ordered via global scope (Play -> Ten)
        $academicPosition = [];
        foreach ($classes->values() as $pos => $cls) {
            $academicPosition[(int) $cls->id] = $pos;
        }

        $branches = Branch::orderBy('branch_name')->get();
        $exams = Exam::with('sessionYear')->orderByDesc('id')->get();

        $todayOverview = $this->resolver->getEffectiveScheduleOverview($today);
        $todayOverview['slots'] = $this->enrichResolvedSlots($todayOverview['slots']);

        $normalSchedule = StudentAttendanceSchedule::with(['slots.classes', 'branch'])
            ->where('mode', StudentAttendanceSchedule::MODE_NORMAL)
            ->where('is_default', true)
            ->where('is_template', false)
            ->orderBy('id')
            ->first();

        $examTemplate = StudentAttendanceSchedule::with(['slots.classes'])
            ->templates()
            ->orderBy('id')
            ->first();

        $examCampaigns = StudentAttendanceSchedule::with(['slots.classes', 'branch', 'exam'])
            ->where('mode', StudentAttendanceSchedule::MODE_EXAM)
            ->where('is_template', false)
            ->get()
            ->map(function (StudentAttendanceSchedule $schedule) use ($todayStr, $academicPosition): array {
                return $this->serializeSchedule($schedule, $todayStr, $academicPosition);
            })
            ->sort(function (array $a, array $b): int {
                $rank = [
                    'active_now' => 0,
                    'upcoming'   => 1,
                    'expired'    => 2,
                    'inactive'   => 3,
                ];
                $cmp = ($rank[$a['status_code']] ?? 9) <=> ($rank[$b['status_code']] ?? 9);
                if ($cmp !== 0) {
                    return $cmp;
                }
                $dateCmp = strcmp((string) ($b['start_date'] ?? ''), (string) ($a['start_date'] ?? ''));
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }

                return $b['id'] <=> $a['id'];
            })
            ->values()
            ->all();

        $normalScheduleData = $normalSchedule
            ? $this->serializeSchedule($normalSchedule, $todayStr, $academicPosition)
            : null;

        $examTemplateData = $examTemplate
            ? $this->serializeSchedule($examTemplate, $todayStr, $academicPosition)
            : null;

        $classesData = $classes->map(fn (Classes $c) => [
            'id'         => (int) $c->id,
            'class_name' => (string) $c->class_name,
        ])->values()->all();

        $branchesData = $branches->map(fn (Branch $b) => [
            'id'          => (int) $b->id,
            'branch_name' => (string) $b->branch_name,
        ])->values()->all();

        $examsData = $exams->map(fn (Exam $e) => [
            'id'           => (int) $e->id,
            'name'         => (string) $e->name,
            'session_name' => (string) ($e->sessionYear?->session_name ?? ''),
            'start_date'   => $e->start_date ? Carbon::parse($e->start_date, self::TIMEZONE)->toDateString() : null,
            'end_date'     => $e->end_date ? Carbon::parse($e->end_date, self::TIMEZONE)->toDateString() : null,
        ])->values()->all();

        return view('pages.attendance.schedules', [
            'todayDate'          => $todayStr,
            'todayFormatted'     => $today->format('d M, Y'),
            'todayOverview'      => $todayOverview,
            'normalSchedule'     => $normalScheduleData,
            'examTemplate'       => $examTemplateData,
            'examCampaigns'      => $examCampaigns,
            'classesData'        => $classesData,
            'branchesData'       => $branchesData,
            'examsData'          => $examsData,
        ]);
    }

    /**
     * Create a new student attendance schedule (typically an Exam Time campaign).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateSchedulePayload($request, null);

        $schedule = DB::transaction(function () use ($validated): StudentAttendanceSchedule {
            $mode = $validated['mode'];
            $isNormal = $mode === StudentAttendanceSchedule::MODE_NORMAL;

            $schedule = StudentAttendanceSchedule::create([
                'name'        => $validated['name'],
                'mode'        => $mode,
                'is_default'  => $isNormal && !StudentAttendanceSchedule::defaultNormal()->exists(),
                'is_template' => false,
                'is_active'   => $isNormal ? true : (bool) ($validated['is_active'] ?? true),
                'start_date'  => $isNormal ? null : $validated['start_date'],
                'end_date'    => $isNormal ? null : $validated['end_date'],
                'exam_id'     => $isNormal ? null : ($validated['exam_id'] ?? null),
                'branch_id'   => $validated['branch_id'] ?? null,
                'priority'    => (int) ($validated['priority'] ?? ($isNormal ? 10 : 20)),
                'notes'       => $validated['notes'] ?? null,
                'created_by'  => Auth::id(),
            ]);

            $this->syncSlotsAndClasses($schedule, $validated['slots']);

            return $schedule->load(['slots.classes', 'branch', 'exam']);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => "Schedule '{$schedule->name}' created successfully.",
                'schedule' => $this->serializeScheduleForResponse($schedule),
            ], 201);
        }

        return redirect()->route('attendance.schedules.index')
            ->with('success', "Schedule '{$schedule->name}' created successfully.");
    }

    /**
     * Update an existing Normal School Time or Exam Time schedule and its slots/class mappings.
     */
    public function update(Request $request, StudentAttendanceSchedule $schedule): JsonResponse|RedirectResponse
    {
        if ($schedule->is_template) {
            return $this->errorResponse(
                $request,
                'System schedule templates cannot be modified directly. Create an exam campaign from the template instead.',
                422
            );
        }

        $validated = $this->validateSchedulePayload($request, $schedule);

        $updated = DB::transaction(function () use ($schedule, $validated): StudentAttendanceSchedule {
            $isDefaultNormal = $schedule->is_default;
            $mode = $isDefaultNormal ? StudentAttendanceSchedule::MODE_NORMAL : $validated['mode'];
            $isNormal = $mode === StudentAttendanceSchedule::MODE_NORMAL;

            $schedule->update([
                'name'        => $validated['name'],
                'mode'        => $mode,
                'is_default'  => $isDefaultNormal,
                'is_template' => false,
                'is_active'   => $isDefaultNormal ? true : (bool) ($validated['is_active'] ?? $schedule->is_active),
                'start_date'  => $isNormal ? null : $validated['start_date'],
                'end_date'    => $isNormal ? null : $validated['end_date'],
                'exam_id'     => $isNormal ? null : ($validated['exam_id'] ?? null),
                'branch_id'   => $isDefaultNormal ? $schedule->branch_id : ($validated['branch_id'] ?? null),
                'priority'    => (int) ($validated['priority'] ?? $schedule->priority),
                'notes'       => $validated['notes'] ?? null,
            ]);

            $this->syncSlotsAndClasses($schedule, $validated['slots']);

            return $schedule->fresh(['slots.classes', 'branch', 'exam']);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'status'   => 'success',
                'message'  => "Schedule '{$updated->name}' updated successfully.",
                'schedule' => $this->serializeScheduleForResponse($updated),
            ], 200);
        }

        return redirect()->route('attendance.schedules.index')
            ->with('success', "Schedule '{$updated->name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status of an Exam Time campaign.
     */
    public function toggleStatus(Request $request, StudentAttendanceSchedule $schedule): JsonResponse|RedirectResponse
    {
        if ($schedule->is_default) {
            return $this->errorResponse(
                $request,
                'The default Normal School Time schedule cannot be deactivated.',
                422
            );
        }

        if ($schedule->is_template) {
            return $this->errorResponse(
                $request,
                'System schedule templates cannot be activated directly. Create an exam campaign from the template instead.',
                422
            );
        }

        $schedule->is_active = !$schedule->is_active;
        $schedule->save();

        $stateLabel = $schedule->is_active ? 'activated' : 'deactivated';
        $message = "Campaign '{$schedule->name}' has been {$stateLabel}.";

        if ($request->expectsJson()) {
            return response()->json([
                'status'    => 'success',
                'message'   => $message,
                'is_active' => (bool) $schedule->is_active,
                'schedule'  => $this->serializeScheduleForResponse($schedule->load(['slots.classes', 'branch', 'exam'])),
            ], 200);
        }

        return redirect()->route('attendance.schedules.index')->with('success', $message);
    }

    /**
     * Delete an Exam Time campaign (default Normal schedule and system template are protected).
     */
    public function destroy(Request $request, StudentAttendanceSchedule $schedule): JsonResponse|RedirectResponse
    {
        if ($schedule->is_default) {
            return $this->errorResponse(
                $request,
                'The default Normal School Time schedule cannot be deleted.',
                422
            );
        }

        if ($schedule->is_template) {
            return $this->errorResponse(
                $request,
                'System schedule templates cannot be deleted.',
                422
            );
        }

        $name = $schedule->name;

        DB::transaction(function () use ($schedule): void {
            $schedule->delete();
        });

        $message = "Campaign '{$name}' deleted successfully.";

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $message,
            ], 200);
        }

        return redirect()->route('attendance.schedules.index')->with('success', $message);
    }

    /**
     * AJAX Endpoint returning the effective Student & Staff attendance time-table for a date and optional branch.
     */
    public function effectiveSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'date'      => 'nullable|date',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $date = $request->filled('date')
            ? Carbon::parse((string) $request->input('date'), self::TIMEZONE)->toDateString()
            : Carbon::today(self::TIMEZONE)->toDateString();

        $branchId = $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : null;

        $overview = $this->resolver->getEffectiveScheduleOverview($date, $branchId);
        $studentSlots = $this->enrichResolvedSlots($overview['slots']);

        $staffShifts = Shift::where('type', 'staff')
            ->orderBy('in_time_start')
            ->orderBy('id')
            ->get()
            ->filter(fn (Shift $s) => !empty($s->in_time_start) || !empty($s->in_time_end) || !empty($s->out_time))
            ->values()
            ->map(function (Shift $s): array {
                return [
                    'id'            => (int) $s->id,
                    'name'          => (string) ($s->name ?: $s->shift_name ?: 'Staff Shift'),
                    'shift_name'    => (string) ($s->shift_name ?: $s->name ?: 'Staff Shift'),
                    'type'          => 'staff',
                    'in_time_start' => $s->in_time_start,
                    'in_time_end'   => $s->in_time_end,
                    'late_time_end' => $s->late_time_end,
                    'absent_time'   => $s->absent_time,
                    'out_time'      => $s->out_time,
                ];
            })
            ->all();

        return response()->json([
            'status'           => 'success',
            'date'             => $overview['date'],
            'branch_id'        => $overview['branch_id'],
            'mode'             => $overview['active_mode'],
            'active_campaigns' => $overview['exam_campaigns'],
            'student_slots'    => $studentSlots,
            'staff_shifts'     => $staffShifts,
        ], 200);
    }

    /**
     * Validate schedule payload with strict chronological time & duplicate-class checks.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validateSchedulePayload(Request $request, ?StudentAttendanceSchedule $existing): array
    {
        $effectiveMode = ($existing && $existing->is_default)
            ? StudentAttendanceSchedule::MODE_NORMAL
            : (string) $request->input('mode', StudentAttendanceSchedule::MODE_EXAM);

        $timeRegex = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';

        $rules = [
            'name'                             => ['required', 'string', 'max:150'],
            'mode'                             => ['required', 'in:normal,exam'],
            'branch_id'                        => ['nullable', 'integer', 'exists:branches,id'],
            'exam_id'                          => ['nullable', 'integer', 'exists:exams,id'],
            'priority'                         => ['nullable', 'integer', 'min:1', 'max:999'],
            'is_active'                        => ['nullable', 'boolean'],
            'notes'                            => ['nullable', 'string', 'max:1000'],
            'start_date'                       => $effectiveMode === StudentAttendanceSchedule::MODE_EXAM
                ? ['required', 'date']
                : ['nullable', 'date'],
            'end_date'                         => $effectiveMode === StudentAttendanceSchedule::MODE_EXAM
                ? ['required', 'date', 'after_or_equal:start_date']
                : ['nullable', 'date', 'after_or_equal:start_date'],
            'slots'                            => ['required', 'array', 'min:1'],
            'slots.*.id'                       => ['nullable', 'integer'],
            'slots.*.slot_name'                => ['required', 'string', 'max:100'],
            'slots.*.shift_group'              => ['required', 'in:Morning,Day'],
            'slots.*.on_time_start'            => ['required', 'string', "regex:{$timeRegex}"],
            'slots.*.on_time_end'              => ['required', 'string', "regex:{$timeRegex}"],
            'slots.*.late_end'                 => ['required', 'string', "regex:{$timeRegex}"],
            'slots.*.absent_from'              => ['required', 'string', "regex:{$timeRegex}"],
            'slots.*.check_out_from'           => ['required', 'string', "regex:{$timeRegex}"],
            'slots.*.early_grace_minutes'      => ['nullable', 'integer', 'min:0', 'max:180'],
            'slots.*.debounce_minutes'         => ['nullable', 'integer', 'min:0', 'max:60'],
            'slots.*.min_checkout_gap_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'slots.*.class_ids'                => ['required', 'array', 'min:1'],
            'slots.*.class_ids.*'              => ['required', 'integer', 'exists:classes,id'],
        ];

        $messages = [
            'slots.required'             => 'At least one time slot is required.',
            'slots.*.class_ids.required' => 'Each time slot must have at least one class assigned.',
            'slots.*.class_ids.min'      => 'Each time slot must have at least one class assigned.',
            'end_date.after_or_equal'    => 'End date must be on or after the start date.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $validator->after(function ($v) use ($request): void {
            $slots = $request->input('slots');
            if (!is_array($slots)) {
                return;
            }

            $classNamesById = Classes::pluck('class_name', 'id')->all();
            $assignedClassOwners = [];

            foreach ($slots as $idx => $slot) {
                if (!is_array($slot)) {
                    continue;
                }

                $slotLabel = trim((string) ($slot['slot_name'] ?? '')) ?: ('Slot #' . ($idx + 1));

                $tStart = $this->normalizeTimeOrNull($slot['on_time_start'] ?? null);
                $tEnd = $this->normalizeTimeOrNull($slot['on_time_end'] ?? null);
                $tLate = $this->normalizeTimeOrNull($slot['late_end'] ?? null);
                $tAbsent = $this->normalizeTimeOrNull($slot['absent_from'] ?? null);
                $tOut = $this->normalizeTimeOrNull($slot['check_out_from'] ?? null);

                if ($tStart && $tEnd && $tStart >= $tEnd) {
                    $v->errors()->add(
                        "slots.{$idx}.on_time_end",
                        "{$slotLabel}: On-Time End must be strictly after On-Time Start."
                    );
                }

                if ($tEnd && $tLate && $tEnd >= $tLate) {
                    $v->errors()->add(
                        "slots.{$idx}.late_end",
                        "{$slotLabel}: Late End must be strictly after On-Time End."
                    );
                }

                if ($tLate && $tAbsent && $tLate >= $tAbsent) {
                    $v->errors()->add(
                        "slots.{$idx}.absent_from",
                        "{$slotLabel}: Absent From must be strictly after Late End."
                    );
                }

                if ($tAbsent && $tOut && $tAbsent >= $tOut) {
                    $v->errors()->add(
                        "slots.{$idx}.check_out_from",
                        "{$slotLabel}: Check-Out From must be strictly after Absent From."
                    );
                }

                $classIds = $slot['class_ids'] ?? [];
                if (is_array($classIds)) {
                    $seenInSlot = [];
                    foreach ($classIds as $rawClassId) {
                        $cid = (int) $rawClassId;
                        if ($cid <= 0) {
                            continue;
                        }
                        if (isset($seenInSlot[$cid])) {
                            continue;
                        }
                        $seenInSlot[$cid] = true;

                        if (isset($assignedClassOwners[$cid])) {
                            $className = $classNamesById[$cid] ?? "Class #{$cid}";
                            $ownerSlot = $assignedClassOwners[$cid];
                            $v->errors()->add(
                                "slots.{$idx}.class_ids",
                                "Class '{$className}' is already assigned to '{$ownerSlot}'. Each class can belong to only one slot per schedule."
                            );
                        } else {
                            $assignedClassOwners[$cid] = $slotLabel;
                        }
                    }
                }
            }
        });

        $validated = $validator->validate();

        // Normalize all slot times to HH:MM:SS and deduplicate class_ids per slot
        foreach ($validated['slots'] as $idx => $slot) {
            $validated['slots'][$idx]['on_time_start'] = $this->normalizeTime((string) $slot['on_time_start']);
            $validated['slots'][$idx]['on_time_end'] = $this->normalizeTime((string) $slot['on_time_end']);
            $validated['slots'][$idx]['late_end'] = $this->normalizeTime((string) $slot['late_end']);
            $validated['slots'][$idx]['absent_from'] = $this->normalizeTime((string) $slot['absent_from']);
            $validated['slots'][$idx]['check_out_from'] = $this->normalizeTime((string) $slot['check_out_from']);
            $validated['slots'][$idx]['early_grace_minutes'] = (int) ($slot['early_grace_minutes'] ?? 60);
            $validated['slots'][$idx]['debounce_minutes'] = (int) ($slot['debounce_minutes'] ?? 10);
            $validated['slots'][$idx]['min_checkout_gap_minutes'] = (int) ($slot['min_checkout_gap_minutes'] ?? 15);
            $validated['slots'][$idx]['class_ids'] = array_values(array_unique(array_map('intval', $slot['class_ids'])));
        }

        if ($effectiveMode === StudentAttendanceSchedule::MODE_EXAM) {
            $validated['start_date'] = Carbon::parse((string) $validated['start_date'], self::TIMEZONE)->toDateString();
            $validated['end_date'] = Carbon::parse((string) $validated['end_date'], self::TIMEZONE)->toDateString();
        }

        return $validated;
    }

    /**
     * Sync slots (updating matching slot IDs in-place to preserve historical `attendances.schedule_slot_id` FKs)
     * and rebuild class mappings cleanly inside the active DB transaction.
     *
     * @param  list<array<string, mixed>>  $slotPayloads
     */
    private function syncSlotsAndClasses(StudentAttendanceSchedule $schedule, array $slotPayloads): void
    {
        // Clear schedule-scoped class mappings first so moving a class across slots never collides with UNIQUE(schedule_id, class_id)
        StudentAttendanceScheduleSlotClass::where('schedule_id', $schedule->id)->delete();

        $existingSlotsById = StudentAttendanceScheduleSlot::where('schedule_id', $schedule->id)
            ->get()
            ->keyBy('id');

        $keptSlotIds = [];

        foreach ($slotPayloads as $idx => $payload) {
            $slotAttributes = [
                'schedule_id'              => $schedule->id,
                'slot_name'                => trim((string) $payload['slot_name']),
                'shift_group'              => (string) $payload['shift_group'],
                'on_time_start'            => $payload['on_time_start'],
                'on_time_end'              => $payload['on_time_end'],
                'late_end'                 => $payload['late_end'],
                'absent_from'              => $payload['absent_from'],
                'check_out_from'           => $payload['check_out_from'],
                'early_grace_minutes'      => $payload['early_grace_minutes'],
                'debounce_minutes'         => $payload['debounce_minutes'],
                'min_checkout_gap_minutes' => $payload['min_checkout_gap_minutes'],
                'sort_order'               => ($idx + 1) * 10,
            ];

            $requestedId = isset($payload['id']) ? (int) $payload['id'] : null;
            if ($requestedId !== null && $existingSlotsById->has($requestedId)) {
                /** @var StudentAttendanceScheduleSlot $slot */
                $slot = $existingSlotsById->get($requestedId);
                $slot->update($slotAttributes);
            } else {
                $slot = StudentAttendanceScheduleSlot::create($slotAttributes);
            }

            $keptSlotIds[] = $slot->id;

            foreach ($payload['class_ids'] as $classId) {
                StudentAttendanceScheduleSlotClass::create([
                    'schedule_id' => $schedule->id,
                    'slot_id'     => $slot->id,
                    'class_id'    => (int) $classId,
                ]);
            }
        }

        StudentAttendanceScheduleSlot::where('schedule_id', $schedule->id)
            ->whereNotIn('id', $keptSlotIds)
            ->delete();
    }

    /**
     * Add computed `late_start` (`on_time_end + 1 min`) to resolved slot arrays for UI display.
     *
     * @param  list<array<string, mixed>>  $slots
     * @return list<array<string, mixed>>
     */
    private function enrichResolvedSlots(array $slots): array
    {
        return array_map(function (array $slot): array {
            $slot['late_start'] = $this->computeLateStart(
                (string) ($slot['on_time_end'] ?? ''),
                (string) ($slot['late_end'] ?? '')
            );

            return $slot;
        }, $slots);
    }

    /**
     * @param  array<int, int>  $academicPosition
     * @return array<string, mixed>
     */
    private function serializeSchedule(
        StudentAttendanceSchedule $schedule,
        string $todayStr,
        array $academicPosition
    ): array {
        $startStr = $schedule->start_date ? $schedule->start_date->format('Y-m-d') : null;
        $endStr = $schedule->end_date ? $schedule->end_date->format('Y-m-d') : null;

        [$statusCode, $statusLabel] = $this->resolveCampaignStatus($schedule, $todayStr, $startStr, $endStr);

        $allMappedClasses = [];
        $slots = [];

        foreach ($schedule->slots as $slot) {
            $slotClasses = $slot->classes
                ->sortBy(fn (Classes $c) => $academicPosition[(int) $c->id] ?? 999)
                ->values();

            $classList = $slotClasses->map(fn (Classes $c) => [
                'id'         => (int) $c->id,
                'class_name' => (string) $c->class_name,
            ])->all();

            foreach ($classList as $cItem) {
                $allMappedClasses[$cItem['id']] = $cItem;
            }

            $onTimeStart = $this->normalizeTime((string) $slot->on_time_start);
            $onTimeEnd = $this->normalizeTime((string) $slot->on_time_end);
            $lateEnd = $this->normalizeTime((string) $slot->late_end);
            $absentFrom = $this->normalizeTime((string) $slot->absent_from);
            $checkOutFrom = $this->normalizeTime((string) $slot->check_out_from);

            $slots[] = [
                'id'                       => (int) $slot->id,
                'slot_name'                => (string) $slot->slot_name,
                'shift_group'              => (string) $slot->shift_group,
                'on_time_start'            => $onTimeStart,
                'on_time_end'              => $onTimeEnd,
                'late_start'               => $this->computeLateStart($onTimeEnd, $lateEnd),
                'late_end'                 => $lateEnd,
                'absent_from'              => $absentFrom,
                'check_out_from'           => $checkOutFrom,
                'early_grace_minutes'      => (int) $slot->early_grace_minutes,
                'debounce_minutes'         => (int) $slot->debounce_minutes,
                'min_checkout_gap_minutes' => (int) $slot->min_checkout_gap_minutes,
                'class_ids'                => array_column($classList, 'id'),
                'classes'                  => $classList,
                'class_summary'            => $this->formatClassRangeSummary($classList, $academicPosition),
            ];
        }

        uasort($allMappedClasses, fn (array $a, array $b): int => ($academicPosition[$a['id']] ?? 999) <=> ($academicPosition[$b['id']] ?? 999));
        $orderedCampaignClasses = array_values($allMappedClasses);

        return [
            'id'              => (int) $schedule->id,
            'name'            => (string) $schedule->name,
            'mode'            => (string) $schedule->mode,
            'is_default'      => (bool) $schedule->is_default,
            'is_template'     => (bool) $schedule->is_template,
            'is_active'       => (bool) $schedule->is_active,
            'start_date'      => $startStr,
            'end_date'        => $endStr,
            'date_range_text' => ($startStr && $endStr)
                ? Carbon::parse($startStr, self::TIMEZONE)->format('d M, Y') . ' – ' . Carbon::parse($endStr, self::TIMEZONE)->format('d M, Y')
                : 'Always Active (Default)',
            'exam_id'         => $schedule->exam_id !== null ? (int) $schedule->exam_id : null,
            'exam_name'       => $schedule->exam?->name,
            'branch_id'       => $schedule->branch_id !== null ? (int) $schedule->branch_id : null,
            'branch_name'     => $schedule->branch?->branch_name ?? 'All Branches',
            'priority'        => (int) $schedule->priority,
            'notes'           => $schedule->notes,
            'status_code'     => $statusCode,
            'status_label'    => $statusLabel,
            'class_summary'   => $this->formatClassRangeSummary($orderedCampaignClasses, $academicPosition),
            'class_count'     => count($orderedCampaignClasses),
            'slots'           => $slots,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeScheduleForResponse(StudentAttendanceSchedule $schedule): array
    {
        $classes = Classes::all();
        $academicPosition = [];
        foreach ($classes->values() as $pos => $cls) {
            $academicPosition[(int) $cls->id] = $pos;
        }

        return $this->serializeSchedule(
            $schedule,
            Carbon::today(self::TIMEZONE)->toDateString(),
            $academicPosition
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveCampaignStatus(
        StudentAttendanceSchedule $schedule,
        string $todayStr,
        ?string $startStr,
        ?string $endStr
    ): array {
        if ($schedule->is_template) {
            return ['template', 'Template'];
        }

        if ($schedule->is_default) {
            return ['active_now', 'Active Default'];
        }

        if (!$schedule->is_active) {
            return ['inactive', 'Inactive'];
        }

        if ($startStr !== null && $endStr !== null) {
            if ($todayStr < $startStr) {
                return ['upcoming', 'Upcoming'];
            }
            if ($todayStr > $endStr) {
                return ['expired', 'Expired'];
            }

            return ['active_now', 'Active Now'];
        }

        return ['active_now', 'Active Now'];
    }

    private function computeLateStart(string $onTimeEnd, string $lateEnd): string
    {
        if ($onTimeEnd === '') {
            return $lateEnd;
        }

        $start = Carbon::createFromFormat('H:i:s', $this->normalizeTime($onTimeEnd), self::TIMEZONE)
            ->addMinute()
            ->setSecond(0);

        return $start->format('H:i:s');
    }

    private function normalizeTime(string $time): string
    {
        $trimmed = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $trimmed, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
        }

        return Carbon::parse($trimmed, self::TIMEZONE)->format('H:i:s');
    }

    private function normalizeTimeOrNull(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', trim($value))) {
            return null;
        }

        return $this->normalizeTime($value);
    }

    /**
     * @param  list<array{id: int, class_name: string}>  $slotClasses
     * @param  array<int, int>  $academicPosition
     */
    private function formatClassRangeSummary(array $slotClasses, array $academicPosition): string
    {
        $count = count($slotClasses);
        if ($count === 0) {
            return 'No Classes';
        }
        if ($count === 1) {
            return $slotClasses[0]['class_name'];
        }
        if ($count === 2) {
            return "{$slotClasses[0]['class_name']}, {$slotClasses[1]['class_name']}";
        }

        $isContiguous = true;
        for ($i = 1; $i < $count; $i++) {
            $prevPos = $academicPosition[$slotClasses[$i - 1]['id']] ?? null;
            $currPos = $academicPosition[$slotClasses[$i]['id']] ?? null;
            if ($prevPos === null || $currPos === null || $currPos !== $prevPos + 1) {
                $isContiguous = false;
                break;
            }
        }

        if ($isContiguous) {
            return "{$slotClasses[0]['class_name']} - {$slotClasses[$count - 1]['class_name']}";
        }

        return implode(', ', array_column($slotClasses, 'class_name'));
    }

    private function errorResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'error',
                'message' => $message,
            ], $status);
        }

        return redirect()->route('attendance.schedules.index')->with('error', $message);
    }
}
