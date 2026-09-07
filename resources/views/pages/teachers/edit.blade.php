@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Staff Information')

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
                    themeBlue: '#008ED6', 
                    themeGreen: '#009A49',
                    themeDark: '#070E14',
                    themeNavy: '#0F1E2C',
                    gray: {
                        55: '#f8fafc',
                        450: '#94a3b8',
                        455: '#8a99ad',
                        550: '#64748b',
                        555: '#64748b',
                        650: '#475569',
                        850: '#1e293b'
                    }
                },
                fontFamily: { 
                    sans: ['Figtree', 'sans-serif'], 
                    secondary: ['Onest', 'sans-serif'], 
                    mono: ['Fira Code', 'monospace'] 
                } 
            } 
        } 
    }
</script>
@endpush

@section('back_button')
<a href="{{ route('teachers.index') }}" class="inline-flex items-center gap-2 text-sm font-extrabold text-themeBlue bg-themeBlue/5 dark:bg-themeBlue/10 hover:bg-themeBlue/10 dark:hover:bg-themeBlue/15 px-3.5 py-1.5 rounded-xl border border-themeBlue/25 transition-all hover:scale-105 active:scale-95 shadow-sm shadow-themeBlue/5">
    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    Back to Staff List
</a>
@endsection

@section('content')
<div class="w-full min-h-screen relative text-gray-900 dark:text-gray-100">
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-white/70 dark:bg-themeDark/70 z-50 flex items-center justify-center backdrop-blur-md transition-all duration-300">
        <div class="text-center p-8 bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-xl">
            <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-themeBlue rounded-full animate-spin mb-4"></div>
            <p class="text-xs font-black text-themeBlue dark:text-indigo-400 uppercase tracking-widest">Loading Staff Data...</p>
        </div>
    </div>

    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                Edit Staff Information
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Modify faculty member record, dashboard authentication credentials and biometric device mapping</p>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
            <a href="/teacher/view/{{ $id }}" class="h-11 px-5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 border border-indigo-200 dark:border-indigo-900/60 shadow-sm whitespace-nowrap active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>View Profile</span>
            </a>
        </div>
    </div>

    <!-- Form Panel Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-10 shadow-sm hover:shadow-md transition-all duration-300">
        
        <form id="teacherForm" onsubmit="event.preventDefault(); window.UpdateTeacher();">
            
            <!-- Section 1: Login Account Details -->
            <div class="flex items-center gap-2.5 pb-2 mb-6 border-b border-gray-100 dark:border-white/[0.06]">
                <span class="w-2 h-2 rounded-full bg-themeBlue"></span>
                <h3 class="text-xs font-black tracking-widest text-gray-900 dark:text-white uppercase">1. Dashboard Account Credentials</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Full Name <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="name" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Enter full name" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Email Address (Login ID) <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="email" id="email" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Used for account login" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Password</label>
                    <input type="password" id="password" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Leave blank to keep unchanged">
                </div>
            </div>

            <!-- Section 2: Professional & Personal Details -->
            <div class="flex items-center gap-2.5 pb-2 mb-6 border-b border-gray-100 dark:border-white/[0.06]">
                <span class="w-2 h-2 rounded-full bg-themeGreen"></span>
                <h3 class="text-xs font-black tracking-widest text-gray-900 dark:text-white uppercase">2. Professional & Institutional Details</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Employee ID <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="employee_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. TEA-2026-01" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Designation / Rank <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="designation" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. Assistant Teacher" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Department</label>
                    <input type="text" id="department" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. ICT, Science, Mathematics">
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Mobile Phone <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="phone" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Enter mobile number" required>
                </div>
                
                <!-- Custom Date Picker Component with Month selector & Year Input (Rule 10) -->
                <div class="relative" x-data="datePicker('')" id="joiningDatePickerWrapper" @date-selected.window="if($event.detail) value = $event.detail" @click.away="show = false; mOpen = false">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Joining Date <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="hidden" id="joining_date" :value="value">
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute right-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between gap-1 mb-2 pb-2 border-b border-gray-100 dark:border-white/[0.06]">
                            <!-- Month Dropdown Toggle -->
                            <div class="relative">
                                <button type="button" @click="mOpen = !mOpen" class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1 hover:text-themeBlue">
                                    <span x-text="monthNames[currentMonth]"></span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="mOpen" x-cloak class="absolute left-0 z-50 mt-1 w-32 bg-white dark:bg-themeDark border border-gray-150 dark:border-white/[0.08] rounded-xl shadow-lg py-1 max-h-48 overflow-y-auto">
                                    <template x-for="(m, mIdx) in monthNames" :key="mIdx">
                                        <button type="button" @click="currentMonth = mIdx; mOpen = false; generateCalendar()" class="w-full text-left px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-themeNavy font-bold" :class="currentMonth === mIdx ? 'text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" x-text="m"></button>
                                    </template>
                                </div>
                            </div>

                            <!-- Year Input Field -->
                            <input type="number" x-model.number="currentYear" @input="generateCalendar()" class="w-16 h-7 text-xs font-black text-center bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 focus:outline-none focus:border-themeBlue [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">

                            <!-- Month Navigation Arrows -->
                            <div class="flex items-center gap-1">
                                <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Days header -->
                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
                        
                        <!-- Days grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(d, i) in days" :key="i">
                                <button type="button" @click="selectDay(d.day)" 
                                        class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all"
                                        :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                        :disabled="!d.isCurrentMonth">
                                    <span x-text="d.day"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Gender <span class="text-red-500 ml-0.5">*</span></label>
                    <select id="gender" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="relative">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Blood Group</label>
                    <select id="blood_group" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>

                <div class="relative">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Biometric ID (Device / RFID Mapping)</label>
                    <input type="text" id="biometric_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400 font-mono" placeholder="e.g. 10006">
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Staff Photo</label>
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 shrink-0 bg-gray-50/50 dark:bg-themeDark border-2 border-dashed border-gray-100 dark:border-gray-800 flex items-center justify-center rounded-xl overflow-hidden shadow-sm">
                            <img id="photoPreview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                            <svg id="photoIcon" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="flex-1 relative">
                            <input type="file" id="photo" onchange="window.previewImage(event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/png, image/jpeg, image/gif">
                            <div class="h-11 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-250 text-xs font-black rounded-xl flex items-center justify-center transition-all cursor-pointer w-full uppercase tracking-wider">Change Photo</div>
                        </div>
                    </div>
                    <p class="text-[9px] text-gray-400 dark:text-gray-500 mt-1">Max file size: 1 MB</p>
                </div>

                <div class="lg:col-span-3">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Present / Permanent Address <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="address" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Enter residential address" required>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="mt-10 flex flex-wrap gap-4 items-center border-t border-gray-100 dark:border-white/[0.05] pt-6">
                <a href="{{ route('teachers.index') }}" class="h-11 px-8 border-2 border-red-200 hover:bg-red-50 text-red-600 dark:border-red-950 dark:hover:bg-red-950/20 font-black rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center active:scale-95">Cancel</a>
                <div class="flex-grow"></div>
                <button type="submit" id="btnSubmit" class="h-11 px-12 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center active:scale-95">
                    Update Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const teacherId = "{{ $id }}";

    function getAuthHeaders() {
        return { 
            headers: { 
                'Accept': 'application/json', 
                'X-Requested-With': 'XMLHttpRequest', 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
            } 
        };
    }

    function datePicker(initialValue = '') {
        return {
            show: false,
            mOpen: false,
            value: initialValue,
            currentYear: new Date(initialValue || new Date()).getFullYear(),
            currentMonth: new Date(initialValue || new Date()).getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            
            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        const d = new Date(val);
                        if (!isNaN(d)) {
                            this.currentYear = d.getFullYear();
                            this.currentMonth = d.getMonth();
                            this.generateCalendar();
                        }
                    }
                });
            },
            
            generateCalendar() {
                const firstDayIndex = new Date(this.currentYear, this.currentMonth, 1).getDay();
                const totalDays = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                
                const days = [];
                for (let i = 0; i < firstDayIndex; i++) {
                    days.push({ day: '', isCurrentMonth: false });
                }
                for (let i = 1; i <= totalDays; i++) {
                    days.push({ day: i, isCurrentMonth: true });
                }
                this.days = days;
            },
            
            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
                this.generateCalendar();
            },
            
            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
                this.generateCalendar();
            },
            
            selectDay(day) {
                if (!day) return;
                const m = String(this.currentMonth + 1).padStart(2, '0');
                const d = String(day).padStart(2, '0');
                this.value = `${this.currentYear}-${m}-${d}`;
                this.show = false;
            },
            
            formatDisplay(val) {
                if (!val) return 'Select Date';
                const parts = val.split('-');
                if (parts.length === 3) {
                    const d = new Date(parts[0], parseInt(parts[1]) - 1, parts[2]);
                    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                }
                return val;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            let res = await axios.get(`/ajax/teachers/${teacherId}`, getAuthHeaders());
            let teacher = res.data.data;
            
            document.getElementById('name').value = teacher.user?.name || '';
            document.getElementById('email').value = teacher.user?.email || '';
            
            document.getElementById('employee_id').value = teacher.employee_id || '';
            document.getElementById('designation').value = teacher.designation || '';
            document.getElementById('department').value = teacher.department || '';
            document.getElementById('phone').value = teacher.phone || '';
            document.getElementById('gender').value = teacher.gender || 'Male';
            document.getElementById('blood_group').value = teacher.blood_group || '';
            document.getElementById('biometric_id').value = teacher.biometric_id || '';
            document.getElementById('address').value = teacher.address || '';

            // Update Alpine DatePicker value
            if (teacher.joining_date) {
                document.getElementById('joining_date').value = teacher.joining_date;
                let pickerEl = document.getElementById('joiningDatePickerWrapper');
                if (pickerEl && pickerEl._x_dataStack) {
                    pickerEl._x_dataStack[0].value = teacher.joining_date;
                }
            }

            if (teacher.photo) {
                let preview = document.getElementById('photoPreview');
                let icon = document.getElementById('photoIcon');
                preview.src = '/storage/' + teacher.photo;
                preview.classList.remove('hidden');
                if (icon) icon.classList.add('hidden');
            }

            document.getElementById('loadingOverlay').classList.add('hidden');
        } catch (error) { 
            console.error(error);
            await showAlert("Failed to load staff information!", "Error"); 
            window.location.href = '/teachers'; 
        }
    });

    window.previewImage = function(event) {
        const file = event.target.files[0];
        if (file) {
            let preview = document.getElementById('photoPreview');
            let icon = document.getElementById('photoIcon');
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            if (icon) icon.classList.add('hidden');
        }
    };

    window.UpdateTeacher = async function() {
        let btn = document.getElementById('btnSubmit');
        let originalText = btn.innerText;

        let formData = new FormData();
        formData.append('_method', 'PUT');
        
        formData.append('name', document.getElementById('name').value);
        formData.append('email', document.getElementById('email').value);
        let password = document.getElementById('password').value;
        if (password) formData.append('password', password);

        formData.append('employee_id', document.getElementById('employee_id').value);
        formData.append('biometric_id', document.getElementById('biometric_id').value);
        formData.append('designation', document.getElementById('designation').value);
        formData.append('department', document.getElementById('department').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('joining_date', document.getElementById('joining_date').value);
        formData.append('gender', document.getElementById('gender').value);
        formData.append('blood_group', document.getElementById('blood_group').value);
        formData.append('address', document.getElementById('address').value);

        let photoFile = document.getElementById('photo').files[0];
        if (photoFile) formData.append('photo', photoFile);

        try {
            btn.disabled = true;
            btn.innerText = 'Updating Record...';

            let res = await axios.post(`/ajax/teachers/${teacherId}`, formData, {
                headers: { 
                    'Accept': 'application/json', 
                    'Content-Type': 'multipart/form-data', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                }
            });

            if (res.status === 200) { 
                await showAlert('Staff information updated successfully!', 'Success'); 
                window.location.href = '/teacher/view/' + teacherId; 
            }
        } catch (error) { 
            let errMsg = error.response?.data?.message || 'Update failed! Please check the input fields.';
            await showAlert(errMsg, 'Update Error'); 
        } finally {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    };
</script>
@endpush