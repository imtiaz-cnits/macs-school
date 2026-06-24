<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Admit Cards - {{ $schoolClass->class_name }}</title>
    <style>
        /* A4 Page Setup */
        @page { size: A4 portrait; margin: 20px; }
        body { font-family: 'Helvetica', sans-serif; margin: 0; padding: 0; color: #000; font-size: 11px; }
        
        /* 🚨 3 Cards per page - Fixed Pixel Height for perfect fit */
        .admit-card-box { 
            border: 2px dashed #2a5a3b; 
            padding: 10px;
            border-radius: 8px; 
            margin-bottom: 15px; 
            height: 320px; /* 🚨 পার্সেন্টেজের বদলে ফিক্সড পিক্সেল দেওয়া হলো */
            box-sizing: border-box;
            position: relative; /* Absolute সিগনেচারের জন্য */
            overflow: hidden; /* কনটেন্ট বড় হলেও বক্স বড় হবে না */
        }
        
        .page-break { page-break-after: always; }
        
        /* Header */
        .header-table { width: 100%; border: none; margin-bottom: 5px; }
        .header-table td { border: none; vertical-align: top; }
        .school-name { font-size: 14px; font-weight: bold; color: #000; text-transform: uppercase; }
        .school-address { font-size: 9px; margin-bottom: 3px;}
        .admit-title { background: #2a5a3b; color: #fff; font-size: 11px; font-weight: bold; padding: 3px 15px; border-radius: 20px; display: inline-block; margin-top: 2px; }
        
        /* Student Info Table */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .info-table td { padding: 2px; font-size: 11px; border: none; border-bottom: 1px dashed #ccc; }
        .info-table .label { font-weight: bold; width: 18%; color: #2a5a3b; }
        .info-table .val { width: 32%; font-weight: bold; text-transform: uppercase; }
        
        /* Routine Table */
        .routine-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; font-size: 9px; } /* ফন্ট কিছুটা কমানো হলো */
        .routine-table th { background: #f2f2f2; border: 1px solid #777; padding: 2px; color: #2a5a3b; }
        .routine-table td { border: 1px solid #777; padding: 2px; text-align: center; font-weight: bold;}
        
        /* Signatures - Positioned absolutely at the bottom */
        .signature-wrapper { position: absolute; bottom: 10px; left: 0; width: 100%; padding: 0 12px; box-sizing: border-box; }
        .signatures { width: 100%; border: none; }
        .signatures td { border: none; text-align: center; width: 33%; font-weight: bold; font-size: 10px; }
        .sign-line { border-top: 1px solid #000; display: inline-block; width: 100px; padding-top: 3px; }
        
        /* Photo Box */
        .photo-box { width: 55px; height: 65px; border: 1px solid #000; text-align: center; line-height: 65px; font-size: 10px; color: #777; background: #f9f9f9; float: right; object-fit: cover;}
    </style>
</head>
<body>

    @php
        // School Logo Fetching
        $logoPath = public_path('img/logo.svg');
        $logoSrc = '';
        if(file_exists($logoPath)){
            $logoData = base64_encode(file_get_contents($logoPath));
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
                    <td style="width: 15%; text-align: center;">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" style="width: 50px; height: 50px; border-radius: 50%;" alt="Logo" />
                        @else
                            <div style="width: 50px; height: 50px; border: 1px solid #000; border-radius: 50%; line-height: 50px;">LOGO</div>
                        @endif
                    </td>
                    <td style="width: 65%; text-align: center;">
                        <div class="school-name">Pabna International School</div>
                        <div class="school-address">{{ $branch->branch_name ?? 'Zilla Para, Pabna Sadar, Pabna' }}</div>
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
                    <td class="val" colspan="3" style="font-size: 12px;">{{ $student->student_name ?? $student->first_name.' '.$student->last_name }}</td>
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
                <div style="text-align: center; color: #777; font-size: 10px; margin-bottom: 5px;">(Exam Routine Not Published Yet)</div>
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