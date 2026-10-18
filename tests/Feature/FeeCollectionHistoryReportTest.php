<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeSetup;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeeCollectionHistoryReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected Classes $classTwo;
    protected Classes $classThree;
    protected Section $section;
    protected SessionYear $session;
    protected FeeCategory $feeCategory;
    protected FeeSetup $feeSetup;
    protected Student $studentTwo;
    protected Student $studentThree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_report@macsschool.com'],
            ['name' => 'Report Admin', 'password' => bcrypt('password')]
        );
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->adminUser->assignRole($adminRole);

        $this->branch = Branch::firstOrCreate(['id' => 1], ['branch_name' => 'Main Campus', 'user_id' => $this->adminUser->id]);
        $this->session = SessionYear::firstOrCreate(['id' => 1], ['session_name' => '2026', 'user_id' => $this->adminUser->id]);

        $this->classTwo = Classes::firstOrCreate(['id' => 1], ['class_name' => 'Two', 'user_id' => $this->adminUser->id]);
        $this->classThree = Classes::firstOrCreate(['id' => 2], ['class_name' => 'Three', 'user_id' => $this->adminUser->id]);

        $this->section = Section::firstOrCreate(
            ['id' => 1],
            ['section_name' => 'A', 'class_id' => $this->classTwo->id, 'session_year_id' => $this->session->id, 'user_id' => $this->adminUser->id]
        );

        $this->feeCategory = FeeCategory::firstOrCreate(['id' => 1], ['name' => 'Tuition Fee', 'user_id' => $this->adminUser->id]);

        $this->feeSetup = FeeSetup::create([
            'session_year_id' => $this->session->id,
            'branch_id' => $this->branch->id,
            'class_id' => $this->classTwo->id,
            'fee_category_id' => $this->feeCategory->id,
            'amount' => 1000.00,
            'fee_month' => 'October',
            'user_id' => $this->adminUser->id,
        ]);

        $this->studentTwo = Student::create([
            'user_id' => $this->adminUser->id,
            'student_name' => 'Rahim Two',
            'student_identity' => 'MACS-2026-0002',
            'roll_number' => '05',
            'dob' => '2015-05-10',
            'gender' => 'Male',
            'class_id' => $this->classTwo->id,
            'section_id' => $this->section->id,
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session->id,
            'father_name' => 'Father Two',
            'father_mobile' => '01711000002',
            'mother_name' => 'Mother Two',
            'mother_mobile' => '01711000022',
            'guardian_name' => 'Guardian Two',
            'guardian_mobile' => '01711000032',
            'status' => 'Active',
            'sms_status' => 'Active',
            'present_village' => 'V',
            'present_post_office' => 'P',
            'present_district' => 'D',
            'present_division' => 'Div',
            'permanent_village' => 'V',
            'permanent_post_office' => 'P',
            'permanent_district' => 'D',
            'permanent_division' => 'Div',
        ]);

        $this->studentThree = Student::create([
            'user_id' => $this->adminUser->id,
            'student_name' => 'Karim Three',
            'student_identity' => 'MACS-2026-0003',
            'roll_number' => '12',
            'dob' => '2014-06-15',
            'gender' => 'Male',
            'class_id' => $this->classThree->id,
            'section_id' => $this->section->id,
            'branch_id' => $this->branch->id,
            'session_year_id' => $this->session->id,
            'father_name' => 'Father Three',
            'father_mobile' => '01711000003',
            'mother_name' => 'Mother Three',
            'mother_mobile' => '01711000023',
            'guardian_name' => 'Guardian Three',
            'guardian_mobile' => '01711000033',
            'status' => 'Active',
            'sms_status' => 'Active',
            'present_village' => 'V',
            'present_post_office' => 'P',
            'present_district' => 'D',
            'present_division' => 'Div',
            'permanent_village' => 'V',
            'permanent_post_office' => 'P',
            'permanent_district' => 'D',
            'permanent_division' => 'Div',
        ]);
    }

    protected function createInvoice(array $attributes): FeeInvoice
    {
        return FeeInvoice::create(array_merge([
            'user_id' => $this->adminUser->id,
            'fee_setup_id' => $this->feeSetup->id,
            'amount' => 1000.00,
            'discount' => 0.00,
            'net_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0.00,
            'status' => 'Paid',
            'due_date' => '2026-10-01',
            'created_at' => '2026-10-01 00:00:00',
        ], $attributes));
    }

    public function test_fee_collection_report_specific_class_removes_redundant_class_name_from_row(): void
    {
        $invoice = $this->createInvoice([
            'invoice_no' => 'INV-202610-0002',
            'student_id' => $this->studentTwo->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-20261015-1002',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentTwo->id,
            'paid_amount' => 1000.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
            'created_at' => '2026-10-15 14:30:00',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('fees.reports.index', [
            'class_id' => $this->classTwo->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        // Header contains Class: Two
        $response->assertSee('Class: <strong style="color: #008ED6;">Two</strong>', false);
        // Student Info contains student name, ID and roll
        $response->assertSee('Rahim Two');
        $response->assertSee('MACS-2026-0002');
        $response->assertSee('Roll: 05');
        // Does NOT repeat the class name in the student meta row (no '>Two</span>,')
        $response->assertDontSee('>Two</span>,', false);
    }

    public function test_fee_collection_report_all_classes_retains_class_name_in_student_row(): void
    {
        $invoiceTwo = $this->createInvoice([
            'invoice_no' => 'INV-202610-0002',
            'student_id' => $this->studentTwo->id,
        ]);

        FeePayment::create([
            'receipt_no' => 'REC-20261015-1002',
            'fee_invoice_id' => $invoiceTwo->id,
            'student_id' => $this->studentTwo->id,
            'paid_amount' => 1000.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
            'created_at' => '2026-10-15 14:30:00',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('fees.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        // Header contains Class: All Classes
        $response->assertSee('Class: <strong style="color: #008ED6;">All Classes</strong>', false);
        // Student Info row contains class name to differentiate students
        $response->assertSee('MACS-2026-0002');
        $response->assertSee('>Two</span>,', false);
        $response->assertSee('Roll: 05');
    }

    public function test_fee_collection_report_displays_actual_payment_datetime_not_invoice_creation(): void
    {
        // Invoice created on 01 Oct 2026 at 00:00:00
        $invoice = $this->createInvoice([
            'invoice_no' => 'INV-202610-0099',
            'student_id' => $this->studentTwo->id,
            'created_at' => '2026-10-01 00:00:00',
        ]);

        // Payment collected on 18 Oct 2026 at 15:45:10
        $payment = FeePayment::create([
            'receipt_no' => 'REC-20261018-9999',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentTwo->id,
            'paid_amount' => 1000.00,
            'payment_date' => '2026-10-18',
            'payment_method' => 'Cash',
            'collected_by' => $this->adminUser->id,
        ]);
        DB::table('fee_payments')->where('id', $payment->id)->update([
            'created_at' => '2026-10-18 15:45:10',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('fees.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        // Sourced from actual payment timestamp: 18 Oct 2026, 03:45 PM
        $response->assertSee('18 Oct 2026, 03:45 PM');
        // Must NOT display the invoice creation date 01 Oct 2026, 12:00 AM
        $response->assertDontSee('01 Oct 2026, 12:00 AM');
    }

    public function test_fee_collection_report_without_payment_time_displays_clean_date_without_fake_time(): void
    {
        $invoice = $this->createInvoice([
            'invoice_no' => 'INV-202610-0088',
            'student_id' => $this->studentTwo->id,
            'amount' => 500.00,
            'net_amount' => 500.00,
            'paid_amount' => 500.00,
        ]);

        // Older payment record with payment_date only, created_at is null
        $payment = new FeePayment();
        $payment->receipt_no = 'REC-20261020-8888';
        $payment->fee_invoice_id = $invoice->id;
        $payment->student_id = $this->studentTwo->id;
        $payment->paid_amount = 500.00;
        $payment->payment_date = '2026-10-20';
        $payment->payment_method = 'Cash';
        $payment->collected_by = $this->adminUser->id;
        $payment->timestamps = false;
        $payment->created_at = null;
        $payment->updated_at = null;
        $payment->save();

        $response = $this->actingAs($this->adminUser)->get(route('fees.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        // Clean date formatted without fake 12:00 AM
        $response->assertSee('20 Oct 2026');
        $response->assertDontSee('20 Oct 2026, 12:00 AM');
    }

    public function test_fee_collection_report_missing_dates_displays_date_unavailable_fallback(): void
    {
        $this->actingAs($this->adminUser);

        $col = (object) [
            'receipt_no' => 'REC-TEST-NULL',
            'collection_date_formatted' => 'Date unavailable',
            'items_count' => 1,
            'student' => $this->studentTwo,
            'categories_summary' => 'Tuition Fee',
            'months' => ['October'],
            'category_months' => [],
            'payment_method' => 'Cash',
            'paid_amount' => 500.00,
        ];
        $html = view('pages.fees.reports', [
            'payments' => collect(),
            'collections' => collect([$col]),
            'dues' => collect(),
            'defaulters' => collect(),
            'totalCollected' => 500.00,
            'totalDue' => 0.00,
            'totalCollectedFormatted' => '500.00',
            'totalDueFormatted' => '0.00',
            'totalCollectionCount' => 1,
            'totalPaymentRecordsCount' => 1,
            'uniquePayingStudentsCount' => 1,
            'totalDueInvoicesCount' => 0,
            'uniqueDefaulterStudentsCount' => 0,
            'methodBreakdown' => collect(),
            'dueMonthBreakdown' => collect(),
            'startDate' => '2026-10-01',
            'endDate' => '2026-10-31',
            'classId' => null,
            'branchId' => null,
            'branches' => collect([$this->branch]),
            'classes' => collect([$this->classTwo, $this->classThree]),
            'collectionExportData' => collect(),
            'defaultersExportData' => collect(),
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('Date unavailable', $html);
    }

    public function test_fee_collection_report_csv_export_contains_authoritative_date(): void
    {
        $invoice = $this->createInvoice([
            'invoice_no' => 'INV-202610-0066',
            'student_id' => $this->studentTwo->id,
            'amount' => 1200.00,
            'net_amount' => 1200.00,
            'paid_amount' => 1200.00,
        ]);

        $payment = FeePayment::create([
            'receipt_no' => 'REC-20261025-6666',
            'fee_invoice_id' => $invoice->id,
            'student_id' => $this->studentTwo->id,
            'paid_amount' => 1200.00,
            'payment_date' => '2026-10-25',
            'payment_method' => 'bKash',
            'collected_by' => $this->adminUser->id,
        ]);
        DB::table('fee_payments')->where('id', $payment->id)->update([
            'created_at' => '2026-10-25 11:20:00',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('fees.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertSee('25 Oct 2026, 11:20 AM');
        $response->assertSee('REC-20261025-6666');
    }
}
