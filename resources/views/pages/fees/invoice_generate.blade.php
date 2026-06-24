@extends('tyro-dashboard::layouts.admin')

@section('title', 'Generate Fee Invoices')

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
    .form-label { @apply block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wider; }
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-themeGreen outline-none transition shadow-sm placeholder-gray-400; }

    /* Print & PDF Optimized CSS for A4 */
    @media print {
        @page { size: A4 portrait; margin: 10mm; }
        body * { visibility: hidden; }
        #printableHistoryArea, #printableHistoryArea * { visibility: visible; }
        #printableHistoryArea { position: absolute; left: 0; top: 0; width: 100%; padding: 0; background: white; border: none; }
        
        /* জাদুকরী কোড: প্রিন্টের সময় সব লেখাকে একদম গাঢ় কালো (#000000) করে দিবে */
        #printableHistoryArea * { color: #000000 !important; }

        .no-print { display: none !important; }
        .print-header { display: block !important; text-align: center; margin-bottom: 20px; color: #000 !important; }
        table { width: 100%; border-collapse: collapse; border: 1px solid #000 !important; }
        th, td { border: 1px solid #000 !important; padding: 6px 8px !important; font-size: 12px !important; }
        th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline no-print">Dashboard</a>
<span class="text-gray-400 mx-2 no-print">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium no-print">Generate Invoices</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="mb-8 no-print">
        <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Invoice Generation</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Generate fee bills for all active students in a specific class</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-themeIndigo/20 dark:border-indigo-900/30 p-6 md:p-8 mb-12 relative overflow-hidden no-print">
        <div class="absolute top-0 left-0 w-full h-1 bg-themeIndigo"></div>
        <div class="flex items-center gap-3 mb-6 border-b border-gray-100 dark:border-gray-700 pb-3">
            <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-wider">Select Criteria to Generate</h3>
        </div>
        
        <form action="{{ route('fees.invoice.store') }}" method="POST" onsubmit="return confirm('Generate invoices for this class?');">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="form-label">Branch <span class="text-red-500">*</span></label>
                    <select name="branch_id" class="form-input" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $b) <option value="{{ $b->id }}">{{ $b->branch_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Session <span class="text-red-500">*</span></label>
                    <select name="session_year_id" class="form-input" required>
                        <option value="">Select Session</option>
                        @foreach($sessions as $s) <option value="{{ $s->id }}">{{ $s->session_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Class <span class="text-red-500">*</span></label>
                    <select name="class_id" class="form-input" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $c) <option value="{{ $c->id }}">{{ $c->class_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Fee Category <span class="text-red-500">*</span></label>
                    <select name="fee_category_id" class="form-input" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $cat) <option value="{{ $cat->id }}">{{ $cat->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Fee Month (If Monthly)</label>
                    <select name="fee_month" class="form-input">
                        <option value="">-- One Time / Yearly Fee --</option>
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                            <option value="{{ $month }}">{{ $month }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label text-themeRed dark:text-red-400">Last Date of Payment <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" class="form-input" required>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-themeIndigo hover:bg-indigo-700 text-white font-black py-4 px-10 rounded-xl shadow-xl uppercase tracking-widest text-sm flex items-center transition-transform hover:scale-105">
                    Generate Bulk Invoices
                </button>
            </div>
        </form>
    </div>

    <div id="printableHistoryArea" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-themeGreen/20 dark:border-green-900/30 overflow-hidden">
        
        <div class="print-header hidden">
            <h2 style="font-size: 22px; font-weight: bold; margin: 0;">Invoice Generation History</h2>
            <p style="font-size: 12px; margin-top: 5px;">Generated on: {{ date('d M Y') }}</p>
        </div>

        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50 dark:bg-gray-900/40 no-print">
            <h3 class="text-lg font-black text-themeGreen dark:text-green-500 uppercase tracking-wider flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Generation History
            </h3>
            
            <div class="flex gap-3 w-full md:w-auto">
                <input type="text" id="searchBatchInput" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm px-4 py-2 w-full md:w-64 focus:ring-themeGreen transition" placeholder="Search Class, Month, Branch...">
                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-lg font-bold text-sm flex items-center shadow-md">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print List
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Date & Time</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Class & Branch</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Fee Name</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-center">Billed</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="batchHistoryTableBody">
                    @forelse($generatedBatches as $batch)
                        @if($batch->feeSetup)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-900 dark:text-white text-sm">{{ date('d M Y', strtotime($batch->generated_at)) }}</div>
                                <div class="text-[10px] text-gray-400 font-mono">{{ date('h:i A', strtotime($batch->generated_at)) }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-black text-themeIndigo dark:text-indigo-400 text-sm uppercase">{{ $batch->feeSetup->schoolClass->class_name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-gray-500">{{ $batch->feeSetup->branch->branch_name ?? 'N/A' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800 dark:text-gray-200 text-sm">{{ $batch->feeSetup->category->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-gray-400 italic">{{ $batch->feeSetup->fee_month ?? 'One Time Fee' }}</div>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="bg-themeGreen/10 text-themeGreen dark:text-green-400 font-black px-3 py-0.5 rounded-full text-xs">
                                    {{ $batch->total_students }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right font-black text-themeRed dark:text-red-400 text-lg font-mono">
                                ৳ {{ number_format($batch->total_amount, 2) }}
                            </td>
                        </tr>
                        @endif
                    @empty
                    <tr id="emptyRow">
                        <td colspan="5" class="py-12 text-center text-gray-500 font-medium">No records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchBatchInput');
        const tableBody = document.getElementById('batchHistoryTableBody');
        const rows = tableBody.querySelectorAll('tr:not(#emptyRow)');

        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = searchInput.value.toLowerCase();
                rows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush