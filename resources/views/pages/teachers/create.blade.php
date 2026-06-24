@extends('tyro-dashboard::layouts.admin')

@section('title', 'Add New Teacher')

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
                colors: { themeGreen: '#1e4630', themeRed: '#cc0000', themePink: '#d97782' },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    .form-label { @apply block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5; }
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-1 focus:ring-themeGreen outline-none transition; }
    .required-star { @apply text-green-700 dark:text-green-500 ml-1; }
    .section-title { @apply text-lg font-bold text-themeGreen dark:text-green-500 border-b border-gray-200 dark:border-gray-700 pb-2 mb-6; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1200px] mx-auto">
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 md:p-10 border border-gray-100 dark:border-gray-700">
        
        <h2 class="text-3xl md:text-4xl font-extrabold text-themeGreen dark:text-green-500 text-center mb-10">
            Add New Teacher
        </h2>

        <form id="teacherForm" onsubmit="event.preventDefault(); window.SaveTeacher();">
            
            <h3 class="section-title">1. Login Account Details (For Dashboard Access)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div>
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" id="name" class="form-input" placeholder="Enter full name" required>
                </div>
                <div>
                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" id="email" class="form-input" placeholder="Will be used for login" required>
                </div>
                <div>
                    <label class="form-label">Password <span class="required-star">*</span></label>
                    <input type="password" id="password" class="form-input" placeholder="Create a strong password" required minlength="6">
                </div>
            </div>

            <h3 class="section-title">2. Professional & Personal Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div>
                    <label class="form-label">Employee ID <span class="required-star">*</span></label>
                    <input type="text" id="employee_id" class="form-input" placeholder="e.g. TEA-2024-01" required>
                </div>
                <div>
                    <label class="form-label">Designation <span class="required-star">*</span></label>
                    <input type="text" id="designation" class="form-input" placeholder="e.g. Senior Teacher" required>
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <input type="text" id="department" class="form-input" placeholder="e.g. Science, Mathematics">
                </div>

                <div>
                    <label class="form-label">Mobile Number <span class="required-star">*</span></label>
                    <input type="text" id="phone" class="form-input" placeholder="Enter mobile number" required>
                </div>
                <div>
                    <label class="form-label">Joining Date <span class="required-star">*</span></label>
                    <input type="date" id="joining_date" class="form-input" required>
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
                    <label class="form-label">Blood Group</label>
                    <select id="blood_group" class="form-input">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="form-label">Present/Permanent Address <span class="required-star">*</span></label>
                    <input type="text" id="address" class="form-input" placeholder="Enter full address" required>
                </div>

                <div>
                    <label class="form-label">Teacher's Photo</label>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 shrink-0 bg-gray-100 dark:bg-gray-700 flex items-center justify-center rounded border border-dashed border-gray-300 dark:border-gray-500 overflow-hidden">
                            <img id="photoPreview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                            <svg id="photoIcon" class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="flex-1 relative">
                            <input type="file" id="photo" onchange="window.previewImage(event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/png, image/jpeg, image/gif">
                            <div class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white text-center font-bold py-2 px-4 rounded transition">Choose Photo</div>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Max size: 1 MB</p>
                </div>

            </div>

            <div class="mt-10 flex flex-wrap gap-4 items-center border-t border-gray-200 dark:border-gray-700 pt-6">
                <a href="{{ route('teachers.index') }}" class="bg-themeRed hover:bg-red-800 text-white font-bold py-2.5 px-8 rounded shadow transition w-full md:w-auto text-center">Close</a>
                <button type="reset" class="bg-themePink hover:bg-rose-500 text-white font-bold py-2.5 px-8 rounded shadow transition w-full md:w-auto text-center">Reset</button>
                <div class="flex-grow"></div>
                <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-bold py-2.5 px-16 rounded shadow transition w-full lg:w-auto text-center">Save Teacher</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    // ইমেজ প্রিভিউ লজিক
    window.previewImage = function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('photoPreview');
        const icon = document.getElementById('photoIcon');

        if (file) {
            if (file.size > 1024 * 1024) { 
                alert("File is too large! Maximum allowed size is 1MB.");
                event.target.value = "";
                preview.src = "";
                preview.classList.add('hidden');
                icon.classList.remove('hidden');
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            icon.classList.add('hidden');
        } else {
            preview.src = "";
            preview.classList.add('hidden');
            icon.classList.remove('hidden');
        }
    };

    // ফর্ম সাবমিট
    window.SaveTeacher = async function() {
        let formData = new FormData();

        // Login Info
        formData.append('name', document.getElementById('name').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('password', document.getElementById('password').value);

        // Profile Info
        formData.append('employee_id', document.getElementById('employee_id').value);
        formData.append('designation', document.getElementById('designation').value);
        formData.append('department', document.getElementById('department').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('address', document.getElementById('address').value);
        formData.append('gender', document.getElementById('gender').value);
        formData.append('blood_group', document.getElementById('blood_group').value);
        formData.append('joining_date', document.getElementById('joining_date').value);

        let photoFile = document.getElementById('photo').files[0];
        if(photoFile) formData.append('photo', photoFile);

        try {
            let saveBtn = document.querySelector('button[type="submit"]');
            saveBtn.innerText = 'Saving...';
            saveBtn.disabled = true;

            let res = await axios.post('/ajax/teachers', formData, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (res.status === 201 || res.data.status === 'success') {
                alert('Teacher added and user account created successfully!');
                window.location.href = '/teachers'; // সাবমিট হওয়ার পর লিস্ট পেজে যাবে
            }
        } catch (error) {
            console.error(error);
            let errorMsg = 'Failed to save teacher data!';
            if(error.response && error.response.data && error.response.data.message) {
                // ইমেইল বা আইডি ডুপ্লিকেট হলে লারাভেলের মেসেজ দেখাবে
                errorMsg = error.response.data.message; 
            }
            alert(errorMsg);
        } finally {
            let saveBtn = document.querySelector('button[type="submit"]');
            saveBtn.innerText = 'Save Teacher';
            saveBtn.disabled = false;
        }
    };
</script>
@endpush