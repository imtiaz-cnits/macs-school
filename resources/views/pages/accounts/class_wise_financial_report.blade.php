@extends('tyro-dashboard::layouts.admin')

@section('title', 'Class-wise Financial Report')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 0.85rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="{
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
                Financial summary of monthly fees, other fees and miscellaneous charges by class.
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

                <!-- 1. Academic Session -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Academic Session</label>
                    <select name="session_year_id" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ (string)$session_id === (string)$sess->id ? 'selected' : '' }}>
                                {{ $sess->session_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Reporting Period Mode -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Reporting Period</label>
                    <select name="period_type" x-model="periodType" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                        <option value="all">Full Session</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom Date Range</option>
                    </select>
                </div>

                <!-- 3. Dynamic Period Inputs: Monthly Mode -->
                <template x-if="periodType === 'monthly'">
                    <div class="sm:col-span-2 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Month</label>
                            <select name="month" x-model="selectedMonth" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $m)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endforeach
                            </select>
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
                                    <input type="number" x-model.number="currentYear" @input="generateCalendar()" class="w-16 h-7 text-xs font-black text-center bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
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
                                    <input type="number" x-model.number="currentYear" @input="generateCalendar()" class="w-16 h-7 text-xs font-black text-center bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
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

    <!-- Active Filter Scope Bar -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 no-print">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-50 dark:bg-themeBlue/10 text-themeBlue font-bold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                Session: {{ $session_name }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 dark:bg-themeGreen/10 text-themeGreen font-bold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Period: {{ $period_label }}
            </span>
        </div>
        <div class="text-[11px] text-gray-400">
            Generated: {{ $generated_at }}
        </div>
    </div>

    <!-- Main Report Table: Exact 13 Columns Structure (Section 2) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm overflow-hidden">
        <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <!-- Header Row 1: High Level Financial Groups -->
                    <tr class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08]">
                        <th rowspan="2" class="!bg-transparent border-r border-gray-100 dark:border-white/[0.06] text-left text-[11px] font-black text-gray-700 dark:text-gray-200 uppercase tracking-widest min-w-[120px]">
                            Class
                        </th>
                        <th colspan="4" class="!bg-transparent text-center border-r border-gray-100 dark:border-white/[0.06] text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] bg-blue-50/20 dark:bg-blue-950/10 py-2">
                            Due
                        </th>
                        <th colspan="4" class="!bg-transparent text-center border-r border-gray-100 dark:border-white/[0.06] text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] bg-emerald-50/20 dark:bg-emerald-950/10 py-2">
                            Collected
                        </th>
                        <th colspan="4" class="!bg-transparent text-center text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-[0.2em] bg-rose-50/20 dark:bg-rose-950/10 py-2">
                            Outstanding
                        </th>
                    </tr>

                    <!-- Header Row 2: Four Sub-columns per Group -->
                    <tr class="!bg-transparent border-b-2 border-gray-200 dark:border-white/[0.12] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        <!-- Under Due -->
                        <th class="text-right whitespace-nowrap">Monthly Fee</th>
                        <th class="text-right whitespace-nowrap">Other Fees</th>
                        <th class="text-right whitespace-nowrap">Other</th>
                        <th class="text-right whitespace-nowrap border-r border-gray-100 dark:border-white/[0.06] text-blue-700 dark:text-blue-300">Total</th>

                        <!-- Under Collected -->
                        <th class="text-right whitespace-nowrap">Monthly Fee</th>
                        <th class="text-right whitespace-nowrap">Other Fees</th>
                        <th class="text-right whitespace-nowrap">Other</th>
                        <th class="text-right whitespace-nowrap border-r border-gray-100 dark:border-white/[0.06] text-emerald-700 dark:text-emerald-300">Total</th>

                        <!-- Under Outstanding -->
                        <th class="text-right whitespace-nowrap">Monthly Fee</th>
                        <th class="text-right whitespace-nowrap">Other Fees</th>
                        <th class="text-right whitespace-nowrap">Other</th>
                        <th class="text-right whitespace-nowrap text-rose-700 dark:text-rose-300">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @forelse($rows as $r)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-themeDark/30 transition-colors">
                        <!-- Class Name in Academic Order (Rule 9) -->
                        <td class="font-bold text-gray-900 dark:text-gray-100 border-r border-gray-100 dark:border-white/[0.06] whitespace-nowrap text-xs">
                            {{ $r['class_name'] }}
                        </td>

                        <!-- Due Sub-columns -->
                        <td class="text-right font-mono font-semibold text-xs text-gray-700 dark:text-gray-300">
                            {{ number_format($r['due']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-semibold text-xs text-gray-700 dark:text-gray-300">
                            {{ number_format($r['due']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-semibold text-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($r['due']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-bold text-xs text-blue-700 dark:text-blue-300 bg-blue-50/30 dark:bg-blue-950/20 border-r border-gray-100 dark:border-white/[0.06]">
                            {{ number_format($r['due']['total'], 2) }}
                        </td>

                        <!-- Collected Sub-columns -->
                        <td class="text-right font-mono font-semibold text-xs text-gray-700 dark:text-gray-300">
                            {{ number_format($r['collected']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-semibold text-xs text-gray-700 dark:text-gray-300">
                            {{ number_format($r['collected']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-semibold text-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($r['collected']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-bold text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50/30 dark:bg-emerald-950/20 border-r border-gray-100 dark:border-white/[0.06]">
                            {{ number_format($r['collected']['total'], 2) }}
                        </td>

                        <!-- Outstanding Sub-columns -->
                        <td class="text-right font-mono font-semibold text-xs text-gray-700 dark:text-gray-300">
                            {{ number_format($r['outstanding']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-semibold text-xs text-gray-700 dark:text-gray-300">
                            {{ number_format($r['outstanding']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-semibold text-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($r['outstanding']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-bold text-xs text-rose-700 dark:text-rose-300 bg-rose-50/30 dark:bg-rose-950/20">
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
                    <tr class="border-t-2 border-gray-300 dark:border-white/[0.15] bg-gray-50/80 dark:bg-themeDark/60 font-black text-xs">
                        <td class="font-black text-gray-900 dark:text-white uppercase tracking-wider border-r border-gray-200 dark:border-white/[0.08] text-xs">
                            Grand Total
                        </td>

                        <!-- Grand Total Due -->
                        <td class="text-right font-mono font-black text-gray-900 dark:text-white">
                            {{ number_format($grand_total['due']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-900 dark:text-white">
                            {{ number_format($grand_total['due']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-600 dark:text-gray-400">
                            {{ number_format($grand_total['due']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-blue-700 dark:text-blue-300 bg-blue-100/40 dark:bg-blue-900/40 border-r border-gray-200 dark:border-white/[0.08]">
                            {{ number_format($grand_total['due']['total'], 2) }}
                        </td>

                        <!-- Grand Total Collected -->
                        <td class="text-right font-mono font-black text-gray-900 dark:text-white">
                            {{ number_format($grand_total['collected']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-900 dark:text-white">
                            {{ number_format($grand_total['collected']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-600 dark:text-gray-400">
                            {{ number_format($grand_total['collected']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-emerald-700 dark:text-emerald-300 bg-emerald-100/40 dark:bg-emerald-900/40 border-r border-gray-200 dark:border-white/[0.08]">
                            {{ number_format($grand_total['collected']['total'], 2) }}
                        </td>

                        <!-- Grand Total Outstanding -->
                        <td class="text-right font-mono font-black text-gray-900 dark:text-white">
                            {{ number_format($grand_total['outstanding']['monthly_fee'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-900 dark:text-white">
                            {{ number_format($grand_total['outstanding']['other_fees'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-gray-600 dark:text-gray-400">
                            {{ number_format($grand_total['outstanding']['other'], 2) }}
                        </td>
                        <td class="text-right font-mono font-black text-rose-700 dark:text-rose-300 bg-rose-100/40 dark:bg-rose-900/40">
                            {{ number_format($grand_total['outstanding']['total'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection

