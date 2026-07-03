@extends('tyro-dashboard::layouts.admin')

@section('title', 'Staff Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Staff List
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Search, filter, view and manage all school staff members and teaching personnel</p>
        </div>
        
        <a href="{{ route('teacher.add') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Staff
        </a>
    </div>

    <!-- Search Bar Wrapper Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8">
        <div class="flex flex-col md:flex-row gap-6 items-end">
            <div class="flex-grow w-full">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Search Database</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="filter_search" placeholder="Enter Name, Email, Employee ID or Phone..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 pl-11 placeholder-gray-400">
                </div>
            </div>
            
            <div class="flex gap-3 shrink-0 w-full md:w-auto">
                <button onclick="window.fetchList()" class="flex-1 md:flex-none h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black px-8 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest active:scale-95 flex items-center justify-center">
                    Search
                </button>
                <button onclick="window.resetFilter()" class="flex-1 md:flex-none h-11 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black px-8 rounded-xl transition-all uppercase tracking-widest active:scale-95 flex items-center justify-center">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">SL</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-24">Avatar</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Personnel Profile</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Professional Rank</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-48">Manage</th>
                    </tr>
                </thead>
                <tbody id="tableList">
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-450 dark:text-gray-500 font-bold uppercase tracking-wider">
                            <div class="flex flex-col items-center gap-3 animate-pulse justify-center">
                                <div class="h-4 w-32 bg-gray-200 dark:bg-gray-800 rounded-md"></div>
                                <span class="text-xs">Initializing Database...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
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

    window.fetchList = async function() {
        let list = document.getElementById('tableList');
        let search_text = document.getElementById('filter_search').value;
        let queryParams = new URLSearchParams({ search: search_text }).toString();

        try {
            list.innerHTML = `
                <tr>
                    <td colspan="5" class="py-12">
                        <div class="flex flex-col items-center gap-3 animate-pulse justify-center">
                            <div class="h-4 w-32 bg-gray-200 dark:bg-gray-800 rounded-md"></div>
                            <span class="text-xs text-gray-455 dark:text-gray-500 font-bold uppercase tracking-widest">Syncing Staff Members...</span>
                        </div>
                    </td>
                </tr>
            `;
            
            let res = await axios.get(`/ajax/teachers?${queryParams}`, getAuthHeaders());
            list.innerHTML = ''; 
            let data = res.data.teacherData || [];

            if (data.length === 0) {
                list.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-sm font-bold text-red-500 uppercase tracking-widest">No matching records found in database.</td></tr>`;
                return;
            }

            data.forEach((item, index) => {
                let name = item.name || 'N/A';
                let email = item.email || 'No Email';
                let photoUrl = item.photo ? '/storage/' + item.photo : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=008ED6&color=fff&bold=true`;

                let row = `
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">${index + 1}</td>
                        <td class="py-4 px-4">
                            <div class="relative w-11 h-11 rounded-xl overflow-hidden border border-gray-100 dark:border-white/[0.06] shadow-sm">
                                <img src="${photoUrl}" alt="${name}" class="w-full h-full object-cover">
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">${name}</div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider">ID: PIS-T-${item.id}</span>
                                <span class="text-gray-300 dark:text-gray-700">•</span>
                                <span class="text-[9px] font-black text-themeBlue uppercase tracking-wider">${email}</span>
                            </div>
                            <div class="text-[9px] font-bold text-gray-455 dark:text-gray-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                ${item.phone || 'N/A'}
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="px-3 py-1 bg-green-50 dark:bg-green-950/20 text-themeGreen dark:text-green-455 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block">
                                ${item.designation || 'Staff'}
                            </div>
                            <div class="text-[9px] font-black text-gray-400 dark:text-gray-550 uppercase mt-1.5 tracking-wider">Department: <span class="text-gray-650 dark:text-gray-350 font-bold">${item.department || 'General'}</span></div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center justify-end gap-2.5">
                                <!-- View Button -->
                                <a href="/teacher/view/${item.id}" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <!-- Edit Button -->
                                <a href="/teacher/edit/${item.id}" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Staff">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <!-- Delete Button -->
                                <button onclick="window.DeleteID(${item.id})" class="action-btn text-red-650 hover:text-red-800 hover:border-red-600" title="Delete Staff">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>`;
                list.innerHTML += row;
            });
        } catch (e) { 
            console.error(e);
            list.innerHTML = `<tr><td colspan="5" class="py-20 text-center text-sm font-black text-red-600 uppercase tracking-widest">System Error: Could not sync with database.</td></tr>`; 
        }
    };

    window.DeleteID = async function(id) {
        if (await showDanger('Delete Staff', 'Are you sure you want to delete this staff and their associated user account? This action cannot be undone.')) {
            try {
                let res = await axios.delete("/ajax/teachers/" + id, getAuthHeaders());
                if(res.status === 200) { 
                    window.fetchList(); 
                    showSuccess("Staff member deleted successfully.");
                }
            } catch (e) { 
                showAlert("Authorization Error: Delete Failed!", "Delete Failed"); 
            }
        }
    };

    let typingTimer;
    document.getElementById('filter_search').addEventListener('input', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => window.fetchList(), 400); 
    });

    document.addEventListener('DOMContentLoaded', () => window.fetchList());
</script>
@endpush