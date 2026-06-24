@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Teacher Information')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630', themeRed: '#cc0000', themePink: '#d97782' }, fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } } } }
</script>
<style>
    .form-label { @apply block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5; }
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-1 focus:ring-themeGreen outline-none transition; }
    .required-star { @apply text-green-700 dark:text-green-500 ml-1; }
    .section-title { @apply text-lg font-bold text-themeGreen dark:text-green-500 border-b border-gray-200 dark:border-gray-700 pb-2 mb-6; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1200px] mx-auto relative">
    
    <div id="loadingOverlay" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 z-50 flex items-center justify-center backdrop-blur-sm rounded-lg">
        <div class="text-center">
            <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-themeGreen rounded-full animate-spin mb-3"></div>
            <p class="text-lg font-bold text-gray-700 dark:text-gray-300">Loading Data...</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 md:p-10 border border-gray-100 dark:border-gray-700">
        
        <h2 class="text-3xl md:text-4xl font-extrabold text-themeGreen dark:text-green-500 text-center mb-10">
            Edit Teacher Information
        </h2>

        <form id="teacherForm" onsubmit="event.preventDefault(); window.UpdateTeacher();">
            
            <h3 class="section-title">1. Login Account Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div>
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" id="name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" id="email" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" id="password" class="form-input" placeholder="Leave blank to keep current password">
                </div>
            </div>

            <h3 class="section-title">2. Professional Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="form-label">Employee ID <span class="required-star">*</span></label>
                    <input type="text" id="employee_id" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Designation <span class="required-star">*</span></label>
                    <input type="text" id="designation" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <input type="text" id="department" class="form-input">
                </div>
                <div>
                    <label class="form-label">Mobile Number <span class="required-star">*</span></label>
                    <input type="text" id="phone" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Joining Date <span class="required-star">*</span></label>
                    <input type="date" id="joining_date" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Gender <span class="required-star">*</span></label>
                    <select id="gender" class="form-input" required>
                        <option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Blood Group</label>
                    <select id="blood_group" class="form-input">
                        <option value="">Select</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="O+">O+</option><option value="O-">O-</option><option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="form-label">Address <span class="required-star">*</span></label>
                    <input type="text" id="address" class="form-input" required>
                </div>

                <div>
                    <label class="form-label">Teacher's Photo</label>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 shrink-0 bg-gray-100 flex items-center justify-center rounded border overflow-hidden">
                            <img id="photoPreview" src="" class="w-full h-full object-cover hidden">
                        </div>
                        <div class="flex-1 relative">
                            <input type="file" id="photo" onchange="window.previewImage(event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/png, image/jpeg, image/gif">
                            <div class="bg-gray-200 text-center font-bold py-2 px-4 rounded">Change Photo</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap gap-4 items-center border-t border-gray-200 dark:border-gray-700 pt-6">
                <a href="{{ route('teachers.index') }}" class="bg-themeRed text-white font-bold py-2.5 px-8 rounded shadow">Cancel</a>
                <div class="flex-grow"></div>
                <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-bold py-2.5 px-16 rounded shadow">Update Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const teacherId = "{{ $id }}";

    function getAuthHeaders() {
        return { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') } };
    }

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            let res = await axios.get(`/ajax/teachers/${teacherId}`, getAuthHeaders());
            let teacher = res.data.data;
            
            document.getElementById('name').value = teacher.user.name;
            document.getElementById('email').value = teacher.user.email;
            
            document.getElementById('employee_id').value = teacher.employee_id;
            document.getElementById('designation').value = teacher.designation;
            document.getElementById('department').value = teacher.department || '';
            document.getElementById('phone').value = teacher.phone;
            document.getElementById('joining_date').value = teacher.joining_date;
            document.getElementById('gender').value = teacher.gender;
            document.getElementById('blood_group').value = teacher.blood_group || '';
            document.getElementById('address').value = teacher.address;

            if(teacher.photo) {
                let preview = document.getElementById('photoPreview');
                preview.src = '/storage/' + teacher.photo;
                preview.classList.remove('hidden');
            }
            document.getElementById('loadingOverlay').classList.add('hidden');
        } catch (error) { alert("Failed to load data."); window.location.href = '/teachers'; }
    });

    window.previewImage = function(event) {
        const file = event.target.files[0];
        if (file) {
            document.getElementById('photoPreview').src = URL.createObjectURL(file);
            document.getElementById('photoPreview').classList.remove('hidden');
        }
    };

    window.UpdateTeacher = async function() {
        let formData = new FormData();
        formData.append('_method', 'PUT'); // লারাভেল PUT ট্রিক
        
        formData.append('name', document.getElementById('name').value);
        formData.append('email', document.getElementById('email').value);
        let password = document.getElementById('password').value;
        if(password) formData.append('password', password);

        formData.append('employee_id', document.getElementById('employee_id').value);
        formData.append('designation', document.getElementById('designation').value);
        formData.append('department', document.getElementById('department').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('joining_date', document.getElementById('joining_date').value);
        formData.append('gender', document.getElementById('gender').value);
        formData.append('blood_group', document.getElementById('blood_group').value);
        formData.append('address', document.getElementById('address').value);

        let photoFile = document.getElementById('photo').files[0];
        if(photoFile) formData.append('photo', photoFile);

        try {
            document.querySelector('button[type="submit"]').innerText = 'Updating...';
            let res = await axios.post(`/ajax/teachers/${teacherId}`, formData, {
                headers: { 'Accept': 'application/json', 'Content-Type': 'multipart/form-data', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            if (res.status === 200) { alert('Updated successfully!'); window.location.href = '/teachers'; }
        } catch (error) { alert(error.response?.data?.message || 'Update failed!'); document.querySelector('button[type="submit"]').innerText = 'Update Changes'; }
    };
</script>
@endpush