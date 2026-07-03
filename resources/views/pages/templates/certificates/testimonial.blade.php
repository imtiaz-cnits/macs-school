<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* 🚨 Import Google Font Roboto 🚨 */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');

        /* A4 Landscape Zero Margin */
        @page { size: a4 landscape; margin: 0; }
        * { box-sizing: border-box; }

        body { 
            font-family: 'Roboto', sans-serif; 
            margin: 0; padding: 45px 60px; 
            color: #1e293b; background: #fff;
        }

        /* MACS Brand Borders */
        .border-outer {
            position: fixed; top: 15px; bottom: 15px; left: 15px; right: 15px;
            border: 12px solid #009A49; /* MACS Green */
            z-index: -2;
        }
        .border-inner {
            position: fixed; top: 32px; bottom: 32px; left: 32px; right: 32px;
            border: 2px solid #008ED6; /* MACS Sky Blue */
            z-index: -1;
        }

        /* Watermark */
        .watermark {
            position: fixed; top: 22%; left: 35%;
            width: 320px; opacity: 0.04; z-index: -3;
        }

        /* Header */
        .header { text-align: center; margin-bottom: 25px; margin-top: 5px; }
        .school-name { font-size: 34px; font-weight: 900; color: #008ED6; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .address { font-size: 13px; font-weight: 700; color: #475569; margin: 0 0 12px 0; }
        .title-badge { 
            background: #009A49; color: #fff; padding: 8px 50px; 
            border-radius: 50px; font-size: 17px; display: inline-block; font-weight: bold; text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Content */
        .content { 
            font-size: 18px; 
            line-height: 1.8; 
            text-align: justify; 
            padding: 0 20px; 
            margin-top: 20px;
            color: #1e293b;
        }
        
        .highlight { font-weight: 700; color: #0f172a; border-bottom: 1.5px dashed #008ED6; padding: 0 4px; }

        /* Signature Area */
        .footer-table { width: 100%; margin-top: 60px; padding: 0 20px; }
        
        .sig-box {
            border-top: 2px solid #1e293b; 
            width: 230px; 
            text-align: center;
            font-weight: 900; 
            font-size: 16px; 
            padding-top: 6px; 
            margin-left: auto;
            margin-right: 25px;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
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
            $fallbackPath = public_path('img/logo.svg');
            if(file_exists($fallbackPath)){
                $logoData = base64_encode(file_get_contents($fallbackPath));
                $logoSrc = 'data:image/svg+xml;base64,' . $logoData;
            }
        }
    @endphp

    <div class="border-outer"></div>
    <div class="border-inner"></div>
    
    @if($logoSrc)
        <img src="{{ $logoSrc }}" class="watermark">
    @endif

    <div class="header">
        @if($logoSrc)
            <img src="{{ $logoSrc }}" width="75" style="margin-bottom: 8px;">
        @endif
        <h1 class="school-name">{{ config('app.name', 'Pabna International School') }}</h1>
        <p class="address">{{ $student->branch->branch_name ?? 'Main Branch' }}</p>
        <div class="title-badge">Academic Testimonial</div>
    </div>

    <div class="content">
        This is to certify that <span class="highlight">{{ $student->student_name }}</span>, 
        son/daughter of <span class="highlight">{{ $student->father_name ?? '........................' }}</span> 
        and <span class="highlight">{{ $student->mother_name ?? '........................' }}</span>, 
        was a student of this prestigious institution in Class <span class="highlight">{{ $student->schoolClass->class_name ?? 'N/A' }}</span> 
        under the session <span class="highlight">{{ $student->sessionYear->session_name ?? '2026' }}</span>. <br><br>
        His/Her date of birth is recorded as <span class="highlight">{{ $student->dob ? date('d M, Y', strtotime($student->dob)) : '..................' }}</span>.
        To the best of my knowledge, he/she bears a good moral character and 
        never took part in any activities subversive of school discipline.
        <br><br>
        I wish him/her every success and a bright future in life.
    </div>

    <table class="footer-table">
        <tr>
            <td style="text-align: left; vertical-align: bottom; font-weight: 700; font-size: 15px; color: #008ED6;">
                Date: {{ date('d M, Y', strtotime($date)) }}
            </td>
            <td style="text-align: right; vertical-align: bottom;">
                <div class="sig-box">
                    PRINCIPAL SIGNATURE
                </div>
            </td>
        </tr>
    </table>
</body>
</html>