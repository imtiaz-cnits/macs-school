@php
    $invoice = $receiptData['invoice'];
    $meta = $receiptData['payment_meta'];
    $isAdmission = $receiptData['is_admission'];
    $isScoped = $receiptData['is_scoped_payment'];
    $scopedReversed = $receiptData['scoped_is_reversed'];
    $branchLine = 'Jalalpur, Pabna Sadar, Pabna (' . ($receiptData['branch_name'] ?? 'Jalalpur Branch') . ')';
@endphp

<div class="receipt-container text-[11px] leading-normal">

    <!-- Top Section: Header, Student Info & Details Table -->
    <div>
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
                @if(!empty($copyTitle))
                    <span class="text-[8.5px] font-black uppercase border border-black px-1.5 py-0.5 bg-gray-100 mb-0.5 inline-block">{{ $copyTitle }}</span>
                @endif
                <h2 class="text-xs font-black uppercase tracking-wider text-gray-900 block leading-tight">Fee Receipt</h2>
                <p class="text-[9px] mt-0.5 font-semibold text-gray-700 whitespace-nowrap">Invoice No: <strong>{{ $invoice->invoice_no }}</strong></p>
                @if($isScoped && $receiptData['scoped_payment'])
                    <p class="text-[9px] font-semibold text-gray-700 whitespace-nowrap">Receipt No: <strong>{{ $receiptData['scoped_payment']->receipt_no }}</strong></p>
                @endif
                <p class="text-[9px] font-semibold text-gray-700 whitespace-nowrap">Date: {{ $receiptData['date_display'] }}</p>
                <p class="text-[9px] font-semibold text-gray-700 whitespace-nowrap">Print Date: {{ \Carbon\Carbon::now('Asia/Dhaka')->format('d-M-Y') }}</p>
            </div>
        </div>

        @if($scopedReversed)
            <div class="mt-2 border-2 border-black bg-gray-100 px-2.5 py-1.5 text-[9.5px]">
                <div class="flex justify-between items-center font-black uppercase tracking-wide text-red-700">
                    <span>VOID / REVERSED PAYMENT</span>
                    <span>REF: {{ $meta['reversal_reference'] ?? 'N/A' }}</span>
                </div>
                <div class="mt-0.5 text-[9px] font-semibold text-gray-800 flex flex-wrap justify-between gap-x-3">
                    <span>Reversed By: {{ $meta['reversed_by_name'] ?? 'Authorized Admin' }}</span>
                    @if(!empty($meta['reversed_at_formatted']))
                        <span>Reversed At: {{ $meta['reversed_at_formatted'] }}</span>
                    @endif
                </div>
                <div class="text-[9px] font-medium text-gray-800">
                    Reason: {{ $meta['reversal_reason'] ?? 'No reason recorded' }}
                </div>
            </div>
        @elseif($receiptData['is_cancelled'])
            <div class="mt-2 border-2 border-black bg-gray-100 px-2.5 py-1 text-center font-black uppercase tracking-wider text-[9.5px] text-red-700">
                CANCELLED INVOICE — VOID FOR COLLECTION
            </div>
        @endif

        <div class="double-line"></div>

        <!-- Student Info Grid -->
        <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 py-0.5 text-[10px] font-medium">
            <div class="truncate"><span class="font-bold text-gray-600">Student Name:</span> {{ $invoice->student->student_name ?? 'N/A' }}</div>
            <div class="whitespace-nowrap"><span class="font-bold text-gray-600">Student ID:</span> <strong>{{ $invoice->student->student_identity ?? 'N/A' }}</strong></div>

            <div class="truncate"><span class="font-bold text-gray-600">Class:</span> {{ $invoice->student->schoolClass->class_name ?? 'N/A' }} ({{ $invoice->student->section->section_name ?? 'N/A' }})</div>
            <div class="whitespace-nowrap"><span class="font-bold text-gray-600">Roll No:</span> {{ $invoice->student->roll_number ?? 'N/A' }}</div>

            <div class="col-span-2"><span class="font-bold text-gray-600">Session:</span> {{ $invoice->student->sessionYear->session_name ?? $invoice->student->session_year->session_name ?? 'N/A' }}</div>
        </div>

        <div class="double-line"></div>

        @if($isAdmission)
            <div class="mt-1 mb-1 flex flex-wrap items-center justify-between gap-1 text-[9.5px] font-bold">
                <span class="uppercase tracking-tight text-gray-900">
                    Admission Fee Package — {{ $receiptData['structure_name'] }}
                </span>
                @if(!empty($receiptData['tuition_month_label']))
                    <span class="border border-black px-1.5 py-0.5 text-[8px] font-extrabold uppercase bg-gray-100 whitespace-nowrap">
                        Includes {{ $receiptData['tuition_month_label'] }} Tuition
                    </span>
                @endif
            </div>

            <!-- Admission Multi-Head Breakdown Table (Compact A5 Single-Page Fit) -->
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-black text-[8.5px] font-bold uppercase text-gray-700">
                        <th class="py-0.5 pr-1">Fee Head</th>
                        <th class="py-0.5 px-1 text-right whitespace-nowrap">Gross</th>
                        <th class="py-0.5 px-1 text-right whitespace-nowrap">Disc</th>
                        <th class="py-0.5 px-1 text-right whitespace-nowrap">Net</th>
                        <th class="py-0.5 px-1 text-right whitespace-nowrap">{{ $isScoped ? 'This Receipt' : 'Paid' }}</th>
                        <th class="py-0.5 pl-1 text-right whitespace-nowrap">Due</th>
                    </tr>
                </thead>
                <tbody class="text-[9px] leading-tight">
                    @forelse($receiptData['head_rows'] as $row)
                        <tr class="border-b border-gray-200 {{ $scopedReversed ? 'text-gray-500' : '' }}">
                            <td class="py-0.5 pr-1 font-semibold {{ $scopedReversed ? 'line-through' : 'text-gray-900' }}">
                                {{ $row['head_name'] }}
                                @if($row['is_tuition_head'])
                                    <span class="ml-0.5 px-1 border border-gray-400 text-[7px] font-bold uppercase whitespace-nowrap">Tuition</span>
                                @endif
                            </td>
                            <td class="py-0.5 px-1 text-right font-mono whitespace-nowrap">{{ $row['gross_formatted'] }}</td>
                            <td class="py-0.5 px-1 text-right font-mono whitespace-nowrap">{{ $row['discount_formatted'] }}</td>
                            <td class="py-0.5 px-1 text-right font-mono font-bold whitespace-nowrap">{{ $row['net_formatted'] }}</td>
                            <td class="py-0.5 px-1 text-right font-mono font-bold whitespace-nowrap">
                                @if($scopedReversed)
                                    <span class="line-through text-gray-500">{{ $row['reversed_paid_formatted'] }}</span>
                                    @if(!empty($receiptData['head_allocation_audit']['used_provenance_fallback']) && bccomp($row['reversed_paid'], '0.00', 2) > 0)
                                        <span class="block text-[7px] font-bold uppercase text-gray-600">[ORIG UNSYNCED]</span>
                                    @endif
                                    <span class="block text-[8.5px] text-red-700 font-black">0.00</span>
                                @else
                                    <span class="text-green-700">{{ $row['paid_formatted'] }}</span>
                                @endif
                            </td>
                            <td class="py-0.5 pl-1 text-right font-mono font-bold whitespace-nowrap">{{ $row['due_formatted'] }}</td>
                        </tr>
                    @empty
                        <tr class="border-b border-gray-200">
                            <td colspan="6" class="py-2 text-center text-gray-500 font-semibold">No head allocation snapshot recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($isScoped && !empty($receiptData['head_allocation_audit']))
                @php $allocAudit = $receiptData['head_allocation_audit']; @endphp
                @if(!$scopedReversed && !empty($allocAudit['has_status_mismatch']))
                    <p class="mt-1 text-[8px] font-semibold italic text-gray-600">
                        Active head allocation breakdown unavailable (no Active head allocations matched for this receipt).
                    </p>
                @elseif($scopedReversed && !empty($allocAudit['missing_expected_allocations']) && empty($allocAudit['used_provenance_fallback']))
                    <p class="mt-1 text-[8px] font-semibold italic text-gray-600">
                        Reversed head allocation rows missing from ledger (active collected: ৳0.00).
                    </p>
                @endif
            @endif
        @else
            <!-- Legacy Single-Line Invoice Details Table (A5 Matched Format) -->
            <table class="w-full text-left mt-1.5 border-collapse">
                <thead>
                    <tr class="border-b border-black text-[9.5px] font-bold uppercase text-gray-700">
                        <th class="py-1">Fee Description</th>
                        <th class="py-1 w-24">Month</th>
                        <th class="py-1 text-right w-24">Amount</th>
                        <th class="py-1 text-right w-24">{{ ($isScoped && !$scopedReversed && bccomp($receiptData['scoped_paid_total'], $receiptData['invoice_paid_total'], 2) !== 0) ? 'Receipt Paid' : 'Paid' }}</th>
                    </tr>
                </thead>
                <tbody class="text-[10px]">
                    <tr class="border-b border-gray-200">
                        <td class="py-2 font-bold {{ $scopedReversed ? 'line-through text-gray-500' : '' }}">
                            {{ $receiptData['legacy_category_name'] }}
                            @if($scopedReversed)
                                <span class="ml-1 text-[8.5px] font-black text-red-700 uppercase">[REVERSED]</span>
                            @endif
                        </td>
                        <td class="py-2 font-semibold text-gray-700">
                            {{ $receiptData['month_label'] }}
                        </td>
                        <td class="py-2 text-right font-bold font-mono whitespace-nowrap">{{ $receiptData['net_total_formatted'] }}</td>
                        <td class="py-2 text-right font-bold font-mono whitespace-nowrap">
                            @if($scopedReversed)
                                <span class="line-through text-gray-500">{{ $receiptData['scoped_reversed_total_formatted'] }}</span>
                                <span class="block text-[9.5px] text-red-700 font-black">0.00</span>
                            @elseif(!empty($receiptData['has_fine']))
                                <span class="text-green-700">{{ $receiptData['tuition_paid_total_formatted'] }}</span>
                            @else
                                <span class="text-green-700">{{ $isScoped ? $receiptData['scoped_paid_total_formatted'] : $receiptData['invoice_paid_total_formatted'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($receiptData['has_fine']))
                        <tr class="border-b border-gray-200">
                            <td class="py-1.5 font-bold {{ $scopedReversed ? 'line-through text-gray-500' : 'text-gray-800' }}">
                                Late Fine
                                @if(!empty($receiptData['fine_source']))
                                    <span class="ml-1 text-[8px] font-bold uppercase text-gray-600">({{ ucfirst($receiptData['fine_source']) }})</span>
                                @endif
                            </td>
                            <td class="py-1.5 font-semibold text-gray-700">
                                {{ $receiptData['month_label'] }}
                            </td>
                            <td class="py-1.5 text-right font-bold font-mono whitespace-nowrap">{{ $receiptData['fine_total_formatted'] }}</td>
                            <td class="py-1.5 text-right font-bold font-mono whitespace-nowrap">
                                @if($scopedReversed)
                                    <span class="text-red-700 font-black">0.00</span>
                                @else
                                    <span class="text-green-700">{{ $receiptData['fine_paid_total_formatted'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif
    </div>

    <!-- Summary & Signatures Footer (Strictly Inside Receipt Border) -->
    <div class="mt-3">
        <div class="flex justify-between items-end border-t border-black pt-2 gap-2">
            <div class="w-1/2 text-[9px] space-y-0.5 font-bold text-gray-700">
                @if($meta['mode'] === 'scoped_reversed')
                    <p>Original Receipt: <span class="font-mono">{{ $meta['receipt_no'] }}</span></p>
                    <p>Reversal Ref: <span class="font-mono">{{ $meta['reversal_reference'] ?? 'N/A' }}</span></p>
                    <p class="mt-0.5">Status: <span class="border border-black px-1.5 py-0.5 bg-gray-100 text-red-700 font-black">REVERSED</span></p>
                @elseif($meta['mode'] === 'waived')
                    <p>Settlement: {{ $meta['settlement_text'] }}</p>
                    <p class="mt-0.5">Status: <span class="border border-black px-1.5 py-0.5 bg-gray-100 font-black">WAIVED</span></p>
                @elseif($meta['mode'] === 'active' || $meta['mode'] === 'legacy_unpaid')
                    <p>Method: {{ $meta['method'] }}</p>
                    @if(!empty($meta['trx_id']))
                        <p>Trx ID: {{ $meta['trx_id'] }}</p>
                    @endif
                    @if($isAdmission && !empty($meta['active_receipt_nos']))
                        <p>Receipt(s): <span class="font-mono">{{ implode(', ', $meta['active_receipt_nos']) }}</span></p>
                    @endif
                    <p class="mt-0.5">Status: <span class="border border-black px-1.5 py-0.5 bg-gray-50">{{ $receiptData['status_label'] }}</span></p>
                @else
                    <p>Payment: No Active Payment</p>
                    <p class="mt-0.5">Status: <span class="border border-black px-1.5 py-0.5 bg-gray-50">{{ $receiptData['status_label'] }}</span></p>
                @endif

                @if(!$scopedReversed && $receiptData['reversed_payments']->isNotEmpty())
                    <p class="text-[8.5px] text-gray-600 pt-0.5">
                        Reversed Excluded:
                        {{ $receiptData['reversed_payments']->map(fn ($rp) => $rp->receipt_no . ($rp->reversal_reference ? ' (' . $rp->reversal_reference . ')' : ''))->join(', ') }}
                    </p>
                @endif
            </div>

            <div class="w-56 space-y-0.5 text-[10px] font-bold">
                @if($isAdmission || $receiptData['has_discount'])
                    <div class="flex justify-between text-[9.5px]">
                        <span class="text-gray-600">Gross Amount:</span>
                        <span class="font-mono">৳{{ $receiptData['gross_total_formatted'] }}</span>
                    </div>
                    <div class="flex justify-between text-[9.5px]">
                        <span class="text-gray-600">Discount:</span>
                        <span class="font-mono">-৳{{ $receiptData['discount_total_formatted'] }}</span>
                    </div>
                @endif
                @if(!$isAdmission && !empty($receiptData['has_fine']))
                    <div class="flex justify-between text-[9.5px]">
                        <span class="text-gray-600">Tuition Net:</span>
                        <span class="font-mono">৳{{ $receiptData['net_total_formatted'] }}</span>
                    </div>
                    <div class="flex justify-between text-[9.5px]">
                        <span class="text-gray-600">Late Fine:</span>
                        <span class="font-mono">+৳{{ $receiptData['fine_total_formatted'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Payable:</span>
                        <span class="font-mono">৳{{ $receiptData['total_payable_formatted'] }}</span>
                    </div>
                @else
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Payable:</span>
                        <span class="font-mono">৳{{ $receiptData['net_total_formatted'] }}</span>
                    </div>
                @endif

                @if($isScoped && $scopedReversed)
                    <div class="flex justify-between text-gray-500">
                        <span>Reversed Amount:</span>
                        <span class="font-mono line-through">৳{{ $receiptData['scoped_reversed_total_formatted'] }}</span>
                    </div>
                    <div class="flex justify-between text-red-700">
                        <span>Active Paid (Slip):</span>
                        <span class="font-mono">৳0.00</span>
                    </div>
                @elseif($isScoped)
                    <div class="flex justify-between text-green-700">
                        <span>{{ ($isAdmission || bccomp($receiptData['invoice_paid_total'], $receiptData['scoped_paid_total'], 2) !== 0) ? 'This Receipt Paid:' : 'Total Paid   :' }}</span>
                        <span class="font-mono">৳{{ $receiptData['scoped_paid_total_formatted'] }}</span>
                    </div>
                    @if(bccomp($receiptData['invoice_paid_total'], $receiptData['scoped_paid_total'], 2) !== 0)
                        <div class="flex justify-between text-[9.5px] text-gray-700">
                            <span>Invoice Paid Total:</span>
                            <span class="font-mono">৳{{ $receiptData['invoice_paid_total_formatted'] }}</span>
                        </div>
                    @endif
                @else
                    <div class="flex justify-between text-green-700">
                        <span>Total Paid   :</span>
                        <span class="font-mono">৳{{ $receiptData['invoice_paid_total_formatted'] }}</span>
                    </div>
                @endif

                <div class="border-t border-black my-0.5"></div>
                <div class="flex justify-between font-black text-[11.5px] text-red-600">
                    <span>Remaining Due:</span>
                    <span class="font-mono">৳{{ $receiptData['invoice_due_total_formatted'] }}</span>
                </div>
            </div>
        </div>

        <!-- Footer: Amount in Words & Cashier Signature -->
        <div class="flex justify-between items-end mt-5 pt-2 border-t border-dashed border-gray-300 gap-4">
            <div class="flex-1 min-w-0 pr-2">
                <p class="text-[8.5px] font-bold text-gray-500 uppercase tracking-wider">In Words:</p>
                <p class="text-[10px] font-bold leading-snug text-gray-900 break-words">{{ $receiptData['paid_in_words'] ?? \App\Helpers\NumberToWords::convert($isScoped ? ($scopedReversed ? '0.00' : $receiptData['scoped_paid_total']) : $receiptData['invoice_paid_total']) }}</p>
            </div>
            <div class="text-center w-32 shrink-0">
                <div class="border-t border-black w-full mb-0.5"></div>
                <p class="text-[8.5px] font-black uppercase text-gray-600">Authorized Cashier</p>
            </div>
        </div>
    </div>

</div>
