@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Reports')

@push('styles')
<style>
    select option { background: #ffffff; color: #1f2937; }
    .dark select option { background: #0f1e2c; color: #ffffff; }
    .table th { background-color: transparent !important; }
    
    /* Tab functionality anims */
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.25s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="{ activeTab: 'collection' }">
    
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                </svg>
                Collection & Due Report
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Daily financial overview and student balances</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 mb-8 relative z-20 no-print shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('fees.reports.index') }}" method="GET" id="reportFilterForm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <!-- From Date Picker -->
                <div class="relative" x-data="datePicker('{{ $startDate }}')" @click.away="show = false">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">From Date</label>
                    <input type="hidden" name="start_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute left-0 z-55 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between mb-2">
                            <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-555" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider" x-text="monthNames[currentMonth] + ' ' + currentYear"></span>
                            <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-555" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
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
                    </div>
                </div>

                <!-- To Date Picker -->
                <div class="relative" x-data="datePicker('{{ $endDate }}')" @click.away="show = false">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">To Date</label>
                    <input type="hidden" name="end_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute left-0 z-55 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between mb-2">
                            <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-555" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider" x-text="monthNames[currentMonth] + ' ' + currentYear"></span>
                            <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-555" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
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
                    </div>
                </div>

                <!-- Class Filter Dropdown -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Filter by Class</label>
                    <div x-data="{ 
                        open: false, 
                        value: '{{ $classId }}', 
                        label: '{{ $classId ? ($classes->firstWhere('id', $classId)->class_name ?? 'All Classes') : 'All Classes' }}',
                        items: [
                            { value: '', label: 'All Classes' },
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
                        <input type="hidden" name="class_id" x-ref="hiddenInput" :value="value">
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

                <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center whitespace-nowrap active:scale-95">Generate</button>
            </div>
        </form>
    </div>

    <!-- Stats Dashboard Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total Collection Card -->
        <div class="bg-gradient-to-br from-themeGreen to-green-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden">
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-green-100 font-bold uppercase tracking-wider text-[10px] mb-1">Total Collection</p>
            <h3 class="text-3xl font-black font-mono">৳ {{ number_format($totalCollected, 2) }}</h3>
            <p class="text-[10px] text-green-200 mt-2 font-semibold">{{ date('d M Y', strtotime($startDate)) }} to {{ date('d M Y', strtotime($endDate)) }}</p>
        </div>

        <!-- Total Pending Dues Card -->
        <div class="bg-gradient-to-br from-red-600 to-red-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden">
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-red-100 font-bold uppercase tracking-wider text-[10px] mb-1">Total Pending Dues</p>
            <h3 class="text-3xl font-black font-mono">৳ {{ number_format($totalDue, 2) }}</h3>
            <p class="text-[10px] text-red-200 mt-2 font-semibold">Overall pending balance</p>
        </div>

        <!-- Breakdown breakdown Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 lg:col-span-2 flex flex-col justify-between">
            <h4 class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-3.5">Collection Breakdown</h4>
            <div class="flex flex-wrap gap-4 items-center justify-start w-full">
                @forelse($methodBreakdown as $method => $amount)
                <div class="flex-1 min-w-[120px] bg-gray-55 dark:bg-themeDark/45 p-3 rounded-2xl border border-gray-100 dark:border-white/[0.04] transition-all">
                    <p class="text-[9px] font-black text-gray-450 dark:text-gray-500 uppercase tracking-wider mb-1">{{ $method }}</p>
                    <h4 class="text-sm font-black text-themeBlue dark:text-themeBlue font-mono">৳ {{ number_format($amount, 2) }}</h4>
                </div>
                @empty
                <p class="text-xs text-gray-450 dark:text-gray-500 font-bold uppercase tracking-wider w-full text-center py-2">No collections in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Segmented Tab switcher under-bar -->
    <div class="flex gap-2 p-1.5 bg-gray-55 dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-2xl w-fit mb-6 no-print">
        <button @click="activeTab = 'collection'" class="h-10 px-5 text-xs rounded-xl transition-all uppercase tracking-wider flex items-center justify-center gap-2" :class="activeTab === 'collection' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : 'text-gray-500 dark:text-gray-450 font-bold hover:text-gray-900 dark:hover:text-white'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Collection History
        </button>
        <button @click="activeTab = 'dues'" class="h-10 px-5 text-xs rounded-xl transition-all uppercase tracking-wider flex items-center justify-center gap-2" :class="activeTab === 'dues' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black shadow-sm' : 'text-gray-500 dark:text-gray-450 font-bold hover:text-gray-900 dark:hover:text-white'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Defaulters List (Dues)
        </button>
    </div>

    <!-- Data Tables Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        
        <!-- Collection History Tab -->
        <div id="tab-collection" class="tab-content" :class="{ 'active': activeTab === 'collection' }">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Receipt & Date</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student Info</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Fee Details</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center">Method</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($payments as $pay)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-4 px-4">
                                <div class="font-bold text-themeBlue dark:text-themeBlue text-sm">{{ $pay->receipt_no }}</div>
                                <div class="text-[10px] text-gray-500 font-mono mt-0.5">{{ date('d M Y, h:i A', strtotime($pay->created_at)) }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $pay->student->student_name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-gray-555 dark:text-gray-450 mt-0.5">{{ $pay->student->student_identity ?? 'N/A' }} <span class="mx-1">•</span> Class: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $pay->student->schoolClass->class_name ?? 'N/A' }}</span></div>
                            </td>
                            <td class="py-4 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ $pay->invoice->feeSetup->category->name ?? 'Fee' }}
                                <span class="px-2 py-0.5 bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-655 dark:text-gray-300 text-[9px] font-black uppercase tracking-wider rounded-lg inline-block mt-1">{{ $pay->invoice->feeSetup->fee_month ?? '' }}</span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-650 dark:text-gray-300 px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider inline-block">{{ $pay->payment_method }}</span>
                            </td>
                            <td class="py-4 px-4 text-right font-black text-themeGreen dark:text-themeGreen text-lg font-mono">
                                ৳ {{ number_format($pay->paid_amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">No collections found in this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Defaulters List Tab -->
        <div id="tab-dues" class="tab-content" :class="{ 'active': activeTab === 'dues' }">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Student Info</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Fee Description</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Due Date</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-[0.2em] text-right">Due Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($dues as $due)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-4 px-4">
                                <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $due->student->student_name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-gray-555 dark:text-gray-450 mt-0.5">{{ $due->student->student_identity ?? 'N/A' }} <span class="mx-1">•</span> Class: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $due->student->schoolClass->class_name ?? 'N/A' }}</span></div>
                            </td>
                            <td class="py-4 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <div class="font-bold text-gray-900 dark:text-gray-100">{{ $due->feeSetup->category->name ?? 'Fee' }}</div>
                                <span class="px-2 py-0.5 bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-655 dark:text-gray-300 text-[9px] font-black uppercase tracking-wider rounded-lg inline-block mt-1">{{ $due->feeSetup->fee_month ?? 'One Time' }}</span>
                            </td>
                            <td class="py-4 px-4 text-right text-sm font-bold text-gray-600 dark:text-gray-450">
                                {{ date('d M Y', strtotime($due->due_date)) }}
                            </td>
                            <td class="py-4 px-4 text-right font-black text-red-655 dark:text-red-400 text-lg font-mono">
                                ৳ {{ number_format($due->due_amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="py-12 text-center text-themeGreen dark:text-themeGreen font-black text-sm uppercase tracking-wider">Awesome! No pending dues found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    // Custom Date Picker component data definition (Rule 10)
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue || new Date().toISOString().split('T')[0],
            currentYear: new Date(initialValue || new Date()).getFullYear(),
            currentMonth: new Date(initialValue || new Date()).getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            
            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        const d = new Date(val);
                        this.currentYear = d.getFullYear();
                        this.currentMonth = d.getMonth();
                        this.generateCalendar();
                    }
                });
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
            
            formatDisplay(val) {
                if (!val) return 'Select Date';
                const d = new Date(val);
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        }
    }
</script>
@endpush