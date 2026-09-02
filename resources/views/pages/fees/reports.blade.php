@extends('tyro-dashboard::layouts.admin')

@section('title', 'Financial Reports')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Table padding override to align with MACS Design guidelines */
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
    
    /* Tab animation */
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.25s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="{ activeTab: 'collection' }">
    
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                </svg>
                Financial Reports
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Comprehensive fee collection history, defaulters summary, and monthly due analysis</p>
        </div>

        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('fees.payments.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Receipts List
            </a>
            <a href="{{ route('fees.collection.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Collect Fees
            </a>
        </div>
    </div>

    @php
        $selectedBranch = $branches->firstWhere('id', request('branch_id'));
        $selectedBranchName = $selectedBranch ? ($selectedBranch->branch_name ?? $selectedBranch->name) : 'All Branches';

        $selectedClass = $classes->firstWhere('id', request('class_id'));
        $selectedClassName = $selectedClass ? $selectedClass->class_name : 'All Classes';
    @endphp

    <!-- Filter Card (Rule 7 Dropdowns & Rule 10 DatePickers) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 mb-8 relative z-20 no-print shadow-sm hover:shadow-md transition-all duration-300" x-data="reportFilter()">
        <form action="{{ route('fees.reports.index') }}" method="GET" id="reportFilterForm">
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="class_id" :value="form.class_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                
                <!-- Branch Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">Branch</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                            <button type="button" @click="selectBranch('{{ $b->id }}', '{{ $b->branch_name ?? $b->name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == '{{ $b->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $b->branch_name ?? $b->name }}</span>
                                <template x-if="form.branch_id == '{{ $b->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Class Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">Filter by Class</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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

                <!-- From Date Picker (Rule 10) -->
                <div class="relative" x-data="datePicker('{{ $startDate }}')" @click.away="show = false">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">From Date</label>
                    <input type="hidden" name="start_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                    </div>
                </div>

                <!-- To Date Picker (Rule 10) -->
                <div class="relative" x-data="datePicker('{{ $endDate }}')" @click.away="show = false">
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 dark:text-gray-400 uppercase mb-1.5 ml-1">To Date</label>
                    <input type="hidden" name="end_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex gap-2">
                    <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center whitespace-nowrap active:scale-95">Generate Report</button>
                    <a href="{{ route('fees.reports.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-500 dark:text-gray-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap">Reset</a>
                </div>

            </div>
        </form>
    </div>

    <!-- Aggregate Stats Dashboard Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total Collection Card -->
        <div class="bg-gradient-to-br from-themeGreen to-green-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden flex flex-col justify-between">
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <p class="text-green-100 font-bold uppercase tracking-wider text-[10px] mb-1">Total Collection</p>
                <h3 class="text-3xl font-black font-mono tracking-tight">৳ {{ number_format($totalCollected, 2) }}</h3>
            </div>
            <div class="flex items-center justify-between text-[11px] text-green-200 mt-4 pt-3 border-t border-white/10 font-medium">
                <span>{{ $totalCollectionCount }} Receipt(s)</span>
                <span>{{ $uniquePayingStudentsCount }} Student(s)</span>
            </div>
        </div>

        <!-- Total Pending Dues Card -->
        <div class="bg-gradient-to-br from-red-600 to-red-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden flex flex-col justify-between">
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <p class="text-red-100 font-bold uppercase tracking-wider text-[10px] mb-1">Total Pending Dues</p>
                <h3 class="text-3xl font-black font-mono tracking-tight">৳ {{ number_format($totalDue, 2) }}</h3>
            </div>
            <div class="flex items-center justify-between text-[11px] text-red-200 mt-4 pt-3 border-t border-white/10 font-medium">
                <span>{{ $uniqueDefaulterStudentsCount }} Defaulter(s)</span>
                <span>{{ $totalDueInvoicesCount }} Invoice(s)</span>
            </div>
        </div>

        <!-- Collection Method Breakdown Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <h4 class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-3">Payment Method Breakdown</h4>
            <div class="grid grid-cols-2 gap-2 w-full">
                @forelse($methodBreakdown as $method => $amount)
                <div class="bg-gray-50/70 dark:bg-themeDark/60 p-2.5 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                    <p class="text-[9px] font-black text-gray-450 dark:text-gray-500 uppercase tracking-wider">{{ $method }}</p>
                    <h5 class="text-xs font-black text-themeBlue font-mono mt-0.5">৳ {{ number_format($amount, 0) }}</h5>
                </div>
                @empty
                <p class="text-xs text-gray-400 font-semibold col-span-2 py-2">No collections yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Due Months Summary Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <h4 class="text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-3">Due Months Summary</h4>
            <div class="space-y-1.5 max-h-28 overflow-y-auto pr-1">
                @forelse($dueMonthBreakdown as $mName => $mStat)
                <div class="flex items-center justify-between text-xs py-1 border-b border-gray-50 dark:border-white/[0.03]">
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ $mName }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-semibold text-gray-400">{{ $mStat->students_count }} st.</span>
                        <span class="font-mono font-black text-red-600 dark:text-red-400">৳ {{ number_format($mStat->amount, 0) }}</span>
                    </div>
                </div>
                @empty
                <p class="text-xs text-themeGreen font-bold py-2">No pending due months!</p>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Segmented Tab Switcher -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6 no-print">
        <div class="flex gap-2 p-1.5 bg-gray-50/70 dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-2xl w-fit">
            <button @click="activeTab = 'collection'" class="h-10 px-5 text-xs rounded-xl transition-all uppercase tracking-wider flex items-center justify-center gap-2" :class="activeTab === 'collection' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : 'text-gray-500 dark:text-gray-450 font-bold hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Collection History (<span class="font-mono">{{ $totalCollectionCount }}</span>)
            </button>
            <button @click="activeTab = 'dues'" class="h-10 px-5 text-xs rounded-xl transition-all uppercase tracking-wider flex items-center justify-center gap-2" :class="activeTab === 'dues' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : 'text-gray-500 dark:text-gray-450 font-bold hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Defaulters List (<span class="font-mono">{{ $uniqueDefaulterStudentsCount }}</span>)
            </button>
        </div>

        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 bg-white dark:bg-themeNavy px-4 py-2.5 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm">
            Filtered: <span class="text-themeBlue font-black">{{ $selectedClassName }}</span> • <span class="text-gray-700 dark:text-gray-200 font-black">{{ date('d M Y', strtotime($startDate)) }} to {{ date('d M Y', strtotime($endDate)) }}</span>
        </div>
    </div>

    <!-- Data Tables Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-12">
        
        <!-- Collection History Tab -->
        <div id="tab-collection" class="tab-content" :class="{ 'active': activeTab === 'collection' }">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-14 text-center">#</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Receipt & Date</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student Info</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Class & Roll</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Fee Details & Month</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Method</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Paid Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                        @forelse($payments as $index => $pay)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <td class="py-0 px-0 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">{{ $index + 1 }}</td>
                            <td class="py-0 px-0">
                                <div class="font-mono font-black text-themeBlue text-xs">{{ $pay->receipt_no }}</div>
                                <div class="text-[10px] text-gray-500 font-semibold mt-0.5">{{ date('d M Y, h:i A', strtotime($pay->payment_date ?? $pay->created_at)) }}</div>
                            </td>
                            <td class="py-0 px-0">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $pay->student->student_name ?? 'N/A' }}</div>
                                <div class="text-[10px] font-semibold text-gray-450 mt-0.5">{{ $pay->student->student_identity ?? 'N/A' }}</div>
                            </td>
                            <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400">
                                {{ $pay->student->schoolClass->class_name ?? 'N/A' }}
                                <div class="text-[10px] font-semibold text-gray-450 mt-0.5">Roll: {{ $pay->student->roll_number ?? 'N/A' }}</div>
                            </td>
                            <td class="py-0 px-0 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <div class="font-bold text-gray-900 dark:text-gray-100">{{ $pay->invoice->feeSetup->category->name ?? 'Fee' }}</div>
                                @php
                                    $mName = 'One Time';
                                    if ($pay->invoice && $pay->invoice->feeSetup) {
                                        if ($pay->invoice->feeSetup->fee_month && !in_array(strtolower($pay->invoice->feeSetup->fee_month), ['monthly', 'one time', 'one_time'])) {
                                            $mName = $pay->invoice->feeSetup->fee_month;
                                        } elseif ($pay->invoice->due_date) {
                                            $mName = date('F', strtotime($pay->invoice->due_date));
                                        }
                                    }
                                @endphp
                                <span class="px-2 py-0.5 bg-gray-50 dark:bg-themeDark border border-gray-150 dark:border-white/[0.06] text-themeBlue dark:text-themeBlue text-[9px] font-black uppercase tracking-wider rounded-lg inline-block mt-0.5">{{ $mName }}</span>
                            </td>
                            <td class="py-0 px-0 text-center">
                                <span class="px-2 py-0.5 text-[10px] font-black rounded-md border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-themeDark/45">{{ $pay->payment_method }}</span>
                            </td>
                            <td class="py-0 px-0 text-right font-black text-themeGreen dark:text-themeGreen text-sm font-mono">
                                ৳ {{ number_format($pay->paid_amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No collections found in this date range.</td></tr>
                        @endforelse
                    </tbody>
                    
                    <!-- Table Summary Footer (Aggregate Collections) -->
                    @if($payments->isNotEmpty())
                    <tfoot>
                        <tr class="bg-gray-50/80 dark:bg-themeNavy/80 border-t-2 border-gray-200 dark:border-white/[0.1] font-black">
                            <td colspan="4" class="py-3 px-4 text-xs uppercase tracking-widest text-gray-600 dark:text-gray-300">
                                Total Collection Summary: <span class="text-themeBlue font-mono">{{ $totalCollectionCount }}</span> Receipts • <span class="text-themeBlue font-mono">{{ $uniquePayingStudentsCount }}</span> Students
                            </td>
                            <td colspan="2" class="py-3 px-4 text-right text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                Grand Total Collected:
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-base font-black text-themeGreen dark:text-green-400">
                                ৳ {{ number_format($totalCollected, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Defaulters List Tab (Student-Wise with Total Due Months & Month Names) -->
        <div id="tab-dues" class="tab-content" :class="{ 'active': activeTab === 'dues' }">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-14 text-center">#</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student Info</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Class & Roll</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Total Due Months & Month Names</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Fee Types</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Invoices</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-[0.2em] text-right">Total Due Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                        @forelse($defaulters as $index => $def)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <td class="py-0 px-0 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">{{ $index + 1 }}</td>
                            <td class="py-0 px-0">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $def->student->student_name ?? 'N/A' }}</div>
                                <div class="text-[10px] font-semibold text-gray-450 mt-0.5">ID: {{ $def->student->student_identity ?? 'N/A' }} @if($def->student && $def->student->phone) • {{ $def->student->phone }} @endif</div>
                            </td>
                            <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400">
                                {{ $def->student->schoolClass->class_name ?? 'N/A' }}
                                <div class="text-[10px] font-semibold text-gray-450 mt-0.5">Roll: {{ $def->student->roll_number ?? 'N/A' }}</div>
                            </td>
                            <td class="py-0 px-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2.5 py-1 text-[10px] font-black bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300 rounded-lg">
                                        {{ $def->due_months_count }} {{ Str::plural('Month', $def->due_months_count) }} Due
                                    </span>
                                    @foreach($def->due_months as $monthName)
                                        <span class="inline-block px-2 py-0.5 text-[9px] font-bold bg-gray-100 dark:bg-themeDark border border-gray-200 dark:border-white/[0.08] text-gray-700 dark:text-gray-300 rounded-md">
                                            {{ $monthName }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400">
                                {{ $def->categories->join(', ') }}
                            </td>
                            <td class="py-0 px-0 text-center">
                                <span class="inline-block px-2.5 py-1 text-[10px] font-mono font-bold bg-gray-50 dark:bg-themeDark border border-gray-150 dark:border-white/[0.08] rounded-lg text-gray-700 dark:text-gray-300">
                                    {{ $def->total_invoices }} {{ Str::plural('Inv', $def->total_invoices) }}
                                </span>
                            </td>
                            <td class="py-0 px-0 text-right font-black text-red-600 dark:text-red-400 text-sm font-mono">
                                ৳ {{ number_format($def->total_due, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="py-12 text-center text-themeGreen font-black text-sm uppercase tracking-wider">Awesome! No pending defaulters found for this class and date range.</td></tr>
                        @endforelse
                    </tbody>

                    <!-- Table Summary Footer (Aggregate Defaulters Dues) -->
                    @if($defaulters->isNotEmpty())
                    <tfoot>
                        <tr class="bg-gray-50/80 dark:bg-themeNavy/80 border-t-2 border-gray-200 dark:border-white/[0.1] font-black">
                            <td colspan="4" class="py-3 px-4 text-xs uppercase tracking-widest text-gray-600 dark:text-gray-300">
                                Defaulter Summary: <span class="text-red-600 font-mono">{{ $uniqueDefaulterStudentsCount }}</span> Students • <span class="text-red-600 font-mono">{{ $totalDueInvoicesCount }}</span> Unpaid Invoices
                            </td>
                            <td colspan="2" class="py-3 px-4 text-right text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                Grand Total Due:
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-base font-black text-red-600 dark:text-red-400">
                                ৳ {{ number_format($totalDue, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Custom Alpine Report Filter Dropdown Controller
    function reportFilter() {
        return {
            activeDropdown: null,
            branchText: '{{ $selectedBranchName }}',
            classText: '{{ $selectedClassName }}',
            
            form: {
                branch_id: '{{ request('branch_id') }}',
                class_id: '{{ request('class_id') }}'
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
</script>
@endpush