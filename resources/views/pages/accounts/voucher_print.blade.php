<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Voucher - {{ $expense->voucher_no }}</title>
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
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                gap: 20px;
            }
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
            .voucher-container {
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
                height: 205mm;
                background: #fff;
                padding: 6mm;
                box-sizing: border-box;
                display: flex;
                gap: 6mm;
            }
            .voucher-container {
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
        .double-line { border-top: 3px double #000; margin: 8px 0; }
        .dashed-line { border-top: 1px dashed #000; margin: 8px 0; }
    </style>
</head>
<body>

    <!-- Print / Close Bar -->
    <div class="fixed top-5 right-5 flex gap-3 no-print z-50">
        <button onclick="window.print()" class="bg-gray-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-lg hover:bg-gray-800 transition flex items-center justify-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print Voucher
        </button>
        <button onclick="window.close()" class="bg-red-100 text-red-700 font-bold py-2.5 px-6 rounded-xl hover:bg-red-200 transition text-sm">
            Close
        </button>
    </div>

    <!-- Dual Copy Page Container (Office Copy & Payee Copy) -->
    <div class="page-container">
        @foreach(['OFFICE COPY', 'PAYEE COPY'] as $copyTitle)
            <div class="voucher-container text-[12px] leading-normal">

                <!-- Voucher Header -->
                <div>
                    <div class="flex justify-between items-start">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-12 h-12 object-contain filter grayscale contrast-125" alt="Logo">
                            <div>
                                <h1 class="text-base font-black tracking-tight uppercase leading-none">MACS School and College</h1>
                                <p class="text-[9px] mt-1 font-semibold text-gray-700">Jalalpur, Pabna Sadar, Pabna</p>
                                <p class="text-[9px] font-semibold text-gray-700">Phone: 01896-220299, 01896-220300</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black uppercase border border-black px-2 py-0.5 bg-gray-100 mb-1 inline-block">{{ $copyTitle }}</span>
                            <h2 class="text-sm font-black uppercase block leading-none mt-1">Debit Voucher</h2>
                            <p class="text-[10px] mt-1 font-semibold">Voucher No: <strong class="font-mono">{{ $expense->voucher_no }}</strong></p>
                            <p class="text-[10px] font-semibold">Date: {{ date('d-M-Y', strtotime($expense->expense_date)) }}</p>
                        </div>
                    </div>

                    <div class="double-line"></div>

                    <!-- Payment Details Table/Grid -->
                    <div class="space-y-2 py-2 text-[11px]">
                        <div class="grid grid-cols-6 gap-2">
                            <span class="font-bold text-gray-700 col-span-2">Paid To:</span>
                            <span class="font-extrabold text-gray-900 col-span-4">{{ $expense->paid_to }}</span>
                        </div>
                        <div class="grid grid-cols-6 gap-2">
                            <span class="font-bold text-gray-700 col-span-2">Expense Category:</span>
                            <span class="font-bold text-gray-900 col-span-4">{{ $expense->category->name ?? 'General Expense' }}</span>
                        </div>
                        <div class="grid grid-cols-6 gap-2">
                            <span class="font-bold text-gray-700 col-span-2">Branch:</span>
                            <span class="font-semibold text-gray-800 col-span-4">{{ $expense->branch->branch_name ?? 'Main Branch / General' }}</span>
                        </div>
                        <div class="grid grid-cols-6 gap-2">
                            <span class="font-bold text-gray-700 col-span-2">Payment Method:</span>
                            <span class="font-bold text-gray-900 col-span-4 uppercase">{{ $expense->payment_method }}</span>
                        </div>
                        @if($expense->reference_no)
                        <div class="grid grid-cols-6 gap-2">
                            <span class="font-bold text-gray-700 col-span-2">Reference / Trx:</span>
                            <span class="font-mono text-gray-900 col-span-4">{{ $expense->reference_no }}</span>
                        </div>
                        @endif
                        @if($expense->note)
                        <div class="grid grid-cols-6 gap-2">
                            <span class="font-bold text-gray-700 col-span-2">Particulars / Note:</span>
                            <span class="text-gray-800 col-span-4">{{ $expense->note }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="dashed-line"></div>

                    <!-- Financial Summary Box -->
                    <div class="my-3 p-3 bg-gray-50 border border-black rounded flex justify-between items-center">
                        <div>
                            <span class="text-[10px] font-black uppercase text-gray-600 block">Total Amount Paid</span>
                            <span class="text-[10px] text-gray-500 font-medium italic">Payment verified and recorded</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xl font-black font-mono">৳ {{ number_format($expense->amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Voucher Footer Signatures -->
                <div class="pt-6">
                    <div class="grid grid-cols-4 gap-2 text-center text-[10px] font-bold">
                        <div>
                            <div class="border-t border-black pt-1">Prepared By</div>
                            <div class="text-[8px] text-gray-600 font-normal mt-0.5 truncate">{{ $expense->creator->name ?? 'Admin' }}</div>
                        </div>
                        <div>
                            <div class="border-t border-black pt-1">Accountant</div>
                            <div class="text-[8px] text-gray-600 font-normal mt-0.5">&nbsp;</div>
                        </div>
                        <div>
                            <div class="border-t border-black pt-1">Approved By</div>
                            <div class="text-[8px] text-gray-600 font-normal mt-0.5">&nbsp;</div>
                        </div>
                        <div>
                            <div class="border-t border-black pt-1">Received By</div>
                            <div class="text-[8px] text-gray-600 font-normal mt-0.5 truncate">{{ $expense->paid_to }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-2 border-t border-gray-200 flex justify-between text-[8px] text-gray-500 font-mono">
                        <span>Printed at: {{ date('d-M-Y h:i A') }}</span>
                        <span>MACS Accounts & Finance</span>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</body>
</html>
