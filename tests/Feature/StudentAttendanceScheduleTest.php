<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Shift;
use App\Models\Student;
use App\Models\StudentAttendanceSchedule;
use App\Models\StudentAttendanceScheduleSlot;
use App\Models\StudentAttendanceScheduleSlotClass;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ResolvedStudentSchedule;
use App\Services\StudentAttendanceScheduleResolver;
use App\Services\StudentPunchEvaluator;
use Carbon\Carbon;
use Database\Seeders\StudentAttendanceScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAttendanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branchMain;
    private Branch $branchSecond;
    private SessionYear $session2026;
    /** @var array<string, Classes> */
    private array $classes = [];
    private StudentAttendanceScheduleResolver $resolver;
    private StudentPunchEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Schedule Admin',
            'email'    => 'schedule_admin@macsschool.test',
            'password' => bcrypt('secret'),
        ]);

        $this->branchMain = Branch::create(['branch_name' => 'Jalalpur Branch', 'user_id' => $this->admin->id]);
        $this->branchSecond = Branch::create(['branch_name' => 'Town Branch', 'user_id' => $this->admin->id]);
        $this->session2026 = SessionYear::create(['session_name' => '2026', 'user_id' => $this->admin->id]);

        foreach (['Play', 'Nursery', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'] as $name) {
            $this->classes[$name] = Classes::create(['class_name' => $name, 'user_id' => $this->admin->id]);
        }

        $this->resolver = app(StudentAttendanceScheduleResolver::class);
        $this->evaluator = app(StudentPunchEvaluator::class);
    }

    public function test_seeder_is_idempotent_and_maps_all_classes_including_flexible_aliases(): void
    {
        // Add an optional KG class to verify alias matching
        $kg = Classes::create(['class_name' => 'K.G.', 'user_id' => $this->admin->id]);

        $this->seed(StudentAttendanceScheduleSeeder::class);
        $this->seed(StudentAttendanceScheduleSeeder::class); // Run twice for strict idempotency

        $this->assertSame(2, StudentAttendanceSchedule::count());
        $this->assertSame(5, StudentAttendanceScheduleSlot::count());

        $normal = StudentAttendanceSchedule::defaultNormal()->with('slots')->first();
        $this->assertNotNull($normal);
        $this->assertSame('Normal School Time', $normal->name);
        $this->assertTrue($normal->is_default);
        $this->assertTrue($normal->is_active);
        $this->assertFalse($normal->is_template);
        $this->assertNull($normal->start_date);
        $this->assertNull($normal->end_date);
        $this->assertSame(10, $normal->priority);
        $this->assertCount(2, $normal->slots);

        // 6 Morning classes (Play, Nursery, K.G., One, Two, Three) + 7 Day classes (Four..Ten) = 13
        $this->assertSame(13, StudentAttendanceScheduleSlotClass::where('schedule_id', $normal->id)->count());

        $morningNormalSlot = $normal->slots->firstWhere('shift_group', 'Morning');
        $this->assertTrue($morningNormalSlot->classes->contains('id', $kg->id));
        $this->assertTrue($morningNormalSlot->classes->contains('id', $this->classes['Three']->id));
        $this->assertFalse($morningNormalSlot->classes->contains('id', $this->classes['Four']->id));

        $examTemplate = StudentAttendanceSchedule::templates()->with('slots')->first();
        $this->assertNotNull($examTemplate);
        $this->assertSame('exam', $examTemplate->mode);
        $this->assertFalse($examTemplate->is_default);
        $this->assertFalse($examTemplate->is_active);
        $this->assertTrue($examTemplate->is_template);
        $this->assertSame(20, $examTemplate->priority);
        $this->assertCount(3, $examTemplate->slots);
        $this->assertSame(13, StudentAttendanceScheduleSlotClass::where('schedule_id', $examTemplate->id)->count());

        // In Exam Template: Class Four is mapped to Morning Exam, Class Ten has its own slot
        $morningExamSlot = $examTemplate->slots->firstWhere('shift_group', 'Morning');
        $this->assertTrue($morningExamSlot->classes->contains('id', $this->classes['Four']->id));
        $this->assertFalse($morningExamSlot->classes->contains('id', $this->classes['Five']->id));

        $tenExamSlot = $examTemplate->slots->firstWhere('slot_name', 'Day Exam (Class Ten)');
        $this->assertNotNull($tenExamSlot);
        $this->assertSame([$this->classes['Ten']->id], $tenExamSlot->classes->pluck('id')->all());
    }

    public function test_normal_school_time_resolution_and_exact_second_boundaries(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);
        $date = '2026-11-24';

        // Morning Normal (Play - Three): 07:40-08:15 Present, 08:16-08:30 Late, 08:31+ Absent, 11:00+ Out
        $playSchedule = $this->resolver->resolveForClassOnDate($this->classes['Play']->id, $date, $this->branchMain->id);
        $this->assertNotNull($playSchedule);
        $this->assertSame('normal', $playSchedule->mode);
        $this->assertSame(ResolvedStudentSchedule::SOURCE_NORMAL_SCHEDULE, $playSchedule->source);
        $this->assertSame('Morning', $playSchedule->shiftGroup);
        $this->assertSame('07:40:00', $playSchedule->onTimeStart);
        $this->assertSame('08:15:00', $playSchedule->onTimeEnd);
        $this->assertSame('08:30:00', $playSchedule->lateEnd);
        $this->assertSame('08:31:00', $playSchedule->absentFrom);
        $this->assertSame('11:00:00', $playSchedule->checkOutFrom);

        $this->assertSame('Present', $this->evaluator->evaluate(['07:40:00'], $playSchedule, $date)->status);
        $this->assertSame('Present', $this->evaluator->evaluate(['08:15:59'], $playSchedule, $date)->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['08:16:00'], $playSchedule, $date)->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['08:30:59'], $playSchedule, $date)->status);
        $this->assertSame('Absent', $this->evaluator->evaluate(['08:31:00'], $playSchedule, $date)->status);

        // Day Normal (Four - Ten): 11:30-12:15 Present, 12:16-12:20 Late, 12:21+ Absent, 15:30+ Out
        $fourSchedule = $this->resolver->resolveForClassOnDate($this->classes['Four']->id, $date, $this->branchMain->id);
        $this->assertNotNull($fourSchedule);
        $this->assertSame('Day', $fourSchedule->shiftGroup);
        $this->assertSame('11:30:00', $fourSchedule->onTimeStart);
        $this->assertSame('12:15:00', $fourSchedule->onTimeEnd);
        $this->assertSame('12:20:00', $fourSchedule->lateEnd);
        $this->assertSame('12:21:00', $fourSchedule->absentFrom);
        $this->assertSame('15:30:00', $fourSchedule->checkOutFrom);

        $this->assertSame('Present', $this->evaluator->evaluate(['12:15:59'], $fourSchedule, $date)->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['12:16:00'], $fourSchedule, $date)->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['12:20:59'], $fourSchedule, $date)->status);
        $this->assertSame('Absent', $this->evaluator->evaluate(['12:21:00'], $fourSchedule, $date)->status);
    }

    public function test_exam_template_never_activates_automatically_even_if_dates_were_set(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        $template = StudentAttendanceSchedule::templates()->firstOrFail();
        $template->update([
            'is_active'  => true, // Even if flipped active while still is_template = true
            'start_date' => '2026-11-20',
            'end_date'   => '2026-11-30',
        ]);

        $resolved = $this->resolver->resolveForClassOnDate($this->classes['Four']->id, '2026-11-24', $this->branchMain->id);
        $this->assertSame('normal', $resolved->mode);
        $this->assertSame('Day', $resolved->shiftGroup);
    }

    public function test_exam_campaign_shifts_class_four_gives_class_ten_dedicated_slot_and_auto_reverts_outside_dates(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);
        $this->cloneExamTemplateAsActiveCampaign('Annual Exam 2026', '2026-12-01', '2026-12-10');

        // Before start_date -> Normal School Time
        $beforeFour = $this->resolver->resolveForClassOnDate($this->classes['Four']->id, '2026-11-30', $this->branchMain->id);
        $this->assertSame('normal', $beforeFour->mode);
        $this->assertSame('Day', $beforeFour->shiftGroup);
        $this->assertSame('11:30:00', $beforeFour->onTimeStart);

        // Inside exam range -> Class Four moves from Day to Morning Exam (08:00-09:00, out 09:40)
        $examFour = $this->resolver->resolveForClassOnDate($this->classes['Four']->id, '2026-12-05', $this->branchMain->id);
        $this->assertSame('exam', $examFour->mode);
        $this->assertSame(ResolvedStudentSchedule::SOURCE_EXAM_SCHEDULE, $examFour->source);
        $this->assertSame('Morning', $examFour->shiftGroup);
        $this->assertSame('08:00:00', $examFour->onTimeStart);
        $this->assertSame('09:00:00', $examFour->onTimeEnd);
        $this->assertSame('09:10:00', $examFour->lateEnd);
        $this->assertSame('09:11:00', $examFour->absentFrom);
        $this->assertSame('09:40:00', $examFour->checkOutFrom);

        // Inside exam range -> Classes Five-Nine resolve to Day Exam (11:30-12:00, late 12:01-12:10, absent 12:11, out 13:00)
        $examSeven = $this->resolver->resolveForClassOnDate($this->classes['Seven']->id, '2026-12-05', $this->branchMain->id);
        $this->assertSame('exam', $examSeven->mode);
        $this->assertSame('Day', $examSeven->shiftGroup);
        $this->assertSame('11:30:00', $examSeven->onTimeStart);
        $this->assertSame('12:00:00', $examSeven->onTimeEnd);
        $this->assertSame('12:10:00', $examSeven->lateEnd);
        $this->assertSame('12:11:00', $examSeven->absentFrom);
        $this->assertSame('13:00:00', $examSeven->checkOutFrom);

        $this->assertSame('Present', $this->evaluator->evaluate(['12:00:59'], $examSeven, '2026-12-05')->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['12:01:00'], $examSeven, '2026-12-05')->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['12:10:59'], $examSeven, '2026-12-05')->status);
        $this->assertSame('Absent', $this->evaluator->evaluate(['12:11:00'], $examSeven, '2026-12-05')->status);

        // Inside exam range -> Class Ten resolves to dedicated Exam slot (09:00-09:20, late 09:21-09:30, absent 09:31, out 12:50)
        $examTen = $this->resolver->resolveForClassOnDate($this->classes['Ten']->id, '2026-12-05', $this->branchMain->id);
        $this->assertSame('exam', $examTen->mode);
        $this->assertSame('09:00:00', $examTen->onTimeStart);
        $this->assertSame('09:20:00', $examTen->onTimeEnd);
        $this->assertSame('09:30:00', $examTen->lateEnd);
        $this->assertSame('09:31:00', $examTen->absentFrom);
        $this->assertSame('12:50:00', $examTen->checkOutFrom);

        $this->assertSame('Present', $this->evaluator->evaluate(['09:20:59'], $examTen, '2026-12-05')->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['09:21:00'], $examTen, '2026-12-05')->status);
        $this->assertSame('Late', $this->evaluator->evaluate(['09:30:59'], $examTen, '2026-12-05')->status);
        $this->assertSame('Absent', $this->evaluator->evaluate(['09:31:00'], $examTen, '2026-12-05')->status);

        // After end_date -> automatically reverts to Normal School Time
        $afterFour = $this->resolver->resolveForClassOnDate($this->classes['Four']->id, '2026-12-11', $this->branchMain->id);
        $this->assertSame('normal', $afterFour->mode);
        $this->assertSame('Day', $afterFour->shiftGroup);
        $this->assertSame('15:30:00', $afterFour->checkOutFrom);

        $afterTen = $this->resolver->resolveForClassOnDate($this->classes['Ten']->id, '2026-12-11', $this->branchMain->id);
        $this->assertSame('normal', $afterTen->mode);
        $this->assertSame('11:30:00', $afterTen->onTimeStart);
    }

    public function test_partial_monthly_exam_affects_only_mapped_classes_while_others_stay_on_normal_time(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        $monthlyTest = StudentAttendanceSchedule::create([
            'name'        => 'Classes Nine & Ten Monthly Test',
            'mode'        => StudentAttendanceSchedule::MODE_EXAM,
            'is_default'  => false,
            'is_template' => false,
            'is_active'   => true,
            'start_date'  => '2026-11-25',
            'end_date'    => '2026-11-27',
            'priority'    => 20,
            'created_by'  => $this->admin->id,
        ]);

        $slot = StudentAttendanceScheduleSlot::create([
            'schedule_id'              => $monthlyTest->id,
            'slot_name'                => 'Senior Monthly Exam (Nine - Ten)',
            'shift_group'              => 'Day',
            'on_time_start'            => '09:00:00',
            'on_time_end'              => '09:20:00',
            'late_end'                 => '09:30:00',
            'absent_from'              => '09:31:00',
            'check_out_from'           => '12:50:00',
            'early_grace_minutes'      => 60,
            'debounce_minutes'         => 10,
            'min_checkout_gap_minutes' => 15,
            'sort_order'               => 10,
        ]);

        foreach (['Nine', 'Ten'] as $clsName) {
            StudentAttendanceScheduleSlotClass::create([
                'schedule_id' => $monthlyTest->id,
                'slot_id'     => $slot->id,
                'class_id'    => $this->classes[$clsName]->id,
            ]);
        }

        // On 2026-11-26: Nine & Ten get Exam Time, Play..Eight remain on Normal School Time
        $nine = $this->resolver->resolveForClassOnDate($this->classes['Nine']->id, '2026-11-26');
        $ten = $this->resolver->resolveForClassOnDate($this->classes['Ten']->id, '2026-11-26');
        $play = $this->resolver->resolveForClassOnDate($this->classes['Play']->id, '2026-11-26');
        $eight = $this->resolver->resolveForClassOnDate($this->classes['Eight']->id, '2026-11-26');

        $this->assertSame('exam', $nine->mode);
        $this->assertSame('exam', $ten->mode);
        $this->assertSame('normal', $play->mode);
        $this->assertSame('normal', $eight->mode);

        // Overview reflects mixed mode with 3 effective slots (Morning Play-Three, Day Four-Eight, Senior Exam Nine-Ten)
        $overview = $this->resolver->getEffectiveScheduleOverview('2026-11-26');
        $this->assertSame('mixed', $overview['active_mode']);
        $this->assertCount(1, $overview['exam_campaigns']);
        $this->assertCount(3, $overview['slots']);
    }

    public function test_branch_specific_and_higher_priority_exam_campaigns_take_precedence(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);

        // Global Exam Campaign (priority 20)
        $this->cloneExamTemplateAsActiveCampaign('Global Term Exam', '2026-12-01', '2026-12-10', null, 20);

        // Higher-priority Global Override for Class Ten (priority 50)
        $highPrio = StudentAttendanceSchedule::create([
            'name'        => 'Special Class Ten Board Mock',
            'mode'        => StudentAttendanceSchedule::MODE_EXAM,
            'is_default'  => false,
            'is_template' => false,
            'is_active'   => true,
            'start_date'  => '2026-12-03',
            'end_date'    => '2026-12-05',
            'branch_id'   => null,
            'priority'    => 50,
            'created_by'  => $this->admin->id,
        ]);
        $highSlot = StudentAttendanceScheduleSlot::create([
            'schedule_id'    => $highPrio->id,
            'slot_name'      => 'Class Ten Special Mock',
            'shift_group'    => 'Morning',
            'on_time_start'  => '08:30:00',
            'on_time_end'    => '08:50:00',
            'late_end'       => '09:00:00',
            'absent_from'    => '09:01:00',
            'check_out_from' => '12:00:00',
        ]);
        StudentAttendanceScheduleSlotClass::create([
            'schedule_id' => $highPrio->id,
            'slot_id'     => $highSlot->id,
            'class_id'    => $this->classes['Ten']->id,
        ]);

        // Branch-specific Exam Campaign for branchSecond (even with lower priority 15, branch beats global)
        $branchCampaign = StudentAttendanceSchedule::create([
            'name'        => 'Town Branch Custom Exam',
            'mode'        => StudentAttendanceSchedule::MODE_EXAM,
            'is_default'  => false,
            'is_template' => false,
            'is_active'   => true,
            'start_date'  => '2026-12-01',
            'end_date'    => '2026-12-10',
            'branch_id'   => $this->branchSecond->id,
            'priority'    => 15,
            'created_by'  => $this->admin->id,
        ]);
        $branchSlot = StudentAttendanceScheduleSlot::create([
            'schedule_id'    => $branchCampaign->id,
            'slot_name'      => 'Town Branch Ten Slot',
            'shift_group'    => 'Day',
            'on_time_start'  => '10:00:00',
            'on_time_end'    => '10:15:00',
            'late_end'       => '10:25:00',
            'absent_from'    => '10:26:00',
            'check_out_from' => '13:15:00',
        ]);
        StudentAttendanceScheduleSlotClass::create([
            'schedule_id' => $branchCampaign->id,
            'slot_id'     => $branchSlot->id,
            'class_id'    => $this->classes['Ten']->id,
        ]);

        // Main branch on 2026-12-04 -> gets priority 50 global override
        $mainTen = $this->resolver->resolveForClassOnDate($this->classes['Ten']->id, '2026-12-04', $this->branchMain->id);
        $this->assertSame('Special Class Ten Board Mock', $mainTen->scheduleName);
        $this->assertSame('08:30:00', $mainTen->onTimeStart);

        // Main branch on 2026-12-06 (outside high-prio dates) -> gets priority 20 global exam
        $mainTenLater = $this->resolver->resolveForClassOnDate($this->classes['Ten']->id, '2026-12-06', $this->branchMain->id);
        $this->assertSame('Global Term Exam', $mainTenLater->scheduleName);
        $this->assertSame('09:00:00', $mainTenLater->onTimeStart);

        // Town branch on 2026-12-04 -> branch-specific campaign beats global priority 50
        $townTen = $this->resolver->resolveForClassOnDate($this->classes['Ten']->id, '2026-12-04', $this->branchSecond->id);
        $this->assertSame('Town Branch Custom Exam', $townTen->scheduleName);
        $this->assertSame('10:00:00', $townTen->onTimeStart);
    }

    public function test_early_punch_grace_hard_noise_floor_and_double_tap_debounce(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);
        $date = '2026-11-24';

        // Morning Normal: on_time_start 07:40:00, early_grace 60m -> earliest_valid_in = 06:40:00
        $morning = $this->resolver->resolveForClassOnDate($this->classes['Play']->id, $date);

        // 06:45 AM is within grace -> Present
        $earlyValid = $this->evaluator->evaluate(['06:45:00'], $morning, $date);
        $this->assertTrue($earlyValid->hasValidPunch);
        $this->assertSame('Present', $earlyValid->status);
        $this->assertSame('06:45:00', $earlyValid->checkIn);
        $this->assertNull($earlyValid->checkOut);
        $this->assertSame('06:40:00', $earlyValid->earliestValidIn);

        // 05:30 AM (below 06:00 AM hard floor) -> discarded as noise
        $noiseOnly = $this->evaluator->evaluate(['05:30:00'], $morning, $date);
        $this->assertFalse($noiseOnly->hasValidPunch);
        $this->assertSame('Absent', $noiseOnly->status);
        $this->assertNull($noiseOnly->checkIn);
        $this->assertSame(['05:30:00'], $noiseOnly->discardedPunches);

        // Noise punch (05:30:00) + real punch (07:50:00) -> 05:30 discarded, 07:50 kept as Present
        $noiseAndReal = $this->evaluator->evaluate(['05:30:00', '07:50:00'], $morning, $date);
        $this->assertTrue($noiseAndReal->hasValidPunch);
        $this->assertSame('Present', $noiseAndReal->status);
        $this->assertSame('07:50:00', $noiseAndReal->checkIn);
        $this->assertNull($noiseAndReal->checkOut);
        $this->assertSame(['05:30:00'], $noiseAndReal->discardedPunches);

        // Double-tap debounce (8 seconds apart and 5 minutes apart, both < 10 min debounce) -> NO checkOut
        $doubleTapSec = $this->evaluator->evaluate(['07:50:00', '07:50:08'], $morning, $date);
        $this->assertSame('Present', $doubleTapSec->status);
        $this->assertSame('07:50:00', $doubleTapSec->checkIn);
        $this->assertNull($doubleTapSec->checkOut);
        $this->assertSame('Card Swiped (In: 07:50:00)', $doubleTapSec->toRemarks());

        $doubleTapMin = $this->evaluator->evaluate(['07:50:00', '07:55:00'], $morning, $date);
        $this->assertSame('Present', $doubleTapMin->status);
        $this->assertSame('07:50:00', $doubleTapMin->checkIn);
        $this->assertNull($doubleTapMin->checkOut);

        // Hard floor clamps early grace when on_time_start - grace < 06:00:00
        $earlyMorningRule = new ResolvedStudentSchedule(
            mode: 'normal',
            scheduleId: null,
            scheduleName: 'Test Early',
            slotId: null,
            slotName: 'Early Slot',
            shiftGroup: 'Morning',
            onTimeStart: '06:30:00',
            onTimeEnd: '07:00:00',
            lateEnd: '07:10:00',
            absentFrom: '07:11:00',
            checkOutFrom: '10:00:00',
            earlyGraceMinutes: 60, // 06:30 - 60m = 05:30 -> clamped to 06:00:00
            debounceMinutes: 10,
            minCheckoutGapMinutes: 15,
            source: ResolvedStudentSchedule::SOURCE_NORMAL_SCHEDULE,
        );
        $clampedEval = $this->evaluator->evaluate(['05:55:00', '06:05:00'], $earlyMorningRule, $date);
        $this->assertSame('06:00:00', $clampedEval->earliestValidIn);
        $this->assertSame(['05:55:00'], $clampedEval->discardedPunches);
        $this->assertSame('06:05:00', $clampedEval->checkIn);
    }

    public function test_checkout_qualification_tight_exam_morning_and_late_absent_traceability(): void
    {
        $this->seed(StudentAttendanceScheduleSeeder::class);
        $this->cloneExamTemplateAsActiveCampaign('1st Term Exam', '2026-12-01', '2026-12-10');
        $date = '2026-12-02';

        // Exam Morning (Play - Four): 08:00-09:00 Present, 09:01-09:10 Late, 09:11+ Absent, 09:40+ Check-Out
        $examMorning = $this->resolver->resolveForClassOnDate($this->classes['Four']->id, $date);

        // 1. Punch before check_out_from (09:25 AM < 09:40 AM) -> NO checkOut
        $earlyExit = $this->evaluator->evaluate(['08:05:00', '09:25:00'], $examMorning, $date);
        $this->assertSame('Present', $earlyExit->status);
        $this->assertSame('08:05:00', $earlyExit->checkIn);
        $this->assertNull($earlyExit->checkOut);

        // 2. Tight Exam Morning case: Late arrival 09:10:00 + exit 09:40:00 (30m >= 15m & >= 09:40) -> Late + valid checkOut
        $tightLate = $this->evaluator->evaluate(['09:10:00', '09:40:00'], $examMorning, $date);
        $this->assertSame('Late', $tightLate->status);
        $this->assertSame('09:10:00', $tightLate->checkIn);
        $this->assertSame('09:40:00', $tightLate->checkOut);
        $this->assertSame('Card Swiped (In: 09:10:00, Out: 09:40:00)', $tightLate->toRemarks());

        // 3. Late-Absent arrival 09:30:00 + exit 09:40:00 (10m gap < 15m min_checkout_gap) -> Absent + NO checkOut
        $absentShortGap = $this->evaluator->evaluate(['09:30:00', '09:40:00'], $examMorning, $date);
        $this->assertSame('Absent', $absentShortGap->status);
        $this->assertSame('09:30:00', $absentShortGap->checkIn);
        $this->assertNull($absentShortGap->checkOut);
        $this->assertSame('Card Swiped (In: 09:30:00)', $absentShortGap->toRemarks());

        // 4. Late-Absent arrival 09:30:00 + exit 09:45:00 (15m gap >= 15m & >= 09:40) -> Absent + valid checkOut (never upgraded)
        $absentValidOut = $this->evaluator->evaluate(['09:30:00', '09:45:00'], $examMorning, $date);
        $this->assertSame('Absent', $absentValidOut->status);
        $this->assertSame('09:30:00', $absentValidOut->checkIn);
        $this->assertSame('09:45:00', $absentValidOut->checkOut);
        $this->assertSame('Card Swiped (In: 09:30:00, Out: 09:45:00)', $absentValidOut->toRemarks());

        // Verify Attendance model regex accessors parse remarks cleanly and snapshot columns persist
        $section = Section::create([
            'section_name'    => 'A',
            'class_id'        => $this->classes['Four']->id,
            'session_year_id' => $this->session2026->id,
            'user_id'         => $this->admin->id,
        ]);
        $student = $this->createStudent($this->classes['Four'], $section);

        $attendance = Attendance::create(array_merge([
            'student_id'      => $student->id,
            'branch_id'       => $this->branchMain->id,
            'session_year_id' => $this->session2026->id,
            'class_id'        => $this->classes['Four']->id,
            'section_id'      => $section->id,
            'user_id'         => $this->admin->id,
            'attendance_date' => $date,
            'status'          => $tightLate->status,
            'remarks'         => $tightLate->toRemarks(),
        ], $examMorning->toAttendanceSnapshot()));

        $this->assertSame('09:10 AM', $attendance->in_time);
        $this->assertSame('09:40 AM', $attendance->out_time);
        $this->assertSame('exam', $attendance->schedule_mode);
        $this->assertSame($examMorning->slotId, $attendance->schedule_slot_id);
        $this->assertSame('Morning Exam (Play - Four)', $attendance->schedule_slot_name);
        $this->assertNotNull($attendance->scheduleSlot);
    }

    public function test_legacy_shift_fallback_when_new_schedule_tables_are_empty_and_staff_service_remains_intact(): void
    {
        // Do NOT run StudentAttendanceScheduleSeeder (new tables are empty)
        $legacyStudentShift = Shift::create([
            'name'          => 'Morning Student',
            'shift_name'    => 'Morning Student',
            'type'          => 'student',
            'in_time_start' => '09:00:00',
            'in_time_end'   => '09:30:00',
            'late_time_end' => '09:40:00',
            'absent_time'   => '09:41:00',
            'out_time'      => '11:50:00',
            'user_id'       => $this->admin->id,
        ]);

        $staffShift = Shift::create([
            'name'          => 'Morning Staff',
            'shift_name'    => 'Morning Staff',
            'type'          => 'staff',
            'in_time_start' => '07:30:00',
            'in_time_end'   => '08:30:00',
            'late_time_end' => '09:00:00',
            'absent_time'   => '09:01:00',
            'out_time'      => '12:00:00',
            'user_id'       => $this->admin->id,
        ]);

        $section = Section::create([
            'section_name'    => 'A',
            'class_id'        => $this->classes['One']->id,
            'session_year_id' => $this->session2026->id,
            'user_id'         => $this->admin->id,
        ]);
        $student = $this->createStudent($this->classes['One'], $section, $legacyStudentShift->id);

        $fallback = $this->resolver->resolveForStudentOnDate($student, '2026-11-24');
        $this->assertNotNull($fallback);
        $this->assertSame(ResolvedStudentSchedule::SOURCE_LEGACY_SHIFT, $fallback->source);
        $this->assertSame('09:00:00', $fallback->onTimeStart);
        $this->assertSame('09:30:00', $fallback->onTimeEnd);
        $this->assertSame('09:40:00', $fallback->lateEnd);
        $this->assertSame('09:41:00', $fallback->absentFrom);
        $this->assertSame('11:50:00', $fallback->checkOutFrom);

        // Existing AttendanceService::calculateStatus(Carbon, Shift) for staff remains 100% untouched
        $attendanceService = app(AttendanceService::class);
        $this->assertSame('Present', $attendanceService->calculateStatus(Carbon::parse('2026-11-24 08:15:00', 'Asia/Dhaka'), $staffShift));
        $this->assertSame('Late', $attendanceService->calculateStatus(Carbon::parse('2026-11-24 08:45:00', 'Asia/Dhaka'), $staffShift));
        $this->assertSame('Absent', $attendanceService->calculateStatus(Carbon::parse('2026-11-24 09:05:00', 'Asia/Dhaka'), $staffShift));
    }

    private function cloneExamTemplateAsActiveCampaign(
        string $name,
        string $startDate,
        string $endDate,
        ?int $branchId = null,
        int $priority = 20
    ): StudentAttendanceSchedule {
        $template = StudentAttendanceSchedule::templates()->with('slots.slotClasses')->firstOrFail();

        $campaign = StudentAttendanceSchedule::create([
            'name'        => $name,
            'mode'        => StudentAttendanceSchedule::MODE_EXAM,
            'is_default'  => false,
            'is_template' => false,
            'is_active'   => true,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'branch_id'   => $branchId,
            'priority'    => $priority,
            'created_by'  => $this->admin->id,
        ]);

        foreach ($template->slots as $tplSlot) {
            $newSlot = StudentAttendanceScheduleSlot::create([
                'schedule_id'              => $campaign->id,
                'slot_name'                => $tplSlot->slot_name,
                'shift_group'              => $tplSlot->shift_group,
                'on_time_start'            => $tplSlot->on_time_start,
                'on_time_end'              => $tplSlot->on_time_end,
                'late_end'                 => $tplSlot->late_end,
                'absent_from'              => $tplSlot->absent_from,
                'check_out_from'           => $tplSlot->check_out_from,
                'early_grace_minutes'      => $tplSlot->early_grace_minutes,
                'debounce_minutes'         => $tplSlot->debounce_minutes,
                'min_checkout_gap_minutes' => $tplSlot->min_checkout_gap_minutes,
                'sort_order'               => $tplSlot->sort_order,
            ]);

            foreach ($tplSlot->slotClasses as $sc) {
                StudentAttendanceScheduleSlotClass::create([
                    'schedule_id' => $campaign->id,
                    'slot_id'     => $newSlot->id,
                    'class_id'    => $sc->class_id,
                ]);
            }
        }

        return $campaign;
    }

    private function createStudent(Classes $class, Section $section, ?int $shiftId = null): Student
    {
        return Student::create([
            'user_id'               => $this->admin->id,
            'student_name'          => 'Test Schedule Student',
            'student_identity'      => 'STD-SCH-' . uniqid(),
            'roll_number'           => '1',
            'dob'                   => '2016-01-01',
            'gender'                => 'Male',
            'class_id'              => $class->id,
            'section_id'            => $section->id,
            'branch_id'             => $this->branchMain->id,
            'session_year_id'       => $this->session2026->id,
            'shift_id'              => $shiftId,
            'father_name'           => 'Father',
            'father_mobile'         => '01700000001',
            'mother_name'           => 'Mother',
            'mother_mobile'         => '01700000002',
            'guardian_name'         => 'Guardian',
            'guardian_mobile'       => '01700000003',
            'status'                => 'Active',
            'sms_status'            => 'Active',
            'present_village'       => 'V',
            'present_post_office'   => 'P',
            'present_district'      => 'D',
            'present_division'      => 'Div',
            'permanent_village'     => 'V',
            'permanent_post_office' => 'P',
            'permanent_district'    => 'D',
            'permanent_division'    => 'Div',
        ]);
    }
}
