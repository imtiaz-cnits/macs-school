<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student List Report</title>
    <style>
        @page { 
            margin: 12px 18px; 
            size: A4 portrait; 
        }
        
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #ffffff;
            color: #1a202c;
        }

        /* Header Style */
        .header-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2px solid #009A49; /* MACS Green Accent Line */
            padding-bottom: 6px;
        }

        .logo-cell {
            width: 8%;
            vertical-align: middle;
        }

        .logo-img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }

        .title-cell {
            width: 62%;
            vertical-align: middle;
            padding-left: 10px;
        }

        .school-name {
            font-size: 18px;
            font-weight: 900;
            color: #008ED6; /* MACS Sky Blue */
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .school-subtitle {
            font-size: 9.5px;
            color: #4a5568;
            margin: 2px 0 0 0;
            font-weight: 700;
        }

        .report-title {
            font-size: 11px;
            font-weight: 900;
            color: #009A49; /* MACS Green */
            margin: 3px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filter-label {
            font-weight: 800;
            color: #008ED6;
        }

        /* Table Style */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .data-table th {
            background-color: #008ED6; /* MACS Sky Blue */
            color: #ffffff;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #008ED6;
        }

        .data-table td {
            padding: 3px 5px;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Helpers & Badges */
        .student-photo {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid #cbd5e1;
            object-fit: cover;
        }

        .student-name {
            font-size: 9.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .student-id {
            font-size: 8px;
            color: #009A49; /* MACS Green */
            font-weight: 800;
            margin-top: 1px;
        }

        .class-badge {
            font-size: 9px;
            font-weight: 700;
            color: #0f172a;
        }

        .roll-badge {
            font-size: 8px;
            font-weight: 800;
            background-color: #f1f5f9;
            color: #475569;
            padding: 0.5px 3px;
            border-radius: 2px;
            border: 1px solid #e2e8f0;
            display: inline-block;
            margin-top: 1.5px;
        }

        .contact-info {
            font-size: 8px;
            color: #475569;
            line-height: 1.25;
        }

        .contact-bold {
            color: #1e293b;
            font-weight: 700;
        }

        /* Footer Style */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <!-- Branded Header -->
    <table class="header-container">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('img/logo.svg') }}" class="logo-img" alt="Logo">
            </td>
            <td class="title-cell">
                <div class="school-name">{{ $branchName }}</div>
                <div class="school-subtitle">
                    <span class="filter-label">Branch:</span> {{ $filters['branch'] }} &nbsp;&nbsp;|&nbsp;&nbsp; 
                    <span class="filter-label">Class:</span> {{ $filters['class'] }}
                </div>
                <div class="report-title">Student Report</div>
            </td>
            <td style="text-align: right; vertical-align: bottom; font-size: 7.5px; color: #94a3b8; font-style: italic; padding-bottom: 2px;">
                Generated: {{ date('d M, Y h:i A') }}
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" style="text-align: center;">SL</th>
                <th width="8%" style="text-align: center;">Photo</th>
                <th width="30%">Student Information</th>
                <th width="28%">Academic Details</th>
                <th width="30%">Contact & Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                @php
                    $hasCustomPhoto = false;
                    $photoPath = '';
                    if ($student->photo) {
                        if (!str_starts_with($student->photo, 'img/')) {
                            $checkPath = public_path('storage/' . $student->photo);
                            if (file_exists($checkPath)) {
                                $photoPath = $checkPath;
                                $hasCustomPhoto = true;
                            }
                        }
                    }

                    if (!$hasCustomPhoto) {
                        // Extract initials
                        $words = explode(" ", trim($student->student_name));
                        $initials = "";
                        if (count($words) >= 2) {
                            $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                        } else {
                            $initials = strtoupper(substr($words[0] ?? 'S', 0, 2));
                        }

                        // Style background color based on gender
                        $avatarBg = '#008ED6'; // MACS Sky Blue for boys
                        if ($student->gender === 'Female') {
                            $avatarBg = '#e0115f'; // Soft pink/rose for girls
                        }
                    }
                @endphp
                <tr>
                    <td style="text-align: center; font-weight: bold; color: #475569;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">
                        @if($hasCustomPhoto)
                            <img src="{{ $photoPath }}" class="student-photo" alt="Student">
                        @else
                            <div style="width: 26px; height: 26px; line-height: 26px; border-radius: 50%; background-color: {{ $avatarBg }}; color: #ffffff; text-align: center; font-weight: bold; font-size: 9.5px; border: 1px solid #cbd5e1; display: inline-block;">
                                {{ $initials }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="student-name">{{ $student->student_name }}</div>
                        <div class="student-id">ID: {{ $student->student_identity ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="class-badge">Class: {{ $student->schoolClass->class_name ?? 'N/A' }}</div>
                        <div class="roll-badge">Roll: {{ $student->roll_number ?? 'N/A' }}</div>
                        <div style="font-size: 7.5px; color: #64748b; margin-top: 1.5px;">
                            Section: {{ $student->section->section_name ?? 'N/A' }} | Shift: {{ $student->shift->shift_name ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="contact-info">
                            <span class="contact-bold">Emergency Contact:</span> {{ $student->guardian_mobile ?? 'N/A' }}<br>
                            <span class="contact-bold">Gender:</span> {{ $student->gender ?? 'N/A' }}<br>
                            <span class="contact-bold">DOB:</span> {{ $student->dob ? date('d-m-Y', strtotime($student->dob)) : 'N/A' }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #ef4444; font-weight: bold; font-size: 11px;">
                        No students found matching the filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Printed Footer -->
    <div class="footer">
        {{ $branchName }} - Registered Student List Report. (System Auto Generated)
    </div>

</body>
</html>
