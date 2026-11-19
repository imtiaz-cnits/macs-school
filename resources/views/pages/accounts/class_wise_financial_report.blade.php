@extends('tyro-dashboard::layouts.admin')

@section('title', 'Class-wise Financial Report')

@section('content')
<div class="w-full min-w-0 min-h-screen" x-data="{
    periodType: '{{ $period_type }}',
    selectedMonth: '{{ $month ?: date('F') }}',
    selectedYear: '{{ $year ?: date('Y') }}'
}">

    <!-- Page Header (Rule 4) -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                    <rect x="2" y="2" width="20" height="20" rx="3"/>
                </svg>
                Class-wise Financial Report
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Financial summary of monthly fees, exam fees and other fees by class.
            </p>
        </div>

        <!-- Action Buttons: Print, Excel, PDF -->
        <div class="flex flex-wrap gap-2.5 w-full md:w-auto justify-start md:justify-end">
            <!-- Print Report -->
            <a href="{{ route('accounts.reports.class_wise_print', request()->query()) }}" target="_blank" class="h-11 px-4 bg-gray-900 hover:bg-black text-white text-xs font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Report
            </a>

            <!-- Export Excel -->
            <a href="{{ route('accounts.reports.class_wise_excel', request()->query()) }}" class="h-11 px-4 border-2 border-emerald-200 dark:border-emerald-800/80 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
                Export Excel
            </a>

            <!-- Export PDF -->
            <a href="{{ route('accounts.reports.class_wise_pdf', request()->query()) }}" class="h-11 px-4 border-2 border-rose-200 dark:border-rose-800/80 rounded-xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-700 dark:text-rose-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export PDF
            </a>
        </div>
    </div>

    <!-- Summary Cards (Section 8) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8 no-print">
        <!-- Total Due Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Total Due</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-themeBlue/10 text-themeBlue flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                Tk {{ number_format($total_due, 2) }}
            </div>
            <p class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1">
                Total payable dues across all classes
            </p>
        </div>

        <!-- Total Collected Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Total Collected</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-themeGreen/10 text-themeGreen flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                Tk {{ number_format($total_collected, 2) }}
            </div>
            <p class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1">
                Successfully realized receipts in period
            </p>
        </div>

        <!-- Total Outstanding Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-400">Total Outstanding</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400">
                Tk {{ number_format($total_outstanding, 2) }}
            </div>
            <p class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1">
                Unpaid balance (Due - Collected)
            </p>
        </div>
    </div>

    <!-- Filters Card (Section 7) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm mb-8 no-print">
        <form action="{{ route('accounts.reports.class_wise') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">

                <!-- 1. Academic Session: Rule 7 Custom Select -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Academic Session</label>
                    <div x-data="{
                        open: false,
                        value: '{{ $session_id }}',
                        label: '{{ addslashes($sessions->firstWhere('id', $session_id)?->session_name ?? ($sessions->first()?->session_name ?? 'Select Session')) }}',
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                        }
                    }" class="relative" @click.away="open = false">
                        <input type="hidden" name="session_year_id" :value="value">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate font-bold" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            @foreach($sessions as $sess)
                                <button type="button" @click="select('{{ $sess->id }}', '{{ addslashes($sess->session_name) }}')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == '{{ $sess->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                                    <span>{{ $sess->session_name }}</span>
                                    <svg x-show="value == '{{ $sess->id }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 2. Reporting Period Mode: Rule 7 Custom Select -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Reporting Period</label>
                    <div x-data="{
                        open: false,
                        get label() {
                            if (periodType === 'monthly') return 'Monthly';
                            if (periodType === 'custom') return 'Custom Date Range';
                            return 'Full Session';
                        },
                        select(val) {
                            periodType = val;
                            this.open = false;
                        }
                    }" class="relative" @click.away="open = false">
                        <input type="hidden" name="period_type" :value="periodType">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate font-bold" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="select('all')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="periodType === 'all' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                                <span>Full Session</span>
                                <svg x-show="periodType === 'all'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <button type="button" @click="select('monthly')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="periodType === 'monthly' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                                <span>Monthly</span>
                                <svg x-show="periodType === 'monthly'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <button type="button" @click="select('custom')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="periodType === 'custom' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                                <span>Custom Date Range</span>
                                <svg x-show="periodType === 'custom'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3. Dynamic Period Inputs: Monthly Mode (Rule 7 Custom Select) -->
                <template x-if="periodType === 'monthly'">
                    <div class="sm:col-span-2 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Month</label>
                            <div x-data="{
                                open: false,
                                select(m) {
                                    selectedMonth = m;
                                    this.open = false;
                                }
                            }" class="relative" @click.away="open = false">
                                <input type="hidden" name="month" :value="selectedMonth">
                                <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate font-bold" x-text="selectedMonth"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $m)
                                        <button type="button" @click="select('{{ $m }}')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="selectedMonth === '{{ $m }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                                            <span>{{ $m }}</span>
                                            <svg x-show="selectedMonth === '{{ $m }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Year</label>
                            <input type="number" name="year" x-model="selectedYear" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                        </div>
                    </div>
                </template>

                <!-- 4. Dynamic Period Inputs: Custom Date Range Mode (Rule 10 Alpine DatePicker) -->
                <template x-if="periodType === 'custom'">
                    <div class="sm:col-span-2 grid grid-cols-2 gap-3">
                        <!-- Start Date -->
                        <div x-data="datePicker('{{ $start_date ?: date('Y-m-01') }}')" @click.away="show = false; mOpen = false" class="relative">
                            <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Start Date</label>
                            <input type="hidden" name="start_date" :value="value">
                            <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </button>
                            <!-- Calendar Panel -->
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
                                        <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                                        <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                </div>
                                <div class="grid grid-cols-7 gap-1">
                                    <template x-for="(d, i) in days" :key="i">
                                        <button type="button" @click="selectDay(d.day)" :disabled="!d.day" :class="{'bg-themeBlue text-white font-black rounded-lg shadow-sm': isSelected(d.day), 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg': d.day && !isSelected(d.day), 'opacity-0 cursor-default': !d.day}" class="h-7 text-xs flex items-center justify-center transition-all font-semibold" x-text="d.day || ''"></button>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                                    <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                                    <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
                                </div>
                            </div>
                        </div>

                        <!-- End Date -->
                        <div x-data="datePicker('{{ $end_date ?: date('Y-m-d') }}')" @click.away="show = false; mOpen = false" class="relative">
                            <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">End Date</label>
                            <input type="hidden" name="end_date" :value="value">
                            <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </button>
                            <!-- Calendar Panel -->
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
                                        <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                                        <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                </div>
                                <div class="grid grid-cols-7 gap-1">
                                    <template x-for="(d, i) in days" :key="i">
                                        <button type="button" @click="selectDay(d.day)" :disabled="!d.day" :class="{'bg-themeBlue text-white font-black rounded-lg shadow-sm': isSelected(d.day), 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg': d.day && !isSelected(d.day), 'opacity-0 cursor-default': !d.day}" class="h-7 text-xs flex items-center justify-center transition-all font-semibold" x-text="d.day || ''"></button>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                                    <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                                    <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Full Session Placeholder in 3rd/4th columns when All Periods selected -->
                <template x-if="periodType === 'all'">
                    <div class="sm:col-span-2 flex items-center gap-2">
                        <div class="w-full h-11 px-4 bg-gray-50/40 dark:bg-themeDark/60 border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-xl flex items-center text-xs font-semibold text-gray-500 dark:text-gray-400">
                            Analyzing all transactions for Academic Session {{ $session_name }}
                        </div>
                    </div>
                </template>

                <!-- Filter Actions -->
                <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('accounts.reports.class_wise') }}" class="h-11 px-5 border-2 border-gray-150 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-wider flex items-center justify-center transition-all active:scale-95">
                        Reset Filters
                    </a>
                    <button type="submit" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen hover:from-blue-600 hover:to-emerald-600 text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Main Report Table Card: Compact 13 Columns (AGENTS.md Rule 9, Zero Desktop Scrollbar) -->
    <div class="w-full min-w-0 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Integrated Scope & Metadata Header Bar -->
        <div class="px-5 py-3.5 border-b border-gray-100 dark:border-white/[0.06] flex flex-wrap items-center justify-between gap-2.5 no-print">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-sky-50 dark:bg-themeBlue/15 border border-sky-100 dark:border-themeBlue/25 text-themeBlue text-xs font-black">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    Session: {{ $session_name }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 dark:bg-themeGreen/15 border border-emerald-100 dark:border-themeGreen/25 text-themeGreen text-xs font-black">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Period: {{ $period_label }}
                </span>
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 whitespace-nowrap">
                Generated: {{ $generated_at }}
            </div>
        </div>

        <div class="px-4 pb-3 pt-1">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-1 !mb-0 !rounded-none overflow-x-auto">
                <table class="w-full text-left border-collapse table tabular-nums [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2 [&>thead>tr>th]:!px-1.5 [&>thead>tr>th:first-child]:!pl-1 [&>thead>tr>th:last-child]:!pr-1 [&>thead>tr>th]:!leading-tight [&>thead>tr>th.grp-due]:!text-themeBlue [&>thead>tr>th.grp-coll]:!text-themeGreen [&>thead>tr>th.grp-out]:!text-rose-500 [&>thead>tr>th.sub-h]:!text-[9px] [&>thead>tr>th.sub-h]:!tracking-[0.04em] [&>thead>tr>th.sub-h]:!text-gray-400 dark:[&>thead>tr>th.sub-h]:!text-gray-500 [&>thead>tr>th.sub-due]:!text-themeBlue [&>thead>tr>th.sub-coll]:!text-themeGreen [&>thead>tr>th.sub-out]:!text-rose-500 [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-2 [&>tbody>tr>td]:!px-1.5 [&>tbody>tr>td:first-child]:!pl-1 [&>tbody>tr>td:last-child]:!pr-1 [&>tbody>tr>td]:!text-[11.5px] [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/70 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35 [&>tbody>tr>td.cell-zero]:!text-gray-300 dark:[&>tbody>tr>td.cell-zero]:!text-gray-600 [&>tbody>tr>td.cell-due]:!text-themeBlue [&>tbody>tr>td.cell-due]:!bg-sky-50/35 dark:[&>tbody>tr>td.cell-due]:!bg-themeBlue/10 [&>tbody>tr>td.cell-coll]:!text-themeGreen [&>tbody>tr>td.cell-coll]:!bg-emerald-50/35 dark:[&>tbody>tr>td.cell-coll]:!bg-themeGreen/10 [&>tbody>tr>td.cell-out]:!text-rose-600 dark:[&>tbody>tr>td.cell-out]:!text-rose-400 [&>tbody>tr>td.cell-out]:!bg-rose-50/35 dark:[&>tbody>tr>td.cell-out]:!bg-rose-950/20 [&>tfoot>tr>td]:!py-2.5 [&>tfoot>tr>td]:!px-1.5 [&>tfoot>tr>td:first-child]:!pl-1 [&>tfoot>tr>td:last-child]:!pr-1 [&>tfoot>tr>td]:!text-[11.5px] [&>tfoot>tr>td]:!bg-gray-50/80 dark:[&>tfoot>tr>td]:!bg-themeDark/70 [&>tfoot>tr>td]:!border-t-2 [&>tfoot>tr>td]:!border-b-0 [&>tfoot>tr>td]:!border-gray-200 dark:[&>tfoot>tr>td]:!border-white/[0.12] [&>tfoot>tr>td.cell-due]:!text-themeBlue [&>tfoot>tr>td.cell-due]:!bg-sky-50/70 dark:[&>tfoot>tr>td.cell-due]:!bg-themeBlue/15 [&>tfoot>tr>td.cell-coll]:!text-themeGreen [&>tfoot>tr>td.cell-coll]:!bg-emerald-50/70 dark:[&>tfoot>tr>td.cell-coll]:!bg-themeGreen/15 [&>tfoot>tr>td.cell-out]:!text-rose-600 dark:[&>tfoot>tr>td.cell-out]:!text-rose-400 [&>tfoot>tr>td.cell-out]:!bg-rose-50/70 dark:[&>tfoot>tr>td.cell-out]:!bg-rose-950/30">
                    <thead>
                        <!-- Header Row 1: High Level Financial Groups -->
                        <tr class="!bg-transparent">
                            <th rowspan="2" class="!bg-transparent border-b border-r border-gray-200 dark:border-white/[0.08] text-left !text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase !tracking-[0.14em] whitespace-nowrap">
                                Class
                            </th>
                            <th colspan="4" class="grp-due !bg-transparent text-center border-b border-r border-gray-200 dark:border-white/[0.08] !text-[10px] font-black uppercase !tracking-[0.16em]">
                                Due
                            </th>
                            <th colspan="4" class="grp-coll !bg-transparent text-center border-b border-r border-gray-200 dark:border-white/[0.08] !text-[10px] font-black uppercase !tracking-[0.16em]">
                                Collected
                            </th>
                            <th colspan="4" class="grp-out !bg-transparent text-center border-b border-gray-200 dark:border-white/[0.08] !text-[10px] font-black uppercase !tracking-[0.16em]">
                                Outstanding
                            </th>
                        </tr>

                        <!-- Header Row 2: Four Sub-columns per Group -->
                        <tr class="!bg-transparent">
                            <!-- Under Due -->
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Monthly Fee</th>
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Exam Fees</th>
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Other Fees</th>
                            <th class="sub-h sub-due !bg-transparent border-b border-r border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Total</th>

                            <!-- Under Collected -->
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Monthly Fee</th>
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Exam Fees</th>
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Other Fees</th>
                            <th class="sub-h sub-coll !bg-transparent border-b border-r border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Total</th>

                            <!-- Under Outstanding -->
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Monthly Fee</th>
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Exam Fees</th>
                            <th class="sub-h !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Other Fees</th>
                            <th class="sub-h sub-out !bg-transparent border-b border-gray-200 dark:border-white/[0.08] text-right whitespace-nowrap">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                        <tr class="transition-colors">
                            <!-- Class Name in Academic Order -->
                            <td class="font-bold text-gray-900 dark:text-gray-100 border-r border-gray-100 dark:border-white/[0.06] whitespace-nowrap">
                                {{ $r['class_name'] }}
                            </td>

                            <!-- Due Sub-columns -->
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['due']['monthly_fee'] == 0 ? 'cell-zero' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ number_format($r['due']['monthly_fee'], 2) }}
                            </td>
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['due']['exam_fees'] == 0 ? 'cell-zero' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ number_format($r['due']['exam_fees'], 2) }}
                            </td>
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['due']['other_fees'] == 0 ? 'cell-zero' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ number_format($r['due']['other_fees'], 2) }}
                            </td>
                            <td class="cell-due text-right font-mono font-black border-r border-gray-100 dark:border-white/[0.06] whitespace-nowrap">
                                {{ number_format($r['due']['total'], 2) }}
                            </td>

                            <!-- Collected Sub-columns -->
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['collected']['monthly_fee'] == 0 ? 'cell-zero' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ number_format($r['collected']['monthly_fee'], 2) }}
                            </td>
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['collected']['exam_fees'] == 0 ? 'cell-zero' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ number_format($r['collected']['exam_fees'], 2) }}
                            </td>
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['collected']['other_fees'] == 0 ? 'cell-zero' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ number_format($r['collected']['other_fees'], 2) }}
                            </td>
                            <td class="cell-coll text-right font-mono font-black border-r border-gray-100 dark:border-white/[0.06] whitespace-nowrap">
                                {{ number_format($r['collected']['total'], 2) }}
                            </td>

                            <!-- Outstanding Sub-columns -->
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['outstanding']['monthly_fee'] == 0 ? 'cell-zero' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ number_format($r['outstanding']['monthly_fee'], 2) }}
                            </td>
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['outstanding']['exam_fees'] == 0 ? 'cell-zero' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ number_format($r['outstanding']['exam_fees'], 2) }}
                            </td>
                            <td class="text-right font-mono font-semibold whitespace-nowrap {{ (float) $r['outstanding']['other_fees'] == 0 ? 'cell-zero' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ number_format($r['outstanding']['other_fees'], 2) }}
                            </td>
                            <td class="cell-out text-right font-mono font-black whitespace-nowrap">
                                {{ number_format($r['outstanding']['total'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center py-8 text-gray-400 text-xs font-semibold">
                                No class-wise financial records found for the selected filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <!-- Grand Total Row: Bold & Authoritative -->
                        <tr>
                            <td class="font-black text-gray-900 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-white/[0.08] whitespace-nowrap">
                                Grand Total
                            </td>

                            <!-- Grand Total Due -->
                            <td class="text-right font-mono font-black text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($grand_total['due']['monthly_fee'], 2) }}
                            </td>
                            <td class="text-right font-mono font-black text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($grand_total['due']['exam_fees'], 2) }}
                            </td>
                            <td class="text-right font-mono font-black text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ number_format($grand_total['due']['other_fees'], 2) }}
                            </td>
                            <td class="cell-due text-right font-mono font-black border-r border-gray-200 dark:border-white/[0.08] whitespace-nowrap">
                                {{ number_format($grand_total['due']['total'], 2) }}
                            </td>

                            <!-- Grand Total Collected -->
                            <td class="text-right font-mono font-black text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($grand_total['collected']['monthly_fee'], 2) }}
                            </td>
                            <td class="text-right font-mono font-black text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($grand_total['collected']['exam_fees'], 2) }}
                            </td>
                            <td class="text-right font-mono font-black text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ number_format($grand_total['collected']['other_fees'], 2) }}
                            </td>
                            <td class="cell-coll text-right font-mono font-black border-r border-gray-200 dark:border-white/[0.08] whitespace-nowrap">
                                {{ number_format($grand_total['collected']['total'], 2) }}
                            </td>

                            <!-- Grand Total Outstanding -->
                            <td class="text-right font-mono font-black text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($grand_total['outstanding']['monthly_fee'], 2) }}
                            </td>
                            <td class="text-right font-mono font-black text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($grand_total['outstanding']['exam_fees'], 2) }}
                            </td>
                            <td class="text-right font-mono font-black text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ number_format($grand_total['outstanding']['other_fees'], 2) }}
                            </td>
                            <td class="cell-out text-right font-mono font-black whitespace-nowrap">
                                {{ number_format($grand_total['outstanding']['total'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Rule 10 Custom Alpine DatePicker
    window.datePicker = function(initialValue = '') {
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
                const parts = String(val).split('-');
                if (parts.length === 3) {
                    const yr = parseInt(parts[0], 10);
                    const mo = parseInt(parts[1], 10) - 1;
                    if (!isNaN(yr) && yr >= 1900) this.currentYear = yr;
                    if (!isNaN(mo) && mo >= 0 && mo <= 11) this.currentMonth = mo;
                }
            },

            generateCalendar() {
                const yr = parseInt(this.currentYear, 10);
                if (isNaN(yr) || yr < 1000) return;

                const firstDayIndex = new Date(yr, this.currentMonth, 1).getDay();
                const totalDays = new Date(yr, this.currentMonth + 1, 0).getDate();

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
                this.mOpen = false;
                this.$dispatch('date-selected', this.value);
            },

            clearDate() {
                this.value = '';
                this.show = false;
                this.mOpen = false;
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
                this.mOpen = false;
                this.$dispatch('date-selected', this.value);
            },

            isSelected(day) {
                if (!day || !this.value) return false;
                const parts = String(this.value).split('-');
                if (parts.length === 3) {
                    return parseInt(parts[0], 10) === parseInt(this.currentYear, 10) &&
                           (parseInt(parts[1], 10) - 1) === parseInt(this.currentMonth, 10) &&
                           parseInt(parts[2], 10) === parseInt(day, 10);
                }
                return false;
            },

            formatDisplay(val) {
                if (!val) return 'Select Date';
                const parts = String(val).split('-');
                if (parts.length === 3) {
                    const yr = parseInt(parts[0], 10);
                    const mo = parseInt(parts[1], 10) - 1;
                    const da = parseInt(parts[2], 10);
                    if (!isNaN(yr) && !isNaN(mo) && !isNaN(da)) {
                        const d = new Date(yr, mo, da);
                        return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
                    }
                }
                return val;
            }
        };
    };
</script>
@endpush

