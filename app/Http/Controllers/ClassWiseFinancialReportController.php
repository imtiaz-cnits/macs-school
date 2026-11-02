<?php

namespace App\Http\Controllers;

use App\Models\SessionYear;
use App\Services\ClassWiseFinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Shuchkin\SimpleXLSXGen;

class ClassWiseFinancialReportController extends Controller
{
    protected ClassWiseFinancialReportService $reportService;

    public function __construct(ClassWiseFinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Authorize financial report access for admin and super-admin.
     */
    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (method_exists($user, 'hasRole')) {
            if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                abort(403, 'Unauthorized access to financial reports.');
            }
        }
    }

    /**
     * Display the Class-wise Financial Report interactive dashboard view.
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $request->validate([
            'session_year_id' => 'nullable|exists:session_years,id',
            'period_type'     => 'nullable|in:all,monthly,custom',
            'month'           => 'nullable|string|max:20',
            'year'            => 'nullable|numeric|min:2000|max:2100',
            'start_date'      => 'nullable|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
        ]);

        $filters = [
            'session_year_id' => $request->session_year_id,
            'period_type'     => $request->period_type ?? 'all',
            'month'           => $request->month,
            'year'            => $request->year,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ];

        $reportData = $this->reportService->generateReport($filters);
        $sessions = SessionYear::latest()->get();

        return view('pages.accounts.class_wise_financial_report', array_merge($reportData, [
            'sessions' => $sessions,
        ]));
    }

    /**
     * Display the professional printable A4 landscape report.
     */
    public function printReport(Request $request)
    {
        $this->authorizeAccess();

        $filters = [
            'session_year_id' => $request->session_year_id,
            'period_type'     => $request->period_type ?? 'all',
            'month'           => $request->month,
            'year'            => $request->year,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ];

        $reportData = $this->reportService->generateReport($filters);

        return view('pages.accounts.class_wise_financial_print', $reportData);
    }

    /**
     * Export the filtered report as a genuine Excel (.xlsx) spreadsheet.
     */
    public function exportExcel(Request $request)
    {
        $this->authorizeAccess();

        $filters = [
            'session_year_id' => $request->session_year_id,
            'period_type'     => $request->period_type ?? 'all',
            'month'           => $request->month,
            'year'            => $request->year,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ];

        $reportData = $this->reportService->generateReport($filters);

        // Build Excel Table Rows
        $rows = [];

        // Meta headers
        $rows[] = ['MACS SCHOOL & COLLEGE'];
        $rows[] = ['Class-wise Financial Report'];
        $rows[] = ['Academic Session:', $reportData['session_name'], 'Reporting Period:', $reportData['period_label']];
        $rows[] = ['Generated At:', $reportData['generated_at']];
        $rows[] = []; // Blank spacer row

        // Grouped Column Header Row 1
        $rows[] = [
            'Class',
            'Due', '', '', '',
            'Collected', '', '', '',
            'Outstanding', '', '', ''
        ];

        // Column Sub-Header Row 2
        $rows[] = [
            'Class',
            'Monthly Fee', 'Other Fees', 'Other', 'Total',
            'Monthly Fee', 'Other Fees', 'Other', 'Total',
            'Monthly Fee', 'Other Fees', 'Other', 'Total'
        ];

        // Class Rows
        foreach ($reportData['rows'] as $r) {
            $rows[] = [
                $r['class_name'],
                (float) $r['due']['monthly_fee'],
                (float) $r['due']['other_fees'],
                (float) $r['due']['other'],
                (float) $r['due']['total'],
                (float) $r['collected']['monthly_fee'],
                (float) $r['collected']['other_fees'],
                (float) $r['collected']['other'],
                (float) $r['collected']['total'],
                (float) $r['outstanding']['monthly_fee'],
                (float) $r['outstanding']['other_fees'],
                (float) $r['outstanding']['other'],
                (float) $r['outstanding']['total']
            ];
        }

        // Grand Total Row
        $gt = $reportData['grand_total'];
        $rows[] = [
            'Grand Total',
            (float) $gt['due']['monthly_fee'],
            (float) $gt['due']['other_fees'],
            (float) $gt['due']['other'],
            (float) $gt['due']['total'],
            (float) $gt['collected']['monthly_fee'],
            (float) $gt['collected']['other_fees'],
            (float) $gt['collected']['other'],
            (float) $gt['collected']['total'],
            (float) $gt['outstanding']['monthly_fee'],
            (float) $gt['outstanding']['other_fees'],
            (float) $gt['outstanding']['other'],
            (float) $gt['outstanding']['total']
        ];

        $filename = 'class_wise_financial_report_' . date('Ymd_His') . '.xlsx';
        $xlsx = SimpleXLSXGen::fromArray($rows, 'Financial Report');

        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ]);
    }

    /**
     * Export the filtered report as an A4 Landscape PDF.
     */
    public function exportPdf(Request $request)
    {
        $this->authorizeAccess();

        $filters = [
            'session_year_id' => $request->session_year_id,
            'period_type'     => $request->period_type ?? 'all',
            'month'           => $request->month,
            'year'            => $request->year,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ];

        $reportData = $this->reportService->generateReport($filters);

        $pdf = Pdf::loadView('pages.accounts.class_wise_financial_pdf', $reportData)
            ->setPaper('a4', 'landscape');

        $filename = 'class_wise_financial_report_' . date('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}

