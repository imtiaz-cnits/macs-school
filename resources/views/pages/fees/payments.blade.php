@extends('tyro-dashboard::layouts.admin')

@section('title', 'Payment Receipts')

@section('content')
<div id="paymentsIndexRoot" class="w-full min-h-screen" x-data="{ loading: false }" @trigger-loader.window="loading = true">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Payment Receipts
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">
                View, filter, and print student payment receipts
            </p>
        </div>
        
        <!-- Shortcut Action Buttons at Header Top-Right -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('fees.collection.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Collect Fees
            </a>
            <a href="{{ route('fees.reports.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Reports
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="mb-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm no-print">
        <form action="{{ route('fees.payments.index') }}" method="GET" class="space-y-4" @submit="window.dispatchEvent(new CustomEvent('trigger-loader'))">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                
                <!-- Branch Filter -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 uppercase mb-2">Branch</label>
                    <select name="branch_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->branch_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Class Filter -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 uppercase mb-2">Class</label>
                    <select name="class_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                        <option value="">All Classes</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 uppercase mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 uppercase mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-450 uppercase mb-2">Payment Method</label>
                    <select name="payment_method" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                        <option value="">All Methods</option>
                        <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>Cash</option>
                        <option value="BANK" {{ request('payment_method') === 'BANK' ? 'selected' : '' }}>Bank</option>
                        <option value="BKASH" {{ request('payment_method') === 'BKASH' ? 'selected' : '' }}>bKash</option>
                        <option value="NAGAD" {{ request('payment_method') === 'NAGAD' ? 'selected' : '' }}>Nagad</option>
                        <option value="ROCKET" {{ request('payment_method') === 'ROCKET' ? 'selected' : '' }}>Rocket</option>
                    </select>
                </div>

            </div>

            <!-- Search Query & Actions -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <input type="text" name="search_query" value="{{ request('search_query') }}" placeholder="Search Student ID, Name, or Receipt No (e.g. REC-...)" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm text-gray-700 dark:text-gray-250 px-3 placeholder-gray-450">
                <div class="flex gap-2">
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">Filter Receipts</button>
                    <a href="{{ route('fees.payments.index') }}" class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-500 dark:text-gray-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap" @click="window.dispatchEvent(new CustomEvent('trigger-loader'))">Reset</a>
                </div>
            </div>

        </form>
    </div>

    @if(session('success')) <div class="bg-green-50 dark:bg-themeGreen/10 text-themeGreen p-4 rounded-2xl mb-6 font-bold border border-green-200/30">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 p-4 rounded-2xl mb-6 font-bold border border-red-200/30">{{ session('error') }}</div> @endif

    <!-- Main Listing Panel (Hidden when loading) -->
    <div x-show="!loading" x-data="{
        selectedReceipts: [],
        toggleAll(checked) {
            if (checked) {
                this.selectedReceipts = Array.from(document.querySelectorAll('.receipt-checkbox')).map(el => el.value);
            } else {
                this.selectedReceipts = [];
            }
        }
    }">
        <form action="{{ route('fees.payments.print_selected') }}" method="POST" target="_blank" class="w-full">
            @csrf
            
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm overflow-hidden mb-6">
                @if($payments->isEmpty())
                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 font-bold">
                        No receipts found matching the filter options.
                    </div>
                @else
                    <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0">
                        <table class="w-full text-left border-collapse table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 w-12 text-center">
                                        <input type="checkbox" @change="toggleAll($el.checked)" class="rounded border-gray-300 text-themeBlue focus:ring-themeBlue/20 h-4 w-4">
                                    </th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Receipt No</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Class & Roll</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Description</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Month</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Paid</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Method</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $pay)
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0 text-center">
                                            <input type="checkbox" name="payment_ids[]" value="{{ $pay->id }}" x-model="selectedReceipts" class="receipt-checkbox rounded border-gray-300 text-themeBlue focus:ring-themeBlue/20 h-4 w-4">
                                        </td>
                                        <td class="py-0 px-0 font-mono font-black text-gray-700 dark:text-gray-300 text-xs">{{ $pay->receipt_no }}</td>
                                        <td class="py-0 px-0">
                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $pay->student->student_name ?? 'N/A' }}</div>
                                            <div class="text-[10px] font-semibold text-gray-450 mt-0.5">{{ $pay->student->student_identity ?? 'N/A' }}</div>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400">
                                            {{ $pay->student->schoolClass->class_name ?? 'N/A' }}
                                            <div class="text-[10px] font-semibold text-gray-450 mt-0.5">Roll: {{ $pay->student->roll_number ?? 'N/A' }}</div>
                                        </td>
                                        <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400">
                                            {{ $pay->invoice->feeSetup->category->name ?? 'Fee Payment' }}
                                        </td>
                                        @php
                                            $monthName = 'One Time';
                                            if ($pay->invoice && $pay->invoice->feeSetup) {
                                                if ($pay->invoice->feeSetup->fee_month && strtolower($pay->invoice->feeSetup->fee_month) !== 'monthly') {
                                                    $monthName = $pay->invoice->feeSetup->fee_month;
                                                } elseif ($pay->invoice->due_date) {
                                                    $monthName = date('F', strtotime($pay->invoice->due_date));
                                                } else {
                                                    $monthName = date('F', strtotime($pay->invoice->created_at));
                                                }
                                            }
                                        @endphp
                                        <td class="py-0 px-0 text-sm font-semibold text-gray-600 dark:text-gray-400">
                                            {{ $monthName }}
                                        </td>
                                        <td class="py-0 px-0 text-sm font-mono font-black text-right text-green-700 dark:text-themeGreen">৳{{ number_format($pay->paid_amount, 2) }}</td>
                                        <td class="py-0 px-0 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ date('d-M-Y', strtotime($pay->payment_date)) }}</td>
                                        <td class="py-0 px-0">
                                            <span class="px-2 py-0.5 text-[10px] font-black rounded-md border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-themeDark/45">{{ $pay->payment_method }}</span>
                                        </td>
                                        <td class="py-0 px-0">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('fees.invoice.pos_print', $pay->fee_invoice_id) }}" target="_blank" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue flex items-center justify-center h-8 w-8 border border-gray-150 dark:border-white/[0.08] rounded-lg transition-all" title="Print Receipt">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Custom pagination formatting -->
            @if($payments->isNotEmpty())
                <div class="mt-4 no-print flex justify-center">
                    {{ $payments->links('pagination::tailwind') }}
                </div>
            @endif

            <!-- Floating Checkout / Batch Action Bar -->
            <div x-show="selectedReceipts.length > 0"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-y-10 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-y-0 opacity-100"
                 x-transition:leave-end="translate-y-10 opacity-0"
                 class="sticky bottom-4 w-full bg-white dark:bg-themeNavy border-2 border-themeBlue/30 rounded-3xl p-4 shadow-2xl flex flex-col sm:flex-row justify-between items-center gap-4 z-50 transition-all mt-6 no-print">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-themeBlue/10 rounded-2xl flex items-center justify-center text-themeBlue">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-gray-900 dark:text-white">Batch Action Selected</h4>
                        <p class="text-xs font-semibold text-gray-500 mt-0.5"><span x-text="selectedReceipts.length" class="text-themeBlue font-black"></span> receipt(s) selected for printing.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print Selected
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Skeleton Loader (Pulsing placeholder rows) -->
    <div x-show="loading" class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm no-print">
        <div class="animate-pulse space-y-6">
            <div class="h-4 w-48 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
            <div class="border-b border-gray-100 dark:border-white/[0.06] pb-4"></div>
            <div class="space-y-4">
                @for($i = 0; $i < 6; $i++)
                    <div class="flex items-center justify-between gap-4">
                        <div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-40 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                    </div>
                    <div class="border-b border-gray-50 dark:border-white/[0.02] pb-2"></div>
                @endfor
            </div>
        </div>
    </div>

</div>

<!-- Custom styles override for borderless tighter table rows -->
<style>
    .table th, .table td { 
        padding: 0.625rem 1rem !important; 
    }
</style>

<script>
    // Global pagination link listener to show loader on page shift
    document.addEventListener('DOMContentLoaded', () => {
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('trigger-loader'));
            });
        });
    });
</script>
@endsection
