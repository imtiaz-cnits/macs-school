<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Class-wise Financial Report - {{ $session_name }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 6mm 8mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #111827;
            font-size: 8.5px;
            line-height: 1.2;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #111827;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .school-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
            margin: 0;
            line-height: 1.1;
        }
        .school-info {
            font-size: 8px;
            color: #4b5563;
            margin: 2px 0 0 0;
        }
        .report-title-badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            background: #f3f4f6;
            border: 1px solid #9ca3af;
            padding: 1px 5px;
            border-radius: 2px;
        }
        .report-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 2px 0 0 0;
            color: #111827;
        }
        .report-meta {
            font-size: 8px;
            font-weight: bold;
            color: #374151;
            margin-top: 2px;
        }

        /* Summary Cards Table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: center;
            border-radius: 4px;
        }
        .summary-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-val {
            font-size: 11px;
            font-weight: bold;
            margin-top: 2px;
            font-family: 'Courier New', Courier, monospace;
        }

        /* Main Data Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .data-table th, .data-table td {
            border: 0.75px solid #9ca3af;
            padding: 3px 4px;
            font-size: 8px;
        }
        .data-table thead th {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 7.5px;
        }
        .bg-head-due {
            background-color: #eff6ff;
            color: #1e40af;
        }
        .bg-head-collected {
            background-color: #ecfdf5;
            color: #065f46;
        }
        .bg-head-outstanding {
            background-color: #fff1f2;
            color: #9f1239;
        }
        .bg-total-col {
            background-color: #f9fafb;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .grand-total-row {
            background-color: #f3f4f6;
            font-weight: bold;
            border-top: 1.5px solid #111827;
            border-bottom: 2px solid #111827;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }
        .signatures-table td {
            width: 25%;
            text-align: center;
            vertical-align: bottom;
            font-size: 8px;
            font-weight: bold;
            border: none;
            padding: 0 10px;
        }
        .sig-line {
            border-top: 0.75px solid #4b5563;
            padding-top: 3px;
        }
        .footer-note {
            margin-top: 10px;
            border-top: 0.5px solid #d1d5db;
            padding-top: 3px;
            font-size: 7px;
            color: #6b7280;
        }
    </style>
</head>
<body>

    @php
        $logoSrc = null;
        if (file_exists(public_path('img/macs_logo.jpeg'))) {
            $logoSrc = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(public_path('img/macs_logo.jpeg')));
        }
    @endphp

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 8%; text-align: left;">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" style="height: 48px; width: auto;" alt="Logo">
                @endif
            </td>
            <td style="width: 52%; text-align: left; padding-left: 6px;">
                <div class="school-name">MACS School & College</div>
                <div class="school-info">Jalalpur, Pabna Sadar, Pabna | Phone: 01896-220299, 01896-220300</div>
                <div class="school-info">Email: contact@macsschool.edu.bd</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="report-title-badge">Accounts & Finance Division</div>
                <div class="report-title">Class-wise Financial Report</div>
                <div class="report-meta">
                    Session: {{ $session_name }} &bull; Period: {{ $period_label }}
                </div>
                <div class="school-info" style="font-size: 7px; color: #6b7280;">
                    Generated: {{ $generated_at }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Summary Box -->
    <table class="summary-table">
        <tr>
            <td style="width: 33.33%; background: #eff6ff;">
                <div class="summary-label" style="color: #1e40af;">Total Due</div>
                <div class="summary-val" style="color: #1e3a8a;">Tk {{ number_format($total_due, 2) }}</div>
            </td>
            <td style="width: 33.33%; background: #ecfdf5;">
                <div class="summary-label" style="color: #065f46;">Total Collected</div>
                <div class="summary-val" style="color: #064e3b;">Tk {{ number_format($total_collected, 2) }}</div>
            </td>
            <td style="width: 33.34%; background: #fff1f2;">
                <div class="summary-label" style="color: #9f1239;">Total Outstanding</div>
                <div class="summary-val" style="color: #881337;">Tk {{ number_format($total_outstanding, 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Main Data Table: Exact 13 Columns -->
    <table class="data-table">
        <thead>
            <!-- Grouped Header Row 1 -->
            <tr>
                <th rowspan="2" class="text-left" style="width: 95px; vertical-align: middle; background-color: #f3f4f6;">Class</th>
                <th colspan="4" class="text-center bg-head-due">Due</th>
                <th colspan="4" class="text-center bg-head-collected">Collected</th>
                <th colspan="4" class="text-center bg-head-outstanding">Outstanding</th>
            </tr>
            <!-- Sub-column Header Row 2 -->
            <tr>
                <!-- Due -->
                <th class="text-right bg-head-due">Monthly Fee</th>
                <th class="text-right bg-head-due">Other Fees</th>
                <th class="text-right bg-head-due">Other</th>
                <th class="text-right bg-head-due" style="font-weight: 900;">Total</th>

                <!-- Collected -->
                <th class="text-right bg-head-collected">Monthly Fee</th>
                <th class="text-right bg-head-collected">Other Fees</th>
                <th class="text-right bg-head-collected">Other</th>
                <th class="text-right bg-head-collected" style="font-weight: 900;">Total</th>

                <!-- Outstanding -->
                <th class="text-right bg-head-outstanding">Monthly Fee</th>
                <th class="text-right bg-head-outstanding">Other Fees</th>
                <th class="text-right bg-head-outstanding">Other</th>
                <th class="text-right bg-head-outstanding" style="font-weight: 900;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <!-- Class in Academic Order -->
                <td class="text-left" style="font-weight: bold;">{{ $r['class_name'] }}</td>

                <!-- Due -->
                <td class="text-right font-mono">{{ number_format($r['due']['monthly_fee'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($r['due']['other_fees'], 2) }}</td>
                <td class="text-right font-mono" style="color: #6b7280;">{{ number_format($r['due']['other'], 2) }}</td>
                <td class="text-right font-mono bg-total-col" style="color: #1e40af;">{{ number_format($r['due']['total'], 2) }}</td>

                <!-- Collected -->
                <td class="text-right font-mono">{{ number_format($r['collected']['monthly_fee'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($r['collected']['other_fees'], 2) }}</td>
                <td class="text-right font-mono" style="color: #6b7280;">{{ number_format($r['collected']['other'], 2) }}</td>
                <td class="text-right font-mono bg-total-col" style="color: #065f46;">{{ number_format($r['collected']['total'], 2) }}</td>

                <!-- Outstanding -->
                <td class="text-right font-mono">{{ number_format($r['outstanding']['monthly_fee'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($r['outstanding']['other_fees'], 2) }}</td>
                <td class="text-right font-mono" style="color: #6b7280;">{{ number_format($r['outstanding']['other'], 2) }}</td>
                <td class="text-right font-mono bg-total-col" style="color: #9f1239;">{{ number_format($r['outstanding']['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <!-- Grand Total Row -->
            <tr class="grand-total-row">
                <td class="text-left" style="font-weight: 900; text-transform: uppercase;">Grand Total</td>

                <!-- Due Grand Total -->
                <td class="text-right font-mono">{{ number_format($grand_total['due']['monthly_fee'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($grand_total['due']['other_fees'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($grand_total['due']['other'], 2) }}</td>
                <td class="text-right font-mono" style="color: #1e3a8a; font-weight: 900;">{{ number_format($grand_total['due']['total'], 2) }}</td>

                <!-- Collected Grand Total -->
                <td class="text-right font-mono">{{ number_format($grand_total['collected']['monthly_fee'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($grand_total['collected']['other_fees'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($grand_total['collected']['other'], 2) }}</td>
                <td class="text-right font-mono" style="color: #064e3b; font-weight: 900;">{{ number_format($grand_total['collected']['total'], 2) }}</td>

                <!-- Outstanding Grand Total -->
                <td class="text-right font-mono">{{ number_format($grand_total['outstanding']['monthly_fee'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($grand_total['outstanding']['other_fees'], 2) }}</td>
                <td class="text-right font-mono">{{ number_format($grand_total['outstanding']['other'], 2) }}</td>
                <td class="text-right font-mono" style="color: #881337; font-weight: 900;">{{ number_format($grand_total['outstanding']['total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Block -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-line">Prepared By</div>
            </td>
            <td>
                <div class="sig-line">Accounts Officer</div>
            </td>
            <td>
                <div class="sig-line">Internal Auditor</div>
            </td>
            <td>
                <div class="sig-line">Principal / Head of Institute</div>
            </td>
        </tr>
    </table>

    <!-- Footer Note -->
    <table style="width: 100%; margin-top: 8px;">
        <tr>
            <td class="footer-note" style="text-align: left; width: 60%;">
                Class-wise Financial Report &bull; MACS Education Management System
            </td>
            <td class="footer-note" style="text-align: right; width: 40%;">
                Printed on: {{ now()->format('d M Y, h:i:s A') }}
            </td>
        </tr>
    </table>

</body>
</html>

