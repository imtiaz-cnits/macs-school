@extends('tyro-dashboard::layouts.admin')

@section('title', 'Smart Marks Entry')

@section('content')
<div x-data="marksFilterController()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Smart Marks Entry
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Record and manage terminal examination marks for students</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-lg shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Filters Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8">
        <form action="{{ route('marks.index') }}" method="GET" @submit="
            if(!form.session_year_id) { event.preventDefault(); showAlert('Please select Session!', 'Validation'); return; }
            if(!form.branch_id) { event.preventDefault(); showAlert('Please select Branch!', 'Validation'); return; }
            if(!form.exam_id) { event.preventDefault(); showAlert('Please select Exam!', 'Validation'); return; }
            if(!form.class_id) { event.preventDefault(); showAlert('Please select Class!', 'Validation'); return; }
            if(!form.subject_id) { event.preventDefault(); showAlert('Please select Subject!', 'Validation'); return; }
        ">
            
            <input type="hidden" name="session_year_id" :value="form.session_year_id">
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="exam_id" :value="form.exam_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="subject_id" :value="form.subject_id">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Session Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'session') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Academic Session *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'session' ? null : 'session'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="sessionText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'session'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectSession('', 'Select Session')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Session</button>
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

                <!-- Branch Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Branch *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="branchText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectBranch('', 'Select Branch')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Branch</button>
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

                <!-- Exam Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'exam') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Exam *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'exam' ? null : 'exam'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="examText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'exam'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectExam('', 'Select Exam')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Exam</button>
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <!-- Class Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Class *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="classText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectClass('', 'Select Class')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-55/50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Class</button>
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
                        <button type="button" @click="selectSubject('', 'Select Subject')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-455 transition-colors">Select Subject</button>
                        @foreach($subjects as $subject)
                            <button type="button" @click="selectSubject('{{ $subject->id }}', '{{ $subject->subject_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.subject_id == '{{ $subject->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $subject->subject_name }}</span>
                                <template x-if="form.subject_id == '{{ $subject->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-[0.15em] text-xs active:scale-95 flex items-center justify-center">
                        Load Students
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Enter Marks View Table Wrapper -->
    @if(request()->filled(['session_year_id', 'branch_id', 'exam_id', 'class_id', 'subject_id']))
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 relative">
            
            <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-white/[0.04] pb-4">
                <h3 class="text-sm font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider ml-1">Enter Marks</h3>
                @if($exam_schedule)
                    <div class="text-xs font-bold text-gray-600 bg-gray-50 dark:bg-themeDark px-4 py-2 rounded-xl border border-gray-100 dark:border-gray-800">
                        Full Marks: <span class="text-themeGreen font-black">{{ $exam_schedule->full_marks }}</span> | Pass: <span class="text-red-500 font-black">{{ $exam_schedule->pass_marks }}</span>
                    </div>
                @else
                    <div class="text-xs font-bold text-red-500 bg-red-50 dark:bg-red-950/20 px-4 py-2 rounded-xl">Subject not scheduled for this class!</div>
                @endif
            </div>

            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">ID / Roll</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Student Name</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-themeGreen uppercase tracking-[0.2em] text-center">CT ({{ $exam_schedule->ct_marks ?? 0 }})</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-themeBlue uppercase tracking-[0.2em] text-center">Written ({{ $exam_schedule->written_marks ?? 0 }})</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-purple-500 uppercase tracking-[0.2em] text-center">MCQ ({{ $exam_schedule->mcq_marks ?? 0 }})</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-450 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Total</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-450 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Grade</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-450 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-3.5 px-4 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">{{ $student->student_identity ?? $student->id }}</td>
                            <td class="py-3.5 px-4 text-sm font-bold text-gray-900 dark:text-gray-100">{{ $student->student_name ?? 'Unknown' }}</td>
                            
                            <td class="py-3.5 px-4 text-center">
                                <input type="number" step="0.5" class="mark-input ct-input h-9 text-center font-bold border-2 border-gray-100 dark:border-gray-800 rounded-lg bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all w-20" data-id="{{ $student->id }}" value="{{ $student->mark->ct_mark ?? '' }}">
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <input type="number" step="0.5" class="mark-input written-input h-9 text-center font-bold border-2 border-gray-100 dark:border-gray-800 rounded-lg bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all w-20" data-id="{{ $student->id }}" value="{{ $student->mark->written_mark ?? '' }}">
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <input type="number" step="0.5" class="mark-input mcq-input h-9 text-center font-bold border-2 border-gray-100 dark:border-gray-800 rounded-lg bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all w-20" data-id="{{ $student->id }}" value="{{ $student->mark->mcq_mark ?? '' }}">
                            </td>
                            
                            <td class="py-3.5 px-4 text-center font-black text-gray-800 dark:text-gray-200 text-lg total-display-{{ $student->id }}">
                                {{ $student->mark->total_mark ?? 0 }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="grade-display-{{ $student->id }} px-3 py-1 text-xs font-black rounded-full {{ isset($student->mark) && $student->mark->grade_point >= 4 ? 'bg-green-100 text-green-700 dark:bg-green-950/20 dark:text-green-400' : (isset($student->mark) && ($student->mark->letter_grade == 'F' || $student->mark->letter_grade == 'Fail') ? 'bg-red-100 text-red-700 dark:bg-red-950/20 dark:text-red-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300') }}">
                                    {{ $student->mark->letter_grade ?? '--' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="status-{{ $student->id }} text-[10px] font-bold px-2.5 py-1 rounded-full transition-opacity duration-200 opacity-0"></span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-sm font-bold text-gray-400">No students found for this specific Branch & Session.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    function marksFilterController() {
        return {
            activeDropdown: null,
            sessionText: '{{ $sessions->firstWhere("id", request("session_year_id"))->session_name ?? "Select Session" }}',
            branchText: '{{ $branches->firstWhere("id", request("branch_id"))->branch_name ?? "Select Branch" }}',
            examText: '{{ $exams->firstWhere("id", request("exam_id"))->name ?? "Select Exam" }}',
            classText: '{{ $classes->firstWhere("id", request("class_id"))->class_name ?? "Select Class" }}',
            subjectText: '{{ $subjects->firstWhere("id", request("subject_id"))->subject_name ?? "Select Subject" }}',
            
            form: {
                session_year_id: '{{ request("session_year_id") }}',
                branch_id: '{{ request("branch_id") }}',
                exam_id: '{{ request("exam_id") }}',
                class_id: '{{ request("class_id") }}',
                subject_id: '{{ request("subject_id") }}'
            },
            
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
                this.activeDropdown = null;
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

    document.addEventListener('DOMContentLoaded', function() {
        const session_year_id = "{{ request('session_year_id') }}";
        const branch_id = "{{ request('branch_id') }}";
        const exam_id = "{{ request('exam_id') }}";
        const class_id = "{{ request('class_id') }}";
        const subject_id = "{{ request('subject_id') }}";

        document.querySelectorAll('.mark-input').forEach(input => {
            input.addEventListener('blur', function() {
                saveMark(this.dataset.id);
            });
            input.addEventListener('keypress', function(e) {
                if(e.key === 'Enter') input.blur(); 
            });
        });

        function saveMark(studentId) {
            const row = document.querySelector(`.ct-input[data-id="${studentId}"]`).closest('tr');
            const ctVal = parseFloat(row.querySelector('.ct-input').value) || 0;
            const writtenVal = parseFloat(row.querySelector('.written-input').value) || 0;
            const mcqVal = parseFloat(row.querySelector('.mcq-input').value) || 0;

            const statusLabel = row.querySelector(`.status-${studentId}`);
            statusLabel.textContent = 'Saving...';
            statusLabel.className = `status-${studentId} text-[10px] font-bold px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 opacity-100`;

            axios.post("{{ route('marks.store.ajax') }}", {
                _token: "{{ csrf_token() }}",
                session_year_id: session_year_id,
                branch_id: branch_id,
                exam_id: exam_id,
                class_id: class_id,
                subject_id: subject_id,
                student_id: studentId,
                ct_mark: ctVal,
                written_mark: writtenVal,
                mcq_mark: mcqVal
            })
            .then(response => {
                if(response.data.success) {
                    row.querySelector(`.total-display-${studentId}`).textContent = response.data.total;
                    
                    const gradeSpan = row.querySelector(`.grade-display-${studentId}`);
                    gradeSpan.textContent = response.data.letter_grade;
                    
                    if(response.data.letter_grade === 'F' || response.data.letter_grade === 'Fail') {
                        gradeSpan.className = `grade-display-${studentId} px-3 py-1 text-xs font-black rounded-full bg-red-100 text-red-700 dark:bg-red-950/20 dark:text-red-400`;
                    } else {
                        gradeSpan.className = `grade-display-${studentId} px-3 py-1 text-xs font-black rounded-full bg-green-100 text-green-700 dark:bg-green-950/20 dark:text-green-400`;
                    }

                    statusLabel.textContent = 'Saved ✓';
                    statusLabel.className = `status-${studentId} text-[10px] font-bold px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-400 opacity-100`;
                    setTimeout(() => { 
                        statusLabel.classList.replace('opacity-100', 'opacity-0');
                    }, 2000);
                }
            })
            .catch(error => {
                console.error("Save Error:", error);
                statusLabel.textContent = 'Failed!';
                statusLabel.className = `status-${studentId} text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-950/30 text-red-700 dark:text-red-400 opacity-100`;
            });
        }
    });
</script>
@endpush