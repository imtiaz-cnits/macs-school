@extends('tyro-dashboard::layouts.admin')

@section('title', 'Student Profile')

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
                        555: '#64748b',
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
<a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 text-sm font-extrabold text-themeBlue bg-themeBlue/5 dark:bg-themeBlue/10 hover:bg-themeBlue/10 dark:hover:bg-themeBlue/15 px-3.5 py-1.5 rounded-xl border border-themeBlue/25 transition-all hover:scale-105 active:scale-95 shadow-sm shadow-themeBlue/5">
    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    Back to Student List
</a>
@endsection

@section('content')
<div class="w-full min-h-screen relative text-gray-900 dark:text-gray-100">
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-white/70 dark:bg-themeDark/70 z-50 flex items-center justify-center backdrop-blur-md transition-all duration-300">
        <div class="text-center p-8 bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-xl">
            <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-themeBlue rounded-full animate-spin mb-4"></div>
            <p class="text-xs font-black text-themeBlue dark:text-indigo-400 uppercase tracking-widest">Loading Record...</p>
        </div>
    </div>

    <!-- Page Header (Tile Case, Preceded with Icon) -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Student Profile & Academic Summary
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-450 mt-1">Pabna International School</p>
        </div>
        
        <div class="flex gap-3 w-full md:w-auto">
            <button onclick="pushToDevice()" class="h-10 px-5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 border border-indigo-200 dark:border-indigo-900/60 shadow-sm whitespace-nowrap active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                Push to Machine
            </button>

            <a href="/student/edit/{{ $id }}" class="h-10 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                Edit Profile
            </a>
        </div>
    </div>

    <!-- Main Grid: Left side (Compact Personal info), Right side (Academic dynamic summaries) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- ================= LEFT COLUMN: COMPACT STUDENT INFO ================= -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Compact Avatar Card -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-themeBlue/[0.02] rounded-full -mr-16 -mt-16 pointer-events-none"></div>
                
                <div class="flex items-center gap-4 relative z-10">
                    <div class="relative shrink-0">
                        <img id="profile_photo" src="" alt="Student" class="w-20 h-20 rounded-2xl object-cover border-2 border-gray-100 dark:border-themeDark shadow-sm bg-gray-50">
                        <div class="absolute -bottom-1 -right-1 px-2 py-0.5 bg-themeGreen text-white text-[8px] font-black rounded-full border border-white dark:border-themeNavy uppercase tracking-wider">Active</div>
                    </div>
                    <div class="min-w-0">
                        <h2 id="view_student_name" class="text-lg font-black text-gray-900 dark:text-white truncate">Student Name</h2>
                        <p id="view_name_in_bangla" class="text-xs text-themeBlue dark:text-indigo-400 font-bold truncate mt-0.5">Name in Bangla</p>
                        
                        <div class="mt-2.5 flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-indigo-50/70 dark:bg-themeBlue/10 border border-indigo-100/50 dark:border-white/[0.06] rounded-lg text-[9px] font-black text-themeBlue font-mono uppercase tracking-wider" id="badge_identity">PIS-0000</span>
                            <span class="px-2 py-1 bg-gray-50 dark:bg-themeDark border border-gray-150 dark:border-white/[0.06] rounded-lg text-[9px] font-bold text-gray-700 dark:text-gray-300">
                                Class <span id="badge_class" class="font-black text-themeBlue">...</span> • Roll <span id="badge_roll" class="font-black text-themeGreen">...</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Academic & Personal info combined -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="flex items-center gap-2 text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-4">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm-1.2 6.375a3.375 3.375 0 00-5.1 0v.125h5.1v-.125z" /></svg>
                    Student Details
                </h3>
                
                <div class="grid grid-cols-2 gap-x-4 gap-y-3.5">
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Branch</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200" id="view_branch">...</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Session</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200" id="view_session">...</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Section</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200" id="view_section">...</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Shift</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200" id="view_shift">...</span>
                    </div>
                    
                    <div class="col-span-2 h-[1px] bg-gray-100 dark:bg-white/[0.05] my-1"></div>
                    
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Date of Birth</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200" id="view_dob">...</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Gender</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200" id="view_gender">...</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Blood Group</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono" id="view_blood_group">...</span>
                    </div>
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Religion</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200" id="view_religion">...</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 block">Birth Certificate No</span>
                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono" id="view_birth_certificate">...</span>
                    </div>
                    <div class="col-span-2">
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-0.5 block">RFID Card Number</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 font-mono" id="view_card_number">...</span>
                            <button type="button" onclick="syncRfidCardSingle({{ $id }})" class="p-1.5 bg-themeBlue/10 hover:bg-themeBlue/20 text-themeBlue rounded-lg transition-colors" title="Sync Card from Machine">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-span-2 h-[1px] bg-gray-100 dark:bg-white/[0.05] my-1"></div>
                    
                    <div class="col-span-2">
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 block">Uploaded Document</span>
                        <div id="document_wrapper"></div>
                    </div>
                </div>
            </div>

            <!-- Family & Guardian Card -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="flex items-center gap-2 text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-4">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    Family Details
                </h3>
                
                <div class="space-y-4">
                    <!-- Father's Info -->
                    <div class="p-3 bg-blue-50/15 dark:bg-themeBlue/[0.03] border border-themeBlue/10 rounded-2xl">
                        <span class="text-[9px] font-black text-themeBlue uppercase tracking-widest block mb-2">Father's Info</span>
                        <div class="grid grid-cols-2 gap-x-2 gap-y-2 text-[11px]">
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Name</span><span class="font-bold truncate block" id="view_father_name">...</span></div>
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Name (Bangla)</span><span class="font-bold truncate block" id="view_father_name_bn">...</span></div>
                            <div><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Occupation</span><span class="font-bold truncate block" id="view_father_occupation">...</span></div>
                            <div><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Mobile</span><span class="font-bold block" id="view_father_mobile">...</span></div>
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">NID</span><span class="font-mono block" id="view_father_nid">...</span></div>
                        </div>
                    </div>
                    
                    <!-- Mother's Info -->
                    <div class="p-3 bg-green-50/15 dark:bg-themeGreen/[0.03] border border-themeGreen/10 rounded-2xl">
                        <span class="text-[9px] font-black text-themeGreen uppercase tracking-widest block mb-2">Mother's Info</span>
                        <div class="grid grid-cols-2 gap-x-2 gap-y-2 text-[11px]">
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Name</span><span class="font-bold truncate block" id="view_mother_name">...</span></div>
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Name (Bangla)</span><span class="font-bold truncate block" id="view_mother_name_bn">...</span></div>
                            <div><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Occupation</span><span class="font-bold truncate block" id="view_mother_occupation">...</span></div>
                            <div><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Mobile</span><span class="font-bold block" id="view_mother_mobile">...</span></div>
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">NID</span><span class="font-mono block" id="view_mother_nid">...</span></div>
                        </div>
                    </div>
                    
                    <!-- Guardian Info -->
                    <div class="p-3 bg-gray-50/50 dark:bg-themeDark/45 border border-gray-150 dark:border-white/[0.04] rounded-2xl">
                        <span class="text-[9px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest block mb-2">Local Guardian</span>
                        <div class="grid grid-cols-2 gap-x-2 gap-y-2 text-[11px]">
                            <div><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Name</span><span class="font-bold truncate block" id="view_guardian_name">...</span></div>
                            <div><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Occupation</span><span class="font-bold truncate block" id="view_guardian_occupation">...</span></div>
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Mobile</span><span class="font-bold text-red-550 block font-mono" id="view_guardian_mobile">...</span></div>
                            <div class="col-span-2"><span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Email</span><span class="font-medium text-themeBlue block lowercase truncate" id="view_email">...</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Addresses -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="flex items-center gap-2 text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-4">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                    Residential Address
                </h3>
                
                <div class="space-y-3.5 text-xs">
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest block mb-0.5">Present Address</span>
                        <p class="font-semibold italic text-gray-655 dark:text-gray-400 leading-relaxed" id="view_present_address">...</p>
                    </div>
                    <div class="h-[1px] bg-gray-100 dark:bg-white/[0.05]"></div>
                    <div>
                        <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest block mb-0.5">Permanent Address</span>
                        <p class="font-semibold italic text-gray-655 dark:text-gray-400 leading-relaxed" id="view_permanent_address">...</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================= RIGHT COLUMN: DYNAMIC SUMMARY MODULES ================= -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Dashboard Quick Statistics Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Circular Attendance Widget -->
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm md:col-span-2 flex flex-col md:flex-row items-center gap-6">
                    <div class="shrink-0">
                        <div class="relative w-28 h-28 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                <circle class="text-gray-100 dark:text-gray-800" stroke-width="8" stroke="currentColor" fill="transparent" r="38" cx="50" cy="50"/>
                                <circle id="attendance_circle" class="text-themeBlue transition-all duration-500" stroke-width="8" stroke-dasharray="238.76" stroke-dashoffset="238.76" stroke-linecap="round" stroke="currentColor" fill="transparent" r="38" cx="50" cy="50"/>
                            </svg>
                            <div class="absolute text-center">
                                <span class="text-xl font-black text-gray-900 dark:text-white" id="attendance_percentage_val">100%</span>
                                <span class="block text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-0.5">Rate</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex-1 w-full">
                        <h4 class="text-xs font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.05] pb-2 mb-3">Attendance Stats</h4>
                        <div class="grid grid-cols-3 gap-4 text-center md:text-left">
                            <div>
                                <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Present</span>
                                <span class="text-base font-black text-themeGreen" id="attendance_present_days">0</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Absent</span>
                                <span class="text-base font-black text-red-500" id="attendance_absent_days">0</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block">Late</span>
                                <span class="text-base font-black text-amber-500" id="attendance_late_days">0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Evaluation / Progress Summary -->
                <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm flex flex-col justify-between">
                    <div>
                        <h4 class="text-xs font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.05] pb-2 mb-3">Academic Index</h4>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-themeGreen" id="gpa_aggregate">0.00</span>
                            <span class="text-[10px] font-black text-gray-450 uppercase tracking-wider">Average GPA</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-themeBlue to-themeGreen h-full rounded-full transition-all duration-500" id="gpa_bar" style="width: 0%"></div>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest" id="evaluation_label">Needs Review</span>
                            <span class="text-[9px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider font-mono">Max: 5.0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exam Marks & Results History Table -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="flex items-center gap-2 text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-2">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Exam Marks & Results History
                </h3>
                
                <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Exam</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Subject</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Marks</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Grade</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">GPA</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="results_table_body">
                            <!-- Pulsing Loading Row Skeletons -->
                            <tr class="animate-pulse">
                                <td class="!py-3.5 !px-3"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="!py-3.5 !px-3"><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="!py-3.5 !px-3 text-center"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                <td class="!py-3.5 !px-3 text-center"><div class="h-4 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                <td class="!py-3.5 !px-3 text-center"><div class="h-4 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                <td class="!py-3.5 !px-3 text-center"><div class="h-4 w-14 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>            <!-- Fees & Invoices History Table -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="flex items-center gap-2 text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-2">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-1.958-.59a2.502 2.502 0 010-3.236c1.117-.837 2.8-.837 3.917 0l.44.33M12 3v3m0 12v3" /></svg>
                    Fees & Invoices History
                </h3>

                <!-- Summary Panel inside Fees Card -->
                <div class="grid grid-cols-3 gap-4 mb-4 mt-2">
                    <div class="p-3 bg-blue-50/15 dark:bg-themeBlue/[0.03] border border-themeBlue/10 rounded-2xl text-center">
                        <span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest block mb-0.5">Total Billed</span>
                        <span class="text-sm font-black text-gray-900 dark:text-white font-mono" id="summary_total_billed">৳0.00</span>
                    </div>
                    <div class="p-3 bg-green-50/15 dark:bg-themeGreen/[0.03] border border-themeGreen/10 rounded-2xl text-center">
                        <span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest block mb-0.5">Total Paid</span>
                        <span class="text-sm font-black text-themeGreen font-mono" id="summary_total_paid">৳0.00</span>
                    </div>
                    <div class="p-3 bg-red-50/15 dark:bg-red-500/[0.03] border border-red-500/10 rounded-2xl text-center">
                        <span class="text-[8px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest block mb-0.5">Total Due</span>
                        <span class="text-sm font-black text-red-500 font-mono" id="summary_total_due">৳0.00</span>
                    </div>
                </div>
                
                <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Invoice No</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Fee Type / Month</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Net Amt</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Paid</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Due</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="fees_table_body">
                            <!-- Pulsing Loading Row Skeletons -->
                            <tr class="animate-pulse">
                                <td class="!py-3.5 !px-3"><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="!py-3.5 !px-3"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="!py-3.5 !px-3 text-right"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                                <td class="!py-3.5 !px-3 text-right"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                                <td class="!py-3.5 !px-3 text-right"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md ml-auto"></div></td>
                                <td class="!py-3.5 !px-3 text-center"><div class="h-4 w-14 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div id="fees_pagination_container" class="flex flex-col sm:flex-row items-center justify-between mt-4 gap-3 px-1 no-print hidden">
                    <span class="text-xs font-semibold text-gray-555 dark:text-gray-400" id="fees_pagination_info">Showing 1 to 10 of 12 entries</span>
                    <div class="flex gap-2">
                        <button type="button" id="fees_prev_btn" class="px-3.5 py-1.5 bg-gray-55/70 dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] hover:bg-gray-100 dark:hover:bg-themeDark/45 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">Prev</button>
                        <button type="button" id="fees_next_btn" class="px-3.5 py-1.5 bg-gray-55/70 dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] hover:bg-gray-100 dark:hover:bg-themeDark/45 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                    </div>
                </div>
            </div>

            <!-- Student Customized Fees Card -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex justify-between items-center border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-2">
                    <h3 class="flex items-center gap-2 text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider">
                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122A3 3 0 0010.5 21.75h2.9m3.07-5.628a3 3 0 01-1.07 5.628H12.5m2.77-10.874A3 3 0 0012 3m0 0a3 3 0 00-3.27 2.25M12 3v18.75m0-18.75a3 3 0 005.27 2.25M12 21.75a3 3 0 01-3.07-5.628M12 21.75a3 3 0 00-2.9-5.628" /></svg>
                        Customized Fees Configuration
                    </h3>
                    <button type="button" onclick="openCustomFeeModal()" class="h-8 px-3 bg-themeBlue hover:bg-themeBlue/90 text-white text-[10px] font-black rounded-xl uppercase tracking-widest transition-all shadow-sm flex items-center justify-center gap-1 active:scale-95">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Set Custom Fee
                    </button>
                </div>

                <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Fee Category</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right">Custom Amount</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2.5 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="custom_fees_table_body">
                            <tr>
                                <td colspan="3" class="text-center py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">No customized fees configured</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Subject-Wise Evaluation Stats -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="flex items-center gap-2 text-xs font-black text-gray-800 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-4">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" /></svg>
                    Subject Performance Distribution
                </h3>
                
                <div class="space-y-4" id="subjects_distribution_container">
                    <div class="animate-pulse space-y-3">
                        <div class="h-4 w-full bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                        <div class="h-4 w-4/5 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Customized Fee Modal -->
    <div id="customFeeModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-md hidden no-print">
        <div x-data="{ 
            open: false, 
            selectedValue: '', 
            selectedLabel: 'Choose Category', 
            options: [],
            init() {
                window.addEventListener('populate-categories', (e) => {
                    this.options = e.detail;
                });
                window.addEventListener('reset-category-dropdown', () => {
                    this.selectedValue = '';
                    this.selectedLabel = 'Choose Category';
                    document.getElementById('custom_fee_category_id').value = '';
                });
            },
            select(opt) {
                this.selectedValue = opt.id;
                this.selectedLabel = opt.name;
                this.open = false;
                document.getElementById('custom_fee_category_id').value = opt.id;
            }
        }" class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.08] p-6 w-full max-w-md shadow-2xl relative mx-4">
            <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-4">Set Customized Fee</h3>
            
            <div class="space-y-4">
                <div class="relative">
                    <label class="text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1.5 block">Fee Category</label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-4 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span x-text="selectedLabel">Choose Category</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" id="custom_fee_category_id" value="">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-[9999] w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="opt in options" :key="opt.id">
                            <button type="button" @click="select(opt)" :class="selectedValue == opt.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="opt.name"></span>
                                <template x-if="selectedValue == opt.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-1.5 block">Customized Monthly Fee (৳)</label>
                    <input type="number" id="custom_fee_amount" placeholder="e.g. 800" min="0" step="any" class="w-full h-11 px-4 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-gray-800 dark:text-white">
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 border-t border-gray-100 dark:border-white/[0.06] pt-4">
                <button type="button" onclick="closeCustomFeeModal()" class="h-10 px-5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                <button type="button" onclick="saveCustomFee()" class="h-10 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest active:scale-95">Save Fee</button>
            </div>
        </div>
    </div>

    <!-- Developer Footer -->
    <div class="mt-12 text-center border-t border-gray-100 dark:border-gray-800/80 pt-6 pb-4">
        <p class="text-[10px] font-black text-gray-450 dark:text-gray-555 uppercase tracking-[0.3em]">
            Developed by <a href="https://www.codenextit.com" target="_blank" class="text-themeGreen font-black hover:underline">Code Next IT</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const studentId = "{{ $id }}";

    const getAuthHeaders = () => ({ 
        headers: { 
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        } 
    });

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            let res = await axios.get(`/ajax/students/${studentId}`, getAuthHeaders());
            
            // Extract the dynamic datasets added to controller response
            let s = res.data.data;
            let attendance = res.data.attendance || { total: 0, present: 0, absent: 0, late: 0, percentage: 100 };
            let marks = res.data.marks || [];

            // Profile Photo Path
            let finalPhotoUrl = '/img/default-student.png'; 
            if (s.photo) {
                if (s.photo.startsWith('img/')) {
                    finalPhotoUrl = '/' + s.photo;
                } else {
                    finalPhotoUrl = '/storage/' + s.photo;
                }
            }
            document.getElementById('profile_photo').src = finalPhotoUrl;

            // Student header info
            document.getElementById('view_student_name').innerText = s.student_name || 'N/A';
            document.getElementById('view_name_in_bangla').innerText = s.name_in_bangla || '';
            document.getElementById('badge_identity').innerText = s.student_identity || 'PIS-0000-00-0000';
            document.getElementById('badge_class').innerText = s.school_class ? s.school_class.class_name : 'N/A';
            document.getElementById('badge_roll').innerText = s.roll_number || 'N/A';

            // Academic info
            document.getElementById('view_branch').innerText = s.branch ? s.branch.branch_name : 'N/A';
            document.getElementById('view_session').innerText = s.session_year ? s.session_year.session_name : 'N/A';
            document.getElementById('view_section').innerText = s.section ? s.section.section_name : 'N/A';
            document.getElementById('view_shift').innerText = s.shift ? s.shift.shift_name : 'N/A';

            // Personal info
            document.getElementById('view_dob').innerText = s.dob || 'N/A';
            document.getElementById('view_gender').innerText = s.gender || 'N/A';
            document.getElementById('view_blood_group').innerText = s.blood_group || 'N/A';
            document.getElementById('view_religion').innerText = s.religion || 'N/A';
            document.getElementById('view_birth_certificate').innerText = s.birth_certificate || 'N/A';
            document.getElementById('view_card_number').innerText = s.card_number || 'N/A';

            // Document file attachment handler
            let docWrapper = document.getElementById('document_wrapper');
            if (s.document_file) {
                docWrapper.innerHTML = `
                    <a href="/storage/${s.document_file}" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue dark:text-indigo-400 font-bold text-[10px] rounded-xl hover:bg-indigo-100 dark:hover:bg-themeBlue/20 transition border border-indigo-100 dark:border-white/[0.05] uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        View Attachment
                    </a>
                `;
            } else {
                docWrapper.innerHTML = `<span class="text-[9px] font-black text-gray-400 dark:text-gray-500 bg-gray-50/50 dark:bg-themeDark px-3 py-1.5 rounded-lg border border-gray-150 dark:border-white/[0.04] inline-block uppercase tracking-wider">No Document</span>`;
            }

            // Parents and Guardians
            document.getElementById('view_father_name').innerText = s.father_name || 'N/A';
            document.getElementById('view_father_name_bn').innerText = s.father_name_bn || 'N/A';
            document.getElementById('view_father_occupation').innerText = s.father_occupation || 'N/A';
            document.getElementById('view_father_mobile').innerText = s.father_mobile || 'N/A';
            document.getElementById('view_father_nid').innerText = s.father_nid || 'N/A';
            
            document.getElementById('view_mother_name').innerText = s.mother_name || 'N/A';
            document.getElementById('view_mother_name_bn').innerText = s.mother_name_bn || 'N/A';
            document.getElementById('view_mother_occupation').innerText = s.mother_occupation || 'N/A';
            document.getElementById('view_mother_mobile').innerText = s.mother_mobile || 'N/A';
            document.getElementById('view_mother_nid').innerText = s.mother_nid || 'N/A';
            
            document.getElementById('view_guardian_name').innerText = s.guardian_name || 'N/A';
            document.getElementById('view_guardian_occupation').innerText = s.guardian_occupation || 'N/A';
            document.getElementById('view_guardian_mobile').innerText = s.guardian_mobile || 'N/A';
            document.getElementById('view_email').innerText = s.email || 'N/A';

            // Addresses
            const formatAddress = (village, postOffice, postCode, district, division) => {
                let parts = [];
                if (village) parts.push(village);
                if (postOffice) parts.push(`PO: ${postOffice}`);
                if (postCode) parts.push(`Code: ${postCode}`);
                if (district) parts.push(`Dist: ${district}`);
                if (division) parts.push(`Div: ${division}`);
                return parts.length > 0 ? parts.join(', ') : 'N/A';
            };

            document.getElementById('view_present_address').innerText = formatAddress(
                s.present_village, s.present_post_office, s.present_post_code, s.present_district, s.present_division
            );
            document.getElementById('view_permanent_address').innerText = formatAddress(
                s.permanent_village, s.permanent_post_office, s.permanent_post_code, s.permanent_district, s.permanent_division
            );

            // ==========================================
            // DYNAMIC ATTENDANCE PROCESSING
            // ==========================================
            document.getElementById('attendance_percentage_val').innerText = attendance.percentage + '%';
            document.getElementById('attendance_present_days').innerText = attendance.present;
            document.getElementById('attendance_absent_days').innerText = attendance.absent;
            document.getElementById('attendance_late_days').innerText = attendance.late;

            // Animate Circle Ring
            const circumference = 238.76;
            const offset = circumference - (attendance.percentage / 100) * circumference;
            document.getElementById('attendance_circle').setAttribute('stroke-dashoffset', offset);

            // ==========================================
            // DYNAMIC FEES & INVOICES PROCESSING
            // ==========================================
            let invoices = res.data.invoices || [];
            let feesBody = document.getElementById('fees_table_body');
            
            let totalBilled = 0;
            let totalPaid = 0;
            let totalDue = 0;

            // Calculate totals across all invoices
            invoices.forEach(inv => {
                let netAmt = inv.net_amount !== null ? parseFloat(inv.net_amount) : 0;
                let paidAmt = inv.paid_amount !== null ? parseFloat(inv.paid_amount) : 0;
                let dueAmt = inv.due_amount !== null ? parseFloat(inv.due_amount) : 0;
                totalBilled += netAmt;
                totalPaid += paidAmt;
                totalDue += dueAmt;
            });

            document.getElementById('summary_total_billed').innerText = '৳' + totalBilled.toFixed(2);
            document.getElementById('summary_total_paid').innerText = '৳' + totalPaid.toFixed(2);
            document.getElementById('summary_total_due').innerText = '৳' + totalDue.toFixed(2);

            // Client-side pagination state
            let feesCurrentPage = 1;
            const feesItemsPerPage = 10;

            function renderFeesPage(page) {
                feesBody.innerHTML = '';

                if (invoices.length > 0) {
                    let start = (page - 1) * feesItemsPerPage;
                    let end = Math.min(start + feesItemsPerPage, invoices.length);
                    let pageItems = invoices.slice(start, end);

                    pageItems.forEach(inv => {
                        let invoiceNo = inv.invoice_no || 'N/A';
                        let categoryName = inv.fee_setup && inv.fee_setup.category ? inv.fee_setup.category.name : 'Fee Payment';
                        let feeMonth = '';
                        if (inv.due_date) {
                            try {
                                const parts = inv.due_date.split('-');
                                if (parts.length === 3) {
                                    const monthIndex = parseInt(parts[1], 10) - 1;
                                    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                                    if (monthIndex >= 0 && monthIndex < 12) {
                                        feeMonth = ` (${monthNames[monthIndex]})`;
                                    }
                                }
                            } catch (e) {
                                console.error(e);
                            }
                        }
                        if (!feeMonth && inv.fee_setup && inv.fee_setup.fee_month) {
                            feeMonth = ` (${inv.fee_setup.fee_month})`;
                        }
                        let feeDetails = categoryName + feeMonth;

                        let netAmt = inv.net_amount !== null ? parseFloat(inv.net_amount) : 0;
                        let paidAmt = inv.paid_amount !== null ? parseFloat(inv.paid_amount) : 0;
                        let dueAmt = inv.due_amount !== null ? parseFloat(inv.due_amount) : 0;
                        
                        let status = inv.status || 'Unpaid';
                        let statusClass = 'bg-red-100 text-red-600 dark:bg-red-950/30 dark:text-red-500';
                        if (status === 'Paid') {
                            statusClass = 'bg-green-100 text-themeGreen dark:bg-green-950/30 dark:text-green-500';
                        } else if (status === 'Partially Paid') {
                            statusClass = 'bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-500';
                        }

                        feesBody.innerHTML += `
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                <td class="py-2.5 px-3 text-sm font-mono font-black text-gray-900 dark:text-gray-100">${invoiceNo}</td>
                                <td class="py-2.5 px-3 text-sm font-bold text-gray-600 dark:text-gray-400">${feeDetails}</td>
                                <td class="py-2.5 px-3 text-sm font-bold text-gray-900 dark:text-gray-100 text-right font-mono">৳${netAmt.toFixed(2)}</td>
                                <td class="py-2.5 px-3 text-sm font-bold text-themeGreen text-right font-mono">৳${paidAmt.toFixed(2)}</td>
                                <td class="py-2.5 px-3 text-sm font-bold text-red-500 text-right font-mono">৳${dueAmt.toFixed(2)}</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg ${statusClass}">${status}</span>
                                </td>
                            </tr>
                        `;
                    });

                    // Update controls
                    if (invoices.length > feesItemsPerPage) {
                        document.getElementById('fees_pagination_container').classList.remove('hidden');
                        document.getElementById('fees_pagination_info').innerText = `Showing ${start + 1} to ${end} of ${invoices.length} entries`;
                        document.getElementById('fees_prev_btn').disabled = (page === 1);
                        document.getElementById('fees_next_btn').disabled = (end >= invoices.length);
                    } else {
                        document.getElementById('fees_pagination_container').classList.add('hidden');
                    }
                } else {
                    feesBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="py-8 text-center text-xs font-semibold text-gray-400 dark:text-gray-555">No Fee Invoices Found.</td>
                        </tr>
                    `;
                    document.getElementById('fees_pagination_container').classList.add('hidden');
                }
            }

            document.getElementById('fees_prev_btn').onclick = () => {
                if (feesCurrentPage > 1) {
                    feesCurrentPage--;
                    renderFeesPage(feesCurrentPage);
                }
            };

            document.getElementById('fees_next_btn').onclick = () => {
                if (feesCurrentPage * feesItemsPerPage < invoices.length) {
                    feesCurrentPage++;
                    renderFeesPage(feesCurrentPage);
                }
            };

            // Initial trigger
            renderFeesPage(feesCurrentPage);

            // ==========================================
            // DYNAMIC MARKS & RESULTS PROCESSING
            // ==========================================
            let resultsBody = document.getElementById('results_table_body');
            resultsBody.innerHTML = ''; // Clear skeleton

            let totalGPA = 0;
            let validGpaCount = 0;
            let subjectMarksMap = {};

            if (marks.length > 0) {
                marks.forEach(m => {
                    let examName = m.exam ? m.exam.exam_name : 'N/A';
                    let subjectName = m.subject ? m.subject.subject_name : 'N/A';
                    let totalVal = m.total_mark !== null ? parseFloat(m.total_mark) : 0;
                    let letterGrade = m.letter_grade || 'N/A';
                    let gradePoint = m.grade_point !== null ? parseFloat(m.grade_point) : 0.0;
                    let status = m.is_absent ? 'Absent' : (gradePoint > 0 ? 'Passed' : 'Failed');
                    
                    // Track subject-wise averages for distribution index
                    if (!subjectMarksMap[subjectName]) {
                        subjectMarksMap[subjectName] = [];
                    }
                    subjectMarksMap[subjectName].push(totalVal);

                    if (!m.is_absent) {
                        totalGPA += gradePoint;
                        validGpaCount++;
                    }

                    let statusClass = status === 'Passed' 
                        ? 'bg-green-100 text-themeGreen dark:bg-green-950/30 dark:text-green-500' 
                        : (status === 'Absent' ? 'bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-500' : 'bg-red-100 text-red-600 dark:bg-red-950/30 dark:text-red-500');

                    resultsBody.innerHTML += `
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <td class="py-2.5 px-3 text-sm font-bold text-gray-900 dark:text-gray-100">${examName}</td>
                            <td class="py-2.5 px-3 text-sm font-bold text-gray-600 dark:text-gray-400">${subjectName}</td>
                            <td class="py-2.5 px-3 text-sm font-bold text-gray-900 dark:text-gray-100 text-center font-mono">${totalVal.toFixed(1)}</td>
                            <td class="py-2.5 px-3 text-sm font-black text-gray-800 dark:text-gray-200 text-center font-mono">${letterGrade}</td>
                            <td class="py-2.5 px-3 text-sm font-black text-themeBlue text-center font-mono">${gradePoint.toFixed(2)}</td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg ${statusClass}">${status}</span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                resultsBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-8 text-center text-xs font-semibold text-gray-400 dark:text-gray-500">No Exam Marks Recorded Yet.</td>
                    </tr>
                `;
            }

            // Calculate & Set Average GPA
            let avgGpa = validGpaCount > 0 ? (totalGPA / validGpaCount) : 0.0;
            document.getElementById('gpa_aggregate').innerText = avgGpa.toFixed(2);
            
            // Update GPA aggregate progress line
            let gpaPercentage = (avgGpa / 5.0) * 100;
            document.getElementById('gpa_bar').style.width = gpaPercentage + '%';

            // Eval details
            let evalLabel = 'Needs Review';
            if (avgGpa >= 4.0) evalLabel = 'Excellent';
            else if (avgGpa >= 3.0) evalLabel = 'Good';
            else if (avgGpa >= 2.0) evalLabel = 'Average';
            document.getElementById('evaluation_label').innerText = evalLabel;

            // ==========================================
            // DYNAMIC SUBJECT PERFORMANCE DISTRIBUTION
            // ==========================================
            let distContainer = document.getElementById('subjects_distribution_container');
            distContainer.innerHTML = ''; // Clear skeleton

            let subjectsList = Object.keys(subjectMarksMap);
            if (subjectsList.length > 0) {
                subjectsList.forEach(subjectName => {
                    let values = subjectMarksMap[subjectName];
                    let avgMark = values.reduce((a, b) => a + b, 0) / values.length;
                    
                    // Cap at 100
                    let barWidth = Math.min(avgMark, 100);
                    let colorClass = avgMark >= 80 ? 'bg-themeGreen' : (avgMark >= 50 ? 'bg-themeBlue' : 'bg-red-500');

                    distContainer.innerHTML += `
                        <div>
                            <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                <span class="text-gray-700 dark:text-gray-300 font-bold">${subjectName}</span>
                                <span class="font-mono text-gray-500 dark:text-gray-450">${avgMark.toFixed(1)} Avg</span>
                            </div>
                            <div class="w-full bg-gray-50 dark:bg-themeDark h-1.5 rounded-full overflow-hidden border border-gray-100/50 dark:border-white/[0.04]">
                                <div class="${colorClass} h-full rounded-full transition-all duration-500" style="width: ${barWidth}%"></div>
                            </div>
                        </div>
                    `;
                });
            } else {
                distContainer.innerHTML = `<span class="text-xs font-semibold text-gray-400 dark:text-gray-500">No Subject Distribution Stats Available.</span>`;
            }

            // ==========================================
            // DYNAMIC CUSTOMIZED FEES PROCESSING
            // ==========================================
            let customFees = res.data.customFees || [];
            let feeCategories = res.data.feeCategories || [];
            
            // Populate category dropdown via Alpine.js event
            window.dispatchEvent(new CustomEvent('populate-categories', { detail: feeCategories }));

            renderCustomFees(customFees);

            // Hide Loading Overlay
            document.getElementById('loadingOverlay').classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => document.getElementById('loadingOverlay').classList.add('hidden'), 300);

        } catch (err) {
            console.error(err);
            await showAlert("Critical Error: Failed to fetch student profile details.", "Error");
            window.location.href = '/students';
        }
    });
    async function syncRfidCardSingle(studentId) {
        try {
            document.getElementById('loadingOverlay').classList.remove('hidden', 'opacity-0', 'pointer-events-none');
            
            let res = await axios.post(`/ajax/students/${studentId}/sync-card`, {}, getAuthHeaders());
            if (res.data.status === 'success') {
                document.getElementById('view_card_number').innerText = res.data.card_number;
                await showAlert(res.data.message, "Sync Success");
            } else {
                await showAlert(res.data.message || "Failed to sync card.", "Error");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Card sync failed.";
            await showAlert(errMsg, "Error");
        } finally {
            document.getElementById('loadingOverlay').classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => document.getElementById('loadingOverlay').classList.add('hidden'), 300);
        }
    }

    function renderCustomFees(fees) {
        let customFeesBody = document.getElementById('custom_fees_table_body');
        customFeesBody.innerHTML = '';
        if (fees.length > 0) {
            fees.forEach(f => {
                let catName = f.category ? f.category.name : 'Unknown';
                let amount = parseFloat(f.amount);
                customFeesBody.innerHTML += `
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                        <td class="py-2.5 px-3 text-sm font-bold text-gray-700 dark:text-gray-300">${catName}</td>
                        <td class="py-2.5 px-3 text-sm font-bold text-gray-900 dark:text-gray-100 text-right font-mono">৳${amount.toFixed(2)}</td>
                        <td class="py-2.5 px-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" onclick="deleteCustomFee(${f.id})" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        } else {
            customFeesBody.innerHTML = `
                <tr>
                    <td colspan="3" class="text-center py-4 text-xs font-bold text-gray-400 dark:text-gray-555 uppercase tracking-widest">No customized fees configured</td>
                </tr>
            `;
        }
    }

    function openCustomFeeModal() {
        document.getElementById('customFeeModal').classList.remove('hidden');
        window.dispatchEvent(new CustomEvent('reset-category-dropdown'));
        document.getElementById('custom_fee_amount').value = '';
    }

    function closeCustomFeeModal() {
        document.getElementById('customFeeModal').classList.add('hidden');
    }

    async function saveCustomFee() {
        let catId = document.getElementById('custom_fee_category_id').value;
        let amount = document.getElementById('custom_fee_amount').value;
        if (!catId || !amount) {
            await showAlert("Please select a category and enter an amount.", "Validation Error");
            return;
        }
        
        try {
            document.getElementById('loadingOverlay').classList.remove('hidden', 'opacity-0', 'pointer-events-none');
            closeCustomFeeModal();

            let res = await axios.post(`/ajax/students/${studentId}/custom-fees`, {
                fee_category_id: catId,
                amount: amount
            }, getAuthHeaders());

            if (res.data.status === 'success') {
                let reloadRes = await axios.get(`/ajax/students/${studentId}/custom-fees`, getAuthHeaders());
                renderCustomFees(reloadRes.data.data);
                await showAlert(res.data.message, "Success");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Failed to save customized fee.";
            await showAlert(errMsg, "Error");
        } finally {
            document.getElementById('loadingOverlay').classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => document.getElementById('loadingOverlay').classList.add('hidden'), 300);
        }
    }

    async function deleteCustomFee(feeId) {
        let confirmDelete = await showConfirm("Delete Custom Fee?", "Are you sure you want to remove this customized fee? The student will be billed standard fees for this category.");
        if (!confirmDelete) return;

        try {
            document.getElementById('loadingOverlay').classList.remove('hidden', 'opacity-0', 'pointer-events-none');

            let res = await axios.delete(`/ajax/students/custom-fees/${feeId}`, getAuthHeaders());
            if (res.data.status === 'success') {
                let reloadRes = await axios.get(`/ajax/students/${studentId}/custom-fees`, getAuthHeaders());
                renderCustomFees(reloadRes.data.data);
                await showAlert(res.data.message, "Success");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Failed to delete customized fee.";
            await showAlert(errMsg, "Error");
        } finally {
            document.getElementById('loadingOverlay').classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => document.getElementById('loadingOverlay').classList.add('hidden'), 300);
        }
    }

    async function pushToDevice() {
        try {
            let confirmed = await showConfirm("Push Student to Device", "Do you want to push this student's record to the biometric device?");
            if (!confirmed) return;

            document.getElementById('loadingOverlay').classList.remove('hidden', 'opacity-0', 'pointer-events-none');

            let res = await axios.post(`/ajax/students/${studentId}/push-to-device`, {}, getAuthHeaders());
            if (res.data.status === 'success') {
                await showAlert(res.data.message, "Push Success");
            } else {
                await showAlert(res.data.message || "Failed to push student to device.", "Error");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Device sync failed.";
            await showAlert(errMsg, "Push Error");
        } finally {
            document.getElementById('loadingOverlay').classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => document.getElementById('loadingOverlay').classList.add('hidden'), 300);
        }
    }
</script>
@endpush