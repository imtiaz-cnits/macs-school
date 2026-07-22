@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Student Information')

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
                    themeGreen: '#009A49', 
                    themeBlue: '#008ED6', 
                    themeDark: '#070E14', 
                    themeNavy: '#0F1E2C',
                    gray: {
                        55: '#f8fafc',
                        450: '#94a3b8',
                        555: '#64748b',
                        850: '#1e293b'
                    }
                },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    [x-cloak] { display: none !important; }
    .form-label {
        display: block;
        font-size: 10px !important;
        font-weight: 900 !important;
        letter-spacing: 0.15em !important;
        text-transform: uppercase !important;
        color: #6b7280; /* text-gray-500 */
        margin-bottom: 0.5rem !important;
    }
    .dark .form-label {
        color: #9ca3af; /* text-gray-400 */
    }
    .form-input {
        width: 100%;
        height: 44px !important;
        padding: 0 1rem !important;
        border-radius: 12px !important; /* rounded-xl */
        border: 2px solid #e2e8f0 !important; /* border-gray-200 */
        background-color: rgba(248, 250, 252, 0.5) !important; /* bg-gray-50/50 */
        color: #0f172a !important; /* text-gray-900 */
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
        outline: none !important;
        box-sizing: border-box;
    }
    .dark .form-input {
        border-color: rgba(255, 255, 255, 0.08) !important;
        background-color: #070e14 !important; /* bg-themeDark */
        color: #f8fafc !important;
    }
    .form-input:focus {
        border-color: #008ED6 !important;
        box-shadow: 0 0 0 4px rgba(0, 142, 214, 0.1) !important;
        background-color: #ffffff !important;
    }
    .dark .form-input:focus {
        background-color: #0f1e2c !important;
    }
    .required-star {
        color: #ef4444; /* text-red-500 */
        margin-left: 0.125rem;
    }
    .id-display-card {
        background-color: rgba(0, 142, 214, 0.05);
        border: 1px solid rgba(0, 142, 214, 0.15);
    }
    .dark .id-display-card {
        background-color: rgba(0, 142, 214, 0.08);
        border-color: rgba(255, 255, 255, 0.08);
    }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeBlue hover:text-themeGreen font-bold transition-colors">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<a href="{{ route('students.index') }}" class="text-themeBlue hover:text-themeGreen font-bold transition-colors">Student Management</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-500 dark:text-gray-400 font-medium">Edit Student</span>
@endsection

@section('content')
<div class="w-full min-h-screen text-gray-900 dark:text-gray-100">
    
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm p-6 md:p-10 relative overflow-hidden">
        
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="absolute inset-0 bg-white/70 dark:bg-themeDark/70 z-50 flex items-center justify-center backdrop-blur-md transition-all duration-300">
            <div class="text-center p-8 bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-xl">
                <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-themeBlue rounded-full animate-spin mb-4"></div>
                <p class="text-xs font-black text-themeBlue dark:text-indigo-400 uppercase tracking-widest">Fetching Student Data...</p>
            </div>
        </div>

        <!-- Redesigned Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6 border-b border-gray-150 dark:border-white/[0.08] pb-6">
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                    <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Edit Student Info
                </h1>
                <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Pabna International School - Smart Education Management System</p>
            </div>
            
            <!-- Student Identity Customizer Card -->
            <div x-data="studentIdentityCustomizer()" class="bg-gray-50/50 dark:bg-themeNavy/60 border border-gray-200/50 dark:border-white/[0.06] rounded-2xl shadow-sm p-3.5 w-full md:w-auto min-w-[360px]">
                <label class="text-[10px] font-black text-themeBlue uppercase tracking-widest block mb-2">Student Identity</label>
                <div class="flex items-center gap-1.5 text-xs">
                    <!-- Custom Year Dropdown -->
                    <div class="relative" @click.away="yearOpen = false">
                        <button type="button" @click="yearOpen = !yearOpen" class="w-[76px] flex items-center justify-between px-2 h-9 text-[11px] font-mono font-bold bg-white dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-themeBlue/15 focus:border-themeBlue transition-all text-left">
                            <span x-text="year || 'Year'"></span>
                            <svg class="w-3 h-3 text-gray-400 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="yearOpen" x-cloak x-transition class="absolute left-0 z-50 w-[80px] mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-48 overflow-y-auto">
                            <template x-for="y in yearOptions" :key="y">
                                <button type="button" @click="year = y; yearOpen = false; updateIdentity()" :class="year == y ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full text-center px-3 py-1.5 text-[11px] font-mono hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                    <span x-text="y"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <span class="text-gray-400 font-black">-</span>
                    
                    <!-- Custom Month Dropdown -->
                    <div class="relative" @click.away="monthOpen = false">
                        <button type="button" @click="monthOpen = !monthOpen" class="w-[70px] flex items-center justify-between px-2 h-9 text-[11px] font-mono font-bold bg-white dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-themeBlue/15 focus:border-themeBlue transition-all text-left">
                            <span x-text="month || 'Month'"></span>
                            <svg class="w-3 h-3 text-gray-400 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="monthOpen" x-cloak x-transition class="absolute left-0 z-50 w-[75px] mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-48 overflow-y-auto">
                            <template x-for="m in monthOptions" :key="m">
                                <button type="button" @click="month = m; monthOpen = false; updateIdentity()" :class="month == m ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full text-center px-3 py-1.5 text-[11px] font-mono hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                    <span x-text="m"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <span class="text-gray-400 font-black">-</span>
                    
                    <!-- Class Shortform Input -->
                    <input type="text" x-model="classShort" @input="updateIdentity()" placeholder="Class" class="bg-white dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-xl h-9 w-[60px] text-center text-[11px] font-mono font-bold text-gray-700 dark:text-gray-200 focus:outline-none focus:border-themeBlue focus:ring-2 focus:ring-themeBlue/15 transition-all uppercase" maxlength="4">
                    
                    <span class="text-gray-400 font-black">-</span>
                    
                    <!-- ID Input (Random Generated) -->
                    <div class="relative flex items-center gap-1">
                        <input type="text" x-model="randId" @input="updateIdentity()" placeholder="ID" class="bg-white dark:bg-themeDark border border-gray-200 dark:border-gray-800 rounded-xl h-9 w-[60px] text-center text-[11px] font-mono font-bold text-gray-700 dark:text-gray-200 focus:outline-none focus:border-themeBlue focus:ring-2 focus:ring-themeBlue/15 transition-all" maxlength="5">
                        <button type="button" @click="regenerateId()" class="w-8 h-8 flex items-center justify-center bg-white hover:bg-gray-50 dark:bg-themeDark dark:hover:bg-themeDark/80 border border-gray-200 dark:border-gray-800 text-themeBlue rounded-xl transition-all active:scale-95 shrink-0" title="Regenerate ID">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                        </button>
                    </div>
                </div>
                <input type="hidden" id="student_identity" value="">
            </div>
        </div>

        <form id="editForm" onsubmit="event.preventDefault(); window.UpdateStudent();">
            
            <!-- Section 1: Academic Details -->
            <div class="flex items-center gap-2 mb-6">
                <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-themeBlue to-themeGreen"></span>
                <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider font-secondary">Academic Details</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5 mb-10 p-6 bg-gray-50/50 dark:bg-themeDark/30 rounded-3xl border border-gray-100 dark:border-white/[0.06]">
                <div>
                    <label class="form-label text-themeBlue">Branch <span class="required-star">*</span></label>
                    <select id="branch_id" class="form-input" required></select>
                </div>
                <div>
                    <label class="form-label text-themeBlue">Class <span class="required-star">*</span></label>
                    <select id="class_id" class="form-input" required></select>
                </div>
                <div>
                    <label class="form-label text-themeBlue">Section <span class="required-star">*</span></label>
                    <select id="section_id" class="form-input" required></select>
                </div>
                <div>
                    <label class="form-label text-themeBlue">Shift</label>
                    <select id="shift_id" class="form-input"></select>
                </div>
                <div>
                    <label class="form-label text-themeBlue">Session <span class="required-star">*</span></label>
                    <select id="session_year_id" class="form-input" required></select>
                </div>
            </div>

            <!-- Section 2: Basic Information -->
            <div class="flex items-center gap-2 mb-6">
                <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-themeBlue to-themeGreen"></span>
                <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider font-secondary">Basic Information</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10 p-6 bg-gray-50/50 dark:bg-themeDark/30 rounded-3xl border border-gray-100 dark:border-white/[0.06]">
                <div>
                    <label class="form-label">Student Name <span class="required-star">*</span></label>
                    <input type="text" id="student_name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Name in Bangla</label>
                    <input type="text" id="name_in_bangla" class="form-input">
                </div>
                <div>
                    <label class="form-label">Class Roll <span class="required-star">*</span></label>
                    <input type="text" id="roll_number" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Date of Birth <span class="required-star">*</span></label>
                    <input type="date" id="dob" class="form-input" required>
                </div>
                
                <div>
                    <label class="form-label">Birth Certificate No</label>
                    <input type="text" id="birth_certificate" class="form-input">
                </div>
                <div>
                    <label class="form-label">Gender <span class="required-star">*</span></label>
                    <select id="gender" class="form-input" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Religion</label>
                    <select id="religion" class="form-input">
                        <option value="Islam">Islam</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Christian">Christian</option>
                        <option value="Buddhist">Buddhist</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Blood Group</label>
                    <select id="blood_group" class="form-input">
                        <option value="">None</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Student Email</label>
                    <input type="email" id="email" class="form-input">
                </div>
                <div>
                    <label class="form-label">RFID Card Number</label>
                    <div class="flex gap-2">
                        <input type="text" id="card_number" class="form-input flex-1" placeholder="e.g. 0010754689">
                        <button type="button" onclick="window.scanRfidCard(event)" class="h-11 px-4 bg-gray-50/50 dark:bg-themeNavy hover:bg-themeBlue/5 border-2 border-gray-100 dark:border-gray-800 text-themeBlue font-black text-xs uppercase tracking-wider rounded-xl transition-all whitespace-nowrap active:scale-95 shrink-0 flex items-center justify-center gap-1.5" title="Swipe card on ZKTeco device, then click to auto-bind">
                            <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-8.22-.07m8.22-.07a6 6 0 00-8.22-.07M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H10a1 1 0 01-1-1v-4z"/></svg>
                            Scan Card
                        </button>
                    </div>
                </div>
                <div>
                    <label class="form-label">SMS Status</label>
                    <select id="sms_status" class="form-input">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <!-- Student Photo -->
                <div>
                    <label class="form-label">Student Photo</label>
                    <div class="flex items-center gap-3 bg-gray-50/50 dark:bg-themeDark/40 p-1.5 rounded-xl border-2 border-gray-100 dark:border-gray-800 h-11">
                        <div class="w-8 h-8 shrink-0 bg-gray-100 dark:bg-themeNavy/50 flex items-center justify-center rounded-lg border border-dashed border-gray-300 dark:border-gray-700 overflow-hidden relative animate-none">
                            <img id="photoPreview" src="" alt="Preview" class="w-full h-full object-cover hidden absolute inset-0 z-10">
                            <svg id="photoIcon" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="flex-1 relative h-full">
                            <input type="file" id="photo" onchange="window.previewImage(event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/jpg">
                            <div class="h-full flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-themeNavy/50 text-gray-700 dark:text-gray-300 text-[10px] font-black uppercase tracking-wider rounded-lg transition border border-gray-200 dark:border-gray-800">
                                Choose Photo
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Upload -->
                <div class="relative">
                    <label class="form-label">Document (Birth Cert/NID)</label>
                    <div class="relative flex items-center justify-between gap-3 bg-gray-50/50 dark:bg-themeDark/40 p-1.5 rounded-xl border-2 border-gray-100 dark:border-gray-800 h-11 cursor-pointer hover:bg-gray-100/50 dark:hover:bg-themeDark/60 transition" onclick="document.getElementById('document_file').click()">
                        <input type="file" id="document_file" class="hidden" accept=".pdf, image/jpeg, image/png, image/jpg" onchange="window.previewDocument(event)">
                        
                        <div id="docPlaceholder" class="flex items-center text-gray-400 pl-1.5 w-full">
                            <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <span class="text-xs font-semibold">Upload PDF/Image</span>
                        </div>

                        <div id="docPreviewInfo" class="hidden flex items-center justify-between w-full pl-1.5 pr-1">
                            <div class="flex items-center overflow-hidden">
                                <svg class="w-4 h-4 text-emerald-500 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <div class="truncate max-w-[120px]">
                                    <p id="docFileName" class="text-[10px] font-bold text-gray-700 dark:text-gray-300 truncate"></p>
                                </div>
                            </div>
                            <button type="button" onclick="window.removeDocument(event)" class="text-red-500 hover:text-red-700 p-1 shrink-0 bg-red-50 dark:bg-red-950/20 rounded-md shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>
                    <div id="currentDocumentWrapper" class="hidden w-full text-center absolute -bottom-5">
                        <a id="currentDocumentLink" href="#" target="_blank" class="text-[9px] text-themeBlue dark:text-indigo-400 hover:text-themeBlue/80 font-bold flex items-center justify-center gap-1" onclick="event.stopPropagation()">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            View Current Doc
                        </a>
                    </div>
                </div>
            </div>

            </div>

            <!-- Section 3: Family Details -->
            <div class="flex items-center gap-2 mb-6">
                <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-themeBlue to-themeGreen"></span>
                <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider font-secondary">Family Details</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-gray-50/50 dark:bg-themeDark/30 p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06]">
                    <h4 class="text-[10px] font-black text-themeBlue uppercase mb-4 tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-2">Father's Details</h4>
                    <div class="space-y-4">
                        <input type="text" id="father_name" class="form-input" placeholder="Father's Name *" required>
                        <input type="text" id="father_name_bn" class="form-input" placeholder="Father's Name (Bangla)">
                        <input type="text" id="father_occupation" class="form-input" placeholder="Occupation">
                        <input type="text" id="father_mobile" class="form-input" placeholder="Father's Mobile *" required>
                        <input type="text" id="father_nid" class="form-input" placeholder="Father's NID">
                    </div>
                </div>

                <div class="bg-gray-50/50 dark:bg-themeDark/30 p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06]">
                    <h4 class="text-[10px] font-black text-themeBlue uppercase mb-4 tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-2">Mother's Details</h4>
                    <div class="space-y-4">
                        <input type="text" id="mother_name" class="form-input" placeholder="Mother's Name *" required>
                        <input type="text" id="mother_name_bn" class="form-input" placeholder="Mother's Name (Bangla)">
                        <input type="text" id="mother_occupation" class="form-input" placeholder="Occupation">
                        <input type="text" id="mother_mobile" class="form-input" placeholder="Mother's Mobile *" required>
                        <input type="text" id="mother_nid" class="form-input" placeholder="Mother's NID">
                    </div>
                </div>

                <div class="bg-gray-50/50 dark:bg-themeDark/30 p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06]">
                    <h4 class="text-[10px] font-black text-themeGreen uppercase mb-4 tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-2">Emergency Contact</h4>
                    <div class="space-y-4">
                        <input type="text" id="guardian_name" class="form-input" placeholder="Guardian Name">
                        <input type="text" id="guardian_occupation" class="form-input" placeholder="Occupation">
                        <input type="text" id="guardian_mobile" class="form-input" placeholder="Guardian Mobile *" required>
                    </div>
                </div>
            </div>

            <!-- Section 4: Address Information -->
            <div class="flex items-center gap-2 mb-6">
                <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-themeBlue to-themeGreen"></span>
                <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider font-secondary">Address Information</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                <div class="bg-gray-50/50 dark:bg-themeDark/30 p-6 rounded-3xl border border-gray-100 dark:border-white/[0.06]">
                    <h4 class="text-[10px] font-black text-themeBlue uppercase mb-4 tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-2">Present Address</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="form-label">Village / Road <span class="required-star">*</span></label>
                            <input type="text" id="present_village" oninput="window.syncAddress()" class="form-input" placeholder="Enter Village / Road" required>
                        </div>
                        <div>
                            <label class="form-label">Post Office <span class="required-star">*</span></label>
                            <input type="text" id="present_post_office" oninput="window.syncAddress()" class="form-input" placeholder="Enter Post Office" required>
                        </div>
                        <div>
                            <label class="form-label">Post Code</label>
                            <input type="text" id="present_post_code" oninput="window.syncAddress()" class="form-input" placeholder="e.g. 6600">
                        </div>
                        
                        <div>
                            <label class="form-label">District <span class="required-star">*</span></label>
                            <select id="present_district" onchange="window.syncAddress()" class="form-input" required>
                                <option value="">Select District</option>
                                <option value="Bagerhat">Bagerhat</option><option value="Bandarban">Bandarban</option><option value="Barguna">Barguna</option><option value="Barishal">Barishal</option><option value="Bhola">Bhola</option><option value="Bogura">Bogura</option><option value="Brahmanbaria">Brahmanbaria</option><option value="Chandpur">Chandpur</option><option value="Chapainawabganj">Chapainawabganj</option><option value="Chattogram">Chattogram</option><option value="Chuadanga">Chuadanga</option><option value="Cox's Bazar">Cox's Bazar</option><option value="Cumilla">Cumilla</option><option value="Dhaka">Dhaka</option><option value="Dinajpur">Dinajpur</option><option value="Faridpur">Faridpur</option><option value="Feni">Feni</option><option value="Gaibandha">Gaibandha</option><option value="Gazipur">Gazipur</option><option value="Gopalganj">Gopalganj</option><option value="Habiganj">Habiganj</option><option value="Jamalpur">Jamalpur</option><option value="Jashore">Jashore</option><option value="Jhalokati">Jhalokati</option><option value="Jhenaidah">Jhenaidah</option><option value="Joypurhat">Joypurhat</option><option value="Khagrachhari">Khagrachhari</option><option value="Khulna">Khulna</option><option value="Kishoreganj">Kishoreganj</option><option value="Kurigram">Kurigram</option><option value="Kushtia">Kushtia</option><option value="Lakshmipur">Lakshmipur</option><option value="Lalmonirhat">Lalmonirhat</option><option value="Madaripur">Madaripur</option><option value="Magura">Magura</option><option value="Manikganj">Manikganj</option><option value="Meherpur">Meherpur</option><option value="Moulvibazar">Moulvibazar</option><option value="Munshiganj">Munshiganj</option><option value="Mymensingh">Mymensingh</option><option value="Naogaon">Naogaon</option><option value="Narail">Narail</option><option value="Narayanganj">Narayanganj</option><option value="Narsingdi">Narsingdi</option><option value="Natore">Natore</option><option value="Netrokona">Netrokona</option><option value="Nilphamari">Nilphamari</option><option value="Noakhali">Noakhali</option><option value="Pabna">Pabna</option><option value="Panchagarh">Panchagarh</option><option value="Patuakhali">Patuakhali</option><option value="Pirojpur">Pirojpur</option><option value="Rajbari">Rajbari</option><option value="Rajshahi">Rajshahi</option><option value="Rangamati">Rangamati</option><option value="Rangpur">Rangpur</option><option value="Satkhira">Satkhira</option><option value="Shariatpur">Shariatpur</option><option value="Sherpur">Sherpur</option><option value="Sirajganj">Sirajganj</option><option value="Sunamganj">Sunamganj</option><option value="Sylhet">Sylhet</option><option value="Tangail">Tangail</option><option value="Thakurgaon">Thakurgaon</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Division <span class="required-star">*</span></label>
                            <select id="present_division" onchange="window.syncAddress()" class="form-input" required>
                                <option value="">Select Division</option>
                                <option value="Barishal">Barishal</option>
                                <option value="Chattogram">Chattogram</option>
                                <option value="Dhaka">Dhaka</option>
                                <option value="Khulna">Khulna</option>
                                <option value="Mymensingh">Mymensingh</option>
                                <option value="Rajshahi">Rajshahi</option>
                                <option value="Rangpur">Rangpur</option>
                                <option value="Sylhet">Sylhet</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50/50 dark:bg-themeDark/30 p-6 rounded-3xl border border-gray-150 dark:border-white/[0.06] relative">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-white/[0.06] pb-2">
                        <h4 class="text-[10px] font-black text-themeBlue uppercase tracking-widest">Permanent Address</h4>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="same_as_present" onchange="window.togglePermanentAddress()" class="w-4 h-4 text-themeBlue border-gray-300 rounded focus:ring-themeBlue/15 cursor-pointer">
                            <label for="same_as_present" class="text-xs font-black text-gray-500 cursor-pointer select-none">Same as Present</label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="form-label">Village / Road <span class="required-star">*</span></label>
                            <input type="text" id="permanent_village" class="form-input" placeholder="Enter Village / Road" required>
                        </div>
                        <div>
                            <label class="form-label">Post Office <span class="required-star">*</span></label>
                            <input type="text" id="permanent_post_office" class="form-input" placeholder="Enter Post Office" required>
                        </div>
                        <div>
                            <label class="form-label">Post Code</label>
                            <input type="text" id="permanent_post_code" class="form-input" placeholder="e.g. 6600">
                        </div>
                        
                        <div>
                            <label class="form-label">District <span class="required-star">*</span></label>
                            <select id="permanent_district" class="form-input" required>
                                <option value="">Select District</option>
                                <option value="Bagerhat">Bagerhat</option><option value="Bandarban">Bandarban</option><option value="Barguna">Barguna</option><option value="Barishal">Barishal</option><option value="Bhola">Bhola</option><option value="Bogura">Bogura</option><option value="Brahmanbaria">Brahmanbaria</option><option value="Chandpur">Chandpur</option><option value="Chapainawabganj">Chapainawabganj</option><option value="Chattogram">Chattogram</option><option value="Chuadanga">Chuadanga</option><option value="Cox's Bazar">Cox's Bazar</option><option value="Cumilla">Cumilla</option><option value="Dhaka">Dhaka</option><option value="Dinajpur">Dinajpur</option><option value="Faridpur">Faridpur</option><option value="Feni">Feni</option><option value="Gaibandha">Gaibandha</option><option value="Gazipur">Gazipur</option><option value="Gopalganj">Gopalganj</option><option value="Habiganj">Habiganj</option><option value="Jamalpur">Jamalpur</option><option value="Jashore">Jashore</option><option value="Jhalokati">Jhalokati</option><option value="Jhenaidah">Jhenaidah</option><option value="Joypurhat">Joypurhat</option><option value="Khagrachhari">Khagrachhari</option><option value="Khulna">Khulna</option><option value="Kishoreganj">Kishoreganj</option><option value="Kurigram">Kurigram</option><option value="Kushtia">Kushtia</option><option value="Lakshmipur">Lakshmipur</option><option value="Lalmonirhat">Lalmonirhat</option><option value="Madaripur">Madaripur</option><option value="Magura">Magura</option><option value="Manikganj">Manikganj</option><option value="Meherpur">Meherpur</option><option value="Moulvibazar">Moulvibazar</option><option value="Munshiganj">Munshiganj</option><option value="Mymensingh">Mymensingh</option><option value="Naogaon">Naogaon</option><option value="Narail">Narail</option><option value="Narayanganj">Narayanganj</option><option value="Narsingdi">Narsingdi</option><option value="Natore">Natore</option><option value="Netrokona">Netrokona</option><option value="Nilphamari">Nilphamari</option><option value="Noakhali">Noakhali</option><option value="Pabna">Pabna</option><option value="Panchagarh">Panchagarh</option><option value="Patuakhali">Patuakhali</option><option value="Pirojpur">Pirojpur</option><option value="Rajbari">Rajbari</option><option value="Rajshahi">Rajshahi</option><option value="Rangamati">Rangamati</option><option value="Rangpur">Rangpur</option><option value="Satkhira">Satkhira</option><option value="Shariatpur">Shariatpur</option><option value="Sherpur">Sherpur</option><option value="Sirajganj">Sirajganj</option><option value="Sunamganj">Sunamganj</option><option value="Sylhet">Sylhet</option><option value="Tangail">Tangail</option><option value="Thakurgaon">Thakurgaon</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="form-label">Division <span class="required-star">*</span></label>
                            <select id="permanent_division" class="form-input" required>
                                <option value="">Select Division</option>
                                <option value="Barishal">Barishal</option>
                                <option value="Chattogram">Chattogram</option>
                                <option value="Dhaka">Dhaka</option>
                                <option value="Khulna">Khulna</option>
                                <option value="Mymensingh">Mymensingh</option>
                                <option value="Rajshahi">Rajshahi</option>
                                <option value="Rangpur">Rangpur</option>
                                <option value="Sylhet">Sylhet</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action Panel -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between border-t border-gray-150 dark:border-white/[0.08] pt-8 mt-6">
                <a href="{{ route('students.index') }}" class="h-11 px-10 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl uppercase tracking-[0.2em] text-xs flex items-center justify-center transition-all hover:-translate-y-0.5 active:scale-95 shadow-md shadow-rose-600/10 w-full md:w-auto">Cancel</a>
                <button type="submit" class="h-11 px-12 bg-gradient-to-r from-themeBlue to-themeGreen hover:from-themeBlue/90 hover:to-themeGreen/90 text-white font-black uppercase tracking-[0.2em] text-xs rounded-xl shadow-md shadow-themeBlue/10 transition-all hover:scale-105 active:scale-95 disabled:opacity-50 w-full md:w-auto">
                    Save Changes
                </button>
            </div>
        </form>

        <div class="mt-12 text-center border-t border-gray-150 dark:border-white/[0.08] pt-6">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em]">
                Powered by <a href="https://www.codenextit.com" target="_blank" class="text-themeBlue font-bold hover:text-themeGreen transition-colors">Code Next IT</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    window.studentIdentityCustomizer = function(initialIdentity = '') {
        return {
            year: '',
            month: '',
            classShort: '',
            randId: '',
            yearOpen: false,
            monthOpen: false,
            yearOptions: [],
            monthOptions: ['01','02','03','04','05','06','07','08','09','10','11','12'],
            
            init() {
                const currentY = new Date().getFullYear();
                for (let y = currentY - 2; y <= currentY + 4; y++) {
                    this.yearOptions.push(String(y));
                }
                
                if (initialIdentity) {
                    this.parseIdentity(initialIdentity);
                } else {
                    this.year = String(currentY);
                    this.month = String(new Date().getMonth() + 1).padStart(2, '0');
                    this.fetchNextSerial();
                }
                
                // Listen to class hidden input changes
                const classSelect = document.getElementById('class_id');
                if (classSelect) {
                    classSelect.addEventListener('change', () => {
                        const classButton = classSelect.closest('.relative').querySelector('button span');
                        if (classButton) {
                            const className = classButton.innerText;
                            if (className && className !== 'Select Class') {
                                this.classShort = this.getClassShortform(className);
                                this.updateIdentity();
                            }
                        }
                    });
                }
                
                // For edit form event listener
                window.addEventListener('load-student-identity', (e) => {
                    if (e.detail) {
                        this.parseIdentity(e.detail);
                        this.updateIdentity();
                    }
                });

                this.updateIdentity();
            },
            
            parseIdentity(idStr) {
                if (!idStr) return;
                const parts = idStr.split('-');
                if (parts.length >= 1) this.year = parts[0];
                if (parts.length >= 2) this.month = parts[1];
                if (parts.length >= 3) this.classShort = parts[2];
                if (parts.length >= 4) this.randId = parts[3];
            },
            
            async fetchNextSerial() {
                try {
                    let res = await axios.get('/ajax/students/next-serial');
                    if (res.data && res.data.nextSerial) {
                        this.randId = String(res.data.nextSerial);
                        this.updateIdentity();
                    }
                } catch (e) {
                    this.randId = '539';
                    this.updateIdentity();
                }
            },
            
            regenerateId() {
                this.fetchNextSerial();
            },
            
            getClassShortform(className) {
                if (!className) return '';
                const name = className.toLowerCase().trim();
                if (name.includes('one') || name.includes('1')) return 'C1';
                if (name.includes('two') || name.includes('2')) return 'C2';
                if (name.includes('three') || name.includes('3')) return 'C3';
                if (name.includes('four') || name.includes('4')) return 'C4';
                if (name.includes('five') || name.includes('5')) return 'C5';
                if (name.includes('six') || name.includes('6')) return 'C6';
                if (name.includes('seven') || name.includes('7')) return 'C7';
                if (name.includes('eight') || name.includes('8')) return 'C8';
                if (name.includes('nine') || name.includes('9')) return 'C9';
                if (name.includes('ten') || name.includes('10')) return 'C10';
                if (name.includes('nursery')) return 'NUR';
                if (name.includes('play')) return 'PLAY';
                if (name.includes('baby')) return 'BABY';
                
                const words = className.replace(/[^a-zA-Z0-9\s]/g, '').split(/\s+/);
                if (words.length === 1) return words[0].substring(0, 3).toUpperCase();
                return words.map(w => w[0]).join('').toUpperCase();
            },
            
            updateIdentity() {
                const y = this.year || 'YYYY';
                const m = this.month || 'MM';
                const c = this.classShort || 'CLASS';
                const r = this.randId || 'XXXX';
                
                const fullId = `${y}-${m}-${c}-${r}`;
                const inputEl = document.getElementById('student_identity');
                if (inputEl) {
                    inputEl.value = fullId;
                    inputEl.dispatchEvent(new Event('change'));
                }
            }
        };
    };

    const studentId = "{{ $id ?? request()->segment(3) }}";

    const getAuthHeaders = () => ({ 
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
    });

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const [branches, classes, sections, shifts, sessions, studentRes] = await Promise.all([
                axios.get('/ajax/branches', getAuthHeaders()),
                axios.get('/ajax/classes', getAuthHeaders()),
                axios.get('/ajax/sections', getAuthHeaders()),
                axios.get('/ajax/shifts', getAuthHeaders()),
                axios.get('/ajax/sessions', getAuthHeaders()),
                axios.get(`/ajax/students/${studentId}`, getAuthHeaders())
            ]);

            const fill = (id, data, key) => {
                let s = document.getElementById(id);
                s.add(new Option(`Select ${id.replace('_id', '')}`, ''));
                if(data) data.forEach(i => s.add(new Option(i[key], i.id)));
            };

            fill('branch_id', branches.data.branchData || branches.data, 'branch_name');
            fill('class_id', classes.data.classData || classes.data, 'class_name');
            fill('section_id', sections.data.sectionData || sections.data, 'section_name');
            fill('shift_id', shifts.data.shiftData || shifts.data, 'shift_name');
            fill('session_year_id', sessions.data.sessionData || sessions.data, 'session_name');

            const s = studentRes.data.data || studentRes.data;
            window.dispatchEvent(new CustomEvent('load-student-identity', { detail: s.student_identity }));
            
            const fields = [
                'roll_number', 'student_name', 'name_in_bangla', 'birth_certificate', 
                'blood_group', 'religion', 'dob', 'gender', 'email', 'sms_status',
                'father_name', 'father_name_bn', 'father_nid', 'father_mobile', 'father_occupation', 
                'mother_name', 'mother_name_bn', 'mother_nid', 'mother_mobile', 'mother_occupation', 
                'guardian_name', 'guardian_mobile', 'guardian_occupation',
                'present_village', 'present_post_office', 'present_post_code', 'present_district', 'present_division',
                'permanent_village', 'permanent_post_office', 'permanent_post_code', 'permanent_district', 'permanent_division',
                'branch_id', 'class_id', 'section_id', 'shift_id', 'session_year_id', 'card_number'
            ];

            fields.forEach(f => {
                const el = document.getElementById(f);
                if(el) el.value = s[f] || '';
            });

            if(s.present_village && s.present_village === s.permanent_village && s.present_district === s.permanent_district) {
                document.getElementById('same_as_present').checked = true;
                window.togglePermanentAddress();
            }

            if(s.photo) {
                const preview = document.getElementById('photoPreview');
                const icon = document.getElementById('photoIcon');
                
                let finalPhotoUrl = '/img/default-student.png';
                if (s.photo.startsWith('img/')) {
                    finalPhotoUrl = '/' + s.photo;
                } else {
                    finalPhotoUrl = '/storage/' + s.photo;
                }

                preview.src = finalPhotoUrl;
                preview.classList.remove('hidden');
                if(icon) icon.classList.add('hidden');
            }

            if(s.document_file) {
                document.getElementById('docPlaceholder').querySelector('span').innerText = 'Replace Doc';
                document.getElementById('currentDocumentWrapper').classList.remove('hidden');
                document.getElementById('currentDocumentLink').href = '/storage/' + s.document_file;
            }

            document.getElementById('loadingOverlay').classList.add('hidden');

        } catch (e) { 
            console.error(e);
            await showAlert("Failed to load student data!", "Error");
            window.location.href = '/students';
        }
    });

    window.previewImage = e => {
        const file = e.target.files[0];
        const preview = document.getElementById('photoPreview');
        const icon = document.getElementById('photoIcon');
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            if(icon) icon.classList.add('hidden');
        }
    };

    window.previewDocument = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            showAlert("File size is too large! Maximum allowed size is 2MB.", "Attention");
            e.target.value = '';
            return;
        }

        document.getElementById('docPlaceholder').classList.add('hidden');
        document.getElementById('docPreviewInfo').classList.remove('hidden');
        document.getElementById('docFileName').innerText = file.name;
    };

    window.removeDocument = (e) => {
        e.stopPropagation();
        document.getElementById('document_file').value = '';
        document.getElementById('docPlaceholder').classList.remove('hidden');
        document.getElementById('docPreviewInfo').classList.add('hidden');
    };

    window.togglePermanentAddress = function() {
        const isChecked = document.getElementById('same_as_present').checked;
        const fields = ['village', 'post_office', 'post_code', 'district', 'division'];

        fields.forEach(f => {
            const present = document.getElementById('present_' + f);
            const permanent = document.getElementById('permanent_' + f);
            
            if (isChecked) {
                permanent.value = present.value;
                permanent.setAttribute('readonly', true);
                if(permanent.tagName === 'SELECT') {
                    permanent.style.pointerEvents = 'none';
                }
                permanent.classList.add('bg-gray-100', 'dark:bg-gray-700', 'cursor-not-allowed', 'opacity-70');
            } else {
                permanent.removeAttribute('readonly');
                if(permanent.tagName === 'SELECT') {
                    permanent.style.pointerEvents = 'auto';
                }
                permanent.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'cursor-not-allowed', 'opacity-70');
            }
        });
    };

    window.syncAddress = function() {
        if(document.getElementById('same_as_present').checked) {
            document.getElementById('permanent_village').value = document.getElementById('present_village').value;
            document.getElementById('permanent_post_office').value = document.getElementById('present_post_office').value;
            document.getElementById('permanent_post_code').value = document.getElementById('present_post_code').value;
            document.getElementById('permanent_district').value = document.getElementById('present_district').value;
            document.getElementById('permanent_division').value = document.getElementById('present_division').value;
        }
    };

    window.UpdateStudent = async function() {
        let formData = new FormData();
        formData.append('_method', 'PUT');

        const fields = [
            'roll_number', 'student_name', 'name_in_bangla', 'birth_certificate', 'student_identity',
            'blood_group', 'religion', 'dob', 'gender', 'email', 'sms_status', 'card_number',
            'father_name', 'father_name_bn', 'father_nid', 'father_mobile', 'father_occupation', 
            'mother_name', 'mother_name_bn', 'mother_nid', 'mother_mobile', 'mother_occupation', 
            'guardian_name', 'guardian_mobile', 'guardian_occupation',
            'present_village', 'present_post_office', 'present_post_code', 'present_district', 'present_division',
            'permanent_village', 'permanent_post_office', 'permanent_post_code', 'permanent_district', 'permanent_division',
            'branch_id', 'class_id', 'section_id', 'shift_id', 'session_year_id'
        ];

        fields.forEach(f => {
            const el = document.getElementById(f);
            if(el) formData.append(f, el.value);
        });

        let photo = document.getElementById('photo').files[0];
        if(photo) formData.append('photo', photo);

        let documentFile = document.getElementById('document_file').files[0];
        if(documentFile) formData.append('document_file', documentFile);

        try {
            let btn = document.querySelector('button[type="submit"]');
            btn.innerText = 'SAVING UPDATES...';
            btn.disabled = true;

            let res = await axios.post(`/ajax/students/${studentId}`, formData, {
                headers: { ...getAuthHeaders().headers, 'Content-Type': 'multipart/form-data' }
            });

            if (res.status === 200) {
                await showAlert('Updated successfully!', 'Success');
                window.location.href = '/students'; // শুধু /students হবে
            }
        } catch (err) {
            showAlert(err.response?.data?.message || 'Update failed! Check all required fields.', 'Error');
        } finally {
            let btn = document.querySelector('button[type="submit"]');
            btn.innerText = 'Save Changes';
            btn.disabled = false;
        }
    };

    window.scanRfidCard = async function(event) {
        try {
            await showAlert("Please swipe the RFID card on the biometric device now, then click OK to scan.", "Card Swipe Scanner");
            
            let btn = event.currentTarget || document.querySelector('button[onclick*="scanRfidCard"]');
            let origHtml = btn.innerHTML;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3-3m0 0l3 3m-3-3v12"/></svg> Detecting...';
            btn.disabled = true;

            let res = await axios.get('/ajax/students/scan-card', getAuthHeaders());
            
            if (res.data.status === 'success') {
                document.getElementById('card_number').value = res.data.card_number;
                document.getElementById('card_number').dispatchEvent(new Event('input'));
                await showAlert("Success! Detected Card Number: " + res.data.card_number, "Scan Successful");
            }
        } catch (err) {
            let errMsg = err.response?.data?.message || "Failed to scan card. Please make sure the device is connected and swipe was recent.";
            showAlert(errMsg, "Scan Error");
        } finally {
            let btn = document.querySelector('button[onclick*="scanRfidCard"]');
            if (btn) {
                btn.innerHTML = `<svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-8.22-.07m8.22-.07a6 6 0 00-8.22-.07M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H10a1 1 0 01-1-1v-4z"/></svg> Scan Card`;
                btn.disabled = false;
            }
        }
    };
</script>
@endpush