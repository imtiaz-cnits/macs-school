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

    <!-- Search System under title/tab area (Rule 2 & 4 compliance) -->
    @if(request('mode', 'single') === 'single')
        <div class="mb-8 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm no-print">
            <form action="{{ route('fees.collection.index') }}" method="GET" class="w-full flex flex-col sm:flex-row gap-3" @submit="window.dispatchEvent(new CustomEvent('trigger-loader'))">
                <input type="hidden" name="mode" value="single">
                <input type="text" name="student_identity" value="{{ request('student_identity') }}" placeholder="Enter Student ID (e.g. PIS-...)" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-mono uppercase text-gray-700 dark:text-gray-250 px-3 placeholder-gray-450" required>
                <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">Search Student</button>
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
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Student Info Sidebar Panel -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 lg:col-span-1 h-fit shadow-sm hover:shadow-md transition-all duration-300">
                <div class="text-center mb-6">
                    <div class="w-24 h-24 mx-auto bg-gray-55 dark:bg-themeDark rounded-full border-4 border-gray-100 dark:border-gray-800 shadow-md mb-3 flex items-center justify-center overflow-hidden">
                        <img src="https://ui-avatars.com/api/?name={{ $student->student_name }}&background=008ED6&color=fff" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white leading-tight">{{ $student->student_name }}</h3>
                    <p class="text-themeBlue font-mono font-black text-sm mt-1.5 uppercase tracking-wider">{{ $student->student_identity }}</p>
                </div>
                
                <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-white/[0.05] text-xs">
                    <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-555 uppercase tracking-wider">Class:</span> <span class="font-bold text-gray-800 dark:text-gray-200">{{ $student->schoolClass->class_name ?? 'N/A' }}</span></div>
                    <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-555 uppercase tracking-wider">Section:</span> <span class="font-bold text-gray-800 dark:text-gray-200">{{ $student->section->section_name ?? 'N/A' }}</span></div>
                    <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-555 uppercase tracking-wider">Roll No:</span> <span class="font-mono font-black text-gray-800 dark:text-gray-200">{{ $student->roll_number }}</span></div>
                    <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-555 uppercase tracking-wider">Mobile:</span> <span class="font-bold text-gray-800 dark:text-gray-200">{{ $student->father_mobile }}</span></div>
                </div>
            </div>

            <!-- Dues & Payments Area -->
            <div class="lg:col-span-3 space-y-8">
                
                <!-- Pending Dues Table -->
                <form action="{{ route('fees.collection.bulk_store') }}" method="POST" id="bulkPaymentForm">
                    @csrf
                    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden relative">
                        <div class="p-6 border-b border-gray-100 dark:border-white/[0.05]">
                            <h3 class="text-sm font-black text-red-655 dark:text-red-400 uppercase tracking-wider flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Pending Dues
                            </h3>
                        </div>
                        
                        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                            <table class="w-full text-left border-collapse table">
                                <thead>
                                    <tr class="!bg-transparent">
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-center w-12">
                                            <input type="checkbox" id="selectAllDues" class="w-4 h-4 text-themeGreen rounded border-gray-200 dark:border-gray-800 focus:ring-themeGreen cursor-pointer">
                                        </th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-2 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Fee Description</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Month</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Net Bill</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-[0.2em] text-right">Due Amt</th>
                                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-36">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse($invoices as $inv)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                                        <td class="py-4 px-4 text-center">
                                            <input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" data-amount="{{ $inv->due_amount }}" class="due-checkbox w-4 h-4 text-themeGreen rounded border-gray-200 dark:border-gray-800 focus:ring-themeGreen cursor-pointer">
                                        </td>
                                        <td class="py-4 px-2">
                                            <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $inv->feeSetup->category->name }}</div>
                                            <div class="text-[10px] text-gray-400 dark:text-gray-555 font-mono mt-0.5">{{ $inv->invoice_no }}</div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="px-2 py-0.5 bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-655 dark:text-gray-300 text-[9px] font-black uppercase tracking-wider rounded-lg inline-block">{{ $inv->feeSetup->fee_month ?? 'One Time' }}</span>
                                        </td>
                                        <td class="py-4 px-4 text-right text-sm font-semibold text-gray-600 dark:text-gray-400">{{ number_format($inv->net_amount, 2) }}</td>
                                        <td class="py-4 px-4 text-right font-black text-red-655 dark:text-red-400 text-lg font-mono">৳ {{ number_format($inv->due_amount, 2) }}</td>
                                        <td class="py-4 px-4 text-right">
                                            <button type="button" onclick="openPayModal({{ $inv->id }}, '{{ $inv->feeSetup->category->name }}', {{ $inv->due_amount }})" class="h-9 px-4 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-[10px] font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center active:scale-95">Pay Single</button>
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

                <!-- Payment History -->
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-white/[0.05]">
                        <h3 class="text-sm font-black text-themeGreen dark:text-themeGreen uppercase tracking-wider flex items-center">
                            <svg class="w-5 h-5 mr-2 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Payment History
                        </h3>
                    </div>
                    
                    <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                        <table class="w-full text-left border-collapse table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Paid Items</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Date & Method</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Total Paid</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-36">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($paymentHistory as $receiptNo => $payments)
                                @php
                                    $totalPaid = $payments->sum('paid_amount');
                                    $date = $payments->first()->payment_date;
                                    $method = $payments->first()->payment_method;
                                @endphp
                                
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                                    <td class="py-4 px-4">
                                        <div class="space-y-2 mb-2">
                                            @foreach($payments as $p)
                                                <div class="font-bold text-gray-955 dark:text-gray-100 text-sm flex items-center">
                                                    <svg class="w-4 h-4 text-themeGreen mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                                    {{ $p->invoice->feeSetup->category->name }} 
                                                    <span class="ml-1 px-2 py-0.5 bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-655 dark:text-gray-300 text-[9px] font-black uppercase tracking-wider rounded-lg inline-block">{{ $p->invoice->feeSetup->fee_month ?? 'One Time' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="text-[10px] text-gray-500 font-mono bg-gray-50 dark:bg-themeDark border border-gray-100 dark:border-white/[0.04] inline-block px-2 py-0.5 rounded-lg">{{ $receiptNo }}</div>
                                    </td>
                                    
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ date('d M, Y', strtotime($date)) }}</div>
                                        <div class="text-xs text-gray-550 dark:text-gray-455 font-semibold mt-0.5">Via {{ $method }}</div>
                                    </td>
                                    
                                    <td class="py-4 px-4 text-right font-black text-themeGreen dark:text-themeGreen text-lg font-mono">
                                        ৳ {{ number_format($totalPaid, 2) }}
                                    </td>
                                    
                                    <td class="py-4 px-4 text-right">
                                        <a href="{{ route('fees.receipt.pos_print', $receiptNo) }}" target="_blank" class="h-9 px-4 bg-indigo-55 hover:bg-indigo-100 dark:bg-themeBlue/10 dark:hover:bg-themeBlue/20 text-themeBlue text-[10px] font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95 inline-flex">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            Receipt
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-gray-555 font-bold uppercase tracking-wider">
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
        @elseif(request()->filled('student_identity'))
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl py-20 text-center shadow-sm">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <h3 class="text-sm font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student not found matching ID: "{{ request('student_identity') }}"</h3>
            </div>
        @else
            <!-- Directory of Outstanding Dues Students (instead of blank screen) -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden relative">
                <div class="p-6 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
                    <h3 class="text-sm font-black text-themeBlue uppercase tracking-wider flex items-center">
                        <svg class="w-5 h-5 mr-2 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Outstanding Dues Student Directory (বকেয়া ফি শিক্ষার্থী তালিকা)
                    </h3>
                    <div class="text-xs font-bold text-gray-550 bg-gray-50 dark:bg-themeDark px-4 py-2 rounded-xl border border-gray-100 dark:border-gray-800">
                        Total Due Students: <span class="text-themeBlue font-black">{{ $dueStudents->total() }}</span>
                    </div>
                </div>

                <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student ID</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student Name</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Class / Section</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-[0.2em] text-right">Total Outstanding</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-36">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($dueStudents as $ds)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                                <td class="py-4 px-4 font-mono font-black text-themeBlue text-sm uppercase">{{ $ds->student_identity }}</td>
                                <td class="py-4 px-4 font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $ds->student_name }}</td>
                                <td class="py-4 px-4 font-bold text-gray-655 dark:text-gray-400 text-sm">
                                    {{ $ds->schoolClass->class_name ?? 'N/A' }} 
                                    @if($ds->section)
                                        - <span class="text-xs text-gray-400">{{ $ds->section->section_name }}</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-right font-black text-red-655 dark:text-red-400 text-base font-mono">৳ {{ number_format($ds->total_due, 2) }}</td>
                                <td class="py-4 px-4 text-right">
                                    <a href="{{ route('fees.collection.index', ['mode' => 'single', 'student_identity' => $ds->student_identity]) }}" class="h-9 px-4 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-[10px] font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center active:scale-95 inline-flex whitespace-nowrap">Collect Dues</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-themeGreen dark:text-themeGreen font-black text-sm uppercase tracking-wider">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-themeGreen opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    All invoices paid! No students have outstanding dues.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($dueStudents->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.05] bg-gray-50/50 dark:bg-themeDark/30">
                    {{ $dueStudents->links() }}
                </div>
                @endif
            </div>
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

                    <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                        <table class="w-full text-left border-collapse table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-center w-12">
                                        <input type="checkbox" :checked="isAllSelected()" @click="toggleAll()" class="w-4 h-4 text-themeGreen rounded border-gray-200 dark:border-gray-800 focus:ring-themeGreen cursor-pointer">
                                    </th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-2 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student Details</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Invoice No</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-[0.2em] text-right">Due Amt</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-themeGreen dark:text-themeGreen uppercase tracking-[0.2em] text-right w-44">Paying Amt (৳)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($bulkInvoices as $inv)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                                    <td class="py-4 px-4 text-center">
                                        <input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" :checked="selectedInvoices.includes('{{ $inv->id }}')" @click="toggleInvoice('{{ $inv->id }}')" class="w-4 h-4 text-themeGreen rounded border-gray-200 dark:border-gray-800 focus:ring-themeGreen cursor-pointer">
                                    </td>
                                    <td class="py-4 px-2">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $inv->student->student_name }}</div>
                                        <div class="text-[10px] text-themeBlue font-mono font-black mt-0.5">{{ $inv->student->student_identity }}</div>
                                    </td>
                                    <td class="py-4 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400">
                                        <div>{{ $inv->invoice_no }}</div>
                                        <div class="text-[10px] text-gray-455 dark:text-gray-500 mt-0.5 uppercase">{{ $inv->feeSetup->fee_month ?? 'One Time' }}</div>
                                    </td>
                                    <td class="py-4 px-4 text-right font-black text-red-655 dark:text-red-400 text-base font-mono">৳ {{ number_format($inv->due_amount, 2) }}</td>
                                    <td class="py-4 px-4 text-right">
                                        <input type="number" step="0.01" name="paying_amounts[{{ $inv->id }}]" x-model.number="payingAmounts['{{ $inv->id }}']" @input="recalculate()" class="w-full h-9 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-55/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-bold font-mono text-themeGreen dark:text-themeGreen px-3 text-right">
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

                <!-- Bulk Collection Summary Card (Bottom Control Panel) -->
                @if($bulkInvoices->count() > 0)
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-12 text-gray-900 dark:text-white">
                    <h4 class="text-xs font-black text-themeBlue uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-6">Payment Summary & Method</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-555 block mb-1">Selected Outstanding</span>
                            <span class="text-xl font-black font-mono text-red-600 dark:text-red-400">৳ <span x-text="totalOutstanding.toFixed(2)">0.00</span></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-555 block mb-1">Total Collection Amount</span>
                            <span class="text-xl font-black font-mono text-themeGreen dark:text-themeGreen">৳ <span x-text="totalPaying.toFixed(2)">0.00</span></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-555 block mb-1">Selected Count</span>
                            <span class="text-xl font-black text-gray-850 dark:text-white"><span x-text="selectedInvoices.length">0</span> Student(s)</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 items-end">
                        <!-- Custom Alpine Dropdown for Bulk Payment Method -->
                        <div class="relative">
                            <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Payment Method <span class="text-red-500">*</span></label>
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
                                <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="label"></span>
                                    <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <input type="hidden" name="payment_method" x-ref="hiddenInput" value="Cash" required>
                                <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    <template x-for="opt in ['Cash', 'bKash', 'Nagad', 'Bank']" :key="opt">
                                        <button type="button" @click="select(opt)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="value == opt ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span x-text="opt"></span>
                                            <svg x-show="value == opt" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Transaction ID (Optional)</label>
                            <input type="text" name="transaction_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-450" placeholder="e.g. TRX-93821">
                        </div>

                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Note (Optional)</label>
                            <input type="text" name="note" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-450" placeholder="e.g. Monthly Fees Collection">
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-white/[0.06]">
                        <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95">
                            Collect Selected Fees
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
<div id="paymentModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-xl border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Collect Payment</h3>
            <button onclick="closePayModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <form action="{{ route('fees.collection.store') }}" method="POST" class="p-6 text-gray-900 dark:text-white" id="singlePaymentForm">
            @csrf
            <input type="hidden" name="invoice_id" id="modalInvoiceId">
            
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Fee Description</label>
                <div id="modalFeeName" class="text-sm font-bold text-gray-900 dark:text-white"></div>
            </div>

            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-1 block">Total Due Amount</label>
                <div class="text-2xl font-black text-red-600 dark:text-red-400 font-mono">৳ <span id="modalDueAmountText">0.00</span></div>
            </div>

            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Paying Amount (৳) <span class="text-red-500 ml-0.5">*</span></label>
                <input type="number" step="0.01" name="pay_amount" id="modalPayAmount" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-55/50 dark:bg-themeNavy focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-lg font-bold font-mono text-themeGreen dark:text-themeGreen px-3" required>
                <p class="text-[10px] text-gray-400 dark:text-gray-550 mt-1.5">You can receive partial payment by changing this amount.</p>
            </div>

            <!-- Custom Alpine Dropdown for Single Payment Method -->
            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Payment Method <span class="text-red-500">*</span></label>
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
                    <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="payment_method" x-ref="hiddenInput" value="Cash" required>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <template x-for="opt in ['Cash', 'bKash', 'Nagad', 'Bank']" :key="opt">
                            <button type="button" @click="select(opt)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-55 dark:hover:bg-themeDark/45 transition-colors" :class="value == opt ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span x-text="opt"></span>
                                <svg x-show="value == opt" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Trx ID / Note (Optional)</label>
                <input type="text" name="transaction_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-455" placeholder="e.g. 8N2K9DJ3">
            </div>

            <div class="mt-8 flex gap-3">
                <button type="button" onclick="closePayModal()" class="flex-1 h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                <button type="submit" class="flex-1 h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center active:scale-95">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openPayModal(invoiceId, feeName, dueAmount) {
        document.getElementById('modalInvoiceId').value = invoiceId;
        document.getElementById('modalFeeName').innerText = feeName;
        document.getElementById('modalDueAmountText').innerText = parseFloat(dueAmount).toFixed(2);
        
        document.getElementById('modalPayAmount').value = dueAmount;
        document.getElementById('modalPayAmount').max = dueAmount;

        const modal = document.getElementById('paymentModal');
        modal.classList.remove('hidden');
        modal.classList.add('modal-active');
    }

    function closePayModal() {
        const modal = document.getElementById('paymentModal');
        modal.classList.remove('modal-active');
        modal.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.due-checkbox');
        const selectAll = document.getElementById('selectAllDues');
        const bulkBar = document.getElementById('bulkPaymentBar');
        const bulkTotal = document.getElementById('bulkTotalDisplay');

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
            
            if(bulkBar) {
                if(checkedCount > 0) {
                    bulkBar.classList.remove('hidden');
                } else {
                    bulkBar.classList.add('hidden');
                }
            }
            
            if(selectAll) {
                selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
            }
        }

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
</script>
@endpush

@push('css')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush