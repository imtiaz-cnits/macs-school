<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Mark Sheet - {{ $student->student_identity }}</title>
    <style>
        /* PDF Page Setup */
        @page { size: A4 portrait; margin: 25px; }
        
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0; padding: 0; color: #000; font-size: 11px; }
        
        /* Outer Double Border */
        .page-wrapper { border: 6px solid #f6ecec; padding: 2px; background: white; }
        .marksheet-container { border: 2px solid #235a3b; padding: 15px; position: relative; }
        
        /* Typography & Colors */
        h1, h2, h3, h4, h5, p, div { margin: 0; padding: 0; }
        .text-center { text-align: center; }
        .bg-green { background-color: #2a5a3b !important; color: white !important; font-weight: bold; }
        .text-green { color: #2a5a3b; }
        
        /* Tables Default */
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #777; padding: 4px; text-align: center; }
        .text-left { text-align: left; }
        
        /* Header Layout using Table (20% - 60% - 20% for perfect center) */
        .header-table { width: 100%; border: none; margin-bottom: 10px; }
        .header-table td { border: none; padding: 0; }
        
        /* Specific Styles for Header elements */
        .logo-placeholder { width: 75px; height: 75px; border: 2px solid #2a5a3b; border-radius: 50%; display: inline-block; line-height: 75px; text-align: center; color: #2a5a3b; font-weight: bold; font-size: 11px; margin: 0 auto; }
        .school-title { font-size: 19px; font-weight: bold; color: #000; padding-bottom: 4px;}
        .school-address { font-size: 12px; padding-bottom: 3px; }
        .eiin-code { font-size: 11px; font-weight: bold; }
        .progress-title { font-size: 16px; font-weight: bold; color: #000; text-decoration: underline; margin-top: 10px; }
        
        /* Top Right Grading Table */
        .grading-table { width: 145px; font-size: 9px; margin-left: auto; margin-right: 0; margin-top: 0; }
        .grading-table th, .grading-table td { padding: 2px; }
        
        /* Student Info Table */
        .student-info th { background-color: #f2f2f2; width: 15%; text-align: left; color: #000; padding: 4px 6px; }
        .student-info td { text-align: left; width: 35%; font-weight: bold; padding: 4px 6px; }
        
        /* Main Marks Table */
        .marks-table th { background-color: #2a5a3b; color: white; padding: 6px 4px; font-size: 10px; border: 1px solid #1c3e28; }
        .marks-table td { font-size: 11px; font-weight: bold; padding: 5px 4px; }
        .subject-name { text-align: left !important; padding-left: 5px; }
        
        /* Bottom wrapper for final elements */
        .bottom-wrapper { width: 100%; border: none; margin-top: 5px; page-break-inside: avoid; }
        .bottom-wrapper td { border: none; padding: 0 4px 0 0; vertical-align: top; }
        .bottom-wrapper table { margin-bottom: 0; }
        
        /* Small Bottom Tables */
        .small-table th { background-color: #2a5a3b; color: white; font-size: 10px; padding: 4px; }
        .small-table td { text-align: left; padding: 4px; font-size: 10px; }
        .small-table .val { text-align: center; font-weight: bold; }
        
        .comments-box { height: 60px; border: 1px solid #777; padding: 5px; text-align: left; font-size: 10px; border-top: none;}
        
        /* Signatures */
        .signatures { width: 100%; margin-top: 50px; border: none; page-break-inside: avoid; }
        .signatures td { border: none; text-align: center; width: 33.33%; font-weight: bold; font-size: 11px; padding-top: 10px; }
        .sign-line { border-top: 1px dashed #000; display: inline-block; width: 140px; padding-top: 4px; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="marksheet-container">
        
        <table class="header-table">
            <tr>
                <td style="width: 20%; text-align: center; vertical-align: middle;">
                    @php
                        $imagePath = public_path('img/logo.svg');
                        $imageData = base64_encode(file_get_contents($imagePath));
                        $imageSrc = 'data:image/svg+xml;base64,' . $imageData;
                    @endphp
                    <img src="{{ $imageSrc }}" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid #2a5a3b; padding: 2px;" alt="School Logo" />
                </td>
                
                <td style="width: 60%; text-align: center; vertical-align: middle;">
                    <div class="school-title">Pabna International School</div>
                    <div class="school-address">{{ $student->branch->branch_name ?? 'Zilla Para, Pabna Sadar, Pabna' }}</div>
                    <div class="eiin-code">EIIN: 451211 &nbsp; | &nbsp; School Code: 451211</div>
                    <div class="progress-title">Progress Report</div>
                </td>
                
                <td style="width: 20%; vertical-align: top;">
                    <table class="grading-table">
                        <tr class="bg-green">
                            <th>Range</th>
                            <th>Grade</th>
                            <th>GPA</th>
                        </tr>
                        <tr><td>80-100</td><td>A+</td><td>5.00</td></tr>
                        <tr><td>70-79</td><td>A</td><td>4.00</td></tr>
                        <tr><td>60-69</td><td>A-</td><td>3.50</td></tr>
                        <tr><td>50-59</td><td>B</td><td>3.00</td></tr>
                        <tr><td>40-49</td><td>C</td><td>2.00</td></tr>
                        <tr><td>0-39</td><td>F</td><td>0.00</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="student-info">
            <tr>
                <th>Student Name:</th>
                <td style="text-transform: uppercase;">{{ $student->student_name ?? $student->first_name.' '.$student->last_name }}</td>
                <th>Roll No:</th>
                <td>{{ $student->student_identity }}</td>
            </tr>
            <tr>
                <th>Father's Name:</th>
                <td style="text-transform: uppercase;">{{ $student->father_name ?? 'N/A' }}</td>
                <th>Group:</th>
                <td>{{ $student->group ?? 'General' }}</td>
            </tr>
            <tr>
                <th>Mother's Name:</th>
                <td style="text-transform: uppercase;">{{ $student->mother_name ?? 'N/A' }}</td>
                <th>Exam:</th>
                <td>{{ $exam->name }}</td>
            </tr>
            <tr>
                <th>Student ID:</th>
                <td>{{ $student->student_identity }}</td>
                <th>Year/Session:</th>
                <td>{{ $student->sessionYear->session_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Class:</th>
                <td colspan="3">{{ $student->schoolClass->class_name ?? 'Five' }}</td>
            </tr>
        </table>

        <table class="marks-table">
            <thead>
                <tr>
                    <th rowspan="2">Code</th>
                    <th rowspan="2" style="width: 25%;">Subject Name</th>
                    <th rowspan="2">Full Marks</th>
                    <th rowspan="2">Highest Marks</th>
                    <th colspan="4">Obtaining Marks</th>
                    <th rowspan="2">Total Marks</th>
                    <th rowspan="2">Letter Grade</th>
                    <th rowspan="2">Grade Point</th>
                </tr>
                <tr>
                    <th>MCQ</th>
                    <th>WRITEN</th>
                    <th>CA</th>
                    <th>CT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($marks as $mark)
                <tr>
                    <td>{{ $mark->subject->subject_code ?? '101' }}</td>
                    <td class="subject-name">{{ $mark->subject->subject_name }}</td>
                    <td>100</td> 
                    <td>-</td>
                    <td>{{ $mark->mcq_mark > 0 ? $mark->mcq_mark : '-' }}</td>
                    <td>{{ $mark->written_mark > 0 ? $mark->written_mark : '-' }}</td>
                    <td>-</td>
                    <td>{{ $mark->ct_mark > 0 ? $mark->ct_mark : '-' }}</td>
                    <td>{{ $mark->total_mark }}</td>
                    <td style="color: {{ ($mark->letter_grade == 'F' || $mark->letter_grade == 'Fail') ? 'red' : 'black' }};">{{ $mark->letter_grade }}</td>
                    <td>{{ number_format($mark->grade_point, 2) }}</td>
                </tr>
                @endforeach
                
                <tr style="background-color: #f2f2f2;">
                    <td colspan="2" class="subject-name" style="text-align: right !important; padding-right: 10px;">Total Exam Marks</td>
                    <td>{{ $marks->count() * 100 }}</td>
                    <td colspan="5" class="subject-name" style="text-align: right !important; padding-right: 10px;">Obtained Marks & GPA</td>
                    <td style="color: #2a5a3b; font-size: 12px;">{{ $total_marks }}</td>
                    <td style="color: {{ $final_grade == 'F' ? 'red' : '#2a5a3b' }}; font-size: 12px;">{{ $final_grade }}</td>
                    <td style="color: {{ $cgpa == '0.00' ? 'red' : '#2a5a3b' }}; font-size: 12px;">{{ $cgpa }}</td>
                </tr>
            </tbody>
        </table>

        <table class="bottom-wrapper">
            <tr>
                <td style="width: 33%;">
                    <table class="small-table">
                        <tr><th colspan="2">Result Status</th></tr>
                        <tr><td>Status</td><td class="val" style="color: {{ $final_grade == 'F' ? 'red' : 'green' }};">{{ $final_grade == 'F' ? 'Failed' : 'Passed' }}</td></tr>
                        <tr><td>Section Position</td><td class="val">N/A</td></tr>
                        <tr><td>GPA [Without 4th]</td><td class="val">{{ $cgpa }}</td></tr>
                        <tr><td>Failed Subject [s]</td><td class="val">{{ $marks->whereIn('letter_grade', ['F', 'Fail'])->count() }}</td></tr>
                        <tr><td>Working Day</td><td class="val">N/A</td></tr>
                        <tr><td>Total Present</td><td class="val">N/A</td></tr>
                        <tr><td>Total Absent</td><td class="val">N/A</td></tr>
                    </table>
                </td>
                
                <td style="width: 33%;">
                    <table class="small-table">
                        <tr><th colspan="2">Moral & Behavior Evaluation</th></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Excellent</td></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Good</td></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Average</td></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Poor</td></tr>
                    </table>
                    <div class="comments-box">
                        <strong>Comments:</strong>
                    </div>
                </td>
                
                <td style="width: 33%; padding-right: 0;">
                    <table class="small-table">
                        <tr><th colspan="2">Co-Curricular Activities</th></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Sports</td></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Cultural Function</td></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Scout/BNCC</td></tr>
                        <tr><td style="width: 15px; border-right: none;"><div style="width: 8px; height: 8px; border: 1px solid #000;"></div></td><td style="border-left: none;">Math Olympiad</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="signatures">
            <tr>
                <td><span class="sign-line">Guardian</span></td>
                <td><span class="sign-line">Class Teacher</span></td>
                <td><span class="sign-line">Principal</span></td>
            </tr>
        </table>

    </div>
</div>

</body>
</html>