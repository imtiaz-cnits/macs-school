@extends('tyro-dashboard::layouts.admin')

@section('title', 'Payment Receipts')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Custom styles override for borderless tighter table rows */
    .table th, .table td { 
        padding: 0.625rem 1rem !important; 
    }
</style>
@endpush

@section('content')
<div id="paymentsIndexRoot" class="w-full min-h-screen" x-data="{ loading: false }" @trigger-loader.window="loading = true">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Payment Receipts
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">
                View, filter, and print student payment receipts
            </p>
        </div>
        
        <!-- Shortcut Action Buttons at Header Top-Right -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('fees.collection.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Collect Fees
            </a>
            <a href="{{ route('fees.reports.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Reports
            </a>
        </div>
    </div>

    @php
        $selectedBranch = $branches->firstWhere('id', request('branch_id'));
        $selectedBranchName = $selectedBranch ? $selectedBranch->branch_name : 'All Branches';

        $selectedClass = $classes->firstWhere('id', request('class_id'));
        $selectedClassName = $selectedClass ? $selectedClass->class_name : 'All Classes';

        $methods = [
            'CASH' => 'Cash',
            'BANK' => 'Bank',
            'BKASH' => 'bKash',
            'NAGAD' => 'Nagad',
            'ROCKET' => 'Rocket'
        ];
        $selectedMethodName = isset($methods[request('payment_method')]) ? $methods[request('payment_method')] : 'All Methods';
    @endphp

    <!-- Filters Card (Using Custom Alpine Dropdowns & DatePickers) -->
    <div class="mb-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm no-print" x-data="paymentReceiptsFilter()">
        <form action="{{ route('fees.payments.index') }}" method="GET" class="space-y-4" @submit="window.dispatchEvent(new CustomEvent('trigger-loader'))">
            
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="payment_method" :value="form.payment_method">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                
                <!-- Branch Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">Branch</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="branchText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectBranch('', 'All Branches')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>All Branches</span>
                            <template x-if="form.branch_id === ''">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        @foreach($branches as $b)
                            <button type="button" @click="selectBranch('{{ $b->id }}', '{{ $b->branch_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == '{{ $b->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $b->branch_name }}</span>
                                <template x-if="form.branch_id == '{{ $b->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Class Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">Class</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="classText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectClass('', 'All Classes')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>All Classes</span>
                            <template x-if="form.class_id === ''">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        @foreach($classes as $c)
                            <button type="button" @click="selectClass('{{ $c->id }}', '{{ $c->class_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id == '{{ $c->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $c->class_name }}</span>
                                <template x-if="form.class_id == '{{ $c->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Date From Picker (Rule 10) -->
                <div class="relative" x-data="datePicker('{{ request('date_from') }}')" @click.away="show = false">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">Date From</label>
                    <input type="hidden" name="date_from" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute left-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between mb-3 border-b border-gray-100 dark:border-white/[0.04] pb-2">
                            <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            
                            <div class="flex items-center gap-1.5">
                                <!-- Month Dropdown -->
                                <div class="relative" x-data="{ mOpen: false }">
                                    <button type="button" @click="mOpen = !mOpen" class="flex items-center gap-0.5 px-2 py-1 bg-gray-50/50 dark:bg-themeDark border border-gray-150 dark:border-gray-800 rounded-lg text-xs font-black text-gray-700 dark:text-gray-200 hover:text-themeBlue transition-all">
                                        <span x-text="monthNames[currentMonth]"></span>
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="mOpen" x-cloak @click.away="mOpen = false" class="absolute left-1/2 -translate-x-1/2 z-50 mt-1 w-28 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-xl shadow-xl py-1 max-h-48 overflow-y-auto" x-transition>
                                        <template x-for="(mName, idx) in monthNames" :key="idx">
                                            <button type="button" @click="currentMonth = idx; generateCalendar(); mOpen = false" class="w-full text-center px-3 py-1.5 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="currentMonth === idx ? 'text-themeBlue font-black bg-indigo-50 dark:bg-themeBlue/10' : 'text-gray-700 dark:text-gray-200'">
                                                <span x-text="mName"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Year Input -->
                                <input type="number" x-model="currentYear" @input="generateCalendar()" class="w-16 h-[26px] text-center text-xs font-black text-gray-800 dark:text-gray-200 bg-gray-50/50 dark:bg-themeDark border border-gray-150 dark:border-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-themeBlue/15 focus:border-themeBlue transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" min="1900" max="2100">
                            </div>
                            
                            <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        
                        <!-- Days header -->
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
                        
                        <!-- Days Grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(d, i) in days" :key="i">
                                <button type="button" @click="selectDay(d.day)" 
                                        class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all"
                                        :class="d.day === parseInt((value || '').split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                        :disabled="!d.isCurrentMonth">
                                    <span x-text="d.day"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Clear Date Button -->
                        <template x-if="value">
                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-white/[0.04] text-center">
                                <button type="button" @click="value = ''; show = false;" class="text-[10px] font-bold text-red-500 hover:underline">Clear Date</button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Date To Picker (Rule 10) -->
                <div class="relative" x-data="datePicker('{{ request('date_to') }}')" @click.away="show = false">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">Date To</label>
                    <input type="hidden" name="date_to" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute left-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between mb-3 border-b border-gray-100 dark:border-white/[0.04] pb-2">
                            <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            
                            <div class="flex items-center gap-1.5">
                                <!-- Month Dropdown -->
                                <div class="relative" x-data="{ mOpen: false }">
                                    <button type="button" @click="mOpen = !mOpen" class="flex items-center gap-0.5 px-2 py-1 bg-gray-50/50 dark:bg-themeDark border border-gray-150 dark:border-gray-800 rounded-lg text-xs font-black text-gray-700 dark:text-gray-200 hover:text-themeBlue transition-all">
                                        <span x-text="monthNames[currentMonth]"></span>
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="mOpen" x-cloak @click.away="mOpen = false" class="absolute left-1/2 -translate-x-1/2 z-50 mt-1 w-28 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-xl shadow-xl py-1 max-h-48 overflow-y-auto" x-transition>
                                        <template x-for="(mName, idx) in monthNames" :key="idx">
                                            <button type="button" @click="currentMonth = idx; generateCalendar(); mOpen = false" class="w-full text-center px-3 py-1.5 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="currentMonth === idx ? 'text-themeBlue font-black bg-indigo-50 dark:bg-themeBlue/10' : 'text-gray-700 dark:text-gray-200'">
                                                <span x-text="mName"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Year Input -->
                                <input type="number" x-model="currentYear" @input="generateCalendar()" class="w-16 h-[26px] text-center text-xs font-black text-gray-800 dark:text-gray-200 bg-gray-50/50 dark:bg-themeDark border border-gray-150 dark:border-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-themeBlue/15 focus:border-themeBlue transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" min="1900" max="2100">
                            </div>
                            
                            <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        
                        <!-- Days header -->
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
                        
                        <!-- Days Grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(d, i) in days" :key="i">
                                <button type="button" @click="selectDay(d.day)" 
                                        class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all"
                                        :class="d.day === parseInt((value || '').split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                        :disabled="!d.isCurrentMonth">
                                    <span x-text="d.day"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Clear Date Button -->
                        <template x-if="value">
                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-white/[0.04] text-center">
                                <button type="button" @click="value = ''; show = false;" class="text-[10px] font-bold text-red-500 hover:underline">Clear Date</button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Payment Method Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'method') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-455 dark:text-gray-400 uppercase mb-1.5 ml-1">Payment Method</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'method' ? null : 'method'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="methodText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'method'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectMethod('', 'All Methods')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.payment_method === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>All Methods</span>
                            <template x-if="form.payment_method === ''">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        @foreach($methods as $key => $name)
                            <button type="button" @click="selectMethod('{{ $key }}', '{{ $name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.payment_method == '{{ $key }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $name }}</span>
                                <template x-if="form.payment_method == '{{ $key }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Search Query & Actions -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <input type="text" name="search_query" value="{{ request('search_query') }}" placeholder="Search Student ID, Name, or Receipt No (e.g. REC-...)" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-250 px-3.5 placeholder-gray-450">
                <div class="flex gap-2">
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">Filter Receipts</button>
                    <a href="{{ route('fees.payments.index') }}" class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-500 dark:text-gray-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap" @click="window.dispatchEvent(new CustomEvent('trigger-loader'))">Reset</a>
                </div>
            </div>

        </form>
    </div>

    @if(session('success')) <div class="bg-green-50 dark:bg-themeGreen/10 text-themeGreen p-4 rounded-2xl mb-6 font-bold border border-green-200/30">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 p-4 rounded-2xl mb-6 font-bold border border-red-200/30">{{ session('error') }}</div> @endif

    <!-- Main Listing Panel (Hidden when loading) -->
    <div x-show="!loading" x-data="{
        selectedReceipts: [],
        toggleAll(checked) {
            if (checked) {
                this.selectedReceipts = Array.from(document.querySelectorAll('.receipt-checkbox')).map(el => el.value);
            } else {
                this.selectedReceipts = [];
            }
        }
    }">
        <form action="{{ route('fees.payments.print_selected') }}" method="POST" target="_blank" class="w-full">
            @csrf
            
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm overflow-hidden mb-6">
                @if($payments->isEmpty())
                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 font-bold">
                        No receipts found matching the filter options.
                    </div>
                @else
                    <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                        <table class="w-full text-left border-collapse table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 w-12 text-center">
                                        <input type="checkbox" @change="toggleAll($el.checked)" class="rounded border-gray-300 text-themeBlue focus:ring-themeBlue/20 h-4 w-4">
                                    </th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Receipt No</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Class & Roll</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Description</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Month</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Paid</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Method</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                                @foreach($payments as $pay)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0 text-center">
                                            <input type="checkbox" name="payment_ids[]" value="{{ $pay->id }}" x-model="selectedReceipts" class="receipt-checkbox rounded border-gray-300 text-themeBlue focus:ring-themeBlue/20 h-4 w-4">
                                        </td>
                                        <td class="py-0 px-0 font-mono font-black text-gray-700 dark:text-gray-300 text-xs">{{ $pay->receipt_no }}</td>
                                        <td class="py-0 px-0">
                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $pay->student->student_name ?? 'N/A' }}</div>
                                            <div class="text-[10px] font-semibold text-gray-450 mt-0.5">{{ $pay->student->student_identity ?? 'N/A' }}</div>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400">
                                            {{ $pay->student->schoolClass->class_name ?? 'N/A' }}
                                            <div class="text-[10px] font-semibold text-gray-450 mt-0.5">Roll: {{ $pay->student->roll_number ?? 'N/A' }}</div>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400">
                                            {{ $pay->invoice->feeSetup->category->name ?? 'Fee Payment' }}
                                        </td>
                                        @php
                                            $monthName = 'One Time';
                                            if ($pay->invoice && $pay->invoice->feeSetup) {
                                                if ($pay->invoice->feeSetup->fee_month && strtolower($pay->invoice->feeSetup->fee_month) !== 'monthly') {
                                                    $monthName = $pay->invoice->feeSetup->fee_month;
                                                } elseif ($pay->invoice->due_date) {
                                                    $monthName = date('F', strtotime($pay->invoice->due_date));
                                                } else {
                                                    $monthName = date('F', strtotime($pay->invoice->created_at));
                                                }
                                            }
                                        @endphp
                                        <td class="py-0 px-0 text-sm font-semibold text-gray-600 dark:text-gray-400">
                                            {{ $monthName }}
                                        </td>
                                        <td class="py-0 px-0 text-sm font-mono font-black text-right text-green-700 dark:text-themeGreen">৳{{ number_format($pay->paid_amount, 2) }}</td>
                                        <td class="py-0 px-0 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ date('d-M-Y', strtotime($pay->payment_date)) }}</td>
                                        <td class="py-0 px-0">
                                            <span class="px-2 py-0.5 text-[10px] font-black rounded-md border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-themeDark/45">{{ $pay->payment_method }}</span>
                                        </td>
                                        <td class="py-0 px-0">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('fees.invoice.pos_print', $pay->fee_invoice_id) }}" target="_blank" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue flex items-center justify-center h-8 w-8 border border-gray-150 dark:border-white/[0.08] rounded-lg transition-all" title="Print Receipt">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Custom pagination formatting -->
            @if($payments->isNotEmpty())
                <div class="mt-4 no-print flex justify-center">
                    {{ $payments->links('pagination::tailwind') }}
                </div>
            @endif

            <!-- Floating Checkout / Batch Action Bar -->
            <div x-show="selectedReceipts.length > 0"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-y-10 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-y-0 opacity-100"
                 x-transition:leave-end="translate-y-10 opacity-0"
                 class="sticky bottom-4 w-full bg-white dark:bg-themeNavy border-2 border-themeBlue/30 rounded-3xl p-4 shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-4 z-50 transition-all mt-6 no-print">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-themeBlue/10 rounded-2xl flex items-center justify-center text-themeBlue">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-gray-900 dark:text-white">Batch Action Selected</h4>
                        <p class="text-xs font-semibold text-gray-500 mt-0.5"><span x-text="selectedReceipts.length" class="text-themeBlue font-black"></span> receipt(s) selected for printing.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print Selected
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Skeleton Loader (Pulsing placeholder rows) -->
    <div x-show="loading" class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm no-print">
        <div class="animate-pulse space-y-6">
            <div class="h-4 w-48 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
            <div class="border-b border-gray-100 dark:border-white/[0.06] pb-4"></div>
            <div class="space-y-4">
                @for($i = 0; $i < 6; $i++)
                    <div class="flex items-center justify-between gap-4">
                        <div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-40 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                    </div>
                    <div class="border-b border-gray-50 dark:border-white/[0.02] pb-2"></div>
                @endfor
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Custom Alpine Filter Dropdown Controller
    function paymentReceiptsFilter() {
        return {
            activeDropdown: null,
            branchText: '{{ $selectedBranchName }}',
            classText: '{{ $selectedClassName }}',
            methodText: '{{ $selectedMethodName }}',
            
            form: {
                branch_id: '{{ request('branch_id') }}',
                class_id: '{{ request('class_id') }}',
                payment_method: '{{ request('payment_method') }}'
            },
            
            selectBranch(id, name) {
                this.form.branch_id = id;
                this.branchText = name;
                this.activeDropdown = null;
            },
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.activeDropdown = null;
            },
            selectMethod(code, name) {
                this.form.payment_method = code;
                this.methodText = name;
                this.activeDropdown = null;
            }
        };
    }

    // Custom Date Picker component data definition (Rule 10)
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue || '',
            currentYear: new Date(initialValue || new Date()).getFullYear(),
            currentMonth: new Date(initialValue || new Date()).getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            
            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        const d = new Date(val);
                        if (!isNaN(d.getTime())) {
                            this.currentYear = d.getFullYear();
                            this.currentMonth = d.getMonth();
                            this.generateCalendar();
                        }
                    }
                });
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
                this.$dispatch('date-selected', this.value);
            },
            
            formatDisplay(val) {
                if (!val) return 'Select Date';
                const d = new Date(val);
                if (isNaN(d.getTime())) return 'Select Date';
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        };
    }

    // Global pagination link listener to show loader on page shift
    document.addEventListener('DOMContentLoaded', () => {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('trigger-loader'));
            });
        });
    });
</script>
@endpush
