@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Collection')

@section('content')
<div id="feeCollectionRoot" class="w-full min-h-screen" x-data="{ loading: false }" @trigger-loader.window="loading = true">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18-1.5a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 7.5" />
                </svg>
                Fee Collection
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">
                @if(request('mode', 'single') === 'bulk')
                    Collect fees in bulk by class and category
                @else
                    Search student and collect pending dues
                @endif
            </p>
        </div>
        
        <!-- Shortcut Action Buttons at Header Top-Right -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('fees.categories.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Categories
            </a>
            <a href="{{ route('fees.setup.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                Setup
            </a>
            <a href="{{ route('fees.invoice.generate') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Generate
            </a>
            <a href="{{ route('fees.reports.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reports
            </a>
        </div>
    </div>

    @if(isset($hasAdmissionSchema) && !$hasAdmissionSchema)
        <div class="mb-6 rounded-2xl border border-amber-200 dark:border-amber-500/30 bg-amber-50/90 dark:bg-amber-950/30 px-4 py-3 text-xs font-bold text-amber-800 dark:text-amber-300 flex items-center gap-3 no-print">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <span>Admission schema migrations are pending. Please contact the system administrator.</span>
        </div>
    @endif

    <!-- Collection Mode Tabs -->
    <div class="flex gap-4 border-b border-gray-100 dark:border-white/[0.06] pb-px mb-8 no-print" x-data="{ activeTab: '{{ request('mode', 'single') }}' }">
        <a href="{{ route('fees.collection.index', ['mode' => 'single']) }}" 
           class="px-4 py-2.5 text-xs font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2"
           :class="activeTab === 'single' ? 'border-themeBlue text-themeBlue' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
           @click="window.dispatchEvent(new CustomEvent('trigger-loader'))">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Single Student Collection
        </a>
        <a href="{{ route('fees.collection.index', ['mode' => 'bulk']) }}" 
           class="px-4 py-2.5 text-xs font-black uppercase tracking-widest border-b-2 transition-all flex items-center gap-2"
           :class="activeTab === 'bulk' ? 'border-themeBlue text-themeBlue' : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
           @click="window.dispatchEvent(new CustomEvent('trigger-loader'))">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Bulk / Class / Category Collection
        </a>
    </div>

    <!-- Filter Criteria & Student Search (Single Collection) -->
    @if(request('mode', 'single') === 'single')
        <div class="mb-8 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 no-print" x-data="singleCollectionFilter()">
            <form action="{{ route('fees.collection.index') }}" method="GET" @submit="window.dispatchEvent(new CustomEvent('trigger-loader'))">
                <input type="hidden" name="mode" value="single">
                <input type="hidden" name="branch_id" :value="form.branch_id">
                <input type="hidden" name="session_year_id" :value="form.session_year_id">
                <input type="hidden" name="class_id" :value="form.class_id">
                <input type="hidden" name="section_id" :value="form.section_id">

                <!-- Row 1: Dropdown Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Branch Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Branch</label>
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
                            @foreach($branches as $branch)
                                <button type="button" @click="selectBranch('{{ $branch->id }}', '{{ $branch->branch_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == '{{ $branch->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $branch->branch_name }}</span>
                                    <template x-if="form.branch_id == '{{ $branch->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Session Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'session') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Academic Session</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'session' ? null : 'session'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="sessionText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="selectSession('', 'All Sessions')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Sessions</span>
                                <template x-if="form.session_year_id === ''">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                            @foreach($sessions as $session)
                                <button type="button" @click="selectSession('{{ $session->id }}', '{{ $session->session_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id == '{{ $session->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $session->session_name }}</span>
                                    <template x-if="form.session_year_id == '{{ $session->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Class Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class</label>
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
                            @foreach($classes as $class)
                                <button type="button" @click="selectClass('{{ $class->id }}', '{{ $class->class_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id == '{{ $class->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $class->class_name }}</span>
                                    <template x-if="form.class_id == '{{ $class->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Section Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'section') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Section</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'section' ? null : 'section'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="sectionText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'section'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="selectSection('', 'All Sections')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Sections</span>
                                <template x-if="form.section_id === ''">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                            @foreach($sections as $sec)
                                <button type="button" @click="selectSection('{{ $sec->id }}', '{{ $sec->section_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id == '{{ $sec->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $sec->section_name }}</span>
                                    <template x-if="form.section_id == '{{ $sec->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Row 2: Search Input & Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 items-center">
                    <div class="relative w-full flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" name="student_identity" value="{{ request('student_identity', request('search', '')) }}" placeholder="Enter Student ID (e.g. PIS-...) or Search by Name / Roll / Mobile..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-250 pl-10 pr-3 placeholder-gray-400">
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button type="submit" class="flex-1 sm:flex-none h-11 px-7 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span>Search / Filter</span>
                        </button>
                        <a href="{{ route('fees.collection.index', ['mode' => 'single']) }}" class="flex-1 sm:flex-none h-11 px-5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl transition-all uppercase tracking-wider active:scale-95 flex items-center justify-center gap-1.5 whitespace-nowrap">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @if(session('success')) 
        <div class="bg-green-50 dark:bg-themeGreen/10 text-themeGreen p-4 rounded-2xl mb-6 font-bold border border-green-200/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <span>{{ session('success') }}</span>
            <div class="flex gap-2">
                @if(session('print_invoice_id'))
                    <a href="{{ route('fees.invoice.pos_print', session('print_invoice_id')) }}" target="_blank" class="h-9 px-4 bg-themeGreen text-white text-[10px] font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        POS Receipt
                    </a>
                @endif
                @if(session('print_receipt_no'))
                    <a href="{{ route('fees.receipt.pos_print', session('print_receipt_no')) }}" target="_blank" class="h-9 px-4 bg-themeBlue text-white text-[10px] font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Master Receipt
                    </a>
                    <a href="{{ route('fees.receipt.pos_print_individual', session('print_receipt_no')) }}" target="_blank" class="h-9 px-4 bg-themeGreen text-white text-[10px] font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Individual Receipts
                    </a>
                @endif
            </div>
        </div> 
    @endif
    @if(session('error')) <div class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 p-4 rounded-2xl mb-6 font-bold border border-red-200/30">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 p-4 rounded-2xl mb-6 font-bold border border-red-200/30">{{ $errors->first() }}</div> @endif

    <!-- Main Content Panel (Hidden when loading) -->
    <div x-show="!loading">
        <!-- Single Mode View -->
        @if(request('mode', 'single') === 'single')
        @if($student)
        <div class="mb-4 flex flex-wrap justify-between items-center gap-3">
            <a href="{{ route('fees.collection.index', array_merge(request()->except(['student_identity', 'search']), ['mode' => 'single'])) }}" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-wider text-themeBlue hover:text-themeGreen active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Fee Collection</span>
            </a>
            <a href="{{ route('student.view', $student->id) }}" class="inline-flex items-center gap-1.5 px-3.5 h-8 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-themeNavy text-[10px] font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue transition-all shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Student Profile</span>
            </a>
        </div>

        <div class="w-full min-w-0 space-y-6">
            <!-- Full-Width Horizontal Student Profile Banner -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl px-6 py-4 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                    <!-- Student Identity & Status -->
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 bg-gray-50 dark:bg-themeDark rounded-2xl border-2 border-gray-100 dark:border-white/[0.08] shadow-sm flex items-center justify-center overflow-hidden flex-shrink-0">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($student->student_name) }}&background=008ED6&color=fff" alt="{{ $student->student_name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight truncate">{{ $student->student_name }}</h3>
                                @if(($student->status ?? 'Active') === 'Inactive')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20 whitespace-nowrap">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactive Student
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 whitespace-nowrap">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active Student
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span class="px-2.5 py-0.5 rounded-lg bg-sky-50 dark:bg-themeBlue/15 text-themeBlue font-mono font-black text-xs tracking-wider">{{ $student->student_identity }}</span>
                                @if($student->branch)
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">&middot; {{ $student->branch->branch_name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Academic & Contact Metadata Chips -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 xl:flex xl:items-center xl:gap-3">
                        <div class="rounded-2xl bg-gray-50/80 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] px-3.5 py-2">
                            <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Class</div>
                            <div class="text-xs font-black text-gray-800 dark:text-gray-100 mt-0.5 whitespace-nowrap">{{ $student->schoolClass->class_name ?? 'N/A' }}</div>
                        </div>
                        <div class="rounded-2xl bg-gray-50/80 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] px-3.5 py-2">
                            <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Section</div>
                            <div class="text-xs font-black text-gray-800 dark:text-gray-100 mt-0.5 whitespace-nowrap">{{ $student->section->section_name ?? 'N/A' }}</div>
                        </div>
                        <div class="rounded-2xl bg-gray-50/80 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] px-3.5 py-2">
                            <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Roll No</div>
                            <div class="text-xs font-mono font-black text-gray-800 dark:text-gray-100 mt-0.5 whitespace-nowrap">{{ $student->roll_number }}</div>
                        </div>
                        <div class="rounded-2xl bg-gray-50/80 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] px-3.5 py-2">
                            <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Mobile</div>
                            <div class="text-xs font-mono font-bold text-gray-800 dark:text-gray-100 mt-0.5 whitespace-nowrap">{{ $student->father_mobile }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if(($student->status ?? 'Active') === 'Inactive')
            <!-- Inactive Student Account Notice Banner -->
            <div class="p-4 rounded-2xl bg-rose-50/90 dark:bg-rose-950/25 border-2 border-rose-200 dark:border-rose-900/50 text-rose-800 dark:text-rose-300 flex items-center gap-3.5 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/60 flex items-center justify-center shrink-0 text-rose-600 dark:text-rose-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="font-black text-xs uppercase tracking-wider text-rose-700 dark:text-rose-300 flex items-center gap-2">
                        <span>Inactive Student Account</span>
                        <span class="px-2 py-0.5 text-[9px] font-black rounded-md bg-rose-200/70 dark:bg-rose-900/70 text-rose-900 dark:text-rose-100">School Leaver</span>
                    </div>
                    <p class="font-medium text-xs text-rose-700 dark:text-rose-300/90 mt-0.5">This student has been marked as <strong>Inactive</strong>. You can collect previous pending dues below, but no new monthly or exam fee invoices will be generated for them.</p>
                </div>
            </div>
            @endif

            <!-- Full-Width Pending Dues Table (AGENTS.md Rule 9) -->
            <form action="{{ route('fees.collection.bulk_store') }}" method="POST" id="bulkPaymentForm" class="w-full min-w-0">
                @csrf
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden relative">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
                        <h3 class="text-sm font-black text-red-600 dark:text-red-400 uppercase tracking-wider flex items-center whitespace-nowrap">
                            <svg class="w-5 h-5 mr-2 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Pending Dues
                        </h3>

                        <!-- Important Financial Summary Badges -->
                        <div class="flex flex-wrap items-center gap-2">
                            @php
                                $pendingInvoicesCount = $invoices->count();
                                $totalNetBill = '0.00';
                                $totalDueAmount = '0.00';
                                $totalDiscount = '0.00';
                                foreach ($invoices as $sumInv) {
                                    $invPayable = method_exists($sumInv, 'totalPayableAmount')
                                        ? $sumInv->totalPayableAmount()
                                        : bcadd((string) ($sumInv->net_amount ?? '0'), '0.00', 2);
                                    $totalNetBill = bcadd($totalNetBill, $invPayable, 2);
                                    $totalDueAmount = bcadd($totalDueAmount, bcadd((string) ($sumInv->due_amount ?? '0'), '0.00', 2), 2);
                                    $totalDiscount = bcadd($totalDiscount, bcadd((string) ($sumInv->discount ?? '0'), '0.00', 2), 2);
                                }
                                $totalLifetimePaid = '0.00';
                                foreach ($paymentHistory->flatten()->filter(fn ($pay) => !$pay->isReversed()) as $activePay) {
                                    $totalLifetimePaid = bcadd($totalLifetimePaid, bcadd((string) $activePay->paid_amount, '0.00', 2), 2);
                                }
                            @endphp

                            <!-- Dynamic Selected Amount (shown when checkboxes are checked) -->
                            <div id="headerSelectedPill" class="hidden px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-themeBlue to-indigo-600 text-white shadow-md shadow-themeBlue/20 text-xs font-black flex items-center gap-1.5 transition-all whitespace-nowrap">
                                <span class="text-[10px] font-black uppercase tracking-wider opacity-90">Selected:</span>
                                <span class="font-mono text-xs font-black">৳ <span id="headerSelectedDisplay">0.00</span></span>
                            </div>

                            <!-- Invoices Count -->
                            <div class="px-3 py-1.5 rounded-xl bg-gray-50/80 dark:bg-themeDark border border-gray-150 dark:border-white/[0.08] text-xs font-semibold flex items-center gap-1.5 shadow-sm whitespace-nowrap">
                                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Invoices:</span>
                                <span id="summaryInvoicesCount" class="font-black text-gray-800 dark:text-gray-200">{{ $pendingInvoicesCount }}</span>
                            </div>

                            <!-- Total Bill -->
                            <div class="px-3 py-1.5 rounded-xl bg-gray-50/80 dark:bg-themeDark border border-gray-150 dark:border-white/[0.08] text-xs font-semibold flex items-center gap-1.5 shadow-sm whitespace-nowrap">
                                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Bill:</span>
                                <span id="summaryTotalBill" class="font-black font-mono text-gray-900 dark:text-white">৳ {{ number_format($totalNetBill, 2) }}</span>
                            </div>

                            <!-- Total Discount -->
                            <div id="summaryDiscountWrapper" class="{{ $totalDiscount > 0 ? '' : 'hidden' }} px-3 py-1.5 rounded-xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/40 text-xs font-semibold flex items-center gap-1.5 shadow-sm text-amber-700 dark:text-amber-400 whitespace-nowrap">
                                <span class="text-[10px] font-black uppercase tracking-wider opacity-80">Discount:</span>
                                <span id="summaryDiscount" class="font-black font-mono">৳ {{ number_format($totalDiscount, 2) }}</span>
                            </div>

                            <!-- Total Paid (Active Only) -->
                            <div class="px-3 py-1.5 rounded-xl bg-green-50/70 dark:bg-themeGreen/10 border border-green-200/60 dark:border-green-800/40 text-xs font-semibold flex items-center gap-1.5 shadow-sm text-themeGreen whitespace-nowrap">
                                <span class="text-[10px] font-black uppercase tracking-wider opacity-80">Total Paid:</span>
                                <span class="font-black font-mono">৳ {{ number_format($totalLifetimePaid, 2) }}</span>
                            </div>

                            <!-- Total Pending Due (Prominent Red Badge) -->
                            <div class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 text-white shadow-md shadow-red-500/20 text-xs font-black flex items-center gap-1.5 whitespace-nowrap">
                                <span class="uppercase tracking-wider text-[10px] opacity-90">Total Due:</span>
                                <span id="summaryTotalDue" class="font-mono text-xs sm:text-sm font-black tracking-tight">৳ {{ number_format($totalDueAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-4 pt-1">
                        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                            <table class="w-full text-left border-collapse table [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-1 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-1 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-center w-10">
                                            <input type="checkbox" id="selectAllDues" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 accent-themeBlue focus:ring-themeBlue cursor-pointer">
                                        </th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Fee Description</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Month</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] text-right whitespace-nowrap">Net Bill</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-themeGreen dark:text-themeGreen uppercase tracking-[0.18em] text-right whitespace-nowrap">Due Amt (৳)</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] text-right whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $inv)
                                    @php
                                        $isAdmInv = method_exists($inv, 'isAdmissionInvoice') && $inv->isAdmissionInvoice();
                                        $fineAmountStr = method_exists($inv, 'fineAmountDecimal') ? $inv->fineAmountDecimal() : '0.00';
                                        $hasLateFine = !$isAdmInv && bccomp($fineAmountStr, '0.00', 2) > 0;
                                        $isMonthlyTuition = (bool) ($inv->is_monthly_tuition_row ?? (method_exists($inv, 'isMonthlyTuitionInvoice') && $inv->isMonthlyTuitionInvoice()));
                                        $canManageLateFine = (bool) ($inv->can_manage_late_fine ?? (
                                            $isMonthlyTuition
                                            && ($inv->status === 'Unpaid')
                                            && bccomp((string) ($inv->net_amount ?? '0'), '0.00', 2) > 0
                                            && bccomp((string) ($inv->paid_amount ?? '0'), '0.00', 2) === 0
                                        ));
                                        $canWaiveFine = (bool) ($inv->can_waive_late_fine ?? ($canManageLateFine && $hasLateFine));
                                        $fineSource = $inv->fine_source ?? null;
                                        $fineReason = $inv->fine_reason ?? null;
                                        $invCategoryId = $inv->feeSetup->fee_category_id ?? '';
                                        $invTitle = $isAdmInv
                                            ? ('Admission Fee Package — ' . ($inv->admissionFeeStructure->structure_name ?? 'Admission Fee'))
                                            : ($inv->feeSetup?->category?->name ?? 'Fee Payment');
                                    @endphp
                                    <tr id="inv-row-{{ $inv->id }}" data-category-id="{{ $invCategoryId }}" class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0 text-center">
                                            <input type="checkbox" id="checkbox-{{ $inv->id }}" name="invoice_ids[]" value="{{ $inv->id }}" data-invoice-id="{{ $inv->id }}" data-amount="{{ $inv->due_amount }}" class="due-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 accent-themeBlue focus:ring-themeBlue cursor-pointer">
                                        </td>
                                        <td class="py-0 px-0">
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-gray-100 text-sm flex flex-wrap items-center gap-2">
                                                    <span class="whitespace-nowrap">{{ $invTitle }}</span>
                                                    @if($isAdmInv)
                                                        <span class="inline-flex items-center px-2 py-0.5 bg-indigo-50 dark:bg-themeBlue/15 border border-indigo-100 dark:border-themeBlue/30 text-themeBlue text-[9px] font-black uppercase tracking-wider rounded-lg whitespace-nowrap">Admission</span>
                                                    @elseif($hasLateFine)
                                                        <span title="{{ $fineReason ?: 'Late payment fine' }}" class="inline-flex items-center px-2 py-0.5 bg-amber-50 dark:bg-amber-500/15 border border-amber-200 dark:border-amber-500/30 text-amber-700 dark:text-amber-300 text-[9px] font-black uppercase tracking-wider rounded-lg whitespace-nowrap">
                                                            + ৳{{ number_format((float) $fineAmountStr, 2) }} Late Fine &bull; {{ $fineSource === 'auto' ? 'Auto' : 'Manual' }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-gray-400 dark:text-gray-500 font-mono mt-0.5 whitespace-nowrap">{{ $inv->invoice_no }}</div>
                                            </div>
                                        </td>
                                        <td class="py-0 px-0 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 bg-sky-50/70 dark:bg-themeDark border border-sky-100 dark:border-white/[0.06] text-themeBlue text-[10px] font-black uppercase tracking-wider rounded-lg whitespace-nowrap">{{ $inv->month_name }}</span>
                                        </td>
                                        <td class="py-0 px-0 text-right font-mono whitespace-nowrap">
                                            <div id="net-bill-{{ $inv->id }}" class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ number_format($inv->net_amount, 2) }}</div>
                                            @if($hasLateFine)
                                                <div class="text-[10px] font-black text-amber-600 dark:text-amber-400 mt-0.5">+ ৳{{ number_format((float) $fineAmountStr, 2) }} Fine</div>
                                            @endif
                                        </td>
                                        <td class="py-0 px-0 text-right whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-xl bg-emerald-50/70 dark:bg-themeGreen/10 border border-emerald-200/60 dark:border-themeGreen/25 text-sm font-black font-mono text-themeGreen whitespace-nowrap">
                                                <span class="text-xs mr-1 opacity-75">৳</span>
                                                <span id="due-display-{{ $inv->id }}">{{ number_format($inv->due_amount, 2) }}</span>
                                            </span>
                                        </td>
                                        <td class="py-0 px-0 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                                @if(!$isAdmInv && $inv->feeSetup)
                                                <button type="button"
                                                        id="adjust-btn-{{ $inv->id }}"
                                                        data-fee-name="{{ $invTitle }}"
                                                        data-month-name="{{ $inv->month_name }}"
                                                        data-setup-amount="{{ $inv->feeSetup->amount }}"
                                                        data-gross-amount="{{ $inv->amount }}"
                                                        data-discount-amount="{{ $inv->discount }}"
                                                        data-net-amount="{{ $inv->net_amount }}"
                                                        data-fine-amount="{{ $fineAmountStr }}"
                                                        data-fine-reason="{{ $fineReason ?? '' }}"
                                                        data-paid-amount="{{ $inv->paid_amount }}"
                                                        data-due-amount="{{ $inv->due_amount }}"
                                                        data-can-manage-late-fine="{{ $canManageLateFine ? '1' : '0' }}"
                                                        data-late-fine-url="{{ route('fees.invoice.late_fine', $inv->id) }}"
                                                        onclick="openAdjustModal({{ $inv->id }})"
                                                        title="{{ $canManageLateFine ? 'Add Late Fine or Adjust Fee' : 'Adjust Fee Amount' }} for {{ $inv->month_name }}"
                                                        class="h-8 w-8 rounded-lg border border-themeBlue/30 hover:border-themeBlue bg-sky-50/70 hover:bg-themeBlue dark:bg-themeBlue/10 dark:hover:bg-themeBlue text-themeBlue hover:text-white flex items-center justify-center transition-all active:scale-95 flex-shrink-0 group">
                                                    <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                    </svg>
                                                </button>
                                                @endif
                                                @if($canWaiveFine)
                                                <button type="button"
                                                        id="waive-btn-{{ $inv->id }}"
                                                        onclick="openWaiveFineModal({{ $inv->id }}, {{ \Illuminate\Support\Js::from($inv->invoice_no) }}, {{ \Illuminate\Support\Js::from($inv->month_name) }}, {{ (float) $fineAmountStr }}, {{ \Illuminate\Support\Js::from(route('fees.invoice.waive_fine', $inv->id)) }})"
                                                        title="Waive unpaid late fine"
                                                        class="h-8 px-2.5 rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50/70 dark:bg-rose-500/10 hover:bg-rose-600 hover:border-rose-600 text-rose-600 dark:text-rose-400 hover:text-white text-[10px] font-black uppercase tracking-wider transition-all active:scale-95 flex-shrink-0">
                                                    Waive Fine
                                                </button>
                                                @endif
                                                @if($isAdmInv)
                                                    <button type="button"
                                                            id="pay-btn-{{ $inv->id }}"
                                                            data-fee-name="{{ $invTitle }} ({{ $inv->month_name }})"
                                                            data-admission-invoice="{{ json_encode([
                                                                'invoice_id' => $inv->id,
                                                                'invoice_no' => $inv->invoice_no,
                                                                'structure_name' => $inv->prepared_structure_name ?? ($invTitle ?: 'Admission Fee Package'),
                                                                'due_amount' => bcadd((string) $inv->due_amount, '0.00', 2),
                                                                'heads' => $inv->prepared_admission_heads ?? [],
                                                            ]) }}"
                                                            onclick="window.openAdmissionPayModalFromButton(this)"
                                                            class="h-8 px-3.5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-[10px] font-black rounded-lg shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center active:scale-95 whitespace-nowrap">
                                                        Collect Heads
                                                    </button>
                                                @else
                                                    <button type="button" id="pay-btn-{{ $inv->id }}" data-fee-name="{{ $invTitle }} ({{ $inv->month_name }})" onclick="openPayModal({{ $inv->id }}, {{ \Illuminate\Support\Js::from($invTitle . ' (' . $inv->month_name . ')') }}, {{ $inv->due_amount }})" class="h-8 px-3.5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-[10px] font-black rounded-lg shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center active:scale-95 whitespace-nowrap">Pay Single</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-themeGreen dark:text-themeGreen font-black text-sm uppercase tracking-wider">
                                            <svg class="w-12 h-12 mx-auto mb-3 text-themeGreen opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            All Clear! No pending dues for this student.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Floating modern selected dues panel -->
                    @if($invoices->count() > 0)
                    <div id="bulkPaymentBar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[999] w-[calc(100%-2rem)] max-w-2xl bg-gradient-to-r from-themeBlue to-themeGreen p-4 rounded-3xl shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-4 hidden transition-all duration-300">
                        <div class="text-white flex items-center">
                            <span class="text-xs font-black uppercase tracking-wider opacity-85">Selected Total:</span>
                            <span class="text-2xl font-black ml-3 font-mono">৳ <span id="bulkTotalDisplay">0.00</span></span>
                        </div>
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <!-- Custom Alpine Dropdown for bulk payment method -->
                            <div x-data="{ 
                                open: false, 
                                value: 'Cash', 
                                label: 'Cash',
                                select(val) {
                                    this.value = val;
                                    this.label = val;
                                    this.open = false;
                                    let inp = this.$refs.hiddenInput;
                                    inp.value = val;
                                }
                            }" class="relative w-full sm:w-36 text-gray-900" @click.away="open = false">
                                <button type="button" @click="open = !open" class="w-full h-10 px-3 bg-white/20 border border-white/30 rounded-xl flex items-center justify-between text-xs font-black text-white focus:outline-none focus:ring-2 focus:ring-white transition-all text-left">
                                    <span class="truncate uppercase tracking-wider" x-text="label"></span>
                                    <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <input type="hidden" name="payment_method" x-ref="hiddenInput" value="Cash">
                                <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    <template x-for="opt in ['Cash', 'bKash', 'Nagad', 'Bank']" :key="opt">
                                        <button type="button" @click="select(opt)" class="w-full flex items-center justify-between px-3 py-2 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="value == opt ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span x-text="opt"></span>
                                            <svg x-show="value == opt" class="w-3 h-3 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <button type="submit" class="w-full sm:w-auto h-10 px-5 bg-white text-themeGreen font-black rounded-xl text-xs uppercase tracking-widest hover:bg-gray-50 hover:-translate-y-0.5 active:scale-95 transition-all shadow-md">Pay Selected</button>
                        </div>
                    </div>
                    @endif
                </div>
            </form>

            <!-- Full-Width Payment History Table (AGENTS.md Rule 9) -->
            <div class="w-full min-w-0 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between gap-3">
                    <h3 class="text-sm font-black text-themeGreen dark:text-themeGreen uppercase tracking-wider flex items-center">
                        <svg class="w-5 h-5 mr-2 text-themeGreen flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        Payment History
                    </h3>
                    <span class="text-xs font-mono font-bold text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $paymentHistory->count() }} receipt(s)</span>
                </div>

                <div class="px-6 pb-4 pt-1">
                    <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                        <table class="w-full text-left border-collapse table [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Paid Items</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Date &amp; Method</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] text-right whitespace-nowrap">Total Paid</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] text-right whitespace-nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentHistory as $receiptNo => $payments)
                                @php
                                    $activeGroupPayments = $payments->filter(fn ($pay) => !$pay->isReversed());
                                    $reversedGroupPayments = $payments->filter(fn ($pay) => $pay->isReversed());
                                    $totalPaid = '0.00';
                                    foreach ($activeGroupPayments as $activePay) {
                                        $totalPaid = \App\Helpers\NumberToWords::add($totalPaid, $activePay->paid_amount);
                                    }
                                    $totalReversed = '0.00';
                                    foreach ($reversedGroupPayments as $reversedPay) {
                                        $totalReversed = \App\Helpers\NumberToWords::add($totalReversed, $reversedPay->reversed_amount ?: $reversedPay->paid_amount);
                                    }
                                    $primaryPay = $activeGroupPayments->first() ?? $payments->first();
                                    $date = $primaryPay->payment_date;
                                    $method = $primaryPay->payment_method;
                                    $isGroupFullyReversed = $activeGroupPayments->isEmpty() && $reversedGroupPayments->isNotEmpty();
                                @endphp

                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0">
                                        <div class="space-y-1.5 mb-2">
                                            @foreach($payments as $p)
                                                @php
                                                    $isAdmItem = $p->invoice && $p->invoice->isAdmissionInvoice();
                                                    $itemTitle = $isAdmItem
                                                        ? ('Admission Fee Package — ' . ($p->invoice->admissionFeeStructure->structure_name ?? 'Admission Fee'))
                                                        : ($p->invoice->feeSetup->category->name ?? 'Fee Payment');
                                                    $isItemReversed = $p->isReversed();
                                                @endphp
                                                <div>
                                                    <div class="font-bold {{ $isItemReversed ? 'text-gray-400 dark:text-gray-500 line-through' : 'text-gray-900 dark:text-gray-100' }} text-sm flex flex-wrap items-center gap-1.5">
                                                        @if($isItemReversed)
                                                            <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        @else
                                                            <svg class="w-4 h-4 text-themeGreen flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                                        @endif
                                                        <span>{{ $itemTitle }}</span>
                                                        <span class="inline-flex items-center px-2 py-0.5 bg-sky-50/70 dark:bg-themeDark border border-sky-100 dark:border-white/[0.06] text-themeBlue text-[9px] font-black uppercase tracking-wider rounded-lg whitespace-nowrap">{{ $p->invoice->month_name ?? 'One Time' }}</span>
                                                        @if(!$isAdmItem && method_exists($p, 'fineAmountDecimal') && bccomp($p->fineAmountDecimal(), '0.00', 2) > 0)
                                                            <span class="inline-flex items-center px-2 py-0.5 bg-amber-50 dark:bg-amber-500/15 border border-amber-200 dark:border-amber-500/30 text-amber-700 dark:text-amber-300 text-[9px] font-black uppercase tracking-wider rounded-lg whitespace-nowrap">
                                                                Incl. ৳{{ number_format((float) $p->fineAmountDecimal(), 2) }} Late Fine
                                                            </span>
                                                        @endif
                                                        @if($isItemReversed)
                                                            <span class="inline-flex items-center px-2 py-0.5 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 text-[9px] font-black uppercase tracking-wider rounded-lg whitespace-nowrap">
                                                                Reversed{{ $p->reversal_reference ? ' · ' . $p->reversal_reference : '' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if($isAdmItem)
                                                        @php
                                                            $allocSelection = $p->prepared_alloc_selection ?? [
                                                                'allocations' => collect(),
                                                                'has_status_mismatch' => false,
                                                                'missing_expected_allocations' => false,
                                                                'used_provenance_fallback' => false,
                                                            ];
                                                            $displayHeadAllocs = $allocSelection['allocations'] ?? collect();
                                                        @endphp
                                                        @if($displayHeadAllocs->isNotEmpty())
                                                            <div class="mt-1 pl-5 flex flex-wrap gap-1.5">
                                                                @foreach($displayHeadAllocs as $headAlloc)
                                                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded-md border border-gray-100 dark:border-white/[0.06] bg-gray-50 dark:bg-themeDark whitespace-nowrap {{ $isItemReversed ? 'text-gray-400 line-through' : 'text-gray-600 dark:text-gray-300' }}">
                                                                        {{ $headAlloc->head_name_snapshot }}: ৳{{ \App\Helpers\NumberToWords::formatDecimal($headAlloc->allocated_paid_amount) }}
                                                                        @if($isItemReversed && !$headAlloc->isReversed())
                                                                            <span class="ml-0.5 text-[9px] font-black uppercase text-rose-500 no-underline inline-block">(Orig. Unsynced)</span>
                                                                        @endif
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @elseif($allocSelection['has_status_mismatch'] && !$isItemReversed)
                                                            <div class="mt-1 pl-5 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                                                                Active head allocation breakdown unavailable (no Active head allocations matched).
                                                            </div>
                                                        @elseif($isItemReversed && $allocSelection['missing_expected_allocations'])
                                                            <div class="mt-1 pl-5 text-[10px] font-semibold text-gray-400 italic">
                                                                Reversed head allocation details missing from ledger.
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 font-mono bg-gray-50 dark:bg-themeDark border border-gray-150 dark:border-white/[0.06] inline-block px-2 py-0.5 rounded-lg whitespace-nowrap">{{ $receiptNo }}</span>
                                    </td>

                                    <td class="py-0 px-0 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm whitespace-nowrap">{{ $date ? date('d M, Y', strtotime((string) $date)) : 'N/A' }}</div>
                                        @if($isGroupFullyReversed)
                                            <div class="text-[10px] text-rose-600 dark:text-rose-400 font-black uppercase tracking-wider mt-0.5 whitespace-nowrap">Void / Reversed</div>
                                        @else
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold mt-0.5 whitespace-nowrap">Via {{ $method }}</div>
                                        @endif
                                    </td>

                                    <td class="py-0 px-0 text-right whitespace-nowrap">
                                        <div class="font-black {{ $isGroupFullyReversed ? 'text-rose-600 dark:text-rose-400' : 'text-themeGreen dark:text-themeGreen' }} text-base font-mono whitespace-nowrap">
                                            ৳ {{ \App\Helpers\NumberToWords::formatDecimal($totalPaid) }}
                                        </div>
                                        @if($reversedGroupPayments->isNotEmpty())
                                            <div class="text-[10px] font-bold text-rose-500 line-through mt-0.5 whitespace-nowrap">
                                                Reversed: ৳{{ \App\Helpers\NumberToWords::formatDecimal($totalReversed) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="py-0 px-0 text-right whitespace-nowrap">
                                        <a href="{{ route('fees.receipt.pos_print', $receiptNo) }}" target="_blank" class="h-8 px-3.5 bg-sky-50 hover:bg-themeBlue text-themeBlue hover:text-white border border-sky-200/70 dark:bg-themeBlue/10 dark:border-themeBlue/25 dark:hover:bg-themeBlue dark:hover:text-white text-[10px] font-black rounded-lg shadow-sm hover:-translate-y-0.5 transition-all uppercase tracking-widest inline-flex items-center justify-center gap-1.5 whitespace-nowrap active:scale-95">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.3"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            {{ $isGroupFullyReversed ? 'Void Receipt' : 'Receipt' }}
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-gray-500 font-bold text-xs uppercase tracking-wider">
                                        No payment history available.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @else
            @php
                $isSearchLookup = (bool) ($hasSearchQuery ?? false);
                $stdFeeCols = $standardFeeColumns ?? collect();
                $stdFeeRows = $standardFeeRows ?? collect();
                $stdScopeLabel = $standardFeeScopeLabel ?? '';
            @endphp

            @if($isSearchLookup)
                <!-- Search Results Directory (Only shown when partial search matches multiple students, AGENTS.md Rule 9) -->
                <div class="w-full min-w-0 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden relative">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex flex-wrap justify-between items-center gap-3">
                        <h3 class="text-sm font-black text-themeBlue uppercase tracking-wider flex items-center">
                            <svg class="w-5 h-5 mr-2 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            Matching Students
                        </h3>
                        <div class="text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-themeDark px-3.5 py-1.5 rounded-xl border border-gray-100 dark:border-white/[0.08] whitespace-nowrap">
                            Matched: <span class="text-themeBlue font-black font-mono">{{ method_exists($dueStudents, 'total') ? $dueStudents->total() : $dueStudents->count() }}</span>
                        </div>
                    </div>

                    <div class="px-6 pb-4 pt-1">
                        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                            <table class="w-full text-left border-collapse table tabular-nums [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Student ID</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Student Name</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Class / Section &amp; Roll</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] text-right whitespace-nowrap">Outstanding Due</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] text-right whitespace-nowrap">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dueStudents as $ds)
                                    @php $dsDue = (float) ($ds->total_due ?? 0); @endphp
                                    <tr class="transition-colors">
                                        <td class="py-0 px-0 whitespace-nowrap">
                                            <span class="font-mono font-black text-themeBlue text-xs uppercase">{{ $ds->student_identity }}</span>
                                        </td>
                                        <td class="py-0 px-0 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $ds->student_name }}</span>
                                                @if(($ds->status ?? 'Active') === 'Inactive')
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactive
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-0 px-0 whitespace-nowrap">
                                            <span class="font-bold text-gray-700 dark:text-gray-300 text-xs">{{ $ds->schoolClass->class_name ?? 'N/A' }}</span>
                                            @if($ds->section)
                                                <span class="text-xs text-gray-400"> - {{ $ds->section->section_name }}</span>
                                            @endif
                                            @if($ds->roll_number)
                                                <span class="text-gray-400 mx-1">&bull;</span>
                                                <span class="font-mono text-xs font-bold text-gray-500 dark:text-gray-400">Roll: {{ $ds->roll_number }}</span>
                                            @endif
                                        </td>
                                        <td class="py-0 px-0 text-right whitespace-nowrap">
                                            <span class="font-mono font-black text-sm {{ $dsDue > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-themeGreen dark:text-emerald-400' }}">
                                                ৳ {{ number_format($dsDue, 2) }}
                                            </span>
                                        </td>
                                        <td class="py-0 px-0 text-right whitespace-nowrap">
                                            <a href="{{ route('fees.collection.index', ['mode' => 'single', 'student_identity' => $ds->student_identity]) }}" class="h-8 px-3.5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-[10px] font-black rounded-lg shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest inline-flex items-center justify-center active:scale-95 whitespace-nowrap">Open Ledger</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="py-10 text-center text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                                No students found matching your search query.
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(method_exists($dueStudents, 'hasPages') && $dueStudents->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.06] bg-gray-50/40 dark:bg-themeDark/25">
                        {{ $dueStudents->onEachSide(1)->links('partials.pagination') }}
                    </div>
                    @endif
                </div>
            @else
                <!-- Class-wise Standard Fee Schedule Reference Table (Full-Width, AGENTS.md Rule 9) -->
                <div class="w-full min-w-0 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden relative">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex flex-wrap justify-between items-center gap-3">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <div>
                                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">
                                    Class-wise Standard Fee Schedule
                                </h3>
                                <p class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-0.5">
                                    Standard configured fee amounts by academic class (Play to Ten)
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if($stdScopeLabel)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-50 dark:bg-themeBlue/15 border border-sky-200/70 dark:border-themeBlue/30 text-[11px] font-black text-themeBlue dark:text-blue-300 whitespace-nowrap">
                                {{ $stdScopeLabel }}
                            </span>
                            @endif
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-gray-50 dark:bg-themeDark border border-gray-200/80 dark:border-white/[0.08] text-[11px] font-bold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                Classes: <strong class="font-mono font-black text-gray-900 dark:text-white ml-1">{{ $stdFeeRows->count() }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="px-6 pb-4 pt-1">
                        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                            <table class="w-full text-left border-collapse table tabular-nums [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] text-center w-12 whitespace-nowrap">#</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Class</th>
                                        @foreach($stdFeeCols as $col)
                                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black uppercase tracking-[0.18em] text-right whitespace-nowrap {{ $col['type'] === 'admission' ? 'text-themeGreen' : ($col['type'] === 'monthly' ? 'text-themeBlue' : 'text-gray-400 dark:text-gray-550') }}">
                                                {{ $col['label'] }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($stdFeeRows as $idx => $row)
                                    <tr class="transition-colors">
                                        <td class="py-0 px-0 text-center whitespace-nowrap">
                                            <span class="font-mono font-black text-xs text-gray-400 dark:text-gray-500">{{ str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td class="py-0 px-0 whitespace-nowrap">
                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $row['class_name'] }}</span>
                                        </td>
                                        @foreach($stdFeeCols as $col)
                                            @php $cellVal = $row['cells'][$col['key']] ?? null; @endphp
                                            <td class="py-0 px-0 text-right whitespace-nowrap">
                                                @if($col['type'] === 'admission')
                                                    @if(is_array($cellVal))
                                                        <div class="inline-flex items-center justify-end gap-1.5">
                                                            @if(!empty($cellVal['includes_tuition']))
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-sky-50 dark:bg-themeBlue/15 border border-sky-200/60 dark:border-themeBlue/25 text-[9px] font-black uppercase tracking-wider text-themeBlue dark:text-blue-300" title="Includes January Monthly Tuition">Incl. Jan</span>
                                                            @endif
                                                            <span class="font-mono font-black text-sm text-themeGreen dark:text-emerald-400">{{ $cellVal['amount'] }}</span>
                                                        </div>
                                                    @else
                                                        <span class="font-mono text-xs text-gray-300 dark:text-gray-600">—</span>
                                                    @endif
                                                @else
                                                    @if($cellVal !== null)
                                                        <span class="font-mono text-sm {{ $col['type'] === 'monthly' ? 'font-black text-themeBlue dark:text-blue-400' : 'font-bold text-gray-800 dark:text-gray-200' }}">{{ $cellVal }}</span>
                                                    @else
                                                        <span class="font-mono text-xs text-gray-300 dark:text-gray-600">—</span>
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="{{ 2 + $stdFeeCols->count() }}">
                                            <div class="py-10 text-center text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                                No active standard fee setup configured for the selected scope.
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif

    <!-- Bulk Mode View -->
    @if(request('mode') === 'bulk')
        <!-- Filters Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8" x-data="bulkCollectionSetup()">
            <form action="{{ route('fees.collection.index') }}" method="GET" @submit="window.dispatchEvent(new CustomEvent('trigger-loader'))">
                <input type="hidden" name="mode" value="bulk">
                <input type="hidden" name="branch_id" :value="form.branch_id">
                <input type="hidden" name="session_year_id" :value="form.session_year_id">
                <input type="hidden" name="class_id" :value="form.class_id">
                <input type="hidden" name="section_id" :value="form.section_id">
                <input type="hidden" name="fee_category_id" :value="form.fee_category_id">
                <input type="hidden" name="fee_month" :value="form.fee_month">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Branch Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Branch *</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="branchText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            @foreach($branches as $branch)
                                <button type="button" @click="selectBranch('{{ $branch->id }}', '{{ $branch->branch_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == '{{ $branch->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $branch->branch_name }}</span>
                                    <template x-if="form.branch_id == '{{ $branch->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Session Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'session') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Academic Session *</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'session' ? null : 'session'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="sessionText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            @foreach($sessions as $session)
                                <button type="button" @click="selectSession('{{ $session->id }}', '{{ $session->session_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id == '{{ $session->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $session->session_name }}</span>
                                    <template x-if="form.session_year_id == '{{ $session->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Class Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Class *</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="classText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            @foreach($classes as $class)
                                <button type="button" @click="selectClass('{{ $class->id }}', '{{ $class->class_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id == '{{ $class->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $class->class_name }}</span>
                                    <template x-if="form.class_id == '{{ $class->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Section Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'section') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Section (Optional)</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'section' ? null : 'section'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="sectionText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'section'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            @foreach($sections as $sec)
                                <button type="button" @click="selectSection('{{ $sec->id }}', '{{ $sec->section_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id == '{{ $sec->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $sec->section_name }}</span>
                                    <template x-if="form.section_id == '{{ $sec->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Fee Category Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'category') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Fee Category *</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'category' ? null : 'category'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="categoryText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'category'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            @foreach($categories as $cat)
                                <button type="button" @click="selectCategory('{{ $cat->id }}', '{{ $cat->name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="form.fee_category_id == '{{ $cat->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $cat->name }}</span>
                                    <template x-if="form.fee_category_id == '{{ $cat->id }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Month Dropdown (Optional) -->
                    <div class="relative" @click.away="if(activeDropdown === 'month') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Month (Optional)</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'month' ? null : 'month'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="monthText"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'month'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="selectMonth('', 'Choose Month')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-700 dark:text-gray-200 font-bold">
                                <span>Choose Month</span>
                            </button>
                            @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                                <button type="button" @click="selectMonth('{{ $month }}', '{{ $month }}')" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="form.fee_month == '{{ $month }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $month }}</span>
                                    <template x-if="form.fee_month == '{{ $month }}'">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('fees.collection.index', ['mode' => 'bulk']) }}" class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-[0.15em] flex items-center justify-center transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap" @click="window.dispatchEvent(new CustomEvent('trigger-loader'))">
                        Reset Filters
                    </a>
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-[0.15em] text-xs active:scale-95 flex items-center justify-center min-w-[200px]">
                        Load Invoices
                    </button>
                </div>
            </form>
        </div>

        @if(request()->filled(['branch_id', 'session_year_id', 'class_id', 'fee_category_id']))
            <form action="{{ route('fees.collection.bulk_students_store') }}" method="POST" id="bulkStudentsPaymentForm" 
                  x-data="{
                      selectedInvoices: [],
                      payingAmounts: {},
                      dueAmounts: {},
                      totalOutstanding: 0,
                      totalPaying: 0,
                      
                      init() {
                          @foreach($bulkInvoices as $inv)
                              this.dueAmounts['{{ $inv->id }}'] = {{ $inv->due_amount }};
                              this.payingAmounts['{{ $inv->id }}'] = {{ $inv->due_amount }};
                              this.selectedInvoices.push('{{ $inv->id }}');
                          @endforeach
                          this.recalculate();
                      },
                      
                      toggleInvoice(id) {
                          const idx = this.selectedInvoices.indexOf(id);
                          if (idx > -1) {
                              this.selectedInvoices.splice(idx, 1);
                          } else {
                              this.selectedInvoices.push(id);
                          }
                          this.recalculate();
                      },
                      
                      isAllSelected() {
                          return this.selectedInvoices.length === {{ $bulkInvoices->count() }};
                      },
                      
                      toggleAll() {
                          if (this.isAllSelected()) {
                              this.selectedInvoices = [];
                          } else {
                              this.selectedInvoices = [];
                              @foreach($bulkInvoices as $inv)
                                  this.selectedInvoices.push('{{ $inv->id }}');
                              @endforeach
                          }
                          this.recalculate();
                      },
                      
                      recalculate() {
                          let outSum = 0;
                          let paySum = 0;
                          this.selectedInvoices.forEach(id => {
                              outSum += parseFloat(this.dueAmounts[id] || 0);
                              paySum += parseFloat(this.payingAmounts[id] || 0);
                          });
                          this.totalOutstanding = outSum;
                          this.totalPaying = paySum;
                      }
                  }">
                @csrf
                <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                <input type="hidden" name="session_year_id" value="{{ request('session_year_id') }}">
                <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                <input type="hidden" name="section_id" value="{{ request('section_id') }}">
                <input type="hidden" name="fee_category_id" value="{{ request('fee_category_id') }}">
                <input type="hidden" name="fee_month" value="{{ request('fee_month') }}">

                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden relative mb-8">
                    <div class="p-6 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
                        <h3 class="text-sm font-black text-themeBlue uppercase tracking-wider flex items-center">
                            <svg class="w-5 h-5 mr-2 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Outstanding Invoices
                        </h3>
                        <div class="text-xs font-bold text-gray-550 bg-gray-50 dark:bg-themeDark px-4 py-2 rounded-xl border border-gray-100 dark:border-gray-800">
                            Found: <span class="text-themeBlue font-black">{{ $bulkInvoices->count() }}</span> Invoice(s)
                        </div>
                    </div>

                    <div class="px-6 pb-4 pt-1">
                        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                            <table class="w-full text-left border-collapse table [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-1 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-1 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-center w-10">
                                            <input type="checkbox" :checked="isAllSelected()" @click="toggleAll()" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 accent-themeBlue focus:ring-themeBlue cursor-pointer">
                                        </th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Student Details</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.18em] whitespace-nowrap">Invoice No</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-[0.18em] text-right whitespace-nowrap">Due Amt</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-themeGreen dark:text-themeGreen uppercase tracking-[0.18em] text-right w-44 whitespace-nowrap">Paying Amt (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bulkInvoices as $inv)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0 text-center">
                                            <input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" :checked="selectedInvoices.includes('{{ $inv->id }}')" @click="toggleInvoice('{{ $inv->id }}')" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 accent-themeBlue focus:ring-themeBlue cursor-pointer">
                                        </td>
                                        <td class="py-0 px-0 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $inv->student->student_name }}</span>
                                                @if(($inv->student->status ?? 'Active') === 'Inactive')
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                                        <span class="w-1 h-1 rounded-full bg-rose-500"></span> Inactive
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-themeBlue font-mono font-black mt-0.5">{{ $inv->student->student_identity }}</div>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            <div class="font-mono text-xs">{{ $inv->invoice_no }}</div>
                                            <div class="text-[10px] text-themeBlue font-black mt-0.5 uppercase">{{ $inv->month_name }}</div>
                                        </td>
                                        <td class="py-0 px-0 text-right font-black text-red-600 dark:text-red-400 text-sm font-mono whitespace-nowrap">৳ {{ number_format($inv->due_amount, 2) }}</td>
                                        <td class="py-0 px-0 text-right whitespace-nowrap">
                                            <input type="number" step="0.01" name="paying_amounts[{{ $inv->id }}]" x-model.number="payingAmounts['{{ $inv->id }}']" @input="recalculate()" class="w-full h-9 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-bold font-mono text-themeGreen dark:text-themeGreen px-3 text-right">
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-themeGreen dark:text-themeGreen font-black text-sm uppercase tracking-wider">
                                            <svg class="w-12 h-12 mx-auto mb-3 text-themeGreen opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            No outstanding invoices found matching these filters.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bulk Collection Summary Card (Bottom Sticky Floating Panel) -->
                @if($bulkInvoices->count() > 0)
                <div class="sticky bottom-4 z-40 bg-white/95 dark:bg-themeNavy/95 backdrop-blur-md border border-gray-155 dark:border-white/[0.08] px-6 py-4 rounded-2xl text-gray-900 dark:text-white no-print mt-8 shadow-[0_8px_30px_rgba(0,0,0,0.08)] flex flex-col md:flex-row md:items-center md:justify-between gap-4 animate-fade-in-up">
                    
                    <!-- Left: Summary Stats -->
                    <div class="flex flex-wrap items-center gap-6 divide-x divide-gray-100 dark:divide-white/[0.08]">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-555 block mb-0.5">Selected Dues</span>
                            <span class="text-lg font-black font-mono text-red-600 dark:text-red-400">৳ <span x-text="totalOutstanding.toFixed(2)">0.00</span></span>
                        </div>
                        <div class="pl-6">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-555 block mb-0.5">Paying Amount</span>
                            <span class="text-lg font-black font-mono text-themeGreen dark:text-themeGreen">৳ <span x-text="totalPaying.toFixed(2)">0.00</span></span>
                        </div>
                        <div class="pl-6">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-555 block mb-0.5">Selected Count</span>
                            <span class="text-sm font-black text-gray-800 dark:text-white"><span x-text="selectedInvoices.length">0</span> Student(s)</span>
                        </div>
                    </div>

                    <!-- Right: Payment Method & Submit Button -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                        <!-- Custom Alpine Dropdown for Bulk Payment Method -->
                        <div class="relative w-full sm:w-48">
                            <div x-data="{ 
                                open: false, 
                                value: 'Cash', 
                                label: 'Cash',
                                select(val) {
                                    this.value = val;
                                    this.label = val;
                                    this.open = false;
                                    let inp = this.$refs.hiddenInput;
                                    inp.value = val;
                                }
                            }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                                <button type="button" @click="open = !open" class="w-full h-10 px-4 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-bold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="label"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <input type="hidden" name="payment_method" x-ref="hiddenInput" value="Cash" required>
                                <div x-show="open" x-cloak class="absolute bottom-11 z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    <template x-for="opt in ['Cash', 'bKash', 'Nagad', 'Bank']" :key="opt">
                                        <button type="button" @click="select(opt)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == opt ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span x-text="opt"></span>
                                            <svg x-show="value == opt" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black h-10 px-8 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-[0.15em] text-xs active:scale-95 whitespace-nowrap flex items-center justify-center">
                            Collect Fees
                        </button>
                    </div>

                </div>
                @endif
            </form>
        @endif
    @endif
    </div>

    <!-- Skeleton Loading Block (Shown when loading) -->
    <div x-show="loading" x-cloak class="space-y-6">
        <!-- Skeleton Card 1 (Summary/Search State) -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm animate-pulse">
            <div class="h-4 w-1/4 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-4"></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl"></div>
                <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl"></div>
                <div class="h-10 bg-gray-200 dark:bg-gray-700/60 rounded-xl"></div>
            </div>
        </div>
        <!-- Skeleton Card 2 (Table/Invoices State) -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm overflow-hidden animate-pulse">
            <div class="p-6 border-b border-gray-100 dark:border-white/[0.05]">
                <div class="h-5 w-40 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
            </div>
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="w-16 border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4"><div class="h-3 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4"><div class="h-3 w-32 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4"><div class="h-3 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-right"><div class="h-3 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></th>
                            <th class="border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-right"><div class="h-3 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(range(1, 5) as $i)
                        <tr>
                            <td class="py-4 px-4"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                            <td class="py-4 px-4">
                                <div class="h-4 w-48 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-2"></div>
                                <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                            </td>
                            <td class="py-4 px-4"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                            <td class="py-4 px-4 text-right"><div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td class="py-4 px-4 text-right"><div class="h-5 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Collect Payment Single Modal -->
<div id="paymentModal" onclick="if (event.target === this) closePayModal()" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-2xl border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Collect Payment</h3>
            <button type="button" onclick="closePayModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <form action="{{ route('fees.collection.store') }}" method="POST" class="p-6 text-gray-900 dark:text-white" id="singlePaymentForm">
            @csrf
            <input type="hidden" name="invoice_id" id="modalInvoiceId">
            
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Fee Description</label>
                <div id="modalFeeName" class="text-sm font-bold text-gray-900 dark:text-white"></div>
            </div>

            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Total Due Amount</label>
                <div class="text-2xl font-black text-red-600 dark:text-red-400 font-mono">৳ <span id="modalDueAmountText">0.00</span></div>
            </div>

            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">Paying Amount (৳) <span class="text-red-500 ml-0.5">*</span></label>
                <input type="number" step="0.01" name="pay_amount" id="modalPayAmount" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-lg font-bold font-mono text-themeGreen dark:text-themeGreen px-3" required>
                <p class="text-[10px] text-gray-400 dark:text-gray-400 mt-1.5">You can receive partial payment by changing this amount.</p>
            </div>

            <!-- Custom Alpine Dropdown for Single Payment Method -->
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">Payment Method <span class="text-red-500">*</span></label>
                <div x-data="{ 
                    open: false, 
                    value: 'Cash', 
                    label: 'Cash',
                    select(val) {
                        this.value = val;
                        this.label = val;
                        this.open = false;
                        let inp = this.$refs.hiddenInput;
                        inp.value = val;
                    }
                }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                    <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="payment_method" x-ref="hiddenInput" value="Cash" required>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <template x-for="opt in ['Cash', 'bKash', 'Nagad', 'Bank']" :key="opt">
                            <button type="button" @click="select(opt)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == opt ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span x-text="opt"></span>
                                <svg x-show="value == opt" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">Trx ID / Note (Optional)</label>
                <input type="text" name="transaction_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 placeholder-gray-400" placeholder="e.g. 8N2K9DJ3">
            </div>

            <div class="mt-8 flex gap-3">
                <button type="button" onclick="closePayModal()" class="flex-1 h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                <button type="submit" class="flex-1 h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center active:scale-95">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Collect Admission Payment (Head-Wise & Lump-Sum) Modal -->
<div id="admissionPaymentModal"
     x-data="admissionPayModalComponent()"
     x-show="isOpen"
     x-cloak
     @open-admission-pay-modal.window="open($event.detail)"
     @keydown.escape.window="if (isOpen && !isSubmitting) close()"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-themeDark/50 backdrop-blur-md p-3 sm:p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-4xl max-h-[94vh] flex flex-col rounded-3xl shadow-2xl border border-gray-100 dark:border-white/[0.08] overflow-hidden" @click.away="!isSubmitting && close()">
        <div class="px-6 py-3.5 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between gap-4 flex-shrink-0">
            <div>
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">Collect Admission Fee</h3>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
                    <span class="font-mono font-black text-themeBlue" x-text="invoiceNo"></span>
                    <span class="mx-1.5 text-gray-300 dark:text-gray-700">|</span>
                    <span x-text="structureName"></span>
                </p>
            </div>
            <button type="button" @click="!isSubmitting && close()" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form @submit.prevent="submit()" class="p-5 overflow-y-auto space-y-3.5 text-gray-900 dark:text-white">
            <!-- Summary Counters -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-2xl border border-gray-100 dark:border-white/[0.06] bg-gray-50/70 dark:bg-themeDark/40 p-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Net Collectible Due</div>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <div class="text-base sm:text-lg font-black font-mono text-red-600 dark:text-red-400" x-text="'৳ ' + formatMoney(effectiveDuePaisa)"></div>
                        <span x-show="directDiscountPaisa > 0" class="text-xs font-mono font-bold text-gray-400 line-through" x-text="'৳' + formatMoney(totalDuePaisa)"></span>
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 dark:border-white/[0.06] bg-gray-50/70 dark:bg-themeDark/40 p-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Paying Now</div>
                    <div class="text-base sm:text-lg font-black font-mono text-themeGreen mt-0.5" x-text="'৳ ' + formatMoney(payingTotalPaisa)"></div>
                </div>
                <div class="rounded-2xl border border-gray-100 dark:border-white/[0.06] bg-gray-50/70 dark:bg-themeDark/40 p-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Remaining After</div>
                    <div class="text-base sm:text-lg font-black font-mono text-gray-800 dark:text-gray-200 mt-0.5" x-text="'৳ ' + formatMoney(Math.max(0, effectiveDuePaisa - payingTotalPaisa))"></div>
                </div>
            </div>

            <!-- Allocation Mode Toggle & Quick Action (Auto Split First & Default) -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-xl border border-gray-150 dark:border-white/[0.08] bg-gray-50/70 dark:bg-themeDark/40 p-1">
                    <button type="button"
                            @click="allocationMode = 'lump_sum'"
                            class="px-3.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                            :class="allocationMode === 'lump_sum' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'">
                        Auto Split
                    </button>
                    <button type="button"
                            @click="allocationMode = 'per_head'"
                            class="px-3.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                            :class="allocationMode === 'per_head' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'">
                        Per-Head Allocation
                    </button>
                </div>
                <button type="button"
                        @click="fillFullDue()"
                        class="h-8 px-3.5 rounded-xl border-2 border-themeBlue/20 bg-themeBlue/10 text-themeBlue text-[10px] font-black uppercase tracking-widest hover:bg-themeBlue hover:text-white transition-all"
                        x-text="(allocationMode === 'lump_sum' && hasTuitionHead && !includeTuitionInAutoSplit && pendingTuitionDuePaisa > 0) ? 'Pay Non-Tuition Due' : 'Pay Full Due'">
                </button>
            </div>

            <!-- Per-Head Table -->
            <div x-show="allocationMode === 'per_head'" class="w-full min-w-0 rounded-2xl border border-gray-100 dark:border-white/[0.06] overflow-hidden">
                <div class="table-container w-full max-w-full max-h-60 overflow-y-auto bg-transparent !border-none !shadow-none !mt-0 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-4 [&>thead>tr>th:last-child]:!pr-4 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr>td]:!py-2 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-4 [&>tbody>tr>td:last-child]:!pr-4 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:hover>td]:!bg-gray-50/70 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35 [&>tbody>tr>td.col-due]:!text-red-600 dark:[&>tbody>tr>td.col-due]:!text-red-400 [&>tbody>tr>td.col-muted]:!text-gray-600 dark:[&>tbody>tr>td.col-muted]:!text-gray-400">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Fee Head</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right whitespace-nowrap">Net</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right whitespace-nowrap">Paid</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right whitespace-nowrap">Due</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right whitespace-nowrap">Collect Now (৳)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                            <template x-for="head in heads" :key="head.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="head.head_name"></span>
                                            <span x-show="head.is_tuition_head" class="px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-md bg-themeBlue/10 text-themeBlue">Tuition</span>
                                            <span x-show="head.is_discountable" class="px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-md bg-amber-100/80 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">Discountable</span>
                                        </div>
                                        <div class="text-[11px] font-medium text-gray-500 dark:text-gray-400" x-text="head.category_name"></div>
                                    </td>
                                    <td class="py-0 px-0 col-muted text-right text-xs font-mono font-bold whitespace-nowrap" x-text="'৳' + head.net_amount"></td>
                                    <td class="py-0 px-0 col-muted text-right text-xs font-mono font-bold whitespace-nowrap" x-text="'৳' + head.allocated_paid_amount"></td>
                                    <td class="py-0 px-0 col-due text-right text-xs font-mono font-black whitespace-nowrap">
                                        <span x-text="'৳' + formatMoney(effectiveHeadDuePaisa(head))"></span>
                                        <span x-show="(discountShareByHeadId[head.id] || 0) > 0" class="block text-[10px] font-bold text-gray-400 line-through" x-text="'৳' + head.due_amount"></span>
                                    </td>
                                    <td class="py-0 px-0 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   :max="formatMoney(effectiveHeadDuePaisa(head))"
                                                   :disabled="effectiveHeadDuePaisa(head) <= 0"
                                                   x-model="head.pay_input"
                                                   class="w-24 sm:w-28 h-9 px-2.5 text-right text-xs font-mono font-black border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/60 dark:bg-themeDark focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all">
                                            <button type="button"
                                                    x-show="effectiveHeadDuePaisa(head) > 0"
                                                    @click="head.pay_input = formatMoney(effectiveHeadDuePaisa(head))"
                                                    class="px-2.5 h-9 rounded-lg border border-gray-200 dark:border-white/[0.08] text-[10px] font-black uppercase tracking-wider text-themeBlue hover:bg-themeBlue/10 transition-colors">
                                                Max
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Auto Split Mode -->
            <div x-show="allocationMode === 'lump_sum'" class="space-y-3">
                <div class="rounded-2xl border border-gray-100 dark:border-white/[0.06] bg-gray-50/60 dark:bg-themeDark/30 p-3.5 space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase block">
                            Total Paying Amount (৳) <span x-show="directDiscountPaisa <= 0" class="text-red-500">*</span>
                        </label>
                        <span x-show="hasTuitionHead && !includeTuitionInAutoSplit && pendingTuitionDuePaisa > 0"
                              x-cloak
                              class="px-2 py-0.5 rounded-lg bg-themeBlue/10 text-themeBlue text-[10px] font-mono font-black">
                            Non-Tuition Max: ৳<span x-text="formatMoney(autoSplitMaxPaisa)"></span>
                        </span>
                    </div>
                    <input type="number"
                           step="0.01"
                           :min="directDiscountPaisa > 0 ? '0' : '0.01'"
                           :max="formatMoney(autoSplitMaxPaisa)"
                           x-model="lumpAmount"
                           class="w-full h-10 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 text-base font-mono font-black text-themeGreen transition-all">
                    <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400"
                       x-text="(hasTuitionHead && !includeTuitionInAutoSplit && pendingTuitionDuePaisa > 0)
                           ? ('Monthly Tuition Fee (৳' + formatMoney(pendingTuitionDuePaisa) + ' due) is excluded — Auto Split allocates across pending non-tuition admission heads only.')
                           : 'Auto Split allocates your paying amount accurately across pending admission heads in package sequence order.'">
                    </p>
                </div>

                <!-- Monthly Tuition Fee Select / Unselect Option in Auto Split -->
                <label x-show="hasTuitionHead"
                       x-cloak
                       class="flex items-center justify-between gap-3 rounded-2xl border px-3.5 py-2.5 transition-all select-none"
                       :class="pendingTuitionDuePaisa <= 0
                           ? 'border-gray-150 dark:border-white/[0.06] bg-gray-50/60 dark:bg-themeDark/30 opacity-75 cursor-not-allowed'
                           : (includeTuitionInAutoSplit
                               ? 'border-themeBlue/30 bg-sky-50/60 dark:bg-themeBlue/10 cursor-pointer hover:border-themeBlue/50'
                               : 'border-gray-200 dark:border-white/[0.08] bg-gray-50/70 dark:bg-themeDark/40 cursor-pointer hover:border-gray-300 dark:hover:border-white/[0.14]')">
                    <div class="flex items-center gap-3 min-w-0">
                        <input type="checkbox"
                               id="includeTuitionInAutoSplitCheckbox"
                               x-model="includeTuitionInAutoSplit"
                               @change="onTuitionAutoSplitToggle()"
                               :disabled="pendingTuitionDuePaisa <= 0"
                               class="w-4 h-4 rounded-md border-2 border-gray-300 dark:border-gray-600 text-themeBlue focus:ring-4 focus:ring-themeBlue/15 transition-all cursor-pointer disabled:cursor-not-allowed">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-black text-gray-900 dark:text-white tracking-tight">Include Monthly Tuition Fee</span>
                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider"
                                      :class="pendingTuitionDuePaisa <= 0
                                          ? 'bg-emerald-100 dark:bg-themeGreen/20 text-themeGreen'
                                          : (includeTuitionInAutoSplit ? 'bg-themeBlue/15 text-themeBlue' : 'bg-gray-200/80 dark:bg-gray-800 text-gray-600 dark:text-gray-400')"
                                      x-text="pendingTuitionDuePaisa <= 0 ? 'Settled' : (includeTuitionInAutoSplit ? 'Included' : 'Excluded')"></span>
                            </div>
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400 truncate" x-text="tuitionHeadNames"></p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-xs font-mono font-black whitespace-nowrap"
                             :class="pendingTuitionDuePaisa <= 0 ? 'text-themeGreen' : (includeTuitionInAutoSplit ? 'text-themeBlue' : 'text-gray-500 dark:text-gray-400 line-through')"
                             x-text="'৳ ' + formatMoney(pendingTuitionDuePaisa)"></div>
                        <div class="text-[9px] font-black uppercase tracking-wider text-gray-400"
                             x-text="pendingTuitionDuePaisa <= 0 ? 'Paid' : 'Tuition Due'"></div>
                    </div>
                </label>
            </div>

            <!-- Admin-Only Direct Discount Option (Strict is_discountable Package Rule) -->
            <div x-show="canGrantDiscount" x-cloak class="rounded-2xl border border-amber-200/80 dark:border-amber-500/20 bg-amber-50/40 dark:bg-amber-950/15 p-3.5 space-y-2.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-black tracking-widest text-amber-800 dark:text-amber-300 uppercase">Direct Discount (৳)</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold font-mono"
                              :class="eligibleDiscountablePaisa > 0 ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-900 dark:text-amber-200' : 'bg-gray-100 dark:bg-themeDark text-gray-500 dark:text-gray-400'"
                              x-text="eligibleDiscountablePaisa > 0 ? ('Eligible Due: ৳' + formatMoney(eligibleDiscountablePaisa)) : 'Eligible Due: ৳0.00 (No discountable heads)'"></span>
                    </div>
                    <div class="flex items-center gap-2" x-show="eligibleDiscountablePaisa > 0">
                        <button type="button"
                                @click="applyDiscountOnly()"
                                class="px-2.5 h-7 rounded-lg border border-amber-300/80 dark:border-amber-500/30 bg-white dark:bg-themeNavy text-[10px] font-black uppercase tracking-wider text-amber-700 dark:text-amber-300 hover:bg-amber-100/70 transition-colors">
                            Discount Only (৳0 Pay)
                        </button>
                        <button type="button"
                                x-show="directDiscountPaisa > 0"
                                @click="clearDirectDiscount()"
                                class="px-2.5 h-7 rounded-lg border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-themeNavy text-[10px] font-black uppercase tracking-wider text-gray-500 hover:text-red-500 transition-colors">
                            Clear
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Discount Amount (৳)</label>
                        <input type="number"
                               step="0.01"
                               min="0"
                               :max="formatMoney(eligibleDiscountablePaisa)"
                               :disabled="eligibleDiscountablePaisa <= 0"
                               x-model="directDiscountInput"
                               @input="onDirectDiscountInput()"
                               placeholder="0.00"
                               class="w-full h-10 px-3 border-2 border-amber-200/70 dark:border-gray-800 rounded-xl bg-white dark:bg-themeDark focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 text-sm font-mono font-black text-amber-700 dark:text-amber-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Discount Reason (Optional)</label>
                        <input type="text"
                               :disabled="eligibleDiscountablePaisa <= 0"
                               x-model="discountReason"
                               placeholder="e.g. Principal approved admission discount"
                               class="w-full h-10 px-3 border-2 border-amber-200/70 dark:border-gray-800 rounded-xl bg-white dark:bg-themeDark focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 text-xs font-semibold text-gray-800 dark:text-gray-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Payment Metadata Fields -->
            <div x-show="payingTotalPaisa === 0 && directDiscountPaisa > 0" x-cloak class="rounded-xl border border-blue-200/70 dark:border-themeBlue/30 bg-blue-50/50 dark:bg-themeBlue/10 px-3.5 py-2 text-xs font-semibold text-themeBlue">
                Discount-Only Mode: ৳0.00 cash will be collected, and only the Direct Discount audit adjustment will update this invoice.
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" :class="payingTotalPaisa === 0 && directDiscountPaisa > 0 ? 'opacity-50 pointer-events-none' : ''">
                <!-- Custom Alpine Payment Method Dropdown (Rule 7) -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Payment Method <span class="text-red-500">*</span></label>
                    <div class="relative w-full" @click.away="methodOpen = false">
                        <button type="button" @click="methodOpen = !methodOpen" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="paymentMethod"></span>
                            <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="methodOpen" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="opt in paymentMethods" :key="opt">
                                <button type="button" @click="paymentMethod = opt; methodOpen = false" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="paymentMethod === opt ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="opt"></span>
                                    <svg x-show="paymentMethod === opt" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Custom Alpine Date Picker (Rule 10) -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Payment Date <span class="text-red-500">*</span></label>
                    <div class="relative" x-data="datePicker(paymentDate)" @click.away="show = false" @date-selected="if($event.detail) paymentDate = $event.detail">
                        <button type="button" @click="show = !show" class="w-full h-10 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            <span x-text="paymentDate || 'Select Date'" class="truncate"></span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>
                        <div x-show="show" x-cloak class="absolute z-50 right-0 sm:left-0 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-2xl p-4">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="relative" @click.away="mOpen = false">
                                    <button type="button" @click="mOpen = !mOpen" class="px-2.5 py-1.5 rounded-lg bg-gray-50 dark:bg-themeDark border border-gray-150 dark:border-white/[0.08] text-xs font-black text-gray-800 dark:text-gray-200 flex items-center gap-1">
                                        <span x-text="months[month]"></span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="mOpen" x-cloak class="absolute z-50 left-0 mt-1 w-36 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-xl shadow-xl py-1 max-h-48 overflow-y-auto">
                                        <template x-for="(mName, mIdx) in months" :key="mIdx">
                                            <button type="button" @click="month = mIdx; mOpen = false; calculateDays()" class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45" :class="month === mIdx ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                                <span x-text="mName"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <input type="number" x-model.number="year" @input="calculateDays()" class="w-20 h-8 px-2 text-center text-xs font-black rounded-lg border border-gray-150 dark:border-white/[0.08] bg-gray-50 dark:bg-themeDark text-gray-800 dark:text-gray-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black text-gray-400 uppercase mb-1">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                <template x-for="(blank, bi) in blankDays" :key="'b'+bi">
                                    <div></div>
                                </template>
                                <template x-for="day in daysInMonth" :key="day">
                                    <button type="button" @click="selectDay(day); $dispatch('date-selected', formatted)" class="h-8 w-8 rounded-lg font-bold transition-colors flex items-center justify-center" :class="isSelected(day) ? 'bg-themeBlue text-white font-black' : 'hover:bg-gray-100 dark:hover:bg-themeDark/50 text-gray-700 dark:text-gray-200'" x-text="day"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Transaction ID (Optional)</label>
                    <input type="text" x-model="transactionId" class="w-full h-10 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 text-sm font-semibold text-gray-800 dark:text-gray-200 transition-all" placeholder="e.g. 8N2K9DJ3">
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Collection Note (Optional)</label>
                    <input type="text" x-model="note" class="w-full h-10 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 text-sm font-semibold text-gray-800 dark:text-gray-200 transition-all" placeholder="Optional counter note">
                </div>
            </div>

            <div class="pt-1 flex gap-3">
                <button type="button" @click="!isSubmitting && close()" class="flex-1 h-10 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                <button type="submit" :disabled="isSubmitting" class="flex-1 h-10 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center active:scale-95 disabled:opacity-60">
                    <span x-text="isSubmitting ? 'Processing...' : (directDiscountPaisa > 0 ? (payingTotalPaisa > 0 ? 'Apply Discount & Collect' : 'Apply Direct Discount') : 'Confirm Collection')"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Adjust Monthly Fee Modal (Single Month or Continuous) -->
<div id="adjustFeeModal" 
     x-data="adjustFeeComponent()" 
     x-show="isOpen" 
     x-cloak 
     @open-adjust-modal.window="initModal($event.detail)"
     @keydown.escape.window="closeModal()"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-md p-4 transition-opacity">
    <div class="bg-white dark:bg-themeNavy w-full max-w-lg rounded-3xl shadow-2xl border border-gray-150 dark:border-white/[0.08] overflow-hidden relative" @click.away="closeModal()">
        
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex justify-between items-center bg-gray-50/50 dark:bg-themeDark/40">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-2xl bg-themeBlue/10 text-themeBlue flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                        Adjust Fee Amount
                    </h3>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
                        <span x-text="feeName" class="text-themeBlue"></span> &bull; <span x-text="monthName" class="font-bold"></span>
                    </p>
                </div>
            </div>
            <button @click="closeModal()" type="button" class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Modal Content Form -->
        <div class="p-6 text-gray-900 dark:text-white">
            
            <!-- Summary Stats Strip -->
            <div class="grid grid-cols-3 gap-2.5 p-3 rounded-2xl bg-gray-50 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.04] mb-5">
                <div class="text-center">
                    <span class="text-[9px] font-black uppercase tracking-wider text-gray-400 block">Current Bill</span>
                    <span class="text-xs sm:text-sm font-black font-mono text-gray-700 dark:text-gray-300">৳ <span x-text="netAmount.toFixed(2)"></span></span>
                </div>
                <div class="text-center border-x border-gray-200 dark:border-white/[0.08]">
                    <span class="text-[9px] font-black uppercase tracking-wider text-gray-400 block">Already Paid</span>
                    <span class="text-xs sm:text-sm font-black font-mono text-themeGreen">৳ <span x-text="paidAmount.toFixed(2)"></span></span>
                </div>
                <div class="text-center">
                    <span class="text-[9px] font-black uppercase tracking-wider text-gray-400 block">Current Due</span>
                    <span class="text-xs sm:text-sm font-black font-mono text-red-500">৳ <span x-text="currentDue.toFixed(2)"></span></span>
                </div>
            </div>

            <!-- Action Tab Switcher: Shown ONLY for eligible unpaid Monthly Fee invoices -->
            <div x-show="canManageLateFine" x-cloak class="mb-5">
                <div class="grid grid-cols-2 gap-2 p-1 bg-gray-100 dark:bg-themeDark rounded-2xl border border-gray-200/60 dark:border-white/[0.04]">
                    <button type="button"
                            @click="activeTab = 'late_fine'"
                            :class="activeTab === 'late_fine' ? 'bg-white dark:bg-themeNavy text-amber-600 dark:text-amber-400 shadow-sm font-black' : 'text-gray-500 dark:text-gray-400 font-semibold hover:text-gray-900'"
                            class="py-2.5 px-3 rounded-xl text-xs uppercase tracking-wider transition-all"
                            x-text="currentFine > 0 ? 'Update Late Fine' : 'Add Late Fine'">
                    </button>
                    <button type="button"
                            @click="activeTab = 'adjust_fee'"
                            :class="activeTab === 'adjust_fee' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm font-black' : 'text-gray-500 dark:text-gray-400 font-semibold hover:text-gray-900'"
                            class="py-2.5 px-3 rounded-xl text-xs uppercase tracking-wider transition-all">
                        Adjust Tuition Fee
                    </button>
                </div>
            </div>

            <!-- TAB 1: ADD / UPDATE LATE FINE (Embedded on Monthly Fee Row — Strictly Monthly Fee Only) -->
            <div x-show="canManageLateFine && activeTab === 'late_fine'" x-cloak>
                <div class="mb-4 p-3 rounded-2xl bg-amber-50/70 dark:bg-amber-500/10 border border-amber-200/70 dark:border-amber-500/25 text-xs text-amber-800 dark:text-amber-200 font-medium">
                    Applies an embedded <span class="font-black">Late Fine</span> directly on <span x-text="monthName" class="font-black"></span>'s Monthly Fee row (reported under Other Fees so Monthly Tuition stays pure).
                </div>

                <div class="mb-4">
                    <label class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase mb-2 block">
                        Late Fine Amount (৳) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2 mb-2.5">
                        <button type="button" @click="fineAmount = '20'" :class="String(fineAmount) === '20' ? 'bg-themeBlue text-white border-themeBlue' : 'bg-gray-50 dark:bg-themeDark text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-themeBlue'" class="px-3.5 py-1.5 rounded-xl border-2 text-xs font-black font-mono transition-all">+৳20</button>
                        <button type="button" @click="fineAmount = '40'" :class="String(fineAmount) === '40' ? 'bg-themeBlue text-white border-themeBlue' : 'bg-gray-50 dark:bg-themeDark text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-themeBlue'" class="px-3.5 py-1.5 rounded-xl border-2 text-xs font-black font-mono transition-all">+৳40</button>
                        <button type="button" @click="fineAmount = '50'" :class="String(fineAmount) === '50' ? 'bg-themeBlue text-white border-themeBlue' : 'bg-gray-50 dark:bg-themeDark text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-themeBlue'" class="px-3.5 py-1.5 rounded-xl border-2 text-xs font-black font-mono transition-all">+৳50</button>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 font-mono font-bold text-sm">৳</span>
                        <input type="number"
                               step="0.01"
                               min="1"
                               max="10000"
                               id="lateFineAmountInput"
                               x-model="fineAmount"
                               placeholder="20.00"
                               class="w-full h-11 pl-9 pr-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-base font-bold font-mono text-gray-900 dark:text-white shadow-sm">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase mb-2 block">
                        Reason / Remarks <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           maxlength="255"
                           x-model="fineReason"
                           placeholder="e.g. Late payment fine"
                           class="w-full h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-900 dark:text-white shadow-sm">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button"
                            @click="closeModal()"
                            :disabled="submitting"
                            class="flex-1 h-11 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">
                        Cancel
                    </button>
                    <button type="button"
                            @click="submitLateFine()"
                            :disabled="submitting"
                            class="flex-1 h-11 px-4 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-wider flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50">
                        <span x-show="!submitting" x-text="currentFine > 0 ? 'Update Late Fine' : 'Add Late Fine'">Add Late Fine</span>
                        <span x-show="submitting" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>

            <!-- TAB 2: ADJUST TUITION FEE (Existing Single / Continuous Adjustments) -->
            <div x-show="activeTab === 'adjust_fee'" x-cloak>
            <!-- Mode Selector Tabs -->
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase mb-2 block">Select Adjustment Mode</label>
                <div class="grid grid-cols-3 gap-2 p-1 bg-gray-100 dark:bg-themeDark rounded-2xl border border-gray-200/60 dark:border-white/[0.04]">
                    <button type="button" 
                            @click="mode = 'increase'" 
                            :class="mode === 'increase' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm font-black' : 'text-gray-500 dark:text-gray-400 font-semibold hover:text-gray-900'"
                            class="py-2 px-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Increase (+)
                    </button>
                    <button type="button" 
                            @click="mode = 'decrease'" 
                            :class="mode === 'decrease' ? 'bg-white dark:bg-themeNavy text-rose-500 shadow-sm font-black' : 'text-gray-500 dark:text-gray-400 font-semibold hover:text-gray-900'"
                            class="py-2 px-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/></svg>
                        Decrease (-)
                    </button>
                    <button type="button" 
                            @click="mode = 'set_amount'" 
                            :class="mode === 'set_amount' ? 'bg-white dark:bg-themeNavy text-themeGreen shadow-sm font-black' : 'text-gray-500 dark:text-gray-400 font-semibold hover:text-gray-900'"
                            class="py-2 px-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                        <svg class="w-3.5 h-3.5 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Set Exact (=)
                    </button>
                </div>
            </div>

            <!-- Amount Input Field -->
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase mb-2 block">
                    <span x-show="mode === 'increase'">Extra / Increase Amount (৳)</span>
                    <span x-show="mode === 'decrease'">Discount / Reduction Amount (৳)</span>
                    <span x-show="mode === 'set_amount'">New Net Bill Amount (৳)</span>
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 font-mono font-bold text-sm">৳</span>
                    <input type="number" 
                           step="0.01" 
                           min="0"
                           id="adjustAmountInput"
                           x-model="adjustAmount" 
                           placeholder="0.00" 
                           class="w-full h-11 pl-9 pr-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-base font-bold font-mono text-gray-900 dark:text-white shadow-sm"
                           required>
                </div>
            </div>

            <!-- Live Preview Calculation Box -->
            <div class="mb-5 p-3.5 rounded-2xl border transition-all" 
                 :class="mode === 'increase' ? 'bg-blue-50/50 dark:bg-themeBlue/10 border-blue-200/60 dark:border-blue-900/40' : (mode === 'decrease' ? 'bg-amber-50/50 dark:bg-amber-950/20 border-amber-200/60 dark:border-amber-900/40' : 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200/60 dark:border-emerald-900/40')">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-600 dark:text-gray-400">Resulting Due Amount:</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 line-through font-mono">৳ <span x-text="currentDue.toFixed(2)"></span></span>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        <span class="text-base font-black font-mono text-themeGreen dark:text-themeGreen">৳ <span x-text="previewDueAmount.toFixed(2)"></span></span>
                    </div>
                </div>
            </div>

            <!-- Application Scope Choice Cards -->
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase mb-2 block">Application Scope</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div @click="scope = 'single'" 
                         :class="scope === 'single' ? 'border-themeBlue bg-indigo-50/40 dark:bg-themeBlue/10 text-themeBlue ring-2 ring-themeBlue/20' : 'border-gray-200 dark:border-white/[0.06] hover:bg-gray-50 dark:hover:bg-themeDark/40 text-gray-700 dark:text-gray-300'"
                         class="p-3 border-2 rounded-2xl cursor-pointer transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider">This Month Only</span>
                            <span :class="scope === 'single' ? 'bg-themeBlue text-white' : 'border border-gray-300 dark:border-gray-600'" class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold">
                                <span x-show="scope === 'single'">✓</span>
                            </span>
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 leading-tight">One-time change for <span x-text="monthName" class="font-bold"></span>. Future months stay unchanged.</p>
                    </div>

                    <div @click="scope = 'continuous'" 
                         :class="scope === 'continuous' ? 'border-themeBlue bg-indigo-50/40 dark:bg-themeBlue/10 text-themeBlue ring-2 ring-themeBlue/20' : 'border-gray-200 dark:border-white/[0.06] hover:bg-gray-50 dark:hover:bg-themeDark/40 text-gray-700 dark:text-gray-300'"
                         class="p-3 border-2 rounded-2xl cursor-pointer transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider">Continuous Fee</span>
                            <span :class="scope === 'continuous' ? 'bg-themeBlue text-white' : 'border border-gray-300 dark:border-gray-600'" class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold">
                                <span x-show="scope === 'continuous'">✓</span>
                            </span>
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 leading-tight">Updates student's monthly fee profile for all eligible unpaid months.</p>
                    </div>
                </div>
            </div>

            <!-- Continuous Confirmation Checkbox Warning -->
            <div x-show="scope === 'continuous'" x-cloak class="mb-5 p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300">
                <div class="flex items-start gap-2.5">
                    <input type="checkbox" id="continuousConfirmCheck" x-model="continuousConfirmed" class="w-4 h-4 mt-0.5 text-themeBlue rounded border-gray-300 dark:border-gray-700 focus:ring-themeBlue cursor-pointer">
                    <label for="continuousConfirmCheck" class="text-xs font-semibold cursor-pointer">
                        I confirm that I want to update this student's continuous monthly fee profile for all eligible zero-paid upcoming invoices.
                    </label>
                </div>
            </div>

            <!-- Required Reason / Remarks Field -->
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase mb-2 block">
                    Reason / Remarks <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       maxlength="255"
                       x-model="reason"
                       required
                       placeholder="e.g. Principal approved scholarship discount, extra coaching fee"
                       class="w-full h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-900 dark:text-white shadow-sm">
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3 pt-2">
                <button type="button" 
                        @click="closeModal()" 
                        :disabled="submitting"
                        class="flex-1 h-11 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">
                    Cancel
                </button>
                <button type="button" 
                        @click="submitAdjustment()" 
                        :disabled="submitting"
                        class="flex-1 h-11 px-4 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-wider flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50">
                    <span x-show="!submitting">Apply Adjustment</span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Saving...
                    </span>
                </button>
            </div>
            </div>

        </div>
    </div>
</div>

<!-- Waive Unpaid Late Fine Modal -->
<div id="waiveFineModal"
     x-data="waiveFineComponent()"
     x-show="isOpen"
     x-cloak
     @open-waive-fine-modal.window="initModal($event.detail)"
     @keydown.escape.window="closeModal()"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-md p-4 transition-opacity">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-2xl border border-gray-150 dark:border-white/[0.08] overflow-hidden relative" @click.away="closeModal()">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex justify-between items-center bg-gray-50/50 dark:bg-themeDark/40">
            <div>
                <h3 class="text-base font-black text-gray-900 dark:text-white tracking-tight">Waive Late Fine</h3>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
                    <span x-text="invoiceNo" class="font-mono text-themeBlue"></span> &bull; <span x-text="monthName"></span>
                </p>
            </div>
            <button @click="closeModal()" type="button" class="w-8 h-8 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="p-6 text-gray-900 dark:text-white">
            <div class="mb-4 p-3.5 rounded-2xl bg-rose-50/70 dark:bg-rose-500/10 border border-rose-200/70 dark:border-rose-500/25 flex items-center justify-between">
                <span class="text-xs font-bold text-rose-700 dark:text-rose-300">Unpaid Fine Amount to Waive</span>
                <span class="text-base font-black font-mono text-rose-600 dark:text-rose-400">৳ <span x-text="dueAmount.toFixed(2)"></span></span>
            </div>

            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase mb-2 block">
                    Waiver Reason <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="waiveFineReasonInput"
                       maxlength="255"
                       x-model="reason"
                       placeholder="e.g. Principal approved late fine waiver"
                       class="w-full h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-900 dark:text-white shadow-sm">
            </div>

            <div class="flex items-center gap-3">
                <button type="button" @click="closeModal()" :disabled="submitting" class="flex-1 h-11 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                <button type="button" @click="submitWaive()" :disabled="submitting" class="flex-1 h-11 px-4 bg-rose-600 hover:bg-rose-700 text-white text-xs font-black rounded-xl shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-wider disabled:opacity-50">
                    <span x-show="!submitting">Confirm Waiver</span>
                    <span x-show="submitting">Processing...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openAdjustModal(invoiceId) {
        const btn = document.getElementById(`adjust-btn-${invoiceId}`);
        if (!btn) return;

        window.dispatchEvent(new CustomEvent('open-adjust-modal', {
            detail: {
                id: invoiceId,
                feeName: btn.dataset.feeName,
                monthName: btn.dataset.monthName,
                setupAmount: parseFloat(btn.dataset.setupAmount) || 0,
                netAmount: parseFloat(btn.dataset.netAmount) || 0,
                fineAmount: parseFloat(btn.dataset.fineAmount) || 0,
                fineReason: btn.dataset.fineReason || '',
                paidAmount: parseFloat(btn.dataset.paidAmount) || 0,
                dueAmount: parseFloat(btn.dataset.dueAmount) || 0,
                canManageLateFine: btn.dataset.canManageLateFine === '1',
                lateFineUrl: btn.dataset.lateFineUrl || ''
            }
        }));
    }

    function openWaiveFineModal(invoiceId, invoiceNo, monthName, dueAmount, waiveUrl) {
        window.dispatchEvent(new CustomEvent('open-waive-fine-modal', {
            detail: {
                id: invoiceId,
                invoiceNo: invoiceNo,
                monthName: monthName,
                dueAmount: parseFloat(dueAmount) || 0,
                waiveUrl: waiveUrl
            }
        }));
    }

    function waiveFineComponent() {
        return {
            isOpen: false,
            submitting: false,
            invoiceId: null,
            invoiceNo: '',
            monthName: '',
            dueAmount: 0,
            waiveUrl: '',
            reason: '',

            initModal(data) {
                this.invoiceId = data.id;
                this.invoiceNo = data.invoiceNo || '';
                this.monthName = data.monthName || '';
                this.dueAmount = parseFloat(data.dueAmount) || 0;
                this.waiveUrl = data.waiveUrl || '';
                this.reason = '';
                this.submitting = false;
                this.isOpen = true;
                this.$nextTick(() => {
                    const inp = document.getElementById('waiveFineReasonInput');
                    if (inp) inp.focus();
                });
            },

            closeModal() {
                if (this.submitting) return;
                this.isOpen = false;
            },

            async submitWaive() {
                if (!this.invoiceId || !this.waiveUrl) return;
                const cleanReason = String(this.reason || '').trim();
                if (cleanReason.length < 3) {
                    showDanger('Reason Required', 'Please enter a waiver reason (at least 3 characters).');
                    return;
                }

                this.submitting = true;
                try {
                    const response = await fetch(this.waiveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ reason: cleanReason })
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.isOpen = false;
                        await showSuccess(data.message || 'Late fine waived successfully.');
                        window.location.reload();
                    } else {
                        showDanger('Waiver Failed', data.message || 'Failed to waive late fine.');
                    }
                } catch (err) {
                    console.error(err);
                    showDanger('Error', 'An error occurred while waiving the late fine.');
                } finally {
                    this.submitting = false;
                }
            }
        };
    }

    function adjustFeeComponent() {
        return {
            isOpen: false,
            submitting: false,
            canManageLateFine: false,
            activeTab: 'adjust_fee',
            invoiceId: null,
            feeName: '',
            monthName: '',
            setupAmount: 0,
            netAmount: 0,
            currentFine: 0,
            paidAmount: 0,
            currentDue: 0,
            lateFineUrl: '',
            fineAmount: '20',
            fineReason: 'Late payment fine',
            mode: 'increase',
            adjustAmount: '',
            scope: 'single',
            reason: '',
            continuousConfirmed: false,

            initModal(data) {
                this.invoiceId = data.id;
                this.feeName = data.feeName;
                this.monthName = data.monthName;
                this.setupAmount = parseFloat(data.setupAmount) || 0;
                this.netAmount = parseFloat(data.netAmount) || 0;
                this.currentFine = parseFloat(data.fineAmount) || 0;
                this.paidAmount = parseFloat(data.paidAmount) || 0;
                this.currentDue = parseFloat(data.dueAmount) || 0;
                this.canManageLateFine = Boolean(data.canManageLateFine);
                this.lateFineUrl = data.lateFineUrl || '';
                this.activeTab = this.canManageLateFine ? 'late_fine' : 'adjust_fee';
                this.fineAmount = this.currentFine > 0 ? String(this.currentFine) : '20';
                this.fineReason = data.fineReason || 'Late payment fine';
                this.mode = 'increase';
                this.adjustAmount = '';
                this.scope = 'single';
                this.reason = '';
                this.continuousConfirmed = false;
                this.submitting = false;
                this.isOpen = true;
                this.$nextTick(() => {
                    const targetId = (this.canManageLateFine && this.activeTab === 'late_fine') ? 'lateFineAmountInput' : 'adjustAmountInput';
                    const inp = document.getElementById(targetId);
                    if (inp) inp.focus();
                });
            },

            closeModal() {
                if (this.submitting) return;
                this.isOpen = false;
            },

            async submitLateFine() {
                if (!this.invoiceId || !this.lateFineUrl) return;
                const val = parseFloat(this.fineAmount);
                if (isNaN(val) || val < 1 || val > 10000) {
                    showDanger('Invalid Fine Amount', 'Please enter a valid late fine amount between ৳1.00 and ৳10,000.00.');
                    return;
                }
                const cleanReason = String(this.fineReason || '').trim();
                if (cleanReason.length < 3) {
                    showDanger('Reason Required', 'Please provide a reason (at least 3 characters) for this late fine.');
                    return;
                }

                this.submitting = true;
                try {
                    const response = await fetch(this.lateFineUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            amount: val,
                            reason: cleanReason
                        })
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.isOpen = false;
                        await showSuccess(data.message || 'Late fine added successfully.');
                        window.location.reload();
                    } else {
                        showDanger('Failed to Add Late Fine', data.message || 'Could not create late fine invoice.');
                    }
                } catch (err) {
                    console.error(err);
                    showDanger('Error', 'An error occurred while adding the late fine.');
                } finally {
                    this.submitting = false;
                }
            },

            get previewNetAmount() {
                let val = parseFloat(this.adjustAmount) || 0;
                if (this.mode === 'increase') {
                    return this.netAmount + val;
                } else if (this.mode === 'decrease') {
                    return Math.max(0, this.netAmount - val);
                } else {
                    return val;
                }
            },

            get previewDueAmount() {
                return Math.max(0, (this.previewNetAmount + this.currentFine) - this.paidAmount);
            },

            async submitAdjustment() {
                if (!this.invoiceId) return;
                let val = parseFloat(this.adjustAmount);
                if (isNaN(val) || val < 0) {
                    showDanger('Invalid Amount', 'Please enter a valid non-negative amount.');
                    return;
                }

                if (this.mode === 'decrease' && val > (this.netAmount - this.paidAmount)) {
                    showDanger('Exceeds Limit', 'Decrease amount cannot exceed current unpaid balance of ৳' + (this.netAmount - this.paidAmount).toFixed(2));
                    return;
                }

                if (this.mode === 'set_amount' && val < this.paidAmount) {
                    showDanger('Invalid Amount', 'Desired amount cannot be less than already collected payment of ৳' + this.paidAmount.toFixed(2));
                    return;
                }

                if (this.scope === 'continuous' && !this.continuousConfirmed) {
                    showDanger('Confirmation Required', 'Please confirm continuous adjustment by checking the confirmation box.');
                    return;
                }

                const cleanReason = String(this.reason || '').trim();
                if (cleanReason.length < 3) {
                    showDanger('Reason Required', 'Please enter a reason (at least 3 characters) before applying a tuition fee adjustment.');
                    return;
                }

                this.submitting = true;

                try {
                    const response = await fetch("{{ route('fees.invoice.adjust') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            invoice_id: this.invoiceId,
                            mode: this.mode,
                            amount: val,
                            scope: this.scope,
                            reason: this.reason
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        if (data.updated_invoices && data.updated_invoices.length > 0) {
                            data.updated_invoices.forEach(invData => {
                                // Update read-only due badge
                                const dueEl = document.getElementById(`due-display-${invData.id}`);
                                if (dueEl) {
                                    dueEl.innerText = parseFloat(invData.due_amount).toFixed(2);
                                }

                                // Update net bill column
                                const netCell = document.getElementById(`net-bill-${invData.id}`);
                                if (netCell) {
                                    netCell.innerText = parseFloat(invData.net_amount).toFixed(2);
                                }

                                // Update checkbox
                                const chk = document.getElementById(`checkbox-${invData.id}`);
                                if (chk) {
                                    chk.dataset.amount = invData.due_amount;
                                }

                                // Update Pay Single button onclick
                                const payBtn = document.getElementById(`pay-btn-${invData.id}`);
                                if (payBtn) {
                                    const feeName = payBtn.dataset.feeName;
                                    payBtn.setAttribute('onclick', `openPayModal(${invData.id}, '${feeName}', ${invData.due_amount})`);
                                }

                                // Update Adjust button data attributes
                                const adjBtn = document.getElementById(`adjust-btn-${invData.id}`);
                                if (adjBtn) {
                                    adjBtn.dataset.netAmount = invData.net_amount;
                                    adjBtn.dataset.dueAmount = invData.due_amount;
                                    adjBtn.dataset.paidAmount = invData.paid_amount;
                                    adjBtn.dataset.grossAmount = invData.amount;
                                    adjBtn.dataset.discountAmount = invData.discount;
                                }
                            });
                        }

                        // Update summary badges
                        if (data.summary) {
                            const billEl = document.getElementById('summaryTotalBill');
                            if (billEl) billEl.innerText = '৳ ' + data.summary.total_bill;

                            const dueEl = document.getElementById('summaryTotalDue');
                            if (dueEl) dueEl.innerText = '৳ ' + data.summary.total_due;

                            const discWrap = document.getElementById('summaryDiscountWrapper');
                            const discEl = document.getElementById('summaryDiscount');
                            const discountVal = parseFloat(String(data.summary.total_discount).replace(/,/g, ''));
                            if (discountVal > 0) {
                                if (discWrap) discWrap.classList.remove('hidden');
                                if (discEl) discEl.innerText = '৳ ' + data.summary.total_discount;
                            } else if (discWrap) {
                                discWrap.classList.add('hidden');
                            }
                        }

                        if (typeof window.calculateSingleTotal === 'function') {
                            window.calculateSingleTotal();
                        }

                        this.isOpen = false;
                        showSuccess(data.message || 'Fee adjusted successfully.');
                    } else {
                        showDanger('Adjustment Failed', data.message || 'Unable to adjust fee.');
                    }
                } catch (err) {
                    console.error(err);
                    showDanger('Error', 'An error occurred while saving the adjustment.');
                } finally {
                    this.submitting = false;
                }
            }
        };
    }

    function openPayModal(invoiceId, feeName, dueAmount) {
        document.getElementById('modalInvoiceId').value = invoiceId;
        document.getElementById('modalFeeName').innerText = feeName;
        document.getElementById('modalDueAmountText').innerText = parseFloat(dueAmount).toFixed(2);
        
        document.getElementById('modalPayAmount').value = dueAmount;
        document.getElementById('modalPayAmount').max = dueAmount;

        const modal = document.getElementById('paymentModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closePayModal() {
        const modal = document.getElementById('paymentModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('paymentModal');
            if (modal && !modal.classList.contains('hidden')) {
                closePayModal();
            }
        }
    });

    async function updateSingleFeeAmount(input) {
        const invoiceId = input.dataset.invoiceId;
        const categoryId = input.dataset.categoryId;
        const newAmount = parseFloat(input.value);
        const prevValue = input.dataset.prevValue;
        const spinner = document.getElementById('spinner-' + invoiceId);

        if (isNaN(newAmount) || newAmount < 0) {
            showDanger('Invalid Amount', 'Please enter a valid non-negative fee amount.');
            input.value = prevValue;
            return;
        }

        if (spinner) spinner.classList.remove('hidden');
        input.classList.add('opacity-60');

        try {
            const response = await fetch("{{ route('fees.custom_fee.update_inline') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    invoice_id: invoiceId,
                    amount: newAmount
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Update all invoices returned in data.updated_invoices
                if (data.updated_invoices && data.updated_invoices.length > 0) {
                    data.updated_invoices.forEach(invData => {
                        // Update input in table
                        const rowInput = document.querySelector(`.single-due-input[data-invoice-id="${invData.id}"]`);
                        if (rowInput) {
                            rowInput.value = parseFloat(invData.due_amount).toFixed(2);
                            rowInput.dataset.prevValue = rowInput.value;
                        }

                        // Update net bill column
                        const netCell = document.getElementById(`net-bill-${invData.id}`);
                        if (netCell) {
                            netCell.innerText = parseFloat(invData.net_amount).toFixed(2);
                        }

                        // Update checkbox data-amount
                        const chk = document.getElementById(`checkbox-${invData.id}`);
                        if (chk) {
                            chk.dataset.amount = invData.due_amount;
                        }

                        // Update pay single button onclick
                        const payBtn = document.getElementById(`pay-btn-${invData.id}`);
                        if (payBtn) {
                            const feeName = payBtn.dataset.feeName;
                            payBtn.setAttribute('onclick', `openPayModal(${invData.id}, '${feeName}', ${invData.due_amount})`);
                        }
                    });
                }

                // Update summary badges
                if (data.summary) {
                    const billEl = document.getElementById('summaryTotalBill');
                    if (billEl) billEl.innerText = '৳ ' + data.summary.total_bill;

                    const dueEl = document.getElementById('summaryTotalDue');
                    if (dueEl) dueEl.innerText = '৳ ' + data.summary.total_due;

                    const discWrap = document.getElementById('summaryDiscountWrapper');
                    const discEl = document.getElementById('summaryDiscount');
                    const discountVal = parseFloat(String(data.summary.total_discount).replace(/,/g, ''));
                    if (discountVal > 0) {
                        if (discWrap) discWrap.classList.remove('hidden');
                        if (discEl) discEl.innerText = '৳ ' + data.summary.total_discount;
                    } else if (discWrap) {
                        discWrap.classList.add('hidden');
                    }
                }

                // Recalculate selected total if any checked
                if (typeof window.calculateSingleTotal === 'function') {
                    window.calculateSingleTotal();
                }

                // Visual highlight pulse on all inputs of this category
                document.querySelectorAll(`.single-due-input[data-category-id="${categoryId}"]`).forEach(inp => {
                    inp.classList.add('!border-themeGreen', '!bg-green-50/50', 'dark:!bg-green-950/30');
                    setTimeout(() => {
                        inp.classList.remove('!border-themeGreen', '!bg-green-50/50', 'dark:!bg-green-950/30');
                    }, 1500);
                });

                showSuccess(data.message || 'Custom fee updated successfully.');
            } else {
                showDanger('Update Failed', data.message || 'Failed to update fee.');
                input.value = prevValue;
            }
        } catch (err) {
            console.error(err);
            showDanger('Error', 'An error occurred while updating the fee.');
            input.value = prevValue;
        } finally {
            if (spinner) spinner.classList.add('hidden');
            input.classList.remove('opacity-60');
        }
    }


    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.due-checkbox');
        const selectAll = document.getElementById('selectAllDues');
        const bulkBar = document.getElementById('bulkPaymentBar');
        const bulkTotal = document.getElementById('bulkTotalDisplay');
        const headerSelectedPill = document.getElementById('headerSelectedPill');
        const headerSelectedDisplay = document.getElementById('headerSelectedDisplay');

        function calculateTotal() {
            let total = 0;
            let checkedCount = 0;
            
            checkboxes.forEach(box => {
                if(box.checked) {
                    total += parseFloat(box.dataset.amount);
                    checkedCount++;
                }
            });

            if (bulkTotal) {
                bulkTotal.innerText = total.toFixed(2);
            }

            if (headerSelectedDisplay) {
                headerSelectedDisplay.innerText = total.toFixed(2);
            }
            
            if(bulkBar) {
                if(checkedCount > 0) {
                    bulkBar.classList.remove('hidden');
                } else {
                    bulkBar.classList.add('hidden');
                }
            }

            if (headerSelectedPill) {
                if (checkedCount > 0) {
                    headerSelectedPill.classList.remove('hidden');
                } else {
                    headerSelectedPill.classList.add('hidden');
                }
            }
            
            if(selectAll) {
                selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
            }
        }

        window.calculateSingleTotal = calculateTotal;

        checkboxes.forEach(box => {
            box.addEventListener('change', calculateTotal);
        });

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(box => box.checked = this.checked);
                calculateTotal();
            });
        }
        
        // Listen for all page transition link clicks (pagination, switcher tabs, action buttons)
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (link && link.href && !link.href.startsWith('#') && link.getAttribute('target') !== '_blank' && !link.href.startsWith('javascript:')) {
                window.dispatchEvent(new CustomEvent('trigger-loader'));
            }
        });
        
        // Single & Bulk confirm dialogs
        const singleForm = document.getElementById('singlePaymentForm');
        if (singleForm) {
            singleForm.onsubmit = async function(e) {
                e.preventDefault();
                const form = e.currentTarget;
                if (await showConfirm('Confirm Payment', 'Are you sure you want to record this single fee payment?')) {
                    window.dispatchEvent(new CustomEvent('trigger-loader'));
                    form.submit();
                }
            };
        }

        const bulkForm = document.getElementById('bulkPaymentForm');
        if (bulkForm) {
            bulkForm.onsubmit = async function(e) {
                e.preventDefault();
                const form = e.currentTarget;
                if (await showConfirm('Confirm Bulk Payment', 'Are you sure you want to record payment for all selected dues?')) {
                    window.dispatchEvent(new CustomEvent('trigger-loader'));
                    form.submit();
                }
            };
        }

        const bulkStudentsForm = document.getElementById('bulkStudentsPaymentForm');
        if (bulkStudentsForm) {
            bulkStudentsForm.onsubmit = async function(e) {
                e.preventDefault();
                const form = e.currentTarget;
                if (await showConfirm('Confirm Bulk Collection', 'Are you sure you want to record payments for all selected student dues?')) {
                    window.dispatchEvent(new CustomEvent('trigger-loader'));
                    form.submit();
                }
            };
        }
    });

    function singleCollectionFilter() {
        return {
            activeDropdown: null,
            branchText: '{{ request('branch_id') ? ($branches->firstWhere('id', request('branch_id'))->branch_name ?? 'All Branches') : 'All Branches' }}',
            sessionText: '{{ request('session_year_id') ? ($sessions->firstWhere('id', request('session_year_id'))->session_name ?? 'All Sessions') : 'All Sessions' }}',
            classText: '{{ request('class_id') ? ($classes->firstWhere('id', request('class_id'))->class_name ?? 'All Classes') : 'All Classes' }}',
            sectionText: '{{ request('section_id') ? ($sections->firstWhere('id', request('section_id'))->section_name ?? 'All Sections') : 'All Sections' }}',
            
            form: {
                branch_id: '{{ request('branch_id', '') }}',
                session_year_id: '{{ request('session_year_id', '') }}',
                class_id: '{{ request('class_id', '') }}',
                section_id: '{{ request('section_id', '') }}'
            },
            
            selectBranch(id, name) {
                this.form.branch_id = id;
                this.branchText = name;
                this.activeDropdown = null;
            },
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
                this.activeDropdown = null;
            },
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.activeDropdown = null;
            },
            selectSection(id, name) {
                this.form.section_id = id;
                this.sectionText = name;
                this.activeDropdown = null;
            }
        };
    }

    function bulkCollectionSetup() {
        return {
            activeDropdown: null,
            branchText: '{{ request('branch_id') ? ($branches->firstWhere('id', request('branch_id'))->branch_name ?? 'Choose Branch') : 'Choose Branch' }}',
            sessionText: '{{ request('session_year_id') ? ($sessions->firstWhere('id', request('session_year_id'))->session_name ?? 'Choose Session') : 'Choose Session' }}',
            classText: '{{ request('class_id') ? ($classes->firstWhere('id', request('class_id'))->class_name ?? 'Choose Class') : 'Choose Class' }}',
            sectionText: '{{ request('section_id') ? ($sections->firstWhere('id', request('section_id'))->section_name ?? 'Choose Section') : 'Choose Section' }}',
            categoryText: '{{ request('fee_category_id') ? ($categories->firstWhere('id', request('fee_category_id'))->name ?? 'Choose Category') : 'Choose Category' }}',
            monthText: '{{ request('fee_month') ?: 'Choose Month' }}',
            
            form: {
                branch_id: '{{ request('branch_id', '') }}',
                session_year_id: '{{ request('session_year_id', '') }}',
                class_id: '{{ request('class_id', '') }}',
                section_id: '{{ request('section_id', '') }}',
                fee_category_id: '{{ request('fee_category_id', '') }}',
                fee_month: '{{ request('fee_month', '') }}'
            },
            
            selectBranch(id, name) {
                this.form.branch_id = id;
                this.branchText = name;
                this.activeDropdown = null;
            },
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
                this.activeDropdown = null;
            },
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.activeDropdown = null;
            },
            selectSection(id, name) {
                this.form.section_id = id;
                this.sectionText = name;
                this.activeDropdown = null;
            },
            selectCategory(id, name) {
                this.form.fee_category_id = id;
                this.categoryText = name;
                this.activeDropdown = null;
            },
            selectMonth(value, name) {
                this.form.fee_month = value;
                this.monthText = name;
                this.activeDropdown = null;
            }
        };
    }

    if (typeof window.datePicker !== 'function') {
        window.datePicker = function(initialValue = '') {
            const today = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const defaultDate = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;
            const parsed = (initialValue && /^\d{4}-\d{2}-\d{2}$/.test(initialValue))
                ? new Date(initialValue + 'T00:00:00')
                : new Date(defaultDate + 'T00:00:00');
            return {
                show: false,
                mOpen: false,
                formatted: initialValue || defaultDate,
                year: parsed.getFullYear(),
                month: parsed.getMonth(),
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                blankDays: [],
                daysInMonth: [],
                init() {
                    this.calculateDays();
                },
                calculateDays() {
                    const y = parseInt(this.year, 10) || today.getFullYear();
                    const firstDay = new Date(y, this.month, 1).getDay();
                    const totalDays = new Date(y, this.month + 1, 0).getDate();
                    this.blankDays = Array.from({ length: firstDay }, (_, i) => i + 1);
                    this.daysInMonth = Array.from({ length: totalDays }, (_, i) => i + 1);
                },
                selectDay(day) {
                    const y = parseInt(this.year, 10) || today.getFullYear();
                    this.formatted = `${y}-${pad(this.month + 1)}-${pad(day)}`;
                    this.show = false;
                },
                isSelected(day) {
                    const y = parseInt(this.year, 10) || today.getFullYear();
                    return this.formatted === `${y}-${pad(this.month + 1)}-${pad(day)}`;
                }
            };
        };
    }

    window.openAdmissionPayModalFromButton = function(buttonEl) {
        try {
            const raw = buttonEl.getAttribute('data-admission-invoice') || '{}';
            const detail = JSON.parse(raw);
            window.dispatchEvent(new CustomEvent('open-admission-pay-modal', { detail }));
        } catch (err) {
            console.error(err);
            showAlert('Unable to load admission invoice details.', 'Modal Error');
        }
    };

    window.admissionPayModalComponent = function() {
        const generateIdempotencyKey = () => {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }
            return 'adm-col-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
        };

        const todayIso = () => {
            const d = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        };

        return {
            isOpen: false,
            isSubmitting: false,
            canGrantDiscount: @json(auth()->check() && \App\Services\AdmissionInvoiceService::userCanGrantDiscount(auth()->user())),
            allocationMode: 'lump_sum',
            includeTuitionInAutoSplit: true,
            invoiceId: null,
            invoiceNo: '',
            structureName: 'Admission Fee Package',
            totalDueAmount: '0.00',
            lumpAmount: '0.00',
            directDiscountInput: '',
            discountReason: '',
            lastAutoEffectiveDuePaisa: 0,
            heads: [],
            paymentMethod: 'Cash',
            paymentMethods: ['Cash', 'Bank', 'bKash', 'Nagad', 'Rocket'],
            methodOpen: false,
            paymentDate: todayIso(),
            transactionId: '',
            note: '',
            idempotencyKey: '',

            init() {
                this.$watch('isOpen', (val) => {
                    document.body.classList.toggle('overflow-hidden', Boolean(val));
                });
            },

            toPaisa(val) {
                const parsed = parseFloat(String(val ?? '0').replace(/,/g, ''));
                if (isNaN(parsed)) return 0;
                return Math.round(parsed * 100);
            },

            formatMoney(paisa) {
                return (Math.max(0, Number(paisa || 0)) / 100).toFixed(2);
            },

            get totalDuePaisa() {
                return this.toPaisa(this.totalDueAmount);
            },

            get eligibleDiscountablePaisa() {
                return this.heads.reduce((sum, h) => {
                    if (!h.is_discountable) return sum;
                    return sum + Math.max(0, this.toPaisa(h.due_amount));
                }, 0);
            },

            get directDiscountPaisa() {
                if (!this.canGrantDiscount) return 0;
                const raw = Math.max(0, this.toPaisa(this.directDiscountInput));
                return Math.min(raw, this.eligibleDiscountablePaisa);
            },

            get effectiveDuePaisa() {
                return Math.max(0, this.totalDuePaisa - this.directDiscountPaisa);
            },

            get discountShareByHeadId() {
                const shares = {};
                const discTotal = this.directDiscountPaisa;
                const eligibleTotal = this.eligibleDiscountablePaisa;
                if (discTotal <= 0 || eligibleTotal <= 0) {
                    return shares;
                }

                const items = this.heads
                    .filter((h) => Boolean(h.is_discountable) && this.toPaisa(h.due_amount) > 0)
                    .map((h) => {
                        const duePaisa = this.toPaisa(h.due_amount);
                        const exact = discTotal * duePaisa;
                        const floored = Math.floor(exact / eligibleTotal);
                        const rem = exact % eligibleTotal;
                        return { id: h.id, sortOrder: Number(h.sort_order || 0), duePaisa, share: floored, rem };
                    });

                let allocated = items.reduce((s, it) => s + it.share, 0);
                let leftover = discTotal - allocated;

                const byRem = [...items].sort((a, b) => {
                    if (b.rem !== a.rem) return b.rem - a.rem;
                    if (a.sortOrder !== b.sortOrder) return a.sortOrder - b.sortOrder;
                    return Number(a.id) - Number(b.id);
                });

                for (const it of byRem) {
                    if (leftover <= 0) break;
                    if (it.share < it.duePaisa) {
                        it.share += 1;
                        leftover -= 1;
                    }
                }

                for (const it of items) {
                    shares[it.id] = it.share;
                }
                return shares;
            },

            effectiveHeadDuePaisa(head) {
                const base = Math.max(0, this.toPaisa(head.due_amount));
                const disc = Number(this.discountShareByHeadId[head.id] || 0);
                return Math.max(0, base - disc);
            },

            get tuitionHeads() {
                return this.heads.filter((h) => Boolean(h.is_tuition_head));
            },

            get hasTuitionHead() {
                return this.tuitionHeads.length > 0;
            },

            get tuitionHeadNames() {
                const names = this.tuitionHeads.map((h) => h.head_name).filter(Boolean);
                return names.length > 0 ? names.join(', ') : 'Monthly Tuition Fee';
            },

            get pendingTuitionDuePaisa() {
                return this.tuitionHeads.reduce((sum, h) => sum + this.effectiveHeadDuePaisa(h), 0);
            },

            get excludedTuitionDuePaisa() {
                if (this.includeTuitionInAutoSplit || !this.hasTuitionHead) {
                    return 0;
                }
                return this.pendingTuitionDuePaisa;
            },

            get autoSplitMaxPaisa() {
                return Math.max(0, this.effectiveDuePaisa - this.excludedTuitionDuePaisa);
            },

            get payingTotalPaisa() {
                if (this.allocationMode === 'lump_sum') {
                    return Math.max(0, this.toPaisa(this.lumpAmount));
                }
                return this.heads.reduce((sum, h) => sum + Math.max(0, this.toPaisa(h.pay_input)), 0);
            },

            open(detail) {
                this.invoiceId = detail.invoice_id || null;
                this.invoiceNo = detail.invoice_no || '';
                this.structureName = detail.structure_name || 'Admission Fee Package';
                this.totalDueAmount = detail.due_amount || '0.00';
                this.directDiscountInput = '';
                this.discountReason = '';
                this.allocationMode = 'lump_sum';
                this.includeTuitionInAutoSplit = true;
                this.heads = (Array.isArray(detail.heads) ? detail.heads : []).map((h) => ({
                    ...h,
                    pay_input: h.due_amount || '0.00',
                }));
                this.lastAutoEffectiveDuePaisa = this.autoSplitMaxPaisa;
                this.lumpAmount = this.formatMoney(this.autoSplitMaxPaisa);
                this.paymentMethod = 'Cash';
                this.methodOpen = false;
                this.paymentDate = todayIso();
                this.transactionId = '';
                this.note = '';
                this.idempotencyKey = generateIdempotencyKey();
                this.isOpen = true;
            },

            close() {
                this.isOpen = false;
                this.methodOpen = false;
                document.body.classList.remove('overflow-hidden');
            },

            onTuitionAutoSplitToggle() {
                const prevAuto = this.lastAutoEffectiveDuePaisa;
                const newMax = this.autoSplitMaxPaisa;
                const curLump = this.toPaisa(this.lumpAmount);

                if (!this.includeTuitionInAutoSplit) {
                    if (curLump === prevAuto || curLump > newMax) {
                        this.lumpAmount = this.formatMoney(newMax);
                    }
                } else if (curLump === prevAuto) {
                    this.lumpAmount = this.formatMoney(newMax);
                }

                this.lastAutoEffectiveDuePaisa = newMax;
            },

            onDirectDiscountInput() {
                const rawEntered = Math.max(0, this.toPaisa(this.directDiscountInput));
                if (rawEntered > this.eligibleDiscountablePaisa) {
                    this.directDiscountInput = this.formatMoney(this.eligibleDiscountablePaisa);
                }

                const newAutoMax = this.autoSplitMaxPaisa;
                const currentLump = this.toPaisa(this.lumpAmount);
                if (currentLump === this.lastAutoEffectiveDuePaisa || currentLump > newAutoMax) {
                    this.lumpAmount = this.formatMoney(newAutoMax);
                }
                this.lastAutoEffectiveDuePaisa = newAutoMax;

                this.heads = this.heads.map((h) => {
                    const effHeadDue = this.effectiveHeadDuePaisa(h);
                    const curPay = this.toPaisa(h.pay_input);
                    return {
                        ...h,
                        pay_input: curPay > effHeadDue ? this.formatMoney(effHeadDue) : h.pay_input,
                    };
                });
            },

            applyDiscountOnly() {
                this.lumpAmount = '0.00';
                this.heads = this.heads.map((h) => ({ ...h, pay_input: '0.00' }));
            },

            clearDirectDiscount() {
                this.directDiscountInput = '';
                this.discountReason = '';
                this.onDirectDiscountInput();
            },

            fillFullDue() {
                const targetMax = this.autoSplitMaxPaisa;
                this.lastAutoEffectiveDuePaisa = targetMax;
                this.lumpAmount = this.formatMoney(targetMax);
                this.heads = this.heads.map((h) => ({
                    ...h,
                    pay_input: this.formatMoney(this.effectiveHeadDuePaisa(h)),
                }));
            },

            async submit() {
                if (this.isSubmitting || !this.invoiceId) return;

                const rawEnteredDisc = Math.max(0, this.toPaisa(this.directDiscountInput));
                if (this.canGrantDiscount && rawEnteredDisc > this.eligibleDiscountablePaisa) {
                    await showAlert(`Direct discount cannot exceed eligible discountable due of ৳${this.formatMoney(this.eligibleDiscountablePaisa)}.`, 'Validation Error');
                    return;
                }

                const discPaisa = this.directDiscountPaisa;
                const effDuePaisa = this.effectiveDuePaisa;
                const cashPaisa = this.payingTotalPaisa;
                const tuitionExcluded = this.allocationMode === 'lump_sum'
                    && this.hasTuitionHead
                    && !this.includeTuitionInAutoSplit
                    && this.pendingTuitionDuePaisa > 0;

                if (cashPaisa <= 0 && discPaisa <= 0) {
                    await showAlert('Please enter a positive collection amount or direct discount.', 'Validation Error');
                    return;
                }

                if (this.allocationMode === 'lump_sum') {
                    if (cashPaisa > this.autoSplitMaxPaisa) {
                        if (tuitionExcluded) {
                            await showAlert(`Total paying amount cannot exceed eligible non-tuition due of ৳${this.formatMoney(this.autoSplitMaxPaisa)}. Select Monthly Tuition Fee to pay more.`, 'Validation Error');
                        } else {
                            await showAlert('Total paying amount cannot exceed net collectible invoice due.', 'Validation Error');
                        }
                        return;
                    }
                } else if (cashPaisa > effDuePaisa) {
                    await showAlert('Total paying amount cannot exceed net collectible invoice due.', 'Validation Error');
                    return;
                }

                const payload = {
                    invoice_id: this.invoiceId,
                    payment_method: this.paymentMethod,
                    payment_date: this.paymentDate,
                    transaction_id: this.transactionId || null,
                    note: this.note || null,
                    idempotency_key: this.idempotencyKey,
                };

                if (discPaisa > 0) {
                    payload.direct_discount_amount = this.formatMoney(discPaisa);
                    payload.discount_reason = this.discountReason || null;
                }

                if (this.allocationMode === 'lump_sum') {
                    payload.pay_amount = this.formatMoney(cashPaisa);
                    payload.include_tuition_in_auto_split = Boolean(this.includeTuitionInAutoSplit);
                } else if (cashPaisa > 0) {
                    const headMap = {};
                    for (const h of this.heads) {
                        const hp = this.toPaisa(h.pay_input);
                        const dp = this.effectiveHeadDuePaisa(h);
                        if (hp > dp) {
                            await showAlert(`Entered amount for "${h.head_name}" exceeds its collectible due balance of ৳${this.formatMoney(dp)}.`, 'Validation Error');
                            return;
                        }
                        if (hp > 0) {
                            headMap[h.id] = this.formatMoney(hp);
                        }
                    }
                    payload.head_allocations = headMap;
                } else {
                    payload.pay_amount = '0.00';
                }

                const tuitionNote = tuitionExcluded && cashPaisa > 0 ? ' (excluding Monthly Tuition Fee)' : '';
                let confirmMsg = `Collect ৳${this.formatMoney(cashPaisa)}${tuitionNote} against ${this.invoiceNo} via ${this.paymentMethod}?`;
                if (discPaisa > 0 && cashPaisa > 0) {
                    confirmMsg = `Apply ৳${this.formatMoney(discPaisa)} direct discount and collect ৳${this.formatMoney(cashPaisa)}${tuitionNote} against ${this.invoiceNo} via ${this.paymentMethod}?`;
                } else if (discPaisa > 0 && cashPaisa === 0) {
                    confirmMsg = `Apply ৳${this.formatMoney(discPaisa)} direct discount (with ৳0.00 cash collection) against ${this.invoiceNo}?`;
                }

                const confirmed = await showConfirm('Confirm Admission Fee Collection', confirmMsg);
                if (!confirmed) return;

                this.isSubmitting = true;
                try {
                    const response = await fetch("{{ route('fees.collection.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        this.close();
                        await showSuccess(data.message || 'Admission fee payment collected successfully.');
                        window.dispatchEvent(new CustomEvent('trigger-loader'));
                        window.location.reload();
                    } else {
                        await showAlert(data.message || 'Unable to collect admission fee.', 'Collection Error');
                    }
                } catch (err) {
                    console.error(err);
                    await showAlert('Network or server error while saving collection.', 'Collection Error');
                } finally {
                    this.isSubmitting = false;
                }
            },
        };
    };
</script>
@endpush

@push('css')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush