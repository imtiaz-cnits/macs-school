@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Reports')

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
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-themeGreen outline-none transition shadow-sm; }
    /* Tab functionality */
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.3s ease-in-out; }
    .tab-btn.active { @apply border-themeIndigo text-themeIndigo dark:border-indigo-400 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Financial Reports</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="mb-8">
        <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Collection & Due Report</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daily financial overview and student balances</p>
    </div>

    <form action="{{ route('fees.reports.index') }}" method="GET" class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-8">
        <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wider">From Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-input">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wider">To Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-input">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wider">Filter by Class</label>
                <select name="class_id" class="form-input">
                    <option value="">All Classes</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full bg-gray-900 dark:bg-gray-700 text-white font-black px-8 py-2.5 rounded-lg shadow hover:bg-gray-800 dark:hover:bg-gray-600 transition uppercase tracking-widest text-sm">Generate Report</button>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-themeGreen to-green-900 rounded-2xl p-6 shadow-lg text-white relative overflow-hidden">
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-green-100 font-bold uppercase tracking-wider text-sm mb-1">Total Collection</p>
            <h3 class="text-3xl font-black font-mono">৳ {{ number_format($totalCollected, 2) }}</h3>
            <p class="text-xs text-green-200 mt-2">{{ date('d M Y', strtotime($startDate)) }} to {{ date('d M Y', strtotime($endDate)) }}</p>
        </div>

        <div class="bg-gradient-to-br from-themeRed to-red-900 rounded-2xl p-6 shadow-lg text-white relative overflow-hidden">
            <svg class="absolute right-0 top-0 w-32 h-32 text-white opacity-10 transform translate-x-8 -translate-y-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-red-100 font-bold uppercase tracking-wider text-sm mb-1">Total Pending Dues</p>
            <h3 class="text-3xl font-black font-mono">৳ {{ number_format($totalDue, 2) }}</h3>
            <p class="text-xs text-red-200 mt-2">Overall pending amount</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 lg:col-span-2 flex flex-wrap gap-4 items-center justify-between">
            <div class="w-full mb-2"><h4 class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Collection Breakdown</h4></div>
            @forelse($methodBreakdown as $method => $amount)
            <div class="flex-1 min-w-[120px] bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">{{ $method }}</p>
                <h4 class="text-lg font-black text-themeIndigo dark:text-indigo-400 font-mono">৳ {{ number_format($amount, 2) }}</h4>
            </div>
            @empty
            <p class="text-sm text-gray-400 font-medium w-full text-center py-2">No collections in this period.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button onclick="switchTab('collection')" class="tab-btn active flex-1 py-4 text-sm font-black uppercase tracking-wider border-b-2 text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-700 dark:hover:text-gray-300 transition" id="btn-collection">
                Collection History
            </button>
            <button onclick="switchTab('dues')" class="tab-btn flex-1 py-4 text-sm font-black uppercase tracking-wider border-b-2 text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-700 dark:hover:text-gray-300 transition" id="btn-dues">
                Defaulters List (Dues)
            </button>
        </div>

        <div id="tab-collection" class="tab-content active overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Receipt & Date</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Student Info</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Fee Details</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase text-right">Method</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($payments as $pay)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="font-bold text-themeIndigo dark:text-indigo-400">{{ $pay->receipt_no }}</div>
                            <div class="text-[11px] text-gray-500 font-medium mt-0.5">{{ date('d M Y, h:i A', strtotime($pay->created_at)) }}</div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-bold text-gray-900 dark:text-gray-100">{{ $pay->student->student_name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $pay->student->student_identity ?? 'N/A' }} | Class: {{ $pay->student->schoolClass->class_name ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                            {{ $pay->invoice->feeSetup->category->name ?? 'Fee' }}
                            <span class="text-[10px] text-gray-400 block">{{ $pay->invoice->feeSetup->fee_month ?? '' }}</span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <span class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-[10px] font-black uppercase">{{ $pay->payment_method }}</span>
                        </td>
                        <td class="py-4 px-6 text-right font-black text-themeGreen dark:text-green-400 text-lg">
                            ৳ {{ number_format($pay->paid_amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-12 text-center text-gray-500 dark:text-gray-400 font-medium">No collections found in this date range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="tab-dues" class="tab-content overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Student Info</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Fee Description</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase text-right">Due Date</th>
                        <th class="py-4 px-6 text-xs font-black text-themeRed dark:text-red-400 uppercase text-right">Due Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($dues as $due)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="font-bold text-gray-900 dark:text-gray-100">{{ $due->student->student_name ?? 'N/A' }}</div>
                            <div class="text-[11px] text-gray-500 mt-0.5 font-mono">{{ $due->student->student_identity ?? 'N/A' }} <span class="mx-1">•</span> Class: {{ $due->student->schoolClass->class_name ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-700 dark:text-gray-300">
                            <div class="font-bold">{{ $due->feeSetup->category->name ?? 'Fee' }}</div>
                            <div class="text-[10px] text-gray-400">{{ $due->feeSetup->fee_month ?? 'One Time' }}</div>
                        </td>
                        <td class="py-4 px-6 text-right text-sm text-gray-600 dark:text-gray-400">
                            {{ date('d M Y', strtotime($due->due_date)) }}
                        </td>
                        <td class="py-4 px-6 text-right font-black text-themeRed dark:text-red-400 text-lg">
                            ৳ {{ number_format($due->due_amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-12 text-center text-green-600 dark:text-green-400 font-bold">Awesome! No pending dues found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Tab switching logic
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        document.getElementById('tab-' + tabId).classList.add('active');
        document.getElementById('btn-' + tabId).classList.add('active');
    }
</script>
@endpush