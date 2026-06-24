@extends('tyro-dashboard::layouts.admin')

@section('title', 'Class Routine Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { extend: { colors: { themeGreen: '#1e4630', cardDark: '#1a2234', routineBg: '#185a9d', outlineBlue: '#0f386b' } } } 
    }
</script>
<style>
    /* Fixed Dropdown Design */
    .smart-input { background-color: #ffffff !important; color: #374151 !important; border: 2px solid #e5e7eb !important; border-radius: 0.75rem !important; padding: 0.75rem 1rem !important; font-weight: 600 !important; font-size: 0.875rem !important; width: 100%; outline: none !important; appearance: auto !important; transition: all 0.3s ease; }
    .dark .smart-input { background-color: #1a2234 !important; color: #e5e7eb !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1; }

    /* Custom Comic Title */
    .comic-title { color: #ffcc00; text-shadow: -3px -3px 0 #0f386b, 3px -3px 0 #0f386b, -3px 3px 0 #0f386b, 3px 3px 0 #0f386b, 4px 6px 0 rgba(0,0,0,0.3); font-family: 'Arial Black', Impact, sans-serif; }
    .day-pill { border: 4px solid #0f386b; box-shadow: 0 4px 0 #0f386b; }
    .slot-box { background: #ffffff; border: 4px solid #0f386b; box-shadow: 0 4px 0 #0f386b; border-radius: 12px; transition: transform 0.2s; }
    .slot-box:hover { transform: translateY(-3px); }
    .delete-btn { @apply absolute -top-3 -right-3 bg-red-500 text-white border-2 border-[#0f386b] rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer shadow-sm hover:bg-red-600 z-10; }

    /* ==========================================
       🔥 MAGIC PRINT CSS (Strictly 1 Page & Scaled)
       ========================================== */
    @media print {
        @page { size: A4 landscape; margin: 0; }
        html, body { margin: 0 !important; padding: 0 !important; height: 100%; width: 100%; overflow: hidden; }
        body * { visibility: hidden; }
        
        #printableRoutine { 
            visibility: visible; 
            position: fixed; 
            left: 0; 
            top: 0; 
            width: 297mm; /* Exact A4 Landscape Width */
            height: 209mm; /* Exact A4 Landscape Height */
            background-color: #185a9d !important; 
            padding: 8mm !important;
            box-sizing: border-box;
            z-index: 99999;
            overflow: hidden;
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important;
            border-radius: 0 !important;
        }

        #printableRoutine * { visibility: visible; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .no-print, .delete-btn { display: none !important; }

        /* Responsive Scaling for Print */
        .comic-title { font-size: 38px !important; margin-bottom: 2px !important; line-height: 1.1 !important; }
        #printClassInfo { font-size: 14px !important; margin-bottom: 15px !important; }
        
        .day-pill { font-size: 12px !important; padding: 6px !important; border-width: 2px !important; box-shadow: 0 2px 0 #0f386b !important; }
        .slot-box { border-width: 2px !important; padding: 6px !important; min-height: 55px !important; box-shadow: 0 2px 0 #0f386b !important; }
        
        .print-subject { font-size: 13px !important; font-weight: 900 !important; color: #000 !important; }
        .print-time { font-size: 10px !important; font-weight: bold !important; color: #444 !important; }
        .print-teacher { font-size: 10px !important; color: #1e4630 !important; font-weight: bold !important; }
    }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1600px] mx-auto min-h-screen">
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Class Routine</h1>
            <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Smart Class Routine</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-3 no-print">
            <div class="bg-white dark:bg-cardDark rounded-[2rem] shadow-xl p-6 border border-gray-100 dark:border-gray-700 sticky top-8">
                <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-wider border-b-2 border-gray-100 dark:border-gray-700 pb-4 mb-6">Add New Slot</h3>
                <form id="routineForm">
                    <div class="grid grid-cols-1 gap-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="smart-label">Branch</label><select name="branch_id" id="branch_id" class="smart-input" onchange="loadRoutine()"><option value="">Main Branch</option>@foreach($branches as $branch) <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option> @endforeach</select></div>
                            <div><label class="smart-label">Session *</label><select name="session_year_id" id="session_id" class="smart-input" required onchange="loadRoutine()">@foreach($sessions as $session) <option value="{{ $session->id }}">{{ $session->session_name }}</option> @endforeach</select></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="smart-label">Class *</label><select name="class_id" id="class_id" class="smart-input" required onchange="loadRoutine()"><option value="">Select...</option>@foreach($classes as $class) <option value="{{ $class->id }}">{{ $class->class_name }}</option> @endforeach</select></div>
                            <div><label class="smart-label">Section</label><select name="section_id" id="section_id" class="smart-input" onchange="loadRoutine()"><option value="">Any</option>@foreach($sections as $section) <option value="{{ $section->id }}">{{ $section->section_name }}</option> @endforeach</select></div>
                        </div>
                        <div><label class="smart-label">Subject *</label><select name="subject_id" class="smart-input" required><option value="">Select Subject...</option>@foreach($subjects as $subject) <option value="{{ $subject->id }}">{{ $subject->subject_name ?? $subject->name }}</option> @endforeach</select></div>
                        <div><label class="smart-label">Teacher *</label><select name="teacher_id" class="smart-input" required><option value="">Assign Teacher...</option>@foreach($teachers as $teacher) <option value="{{ $teacher->id }}">{{ $teacher->user->name ?? 'Unknown' }}</option> @endforeach</select></div>
                        <div><label class="smart-label">Day *</label><select name="day" class="smart-input" required><option value="Saturday">Saturday</option><option value="Sunday">Sunday</option><option value="Monday">Monday</option><option value="Tuesday">Tuesday</option><option value="Wednesday">Wednesday</option><option value="Thursday">Thursday</option></select></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="smart-label">Start Time *</label><input type="time" name="start_time" class="smart-input" required></div>
                            <div><label class="smart-label">End Time *</label><input type="time" name="end_time" class="smart-input" required></div>
                        </div>
                        <button type="submit" id="submitBtn" class="mt-4 bg-[#1e4630] hover:bg-green-900 text-white font-black py-4 rounded-xl shadow-lg transition-all uppercase tracking-widest text-xs w-full active:scale-95">+ Add to Routine</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-9">
            <div class="flex justify-between items-center mb-6 no-print">
                <span id="loader" class="hidden text-xs font-bold text-gray-500 uppercase animate-pulse">Syncing...</span>
                <button onclick="window.print()" class="ml-auto bg-themeGreen hover:bg-green-900 text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Routine
                </button>
            </div>

            <div id="noDataMsg" class="text-center py-20 bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 hidden no-print">
                <h4 class="text-lg font-black text-gray-500 uppercase tracking-widest">Select a Class</h4>
            </div>

            <div id="printableRoutine" class="hidden relative overflow-hidden bg-routineBg rounded-[2rem] p-6 md:p-8 border-4 border-outlineBlue shadow-2xl">
                <div class="text-center mb-6 relative z-10">
                    <h2 class="comic-title text-4xl md:text-5xl tracking-widest">CLASS ROUTINE</h2>
                    <p id="printClassInfo" class="text-white font-bold text-xs mt-1 uppercase tracking-widest drop-shadow-md"></p>
                </div>
                <div id="routineBoard" class="relative z-10"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const daysOfWeek = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    const dayColors = { 'Saturday': '#d93846', 'Sunday': '#208b98', 'Monday': '#f5b62b', 'Tuesday': '#e37222', 'Wednesday': '#3b8456', 'Thursday': '#8b5cf6' };
    
    function formatTime(timeStr) {
        let [hours, minutes] = timeStr.split(':');
        let ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }

    async function loadRoutine() {
        let branchSelect = document.getElementById('branch_id');
        let session_id = document.getElementById('session_id').value;
        let classSelect = document.getElementById('class_id');
        let sectionSelect = document.getElementById('section_id');
        
        let branch_id = branchSelect.value;
        let class_id = classSelect.value;
        let section_id = sectionSelect.value;

        if (!class_id) {
            document.getElementById('printableRoutine').classList.add('hidden');
            document.getElementById('noDataMsg').classList.remove('hidden');
            return;
        }

        document.getElementById('printClassInfo').innerText = `Branch: ${branch_id ? branchSelect.options[branchSelect.selectedIndex].text : 'Main'} | Class: ${classSelect.options[classSelect.selectedIndex].text} | Sec: ${section_id ? sectionSelect.options[sectionSelect.selectedIndex].text : 'All'}`;
        document.getElementById('loader').classList.remove('hidden');
        
        try {
            let res = await axios.get('/routine/get', { params: { branch_id, session_year_id: session_id, class_id, section_id } });
            let data = res.data.routine;
            
            // ১. বের করা যে সর্বোচ্চ কয়টা ক্লাস আছে কোনো দিনে (Rows)
            let maxSlots = 5; // ডিফল্ট ৫টা রো থাকবেই (ডিজাইনের জন্য)
            daysOfWeek.forEach(day => {
                if (data[day] && data[day].length > maxSlots) maxSlots = data[day].length;
            });

            // ২. ম্যাট্রিক্স গ্রিড তৈরি (৭ কলাম: ১টি সিরিয়ালের জন্য + ৬টি দিনের জন্য)
            let html = `<div class="grid grid-cols-[35px_repeat(6,_1fr)] md:grid-cols-[50px_repeat(6,_1fr)] gap-2 md:gap-3 w-full">`;

            // হেডার রো: SL এবং বারগুলোর নাম
            html += `<div class="flex flex-col"><div class="day-pill w-full h-full flex items-center justify-center rounded-xl text-white font-black uppercase shadow-sm" style="background-color: #0f386b; font-size: 11px;">SL</div></div>`;
            daysOfWeek.forEach(day => {
                html += `<div class="flex flex-col"><div class="day-pill w-full h-full flex items-center justify-center text-center rounded-xl text-white font-black uppercase shadow-sm" style="background-color: ${dayColors[day]}; font-size: 11px;">${day}</div></div>`;
            });

            // রো অনুযায়ী ডাটা বসানো (Period 1, Period 2...)
            for (let i = 0; i < maxSlots; i++) {
                // সিরিয়াল (SL) নম্বর
                html += `<div class="flex flex-col"><div class="day-pill w-full h-full min-h-[50px] flex items-center justify-center rounded-xl text-white font-black text-lg shadow-sm" style="background-color: #0f386b;">${i + 1}</div></div>`;
                
                // ওই রো-এর জন্য সব দিনের ক্লাস
                daysOfWeek.forEach(day => {
                    if (data[day] && data[day][i]) {
                        let slot = data[day][i];
                        let subjectName = slot.subject ? (slot.subject.subject_name || slot.subject.name) : 'N/A';
                        let teacherName = slot.teacher && slot.teacher.user ? slot.teacher.user.name : 'Unknown';
                        
                        html += `
                        <div class="slot-box relative group p-1.5 md:p-2 flex flex-col items-center justify-center text-center w-full h-full min-h-[50px]">
                            <div class="delete-btn" onclick="deleteRoutine(${slot.id})" title="Delete"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg></div>
                            <div class="print-time text-[9px] font-black text-gray-500 mb-0.5">${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</div>
                            <div class="print-subject font-black text-[11px] md:text-[13px] text-outlineBlue uppercase leading-tight mb-0.5 break-words w-full line-clamp-2" title="${subjectName}">${subjectName}</div>
                            <div class="print-teacher text-[9px] font-bold text-themeGreen capitalize line-clamp-1" title="${teacherName}">${teacherName}</div>
                        </div>`;
                    } else {
                        // খালি বক্স
                        html += `<div class="slot-box bg-white/60 border-dashed border-opacity-50 flex items-center justify-center w-full h-full min-h-[50px]"><span class="text-[9px] font-black text-gray-400 opacity-50 uppercase tracking-widest no-print">Empty</span></div>`;
                    }
                });
            }
            html += `</div>`;
            
            document.getElementById('routineBoard').innerHTML = html;
            document.getElementById('noDataMsg').classList.add('hidden');
            document.getElementById('printableRoutine').classList.remove('hidden');

        } catch (err) { console.error(err); } 
        finally { document.getElementById('loader').classList.add('hidden'); }
    }

    document.getElementById('routineForm').onsubmit = async function(e) {
        e.preventDefault();
        let btn = document.getElementById('submitBtn');
        try {
            btn.disabled = true; btn.innerText = 'Checking...';
            let res = await axios.post('/routine/store', Object.fromEntries(new FormData(this)), { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            if (res.data.status === 'error') alert("⚠️ WARNING: " + res.data.message);
            else { alert("✅ " + res.data.message); loadRoutine(); }
        } catch (err) { alert("❌ Error!"); } 
        finally { btn.disabled = false; btn.innerText = '+ Add to Routine'; }
    };

    async function deleteRoutine(id) {
        if(confirm("Are you sure?")) {
            try { await axios.delete(`/routine/destroy/${id}`, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); loadRoutine(); } 
            catch (err) { alert("Failed to delete."); }
        }
    }

    document.addEventListener('DOMContentLoaded', loadRoutine);
</script>
@endpush