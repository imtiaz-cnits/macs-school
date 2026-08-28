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
            body { background: #f3f4f6; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-h-screen; gap: 20px; }
            .page-container {
                width: 297mm;
                height: 210mm;
                background: #fff;
                padding: 10mm;
                border: 1px solid #ddd;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                box-sizing: border-box;
                display: flex;
                gap: 8mm;
            }
            .receipt-container {
                flex: 1;
                height: 100%;
                border: 1.5px solid #000;
                padding: 8mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
        }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .page-container {
                width: 297mm;
                height: 205mm; /* Reduced slightly to prevent vertical overflow/blank page */
                background: #fff;
                padding: 6mm;
                box-sizing: border-box;
                display: flex;
                gap: 6mm;
                page-break-after: always;
                break-after: page;
            }
            .page-container:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .receipt-container {
                flex: 1;
                height: 100%;
                border: 1.5px solid #000;
                padding: 6mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                page-break-inside: avoid;
            }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 0; }
        }
        .dashed-line { border-top: 1px dashed #000; margin: 8px 0; }
        .double-line { border-top: 3px double #000; margin: 8px 0; }
    </style>
</head>
<body>

    <div class="fixed top-5 right-5 flex gap-3 no-print">
        <button onclick="window.print()" class="bg-gray-900 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:bg-gray-800 transition flex items-center justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Master Receipt
        </button>
        <button onclick="window.close()" class="bg-red-100 text-red-700 font-bold py-2 px-6 rounded-lg hover:bg-red-200 transition text-sm text-center">
            Close
        </button>
    </div>

    <div class="page-container">
        @foreach(['STUDENT COPY', 'OFFICE COPY'] as $copyTitle)
            <div class="receipt-container text-[12px] leading-normal">
                
                <!-- Header -->
                <div>
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-12 h-12 object-contain filter grayscale contrast-125" alt="Logo">
                            <div>
                                <h1 class="text-lg font-black tracking-tight uppercase leading-none">MACS School and College</h1>
                                <p class="text-[9px] mt-1 font-semibold text-gray-700">Jalalpur, Pabna Sadar, Pabna (Jalalpur Branch)</p>
                                <p class="text-[9px] font-semibold text-gray-700">Phone: 01896-220299, 01896-220300</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black uppercase border border-black px-2 py-0.5 bg-gray-100 mb-1 inline-block">{{ $copyTitle }}</span>
                            <h2 class="text-sm font-extrabold uppercase block leading-none mt-1">Master Receipt</h2>
                            <p class="text-[10px] mt-1 font-semibold text-gray-755">Receipt No: <strong>{{ $receipt_no }}</strong></p>
                            <p class="text-[10px] font-semibold text-gray-755">Date: {{ date('d-M-Y', strtotime($date)) }}</p>
                        </div>
                    </div>

                    <div class="double-line"></div>

                    <!-- Student Info Grid (Aligned with AMOUNT column) -->
                    <div class="grid grid-cols-5 gap-y-1.5 py-1 text-[11px] font-medium">
                        @if(isset($isMultipleStudents) && $isMultipleStudents)
                            <div class="col-span-3"><span class="font-bold text-gray-600">Student Name:</span> Multiple (Class Bulk)</div>
                            <div class="col-span-2"><span class="font-bold text-gray-600">Student ID  :</span> <strong>Multiple Students</strong></div>
                            <div class="col-span-5"><span class="font-bold text-gray-600">Session     :</span> {{ $student->session_year->session_name ?? 'N/A' }}</div>
                        @else
                            <div class="col-span-3"><span class="font-bold text-gray-600">Student Name:</span> {{ $student->student_name ?? 'N/A' }}</div>
                            <div class="col-span-2"><span class="font-bold text-gray-600">Student ID  :</span> <strong>{{ $student->student_identity ?? 'N/A' }}</strong></div>
                            
                            <div class="col-span-3"><span class="font-bold text-gray-600">Class       :</span> {{ $student->schoolClass->class_name ?? 'N/A' }} ({{ $student->section->section_name ?? 'N/A' }})</div>
                            <div class="col-span-2"><span class="font-bold text-gray-600">Roll No     :</span> {{ $student->roll_number ?? 'N/A' }}</div>
                            
                            <div class="col-span-5"><span class="font-bold text-gray-600">Session     :</span> {{ $student->session_year->session_name ?? 'N/A' }}</div>
                        @endif
                    </div>

                    <div class="double-line"></div>

                    <!-- Invoice Details Table -->
                    <div class="max-h-[50mm] overflow-y-auto">
                        <table class="w-full text-left mt-2">
                            <thead>
                                <tr class="border-b border-black">
                                    @if(isset($isMultipleStudents) && $isMultipleStudents)
                                        <th class="py-1 font-bold uppercase text-[10px] text-gray-700">Student Details</th>
                                    @endif
                                    <th class="py-1 font-bold uppercase text-[10px] text-gray-700">Fee Category</th>
                                    <th class="py-1 font-bold uppercase text-[10px] text-gray-700 w-24">Month</th>
                                    <th class="py-1 text-right font-bold uppercase text-[10px] text-gray-700 w-32">Paid Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotal = 0; @endphp
                                @foreach($payments as $pay)
                                <tr class="border-b border-gray-200">
                                    @if(isset($isMultipleStudents) && $isMultipleStudents)
                                        <td class="py-2 font-bold">{{ $pay->student->student_name }} ({{ $pay->student->student_identity }})</td>
                                    @endif
                                    <td class="py-2 font-semibold">
                                        {{ $pay->invoice->feeSetup->category->name }}
                                    </td>
                                    @php
                                        $monthName = 'One Time';
                                        if ($pay->invoice->feeSetup) {
                                            if ($pay->invoice->feeSetup->fee_month && strtolower($pay->invoice->feeSetup->fee_month) !== 'monthly') {
                                                $monthName = $pay->invoice->feeSetup->fee_month;
                                            } elseif ($pay->invoice->due_date) {
                                                $monthName = date('F', strtotime($pay->invoice->due_date));
                                            } else {
                                                $monthName = date('F', strtotime($pay->invoice->created_at));
                                            }
                                        }
                                    @endphp
                                    <td class="py-2 font-semibold text-gray-700">
                                        {{ $monthName }}
                                    </td>
                                    <td class="py-2 text-right font-bold font-mono text-green-700">{{ number_format($pay->paid_amount, 2) }}</td>
                                </tr>
                                @php $grandTotal += $pay->paid_amount; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary & Signatures Footer -->
                <div>
                    <div class="flex justify-between items-end border-t border-black pt-3">
                        <div class="w-1/2 text-[10px] space-y-0.5 font-bold text-gray-705">
                            <p>Method: {{ strtoupper($payments->first()->payment_method ?? 'Cash') }}</p>
                            @if($payments->first() && $payments->first()->transaction_id)
                                <p>Trx ID: {{ $payments->first()->transaction_id }}</p>
                            @endif
                        </div>
                        <div class="w-60 space-y-1 text-[12px] font-bold">
                            <div class="flex justify-between font-black text-[13px] text-green-700">
                                <span>GRAND TOTAL Paid:</span>
                                <span class="font-mono">৳{{ number_format($grandTotal, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Signature Lines -->
                    <div class="flex justify-between items-center mt-10 pt-4 border-t border-dashed border-gray-300">
                        <div class="text-center w-40">
                            <div class="border-t border-black w-full mb-0.5"></div>
                            <p class="text-[9px] font-black uppercase text-gray-600">Guardian</p>
                        </div>
                        <div class="text-center text-[8px] font-bold text-gray-500 uppercase">
                            <p>Received By: {{ $collector->name ?? 'System' }}</p>
                            <p class="text-[7px] italic">CodeNext IT</p>
                        </div>
                        <div class="text-center w-40">
                            <div class="border-t border-black w-full mb-0.5"></div>
                            <p class="text-[9px] font-black uppercase text-gray-600">Authorized Cashier</p>
                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 1000);
        }
    </script>
</body>
</html>