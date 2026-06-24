@extends('tyro-dashboard::layouts.admin')

@section('title', 'Subject Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#1e4630', themeRed: '#cc0000' },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    /* Select বক্সের ভেতরে ডার্ক মোডে টেক্সট সাদা রাখার জন্য */
    select option { background: #1f2937; color: white; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1200px] mx-auto">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Global Subjects</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage global subjects for the school</p>
        </div>
        
        <button onclick="window.openModal('addSubjectModal')" class="inline-flex items-center justify-center px-5 py-2.5 bg-themeGreen hover:bg-green-900 text-white text-sm font-bold rounded-lg shadow-sm transition-colors w-full md:w-auto">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Subject
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6 flex gap-4 items-center">
        <div class="flex-grow relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" id="filter_search" placeholder="Search by Subject Name or Code..." class="w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white pl-10 pr-3 py-3 text-sm focus:ring-2 focus:ring-themeGreen outline-none transition">
        </div>
        <button onclick="window.resetFilter()" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white text-sm font-bold py-3 px-6 rounded-lg shadow-sm transition shrink-0">Reset</button>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase w-16">SL</th>
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase">Subject Info</th>
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase">Type</th>
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase text-right w-48">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableList" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr><td colspan="4" class="py-12 text-center text-gray-500 font-medium">Loading subjects...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="addSubjectModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-lg rounded-xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
            <h3 class="text-xl font-bold text-themeGreen dark:text-green-500">Add New Subject</h3>
            <button onclick="window.closeModal('addSubjectModal')" class="text-gray-400 hover:text-red-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </button>
        </div>
        <form id="addSubjectForm" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-gray-900 dark:text-white">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-1">Subject Name *</label>
                    <input type="text" name="subject_name" required placeholder="e.g. Mathematics" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 px-3 py-2.5 outline-none focus:ring-1 focus:ring-themeGreen shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Subject Code</label>
                    <input type="text" name="subject_code" placeholder="e.g. MAT-101" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 px-3 py-2.5 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Subject Type</label>
                    <select name="subject_type" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 px-3 py-2.5 outline-none shadow-sm">
                        <option value="Theory">Theory</option>
                        <option value="Practical">Practical</option>
                        <option value="Objective">Objective</option>
                        <option value="Both">Both</option>
                    </select>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="window.closeModal('addSubjectModal')" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg font-bold">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-themeGreen hover:bg-green-900 text-white rounded-lg font-bold shadow-lg transition-colors">Save Subject</button>
            </div>
        </form>
    </div>
</div>

<div id="editSubjectModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-lg rounded-xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
            <h3 class="text-xl font-bold text-indigo-600 dark:text-indigo-400">Edit Subject</h3>
            <button onclick="window.closeModal('editSubjectModal')" class="text-gray-400 hover:text-red-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            </button>
        </div>
        <form id="editSubjectForm" class="p-6">
            <input type="hidden" id="edit_subject_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-gray-900 dark:text-white">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-1">Subject Name *</label>
                    <input type="text" id="edit_subject_name" name="subject_name" required class="w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 px-3 py-2.5 outline-none focus:ring-1 focus:ring-indigo-500 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Subject Code</label>
                    <input type="text" id="edit_subject_code" name="subject_code" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 px-3 py-2.5 outline-none shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Subject Type</label>
                    <select name="subject_type" id="edit_subject_type" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 px-3 py-2.5 outline-none shadow-sm">
                        <option value="Theory">Theory</option>
                        <option value="Practical">Practical</option>
                        <option value="Objective">Objective</option>
                        <option value="Both">Both</option>
                    </select>
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-3">
                <button type="button" onclick="window.closeModal('editSubjectModal')" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white font-bold rounded-lg">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-lg transition-colors">Update Subject</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4 text-center">
    <div class="bg-white dark:bg-gray-800 w-full max-w-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4 text-red-600 dark:text-red-500">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Are you sure?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">This global subject will be permanently deleted.</p>
        <input type="hidden" id="deleteID">
        <div class="mt-6 flex justify-center gap-3">
            <button onclick="window.closeModal('deleteModal')" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white font-bold rounded-lg w-full">Cancel</button>
            <button onclick="window.ConfirmDelete()" class="px-5 py-2.5 bg-themeRed hover:bg-red-800 text-white rounded-lg w-full font-bold shadow-lg transition-colors">Yes, Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    window.openModal = id => { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('modal-active'); };
    window.closeModal = id => { document.getElementById(id).classList.remove('modal-active'); document.getElementById(id).classList.add('hidden'); };

    const getAuthHeaders = () => ({ 
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
    });

    // ১. সাবজেক্ট লিস্ট ফেচ করা
    window.fetchList = async function() {
        let list = document.getElementById('tableList');
        let search = document.getElementById('filter_search').value;
        let q = new URLSearchParams({ search }).toString();

        try {
            list.innerHTML = `<tr><td colspan="4" class="py-12 text-center text-gray-400 italic">Searching...</td></tr>`;
            let res = await axios.get(`/ajax/subjects?${q}`, getAuthHeaders());
            let data = res.data.subjectData || [];
            list.innerHTML = data.length ? '' : `<tr><td colspan="4" class="py-12 text-center text-red-500">No subjects found.</td></tr>`;

            data.forEach((item, index) => {
                list.innerHTML += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-6 text-gray-600 dark:text-gray-400">${index + 1}</td>
                        <td class="py-4 px-6">
                            <div class="text-lg font-black text-gray-900 dark:text-gray-100">${item.subject_name}</div>
                            <div class="text-sm font-medium text-gray-500 mt-1">Code: <span class="text-gray-700 dark:text-gray-300 font-bold">${item.subject_code || 'N/A'}</span></div>
                        </td>
                        <td class="py-4 px-6 text-sm">
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-full border border-gray-200 dark:border-gray-600">${item.subject_type}</span>
                        </td>
                        <td class="py-4 px-6 text-right space-x-4 whitespace-nowrap">
                            <button onclick="window.EditSubject(${item.id})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-bold uppercase text-xs tracking-wider">Edit</button>
                            <button onclick="window.DeleteID(${item.id})" class="text-themeRed dark:text-red-400 hover:text-red-800 font-bold uppercase text-xs tracking-wider">Delete</button>
                        </td>
                    </tr>`;
            });
        } catch (e) { list.innerHTML = `<tr><td colspan="4" class="py-12 text-center text-red-500 font-bold">Error loading data.</td></tr>`; }
    };

    // ২. সাবজেক্ট এডিট (ডাটা ফিল করা)
    window.EditSubject = async function(id) {
        try {
            let res = await axios.get(`/ajax/subjects/${id}/edit`, getAuthHeaders());
            let s = res.data.data;
            document.getElementById('edit_subject_id').value = s.id;
            document.getElementById('edit_subject_name').value = s.subject_name;
            document.getElementById('edit_subject_code').value = s.subject_code || '';
            document.getElementById('edit_subject_type').value = s.subject_type;
            
            window.openModal('editSubjectModal');
        } catch (e) { alert("Failed to fetch data."); }
    };

    // ৩. সেভ এবং আপডেট
    const handleForm = async (e, url, method, modalId) => {
        e.preventDefault();
        let data = Object.fromEntries(new FormData(e.target).entries());
        try {
            let res = await axios({ method, url, data, ...getAuthHeaders() });
            if (res.status === 200 || res.status === 201) {
                // alert(res.data.message || "Success!"); // Alert সরালে UX আরো ভালো হবে
                window.closeModal(modalId);
                window.fetchList();
                e.target.reset();
            }
        } catch (err) { alert("Action Failed: " + (err.response.data.message || "Error")); }
    };

    document.getElementById('addSubjectForm').onsubmit = e => handleForm(e, '/ajax/subjects', 'post', 'addSubjectModal');
    document.getElementById('editSubjectForm').onsubmit = e => {
        let id = document.getElementById('edit_subject_id').value;
        handleForm(e, `/ajax/subjects/${id}`, 'put', 'editSubjectModal');
    };

    // ৪. ডিলিট লজিক
    window.DeleteID = id => { document.getElementById('deleteID').value = id; window.openModal('deleteModal'); };
    window.ConfirmDelete = async () => {
        let id = document.getElementById('deleteID').value;
        try {
            await axios.delete(`/ajax/subjects/${id}`, getAuthHeaders());
            window.closeModal('deleteModal');
            window.fetchList();
        } catch (e) { alert("Delete failed!"); }
    };

    window.resetFilter = () => { document.getElementById('filter_search').value = ""; window.fetchList(); };
    
    // Live Search
    let typingTimer;
    document.getElementById('filter_search').oninput = () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(window.fetchList, 400);
    };

    // Initial Load
    document.addEventListener('DOMContentLoaded', window.fetchList);
</script>
@endpush