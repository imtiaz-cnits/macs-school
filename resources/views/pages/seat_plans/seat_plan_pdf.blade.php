<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Seat Plan - {{ $schoolClass->class_name }}</title>
    <style>
        /* 🚨 Import Google Font Roboto 🚨 */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');
        
        /* 🚨 A4 Page Setup: একদম ব্যালেন্সড মার্জিন 🚨 */
        @page { size: A4 portrait; margin: 30px 20px; } 
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 0; color: #0f172a; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* 🚨 Main Grid Layout: Fixed Table Layout 🚨 */
        .main-grid { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed;
        }
        .main-grid > tbody > tr { page-break-inside: avoid; }
        
        /* Symmetrical percentage layout: 48% + 4% + 48% = 100% */
        .card-td { width: 48%; vertical-align: top; padding: 0; } 
        .gap-td { width: 4%; } 
        
        /* Individual Card Design */
        .seat-card { 
            border: 2px solid #009A49; /* MACS Green accent border */
            border-radius: 12px; 
            padding: 12px; 
            background: #ffffff; 
            /* Width left empty to default to auto (fits parent cell perfectly) */
            height: 170px; /* 198px outer height - 24px padding - 4px border */
            overflow: hidden;
        }
        
        /* Inner Table - Fixed Layout */
        .card-inner-table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
        }
        .card-inner-table td { border: none; padding: 0; vertical-align: top; }
        
        /* School Logo */
        .school-logo { 
            width: 46px; 
            height: 46px; 
            border-radius: 50%; 
            border: 1px solid #e2e8f0; 
            padding: 2px; 
            background: #fff; 
        }
        
        /* Center Info */
        .center-content { text-align: center; }
        .school-name { 
            color: #000; /* MACS Sky Blue */
            font-weight: 800; 
            font-size: 13px;  
            text-transform: uppercase; 
            margin-bottom: 12px; 
            letter-spacing: 0.5px;
        }
        .badge-wrapper { text-align: center; margin-bottom: 4px; }
        .badge { 
            border: 1px solid #009A49; /* MACS Green accent */
            border-radius: 12px; 
            display: inline-block; 
            font-size: 9px; 
            font-weight: bold; 
            padding: 2px 10px; 
            color: #009A49; 
            background-color: #edfdf1; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        
        /* Details Table inside Card (Full Width) */
        .details-table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed;}
        .details-table td { padding: 3.2px 0px; text-align: left; border-bottom: 1px dashed #e2e8f0; vertical-align: top; line-height: 1.25;}
        .details-table tr:last-child td { border-bottom: none; padding-bottom: 0; }
        
        .details-table .label { 
            font-weight: 500; 
            color: #000; 
            font-size: 10px; 
            text-transform: uppercase; 
            letter-spacing: 0.2px;
        }
        .details-table .val { 
            font-weight: 700; 
            color: #0f172a; 
            text-transform: uppercase; 
            font-size: 12px; 
            word-wrap: break-word;
        }
        .details-table .val-name {
            color: #000 !important; /* Highlight Name in MACS Sky Blue */
            font-weight: 800 !important;
        }
        
        /* Premium Roll Box */
        .roll-box { 
            border: 2px solid #008ED6; /* MACS Sky Blue border */
            text-align: center; 
            border-radius: 6px; 
            overflow: hidden; 
            display: block; 
            width: 55px; 
            background: #fff; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
        }
        .roll-title { 
            background: #008ED6; /* MACS Sky Blue header banner */
            color: #fff; 
            font-size: 9px; 
            font-weight: bold; 
            padding: 3px 0; 
            text-transform: uppercase; 
            letter-spacing: 1px;
        }
        .roll-number { 
            font-size: 18px; 
            font-weight: 900; 
            padding: 3px 0; 
            color: #009A49; /* MACS Green accent for roll value */
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
            // Fallback to logo.svg if jpeg is not found
            $fallbackPath = public_path('img/logo.svg');
            if(file_exists($fallbackPath)){
                $logoData = base64_encode(file_get_contents($fallbackPath));
                $logoSrc = 'data:image/svg+xml;base64,' . $logoData;
            }
        }
    @endphp

    <table class="main-grid">
        <tbody>
            @foreach($students->chunk(2) as $chunk)
            <tr>
                @foreach($chunk as $student)
                    <td class="card-td">
                        <div class="seat-card">
                            <table class="card-inner-table">
                                <!-- Top Row (School Logo, Center Info, Roll Box) -->
                                <tr>
                                    <!-- Top Left: School Logo (instead of photo) -->
                                    <td style="width: 18%; text-align: left; vertical-align: middle;">
                                        @if($logoSrc)
                                            <img src="{{ $logoSrc }}" class="school-logo" alt="Logo" />
                                        @else
                                            <div class="school-logo" style="border: 1px solid #e2e8f0; line-height: 46px; font-size: 9px; text-align: center; color: #64748b;">LOGO</div>
                                        @endif
                                    </td>
                                    
                                    <!-- Top Middle: School Name & Badge -->
                                    <td style="width: 70%; text-align: center; vertical-align: middle; padding: 0 4px;" class="center-content">
                                        <div class="school-name">{{ $schoolClass->school_name ?? 'MACS SCHOOL AND COLLEGE' }}</div>
                                        <div class="badge-wrapper">
                                            <div class="badge">Exam Seat Plan</div>
                                        </div>
                                    </td>

                                    <!-- Top Right: Roll Box (instead of Logo) -->
                                    <td style="width: 18%; text-align: right; vertical-align: middle;">
                                        <div class="roll-box" style="margin-left: auto; margin-right: 0;">
                                            <div class="roll-title">Roll</div>
                                            <div class="roll-number">{{ $student->roll_number }}</div> 
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Gap Row -->
                                <tr>
                                    <td colspan="3" style="height: 6px; border: none;"></td>
                                </tr>
                                
                                <!-- Bottom Row: Student Information (Full Width) -->
                                <tr>
                                    <td colspan="3" style="width: 100%;">
                                        <table class="details-table">
                                            <tr>
                                                <td style="width: 70%;"><span class="label">Name:</span> <span class="val val-name">{{ $student->student_name ?? $student->first_name }}</span></td>
                                                <td style="width: 30%;"><span class="label">ID:</span> <span class="val">{{ $student->student_identity }}</span></td>
                                            </tr>
                                            <tr>
                                                <td><span class="label">Class:</span> <span class="val">{{ $schoolClass->class_name ?? 'N/A' }}</span></td>
                                                <td><span class="label">Shift:</span> <span class="val">{{ str_ireplace([' student', ' staff'], '', $student->shift->shift_name ?? 'N/A') }}</span></td>
                                            </tr>
                                            <tr>
                                                <td><span class="label">Section:</span> <span class="val">{{ $student->section->section_name ?? 'N/A' }}</span></td>
                                                <td><span class="label">Session:</span> <span class="val">{{ $session->session_name ?? 'N/A' }}</span></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="border-bottom: none; padding-bottom: 0;"><span class="label">Exam:</span> <span class="val">{{ $exam->name ?? 'N/A' }}</span></td>
                                            </tr>
                                        </table>
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
            @if(!$loop->last)
                <tr><td colspan="3" style="height: 16px; border: none; padding: 0; line-height: 1;"></td></tr>
            @endif
            @endforeach
        </tbody>
    </table>

</body>
</html>