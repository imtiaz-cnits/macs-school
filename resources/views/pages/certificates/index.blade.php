@extends('tyro-dashboard::layouts.admin')

@section('title', 'Certificate Hub')

@section('content')
<div x-data="certificateController({{ json_encode($classSections ?? []) }})" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Certificate Hub
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Generate formal testimonials, transfer certificates, and other documents</p>
        </div>
    </div>

    @if(session('error') || (isset($errors) && $errors->any()))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-xl shadow-sm flex items-center justify-between">
            <span>{{ session('error') ?? (isset($errors) ? $errors->first() : '') }}</span>
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
    @endif

    <!-- Form Card Wrapper -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        
        <!-- Search Mode Toggle -->
        <div class="flex items-center gap-3 mb-8 border-b border-gray-100 dark:border-white/[0.06] pb-5">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Student Search Method:</span>
            
            <div class="inline-flex p-1 bg-gray-100/80 dark:bg-themeDark rounded-xl border border-gray-200/60 dark:border-white/[0.06]">
                <button type="button" 
                        @click="setMode('filter')" 
                        class="px-4 py-2 text-xs font-black rounded-lg transition-all flex items-center gap-2"
                        :class="mode === 'filter' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter by Class & Roll
                </button>
                <button type="button" 
                        @click="setMode('id')" 
                        class="px-4 py-2 text-xs font-black rounded-lg transition-all flex items-center gap-2"
                        :class="mode === 'id' ? 'bg-white dark:bg-themeNavy text-themeBlue shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    Direct Student ID
                </button>
            </div>
        </div>

        <form action="{{ route('certificates.generate') }}" method="POST" target="_blank" @submit="handleSubmit($event)">
            @csrf
            
            <input type="hidden" name="student_id" :value="form.student_id">
            <input type="hidden" name="class_id" :value="form.class_id">
            <input type="hidden" name="section_id" :value="form.section_id">
            <input type="hidden" name="roll_number" :value="form.roll_number">
            <input type="hidden" name="type" :value="form.type">
            <input type="hidden" name="issue_date" :value="form.issue_date">

            <!-- Mode 1: Class & Roll Filter Grid -->
            <div x-show="mode === 'filter'" x-cloak class="mb-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    
                    <!-- Class Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'class') activeDropdown = null">
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Select Class *</label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
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

                    <!-- Dynamic Section Dropdown (Auto-appears if class has sections) -->
                    <div x-show="availableSections.length > 0" x-cloak class="relative" @click.away="if(activeDropdown === 'section') activeDropdown = null">
                        <label class="block text-[10px] font-black text-themeBlue dark:text-themeBlue uppercase tracking-widest mb-1.5 ml-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-themeBlue animate-pulse"></span>
                            Select Section *
                        </label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'section' ? null : 'section'" class="w-full h-11 px-3 bg-themeBlue/[0.03] dark:bg-themeNavy border-2 border-themeBlue/20 dark:border-themeBlue/30 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="sectionText"></span>
                            <svg class="w-4 h-4 text-themeBlue flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'section'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="sec in availableSections" :key="sec.id">
                                <button type="button" @click="selectSection(sec.id, sec.section_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.section_id == sec.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="sec.section_name"></span>
                                    <template x-if="form.section_id == sec.id">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Roll Number Input with Lookup -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Roll Number *</label>
                        <div class="relative">
                            <input type="text" 
                                   x-model="form.roll_number" 
                                   @input.debounce.400ms="findStudentByClassRoll()" 
                                   placeholder="Ex: 1, 5, 23" 
                                   class="w-full h-11 px-4 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            
                            <!-- Search / Loading indicator -->
                            <div class="absolute right-3 top-3">
                                <template x-if="loading">
                                    <svg class="animate-spin h-5 w-5 text-themeBlue" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Quick Student Selector Dropdown from Class Roster -->
                <div x-show="classStudents.length > 0" class="mt-4">
                    <div class="relative" @click.away="if(activeDropdown === 'roster') activeDropdown = null">
                        <button type="button" 
                                @click="activeDropdown = activeDropdown === 'roster' ? null : 'roster'"
                                class="text-xs font-bold text-themeBlue hover:underline flex items-center gap-1.5 py-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Or select directly from <span class="font-black" x-text="classStudents.length"></span> students in this class</span>
                            <svg class="w-3 h-3 text-themeBlue transition-transform" :class="activeDropdown === 'roster' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        
                        <div x-show="activeDropdown === 'roster'" x-cloak class="absolute z-50 w-full md:w-96 mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-2xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="st in classStudents" :key="st.id">
                                <button type="button" 
                                        @click="pickStudent(st)" 
                                        class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors"
                                        :class="form.student_id == st.student_identity ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block px-1.5 py-0.5 font-mono text-[10px] font-black rounded bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-300">Roll: <span x-text="st.roll_number"></span></span>
                                        <span class="font-bold" x-text="st.student_name"></span>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-mono" x-text="st.student_identity"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mode 2: Direct Student ID Input -->
            <div x-show="mode === 'id'" x-cloak class="mb-8">
                <div class="max-w-md">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Student ID / Identity *</label>
                    <div class="relative">
                        <input type="text" 
                               x-model="form.student_id" 
                               @input.debounce.400ms="findStudentById()"
                               placeholder="Ex: PIS-2026-01-0002" 
                               class="w-full h-11 px-4 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                        
                        <div class="absolute right-3 top-3">
                            <template x-if="loading">
                                <svg class="animate-spin h-5 w-5 text-themeBlue" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </template>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1.5 ml-1">Enter student registration code (e.g. PIS-2026-01-0002) or system ID.</p>
                </div>
            </div>

            <!-- Live Verified Student Preview Card -->
            <div x-show="selectedStudent" x-cloak x-transition class="mb-8 p-5 bg-gradient-to-r from-themeBlue/[0.04] to-themeGreen/[0.04] dark:bg-themeDark/40 rounded-2xl border-2 border-themeBlue/20 dark:border-themeBlue/30">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-themeBlue/10 text-themeBlue flex items-center justify-center font-black text-base flex-shrink-0 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black text-gray-900 dark:text-white" x-text="selectedStudent ? selectedStudent.student_name : ''"></h3>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-themeGreen/10 text-themeGreen uppercase tracking-wider">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Verified
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-gray-600 dark:text-gray-300 font-medium">
                                <span>Class: <strong class="text-gray-900 dark:text-white" x-text="selectedStudent ? selectedStudent.class_name : ''"></strong></span>
                                <template x-if="selectedStudent && selectedStudent.section_name">
                                    <span>• Section: <strong class="text-gray-900 dark:text-white" x-text="selectedStudent.section_name"></strong></span>
                                </template>
                                <span>• Roll: <strong class="text-themeBlue" x-text="selectedStudent ? selectedStudent.roll_number : ''"></strong></span>
                                <span>• ID: <strong class="font-mono text-gray-900 dark:text-white" x-text="selectedStudent ? selectedStudent.student_identity : ''"></strong></span>
                            </div>
                            <template x-if="selectedStudent && (selectedStudent.father_name || selectedStudent.mother_name)">
                                <div class="text-[11px] text-gray-400 mt-1">
                                    <span x-show="selectedStudent.father_name">Father: <span x-text="selectedStudent.father_name"></span></span>
                                    <span x-show="selectedStudent.mother_name" class="ml-2">• Mother: <span x-text="selectedStudent.mother_name"></span></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <button type="button" @click="clearSelectedStudent()" class="text-xs font-bold text-gray-400 hover:text-red-500 transition-colors self-start sm:self-center px-2 py-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/20">
                        Clear / Change
                    </button>
                </div>
            </div>

            <!-- Not Found Alert -->
            <div x-show="notFound && !loading" x-cloak class="mb-8 p-4 bg-amber-50 dark:bg-amber-950/20 border-l-4 border-amber-500 text-amber-800 dark:text-amber-300 font-semibold text-xs rounded-r-xl">
                No student record found with the specified parameters. Please verify the Class and Roll Number.
            </div>

            <!-- Document Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 pt-4 border-t border-gray-100 dark:border-white/[0.06]">
                
                <!-- Document Type Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'type') activeDropdown = null">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Document Type *</label>
                    <button type="button" @click="activeDropdown = activeDropdown === 'type' ? null : 'type'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-250 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="typeText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="activeDropdown === 'type'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectType('testimonial', 'Testimonial')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.type === 'testimonial' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>Testimonial</span>
                            <template x-if="form.type === 'testimonial'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        <button type="button" @click="selectType('tc', 'Transfer Certificate (TC)')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.type === 'tc' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>Transfer Certificate (TC)</span>
                            <template x-if="form.type === 'tc'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        <button type="button" @click="selectType('general', 'General Certificate')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.type === 'general' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span>General Certificate</span>
                            <template x-if="form.type === 'general'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Custom Date Picker Component -->
                <div class="relative" x-data="datePicker(form.issue_date)" @date-selected.window="if($event.detail) form.issue_date = $event.detail" @click.away="show = false">
                    <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Issue Date</label>
                    <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="formatDisplay(value)"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    
                    <!-- Calendar Dropdown panel -->
                    <div x-show="show" x-cloak class="absolute right-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                        <div class="flex items-center justify-between mb-2">
                            <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider" x-text="monthNames[currentMonth] + ' ' + currentYear"></span>
                            <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
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
            </div>

            <!-- TC Extra Fields -->
            <div x-show="form.type === 'tc'" x-cloak x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 bg-themeGreen/5 dark:bg-themeDark/30 p-6 rounded-3xl border border-themeGreen/10 dark:border-white/[0.04]">
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Reason for Leaving</label>
                    <input type="text" name="leaving_reason" x-model="form.leaving_reason" placeholder="Ex: Change of Residence / To admit elsewhere" class="w-full h-11 px-4 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-250 placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Last Exam Result</label>
                    <input type="text" name="last_exam_result" x-model="form.last_exam_result" placeholder="Ex: Passed with GPA-5.00" class="w-full h-11 px-4 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-250 placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>
            </div>

            <div class="flex justify-center border-t border-gray-100 dark:border-white/[0.06] pt-6">
                <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-16 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Generate Document PDF
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
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

    function certificateController(classSectionsMap = {}) {
        return {
            mode: 'filter', // 'filter' or 'id'
            activeDropdown: null,
            classSectionsMap: classSectionsMap,
            availableSections: [],
            classStudents: [],
            
            classText: 'Select Class',
            sectionText: 'Select Section',
            typeText: 'Testimonial',
            
            selectedStudent: null,
            loading: false,
            notFound: false,
            
            form: {
                student_id: '',
                class_id: '',
                section_id: '',
                roll_number: '',
                type: 'testimonial',
                issue_date: '{{ date("Y-m-d") }}',
                leaving_reason: '',
                last_exam_result: ''
            },
            
            setMode(m) {
                this.mode = m;
                this.notFound = false;
            },
            
            selectClass(id, name) {
                this.form.class_id = id;
                this.classText = name;
                this.form.section_id = '';
                this.sectionText = 'Select Section';
                this.activeDropdown = null;
                
                // Set available sections if any
                this.availableSections = this.classSectionsMap[id] || [];
                
                // Clear and re-load students
                this.selectedStudent = null;
                this.notFound = false;
                this.loadClassStudents();
                
                if (this.form.roll_number) {
                    this.findStudentByClassRoll();
                }
            },
            
            selectSection(id, name) {
                this.form.section_id = id;
                this.sectionText = name;
                this.activeDropdown = null;
                this.loadClassStudents();
                
                if (this.form.roll_number) {
                    this.findStudentByClassRoll();
                }
            },
            
            selectType(val, label) {
                this.form.type = val;
                this.typeText = label;
                this.activeDropdown = null;
            },
            
            async loadClassStudents() {
                if (!this.form.class_id) {
                    this.classStudents = [];
                    return;
                }
                try {
                    let url = `{{ route('certificates.students') }}?class_id=${this.form.class_id}`;
                    if (this.form.section_id) {
                        url += `&section_id=${this.form.section_id}`;
                    }
                    const res = await fetch(url);
                    this.classStudents = await res.json();
                } catch (e) {
                    this.classStudents = [];
                }
            },
            
            async findStudentByClassRoll() {
                if (!this.form.class_id || !this.form.roll_number) {
                    this.selectedStudent = null;
                    return;
                }
                this.loading = true;
                this.notFound = false;
                try {
                    let url = `{{ route('certificates.students') }}?class_id=${this.form.class_id}&roll_number=${encodeURIComponent(this.form.roll_number)}`;
                    if (this.form.section_id) {
                        url += `&section_id=${this.form.section_id}`;
                    }
                    const res = await fetch(url);
                    const data = await res.json();
                    if (data && data.length > 0) {
                        this.selectedStudent = data[0];
                        this.form.student_id = data[0].student_identity;
                        this.notFound = false;
                    } else {
                        this.selectedStudent = null;
                        this.form.student_id = '';
                        this.notFound = true;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },
            
            async findStudentById() {
                if (!this.form.student_id) {
                    this.selectedStudent = null;
                    return;
                }
                this.loading = true;
                this.notFound = false;
                try {
                    const url = `{{ route('certificates.students') }}?search=${encodeURIComponent(this.form.student_id)}`;
                    const res = await fetch(url);
                    const data = await res.json();
                    if (data && data.length > 0) {
                        this.selectedStudent = data[0];
                        this.form.class_id = data[0].class_id;
                        this.form.roll_number = data[0].roll_number;
                        this.notFound = false;
                    } else {
                        this.selectedStudent = null;
                        this.notFound = true;
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },
            
            pickStudent(st) {
                this.selectedStudent = st;
                this.form.student_id = st.student_identity;
                this.form.roll_number = st.roll_number;
                this.notFound = false;
                this.activeDropdown = null;
            },
            
            clearSelectedStudent() {
                this.selectedStudent = null;
                this.form.student_id = '';
                this.form.roll_number = '';
                this.notFound = false;
            },
            
            async handleSubmit(e) {
                if (this.mode === 'filter') {
                    if (!this.form.class_id) {
                        e.preventDefault();
                        await showAlert('Please select a Class!', 'Validation Required');
                        return;
                    }
                    if (!this.form.roll_number && !this.form.student_id) {
                        e.preventDefault();
                        await showAlert('Please enter a Roll Number or pick a student!', 'Validation Required');
                        return;
                    }
                } else {
                    if (!this.form.student_id) {
                        e.preventDefault();
                        await showAlert('Please enter Student ID / Identity!', 'Validation Required');
                        return;
                    }
                }
                
                if (!this.form.type) {
                    e.preventDefault();
                    await showAlert('Please select Document Type!', 'Validation Required');
                    return;
                }
            }
        };
    }
</script>
@endpush