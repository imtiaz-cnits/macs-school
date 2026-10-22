@extends('tyro-dashboard::layouts.admin')

@section('title', 'SMS Delivery Report')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .table th, .table td { padding: 0.75rem 1rem !important; }

    .print-only {
        display: none !important;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 6mm 7mm 6mm 7mm;
        }

        html, body {
            background: #ffffff !important;
            color: #0F1E2C !important;
            font-family: 'Figtree', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        body * {
            visibility: hidden;
        }

        #printableReportArea, #printableReportArea * {
            visibility: visible;
        }

        #printableReportArea {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
            display: block !important;
        }

        .no-print, nav, aside, header, footer, .sidebar, .sidebar-container, .topbar, .admin-bar, .sidebar-overlay, #globalModal {
            display: none !important;
        }

        .dashboard-layout, .main-content, .page-content {
            padding: 0 !important;
            margin: 0 !important;
            display: block !important;
            width: 100% !important;
            min-height: auto !important;
            background: transparent !important;
            border: none !important;
        }

        .print-only {
            display: block !important;
        }

        /* Printable Table Formatting */
        #printableReportArea table.report-table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border: 1px solid #475569 !important;
        }

        #printableReportArea thead {
            display: table-header-group !important;
        }

        #printableReportArea tbody {
            display: table-row-group !important;
        }

        #printableReportArea tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        #printableReportArea th {
            background-color: #008ED6 !important;
            color: #ffffff !important;
            font-size: 9.5px !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 5px 6px !important;
            border: 1px solid #0072ad !important;
            border-bottom: 1.5px solid #005a8c !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printableReportArea td {
            border: 1px solid #CBD5E1 !important;
            padding: 4px 5px !important;
            font-size: 8.5px !important;
            line-height: 1.25 !important;
            color: #0F1E2C !important;
            vertical-align: top !important;
        }

        #printableReportArea tbody tr:nth-child(even) td {
            background-color: #F8FAFC !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printableReportArea tbody tr:nth-child(odd) td {
            background-color: #FFFFFF !important;
        }

        .print-signatures {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            margin-top: 24px !important;
            padding-top: 10px !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
            width: 100% !important;
        }

        .print-signatures table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: none !important;
        }

        .print-signatures td {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
        }
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                SMS Delivery Report
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Monitor, filter, and review real-time text message delivery logs and balance metrics</p>
        </div>
        
        <div class="flex items-center gap-2.5">
            <!-- Print Report Button -->
            <button type="button" onclick="prepareAndPrintSmsReport()" id="printSmsReportBtn" title="Print Filtered SMS Delivery Report" class="h-10 px-4 bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-200 border-2 border-gray-200 dark:border-gray-800 hover:border-themeBlue hover:text-themeBlue dark:hover:text-themeBlue rounded-xl hover:-translate-y-0.5 hover:shadow-md transition-all text-xs font-black uppercase tracking-wider flex items-center gap-2 group active:scale-95 disabled:opacity-50 disabled:pointer-events-none">
                <svg class="w-4 h-4 text-themeBlue group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span id="printSmsBtnText">Print Report</span>
            </button>

            <!-- Refresh Gateway Balance Button -->
            <a href="{{ request()->fullUrlWithQuery(['refresh_balance' => 1]) }}" title="Sync Live Balance from Gateway" class="h-10 px-4 bg-white dark:bg-themeNavy text-themeBlue border-2 border-themeBlue/25 hover:border-themeBlue rounded-xl hover:bg-themeBlue/5 hover:-translate-y-0.5 hover:shadow-md transition-all text-xs font-black uppercase tracking-wider flex items-center gap-2 group">
                <svg class="w-4 h-4 text-themeBlue group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Sync Balance</span>
            </a>

            <!-- Send New SMS Button -->
            <a href="{{ route('sms.general-notice') }}" class="h-10 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>Send SMS</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards (4 Column Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
        <!-- Sent Today -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">Sent Today</div>
                <div class="w-8 h-8 rounded-xl bg-themeBlue/10 flex items-center justify-center text-themeBlue">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-themeBlue tracking-tight">{{ number_format($todaySent) }} <span class="text-xs font-semibold text-gray-400">sms</span></div>
            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 mt-1">Delivered today ({{ Carbon\Carbon::today('Asia/Dhaka')->format('d M') }})</div>
        </div>

        <!-- This Month -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">This Month</div>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-indigo-500 tracking-tight">{{ number_format($monthSent) }} <span class="text-xs font-semibold text-gray-400">sms</span></div>
            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 mt-1">Delivered in {{ Carbon\Carbon::now('Asia/Dhaka')->format('F Y') }}</div>
        </div>

        <!-- Total Delivered -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">Total Delivered</div>
                <div class="w-8 h-8 rounded-xl bg-themeGreen/10 flex items-center justify-center text-themeGreen">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-themeGreen tracking-tight">{{ number_format($totalSent) }} <span class="text-xs font-semibold text-gray-400">sms</span></div>
            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 mt-1">
                All-time successful
                @if($totalFailed > 0)
                    <span class="text-amber-500 font-bold">({{ number_format($totalFailed) }} other)</span>
                @endif
            </div>
        </div>

        <!-- Live SMS Balance -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">SMS Balance</div>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            @if($gatewayBalance['status'] === 'success')
                @php
                    $smsCount = $gatewayBalance['remaining_sms'] ?? (is_numeric($gatewayBalance['balance']) ? (int) floor((float)$gatewayBalance['balance'] / 0.33) : 0);
                @endphp
                <div class="text-3xl font-black text-themeGreen tracking-tight">
                    {{ number_format($smsCount) }}
                    <span class="text-xs font-semibold text-gray-400">sms</span>
                </div>
                <div class="text-xs font-medium text-gray-400 dark:text-gray-500 mt-1 flex items-center justify-between" title="Synced at {{ $gatewayBalance['synced_at'] }} from MiM SMS Gateway">
                    <span>Available to send</span>
                    <span class="flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live
                    </span>
                </div>
            @else
                <div class="text-2xl font-black text-amber-500 tracking-tight">Standby</div>
                <div class="text-xs font-medium text-gray-400 dark:text-gray-500 mt-1 truncate" title="{{ is_array($gatewayBalance['raw'] ?? null) ? json_encode($gatewayBalance['raw']) : ($gatewayBalance['message'] ?? 'Gateway connection standby') }}">
                    {{ $gatewayBalance['message'] ?? 'Synced via MiM SMS' }}
                </div>
            @endif
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 mb-6 shadow-sm">
        <form method="GET" action="{{ route('sms.report') }}" id="smsFilterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4 items-end">
                
                <!-- Search Input -->
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-[10px] font-black tracking-widest text-themeBlue uppercase mb-1.5">Search SMS</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Student, ID, mobile, message..." class="form-input !h-10 !text-xs bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all pl-9">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- Date Filter (Alpine Dropdown) -->
                <div x-data="{
                    open: false,
                    selectedValue: '{{ request('date_filter', '') }}',
                    selectedLabel: '{{ match(request('date_filter')) { 'today' => 'Today', 'yesterday' => 'Yesterday', 'last_7_days' => 'Last 7 Days', 'this_month' => 'This Month', default => 'All Dates' } }}',
                    select(label, val) {
                        this.selectedLabel = label;
                        this.selectedValue = val;
                        this.open = false;
                        document.getElementById('input_date_filter').value = val;
                        document.getElementById('smsFilterForm').submit();
                    }
                }" class="relative">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Date Range</label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span x-text="selectedLabel">All Dates</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="date_filter" id="input_date_filter" value="{{ request('date_filter') }}">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <button type="button" @click="select('All Dates', '')" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>All Dates</span>
                        </button>
                        <button type="button" @click="select('Today', 'today')" :class="selectedValue === 'today' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>Today</span>
                        </button>
                        <button type="button" @click="select('Yesterday', 'yesterday')" :class="selectedValue === 'yesterday' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>Yesterday</span>
                        </button>
                        <button type="button" @click="select('Last 7 Days', 'last_7_days')" :class="selectedValue === 'last_7_days' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>Last 7 Days</span>
                        </button>
                        <button type="button" @click="select('This Month', 'this_month')" :class="selectedValue === 'this_month' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>This Month</span>
                        </button>
                    </div>
                </div>

                <!-- Status Filter (Alpine Dropdown) -->
                <div x-data="{
                    open: false,
                    selectedValue: '{{ request('status', 'All') }}',
                    selectedLabel: '{{ match(request('status')) { 'Sent' => 'Sent', 'Failed' => 'Failed', 'Blocked (Test Mode)' => 'Blocked (Test)', 'Ambiguous' => 'Ambiguous', default => 'All Status' } }}',
                    select(label, val) {
                        this.selectedLabel = label;
                        this.selectedValue = val;
                        this.open = false;
                        document.getElementById('input_status').value = val;
                        document.getElementById('smsFilterForm').submit();
                    }
                }" class="relative">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Status</label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span x-text="selectedLabel">All Status</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="status" id="input_status" value="{{ request('status', 'All') }}">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <button type="button" @click="select('All Status', 'All')" :class="selectedValue === 'All' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>All Status</span>
                        </button>
                        <button type="button" @click="select('Sent', 'Sent')" :class="selectedValue === 'Sent' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Sent</span>
                        </button>
                        <button type="button" @click="select('Failed', 'Failed')" :class="selectedValue === 'Failed' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Failed</span>
                        </button>
                        <button type="button" @click="select('Blocked (Test)', 'Blocked (Test Mode)')" :class="selectedValue === 'Blocked (Test Mode)' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Blocked (Test Mode)</span>
                        </button>
                        <button type="button" @click="select('Ambiguous', 'Ambiguous')" :class="selectedValue === 'Ambiguous' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Ambiguous</span>
                        </button>
                    </div>
                </div>

                <!-- Type Filter (Alpine Dropdown) -->
                <div x-data="{
                    open: false,
                    selectedValue: '{{ request('event_type', 'All') }}',
                    selectedLabel: '{{ match(request('event_type')) { 'check_in' => 'Check-In', 'check_out' => 'Check-Out', 'general_notice' => 'Notice', 'result' => 'Result', default => 'All Types' } }}',
                    select(label, val) {
                        this.selectedLabel = label;
                        this.selectedValue = val;
                        this.open = false;
                        document.getElementById('input_event_type').value = val;
                        document.getElementById('smsFilterForm').submit();
                    }
                }" class="relative">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">SMS Type</label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span x-text="selectedLabel">All Types</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="event_type" id="input_event_type" value="{{ request('event_type', 'All') }}">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <button type="button" @click="select('All Types', 'All')" :class="selectedValue === 'All' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>All Types</span>
                        </button>
                        <button type="button" @click="select('Check-In', 'check_in')" :class="selectedValue === 'check_in' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>Attendance Check-In</span>
                        </button>
                        <button type="button" @click="select('Check-Out', 'check_out')" :class="selectedValue === 'check_out' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>Attendance Check-Out</span>
                        </button>
                        <button type="button" @click="select('Notice', 'general_notice')" :class="selectedValue === 'general_notice' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>General Notice</span>
                        </button>
                        <button type="button" @click="select('Result', 'result')" :class="selectedValue === 'result' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>Exam Result</span>
                        </button>
                    </div>
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 h-10 bg-themeBlue hover:bg-themeBlue/90 text-white text-xs font-black rounded-xl shadow-sm hover:shadow hover:-translate-y-0.5 transition-all uppercase tracking-wider flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Search</span>
                    </button>
                    <a href="{{ route('sms.report') }}" title="Reset All Filters" class="h-10 px-3 bg-gray-50/50 hover:bg-rose-50/70 dark:bg-themeDark dark:hover:bg-rose-950/20 text-gray-600 hover:text-rose-600 dark:text-gray-300 dark:hover:text-rose-400 border-2 border-gray-100 hover:border-rose-200 dark:border-gray-800 dark:hover:border-rose-900/40 rounded-xl transition-all flex items-center justify-center shadow-sm hover:-translate-y-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Logs List Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        
        @if($logs->isEmpty())
            <div class="text-center py-16">
                <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-themeDark flex items-center justify-center mx-auto mb-4 text-gray-400">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">No SMS Logs Found</h3>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No message logs match the selected filter criteria.</p>
                <div class="mt-4">
                    <a href="{{ route('sms.report') }}" class="btn-sm bg-gray-100 dark:bg-themeDark text-themeBlue rounded-xl px-4 py-2 font-black text-xs inline-flex items-center gap-1.5 hover:bg-themeBlue/10 transition-colors">
                        Clear All Filters
                    </a>
                </div>
            </div>
        @else
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-36">Date & Time</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-48">Recipient Info</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-36">Mobile Number</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-28 text-center">Type</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Message Content</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-28">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <!-- Date & Time -->
                            <td class="py-4 px-4 align-top whitespace-nowrap">
                                <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ \Carbon\Carbon::parse($log->created_at, 'Asia/Dhaka')->format('d M, Y') }}</div>
                                <div class="text-[10px] font-mono font-bold text-gray-400 dark:text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($log->created_at, 'Asia/Dhaka')->format('h:i:s A') }}</div>
                            </td>

                            <!-- Recipient Info -->
                            <td class="py-4 px-4 align-top">
                                @if($log->student)
                                    <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $log->student->student_name }}</div>
                                    <div class="text-[10px] font-mono font-semibold text-gray-400 dark:text-gray-500 mt-0.5">
                                        ID: <span class="text-gray-700 dark:text-gray-300 font-bold">{{ $log->student->student_identity }}</span>
                                        @if($log->student->schoolClass)
                                            <span class="mx-1 text-gray-300 dark:text-gray-700">|</span> {{ $log->student->schoolClass->class_name }}
                                        @endif
                                    </div>
                                @else
                                    <div class="text-gray-600 dark:text-gray-400 text-sm font-semibold">General Recipient</div>
                                    <div class="text-[10px] font-mono text-gray-400 mt-0.5">ID: N/A</div>
                                @endif
                            </td>

                            <!-- Mobile Number -->
                            <td class="py-4 px-4 align-top text-sm font-mono font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $log->mobile_number }}
                            </td>

                            <!-- Event Type -->
                            <td class="py-4 px-4 text-center align-top whitespace-nowrap">
                                @if($log->event_type === 'check_in')
                                    <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg inline-flex items-center gap-1 bg-themeBlue/10 text-themeBlue border border-themeBlue/20">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                        Check-In
                                    </span>
                                @elseif($log->event_type === 'check_out')
                                    <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg inline-flex items-center gap-1 bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Check-Out
                                    </span>
                                @elseif($log->event_type === 'result')
                                    <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg inline-block bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                                        Result
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg inline-block bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-white/[0.06]">
                                        Notice
                                    </span>
                                @endif
                            </td>

                            <!-- Message Content -->
                            <td class="py-4 px-4 align-top">
                                <div class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50/60 dark:bg-themeDark p-3 rounded-2xl border border-gray-150/80 dark:border-white/[0.05]">
                                    {{ $log->message }}
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4 text-center align-top whitespace-nowrap">
                                @if(strtolower($log->status) === 'sent')
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-xl inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-500/10 text-themeGreen dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-themeGreen"></span>
                                        Sent
                                    </span>
                                @elseif(strtolower($log->status) === 'failed')
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-xl inline-flex items-center gap-1.5 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Failed
                                    </span>
                                @elseif(str_contains(strtolower($log->status), 'blocked'))
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-xl inline-flex items-center gap-1.5 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Blocked (Test)
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-xl inline-flex items-center gap-1.5 bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                        {{ $log->status }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/[0.06] flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                    Showing <span class="font-bold text-gray-900 dark:text-gray-200">{{ $logs->firstItem() ?? 0 }}</span> to <span class="font-bold text-gray-900 dark:text-gray-200">{{ $logs->lastItem() ?? 0 }}</span> of <span class="font-bold text-gray-900 dark:text-gray-200">{{ $logs->total() }}</span> messages
                </div>
                <div>
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Official Printable Report Area (Print Only) -->
    @php
        $macsLogoPath = public_path('img/macs_logo.jpeg');
        $macsLogoSrc = file_exists($macsLogoPath) ? asset('img/macs_logo.jpeg') : (file_exists(public_path('img/logo.png')) ? asset('img/logo.png') : '');

        $initialDateLabel = match(request('date_filter')) {
            'today' => 'Today (' . Carbon\Carbon::today('Asia/Dhaka')->format('d M Y') . ')',
            'yesterday' => 'Yesterday (' . Carbon\Carbon::yesterday('Asia/Dhaka')->format('d M Y') . ')',
            'last_7_days' => 'Last 7 Days',
            'this_month' => 'This Month (' . Carbon\Carbon::now('Asia/Dhaka')->format('F Y') . ')',
            default => 'All Dates'
        };
        $initialStatusLabel = request('status', 'All');
        if ($initialStatusLabel === 'All' || empty($initialStatusLabel)) $initialStatusLabel = 'All Status';

        $initialTypeLabel = match(request('event_type')) {
            'check_in' => 'Attendance Check-In',
            'check_out' => 'Attendance Check-Out',
            'general_notice' => 'General Notice',
            'result' => 'Exam Result',
            default => 'All Types'
        };
    @endphp

    <div id="printableReportArea" class="print-only">
        <!-- Header: Logo (Left), School Info & Title (Center), Print Date (Right) -->
        <table style="width: 100%; border-collapse: collapse; border: none !important; margin-bottom: 3px;">
            <tr style="border: none !important; background: transparent !important;">
                <!-- Left: MACS Logo -->
                <td style="width: 14%; text-align: left; vertical-align: middle; border: none !important; padding: 0 !important; background: transparent !important;">
                    @if($macsLogoSrc)
                    <img src="{{ $macsLogoSrc }}" style="width: 52px; height: 52px; object-fit: contain; border-radius: 50%; border: 1.5px solid #008ED6; padding: 2px;" alt="MACS Logo">
                    @else
                    <div style="width: 50px; height: 50px; border: 1.5px solid #008ED6; border-radius: 50%; text-align: center; line-height: 50px; font-weight: 900; color: #008ED6; font-size: 11px;">MACS</div>
                    @endif
                </td>

                <!-- Center: School Details & Report Badge -->
                <td style="width: 72%; text-align: center; vertical-align: middle; border: none !important; padding: 0 !important; background: transparent !important;">
                    <div style="font-size: 20px; font-weight: 900; color: #0F1E2C; letter-spacing: -0.3px; text-transform: uppercase; line-height: 1.1;">
                        MACS School &amp; College
                    </div>
                    <div style="font-size: 8.5px; font-weight: 600; color: #475569; margin-top: 1px;">
                        Jalalpur, Pabna Sadar, Pabna &bull; Bangladesh
                    </div>
                    <div style="font-size: 8px; font-weight: 500; color: #64748B; margin-top: 0.5px;">
                        Hotline: 01896-220299, 01896-220300 &bull; Web: macs.edu.bd
                    </div>
                    <div style="margin-top: 4px; margin-bottom: 4px;">
                        <span style="display: inline-block; background-color: #008ED6; color: #ffffff; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; padding: 4px 18px; border-radius: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                            SMS DELIVERY REPORT
                        </span>
                    </div>
                </td>

                <!-- Right: Print Metadata -->
                <td style="width: 14%; text-align: right; vertical-align: middle; border: none !important; padding: 0 !important; background: transparent !important;">
                    <div style="font-size: 7.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.4px;">Date of Issue</div>
                    <div id="printIssueDate" style="font-size: 9.5px; font-weight: 800; font-family: monospace; color: #0F1E2C; margin-top: 1px;">{{ Carbon\Carbon::now('Asia/Dhaka')->format('d M, Y') }}</div>
                    <div id="printIssueTime" style="font-size: 7.5px; font-weight: 600; color: #94A3B8;">{{ Carbon\Carbon::now('Asia/Dhaka')->format('h:i A') }}</div>
                </td>
            </tr>
        </table>

        <!-- Filter Meta & Summary Highlights Card (Matching MACS Standards) -->
        <div style="background-color: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 4px; padding: 4px 8px; margin-top: 2px; margin-bottom: 6px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
            <table style="width: 100%; border: none !important; border-collapse: collapse; margin: 0 !important; padding: 0 !important; background: transparent !important;">
                <tr style="border: none !important; background: transparent !important;">
                    <td style="border: none !important; padding: 1px 2px !important; font-size: 9.5px; color: #334155; font-weight: 700; text-align: left; vertical-align: middle; background: transparent !important;">
                        Date: <strong id="printCriteriaDate" style="color: #008ED6;">{{ $initialDateLabel }}</strong>
                        &nbsp;&bull;&nbsp; Status: <strong id="printCriteriaStatus" style="color: #008ED6;">{{ $initialStatusLabel }}</strong>
                        &nbsp;&bull;&nbsp; Type: <strong id="printCriteriaType" style="color: #008ED6;">{{ $initialTypeLabel }}</strong>
                        <span id="printCriteriaSearchWrap" style="{{ request('search') ? 'display: inline;' : 'display: none;' }}">&nbsp;&bull;&nbsp; Search: <strong id="printCriteriaSearch" style="color: #0F1E2C;">"{{ request('search') }}"</strong></span>
                    </td>
                    <td style="border: none !important; padding: 1px 2px !important; font-size: 9.5px; color: #334155; font-weight: 700; text-align: right; white-space: nowrap; vertical-align: middle; background: transparent !important;">
                        Total SMS: <strong id="printStatTotal" style="color: #0F1E2C; font-family: monospace; font-size: 11px;">{{ number_format($logs->total()) }}</strong>
                        &nbsp;&bull;&nbsp; Sent: <strong id="printStatSent" style="color: #009A49; font-family: monospace; font-size: 11px;">{{ number_format($totalSent ?? 0) }}</strong>
                        &nbsp;&bull;&nbsp; Failed: <strong id="printStatFailed" style="color: #E11D48; font-family: monospace; font-size: 11px;">{{ number_format($totalFailed ?? 0) }}</strong>
                        <span id="printStatOtherWrap" style="display: none;">&nbsp;&bull;&nbsp; Other: <strong id="printStatOther" style="color: #D97706; font-family: monospace; font-size: 11px;">0</strong></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- SMS Details Table (8 Columns) -->
        <table class="report-table" style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
            <thead>
                <tr>
                    <th style="width: 4%; text-align: center;">#</th>
                    <th style="width: 11%; text-align: left;">Date &amp; Time</th>
                    <th style="width: 15%; text-align: left;">Recipient Name</th>
                    <th style="width: 11%; text-align: left;">Student ID</th>
                    <th style="width: 11%; text-align: left;">Mobile Number</th>
                    <th style="width: 9%; text-align: center;">SMS Type</th>
                    <th style="width: 31%; text-align: left;">Message Content</th>
                    <th style="width: 8%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody id="printTableBody">
                @forelse($logs as $index => $log)
                <tr>
                    <td style="text-align: center; font-weight: 800; font-family: monospace; font-size: 8.5px; color: #475569;">{{ $logs->firstItem() ? ($logs->firstItem() + $index) : ($index + 1) }}</td>
                    <td style="white-space: nowrap; line-height: 1.25;">
                        <div style="font-weight: 700; color: #0F1E2C; font-size: 8.5px;">{{ \Carbon\Carbon::parse($log->created_at, 'Asia/Dhaka')->format('d M, Y') }}</div>
                        <div style="font-family: monospace; font-size: 8px; color: #64748B;">{{ \Carbon\Carbon::parse($log->created_at, 'Asia/Dhaka')->format('h:i:s A') }}</div>
                    </td>
                    <td style="line-height: 1.25;">
                        <div style="font-weight: 700; color: #0F1E2C; font-size: 9px;">{{ $log->student ? $log->student->student_name : 'General Recipient' }}</div>
                    </td>
                    <td style="line-height: 1.2;">
                        @if($log->student)
                            <div style="font-family: monospace; font-weight: 700; color: #0F1E2C; font-size: 9px;">{{ $log->student->student_identity }}</div>
                            @if($log->student->schoolClass)
                                <div style="font-size: 8px; color: #64748B;">{{ $log->student->schoolClass->class_name }}</div>
                            @endif
                        @else
                            <span style="color: #94A3B8; font-size: 8.5px;">N/A</span>
                        @endif
                    </td>
                    <td style="font-family: monospace; font-weight: 700; color: #0F1E2C; font-size: 8.5px; white-space: nowrap;">
                        {{ $log->mobile_number }}
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        @if($log->event_type === 'check_in')
                            <span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #EFF6FF; color: #1D4ED8;">Check-In</span>
                        @elseif($log->event_type === 'check_out')
                            <span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #FAF5FF; color: #7E22CE;">Check-Out</span>
                        @elseif($log->event_type === 'result')
                            <span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #EEF2FF; color: #4338CA;">Result</span>
                        @else
                            <span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #F1F5F9; color: #475569;">Notice</span>
                        @endif
                    </td>
                    <td style="word-break: break-word; overflow-wrap: break-word; font-size: 8px; line-height: 1.3; color: #1E293B;">
                        {{ $log->message }}
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        @if(strtolower($log->status) === 'sent')
                            <span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">Sent</span>
                        @elseif(strtolower($log->status) === 'failed')
                            <span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #FFF1F2; color: #BE123C; border: 1px solid #FECDD3;">Failed</span>
                        @elseif(str_contains(strtolower($log->status), 'blocked'))
                            <span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;">Blocked</span>
                        @else
                            <span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #F3E8FF; color: #7E22CE; border: 1px solid #E9D5FF;">{{ $log->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 24px; color: #64748B; font-size: 11px; font-weight: 600;">
                        No SMS records found matching the selected criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Official Printable Signatures Block (Only On Final Page) -->
        <div class="print-signatures">
            <table style="width: 100%; border-collapse: collapse; border: none !important; margin-bottom: 0 !important;">
                <tr style="border: none !important; background: transparent !important;">
                    <td style="width: 50%; text-align: center; vertical-align: bottom; border: none !important; padding: 0 !important; background: transparent !important;">
                        <span style="display: inline-block; width: 160px; border-top: 1.4px dashed #64748B; padding-top: 5px; font-size: 10px; font-weight: 800; color: #0F1E2C; letter-spacing: 0.2px;">
                            Prepared By
                        </span>
                    </td>
                    <td style="width: 50%; text-align: center; vertical-align: bottom; border: none !important; padding: 0 !important; background: transparent !important;">
                        <span style="display: inline-block; width: 160px; border-top: 1.4px dashed #64748B; padding-top: 5px; font-size: 10px; font-weight: 800; color: #0F1E2C; letter-spacing: 0.2px;">
                            Authorized By
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let isSmsPrinting = false;

    async function prepareAndPrintSmsReport() {
        if (isSmsPrinting) return;
        isSmsPrinting = true;

        const btn = document.getElementById('printSmsReportBtn');
        const btnText = document.getElementById('printSmsBtnText');
        const origText = btnText ? btnText.textContent : 'Print Report';

        if (btnText) btnText.textContent = 'Preparing...';
        if (btn) btn.disabled = true;

        try {
            const queryParams = window.location.search;
            const printDataUrl = "{{ route('sms.report.ajax-print-data') }}" + queryParams;

            const response = await fetch(printDataUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`Server returned HTTP ${response.status}`);
            }

            const data = await response.json();

            // 1. Update Criteria Summary
            if (data.criteria) {
                document.getElementById('printCriteriaDate').textContent = data.criteria.date_range || 'All Dates';
                document.getElementById('printCriteriaStatus').textContent = data.criteria.status || 'All Status';
                document.getElementById('printCriteriaType').textContent = data.criteria.event_type || 'All Types';

                const searchWrap = document.getElementById('printCriteriaSearchWrap');
                const searchElem = document.getElementById('printCriteriaSearch');
                if (data.criteria.search) {
                    searchElem.textContent = `"${data.criteria.search}"`;
                    searchWrap.style.display = 'inline';
                } else {
                    searchWrap.style.display = 'none';
                }
            }

            // 2. Update Metrics Summary
            if (data.stats) {
                document.getElementById('printStatTotal').textContent = Number(data.stats.total || 0).toLocaleString();
                document.getElementById('printStatSent').textContent = Number(data.stats.sent || 0).toLocaleString();
                document.getElementById('printStatFailed').textContent = Number(data.stats.failed || 0).toLocaleString();

                const otherWrap = document.getElementById('printStatOtherWrap');
                const otherElem = document.getElementById('printStatOther');
                if (data.stats.other > 0) {
                    otherElem.textContent = Number(data.stats.other).toLocaleString();
                    otherWrap.style.display = 'inline';
                } else {
                    otherWrap.style.display = 'none';
                }
            }

            // 3. Update Generation Date & Time
            if (data.generated_at) {
                document.getElementById('printIssueDate').textContent = data.generated_at.date;
                document.getElementById('printIssueTime').textContent = data.generated_at.time;
            }

            // 4. Populate All Matching Table Rows
            const tbody = document.getElementById('printTableBody');
            tbody.innerHTML = '';

            if (!data.records || data.records.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="8" style="text-align: center; padding: 24px; color: #64748B; font-size: 11px; font-weight: 600;">No SMS records found matching the selected criteria.</td>`;
                tbody.appendChild(tr);
            } else {
                const fragment = document.createDocumentFragment();
                data.records.forEach((row, idx) => {
                    const tr = document.createElement('tr');

                    // Status styling
                    let statusBadge = '';
                    const st = (row.status || '').toLowerCase();
                    if (st === 'sent') {
                        statusBadge = `<span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #ECFDF5; color: #047857; border: 1px solid #A7F3D0;">Sent</span>`;
                    } else if (st === 'failed') {
                        statusBadge = `<span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #FFF1F2; color: #BE123C; border: 1px solid #FECDD3;">Failed</span>`;
                    } else if (st.includes('blocked')) {
                        statusBadge = `<span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;">Blocked</span>`;
                    } else {
                        statusBadge = `<span style="display: inline-block; padding: 2px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #F3E8FF; color: #7E22CE; border: 1px solid #E9D5FF;">${escapeHtml(row.status || '')}</span>`;
                    }

                    // SMS Type badge
                    let typeBadge = '';
                    const evt = (row.event_type || '').toLowerCase();
                    if (evt === 'check_in') {
                        typeBadge = `<span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #EFF6FF; color: #1D4ED8;">Check-In</span>`;
                    } else if (evt === 'check_out') {
                        typeBadge = `<span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #FAF5FF; color: #7E22CE;">Check-Out</span>`;
                    } else if (evt === 'result') {
                        typeBadge = `<span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #EEF2FF; color: #4338CA;">Result</span>`;
                    } else {
                        typeBadge = `<span style="display: inline-block; padding: 1.5px 5px; font-size: 8px; font-weight: 800; text-transform: uppercase; border-radius: 4px; background-color: #F1F5F9; color: #475569;">${escapeHtml(row.event_type_label || 'Notice')}</span>`;
                    }

                    const studentIdInfo = row.student_identity ? `
                        <div style="font-family: monospace; font-weight: 700; color: #0F1E2C; font-size: 9px;">${escapeHtml(row.student_identity)}</div>
                        ${row.class_name ? `<div style="font-size: 8px; color: #64748B;">${escapeHtml(row.class_name)}${row.section_name ? ` (${escapeHtml(row.section_name)})` : ''}</div>` : ''}
                    ` : `<span style="color: #94A3B8; font-size: 8.5px;">N/A</span>`;

                    tr.innerHTML = `
                        <td style="text-align: center; font-weight: 800; font-family: monospace; font-size: 8.5px; color: #475569;">${row.sl || (idx + 1)}</td>
                        <td style="white-space: nowrap; line-height: 1.25;">
                            <div style="font-weight: 700; color: #0F1E2C; font-size: 8.5px;">${escapeHtml(row.date)}</div>
                            <div style="font-family: monospace; font-size: 8px; color: #64748B;">${escapeHtml(row.time)}</div>
                        </td>
                        <td style="line-height: 1.25;">
                            <div style="font-weight: 700; color: #0F1E2C; font-size: 9px;">${escapeHtml(row.recipient_name || 'General Recipient')}</div>
                        </td>
                        <td style="line-height: 1.2;">
                            ${studentIdInfo}
                        </td>
                        <td style="font-family: monospace; font-weight: 700; color: #0F1E2C; font-size: 8.5px; white-space: nowrap;">
                            ${escapeHtml(row.mobile_number || '')}
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            ${typeBadge}
                        </td>
                        <td style="word-break: break-word; overflow-wrap: break-word; font-size: 8px; line-height: 1.3; color: #1E293B;">
                            ${escapeHtml(row.message || '')}
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            ${statusBadge}
                        </td>
                    `;
                    fragment.appendChild(tr);
                });
                tbody.appendChild(fragment);
            }

            // 5. Trigger Browser Print
            window.print();

        } catch (err) {
            console.error('Failed to prepare SMS print report data:', err);
            if (typeof showAlert === 'function') {
                showAlert('Could not prepare print report data. Please check your network connection and try again.', 'Print Error');
            } else {
                alert('Could not prepare print report data. Please try again.');
            }
        } finally {
            if (btnText) btnText.textContent = origText;
            if (btn) btn.disabled = false;
            isSmsPrinting = false;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endpush