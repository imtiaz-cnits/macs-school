@extends('tyro-dashboard::layouts.admin')

@section('title', 'Payment Receipts')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush

@section('content')
<div id="paymentsIndexRoot" class="w-full min-w-0 min-h-screen" x-data="{ loading: false }" @trigger-loader.window="loading = true">
    
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
        $statuses = [
            'Active' => 'Active',
            'Reversed' => 'Reversed',
        ];
        $selectedStatusName = isset($statuses[$statusFilter ?? request('status')]) ? $statuses[$statusFilter ?? request('status')] : 'All Statuses';
    @endphp

    @if(!empty($schemaWarning))
        <div class="mb-6 rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/90 dark:bg-amber-950/30 px-4 py-3 text-xs font-bold text-amber-800 dark:text-amber-300 flex items-center gap-3 no-print">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <span>{{ $schemaWarning }}</span>
        </div>
    @endif

    <!-- Filters Card (Using Custom Alpine Dropdowns & DatePickers) -->
    <div class="mb-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm no-print" x-data="paymentReceiptsFilter()">
        <form action="{{ route('fees.payments.index') }}" method="GET" class="space-y-4" @submit="window.dispatchEvent(new CustomEvent('trigger-loader'))">
            
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="payment_method" :value="form.payment_method">
            <input type="hidden" name="status" :value="form.status">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                
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

                <!-- Status Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'status') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-455 dark:text-gray-400 uppercase mb-1.5 ml-1">Status</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'status' ? null : 'status'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="statusText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'status'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        @foreach($statuses as $statusCode => $statusLabel)
                            <button type="button" @click="selectStatus('{{ $statusCode }}', '{{ $statusLabel }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.status == '{{ $statusCode }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $statusLabel }}</span>
                                <template x-if="form.status == '{{ $statusCode }}'">
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
            
            <div class="w-full min-w-0 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Payment Receipts Directory
                    </h3>
                    <span class="text-xs font-mono font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ number_format($payments->total()) }} receipt(s)
                    </span>
                </div>

                @if($payments->isEmpty())
                    <div class="py-12 px-6 text-center text-gray-400 dark:text-gray-500 font-bold text-sm">
                        No receipts found matching the filter options.
                    </div>
                @else
                    <div class="px-6 pb-3 pt-1">
                        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                            <table class="w-full text-left border-collapse table [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-2.5 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-2.5 [&>tbody>tr>td]:!px-2.5 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 w-9 text-center">
                                            <input type="checkbox" @change="toggleAll($el.checked)" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 accent-themeBlue focus:ring-themeBlue cursor-pointer">
                                        </th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Receipt No</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Student</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Class &amp; Roll</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Description</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Month</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] text-right whitespace-nowrap">Paid</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Date</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Method</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Status</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] text-right whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $pay)
                                        @php
                                            $headChips = ($pay->prepared_is_admission && !empty($pay->prepared_head_chips)) ? $pay->prepared_head_chips : [];
                                            $headChipCount = count($headChips);
                                        @endphp
                                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                            <td class="py-0 px-0 text-center">
                                                <input type="checkbox" name="payment_ids[]" value="{{ $pay->id }}" x-model="selectedReceipts" class="receipt-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 accent-themeBlue focus:ring-themeBlue cursor-pointer">
                                            </td>
                                            <td class="py-0 px-0 font-mono font-black text-themeBlue text-xs whitespace-nowrap">{{ $pay->receipt_no }}</td>
                                            <td class="py-0 px-0 whitespace-nowrap">
                                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $pay->student->student_name ?? 'N/A' }}</div>
                                                <div class="text-[10px] font-mono font-semibold text-gray-400 dark:text-gray-500 mt-0.5 whitespace-nowrap">{{ $pay->student->student_identity ?? 'N/A' }}</div>
                                            </td>
                                            <td class="py-0 px-0 whitespace-nowrap">
                                                <div class="text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $pay->student->schoolClass->class_name ?? 'N/A' }}</div>
                                                <div class="text-[10px] font-mono font-semibold text-gray-400 dark:text-gray-500 mt-0.5 whitespace-nowrap">Roll: {{ $pay->student->roll_number ?? 'N/A' }}</div>
                                            </td>
                                            <td class="py-0 px-0 text-xs sm:text-sm font-bold text-gray-700 dark:text-gray-300" @if($headChipCount > 3) x-data="{ showHeads: false }" @endif>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="whitespace-nowrap">{{ $pay->prepared_description }}</span>
                                                    @if(!$pay->prepared_is_admission && bccomp((string) ($pay->prepared_fine_amount ?? '0'), '0.00', 2) > 0)
                                                        <span class="px-1.5 py-0.5 rounded-md bg-amber-50 dark:bg-amber-500/15 border border-amber-200 dark:border-amber-500/30 text-amber-700 dark:text-amber-300 text-[9px] font-black uppercase tracking-wider whitespace-nowrap">
                                                            + ৳{{ number_format((float) $pay->prepared_fine_amount, 2) }} Fine
                                                        </span>
                                                    @endif
                                                    @if($headChipCount > 3)
                                                        <button type="button"
                                                                @click="showHeads = !showHeads"
                                                                class="px-2 py-0.5 rounded-md bg-sky-50 dark:bg-themeBlue/15 text-themeBlue border border-sky-200/70 dark:border-themeBlue/30 text-[10px] font-black uppercase tracking-wider hover:bg-themeBlue hover:text-white transition-all whitespace-nowrap">
                                                            <span x-text="showHeads ? 'Hide {{ $headChipCount }} heads' : '{{ $headChipCount }} heads'"></span>
                                                        </button>
                                                    @endif
                                                </div>
                                                @if($headChipCount > 0 && $headChipCount <= 3)
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        @foreach($headChips as $chip)
                                                            <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded-md bg-gray-50 dark:bg-themeDark/50 border border-gray-150 dark:border-white/[0.08] text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                                                {{ $chip['head_name'] }}: ৳{{ $chip['amount_formatted'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @elseif($headChipCount > 3)
                                                    <div x-show="showHeads" x-cloak class="mt-1.5 flex flex-wrap gap-1 max-w-md">
                                                        @foreach($headChips as $chip)
                                                            <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded-md bg-gray-50 dark:bg-themeDark/50 border border-gray-150 dark:border-white/[0.08] text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                                                {{ $chip['head_name'] }}: ৳{{ $chip['amount_formatted'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if($pay->prepared_has_alloc_mismatch)
                                                    <div class="mt-1 text-[10px] font-bold text-amber-600 dark:text-amber-400">Reversal audit copy displayed</div>
                                                @endif
                                            </td>
                                            <td class="py-0 px-0 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-sky-50/70 dark:bg-themeDark border border-sky-100 dark:border-white/[0.06] text-themeBlue text-[10px] font-black uppercase tracking-wider whitespace-nowrap">{{ $pay->prepared_month }}</span>
                                            </td>
                                            <td class="py-0 px-0 text-sm font-mono font-black text-right whitespace-nowrap {{ $pay->prepared_is_reversed ? 'text-gray-400 line-through' : 'text-themeGreen' }}">৳{{ number_format($pay->paid_amount, 2) }}</td>
                                            <td class="py-0 px-0 text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ date('d-M-Y', strtotime($pay->payment_date)) }}</td>
                                            <td class="py-0 px-0 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-lg border border-gray-200 dark:border-white/[0.08] text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-themeDark/45 whitespace-nowrap">{{ $pay->payment_method }}</span>
                                            </td>
                                            <td class="py-0 px-0 whitespace-nowrap">
                                                @if($pay->prepared_is_reversed)
                                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200/70 dark:border-rose-500/30 whitespace-nowrap">
                                                        Reversed
                                                    </span>
                                                    @if($pay->prepared_reversal_ref)
                                                        <div class="text-[10px] font-mono font-bold text-gray-500 dark:text-gray-400 mt-0.5 whitespace-nowrap">{{ $pay->prepared_reversal_ref }}</div>
                                                    @endif
                                                    @if($pay->prepared_reversal_reason)
                                                        <div class="text-[10px] font-medium text-gray-400 mt-0.5 truncate max-w-[140px]" title="{{ $pay->prepared_reversal_reason }}">{{ $pay->prepared_reversal_reason }}</div>
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-lg bg-emerald-50 dark:bg-themeGreen/15 text-themeGreen border border-themeGreen/25 whitespace-nowrap">
                                                        Active
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-0 px-0 whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                                    <a href="{{ !empty($pay->prepared_is_reversed) ? route('fees.receipt.pos_print', $pay->receipt_no) : route('fees.invoice.pos_print', $pay->fee_invoice_id) }}" target="_blank" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue flex items-center justify-center h-8 w-8 border border-gray-200 dark:border-white/[0.08] rounded-lg transition-all" title="{{ !empty($pay->prepared_is_reversed) ? 'Print Void Receipt' : 'Print Receipt' }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                    </a>
                                                    @if(!empty($canReversePayments) && !$pay->prepared_is_reversed)
                                                        <button type="button"
                                                                data-payment-reversal="{{ json_encode([
                                                                    'payment_id' => $pay->id,
                                                                    'receipt_no' => $pay->receipt_no,
                                                                    'student_name' => $pay->student->student_name ?? 'N/A',
                                                                    'description' => $pay->prepared_description,
                                                                    'paid_amount' => number_format((float) $pay->paid_amount, 2, '.', ''),
                                                                    'payment_method' => $pay->payment_method,
                                                                    'reverse_url' => route('fees.payments.reverse', $pay->id),
                                                                ]) }}"
                                                                onclick="window.openReverseModalFromButton(this)"
                                                                class="action-btn text-red-600 hover:text-red-800 hover:border-red-600 flex items-center justify-center h-8 w-8 border border-gray-200 dark:border-white/[0.08] rounded-lg transition-all"
                                                                title="Reverse Payment">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"></path></svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- MACS School Styled Pagination Footer -->
                    @if($payments->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.06] bg-gray-50/40 dark:bg-themeDark/25 no-print">
                            {{ $payments->onEachSide(1)->links('partials.pagination') }}
                        </div>
                    @endif
                @endif
            </div>

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

    <!-- Reverse Payment Modal (Admin / Super Admin Only) -->
    @if(!empty($canReversePayments))
    <div id="reversePaymentModal"
         x-data="reversePaymentModalComponent()"
         x-show="isOpen"
         x-cloak
         @open-reverse-payment-modal.window="open($event.detail)"
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-themeDark/50 backdrop-blur-md p-4">
        <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-2xl border border-gray-100 dark:border-white/[0.08] overflow-hidden" @click.away="!isSubmitting && close()">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">Reverse Payment Receipt</h3>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Full reversal audit entry</p>
                </div>
                <button type="button" @click="!isSubmitting && close()" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submit()" class="p-6 space-y-4 text-gray-900 dark:text-white">
                <div class="bg-gray-50/70 dark:bg-themeDark/40 border border-gray-150 dark:border-white/[0.06] rounded-2xl p-4 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Receipt No:</span>
                        <span class="font-mono font-black text-gray-900 dark:text-white" x-text="receiptNo"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Student:</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200" x-text="studentName"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Fee Item:</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 truncate max-w-[200px]" x-text="description"></span>
                    </div>
                    <div class="flex justify-between pt-1 border-t border-gray-150 dark:border-white/[0.06]">
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Full Reversal Amount:</span>
                        <span class="font-mono font-black text-red-600 dark:text-red-400" x-text="'৳ ' + paidAmount"></span>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/80 dark:bg-amber-950/30 px-3.5 py-2.5 text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                    Reversals always restore the full receipt amount back onto pending invoice due balance and mark the receipt as Reversed in the audit ledger.
                </div>

                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1.5">
                        Reversal Reason <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="reason" rows="3" maxlength="255" placeholder="e.g. Wrong receipt amount entered or duplicate transaction"
                              class="w-full px-3.5 py-2.5 text-xs font-semibold rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 text-gray-900 dark:text-gray-100 transition-all"></textarea>
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="button" @click="!isSubmitting && close()" class="flex-1 h-11 px-5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                    <button type="submit" :disabled="isSubmitting" class="flex-1 h-11 px-5 bg-gradient-to-r from-red-600 to-rose-600 text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center active:scale-95 disabled:opacity-60">
                        <span x-text="isSubmitting ? 'Reversing...' : 'Reverse Payment'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

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
            statusText: '{{ $selectedStatusName }}',
            
            form: {
                branch_id: '{{ request('branch_id') }}',
                class_id: '{{ request('class_id') }}',
                payment_method: '{{ request('payment_method') }}',
                status: '{{ $statusFilter ?? request('status') }}'
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
            },
            selectStatus(code, name) {
                this.form.status = code;
                this.statusText = name;
                this.activeDropdown = null;
            }
        };
    }

    window.openReverseModalFromButton = function(buttonEl) {
        try {
            const raw = buttonEl.getAttribute('data-payment-reversal') || '{}';
            const detail = JSON.parse(raw);
            window.dispatchEvent(new CustomEvent('open-reverse-payment-modal', { detail }));
        } catch (err) {
            console.error(err);
            showAlert('Unable to load payment reversal details.', 'Modal Error');
        }
    };

    window.reversePaymentModalComponent = function() {
        const generateIdempotencyKey = () => {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }
            return 'rev-pay-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
        };

        return {
            isOpen: false,
            isSubmitting: false,
            paymentId: null,
            receiptNo: '',
            studentName: '',
            description: '',
            paidAmount: '0.00',
            reverseUrl: '',
            reason: '',
            idempotencyKey: '',

            open(detail) {
                this.paymentId = detail.payment_id || null;
                this.receiptNo = detail.receipt_no || '';
                this.studentName = detail.student_name || '';
                this.description = detail.description || '';
                this.paidAmount = detail.paid_amount || '0.00';
                this.reverseUrl = detail.reverse_url || '';
                this.reason = '';
                this.idempotencyKey = generateIdempotencyKey();
                this.isOpen = true;
            },

            close() {
                this.isOpen = false;
            },

            async submit() {
                if (this.isSubmitting || !this.reverseUrl) return;
                const trimmedReason = (this.reason || '').trim();
                if (trimmedReason.length < 3) {
                    await showAlert('Please enter a clear reversal reason (at least 3 characters).', 'Validation Error');
                    return;
                }

                const confirmed = await showDanger(
                    'Confirm Payment Reversal',
                    `Reverse full payment of ৳${this.paidAmount} for receipt ${this.receiptNo}?`
                );
                if (!confirmed) return;

                this.isSubmitting = true;
                try {
                    const response = await fetch(this.reverseUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            reason: trimmedReason,
                            idempotency_key: this.idempotencyKey,
                        }),
                    });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.close();
                        await showSuccess(data.message || 'Payment reversed successfully.');
                        window.dispatchEvent(new CustomEvent('trigger-loader'));
                        window.location.reload();
                    } else {
                        await showAlert(data.message || 'Unable to reverse payment.', 'Reversal Error');
                    }
                } catch (err) {
                    console.error(err);
                    await showAlert('Network or server error while reversing payment.', 'Reversal Error');
                } finally {
                    this.isSubmitting = false;
                }
            },
        };
    };

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
