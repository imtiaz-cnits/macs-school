@extends('tyro-dashboard::layouts.admin')

@section('title', 'Financial Statement & Cash Flow')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="{ activeTab: 'statement' }">

    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Financial Statement & Cash Flow
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Income vs Expense reconciliation, method-wise cash flow, and actual collection audits
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('accounts.reports.print', request()->query()) }}" target="_blank" class="h-11 px-5 bg-gray-900 hover:bg-black text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Statement
            </a>
            <a href="{{ route('accounts.expenses.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Expenses
            </a>
            <a href="{{ route('fees.reports.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Fee Collections
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm mb-8 no-print">
        <form action="{{ route('accounts.reports.index') }}" method="GET" class="space-y-4">
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

                        <!-- Calendar Dropdown Panel (Rule 10) -->
                        <div x-show="show" x-cloak class="absolute left-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                            <div class="flex items-center justify-between gap-1 mb-2 pb-2 border-b border-gray-100 dark:border-white/[0.06]">
                                <!-- Month Dropdown Toggle -->
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

                                <!-- Year Input Field -->
                                <input type="number" x-model.number="currentYear" @input="generateCalendar()" class="w-16 h-7 text-xs font-black text-center bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-themeBlue [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">

                                <!-- Month Navigation Arrows -->
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Days Header -->
                            <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>

                            <!-- Days Grid -->
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(d, i) in days" :key="i">
                                    <button type="button" @click="selectDay(d.day)"
                                            class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all"
                                            :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                            :disabled="!d.isCurrentMonth">
                                        <span x-text="d.day"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Action Shortcuts -->
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                                <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                                <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
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

                        <!-- Calendar Dropdown Panel (Rule 10) -->
                        <div x-show="show" x-cloak class="absolute left-0 sm:left-auto sm:right-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                            <div class="flex items-center justify-between gap-1 mb-2 pb-2 border-b border-gray-100 dark:border-white/[0.06]">
                                <!-- Month Dropdown Toggle -->
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

                                <!-- Year Input Field -->
                                <input type="number" x-model.number="currentYear" @input="generateCalendar()" class="w-16 h-7 text-xs font-black text-center bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-themeBlue [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">

                                <!-- Month Navigation Arrows -->
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Days Header -->
                            <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>

                            <!-- Days Grid -->
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(d, i) in days" :key="i">
                                    <button type="button" @click="selectDay(d.day)"
                                            class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all"
                                            :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                            :disabled="!d.isCurrentMonth">
                                        <span x-text="d.day"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Action Shortcuts -->
                            <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                                <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                                <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch Filter: Rule 7 Custom Select -->
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
                            <button type="button" @click="select('', 'All Branches')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Branches</span>
                                <svg x-show="value === ''" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @foreach($branches as $b)
                                <button type="button" @click="select('{{ $b->id }}', '{{ addslashes($b->branch_name) }}')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == '{{ $b->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $b->branch_name }}</span>
                                    <svg x-show="value == '{{ $b->id }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Payment Method Filter: Rule 7 Custom Select -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Payment Method</label>
                    <div x-data="{
                        open: false,
                        value: '{{ $paymentMethod ?? '' }}',
                        label: '{{ !empty($paymentMethod) ? $paymentMethod : 'All Methods' }}',
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                        }
                    }" class="relative" @click.away="open = false">
                        <input type="hidden" name="payment_method" :value="value">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute left-0 sm:left-auto sm:right-0 z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="select('', 'All Methods')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Methods</span>
                                <svg x-show="value === ''" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @foreach(['Cash', 'Bank', 'bKash', 'Nagad', 'Cheque', 'Rocket'] as $m)
                                <button type="button" @click="select('{{ $m }}', '{{ $m }}')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value.toLowerCase() === '{{ strtolower($m) }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $m }}</span>
                                    <svg x-show="value.toLowerCase() === '{{ strtolower($m) }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('accounts.reports.index') }}" class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-500 dark:text-gray-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap">
                    Reset
                </a>
                <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                    Generate Statement
                </button>
            </div>
        </form>
    </div>

    <!-- 4 Primary Financial KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <!-- Total Income (Actual Collections) -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Total Collected Income</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 flex items-center justify-center text-themeGreen">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($totalIncome, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                {{ $totalCollectionsCount }} successful payment receipt(s)
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-red-600 dark:text-red-400">Total Expenses</span>
                <span class="w-8 h-8 rounded-xl bg-red-50 dark:bg-red-950/20 flex items-center justify-center text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($totalExpense, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                {{ $totalExpensesCount }} debit voucher(s) recorded
            </div>
        </div>

        <!-- Overall Net Surplus / Deficit -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest {{ $netSurplus >= 0 ? 'text-themeBlue' : 'text-red-500' }}">
                    {{ $netSurplus >= 0 ? 'Net Operating Surplus' : 'Operating Deficit' }}
                </span>
                <span class="w-8 h-8 rounded-xl {{ $netSurplus >= 0 ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue' : 'bg-red-50 dark:bg-red-950/20 text-red-500' }} flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono {{ $netSurplus >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600' }}">
                ৳ {{ number_format($netSurplus, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                All payment methods combined
            </div>
        </div>

        <!-- Physical Cash Movement (Cash In - Cash Out) -->
        <div class="bg-gradient-to-br from-themeBlue to-indigo-900 text-white rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-100">Net Physical Cash Movement</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono">
                ৳ {{ number_format($netCashMovement, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-blue-100 mt-1">
                Cash In: ৳ {{ number_format($totalCashIn, 2) }} • Cash Out: ৳ {{ number_format($totalCashOut, 2) }}
            </div>
        </div>
    </div>

    <!-- Contextual Invoicing & Receivables Callout Banner -->
    <div class="mb-8 p-5 bg-gray-50 dark:bg-themeDark border border-gray-200/60 dark:border-white/[0.06] rounded-3xl">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-550 block mb-1">
                    Billed Invoices vs Actual Collected Cash (Accounts Receivable Scope)
                </span>
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                    Per accounting standards, invoices are receivables targets. Only actual fee payments are counted as recognized income. Consolidated statements include central/unassigned institutional expenses, while individual campus statements exclude them.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-6 text-xs font-bold text-gray-700 dark:text-gray-300">
                <div>
                    <span class="text-[10px] text-gray-400 block font-normal uppercase">Billed Gross</span>
                    <span class="font-mono">৳ {{ number_format($totalBilledGross, 2) }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 block font-normal uppercase">Discounts</span>
                    <span class="font-mono text-amber-600">৳ {{ number_format($totalBilledDiscount, 2) }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 block font-normal uppercase">Invoiced Net</span>
                    <span class="font-mono font-black">৳ {{ number_format($totalBilledNet, 2) }}</span>
                </div>
                <div class="border-l border-gray-300 dark:border-gray-700 pl-4">
                    <span class="text-[10px] text-red-500 block font-normal uppercase">Outstanding Dues</span>
                    <span class="font-mono font-black text-red-600">৳ {{ number_format($totalOutstandingDue, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Reconciliation & Cash Flow Matrix -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8 overflow-hidden">
        <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-white/[0.05] pb-3">
            <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Payment-Method-Wise Reconciliation & Net Cash Flow
            </h3>
            <span class="text-xs font-semibold text-gray-400">
                Period: {{ date('d M Y', strtotime($startDate)) }} — {{ date('d M Y', strtotime($endDate)) }}
            </span>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Payment Channel</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] text-right">Inflow (Income)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-red-600 dark:text-red-400 uppercase tracking-[0.2em] text-right">Outflow (Expense)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Net Movement</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-28">Flow Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($methodSummary as $row)
                        @if($row['in'] > 0 || $row['out'] > 0)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04] {{ strtolower($row['method']) === 'cash' ? 'bg-blue-50/20 dark:bg-blue-950/10' : '' }}">
                            <td class="py-4 px-4 font-bold text-gray-900 dark:text-gray-100 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full {{ strtolower($row['method']) === 'cash' ? 'bg-themeBlue' : (in_array(strtolower($row['method']), ['bkash', 'nagad', 'rocket']) ? 'bg-pink-500' : 'bg-gray-400') }}"></span>
                                    <span>{{ $row['method'] }}</span>
                                    @if(strtolower($row['method']) === 'cash')
                                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-blue-100 text-themeBlue dark:bg-blue-900/40 dark:text-blue-300">Physical Cash</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-4 font-mono font-bold text-right text-emerald-600 dark:text-emerald-400">
                                ৳ {{ number_format($row['in'], 2) }}
                            </td>
                            <td class="py-4 px-4 font-mono font-bold text-right text-red-600 dark:text-red-400">
                                ৳ {{ number_format($row['out'], 2) }}
                            </td>
                            <td class="py-4 px-4 font-mono font-black text-right {{ $row['net'] >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600' }}">
                                ৳ {{ number_format($row['net'], 2) }}
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block {{ $row['net'] >= 0 ? 'bg-green-50 text-themeGreen dark:bg-green-950/20 dark:text-green-400' : 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400' }}">
                                    {{ $row['net'] >= 0 ? 'Surplus' : 'Deficit' }}
                                </span>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    <!-- Total Row -->
                    <tr class="bg-gray-50/80 dark:bg-themeDark font-black">
                        <td class="py-4 px-4 text-xs uppercase tracking-wider text-gray-900 dark:text-white">
                            Total Financial Position
                        </td>
                        <td class="py-4 px-4 font-mono text-right text-emerald-600 dark:text-emerald-400 text-sm">
                            ৳ {{ number_format($totalIncome, 2) }}
                        </td>
                        <td class="py-4 px-4 font-mono text-right text-red-600 dark:text-red-400 text-sm">
                            ৳ {{ number_format($totalExpense, 2) }}
                        </td>
                        <td class="py-4 px-4 font-mono text-right text-sm {{ $netSurplus >= 0 ? 'text-themeBlue' : 'text-red-600' }}">
                            ৳ {{ number_format($netSurplus, 2) }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                Total
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Breakdowns Grid (Income by Fee Category vs Expense by Expense Category) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

        <!-- Left: Income by Fee Category -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-white/[0.05] pb-3">
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    Income by Fee Category
                </h3>
                <span class="text-xs font-mono font-bold text-themeGreen">৳ {{ number_format($totalIncome, 2) }}</span>
            </div>

            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Category</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-20">Receipts</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Amount</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right w-16">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incomeByCategory as $inc)
                        <tr class="border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-3 px-3 text-xs font-bold text-gray-800 dark:text-gray-200">{{ $inc['name'] }}</td>
                            <td class="py-3 px-3 text-xs font-mono text-center text-gray-500">{{ $inc['count'] }}</td>
                            <td class="py-3 px-3 text-xs font-mono font-bold text-right text-gray-900 dark:text-white">৳ {{ number_format($inc['amount'], 2) }}</td>
                            <td class="py-3 px-3 text-xs font-mono text-right text-emerald-600">{{ $inc['percentage'] }}%</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-xs text-gray-400">No income recorded for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Expense by Expense Category -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-white/[0.05] pb-3">
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    Expense by Category
                </h3>
                <span class="text-xs font-mono font-bold text-red-600">৳ {{ number_format($totalExpense, 2) }}</span>
            </div>

            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Category</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-20">Vouchers</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Amount</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right w-16">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseByCategory as $exp)
                        <tr class="border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-3 px-3 text-xs font-bold text-gray-800 dark:text-gray-200">{{ $exp['name'] }}</td>
                            <td class="py-3 px-3 text-xs font-mono text-center text-gray-500">{{ $exp['count'] }}</td>
                            <td class="py-3 px-3 text-xs font-mono font-bold text-right text-gray-900 dark:text-white">৳ {{ number_format($exp['amount'], 2) }}</td>
                            <td class="py-3 px-3 text-xs font-mono text-right text-red-600">{{ $exp['percentage'] }}%</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-xs text-gray-400">No expenses recorded for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Period Transactions Explorer (Tabbed) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8 overflow-hidden">

        <!-- Tab Navigation -->
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/[0.05] pb-4 mb-4">
            <div class="flex items-center gap-4">
                <button type="button" @click="activeTab = 'collections'" class="text-xs font-black uppercase tracking-wider py-1.5 px-3 rounded-xl transition-all" :class="activeTab === 'collections' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-400 hover:text-gray-700'">
                    Fee Collections in Period ({{ $totalCollectionsCount }})
                </button>
                <button type="button" @click="activeTab = 'expenses'" class="text-xs font-black uppercase tracking-wider py-1.5 px-3 rounded-xl transition-all" :class="activeTab === 'expenses' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-400 hover:text-gray-700'">
                    Expenses in Period ({{ $totalExpensesCount }})
                </button>
            </div>
            <span class="text-[11px] text-gray-400 font-semibold">Showing records matching active filters</span>
        </div>

        <!-- Collections Tab Content -->
        <div x-show="activeTab === 'collections'">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto max-h-96">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent sticky top-0 bg-white dark:bg-themeNavy">
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Receipt No</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Student</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Fee Category</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Method</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $pay)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-2.5 px-3 font-mono font-bold text-xs text-themeBlue">{{ $pay->receipt_no }}</td>
                            <td class="py-2.5 px-3 text-xs text-gray-600 dark:text-gray-300">{{ date('d M Y', strtotime($pay->payment_date)) }}</td>
                            <td class="py-2.5 px-3 text-xs font-bold text-gray-900 dark:text-gray-100">{{ $pay->student->student_name ?? 'N/A' }}</td>
                            <td class="py-2.5 px-3 text-xs text-gray-700 dark:text-gray-300">{{ $pay->invoice?->feeSetup?->category?->name ?? 'Fee' }}</td>
                            <td class="py-2.5 px-3 text-xs">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-white/[0.05] text-gray-700 dark:text-gray-300 uppercase">
                                    {{ $pay->payment_method }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-xs text-right text-emerald-600">৳ {{ number_format($pay->paid_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-gray-400">No collection records in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expenses Tab Content -->
        <div x-show="activeTab === 'expenses'" style="display: none;">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto max-h-96">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent sticky top-0 bg-white dark:bg-themeNavy">
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Voucher No</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Category</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Paid To</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Method</th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-2.5 px-3 font-mono font-bold text-xs text-themeBlue">{{ $exp->voucher_no }}</td>
                            <td class="py-2.5 px-3 text-xs text-gray-600 dark:text-gray-300">{{ date('d M Y', strtotime($exp->expense_date)) }}</td>
                            <td class="py-2.5 px-3 text-xs font-bold text-gray-900 dark:text-gray-100">{{ $exp->category->name ?? 'Uncategorized' }}</td>
                            <td class="py-2.5 px-3 text-xs text-gray-700 dark:text-gray-300">{{ $exp->paid_to }}</td>
                            <td class="py-2.5 px-3 text-xs">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-white/[0.05] text-gray-700 dark:text-gray-300 uppercase">
                                    {{ $exp->payment_method }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-xs text-right text-red-600">৳ {{ number_format($exp->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs text-gray-400">No expense records in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Institutional Compliance & Policy Notice -->
    <div class="p-5 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl mb-8">
        <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-550 mb-2">Accounting Compliance & Operational Scope</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-medium text-gray-600 dark:text-gray-400">
            <div class="flex items-start gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-themeBlue mt-1.5 shrink-0"></span>
                <span><strong>Branch Scope:</strong> Consolidated statements include central and unassigned institutional expenses (<code class="font-mono text-[11px] text-gray-500">branch_id = null</code>). Individual campus statements strictly isolate branch-attributed expenses.</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-themeBlue mt-1.5 shrink-0"></span>
                <span><strong>Refunds & Adjustments:</strong> The school management system does not record cash refunds or payment reversals. Fee adjustments solely modify invoice discounts and future receivables.</span>
            </div>
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
