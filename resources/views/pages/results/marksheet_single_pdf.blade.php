@php
    $reportsList = isset($reports) && is_array($reports) ? $reports : [get_defined_vars()];
    $firstReport = $reportsList[0] ?? [];
    $docTitle = count($reportsList) === 1 
        ? ('Academic Progress Report - ' . ($firstReport['student']->student_identity ?? ''))
        : ('Academic Progress Reports - ' . ($firstReport['student']->schoolClass->class_name ?? 'Class'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>{{ $docTitle }}</title>
    <style>
        @page {
            size: a4 portrait;
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
            margin: 5mm 6mm !important;
            padding: 0 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0F1E2C;
            font-size: 8.5px;
            line-height: 1.25;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Outer Sheet Container - Full Page Height with Slim Margins */
        .marksheet-wrapper {
            border: 2px solid #008ED6;
            border-radius: 12px;
            padding: 10px 14px 8px 14px;
            background: #ffffff;
            height: 278mm;
            position: relative;
        }

        /* Top Brand Strip */
        /* .top-accent-bar {
            width: 100%;
            height: 3.5px;
            background-color: #008ED6;
            margin-bottom: 7px;
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
            font-size: 22px;
            font-weight: 800;
            color: #0F1E2C;
            letter-spacing: -0.3px;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .school-address {
            font-size: 9px;
            font-weight: 600;
            color: #475569;
            margin-top: 1px;
        }
        .school-contact {
            font-family: 'Inter' !important;
            font-size: 9px;
            font-weight: 500;
            color: #64748B;
            margin-top: 1px;
        }
        .report-badge {
            display: inline-block;
            background-color: #008ED6;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 2.5px 14px;
            border-radius: 20px;
            margin-top: 4px;
        }
        .exam-banner-title {
            font-size: 10px;
            font-weight: 700;
            color: #008ED6;
            margin-top: 2.5px;
            letter-spacing: 0.2px;
        }

        /* Grading System Mini Table - Themed Blue & Green (No Black Lines) */
        .grade-legend-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #1E293B;
            border-radius: 6px;
            overflow: hidden;
            font-size: 6.8px;
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
            margin-top: 6px;
            margin-bottom: 6px;
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
            padding: 6px 10px;
            vertical-align: middle;
        }
        .student-photo-card-cell {
            width: 85px;
            border: none;
            background: transparent;
            padding: 0;
            text-align: right;
            vertical-align: middle;
        }
        .student-photo-box {
            height: 72.5pt;
            width: auto;
            max-width: 85px;
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
            padding: 3px 3px;
            font-size: 8.5px;
            vertical-align: middle;
        }
        .info-label {
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            font-size: 7.2px;
            letter-spacing: 0.3px;
            width: 16%;
        }
        .info-val {
            font-weight: 700;
            color: #0F1E2C;
            width: 34%;
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

        /* Main Subject Marks Table - Rebalanced Columns */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 5px;
            border: 1.2px solid #1E293B;
            border-radius: 7px;
            overflow: hidden;
        }
        .marks-table th {
            background-color: #008ED6;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            padding: 4px 2px;
            border: 1px solid #1E293B;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .marks-table th.sub-head {
            background-color: #0277B5;
            font-size: 7px;
            padding: 2px 2px;
            border: 1px solid #1E293B;
        }
        .marks-table td {
            border: 1px solid #1E293B;
            padding: 5px 3px;
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
            padding-left: 6px;
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
            padding: 5px 3px;
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
            margin-bottom: 6px;
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
            border-radius: 8px;
            background-color: #ffffff;
            padding: 6px 8px;
        }
        .eval-card-header {
            font-size: 8.8px;
            font-weight: 800;
            color: #0F1E2C;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding-bottom: 2.5px;
            border-bottom: 1.5px solid #F1F5F9;
            margin-bottom: 4px;
            text-align: center;
        }
        .eval-card-header.header-blue {
            color: #008ED6;
            border-bottom-color: #E0F2FE;
        }
        .eval-card-header.header-green {
            color: #009A49;
            border-bottom-color: #DCFCE7;
        }

        /* Merit & Attendance Box - Enhanced Typography */
        .merit-table td, .merit-table th {
            border: 1px solid #1E293B;
            padding: 2.5px 2px;
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
            padding: 2px 2px;
            vertical-align: middle;
        }
        .cgpa-callout {
            background-color: #F0FDF4;
            border: 1.5px solid #86EFAC;
            border-radius: 7px;
            text-align: center;
            padding: 3px 4px;
        }
        .cgpa-callout-fail {
            background-color: #FEF2F2;
            border: 1.5px solid #FECACA;
            border-radius: 7px;
            text-align: center;
            padding: 3px 4px;
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

        /* Observations / Teacher Remarks Box - Aligned with 10px Margins */
        .remarks-box {
            background-color: #F8FAFC;
            border: 1.2px solid #E2E8F0;
            border-radius: 8px;
            padding: 6px 10px;
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 7px;
        }
        .remarks-title {
            font-size: 8.2px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .remarks-text {
            font-size: 8.8px;
            font-weight: 600;
            color: #0F1E2C;
            line-height: 1.25;
        }

        /* Bottom Anchored Footer Section */
        .bottom-anchored-footer {
            position: absolute;
            bottom: 8px;
            left: 14px;
            right: 14px;
        }

        /* Signatures Section */
        .signatures-table {
            width: 100%;
            margin-top: 0px;
            margin-bottom: 3px;
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
            padding-top: 3px;
        }

        /* Footer Info */
        .footer-table {
            width: 100%;
            margin-top: 4px;
            border-top: 1px solid #E2E8F0;
            padding-top: 3px;
        }
        .footer-table td {
            border: none;
            font-size: 7.8px;
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
                <td style="width: 16%; text-align: left;">
                    @if(!empty($logoSrc))
                        <img src="{{ $logoSrc }}" style="width: 74px; height: 74px; object-fit: contain;" alt="MACS Logo" />
                    @else
                        <div style="width: 72px; height: 72px; border: 1.5px solid #008ED6; border-radius: 8px; text-align: center; line-height: 72px; font-weight: 800; color: #008ED6; font-size: 11px;">MACS</div>
                    @endif
                </td>

                <!-- Center: School Details & Progress Report Title -->
                <td style="width: 56%; text-align: center;">
                    <div class="school-name">MACS School &amp; College</div>
                    <div class="school-address">Jalalpur, Pabna Sadar, Pabna &bull; Bangladesh</div>
                    <div class="school-contact">Hotline: 01896-220299, 01896-220300 &bull; Web: macs.edu.bd</div>
                    <div class="report-badge">Academic Progress Report</div>
                    <div class="exam-banner-title">
                        @if(str_contains($exam->name, (string)$sessionYear->session_name))
                            {{ $exam->name }}
                        @else
                            {{ $exam->name }} - {{ $sessionYear->session_name }}
                        @endif
                    </div>
                </td>

                <!-- Right: GPA Grading System Card (Themed, No Black Lines) -->
                <td style="width: 28%; text-align: right;">
                <table class="grade-legend-table">
                    <thead>
                        <tr>
                            <th>Marks</th>
                            <th>LG</th>
                            <th>GP</th>
                            <th>Remark</th>
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
                            <td class="info-val" style="color: #008ED6; font-size: 9.5px; font-weight: 800;">{{ $student->student_name ?: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</td>
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
                        <div class="student-photo-box" style="width: 72.5pt; text-align: center; line-height: 72.5pt; font-size: 8px; color: #94A3B8; border-color: #CBD5E1;">Photo</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Main Academic Performance Table -->
    <table class="marks-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 4%;">#</th>
                <th rowspan="2" style="width: 26%; text-align: left; padding-left: 6px;">Subject Name</th>
                <th rowspan="2" style="width: 9%;">Full Marks</th>
                <th colspan="4" style="width: 37%;">Continuous &amp; Terminal Assessment</th>
                <th rowspan="2" style="width: 10%;">Class Top</th>
                <th rowspan="2" style="width: 7%;">Grade</th>
                <th rowspan="2" style="width: 7%;">Point</th>
            </tr>
            <tr>
                <th class="sub-head" style="width: 9%;">CT (10)</th>
                <th class="sub-head" style="width: 9%;">MT (20)</th>
                <th class="sub-head" style="width: 10%;">Terminal (70)</th>
                <th class="sub-head" style="width: 9%; background-color: #005E8A;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $maxTotalPossible = 0; @endphp
            @foreach($subjectResults as $index => $res)
                @php 
                    $maxTotalPossible += $res['full_marks'];
                    $lg = $res['letter_grade'];
                    $gradeClass = match($lg) {
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
                    <td style="color: #64748B; font-weight: 700;">{{ $index + 1 }}</td>
                    <td class="sub-name-cell">{{ $res['subject_name'] }}</td>
                    <td style="font-weight: 700;">{{ $res['full_marks'] }}</td>
                    <td>{{ number_format($res['ct_mark'], 2) }}</td>
                    <td>{{ number_format($res['mcq_mark'], 2) }}</td>
                    <td>{{ number_format($res['written_mark'], 2) }}</td>
                    <td style="font-weight: 800; color: #008ED6;">{{ number_format($res['total_mark'], 2) }}</td>
                    <td style="color: #0284C7; font-weight: 700;">{{ number_format($res['top_mark'], 2) }}</td>
                    <td>
                        <span class="grade-pill {{ $gradeClass }}">{{ $lg }}</span>
                    </td>
                    <td style="font-weight: 800; {{ $lg === 'F' ? 'color: #DC2626;' : 'color: #0F1E2C;' }}">
                        {{ number_format($res['grade_point'], 2) }}
                    </td>
                </tr>
            @endforeach

            <!-- Total Marks Summary Row -->
            <tr class="total-summary-row">
                <td colspan="2" style="text-align: left; padding-left: 6px;">GRAND TOTAL &amp; PERFORMANCE</td>
                <td style="font-weight: 800;">{{ $maxTotalPossible }}</td>
                <td colspan="3" style="text-align: right; color: #64748B; font-size: 9px;">Marks Obtained:</td>
                <td style="font-weight: 800; color: #008ED6; font-size: 10px;">{{ number_format($totalMarks, 2) }}</td>
                <td style="color: #64748B; font-size: 9px;">GPA:</td>
                <td>
                    <span class="grade-pill {{ $finalGrade === 'F' ? 'grade-f' : 'grade-a' }}">
                        {{ $finalGrade }}
                    </span>
                </td>
                <td style="font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; font-size: 10px;">
                    {{ number_format($cgpa, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Evaluation Section: 2 Columns (Merit & Attendance + Final Overall Status) - Centered with 10px Margins -->
    <div class="eval-wrapper">
        <table class="eval-container">
            <tr>
                <!-- Left: Merit & Attendance -->
                <td style="width: 50%; padding-right: 3px;">
                    <div class="eval-card">
                        <div class="eval-card-header header-blue">Academic Merit &amp; Attendance Record</div>
                        <table class="merit-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th colspan="3" style="background-color: #EFF6FF; color: #0284C7;">Merit Position</th>
                                    <th colspan="3" style="background-color: #F0FDF4; color: #009A49;">Attendance Details</th>
                                </tr>
                                <tr>
                                    <th style="width: 16%;">Section</th>
                                    <th style="width: 16%;">Shift</th>
                                    <th style="width: 18%;">Class</th>
                                    <th style="width: 16%;">Working</th>
                                    <th style="width: 17%;">Present</th>
                                    <th style="width: 17%;">Absent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="font-weight: 800; color: #008ED6; font-size: 9.5px;">{{ $meritPosition['section_wise'] }}</td>
                                    <td style="font-weight: 800; color: #008ED6; font-size: 9.5px;">{{ $meritPosition['shift_wise'] }}</td>
                                    <td style="font-weight: 800; color: #008ED6; font-size: 9.5px;">{{ $meritPosition['class_wise'] }}</td>
                                    <td style="font-size: 9px; font-weight: 700;">{{ $attendance['working_days'] }}</td>
                                    <td style="color: #009A49; font-weight: 800; font-size: 9px;">{{ $attendance['present'] }}</td>
                                    <td style="color: #DC2626; font-weight: 800; font-size: 9px;">{{ $attendance['absent'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="font-size: 7.5px; color: #64748B; margin-top: 3.5px; font-weight: 500;">
                            Class Enrolment: <strong style="color: #0F1E2C;">{{ $totalClassStudents }} Students</strong> &bull; Ranking on GPA &amp; Total Marks
                        </div>
                    </div>
                </td>

                <!-- Right: Final Result & GPA Showcase -->
                <td style="width: 50%; padding-left: 3px;">
                    <div class="eval-card">
                        <div class="eval-card-header header-green">Final Evaluation &amp; Remarks</div>
                        <table class="result-highlight-table" style="width: 100%;">
                            <tr>
                                <!-- GPA Callout -->
                                <td style="width: 32%;">
                                    <div class="{{ $finalGrade === 'F' ? 'cgpa-callout-fail' : 'cgpa-callout' }}">
                                        <div class="cgpa-score" style="color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }};">
                                            {{ number_format($cgpa, 2) }}
                                        </div>
                                        <div class="cgpa-label">Grade Point Avg</div>
                                    </div>
                                </td>

                                <!-- Letter Grade Callout -->
                                <td style="width: 30%; text-align: center;">
                                    <div style="background-color: #F8FAFC; border: 1.2px solid #E2E8F0; border-radius: 7px; padding: 3px 3px;">
                                        <div style="font-size: 16px; font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; line-height: 1;">
                                            {{ $finalGrade }}
                                        </div>
                                        <div class="cgpa-label">Letter Grade</div>
                                    </div>
                                </td>

                                <!-- Result Status & Remarks -->
                                <td style="width: 38%; padding-left: 6px;">
                                    <table style="border-collapse: collapse; width: 100%;">
                                        <tr>
                                            <td style="border: none; padding: 1.5px 4px 1.5px 0; font-size: 8px; font-weight: 700; color: #64748B; text-transform: uppercase; white-space: nowrap; width: 1%; vertical-align: middle;">Status:</td>
                                            <td style="border: none; padding: 1.5px 0; font-size: 11px; font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; white-space: nowrap; vertical-align: middle;">
                                                {{ $finalGrade === 'F' ? 'FAILED' : 'PASSED' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="border: none; padding: 1.5px 4px 1.5px 0; font-size: 8px; font-weight: 700; color: #64748B; text-transform: uppercase; white-space: nowrap; width: 1%; vertical-align: middle;">Remarks:</td>
                                            <td style="border: none; padding: 1.5px 0; font-size: 9.5px; font-weight: 700; color: #0F1E2C; white-space: nowrap; vertical-align: middle;">
                                                {{ $remark }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Teacher's Observations & Assessment Box -->
    <div class="remarks-box">
        <div class="remarks-title">Observations &amp; Academic Recommendation:</div>
        <div class="remarks-text">
            @if($finalGrade === 'F')
                The student requires focused academic intervention and extra support in written assessments to achieve passing standards. Regular revision and parent-teacher collaboration are strongly recommended.
            @elseif($cgpa >= 4.5)
                Outstanding academic performance! Demonstrates strong conceptual understanding, excellent discipline, and consistent classroom engagement. Keep up the high standard of achievement.
            @elseif($cgpa >= 3.5)
                Good academic progress with praiseworthy effort. With additional focus on targeted subject areas, further academic excellence can be readily attained.
            @else
                Satisfactory performance with clear potential for improvement. Enhanced daily practice, regular homework completion, and active class participation are advised.
            @endif
        </div>
    </div>

    <!-- Bottom Anchored Signatures & Footer -->
    <div class="bottom-anchored-footer">
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
