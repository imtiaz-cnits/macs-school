@extends('tyro-dashboard::layouts.admin')

@section('title', 'Vendor Ledger: ' . $vendor->name)

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">

    <!-- Page Header (Rule 4) -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('accounts.payables.index') }}" class="text-xs font-bold text-gray-400 hover:text-themeBlue flex items-center gap-1">
                    &larr; Back to Payables Dashboard
                </a>
            </div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Vendor Ledger: {{ $vendor->name }}
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-450 mt-1">
                {{ implode(' • ', array_filter([$vendor->contact_person, $vendor->phone, $vendor->address])) ?: 'General Vendor' }}
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('accounts.payables.ledger_print', array_merge(['id' => $vendor->id], request()->query())) }}" target="_blank" class="h-11 px-5 bg-gray-900 hover:bg-black text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Statement
            </a>
            <a href="{{ route('accounts.vendor-payments.create', ['vendor_id' => $vendor->id]) }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Record Payment
            </a>
        </div>
    </div>

    <!-- Filter Card (Rule 4) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm mb-8 no-print">
        <form action="{{ route('accounts.payables.ledger', $vendor->id) }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Period From: Rule 10 Custom Alpine DatePicker -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Period From</label>
                    <div class="relative" x-data="datePicker('{{ $startDate }}')" @click.away="show = false; mOpen = false">
                        <input type="hidden" name="start_date" :value="value">
                        <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>

                        <div x-show="show" x-cloak class="absolute left-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                            <div class="flex items-center justify-between gap-1 mb-2 pb-2 border-b border-gray-100 dark:border-white/[0.06]">
                                <div class="relative">
                                    <button type="button" @click="mOpen = !mOpen" class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1 hover:text-themeBlue">
                                        <span x-text="monthNames[currentMonth]"></span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="mOpen" x-cloak class="absolute left-0 z-50 mt-1 w-32 bg-white dark:bg-themeDark border border-gray-150 dark:border-white/[0.08] rounded-xl shadow-lg py-1 max-h-48 overflow-y-auto">
                                        <template x-for="(m, mIdx) in monthNames" :key="mIdx">
                                            <button type="button" @click="currentMonth = mIdx; mOpen = false; generateCalendar()" class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-themeNavy font-bold" :class="currentMonth === mIdx ? 'text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" x-text="m"></button>
                                        </template>
                                    </div>
                                </div>
                                <input type="number" x-model.number="currentYear" @input="generateCalendar()" class="w-16 h-7 text-xs font-black text-center bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-themeBlue [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors"><svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors"><svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 uppercase mb-1"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(d, i) in days" :key="i">
                                    <button type="button" @click="selectDay(d.day)" class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all" :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'" :disabled="!d.isCurrentMonth">
                                        <span x-text="d.day"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                                <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500">Clear</button>
                                <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline">Today</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Period To: Rule 10 Custom Alpine DatePicker -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Period To</label>
                    <div class="relative" x-data="datePicker('{{ $endDate }}')" @click.away="show = false; mOpen = false">
                        <input type="hidden" name="end_date" :value="value">
                        <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>

                        <div x-show="show" x-cloak class="absolute left-0 sm:left-auto sm:right-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                            <div class="flex items-center justify-between gap-1 mb-2 pb-2 border-b border-gray-100 dark:border-white/[0.06]">
                                <div class="relative">
                                    <button type="button" @click="mOpen = !mOpen" class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1 hover:text-themeBlue">
                                        <span x-text="monthNames[currentMonth]"></span>
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="mOpen" x-cloak class="absolute left-0 z-50 mt-1 w-32 bg-white dark:bg-themeDark border border-gray-150 dark:border-white/[0.08] rounded-xl shadow-lg py-1 max-h-48 overflow-y-auto">
                                        <template x-for="(m, mIdx) in monthNames" :key="mIdx">
                                            <button type="button" @click="currentMonth = mIdx; mOpen = false; generateCalendar()" class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-themeNavy font-bold" :class="currentMonth === mIdx ? 'text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" x-text="m"></button>
                                        </template>
                                    </div>
                                </div>
                                <input type="number" x-model.number="currentYear" @input="generateCalendar()" class="w-16 h-7 text-xs font-black text-center bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-themeBlue [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors"><svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                                    <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors"><svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 uppercase mb-1"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(d, i) in days" :key="i">
                                    <button type="button" @click="selectDay(d.day)" class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all" :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'" :disabled="!d.isCurrentMonth">
                                        <span x-text="d.day"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                                <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500">Clear</button>
                                <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline">Today</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch Filter -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Branch</label>
                    <div x-data="{
                        open: false,
                        value: '{{ $branchId }}',
                        label: '{{ $branches->firstWhere('id', $branchId)?->branch_name ?? 'All Branches' }}',
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                        }
                    }" class="relative" @click.away="open = false">
                        <input type="hidden" name="branch_id" :value="value">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="select('', 'All Branches')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45" :class="value === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Branches</span>
                                <svg x-show="value === ''" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @foreach($branches as $b)
                                <button type="button" @click="select('{{ $b->id }}', '{{ addslashes($b->branch_name) }}')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45" :class="value == '{{ $b->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $b->branch_name }}</span>
                                    <svg x-show="value == '{{ $b->id }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Submit Filter Button -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                        Filter Ledger
                    </button>
                    <a href="{{ route('accounts.payables.ledger', $vendor->id) }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 text-gray-500 dark:text-gray-300 text-xs font-black uppercase flex items-center justify-center">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 5 Period Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-1">Prior Opening Due</span>
            <div class="text-xl font-black font-mono text-gray-700 dark:text-gray-300">৳ {{ number_format($prior_due, 2) }}</div>
            <span class="text-[10px] text-gray-400 mt-1 block">Before {{ date('d M Y', strtotime($startDate)) }}</span>
        </div>
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-blue-600 block mb-1">Total Period Billed</span>
            <div class="text-xl font-black font-mono text-blue-600">৳ {{ number_format($total_period_billed, 2) }}</div>
            <span class="text-[10px] text-gray-400 mt-1 block">New bills registered</span>
        </div>
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 block mb-1">Total Period Paid</span>
            <div class="text-xl font-black font-mono text-emerald-600">৳ {{ number_format($total_period_paid, 2) }}</div>
            <span class="text-[10px] text-gray-400 mt-1 block">Settlements disbursed</span>
        </div>
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-red-600 block mb-1">Closing Payable</span>
            <div class="text-xl font-black font-mono text-red-600">৳ {{ number_format($closing_due, 2) }}</div>
            <span class="text-[10px] text-gray-400 mt-1 block">Net outstanding balance</span>
        </div>
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-rose-600 block mb-1">Overdue Amount</span>
            <div class="text-xl font-black font-mono text-rose-600">৳ {{ number_format($overdue_amount, 2) }}</div>
            <span class="text-[10px] text-gray-400 mt-1 block">Past due date</span>
        </div>
    </div>

    <!-- Chronological Ledger Table (Rule 9) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-8">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-white/[0.06]">
            <div>
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Authoritative Chronological Ledger & Running Balance
                </h3>
                <p class="text-[11px] text-gray-400 font-medium mt-0.5">Every transaction derives running balance: Running = Prior + Bills - Payments</p>
            </div>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Transaction Type</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Reference</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Description / Particulars</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Debit (Payment ৳)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Credit (Bill ৳)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Prior Balance Row -->
                    <tr class="bg-gray-50/40 dark:bg-themeDark/30 font-bold border-b border-gray-200 dark:border-white/[0.06]">
                        <td class="py-3 px-4 font-mono text-xs text-gray-500">{{ date('d M Y', strtotime($startDate)) }}</td>
                        <td class="py-3 px-4 text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300">Balance B/F</td>
                        <td class="py-3 px-4 text-xs font-mono text-gray-400">—</td>
                        <td class="py-3 px-4 text-xs text-gray-500 italic">Balance brought forward from prior periods</td>
                        <td class="py-3 px-4 font-mono text-xs text-right text-gray-400">—</td>
                        <td class="py-3 px-4 font-mono text-xs text-right text-gray-400">—</td>
                        <td class="py-3 px-4 font-mono font-black text-sm text-right text-gray-900 dark:text-white">৳ {{ number_format($prior_due, 2) }}</td>
                    </tr>

                    @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-3 px-4 font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ date('d M Y', strtotime($row['date'])) }}</td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($row['type'] === 'Payment Settlement')
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400">Payment</span>
                            @elseif($row['type'] === 'Opening Balance')
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400">Opening Balance</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400">Expense Bill</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-xs text-themeBlue whitespace-nowrap">{{ $row['reference'] }}</td>
                        <td class="py-3 px-4 text-xs text-gray-800 dark:text-gray-200">
                            <div>{{ $row['description'] }}</div>
                            @if(!empty($row['due_date']))
                                <div class="text-[10px] text-gray-400 mt-0.5">Due: {{ date('d M Y', strtotime($row['due_date'])) }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-xs text-right text-emerald-600 whitespace-nowrap">
                            {{ $row['debit'] > 0 ? ('৳ ' . number_format($row['debit'], 2)) : '—' }}
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-xs text-right text-blue-600 whitespace-nowrap">
                            {{ $row['credit'] > 0 ? ('৳ ' . number_format($row['credit'], 2)) : '—' }}
                        </td>
                        <td class="py-3 px-4 font-mono font-black text-sm text-right text-gray-900 dark:text-white whitespace-nowrap">
                            ৳ {{ number_format($row['running_balance'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-xs text-gray-400">
                            No ledger transactions recorded within this period.
                        </td>
                    </tr>
                    @endforelse

                    <!-- Closing Balance Summary Row -->
                    <tr class="bg-gray-100/60 dark:bg-themeDark/60 font-black border-t-2 border-gray-300 dark:border-white/[0.1]">
                        <td colspan="4" class="py-4 px-4 text-xs uppercase tracking-wider text-right">Period Totals & Closing Payable Balance:</td>
                        <td class="py-4 px-4 font-mono text-sm text-right text-emerald-600 whitespace-nowrap">৳ {{ number_format($total_period_paid, 2) }}</td>
                        <td class="py-4 px-4 font-mono text-sm text-right text-blue-600 whitespace-nowrap">৳ {{ number_format($total_period_billed, 2) }}</td>
                        <td class="py-4 px-4 font-mono text-base text-right text-red-600 whitespace-nowrap">৳ {{ number_format($closing_due, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Rule 10 Custom Alpine DatePicker
    function datePicker(initialValue = '') {
        return {
            show: false,
            mOpen: false,
            value: initialValue || '',
            currentYear: new Date().getFullYear(),
            currentMonth: new Date().getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

            init() {
                if (this.value) {
                    this.parseInitial(this.value);
                }
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        this.parseInitial(val);
                        this.generateCalendar();
                    }
                });
            },

            parseInitial(val) {
                if (!val) return;
                const parts = val.split('-');
                if (parts.length === 3) {
                    this.currentYear = parseInt(parts[0]);
                    this.currentMonth = parseInt(parts[1]) - 1;
                }
            },

            generateCalendar() {
                const firstDayIndex = new Date(this.currentYear, this.currentMonth, 1).getDay();
                const totalDays = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

                const days = [];
                for (let i = 0; i < firstDayIndex; i++) {
                    days.push({ day: '', isCurrentMonth: false });
                }
                for (let i = 1; i <= totalDays; i++) {
                    days.push({ day: i, isCurrentMonth: true });
                }
                this.days = days;
            },

            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
                this.generateCalendar();
            },

            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
                this.generateCalendar();
            },

            selectDay(day) {
                if (!day) return;
                const formattedMonth = String(this.currentMonth + 1).padStart(2, '0');
                const formattedDay = String(day).padStart(2, '0');
                this.value = `${this.currentYear}-${formattedMonth}-${formattedDay}`;
                this.show = false;
                this.$dispatch('date-selected', this.value);
            },

            clearDate() {
                this.value = '';
                this.show = false;
                this.$dispatch('date-selected', '');
            },

            selectToday() {
                const now = new Date();
                this.currentYear = now.getFullYear();
                this.currentMonth = now.getMonth();
                const formattedMonth = String(now.getMonth() + 1).padStart(2, '0');
                const formattedDay = String(now.getDate()).padStart(2, '0');
                this.value = `${this.currentYear}-${formattedMonth}-${formattedDay}`;
                this.show = false;
                this.$dispatch('date-selected', this.value);
            },

            formatDisplay(val) {
                if (!val) return 'Select Date';
                const parts = val.split('-');
                if (parts.length === 3) {
                    const d = new Date(parts[0], parts[1] - 1, parts[2]);
                    return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
                }
                return val;
            }
        };
    }
</script>
@endpush
