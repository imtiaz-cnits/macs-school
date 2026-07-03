@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Collection')

@push('styles')
<style>
    select option { background: #ffffff; color: #1f2937; }
    .dark select option { background: #0f1e2c; color: #ffffff; }
    .table th { background-color: transparent !important; }
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18-1.5a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 7.5" />
                </svg>
                Fee Collection
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Search student and collect pending dues</p>
        </div>
        
        <form action="{{ route('fees.collection.index') }}" method="GET" class="w-full md:w-96 flex gap-3">
            <input type="text" name="student_identity" value="{{ request('student_identity') }}" placeholder="Enter Student ID (e.g. PIS-...)" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-mono uppercase text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" required>
            <button type="submit" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">Search</button>
        </form>
    </div>

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

    @if($student)
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Student Info Sidebar Panel -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 lg:col-span-1 h-fit shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-center mb-6">
                <div class="w-24 h-24 mx-auto bg-gray-50 dark:bg-themeDark rounded-full border-4 border-gray-100 dark:border-gray-800 shadow-md mb-3 flex items-center justify-center overflow-hidden">
                    <img src="https://ui-avatars.com/api/?name={{ $student->student_name }}&background=008ED6&color=fff" class="w-full h-full object-cover">
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white leading-tight">{{ $student->student_name }}</h3>
                <p class="text-themeBlue font-mono font-black text-sm mt-1.5 uppercase tracking-wider">{{ $student->student_identity }}</p>
            </div>
            
            <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-white/[0.05] text-xs">
                <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider">Class:</span> <span class="font-bold text-gray-800 dark:text-gray-200">{{ $student->schoolClass->class_name ?? 'N/A' }}</span></div>
                <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider">Section:</span> <span class="font-bold text-gray-800 dark:text-gray-200">{{ $student->section->section_name ?? 'N/A' }}</span></div>
                <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider">Roll No:</span> <span class="font-mono font-black text-gray-800 dark:text-gray-200">{{ $student->roll_number }}</span></div>
                <div class="flex justify-between items-center"><span class="font-bold text-gray-400 dark:text-gray-550 uppercase tracking-wider">Mobile:</span> <span class="font-bold text-gray-800 dark:text-gray-200">{{ $student->father_mobile }}</span></div>
            </div>
        </div>

        <!-- Dues & Payments Area -->
        <div class="lg:col-span-3 space-y-8">
            
            <!-- Pending Dues Table -->
            <form action="{{ route('fees.collection.bulk_store') }}" method="POST" id="bulkPaymentForm">
                @csrf
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden relative">
                    <div class="p-6 border-b border-gray-100 dark:border-white/[0.05]">
                        <h3 class="text-sm font-black text-red-650 dark:text-red-400 uppercase tracking-wider flex items-center">
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
                                        <div class="text-[10px] text-gray-400 dark:text-gray-500 font-mono mt-0.5">{{ $inv->invoice_no }}</div>
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
                                        <svg class="w-12 h-12 mx-auto mb-3 text-themeGreen opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        All Clear! No pending dues for this student.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Floating modern selected dues panel (Rule 1) -->
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
                                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            }" class="relative w-full sm:w-36 text-gray-900" @click.away="open = false">
                                <button type="button" @click="open = !open" class="w-full h-10 px-3 bg-white/20 border border-white/30 rounded-xl flex items-center justify-between text-xs font-black text-white focus:outline-none focus:ring-2 focus:ring-white transition-all text-left">
                                    <span class="truncate uppercase tracking-wider" x-text="label"></span>
                                    <svg class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <input type="hidden" name="payment_method" x-ref="hiddenInput" value="Cash">
                                <div x-show="open" x-cloak class="absolute bottom-full z-50 w-full mb-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    <template x-for="opt in ['Cash', 'bKash', 'Nagad', 'Bank']" :key="opt">
                                        <button type="button" @click="select(opt)" class="w-full flex items-center justify-between px-3 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == opt ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
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
                                            <div class="font-bold text-gray-950 dark:text-gray-100 text-sm flex items-center">
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
                                    <div class="text-xs text-gray-550 dark:text-gray-450 font-semibold mt-0.5">Via {{ $method }}</div>
                                </td>
                                
                                <td class="py-4 px-4 text-right font-black text-themeGreen dark:text-themeGreen text-lg font-mono">
                                    ৳ {{ number_format($totalPaid, 2) }}
                                </td>
                                
                                <td class="py-4 px-4 text-right">
                                    <a href="{{ route('fees.receipt.pos_print', $receiptNo) }}" target="_blank" class="h-9 px-4 bg-indigo-50 hover:bg-indigo-100 dark:bg-themeBlue/10 dark:hover:bg-themeBlue/20 text-themeBlue text-[10px] font-black rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95 inline-flex">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        Receipt
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">
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
            <h3 class="text-sm font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student not found matching ID: "{{ request('student_identity') }}"</h3>
        </div>
    @else
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl py-20 text-center shadow-sm">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <h3 class="text-sm font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Search for a student to view and collect dues</h3>
        </div>
    @endif
</div>

<!-- Collect Payment Single Modal (Rule 3) -->
<div id="paymentModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-xl border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Collect Payment</h3>
            <button onclick="closePayModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
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
                <input type="number" step="0.01" name="pay_amount" id="modalPayAmount" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-lg font-bold font-mono text-themeGreen dark:text-themeGreen px-3" required>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1.5">You can receive partial payment by changing this amount.</p>
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
                        inp.dispatchEvent(new Event('input', { bubbles: true }));
                        inp.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                    <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="label"></span>
                        <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" name="payment_method" x-ref="hiddenInput" value="Cash" required>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
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
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Trx ID / Note (Optional)</label>
                <input type="text" name="transaction_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-450" placeholder="e.g. 8N2K9DJ3">
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
    });

    // Custom confirm dialog system helper (Rule 8) for Single & Bulk payments
    document.getElementById('singlePaymentForm').onsubmit = async function(e) {
        e.preventDefault();
        const form = e.currentTarget;
        if (await showConfirm('Confirm Payment', 'Are you sure you want to record this single fee payment?')) {
            form.submit();
        }
    };

    document.getElementById('bulkPaymentForm').onsubmit = async function(e) {
        e.preventDefault();
        const form = e.currentTarget;
        if (await showConfirm('Confirm Bulk Payment', 'Are you sure you want to record payment for all selected dues?')) {
            form.submit();
        }
    };
</script>
@endpush