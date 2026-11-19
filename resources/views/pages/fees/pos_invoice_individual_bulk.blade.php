<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Receipts - {{ $receipt_no }}</title>
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
                gap: 20px;
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
                padding: 5mm 6mm;
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
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .page-container:last-child,
            .page-container:last-of-type {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .receipt-container {
                width: 100%;
                min-height: 194mm;
                border: 1.5px solid #000;
                padding: 4.5mm 5.5mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                page-break-inside: avoid;
                break-inside: avoid;
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

    <div class="fixed top-5 right-5 flex gap-3 no-print z-50">
        <button onclick="window.print()" class="bg-gray-900 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg hover:bg-gray-800 transition flex items-center justify-center text-xs uppercase tracking-wider cursor-pointer">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Receipts
        </button>
        <button onclick="window.close()" class="bg-red-50 text-red-700 font-bold py-2.5 px-5 rounded-xl border border-red-200 hover:bg-red-100 transition text-xs uppercase tracking-wider text-center cursor-pointer">
            Close
        </button>
    </div>

    @php
        $resolvedCards = isset($slipCards)
            ? $slipCards
            : collect($invoices ?? [])->map(fn ($inv) => app(\App\Services\FeeReceiptService::class)->buildSingleInvoiceReceiptData($inv));
    @endphp

    <!-- One Single-Copy A5 Portrait Receipt Per Page -->
    @foreach($resolvedCards as $cardData)
        <div class="page-container">
            @include('pages.fees.partials._pos_invoice_card', [
                'receiptData' => $cardData,
                'copyTitle' => null,
            ])
        </div>
    @endforeach
</body>
</html>
