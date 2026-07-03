@extends('tyro-dashboard::layouts.admin')

@section('title', 'Category-wise Fee Summary')

@push('styles')
<style>
    select option { background: #ffffff; color: #1f2937; }
    .dark select option { background: #0f1e2c; color: #ffffff; }
    .table th { background-color: transparent !important; }
    
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body * { visibility: hidden; }
        #printableSummaryArea, #printableSummaryArea * { visibility: visible; }
        #printableSummaryArea { position: absolute; left: 0; top: 0; width: 100%; padding: 0; background: white; border: none; box-shadow: none; }
        #printableSummaryArea * { color: #000000 !important; }
        .no-print { display: none !important; }
        .print-header { display: block !important; text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; border: 1px solid #000 !important; margin-top: 10px; }
        th, td { border: 1px solid #000 !important; padding: 6px 8px !important; font-size: 13px !important; }
        th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .progress-bar-bg { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #999; }
        .progress-bar-fill { background: #009A49 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .filter-badges { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 15px; justify-content: flex-end; }
        .filter-badge { border: 1px solid #000 !important; padding: 6px 12px !important; border-radius: 6px !important; font-size: 14px !important; font-weight: bold !important; background: transparent !important; color: #000 !important; }
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Category-Wise Summary
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Analyze fee collection and dues based on specific criteria</p>
        </div>
        
        <button onclick="window.print()" class="inline-flex items-center justify-center bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-black rounded-xl px-4 h-11 uppercase tracking-widest transition-all hover:-translate-y-0.5 active:scale-95 shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Report
        </button>
    </div>

    <!-- Filter Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 mb-8 relative z-20 no-print shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('fees.reports.summary') }}" method="GET" id="summaryFilterForm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <!-- Branch Dropdown -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Branch</label>
                    <div x-data="{ 
                        open: false, 
                        value: '{{ $branchId }}', 
                        label: '{{ $branchId ? ($branches->firstWhere('id', $branchId)->branch_name ?? 'All Branches') : 'All Branches' }}',
                        items: [
                            { value: '', label: 'All Branches' },
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
                            <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="branch_id" x-ref="hiddenInput" :value="value">
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

                <!-- Session Dropdown -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Session</label>
                    <div x-data="{ 
                        open: false, 
                        value: '{{ $sessionId }}', 
                        label: '{{ $sessionId ? ($sessions->firstWhere('id', $sessionId)->session_name ?? 'All Sessions') : 'All Sessions' }}',
                        items: [
                            { value: '', label: 'All Sessions' },
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
                            <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="session_year_id" x-ref="hiddenInput" :value="value">
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

                <!-- Class Dropdown -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Class</label>
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

                <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center whitespace-nowrap active:scale-95">Filter Data</button>
            </div>
        </form>
    </div>

    <!-- Printable Area Wrapper -->
    <div id="printableSummaryArea">
        <div class="print-header hidden">
            Fee Collection Summary Report
            <p style="font-size: 12px; font-weight: normal; margin-top: 5px;">Printed on: {{ date('d M Y') }}</p>
        </div>

        <!-- Dashboard Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Target (Billed) -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden">
                <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <p class="text-slate-200 font-bold uppercase tracking-wider text-[10px] mb-1">Total Target (Billed)</p>
                <h3 class="text-3xl font-black font-mono">৳ {{ number_format($overallNet, 2) }}</h3>
            </div>
            
            <!-- Total Collected -->
            <div class="bg-gradient-to-br from-themeGreen to-green-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden">
                <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-green-100 font-bold uppercase tracking-wider text-[10px] mb-1">Total Collected ({{ $overallPercentage }}%)</p>
                <h3 class="text-3xl font-black font-mono">৳ {{ number_format($overallPaid, 2) }}</h3>
            </div>
            
            <!-- Total Pending Dues -->
            <div class="bg-gradient-to-br from-red-600 to-red-900 rounded-3xl p-6 shadow-lg text-white relative overflow-hidden">
                <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-red-100 font-bold uppercase tracking-wider text-[10px] mb-1">Total Pending Dues</p>
                <h3 class="text-3xl font-black font-mono">৳ {{ number_format($overallDue, 2) }}</h3>
            </div>
        </div>

        <!-- Breakdown Table Card Wrapper -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
            
            <div class="p-6 border-b border-gray-100 dark:border-white/[0.05] flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-transparent">
                <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider flex items-center">
                    <svg class="w-5 h-5 mr-2 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    Breakdown by Fee Category
                </h3>
                
                @php
                    $selectedBranch = $branchId ? ($branches->firstWhere('id', $branchId)->branch_name ?? '') : 'All Branches';
                    $selectedSession = $sessionId ? ($sessions->firstWhere('id', $sessionId)->session_name ?? '') : 'All Sessions';
                    $selectedClass = $classId ? ($classes->firstWhere('id', $classId)->class_name ?? '') : 'All Classes';
                @endphp

                <div class="flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-wider filter-badges">
                    <span class="filter-badge px-3 py-1.5 bg-indigo-50 text-indigo-700 dark:bg-themeBlue/10 dark:text-themeBlue border border-indigo-100 dark:border-white/[0.06] rounded-xl flex items-center gap-1.5">
                        🏢 {{ $selectedBranch }}
                    </span>
                    <span class="filter-badge px-3 py-1.5 bg-green-50 text-themeGreen dark:bg-themeGreen/10 dark:text-themeGreen border border-green-100 dark:border-white/[0.06] rounded-xl flex items-center gap-1.5">
                        📅 Session: {{ $selectedSession }}
                    </span>
                    <span class="filter-badge px-3 py-1.5 bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-100 dark:border-white/[0.06] rounded-xl flex items-center gap-1.5">
                        🎓 Class: {{ $selectedClass }}
                    </span>
                </div>
            </div>
            
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Category Name</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Total Billed</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-themeGreen dark:text-themeGreen uppercase tracking-[0.2em] text-right">Collected</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-red-500 dark:text-red-400 uppercase tracking-[0.2em] text-right">Pending Due</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-48">Collection Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($categorySummary as $categoryName => $data)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-4 px-4 font-black text-themeBlue dark:text-themeBlue text-sm uppercase">{{ $categoryName }}</td>
                            <td class="py-4 px-4 text-right font-bold text-gray-800 dark:text-gray-200 font-mono text-sm">৳ {{ number_format($data->total_net, 2) }}</td>
                            <td class="py-4 px-4 text-right font-black text-themeGreen dark:text-themeGreen text-lg font-mono">৳ {{ number_format($data->total_paid, 2) }}</td>
                            <td class="py-4 px-4 text-right font-black text-red-655 dark:text-red-400 text-lg font-mono">৳ {{ number_format($data->total_due, 2) }}</td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-gray-100 dark:bg-themeDark rounded-full h-2 progress-bar-bg border border-gray-200 dark:border-white/[0.05]">
                                        <div class="bg-gradient-to-r from-themeBlue to-themeGreen h-2 rounded-full progress-bar-fill" style="width: {{ $data->percentage }}%"></div>
                                    </div>
                                    <span class="text-xs font-mono font-black text-gray-600 dark:text-gray-300 w-8 text-right">{{ $data->percentage }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-500 dark:text-gray-400 font-medium">No fee data available for the selected filters.</td>
                        </tr>
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
@endpush