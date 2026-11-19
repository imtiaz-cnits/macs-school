@extends('tyro-dashboard::layouts.admin')

@section('title', 'Financial Reports')

@push('styles')
<style>
    /* Tab animation */
    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.25s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .print-only {
        display: none !important;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 4mm 5mm 4mm 5mm;
        }

        html,
        body {
            background: #ffffff !important;
            color: #0F1E2C !important;
            font-family: 'Figtree', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
        }

        body * {
            visibility: hidden;
        }

        #printableReportArea,
        #printableReportArea * {
            visibility: visible;
        }

        #printableReportArea {
            position: relative !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            min-height: calc(297mm - 12mm) !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            box-sizing: border-box !important;
            padding: 0 !important;
            margin: 0 !important;
            background: #ffffff !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Paginated Print Container Rules */
        #printableReportArea.has-paginated-print {
            display: block !important;
            min-height: auto !important;
            height: auto !important;
        }

        #printableReportArea.has-paginated-print > .print-top-section,
        #printableReportArea.has-paginated-print > #printSigSpacer,
        #printableReportArea.has-paginated-print > .print-signatures {
            display: none !important;
        }

        #printableReportArea.has-paginated-print #printPagesContainer {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .print-page {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }

        .print-page-final,
        .print-page:last-child {
            min-height: calc(297mm - 16mm) !important;
            justify-content: space-between !important;
            page-break-after: avoid !important;
            break-after: avoid !important;
        }

        .print-page-content {
            flex: 0 0 auto !important;
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .print-page-footer {
            flex: 0 0 auto !important;
            width: 100% !important;
            margin-top: auto !important;
            padding-top: 16px !important;
            padding-bottom: 0 !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .no-print,
        nav,
        aside,
        header,
        footer,
        .sidebar,
        .sidebar-container,
        .topbar,
        .admin-bar,
        .sidebar-overlay,
        #globalModal {
            display: none !important;
        }

        .dashboard-layout,
        .main-content,
        .page-content {
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

        .tab-content {
            display: none !important;
        }

        .tab-content.active {
            display: block !important;
        }

        .print-top-section {
            flex: 1 0 auto !important;
            width: 100% !important;
        }

        /* Deep Table Border */
        #printableReportArea table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border: 1px solid #475569 !important;
            border-radius: 0 !important;
        }

        #printableReportArea .table-container {
            overflow: visible !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #printableReportArea .table-card-wrapper {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        /* Point 4: school er branding just 1st page e hobe porer page gulate table er header diye shuru hobe just */
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
            font-size: 10px !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            padding: 5px 6px !important;
            border: 1px solid #0072ad !important;
            border-bottom: 1.5px solid #005a8c !important;
            border-radius: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printableReportArea td {
            border: 1px solid #475569 !important;
            padding: 3px 4px !important;
            font-size: 8.5px !important;
            line-height: 1.2 !important;
            color: #0F1E2C !important;
            vertical-align: top !important;
        }

        #printableReportArea th:first-child,
        #printableReportArea td:first-child {
            width: 24px !important;
            min-width: 24px !important;
            max-width: 28px !important;
            padding: 3px 2px !important;
            text-align: center !important;
        }

        #printableReportArea th.col-receipt,
        #printableReportArea td.col-receipt {
            width: 122px !important;
            min-width: 118px !important;
            max-width: 125px !important;
            white-space: nowrap !important;
        }

        #printableReportArea th.col-student,
        #printableReportArea td.col-student {
            width: 175px !important;
            min-width: 165px !important;
            max-width: 185px !important;
        }

        #printableReportArea th.col-fee-details,
        #printableReportArea td.col-fee-details {
            width: auto !important;
            min-width: 250px !important;
        }

        #printableReportArea th.col-method,
        #printableReportArea td.col-method {
            width: 58px !important;
            min-width: 55px !important;
            max-width: 65px !important;
            text-align: center !important;
        }

        #printableReportArea th.col-amount,
        #printableReportArea td.col-amount {
            width: 88px !important;
            min-width: 85px !important;
            max-width: 92px !important;
            font-size: 11px !important;
            font-weight: 900 !important;
            font-family: monospace !important;
            white-space: nowrap !important;
            text-align: right !important;
        }

        #printableReportArea th.col-invoices,
        #printableReportArea td.col-invoices {
            width: 65px !important;
            min-width: 60px !important;
            max-width: 70px !important;
            text-align: center !important;
        }

        #printableReportArea th.col-due-amount,
        #printableReportArea td.col-due-amount {
            width: 95px !important;
            min-width: 90px !important;
            max-width: 100px !important;
            font-size: 11px !important;
            font-weight: 900 !important;
            font-family: monospace !important;
            white-space: nowrap !important;
            text-align: right !important;
        }

        #printableReportArea td span {
            padding: 0.5px 3px !important;
            font-size: 8px !important;
        }

        #printableReportArea td.col-method span {
            font-size: 10.5px !important;
            margin-top: 4px !important;
        }

        #printableReportArea tfoot td.col-amount {
            font-size: 11.5px !important;
            font-weight: 900 !important;
            font-family: monospace !important;
            white-space: nowrap !important;
            text-align: right !important;
        }

        #printableReportArea .student-meta,
        #printableReportArea .student-meta span {
            font-size: 10.5px !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
            padding: 0 !important;
        }

        #printableReportArea td.col-fee-details {
            vertical-align: top !important;
            padding: 3px 4px !important;
        }

        #printableReportArea .col-fee-details-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 1.5px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #printableReportArea .col-fee-details-list > * {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }

        #printableReportArea .fee-detail-line {
            font-size: 10.5px !important;
            line-height: 1.25 !important;
            color: #0F1E2C !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #printableReportArea .fee-detail-line span {
            font-size: 10.5px !important;
            line-height: 1.25 !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        #printableReportArea .fee-cat-name {
            font-weight: 800 !important;
            color: #0F1E2C !important;
        }

        #printableReportArea .fee-sep {
            font-weight: 700 !important;
            color: #475569 !important;
            margin: 0 2.5px !important;
        }

        #printableReportArea .fee-months {
            font-weight: 600 !important;
            color: #0F1E2C !important;
        }

        #printableReportArea tbody tr:nth-child(even) td {
            background-color: #F8FAFC !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printableReportArea tbody tr:nth-child(odd) td {
            background-color: #FFFFFF !important;
        }

        #printableReportArea tfoot {
            display: table-row-group !important;
            page-break-inside: avoid !important;
        }

        #printableReportArea tfoot tr {
            background-color: #F1F5F9 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            font-weight: 800 !important;
        }

        #printableReportArea tfoot td {
            padding: 5px 6px !important;
            font-size: 8.5px !important;
            border: 1px solid #475569 !important;
        }

        /* Point 2: signature area ta pager ekdom niche hobe, page er height always full hobe */
        #printSigSpacer {
            display: block !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .print-signatures {
            display: block !important;
            width: 100% !important;
            margin-top: auto !important;
            padding-top: 4px !important;
            padding-bottom: 0 !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: avoid !important;
            break-after: avoid !important;
        }

        #printableReportArea .print-signatures table,
        #printableReportArea .print-signatures tr,
        #printableReportArea .print-signatures td {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
    }

    /* Measurement state for print pagination calculation */
    #printableReportArea.measuring-print {
        position: absolute !important;
        left: -9999px !important;
        top: 0 !important;
        width: 200mm !important;
        max-width: 200mm !important;
        visibility: hidden !important;
        pointer-events: none !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
        font-family: 'Figtree', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    }
    #printableReportArea.measuring-print .print-only { display: block !important; }
    #printableReportArea.measuring-print .no-print { display: none !important; }
    #printableReportArea.measuring-print table { width: 100% !important; border-collapse: collapse !important; border: 1px solid #475569 !important; }
    #printableReportArea.measuring-print th { background-color: #008ED6 !important; color: #ffffff !important; font-size: 10px !important; font-weight: 900 !important; text-transform: uppercase !important; padding: 5px 6px !important; border: 1px solid #0072ad !important; }
    #printableReportArea.measuring-print td { border: 1px solid #475569 !important; padding: 3px 4px !important; font-size: 8.5px !important; line-height: 1.2 !important; color: #0F1E2C !important; }
    #printableReportArea.measuring-print th:first-child,
    #printableReportArea.measuring-print td:first-child { width: 24px !important; min-width: 24px !important; max-width: 28px !important; padding: 3px 2px !important; text-align: center !important; }
    #printableReportArea.measuring-print th.col-receipt, #printableReportArea.measuring-print td.col-receipt { width: 122px !important; min-width: 118px !important; max-width: 125px !important; }
    #printableReportArea.measuring-print th.col-student, #printableReportArea.measuring-print td.col-student { width: 175px !important; min-width: 165px !important; max-width: 185px !important; }
    #printableReportArea.measuring-print th.col-fee-details, #printableReportArea.measuring-print td.col-fee-details { width: auto !important; min-width: 250px !important; }
    #printableReportArea.measuring-print th.col-method, #printableReportArea.measuring-print td.col-method { width: 58px !important; min-width: 55px !important; max-width: 65px !important; }
    #printableReportArea.measuring-print th.col-amount, #printableReportArea.measuring-print td.col-amount { width: 88px !important; min-width: 85px !important; max-width: 92px !important; font-size: 11px !important; font-weight: 900 !important; font-family: monospace !important; text-align: right !important; }
    #printableReportArea.measuring-print th.col-invoices, #printableReportArea.measuring-print td.col-invoices { width: 65px !important; min-width: 60px !important; max-width: 70px !important; text-align: center !important; }
    #printableReportArea.measuring-print th.col-due-amount, #printableReportArea.measuring-print td.col-due-amount { width: 95px !important; min-width: 90px !important; max-width: 100px !important; font-size: 11px !important; font-weight: 900 !important; font-family: monospace !important; text-align: right !important; }
    #printableReportArea.measuring-print .col-fee-details-list { display: flex !important; flex-direction: column !important; gap: 1.5px !important; }
    #printableReportArea.measuring-print .fee-detail-line, #printableReportArea.measuring-print .fee-detail-line span { font-size: 10.5px !important; line-height: 1.25 !important; }
    #printableReportArea.measuring-print td.col-method span { font-size: 10.5px !important; }
    #printableReportArea.measuring-print .fee-cat-name { font-weight: 800 !important; color: #0F1E2C !important; }
    #printableReportArea.measuring-print .fee-sep { font-weight: 700 !important; color: #475569 !important; margin: 0 2.5px !important; }
    #printableReportArea.measuring-print .fee-months { font-weight: 600 !important; color: #0F1E2C !important; }
    #printableReportArea.measuring-print .student-meta, #printableReportArea.measuring-print .student-meta span { font-size: 10.5px !important; font-weight: 700 !important; line-height: 1.25 !important; }
    #printableReportArea.measuring-print tfoot td { padding: 5px 6px !important; font-size: 8.5px !important; font-weight: 800 !important; }
    #printableReportArea.measuring-print tfoot td.col-amount { font-size: 11.5px !important; font-weight: 900 !important; font-family: monospace !important; text-align: right !important; }
</style>
@endpush

@section('content')
<div class="w-full min-w-0 min-h-screen" x-data="{ activeTab: 'collection' }">

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
            <!-- Export CSV Button -->
            <button type="button" onclick="exportCurrentReport()" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export CSV
            </button>

            <!-- Print Report Button -->
            <button type="button" onclick="prepareAndPrint()" class="h-11 px-5 rounded-xl bg-gradient-to-r from-themeBlue to-themeGreen hover:opacity-95 text-white text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Report
            </button>

            <a href="{{ route('fees.payments.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Receipts List
            </a>
            <a href="{{ route('fees.collection.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Collect Fees
            </a>
        </div>
    </div>

    @php
    $selectedBranch = $branches->firstWhere('id', request('branch_id'));
    $selectedBranchName = $selectedBranch ? ($selectedBranch->branch_name ?? $selectedBranch->name) : 'All Branches';

    $selectedClass = $classes->firstWhere('id', request('class_id'));
    $selectedClassName = $selectedClass ? $selectedClass->class_name : 'All Classes';

    $selectedMonthVal = $selectedMonth ?? '';
    $selectedYearVal = $selectedYear ?? (int) date('Y', strtotime($startDate));
    $availableYearsList = $availableYears ?? range((int) date('Y') + 1, max(2020, (int) date('Y') - 5));
    $monthOptions = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    $macsLogoPath = public_path('img/macs_logo.jpeg');
    $macsLogoSrc = file_exists($macsLogoPath) ? asset('img/macs_logo.jpeg') : (file_exists(public_path('img/logo.png')) ? asset('img/logo.png') : '');
    @endphp

    <!-- Filter Card (Rule 7 Dropdowns, Specific Month Selector & Rule 10 DatePickers) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-6 mb-8 relative z-20 no-print shadow-sm hover:shadow-md transition-all duration-300" x-data="reportFilter()" @manual-date-picked.window="clearMonthSelection()">
        <form action="{{ route('fees.reports.index') }}" method="GET" id="reportFilterForm" class="space-y-4">
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="due_type" :value="form.due_type">
            <input type="hidden" name="month" :value="form.month">
            <input type="hidden" name="year" :value="form.year">

            <!-- Row 1: Scope & Due Mode -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <!-- Branch Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Branch</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="branchText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'branch' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectBranch('', 'All Branches')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>All Branches</span>
                            <template x-if="form.branch_id === ''">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        @foreach($branches as $b)
                        <button type="button" @click="selectBranch('{{ $b->id }}', '{{ addslashes($b->branch_name ?? $b->name) }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == '{{ $b->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>{{ $b->branch_name ?? $b->name }}</span>
                            <template x-if="form.branch_id == '{{ $b->id }}'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Class Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Filter by Class</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="classText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'class' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectClass('', 'All Classes')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>All Classes</span>
                            <template x-if="form.class_id === ''">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        @foreach($classes as $c)
                        <button type="button" @click="selectClass('{{ $c->id }}', '{{ addslashes($c->class_name) }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id == '{{ $c->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>{{ $c->class_name }}</span>
                            <template x-if="form.class_id == '{{ $c->id }}'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Due Filter Mode Dropdown (Rule 7) -->
                <div class="relative sm:col-span-2 lg:col-span-1" @click.away="if(activeDropdown === 'due_type') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Due Filter Mode</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'due_type' ? null : 'due_type'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="dueTypeText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'due_type' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="activeDropdown === 'due_type'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectDueType('period', 'Selected Period (Monthly Due)')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.due_type === 'period' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>Selected Period (Monthly Due)</span>
                            <template x-if="form.due_type === 'period'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        <button type="button" @click="selectDueType('cumulative', 'Cumulative Arrears (Up to Date)')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.due_type === 'cumulative' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>Cumulative Arrears (Up to Date)</span>
                            <template x-if="form.due_type === 'cumulative'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Row 2: Specific Month / Year & Custom Date Range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Specific Month Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'month') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Specific Month</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'month' ? null : 'month'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 rounded-xl flex items-center justify-between text-xs font-semibold focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left" :class="form.month ? 'border-themeBlue/50 text-themeBlue dark:text-blue-300' : 'border-gray-100 dark:border-gray-800 text-gray-700 dark:text-gray-200'">
                        <span class="truncate" x-text="form.month ? form.month : 'Custom Date Range'"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'month' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="activeDropdown === 'month'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectMonth('')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.month === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>Custom Date Range</span>
                            <template x-if="form.month === ''">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        @foreach($monthOptions as $mName)
                        <button type="button" @click="selectMonth('{{ $mName }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.month === '{{ $mName }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>{{ $mName }}</span>
                            <template x-if="form.month === '{{ $mName }}'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- Year Dropdown (Rule 7) -->
                <div class="relative" @click.away="if(activeDropdown === 'year') activeDropdown = null">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Year</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'year' ? null : 'year'" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate font-mono font-bold" x-text="form.year"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="activeDropdown === 'year' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="activeDropdown === 'year'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        @foreach($availableYearsList as $yr)
                        <button type="button" @click="selectYear('{{ $yr }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left font-mono hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="String(form.year) === '{{ $yr }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                            <span>{{ $yr }}</span>
                            <template x-if="String(form.year) === '{{ $yr }}'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                        @endforeach
                    </div>
                </div>

                <!-- From Date Picker (Rule 10) -->
                <div class="relative" x-data="datePicker('{{ $startDate }}')" @click.away="show = false" @sync-report-dates.window="if ($event.detail && $event.detail.start) { value = $event.detail.start; }">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">From Date</label>
                    <input type="hidden" name="start_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>

                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute left-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between mb-3 border-b border-gray-100 dark:border-white/[0.04] pb-2">
                            <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div class="flex items-center gap-1.5">
                                <!-- Month Dropdown -->
                                <div class="relative" x-data="{ mOpen: false }">
                                    <button type="button" @click="mOpen = !mOpen" class="flex items-center gap-0.5 px-2 py-1 bg-gray-50/50 dark:bg-themeDark border border-gray-150 dark:border-gray-800 rounded-lg text-xs font-black text-gray-700 dark:text-gray-200 hover:text-themeBlue transition-all">
                                        <span x-text="monthNames[currentMonth]"></span>
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
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
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
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
                                    :class="d.day === parseInt((value || '').split('-')[2]) && currentMonth === (parseInt((value || '').split('-')[1], 10) - 1) && parseInt(currentYear, 10) === parseInt((value || '').split('-')[0], 10) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                    :disabled="!d.isCurrentMonth">
                                    <span x-text="d.day"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- To Date Picker (Rule 10) -->
                <div class="relative" x-data="datePicker('{{ $endDate }}')" @click.away="show = false" @sync-report-dates.window="if ($event.detail && $event.detail.end) { value = $event.detail.end; }">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">To Date</label>
                    <input type="hidden" name="end_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>

                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute left-0 sm:left-auto sm:right-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between mb-3 border-b border-gray-100 dark:border-white/[0.04] pb-2">
                            <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div class="flex items-center gap-1.5">
                                <!-- Month Dropdown -->
                                <div class="relative" x-data="{ mOpen: false }">
                                    <button type="button" @click="mOpen = !mOpen" class="flex items-center gap-0.5 px-2 py-1 bg-gray-50/50 dark:bg-themeDark border border-gray-150 dark:border-gray-800 rounded-lg text-xs font-black text-gray-700 dark:text-gray-200 hover:text-themeBlue transition-all">
                                        <span x-text="monthNames[currentMonth]"></span>
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
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
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
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
                                    :class="d.day === parseInt((value || '').split('-')[2]) && currentMonth === (parseInt((value || '').split('-')[1], 10) - 1) && parseInt(currentYear, 10) === parseInt((value || '').split('-')[0], 10) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                    :disabled="!d.isCurrentMonth">
                                    <span x-text="d.day"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Action Bar (Rule 2 Buttons — Spacious & Balanced) -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-white/[0.06]">
                <div class="flex items-center gap-2">
                    <template x-if="form.month">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-50 dark:bg-themeBlue/15 border border-sky-200/70 dark:border-themeBlue/30 text-[11px] font-bold text-themeBlue dark:text-blue-300">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Monthly Scope: <strong x-text="form.month + ' ' + form.year"></strong></span>
                        </span>
                    </template>
                    <template x-if="!form.month">
                        <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500">
                            Select a specific month for full-month figures, or customize From / To dates.
                        </span>
                    </template>
                </div>

                <div class="flex items-center gap-2.5 ml-auto">
                    <a href="{{ route('fees.reports.index') }}" class="h-11 px-6 border-2 border-gray-200 dark:border-gray-700/80 rounded-xl bg-white dark:bg-themeDark/60 hover:bg-gray-50 dark:hover:bg-themeDark text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset
                    </a>
                    <button type="submit" class="h-11 px-7 bg-gradient-to-r from-themeBlue to-themeGreen hover:opacity-95 text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Aggregate Stats Dashboard Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 no-print">

        <!-- Total Collection Card -->
        <div class="bg-gradient-to-br from-themeGreen to-green-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden flex flex-col justify-between">
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
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
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-red-100 font-bold uppercase tracking-wider text-[10px] mb-1">
                    {{ ($dueType ?? 'period') === 'cumulative' ? 'Total Cumulative Arrears' : 'Total Period Pending Dues' }}
                </p>
                <h3 class="text-3xl font-black font-mono tracking-tight">৳ {{ number_format($totalDue, 2) }}</h3>
                <p class="text-[10px] text-red-200 font-semibold mt-1">
                    @if(($dueType ?? 'period') === 'cumulative')
                    Period Due (Range): <span class="font-mono font-bold text-white">৳ {{ number_format($periodTotalDue ?? 0, 0) }}</span>
                    @else
                    Cumulative Arrears: <span class="font-mono font-bold text-white">৳ {{ number_format($cumulativeTotalDue ?? 0, 0) }}</span>
                    @endif
                </p>
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

    <!-- Segmented Tab Switcher & Quick Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6 no-print">
        <div class="flex gap-2 p-1.5 bg-gray-50/70 dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-2xl w-fit">
            <button @click="activeTab = 'collection'" class="h-10 px-5 text-xs rounded-xl transition-all uppercase tracking-wider flex items-center justify-center gap-2" :class="activeTab === 'collection' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : 'text-gray-500 dark:text-gray-450 font-bold hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Collection History (<span class="font-mono">{{ $totalCollectionCount }}</span>)
            </button>
            <button @click="activeTab = 'dues'" class="h-10 px-5 text-xs rounded-xl transition-all uppercase tracking-wider flex items-center justify-center gap-2" :class="activeTab === 'dues' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : 'text-gray-500 dark:text-gray-450 font-bold hover:text-gray-900 dark:hover:text-white'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Defaulters List (<span class="font-mono">{{ $uniqueDefaulterStudentsCount }}</span>)
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="text-xs font-bold text-gray-500 dark:text-gray-400 bg-white dark:bg-themeNavy px-4 py-2.5 rounded-2xl border border-gray-100 dark:border-white/[0.06] shadow-sm">
                Filtered: <span class="text-themeBlue font-black">{{ $selectedClassName }}</span> •
                @if($selectedMonthVal)
                    <span class="text-themeGreen font-black">{{ $selectedMonthVal }} {{ $selectedYearVal }}</span>
                    <span class="text-gray-400 dark:text-gray-500 font-semibold">({{ date('d M Y', strtotime($startDate)) }} to {{ date('d M Y', strtotime($endDate)) }})</span>
                @else
                    <span class="text-gray-700 dark:text-gray-200 font-black">{{ date('d M Y', strtotime($startDate)) }} to {{ date('d M Y', strtotime($endDate)) }}</span>
                @endif
            </div>

            <!-- Quick Table Action Buttons -->
            <button type="button" onclick="exportCurrentReport()" title="Export active table to CSV" class="h-10 px-3 bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 border border-gray-200 dark:border-white/[0.08] rounded-xl text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center gap-1.5 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-3.5 h-3.5 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export
            </button>
            <button type="button" onclick="prepareAndPrint()" title="Print active report" class="h-10 px-3 bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 border border-gray-200 dark:border-white/[0.08] rounded-xl text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center gap-1.5 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print
            </button>
        </div>
    </div>

    <!-- Official Printable Report Area -->
    <div id="printableReportArea">
        <!-- Paginated Print Output Container (Dynamically constructed for print) -->
        <div id="printPagesContainer" class="print-only" style="display: none;"></div>

        <div class="print-top-section">
            <!-- Official Printable Report Header (Point 4: 1st page only, Point 3: minimized gaps) -->
            <div class="print-only mb-1.5">
                <!-- Header: Logo (Left), School Info & Title (Center), Print Date (Right) -->
                <table style="width: 100%; border-collapse: collapse; border: none !important; margin-bottom: 2px;">
                    <tr style="border: none !important;">
                        <!-- Left: MACS Logo -->
                        <td style="width: 14%; text-align: left; vertical-align: middle; border: none !important; padding: 0 !important;">
                            @if($macsLogoSrc)
                            <img src="{{ $macsLogoSrc }}" style="width: 52px; height: 52px; object-fit: contain; border-radius: 50%; border: 1.5px solid #008ED6; padding: 2px;" alt="MACS Logo">
                            @else
                            <div style="width: 50px; height: 50px; border: 1.5px solid #008ED6; border-radius: 50%; text-align: center; line-height: 50px; font-weight: 900; color: #008ED6; font-size: 11px;">MACS</div>
                            @endif
                        </td>

                        <!-- Center: School Details & Report Badge -->
                        <td style="width: 72%; text-align: center; vertical-align: middle; border: none !important; padding: 0 !important;">
                            <div style="font-size: 20px; font-weight: 900; color: #0F1E2C; letter-spacing: -0.3px; text-transform: uppercase; line-height: 1.1;">
                                MACS School &amp; College
                            </div>
                            <div style="font-size: 8.5px; font-weight: 600; color: #475569; margin-top: 1px;">
                                Jalalpur, Pabna Sadar, Pabna &bull; Bangladesh
                            </div>
                            <div style="font-size: 8px; font-weight: 500; color: #64748B; margin-top: 0.5px;">
                                Hotline: 01896-220299, 01896-220300 &bull; Web: macs.edu.bd
                            </div>
                            <div style="margin-top: 4px; margin-bottom: 4px;">
                                <span style="display: inline-block; background-color: #008ED6; color: #ffffff; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; padding: 5px 18px; border-radius: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact;" x-text="activeTab === 'collection' ? 'FEE COLLECTION HISTORY REPORT' : ('{{ ($dueType ?? 'period') === 'cumulative' ? 'STUDENT CUMULATIVE ARREARS REPORT' : 'STUDENT MONTHLY / PERIOD DUES REPORT' }}')">
                                    FEE COLLECTION HISTORY REPORT
                                </span>
                            </div>
                        </td>

                        <!-- Right: Print Metadata -->
                        <td style="width: 14%; text-align: right; vertical-align: middle; border: none !important; padding: 0 !important;">
                            <div style="font-size: 7.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.4px;">Date of Issue</div>
                            <div style="font-size: 9.5px; font-weight: 800; font-family: monospace; color: #0F1E2C; margin-top: 1px;">{{ date('d M, Y') }}</div>
                            <div style="font-size: 7.5px; font-weight: 600; color: #94A3B8;">{{ date('h:i A') }}</div>
                        </td>
                    </tr>
                </table>

                <!-- Filter Meta & Summary Highlights Card (Point 1: Ultra Compact Single-Row Table) -->
                <div style="background-color: #F8FAFC; border: 1px solid #CBD5E1; border-radius: 4px; padding: 3px 6px; margin-top: 1px; margin-bottom: 2px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                    <!-- Collection Summary Row -->
                    <table x-show="activeTab === 'collection'" style="width: 100%; border: none !important; border-collapse: collapse; margin: 0 !important; padding: 0 !important; background: transparent !important;">
                        <tr style="border: none !important; background: transparent !important;">
                            <td style="border: none !important; padding: 0.5px 2px !important; font-size: 10px; color: #334155; font-weight: 700; white-space: nowrap;">
                                Branch: <strong style="color: #008ED6;">{{ $selectedBranchName }}</strong>
                                &nbsp;&bull;&nbsp; Class: <strong style="color: #008ED6;">{{ $selectedClassName }}</strong>
                                &nbsp;&bull;&nbsp; Period: <strong style="color: #0F1E2C;">@if($selectedMonthVal){{ $selectedMonthVal }} {{ $selectedYearVal }} ({{ date('d M Y', strtotime($startDate)) }} - {{ date('d M Y', strtotime($endDate)) }})@else{{ date('d M Y', strtotime($startDate)) }} - {{ date('d M Y', strtotime($endDate)) }}@endif</strong>
                            </td>
                            <td style="border: none !important; padding: 0.5px 2px !important; font-size: 10px; color: #334155; font-weight: 700; text-align: right; white-space: nowrap;">
                                Receipts: <strong style="color: #0F1E2C;">{{ $totalCollectionCount }}</strong>@if($totalPaymentRecordsCount > $totalCollectionCount) <span style="color: #64748B; font-size: 9px;">({{ $totalPaymentRecordsCount }} Fees)</span>@endif
                                &nbsp;&bull;&nbsp; Students: <strong style="color: #0F1E2C;">{{ $uniquePayingStudentsCount }}</strong>
                                &nbsp;&bull;&nbsp; Total Collected: <strong style="color: #009A49; font-size: 11.5px; font-family: monospace; font-weight: 900;">৳ {{ number_format($totalCollected, 2) }}</strong>
                            </td>
                        </tr>
                    </table>

                    <!-- Defaulters Summary Row -->
                    <table x-show="activeTab === 'dues'" style="width: 100%; border: none !important; border-collapse: collapse; margin: 0 !important; padding: 0 !important; background: transparent !important;">
                        <tr style="border: none !important; background: transparent !important;">
                            <td style="border: none !important; padding: 0.5px 2px !important; font-size: 10px; color: #334155; font-weight: 700; white-space: nowrap;">
                                Branch: <strong style="color: #008ED6;">{{ $selectedBranchName }}</strong>
                                &nbsp;&bull;&nbsp; Class: <strong style="color: #008ED6;">{{ $selectedClassName }}</strong>
                                &nbsp;&bull;&nbsp; Period: <strong style="color: #0F1E2C;">{{ ($dueType ?? 'period') === 'cumulative' ? 'All Unpaid Arrears up to ' . date('d M Y', strtotime($endDate)) : ($selectedMonthVal ? $selectedMonthVal . ' ' . $selectedYearVal . ' (' . date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate)) . ')' : date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate))) }}</strong>
                            </td>
                            <td style="border: none !important; padding: 0.5px 2px !important; font-size: 10px; color: #334155; font-weight: 700; text-align: right; white-space: nowrap;">
                                Defaulters: <strong style="color: #DC2626;">{{ $uniqueDefaulterStudentsCount }}</strong>
                                &nbsp;&bull;&nbsp; Invoices: <strong style="color: #0F1E2C;">{{ $totalDueInvoicesCount }}</strong>
                                &nbsp;&bull;&nbsp; Total Due: <strong style="color: #DC2626; font-size: 11.5px; font-family: monospace; font-weight: 900;">৳ {{ number_format($totalDue, 2) }}</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Data Tables Card (AGENTS.md Rule 9, print:!mt-0 print:!mb-0) -->
            <div class="table-card-wrapper w-full min-w-0 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-12 print:!border-none print:!shadow-none print:!rounded-none print:!overflow-visible print:!bg-transparent print:!mb-0 print:!mt-0 print:!p-0">

                <!-- Collection History Tab -->
                <div id="tab-collection" class="tab-content active" :class="{ 'active': activeTab === 'collection' }">
                    <!-- Screen-only Section Header -->
                    <div class="no-print px-6 py-4 border-b border-gray-100 dark:border-white/[0.06] flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-themeGreen flex-shrink-0"></span>
                            <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">Collection History</h3>
                        </div>
                        <div class="flex items-center gap-2 text-[11px] font-semibold text-gray-400 dark:text-gray-500 whitespace-nowrap">
                            <span><strong class="font-mono text-gray-700 dark:text-gray-200">{{ $totalCollectionCount }}</strong> receipt(s)</span>
                            <span>&bull;</span>
                            <span><strong class="font-mono text-gray-700 dark:text-gray-200">{{ $uniquePayingStudentsCount }}</strong> student(s)</span>
                        </div>
                    </div>

                    <div class="px-6 pb-4 pt-1 print:!p-0">
                        <div class="table-container bg-transparent !border-none !shadow-none !rounded-none print:!mt-0 !mt-2 !mb-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse table tabular-nums [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-1 [&>thead>tr>th:last-child]:!pr-1 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-1 [&>tbody>tr>td:last-child]:!pr-1 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35 [&>tbody>tr>td.col-sl]:!text-gray-400 dark:[&>tbody>tr>td.col-sl]:!text-gray-500 [&>tbody>tr>td.col-amount]:!text-themeGreen dark:[&>tbody>tr>td.col-amount]:!text-emerald-400 [&>tfoot>tr>td]:!py-3 [&>tfoot>tr>td]:!px-3 [&>tfoot>tr>td:first-child]:!pl-1 [&>tfoot>tr>td:last-child]:!pr-1 [&>tfoot>tr>td]:!bg-gray-50/80 dark:[&>tfoot>tr>td]:!bg-themeDark/60 [&>tfoot>tr:hover>td]:!bg-gray-50/80 dark:[&>tfoot>tr:hover>td]:!bg-themeDark/60 [&>tfoot>tr>td]:!border-t-2 [&>tfoot>tr>td]:!border-b-0 [&>tfoot>tr>td]:!border-gray-200 dark:[&>tfoot>tr>td]:!border-white/[0.12] [&>tfoot>tr>td.col-amount]:!text-themeGreen dark:[&>tfoot>tr>td.col-amount]:!text-emerald-400">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center whitespace-nowrap w-12 col-sl">#</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] whitespace-nowrap col-receipt">Receipt &amp; Date</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] whitespace-nowrap col-student">Student Info</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] whitespace-nowrap col-fee-details">Fee Details &amp; Month</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center whitespace-nowrap col-method">Method</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right whitespace-nowrap col-amount">Paid Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($collections as $index => $col)
                                    <tr class="transition-colors" x-data="{ expanded: false }">
                                        <td class="py-0 px-0 text-center font-mono font-black text-sm whitespace-nowrap col-sl">{{ $index + 1 }}</td>
                                        <td class="py-0 px-0 col-receipt whitespace-nowrap">
                                            <div class="flex items-center gap-1.5 flex-nowrap">
                                                <a href="{{ route('fees.receipt.pos_print', $col->receipt_no) }}" target="_blank" class="font-mono font-black text-themeBlue hover:underline text-xs whitespace-nowrap" title="Print POS Receipt">{{ $col->receipt_no }}</a>
                                                @if($col->items_count > 1)
                                                 <span class="no-print inline-flex items-center px-1.5 py-0.5 bg-sky-50 dark:bg-themeBlue/15 border border-sky-200/70 dark:border-themeBlue/30 text-themeBlue dark:text-blue-300 text-[9px] font-black rounded-md whitespace-nowrap">{{ $col->items_count }} Fees</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold mt-0.5 whitespace-nowrap">{{ $col->collection_date_formatted ?? 'Date unavailable' }}</div>
                                        </td>
                                        <td class="py-0 px-0 col-student whitespace-nowrap">
                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $col->student->student_name ?? 'N/A' }}</div>
                                            <div class="student-meta text-xs font-bold text-gray-600 dark:text-gray-400 mt-0.5 leading-tight whitespace-nowrap">
                                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $col->student->student_identity ?? 'N/A' }}</span>
                                                <span class="text-gray-400 mx-1">&bull;</span>
                                                @if(!$selectedClass)
                                                <span class="text-gray-700 dark:text-gray-300">{{ $col->student->schoolClass->class_name ?? 'N/A' }}</span>,
                                                @endif
                                                <span class="text-gray-600 dark:text-gray-400">Roll: {{ $col->student->roll_number ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-semibold text-gray-700 dark:text-gray-300 col-fee-details">
                                            <div class="col-fee-details-list space-y-0.5">
                                                @if(isset($col->category_months) && count($col->category_months) > 0)
                                                    @foreach($col->category_months as $catName => $catMonths)
                                                        @php
                                                            $filteredMonths = collect($catMonths)->filter(function($m) {
                                                                return !empty($m) && !in_array(strtolower(trim($m)), ['one time', 'one_time', 'general', '']);
                                                            });
                                                        @endphp
                                                        <div class="fee-detail-line text-xs leading-snug">
                                                            <span class="fee-cat-name font-bold text-gray-900 dark:text-gray-100">{{ $catName }}</span>@if($filteredMonths->isNotEmpty())<span class="fee-sep font-bold text-gray-400 mx-1">-</span><span class="fee-months font-semibold text-gray-600 dark:text-gray-300">{{ $filteredMonths->join(', ') }}</span>@elseif(collect($catMonths)->isNotEmpty() && strtolower(trim(collect($catMonths)->first())) === 'one time')<span class="fee-sep font-bold text-gray-400 mx-1">-</span><span class="fee-months font-semibold text-gray-600 dark:text-gray-300">One Time</span>@endif
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="fee-detail-line text-xs leading-snug">
                                                        <span class="fee-cat-name font-bold text-gray-900 dark:text-gray-100">{{ $col->categories_summary ?? 'Fee' }}</span>@if(!empty($col->months))<span class="fee-sep font-bold text-gray-400 mx-1">-</span><span class="fee-months font-semibold text-gray-600 dark:text-gray-300">{{ collect($col->months)->join(', ') }}</span>@endif
                                                    </div>
                                                @endif
                                            </div>
                                            @if($col->items_count > 1)
                                            <div class="no-print mt-1">
                                                <button type="button" @click="expanded = !expanded" class="text-[10px] font-bold text-themeBlue hover:underline inline-flex items-center gap-1 focus:outline-none">
                                                    <span x-text="expanded ? 'Hide fee breakdown' : 'View breakdown (' + {{ $col->items_count }} + ')'"></span>
                                                    <svg class="w-3 h-3 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                <div x-show="expanded" x-cloak class="mt-1.5 p-2.5 bg-gray-50/90 dark:bg-themeDark/80 rounded-xl border border-gray-200/70 dark:border-white/[0.08] text-[11px] space-y-1">
                                                    @foreach($col->items as $it)
                                                    @php
                                                    $itMonth = $it->invoice->month_name ?? 'One Time';
                                                    @endphp
                                                    <div class="flex justify-between items-center gap-4 text-gray-600 dark:text-gray-300">
                                                        <span>{{ $it->report_fee_label ?? ($it->invoice->feeSetup->category->name ?? 'Fee') }} ({{ $itMonth }})</span>
                                                        <span class="font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap">৳ {{ number_format($it->report_effective_amount ?? $it->paid_amount, 2) }}</span>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                        <td class="py-0 px-0 text-center whitespace-nowrap col-method">
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-lg border border-gray-200 dark:border-white/[0.08] text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-themeDark/50 whitespace-nowrap">{{ $col->payment_method }}</span>
                                        </td>
                                        <td class="py-0 px-0 text-right font-black text-sm sm:text-base font-mono whitespace-nowrap col-amount">
                                            ৳ {{ number_format($col->paid_amount, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="py-12 text-center text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">No collections found in this date range.</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>

                                <!-- Table Summary Footer (Aggregate Collections) -->
                                @if($collections->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="whitespace-nowrap">
                                            <span class="text-xs font-black uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                                Total Collection Summary: <strong class="text-themeBlue font-mono">{{ $totalCollectionCount }}</strong> Receipts @if($totalPaymentRecordsCount > $totalCollectionCount) (<strong class="font-mono">{{ $totalPaymentRecordsCount }}</strong> Invoices) @endif &bull; <strong class="text-themeBlue font-mono">{{ $uniquePayingStudentsCount }}</strong> Students
                                            </span>
                                        </td>
                                        <td colspan="2" class="text-right whitespace-nowrap">
                                            <span class="text-xs font-black uppercase tracking-wider text-gray-600 dark:text-gray-300">Grand Total Collected:</span>
                                        </td>
                                        <td class="text-right font-mono text-base font-black whitespace-nowrap col-amount">
                                            ৳ {{ number_format($totalCollected, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Defaulters List Tab (Student-Wise with Total Due Months & Month Names) -->
                <div id="tab-dues" class="tab-content" :class="{ 'active': activeTab === 'dues' }">
                    <!-- Quick Mode Switcher Header Bar (Rule 2) -->
                    <div class="no-print px-6 py-3.5 border-b border-gray-100 dark:border-white/[0.06] bg-gray-50/40 dark:bg-themeDark/30 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mr-1">Arrears Mode:</span>
                            <a href="{{ request()->fullUrlWithQuery(['due_type' => 'period']) }}" class="px-3.5 py-1.5 text-[11px] rounded-xl transition-all uppercase tracking-wider inline-flex items-center gap-1.5 whitespace-nowrap {{ ($dueType ?? 'period') === 'period' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : 'bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] text-gray-600 dark:text-gray-300 font-bold hover:text-themeBlue' }}">
                                <span>Period Due</span>
                                <span class="font-mono text-[11px] opacity-90">(৳ {{ number_format($periodTotalDue ?? 0, 0) }})</span>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['due_type' => 'cumulative']) }}" class="px-3.5 py-1.5 text-[11px] rounded-xl transition-all uppercase tracking-wider inline-flex items-center gap-1.5 whitespace-nowrap {{ ($dueType ?? 'period') === 'cumulative' ? 'bg-gradient-to-r from-red-600 to-red-800 text-white font-black shadow-sm' : 'bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] text-gray-600 dark:text-gray-300 font-bold hover:text-red-500' }}">
                                <span>Cumulative Arrears</span>
                                <span class="font-mono text-[11px] opacity-90">(৳ {{ number_format($cumulativeTotalDue ?? 0, 0) }})</span>
                            </a>
                        </div>
                        <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                            @if(($dueType ?? 'period') === 'cumulative')
                            Showing all unpaid balances due on or before <span class="font-bold text-gray-800 dark:text-gray-200">{{ date('d M Y', strtotime($endDate)) }}</span>
                            @else
                            Showing only invoices due between <span class="font-bold text-gray-800 dark:text-gray-200">{{ date('d M Y', strtotime($startDate)) }}</span> and <span class="font-bold text-gray-800 dark:text-gray-200">{{ date('d M Y', strtotime($endDate)) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="px-6 pb-4 pt-1 print:!p-0">
                        <div class="table-container bg-transparent !border-none !shadow-none !rounded-none print:!mt-0 !mt-2 !mb-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse table tabular-nums [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-1 [&>thead>tr>th:last-child]:!pr-1 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>thead>tr>th.col-due-amount]:!text-rose-500 dark:[&>thead>tr>th.col-due-amount]:!text-rose-400 [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-1 [&>tbody>tr>td:last-child]:!pr-1 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35 [&>tbody>tr>td.col-sl]:!text-gray-400 dark:[&>tbody>tr>td.col-sl]:!text-gray-500 [&>tbody>tr>td.col-fee-types]:!text-xs [&>tbody>tr>td.col-fee-types]:!text-gray-600 dark:[&>tbody>tr>td.col-fee-types]:!text-gray-300 [&>tbody>tr>td.col-due-amount]:!text-rose-600 dark:[&>tbody>tr>td.col-due-amount]:!text-rose-400 [&>tfoot>tr>td]:!py-3 [&>tfoot>tr>td]:!px-3 [&>tfoot>tr>td:first-child]:!pl-1 [&>tfoot>tr>td:last-child]:!pr-1 [&>tfoot>tr>td]:!bg-gray-50/80 dark:[&>tfoot>tr>td]:!bg-themeDark/60 [&>tfoot>tr:hover>td]:!bg-gray-50/80 dark:[&>tfoot>tr:hover>td]:!bg-themeDark/60 [&>tfoot>tr>td]:!border-t-2 [&>tfoot>tr>td]:!border-b-0 [&>tfoot>tr>td]:!border-gray-200 dark:[&>tfoot>tr>td]:!border-white/[0.12] [&>tfoot>tr>td.col-due-amount]:!text-rose-600 dark:[&>tfoot>tr>td.col-due-amount]:!text-rose-400">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center whitespace-nowrap w-12 col-sl">#</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] whitespace-nowrap col-student">Student Info</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] whitespace-nowrap col-fee-details">Total Due Months &amp; Month Names</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] whitespace-nowrap col-fee-types">Fee Types</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center whitespace-nowrap col-invoices">Invoices</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-rose-500 dark:text-rose-400 uppercase tracking-[0.2em] text-right whitespace-nowrap col-due-amount col-amount">Total Due Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($defaulters as $index => $def)
                                    <tr class="transition-colors">
                                        <td class="py-0 px-0 text-center font-mono font-black text-sm whitespace-nowrap col-sl">{{ $index + 1 }}</td>
                                        <td class="py-0 px-0 col-student whitespace-nowrap">
                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $def->student->student_name ?? 'N/A' }}</div>
                                            <div class="student-meta text-xs font-bold text-gray-600 dark:text-gray-400 mt-0.5 leading-tight whitespace-nowrap">
                                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $def->student->student_identity ?? 'N/A' }}</span>
                                                <span class="text-gray-400 mx-1">&bull;</span>
                                                @if(!$selectedClass)
                                                <span class="text-gray-700 dark:text-gray-300">{{ $def->student->schoolClass->class_name ?? 'N/A' }}</span>,
                                                @endif
                                                <span class="text-gray-600 dark:text-gray-400">Roll: {{ $def->student->roll_number ?? 'N/A' }}</span>
                                                @if($def->student && $def->student->phone)
                                                <span class="text-gray-400 mx-1">&bull;</span>
                                                <span class="text-gray-500">{{ $def->student->phone }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-0 px-0 col-fee-details">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-black bg-rose-50 text-rose-600 border border-rose-200/70 dark:bg-rose-950/40 dark:border-rose-500/25 dark:text-rose-300 rounded-lg whitespace-nowrap">
                                                    {{ $def->due_months_count }} {{ Str::plural('Month', $def->due_months_count) }} Due
                                                </span>
                                                @foreach($def->due_months as $monthName)
                                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold bg-gray-100 dark:bg-themeDark border border-gray-200 dark:border-white/[0.08] text-gray-700 dark:text-gray-300 rounded-md whitespace-nowrap">
                                                    {{ $monthName }}
                                                </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="py-0 px-0 text-xs font-semibold col-fee-types">
                                            {{ $def->categories->join(', ') }}
                                        </td>
                                        <td class="py-0 px-0 text-center whitespace-nowrap col-invoices">
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-mono font-bold bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-white/[0.08] rounded-lg text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                {{ $def->total_invoices }} {{ Str::plural('Inv', $def->total_invoices) }}
                                            </span>
                                        </td>
                                        <td class="py-0 px-0 text-right font-black text-sm sm:text-base font-mono whitespace-nowrap col-due-amount col-amount">
                                            ৳ {{ number_format($def->total_due, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="py-12 text-center text-themeGreen font-black text-xs uppercase tracking-wider">Awesome! No pending defaulters found for this class and date range.</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>

                                <!-- Table Summary Footer (Aggregate Defaulters Dues) -->
                                @if($defaulters->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="whitespace-nowrap">
                                            <span class="text-xs font-black uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                                Defaulter Summary: <strong class="text-rose-500 font-mono">{{ $uniqueDefaulterStudentsCount }}</strong> Students &bull; <strong class="text-rose-500 font-mono">{{ $totalDueInvoicesCount }}</strong> Unpaid Invoices
                                            </span>
                                        </td>
                                        <td colspan="2" class="text-right whitespace-nowrap">
                                            <span class="text-xs font-black uppercase tracking-wider text-gray-600 dark:text-gray-300">Grand Total Due:</span>
                                        </td>
                                        <td class="text-right font-mono text-base font-black whitespace-nowrap col-due-amount col-amount">
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
        </div><!-- End .print-top-section -->

        <!-- Dynamic spacer to push signatures to the absolute bottom of the final page -->
        <div id="printSigSpacer" class="print-only" style="width: 100%; height: 0px;"></div>

        <!-- Official Printable Signatures (Point 3: Exactly once, only on final page near bottom) -->
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
                            Accounts Officer
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
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Custom Alpine Report Filter Dropdown Controller
    function reportFilter() {
        return {
            activeDropdown: null,
            branchText: '{{ addslashes($selectedBranchName) }}',
            classText: '{{ addslashes($selectedClassName) }}',
            dueTypeText: '{{ ($dueType ?? 'period') === 'cumulative' ? 'Cumulative Arrears (Up to Date)' : 'Selected Period (Monthly Due)' }}',
            monthNamesList: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

            form: {
                branch_id: '{{ request('branch_id') }}',
                class_id: '{{ request('class_id') }}',
                due_type: '{{ request('due_type', $dueType ?? 'period') }}',
                month: '{{ $selectedMonthVal }}',
                year: '{{ $selectedYearVal }}'
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
            selectDueType(type, text) {
                this.form.due_type = type;
                this.dueTypeText = text;
                this.activeDropdown = null;
            },
            selectMonth(monthName) {
                this.form.month = monthName;
                this.activeDropdown = null;
                if (monthName) {
                    this.syncDatesFromMonth();
                }
            },
            selectYear(yr) {
                this.form.year = String(yr);
                this.activeDropdown = null;
                if (this.form.month) {
                    this.syncDatesFromMonth();
                }
            },
            syncDatesFromMonth() {
                const idx = this.monthNamesList.indexOf(this.form.month);
                const yr = parseInt(this.form.year, 10);
                if (idx < 0 || isNaN(yr)) return;
                const mm = String(idx + 1).padStart(2, '0');
                const lastDay = new Date(yr, idx + 1, 0).getDate();
                const start = `${yr}-${mm}-01`;
                const end = `${yr}-${mm}-${String(lastDay).padStart(2, '0')}`;
                window.dispatchEvent(new CustomEvent('sync-report-dates', { detail: { start, end } }));
            },
            clearMonthSelection() {
                this.form.month = '';
            }
        };
    }

    // Custom Date Picker component data definition (Rule 10)
    function datePicker(initialValue = '') {
        const parseIso = (val) => {
            if (!val) return null;
            const parts = String(val).split('-');
            if (parts.length !== 3) return null;
            const y = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10) - 1;
            const d = parseInt(parts[2], 10);
            if (isNaN(y) || isNaN(m) || isNaN(d)) return null;
            return { y, m, d };
        };
        const initParsed = parseIso(initialValue);
        const now = new Date();

        return {
            show: false,
            value: initialValue || '',
            currentYear: initParsed ? initParsed.y : now.getFullYear(),
            currentMonth: initParsed ? initParsed.m : now.getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    const p = parseIso(val);
                    if (p) {
                        this.currentYear = p.y;
                        this.currentMonth = p.m;
                        this.generateCalendar();
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
                    days.push({
                        day: '',
                        isCurrentMonth: false
                    });
                }
                for (let i = 1; i <= totalDays; i++) {
                    days.push({
                        day: i,
                        isCurrentMonth: true
                    });
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
                window.dispatchEvent(new CustomEvent('manual-date-picked', { detail: this.value }));
            },

            formatDisplay(val) {
                const p = parseIso(val);
                if (!p) return 'Select Date';
                const d = new Date(p.y, p.m, p.d);
                return d.toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            }
        };
    }

    // Financial Reports CSV Export Handler
    function exportCurrentReport() {
        let activeTab = 'collection';
        const alpineEl = document.querySelector('[x-data]');
        if (window.Alpine && alpineEl && Alpine.$data(alpineEl) && Alpine.$data(alpineEl).activeTab) {
            activeTab = Alpine.$data(alpineEl).activeTab;
        } else {
            const tabCollection = document.getElementById('tab-collection');
            activeTab = (tabCollection && tabCollection.classList.contains('active')) ? 'collection' : 'dues';
        }

        const branchName = '{{ addslashes($selectedBranchName) }}';
        const className = '{{ addslashes($selectedClassName) }}';
        const startDate = '{{ $startDate }}';
        const endDate = '{{ $endDate }}';

        let csvRows = [];

        if (activeTab === 'collection') {
            const data = @json($collectionExportData);

            // Header comments / meta
            csvRows.push(['MACS SCHOOL - FEE COLLECTION HISTORY REPORT']);
            csvRows.push([`Branch: ${branchName}`, `Class: ${className}`, `Date Range: ${startDate} to ${endDate}`, `Generated: ${new Date().toLocaleString()}`]);
            csvRows.push([]); // blank line

            // Table headers
            csvRows.push(['#', 'Receipt No', 'Payment Date', 'Student Name', 'Student ID', 'Class', 'Roll', 'Fee Categories', 'Fee Months', 'Fees Count', 'Payment Method', 'Paid Amount (BDT)']);

            // Data rows
            data.forEach(item => {
                csvRows.push([
                    item.sl,
                    item.receipt_no,
                    item.date,
                    item.student_name,
                    item.student_id,
                    item.class,
                    item.roll,
                    item.category,
                    item.month,
                    item.items_count,
                    item.method,
                    typeof item.amount === 'number' ? item.amount.toFixed(2) : String(item.amount ?? '0.00')
                ]);
            });

            // Summary row
            csvRows.push([]);
            csvRows.push(['', '', '', '', '', '', '', '', '', 'Total Collection Summary:', '{{ $totalCollectionCount }} Receipts, {{ $uniquePayingStudentsCount }} Students', '{{ $totalCollectedFormatted }}']);

            downloadCSV(csvRows, `Fee_Collection_Report_${className.replace(/\s+/g, '_')}_${startDate}_to_${endDate}.csv`);
        } else {
            const data = @json($defaultersExportData);

            // Header comments / meta
            csvRows.push(['MACS SCHOOL - STUDENT DEFAULTERS & PENDING DUES REPORT']);
            csvRows.push([`Branch: ${branchName}`, `Class: ${className}`, `Date Range: ${startDate} to ${endDate}`, `Generated: ${new Date().toLocaleString()}`]);
            csvRows.push([]); // blank line

            // Table headers
            csvRows.push(['#', 'Student Name', 'Student ID', 'Phone', 'Class', 'Roll', 'Due Months Count', 'Due Months', 'Fee Categories', 'Unpaid Invoices', 'Total Due (BDT)']);

            // Data rows
            data.forEach(item => {
                csvRows.push([
                    item.sl,
                    item.student_name,
                    item.student_id,
                    item.phone,
                    item.class,
                    item.roll,
                    item.due_months_count,
                    item.due_months,
                    item.categories,
                    item.total_invoices,
                    typeof item.total_due === 'number' ? item.total_due.toFixed(2) : String(item.total_due ?? '0.00')
                ]);
            });

            // Summary row
            csvRows.push([]);
            csvRows.push(['', '', '', '', '', '', '', '', 'Grand Total Due:', '{{ $uniqueDefaulterStudentsCount }} Students, {{ $totalDueInvoicesCount }} Invoices', '{{ $totalDueFormatted }}']);

            downloadCSV(csvRows, `Fee_Defaulters_Report_${className.replace(/\s+/g, '_')}_${startDate}_to_${endDate}.csv`);
        }
    }

    function downloadCSV(rows, filename) {
        const csvContent = '\uFEFF' + rows.map(e => e.map(val => {
            if (val === null || val === undefined) return '""';
            let str = String(val).replace(/"/g, '""');
            return `"${str}"`;
        }).join(',')).join('\r\n');

        const blob = new Blob([csvContent], {
            type: 'text/csv;charset=utf-8;'
        });
        const link = document.createElement('a');
        if (navigator.msSaveBlob) {
            navigator.msSaveBlob(blob, filename);
        } else {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }

    let isPaginating = false;

    // Paginated print generator guaranteeing signature pinned to bottom of final page and no orphaned rows
    function buildPaginatedPrintPages() {
        const printable = document.getElementById('printableReportArea');
        if (!printable) return false;

        const container = document.getElementById('printPagesContainer');
        if (!container) return false;

        container.innerHTML = '';

        // Determine active tab name
        let activeTabName = 'collection';
        const alpineEl = document.querySelector('[x-data]');
        if (window.Alpine && alpineEl && Alpine.$data(alpineEl) && Alpine.$data(alpineEl).activeTab) {
            activeTabName = Alpine.$data(alpineEl).activeTab;
        } else {
            const tabDues = document.getElementById('tab-dues');
            if (tabDues && (tabDues.classList.contains('active') || tabDues.offsetParent !== null)) {
                activeTabName = 'dues';
            }
        }

        const activeTab = document.getElementById(`tab-${activeTabName}`);
        const activeTable = activeTab ? activeTab.querySelector('table') : printable.querySelector('table');
        if (!activeTable) return false;

        const headerBrand = printable.querySelector('.print-top-section > div.print-only');
        const sigElem = printable.querySelector('.print-signatures');
        const thead = activeTable.querySelector('thead');
        const tfoot = activeTable.querySelector('tfoot');
        const rows = Array.from(activeTable.querySelectorAll('tbody tr'));

        // Ensure inactive summary card is hidden during measurement
        const duesSummaryTable = headerBrand ? headerBrand.querySelector('table[x-show*="dues"]') : null;
        const colSummaryTable = headerBrand ? headerBrand.querySelector('table[x-show*="collection"]') : null;
        if (headerBrand) {
            if (activeTabName === 'collection') {
                if (duesSummaryTable) duesSummaryTable.style.display = 'none';
                if (colSummaryTable) colSummaryTable.style.display = 'table';
            } else {
                if (colSummaryTable) colSummaryTable.style.display = 'none';
                if (duesSummaryTable) duesSummaryTable.style.display = 'table';
            }
        }

        // Measure row heights accurately under 200mm printable width with print-calibration
        printable.classList.add('measuring-print');
        // Screen-to-print typography calibration: subpixel text on screen measures ~1.07x print size
        const PRINT_CALIB = 0.935;
        const headerH = Math.ceil((headerBrand ? headerBrand.getBoundingClientRect().height : 95) * 0.90);
        const theadH = Math.ceil((thead ? thead.getBoundingClientRect().height : 28) * 0.65);
        const tfootH = Math.ceil((tfoot ? tfoot.getBoundingClientRect().height : 0) * 0.70);
        const sigH = Math.ceil(((sigElem && sigElem.offsetHeight > 0) ? sigElem.getBoundingClientRect().height : 40) * 0.85);
        const rowHeights = rows.map(r => Math.ceil((r.getBoundingClientRect().height || r.offsetHeight || 30) * PRINT_CALIB));
        printable.classList.remove('measuring-print');

        // Printable height budget (A4: 289mm = 1092px; 1065px safely fills page to 97% without spilling)
        const PAGE_H = 1065;

        const OVERHEAD_P1 = headerH + theadH + 4;
        const OVERHEAD_PN = theadH + 4;
        const SIG_ALLOWANCE = tfootH + sigH + 18;

        // Partition rows across pages
        const pages = [];
        const totalRows = rows.length;

        if (totalRows === 0) {
            pages.push([]);
        } else {
            let i = 0;
            let isP1 = true;

            while (i < totalRows) {
                const overhead = isP1 ? OVERHEAD_P1 : OVERHEAD_PN;
                const capacity = PAGE_H - overhead;

                // Check remaining rows height from index i to end
                let remainingRowsH = 0;
                for (let j = i; j < totalRows; j++) {
                    remainingRowsH += rowHeights[j];
                }

                // If ALL remaining rows AND totals AND signatures fit on this page, finish on this page
                if (remainingRowsH + SIG_ALLOWANCE <= capacity) {
                    const curPageRows = [];
                    for (let j = i; j < totalRows; j++) {
                        curPageRows.push(rows[j]);
                    }
                    pages.push(curPageRows);
                    break;
                }

                // Not all remaining rows + signatures fit.
                // We pack rows onto this intermediate page tightly to avoid unnecessary blank space below the table.
                const curPageRows = [];
                let curH = 0;

                while (i < totalRows) {
                    const rH = rowHeights[i];
                    const rowsLeftAfterThis = totalRows - (i + 1);

                    // If adding this row exceeds intermediate capacity, break to next page
                    if (curH + rH > capacity && curPageRows.length > 0) {
                        break;
                    }

                    // Protect against 0 data rows on the final page:
                    // If this row would be the last row, but signatures won't fit here,
                    // keep at least 1 or 2 rows for the final page alongside Grand Total and Signatures.
                    if (rowsLeftAfterThis === 0 && curPageRows.length > 0) {
                        if (curPageRows.length >= 2) {
                            curPageRows.pop();
                            i--; // Rewind so both rows move to next page
                        }
                        break;
                    }

                    // If only 1 row would be left after this row and it wouldn't fit with signatures on final page:
                    // Only hold back 1 row if we have plenty of rows and are close to full capacity (>92%)
                    if (rowsLeftAfterThis === 1 && curPageRows.length >= 3 && (curH + rH + rowHeights[i + 1] + SIG_ALLOWANCE > capacity)) {
                        if (curH + rH > capacity * 0.92) {
                            break;
                        }
                    }

                    curPageRows.push(rows[i]);
                    curH += rH;
                    i++;
                }

                // Safety fallback to prevent infinite loop on abnormally tall single row
                if (curPageRows.length === 0 && i < totalRows) {
                    curPageRows.push(rows[i]);
                    i++;
                }

                pages.push(curPageRows);
                isP1 = false;
            }
        }

        // Build DOM structure for each printed page
        const numPages = pages.length;
        pages.forEach((pRows, pageIdx) => {
            const isFinalPage = (pageIdx === numPages - 1);
            const pageDiv = document.createElement('div');
            pageDiv.className = isFinalPage ? 'print-page print-page-final' : 'print-page';

            const contentDiv = document.createElement('div');
            contentDiv.className = 'print-page-content';

            // Top branding & meta filter card ONLY on page 1
            if (pageIdx === 0 && headerBrand) {
                const headerClone = headerBrand.cloneNode(true);
                headerClone.style.display = 'block';

                if (activeTabName === 'collection') {
                    const duesCard = headerClone.querySelector('table[x-show*="dues"]');
                    if (duesCard) duesCard.remove();
                    const colCard = headerClone.querySelector('table[x-show*="collection"]');
                    if (colCard) colCard.style.display = 'table';
                } else {
                    const colCard = headerClone.querySelector('table[x-show*="collection"]');
                    if (colCard) colCard.remove();
                    const duesCard = headerClone.querySelector('table[x-show*="dues"]');
                    if (duesCard) duesCard.style.display = 'table';
                }
                contentDiv.appendChild(headerClone);
            }

            // Page table with repeated thead
            const pageTable = document.createElement('table');
            pageTable.className = activeTable.className;

            if (thead) {
                pageTable.appendChild(thead.cloneNode(true));
            }

            const tbody = document.createElement('tbody');
            if (pRows.length === 0) {
                const emptyTr = document.createElement('tr');
                const emptyTd = document.createElement('td');
                emptyTd.colSpan = (activeTabName === 'collection' ? 6 : 7);
                emptyTd.style.cssText = 'text-align: center; padding: 20px; font-size: 11px; color: #64748B;';
                emptyTd.textContent = 'No records found for the selected period.';
                emptyTr.appendChild(emptyTd);
                tbody.appendChild(emptyTr);
            } else {
                pRows.forEach(r => tbody.appendChild(r.cloneNode(true)));
            }
            pageTable.appendChild(tbody);

            // Totals row ONLY on final page
            if (isFinalPage && tfoot) {
                pageTable.appendChild(tfoot.cloneNode(true));
            }

            contentDiv.appendChild(pageTable);
            pageDiv.appendChild(contentDiv);

            // Signatures ONLY on final page (Point 3: Exactly once, positioned near bottom, reduced gap)
            if (isFinalPage) {
                const footerDiv = document.createElement('div');
                footerDiv.className = 'print-page-footer';

                if (sigElem) {
                    const sigClone = sigElem.cloneNode(true);
                    sigClone.style.display = 'block';
                    footerDiv.appendChild(sigClone);
                }

                pageDiv.appendChild(footerDiv);
            }

            container.appendChild(pageDiv);
        });

        printable.classList.add('has-paginated-print');
        return true;
    }

    function resetPrintPagination() {
        const printable = document.getElementById('printableReportArea');
        if (printable) {
            printable.classList.remove('has-paginated-print');
        }
        const container = document.getElementById('printPagesContainer');
        if (container) {
            container.innerHTML = '';
        }
    }

    function prepareAndPrint() {
        isPaginating = true;
        try {
            buildPaginatedPrintPages();
        } catch (e) {
            console.error('buildPaginatedPrintPages failed:', e);
        }
        window.print();
        setTimeout(() => { isPaginating = false; }, 1500);
    }

    window.addEventListener('beforeprint', () => {
        if (!isPaginating) {
            try {
                buildPaginatedPrintPages();
            } catch (e) {
                console.error('beforeprint pagination error:', e);
            }
        }
    });

    window.addEventListener('afterprint', () => {
        resetPrintPagination();
        isPaginating = false;
    });
</script>
@endpush