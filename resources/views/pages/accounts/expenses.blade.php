@extends('tyro-dashboard::layouts.admin')

@section('title', 'Expenses Management')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="expenseManager()">

    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
                Expenses Management
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Track school disbursements, generate debit vouchers, and audit expenditure receipts
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('accounts.categories.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z"/></svg>
                Categories
            </a>

            <button type="button" @click="openAddModal()" class="h-11 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Record Expense
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-themeGreen/10 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-600 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-600 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Summary Cards (Rule 5) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Today's Expense -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Today's Expense</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-themeBlue/10 flex items-center justify-center text-themeBlue">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($todayTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                {{ date('d M Y') }}
            </div>
        </div>

        <!-- This Month's Expense -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">This Month's Expense</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-themeGreen/10 flex items-center justify-center text-themeGreen">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white">
                ৳ {{ number_format($thisMonthTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 mt-1">
                {{ date('F Y') }}
            </div>
        </div>

        <!-- Filtered Total -->
        <div class="bg-gradient-to-br from-themeBlue to-indigo-800 rounded-3xl p-5 shadow-sm text-white hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-100">Filtered Total</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black font-mono">
                ৳ {{ number_format($filteredTotal, 2) }}
            </div>
            <div class="text-[11px] font-semibold text-blue-100 mt-1">
                {{ $filteredCount }} record(s) matching filter
            </div>
        </div>

        <!-- Method Breakdown Pill -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2 block">Payment Breakdown</span>
            <div class="space-y-1.5 overflow-y-auto max-h-16 text-xs">
                @forelse($methodBreakdown as $m => $total)
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300 font-semibold">
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $m }}:</span>
                        <span class="font-mono font-bold">৳ {{ number_format($total, 2) }}</span>
                    </div>
                @empty
                    <div class="text-xs text-gray-400">No payment data</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filters Card (Rule 4) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm mb-8 no-print">
        <form action="{{ route('accounts.expenses.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

                <!-- Start Date -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Date From</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Date To</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Category</label>
                    <select name="expense_category_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Branch Filter -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Branch</label>
                    <select name="branch_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                                {{ $b->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Method Filter -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 ml-1">Method</label>
                    <select name="payment_method" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                        <option value="">All Methods</option>
                        @foreach(['Cash', 'Bank', 'bKash', 'Nagad', 'Cheque'] as $m)
                            <option value="{{ $m }}" {{ $paymentMethod == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Search Query & Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search Voucher No, Paid To, Ref No, or Notes..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-250 px-3.5 placeholder-gray-400">
                <div class="flex gap-2 shrink-0">
                    <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                        Filter Expenses
                    </button>
                    <a href="{{ route('accounts.expenses.index') }}" class="h-11 px-6 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-500 dark:text-gray-300 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 whitespace-nowrap">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Expenses Table Card (Rule 9) -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-8">

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Voucher No</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Category</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Paid To / Reference</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Method</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Amount</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">Receipt</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-36">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $exp)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <!-- Voucher No -->
                        <td class="py-4 px-4 font-mono font-black text-themeBlue text-xs whitespace-nowrap">
                            {{ $exp->voucher_no }}
                        </td>

                        <!-- Date -->
                        <td class="py-4 px-4 text-xs font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ date('d M Y', strtotime($exp->expense_date)) }}
                        </td>

                        <!-- Category -->
                        <td class="py-4 px-4">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 dark:bg-white/[0.05] text-gray-800 dark:text-gray-200">
                                {{ $exp->category->name ?? 'Uncategorized' }}
                            </span>
                        </td>

                        <!-- Paid To & Metadata -->
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $exp->paid_to }}</div>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-400 dark:text-gray-500 font-medium">
                                @if($exp->branch)
                                    <span>🏢 {{ $exp->branch->branch_name }}</span>
                                @endif
                                @if($exp->reference_no)
                                    <span>• Ref: {{ $exp->reference_no }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- Method -->
                        <td class="py-4 px-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block
                                @if($exp->payment_method === 'Cash') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400
                                @elseif($exp->payment_method === 'Bank') bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400
                                @elseif(in_array($exp->payment_method, ['bKash', 'Nagad'])) bg-pink-50 text-pink-700 dark:bg-pink-950/20 dark:text-pink-400
                                @else bg-purple-50 text-purple-700 dark:bg-purple-950/20 dark:text-purple-400
                                @endif">
                                {{ $exp->payment_method }}
                            </span>
                        </td>

                        <!-- Amount -->
                        <td class="py-4 px-4 font-mono font-black text-right text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                            ৳ {{ number_format($exp->amount, 2) }}
                        </td>

                        <!-- Receipt View Link (Authenticated Private Stream) -->
                        <td class="py-4 px-4 text-center">
                            @if($exp->hasReceipt())
                                <a href="{{ route('expenses.receipt.view', $exp->id) }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue hover:bg-themeBlue hover:text-white transition-all shadow-xs" title="View Secure Receipt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                </a>
                            @else
                                <span class="text-gray-300 dark:text-gray-600 font-mono text-sm">-</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Print Voucher Button -->
                                <a href="{{ route('accounts.expenses.voucher_print', $exp->id) }}" target="_blank" class="action-btn text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:border-gray-400" title="Print Debit Voucher">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                </a>

                                <!-- Edit Button -->
                                <button type="button" @click="openEditModal({{ json_encode($exp) }})" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Expense">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <!-- Delete / Void Button -->
                                <form action="{{ route('accounts.expenses.destroy', $exp->id) }}" method="POST" class="inline" onsubmit="return handleExpenseDelete(event, '{{ $exp->voucher_no }}', '৳ {{ number_format($exp->amount, 2) }}');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="delete_reason" value="Deleted via Expenses Directory">
                                    <button type="submit" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Expense">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider text-xs">
                            No expenses found matching the filter criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($expenses->hasPages())
            <div class="pt-6 border-t border-gray-100 dark:border-white/[0.05]">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <!-- Add / Edit Expense Modal (Alpine.js) -->
    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
        <!-- Backdrop Blur (Rule 3) -->
        <div class="fixed inset-0 bg-themeDark/60 backdrop-blur-md transition-opacity" @click="isModalOpen = false"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 overflow-hidden my-8" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/[0.05] pb-4 mb-6">
                    <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span x-text="isEditMode ? 'Edit Expense Voucher: ' + form.voucher_no : 'Record New Expense'"></span>
                    </h3>
                    <button type="button" @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form -->
                <form :action="isEditMode ? '{{ url('accounts/expenses') }}/' + form.id : '{{ route('accounts.expenses.store') }}'" method="POST" enctype="multipart/form-data">
                    @csrf
                    <template x-if="isEditMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <!-- Category -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Expense Category <span class="text-red-500">*</span>
                            </label>
                            <select name="expense_category_id" x-model="form.expense_category_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3" required>
                                <option value="">Select Category</option>
                                @foreach($activeCategories as $ac)
                                    <option value="{{ $ac->id }}">{{ $ac->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Branch -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Branch (Optional)
                            </label>
                            <select name="branch_id" x-model="form.branch_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3">
                                <option value="">Main Branch / General</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Expense Date -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Expense Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="expense_date" x-model="form.expense_date" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3" required>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Amount (BDT) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" name="amount" x-model="form.amount" placeholder="0.00" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold font-mono text-gray-700 dark:text-gray-200 px-3.5" required>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select name="payment_method" x-model="form.payment_method" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank">Bank</option>
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>

                        <!-- Paid To -->
                        <div>
                            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                                Paid To / Recipient <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="paid_to" x-model="form.paid_to" placeholder="Vendor / Person / Store Name" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5" required>
                        </div>
                    </div>

                    <!-- Reference No -->
                    <div class="mb-4">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Reference No / Cheque No / TrxID (Optional)
                        </label>
                        <input type="text" name="reference_no" x-model="form.reference_no" placeholder="e.g. Memo #4451 or Trx ID" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                    </div>

                    <!-- Receipt File Upload (Private storage) -->
                    <div class="mb-4">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Attach Receipt / Bill Memo (Max 5MB - JPG, PNG, WebP, PDF)
                        </label>
                        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-wider file:bg-gray-100 dark:file:bg-gray-800 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-200 cursor-pointer border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl p-2 bg-gray-50/50 dark:bg-themeDark">

                        <!-- Existing Receipt in Edit Mode -->
                        <template x-if="isEditMode && form.has_receipt">
                            <div class="mt-2.5 p-3 rounded-xl bg-gray-50 dark:bg-themeDark border border-gray-100 dark:border-white/[0.05] flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Existing receipt attached
                                </span>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remove_receipt" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    <span class="text-[11px] font-bold text-red-600">Remove Receipt</span>
                                </label>
                            </div>
                        </template>
                    </div>

                    <!-- Reason for Edit (Only in Edit Mode) -->
                    <template x-if="isEditMode">
                        <div class="mb-4">
                            <label class="text-[10px] font-black tracking-widest text-amber-600 dark:text-amber-400 uppercase mb-1.5 block">
                                Reason for Update (Audit Trail)
                            </label>
                            <input type="text" name="edit_reason" x-model="form.edit_reason" placeholder="e.g. Corrected recipient name or amount" class="w-full h-11 border-2 border-amber-200 dark:border-amber-900/50 rounded-xl bg-amber-50/30 dark:bg-amber-950/10 focus:outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 px-3.5">
                        </div>
                    </template>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Notes / Remarks (Optional)
                        </label>
                        <textarea name="note" x-model="form.note" rows="2" placeholder="Additional details or justification..." class="w-full min-h-[70px] border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-semibold text-gray-700 dark:text-gray-200 p-3 placeholder-gray-400"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-white/[0.05]">
                        <button type="button" @click="isModalOpen = false" class="h-11 px-5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeDark text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-wider hover:bg-gray-50 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="h-11 px-7 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest active:scale-95">
                            <span x-text="isEditMode ? 'Update Voucher' : 'Save Expense'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function expenseManager() {
        return {
            isModalOpen: false,
            isEditMode: false,
            form: {
                id: null,
                voucher_no: '',
                expense_category_id: '',
                branch_id: '',
                expense_date: '{{ date('Y-m-d') }}',
                amount: '',
                payment_method: 'Cash',
                paid_to: '',
                reference_no: '',
                note: '',
                edit_reason: '',
                has_receipt: false
            },
            openAddModal() {
                this.isEditMode = false;
                this.form = {
                    id: null,
                    voucher_no: '',
                    expense_category_id: '',
                    branch_id: '',
                    expense_date: '{{ date('Y-m-d') }}',
                    amount: '',
                    payment_method: 'Cash',
                    paid_to: '',
                    reference_no: '',
                    note: '',
                    edit_reason: '',
                    has_receipt: false
                };
                this.isModalOpen = true;
            },
            openEditModal(expense) {
                this.isEditMode = true;
                this.form = {
                    id: expense.id,
                    voucher_no: expense.voucher_no,
                    expense_category_id: expense.expense_category_id,
                    branch_id: expense.branch_id || '',
                    expense_date: expense.expense_date,
                    amount: expense.amount,
                    payment_method: expense.payment_method,
                    paid_to: expense.paid_to,
                    reference_no: expense.reference_no || '',
                    note: expense.note || '',
                    edit_reason: '',
                    has_receipt: Boolean(expense.receipt_file)
                };
                this.isModalOpen = true;
            }
        };
    }

    async function handleExpenseDelete(event, voucherNo, amount) {
        event.preventDefault();
        const form = event.currentTarget;

        const confirmed = await showDanger(
            'Delete Expense Voucher',
            `Are you sure you want to delete voucher ${voucherNo} for ${amount}? This will permanently remove the record and log an audit snapshot.`
        );

        if (confirmed) {
            form.submit();
        }
        return false;
    }
</script>
@endpush
