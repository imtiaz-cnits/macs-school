@extends('tyro-dashboard::layouts.admin')

@section('title', 'Admission Fee Structures')

@php
    $branchesPayload = $branches->map(fn ($b) => ['id' => (string) $b->id, 'name' => (string) $b->branch_name])->values();
    $classesPayload = $classes->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->class_name])->values();
    $sessionsPayload = $sessions->map(fn ($s) => ['id' => (string) $s->id, 'name' => (string) $s->session_name])->values();
    $categoriesPayload = $feeCategories->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])->values();
    $monthsPayload = [
        ['id' => '1', 'name' => 'January'],
        ['id' => '2', 'name' => 'February'],
        ['id' => '3', 'name' => 'March'],
        ['id' => '4', 'name' => 'April'],
        ['id' => '5', 'name' => 'May'],
        ['id' => '6', 'name' => 'June'],
        ['id' => '7', 'name' => 'July'],
        ['id' => '8', 'name' => 'August'],
        ['id' => '9', 'name' => 'September'],
        ['id' => '10', 'name' => 'October'],
        ['id' => '11', 'name' => 'November'],
        ['id' => '12', 'name' => 'December'],
    ];
@endphp

@section('content')
<div class="w-full min-w-0 min-h-screen"
     x-data="admissionStructureConsole({
         schemaReady: @js((bool) $schemaReady),
         schemaWarning: @js($schemaWarning ?? ''),
         structures: @js($structuresPayload),
         initialFilters: {
             branch_id: @js((string) ($filters['branch_id'] ?? '')),
             class_id: @js((string) ($filters['class_id'] ?? '')),
             session_year_id: @js((string) ($filters['session_year_id'] ?? '')),
             status: @js((string) ($filters['status'] ?? '')),
             search: @js((string) ($filters['search'] ?? ''))
         },
         urls: {
             index: @js(route('fees.admission.structures.index')),
             store: @js(route('fees.admission.structures.store')),
             base: @js(url('/fees/admission-structures'))
         },
         options: {
             branches: @js($branchesPayload),
             classes: @js($classesPayload),
             sessions: @js($sessionsPayload),
             categories: @js($categoriesPayload),
             months: @js($monthsPayload),
             statuses: [
                 { id: 'Active', name: 'Active' },
                 { id: 'Inactive', name: 'Inactive' },
                 { id: 'Archived', name: 'Archived' }
             ],
             formStatuses: [
                 { id: 'Active', name: 'Active' },
                 { id: 'Inactive', name: 'Inactive' }
             ]
         }
     })">

    <!-- Page Header (AGENTS.md Rule 4) -->
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Admission Fee Structures
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Configure versioned multi-head all-time or session-scoped admission fee packages by branch and class.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end">
            <a href="{{ route('fees.admission.reports.index') }}"
               class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                Admission Reports
            </a>
            <button type="button"
                    @click="openCreateModal()"
                    :disabled="!schemaReady"
                    :class="!schemaReady ? 'opacity-50 cursor-not-allowed' : ''"
                    class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Structure
            </button>
        </div>
    </div>

    <!-- Pre-migration Schema Guard Warning -->
    @if(!$schemaReady)
        <div class="mb-6 rounded-3xl border-2 border-amber-200 dark:border-amber-500/30 bg-amber-50/90 dark:bg-amber-950/25 p-5 shadow-sm flex items-start gap-4">
            <div class="w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-black uppercase tracking-wider text-amber-900 dark:text-amber-200">Schema Migration Required</h2>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mt-1">{{ $schemaWarning }}</p>
            </div>
        </div>
    @endif

    <!-- Filter Card (AGENTS.md Rule 7 Custom Alpine Dropdowns) -->
    <div class="mb-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm">
        <form @submit.prevent="fetchStructures()" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Branch Filter -->
                <div class="relative" @click.away="if (activeFilterDropdown === 'branch') activeFilterDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Branch</label>
                        <button type="button" x-show="filters.branch_id !== ''" x-cloak @click="clearFilter('branch_id')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleFilterDropdown('branch')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="optionLabel('branches', filters.branch_id, 'All Branches')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeFilterDropdown === 'branch'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.branches" :key="opt.id">
                            <button type="button" @click="chooseFilter('branch_id', opt.id)" :class="filters.branch_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.branch_id == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Class Filter (Play..Ten) -->
                <div class="relative" @click.away="if (activeFilterDropdown === 'class') activeFilterDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Class</label>
                        <button type="button" x-show="filters.class_id !== ''" x-cloak @click="clearFilter('class_id')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleFilterDropdown('class')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="optionLabel('classes', filters.class_id, 'All Classes')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeFilterDropdown === 'class'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.classes" :key="opt.id">
                            <button type="button" @click="chooseFilter('class_id', opt.id)" :class="filters.class_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.class_id == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Session Scope Filter -->
                <div class="relative" @click.away="if (activeFilterDropdown === 'session') activeFilterDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Session Scope</label>
                        <button type="button" x-show="filters.session_year_id !== ''" x-cloak @click="clearFilter('session_year_id')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleFilterDropdown('session')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="filters.session_year_id === 'all_time' ? 'All-Time Default Packages' : optionLabel('sessions', filters.session_year_id, 'All Scopes')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeFilterDropdown === 'session'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <button type="button" @click="chooseFilter('session_year_id', 'all_time')" :class="filters.session_year_id === 'all_time' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>All-Time (Applies Every Year)</span>
                            <template x-if="filters.session_year_id === 'all_time'">
                                <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        <template x-for="opt in options.sessions" :key="opt.id">
                            <button type="button" @click="chooseFilter('session_year_id', opt.id)" :class="filters.session_year_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.session_year_id == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="relative" @click.away="if (activeFilterDropdown === 'status') activeFilterDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Status</label>
                        <button type="button" x-show="filters.status !== ''" x-cloak @click="clearFilter('status')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleFilterDropdown('status')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="optionLabel('statuses', filters.status, 'All Statuses')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeFilterDropdown === 'status'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.statuses" :key="opt.id">
                            <button type="button" @click="chooseFilter('status', opt.id)" :class="filters.status === opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.status === opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center pt-1">
                <input type="text"
                       x-model.trim="filters.search"
                       placeholder="Search structure package name..."
                       class="flex-1 h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                <div class="flex gap-2">
                    <button type="submit"
                            :disabled="!schemaReady || isLoading"
                            class="h-11 px-7 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest disabled:opacity-60">
                        Filter Structures
                    </button>
                    <button type="button"
                            @click="resetFilters()"
                            :disabled="!schemaReady || isLoading"
                            class="h-11 px-5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-wider transition-all">
                        Reset
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Directory Table Card (AGENTS.md Rule 9 Borderless) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between pb-3 mb-2 border-b border-gray-100 dark:border-white/[0.06]">
            <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-white">Configured Admission Packages</h2>
            <span class="text-xs font-mono font-bold text-gray-500 dark:text-gray-400" x-text="structures.length + ' structure(s)'"></span>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
            <table class="w-full text-left border-collapse table [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">Structure &amp; Scope</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] text-center whitespace-nowrap">Version</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] whitespace-nowrap">First-Month Tuition</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] text-center whitespace-nowrap">Heads / Usage</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] text-right whitespace-nowrap">Total Gross</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] text-center whitespace-nowrap">Status</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.16em] text-right whitespace-nowrap">Action</th>
                    </tr>
                </thead>

                <!-- Skeleton Loading State (Rule 9) -->
                <tbody x-show="isLoading" x-cloak class="divide-y divide-gray-100 dark:divide-white/[0.06]">
                    @for($s = 0; $s < 4; $s++)
                        <tr class="animate-pulse">
                            <td class="py-0 px-0"><div class="h-4 w-52 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                            <td class="py-0 px-0"><div class="h-4 w-10 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                            <td class="py-0 px-0"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                            <td class="py-0 px-0"><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                            <td class="py-0 px-0"><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td class="py-0 px-0"><div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                            <td class="py-0 px-0"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                        </tr>
                    @endfor
                </tbody>

                <!-- Data Rows -->
                <tbody x-show="!isLoading" class="divide-y divide-gray-100 dark:divide-white/[0.06]">
                    <template x-if="structures.length === 0">
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <p class="text-sm font-bold text-gray-500 dark:text-gray-400"
                                   x-text="!schemaReady ? 'No admission structures available while schema migrations are pending.' : 'No admission fee structures matched the current filter criteria.'"></p>
                            </td>
                        </tr>
                    </template>

                    <template x-for="st in structures" :key="'st-' + st.id">
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <td class="py-0 px-0">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap" x-text="st.structure_name"></div>
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5 whitespace-nowrap">
                                    <span x-text="st.branch_name"></span>
                                    <span class="mx-1 text-gray-300 dark:text-gray-600">&middot;</span>
                                    <span x-text="'Class ' + st.class_name"></span>
                                    <span class="mx-1 text-gray-300 dark:text-gray-600">&middot;</span>
                                    <span :class="st.session_year_id ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-themeBlue font-semibold'"
                                          x-text="st.session_year_id ? ('Session ' + st.session_name + ' Override') : 'All Sessions (Default)'"></span>
                                </div>
                            </td>
                            <td class="py-0 px-0 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-themeBlue/15 text-themeBlue font-mono font-black text-xs whitespace-nowrap" x-text="'v' + st.version"></span>
                            </td>
                            <td class="py-0 px-0 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider whitespace-nowrap"
                                      :class="st.includes_monthly_tuition ? 'bg-sky-50 dark:bg-themeBlue/15 text-themeBlue border border-sky-200/60 dark:border-themeBlue/30' : 'bg-gray-100 dark:bg-themeDark text-gray-500 dark:text-gray-400'"
                                      x-text="st.includes_monthly_tuition ? ('Includes ' + st.tuition_month_name) : 'Excluded'"></span>
                            </td>
                            <td class="py-0 px-0 text-center text-xs font-mono font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                <span x-text="st.items_count + ' heads'"></span>
                                <div class="text-[10px] text-gray-400 font-normal mt-0.5 whitespace-nowrap" x-text="st.invoices_count + ' invoice(s)'"></div>
                            </td>
                            <td class="py-0 px-0 text-right font-mono font-black text-sm text-themeGreen whitespace-nowrap" x-text="'৳ ' + st.total_amount"></td>
                            <td class="py-0 px-0 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider whitespace-nowrap"
                                      :class="statusBadgeClass(st.status)"
                                      x-text="st.status"></span>
                            </td>
                            <td class="py-0 px-0 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <!-- Quick View Modal -->
                                    <button type="button"
                                            @click="openQuickViewModal(st)"
                                            class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue flex items-center justify-center h-8 w-8 border border-gray-200 dark:border-white/[0.08] rounded-lg transition-all"
                                            title="Quick View Package">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Versioned Edit (Creates vN+1) -->
                                    <button type="button"
                                            @click="openEditVersionModal(st)"
                                            class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue flex items-center justify-center h-8 w-8 border border-gray-200 dark:border-white/[0.08] rounded-lg transition-all"
                                            title="Edit as New Version">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        </svg>
                                    </button>

                                    <!-- Activate -->
                                    <template x-if="st.status !== 'Active'">
                                        <button type="button"
                                                @click="activateStructure(st)"
                                                class="px-2.5 h-8 rounded-lg bg-emerald-50 hover:bg-themeGreen text-themeGreen hover:text-white border border-emerald-200 text-[10px] font-black uppercase tracking-wider transition-all">
                                            Activate
                                        </button>
                                    </template>

                                    <!-- Deactivate -->
                                    <template x-if="st.status === 'Active'">
                                        <button type="button"
                                                @click="deactivateStructure(st)"
                                                class="px-2.5 h-8 rounded-lg bg-amber-50 hover:bg-amber-500 text-amber-700 hover:text-white border border-amber-200 text-[10px] font-black uppercase tracking-wider transition-all">
                                            Deactivate
                                        </button>
                                    </template>

                                    <!-- Delete Unused Draft Only -->
                                    <template x-if="st.status === 'Inactive' && Number(st.invoices_count) === 0">
                                        <button type="button"
                                                @click="deleteDraftStructure(st)"
                                                class="action-btn text-red-600 hover:text-red-800 hover:border-red-600 flex items-center justify-center h-8 w-8 border border-gray-200 dark:border-white/[0.08] rounded-lg transition-all"
                                                title="Delete Unused Draft">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Versioned-Edit Structure Modal (AGENTS.md Rule 3 & Rule 8 z-[9999]) -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-themeDark/40 backdrop-blur-md p-4 overflow-y-auto">
        <div @click.away="closeModal()"
             class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.08] shadow-2xl w-full max-w-4xl my-6 overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider"
                        x-text="isEditMode ? ('Edit Admission Structure (Creates v' + nextVersionNumber + ')') : 'Create Admission Fee Structure'"></h3>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5"
                       x-text="isEditMode ? 'Branch, Class, and Session scope remain locked; saving creates a new immutable version.' : 'Define scoped package details and head breakdown matching exact BCMath total.'"></p>
                </div>
                <button type="button" @click="closeModal()" class="text-gray-400 hover:text-rose-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitModal()" class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
                <div x-show="modalError !== ''" x-cloak class="rounded-2xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-950/30 px-4 py-3 text-xs font-bold text-rose-700 dark:text-rose-300" x-text="modalError"></div>

                <!-- Scope Selectors (Rule 7 Dropdowns without placeholder repetition) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Modal Branch -->
                    <div class="relative" @click.away="if (activeModalDropdown === 'branch') activeModalDropdown = null">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1.5">Branch *</label>
                        <button type="button"
                                :disabled="isEditMode"
                                @click="if (!isEditMode) activeModalDropdown = (activeModalDropdown === 'branch' ? null : 'branch')"
                                :class="isEditMode ? 'opacity-60 cursor-not-allowed' : ''"
                                class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="optionLabel('branches', form.branch_id, 'Choose Branch')"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeModalDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-56 overflow-y-auto">
                            <template x-for="opt in options.branches" :key="'mb-' + opt.id">
                                <button type="button" @click="form.branch_id = opt.id; activeModalDropdown = null" :class="form.branch_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                    <span x-text="opt.name"></span>
                                    <template x-if="form.branch_id == opt.id"><svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Modal Class (Play..Ten) -->
                    <div class="relative" @click.away="if (activeModalDropdown === 'class') activeModalDropdown = null">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1.5">Class *</label>
                        <button type="button"
                                :disabled="isEditMode"
                                @click="if (!isEditMode) activeModalDropdown = (activeModalDropdown === 'class' ? null : 'class')"
                                :class="isEditMode ? 'opacity-60 cursor-not-allowed' : ''"
                                class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="optionLabel('classes', form.class_id, 'Choose Class')"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeModalDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-56 overflow-y-auto">
                            <template x-for="opt in options.classes" :key="'mc-' + opt.id">
                                <button type="button" @click="form.class_id = opt.id; activeModalDropdown = null" :class="form.class_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                    <span x-text="opt.name"></span>
                                    <template x-if="form.class_id == opt.id"><svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Modal Session Scope -->
                    <div class="relative" @click.away="if (activeModalDropdown === 'session') activeModalDropdown = null">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1.5">Session Scope</label>
                        <button type="button"
                                :disabled="isEditMode"
                                @click="if (!isEditMode) activeModalDropdown = (activeModalDropdown === 'session' ? null : 'session')"
                                :class="isEditMode ? 'opacity-60 cursor-not-allowed' : ''"
                                class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="form.session_year_id ? ('Session ' + optionLabel('sessions', form.session_year_id, '')) : 'All Sessions (Default)'"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeModalDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-56 overflow-y-auto">
                            <button type="button" @click="form.session_year_id = ''; activeModalDropdown = null" :class="form.session_year_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span>All-Time Package (No Specific Year)</span>
                                <template x-if="form.session_year_id === ''"><svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                            </button>
                            <template x-for="opt in options.sessions" :key="'ms-' + opt.id">
                                <button type="button" @click="form.session_year_id = opt.id; activeModalDropdown = null" :class="form.session_year_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                    <span x-text="'Session ' + opt.name + ' Override'"></span>
                                    <template x-if="form.session_year_id == opt.id"><svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Scope & Immutability Note -->
                <div class="rounded-2xl border border-sky-100 dark:border-themeBlue/20 bg-sky-50/70 dark:bg-themeBlue/10 px-4 py-2.5 text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                    <span x-show="!isEditMode">Keep scope as <strong class="text-themeBlue">All Sessions (Default)</strong> so this structure applies automatically across 2026 and future sessions. Select a specific session only to override a single year.</span>
                    <span x-show="isEditMode" x-cloak x-text="'Editing publishes immutable Version v' + nextVersionNumber + ' for future admissions only. Past invoices and receipts remain unaffected.'"></span>
                </div>

                <!-- Package Title, Status & Tuition Settings -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1.5">Structure Package Name *</label>
                        <input type="text" x-model.trim="form.structure_name" placeholder="e.g. Play Admission Fee" class="w-full h-11 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark text-sm font-semibold text-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                    </div>

                    <div class="relative" @click.away="if (activeModalDropdown === 'status') activeModalDropdown = null">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1.5">Status *</label>
                        <button type="button" @click="activeModalDropdown = (activeModalDropdown === 'status' ? null : 'status')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span x-text="form.status"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeModalDropdown === 'status'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1">
                            <template x-for="opt in options.formStatuses" :key="'mst-' + opt.id">
                                <button type="button" @click="form.status = opt.id; activeModalDropdown = null" :class="form.status === opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                    <span x-text="opt.name"></span>
                                    <template x-if="form.status === opt.id"><svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- First-Month Tuition + Replace Active -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center p-4 rounded-2xl bg-gray-50/70 dark:bg-themeDark/50 border border-gray-100 dark:border-white/[0.06]">
                    <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-gray-800 dark:text-gray-200">
                        <input type="checkbox" x-model="form.includes_monthly_tuition" class="w-4 h-4 rounded border-gray-300 text-themeBlue focus:ring-themeBlue">
                        <span>Includes First-Month Tuition</span>
                    </label>

                    <div class="relative" x-show="form.includes_monthly_tuition" x-cloak @click.away="if (activeModalDropdown === 'month') activeModalDropdown = null">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1">Tuition Month *</label>
                        <button type="button" @click="activeModalDropdown = (activeModalDropdown === 'month' ? null : 'month')" class="w-full h-10 px-3 bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span x-text="optionLabel('months', form.tuition_month_number, 'Choose Month')"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeModalDropdown === 'month'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-48 overflow-y-auto">
                            <template x-for="opt in options.months" :key="'mmo-' + opt.id">
                                <button type="button" @click="form.tuition_month_number = opt.id; activeModalDropdown = null" :class="form.tuition_month_number == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                    <span x-text="opt.name"></span>
                                    <template x-if="form.tuition_month_number == opt.id"><svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                                </button>
                            </template>
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-gray-700 dark:text-gray-300" x-show="form.status === 'Active'">
                        <input type="checkbox" x-model="form.replace_active" class="w-4 h-4 rounded border-gray-300 text-themeGreen focus:ring-themeGreen">
                        <span>Replace any existing Active structure in this scope</span>
                    </label>
                </div>

                <!-- Dynamic Fee Head Repeater -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-gray-800 dark:text-white">Fee Heads Breakdown</h4>
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Exact sum of all heads equals the Structure Total Gross.</p>
                        </div>
                        <button type="button" @click="addHead()" class="h-9 px-4 bg-indigo-50 dark:bg-themeBlue/15 text-themeBlue hover:bg-themeBlue hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                            + Add Fee Head
                        </button>
                    </div>

                    <template x-for="(item, idx) in form.items" :key="'head-row-' + idx">
                        <div class="p-3.5 rounded-2xl border border-gray-150 dark:border-white/[0.08] bg-gray-50/40 dark:bg-themeDark/30 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <!-- Fee Category Dropdown (Rule 7) -->
                            <div class="md:col-span-3 relative" @click.away="if (activeHeadDropdown === idx) activeHeadDropdown = null">
                                <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1">Category *</label>
                                <button type="button" @click="activeHeadDropdown = (activeHeadDropdown === idx ? null : idx)" class="w-full h-10 px-3 bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 text-left">
                                    <span class="truncate" x-text="optionLabel('categories', item.fee_category_id, 'Choose Category')"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeHeadDropdown === idx" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-48 overflow-y-auto">
                                    <template x-for="cat in options.categories" :key="'cat-' + idx + '-' + cat.id">
                                        <button type="button" @click="item.fee_category_id = cat.id; activeHeadDropdown = null" :class="item.fee_category_id == cat.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45">
                                            <span x-text="cat.name"></span>
                                            <template x-if="item.fee_category_id == cat.id"><svg class="w-3 h-3 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></template>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Head Name -->
                            <div class="md:col-span-3">
                                <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1">Head Name *</label>
                                <input type="text" x-model.trim="item.head_name" placeholder="e.g. Admission Registration" class="w-full h-10 px-3 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-semibold text-gray-800 dark:text-white focus:outline-none focus:border-themeBlue" required>
                            </div>

                            <!-- Amount -->
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1">Amount (৳) *</label>
                                <input type="text" inputmode="decimal" x-model="item.amount" placeholder="0.00" class="w-full h-10 px-3 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-mono font-bold text-gray-900 dark:text-white text-right focus:outline-none focus:border-themeBlue" required>
                            </div>

                            <!-- Flags -->
                            <div class="md:col-span-3 flex flex-wrap items-center gap-3 pb-1.5">
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input type="checkbox" x-model="item.is_discountable" class="w-3.5 h-3.5 rounded text-themeBlue">
                                    <span>Discountable</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-themeBlue cursor-pointer">
                                    <input type="checkbox" x-model="item.is_tuition_head" class="w-3.5 h-3.5 rounded text-themeBlue">
                                    <span>Tuition Head</span>
                                </label>
                            </div>

                            <!-- Remove -->
                            <div class="md:col-span-1 flex justify-end pb-1">
                                <button type="button"
                                        @click="removeHead(idx)"
                                        :disabled="form.items.length <= 1"
                                        class="h-8 w-8 rounded-lg border border-rose-200 dark:border-rose-500/30 text-rose-600 hover:bg-rose-50 flex items-center justify-center disabled:opacity-40">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Live Total Preview & Notes -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-3 border-t border-gray-100 dark:border-white/[0.06]">
                    <div class="flex-1">
                        <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1">Optional Notes</label>
                        <input type="text" x-model.trim="form.notes" placeholder="Optional internal remarks" class="w-full h-10 px-3 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark text-xs font-semibold text-gray-800 dark:text-white focus:outline-none focus:border-themeBlue">
                    </div>
                    <div class="px-4 py-2.5 rounded-2xl bg-indigo-50/70 dark:bg-themeBlue/10 border border-indigo-100 dark:border-themeBlue/20 text-right">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Computed Total Gross</div>
                        <div class="text-lg font-black font-mono text-themeGreen" x-text="'৳ ' + computedTotalString()"></div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-white/[0.06]">
                    <button type="button" @click="closeModal()" class="h-11 px-5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-wider transition-all">Cancel</button>
                    <button type="submit"
                            :disabled="isSubmitting"
                            class="h-11 px-8 rounded-xl bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black uppercase tracking-widest shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-60">
                        <span x-show="!isSubmitting" x-text="isEditMode ? 'Save as Version v' + nextVersionNumber : 'Create Structure'"></span>
                        <span x-show="isSubmitting" x-cloak>Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick View Structure Modal (Compact Popover, AGENTS.md Rule 3 & Rule 8 z-[9999]) -->
    <div id="quickViewModal"
         x-show="quickViewModalOpen"
         x-cloak
         @keydown.escape.window="if (quickViewModalOpen) closeQuickViewModal()"
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-themeDark/40 backdrop-blur-md p-4 overflow-y-auto">
        <div @click.away="closeQuickViewModal()"
             class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.08] shadow-2xl w-full max-w-xl my-6 overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight" x-text="quickViewItem ? quickViewItem.structure_name : ''"></h3>
                        <span class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-themeBlue/15 text-themeBlue font-mono font-black text-[10px]"
                              x-text="quickViewItem ? ('v' + quickViewItem.version) : ''"></span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider"
                              :class="quickViewItem ? statusBadgeClass(quickViewItem.status) : ''"
                              x-text="quickViewItem ? quickViewItem.status : ''"></span>
                    </div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1"
                       x-text="quickViewItem ? (quickViewItem.branch_name + ' · Class ' + quickViewItem.class_name + ' · ' + (quickViewItem.session_year_id ? ('Session ' + quickViewItem.session_name + ' Override') : 'All Sessions (Default)')) : ''"></p>
                </div>
                <button type="button" @click="closeQuickViewModal()" class="h-8 w-8 rounded-xl border border-gray-200 dark:border-white/[0.08] flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-white flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-gray-50/80 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] px-3.5 py-2.5">
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400">Scope</div>
                        <div class="text-xs font-bold mt-0.5 truncate"
                             :class="quickViewItem && quickViewItem.session_year_id ? 'text-amber-600 dark:text-amber-400' : 'text-themeBlue'"
                             x-text="quickViewItem ? (quickViewItem.session_year_id ? ('Session ' + quickViewItem.session_name) : 'All Sessions') : ''"></div>
                    </div>
                    <div class="rounded-2xl bg-gray-50/80 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] px-3.5 py-2.5">
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400">First Tuition</div>
                        <div class="text-xs font-bold text-gray-800 dark:text-gray-100 mt-0.5 truncate"
                             x-text="quickViewItem ? (quickViewItem.includes_monthly_tuition ? quickViewItem.tuition_month_name : 'Excluded') : ''"></div>
                    </div>
                    <div class="rounded-2xl bg-gray-50/80 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] px-3.5 py-2.5 text-right">
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400">Total Gross</div>
                        <div class="text-sm font-mono font-black text-themeGreen mt-0.5"
                             x-text="quickViewItem ? ('৳ ' + quickViewItem.total_amount) : ''"></div>
                    </div>
                </div>

                <template x-if="quickViewItem && quickViewItem.notes">
                    <div class="rounded-2xl border border-gray-100 dark:border-white/[0.06] bg-gray-50/50 dark:bg-themeDark/40 px-3.5 py-2 text-xs font-medium text-gray-600 dark:text-gray-300" x-text="quickViewItem.notes"></div>
                </template>

                <div class="rounded-2xl border border-gray-100 dark:border-white/[0.08] overflow-hidden">
                    <div class="max-h-72 overflow-y-auto">
                        <table class="w-full text-left border-collapse table [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-gray-50/80 dark:[&>thead>tr>th]:!bg-themeDark/70 [&>thead>tr>th]:!py-2 [&>thead>tr>th]:!px-3 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-100 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-2 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.05] [&>tbody>tr:last-child>td]:!border-b-0">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent text-[9px] font-black text-gray-400 uppercase tracking-widest text-center w-10">#</th>
                                    <th class="!bg-transparent text-[9px] font-black text-gray-400 uppercase tracking-widest">Fee Head</th>
                                    <th class="!bg-transparent text-[9px] font-black text-gray-400 uppercase tracking-widest">Category</th>
                                    <th class="!bg-transparent text-[9px] font-black text-gray-400 uppercase tracking-widest text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in (quickViewItem ? quickViewItem.items : [])" :key="'qvi-' + item.id">
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeDark/30">
                                        <td class="text-center font-mono font-bold text-xs text-gray-400" x-text="idx + 1"></td>
                                        <td>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="text-xs font-bold text-gray-900 dark:text-gray-100" x-text="item.head_name"></span>
                                                <template x-if="item.is_tuition_head">
                                                    <span class="px-1.5 py-0.5 rounded bg-sky-100 dark:bg-themeBlue/20 text-[9px] font-black uppercase text-themeBlue">Tuition</span>
                                                </template>
                                                <template x-if="!item.is_discountable">
                                                    <span class="px-1.5 py-0.5 rounded bg-gray-150 dark:bg-gray-800 text-[9px] font-black uppercase text-gray-500">Locked</span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="item.category_name"></td>
                                        <td class="text-right font-mono font-bold text-xs text-gray-900 dark:text-white whitespace-nowrap" x-text="'৳ ' + item.amount"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-white/[0.06]">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400"
                          x-text="quickViewItem ? (quickViewItem.items_count + ' head(s) · ' + quickViewItem.invoices_count + ' invoice(s)') : ''"></span>
                    <div class="flex items-center gap-2.5">
                        <button type="button"
                                @click="editFromQuickView()"
                                class="h-10 px-4 rounded-xl bg-sky-50 dark:bg-themeBlue/15 text-themeBlue border border-sky-200 dark:border-themeBlue/30 text-[10px] font-black uppercase tracking-widest hover:bg-themeBlue hover:text-white transition-all"
                                x-text="quickViewItem ? ('Edit as v' + (Number(quickViewItem.version) + 1)) : 'Edit'"></button>
                        <button type="button"
                                @click="closeQuickViewModal()"
                                class="h-10 px-5 rounded-xl border-2 border-gray-100 dark:border-gray-800 text-xs font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-themeDark transition-all">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    window.admissionStructureConsole = function(config) {
        return {
            schemaReady: Boolean(config.schemaReady),
            structures: config.structures || [],
            options: config.options,
            filters: { ...config.initialFilters },
            activeFilterDropdown: null,
            activeModalDropdown: null,
            activeHeadDropdown: null,
            quickViewModalOpen: false,
            quickViewItem: null,
            isLoading: false,
            modalOpen: false,
            isEditMode: false,
            editingId: null,
            nextVersionNumber: 1,
            isSubmitting: false,
            modalError: '',
            form: {
                branch_id: '',
                class_id: '',
                session_year_id: '',
                structure_name: '',
                status: 'Active',
                replace_active: true,
                includes_monthly_tuition: false,
                tuition_month_number: '1',
                notes: '',
                items: []
            },

            toggleFilterDropdown(name) {
                this.activeFilterDropdown = this.activeFilterDropdown === name ? null : name;
            },

            chooseFilter(field, val) {
                this.filters[field] = String(val);
                this.activeFilterDropdown = null;
            },

            clearFilter(field) {
                this.filters[field] = '';
                this.activeFilterDropdown = null;
            },

            optionLabel(group, idVal, fallback) {
                if (!idVal && idVal !== 0) return fallback;
                const match = (this.options[group] || []).find(o => String(o.id) === String(idVal));
                return match ? match.name : fallback;
            },

            statusBadgeClass(status) {
                if (status === 'Active') return 'bg-green-100 text-themeGreen dark:bg-themeGreen/20 dark:text-themeGreen';
                if (status === 'Archived') return 'bg-gray-150 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
                return 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300';
            },

            openQuickViewModal(st) {
                this.quickViewItem = st;
                this.quickViewModalOpen = true;
            },

            closeQuickViewModal() {
                this.quickViewModalOpen = false;
                this.quickViewItem = null;
            },

            editFromQuickView() {
                if (!this.quickViewItem) return;
                const target = this.quickViewItem;
                this.closeQuickViewModal();
                this.openEditVersionModal(target);
            },

            makeEmptyItem(sortOrder = 10) {
                return {
                    fee_category_id: '',
                    head_name: '',
                    amount: '0.00',
                    is_discountable: true,
                    is_tuition_head: false,
                    sort_order: sortOrder
                };
            },

            parseToPaisa(val) {
                const raw = String(val ?? '').trim();
                if (!/^(0|[1-9]\d*)(\.\d{1,2})?$/.test(raw)) return null;
                const parts = raw.split('.');
                const whole = parseInt(parts[0], 10);
                const frac = parseInt((parts[1] || '').padEnd(2, '0'), 10);
                return (whole * 100) + frac;
            },

            computedTotalString() {
                let totalPaisa = 0;
                for (const item of this.form.items) {
                    const p = this.parseToPaisa(item.amount);
                    if (p !== null) totalPaisa += p;
                }
                const whole = Math.floor(totalPaisa / 100);
                const frac = String(totalPaisa % 100).padStart(2, '0');
                return `${whole}.${frac}`;
            },

            openCreateModal() {
                if (!this.schemaReady) return;
                this.isEditMode = false;
                this.editingId = null;
                this.nextVersionNumber = 1;
                this.modalError = '';
                this.activeModalDropdown = null;
                this.activeHeadDropdown = null;
                this.form = {
                    branch_id: this.filters.branch_id || '',
                    class_id: this.filters.class_id || '',
                    session_year_id: this.filters.session_year_id && this.filters.session_year_id !== 'all_time' ? this.filters.session_year_id : '',
                    structure_name: '',
                    status: 'Active',
                    replace_active: true,
                    includes_monthly_tuition: false,
                    tuition_month_number: '1',
                    notes: '',
                    items: [this.makeEmptyItem(10)]
                };
                this.modalOpen = true;
            },

            openEditVersionModal(st) {
                if (!this.schemaReady) return;
                this.isEditMode = true;
                this.editingId = st.id;
                this.nextVersionNumber = (parseInt(st.version, 10) || 1) + 1;
                this.modalError = '';
                this.activeModalDropdown = null;
                this.activeHeadDropdown = null;
                this.form = {
                    branch_id: String(st.branch_id),
                    class_id: String(st.class_id),
                    session_year_id: st.session_year_id ? String(st.session_year_id) : '',
                    structure_name: st.structure_name,
                    status: st.status === 'Archived' ? 'Active' : st.status,
                    replace_active: true,
                    includes_monthly_tuition: Boolean(st.includes_monthly_tuition),
                    tuition_month_number: st.tuition_month_number ? String(st.tuition_month_number) : '1',
                    notes: st.notes || '',
                    items: (st.items || []).map((it, idx) => ({
                        fee_category_id: String(it.fee_category_id),
                        head_name: it.head_name,
                        amount: it.amount,
                        is_discountable: Boolean(it.is_discountable),
                        is_tuition_head: Boolean(it.is_tuition_head),
                        sort_order: (idx + 1) * 10
                    }))
                };
                if (this.form.items.length === 0) this.form.items.push(this.makeEmptyItem(10));
                this.modalOpen = true;
            },

            closeModal() {
                this.modalOpen = false;
                this.activeModalDropdown = null;
                this.activeHeadDropdown = null;
            },

            addHead() {
                this.form.items.push(this.makeEmptyItem((this.form.items.length + 1) * 10));
            },

            removeHead(idx) {
                if (this.form.items.length <= 1) return;
                this.form.items.splice(idx, 1);
            },

            async fetchStructures() {
                if (!this.schemaReady || this.isLoading) return;
                this.isLoading = true;
                try {
                    const params = new URLSearchParams();
                    Object.entries(this.filters).forEach(([k, v]) => {
                        if (v !== null && v !== undefined && String(v).trim() !== '') {
                            params.set(k, String(v).trim());
                        }
                    });
                    const qs = params.toString();
                    const res = await axios.get(qs ? `${config.urls.index}?${qs}` : config.urls.index, {
                        headers: { 'Accept': 'application/json' }
                    });
                    this.structures = res.data.structures || [];
                } catch (err) {
                    if (typeof showAlert === 'function') {
                        showAlert(err?.response?.data?.message || 'Unable to load admission structures.', 'Error');
                    }
                } finally {
                    this.isLoading = false;
                }
            },

            resetFilters() {
                this.filters = { branch_id: '', class_id: '', session_year_id: '', status: '', search: '' };
                this.fetchStructures();
            },

            async submitModal() {
                if (this.isSubmitting) return;
                this.modalError = '';

                if (!this.form.branch_id || !this.form.class_id) {
                    this.modalError = 'Please select Branch and Class.';
                    return;
                }

                for (let i = 0; i < this.form.items.length; i++) {
                    const p = this.parseToPaisa(this.form.items[i].amount);
                    if (p === null) {
                        this.modalError = `Invalid amount at fee head #${i + 1}. Enter a valid decimal up to 2 decimal places.`;
                        return;
                    }
                }

                this.isSubmitting = true;
                try {
                    const payload = {
                        branch_id: parseInt(this.form.branch_id, 10),
                        class_id: parseInt(this.form.class_id, 10),
                        session_year_id: this.form.session_year_id ? parseInt(this.form.session_year_id, 10) : null,
                        structure_name: this.form.structure_name,
                        status: this.form.status,
                        replace_active: Boolean(this.form.replace_active),
                        total_amount: this.computedTotalString(),
                        includes_monthly_tuition: Boolean(this.form.includes_monthly_tuition),
                        tuition_month_number: this.form.includes_monthly_tuition ? parseInt(this.form.tuition_month_number, 10) : null,
                        notes: this.form.notes || null,
                        items: this.form.items.map((it, idx) => ({
                            fee_category_id: parseInt(it.fee_category_id, 10),
                            head_name: it.head_name,
                            amount: String(it.amount).trim(),
                            is_discountable: Boolean(it.is_discountable),
                            is_tuition_head: Boolean(it.is_tuition_head),
                            sort_order: (idx + 1) * 10
                        }))
                    };

                    const url = this.isEditMode ? `${config.urls.base}/${this.editingId}` : config.urls.store;
                    const method = this.isEditMode ? 'put' : 'post';
                    const res = await axios[method](url, payload, { headers: { 'Accept': 'application/json' } });

                    this.closeModal();
                    if (typeof showSuccess === 'function') {
                        await showSuccess(res.data.message || 'Saved successfully.');
                    } else if (typeof showAlert === 'function') {
                        await showAlert(res.data.message || 'Saved successfully.', 'Success');
                    }
                    await this.fetchStructures();
                } catch (err) {
                    this.modalError = err?.response?.data?.message || 'Unable to save Admission Fee Structure.';
                } finally {
                    this.isSubmitting = false;
                }
            },

            async activateStructure(st) {
                const confirmed = typeof showConfirm === 'function'
                    ? await showConfirm('Activate Structure', `Activate '${st.structure_name}' (v${st.version})? Any active structure in this scope will be archived cleanly.`)
                    : true;
                if (!confirmed) return;

                try {
                    const res = await axios.post(`${config.urls.base}/${st.id}/activate`, { deactivate_existing: true }, { headers: { 'Accept': 'application/json' } });
                    if (typeof showSuccess === 'function') await showSuccess(res.data.message);
                    await this.fetchStructures();
                } catch (err) {
                    if (typeof showAlert === 'function') showAlert(err?.response?.data?.message || 'Activation failed.', 'Error');
                }
            },

            async deactivateStructure(st) {
                const confirmed = typeof showConfirm === 'function'
                    ? await showConfirm('Deactivate Structure', `Deactivate '${st.structure_name}' (v${st.version})? New student admissions in this scope will not use it unless activated again.`)
                    : true;
                if (!confirmed) return;

                try {
                    const res = await axios.post(`${config.urls.base}/${st.id}/deactivate`, { archive: false }, { headers: { 'Accept': 'application/json' } });
                    if (typeof showSuccess === 'function') await showSuccess(res.data.message);
                    await this.fetchStructures();
                } catch (err) {
                    if (typeof showAlert === 'function') showAlert(err?.response?.data?.message || 'Deactivation failed.', 'Error');
                }
            },

            async deleteDraftStructure(st) {
                const confirmed = typeof showDanger === 'function'
                    ? await showDanger('Delete Draft Structure', `Delete unused draft '${st.structure_name}' (v${st.version}) permanently?`)
                    : (typeof showConfirm === 'function' ? await showConfirm('Delete Draft Structure', `Delete '${st.structure_name}'?`) : true);
                if (!confirmed) return;

                try {
                    const res = await axios.delete(`${config.urls.base}/${st.id}`, { headers: { 'Accept': 'application/json' } });
                    if (typeof showSuccess === 'function') await showSuccess(res.data.message);
                    await this.fetchStructures();
                } catch (err) {
                    if (typeof showAlert === 'function') showAlert(err?.response?.data?.message || 'Delete failed.', 'Error');
                }
            }
        };
    };
</script>
@endpush
