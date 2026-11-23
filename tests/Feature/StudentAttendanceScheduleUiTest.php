<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentAttendanceSchedule;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\StudentAttendanceScheduleSeeder;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAttendanceScheduleUiTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $editor;
    private User $regularUser;
    private Branch $branchMain;
    private Branch $branchTown;
    private SessionYear $session;
    private Section $section;
    private Exam $termExam;
    /** @var array<string, Classes> */
    private array $classes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);

        $this->superAdmin = User::create([
            'name'     => 'Super Admin User',
            'email'    => 'p4_superadmin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->admin = User::create([
            'name'     => 'Admin User',
            'email'    => 'p4_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->editor = User::create([
            'name'     => 'Editor User',
            'email'    => 'p4_editor@macsschool.test',
            'password' => bcrypt('secret'),
        ]);
        $this->editor->assignRole($editorRole);

        $this->regularUser = User::create([
            'name'     => 'Plain User',
            'email'    => 'p4_plain@macsschool.test',
            'password' => bcrypt('secret'),
        ]);

        $this->branchMain = Branch::create(['branch_name' => 'Jalalpur Branch', 'user_id' => $this->superAdmin->id]);
        $this->branchTown = Branch::create(['branch_name' => 'Town Branch', 'user_id' => $this->superAdmin->id]);
        $this->session = SessionYear::create(['session_name' => '2026', 'user_id' => $this->superAdmin->id]);
        $this->section = Section::create(['section_name' => 'Section A', 'user_id' => $this->superAdmin->id]);

        $this->termExam = Exam::create([
            'name'            => '1st Term Exam 2026',
            'session_year_id' => $this->session->id,
            'start_date'      => '2026-12-01',
            'end_date'        => '2026-12-10',
            'status'          => 'upcoming',
        ]);

        foreach (['Play', 'Nursery', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'] as $name) {
            $this->classes[$name] = Classes::create(['class_name' => $name, 'user_id' => $this->superAdmin->id]);
        }

        // Seed existing Staff shifts (must remain visible and completely untouched)
        Shift::create([
            'name'          => 'Morning Staff',
            'shift_name'    => 'Morning Staff',
            'type'          => 'staff',
            'in_time_start' => '07:30:00',
            'in_time_end'   => '08:30:00',
            'late_time_end' => '09:00:00',
            'absent_time'   => '09:01:00',
            'out_time'      => '12:00:00',
            'user_id'       => $this->superAdmin->id,
        ]);

        Shift::create([
            'name'          => 'Day Staff',
            'shift_name'    => 'Day Staff',
            'type'          => 'staff',
            'in_time_start' => '11:30:00',
            'in_time_end'   => '12:00:00',
            'late_time_end' => '12:30:00',
            'absent_time'   => '12:31:00',
            'out_time'      => '16:00:00',
            'user_id'       => $this->superAdmin->id,
        ]);

        $this->seed(StudentAttendanceScheduleSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Helper to build a valid 3-slot Full Exam payload matching the Standard Exam Template.
     *
     * @return array<string, mixed>
     */
    private function buildFullExamPayload(array $overrides = []): array
    {
        return array_merge([
            'name'       => '1st Term Exam 2026',
            'mode'       => 'exam',
            'branch_id'  => null,
            'exam_id'    => $this->termExam->id,
            'start_date' => '2026-12-01',
            'end_date'   => '2026-12-10',
            'priority'   => 20,
            'is_active'  => true,
            'notes'      => 'Standard term exam timetable.',
            'slots'      => [
                [
                    'slot_name'                => 'Morning Exam (Play - Four)',
                    'shift_group'              => 'Morning',
                    'on_time_start'            => '08:00:00',
                    'on_time_end'              => '09:00:00',
                    'late_end'                 => '09:10:00',
                    'absent_from'              => '09:11:00',
                    'check_out_from'           => '09:40:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => [
                        $this->classes['Play']->id,
                        $this->classes['Nursery']->id,
                        $this->classes['One']->id,
                        $this->classes['Two']->id,
                        $this->classes['Three']->id,
                        $this->classes['Four']->id,
                    ],
                ],
                [
                    'slot_name'                => 'Day Exam (Five - Nine)',
                    'shift_group'              => 'Day',
                    'on_time_start'            => '11:30:00',
                    'on_time_end'              => '12:00:00',
                    'late_end'                 => '12:10:00',
                    'absent_from'              => '12:11:00',
                    'check_out_from'           => '13:00:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => [
                        $this->classes['Five']->id,
                        $this->classes['Six']->id,
                        $this->classes['Seven']->id,
                        $this->classes['Eight']->id,
                        $this->classes['Nine']->id,
                    ],
                ],
                [
                    'slot_name'                => 'Day Exam (Class Ten)',
                    'shift_group'              => 'Day',
                    'on_time_start'            => '09:00:00',
                    'on_time_end'              => '09:20:00',
                    'late_end'                 => '09:30:00',
                    'absent_from'              => '09:31:00',
                    'check_out_from'           => '12:50:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => [
                        $this->classes['Ten']->id,
                    ],
                ],
            ],
        ], $overrides);
    }

    /**
     * 1. RBAC Enforcement: Admin & Super Admin allowed; Guest, Editor & Plain User denied.
     */
    public function test_rbac_enforcement_for_schedule_manager_and_endpoints(): void
    {
        $normal = StudentAttendanceSchedule::defaultNormal()->firstOrFail();

        // Guest is redirected to login
        $this->get(route('attendance.schedules.index'))->assertRedirect();
        $this->getJson(route('attendance.effective-schedule'))->assertStatus(401);

        // Editor & Plain User are denied on all Schedule Manager and AJAX routes
        foreach ([$this->editor, $this->regularUser] as $unauthorized) {
            $this->actingAs($unauthorized);

            $resIndex = $this->get(route('attendance.schedules.index'));
            $this->assertTrue(in_array($resIndex->status(), [302, 403], true), 'Non-admin must be denied GET schedules.');

            $resStore = $this->postJson(route('attendance.schedules.store'), $this->buildFullExamPayload());
            $this->assertTrue(in_array($resStore->status(), [302, 403], true), 'Non-admin must be denied POST schedules.');

            $resToggle = $this->postJson(route('attendance.schedules.toggle', $normal));
            $this->assertTrue(in_array($resToggle->status(), [302, 403], true), 'Non-admin must be denied toggle.');

            $resDelete = $this->deleteJson(route('attendance.schedules.destroy', $normal));
            $this->assertTrue(in_array($resDelete->status(), [302, 403], true), 'Non-admin must be denied delete.');

            $resEffective = $this->getJson(route('attendance.effective-schedule'));
            $this->assertTrue(in_array($resEffective->status(), [302, 403], true), 'Non-admin must be denied effective-schedule AJAX.');
        }

        // Admin & Super Admin can access Schedule Manager and Daily Attendance views cleanly
        foreach ([$this->admin, $this->superAdmin] as $authorized) {
            $this->actingAs($authorized);

            $this->get(route('attendance.schedules.index'))
                ->assertStatus(200)
                ->assertSee('Student Attendance Schedules')
                ->assertSee('Normal School Time')
                ->assertSee('Create Exam Schedule');

            $this->get(route('attendance.index'))
                ->assertStatus(200)
                ->assertSee('Standard RFID &amp; Fingerprint Attendance Time-Table', false)
                ->assertSee('Manage Schedules')
                ->assertSee('Attendance Schedules');
        }
    }

    /**
     * 2. Schedule CRUD & 1-Click Template Workflow:
     *    Create Full 3-slot Exam Campaign, Create Partial Monthly Test, Update Normal Time (preserving slot IDs),
     *    Toggle Active/Inactive, and Delete Exam Campaign.
     */
    public function test_schedule_crud_full_exam_partial_monthly_test_normal_update_toggle_and_delete(): void
    {
        $this->actingAs($this->superAdmin);

        // A. Create full 3-slot Exam Campaign from template payload
        $createRes = $this->postJson(route('attendance.schedules.store'), $this->buildFullExamPayload());
        $createRes->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'schedule' => [
                    'name'        => '1st Term Exam 2026',
                    'mode'        => 'exam',
                    'is_active'   => true,
                    'class_count' => 12,
                ],
            ]);

        $examId = (int) $createRes->json('schedule.id');
        $examCampaign = StudentAttendanceSchedule::with('slots.classes')->findOrFail($examId);
        $this->assertCount(3, $examCampaign->slots);
        $this->assertSame(12, $examCampaign->slotClasses()->count());

        // B. Create Partial Monthly Exam Campaign (Classes Nine & Ten only)
        $monthlyRes = $this->postJson(route('attendance.schedules.store'), [
            'name'       => 'Monthly Test (Class 9-10)',
            'mode'       => 'exam',
            'start_date' => '2026-12-15',
            'end_date'   => '2026-12-18',
            'priority'   => 25,
            'is_active'  => true,
            'slots'      => [
                [
                    'slot_name'                => 'Monthly Exam (Nine - Ten)',
                    'shift_group'              => 'Morning',
                    'on_time_start'            => '08:30:00',
                    'on_time_end'              => '09:00:00',
                    'late_end'                 => '09:15:00',
                    'absent_from'              => '09:16:00',
                    'check_out_from'           => '11:00:00',
                    'early_grace_minutes'      => 45,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => [$this->classes['Nine']->id, $this->classes['Ten']->id],
                ],
            ],
        ]);
        $monthlyRes->assertStatus(201)
            ->assertJson([
                'status'   => 'success',
                'schedule' => [
                    'name'          => 'Monthly Test (Class 9-10)',
                    'class_count'   => 2,
                    'class_summary' => 'Nine, Ten',
                ],
            ]);

        // C. Update Default Normal School Time timings & verify slot IDs remain intact (preserving attendance FK snapshots)
        $normal = StudentAttendanceSchedule::defaultNormal()->with('slots.classes')->firstOrFail();
        $morningSlot = $normal->slots->firstWhere('shift_group', 'Morning');
        $daySlot = $normal->slots->firstWhere('shift_group', 'Day');

        // Attach a historical attendance snapshot pointing to $morningSlot->id
        $student = Student::create([
            'user_id'               => $this->superAdmin->id,
            'student_identity'      => 'STD-SNAP-001',
            'card_number'           => 'SNAPCARD001',
            'student_name'          => 'Snapshot Student',
            'roll_number'           => '1',
            'dob'                   => '2016-01-01',
            'gender'                => 'Male',
            'father_name'           => 'Father',
            'father_mobile'         => '01711000001',
            'mother_name'           => 'Mother',
            'mother_mobile'         => '01711000002',
            'guardian_mobile'       => '01711000001',
            'branch_id'             => $this->branchMain->id,
            'session_year_id'       => $this->session->id,
            'class_id'              => $this->classes['One']->id,
            'section_id'            => $this->section->id,
            'status'                => 'Active',
            'sms_status'            => 'Active',
            'present_village'       => 'Jalalpur',
            'present_post_office'   => 'Jalalpur',
            'present_district'      => 'Sylhet',
            'present_division'      => 'Sylhet',
            'permanent_village'     => 'Jalalpur',
            'permanent_post_office' => 'Jalalpur',
            'permanent_district'    => 'Sylhet',
            'permanent_division'    => 'Sylhet',
        ]);

        $historicalAtt = Attendance::create([
            'student_id'         => $student->id,
            'attendance_date'    => '2026-11-20',
            'branch_id'          => $this->branchMain->id,
            'session_year_id'    => $this->session->id,
            'class_id'           => $this->classes['One']->id,
            'section_id'         => $this->section->id,
            'user_id'            => $this->superAdmin->id,
            'status'             => 'Present',
            'remarks'            => 'Card Swiped (In: 07:55:00)',
            'schedule_mode'      => 'normal',
            'schedule_slot_id'   => $morningSlot->id,
            'schedule_slot_name' => $morningSlot->slot_name,
        ]);

        $updateNormalRes = $this->putJson(route('attendance.schedules.update', $normal), [
            'name'     => 'Normal School Time (Winter)',
            'mode'     => 'normal',
            'priority' => 10,
            'slots'    => [
                [
                    'id'                       => $morningSlot->id,
                    'slot_name'                => 'Morning (Play - Three)',
                    'shift_group'              => 'Morning',
                    'on_time_start'            => '08:00:00',
                    'on_time_end'              => '08:30:00',
                    'late_end'                 => '08:45:00',
                    'absent_from'              => '08:46:00',
                    'check_out_from'           => '11:15:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => $morningSlot->classes->pluck('id')->all(),
                ],
                [
                    'id'                       => $daySlot->id,
                    'slot_name'                => 'Day (Four - Ten)',
                    'shift_group'              => 'Day',
                    'on_time_start'            => '11:30:00',
                    'on_time_end'              => '12:15:00',
                    'late_end'                 => '12:20:00',
                    'absent_from'              => '12:21:00',
                    'check_out_from'           => '15:30:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => $daySlot->classes->pluck('id')->all(),
                ],
            ],
        ]);
        $updateNormalRes->assertStatus(200);

        $morningSlot->refresh();
        $this->assertSame('08:00:00', $morningSlot->on_time_start);
        $this->assertSame('11:15:00', $morningSlot->check_out_from);
        // Historical FK remains preserved intact!
        $this->assertSame($morningSlot->id, $historicalAtt->fresh()->schedule_slot_id);

        // D. Toggle Exam Campaign Inactive -> Active
        $this->postJson(route('attendance.schedules.toggle', $examCampaign))
            ->assertStatus(200)
            ->assertJson(['is_active' => false]);
        $this->assertFalse($examCampaign->fresh()->is_active);

        $this->postJson(route('attendance.schedules.toggle', $examCampaign))
            ->assertStatus(200)
            ->assertJson(['is_active' => true]);
        $this->assertTrue($examCampaign->fresh()->is_active);

        // E. Delete Exam Campaign cleanly
        $this->deleteJson(route('attendance.schedules.destroy', $examCampaign))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);
        $this->assertDatabaseMissing('student_attendance_schedules', ['id' => $examCampaign->id]);
    }

    /**
     * 3. Validation & Safety Guards:
     *    - Duplicate class_id across slots rejected with clear 422 error
     *    - Invalid time ordering rejected
     *    - end_date < start_date rejected
     *    - Default Normal schedule & System Exam Template protected against delete/toggle/direct edit
     */
    public function test_validation_rules_and_safety_guards(): void
    {
        $this->actingAs($this->admin);

        // 3a. Duplicate class_id across two slots in same schedule -> 422 human-readable validation error
        $dupPayload = $this->buildFullExamPayload();
        $dupPayload['slots'][1]['class_ids'][] = $this->classes['Four']->id; // Already in slot 0!

        $this->postJson(route('attendance.schedules.store'), $dupPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slots.1.class_ids']);

        // 3b. Invalid time ordering: on_time_end >= late_end AND absent_from >= check_out_from
        $badTimesPayload = $this->buildFullExamPayload();
        $badTimesPayload['slots'][0]['on_time_end'] = '09:15:00';
        $badTimesPayload['slots'][0]['late_end'] = '09:10:00'; // < on_time_end!
        $badTimesPayload['slots'][1]['absent_from'] = '13:30:00';
        $badTimesPayload['slots'][1]['check_out_from'] = '13:00:00'; // < absent_from!

        $this->postJson(route('attendance.schedules.store'), $badTimesPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'slots.0.late_end',
                'slots.1.check_out_from',
            ]);

        // 3c. end_date < start_date rejected
        $badDatesPayload = $this->buildFullExamPayload([
            'start_date' => '2026-12-15',
            'end_date'   => '2026-12-10',
        ]);
        $this->postJson(route('attendance.schedules.store'), $badDatesPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        // 3d. Slot without classes rejected
        $emptyClassPayload = $this->buildFullExamPayload();
        $emptyClassPayload['slots'][2]['class_ids'] = [];
        $this->postJson(route('attendance.schedules.store'), $emptyClassPayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slots.2.class_ids']);

        // 3e. Default Normal schedule cannot be deactivated or deleted
        $normal = StudentAttendanceSchedule::defaultNormal()->firstOrFail();
        $this->postJson(route('attendance.schedules.toggle', $normal))->assertStatus(422);
        $this->deleteJson(route('attendance.schedules.destroy', $normal))->assertStatus(422);
        $this->assertTrue($normal->fresh()->is_active);
        $this->assertDatabaseHas('student_attendance_schedules', ['id' => $normal->id]);

        // 3f. System Exam Template cannot be edited directly, activated/toggled, or deleted
        $template = StudentAttendanceSchedule::templates()->firstOrFail();
        $this->putJson(route('attendance.schedules.update', $template), $this->buildFullExamPayload())->assertStatus(422);
        $this->postJson(route('attendance.schedules.toggle', $template))->assertStatus(422);
        $this->deleteJson(route('attendance.schedules.destroy', $template))->assertStatus(422);
        $this->assertFalse($template->fresh()->is_active);
        $this->assertDatabaseHas('student_attendance_schedules', ['id' => $template->id]);
    }

    /**
     * 4. Effective Schedule AJAX Endpoint:
     *    - Normal date -> mode = 'normal', 2 student slots + intact staff shifts
     *    - Full Exam date -> mode = 'exam', 3 student slots (Play-Four, Five-Nine, Ten) + intact staff shifts
     *    - Partial Monthly Exam date -> mode = 'mixed' (Nine, Ten Exam + Play-Three & Four-Eight Normal) + intact staff shifts
     *    - Branch-specific Exam campaign -> visible ONLY for matching branch_id
     */
    public function test_effective_schedule_ajax_endpoint_normal_exam_mixed_and_branch_scoped(): void
    {
        $this->actingAs($this->admin);

        // 4a. Normal School Time Date
        $normalRes = $this->getJson(route('attendance.effective-schedule', ['date' => '2026-11-24']));
        $normalRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'date'   => '2026-11-24',
                'mode'   => 'normal',
            ]);

        $normalSlots = $normalRes->json('student_slots');
        $this->assertCount(2, $normalSlots);
        $this->assertSame('Play - Three', $normalSlots[0]['class_summary']);
        $this->assertSame('07:40:00', $normalSlots[0]['on_time_start']);
        $this->assertSame('08:15:00', $normalSlots[0]['on_time_end']);
        $this->assertSame('08:16:00', $normalSlots[0]['late_start']);
        $this->assertSame('08:30:00', $normalSlots[0]['late_end']);
        $this->assertSame('08:31:00', $normalSlots[0]['absent_from']);
        $this->assertSame('11:00:00', $normalSlots[0]['check_out_from']);

        $this->assertSame('Four - Ten', $normalSlots[1]['class_summary']);
        $this->assertSame('12:16:00', $normalSlots[1]['late_start']);
        $this->assertSame('15:30:00', $normalSlots[1]['check_out_from']);

        // Staff shifts MUST remain intact!
        $staffShifts = $normalRes->json('staff_shifts');
        $this->assertCount(2, $staffShifts);
        $this->assertSame(['Morning Staff', 'Day Staff'], array_column($staffShifts, 'name'));

        // 4b. Full 3-slot Exam Campaign Date (2026-12-01..2026-12-10)
        $this->postJson(route('attendance.schedules.store'), $this->buildFullExamPayload())->assertStatus(201);

        $examRes = $this->getJson(route('attendance.effective-schedule', ['date' => '2026-12-05']));
        $examRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'date'   => '2026-12-05',
                'mode'   => 'exam',
            ]);
        $examSlots = $examRes->json('student_slots');
        $this->assertCount(3, $examSlots);
        $examSummaries = array_column($examSlots, 'class_summary');
        $this->assertContains('Play - Four', $examSummaries);
        $this->assertContains('Five - Nine', $examSummaries);
        $this->assertContains('Ten', $examSummaries);
        $this->assertSame(['1st Term Exam 2026'], array_column($examRes->json('active_campaigns'), 'name'));
        $this->assertCount(2, $examRes->json('staff_shifts'));

        // 4c. Partial Monthly Exam Date (Classes 9-10 only, 2026-12-15..2026-12-18) -> mode = 'mixed'
        $this->postJson(route('attendance.schedules.store'), [
            'name'       => 'Monthly Class Test 9-10',
            'mode'       => 'exam',
            'start_date' => '2026-12-15',
            'end_date'   => '2026-12-18',
            'priority'   => 25,
            'is_active'  => true,
            'slots'      => [
                [
                    'slot_name'                => 'Monthly Test (Nine - Ten)',
                    'shift_group'              => 'Morning',
                    'on_time_start'            => '08:30:00',
                    'on_time_end'              => '09:00:00',
                    'late_end'                 => '09:10:00',
                    'absent_from'              => '09:11:00',
                    'check_out_from'           => '11:00:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => [$this->classes['Nine']->id, $this->classes['Ten']->id],
                ],
            ],
        ])->assertStatus(201);

        $mixedRes = $this->getJson(route('attendance.effective-schedule', ['date' => '2026-12-16']));
        $mixedRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'date'   => '2026-12-16',
                'mode'   => 'mixed',
            ]);
        $mixedSlots = $mixedRes->json('student_slots');
        $this->assertCount(3, $mixedSlots);
        $this->assertEqualsCanonicalizing(
            ['Play - Three', 'Four - Eight', 'Nine, Ten'],
            array_column($mixedSlots, 'class_summary')
        );
        $this->assertCount(2, $mixedRes->json('staff_shifts'));

        // 4d. Branch-Specific Exam Campaign (Town Branch ONLY, 2026-12-22..2026-12-24)
        $this->postJson(route('attendance.schedules.store'), [
            'name'       => 'Town Branch Special Test',
            'mode'       => 'exam',
            'branch_id'  => $this->branchTown->id,
            'start_date' => '2026-12-22',
            'end_date'   => '2026-12-24',
            'priority'   => 30,
            'is_active'  => true,
            'slots'      => [
                [
                    'slot_name'                => 'Town Exam (Five - Ten)',
                    'shift_group'              => 'Morning',
                    'on_time_start'            => '08:00:00',
                    'on_time_end'              => '08:30:00',
                    'late_end'                 => '08:45:00',
                    'absent_from'              => '08:46:00',
                    'check_out_from'           => '10:30:00',
                    'early_grace_minutes'      => 60,
                    'debounce_minutes'         => 10,
                    'min_checkout_gap_minutes' => 15,
                    'class_ids'                => [
                        $this->classes['Five']->id,
                        $this->classes['Six']->id,
                        $this->classes['Seven']->id,
                        $this->classes['Eight']->id,
                        $this->classes['Nine']->id,
                        $this->classes['Ten']->id,
                    ],
                ],
            ],
        ])->assertStatus(201);

        // Without branch_id -> Normal
        $this->getJson(route('attendance.effective-schedule', ['date' => '2026-12-23']))
            ->assertStatus(200)
            ->assertJson(['mode' => 'normal']);

        // Main branch -> Normal (not Town Branch)
        $this->getJson(route('attendance.effective-schedule', ['date' => '2026-12-23', 'branch_id' => $this->branchMain->id]))
            ->assertStatus(200)
            ->assertJson(['mode' => 'normal']);

        // Town branch -> Mixed (Town Branch Special Test applies!)
        $townRes = $this->getJson(route('attendance.effective-schedule', ['date' => '2026-12-23', 'branch_id' => $this->branchTown->id]));
        $townRes->assertStatus(200)
            ->assertJson(['mode' => 'mixed']);
        $this->assertSame(['Town Branch Special Test'], array_column($townRes->json('active_campaigns'), 'name'));
    }
}
