<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class-wise Financial Report - {{ $session_name }} ({{ $period_label }})</title>
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
                padding: 24px;
                display: flex;
                flex-direction: column;
                align-items: center;
                min-height: 100vh;
            }
            .print-sheet {
                width: 297mm;
                min-height: 210mm;
                background: #ffffff;
                padding: 12mm 15mm;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
            }
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
                margin: 0;
                color: #000000;
            }
            .print-sheet {
                width: 100%;
                min-height: 100%;
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
                margin: 8mm 10mm;
            }
        }
        .report-table th, .report-table td {
            padding: 5px 7px !important;
            font-size: 11px;
            border: 1px solid #d1d5db;
        }
        .report-table thead th {
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

    <!-- Sticky Print / Action Toolbar (Hidden in Print) -->
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
                    <h1 class="text-xl font-black uppercase tracking-tight text-gray-900 leading-none">MACS School & College</h1>
                    <p class="text-[11px] font-semibold text-gray-600 mt-1">Jalalpur, Pabna Sadar, Pabna</p>
                    <p class="text-[10px] font-medium text-gray-500">Phone: 01896-220299, 01896-220300</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[9px] font-black uppercase border border-gray-800 px-2 py-0.5 bg-gray-100 inline-block rounded tracking-wider">
                    Accounts & Finance Report
                </span>
                <h2 class="text-base font-black uppercase text-gray-900 tracking-tight mt-1 leading-tight">
                    Class-wise Financial Report
                </h2>
                <div class="flex items-center justify-end gap-3 mt-1 text-[11px] font-semibold text-gray-700">
                    <span>Academic Session: <strong class="font-bold text-gray-900">{{ $session_name }}</strong></span>
                    <span>•</span>
                    <span>Period: <strong class="font-bold text-gray-900">{{ $period_label }}</strong></span>
                </div>
                <p class="text-[9px] font-medium text-gray-400 mt-0.5">Generated At: {{ $generated_at }}</p>
            </div>
        </div>

        <!-- Executive Summary Snapshot Cards -->
        <div class="grid grid-cols-3 gap-3 mb-3">
            <div class="p-2.5 border border-gray-300 rounded-lg bg-blue-50/40 text-center">
                <div class="text-[9px] font-black uppercase tracking-widest text-blue-700">Total Due Amount</div>
                <div class="text-base font-black font-mono text-gray-900 mt-0.5">Tk {{ number_format($total_due, 2) }}</div>
            </div>
            <div class="p-2.5 border border-gray-300 rounded-lg bg-emerald-50/40 text-center">
                <div class="text-[9px] font-black uppercase tracking-widest text-emerald-700">Total Collected Amount</div>
                <div class="text-base font-black font-mono text-emerald-700 mt-0.5">Tk {{ number_format($total_collected, 2) }}</div>
            </div>
            <div class="p-2.5 border border-gray-300 rounded-lg bg-rose-50/40 text-center">
                <div class="text-[9px] font-black uppercase tracking-widest text-rose-700">Total Outstanding Balance</div>
                <div class="text-base font-black font-mono text-rose-700 mt-0.5">Tk {{ number_format($total_outstanding, 2) }}</div>
            </div>
        </div>

        <!-- Exact 13 Columns Financial Report Table -->
        <div class="w-full flex-1">
            <table class="w-full report-table text-left border-collapse">
                <thead>
                    <!-- Header Row 1: High Level Financial Groups -->
                    <tr>
                        <th rowspan="2" class="text-left font-black uppercase tracking-wider text-gray-900 align-middle" style="width: 120px;">
                            Class
                        </th>
                        <th colspan="4" class="text-center font-black uppercase tracking-widest text-blue-800 bg-blue-50/60 py-1">
                            Due
                        </th>
                        <th colspan="4" class="text-center font-black uppercase tracking-widest text-emerald-800 bg-emerald-50/60 py-1">
                            Collected
                        </th>
                        <th colspan="4" class="text-center font-black uppercase tracking-widest text-rose-800 bg-rose-50/60 py-1">
                            Outstanding
                        </th>
                    </tr>

                    <!-- Header Row 2: 4 Sub-Columns Per Group -->
                    <tr class="text-[10px] font-bold text-gray-700">
                        <!-- Due Sub-columns -->
                        <th class="text-right whitespace-nowrap bg-blue-50/20">Monthly Fee</th>
                        <th class="text-right whitespace-nowrap bg-blue-50/20">Other Fees</th>
                        <th class="text-right whitespace-nowrap bg-blue-50/20">Other</th>
                        <th class="text-right whitespace-nowrap bg-blue-100/50 font-black text-blue-900">Total</th>

                        <!-- Collected Sub-columns -->
                        <th class="text-right whitespace-nowrap bg-emerald-50/20">Monthly Fee</th>
                        <th class="text-right whitespace-nowrap bg-emerald-50/20">Other Fees</th>
                        <th class="text-right whitespace-nowrap bg-emerald-50/20">Other</th>
                        <th class="text-right whitespace-nowrap bg-emerald-100/50 font-black text-emerald-900">Total</th>

                        <!-- Outstanding Sub-columns -->
                        <th class="text-right whitespace-nowrap bg-rose-50/20">Monthly Fee</th>
                        <th class="text-right whitespace-nowrap bg-rose-50/20">Other Fees</th>
                        <th class="text-right whitespace-nowrap bg-rose-50/20">Other</th>
                        <th class="text-right whitespace-nowrap bg-rose-100/50 font-black text-rose-900">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr>
                        <!-- Class Name in Academic Order -->
                        <td class="font-bold text-gray-900 whitespace-nowrap">
                            {{ $r['class_name'] }}
                        </td>

                        <!-- Due Sub-columns -->
                        <td class="text-right font-mono font-medium text-gray-800">
                            {{ number_format($r['due']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-medium text-gray-800">
                            {{ number_format($r['due']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-medium text-gray-500">
                            {{ number_format($r['due']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-bold text-blue-900 bg-blue-50/30">
                            {{ number_format($r['due']['total'], 2) }}
                        </td>

                        <!-- Collected Sub-columns -->
                        <td class="text-right font-mono font-medium text-gray-800">
                            {{ number_format($r['collected']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-medium text-gray-800">
                            {{ number_format($r['collected']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-medium text-gray-500">
                            {{ number_format($r['collected']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-bold text-emerald-900 bg-emerald-50/30">
                            {{ number_format($r['collected']['total'], 2) }}
                        </td>

                        <!-- Outstanding Sub-columns -->
                        <td class="text-right font-mono font-medium text-gray-800">
                            {{ number_format($r['outstanding']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-medium text-gray-800">
                            {{ number_format($r['outstanding']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-medium text-gray-500">
                            {{ number_format($r['outstanding']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-bold text-rose-900 bg-rose-50/30">
                            {{ number_format($r['outstanding']['total'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <!-- Grand Total Row -->
                    <tr class="bg-gray-100 font-black text-gray-900 double-bottom">
                        <td class="font-black uppercase tracking-wider text-gray-900">
                            Grand Total
                        </td>

                        <!-- Due Grand Totals -->
                        <td class="text-right font-mono font-black text-gray-900">
                            {{ number_format($grand_total['due']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-900">
                            {{ number_format($grand_total['due']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-600">
                            {{ number_format($grand_total['due']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-blue-900 bg-blue-100/60">
                            {{ number_format($grand_total['due']['total'], 2) }}
                        </td>

                        <!-- Collected Grand Totals -->
                        <td class="text-right font-mono font-black text-gray-900">
                            {{ number_format($grand_total['collected']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-900">
                            {{ number_format($grand_total['collected']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-600">
                            {{ number_format($grand_total['collected']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-emerald-900 bg-emerald-100/60">
                            {{ number_format($grand_total['collected']['total'], 2) }}
                        </td>

                        <!-- Outstanding Grand Totals -->
                        <td class="text-right font-mono font-black text-gray-900">
                            {{ number_format($grand_total['outstanding']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-900">
                            {{ number_format($grand_total['outstanding']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-600">
                            {{ number_format($grand_total['outstanding']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-rose-900 bg-rose-100/60">
                            {{ number_format($grand_total['outstanding']['total'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Signature Footer for Official Auditing -->
        <div class="mt-8 pt-4 grid grid-cols-4 gap-4 text-center text-xs font-bold text-gray-700">
            <div>
                <div class="border-t border-gray-400 pt-1">Prepared By</div>
            </div>
            <div>
                <div class="border-t border-gray-400 pt-1">Accounts Officer</div>
            </div>
            <div>
                <div class="border-t border-gray-400 pt-1">Internal Auditor</div>
            </div>
            <div>
                <div class="border-t border-gray-400 pt-1">Principal / Head of Institute</div>
            </div>
        </div>

        <!-- Report Footnote / Disclaimer -->
        <div class="mt-3 pt-2 border-t border-gray-200 flex justify-between items-center text-[9px] text-gray-500">
            <span>Class-wise Financial Report &bull; MACS Education Management System</span>
            <span>Printed on: {{ now()->format('d M Y, h:i:s A') }}</span>
        </div>

    </div>

</body>
</html>

