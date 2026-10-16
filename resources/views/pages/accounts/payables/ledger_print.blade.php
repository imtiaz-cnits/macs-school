<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Statement - {{ $vendor->name }} ({{ date('d-M-Y', strtotime($startDate)) }} to {{ date('d-M-Y', strtotime($endDate)) }})</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #000;
            background: #fff;
        }
        @media screen {
            body {
                background: #f3f4f6;
                padding: 30px;
                display: flex;
                flex-direction: column;
                align-items: center;
                min-height: 100vh;
            }
            .statement-sheet {
                width: 210mm;
                min-height: 297mm;
                background: #fff;
                padding: 15mm;
                border: 1px solid #ddd;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                box-sizing: border-box;
            }
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .statement-sheet {
                width: 100%;
                padding: 10mm;
                box-sizing: border-box;
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 6mm;
            }
        }
        .double-line { border-top: 3px double #000; margin: 8px 0; }
        .dashed-line { border-top: 1px dashed #666; margin: 6px 0; }
    </style>
</head>
<body>

    <!-- Print / Close Controls -->
    <div class="fixed top-5 right-5 flex gap-3 no-print z-50">
        <button onclick="window.print()" class="bg-gray-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-lg hover:bg-black transition flex items-center justify-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print Statement
        </button>
        <button onclick="window.close()" class="bg-red-100 text-red-700 font-bold py-2.5 px-6 rounded-xl hover:bg-red-200 transition text-sm">
            Close
        </button>
    </div>

    <!-- Printable A4 Sheet -->
    <div class="statement-sheet">

        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-black pb-3">
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-14 h-14 object-contain filter grayscale contrast-125" alt="MACS Logo">
                <div>
                    <h1 class="text-xl font-black uppercase tracking-tight leading-none">MACS School and College</h1>
                    <p class="text-[10px] mt-1 font-semibold text-gray-700">Jalalpur, Pabna Sadar, Pabna</p>
                    <p class="text-[10px] font-semibold text-gray-700">Phone: 01896-220299, 01896-220300</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[9px] font-black uppercase border border-black px-2 py-0.5 bg-gray-100 inline-block">Accounts Payable</span>
                <h2 class="text-sm font-black uppercase block leading-none mt-1">Vendor Ledger Statement</h2>
                <p class="text-[10px] mt-1 font-bold">Statement Period: {{ date('d-M-Y', strtotime($startDate)) }} to {{ date('d-M-Y', strtotime($endDate)) }}</p>
                <p class="text-[9px] font-semibold text-gray-600">Printed On: {{ date('d-M-Y h:i A') }}</p>
            </div>
        </div>

        <!-- Vendor & Account Summary Box -->
        <div class="grid grid-cols-2 gap-4 my-4 p-3 border border-black bg-gray-50/50 rounded text-xs">
            <div>
                <span class="text-[9px] font-black uppercase text-gray-550 block">Vendor / Payee Details</span>
                <h3 class="text-base font-black text-gray-900 leading-tight mt-0.5">{{ $vendor->name }}</h3>
                @if($vendor->company_name)
                    <p class="font-bold text-gray-700 text-xs">{{ $vendor->company_name }}</p>
                @endif
                <p class="text-gray-600 text-[11px] mt-1">Phone: <span class="font-bold text-gray-800">{{ $vendor->phone ?? 'N/A' }}</span></p>
                @if($vendor->address)
                    <p class="text-gray-600 text-[11px]">Address: {{ $vendor->address }}</p>
                @endif
            </div>
            <div class="text-right flex flex-col justify-between">
                <div>
                    <span class="text-[9px] font-black uppercase text-gray-550 block">Account Status</span>
                    <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded border {{ $closing_payable > 0 ? 'bg-amber-100 text-amber-900 border-amber-300' : 'bg-emerald-100 text-emerald-900 border-emerald-300' }}">
                        {{ $closing_payable > 0 ? 'Outstanding Payable Due' : 'Account Settled / Clear' }}
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-[10px] font-bold text-gray-600">Total Outstanding Balance:</span>
                    <div class="text-lg font-black font-mono text-gray-900">৳ {{ number_format($closing_payable, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- 5 Summary KPI Cards -->
        <div class="grid grid-cols-5 gap-2 mb-4 text-center">
            <div class="p-2 border border-black rounded bg-gray-50">
                <span class="text-[8px] font-black uppercase text-gray-600 block">Prior / Op. Due</span>
                <span class="text-xs font-black font-mono">৳ {{ number_format($prior_due, 2) }}</span>
            </div>
            <div class="p-2 border border-black rounded bg-gray-50">
                <span class="text-[8px] font-black uppercase text-gray-600 block">Period Billed</span>
                <span class="text-xs font-black font-mono">৳ {{ number_format($billed_in_period, 2) }}</span>
            </div>
            <div class="p-2 border border-black rounded bg-gray-50">
                <span class="text-[8px] font-black uppercase text-gray-600 block">Period Paid</span>
                <span class="text-xs font-black font-mono text-emerald-800">৳ {{ number_format($paid_in_period, 2) }}</span>
            </div>
            <div class="p-2 border border-black rounded bg-gray-100">
                <span class="text-[8px] font-black uppercase text-gray-900 block">Closing Due</span>
                <span class="text-xs font-black font-mono text-gray-900">৳ {{ number_format($closing_payable, 2) }}</span>
            </div>
            <div class="p-2 border border-black rounded bg-amber-50">
                <span class="text-[8px] font-black uppercase text-amber-900 block">Overdue</span>
                <span class="text-xs font-black font-mono text-amber-900">৳ {{ number_format($overdue_amount, 2) }}</span>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="mb-4">
            <table class="w-full text-left border-collapse border border-black text-[11px]">
                <thead>
                    <tr class="bg-gray-100 border-b border-black text-[9px] uppercase tracking-wider font-black">
                        <th class="p-2 border-r border-black w-24">Date</th>
                        <th class="p-2 border-r border-black w-28">Ref / Trx #</th>
                        <th class="p-2 border-r border-black">Particulars / Description</th>
                        <th class="p-2 border-r border-black w-20 text-center">Method</th>
                        <th class="p-2 border-r border-black w-24 text-right">Billed (Dr)</th>
                        <th class="p-2 border-r border-black w-24 text-right">Paid (Cr)</th>
                        <th class="p-2 w-28 text-right">Balance Due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black">
                    <!-- Opening Row -->
                    <tr class="bg-gray-50/50 font-bold">
                        <td class="p-2 border-r border-black">{{ date('d-M-Y', strtotime($startDate)) }}</td>
                        <td class="p-2 border-r border-black font-mono text-[10px]">OPENING</td>
                        <td class="p-2 border-r border-black italic text-gray-700">Opening Balance / Prior Dues Brought Forward</td>
                        <td class="p-2 border-r border-black text-center text-gray-500">—</td>
                        <td class="p-2 border-r border-black text-right font-mono">{{ $prior_due > 0 ? '৳ ' . number_format($prior_due, 2) : '—' }}</td>
                        <td class="p-2 border-r border-black text-right font-mono text-gray-500">—</td>
                        <td class="p-2 text-right font-mono font-black">৳ {{ number_format($prior_due, 2) }}</td>
                    </tr>

                    @forelse($transactions as $trx)
                        <tr>
                            <td class="p-2 border-r border-black whitespace-nowrap">{{ date('d-M-Y', strtotime($trx['date'])) }}</td>
                            <td class="p-2 border-r border-black font-mono font-bold text-[10px]">{{ $trx['ref_no'] }}</td>
                            <td class="p-2 border-r border-black">
                                <span class="font-bold">{{ $trx['particulars'] }}</span>
                                @if(!empty($trx['note']))
                                    <span class="block text-[10px] text-gray-600 italic">{{ $trx['note'] }}</span>
                                @endif
                            </td>
                            <td class="p-2 border-r border-black text-center text-[10px]">
                                {{ $trx['payment_method'] ?? '—' }}
                            </td>
                            <td class="p-2 border-r border-black text-right font-mono">
                                @if($trx['billed'] > 0)
                                    ৳ {{ number_format($trx['billed'], 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-2 border-r border-black text-right font-mono font-bold text-emerald-800">
                                @if($trx['paid'] > 0)
                                    ৳ {{ number_format($trx['paid'], 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-2 text-right font-mono font-black">
                                ৳ {{ number_format($trx['running_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500 italic">No transactions recorded for this vendor within the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 border-t-2 border-black font-black text-xs">
                        <td colspan="4" class="p-2 border-r border-black text-right uppercase tracking-wider">Total In Period / Final Balance:</td>
                        <td class="p-2 border-r border-black text-right font-mono">৳ {{ number_format($billed_in_period, 2) }}</td>
                        <td class="p-2 border-r border-black text-right font-mono text-emerald-800">৳ {{ number_format($paid_in_period, 2) }}</td>
                        <td class="p-2 text-right font-mono text-sm bg-gray-200">৳ {{ number_format($closing_payable, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Notes / Disclaimer -->
        <div class="text-[9px] text-gray-600 italic border-l-2 border-gray-400 pl-2 mb-8">
            * This is an authoritative, system-generated statement of accounts payable for {{ $vendor->name }}. All billings, payments, and running balances are derived from canonical school expense vouchers and verified vendor disbursements.
        </div>

        <!-- Dual Signature Section -->
        <div class="grid grid-cols-3 gap-6 pt-12 mt-auto text-center text-xs">
            <div>
                <div class="border-t border-black pt-1 font-bold">Prepared By</div>
                <div class="text-[9px] text-gray-500 font-medium">Accounts Officer</div>
            </div>
            <div>
                <div class="border-t border-black pt-1 font-bold">Verified By</div>
                <div class="text-[9px] text-gray-500 font-medium">Chief Accountant / Internal Auditor</div>
            </div>
            <div>
                <div class="border-t border-black pt-1 font-bold">Approved By</div>
                <div class="text-[9px] text-gray-500 font-medium">Principal / Managing Authority</div>
            </div>
        </div>

    </div>

</body>
</html>
