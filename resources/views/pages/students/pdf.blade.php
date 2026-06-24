<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student List Report</title>
    <style>
        @page { 
            margin: 20px 25px; 
            size: A4 portrait; 
        }
        
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #ffffff;
            color: #333333;
        }

        /* Header Style */
        .header-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 3px solid #1e4630;
            padding-bottom: 12px;
        }

        .logo-cell {
            width: 12%;
            vertical-align: middle;
        }

        .logo-img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
        }

        .title-cell {
            width: 88%;
            vertical-align: middle;
            padding-left: 15px;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e4630;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .school-subtitle {
            font-size: 11px;
            color: #666666;
            margin: 3px 0 0 0;
            font-weight: bold;
        }

        .report-title {
            font-size: 15px;
            font-weight: bold;
            color: #cc0000;
            margin: 5px 0 0 0;
            text-transform: uppercase;
        }

        /* Filter Info Cards */
        .filters-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f3f6f4;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .filters-table td {
            padding: 8px 12px;
            color: #4a5568;
        }

        .filter-label {
            font-weight: bold;
            color: #1e4630;
        }

        /* Table Style */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .data-table th {
            background-color: #1e4630;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #1e4630;
        }

        .data-table td {
            padding: 8px 8px;
            font-size: 11px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* Helpers & Badges */
        .student-photo {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1;
            object-fit: cover;
        }

        .student-name {
            font-size: 12px;
            font-weight: bold;
            color: #1a202c;
        }

        .student-id {
            font-size: 10px;
            color: #1e4630;
            font-weight: bold;
            margin-top: 2px;
        }

        .class-badge {
            font-size: 11px;
            font-weight: bold;
            color: #1e4630;
        }

        .roll-badge {
            font-size: 9.5px;
            font-weight: bold;
            background-color: #edf2f7;
            color: #4a5568;
            padding: 2px 5px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            display: inline-block;
            margin-top: 3px;
        }

        .contact-info {
            font-size: 10px;
            color: #4a5568;
            line-height: 1.4;
        }

        .contact-bold {
            color: #2d3748;
            font-weight: bold;
        }

        /* Footer Style */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #718096;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
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
                <div class="report-title">Student Report</div>
            </td>
        </tr>
    </table>

    <!-- Applied Filters Info -->
    <table class="filters-table">
        <tr>
            <td width="33%">
                <span class="filter-label">Branch:</span> {{ $filters['branch'] }}
            </td>
            <td width="33%">
                <span class="filter-label">Class:</span> {{ $filters['class'] }}
            </td>
            <td width="34%">
                <span class="filter-label">Section:</span> {{ $filters['section'] }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="filter-label">Session:</span> {{ $filters['session'] }}
            </td>
            <td>
                <span class="filter-label">Shift:</span> {{ $filters['shift'] }}
            </td>
            <td>
                <span class="filter-label">Gender:</span> {{ $filters['gender'] }}
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right; color: #a0aec0; font-style: italic; font-size: 9px; padding-top: 2px;">
                Report Generated on: {{ date('d M, Y h:i A') }}
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">SL</th>
                <th width="10%" style="text-align: center;">Photo</th>
                <th width="28%">Student Information</th>
                <th width="27%">Academic Details</th>
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
                        $avatarBg = '#1e4630'; // Theme Green
                        if ($student->gender === 'Female') {
                            $avatarBg = '#8b1e3f'; // Premium Maroon-Rose for girls
                        }
                    }
                @endphp
                <tr>
                    <td style="text-align: center; font-weight: bold; color: #4a5568;">{{ $index + 1 }}</td>
                    <td style="text-align: center;">
                        @if($hasCustomPhoto)
                            <img src="{{ $photoPath }}" class="student-photo" alt="Student">
                        @else
                            <div style="width: 38px; height: 38px; line-height: 38px; border-radius: 50%; background-color: {{ $avatarBg }}; color: #ffffff; text-align: center; font-weight: bold; font-size: 13px; border: 1.5px solid #cbd5e1; display: inline-block;">
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
                        <div style="font-size: 9px; color: #718096; margin-top: 3px;">
                            Section: {{ $student->section->section_name ?? 'N/A' }} | Shift: {{ $student->shift->shift_name ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="contact-info">
                            <span class="contact-bold">Mob:</span> {{ $student->guardian_mobile ?? 'N/A' }}<br>
                            <span class="contact-bold">Gender:</span> {{ $student->gender ?? 'N/A' }}<br>
                            <span class="contact-bold">DOB:</span> {{ $student->dob ? date('d-m-Y', strtotime($student->dob)) : 'N/A' }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #e53e3e; font-weight: bold; font-size: 13px;">
                        No students found matching the filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Printed Footer -->
    <div class="footer">
        Pabna International School - Registered Student List Report. Page 1 of 1 (System Auto Generated)
    </div>

</body>
</html>
