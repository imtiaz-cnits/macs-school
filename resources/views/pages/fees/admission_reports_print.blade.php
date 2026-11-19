<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportData['mode_label'] ?? 'Admission Fee Report' }} - Printable Sheet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #0F1E2C;
            background: #f8fafc;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        @media screen {
            body {
                background: #f1f5f9;
                padding: 16px;
                display: flex;
                flex-direction: column;
                align-items: center;
                min-height: 100vh;
            }
            .print-sheet {
                width: 297mm;
                min-height: 210mm;
                background: #ffffff;
                padding: 7mm 5mm;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
            }
        }
        @media print {
            html, body {
                height: 100%;
                background: #ffffff;
                padding: 0;
                margin: 0;
                color: #000000;
            }
            .print-sheet {
                width: 100%;
                min-height: calc(100% - 1px);
                padding: 0;
                box-sizing: border-box;
                border: none;
                box-shadow: none;
                display: flex;
                flex-direction: column;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 landscape;
                margin: 6mm 4mm;
            }
        }
        .report-table th, .report-table td {
            padding: 5px 7px !important;
            font-size: 10.5px;
            border: 1px solid #d1d5db;
        }
        .report-table thead th {
            background-color: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .matrix-table th, .matrix-table td {
            padding: 3.5px 4.5px !important;
            font-size: 9px;
            line-height: 1.2;
            border: 1px solid #d1d5db;
        }
        .matrix-table thead th {
            background-color: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .double-bottom {
            border-bottom: 3px double #000000 !important;
        }
    </style>
</head>
<body>

    @php
        $mode = $reportData['mode'] ?? 'branch';
        $summary = $reportData['summary'] ?? [];
        $audit = $reportData['cancelled_audit'] ?? ['count' => 0, 'invoices' => []];
        $recon = $reportData['reconciliation'] ?? [];
        $rows = $reportData['rows'] ?? [];
        $classMatrix = $reportData['class_matrix'] ?? [];
        $matrixCols = $classMatrix['head_columns'] ?? [];
        $matrixRows = $classMatrix['rows'] ?? [];
        $matrixTotals = $classMatrix['column_totals'] ?? [];
        $useClassMatrix = ($mode === 'class' && (($reportData['filters']['class_view'] ?? 'matrix') !== 'summary') && count($matrixCols) > 0);
    @endphp

    <!-- Sticky Action Toolbar (Hidden in Print) -->
    <div class="fixed top-5 right-6 flex items-center gap-3 no-print z-50">
        <button onclick="window.print()" class="bg-gray-900 hover:bg-black text-white text-xs font-black uppercase tracking-widest py-2.5 px-5 rounded-xl shadow-lg transition-all flex items-center gap-2 active:scale-95">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Print Sheet
        </button>
        <button onclick="window.close()" class="bg-white border-2 border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-black uppercase tracking-widest py-2 px-4 rounded-xl shadow-sm transition-all active:scale-95">
            Close
        </button>
    </div>

    <!-- Printable Sheet Container -->
    <div class="print-sheet">

        <!-- Institutional Header -->
        <div class="flex justify-between items-center border-b-2 border-gray-900 pb-3 mb-3">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-14 h-14 object-contain rounded" alt="MACS Logo">
                <div>
                    <h1 class="text-xl font-black uppercase tracking-tight text-gray-900 leading-none">MACS School &amp; College</h1>
                    <p class="text-[11px] font-semibold text-gray-600 mt-1">Jalalpur, Pabna Sadar, Pabna</p>
                    <p class="text-[10px] font-medium text-gray-500">Phone: 01896-220299, 01896-220300</p>
                </div>
            </div>

            <div class="text-right">
                <span class="text-[9px] font-black uppercase border border-gray-800 px-2 py-0.5 bg-gray-100 inline-block rounded tracking-wider">
                    Admission Fee Financial Audit
                </span>
                <h2 class="text-base font-black uppercase text-gray-900 tracking-tight mt-1 leading-tight">
                    {{ $reportData['mode_label'] ?? 'Admission Fee Report' }}
                </h2>
                <div class="text-[10px] font-semibold text-gray-700 mt-1">
                    Branch: <strong class="text-gray-900">{{ $filterLabels['branch'] ?? 'All Branches' }}</strong>
                    &bull; Session: <strong class="text-gray-900">{{ $filterLabels['session'] ?? 'All Sessions' }}</strong>
                    &bull; Class: <strong class="text-gray-900">{{ $filterLabels['class'] ?? 'All Classes' }}</strong>
                </div>
                <div class="text-[10px] font-semibold text-gray-600 mt-0.5">
                    Category: <strong>{{ $filterLabels['category'] ?? 'All Categories' }}</strong>
                    &bull; Date Range: <strong>{{ $filterLabels['date_range'] ?? 'All Dates' }}</strong>
                    &bull; Invoice: <strong>{{ $filterLabels['invoice_status'] ?? 'All Active Statuses' }}</strong>
                </div>
                <p class="text-[9px] font-medium text-gray-500 mt-0.5">
                    Generated At: {{ $reportData['generated_at'] ?? now()->format('d M Y, h:i A') }}
                    &bull; Reconciliation: <strong>{{ !empty($recon['is_reconciled']) ? 'VERIFIED' : 'VARIANCE' }}</strong>
                </p>
            </div>
        </div>

        <!-- Pre-Migration Warning (When Schema Is Pending) -->
        @if(!$schemaReady && !empty($schemaWarning))
            <div class="mb-3 p-3 border-2 border-amber-500 bg-amber-50 rounded-lg text-amber-900 text-xs font-bold">
                {{ $schemaWarning }}
            </div>
        @endif

        <!-- Option A Date Clarification -->
        <div class="mb-2.5 text-[9.5px] font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded px-2.5 py-1.5">
            <strong>Date Semantics (Option A):</strong> Gross, Discount, Net Receivable, Cumulative Collected &amp; Due are grouped by Invoice/Due Date (Cohort); Period Collected independently totals Active Cash Receipts by Payment Date. Historical Admission Class attribution uses <em>admission_fee_structures.class_id</em>.
        </div>

        <!-- Summary Totals Strip -->
        <div class="grid grid-cols-6 gap-2 mb-3">
            <div class="p-2 border border-gray-300 rounded text-center bg-gray-50/70">
                <div class="text-[8.5px] font-black uppercase tracking-wider text-gray-600">Gross Billed</div>
                <div class="text-xs font-black font-mono text-gray-900 mt-0.5">Tk {{ $summary['gross_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="p-2 border border-gray-300 rounded text-center bg-amber-50/40">
                <div class="text-[8.5px] font-black uppercase tracking-wider text-amber-700">Discount</div>
                <div class="text-xs font-black font-mono text-amber-800 mt-0.5">Tk {{ $summary['discount_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="p-2 border border-gray-300 rounded text-center bg-blue-50/40">
                <div class="text-[8.5px] font-black uppercase tracking-wider text-blue-700">Net Receivable</div>
                <div class="text-xs font-black font-mono text-blue-900 mt-0.5">Tk {{ $summary['net_receivable_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="p-2 border border-gray-300 rounded text-center bg-emerald-50/40">
                <div class="text-[8.5px] font-black uppercase tracking-wider text-emerald-700">Collected ({{ $summary['collection_rate'] ?? '0.0' }}%)</div>
                <div class="text-xs font-black font-mono text-emerald-800 mt-0.5">Tk {{ $summary['collected_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="p-2 border border-gray-300 rounded text-center bg-rose-50/40">
                <div class="text-[8.5px] font-black uppercase tracking-wider text-rose-700">Outstanding Due</div>
                <div class="text-xs font-black font-mono text-rose-800 mt-0.5">Tk {{ $summary['due_formatted'] ?? '0.00' }}</div>
            </div>
            <div class="p-2 border border-gray-300 rounded text-center bg-indigo-50/40">
                <div class="text-[8.5px] font-black uppercase tracking-wider text-indigo-700">Period Collected</div>
                <div class="text-xs font-black font-mono text-indigo-800 mt-0.5">Tk {{ $summary['period_collected_formatted'] ?? '0.00' }}</div>
            </div>
        </div>

        <!-- Mode-Specific Reconciled Data Table -->
        <div class="w-full flex-1">
            @if($useClassMatrix)
                <div class="mb-1.5 flex items-center justify-between text-[9.5px] font-bold text-gray-700">
                    <span>
                        Class-wise Fee Head Matrix &bull; Head Column Metric:
                        <strong class="uppercase text-gray-900">{{ $classMatrix['metric_label'] ?? 'Net Receivable' }}</strong>
                    </span>
                    <span class="font-mono">
                        {{ count($matrixRows) }} Class(es) &bull; {{ count($matrixCols) }} Fee Head(s)
                    </span>
                </div>

                <table class="w-full matrix-table text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="font-black whitespace-nowrap">Class</th>
                            <th class="text-center font-black">Inv</th>
                            <th class="text-center font-black">Std</th>
                            @foreach($matrixCols as $mCol)
                                <th class="text-center font-black align-bottom {{ !empty($mCol['is_tuition_head']) ? 'text-blue-900' : 'text-gray-800' }}">
                                    {{ $mCol['head_name'] }}
                                </th>
                            @endforeach
                            <th class="text-right font-black whitespace-nowrap">Gross</th>
                            <th class="text-right font-black whitespace-nowrap">Disc.</th>
                            <th class="text-right font-black whitespace-nowrap text-blue-900">Net Rec.</th>
                            <th class="text-right font-black whitespace-nowrap text-emerald-900">Collected</th>
                            <th class="text-right font-black whitespace-nowrap text-rose-900">Due</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($matrixRows as $mRow)
                            <tr>
                                <td class="font-bold whitespace-nowrap">{{ $mRow['class_name'] }}</td>
                                <td class="text-center font-mono">{{ $mRow['invoice_count'] }}</td>
                                <td class="text-center font-mono">{{ $mRow['student_count'] }}</td>
                                @foreach($matrixCols as $mCol)
                                    @php
                                        $hKey = $mCol['head_key'];
                                        $cell = $mRow['cells'][$hKey] ?? null;
                                        $isZero = $cell ? !empty($cell['is_zero']) : true;
                                    @endphp
                                    <td class="text-right font-mono {{ $isZero ? 'text-gray-400' : 'text-gray-900 font-semibold' }}">
                                        {{ $cell['active_value_formatted'] ?? '0.00' }}
                                    </td>
                                @endforeach
                                <td class="text-right font-mono">{{ $mRow['gross_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $mRow['discount_formatted'] }}</td>
                                <td class="text-right font-mono font-bold">{{ $mRow['net_receivable_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-emerald-800">{{ $mRow['collected_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-rose-800">{{ $mRow['due_formatted'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr class="bg-gray-100 font-black text-gray-900 double-bottom">
                            <td class="uppercase tracking-wider whitespace-nowrap">Grand Total</td>
                            <td class="text-center font-mono">{{ $summary['active_invoice_count'] ?? 0 }}</td>
                            <td class="text-center font-mono">{{ $summary['active_student_count'] ?? 0 }}</td>
                            @foreach($matrixCols as $mCol)
                                @php
                                    $hKey = $mCol['head_key'];
                                    $colTotal = $matrixTotals[$hKey] ?? null;
                                @endphp
                                <td class="text-right font-mono">{{ $colTotal['active_value_formatted'] ?? '0.00' }}</td>
                            @endforeach
                            <td class="text-right font-mono">{{ $summary['gross_formatted'] ?? '0.00' }}</td>
                            <td class="text-right font-mono">{{ $summary['discount_formatted'] ?? '0.00' }}</td>
                            <td class="text-right font-mono">{{ $summary['net_receivable_formatted'] ?? '0.00' }}</td>
                            <td class="text-right font-mono text-emerald-900">{{ $summary['collected_formatted'] ?? '0.00' }}</td>
                            <td class="text-right font-mono text-rose-900">{{ $summary['due_formatted'] ?? '0.00' }}</td>
                        </tr>
                    </tfoot>
                </table>
            @else
            <table class="w-full report-table text-left border-collapse">
                <thead>
                    @if($mode === 'branch')
                        <tr>
                            <th>Branch</th>
                            <th class="text-center">Invoices</th>
                            <th class="text-center">Students</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Net Receivable</th>
                            <th class="text-right">Collected (Cumul.)</th>
                            <th class="text-right">Due</th>
                            <th class="text-right">Period Collected</th>
                            <th class="text-right">Rate</th>
                        </tr>
                    @elseif($mode === 'class')
                        <tr>
                            <th>Admission Class</th>
                            <th class="text-center">Invoices</th>
                            <th class="text-center">Students</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Net Receivable</th>
                            <th class="text-right">Collected (Cumul.)</th>
                            <th class="text-right">Due</th>
                            <th class="text-right">Period Collected</th>
                            <th class="text-right">Rate</th>
                        </tr>
                    @elseif($mode === 'head')
                        <tr>
                            <th>Fee Category</th>
                            <th>Fee Head</th>
                            <th class="text-center">Tuition</th>
                            <th class="text-center">Invoices</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Net Receivable</th>
                            <th class="text-right">Collected (Cumul.)</th>
                            <th class="text-right">Due</th>
                            <th class="text-right">Period Collected</th>
                            <th class="text-right">Rate</th>
                        </tr>
                    @else
                        <tr>
                            <th>Invoice No</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Admission Class</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-center">Heads</th>
                            <th class="text-right">Gross</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Net Receivable</th>
                            <th class="text-right">Collected</th>
                            <th class="text-right">Due</th>
                            <th class="text-right">Period Coll.</th>
                        </tr>
                    @endif
                </thead>

                <tbody>
                    @forelse($rows as $r)
                        @if($mode === 'branch')
                            <tr>
                                <td class="font-bold">{{ $r['branch_name'] }}</td>
                                <td class="text-center font-mono">{{ $r['invoice_count'] }}</td>
                                <td class="text-center font-mono">{{ $r['student_count'] }}</td>
                                <td class="text-right font-mono">{{ $r['gross_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $r['discount_formatted'] }}</td>
                                <td class="text-right font-mono font-bold">{{ $r['net_receivable_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-emerald-800">{{ $r['collected_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-rose-800">{{ $r['due_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-indigo-800">{{ $r['period_collected_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $r['collection_rate'] }}%</td>
                            </tr>
                        @elseif($mode === 'class')
                            <tr>
                                <td class="font-bold">{{ $r['class_name'] }}</td>
                                <td class="text-center font-mono">{{ $r['invoice_count'] }}</td>
                                <td class="text-center font-mono">{{ $r['student_count'] }}</td>
                                <td class="text-right font-mono">{{ $r['gross_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $r['discount_formatted'] }}</td>
                                <td class="text-right font-mono font-bold">{{ $r['net_receivable_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-emerald-800">{{ $r['collected_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-rose-800">{{ $r['due_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-indigo-800">{{ $r['period_collected_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $r['collection_rate'] }}%</td>
                            </tr>
                        @elseif($mode === 'head')
                            <tr>
                                <td class="font-semibold">{{ $r['category_name'] }}</td>
                                <td class="font-bold">{{ $r['head_name'] }}</td>
                                <td class="text-center">{{ !empty($r['is_tuition_head']) ? 'Yes' : 'No' }}</td>
                                <td class="text-center font-mono">{{ $r['invoice_count'] }}</td>
                                <td class="text-right font-mono">{{ $r['gross_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $r['discount_formatted'] }}</td>
                                <td class="text-right font-mono font-bold">{{ $r['net_receivable_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-emerald-800">{{ $r['collected_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-rose-800">{{ $r['due_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-indigo-800">{{ $r['period_collected_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $r['collection_rate'] }}%</td>
                            </tr>
                        @else
                            <tr>
                                <td class="font-mono font-bold whitespace-nowrap">{{ $r['invoice_no'] }}</td>
                                <td class="font-mono whitespace-nowrap">{{ $r['student_identity'] }}</td>
                                <td class="font-bold">{{ $r['student_name'] }}</td>
                                <td>{{ $r['admission_class_name'] }}</td>
                                <td>{{ $r['branch_name'] }}</td>
                                <td class="font-mono whitespace-nowrap">{{ $r['invoice_date'] ?? 'N/A' }}</td>
                                <td class="font-bold">{{ $r['display_status'] }}</td>
                                <td class="text-center font-mono">{{ $r['head_count'] }}</td>
                                <td class="text-right font-mono">{{ $r['gross_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $r['discount_formatted'] }}</td>
                                <td class="text-right font-mono font-bold">{{ $r['net_receivable_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-emerald-800">{{ $r['collected_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-rose-800">{{ $r['due_formatted'] }}</td>
                                <td class="text-right font-mono font-bold text-indigo-800">{{ $r['period_collected_formatted'] }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ $mode === 'student' ? 14 : ($mode === 'head' ? 11 : 10) }}" class="text-center py-6 font-bold text-gray-500">
                                {{ !$schemaReady ? 'No data rendered while schema migrations are pending.' : 'No active admission fee records matched the selected filters.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if(count($rows) > 0)
                    <tfoot>
                        <tr class="bg-gray-100 font-black text-gray-900 double-bottom">
                            @if($mode === 'branch' || $mode === 'class')
                                <td class="uppercase tracking-wider">Grand Total</td>
                                <td class="text-center font-mono">{{ $summary['active_invoice_count'] ?? 0 }}</td>
                                <td class="text-center font-mono">{{ $summary['active_student_count'] ?? 0 }}</td>
                                <td class="text-right font-mono">{{ $summary['gross_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['discount_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['net_receivable_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-emerald-900">{{ $summary['collected_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-rose-900">{{ $summary['due_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-indigo-900">{{ $summary['period_collected_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['collection_rate'] ?? '0.0' }}%</td>
                            @elseif($mode === 'head')
                                <td colspan="3" class="uppercase tracking-wider">Grand Total</td>
                                <td class="text-center font-mono">{{ $summary['active_invoice_count'] ?? 0 }}</td>
                                <td class="text-right font-mono">{{ $summary['gross_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['discount_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['net_receivable_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-emerald-900">{{ $summary['collected_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-rose-900">{{ $summary['due_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-indigo-900">{{ $summary['period_collected_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['collection_rate'] ?? '0.0' }}%</td>
                            @else
                                <td colspan="7" class="uppercase tracking-wider">Grand Total ({{ $summary['active_invoice_count'] ?? 0 }} Active Invoices / {{ $summary['active_student_count'] ?? 0 }} Students)</td>
                                <td class="text-center font-mono">—</td>
                                <td class="text-right font-mono">{{ $summary['gross_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['discount_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono">{{ $summary['net_receivable_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-emerald-900">{{ $summary['collected_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-rose-900">{{ $summary['due_formatted'] ?? '0.00' }}</td>
                                <td class="text-right font-mono text-indigo-900">{{ $summary['period_collected_formatted'] ?? '0.00' }}</td>
                            @endif
                        </tr>
                    </tfoot>
                @endif
            </table>
            @endif
        </div>

        <!-- Isolated Cancelled Invoices Audit Section -->
        @if($schemaReady && ($audit['count'] ?? 0) > 0)
            <div class="mt-4 border border-amber-400 rounded p-2.5 bg-amber-50/30">
                <div class="flex justify-between items-center mb-1.5">
                    <h3 class="text-[11px] font-black uppercase tracking-wider text-amber-900">
                        Cancelled Admission Invoices Audit (Excluded From Active Totals)
                    </h3>
                    <span class="text-[10px] font-mono font-black text-amber-900">
                        Count: {{ $audit['count'] }} &bull; Historical Net: Tk {{ $audit['net_amount_formatted'] ?? '0.00' }}
                    </span>
                </div>
                <table class="w-full report-table text-left border-collapse">
                    <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Admission Class</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th class="text-right">Historical Gross</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Historical Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audit['invoices'] as $cInv)
                            <tr>
                                <td class="font-mono line-through">{{ $cInv['invoice_no'] }}</td>
                                <td class="font-mono">{{ $cInv['student_identity'] }}</td>
                                <td>{{ $cInv['student_name'] }}</td>
                                <td>{{ $cInv['admission_class_name'] }}</td>
                                <td>{{ $cInv['branch_name'] }}</td>
                                <td class="font-mono">{{ $cInv['invoice_date'] ?? 'N/A' }}</td>
                                <td class="text-right font-mono">{{ $cInv['gross_formatted'] }}</td>
                                <td class="text-right font-mono">{{ $cInv['discount_formatted'] }}</td>
                                <td class="text-right font-mono font-bold">{{ $cInv['net_amount_formatted'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Signature Footer for Official Auditing (Always Docked at Page Bottom) -->
        <div class="mt-auto pt-6 break-inside-avoid">
            <div class="pt-4 grid grid-cols-4 gap-6 text-center text-xs font-bold text-gray-700">
                <div>
                    <div class="border-t border-gray-400 pt-1">Prepared By</div>
                </div>
                <div>
                    <div class="border-t border-gray-400 pt-1">Accountant / Cashier</div>
                </div>
                <div>
                    <div class="border-t border-gray-400 pt-1">Internal Auditor</div>
                </div>
                <div>
                    <div class="border-t border-gray-400 pt-1">Principal / Head of Institute</div>
                </div>
            </div>

            <!-- Footnote -->
            <div class="mt-3 pt-2 border-t border-gray-200 flex justify-between items-center text-[9px] text-gray-500">
                <span>Admission Fee Report &bull; MACS Education Management System</span>
                <span>Printed on: {{ now()->format('d M Y, h:i:s A') }}</span>
            </div>
        </div>

    </div>

</body>
</html>
