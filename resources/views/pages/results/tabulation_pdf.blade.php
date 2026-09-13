<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Tabulation Sheet - {{ $schoolClass->class_name }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 3mm 4mm 3mm 4mm;
        }

        /* Embedded TrueType Fonts for Crisp DomPDF Typography */
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
        @font-face {
            font-family: 'Noto Serif Bengali';
            font-style: normal;
            font-weight: 400;
            src: url('{{ $fontNotoRegular ?? str_replace('\\', '/', public_path('fonts/NotoSerifBengali-Regular.ttf')) }}') format('truetype');
        }
        @font-face {
            font-family: 'Noto Serif Bengali';
            font-style: normal;
            font-weight: 700;
            src: url('{{ $fontNotoBold ?? str_replace('\\', '/', public_path('fonts/NotoSerifBengali-Bold.ttf')) }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Noto Serif Bengali', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0F1E2C;
            font-size: 8px;
            line-height: 1.15;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Header Layout (Compact & Centered) */
        .header-container {
            margin-bottom: 3px;
            width: 100%;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
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
        .report-badge {
            display: inline-block;
            background-color: #008ED6;
            color: #ffffff;
            font-size: 7.8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 1.5px 12px;
            border-radius: 12px;
            margin-top: 2px;
        }
        .exam-banner-title {
            font-size: 8.5px;
            font-weight: 700;
            color: #008ED6;
            margin-top: 1.5px;
            letter-spacing: 0.2px;
        }

        /* Main Tabulation Table */
        .tabulation-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #1E293B;
            margin-bottom: 0;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }

        /* Table Headers */
        .th-main {
            background-color: #008ED6;
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            padding: 2.5px 1px;
            border: 1px solid #1E293B;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            font-size: 8.5px;
        }
        .th-roll {
            width: 20px;
            min-width: 18px;
            max-width: 22px;
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.1px;
            text-align: center;
            padding: 2px 1px;
        }
        .th-student {
            width: 95px;
            min-width: 85px;
            max-width: 100px;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.2px;
            text-align: left;
            padding: 2px 2px 2px 4px !important;
        }
        .th-summary {
            background-color: #009A49;
            border-color: #047857;
            font-size: 8.2px;
            font-weight: 800;
            width: 22px;
            min-width: 20px;
            max-width: 24px;
            padding: 2px 1px;
        }
        .th-sub {
            background-color: #0277B5;
            color: #ffffff;
            font-size: 7.2px;
            font-weight: 700;
            text-align: center;
            padding: 1.5px 0.5px;
            border: 1px solid #1E293B;
            text-transform: uppercase;
            width: 18px;
            min-width: 16px;
            max-width: 20px;
            letter-spacing: -0.1px;
        }
        .th-sub-total {
            background-color: #0369A1;
            font-weight: 800;
            width: 20px;
            min-width: 18px;
            max-width: 22px;
        }

        /* Table Body Cells - Crisp 9px Typography & Dynamic Page Fill */
        .tabulation-table td {
            border: 1px solid #1E293B;
            padding: 1.8px 1px;
            text-align: center;
            font-size: 9px;
            font-weight: 600;
            color: #1E293B;
            vertical-align: middle;
            line-height: 1.15;
        }
        .tabulation-table tr:nth-child(even) td {
            background-color: #F8FAFC;
        }

        /* Roll & Student Name Cells */
        .td-roll {
            text-align: center !important;
            font-weight: 800;
            color: #0F1E2C;
            font-size: 9px;
            padding: 1.8px 1px !important;
            width: 20px;
        }
        .td-student {
            text-align: left !important;
            padding: 1.8px 2px 1.8px 4px !important;
            font-weight: 700;
            color: #0F1E2C;
            font-size: 9px;
            white-space: nowrap;
            width: 95px;
        }

        /* Mark Cells */
        .td-mark {
            font-size: 9px;
            color: #334155;
            font-weight: 600;
            padding: 1.8px 0.5px !important;
            width: 18px;
            min-width: 16px;
        }
        .td-total {
            font-weight: 800;
            color: #0F1E2C;
            font-size: 9px;
            width: 20px;
            min-width: 18px;
        }
        .mark-fail {
            color: #DC2626 !important;
            font-weight: 800 !important;
            background-color: #FEF2F2 !important;
        }

        /* Summary Column Cells */
        .td-summary {
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            width: 22px;
            min-width: 20px;
            padding: 1.8px 1px !important;
        }
        .td-grand-total {
            font-weight: 800;
            color: #0F1E2C;
        }
        .rank-badge {
            display: inline-block;
            background-color: #F1F5F9;
            border: 1px solid #CBD5E1;
            color: #0F1E2C;
            padding: 0.5px 3px;
            border-radius: 3px;
            font-weight: 800;
            font-size: 9px;
        }

        /* Final Grade Text */
        .grade-pill {
            font-weight: 800;
            font-size: 9px;
        }
        .grade-aplus, .grade-a { color: #009A49; }
        .grade-aminus, .grade-b { color: #0284C7; }
        .grade-c, .grade-d { color: #D97706; }
        .grade-fail { color: #DC2626 !important; font-weight: 800; }

        .no-data-cell {
            padding: 20px;
            text-align: center;
            font-weight: 700;
            color: #DC2626;
            font-size: 11px;
            background-color: #FEF2F2;
        }

        /* Signatures Section - Anchored to bottom of final page */
        .signatures-section {
            position: absolute;
            bottom: 4mm;
            left: 5mm;
            right: 5mm;
            width: auto;
            page-break-inside: avoid;
        }
        .signatures-table {
            width: 100%;
            margin-bottom: 2px;
        }
        .signatures-table td {
            border: none;
            text-align: center;
            vertical-align: bottom;
            font-size: 8px;
            font-weight: 700;
            color: #334155;
            width: 33.33%;
            padding: 0;
        }
        .sig-line {
            display: inline-block;
            width: 140px;
            border-top: 1.2px dashed #94A3B8;
            padding-top: 2px;
        }

        /* Footer Details */
        .footer-table {
            width: 100%;
            border-top: 1px solid #E2E8F0;
            padding-top: 1.5px;
            margin-top: 2px;
        }
        .footer-table td {
            border: none;
            font-size: 6.8px;
            color: #94A3B8;
            font-weight: 500;
            padding: 1px 0;
        }
    </style>
</head>
<body>

@php
    $chunks = $subjectChunks ?? collect([$schedules]);
    $isMultiPart = $chunks->count() > 1;
    $className = strtolower(trim($schoolClass->class_name ?? ''));
    $isHighSchool = in_array($className, ['nine', 'class nine', '9', 'class 9', 'ten', 'class ten', '10', 'class 10']);
@endphp

@foreach($chunks as $chunkIndex => $chunkSchedules)
    @php
        $isLastPart = ($chunkIndex === $chunks->count() - 1);
        $partNumber = $chunkIndex + 1;
        $totalParts = $chunks->count();
    @endphp

    <div class="tabulation-part" style="{{ !$loop->first ? 'page-break-before: always;' : '' }}">

        <!-- Header Section (Compact & Dead Centered) -->
        <div class="header-container">
            <table class="header-table">
                <tr>
                    <!-- Left: School Logo -->
                    <td style="width: 12%; text-align: left; vertical-align: middle;">
                        @if(!empty($logoSrc))
                            <img src="{{ $logoSrc }}" style="width: 44px; height: 44px; object-fit: contain;" alt="MACS Logo" />
                        @else
                            <div style="width: 44px; height: 44px; border: 1.5px solid #008ED6; border-radius: 6px; text-align: center; line-height: 44px; font-weight: 800; color: #008ED6; font-size: 10px;">MACS</div>
                        @endif
                    </td>

                    <!-- Center: School Details & Tabulation Banner (Dead Center) -->
                    <td style="width: 76%; text-align: center; vertical-align: middle;">
                        <div class="school-name">MACS SCHOOL &amp; COLLEGE</div>
                        <div class="school-address">{{ str_ends_with(strtolower($branch->branch_name ?? ''), 'branch') ? $branch->branch_name : (($branch->branch_name ?? 'Jalalpur') . ' Branch') }} &bull; Pabna Sadar, Pabna &bull; Bangladesh</div>
                        <div class="report-badge">
                            @if($isMultiPart)
                                ACADEMIC TABULATION SHEET &bull; PART {{ $partNumber }} OF {{ $totalParts }}
                            @else
                                ACADEMIC TABULATION SHEET
                            @endif
                        </div>
                        <div class="exam-banner-title">
                            {{ $exam->name }} &bull; Class: {{ $schoolClass->class_name }} &bull; Session: {{ $sessionYear->session_name ?? date('Y') }}
                        </div>
                    </td>

                    <!-- Right: Balancer Spacer -->
                    <td style="width: 12%; text-align: right; vertical-align: middle;">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Main Tabulation Marks Table -->
        <table class="tabulation-table">
            <thead>
                <tr>
                    <th rowspan="2" class="th-main th-roll">ROLL</th>
                    <th rowspan="2" class="th-main th-student">STUDENT NAME</th>
                    @foreach($chunkSchedules as $schedule)
                        @php
                            $subName = $schedule->formatted_subject_name ?? \App\Http\Controllers\ResultController::formatSubjectName($schedule->subject->subject_name ?? '');
                        @endphp
                        <th colspan="4" class="th-main">{{ $subName }}</th>
                    @endforeach

                    @if($isLastPart)
                        <th rowspan="2" class="th-main th-summary">TOTAL</th>
                        <th rowspan="2" class="th-main th-summary">GRADE</th>
                        <th rowspan="2" class="th-main th-summary">GPA</th>
                        <th rowspan="2" class="th-main th-summary">MERIT</th>
                    @endif
                </tr>
                <tr>
                    @foreach($chunkSchedules as $schedule)
                        @if($isHighSchool)
                            <th class="th-sub">MCQ</th>
                            <th class="th-sub">WRT</th>
                            <th class="th-sub">PRAC</th>
                            <th class="th-sub th-sub-total">TOTAL</th>
                        @else
                            <th class="th-sub">CT</th>
                            <th class="th-sub">MT</th>
                            <th class="th-sub">TERM</th>
                            <th class="th-sub th-sub-total">TOTAL</th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($studentData as $index => $data)
                @php
                    $isOverallFail = ($data->final_grade === 'F' || $data->final_grade === 'Fail');
                @endphp
                <tr>
                    <td class="td-roll">{{ $data->student->roll_number ?? $data->student->student_identity }}</td>
                    <td class="td-student">{{ $data->student->student_name ?? 'Name N/A' }}</td>

                    @foreach($chunkSchedules as $schedule)
                        @php
                            $mark = $data->marks->get($schedule->subject_id);
                            $isSubFailed = $mark && ($mark->letter_grade === 'F' || $mark->letter_grade === 'Fail');
                        @endphp
                        @if($isHighSchool)
                            <td class="td-mark">{{ $mark && $mark->mcq_mark > 0 ? (float)$mark->mcq_mark : '-' }}</td>
                            <td class="td-mark">{{ $mark && $mark->written_mark > 0 ? (float)$mark->written_mark : '-' }}</td>
                            <td class="td-mark">{{ $mark && $mark->ct_mark > 0 ? (float)$mark->ct_mark : '-' }}</td>
                            <td class="td-mark td-total {{ $isSubFailed ? 'mark-fail' : '' }}">
                                {{ $mark ? (float)$mark->total_mark : '-' }}
                            </td>
                        @else
                            <td class="td-mark">{{ $mark && $mark->ct_mark > 0 ? (float)$mark->ct_mark : '-' }}</td>
                            <td class="td-mark">{{ $mark && $mark->mcq_mark > 0 ? (float)$mark->mcq_mark : '-' }}</td>
                            <td class="td-mark">{{ $mark && $mark->written_mark > 0 ? (float)$mark->written_mark : '-' }}</td>
                            <td class="td-mark td-total {{ $isSubFailed ? 'mark-fail' : '' }}">
                                {{ $mark ? (float)$mark->total_mark : '-' }}
                            </td>
                        @endif
                    @endforeach

                    @if($isLastPart)
                        <td class="td-summary td-grand-total">{{ $data->grand_total }}</td>
                        <td class="td-summary">
                            <span class="grade-pill grade-{{ strtolower(str_replace('+', 'plus', $data->final_grade)) }} {{ $isOverallFail ? 'grade-fail' : '' }}">
                                {{ $data->final_grade }}
                            </span>
                        </td>
                        <td class="td-summary {{ $isOverallFail ? 'mark-fail' : '' }}">
                            {{ number_format((float)$data->cgpa, 2) }}
                        </td>
                        <td class="td-summary">
                            <span class="rank-badge">{{ $data->merit_rank ?? ($index + 1) }}</span>
                        </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 2 + count($chunkSchedules) * 4 + ($isLastPart ? 4 : 0) }}" class="no-data-cell">
                        No student records found for this class and examination.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures & Authority Section on Final Part -->
        @if($isLastPart)
        <div class="signatures-section">
            <table class="signatures-table">
                <tr>
                    <td>
                        <div class="sig-line">Class Teacher's Signature</div>
                    </td>
                    <td>
                        <div class="sig-line">Exam Controller's Signature</div>
                    </td>
                    <td>
                        @if(!empty($signatureSrc))
                            <div style="margin-bottom: 2px;">
                                <img src="{{ $signatureSrc }}" style="height: 32px; object-fit: contain;" alt="Signature" />
                            </div>
                        @else
                            <div style="height: 32px;"></div>
                        @endif
                        <div class="sig-line">Principal's Signature</div>
                    </td>
                </tr>
            </table>

            <!-- Subtle Document Footer -->
            <table class="footer-table">
                <tr>
                    <td style="text-align: left;">Generated on: {{ date('d M Y, h:i A') }}</td>
                </tr>
            </table>
        </div>
        @endif

    </div>
@endforeach

    <!-- DomPDF Script for Dynamic Page Numbering on all pages (A4 Landscape) -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page " . $PAGE_NUM . " of " . $PAGE_COUNT;
            $font = $fontMetrics->get_font("Helvetica", "normal");
            $size = 7;
            $pdf->page_text(760, 582, $text, $font, $size, array(0.5, 0.55, 0.6));
        }
    </script>

</body>
</html>