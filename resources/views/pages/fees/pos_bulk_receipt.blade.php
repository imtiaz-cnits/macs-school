<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Receipt - {{ $receipt_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #000;
        }
        @media screen {
            body {
                background: #f3f4f6;
                padding: 24px 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                min-height: 100vh;
            }
            .page-container {
                width: 148mm;
                min-height: 210mm;
                background: #fff;
                padding: 6mm 8mm;
                border: 1px solid #ddd;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                box-sizing: border-box;
                margin: 0 auto;
            }
            .receipt-container {
                width: 100%;
                min-height: 198mm;
                border: 1.5px solid #000;
                padding: 6mm 7mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .page-container {
                width: 100%;
                max-width: 100%;
                min-height: auto;
                background: #fff;
                padding: 0;
                margin: 0;
                border: none;
                box-shadow: none;
                box-sizing: border-box;
            }
            .receipt-container {
                width: 100%;
                min-height: 194mm;
                border: 1.5px solid #000;
                padding: 5mm 6mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                page-break-inside: avoid;
                break-inside: avoid;
                page-break-after: avoid;
                break-after: avoid;
            }
            .no-print { display: none !important; }
            @page {
                size: A5 portrait;
                margin: 6mm 8mm;
            }
            table { page-break-inside: auto; break-inside: auto; }
            tr { page-break-inside: avoid; break-inside: avoid; }
            thead { display: table-header-group; }
        }
        .dashed-line { border-top: 1px dashed #000; margin: 6px 0; }
        .double-line { border-top: 3px double #000; margin: 6px 0; }
    </style>
    <script>
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 1000);
        }
    </script>
</head>
<body>

    <!-- On-Screen Print Controls (Hidden on Paper) -->
    <div class="fixed top-5 right-5 flex gap-3 no-print z-50">
        <button onclick="window.print()" class="bg-gray-900 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg hover:bg-gray-800 transition flex items-center justify-center text-xs uppercase tracking-wider cursor-pointer">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Master Receipt
        </button>
        <button onclick="window.close()" class="bg-red-50 text-red-700 font-bold py-2.5 px-5 rounded-xl border border-red-200 hover:bg-red-100 transition text-xs uppercase tracking-wider text-center cursor-pointer">
            Close
        </button>
    </div>

    <!-- Single A5 Master Receipt Container -->
    <div class="page-container">
        <div class="receipt-container text-[11px] leading-normal">

            <!-- Top Section: Header, Student Info & Table -->
            <div>
                <!-- School Header & Receipt Meta -->
                <div class="flex justify-between items-start gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-10 h-10 object-contain shrink-0" alt="Logo">
                        <div class="min-w-0">
                            <h1 class="text-[13px] font-black tracking-tight uppercase leading-tight whitespace-nowrap">MACS SCHOOL &amp; COLLEGE</h1>
                            <p class="text-[8.5px] font-semibold text-gray-700 leading-tight whitespace-nowrap">Jalalpur, Pabna Sadar, Pabna (Jalalpur Branch)</p>
                            <p class="text-[8.5px] font-semibold text-gray-700 leading-tight whitespace-nowrap">Phone: 01896-220299, 01896-220300</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <h2 class="text-xs font-black uppercase tracking-wider text-gray-900 block leading-tight">Master Receipt</h2>
                        <p class="text-[9.5px] mt-0.5 font-semibold text-gray-700 whitespace-nowrap">Receipt No: <strong>{{ $receipt_no }}</strong></p>
                        <p class="text-[9.5px] font-semibold text-gray-700 whitespace-nowrap">Date: {{ date('d-M-Y', strtotime($date)) }}</p>
                        <p class="text-[9.5px] font-semibold text-gray-700 whitespace-nowrap">Print Date: {{ \Carbon\Carbon::now('Asia/Dhaka')->format('d-M-Y') }}</p>
                    </div>
                </div>

                <div class="double-line"></div>

                <!-- Student Information Grid -->
                <div class="grid grid-cols-2 gap-x-2 gap-y-1 py-1 text-[10px] font-medium">
                    @if(isset($isMultipleStudents) && $isMultipleStudents)
                        <div><span class="font-bold text-gray-600">Student Name:</span> Multiple (Class Bulk)</div>
                        <div><span class="font-bold text-gray-600">Student ID:</span> <strong>Multiple Students</strong></div>
                        <div class="col-span-2"><span class="font-bold text-gray-600">Session:</span> {{ $student->sessionYear->session_name ?? $student->session_year->session_name ?? 'N/A' }}</div>
                    @else
                        <div><span class="font-bold text-gray-600">Student Name:</span> {{ $student->student_name ?? 'N/A' }}</div>
                        <div><span class="font-bold text-gray-600">Student ID:</span> <strong>{{ $student->student_identity ?? 'N/A' }}</strong></div>

                        <div><span class="font-bold text-gray-600">Class:</span> {{ $student->schoolClass->class_name ?? 'N/A' }} ({{ $student->section->section_name ?? 'N/A' }})</div>
                        <div><span class="font-bold text-gray-600">Roll No:</span> {{ $student->roll_number ?? 'N/A' }}</div>

                        <div class="col-span-2"><span class="font-bold text-gray-600">Session:</span> {{ $student->sessionYear->session_name ?? $student->session_year->session_name ?? 'N/A' }}</div>
                    @endif
                </div>

                <div class="double-line"></div>

                <!-- Invoice Details Table -->
                <table class="w-full text-left mt-1.5 border-collapse">
                    <thead>
                        <tr class="border-b border-black text-[9.5px] font-bold uppercase text-gray-700">
                            @if(isset($isMultipleStudents) && $isMultipleStudents)
                                <th class="py-1">Student Details</th>
                            @endif
                            <th class="py-1">Fee Category</th>
                            <th class="py-1 w-24 text-left">Month</th>
                            <th class="py-1 w-24 text-right">Paid Amount</th>
                        </tr>
                    </thead>
                    <tbody class="text-[10px]">
                        @php $grandTotal = '0.00'; @endphp
                        @foreach($payments as $pay)
                        <tr class="border-b border-gray-200">
                            @if(isset($isMultipleStudents) && $isMultipleStudents)
                                <td class="py-1 font-bold">{{ $pay->student->student_name }} ({{ $pay->student->student_identity }})</td>
                            @endif
                            <td class="py-1 font-semibold">
                                {{ $pay->invoice->feeSetup->category->name ?? 'Fee Payment' }}
                            </td>
                            @php
                                $monthName = $pay->invoice->month_name ?? ($pay->invoice->feeSetup->fee_month ?? 'One Time');
                            @endphp
                            <td class="py-1 font-semibold text-gray-700">
                                {{ $monthName }}
                            </td>
                            <td class="py-1 text-right font-bold font-mono text-green-700">৳{{ number_format((float)$pay->paid_amount, 2) }}</td>
                        </tr>
                        @php $grandTotal = \App\Helpers\NumberToWords::add($grandTotal, $pay->paid_amount); @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Bottom Section: Summary & Signatures Footer -->
            <div class="mt-4">
                <div class="flex justify-between items-end border-t border-black pt-2">
                    <div class="text-[9.5px] space-y-0.5 font-bold text-gray-700">
                        <p>Method: {{ strtoupper($payments->first()->payment_method ?? 'Cash') }}</p>
                        @if($payments->first() && $payments->first()->transaction_id)
                            <p>Trx ID: {{ $payments->first()->transaction_id }}</p>
                        @endif
                    </div>
                    <div class="space-y-1 text-right">
                        <div class="flex items-baseline justify-end gap-2 font-black text-xs text-green-700">
                            <span class="text-gray-900 uppercase tracking-tight text-[10px]">GRAND TOTAL Paid:</span>
                            <span class="font-mono text-[13px]">৳{{ \App\Helpers\NumberToWords::formatDecimal($grandTotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer: Amount in Words & Cashier Signature -->
                <div class="flex justify-between items-end mt-5 pt-2 border-t border-dashed border-gray-300 gap-4">
                    <div class="flex-1 min-w-0 pr-2">
                        <p class="text-[8.5px] font-bold text-gray-500 uppercase tracking-wider">In Words:</p>
                        <p class="text-[10px] font-bold leading-snug text-gray-900 break-words">{{ \App\Helpers\NumberToWords::convert($grandTotal) }}</p>
                    </div>
                    <div class="text-center w-32 shrink-0">
                        <div class="border-t border-black w-full mb-0.5"></div>
                        <p class="text-[8.5px] font-black uppercase text-gray-600">Authorized Cashier</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
