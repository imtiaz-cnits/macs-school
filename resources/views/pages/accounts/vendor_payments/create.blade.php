@extends('tyro-dashboard::layouts.admin')

@section('title', 'Record Vendor Payment')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="vendorPaymentForm({{ $preselectedVendorId ? $preselectedVendorId : 'null' }})">

    <!-- Page Header (Rule 4) -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Record Vendor Payment
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-450 mt-1">
                Settle outstanding vendor bills and opening liabilities with verified payment methods
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('accounts.vendor-payments.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Payments
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-2xl flex items-center gap-3 text-red-700 dark:text-red-450 text-sm font-bold">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('accounts.vendor-payments.store') }}" enctype="multipart/form-data" @submit.prevent="submitForm($el)">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left 2 Cols: Vendor Selection & Bill Allocation Grid -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Step 1: Select Vendor Card -->
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm">
                    <h2 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <span class="w-6 h-6 rounded-lg bg-themeBlue/10 text-themeBlue text-xs font-black flex items-center justify-center">1</span>
                        Select Vendor & Review Outstanding Dues
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Vendor Custom Dropdown (Rule 7) -->
                        <div x-data="{
                            open: false,
                            selected: selectedVendorId,
                            selectedLabel: '{{ $preselectedVendorId && $vendors->find($preselectedVendorId) ? addslashes($vendors->find($preselectedVendorId)->name) : 'Choose Vendor / Payee' }}',
                            items: [
                                @foreach($vendors as $v)
                                { id: '{{ $v->id }}', name: '{{ addslashes($v->name) }}', company: '{{ addslashes($v->company_name ?? '') }}' },
                                @endforeach
                            ],
                            select(item) {
                                this.selected = item.id;
                                this.selectedLabel = item.name;
                                this.open = false;
                                $data.onVendorChange(item.id);
                            }
                        }" class="relative">
                            <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">
                                Vendor / Payee <span class="text-red-500">*</span>
                            </label>
                            <input type="hidden" name="vendor_id" :value="selected">
                            <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                <span class="truncate" x-text="selectedLabel"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                <template x-for="item in items" :key="item.id">
                                    <button type="button" @click="select(item)" :class="{'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black': selected === item.id}" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-700 dark:text-gray-300">
                                        <div>
                                            <span class="font-bold block" x-text="item.name"></span>
                                            <span x-show="item.company" class="text-[10px] text-gray-400 block" x-text="item.company"></span>
                                        </div>
                                        <svg x-show="selected === item.id" class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Vendor Quick Info / Outstanding Total Badge -->
                        <div class="flex items-center">
                            <div x-show="selectedVendorId" class="w-full p-3.5 rounded-xl border border-gray-100 dark:border-white/[0.06] bg-gray-50/50 dark:bg-themeDark/40 flex items-center justify-between" x-cloak>
                                <div>
                                    <span class="text-[10px] font-black uppercase text-gray-400 block">Total Outstanding Balance</span>
                                    <span class="text-xl font-black font-mono text-rose-600 dark:text-rose-400" x-text="'৳ ' + Number(vendorTotalDue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                </div>
                                <a :href="'{{ url('accounts/payables/ledger') }}/' + selectedVendorId" target="_blank" class="text-xs font-bold text-themeBlue hover:underline flex items-center gap-1">
                                    View Ledger
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                            <div x-show="!selectedVendorId" class="text-xs text-gray-400 italic">
                                Please choose a vendor above to load outstanding vouchers and opening balances.
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Step 2: Bill Allocation Table Card -->
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-themeGreen/10 text-themeGreen text-xs font-black flex items-center justify-center">2</span>
                                Allocate Payment to Outstanding Bills & Dues
                            </h2>
                            <p class="text-xs text-gray-400 mt-0.5">Specify how much of the payment settles each individual bill</p>
                        </div>
                        <div class="flex items-center gap-2" x-show="bills.length > 0">
                            <button type="button" @click="autoAllocate()" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-bold transition-colors">
                                Auto-Allocate by Date
                            </button>
                            <button type="button" @click="clearAllocations()" class="px-3 py-1.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 text-gray-500 rounded-lg text-xs font-bold transition-colors">
                                Reset
                            </button>
                        </div>
                    </div>

                    <!-- Loading State Skeleton (Rule 9) -->
                    <div x-show="loadingBills" class="py-12 flex flex-col items-center justify-center" x-cloak>
                        <svg class="animate-spin h-8 w-8 text-themeBlue mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-xs font-bold text-gray-400">Loading vendor bills and dues...</span>
                    </div>

                    <!-- No Bills State -->
                    <div x-show="!loadingBills && selectedVendorId && bills.length === 0" class="py-12 text-center text-gray-400" x-cloak>
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="font-bold text-sm text-gray-700 dark:text-gray-300">All accounts are settled!</p>
                        <p class="text-xs text-gray-400 mt-1">This vendor has no outstanding bills or opening dues requiring payment.</p>
                    </div>

                    <!-- Allocation Table (Rule 9 Borderless Standard) -->
                    <div x-show="!loadingBills && bills.length > 0" class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto" x-cloak>
                        <table class="w-full text-left border-collapse table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Item / Voucher</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-24">Date</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-24">Bill Amount</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-24">Due Balance</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-44">Allocate (৳)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/[0.04]">
                                <template x-for="(bill, index) in bills" :key="bill.type + '-' + bill.id">
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" :checked="bill.allocated > 0" @change="toggleBillRow(bill, $event.target.checked)" class="rounded text-themeBlue focus:ring-themeBlue/20">
                                                <div>
                                                    <span class="font-mono font-bold text-xs" :class="bill.type === 'opening_balance' ? 'text-purple-600' : 'text-themeBlue'" x-text="bill.ref_no"></span>
                                                    <span class="block text-[10px] text-gray-400" x-text="bill.title"></span>
                                                </div>
                                            </div>
                                            <input type="hidden" :name="'allocations[' + index + '][type]'" :value="bill.type">
                                            <input type="hidden" :name="'allocations[' + index + '][id]'" :value="bill.id">
                                        </td>
                                        <td class="py-0 px-0 text-xs text-gray-600 dark:text-gray-300" x-text="bill.date"></td>
                                        <td class="py-0 px-0 text-right font-mono text-xs text-gray-600 dark:text-gray-400" x-text="'৳ ' + Number(bill.amount).toFixed(2)"></td>
                                        <td class="py-0 px-0 text-right font-mono text-xs font-bold text-rose-600 dark:text-rose-400" x-text="'৳ ' + Number(bill.due).toFixed(2)"></td>
                                        <td class="py-0 px-0 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <input type="number" step="0.01" min="0" :max="bill.due" :name="'allocations[' + index + '][amount]'" x-model.number="bill.allocated" @input="onAllocationInput()" class="w-28 h-8 px-2 text-right bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-lg text-xs font-mono font-bold text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-themeBlue/20 focus:border-themeBlue">
                                                <button type="button" @click="bill.allocated = Number(bill.due); onAllocationInput();" class="text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 hover:bg-themeBlue hover:text-white transition-colors text-gray-600 dark:text-gray-300">
                                                    Full
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50/70 dark:bg-themeNavy/60 font-black text-xs border-t-2 border-gray-200 dark:border-gray-800">
                                    <td colspan="4" class="py-2 text-right uppercase tracking-wider text-gray-700 dark:text-gray-300">Total Allocated:</td>
                                    <td class="py-2 text-right font-mono text-sm font-black" :class="totalAllocated === paymentAmount && paymentAmount > 0 ? 'text-emerald-600' : 'text-themeBlue'" x-text="'৳ ' + Number(totalAllocated).toFixed(2)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>

            </div>

            <!-- Right 1 Col: Payment Transaction Details & Submission -->
            <div class="space-y-6">

                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm">
                    <h2 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                        <span class="w-6 h-6 rounded-lg bg-purple-500/10 text-purple-600 text-xs font-black flex items-center justify-center">3</span>
                        Payment Details
                    </h2>

                    <!-- Payment Date Picker (Rule 10) -->
                    <div class="relative mb-4" x-data="datePicker('{{ date('Y-m-d') }}')" @click.away="show = false; mOpen = false">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Payment Date <span class="text-red-500">*</span></label>
                        <input type="hidden" name="payment_date" :value="value">
                        <button type="button" @click="show = !show" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate font-mono" x-text="formatDisplay(value)"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>

                        <!-- Calendar Dropdown Panel (Rule 10) -->
                        <div x-show="show" x-cloak class="absolute left-0 sm:left-auto sm:right-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
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
                                        <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
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
                                            :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
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

                    <!-- Payment Method Dropdown (Rule 7) -->
                    <div x-data="{
                        open: false,
                        selected: 'Cash',
                        selectedLabel: 'Cash',
                        items: [
                            { id: 'Cash', name: 'Cash' },
                            { id: 'Bank', name: 'Bank' },
                            { id: 'bKash', name: 'bKash' },
                            { id: 'Nagad', name: 'Nagad' },
                            { id: 'Cheque', name: 'Cheque' },
                            { id: 'Rocket', name: 'Rocket' }
                        ],
                        select(item) {
                            this.selected = item.id;
                            this.selectedLabel = item.name;
                            this.open = false;
                        }
                    }" class="relative mb-4">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Payment Method <span class="text-red-500">*</span></label>
                        <input type="hidden" name="payment_method" :value="selected">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            <span class="truncate" x-text="selectedLabel"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                            <template x-for="item in items" :key="item.id">
                                <button type="button" @click="select(item)" :class="{'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black': selected === item.id}" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-700 dark:text-gray-300">
                                    <span x-text="item.name"></span>
                                    <svg x-show="selected === item.id" class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Payment Amount (Authoritative) -->
                    <div class="mb-4">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">
                            Payment Amount (৳) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" x-model.number="paymentAmount" @input="onPaymentAmountInput()" required class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-lg font-mono font-black text-gray-900 dark:text-white focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    </div>

                    <!-- Allocation Matching Indicator -->
                    <div class="mb-4 p-3 rounded-xl border text-xs font-semibold" :class="allocationMatchStatusClass">
                        <div class="flex items-center justify-between">
                            <span x-text="allocationMatchMessage"></span>
                            <span class="font-mono font-black" x-text="'Diff: ৳ ' + Number(Math.abs(paymentAmount - totalAllocated)).toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- Payment Reference / Trx # -->
                    <div class="mb-4">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Payment Ref / Cheque #</label>
                        <input type="text" name="reference_no" placeholder="Trx ID / Cheque # / Deposit Slip" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    </div>

                    <!-- Branch Dropdown (Rule 7) -->
                    <div x-data="{
                        open: false,
                        selected: '',
                        selectedLabel: 'Unassigned / Consolidated',
                        items: [
                            { id: '', name: 'Unassigned / Consolidated' },
                            @foreach($branches as $b)
                            { id: '{{ $b->id }}', name: '{{ addslashes($b->branch_name) }}' },
                            @endforeach
                        ],
                        select(item) {
                            this.selected = item.id;
                            this.selectedLabel = item.name;
                            this.open = false;
                        }
                    }" class="relative mb-4">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Branch</label>
                        <input type="hidden" name="branch_id" :value="selected">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            <span class="truncate" x-text="selectedLabel"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                            <template x-for="item in items" :key="item.id">
                                <button type="button" @click="select(item)" :class="{'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black': selected === item.id}" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-700 dark:text-gray-300">
                                    <span x-text="item.name"></span>
                                    <svg x-show="selected === item.id" class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Receipt Attachment -->
                    <div class="mb-4">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Receipt / Evidence Attachment</label>
                        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-100 dark:file:bg-gray-800 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-200">
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-450 uppercase mb-1.5">Payment Note</label>
                        <textarea name="note" rows="2" placeholder="Remarks or cheque details..." class="w-full p-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="!canSubmit" class="w-full h-12 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Confirm & Record Payment
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>

@push('scripts')
<script>
function vendorPaymentForm(initialVendorId) {
    return {
        selectedVendorId: initialVendorId || '',
        vendorTotalDue: 0,
        paymentAmount: 0,
        bills: [],
        loadingBills: false,

        init() {
            if (this.selectedVendorId) {
                this.loadVendorBills(this.selectedVendorId);
            }
        },

        onVendorChange(vendorId) {
            this.selectedVendorId = vendorId;
            this.paymentAmount = 0;
            this.bills = [];
            if (vendorId) {
                this.loadVendorBills(vendorId);
            }
        },

        async loadVendorBills(vendorId) {
            this.loadingBills = true;
            try {
                const res = await fetch(`{{ url('accounts/vendor-payments/bills-ajax') }}/${vendorId}`);
                const data = await res.json();
                if (data.success) {
                    this.vendorTotalDue = data.total_outstanding;
                    this.bills = data.items.map(item => ({
                        type: item.type,
                        id: item.id,
                        ref_no: item.ref_no,
                        title: item.title,
                        date: item.date,
                        amount: Number(item.amount),
                        paid: Number(item.paid),
                        due: Number(item.due),
                        allocated: 0
                    }));
                }
            } catch (e) {
                console.error('Failed to load bills:', e);
            } finally {
                this.loadingBills = false;
            }
        },

        get totalAllocated() {
            return this.bills.reduce((sum, b) => sum + (Number(b.allocated) || 0), 0);
        },

        get canSubmit() {
            return this.selectedVendorId &&
                   this.paymentAmount > 0 &&
                   Math.abs(this.paymentAmount - this.totalAllocated) < 0.001 &&
                   this.totalAllocated > 0;
        },

        get allocationMatchStatusClass() {
            if (this.paymentAmount <= 0) return 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-themeDark dark:border-gray-800';
            const diff = Math.abs(this.paymentAmount - this.totalAllocated);
            if (diff < 0.001) return 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-800';
            if (this.totalAllocated < this.paymentAmount) return 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-800';
            return 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-800';
        },

        get allocationMatchMessage() {
            if (this.paymentAmount <= 0) return 'Enter payment amount and allocate below.';
            const diff = this.paymentAmount - this.totalAllocated;
            if (Math.abs(diff) < 0.001) return 'Allocations match payment amount perfectly.';
            if (diff > 0) return `Unallocated payment: ৳ ${diff.toFixed(2)}`;
            return `Over-allocated by: ৳ ${Math.abs(diff).toFixed(2)}`;
        },

        toggleBillRow(bill, isChecked) {
            if (isChecked) {
                const remainingToAllocate = Math.max(0, this.paymentAmount - this.totalAllocated);
                bill.allocated = Number(Math.min(bill.due, remainingToAllocate > 0 ? remainingToAllocate : bill.due).toFixed(2));
            } else {
                bill.allocated = 0;
            }
            this.onAllocationInput();
        },

        onAllocationInput() {
            // If user has not set paymentAmount, sync it with totalAllocated
            if (this.paymentAmount === 0 || this.paymentAmount < this.totalAllocated) {
                this.paymentAmount = Number(this.totalAllocated.toFixed(2));
            }
        },

        onPaymentAmountInput() {
            // Auto allocate if single bill or if bills empty
            if (this.bills.length === 1 && this.paymentAmount > 0) {
                this.bills[0].allocated = Number(Math.min(this.bills[0].due, this.paymentAmount).toFixed(2));
            }
        },

        autoAllocate() {
            let remaining = this.paymentAmount > 0 ? this.paymentAmount : this.vendorTotalDue;
            if (this.paymentAmount === 0) {
                this.paymentAmount = remaining;
            }
            this.bills.forEach(b => {
                if (remaining > 0) {
                    const alloc = Math.min(b.due, remaining);
                    b.allocated = Number(alloc.toFixed(2));
                    remaining -= alloc;
                } else {
                    b.allocated = 0;
                }
            });
        },

        clearAllocations() {
            this.bills.forEach(b => b.allocated = 0);
        },

        async submitForm(form) {
            if (!this.canSubmit) {
                await showAlert('Payment amount must match total allocated bills exactly before submitting.', 'Validation Notice');
                return;
            }
            form.submit();
        }
    };
}

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
                this.currentYear = parseInt(parts[0]);
                this.currentMonth = parseInt(parts[1]) - 1;
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
            this.$dispatch('date-selected', this.value);
        },

        clearDate() {
            this.value = '';
            this.show = false;
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
            this.$dispatch('date-selected', this.value);
        },

        formatDisplay(val) {
            if (!val) return 'Select Date';
            const parts = val.split('-');
            if (parts.length === 3) {
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            }
            return val;
        }
    };
}
</script>
@endpush
@endsection
