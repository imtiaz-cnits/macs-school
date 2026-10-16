@extends('tyro-dashboard::layouts.admin')

@section('title', 'Payables & Due')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="payablesDashboard()">

    <!-- Page Header (Rule 4) -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Payables & Due
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-450 mt-1">
                Vendor liabilities, opening balances, payment tracking, and scheduled bill maturities
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <button type="button" @click="isOpeningBalanceModalOpen = true" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Register Opening Due
            </button>
            <a href="{{ route('accounts.vendor-payments.create') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Record Payment
            </a>
        </div>
    </div>

    <!-- 4 Primary KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <!-- Total Outstanding Payables -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-550">Total Outstanding Payables</span>
                <span class="w-8 h-8 rounded-xl bg-red-50 dark:bg-red-950/20 flex items-center justify-center text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($totalOutstandingPayable, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1 flex items-center justify-between">
                <span>Bills: ৳ {{ number_format($totalBillsDue, 2) }}</span>
                <span>Opening: ৳ {{ number_format($totalOBDue, 2) }}</span>
            </div>
        </div>

        <!-- Due Soon (Next 7 Days) -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-400">Due Within 7 Days</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/20 flex items-center justify-center text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400">
                ৳ {{ number_format($totalDueSoon, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                Upcoming bill maturities
            </div>
        </div>

        <!-- Overdue Payables -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-400">Overdue Payables</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/20 flex items-center justify-center text-rose-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400">
                ৳ {{ number_format($totalOverdue, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-rose-500 mt-1">
                Action required on expired due dates
            </div>
        </div>

        <!-- Bill Status Breakdown -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-550 block mb-2">Bill Status Overview</span>
            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between items-center text-gray-700 dark:text-gray-300 font-semibold">
                    <span class="flex items-center gap-1.5 text-[11px] text-gray-500"><span class="w-2 h-2 rounded-full bg-red-500"></span> Unpaid Bills:</span>
                    <span class="font-mono font-bold">{{ $unpaidCount }}</span>
                </div>
                <div class="flex justify-between items-center text-gray-700 dark:text-gray-300 font-semibold">
                    <span class="flex items-center gap-1.5 text-[11px] text-gray-500"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Partially Paid:</span>
                    <span class="font-mono font-bold">{{ $partiallyPaidCount }}</span>
                </div>
                <div class="flex justify-between items-center text-gray-700 dark:text-gray-300 font-semibold">
                    <span class="flex items-center gap-1.5 text-[11px] text-gray-500"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Fully Settled:</span>
                    <span class="font-mono font-bold">{{ $paidCount }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Outstanding Balances Table Card (Rule 9) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-white/[0.06]">
            <div>
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Vendor & Payee Outstanding Balances
                </h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Authoritative balances derived from bills, opening dues, and settled payment allocations</p>
            </div>
            <a href="{{ route('accounts.vendors.index') }}" class="text-xs font-bold text-themeBlue hover:underline flex items-center gap-1">
                View All Vendors &rarr;
            </a>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Vendor / Payee</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Contact</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Opening Due</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Bills Due</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Overdue</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Total Due</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-36">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendorSummaries as $vs)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-3.5 px-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $vs['name'] }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-gray-600 dark:text-gray-400">
                            {{ $vs['phone'] ?: ($vs['contact_person'] ?: '—') }}
                        </td>
                        <td class="py-3.5 px-4 font-mono font-bold text-xs text-right text-gray-600 dark:text-gray-400">
                            ৳ {{ number_format($vs['opening_due'], 2) }}
                        </td>
                        <td class="py-3.5 px-4 font-mono font-bold text-xs text-right text-gray-600 dark:text-gray-400">
                            ৳ {{ number_format($vs['bills_due'], 2) }}
                        </td>
                        <td class="py-3.5 px-4 font-mono font-bold text-xs text-right">
                            @if($vs['overdue_amount'] > 0)
                                <span class="text-rose-600 font-black">৳ {{ number_format($vs['overdue_amount'], 2) }}</span>
                            @else
                                <span class="text-gray-400">৳ 0.00</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-mono font-black text-sm text-right text-red-600 dark:text-red-400">
                            ৳ {{ number_format($vs['total_due'], 2) }}
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('accounts.vendor-payments.create', ['vendor_id' => $vs['id']]) }}" class="action-btn text-themeGreen hover:text-themeGreen hover:border-themeGreen" title="Pay Vendor Due">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </a>
                                <a href="{{ route('accounts.payables.ledger', $vs['id']) }}" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="View Vendor Ledger">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-xs text-gray-400">
                            No outstanding vendor dues found. All liabilities are fully settled.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Critical Bills Approaching Due Date Table -->
    @if($criticalBills->isNotEmpty())
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-8">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-white/[0.06]">
            <div>
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Critical Bills Approaching Due Date / Overdue
                </h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Bills with upcoming maturity dates requiring timely disbursement</p>
            </div>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Voucher No</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Vendor</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Bill Date</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Due Date</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Status</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Bill Total</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Remaining Due</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($criticalBills as $bill)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-3 px-4 font-mono font-bold text-xs text-themeBlue whitespace-nowrap">{{ $bill->voucher_no }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-gray-900 dark:text-gray-100">{{ $bill->vendor?->name ?? $bill->paid_to }}</td>
                        <td class="py-3 px-4 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ date('d M Y', strtotime($bill->expense_date)) }}</td>
                        <td class="py-3 px-4 text-xs font-bold whitespace-nowrap {{ $bill->is_overdue ? 'text-rose-600' : 'text-gray-800 dark:text-gray-200' }}">
                            {{ date('d M Y', strtotime($bill->due_date)) }}
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($bill->is_overdue)
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-rose-50 text-rose-600 dark:bg-rose-950/20 dark:text-rose-400">Overdue</span>
                            @elseif($bill->payment_status === 'Partially Paid')
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-amber-50 text-amber-600 dark:bg-amber-950/20 dark:text-amber-400">Partial</span>
                            @else
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-gray-100 text-gray-700 dark:bg-white/[0.05] dark:text-gray-300">Unpaid</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-xs text-right text-gray-600 dark:text-gray-400">৳ {{ number_format($bill->amount, 2) }}</td>
                        <td class="py-3 px-4 font-mono font-black text-xs text-right text-red-600">৳ {{ number_format($bill->due_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('accounts.vendor-payments.create', ['vendor_id' => $bill->vendor_id]) }}" class="action-btn text-themeGreen hover:text-themeGreen hover:border-themeGreen" title="Settle Bill">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Payment Activity Table -->
    @if($recentPayments->isNotEmpty())
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-8">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-white/[0.06]">
            <div>
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Recent Payment Settlements
                </h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Authoritative cash disbursements applied against payables</p>
            </div>
            <a href="{{ route('accounts.vendor-payments.index') }}" class="text-xs font-bold text-themeBlue hover:underline flex items-center gap-1">
                View All Payments &rarr;
            </a>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Payment No</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Vendor</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Method</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Amount Paid</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Voucher</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentPayments as $payment)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-3 px-4 font-mono font-bold text-xs text-themeBlue">{{ $payment->payment_no }}</td>
                        <td class="py-3 px-4 text-xs text-gray-700 dark:text-gray-300">{{ date('d M Y', strtotime($payment->payment_date)) }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-gray-900 dark:text-gray-100">{{ $payment->vendor?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-xs">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-gray-100 dark:bg-white/[0.05] text-gray-800 dark:text-gray-200">
                                {{ $payment->payment_method }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono font-black text-xs text-right text-emerald-600">৳ {{ number_format($payment->amount, 2) }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('accounts.vendor-payments.show', $payment->id) }}" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="View Settlement Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Register Opening Balance Modal (Rule 3) -->
    <div x-show="isOpeningBalanceModalOpen" x-cloak @click.self="isOpeningBalanceModalOpen = false" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6 overflow-hidden" style="display: none;">
        <div class="fixed inset-0 bg-themeDark/60 backdrop-blur-md transition-opacity" @click="isOpeningBalanceModalOpen = false"></div>

        <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl max-w-xl w-full flex flex-col overflow-hidden z-10 my-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <form action="{{ route('accounts.payables.opening_balance.store') }}" method="POST" class="flex flex-col">
                @csrf

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] bg-white dark:bg-themeNavy shrink-0">
                    <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Register Vendor Opening Balance
                    </h3>
                    <button type="button" @click="isOpeningBalanceModalOpen = false" class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark/60 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <input type="hidden" name="vendor_id" :value="selectedVendorId">

                    <!-- Vendor Selector -->
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Vendor / Payee <span class="text-red-500">*</span>
                        </label>
                        <div class="relative" @click.away="vendorDropdownOpen = false">
                            <button type="button" @click="vendorDropdownOpen = !vendorDropdownOpen" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate" x-text="selectedVendorName || 'Select Vendor'"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="vendorDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="vendorDropdownOpen" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-56 overflow-y-auto" x-transition>
                                @foreach($vendors as $v)
                                    <button type="button" @click="selectedVendorId = '{{ $v->id }}'; selectedVendorName = '{{ addslashes($v->name) }}'; vendorDropdownOpen = false;" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45" :class="selectedVendorId == '{{ $v->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>{{ $v->name }}</span>
                                        <template x-if="selectedVendorId == '{{ $v->id }}'">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Opening Balance Amount -->
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Opening Balance Amount (৳) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="opening_balance" required placeholder="0.00" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-black font-mono text-gray-800 dark:text-gray-200 px-3.5">
                    </div>

                    <!-- Effective Date & Ref -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Effective Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="effective_date" value="{{ date('Y-m-d') }}" required class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                        </div>
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Original Bill / Ref No (Optional)
                            </label>
                            <input type="text" name="reference_no" placeholder="e.g. INV-2025-09" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                        </div>
                    </div>

                    <!-- Note -->
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Notes / Details (Optional)
                        </label>
                        <input type="text" name="note" placeholder="Historical balance description" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                    </div>

                    <div class="p-3 bg-blue-50/60 dark:bg-themeBlue/10 rounded-xl text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        <strong>Accounting Note:</strong> Registering an opening balance records pre-existing liabilities into the ledger without inflating current-period expenses or fabricating cash outflows.
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50/60 dark:bg-themeDark/45 border-t border-gray-100 dark:border-white/[0.06] flex items-center justify-end gap-3">
                    <button type="button" @click="isOpeningBalanceModalOpen = false" class="h-10 px-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="h-10 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg transition-all uppercase tracking-wider">
                        Save Opening Due
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function payablesDashboard() {
        return {
            isOpeningBalanceModalOpen: false,
            vendorDropdownOpen: false,
            selectedVendorId: '',
            selectedVendorName: '',
        };
    }
</script>
@endpush
