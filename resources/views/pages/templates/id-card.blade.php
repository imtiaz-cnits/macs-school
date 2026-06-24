<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Student ID Cards</title>
    <style>
        @page { 
            margin: 15px 12px; 
            size: A4 portrait; 
        }
        
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #ffffff;
        }
        
        /* Main Page Grid (3 Cards Per Row) */
        .page-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        
        .card-td {
            width: 33.33%;
            padding: 5px; 
            vertical-align: top;
        }

        /* Card Container */
        .card {
            border: 2px solid #1e4630; 
            border-radius: 10px;
            height: 350px; /* 🆕 ৩ লাইনে ৯টা ফিট করার জন্য পারফেক্ট সাইজ */
            position: relative; 
            background-color: #ffffff;
            overflow: hidden;
            z-index: 1;
        }
        
        .page-break { 
            page-break-after: always; 
        }

        /* Background Watermark Logo */
        .watermark {
            position: absolute;
            top: 135px; 
            left: 15%;
            width: 70%; 
            opacity: 0.08; 
            z-index: -1; 
        }
        
        /* Absolute Header */
        .card-top {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            background-color: #1e4630; 
            height: 80px; 
            text-align: center;
            padding-top: 8px;
            box-sizing: border-box;
            border-bottom: 2px solid #ffffff;
            z-index: 10;
        }
        
        .school-title {
            color: #ffffff;
            font-size: 11px; 
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0px;
            margin: 0 auto 3px auto;
            padding: 0 5px;
            line-height: 1.2; 
            max-height: 26px; 
            overflow: hidden;
            white-space: normal; 
            word-wrap: break-word;
        }
        
        .class-badge {
            color: #fde047; 
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .class-badge .divider {
            color: #ffffff;
            margin: 0 4px;
            font-weight: normal;
        }
        
        /* Absolute Photo Wrapper */
        .photo-wrapper {
            position: absolute;
            top: 45px; 
            left: 0;
            right: 0;
            text-align: center;
            z-index: 15;
        }
        
        .student-photo {
            width: 62px;  
            height: 72px; 
            border: 2px solid #cc0000; 
            border-radius: 6px;
            background-color: #ffffff;
            object-fit: cover;
        }
        
        /* Absolute Body */
        .card-body {
            position: absolute;
            top: 122px; /* 🆕 ছবির নিচ থেকে শুরু হবে */
            left: 12px; /* 🆕 দুপাশে সমান প্যাডিং */
            right: 12px;
            text-align: center;
            z-index: 10; 
        }
        
        .student-name {
            font-size: 14.5px; /* 🆕 নাম বড় করা হয়েছে */
            font-weight: 900;
            color: #cc0000; 
            text-transform: uppercase;
            margin-bottom: 6px; 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis; 
        }
        
        /* 🆕 100% Fixed 1-Column Details Table */
        .data-table {
            width: 100%;
            font-size: 11px; /* 🆕 ফন্ট সাইজ অনেক বড় ও স্পষ্ট করা হয়েছে */
            table-layout: fixed; 
            border-collapse: collapse;
        }
        
        .data-table td {
            padding: 3px 0; /* 🆕 গ্যাপ পারফেক্ট করা হয়েছে */
            border-bottom: 1px dashed #e5e7eb;
            white-space: nowrap; 
            overflow: hidden;
            text-overflow: ellipsis; 
        }
        
        .data-table tr:last-child td {
            border-bottom: none; 
        }

        /* 🆕 Column Widths for 1-Column Layout */
        .c-lbl { width: 32%; color: #1e4630; font-weight: bold; text-align: left; }
        .c-col { width: 5%; color: #1e4630; font-weight: bold; text-align: center; }
        .c-val { width: 63%; color: #111827; font-weight: bold; text-align: left; padding-left: 2px;}

        /* Absolute Footer Graphics */
        .footer-graphic-1 {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 80px;
            height: 35px;
            background-color: #cc0000; 
            border-top-left-radius: 100%;
            z-index: 2;
        }
        
        .footer-graphic-2 {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 45px;
            height: 20px;
            background-color: #facc15; 
            border-top-left-radius: 100%;
            z-index: 3;
        }
        
        /* Absolute Signature Area */
        .signature-area {
            position: absolute;
            left: 12px;
            bottom: 10px; 
            z-index: 5;
            text-align: center;
        }
        
        .sig-text {
            font-family: 'Times New Roman', Times, serif;
            font-style: italic;
            color: #1e4630; 
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .sig-title {
            border-top: 1px solid #000;
            font-size: 8px;
            font-weight: bold;
            padding-top: 2px;
            color: #000;
        }
    </style>
</head>
<body>
    
    @foreach($students->chunk(9) as $pageStudents)
        
        <table class="page-table">
            @foreach($pageStudents->chunk(3) as $rowStudents)
                <tr>
                    @foreach($rowStudents as $student)
                        @php
                            $photoPath = public_path('img/default-student.png');
                            if ($student->photo) {
                                if (str_starts_with($student->photo, 'img/')) {
                                    $photoPath = public_path($student->photo);
                                } else {
                                    $photoPath = public_path('storage/' . $student->photo);
                                }
                            }
                            
                            $dob = $student->dob ? date('d.m.Y', strtotime($student->dob)) : 'N/A';
                            $mobile = $student->father_mobile ?? $student->guardian_mobile ?? 'N/A';
                        @endphp
                        
                        <td class="card-td">
                            <div class="card">
                                
                                <img src="{{ public_path('img/logo.svg') }}" class="watermark">
                                
                                <div class="card-top">
                                    <div class="school-title">{{ $student->branch->branch_name ?? 'PABNA INTERNATIONAL SCHOOL' }}</div>
                                    <div class="class-badge">
                                        CLASS: {{ $student->schoolClass->class_name ?? 'N/A' }} 
                                        <span class="divider">|</span> 
                                        SEC: {{ $student->section->section_name ?? 'N/A' }}
                                    </div>
                                </div>

                                <div class="photo-wrapper">
                                    <img src="{{ $photoPath }}" class="student-photo">
                                </div>

                                <div class="card-body">
                                    <div class="student-name">{{ $student->student_name }}</div>
                                    
                                    <table class="data-table">
                                        <tr>
                                            <td class="c-lbl">ID No</td><td class="c-col">:</td><td class="c-val" style="color: #1e4630;">{{ $student->student_identity }}</td>
                                        </tr>
                                        <tr>
                                            <td class="c-lbl">Roll No</td><td class="c-col">:</td><td class="c-val">{{ $student->roll_number }}</td>
                                        </tr>
                                        <tr>
                                            <td class="c-lbl">Shift</td><td class="c-col">:</td><td class="c-val">{{ $student->shift->shift_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="c-lbl">Session</td><td class="c-col">:</td><td class="c-val">{{ $student->sessionYear->session_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="c-lbl">DOB</td><td class="c-col">:</td><td class="c-val">{{ $dob }}</td>
                                        </tr>
                                        <tr>
                                            <td class="c-lbl">Mobile</td><td class="c-col">:</td><td class="c-val">{{ $mobile }}</td>
                                        </tr>
                                        <tr>
                                            <td class="c-lbl">Blood Grp</td><td class="c-col">:</td><td class="c-val" style="color: #cc0000;">{{ $student->blood_group ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="footer-graphic-1"></div>
                                <div class="footer-graphic-2"></div>
                                
                                <div class="signature-area">
                                    <div class="sig-text">Principal</div>
                                    <div class="sig-title">Signature</div>
                                </div>
                                
                            </div>
                        </td>
                    @endforeach
                    
                    @for($i = $rowStudents->count(); $i < 3; $i++)
                        <td class="card-td"></td>
                    @endfor
                </tr>
            @endforeach
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif

    @endforeach

</body>
</html>