@extends('tyro-dashboard::layouts.admin')

@section('title', 'Teacher Profile')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' }, fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } } } }
</script>
<style>
    .info-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1; }
    .info-value { @apply text-base font-bold text-gray-900 dark:text-white; }
    .info-card { @apply bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto relative">
    
    <div id="loadingOverlay" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 z-50 flex items-center justify-center backdrop-blur-sm rounded-xl">
        <div class="text-center">
            <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-themeGreen rounded-full animate-spin mb-3"></div>
            <p class="text-lg font-bold text-gray-700">Loading Profile...</p>
        </div>
    </div>

    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Teacher Profile</h1>
        <div class="flex gap-3">
            <a href="{{ route('teachers.index') }}" class="px-5 py-2.5 bg-gray-200 text-gray-800 font-bold rounded-lg shadow-sm">Back</a>
            <a href="/teacher/edit/{{ $id }}" class="px-5 py-2.5 bg-themeGreen text-white font-bold rounded-lg shadow-sm">Edit</a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 p-8 mb-8 flex items-center gap-8">
        <img id="profile_photo" src="" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg bg-gray-100">
        <div>
            <h2 id="view_name" class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2">Teacher Name</h2>
            <p id="view_designation" class="text-lg text-gray-600 dark:text-gray-400 mb-4 font-medium">Designation</p>
            <div class="flex gap-3">
                <span class="px-4 py-1.5 bg-themeGreen/10 text-themeGreen rounded-full text-sm font-bold">ID: <span id="badge_id">...</span></span>
                <span class="px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-bold">Dept: <span id="badge_dept">...</span></span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="info-card">
            <h3 class="text-xl font-bold text-themeGreen mb-6 border-b pb-3">Account & Contact Info</h3>
            <div class="grid grid-cols-2 gap-y-6">
                <div class="col-span-2"><p class="info-label">Email Address (Login ID)</p><p class="info-value" id="view_email">...</p></div>
                <div><p class="info-label">Mobile Number</p><p class="info-value" id="view_phone">...</p></div>
                <div><p class="info-label">Joining Date</p><p class="info-value" id="view_joining">...</p></div>
            </div>
        </div>

        <div class="info-card">
            <h3 class="text-xl font-bold text-themeGreen mb-6 border-b pb-3">Personal Details</h3>
            <div class="grid grid-cols-2 gap-y-6">
                <div><p class="info-label">Gender</p><p class="info-value" id="view_gender">...</p></div>
                <div><p class="info-label">Blood Group</p><p class="info-value" id="view_blood">...</p></div>
                <div class="col-span-2"><p class="info-label">Full Address</p><p class="info-value" id="view_address">...</p></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const teacherId = "{{ $id }}";
    function getAuthHeaders() { return { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } }; }

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            let res = await axios.get(`/ajax/teachers/${teacherId}`, getAuthHeaders());
            let teacher = res.data.data;

            document.getElementById('view_name').innerText = teacher.user.name;
            document.getElementById('view_email').innerText = teacher.user.email;
            document.getElementById('view_designation').innerText = teacher.designation;
            document.getElementById('badge_id').innerText = teacher.employee_id;
            document.getElementById('badge_dept').innerText = teacher.department || 'N/A';
            
            document.getElementById('view_phone').innerText = teacher.phone;
            document.getElementById('view_joining').innerText = teacher.joining_date;
            document.getElementById('view_gender').innerText = teacher.gender;
            document.getElementById('view_blood').innerText = teacher.blood_group || 'N/A';
            document.getElementById('view_address').innerText = teacher.address;

            let photoUrl = teacher.photo ? '/storage/' + teacher.photo : `https://ui-avatars.com/api/?name=${teacher.user.name}&background=1e4630&color=fff&size=256`;
            document.getElementById('profile_photo').src = photoUrl;

            document.getElementById('loadingOverlay').classList.add('hidden');
        } catch (error) { alert("Failed to load profile!"); window.location.href = '/teachers'; }
    });
</script>
@endpush