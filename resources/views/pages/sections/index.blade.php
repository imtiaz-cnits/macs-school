@extends('tyro-dashboard::layouts.admin')

@section('title', 'Section Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Section Management
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Manage academic sections and departments for student enrollment grouping</p>
        </div>
        <button onclick="window.openModal('createModal')" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Section
        </button>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">SL</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Section Name</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-48">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableList">
                    <tr>
                        <td colspan="3" class="py-12">
                            <div class="flex flex-col items-center gap-3 animate-pulse justify-center">
                                <div class="h-4 w-24 bg-gray-200 dark:bg-gray-800 rounded-md"></div>
                                <span class="text-xs text-gray-450 dark:text-gray-500 font-bold uppercase tracking-widest">Loading Sections...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Add New Section</h3>
            <button onclick="window.closeModal('createModal')" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Section Name <span class="text-red-500 ml-0.5">*</span></label>
            <input type="text" id="SectionName" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. Section A">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-themeDark/50 flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.05]">
            <button onclick="window.closeModal('createModal')" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
            <button onclick="window.SaveData()" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Save Section</button>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div id="updateModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Update Section</h3>
            <button onclick="window.closeModal('updateModal')" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <input type="hidden" id="updateID">
            <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Section Name <span class="text-red-500 ml-0.5">*</span></label>
            <input type="text" id="SectionNameUpdate" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-themeDark/50 flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.05]">
            <button onclick="window.closeModal('updateModal')" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
            <button onclick="window.UpdateData()" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Update Changes</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    window.openModal = function(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('modal-active');
        if(id === 'createModal') document.getElementById('SectionName').value = '';
    };

    window.closeModal = function(id) {
        document.getElementById(id).classList.remove('modal-active');
        document.getElementById(id).classList.add('hidden');
    };

    function getAuthHeaders() {
        return { 
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            } 
        };
    }

    window.fetchList = async function() {
        let list = document.getElementById('tableList');
        try {
            let res = await axios.get("/ajax/sections", getAuthHeaders());
            list.innerHTML = ''; 
            let data = res.data.sectionData || res.data.data || res.data;

            if (!data || data.length === 0) {
                list.innerHTML = `<tr><td colspan="3" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">No sections found. Add a new section.</td></tr>`;
                return;
            }

            data.forEach((item, index) => {
                let row = `
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">${index + 1}</td>
                        <td class="py-4 px-4 text-sm font-bold text-gray-900 dark:text-gray-100">${item.section_name}</td>
                        <td class="py-4 px-4">
                            <div class="flex items-center justify-end gap-2.5">
                                <!-- Edit Button -->
                                <button onclick="window.FillUpdateForm(${item.id})" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Section">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <!-- Delete Button -->
                                <button onclick="window.DeleteID(${item.id})" class="action-btn text-red-650 hover:text-red-800 hover:border-red-600" title="Delete Section">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>`;
                list.innerHTML += row;
            });
        } catch (e) { list.innerHTML = `<tr><td colspan="3" class="py-12 text-center text-red-500">Error loading data.</td></tr>`; }
    };

    window.SaveData = async function() {
        let name = document.getElementById('SectionName').value;
        if(!name) return showAlert("Section Name is required!", "Field Required");
        try {
            let res = await axios.post("/ajax/sections", {section_name: name}, getAuthHeaders());
            if(res.status === 200 || res.status === 201) {
                window.closeModal('createModal'); window.fetchList();
            }
        } catch (e) { showAlert("Failed to save the section.", "Save Failed"); }
    };

    window.FillUpdateForm = async function(id) {
        try {
            let res = await axios.get("/ajax/sections/" + id, getAuthHeaders());
            let data = res.data.data || res.data.rows;
            document.getElementById('updateID').value = id;
            document.getElementById('SectionNameUpdate').value = data.section_name;
            window.openModal('updateModal');
        } catch (e) { showAlert("Failed to fetch section data.", "Error"); }
    };

    window.UpdateData = async function() {
        let id = document.getElementById('updateID').value;
        let name = document.getElementById('SectionNameUpdate').value;
        try {
            let res = await axios.put("/ajax/sections/" + id, {section_name: name}, getAuthHeaders());
            if(res.status === 200) { window.closeModal('updateModal'); window.fetchList(); }
        } catch (e) { showAlert("Failed to update section details.", "Update Failed"); }
    };

    window.DeleteID = async function(id) {
        if (await showDanger('Delete Section', 'Are you sure you want to delete this section? This action cannot be undone.')) {
            try {
                let res = await axios.delete("/ajax/sections/" + id, getAuthHeaders());
                if(res.status === 200) { 
                    window.fetchList(); 
                    showSuccess("Section deleted successfully.");
                }
            } catch (e) { 
                showAlert("Delete Failed!", "Error"); 
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => { window.fetchList(); });
</script>
@endpush