<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>General Certificate - {{ $student->student_name }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Georgia, serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #1E293B;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ══════════════════════════════════════════════════
           PRESTIGIOUS MULTI-TIER ARCHITECTURAL FRAME
        ══════════════════════════════════════════════════ */
        .frame-outer {
            position: fixed;
            top: 7mm;
            bottom: 7mm;
            left: 7mm;
            right: 7mm;
            border: 3.5px solid #00558F; /* MACS Royal Navy Blue */
            z-index: 10;
        }
        .frame-accent {
            position: fixed;
            top: 9.5mm;
            bottom: 9.5mm;
            left: 9.5mm;
            right: 9.5mm;
            border: 1px solid #C5A059; /* Classic Certificate Gold */
            z-index: 11;
        }
        .frame-inner {
            position: fixed;
            top: 11.5mm;
            bottom: 11.5mm;
            left: 11.5mm;
            right: 11.5mm;
            border: 1.5px solid #009A49; /* MACS Emerald Green */
            z-index: 12;
        }

        /* Classical Corner Brackets */
        .corner-accent {
            position: fixed;
            width: 14px;
            height: 14px;
            z-index: 15;
        }
        .corner-tl { top: 9.5mm; left: 9.5mm; border-top: 2.5px solid #C5A059; border-left: 2.5px solid #C5A059; }
        .corner-tr { top: 9.5mm; right: 9.5mm; border-top: 2.5px solid #C5A059; border-right: 2.5px solid #C5A059; }
        .corner-bl { bottom: 9.5mm; left: 9.5mm; border-bottom: 2.5px solid #C5A059; border-left: 2.5px solid #C5A059; }
        .corner-br { bottom: 9.5mm; right: 9.5mm; border-bottom: 2.5px solid #C5A059; border-right: 2.5px solid #C5A059; }

        /* Subtle Center Watermark */
        .watermark {
            position: fixed;
            top: 24%;
            left: 36%;
            width: 280px;
            opacity: 0.045;
            z-index: 1;
        }

        /* Main Certificate Content Container */
        .cert-container {
            position: relative;
            z-index: 5;
            padding: 20mm 26mm 16mm 26mm;
            text-align: center;
        }

        /* Top Meta Bar: Serial & Student ID */
        .meta-bar {
            width: 100%;
            margin-bottom: 6px;
        }
        .meta-bar td {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 9px;
            font-weight: 700;
            color: #64748B;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: none;
            padding: 0;
        }

        /* Institution Header */
        .logo-img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .school-title {
            font-size: 27px;
            font-weight: 900;
            color: #00558F;
            letter-spacing: 2px;
            margin: 0;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .school-subtitle {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            margin: 4px 0 0 0;
            letter-spacing: 0.6px;
        }
        .header-divider {
            width: 320px;
            height: 1.2px;
            background: #C5A059;
            margin: 7px auto 12px auto;
        }

        /* Certificate Title Banner */
        .title-box {
            display: inline-block;
            border-top: 1.5px solid #009A49;
            border-bottom: 1.5px solid #009A49;
            padding: 4px 28px;
            margin-bottom: 3px;
        }
        .title-text {
            font-size: 16px;
            font-weight: 900;
            color: #007A3A;
            letter-spacing: 3.5px;
            text-transform: uppercase;
        }
        .whom-text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 2.5px;
            color: #64748B;
            margin-top: 4px;
            margin-bottom: 14px;
            text-transform: uppercase;
        }

        /* Certify Statement */
        .certify-intro {
            font-size: 14px;
            font-style: italic;
            color: #475569;
            margin-bottom: 5px;
        }

        /* Student Name Hero */
        .student-name-hero {
            font-size: 23px;
            font-weight: 900;
            color: #0B2545;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin: 0 auto;
            line-height: 1.15;
        }
        .name-accent-line {
            width: 280px;
            height: 1.5px;
            background: #008ED6;
            margin: 4px auto 14px auto;
        }

        /* Certificate Prose Body */
        .body-paragraph {
            font-family: 'Times New Roman', Georgia, serif;
            font-size: 14.5px;
            line-height: 2.1;
            color: #1E293B;
            text-align: justify;
            padding: 0 10px;
            margin: 0 0 10px 0;
        }
        .field-highlight {
            font-weight: 800;
            color: #0F172A;
            border-bottom: 1px solid #CBD5E1;
            padding-bottom: 1px;
        }

        /* Authority & Signatures Section */
        .authority-table {
            width: 100%;
            margin-top: 32px;
            border-collapse: collapse;
        }
        .authority-table td {
            border: none;
            vertical-align: bottom;
            text-align: center;
            padding: 0 10px;
        }
        .sig-line-left {
            border-top: 1.2px solid #64748B;
            width: 165px;
            margin: 0 auto;
            padding-top: 5px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 9.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .sig-line-right {
            border-top: 1.5px solid #0F172A;
            width: 185px;
            margin: 0 auto;
            padding-top: 5px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 10px;
            font-weight: 900;
            color: #0F172A;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .sig-designation {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 8.5px;
            font-weight: 600;
            color: #64748B;
            margin-top: 2px;
        }

        /* Official Seal Medallion */
        .seal-circle {
            display: inline-block;
            width: 76px;
            height: 76px;
            border-radius: 50%;
            border: 2px dashed #C5A059;
            padding: 3px;
            margin: 0 auto;
        }
        .seal-inner {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            border: 1.2px solid #009A49;
            background: #F8FAFC;
            text-align: center;
            padding-top: 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 6.5px;
            font-weight: 800;
            color: #007A3A;
            line-height: 1.25;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    @php
        if (!isset($logoSrc) || !$logoSrc) {
            $logoPath = public_path('img/macs_logo.jpeg');
            $logoSrc = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : '';
        }
        if (!isset($signatureSrc) || !$signatureSrc) {
            $signaturePath = public_path('img/signature.png');
            $signatureSrc = file_exists($signaturePath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath)) : '';
        }
        if (!isset($certNo) || !$certNo) {
            $certNo = 'MACS/GC/' . ($student->sessionYear->session_name ?? date('Y')) . '/' . str_pad($student->id, 4, '0', STR_PAD_LEFT);
        }
        $dobFormatted = $student->dob ? date('d M, Y', strtotime($student->dob)) : 'N/A';
        $issueDateFormatted = date('d M, Y', strtotime($date));
    @endphp

    <!-- Ornamental Multi-tier Borders -->
    <div class="frame-outer"></div>
    <div class="frame-accent"></div>
    <div class="frame-inner"></div>

    <!-- Classical Corner Brackets -->
    <div class="corner-accent corner-tl"></div>
    <div class="corner-accent corner-tr"></div>
    <div class="corner-accent corner-bl"></div>
    <div class="corner-accent corner-br"></div>

    <!-- Watermark Logo -->
    @if($logoSrc)
        <img src="{{ $logoSrc }}" class="watermark" alt="Watermark" />
    @endif

    <div class="cert-container">
        <!-- Top Meta Bar -->
        <table class="meta-bar">
            <tr>
                <td style="text-align: left; width: 50%;">
                    CERTIFICATE NO: <span style="color: #0F172A;">{{ $certNo }}</span>
                </td>
                <td style="text-align: right; width: 50%;">
                    STUDENT ID: <span style="color: #0F172A;">{{ $student->student_identity }}</span>
                </td>
            </tr>
        </table>

        <!-- School Crest & Header -->
        @if($logoSrc)
            <img src="{{ $logoSrc }}" class="logo-img" alt="MACS Logo" />
        @endif
        <h1 class="school-title">{{ config('app.name', 'MACS School & College') }}</h1>
        <p class="school-subtitle">{{ $student->branch->branch_name ?? 'Main Branch' }} &bull; Pabna Sadar, Pabna &bull; Bangladesh &bull; Estd. 2005</p>
        <div class="header-divider"></div>

        <!-- Certificate Title -->
        <div class="title-box">
            <span class="title-text">GENERAL CERTIFICATE</span>
        </div>
        <div class="whom-text">TO WHOM IT MAY CONCERN</div>

        <!-- Certify Statement -->
        <div class="certify-intro">This is to certify that</div>
        <div class="student-name-hero">{{ $student->student_name }}</div>
        <div class="name-accent-line"></div>

        <!-- Certificate Body -->
        <p class="body-paragraph">
            Son/Daughter of <span class="field-highlight">{{ $student->father_name ?? '........................' }}</span> and <span class="field-highlight">{{ $student->mother_name ?? '........................' }}</span>, is/was a student of this prestigious institution. According to official academic records, he/she is/was studying in Class <span class="field-highlight">{{ $student->schoolClass->class_name ?? 'N/A' }}</span> under the academic session <span class="field-highlight">{{ $student->sessionYear->session_name ?? date('Y') }}</span>.
        </p>

        <p class="body-paragraph">
            His/Her date of birth according to the Admission Register is recorded as <span class="field-highlight">{{ $dobFormatted }}</span>.
        </p>

        <p class="body-paragraph">
            To the best of our knowledge, he/she bears a good moral character and did not take part in any activities subversive of the state or of school discipline.
        </p>

        <p class="body-paragraph" style="margin-bottom: 14px;">
            I wish him/her every success and a bright future in life.
        </p>

        <!-- Authority Signatures & Official Seal -->
        <table class="authority-table">
            <tr>
                <!-- Left: Date & In-charge Signature -->
                <td style="width: 32%; text-align: left;">
                    <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 9.5px; font-weight: 700; color: #00558F; margin-bottom: 26px;">
                        Date of Issue: {{ $issueDateFormatted }}
                    </div>
                    <div class="sig-line-left" style="margin-left: 0;">
                        PREPARED BY
                        <div class="sig-designation">Office Assistant</div>
                    </div>
                </td>

                <!-- Center: Official Seal Medallion -->
                <td style="width: 36%; text-align: center;">
                    <div class="seal-circle">
                        <div class="seal-inner">
                            MACS<br>
                            OFFICIAL<br>
                            SEAL<br>
                            ESTD 2005
                        </div>
                    </div>
                </td>

                <!-- Right: Principal's Signature -->
                <td style="width: 32%; text-align: right;">
                    <div style="height: 38px; margin-bottom: 2px; text-align: center; margin-left: auto; width: 185px;">
                        @if($signatureSrc)
                            <img src="{{ $signatureSrc }}" style="height: 38px; object-fit: contain;" alt="Principal Signature" />
                        @endif
                    </div>
                    <div class="sig-line-right" style="margin-left: auto; margin-right: 0;">
                        PRINCIPAL
                        <div class="sig-designation">MACS School &amp; College</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>