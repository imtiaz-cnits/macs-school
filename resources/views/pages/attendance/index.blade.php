@extends('tyro-dashboard::layouts.admin')

@section('title', 'Daily Attendance Management')

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
                colors: { 
                    themeGreen: '#1e4630', 
                    themeRed: '#cc0000',
                    cardDark: '#1a2234' // রেফারেন্স ইমেজ অনুযায়ী ডার্ক নেভি কালার
                } 
            } 
        } 
    }
</script>
<style>
    /* ড্রপডাউন ডিজাইন ফিক্স */
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
        appearance: auto !important;
    }
    .dark .smart-select { 
        background-color: #111827 !important; 
        color: #e5e7eb !important; 
        border-color: #374151 !important; 
    }
    .smart-label { @apply block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1; }
    
    /* রেডিও বাটন স্টাইল */
    .status-radio { @apply w-5 h-5 cursor-pointer; }

    /* প্রিমিয়াম কাউন্টার কার্ড ডিজাইন */
    .premium-card { @apply bg-[#1a2234] p-6 rounded-[2.5rem] border border-gray-700/30 shadow-2xl flex items-center gap-6 transition-all hover:scale-[1.02]; }
    .icon-box { @apply w-16 h-16 rounded-3xl flex items-center justify-center shrink-0 shadow-lg; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-10 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Daily Attendance</h1>
            <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
        </div>
        <div class="bg-themeGreen/10 px-6 py-2.5 rounded-2xl border border-themeGreen/20 backdrop-blur-sm">
            <span class="text-xs font-black text-themeGreen uppercase tracking-widest">Date: {{ date('d M, Y') }}</span>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-8 border border-gray-100 dark:border-gray-700 mb-10">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-5 mb-8">
            <div><label class="smart-label">Branch</label><select id="attendance_branch" class="smart-select"></select></div>
            <div><label class="smart-label">Session</label><select id="attendance_session" class="smart-select"></select></div>
            <div><label class="smart-label">Class *</label><select id="attendance_class" class="smart-select"></select></div>
            <div><label class="smart-label">Section</label><select id="attendance_section" class="smart-select"></select></div>
            <div>
                <label class="smart-label">Taking Teacher *</label>
                <select id="attendance_teacher" class="smart-select"><option value="">Loading...</option></select>
            </div>
            <div><label class="smart-label">Attendance Date</label><input type="date" id="attendance_date" value="{{ date('Y-m-d') }}" class="smart-select"></div>
        </div>

        <div class="flex justify-center md:justify-end">
            <button onclick="window.fetchStudents()" class="bg-themeGreen hover:bg-green-900 text-white font-black py-4 px-12 rounded-2xl shadow-xl shadow-themeGreen/20 transition-all uppercase tracking-widest text-xs flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                Load Student List
            </button>
        </div>
    </div>

    <div id="counterSection" class="hidden grid grid-cols-1 md:grid-cols-3 gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="premium-card">
            <div class="icon-box bg-blue-500/10 text-blue-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Total Strength</p>
                <h3 id="live_total" class="text-4xl font-black text-white tracking-tighter">0</h3>
            </div>
        </div>

        <div class="premium-card">
            <div class="icon-box bg-green-500/10 text-green-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Marked Present</p>
                <h3 id="live_present" class="text-4xl font-black text-green-500 tracking-tighter">0</h3>
            </div>
        </div>

        <div class="premium-card">
            <div class="icon-box bg-red-500/10 text-red-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Marked Absent</p>
                <h3 id="live_absent" class="text-4xl font-black text-red-500 tracking-tighter">0</h3>
            </div>
        </div>
    </div>

    <div id="attendanceContainer" class="hidden animate-in fade-in duration-500">
        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <form id="attendanceForm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="py-6 px-10 text-[10px] font-black text-gray-400 uppercase w-32 text-center tracking-widest">Roll No</th>
                                <th class="py-6 px-10 text-[10px] font-black text-gray-400 uppercase tracking-widest">Student Name</th>
                                <th class="py-6 px-10 text-[10px] font-black text-gray-400 uppercase text-center tracking-widest">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody id="studentList" class="divide-y divide-gray-100 dark:divide-gray-700 font-medium">
                            </tbody>
                    </table>
                </div>
                <div class="p-10 bg-gray-50/50 dark:bg-gray-900/20 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">Ensure all data is correct before clicking submit</p>
                    <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-black py-5 px-20 rounded-2xl shadow-2xl uppercase tracking-widest text-sm transition-all hover:scale-105 active:scale-95">
                        Submit Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="noData" class="hidden py-20 text-center">
        <p class="text-gray-500 font-black uppercase tracking-[0.3em] text-sm animate-pulse">No students match your criteria</p>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const getAuthHeaders = () => ({ 
        headers: { 
            'Accept': 'application/json', 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
        } 
    });

    async function loadFilters() {
        try {
            const [branches, sessions, classes, sections, teachers] = await Promise.all([
                axios.get('/ajax/branches', getAuthHeaders()),
                axios.get('/ajax/sessions', getAuthHeaders()),
                axios.get('/ajax/classes', getAuthHeaders()),
                axios.get('/ajax/sections', getAuthHeaders()),
                axios.get('/ajax/teachers', getAuthHeaders()) 
            ]);

            const fill = (id, data, key) => {
                let s = document.getElementById(id);
                s.innerHTML = `<option value="">Select ${id.split('_')[1].toUpperCase()}</option>`;
                if(data) data.forEach(i => s.add(new Option(i[key], i.id)));
            };

            fill('attendance_branch', branches.data.branchData, 'branch_name');
            fill('attendance_session', sessions.data.sessionData, 'session_name');
            fill('attendance_class', classes.data.classData, 'class_name');
            fill('attendance_section', sections.data.sectionData, 'section_name');
            fill('attendance_teacher', teachers.data.teacherData, 'name'); 
        } catch (e) { console.error("Filter Load Error:", e); }
    }

    // ২. রিয়েল-টাইম কাউন্টার আপডেট লজিক
    window.updateCounters = function() {
        let present = document.querySelectorAll('input[type="radio"][value="Present"]:checked').length;
        let absent = document.querySelectorAll('input[type="radio"][value="Absent"]:checked').length;
        let total = present + absent;

        document.getElementById('live_total').innerText = total;
        document.getElementById('live_present').innerText = present;
        document.getElementById('live_absent').innerText = absent;
    };

    window.fetchStudents = async function() {
        let classId = document.getElementById('attendance_class').value;
        if(!classId) return alert("Please select a Class!");

        let query = new URLSearchParams({ 
            branch_id: document.getElementById('attendance_branch').value, 
            session_year_id: document.getElementById('attendance_session').value, 
            class_id: classId, 
            section_id: document.getElementById('attendance_section').value 
        }).toString();

        try {
            let res = await axios.get(`/ajax/attendance/students?${query}`, getAuthHeaders());
            let students = res.data.students || [];
            let list = document.getElementById('studentList');
            let container = document.getElementById('attendanceContainer');
            let counterSec = document.getElementById('counterSection');
            let noData = document.getElementById('noData');
            
            list.innerHTML = '';
            if(students.length > 0) {
                students.forEach(s => {
                    list.innerHTML += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-all border-b border-gray-50 dark:border-gray-700 last:border-0">
                            <td class="py-6 px-10 text-center font-black text-themeGreen dark:text-green-500 font-mono text-2xl">${s.roll_number}</td>
                            <td class="py-6 px-10 font-black text-gray-900 dark:text-white uppercase tracking-tight">${s.student_name}</td>
                            <td class="py-6 px-10">
                                <div class="flex justify-center gap-12">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="radio" name="status[${s.id}]" value="Present" checked onchange="window.updateCounters()" class="status-radio accent-green-600">
                                        <span class="text-xs font-black uppercase text-gray-400 group-hover:text-green-600 transition-colors">Present</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="radio" name="status[${s.id}]" value="Absent" onchange="window.updateCounters()" class="status-radio accent-red-600">
                                        <span class="text-xs font-black uppercase text-gray-400 group-hover:text-red-600 transition-colors">Absent</span>
                                    </label>
                                </div>
                            </td>
                        </tr>`;
                });
                container.classList.remove('hidden');
                counterSec.classList.remove('hidden');
                noData.classList.add('hidden');
                window.updateCounters(); // ইনিশিয়াল কাউন্ট
            } else {
                container.classList.add('hidden');
                counterSec.classList.add('hidden');
                noData.classList.remove('hidden');
            }
        } catch (e) { alert("Failed to load students list."); }
    };

    document.getElementById('attendanceForm').onsubmit = async function(e) {
        e.preventDefault();
        let teacherId = document.getElementById('attendance_teacher').value;
        if(!teacherId) return alert("Please select the Taking Teacher!");

        let btn = this.querySelector('button[type="submit"]');
        let formData = new FormData(this);
        let attendanceData = {};
        for(let [key, value] of formData.entries()) {
            let studentId = key.match(/\d+/)[0];
            attendanceData[studentId] = value;
        }

        try {
            btn.disabled = true; btn.innerText = 'SAVING...';
            
            // let এর জায়গায় res ভ্যারিয়েবলটি ধরতে হবে
            let res = await axios.post('/ajax/attendance/save', { 
                branch_id: document.getElementById('attendance_branch').value,
                session_year_id: document.getElementById('attendance_session').value,
                class_id: document.getElementById('attendance_class').value, 
                section_id: document.getElementById('attendance_section').value,
                teacher_id: teacherId, 
                attendance_date: document.getElementById('attendance_date').value, 
                attendance_data: attendanceData 
            }, getAuthHeaders());
            
            // সার্ভার থেকে আসা ডায়নামিক মেসেজটি দেখাবে
            alert(res.data.message || "Alhamdulillah! Attendance saved successfully.");
            window.location.reload();
            
        } catch (err) { 
            btn.disabled = false; 
            btn.innerText = 'Submit Attendance'; 
            alert("Failed to save."); 
        }
    };

    document.addEventListener('DOMContentLoaded', loadFilters);
</script>
@endpush