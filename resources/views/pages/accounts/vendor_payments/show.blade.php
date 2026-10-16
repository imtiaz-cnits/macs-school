@extends('tyro-dashboard::layouts.admin')

@section('title', 'Payment Voucher - ' . $payment->payment_no)

@push('styles')
<style>
    @media print {
        body { background: #fff !important; }
        .no-print { display: none !important; }
        .printable-sheet { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; }
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">

    <!-- Top Navigation / Controls (no-print) -->
    <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
        <div class="flex items-center gap-3">
            <a href="{{ route('accounts.vendor-payments.index') }}" class="h-10 w-10 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 flex items-center justify-center text-gray-600 dark:text-gray-300 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                    Payment Voucher: <span class="font-mono text-themeBlue">{{ $payment->payment_no }}</span>
                </h1>
                <p class="text-xs font-semibold text-gray-500 mt-0.5">Disbursement details and settlement allocations</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('accounts.payables.ledger', $payment->vendor_id) }}" class="h-10 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Vendor Ledger
            </a>
            <button onclick="window.print()" class="h-10 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Voucher
            </button>
        </div>
    </div>

    <!-- Voucher Printable Card -->
    <div class="printable-sheet bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-8 shadow-sm max-w-4xl mx-auto">

        <!-- Voucher Header -->
        <div class="flex justify-between items-start border-b-2 border-gray-200 dark:border-white/[0.1] pb-6 mb-6">
            <div class="flex items-center gap-4">
                <img src="{{ asset('img/macs_logo.jpeg') }}" class="w-16 h-16 object-contain" alt="Logo">
                <div>
                    <h2 class="text-xl font-black uppercase tracking-tight text-gray-900 dark:text-white leading-tight">MACS School and College</h2>
                    <p class="text-xs text-gray-500 font-semibold">Jalalpur, Pabna Sadar, Pabna</p>
                    <p class="text-xs text-gray-500 font-semibold">Phone: 01896-220299, 01896-220300</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-lg border border-emerald-300 dark:border-emerald-800 mb-1">
                    Payment Voucher (Disbursement)
                </span>
                <div class="text-xs font-mono font-bold text-gray-600 dark:text-gray-300">Voucher No: <span class="text-themeBlue font-black text-sm">{{ $payment->payment_no }}</span></div>
                <div class="text-xs text-gray-500 font-semibold">Date: {{ date('d-M-Y', strtotime($payment->payment_date)) }}</div>
            </div>
        </div>

        <!-- Vendor & Payment Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 rounded-2xl bg-gray-50/60 dark:bg-themeDark/40 border border-gray-100 dark:border-white/[0.06] mb-6 text-xs">
            <div>
                <span class="text-[10px] font-black uppercase text-gray-400 block mb-1">Paid To (Vendor / Payee)</span>
                <h3 class="text-base font-black text-gray-900 dark:text-white">{{ $payment->vendor->name }}</h3>
                @if($payment->vendor->company_name)
                    <p class="font-bold text-gray-700 dark:text-gray-300">{{ $payment->vendor->company_name }}</p>
                @endif
                <p class="text-gray-500 mt-1">Phone: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $payment->vendor->phone ?? 'N/A' }}</span></p>
                @if($payment->vendor->address)
                    <p class="text-gray-500">Address: {{ $payment->vendor->address }}</p>
                @endif
            </div>

            <div class="space-y-1.5 text-right md:text-right">
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 block">Payment Method</span>
                    <span class="inline-block font-black text-sm text-gray-900 dark:text-white">{{ $payment->payment_method }}</span>
                    @if($payment->reference_no)
                        <span class="text-xs font-mono text-gray-500 block">Ref: {{ $payment->reference_no }}</span>
                    @endif
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 block">Branch</span>
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $payment->branch ? $payment->branch->branch_name : 'Consolidated / Unassigned' }}</span>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase text-gray-400 block">Recorded By</span>
                    <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $payment->creator->name ?? 'System' }} on {{ $payment->created_at->format('d-M-Y h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Bill Settlement Allocations Table -->
        <div class="mb-6">
            <h4 class="text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                Settlement Allocations
            </h4>
            <table class="w-full text-left border-collapse border border-gray-200 dark:border-white/[0.08] text-xs">
                <thead>
                    <tr class="bg-gray-100 dark:bg-themeDark/60 font-black text-[10px] uppercase tracking-wider text-gray-600 dark:text-gray-300">
                        <th class="p-2.5 border-b border-r border-gray-200 dark:border-white/[0.08]">Item Type</th>
                        <th class="p-2.5 border-b border-r border-gray-200 dark:border-white/[0.08]">Reference / Voucher</th>
                        <th class="p-2.5 border-b border-r border-gray-200 dark:border-white/[0.08] text-right">Original Liability</th>
                        <th class="p-2.5 border-b border-gray-200 dark:border-white/[0.08] text-right">Amount Settled in This Trx</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/[0.08]">
                    @forelse($payment->allocations as $alloc)
                        <tr>
                            <td class="p-2.5 border-r border-gray-200 dark:border-white/[0.08]">
                                @if($alloc->allocatable_type === 'App\Models\VendorOpeningBalance')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-100 text-purple-800">Opening Balance</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-800">Expense Bill</span>
                                @endif
                            </td>
                            <td class="p-2.5 border-r border-gray-200 dark:border-white/[0.08] font-mono font-bold">
                                @if($alloc->allocatable_type === 'App\Models\VendorOpeningBalance')
                                    {{ $alloc->allocatable->reference_no ?? 'Opening Due #' . $alloc->allocatable_id }}
                                @else
                                    {{ $alloc->allocatable->voucher_no ?? 'Voucher #' . $alloc->allocatable_id }}
                                @endif
                            </td>
                            <td class="p-2.5 border-r border-gray-200 dark:border-white/[0.08] text-right font-mono">
                                @if($alloc->allocatable_type === 'App\Models\VendorOpeningBalance')
                                    ৳ {{ number_format($alloc->allocatable->opening_balance, 2) }}
                                @else
                                    ৳ {{ number_format($alloc->allocatable->amount, 2) }}
                                @endif
                            </td>
                            <td class="p-2.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                ৳ {{ number_format($alloc->allocated_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-400 italic">No bill allocations found for this payment record.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 dark:bg-themeDark/40 font-black text-xs border-t-2 border-gray-300 dark:border-white/[0.1]">
                        <td colspan="3" class="p-2.5 text-right uppercase tracking-wider text-gray-700 dark:text-gray-300">Total Disbursement Amount:</td>
                        <td class="p-2.5 text-right font-mono text-base font-black text-emerald-600 dark:text-emerald-400">
                            ৳ {{ number_format($payment->amount, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($payment->note)
            <div class="mb-6 p-3 rounded-xl bg-gray-50 dark:bg-themeDark/30 border border-gray-100 dark:border-white/[0.06] text-xs">
                <span class="text-[10px] font-black uppercase text-gray-400 block mb-0.5">Payment Note / Memo:</span>
                <p class="text-gray-700 dark:text-gray-300 italic">{{ $payment->note }}</p>
            </div>
        @endif

        @if($payment->receipt_url)
            <div class="mb-6 p-3 rounded-xl border border-gray-100 dark:border-white/[0.06] text-xs no-print flex items-center justify-between">
                <span class="font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    Payment Evidence / Receipt Document Attached
                </span>
                <a href="{{ $payment->receipt_url }}" target="_blank" class="px-3 py-1 bg-themeBlue text-white font-bold rounded-lg text-xs hover:bg-blue-600 transition-colors">
                    View Document
                </a>
            </div>
        @endif

        <!-- Signatures Row -->
        <div class="grid grid-cols-3 gap-6 pt-12 mt-8 text-center text-xs">
            <div>
                <div class="border-t border-gray-400 dark:border-gray-600 pt-1 font-bold">Paid By</div>
                <div class="text-[10px] text-gray-500 font-medium">Cashier / Accounts Officer</div>
            </div>
            <div>
                <div class="border-t border-gray-400 dark:border-gray-600 pt-1 font-bold">Received By</div>
                <div class="text-[10px] text-gray-500 font-medium">Payee Signature</div>
            </div>
            <div>
                <div class="border-t border-gray-400 dark:border-gray-600 pt-1 font-bold">Approved By</div>
                <div class="text-[10px] text-gray-500 font-medium">Head of Accounts / Principal</div>
            </div>
        </div>

    </div>

</div>
@endsection
