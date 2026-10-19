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
            defaultLabel: defaultLabel,
            selectedLabel: defaultLabel,
            selectedValue: '',
            options: [],
            reset() {
                this.selectedLabel = this.defaultLabel;
                this.selectedValue = '';
                this.open = false;
                let input = document.getElementById(id);
                if (input) {
                    input.value = '';
                }
            },
            async init() {
                if (ajaxUrl) {
                    try {
                        let res = await axios.get(ajaxUrl, getAuthHeaders());
                        let list = res.data[dataKey] || [];
                        this.options = list.map(item => {
                            let name = item[nameField];
                            if (id.includes('shift') && name) {
                                name = name.replace(/\s*student/i, '').replace(/\s*staff/i, '').trim();
                            }
                            return {
                                id: item[idField],
                                name: name
                            };
                        });
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
    window.areaDropdownState = function() {
        return {
            open: false,
            search: '',
            selectedLabel: 'All Areas',
            selectedValue: '',
            options: [],
            get filteredOptions() {
                if (!this.search.trim()) return this.options;
                let q = this.search.toLowerCase().trim();
                return this.options.filter(opt => opt.toLowerCase().includes(q));
            },
            async init() {
                try {
                    let res = await axios.get('/ajax/students/areas', getAuthHeaders());
                    if (res.data && res.data.areas) {
                        this.options = res.data.areas;
                    }
                } catch (e) {
                    console.error('Failed to load areas', e);
                }
            },
            select(label, val) {
                this.selectedLabel = label;
                this.selectedValue = val;
                this.open = false;
                let input = document.getElementById('filter_area');
                if (input) {
                    input.value = val;
                    input.dispatchEvent(new Event('change'));
                }
            },
            reset() {
                this.selectedLabel = 'All Areas';
                this.selectedValue = '';
                this.search = '';
                this.open = false;
                let input = document.getElementById('filter_area');
                if (input) input.value = '';
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
        <!-- Bulk RFID Sync -->
        <button type="button" onclick="bulkSyncRfidCards()" class="btn-sm bg-white dark:bg-themeNavy text-themeBlue border border-themeBlue/25 rounded-xl hover:bg-themeBlue/5 hover:-translate-y-0.5 hover:shadow-md transition-all flex items-center gap-1.5 !h-9 !px-3">
            <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            <span>Bulk RFID Sync</span>
        </button>

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
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3 sm:gap-4 items-end">
        <!-- Search Student -->
        <div>
            <label class="block text-[10px] font-black tracking-widest text-themeBlue uppercase mb-1.5">Search Student</label>
            <input type="text" id="filter_search" placeholder="Name, ID or Mobile..." class="form-input !h-10 !text-xs bg-gray-50/50 dark:bg-themeNavy rounded-xl focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all">
        </div>

        <!-- Dynamic Custom Dropdowns with Alpine.js -->
        <!-- Branch -->
        <div x-data="dropdownState('filter_branch', 'All Branches', '/ajax/branches', 'branchData', 'branch_name', 'id')" @reset-filters.window="reset()" class="relative">
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

        <!-- Session -->
        <div x-data="dropdownState('filter_session', 'All Sessions', '/ajax/sessions', 'sessionData', 'session_name', 'id')" @reset-filters.window="reset()" class="relative">
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
        <div x-data="dropdownState('filter_class', 'All Classes', '/ajax/classes', 'classData', 'class_name', 'id')" @reset-filters.window="reset()" class="relative">
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

        <!-- Shift -->
        <div x-data="dropdownState('filter_shift', 'All Shifts', '/ajax/shifts?type=student', 'shiftData', 'shift_name', 'id')" @reset-filters.window="reset()" class="relative">
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
        <div x-data="dropdownState('filter_section', 'All Sections', '/ajax/sections', 'sectionData', 'section_name', 'id')" @reset-filters.window="reset()" class="relative">
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
            },
            reset() {
                this.selectedLabel = 'All Genders';
                this.selectedValue = '';
                let input = document.getElementById('filter_gender');
                if (input) input.value = '';
            }
        }" @reset-filters.window="reset()" class="relative">
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

        <!-- Status -->
        <div x-data="{
            open: false,
            selectedValue: '',
            selectedLabel: 'All Status',
            select(label, val) {
                this.selectedLabel = label;
                this.selectedValue = val;
                this.open = false;
                let input = document.getElementById('filter_status');
                if (input) {
                    input.value = val;
                    input.dispatchEvent(new Event('change'));
                }
            },
            reset() {
                this.selectedLabel = 'All Status';
                this.selectedValue = '';
                let input = document.getElementById('filter_status');
                if (input) input.value = '';
            }
        }" @reset-filters.window="reset()" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Status</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All Status</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_status" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select('All Status', '')" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Status</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <button type="button" @click="select('Active', 'Active')" :class="selectedValue === 'Active' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                    </span>
                    <template x-if="selectedValue === 'Active'">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <button type="button" @click="select('Inactive', 'Inactive')" :class="selectedValue === 'Inactive' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Inactive
                    </span>
                    <template x-if="selectedValue === 'Inactive'">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
            </div>
        </div>

        <!-- RFID Card Status -->
        <div x-data="{
            open: false,
            selectedValue: '',
            selectedLabel: 'All RFID Cards',
            select(label, val) {
                this.selectedLabel = label;
                this.selectedValue = val;
                this.open = false;
                let input = document.getElementById('filter_rfid_status');
                if (input) {
                    input.value = val;
                    input.dispatchEvent(new Event('change'));
                }
            },
            reset() {
                this.selectedLabel = 'All RFID Cards';
                this.selectedValue = '';
                let input = document.getElementById('filter_rfid_status');
                if (input) input.value = '';
            }
        }" @reset-filters.window="reset()" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">RFID Card</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">All RFID Cards</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_rfid_status" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select('All RFID Cards', '')" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All RFID Cards</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <button type="button" @click="select('Card Synced', 'synced')" :class="selectedValue === 'synced' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Card Synced
                    </span>
                    <template x-if="selectedValue === 'synced'">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <button type="button" @click="select('Not Synced', 'not_synced')" :class="selectedValue === 'not_synced' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> Not Synced
                    </span>
                    <template x-if="selectedValue === 'not_synced'">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
            </div>
        </div>

        <!-- Area / Village -->
        <div x-data="areaDropdownState()" @reset-filters.window="reset()" class="relative">
            <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Area / Village</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-10 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span class="truncate pr-1" x-text="selectedLabel">All Areas</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="filter_area" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-64 sm:w-72 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <div class="p-2 border-b border-gray-100 dark:border-white/[0.06] sticky top-0 bg-white dark:bg-themeNavy z-10">
                    <input type="text" x-model="search" placeholder="Search area..." @click.stop class="w-full px-2.5 py-1.5 text-xs bg-gray-50/70 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg focus:outline-none focus:border-themeBlue text-gray-800 dark:text-gray-200">
                </div>
                <button type="button" @click="select('All Areas', '')" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>All Areas</span>
                    <template x-if="selectedValue === ''">
                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                </button>
                <template x-for="item in filteredOptions" :key="item">
                    <button type="button" @click="select(item, item)" :class="selectedValue === item ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                        <span x-text="item"></span>
                        <template x-if="selectedValue === item">
                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                    </button>
                </template>
                <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-xs text-gray-400 text-center">
                    No matching area found
                </div>
            </div>
        </div>

        <!-- Reset Button -->
        <div>
            <label class="block text-[10px] font-black tracking-widest text-gray-400 dark:text-gray-550 uppercase mb-1.5">Reset</label>
            <button type="button" onclick="window.resetFilters()" title="Reset all filters" class="w-full flex items-center justify-center gap-2 px-3 h-10 text-xs font-black uppercase tracking-[0.1em] bg-gray-50/50 hover:bg-rose-50/70 dark:bg-themeNavy dark:hover:bg-rose-950/20 text-gray-600 hover:text-rose-600 dark:text-gray-300 dark:hover:text-rose-400 border-2 border-gray-100 hover:border-rose-200 dark:border-gray-800 dark:hover:border-rose-900/40 rounded-xl transition-all shadow-sm hover:shadow hover:-translate-y-0.5 group">
                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-rose-500 group-hover:rotate-[-90deg] transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Reset</span>
            </button>
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

<!-- Discontinuation & Fee Reconciliation Modal -->
<div id="discontinuationModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-black/40 backdrop-blur-md p-4 overflow-y-auto">
    <div class="bg-white dark:bg-themeNavy w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-gray-150 dark:border-white/[0.08] my-8">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-gray-150 dark:border-white/[0.08] flex items-center justify-between bg-gray-50/50 dark:bg-themeDark/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center text-rose-600 dark:text-rose-450">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        Discontinue Student
                        <span id="discStudentIdentity" class="text-xs px-2 py-0.5 rounded-lg font-mono font-bold bg-themeBlue/10 text-themeBlue"></span>
                    </h3>
                    <p id="discStudentName" class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5"></p>
                </div>
            </div>
            <button type="button" onclick="window.closeModal('discontinuationModal')" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark/60 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
            <input type="hidden" id="discStudentId">

            @php
                $currentYearNum = (int) date('Y');
                $currentMonthNum = date('m');
                $currentMonthName = date('F');
                $sessionYearsList = \App\Models\SessionYear::pluck('session_name')->map(function($s) {
                    return preg_match('/\b(20\d{2})\b/', $s, $m) ? (int)$m[1] : null;
                })->filter()->all();
                $yearRangeList = range($currentYearNum + 1, $currentYearNum - 4);
                $availableDiscYears = array_values(array_unique(array_merge($yearRangeList, $sessionYearsList)));
                rsort($availableDiscYears);
            @endphp
            <!-- Month, Year & Reason Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Custom Alpine Month Dropdown -->
                <div x-data="{
                    open: false,
                    value: '{{ $currentMonthNum }}',
                    label: '{{ $currentMonthName }}',
                    items: [
                        { value: '01', label: 'January' },
                        { value: '02', label: 'February' },
                        { value: '03', label: 'March' },
                        { value: '04', label: 'April' },
                        { value: '05', label: 'May' },
                        { value: '06', label: 'June' },
                        { value: '07', label: 'July' },
                        { value: '08', label: 'August' },
                        { value: '09', label: 'September' },
                        { value: '10', label: 'October' },
                        { value: '11', label: 'November' },
                        { value: '12', label: 'December' }
                    ],
                    setMonth(val) {
                        let pad = String(val).padStart(2, '0');
                        let found = this.items.find(i => i.value === pad);
                        if (found) {
                            this.value = found.value;
                            this.label = found.label;
                        }
                        let el = document.getElementById('discMonth');
                        if (el) el.value = pad;
                    },
                    select(item) {
                        this.value = item.value;
                        this.label = item.label;
                        this.open = false;
                        let el = document.getElementById('discMonth');
                        if (el) el.value = item.value;
                        window.onDiscontinuationPeriodChange();
                    }
                }" @set-disc-month.window="setMonth($event.detail)" @click.away="open = false" class="relative" :class="open ? 'z-30' : 'z-20'">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Last Active Month *</label>
                    <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="label">{{ $currentMonthName }}</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" id="discMonth" :value="value" value="{{ $currentMonthNum }}">

                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <template x-for="item in items" :key="item.value">
                            <button type="button" @click="select(item)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                                <span x-text="item.label"></span>
                                <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Final billing month.</p>
                </div>

                <!-- Custom Alpine Year Dropdown -->
                <div x-data="{
                    open: false,
                    value: '{{ $currentYearNum }}',
                    label: '{{ $currentYearNum }}',
                    items: [
                        @foreach($availableDiscYears as $yr)
                            { value: '{{ $yr }}', label: '{{ $yr }}' },
                        @endforeach
                    ],
                    setYear(val) {
                        let y = String(val);
                        let found = this.items.find(i => String(i.value) === y);
                        if (!found) {
                            this.items.unshift({ value: y, label: y });
                            this.items.sort((a, b) => Number(b.value) - Number(a.value));
                        }
                        this.value = y;
                        this.label = y;
                        let el = document.getElementById('discYear');
                        if (el) el.value = y;
                    },
                    select(item) {
                        this.value = item.value;
                        this.label = item.label;
                        this.open = false;
                        let el = document.getElementById('discYear');
                        if (el) el.value = item.value;
                        window.onDiscontinuationPeriodChange();
                    }
                }" @set-disc-year.window="setYear($event.detail)" @click.away="open = false" class="relative" :class="open ? 'z-30' : 'z-10'">
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Last Active Year *</label>
                    <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="label">{{ $currentYearNum }}</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" id="discYear" :value="value" value="{{ $currentYearNum }}">

                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <template x-for="item in items" :key="item.value">
                            <button type="button" @click="select(item)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'">
                                <span x-text="item.label"></span>
                                <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Billing session year.</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-gray-500 uppercase mb-1.5">Discontinuation Reason</label>
                    <input type="text" id="discReason" placeholder="e.g. TC issued, Family relocated..." class="w-full px-3.5 h-11 text-xs font-semibold rounded-xl bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Optional note for audit logs.</p>
                </div>
            </div>
            <input type="hidden" id="discLastActiveMonth">
            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                Note: The student remains active through this month and becomes inactive effective the 1st day of the following month.
            </p>

            <!-- Analyze Button -->
            <div class="flex justify-end">
                <button type="button" onclick="window.loadDiscontinuationPreview()" id="btnAnalyzeInvoices" class="h-9 px-4 bg-themeBlue text-white text-xs font-black rounded-xl hover:bg-themeBlue/90 transition-all flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Analyze Invoices
                </button>
            </div>

            <!-- Loading Skeleton -->
            <div id="discLoadingSkeleton" class="hidden py-6 text-center space-y-3">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-themeBlue border-r-transparent"></div>
                <p class="text-xs font-bold text-gray-500">Analyzing billing records & payment history...</p>
            </div>

            <!-- Preview Results Container -->
            <div id="discPreviewResults" class="hidden space-y-4 pt-2">
                <!-- Effective Banner -->
                <div class="p-3.5 rounded-2xl bg-indigo-50/70 dark:bg-themeBlue/10 border border-indigo-100 dark:border-themeBlue/20 flex items-center justify-between text-xs">
                    <span class="font-bold text-gray-700 dark:text-gray-200">
                        Effective Inactive Date: <strong id="discEffectiveDate" class="text-themeBlue"></strong>
                    </span>
                    <span class="text-[11px] font-semibold text-gray-500">
                        Last Active: <span id="discLastActiveLabel" class="font-bold text-gray-700 dark:text-gray-300"></span>
                    </span>
                </div>

                <!-- 3 Categories Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- 1. Historical Invoices (Preserved) -->
                    <div class="p-3.5 rounded-2xl border border-emerald-150 dark:border-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-500/5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Historical Invoices</span>
                            <span id="discHistCount" class="text-xs px-2 py-0.5 rounded-full font-bold bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">0</span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Preserved untouched. Valid historical bills.</p>
                        <div class="mt-2 text-xs font-black text-gray-800 dark:text-gray-200">
                            Dues: ৳<span id="discHistDue">0.00</span>
                        </div>
                    </div>

                    <!-- 2. Safe to Cancel Invoices -->
                    <div class="p-3.5 rounded-2xl border border-sky-150 dark:border-sky-500/20 bg-sky-50/40 dark:bg-sky-500/5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-black uppercase tracking-wider text-sky-700 dark:text-sky-400">Safe to Cancel</span>
                            <span id="discCancelCount" class="text-xs px-2 py-0.5 rounded-full font-bold bg-sky-100 dark:bg-sky-500/20 text-sky-700 dark:text-sky-300">0</span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Unpaid post-discontinuation invoices to be cancelled.</p>
                        <div class="mt-2 text-xs font-black text-sky-600 dark:text-sky-400">
                            Clear: ৳<span id="discCancelDue">0.00</span>
                        </div>
                    </div>

                    <!-- 3. Invoices with Payments (Requires Review) -->
                    <div class="p-3.5 rounded-2xl border border-amber-150 dark:border-amber-500/20 bg-amber-50/40 dark:bg-amber-500/5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">Has Payments</span>
                            <span id="discReviewCount" class="text-xs px-2 py-0.5 rounded-full font-bold bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300">0</span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Will NOT be auto-cancelled. Preserved for review.</p>
                        <div class="mt-2 text-xs font-black text-amber-600 dark:text-amber-400">
                            Paid: ৳<span id="discReviewPaid">0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Invoices Details List Accordion -->
                <div id="discInvoicesDetailBox" class="space-y-2 text-xs">
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeNavy/30 flex items-center justify-between border-t border-gray-150 dark:border-white/[0.08]">
            <button type="button" onclick="window.closeModal('discontinuationModal')" class="h-10 px-4 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                Close
            </button>
            <button type="button" id="btnConfirmDiscontinue" onclick="window.submitDiscontinuationReconcile()" disabled class="h-10 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-wider opacity-50 cursor-not-allowed">
                Confirm Discontinuation & Reconcile
            </button>
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
        let status = document.getElementById('filter_status') ? document.getElementById('filter_status').value : '';
        let rfid_status = document.getElementById('filter_rfid_status') ? document.getElementById('filter_rfid_status').value : '';
        let area = document.getElementById('filter_area') ? document.getElementById('filter_area').value : '';

        let queryParams = new URLSearchParams({
            search: search_text,
            branch_id: branch_id,
            class_id: class_id,
            section_id: section_id,
            session_year_id: session_id,
            shift_id: shift_id,
            gender: gender,
            status: status,
            rfid_status: rfid_status,
            area: area,
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

                // Format Section to remove the word "Section" or "Section "
                let formattedSection = sectionName;
                if (sectionName && sectionName !== 'N/A') {
                    formattedSection = sectionName.replace(/section\s*/i, '').trim();
                }

                // Format Shift to remove the word " Student" or "Student"
                let formattedShift = shiftName;
                if (shiftName && shiftName !== 'N/A') {
                    formattedShift = shiftName.replace(/\s*student/i, '').trim();
                }

                let discBadge = (item.status === 'Inactive' && item.discontinued_month)
                    ? `<span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-gray-100 dark:bg-themeDark text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-white/[0.06]">Disc: ${item.discontinued_month}</span>`
                    : '';

                let safeStudentName = (item.student_name || '').replace(/'/g, "\\'");
                let safeIdentity = (item.student_identity || '').replace(/'/g, "\\'");
                let safeDiscMonth = item.discontinued_month || '';

                let statusBadge = item.status === 'Inactive'
                    ? `<button type="button" onclick="toggleStudentStatus(${item.id}, 'Active', '${safeStudentName}', '${safeIdentity}', '${safeDiscMonth}')" title="Click to reactivate student" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-450 border border-rose-200 dark:border-rose-500/20 hover:scale-105 transition-all cursor-pointer">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactive
                       </button>${discBadge}`
                    : `<button type="button" onclick="toggleStudentStatus(${item.id}, 'Inactive', '${safeStudentName}', '${safeIdentity}', '${safeDiscMonth}')" title="Click to discontinue & reconcile invoices" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-450 border border-emerald-200 dark:border-emerald-500/20 hover:scale-105 transition-all cursor-pointer">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active
                       </button>`;

                let row = `
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                        <td class="py-0 px-0 text-sm font-bold text-gray-555 dark:text-gray-400">${startingSl + index + 1}</td>
                        <td class="py-0 px-0">
                            <img src="${photoUrl}" alt="Photo" class="w-12 h-12 rounded-2xl object-cover border border-gray-100 dark:border-white/[0.08] shadow-sm">
                        </td>
                        <td class="py-0 px-0">
                            <div class="flex items-center gap-2">
                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100">${item.student_name}</div>
                                ${statusBadge}
                            </div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-550 mt-1">
                                ID: <span class="text-gray-700 dark:text-gray-300 font-bold">${item.student_identity || 'N/A'}</span>
                                <span class="mx-1 text-gray-300 dark:text-gray-700">|</span> Device ID: <span class="text-themeBlue font-bold">${item.id}</span>
                                <span class="mx-1 text-gray-300 dark:text-gray-700">|</span> Card: <span class="${item.card_number ? 'text-themeGreen font-black' : 'text-gray-400 font-semibold'}" id="card-num-${item.id}">${item.card_number || 'N/A'}</span>
                            </div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-550 mt-0.5">Mob: <span class="text-gray-700 dark:text-gray-300 font-bold">${item.guardian_mobile}</span></div>
                        </td>
                        <td class="py-0 px-0">
                            <div class="text-sm text-gray-800 dark:text-gray-200">
                                <span class="font-black text-themeBlue">${className}</span> 
                                <span class="ml-2 px-2 py-0.5 bg-gray-100 dark:bg-themeDark text-[10px] font-black rounded-lg text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-white/[0.06]">ROLL: ${rollNumber}</span>
                            </div>
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                                <span>Section: <span class="text-gray-700 dark:text-gray-300 font-bold">${formattedSection}</span></span>
                                <span>Shift: <span class="text-gray-700 dark:text-gray-300 font-bold">${formattedShift}</span></span>
                            </div>
                        </td>
                        <td class="py-0 px-0">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="syncRfidCardSingle(${item.id})" class="action-btn text-themeGreen hover:text-themeGreen hover:border-themeGreen" title="Sync RFID Card">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                </button>
                                <button onclick="pushToDeviceSingle(${item.id})" class="action-btn text-indigo-500 hover:text-indigo-700 hover:border-indigo-500" title="Push Student to Device">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                                </button>
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
        let status = document.getElementById('filter_status') ? document.getElementById('filter_status').value : '';
        let rfid_status = document.getElementById('filter_rfid_status') ? document.getElementById('filter_rfid_status').value : '';
        let area = document.getElementById('filter_area') ? document.getElementById('filter_area').value : '';

        let queryParams = new URLSearchParams({
            search: search_text,
            branch_id: branch_id,
            class_id: class_id,
            section_id: section_id,
            session_year_id: session_id,
            shift_id: shift_id,
            gender: gender,
            status: status,
            rfid_status: rfid_status,
            area: area
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
        let status = document.getElementById('filter_status') ? document.getElementById('filter_status').value : '';
        let rfid_status = document.getElementById('filter_rfid_status') ? document.getElementById('filter_rfid_status').value : '';
        let area = document.getElementById('filter_area') ? document.getElementById('filter_area').value : '';

        let queryParams = new URLSearchParams({
            search: search_text,
            branch_id: branch_id,
            class_id: class_id,
            section_id: section_id,
            session_year_id: session_id,
            shift_id: shift_id,
            gender: gender,
            status: status,
            rfid_status: rfid_status,
            area: area
        }).toString();

        window.open(`/ajax/students/export-pdf?${queryParams}`, '_blank');
    };

    // Reset all filters back to defaults
    window.resetFilters = function() {
        let searchInput = document.getElementById('filter_search');
        if (searchInput) {
            searchInput.value = '';
        }

        ['filter_branch', 'filter_class', 'filter_section', 'filter_session', 'filter_shift', 'filter_gender', 'filter_status', 'filter_rfid_status', 'filter_area'].forEach(id => {
            let el = document.getElementById(id);
            if (el) {
                el.value = '';
            }
        });

        window.dispatchEvent(new CustomEvent('reset-filters'));

        window.fetchList(1);
    };

    // Event Listeners
    let typingTimer;
    document.getElementById('filter_search').addEventListener('input', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function () { window.fetchList(1); }, 400); 
    });

    ['filter_branch', 'filter_class', 'filter_section', 'filter_session', 'filter_shift', 'filter_gender', 'filter_status', 'filter_rfid_status', 'filter_area'].forEach(id => {
        let el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function() { window.fetchList(1); });
        }
    });

    window.toggleStudentStatus = async function(studentId, targetStatus, studentName = '', studentIdentity = '', discontinuedMonth = '') {
        if (targetStatus === 'Inactive') {
            window.openDiscontinuationModal(studentId, studentName, studentIdentity, discontinuedMonth);
            return;
        }

        // Reactivation confirmation
        let confirmed = await showConfirm(
            "Reactivate Student?",
            `Do you want to reactivate ${studentName || 'this student'}? They will be active for current and future billing periods. Historical discontinuation logs will remain preserved.`
        );
        if (!confirmed) return;

        try {
            let res = await axios.post(`/ajax/students/${studentId}/reactivate`, {}, getAuthHeaders());
            if (res.data.status === 'success') {
                if (typeof showSuccess === 'function') {
                    await showSuccess(res.data.message);
                } else {
                    await showAlert(res.data.message, "Reactivated");
                }
                window.fetchList();
            } else {
                await showAlert(res.data.message || "Reactivation failed.", "Error");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Reactivation failed.";
            await showAlert(errMsg, "Error");
        }
    };

    window.onDiscontinuationPeriodChange = function() {
        let monthEl = document.getElementById('discMonth');
        let yearEl = document.getElementById('discYear');
        if (!monthEl || !yearEl) return;

        let mVal = monthEl.value;
        let yVal = yearEl.value;
        let ym = `${yVal}-${mVal}`;
        document.getElementById('discLastActiveMonth').value = ym;

        // Calculate and preview effective inactive date (first day of following month)
        let mNum = parseInt(mVal, 10);
        let yNum = parseInt(yVal, 10);
        let nextMonth = mNum === 12 ? 1 : mNum + 1;
        let nextYear = mNum === 12 ? yNum + 1 : yNum;
        let monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        let effectiveStr = `01 ${monthNames[nextMonth]} ${nextYear}`;

        let effEl = document.getElementById('discEffectiveDate');
        if (effEl) effEl.innerText = effectiveStr;
        let labelEl = document.getElementById('discLastActiveLabel');
        if (labelEl) labelEl.innerText = `${monthNames[mNum]} ${yNum}`;

        window.loadDiscontinuationPreview();
    };

    window.openDiscontinuationModal = function(id, name, identity, currentDiscMonth) {
        document.getElementById('discStudentId').value = id;
        document.getElementById('discStudentName').innerText = name || '';
        document.getElementById('discStudentIdentity').innerText = identity ? `ID: ${identity}` : '';

        // Default to previous month or current month (YYYY-MM)
        let now = new Date();
        let defaultYear = now.getFullYear();
        let defaultMonth = String(now.getMonth() + 1).padStart(2, '0');

        if (currentDiscMonth && currentDiscMonth.includes('-')) {
            let parts = currentDiscMonth.split('-');
            defaultYear = parseInt(parts[0], 10);
            defaultMonth = parts[1].padStart(2, '0');
        } else {
            let prevDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            defaultYear = prevDate.getFullYear();
            defaultMonth = String(prevDate.getMonth() + 1).padStart(2, '0');
        }

        let yearEl = document.getElementById('discYear');
        let monthEl = document.getElementById('discMonth');

        if (yearEl) yearEl.value = defaultYear;
        if (monthEl) monthEl.value = defaultMonth;

        window.dispatchEvent(new CustomEvent('set-disc-month', { detail: defaultMonth }));
        window.dispatchEvent(new CustomEvent('set-disc-year', { detail: defaultYear }));

        document.getElementById('discLastActiveMonth').value = `${defaultYear}-${defaultMonth}`;
        document.getElementById('discReason').value = '';

        document.getElementById('discPreviewResults').classList.add('hidden');
        let confirmBtn = document.getElementById('btnConfirmDiscontinue');
        confirmBtn.disabled = true;
        confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');

        window.openModal('discontinuationModal');
        window.onDiscontinuationPeriodChange();
    };

    window.loadDiscontinuationPreview = async function() {
        let studentId = document.getElementById('discStudentId').value;
        let month = document.getElementById('discLastActiveMonth').value;
        if (!month) {
            await showAlert("Please select the last active billing month.", "Input Required");
            return;
        }

        document.getElementById('discLoadingSkeleton').classList.remove('hidden');
        document.getElementById('discPreviewResults').classList.add('hidden');
        let confirmBtn = document.getElementById('btnConfirmDiscontinue');
        confirmBtn.disabled = true;
        confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');

        try {
            let res = await axios.get(`/ajax/students/${studentId}/discontinuation-preview?last_active_month=${month}`, getAuthHeaders());
            document.getElementById('discLoadingSkeleton').classList.add('hidden');

            if (res.data.status === 'success') {
                let data = res.data;
                document.getElementById('discEffectiveDate').innerText = data.discontinuation.effective_date;
                document.getElementById('discLastActiveLabel').innerText = data.discontinuation.last_active_month_name;

                // Summary cards
                document.getElementById('discHistCount').innerText = data.historical_category.count;
                document.getElementById('discHistDue').innerText = Number(data.historical_category.total_due).toFixed(2);

                document.getElementById('discCancelCount').innerText = data.cancellation_category.count;
                document.getElementById('discCancelDue').innerText = Number(data.cancellation_category.total_due).toFixed(2);

                document.getElementById('discReviewCount').innerText = data.review_category.count;
                document.getElementById('discReviewPaid').innerText = Number(data.review_category.total_paid).toFixed(2);

                // Details list
                let detailBox = document.getElementById('discInvoicesDetailBox');
                let html = '';

                if (data.cancellation_category.count > 0) {
                    html += `<div class="p-3 bg-sky-50/50 dark:bg-sky-500/5 rounded-2xl border border-sky-150 dark:border-sky-500/10">
                        <div class="font-bold text-sky-800 dark:text-sky-300 mb-1.5 flex items-center justify-between">
                            <span>Invoices to be Cancelled (${data.cancellation_category.count})</span>
                            <span class="text-[11px] font-mono font-black">Total Due: ৳${Number(data.cancellation_category.total_due).toFixed(2)}</span>
                        </div>
                        <div class="space-y-1 max-h-36 overflow-y-auto pr-1">`;
                    data.cancellation_category.invoices.forEach(inv => {
                        html += `<div class="flex items-center justify-between py-1 border-b border-sky-100 dark:border-sky-500/10 last:border-none">
                            <div>
                                <span class="font-mono font-bold text-gray-700 dark:text-gray-300">${inv.invoice_no}</span>
                                <span class="text-gray-400 dark:text-gray-500 ml-1.5">(${inv.month_name} - ${inv.category_name})</span>
                            </div>
                            <div class="font-mono font-bold text-gray-800 dark:text-gray-200">
                                ৳${Number(inv.due_amount).toFixed(2)}
                            </div>
                        </div>`;
                    });
                    html += `</div></div>`;
                } else {
                    html += `<div class="p-3 bg-gray-50/80 dark:bg-themeDark/40 rounded-2xl text-gray-500 text-center font-medium">No post-discontinuation unpaid invoices found to cancel.</div>`;
                }

                if (data.review_category.count > 0) {
                    html += `<div class="p-3 bg-amber-50/50 dark:bg-amber-500/5 rounded-2xl border border-amber-200 dark:border-amber-500/20">
                        <div class="font-bold text-amber-800 dark:text-amber-300 mb-1.5 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Invoices with Payments (Will NOT be auto-cancelled)
                            </span>
                            <span class="text-[11px] font-mono font-black text-amber-700 dark:text-amber-300">Paid: ৳${Number(data.review_category.total_paid).toFixed(2)}</span>
                        </div>
                        <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">`;
                    data.review_category.invoices.forEach(inv => {
                        let rList = inv.receipts.map(r => `${r.receipt_no} (৳${r.paid_amount})`).join(', ');
                        html += `<div class="py-1 border-b border-amber-100 dark:border-amber-500/10 last:border-none">
                            <div class="flex items-center justify-between">
                                <span class="font-mono font-bold text-gray-700 dark:text-gray-300">${inv.invoice_no} (${inv.month_name})</span>
                                <span class="font-mono font-bold text-amber-700 dark:text-amber-300">Paid: ৳${Number(inv.paid_amount).toFixed(2)}</span>
                            </div>
                            <div class="text-[10px] text-gray-500 mt-0.5">Receipts: ${rList || 'N/A'}</div>
                        </div>`;
                    });
                    html += `</div></div>`;
                }

                detailBox.innerHTML = html;
                document.getElementById('discPreviewResults').classList.remove('hidden');

                confirmBtn.disabled = false;
                confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                await showAlert(res.data.message || "Failed to load preview.", "Error");
            }
        } catch (e) {
            document.getElementById('discLoadingSkeleton').classList.add('hidden');
            await showAlert(e.response?.data?.message || "Failed to analyze invoices.", "Error");
        }
    };

    window.submitDiscontinuationReconcile = async function() {
        let studentId = document.getElementById('discStudentId').value;
        let month = document.getElementById('discLastActiveMonth').value;
        let reason = document.getElementById('discReason').value;

        let confirmed = await showConfirm(
            "Confirm Discontinuation & Fee Reconciliation",
            `Are you sure you want to discontinue this student effective after ${month}? Eligible post-discontinuation invoices will be safely cancelled and historical records will remain intact.`
        );
        if (!confirmed) return;

        let confirmBtn = document.getElementById('btnConfirmDiscontinue');
        confirmBtn.disabled = true;
        let originalText = confirmBtn.innerText;
        confirmBtn.innerText = "Processing...";

        try {
            let res = await axios.post(`/ajax/students/${studentId}/discontinue-reconcile`, {
                last_active_month: month,
                reason: reason,
                confirm: true,
            }, getAuthHeaders());

            if (res.data.status === 'success') {
                window.closeModal('discontinuationModal');
                if (typeof showSuccess === 'function') {
                    await showSuccess(res.data.message);
                } else {
                    await showAlert(res.data.message, "Success");
                }
                window.fetchList();
            } else {
                await showAlert(res.data.message || "Reconciliation failed.", "Error");
            }
        } catch (e) {
            let errMsg = e.response?.data?.message || "Discontinuation failed.";
            await showAlert(errMsg, "Error");
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.innerText = originalText;
        }
    };

    window.syncRfidCardSingle = async function(studentId) {
        try {
            let confirmed = await showConfirm("Sync Student RFID Card", "Do you want to sync this student's RFID card from the biometric device?");
            if (!confirmed) return;

            let res = await axios.post(`/ajax/students/${studentId}/sync-card`, {}, getAuthHeaders());
            if (res.data.status === 'success') {
                let cardEl = document.getElementById(`card-num-${studentId}`);
                if (cardEl) {
                    cardEl.innerText = res.data.card_number;
                    cardEl.className = 'text-themeGreen font-black';
                }
                await showAlert(res.data.message, "Sync Success");
            } else {
                await showAlert(res.data.message || "Failed to sync card.", "Error");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "RFID sync failed.";
            await showAlert(errMsg, "Sync Error");
        }
    };

    window.pushToDeviceSingle = async function(studentId) {
        try {
            let confirmed = await showConfirm("Push Student to Device", "Do you want to push this student record to the biometric device?");
            if (!confirmed) return;

            let res = await axios.post(`/ajax/students/${studentId}/push-to-device`, {}, getAuthHeaders());
            if (res.data.status === 'success') {
                await showAlert(res.data.message, "Sync Success");
            } else {
                await showAlert(res.data.message || "Failed to push student to device.", "Error");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Device sync failed.";
            await showAlert(errMsg, "Sync Error");
        }
    };

    window.bulkSyncRfidCards = async function() {
        try {
            let confirmed = await showConfirm(
                "Bulk RFID Card Sync",
                "Do you want to sync/update RFID cards from the biometric machine for all students who have device mappings?"
            );
            if (!confirmed) return;

            await showAlert("Syncing biometric cards in the background... Please wait.", "Info");

            let res = await axios.post('/ajax/students/bulk-sync-cards', {}, getAuthHeaders());
            if (res.data.status === 'success') {
                await showAlert(res.data.message, "Sync Success");
                window.fetchList(1);
            } else {
                await showAlert(res.data.message || "Bulk sync failed.", "Error");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Bulk RFID sync failed.";
            await showAlert(errMsg, "Sync Error");
        }
    };

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        window.fetchList();
    });
</script>
@endpush