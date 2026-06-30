@extends('tyro-dashboard::layouts.admin')

@section('title', 'Student List')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
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
<style>
    [x-cloak] { display: none !important; }
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    .table th, .table td { padding: 0.625rem 1rem !important; }
</style>
@endpush

@section('breadcrumb')
<span>Student Management</span>
@endsection

@section('content')
<!-- Header Section -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Student Management
        </h1>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Monitor, filter, and manage registered student accounts.</p>
    </div>
    <div class="flex items-center gap-2">
        <!-- Add Student -->
        <a href="{{ route('student.admission') }}" class="btn-sm bg-gradient-to-r from-themeBlue to-themeGreen text-white border-none rounded-xl hover:-translate-y-0.5 hover:shadow-lg transition-all flex items-center gap-1.5 !h-9 !px-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Add Student
        </a>

        <!-- Export Dropdown Button -->
        <div x-data="{ open: false }" class="relative inline-block text-left">
            <button @click="open = !open" type="button" class="btn-sm bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-white/[0.08] rounded-xl hover:-translate-y-0.5 hover:shadow-md transition-all flex items-center gap-1.5 !h-9 !px-3.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export</span>
                <svg class="w-3 h-3 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute right-0 z-50 w-44 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1">
                <button type="button" @click="window.exportToExcel(); open = false;" class="w-full flex items-center gap-2 px-4 py-2.5 text-xs text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-450 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Download Excel</span>
                </button>
                <button type="button" @click="window.exportToPDF(); open = false;" class="w-full flex items-center gap-2 px-4 py-2.5 text-xs text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-450 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Download PDF</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section (Placed borderless directly below title with reduced margins) -->
<div class="mb-2 pb-4 border-b border-gray-150 dark:border-white/[0.08]">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-4 items-end">
        <!-- Search Student -->
        <div>
            <label class="block text-[10px] font-black tracking-widest text-themeBlue uppercase mb-1.5">Search Student</label>
            <input type="text" id="filter_search" placeholder="Name, ID or Mobile..." class="form-input !h-10 !text-xs bg-gray-50/50 dark:bg-themeNavy rounded-xl focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all">
        </div>

        <!-- Dynamic Custom Dropdowns with Alpine.js -->
        <!-- Session -->
        <div x-data="dropdownState('filter_session', 'All Sessions', '/ajax/sessions', 'sessionData', 'session_name', 'id')" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Session</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All Sessions</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_session" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Sessions</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
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

        <!-- Class -->
        <div x-data="dropdownState('filter_class', 'All Classes', '/ajax/classes', 'classData', 'class_name', 'id')" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Class</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All Classes</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_class" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Classes</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
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

        <!-- Branch -->
        <div x-data="dropdownState('filter_branch', 'All Branches', '/ajax/branches', 'branchData', 'branch_name', 'id')" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Branch</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All Branches</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_branch" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Branches</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
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
        <div x-data="dropdownState('filter_shift', 'All Shifts', '/ajax/shifts', 'shiftData', 'shift_name', 'id')" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Shift</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All Shifts</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_shift" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Shifts</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
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
        <div x-data="dropdownState('filter_section', 'All Sections', '/ajax/sections', 'sectionData', 'section_name', 'id')" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Section</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All Sections</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_section" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Sections</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
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

        <!-- Gender (Static Dropdown) -->
        <div x-data="{ 
            open: false, 
            selectedLabel: 'All Genders', 
            selectedValue: '',
            select(label, val) {
                this.selectedLabel = label;
                this.selectedValue = val;
                this.open = false;
                let input = document.getElementById('filter_gender');
                if (input) {
                    input.value = val;
                    input.dispatchEvent(new Event('change'));
                }
            }
        }" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Gender</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All Genders</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_gender" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select('All Genders', '')" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Genders</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <button type="button" @click="select('Male', 'Male')" :class="selectedValue === 'Male' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Male</span>
                    <template x-if="selectedValue === 'Male'">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <button type="button" @click="select('Female', 'Female')" :class="selectedValue === 'Female' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Female</span>
                    <template x-if="selectedValue === 'Female'">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Borderless Table View (Zero margins, tight gap layout) -->
<div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0">
    <table class="w-full text-left border-collapse table">
        <thead>
            <tr class="!bg-transparent">
                <th class="w-16 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0">SL</th>
                <th class="w-24 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0">Photo</th>
                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0">Student Info</th>
                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0">Academic Details</th>
                <th class="text-right w-44 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0">Actions</th>
            </tr>
        </thead>
        <tbody id="tableList" class="divide-y divide-gray-150 dark:divide-white/[0.06]">
            @for ($i = 0; $i < 5; $i++)
            <tr class="animate-pulse">
                <td class="py-0 px-0"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                <td class="py-0 px-0"><div class="w-12 h-12 rounded-2xl bg-gray-200 dark:bg-gray-700/60"></div></td>
                <td class="py-0 px-0">
                    <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-2"></div>
                    <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-1.5"></div>
                    <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                </td>
                <td class="py-0 px-0">
                    <div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-2"></div>
                    <div class="h-3 w-36 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                </td>
                <td class="py-0 px-0">
                    <div class="flex items-center justify-end gap-2">
                        <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700/60"></div>
                        <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700/60"></div>
                        <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700/60"></div>
                    </div>
                </td>
            </tr>
            @endfor
        </tbody>
    </table>
</div>

<!-- Pagination Footer (Floating directly below table) -->
<div id="pagination-container" class="mt-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-black/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-white/[0.08] text-center">
        <div class="p-6">
            <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-950/30 flex items-center justify-center mx-auto mb-4 text-red-600 dark:text-red-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">Are you sure?</h3>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">This student's profile will be permanently deleted from database.</p>
            <input type="hidden" id="deleteID">
        </div>
        <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeNavy/30 flex justify-center gap-3 border-t border-gray-150 dark:border-white/[0.08]">
            <button onclick="window.closeModal('deleteModal')" class="btn btn-secondary w-full">Cancel</button>
            <button onclick="window.ConfirmDelete()" class="btn bg-red-600 hover:bg-red-700 text-white border-none w-full">Yes, Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.openModal = function(id) { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('modal-active'); };
    window.closeModal = function(id) { document.getElementById(id).classList.remove('modal-active'); document.getElementById(id).classList.add('hidden'); };

    // Fetch and display students with pagination
    window.fetchList = async function(page = 1) {
        let list = document.getElementById('tableList');
        
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
            gender: gender,
            page: page
        }).toString();

        try {
            let skeletonHtml = '';
            for (let i = 0; i < 5; i++) {
                skeletonHtml += `
                    <tr class="animate-pulse">
                        <td class="py-0 px-0"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                        <td class="py-0 px-0"><div class="w-12 h-12 rounded-2xl bg-gray-200 dark:bg-gray-700/60"></div></td>
                        <td class="py-0 px-0">
                            <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-2"></div>
                            <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-1.5"></div>
                            <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        </td>
                        <td class="py-0 px-0">
                            <div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-2"></div>
                            <div class="h-3 w-36 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        </td>
                        <td class="py-0 px-0">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700/60"></div>
                                <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700/60"></div>
                                <div class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-700/60"></div>
                            </div>
                        </td>
                    </tr>`;
            }
            list.innerHTML = skeletonHtml;
            
            let res = await axios.get(`/ajax/students?${queryParams}`, getAuthHeaders());
            list.innerHTML = ''; 
            
            let paginator = res.data.studentData; 
            let data = paginator.data || [];

            if (data.length === 0) {
                list.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-sm font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">No students found.</td></tr>`;
                document.getElementById('pagination-container').innerHTML = ''; 
                return;
            }

            let startingSl = (paginator.current_page - 1) * paginator.per_page; 

            data.forEach((item, index) => {
                let photoUrl = `https://ui-avatars.com/api/?name=${item.student_name}&background=008ED6&color=fff`;
                
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
                let studentGender = item.gender ? item.gender : 'N/A';

                let row = `
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                        <td class="py-0 px-0 text-sm font-bold text-gray-555 dark:text-gray-400">${startingSl + index + 1}</td>
                        <td class="py-0 px-0">
                            <img src="${photoUrl}" alt="Photo" class="w-12 h-12 rounded-2xl object-cover border border-gray-100 dark:border-white/[0.08] shadow-sm">
                        </td>
                        <td class="py-0 px-0">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">${item.student_name}</div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 mt-1">ID: <span class="text-gray-700 dark:text-gray-300 font-bold">${item.student_identity || 'N/A'}</span></div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 mt-0.5">Mob: <span class="text-gray-700 dark:text-gray-300 font-bold">${item.guardian_mobile}</span></div>
                        </td>
                        <td class="py-0 px-0">
                            <div class="text-sm text-gray-800 dark:text-gray-200">
                                <span class="font-black text-themeBlue">${className}</span> 
                                <span class="ml-2 px-2 py-0.5 bg-gray-100 dark:bg-themeDark text-[10px] font-black rounded-lg text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-white/[0.06]">ROLL: ${rollNumber}</span>
                            </div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                                <span>Sec: <span class="text-gray-700 dark:text-gray-300 font-bold">${sectionName}</span></span>
                                <span>Shift: <span class="text-gray-700 dark:text-gray-300 font-bold">${shiftName}</span></span>
                                <span>Gender: <span class="text-gray-700 dark:text-gray-300 font-bold">${studentGender}</span></span>
                            </div>
                        </td>
                        <td class="py-0 px-0">
                            <div class="flex items-center justify-end gap-2">
                                <a href="/student/view/${item.id}" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="/student/edit/${item.id}" class="action-btn text-indigo-600 hover:text-indigo-800 hover:border-indigo-600" title="Edit Profile">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button onclick="window.DeleteID(${item.id})" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Student">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>`;
                list.innerHTML += row;
            });

            // Render Pagination Logic
            renderPagination(paginator);

        } catch (e) { 
            console.error(e);
            list.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-sm font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">Error loading student data.</td></tr>`; 
        }
    };

    // UI builder for pagination
    function renderPagination(paginator) {
        let container = document.getElementById('pagination-container');
        if (!paginator || paginator.last_page <= 1) {
            container.innerHTML = `<div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Showing ${paginator.total || 0} entries</div>`;
            return;
        }

        let html = `<div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4 md:mb-0">
                        Showing <span class="font-black text-gray-750 dark:text-gray-300">${paginator.from || 0}</span> to <span class="font-black text-gray-750 dark:text-gray-300">${paginator.to || 0}</span> of <span class="font-black text-gray-750 dark:text-gray-300">${paginator.total}</span> entries
                    </div>`;
        
        html += `<div class="flex items-center space-x-1.5">`;

        // Prev Button
        if (paginator.current_page > 1) {
            html += `<button onclick="window.fetchList(${paginator.current_page - 1})" class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs">Prev</button>`;
        } else {
            html += `<button disabled class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs opacity-50 cursor-not-allowed">Prev</button>`;
        }

        // Page Numbers
        for(let i=1; i<=paginator.last_page; i++) {
            if (i === 1 || i === paginator.last_page || Math.abs(paginator.current_page - i) <= 1) {
                if (i === paginator.current_page) {
                    html += `<button class="btn-xs btn-primary !h-9 !w-9 !p-0 !rounded-lg !text-xs shadow-sm">${i}</button>`;
                } else {
                    html += `<button onclick="window.fetchList(${i})" class="btn-xs btn-secondary !h-9 !w-9 !p-0 !rounded-lg !text-xs">${i}</button>`;
                }
            } else if (Math.abs(paginator.current_page - i) === 2) {
                html += `<span class="px-2 text-gray-400 font-bold">...</span>`;
            }
        }

        // Next Button
        if (paginator.current_page < paginator.last_page) {
            html += `<button onclick="window.fetchList(${paginator.current_page + 1})" class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs">Next</button>`;
        } else {
            html += `<button disabled class="btn-xs btn-secondary !h-9 !py-0 !px-3 !rounded-lg !text-xs opacity-50 cursor-not-allowed">Next</button>`;
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
        } catch (e) { showAlert("Delete Failed!", "Error"); }
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

    ['filter_branch', 'filter_class', 'filter_section', 'filter_session', 'filter_shift', 'filter_gender'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() { window.fetchList(1); });
    });

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        window.fetchList();
    });
</script>
@endpush