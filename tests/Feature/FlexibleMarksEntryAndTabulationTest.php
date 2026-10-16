<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\Student;
use App\Models\Mark;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FlexibleMarksEntryAndTabulationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected SessionYear $session;
    protected Classes $classPlay;
    protected Classes $classTen;
    protected Section $sectionA;
    protected Subject $subjectMath;
    protected Subject $subjectEnglish;
    protected Exam $examTerm2;
    protected ExamSchedule $scheduleMathPlay;
    protected ExamSchedule $scheduleEnglishPlay;
    protected ExamSchedule $scheduleMathTen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin_test@macsschool.com',
            'password' => bcrypt('password'),
        ]);

        $role = \HasinHayder\Tyro\Models\Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->user->assignRole($role);
        $this->actingAs($this->user);

        $this->branch = Branch::create([
            'branch_name' => 'Jalalpur Branch',
            'user_id'     => $this->user->id,
        ]);

        $this->session = SessionYear::create([
            'session_name' => '2026',
            'user_id'      => $this->user->id,
        ]);

        $this->classPlay = Classes::create([
            'class_name' => 'Seven',
            'user_id'    => $this->user->id,
        ]);

        $this->classTen = Classes::create([
            'class_name' => 'Class Ten',
            'user_id'    => $this->user->id,
        ]);

        $this->sectionA = Section::create([
            'section_name' => 'A',
            'user_id'      => $this->user->id,
        ]);

        $this->subjectMath = Subject::create([
            'subject_name' => 'Mathematics',
            'subject_code' => 'MATH101',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);

        $this->subjectEnglish = Subject::create([
            'subject_name' => 'English',
            'subject_code' => 'ENG101',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);

        $this->examTerm2 = Exam::create([
            'name'            => '2nd Term Exam 2026',
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'start_date'      => '2026-06-01',
            'end_date'        => '2026-06-15',
            'status'          => 'running',
        ]);

        // Play-9 Schedule: CT = 10, MT = 20, Terminal = 70, Full = 100, Pass = 33
        $this->scheduleMathPlay = ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $this->subjectMath->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 10,
            'mcq_marks'     => 20,
            'written_marks' => 70,
        ]);

        $this->scheduleEnglishPlay = ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $this->subjectEnglish->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 10,
            'mcq_marks'     => 20,
            'written_marks' => 70,
        ]);

        // Class 10 Schedule: MCQ = 30, Written CQ = 50, Practical = 20, Full = 100, Pass = 33
        $this->scheduleMathTen = ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classTen->id,
            'subject_id'    => $this->subjectMath->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 20, // Practical
            'mcq_marks'     => 30, // MCQ
            'written_marks' => 50, // Written CQ
        ]);
    }

    protected function createStudent(string $roll, string $name, Classes $class): Student
    {
        return Student::create([
            'user_id'                => $this->user->id,
            'student_identity'       => 'STD-' . $roll . '-' . rand(100, 999),
            'roll_number'            => $roll,
            'student_name'           => $name,
            'dob'                    => '2015-01-01',
            'gender'                 => 'Male',
            'father_name'            => 'Father of ' . $name,
            'father_mobile'          => '01711000000',
            'mother_name'            => 'Mother of ' . $name,
            'mother_mobile'          => '01722000000',
            'guardian_name'          => 'Guardian of ' . $name,
            'guardian_mobile'        => '01733000000',
            'present_village'        => 'Village',
            'present_post_office'    => 'Post Office',
            'present_district'       => 'District',
            'present_division'       => 'Division',
            'permanent_village'      => 'Village',
            'permanent_post_office'  => 'Post Office',
            'permanent_district'     => 'District',
            'permanent_division'     => 'Division',
            'session_year_id'        => $this->session->id,
            'branch_id'              => $this->branch->id,
            'class_id'               => $class->id,
            'section_id'             => $this->sectionA->id,
            'status'                 => 'Active',
            'sms_status'             => 'Active',
        ]);
    }

    /**
     * Case 1: All Components remains the default mode in Marks Entry
     */
    public function test_01_all_components_remains_default_mode_in_marks_entry()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('marks.index', [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('entryMode', 'all');
    }

    /**
     * Case 2: Existing composite marks and grades are calculated exactly as before
     */
    public function test_02_existing_composite_marks_and_grades_calculated_as_before()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('01', 'Karim Ali', $this->classPlay);

        $payload = [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'entry_mode'      => 'all',
            'ct_mark'         => 10,
            'mcq_mark'        => 18,
            'written_mark'    => 60,
        ];

        $response = $this->postJson(route('marks.store.ajax'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'total'        => 88.0,
                'letter_grade' => 'A+',
                'grade_point'  => 5.0,
            ]);

        $this->assertDatabaseHas('marks', [
            'student_id'   => $student->id,
            'total_mark'   => 88.0,
            'letter_grade' => 'A+',
            'grade_point'  => 5.0,
        ]);
    }

    /**
     * Case 3: CT-only marks entry preserves existing MT and Terminal marks
     */
    public function test_03_ct_only_marks_entry_preserves_existing_mt_and_terminal_marks()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('02', 'Rahim Mia', $this->classPlay);

        // Pre-existing composite mark
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 5,
            'mcq_mark'        => 15,
            'written_mark'    => 50,
            'total_mark'      => 70,
            'letter_grade'    => 'A',
            'grade_point'     => 4.0,
        ]);

        // Update in CT-only mode
        $response = $this->postJson(route('marks.store.ajax'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'entry_mode'      => 'ct_only',
            'ct_mark'         => 10,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('marks', [
            'student_id'   => $student->id,
            'ct_mark'      => 10.0,
            'mcq_mark'     => 15.0, // PRESERVED!
            'written_mark' => 50.0, // PRESERVED!
            'total_mark'   => 75.0, // 10 + 15 + 50
            'letter_grade' => 'A',
            'grade_point'  => 4.0,
        ]);
    }

    /**
     * Case 4: MT-only marks entry preserves existing CT and Terminal marks
     */
    public function test_04_mt_only_marks_entry_preserves_existing_ct_and_terminal_marks()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('03', 'Jamal Hossain', $this->classPlay);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 8,
            'mcq_mark'        => 12,
            'written_mark'    => 45,
            'total_mark'      => 65,
            'letter_grade'    => 'A-',
            'grade_point'     => 3.5,
        ]);

        $response = $this->postJson(route('marks.store.ajax'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'entry_mode'      => 'mt_only',
            'mcq_mark'        => 19,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('marks', [
            'student_id'   => $student->id,
            'ct_mark'      => 8.0,  // PRESERVED!
            'mcq_mark'     => 19.0, // UPDATED
            'written_mark' => 45.0, // PRESERVED!
            'total_mark'   => 72.0, // 8 + 19 + 45
            'letter_grade' => 'A',
            'grade_point'  => 4.0,
        ]);
    }

    /**
     * Case 5: Terminal-only marks entry preserves existing CT and MT marks
     */
    public function test_05_terminal_only_marks_entry_preserves_existing_ct_and_mt_marks()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('04', 'Kamal Ahmed', $this->classPlay);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 9,
            'mcq_mark'        => 17,
            'written_mark'    => 30,
            'total_mark'      => 56,
            'letter_grade'    => 'B',
            'grade_point'     => 3.0,
        ]);

        $response = $this->postJson(route('marks.store.ajax'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'entry_mode'      => 'terminal_only',
            'written_mark'    => 58,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('marks', [
            'student_id'   => $student->id,
            'ct_mark'      => 9.0,  // PRESERVED!
            'mcq_mark'     => 17.0, // PRESERVED!
            'written_mark' => 58.0, // UPDATED
            'total_mark'   => 84.0, // 9 + 17 + 58
            'letter_grade' => 'A+',
            'grade_point'  => 5.0,
        ]);
    }

    /**
     * Case 6: Invalid component modes are rejected server-side
     */
    public function test_06_invalid_component_modes_are_rejected_server_side()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('05', 'Nabil Hasan', $this->classPlay);

        // Invalid entry_mode in marks store ajax
        $response = $this->postJson(route('marks.store.ajax'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'entry_mode'      => 'hacked_mode_xyz',
            'ct_mark'         => 10,
        ]);

        $response->assertStatus(422);

        // Invalid component mode in tabulation generation
        $tabResponse = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'invalid_tabulation_mode',
        ]);

        $tabResponse->assertSessionHasErrors('exam_component_mode');
    }

    /**
     * Case 7: CT-only grading uses CT's configured full marks and proportional 33% threshold
     */
    public function test_07_ct_only_grading_uses_configured_full_marks_and_proportional_33_threshold()
    {
        $this->actingAs($this->user);

        // Student A: CT = 3.0 (< 3.3) -> F (0.00)
        // Student B: CT = 3.5 (>= 3.3, 35%) -> D (1.00)
        // Student C: CT = 8.5 (85%) -> A+ (5.00)
        $studentA = $this->createStudent('10', 'Student Fail', $this->classPlay);
        $studentB = $this->createStudent('11', 'Student D', $this->classPlay);
        $studentC = $this->createStudent('12', 'Student Top', $this->classPlay);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentA->id,
            'ct_mark'         => 3.0,
            'total_mark'      => 3.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentB->id,
            'ct_mark'         => 3.5,
            'total_mark'      => 3.5,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentC->id,
            'ct_mark'         => 8.5,
            'total_mark'      => 8.5,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        // Request CT-only tabulation
        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'ct_only',
        ]);

        $response->assertStatus(200);
        // Returns stream PDF
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 8: MT-only grading uses MT's configured full marks and proportional 33% threshold
     */
    public function test_08_mt_only_grading_uses_configured_full_marks_and_proportional_33_threshold()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('13', 'Student MT', $this->classPlay);

        // MT full marks is 20. 33% threshold = 6.60
        // Score = 15.0 -> 75% -> A (4.00)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'mcq_mark'        => 15.0,
            'total_mark'      => 15.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'mt_only',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 9: CT + MT totals and grades use the sum of the selected components' full marks
     */
    public function test_09_ct_plus_mt_totals_and_grades_use_sum_of_selected_components_full_marks()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('14', 'Student CT MT', $this->classPlay);

        // CT Full = 10, MT Full = 20 -> Combined Full = 30. 33% threshold = 9.90
        // CT = 8, MT = 16 -> Obtained = 24 / 30 = 80% -> A+ (5.00)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 8.0,
            'mcq_mark'        => 16.0,
            'total_mark'      => 24.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'ct_mt',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 10: Terminal-only grading uses Terminal's configured full marks and proportional 33% threshold
     */
    public function test_10_terminal_only_grading_uses_terminal_configured_full_marks_and_proportional_33_threshold()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('15', 'Student Term', $this->classPlay);

        // Terminal Full = 70. 33% threshold = 23.1
        // Score = 56.0 / 70 = 80% -> A+ (5.00)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'written_mark'    => 56.0,
            'total_mark'      => 56.0,
            'letter_grade'    => 'B',
            'grade_point'     => 3.0,
        ]);

        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'terminal_only',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 11: Partial-mode calculations do not mutate stored totals, letter grades, or grade points
     */
    public function test_11_partial_mode_calculations_do_not_mutate_stored_totals_letter_grades_or_grade_points()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('16', 'Student Immutable', $this->classPlay);

        $mark = Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 8.0,
            'mcq_mark'        => 16.0,
            'written_mark'    => 50.0,
            'total_mark'      => 74.0,
            'letter_grade'    => 'A',
            'grade_point'     => 4.0,
        ]);

        // Generate CT-only tabulation
        $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'ct_only',
        ]);

        // Assert database record was NOT mutated
        $markFresh = $mark->fresh();
        $this->assertEquals(74.0, (float)$markFresh->total_mark);
        $this->assertEquals('A', $markFresh->letter_grade);
        $this->assertEquals(4.0, (float)$markFresh->grade_point);
    }

    /**
     * Case 12: Overall Pass/Fail, CGPA, and merit ranking use selected evaluation results
     */
    public function test_12_overall_pass_fail_cgpa_and_merit_ranking_use_selected_evaluation_results()
    {
        $this->actingAs($this->user);
        $studentTop = $this->createStudent('17', 'Top Student', $this->classPlay);
        $studentFail = $this->createStudent('18', 'Failing Student', $this->classPlay);

        // Top student has CT = 9.0 in Math and CT = 8.0 in English
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentTop->id,
            'ct_mark'         => 9.0,
            'total_mark'      => 9.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $studentTop->id,
            'ct_mark'         => 8.0,
            'total_mark'      => 8.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        // Failing student has CT = 2.0 in Math (< 3.3)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentFail->id,
            'ct_mark'         => 2.0,
            'total_mark'      => 2.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'ct_only',
            'sort_by'             => 'merit',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 13: Class 10's MCQ + Written CQ + Practical behavior remains unchanged
     */
    public function test_13_class_ten_mcq_written_practical_behavior_and_layout_remain_unchanged()
    {
        $this->actingAs($this->user);
        $studentTen = $this->createStudent('19', 'Class Ten Student', $this->classTen);

        // In Marks Entry for Class 10, entry_mode is forced to 'all'
        $indexResponse = $this->get(route('marks.index', [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classTen->id,
            'subject_id'      => $this->subjectMath->id,
            'entry_mode'      => 'ct_only', // should be ignored for Class 10
        ]));

        $indexResponse->assertStatus(200);
        $indexResponse->assertViewHas('entryMode', 'all');

        // Store marks for Class 10
        $storeResponse = $this->postJson(route('marks.store.ajax'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classTen->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentTen->id,
            'entry_mode'      => 'ct_only', // ignored for Class 10
            'mcq_mark'        => 25.0,
            'written_mark'    => 40.0,
            'ct_mark'         => 18.0, // Practical
        ]);

        $storeResponse->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'total'        => 83.0,
                'letter_grade' => 'A+',
                'grade_point'  => 5.0,
            ]);

        // In Tabulation, Class 10 is forced to 'all' composite mode
        $tabResponse = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classTen->id,
            'exam_component_mode' => 'ct_only', // should be forced to 'all' for Class 10
        ]);

        $tabResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $tabResponse->headers->get('Content-Type'));
    }

    /**
     * Case 14: Default tabulation All Components renders exact output
     */
    public function test_14_default_tabulation_all_components_renders_exact_output()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('20', 'Student Default', $this->classPlay);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'mcq_mark'        => 20.0,
            'written_mark'    => 65.0,
            'total_mark'      => 95.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            // no exam_component_mode passed -> defaults to 'all'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 15: Missing marks, absent students, and zero full marks handled safely
     */
    public function test_15_missing_marks_absent_students_and_zero_full_marks_handled_safely()
    {
        $this->actingAs($this->user);
        // Student with no marks created
        $studentEmpty = $this->createStudent('21', 'Student Empty', $this->classPlay);

        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'exam_component_mode' => 'ct_only',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 16: Existing filters, permissions, and section handling remain functional
     */
    public function test_16_existing_filters_permissions_and_section_handling_remain_functional()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('22', 'Student Section Filter', $this->classPlay);

        $response = $this->post(route('results.tabulation.generate'), [
            'session_year_id'     => $this->session->id,
            'branch_id'           => $this->branch->id,
            'exam_id'             => $this->examTerm2->id,
            'class_id'            => $this->classPlay->id,
            'section_id'          => $this->sectionA->id,
            'sort_by'             => 'roll',
            'exam_component_mode' => 'ct_mt',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Case 17: Negative marks are rejected in all modes
     */
    public function test_17_negative_marks_are_rejected_in_all_modes()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('23', 'Student Neg', $this->classPlay);

        $response = $this->postJson(route('marks.store.ajax'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'entry_mode'      => 'ct_only',
            'ct_mark'         => -5,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Marks cannot be negative!']);
    }

    /**
     * Case 18: Marks exceeding configured base marks are rejected
     */
    public function test_18_marks_exceeding_configured_base_marks_are_rejected()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('24', 'Student Overflow', $this->classPlay);

        // Schedule Math CT base mark is 10
        $response = $this->postJson(route('marks.store.ajax'), [
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'entry_mode'      => 'ct_only',
            'ct_mark'         => 15, // > 10
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Helper to execute a request and capture the view data passed to tabulation_pdf
     */
    protected function captureTabulationData(callable $callback): array
    {
        $captured = [];
        \Illuminate\Support\Facades\View::composer('pages.results.tabulation_pdf', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $callback();

        return $captured;
    }

    /**
     * Case 19: S.B.A and Physical Education exempt subjects (CT = 0) are omitted in CT Only mode
     */
    public function test_19_sba_and_physical_education_exempt_subjects_do_not_fail_student_in_ct_only_mode()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('25', 'Student With Exempt Subjects', $this->classPlay);

        // S.B.A: ct_marks = 0, mcq_marks = 0, written_marks = 100
        $subjectSba = Subject::create([
            'subject_name' => 'S.B.A',
            'subject_code' => 'SBA101',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectSba->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 100,
        ]);

        // Physical Education: ct_marks = 0, mcq_marks = 0, written_marks = 35
        $subjectPhysical = Subject::create([
            'subject_name' => 'শারীরিক শিক্ষা',
            'subject_code' => 'PHY101',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectPhysical->id,
            'full_marks'    => 35,
            'pass_marks'    => 12,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 35,
        ]);

        // Student has 10/10 in Math CT, 10/10 in English CT
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'total_mark'      => 10.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'total_mark'      => 10.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        // S.B.A has stored terminal marks in DB, CT is 0
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectSba->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'written_mark'    => 85.0,
            'total_mark'      => 85.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        // Physical Education has stored terminal marks in DB, CT is 0
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectPhysical->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'written_mark'    => 32.0,
            'total_mark'      => 32.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $this->classPlay->id,
                'exam_component_mode' => 'ct_only',
            ]);
            $response->assertStatus(200);
            $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        });

        $this->assertNotEmpty($captured);
        $studentData = $captured['studentData'];
        $firstStudent = $studentData[0];

        // Must NOT be FAIL; CGPA must be 5.00
        $this->assertEquals('A+', $firstStudent->final_grade);
        $this->assertEquals('5.00', $firstStudent->cgpa);
        $this->assertEquals(20.0, (float)$firstStudent->grand_total);

        // Entire subject column group must be omitted in CT Only mode
        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertNotContains($subjectSba->id, $visibleScheduleIds);
        $this->assertNotContains($subjectPhysical->id, $visibleScheduleIds);
        $this->assertContains($this->subjectMath->id, $visibleScheduleIds);
        $this->assertContains($this->subjectEnglish->id, $visibleScheduleIds);

        // partial_marks must not contain the omitted subjects
        $pMarks = $firstStudent->partial_marks;
        $this->assertFalse($pMarks->has($subjectSba->id));
        $this->assertFalse($pMarks->has($subjectPhysical->id));

        // Applicable subjects must have letter_grade = 'A+'
        $this->assertEquals('A+', $pMarks->get($this->subjectMath->id)->letter_grade);
        $this->assertEquals('A+', $pMarks->get($this->subjectEnglish->id)->letter_grade);
    }

    /**
     * Case 20: Exempt subjects (MT = 0) are omitted in MT Only mode
     */
    public function test_20_exempt_subjects_do_not_fail_student_in_mt_only_mode()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('26', 'Student MT Exempt', $this->classPlay);

        $subjectSba = Subject::create([
            'subject_name' => 'S.B.A',
            'subject_code' => 'SBA102',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectSba->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 100,
        ]);

        // Student scored 20/20 in Math MT, 20/20 in English MT
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'mcq_mark'        => 20.0,
            'total_mark'      => 20.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $student->id,
            'mcq_mark'        => 20.0,
            'total_mark'      => 20.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectSba->id,
            'student_id'      => $student->id,
            'mcq_mark'        => 0.0,
            'written_mark'    => 80.0,
            'total_mark'      => 80.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $this->classPlay->id,
                'exam_component_mode' => 'mt_only',
            ]);
            $response->assertStatus(200);
        });

        $studentData = $captured['studentData'];
        $this->assertEquals('A+', $studentData[0]->final_grade);
        $this->assertEquals('5.00', $studentData[0]->cgpa);

        // Entire subject column group omitted in MT Only
        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertNotContains($subjectSba->id, $visibleScheduleIds);
        $this->assertFalse($studentData[0]->partial_marks->has($subjectSba->id));
    }

    /**
     * Case 21: Exempt subjects are omitted in CT + MT mode when both CT and MT are zero
     */
    public function test_21_exempt_subjects_do_not_fail_student_in_ct_mt_mode()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('27', 'Student CT MT Exempt', $this->classPlay);

        $subjectSba = Subject::create([
            'subject_name' => 'S.B.A',
            'subject_code' => 'SBA103',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectSba->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 100,
        ]);

        // Student scored CT = 10, MT = 20 in Math and English (total 30/30)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'mcq_mark'        => 20.0,
            'total_mark'      => 30.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'mcq_mark'        => 20.0,
            'total_mark'      => 30.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectSba->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'mcq_mark'        => 0.0,
            'written_mark'    => 85.0,
            'total_mark'      => 85.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $this->classPlay->id,
                'exam_component_mode' => 'ct_mt',
            ]);
            $response->assertStatus(200);
        });

        $studentData = $captured['studentData'];
        $this->assertEquals('A+', $studentData[0]->final_grade);
        $this->assertEquals('5.00', $studentData[0]->cgpa);

        // Entire subject column group omitted in CT + MT when both are 0
        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertNotContains($subjectSba->id, $visibleScheduleIds);
        $this->assertFalse($studentData[0]->partial_marks->has($subjectSba->id));
    }

    /**
     * Case 22: Actual failure in an applicable subject is strictly detected as FAIL
     */
    public function test_22_actual_failure_in_applicable_subject_is_strictly_detected_as_fail()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('28', 'Student Real Fail', $this->classPlay);

        // Subject S.B.A (Exempt)
        $subjectSba = Subject::create([
            'subject_name' => 'S.B.A',
            'subject_code' => 'SBA104',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectSba->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 100,
        ]);

        // Student failed Math CT: scored 2.0 / 10.0 (< 3.33% threshold)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 2.0,
            'total_mark'      => 2.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        // Student passed English CT: scored 9.0 / 10.0
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $student->id,
            'ct_mark'         => 9.0,
            'total_mark'      => 9.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        // S.B.A has mark in DB
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectSba->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'written_mark'    => 90.0,
            'total_mark'      => 90.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $this->classPlay->id,
                'exam_component_mode' => 'ct_only',
            ]);
            $response->assertStatus(200);
        });

        $studentData = $captured['studentData'];
        $firstStudent = $studentData[0];

        // Real failure must result in overall FAIL and CGPA 0.00
        $this->assertEquals('F', $firstStudent->final_grade);
        $this->assertEquals('0.00', $firstStudent->cgpa);
        $this->assertEquals('F', $firstStudent->partial_marks->get($this->subjectMath->id)->letter_grade);
        $this->assertEquals('A+', $firstStudent->partial_marks->get($this->subjectEnglish->id)->letter_grade);

        // S.B.A was omitted from schedules, and Math failure is strictly preserved
        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertNotContains($subjectSba->id, $visibleScheduleIds);
    }

    /**
     * Case 23: Missing marks for an applicable subject are NOT omitted and remain visible
     */
    public function test_23_missing_marks_for_applicable_subject_are_not_silently_treated_as_exempt()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('29', 'Student Missing Mark', $this->classPlay);

        // Student has Math mark entered, but English mark NOT entered
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 9.0,
            'total_mark'      => 9.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $this->classPlay->id,
                'exam_component_mode' => 'ct_only',
            ]);
            $response->assertStatus(200);
        });

        $studentData = $captured['studentData'];
        $pMarks = $studentData[0]->partial_marks;

        // English has configured ct_marks = 10 > 0, so it MUST remain visible in schedules!
        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertContains($this->subjectEnglish->id, $visibleScheduleIds);
        $this->assertTrue($pMarks->has($this->subjectEnglish->id));
        $this->assertFalse($pMarks->get($this->subjectEnglish->id)->has_mark);
        $this->assertEquals('-', $pMarks->get($this->subjectEnglish->id)->letter_grade);
    }

    /**
     * Case 24: CGPA and merit ranking exclude exempt subjects correctly
     */
    public function test_24_cgpa_and_merit_ranking_exclude_exempt_subjects_correctly()
    {
        $this->actingAs($this->user);
        $studentTop = $this->createStudent('30', 'Top Rank Student', $this->classPlay);
        $studentSecond = $this->createStudent('31', 'Second Rank Student', $this->classPlay);

        // Add S.B.A subject (Exempt)
        $subjectSba = Subject::create([
            'subject_name' => 'S.B.A',
            'subject_code' => 'SBA105',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectSba->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 100,
        ]);

        // Student Top: Math CT = 10 (A+, 5.0), English CT = 10 (A+, 5.0) -> Total = 20, CGPA = 5.00
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentTop->id,
            'ct_mark'         => 10.0,
            'total_mark'      => 10.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $studentTop->id,
            'ct_mark'         => 10.0,
            'total_mark'      => 10.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        // Student Second: Math CT = 7 (A, 4.0), English CT = 7 (A, 4.0) -> Total = 14, CGPA = 4.00
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $studentSecond->id,
            'ct_mark'         => 7.0,
            'total_mark'      => 7.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $studentSecond->id,
            'ct_mark'         => 7.0,
            'total_mark'      => 7.0,
            'letter_grade'    => 'F',
            'grade_point'     => 0.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $this->classPlay->id,
                'exam_component_mode' => 'ct_only',
                'sort_by'             => 'merit',
            ]);
            $response->assertStatus(200);
        });

        $studentData = $captured['studentData'];
        $this->assertEquals($studentTop->id, $studentData[0]->student->id);
        $this->assertEquals('5.00', $studentData[0]->cgpa);
        $this->assertEquals(1, $studentData[0]->merit_rank);

        $this->assertEquals($studentSecond->id, $studentData[1]->student->id);
        $this->assertEquals('4.00', $studentData[1]->cgpa);
        $this->assertEquals(2, $studentData[1]->merit_rank);

        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertNotContains($subjectSba->id, $visibleScheduleIds);
    }

    /**
     * Case 25: All Components default mode remains unchanged with exempt subjects present
     */
    public function test_25_all_components_default_mode_remains_unchanged_with_exempt_subjects()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('32', 'Student Composite Default', $this->classPlay);

        $subjectSba = Subject::create([
            'subject_name' => 'S.B.A',
            'subject_code' => 'SBA106',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectSba->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 100,
        ]);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'mcq_mark'        => 20.0,
            'written_mark'    => 60.0,
            'total_mark'      => 90.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectSba->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'mcq_mark'        => 0.0,
            'written_mark'    => 85.0,
            'total_mark'      => 85.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id' => $this->session->id,
                'branch_id'       => $this->branch->id,
                'exam_id'         => $this->examTerm2->id,
                'class_id'        => $this->classPlay->id,
                // no exam_component_mode -> defaults to 'all'
            ]);
            $response->assertStatus(200);
        });

        $studentData = $captured['studentData'];
        $this->assertEquals('A+', $studentData[0]->final_grade);
        $this->assertEquals('5.00', $studentData[0]->cgpa);
        $this->assertEquals(175.0, (float)$studentData[0]->grand_total);

        // In All Components, S.B.A is RETAINED!
        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertContains($subjectSba->id, $visibleScheduleIds);
    }

    /**
     * Case 26: Subject with only one component applicable remains visible in CT + MT mode
     */
    public function test_26_subject_with_only_one_component_applicable_remains_visible_in_ct_mt_mode()
    {
        $this->actingAs($this->user);
        $student = $this->createStudent('33', 'Student Single Component', $this->classPlay);

        // Subject Arabic: CT = 10, MT = 0 (only CT applicable)
        $subjectArabic = Subject::create([
            'subject_name' => 'Arabic',
            'subject_code' => 'ARB101',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectArabic->id,
            'full_marks'    => 50,
            'pass_marks'    => 17,
            'ct_marks'      => 10,
            'mcq_marks'     => 0,
            'written_marks' => 40,
        ]);

        // Subject Drawing: CT = 0, MT = 10 (only MT applicable)
        $subjectDrawing = Subject::create([
            'subject_name' => 'Drawing',
            'subject_code' => 'DRW101',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectDrawing->id,
            'full_marks'    => 50,
            'pass_marks'    => 17,
            'ct_marks'      => 0,
            'mcq_marks'     => 10,
            'written_marks' => 40,
        ]);

        // Subject S.B.A: CT = 0, MT = 0 (neither applicable)
        $subjectSba = Subject::create([
            'subject_name' => 'S.B.A',
            'subject_code' => 'SBA107',
            'class_id'     => $this->classPlay->id,
            'user_id'      => $this->user->id,
        ]);
        ExamSchedule::create([
            'branch_id'     => $this->branch->id,
            'exam_id'       => $this->examTerm2->id,
            'class_id'      => $this->classPlay->id,
            'subject_id'    => $subjectSba->id,
            'full_marks'    => 100,
            'pass_marks'    => 33,
            'ct_marks'      => 0,
            'mcq_marks'     => 0,
            'written_marks' => 100,
        ]);

        // Marks for student:
        // Math: CT = 10, MT = 20 (30/30 -> A+)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectMath->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'mcq_mark'        => 20.0,
            'total_mark'      => 30.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);
        // English: CT = 10, MT = 20 (30/30 -> A+)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $this->subjectEnglish->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'mcq_mark'        => 20.0,
            'total_mark'      => 30.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);
        // Arabic: CT = 10, MT = 0 (10/10 -> A+)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectArabic->id,
            'student_id'      => $student->id,
            'ct_mark'         => 10.0,
            'mcq_mark'        => 0.0,
            'total_mark'      => 10.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);
        // Drawing: CT = 0, MT = 10 (10/10 -> A+)
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectDrawing->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'mcq_mark'        => 10.0,
            'total_mark'      => 10.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);
        // S.B.A has stored terminal mark
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classPlay->id,
            'subject_id'      => $subjectSba->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'mcq_mark'        => 0.0,
            'written_mark'    => 80.0,
            'total_mark'      => 80.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        $captured = $this->captureTabulationData(function () {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $this->classPlay->id,
                'exam_component_mode' => 'ct_mt',
            ]);
            $response->assertStatus(200);
        });

        $visibleScheduleIds = $captured['schedules']->pluck('subject_id')->toArray();

        // Arabic and Drawing must be visible because at least one component applies!
        $this->assertContains($subjectArabic->id, $visibleScheduleIds);
        $this->assertContains($subjectDrawing->id, $visibleScheduleIds);

        // S.B.A must be omitted because neither applies!
        $this->assertNotContains($subjectSba->id, $visibleScheduleIds);

        // Student's result is evaluated across the 4 applicable subjects (Math, English, Arabic, Drawing)
        $studentData = $captured['studentData'];
        $this->assertEquals('A+', $studentData[0]->final_grade);
        $this->assertEquals('5.00', $studentData[0]->cgpa);
        $this->assertEquals(80.0, (float)$studentData[0]->grand_total);
    }

    /**
     * Case 27: Hybrid schedules (some with exam_id and some with null exam_id) retain all applicable subjects in CT Only mode
     */
    public function test_27_hybrid_schedules_retain_all_applicable_subjects_in_ct_only()
    {
        $classSix = Classes::create(['class_name' => 'Six', 'user_id' => $this->user->id]);
        $sub1 = Subject::create(['subject_name' => 'Bangla 1st', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $sub2 = Subject::create(['subject_name' => 'Bangla 2nd', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $sub3 = Subject::create(['subject_name' => 'Math', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $sub4 = Subject::create(['subject_name' => 'ICT', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $subSba = Subject::create(['subject_name' => 'S.B.A', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);

        // Sub 1 & 3 have exam_id set to $this->examTerm2->id
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $sub1->id,
            'ct_marks'        => 10.0,
            'mcq_marks'       => 20.0,
            'written_marks'   => 70.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $sub3->id,
            'ct_marks'        => 10.0,
            'mcq_marks'       => 20.0,
            'written_marks'   => 70.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);

        // Sub 2 & 4 have exam_id NULL (Class default schedules)
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $sub2->id,
            'ct_marks'        => 5.0,
            'mcq_marks'       => 10.0,
            'written_marks'   => 35.0,
            'full_marks'      => 50.0,
            'pass_marks'      => 17.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $sub4->id,
            'ct_marks'        => 5.0,
            'mcq_marks'       => 10.0,
            'written_marks'   => 35.0,
            'full_marks'      => 50.0,
            'pass_marks'      => 17.0,
        ]);

        // S.B.A has exam_id NULL with CT = 0, MT = 0
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $subSba->id,
            'ct_marks'        => 0.0,
            'mcq_marks'       => 0.0,
            'written_marks'   => 100.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);

        $student = $this->createStudent('1', 'Sakib Ahmed', $classSix);

        foreach ([$sub1, $sub2, $sub3, $sub4] as $sub) {
            Mark::create([
                'session_year_id' => $this->session->id,
                'branch_id'       => $this->branch->id,
                'exam_id'         => $this->examTerm2->id,
                'class_id'        => $classSix->id,
                'subject_id'      => $sub->id,
                'student_id'      => $student->id,
                'ct_mark'         => 5.0,
                'mcq_mark'        => 10.0,
                'written_mark'    => 30.0,
                'total_mark'      => 45.0,
                'letter_grade'    => 'A',
                'grade_point'     => 4.0,
            ]);
        }

        $captured = $this->captureTabulationData(function () use ($classSix) {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $classSix->id,
                'exam_component_mode' => 'ct_only',
            ]);
            $response->assertStatus(200);
        });

        $visibleSubjectIds = $captured['schedules']->pluck('subject_id')->toArray();

        // All 4 applicable subjects (both exam_id=2 and exam_id=NULL) MUST be visible
        $this->assertContains($sub1->id, $visibleSubjectIds);
        $this->assertContains($sub2->id, $visibleSubjectIds);
        $this->assertContains($sub3->id, $visibleSubjectIds);
        $this->assertContains($sub4->id, $visibleSubjectIds);
        $this->assertCount(4, $visibleSubjectIds);

        // S.B.A must be omitted in CT Only
        $this->assertNotContains($subSba->id, $visibleSubjectIds);
    }

    /**
     * Case 28: Hybrid schedules retain all applicable subjects in MT Only mode
     */
    public function test_28_hybrid_schedules_retain_all_applicable_subjects_in_mt_only()
    {
        $classSix = Classes::create(['class_name' => 'Six', 'user_id' => $this->user->id]);
        $sub1 = Subject::create(['subject_name' => 'Bangla 1st', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $sub2 = Subject::create(['subject_name' => 'Bangla 2nd', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $subSba = Subject::create(['subject_name' => 'S.B.A', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);

        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $sub1->id,
            'ct_marks'        => 10.0,
            'mcq_marks'       => 20.0,
            'written_marks'   => 70.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $sub2->id,
            'ct_marks'        => 5.0,
            'mcq_marks'       => 10.0,
            'written_marks'   => 35.0,
            'full_marks'      => 50.0,
            'pass_marks'      => 17.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $subSba->id,
            'ct_marks'        => 0.0,
            'mcq_marks'       => 0.0,
            'written_marks'   => 100.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);

        $this->createStudent('2', 'Hasan Ali', $classSix);

        $captured = $this->captureTabulationData(function () use ($classSix) {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $classSix->id,
                'exam_component_mode' => 'mt_only',
            ]);
            $response->assertStatus(200);
        });

        $visibleSubjectIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertContains($sub1->id, $visibleSubjectIds);
        $this->assertContains($sub2->id, $visibleSubjectIds);
        $this->assertNotContains($subSba->id, $visibleSubjectIds);
        $this->assertCount(2, $visibleSubjectIds);
    }

    /**
     * Case 29: Hybrid schedules retain all applicable subjects in CT+MT mode
     */
    public function test_29_hybrid_schedules_retain_all_applicable_subjects_in_ct_mt()
    {
        $classSix = Classes::create(['class_name' => 'Six', 'user_id' => $this->user->id]);
        $sub1 = Subject::create(['subject_name' => 'Bangla 1st', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $sub2 = Subject::create(['subject_name' => 'Bangla 2nd', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $subSba = Subject::create(['subject_name' => 'S.B.A', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);

        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $sub1->id,
            'ct_marks'        => 10.0,
            'mcq_marks'       => 20.0,
            'written_marks'   => 70.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $sub2->id,
            'ct_marks'        => 5.0,
            'mcq_marks'       => 10.0,
            'written_marks'   => 35.0,
            'full_marks'      => 50.0,
            'pass_marks'      => 17.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $subSba->id,
            'ct_marks'        => 0.0,
            'mcq_marks'       => 0.0,
            'written_marks'   => 100.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);

        $this->createStudent('3', 'Rafiq Islam', $classSix);

        $captured = $this->captureTabulationData(function () use ($classSix) {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $classSix->id,
                'exam_component_mode' => 'ct_mt',
            ]);
            $response->assertStatus(200);
        });

        $visibleSubjectIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertContains($sub1->id, $visibleSubjectIds);
        $this->assertContains($sub2->id, $visibleSubjectIds);
        $this->assertNotContains($subSba->id, $visibleSubjectIds);
        $this->assertCount(2, $visibleSubjectIds);
    }

    /**
     * Case 30: Hybrid schedules in All Components mode retains all subjects including S.B.A
     */
    public function test_30_hybrid_schedules_in_all_components_mode_shows_all_subjects_including_sba()
    {
        $classSix = Classes::create(['class_name' => 'Six', 'user_id' => $this->user->id]);
        $sub1 = Subject::create(['subject_name' => 'Bangla 1st', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $sub2 = Subject::create(['subject_name' => 'Bangla 2nd', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);
        $subSba = Subject::create(['subject_name' => 'S.B.A', 'class_id' => $classSix->id, 'user_id' => $this->user->id]);

        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $sub1->id,
            'ct_marks'        => 10.0,
            'mcq_marks'       => 20.0,
            'written_marks'   => 70.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $sub2->id,
            'ct_marks'        => 5.0,
            'mcq_marks'       => 10.0,
            'written_marks'   => 35.0,
            'full_marks'      => 50.0,
            'pass_marks'      => 17.0,
        ]);
        ExamSchedule::create([
            'class_id'        => $classSix->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => null,
            'subject_id'      => $subSba->id,
            'ct_marks'        => 0.0,
            'mcq_marks'       => 0.0,
            'written_marks'   => 100.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);

        $this->createStudent('4', 'Tanvir Hasan', $classSix);

        $captured = $this->captureTabulationData(function () use ($classSix) {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $classSix->id,
                'exam_component_mode' => 'all',
            ]);
            $response->assertStatus(200);
        });

        $visibleSubjectIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertContains($sub1->id, $visibleSubjectIds);
        $this->assertContains($sub2->id, $visibleSubjectIds);
        $this->assertContains($subSba->id, $visibleSubjectIds);
        $this->assertCount(3, $visibleSubjectIds);
    }

    /**
     * Case 31: Missing schedule record for a subject with marks is handled safely without crashing
     */
    public function test_31_missing_schedule_record_handled_safely_without_crashing()
    {
        $classSeven = Classes::create(['class_name' => 'Class Seven Extra', 'user_id' => $this->user->id]);
        $sub1 = Subject::create(['subject_name' => 'Math', 'class_id' => $classSeven->id, 'user_id' => $this->user->id]);
        $subExtra = Subject::create(['subject_name' => 'Physical Education', 'class_id' => $classSeven->id, 'user_id' => $this->user->id]);

        ExamSchedule::create([
            'class_id'        => $classSeven->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $sub1->id,
            'ct_marks'        => 10.0,
            'mcq_marks'       => 20.0,
            'written_marks'   => 70.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);
        // Note: $subExtra has NO exam schedule at all

        $student = $this->createStudent('1', 'Karim Uddin', $classSeven);

        // Student has marks in both subjects
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $classSeven->id,
            'subject_id'      => $sub1->id,
            'student_id'      => $student->id,
            'ct_mark'         => 8.0,
            'mcq_mark'        => 16.0,
            'written_mark'    => 55.0,
            'total_mark'      => 79.0,
            'letter_grade'    => 'A',
            'grade_point'     => 4.0,
        ]);
        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $classSeven->id,
            'subject_id'      => $subExtra->id,
            'student_id'      => $student->id,
            'ct_mark'         => 0.0,
            'mcq_mark'        => 0.0,
            'written_mark'    => 50.0,
            'total_mark'      => 50.0,
            'letter_grade'    => 'B',
            'grade_point'     => 3.0,
        ]);

        // In CT Only, sub1 has CT>0 so it's visible, subExtra has ct_marks=0 so it's omitted
        $captured = $this->captureTabulationData(function () use ($classSeven) {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $classSeven->id,
                'exam_component_mode' => 'ct_only',
            ]);
            $response->assertStatus(200);
        });

        $visibleSubjectIds = $captured['schedules']->pluck('subject_id')->toArray();
        $this->assertContains($sub1->id, $visibleSubjectIds);
        $this->assertNotContains($subExtra->id, $visibleSubjectIds);

        // In All Components mode, both are visible
        $capturedAll = $this->captureTabulationData(function () use ($classSeven) {
            $response = $this->post(route('results.tabulation.generate'), [
                'session_year_id'     => $this->session->id,
                'branch_id'           => $this->branch->id,
                'exam_id'             => $this->examTerm2->id,
                'class_id'            => $classSeven->id,
                'exam_component_mode' => 'all',
            ]);
            $response->assertStatus(200);
        });

        $visibleAllSubjectIds = $capturedAll['schedules']->pluck('subject_id')->toArray();
        $this->assertContains($sub1->id, $visibleAllSubjectIds);
        $this->assertContains($subExtra->id, $visibleAllSubjectIds);
    }

    /**
     * Case 32: Class Ten MCQ + Written CQ + Practical workflow remains unchanged across all modes
     */
    public function test_32_class_ten_workflow_remains_unchanged_regardless_of_component_mode_request()
    {
        $studentTen = $this->createStudent('1', 'Fahim Muntasir', $this->classTen);

        $subGeneral = Subject::create(['subject_name' => 'Bangla 1st', 'class_id' => $this->classTen->id, 'user_id' => $this->user->id]);
        $subScience = Subject::create(['subject_name' => 'Physics', 'class_id' => $this->classTen->id, 'user_id' => $this->user->id]);

        // General subject: Written 70, MCQ 30, Practical 0 (stored in CT)
        ExamSchedule::create([
            'class_id'        => $this->classTen->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $subGeneral->id,
            'ct_marks'        => 0.0,
            'mcq_marks'       => 30.0,
            'written_marks'   => 70.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);

        // Science subject: Written 50, MCQ 25, Practical 25 (stored in CT)
        ExamSchedule::create([
            'class_id'        => $this->classTen->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'subject_id'      => $subScience->id,
            'ct_marks'        => 25.0,
            'mcq_marks'       => 25.0,
            'written_marks'   => 50.0,
            'full_marks'      => 100.0,
            'pass_marks'      => 33.0,
        ]);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classTen->id,
            'subject_id'      => $subGeneral->id,
            'student_id'      => $studentTen->id,
            'ct_mark'         => 0.0,
            'mcq_mark'        => 22.0,
            'written_mark'    => 54.0,
            'total_mark'      => 76.0,
            'letter_grade'    => 'A',
            'grade_point'     => 4.0,
        ]);

        Mark::create([
            'session_year_id' => $this->session->id,
            'branch_id'       => $this->branch->id,
            'exam_id'         => $this->examTerm2->id,
            'class_id'        => $this->classTen->id,
            'subject_id'      => $subScience->id,
            'student_id'      => $studentTen->id,
            'ct_mark'         => 22.0,
            'mcq_mark'        => 20.0,
            'written_mark'    => 40.0,
            'total_mark'      => 82.0,
            'letter_grade'    => 'A+',
            'grade_point'     => 5.0,
        ]);

        foreach (['ct_only', 'mt_only', 'ct_mt', 'terminal_only'] as $mode) {
            $captured = $this->captureTabulationData(function () use ($mode) {
                $response = $this->post(route('results.tabulation.generate'), [
                    'session_year_id'     => $this->session->id,
                    'branch_id'           => $this->branch->id,
                    'exam_id'             => $this->examTerm2->id,
                    'class_id'            => $this->classTen->id,
                    'exam_component_mode' => $mode,
                ]);
                $response->assertStatus(200);
            });

            // Class 10 is forced to 'all' mode
            $this->assertEquals('all', $captured['componentMode']);
            $this->assertStringContainsString('ten', strtolower($captured['schoolClass']->class_name));

            // Both subjects are visible
            $visibleSubjectIds = $captured['schedules']->pluck('subject_id')->toArray();
            $this->assertContains($subGeneral->id, $visibleSubjectIds);
            $this->assertContains($subScience->id, $visibleSubjectIds);

            // Composite marks and final grade calculated as normal
            $studentData = $captured['studentData'];
            $this->assertEquals('A', $studentData[0]->final_grade);
            $this->assertEquals('4.50', $studentData[0]->cgpa);
            $this->assertEquals(158.0, (float)$studentData[0]->grand_total);
        }
    }
}


