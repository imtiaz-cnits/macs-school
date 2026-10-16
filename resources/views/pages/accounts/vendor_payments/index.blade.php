@extends('tyro-dashboard::layouts.admin')

@section('title', 'Vendor Payments')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="vendorPaymentsPage()">

    <!-- Page Header (Rule 4) -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Vendor Payments
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-450 mt-1">
                Disbursement records, bill settlements, payment vouchers, and payment method reconciliation
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('accounts.payables.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Payables Dashboard
            </a>
            <a href="{{ route('accounts.vendor-payments.create') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Record Payment
            </a>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <!-- Today's Disbursements -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-550">Today's Disbursements</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 flex items-center justify-center text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($todayTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                Cash & bank outflows today
            </div>
        </div>

        <!-- This Month's Disbursements -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-themeBlue">This Month Total</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/20 flex items-center justify-center text-themeBlue">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-themeBlue">
                ৳ {{ number_format($thisMonthTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                Total settlements this calendar month
            </div>
        </div>

        <!-- Filtered Total Paid -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-550">Filtered Total Paid</span>
                <span class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/20 flex items-center justify-center text-purple-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($filteredTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                Sum of matching records
            </div>
        </div>

        <!-- Filtered Transactions Count -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-550">Transactions Count</span>
                <span class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                {{ number_format($filteredCount) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                Disbursement entries found
            </div>
        </div>

    </div>

    <!-- Filter Card (Rule 7 Custom Selects & Rule 10 Custom DatePickers) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm mb-8">
        <form method="GET" action="{{ route('accounts.vendor-payments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">

            <!-- Start Date: Rule 10 Custom Alpine DatePicker -->
            <div>
                <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5 ml-1">Start Date</label>
                <div class="relative" x-data="datePicker('{{ $startDate }}')" @click.away="show = false; mOpen = false">
                    <input type="hidden" name="start_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                                <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors"><svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                                <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors"><svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(d, i) in days" :key="i">
                                <button type="button" @click="selectDay(d.day)" class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all" :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'" :disabled="!d.isCurrentMonth">
                                    <span x-text="d.day"></span>
                                </button>
                            </template>
                        </div>
                        <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                            <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                            <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- End Date: Rule 10 Custom Alpine DatePicker -->
            <div>
                <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5 ml-1">End Date</label>
                <div class="relative" x-data="datePicker('{{ $endDate }}')" @click.away="show = false; mOpen = false">
                    <input type="hidden" name="end_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(d, i) in days" :key="i">
                                <button type="button" @click="selectDay(d.day)" class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all" :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'" :disabled="!d.isCurrentMonth">
                                    <span x-text="d.day"></span>
                                </button>
                            </template>
                        </div>
                        <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                            <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                            <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendor Custom Dropdown (Rule 7) -->
            <div x-data="{
                open: false,
                selected: '{{ $vendorId ?? '' }}',
                selectedLabel: '{{ $vendorId && $vendors->find($vendorId) ? addslashes($vendors->find($vendorId)->name) : 'All Vendors' }}',
                items: [
                    { id: '', name: 'All Vendors' },
                    @foreach($vendors as $v)
                    { id: '{{ $v->id }}', name: '{{ addslashes($v->name) }}' },
                    @endforeach
                ],
                select(item) {
                    this.selected = item.id;
                    this.selectedLabel = item.name;
                    this.open = false;
                }
            }" class="relative">
                <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Vendor / Payee</label>
                <input type="hidden" name="vendor_id" :value="selected">
                <button type="button" @click="open = !open" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    <span class="truncate" x-text="selectedLabel"></span>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                    <template x-for="item in items" :key="item.id">
                        <button type="button" @click="select(item)" :class="{'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black': selected === item.id}" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-700 dark:text-gray-300">
                            <span x-text="item.name"></span>
                            <svg x-show="selected === item.id" class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Payment Method Dropdown (Rule 7) -->
            <div x-data="{
                open: false,
                selected: '{{ $paymentMethod ?? '' }}',
                selectedLabel: '{{ $paymentMethod ?: 'All Methods' }}',
                items: [
                    { id: '', name: 'All Methods' },
                    { id: 'Cash', name: 'Cash' },
                    { id: 'Bank', name: 'Bank' },
                    { id: 'bKash', name: 'bKash' },
                    { id: 'Nagad', name: 'Nagad' },
                    { id: 'Cheque', name: 'Cheque' },
                    { id: 'Rocket', name: 'Rocket' }
                ],
                select(item) {
                    this.selected = item.id;
                    this.selectedLabel = item.name;
                    this.open = false;
                }
            }" class="relative">
                <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Payment Method</label>
                <input type="hidden" name="payment_method" :value="selected">
                <button type="button" @click="open = !open" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    <span class="truncate" x-text="selectedLabel"></span>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                    <template x-for="item in items" :key="item.id">
                        <button type="button" @click="select(item)" :class="{'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black': selected === item.id}" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-700 dark:text-gray-300">
                            <span x-text="item.name"></span>
                            <svg x-show="selected === item.id" class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Branch Dropdown (Rule 7) -->
            <div x-data="{
                open: false,
                selected: '{{ $branchId ?? '' }}',
                selectedLabel: '{{ $branchId && $branches->find($branchId) ? addslashes($branches->find($branchId)->branch_name) : 'All Branches' }}',
                items: [
                    { id: '', name: 'All Branches' },
                    @foreach($branches as $b)
                    { id: '{{ $b->id }}', name: '{{ addslashes($b->branch_name) }}' },
                    @endforeach
                ],
                select(item) {
                    this.selected = item.id;
                    this.selectedLabel = item.name;
                    this.open = false;
                }
            }" class="relative">
                <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Branch</label>
                <input type="hidden" name="branch_id" :value="selected">
                <button type="button" @click="open = !open" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    <span class="truncate" x-text="selectedLabel"></span>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                    <template x-for="item in items" :key="item.id">
                        <button type="button" @click="select(item)" :class="{'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black': selected === item.id}" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-700 dark:text-gray-300">
                            <span x-text="item.name"></span>
                            <svg x-show="selected === item.id" class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Search & Submit -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Payment / Ref #" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>
                <button type="submit" class="h-10 px-4 bg-themeBlue hover:bg-blue-600 text-white font-bold rounded-xl text-xs flex items-center justify-center transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
                <a href="{{ route('accounts.vendor-payments.index') }}" class="h-10 px-3 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-xs flex items-center justify-center transition-colors">
                    Reset
                </a>
            </div>

        </form>
    </div>

    <!-- Payments Directory Table Card (Rule 9 Borderless Standard) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                Vendor Payment Disbursements
            </h2>
            <span class="text-xs font-bold text-gray-400">
                Showing {{ $payments->firstItem() ?? 0 }}-{{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} records
            </span>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-28">Payment #</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-24">Date</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Vendor / Payee</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-28">Method</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-48">Allocations</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-32">Amount Paid</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                    @forelse($payments as $p)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <td class="py-0 px-0">
                                <a href="{{ route('accounts.vendor-payments.show', $p->id) }}" class="font-mono font-black text-themeBlue hover:underline text-xs">
                                    {{ $p->payment_no }}
                                </a>
                                @if($p->reference_no)
                                    <span class="block text-[10px] font-mono text-gray-400">Ref: {{ $p->reference_no }}</span>
                                @endif
                            </td>
                            <td class="py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                {{ date('d-M-Y', strtotime($p->payment_date)) }}
                            </td>
                            <td class="py-0 px-0">
                                <a href="{{ route('accounts.payables.ledger', $p->vendor_id) }}" class="text-sm font-bold text-gray-900 dark:text-gray-100 hover:text-themeBlue transition-colors block">
                                    {{ $p->vendor->name }}
                                </a>
                                <span class="text-[11px] text-gray-400">
                                    {{ $p->branch ? $p->branch->branch_name : 'Consolidated / Unassigned' }}
                                </span>
                            </td>
                            <td class="py-0 px-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    {{ $p->payment_method }}
                                </span>
                            </td>
                            <td class="py-0 px-0 text-xs text-gray-600 dark:text-gray-400">
                                @if($p->allocations->count() > 0)
                                    <div class="space-y-0.5">
                                        @foreach($p->allocations->take(2) as $alloc)
                                            <div class="text-[11px] flex items-center justify-between">
                                                <span class="truncate max-w-[140px]">
                                                    @if($alloc->allocatable_type === 'App\Models\VendorOpeningBalance')
                                                        <span class="text-purple-600 font-bold">OB:</span> {{ $alloc->allocatable->reference_no ?? 'Opening' }}
                                                    @else
                                                        <span class="text-themeBlue font-bold">Bill:</span> {{ $alloc->allocatable->voucher_no ?? 'Bill #' . $alloc->allocatable_id }}
                                                    @endif
                                                </span>
                                                <span class="font-mono font-semibold">৳{{ number_format($alloc->allocated_amount, 2) }}</span>
                                            </div>
                                        @endforeach
                                        @if($p->allocations->count() > 2)
                                            <span class="text-[10px] text-gray-400 italic">+{{ $p->allocations->count() - 2 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">—</span>
                                @endif
                            </td>
                            <td class="py-0 px-0 text-right">
                                <span class="text-sm font-black font-mono text-emerald-600 dark:text-emerald-400">
                                    ৳ {{ number_format($p->amount, 2) }}
                                </span>
                            </td>
                            <td class="py-0 px-0 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('accounts.vendor-payments.show', $p->id) }}" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-themeBlue/10 transition-colors" title="View Voucher">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <p class="font-bold text-sm">No vendor payments found</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Try adjusting your filters or record a new disbursement</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/[0.06]">
            {{ $payments->links() }}
        </div>
    </div>

    <!-- Payment Methods Breakdown Section -->
    @if(!empty($methodBreakdown))
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm">
            <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-550 mb-3">
                Payment Method Reconciliation Summary (Filtered Period)
            </h3>
            <div class="flex flex-wrap gap-4">
                @foreach($methodBreakdown as $method => $amount)
                    <div class="flex items-center gap-2 px-3.5 py-2 bg-gray-50/80 dark:bg-themeDark/40 rounded-xl border border-gray-100 dark:border-white/[0.06]">
                        <span class="w-2 h-2 rounded-full bg-themeBlue"></span>
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $method }}:</span>
                        <span class="text-xs font-black font-mono text-gray-900 dark:text-white">৳ {{ number_format($amount, 2) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
function vendorPaymentsPage() {
    return {};
}

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
@endsection
