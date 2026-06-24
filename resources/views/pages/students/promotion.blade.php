@extends('tyro-dashboard::layouts.admin')

@section('title', 'Student Promotion')

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
                colors: { themeGreen: '#1e4630', themeRed: '#cc0000', themeIndigo: '#4f46e5' },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    .form-label { @apply block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5; }
    .form-input { @apply w-full border-2 border-gray-100 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-4 py-3 focus:ring-4 focus:ring-themeGreen/10 focus:border-themeGreen outline-none transition shadow-sm font-bold text-sm; }
    .smart-table-header { @apply py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700; }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<a href="{{ route('students.index') }}" class="text-themeGreen font-bold hover:underline">Student Management</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Promotion</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter flex items-center gap-3">
            <svg class="w-8 h-8 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            Student Promotion
        </h1>
        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-2">Bulk upgrade class sections to the next academic session</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
        
        <div class="bg-white dark:bg-gray-800 p-6 md:p-8 rounded-[2rem] border-2 border-red-100 dark:border-red-900/30 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 dark:bg-red-900/10 rounded-bl-full -z-10"></div>
            <h3 class="text-sm font-black text-themeRed dark:text-red-400 uppercase tracking-[0.2em] mb-6 flex items-center">
                <span class="w-6 h-6 rounded-full bg-red-100 text-themeRed flex items-center justify-center mr-2 text-xs">1</span> 
                Promote From (Current)
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label">Current Branch <span class="text-red-500">*</span></label>
                    <select id="from_branch" class="form-input border-red-50 focus:border-themeRed focus:ring-red-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Current Session <span class="text-red-500">*</span></label>
                    <select id="from_session" class="form-input border-red-50 focus:border-themeRed focus:ring-red-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Current Class <span class="text-red-500">*</span></label>
                    <select id="from_class" class="form-input border-red-50 focus:border-themeRed focus:ring-red-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Current Shift</label>
                    <select id="from_shift" class="form-input border-red-50 focus:border-themeRed focus:ring-red-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Current Section <span class="text-red-500">*</span></label>
                    <select id="from_section" class="form-input border-red-50 focus:border-themeRed focus:ring-red-100"><option value="">Loading...</option></select>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 md:p-8 rounded-[2rem] border-2 border-green-100 dark:border-green-900/30 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-green-50 dark:bg-green-900/10 rounded-bl-full -z-10"></div>
            <h3 class="text-sm font-black text-themeGreen dark:text-green-400 uppercase tracking-[0.2em] mb-6 flex items-center">
                <span class="w-6 h-6 rounded-full bg-green-100 text-themeGreen flex items-center justify-center mr-2 text-xs">2</span> 
                Promote To (Next)
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label">Next Branch <span class="text-red-500">*</span></label>
                    <select id="to_branch" class="form-input border-green-50 focus:border-themeGreen focus:ring-green-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Next Session <span class="text-red-500">*</span></label>
                    <select id="to_session" class="form-input border-green-50 focus:border-themeGreen focus:ring-green-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Next Class <span class="text-red-500">*</span></label>
                    <select id="to_class" class="form-input border-green-50 focus:border-themeGreen focus:ring-green-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Next Shift</label>
                    <select id="to_shift" class="form-input border-green-50 focus:border-themeGreen focus:ring-green-100"><option value="">Loading...</option></select>
                </div>
                <div>
                    <label class="form-label">Next Section <span class="text-red-500">*</span></label>
                    <select id="to_section" class="form-input border-green-50 focus:border-themeGreen focus:ring-green-100"><option value="">Loading...</option></select>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mb-10">
        <button onclick="loadStudentsForPromotion()" class="bg-gray-900 hover:bg-black dark:bg-gray-700 dark:hover:bg-gray-600 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-xl transition-all hover:scale-105 active:scale-95 flex items-center justify-center mx-auto">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            Fetch Students For Promotion
        </button>
    </div>

    <div id="studentTableSection" class="hidden bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden mb-8 transition-all">
        <div class="p-6 bg-themeIndigo/5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-sm font-black text-themeIndigo uppercase tracking-widest">Student List</h3>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="w-5 h-5 text-themeIndigo rounded border-gray-300 focus:ring-themeIndigo cursor-pointer">
                <label for="selectAll" class="text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer">Select All For Promotion</label>
            </div>
        </div>
        
        <form id="promotionForm" onsubmit="event.preventDefault(); submitPromotion();">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="smart-table-header text-center w-16">Promote</th>
                            <th class="smart-table-header text-center w-20">Current Roll</th>
                            <th class="smart-table-header">Student Details</th>
                            <th class="smart-table-header text-center w-24">Marks</th> <th class="smart-table-header text-center w-24">Grade</th> <th class="smart-table-header text-center w-32">New Roll No.</th>
                        </tr>
                    </thead>
                    <tbody id="promotionTableBody" class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        </tbody>
                </table>
            </div>
            
            <div class="p-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white px-12 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-sm shadow-xl shadow-themeGreen/30 transition-all hover:scale-105">
                    Confirm Promotion
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // ==========================================
    // 1. Auth Headers (আপনার আগের কোডের মতো)
    // ==========================================
    const getAuthHeaders = () => ({ 
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
    });

    // ==========================================
    // 2. ড্রপডাউন ডাইনামিক লোড লজিক
    // ==========================================
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            // আপনার API থেকে ডাটা আনা হচ্ছে
            const [branches, classes, sections, sessions, shifts] = await Promise.all([
                axios.get('/ajax/branches', getAuthHeaders()),
                axios.get('/ajax/classes', getAuthHeaders()),
                axios.get('/ajax/sections', getAuthHeaders()),
                axios.get('/ajax/sessions', getAuthHeaders()),
                axios.get('/ajax/shifts', getAuthHeaders())
            ]);

            // আপনার সেই কাজ করা fill ফাংশন
            const fill = (id, data, key) => {
                let s = document.getElementById(id);
                if(s) {
                    s.innerHTML = `<option value="">Select</option>`; // আগের অপশন ক্লিয়ার করে ডিফল্ট রাখা
                    if(data) data.forEach(i => s.add(new Option(i[key], i.id)));
                }
            };

            // Promote From (Current) ড্রপডাউনে ডাটা বসানো
            fill('from_branch', branches.data.branchData, 'branch_name');
            fill('from_class', classes.data.classData, 'class_name');
            fill('from_section', sections.data.sectionData, 'section_name');
            fill('from_session', sessions.data.sessionData, 'session_name');
            fill('from_shift', shifts.data.shiftData, 'shift_name');

            // Promote To (Next) ড্রপডাউনে ডাটা বসানো
            fill('to_branch', branches.data.branchData, 'branch_name');
            fill('to_class', classes.data.classData, 'class_name');
            fill('to_section', sections.data.sectionData, 'section_name');
            fill('to_session', sessions.data.sessionData, 'session_name');
            fill('to_shift', shifts.data.shiftData, 'shift_name');

        } catch (e) { 
            console.error("Dropdown error:", e); 
        }
    });

   // ==========================================
    // 3. রিয়েল স্টুডেন্ট লিস্ট আনার ফাংশন (Dedicated Route)
    // ==========================================
    window.loadStudentsForPromotion = async function() {
        let from_branch = document.getElementById('from_branch').value;
        let from_session = document.getElementById('from_session').value;
        let from_class = document.getElementById('from_class').value;
        let from_shift = document.getElementById('from_shift').value;
        let from_section = document.getElementById('from_section').value;

        // ভ্যালিডেশন
        if(!from_branch || !from_session || !from_class || !from_section) {
            alert("Please select Current Branch, Session, Class, and Section to fetch students.");
            return;
        }
        
        let tableSection = document.getElementById('studentTableSection');
        let tbody = document.getElementById('promotionTableBody');
        
        tableSection.classList.remove('hidden');
        tbody.innerHTML = `<tr><td colspan="4" class="py-12 text-center font-bold text-gray-400 animate-pulse uppercase tracking-widest">Loading Students...</td></tr>`;
        
        try {
            // নতুন ডেডিকেটেড রাউটে কল করা হচ্ছে
            let url = `/ajax/students/promotion-list?branch_id=${from_branch}&session_year_id=${from_session}&class_id=${from_class}&section_id=${from_section}`;
            if (from_shift) url += `&shift_id=${from_shift}`;

            let res = await axios.get(url, getAuthHeaders());
            
            // যেহেতু আমরা কন্ট্রোলার থেকে 'data' এর ভেতরে পাঠিয়েছি, তাই সরাসরি ধরতে পারছি
            let students = res.data.data || [];

            if (students.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="py-12 text-center font-black text-red-400 uppercase tracking-[0.2em]">No students found for this selection!</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            students.forEach(std => {
                let name = std.student_name || std.name || 'Unknown';
                let roll = std.roll_number || std.roll || '';
                let identity = std.student_identity || std.identity || '';

                tbody.innerHTML += `
                    <tr class="hover:bg-green-50/30 dark:hover:bg-green-900/10 transition-colors border-b border-gray-50 dark:border-gray-700">
                        <td class="py-4 px-6 text-center">
                            <input type="checkbox" name="promote_student_ids[]" value="${std.id}" class="promote-checkbox w-5 h-5 text-themeGreen rounded border-gray-300 focus:ring-themeGreen cursor-pointer" checked>
                        </td>
                        <td class="py-4 px-6 text-center font-mono font-black text-gray-500">${roll}</td>
                        <td class="py-4 px-6">
                            <div class="font-black text-sm uppercase text-gray-800 dark:text-white">${name}</div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">ID: ${identity}</div>
                        </td>
                        <td class="py-4 px-2 text-center">
                            <input type="text" name="total_marks[${std.id}]" class="w-full text-center border-2 border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 py-1.5 font-bold focus:border-themeGreen outline-none text-xs" placeholder="Marks">
                        </td>
                        <td class="py-4 px-2 text-center">
                            <input type="text" name="grades[${std.id}]" class="w-full text-center border-2 border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 py-1.5 font-bold focus:border-themeGreen outline-none text-xs" placeholder="A+">
                        </td>
                        <td class="py-4 px-6 text-center">
                            <input type="text" name="new_rolls[${std.id}]" class="w-20 mx-auto text-center border-2 border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 py-1.5 font-black font-mono focus:border-themeGreen outline-none" placeholder="Roll">
                        </td>
                    </tr>
                `;
            });
            
        } catch (error) {
            console.error("Error fetching students:", error);
            tbody.innerHTML = `<tr><td colspan="4" class="py-12 text-center font-bold text-red-500 uppercase tracking-widest">Failed to load students!</td></tr>`;
        }
    };
    
    // ==========================================
    // 4. সব স্টুডেন্ট একসাথে সিলেক্ট করা
    // ==========================================
    window.toggleSelectAll = function() {
        let isChecked = document.getElementById('selectAll').checked;
        let checkboxes = document.querySelectorAll('.promote-checkbox');
        checkboxes.forEach(cb => cb.checked = isChecked);
    };

    // ==========================================
    // 5. প্রমোশন সাবমিট করা
    // ==========================================
    window.submitPromotion = async function() {
        let form = document.getElementById('promotionForm');
        let formData = new FormData(form);
        
        formData.append('to_branch', document.getElementById('to_branch').value);
        formData.append('to_session', document.getElementById('to_session').value);
        formData.append('to_class', document.getElementById('to_class').value);
        formData.append('to_shift', document.getElementById('to_shift').value);
        formData.append('to_section', document.getElementById('to_section').value);

        if(!formData.get('to_branch') || !formData.get('to_session') || !formData.get('to_class') || !formData.get('to_section')) {
            alert("Please select Next Branch, Session, Class, and Section before confirming!");
            return;
        }

        let checkboxes = document.querySelectorAll('.promote-checkbox:checked');
        if(checkboxes.length === 0) {
            alert("Please select at least one student to promote!");
            return;
        }

        try {
            let btn = form.querySelector('button[type="submit"]');
            btn.innerText = 'PROMOTING...';
            btn.disabled = true;

            // আপনার getAuthHeaders ব্যবহার করা হয়েছে
            let res = await axios.post('/ajax/students/promote', formData, getAuthHeaders());

            if (res.status === 200) {
                alert(res.data.message || "Promotion Successful!");
                window.location.reload(); 
            }
        } catch (error) {
            console.error(error);
            alert(error.response?.data?.message || "Something went wrong during promotion!");
        } finally {
            let btn = form.querySelector('button[type="submit"]');
            btn.innerText = 'Confirm Promotion';
            btn.disabled = false;
        }
    };
</script>
@endpush