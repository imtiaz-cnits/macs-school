@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance Summary Report')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<!-- Load Alpine.js to fix dropdown component issues -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Table padding override to align with MACS Design guidelines */
    .table th, .table td {
        padding: 0.875rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div x-data="attendanceReportPage()" x-init="init()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Attendance Report
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Pabna International School</p>
        </div>
        <div class="bg-themeGreen/10 px-5 py-2 rounded-xl border border-themeGreen/20 backdrop-blur-sm">
            <span class="text-xs font-black text-themeGreen uppercase tracking-widest">Live Report Center</span>
        </div>
    </div>

    <!-- Filters Panel Card -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm mb-8 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <!-- Branch -->
            <div class="relative" @click.away="activeDropdown === 'branch' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Branch</label>
                <button type="button" @click="toggleDropdown('branch')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="branchText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('branch_id', '', 'Select Branch')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                        Select Branch
                    </button>
                    <template x-for="item in branches" :key="item.id">
                        <button type="button" @click="selectValue('branch_id', item.id, item.branch_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.branch_name"></span>
                            <template x-if="form.branch_id === item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Session -->
            <div class="relative" @click.away="activeDropdown === 'session' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Session</label>
                <button type="button" @click="toggleDropdown('session')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="sessionText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('session_year_id', '', 'Select Session')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                        Select Session
                    </button>
                    <template x-for="item in sessions" :key="item.id">
                        <button type="button" @click="selectValue('session_year_id', item.id, item.session_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.session_name"></span>
                            <template x-if="form.session_year_id === item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Class -->
            <div class="relative" @click.away="activeDropdown === 'class' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class *</label>
                <button type="button" @click="toggleDropdown('class')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="classText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('class_id', '', 'Select Class')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                        Select Class
                    </button>
                    <template x-for="item in classes" :key="item.id">
                        <button type="button" @click="selectValue('class_id', item.id, item.class_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.class_name"></span>
                            <template x-if="form.class_id === item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Section -->
            <div class="relative" @click.away="activeDropdown === 'section' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Section</label>
                <button type="button" @click="toggleDropdown('section')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="sectionText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'section'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('section_id', '', 'Select Section')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                        Select Section
                    </button>
                    <template x-for="item in sections" :key="item.id">
                        <button type="button" @click="selectValue('section_id', item.id, item.section_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.section_name"></span>
                            <template x-if="form.section_id === item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Custom Date Picker Component -->
            <div class="relative" x-data="datePicker(form.attendance_date)" @date-selected.window="if($event.detail) form.attendance_date = $event.detail" @click.away="show = false">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Attendance Date</label>
                <button type="button" @click="show = !show" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="formatDisplay(value)"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                
                <!-- Calendar Dropdown panel -->
                <div x-show="show" x-cloak class="absolute right-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                    <div class="flex items-center justify-between mb-2">
                        <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider" x-text="monthNames[currentMonth] + ' ' + currentYear"></span>
                        <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    
                    <!-- Days header -->
                    <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">
                        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    
                    <!-- Days grid -->
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

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="button" @click="fetchReport()" :disabled="loading" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 disabled:opacity-50 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span x-text="loading ? 'Generating...' : 'Generate Report'"></span>
            </button>
        </div>
    </div>

    <!-- Statistics counters Section -->
    <div x-show="fetched && reports.length > 0" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 no-print" x-transition>
        <!-- Total Strength -->
        <div class="bg-white dark:bg-themeNavy p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex items-center gap-5 hover:-translate-y-0.5 transition-all duration-300">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-themeBlue/10 text-themeBlue shadow-sm shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-wider mb-0.5">Total Students</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight" x-text="countTotal"></h3>
            </div>
        </div>

        <!-- Present Marked -->
        <div class="bg-white dark:bg-themeNavy p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex items-center gap-5 hover:-translate-y-0.5 transition-all duration-300">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-themeGreen/10 text-themeGreen shadow-sm shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-wider mb-0.5">Present Today</p>
                <h3 class="text-2xl font-black text-themeGreen tracking-tight" x-text="countPresent"></h3>
            </div>
        </div>

        <!-- Absent Marked -->
        <div class="bg-white dark:bg-themeNavy p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex items-center gap-5 hover:-translate-y-0.5 transition-all duration-300">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-rose-500/10 text-rose-500 shadow-sm shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-wider mb-0.5">Absent Today</p>
                <h3 class="text-2xl font-black text-rose-500 tracking-tight" x-text="countAbsent"></h3>
            </div>
        </div>
    </div>

    <!-- Report Table Panel -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-hidden mb-8" x-transition>
        <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-32 text-center">Roll</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student Name</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Taken By (Teacher)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-40">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                    
                    <!-- Skeleton Loader Rows -->
                    <template x-if="loading">
                        <template x-for="i in [1, 2, 3]">
                            <tr class="animate-pulse">
                                <td class="py-0 px-0 text-center"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                <td class="py-0 px-0"><div class="h-4 w-40 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="py-0 px-0"><div class="h-4 w-32 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="py-0 px-0 text-center"><div class="h-6 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-full mx-auto"></div></td>
                            </tr>
                        </template>
                    </template>
                    
                    <!-- Data Rows -->
                    <template x-if="!loading && reports.length > 0">
                        <template x-for="item in reports" :key="item.id">
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                <td class="py-0 px-0 text-center font-mono font-black text-2xl text-themeGreen dark:text-green-500" x-text="item.student.roll_number"></td>
                                <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100 uppercase tracking-tight" x-text="item.student.student_name"></td>
                                <td class="py-0 px-0 text-xs font-bold text-gray-400 dark:text-gray-555 uppercase italic" x-text="item.teacher && item.teacher.user ? 'Prof. ' + item.teacher.user.name : 'N/A'"></td>
                                <td class="py-0 px-0 text-center">
                                    <span :class="item.status === 'Present' ? 'bg-green-150/15 dark:bg-green-500/10 text-themeGreen dark:text-green-400 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border border-themeGreen/25 dark:border-green-500/20 shadow-sm' : 'bg-rose-500/10 text-rose-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border border-rose-500/20 shadow-sm'" x-text="item.status"></span>
                                </td>
                            </tr>
                        </template>
                    </template>
                    
                    <!-- Empty Placeholder Rows -->
                    <template x-if="!loading && reports.length === 0">
                        <tr>
                            <td colspan="4" class="py-20 text-center text-gray-400 dark:text-gray-550 font-bold uppercase tracking-[0.2em] text-xs" x-text="fetched ? 'No records found.' : 'Generating reports requires filtering...'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const getAuthHeaders = () => ({ 
        headers: { 
            'Accept': 'application/json', 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
        } 
    });

    // Custom Date Picker component data definition
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue || new Date().toISOString().split('T')[0],
            currentYear: new Date().getFullYear(),
            currentMonth: new Date().getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            
            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        const d = new Date(val);
                        this.currentYear = d.getFullYear();
                        this.currentMonth = d.getMonth();
                        this.generateCalendar();
                    }
                });
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
            
            formatDisplay(val) {
                if (!val) return 'Select Date';
                const d = new Date(val);
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        }
    }

    function attendanceReportPage() {
        return {
            // Dropdowns loaded lists
            branches: [],
            sessions: [],
            classes: [],
            sections: [],

            // Form inputs
            form: {
                branch_id: '',
                session_year_id: '',
                class_id: '',
                section_id: '',
                attendance_date: '{{ date("Y-m-d") }}'
            },

            // Active Dropdown name
            activeDropdown: null,

            // Text labels
            branchText: 'Select Branch',
            sessionText: 'Select Session',
            classText: 'Select Class',
            sectionText: 'Select Section',

            // Data lists
            reports: [],
            
            // Loading/fetched statuses
            loading: false,
            fetched: false,

            async init() {
                this.loading = true;
                try {
                    const [branches, sessions, classes, sections] = await Promise.all([
                        axios.get('/ajax/branches', getAuthHeaders()),
                        axios.get('/ajax/sessions', getAuthHeaders()),
                        axios.get('/ajax/classes', getAuthHeaders()),
                        axios.get('/ajax/sections', getAuthHeaders())
                    ]);

                    this.branches = branches.data.branchData || [];
                    this.sessions = sessions.data.sessionData || [];
                    this.classes = classes.data.classData || [];
                    this.sections = sections.data.sectionData || [];

                    // Setup session defaults
                    if (this.sessions.length > 0) {
                        const firstSession = this.sessions[0];
                        this.form.session_year_id = firstSession.id;
                        this.sessionText = firstSession.session_name;
                    }
                } catch (err) {
                    console.error("Filter Load Error:", err);
                    showAlert("Failed to initialize dropdown filters.", "Error");
                } finally {
                    this.loading = false;
                }
            },

            toggleDropdown(name) {
                this.activeDropdown = this.activeDropdown === name ? null : name;
            },

            selectValue(field, val, text) {
                this.form[field] = val;
                
                const mapping = {
                    branch_id: 'branchText',
                    session_year_id: 'sessionText',
                    class_id: 'classText',
                    section_id: 'sectionText'
                };
                
                const targetTextVar = mapping[field];
                if (targetTextVar) {
                    this[targetTextVar] = text;
                }
                
                this.activeDropdown = null;
            },

            async fetchReport() {
                if (!this.form.class_id) {
                    showAlert("Please select a Class!", "Attention");
                    return;
                }

                this.loading = true;
                this.fetched = false;
                this.reports = [];

                let query = new URLSearchParams({
                    branch_id: this.form.branch_id,
                    session_year_id: this.form.session_year_id,
                    class_id: this.form.class_id,
                    section_id: this.form.section_id,
                    attendance_date: this.form.attendance_date
                }).toString();

                try {
                    let res = await axios.get(`/ajax/attendance/report-data?${query}`, getAuthHeaders());
                    this.reports = res.data.data || [];
                    this.fetched = true;
                } catch (err) {
                    console.error(err);
                    showAlert("Failed to load attendance report records.", "Error");
                } finally {
                    this.loading = false;
                }
            },

            // Computed counts
            get countTotal() {
                return this.reports.length;
            },

            get countPresent() {
                return this.reports.filter(r => r.status === 'Present').length;
            },

            get countAbsent() {
                return this.reports.filter(r => r.status === 'Absent').length;
            }
        };
    }
</script>
@endpush