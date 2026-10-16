@extends('tyro-dashboard::layouts.admin')

@section('title', 'Expenses Management')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="expenseManager()">

    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
                Expenses Management
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Track school disbursements, generate debit vouchers, and audit expenditure receipts
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('accounts.categories.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z"/></svg>
                Categories
            </a>

            <button type="button" @click="openAddModal()" class="h-11 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Record Expense
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-themeGreen/10 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-600 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-600 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Summary Cards (Rule 5) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Today's Expense -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Today's Expense</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-themeBlue/10 flex items-center justify-center text-themeBlue">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($todayTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                {{ date('d M Y') }}
            </div>
        </div>

        <!-- This Month's Expense -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">This Month's Expense</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-themeGreen/10 flex items-center justify-center text-themeGreen">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($thisMonthTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                {{ date('F Y') }}
            </div>
        </div>

        <!-- Filtered Total -->
        <div class="bg-gradient-to-br from-themeBlue to-indigo-800 rounded-3xl p-5 shadow-sm text-white hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-100">Filtered Total</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono">
                ৳ {{ number_format($filteredTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-blue-100 mt-1">
                {{ $filteredCount }} record(s) matching filter
            </div>
        </div>

        <!-- Method Breakdown Pill -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2 block">Payment Breakdown</span>
            <div class="space-y-1.5 overflow-y-auto max-h-16 text-xs">
                @forelse($methodBreakdown as $m => $total)
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300 font-semibold">
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $m }}:</span>
                        <span class="font-mono font-bold">৳ {{ number_format($total, 2) }}</span>
                    </div>
                @empty
                    <div class="text-xs text-gray-400">No payment data</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filters Card (Rule 4) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm mb-8 no-print">
        <form action="{{ route('accounts.expenses.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

                <!-- Start Date: Rule 10 Custom Alpine DatePicker -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Date From</label>
                    <div class="relative" x-data="datePicker('{{ $startDate }}')" @click.away="show = false; mOpen = false">
                        <input type="hidden" name="start_date" :value="value">
                        <button type="button" @click="show = !show" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
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

                <!-- End Date: Rule 10 Custom Alpine DatePicker -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Date To</label>
                    <div class="relative" x-data="datePicker('{{ $endDate }}')" @click.away="show = false; mOpen = false">
                        <input type="hidden" name="end_date" :value="value">
                        <button type="button" @click="show = !show" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
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

                <!-- Category Filter: Rule 7 Custom Select -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Category</label>
                    <div x-data="{
                        open: false,
                        value: '{{ $categoryId }}',
                        label: '{{ $categories->firstWhere('id', $categoryId)?->name ?? 'All Categories' }}',
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                        }
                    }" class="relative" @click.away="open = false">
                        <input type="hidden" name="expense_category_id" :value="value">
                        <button type="button" @click="open = !open" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="select('', 'All Categories')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Categories</span>
                                <svg x-show="value === ''" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @foreach($categories as $cat)
                                <button type="button" @click="select('{{ $cat->id }}', '{{ addslashes($cat->name) }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == '{{ $cat->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $cat->name }}</span>
                                    <svg x-show="value == '{{ $cat->id }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endforeach
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
                        <button type="button" @click="open = !open" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="select('', 'All Branches')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Branches</span>
                                <svg x-show="value === ''" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @foreach($branches as $b)
                                <button type="button" @click="select('{{ $b->id }}', '{{ addslashes($b->branch_name) }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == '{{ $b->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $b->branch_name }}</span>
                                    <svg x-show="value == '{{ $b->id }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Payment Method Filter: Rule 7 Custom Select -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Method</label>
                    <div x-data="{
                        open: false,
                        value: '{{ $paymentMethod }}',
                        label: '{{ $paymentMethod ?: 'All Methods' }}',
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                        }
                    }" class="relative" @click.away="open = false">
                        <input type="hidden" name="payment_method" :value="value">
                        <button type="button" @click="open = !open" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute left-0 sm:left-auto sm:right-0 z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="select('', 'All Methods')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Methods</span>
                                <svg x-show="value === ''" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            @foreach(['Cash', 'Bank', 'bKash', 'Nagad', 'Cheque'] as $m)
                                <button type="button" @click="select('{{ $m }}', '{{ $m }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === '{{ $m }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $m }}</span>
                                    <svg x-show="value === '{{ $m }}'" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Query & Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search Voucher No, Paid To, Vendor, Ref No, or Notes..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-250 px-3.5 placeholder-gray-400">
                <div class="flex gap-2 shrink-0">
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                        Filter Expenses
                    </button>
                    <a href="{{ route('accounts.expenses.index') }}" class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-500 dark:text-gray-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Expenses Table Card (Rule 9) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-8">

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Voucher No</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Category</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Paid To / Vendor</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Method</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Amount</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">Receipt</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-36">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $exp)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <!-- Voucher No -->
                        <td class="py-4 px-4 font-mono font-black text-themeBlue text-xs whitespace-nowrap">
                            {{ $exp->voucher_no }}
                        </td>

                        <!-- Date -->
                        <td class="py-4 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ date('d M Y', strtotime($exp->expense_date)) }}
                        </td>

                        <!-- Category -->
                        <td class="py-4 px-4">
                            @if($exp->items && $exp->items->isNotEmpty())
                                @php
                                    $uniqueCats = $exp->items->pluck('category.name')->filter()->unique()->values();
                                @endphp
                                @if($uniqueCats->count() > 1)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($uniqueCats->take(2) as $cName)
                                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-gray-100 dark:bg-white/[0.05] text-gray-800 dark:text-gray-200">
                                                {{ $cName }}
                                            </span>
                                        @endforeach
                                        @if($uniqueCats->count() > 2)
                                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-themeBlue/10 text-themeBlue">
                                                +{{ $uniqueCats->count() - 2 }}
                                            </span>
                                        @endif
                                    </div>
                                @elseif($uniqueCats->isNotEmpty())
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 dark:bg-white/[0.05] text-gray-800 dark:text-gray-200">
                                        {{ $uniqueCats->first() }}
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 dark:bg-white/[0.05] text-gray-800 dark:text-gray-200">
                                        {{ $exp->category->name ?? 'Uncategorized' }}
                                    </span>
                                @endif
                            @else
                                <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 dark:bg-white/[0.05] text-gray-800 dark:text-gray-200">
                                    {{ $exp->category->name ?? 'Uncategorized' }}
                                </span>
                            @endif
                        </td>

                        <!-- Paid To & Metadata -->
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <span>{{ $exp->paid_to }}</span>
                                @if($exp->is_overdue)
                                    <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-300 animate-pulse">Overdue</span>
                                @elseif($exp->payment_status === 'Unpaid')
                                    <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400">Unpaid Bill</span>
                                @elseif($exp->payment_status === 'Partially Paid')
                                    <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400">Due: ৳ {{ number_format($exp->due_amount, 2) }}</span>
                                @else
                                    <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400">Paid</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-400 dark:text-gray-500 font-medium">
                                @if($exp->branch)
                                    <span>🏢 {{ $exp->branch->branch_name }}</span>
                                @endif
                                @if($exp->reference_no)
                                    <span>• Ref: {{ $exp->reference_no }}</span>
                                @endif
                                @if($exp->due_date && $exp->due_amount > 0)
                                    <span class="font-semibold text-rose-500">• Due: {{ date('d-M-Y', strtotime($exp->due_date)) }}</span>
                                @endif
                                @if($exp->items_count ?? $exp->items->count())
                                    <span class="text-themeBlue font-bold">• {{ $exp->items->count() }} item(s)</span>
                                @endif
                            </div>
                        </td>

                        <!-- Method -->
                        <td class="py-4 px-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block
                                @if($exp->payment_method === 'Cash') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400
                                @elseif($exp->payment_method === 'Bank') bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400
                                @elseif(in_array($exp->payment_method, ['bKash', 'Nagad'])) bg-pink-50 text-pink-700 dark:bg-pink-950/20 dark:text-pink-400
                                @elseif($exp->payment_method === 'Credit / Payable') bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400
                                @else bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400
                                @endif">
                                {{ $exp->payment_method }}
                            </span>
                        </td>

                        <!-- Amount -->
                        <td class="py-4 px-4 font-mono font-black text-right text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                            ৳ {{ number_format($exp->amount, 2) }}
                        </td>

                        <!-- Receipt View Link (Authenticated Private Stream) -->
                        <td class="py-4 px-4 text-center">
                            @if($exp->hasReceipt())
                                <a href="{{ route('expenses.receipt.view', $exp->id) }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue hover:bg-themeBlue hover:text-white transition-all shadow-xs" title="View Secure Receipt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                </a>
                            @else
                                <span class="text-gray-300 dark:text-gray-600 font-mono text-sm">-</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($exp->due_amount > 0 && $exp->vendor_id)
                                    <!-- Settle / Pay Bill Button -->
                                    <a href="{{ route('accounts.vendor-payments.create', ['vendor_id' => $exp->vendor_id]) }}" class="action-btn text-themeGreen hover:text-themeGreen hover:border-themeGreen" title="Pay / Settle Outstanding Bill">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </a>
                                @endif

                                <!-- Print Voucher Button -->
                                <a href="{{ route('accounts.expenses.voucher_print', $exp->id) }}" target="_blank" class="action-btn text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:border-gray-400" title="Print Debit Voucher">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                </a>

                                <!-- Edit Button -->
                                <button type="button" @click="openEditModal({{ json_encode($exp) }})" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Expense">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <!-- Delete / Void Button -->
                                <form action="{{ route('accounts.expenses.destroy', $exp->id) }}" method="POST" class="inline" onsubmit="return handleExpenseDelete(event, '{{ $exp->voucher_no }}', '৳ {{ number_format($exp->amount, 2) }}');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="delete_reason" value="Deleted via Expenses Directory">
                                    <button type="submit" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Expense">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>              </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider text-xs">
                            No expenses found matching the filter criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($expenses->hasPages())
            <div class="pt-6 border-t border-gray-100 dark:border-white/[0.05]">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <!-- Add / Edit Expense Modal (Alpine.js) -->
    <div x-show="isModalOpen" x-cloak @click.self="isModalOpen = false" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6 overflow-hidden" style="display: none;">
        <!-- Backdrop Blur (Rule 3) -->
        <div class="fixed inset-0 bg-themeDark/60 backdrop-blur-md transition-opacity" @click="isModalOpen = false"></div>

        <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl max-w-5xl w-full h-[90vh] max-h-[860px] flex flex-col overflow-hidden z-10 my-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <!-- Form wraps full modal (Header + Scrollable Body + Footer) -->
            <form :action="isEditMode ? '{{ url('accounts/expenses') }}/' + form.id : '{{ route('accounts.expenses.store') }}'"
                  method="POST"
                  enctype="multipart/form-data"
                  class="flex flex-col h-full min-h-0 overflow-hidden">
                @csrf
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Fixed Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 sm:px-8 sm:py-5 border-b border-gray-100 dark:border-white/[0.06] bg-white dark:bg-themeNavy shrink-0">
                    <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span x-text="isEditMode ? 'Edit Expense Voucher: ' + form.voucher_no : 'Record New Expense Voucher'"></span>
                    </h3>
                    <button type="button" @click="isModalOpen = false" class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark/60 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Scrollable Body Content -->
                <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5 sm:px-8 sm:py-6 space-y-4">
                    <!-- Hidden Inputs for Dropdown selections -->
                    <input type="hidden" name="expense_category_id" :value="form.items[0]?.expense_category_id || form.expense_category_id || ''">
                    <input type="hidden" name="vendor_id" :value="form.vendor_id">
                    <input type="hidden" name="paid_to" :value="form.paid_to">
                    <input type="hidden" name="branch_id" :value="form.branch_id">
                    <input type="hidden" name="payment_method" :value="form.payment_method">
                    <input type="hidden" name="amount" :value="calculateVoucherTotal().toFixed(2)">
                    <input type="hidden" name="payment_type" :value="form.payment_type">
                    <input type="hidden" name="initial_paid_amount" :value="form.initial_paid_amount">
                    <input type="hidden" name="due_date" :value="form.due_date">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <!-- Vendor / Recipient with Quick-Add '+' Button (One Vendor Per Voucher) -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Paid To / Vendor <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1" @click.away="if(activeDropdown === 'vendor') activeDropdown = null">
                                    <button type="button" @click="activeDropdown = activeDropdown === 'vendor' ? null : 'vendor'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                        <span class="truncate" x-text="vendorText()"></span>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'vendor' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <!-- Searchable Vendor Dropdown Menu -->
                                    <div x-show="activeDropdown === 'vendor'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-2 max-h-64 overflow-y-auto" x-transition>
                                        <input type="text" x-model="vendorSearch" placeholder="Search vendor..." class="w-full h-9 px-3 mb-1.5 bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-700 dark:text-gray-200 focus:outline-none focus:border-themeBlue">

                                        <div class="space-y-0.5">
                                            <template x-for="ven in filteredVendors()" :key="ven.id">
                                                <button type="button" @click="selectVendor(ven)" class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-lg text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.vendor_id == ven.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                                    <div>
                                                        <div class="font-bold" x-text="ven.name"></div>
                                                        <div class="text-[10px] text-gray-400" x-show="ven.phone" x-text="ven.phone"></div>
                                                    </div>
                                                    <template x-if="form.vendor_id == ven.id">
                                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    </template>
                                                </button>
                                            </template>
                                            <!-- Custom Recipient Option -->
                                            <button type="button" @click="enableCustomRecipient()" class="w-full px-3 py-2 text-xs text-left text-themeBlue font-bold hover:bg-themeBlue/5 rounded-lg border-t border-gray-100 dark:border-gray-800 mt-1 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                <span>Other / Custom Payee Name</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Compact Vendor '+' Button -->
                                <button type="button" @click="openQuickVendorModal()" class="h-11 w-11 shrink-0 bg-themeGreen/10 hover:bg-themeGreen text-themeGreen hover:text-white border-2 border-themeGreen/20 rounded-xl flex items-center justify-center transition-all shadow-xs" title="Add New Vendor">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                            <!-- Inline Custom Recipient Text Input when not selecting from registered vendor -->
                            <div x-show="isCustomRecipient" x-cloak class="mt-2" x-transition>
                                <input type="text" x-model="form.paid_to" placeholder="Type custom payee or store name..." class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue">
                            </div>
                        </div>

                        <!-- Expense Date: Rule 10 Custom Alpine DatePicker -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Expense Date <span class="text-red-500">*</span>
                            </label>
                            <input type="hidden" name="expense_date" :value="form.expense_date">
                            <div class="relative" x-data="datePicker(form.expense_date)" @date-selected.window="if($event.detail) { value = $event.detail; form.expense_date = $event.detail; }" @click.away="show = false; mOpen = false">
                                <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate font-mono" x-text="formatDisplay(form.expense_date)"></span>
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
                                </div>
                            </div>
                        </div>

                        <!-- Branch: Custom Select (Rule 7) -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Branch (Optional)
                            </label>
                            <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                                <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="branchText()"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'branch' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-56 overflow-y-auto" x-transition>
                                    <button type="button" @click="selectBranch('', 'Main Branch / General')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45" :class="form.branch_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>Main Branch / General</span>
                                        <template x-if="form.branch_id === ''">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                    @foreach($branches as $b)
                                        <button type="button" @click="selectBranch('{{ $b->id }}', '{{ $b->branch_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45" :class="form.branch_id == '{{ $b->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span>{{ $b->branch_name }}</span>
                                            <template x-if="form.branch_id == '{{ $b->id }}'">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Payment Terms Selector -->
                        <div class="sm:col-span-2 p-3 bg-gray-50/70 dark:bg-themeDark/40 rounded-2xl border border-gray-150 dark:border-white/[0.08]" x-show="!isEditMode">
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                                Payment Terms / Settlement Option <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="form.payment_type = 'full'; if(form.payment_method === 'Credit / Payable') form.payment_method = 'Cash';" :class="form.payment_type === 'full' ? 'bg-themeBlue text-white font-black shadow-sm' : 'bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800'" class="h-9 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>Full Payment</span>
                                </button>
                                <button type="button" @click="form.payment_type = 'partial'; if(form.payment_method === 'Credit / Payable') form.payment_method = 'Cash';" :class="form.payment_type === 'partial' ? 'bg-themeBlue text-white font-black shadow-sm' : 'bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800'" class="h-9 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2"/></svg>
                                    <span>Partial Payment</span>
                                </button>
                                <button type="button" @click="form.payment_type = 'unpaid'; form.payment_method = 'Credit / Payable';" :class="form.payment_type === 'unpaid' ? 'bg-themeBlue text-white font-black shadow-sm' : 'bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-800'" class="h-9 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span>Credit / Unpaid Bill</span>
                                </button>
                            </div>
                        </div>

                        <!-- Payment Method: Custom Select (Rule 7) -->
                        <div x-show="form.payment_type !== 'unpaid'">
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <div class="relative" @click.away="if(activeDropdown === 'method') activeDropdown = null">
                                <button type="button" @click="activeDropdown = activeDropdown === 'method' ? null : 'method'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="form.payment_method"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'method' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'method'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1" x-transition>
                                    @foreach(['Cash', 'Bank', 'bKash', 'Nagad', 'Cheque'] as $m)
                                        <button type="button" @click="form.payment_method = '{{ $m }}'; activeDropdown = null;" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45" :class="form.payment_method === '{{ $m }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span>{{ $m }}</span>
                                            <template x-if="form.payment_method === '{{ $m }}'">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Initial Paid Amount (When Partial) -->
                        <div x-show="form.payment_type === 'partial'" x-cloak>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Initial Paid Amount (৳) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" :max="calculateVoucherTotal()" name="initial_paid_amount" x-model.number="form.initial_paid_amount" placeholder="Amount paid today..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-mono font-bold text-gray-900 dark:text-gray-100 px-3.5">
                            <div class="mt-1 text-[10px] font-semibold text-rose-500" x-show="form.initial_paid_amount > 0">
                                Remaining Due: ৳ <span x-text="formatMoney(Math.max(0, calculateVoucherTotal() - (form.initial_paid_amount || 0)))"></span>
                            </div>
                        </div>

                        <!-- Due Date (When Partial or Unpaid or in Edit Mode) -->
                        <div x-show="form.payment_type !== 'full' || isEditMode" :class="form.payment_type === 'unpaid' ? 'sm:col-span-2' : ''" x-cloak>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Payment Due Date (Optional)
                            </label>
                            <input type="date" name="due_date" x-model="form.due_date" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                        </div>

                        <!-- Reference No -->
                        <div class="sm:col-span-2">
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Reference / Bill No / Cheque No / TrxID (Optional)
                            </label>
                            <input type="text" name="reference_no" x-model="form.reference_no" placeholder="e.g. Memo #4451, Invoice #889, or Cheque No" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                        </div>
                    </div>

                    <!-- Multi-Item Voucher Line Items (Multi-Category Support) -->
                    <div class="mb-5 p-4 rounded-2xl border border-gray-150 dark:border-white/[0.08] bg-gray-50/40 dark:bg-themeDark/30">
                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200 dark:border-white/[0.06]">
                            <div>
                                <span class="text-xs font-black uppercase tracking-wider text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    Voucher Line Items
                                </span>
                                <span class="text-[10px] text-gray-400 font-semibold block mt-0.5">Line Total = Quantity × Unit Rate • Individual Item Categories</span>
                            </div>
                            <button type="button" @click="addItem()" class="h-8 px-3 border border-themeBlue/30 hover:border-themeBlue bg-themeBlue/5 hover:bg-themeBlue text-themeBlue hover:text-white rounded-lg text-[11px] font-black uppercase tracking-wider flex items-center gap-1.5 transition-all shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Add Item Row
                            </button>
                        </div>

                        <!-- Line Items Inputs Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-555 border-b border-gray-200 dark:border-white/[0.05]">
                                        <th class="py-2 px-2 w-4/12">Item Description *</th>
                                        <th class="py-2 px-2 w-3/12">Category *</th>
                                        <th class="py-2 px-2 w-1.5/12 text-center">Qty *</th>
                                        <th class="py-2 px-2 w-1.5/12 text-right">Rate (৳) *</th>
                                        <th class="py-2 px-2 w-2/12 text-right">Total (৳)</th>
                                        <th class="py-2 px-1 text-center w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in form.items" :key="index">
                                        <tr class="border-b border-gray-100 dark:border-white/[0.04]">
                                            <!-- Description -->
                                            <td class="py-2 px-2">
                                                <input type="text" x-model="item.item_name" placeholder="Item name or description" class="w-full h-10 px-3 bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue" required>
                                            </td>
                                            <!-- Item Category with inline Quick '+' Button -->
                                            <td class="py-2 px-2">
                                                <div class="flex items-center gap-1">
                                                    <select x-model="item.expense_category_id" class="w-full h-10 px-2.5 bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue" required>
                                                        <option value="" disabled>Select Category</option>
                                                        <template x-for="cat in categoriesList" :key="cat.id">
                                                            <option :value="cat.id" :selected="item.expense_category_id == cat.id" x-text="cat.name"></option>
                                                        </template>
                                                    </select>
                                                    <button type="button" @click="openQuickCategoryModal(index)" class="h-10 w-8 shrink-0 bg-themeBlue/10 hover:bg-themeBlue text-themeBlue hover:text-white border border-themeBlue/20 rounded-xl flex items-center justify-center transition-all shadow-xs" title="Quick Add Category">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <!-- Quantity -->
                                            <td class="py-2 px-2">
                                                <input type="number" step="0.01" min="0.01" x-model.number="item.quantity" class="w-full h-10 px-2 text-center font-mono font-bold bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue" required>
                                            </td>
                                            <!-- Unit Rate -->
                                            <td class="py-2 px-2">
                                                <input type="number" step="0.01" min="0" x-model.number="item.unit_rate" class="w-full h-10 px-2 text-right font-mono font-bold bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue" required>
                                            </td>
                                            <!-- Calculated Line Total -->
                                            <td class="py-2 px-2">
                                                <div class="h-10 px-2 flex items-center justify-end font-mono font-black text-xs text-gray-900 dark:text-gray-100 bg-gray-100/70 dark:bg-white/[0.04] rounded-xl whitespace-nowrap">
                                                    ৳ <span x-text="formatMoney(calculateLineTotal(item))"></span>
                                                </div>
                                            </td>
                                            <!-- Remove Row -->
                                            <td class="py-2 px-1 text-center">
                                                <button type="button" @click="removeItem(index)" :disabled="form.items.length <= 1" class="w-9 h-9 inline-flex items-center justify-center rounded-xl text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/20 disabled:opacity-30 disabled:cursor-not-allowed transition-all" title="Remove Item">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Hidden Inputs for Form Submission -->
                        <template x-for="(item, idx) in form.items" :key="'hidden_' + idx">
                            <div>
                                <input type="hidden" :name="'items[' + idx + '][item_name]'" :value="item.item_name">
                                <input type="hidden" :name="'items[' + idx + '][expense_category_id]'" :value="item.expense_category_id">
                                <input type="hidden" :name="'items[' + idx + '][quantity]'" :value="item.quantity">
                                <input type="hidden" :name="'items[' + idx + '][unit_rate]'" :value="item.unit_rate">
                                <input type="hidden" :name="'items[' + idx + '][note]'" :value="item.note || ''">
                            </div>
                        </template>

                        <!-- Live Grand Voucher Total Bar -->
                        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-white/[0.08] flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Voucher Amount</span>
                            <div class="text-right">
                                <span class="text-xl font-black font-mono text-themeBlue">
                                    ৳ <span x-text="formatMoney(calculateVoucherTotal())"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt File Upload (Private storage) -->
                    <div class="mb-4">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Attach Receipt / Bill Memo (Max 5MB - JPG, PNG, WebP, PDF)
                        </label>
                        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-wider file:bg-gray-100 dark:file:bg-gray-800 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-200 cursor-pointer border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl p-2 bg-gray-50/50 dark:bg-themeDark">

                        <!-- Existing Receipt in Edit Mode -->
                        <template x-if="isEditMode && form.has_receipt">
                            <div class="mt-2.5 p-3 rounded-xl bg-gray-50 dark:bg-themeDark border border-gray-100 dark:border-white/[0.05] flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Existing receipt attached
                                </span>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remove_receipt" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    <span class="text-[11px] font-bold text-red-600">Remove Receipt</span>
                                </label>
                            </div>
                        </template>
                    </div>

                    <!-- Reason for Edit (Only in Edit Mode) -->
                    <template x-if="isEditMode">
                        <div class="mb-4">
                            <label class="text-[10px] font-black tracking-widest text-amber-600 dark:text-amber-400 uppercase mb-1.5 block">
                                Reason for Update (Audit Trail)
                            </label>
                            <input type="text" name="edit_reason" x-model="form.edit_reason" placeholder="e.g. Corrected line items quantity or vendor" class="w-full h-11 border-2 border-amber-200 dark:border-amber-900/50 rounded-xl bg-amber-50/30 dark:bg-amber-950/10 focus:outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                        </div>
                    </template>

                    <!-- Notes -->
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Notes / Remarks (Optional)
                        </label>
                        <textarea name="note" x-model="form.note" rows="2" placeholder="Additional details or justification..." class="w-full min-h-[70px] border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 p-3 placeholder-gray-400"></textarea>
                    </div>
                </div>

                <!-- Fixed Modal Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 sm:px-8 sm:py-4 border-t border-gray-100 dark:border-white/[0.06] bg-gray-50/90 dark:bg-themeDark/60 backdrop-blur-sm shrink-0">
                    <button type="button" @click="isModalOpen = false" class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-wider hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-all shadow-xs">
                        Cancel
                    </button>
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest active:scale-95 flex items-center gap-2">
                        <span x-text="isEditMode ? 'Update Voucher' : 'Save Expense Voucher'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Category Modal (Phase 4) -->
    <div x-show="isCategoryModalOpen" x-cloak class="fixed inset-0 z-[10000] overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-themeDark/70 backdrop-blur-sm transition-opacity" @click="isCategoryModalOpen = false"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-3xl shadow-2xl max-w-md w-full p-6" @click.stop>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100 dark:border-white/[0.06]">
                    <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Quick Add Category
                    </h4>
                    <button type="button" @click="isCategoryModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">Category Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="categoryForm.name" placeholder="e.g. Science Lab Supplies" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue">
                    </div>
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">Description (Optional)</label>
                        <textarea x-model="categoryForm.description" rows="2" placeholder="Brief description..." class="w-full p-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/[0.06]">
                        <button type="button" @click="isCategoryModalOpen = false" class="h-10 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-black uppercase text-gray-600 dark:text-gray-300 hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="saveQuickCategory()" :disabled="isCategorySubmitting" class="h-10 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md hover:shadow-lg disabled:opacity-50 flex items-center gap-1.5">
                            <span x-text="isCategorySubmitting ? 'Saving...' : 'Save Category'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Vendor Modal (Phase 5) -->
    <div x-show="isVendorModalOpen" x-cloak class="fixed inset-0 z-[10000] overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-themeDark/70 backdrop-blur-sm transition-opacity" @click="isVendorModalOpen = false"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-3xl shadow-2xl max-w-md w-full p-6" @click.stop>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100 dark:border-white/[0.06]">
                    <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Quick Add Vendor / Payee
                    </h4>
                    <button type="button" @click="isVendorModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Vendor / Shop Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="vendorForm.name" placeholder="e.g. Modern Books & Stationers" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue">
                    </div>
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Contact Person (Optional)</label>
                        <input type="text" x-model="vendorForm.contact_person" placeholder="e.g. Mr. Rafiqul Islam" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue">
                    </div>
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Phone Number (Optional)</label>
                        <input type="text" x-model="vendorForm.phone" placeholder="e.g. 017xxxxxxxx" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue">
                    </div>
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Address (Optional)</label>
                        <input type="text" x-model="vendorForm.address" placeholder="e.g. Main Market, Pabna" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue">
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-white/[0.06]">
                        <button type="button" @click="isVendorModalOpen = false" class="h-10 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-black uppercase text-gray-600 dark:text-gray-300 hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="saveQuickVendor()" :disabled="isVendorSubmitting" class="h-10 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-md hover:shadow-lg disabled:opacity-50 flex items-center gap-1.5">
                            <span x-text="isVendorSubmitting ? 'Saving...' : 'Save Vendor'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function expenseManager() {
        return {
            isModalOpen: false,
            isEditMode: false,
            activeDropdown: null,
            isCustomRecipient: false,

            // Quick Category state
            isCategoryModalOpen: false,
            isCategorySubmitting: false,
            categoryTargetIndex: null,
            categoryForm: { name: '', description: '' },
            categoriesList: @json($activeCategories),

            // Quick Vendor state
            isVendorModalOpen: false,
            isVendorSubmitting: false,
            vendorSearch: '',
            vendorForm: { name: '', contact_person: '', phone: '', address: '' },
            vendorsList: @json($vendors),

            form: {
                id: null,
                voucher_no: '',
                expense_category_id: '',
                vendor_id: '',
                paid_to: '',
                branch_id: '',
                expense_date: '{{ date('Y-m-d') }}',
                payment_method: 'Cash',
                payment_type: 'full',
                initial_paid_amount: '',
                due_date: '',
                reference_no: '',
                note: '',
                edit_reason: '',
                has_receipt: false,
                items: [
                    { item_name: '', expense_category_id: '', quantity: 1, unit_rate: '', note: '' }
                ]
            },

            openAddModal() {
                this.isEditMode = false;
                this.isCustomRecipient = false;
                this.activeDropdown = null;
                const defaultCat = this.categoriesList.length > 0 ? this.categoriesList[0].id : '';
                this.form = {
                    id: null,
                    voucher_no: '',
                    expense_category_id: defaultCat,
                    vendor_id: '',
                    paid_to: '',
                    branch_id: '',
                    expense_date: '{{ date('Y-m-d') }}',
                    payment_method: 'Cash',
                    payment_type: 'full',
                    initial_paid_amount: '',
                    due_date: '',
                    reference_no: '',
                    note: '',
                    edit_reason: '',
                    has_receipt: false,
                    items: [
                        { item_name: '', expense_category_id: defaultCat, quantity: 1, unit_rate: '', note: '' }
                    ]
                };
                window.dispatchEvent(new CustomEvent('date-selected', { detail: this.form.expense_date }));
                this.isModalOpen = true;
            },

            openEditModal(expense) {
                this.isEditMode = true;
                this.activeDropdown = null;

                let initialItems = [];
                if (expense.items && expense.items.length > 0) {
                    initialItems = expense.items.map(it => ({
                        item_name: it.item_name || '',
                        expense_category_id: it.expense_category_id || expense.expense_category_id || (this.categoriesList[0]?.id || ''),
                        quantity: parseFloat(it.quantity) || 1,
                        unit_rate: parseFloat(it.unit_rate) || 0,
                        note: it.note || ''
                    }));
                } else {
                    initialItems = [{
                        item_name: expense.note || (expense.category?.name || 'General Expense'),
                        expense_category_id: expense.expense_category_id || (this.categoriesList[0]?.id || ''),
                        quantity: 1,
                        unit_rate: parseFloat(expense.amount) || 0,
                        note: ''
                    }];
                }

                this.isCustomRecipient = !expense.vendor_id && Boolean(expense.paid_to);

                this.form = {
                    id: expense.id,
                    voucher_no: expense.voucher_no,
                    expense_category_id: expense.expense_category_id || (initialItems[0]?.expense_category_id || ''),
                    vendor_id: expense.vendor_id || '',
                    paid_to: expense.paid_to || '',
                    branch_id: expense.branch_id || '',
                    expense_date: expense.expense_date,
                    payment_method: expense.payment_method,
                    payment_type: expense.payment_status === 'Unpaid' ? 'unpaid' : (expense.payment_status === 'Partially Paid' ? 'partial' : 'full'),
                    initial_paid_amount: expense.paid_amount || '',
                    due_date: expense.due_date || '',
                    reference_no: expense.reference_no || '',
                    note: expense.note || '',
                    edit_reason: '',
                    has_receipt: Boolean(expense.receipt_file),
                    items: initialItems
                };
                window.dispatchEvent(new CustomEvent('date-selected', { detail: expense.expense_date }));
                this.isModalOpen = true;
            },

            // Item actions
            addItem() {
                const prevCat = this.form.items.length > 0
                    ? (this.form.items[this.form.items.length - 1].expense_category_id || (this.categoriesList[0]?.id || ''))
                    : (this.categoriesList[0]?.id || '');
                this.form.items.push({ item_name: '', expense_category_id: prevCat, quantity: 1, unit_rate: '', note: '' });
            },

            removeItem(index) {
                if (this.form.items.length > 1) {
                    this.form.items.splice(index, 1);
                }
            },

            calculateLineTotal(item) {
                const qty = parseFloat(item.quantity) || 0;
                const rate = parseFloat(item.unit_rate) || 0;
                return Math.round(qty * rate * 100) / 100;
            },

            calculateVoucherTotal() {
                let sum = 0;
                for (const item of this.form.items) {
                    sum += this.calculateLineTotal(item);
                }
                return Math.round(sum * 100) / 100;
            },

            formatMoney(amount) {
                return (parseFloat(amount) || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            // Dropdown Helpers
            categoryText() {
                if (!this.form.expense_category_id) return 'Select Category';
                const found = this.categoriesList.find(c => c.id == this.form.expense_category_id);
                return found ? found.name : 'Select Category';
            },

            selectCategory(catId) {
                this.form.expense_category_id = catId;
                this.activeDropdown = null;
            },

            vendorText() {
                if (this.isCustomRecipient) {
                    return this.form.paid_to ? ('Custom: ' + this.form.paid_to) : 'Other / Custom Payee';
                }
                if (this.form.vendor_id) {
                    const found = this.vendorsList.find(v => v.id == this.form.vendor_id);
                    if (found) return found.name;
                }
                return this.form.paid_to || 'Select Vendor / Payee';
            },

            filteredVendors() {
                if (!this.vendorSearch.trim()) return this.vendorsList;
                const q = this.vendorSearch.toLowerCase();
                return this.vendorsList.filter(v =>
                    (v.name && v.name.toLowerCase().includes(q)) ||
                    (v.phone && v.phone.toLowerCase().includes(q))
                );
            },

            selectVendor(ven) {
                this.form.vendor_id = ven.id;
                this.form.paid_to = ven.name;
                this.isCustomRecipient = false;
                this.activeDropdown = null;
            },

            enableCustomRecipient() {
                this.isCustomRecipient = true;
                this.form.vendor_id = '';
                this.activeDropdown = null;
            },

            branchText() {
                if (!this.form.branch_id) return 'Main Branch / General';
                const branchEl = document.querySelector(`[data-branch-id="${this.form.branch_id}"]`);
                return branchEl ? branchEl.innerText : 'Main Branch / General';
            },

            selectBranch(id, name) {
                this.form.branch_id = id;
                this.activeDropdown = null;
            },

            // Quick Category Modal
            openQuickCategoryModal(rowIndex = null) {
                this.categoryTargetIndex = rowIndex;
                this.categoryForm = { name: '', description: '' };
                this.isCategoryModalOpen = true;
            },

            async saveQuickCategory() {
                if (!this.categoryForm.name.trim()) {
                    await showAlert('Please enter a category name.', 'Validation Error');
                    return;
                }

                this.isCategorySubmitting = true;
                try {
                    const res = await fetch('{{ route('accounts.categories.store_ajax') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            name: this.categoryForm.name,
                            description: this.categoryForm.description
                        })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.categoriesList.push(data.category);
                        if (this.categoryTargetIndex !== null && this.form.items[this.categoryTargetIndex]) {
                            this.form.items[this.categoryTargetIndex].expense_category_id = data.category.id;
                        } else if (this.form.items.length > 0) {
                            this.form.items[this.form.items.length - 1].expense_category_id = data.category.id;
                        }
                        this.form.expense_category_id = data.category.id;
                        this.isCategoryModalOpen = false;
                        await showAlert('Category created and selected for item row.', 'Success');
                    } else {
                        const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Failed to create category.');
                        await showAlert(errMsg, 'Error');
                    }
                } catch (e) {
                    await showAlert('Network or server error while creating category.', 'Error');
                } finally {
                    this.isCategorySubmitting = false;
                }
            },

            // Quick Vendor Modal
            openQuickVendorModal() {
                this.activeDropdown = null;
                this.vendorForm = { name: '', contact_person: '', phone: '', address: '' };
                this.isVendorModalOpen = true;
            },

            async saveQuickVendor() {
                if (!this.vendorForm.name.trim()) {
                    await showAlert('Please enter a vendor or shop name.', 'Validation Error');
                    return;
                }

                this.isVendorSubmitting = true;
                try {
                    const res = await fetch('{{ route('accounts.vendors.store_ajax') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            name: this.vendorForm.name,
                            contact_person: this.vendorForm.contact_person,
                            phone: this.vendorForm.phone,
                            address: this.vendorForm.address
                        })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.vendorsList.push(data.vendor);
                        this.selectVendor(data.vendor);
                        this.isVendorModalOpen = false;
                        await showAlert('Vendor registered and selected.', 'Success');
                    } else {
                        const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Failed to register vendor.');
                        await showAlert(errMsg, 'Error');
                    }
                } catch (e) {
                    await showAlert('Network or server error while registering vendor.', 'Error');
                } finally {
                    this.isVendorSubmitting = false;
                }
            }
        };
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

    async function handleExpenseDelete(event, voucherNo, amount) {
        event.preventDefault();
        const form = event.currentTarget;

        const confirmed = await showDanger(
            'Delete Expense Voucher',
            `Are you sure you want to delete voucher ${voucherNo} for ${amount}? This will permanently remove the record and log an audit snapshot.`
        );

        if (confirmed) {
            form.submit();
        }
        return false;
    }
</script>
@endpush
