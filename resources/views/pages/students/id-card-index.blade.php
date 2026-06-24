@extends('tyro-dashboard::layouts.admin')

@section('title', 'Generate Student ID Cards')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#1e4630' } 
            } 
        } 
    }
</script>
<style>
    /* প্রিমিয়াম স্মার্ট ড্রপডাউন ও ইনপুট স্টাইল */
    .smart-select {
        background-color: white !important;
        color: #374151 !important;
        border: 2px solid #f3f4f6 !important;
        border-radius: 1rem !important;
        padding: 0.75rem 1rem !important;
        padding-right: 2.5rem !important; /* Force space for the arrow */
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        width: 100%;
        outline: none !important;
        transition: all 0.3s ease;
        
        /* Dropdown Arrow Fix */
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%239ca3af' viewBox='0 0 24 24' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 1rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .dark .smart-select { 
        background-color: #111827 !important; 
        color: #e5e7eb !important; 
        border-color: #374151 !important; 
    }
    .smart-select:focus { border-color: #1e4630 !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-10">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">ID Card Generator</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-6 md:p-10 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('id-cards.generate') }}" method="POST" target="_blank">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-10 items-start">
                
                <div>
                    <label class="smart-label">Search ID / Roll</label>
                    <input type="text" name="student_id_search" placeholder="Ex: 2026-..." class="smart-select">
                    <p class="text-[9px] text-gray-400 mt-1 ml-1 font-bold italic truncate">Leave blank for bulk print</p>
                </div>

                <div>
                    <label class="smart-label">Select Branch</label>
                    <select name="branch_id" id="filter_branch" class="smart-select"></select>
                </div>
                
                <div>
                    <label class="smart-label">Session</label>
                    <select name="session_year_id" id="filter_session" class="smart-select"></select>
                </div>
                
                <div>
                    <label class="smart-label">Class *</label>
                    <select name="class_id" id="filter_class" class="smart-select">
                        <option value="">Loading...</option>
                    </select>
                </div>
                
                <div>
                    <label class="smart-label">Shift</label>
                    <select name="shift_id" id="filter_shift" class="smart-select"></select>
                </div>

                <div>
                    <label class="smart-label">Section</label>
                    <select name="section_id" id="filter_section" class="smart-select"></select>
                </div>

            </div>

            <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-gray-100 dark:border-gray-700 rounded-[2rem] mb-10 group hover:border-themeGreen/30 transition-all">
                <div class="w-20 h-20 bg-themeGreen/10 text-themeGreen rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                </div>
                <h4 class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Ready to Process</h4>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Select class for bulk or enter ID for single print</p>
            </div>

            <div class="flex justify-center">
                <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-black py-5 px-20 rounded-2xl shadow-2xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95 flex items-center gap-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Generate ID Card PDF
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const getHeaders = () => ({ headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });

    async function loadIdFilters() {
        try {
            const [branches, sessions, classes, sections, shifts] = await Promise.all([
                axios.get('/ajax/branches', getHeaders()),
                axios.get('/ajax/sessions', getHeaders()),
                axios.get('/ajax/classes', getHeaders()),
                axios.get('/ajax/sections', getHeaders()),
                axios.get('/ajax/shifts', getHeaders())
            ]);

            const fill = (id, data, key) => {
                let s = document.getElementById(id);
                s.innerHTML = `<option value="">Select ${id.split('_')[1].toUpperCase()}</option>`;
                if(data) data.forEach(i => s.add(new Option(i[key], i.id)));
            };

            // Safely accessing data based on your established structure
            fill('filter_branch', branches.data.branchData || branches.data, 'branch_name');
            fill('filter_session', sessions.data.sessionData || sessions.data, 'session_name');
            fill('filter_class', classes.data.classData || classes.data, 'class_name');
            fill('filter_section', sections.data.sectionData || sections.data, 'section_name');
            fill('filter_shift', shifts.data.shiftData || shifts.data, 'shift_name');

        } catch (e) { 
            console.error("ID Filter Error:", e);
        }
    }

    document.addEventListener('DOMContentLoaded', loadIdFilters);
</script>
@endpush