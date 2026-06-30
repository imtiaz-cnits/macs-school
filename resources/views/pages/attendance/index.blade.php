@extends('tyro-dashboard::layouts.admin')

@section('title', 'Daily Attendance Management')

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
<div x-data="attendancePage()" x-init="init()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Daily Attendance
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Pabna International School</p>
        </div>
        <div class="bg-themeGreen/10 px-5 py-2 rounded-xl border border-themeGreen/20 backdrop-blur-sm">
            <span class="text-xs font-black text-themeGreen uppercase tracking-widest">Date: {{ date('d M, Y') }}</span>
        </div>
    </div>

    <!-- Filters Panel Card -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm mb-8 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
            <!-- Branch -->
            <div class="relative" @click.away="activeDropdown === 'branch' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Branch</label>
                <button type="button" @click="toggleDropdown('branch')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="branchText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
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
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Session</label>
                <button type="button" @click="toggleDropdown('session')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="sessionText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
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
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class *</label>
                <button type="button" @click="toggleDropdown('class')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="classText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
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
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Section</label>
                <button type="button" @click="toggleDropdown('section')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="sectionText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
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

            <!-- Taking Teacher -->
            <div class="relative" @click.away="activeDropdown === 'teacher' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Taking Teacher *</label>
                <button type="button" @click="toggleDropdown('teacher')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="teacherText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'teacher'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('teacher_id', '', 'Select Teacher')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                        Select Teacher
                    </button>
                    <template x-for="item in teachers" :key="item.id">
                        <button type="button" @click="selectValue('teacher_id', item.id, item.name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.teacher_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.name"></span>
                            <template x-if="form.teacher_id === item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Custom Date Picker Component -->
            <div class="relative" x-data="datePicker(form.attendance_date)" @date-selected.window="if($event.detail) form.attendance_date = $event.detail" @click.away="show = false">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Attendance Date</label>
                <button type="button" @click="show = !show" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="formatDisplay(value)"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                
                <!-- Calendar Dropdown panel -->
                <div x-show="show" x-cloak class="absolute right-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                    <div class="flex items-center justify-between mb-2">
                        <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider" x-text="monthNames[currentMonth] + ' ' + currentYear"></span>
                        <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
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

        <!-- Action Button -->
        <div class="flex justify-end">
            <button type="button" @click="fetchStudents()" :disabled="loading" class="inline-flex items-center justify-center px-10 py-4 h-11 bg-gradient-to-r from-themeBlue to-themeGreen hover:from-themeBlue/90 hover:to-themeGreen/90 text-white font-black uppercase tracking-[0.2em] text-xs rounded-xl shadow-md shadow-themeBlue/10 transition-all hover:scale-105 active:scale-95 disabled:opacity-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span x-text="loading ? 'Loading Students...' : 'Load Student List'"></span>
            </button>
        </div>
    </div>

    <!-- Statistics counters Section -->
    <div x-show="fetched && students.length > 0" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 no-print" x-transition>
        <!-- Total Strength -->
        <div class="bg-white dark:bg-themeNavy p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex items-center gap-5 hover:-translate-y-0.5 transition-all duration-300">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-themeBlue/10 text-themeBlue shadow-sm shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Total Strength</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight" x-text="liveTotal"></h3>
            </div>
        </div>

        <!-- Present Marked -->
        <div class="bg-white dark:bg-themeNavy p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex items-center gap-5 hover:-translate-y-0.5 transition-all duration-300">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-themeGreen/10 text-themeGreen shadow-sm shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Marked Present</p>
                <h3 class="text-2xl font-black text-themeGreen tracking-tight" x-text="livePresent"></h3>
            </div>
        </div>

        <!-- Absent Marked -->
        <div class="bg-white dark:bg-themeNavy p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex items-center gap-5 hover:-translate-y-0.5 transition-all duration-300">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-rose-500/10 text-rose-500 shadow-sm shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-0.5">Marked Absent</p>
                <h3 class="text-2xl font-black text-rose-500 tracking-tight" x-text="liveAbsent"></h3>
            </div>
        </div>
    </div>

    <!-- Student List Table Panel -->
    <div x-show="fetched && students.length > 0" x-cloak class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-hidden" x-transition>
        <form @submit.prevent="submitAttendance()">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="w-24 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center">Roll No</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student Name</th>
                                <th class="w-64 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06] font-medium">
                            <template x-for="(s, index) in students" :key="s.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0 text-center font-mono font-black text-themeGreen dark:text-green-500 text-lg" x-text="s.roll_number"></td>
                                    <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100 uppercase tracking-tight" x-text="s.student_name"></td>
                                    <td class="py-0 px-0">
                                        <div class="flex items-center justify-center gap-3">
                                            <!-- Present Toggle -->
                                            <button type="button" @click="attendance_data[s.id] = 'Present'" 
                                                    class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:-translate-y-0.5"
                                                    :class="attendance_data[s.id] === 'Present' ? 'bg-themeGreen text-white shadow-sm' : 'bg-gray-100/50 dark:bg-themeDark text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'">
                                                Present
                                            </button>
                                            <!-- Absent Toggle -->
                                            <button type="button" @click="attendance_data[s.id] = 'Absent'" 
                                                    class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-200 hover:-translate-y-0.5"
                                                    :class="attendance_data[s.id] === 'Absent' ? 'bg-rose-600 text-white shadow-sm' : 'bg-gray-100/50 dark:bg-themeDark text-gray-400 hover:text-rose-500 dark:hover:text-rose-400'">
                                                Absent
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Submit Panel Footer -->
            <div class="p-6 bg-gray-50/50 dark:bg-themeDark/30 border-t border-gray-100 dark:border-white/[0.06] flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest italic">Ensure all statuses are marked correctly before submission</p>
                <button type="submit" :disabled="saving" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 disabled:opacity-50">
                    <span x-text="saving ? 'Submitting...' : 'Submit Attendance'"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Skeleton Pulse Loader during load state -->
    <div x-show="loading && !fetched" x-cloak class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm">
        <div class="space-y-4 animate-pulse">
            <div class="h-6 bg-gray-200 dark:bg-gray-700/60 rounded-md w-1/4"></div>
            <div class="space-y-3">
                <div class="grid grid-cols-3 gap-4">
                    <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl col-span-1"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl col-span-1"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl col-span-1"></div>
                </div>
                <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl"></div>
                <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl"></div>
                <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl"></div>
            </div>
        </div>
    </div>

    <!-- No Data Placeholder -->
    <div x-show="fetched && students.length === 0" x-cloak class="py-20 text-center bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm">
        <p class="text-gray-400 dark:text-gray-550 font-black uppercase tracking-[0.2em] text-xs">No students found matching your criteria</p>
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
                // Watch internal value and dispatch back to Alpine form state
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
                
                // Pad previous month days
                for (let i = 0; i < firstDayIndex; i++) {
                    days.push({ day: '', isCurrentMonth: false });
                }
                
                // Current month days
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

    function attendancePage() {
        return {
            // Dropdowns loaded lists
            branches: [],
            sessions: [],
            classes: [],
            sections: [],
            teachers: [],

            // Form inputs
            form: {
                branch_id: '',
                session_year_id: '',
                class_id: '',
                section_id: '',
                teacher_id: '',
                attendance_date: '{{ date("Y-m-d") }}'
            },

            // Active Dropdowns trigger
            activeDropdown: null,

            // Dropdowns triggers labels
            branchText: 'Select Branch',
            sessionText: 'Select Session',
            classText: 'Select Class',
            sectionText: 'Select Section',
            teacherText: 'Select Teacher',

            // Students attendance lists
            students: [],
            attendance_data: {}, // key: student_id, val: "Present" / "Absent"
            
            // Loading flags
            loading: false,
            saving: false,
            fetched: false,

            async init() {
                this.loading = true;
                try {
                    const [branches, sessions, classes, sections, teachers] = await Promise.all([
                        axios.get('/ajax/branches', getAuthHeaders()),
                        axios.get('/ajax/sessions', getAuthHeaders()),
                        axios.get('/ajax/classes', getAuthHeaders()),
                        axios.get('/ajax/sections', getAuthHeaders()),
                        axios.get('/ajax/teachers', getAuthHeaders()) 
                    ]);

                    this.branches = branches.data.branchData || [];
                    this.sessions = sessions.data.sessionData || [];
                    this.classes = classes.data.classData || [];
                    this.sections = sections.data.sectionData || [];
                    this.teachers = teachers.data.teacherData || [];

                    // Setup defaults if list items are single or populated
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
                    section_id: 'sectionText',
                    teacher_id: 'teacherText'
                };
                
                const targetTextVar = mapping[field];
                if (targetTextVar) {
                    this[targetTextVar] = text;
                }
                
                this.activeDropdown = null;
            },

            async fetchStudents() {
                if (!this.form.class_id) {
                    showAlert("Please select a Class!", "Attention");
                    return;
                }

                this.loading = true;
                this.fetched = false;
                this.students = [];

                let query = new URLSearchParams({ 
                    branch_id: this.form.branch_id, 
                    session_year_id: this.form.session_year_id, 
                    class_id: this.form.class_id, 
                    section_id: this.form.section_id 
                }).toString();

                try {
                    let res = await axios.get(`/ajax/attendance/students?${query}`, getAuthHeaders());
                    this.students = res.data.students || [];
                    
                    // Reset and populate attendance_data default values to "Present"
                    this.attendance_data = {};
                    this.students.forEach(s => {
                        this.attendance_data[s.id] = 'Present';
                    });
                    
                    this.fetched = true;
                } catch (e) {
                    console.error(e);
                    showAlert("Failed to load students list.", "Error");
                } finally {
                    this.loading = false;
                }
            },

            // Computed counts getters
            get liveTotal() {
                return this.students.length;
            },

            get livePresent() {
                return this.students.filter(s => this.attendance_data[s.id] === 'Present').length;
            },

            get liveAbsent() {
                return this.students.filter(s => this.attendance_data[s.id] === 'Absent').length;
            },

            async submitAttendance() {
                if (!this.form.teacher_id) {
                    showAlert("Please select the Taking Teacher!", "Attention");
                    return;
                }

                this.saving = true;
                try {
                    let res = await axios.post('/ajax/attendance/save', { 
                        branch_id: this.form.branch_id,
                        session_year_id: this.form.session_year_id,
                        class_id: this.form.class_id, 
                        section_id: this.form.section_id,
                        teacher_id: this.form.teacher_id, 
                        attendance_date: this.form.attendance_date, 
                        attendance_data: this.attendance_data 
                    }, getAuthHeaders());
                    
                    await showAlert(res.data.message || "Alhamdulillah! Attendance saved successfully.", "Success");
                    window.location.reload();
                    
                } catch (err) { 
                    let errMsg = err.response && err.response.data && err.response.data.message 
                        ? err.response.data.message 
                        : "Failed to save attendance.";
                    showAlert(errMsg, "Save Error");
                } finally {
                    this.saving = false;
                }
            }
        };
    }
</script>
@endpush