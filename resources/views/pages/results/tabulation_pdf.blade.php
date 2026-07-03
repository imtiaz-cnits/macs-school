<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Tabulation Sheet - {{ $schoolClass->class_name }}</title>
    <style>
        /* 🚨 Import Google Font Roboto 🚨 */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');

        /* Legal Landscape Mode */
        @page { size: legal landscape; margin: 15px; }
        body { font-family: 'Roboto', sans-serif; font-size: 9.5px; color: #1e293b; margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .header-table { width: 100%; border: none; margin-bottom: 15px; }
        .header-table td { border: none; text-align: center; vertical-align: middle; }
        
        .school-title { 
            font-size: 20px; 
            font-weight: 900; 
            background: #009A49; /* MACS Green Accent Title Banner */
            color: white; 
            display: inline-block; 
            padding: 6px 24px; 
            border-radius: 8px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .school-address { font-size: 12px; margin-top: 5px; font-weight: 700; color: #475569; }
        .info-text { font-size: 10px; margin-top: 3px; color: #64748b; font-weight: 500; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 4px; text-align: center; font-size: 9px; }
        th { background-color: #f8fafc; font-weight: 700; color: #334155; text-transform: uppercase; font-size: 8.5px; }
        
        .student-info { text-align: left; width: 130px; font-weight: 750; padding-left: 6px; color: #0f172a; }
        .highlight { font-weight: 700; color: #0f172a; }
        .fail { color: #ef4444; font-weight: 800; }
        
        .footer-text { margin-top: 20px; font-size: 9px; color: #94a3b8; font-weight: 500; }
    </style>
</head>
<body>

    @php
        $logoPath = public_path('img/macs_logo.jpeg');
        $logoSrc = '';
        if(file_exists($logoPath)){
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoSrc = 'data:image/jpeg;base64,' . $logoData;
        } else {
            // Fallback to logo.svg if jpeg is not found
            $fallbackPath = public_path('img/logo.svg');
            if(file_exists($fallbackPath)){
                $logoData = base64_encode(file_get_contents($fallbackPath));
                $logoSrc = 'data:image/svg+xml;base64,' . $logoData;
            }
        }
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: left;">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" style="width: 70px; height: 70px; border-radius: 50%; border: 1px solid #e2e8f0; padding: 2px;" alt="Logo" />
                @else
                    <div style="width: 70px; height: 70px; border-radius: 50%; border: 1px solid #e2e8f0; line-height: 70px; text-align: center; font-weight: bold; color: #94a3b8; font-size: 10px;">LOGO</div>
                @endif
            </td>
            <td style="width: 70%;">
                <div class="school-title">{{ config('app.name', 'Pabna International School') }}</div>
                <div class="school-address">{{ $branch->branch_name ?? 'Pabna Sadar, Pabna' }}</div>
                <div class="info-text">EIIN: 451211 | School Code: 451211</div>
                <div class="info-text" style="text-decoration: underline; font-weight: 700; color: #008ED6; margin-top: 5px; font-size: 11px;">
                    Tabulation Sheet - {{ $exam->name }} | Class: {{ $schoolClass->class_name }}
                </div>
            </td>
            <td style="width: 15%; text-align: right;">
                <div style="border: 1.5px solid #008ED6; color: #008ED6; display: inline-block; padding: 5px 12px; border-radius: 8px; font-weight: 700; font-size: 10px;">
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
                    <span style="color: #64748b; font-size: 8px; font-weight: 500;">Roll: {{ $data->student->student_identity }}</span>
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
                <td colspan="100%" style="padding: 20px; font-weight: bold; color: #ef4444;">No Data Found for this Class & Exam!</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-text">
        &copy; {{ date('Y') }}. All Rights Reserved by PCMSC.
    </div>

</body>
</html>