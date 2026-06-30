@extends('tyro-dashboard::layouts.admin')

@section('title', 'Class Routine Management')

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
    
    /* Dynamic styling variables */
    .day-header { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; text-align: center; }

    /* ==========================================
       🔥 PREMIUM PRINT CSS (Landscape fit)
       ========================================== */
    @media print {
        @page { size: A4 landscape; margin: 8mm; }
        html, body { margin: 0 !important; padding: 0 !important; background: #ffffff !important; color: #000000 !important; }
        body * { visibility: hidden; }
        
        #printableRoutine { 
            visibility: visible; 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 100% !important; 
            background: #ffffff !important; 
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            z-index: 99999;
            overflow: visible;
        }

        #printableRoutine * { visibility: visible; color: #000000 !important; }
        .no-print, .delete-btn, button, svg { display: none !important; }
    }
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

    function formatTime(timeStr) {
        if (!timeStr) return '';
        let [hours, minutes] = timeStr.split(':');
        let ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${hours}:${minutes} ${ampm}`;
    }

    // Custom Time Picker component data definition
    function timePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue || '09:00', // HH:MM (24h)
            hour: 9,
            minute: 0,
            period: 'AM',
            
            init() {
                this.parseValue(this.value);
                this.$watch('value', val => {
                    if (val) this.parseValue(val);
                });
            },
            
            parseValue(val) {
                if (!val) return;
                const parts = val.split(':');
                let h = parseInt(parts[0]);
                this.minute = parseInt(parts[1] || '0');
                
                if (h >= 12) {
                    this.period = 'PM';
                    this.hour = h === 12 ? 12 : h - 12;
                } else {
                    this.period = 'AM';
                    this.hour = h === 0 ? 12 : h;
                }
            },
            
            selectHour(h) {
                this.hour = h;
                this.updateValue();
            },
            
            selectMinute(m) {
                this.minute = m;
                this.updateValue();
            },
            
            selectPeriod(p) {
                this.period = p;
                this.updateValue();
            },
            
            updateValue() {
                let h24 = this.hour;
                if (this.period === 'PM') {
                    h24 = this.hour === 12 ? 12 : this.hour + 12;
                } else {
                    h24 = this.hour === 12 ? 0 : this.hour;
                }
                const formattedHour = String(h24).padStart(2, '0');
                const formattedMinute = String(this.minute).padStart(2, '0');
                this.value = `${formattedHour}:${formattedMinute}`;
                this.$dispatch('time-selected', this.value);
            },
            
            formatDisplay(val) {
                if (!val) return 'Select Time';
                const parts = val.split(':');
                let h = parseInt(parts[0]);
                let m = parseInt(parts[1] || '0');
                let p = h >= 12 ? 'PM' : 'AM';
                let displayHour = h % 12;
                if (displayHour === 0) displayHour = 12;
                return `${String(displayHour).padStart(2, '0')}:${String(m).padStart(2, '0')} ${p}`;
            }
        }
    }

    // ==========================================
    // Alpine Controller for Class Routine
    // ==========================================
    function routinePage() {
        return {
            // Dropdowns resources from blade
            branches: @json($branches),
            sessions: @json($sessions),
            classes: @json($classes),
            sections: @json($sections),
            subjects: @json($subjects),
            teachers: [],

            // Filter states & inputs x-model
            form: {
                branch_id: '',
                session_year_id: '{{ $sessions->first()->id ?? "" }}',
                class_id: '',
                section_id: '',
                subject_id: '',
                teacher_id: '',
                day: 'Saturday',
                start_time: '',
                end_time: ''
            },

            // Active Dropdown state
            activeDropdown: null,

            // Active displays texts
            branchText: 'Select Branch',
            sessionText: '{{ $sessions->first()->session_name ?? "Select Session" }}',
            classText: 'Select...',
            sectionText: 'Any',
            subjectText: 'Select Subject...',
            teacherText: 'Assign Teacher...',
            dayText: 'Saturday',

            // Routine content state
            routineData: {},
            loading: false,
            saving: false,
            editMode: false,
            editId: null,

            init() {
                // Populate teachers list
                const list = @json($teachers);
                this.teachers = list.map(t => ({
                    id: t.id,
                    name: t.user ? t.user.name : 'Unknown'
                }));
                this.loadRoutine();
            },

            toggleDropdown(name) {
                this.activeDropdown = this.activeDropdown === name ? null : name;
            },

            selectValue(field, val, text) {
                this.form[field] = val;
                
                // Map DB keys to Alpine label display properties
                const mapping = {
                    branch_id: 'branchText',
                    session_year_id: 'sessionText',
                    class_id: 'classText',
                    section_id: 'sectionText',
                    subject_id: 'subjectText',
                    teacher_id: 'teacherText',
                    day: 'dayText'
                };
                
                const targetTextVar = mapping[field];
                if (targetTextVar) {
                    this[targetTextVar] = text;
                }
                
                this.activeDropdown = null;

                // Reload routine automatically when query filters change
                if (['branch_id', 'session_year_id', 'class_id', 'section_id'].includes(field)) {
                    this.loadRoutine();
                }
            },

            fillUpdateForm(item) {
                this.editMode = true;
                this.editId = item.id;
                
                // Set form fields
                this.form.session_year_id = item.session_year_id;
                this.form.branch_id = item.branch_id || '';
                this.form.class_id = item.class_id;
                this.form.section_id = item.section_id || '';
                this.form.subject_id = item.subject_id;
                this.form.teacher_id = item.teacher_id;
                this.form.day = item.day;
                this.form.start_time = item.start_time ? item.start_time.substring(0, 5) : '';
                this.form.end_time = item.end_time ? item.end_time.substring(0, 5) : '';

                // Populate labels
                const branchObj = this.branches.find(b => b.id == item.branch_id);
                this.branchText = branchObj ? branchObj.branch_name : 'Select Branch';

                const sessionObj = this.sessions.find(s => s.id == item.session_year_id);
                this.sessionText = sessionObj ? sessionObj.session_name : 'Select Session';

                const classObj = this.classes.find(c => c.id == item.class_id);
                this.classText = classObj ? classObj.class_name : 'Select...';

                const sectionObj = this.sections.find(s => s.id == item.section_id);
                this.sectionText = sectionObj ? sectionObj.section_name : 'Any';

                const subjectObj = this.subjects.find(s => s.id == item.subject_id);
                this.subjectText = subjectObj ? (subjectObj.subject_name || subjectObj.name) : 'Select Subject...';

                const teacherObj = this.teachers.find(t => t.id == item.teacher_id);
                this.teacherText = teacherObj ? teacherObj.name : 'Assign Teacher...';

                this.dayText = item.day || 'Saturday';

                // Scroll to form panel
                document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
                
                // Reload routine data for the target class/section
                this.loadRoutine();
            },
            
            cancelEdit() {
                this.editMode = false;
                this.editId = null;
                
                // Clear active inputs (only the slots specific details, keeping queries)
                this.form.subject_id = '';
                this.form.teacher_id = '';
                this.form.start_time = '';
                this.form.end_time = '';

                // Reset labels
                this.subjectText = 'Select Subject...';
                this.teacherText = 'Assign Teacher...';
            },

            get groupedClasses() {
                if (!Array.isArray(this.routineData)) return [];
                
                const groups = {};
                this.routineData.forEach(slot => {
                    const classId = slot.class_id;
                    const sectionId = slot.section_id || 'any';
                    const key = `${classId}-${sectionId}`;
                    
                    if (!groups[key]) {
                        groups[key] = {
                            class_id: classId,
                            class_name: slot.class ? slot.class.class_name : 'N/A',
                            section_id: slot.section_id || '',
                            section_name: slot.section ? slot.section.section_name : 'Any',
                            branch_name: slot.branch ? slot.branch.branch_name : 'Main Branch',
                            slotsCount: 0,
                            days: new Set()
                        };
                    }
                    
                    groups[key].slotsCount++;
                    if (slot.day) {
                        groups[key].days.add(slot.day);
                    }
                });
                
                return Object.values(groups).map(g => ({
                    ...g,
                    daysList: Array.from(g.days).join(', ')
                }));
            },

            selectClassSection(classId, className, sectionId, sectionName) {
                this.form.class_id = classId;
                this.classText = className;
                this.form.section_id = sectionId;
                this.sectionText = sectionName || 'Any';
                this.loadRoutine();
            },

            resetClassSelection() {
                this.form.class_id = '';
                this.classText = 'Select...';
                this.form.section_id = '';
                this.sectionText = 'Any';
                this.loadRoutine();
            },

            get maxSlots() {
                let slots = 5; // Default slots height
                const days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                days.forEach(day => {
                    if (this.routineData[day] && this.routineData[day].length > slots) {
                        slots = this.routineData[day].length;
                    }
                });
                return slots;
            },

            get printClassInfo() {
                return `Branch: ${this.branchText} | Class: ${this.classText} | Section: ${this.sectionText}`;
            },

            async loadRoutine() {
                this.loading = true;
                try {
                    let params = {
                        branch_id: this.form.branch_id,
                        session_year_id: this.form.session_year_id,
                        class_id: this.form.class_id,
                        section_id: this.form.section_id
                    };
                    let res = await axios.get('/routine/get', { params });
                    this.routineData = res.data.routine || (this.form.class_id ? {} : []);
                } catch (err) {
                    console.error("Failed to load routine data:", err);
                    this.routineData = this.form.class_id ? {} : [];
                } finally {
                    this.loading = false;
                }
            },

            async submitForm() {
                if (!this.form.class_id) {
                    showAlert("Please select a Class before saving the routine.", "Attention");
                    return;
                }
                if (!this.form.subject_id || !this.form.teacher_id || !this.form.start_time || !this.form.end_time) {
                    showAlert("Please fill in all required fields (Subject, Teacher, Day, Times).", "Attention");
                    return;
                }

                this.saving = true;
                try {
                    let res;
                    if (this.editMode) {
                        res = await axios.put(`/routine/update/${this.editId}`, this.form, getAuthHeaders());
                    } else {
                        res = await axios.post('/routine/store', this.form, getAuthHeaders());
                    }

                    if (res.data.status === 'error') {
                        showAlert(res.data.message, "Warning");
                    } else {
                        await showAlert(res.data.message, "Success");
                        if (this.editMode) {
                            this.cancelEdit();
                        } else {
                            // Reset input values
                            this.form.start_time = '';
                            this.form.end_time = '';
                        }
                        this.loadRoutine();
                    }
                } catch (err) {
                    showAlert("Failed to save routine slot.", "Error");
                } finally {
                    this.saving = false;
                }
            },

            async deleteSlot(id) {
                const confirmed = await showDanger("Delete Slot", "Are you sure you want to delete this routine slot? This action cannot be undone.");
                if (confirmed) {
                    try {
                        await axios.delete(`/routine/destroy/${id}`, getAuthHeaders());
                        await showAlert("Routine slot deleted successfully!", "Success");
                        this.loadRoutine();
                    } catch (err) {
                        showAlert("Failed to delete slot.", "Error");
                    }
                }
            }
        };
    }
</script>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeBlue font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Class Routine</span>
@endsection

@section('content')
<div x-data="routinePage()" x-init="init()">
    
    <!-- Title Section -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Class Routine
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Smart Class Routine Management</p>
        </div>
        <div>
            <button @click="window.print()" class="btn-sm bg-gradient-to-r from-themeBlue to-themeGreen text-white border-none rounded-xl hover:-translate-y-0.5 hover:shadow-lg transition-all flex items-center gap-1.5 !h-9 !px-4 uppercase text-xs font-black tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l2.9-2.9m0 0l2.9 2.9m-2.9-2.9v6.3m4.72-9.35V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                Print Routine
            </button>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Add New Slot Column (Sticky panel) -->
        <div class="lg:col-span-3 no-print">
            <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm sticky top-6">
                <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6" x-text="editMode ? 'Edit Slot' : 'Add New Slot'">Add New Slot</h3>
                
                <form @submit.prevent="submitForm()">
                    <div class="flex flex-col gap-5">
                        
                        <!-- Branch & Session Selector -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Branch -->
                            <div class="relative" @click.away="activeDropdown === 'branch' && (activeDropdown = null)">
                                <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Branch</label>
                                <button type="button" @click="toggleDropdown('branch')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                    <span class="truncate" x-text="branchText"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                    <button type="button" @click="selectValue('branch_id', '', 'Select Branch')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>Select Branch</span>
                                        <template x-if="form.branch_id === ''">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                    <template x-for="item in branches" :key="item.id">
                                        <button type="button" @click="selectValue('branch_id', item.id, item.branch_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span x-text="item.branch_name"></span>
                                            <template x-if="form.branch_id === item.id">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Session -->
                            <div class="relative" @click.away="activeDropdown === 'session' && (activeDropdown = null)">
                                <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Session *</label>
                                <button type="button" @click="toggleDropdown('session')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                    <span class="truncate" x-text="sessionText"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                    <template x-for="item in sessions" :key="item.id">
                                        <button type="button" @click="selectValue('session_year_id', item.id, item.session_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span x-text="item.session_name"></span>
                                            <template x-if="form.session_year_id === item.id">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Class & Section Selector -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Class -->
                            <div class="relative" @click.away="activeDropdown === 'class' && (activeDropdown = null)">
                                <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class *</label>
                                <button type="button" @click="toggleDropdown('class')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                    <span class="truncate" x-text="classText"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                    <template x-for="item in classes" :key="item.id">
                                        <button type="button" @click="selectValue('class_id', item.id, item.class_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span x-text="item.class_name"></span>
                                            <template x-if="form.class_id === item.id">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Section -->
                            <div class="relative" @click.away="activeDropdown === 'section' && (activeDropdown = null)">
                                <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Section</label>
                                <button type="button" @click="toggleDropdown('section')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                    <span class="truncate" x-text="sectionText"></span>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="activeDropdown === 'section'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                    <button type="button" @click="selectValue('section_id', '', 'Any')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>Any</span>
                                        <template x-if="form.section_id === ''">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                    <template x-for="item in sections" :key="item.id">
                                        <button type="button" @click="selectValue('section_id', item.id, item.section_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span x-text="item.section_name"></span>
                                            <template x-if="form.section_id === item.id">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Subject Selector -->
                        <div class="relative" @click.away="activeDropdown === 'subject' && (activeDropdown = null)">
                            <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Subject *</label>
                            <button type="button" @click="toggleDropdown('subject')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                <span class="truncate" x-text="subjectText"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="activeDropdown === 'subject'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                <template x-for="item in subjects" :key="item.id">
                                    <button type="button" @click="selectValue('subject_id', item.id, item.subject_name || item.name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.subject_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span x-text="item.subject_name || item.name"></span>
                                        <template x-if="form.subject_id === item.id">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Teacher Selector -->
                        <div class="relative" @click.away="activeDropdown === 'teacher' && (activeDropdown = null)">
                            <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Teacher *</label>
                            <button type="button" @click="toggleDropdown('teacher')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                <span class="truncate" x-text="teacherText"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="activeDropdown === 'teacher'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                <template x-for="item in teachers" :key="item.id">
                                    <button type="button" @click="selectValue('teacher_id', item.id, item.name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.teacher_id === item.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span x-text="item.name"></span>
                                        <template x-if="form.teacher_id === item.id">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Day Selector -->
                        <div class="relative" @click.away="activeDropdown === 'day' && (activeDropdown = null)">
                            <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Day *</label>
                            <button type="button" @click="toggleDropdown('day')" class="w-full h-10 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                                <span class="truncate" x-text="dayText"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="activeDropdown === 'day'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                                <template x-for="day in ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']" :key="day">
                                    <button type="button" @click="selectValue('day', day, day)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.day === day ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span x-text="day"></span>
                                        <template x-if="form.day === day">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Start & End Time (Custom Alpine.js Pickers) -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Start Time -->
                            <div class="relative" x-data="timePicker(form.start_time)" @time-selected.window="if($event.detail && $event.target.id === 'startTimePicker') form.start_time = $event.detail" @click.away="show = false" id="startTimePicker">
                                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Start Time *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark text-gray-900 dark:text-white focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue outline-none transition-all font-semibold text-xs flex items-center justify-between">
                                    <span x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                
                                <!-- Time Picker Dropdown -->
                                <div x-show="show" x-cloak class="absolute left-0 z-50 mt-1.5 w-56 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3 flex gap-3 justify-center" x-transition>
                                    <!-- Hours Column -->
                                    <div class="flex flex-col items-center">
                                        <span class="text-[9px] font-black text-gray-450 uppercase mb-1">Hr</span>
                                        <div class="flex flex-col gap-1 max-h-36 overflow-y-auto w-12 scrollbar-none">
                                            <template x-for="h in Array.from({length: 12}, (_, i) => i + 1)" :key="h">
                                                <button type="button" @click="selectHour(h)" 
                                                        class="h-7 text-xs font-bold rounded-lg transition-all"
                                                        :class="hour === h ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                                    <span x-text="String(h).padStart(2, '0')"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <!-- Divider -->
                                    <div class="self-center font-black text-gray-300 dark:text-gray-700">:</div>
                                    <!-- Minutes Column -->
                                    <div class="flex flex-col items-center">
                                        <span class="text-[9px] font-black text-gray-450 uppercase mb-1">Min</span>
                                        <div class="flex flex-col gap-1 max-h-36 overflow-y-auto w-12 scrollbar-none">
                                            <template x-for="m in [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55]" :key="m">
                                                <button type="button" @click="selectMinute(m)" 
                                                        class="h-7 text-xs font-bold rounded-lg transition-all"
                                                        :class="minute === m ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                                    <span x-text="String(m).padStart(2, '0')"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <!-- Divider -->
                                    <div class="self-center font-black text-gray-300 dark:text-gray-700">|</div>
                                    <!-- AM/PM Column -->
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="text-[9px] font-black text-gray-450 uppercase mb-1">Period</span>
                                        <button type="button" @click="selectPeriod('AM')" 
                                                class="w-12 h-7 text-xs font-bold rounded-lg transition-all"
                                                :class="period === 'AM' ? 'bg-themeGreen text-white font-black' : 'text-gray-750 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                            AM
                                        </button>
                                        <button type="button" @click="selectPeriod('PM')" 
                                                class="w-12 h-7 text-xs font-bold rounded-lg transition-all"
                                                :class="period === 'PM' ? 'bg-themeGreen text-white font-black' : 'text-gray-750 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                            PM
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- End Time -->
                            <div class="relative" x-data="timePicker(form.end_time)" @time-selected.window="if($event.detail && $event.target.id === 'endTimePicker') form.end_time = $event.detail" @click.away="show = false" id="endTimePicker">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">End Time *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark text-gray-900 dark:text-white focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue outline-none transition-all font-semibold text-xs flex items-center justify-between">
                                    <span x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                
                                <!-- Time Picker Dropdown -->
                                <div x-show="show" x-cloak class="absolute right-0 z-50 mt-1.5 w-56 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3 flex gap-3 justify-center" x-transition>
                                    <!-- Hours Column -->
                                    <div class="flex flex-col items-center">
                                        <span class="text-[9px] font-black text-gray-450 uppercase mb-1">Hr</span>
                                        <div class="flex flex-col gap-1 max-h-36 overflow-y-auto w-12 scrollbar-none">
                                            <template x-for="h in Array.from({length: 12}, (_, i) => i + 1)" :key="h">
                                                <button type="button" @click="selectHour(h)" 
                                                        class="h-7 text-xs font-bold rounded-lg transition-all"
                                                        :class="hour === h ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                                    <span x-text="String(h).padStart(2, '0')"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <!-- Divider -->
                                    <div class="self-center font-black text-gray-300 dark:text-gray-700">:</div>
                                    <!-- Minutes Column -->
                                    <div class="flex flex-col items-center">
                                        <span class="text-[9px] font-black text-gray-450 uppercase mb-1">Min</span>
                                        <div class="flex flex-col gap-1 max-h-36 overflow-y-auto w-12 scrollbar-none">
                                            <template x-for="m in [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55]" :key="m">
                                                <button type="button" @click="selectMinute(m)" 
                                                        class="h-7 text-xs font-bold rounded-lg transition-all"
                                                        :class="minute === m ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                                    <span x-text="String(m).padStart(2, '0')"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                    <!-- Divider -->
                                    <div class="self-center font-black text-gray-300 dark:text-gray-700">|</div>
                                    <!-- AM/PM Column -->
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="text-[9px] font-black text-gray-450 uppercase mb-1">Period</span>
                                        <button type="button" @click="selectPeriod('AM')" 
                                                class="w-12 h-7 text-xs font-bold rounded-lg transition-all"
                                                :class="period === 'AM' ? 'bg-themeGreen text-white font-black' : 'text-gray-750 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                            AM
                                        </button>
                                        <button type="button" @click="selectPeriod('PM')" 
                                                class="w-12 h-7 text-xs font-bold rounded-lg transition-all"
                                                :class="period === 'PM' ? 'bg-themeGreen text-white font-black' : 'text-gray-750 dark:text-gray-250 hover:bg-gray-50 dark:hover:bg-themeDark/45'">
                                            PM
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit & Cancel Buttons -->
                        <div class="flex flex-col gap-2">
                            <button type="submit" :disabled="saving" class="mt-4 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-3 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs w-full active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="saving ? 'Saving...' : (editMode ? 'Update Slot' : '+ Add to Routine')"></span>
                            </button>
                            <button x-show="editMode" @click="cancelEdit()" type="button" class="bg-gray-100 dark:bg-themeNavy border border-gray-200 dark:border-white/[0.06] text-gray-600 dark:text-gray-300 font-black py-2.5 rounded-xl transition-all uppercase tracking-widest text-[10px] w-full hover:bg-gray-200 dark:hover:bg-themeDark text-center">
                                Cancel Edit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Routine Board Display -->
        <div class="lg:col-span-9">
            
            <!-- Loader State Indicator -->
            <div x-show="loading" class="flex items-center justify-end mb-4 no-print" x-cloak>
                <span class="text-xs font-bold text-gray-500 uppercase animate-pulse">Syncing routine data...</span>
            </div>

            <!-- No Class Selected: Render Grouped Class Routines Directory Table -->
            <div x-show="!form.class_id" x-cloak class="no-print">
                <div class="mb-4">
                    <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest">Class Routine Directory</h3>
                </div>
                
                <div class="table-container bg-transparent !shadow-none !mt-2 !mb-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-none table">
                            <thead>
                                <tr class="!bg-transparent">
                                    <th class="w-16 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center">SL</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Class / Section</th>
                                    <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Scheduled Days</th>
                                    <th class="w-32 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center">Total Slots</th>
                                    <th class="w-32 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                                <!-- Skeleton loader rows in flat list -->
                                <tr x-show="loading" class="animate-pulse">
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                    <td class="py-0 px-0"><div class="h-4 w-40 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                    <td class="py-0 px-0 text-center"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                    <td class="py-0 px-0 text-right"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                                </tr>
                                
                                <template x-for="(item, index) in groupedClasses" :key="item.class_id + '-' + item.section_id">
                                    <tr x-show="!loading" class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                        <td class="py-0 px-0 text-center font-mono font-black text-gray-555 dark:text-gray-400" x-text="index + 1"></td>
                                        <td class="py-0 px-0">
                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="item.class_name"></div>
                                            <div class="text-[10px] font-bold text-gray-400 dark:text-gray-550 uppercase tracking-widest mt-0.5" x-text="'Section: ' + item.section_name + ' | ' + item.branch_name"></div>
                                        </td>
                                        <td class="py-0 px-0">
                                            <div class="text-xs font-semibold text-gray-650 dark:text-gray-300" x-text="item.daysList"></div>
                                        </td>
                                        <td class="py-0 px-0 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-themeBlue/10 text-themeBlue" x-text="item.slotsCount + ' Slots'"></span>
                                        </td>
                                        <td class="py-0 px-0 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" @click="selectClassSection(item.class_id, item.class_name, item.section_id, item.section_name)" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="View Weekly Board">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="groupedClasses.length === 0 && !loading" x-cloak>
                                    <td colspan="5" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No class routines found. Add slots to generate.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Routine Printable Container -->
            <div id="printableRoutine" x-show="form.class_id" x-cloak class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 md:p-8 shadow-sm">
                
                <!-- Print Header Info -->
                <div class="text-center mb-6 border-b border-gray-100 dark:border-white/[0.06] pb-4 relative">
                    <button type="button" @click="resetClassSelection()" class="absolute left-0 top-0 text-[10px] font-black tracking-widest text-themeBlue hover:text-themeBlue/80 hover:scale-105 active:scale-95 transition-all uppercase flex items-center gap-1.5 no-print">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back to List
                    </button>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white font-secondary tracking-tight">CLASS ROUTINE</h2>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-widest" x-text="printClassInfo"></p>
                </div>

                <!-- Matrix Board -->
                <div class="flex flex-col gap-3">
                    
                    <!-- Days Headers Row -->
                    <div class="grid grid-cols-[50px_repeat(6,_1fr)] gap-3 w-full items-center">
                        <div class="h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-themeDark border border-gray-200 dark:border-white/[0.06] text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                            SL
                        </div>
                        <template x-for="day in ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']" :key="day">
                            <div class="h-10 flex items-center justify-center rounded-xl text-[10px] font-black uppercase tracking-widest text-center"
                                 :class="{
                                     'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20': day === 'Saturday',
                                     'bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-100 dark:border-teal-500/20': day === 'Sunday',
                                     'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-500/20': day === 'Monday',
                                     'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-100 dark:border-orange-500/20': day === 'Tuesday',
                                     'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20': day === 'Wednesday',
                                     'bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-500/20': day === 'Thursday'
                                 }"
                                 x-text="day">
                            </div>
                        </template>
                    </div>

                    <!-- Matrix Periods Rows -->
                    <template x-for="i in Array.from({length: maxSlots}, (_, idx) => idx)" :key="i">
                        <div class="grid grid-cols-[50px_repeat(6,_1fr)] gap-3 w-full items-stretch">
                            
                            <!-- SL number -->
                            <div class="flex items-center justify-center rounded-xl bg-gray-50/50 dark:bg-themeDark border border-gray-150/40 dark:border-white/[0.06] text-sm font-black text-gray-500 dark:text-gray-400">
                                <span x-text="i + 1"></span>
                            </div>
                            
                            <!-- Days Slots loop -->
                            <template x-for="day in ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']" :key="day">
                                <div class="min-h-[72px] flex">
                                    
                                    <!-- Filled class slot -->
                                    <template x-if="routineData[day] && routineData[day][i]">
                                        <div @click="fillUpdateForm(routineData[day][i])" class="group relative w-full p-3 rounded-2xl border border-gray-100 dark:border-white/[0.06] bg-white dark:bg-themeNavy flex flex-col justify-between hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 select-none cursor-pointer">
                                            
                                            <!-- Edit button -->
                                            <button type="button" @click.stop="fillUpdateForm(routineData[day][i])" class="absolute -top-1.5 -left-1.5 w-5 h-5 rounded-full bg-themeBlue hover:bg-themeBlue/90 text-white flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity border border-white dark:border-themeNavy z-10 cursor-pointer no-print" title="Edit Slot">
                                                <svg class="w-2 h-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            
                                            <!-- Delete cross button -->
                                            <button type="button" @click.stop="deleteSlot(routineData[day][i].id)" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity border border-white dark:border-themeNavy z-10 cursor-pointer no-print">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                            
                                            <!-- Start & End Time -->
                                            <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1 block print-time" x-text="formatTime(routineData[day][i].start_time) + ' - ' + formatTime(routineData[day][i].end_time)"></span>
                                            
                                            <!-- Subject Name -->
                                            <span class="text-xs font-black text-themeBlue leading-tight uppercase mb-1 block line-clamp-2 print-subject" x-text="routineData[day][i].subject ? (routineData[day][i].subject.subject_name || routineData[day][i].subject.name) : 'N/A'"></span>
                                            
                                            <!-- Teacher Name -->
                                            <span class="text-[10px] font-bold text-themeGreen capitalize block truncate print-teacher" x-text="routineData[day][i].teacher && routineData[day][i].teacher.user ? routineData[day][i].teacher.user.name : 'Unknown'"></span>
                                        </div>
                                    </template>
                                    
                                    <!-- Empty slot placeholder -->
                                    <template x-if="!routineData[day] || !routineData[day][i]">
                                        <div class="w-full rounded-2xl border border-dashed border-gray-100 dark:border-gray-800 bg-gray-50/10 dark:bg-themeDark/10 flex items-center justify-center">
                                            <span class="text-[9px] font-black text-gray-300 dark:text-gray-700 uppercase tracking-widest no-print">Empty</span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    
                </div>
            </div>
            
        </div>
    </div>

</div>
@endsection