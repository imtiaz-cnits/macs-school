@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Collection')

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
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-themeGreen outline-none transition shadow-sm; }
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Fee Collection</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Fee Collection</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Search student and collect pending dues</p>
        </div>
        
        <form action="{{ route('fees.collection.index') }}" method="GET" class="w-full md:w-96 flex gap-2">
            <input type="text" name="student_identity" value="{{ request('student_identity') }}" placeholder="Enter Student ID (e.g. PIS-...)" class="form-input flex-1 font-mono uppercase" required>
            <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-bold px-6 py-2.5 rounded-lg shadow transition">Search</button>
        </form>
    </div>

    @if(session('success')) 
        <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 p-4 rounded-xl mb-6 font-bold border border-green-200 dark:border-green-800 flex justify-between items-center">
            <span>{{ session('success') }}</span>
            <div class="flex gap-2">
                @if(session('print_invoice_id'))
                    <a href="{{ route('fees.invoice.pos_print', session('print_invoice_id')) }}" target="_blank" class="bg-green-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-green-700 shadow-sm flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print POS Receipt
                    </a>
                @endif
                @if(session('print_receipt_no'))
                    <a href="{{ route('fees.receipt.pos_print', session('print_receipt_no')) }}" target="_blank" class="bg-themeIndigo text-white px-4 py-1.5 rounded-lg text-sm hover:bg-indigo-700 shadow-sm flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print Master Receipt
                    </a>
                @endif
            </div>
        </div> 
    @endif
    @if(session('error')) <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 font-bold border border-red-200">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6 font-bold border border-red-200">{{ $errors->first() }}</div> @endif

    @if($student)
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-themeIndigo/20 p-6 lg:col-span-1 h-fit relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-themeIndigo"></div>
            <div class="text-center mb-4">
                <div class="w-24 h-24 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full border-4 border-white dark:border-gray-600 shadow-md mb-3 flex items-center justify-center overflow-hidden">
                    <img src="https://ui-avatars.com/api/?name={{ $student->student_name }}&background=4f46e5&color=fff" class="w-full h-full object-cover">
                </div>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $student->student_name }}</h3>
                <p class="text-themeIndigo dark:text-indigo-400 font-mono font-bold mt-1">{{ $student->student_identity }}</p>
            </div>
            
            <div class="space-y-3 mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Class:</span> <span class="font-bold dark:text-white">{{ $student->schoolClass->class_name ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Section:</span> <span class="font-bold dark:text-white">{{ $student->section->section_name ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Roll No:</span> <span class="font-bold dark:text-white">{{ $student->roll_number }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Mobile:</span> <span class="font-bold dark:text-white">{{ $student->father_mobile }}</span></div>
            </div>
        </div>

        <div class="lg:col-span-3 space-y-8">
            
            <form action="{{ route('fees.collection.bulk_store') }}" method="POST" id="bulkPaymentForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-themeRed/20 dark:border-red-900/30 overflow-hidden relative pb-16">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-red-50 dark:bg-red-900/10">
                        <h3 class="text-lg font-black text-themeRed dark:text-red-400 uppercase tracking-wider flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Pending Dues
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-4 px-4 text-center w-12">
                                        <input type="checkbox" id="selectAllDues" class="w-4 h-4 text-themeGreen rounded border-gray-300 focus:ring-themeGreen cursor-pointer">
                                    </th>
                                    <th class="py-4 px-2 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Fee Description</th>
                                    <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Month</th>
                                    <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase text-right">Net Bill</th>
                                    <th class="py-4 px-6 text-xs font-black text-themeRed dark:text-red-400 uppercase text-right">Due Amt</th>
                                    <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($invoices as $inv)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="py-4 px-4 text-center">
                                        <input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" data-amount="{{ $inv->due_amount }}" class="due-checkbox w-4 h-4 text-themeGreen rounded border-gray-300 focus:ring-themeGreen cursor-pointer">
                                    </td>
                                    <td class="py-4 px-2">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ $inv->feeSetup->category->name }}</div>
                                        <div class="text-[10px] text-gray-400 font-mono mt-0.5">{{ $inv->invoice_no }}</div>
                                    </td>
                                    <td class="py-4 px-6 text-sm font-medium text-gray-600 dark:text-gray-400">{{ $inv->feeSetup->fee_month ?? 'One Time' }}</td>
                                    <td class="py-4 px-6 text-right font-medium text-gray-600 dark:text-gray-300">{{ number_format($inv->net_amount, 2) }}</td>
                                    <td class="py-4 px-6 text-right font-black text-themeRed dark:text-red-400 text-lg">৳ {{ number_format($inv->due_amount, 2) }}</td>
                                    <td class="py-4 px-6 text-right space-x-2">
                                        <button type="button" onclick="openPayModal({{ $inv->id }}, '{{ $inv->feeSetup->category->name }}', {{ $inv->due_amount }})" class="bg-themeGreen text-white px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-green-800 shadow-sm transition">Pay Single</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-green-600 dark:text-green-400 font-black text-lg">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-green-500 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        All Clear! No pending dues for this student.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($invoices->count() > 0)
                    <div id="bulkPaymentBar" class="absolute bottom-0 left-0 w-full bg-themeGreen dark:bg-green-900 px-6 py-3 flex justify-between items-center hidden translate-y-full transition-transform duration-300 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
                        <div class="text-white flex items-center">
                            <span class="text-sm font-medium opacity-80">Selected Total:</span>
                            <span class="text-xl font-black ml-2 font-mono">৳ <span id="bulkTotalDisplay">0.00</span></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <select name="payment_method" class="bg-white/20 text-white border border-white/30 rounded px-3 py-1.5 text-sm outline-none focus:ring-1 focus:ring-white">
                                <option class="text-black" value="Cash">Cash</option>
                                <option class="text-black" value="bKash">bKash</option>
                                <option class="text-black" value="Nagad">Nagad</option>
                                <option class="text-black" value="Bank">Bank Transfer</option>
                            </select>
                            <button type="submit" class="bg-white text-themeGreen px-5 py-1.5 rounded font-black text-sm hover:bg-gray-100 shadow-lg transition-transform hover:scale-105 active:scale-95">Pay Selected Dues</button>
                        </div>
                    </div>
                    @endif
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-themeGreen/20 dark:border-green-900/30 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-green-50 dark:bg-green-900/10">
                    <h3 class="text-lg font-black text-themeGreen dark:text-green-500 uppercase tracking-wider flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        Payment History
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Paid Items</th>
                                <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase">Date & Method</th>
                                <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase text-right">Total Paid</th>
                                <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            
                            @forelse($paymentHistory as $receiptNo => $payments)
                            @php
                                $totalPaid = $payments->sum('paid_amount');
                                $date = $payments->first()->payment_date;
                                $method = $payments->first()->payment_method;
                            @endphp
                            
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="space-y-1 mb-1.5">
                                        @foreach($payments as $p)
                                            <div class="font-bold text-gray-900 dark:text-gray-100 text-sm flex items-start">
                                                <svg class="w-4 h-4 text-themeGreen mr-1 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                {{ $p->invoice->feeSetup->category->name }} 
                                                {{ $p->invoice->feeSetup->fee_month ? '('.$p->invoice->feeSetup->fee_month.')' : '' }}
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="text-[10px] text-gray-500 font-mono bg-gray-100 dark:bg-gray-700 inline-block px-2 py-0.5 rounded">{{ $receiptNo }}</div>
                                </td>
                                
                                <td class="py-4 px-6">
                                    <div class="font-bold text-gray-800 dark:text-gray-200">{{ date('d M, Y', strtotime($date)) }}</div>
                                    <div class="text-xs text-gray-500 font-medium mt-0.5">Via {{ $method }}</div>
                                </td>
                                
                                <td class="py-4 px-6 text-right font-black text-themeGreen dark:text-green-400 text-xl">
                                    ৳ {{ number_format($totalPaid, 2) }}
                                </td>
                                
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('fees.receipt.pos_print', $receiptNo) }}" target="_blank" class="inline-flex items-center text-themeIndigo hover:text-indigo-800 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 px-4 py-2 rounded-xl text-sm font-black transition-transform hover:scale-105 shadow-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        Receipt
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-gray-500 dark:text-gray-400 font-medium">
                                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    No payment history available.
                                </td>
                            </tr>
                            @endforelse
                            
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    @elseif(request()->filled('student_identity'))
        @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 py-20 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z"></path></svg>
            <h3 class="text-xl font-bold text-gray-500 dark:text-gray-400">Search for a student to view and collect dues</h3>
        </div>
    @endif
</div>

<div id="paymentModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-themeGreen/5">
            <h3 class="text-xl font-black text-themeGreen dark:text-green-500 uppercase tracking-wider">Collect Payment</h3>
            <p id="modalFeeName" class="text-sm text-gray-600 dark:text-gray-400 font-bold mt-1"></p>
        </div>
        
        <form action="{{ route('fees.collection.store') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="invoice_id" id="modalInvoiceId">
            
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-wider">Total Due Amount</label>
                <div class="text-2xl font-black text-themeRed dark:text-red-400 font-mono">৳ <span id="modalDueAmountText">0.00</span></div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-wider">Paying Amount (৳) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="pay_amount" id="modalPayAmount" class="form-input text-lg font-bold font-mono text-themeGreen" required>
                <p class="text-[10px] text-gray-400 mt-1">You can receive partial payment by changing this amount.</p>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-wider">Payment Method <span class="text-red-500">*</span></label>
                <select name="payment_method" class="form-input">
                    <option value="Cash">Cash</option>
                    <option value="bKash">bKash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Bank">Bank Transfer</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 uppercase tracking-wider">Trx ID / Note (Optional)</label>
                <input type="text" name="transaction_id" class="form-input" placeholder="e.g. 8N2K9DJ3">
            </div>

            <div class="mt-8 flex gap-3">
                <button type="button" onclick="closePayModal()" class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="flex-1 bg-themeGreen hover:bg-green-900 text-white font-black py-3 rounded-xl shadow-lg transition">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openPayModal(invoiceId, feeName, dueAmount) {
        document.getElementById('modalInvoiceId').value = invoiceId;
        document.getElementById('modalFeeName').innerText = feeName;
        document.getElementById('modalDueAmountText').innerText = parseFloat(dueAmount).toFixed(2);
        
        document.getElementById('modalPayAmount').value = dueAmount;
        document.getElementById('modalPayAmount').max = dueAmount;

        const modal = document.getElementById('paymentModal');
        modal.classList.remove('hidden');
        modal.classList.add('modal-active');
    }

    function closePayModal() {
        const modal = document.getElementById('paymentModal');
        modal.classList.remove('modal-active');
        modal.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.due-checkbox');
        const selectAll = document.getElementById('selectAllDues');
        const bulkBar = document.getElementById('bulkPaymentBar');
        const bulkTotal = document.getElementById('bulkTotalDisplay');

        function calculateTotal() {
            let total = 0;
            let checkedCount = 0;
            
            checkboxes.forEach(box => {
                if(box.checked) {
                    total += parseFloat(box.dataset.amount);
                    checkedCount++;
                }
            });

            if (bulkTotal) {
                bulkTotal.innerText = total.toFixed(2);
            }
            
            if(bulkBar) {
                if(checkedCount > 0) {
                    bulkBar.classList.remove('hidden', 'translate-y-full');
                } else {
                    bulkBar.classList.add('hidden', 'translate-y-full');
                }
            }
            
            if(selectAll) {
                selectAll.checked = (checkedCount === checkboxes.length && checkboxes.length > 0);
            }
        }

        checkboxes.forEach(box => {
            box.addEventListener('change', calculateTotal);
        });

        if(selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(box => box.checked = this.checked);
                calculateTotal();
            });
        }
    });
</script>
@endpush