@extends('tyro-dashboard::layouts.admin')

@section('title', 'SMS Delivery Report')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .table th, .table td { padding: 0.75rem 1rem !important; }
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
</div>
@endsection