<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Admit Cards - {{ $schoolClass->class_name }}</title>
    <!-- Load Google Font Figtree -->
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Force Figtree font on all elements globally in the PDF */
        * { font-family: 'Figtree', 'Helvetica', 'Arial', sans-serif !important; }

        /* A4 Page Setup - Tight Margins to fit 3 cards per page */
        @page { size: A4 portrait; margin: 10px 15px; }
        body { font-family: 'Figtree', 'Helvetica', sans-serif; margin: 0; padding: 0; color: #000; font-size: 9.5px; }
        
        /* 🚨 3 Cards per page - Height optimized to 330px to fill A4 page beautifully */
        .admit-card-box { 
            border: 2px dashed #009A49; 
            padding: 8px 10px;
            border-radius: 10px; 
            margin-bottom: 12px; 
            height: 330px; /* 🚨 Increased height to cover page gap */
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }
        
        .page-break { page-break-after: always; }
        
        /* Header */
        .header-table { width: 100%; border: none; margin-bottom: 4px; }
        .header-table td { border: none; vertical-align: top; }
        .school-name { font-size: 18px; font-weight: 900; color: #002C53; text-transform: uppercase; letter-spacing: 0.5px; }
        .school-address { font-size: 8.5px; font-weight: bold; color: #555; margin-bottom: 1px; }
        .admit-title { background: #002C53; color: #fff; font-size: 9px; font-weight: 900; padding: 3px 12px; border-radius: 20px; display: inline-block; margin-top: 1px; text-transform: uppercase; letter-spacing: 0.8px; }
        
        /* Student Info Table */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .info-table td { padding: 1.5px 3px; font-size: 9px; border: none; border-bottom: 1px dashed #e2e8f0; }
        .info-table .label { font-weight: bold; width: 18%; color: #009A49; }
        .info-table .val { width: 32%; font-weight: bold; text-transform: uppercase; color: #1e293b; }
        
        /* Allowance Text */
        .allowance-text { text-align: left; font-size: 11px; font-weight: bold; color: #0f172a; margin: 3px 0 5px 0; font-style: italic; }
        
        /* Routine Table */
        .routine-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 9px; }
        .routine-table th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 3px 4px; color: #009A49; font-weight: 900; text-transform: uppercase; }
        .routine-table td { border: 1px solid #cbd5e1; padding: 3.5px 4px; text-align: center; font-weight: bold; color: #334155; }
        
        /* Signatures - Positioned absolutely at the bottom */
        .signature-wrapper { position: absolute; bottom: 8px; left: 0; width: 100%; padding: 0 10px; box-sizing: border-box; }
        .signatures { width: 100%; border: none; }
        .signatures td { border: none; font-weight: bold; font-size: 9px; color: #475569; }
        .sign-line { border-top: 1px solid #94a3b8; display: inline-block; width: 120px; padding-top: 3px; text-align: center; }
        
        /* Photo Box */
        .photo-box { width: 50px; height: 60px; border: 1px solid #cbd5e1; text-align: center; line-height: 60px; font-size: 9px; color: #64748b; background: #f8fafc; float: right; object-fit: cover; border-radius: 5px; }
    </style>
</head>
<body>

    @php
        // Subject translation helper function
        if (!function_exists('translateSubject')) {
            function translateSubject($name) {
                $translations = [
                    'বাংলা' => 'Bangla',
                    'ইংরেজী' => 'English',
                    'ইংরেজি' => 'English',
                    'গণিত' => 'Mathematics',
                    'আরবী / ধর্মশিক্ষা' => 'Arabic & Religion',
                    'ড্রইং' => 'Drawing',
                    'সাধারণ জ্ঞান' => 'General Knowledge',
                    'সমাজ' => 'Social Science',
                    'বাংলাদেশ ও বিশ্বপরিচয়' => 'Bangladesh & Global Studies',
                    'বিজ্ঞান' => 'Science',
                    'ইসলাম / হিন্দু শিক্ষা' => 'Islam & Hinduism Education',
                    'ইসলাম ও নৈতিক শিক্ষা' => 'Islam & Moral Education',
                    'বাংলা ১ম পত্র' => 'Bangla 1st Paper',
                    'বাংলা ২য় পত্র' => 'Bangla 2nd Paper',
                    'ইংরেজী ১ম পত্র' => 'English 1st Paper',
                    'ইংরেজী ২য় পত্র' => 'English 2nd Paper',
                    'ইসলাম শিক্ষা' => 'Islamic Studies',
                    'তথ্য ও যোগাযোগ প্রযুক্তি' => 'ICT',
                    'কৃষি শিক্ষা' => 'Agriculture Studies',
                    'সামাজিক বিজ্ঞান' => 'Social Science',
                    'সাধারণ গণিত' => 'General Mathematics',
                    'জীববিজ্ঞান / ভূগোল' => 'Biology / Geography',
                    'রসায়ন / অর্থনীতি' => 'Chemistry / Economics',
                    'পদার্থ / ইতিহাস' => 'Physics / History',
                    'বাংলাদেশ ও বিশ্বপরিচয় / সাধারণ বিজ্ঞান' => 'Bangladesh & Global Studies / General Science',
                    'উচ্চতর গণিত / কৃষি শিক্ষা' => 'Higher Mathematics / Agriculture Studies',
                    'S.B.A' => 'S.B.A',
                    'শারীরিক শিক্ষা' => 'Physical Education',
                ];

                $cleanName = trim($name);
                if (isset($translations[$cleanName])) {
                    return $translations[$cleanName];
                }

                // Fallback replacement logic for compound strings
                $replaced = $cleanName;
                foreach ($translations as $bn => $en) {
                    $replaced = str_replace($bn, $en, $replaced);
                }
                return $replaced;
            }
        }

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

        // Principal Signature Robust Fetching
        $signaturePath = public_path('img/signature.png');
        $signatureSrc = '';
        if(file_exists($signaturePath)){
            $sigData = base64_encode(file_get_contents($signaturePath));
            $signatureSrc = 'data:image/png;base64,' . $sigData;
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
                            <img src="{{ $logoSrc }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: contain;" alt="Logo" />
                        @else
                            <div style="width: 40px; height: 40px; border: 1px solid #cbd5e1; border-radius: 50%; line-height: 40px; text-align: center; font-size: 8px; color: #64748b; font-weight: bold;">LOGO</div>
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
                    <td class="val" colspan="3" style="font-size: 10px; color: #000;">{{ $student->student_name ?? $student->first_name.' '.$student->last_name }}</td>
                </tr>
                <tr>
                    <td class="label">Roll Number:</td>
                    <td class="val">{{ $student->roll_number }}</td>
                    <td class="label">Student ID:</td>
                    <td class="val">{{ $student->student_identity }}</td>
                </tr>
                <tr>
                    <td class="label">Class:</td>
                    <td class="val">{{ $schoolClass->class_name }}</td>
                    <td class="label">Section:</td>
                    <td class="val">{{ $student->section->section_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Session:</td>
                    <td class="val">{{ $session->session_name }}</td>
                    <td class="label">Shift:</td>
                    <td class="val">{{ $student->shift->shift_name ?? 'N/A' }}</td>
                </tr>
            </table>

            @php
                $examName = $exam->name;
                $examNameClean = str_ireplace('Exam', 'Examination', $examName);
                if (strpos($examNameClean, '-') === false) {
                    $examNameClean = preg_replace('/\s+(\d{4})$/', ' - $1', $examNameClean);
                }
            @endphp
            <div class="allowance-text">
                The mentioned student has been allowed to participate in the {{ $examNameClean }}.
            </div>

             @if($routines->count() > 0)
                @if($routines->count() > 5)
                    @php
                        $half = ceil($routines->count() / 2);
                        $chunks = $routines->chunk($half);
                    @endphp
                    <table style="width: 100%; border: none; margin: 0; padding: 0; border-collapse: collapse;">
                        <tr>
                            <td style="width: 49%; vertical-align: top; padding: 0; border: none;">
                                @if(isset($chunks[0]))
                                <table class="routine-table" style="margin-bottom: 0;">
                                    <tbody>
                                        @foreach($chunks[0] as $routine)
                                        <tr>
                                            <td style="width: 35%;">{{ \Carbon\Carbon::parse($routine->exam_date)->format('d M Y') }}</td>
                                            <td style="width: 65%; text-align: left; padding-left: 5px;">{{ translateSubject($routine->subject->subject_name) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @endif
                            </td>
                            <td style="width: 2%; border: none; padding: 0;"></td> <!-- Spacer -->
                            <td style="width: 49%; vertical-align: top; padding: 0; border: none;">
                                @if(isset($chunks[1]))
                                <table class="routine-table" style="margin-bottom: 0;">
                                    <tbody>
                                        @foreach($chunks[1] as $routine)
                                        <tr>
                                            <td style="width: 35%;">{{ \Carbon\Carbon::parse($routine->exam_date)->format('d M Y') }}</td>
                                            <td style="width: 65%; text-align: left; padding-left: 5px;">{{ translateSubject($routine->subject->subject_name) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @endif
                            </td>
                        </tr>
                    </table>
                @else
                    <table class="routine-table">
                        <tbody>
                            @foreach($routines as $routine)
                            <tr>
                                <td style="width: 30%;">{{ \Carbon\Carbon::parse($routine->exam_date)->format('d M Y') }}</td>
                                <td style="width: 70%; text-align: left; padding-left: 8px;">{{ translateSubject($routine->subject->subject_name) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @else
                <div style="text-align: center; color: #94a3b8; font-size: 8.5px; margin: 4px 0;">(Exam Routine Not Published Yet)</div>
            @endif

            <div class="signature-wrapper">
                <table class="signatures">
                    <tr>
                        <!-- Class Teacher Signature Area -->
                        <td style="width: 50%; text-align: left; padding-left: 15px; vertical-align: bottom;">
                            <div style="display: inline-block; text-align: center; width: 120px;">
                                <div style="height: 30px;"></div> 
                                <span class="sign-line">Class Teacher</span>
                            </div>
                        </td>
                        
                        <!-- Principal Signature Area -->
                        <td style="width: 50%; text-align: right; padding-right: 15px; vertical-align: bottom;">
                            <div style="display: inline-block; text-align: center; width: 120px;">
                                @if($signatureSrc)
                                    <img src="{{ $signatureSrc }}" style="max-height: 32px; width: auto; margin-bottom: 2px;" alt="Principal Signature" /><br>
                                @else
                                    <div style="height: 30px;"></div>
                                @endif
                                <span class="sign-line">Principal</span>
                            </div>
                        </td>
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