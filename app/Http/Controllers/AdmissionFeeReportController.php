<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Classes;
use App\Models\FeeCategory;
use App\Models\SessionYear;
use App\Services\AdmissionFeeReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Shuchkin\SimpleXLSXGen;

/**
 * Reporting Dashboard, Print View & Excel Export Controller for Admission Fees (Subphases 4C + 4D).
 * Supports both backward-compatible JSON API consumers and the TALL-stack Blade console.
 */
class AdmissionFeeReportController extends Controller
{
    public const PENDING_SCHEMA_WARNING = 'Admission schema migrations are pending. Please contact the system administrator.';

    public function __construct(
        protected AdmissionFeeReportService $reportService
    ) {
    }

    /**
     * Authorize strict financial report access (`admin` and `super-admin` only).
     */
    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (!method_exists($user, 'hasRole') || (!$user->hasRole('admin') && !$user->hasRole('super-admin'))) {
            abort(403, 'Unauthorized access to admission fee reports.');
        }
    }

    /**
     * Pre-migration schema check without executing queries or relations against pending admission tables.
     */
    protected function admissionSchemaReady(): bool
    {
        return $this->reportService->admissionTablesAvailable()
            && Schema::hasTable('admission_fee_structure_items')
            && Schema::hasColumn('fee_invoices', 'admission_fee_structure_id')
            && Schema::hasColumn('fee_payments', 'status');
    }

    /**
     * Render Admission Fee Reports UI Console for browser visits, or return reconciled JSON
     * for API/AJAX requests to maintain full Subphase 4C backward compatibility.
     */
    public function index(Request $request): JsonResponse|View
    {
        $this->authorizeAccess();

        $resolved = $this->resolveReportPayload($request);

        if ($request->expectsJson() || $request->wantsJson() || $request->boolean('json') || $request->input('format') === 'json') {
            return response()->json($resolved['report']);
        }

        $options = $this->loadFilterOptions();

        return view('pages.fees.admission_reports', [
            'reportData' => $resolved['report'],
            'schemaReady' => $resolved['schema_ready'],
            'schemaWarning' => $resolved['schema_warning'],
            'filters' => $resolved['filters'],
            'branches' => $options['branches'],
            'classes' => $options['classes'],
            'sessions' => $options['sessions'],
            'feeCategories' => $options['feeCategories'],
        ]);
    }

    /**
     * Dedicated JSON data endpoint for reactive Alpine/Axios tab switching and filtering.
     */
    public function data(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $resolved = $this->resolveReportPayload($request);

        return response()->json($resolved['report']);
    }

    /**
     * Render the standalone A4 Landscape print view with reconciled totals and cancelled audit block.
     */
    public function printReport(Request $request): View
    {
        $this->authorizeAccess();

        $resolved = $this->resolveReportPayload($request);
        $options = $this->loadFilterOptions();
        $filters = $resolved['filters'];

        $branchName = !empty($filters['branch_id'])
            ? ($options['branches']->firstWhere('id', (int) $filters['branch_id'])?->branch_name ?? 'Unknown Branch')
            : 'All Branches';
        $sessionName = !empty($filters['session_year_id'])
            ? ($options['sessions']->firstWhere('id', (int) $filters['session_year_id'])?->session_name ?? 'Unknown Session')
            : 'All Sessions';
        $className = !empty($filters['class_id'])
            ? ($options['classes']->firstWhere('id', (int) $filters['class_id'])?->class_name ?? 'Unknown Class')
            : 'All Admission Classes';
        $categoryName = !empty($filters['fee_category_id'])
            ? ($options['feeCategories']->firstWhere('id', (int) $filters['fee_category_id'])?->name ?? 'Unknown Category')
            : 'All Categories';

        return view('pages.fees.admission_reports_print', [
            'reportData' => $resolved['report'],
            'schemaReady' => $resolved['schema_ready'],
            'schemaWarning' => $resolved['schema_warning'],
            'filterLabels' => [
                'branch' => $branchName,
                'session' => $sessionName,
                'class' => $className,
                'category' => $categoryName,
                'invoice_status' => $filters['invoice_status'] ?? 'All Active Statuses',
                'payment_status' => $filters['payment_status'] ?? 'All Payment States',
                'date_range' => (!empty($filters['date_from']) || !empty($filters['date_to']))
                    ? (($filters['date_from'] ?: 'Start') . ' to ' . ($filters['date_to'] ?: 'Today'))
                    : 'All Dates',
                'search' => $filters['search'] ?? '',
                'matrix_metric' => AdmissionFeeReportService::MATRIX_METRIC_LABELS[$filters['matrix_metric'] ?? AdmissionFeeReportService::METRIC_NET_RECEIVABLE] ?? 'Net Receivable',
                'class_view' => $filters['class_view'] ?? 'matrix',
            ],
        ]);
    }

    /**
     * Export the reconciled Admission Fee Report as a genuine Excel (.xlsx) workbook.
     */
    public function exportExcel(Request $request): Response
    {
        $this->authorizeAccess();

        $resolved = $this->resolveReportPayload($request);
        $reportData = $resolved['report'];
        $rows = $this->reportService->buildExcelRows($reportData);

        $mode = $reportData['mode'] ?? 'branch';
        $filename = 'admission_fee_report_' . $mode . '_' . date('Ymd_His') . '.xlsx';
        $xlsx = SimpleXLSXGen::fromArray($rows, 'Admission Report');

        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'mode' => 'nullable|in:branch,class,student,head',
            'matrix_metric' => 'nullable|in:gross,discount,net_receivable,collected,due,period_collected',
            'class_view' => 'nullable|in:matrix,summary',
            'branch_id' => 'nullable|exists:branches,id',
            'session_year_id' => 'nullable|exists:session_years,id',
            'class_id' => 'nullable|exists:classes,id',
            'fee_category_id' => 'nullable|exists:fee_categories,id',
            'head_name' => 'nullable|string|max:150',
            'invoice_status' => 'nullable|in:Unpaid,Partial,Paid,Waived,Cancelled',
            'payment_status' => 'nullable|in:Unpaid,Partial,Paid,Waived',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'search' => 'nullable|string|max:120',
        ]);
    }

    /**
     * @return array{
     *     schema_ready: bool,
     *     schema_warning: ?string,
     *     filters: array<string, mixed>,
     *     report: array<string, mixed>
     * }
     */
    protected function resolveReportPayload(Request $request): array
    {
        $filters = $this->validateFilters($request);
        $schemaReady = $this->admissionSchemaReady();

        if (!$schemaReady) {
            $report = $this->buildPendingSchemaReportPayload($filters);
            $report['schema_warning'] = self::PENDING_SCHEMA_WARNING;

            return [
                'schema_ready' => false,
                'schema_warning' => self::PENDING_SCHEMA_WARNING,
                'filters' => $report['filters'],
                'report' => $report,
            ];
        }

        $report = $this->reportService->generateReport($filters);
        $report['schema_warning'] = null;

        return [
            'schema_ready' => true,
            'schema_warning' => null,
            'filters' => $report['filters'] ?? $filters,
            'report' => $report,
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
    protected function loadFilterOptions(): array
    {
        return [
            'branches' => Branch::orderBy('branch_name')->get(),
            'classes' => Classes::all(),
            'sessions' => SessionYear::orderByDesc('id')->get(),
            'feeCategories' => FeeCategory::where('status', 'Active')->orderBy('name')->get(),
        ];
    }

    /**
     * Static pre-migration empty report payload guaranteeing zero queries against pending tables/columns.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildPendingSchemaReportPayload(array $filters): array
    {
        $mode = in_array($filters['mode'] ?? null, AdmissionFeeReportService::VALID_MODES, true)
            ? (string) $filters['mode']
            : AdmissionFeeReportService::MODE_BRANCH;

        $modeLabels = [
            AdmissionFeeReportService::MODE_BRANCH => 'Branch-wise Admission Fee Report',
            AdmissionFeeReportService::MODE_CLASS => 'Class-wise Admission Fee Report',
            AdmissionFeeReportService::MODE_STUDENT => 'Student-wise Admission Fee Report',
            AdmissionFeeReportService::MODE_HEAD => 'Fee Head-wise Admission Fee Report',
        ];

        $rawMetric = strtolower(trim((string) ($filters['matrix_metric'] ?? AdmissionFeeReportService::METRIC_NET_RECEIVABLE)));
        $matrixMetric = in_array($rawMetric, AdmissionFeeReportService::VALID_MATRIX_METRICS, true)
            ? $rawMetric
            : AdmissionFeeReportService::METRIC_NET_RECEIVABLE;

        $rawClassView = strtolower(trim((string) ($filters['class_view'] ?? 'matrix')));
        $classView = in_array($rawClassView, ['matrix', 'summary'], true) ? $rawClassView : 'matrix';

        return [
            'mode' => $mode,
            'mode_label' => $modeLabels[$mode],
            'tables_available' => false,
            'filters' => [
                'mode' => $mode,
                'matrix_metric' => $matrixMetric,
                'class_view' => $classView,
                'branch_id' => isset($filters['branch_id']) && $filters['branch_id'] !== '' ? (int) $filters['branch_id'] : null,
                'session_year_id' => isset($filters['session_year_id']) && $filters['session_year_id'] !== '' ? (int) $filters['session_year_id'] : null,
                'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
                'fee_category_id' => isset($filters['fee_category_id']) && $filters['fee_category_id'] !== '' ? (int) $filters['fee_category_id'] : null,
                'head_name' => trim((string) ($filters['head_name'] ?? '')),
                'invoice_status' => isset($filters['invoice_status']) && $filters['invoice_status'] !== '' ? (string) $filters['invoice_status'] : null,
                'payment_status' => isset($filters['payment_status']) && $filters['payment_status'] !== '' ? (string) $filters['payment_status'] : null,
                'date_from' => isset($filters['date_from']) && $filters['date_from'] !== '' ? (string) $filters['date_from'] : null,
                'date_to' => isset($filters['date_to']) && $filters['date_to'] !== '' ? (string) $filters['date_to'] : null,
                'search' => trim((string) ($filters['search'] ?? '')),
            ],
            'summary' => [
                'active_invoice_count' => 0,
                'active_student_count' => 0,
                'gross' => '0.00',
                'gross_formatted' => '0.00',
                'discount' => '0.00',
                'discount_formatted' => '0.00',
                'net_receivable' => '0.00',
                'net_receivable_formatted' => '0.00',
                'collected' => '0.00',
                'collected_formatted' => '0.00',
                'due' => '0.00',
                'due_formatted' => '0.00',
                'period_collected' => '0.00',
                'period_collected_formatted' => '0.00',
                'collection_rate' => '0.0',
            ],
            'cancelled_audit' => [
                'count' => 0,
                'gross_amount' => '0.00',
                'gross_amount_formatted' => '0.00',
                'discount_amount' => '0.00',
                'discount_amount_formatted' => '0.00',
                'net_amount' => '0.00',
                'net_amount_formatted' => '0.00',
                'invoices' => [],
            ],
            'reconciliation' => [
                'invoice_sum_gross' => '0.00',
                'head_sum_gross' => '0.00',
                'gross_diff' => '0.00',
                'invoice_sum_discount' => '0.00',
                'head_sum_discount' => '0.00',
                'discount_diff' => '0.00',
                'invoice_sum_net' => '0.00',
                'head_sum_net' => '0.00',
                'net_diff' => '0.00',
                'invoice_sum_collected' => '0.00',
                'head_sum_collected' => '0.00',
                'collected_diff' => '0.00',
                'invoice_sum_due' => '0.00',
                'head_sum_due' => '0.00',
                'due_diff' => '0.00',
                'invoice_sum_period_collected' => '0.00',
                'head_sum_period_collected' => '0.00',
                'period_collected_diff' => '0.00',
                'rows_sum_net' => '0.00',
                'rows_sum_collected' => '0.00',
                'rows_sum_due' => '0.00',
                'rows_sum_period_collected' => '0.00',
                'net_minus_collected_equals_due' => true,
                'is_reconciled' => true,
            ],
            'rows' => [],
            'class_matrix' => AdmissionFeeReportService::emptyClassMatrixPayload($matrixMetric),
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }
}
