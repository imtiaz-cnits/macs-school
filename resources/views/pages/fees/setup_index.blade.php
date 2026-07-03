@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Setup Management')

@push('styles')
<style>
    select option { background: #ffffff; color: #1f2937; }
    .dark select option { background: #0f1e2c; color: #ffffff; }
    .table th { background-color: transparent !important; }

    /* Print & PDF CSS - Optimized for A4 */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden; }
        #printableTableArea, #printableTableArea * { visibility: visible; }
        #printableTableArea { position: absolute; left: 0; top: 0; width: 100%; padding: 0; background: white; box-shadow: none; border: none; }
        .no-print { display: none !important; }
        
        .print-header { display: block !important; text-align: center; margin-bottom: 15px; font-weight: bold; font-size: 20px; color: black; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        
        th, td { 
            border: 1px solid #333 !important; 
            padding: 4px 6px !important; 
            color: black !important; 
            font-size: 11px !important; 
            line-height: 1.2 !important;
        }
        
        .text-lg, .text-sm, .text-xs { font-size: 11px !important; }
        .bg-blue-50, .bg-red-50 { background: transparent !important; }
        th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div class="w-full">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Class-wise Fee Setup
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Assign fee amounts to specific classes and sessions</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success')) 
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-950/20 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm no-print">
            {{ session('success') }}
        </div> 
    @endif
    @if(session('error')) 
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm no-print">
            {{ session('error') }}
        </div> 
    @endif
    @if($errors->any()) 
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm no-print">
            {{ $errors->first() }}
        </div> 
    @endif

    <!-- Form Panel Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-8 mb-8 relative no-print shadow-sm hover:shadow-md transition-all duration-300">
        <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider mb-6 border-b border-gray-100 dark:border-white/[0.05] pb-3">Assign New Fee</h3>
        
        <form action="{{ route('fees.setup.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Branch <span class="text-red-500 ml-0.5">*</span></label>
                    <div x-data="{ 
                        open: false, 
                        value: '', 
                        label: 'Select Branch',
                        items: [
                            @foreach($branches as $b)
                                { value: '{{ $b->id }}', label: '{{ $b->branch_name }}' },
                            @endforeach
                        ],
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                            let inp = this.$refs.hiddenInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-450 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="branch_id" x-ref="hiddenInput" value="" required>
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Session <span class="text-red-500 ml-0.5">*</span></label>
                    <div x-data="{ 
                        open: false, 
                        value: '', 
                        label: 'Select Session',
                        items: [
                            @foreach($sessions as $s)
                                { value: '{{ $s->id }}', label: '{{ $s->session_name }}' },
                            @endforeach
                        ],
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                            let inp = this.$refs.hiddenInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-450 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="session_year_id" x-ref="hiddenInput" value="" required>
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Class <span class="text-red-500 ml-0.5">*</span></label>
                    <div x-data="{ 
                        open: false, 
                        value: '', 
                        label: 'Select Class',
                        items: [
                            @foreach($classes as $c)
                                { value: '{{ $c->id }}', label: '{{ $c->class_name }}' },
                            @endforeach
                        ],
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
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
                        <input type="hidden" name="class_id" x-ref="hiddenInput" value="" required>
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Fee Category <span class="text-red-500 ml-0.5">*</span></label>
                    <div x-data="{ 
                        open: false, 
                        value: '', 
                        label: 'Select Category',
                        items: [
                            @foreach($categories as $cat)
                                { value: '{{ $cat->id }}', label: '{{ $cat->name }}' },
                            @endforeach
                        ],
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
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
                        <input type="hidden" name="fee_category_id" x-ref="hiddenInput" value="" required>
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Fee Month (Optional)</label>
                    <div x-data="{ 
                        open: false, 
                        value: '', 
                        label: '-- One Time / Yearly Fee --',
                        items: [
                            { value: '', label: '-- One Time / Yearly Fee --' },
                            @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                                { value: '{{ $month }}', label: '{{ $month }}' },
                            @endforeach
                        ],
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
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
                        <input type="hidden" name="fee_month" x-ref="hiddenInput" value="">
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value === item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Amount (৳) <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="number" step="0.01" name="amount" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-mono font-bold text-red-500 dark:text-red-400 px-3 placeholder-gray-400" placeholder="0.00" required>
                </div>

                <div class="md:col-span-3 lg:col-span-2 flex items-end">
                    <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Set Fee Amount
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Logs List Card -->
    <div id="printableTableArea" class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        
        <div class="print-header hidden">
            School Fee Setup Details
            <p style="font-size: 12px; font-weight: normal; margin-top: 5px;">Printed on: {{ date('d M Y') }}</p>
        </div>

        <div class="p-6 border-b border-gray-100 dark:border-white/[0.05] flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/30 dark:bg-themeDark/30">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Current Fee Assignments</h3>
            
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto no-print">
                <div class="relative flex-1 sm:w-64">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="searchTableInput" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 pl-10 pr-3 placeholder-gray-400" placeholder="Search Branch, Class, Fee...">
                </div>
                
                <button onclick="window.print()" class="inline-flex items-center justify-center bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-black rounded-xl px-4 h-11 uppercase tracking-widest transition-all hover:-translate-y-0.5 active:scale-95 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print / PDF
                </button>
            </div>
        </div>

        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Branch</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Session</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Class</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Fee Name</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Month</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Amount (৳)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right no-print w-36">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="feeSetupTableBody">
                    @forelse($setups as $setup)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4 font-bold text-gray-600 dark:text-gray-450 text-sm">{{ $setup->branch->branch_name ?? 'N/A' }}</td>
                        <td class="py-4 px-4 font-bold text-gray-600 dark:text-gray-450 text-sm">{{ $setup->sessionYear->session_name ?? 'N/A' }}</td>
                        <td class="py-4 px-4 font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $setup->schoolClass->class_name ?? 'N/A' }}</td>
                        <td class="py-4 px-4 font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $setup->category->name }}</td>
                        <td class="py-4 px-4">
                            @if($setup->fee_month)
                                <span class="bg-blue-50 dark:bg-blue-950/20 text-themeBlue dark:text-blue-400 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block px-3 py-1">{{ strtoupper($setup->fee_month) }}</span>
                            @else
                                <span class="text-gray-400 italic text-sm font-semibold">One Time</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right font-black text-themeRed dark:text-red-400 text-sm font-mono">
                            {{ number_format($setup->amount, 2) }}
                        </td>
                        <td class="py-4 px-4 text-right no-print">
                            <div class="flex items-center justify-end">
                                <form action="{{ route('fees.setup.destroy', $setup->id) }}" method="POST" class="inline" onsubmit="return confirmDelete(event);">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn text-red-650 hover:text-red-800 hover:border-red-600" title="Remove Assignment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="7" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">No fee setups found. Assign a fee to a class above!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    // Live Search Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchTableInput');
        const tableBody = document.getElementById('feeSetupTableBody');
        const rows = tableBody.querySelectorAll('tr:not(#emptyRow)');

        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = searchInput.value.toLowerCase();

                rows.forEach(row => {
                    let rowText = row.innerText.toLowerCase();
                    if(rowText.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });

    async function confirmDelete(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (await showDanger('Remove Fee Assignment', 'Are you sure you want to remove this class fee setup? This might affect student billing logs.')) {
            form.submit();
        }
    }
</script>
@endpush