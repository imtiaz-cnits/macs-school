@extends('tyro-dashboard::layouts.admin')

@section('title', 'Student List')

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
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<a href="{{ route('students.index') }}" class="text-themeGreen font-bold hover:underline">Student Management</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Student List</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-7 gap-4 items-end">
            <div class="xl:col-span-1">
                <label class="block text-xs font-bold text-themeGreen dark:text-green-500 mb-1.5">Search Student</label>
                <input type="text" id="filter_search" placeholder="Name, ID or Mobile..." class="w-full border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-themeGreen outline-none transition placeholder-gray-400">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Session</label>
                <select id="filter_session" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-themeGreen outline-none transition">
                    <option value="">All Sessions</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Class</label>
                <select id="filter_class" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-themeGreen outline-none transition">
                    <option value="">All Classes</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Branch</label>
                <select id="filter_branch" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-themeGreen outline-none transition">
                    <option value="">All Branches</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Shift</label>
                <select id="filter_shift" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-themeGreen outline-none transition">
                    <option value="">All Shifts</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Section</label>
                <select id="filter_section" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-themeGreen outline-none transition">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Gender</label>
                <select id="filter_gender" class="w-full border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-1 focus:ring-themeGreen outline-none transition">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Modern Premium Export Toolbar -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="p-2.5 bg-themeGreen/10 text-themeGreen dark:bg-green-500/10 dark:text-green-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </span>
                <div>
                    <h3 class="font-bold text-gray-800 dark:text-white text-base">Registered Students List</h3>
                </div>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button onclick="window.exportToExcel()" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 text-sm font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/50 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer shadow-sm">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download Excel
                </button>
                <button onclick="window.exportToPDF()" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 text-sm font-bold text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/50 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer shadow-sm">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Download PDF
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-16">SL</th>
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-24">Photo</th>
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Student Info</th>
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Academic Details</th>
                        <th class="py-4 px-6 text-sm font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider text-right w-40">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableList" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr><td colspan="5" class="py-12 text-center text-base text-gray-500">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div id="pagination-container" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex flex-col md:flex-row justify-between items-center gap-4">
        </div>
    </div>

</div>

<div id="deleteModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-gray-900/75 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-gray-800 w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700 text-center">
        <div class="p-6">
            <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Are you sure?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">This student's data will be permanently deleted.</p>
            <input type="hidden" id="deleteID">
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-center gap-3">
            <button onclick="window.closeModal('deleteModal')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 transition-colors w-full">Cancel</button>
            <button onclick="window.ConfirmDelete()" class="px-4 py-2 text-sm font-medium text-white bg-themeRed hover:bg-red-800 rounded-lg transition-colors w-full shadow-sm">Yes, Delete</button>
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
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            } 
        };
    }

    async function loadFilterOptions() {
        try {
            const [branches, classes, sections, sessions, shifts] = await Promise.all([
                axios.get('/ajax/branches', getAuthHeaders()),
                axios.get('/ajax/classes', getAuthHeaders()),
                axios.get('/ajax/sections', getAuthHeaders()),
                axios.get('/ajax/sessions', getAuthHeaders()),
                axios.get('/ajax/shifts', getAuthHeaders()) 
            ]);

            let bSelect = document.getElementById('filter_branch');
            (branches.data.branchData || []).forEach(item => bSelect.add(new Option(item.branch_name, item.id)));

            let cSelect = document.getElementById('filter_class');
            (classes.data.classData || []).forEach(item => cSelect.add(new Option(item.class_name, item.id)));

            let secSelect = document.getElementById('filter_section');
            (sections.data.sectionData || []).forEach(item => secSelect.add(new Option(item.section_name, item.id)));

            let sessSelect = document.getElementById('filter_session');
            (sessions.data.sessionData || []).forEach(item => sessSelect.add(new Option(item.session_name, item.id)));
            
            let shiftSelect = document.getElementById('filter_shift');
            (shifts.data.shiftData || []).forEach(item => shiftSelect.add(new Option(item.shift_name, item.id)));

        } catch (e) { console.error("Filter load error", e); }
    }

    // Fetch and display students with pagination
    window.fetchList = async function(page = 1) {
        let list = document.getElementById('tableList');
        
        let search_text = document.getElementById('filter_search').value;
        let branch_id = document.getElementById('filter_branch').value;
        let class_id = document.getElementById('filter_class').value;
        let section_id = document.getElementById('filter_section').value;
        let session_id = document.getElementById('filter_session').value;
        let shift_id = document.getElementById('filter_shift').value; 
        let gender = document.getElementById('filter_gender').value; // 🚨 নতুন ভ্যালু

        let queryParams = new URLSearchParams({
            search: search_text,
            branch_id: branch_id,
            class_id: class_id,
            section_id: section_id,
            session_year_id: session_id,
            shift_id: shift_id,
            gender: gender, // 🚨 URL-এ প্যারামিটার পাঠানো হলো
            page: page
        }).toString();

        try {
            list.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-base text-gray-500 font-medium">Loading Students...</td></tr>`;
            
            let res = await axios.get(`/ajax/students?${queryParams}`, getAuthHeaders());
            list.innerHTML = ''; 
            
            // Laravel Pagination Object
            let paginator = res.data.studentData; 
            let data = paginator.data || [];

            if (data.length === 0) {
                list.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-base text-red-500 font-medium">No students found.</td></tr>`;
                document.getElementById('pagination-container').innerHTML = ''; 
                return;
            }

            let startingSl = (paginator.current_page - 1) * paginator.per_page; 

            data.forEach((item, index) => {
                let photoUrl = `https://ui-avatars.com/api/?name=${item.student_name}&background=1e4630&color=fff`;
                
                if (item.photo) {
                    if (item.photo.startsWith('img/')) {
                        photoUrl = '/' + item.photo;
                    } else {
                        photoUrl = '/storage/' + item.photo;
                    }
                }

                let className = item.school_class ? item.school_class.class_name : 'N/A';
                let sectionName = item.section ? item.section.section_name : 'N/A';
                let branchName = item.branch ? item.branch.branch_name : 'N/A';
                let shiftName = item.shift ? item.shift.shift_name : 'N/A';
                let rollNumber = item.roll_number ? item.roll_number : 'N/A';
                let studentGender = item.gender ? item.gender : 'N/A'; // জেন্ডার ভিউয়ের জন্য

                let row = `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="py-4 px-6 text-base font-medium text-gray-600 dark:text-gray-400">${startingSl + index + 1}</td>
                        <td class="py-4 px-6">
                            <img src="${photoUrl}" alt="Photo" class="w-14 h-14 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 shadow-sm">
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-lg font-bold text-gray-900 dark:text-gray-100">${item.student_name}</div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">ID: <span class="text-gray-700 dark:text-gray-300">${item.student_identity || 'N/A'}</span></div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Mob: <span class="text-gray-700 dark:text-gray-300">${item.guardian_mobile}</span> | Gender: <span class="text-gray-700 dark:text-gray-300">${studentGender}</span></div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-base text-gray-800 dark:text-gray-200">
                                <span class="font-bold text-themeGreen dark:text-green-500">Class: ${className}</span> 
                                <span class="ml-2 px-2.5 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm font-bold text-gray-800 dark:text-gray-200">Roll: ${rollNumber}</span>
                            </div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1.5 flex gap-3">
                                <span>Section: <span class="text-gray-700 dark:text-gray-300">${sectionName}</span></span>
                                <span>Shift: <span class="text-gray-700 dark:text-gray-300">${shiftName}</span></span>
                            </div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-0.5">Branch: <span class="text-gray-700 dark:text-gray-300">${branchName}</span></div>
                        </td>
                        <td class="py-4 px-6 text-right space-x-3 text-base whitespace-nowrap">
                            <a href="/student/view/${item.id}" class="text-blue-600 hover:text-blue-800 font-bold transition-colors">View</a>
                            <a href="/student/edit/${item.id}" class="text-indigo-600 hover:text-indigo-800 font-bold transition-colors">Edit</a>
                            <button onclick="window.DeleteID(${item.id})" class="text-red-600 hover:text-red-800 font-bold transition-colors">Delete</button>
                        </td>
                    </tr>`;
                list.innerHTML += row;
            });

            // Render Pagination Logic
            renderPagination(paginator);

        } catch (e) { 
            console.error(e);
            list.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-lg text-red-500 font-bold">Error loading student data.</td></tr>`; 
        }
    };

    // UI builder for pagination
    function renderPagination(paginator) {
        let container = document.getElementById('pagination-container');
        if (!paginator || paginator.last_page <= 1) {
            container.innerHTML = `<div class="text-sm text-gray-500 font-medium">Showing ${paginator.total || 0} entries</div>`;
            return;
        }

        let html = `<div class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-4 md:mb-0">
                        Showing <span class="font-bold">${paginator.from || 0}</span> to <span class="font-bold">${paginator.to || 0}</span> of <span class="font-bold">${paginator.total}</span> entries
                    </div>`;
        
        html += `<div class="flex items-center space-x-1">`;

        // Prev Button
        if (paginator.current_page > 1) {
            html += `<button onclick="window.fetchList(${paginator.current_page - 1})" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Prev</button>`;
        } else {
            html += `<button disabled class="px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-800/50 text-sm font-bold text-gray-400 dark:text-gray-600 cursor-not-allowed">Prev</button>`;
        }

        // Page Numbers
        for(let i=1; i<=paginator.last_page; i++) {
            if (i === 1 || i === paginator.last_page || Math.abs(paginator.current_page - i) <= 1) {
                if (i === paginator.current_page) {
                    html += `<button class="px-3 py-1.5 border border-themeGreen rounded-md bg-themeGreen text-sm font-bold text-white shadow-sm">${i}</button>`;
                } else {
                    html += `<button onclick="window.fetchList(${i})" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">${i}</button>`;
                }
            } else if (Math.abs(paginator.current_page - i) === 2) {
                html += `<span class="px-2 py-1 text-gray-400">...</span>`;
            }
        }

        // Next Button
        if (paginator.current_page < paginator.last_page) {
            html += `<button onclick="window.fetchList(${paginator.current_page + 1})" class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Next</button>`;
        } else {
            html += `<button disabled class="px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-800/50 text-sm font-bold text-gray-400 dark:text-gray-600 cursor-not-allowed">Next</button>`;
        }

        html += `</div>`;
        container.innerHTML = html;
    }

    // Actions
    window.DeleteID = function(id) { document.getElementById('deleteID').value = id; window.openModal('deleteModal'); };

    window.ConfirmDelete = async function() {
        let id = document.getElementById('deleteID').value;
        try {
            let res = await axios.delete("/ajax/students/" + id, getAuthHeaders());
            if(res.status === 200) { window.closeModal('deleteModal'); window.fetchList(); }
        } catch (e) { alert("Delete Failed!"); }
    };

    window.exportToExcel = function() {
        let search_text = document.getElementById('filter_search').value;
        let branch_id = document.getElementById('filter_branch').value;
        let class_id = document.getElementById('filter_class').value;
        let section_id = document.getElementById('filter_section').value;
        let session_id = document.getElementById('filter_session').value;
        let shift_id = document.getElementById('filter_shift').value; 
        let gender = document.getElementById('filter_gender').value;

        let queryParams = new URLSearchParams({
            search: search_text,
            branch_id: branch_id,
            class_id: class_id,
            section_id: section_id,
            session_year_id: session_id,
            shift_id: shift_id,
            gender: gender
        }).toString();

        window.location.href = `/ajax/students/export-excel?${queryParams}`;
    };

    window.exportToPDF = function() {
        let search_text = document.getElementById('filter_search').value;
        let branch_id = document.getElementById('filter_branch').value;
        let class_id = document.getElementById('filter_class').value;
        let section_id = document.getElementById('filter_section').value;
        let session_id = document.getElementById('filter_session').value;
        let shift_id = document.getElementById('filter_shift').value; 
        let gender = document.getElementById('filter_gender').value;

        let queryParams = new URLSearchParams({
            search: search_text,
            branch_id: branch_id,
            class_id: class_id,
            section_id: section_id,
            session_year_id: session_id,
            shift_id: shift_id,
            gender: gender
        }).toString();

        window.open(`/ajax/students/export-pdf?${queryParams}`, '_blank');
    };

    // Event Listeners
    let typingTimer;
    document.getElementById('filter_search').addEventListener('input', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function () { window.fetchList(1); }, 400); 
    });

    // 🚨 ইভেন্ট লিসেনারে 'filter_gender' যোগ করা হলো
    ['filter_branch', 'filter_class', 'filter_section', 'filter_session', 'filter_shift', 'filter_gender'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() { window.fetchList(1); });
    });

    // Init
    document.addEventListener('DOMContentLoaded', async () => {
        await loadFilterOptions();
        window.fetchList();
    });
</script>
@endpush