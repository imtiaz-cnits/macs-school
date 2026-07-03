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
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Generate and print formal academic marksheet reports for students</p>
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
            if(!form.session_year_id) { event.preventDefault(); showAlert('Please select Session!', 'Validation'); return; }
            if(!form.exam_id) { event.preventDefault(); showAlert('Please select Exam!', 'Validation'); return; }
            if(!form.student_identity) { event.preventDefault(); showAlert('Please enter Student ID or Roll!', 'Validation'); return; }
        ">
            @csrf
            
            <input type="hidden" name="session_year_id" :value="form.session_year_id">
            <input type="hidden" name="exam_id" :value="form.exam_id">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Session Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'session') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Session *</label>
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

                <!-- Exam Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'exam') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Exam *</label>
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

                <!-- Student Identity -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Student ID / Roll *</label>
                    <input type="text" name="student_identity" x-model="form.student_identity" placeholder="Ex: PIS-2026-01-0002" class="w-full h-11 px-4 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-250 placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>
            </div>

            <div class="flex justify-center border-t border-gray-100 dark:border-white/[0.06] pt-6">
                <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-16 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 flex items-center justify-center">
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
            activeDropdown: null,
            sessionText: 'Choose Session',
            examText: 'Choose Exam',
            
            form: {
                session_year_id: '',
                exam_id: '',
                student_identity: ''
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
            }
        };
    }
</script>
@endpush