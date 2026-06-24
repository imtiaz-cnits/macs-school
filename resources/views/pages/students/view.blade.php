@extends('tyro-dashboard::layouts.admin')

@section('title', 'Student Profile View')

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
                    themeGreen: '#1e4630', 
                    themeRed: '#cc0000', 
                    themeIndigo: '#6366f1', 
                    cardDark: '#111827'
                },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'], mono: ['Fira Code', 'monospace'] } 
            } 
        } 
    }
</script>
<style>
    .info-label { @apply text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-1.5; }
    .info-value { @apply text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight; }
    .info-card { @apply bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm transition-all hover:shadow-md; }
    .section-title { @apply text-lg font-black text-themeGreen dark:text-green-500 mb-6 flex items-center gap-2 border-b border-gray-50 dark:border-gray-700/50 pb-3 uppercase tracking-tighter; }
    
    .identity-badge { 
        @apply px-5 py-2.5 bg-themeIndigo/10 dark:bg-themeIndigo/20 border-2 border-themeIndigo/30 dark:border-themeIndigo/50 
        rounded-xl text-base font-black text-themeIndigo dark:text-indigo-300 font-mono shadow-lg shadow-themeIndigo/5 uppercase tracking-wider;
    }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto relative min-h-screen">
    
    <div id="loadingOverlay" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 z-50 flex items-center justify-center backdrop-blur-sm rounded-xl transition-all duration-300">
        <div class="text-center">
            <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-themeGreen rounded-full animate-spin mb-3"></div>
            <p class="text-lg font-black text-themeGreen dark:text-green-500 uppercase tracking-widest">Loading Record...</p>
        </div>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Student Profile</h1>
            <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <a href="{{ route('students.index') }}" class="flex-1 md:flex-none inline-flex items-center justify-center px-6 py-3 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-widest transition-all hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back
            </a>
            <a href="/student/edit/{{ $id }}" class="flex-1 md:flex-none inline-flex items-center justify-center px-8 py-3 bg-themeGreen hover:bg-green-900 text-white text-xs font-black rounded-xl shadow-xl uppercase tracking-widest transition-all hover:scale-105">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit Profile
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-700 p-6 md:p-10 mb-8 flex flex-col md:flex-row items-center gap-10 overflow-hidden relative">
        <div class="absolute top-0 right-0 w-64 h-64 bg-themeGreen/5 rounded-full -mr-32 -mt-32"></div>
        
        <div class="shrink-0 relative z-10">
            <img id="profile_photo" src="" alt="Student" class="w-32 h-32 md:w-48 md:h-48 rounded-[2.5rem] object-cover border-4 border-white dark:border-gray-700 shadow-2xl bg-gray-50 dark:bg-gray-900">
            <div class="absolute -bottom-2 -right-2 px-4 py-1.5 bg-green-600 text-white text-[10px] font-black rounded-full border-4 border-white dark:border-gray-800 uppercase shadow-lg">Active Student</div>
        </div>

        <div class="text-center md:text-left flex-1 z-10">
            <h2 id="view_student_name" class="text-3xl md:text-5xl font-black text-gray-900 dark:text-white mb-2 uppercase tracking-tighter">Student Name</h2>
            <p id="view_name_in_bangla" class="text-xl text-themeGreen dark:text-green-500 mb-8 font-bold">বাংলা নাম</p>
            
            <div class="flex flex-wrap justify-center md:justify-start gap-4">
                <div class="identity-badge" id="badge_identity">PIS-0000-00-0000</div>
                
                <div class="bg-gray-50 dark:bg-gray-900 px-5 py-2.5 rounded-xl border border-gray-100 dark:border-gray-700">
                    <span class="info-label block">Class & Roll</span>
                    <span class="text-sm font-black text-gray-700 dark:text-gray-300 uppercase">
                        Class: <span id="badge_class">...</span> • Roll: <span id="badge_roll">...</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <div class="info-card">
            <h3 class="section-title">Academic Details</h3>
            <div class="grid grid-cols-2 gap-8">
                <div><p class="info-label">Branch</p><p class="info-value" id="view_branch">...</p></div>
                <div><p class="info-label">Session</p><p class="info-value" id="view_session">...</p></div>
                <div><p class="info-label">Section</p><p class="info-value" id="view_section">...</p></div>
                <div><p class="info-label">Shift</p><p class="info-value" id="view_shift">...</p></div>
            </div>
        </div>

        <div class="info-card flex flex-col justify-between">
            <div>
                <h3 class="section-title text-themeIndigo dark:text-indigo-400">Personal Details</h3>
                <div class="grid grid-cols-2 gap-8">
                    <div><p class="info-label">Date of Birth</p><p class="info-value" id="view_dob">...</p></div>
                    <div><p class="info-label">Gender</p><p class="info-value" id="view_gender">...</p></div>
                    <div><p class="info-label">Blood Group</p><p class="info-value" id="view_blood_group">...</p></div>
                    <div><p class="info-label">Religion</p><p class="info-value" id="view_religion">...</p></div>
                    <div class="col-span-2"><p class="info-label">Birth Certificate No</p><p class="info-value font-mono" id="view_birth_certificate">...</p></div>
                </div>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                <p class="info-label mb-3">Uploaded Document</p>
                <div id="document_wrapper"></div>
            </div>
        </div>

        <div class="info-card lg:col-span-2">
            <h3 class="section-title border-themeGreen/20">Family & Guardian Info</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="p-6 bg-themeGreen/5 dark:bg-green-900/10 rounded-2xl border border-themeGreen/10">
                    <p class="info-label text-themeGreen mb-4 font-black">Father's Info</p>
                    <div class="space-y-4">
                        <div><p class="info-label">Name</p><p class="info-value" id="view_father_name">...</p></div>
                        <div><p class="info-label">Occupation</p><p class="info-value" id="view_father_occupation">...</p></div>
                        <div><p class="info-label">Mobile</p><p class="info-value" id="view_father_mobile">...</p></div>
                        <div><p class="info-label">NID</p><p class="info-value" id="view_father_nid">...</p></div>
                    </div>
                </div>
                <div class="p-6 bg-themeIndigo/5 dark:bg-indigo-900/10 rounded-2xl border border-themeIndigo/10">
                    <p class="info-label text-themeIndigo mb-4 font-black">Mother's Info</p>
                    <div class="space-y-4">
                        <div><p class="info-label">Name</p><p class="info-value" id="view_mother_name">...</p></div>
                        <div><p class="info-label">Occupation</p><p class="info-value" id="view_mother_occupation">...</p></div>
                        <div><p class="info-label">Mobile</p><p class="info-value" id="view_mother_mobile">...</p></div>
                        <div><p class="info-label">NID</p><p class="info-value" id="view_mother_nid">...</p></div>
                    </div>
                </div>
                <div class="p-6 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <p class="info-label mb-4 font-black">Guardian Info</p>
                    <div class="space-y-4">
                        <div><p class="info-label">Local Guardian</p><p class="info-value" id="view_guardian_name">...</p></div>
                        <div><p class="info-label">Occupation</p><p class="info-value" id="view_guardian_occupation">...</p></div>
                        <div><p class="info-label">Guardian Mobile</p><p class="info-value text-themeRed" id="view_guardian_mobile">...</p></div>
                        <div><p class="info-label">Email</p><p class="info-value lowercase font-medium" id="view_email">...</p></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-card lg:col-span-2 bg-gray-50 dark:bg-gray-900/30">
            <h3 class="section-title">Residential Address</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="info-label">Present Address</p>
                    <p class="info-value leading-relaxed italic text-gray-600 dark:text-gray-400" id="view_present_address">...</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="info-label">Permanent Address</p>
                    <p class="info-value leading-relaxed italic text-gray-600 dark:text-gray-400" id="view_permanent_address">...</p>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-16 text-center border-t border-gray-100 dark:border-gray-800 pt-8 pb-4">
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.5em]">
            Developed by <a href="https://www.codenextit.com" target="_blank" class="text-themeGreen font-bold hover:underline">Code Next IT</a>
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
            let s = res.data.data;

            // ==========================================
            // স্মার্ট ইমেজ পাথ লজিক
            // ==========================================
            let finalPhotoUrl = '/img/default-student.png'; 
            
            if (s.photo) {
                if (s.photo.startsWith('img/')) {
                    finalPhotoUrl = '/' + s.photo;
                } else {
                    finalPhotoUrl = '/storage/' + s.photo;
                }
            }
            document.getElementById('profile_photo').src = finalPhotoUrl;

            // ১. হেডার ইনফরমেশন
            document.getElementById('view_student_name').innerText = s.student_name || 'N/A';
            document.getElementById('view_name_in_bangla').innerText = s.name_in_bangla || '';
            document.getElementById('badge_identity').innerText = s.student_identity || 'PIS-0000-00-0000';
            document.getElementById('badge_class').innerText = s.school_class ? s.school_class.class_name : 'N/A';
            document.getElementById('badge_roll').innerText = s.roll_number || 'N/A';

            // ২. একাডেমিক ডাটা
            document.getElementById('view_branch').innerText = s.branch ? s.branch.branch_name : 'N/A';
            document.getElementById('view_session').innerText = s.session_year ? s.session_year.session_name : 'N/A';
            document.getElementById('view_section').innerText = s.section ? s.section.section_name : 'N/A';
            document.getElementById('view_shift').innerText = s.shift ? s.shift.shift_name : 'N/A';

            // ৩. পার্সোনাল ডাটা
            document.getElementById('view_dob').innerText = s.dob || 'N/A';
            document.getElementById('view_gender').innerText = s.gender || 'N/A';
            document.getElementById('view_blood_group').innerText = s.blood_group || 'N/A';
            document.getElementById('view_religion').innerText = s.religion || 'N/A';
            document.getElementById('view_birth_certificate').innerText = s.birth_certificate || 'N/A';
            document.getElementById('view_email').innerText = s.email || 'N/A';

            // --- Document View Logic ---
            let docWrapper = document.getElementById('document_wrapper');
            if (s.document_file) {
                docWrapper.innerHTML = `
                    <a href="/storage/${s.document_file}" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-bold text-xs rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition border border-indigo-100 dark:border-indigo-800 uppercase tracking-widest">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        View Attachment
                    </a>
                `;
            } else {
                docWrapper.innerHTML = `<span class="text-xs font-bold text-gray-400 bg-gray-50 dark:bg-gray-800 px-4 py-2 rounded-lg border border-gray-100 dark:border-gray-700">No Document Uploaded</span>`;
            }

            // ৪. প্যারেন্টস ও গার্ডিয়ান (Occupation ফিল্ডগুলো যুক্ত করা হয়েছে)
            document.getElementById('view_father_name').innerText = s.father_name || 'N/A';
            document.getElementById('view_father_occupation').innerText = s.father_occupation || 'N/A';
            document.getElementById('view_father_mobile').innerText = s.father_mobile || 'N/A';
            document.getElementById('view_father_nid').innerText = s.father_nid || 'N/A';
            
            document.getElementById('view_mother_name').innerText = s.mother_name || 'N/A';
            document.getElementById('view_mother_occupation').innerText = s.mother_occupation || 'N/A';
            document.getElementById('view_mother_mobile').innerText = s.mother_mobile || 'N/A';
            document.getElementById('view_mother_nid').innerText = s.mother_nid || 'N/A';
            
            document.getElementById('view_guardian_name').innerText = s.guardian_name || 'N/A';
            document.getElementById('view_guardian_occupation').innerText = s.guardian_occupation || 'N/A';
            document.getElementById('view_guardian_mobile').innerText = s.guardian_mobile || 'N/A';

            // ৫. অ্যাড্রেস (আলাদা কলামগুলোকে একসাথে জুড়ে দেওয়া হয়েছে)
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

            // লোডিংOverlay সরিয়ে দেওয়া
            document.getElementById('loadingOverlay').classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => document.getElementById('loadingOverlay').classList.add('hidden'), 300);

        } catch (err) {
            console.error(err);
            alert("Critical Error: Failed to fetch student data.");
            window.location.href = '/students';
        }
    });
</script>
@endpush