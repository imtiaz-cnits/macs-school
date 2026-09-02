@extends('tyro-dashboard::layouts.admin')

@section('title', 'Exam Subject Setup')

@push('styles')
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
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Configure universal subject marks distribution and grading blueprint for all examinations</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-lg shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-950/20 border-l-4 border-themeGreen text-green-700 dark:text-green-400 font-bold rounded-r-lg shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8">
        <form action="{{ route('exam-schedules.store') }}" method="POST" @submit="
            if(!form.branch_id) { event.preventDefault(); showAlert('Please select Branch!', 'Validation'); return; }
            if(!form.class_id) { event.preventDefault(); showAlert('Please select Class!', 'Validation'); return; }
            if(!form.subject_id) { event.preventDefault(); showAlert('Please select Subject!', 'Validation'); return; }
        ">
            @csrf
            
            <input type="hidden" name="branch_id" :value="form.branch_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="subject_id" :value="form.subject_id">

            <h4 class="text-xs font-black text-themeBlue uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-6">Basic Selection</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Branch Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'branch') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Branch *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="branchText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
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

                <!-- Class Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Class *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="classText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
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
                    <button type="button" @click="activeDropdown = activeDropdown === 'subject' ? null : 'subject'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="subjectText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'subject'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <template x-for="subject in filteredSubjects" :key="subject.id">
                            <button type="button" @click="selectSubject(subject.id, subject.subject_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.subject_id == subject.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span x-text="subject.subject_name"></span>
                                <template x-if="form.subject_id == subject.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>

                        <!-- ক্লাস সিলেক্ট না করলে বা সাবজেক্ট না থাকলে এই মেসেজ দেখাবে -->
                        <template x-if="filteredSubjects.length === 0">
                            <div class="px-4 py-3 text-xs font-medium text-gray-400 dark:text-gray-500 text-center">
                                Please select a class first
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Marks Distribution Section (Dynamic switching based on Class) -->
            <div class="flex justify-between items-center border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-6">
                <h4 class="text-xs font-black text-themeBlue uppercase tracking-widest">
                    Marks Distribution
                </h4>
                <div class="text-[11px] font-bold px-3 py-1 rounded-lg border" :class="isClassTen ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/30 dark:text-purple-300' : 'bg-green-50 text-themeGreen border-green-200 dark:bg-green-950/30 dark:text-green-400'">
                    Pattern: <span x-text="isClassTen ? 'Class Ten (MCQ | Written | Practical)' : 'Nursery - Nine (CT | MT | Terminal)'"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6 mb-8">
                <!-- Full Marks -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Full Marks *</label>
                    <input type="number" step="0.01" name="full_marks" x-model="form.full_marks" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>
                
                <!-- Pass Marks -->
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Pass Marks *</label>
                    <input type="number" step="0.01" name="pass_marks" x-model="form.pass_marks" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>
                
                <!-- Dynamic Field 1: CT (Nursery-9) vs MCQ (Ten) -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-1.5 ml-1" :class="isClassTen ? 'text-purple-600 dark:text-purple-400' : 'text-themeGreen'" x-text="isClassTen ? 'MCQ Marks' : 'CT Marks (Class Test)'"></label>
                    <input type="number" step="0.01" :name="isClassTen ? 'mcq_marks' : 'ct_marks'" :value="isClassTen ? form.mcq_marks : form.ct_marks" @input="if(isClassTen) form.mcq_marks = $event.target.value; else form.ct_marks = $event.target.value;" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" placeholder="0">
                </div>
                
                <!-- Dynamic Field 2: MT (Nursery-9) vs Written (Ten) -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-1.5 ml-1" :class="isClassTen ? 'text-themeBlue' : 'text-amber-500'" x-text="isClassTen ? 'Written Marks (CQ)' : 'MT Marks (Monthly Test)'"></label>
                    <input type="number" step="0.01" :name="isClassTen ? 'written_marks' : 'mcq_marks'" :value="isClassTen ? form.written_marks : form.mcq_marks" @input="if(isClassTen) form.written_marks = $event.target.value; else form.mcq_marks = $event.target.value;" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" placeholder="0">
                </div>
                
                <!-- Dynamic Field 3: Terminal (Nursery-9) vs Practical (Ten) -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-1.5 ml-1" :class="isClassTen ? 'text-teal-600 dark:text-teal-400' : 'text-themeBlue'" x-text="isClassTen ? 'Practical Marks' : 'Terminal Exam Marks'"></label>
                    <input type="number" step="0.01" :name="isClassTen ? 'ct_marks' : 'written_marks'" :value="isClassTen ? form.ct_marks : form.written_marks" @input="if(isClassTen) form.ct_marks = $event.target.value; else form.written_marks = $event.target.value;" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" placeholder="0">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-white/[0.06]">
                <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95">
                    Save Subject Setup
                </button>
            </div>
        </form>
    </div>

    <!-- List Section -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm">
        <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Configured Subjects List</h3>
        
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-16 text-center">#</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Class & Branch</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Subject</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Marks (Full / Pass)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Mark Distribution Breakdown</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-24">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                    @forelse($schedules as $index => $schedule)
                    @php
                        $isTen = $schedule->classes && strtolower($schedule->classes->class_name) === 'ten';
                    @endphp
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                        <td class="py-0 px-0 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">{{ $index + 1 }}</td>
                        <td class="py-0 px-0">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">Class: {{ $schedule->classes->class_name ?? 'N/A' }}</div>
                            @if($schedule->branch)
                                <div class="text-[10px] font-bold text-themeBlue mt-0.5 uppercase tracking-wider">{{ $schedule->branch->branch_name ?? $schedule->branch->name }}</div>
                            @endif
                        </td>
                        <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ $schedule->subject->subject_name ?? 'N/A' }}
                        </td>
                        <td class="py-0 px-0 text-center">
                            <span class="inline-block px-2.5 py-1 text-[10px] font-bold text-themeGreen bg-themeGreen/10 rounded-lg">{{ $schedule->full_marks }}</span>
                            <span class="text-gray-400 mx-1">/</span>
                            <span class="inline-block px-2.5 py-1 text-[10px] font-bold text-red-650 bg-red-100 dark:bg-red-950/20 dark:text-red-400 rounded-lg">{{ $schedule->pass_marks }}</span>
                        </td>
                        <td class="py-0 px-0 text-center text-xs font-semibold">
                            @if($isTen)
                                <div class="inline-flex items-center gap-1.5 bg-purple-50 dark:bg-purple-950/20 px-3 py-1.5 rounded-xl border border-purple-100 dark:border-purple-900/30">
                                    <span class="text-purple-700 dark:text-purple-300 font-bold">MCQ: <span class="font-black">{{ $schedule->mcq_marks ?? 0 }}</span></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-themeBlue font-bold">Written: <span class="font-black">{{ $schedule->written_marks ?? 0 }}</span></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-teal-600 dark:text-teal-400 font-bold">Practical: <span class="font-black">{{ $schedule->ct_marks ?? 0 }}</span></span>
                                </div>
                            @else
                                <div class="inline-flex items-center gap-1.5 bg-gray-50 dark:bg-themeDark px-3 py-1.5 rounded-xl border border-gray-100 dark:border-gray-800">
                                    <span class="text-themeGreen font-bold">CT: <span class="font-black">{{ $schedule->ct_marks ?? 0 }}</span></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-amber-600 dark:text-amber-400 font-bold">MT: <span class="font-black">{{ $schedule->mcq_marks ?? 0 }}</span></span>
                                    <span class="text-themeBlue font-bold">Terminal: <span class="font-black">{{ $schedule->written_marks ?? 0 }}</span></span>
                                </div>
                            @endif
                        </td>
                        <td class="py-0 px-0 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('exam-schedules.destroy', $schedule->id) }}" method="POST" @submit.prevent="if (await showDanger('Delete Setup', 'Are you sure you want to delete this subject mark setup? This action cannot be undone.')) $el.submit()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Setup">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No configured subjects found. Set up one above!</td>
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
    function examSubjectSetup() {
        return {
            activeDropdown: null,
            branchText: 'Choose Branch',
            classText: 'Choose Class',
            subjectText: 'Choose Subject',
            
            form: {
                branch_id: '',
                class_id: '',
                subject_id: '',
                full_marks: '100',
                pass_marks: '33',
                ct_marks: '10',      // CT (Nursery-9) or Practical (Ten)
                written_marks: '70', // Terminal (Nursery-9) or Written (Ten)
                mcq_marks: '20'      // MT (Nursery-9) or MCQ (Ten)
            },

            allSubjects: @json($subjects),

            get isClassTen() {
                return this.classText.trim().toLowerCase() === 'ten' || this.classText.trim().toLowerCase() === 'class ten';
            },

            get filteredSubjects() {
                if (!this.form.class_id) return [];
                return this.allSubjects.filter(subject => subject.class_id == this.form.class_id);
            },
            
            selectBranch(id, name) {
                this.form.branch_id = id;
                this.branchText = name;
                this.activeDropdown = null;
            },
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.activeDropdown = null;
                this.form.subject_id = '';
                this.subjectText = 'Choose Subject';

                // Automatically adjust defaults based on Class
                if (name.trim().toLowerCase() === 'ten') {
                    this.form.mcq_marks = '30';
                    this.form.written_marks = '70';
                    this.form.ct_marks = '0'; // Practical
                } else {
                    this.form.ct_marks = '10';      // CT
                    this.form.mcq_marks = '20';     // MT
                    this.form.written_marks = '70'; // Terminal
                }
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