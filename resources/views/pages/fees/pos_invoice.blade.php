<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* থার্মাল প্রিন্টারের জন্য বেসিক ফন্ট ও সাইজ */
        body {
            font-family: 'Courier New', Courier, monospace; /* পস মেশিনের জন্য এই ফন্টটি বেস্ট */
            color: #000;
            background: #f3f4f6; /* শুধু স্ক্রিনে দেখার জন্য ব্যাকগ্রাউন্ড */
            margin: 0;
            padding: 20px 0;
            display: flex;
            justify-content: center;
        }

        .pos-receipt {
            width: 80mm; /* স্ট্যান্ডার্ড পস রোল সাইজ */
            background: #fff;
            padding: 5mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* প্রিন্ট করার সময় এই সিএসএস কাজ করবে */
        @media print {
            body { background: #fff; padding: 0; display: block; }
            .pos-receipt { width: 100%; max-width: 80mm; box-shadow: none; margin: 0 auto; padding: 0; }
            .no-print { display: none !important; } /* প্রিন্ট বাটনের মতো জিনিস লুকানোর জন্য */
            @page { margin: 0; } /* ব্রাউজারের ডিফল্ট মার্জিন সরানোর জন্য */
        }
        
        .dashed-line { border-top: 1px dashed #000; margin: 8px 0; }
    </style>
</head>
<body>

    <div class="fixed top-5 right-5 flex flex-col gap-3 no-print">
        <button onclick="window.print()" class="bg-gray-900 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:bg-gray-800 transition flex items-center justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print POS
        </button>
        <button onclick="window.close()" class="bg-red-100 text-red-700 font-bold py-2 px-6 rounded-lg hover:bg-red-200 transition text-sm text-center">
            Close
        </button>
    </div>

    <div class="pos-receipt text-[12px] leading-tight">
        
        <div class="text-center mb-3">
            <h2 class="text-[16px] font-black uppercase mb-1">Polsah Cadet School</h2>
            <p class="text-[10px]">123 Education Road, City Area</p>
            <p class="text-[10px]">Phone: +880 1234 567890</p>
        </div>

        <div class="dashed-line"></div>
        <div class="text-center font-bold text-[14px] uppercase my-2">Fee Receipt</div>
        <div class="dashed-line"></div>

        <div class="mb-2">
            <div class="flex justify-between"><span>Inv No:</span> <strong>{{ $invoice->invoice_no }}</strong></div>
            <div class="flex justify-between"><span>Date:</span> <span>{{ date('d-M-Y', strtotime($invoice->created_at)) }}</span></div>
            <div class="flex justify-between"><span>Status:</span> <strong>{{ strtoupper($invoice->status) }}</strong></div>
        </div>

        <div class="dashed-line"></div>

        <div class="mb-2">
            <div><span class="font-bold">Name:</span> {{ $invoice->student->student_name ?? 'N/A' }}</div>
            <div><span class="font-bold">ID:</span> {{ $invoice->student->student_identity ?? 'N/A' }}</div>
            <div class="flex justify-between">
                <span><span class="font-bold">Class:</span> {{ $invoice->student->schoolClass->class_name ?? 'N/A' }}</span>
                <span><span class="font-bold">Roll:</span> {{ $invoice->student->roll_number ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="dashed-line"></div>

        <table class="w-full text-left mb-2">
            <thead>
                <tr>
                    <th class="py-1">Description</th>
                    <th class="py-1 text-right">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-1 pr-2">
                        {{ $invoice->feeSetup->category->name ?? 'Fee' }}
                        @if($invoice->feeSetup->fee_month)
                            <br><span class="text-[10px]">({{ $invoice->feeSetup->fee_month }})</span>
                        @endif
                    </td>
                    <td class="py-1 text-right align-top">{{ number_format($invoice->amount, 2) }}</td>
                </tr>
                @if($invoice->discount > 0)
                <tr>
                    <td class="py-1 text-right">Discount (-)</td>
                    <td class="py-1 text-right">{{ number_format($invoice->discount, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="dashed-line"></div>

        <div class="mb-3 space-y-1 text-[13px]">
            <div class="flex justify-between font-bold"><span>Total Bill:</span> <span>{{ number_format($invoice->net_amount, 2) }}</span></div>
            <div class="flex justify-between"><span>Paid:</span> <span>{{ number_format($invoice->paid_amount, 2) }}</span></div>
            <div class="dashed-line"></div>
            <div class="flex justify-between font-black text-[15px]"><span>Total Due:</span> <span>{{ number_format($invoice->due_amount, 2) }}</span></div>
        </div>

        <div class="dashed-line"></div>

        <div class="text-center mt-4">
            <p class="text-[10px] mb-1">Generated By: {{ $invoice->user->name ?? 'System' }}</p>
            <p class="text-[10px] italic">Thank you for your payment!</p>
            <p class="text-[9px] mt-2 font-bold">Powered by CodeNext IT</p>
        </div>

    </div>

    <script>
        window.onload = function() {
            // পেজ লোড হওয়ার ১ সেকেন্ড পর অটোমেটিক প্রিন্ট অপশন আসবে
            setTimeout(() => {
                window.print();
            }, 1000);
        }
    </script>
</body>
</html>