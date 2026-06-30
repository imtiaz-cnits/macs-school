@extends('tyro-dashboard::layouts.admin')

@section('title', 'Generate Student ID Cards')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#009A49', themeBlue: '#008ED6', themeDark: '#070E14', themeNavy: '#0F1E2C' },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    [x-cloak] { display: none !important; }
    .form-label {
        display: block;
        font-size: 10px !important;
        font-weight: 900 !important;
        letter-spacing: 0.15em !important;
        text-transform: uppercase !important;
        color: #6b7280; /* text-gray-500 */
        margin-bottom: 0.5rem !important;
    }
    .dark .form-label {
        color: #9ca3af; /* text-gray-400 */
    }
    .form-input {
        width: 100%;
        height: 44px !important;
        padding: 0 1rem !important;
        border-radius: 12px !important; /* rounded-xl */
        border: 2px solid #e2e8f0 !important; /* border-gray-200 */
        background-color: rgba(248, 250, 252, 0.5) !important; /* bg-gray-50/50 */
        color: #0f172a !important; /* text-gray-900 */
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
        outline: none !important;
        box-sizing: border-box;
    }
    .dark .form-input {
        border-color: rgba(255, 255, 255, 0.08) !important;
        background-color: #070e14 !important; /* bg-themeDark */
        color: #f8fafc !important;
    }
    .form-input:focus {
        border-color: #008ED6 !important;
        box-shadow: 0 0 0 4px rgba(0, 142, 214, 0.1) !important;
        background-color: #ffffff !important;
    }
    .dark .form-input:focus {
        background-color: #0f1e2c !important;
    }
    .required-star {
        color: #ef4444; /* text-red-500 */
        margin-left: 0.125rem;
    }
    /* Dropdown Trigger and Options Font Size Override */
    .relative button[type="button"] {
        font-size: 0.875rem !important; /* text-sm */
    }
    .relative div button {
        font-size: 0.875rem !important; /* text-sm */
    }
</style>
<script>
    function getAuthHeaders() {
        return { 
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            } 
        };
    }

    window.dropdownState = function(id, defaultLabel, ajaxUrl, dataKey, nameField, idField) {
        return {
            open: false,
            selectedLabel: defaultLabel,
            selectedValue: '',
            options: [],
            async init() {
                let input = document.getElementById(id);
                if (input) {
                    this.selectedValue = input.value;
                    input.addEventListener('change', () => {
                        this.selectedValue = input.value;
                        let found = this.options.find(o => o.id == input.value);
                        this.selectedLabel = found ? found.name : defaultLabel;
                    });
                }

                if (ajaxUrl) {
                    try {
                        let res = await axios.get(ajaxUrl, getAuthHeaders());
                        let list = res.data[dataKey] || [];
                        this.options = list.map(item => ({
                            id: item[idField],
                            name: item[nameField]
                        }));
                    } catch (e) {
                        console.error('Failed to load ' + id, e);
                    }
                }
            },
            select(option) {
                this.selectedLabel = option ? option.name : defaultLabel;
                this.selectedValue = option ? option.id : '';
                this.open = false;
                
                let input = document.getElementById(id);
                if (input) {
                    input.value = this.selectedValue;
                    input.dispatchEvent(new Event('change'));
                }
            }
        };
    };
</script>
@endpush

@section('breadcrumb')
<span>Student Management</span>
@endsection

@section('content')
<!-- Header Section -->
<div class="border-gray-150 dark:border-white/[0.08] pb-6">
    <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
        <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2H9.17a3.001 3.001 0 01-2.83-2z"/>
        </svg>
        ID Card Generator
    </h1>
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Pabna International School - Student ID Card Management</p>
</div>

<form action="{{ route('id-cards.generate') }}" method="POST" target="_blank" onsubmit="return validateForm(event);">
    @csrf
    
    <!-- Filter Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-5 mb-6  items-end">
        
        <!-- Search ID / Roll -->
        <div>
            <label class="form-label">Search ID / Roll</label>
            <input type="text" name="student_id_search" placeholder="Ex: 2026-..." class="form-input bg-gray-50/50 dark:bg-themeDark/40">
        </div>

        <!-- Select Branch -->
        <div x-data="dropdownState('filter_branch', 'Select Branch', '/ajax/branches', 'branchData', 'branch_name', 'id')" class="relative">
            <label class="form-label">Select Branch</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Branch</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" name="branch_id" id="filter_branch" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Select Branch</span>
                </button>
                <template x-for="opt in options" :key="opt.id">
                    <button type="button" @click="select(opt)" :class="selectedValue == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                        <span x-text="opt.name"></span>
                        <template x-if="selectedValue == opt.id">
                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                    </button>
                </template>
            </div>
        </div>

        <!-- Session -->
        <div x-data="dropdownState('filter_session', 'Select Session', '/ajax/sessions', 'sessionData', 'session_name', 'id')" class="relative">
            <label class="form-label">Session</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Session</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" name="session_year_id" id="filter_session" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Select Session</span>
                </button>
                <template x-for="opt in options" :key="opt.id">
                    <button type="button" @click="select(opt)" :class="selectedValue == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                        <span x-text="opt.name"></span>
                        <template x-if="selectedValue == opt.id">
                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                    </button>
                </template>
            </div>
        </div>

        <!-- Class * -->
        <div x-data="dropdownState('filter_class', 'Select Class', '/ajax/classes', 'classData', 'class_name', 'id')" class="relative">
            <label class="form-label">Class <span class="required-star">*</span></label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Class</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" name="class_id" id="filter_class" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Select Class</span>
                </button>
                <template x-for="opt in options" :key="opt.id">
                    <button type="button" @click="select(opt)" :class="selectedValue == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                        <span x-text="opt.name"></span>
                        <template x-if="selectedValue == opt.id">
                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                    </button>
                </template>
            </div>
        </div>

        <!-- Shift -->
        <div x-data="dropdownState('filter_shift', 'Select Shift', '/ajax/shifts', 'shiftData', 'shift_name', 'id')" class="relative">
            <label class="form-label">Shift</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Shift</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" name="shift_id" id="filter_shift" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Select Shift</span>
                </button>
                <template x-for="opt in options" :key="opt.id">
                    <button type="button" @click="select(opt)" :class="selectedValue == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                        <span x-text="opt.name"></span>
                        <template x-if="selectedValue == opt.id">
                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                    </button>
                </template>
            </div>
        </div>

        <!-- Section -->
        <div x-data="dropdownState('filter_section', 'Select Section', '/ajax/sections', 'sectionData', 'section_name', 'id')" class="relative">
            <label class="form-label">Section</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Section</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" name="section_id" id="filter_section" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Select Section</span>
                </button>
                <template x-for="opt in options" :key="opt.id">
                    <button type="button" @click="select(opt)" :class="selectedValue == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                        <span x-text="opt.name"></span>
                        <template x-if="selectedValue == opt.id">
                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                    </button>
                </template>
            </div>
        </div>

    </div>

    <!-- Processing Panel (Dashed Box with Live AJAX Preview) -->
    <div id="preview-container" class="mb-10">
        <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-gray-150 dark:border-white/[0.06] rounded-[2rem] group hover:border-themeBlue/30 transition-all">
            <div class="w-16 h-16 bg-themeBlue/10 dark:bg-themeBlue/15 text-themeBlue rounded-full flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            </div>
            <h4 class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Ready to Process</h4>
            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Select filters to preview students</p>
        </div>
    </div>

    <!-- Confirm Button -->
    <div class="flex justify-center">
        <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black h-11 px-16 rounded-xl hover:-translate-y-0.5 hover:shadow-lg transition-all uppercase tracking-widest text-sm flex items-center gap-3.5 border-none shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Generate ID Card PDF
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    let typingTimer;

    window.fetchPreview = async function() {
        let search = document.getElementsByName('student_id_search')[0].value;
        let branch_id = document.getElementById('filter_branch').value;
        let session_year_id = document.getElementById('filter_session').value;
        let class_id = document.getElementById('filter_class').value;
        let shift_id = document.getElementById('filter_shift').value;
        let section_id = document.getElementById('filter_section').value;

        // If all filters are empty, show the default placeholder
        if (!search.trim() && !branch_id && !session_year_id && !class_id && !shift_id && !section_id) {
            document.getElementById('preview-container').innerHTML = `
                <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-gray-150 dark:border-white/[0.06] rounded-[2rem] group hover:border-themeBlue/30 transition-all">
                    <div class="w-16 h-16 bg-themeBlue/10 dark:bg-themeBlue/15 text-themeBlue rounded-full flex items-center justify-center mb-4 group-hover:scale-105 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    </div>
                    <h4 class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Ready to Process</h4>
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Select filters to preview students</p>
                </div>
            `;
            return;
        }

        // Show Skeleton items while loading
        let skeletonHtml = `
            <div class="mb-4 flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-themeBlue animate-pulse">Loading preview...</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        `;
        for (let i = 0; i < 5; i++) {
            skeletonHtml += `
                <div class="animate-pulse bg-gray-50 dark:bg-themeDark/30 rounded-2xl p-4 border border-gray-100 dark:border-white/[0.06] flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700/60"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 bg-gray-200 dark:bg-gray-700/60 rounded w-3/4"></div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-700/60 rounded w-1/2"></div>
                    </div>
                </div>
            `;
        }
        skeletonHtml += `</div>`;
        document.getElementById('preview-container').innerHTML = skeletonHtml;

        try {
            let queryParams = new URLSearchParams({
                search: search,
                branch_id: branch_id,
                session_year_id: session_year_id,
                class_id: class_id,
                shift_id: shift_id,
                section_id: section_id,
                per_page: 50
            }).toString();

            let res = await axios.get(`/ajax/students?${queryParams}`, getAuthHeaders());
            let paginator = res.data.studentData;
            let data = paginator.data || [];

            if (data.length === 0) {
                document.getElementById('preview-container').innerHTML = `
                    <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-red-150 dark:border-red-950/20 rounded-[2rem] text-red-500">
                        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <h4 class="text-sm font-black uppercase tracking-widest">No Students Found</h4>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Try adjusting your filters</p>
                    </div>
                `;
                return;
            }

            let cardsHtml = `
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-themeBlue">Previewing Students (${paginator.total})</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">First ${data.length} previewed</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 max-h-[360px] overflow-y-auto pr-1">
            `;

            data.forEach(item => {
                let photoUrl = `https://ui-avatars.com/api/?name=${item.student_name}&background=008ED6&color=fff`;
                if (item.photo) {
                    photoUrl = item.photo.startsWith('img/') ? '/' + item.photo : '/storage/' + item.photo;
                }
                let className = item.school_class ? item.school_class.class_name : 'N/A';
                let rollNumber = item.roll_number ? `Roll: ${item.roll_number}` : 'N/A';

                cardsHtml += `
                    <div class="bg-white dark:bg-themeNavy/45 rounded-2xl p-3.5 border border-gray-150 dark:border-white/[0.06] flex items-center gap-3 shadow-sm hover:shadow-md transition-all">
                        <img src="${photoUrl}" alt="Photo" class="w-10 h-10 rounded-xl object-cover border border-gray-100 dark:border-white/[0.06] shrink-0">
                        <div class="overflow-hidden flex-1">
                            <p class="text-xs font-black text-gray-800 dark:text-gray-200 truncate leading-snug">${item.student_name}</p>
                            <p class="text-[9px] font-bold text-gray-450 dark:text-gray-500 truncate mt-0.5">${item.student_identity || 'N/A'} • ${className}</p>
                            <p class="text-[9px] font-bold text-themeBlue dark:text-themeBlue/80 truncate mt-0.5">${rollNumber}</p>
                        </div>
                    </div>
                `;
            });

            cardsHtml += `</div>`;
            document.getElementById('preview-container').innerHTML = cardsHtml;

        } catch (e) {
            console.error("Preview load error:", e);
            document.getElementById('preview-container').innerHTML = `
                <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-red-150 dark:border-red-950/20 rounded-[2rem] text-red-500">
                    <h4 class="text-sm font-black uppercase tracking-widest">Error Loading Preview</h4>
                </div>
            `;
        }
    };

    function validateForm(event) {
        let classInput = document.getElementById('filter_class');
        let searchInput = document.getElementsByName('student_id_search')[0];
        
        if((!searchInput || !searchInput.value.trim()) && (!classInput || !classInput.value)) {
            event.preventDefault();
            showAlert("Please select a Class or enter a Student ID/Roll for single print!", "Attention");
            return false;
        }
        return true;
    }

    // Set up listeners for the inputs to trigger live AJAX preview
    document.addEventListener('DOMContentLoaded', () => {
        let searchInput = document.getElementsByName('student_id_search')[0];
        searchInput.addEventListener('input', () => {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(window.fetchPreview, 400);
        });

        ['filter_branch', 'filter_session', 'filter_class', 'filter_shift', 'filter_section'].forEach(id => {
            let el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', window.fetchPreview);
            }
        });
    });
</script>
@endpush