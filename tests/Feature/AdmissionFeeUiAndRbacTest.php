<?php

namespace Tests\Feature;

use App\Models\AdmissionFeeStructure;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionFeeReportService;
use App\Services\AdmissionFeeStructureService;
use App\Services\AdmissionInvoiceService;
use App\Services\FeeReceiptService;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdmissionFeeUiAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminUser;
    protected User $editorUser;
    protected User $teacherUser;
    protected User $studentUser;

    protected Branch $branchMain;
    protected SessionYear $session2026;
    protected Classes $clsTen;
    protected Classes $clsPlay;
    protected Section $secPlay;

    protected FeeCategory $admissionCat;
    protected FeeCategory $tuitionCat;
    protected AdmissionFeeStructure $playStructure;

    protected AdmissionFeeStructureService $structureService;
    protected AdmissionInvoiceService $invoiceService;
    protected AdmissionCollectionService $collectionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->structureService = app(AdmissionFeeStructureService::class);
        $this->invoiceService = app(AdmissionInvoiceService::class);
        $this->collectionService = app(AdmissionCollectionService::class);

        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $editorRole = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        $teacherRole = Role::firstOrCreate(['slug' => 'teacher'], ['name' => 'Teacher']);
        $studentRole = Role::firstOrCreate(['slug' => 'student'], ['name' => 'Student']);

        $this->superAdmin = User::create(['name' => 'UI Super', 'email' => 'ui_super@macs.test', 'password' => bcrypt('secret')]);
        $this->superAdmin->assignRole($superRole);

        $this->adminUser = User::create(['name' => 'UI Admin', 'email' => 'ui_admin@macs.test', 'password' => bcrypt('secret')]);
        $this->adminUser->assignRole($adminRole);

        $this->editorUser = User::create(['name' => 'UI Editor', 'email' => 'ui_editor@macs.test', 'password' => bcrypt('secret')]);
        $this->editorUser->assignRole($editorRole);

        $this->teacherUser = User::create(['name' => 'UI Teacher', 'email' => 'ui_teacher@macs.test', 'password' => bcrypt('secret')]);
        $this->teacherUser->assignRole($teacherRole);

        $this->studentUser = User::create(['name' => 'UI Student', 'email' => 'ui_student@macs.test', 'password' => bcrypt('secret')]);
        $this->studentUser->assignRole($studentRole);

        $this->branchMain = Branch::create(['branch_name' => 'Main Campus', 'user_id' => $this->superAdmin->id]);
        $this->session2026 = SessionYear::create(['session_name' => '2026', 'user_id' => $this->superAdmin->id]);

        // Create Ten before Play to verify global Play..Ten class ordering in filter UI
        $this->clsTen = Classes::create(['class_name' => 'Ten', 'user_id' => $this->superAdmin->id]);
        $this->clsPlay = Classes::create(['class_name' => 'Play', 'user_id' => $this->superAdmin->id]);
        $this->secPlay = Section::create(['section_name' => 'A', 'class_id' => $this->clsPlay->id, 'session_year_id' => $this->session2026->id, 'user_id' => $this->superAdmin->id]);

        $this->admissionCat = FeeCategory::create(['name' => 'Admission Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $this->tuitionCat = FeeCategory::create(['name' => 'Monthly Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);

        $this->playStructure = $this->structureService->createStructure([
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsPlay->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Play Admission 2026',
            'status' => 'Active',
            'total_amount' => '2500.00',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 1,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Registration',
                    'amount' => '1500.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->tuitionCat->id,
                    'head_name' => 'January Tuition Fee',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => true,
                    'sort_order' => 20,
                ],
            ],
        ], $this->superAdmin->id);
    }

    protected function createStudent(string $identity): Student
    {
        return Student::create([
            'user_id' => $this->superAdmin->id,
            'student_identity' => $identity,
            'student_name' => 'Student ' . $identity,
            'roll_number' => substr($identity, -2),
            'dob' => '2019-02-01',
            'gender' => 'Male',
            'father_name' => 'Father ' . $identity,
            'father_mobile' => '01711111111',
            'mother_name' => 'Mother ' . $identity,
            'mother_mobile' => '01722222222',
            'guardian_name' => 'Guardian ' . $identity,
            'guardian_mobile' => '01733333333',
            'branch_id' => $this->branchMain->id,
            'session_year_id' => $this->session2026->id,
            'class_id' => $this->clsPlay->id,
            'section_id' => $this->secPlay->id,
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

    public function test_rbac_blocks_unauthorized_roles_on_all_4d_ui_data_and_print_routes(): void
    {
        $routes = [
            route('fees.admission.reports.index'),
            route('fees.admission.reports.data'),
            route('fees.admission.reports.print'),
            route('fees.admission.reports.export_excel'),
        ];

        foreach ($routes as $url) {
            $guestRes = $this->get($url);
            $this->assertContains($guestRes->getStatusCode(), [302, 401, 403]);
        }

        foreach ([$this->editorUser, $this->teacherUser, $this->studentUser] as $blocked) {
            foreach ($routes as $url) {
                $res = $this->actingAs($blocked)->get($url);
                $this->assertContains($res->getStatusCode(), [302, 403]);

                $jsonRes = $this->actingAs($blocked)->getJson($url);
                $this->assertContains($jsonRes->getStatusCode(), [302, 403]);
            }
        }

        foreach ([$this->adminUser, $this->superAdmin] as $allowed) {
            foreach ($routes as $url) {
                $this->actingAs($allowed)->get($url)->assertOk();
            }
        }
    }

    public function test_admission_reports_ui_renders_tall_stack_dashboard_and_preserves_4c_json_api(): void
    {
        $student = $this->createStudent('STD-4D-UI-01');
        $inv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $this->collectionService->collectSinglePayment($inv->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ], $this->superAdmin->id);

        // 1. Browser HTML Console renders without inline <style> blocks
        $htmlRes = $this->actingAs($this->adminUser)->get(route('fees.admission.reports.index'));
        $htmlRes->assertOk();
        $htmlRes->assertViewIs('pages.fees.admission_reports');
        $htmlRes->assertSee('Admission Fee Reports');
        $htmlRes->assertSee('Branch-wise');
        $htmlRes->assertSee('Class-wise');
        $htmlRes->assertSee('Student-wise');
        $htmlRes->assertSee('Fee Head-wise');
        $htmlRes->assertSee('Export Excel');
        $htmlRes->assertSee('Print Report');
        $htmlRes->assertSee('Option A Date Split');
        $htmlRes->assertSee('Financial Reconciliation Verified');
        $bladeRaw = (string) file_get_contents(resource_path('views/pages/fees/admission_reports.blade.php'));
        $printRaw = (string) file_get_contents(resource_path('views/pages/fees/admission_reports_print.blade.php'));
        $this->assertStringNotContainsString('<style', strtolower($bladeRaw));
        // Allow only the currency sign '৳' (U+09F3); forbid any Bengali prose/letters in UI templates
        $this->assertDoesNotMatchRegularExpression('/[\x{0980}-\x{09FF}]/u', str_replace('৳', '', $bladeRaw));
        $this->assertDoesNotMatchRegularExpression('/[\x{0980}-\x{09FF}]/u', str_replace('৳', '', $printRaw));
        $htmlRes->assertDontSee('Select Branch');
        $htmlRes->assertDontSee('Select Class');
        $htmlRes->assertDontSee('Select Session');

        // Verify global Play..Ten class ordering in view payload and rendered JS bootstrap
        $this->assertSame(['Play', 'Ten'], $htmlRes->viewData('classes')->pluck('class_name')->all());
        $content = $htmlRes->getContent();
        $classesSliceStart = strpos($content, 'classes:');
        $this->assertNotFalse($classesSliceStart);
        $classesSlice = substr($content, (int) $classesSliceStart, 250);
        $posPlay = strpos($classesSlice, 'Play');
        $posTen = strpos($classesSlice, 'Ten');
        $this->assertNotFalse($posPlay);
        $this->assertNotFalse($posTen);
        $this->assertLessThan($posTen, $posPlay);

        // 2. 4C JSON API backward compatibility on fees.admission.reports.index
        $jsonLegacyRes = $this->actingAs($this->adminUser)->getJson(route('fees.admission.reports.index', ['mode' => 'class']));
        $jsonLegacyRes->assertOk();
        $jsonLegacyRes->assertJsonPath('mode', 'class');
        $jsonLegacyRes->assertJsonPath('summary.net_receivable', '2500.00');
        $jsonLegacyRes->assertJsonPath('summary.collected', '1500.00');
        $jsonLegacyRes->assertJsonPath('summary.due', '1000.00');
        $jsonLegacyRes->assertJsonPath('reconciliation.is_reconciled', true);

        // 3. Dedicated 4D AJAX data endpoint matches identically
        $dataRes = $this->actingAs($this->adminUser)->getJson(route('fees.admission.reports.data', ['mode' => 'class']));
        $dataRes->assertOk();
        $this->assertSame($jsonLegacyRes->json('summary'), $dataRes->json('summary'));
        $this->assertSame($jsonLegacyRes->json('rows'), $dataRes->json('rows'));
    }

    public function test_standalone_a4_print_view_renders_all_four_modes_and_isolated_cancelled_audit(): void
    {
        $activeStd = $this->createStudent('STD-4D-PRT-1');
        $activeInv = $this->invoiceService->generateForStudent($activeStd, [], $this->superAdmin->id)['invoice'];
        $this->collectionService->collectSinglePayment($activeInv->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ], $this->superAdmin->id);

        $cancelledStd = $this->createStudent('STD-4D-PRT-2');
        $cancelledInv = $this->invoiceService->generateForStudent($cancelledStd, [], $this->superAdmin->id)['invoice'];
        $cancelledInv->update(['status' => 'Cancelled']);

        foreach (['branch', 'class', 'student', 'head'] as $mode) {
            $res = $this->actingAs($this->adminUser)->get(route('fees.admission.reports.print', ['mode' => $mode]));
            $res->assertOk();
            $res->assertViewIs('pages.fees.admission_reports_print');
            $res->assertSee('Admission Fee Financial Audit');
            $res->assertSee('size: A4 landscape');
            $res->assertSee('Grand Total');
            $res->assertSee('Cancelled Admission Invoices Audit (Excluded From Active Totals)');
            $res->assertSee($cancelledInv->invoice_no);
            $res->assertSee('Prepared By');
            $res->assertSee('Accountant / Cashier');
            $res->assertSee('Internal Auditor');
            $res->assertSee('Principal / Head of Institute');
        }
    }

    public function test_sidebar_shows_admission_reports_for_admin_and_super_admin_and_hides_from_editor(): void
    {
        $reportUrl = route('fees.admission.reports.index');
        $structuresUrl = route('fees.admission.structures.index');

        foreach ([$this->adminUser, $this->superAdmin] as $allowed) {
            $res = $this->actingAs($allowed)->get(route('fees.admission.reports.index'));
            $res->assertOk();
            $html = $res->getContent();
            $this->assertGreaterThanOrEqual(2, substr_count($html, $reportUrl));
            $this->assertGreaterThanOrEqual(2, substr_count($html, 'Admission Reports'));
            $this->assertGreaterThanOrEqual(2, substr_count($html, $structuresUrl));
            $this->assertGreaterThanOrEqual(2, substr_count($html, 'Admission Structures'));
        }

        $editorRes = $this->actingAs($this->editorUser)->get(route('student.admission'));
        $editorRes->assertOk();
        $editorRes->assertDontSee($reportUrl, false);
        $editorRes->assertDontSee('Admission Reports');
        $editorRes->assertDontSee($structuresUrl, false);
        $editorRes->assertDontSee('Admission Structures');
    }

    public function test_pre_migration_guard_blocks_schema_queries_and_shows_exact_pending_warning(): void
    {
        $fakeReceiptService = $this->createMock(FeeReceiptService::class);
        $fakeReceiptService->method('admissionTablesAvailable')->willReturn(false);
        $this->app->instance(FeeReceiptService::class, $fakeReceiptService);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $warning = 'Admission schema migrations are pending. Please contact the system administrator.';

        $htmlRes = $this->actingAs($this->adminUser)->get(route('fees.admission.reports.index'));
        $htmlRes->assertOk();
        $htmlRes->assertSee($warning);
        $htmlRes->assertSee('No admission report records available while schema migrations are pending.');

        $printRes = $this->actingAs($this->adminUser)->get(route('fees.admission.reports.print'));
        $printRes->assertOk();
        $printRes->assertSee($warning);

        $jsonRes = $this->actingAs($this->adminUser)->getJson(route('fees.admission.reports.data'));
        $jsonRes->assertOk();
        $jsonRes->assertJsonPath('tables_available', false);
        $jsonRes->assertJsonPath('schema_warning', $warning);
        $jsonRes->assertJsonPath('rows', []);
        $jsonRes->assertJsonPath('summary.net_receivable', '0.00');

        $excelRes = $this->actingAs($this->adminUser)->get(route('fees.admission.reports.export_excel'));
        $excelRes->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        foreach ($queries as $q) {
            $sql = strtolower($q['query']);
            $this->assertStringNotContainsString('admission_fee_structures', $sql);
            $this->assertStringNotContainsString('admission_fee_structure_items', $sql);
            $this->assertStringNotContainsString('admission_invoice_head_allocations', $sql);
            $this->assertStringNotContainsString('admission_payment_head_allocations', $sql);
            $this->assertStringNotContainsString('admission_fee_structure_id', $sql);
        }
    }

    public function test_collection_and_payments_views_render_admission_invoices_without_fee_setup_null_crash(): void
    {
        $student = $this->createStudent('STD-4D-NS-1');
        $admInv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];

        $collectionRes = $this->actingAs($this->adminUser)->get(route('fees.collection.index', [
            'search' => $student->student_identity,
        ]));
        $collectionRes->assertOk();
        $collectionRes->assertSee('Admission Fee Package — Play Admission 2026');
        $collectionRes->assertSee($admInv->invoice_no);
        $collectionRes->assertSee("pay-btn-{$admInv->id}", false);
        $collectionRes->assertDontSee("adjust-btn-{$admInv->id}", false);

        $payment = $this->collectionService->collectSinglePayment($admInv->id, [
            'pay_amount' => '1500.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
        ], $this->superAdmin->id)['payment'];

        $paymentsRes = $this->actingAs($this->adminUser)->get(route('fees.payments.index', [
            'search_query' => $payment->receipt_no,
        ]));
        $paymentsRes->assertOk();
        $paymentsRes->assertSee($payment->receipt_no);
        $paymentsRes->assertSee('Admission Fee Package — Play Admission 2026');
        $paymentsRes->assertSee('One Time');
    }

    public function test_4e_structure_management_crud_versioning_activation_deletion_and_rbac(): void
    {
        // RBAC blocks editor, teacher, student
        foreach ([$this->editorUser, $this->teacherUser, $this->studentUser] as $blocked) {
            $htmlBlocked = $this->actingAs($blocked)->get(route('fees.admission.structures.index'));
            $this->assertContains($htmlBlocked->getStatusCode(), [302, 403]);
            $this->assertContains($this->actingAs($blocked)->postJson(route('fees.admission.structures.store'), [])->getStatusCode(), [302, 403]);
            $this->assertContains($this->actingAs($blocked)->putJson(route('fees.admission.structures.update', $this->playStructure->id), [])->getStatusCode(), [302, 403]);
            $this->assertContains($this->actingAs($blocked)->postJson(route('fees.admission.structures.activate', $this->playStructure->id))->getStatusCode(), [302, 403]);
            $this->assertContains($this->actingAs($blocked)->postJson(route('fees.admission.structures.deactivate', $this->playStructure->id))->getStatusCode(), [302, 403]);
            $this->assertContains($this->actingAs($blocked)->deleteJson(route('fees.admission.structures.destroy', $this->playStructure->id))->getStatusCode(), [302, 403]);
        }

        // Admin views HTML UI with TALL & Play..Ten class order compliance
        $htmlRes = $this->actingAs($this->adminUser)->get(route('fees.admission.structures.index'));
        $htmlRes->assertOk();
        $htmlRes->assertViewIs('pages.fees.admission_structures_index');
        $htmlRes->assertSee('Admission Fee Structures');
        $this->assertSame(['Play', 'Ten'], $htmlRes->viewData('classes')->pluck('class_name')->all());

        $structureBlade = (string) file_get_contents(resource_path('views/pages/fees/admission_structures_index.blade.php'));
        $this->assertStringNotContainsString('<style', strtolower($structureBlade));
        $this->assertDoesNotMatchRegularExpression('/[\x{0980}-\x{09FF}]/u', str_replace('৳', '', $structureBlade));
        $this->assertDoesNotMatchRegularExpression('/\b(?:window\.)?(?:alert|confirm)\s*\(/', $structureBlade);

        // Create Draft structure in Ten scope
        $createRes = $this->actingAs($this->adminUser)->postJson(route('fees.admission.structures.store'), [
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsTen->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Ten Admission Package',
            'status' => 'Inactive',
            'total_amount' => '3200.00',
            'includes_monthly_tuition' => false,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Ten Registration Fee',
                    'amount' => '2000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Ten Lab Charge',
                    'amount' => '1200.00',
                    'is_discountable' => false,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
            ],
        ]);
        $createRes->assertCreated()->assertJsonPath('structure.version', 1)->assertJsonPath('structure.status', 'Inactive');
        $draftV1Id = (int) $createRes->json('structure.id');

        // Versioned edit produces immutable v2 while preserving v1
        $updateRes = $this->actingAs($this->adminUser)->putJson(route('fees.admission.structures.update', $draftV1Id), [
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsTen->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Ten Admission Package Revised',
            'status' => 'Inactive',
            'total_amount' => '3500.00',
            'includes_monthly_tuition' => false,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Ten Registration Fee',
                    'amount' => '2300.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Ten Lab Charge',
                    'amount' => '1200.00',
                    'is_discountable' => false,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
            ],
        ]);
        $updateRes->assertOk()->assertJsonPath('structure.version', 2);
        $v2Id = (int) $updateRes->json('structure.id');

        // Activate v2 then deactivate it back
        $this->actingAs($this->adminUser)
            ->postJson(route('fees.admission.structures.activate', $v2Id))
            ->assertOk()
            ->assertJsonPath('structure.status', 'Active');

        $this->actingAs($this->adminUser)
            ->postJson(route('fees.admission.structures.deactivate', $v2Id), ['archive' => false])
            ->assertOk()
            ->assertJsonPath('structure.status', 'Inactive');

        // Unused draft v1 can be deleted; active playStructure cannot
        $this->actingAs($this->adminUser)
            ->deleteJson(route('fees.admission.structures.destroy', $draftV1Id))
            ->assertOk();
        $this->assertDatabaseMissing('admission_fee_structures', ['id' => $draftV1Id]);

        $this->actingAs($this->adminUser)
            ->deleteJson(route('fees.admission.structures.destroy', $this->playStructure->id))
            ->assertStatus(422);
    }

    public function test_4e_student_admission_form_package_preview_and_admin_only_discount_preview(): void
    {
        $scopeQuery = [
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsPlay->id,
            'session_year_id' => $this->session2026->id,
        ];

        // Editor, Admin, Super Admin can read active package preview
        foreach ([$this->editorUser, $this->adminUser, $this->superAdmin] as $allowed) {
            $this->actingAs($allowed)
                ->getJson(route('ajax.admission.structures.active', $scopeQuery))
                ->assertOk()
                ->assertJsonPath('tables_available', true)
                ->assertJsonPath('structure.id', $this->playStructure->id)
                ->assertJsonPath('structure.total_amount', '2500.00');
        }

        // Blocked roles cannot read active structure AJAX endpoint
        foreach ([$this->teacherUser, $this->studentUser] as $blocked) {
            $res = $this->actingAs($blocked)->getJson(route('ajax.admission.structures.active', $scopeQuery));
            $this->assertContains($res->getStatusCode(), [302, 403]);
        }

        // Editor sees Admission Package Preview panel but NOT Admin Discount Panel
        $editorAdmHtml = $this->actingAs($this->editorUser)->get(route('student.admission'));
        $editorAdmHtml->assertOk();
        $editorAdmHtml->assertSee('Admission Fee Package Preview');
        $editorAdmHtml->assertDontSee('admission_discount_type');
        $editorAdmHtml->assertDontSee('Apply Admission Discount');

        $discBlockedRes = $this->actingAs($this->editorUser)->postJson(route('fees.admission.structures.discount_preview'), [
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsPlay->id,
            'session_year_id' => $this->session2026->id,
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ]);
        $this->assertContains($discBlockedRes->getStatusCode(), [302, 403]);

        // Admin sees Discount Panel and gets exact BCMath discount preview
        $adminAdmHtml = $this->actingAs($this->adminUser)->get(route('student.admission'));
        $adminAdmHtml->assertOk();
        $adminAdmHtml->assertSee('Admission Fee Package Preview');
        $adminAdmHtml->assertSee('admission_discount_type');

        $this->actingAs($this->adminUser)->postJson(route('fees.admission.structures.discount_preview'), [
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsPlay->id,
            'session_year_id' => $this->session2026->id,
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('preview.total_discount', '500.00')
            ->assertJsonPath('preview.total_net', '2000.00');
    }

    public function test_4e_collection_modal_and_payments_status_filter_with_reversal_idempotency(): void
    {
        $student = $this->createStudent('STD-4E-COL-01');
        $admInv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];
        $admInv->load('admissionHeadAllocations');
        $heads = $admInv->admissionHeadAllocations->sortBy('sort_order')->values();

        // Collection view shows Collect Heads button & Admission Payment Modal (zero Blade service calls)
        $colRes = $this->actingAs($this->adminUser)->get(route('fees.collection.index', [
            'search' => $student->student_identity,
        ]));
        $colRes->assertOk();
        $colRes->assertSee('Collect Heads');
        $colRes->assertSee('admissionPaymentModal');
        $colRes->assertSee('Collect Admission Fee');

        // 1. Per-Head collection via JSON endpoint
        $head1 = $heads[0];
        $perHeadRes = $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $admInv->id,
            'head_allocations' => [
                $head1->id => '500.00',
            ],
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'idempotency_key' => 'col-4e-head-1',
        ]);
        $perHeadRes->assertOk()->assertJsonPath('success', true);
        $payment1Id = (int) $perHeadRes->json('payment.id');
        $receipt1No = (string) $perHeadRes->json('receipt_no');

        // 2. Lump-Sum auto split via JSON endpoint
        $lumpRes = $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $admInv->id,
            'pay_amount' => '1000.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-18',
            'idempotency_key' => 'col-4e-lump-1',
        ]);
        $lumpRes->assertOk()->assertJsonPath('success', true);
        $receipt2No = (string) $lumpRes->json('receipt_no');

        // 3. Reverse payment #1 with persistent DB idempotency key (Condition 2)
        $revKey = 'rev-4e-idemp-key-001';
        $revRes = $this->actingAs($this->adminUser)->postJson(route('fees.payments.reverse', $payment1Id), [
            'reason' => 'Wrong head entry during training',
            'idempotency_key' => $revKey,
        ]);
        $revRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_admission_invoice', true);
        $revRef = (string) $revRes->json('reversal_reference');
        $this->assertNotEmpty($revRef);

        $this->assertDatabaseHas('fee_idempotency_keys', [
            'operation' => 'reverse_payment',
            'actor_user_id' => $this->adminUser->id,
            'target_scope' => (string) $payment1Id,
            'idempotency_key' => $revKey,
        ]);

        // Duplicate reversal with same idempotency key must reject cleanly (422)
        $dupRevRes = $this->actingAs($this->adminUser)->postJson(route('fees.payments.reverse', $payment1Id), [
            'reason' => 'Duplicate retry submit',
            'idempotency_key' => $revKey,
        ]);
        $dupRevRes->assertStatus(422);

        // 4. Collection summary Total Paid excludes reversed payment #1 (500.00) and shows only active payment #2 (1000.00)
        $postRevColHtml = $this->actingAs($this->adminUser)->get(route('fees.collection.index', [
            'mode' => 'single',
            'student_identity' => $student->student_identity,
        ]));
        $postRevColHtml->assertOk();
        $this->assertMatchesRegularExpression('/Total Paid:\s*<\/span>\s*<span[^>]*>\s*৳\s*1,000\.00\s*<\/span>/u', (string) $postRevColHtml->getContent());

        // 5. Payment History UI & Status Filter (Active vs Reversed) + Editor lacks Reverse modal
        $activeList = $this->actingAs($this->adminUser)->get(route('fees.payments.index', ['status' => 'Active']));
        $activeList->assertOk();
        $activeList->assertSee($receipt2No);
        $activeList->assertDontSee($receipt1No);
        $activeList->assertSee('reversePaymentModal');

        $reversedList = $this->actingAs($this->adminUser)->get(route('fees.payments.index', ['status' => 'Reversed']));
        $reversedList->assertOk();
        $reversedList->assertSee($receipt1No);
        $reversedList->assertSee($revRef);
        $reversedList->assertDontSee($receipt2No);

        $editorPayments = $this->actingAs($this->editorUser)->get(route('fees.payments.index'));
        $this->assertContains($editorPayments->getStatusCode(), [302, 403]);
    }

    public function test_4e_student_profile_ledger_and_manual_admission_invoice_regeneration_and_rbac(): void
    {
        $student = $this->createStudent('STD-4E-PRF-01');
        $admInv = $this->invoiceService->generateForStudent($student, [], $this->superAdmin->id)['invoice'];

        // Editor sees student profile view without Admin Generate/Regenerate modal and is blocked on endpoint
        $editorViewHtml = $this->actingAs($this->editorUser)->get(route('student.view', $student->id));
        $editorViewHtml->assertOk();
        $editorViewHtml->assertDontSee('generateAdmissionInvoiceModal');

        $editorGenRes = $this->actingAs($this->editorUser)
            ->postJson(route('fees.invoice.admission.generate', $student->id));
        $this->assertContains($editorGenRes->getStatusCode(), [302, 403]);

        // Admin AJAX profile includes enriched admission heads & print link
        $ajaxRes = $this->actingAs($this->adminUser)->getJson("/ajax/students/{$student->id}");
        $ajaxRes->assertOk()
            ->assertJsonPath('admission_schema_ready', true)
            ->assertJsonPath('can_manage_admission_invoices', true)
            ->assertJsonPath('has_active_admission_invoice', true)
            ->assertJsonPath('invoices.0.is_admission_invoice', true)
            ->assertJsonPath('invoices.0.pos_print_url', route('fees.invoice.pos_print', $admInv->id));
        $this->assertCount(2, $ajaxRes->json('invoices.0.prepared_heads'));

        // Active invoice blocks duplicate generation
        $this->actingAs($this->adminUser)
            ->postJson(route('fees.invoice.admission.generate', $student->id))
            ->assertStatus(422);

        // Cancel existing invoice -> Admin regenerates -V2 invoice
        $admInv->update(['status' => 'Cancelled']);

        $regenRes = $this->actingAs($this->adminUser)->postJson(route('fees.invoice.admission.generate', $student->id), [
            'discount_type' => 'fixed',
            'discount_value' => '200.00',
            'due_date' => '2026-12-01',
        ]);
        $regenRes->assertCreated()
            ->assertJsonPath('success', true);
        $this->assertStringEndsWith('-V2', (string) $regenRes->json('invoice.invoice_no'));
    }

    public function test_4e_pre_migration_guard_zero_queries_and_blade_separation_across_all_views(): void
    {
        $student = $this->createStudent('STD-4E-GRD-01');
        $warning = 'Admission schema migrations are pending. Please contact the system administrator.';

        Schema::partialMock()
            ->shouldReceive('hasTable')
            ->andReturnUsing(static fn (string $table): bool => !str_starts_with($table, 'admission_'));

        Schema::partialMock()
            ->shouldReceive('hasColumn')
            ->andReturnUsing(static function (string $table, string $column): bool {
                if (
                    ($table === 'fee_invoices' && $column === 'admission_fee_structure_id')
                    || ($table === 'fee_payments' && $column === 'status')
                ) {
                    return false;
                }

                return true;
            });

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->adminUser)
            ->get(route('fees.admission.structures.index'))
            ->assertOk()
            ->assertSee($warning);

        $this->actingAs($this->adminUser)
            ->getJson(route('fees.admission.structures.index'))
            ->assertOk()
            ->assertJsonPath('tables_available', false)
            ->assertJsonPath('schema_warning', $warning)
            ->assertJsonPath('structures', []);

        $this->actingAs($this->editorUser)
            ->getJson(route('ajax.admission.structures.active', [
                'branch_id' => $this->branchMain->id,
                'class_id' => $this->clsPlay->id,
                'session_year_id' => $this->session2026->id,
            ]))
            ->assertOk()
            ->assertJsonPath('tables_available', false)
            ->assertJsonPath('schema_warning', $warning)
            ->assertJsonPath('structure', null);

        $this->actingAs($this->adminUser)
            ->postJson(route('fees.admission.structures.discount_preview'), [
                'branch_id' => $this->branchMain->id,
                'class_id' => $this->clsPlay->id,
                'session_year_id' => $this->session2026->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('tables_available', false)
            ->assertJsonPath('message', $warning);

        $this->actingAs($this->adminUser)
            ->postJson(route('fees.invoice.admission.generate', $student->id))
            ->assertStatus(422)
            ->assertJsonPath('tables_available', false)
            ->assertJsonPath('message', $warning);

        $this->actingAs($this->adminUser)
            ->get(route('fees.payments.index'))
            ->assertOk()
            ->assertSee($warning);

        $this->actingAs($this->adminUser)
            ->get(route('fees.collection.index', ['search' => $student->student_identity]))
            ->assertOk()
            ->assertSee($warning);

        $this->actingAs($this->adminUser)
            ->getJson("/ajax/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('admission_schema_ready', false)
            ->assertJsonPath('admission_schema_warning', $warning);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        foreach ($queries as $q) {
            $sql = strtolower($q['query']);
            $this->assertStringNotContainsString('admission_fee_structures', $sql);
            $this->assertStringNotContainsString('admission_fee_structure_items', $sql);
            $this->assertStringNotContainsString('admission_invoice_head_allocations', $sql);
            $this->assertStringNotContainsString('admission_payment_head_allocations', $sql);
            $this->assertStringNotContainsString('admission_fee_structure_id', $sql);
        }

        // Condition 3: Blade Separation across all 4E UI templates (zero direct service calls in Blade)
        $bladePaths = [
            'pages/fees/admission_structures_index.blade.php',
            'pages/students/admission.blade.php',
            'pages/fees/collection.blade.php',
            'pages/fees/payments.blade.php',
            'pages/students/view.blade.php',
        ];
        foreach ($bladePaths as $relPath) {
            $raw = (string) file_get_contents(resource_path('views/' . $relPath));
            $this->assertStringNotContainsString('FeeReceiptService', $raw, "Blade template {$relPath} must not call FeeReceiptService directly.");
        }
    }

    public function test_class_wise_head_matrix_ui_data_api_and_print_modes(): void
    {
        $stuPlay = $this->createStudent('STD-MX-UI-01');
        $invPlay = $this->invoiceService->generateForStudent($stuPlay, [
            'discount_type' => 'fixed',
            'discount_value' => '500.00',
        ], $this->superAdmin->id)['invoice'];
        $heads = $invPlay->admissionHeadAllocations()->orderBy('sort_order')->get();

        $this->collectionService->collectSinglePayment($invPlay->id, [
            'pay_amount' => '1200.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-18',
            'head_allocations' => [
                $heads[0]->id => '700.00',
                $heads[1]->id => '500.00',
            ],
        ], $this->adminUser->id);

        $this->actingAs($this->adminUser);

        $htmlRes = $this->get(route('fees.admission.reports.index', ['mode' => 'class']));
        $htmlRes->assertOk()
            ->assertSee('Head Matrix')
            ->assertSee('Summary')
            ->assertSee('Net Receivable')
            ->assertSee('Collected')
            ->assertSee('Admission Registration')
            ->assertSee('January Tuition Fee');
        $rawHtml = (string) preg_replace('/<!--.*?-->/s', '', (string) $htmlRes->getContent());
        $this->assertDoesNotMatchRegularExpression('/[\x{0980}-\x{09FF}]/u', str_replace('৳', '', $rawHtml));

        $dataRes = $this->getJson(route('fees.admission.reports.data', [
            'mode' => 'class',
            'class_view' => 'matrix',
            'matrix_metric' => 'collected',
        ]));
        $dataRes->assertOk()
            ->assertJsonPath('mode', 'class')
            ->assertJsonPath('class_matrix.metric', 'collected')
            ->assertJsonPath('class_matrix.metric_label', 'Collected (Cumul.)')
            ->assertJsonPath('class_matrix.is_reconciled', true);

        $rows = $dataRes->json('class_matrix.rows');
        $this->assertGreaterThanOrEqual(2, count($rows));
        $classNames = array_column($rows, 'class_name');
        $this->assertLessThan(
            array_search('Ten', $classNames, true),
            array_search('Play', $classNames, true)
        );
        $playRow = collect($rows)->firstWhere('class_name', 'Play');
        $this->assertEquals('1200.00', $playRow['collected']);

        $printMatrixRes = $this->get(route('fees.admission.reports.print', [
            'mode' => 'class',
            'class_view' => 'matrix',
            'matrix_metric' => 'collected',
        ]));
        $printMatrixRes->assertOk()
            ->assertSee('class="w-full matrix-table', false)
            ->assertSee('Class-wise Fee Head Matrix')
            ->assertSee('Collected (Cumul.)')
            ->assertSee('Admission Registration')
            ->assertSee('January Tuition Fee')
            ->assertSee('700.00')
            ->assertSee('500.00')
            ->assertSee('Play')
            ->assertSee('Ten')
            ->assertSee('Grand Total')
            ->assertDontSee('>Rate<', false);

        $printSummaryRes = $this->get(route('fees.admission.reports.print', [
            'mode' => 'class',
            'class_view' => 'summary',
        ]));
        $printSummaryRes->assertOk()
            ->assertSee('class="w-full report-table', false)
            ->assertDontSee('class="w-full matrix-table', false);
    }

    public function test_class_matrix_pre_migration_guard_returns_static_empty_matrix_with_zero_admission_queries(): void
    {
        Schema::partialMock()
            ->shouldReceive('hasTable')
            ->andReturnUsing(static fn (string $table): bool => !str_starts_with($table, 'admission_'));

        Schema::partialMock()
            ->shouldReceive('hasColumn')
            ->andReturnUsing(static function (string $table, string $column): bool {
                if (
                    ($table === 'fee_invoices' && $column === 'admission_fee_structure_id')
                    || ($table === 'fee_payments' && $column === 'status')
                ) {
                    return false;
                }

                return true;
            });

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->adminUser)
            ->getJson(route('fees.admission.reports.data', [
                'mode' => 'class',
                'matrix_metric' => 'collected',
            ]))
            ->assertOk()
            ->assertJsonPath('tables_available', false)
            ->assertJsonPath('class_matrix.metric', 'collected')
            ->assertJsonPath('class_matrix.metric_label', 'Collected (Cumul.)')
            ->assertJsonPath('class_matrix.head_columns', [])
            ->assertJsonPath('class_matrix.rows', [])
            ->assertJsonPath('class_matrix.column_totals', [])
            ->assertJsonPath('class_matrix.is_reconciled', true);

        $this->actingAs($this->adminUser)
            ->get(route('fees.admission.reports.index', ['mode' => 'class']))
            ->assertOk();

        $this->actingAs($this->adminUser)
            ->get(route('fees.admission.reports.print', ['mode' => 'class']))
            ->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        foreach ($queries as $q) {
            $sql = strtolower($q['query']);
            $this->assertStringNotContainsString('admission_fee_structures', $sql);
            $this->assertStringNotContainsString('admission_fee_structure_items', $sql);
            $this->assertStringNotContainsString('admission_invoice_head_allocations', $sql);
            $this->assertStringNotContainsString('admission_payment_head_allocations', $sql);
        }
    }

    public function test_collection_modal_auto_split_default_and_direct_discount_invariants(): void
    {
        $sessionCat = FeeCategory::create(['name' => 'Session Fee', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $secTen = Section::create([
            'section_name' => 'A',
            'class_id' => $this->clsTen->id,
            'session_year_id' => $this->session2026->id,
            'user_id' => $this->superAdmin->id,
        ]);

        // Mixed package on Class Ten: 1000.00 discountable Admission Fee + 1000.00 non-discountable Session Fee
        $this->structureService->createStructure([
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsTen->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Ten Mixed Direct Discount Pack',
            'status' => 'Active',
            'total_amount' => '2000.00',
            'includes_monthly_tuition' => false,
            'tuition_month_number' => null,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Fee',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $sessionCat->id,
                    'head_name' => 'Session Fee',
                    'amount' => '1000.00',
                    'is_discountable' => false,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
            ],
        ], $this->superAdmin->id);

        $stu1 = Student::create([
            'user_id' => $this->superAdmin->id,
            'student_identity' => 'STD-MX-DISC-01',
            'student_name' => 'Student Disc One',
            'roll_number' => '01',
            'dob' => '2012-01-01',
            'gender' => 'Male',
            'father_name' => 'Father',
            'father_mobile' => '01711111111',
            'mother_name' => 'Mother',
            'mother_mobile' => '01722222222',
            'guardian_name' => 'Guardian',
            'guardian_mobile' => '01733333333',
            'branch_id' => $this->branchMain->id,
            'session_year_id' => $this->session2026->id,
            'class_id' => $this->clsTen->id,
            'section_id' => $secTen->id,
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
        $inv1 = $this->invoiceService->generateForStudent($stu1, [], $this->superAdmin->id)['invoice'];

        // 1. UI verifies Auto Split is first & default, and Direct Discount controls appear for Admin
        $colPage = $this->actingAs($this->adminUser)->get(route('fees.collection.index', [
            'student_identity' => $stu1->student_identity,
        ]));
        $colPage->assertOk()
            ->assertSee('Auto Split')
            ->assertSee('Per-Head Allocation')
            ->assertSee("allocationMode: 'lump_sum'", false)
            ->assertSee('Direct Discount (৳)')
            ->assertSee('Discount Only (৳0 Pay)')
            ->assertSee('is_discountable');

        $html = (string) $colPage->getContent();
        $this->assertLessThan(strpos($html, 'Per-Head Allocation'), strpos($html, 'Auto Split'));

        // 2. Editor blocked on HTTP & direct service layer
        $editorHttpRes = $this->actingAs($this->editorUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv1->id,
            'direct_discount_amount' => '100.00',
            'pay_amount' => '0.00',
        ]);
        $this->assertContains($editorHttpRes->getStatusCode(), [302, 403]);
        $this->assertThrows(
            fn () => $this->collectionService->collectSinglePayment($inv1->id, [
                'direct_discount_amount' => '100.00',
                'pay_amount' => '0.00',
            ], $this->editorUser->id),
            \Illuminate\Auth\Access\AuthorizationException::class
        );

        // 3. Rejects direct discount exceeding eligible discountable due (1200 > 1000 even though invoice due is 2000)
        $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv1->id,
            'direct_discount_amount' => '1200.00',
            'pay_amount' => '800.00',
            'payment_method' => 'Cash',
        ])->assertStatus(422);

        // 4. Combined Direct Discount (300.00) + Auto Split Cash Payment (1700.00) -> Paid cleanly
        $combinedRes = $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv1->id,
            'direct_discount_amount' => '300.00',
            'discount_reason' => 'Principal special waiver',
            'pay_amount' => '1700.00',
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-19',
            'idempotency_key' => 'comb-disc-pay-1',
        ]);
        $combinedRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('discount_applied', '300.00')
            ->assertJsonPath('paid_amount', '1700.00');
        $this->assertNotEmpty($combinedRes->json('receipt_no'));

        $inv1->refresh()->load('admissionHeadAllocations');
        $this->assertEquals('2000.00', bcadd((string) $inv1->amount, '0.00', 2));
        $this->assertEquals('300.00', bcadd((string) $inv1->discount, '0.00', 2));
        $this->assertEquals('1700.00', bcadd((string) $inv1->net_amount, '0.00', 2));
        $this->assertEquals('1700.00', bcadd((string) $inv1->paid_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $inv1->due_amount, '0.00', 2));
        $this->assertEquals('Paid', $inv1->status);

        $inv1Heads = $inv1->admissionHeadAllocations->keyBy('head_name_snapshot');
        $this->assertEquals('300.00', bcadd((string) $inv1Heads['Admission Fee']->discount, '0.00', 2));
        $this->assertEquals('700.00', bcadd((string) $inv1Heads['Admission Fee']->net_amount, '0.00', 2));
        $this->assertEquals('700.00', bcadd((string) $inv1Heads['Admission Fee']->allocated_paid_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $inv1Heads['Admission Fee']->due_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $inv1Heads['Session Fee']->discount, '0.00', 2));
        $this->assertEquals('1000.00', bcadd((string) $inv1Heads['Session Fee']->allocated_paid_amount, '0.00', 2));

        $this->assertDatabaseHas('fee_adjustment_logs', [
            'fee_invoice_id' => $inv1->id,
            'adjustment_amount' => '300.00',
            'previous_discount' => '0.00',
            'new_discount' => '300.00',
            'reason' => 'Principal special waiver',
        ]);

        // 5. Discount-Only (400.00 discount, 0.00 cash) creates audit log and ZERO receipt, followed by payment + reversal
        $stu2 = Student::create(array_merge($stu1->only([
            'user_id', 'dob', 'gender', 'father_name', 'father_mobile', 'mother_name', 'mother_mobile',
            'guardian_name', 'guardian_mobile', 'branch_id', 'session_year_id', 'class_id', 'section_id',
            'status', 'sms_status', 'present_village', 'present_post_office', 'present_district', 'present_division',
            'permanent_village', 'permanent_post_office', 'permanent_district', 'permanent_division',
        ]), [
            'student_identity' => 'STD-MX-DISC-02',
            'student_name' => 'Student Disc Two',
            'roll_number' => '02',
        ]));
        $inv2 = $this->invoiceService->generateForStudent($stu2, [], $this->superAdmin->id)['invoice'];

        $discOnlyRes = $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv2->id,
            'direct_discount_amount' => '400.00',
            'discount_reason' => 'Merit admission discount',
            'pay_amount' => '0.00',
            'idempotency_key' => 'disc-only-1',
        ]);
        $discOnlyRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('discount_applied', '400.00')
            ->assertJsonPath('paid_amount', '0.00')
            ->assertJsonPath('receipt_no', null);

        $inv2->refresh();
        $this->assertEquals(0, \App\Models\FeePayment::where('fee_invoice_id', $inv2->id)->count());
        $this->assertEquals('400.00', bcadd((string) $inv2->discount, '0.00', 2));
        $this->assertEquals('1600.00', bcadd((string) $inv2->net_amount, '0.00', 2));
        $this->assertEquals('1600.00', bcadd((string) $inv2->due_amount, '0.00', 2));
        $this->assertEquals('Unpaid', $inv2->status);

        // Pay full post-discount due then reverse cleanly
        $payAfterRes = $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv2->id,
            'pay_amount' => '1600.00',
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-19',
        ]);
        $payAfterRes->assertOk();
        $pay2Id = (int) $payAfterRes->json('payment.id');
        $this->actingAs($this->adminUser)->postJson(route('fees.payments.reverse', $pay2Id), [
            'reason' => 'Test reversal after direct discount',
        ])->assertOk();
        $inv2->refresh();
        $this->assertEquals('1600.00', bcadd((string) $inv2->due_amount, '0.00', 2));
        $this->assertEquals('400.00', bcadd((string) $inv2->discount, '0.00', 2));

        // 6. Zero-discountable package rejects direct discount cleanly (422)
        $branchNoDisc = Branch::create(['branch_name' => 'Zero Disc Campus', 'user_id' => $this->superAdmin->id]);
        $this->structureService->createStructure([
            'branch_id' => $branchNoDisc->id,
            'class_id' => $this->clsTen->id,
            'session_year_id' => $this->session2026->id,
            'structure_name' => 'Non-Discountable Pack',
            'status' => 'Active',
            'total_amount' => '1000.00',
            'includes_monthly_tuition' => false,
            'tuition_month_number' => null,
            'items' => [
                [
                    'fee_category_id' => $sessionCat->id,
                    'head_name' => 'Session Fee',
                    'amount' => '1000.00',
                    'is_discountable' => false,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
            ],
        ], $this->superAdmin->id);
        $stu3 = Student::create(array_merge($stu1->only([
            'user_id', 'dob', 'gender', 'father_name', 'father_mobile', 'mother_name', 'mother_mobile',
            'guardian_name', 'guardian_mobile', 'session_year_id', 'class_id', 'section_id',
            'status', 'sms_status', 'present_village', 'present_post_office', 'present_district', 'present_division',
            'permanent_village', 'permanent_post_office', 'permanent_district', 'permanent_division',
        ]), [
            'branch_id' => $branchNoDisc->id,
            'student_identity' => 'STD-MX-NODISC-03',
            'student_name' => 'Student No Disc',
            'roll_number' => '03',
        ]));
        $inv3 = $this->invoiceService->generateForStudent($stu3, [], $this->superAdmin->id)['invoice'];
        $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv3->id,
            'direct_discount_amount' => '100.00',
            'pay_amount' => '900.00',
            'payment_method' => 'Cash',
        ])->assertStatus(422);

        // 7. Financial report reconciles post direct discount cleanly
        $report = app(AdmissionFeeReportService::class)->generateReport(['mode' => 'class']);
        $this->assertTrue($report['reconciliation']['is_reconciled']);
        $this->assertTrue($report['class_matrix']['is_reconciled']);
    }

    public function test_all_time_structures_quick_view_modal_filter_and_report_matrix_integration(): void
    {
        $session2027 = SessionYear::create(['session_name' => '2027', 'user_id' => $this->superAdmin->id]);

        // 1. Create active All-Time structure via controller endpoint (session_year_id = null)
        $createRes = $this->actingAs($this->adminUser)->postJson(route('fees.admission.structures.store'), [
            'branch_id' => $this->branchMain->id,
            'class_id' => $this->clsTen->id,
            'session_year_id' => null,
            'structure_name' => 'Class Ten Admission Fee Structure',
            'status' => 'Active',
            'total_amount' => '3400.00',
            'includes_monthly_tuition' => false,
            'items' => [
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Ten Special Lab Fee',
                    'amount' => '3400.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 10,
                ],
            ],
        ]);
        $createRes->assertCreated()
            ->assertJsonPath('structure.session_year_id', null)
            ->assertJsonPath('structure.session_name', 'All Sessions')
            ->assertJsonPath('structure.is_all_time', true);
        $allTimeId = (int) $createRes->json('structure.id');

        // 2. Verify UI HTML uses Rule 9 borderless table, nowrap fixes, Quick View modal, and zero inline toggleExpand
        $htmlRes = $this->actingAs($this->adminUser)->get(route('fees.admission.structures.index'));
        $htmlRes->assertOk();
        $htmlRes->assertSee('id="quickViewModal"', false);
        $htmlRes->assertSee('openQuickViewModal', false);
        $htmlRes->assertSee('whitespace-nowrap', false);
        $htmlRes->assertSee('[&>thead>tr]:!bg-transparent', false);
        $htmlRes->assertSee('All-Time (Applies Every Year)');
        $htmlRes->assertDontSee('toggleExpand');
        $htmlRes->assertDontSee('expandedId');

        $structureBlade = (string) file_get_contents(resource_path('views/pages/fees/admission_structures_index.blade.php'));
        $this->assertStringNotContainsString('<style', strtolower($structureBlade));
        $this->assertDoesNotMatchRegularExpression('/[\x{0980}-\x{09FF}]/u', str_replace('৳', '', $structureBlade));
        $this->assertDoesNotMatchRegularExpression('/\b(?:window\.)?(?:alert|confirm)\s*\(/', $structureBlade);

        // 3. Filter JSON endpoint with session_year_id=all_time returns only NULL-session structures
        $filterRes = $this->actingAs($this->adminUser)->getJson(route('fees.admission.structures.index', [
            'session_year_id' => 'all_time',
        ]));
        $filterRes->assertOk()->assertJsonCount(1, 'structures')->assertJsonPath('structures.0.id', $allTimeId);

        // 4. Student admission package preview automatically resolves All-Time package for both 2026 and future session 2027
        foreach ([$this->session2026->id, $session2027->id] as $targetSessionId) {
            $this->actingAs($this->editorUser)
                ->getJson(route('ajax.admission.structures.active', [
                    'branch_id' => $this->branchMain->id,
                    'class_id' => $this->clsTen->id,
                    'session_year_id' => $targetSessionId,
                ]))
                ->assertOk()
                ->assertJsonPath('structure.id', $allTimeId)
                ->assertJsonPath('structure.is_all_time', true)
                ->assertJsonPath('structure.session_name', 'All Sessions');
        }

        // 5. Report matrix still seeds active All-Time structure fee heads even when filtered by future session 2027
        $report = app(AdmissionFeeReportService::class)->generateReport([
            'mode' => 'class',
            'session_year_id' => $session2027->id,
        ]);
        $headNames = collect($report['class_matrix']['head_columns'])->pluck('head_name')->all();
        $this->assertContains('Ten Special Lab Fee', $headNames);
    }

    public function test_auto_split_tuition_toggle_excludes_and_includes_monthly_tuition_cleanly(): void
    {
        $branchTuit = Branch::create(['branch_name' => 'Tuition Split Campus', 'user_id' => $this->superAdmin->id]);
        $tuitionCat = FeeCategory::create(['name' => 'Monthly Tuition', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);
        $sessionCat = FeeCategory::create(['name' => 'Session Charge', 'status' => 'Active', 'user_id' => $this->superAdmin->id]);

        $this->structureService->createStructure([
            'branch_id' => $branchTuit->id,
            'class_id' => $this->clsPlay->id,
            'session_year_id' => null,
            'structure_name' => 'Play All-Time With Tuition',
            'status' => 'Active',
            'total_amount' => '2000.00',
            'includes_monthly_tuition' => true,
            'tuition_month_number' => 1,
            'items' => [
                [
                    'fee_category_id' => $tuitionCat->id,
                    'head_name' => 'Monthly Tuition Fee (January)',
                    'amount' => '570.00',
                    'is_discountable' => false,
                    'is_tuition_head' => true,
                    'sort_order' => 10,
                ],
                [
                    'fee_category_id' => $this->admissionCat->id,
                    'head_name' => 'Admission Fee',
                    'amount' => '1000.00',
                    'is_discountable' => true,
                    'is_tuition_head' => false,
                    'sort_order' => 20,
                ],
                [
                    'fee_category_id' => $sessionCat->id,
                    'head_name' => 'Session Fee',
                    'amount' => '430.00',
                    'is_discountable' => false,
                    'is_tuition_head' => false,
                    'sort_order' => 30,
                ],
            ],
        ], $this->superAdmin->id);

        $baseStu = $this->createStudent('STD-TUIT-BASE');
        $stu1 = Student::create(array_merge($baseStu->only([
            'user_id', 'dob', 'gender', 'father_name', 'father_mobile', 'mother_name', 'mother_mobile',
            'guardian_name', 'guardian_mobile', 'session_year_id', 'class_id', 'section_id',
            'status', 'sms_status', 'present_village', 'present_post_office', 'present_district', 'present_division',
            'permanent_village', 'permanent_post_office', 'permanent_district', 'permanent_division',
        ]), [
            'branch_id' => $branchTuit->id,
            'student_identity' => 'STD-TUIT-SPLIT-01',
            'student_name' => 'Tuition Toggle One',
            'roll_number' => '11',
        ]));

        $inv1 = $this->invoiceService->generateForStudent($stu1, [], $this->superAdmin->id)['invoice'];

        // 1. Collection UI renders Auto Split Monthly Tuition toggle & scroll-lock cleanly
        $uiRes = $this->actingAs($this->adminUser)->get(route('fees.collection.index', [
            'mode' => 'single',
            'student_identity' => $stu1->student_identity,
        ]));
        $uiRes->assertOk();
        $uiRes->assertSee('id="includeTuitionInAutoSplitCheckbox"', false);
        $uiRes->assertSee('Include Monthly Tuition Fee');
        $uiRes->assertSee('onTuitionAutoSplitToggle', false);
        $uiRes->assertSee('include_tuition_in_auto_split', false);

        // 2. Auto Split excluding Monthly Tuition Fee pays 1430.00 strictly across non-tuition heads
        $nonTuitionRes = $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv1->id,
            'pay_amount' => '1430.00',
            'include_tuition_in_auto_split' => false,
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-19',
            'idempotency_key' => 'tuit-excl-full-1',
        ]);
        $nonTuitionRes->assertOk()->assertJsonPath('success', true);

        $inv1->refresh()->load('admissionHeadAllocations');
        $heads1 = $inv1->admissionHeadAllocations->keyBy('head_name_snapshot');
        $this->assertSame('Partial', $inv1->status);
        $this->assertEquals('1430.00', bcadd((string) $inv1->paid_amount, '0.00', 2));
        $this->assertEquals('570.00', bcadd((string) $inv1->due_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $heads1['Monthly Tuition Fee (January)']->allocated_paid_amount, '0.00', 2));
        $this->assertEquals('570.00', bcadd((string) $heads1['Monthly Tuition Fee (January)']->due_amount, '0.00', 2));
        $this->assertEquals('1000.00', bcadd((string) $heads1['Admission Fee']->allocated_paid_amount, '0.00', 2));
        $this->assertEquals('430.00', bcadd((string) $heads1['Session Fee']->allocated_paid_amount, '0.00', 2));

        // 3. Attempting another non-tuition Auto Split when only tuition remains is rejected cleanly (422)
        $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv1->id,
            'pay_amount' => '100.00',
            'include_tuition_in_auto_split' => false,
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-19',
        ])->assertStatus(422);

        // 4. Re-enabling Monthly Tuition Fee collects the remaining 570.00 cleanly
        $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv1->id,
            'pay_amount' => '570.00',
            'include_tuition_in_auto_split' => true,
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-19',
            'idempotency_key' => 'tuit-incl-rest-2',
        ])->assertOk();

        $inv1->refresh()->load('admissionHeadAllocations');
        $this->assertSame('Paid', $inv1->status);
        $this->assertEquals('0.00', bcadd((string) $inv1->due_amount, '0.00', 2));
        $this->assertEquals('570.00', bcadd((string) $inv1->admissionHeadAllocations->firstWhere('is_tuition_head', true)->allocated_paid_amount, '0.00', 2));

        // 5. Partial Auto Split + Direct Discount with Monthly Tuition excluded respects cap and leaves tuition untouched
        $stu2 = Student::create(array_merge($stu1->only([
            'user_id', 'dob', 'gender', 'father_name', 'father_mobile', 'mother_name', 'mother_mobile',
            'guardian_name', 'guardian_mobile', 'session_year_id', 'class_id', 'section_id', 'branch_id',
            'status', 'sms_status', 'present_village', 'present_post_office', 'present_district', 'present_division',
            'permanent_village', 'permanent_post_office', 'permanent_district', 'permanent_division',
        ]), [
            'student_identity' => 'STD-TUIT-SPLIT-02',
            'student_name' => 'Tuition Toggle Two',
            'roll_number' => '12',
        ]));
        $inv2 = $this->invoiceService->generateForStudent($stu2, [], $this->superAdmin->id)['invoice'];

        // Exceeding non-tuition cap (1430.00) while tuition excluded -> 422
        $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv2->id,
            'pay_amount' => '1500.00',
            'include_tuition_in_auto_split' => false,
            'payment_method' => 'Cash',
            'payment_date' => '2026-11-19',
        ])->assertStatus(422);

        // Combine 200.00 Direct Discount + 600.00 partial non-tuition Auto Split -> Tuition stays 0.00 paid, 570.00 due
        $this->actingAs($this->adminUser)->postJson(route('fees.collection.store'), [
            'invoice_id' => $inv2->id,
            'direct_discount_amount' => '200.00',
            'discount_reason' => 'Sibling waiver',
            'pay_amount' => '600.00',
            'include_tuition_in_auto_split' => false,
            'payment_method' => 'bKash',
            'payment_date' => '2026-11-19',
            'idempotency_key' => 'tuit-excl-disc-3',
        ])->assertOk();

        $inv2->refresh()->load('admissionHeadAllocations');
        $heads2 = $inv2->admissionHeadAllocations->keyBy('head_name_snapshot');
        $this->assertEquals('200.00', bcadd((string) $inv2->discount, '0.00', 2));
        $this->assertEquals('600.00', bcadd((string) $inv2->paid_amount, '0.00', 2));
        $this->assertEquals('1200.00', bcadd((string) $inv2->due_amount, '0.00', 2));
        $this->assertEquals('0.00', bcadd((string) $heads2['Monthly Tuition Fee (January)']->allocated_paid_amount, '0.00', 2));
        $this->assertEquals('570.00', bcadd((string) $heads2['Monthly Tuition Fee (January)']->due_amount, '0.00', 2));
        $nonTuitionPaidSum = bcadd(
            (string) $heads2['Admission Fee']->allocated_paid_amount,
            (string) $heads2['Session Fee']->allocated_paid_amount,
            2
        );
        $this->assertEquals('600.00', $nonTuitionPaidSum);

        // 6. Reports remain 100% reconciled
        $report = app(AdmissionFeeReportService::class)->generateReport(['mode' => 'class']);
        $this->assertTrue($report['reconciliation']['is_reconciled']);
        $this->assertTrue($report['class_matrix']['is_reconciled']);
    }
}
