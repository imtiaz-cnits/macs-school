@extends('tyro-dashboard::layouts.admin')

@section('title', 'Shift Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } } } }
</script>
<style>
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Shift Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage school shifts (e.g., Morning, Day) for Pabna International School</p>
        </div>
        <button onclick="window.openModal('createModal')" class="inline-flex items-center justify-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors w-full md:w-auto">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Shift
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-6 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20">SL</th>
                        <th class="py-4 px-6 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shift Name</th>
                        <th class="py-4 px-6 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right w-48">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableList" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr><td colspan="3" class="py-12 text-center text-gray-500">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="createModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Add New Shift</h3>
            <button onclick="window.closeModal('createModal')" class="text-gray-400 hover:text-red-500 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <div class="p-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shift Name <span class="text-red-500">*</span></label>
            <input type="text" id="ShiftName" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="e.g. Morning">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3">
            <button onclick="window.closeModal('createModal')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
            <button onclick="window.SaveData()" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Save Shift</button>
        </div>
    </div>
</div>

<div id="updateModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Update Shift</h3>
            <button onclick="window.closeModal('updateModal')" class="text-gray-400 hover:text-red-500 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="updateID">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shift Name <span class="text-red-500">*</span></label>
            <input type="text" id="ShiftNameUpdate" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3">
            <button onclick="window.closeModal('updateModal')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
            <button onclick="window.UpdateData()" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Update Changes</button>
        </div>
    </div>
</div>

<div id="deleteModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700 text-center">
        <div class="p-6">
            <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Are you sure?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">You won't be able to revert this action.</p>
            <input type="hidden" id="deleteID">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-center gap-3">
            <button onclick="window.closeModal('deleteModal')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors w-full">Cancel</button>
            <button onclick="window.ConfirmDelete()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors w-full shadow-sm">Yes, Delete</button>
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
        if(id === 'createModal') document.getElementById('ShiftName').value = '';
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
            let res = await axios.get("/ajax/shifts", getAuthHeaders());
            list.innerHTML = ''; 
            let data = res.data.shiftData || res.data.data || res.data;

            if (!data || data.length === 0) {
                list.innerHTML = `<tr><td colspan="3" class="py-12 text-center text-gray-500">No shifts found. Add a new shift.</td></tr>`;
                return;
            }

            data.forEach((item, index) => {
                let row = `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-400">${index + 1}</td>
                        <td class="py-4 px-6 text-sm font-medium text-gray-900 dark:text-gray-100">${item.shift_name}</td>
                        <td class="py-4 px-6 text-sm text-right">
                            <button onclick="window.FillUpdateForm(${item.id})" class="text-indigo-600 hover:text-indigo-800 font-medium mr-4">Edit</button>
                            <button onclick="window.DeleteID(${item.id})" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                        </td>
                    </tr>`;
                list.innerHTML += row;
            });
        } catch (e) { list.innerHTML = `<tr><td colspan="3" class="py-12 text-center text-red-500">Error loading data.</td></tr>`; }
    };

    window.SaveData = async function() {
        let name = document.getElementById('ShiftName').value;
        if(!name) return alert("Shift Name required!");
        try {
            let res = await axios.post("/ajax/shifts", {shift_name: name}, getAuthHeaders());
            if(res.status === 200 || res.status === 201) {
                window.closeModal('createModal'); window.fetchList();
            }
        } catch (e) { alert("Save Failed!"); }
    };

    window.FillUpdateForm = async function(id) {
        try {
            let res = await axios.get("/ajax/shifts/" + id, getAuthHeaders());
            let data = res.data.data || res.data.rows;
            document.getElementById('updateID').value = id;
            document.getElementById('ShiftNameUpdate').value = data.shift_name;
            window.openModal('updateModal');
        } catch (e) { alert("Data fetch error!"); }
    };

    window.UpdateData = async function() {
        let id = document.getElementById('updateID').value;
        let name = document.getElementById('ShiftNameUpdate').value;
        try {
            let res = await axios.put("/ajax/shifts/" + id, {shift_name: name}, getAuthHeaders());
            if(res.status === 200) { window.closeModal('updateModal'); window.fetchList(); }
        } catch (e) { alert("Update Failed!"); }
    };

    window.DeleteID = function(id) {
        document.getElementById('deleteID').value = id;
        window.openModal('deleteModal');
    };

    window.ConfirmDelete = async function() {
        let id = document.getElementById('deleteID').value;
        try {
            let res = await axios.delete("/ajax/shifts/" + id, getAuthHeaders());
            if(res.status === 200) { window.closeModal('deleteModal'); window.fetchList(); }
        } catch (e) { alert("Delete Failed!"); }
    };

    document.addEventListener('DOMContentLoaded', () => { window.fetchList(); });
</script>
@endpush