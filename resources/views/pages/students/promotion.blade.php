@extends('tyro-dashboard::layouts.admin')

@section('title', 'Student Promotion')

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
                colors: { 
                    themeBlue: '#008ED6',
                    themeGreen: '#009A49',
                    themeDark: '#070E14',
                    themeNavy: '#0F1E2C',
                },
                fontFamily: { 
                    sans: ['Figtree', 'Onest', 'sans-serif'] 
                } 
            } 
        } 
    }
</script>
<style>
    [x-cloak] { display: none !important; }
</style>
<script>
    // ==========================================
    // CSRF and Headers utility
    // ==========================================
    const getAuthHeaders = () => {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        return { 
            headers: { 
                'Accept': 'application/json', 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
            } 
        };
    };

    // ==========================================
    // Alpine Component for Promotion Page
    // ==========================================
    function promotionPage() {
        return {
            activeDropdown: null,
            branches: [],
            classes: [],
            sections: [],
            sessions: [],
            shifts: [],

            from: {
                branch_id: '',
                session_year_id: '',
                class_id: '',
                shift_id: '',
                section_id: ''
            },
            to: {
                branch_id: '',
                session_year_id: '',
                class_id: '',
                shift_id: '',
                section_id: ''
            },

            fromLabels: {
                branch: 'Select Branch',
                session: 'Select Session',
                class: 'Select Class',
                shift: 'Select Shift',
                section: 'Select Section'
            },
            toLabels: {
                branch: 'Select Branch',
                session: 'Select Session',
                class: 'Select Class',
                shift: 'Select Shift',
                section: 'Select Section'
            },

            students: [],
            loading: false,
            fetched: false,
            selectAll: true,
            promoting: false,

            async init() {
                try {
                    const [branchesRes, classesRes, sectionsRes, sessionsRes, shiftsRes] = await Promise.all([
                        axios.get('/ajax/branches', getAuthHeaders()),
                        axios.get('/ajax/classes', getAuthHeaders()),
                        axios.get('/ajax/sections', getAuthHeaders()),
                        axios.get('/ajax/sessions', getAuthHeaders()),
                        axios.get('/ajax/shifts', getAuthHeaders())
                    ]);

                    this.branches = branchesRes.data.branchData || [];
                    this.classes = classesRes.data.classData || [];
                    this.sections = sectionsRes.data.sectionData || [];
                    this.sessions = sessionsRes.data.sessionData || [];
                    this.shifts = shiftsRes.data.shiftData || [];
                } catch (e) {
                    console.error("Failed to load dropdown dynamic options:", e);
                }
            },

            toggleAll() {
                this.students.forEach(std => std.selected = this.selectAll);
            },

            async loadStudents() {
                if (!this.from.branch_id || !this.from.session_year_id || !this.from.class_id || !this.from.section_id) {
                    showAlert("Please select Current Branch, Session, Class, and Section to fetch students.", "Attention");
                    return;
                }

                this.loading = true;
                this.fetched = false;
                this.students = [];

                try {
                    let url = `/ajax/students/promotion-list?branch_id=${this.from.branch_id}&session_year_id=${this.from.session_year_id}&class_id=${this.from.class_id}&section_id=${this.from.section_id}`;
                    if (this.from.shift_id) {
                        url += `&shift_id=${this.from.shift_id}`;
                    }

                    const res = await axios.get(url, getAuthHeaders());
                    const list = res.data.data || [];

                    this.students = list.map(std => ({
                        ...std,
                        selected: true
                    }));
                    this.selectAll = true;
                } catch (error) {
                    console.error("Failed to load student lists:", error);
                    showAlert("Failed to load students. Please try again.", "Error");
                } finally {
                    this.loading = false;
                    this.fetched = true;
                }
            },

            async submitPromotion() {
                if (!this.to.branch_id || !this.to.session_year_id || !this.to.class_id || !this.to.section_id) {
                    showAlert("Please select Next Branch, Session, Class, and Section before confirming!", "Attention");
                    return;
                }

                const selectedList = this.students.filter(std => std.selected);
                if (selectedList.length === 0) {
                    showAlert("Please select at least one student to promote!", "Attention");
                    return;
                }

                this.promoting = true;

                const formData = new FormData();
                formData.append('to_branch', this.to.branch_id);
                formData.append('to_session', this.to.session_year_id);
                formData.append('to_class', this.to.class_id);
                formData.append('to_shift', this.to.shift_id);
                formData.append('to_section', this.to.section_id);

                selectedList.forEach(std => {
                    formData.append('promote_student_ids[]', std.id);

                    // Read input values directly from DOM elements
                    const marksInput = document.querySelector(`input[name="total_marks[${std.id}]"]`);
                    const gradesInput = document.querySelector(`input[name="grades[${std.id}]"]`);
                    const newRollsInput = document.querySelector(`input[name="new_rolls[${std.id}]"]`);

                    formData.append(`total_marks[${std.id}]`, marksInput ? marksInput.value : '');
                    formData.append(`grades[${std.id}]`, gradesInput ? gradesInput.value : '');
                    formData.append(`new_rolls[${std.id}]`, newRollsInput ? newRollsInput.value : '');
                });

                try {
                    const res = await axios.post('/ajax/students/promote', formData, getAuthHeaders());
                    if (res.status === 200) {
                        await showAlert(res.data.message || "Promotion Successful!", "Success");
                        window.location.reload();
                    }
                } catch (error) {
                    console.error(error);
                    showAlert(error.response?.data?.message || "Something went wrong during promotion!", "Error");
                } finally {
                    this.promoting = false;
                }
            }
        };
    }
</script>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeBlue font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<a href="{{ route('students.index') }}" class="text-themeBlue font-bold hover:underline">Student Management</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Promotion</span>
@endsection

@section('content')
<div x-data="promotionPage()">
    
    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            Student Promotion
        </h1>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Bulk upgrade class sections to the next academic session</p>
    </div>

    <!-- Dropdown target values stored in hidden inputs for standard JS/DOM access compatibility -->
    <input type="hidden" :value="from.branch_id" id="from_branch">
    <input type="hidden" :value="from.session_year_id" id="from_session">
    <input type="hidden" :value="from.class_id" id="from_class">
    <input type="hidden" :value="from.shift_id" id="from_shift">
    <input type="hidden" :value="from.section_id" id="from_section">

    <input type="hidden" :value="to.branch_id" id="to_branch">
    <input type="hidden" :value="to.session_year_id" id="to_session">
    <input type="hidden" :value="to.class_id" id="to_class">
    <input type="hidden" :value="to.shift_id" id="to_shift">
    <input type="hidden" :value="to.section_id" id="to_section">

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
        
        <!-- 1. Promote From (Current) -->
        <div class="bg-white dark:bg-themeNavy p-6 md:p-8 rounded-[2rem] border border-gray-100 dark:border-white/[0.06] shadow-sm hover:shadow-md transition-all duration-300 relative">
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-50/50 dark:bg-red-950/10 rounded-tr-[2rem] rounded-bl-full -z-10"></div>
            <h3 class="text-sm font-black text-red-500 dark:text-red-400 uppercase tracking-[0.2em] mb-6 flex items-center">
                <span class="w-6 h-6 rounded-full bg-red-50 dark:bg-red-950/30 text-red-500 flex items-center justify-center mr-2 text-xs font-black">1</span> 
                Promote From (Current)
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Current Branch -->
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Current Branch <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'from_branch' ? null : 'from_branch')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="fromLabels.branch"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'from_branch' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'from_branch'" x-cloak @click.away="if(activeDropdown === 'from_branch') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="from.branch_id = ''; fromLabels.branch = 'Select Branch'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Branch
                            </button>
                            <template x-for="item in branches" :key="item.id">
                                <button @click="from.branch_id = item.id; fromLabels.branch = item.branch_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="from.branch_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.branch_name"></span>
                                    <span x-show="from.branch_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Current Session -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Current Session <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'from_session' ? null : 'from_session')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="fromLabels.session"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'from_session' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'from_session'" x-cloak @click.away="if(activeDropdown === 'from_session') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="from.session_year_id = ''; fromLabels.session = 'Select Session'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Session
                            </button>
                            <template x-for="item in sessions" :key="item.id">
                                <button @click="from.session_year_id = item.id; fromLabels.session = item.session_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="from.session_year_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.session_name"></span>
                                    <span x-show="from.session_year_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Current Class -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Current Class <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'from_class' ? null : 'from_class')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="fromLabels.class"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'from_class' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'from_class'" x-cloak @click.away="if(activeDropdown === 'from_class') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="from.class_id = ''; fromLabels.class = 'Select Class'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Class
                            </button>
                            <template x-for="item in classes" :key="item.id">
                                <button @click="from.class_id = item.id; fromLabels.class = item.class_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="from.class_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.class_name"></span>
                                    <span x-show="from.class_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Current Shift -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Current Shift</label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'from_shift' ? null : 'from_shift')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="fromLabels.shift"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'from_shift' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'from_shift'" x-cloak @click.away="if(activeDropdown === 'from_shift') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="from.shift_id = ''; fromLabels.shift = 'Select Shift'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Shift
                            </button>
                            <template x-for="item in shifts" :key="item.id">
                                <button @click="from.shift_id = item.id; fromLabels.shift = item.shift_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="from.shift_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.shift_name"></span>
                                    <span x-show="from.shift_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Current Section -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Current Section <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'from_section' ? null : 'from_section')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="fromLabels.section"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'from_section' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'from_section'" x-cloak @click.away="if(activeDropdown === 'from_section') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="from.section_id = ''; fromLabels.section = 'Select Section'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Section
                            </button>
                            <template x-for="item in sections" :key="item.id">
                                <button @click="from.section_id = item.id; fromLabels.section = item.section_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="from.section_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.section_name"></span>
                                    <span x-show="from.section_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Promote To (Next) -->
        <div class="bg-white dark:bg-themeNavy p-6 md:p-8 rounded-[2rem] border border-gray-100 dark:border-white/[0.06] shadow-sm hover:shadow-md transition-all duration-300 relative">
            <div class="absolute top-0 right-0 w-32 h-32 bg-green-50/50 dark:bg-green-950/10 rounded-tr-[2rem] rounded-bl-full -z-10"></div>
            <h3 class="text-sm font-black text-themeGreen dark:text-green-400 uppercase tracking-[0.2em] mb-6 flex items-center">
                <span class="w-6 h-6 rounded-full bg-green-50 dark:bg-green-950/30 text-themeGreen flex items-center justify-center mr-2 text-xs font-black">2</span> 
                Promote To (Next)
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Next Branch -->
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Next Branch <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'to_branch' ? null : 'to_branch')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="toLabels.branch"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'to_branch' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'to_branch'" x-cloak @click.away="if(activeDropdown === 'to_branch') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="to.branch_id = ''; toLabels.branch = 'Select Branch'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Branch
                            </button>
                            <template x-for="item in branches" :key="item.id">
                                <button @click="to.branch_id = item.id; toLabels.branch = item.branch_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="to.branch_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.branch_name"></span>
                                    <span x-show="to.branch_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Next Session -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Next Session <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'to_session' ? null : 'to_session')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="toLabels.session"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'to_session' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'to_session'" x-cloak @click.away="if(activeDropdown === 'to_session') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="to.session_year_id = ''; toLabels.session = 'Select Session'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Session
                            </button>
                            <template x-for="item in sessions" :key="item.id">
                                <button @click="to.session_year_id = item.id; toLabels.session = item.session_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="to.session_year_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.session_name"></span>
                                    <span x-show="to.session_year_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Next Class -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Next Class <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'to_class' ? null : 'to_class')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="toLabels.class"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'to_class' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'to_class'" x-cloak @click.away="if(activeDropdown === 'to_class') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="to.class_id = ''; toLabels.class = 'Select Class'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Class
                            </button>
                            <template x-for="item in classes" :key="item.id">
                                <button @click="to.class_id = item.id; toLabels.class = item.class_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="to.class_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.class_name"></span>
                                    <span x-show="to.class_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Next Shift -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Next Shift</label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'to_shift' ? null : 'to_shift')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="toLabels.shift"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'to_shift' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'to_shift'" x-cloak @click.away="if(activeDropdown === 'to_shift') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="to.shift_id = ''; toLabels.shift = 'Select Shift'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Shift
                            </button>
                            <template x-for="item in shifts" :key="item.id">
                                <button @click="to.shift_id = item.id; toLabels.shift = item.shift_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="to.shift_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.shift_name"></span>
                                    <span x-show="to.shift_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Next Section -->
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Next Section <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <button @click="activeDropdown = (activeDropdown === 'to_section' ? null : 'to_section')" type="button" class="w-full flex items-center justify-between px-4 h-11 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left text-gray-700 dark:text-gray-300">
                            <span x-text="toLabels.section"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="activeDropdown === 'to_section' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="activeDropdown === 'to_section'" x-cloak @click.away="if(activeDropdown === 'to_section') activeDropdown = null" class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button @click="to.section_id = ''; toLabels.section = 'Select Section'; activeDropdown = null" type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                                Select Section
                            </button>
                            <template x-for="item in sections" :key="item.id">
                                <button @click="to.section_id = item.id; toLabels.section = item.section_name; activeDropdown = null" type="button" class="w-full flex items-center justify-between px-4 py-2.5 text-sm text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="to.section_id == item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="item.section_name"></span>
                                    <span x-show="to.section_id == item.id">
                                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fetch Button -->
    <div class="text-center mb-10">
        <button @click="loadStudents()" type="button" :disabled="loading" class="inline-flex items-center justify-center px-10 py-4 h-12 bg-gradient-to-r from-themeBlue to-themeGreen hover:from-themeBlue/90 hover:to-themeGreen/90 text-white font-black uppercase tracking-[0.2em] text-xs rounded-xl shadow-md shadow-themeBlue/10 transition-all hover:scale-105 active:scale-95 disabled:opacity-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            <span x-text="loading ? 'Fetching...' : 'Fetch Students For Promotion'"></span>
        </button>
    </div>

    <!-- Loading Spinner -->
    <div x-show="loading" x-cloak class="py-16 text-center">
        <div class="inline-block w-8 h-8 border-4 border-themeBlue border-t-transparent rounded-full animate-spin"></div>
        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-4">Loading Students...</p>
    </div>

    <!-- Empty Placeholder -->
    <div x-show="students.length === 0 && !loading && fetched" x-cloak class="py-16 text-center bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl" x-transition>
        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        <p class="text-sm font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">No Students Found for selection</p>
    </div>

    <!-- Student Table Section -->
    <div x-show="students.length > 0 && !loading" x-cloak class="bg-white dark:bg-themeNavy rounded-3xl shadow-sm border border-gray-100 dark:border-white/[0.06] overflow-hidden mb-8 transition-all" x-transition>
        <div class="p-6 bg-gray-50/50 dark:bg-themeDark/30 border-b border-gray-100 dark:border-white/[0.06] flex justify-between items-center">
            <h3 class="text-sm font-black text-themeBlue uppercase tracking-widest">Student List</h3>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="selectAll" x-model="selectAll" @change="toggleAll()" class="w-5 h-5 text-themeBlue rounded border-gray-300 dark:border-gray-700 focus:ring-themeBlue cursor-pointer">
                <label for="selectAll" class="text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer select-none">Select All For Promotion</label>
            </div>
        </div>
        
        <form id="promotionForm" @submit.prevent="submitPromotion()">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] bg-gray-50/30 dark:bg-themeDark/35 border-b border-gray-100 dark:border-white/[0.06] text-center w-16">Promote</th>
                            <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] bg-gray-50/30 dark:bg-themeDark/35 border-b border-gray-100 dark:border-white/[0.06] text-center w-20">Current Roll</th>
                            <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] bg-gray-50/30 dark:bg-themeDark/35 border-b border-gray-100 dark:border-white/[0.06]">Student Details</th>
                            <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] bg-gray-50/30 dark:bg-themeDark/35 border-b border-gray-100 dark:border-white/[0.06] text-center w-24">Marks</th> 
                            <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] bg-gray-50/30 dark:bg-themeDark/35 border-b border-gray-100 dark:border-white/[0.06] text-center w-24">Grade</th> 
                            <th class="py-4 px-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] bg-gray-50/30 dark:bg-themeDark/35 border-b border-gray-100 dark:border-white/[0.06] text-center w-32">New Roll No.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/40">
                        <template x-for="std in students" :key="std.id">
                            <tr class="hover:bg-gray-50/30 dark:hover:bg-themeDark/20 transition-colors border-b border-gray-50 dark:border-white/[0.06]">
                                <td class="py-4 px-6 text-center">
                                    <input type="checkbox" :checked="std.selected" @change="std.selected = $el.checked" class="w-5 h-5 text-themeBlue rounded border-gray-300 dark:border-gray-700 focus:ring-themeBlue cursor-pointer">
                                </td>
                                <td class="py-4 px-6 text-center font-mono font-black text-gray-500 dark:text-gray-400" x-text="std.roll_number || std.roll || '-'"></td>
                                <td class="py-4 px-6">
                                    <div class="font-black text-sm uppercase text-gray-800 dark:text-white" x-text="std.student_name || std.name || 'Unknown'"></div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase mt-0.5" x-text="'ID: ' + (std.student_identity || std.identity || '')"></div>
                                </td>
                                <td class="py-4 px-2 text-center">
                                    <input type="text" :name="'total_marks[' + std.id + ']'" class="w-full text-center border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy py-1.5 font-bold focus:border-themeBlue outline-none text-xs focus:ring-4 focus:ring-themeBlue/10 transition-all text-gray-900 dark:text-white" placeholder="Marks">
                                </td>
                                <td class="py-4 px-2 text-center">
                                    <input type="text" :name="'grades[' + std.id + ']'" class="w-full text-center border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy py-1.5 font-bold focus:border-themeBlue outline-none text-xs focus:ring-4 focus:ring-themeBlue/10 transition-all text-gray-900 dark:text-white" placeholder="A+">
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <input type="text" :name="'new_rolls[' + std.id + ']'" class="w-20 mx-auto text-center border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy py-1.5 font-black font-mono focus:border-themeBlue outline-none focus:ring-4 focus:ring-themeBlue/10 transition-all text-gray-900 dark:text-white" placeholder="Roll">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <div class="p-6 bg-gray-50/50 dark:bg-themeDark/30 border-t border-gray-100 dark:border-white/[0.06] flex justify-end">
                <button type="submit" :disabled="promoting" class="inline-flex items-center justify-center px-12 py-4 h-12 bg-gradient-to-r from-themeBlue to-themeGreen hover:from-themeBlue/90 hover:to-themeGreen/90 text-white font-black uppercase tracking-[0.2em] text-xs rounded-xl shadow-md shadow-themeBlue/10 transition-all hover:scale-105 active:scale-95 disabled:opacity-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    <span x-text="promoting ? 'PROMOTING...' : 'Confirm Promotion'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection