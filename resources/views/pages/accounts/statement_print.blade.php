<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Statement ({{ date('d-M-Y', strtotime($startDate)) }} to {{ date('d-M-Y', strtotime($endDate)) }})</title>
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
                display: flex;
                flex-direction: column;
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
                min-height: calc(297mm - 10mm);
                padding: 10mm;
                box-sizing: border-box;
                border: none;
                box-shadow: none;
                display: flex;
                flex-direction: column;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 5mm;
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
                <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-14 h-14 object-contain" alt="MACS Logo">
                <div>
                    <h1 class="text-xl font-black uppercase tracking-tight leading-none">MACS School and College</h1>
                    <p class="text-[10px] mt-1 font-semibold text-gray-700">Jalalpur, Pabna Sadar, Pabna</p>
                    <p class="text-[10px] font-semibold text-gray-700">Phone: 01896-220299, 01896-220300</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-[9px] font-black uppercase border border-black px-2 py-0.5 bg-gray-100 inline-block">Official Accounts</span>
                <h2 class="text-sm font-black uppercase block leading-none mt-1">Income & Expense Statement</h2>
                <p class="text-[10px] mt-1 font-bold">Period: {{ date('d-M-Y', strtotime($startDate)) }} to {{ date('d-M-Y', strtotime($endDate)) }}</p>
                <p class="text-[9px] font-semibold text-gray-600">Branch: {{ $selectedBranch ? $selectedBranch->branch_name : 'All Branches / Consolidated' }}</p>
            </div>
        </div>

        <!-- Executive Summary Cards Row -->
        <div class="grid grid-cols-4 gap-2 my-4 text-center">
            <div class="p-2 border border-black rounded bg-gray-50">
                <span class="text-[9px] font-black uppercase text-gray-600 block">Total Income (Collections)</span>
                <span class="text-sm font-black font-mono">৳ {{ number_format($totalIncome, 2) }}</span>
            </div>
            <div class="p-2 border border-black rounded bg-gray-50">
                <span class="text-[9px] font-black uppercase text-gray-600 block">Total Expenses</span>
                <span class="text-sm font-black font-mono">৳ {{ number_format($totalExpense, 2) }}</span>
            </div>
            <div class="p-2 border border-black rounded bg-gray-50">
                <span class="text-[9px] font-black uppercase text-gray-600 block">Net Surplus / (Deficit)</span>
                <span class="text-sm font-black font-mono {{ $netSurplus >= 0 ? '' : 'text-red-700' }}">
                    ৳ {{ number_format($netSurplus, 2) }}
                </span>
            </div>
            <div class="p-2 border border-black rounded bg-gray-100">
                <span class="text-[9px] font-black uppercase text-gray-800 block">Net Physical Cash Flow</span>
                <span class="text-sm font-black font-mono">৳ {{ number_format($netCashMovement, 2) }}</span>
            </div>
        </div>

        <div class="double-line"></div>

        <!-- Section 1: Income vs Expense Category Breakdown -->
        <div class="grid grid-cols-2 gap-4 my-3 text-[11px]">

            <!-- Incomes by Fee Category -->
            <div>
                <h3 class="text-xs font-black uppercase border-b border-black pb-1 mb-2 flex justify-between">
                    <span>1. Recognized Fee Incomes</span>
                    <span class="font-mono">৳ {{ number_format($totalIncome, 2) }}</span>
                </h3>
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-400 text-[10px] uppercase font-bold text-gray-700">
                            <th class="py-1">Fee Category</th>
                            <th class="py-1 text-center w-12">Count</th>
                            <th class="py-1 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomeByCategory as $inc)
                        <tr class="border-b border-gray-100">
                            <td class="py-1 font-medium">{{ $inc['name'] }}</td>
                            <td class="py-1 text-center font-mono text-[10px]">{{ $inc['count'] }}</td>
                            <td class="py-1 text-right font-mono font-bold">৳ {{ number_format($inc['amount'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-2 text-center text-gray-500 italic">No income recorded in period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Expenses by Expense Category -->
            <div>
                <h3 class="text-xs font-black uppercase border-b border-black pb-1 mb-2 flex justify-between">
                    <span>2. Operating Expenses</span>
                    <span class="font-mono">৳ {{ number_format($totalExpense, 2) }}</span>
                </h3>
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-400 text-[10px] uppercase font-bold text-gray-700">
                            <th class="py-1">Expense Category</th>
                            <th class="py-1 text-center w-12">Count</th>
                            <th class="py-1 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseByCategory as $exp)
                        <tr class="border-b border-gray-100">
                            <td class="py-1 font-medium">{{ $exp['name'] }}</td>
                            <td class="py-1 text-center font-mono text-[10px]">{{ $exp['count'] }}</td>
                            <td class="py-1 text-right font-mono font-bold">৳ {{ number_format($exp['amount'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-2 text-center text-gray-500 italic">No expenses recorded in period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div class="dashed-line"></div>

        <!-- Section 2: Method-Wise Cash Flow Reconciliation -->
        <div class="my-4 text-[11px]">
            <h3 class="text-xs font-black uppercase border-b border-black pb-1 mb-2">
                3. Payment Method & Cash Flow Reconciliation
            </h3>
            <table class="w-full text-left border border-black">
                <thead class="bg-gray-100 border-b border-black text-[10px] uppercase font-bold">
                    <tr>
                        <th class="p-1.5">Channel / Method</th>
                        <th class="p-1.5 text-right">Total Inflow (Income)</th>
                        <th class="p-1.5 text-right">Total Outflow (Expense)</th>
                        <th class="p-1.5 text-right">Net Movement</th>
                        <th class="p-1.5 text-center w-24">Balance Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($methodSummary as $row)
                        @if($row['in'] > 0 || $row['out'] > 0)
                        <tr class="border-b border-gray-300 {{ strtolower($row['method']) === 'cash' ? 'font-bold bg-gray-50' : '' }}">
                            <td class="p-1.5">
                                {{ $row['method'] }}
                                @if(strtolower($row['method']) === 'cash')
                                    <span class="text-[9px] uppercase font-black text-gray-600">(Physical Cash In Hand)</span>
                                @endif
                            </td>
                            <td class="p-1.5 text-right font-mono">৳ {{ number_format($row['in'], 2) }}</td>
                            <td class="p-1.5 text-right font-mono">৳ {{ number_format($row['out'], 2) }}</td>
                            <td class="p-1.5 text-right font-mono font-bold {{ $row['net'] >= 0 ? '' : 'text-red-700' }}">
                                ৳ {{ number_format($row['net'], 2) }}
                            </td>
                            <td class="p-1.5 text-center text-[10px] font-bold uppercase">
                                {{ $row['net'] >= 0 ? 'Surplus' : 'Deficit' }}
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    <tr class="bg-gray-100 font-black border-t-2 border-black">
                        <td class="p-1.5 uppercase text-[10px]">Net Operating Total</td>
                        <td class="p-1.5 text-right font-mono">৳ {{ number_format($totalIncome, 2) }}</td>
                        <td class="p-1.5 text-right font-mono">৳ {{ number_format($totalExpense, 2) }}</td>
                        <td class="p-1.5 text-right font-mono {{ $netSurplus >= 0 ? '' : 'text-red-700' }}">
                            ৳ {{ number_format($netSurplus, 2) }}
                        </td>
                        <td class="p-1.5 text-center text-[10px] uppercase">
                            {{ $netSurplus >= 0 ? 'Surplus' : 'Deficit' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Section 3: Notes & Compliance Notice -->
        <div class="my-3 p-2 border border-gray-300 rounded text-[9px] text-gray-600 leading-tight space-y-0.5">
            <div><strong>Accounting Notes & Institutional Compliance:</strong></div>
            <div>(1) Recognized income reflects only actual realized cash/bank/MFS collections deposited via money receipts. Unpaid invoices are treated as Accounts Receivable and not counted as income.</div>
            <div>(2) Physical Cash Movement (৳ {{ number_format($netCashMovement, 2) }}) represents actual cash drawer changes (Cash Collections - Cash Expenses). Digital transfers and cheque disbursements are reconciled in their respective banking lines.</div>
            <div>(3) Consolidated statements include central and unassigned institutional expenses, whereas individual campus statements exclude unassigned expenses.</div>
            <div>(4) The system does not record cash refunds or payment reversals. Fee adjustments solely modify invoice discounts/receivables and are not cash disbursements.</div>
        </div>

        <!-- Signatures Row -->
        <div class="mt-auto pt-8">
            <div class="grid grid-cols-3 gap-6 text-center text-[10px] font-bold">
                <div>
                    <div class="border-t border-black pt-1">Prepared By</div>
                    <div class="text-[8px] text-gray-500 font-normal mt-0.5">Accounts Officer</div>
                </div>
                <div>
                    <div class="border-t border-black pt-1">Accountant</div>
                    <div class="text-[8px] text-gray-500 font-normal mt-0.5">Finance Department</div>
                </div>
                <div>
                    <div class="border-t border-black pt-1">Headmaster / Principal</div>
                    <div class="text-[8px] text-gray-500 font-normal mt-0.5">MACS School & College</div>
                </div>
            </div>

            <div class="mt-6 pt-2 border-t border-gray-200 flex justify-between text-[8px] text-gray-500 font-mono">
                <span>Generated at: {{ date('d-M-Y h:i A') }}</span>
                <span>MACS School Financial Statement • Confidential</span>
            </div>
        </div>

    </div>

</body>
</html>
