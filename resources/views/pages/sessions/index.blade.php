@extends('tyro-dashboard::layouts.admin')

@section('title', 'Session Year Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    .table th, .table td { padding: 0.625rem 1rem !important; }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                    <path d="M3 3v5h5"/>
                    <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                    <path d="M16 16h5v5"/>
                </svg>
                Session Management
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Manage academic sessions and session years for student intake schedules</p>
        </div>
        <button onclick="window.openModal('createModal')" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Session
        </button>
    </div>

    <!-- Standardized Borderless Table Directory Layout -->
    <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
        <table class="w-full text-left border-collapse table">
            <thead>
                <tr class="!bg-transparent">
                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-20">SL</th>
                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Session Year</th>
                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-64">Fee Status</th>
                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-48">Actions</th>
                </tr>
            </thead>
            <tbody id="tableList" class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                <tr>
                    <td colspan="4" class="py-12">
                        <div class="flex flex-col items-center gap-3 animate-pulse justify-center">
                            <div class="h-4 w-24 bg-gray-200 dark:bg-gray-800 rounded-md"></div>
                            <span class="text-xs text-gray-450 dark:text-gray-500 font-bold uppercase tracking-widest">Loading Sessions...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer (Floating directly below table) -->
    <div id="pagination-container" class="mt-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Add New Session</h3>
            <button onclick="window.closeModal('createModal')" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Session Year <span class="text-red-500 ml-0.5">*</span></label>
            <input type="text" id="SessionName" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. 2024-2025">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-themeDark/50 flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.05]">
            <button onclick="window.closeModal('createModal')" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
            <button onclick="window.SaveData()" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Save Session</button>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div id="updateModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Update Session</h3>
            <button onclick="window.closeModal('updateModal')" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <input type="hidden" id="updateID">
            <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Session Year <span class="text-red-500 ml-0.5">*</span></label>
            <input type="text" id="SessionNameUpdate" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-themeDark/50 flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.05]">
            <button onclick="window.closeModal('updateModal')" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
            <button onclick="window.UpdateData()" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Update Changes</button>
        </div>
    </div>
</div>

<!-- Fee Generation Status Details Modal -->
<div id="feeStatusModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-black/40 dark:bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-white/[0.08] transition-all">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center bg-gray-50/50 dark:bg-themeDark/30">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-themeBlue/10 flex items-center justify-center text-themeBlue">
                    <svg class="w-5 h-5 text-themeBlue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white" id="modalSessionTitle">Fee Status Details</h3>
                    <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Annual monthly billing coverage and diagnostic breakdown</p>
                </div>
            </div>
            <button onclick="window.closeModal('feeStatusModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/[0.05] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
            <!-- Status Alert Banner -->
            <div id="modalStatusBanner" class="p-4 rounded-2xl flex items-start gap-3 border">
            </div>

            <!-- Key Metric Cards Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <div class="bg-gray-50/70 dark:bg-themeDark/50 border border-gray-100 dark:border-white/[0.05] rounded-2xl p-3">
                    <div class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase">Target</div>
                    <div class="text-lg font-black text-gray-900 dark:text-white mt-0.5" id="modalExpectedInvoices">0</div>
                    <div class="text-[10px] text-gray-400">12 Mo Invoices</div>
                </div>
                <div class="bg-gray-50/70 dark:bg-themeDark/50 border border-gray-100 dark:border-white/[0.05] rounded-2xl p-3">
                    <div class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase">Existing</div>
                    <div class="text-lg font-black text-themeGreen mt-0.5" id="modalExistingInvoices">0</div>
                    <div class="text-[10px] text-gray-400" id="modalCoveragePercent">0% Covered</div>
                </div>
                <div class="bg-gray-50/70 dark:bg-themeDark/50 border border-gray-100 dark:border-white/[0.05] rounded-2xl p-3">
                    <div class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase">Missing</div>
                    <div class="text-lg font-black text-amber-600 mt-0.5" id="modalMissingInvoices">0</div>
                    <div class="text-[10px] text-gray-400">To Generate</div>
                </div>
                <div class="bg-gray-50/70 dark:bg-themeDark/50 border border-gray-100 dark:border-white/[0.05] rounded-2xl p-3">
                    <div class="text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-550 uppercase">Students</div>
                    <div class="text-lg font-black text-themeBlue mt-0.5" id="modalEligibleStudents">0</div>
                    <div class="text-[10px] text-gray-400">Eligible Enrolled</div>
                </div>
            </div>

            <!-- Diagnostic Breakdown List -->
            <div class="bg-gray-50/50 dark:bg-themeDark/30 border border-gray-100 dark:border-white/[0.05] rounded-2xl p-4 divide-y divide-gray-100 dark:divide-white/[0.05] text-xs font-semibold">
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-gray-400">Active Monthly Fee Setups:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200" id="modalSetupsCount">0 setup(s)</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-gray-400">Total Invoices in Database (Inc. Cancelled):</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200" id="modalTotalInvoices">0</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-gray-400">Cancelled Invoices (Restorable):</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200" id="modalCancelledInvoices">0</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-gray-400">Extra / Unscoped Invoices:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200" id="modalExtraInvoices">0</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-gray-400">Duplicate Invoices:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200" id="modalDuplicateInvoices">0</span>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50/80 dark:bg-themeDark/60 flex justify-between items-center border-t border-gray-100 dark:border-white/[0.05]">
            <button onclick="window.closeModal('feeStatusModal')" class="h-10 px-5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Close</button>
            <div id="modalActionContainer">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    window.openModal = function(id) {
        let el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('modal-active');
        document.body.classList.add('overflow-hidden');
        if(id === 'createModal') document.getElementById('SessionName').value = '';
    };

    window.closeModal = function(id) {
        let el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('modal-active');
        el.classList.add('hidden');
        if (!document.querySelector('.modal-active')) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    // Close modal on backdrop click
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('modal-active')) {
            window.closeModal(e.target.id);
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            let active = document.querySelector('.modal-active');
            if (active) window.closeModal(active.id);
        }
    });

    function getAuthHeaders() {
        return { 
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            } 
        };
    }

    window.sessionDataCache = {};
    window.allSessionsData = [];
    window.currentPage = 1;
    window.perPage = 15;

    window.goToPage = function(page) {
        window.currentPage = page;
        window.renderSessionTable();
    };

    window.fetchList = async function() {
        let list = document.getElementById('tableList');
        try {
            let res = await axios.get("/ajax/sessions", getAuthHeaders());
            let data = res.data.sessionData || res.data.data || res.data;
            window.allSessionsData = Array.isArray(data) ? data : [];
            window.renderSessionTable();
        } catch (e) {
            list.innerHTML = `<tr><td colspan="4" class="py-12 text-center text-red-500 font-bold">Error loading data.</td></tr>`;
            let container = document.getElementById('pagination-container');
            if (container) container.innerHTML = '';
        }
    };

    window.renderSessionTable = function() {
        let list = document.getElementById('tableList');
        if (!list) return;

        let total = window.allSessionsData.length;
        window.sessionDataCache = {};

        if (total === 0) {
            list.innerHTML = `<tr><td colspan="4" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">No sessions found. Add a new session.</td></tr>`;
            window.renderPagination({ total: 0, current_page: 1, last_page: 1, from: 0, to: 0 });
            return;
        }

        let lastPage = Math.max(1, Math.ceil(total / window.perPage));
        if (window.currentPage > lastPage) window.currentPage = lastPage;
        if (window.currentPage < 1) window.currentPage = 1;

        let start = (window.currentPage - 1) * window.perPage;
        let end = Math.min(start + window.perPage, total);
        let pageData = window.allSessionsData.slice(start, end);

        list.innerHTML = '';
        pageData.forEach((item, index) => {
            let sl = start + index + 1;
            window.sessionDataCache[item.id] = item;
            let badgeHtml = '';
            let status = item.generation_status || 'pending';
            if (status === 'completed') {
                badgeHtml = `<button type="button" onclick="window.showFeeStatusModal(${item.id})" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-black bg-emerald-50 dark:bg-themeGreen/10 text-themeGreen border border-emerald-200/50 dark:border-themeGreen/20 hover:scale-105 hover:shadow-xs transition-all cursor-pointer group" title="Click to view details">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Completed
                    <svg class="w-3 h-3 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>`;
            } else if (status === 'partial') {
                let missing = (item.status_details && item.status_details.missing_invoices) ? `${item.status_details.missing_invoices} missing` : 'Partial';
                badgeHtml = `<button type="button" onclick="window.showFeeStatusModal(${item.id})" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-black bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-500/20 hover:scale-105 hover:shadow-xs transition-all cursor-pointer group" title="Click to view details">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Partial (${missing})
                    <svg class="w-3 h-3 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>`;
            } else if (status === 'pending_fee_setup') {
                badgeHtml = `<button type="button" onclick="window.showFeeStatusModal(${item.id})" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-black bg-blue-50 dark:bg-themeBlue/10 text-themeBlue border border-blue-200/50 dark:border-themeBlue/20 hover:scale-105 hover:shadow-xs transition-all cursor-pointer group" title="Click to view details">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Pending Setup
                    <svg class="w-3 h-3 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>`;
            } else {
                badgeHtml = `<button type="button" onclick="window.showFeeStatusModal(${item.id})" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-black bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:scale-105 hover:shadow-xs transition-all cursor-pointer group" title="Click to view details">
                    Pending
                    <svg class="w-3 h-3 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>`;
            }

            let row = `
                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                    <td class="py-4 px-4 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">${sl}</td>
                    <td class="py-4 px-4 text-sm font-bold text-gray-900 dark:text-gray-100">${item.session_name}</td>
                    <td class="py-4 px-4 text-center">${badgeHtml}</td>
                    <td class="py-4 px-4">
                        <div class="flex items-center justify-end gap-2.5">
                            <!-- Generate / Retry Annual Fees Button -->
                            <button onclick="window.GenerateAnnualFees(${item.id}, '${item.session_name}')" class="action-btn text-emerald-600 hover:text-emerald-700 hover:border-emerald-600" title="Generate / Retry Annual Fees">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                            </button>
                            <!-- Edit Button -->
                            <button onclick="window.FillUpdateForm(${item.id})" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Session">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <!-- Delete Button -->
                            <button onclick="window.DeleteID(${item.id})" class="action-btn text-red-650 hover:text-red-800 hover:border-red-600" title="Delete Session">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
            list.innerHTML += row;
        });

        window.renderPagination({
            total: total,
            per_page: window.perPage,
            current_page: window.currentPage,
            last_page: lastPage,
            from: start + 1,
            to: end
        });
    };

    window.renderPagination = function(paginator) {
        let container = document.getElementById('pagination-container');
        if (!container) return;

        if (!paginator || paginator.total === 0) {
            container.innerHTML = '';
            return;
        }

        if (paginator.last_page <= 1) {
            container.innerHTML = `
                <div class="text-xs font-semibold text-gray-400 dark:text-gray-555 uppercase tracking-wider">
                    Showing <span class="font-black text-gray-750 dark:text-gray-300">${paginator.from || 1}</span> to <span class="font-black text-gray-750 dark:text-gray-300">${paginator.to || paginator.total}</span> of <span class="font-black text-gray-750 dark:text-gray-300">${paginator.total}</span> entries
                </div>
            `;
            return;
        }

        let html = `
            <div class="text-xs font-semibold text-gray-400 dark:text-gray-555 uppercase tracking-wider mb-4 md:mb-0">
                Showing <span class="font-black text-gray-750 dark:text-gray-300">${paginator.from}</span> to <span class="font-black text-gray-750 dark:text-gray-300">${paginator.to}</span> of <span class="font-black text-gray-750 dark:text-gray-300">${paginator.total}</span> entries
            </div>
            <div class="flex items-center space-x-1.5">
        `;

        // Prev Button
        if (paginator.current_page > 1) {
            html += `<button type="button" onclick="window.goToPage(${paginator.current_page - 1})" class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs uppercase font-black tracking-wider transition-all">Prev</button>`;
        } else {
            html += `<button disabled class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs opacity-50 cursor-not-allowed uppercase font-black tracking-wider">Prev</button>`;
        }

        // Page Numbers
        for (let i = 1; i <= paginator.last_page; i++) {
            if (i === 1 || i === paginator.last_page || Math.abs(paginator.current_page - i) <= 1) {
                if (i === paginator.current_page) {
                    html += `<button class="btn-xs btn-primary !h-9 !w-9 !p-0 !rounded-lg !text-xs font-black shadow-sm">${i}</button>`;
                } else {
                    html += `<button type="button" onclick="window.goToPage(${i})" class="btn-xs btn-secondary !h-9 !w-9 !p-0 !rounded-lg !text-xs font-black transition-all">${i}</button>`;
                }
            } else if (Math.abs(paginator.current_page - i) === 2) {
                html += `<span class="px-2 text-gray-400 font-bold">...</span>`;
            }
        }

        // Next Button
        if (paginator.current_page < paginator.last_page) {
            html += `<button type="button" onclick="window.goToPage(${paginator.current_page + 1})" class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs uppercase font-black tracking-wider transition-all">Next</button>`;
        } else {
            html += `<button disabled class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs opacity-50 cursor-not-allowed uppercase font-black tracking-wider">Next</button>`;
        }

        html += `</div>`;
        container.innerHTML = html;
    };

    window.showFeeStatusModal = function(id) {
        let item = window.sessionDataCache[id];
        if (!item) return;

        let details = item.status_details || {};
        let status = item.generation_status || 'pending';

        document.getElementById('modalSessionTitle').innerText = `Session ${item.session_name} — Fee Status Details`;
        document.getElementById('modalExpectedInvoices').innerText = (details.expected_invoices ?? 0).toLocaleString();
        document.getElementById('modalExistingInvoices').innerText = (details.existing_invoices ?? 0).toLocaleString();
        document.getElementById('modalMissingInvoices').innerText = (details.missing_invoices ?? 0).toLocaleString();
        document.getElementById('modalEligibleStudents').innerText = (details.eligible_students_count ?? details.active_students_count ?? 0).toLocaleString();
        document.getElementById('modalSetupsCount').innerText = `${details.monthly_setups_count ?? 0} active setup(s)`;
        document.getElementById('modalTotalInvoices').innerText = (details.total_invoices ?? 0).toLocaleString();
        document.getElementById('modalCancelledInvoices').innerText = (details.cancelled_invoices ?? 0).toLocaleString();
        document.getElementById('modalExtraInvoices').innerText = (details.extra_invoices ?? 0).toLocaleString();
        document.getElementById('modalDuplicateInvoices').innerText = (details.duplicate_invoices ?? 0).toLocaleString();

        let pct = details.expected_invoices > 0 
            ? Math.min(100, Math.round(((details.matched_invoices || details.existing_invoices || 0) / details.expected_invoices) * 100)) 
            : 0;
        document.getElementById('modalCoveragePercent').innerText = `${pct}% Generated`;

        let banner = document.getElementById('modalStatusBanner');
        let actionContainer = document.getElementById('modalActionContainer');

        if (status === 'completed') {
            banner.className = "p-4 rounded-2xl flex items-start gap-3 border bg-emerald-50/80 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-200";
            banner.innerHTML = `
                <svg class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <div class="text-xs font-black uppercase tracking-wider">Full Annual Coverage Completed</div>
                    <div class="text-xs font-medium mt-0.5 opacity-90">${details.reason || 'All 12 monthly invoices have been successfully generated for all eligible students.'}</div>
                </div>`;
            actionContainer.innerHTML = '';
        } else if (status === 'partial') {
            banner.className = "p-4 rounded-2xl flex items-start gap-3 border bg-amber-50/80 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/40 text-amber-800 dark:text-amber-200";
            banner.innerHTML = `
                <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <div class="text-xs font-black uppercase tracking-wider">Partial Annual Coverage (${details.missing_invoices ?? 0} Invoices Missing)</div>
                    <div class="text-xs font-medium mt-0.5 opacity-90">${details.reason || 'Some monthly fee slots have not yet been generated.'}</div>
                </div>`;
            actionContainer.innerHTML = `
                <button type="button" onclick="window.closeModal('feeStatusModal'); window.GenerateAnnualFees(${item.id}, '${item.session_name}');" class="h-10 px-5 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                    Generate Missing Invoices Now
                </button>`;
        } else if (status === 'pending_fee_setup') {
            banner.className = "p-4 rounded-2xl flex items-start gap-3 border bg-blue-50/80 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800/40 text-blue-800 dark:text-blue-200";
            banner.innerHTML = `
                <svg class="w-5 h-5 text-themeBlue mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <div>
                    <div class="text-xs font-black uppercase tracking-wider">Monthly Fee Setup Required</div>
                    <div class="text-xs font-medium mt-0.5 opacity-90">${details.reason || 'Please configure Monthly Fee setups for classes in this session to begin generation.'}</div>
                </div>`;
            actionContainer.innerHTML = `
                <a href="/fees/setup" class="h-10 px-5 bg-themeBlue text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center gap-2">
                    Go to Fee Setup
                </a>`;
        } else {
            banner.className = "p-4 rounded-2xl flex items-start gap-3 border bg-gray-50/80 dark:bg-gray-800/40 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300";
            banner.innerHTML = `
                <svg class="w-5 h-5 text-gray-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <div class="text-xs font-black uppercase tracking-wider">Generation Status: ${status}</div>
                    <div class="text-xs font-medium mt-0.5 opacity-90">${details.reason || 'Pending student enrollment and fee configuration.'}</div>
                </div>`;
            actionContainer.innerHTML = '';
        }

        window.openModal('feeStatusModal');
    };

    window.SaveData = async function() {
        let name = document.getElementById('SessionName').value;
        if(!name) return showAlert("Session Name is required!", "Field Required");
        try {
            let res = await axios.post("/ajax/sessions", {session_name: name}, getAuthHeaders());
            if(res.status === 200 || res.status === 201) {
                window.closeModal('createModal'); window.fetchList();
            }
        } catch (e) { showAlert("Failed to save the session.", "Save Failed"); }
    };

    window.FillUpdateForm = async function(id) {
        try {
            let res = await axios.get("/ajax/sessions/" + id, getAuthHeaders());
            let data = res.data.data || res.data.rows;
            document.getElementById('updateID').value = id;
            document.getElementById('SessionNameUpdate').value = data.session_name;
            window.openModal('updateModal');
        } catch (e) { showAlert("Failed to fetch session data.", "Error"); }
    };

    window.UpdateData = async function() {
        let id = document.getElementById('updateID').value;
        let name = document.getElementById('SessionNameUpdate').value;
        try {
            let res = await axios.put("/ajax/sessions/" + id, {session_name: name}, getAuthHeaders());
            if(res.status === 200) { window.closeModal('updateModal'); window.fetchList(); }
        } catch (e) { showAlert("Failed to update session details.", "Update Failed"); }
    };

    window.DeleteID = async function(id) {
        if (await showDanger('Delete Session', 'Are you sure you want to delete this session? This action cannot be undone.')) {
            try {
                let res = await axios.delete("/ajax/sessions/" + id, getAuthHeaders());
                if(res.status === 200) { 
                    window.fetchList(); 
                    showSuccess("Session deleted successfully.");
                }
            } catch (e) { 
                showAlert("Delete Failed!", "Error"); 
            }
        }
    };

    window.GenerateAnnualFees = async function(id, name) {
        if (await showConfirm('Generate Annual Fees', `Are you sure you want to trigger 12-month annual fee generation for session '${name}'? Existing invoices will be preserved.`)) {
            try {
                let res = await axios.post(`/ajax/sessions/${id}/generate-annual-fees`, {}, getAuthHeaders());
                if (res.data.status === 'success') {
                    showSuccess(res.data.message || 'Annual invoices generated successfully.');
                    window.fetchList();
                } else if (res.data.status === 'pending') {
                    showAlert(res.data.message || 'Prerequisites missing. Invoices will be automatically generated once students and monthly fee setups are configured.', 'Generation Pending');
                    window.fetchList();
                } else {
                    showAlert(res.data.message || 'Generation could not be completed.', 'Notice');
                    window.fetchList();
                }
            } catch (e) {
                let msg = e.response && e.response.data && e.response.data.message ? e.response.data.message : 'Failed to trigger annual fee generation.';
                showAlert(msg, 'Error');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => { window.fetchList(); });
</script>
@endpush