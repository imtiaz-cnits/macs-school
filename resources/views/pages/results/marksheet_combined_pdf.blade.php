<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Progress Report (Combined 3-Terms) - {{ $student->student_identity }}</title>
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
            margin: 5mm 6mm !important;
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
            padding: 7px 10px;
            position: relative;
            background: #ffffff;
        }

        /* Top Color Accent Strip */
        .top-accent-bar {
            width: 100%;
            height: 3.5px;
            background-color: #008ED6;
            margin-bottom: 6px;
            border-radius: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Header Layout */
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

        /* Grading System Mini Table */
        .grade-legend-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #CBD5E1;
            border-radius: 5px;
            overflow: hidden;
            font-size: 6.5px;
            text-align: center;
        }
        .grade-legend-table th {
            background-color: #0F1E2C;
            color: #ffffff;
            font-weight: 700;
            padding: 1.2px 2px;
            border: 1px solid #0F1E2C;
            text-transform: uppercase;
        }
        .grade-legend-table td {
            border: 1px solid #E2E8F0;
            padding: 1px 2px;
            font-weight: 600;
            color: #334155;
        }
        .grade-legend-table tr:nth-child(even) td {
            background-color: #F8FAFC;
        }

        /* Student Information Card */
        .student-profile-card {
            width: 100%;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 7px;
            margin-top: 5px;
            margin-bottom: 5px;
            padding: 5px 8px;
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
            width: 14%;
        }
        .info-val {
            font-weight: 700;
            color: #0F1E2C;
            width: 32%;
            font-size: 8.5px;
        }
        .badge-id {
            background-color: #EFF6FF;
            color: #008ED6;
            border: 1px solid #BFDBFE;
            padding: 1px 5px;
            border-radius: 4px;
            font-weight: 800;
            font-family: monospace;
            font-size: 8px;
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
            margin-top: 3px;
            margin-bottom: 5px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            overflow: hidden;
        }
        .marks-table th {
            color: #ffffff;
            font-size: 7.2px;
            font-weight: 700;
            text-align: center;
            padding: 3px 2px;
            border: 1px solid #CBD5E1;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .marks-table th.head-base {
            background-color: #0F1E2C;
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
            padding: 2px 1px;
            background-color: #1E293B;
            border-color: #334155;
        }
        .marks-table td {
            border: 1px solid #E2E8F0;
            padding: 3px 2px;
            text-align: center;
            font-size: 7.8px;
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
            font-size: 8px;
        }
        .marks-table tr.total-summary-row td {
            background-color: #F1F5F9;
            font-weight: 800;
            font-size: 8.5px;
            color: #0F1E2C;
            border-top: 2px solid #94A3B8;
            padding: 4px 3px;
        }

        /* Grade Badges */
        .grade-pill {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 10px;
            font-size: 7.2px;
            font-weight: 800;
            text-align: center;
        }
        .grade-a-plus, .grade-a {
            background-color: #ECFDF5;
            color: #009A49;
            border: 1px solid #A7F3D0;
        }
        .grade-a-minus, .grade-b {
            background-color: #EFF6FF;
            color: #0284C7;
            border: 1px solid #BAE6FD;
        }
        .grade-c, .grade-d {
            background-color: #FFFBEB;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        .grade-f {
            background-color: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }

        /* Bottom Evaluation Cards (2 Columns) */
        .eval-container {
            width: 100%;
            margin-bottom: 4px;
        }
        .eval-container td {
            border: none;
            vertical-align: top;
        }
        .eval-card {
            border: 1px solid #E2E8F0;
            border-radius: 7px;
            background-color: #ffffff;
            padding: 5px 8px;
        }
        .eval-card-header {
            font-size: 7.8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding-bottom: 2.5px;
            border-bottom: 1.5px solid #F1F5F9;
            margin-bottom: 4px;
        }
        .eval-card-header.header-blue {
            color: #008ED6;
            border-bottom-color: #E0F2FE;
        }
        .eval-card-header.header-green {
            color: #009A49;
            border-bottom-color: #DCFCE7;
        }

        /* Merit Table */
        .merit-table td, .merit-table th {
            border: 1px solid #E2E8F0;
            padding: 2px 2px;
            text-align: center;
            font-size: 7.2px;
        }
        .merit-table th {
            background-color: #F8FAFC;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }
        .merit-table td {
            font-weight: 700;
            color: #0F1E2C;
        }

        /* Result Callout Box */
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
            padding: 3px 5px;
        }
        .cgpa-callout-fail {
            background-color: #FEF2F2;
            border: 1.5px solid #FECACA;
            border-radius: 7px;
            text-align: center;
            padding: 3px 5px;
        }
        .cgpa-score {
            font-size: 14px;
            font-weight: 800;
            line-height: 1;
        }
        .cgpa-label {
            font-size: 6.5px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* Signatures Section */
        .signatures-table {
            width: 100%;
            margin-top: 14px;
            margin-bottom: 3px;
        }
        .signatures-table td {
            border: none;
            text-align: center;
            vertical-align: bottom;
            font-size: 7.8px;
            font-weight: 700;
            color: #334155;
            width: 33.33%;
        }
        .sig-line {
            display: inline-block;
            width: 130px;
            border-top: 1.2px dashed #94A3B8;
            padding-top: 2.5px;
        }

        /* Footer Info */
        .footer-table {
            width: 100%;
            margin-top: 4px;
            border-top: 1px solid #E2E8F0;
            padding-top: 2.5px;
        }
        .footer-table td {
            border: none;
            font-size: 6.5px;
            color: #94A3B8;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="marksheet-wrapper">
    <!-- Top Brand Strip -->
    <div class="top-accent-bar"></div>

    <!-- Header Section (Logo, School Details, Grading System) -->
    <table class="header-table">
        <tr>
            <!-- Left: Logo -->
            <td style="width: 12%; text-align: left;">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" style="width: 58px; height: 58px; object-fit: contain;" alt="MACS Logo" />
                @else
                    <div style="width: 54px; height: 54px; border: 1.5px solid #008ED6; border-radius: 8px; text-align: center; line-height: 54px; font-weight: 800; color: #008ED6; font-size: 9px;">MACS</div>
                @endif
            </td>

            <!-- Center: School Details & Combined Progress Report Title -->
            <td style="width: 63%; text-align: center;">
                <div class="school-name">MACS School &amp; College</div>
                <div class="school-address">Jalalpur, Pabna Sadar, Pabna &bull; Bangladesh</div>
                <div class="school-contact">Hotline: 01896-220299, 01896-220300 &bull; Web: macs.edu.bd</div>
                <div class="report-badge">Academic Progress Report (Combined 3-Terms)</div>
                <div class="exam-banner-title">Final Consolidated Evaluation &bull; Academic Session: {{ $sessionYear->session_name }}</div>
            </td>

            <!-- Right: GPA Grading System Card -->
            <td style="width: 25%; text-align: right;">
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

    <!-- Student Information Profile Card -->
    <div class="student-profile-card">
        <table class="student-info-table">
            <tr>
                <td class="info-label">Student ID</td>
                <td class="info-val"><span class="badge-id">{{ $student->student_identity }}</span></td>
                <td class="info-label">Class &amp; Section</td>
                <td class="info-val">{{ $student->schoolClass->class_name ?? 'N/A' }} ({{ $student->section->section_name ?? 'A' }})</td>
                <td rowspan="4" style="width: 10%; text-align: right; vertical-align: middle; padding-left: 6px;">
                    @if(!empty($photoSrc))
                        <img src="{{ $photoSrc }}" style="width: 46px; height: 54px; object-fit: cover; border: 1.5px solid #008ED6; border-radius: 5px; padding: 1px;" alt="Student Photo" />
                    @else
                        <div style="width: 46px; height: 54px; border: 1.5px solid #CBD5E1; border-radius: 5px; text-align: center; line-height: 54px; font-size: 7px; color: #94A3B8;">Photo</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">Student Name</td>
                <td class="info-val" style="color: #008ED6; font-size: 9px; font-weight: 800;">{{ $student->first_name }} {{ $student->last_name }}</td>
                <td class="info-label">Roll Number</td>
                <td class="info-val"><span class="badge-roll">Roll #{{ $student->roll_number }}</span></td>
            </tr>
            <tr>
                <td class="info-label">Father's Name</td>
                <td class="info-val">{{ $student->father_name ?? '-' }}</td>
                <td class="info-label">Shift &amp; Group</td>
                <td class="info-val">{{ $student->shift->shift_name ?? 'Morning' }} &bull; {{ $student->group->group_name ?? 'General' }}</td>
            </tr>
            <tr>
                <td class="info-label">Mother's Name</td>
                <td class="info-val">{{ $student->mother_name ?? '-' }}</td>
                <td class="info-label">Campus Branch</td>
                <td class="info-val">{{ $student->branch->branch_name ?? 'Main Campus' }}</td>
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
                <!-- 1st Term -->
                <th class="sub-head" style="width: 4%;">CT</th>
                <th class="sub-head" style="width: 4%;">MT</th>
                <th class="sub-head" style="width: 4%;">Term</th>
                <th class="sub-head" style="width: 4%; background-color: #0369A1;">Total</th>

                <!-- 2nd Term -->
                <th class="sub-head" style="width: 4%;">CT</th>
                <th class="sub-head" style="width: 4%;">MT</th>
                <th class="sub-head" style="width: 4%;">Term</th>
                <th class="sub-head" style="width: 4%; background-color: #0369A1;">Total</th>

                <!-- 3rd Term -->
                <th class="sub-head" style="width: 3.5%;">CT</th>
                <th class="sub-head" style="width: 3.5%;">MT</th>
                <th class="sub-head" style="width: 3.5%;">Term</th>
                <th class="sub-head" style="width: 3.5%; background-color: #0077B6;">Total</th>
                <th class="sub-head" style="width: 3%;">LG</th>
                <th class="sub-head" style="width: 3.5%;">GP</th>
                <th class="sub-head" style="width: 3.5%;">Top</th>

                <!-- Final Combined -->
                <th class="sub-head" style="width: 6%; background-color: #047857;">Total</th>
                <th class="sub-head" style="width: 5%;">LG</th>
                <th class="sub-head" style="width: 5%;">GP</th>
                <th class="sub-head" style="width: 6%;">Top</th>
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
                <td style="font-weight: 800; color: #009A49; font-size: 9px;">{{ number_format($grandTotalMarks, 1) }}</td>
                <td><span class="grade-pill {{ $finalGrade === 'F' ? 'grade-f' : 'grade-a' }}">{{ $finalGrade }}</span></td>
                <td style="font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; font-size: 9px;">{{ number_format($cgpa, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Evaluation Section: 2 Columns (Multi-term Merit & Final Result Showcase) -->
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
                                    <td style="text-align: left; padding-left: 5px; font-weight: 700; color: #0F1E2C;">{{ $em['exam_name'] }}</td>
                                    <td style="font-weight: 800; color: #008ED6;">{{ $em['section_wise'] }}</td>
                                    <td style="font-weight: 800; color: #008ED6;">{{ $em['shift_wise'] }}</td>
                                    <td style="font-weight: 800; color: #008ED6;">{{ $em['class_wise'] }}</td>
                                    <td>{{ $em['working_days'] ?: '-' }}</td>
                                    <td style="color: #009A49; font-weight: 700;">{{ $em['present'] ?: '-' }}</td>
                                    <td style="color: #DC2626; font-weight: 700;">{{ $em['absent'] ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div style="font-size: 6.8px; color: #64748B; margin-top: 3px; font-weight: 500;">
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
                                <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 7px; padding: 3px 4px;">
                                    <div style="font-size: 14px; font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; line-height: 1;">
                                        {{ $finalGrade }}
                                    </div>
                                    <div class="cgpa-label">Final Grade</div>
                                </div>
                            </td>

                            <!-- Result Status & Remarks -->
                            <td style="width: 40%; padding-left: 5px;">
                                <div style="font-size: 7px; font-weight: 700; color: #64748B; text-transform: uppercase;">Status:</div>
                                <div style="font-size: 10px; font-weight: 800; color: {{ $finalGrade === 'F' ? '#DC2626' : '#009A49' }}; line-height: 1.1;">
                                    {{ $finalGrade === 'F' ? 'FAILED' : 'PASSED' }}
                                </div>
                                <div style="font-size: 7px; font-weight: 700; color: #64748B; margin-top: 2px; text-transform: uppercase;">Remarks:</div>
                                <div style="font-size: 8px; font-weight: 700; color: #0F1E2C;">
                                    {{ $remark }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

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
                        <img src="{{ $signatureSrc }}" style="height: 24px; object-fit: contain;" alt="Signature" />
                    </div>
                @else
                    <div style="height: 24px;"></div>
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

</body>
</html>
