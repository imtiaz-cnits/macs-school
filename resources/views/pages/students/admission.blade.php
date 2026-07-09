@extends('tyro-dashboard::layouts.admin')

@section('title', 'New Student Admission')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#009A49', themeBlue: '#008ED6', themeDark: '#070E14', themeNavy: '#0F1E2C' },
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
    /* Dropdown Trigger and Options Font Size Override */
    .relative button[type="button"] {
        font-size: 0.875rem !important; /* text-sm */
    }
    .relative div button {
        font-size: 0.875rem !important; /* text-sm */
    }
</style>
<script>
    function getAuthHeaders() {
        return { 
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            } 
        };
    }

    window.dropdownState = function(id, defaultLabel, ajaxUrl, dataKey, nameField, idField) {
        return {
            open: false,
            selectedLabel: defaultLabel,
            selectedValue: '',
            options: [],
            disabled: false,
            async init() {
                let input = document.getElementById(id);
                if (input) {
                    this.selectedValue = input.value;
                    this.disabled = input.disabled;
                    input.addEventListener('change', () => {
                        this.selectedValue = input.value;
                        this.disabled = input.disabled;
                        let found = this.options.find(o => o.id == input.value);
                        this.selectedLabel = found ? found.name : defaultLabel;
                    });
                }

                if (ajaxUrl) {
                    try {
                        let res = await axios.get(ajaxUrl, getAuthHeaders());
                        let list = res.data[dataKey] || [];
                        this.options = list.map(item => ({
                            id: item[idField],
                            name: item[nameField]
                        }));
                        // trigger initial sync if there's predefined value
                        if(this.selectedValue) {
                            let found = this.options.find(o => o.id == this.selectedValue);
                            if(found) this.selectedLabel = found.name;
                        }
                    } catch (e) {
                        console.error('Failed to load ' + id, e);
                    }
                }
            },
            select(option) {
                if (this.disabled) return;
                this.selectedLabel = option ? option.name : defaultLabel;
                this.selectedValue = option ? option.id : '';
                this.open = false;
                
                let input = document.getElementById(id);
                if (input) {
                    input.value = this.selectedValue;
                    input.dispatchEvent(new Event('change'));
                }
            }
        };
    };

    window.staticDropdownState = function(id, defaultLabel, rawOptions, initialValue = '', syncCallback = null) {
        let initialLabel = defaultLabel;
        if(initialValue) {
            let found = rawOptions.find(o => o.id === initialValue);
            if(found) initialLabel = found.name;
        }
        return {
            open: false,
            selectedLabel: initialLabel,
            selectedValue: initialValue,
            options: rawOptions,
            disabled: false,
            init() {
                let input = document.getElementById(id);
                if (input) {
                    input.value = this.selectedValue;
                    this.disabled = input.disabled;
                    input.addEventListener('change', () => {
                        this.selectedValue = input.value;
                        this.disabled = input.disabled;
                        let found = this.options.find(o => o.id == input.value);
                        this.selectedLabel = found ? found.name : defaultLabel;
                    });
                }
            },
            select(option) {
                if (this.disabled) return;
                this.selectedLabel = option ? option.name : defaultLabel;
                this.selectedValue = option ? option.id : '';
                this.open = false;
                
                let input = document.getElementById(id);
                if (input) {
                    input.value = this.selectedValue;
                    input.dispatchEvent(new Event('change'));
                }
                if (syncCallback) {
                    syncCallback();
                }
            }
        };
    };

    window.divisionOptions = [
        { id: 'Barishal', name: 'Barishal' },
        { id: 'Chattogram', name: 'Chattogram' },
        { id: 'Dhaka', name: 'Dhaka' },
        { id: 'Khulna', name: 'Khulna' },
        { id: 'Mymensingh', name: 'Mymensingh' },
        { id: 'Rajshahi', name: 'Rajshahi' },
        { id: 'Rangpur', name: 'Rangpur' },
        { id: 'Sylhet', name: 'Sylhet' }
    ];

    window.districtOptions = [
        { id: 'Bagerhat', name: 'Bagerhat' },
        { id: 'Bandarban', name: 'Bandarban' },
        { id: 'Barguna', name: 'Barguna' },
        { id: 'Barishal', name: 'Barishal' },
        { id: 'Bhola', name: 'Bhola' },
        { id: 'Bogura', name: 'Bogura' },
        { id: 'Brahmanbaria', name: 'Brahmanbaria' },
        { id: 'Chandpur', name: 'Chandpur' },
        { id: 'Chapainawabganj', name: 'Chapainawabganj' },
        { id: 'Chattogram', name: 'Chattogram' },
        { id: 'Chuadanga', name: 'Chuadanga' },
        { id: 'Cox\'s Bazar', name: 'Cox\'s Bazar' },
        { id: 'Cumilla', name: 'Cumilla' },
        { id: 'Dhaka', name: 'Dhaka' },
        { id: 'Dinajpur', name: 'Dinajpur' },
        { id: 'Faridpur', name: 'Faridpur' },
        { id: 'Feni', name: 'Feni' },
        { id: 'Gaibandha', name: 'Gaibandha' },
        { id: 'Gazipur', name: 'Gazipur' },
        { id: 'Gopalganj', name: 'Gopalganj' },
        { id: 'Habiganj', name: 'Habiganj' },
        { id: 'Jamalpur', name: 'Jamalpur' },
        { id: 'Jashore', name: 'Jashore' },
        { id: 'Jhalokati', name: 'Jhalokati' },
        { id: 'Jhenaidah', name: 'Jhenaidah' },
        { id: 'Joypurhat', name: 'Joypurhat' },
        { id: 'Khagrachhari', name: 'Khagrachhari' },
        { id: 'Khulna', name: 'Khulna' },
        { id: 'Kishoreganj', name: 'Kishoreganj' },
        { id: 'Kurigram', name: 'Kurigram' },
        { id: 'Kushtia', name: 'Kushtia' },
        { id: 'Lakshmipur', name: 'Lakshmipur' },
        { id: 'Lalmonirhat', name: 'Lalmonirhat' },
        { id: 'Madaripur', name: 'Madaripur' },
        { id: 'Magura', name: 'Magura' },
        { id: 'Manikganj', name: 'Manikganj' },
        { id: 'Meherpur', name: 'Meherpur' },
        { id: 'Moulvibazar', name: 'Moulvibazar' },
        { id: 'Munshiganj', name: 'Munshiganj' },
        { id: 'Mymensingh', name: 'Mymensingh' },
        { id: 'Naogaon', name: 'Naogaon' },
        { id: 'Narail', name: 'Narail' },
        { id: 'Narayanganj', name: 'Narayanganj' },
        { id: 'Narsingdi', name: 'Narsingdi' },
        { id: 'Natore', name: 'Natore' },
        { id: 'Netrokona', name: 'Netrokona' },
        { id: 'Nilphamari', name: 'Nilphamari' },
        { id: 'Noakhali', name: 'Noakhali' },
        { id: 'Pabna', name: 'Pabna' },
        { id: 'Panchagarh', name: 'Panchagarh' },
        { id: 'Patuakhali', name: 'Patuakhali' },
        { id: 'Pirojpur', name: 'Pirojpur' },
        { id: 'Rajbari', name: 'Rajbari' },
        { id: 'Rajshahi', name: 'Rajshahi' },
        { id: 'Rangamati', name: 'Rangamati' },
        { id: 'Rangpur', name: 'Rangpur' },
        { id: 'Satkhira', name: 'Satkhira' },
        { id: 'Shariatpur', name: 'Shariatpur' },
        { id: 'Sherpur', name: 'Sherpur' },
        { id: 'Sirajganj', name: 'Sirajganj' },
        { id: 'Sunamganj', name: 'Sunamganj' },
        { id: 'Sylhet', name: 'Sylhet' },
        { id: 'Tangail', name: 'Tangail' },
        { id: 'Thakurgaon', name: 'Thakurgaon' }
    ];
</script>
@endpush

@section('breadcrumb')
<span>Student Management</span>
@endsection

@section('content')
<!-- Header Section -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6 border-b border-gray-150 dark:border-white/[0.08] pb-6">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            New Student Admission
        </h1>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Pabna International School - Smart Education Management System</p>
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

<form id="admissionForm" onsubmit="event.preventDefault(); window.SaveStudent();">
    
    <!-- Section 1: Academic Details -->
    <div class="flex items-center gap-2 mb-6">
        <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-themeBlue to-themeGreen"></span>
        <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider font-secondary">Academic Details</h3>
    </div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5 mb-10 p-6 bg-gray-50/50 dark:bg-themeDark/30 rounded-3xl border border-gray-100 dark:border-white/[0.06]">
        <!-- Branch -->
        <div x-data="dropdownState('branch_id', 'Select Branch', '/ajax/branches', 'branchData', 'branch_name', 'id')" class="relative">
            <label class="form-label text-themeBlue">Branch <span class="required-star">*</span></label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Branch</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="branch_id" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

        <!-- Class -->
        <div x-data="dropdownState('class_id', 'Select Class', '/ajax/classes', 'classData', 'class_name', 'id')" class="relative">
            <label class="form-label text-themeBlue">Class <span class="required-star">*</span></label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Class</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="class_id" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

        <!-- Section -->
        <div x-data="dropdownState('section_id', 'Select Section', '/ajax/sections', 'sectionData', 'section_name', 'id')" class="relative">
            <label class="form-label text-themeBlue">Section <span class="required-star">*</span></label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Section</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="section_id" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

        <!-- Shift -->
        <div x-data="dropdownState('shift_id', 'Select Shift', '/ajax/shifts', 'shiftData', 'shift_name', 'id')" class="relative">
            <label class="form-label text-themeBlue">Shift</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Shift</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="shift_id" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                <button type="button" @click="select(null)" :class="selectedValue === '' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                    <span>Select Shift</span>
                </button>
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

        <!-- Session -->
        <div x-data="dropdownState('session_year_id', 'Select Session', '/ajax/sessions', 'sessionData', 'session_name', 'id')" class="relative">
            <label class="form-label text-themeBlue">Session <span class="required-star">*</span></label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Session</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="session_year_id" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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
    </div>

    <!-- Section 2: Basic Information -->
    <div class="flex items-center gap-2 mb-6">
        <span class="w-1.5 h-5 rounded-full bg-gradient-to-b from-themeBlue to-themeGreen"></span>
        <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider font-secondary">Basic Information</h3>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
        <div>
            <label class="form-label">Student Name <span class="required-star">*</span></label>
            <input type="text" id="student_name" class="form-input" placeholder="Full Name" required>
        </div>
        <div>
            <label class="form-label">Name in Bangla</label>
            <input type="text" id="name_in_bangla" class="form-input" placeholder="Name in Bangla">
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

        <!-- Gender -->
        <div x-data="staticDropdownState('gender', 'Select Gender', [{id: 'Male', name: 'Male'}, {id: 'Female', name: 'Female'}])" class="relative">
            <label class="form-label">Gender <span class="required-star">*</span></label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Select Gender</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="gender" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

        <!-- Religion -->
        <div x-data="staticDropdownState('religion', 'Islam', [{id: 'Islam', name: 'Islam'}, {id: 'Hindu', name: 'Hindu'}, {id: 'Christian', name: 'Christian'}, {id: 'Buddhist', name: 'Buddhist'}], 'Islam')" class="relative">
            <label class="form-label">Religion</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Islam</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="religion" value="Islam">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

        <!-- Blood Group -->
        <div x-data="staticDropdownState('blood_group', 'None', [{id: '', name: 'None'}, {id: 'A+', name: 'A+'}, {id: 'A-', name: 'A-'}, {id: 'B+', name: 'B+'}, {id: 'B-', name: 'B-'}, {id: 'O+', name: 'O+'}, {id: 'O-', name: 'O-'}, {id: 'AB+', name: 'AB+'}, {id: 'AB-', name: 'AB-'}], '')" class="relative">
            <label class="form-label">Blood Group</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">None</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="blood_group" value="">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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
            <label class="form-label">Student Email</label>
            <input type="email" id="email" class="form-input" placeholder="Email Address">
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

        <!-- SMS Status -->
        <div x-data="staticDropdownState('sms_status', 'Active', [{id: 'Active', name: 'Active'}, {id: 'Inactive', name: 'Inactive'}], 'Active')" class="relative">
            <label class="form-label">SMS Status</label>
            <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                <span x-text="selectedLabel">Active</span>
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <input type="hidden" id="sms_status" value="Active">
            
            <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

        <!-- Student Photo -->
        <div>
            <label class="form-label">Student Photo</label>
            <div class="flex items-center gap-3 bg-gray-50/50 dark:bg-themeDark/40 p-1.5 rounded-xl border-2 border-gray-100 dark:border-gray-800 h-11">
                <div class="w-8 h-8 shrink-0 bg-gray-100 dark:bg-themeNavy/50 flex items-center justify-center rounded-lg border border-dashed border-gray-300 dark:border-gray-700 overflow-hidden relative">
                    <img id="photoPreview" src="" alt="Preview" class="w-full h-full object-cover hidden absolute inset-0 z-10">
                    <svg id="photoIcon" class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div class="flex-1 relative h-full">
                    <input type="file" id="photo" onchange="window.previewImage(event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/jpg">
                    <div class="h-full flex items-center justify-center bg-gray-105 hover:bg-gray-205 dark:bg-gray-800 dark:hover:bg-themeNavy/50 text-gray-750 dark:text-gray-300 text-[10px] font-black uppercase tracking-wider rounded-lg transition border border-gray-200 dark:border-gray-855/80">
                        Choose Photo
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Upload -->
        <div>
            <label class="form-label">Document (Birth Cert/NID)</label>
            <div class="relative flex items-center justify-between gap-3 bg-gray-50/50 dark:bg-themeDark/40 p-1.5 rounded-xl border-2 border-gray-100 dark:border-gray-800 h-11 cursor-pointer hover:bg-gray-100/50 dark:hover:bg-themeDark/60 transition" onclick="document.getElementById('document_file').click()">
                <input type="file" id="document_file" class="hidden" accept=".pdf, image/jpeg, image/png, image/jpg" onchange="window.previewDocument(event)">
                
                <div id="docPlaceholder" class="flex items-center text-gray-400 pl-1.5 w-full">
                    <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    <span class="text-xs font-semibold">Upload PDF/Image</span>
                </div>

                <div id="docPreviewInfo" class="hidden flex items-center justify-between w-full pl-1.5 pr-1">
                    <div class="flex items-center overflow-hidden">
                        <svg class="w-4 h-4 text-emerald-550 mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <div class="truncate max-w-[120px]">
                            <p id="docFileName" class="text-[10px] font-bold text-gray-700 dark:text-gray-300 truncate"></p>
                            <p id="docFileSize" class="text-[8px] text-gray-500"></p>
                        </div>
                    </div>
                    <button type="button" onclick="window.removeDocument(event)" class="text-red-500 hover:text-red-700 p-1 shrink-0 bg-red-50 dark:bg-red-950/20 rounded-md shadow-sm transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
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
        <!-- Father's Details -->
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

        <!-- Mother's Details -->
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

        <!-- Emergency Contact -->
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
        <!-- Present Address -->
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

                <!-- Present District -->
                <div x-data="staticDropdownState('present_district', 'Select District', window.districtOptions, '', window.syncAddress)" class="relative">
                    <label class="form-label">District <span class="required-star">*</span></label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span x-text="selectedLabel">Select District</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" id="present_district" value="">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

                <!-- Present Division -->
                <div x-data="staticDropdownState('present_division', 'Select Division', window.divisionOptions, '', window.syncAddress)" class="relative">
                    <label class="form-label">Division <span class="required-star">*</span></label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span x-text="selectedLabel">Select Division</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" id="present_division" value="">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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
            </div>
        </div>

        <!-- Permanent Address -->
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

                <!-- Permanent District -->
                <div x-data="staticDropdownState('permanent_district', 'Select District', window.districtOptions, '')" class="relative">
                    <label class="form-label">District <span class="required-star">*</span></label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left" :disabled="disabled" :class="disabled ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-themeDark/30 border-gray-200 dark:border-gray-850/50' : ''">
                        <span x-text="selectedLabel">Select District</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" id="permanent_district" value="">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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

                <!-- Permanent Division -->
                <div x-data="staticDropdownState('permanent_division', 'Select Division', window.divisionOptions, '')" class="relative">
                    <label class="form-label">Division <span class="required-star">*</span></label>
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 h-11 text-xs font-semibold bg-gray-50/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left" :disabled="disabled" :class="disabled ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-themeDark/30 border-gray-200 dark:border-gray-855/50' : ''">
                        <span x-text="selectedLabel">Select Division</span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <input type="hidden" id="permanent_division" value="">
                    
                    <div x-show="open" x-cloak @click.away="open = false" x-transition class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
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
            </div>
        </div>
    </div>

    <!-- Action Buttons Footer -->
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between pt-8 border-t border-gray-100 dark:border-white/[0.06] mt-8">
        <div class="flex gap-3 w-full sm:w-auto">
            <a href="{{ route('students.index') }}" class="btn-sm bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-450 border border-rose-100 dark:border-rose-900/30 rounded-xl hover:-translate-y-0.5 hover:shadow-md transition-all font-black uppercase tracking-wider text-center flex items-center justify-center !h-10 !px-8">Close</a>
            <button type="reset" class="btn-sm bg-gray-150 dark:bg-themeNavy/50 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-800 rounded-xl hover:-translate-y-0.5 hover:shadow-md transition-all font-black uppercase tracking-wider !h-10 !px-8">Reset</button>
        </div>
        
        <button type="submit" class="btn bg-gradient-to-r from-themeBlue to-themeGreen text-white border-none rounded-xl hover:-translate-y-0.5 hover:shadow-lg transition-all font-black uppercase tracking-widest !h-11 !px-16 w-full sm:w-auto">
            Confirm Admission
        </button>
    </div>
</form>

<!-- Footer Branding -->
<div class="mt-12 text-center border-t border-gray-50 dark:border-white/[0.04] pt-6">
    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.4em]">
        Powered by <a href="https://www.codenextit.com" target="_blank" class="text-themeBlue dark:text-themeBlue/80 hover:underline decoration-2">Code Next IT</a>
    </p>
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
                    this.regenerateId();
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
            
            regenerateId() {
                this.randId = String(Math.floor(1000 + Math.random() * 9000));
                this.updateIdentity();
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
                    // Dispatch change event to let potential observers know
                    inputEl.dispatchEvent(new Event('change'));
                }
            }
        };
    };

    // PHOTO PREVIEW (with SVG hide/show)
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

    // DOCUMENT PREVIEW
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
            // Dispatch change event to let Alpine components update UI
            permanent.dispatchEvent(new Event('change'));
        });
    };

    window.syncAddress = function() {
        if(document.getElementById('same_as_present').checked) {
            ['village', 'post_office', 'post_code', 'district', 'division'].forEach(f => {
                const present = document.getElementById('present_' + f);
                const permanent = document.getElementById('permanent_' + f);
                permanent.value = present.value;
                // Dispatch change event to let Alpine components update UI
                permanent.dispatchEvent(new Event('change'));
            });
        }
    };

    window.SaveStudent = async function() {
        let formData = new FormData();
        
        const fields = [
            'roll_number', 'student_name', 'card_number', 'student_identity',
            'name_in_bangla', 'dob', 'gender', 'blood_group', 'religion', 'email',
            'father_name', 'father_name_bn', 'father_nid', 'father_mobile', 'father_occupation',
            'mother_name', 'mother_name_bn', 'mother_nid', 'mother_mobile', 'mother_occupation',
            'present_village', 'present_post_office', 'present_post_code', 'present_district', 'present_division',
            'permanent_village', 'permanent_post_office', 'permanent_post_code', 'permanent_district', 'permanent_division',
            'guardian_name', 'guardian_occupation', 'guardian_mobile', 'sms_status', 'branch_id', 'class_id', 'section_id', 
            'shift_id', 'session_year_id', 'birth_certificate'
        ];

        // Frontend validation for required fields
        const requiredFields = ['branch_id', 'class_id', 'section_id', 'session_year_id', 'student_name', 'roll_number', 'dob', 'gender', 'father_name', 'father_mobile', 'mother_name', 'mother_mobile', 'guardian_mobile', 'present_village', 'present_post_office', 'present_district', 'present_division', 'permanent_village', 'permanent_post_office', 'permanent_district', 'permanent_division'];
        for(let f of requiredFields) {
            const el = document.getElementById(f);
            if(!el || !el.value.trim()) {
                let prettyName = f.replace('_id', '').replace('_', ' ').toUpperCase();
                showAlert(`Please select or fill in the required field: ${prettyName}`, "Attention");
                return;
            }
        }

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
                await showAlert(`SUCCESS!\nIdentity: ${res.data.identity}`, "Success");
                window.location.href = '/students';
            }
        } catch (err) {
            showAlert(err.response?.data?.message || 'Check required fields or file sizes!', "Error");
        } finally {
            document.querySelector('button[type="submit"]').innerText = 'Confirm Admission';
            document.querySelector('button[type="submit"]').disabled = false;
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