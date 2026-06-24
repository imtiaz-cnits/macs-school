@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance Summary Report')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#1e4630', themeRed: '#cc0000' } 
            } 
        } 
    }
</script>
<style>
    .smart-select {
        background-color: white !important;
        color: #374151 !important;
        border: 2px solid #f3f4f6 !important;
        border-radius: 1rem !important;
        padding: 0.75rem 1rem !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        width: 100%;
        outline: none !important;
    }
    .dark .smart-select { background-color: #111827 !important; color: #e5e7eb !important; border-color: #374151 !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1; }
    
    /* প্রিমিয়াম স্ট্যাটাস ব্যাজ ডিজাইন */
    .badge-present { @apply bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 px-5 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-green-200 dark:border-green-500/30 shadow-sm shadow-green-100; }
    .badge-absent { @apply bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 px-5 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-red-200 dark:border-red-500/30 shadow-sm shadow-red-100; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-10">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Attendance Report</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl p-8 border border-gray-100 dark:border-gray-700 mb-10">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-5 mb-8">
            <div><label class="smart-label">Branch</label><select id="rep_branch" class="smart-select"></select></div>
            <div><label class="smart-label">Session</label><select id="rep_session" class="smart-select"></select></div>
            <div><label class="smart-label">Class</label><select id="rep_class" class="smart-select"></select></div>
            <div><label class="smart-label">Section</label><select id="rep_section" class="smart-select"></select></div>
            <div><label class="smart-label">Date</label><input type="date" id="rep_date" value="{{ date('Y-m-d') }}" class="smart-select"></div>
        </div>
        <div class="flex justify-end"><button onclick="fetchReport()" class="bg-themeGreen text-white font-black py-4 px-12 rounded-2xl shadow-xl transition-all uppercase tracking-widest text-xs flex items-center gap-2 hover:scale-105 active:scale-95">Generate Report</button></div>
    </div>

    <div id="counterSection" class="hidden grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 animate-in slide-in-from-top duration-500">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl flex items-center gap-5">
            <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center text-blue-600"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg></div>
            <div><p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Students</p><h3 id="count_total" class="text-2xl font-black text-gray-900 dark:text-white">0</h3></div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl flex items-center gap-5 border-l-4 border-l-green-500">
            <div class="w-14 h-14 bg-green-50 dark:bg-green-900/20 rounded-2xl flex items-center justify-center text-green-600"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
            <div><p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Present Today</p><h3 id="count_present" class="text-2xl font-black text-green-600">0</h3></div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl flex items-center gap-5 border-l-4 border-l-red-500">
            <div class="w-14 h-14 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center text-red-600"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
            <div><p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Absent Today</p><h3 id="count_absent" class="text-2xl font-black text-red-600">0</h3></div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <th class="py-6 px-10 text-[10px] font-black text-gray-400 uppercase w-32 text-center tracking-widest">Roll</th>
                        <th class="py-6 px-10 text-[10px] font-black text-gray-400 uppercase tracking-widest">Student Name</th>
                        <th class="py-6 px-10 text-[10px] font-black text-gray-400 uppercase tracking-widest">Taken By (Teacher)</th>
                        <th class="py-6 px-10 text-[10px] font-black text-gray-400 uppercase text-center tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody id="reportList" class="divide-y divide-gray-100 dark:divide-gray-700 font-medium">
                    <tr><td colspan="4" class="py-20 text-center text-gray-400 font-bold uppercase tracking-widest text-xs">Generating reports requires filtering...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const getHeaders = () => ({ headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });

    async function loadReportFilters() {
        try {
            const [branches, sessions, classes, sections] = await Promise.all([
                axios.get('/ajax/branches', getHeaders()),
                axios.get('/ajax/sessions', getHeaders()),
                axios.get('/ajax/classes', getHeaders()),
                axios.get('/ajax/sections', getHeaders())
            ]);
            const fill = (id, data, key) => {
                let s = document.getElementById(id);
                s.innerHTML = `<option value="">Select ${id.split('_')[1].toUpperCase()}</option>`;
                if(data) data.forEach(i => s.add(new Option(i[key], i.id)));
            };
            fill('rep_branch', branches.data.branchData, 'branch_name');
            fill('rep_session', sessions.data.sessionData, 'session_name');
            fill('rep_class', classes.data.classData, 'class_name');
            fill('rep_section', sections.data.sectionData, 'section_name');
        } catch (e) { console.error(e); }
    }

    async function fetchReport() {
        let btn = document.querySelector('button[onclick="fetchReport()"]');
        let query = new URLSearchParams({
            branch_id: document.getElementById('rep_branch').value,
            session_year_id: document.getElementById('rep_session').value,
            class_id: document.getElementById('rep_class').value,
            section_id: document.getElementById('rep_section').value,
            attendance_date: document.getElementById('rep_date').value
        }).toString();

        try {
            btn.innerText = 'WAIT...';
            let res = await axios.get(`/ajax/attendance/report-data?${query}`, getHeaders());
            let data = res.data.data || [];
            let list = document.getElementById('reportList');
            list.innerHTML = '';

            // কাউন্টার ক্যালকুলেশন
            let total = data.length;
            let present = data.filter(item => item.status === 'Present').length;
            let absent = data.filter(item => item.status === 'Absent').length;

            // কাউন্টার আপডেট করা
            document.getElementById('count_total').innerText = total;
            document.getElementById('count_present').innerText = present;
            document.getElementById('count_absent').innerText = absent;
            document.getElementById('counterSection').classList.remove('hidden');

            if(total === 0) {
                list.innerHTML = `<tr><td colspan="4" class="py-20 text-center text-red-400 font-bold uppercase tracking-widest text-xs animate-pulse">No records found.</td></tr>`;
            } else {
                data.forEach(item => {
                    let statusClass = item.status === 'Present' ? 'badge-present' : 'badge-absent';
                    list.innerHTML += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-all border-b border-gray-50 dark:border-gray-700 last:border-0">
                            <td class="py-6 px-10 text-center font-black text-themeGreen dark:text-green-500 font-mono text-2xl">${item.student.roll_number}</td>
                            <td class="py-6 px-10 font-black text-gray-900 dark:text-white uppercase tracking-tight">${item.student.student_name}</td>
                            <td class="py-6 px-10 text-xs font-bold text-gray-400 uppercase italic">Prof. ${item.teacher.user.name}</td>
                            <td class="py-6 px-10 text-center">
                                <span class="${statusClass}">${item.status}</span>
                            </td>
                        </tr>`;
                });
            }
        } catch (e) { alert("Report generation failed!"); }
        finally { btn.innerText = 'GENERATE REPORT'; }
    }

    document.addEventListener('DOMContentLoaded', loadReportFilters);
</script>
@endpush