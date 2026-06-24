@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Setup Management')

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
    
    /* Print & PDF CSS - Optimized for A4 */
    @media print {
        @page { size: A4 portrait; margin: 10mm; } /* A4 পেপারের ডিফল্ট সাইজ এবং মার্জিন */
        body * { visibility: hidden; }
        #printableTableArea, #printableTableArea * { visibility: visible; }
        #printableTableArea { position: absolute; left: 0; top: 0; width: 100%; padding: 0; background: white; box-shadow: none; border: none; }
        .no-print { display: none !important; }
        
        .print-header { display: block !important; text-align: center; margin-bottom: 15px; font-weight: bold; font-size: 20px; color: black; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        
        /* টেবিলকে ছোট এবং কম্প্যাক্ট করার জন্য প্যাডিং ও ফন্ট সাইজ ওভাররাইড করা হলো */
        th, td { 
            border: 1px solid #333 !important; 
            padding: 4px 6px !important; /* প্যাডিং একদম কমিয়ে দেওয়া হলো */
            color: black !important; 
            font-size: 11px !important; /* ফন্ট সাইজ ছোট করা হলো */
            line-height: 1.2 !important;
        }
        
        /* Tailwind এর বড় ফন্ট ক্লাসগুলো প্রিন্টে অফ করা হলো */
        .text-lg, .text-sm, .text-xs { font-size: 11px !important; }
        .bg-blue-50, .bg-red-50 { background: transparent !important; }
        
        /* টেবিলের হেডারের ডিজাইন */
        th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
    }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline no-print">Dashboard</a>
<span class="text-gray-400 mx-2 no-print">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium no-print">Fee Setup</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="mb-8 no-print">
        <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Class-wise Fee Setup</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Assign fee amounts to specific classes and sessions</p>
    </div>

    @if(session('success')) 
        <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 p-4 rounded-xl mb-6 font-bold border border-green-200 dark:border-green-800 no-print">{{ session('success') }}</div> 
    @endif
    @if(session('error')) 
        <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 p-4 rounded-xl mb-6 font-bold border border-red-200 dark:border-red-800 no-print">{{ session('error') }}</div> 
    @endif
    @if($errors->any()) 
        <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 p-4 rounded-xl mb-6 font-bold border border-red-200 dark:border-red-800 no-print">{{ $errors->first() }}</div> 
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-themeGreen/20 dark:border-green-900/30 p-6 md:p-8 mb-8 relative overflow-hidden no-print">
        <div class="absolute top-0 left-0 w-full h-1 bg-themeGreen"></div>
        <h3 class="text-lg font-black text-themeGreen dark:text-green-500 mb-6 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 pb-3">Assign New Fee</h3>
        
        <form action="{{ route('fees.setup.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                
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
                    <label class="form-label">Fee Month (Optional)</label>
                    <select name="fee_month" class="form-input">
                        <option value="">-- One Time / Yearly Fee --</option>
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                            <option value="{{ $month }}">{{ $month }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label text-themeRed dark:text-red-400">Amount (৳) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-input font-mono font-bold text-lg text-themeRed dark:text-red-400" placeholder="0.00" required>
                </div>

                <div class="md:col-span-3 lg:col-span-2 flex items-end">
                    <button type="submit" class="w-full bg-themeGreen hover:bg-green-900 text-white font-black py-3.5 rounded-xl shadow-lg transition-all hover:scale-[1.02] uppercase tracking-widest text-sm flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Set Fee Amount
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="printableTableArea" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        
        <div class="print-header hidden">
            School Fee Setup Details
            <p style="font-size: 12px; font-weight: normal; margin-top: 5px;">Printed on: {{ date('d M Y') }}</p>
        </div>

        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50 dark:bg-gray-900/40">
            <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-wider">Current Fee Assignments</h3>
            
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto no-print">
                <div class="relative flex-1 sm:w-64">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="searchTableInput" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-themeGreen focus:border-themeGreen block w-full pl-10 p-2.5 shadow-sm transition" placeholder="Search Branch, Class, Fee...">
                </div>
                
                <button onclick="window.print()" class="inline-flex items-center justify-center bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print / PDF
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Branch</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Session</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Class</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Fee Name</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Month</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-right">Amount (৳)</th>
                        <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-right no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="feeSetupTableBody">
                    @forelse($setups as $setup)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-6 font-bold text-gray-700 dark:text-gray-300 text-sm">{{ $setup->branch->branch_name ?? 'N/A' }}</td>
                        <td class="py-4 px-6 font-bold text-gray-700 dark:text-gray-300 text-sm">{{ $setup->sessionYear->session_name ?? 'N/A' }}</td>
                        <td class="py-4 px-6 font-black text-themeIndigo dark:text-indigo-400 text-lg">{{ $setup->schoolClass->class_name ?? 'N/A' }}</td>
                        <td class="py-4 px-6 font-bold text-gray-900 dark:text-gray-100">{{ $setup->category->name }}</td>
                        <td class="py-4 px-6 text-sm font-medium text-gray-500 dark:text-gray-400">
                            @if($setup->fee_month)
                                <span class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1 rounded text-xs font-bold tracking-wider">{{ strtoupper($setup->fee_month) }}</span>
                            @else
                                <span class="text-gray-400 italic">One Time</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-right font-black text-themeRed dark:text-red-400 text-lg font-mono">
                            {{ number_format($setup->amount, 2) }}
                        </td>
                        <td class="py-4 px-6 text-right no-print">
                            <form action="{{ route('fees.setup.destroy', $setup->id) }}" method="POST" onsubmit="return confirm('Remove this fee setup?');">
                                @csrf @method('DELETE')
                                <button class="text-themeRed hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-bold text-sm bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg transition-colors">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400 font-medium">No fee setups found. Assign a fee to a class above!</td>
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
    // Live Search Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchTableInput');
        const tableBody = document.getElementById('feeSetupTableBody');
        const rows = tableBody.querySelectorAll('tr:not(#emptyRow)'); // সিলেক্ট করা হচ্ছে সব রো (empty row বাদে)

        if(searchInput) {
            searchInput.addEventListener('keyup', function() {
                let filter = searchInput.value.toLowerCase();

                rows.forEach(row => {
                    // রো-এর ভেতরের সব টেক্সট একসাথে চেক করা হচ্ছে
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
</script>
@endpush