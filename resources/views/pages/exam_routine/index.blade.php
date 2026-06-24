@extends('tyro-dashboard::layouts.admin')

@section('title', 'Exam Routine Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630', cardDark: '#1a2234' } } } }
</script>
<style>
    .smart-input { background-color: #ffffff !important; color: #374151 !important; border: 2px solid #e5e7eb !important; border-radius: 0.75rem !important; padding: 0.75rem 1rem !important; font-weight: 600 !important; font-size: 0.875rem !important; width: 100%; outline: none !important; transition: all 0.3s ease; }
    .dark .smart-input { background-color: #1a2234 !important; color: #e5e7eb !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1; }

    /* ==========================================
       🔥 EXAM PRINT CSS (A4 Portrait Formal)
       ========================================== */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        body * { visibility: hidden; }
        
        #printableRoutine, #printableRoutine * { 
            visibility: visible; color: #000 !important; background: #fff !important;
            -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
        }
        
        #printableRoutine { position: absolute; left: 0; top: 0; width: 100%; padding: 0; box-shadow: none !important; border: none !important; }
        .no-print { display: none !important; }

        /* Print Header - Formal School Style */
        .school-header { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 20px; }
        .school-header h1 { font-size: 28px !important; font-weight: 900 !important; margin: 0 !important; text-transform: uppercase; font-family: 'Times New Roman', serif; }
        .school-header h3 { font-size: 20px !important; margin: 5px 0 0 0 !important; text-decoration: underline; }
        .school-header p { font-size: 16px !important; margin: 5px 0 0 0 !important; font-weight: bold; }

        /* Exam Table */
        table { width: 100% !important; border-collapse: collapse !important; border: 2px solid #000 !important; }
        th, td { border: 1px solid #000 !important; padding: 12px !important; text-align: center; font-size: 16px !important; }
        th { background-color: #e5e7eb !important; font-weight: bold !important; text-transform: uppercase; }
        td { font-weight: 600 !important; }
    }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1500px] mx-auto min-h-screen relative">
    
    <div id="toast-container" class="fixed bottom-8 right-8 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Exam ROUTINE </h1>
            <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Manage Exams Date and Time</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 no-print">
            <div class="bg-white dark:bg-cardDark rounded-[2rem] shadow-xl p-6 border border-gray-100 dark:border-gray-700 sticky top-8">
                <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-wider border-b-2 border-gray-100 dark:border-gray-700 pb-4 mb-6">Add Exam Subject</h3>
                <form id="routineForm">
                    <div class="grid grid-cols-1 gap-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="smart-label">Session *</label><select name="session_year_id" id="session_id" class="smart-input" required onchange="loadRoutine()">@foreach($sessions as $session) <option value="{{ $session->id }}">{{ $session->session_name }}</option> @endforeach</select></div>
                            <div><label class="smart-label">Exam Name *</label><select name="exam_id" id="exam_id" class="smart-input" required onchange="loadRoutine()"><option value="">Select Exam</option>@foreach($exams as $exam) <option value="{{ $exam->id }}">{{ $exam->name }}</option> @endforeach</select></div>
                        </div>
                        
                        <div><label class="smart-label">Class *</label><select name="class_id" id="class_id" class="smart-input" required onchange="loadRoutine()"><option value="">Select Class...</option>@foreach($classes as $class) <option value="{{ $class->id }}">{{ $class->class_name }}</option> @endforeach</select></div>
                        <div><label class="smart-label">Subject *</label><select name="subject_id" class="smart-input dynamic-clear" required><option value="">Select Subject...</option>@foreach($subjects as $subject) <option value="{{ $subject->id }}">{{ $subject->subject_name ?? $subject->name }}</option> @endforeach</select></div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="smart-label">Exam Date *</label><input type="date" name="exam_date" class="smart-input dynamic-clear" required></div>
                            <div><label class="smart-label">Room No (Opt)</label><input type="text" name="room_number" placeholder="Ex: 101" class="smart-input dynamic-clear"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="smart-label">Start Time *</label><input type="time" name="start_time" class="smart-input dynamic-clear" required></div>
                            <div><label class="smart-label">End Time *</label><input type="time" name="end_time" class="smart-input dynamic-clear" required></div>
                        </div>

                        <button type="submit" id="submitBtn" class="mt-2 bg-[#1e4630] hover:bg-green-900 text-white font-black py-4 rounded-xl shadow-lg transition-all uppercase tracking-widest text-xs w-full active:scale-95">+ Add to Schedule</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="flex justify-between items-center mb-6 no-print">
                <span id="loader" class="hidden text-xs font-bold text-gray-500 uppercase animate-pulse">Syncing...</span>
                <button onclick="window.print()" class="ml-auto bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Schedule
                </button>
            </div>

            <div id="noDataMsg" class="text-center py-20 bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 hidden no-print">
                <h4 class="text-lg font-black text-gray-500 uppercase tracking-widest">Select Details</h4>
                <p class="text-xs text-gray-400 font-bold mt-2">Choose Exam & Class to view the schedule.</p>
            </div>

            <div id="printableRoutine" class="hidden bg-white dark:bg-gray-900 rounded-[2rem] p-8 border border-gray-100 dark:border-gray-800 shadow-xl overflow-x-auto">
                
                <div class="school-header hidden print:block mb-6">
                    <h1>Pabna International School</h1>
                    <h3 id="printExamName">Term Examination - 2026</h3>
                    <p id="printClassInfo">Class: Five</p>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 dark:bg-gray-800 border-b-2 border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="p-4 font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-xs">Date & Day</th>
                            <th class="p-4 font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-xs">Subject</th>
                            <th class="p-4 font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-xs">Time</th>
                            <th class="p-4 font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-xs">Room</th>
                            <th class="p-4 font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-xs text-center no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody id="routineBoard" class="divide-y divide-gray-100 dark:divide-gray-800">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Custom Smart Toast Notification Function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-[#1e4630]' : 'bg-red-600';
        const icon = type === 'success' ? '✅' : '⚠️';
        
        toast.className = `${bgColor} text-white px-6 py-3 rounded-xl shadow-2xl font-bold text-sm flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0`;
        toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
        
        document.getElementById('toast-container').appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.remove('translate-y-10', 'opacity-0'), 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('translate-y-10', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function formatTime(timeStr) {
        let [hours, minutes] = timeStr.split(':');
        let ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }

    function formatDate(dateString) {
        const options = { day: '2-digit', month: 'short', year: 'numeric', weekday: 'long' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    async function loadRoutine() {
        let session_id = document.getElementById('session_id').value;
        let examSelect = document.getElementById('exam_id');
        let classSelect = document.getElementById('class_id');
        
        let exam_id = examSelect.value;
        let class_id = classSelect.value;

        if (!class_id || !exam_id) {
            document.getElementById('printableRoutine').classList.add('hidden');
            document.getElementById('noDataMsg').classList.remove('hidden');
            return;
        }

        let sessionText = document.getElementById('session_id').options[document.getElementById('session_id').selectedIndex].text;
        document.getElementById('printExamName').innerText = `${examSelect.options[examSelect.selectedIndex].text} - ${sessionText}`;
        document.getElementById('printClassInfo').innerText = `Class: ${classSelect.options[classSelect.selectedIndex].text}`;

        document.getElementById('loader').classList.remove('hidden');
        
        try {
            let res = await axios.get('/exam-routine/get', { params: { session_year_id: session_id, exam_id: exam_id, class_id: class_id } });
            let data = res.data.routine;
            let html = '';

            if(data.length > 0) {
                data.forEach(slot => {
                    let subjectName = slot.subject ? (slot.subject.subject_name || slot.subject.name) : 'N/A';
                    let room = slot.room_number ? slot.room_number : 'TBA';
                    
                    html += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="p-4 border-b border-gray-100 dark:border-gray-800 align-middle">
                            <span class="font-black text-[#1e4630] dark:text-green-500 uppercase text-sm">${formatDate(slot.exam_date)}</span>
                        </td>
                        <td class="p-4 border-b border-gray-100 dark:border-gray-800">
                            <span class="font-black text-gray-900 dark:text-white uppercase text-base">${subjectName}</span>
                        </td>
                        <td class="p-4 border-b border-gray-100 dark:border-gray-800">
                            <span class="font-bold text-gray-600 dark:text-gray-300 text-xs bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full border border-gray-200 dark:border-gray-700 whitespace-nowrap">
                                ${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}
                            </span>
                        </td>
                        <td class="p-4 border-b border-gray-100 dark:border-gray-800 font-bold text-gray-700 dark:text-gray-300">
                            ${room}
                        </td>
                        <td class="p-4 border-b border-gray-100 dark:border-gray-800 text-center no-print">
                            <button onclick="deleteRoutine(${slot.id})" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Delete Slot">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                    </tr>`;
                });
            } else {
                html = `<tr><td colspan="5" class="p-8 text-center text-gray-400 font-bold uppercase tracking-widest text-xs">No exams scheduled yet</td></tr>`;
            }

            document.getElementById('routineBoard').innerHTML = html;
            document.getElementById('noDataMsg').classList.add('hidden');
            document.getElementById('printableRoutine').classList.remove('hidden');

        } catch (err) { console.error(err); } finally { document.getElementById('loader').classList.add('hidden'); }
    }

    // REAL-TIME AJAX SUBMISSION
    document.getElementById('routineForm').onsubmit = async function(e) {
        e.preventDefault();
        let btn = document.getElementById('submitBtn');
        try {
            btn.disabled = true; 
            btn.innerText = 'Saving...';
            
            let res = await axios.post('/exam-routine/store', Object.fromEntries(new FormData(this)), { 
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
            });
            
            if (res.data.status === 'error') {
                showToast(res.data.message, 'error');
            } else {
                showToast(res.data.message, 'success');
                
                // Magic: Auto Clear Specific Fields for Next Entry!
                let form = document.getElementById('routineForm');
                form.querySelectorAll('.dynamic-clear').forEach(input => input.value = '');
                
                // Instant Reload Table
                loadRoutine(); 
            }
        } catch (err) { 
            showToast("System Error! Please check fields.", 'error'); 
        } finally { 
            btn.disabled = false; 
            btn.innerText = '+ Add to Schedule'; 
        }
    };

    async function deleteRoutine(id) {
        if(confirm("Are you sure you want to delete this exam slot?")) {
            try { 
                await axios.delete(`/exam-routine/destroy/${id}`, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); 
                showToast("Routine slot deleted!", 'success');
                loadRoutine(); 
            } 
            catch (err) { showToast("Failed to delete.", 'error'); }
        }
    }

    document.addEventListener('DOMContentLoaded', loadRoutine);
</script>
@endpush