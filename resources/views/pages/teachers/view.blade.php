@extends('tyro-dashboard::layouts.admin')

@section('title', 'Teacher Profile')

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
            <p class="text-xs font-black text-themeBlue dark:text-indigo-400 uppercase tracking-widest">Loading Staff Profile...</p>
        </div>
    </div>

    <!-- Page Header (Tile Case, Preceded with Theme Icon) -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                </svg>
                Staff Profile & Credentials
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-450 mt-1">Official faculty record, biometric device linkage and employment summary</p>
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button onclick="pushToDevice()" id="btnPushDevice" class="h-11 px-5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/30 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 border border-indigo-200 dark:border-indigo-900/60 shadow-sm whitespace-nowrap active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                <span>Push to Machine</span>
            </button>

            <a href="/teacher/edit/{{ $id }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                <span>Edit Staff</span>
            </a>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- LEFT COLUMN: Avatar & Biometric Card -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Primary Profile Card -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-themeBlue/[0.03] rounded-full -mr-16 -mt-16 pointer-events-none"></div>
                
                <div class="flex flex-col items-center text-center relative z-10">
                    <div class="relative mb-4">
                        <img id="profile_photo" src="" alt="Staff Avatar" class="w-28 h-28 rounded-2xl object-cover border-2 border-gray-100 dark:border-gray-800 shadow-md bg-gray-50">
                        <div class="absolute -bottom-2 -right-2 px-2.5 py-1 bg-themeGreen text-white text-[9px] font-black rounded-full border-2 border-white dark:border-themeNavy uppercase tracking-wider shadow-sm flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                            <span>Active</span>
                        </div>
                    </div>

                    <h2 id="view_name" class="text-xl font-black text-gray-900 dark:text-white tracking-tight">Staff Name</h2>
                    <p id="view_designation" class="text-xs font-black uppercase tracking-wider text-themeBlue mt-1">Designation</p>

                    <div class="flex flex-wrap items-center justify-center gap-2 mt-4">
                        <span class="px-3 py-1 bg-gray-100 dark:bg-themeDark text-gray-700 dark:text-gray-300 rounded-lg text-[10px] font-black uppercase tracking-wider border border-gray-200/50 dark:border-white/[0.06]">
                            <span class="text-gray-400">ID:</span> <span id="badge_id">...</span>
                        </span>
                        <span class="px-3 py-1 bg-themeBlue/10 text-themeBlue rounded-lg text-[10px] font-black uppercase tracking-wider border border-themeBlue/20">
                            <span class="text-themeBlue/70">Dept:</span> <span id="badge_dept">...</span>
                        </span>
                    </div>

                    <!-- Contact Details Divider -->
                    <div class="w-full border-t border-gray-100 dark:border-white/[0.06] my-6"></div>

                    <div class="w-full space-y-3.5 text-left">
                        <div class="flex items-center gap-3 p-3 bg-gray-50/70 dark:bg-themeDark/50 rounded-2xl border border-gray-100/80 dark:border-white/[0.04]">
                            <div class="w-9 h-9 rounded-xl bg-themeBlue/10 text-themeBlue flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500">Email Address</p>
                                <p id="view_email" class="text-xs font-bold text-gray-900 dark:text-gray-200 truncate">...</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3 bg-gray-50/70 dark:bg-themeDark/50 rounded-2xl border border-gray-100/80 dark:border-white/[0.04]">
                            <div class="w-9 h-9 rounded-xl bg-themeGreen/10 text-themeGreen flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500">Mobile Phone</p>
                                <p id="view_phone" class="text-xs font-bold text-gray-900 dark:text-gray-200">...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biometric Machine Integration Widget Card -->
            <div class="bg-gradient-to-br from-indigo-50/60 to-themeBlue/5 dark:from-themeNavy dark:to-themeDark border border-indigo-100 dark:border-white/[0.08] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-themeBlue text-white flex items-center justify-center shadow-md shadow-themeBlue/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.516 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white">Attendance Machine</h3>
                            <p class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">Biometric Sync Status</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-450 border border-emerald-300/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Linked</span>
                    </span>
                </div>

                <div class="bg-white dark:bg-themeDark/80 rounded-2xl p-4 border border-indigo-100/80 dark:border-white/[0.06] mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Biometric Device ID</span>
                            <div id="view_device_id" class="text-2xl font-black font-mono text-themeBlue mt-0.5 tracking-tight">10006</div>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Target IP</span>
                            <div class="text-xs font-mono font-bold text-gray-700 dark:text-gray-300 mt-0.5">103.190.229.86</div>
                        </div>
                    </div>
                </div>

                <button onclick="pushToDevice()" class="w-full py-2.5 px-4 bg-themeBlue hover:bg-themeBlue/90 text-white text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-sm active:scale-98">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                    <span>Sync User to Device</span>
                </button>
            </div>
        </div>

        <!-- RIGHT COLUMN: Detailed Credentials Cards -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Employment & Professional Credentials Card -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-8 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-white/[0.06]">
                    <div class="w-10 h-10 rounded-xl bg-themeBlue/10 text-themeBlue flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white tracking-tight">Employment & Professional Credentials</h3>
                        <p class="text-xs font-medium text-gray-455 dark:text-gray-500">Official institutional position, department, and service setup</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Employee Code</span>
                        <span id="view_employee_id" class="text-sm font-extrabold text-gray-900 dark:text-white font-mono">...</span>
                    </div>

                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Professional Rank</span>
                        <span id="view_rank" class="text-sm font-bold text-themeBlue dark:text-themeBlue">...</span>
                    </div>

                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Department</span>
                        <span id="view_dept_name" class="text-sm font-bold text-gray-900 dark:text-white">...</span>
                    </div>

                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Joining Date</span>
                        <span id="view_joining_date" class="text-sm font-bold text-gray-900 dark:text-white">...</span>
                    </div>

                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Assigned Shift</span>
                        <span id="view_shift" class="text-sm font-bold text-gray-900 dark:text-white">...</span>
                    </div>

                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Created By</span>
                        <span id="view_created_by" class="text-sm font-bold text-gray-900 dark:text-white truncate block">...</span>
                    </div>
                </div>
            </div>

            <!-- Personal & Demographic Details Card -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-8 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100 dark:border-white/[0.06]">
                    <div class="w-10 h-10 rounded-xl bg-themeGreen/10 text-themeGreen flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white tracking-tight">Personal & Demographic Details</h3>
                        <p class="text-xs font-medium text-gray-455 dark:text-gray-500">Identity, blood group, and residential contact address</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Gender</span>
                        <span id="view_gender" class="text-sm font-bold text-gray-900 dark:text-white">...</span>
                    </div>

                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Blood Group</span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-450 rounded-lg text-xs font-black uppercase border border-red-200/50 dark:border-red-900/40">
                            <svg class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 01.832.445l4.5 6.5A5.5 5.5 0 115 11.5a5.485 5.485 0 01.332-1.888l4.5-6.5A1 1 0 0110 2zm0 4.236L7.382 10.02A3.5 3.5 0 1010 15a3.486 3.486 0 002.618-1.216L10 6.236z" clip-rule="evenodd" /></svg>
                            <span id="view_blood">...</span>
                        </span>
                    </div>

                    <div class="p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">System Account</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/30 text-themeGreen dark:text-emerald-450 rounded-lg text-[10px] font-black uppercase border border-emerald-200/50">
                            <span class="w-1.5 h-1.5 rounded-full bg-themeGreen"></span>
                            Verified Staff
                        </span>
                    </div>

                    <div class="sm:col-span-2 md:col-span-3 p-4 bg-gray-50/60 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.04]">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-455 dark:text-gray-500 block mb-1">Residential Full Address</span>
                        <span id="view_address" class="text-sm font-bold text-gray-900 dark:text-white leading-relaxed">...</span>
                    </div>
                </div>
            </div>

        </div>
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

    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        try {
            const options = { year: 'numeric', month: 'short', day: '2-digit' };
            const d = new Date(dateStr);
            return isNaN(d) ? dateStr : d.toLocaleDateString('en-GB', options);
        } catch (e) {
            return dateStr;
        }
    }

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            let res = await axios.get(`/ajax/teachers/${teacherId}`, getAuthHeaders());
            let teacher = res.data.data;

            let teacherName = teacher.user?.name || 'Staff Member';
            let teacherEmail = teacher.user?.email || 'No Email';
            let deviceId = teacher.biometric_id || (10000 + parseInt(teacher.id));

            document.getElementById('view_name').innerText = teacherName;
            document.getElementById('view_email').innerText = teacherEmail;
            document.getElementById('view_designation').innerText = teacher.designation || 'Staff';
            document.getElementById('view_rank').innerText = teacher.designation || 'Staff';
            document.getElementById('badge_id').innerText = teacher.employee_id || ('PIS-T-' + teacher.id);
            document.getElementById('view_employee_id').innerText = teacher.employee_id || ('PIS-T-' + teacher.id);
            document.getElementById('badge_dept').innerText = teacher.department || 'General';
            document.getElementById('view_dept_name').innerText = teacher.department || 'General';
            
            document.getElementById('view_phone').innerText = teacher.phone || 'N/A';
            document.getElementById('view_joining_date').innerText = formatDate(teacher.joining_date);
            document.getElementById('view_gender').innerText = teacher.gender || 'N/A';
            document.getElementById('view_blood').innerText = teacher.blood_group || 'N/A';
            document.getElementById('view_address').innerText = teacher.address || 'N/A';
            document.getElementById('view_device_id').innerText = deviceId;
            document.getElementById('view_shift').innerText = teacher.shift?.name || teacher.shift?.shift_name || 'Regular Staff';
            document.getElementById('view_created_by').innerText = teacher.creator?.name || 'Administrator';

            let photoUrl = teacher.photo 
                ? '/storage/' + teacher.photo 
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(teacherName)}&background=008ED6&color=fff&bold=true&size=256`;
            document.getElementById('profile_photo').src = photoUrl;

            document.getElementById('loadingOverlay').classList.add('hidden');
        } catch (error) { 
            console.error(error);
            await showAlert("Failed to load staff profile record!", "Error"); 
            window.location.href = '/teachers'; 
        }
    });

    window.pushToDevice = async function() {
        try {
            let confirmed = await showConfirm(
                "Push Staff to Device",
                "Do you want to push this staff member credentials directly to the biometric attendance machine?"
            );
            if (!confirmed) return;

            let btn = document.getElementById('btnPushDevice');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-70', 'cursor-not-allowed');
            }

            let res = await axios.post(`/ajax/teachers/${teacherId}/push-to-device`, {}, getAuthHeaders());
            
            if (res.data.status === 'success') {
                await showAlert(res.data.message, "Sync Success");
            } else {
                await showAlert(res.data.message || "Failed to push staff to device.", "Error");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Biometric device synchronization failed.";
            await showAlert(errMsg, "Sync Error");
        } finally {
            let btn = document.getElementById('btnPushDevice');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        }
    };
</script>
@endpush