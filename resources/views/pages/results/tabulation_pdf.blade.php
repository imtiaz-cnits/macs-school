<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Tabulation Sheet - {{ $schoolClass->class_name }}</title>
    <style>
        /* Legal Landscape Mode */
        @page { size: legal landscape; margin: 15px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        
        .header-table { width: 100%; border: none; margin-bottom: 15px; }
        .header-table td { border: none; text-align: center; vertical-align: middle; }
        .school-title { font-size: 22px; font-weight: bold; background: #1c3e28; color: white; display: inline-block; padding: 5px 20px; border-radius: 10px; }
        .school-address { font-size: 14px; margin-top: 5px; font-weight: bold; }
        .info-text { font-size: 12px; margin-top: 3px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #777; padding: 4px; text-align: center; font-size: 10px; }
        th { background-color: #f6f6f6; font-weight: bold; }
        
        .student-info { text-align: left; width: 120px; font-weight: bold; padding-left: 5px; }
        .highlight { font-weight: bold; }
        .fail { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: left;">
                @php
                    $imagePath = public_path('img/logo.svg');
                    $imageData = base64_encode(file_get_contents($imagePath));
                    $imageSrc = 'data:image/svg+xml;base64,' . $imageData;
                @endphp
                <img src="{{ $imageSrc }}" style="width: 70px; height: 70px; border-radius: 50%;" alt="Logo" />
            </td>
            <td style="width: 70%;">
                <div class="school-title">Pabna International School</div>
                <div class="school-address">{{ $branch->branch_name ?? 'Pabna Sadar, Pabna' }}</div>
                <div class="info-text">EIIN: 451211 | School Code: 451211</div>
                <div class="info-text" style="text-decoration: underline; font-weight: bold; margin-top: 5px;">
                    Tabulation Sheet - {{ $exam->name }} | Class: {{ $schoolClass->class_name }}
                </div>
            </td>
            <td style="width: 15%; text-align: right;">
                <div style="border: 1px solid #000; display: inline-block; padding: 5px 10px; border-radius: 5px; font-weight: bold;">
                    Date: {{ date('d M Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Student Info.</th>
                @foreach($schedules as $schedule)
                    <th colspan="3">{{ $schedule->subject->subject_name }}</th>
                @endforeach
                <th rowspan="2">T.M</th>
                <th rowspan="2">G</th>
                <th rowspan="2">P</th>
                <th rowspan="2">PT</th>
            </tr>
            <tr>
                @foreach($schedules as $schedule)
                    <th>Written Mark</th>
                    <th>Oral/CT Mark</th>
                    <th>Total Mark</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($studentData as $index => $data)
            <tr>
                <td class="student-info">
                    {{ $data->student->student_name ?? 'Name N/A' }} <br>
                    <span style="color: #444;">Roll: {{ $data->student->student_identity }}</span>
                </td>
                
                @foreach($schedules as $schedule)
                    @php 
                        $mark = $data->marks->get($schedule->subject_id); 
                        $isFailed = $mark && ($mark->letter_grade == 'F' || $mark->letter_grade == 'Fail');
                    @endphp
                    
                    <td>{{ $mark && $mark->written_mark > 0 ? $mark->written_mark : '-' }}</td>
                    <td>{{ $mark && $mark->ct_mark > 0 ? $mark->ct_mark : '-' }}</td>
                    <td class="{{ $isFailed ? 'fail' : 'highlight' }}">
                        {{ $mark ? $mark->total_mark : '-' }}
                    </td>
                @endforeach

                <td class="highlight">{{ $data->grand_total }}</td>
                <td class="{{ $data->final_grade == 'F' ? 'fail' : 'highlight' }}">{{ $data->final_grade }}</td>
                <td class="{{ $data->cgpa == '0.00' ? 'fail' : 'highlight' }}">{{ $data->cgpa }}</td>
                <td class="highlight">{{ $index + 1 }}</td> </tr>
            @empty
            <tr>
                <td colspan="100%" style="padding: 20px; font-weight: bold; color: red;">No Data Found for this Class & Exam!</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 10px; color: #555;">
        &copy; {{ date('Y') }}. All Rights Reserved by PCMSC.
    </div>

</body>
</html>