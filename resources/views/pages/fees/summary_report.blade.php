@extends('tyro-dashboard::layouts.admin')

@section('title', 'Category-wise Fee Summary')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#1e4630', themeRed: '#cc0000', themeIndigo: '#4f46e5' },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-themeGreen outline-none transition shadow-sm; }
    
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
        .progress-bar-fill { background: #1e4630 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* ব্যাজগুলো প্রিন্টে সুন্দর দেখার জন্য */
        .filter-badges { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 15px; justify-content: flex-end; }
        .filter-badge { border: 1px solid #000 !important; padding: 6px 12px !important; border-radius: 6px !important; font-size: 14px !important; font-weight: bold !important; background: transparent !important; color: #000 !important; }
    }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline no-print">Dashboard</a>
<span class="text-gray-400 mx-2 no-print">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium no-print">Category Summary Report</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="mb-8 no-print flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Category-wise Summary</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Analyze fee collection and dues based on specific criteria</p>
        </div>
        <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Report
        </button>
    </div>

    <form action="{{ route('fees.reports.summary') }}" method="GET" class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-8 no-print">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase">Branch</label>
                <select name="branch_id" class="form-input">
                    <option value="">All Branches</option>
                    @foreach($branches as $b) <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->branch_name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase">Session</label>
                <select name="session_year_id" class="form-input">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $s) <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>{{ $s->session_name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase">Class</label>
                <select name="class_id" class="form-input">
                    <option value="">All Classes</option>
                    @foreach($classes as $c) <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option> @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-themeIndigo hover:bg-indigo-700 text-white font-black py-2.5 rounded-lg shadow transition uppercase text-sm">Filter Data</button>
            </div>
        </div>
    </form>

    <div id="printableSummaryArea">
        <div class="print-header hidden">
            Fee Collection Summary Report
            <p style="font-size: 12px; font-weight: normal; margin-top: 5px;">Printed on: {{ date('d M Y') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <p class="text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-xs mb-1">Total Target (Billed)</p>
                <h3 class="text-2xl font-black font-mono dark:text-white">৳ {{ number_format($overallNet, 2) }}</h3>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-800/30">
                <p class="text-green-600 dark:text-green-400 font-bold uppercase tracking-wider text-xs mb-1">Total Collected ({{ $overallPercentage }}%)</p>
                <h3 class="text-2xl font-black font-mono text-themeGreen dark:text-green-400">৳ {{ number_format($overallPaid, 2) }}</h3>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-6 border border-red-200 dark:border-red-800/30">
                <p class="text-red-600 dark:text-red-400 font-bold uppercase tracking-wider text-xs mb-1">Total Pending Dues</p>
                <h3 class="text-2xl font-black font-mono text-themeRed dark:text-red-400">৳ {{ number_format($overallDue, 2) }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-wider">Breakdown by Fee Category</h3>
                
                @php
                    // ফিল্টার করা ডাটাগুলোর নাম বের করা হচ্ছে
                    $selectedBranch = $branchId ? ($branches->firstWhere('id', $branchId)->branch_name ?? '') : 'All Branches';
                    $selectedSession = $sessionId ? ($sessions->firstWhere('id', $sessionId)->session_name ?? '') : 'All Sessions';
                    $selectedClass = $classId ? ($classes->firstWhere('id', $classId)->class_name ?? '') : 'All Classes';
                @endphp

                <div class="flex flex-wrap gap-3 text-sm font-black filter-badges mt-3 md:mt-0">
                    <span class="filter-badge bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 px-4 py-2.5 rounded-xl border border-indigo-200 dark:border-indigo-700 shadow-sm flex items-center">
                        <span class="mr-1.5 text-lg">🏢</span> {{ $selectedBranch }}
                    </span>
                    <span class="filter-badge bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 px-4 py-2.5 rounded-xl border border-green-200 dark:border-green-700 shadow-sm flex items-center">
                        <span class="mr-1.5 text-lg">📅</span> Session: {{ $selectedSession }}
                    </span>
                    <span class="filter-badge bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 px-4 py-2.5 rounded-xl border border-red-200 dark:border-red-700 shadow-sm flex items-center">
                        <span class="mr-1.5 text-lg">🎓</span> Class: {{ $selectedClass }}
                    </span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Category Name</th>
                            <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-right">Total Billed</th>
                            <th class="py-4 px-6 text-xs font-black text-themeGreen dark:text-green-400 uppercase tracking-widest text-right">Collected</th>
                            <th class="py-4 px-6 text-xs font-black text-themeRed dark:text-red-400 uppercase tracking-widest text-right">Pending Due</th>
                            <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest w-48">Collection Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($categorySummary as $categoryName => $data)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="py-4 px-6 font-black text-themeIndigo dark:text-indigo-400 text-sm uppercase">{{ $categoryName }}</td>
                            <td class="py-4 px-6 text-right font-bold text-gray-700 dark:text-gray-300 font-mono">৳ {{ number_format($data->total_net, 2) }}</td>
                            <td class="py-4 px-6 text-right font-black text-themeGreen dark:text-green-400 text-lg font-mono">৳ {{ number_format($data->total_paid, 2) }}</td>
                            <td class="py-4 px-6 text-right font-black text-themeRed dark:text-red-400 text-lg font-mono">৳ {{ number_format($data->total_due, 2) }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 progress-bar-bg">
                                        <div class="bg-themeGreen h-2 rounded-full progress-bar-fill" style="width: {{ $data->percentage }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300 w-8 text-right">{{ $data->percentage }}%</span>
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