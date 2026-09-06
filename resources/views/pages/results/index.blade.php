@extends('tyro-dashboard::layouts.admin')

@section('title', 'Generate Marksheet')

@section('content')
<div x-data="marksheetGenerator()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <circle cx="12" cy="8" r="7"/>
                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                </svg>
                Marksheet Hub
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Generate official academic progress report marksheets (Single Exam or 3-Term Combined)</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-lg shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Form Card Wrapper -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('results.generate') }}" method="POST" target="_blank" @submit="
            if(!form.session_year_id) { event.preventDefault(); showAlert('Please select Academic Session!', 'Validation'); return; }
            if(reportType === 'single' && !form.exam_id) { event.preventDefault(); showAlert('Please select Exam!', 'Validation'); return; }
            if(!form.student_identity) { event.preventDefault(); showAlert('Please enter Student ID or Roll number!', 'Validation'); return; }
        ">
            @csrf
            
            <input type="hidden" name="report_type" :value="reportType">
            <input type="hidden" name="session_year_id" :value="form.session_year_id">
            <input type="hidden" name="exam_id" :value="form.exam_id">
            <input type="hidden" name="class_id" :value="form.class_id">

            <!-- Report Type Switcher Tabs -->
            <div class="mb-8 p-1.5 bg-gray-50 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] rounded-2xl flex flex-col sm:flex-row gap-2 max-w-xl mx-auto">
                <button type="button" @click="reportType = 'single'" class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2" :class="reportType === 'single' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Single Exam (A4 Portrait)</span>
                </button>
                <button type="button" @click="reportType = 'combined'" class="flex-1 py-3 px-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-2" :class="reportType === 'combined' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Combined 3-Terms (A4 Landscape)</span>
                </button>
            </div>

            <!-- Notice for Combined Report -->
            <template x-if="reportType === 'combined'">
                <div class="mb-6 p-4 bg-indigo-50 dark:bg-themeBlue/10 border border-themeBlue/20 rounded-2xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        Generates the comprehensive <strong>3-Term Progress Report</strong> (1st Term + 2nd Term + Annual Exam) with final cumulative GPA, LG, and term-by-term merit positions.
                    </p>
                </div>
            </template>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Session Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'session') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Academic Session *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'session' ? null : 'session'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="sessionText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
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

                <!-- Class Dropdown (Optional Helper for Roll lookup) -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class (Optional)</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="classText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectClass('', 'All Classes')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors text-gray-500">
                            <span>All Classes</span>
                        </button>
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

                <!-- Exam Dropdown (Visible only for Single Exam) -->
                <div class="relative" x-show="reportType === 'single'" @click.away="if(activeDropdown === 'exam') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Exam *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'exam' ? null : 'exam'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="examText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'exam'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
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

                <!-- Combined Info Card when in combined mode -->
                <div x-show="reportType === 'combined'" class="flex flex-col justify-center">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Included Exams</label>
                    <div class="h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center text-xs font-bold text-themeBlue truncate">
                        1st Term + 2nd Term + Annual
                    </div>
                </div>

                <!-- Student Identity -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Student ID or Roll *</label>
                    <input type="text" name="student_identity" x-model="form.student_identity" placeholder="Enter ID (e.g. 2423112171) or Roll" class="w-full h-11 px-4 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-250 placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>
            </div>

            <div class="flex justify-center border-t border-gray-100 dark:border-white/[0.06] pt-6">
                <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-3.5 px-14 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Generate Marksheet PDF
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function marksheetGenerator() {
        return {
            reportType: 'single',
            activeDropdown: null,
            sessionText: 'Select Session',
            examText: 'Select Exam',
            classText: 'All Classes',
            
            form: {
                session_year_id: '{{ $sessions->first()->id ?? "" }}',
                exam_id: '{{ $exams->first()->id ?? "" }}',
                class_id: '',
                student_identity: ''
            },

            init() {
                @if($sessions->isNotEmpty())
                    this.sessionText = '{{ $sessions->first()->session_name }}';
                @endif
                @if($exams->isNotEmpty())
                    this.examText = '{{ $exams->first()->name }}';
                @endif
            },
            
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
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
            }
        };
    }
</script>
@endpush