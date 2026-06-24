<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Receipt - {{ $receipt_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Courier New', Courier, monospace; color: #000; background: #f3f4f6; margin: 0; padding: 20px 0; display: flex; justify-content: center; }
        .pos-receipt { width: 80mm; background: #fff; padding: 5mm; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        @media print {
            body { background: #fff; padding: 0; display: block; }
            .pos-receipt { width: 100%; max-width: 80mm; box-shadow: none; margin: 0 auto; padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 0; }
        }
        .dashed-line { border-top: 1px dashed #000; margin: 8px 0; }
    </style>
</head>
<body>
    <div class="fixed top-5 right-5 flex flex-col gap-3 no-print">
        <button onclick="window.print()" class="bg-gray-900 text-white font-bold py-3 px-6 rounded hover:bg-gray-800">Print Master POS</button>
        <button onclick="window.close()" class="bg-red-100 text-red-700 font-bold py-2 px-6 rounded">Close</button>
    </div>

    <div class="pos-receipt text-[12px] leading-tight">
        <div class="text-center mb-3">
            <h2 class="text-[16px] font-black uppercase mb-1">Pabna International School</h2>
            <p class="text-[10px]">Master Payment Receipt</p>
        </div>

        <div class="dashed-line"></div>
        <div class="mb-2">
            <div class="flex justify-between"><span>Receipt No:</span> <strong>{{ $receipt_no }}</strong></div>
            <div class="flex justify-between"><span>Date:</span> <span>{{ date('d-M-Y', strtotime($date)) }}</span></div>
            <div class="flex justify-between"><span>ID:</span> <strong>{{ $student->student_identity }}</strong></div>
            <div class="flex justify-between"><span>Name:</span> <span>{{ $student->student_name }}</span></div>
        </div>

        <div class="dashed-line"></div>
        <div class="font-bold text-[11px] mb-1">Payment Details:</div>
        
        <table class="w-full text-left mb-2">
            <tbody>
                @php $grandTotal = 0; @endphp
                @foreach($payments as $pay)
                <tr>
                    <td class="py-1 pr-1 text-[11px]">
                        {{ $pay->invoice->feeSetup->category->name }} ({{ $pay->invoice->feeSetup->fee_month ?? 'One Time' }})
                    </td>
                    <td class="py-1 text-right font-bold">{{ number_format($pay->paid_amount, 2) }}</td>
                </tr>
                @php $grandTotal += $pay->paid_amount; @endphp
                @endforeach
            </tbody>
        </table>

        <div class="dashed-line"></div>
        <div class="flex justify-between font-black text-[15px] my-2">
            <span>GRAND TOTAL:</span> <span>{{ number_format($grandTotal, 2) }}</span>
        </div>
        <div class="dashed-line"></div>

        <div class="text-center mt-4">
            <p class="text-[10px] mb-1">Received By: {{ $collector->name }}</p>
            <p class="text-[9px] mt-2 font-bold">Powered by CodeNext IT</p>
        </div>
    </div>
    <script>window.onload = function() { setTimeout(() => { window.print(); }, 1000); }</script>
</body>
</html>