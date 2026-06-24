@extends('tyro-dashboard::layouts.admin')

@section('title', 'Teacher Management List')

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
    .smart-table-header { @apply py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Teacher List</h1>
            <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
        </div>
        
        <a href="{{ route('teacher.add') }}" class="inline-flex items-center justify-center px-8 py-3 bg-themeGreen hover:bg-green-900 text-white text-xs font-black rounded-xl shadow-xl shadow-themeGreen/20 transition-all hover:scale-105 active:scale-95 uppercase tracking-widest">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Teacher
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 mb-8">
        <div class="flex flex-col md:flex-row gap-6 items-end">
            <div class="flex-grow">
                <label class="block text-[10px] font-black text-themeGreen dark:text-green-500 uppercase tracking-widest mb-2 ml-1">Search Database</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="filter_search" placeholder="Enter Name, Email, Employee ID or Phone..." class="w-full border-2 border-gray-50 dark:border-gray-700 rounded-2xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white pl-12 pr-4 py-3.5 text-sm font-semibold focus:ring-4 focus:ring-themeGreen/10 focus:border-themeGreen outline-none transition placeholder-gray-400">
                </div>
            </div>
            
            <div class="flex gap-3 shrink-0 w-full md:w-auto">
                <button onclick="window.fetchList()" class="flex-1 md:flex-none bg-themeGreen hover:bg-green-900 text-white text-xs font-black py-4 px-10 rounded-2xl shadow-lg transition-all uppercase tracking-widest">
                    Search
                </button>
                <button onclick="window.resetFilter()" class="flex-1 md:flex-none bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-white text-xs font-black py-4 px-10 rounded-2xl transition-all uppercase tracking-widest">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl border border-gray-50 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                        <th class="smart-table-header text-center w-20">SL</th>
                        <th class="smart-table-header w-24">Avatar</th>
                        <th class="smart-table-header">Personnel Profile</th>
                        <th class="smart-table-header">Professional Rank</th>
                        <th class="smart-table-header text-right w-48">Manage</th>
                    </tr>
                </thead>
                <tbody id="tableList" class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <tr><td colspan="5" class="py-20 text-center text-gray-400 font-bold uppercase tracking-widest text-xs">Initializing Database...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="deleteModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/80 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-sm rounded-[2rem] shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="p-8 text-center">
            <div class="w-20 h-20 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
            <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-3 uppercase tracking-tighter">Are you sure?</h3>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest leading-relaxed">This teacher and their associated user account will be permanently removed from the system.</p>
            <input type="hidden" id="deleteID">
        </div>
        <div class="px-8 py-6 bg-gray-50 dark:bg-gray-900/50 flex gap-4">
            <button onclick="window.closeModal('deleteModal')" class="flex-1 px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl hover:bg-gray-50 transition-all">Cancel</button>
            <button onclick="window.ConfirmDelete()" class="flex-1 px-6 py-4 text-xs font-black text-white uppercase tracking-widest bg-themeRed hover:bg-red-800 rounded-2xl shadow-xl shadow-red-200/50 transition-all">Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    window.openModal = function(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('modal-active'); };
    window.closeModal = function(id) { document.getElementById(id).classList.remove('modal-active'); document.getElementById(id).classList.add('hidden'); };

    function getAuthHeaders() {
        return { 
            headers: { 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            } 
        };
    }

    window.resetFilter = function() {
        document.getElementById('filter_search').value = "";
        window.fetchList();
    };

    // Fetch and Display Teacher List (Updated for Flattened Data)
    window.fetchList = async function() {
        let list = document.getElementById('tableList');
        let search_text = document.getElementById('filter_search').value;
        let queryParams = new URLSearchParams({ search: search_text }).toString();

        try {
            list.innerHTML = `<tr><td colspan="5" class="py-20 text-center text-xs font-black text-gray-400 uppercase tracking-[0.3em] animate-pulse">Syncing Teachers...</td></tr>`;
            
            let res = await axios.get(`/ajax/teachers?${queryParams}`, getAuthHeaders());
            list.innerHTML = ''; 
            let data = res.data.teacherData || [];

            if (data.length === 0) {
                list.innerHTML = `<tr><td colspan="5" class="py-20 text-center text-sm font-bold text-red-500 uppercase tracking-widest">No matching records found in database.</td></tr>`;
                return;
            }

            data.forEach((item, index) => {
                // কন্ট্রোলারের ম্যাপ করা ডাটা সরাসরি ব্যবহার করা হচ্ছে
                let name = item.name || 'N/A';
                let email = item.email || 'No Email';
                let photoUrl = item.photo ? '/storage/' + item.photo : `https://ui-avatars.com/api/?name=${name.replace(' ', '+')}&background=1e4630&color=fff&bold=true`;

                let row = `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-all group border-b border-gray-50 dark:border-gray-700 last:border-0">
                        <td class="py-6 px-6 text-center text-sm font-black text-gray-400 group-hover:text-themeGreen transition-colors">${index + 1}</td>
                        <td class="py-6 px-6">
                            <div class="relative w-16 h-16 rounded-3xl overflow-hidden border-2 border-white dark:border-gray-700 shadow-xl group-hover:scale-110 transition-transform">
                                <img src="${photoUrl}" alt="${name}" class="w-full h-full object-cover">
                            </div>
                        </td>
                        <td class="py-6 px-6">
                            <div class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">${name}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] font-black text-gray-400 uppercase">ID:PIS-T-${item.id}</span>
                                <span class="text-gray-300">•</span>
                                <span class="text-[10px] font-black text-themeGreen dark:text-green-500 uppercase">${email}</span>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400 mt-2 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                ${item.phone || 'N/A'}
                            </div>
                        </td>
                        <td class="py-6 px-6">
                            <div class="px-4 py-1.5 bg-themeGreen/10 dark:bg-green-500/10 rounded-full inline-block">
                                <span class="text-[11px] font-black text-themeGreen dark:text-green-500 uppercase tracking-widest">${item.designation || 'Staff'}</span> 
                            </div>
                            <div class="text-[10px] font-black text-gray-400 uppercase mt-2 tracking-tighter">Department: <span class="text-gray-600 dark:text-gray-300">${item.department || 'General'}</span></div>
                        </td>
                        <td class="py-6 px-6 text-right space-x-2 whitespace-nowrap">
                            <a href="/teacher/view/${item.id}" class="inline-flex px-4 py-2 text-[10px] font-black uppercase tracking-widest text-blue-600 hover:bg-blue-50 rounded-xl transition-all">View</a>
                            <a href="/teacher/edit/${item.id}" class="inline-flex px-4 py-2 text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">Edit</a>
                            <button onclick="window.DeleteID(${item.id})" class="inline-flex px-4 py-2 text-[10px] font-black uppercase tracking-widest text-red-600 hover:bg-red-50 rounded-xl transition-all">Delete</button>
                        </td>
                    </tr>`;
                list.innerHTML += row;
            });
        } catch (e) { 
            console.error(e);
            list.innerHTML = `<tr><td colspan="5" class="py-20 text-center text-sm font-black text-red-600 uppercase tracking-widest animate-bounce">System Error: Could not sync with database.</td></tr>`; 
        }
    };

    window.DeleteID = function(id) { document.getElementById('deleteID').value = id; window.openModal('deleteModal'); };

    window.ConfirmDelete = async function() {
        let id = document.getElementById('deleteID').value;
        try {
            let res = await axios.delete("/ajax/teachers/" + id, getAuthHeaders());
            if(res.status === 200) { window.closeModal('deleteModal'); window.fetchList(); }
        } catch (e) { alert("Authorization Error: Delete Failed!"); }
    };

    let typingTimer;
    document.getElementById('filter_search').addEventListener('input', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => window.fetchList(), 400); 
    });

    document.addEventListener('DOMContentLoaded', () => window.fetchList());
</script>
@endpush