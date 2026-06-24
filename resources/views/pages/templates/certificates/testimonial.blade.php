<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* ১. পেজের মার্জিন জিরো, তাই ডোমপিডিএফ নিজে থেকে কোনো জায়গা খাবে না */
        @page { 
            size: a4 landscape; 
            margin: 0; 
        }

        body { 
            font-family: 'Helvetica', sans-serif; 
            margin: 0; 
            padding: 50px 60px; /* কনটেন্টকে চারপাশ থেকে ভেতরে রাখার জন্য প্যাডিং */
            color: #000;
        }

        /* ২. দ্য ম্যাজিক: বর্ডারগুলোকে Fixed করা হয়েছে। এরা পেজের কোনো হাইট দখল করবে না! */
        .border-outer {
            position: fixed;
            top: 15px;
            bottom: 15px;
            left: 15px;
            right: 15px;
            border: 12px solid #1e4630;
            z-index: -2;
        }

        .border-inner {
            position: fixed;
            top: 32px;
            bottom: 32px;
            left: 32px;
            right: 32px;
            border: 2px solid #1e4630;
            z-index: -1;
        }

        /* ৩. ওয়াটারমার্ক ফিক্সড */
        .watermark {
            position: fixed;
            top: 18%; /* ল্যান্ডস্কেপের মাঝ বরাবর */
            left: 35%;
            width: 350px;
            opacity: 0.05;
            z-index: -3;
        }

        /* ৪. নরমাল কনটেন্ট ফ্লো (এরা কোনোদিন ২য় পেজে যাওয়ার মতো বড় নয়) */
        .header { text-align: center; margin-bottom: 30px; margin-top: 10px; }
        .school-name { font-size: 38px; font-weight: 900; color: #1e4630; margin: 0; text-transform: uppercase; }
        .address { font-size: 14px; color: #444; margin-bottom: 15px; }
        .title-badge { 
            background: #1e4630; color: #fff; padding: 10px 50px; 
            border-radius: 50px; font-size: 20px; display: inline-block; font-weight: bold; text-transform: uppercase;
        }

        .content { 
            font-size: 19px; line-height: 1.8; 
            text-align: justify; padding: 0 20px; 
            margin-top: 20px;
        }
        .highlight { font-weight: bold; border-bottom: 1px solid #777; padding: 0 5px; }

        /* ৫. সিগনেচার টেবিল (মার্জিন দিয়ে নিচে নামানো হয়েছে, হারাবে না) */
        .footer-table {
            width: 100%;
            margin-top: 80px; /* কনটেন্ট থেকে অনেকটা নিচে */
            padding: 0 20px;
        }
        .sig-box {
            border-top: 2px solid #000;
            width: 250px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            padding-top: 5px;
            margin-left: auto;
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
            <td style="text-align: left; vertical-align: bottom; font-weight: bold; font-size: 16px; color: #1e4630;">
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