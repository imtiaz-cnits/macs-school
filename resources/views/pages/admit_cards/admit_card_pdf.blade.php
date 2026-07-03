<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Admit Cards - {{ $schoolClass->class_name }}</title>
    <style>
        /* Load Google Font Figtree */
        @import url('https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700;900&display=swap');

        /* A4 Page Setup - Tight Margins to fit 3 cards per page */
        @page { size: A4 portrait; margin: 15px 20px; }
        body { font-family: 'Figtree', 'Helvetica', sans-serif; margin: 0; padding: 0; color: #000; font-size: 10px; }
        
        /* 🚨 3 Cards per page - Height optimized to 255px to fit perfectly on A4 without spilling */
        .admit-card-box { 
            border: 2px dashed #009A49; 
            padding: 8px 10px;
            border-radius: 10px; 
            margin-bottom: 8px; 
            height: 255px; /* 🚨 Optimized height for 3 cards per page */
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }
        
        .page-break { page-break-after: always; }
        
        /* Header */
        .header-table { width: 100%; border: none; margin-bottom: 3px; }
        .header-table td { border: none; vertical-align: top; }
        .school-name { font-size: 13px; font-weight: 900; color: #002C53; text-transform: uppercase; letter-spacing: 0.5px; }
        .school-address { font-size: 8px; font-weight: bold; color: #555; margin-bottom: 2px; }
        .admit-title { background: #002C53; color: #fff; font-size: 9px; font-weight: 900; padding: 3px 12px; border-radius: 20px; display: inline-block; margin-top: 1px; text-transform: uppercase; letter-spacing: 0.8px; }
        
        /* Student Info Table */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .info-table td { padding: 2px 2px; font-size: 10px; border: none; border-bottom: 1px dashed #e2e8f0; }
        .info-table .label { font-weight: bold; width: 18%; color: #009A49; }
        .info-table .val { width: 32%; font-weight: bold; text-transform: uppercase; color: #1a1a1a; }
        
        /* Routine Table */
        .routine-table { width: 100%; border-collapse: collapse; margin-bottom: 3px; font-size: 8px; }
        .routine-table th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 3px 2px; color: #009A49; font-weight: 900; text-transform: uppercase; }
        .routine-table td { border: 1px solid #cbd5e1; padding: 3px 2px; text-align: center; font-weight: bold; color: #334155; }
        
        /* Signatures - Positioned absolutely at the bottom */
        .signature-wrapper { position: absolute; bottom: 8px; left: 0; width: 100%; padding: 0 10px; box-sizing: border-box; }
        .signatures { width: 100%; border: none; }
        .signatures td { border: none; text-align: center; width: 33%; font-weight: bold; font-size: 9px; color: #475569; }
        .sign-line { border-top: 1px solid #94a3b8; display: inline-block; width: 110px; padding-top: 3px; }
        
        /* Photo Box */
        .photo-box { width: 50px; height: 60px; border: 1px solid #cbd5e1; text-align: center; line-height: 60px; font-size: 9px; color: #64748b; background: #f8fafc; float: right; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>

    @php
        // School Logo Robust Fetching (Checking MACS logo first, falling back to SVG)
        $logoPathJpg = public_path('img/macs_logo.jpeg');
        $logoPathSvg = public_path('img/logo.svg');
        $logoSrc = '';
        if(file_exists($logoPathJpg)){
            $logoData = base64_encode(file_get_contents($logoPathJpg));
            $logoSrc = 'data:image/jpeg;base64,' . $logoData;
        } elseif(file_exists($logoPathSvg)){
            $logoData = base64_encode(file_get_contents($logoPathSvg));
            $logoSrc = 'data:image/svg+xml;base64,' . $logoData;
        }
    @endphp

    @foreach($students as $index => $student)
        
        @php
            // Student Photo Robust Fetching
            $photoVal = $student->photo;
            $studentPhotoSrc = '';
            
            if ($photoVal) {
                $possiblePaths = [
                    public_path($photoVal),
                    public_path('storage/' . $photoVal),
                    storage_path('app/public/' . $photoVal),
                    public_path('student_photos/' . basename($photoVal))
                ];

                foreach($possiblePaths as $path) {
                    if(file_exists($path) && is_file($path)) {
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        $studentPhotoData = base64_encode(file_get_contents($path));
                        $studentPhotoSrc = 'data:image/' . $ext . ';base64,' . $studentPhotoData;
                        break;
                    }
                }
            }
        @endphp

        <div class="admit-card-box">
            <table class="header-table">
                <tr>
                    <td style="width: 15%; text-align: center; vertical-align: middle;">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" style="width: 45px; height: 45px; border-radius: 50%; object-fit: contain;" alt="Logo" />
                        @else
                            <div style="width: 45px; height: 45px; border: 1px solid #cbd5e1; border-radius: 50%; line-height: 45px; text-align: center; font-size: 8px; color: #64748b; font-weight: bold;">LOGO</div>
                        @endif
                    </td>
                    <td style="width: 65%; text-align: center; vertical-align: top; padding-top: 1px;">
                        <div class="school-name">MACS School & College</div>
                        <div class="school-address">{{ $branch->branch_name ?? 'Main Branch' }}</div>
                        <div class="admit-title">ADMIT CARD - {{ strtoupper($exam->name) }}</div>
                    </td>
                    <td style="width: 20%; text-align: right; vertical-align: top;">
                        @if($studentPhotoSrc)
                            <img src="{{ $studentPhotoSrc }}" class="photo-box" alt="Student Photo" />
                        @else
                            <div class="photo-box">Photo</div>
                        @endif
                    </td>
                </tr>
            </table>

            <table class="info-table">
                <tr>
                    <td class="label">Student Name:</td>
                    <td class="val" colspan="3" style="font-size: 11px; color: #000;">{{ $student->student_name ?? $student->first_name.' '.$student->last_name }}</td>
                </tr>
                <tr>
                    <td class="label">Roll / ID:</td>
                    <td class="val">{{ $student->student_identity }}</td>
                    <td class="label">Class:</td>
                    <td class="val">{{ $schoolClass->class_name }}</td>
                </tr>
                <tr>
                    <td class="label">Session:</td>
                    <td class="val">{{ $session->session_name }}</td>
                    <td class="label">Section:</td>
                    <td class="val">{{ $student->section->section_name ?? 'N/A' }}</td>
                </tr>
            </table>

            @if($routines->count() > 0)
                <table class="routine-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Date</th>
                            <th style="width: 35%;">Subject</th>
                            <th style="width: 20%;">Start Time</th>
                            <th style="width: 20%;">End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($routines as $routine)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($routine->exam_date)->format('d M Y') }}</td>
                            <td style="text-align: left; padding-left: 5px;">{{ $routine->subject->subject_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($routine->start_time)->format('h:i A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($routine->end_time)->format('h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="text-align: center; color: #94a3b8; font-size: 9px; margin: 8px 0;">(Exam Routine Not Published Yet)</div>
            @endif

            <div class="signature-wrapper">
                <table class="signatures">
                    <tr>
                        <td><span class="sign-line">Student Signature</span></td>
                        <td><span class="sign-line">Class Teacher</span></td>
                        <td><span class="sign-line">Principal</span></td>
                    </tr>
                </table>
            </div>
        </div>

        @if(($index + 1) % 3 == 0 && !$loop->last)
            <div class="page-break"></div>
        @endif

    @endforeach

</body>
</html>