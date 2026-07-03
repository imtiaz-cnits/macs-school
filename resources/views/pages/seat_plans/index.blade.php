@extends('tyro-dashboard::layouts.admin')

@section('title', 'Generate Seat Plan')

@section('content')
<div x-data="seatPlanGenerator()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                </svg>
                Seat Plan Generator
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Generate and print examination seat plans for registered students</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-lg shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Form Card Wrapper -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('seat-plans.generate') }}" method="POST" target="_blank" @submit="
            if(!form.session_year_id) { event.preventDefault(); showAlert('Please select Session!', 'Validation'); return; }
            if(!form.class_id) { event.preventDefault(); showAlert('Please select Class!', 'Validation'); return; }
            if(!form.branch_id) { event.preventDefault(); showAlert('Please select Branch!', 'Validation'); return; }
            if(!form.shift_id) { event.preventDefault(); showAlert('Please select Shift!', 'Validation'); return; }
            if(!form.section_id) { event.preventDefault(); showAlert('Please select Section!', 'Validation'); return; }
            if(!form.exam_id) { event.preventDefault(); showAlert('Please select Exam!', 'Validation'); return; }
        ">
            @csrf
            
            <input type="hidden" name="session_year_id" :value="form.session_year_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="shift_id" :value="form.shift_id">
            <input type="hidden" name="section_id" :value="form.section_id">
            <input type="hidden" name="exam_id" :value="form.exam_id">
            
            <!-- Grid columns - 3 columns layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Session Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'session') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Session *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'session' ? null : 'session'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="sessionText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectSession('', 'Choose Session')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-450 transition-colors">Choose Session</button>
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

                <!-- Class Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class *</label>
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

                <!-- Branch Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Branch *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="branchText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectBranch('', 'Choose Branch')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-450 transition-colors">Choose Branch</button>
                        @foreach($branches as $branch)
                            <button type="button" @click="selectBranch('{{ $branch->id }}', '{{ $branch->branch_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.branch_id == '{{ $branch->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $branch->branch_name }}</span>
                                <template x-if="form.branch_id == '{{ $branch->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Shift Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'shift') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Shift *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'shift' ? null : 'shift'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="shiftText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'shift'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectShift('', 'Choose Shift')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-450 transition-colors">Choose Shift</button>
                        @foreach($shifts as $shift)
                            <button type="button" @click="selectShift('{{ $shift->id }}', '{{ $shift->shift_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.shift_id == '{{ $shift->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $shift->shift_name }}</span>
                                <template x-if="form.shift_id == '{{ $shift->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Section Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'section') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Section *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'section' ? null : 'section'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="sectionText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'section'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectSection('', 'Choose Section')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-450 transition-colors">Choose Section</button>
                        @foreach($sections as $section)
                            <button type="button" @click="selectSection('{{ $section->id }}', '{{ $section->section_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id == '{{ $section->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $section->section_name }}</span>
                                <template x-if="form.section_id == '{{ $section->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Exam Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'exam') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'exam' ? null : 'exam'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="examText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'exam'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectExam('', 'Choose Exam')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-450 transition-colors">Choose Exam</button>
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

            <div class="flex justify-center border-t border-gray-100 dark:border-white/[0.06] pt-6">
                <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-16 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 flex items-center justify-center">
                    Generate Seat Plan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function seatPlanGenerator() {
        return {
            activeDropdown: null,
            sessionText: 'Choose Session',
            classText: 'Choose Class',
            branchText: 'Choose Branch',
            shiftText: 'Choose Shift',
            sectionText: 'Choose Section',
            examText: 'Choose Exam',
            
            form: {
                session_year_id: '',
                class_id: '',
                branch_id: '',
                shift_id: '',
                section_id: '',
                exam_id: ''
            },
            
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
                this.activeDropdown = null;
            },
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.activeDropdown = null;
            },
            selectBranch(id, name) {
                this.form.branch_id = id;
                this.branchText = name;
                this.activeDropdown = null;
            },
            selectShift(id, name) {
                this.form.shift_id = id;
                this.shiftText = name;
                this.activeDropdown = null;
            },
            selectSection(id, name) {
                this.form.section_id = id;
                this.sectionText = name;
                this.activeDropdown = null;
            },
            selectExam(id, name) {
                this.form.exam_id = id;
                this.examText = name;
                this.activeDropdown = null;
            }
        };
    }
</script>
@endpush