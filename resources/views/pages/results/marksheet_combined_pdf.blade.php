@php
    $reportsList = isset($reports) && is_array($reports) ? $reports : [get_defined_vars()];
    $firstReport = $reportsList[0] ?? [];
    $docTitle = count($reportsList) === 1 
        ? ('Progress Report (Combined 3-Terms) - ' . ($firstReport['student']->student_identity ?? ''))
        : ('Progress Reports (Combined 3-Terms) - ' . ($firstReport['student']->schoolClass->class_name ?? 'Class'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $docTitle }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 0;
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $fontInterRegular ?? str_replace('\\', '/', public_path('fonts/Inter-Regular.ttf')) }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 600;
            src: url('{{ $fontInterSemiBold ?? str_replace('\\', '/', public_path('fonts/Inter-SemiBold.ttf')) }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 700;
            src: url('{{ $fontInterBold ?? str_replace('\\', '/', public_path('fonts/Inter-Bold.ttf')) }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 800;
            src: url('{{ $fontInterExtraBold ?? str_replace('\\', '/', public_path('fonts/Inter-ExtraBold.ttf')) }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }
        body {
            margin: 4mm 5mm !important;
            padding: 0 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0F1E2C;
            font-size: 8px;
            line-height: 1.2;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Outer Sheet Container */
        .marksheet-wrapper {
            border: 1.5px solid #008ED6;
            border-radius: 10px;
            padding: 5px 8px;
            position: relative;
            background: #ffffff;
            height: 198mm;
        }

        /* Top Color Accent Strip */
        /* .top-accent-bar {
            width: 100%;
            height: 3.5px;
            background-color: #008ED6;
            margin-bottom: 6px;
            border-radius: 2px;
        } */

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Header Layout */
        .header-container {
            margin-left: 10px;
            margin-right: 10px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
        }
        .school-name {
            font-size: 17px;
            font-weight: 800;
            color: #0F1E2C;
            letter-spacing: -0.3px;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .school-address {
            font-size: 8px;
            font-weight: 600;
            color: #475569;
            margin-top: 1px;
        }
        .school-contact {
            font-size: 7.5px;
            font-weight: 500;
            color: #64748B;
        }
        .report-badge {
            display: inline-block;
            background-color: #008ED6;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 2px 14px;
            border-radius: 20px;
            margin-top: 3px;
        }
        .exam-banner-title {
            font-size: 10px;
            font-weight: 700;
            color: #008ED6;
            margin-top: 2px;
            letter-spacing: 0.2px;
        }

        /* Grading System Mini Table - Themed Blue & Green (No Black Lines) */
        .grade-legend-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #1E293B;
            border-radius: 6px;
            overflow: hidden;
            font-size: 6.6px;
            text-align: center;
        }
        .grade-legend-table th {
            background-color: #008ED6;
            color: #ffffff;
            font-weight: 800;
            padding: 2px 2px;
            border: 1px solid #1E293B;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .grade-legend-table td {
            border: 1px solid #1E293B;
            padding: 1.2px 2px;
            font-weight: 700;
            color: #0F1E2C;
        }
        .grade-legend-table tr:nth-child(even) td {
            background-color: #F0F9FF;
        }

        /* Student Information & Photo Section - 2 Separate Equal-Height Boxes */
        .student-section-container {
            margin-left: 2px;
            margin-right: 2px;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .student-boxes-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .student-info-card-cell {
            background-color: #F8FAFC;
            border: 1.2px solid #E2E8F0;
            border-radius: 8px;
            padding: 4px 12px;
            vertical-align: middle;
        }
        .student-photo-card-cell {
            width: 54.1pt;
            border: none;
            background: transparent;
            padding: 0;
            text-align: right;
            vertical-align: middle;
        }
        .student-photo-box {
            height: 57pt;
            width: 50.1pt;
            max-width: 50.1pt;
            border: 1.5px solid #008ED6;
            border-radius: 8px;
            padding: 1px;
            background-color: #ffffff;
            display: inline-block;
            vertical-align: middle;
            box-sizing: border-box;
        }
        .student-info-table td {
            border: none;
            padding: 1.5px 3px;
            font-size: 8px;
            vertical-align: middle;
        }
        .info-label {
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.3px;
            width: 15%;
        }
        .info-val {
            font-weight: 700;
            color: #0F1E2C;
            width: 35%;
            font-size: 8.5px;
        }
        .badge-id {
            background: transparent;
            color: #008ED6;
            border: none;
            padding: 0;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
            font-size: 8.5px;
        }
        .badge-roll {
            background-color: #ECFDF5;
            color: #009A49;
            border: 1px solid #A7F3D0;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 8px;
        }

        /* Main Subject Marks Table */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5px;
            margin-bottom: 2px;
            border: 1px solid #1E293B;
            border-radius: 6px;
            overflow: hidden;
        }
        .marks-table th {
            color: #ffffff;
            font-size: 7.2px;
            font-weight: 700;
            text-align: center;
            padding: 2.5px 2px;
            border: 1px solid #1E293B;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .marks-table th.head-base {
            background-color: #0284C7;
            border-color: #0369A1;
        }
        .marks-table th.head-term1 {
            background-color: #0284C7;
            border-color: #0369A1;
        }
        .marks-table th.head-term2 {
            background-color: #0284C7;
            border-color: #0369A1;
        }
        .marks-table th.head-term3 {
            background-color: #008ED6;
            border-color: #0077B6;
        }
        .marks-table th.head-final {
            background-color: #009A49;
            border-color: #047857;
        }
        .marks-table th.sub-head {
            font-size: 6.5px;
            padding: 1.8px 1px;
            border: 1px solid #1E293B;
        }
        .marks-table th.sub-head-term1,
        .marks-table th.sub-head-term2 {
            background-color: #0284C7;
            border-color: #0369A1;
        }
        .marks-table th.sub-head-term3 {
            background-color: #0284C7;
            border-color: #0077B6;
        }
        .marks-table th.sub-head-final {
            background-color: #059669;
            border-color: #047857;
        }
        .marks-table td {
            border: 1px solid #1E293B;
            padding: 2px 1px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            color: #1E293B;
        }
        .marks-table tr:nth-child(even) td {
            background-color: #F8FAFC;
        }
        .marks-table td.sub-name-cell {
            text-align: left;
            padding-left: 5px;
            font-weight: 700;
            color: #0F1E2C;
            font-size: 10px;
        }
        .marks-table tr.total-summary-row td {
            background-color: #F1F5F9;
            font-weight: 800;
            font-size: 10px;
            color: #0F1E2C;
            border-top: 1.5px solid #1E293B;
            padding: 3px 2px;
        }

        /* Grade Text (No Background, No Border) */
        .grade-pill {
            display: inline-block;
            font-size: 10px;
            font-weight: 800;
            text-align: center;
            background: transparent;
            border: none;
            padding: 0;
        }
        .grade-a-plus, .grade-a {
            background: transparent;
            color: #009A49;
            border: none;
        }
        .grade-a-minus, .grade-b {
            background: transparent;
            color: #0284C7;
            border: none;
        }
        .grade-c, .grade-d {
            background: transparent;
            color: #D97706;
            border: none;
        }
        .grade-f {
            background: transparent;
            color: #DC2626;
            border: none;
        }

        /* Bottom Evaluation Cards (2 Columns) - Centered with 10px Margins */
        .eval-wrapper {
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 2.5px;
        }
        .eval-container {
            width: 100%;
            margin-bottom: 0;
        }
        .eval-container td {
            border: none;
            vertical-align: top;
        }
        .eval-card {
            border: 1.2px solid #E2E8F0;
            border-radius: 7px;
            background-color: #ffffff;
            padding: 4px 6px;
        }
        .eval-card-header {
            font-size: 8.8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding-bottom: 2px;
            border-bottom: 1.5px solid #F1F5F9;
            margin-bottom: 3px;
        }
        .eval-card-header.header-blue {
            color: #008ED6;
            border-bottom-color: #E0F2FE;
        }
        .eval-card-header.header-green {
            color: #009A49;
            border-bottom-color: #DCFCE7;
        }

        /* Merit Table - Enhanced Typography */
        .merit-table td, .merit-table th {
            border: 1px solid #1E293B;
            padding: 2.2px 2px;
            text-align: center;
            font-size: 8px;
        }
        .merit-table th {
            background-color: #F8FAFC;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            font-size: 8.2px;
        }
        .merit-table td {
            font-weight: 700;
            color: #0F1E2C;
            font-size: 8.5px;
        }

        /* Result Callout Box - Enhanced Typography */
        .result-highlight-table td {
            border: none;
            padding: 1.5px 2px;
            vertical-align: middle;
        }
        .cgpa-callout {
            background-color: #F0FDF4;
            border: 1.5px solid #86EFAC;
            border-radius: 7px;
            text-align: center;
            padding: 2.5px 4px;
        }
        .cgpa-callout-fail {
            background-color: #FEF2F2;
            border: 1.5px solid #FECACA;
            border-radius: 7px;
            text-align: center;
            padding: 2.5px 4px;
        }
        .cgpa-score {
            font-size: 16px;
            font-weight: 800;
            line-height: 1;
        }
        .cgpa-label {
            font-size: 7.5px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            margin-top: 1px;
        }

        /* Bottom Anchored Footer Section */
        .bottom-anchored-footer {
            position: absolute;
            bottom: 4px;
            left: 8px;
            right: 8px;
        }

        /* Signatures Section */
        .signatures-table {
            width: 100%;
            margin-top: 2px;
            margin-bottom: 1px;
        }
        .signatures-table td {
            border: none;
            text-align: center;
            vertical-align: bottom;
            font-size: 9.5px;
            font-weight: 700;
            color: #334155;
            width: 33.33%;
        }
        .sig-line {
            display: inline-block;
            width: 150px;
            border-top: 1.2px dashed #94A3B8;
            padding-top: 2.5px;
        }

        /* Footer Info */
        .footer-table {
            width: 100%;
            margin-top: 2px;
            border-top: 1px solid #E2E8F0;
            padding-top: 1.5px;
        }
        .footer-table td {
            border: none;
            font-size: 7.5px;
            color: #94A3B8;
            font-weight: 500;
        }
    </style>
</head>
<body>

@foreach($reportsList as $report)
@php
    extract($report);
@endphp
<div class="marksheet-wrapper" style="{{ !$loop->first ? 'page-break-before: always;' : '' }}">
    <!-- Top Brand Strip -->
    <div class="top-accent-bar"></div>

    <!-- Header Section (Logo, School Details, Grading System) - Aligned with 10px Margins -->
    <div class="header-container">
        <table class="header-table">
            <tr>
                <!-- Left: Logo (Enlarged) -->
                <td style="width: 20%; text-align: left; vertical-align: middle;">
                    @if(!empty($logoSrc))
                        <img src="{{ $logoSrc }}" style="width: 72px; height: 72px; object-fit: contain;" alt="MACS Logo" />
                    @else
                        <div style="width: 70px; height: 70px; border: 1.5px solid #008ED6; border-radius: 8px; text-align: center; line-height: 70px; font-weight: 800; color: #008ED6; font-size: 11px;">MACS</div>
                    @endif
                </td>

                <!-- Center: School Details & Combined Progress Report Title (60% dead center) -->
                <td style="width: 60%; text-align: center; vertical-align: middle;">
                    <div class="school-name">MACS School &amp; College</div>
                    <div class="school-address">Jalalpur, Pabna Sadar, Pabna &bull; Bangladesh</div>
                    <div class="school-contact">Hotline: 01896-220299, 01896-220300 &bull; Web: macs.edu.bd</div>
                    <div class="report-badge">Academic Progress Report (Combined 3-Terms)</div>
                    <div class="exam-banner-title">Final Consolidated Evaluation &bull; Academic Session: {{ $sessionYear->session_name }}</div>
                </td>

                <!-- Right: GPA Grading System Card (Themed, No Black Lines) -->
            <td style="width: 20%; text-align: right; vertical-align: middle;">
                <table class="grade-legend-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Marks</th>
                            <th style="width: 16%;">LG</th>
                            <th style="width: 16%;">GP</th>
                            <th style="width: 38%;">Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>80-100</td>
                            <td style="color: #009A49; font-weight: 800;">A+</td>
                            <td>5.0</td>
                            <td>Outstanding</td>
                        </tr>
                        <tr>
                            <td>70-79</td>
                            <td style="color: #009A49; font-weight: 800;">A</td>
                            <td>4.0</td>
                            <td>Very Good</td>
                        </tr>
                        <tr>
                            <td>60-69</td>
                            <td style="color: #0284C7; font-weight: 800;">A-</td>
                            <td>3.5</td>
                            <td>Good</td>
                        </tr>
                        <tr>
                            <td>50-59</td>
                            <td style="color: #0284C7; font-weight: 800;">B</td>
                            <td>3.0</td>
                            <td>Average</td>
                        </tr>
                        <tr>
                            <td>40-49</td>
                            <td style="color: #D97706; font-weight: 800;">C</td>
                            <td>2.0</td>
                            <td>Pass</td>
                        </tr>
                        <tr>
                            <td>33-39</td>
                            <td style="color: #D97706; font-weight: 800;">D</td>
                            <td>1.0</td>
                            <td>Poor</td>
                        </tr>
                        <tr>
                            <td>00-32</td>
                            <td style="color: #DC2626; font-weight: 800;">F</td>
                            <td>0.0</td>
                            <td>Fail</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    </div>

    <!-- Student Information & Photo Section - 2 Separate Equal-Height Boxes -->
    <div class="student-section-container">
        <table class="student-boxes-table">
            <tr>
                <!-- Box 1 (Left): Student Information -->
                <td class="student-info-card-cell">
                    <table class="student-info-table" style="width: 100%; border-collapse: collapse; border: none;">
                        <tr>
                            <td class="info-label">Student ID</td>
                            <td class="info-val"><span class="badge-id">{{ $student->student_identity }}</span></td>
                            <td class="info-label">Class &amp; Section</td>
                            <td class="info-val">{{ $student->schoolClass->class_name ?? 'N/A' }} ({{ $student->section->section_name ?? 'A' }})</td>
                        </tr>
                        <tr>
                            <td class="info-label">Student Name</td>
                            <td class="info-val" style="color: #008ED6; font-size: 9px; font-weight: 800;">{{ $student->student_name ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</td>
                            <td class="info-label">Roll Number</td>
                            <td class="info-val"><span class="badge-roll">{{ $student->roll_number }}</span></td>
                        </tr>
                        <tr>
                            <td class="info-label">Father's Name</td>
                            <td class="info-val">{{ $student->father_name ?? '-' }}</td>
                            <td class="info-label">Shift</td>
                            <td class="info-val">
                                @php
                                    $shiftRaw = strtolower($student->shift->shift_name ?? '');
                                    $shiftText = str_contains($shiftRaw, 'day') ? 'Day' : (str_contains($shiftRaw, 'morning') ? 'Morning' : ($student->shift->shift_name ?? 'Day'));
                                @endphp
                                {{ $shiftText }}
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Mother's Name</td>
                            <td class="info-val">{{ $student->mother_name ?? '-' }}</td>
                            <td class="info-label">Campus Branch</td>
                            <td class="info-val">{{ $student->branch->branch_name ?? 'Main Campus' }}</td>
                        </tr>
                    </table>
                </td>

                <!-- Box 2 (Right): Student Photo (Image Box with Same Height) -->
                <td class="student-photo-card-cell">
                    @if(!empty($photoSrc))
                        <img src="{{ $photoSrc }}" class="student-photo-box" alt="Student Photo" />
                    @else
                        <div class="student-photo-box" style="width: 50.1pt; height: 57pt; text-align: center; line-height: 57pt; font-size: 8px; color: #94A3B8; border-color: #CBD5E1;">Photo</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Main Academic Performance Multi-Term Table -->
    <table class="marks-table">
        <thead>
            <tr>
                <th rowspan="2" class="head-base" style="width: 2%;">#</th>
                <th rowspan="2" class="head-base" style="width: 16%; text-align: left; padding-left: 6px;">Subject Name</th>
                <th rowspan="2" class="head-base" style="width: 4%;">Full</th>
                <th colspan="4" class="head-term1" style="width: 16%;">1st Term Exam</th>
                <th colspan="4" class="head-term2" style="width: 16%;">2nd Term Exam</th>
                <th colspan="7" class="head-term3" style="width: 24%;">3rd Term (Final Exam)</th>
                <th colspan="4" class="head-final" style="width: 22%;">Final Combined (100%)</th>
            </tr>
            <tr>
                <!-- 1st Term (Blue Palette) -->
                <th class="sub-head sub-head-term1" style="width: 4%;">CT</th>
                <th class="sub-head sub-head-term1" style="width: 4%;">MT</th>
                <th class="sub-head sub-head-term1" style="width: 4%;">Term</th>
                <th class="sub-head sub-head-term1" style="width: 4%; background-color: #0369A1; font-weight: 800;">Total</th>

                <!-- 2nd Term (Blue Palette) -->
                <th class="sub-head sub-head-term2" style="width: 4%;">CT</th>
                <th class="sub-head sub-head-term2" style="width: 4%;">MT</th>
                <th class="sub-head sub-head-term2" style="width: 4%;">Term</th>
                <th class="sub-head sub-head-term2" style="width: 4%; background-color: #0369A1; font-weight: 800;">Total</th>

                <!-- 3rd Term (MACS Sky Blue Palette) -->
                <th class="sub-head sub-head-term3" style="width: 3.5%;">CT</th>
                <th class="sub-head sub-head-term3" style="width: 3.5%;">MT</th>
                <th class="sub-head sub-head-term3" style="width: 3.5%;">Term</th>
                <th class="sub-head sub-head-term3" style="width: 3.5%; background-color: #0077B6; font-weight: 800;">Total</th>
                <th class="sub-head sub-head-term3" style="width: 3%;">LG</th>
                <th class="sub-head sub-head-term3" style="width: 3.5%;">GP</th>
                <th class="sub-head sub-head-term3" style="width: 3.5%;">Top</th>

                <!-- Final Combined (MACS Green Palette) -->
                <th class="sub-head sub-head-final" style="width: 6%; background-color: #047857; font-weight: 800;">Total</th>
                <th class="sub-head sub-head-final" style="width: 5%;">LG</th>
                <th class="sub-head sub-head-final" style="width: 5%;">GP</th>
                <th class="sub-head sub-head-final" style="width: 6%;">Top</th>
            </tr>
        </thead>
        <tbody>
            @php $grandFullMarks = 0; @endphp
            @foreach($combinedSubjectResults as $idx => $r)
                @php
                    $grandFullMarks += ($r['full_marks'] * 3);
                    $t3_lg = $r['term3']['letter_grade'];
                    $t3_class = match($t3_lg) {
                        'A+' => 'grade-a-plus',
                        'A'  => 'grade-a',
                        'A-' => 'grade-a-minus',
                        'B'  => 'grade-b',
                        'C'  => 'grade-c',
                        'D'  => 'grade-d',
                        default => 'grade-f',
                    };
                    $fin_lg = $r['final']['letter_grade'];
                    $fin_class = match($fin_lg) {
                        'A+' => 'grade-a-plus',
                        'A'  => 'grade-a',
                        'A-' => 'grade-a-minus',
                        'B'  => 'grade-b',
                        'C'  => 'grade-c',
                        'D'  => 'grade-d',
                        default => 'grade-f',
                    };
                @endphp
                <tr>
                    <td style="color: #64748B; font-weight: 700;">{{ $idx + 1 }}</td>
                    <td class="sub-name-cell">{{ $r['subject_name'] }}</td>
                    <td style="font-weight: 700;">{{ $r['full_marks'] }}</td>

                    <!-- Term 1 -->
                    <td>{{ number_format($r['term1']['ct'], 1) }}</td>
                    <td>{{ number_format($r['term1']['mt'], 1) }}</td>
                    <td>{{ number_format($r['term1']['terminal'], 1) }}</td>
                    <td style="font-weight: 700; color: #0284C7;">{{ number_format($r['term1']['total'], 1) }}</td>

                    <!-- Term 2 -->
                    <td>{{ number_format($r['term2']['ct'], 1) }}</td>
                    <td>{{ number_format($r['term2']['mt'], 1) }}</td>
                    <td>{{ number_format($r['term2']['terminal'], 1) }}</td>
                    <td style="font-weight: 700; color: #0284C7;">{{ number_format($r['term2']['total'], 1) }}</td>

                    <!-- Term 3 -->
                    <td>{{ number_format($r['term3']['ct'], 1) }}</td>
                    <td>{{ number_format($r['term3']['mt'], 1) }}</td>
                    <td>{{ number_format($r['term3']['terminal'], 1) }}</td>
                    <td style="font-weight: 700; color: #008ED6;">{{ number_format($r['term3']['total'], 1) }}</td>
                    <td><span class="grade-pill {{ $t3_class }}">{{ $t3_lg }}</span></td>
                    <td style="font-weight: 700;">{{ number_format($r['term3']['grade_point'], 1) }}</td>
                    <td style="color: #64748B;">{{ number_format($r['term3']['top_mark'], 1) }}</td>

                    <!-- Final Combined -->
                    <td style="font-weight: 800; color: #009A49;">{{ number_format($r['final']['total'], 1) }}</td>
                    <td><span class="grade-pill {{ $fin_class }}">{{ $fin_lg }}</span></td>
                    <td style="font-weight: 800; {{ $fin_lg === 'F' ? 'color: #DC2626;' : 'color: #009A49;' }}">
                        {{ number_format($r['final']['grade_point'], 2) }}
                    </td>
                    <td style="color: #047857; font-weight: 700;">{{ number_format($r['final']['top_mark'], 1) }}</td>
                </tr>
            @endforeach

            <!-- Total Marks Summary Row -->
            <tr class="total-summary-row">
                <td colspan="2" style="text-align: left; padding-left: 6px;">GRAND TOTAL &amp; PERFORMANCE</td>
                <td>{{ $grandFullMarks }}</td>
                <td colspan="4" style="text-align: right; color: #64748B; font-size: 7.5px;">Term 1 Total</td>
                <td colspan="4" style="text-align: right; color: #64748B; font-size: 7.5px;">Term 2 Total</td>
                <td colspan="7" style="text-align: right; color: #008ED6; font-size: 7.5px;">Term 3 Final</td>
                <td style="font-weight: 800; color: #009A49; font-size: 10px;">{{ number_format($grandTotalMarks, 1) }}</td>
                <td><span class="grade-pill {{ $finalGrade === 'F' ? 'grade-f' : 'grade-a' }}">{{ $finalGrade }}</span></td>
                <td style="font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; font-size: 10px;">{{ number_format($cgpa, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Evaluation Section: 2 Columns (Multi-term Merit & Final Result Showcase) - Centered with 10px Margins -->
    <div class="eval-wrapper">
        <table class="eval-container">
            <tr>
                <!-- Left: Multi-term Merit & Attendance -->
                <td style="width: 54%; padding-right: 5px;">
                    <div class="eval-card">
                        <div class="eval-card-header header-blue">Term-wise Academic Merit Standing &amp; Attendance</div>
                        <table class="merit-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="width: 32%; text-align: left; padding-left: 5px;">Exam Name</th>
                                    <th colspan="3" style="background-color: #EFF6FF; color: #0284C7;">Merit Position</th>
                                    <th colspan="3" style="background-color: #F0FDF4; color: #009A49;">Attendance Details</th>
                                </tr>
                                <tr>
                                    <th style="width: 11%;">Section</th>
                                    <th style="width: 11%;">Shift</th>
                                    <th style="width: 12%;">Class</th>
                                    <th style="width: 11%;">Working</th>
                                    <th style="width: 11%;">Present</th>
                                    <th style="width: 12%;">Absent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($examMerits as $em)
                                    <tr>
                                        <td style="text-align: left; padding-left: 5px; font-weight: 700; color: #0F1E2C; font-size: 8.5px;">{{ $em['exam_name'] }}</td>
                                        <td style="font-weight: 800; color: #008ED6; font-size: 9px;">{{ $em['section_wise'] }}</td>
                                        <td style="font-weight: 800; color: #008ED6; font-size: 9px;">{{ $em['shift_wise'] }}</td>
                                        <td style="font-weight: 800; color: #008ED6; font-size: 9px;">{{ $em['class_wise'] }}</td>
                                        <td style="font-size: 8.5px; font-weight: 700;">{{ $em['working_days'] ?: '-' }}</td>
                                        <td style="color: #009A49; font-weight: 800; font-size: 8.5px;">{{ $em['present'] ?: '-' }}</td>
                                        <td style="color: #DC2626; font-weight: 800; font-size: 8.5px;">{{ $em['absent'] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div style="font-size: 7.5px; color: #64748B; margin-top: 3.5px; font-weight: 500;">
                            Class Enrolment: <strong style="color: #0F1E2C;">{{ $totalClassStudents }} Students</strong> &bull; Multi-term rankings computed on cumulative GPA
                        </div>
                    </div>
                </td>

                <!-- Right: Final Combined Evaluation & Remarks -->
                <td style="width: 46%; padding-left: 5px;">
                    <div class="eval-card">
                        <div class="eval-card-header header-green">Cumulative Annual Evaluation &amp; Result</div>
                        <table class="result-highlight-table" style="width: 100%;">
                            <tr>
                                <!-- Cumulative CGPA Callout -->
                                <td style="width: 32%;">
                                    <div class="{{ $finalGrade === 'F' ? 'cgpa-callout-fail' : 'cgpa-callout' }}">
                                        <div class="cgpa-score" style="color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }};">
                                            {{ number_format($cgpa, 2) }}
                                        </div>
                                        <div class="cgpa-label">Cumulative GPA</div>
                                    </div>
                                </td>

                                <!-- Letter Grade Callout -->
                                <td style="width: 28%; text-align: center;">
                                    <div style="background-color: #F8FAFC; border: 1.2px solid #E2E8F0; border-radius: 7px; padding: 3px 4px;">
                                        <div style="font-size: 16px; font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; line-height: 1;">
                                            {{ $finalGrade }}
                                        </div>
                                        <div class="cgpa-label">Final Grade</div>
                                    </div>
                                </td>

                                <!-- Result Status & Remarks -->
                                <td style="width: 40%; padding-left: 5px;">
                                    <div style="font-size: 7.8px; font-weight: 700; color: #64748B; text-transform: uppercase;">Status:</div>
                                    <div style="font-size: 11px; font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; line-height: 1.1;">
                                        {{ $finalGrade === 'F' ? 'FAILED' : 'PASSED' }}
                                    </div>
                                    <div style="font-size: 7.8px; font-weight: 700; color: #64748B; margin-top: 2px; text-transform: uppercase;">Remarks:</div>
                                    <div style="font-size: 9px; font-weight: 700; color: #0F1E2C;">
                                        {{ $remark }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Bottom Anchored Footer Section -->
    <div class="bottom-anchored-footer">
        <!-- Teacher & Authority Signatures Section -->
        <table class="signatures-table">
            <tr>
                <td>
                    <div class="sig-line">Class Teacher's Signature</div>
                </td>
                <td>
                    <div class="sig-line">Guardian's Signature</div>
                </td>
                <td>
                    @if(!empty($signatureSrc))
                        <div style="margin-bottom: 2px;">
                            <img src="{{ $signatureSrc }}" style="height: 44px; object-fit: contain;" alt="Signature" />
                        </div>
                    @else
                        <div style="height: 44px;"></div>
                    @endif
                    <div class="sig-line">Principal's Signature</div>
                </td>
            </tr>
        </table>

        <!-- Subtle Footer Information with CodeNext IT Branding -->
        <table class="footer-table">
            <tr>
                <td style="text-align: left;">
                    Developed by <strong style="color: #64748B;">CodeNext IT</strong> | codenextit.com
                </td>
                <td style="text-align: right;">
                    Date of Issue: {{ date('d M, Y') }}
                </td>
            </tr>
        </table>
    </div>
</div>
@endforeach

</body>
</html>
