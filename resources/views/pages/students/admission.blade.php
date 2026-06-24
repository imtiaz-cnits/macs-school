@extends('tyro-dashboard::layouts.admin')

@section('title', 'New Student Admission')

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
                    themePink: '#d97782',
                    themeIndigo: '#4f46e5'
                },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    /* ডার্ক মোডে টেক্সট ক্লিয়ার রাখার জন্য কাস্টম স্টাইল */
    .form-label { @apply block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wider; }
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-themeGreen focus:border-transparent outline-none transition shadow-sm placeholder-gray-400 dark:placeholder-gray-500 disabled:bg-gray-100 disabled:dark:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-70; }
    .required-star { @apply text-red-600 ml-0.5; }
    .id-display-card { @apply bg-indigo-50/50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-3 rounded-xl shadow-sm; }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<a href="{{ route('students.index') }}" class="text-themeGreen font-bold hover:underline">Student Management</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">New Admission</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 md:p-10 border-2 border-themeGreen/30 dark:border-themeGreen/40">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-6 border-b border-gray-100 dark:border-gray-700 pb-8">
            <div>
                <h2 class="text-3xl md:text-4xl font-black text-themeGreen dark:text-green-500 uppercase tracking-tighter">
                    New Student Admission
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">Pabna International School - Smart Education Management System</p>
            </div>
            
            <div class="id-display-card w-full md:w-auto min-w-[250px]">
                <label class="text-[10px] font-black text-themeIndigo uppercase tracking-widest block mb-1">Student Identity (Auto)</label>
                <div class="relative">
                    <input type="text" value="{{ date('Y') }}-XX-XXXX" class="w-full bg-white dark:bg-gray-900 border-none text-indigo-700 dark:text-indigo-400 font-mono font-bold px-3 py-1.5 rounded outline-none" readonly>
                    <svg class="w-4 h-4 text-indigo-300 absolute right-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
            </div>
        </div>

        <form id="admissionForm" onsubmit="event.preventDefault(); window.SaveStudent();">
            
            <h3 class="text-xl font-black text-gray-800 dark:text-white mb-6 border-l-4 border-yellow-500 pl-3 uppercase">Academic Details</h3>
           <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-12 p-6 bg-themeGreen/5 dark:bg-green-900/10 rounded-2xl border border-themeGreen/20">
                <div>
                    <label class="form-label text-green-700 dark:text-green-400">Branch <span class="required-star">*</span></label>
                    <select id="branch_id" class="form-input border-green-200 dark:border-green-900/50" required>
                        <option value="">Select Branch</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-green-700 dark:text-green-400">Class <span class="required-star">*</span></label>
                    <select id="class_id" class="form-input border-green-200 dark:border-green-900/50" required>
                        <option value="">Select Class</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-green-700 dark:text-green-400">Section <span class="required-star">*</span></label>
                    <select id="section_id" class="form-input border-green-200 dark:border-green-900/50" required>
                        <option value="">Select Section</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-green-700 dark:text-green-400">Shift</label>
                    <select id="shift_id" class="form-input border-green-200 dark:border-green-900/50">
                        <option value="">Select Shift</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-green-700 dark:text-green-400">Session <span class="required-star">*</span></label>
                    <select id="session_year_id" class="form-input border-green-200 dark:border-green-900/50" required>
                        <option value="">Select Session</option>
                    </select>
                </div>
            </div>

            <h3 class="text-xl font-black text-gray-800 dark:text-white mb-6 border-l-4 border-blue-500 pl-3 uppercase">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div>
                    <label class="form-label">Student Name <span class="required-star">*</span></label>
                    <input type="text" id="student_name" class="form-input" placeholder="Full Name" required>
                </div>
                <div>
                    <label class="form-label">Name in Bangla</label>
                    <input type="text" id="name_in_bangla" class="form-input" placeholder="বাংলা নাম">
                </div>
                <div>
                    <label class="form-label">Class Roll <span class="required-star">*</span></label>
                    <input type="text" id="roll_number" class="form-input" placeholder="Roll No" required>
                </div>
                <div>
                    <label class="form-label">Date of Birth <span class="required-star">*</span></label>
                    <input type="date" id="dob" class="form-input" required>
                </div>
                
                <div>
                    <label class="form-label">Birth Certificate No</label>
                    <input type="text" id="birth_certificate" class="form-input" placeholder="Reg Number">
                </div>
                <div>
                    <label class="form-label">Gender <span class="required-star">*</span></label>
                    <select id="gender" class="form-input" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
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
                    <input type="email" id="email" class="form-input" placeholder="Email Address">
                </div>
                <div>
                    <label class="form-label">SMS Status</label>
                    <select id="sms_status" class="form-input">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Student Photo</label>
                    <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 shadow-inner h-[60px]">
                        
                        <div class="w-12 h-12 shrink-0 bg-gray-50 dark:bg-gray-700 flex items-center justify-center rounded border border-dashed border-gray-300 dark:border-gray-500 overflow-hidden relative">
                            <img id="photoPreview" src="" alt="Preview" class="w-full h-full object-cover hidden absolute inset-0 z-10">
                            <svg id="photoIcon" class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        
                        <div class="flex-1 relative h-full">
                            <input type="file" id="photo" onchange="window.previewImage(event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/jpg">
                            
                            <div class="h-full flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-[11px] font-black uppercase tracking-wider rounded transition">
                                Choose Photo
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label">Document (Birth Cert/NID)</label>
                    <div class="relative flex items-center justify-between gap-3 bg-white dark:bg-gray-800 p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 shadow-inner h-[60px] cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition" onclick="document.getElementById('document_file').click()">
                        
                        <input type="file" id="document_file" class="hidden" accept=".pdf, image/jpeg, image/png, image/jpg" onchange="window.previewDocument(event)">
                        
                        <div id="docPlaceholder" class="flex items-center text-gray-500 pl-2 w-full">
                            <svg class="w-5 h-5 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <span class="text-[11px] font-bold">Upload PDF/Image</span>
                        </div>

                        <div id="docPreviewInfo" class="hidden flex items-center justify-between w-full pl-2 pr-1">
                            <div class="flex items-center overflow-hidden">
                                <svg class="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <div class="truncate max-w-[120px]">
                                    <p id="docFileName" class="text-[11px] font-bold text-gray-700 dark:text-gray-300 truncate"></p>
                                    <p id="docFileSize" class="text-[9px] text-gray-500"></p>
                                </div>
                            </div>
                            <button type="button" onclick="window.removeDocument(event)" class="text-red-500 hover:text-red-700 p-1.5 shrink-0 bg-red-50 dark:bg-red-900/30 rounded shadow-sm transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="text-xl font-black text-gray-800 dark:text-white mb-6 border-l-4 border-themeGreen pl-3 uppercase">Family Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="bg-gray-50 dark:bg-gray-900/40 p-6 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <h4 class="text-[11px] font-black text-green-600 dark:text-green-500 uppercase mb-4 tracking-widest border-b border-green-100 dark:border-green-900/30 pb-2">Father's Details</h4>
                    <div class="space-y-4">
                        <input type="text" id="father_name" class="form-input" placeholder="Father's Name *" required>
                        <input type="text" id="father_occupation" class="form-input" placeholder="Occupation">
                        <input type="text" id="father_mobile" class="form-input" placeholder="Father's Mobile *" required>
                        <input type="text" id="father_nid" class="form-input" placeholder="Father's NID">
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/40 p-6 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <h4 class="text-[11px] font-black text-themeIndigo dark:text-indigo-400 uppercase mb-4 tracking-widest border-b border-indigo-100 dark:border-indigo-900/30 pb-2">Mother's Details</h4>
                    <div class="space-y-4">
                        <input type="text" id="mother_name" class="form-input" placeholder="Mother's Name *" required>
                        <input type="text" id="mother_occupation" class="form-input" placeholder="Occupation">
                        <input type="text" id="mother_mobile" class="form-input" placeholder="Mother's Mobile *" required>
                        <input type="text" id="mother_nid" class="form-input" placeholder="Mother's NID">
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/40 p-6 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <h4 class="text-[11px] font-black text-themeRed dark:text-red-400 uppercase mb-4 tracking-widest border-b border-red-100 dark:border-red-900/30 pb-2">Emergency Contact</h4>
                    <div class="space-y-4">
                        <input type="text" id="guardian_name" class="form-input" placeholder="Guardian Name">
                        <input type="text" id="guardian_occupation" class="form-input" placeholder="Occupation">
                        <input type="text" id="guardian_mobile" class="form-input" placeholder="Guardian Mobile *" required>
                    </div>
                </div>
            </div>

            <h3 class="text-xl font-black text-gray-800 dark:text-white mb-6 border-l-4 border-themePink pl-3 uppercase">Address Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <div class="bg-gray-50 dark:bg-gray-900/40 p-6 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <h4 class="text-[11px] font-black text-themePink uppercase mb-4 tracking-widest border-b border-pink-100 dark:border-pink-900/30 pb-2">Present Address</h4>
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
                                <option value="Bagerhat">Bagerhat</option><option value="Bandarban">Bandarban</option><option value="Barguna">Barguna</option><option value="Barishal">Barishal</option><option value="Bhola">Bhola</option><option value="Bogura">Bogura</option><option value="Brahmanbaria">Brahmanbaria</option><option value="Chandpur">Chandpur</option><option value="Chapainawabganj">Chapainawabganj</option><option value="Chattogram">Chattogram</option><option value="Chuadanga">Chuadanga</option><option value="Cox's Bazar">Cox's Bazar</option><option value="Cumilla">Cumilla</option><option value="Dhaka">Dhaka</option><option value="Dinajpur">Dinajpur</option><option value="Faridpur">Faridpur</option><option value="Feni">Feni</option><option value="Gaibandha">Gaibandha</option><option value="Gazipur">Gazipur</option><option value="Gopalganj">Gopalganj</option><option value="Habiganj">Habiganj</option><option value="Jamalpur">Jamalpur</option><option value="Jashore">Jashore</option><option value="Jhalokati">Jhalokati</option><option value="Jhenaidah">Jhenaidah</option><option value="Joypurhat">Joypurhat</option>< value="Khagrachhari">Khagrachhari</	option><Option value="Khulna">Khulna</Option><Option value="Kishoreganj">Kishoreganj</Option><Option value="Kurigram">Kurigram</Option><Option value="Kushtia">Kushtia</Option><Option value="Lakshmipur">Lakshmipur</Option><Option value="Lalmonirhat">Lalmonirhat</Option><Option value="Madaripur">Madaripur</Option><Option value="Magura">Magura</Option><Option value="Manikganj">Manikganj</Option><Option value="Meherpur">Meherpur</Option><Option value="Moulvibazar">Moulvibazar</Option><Option value="Munshiganj">Munshiganj</Option><Option value="Mymensingh">Mymensingh</Option><Option value="Naogaon">Naogaon</Option><Option value="Narail">Narail</Option><Option value="Narayanganj">Narayanganj</Option><Option value="Narsingdi">Narsingdi</Option><Option value="Natore">Natore</Option><Option value="Netrokona">Netrokona</Option><Option value="Nilphamari">Nilphamari</Option><Option	value]="Noakhali">Noakhali"</-option>
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

                <div class="bg-gray-50 dark:bg-gray-900/40 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 relative">
                    <div class="flex justify-between items-center mb-4 border-b border-pink-100 dark:border-pink-900/30 pb-2">
                        <h4 class="text-[11px] font-black text-themePink uppercase tracking-widest">Permanent Address</h4>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="same_as_present" onchange="window.togglePermanentAddress()" class="w-4 h-4 text-themeGreen border-gray-300 rounded focus:ring-themeGreen cursor-pointer">
                            <label for="same_as_present" class="text-xs font-bold text-gray-600 dark:text-gray-400 cursor-pointer select-none">Same as Present</label>
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

            <div class="flex flex-col md:flex-row gap-4 items-center justify-between pt-10 border-t border-gray-100 dark:border-gray-700 mt-6">
                <div class="flex gap-3 w-full md:w-auto">
                    <a href="{{ route('students.index') }}" class="flex-1 md:flex-none bg-themeRed hover:bg-red-800 text-white font-black py-4 px-10 rounded-2xl shadow-lg transition-all text-center uppercase text-xs tracking-widest">Close</a>
                    <button type="reset" class="flex-1 md:flex-none bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-black py-4 px-10 rounded-2xl hover:bg-gray-200 transition-all uppercase text-xs tracking-widest border border-gray-200 dark:border-gray-600">Reset</button>
                </div>
                
                <button type="submit" class="w-full md:w-auto bg-themeGreen hover:bg-green-900 text-white font-black py-5 px-24 rounded-2xl shadow-2xl transition-all hover:scale-[1.02] active:scale-95 uppercase tracking-[0.2em] text-sm">
                    Confirm Admission
                </button>
            </div>
        </form>

        <div class="mt-12 text-center border-t border-gray-50 dark:border-gray-900 pt-6">
            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.4em]">
                Powered by <a href="https://www.codenextit.com" target="_blank" class="text-themeGreen dark:text-green-600 hover:underline decoration-2">Code Next IT</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const getAuthHeaders = () => ({ 
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
    });

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const [branches, classes, sections, sessions, shifts] = await Promise.all([
                axios.get('/ajax/branches', getAuthHeaders()),
                axios.get('/ajax/classes', getAuthHeaders()),
                axios.get('/ajax/sections', getAuthHeaders()),
                axios.get('/ajax/sessions', getAuthHeaders()),
                axios.get('/ajax/shifts', getAuthHeaders())
            ]);

            const fill = (id, data, key) => {
                let s = document.getElementById(id);
                if(data && s) data.forEach(i => s.add(new Option(i[key], i.id)));
            };

            fill('branch_id', branches.data.branchData, 'branch_name');
            fill('class_id', classes.data.classData, 'class_name');
            fill('section_id', sections.data.sectionData, 'section_name');
            fill('session_year_id', sessions.data.sessionData, 'session_name');
            fill('shift_id', shifts.data.shiftData, 'shift_name');

        } catch (e) { console.error("Dropdown error:", e); }
    });

    // ফটো প্রিভিউ ফাংশন (SVG আইকন হাইড/শো করার লজিক সহ)
    window.previewImage = e => {
        const file = e.target.files[0];
        const preview = document.getElementById('photoPreview');
        const icon = document.getElementById('photoIcon');
        
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            if(icon) icon.classList.add('hidden');
        } else {
            preview.src = '';
            preview.classList.add('hidden');
            if(icon) icon.classList.remove('hidden');
        }
    };

    // ডকুমেন্ট প্রিভিউ ফাংশন
    window.previewDocument = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            alert("File size is too large! Maximum allowed size is 2MB.");
            e.target.value = '';
            return;
        }

        document.getElementById('docPlaceholder').classList.add('hidden');
        document.getElementById('docPreviewInfo').classList.remove('hidden');
        document.getElementById('docFileName').innerText = file.name;
        document.getElementById('docFileSize').innerText = (file.size / 1024).toFixed(1) + " KB";
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
                permanent.disabled = true; 
            } else {
                permanent.value = '';
                permanent.disabled = false;
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

    window.SaveStudent = async function() {
        let formData = new FormData();
        
        const fields = [
            'roll_number', 'student_name', 
            'name_in_bangla', 'dob', 'gender', 'blood_group', 'religion', 'email',
            'father_name', 'father_nid', 'father_mobile', 'father_occupation',
            'mother_name', 'mother_nid', 'mother_mobile', 'mother_occupation',
            'present_village', 'present_post_office', 'present_post_code', 'present_district', 'present_division',
            'permanent_village', 'permanent_post_office', 'permanent_post_code', 'permanent_district', 'permanent_division',
            'guardian_name', 'guardian_occupation', 'guardian_mobile', 'sms_status', 'branch_id', 'class_id', 'section_id', 
            'shift_id', 'session_year_id', 'birth_certificate'
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
            btn.innerText = 'PROCESSING...';
            btn.disabled = true;

            let res = await axios.post('/ajax/students', formData, {
                headers: { ...getAuthHeaders().headers, 'Content-Type': 'multipart/form-data' }
            });

            if (res.status === 201) {
                alert(`SUCCESS!\nIdentity: ${res.data.identity}`);
                window.location.href = '/students';
            }
        } catch (err) {
            alert(err.response?.data?.message || 'Check required fields or file sizes!');
        } finally {
            document.querySelector('button[type="submit"]').innerText = 'Confirm Admission';
            document.querySelector('button[type="submit"]').disabled = false;
        }
    };
</script>
@endpush