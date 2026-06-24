<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* এ৪ ল্যান্ডস্কেপ জিরো মার্জিন (১০০% এক পেজে গ্যারান্টি) */
        @page { size: a4 landscape; margin: 0; }
        * { box-sizing: border-box; }

        body { 
            font-family: 'Helvetica', sans-serif; 
            margin: 0; padding: 40px 60px; 
            color: #000; background: #fff;
        }

        /* ফিক্সড ব্যাকগ্রাউন্ড বর্ডার (এরা কোনো জায়গা খাবে না) */
        .border-outer {
            position: fixed; top: 15px; bottom: 15px; left: 15px; right: 15px;
            border: 12px solid #1e4630; z-index: -2;
        }
        .border-inner {
            position: fixed; top: 32px; bottom: 32px; left: 32px; right: 32px;
            border: 2px solid #1e4630; z-index: -1;
        }

        /* ওয়াটারমার্ক */
        .watermark {
            position: fixed; top: 18%; left: 35%;
            width: 350px; opacity: 0.05; z-index: -3;
        }

        /* হেডার */
        .header { text-align: center; margin-bottom: 25px; margin-top: 5px; }
        .school-name { font-size: 36px; font-weight: 900; color: #1e4630; margin: 0; text-transform: uppercase; }
        .address { font-size: 14px; color: #444; margin-bottom: 12px; }
        .title-badge { 
            background: #1e4630; color: #fff; padding: 8px 50px; 
            border-radius: 50px; font-size: 18px; display: inline-block; font-weight: bold; text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* কন্টেন্ট ডিজাইন */
        .content { 
            font-size: 19px; 
            line-height: 1.7; 
            text-align: justify; 
            padding: 0 20px; 
            margin-top: 25px;
        }
        
        /* প্যারাগ্রাফের মাঝখানের নির্দিষ্ট গ্যাপ */
        .para { margin: 0 0 15px 0; }
        .highlight { font-weight: bold; border-bottom: 1px dashed #555; padding: 0 5px; }

        /* সিগনেচার এরিয়া */
        .footer-table { width: 100%; margin-top: 55px; padding: 0 20px; }
        
        /* সিগনেচার বক্সকে ভেতরের দিকে আনার ফিক্স */
        .sig-box {
            border-top: 2px solid #000; 
            width: 230px; 
            text-align: center;
            font-weight: bold; 
            font-size: 17px; 
            padding-top: 5px; 
            margin-left: auto;
            margin-right: 25px;
        }
    </style>
</head>
<body>
    <div class="border-outer"></div>
    <div class="border-inner"></div>
    <img src="{{ public_path('img/logo.svg') }}" class="watermark">

    <div class="header">
        <img src="{{ public_path('img/logo.svg') }}" width="80">
        <h1 class="school-name">Pabna International School</h1>
        <p class="address">{{ $student->branch->branch_name ?? 'Pabna International School' }}</p>
        <div class="title-badge">General Certificate</div>
    </div>

    <div class="content">
        <p class="para">This is to certify that <span class="highlight">{{ $student->student_name }}</span>, 
        son/daughter of <span class="highlight">{{ $student->father_name ?? '........................' }}</span> 
        and <span class="highlight">{{ $student->mother_name ?? '........................' }}</span>, 
        is/was a student of this prestigious institution.</p>

        <p class="para">According to our records, he/she is/was studying in Class <span class="highlight">{{ $student->schoolClass->class_name ?? 'N/A' }}</span> 
        under the academic session <span class="highlight">{{ $student->sessionYear->session_name ?? '2026' }}</span>.</p>

        <p class="para">His/Her date of birth according to the Admission Register is recorded as <span class="highlight">{{ $student->dob ? date('d M, Y', strtotime($student->dob)) : '..................' }}</span>.</p>

        <p class="para">To the best of my knowledge, he/she bears a good moral character and did not take part in any activities subversive of the state or of school discipline.</p>

        <p class="para">I wish him/her every success and a bright future in life.</p>
    </div>

    <table class="footer-table">
        <tr>
            <td style="text-align: left; vertical-align: bottom; font-weight: bold; font-size: 16px; color: #1e4630;">
                Date of Issue: {{ date('d M, Y', strtotime($date)) }}
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