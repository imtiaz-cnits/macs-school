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

    @php
        $mr = $masterReceipt ?? app(\App\Services\FeeReceiptService::class)->buildMasterReceiptData($payments, $receipt_no);
        $branchLine = 'Jalalpur, Pabna Sadar, Pabna (' . ($mr['branch_name'] ?? 'Jalalpur Branch') . ')';
        $tableColSpan = $mr['is_multiple_students'] ? 4 : 3;
    @endphp

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
                            <p class="text-[8.5px] font-semibold text-gray-700 leading-tight whitespace-nowrap">{{ $branchLine }}</p>
                            <p class="text-[8.5px] font-semibold text-gray-700 leading-tight whitespace-nowrap">Phone: 01896-220299, 01896-220300</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <h2 class="text-xs font-black uppercase tracking-wider text-gray-900 block leading-tight">Master Receipt</h2>
                        <p class="text-[9.5px] mt-0.5 font-semibold text-gray-700 whitespace-nowrap">Receipt No: <strong>{{ $receipt_no }}</strong></p>
                        <p class="text-[9.5px] font-semibold text-gray-700 whitespace-nowrap">Date: {{ $date ? date('d-M-Y', strtotime((string) $date)) : 'N/A' }}</p>
                        <p class="text-[9.5px] font-semibold text-gray-700 whitespace-nowrap">Print Date: {{ \Carbon\Carbon::now('Asia/Dhaka')->format('d-M-Y') }}</p>
                    </div>
                </div>

                @if($mr['is_fully_reversed'])
                    <div class="mt-2 border-2 border-black bg-gray-100 px-2.5 py-2 text-[9.5px]">
                        <div class="flex justify-between items-center font-black uppercase tracking-wider text-red-700">
                            <span>VOID / REVERSED RECEIPT</span>
                            <span>Audit Copy</span>
                        </div>
                        <div class="mt-1 grid grid-cols-2 gap-x-2 gap-y-0.5 font-semibold text-gray-800">
                            <div>Original Receipt: <span class="font-mono font-black">{{ $receipt_no }}</span></div>
                            <div>Reversal Ref: <span class="font-mono font-black">{{ $mr['reversal_references']->join(', ') ?: 'N/A' }}</span></div>
                            <div>Reversal Date: {{ $mr['reversed_at_formatted'] }}</div>
                            <div>Reversed By: {{ $mr['reversed_by_name'] }}</div>
                            <div class="col-span-2">Reason: {{ $mr['reversal_reason'] }}</div>
                        </div>
                    </div>
                @elseif($mr['has_partial_reversals'])
                    <div class="mt-2 border border-black bg-gray-100 px-2.5 py-1.5 text-[9px] font-bold text-gray-900">
                        <span class="uppercase font-black text-red-700">PARTIAL REVERSAL NOTICE:</span>
                        Reversed items ({{ $mr['reversal_references']->join(', ') }}) are shown separately for audit and excluded from GRAND TOTAL Paid.
                    </div>
                @endif

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

                <!-- Active Invoice Details Table -->
                @if($mr['active_items']->isNotEmpty())
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
                            @foreach($mr['active_items'] as $item)
                                <tr class="border-b {{ ($item['is_admission'] && !empty($item['head_lines'])) ? 'border-gray-300' : 'border-gray-200' }}">
                                    @if(isset($isMultipleStudents) && $isMultipleStudents)
                                        <td class="py-1 font-bold">{{ $item['student']->student_name ?? 'N/A' }} ({{ $item['student']->student_identity ?? 'N/A' }})</td>
                                    @endif
                                    <td class="py-1 font-semibold">
                                        {{ $item['label'] }}
                                        @if($item['is_admission'])
                                            <span class="block text-[8.5px] font-normal text-gray-600">
                                                Invoice: {{ $item['invoice_no'] }}
                                                @if(!empty($item['tuition_month_label']))
                                                    | Includes {{ $item['tuition_month_label'] }} Tuition
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-1 font-semibold text-gray-700">
                                        {{ $item['month_label'] }}
                                    </td>
                                    <td class="py-1 text-right font-bold font-mono text-green-700">৳{{ $item['paid_amount_formatted'] }}</td>
                                </tr>
                                @if($item['is_admission'] && !empty($item['head_lines']))
                                    @foreach($item['head_lines'] as $head)
                                        <tr class="border-b border-gray-100 bg-gray-50/60 text-[9px] text-gray-700">
                                            @if(isset($isMultipleStudents) && $isMultipleStudents)
                                                <td></td>
                                            @endif
                                            <td class="py-0.5 pl-3" colspan="2">
                                                &bull; {{ $head['head_name'] }}
                                                @if($head['is_tuition_head'])
                                                    <span class="ml-1 px-1 border border-gray-400 text-[7.5px] font-bold uppercase">Tuition</span>
                                                @endif
                                            </td>
                                            <td class="py-0.5 text-right font-mono font-semibold text-gray-800">৳{{ $head['allocated_paid_formatted'] }}</td>
                                        </tr>
                                    @endforeach
                                @elseif($item['is_admission'] && !empty($item['has_alloc_status_mismatch']))
                                    <tr class="border-b border-gray-100 bg-gray-50/60 text-[8.5px] text-gray-600 italic">
                                        @if(isset($isMultipleStudents) && $isMultipleStudents)
                                            <td></td>
                                        @endif
                                        <td class="py-0.5 pl-3" colspan="3">
                                            Active head allocation breakdown unavailable (no Active head allocations matched).
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <!-- Reversed Audit Lines Table (Never Counted in Active Collected Total) -->
                @if($mr['reversed_items']->isNotEmpty())
                    <div class="mt-2">
                        <p class="text-[8.5px] font-black uppercase tracking-wider text-red-700 mb-0.5">
                            Reversed Payment Line Items (Excluded from Active Totals)
                        </p>
                        <table class="w-full text-left border-collapse">
                            @if($mr['active_items']->isEmpty())
                                <thead>
                                    <tr class="border-b border-black text-[9.5px] font-bold uppercase text-gray-700">
                                        @if(isset($isMultipleStudents) && $isMultipleStudents)
                                            <th class="py-1">Student Details</th>
                                        @endif
                                        <th class="py-1">Fee Category</th>
                                        <th class="py-1 w-24 text-left">Month</th>
                                        <th class="py-1 w-28 text-right">Reversed Amt</th>
                                    </tr>
                                </thead>
                            @endif
                            <tbody class="text-[9.5px] text-gray-500">
                                @foreach($mr['reversed_items'] as $rItem)
                                    <tr class="border-b border-gray-200">
                                        @if(isset($isMultipleStudents) && $isMultipleStudents)
                                            <td class="py-1 font-semibold line-through">{{ $rItem['student']->student_name ?? 'N/A' }} ({{ $rItem['student']->student_identity ?? 'N/A' }})</td>
                                        @endif
                                        <td class="py-1 font-semibold">
                                            <span class="line-through">{{ $rItem['label'] }}</span>
                                            <span class="ml-1 border border-black px-1 py-0.2 bg-gray-100 text-[8px] font-black text-red-700 uppercase">[REVERSED]</span>
                                            @if(!empty($rItem['reversal_reference']))
                                                <span class="block text-[8px] font-mono text-gray-600">
                                                    Ref: {{ $rItem['reversal_reference'] }} | Receipt: {{ $rItem['receipt_no'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-1 line-through">{{ $rItem['month_label'] }}</td>
                                        <td class="py-1 text-right font-mono font-bold line-through text-red-700">৳{{ $rItem['reversed_amount_formatted'] }}</td>
                                    </tr>
                                    @if($rItem['is_admission'] && !empty($rItem['head_lines']))
                                        @foreach($rItem['head_lines'] as $rHead)
                                            <tr class="border-b border-gray-100 text-[8.5px] text-gray-500">
                                                @if(isset($isMultipleStudents) && $isMultipleStudents)
                                                    <td></td>
                                                @endif
                                                <td class="py-0.5 pl-3" colspan="2">
                                                    <span class="line-through">&bull; {{ $rHead['head_name'] }}</span>
                                                    @if(!empty($rHead['is_unsynced_original']))
                                                        <span class="ml-1 px-1 border border-gray-400 text-[7px] font-bold uppercase text-gray-600">[ORIG ALLOCATION — REVERSAL STATUS MISSING]</span>
                                                    @endif
                                                </td>
                                                <td class="py-0.5 text-right font-mono line-through">৳{{ $rHead['allocated_paid_formatted'] }}</td>
                                            </tr>
                                        @endforeach
                                    @elseif($rItem['is_admission'] && !empty($rItem['missing_expected_allocations']))
                                        <tr class="border-b border-gray-100 text-[8px] text-gray-500 italic">
                                            @if(isset($isMultipleStudents) && $isMultipleStudents)
                                                <td></td>
                                            @endif
                                            <td class="py-0.5 pl-3" colspan="3">
                                                Reversed head allocation rows missing from ledger (active collected: ৳0.00).
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Bottom Section: Summary & Signatures Footer -->
            <div class="mt-4">
                <div class="flex justify-between items-end border-t border-black pt-2">
                    <div class="text-[9.5px] space-y-0.5 font-bold text-gray-700">
                        @if($mr['is_fully_reversed'])
                            <p class="text-red-700 font-black">Status: VOID / REVERSED</p>
                            <p>Total Reversed: <span class="font-mono line-through">৳{{ $mr['reversed_grand_total_formatted'] }}</span></p>
                        @else
                            <p>Method: {{ $mr['primary_active_method'] ?? 'CASH' }}</p>
                            @if(!empty($mr['primary_active_trx_id']))
                                <p>Trx ID: {{ $mr['primary_active_trx_id'] }}</p>
                            @endif
                            @if($mr['has_partial_reversals'])
                                <p class="text-red-700 text-[8.5px]">Excluded Reversed: ৳{{ $mr['reversed_grand_total_formatted'] }}</p>
                            @endif
                        @endif
                    </div>
                    <div class="space-y-1 text-right">
                        @if($mr['is_fully_reversed'])
                            <div class="flex items-baseline justify-end gap-2 font-black text-xs text-red-700">
                                <span class="text-gray-900 uppercase tracking-tight text-[10px]">ACTIVE COLLECTED TOTAL:</span>
                                <span class="font-mono text-[13px]">৳0.00</span>
                            </div>
                        @else
                            <div class="flex items-baseline justify-end gap-2 font-black text-xs text-green-700">
                                <span class="text-gray-900 uppercase tracking-tight text-[10px]">GRAND TOTAL Paid:</span>
                                <span class="font-mono text-[13px]">৳{{ $mr['active_grand_total_formatted'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer: Amount in Words & Cashier Signature -->
                <div class="flex justify-between items-end mt-5 pt-2 border-t border-dashed border-gray-300 gap-4">
                    <div class="flex-1 min-w-0 pr-2">
                        <p class="text-[8.5px] font-bold text-gray-500 uppercase tracking-wider">In Words:</p>
                        <p class="text-[10px] font-bold leading-snug text-gray-900 break-words">{{ $mr['active_grand_total_words'] }}</p>
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
