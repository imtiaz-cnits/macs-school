@extends('tyro-dashboard::layouts.admin')

@section('title', 'Admission Fee Reports')

@php
    $branchesPayload = $branches->map(fn ($b) => ['id' => (string) $b->id, 'name' => (string) $b->branch_name])->values();
    $sessionsPayload = $sessions->map(fn ($s) => ['id' => (string) $s->id, 'name' => (string) $s->session_name])->values();
    $classesPayload = $classes->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->class_name])->values();
    $categoriesPayload = $feeCategories->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])->values();
@endphp

@section('content')
<div class="w-full min-w-0 max-w-full min-h-screen"
     x-data="admissionReportConsole({
         initialReport: @js($reportData),
         schemaReady: @js((bool) $schemaReady),
         schemaWarning: @js($schemaWarning ?? ''),
         dataUrl: @js(route('fees.admission.reports.data')),
         printBaseUrl: @js(route('fees.admission.reports.print')),
         excelBaseUrl: @js(route('fees.admission.reports.export_excel')),
         options: {
             branches: @js($branchesPayload),
             sessions: @js($sessionsPayload),
             classes: @js($classesPayload),
             categories: @js($categoriesPayload),
             invoiceStatuses: [
                 { id: 'Unpaid', name: 'Unpaid' },
                 { id: 'Partial', name: 'Partial' },
                 { id: 'Paid', name: 'Paid' },
                 { id: 'Waived', name: 'Waived' },
                 { id: 'Cancelled', name: 'Cancelled (Audit Only)' }
             ],
             paymentStatuses: [
                 { id: 'Unpaid', name: 'Unpaid' },
                 { id: 'Partial', name: 'Partial' },
                 { id: 'Paid', name: 'Paid' },
                 { id: 'Waived', name: 'Waived' }
             ]
         }
     })">

    <!-- Page Header (AGENTS.md Rule 4) -->
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                Admission Fee Reports
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Reconciled branch, historical class, student, and head-wise admission ledger analytics.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end">
            <a :href="printUrl()"
               target="_blank"
               :class="!schemaReady ? 'opacity-50 pointer-events-none' : ''"
               class="h-11 px-5 bg-gray-900 hover:bg-black text-white text-xs font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print Report
            </a>

            <a :href="excelUrl()"
               :class="!schemaReady ? 'opacity-50 pointer-events-none' : ''"
               class="h-11 px-5 border-2 border-emerald-200 dark:border-emerald-800/80 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="8" y1="13" x2="16" y2="13"></line>
                    <line x1="8" y1="17" x2="16" y2="17"></line>
                </svg>
                Export Excel
            </a>
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

    <!-- Mode Switcher Tabs -->
    <div class="mb-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-2 shadow-sm flex flex-wrap items-center gap-2">
        <template x-for="tab in tabs" :key="tab.key">
            <button type="button"
                    @click="switchMode(tab.key)"
                    :disabled="!schemaReady || isLoading"
                    :class="filters.mode === tab.key
                        ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-md'
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-themeDark/50'"
                    class="h-10 px-5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2 disabled:opacity-60">
                <span x-text="tab.label"></span>
            </button>
        </template>

        <div class="ml-auto px-3 py-1.5 text-[11px] font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" :class="report.reconciliation?.is_reconciled ? 'bg-themeGreen' : 'bg-rose-500'"></span>
            <span x-text="report.mode_label"></span>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span class="font-mono text-[10px]" x-text="'Updated: ' + (report.generated_at || '')"></span>
        </div>
    </div>

    <!-- Custom Filters Card (AGENTS.md Rules 7 & 10) -->
    <div class="mb-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm">
        <form @submit.prevent="fetchReport()" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Branch Filter (Rule 7) -->
                <div class="relative" @click.away="if (activeDropdown === 'branch') activeDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Branch</label>
                        <button type="button" x-show="filters.branch_id !== ''" x-cloak @click="clearSelect('branch_id')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleDropdown('branch')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="selectedLabel('branches', filters.branch_id, 'All Branches')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="activeDropdown === 'branch' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'branch'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.branches" :key="opt.id">
                            <button type="button" @click="chooseOption('branch_id', opt.id)" :class="filters.branch_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.branch_id == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Academic Session Filter (Rule 7) -->
                <div class="relative" @click.away="if (activeDropdown === 'session') activeDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Academic Session</label>
                        <button type="button" x-show="filters.session_year_id !== ''" x-cloak @click="clearSelect('session_year_id')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleDropdown('session')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="selectedLabel('sessions', filters.session_year_id, 'All Sessions')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="activeDropdown === 'session' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'session'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.sessions" :key="opt.id">
                            <button type="button" @click="chooseOption('session_year_id', opt.id)" :class="filters.session_year_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.session_year_id == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Historical Admission Class Filter (Rule 7, Play..Ten Global Order) -->
                <div class="relative" @click.away="if (activeDropdown === 'class') activeDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Admission Class</label>
                        <button type="button" x-show="filters.class_id !== ''" x-cloak @click="clearSelect('class_id')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleDropdown('class')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="selectedLabel('classes', filters.class_id, 'All Admission Classes')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="activeDropdown === 'class' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'class'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.classes" :key="opt.id">
                            <button type="button" @click="chooseOption('class_id', opt.id)" :class="filters.class_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.class_id == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Fee Category Filter (Rule 7) -->
                <div class="relative" @click.away="if (activeDropdown === 'category') activeDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Fee Category</label>
                        <button type="button" x-show="filters.fee_category_id !== ''" x-cloak @click="clearSelect('fee_category_id')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleDropdown('category')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="selectedLabel('categories', filters.fee_category_id, 'All Fee Categories')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="activeDropdown === 'category' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'category'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.categories" :key="opt.id">
                            <button type="button" @click="chooseOption('fee_category_id', opt.id)" :class="filters.fee_category_id == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.fee_category_id == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Invoice Status Filter (Rule 7) -->
                <div class="relative" @click.away="if (activeDropdown === 'invStatus') activeDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Invoice Status</label>
                        <button type="button" x-show="filters.invoice_status !== ''" x-cloak @click="clearSelect('invoice_status')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleDropdown('invStatus')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="selectedLabel('invoiceStatuses', filters.invoice_status, 'All Active Invoices')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="activeDropdown === 'invStatus' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'invStatus'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.invoiceStatuses" :key="opt.id">
                            <button type="button" @click="chooseOption('invoice_status', opt.id)" :class="filters.invoice_status === opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.invoice_status === opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Payment Status Filter (Rule 7) -->
                <div class="relative" @click.away="if (activeDropdown === 'payStatus') activeDropdown = null">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">Payment State</label>
                        <button type="button" x-show="filters.payment_status !== ''" x-cloak @click="clearSelect('payment_status')" class="text-[10px] font-black text-themeBlue hover:underline uppercase tracking-wider">Clear</button>
                    </div>
                    <button type="button" @click="toggleDropdown('payStatus')" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="selectedLabel('paymentStatuses', filters.payment_status, 'All Payment States')"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="activeDropdown === 'payStatus' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'payStatus'" x-cloak x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options.paymentStatuses" :key="opt.id">
                            <button type="button" @click="chooseOption('payment_status', opt.id)" :class="filters.payment_status === opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="filters.payment_status === opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Date From (AGENTS.md Rule 10 Alpine DatePicker) -->
                <div x-data="datePicker(filters.date_from)"
                     @date-selected="filters.date_from = $event.detail || ''"
                     @reset-admission-dates.window="clearDateSilently()"
                     @click.away="show = false; mOpen = false"
                     class="relative">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Date From</label>
                    <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    <div x-show="show" x-cloak x-transition class="absolute left-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3">
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
                                <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                                <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(d, i) in days" :key="i">
                                <button type="button" @click="selectDay(d.day)" :disabled="!d.day" :class="{'bg-themeBlue text-white font-black rounded-lg shadow-sm': isSelected(d.day), 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg': d.day && !isSelected(d.day), 'opacity-0 cursor-default': !d.day}" class="h-7 text-xs flex items-center justify-center transition-all font-semibold" x-text="d.day || ''"></button>
                            </template>
                        </div>
                        <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                            <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                            <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
                        </div>
                    </div>
                </div>

                <!-- Date To (AGENTS.md Rule 10 Alpine DatePicker) -->
                <div x-data="datePicker(filters.date_to)"
                     @date-selected="filters.date_to = $event.detail || ''"
                     @reset-admission-dates.window="clearDateSilently()"
                     @click.away="show = false; mOpen = false"
                     class="relative">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Date To</label>
                    <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    <div x-show="show" x-cloak x-transition class="absolute right-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3">
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
                                <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                                <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(d, i) in days" :key="i">
                                <button type="button" @click="selectDay(d.day)" :disabled="!d.day" :class="{'bg-themeBlue text-white font-black rounded-lg shadow-sm': isSelected(d.day), 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg': d.day && !isSelected(d.day), 'opacity-0 cursor-default': !d.day}" class="h-7 text-xs flex items-center justify-center transition-all font-semibold" x-text="d.day || ''"></button>
                            </template>
                        </div>
                        <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-100 dark:border-white/[0.06]">
                            <button type="button" @click="clearDate()" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors">Clear</button>
                            <button type="button" @click="selectToday()" class="text-[10px] font-bold text-themeBlue hover:underline transition-colors">Today</button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Search Input, Option A Explanation & Submit Actions -->
            <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center pt-1">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="text"
                           x-model.trim="filters.search"
                           placeholder="Search Invoice No, Student ID, Student Name, or Package"
                           class="w-full h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    <input type="text"
                           x-model.trim="filters.head_name"
                           placeholder="Optional Exact Fee Head Name (e.g. Admission Fee)"
                           class="w-full h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>

                <div class="flex gap-2 justify-end">
                    <button type="submit"
                            :disabled="!schemaReady || isLoading"
                            class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95 disabled:opacity-60">
                        <span x-show="!isLoading">Generate Report</span>
                        <span x-show="isLoading" x-cloak>Loading...</span>
                    </button>
                    <button type="button"
                            @click="resetFilters()"
                            :disabled="!schemaReady || isLoading"
                            class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-wider transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 disabled:opacity-60">
                        Reset
                    </button>
                </div>
            </div>

            <div class="text-[11px] font-medium text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-2 pt-1">
                <span class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black text-[10px] uppercase tracking-wider">Option A Date Split</span>
                <span>Gross, Discount, Net Receivable, Cumulative Collected &amp; Due filter by <strong class="font-bold text-gray-700 dark:text-gray-300">Invoice / Due Date</strong>; Period Collected independently sums active cash by <strong class="font-bold text-gray-700 dark:text-gray-300">Payment Date</strong>.</span>
            </div>
        </form>
    </div>

    <!-- Error Notice Banner -->
    <div x-show="errorMessage !== ''" x-cloak class="mb-6 rounded-2xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-950/30 px-4 py-3 text-xs font-bold text-rose-700 dark:text-rose-300" x-text="errorMessage"></div>

    <!-- KPI Cards Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Gross Billed</div>
            <div class="text-xl font-black font-mono text-gray-900 dark:text-white mt-1.5" x-text="'৳ ' + (report.summary?.gross_formatted || '0.00')"></div>
            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1" x-text="(report.summary?.active_invoice_count ?? 0) + ' active invoices'"></div>
        </div>

        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-400">Discount</div>
            <div class="text-xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1.5" x-text="'৳ ' + (report.summary?.discount_formatted || '0.00')"></div>
            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1" x-text="(report.summary?.active_student_count ?? 0) + ' students'"></div>
        </div>

        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="text-[10px] font-black uppercase tracking-widest text-themeBlue">Net Receivable</div>
            <div class="text-xl font-black font-mono text-themeBlue mt-1.5" x-text="'৳ ' + (report.summary?.net_receivable_formatted || '0.00')"></div>
            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1">Gross minus discount</div>
        </div>

        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="text-[10px] font-black uppercase tracking-widest text-themeGreen">Collected (Cumul.)</div>
            <div class="text-xl font-black font-mono text-themeGreen mt-1.5" x-text="'৳ ' + (report.summary?.collected_formatted || '0.00')"></div>
            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1" x-text="'Rate: ' + (report.summary?.collection_rate || '0.0') + '%'"></div>
        </div>

        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-400">Outstanding Due</div>
            <div class="text-xl font-black font-mono text-rose-600 dark:text-rose-400 mt-1.5" x-text="'৳ ' + (report.summary?.due_formatted || '0.00')"></div>
            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1">Net minus collected</div>
        </div>

        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Period Collected</div>
            <div class="text-xl font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1.5" x-text="'৳ ' + (report.summary?.period_collected_formatted || '0.00')"></div>
            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500 mt-1">By active payment date</div>
        </div>
    </div>

    <!-- Reconciliation Integrity Bar -->
    <div class="mb-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5"
                  :class="report.reconciliation?.is_reconciled ? 'bg-emerald-50 dark:bg-themeGreen/10 text-themeGreen border border-emerald-200/70 dark:border-themeGreen/30' : 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30'">
                <span x-text="report.reconciliation?.is_reconciled ? 'Financial Reconciliation Verified' : 'Reconciliation Variance Detected'"></span>
            </span>

            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 font-mono">
                Net = Collected + Due:
                <strong x-text="report.reconciliation?.net_minus_collected_equals_due ? 'MATCHED' : 'MISMATCH'"></strong>
            </span>

            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 font-mono">
                Diff (Net/Coll/Due/Period):
                <span x-text="(report.reconciliation?.net_diff || '0.00') + ' / ' + (report.reconciliation?.collected_diff || '0.00') + ' / ' + (report.reconciliation?.due_diff || '0.00') + ' / ' + (report.reconciliation?.period_collected_diff || '0.00')"></span>
            </span>
        </div>

        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/25 border border-amber-200/70 dark:border-amber-500/20 text-[11px] font-bold text-amber-800 dark:text-amber-300">
                Cancelled Audit:
                <span class="font-mono font-black" x-text="(report.cancelled_audit?.count ?? 0) + ' inv · ৳' + (report.cancelled_audit?.net_amount_formatted || '0.00')"></span>
            </span>
        </div>
    </div>

    <!-- Main Directory Report Card (AGENTS.md Rule 9 Borderless Table) -->
    <div class="w-full min-w-0 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-4 sm:p-5 shadow-sm mb-6">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 pb-3 mb-2 border-b border-gray-100 dark:border-white/[0.06]">
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-white" x-text="report.mode_label"></h2>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 font-mono"
                      x-text="report.mode === 'class' && filters.class_view === 'matrix'
                          ? ((report.class_matrix?.rows?.length || 0) + ' class(es) · ' + (report.class_matrix?.head_columns?.length || 0) + ' head(s)')
                          : ((report.rows?.length || 0) + ' row(s)')"></span>
            </div>

            <!-- Class-wise Matrix View & Metric Controls -->
            <div x-show="report.mode === 'class'" x-cloak class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center p-1 rounded-xl bg-gray-100 dark:bg-themeDark/70 border border-gray-200/70 dark:border-white/[0.06]">
                    <button type="button"
                            @click="switchClassView('matrix')"
                            :class="filters.class_view === 'matrix' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                            class="h-7 px-3 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                        Head Matrix
                    </button>
                    <button type="button"
                            @click="switchClassView('summary')"
                            :class="filters.class_view === 'summary' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'"
                            class="h-7 px-3 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                        Summary
                    </button>
                </div>

                <div x-show="filters.class_view === 'matrix'" class="flex flex-wrap items-center gap-1.5">
                    <template x-for="mOpt in matrixMetrics" :key="'mm-' + mOpt.key">
                        <button type="button"
                                @click="switchMatrixMetric(mOpt.key)"
                                :disabled="!schemaReady || isLoading"
                                :class="filters.matrix_metric === mOpt.key
                                    ? 'bg-themeBlue text-white shadow-sm'
                                    : 'bg-gray-50 dark:bg-themeDark/60 text-gray-600 dark:text-gray-300 border border-gray-200/70 dark:border-white/[0.08] hover:border-themeBlue/40'"
                                class="h-7 px-2.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all disabled:opacity-50"
                                x-text="mOpt.label"></button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Class-wise Horizontal Fee Head Matrix (AGENTS.md Rule 9 Compact Borderless) -->
        <div x-show="!isLoading && report.mode === 'class' && filters.class_view === 'matrix'" x-cloak class="w-full min-w-0">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                <span>
                    Active Head Metric:
                    <strong class="text-themeBlue font-black uppercase tracking-wider" x-text="report.class_matrix?.metric_label || 'Net Receivable'"></strong>
                </span>
                <span class="font-mono text-[10px]" x-text="'Matrix Balance: ' + (report.class_matrix?.is_reconciled ? 'VERIFIED' : 'CHECK')"></span>
            </div>

            <div class="table-container w-full max-w-full bg-transparent !border-none !shadow-none !mt-1 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table [&>thead>tr>th]:!bg-transparent [&>thead>tr>th:first-child]:!bg-white dark:[&>thead>tr>th:first-child]:!bg-themeNavy [&>thead>tr>th]:!py-2 [&>thead>tr>th]:!px-1 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:first-child]:!pr-2 [&>thead>tr>th]:!text-[9.5px] [&>thead>tr>th]:!tracking-[0.04em] [&>thead>tr>th]:!leading-tight [&>thead>tr>th]:!text-gray-400 dark:[&>thead>tr>th]:!text-gray-500 [&>thead>tr>th.is-tuition]:!text-themeBlue [&>thead>tr>th.col-gross]:!text-gray-600 dark:[&>thead>tr>th.col-gross]:!text-gray-300 [&>thead>tr>th.col-disc]:!text-amber-600 dark:[&>thead>tr>th.col-disc]:!text-amber-400 [&>thead>tr>th.col-net]:!text-themeBlue [&>thead>tr>th.col-coll]:!text-themeGreen [&>thead>tr>th.col-due]:!text-rose-500 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr>td]:!py-1.5 [&>tbody>tr>td]:!px-1 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:first-child]:!pr-2 [&>tbody>tr>td]:!text-[11px] [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr>td.cell-zero]:!text-gray-300 dark:[&>tbody>tr>td.cell-zero]:!text-gray-600 [&>tbody>tr>td.cell-active]:!text-gray-900 dark:[&>tbody>tr>td.cell-active]:!text-gray-100 [&>tbody>tr>td.col-count]:!text-gray-600 dark:[&>tbody>tr>td.col-count]:!text-gray-300 [&>tbody>tr>td.col-disc]:!text-amber-600 dark:[&>tbody>tr>td.col-disc]:!text-amber-400 [&>tbody>tr>td.col-net]:!text-themeBlue [&>tbody>tr>td.col-coll]:!text-themeGreen [&>tbody>tr>td.col-due]:!text-rose-600 dark:[&>tbody>tr>td.col-due]:!text-rose-400 [&>tbody>tr:hover>td]:!bg-gray-50/70 dark:[&>tbody>tr:hover>td]:!bg-themeDark/40 [&>tbody>tr>td:first-child]:!bg-white dark:[&>tbody>tr>td:first-child]:!bg-themeNavy [&>tbody>tr:hover>td:first-child]:!bg-gray-50 dark:[&>tbody>tr:hover>td:first-child]:!bg-themeDark [&>tfoot>tr>td]:!py-2 [&>tfoot>tr>td]:!px-1 [&>tfoot>tr>td:first-child]:!pl-0.5 [&>tfoot>tr>td:first-child]:!pr-2 [&>tfoot>tr>td]:!text-[11px] [&>tfoot>tr>td]:!bg-gray-50/90 dark:[&>tfoot>tr>td]:!bg-themeDark/70 [&>tfoot>tr>td:first-child]:!bg-gray-50 dark:[&>tfoot>tr>td:first-child]:!bg-themeDark [&>tfoot>tr>td]:!border-t-2 [&>tfoot>tr>td]:!border-b-0 [&>tfoot>tr>td]:!border-gray-200 dark:[&>tfoot>tr>td]:!border-white/[0.12] [&>tfoot>tr>td.col-disc]:!text-amber-600 dark:[&>tfoot>tr>td.col-disc]:!text-amber-400 [&>tfoot>tr>td.col-net]:!text-themeBlue [&>tfoot>tr>td.col-coll]:!text-themeGreen [&>tfoot>tr>td.col-due]:!text-rose-600 dark:[&>tfoot>tr>td.col-due]:!text-rose-400">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent sticky left-0 z-10 border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-left whitespace-nowrap">Class</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-center whitespace-nowrap">Inv</th>
                            <th class="!bg-transparent border-b border-r border-gray-100 dark:border-white/[0.06] !py-0 !px-0 font-black uppercase text-center whitespace-nowrap">Std</th>

                            <template x-for="col in (report.class_matrix?.head_columns || [])" :key="'mh-' + col.head_key">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-right align-bottom max-w-[72px]"
                                    :class="col.is_tuition_head ? 'is-tuition' : ''"
                                    :title="col.head_name + ' (' + col.category_name + ')'">
                                    <div class="leading-[1.15]" x-text="col.head_name"></div>
                                </th>
                            </template>

                            <th class="!bg-transparent col-gross border-b border-l border-gray-200/80 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-right whitespace-nowrap">Gross</th>
                            <th class="!bg-transparent col-disc border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-right whitespace-nowrap">Disc.</th>
                            <th class="!bg-transparent col-net border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-right whitespace-nowrap">Net</th>
                            <th class="!bg-transparent col-coll border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-right whitespace-nowrap">Coll.</th>
                            <th class="!bg-transparent col-due border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 font-black uppercase text-right whitespace-nowrap">Due</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.06]">
                        <template x-if="!report.class_matrix?.rows || report.class_matrix.rows.length === 0">
                            <tr>
                                <td colspan="24" class="text-center">
                                    <div class="py-8">
                                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400"
                                           x-text="!schemaReady ? 'No admission report records available while schema migrations are pending.' : 'No admission fee records matched the selected filters.'"></p>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="mRow in (report.class_matrix?.rows || [])" :key="'mr-' + mRow.class_id">
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                <td class="py-0 px-0 sticky left-0 z-10 font-bold whitespace-nowrap"
                                    :class="mRow.invoice_count > 0 ? 'cell-active' : 'col-count'"
                                    x-text="mRow.class_name"></td>
                                <td class="py-0 px-0 col-count text-center font-mono font-bold" x-text="mRow.invoice_count"></td>
                                <td class="py-0 px-0 col-count border-r border-gray-100 dark:border-white/[0.06] text-center font-mono font-bold" x-text="mRow.student_count"></td>

                                <template x-for="col in (report.class_matrix?.head_columns || [])" :key="'mc-' + mRow.class_id + '-' + col.head_key">
                                    <td class="py-0 px-0 text-right font-mono whitespace-nowrap"
                                        :class="mRow.cells?.[col.head_key]?.is_zero ? 'cell-zero font-normal' : 'cell-active font-bold'"
                                        x-text="mRow.cells?.[col.head_key]?.active_value_formatted || '0.00'"></td>
                                </template>

                                <td class="py-0 px-0 border-l border-gray-200/80 dark:border-white/[0.08] text-right font-mono font-bold whitespace-nowrap"
                                    :class="mRow.invoice_count > 0 ? 'col-count' : 'cell-zero font-normal'"
                                    x-text="mRow.gross_formatted"></td>
                                <td class="py-0 px-0 text-right font-mono font-bold whitespace-nowrap"
                                    :class="mRow.discount_formatted !== '0.00' ? 'col-disc' : 'cell-zero font-normal'"
                                    x-text="mRow.discount_formatted"></td>
                                <td class="py-0 px-0 text-right font-mono font-black whitespace-nowrap"
                                    :class="mRow.net_receivable_formatted !== '0.00' ? 'col-net' : 'cell-zero font-normal'"
                                    x-text="mRow.net_receivable_formatted"></td>
                                <td class="py-0 px-0 text-right font-mono font-black whitespace-nowrap"
                                    :class="mRow.collected_formatted !== '0.00' ? 'col-coll' : 'cell-zero font-normal'"
                                    x-text="mRow.collected_formatted"></td>
                                <td class="py-0 px-0 text-right font-mono font-black whitespace-nowrap"
                                    :class="mRow.due_formatted !== '0.00' ? 'col-due' : 'cell-zero font-normal'"
                                    x-text="mRow.due_formatted"></td>
                            </tr>
                        </template>
                    </tbody>

                    <tfoot x-show="report.class_matrix?.rows && report.class_matrix.rows.length > 0" x-cloak>
                        <tr>
                            <td class="sticky left-0 z-10 font-black uppercase tracking-wider whitespace-nowrap">Grand Total</td>
                            <td class="text-center font-mono font-black" x-text="report.summary?.active_invoice_count ?? 0"></td>
                            <td class="border-r border-gray-200/80 dark:border-white/[0.08] text-center font-mono font-black" x-text="report.summary?.active_student_count ?? 0"></td>

                            <template x-for="col in (report.class_matrix?.head_columns || [])" :key="'mt-' + col.head_key">
                                <td class="text-right font-mono font-black whitespace-nowrap"
                                    x-text="report.class_matrix?.column_totals?.[col.head_key]?.active_value_formatted || '0.00'"></td>
                            </template>

                            <td class="border-l border-gray-200/80 dark:border-white/[0.08] text-right font-mono font-black whitespace-nowrap" x-text="report.summary?.gross_formatted || '0.00'"></td>
                            <td class="col-disc text-right font-mono font-black whitespace-nowrap" x-text="report.summary?.discount_formatted || '0.00'"></td>
                            <td class="col-net text-right font-mono font-black whitespace-nowrap" x-text="report.summary?.net_receivable_formatted || '0.00'"></td>
                            <td class="col-coll text-right font-mono font-black whitespace-nowrap" x-text="report.summary?.collected_formatted || '0.00'"></td>
                            <td class="col-due text-right font-mono font-black whitespace-nowrap" x-text="report.summary?.due_formatted || '0.00'"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div x-show="isLoading || report.mode !== 'class' || filters.class_view === 'summary'" class="table-container w-full max-w-full bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>thead>tr>th]:!text-gray-400 dark:[&>thead>tr>th]:!text-gray-500 [&>thead>tr>th.col-net]:!text-themeBlue [&>thead>tr>th.col-coll]:!text-themeGreen [&>thead>tr>th.col-due]:!text-rose-500 [&>thead>tr>th.col-period]:!text-indigo-500 [&>tbody>tr>td]:!py-2.5 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeNavy/25 [&>tfoot>tr>td]:!py-2.5 [&>tfoot>tr>td]:!px-3 [&>tfoot>tr>td:first-child]:!pl-0.5 [&>tfoot>tr>td]:!bg-gray-50/80 dark:[&>tfoot>tr>td]:!bg-themeDark/60 [&>tfoot>tr>td]:!border-t-2 [&>tfoot>tr>td]:!border-b-0 [&>tfoot>tr>td]:!border-gray-200 dark:[&>tfoot>tr>td]:!border-white/[0.12]">
                <thead>
                    <tr class="!bg-transparent">
                        <template x-if="report.mode === 'branch'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Branch</th>
                        </template>
                        <template x-if="report.mode === 'class'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Admission Class</th>
                        </template>
                        <template x-if="report.mode === 'head'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Fee Head / Category</th>
                        </template>
                        <template x-if="report.mode === 'student'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Invoice &amp; Student</th>
                        </template>

                        <template x-if="report.mode === 'student'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Admission Class / Branch</th>
                        </template>
                        <template x-if="report.mode === 'student'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date / Status</th>
                        </template>
                        <template x-if="report.mode !== 'student'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Invoices</th>
                        </template>
                        <template x-if="report.mode === 'branch' || report.mode === 'class'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Students</th>
                        </template>

                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Gross</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Discount</th>
                        <th class="!bg-transparent col-net border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-themeBlue uppercase tracking-[0.2em] text-right">Net Receivable</th>
                        <th class="!bg-transparent col-coll border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-themeGreen uppercase tracking-[0.2em] text-right">Collected (Cumul.)</th>
                        <th class="!bg-transparent col-due border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-rose-500 uppercase tracking-[0.2em] text-right">Due</th>
                        <th class="!bg-transparent col-period border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] text-right">Period Collected</th>
                        <template x-if="report.mode !== 'student'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Rate</th>
                        </template>
                        <template x-if="report.mode === 'student'">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Heads / POS</th>
                        </template>
                    </tr>
                </thead>

                <!-- Animated Skeleton Loading Rows (AGENTS.md Rule 9) -->
                <tbody x-show="isLoading" x-cloak class="divide-y divide-gray-100 dark:divide-white/[0.06]">
                    @for($s = 0; $s < 5; $s++)
                        <tr class="animate-pulse">
                            <td><div class="h-4 w-36 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                            <td><div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                            <td x-show="report.mode !== 'head'"><div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                            <td><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td><div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                            <td><div class="h-4 w-14 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                        </tr>
                    @endfor
                </tbody>

                <!-- Data Rows -->
                <tbody x-show="!isLoading" class="divide-y divide-gray-100 dark:divide-white/[0.06]">
                    <template x-if="!report.rows || report.rows.length === 0">
                        <tr>
                            <td colspan="12" class="text-center">
                                <div class="py-10">
                                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400"
                                       x-text="!schemaReady ? 'No admission report records available while schema migrations are pending.' : 'No admission fee records matched the selected filters.'"></p>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-for="(row, idx) in report.rows" :key="rowKey(row, idx)">
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <!-- Branch Column -->
                            <template x-if="report.mode === 'branch'">
                                <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100" x-text="row.branch_name"></td>
                            </template>

                            <!-- Class Column -->
                            <template x-if="report.mode === 'class'">
                                <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100" x-text="row.class_name"></td>
                            </template>

                            <!-- Head Column -->
                            <template x-if="report.mode === 'head'">
                                <td class="py-0 px-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="row.head_name"></span>
                                        <template x-if="row.is_tuition_head">
                                            <span class="px-2 py-0.5 rounded-lg bg-sky-50 dark:bg-themeBlue/15 text-themeBlue text-[9px] font-black uppercase tracking-wider">Monthly Tuition</span>
                                        </template>
                                    </div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5" x-text="row.category_name"></div>
                                </td>
                            </template>

                            <!-- Student Columns -->
                            <template x-if="report.mode === 'student'">
                                <td class="py-0 px-0">
                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="row.student_name"></div>
                                    <div class="text-[11px] font-mono font-semibold text-gray-500 dark:text-gray-400 mt-0.5" x-text="row.invoice_no + ' · ' + row.student_identity"></div>
                                    <div class="text-[11px] font-medium text-themeBlue mt-0.5" x-text="row.structure_name"></div>
                                </td>
                            </template>
                            <template x-if="report.mode === 'student'">
                                <td class="py-0 px-0">
                                    <div class="text-sm font-bold text-gray-700 dark:text-gray-300" x-text="row.admission_class_name"></div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5" x-text="row.branch_name"></div>
                                    <template x-if="row.current_student_class_name && row.current_student_class_name !== row.admission_class_name">
                                        <div class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 mt-0.5" x-text="'Current Class: ' + row.current_student_class_name"></div>
                                    </template>
                                </td>
                            </template>
                            <template x-if="report.mode === 'student'">
                                <td class="py-0 px-0">
                                    <div class="text-xs font-mono font-bold text-gray-600 dark:text-gray-300" x-text="row.invoice_date || 'N/A'"></div>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider"
                                          :class="statusBadgeClass(row.display_status)"
                                          x-text="row.display_status"></span>
                                </td>
                            </template>

                            <!-- Aggregate Counts -->
                            <template x-if="report.mode !== 'student'">
                                <td class="py-0 px-0 text-center font-mono font-black text-gray-600 dark:text-gray-300 text-sm" x-text="row.invoice_count"></td>
                            </template>
                            <template x-if="report.mode === 'branch' || report.mode === 'class'">
                                <td class="py-0 px-0 text-center font-mono font-black text-gray-600 dark:text-gray-300 text-sm" x-text="row.student_count"></td>
                            </template>

                            <!-- Monetary Metrics -->
                            <td class="py-0 px-0 text-right font-mono font-bold text-sm text-gray-700 dark:text-gray-300" x-text="row.gross_formatted"></td>
                            <td class="py-0 px-0 text-right font-mono font-bold text-sm text-amber-600 dark:text-amber-400" x-text="row.discount_formatted"></td>
                            <td class="py-0 px-0 text-right font-mono font-black text-sm text-themeBlue" x-text="row.net_receivable_formatted"></td>
                            <td class="py-0 px-0 text-right font-mono font-black text-sm text-themeGreen" x-text="row.collected_formatted"></td>
                            <td class="py-0 px-0 text-right font-mono font-black text-sm text-rose-600 dark:text-rose-400" x-text="row.due_formatted"></td>
                            <td class="py-0 px-0 text-right font-mono font-black text-sm text-indigo-600 dark:text-indigo-400" x-text="row.period_collected_formatted"></td>

                            <!-- Rate or Student Head Breakdown Toggle + POS Print -->
                            <template x-if="report.mode !== 'student'">
                                <td class="py-0 px-0 text-right font-mono font-black text-xs text-gray-700 dark:text-gray-300" x-text="row.collection_rate + '%'"></td>
                            </template>
                            <template x-if="report.mode === 'student'">
                                <td class="py-0 px-0 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="toggleStudentHeads(row.invoice_id)"
                                                class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue flex items-center justify-center h-8 w-8 border border-gray-200 dark:border-white/[0.08] rounded-lg transition-all"
                                                title="View Fee Heads">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <a :href="row.pos_print_url"
                                           target="_blank"
                                           class="action-btn text-themeGreen hover:text-themeGreen hover:border-themeGreen flex items-center justify-center h-8 w-8 border border-gray-200 dark:border-white/[0.08] rounded-lg transition-all"
                                           title="POS Print">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </a>
                                    </div>
                                    <div x-show="expandedInvoiceId === row.invoice_id" x-cloak class="mt-2 p-2.5 rounded-xl bg-gray-50 dark:bg-themeDark text-left border border-gray-150 dark:border-white/[0.08] space-y-1">
                                        <template x-for="h in row.heads" :key="h.invoice_head_id">
                                            <div class="flex items-center justify-between gap-3 text-[11px]">
                                                <span class="font-bold text-gray-700 dark:text-gray-200" x-text="h.head_name"></span>
                                                <span class="font-mono text-gray-600 dark:text-gray-300" x-text="'Net: ৳' + h.net_formatted + ' | Paid: ৳' + h.collected_formatted + ' | Due: ৳' + h.due_formatted"></span>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>

                <!-- Grand Total Row -->
                <tfoot x-show="!isLoading && report.rows && report.rows.length > 0" x-cloak>
                    <tr class="bg-gray-50/80 dark:bg-themeDark/60 border-t-2 border-gray-200 dark:border-white/[0.12]">
                        <td class="text-xs font-black uppercase tracking-widest text-gray-900 dark:text-white">Grand Total</td>
                        <template x-if="report.mode === 'student'">
                            <td colspan="2" class="text-xs font-bold text-gray-500 dark:text-gray-400" x-text="(report.summary?.active_invoice_count ?? 0) + ' active invoices · ' + (report.summary?.active_student_count ?? 0) + ' students'"></td>
                        </template>
                        <template x-if="report.mode !== 'student'">
                            <td class="text-center font-mono font-black text-sm text-gray-900 dark:text-white" x-text="report.summary?.active_invoice_count ?? 0"></td>
                        </template>
                        <template x-if="report.mode === 'branch' || report.mode === 'class'">
                            <td class="text-center font-mono font-black text-sm text-gray-900 dark:text-white" x-text="report.summary?.active_student_count ?? 0"></td>
                        </template>
                        <td class="text-right font-mono font-black text-sm text-gray-900 dark:text-white" x-text="report.summary?.gross_formatted || '0.00'"></td>
                        <td class="text-right font-mono font-black text-sm text-amber-600 dark:text-amber-400" x-text="report.summary?.discount_formatted || '0.00'"></td>
                        <td class="text-right font-mono font-black text-sm text-themeBlue" x-text="report.summary?.net_receivable_formatted || '0.00'"></td>
                        <td class="text-right font-mono font-black text-sm text-themeGreen" x-text="report.summary?.collected_formatted || '0.00'"></td>
                        <td class="text-right font-mono font-black text-sm text-rose-600 dark:text-rose-400" x-text="report.summary?.due_formatted || '0.00'"></td>
                        <td class="text-right font-mono font-black text-sm text-indigo-600 dark:text-indigo-400" x-text="report.summary?.period_collected_formatted || '0.00'"></td>
                        <template x-if="report.mode !== 'student'">
                            <td class="text-right font-mono font-black text-xs text-gray-900 dark:text-white" x-text="(report.summary?.collection_rate || '0.0') + '%'"></td>
                        </template>
                        <template x-if="report.mode === 'student'">
                            <td></td>
                        </template>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Separate Cancelled Admission Invoices Audit Section (Excluded from Active Totals) -->
    <div x-show="schemaReady && report.cancelled_audit && report.cancelled_audit.count > 0"
         x-cloak
         class="w-full min-w-0 bg-white dark:bg-themeNavy border border-amber-200 dark:border-amber-500/25 rounded-3xl p-4 sm:p-5 shadow-sm mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 mb-3 border-b border-amber-100 dark:border-amber-500/15">
            <div>
                <h3 class="text-sm font-black uppercase tracking-wider text-amber-800 dark:text-amber-300">
                    Cancelled Admission Invoices Audit
                </h3>
                <p class="text-xs font-medium text-amber-700/80 dark:text-amber-400/80 mt-0.5">
                    Strictly isolated for audit traceability and excluded from Gross, Net Receivable, Collected, and Due totals.
                </p>
            </div>
            <div class="text-right font-mono text-xs font-black text-amber-900 dark:text-amber-200">
                <span x-text="report.cancelled_audit.count + ' Cancelled Invoice(s) · Historical Net: ৳ ' + report.cancelled_audit.net_amount_formatted"></span>
            </div>
        </div>

        <div class="table-container w-full max-w-full bg-transparent !border-none !shadow-none !mt-1 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-amber-200/70 dark:[&>thead>tr>th]:!border-amber-500/20 [&>thead>tr>th]:!text-gray-500 [&>thead>tr>th.col-net]:!text-amber-700 dark:[&>thead>tr>th.col-net]:!text-amber-300 [&>tbody>tr>td]:!py-2 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-amber-100/70 dark:[&>tbody>tr>td]:!border-white/[0.05] [&>tbody>tr:hover>td]:!bg-amber-50/40 dark:[&>tbody>tr:hover>td]:!bg-themeDark/30">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-amber-200/70 dark:border-amber-500/20 !py-0 !px-0 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Invoice No</th>
                        <th class="!bg-transparent border-b border-amber-200/70 dark:border-amber-500/20 !py-0 !px-0 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Student</th>
                        <th class="!bg-transparent border-b border-amber-200/70 dark:border-amber-500/20 !py-0 !px-0 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Admission Class / Branch</th>
                        <th class="!bg-transparent border-b border-amber-200/70 dark:border-amber-500/20 !py-0 !px-0 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Date</th>
                        <th class="!bg-transparent border-b border-amber-200/70 dark:border-amber-500/20 !py-0 !px-0 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-right">Historical Gross</th>
                        <th class="!bg-transparent border-b border-amber-200/70 dark:border-amber-500/20 !py-0 !px-0 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-right">Discount</th>
                        <th class="!bg-transparent col-net border-b border-amber-200/70 dark:border-amber-500/20 !py-0 !px-0 text-[10px] font-black text-amber-700 dark:text-amber-300 uppercase tracking-[0.2em] text-right">Historical Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-amber-100/70 dark:divide-white/[0.05]">
                    <template x-for="c in report.cancelled_audit.invoices" :key="'canc-' + c.invoice_id">
                        <tr class="hover:bg-amber-50/40 dark:hover:bg-themeDark/30 transition-colors">
                            <td class="py-0 px-0 font-mono font-bold text-xs text-gray-700 dark:text-gray-300 line-through" x-text="c.invoice_no"></td>
                            <td class="py-0 px-0 text-xs font-bold text-gray-700 dark:text-gray-300" x-text="c.student_name + ' (' + c.student_identity + ')'"></td>
                            <td class="py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400" x-text="c.admission_class_name + ' · ' + c.branch_name"></td>
                            <td class="py-0 px-0 font-mono text-xs text-gray-600 dark:text-gray-400" x-text="c.invoice_date || 'N/A'"></td>
                            <td class="py-0 px-0 text-right font-mono text-xs text-gray-600 dark:text-gray-400" x-text="c.gross_formatted"></td>
                            <td class="py-0 px-0 text-right font-mono text-xs text-gray-600 dark:text-gray-400" x-text="c.discount_formatted"></td>
                            <td class="py-0 px-0 text-right font-mono font-black text-xs text-amber-700 dark:text-amber-300" x-text="c.net_amount_formatted"></td>
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
    window.admissionReportConsole = function(config) {
        const initFilters = config.initialReport?.filters || {};
        return {
            schemaReady: Boolean(config.schemaReady),
            schemaWarning: config.schemaWarning || '',
            report: config.initialReport || { rows: [], summary: {}, cancelled_audit: { count: 0, invoices: [] }, reconciliation: { is_reconciled: true }, class_matrix: { metric: 'net_receivable', metric_label: 'Net Receivable', head_columns: [], rows: [], column_totals: {}, is_reconciled: true } },
            options: config.options,
            activeDropdown: null,
            expandedInvoiceId: null,
            isLoading: false,
            errorMessage: '',
            tabs: [
                { key: 'branch', label: 'Branch-wise' },
                { key: 'class', label: 'Class-wise' },
                { key: 'student', label: 'Student-wise' },
                { key: 'head', label: 'Fee Head-wise' }
            ],
            matrixMetrics: [
                { key: 'net_receivable', label: 'Net Receivable' },
                { key: 'collected', label: 'Collected' },
                { key: 'gross', label: 'Gross' },
                { key: 'due', label: 'Due' },
                { key: 'period_collected', label: 'Period Coll.' },
                { key: 'discount', label: 'Discount' }
            ],
            filters: {
                mode: initFilters.mode || 'branch',
                matrix_metric: initFilters.matrix_metric || 'net_receivable',
                class_view: initFilters.class_view || 'matrix',
                branch_id: initFilters.branch_id ? String(initFilters.branch_id) : '',
                session_year_id: initFilters.session_year_id ? String(initFilters.session_year_id) : '',
                class_id: initFilters.class_id ? String(initFilters.class_id) : '',
                fee_category_id: initFilters.fee_category_id ? String(initFilters.fee_category_id) : '',
                head_name: initFilters.head_name || '',
                invoice_status: initFilters.invoice_status || '',
                payment_status: initFilters.payment_status || '',
                date_from: initFilters.date_from || '',
                date_to: initFilters.date_to || '',
                search: initFilters.search || ''
            },

            toggleDropdown(name) {
                this.activeDropdown = this.activeDropdown === name ? null : name;
            },

            chooseOption(field, value) {
                this.filters[field] = String(value);
                this.activeDropdown = null;
            },

            clearSelect(field) {
                this.filters[field] = '';
                this.activeDropdown = null;
            },

            selectedLabel(optionGroup, currentVal, defaultLabel) {
                if (!currentVal) return defaultLabel;
                const found = (this.options[optionGroup] || []).find(item => String(item.id) === String(currentVal));
                return found ? found.name : defaultLabel;
            },

            switchMode(modeKey) {
                if (!this.schemaReady || this.isLoading) return;
                this.filters.mode = modeKey;
                this.expandedInvoiceId = null;
                this.fetchReport();
            },

            switchClassView(viewMode) {
                if (!this.schemaReady) return;
                this.filters.class_view = viewMode === 'summary' ? 'summary' : 'matrix';
            },

            switchMatrixMetric(metricKey) {
                if (!this.schemaReady || this.isLoading) return;
                this.filters.matrix_metric = metricKey;
                this.fetchReport();
            },

            toggleStudentHeads(invoiceId) {
                this.expandedInvoiceId = this.expandedInvoiceId === invoiceId ? null : invoiceId;
            },

            rowKey(row, idx) {
                if (this.report.mode === 'branch') return 'b-' + (row.branch_id ?? idx);
                if (this.report.mode === 'class') return 'c-' + (row.class_id ?? idx);
                if (this.report.mode === 'head') return 'h-' + (row.fee_category_id ?? 0) + '-' + (row.head_name ?? idx);
                return 's-' + (row.invoice_id ?? idx);
            },

            statusBadgeClass(status) {
                if (status === 'Paid') return 'bg-green-100 text-themeGreen dark:bg-themeGreen/20 dark:text-themeGreen';
                if (status === 'Partial') return 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300';
                if (status === 'Waived') return 'bg-sky-100 text-themeBlue dark:bg-themeBlue/20 dark:text-themeBlue';
                return 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300';
            },

            buildQueryString() {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => {
                    if (v !== null && v !== undefined && String(v).trim() !== '') {
                        params.set(k, String(v).trim());
                    }
                });
                return params.toString();
            },

            printUrl() {
                const qs = this.buildQueryString();
                return qs ? `${config.printBaseUrl}?${qs}` : config.printBaseUrl;
            },

            excelUrl() {
                const qs = this.buildQueryString();
                return qs ? `${config.excelBaseUrl}?${qs}` : config.excelBaseUrl;
            },

            async fetchReport() {
                if (!this.schemaReady || this.isLoading) return;
                this.isLoading = true;
                this.errorMessage = '';

                try {
                    const qs = this.buildQueryString();
                    const url = qs ? `${config.dataUrl}?${qs}` : config.dataUrl;
                    const response = await axios.get(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    this.report = response.data;
                } catch (err) {
                    const msg = err?.response?.data?.message || 'Unable to load admission fee report data.';
                    this.errorMessage = msg;
                    if (typeof showAlert === 'function') {
                        showAlert(msg, 'Admission Report');
                    }
                } finally {
                    this.isLoading = false;
                }
            },

            resetFilters() {
                const currentMode = this.filters.mode || 'branch';
                const currentView = this.filters.class_view || 'matrix';
                const currentMetric = this.filters.matrix_metric || 'net_receivable';
                this.filters = {
                    mode: currentMode,
                    matrix_metric: currentMetric,
                    class_view: currentView,
                    branch_id: '',
                    session_year_id: '',
                    class_id: '',
                    fee_category_id: '',
                    head_name: '',
                    invoice_status: '',
                    payment_status: '',
                    date_from: '',
                    date_to: '',
                    search: ''
                };
                this.expandedInvoiceId = null;
                window.dispatchEvent(new CustomEvent('reset-admission-dates'));
                this.fetchReport();
            }
        };
    };

    window.datePicker = function(initialValue = '') {
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
            },

            parseInitial(val) {
                if (!val) return;
                const parts = String(val).split('-');
                if (parts.length === 3) {
                    const yr = parseInt(parts[0], 10);
                    const mo = parseInt(parts[1], 10) - 1;
                    if (!isNaN(yr) && yr >= 1900) this.currentYear = yr;
                    if (!isNaN(mo) && mo >= 0 && mo <= 11) this.currentMonth = mo;
                }
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
                this.mOpen = false;
                this.$dispatch('date-selected', this.value);
            },

            clearDate() {
                this.value = '';
                this.show = false;
                this.mOpen = false;
                this.$dispatch('date-selected', '');
            },

            clearDateSilently() {
                this.value = '';
                this.show = false;
                this.mOpen = false;
            },

            selectToday() {
                const now = new Date();
                this.currentYear = now.getFullYear();
                this.currentMonth = now.getMonth();
                const formattedMonth = String(now.getMonth() + 1).padStart(2, '0');
                const formattedDay = String(now.getDate()).padStart(2, '0');
                this.value = `${this.currentYear}-${formattedMonth}-${formattedDay}`;
                this.show = false;
                this.mOpen = false;
                this.$dispatch('date-selected', this.value);
            },

            isSelected(day) {
                if (!day || !this.value) return false;
                const parts = String(this.value).split('-');
                if (parts.length === 3) {
                    return parseInt(parts[0], 10) === parseInt(this.currentYear, 10) &&
                           (parseInt(parts[1], 10) - 1) === parseInt(this.currentMonth, 10) &&
                           parseInt(parts[2], 10) === parseInt(day, 10);
                }
                return false;
            },

            formatDisplay(val) {
                if (!val) return 'Select Date';
                const parts = String(val).split('-');
                if (parts.length === 3) {
                    const yr = parseInt(parts[0], 10);
                    const mo = parseInt(parts[1], 10) - 1;
                    const da = parseInt(parts[2], 10);
                    if (!isNaN(yr) && !isNaN(mo) && !isNaN(da)) {
                        const d = new Date(yr, mo, da);
                        return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
                    }
                }
                return val;
            }
        };
    };
</script>
@endpush
