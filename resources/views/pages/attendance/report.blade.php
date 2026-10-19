@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance Report')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    /* Table padding override according to MACS Design System */
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }

    .print-only {
        display: none !important;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 6mm 7mm 6mm 7mm;
        }

        html, body {
            background: #ffffff !important;
            color: #0F1E2C !important;
            font-family: 'Figtree', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        body * {
            visibility: hidden;
        }

        #printableReportArea, #printableReportArea * {
            visibility: visible;
        }

        #printableReportArea {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            min-height: calc(297mm - 12mm) !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }

        .no-print, nav, aside, header, footer, .sidebar, .sidebar-container, .topbar, .admin-bar, .sidebar-overlay, #globalModal {
            display: none !important;
        }

        .dashboard-layout, .main-content, .page-content {
            padding: 0 !important;
            margin: 0 !important;
            display: block !important;
            width: 100% !important;
            min-height: auto !important;
            background: transparent !important;
            border: none !important;
        }

        .print-only {
            display: block !important;
        }

        .print-flex {
            display: flex !important;
        }

        /* Printable Table Formatting */
        #printableReportArea table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border: 1px solid #CBD5E1 !important;
        }

        #printableReportArea thead {
            display: table-header-group !important;
        }

        #printableReportArea tbody {
            display: table-row-group !important;
        }

        #printableReportArea tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        #printableReportArea th {
            background-color: #008ED6 !important;
            color: #ffffff !important;
            font-size: 9.5px !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 6px 8px !important;
            border: 1px solid #0072ad !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printableReportArea td {
            border: 1px solid #CBD5E1 !important;
            padding: 4px 6px !important;
            font-size: 9px !important;
            line-height: 1.3 !important;
            color: #0F1E2C !important;
            vertical-align: middle !important;
        }

        /* Fixed Table Layout & Proportional Columns for Daily Report in Print */
        #printableReportArea table.report-mode-daily {
            table-layout: fixed !important;
            width: 100% !important;
        }

        /* Unified, Consistent Table Font Size for All Headers and Cells (10px) */
        #printableReportArea table.report-mode-daily th,
        #printableReportArea table.report-mode-daily td {
            font-size: 10px !important;
            line-height: 1.35 !important;
        }

        #printableReportArea table.report-mode-daily th {
            padding: 6px 4px !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.4px !important;
        }

        #printableReportArea table.report-mode-daily td {
            padding: 5px 4px !important;
            vertical-align: middle !important;
        }

        /* Container margin reset in print to prevent blank horizontal band */
        #printableReportArea .table-container {
            overflow: visible !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Daily Report Print Columns (10 visible columns - Sum = 100%) */
        #printableReportArea table.report-mode-daily th.col-daily-sl,
        #printableReportArea table.report-mode-daily td.col-daily-sl,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-sl,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-sl,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-sl,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-sl {
            width: 3.5% !important;
            text-align: center !important;
            font-size: 10px !important;
            padding: 5px 2px !important;
            white-space: nowrap !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-roll,
        #printableReportArea table.report-mode-daily td.col-daily-roll,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-roll,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-roll,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-roll,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-roll {
            width: 5.5% !important;
            text-align: center !important;
            font-size: 10px !important;
            font-weight: 800 !important;
            padding: 5px 2px !important;
            white-space: nowrap !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-id,
        #printableReportArea table.report-mode-daily td.col-daily-id,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-id,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-id,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-id,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-id {
            width: 11.5% !important;
            text-align: left !important;
            font-size: 10px !important;
            font-family: monospace !important;
            white-space: nowrap !important;
            padding: 5px 3px !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-name,
        #printableReportArea table.report-mode-daily td.col-daily-name,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-name,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-name,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-name,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-name {
            width: 25% !important;
            text-align: left !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            padding: 5px 4px !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-class,
        #printableReportArea table.report-mode-daily td.col-daily-class,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-class,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-class,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-class,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-class {
            display: table-cell !important;
            width: 9.5% !important;
            text-align: left !important;
            font-size: 10px !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
            padding: 5px 3px !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-section,
        #printableReportArea table.report-mode-daily td.col-daily-section,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-section,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-section,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-section,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-section {
            display: table-cell !important;
            width: 5.5% !important;
            text-align: center !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            white-space: nowrap !important;
            padding: 5px 2px !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-date,
        #printableReportArea table.report-mode-daily td.col-daily-date,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-date,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-date,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-date,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-date {
            width: 13% !important;
            text-align: center !important;
            font-size: 10px !important;
            font-family: monospace !important;
            white-space: nowrap !important;
            padding: 5px 2px !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-in,
        #printableReportArea table.report-mode-daily td.col-daily-in,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-in,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-in,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-in,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-in {
            width: 8.5% !important;
            text-align: center !important;
            font-size: 10px !important;
            font-family: monospace !important;
            font-weight: 700 !important;
            white-space: nowrap !important;
            padding: 5px 2px !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-out,
        #printableReportArea table.report-mode-daily td.col-daily-out,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-out,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-out,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-out,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-out {
            width: 8.5% !important;
            text-align: center !important;
            font-size: 10px !important;
            font-family: monospace !important;
            font-weight: 700 !important;
            white-space: nowrap !important;
            padding: 5px 2px !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-status,
        #printableReportArea table.report-mode-daily td.col-daily-status,
        #printableReportArea table.report-mode-daily.has-no-class-filter th.col-daily-status,
        #printableReportArea table.report-mode-daily.has-no-class-filter td.col-daily-status,
        #printableReportArea table.report-mode-daily.has-class-filter th.col-daily-status,
        #printableReportArea table.report-mode-daily.has-class-filter td.col-daily-status {
            width: 9.5% !important;
            text-align: center !important;
            font-size: 10px !important;
            padding: 5px 2px !important;
        }

        /* Step 2: Slightly Reduced Status Badge Font Size (8.5px, Crisp & Readable) */
        #printableReportArea table.report-mode-daily th.col-daily-status span,
        #printableReportArea table.report-mode-daily td.col-daily-status span {
            display: inline-block !important;
            padding: 1px 4.5px !important;
            font-size: 8.5px !important;
            font-weight: 800 !important;
            border-radius: 9999px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-remarks,
        #printableReportArea table.report-mode-daily td.col-daily-remarks {
            display: none !important;
            width: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
        }

        #printableReportArea table.report-mode-daily th.col-daily-action,
        #printableReportArea table.report-mode-daily td.col-daily-action {
            display: none !important;
            width: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
        }

        /* Signature Block Styling (Only On Final Page - Anchored Near Bottom) */
        .print-signatures {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            margin-top: auto !important;
            padding-top: 24px !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        .print-signatures table {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
    }
</style>
@endpush

@section('content')
<div x-data="attendanceReportApp()" x-init="init()" class="w-full min-h-screen">

    <!-- Top Page Header Section (Screen Only) -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Attendance Report
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                MACS School &amp; College &bull; Real-time Daily, Monthly &amp; Student Analytics
            </p>
        </div>

        <!-- Quick Top Actions: Print & Export CSV -->
        <div class="flex items-center gap-3">
            <button type="button" @click="printReport()" :disabled="loading || reports.length === 0" class="h-11 px-5 bg-white dark:bg-themeNavy border-2 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200 hover:border-themeBlue hover:text-themeBlue dark:hover:text-themeBlue rounded-xl font-black text-xs uppercase tracking-[0.1em] shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Print Report</span>
            </button>

            <button type="button" @click="exportReport()" :disabled="loading || reports.length === 0" class="h-11 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white rounded-xl font-black text-xs uppercase tracking-[0.1em] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    <!-- Report Type Switcher Tabs (Screen Only) -->
    <div class="bg-white dark:bg-themeNavy rounded-2xl border border-gray-100 dark:border-white/[0.06] p-1.5 shadow-sm mb-6 flex flex-wrap gap-2 no-print">
        <button type="button" @click="setReportType('daily')" :class="form.report_type === 'daily' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-themeDark/45'" class="flex-1 min-w-[140px] py-2.5 px-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Daily Attendance</span>
        </button>

        <button type="button" @click="setReportType('monthly')" :class="form.report_type === 'monthly' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-themeDark/45'" class="flex-1 min-w-[140px] py-2.5 px-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Monthly Summary</span>
        </button>

        <button type="button" @click="setReportType('student_history')" :class="form.report_type === 'student_history' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-themeDark/45'" class="flex-1 min-w-[140px] py-2.5 px-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Student History</span>
        </button>

        <button type="button" @click="setReportType('absentee')" :class="form.report_type === 'absentee' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-themeDark/45'" class="flex-1 min-w-[140px] py-2.5 px-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Absentee Report</span>
        </button>
    </div>

    <!-- Filters Panel Card (Screen Only) -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm mb-6 no-print">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Branch Filter -->
            <div class="relative" @click.away="activeDropdown === 'branch' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Branch</label>
                <button type="button" @click="toggleDropdown('branch')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="branchText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('branch_id', '', 'All Branches')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-600 dark:text-gray-300 font-medium transition-colors">
                        All Branches
                    </button>
                    <template x-for="item in branches" :key="item.id">
                        <button type="button" @click="selectValue('branch_id', item.id, item.branch_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.branch_name"></span>
                            <template x-if="form.branch_id == item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Academic Session Filter -->
            <div class="relative" @click.away="activeDropdown === 'session' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Academic Session</label>
                <button type="button" @click="toggleDropdown('session')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="sessionText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('session_year_id', '', 'All Sessions')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-600 dark:text-gray-300 font-medium transition-colors">
                        All Sessions
                    </button>
                    <template x-for="item in sessions" :key="item.id">
                        <button type="button" @click="selectValue('session_year_id', item.id, item.session_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.session_name"></span>
                            <template x-if="form.session_year_id == item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Class Filter (Academic Order Enforced) -->
            <div class="relative" @click.away="activeDropdown === 'class' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class</label>
                <button type="button" @click="toggleDropdown('class')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="classText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('class_id', '', 'All Classes')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-600 dark:text-gray-300 font-medium transition-colors">
                        All Classes
                    </button>
                    <template x-for="item in classes" :key="item.id">
                        <button type="button" @click="selectValue('class_id', item.id, item.class_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.class_name"></span>
                            <template x-if="form.class_id == item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Section Filter (Dependent) -->
            <div class="relative" @click.away="activeDropdown === 'section' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Section</label>
                <button type="button" @click="toggleDropdown('section')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="sectionText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'section'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectValue('section_id', '', 'All Sections')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-600 dark:text-gray-300 font-medium transition-colors">
                        All Sections
                    </button>
                    <template x-for="item in sections" :key="item.id">
                        <button type="button" @click="selectValue('section_id', item.id, item.section_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="item.section_name"></span>
                            <template x-if="form.section_id == item.id">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Second Row: Date From, Date To, Student Search, Actions -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <!-- Date From (Rule 10 Custom Alpine DatePicker) -->
            <div>
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Date From</label>
                <div class="relative" x-data="datePicker(form.date_from)" x-effect="if (form.date_from !== undefined && form.date_from !== value) { value = form.date_from; parseInitial(value); generateCalendar(); }" @date-selected="form.date_from = $event.detail" @click.away="show = false; mOpen = false">
                    <button type="button" @click="show = !show" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                                        :class="isSelected(d.day) ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
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

            <!-- Date To (Rule 10 Custom Alpine DatePicker) -->
            <div>
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Date To</label>
                <div class="relative" x-data="datePicker(form.date_to)" x-effect="if (form.date_to !== undefined && form.date_to !== value) { value = form.date_to; parseInitial(value); generateCalendar(); }" @date-selected="form.date_to = $event.detail" @click.away="show = false; mOpen = false">
                    <button type="button" @click="show = !show" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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
                                        :class="isSelected(d.day) ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
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

            <!-- Student Search Field -->
            <div>
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Search Student</label>
                <div class="relative">
                    <input type="text" x-model="form.search" @keydown.enter.prevent="fetchReport()" placeholder="Name, ID or Roll..." class="w-full h-10 pl-9 pr-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Filter Buttons: Apply & Clear -->
            <div class="flex items-center gap-2">
                <button type="button" @click="fetchReport()" :disabled="loading" class="flex-1 h-10 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl text-xs uppercase tracking-widest shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span x-text="loading ? 'Filtering...' : 'Apply'"></span>
                </button>

                <button type="button" @click="resetFilters()" title="Reset Filters" class="h-10 px-3.5 bg-gray-100 dark:bg-themeDark border-2 border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-bold rounded-xl text-xs hover:bg-gray-200 dark:hover:bg-themeNavy transition-all flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        <!-- Quick Period Preset Buttons -->
        <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-white/[0.04]">
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mr-1">Quick Range:</span>
            <button type="button" @click="setPeriodPreset('today')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue transition-all">Today</button>
            <button type="button" @click="setPeriodPreset('yesterday')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue transition-all">Yesterday</button>
            <button type="button" @click="setPeriodPreset('this_week')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue transition-all">This Week</button>
            <button type="button" @click="setPeriodPreset('this_month')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue transition-all">This Month</button>
            <button type="button" @click="setPeriodPreset('last_month')" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue transition-all">Last Month</button>
        </div>
    </div>

    <!-- Summary Statistics Metric Cards (Screen Only) -->
    <div x-show="stats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6 no-print">
        <!-- Total Students -->
        <div class="bg-white dark:bg-themeNavy p-4 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex flex-col justify-between hover:-translate-y-0.5 transition-all">
            <span class="text-[10px] font-black text-gray-400 dark:text-gray-400 uppercase tracking-wider">Students</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-black text-gray-900 dark:text-white font-mono" x-text="stats.total_students ?? 0"></span>
                <span class="p-1.5 rounded-lg bg-themeBlue/10 text-themeBlue">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
        </div>

        <!-- Present Count -->
        <div class="bg-white dark:bg-themeNavy p-4 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex flex-col justify-between hover:-translate-y-0.5 transition-all">
            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Present</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono" x-text="stats.present_count ?? 0"></span>
                <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
        </div>

        <!-- Late Count -->
        <div class="bg-white dark:bg-themeNavy p-4 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex flex-col justify-between hover:-translate-y-0.5 transition-all">
            <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider">Late</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono" x-text="stats.late_count ?? 0"></span>
                <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
        </div>

        <!-- Absent Count -->
        <div class="bg-white dark:bg-themeNavy p-4 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex flex-col justify-between hover:-translate-y-0.5 transition-all">
            <span class="text-[10px] font-black text-rose-500 uppercase tracking-wider">Absent</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-black text-rose-500 font-mono" x-text="stats.absent_count ?? 0"></span>
                <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
        </div>

        <!-- Attendance Rate % -->
        <div class="bg-white dark:bg-themeNavy p-4 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex flex-col justify-between hover:-translate-y-0.5 transition-all">
            <span class="text-[10px] font-black text-themeBlue uppercase tracking-wider">Attendance Rate</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-black text-themeBlue font-mono" x-text="(stats.attendance_percentage ?? 0) + '%'"></span>
                <span class="p-1.5 rounded-lg bg-themeBlue/10 text-themeBlue">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
        </div>

        <!-- Total Records -->
        <div class="bg-white dark:bg-themeNavy p-4 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm flex flex-col justify-between hover:-translate-y-0.5 transition-all">
            <span class="text-[10px] font-black text-gray-400 dark:text-gray-400 uppercase tracking-wider">Total Records</span>
            <div class="flex items-center justify-between mt-2">
                <span class="text-2xl font-black text-gray-800 dark:text-gray-200 font-mono" x-text="stats.total_records ?? 0"></span>
                <span class="p-1.5 rounded-lg bg-gray-100 dark:bg-themeDark text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
        </div>
    </div>

    <!-- MAIN REPORT & PRINTABLE AREA -->
    <div id="printableReportArea" class="w-full">

        <!-- OFFICIAL PRINT-ONLY HEADER -->
        <div class="print-only mb-3">
            <table style="width: 100%; border-collapse: collapse; border: none !important; margin-bottom: 4px;">
                <tr style="border: none !important;">
                    <!-- Left: MACS Logo -->
                    <td style="width: 14%; text-align: left; vertical-align: middle; border: none !important; padding: 0 !important;">
                        @if(!empty($macsLogoSrc))
                            <img src="{{ $macsLogoSrc }}" style="width: 52px; height: 52px; object-fit: contain; border-radius: 50%; border: 1.5px solid #008ED6; padding: 2px;" alt="MACS Logo">
                        @else
                            <div style="width: 50px; height: 50px; border: 1.5px solid #008ED6; border-radius: 50%; text-align: center; line-height: 50px; font-weight: 900; color: #008ED6; font-size: 11px;">MACS</div>
                        @endif
                    </td>

                    <!-- Center: School Details & Report Badge -->
                    <td style="width: 72%; text-align: center; vertical-align: middle; border: none !important; padding: 0 !important;">
                        <div style="font-size: 19px; font-weight: 900; color: #0F1E2C; letter-spacing: -0.3px; text-transform: uppercase; line-height: 1.1;">
                            MACS School &amp; College
                        </div>
                        <div style="font-size: 8.5px; font-weight: 600; color: #475569; margin-top: 1px;">
                            Jalalpur, Pabna Sadar, Pabna &bull; Bangladesh
                        </div>
                        <div style="font-size: 8px; font-weight: 500; color: #64748B; margin-top: 0.5px;">
                            Hotline: 01896-220299, 01896-220300 &bull; Web: macs.edu.bd
                        </div>
                        <div style="margin-top: 4px; margin-bottom: 2px;">
                            <span style="display: inline-block; background-color: #008ED6; color: #ffffff; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; padding: 4px 16px; border-radius: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact;" x-text="getReportTitleText()">
                                ATTENDANCE REPORT
                            </span>
                        </div>
                    </td>

                    <!-- Right: Print Metadata -->
                    <td style="width: 14%; text-align: right; vertical-align: middle; border: none !important; padding: 0 !important;">
                        <div style="font-size: 7.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.4px;">Generated On</div>
                        <div style="font-size: 9.5px; font-weight: 800; font-family: monospace; color: #0F1E2C; margin-top: 1px;">{{ date('d M, Y') }}</div>
                        <div style="font-size: 7.5px; font-weight: 600; color: #94A3B8;">{{ date('h:i A') }}</div>
                    </td>
                </tr>
            </table>

            <!-- Filter Metadata Banner -->
            <div style="background-color: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 4px; padding: 3px 6px; margin-bottom: 6px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                <table style="width: 100%; border: none !important; border-collapse: collapse; margin: 0 !important; padding: 0 !important; background: transparent !important;">
                    <tr style="border: none !important; background: transparent !important;">
                        <td style="border: none !important; padding: 1px 2px !important; font-size: 9.5px; color: #334155; font-weight: 700;">
                            Branch: <strong style="color: #008ED6;" x-text="branchText"></strong>
                            &nbsp;&bull;&nbsp; Class: <strong style="color: #008ED6;" x-text="classText"></strong>
                            <template x-if="shouldShowSectionInHeader">
                                <span>&nbsp;&bull;&nbsp; Section: <strong style="color: #008ED6;" x-text="headerSectionText"></strong></span>
                            </template>
                            &nbsp;&bull;&nbsp; Period: <strong style="color: #0F1E2C;" x-text="formatDate(form.date_from) + ' - ' + formatDate(form.date_to)"></strong>
                        </td>
                        <td style="border: none !important; padding: 1px 2px !important; font-size: 9.5px; color: #334155; font-weight: 700; text-align: right;">
                            Students: <strong style="color: #0F1E2C;" x-text="stats.total_students ?? 0"></strong>
                            &nbsp;&bull;&nbsp; Present: <strong style="color: #009A49;" x-text="stats.present_count ?? 0"></strong>
                            &nbsp;&bull;&nbsp; Late: <strong style="color: #D97706;" x-text="stats.late_count ?? 0"></strong>
                            &nbsp;&bull;&nbsp; Absent: <strong style="color: #DC2626;" x-text="stats.absent_count ?? 0"></strong>
                            &nbsp;&bull;&nbsp; Rate: <strong style="color: #008ED6;" x-text="(stats.attendance_percentage ?? 0) + '%'"></strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Student Profile Banner (When in Student History Mode & Student Selected) -->
        <template x-if="form.report_type === 'student_history' && selectedStudent">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-themeNavy dark:to-themeDark p-5 rounded-3xl border border-blue-100 dark:border-white/[0.08] shadow-sm mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-themeBlue to-themeGreen text-white font-mono font-black text-xl flex items-center justify-center shadow-md">
                        <span x-text="selectedStudent.roll_number || '#'"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black text-gray-900 dark:text-white" x-text="selectedStudent.student_name"></h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black bg-themeBlue/10 text-themeBlue border border-themeBlue/20" x-text="selectedStudent.student_identity"></span>
                        </div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
                            Class: <strong class="text-gray-800 dark:text-gray-200" x-text="selectedStudent.class_name"></strong> &bull;
                            Section: <strong class="text-gray-800 dark:text-gray-200" x-text="selectedStudent.section_name"></strong> &bull;
                            Guardian: <strong class="text-gray-800 dark:text-gray-200" x-text="selectedStudent.guardian_mobile"></strong>
                        </p>
                    </div>
                </div>

                <!-- Student Mini Stats & Back Button -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-white dark:bg-themeDark px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div class="text-center px-2">
                            <div class="text-[9px] font-black text-gray-400 uppercase">Present</div>
                            <div class="text-xs font-black text-emerald-600" x-text="studentStats ? studentStats.present_days : 0"></div>
                        </div>
                        <div class="text-center px-2 border-l border-gray-200 dark:border-gray-700">
                            <div class="text-[9px] font-black text-gray-400 uppercase">Late</div>
                            <div class="text-xs font-black text-amber-500" x-text="studentStats ? studentStats.late_days : 0"></div>
                        </div>
                        <div class="text-center px-2 border-l border-gray-200 dark:border-gray-700">
                            <div class="text-[9px] font-black text-gray-400 uppercase">Absent</div>
                            <div class="text-xs font-black text-rose-500" x-text="studentStats ? studentStats.absent_days : 0"></div>
                        </div>
                        <div class="text-center px-2 border-l border-gray-200 dark:border-gray-700">
                            <div class="text-[9px] font-black text-gray-400 uppercase">Rate</div>
                            <div class="text-xs font-black text-themeBlue" x-text="(studentStats ? studentStats.attendance_percentage : 0) + '%'"></div>
                        </div>
                    </div>

                    <button type="button" @click="clearSelectedStudent()" class="h-9 px-3.5 bg-white dark:bg-themeDark border border-gray-200 dark:border-gray-700 hover:border-gray-400 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 no-print">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Change Student</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Candidate Students Picker (Student History Mode when no student selected) -->
        <template x-if="form.report_type === 'student_history' && !selectedStudent">
            <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm mb-6 no-print">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Select a Student</h3>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Choose a student to view their complete day-by-day attendance history.</p>
                    </div>
                    <span class="text-xs font-bold text-gray-400 font-mono" x-text="candidateStudents.length + ' Students Available'"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-96 overflow-y-auto p-1">
                    <template x-for="c in candidateStudents" :key="c.id">
                        <button type="button" @click="selectStudent(c.id)" class="p-3 rounded-2xl border-2 border-gray-100 dark:border-gray-800 hover:border-themeBlue dark:hover:border-themeBlue bg-gray-50/50 dark:bg-themeDark/40 hover:bg-white dark:hover:bg-themeDark transition-all text-left flex items-center gap-3 group">
                            <div class="w-10 h-10 rounded-xl bg-themeBlue/10 text-themeBlue font-mono font-black text-sm flex items-center justify-center shrink-0 group-hover:bg-themeBlue group-hover:text-white transition-colors">
                                <span x-text="c.roll_number || '•'"></span>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate group-hover:text-themeBlue transition-colors" x-text="c.student_name"></div>
                                <div class="text-[10px] font-mono text-gray-400 truncate" x-text="c.student_identity"></div>
                                <div class="text-[10px] font-medium text-gray-500 dark:text-gray-400 truncate" x-text="c.class_name + ' - ' + c.section_name"></div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </template>        <!-- Main Report Data Table Card -->
        <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-hidden mb-8 print:!border-none print:!shadow-none print:!rounded-none print:!overflow-visible print:!bg-transparent print:!mb-0 print:!mt-0 print:!p-0">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0 overflow-x-auto print:!overflow-visible">
                <table class="w-full text-left border-collapse table report-mode-daily" :class="{'report-mode-daily': form.report_type === 'daily', 'has-no-class-filter': !form.class_id, 'has-class-filter': !!form.class_id}">
                    
                    <!-- Table Header: DAILY REPORT -->
                    <template x-if="form.report_type === 'daily'">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="col-daily-sl !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-10">#</th>
                                <th class="col-daily-roll !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-14">Roll</th>
                                <th class="col-daily-id !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-28 whitespace-nowrap">Student ID</th>
                                <th class="col-daily-name !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] min-w-[170px]">Student Name</th>
                                <th class="col-daily-class !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-24">Class</th>
                                <th class="col-daily-section !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-16 text-center">Section</th>
                                <th class="col-daily-date !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-28 text-center whitespace-nowrap">Date</th>
                                <th class="col-daily-in !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-24 whitespace-nowrap">IN Time</th>
                                <th class="col-daily-out !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-24 whitespace-nowrap">OUT Time</th>
                                <th class="col-daily-status !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-24 whitespace-nowrap">Status</th>
                                <th class="col-daily-remarks !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] min-w-[140px] no-print">Remarks</th>
                                <th class="col-daily-action !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-14 no-print">Action</th>
                            </tr>
                        </thead>
                    </template>

                    <!-- Table Header: MONTHLY SUMMARY -->
                    <template x-if="form.report_type === 'monthly'">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-12">#</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-16">Roll</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-32 whitespace-nowrap">Student ID</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student Name</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-28">Class</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-20 text-center">Section</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-24">Present</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">Late</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">Absent</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-24">Recorded</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-24">Rate %</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-16 no-print">Action</th>
                            </tr>
                        </thead>
                    </template>

                    <!-- Table Header: STUDENT HISTORY -->
                    <template x-if="form.report_type === 'student_history'">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-12">#</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-32 text-center whitespace-nowrap">Date</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-28 whitespace-nowrap">Status</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-28 whitespace-nowrap">IN Time</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-28 whitespace-nowrap">OUT Time</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Remarks</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-40">Recorded By</th>
                            </tr>
                        </thead>
                    </template>

                    <!-- Table Header: ABSENTEE REPORT -->
                    <template x-if="form.report_type === 'absentee'">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-12">#</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-16">Roll</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-32 whitespace-nowrap">Student ID</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student Name</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-28">Class</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-20 text-center">Section</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-32 text-center whitespace-nowrap">Absent Date</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-36 whitespace-nowrap">Guardian Mobile</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Remarks</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-16 no-print">Action</th>
                            </tr>
                        </thead>
                    </template>

                    <!-- Table Body -->
                    <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">

                        <!-- Skeleton Loading State -->
                        <template x-if="loading">
                            <template x-for="i in [1, 2, 3, 4, 5]" :key="i">
                                <tr class="animate-pulse">
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0"><div class="h-4 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                    <td class="py-0 px-0"><div class="h-4 w-36 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                    <td class="py-0 px-0"><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-10 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0 text-center"><div class="h-5 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-full mx-auto"></div></td>
                                    <td class="py-0 px-0"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                    <td class="py-0 px-0 text-right"><div class="h-6 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-lg ml-auto"></div></td>
                                </tr>
                            </template>
                        </template>

                        <!-- DATA ROWS: DAILY REPORT -->
                        <template x-if="!loading && form.report_type === 'daily'">
                            <template x-for="item in reports" :key="item.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="col-daily-sl py-0 px-0 text-center font-mono font-black text-gray-400 dark:text-gray-400 text-xs" x-text="item.sl"></td>
                                    <td class="col-daily-roll py-0 px-0 text-center font-mono font-black text-themeGreen dark:text-green-500 text-sm" x-text="item.roll_number"></td>
                                    <td class="col-daily-id py-0 px-0 font-mono text-xs font-bold text-themeBlue dark:text-themeBlue whitespace-nowrap" x-text="item.student_identity"></td>
                                    <td class="col-daily-name py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100" x-text="item.student_name"></td>
                                    <td class="col-daily-class py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="item.class_name || '-'"></td>
                                    <td class="col-daily-section py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400 text-center whitespace-nowrap" x-text="formatDisplaySection(item.section_name)"></td>
                                    <td class="col-daily-date py-0 px-0 text-center font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="item.attendance_date_formatted"></td>
                                    <td class="col-daily-in py-0 px-0 text-center font-mono text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="item.in_time"></td>
                                    <td class="col-daily-out py-0 px-0 text-center font-mono text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="item.out_time"></td>
                                    <td class="col-daily-status py-0 px-0 text-center whitespace-nowrap">
                                        <span :class="getStatusBadgeClass(item.status)" x-text="item.status"></span>
                                    </td>
                                    <td class="col-daily-remarks py-0 px-0 text-xs font-medium text-gray-500 dark:text-gray-400 no-print" x-text="item.remarks"></td>
                                    <td class="col-daily-action py-0 px-0 text-right no-print">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" @click="viewStudentHistory(item.student_id)" title="View Student History" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>

                        <!-- DATA ROWS: MONTHLY SUMMARY -->
                        <template x-if="!loading && form.report_type === 'monthly'">
                            <template x-for="item in reports" :key="item.student_id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0 text-center font-mono font-black text-gray-400 dark:text-gray-400 text-xs" x-text="item.sl"></td>
                                    <td class="py-0 px-0 text-center font-mono font-black text-themeGreen dark:text-green-500 text-sm" x-text="item.roll_number"></td>
                                    <td class="py-0 px-0 font-mono text-xs font-bold text-themeBlue dark:text-themeBlue whitespace-nowrap" x-text="item.student_identity"></td>
                                    <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100" x-text="item.student_name"></td>
                                    <td class="py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="item.class_name || '-'"></td>
                                    <td class="py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400 text-center whitespace-nowrap" x-text="formatDisplaySection(item.section_name)"></td>
                                    <td class="py-0 px-0 text-center font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400" x-text="item.present_days"></td>
                                    <td class="py-0 px-0 text-center font-mono text-sm font-bold text-amber-500" x-text="item.late_days"></td>
                                    <td class="py-0 px-0 text-center font-mono text-sm font-bold text-rose-500" x-text="item.absent_days"></td>
                                    <td class="py-0 px-0 text-center font-mono text-sm font-black text-gray-700 dark:text-gray-200" x-text="item.total_recorded"></td>
                                    <td class="py-0 px-0 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="font-mono text-xs font-black" :class="item.attendance_percentage >= 80 ? 'text-emerald-600' : (item.attendance_percentage >= 60 ? 'text-amber-500' : 'text-rose-500')" x-text="item.attendance_percentage + '%'"></span>
                                        </div>
                                    </td>
                                    <td class="py-0 px-0 text-right no-print">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" @click="viewStudentHistory(item.student_id)" title="View Student History" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>

                        <!-- DATA ROWS: STUDENT HISTORY -->
                        <template x-if="!loading && form.report_type === 'student_history'">
                            <template x-for="item in reports" :key="item.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0 text-center font-mono font-black text-gray-400 dark:text-gray-400 text-xs" x-text="item.sl"></td>
                                    <td class="py-0 px-0 text-center font-mono text-xs font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap" x-text="item.attendance_date_formatted"></td>
                                    <td class="py-0 px-0 text-center whitespace-nowrap">
                                        <span :class="getStatusBadgeClass(item.status)" x-text="item.status"></span>
                                    </td>
                                    <td class="py-0 px-0 text-center font-mono text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="item.in_time"></td>
                                    <td class="py-0 px-0 text-center font-mono text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="item.out_time"></td>
                                    <td class="py-0 px-0 text-xs font-medium text-gray-500 dark:text-gray-400" x-text="item.remarks"></td>
                                    <td class="py-0 px-0 text-xs font-bold text-gray-500 dark:text-gray-400" x-text="item.teacher_name"></td>
                                </tr>
                            </template>
                        </template>

                        <!-- DATA ROWS: ABSENTEE REPORT -->
                        <template x-if="!loading && form.report_type === 'absentee'">
                            <template x-for="item in reports" :key="item.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0 text-center font-mono font-black text-gray-400 dark:text-gray-400 text-xs" x-text="item.sl"></td>
                                    <td class="py-0 px-0 text-center font-mono font-black text-rose-500 text-sm" x-text="item.roll_number"></td>
                                    <td class="py-0 px-0 font-mono text-xs font-bold text-themeBlue dark:text-themeBlue whitespace-nowrap" x-text="item.student_identity"></td>
                                    <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100" x-text="item.student_name"></td>
                                    <td class="py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="item.class_name || '-'"></td>
                                    <td class="py-0 px-0 text-xs font-semibold text-gray-600 dark:text-gray-400 text-center whitespace-nowrap" x-text="formatDisplaySection(item.section_name)"></td>
                                    <td class="py-0 px-0 text-center font-mono text-xs font-bold text-rose-600 whitespace-nowrap" x-text="item.attendance_date_formatted"></td>
                                    <td class="py-0 px-0 font-mono text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        <a :href="'tel:' + item.guardian_mobile" class="hover:underline text-themeBlue flex items-center gap-1.5" x-text="item.guardian_mobile"></a>
                                    </td>
                                    <td class="py-0 px-0 text-xs font-medium text-gray-500 dark:text-gray-400" x-text="item.remarks"></td>
                                    <td class="py-0 px-0 text-right no-print">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" @click="viewStudentHistory(item.student_id)" title="View Student History" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!loading && reports.length === 0">
                            <tr>
                                <td :colspan="form.report_type === 'student_history' ? 7 : (form.report_type === 'absentee' ? 10 : 12)" class="py-16 text-center text-gray-400 dark:text-gray-555">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="font-bold text-sm text-gray-500 uppercase tracking-widest" x-text="fetched ? 'No attendance records found for these filters.' : 'Adjust filters and click Apply to generate report.'"></p>
                                    </div>
                                </td>
                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>
        </div>

        <!-- OFFICIAL PRINT SIGNATURES BLOCK (Print Only) -->
        <div class="print-only print-signatures">
            <table style="width: 100%; border-collapse: collapse; border: none !important; margin-bottom: 0 !important;">
                <tr style="border: none !important;">
                    <td style="width: 33.33%; text-align: center; vertical-align: bottom; border: none !important; padding: 0 !important;">
                        <span style="display: inline-block; width: 145px; border-top: 1.4px dashed #64748B; padding-top: 5px; font-size: 10.5px; font-weight: 800; color: #0F1E2C; letter-spacing: 0.2px;">
                            Prepared By
                        </span>
                    </td>
                    <td style="width: 33.33%; text-align: center; vertical-align: bottom; border: none !important; padding: 0 !important;">
                        <span style="display: inline-block; width: 145px; border-top: 1.4px dashed #64748B; padding-top: 5px; font-size: 10.5px; font-weight: 800; color: #0F1E2C; letter-spacing: 0.2px;">
                            Class Teacher
                        </span>
                    </td>
                    <td style="width: 33.33%; text-align: center; vertical-align: bottom; border: none !important; padding: 0 !important;">
                        <span style="display: inline-block; width: 165px; border-top: 1.4px dashed #64748B; padding-top: 5px; font-size: 10.5px; font-weight: 800; color: #0F1E2C; letter-spacing: 0.2px;">
                            Principal / Head of School
                        </span>
                    </td>
                </tr>
            </table>
        </div>

    </div><!-- End #printableReportArea -->

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
                    const y = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10) - 1;
                    if (!isNaN(y) && !isNaN(m)) {
                        this.currentYear = y;
                        this.currentMonth = m;
                    }
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
                this.mOpen = false;
                this.$dispatch('date-selected', this.value);
            },

            clearDate() {
                this.value = '';
                this.show = false;
                this.mOpen = false;
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
                this.mOpen = false;
                this.$dispatch('date-selected', this.value);
            },

            isSelected(day) {
                if (!day || !this.value) return false;
                const parts = this.value.split('-');
                if (parts.length !== 3) return false;
                return parseInt(parts[0], 10) === this.currentYear &&
                       parseInt(parts[1], 10) - 1 === this.currentMonth &&
                       parseInt(parts[2], 10) === day;
            },

            formatDisplay(val) {
                if (!val) return 'Select Date';
                const parts = val.split('-');
                if (parts.length === 3) {
                    const year = parts[0];
                    const monthIdx = parseInt(parts[1], 10) - 1;
                    const day = parseInt(parts[2], 10);
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    if (months[monthIdx]) {
                        return `${day} ${months[monthIdx]}, ${year}`;
                    }
                }
                return val;
            }
        };
    }

    function attendanceReportApp() {
        return {
            // Dropdown options preloaded from controller
            branches: @json($branches ?? []),
            sessions: @json($sessions ?? []),
            classes: @json($classes ?? []),
            sections: @json($sections ?? []),

            // Form state
            form: {
                report_type: 'daily',
                branch_id: '',
                session_year_id: '',
                class_id: '',
                section_id: '',
                date_from: '{{ date("Y-m-d") }}',
                date_to: '{{ date("Y-m-d") }}',
                student_id: '',
                search: '',
            },

            // Active dropdown toggling
            activeDropdown: null,

            // Display text for dropdowns
            branchText: 'All Branches',
            sessionText: 'All Sessions',
            classText: 'All Classes',
            sectionText: 'All Sections',

            // Data storage
            reports: [],
            stats: {
                total_students: 0,
                total_records: 0,
                present_count: 0,
                late_count: 0,
                absent_count: 0,
                total_attended: 0,
                attendance_percentage: 0
            },
            selectedStudent: null,
            studentStats: null,
            candidateStudents: [],

            // Loading state
            loading: false,
            fetched: false,

            init() {
                // Initialize default session if available
                if (this.sessions && this.sessions.length > 0) {
                    this.form.session_year_id = this.sessions[0].id;
                    this.sessionText = this.sessions[0].session_name;
                }
                // Automatically fetch daily report for today
                this.fetchReport();
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

                const targetVar = mapping[field];
                if (targetVar) {
                    this[targetVar] = text;
                }

                // If class changes, reset section selection
                if (field === 'class_id') {
                    this.form.section_id = '';
                    this.sectionText = 'All Sections';
                }

                this.activeDropdown = null;
            },

            setReportType(type) {
                this.form.report_type = type;
                this.form.student_id = '';
                this.selectedStudent = null;
                this.studentStats = null;

                // Adjust default dates based on report type
                const today = new Date().toISOString().split('T')[0];
                if (type === 'monthly') {
                    // Set to first day of current month to today
                    const d = new Date();
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    this.form.date_from = `${year}-${month}-01`;
                    this.form.date_to = today;
                } else if (type === 'daily' || type === 'absentee') {
                    this.form.date_from = today;
                    this.form.date_to = today;
                } else if (type === 'student_history') {
                    const d = new Date();
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    this.form.date_from = `${year}-${month}-01`;
                    this.form.date_to = today;
                }

                this.fetchReport();
            },

            setPeriodPreset(preset) {
                const today = new Date();
                const formatDateStr = (d) => d.toISOString().split('T')[0];

                if (preset === 'today') {
                    this.form.date_from = formatDateStr(today);
                    this.form.date_to = formatDateStr(today);
                } else if (preset === 'yesterday') {
                    const y = new Date();
                    y.setDate(y.getDate() - 1);
                    this.form.date_from = formatDateStr(y);
                    this.form.date_to = formatDateStr(y);
                } else if (preset === 'this_week') {
                    const d = new Date();
                    const day = d.getDay();
                    const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Monday
                    const monday = new Date(d.setDate(diff));
                    this.form.date_from = formatDateStr(monday);
                    this.form.date_to = formatDateStr(new Date());
                } else if (preset === 'this_month') {
                    const d = new Date();
                    const firstDay = new Date(d.getFullYear(), d.getMonth(), 1);
                    this.form.date_from = formatDateStr(firstDay);
                    this.form.date_to = formatDateStr(today);
                } else if (preset === 'last_month') {
                    const d = new Date();
                    const firstDay = new Date(d.getFullYear(), d.getMonth() - 1, 1);
                    const lastDay = new Date(d.getFullYear(), d.getMonth(), 0);
                    this.form.date_from = formatDateStr(firstDay);
                    this.form.date_to = formatDateStr(lastDay);
                }

                this.fetchReport();
            },

            resetFilters() {
                this.form.branch_id = '';
                this.branchText = 'All Branches';
                this.form.class_id = '';
                this.classText = 'All Classes';
                this.form.section_id = '';
                this.sectionText = 'All Sections';
                this.form.search = '';
                this.form.student_id = '';
                this.selectedStudent = null;
                this.studentStats = null;

                const today = new Date().toISOString().split('T')[0];
                this.form.date_from = today;
                this.form.date_to = today;

                this.fetchReport();
            },

            async fetchReport() {
                this.loading = true;
                this.fetched = false;
                this.reports = [];

                let query = new URLSearchParams({
                    report_type: this.form.report_type,
                    branch_id: this.form.branch_id,
                    session_year_id: this.form.session_year_id,
                    class_id: this.form.class_id,
                    section_id: this.form.section_id,
                    date_from: this.form.date_from,
                    date_to: this.form.date_to,
                    student_id: this.form.student_id,
                    search: this.form.search
                }).toString();

                try {
                    const res = await axios.get(`/ajax/attendance/report-data?${query}`, getAuthHeaders());
                    if (res.data.status === 'success') {
                        this.reports = res.data.data || [];
                        this.stats = res.data.stats || {};
                        this.selectedStudent = res.data.student || null;
                        this.studentStats = res.data.student_stats || null;
                        this.candidateStudents = res.data.candidate_students || [];
                        this.fetched = true;
                    } else {
                        showAlert(res.data.message || "Failed to load attendance report.", "Error");
                    }
                } catch (err) {
                    console.error("Attendance Report Fetch Error:", err);
                    showAlert("Failed to load attendance report records.", "Error");
                } finally {
                    this.loading = false;
                }
            },

            viewStudentHistory(studentId) {
                this.form.report_type = 'student_history';
                this.form.student_id = studentId;
                this.fetchReport();
            },

            selectStudent(studentId) {
                this.form.student_id = studentId;
                this.fetchReport();
            },

            clearSelectedStudent() {
                this.form.student_id = '';
                this.selectedStudent = null;
                this.studentStats = null;
                this.fetchReport();
            },

            printReport() {
                window.print();
            },

            exportReport() {
                let query = new URLSearchParams({
                    report_type: this.form.report_type,
                    branch_id: this.form.branch_id,
                    session_year_id: this.form.session_year_id,
                    class_id: this.form.class_id,
                    section_id: this.form.section_id,
                    date_from: this.form.date_from,
                    date_to: this.form.date_to,
                    student_id: this.form.student_id,
                    search: this.form.search
                }).toString();

                window.location.href = `/attendance/report/export?${query}`;
            },

            getStatusBadgeClass(status) {
                if (status === 'Present') {
                    return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border border-emerald-500/20 shadow-sm';
                } else if (status === 'Late') {
                    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border border-amber-500/20 shadow-sm';
                } else {
                    return 'bg-rose-500/10 text-rose-600 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border border-rose-500/20 shadow-sm';
                }
            },

            getReportTitleText() {
                const map = {
                    daily: 'DAILY ATTENDANCE REPORT',
                    monthly: 'MONTHLY ATTENDANCE SUMMARY',
                    student_history: 'STUDENT ATTENDANCE HISTORY',
                    absentee: 'CONFIRMED ABSENTEE REPORT'
                };
                return map[this.form.report_type] || 'ATTENDANCE REPORT';
            },

            formatDate(val) {
                if (!val) return 'N/A';
                try {
                    const d = new Date(val);
                    return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
                } catch(e) {
                    return val;
                }
            },

            formatShortSection(sec) {
                if (!sec || sec === 'N/A' || sec === 'None' || sec === '-' || sec === 'null') return '';
                return String(sec).replace(/^section\s*[:-]?\s*/i, '').trim();
            },

            formatDisplaySection(sec) {
                const s = this.formatShortSection(sec);
                return s ? s : '-';
            },

            formatClassSection(item) {
                if (!item) return '';
                const cls = item.class_name || '';
                const sec = this.formatShortSection(item.section_name);
                return sec ? `${cls} - ${sec}` : cls;
            },

            get shouldShowSectionInHeader() {
                if (!this.form.class_id) return true;
                if (this.form.section_id) return true;
                if (this.reports && this.reports.length > 0) {
                    return this.reports.some(r => {
                        const s = this.formatShortSection(r.section_name);
                        return s !== '';
                    });
                }
                return Boolean(this.sections && this.sections.length > 0);
            },

            get headerSectionText() {
                if (!this.form.section_id) return 'All Sections';
                return this.formatShortSection(this.sectionText) || this.sectionText;
            }
        };
    }
</script>
@endpush