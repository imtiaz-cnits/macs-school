@extends('tyro-dashboard::layouts.admin')

@section('title', 'Add New Staff')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div class="w-full">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Add New Staff
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Register a new staff member and automatically create their dashboard login credentials</p>
        </div>
    </div>

    <!-- Form Panel Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-10 shadow-sm hover:shadow-md transition-all duration-300">
        
        <form id="teacherForm" onsubmit="event.preventDefault(); window.SaveTeacher();">
            
            <!-- Section 1 -->
            <h3 class="text-xs font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase border-b border-gray-100 dark:border-white/[0.05] pb-2 mb-6">1. Login Account Details (For Dashboard Access)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-2 block">Full Name <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="name" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Enter full name" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-2 block">Email Address <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="email" id="email" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Will be used for login" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-2 block">Password <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="password" id="password" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Create a strong password" required minlength="6">
                </div>
            </div>

            <!-- Section 2 -->
            <h3 class="text-xs font-black tracking-widest text-gray-400 dark:text-gray-500 uppercase border-b border-gray-100 dark:border-white/[0.05] pb-2 mb-6">2. Professional & Personal Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-550 dark:text-gray-400 uppercase mb-2 block">Employee ID <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="employee_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. TEA-2024-01" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Designation <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="designation" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. Senior Teacher" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Department</label>
                    <input type="text" id="department" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. Science, Mathematics">
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Mobile Number <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="phone" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Enter mobile number" required>
                </div>
                
                <!-- Custom Date Picker Component -->
                <div class="relative" x-data="datePicker('')" @date-selected.window="if($event.detail) value = $event.detail" @click.away="show = false">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Joining Date <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="hidden" id="joining_date" :value="value">
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

                <div class="relative">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Gender <span class="text-red-500 ml-0.5">*</span></label>
                    <select id="gender" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="relative">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Blood Group</label>
                    <select id="blood_group" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Present/Permanent Address <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="address" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="Enter full address" required>
                </div>

                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Teacher's Photo</label>
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 shrink-0 bg-gray-50/50 dark:bg-themeDark border-2 border-dashed border-gray-100 dark:border-gray-800 flex items-center justify-center rounded-xl overflow-hidden shadow-sm">
                            <img id="photoPreview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                            <svg id="photoIcon" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="flex-1 relative">
                            <input type="file" id="photo" onchange="window.previewImage(event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/png, image/jpeg, image/gif">
                            <div class="h-11 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-250 text-xs font-black rounded-xl flex items-center justify-center transition-all cursor-pointer w-full uppercase tracking-wider">Choose Photo</div>
                        </div>
                    </div>
                    <p class="text-[9px] text-gray-400 dark:text-gray-500 mt-1">Max size: 1 MB</p>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="mt-10 flex flex-wrap gap-4 items-center border-t border-gray-100 dark:border-white/[0.05] pt-6">
                <a href="{{ route('teachers.index') }}" class="h-11 px-8 border-2 border-red-200 hover:bg-red-50 text-red-600 dark:border-red-950 dark:hover:bg-red-950/20 font-black rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center">Close</a>
                <button type="reset" class="h-11 px-8 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-600 dark:text-gray-300 font-black rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center">Reset</button>
                <div class="flex-grow"></div>
                <button type="submit" class="h-11 px-12 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Save Teacher</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue,
            currentYear: new Date(initialValue || new Date()).getFullYear(),
            currentMonth: new Date(initialValue || new Date()).getMonth(),
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

    // Image Preview
    window.previewImage = function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('photoPreview');
        const icon = document.getElementById('photoIcon');

        if (file) {
            if (file.size > 1024 * 1024) { 
                showAlert("File is too large! Maximum allowed size is 1MB.", "File Too Large");
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

    // Save Staff Form Submit
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
                showSuccess("Staff member added and user portal account created successfully!");
                setTimeout(() => {
                    window.location.href = '/teachers';
                }, 1000);
            }
        } catch (error) {
            console.error(error);
            let errorMsg = 'Failed to save staff data!';
            if(error.response && error.response.data && error.response.data.message) {
                errorMsg = error.response.data.message; 
            }
            showAlert(errorMsg, "Registration Failed");
        } finally {
            let saveBtn = document.querySelector('button[type="submit"]');
            saveBtn.innerText = 'Save Teacher';
            saveBtn.disabled = false;
        }
    };
</script>
@endpush