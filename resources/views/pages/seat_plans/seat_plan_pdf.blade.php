<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Seat Plan Cards</title>
    <style>
        /* 🚨 A4 Page Setup: একদম ব্যালেন্সড মার্জিন 🚨 */
        @page { size: A4 portrait; margin: 25px; } 
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0; padding: 0; color: #111827; }
        
        /* 🚨 Main Grid Layout: Fixed Table Layout 🚨 */
        .main-grid { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; /* এই প্রপার্টি টেবিলকে বাইরে বের হতে দেবে না */
        }
        .main-grid > tbody > tr { page-break-inside: avoid; }
        
        /* 48% + 4% + 48% = 100% Perfect Split */
        .card-td { width: 48%; vertical-align: top; padding: 0; } 
        .gap-td { width: 4%; } 
        
        /* Individual Card Design */
        .seat-card { 
            border: 2px solid #1e4630; 
            border-radius: 10px; 
            padding: 10px; 
            background: #ffffff; 
            width: 100%; 
            height: 205px; 
            box-sizing: border-box;
            overflow: hidden; /* ভেতরের কিছু বড় হলেও কার্ড ভাঙবে না */
        }
        
        /* Inner Table - Fixed Layout */
        .card-inner-table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
        }
        .card-inner-table td { border: none; padding: 0; vertical-align: top; }
        
        /* Photo & Logo */
        .student-photo { width: 56px; height: 68px; border: 2px solid #cc0000; object-fit: cover; border-radius: 6px; text-align: center; line-height: 68px; font-size: 10px; color: #777; background: #f9f9f9; }
        .school-logo { width: 46px; height: 46px; border-radius: 50%; border: 1px solid #e5e7eb; padding: 2px; background: #fff; margin: 0 auto;}
        
        /* Center Info */
        .center-content { text-align: center; padding: 0 6px; }
        .school-name { color: #1e4630; font-weight: 900; font-size: 13px; line-height: 1.1; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px;}
        .badge-wrapper { text-align: center; margin-bottom: 6px; }
        .badge { border: 1px solid #1e4630; border-radius: 10px; display: inline-block; font-size: 9px; font-weight: bold; padding: 2px 10px; color: #1e4630; background-color: #f0fdf4; text-transform: uppercase; letter-spacing: 0.5px;}
        
        /* Details Table inside Card */
        .details-table { width: 100%; border-collapse: collapse; margin-top: 4px; table-layout: fixed;}
        .details-table td { padding: 4px 0px; text-align: left; border-bottom: 1px dashed #e5e7eb; vertical-align: top; line-height: 1.25;}
        .details-table tr:last-child td { border-bottom: none; padding-bottom: 0; }
        
        .details-table .label { font-weight: bold; color: #6b7280; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.2px;}
        .details-table .val { font-weight: 900; color: #111827; text-transform: uppercase; font-size: 10px; word-wrap: break-word;}
        
        /* Premium Roll Box */
        .roll-box-wrapper { text-align: right; padding-left: 2px; }
        .roll-box { border: 2px solid #1e4630; text-align: center; margin-top: 8px; border-radius: 6px; overflow: hidden; display: inline-block; width: 55px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-left: auto; margin-right: 0;}
        .roll-title { background: #1e4630; color: #fff; font-size: 9px; font-weight: bold; padding: 3px 0; text-transform: uppercase; letter-spacing: 1px;}
        .roll-number { font-size: 18px; font-weight: 900; padding: 5px 0; color: #cc0000; }
    </style>
</head>
<body>

    @php
        $logoPath = public_path('img/logo.svg');
        $logoSrc = '';
        if(file_exists($logoPath)){
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoSrc = 'data:image/svg+xml;base64,' . $logoData;
        }
    @endphp

    <table class="main-grid">
        <tbody>
            @foreach($students->chunk(2) as $chunk)
            <tr>
                @foreach($chunk as $student)
                    @php
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

                    <td class="card-td">
                        <div class="seat-card">
                            <table class="card-inner-table">
                                <tr>
                                    <!-- Photo: 18% Width -->
                                    <td style="width: 18%; text-align: left; padding-top: 2px;">
                                        @if($studentPhotoSrc)
                                            <img src="{{ $studentPhotoSrc }}" class="student-photo" alt="Photo" />
                                        @else
                                            <div class="student-photo">Photo</div>
                                        @endif
                                    </td>
                                    
                                    <!-- Details: 60% Width -->
                                    <td style="width: 60%;" class="center-content">
                                        <div class="school-name">{{ $branch->branch_name ?? 'PABNA INTERNATIONAL SCHOOL' }}</div>
                                        
                                        <div class="badge-wrapper">
                                            <div class="badge">Exam Seat Plan</div>
                                        </div>
                                        
                                        <table class="details-table">
                                            <tr>
                                                <td style="width: 58%;"><span class="label">Name:</span> <span class="val" style="color: #cc0000;">{{ $student->student_name ?? $student->first_name }}</span></td>
                                                <td style="width: 42%;"><span class="label">ID:</span> <span class="val">{{ $student->student_identity }}</span></td>
                                            </tr>
                                            <tr>
                                                <td><span class="label">Class:</span> <span class="val">{{ $schoolClass->class_name ?? 'N/A' }}</span></td>
                                                <td><span class="label">Shift:</span> <span class="val">{{ $student->shift->shift_name ?? 'N/A' }}</span></td>
                                            </tr>
                                            <tr>
                                                <td><span class="label">Section:</span> <span class="val">{{ $student->section->section_name ?? 'N/A' }}</span></td>
                                                <td><span class="label">Session:</span> <span class="val">{{ $session->session_name ?? 'N/A' }}</span></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2"><span class="label">Exam:</span> <span class="val">{{ $exam->name ?? 'N/A' }}</span></td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- Logo & Roll: 22% Width -->
                                    <td style="width: 22%;" class="roll-box-wrapper">
                                        <div style="text-align: right; width: 100%;">
                                            @if($logoSrc)
                                                <img src="{{ $logoSrc }}" class="school-logo" alt="Logo" style="display: block; margin-left: auto; margin-right: 0;"/>
                                            @else
                                                <div class="school-logo" style="border: 1px solid #777; line-height: 46px; font-size: 9px; text-align: center; display: block; margin-left: auto; margin-right: 0;">LOGO</div>
                                            @endif
                                        </div>
                                        
                                        <div class="roll-box">
                                            <div class="roll-title">Roll</div>
                                            <div class="roll-number">{{ $student->roll_number }}</div> 
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    
                    @if($loop->first && $chunk->count() > 1)
                        <td class="gap-td"></td>
                    @endif
                @endforeach
                
                @if($chunk->count() == 1)
                    <td class="gap-td"></td>
                    <td class="card-td"></td>
                @endif
            </tr>
            <tr><td colspan="3" style="height: 15px; border: none;"></td></tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>