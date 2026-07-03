@extends('tyro-dashboard::layouts.admin')

@section('title', 'Exam Subject Setup')

@push('styles')
<!-- Load Alpine.js to fix dropdown component issues -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Table padding override to align with MACS Design guidelines */
    .table th, .table td {
        padding: 0.875rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div x-data="examSubjectSetup()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Exam Subject Setup
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Student examination subject listing and marks distribution</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8">
        <form action="{{ route('exam-schedules.store') }}" method="POST" @submit="
            if(!form.branch_id) { event.preventDefault(); showAlert('Please select Branch!', 'Validation'); return; }
            if(!form.exam_id) { event.preventDefault(); showAlert('Please select Exam!', 'Validation'); return; }
            if(!form.class_id) { event.preventDefault(); showAlert('Please select Class!', 'Validation'); return; }
            if(!form.subject_id) { event.preventDefault(); showAlert('Please select Subject!', 'Validation'); return; }
        ">
            @csrf
            
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="exam_id" :value="form.exam_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="subject_id" :value="form.subject_id">
            <input type="hidden" name="exam_date" :value="form.exam_date">
            <input type="hidden" name="start_time" :value="form.start_time">
            <input type="hidden" name="end_time" :value="form.end_time">

            <h4 class="text-xs font-black text-themeBlue uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-6">Basic Selection</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Branch Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Branch *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="branchText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectBranch('', 'Choose Branch')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-450 transition-colors">Choose Branch</button>
                        @foreach($branches ?? [] as $branch)
                            <button type="button" @click="selectBranch('{{ $branch->id }}', '{{ $branch->branch_name ?? $branch->name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == '{{ $branch->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $branch->branch_name ?? $branch->name }}</span>
                                <template x-if="form.branch_id == '{{ $branch->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Exam Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'exam') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Exam *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'exam' ? null : 'exam'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="examText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'exam'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectExam('', 'Choose Exam')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Choose Exam</button>
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

                <!-- Class Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Class *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="classText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectClass('', 'Choose Class')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Choose Class</button>
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

                <!-- Subject Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'subject') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Subject *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'subject' ? null : 'subject'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="subjectText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'subject'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectSubject('', 'Choose Subject')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Choose Subject</button>
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
            </div>

            <h4 class="text-xs font-black text-themeBlue uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-6">Marks Distribution</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6 mb-8">
                <!-- Full Marks -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Full Marks *</label>
                    <input type="number" step="0.01" name="full_marks" x-model="form.full_marks" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>
                <!-- Pass Marks -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Pass Marks *</label>
                    <input type="number" step="0.01" name="pass_marks" x-model="form.pass_marks" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>
                <!-- CT Marks -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">CT / Tutorial</label>
                    <input type="number" step="0.01" name="ct_marks" x-model="form.ct_marks" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>
                <!-- Written Marks -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Written Marks</label>
                    <input type="number" step="0.01" name="written_marks" x-model="form.written_marks" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>
                <!-- MCQ Marks -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">MCQ Marks</label>
                    <input type="number" step="0.01" name="mcq_marks" x-model="form.mcq_marks" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>
            </div>

            <h4 class="text-xs font-black text-themeBlue uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-6">Schedule Details (Optional)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Exam Date Picker -->
                <div class="relative" x-data="datePicker(form.exam_date)" @date-selected="form.exam_date = $event.detail" @click.away="show = false">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam Date</label>
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

                <!-- Start Time Picker -->
                <div class="relative" x-data="timePicker(form.start_time)" @time-selected="form.start_time = $event.detail" @click.away="show = false">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Start Time</label>
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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

                <!-- End Time Picker -->
                <div class="relative" x-data="timePicker(form.end_time)" @time-selected="form.end_time = $event.detail" @click.away="show = false">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">End Time</label>
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

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-white/[0.06]">
                <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95">
                    Save Subject
                </button>
            </div>
        </form>
    </div>

    <!-- List Section -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm">
        <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Scheduled Subjects List</h3>
        
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-16 text-center">#</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Exam & Class</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Subject</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Marks (Full / Pass)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Dist. (CT/W/M)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-24">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                    @forelse($schedules as $index => $schedule)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                        <td class="py-0 px-0 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">{{ $index + 1 }}</td>
                        <td class="py-0 px-0">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $schedule->exam->name ?? 'N/A' }}</div>
                            <div class="text-[10px] font-bold text-themeGreen dark:text-green-500 mt-0.5 uppercase tracking-wider">Class: {{ $schedule->classes->class_name ?? 'N/A' }}</div>
                            @if($schedule->branch)
                                <div class="text-[9px] font-semibold text-gray-450 dark:text-gray-550 mt-0.5 uppercase tracking-widest">{{ $schedule->branch->branch_name ?? $schedule->branch->name }}</div>
                            @endif
                        </td>
                        <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ $schedule->subject->subject_name ?? 'N/A' }}
                            @if($schedule->exam_date)
                                <div class="text-[10px] font-semibold text-gray-450 dark:text-gray-500 mt-1 uppercase tracking-wide">
                                    {{ date('d M Y', strtotime($schedule->exam_date)) }}
                                    @if($schedule->start_time)
                                        | {{ date('h:i A', strtotime($schedule->start_time)) }} - {{ date('h:i A', strtotime($schedule->end_time)) }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="py-0 px-0 text-center">
                            <span class="inline-block px-2.5 py-1 text-[10px] font-bold text-themeGreen bg-themeGreen/10 rounded-lg">{{ $schedule->full_marks }}</span>
                            <span class="text-gray-400 mx-1">/</span>
                            <span class="inline-block px-2.5 py-1 text-[10px] font-bold text-red-650 bg-red-100 dark:bg-red-950/20 dark:text-red-400 rounded-lg">{{ $schedule->pass_marks }}</span>
                        </td>
                        <td class="py-0 px-0 text-center text-xs font-semibold text-gray-650 dark:text-gray-400">
                            {{ $schedule->ct_marks ?? 0 }} / {{ $schedule->written_marks ?? 0 }} / {{ $schedule->mcq_marks ?? 0 }}
                        </td>
                        <td class="py-0 px-0 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('exam-schedules.destroy', $schedule->id) }}" method="POST" @submit.prevent="if (await showDanger('Delete Schedule', 'Are you sure you want to delete this subject exam schedule? This action cannot be undone.')) $el.submit()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Schedule">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No scheduled subjects found. Set up one above!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Custom Date Picker component data definition
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue,
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

    function examSubjectSetup() {
        return {
            activeDropdown: null,
            branchText: 'Choose Branch',
            examText: 'Choose Exam',
            classText: 'Choose Class',
            subjectText: 'Choose Subject',
            
            form: {
                branch_id: '',
                exam_id: '',
                class_id: '',
                subject_id: '',
                full_marks: '100',
                pass_marks: '33',
                ct_marks: '20',
                written_marks: '80',
                mcq_marks: '0',
                exam_date: '',
                start_time: '',
                end_time: ''
            },
            
            selectBranch(id, name) {
                this.form.branch_id = id;
                this.branchText = name;
                this.activeDropdown = null;
            },
            selectExam(id, name) {
                this.form.exam_id = id;
                this.examText = name;
                this.activeDropdown = null;
            },
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.activeDropdown = null;
            },
            selectSubject(id, name) {
                this.form.subject_id = id;
                this.subjectText = name;
                this.activeDropdown = null;
            }
        };
    }
</script>
@endpush