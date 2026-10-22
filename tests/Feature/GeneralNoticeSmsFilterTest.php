<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Branch;
use App\Models\SessionYear;
use App\Models\Classes;
use App\Models\Section;
use App\Models\SmsLog;
use App\Models\SmsSchedule;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeneralNoticeSmsFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected SessionYear $session1;
    protected SessionYear $session2;
    protected Classes $class1;
    protected Classes $class2;
    protected Section $sectionA;
    protected Section $sectionB;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_notice_test@macsschool.com'],
            ['name' => 'Admin Notice Tester', 'password' => bcrypt('secret')]
        );

        $role = \HasinHayder\Tyro\Models\Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin']
        );
        $this->adminUser->assignRole($role);

        $this->actingAs($this->adminUser);

        // Seed basic fixtures
        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->session1 = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2025-2026', 'user_id' => $this->adminUser->id]);
        $this->session2 = SessionYear::firstOrCreate(['id' => 2], ['session_name' => '2026-2027', 'user_id' => $this->adminUser->id]);
        $this->class1 = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Class One', 'user_id' => $this->adminUser->id]);
        $this->class2 = Classes::firstOrCreate(['id' => 2], ['class_name' => 'Class Two', 'user_id' => $this->adminUser->id]);
        $this->sectionA = Section::firstOrCreate(['id' => 1], ['section_name' => 'Section A', 'user_id' => $this->adminUser->id]);
        $this->sectionB = Section::firstOrCreate(['id' => 2], ['section_name' => 'Section B', 'user_id' => $this->adminUser->id]);
    }

    protected function makeStudent(array $attributes = []): Student
    {
        static $counter = 100;
        $counter++;

        return Student::create(array_merge([
            'user_id'          => $this->adminUser->id,
            'branch_id'        => $this->branch->id,
            'session_year_id'  => $this->session1->id,
            'class_id'         => $this->class1->id,
            'section_id'       => $this->sectionA->id,
            'student_identity' => 'STD-' . $counter,
            'student_name'     => 'Student ' . $counter,
            'roll_number'      => (string) $counter,
            'gender'           => 'Male',
            'guardian_name'    => 'Guardian ' . $counter,
            'guardian_mobile'  => '01710' . str_pad((string)$counter, 6, '0', STR_PAD_LEFT),
            'father_name'      => 'Father ' . $counter,
            'father_mobile'    => '01710' . str_pad((string)$counter, 6, '0', STR_PAD_LEFT),
            'mother_name'      => 'Mother ' . $counter,
            'mother_mobile'    => '01710' . str_pad((string)$counter, 6, '0', STR_PAD_LEFT),
            'dob'              => '2015-01-01',
            'present_village'        => 'Village',
            'present_post_office'    => 'Post Office',
            'present_district'       => 'District',
            'present_division'       => 'Division',
            'permanent_village'      => 'Village',
            'permanent_post_office'  => 'Post Office',
            'permanent_district'     => 'District',
            'permanent_division'     => 'Division',
            'status'           => 'Active',
            'sms_status'       => 'Active',
        ], $attributes));
    }

    public function test_general_notice_page_renders_successfully()
    {
        $response = $this->get(route('sms.general-notice'));

        $response->assertStatus(200);
        $response->assertSee('General Notice Broadcast');
        $response->assertSee('Recipient Filters');
        $response->assertSee('Academic Session');
    }

    public function test_recipient_summary_strict_and_logic()
    {
        // 1. Session 1, Class 1, Section A, Male -> Should match
        $st1 = $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionA->id,
            'gender'          => 'Male',
            'status'          => 'Active',
        ]);

        // 2. Session 1, Class 1, Section A, Female -> Should NOT match when gender=Male
        $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionA->id,
            'gender'          => 'Female',
            'status'          => 'Active',
        ]);

        // 3. Session 1, Class 1, Section B, Male -> Should NOT match when section=Section A
        $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionB->id,
            'gender'          => 'Male',
            'status'          => 'Active',
        ]);

        // 4. Session 2, Class 1, Section A, Male -> Should NOT match when session=Session 1
        $this->makeStudent([
            'session_year_id' => $this->session2->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionA->id,
            'gender'          => 'Male',
            'status'          => 'Active',
        ]);

        // 5. Inactive student -> Should NOT match
        $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionA->id,
            'gender'          => 'Male',
            'status'          => 'Inactive',
        ]);

        $payload = [
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionA->id,
            'gender'          => 'Male',
            'message'         => 'Test Notice Message',
        ];

        $response = $this->postJson(route('sms.general-notice.recipient-summary'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success'             => true,
            'total_students'      => 1,
            'valid_numbers_count' => 1,
            'unique_numbers_count'=> 1,
        ]);
    }

    public function test_sibling_deduplication_and_cost_calculation()
    {
        $sharedPhone = '01712345678';

        // 2 siblings sharing the same guardian mobile
        $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'student_name'    => 'Sibling One',
            'guardian_mobile' => $sharedPhone,
        ]);

        $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'student_name'    => 'Sibling Two',
            'guardian_mobile' => $sharedPhone,
        ]);

        // Another student with unique phone
        $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'student_name'    => 'Solo Student',
            'guardian_mobile' => '01899999999',
        ]);

        $payload = [
            'session_year_id' => $this->session1->id,
            'class_id'        => 'all',
            'section_id'      => 'all',
            'gender'          => 'all',
            'message'         => 'Hello Guardians',
        ];

        $response = $this->postJson(route('sms.general-notice.recipient-summary'), $payload);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(3, $data['total_students']);
        $this->assertEquals(3, $data['valid_numbers_count']);
        $this->assertEquals(2, $data['unique_numbers_count']); // 2 unique numbers!
        $this->assertEquals(1, $data['duplicate_phone_count']); // 1 duplicate saved
        $this->assertEquals(1, $data['sms_segments']);
        $this->assertEquals('GSM', $data['encoding']);

        // Rate is 0.33, 2 unique numbers * 1 segment * 0.33 = 0.66
        $this->assertEquals(0.66, $data['estimated_cost']);
    }

    public function test_unicode_bengali_encoding_detection_and_segment_count()
    {
        $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'guardian_mobile' => '01711223344',
        ]);

        // 75 Bengali characters -> should be 2 Unicode segments (1st <= 70, then ceil(75/67) = 2)
        $bengaliNotice = 'সকল সম্মানিত অভিভাবকদের জানানো যাচ্ছে যে আগামী রবিবার স্কুল বন্ধ থাকবে। ধন্যবাদ।';

        $payload = [
            'session_year_id' => $this->session1->id,
            'message'         => $bengaliNotice,
        ];

        $response = $this->postJson(route('sms.general-notice.recipient-summary'), $payload);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('Unicode', $data['encoding']);
        $this->assertGreaterThanOrEqual(1, $data['sms_segments']);
    }

    public function test_selective_students_filter_mode()
    {
        $st1 = $this->makeStudent(['session_year_id' => $this->session1->id]);
        $st2 = $this->makeStudent(['session_year_id' => $this->session1->id]);
        $st3 = $this->makeStudent(['session_year_id' => $this->session1->id]);

        $payload = [
            'session_year_id'      => $this->session1->id,
            'recipient_selection'  => 'selective',
            'selected_student_ids' => [$st1->id, $st3->id],
            'message'              => 'Notice to select students',
        ];

        $response = $this->postJson(route('sms.general-notice.recipient-summary'), $payload);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('total_students'));
        $this->assertEquals(2, $response->json('unique_numbers_count'));
    }

    public function test_preview_recipients_pagination_and_masking()
    {
        $st = $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'guardian_mobile' => '01712348107',
        ]);

        $response = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
            'page'            => 1,
            'per_page'        => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination' => ['current_page', 'last_page', 'per_page', 'total']
        ]);

        $students = $response->json('data');
        $this->assertNotEmpty($students);

        $first = collect($students)->firstWhere('id', $st->id);
        $this->assertNotNull($first);
        $this->assertEquals('valid', $first['phone_status']);
        $this->assertEquals('017****8107', $first['masked_phone']);
    }

    public function test_send_general_notice_dispatches_with_deduplication_and_audit_logs()
    {
        // Bind a mock SmsService so we NEVER send real SMS
        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->method('getBalance')->willReturn(['status' => 'success', 'balance' => '500.00']);
        $mockSmsService->expects($this->once()) // Only 1 SMS sent for 2 siblings!
            ->method('sendSms')
            ->willReturn(true);

        $this->app->instance(SmsService::class, $mockSmsService);

        $sharedPhone = '01711223399';
        $sibling1 = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => $sharedPhone]);
        $sibling2 = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => $sharedPhone]);

        $response = $this->postJson(route('sms.general-notice.send'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => 'all',
            'section_id'      => 'all',
            'gender'          => 'all',
            'message'         => 'Test broadcast to parents',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats'   => [
                'sent_count'            => 1,
                'total_students'        => 2,
                'sibling_shared_count'  => 1,
            ]
        ]);

        // Sibling student log was also created with Sibling Shared status
        $this->assertDatabaseHas('sms_logs', [
            'student_id' => $sibling2->id,
            'status'     => 'Sent (Sibling Shared)',
        ]);
    }

    public function test_concurrency_lock_rejects_concurrent_broadcast()
    {
        // Acquire lock first
        $lockKey = 'general_notice_broadcast_' . $this->adminUser->id;
        $lock = Cache::lock($lockKey, 30);
        $this->assertTrue($lock->get());

        try {
            $response = $this->postJson(route('sms.general-notice.send'), [
                'session_year_id' => $this->session1->id,
                'message'         => 'Concurrent test',
            ]);

            $response->assertStatus(429);
            $response->assertJson([
                'success' => false,
                'message' => 'Another broadcast is currently processing. Please wait a moment.',
            ]);
        } finally {
            $lock->release();
        }
    }

    public function test_send_general_notice_validates_required_fields()
    {
        $response = $this->postJson(route('sms.general-notice.send'), [
            'session_year_id' => '',
            'message'         => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_academic_session_isolation_in_summary_and_preview()
    {
        $stSession1 = $this->makeStudent(['session_year_id' => $this->session1->id]);
        $stSession2 = $this->makeStudent(['session_year_id' => $this->session2->id]);

        // When session1 is selected
        $summaryResp = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session1->id,
        ]);
        $summaryResp->assertJson(['total_students' => 1]);

        $previewResp = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
        ]);
        $previewIds = collect($previewResp->json('data'))->pluck('id')->all();
        $this->assertContains($stSession1->id, $previewIds);
        $this->assertNotContains($stSession2->id, $previewIds);

        // When session2 is selected
        $summaryResp2 = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session2->id,
        ]);
        $summaryResp2->assertJson(['total_students' => 1]);

        $previewResp2 = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session2->id,
        ]);
        $previewIds2 = collect($previewResp2->json('data'))->pluck('id')->all();
        $this->assertContains($stSession2->id, $previewIds2);
        $this->assertNotContains($stSession1->id, $previewIds2);
    }

    public function test_gender_filter_boys_and_girls()
    {
        $male = $this->makeStudent(['session_year_id' => $this->session1->id, 'gender' => 'Male']);
        $female = $this->makeStudent(['session_year_id' => $this->session1->id, 'gender' => 'Female']);

        // Filter Male
        $respMale = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session1->id,
            'gender'          => 'Male',
        ]);
        $respMale->assertJson(['total_students' => 1]);

        $prevMale = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
            'gender'          => 'Male',
        ]);
        $prevMaleIds = collect($prevMale->json('data'))->pluck('id')->all();
        $this->assertContains($male->id, $prevMaleIds);
        $this->assertNotContains($female->id, $prevMaleIds);

        // Filter Female
        $respFemale = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session1->id,
            'gender'          => 'Female',
        ]);
        $respFemale->assertJson(['total_students' => 1]);

        $prevFemale = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
            'gender'          => 'Female',
        ]);
        $prevFemaleIds = collect($prevFemale->json('data'))->pluck('id')->all();
        $this->assertContains($female->id, $prevFemaleIds);
        $this->assertNotContains($male->id, $prevFemaleIds);
    }

    public function test_class_and_section_combined_filtering()
    {
        $c1sA = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class1->id, 'section_id' => $this->sectionA->id]);
        $c1sB = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class1->id, 'section_id' => $this->sectionB->id]);
        $c2sA = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class2->id, 'section_id' => $this->sectionA->id]);

        // Filter: class 1, section B
        $resp = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionB->id,
        ]);
        $resp->assertJson(['total_students' => 1]);

        $prev = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionB->id,
        ]);
        $prevIds = collect($prev->json('data'))->pluck('id')->all();
        $this->assertEquals([$c1sB->id], $prevIds);
    }

    public function test_selective_mode_empty_selection_returns_zero_students()
    {
        $this->makeStudent(['session_year_id' => $this->session1->id]);

        $resp = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id'      => $this->session1->id,
            'recipient_selection'  => 'selective',
            'selected_student_ids' => [],
        ]);

        $resp->assertJson([
            'success'        => true,
            'total_students' => 0,
            'estimated_cost' => 0,
        ]);
    }

    public function test_user_requested_three_filter_scenarios()
    {
        // Setup 6 students across classes and sections:
        // Class 1, Section A: 1 Boy, 1 Girl
        $c1sAboy  = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class1->id, 'section_id' => $this->sectionA->id, 'gender' => 'Male']);
        $c1sAgirl = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class1->id, 'section_id' => $this->sectionA->id, 'gender' => 'Female']);

        // Class 1, Section B: 1 Boy, 1 Girl
        $c1sBboy  = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class1->id, 'section_id' => $this->sectionB->id, 'gender' => 'Male']);
        $c1sBgirl = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class1->id, 'section_id' => $this->sectionB->id, 'gender' => 'Female']);

        // Class 2, Section A: 1 Boy, 1 Girl
        $c2sAboy  = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class2->id, 'section_id' => $this->sectionA->id, 'gender' => 'Male']);
        $c2sAgirl = $this->makeStudent(['session_year_id' => $this->session1->id, 'class_id' => $this->class2->id, 'section_id' => $this->sectionA->id, 'gender' => 'Female']);

        // -------------------------------------------------------------
        // Scenario 1: পুরো স্কুলের Boys (All Eligible Students + Gender: Male)
        // -------------------------------------------------------------
        $resp1 = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => 'all',
            'section_id'      => 'all',
            'gender'          => 'Male',
        ]);
        $resp1->assertJson([
            'success'        => true,
            'total_students' => 3,
        ]);

        $prev1 = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => 'all',
            'section_id'      => 'all',
            'gender'          => 'Male',
        ]);
        $ids1 = collect($prev1->json('data'))->pluck('id')->sort()->values()->all();
        $expected1 = collect([$c1sAboy->id, $c1sBboy->id, $c2sAboy->id])->sort()->values()->all();
        $this->assertEquals($expected1, $ids1);

        // -------------------------------------------------------------
        // Scenario 2: একটি নির্দিষ্ট ক্লাসের Girls (Class 1 + Gender: Female)
        // -------------------------------------------------------------
        $resp2 = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => 'all',
            'gender'          => 'Female',
        ]);
        $resp2->assertJson([
            'success'        => true,
            'total_students' => 2,
        ]);

        $prev2 = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => 'all',
            'gender'          => 'Female',
        ]);
        $ids2 = collect($prev2->json('data'))->pluck('id')->sort()->values()->all();
        $expected2 = collect([$c1sAgirl->id, $c1sBgirl->id])->sort()->values()->all();
        $this->assertEquals($expected2, $ids2);

        // -------------------------------------------------------------
        // Scenario 3: একটি ক্লাসের নির্দিষ্ট সেকশনের Boys (Class 1 + Section A + Gender: Male)
        // -------------------------------------------------------------
        $resp3 = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionA->id,
            'gender'          => 'Male',
        ]);
        $resp3->assertJson([
            'success'        => true,
            'total_students' => 1,
        ]);

        $prev3 = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'session_year_id' => $this->session1->id,
            'class_id'        => $this->class1->id,
            'section_id'      => $this->sectionA->id,
            'gender'          => 'Male',
        ]);
        $ids3 = collect($prev3->json('data'))->pluck('id')->sort()->values()->all();
        $this->assertEquals([$c1sAboy->id], $ids3);
    }

    /**
     * Test comma/newline/semicolon/tab parsing, phone normalization, invalid rejection, and deduplication.
     */
    public function test_parse_custom_numbers_comma_newline_and_phone_normalization()
    {
        $rawInput = "01712345678, +8801812345678\n8801912345678; 01712345678\t01312345678, 01234567890\n12345, not-a-number";

        $result = \App\Http\Controllers\SmsController::parseCustomPhoneNumbers($rawInput);

        // Total input tokens
        $this->assertEquals(8, $result['tokens_count']);

        // 4 unique valid numbers: 8801712345678, 8801812345678, 8801912345678, 8801312345678
        $this->assertEquals(4, $result['unique_count']);

        // 1 duplicate (01712345678 entered twice)
        $this->assertEquals(1, $result['duplicate_count']);

        // 5 total valid entries (4 unique + 1 duplicate)
        $this->assertEquals(5, $result['valid_count']);

        // 3 invalid tokens: 01234567890 (invalid BD prefix 012), 12345 (too short), not-a-number
        $this->assertEquals(3, $result['invalid_count']);

        $this->assertContains('8801712345678', $result['valid_numbers']);
        $this->assertContains('8801812345678', $result['valid_numbers']);
        $this->assertContains('8801912345678', $result['valid_numbers']);
        $this->assertContains('8801312345678', $result['valid_numbers']);
    }

    /**
     * Test GSM vs Unicode encoding detection and segment calculation based on MIM SMS rules.
     */
    public function test_sms_encoding_and_segment_calculation()
    {
        // GSM: 160 chars = 1 segment
        $gsm1 = str_repeat('A', 160);
        $resGsm1 = \App\Http\Controllers\SmsController::calculateSmsSegments($gsm1);
        $this->assertEquals('GSM', $resGsm1['encoding']);
        $this->assertEquals(1, $resGsm1['segments']);
        $this->assertEquals(160, $resGsm1['characters']);

        // GSM multipart: 161 chars = 2 segments (153 chars per concatenated segment)
        $gsm2 = str_repeat('A', 161);
        $resGsm2 = \App\Http\Controllers\SmsController::calculateSmsSegments($gsm2);
        $this->assertEquals('GSM', $resGsm2['encoding']);
        $this->assertEquals(2, $resGsm2['segments']);

        // Unicode: 70 chars = 1 segment
        $uni1 = "বিদ্যালয়ের জরুরি বিজ্ঞপ্তি: আগামীকাল ক্লাস বন্ধ থাকবে।"; // ~54 unicode chars
        $resUni1 = \App\Http\Controllers\SmsController::calculateSmsSegments($uni1);
        $this->assertEquals('Unicode', $resUni1['encoding']);
        $this->assertEquals(1, $resUni1['segments']);

        // Unicode multipart: 71 chars = 2 segments (67 chars per concatenated segment)
        $uniLong = str_repeat('ক', 71);
        $resUniLong = \App\Http\Controllers\SmsController::calculateSmsSegments($uniLong);
        $this->assertEquals('Unicode', $resUniLong['encoding']);
        $this->assertEquals(2, $resUniLong['segments']);
    }

    /**
     * Test recipient summary endpoint with custom mobile numbers only.
     */
    public function test_recipient_summary_with_custom_numbers_only()
    {
        $response = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => "01712345678, 01812345678\n01912345678, 01712345678, 01200000000",
            'message'          => 'Emergency school notice',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'recipient_source'       => 'custom',
            'total_students'         => 0,
            'custom_tokens_count'    => 5,
            'custom_valid_count'     => 3, // 3 unique valid
            'custom_invalid_count'   => 1, // 01200000000
            'custom_duplicate_count' => 1, // 01712345678 repeated
            'unique_numbers_count'   => 3,
            'sms_segments'           => 1,
            'estimated_cost'         => 0.99, // 3 unique * 1 segment * 0.33
        ]);
    }

    /**
     * Test recipient summary endpoint with 'both' source, merging students and custom numbers with deduplication.
     */
    public function test_recipient_summary_with_both_source_and_overlap_deduplication()
    {
        $studentA = $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'guardian_mobile' => '01711111111',
        ]);
        $studentB = $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'guardian_mobile' => '01722222222',
        ]);

        // Custom numbers includes 01722222222 (which matches studentB) and 01733333333 (new)
        $response = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'recipient_source' => 'both',
            'session_year_id'  => $this->session1->id,
            'class_id'         => 'all',
            'section_id'       => 'all',
            'gender'           => 'all',
            'custom_numbers'   => "01722222222, 01733333333\n01733333333", // 01733333333 duplicate
            'message'          => 'Broadcast to both students and external contacts',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('both', $data['recipient_source']);
        $this->assertEquals(2, $data['total_students']);
        $this->assertEquals(2, $data['custom_valid_count']); // 2 unique custom (01722222222, 01733333333)

        // 3 unique overall: 01711111111 (studentA), 01722222222 (studentB & custom), 01733333333 (custom)
        $this->assertEquals(3, $data['unique_numbers_count']);

        // Overlapping duplicate avoided = 1 (studentB & custom) + 1 (custom internal duplicate) = 2
        $this->assertEquals(2, $data['duplicate_phone_count']);

        // Estimated cost = 3 * 1 segment * 0.33 = 0.99
        $this->assertEquals(0.99, $data['estimated_cost']);
    }

    /**
     * Test preview recipients endpoint with custom numbers source.
     */
    public function test_preview_recipients_with_custom_numbers()
    {
        $response = $this->postJson(route('sms.general-notice.preview-recipients'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => "01712345678, 01812345678\n01712345678, 01200000000",
            'per_page'         => 15,
            'page'             => 1,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(4, $data);

        // Check custom item attributes
        $this->assertTrue($data[0]['is_custom']);
        $this->assertEquals('Custom Number', $data[0]['class_name']);
        $this->assertEquals('valid', $data[0]['phone_status']);

        // Third item is duplicate
        $this->assertTrue($data[2]['is_custom']);
        $this->assertEquals('Duplicate Skipped', $data[2]['section_name']);

        // Fourth item is invalid
        $this->assertTrue($data[3]['is_custom']);
        $this->assertEquals('invalid', $data[3]['phone_status']);
    }

    /**
     * Test sending general notice with custom numbers only (verifying audit logs without student_id).
     */
    public function test_send_general_notice_with_custom_numbers_only()
    {
        $dispatchedPhones = [];
        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->method('getBalance')->willReturn(['status' => 'success', 'balance' => '500.00']);
        $mockSmsService->expects($this->exactly(2))
            ->method('sendSms')
            ->willReturnCallback(function ($phone, $msg, $studentId, $eventKey, $eventType) use (&$dispatchedPhones) {
                $dispatchedPhones[] = $phone;
                SmsLog::create([
                    'student_id'    => $studentId,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $phone,
                    'message'       => $msg,
                    'status'        => 'Sent',
                ]);
                return true;
            });

        $this->app->instance(SmsService::class, $mockSmsService);

        $response = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => "01712345678, 01812345678\n01712345678", // 2 unique valid, 1 duplicate
            'message'          => 'Emergency campus closure tomorrow.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats'   => [
                'sent_count'        => 2,
                'custom_sent_count' => 2,
            ]
        ]);

        // Verify exact dispatched normalized recipient phone numbers
        $this->assertEquals(['8801712345678', '8801812345678'], $dispatchedPhones);

        // Verify that SMS logs were created with student_id = null
        $logs = SmsLog::where('event_type', 'general_notice')->get();
        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertNull($log->student_id);
            $this->assertEquals('Sent', $log->status);
        }
    }

    /**
     * Test sending general notice with 'both' source, ensuring overlapping phone is only dispatched once.
     */
    public function test_send_general_notice_with_both_source_avoids_duplicate_dispatch()
    {
        $studentA = $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'guardian_mobile' => '01711111111',
        ]);
        $studentB = $this->makeStudent([
            'session_year_id' => $this->session1->id,
            'guardian_mobile' => '01722222222',
        ]);

        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->method('getBalance')->willReturn(['status' => 'success', 'balance' => '500.00']);

        // Exactly 3 sends: 8801711111111, 8801722222222, 8801733333333
        // 8801722222222 is shared between studentB and custom, so it must only be sent ONCE!
        $mockSmsService->expects($this->exactly(3))
            ->method('sendSms')
            ->willReturn(true);

        $this->app->instance(SmsService::class, $mockSmsService);

        $response = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'both',
            'session_year_id'  => $this->session1->id,
            'class_id'         => 'all',
            'section_id'       => 'all',
            'gender'           => 'all',
            'custom_numbers'   => "01722222222, 01733333333", // 01722222222 overlaps
            'message'          => 'Combined broadcast test.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats'   => [
                'sent_count'                 => 3,
                'custom_overlap_dedup_count' => 1,
            ]
        ]);
    }

    /**
     * Test send validation fails when custom numbers are empty or contain only invalid tokens.
     */
    public function test_send_general_notice_custom_numbers_empty_or_invalid_validation()
    {
        // 1. Empty custom numbers in custom mode
        $res1 = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => '',
            'message'          => 'Test message',
        ]);
        $res1->assertStatus(422);
        $res1->assertJsonFragment(['success' => false]);

        // 2. Only invalid numbers in custom mode
        $res2 = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => '01200000000, 12345, invalid',
            'message'          => 'Test message',
        ]);
        $res2->assertStatus(422);
        $res2->assertJsonFragment(['success' => false]);
    }

    /**
     * Test review eligibility and summary response with a single valid custom mobile number.
     */
    public function test_review_eligibility_with_valid_custom_recipients()
    {
        $response = $this->postJson(route('sms.general-notice.recipient-summary'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => '01712345678',
            'message'          => 'Important notice for custom contact.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'              => true,
            'recipient_source'     => 'custom',
            'custom_valid_count'   => 1,
            'unique_numbers_count' => 1,
            'sms_segments'         => 1,
            'encoding'             => 'GSM',
            'estimated_cost'       => 0.33,
        ]);
    }

    /**
     * Test empty message validation on send across all recipient sources.
     */
    public function test_empty_message_validation_across_all_sources()
    {
        // 1. Custom mode with empty message
        $resCustom = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => '01712345678',
            'message'          => '   ',
        ]);
        $resCustom->assertStatus(422);

        // 2. Students mode with empty message
        $this->makeStudent(['session_year_id' => $this->session1->id]);
        $resStudents = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'students',
            'session_year_id'  => $this->session1->id,
            'message'          => '',
        ]);
        $resStudents->assertStatus(422);

        // 3. Both mode with empty message
        $resBoth = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'both',
            'session_year_id'  => $this->session1->id,
            'custom_numbers'   => '01712345678',
            'message'          => '',
        ]);
        $resBoth->assertStatus(422);
    }

    /**
     * Test that modals are properly nested inside the Alpine.js noticeBroadcastApp root div.
     */
    public function test_review_and_preview_modals_nested_inside_alpine_scope()
    {
        $response = $this->get(route('sms.general-notice'));
        $response->assertStatus(200);

        $viewContent = $response->getContent();

        // 1. Check that noticeBroadcastApp root exists
        $this->assertStringContainsString('x-data="noticeBroadcastApp()"', $viewContent);

        // 2. Check that review and preview modals exist in view
        $this->assertStringContainsString('x-show="reviewModalOpen"', $viewContent);
        $this->assertStringContainsString('x-show="previewModalOpen"', $viewContent);

        // 3. Verify that the review modal appears BEFORE the final closing div of the page content
        $xDataPos = strpos($viewContent, 'x-data="noticeBroadcastApp()"');
        $reviewModalPos = strpos($viewContent, 'x-show="reviewModalOpen"');
        $previewModalPos = strpos($viewContent, 'x-show="previewModalOpen"');

        $this->assertGreaterThan($xDataPos, $previewModalPos);
        $this->assertGreaterThan($previewModalPos, $reviewModalPos);
    }

    /**
     * Test creating a valid future SMS schedule with snapshot integrity.
     */
    public function test_store_sms_schedule_creates_pending_record_with_snapshot_and_dhaka_timezone()
    {
        $this->actingAs($this->adminUser);

        $student1 = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01711111111']);
        $student2 = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01722222222']);

        $futureDate = Carbon::now('Asia/Dhaka')->addDays(2)->format('Y-m-d');
        $futureTime = '10:30';

        $response = $this->postJson(route('sms.general-notice.schedule.store'), [
            'recipient_source' => 'students',
            'session_year_id'  => $this->session1->id,
            'class_id'         => 'all',
            'section_id'       => 'all',
            'gender'           => 'all',
            'message'          => 'Scheduled meeting notice for parents.',
            'scheduled_date'   => $futureDate,
            'scheduled_time'   => $futureTime,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $schedule = SmsSchedule::where('user_id', $this->adminUser->id)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('pending', $schedule->status);
        $this->assertEquals(2, $schedule->total_recipients);
        $this->assertEquals(1, $schedule->total_segments);
        $this->assertEquals('GSM', $schedule->encoding);
        $this->assertEquals(0.66, (float)$schedule->estimated_cost);

        // Verify recipient snapshot integrity
        $snapshot = $schedule->recipient_snapshot;
        $this->assertIsArray($snapshot);
        $this->assertEquals(2, $snapshot['total_unique']);
        $this->assertArrayHasKey('8801711111111', $snapshot['students_map']);
        $this->assertArrayHasKey('8801722222222', $snapshot['students_map']);
    }

    /**
     * Test that creating a schedule with past date/time is rejected.
     */
    public function test_store_sms_schedule_rejects_past_time()
    {
        $this->actingAs($this->adminUser);

        $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01711111111']);

        // Yesterday
        $pastDate = Carbon::now('Asia/Dhaka')->subDay()->format('Y-m-d');
        $pastTime = '10:00';

        $response = $this->postJson(route('sms.general-notice.schedule.store'), [
            'recipient_source' => 'students',
            'session_year_id'  => $this->session1->id,
            'message'          => 'Past notice test.',
            'scheduled_date'   => $pastDate,
            'scheduled_time'   => $pastTime,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    /**
     * Test creating schedule with custom and both recipient sources.
     */
    public function test_store_sms_schedule_supports_custom_and_both_recipient_sources()
    {
        $this->actingAs($this->adminUser);

        $student = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01711111111']);

        $futureDate = Carbon::now('Asia/Dhaka')->addDays(3)->format('Y-m-d');
        $futureTime = '14:00';

        // 1. Both source: student (01711111111) + custom with overlap (01711111111, 01799999999)
        $responseBoth = $this->postJson(route('sms.general-notice.schedule.store'), [
            'recipient_source' => 'both',
            'session_year_id'  => $this->session1->id,
            'custom_numbers'   => '01711111111, 01799999999',
            'message'          => 'Both sources scheduled test.',
            'scheduled_date'   => $futureDate,
            'scheduled_time'   => $futureTime,
        ]);

        $responseBoth->assertStatus(200);
        $scheduleBoth = SmsSchedule::latest('id')->first();
        // 01711111111 (from student) + 01799999999 (custom standalone) = 2 unique recipients
        $this->assertEquals(2, $scheduleBoth->total_recipients);
        $this->assertCount(1, $scheduleBoth->recipient_snapshot['standalone_custom_phones']);
        $this->assertEquals('8801799999999', $scheduleBoth->recipient_snapshot['standalone_custom_phones'][0]);

        // 2. Custom source only
        $responseCustom = $this->postJson(route('sms.general-notice.schedule.store'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => '01811111111, 01822222222',
            'message'          => 'Custom numbers only scheduled test.',
            'scheduled_date'   => $futureDate,
            'scheduled_time'   => $futureTime,
        ]);

        $responseCustom->assertStatus(200);
        $scheduleCustom = SmsSchedule::latest('id')->first();
        $this->assertEquals(2, $scheduleCustom->total_recipients);
        $this->assertCount(2, $scheduleCustom->recipient_snapshot['standalone_custom_phones']);
    }

    /**
     * Test schedule management: list, edit pending, and cancel lifecycle.
     */
    public function test_schedule_management_list_edit_and_cancel_lifecycle()
    {
        $this->actingAs($this->adminUser);

        $futureDate = Carbon::now('Asia/Dhaka')->addDays(4)->format('Y-m-d');
        $futureTime = '11:00';

        $schedule = SmsSchedule::create([
            'schedule_uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'user_id'            => $this->adminUser->id,
            'title'              => 'Test Broadcast Original',
            'message'            => 'Original message content',
            'recipient_source'   => 'custom',
            'recipient_snapshot' => ['standalone_custom_phones' => ['8801711111111'], 'total_unique' => 1],
            'scheduled_at'       => Carbon::createFromFormat('Y-m-d H:i', "{$futureDate} {$futureTime}", 'Asia/Dhaka'),
            'status'             => 'pending',
            'total_recipients'   => 1,
            'total_segments'     => 1,
            'encoding'           => 'GSM',
            'estimated_cost'     => 0.33,
        ]);

        // 1. List schedules via JSON
        $listRes = $this->getJson(route('sms.general-notice.schedules'));
        $listRes->assertStatus(200);
        $listRes->assertJsonFragment(['title' => 'Test Broadcast Original']);
        $listRes->assertJsonStructure(['counts' => ['all', 'pending', 'completed', 'cancelled']]);

        // 2. Update schedule while pending
        $newFutureDate = Carbon::now('Asia/Dhaka')->addDays(5)->format('Y-m-d');
        $editRes = $this->putJson(route('sms.general-notice.schedules.update', $schedule->id), [
            'title'          => 'Test Broadcast Updated Title',
            'message'        => 'Updated message content with more details.',
            'scheduled_date' => $newFutureDate,
            'scheduled_time' => '16:00',
        ]);
        $editRes->assertStatus(200);
        $this->assertEquals('Test Broadcast Updated Title', $schedule->fresh()->title);
        $this->assertEquals('Updated message content with more details.', $schedule->fresh()->message);

        // 3. Cancel schedule
        $cancelRes = $this->postJson(route('sms.general-notice.schedules.cancel', $schedule->id));
        $cancelRes->assertStatus(200);
        $this->assertEquals('cancelled', $schedule->fresh()->status);
        $this->assertNotNull($schedule->fresh()->cancelled_at);
        $this->assertEquals($this->adminUser->id, $schedule->fresh()->cancelled_by);
    }

    /**
     * Test preventing edits and cancellations once a schedule is processing or completed.
     */
    public function test_cannot_edit_or_cancel_processing_or_completed_schedule()
    {
        $this->actingAs($this->adminUser);

        $futureDate = Carbon::now('Asia/Dhaka')->addDays(2)->format('Y-m-d');
        $schedule = SmsSchedule::create([
            'schedule_uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'user_id'            => $this->adminUser->id,
            'title'              => 'Locked Broadcast',
            'message'            => 'Message',
            'recipient_source'   => 'custom',
            'recipient_snapshot' => ['standalone_custom_phones' => ['8801711111111'], 'total_unique' => 1],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->addDays(2),
            'status'             => 'completed',
            'total_recipients'   => 1,
            'total_segments'     => 1,
            'encoding'           => 'GSM',
            'estimated_cost'     => 0.33,
        ]);

        // Try to edit completed schedule
        $editRes = $this->putJson(route('sms.general-notice.schedules.update', $schedule->id), [
            'message'        => 'New message',
            'scheduled_date' => $futureDate,
            'scheduled_time' => '12:00',
        ]);
        $editRes->assertStatus(422);

        // Try to cancel completed schedule
        $cancelRes = $this->postJson(route('sms.general-notice.schedules.cancel', $schedule->id));
        $cancelRes->assertStatus(422);
    }

    /**
     * Test the DispatchScheduledSms artisan command processes due pending schedules.
     */
    public function test_dispatch_scheduled_sms_command_processes_due_records()
    {
        $student = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01711111111']);

        // Schedule was due 5 minutes ago
        $dueSchedule = SmsSchedule::create([
            'schedule_uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'user_id'            => $this->adminUser->id,
            'title'              => 'Due Broadcast Test',
            'message'            => 'Due notice alert.',
            'recipient_source'   => 'students',
            'recipient_snapshot' => [
                'students_map'             => ['8801711111111' => [$student->id]],
                'standalone_custom_phones' => ['8801799999999'],
                'total_unique'             => 2,
            ],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->subMinutes(5),
            'status'             => 'pending',
            'total_recipients'   => 2,
            'total_segments'     => 1,
            'encoding'           => 'GSM',
            'estimated_cost'     => 0.66,
        ]);

        // Mock SmsService
        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->expects($this->exactly(2))
            ->method('sendSms')
            ->willReturn(true);

        $this->app->instance(SmsService::class, $mockSmsService);

        \Illuminate\Support\Facades\Artisan::call('sms:dispatch-scheduled', ['--id' => $dueSchedule->id]);

        $dueSchedule->refresh();
        $this->assertEquals('completed', $dueSchedule->status);
        $this->assertEquals(2, $dueSchedule->sent_count);
        $this->assertEquals(0, $dueSchedule->failed_count);
        $this->assertEquals(0.66, (float)$dueSchedule->actual_cost);
        $this->assertNotNull($dueSchedule->dispatched_at);
    }

    /**
     * Test dispatch command prevents duplicate execution if run again.
     */
    public function test_dispatch_scheduled_sms_prevents_duplicate_dispatch()
    {
        $dueSchedule = SmsSchedule::create([
            'schedule_uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'user_id'            => $this->adminUser->id,
            'title'              => 'Already Dispatched Broadcast',
            'message'            => 'Notice.',
            'recipient_source'   => 'custom',
            'recipient_snapshot' => ['standalone_custom_phones' => ['8801711111111'], 'total_unique' => 1],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->subMinutes(10),
            'status'             => 'completed',
            'total_recipients'   => 1,
            'total_segments'     => 1,
            'estimated_cost'     => 0.33,
        ]);

        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->expects($this->never())->method('sendSms');
        $this->app->instance(SmsService::class, $mockSmsService);

        \Illuminate\Support\Facades\Artisan::call('sms:dispatch-scheduled');

        $this->assertEquals('completed', $dueSchedule->fresh()->status);
    }

    /**
     * Test that visible per-SMS rate labels are completely removed from the page.
     */
    public function test_general_notice_page_does_not_display_visible_per_sms_rate_labels()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('sms.general-notice'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. "Rate: ৳0.33/sms" removed
        $this->assertStringNotContainsString('Rate: <span class="font-black text-gray-700 dark:text-gray-200">৳', $content);
        $this->assertStringNotContainsString('/sms</span>', $content);

        // 2. "configured rate of ৳... per segment" removed
        $this->assertStringNotContainsString('configured rate of ৳', $content);

        // 3. Total estimated cost, SMS rate, and balance displays are removed
        $this->assertStringNotContainsString('Est. Cost: ৳', $content);
        $this->assertStringNotContainsString('Estimated Broadcast Cost', $content);
        $this->assertStringNotContainsString('Current SMS Balance', $content);
        $this->assertStringContainsString('SMS Segments:', $content);
        $this->assertStringContainsString('Characters:', $content);

        // 4. Delivery options (Send Now vs Schedule for Later) are present
        $this->assertStringContainsString('SMS Delivery Options', $content);
        $this->assertStringContainsString('Send Now', $content);
        $this->assertStringContainsString('Schedule for Later', $content);
        $this->assertStringContainsString('Scheduled Broadcasts', $content);
    }

    /**
     * Test crash recovery: partially sent schedule only dispatches remaining unsent recipients without duplicating.
     */
    public function test_crash_recovery_does_not_duplicate_already_sent_recipients()
    {
        $student1 = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01711111111']);
        $student2 = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01722222222']);

        $dueSchedule = SmsSchedule::create([
            'schedule_uuid'      => 'crash_uuid_123',
            'user_id'            => $this->adminUser->id,
            'title'              => 'Crash Recovery Test',
            'message'            => 'Notice.',
            'recipient_source'   => 'students',
            'recipient_snapshot' => [
                'students_map'             => [
                    '8801711111111' => [$student1->id],
                    '8801722222222' => [$student2->id],
                ],
                'standalone_custom_phones' => [],
                'total_unique'             => 2,
            ],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->subMinutes(20),
            'status'             => 'processing', // Stalled in processing due to worker crash
            'dispatched_at'      => Carbon::now('Asia/Dhaka')->subMinutes(20),
            'total_recipients'   => 2,
            'total_segments'     => 1,
            'estimated_cost'     => 0.66,
        ]);

        // Recipient 1 was already dispatched before crash
        SmsLog::create([
            'student_id'    => $student1->id,
            'event_key'     => 'sch_crash_uuid_123_sstd_' . $student1->id,
            'event_type'    => 'scheduled_notice',
            'mobile_number' => '8801711111111',
            'message'       => 'Notice.',
            'status'        => 'Sent',
            'created_at'    => Carbon::now('Asia/Dhaka')->subMinutes(20),
            'updated_at'    => Carbon::now('Asia/Dhaka')->subMinutes(20),
        ]);

        // Gateway should ONLY be called for student2!
        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->expects($this->once())
            ->method('sendSms')
            ->with('8801722222222', 'Notice.', $student2->id, 'sch_crash_uuid_123_sstd_' . $student2->id, 'scheduled_notice')
            ->willReturn(true);

        $this->app->instance(SmsService::class, $mockSmsService);

        \Illuminate\Support\Facades\Artisan::call('sms:dispatch-scheduled');

        $dueSchedule->refresh();
        $this->assertEquals('completed', $dueSchedule->status);
        $this->assertEquals(2, $dueSchedule->sent_count);
    }

    /**
     * Test Scheduled SMS with mixed Sent and Blocked (Test Mode) recipients.
     * Verifies that Blocked (Test Mode) does not increment sent_count and is not billed.
     */
    public function test_dispatch_scheduled_sms_mixed_sent_and_blocked_test_mode()
    {
        $student = $this->makeStudent(['session_year_id' => $this->session1->id, 'guardian_mobile' => '01711111111']);

        $dueSchedule = SmsSchedule::create([
            'schedule_uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'user_id'            => $this->adminUser->id,
            'title'              => 'Mixed Sent and Blocked Test',
            'message'            => 'Notice.',
            'recipient_source'   => 'both',
            'recipient_snapshot' => [
                'students_map'             => ['8801711111111' => [$student->id]],
                'standalone_custom_phones' => ['8801722222222'],
                'total_unique'             => 2,
            ],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->subMinutes(5),
            'status'             => 'pending',
            'total_recipients'   => 2,
            'total_segments'     => 1,
            'encoding'           => 'GSM',
            'estimated_cost'     => 0.66,
        ]);

        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->expects($this->exactly(2))
            ->method('sendSms')
            ->willReturnCallback(function ($phone, $message, $studentId, $eventKey, $eventType) {
                if ($phone === '8801711111111') {
                    SmsLog::create([
                        'student_id'    => $studentId,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $phone,
                        'message'       => $message,
                        'status'        => 'Sent',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                    return true;
                } else {
                    SmsLog::create([
                        'student_id'    => null,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $phone,
                        'message'       => $message,
                        'status'        => 'Blocked (Test Mode)',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                    return false;
                }
            });

        $this->app->instance(SmsService::class, $mockSmsService);

        \Illuminate\Support\Facades\Artisan::call('sms:dispatch-scheduled', ['--id' => $dueSchedule->id]);

        $dueSchedule->refresh();
        $this->assertEquals('completed', $dueSchedule->status);
        $this->assertEquals(1, $dueSchedule->sent_count, 'Only the whitelisted recipient must increment sent_count');
        $this->assertEquals(1, $dueSchedule->blocked_count, 'Blocked recipient must be recorded in blocked_count');
        $this->assertEquals(0, $dueSchedule->failed_count);
        $this->assertEquals(0.33, (float)$dueSchedule->actual_cost, 'Only the sent recipient must be billed (0.33, not 0.66)');
        $this->assertEquals(1, $dueSchedule->recipient_snapshot['dispatch_summary']['blocked_count']);
    }

    /**
     * Test Scheduled SMS where all recipients are Blocked (Test Mode).
     * Verifies sent_count is 0, cost is 0.00, and blocked_count is 2.
     */
    public function test_dispatch_scheduled_sms_all_blocked_in_test_mode()
    {
        $dueSchedule = SmsSchedule::create([
            'schedule_uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'user_id'            => $this->adminUser->id,
            'title'              => 'All Blocked Test',
            'message'            => 'Notice.',
            'recipient_source'   => 'custom',
            'recipient_snapshot' => [
                'standalone_custom_phones' => ['8801711111111', '8801722222222'],
                'total_unique'             => 2,
            ],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->subMinutes(5),
            'status'             => 'pending',
            'total_recipients'   => 2,
            'total_segments'     => 1,
            'estimated_cost'     => 0.66,
        ]);

        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->expects($this->exactly(2))
            ->method('sendSms')
            ->willReturnCallback(function ($phone, $message, $studentId, $eventKey, $eventType) {
                SmsLog::create([
                    'student_id'    => null,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $phone,
                    'message'       => $message,
                    'status'        => 'Blocked (Test Mode)',
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
                return false;
            });

        $this->app->instance(SmsService::class, $mockSmsService);

        \Illuminate\Support\Facades\Artisan::call('sms:dispatch-scheduled', ['--id' => $dueSchedule->id]);

        $dueSchedule->refresh();
        $this->assertEquals('completed', $dueSchedule->status);
        $this->assertEquals(0, $dueSchedule->sent_count);
        $this->assertEquals(2, $dueSchedule->blocked_count);
        $this->assertEquals(0, $dueSchedule->failed_count);
        $this->assertEquals(0.00, (float)$dueSchedule->actual_cost);
    }

    /**
     * Test Scheduled SMS with a genuine gateway failure.
     * Verifies partial_failure status, failed_count is 1, and sent_count is 1.
     */
    public function test_dispatch_scheduled_sms_genuine_gateway_failure()
    {
        $dueSchedule = SmsSchedule::create([
            'schedule_uuid'      => (string) \Illuminate\Support\Str::uuid(),
            'user_id'            => $this->adminUser->id,
            'title'              => 'Gateway Failure Test',
            'message'            => 'Notice.',
            'recipient_source'   => 'custom',
            'recipient_snapshot' => [
                'standalone_custom_phones' => ['8801711111111', '8801722222222'],
                'total_unique'             => 2,
            ],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->subMinutes(5),
            'status'             => 'pending',
            'total_recipients'   => 2,
            'total_segments'     => 1,
            'estimated_cost'     => 0.66,
        ]);

        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->expects($this->exactly(2))
            ->method('sendSms')
            ->willReturnCallback(function ($phone, $message, $studentId, $eventKey, $eventType) {
                if ($phone === '8801711111111') {
                    SmsLog::create([
                        'student_id'    => null,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $phone,
                        'message'       => $message,
                        'status'        => 'Sent',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                    return true;
                } else {
                    SmsLog::create([
                        'student_id'    => null,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $phone,
                        'message'       => $message,
                        'status'        => 'Failed',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                    return false;
                }
            });

        $this->app->instance(SmsService::class, $mockSmsService);

        \Illuminate\Support\Facades\Artisan::call('sms:dispatch-scheduled', ['--id' => $dueSchedule->id]);

        $dueSchedule->refresh();
        $this->assertEquals('partial_failure', $dueSchedule->status);
        $this->assertEquals(1, $dueSchedule->sent_count);
        $this->assertEquals(0, $dueSchedule->blocked_count);
        $this->assertEquals(1, $dueSchedule->failed_count);
        $this->assertEquals(0.33, (float)$dueSchedule->actual_cost);
    }

    /**
     * Test crash recovery with pre-existing Sent and Blocked (Test Mode) logs.
     * Verifies that neither Sent nor Blocked counts inflate, and unsent recipients are processed.
     */
    public function test_dispatch_scheduled_sms_crash_recovery_with_mixed_sent_and_blocked()
    {
        $dueSchedule = SmsSchedule::create([
            'schedule_uuid'      => 'recovery_uuid_456',
            'user_id'            => $this->adminUser->id,
            'title'              => 'Crash Recovery Mixed',
            'message'            => 'Notice.',
            'recipient_source'   => 'custom',
            'recipient_snapshot' => [
                'standalone_custom_phones' => ['8801711111111', '8801722222222', '8801733333333'],
                'total_unique'             => 3,
            ],
            'scheduled_at'       => Carbon::now('Asia/Dhaka')->subMinutes(15),
            'status'             => 'processing',
            'dispatched_at'      => Carbon::now('Asia/Dhaka')->subMinutes(15),
            'total_recipients'   => 3,
            'total_segments'     => 1,
            'estimated_cost'     => 0.99,
        ]);

        // Recipient 1 was already Sent
        SmsLog::create([
            'student_id'    => null,
            'event_key'     => 'sch_recovery_uuid_456_scust_8801711111111',
            'event_type'    => 'scheduled_notice',
            'mobile_number' => '8801711111111',
            'message'       => 'Notice.',
            'status'        => 'Sent',
            'created_at'    => Carbon::now('Asia/Dhaka')->subMinutes(15),
            'updated_at'    => Carbon::now('Asia/Dhaka')->subMinutes(15),
        ]);

        // Recipient 2 was already Blocked (Test Mode)
        SmsLog::create([
            'student_id'    => null,
            'event_key'     => 'sch_recovery_uuid_456_scust_8801722222222',
            'event_type'    => 'scheduled_notice',
            'mobile_number' => '8801722222222',
            'message'       => 'Notice.',
            'status'        => 'Blocked (Test Mode)',
            'created_at'    => Carbon::now('Asia/Dhaka')->subMinutes(15),
            'updated_at'    => Carbon::now('Asia/Dhaka')->subMinutes(15),
        ]);

        // Recipient 3 is unsent. sendSms should ONLY be called once for Recipient 3!
        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->expects($this->once())
            ->method('sendSms')
            ->with('8801733333333', 'Notice.', null, 'sch_recovery_uuid_456_scust_8801733333333', 'scheduled_notice')
            ->willReturnCallback(function ($phone, $message, $studentId, $eventKey, $eventType) {
                SmsLog::create([
                    'student_id'    => null,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $phone,
                    'message'       => $message,
                    'status'        => 'Sent',
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
                return true;
            });

        $this->app->instance(SmsService::class, $mockSmsService);

        \Illuminate\Support\Facades\Artisan::call('sms:dispatch-scheduled', ['--id' => $dueSchedule->id]);

        $dueSchedule->refresh();
        $this->assertEquals('completed', $dueSchedule->status);
        $this->assertEquals(2, $dueSchedule->sent_count, 'Recipient 1 + Recipient 3 = 2 sent');
        $this->assertEquals(1, $dueSchedule->blocked_count, 'Recipient 2 = 1 blocked');
        $this->assertEquals(0, $dueSchedule->failed_count);
        $this->assertEquals(0.66, (float)$dueSchedule->actual_cost, 'Only 2 sent messages billed (2 * 0.33 = 0.66)');
    }

    /**
     * Test immediate normal SMS broadcast with mixed Sent and Blocked (Test Mode) recipients.
     * Verifies that stats.sent_count is 1, stats.blocked_count is 1, and response message indicates blocked count.
     */
    public function test_send_general_notice_immediate_broadcast_mixed_sent_and_blocked()
    {
        $this->actingAs($this->adminUser);

        $mockSmsService = $this->createMock(SmsService::class);
        $mockSmsService->method('getBalance')->willReturn(['balance' => 100.0, 'status' => 'success']);
        $mockSmsService->expects($this->exactly(2))
            ->method('sendSms')
            ->willReturnCallback(function ($phone, $message, $studentId, $eventKey, $eventType) {
                if ($phone === '8801711111111') {
                    SmsLog::create([
                        'student_id'    => null,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $phone,
                        'message'       => $message,
                        'status'        => 'Sent',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                    return true;
                } else {
                    SmsLog::create([
                        'student_id'    => null,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $phone,
                        'message'       => $message,
                        'status'        => 'Blocked (Test Mode)',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                    return false;
                }
            });

        $this->app->instance(SmsService::class, $mockSmsService);

        $response = $this->postJson(route('sms.general-notice.send'), [
            'recipient_source' => 'custom',
            'custom_numbers'   => '01711111111, 01722222222',
            'message'          => 'Instant alert.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'stats'   => [
                'sent_count'    => 1,
                'blocked_count' => 1,
                'failed_count'  => 0,
            ],
        ]);
        $this->assertStringContainsString('1 recipient(s) blocked by SMS Test Mode', $response->json('message'));
    }
}

