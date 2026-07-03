@extends('tyro-dashboard::layouts.admin')

@section('title', 'Exam Routine Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<!-- Load Alpine.js to fix dropdown component issues -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Table padding override to align with MACS Design guidelines */
    .table th, .table td {
        padding: 0.875rem 1rem !important;
    }

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
<div x-data="examRoutinePage()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Exam Routine
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Manage exam schedules, subject slots, timings, and print formal sheets</p>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Add Exam Subject Form -->
        <div class="lg:col-span-4 no-print">
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Add Exam Subject</h3>
                
                <form @submit.prevent="saveSchedule()">
                    <div class="space-y-4">
                        
                        <!-- Session & Exam Name Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Session Select -->
                            <div class="relative" @click.away="if(dropdownOpen === 'session') dropdownOpen = null">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Session *</label>
                                <button type="button" @click="dropdownOpen = dropdownOpen === 'session' ? null : 'session'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="sessionText"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="dropdownOpen === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    @foreach($sessions as $session)
                                        <button type="button" @click="selectSession('{{ $session->id }}', '{{ $session->session_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id == '{{ $session->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span>{{ $session->session_name }}</span>
                                            <template x-if="form.session_year_id == '{{ $session->id }}'">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Exam Select -->
                            <div class="relative" @click.away="if(dropdownOpen === 'exam') dropdownOpen = null">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam Name *</label>
                                <button type="button" @click="dropdownOpen = dropdownOpen === 'exam' ? null : 'exam'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="examText"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="dropdownOpen === 'exam'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                    <button type="button" @click="selectExam('', 'Select Exam')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-450 transition-colors">Select Exam</button>
                                    @foreach($exams as $exam)
                                        <button type="button" @click="selectExam('{{ $exam->id }}', '{{ $exam->name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.exam_id == '{{ $exam->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                            <span>{{ $exam->name }}</span>
                                            <template x-if="form.exam_id == '{{ $exam->id }}'">
                                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Class Select -->
                        <div class="relative" @click.away="if(dropdownOpen === 'class') dropdownOpen = null">
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class *</label>
                            <button type="button" @click="dropdownOpen = dropdownOpen === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate" x-text="classText"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="dropdownOpen === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                <button type="button" @click="selectClass('', 'Select Class...')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Class...</button>
                                @foreach($classes as $class)
                                    <button type="button" @click="selectClass('{{ $class->id }}', '{{ $class->class_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.class_id == '{{ $class->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>{{ $class->class_name }}</span>
                                        <template x-if="form.class_id == '{{ $class->id }}'">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Subject Select -->
                        <div class="relative" @click.away="if(dropdownOpen === 'subject') dropdownOpen = null">
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Subject *</label>
                            <button type="button" @click="dropdownOpen = dropdownOpen === 'subject' ? null : 'subject'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate" x-text="subjectText"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="dropdownOpen === 'subject'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                <button type="button" @click="selectSubject('', 'Select Subject...')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Subject...</button>
                                @foreach($subjects as $subject)
                                    <button type="button" @click="selectSubject('{{ $subject->id }}', '{{ $subject->subject_name ?? $subject->name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.subject_id == '{{ $subject->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span>{{ $subject->subject_name ?? $subject->name }}</span>
                                        <template x-if="form.subject_id == '{{ $subject->id }}'">
                                            <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </template>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Date & Room No -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Custom Date Picker -->
                            <div class="relative" x-data="datePicker(form.exam_date)" @date-selected="form.exam_date = $event.detail" @click.away="show = false">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam Date *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                                <div x-show="show" x-cloak class="absolute z-50 w-64 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                                    <div class="flex items-center justify-between mb-3">
                                        <button type="button" @click="prevMonth()" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg></button>
                                        <div class="text-[10px] font-black text-gray-850 dark:text-gray-200 uppercase tracking-widest"><span x-text="monthNames[currentMonth]"></span> <span x-text="currentYear"></span></div>
                                        <button type="button" @click="nextMonth()" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg></button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 mb-2">
                                        <div>S</div><div>M</div><div>T</div><div>W</div><div>T</div><div>F</div><div>S</div>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1">
                                        <template x-for="dayObj in days">
                                            <button type="button" @click="selectDay(dayObj.day)" class="aspect-square text-xs font-bold rounded-lg flex items-center justify-center transition-colors" :class="dayObj.day ? (value === `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(dayObj.day).padStart(2, '0')}` ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-105 dark:hover:bg-gray-800') : 'pointer-events-none opacity-0'" x-text="dayObj.day || ''"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <!-- Room No -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Room No (Opt)</label>
                                <input type="text" name="room_number" x-model="form.room_number" placeholder="Ex: 101" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            </div>
                        </div>

                        <!-- Start & End Time -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Start Time Custom Picker -->
                            <div class="relative" x-data="timePicker(form.start_time)" @time-selected="form.start_time = $event.detail" @click.away="show = false">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Start Time *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <div x-show="show" x-cloak class="absolute z-50 w-48 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                                    <div class="flex gap-2 justify-center items-center">
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">HR</span>
                                            <select :value="hour" @change="selectHour(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-750 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="h in [12,1,2,3,4,5,6,7,8,9,10,11]"><option :value="h" x-text="h"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">MIN</span>
                                            <select :value="minute" @change="selectMinute(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-750 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="m in [0,5,10,15,20,25,30,35,40,45,50,55]"><option :value="m" x-text="String(m).padStart(2, '0')"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">AM/PM</span>
                                            <select :value="period" @change="selectPeriod($event.target.value)" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-750 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <option value="AM">AM</option><option value="PM">PM</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- End Time Custom Picker -->
                            <div class="relative" x-data="timePicker(form.end_time)" @time-selected="form.end_time = $event.detail" @click.away="show = false">
                                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">End Time *</label>
                                <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                    <span class="truncate" x-text="formatDisplay(value)"></span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                <div x-show="show" x-cloak class="absolute z-50 w-48 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                                    <div class="flex gap-2 justify-center items-center">
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">HR</span>
                                            <select :value="hour" @change="selectHour(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-755 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="h in [12,1,2,3,4,5,6,7,8,9,10,11]"><option :value="h" x-text="h"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">MIN</span>
                                            <select :value="minute" @change="selectMinute(parseInt($event.target.value))" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-755 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <template x-for="m in [0,5,10,15,20,25,30,35,40,45,50,55]"><option :value="m" x-text="String(m).padStart(2, '0')"></option></template>
                                            </select>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">AM/PM</span>
                                            <select :value="period" @change="selectPeriod($event.target.value)" class="bg-gray-50 dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-lg p-1 text-xs font-bold text-gray-755 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                                <option value="AM">AM</option><option value="PM">PM</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button type="submit" :disabled="saving" class="w-full bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 flex items-center justify-center gap-2">
                            <span x-text="saving ? 'Saving...' : '+ Add to Schedule'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Exam Routine Schedule Viewer Sheet -->
        <div class="lg:col-span-8">
            <div class="flex justify-between items-center mb-6 no-print">
                <span x-show="loading" class="text-xs font-bold text-gray-500 uppercase animate-pulse">Syncing...</span>
                <button type="button" @click="window.print()" class="ml-auto bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Schedule
                </button>
            </div>

            <!-- Placeholder select information -->
            <div x-show="noData" class="text-center py-20 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl no-print">
                <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Select Details</h4>
                <p class="text-xs text-gray-450 dark:text-gray-500 font-bold mt-2">Choose Session, Exam & Class to view the schedule.</p>
            </div>

            <!-- Routine Board table sheet -->
            <div x-show="!noData" class="bg-white dark:bg-themeNavy rounded-3xl p-6 border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-x-auto" id="printableRoutine">
                
                <!-- School print header -->
                <div class="school-header hidden print:block mb-6">
                    <h1>MACS School & College</h1>
                    <h3 x-text="printExamName">Term Examination - 2026</h3>
                    <p x-text="printClassInfo">Class: Five</p>
                </div>

                <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date & Day</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Subject</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Time</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Room</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center no-print w-24">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                            <template x-for="slot in routineSlots" :key="slot.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0">
                                        <span class="font-bold text-gray-900 dark:text-gray-100 text-sm" x-text="formatDate(slot.exam_date)"></span>
                                    </td>
                                    <td class="py-0 px-0">
                                        <span class="font-bold text-gray-900 dark:text-gray-100 text-sm" x-text="slot.subject ? (slot.subject.subject_name || slot.subject.name) : 'N/A'"></span>
                                    </td>
                                    <td class="py-0 px-0">
                                        <span class="inline-block px-2.5 py-1 text-[10px] font-bold text-themeBlue bg-themeBlue/10 rounded-lg" x-text="`${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}`"></span>
                                    </td>
                                    <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400" x-text="slot.room_number || 'TBA'"></td>
                                    <td class="py-0 px-0 text-center no-print">
                                        <div class="flex items-center justify-center">
                                            <button type="button" @click="deleteRoutine(slot.id)" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Slot">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="routineSlots.length === 0">
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No exams scheduled yet</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // Custom Date Picker component data definition
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue || new Date().toISOString().split('T')[0],
            currentYear: new Date().getFullYear(),
            currentMonth: new Date().getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            
            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        const d = new Date(val);
                        this.currentYear = d.getFullYear();
                        this.currentMonth = d.getMonth();
                        this.generateCalendar();
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
                const formattedMonth = String(this.currentMonth + 1).padStart(2, '0');
                const formattedDay = String(day).padStart(2, '0');
                this.value = `${this.currentYear}-${formattedMonth}-${formattedDay}`;
                this.show = false;
                this.$dispatch('date-selected', this.value);
            },
            
            formatDisplay(val) {
                if (!val) return 'Select Date';
                const d = new Date(val);
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        }
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

    function examRoutinePage() {
        return {
            dropdownOpen: null,
            sessionText: '{{ $sessions->first()->session_name ?? "Select Session" }}',
            examText: 'Select Exam',
            classText: 'Select Class...',
            subjectText: 'Select Subject...',
            
            printExamName: 'Term Examination',
            printClassInfo: 'Class Name',
            
            form: {
                session_year_id: '{{ $sessions->first()->id ?? "" }}',
                exam_id: '',
                class_id: '',
                subject_id: '',
                exam_date: new Date().toISOString().split('T')[0],
                room_number: '',
                start_time: '09:00',
                end_time: '12:00'
            },
            
            routineSlots: [],
            noData: true,
            loading: false,
            saving: false,
            
            init() {
                this.loadRoutine();
            },
            
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
                this.dropdownOpen = null;
                this.loadRoutine();
            },
            
            selectExam(id, name) {
                this.form.exam_id = id;
                this.examText = name;
                this.dropdownOpen = null;
                this.loadRoutine();
            },
            
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.dropdownOpen = null;
                this.loadRoutine();
            },
            
            selectSubject(id, name) {
                this.form.subject_id = id;
                this.subjectText = name;
                this.dropdownOpen = null;
            },
            
            formatTime(timeStr) {
                let [hours, minutes] = timeStr.split(':');
                let ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                return `${hours}:${minutes} ${ampm}`;
            },

            formatDate(dateString) {
                const options = { day: '2-digit', month: 'short', year: 'numeric', weekday: 'long' };
                return new Date(dateString).toLocaleDateString('en-US', options);
            },
            
            async loadRoutine() {
                if (!this.form.class_id || !this.form.exam_id) {
                    this.noData = true;
                    this.routineSlots = [];
                    return;
                }
                
                this.printExamName = `${this.examText} - ${this.sessionText}`;
                this.printClassInfo = `Class: ${this.classText}`;
                this.loading = true;
                
                try {
                    const res = await axios.get('/exam-routine/get', { params: { 
                        session_year_id: this.form.session_year_id, 
                        exam_id: this.form.exam_id, 
                        class_id: this.form.class_id 
                    } });
                    this.routineSlots = res.data.routine;
                    this.noData = false;
                } catch (err) {
                    console.error(err);
                } finally {
                    this.loading = false;
                }
            },
            
            async saveSchedule() {
                if (!this.form.subject_id) {
                    showAlert('Please select Subject!', 'Validation');
                    return;
                }
                
                this.saving = true;
                try {
                    const res = await axios.post('/exam-routine/store', this.form, { 
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
                    });
                    
                    if (res.data.status === 'error') {
                        showModal('Error', res.data.message, 'danger');
                    } else {
                        showSuccess(res.data.message);
                        
                        // Clear specific input fields for next subject entry
                        this.form.subject_id = '';
                        this.subjectText = 'Select Subject...';
                        this.form.room_number = '';
                        
                        this.loadRoutine();
                    }
                } catch (err) {
                    let errMsg = 'System encountered an error. Please verify input fields.';
                    if (err.response && err.response.data && err.response.data.message) {
                        errMsg = err.response.data.message;
                    }
                    showModal('Error', errMsg, 'danger');
                } finally {
                    this.saving = false;
                }
            },
            
            async deleteRoutine(id) {
                const confirmed = await showDanger('Delete Schedule Slot', 'Are you sure you want to delete this exam routine slot?');
                if (confirmed) {
                    try {
                        const res = await axios.delete(`/exam-routine/destroy/${id}`, { 
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
                        });
                        showSuccess(res.data.message);
                        this.loadRoutine();
                    } catch (err) {
                        let errMsg = 'Failed to delete routine slot.';
                        if (err.response && err.response.data && err.response.data.message) {
                            errMsg = err.response.data.message;
                        }
                        showModal('Error', errMsg, 'danger');
                    }
                }
            }
        };
    }
</script>
@endpush