@extends('tyro-dashboard::layouts.admin')

@section('title', 'Subject Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    select option { background: #ffffff; color: #1f2937; }
    .dark select option { background: #0f1e2c; color: #ffffff; }
    .table th { background-color: transparent !important; }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Global Subjects
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Configure global course curricula, subjects, and lesson topics</p>
        </div>
        
        <button onclick="window.openAddSubjectModal()" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Subject
        </button>
    </div>

    <!-- Search filter -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 mb-6 flex gap-4 items-center shadow-sm">
        <div class="flex-grow relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" id="filter_search" placeholder="Search by Subject Name or Code..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 pl-10 pr-3 placeholder-gray-400">
        </div>
        <button onclick="window.resetFilter()" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all shadow-sm shrink-0">Reset</button>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">SL</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Subject Info</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-36">Type</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-48">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableList">
                    <tr>
                        <td colspan="4" class="py-12">
                            <div class="flex flex-col items-center gap-3 animate-pulse justify-center">
                                <div class="h-4 w-24 bg-gray-200 dark:bg-gray-800 rounded-md"></div>
                                <span class="text-xs text-gray-455 dark:text-gray-500 font-bold uppercase tracking-widest">Loading Subjects...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div id="addSubjectModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-lg rounded-3xl shadow-xl border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Add New Subject</h3>
            <button onclick="window.closeModal('addSubjectModal')" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="addSubjectForm">
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-900 dark:text-white">
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Class <span class="text-red-500 ml-0.5">*</span></label>
                    <div x-data="{ 
                        open: false, 
                        value: '', 
                        label: 'Choose Class...',
                        items: [],
                        init() {
                            window.addEventListener('classes-loaded', () => {
                                this.items = window.classesList.map(c => ({ value: c.id, label: c.class_name }));
                            });
                            window.addEventListener('set-add-class', e => {
                                this.value = e.detail;
                                let match = this.items.find(i => i.value == this.value);
                                this.label = match ? match.label : 'Choose Class...';
                            });
                        },
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                            let inp = this.$refs.hiddenInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-450 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="class_id" x-ref="hiddenInput" value="" required>
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Subject Name <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" name="subject_name" required placeholder="e.g. Mathematics" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Subject Code</label>
                    <input type="text" name="subject_code" placeholder="e.g. MAT-101" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400">
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Subject Type</label>
                    <div x-data="{ 
                        open: false, 
                        value: 'Theory', 
                        label: 'Theory',
                        items: [
                            { value: 'Theory', label: 'Theory' },
                            { value: 'Practical', label: 'Practical' },
                            { value: 'Objective', label: 'Objective' },
                            { value: 'Both', label: 'Both' }
                        ],
                        init() {
                            window.addEventListener('set-add-type', e => {
                                this.value = e.detail;
                                let match = this.items.find(i => i.value == this.value);
                                this.label = match ? match.label : 'Theory';
                            });
                        },
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                            let inp = this.$refs.hiddenInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="subject_type" x-ref="hiddenInput" value="Theory">
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value === item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-themeDark/50 flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.05]">
                <button type="button" onclick="window.closeModal('addSubjectModal')" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Save Subject</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Subject Modal -->
<div id="editSubjectModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4">
    <div class="bg-white dark:bg-themeNavy w-full max-w-lg rounded-3xl shadow-xl border border-gray-100 dark:border-white/[0.08]">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Edit Subject</h3>
            <button onclick="window.closeModal('editSubjectModal')" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="editSubjectForm">
            <input type="hidden" id="edit_subject_id">
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-900 dark:text-white">
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Class <span class="text-red-500 ml-0.5">*</span></label>
                    <div x-data="{ 
                        open: false, 
                        value: '', 
                        label: 'Choose Class...',
                        items: [],
                        init() {
                            window.addEventListener('classes-loaded', () => {
                                this.items = window.classesList.map(c => ({ value: c.id, label: c.class_name }));
                            });
                            window.addEventListener('set-edit-class', e => {
                                this.value = e.detail;
                                let match = this.items.find(i => i.value == this.value);
                                this.label = match ? match.label : 'Choose Class...';
                            });
                        },
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                            let inp = this.$refs.hiddenInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-450 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="class_id" id="edit_class_id" x-ref="hiddenInput" value="" required>
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Subject Name <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="edit_subject_name" name="subject_name" required class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3">
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Subject Code</label>
                    <input type="text" id="edit_subject_code" name="subject_code" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3">
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Subject Type</label>
                    <div x-data="{ 
                        open: false, 
                        value: 'Theory', 
                        label: 'Theory',
                        items: [
                            { value: 'Theory', label: 'Theory' },
                            { value: 'Practical', label: 'Practical' },
                            { value: 'Objective', label: 'Objective' },
                            { value: 'Both', label: 'Both' }
                        ],
                        init() {
                            window.addEventListener('set-edit-type', e => {
                                this.value = e.detail;
                                let match = this.items.find(i => i.value == this.value);
                                this.label = match ? match.label : 'Theory';
                            });
                        },
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                            let inp = this.$refs.hiddenInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="subject_type" id="edit_subject_type" x-ref="hiddenInput" value="Theory">
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value === item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value === item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-themeDark/50 flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.05]">
                <button type="button" onclick="window.closeModal('editSubjectModal')" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Update Subject</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    window.openModal = id => { document.getElementById(id).classList.remove('hidden'); document.getElementById(id).classList.add('modal-active'); };
    window.closeModal = id => { document.getElementById(id).classList.remove('modal-active'); document.getElementById(id).classList.add('hidden'); };

    const getAuthHeaders = () => ({ 
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
    });

    window.classesList = [];

    // সব ক্লাসের তথ্য লোড করা
    window.fetchClasses = async function() {
        try {
            let res = await axios.get("/ajax/classes", getAuthHeaders());
            window.classesList = res.data.classData || [];
            
            // Dispatch classes loaded event
            window.dispatchEvent(new CustomEvent('classes-loaded'));
        } catch (e) {
            console.error("Failed to load classes", e);
        }
    };

    // ১. সাবজেক্ট লিস্ট ফেচ করা
    window.fetchList = async function() {
        let list = document.getElementById('tableList');
        let search = document.getElementById('filter_search').value;
        let q = new URLSearchParams({ search }).toString();

        try {
            let res = await axios.get(`/ajax/subjects?${q}`, getAuthHeaders());
            let data = res.data.subjectData || [];
            list.innerHTML = data.length ? '' : `<tr><td colspan="4" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">No subjects found.</td></tr>`;

            data.forEach((item, index) => {
                list.innerHTML += `
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">${index + 1}</td>
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">${item.subject_name}</div>
                            <div class="text-[9px] font-black text-gray-400 dark:text-gray-550 uppercase mt-1">
                                Code: ${item.subject_code || 'N/A'} &nbsp;|&nbsp; Class: <span class="text-themeBlue font-black">${item.class ? item.class.class_name : 'N/A'}</span>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="px-2.5 py-1 bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-650 dark:text-gray-300 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block">${item.subject_type}</span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center justify-end gap-2.5">
                                <!-- Edit Button -->
                                <button onclick="window.EditSubject(${item.id})" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Subject">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <!-- Delete Button -->
                                <button onclick="window.DeleteID(${item.id})" class="action-btn text-red-650 hover:text-red-800 hover:border-red-600" title="Delete Subject">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });
        } catch (e) { list.innerHTML = `<tr><td colspan="4" class="py-12 text-center text-red-500 font-bold">Error loading data.</td></tr>`; }
    };

    // ২. সাবজেক্ট এডিট (ডাটা ফিল করা)
    window.EditSubject = async function(id) {
        try {
            let res = await axios.get(`/ajax/subjects/${id}/edit`, getAuthHeaders());
            let s = res.data.data;
            document.getElementById('edit_subject_id').value = s.id;
            
            // Dispatch events to update Alpine dropdown components
            window.dispatchEvent(new CustomEvent('set-edit-class', { detail: s.class_id || '' }));
            window.dispatchEvent(new CustomEvent('set-edit-type', { detail: s.subject_type || 'Theory' }));

            document.getElementById('edit_subject_name').value = s.subject_name;
            document.getElementById('edit_subject_code').value = s.subject_code || '';
            
            window.openModal('editSubjectModal');
        } catch (e) { showAlert("Failed to fetch subject data.", "Error"); }
    };

    window.openAddSubjectModal = function() {
        window.dispatchEvent(new CustomEvent('set-add-class', { detail: '' }));
        window.dispatchEvent(new CustomEvent('set-add-type', { detail: 'Theory' }));
        document.getElementById('addSubjectForm').reset();
        window.openModal('addSubjectModal');
    };

    // ৩. সেভ এবং আপডেট
    const handleForm = async (e, url, method, modalId) => {
        e.preventDefault();
        let data = Object.fromEntries(new FormData(e.target).entries());
        try {
            let res = await axios({ method, url, data, ...getAuthHeaders() });
            if (res.status === 200 || res.status === 201) {
                window.closeModal(modalId);
                window.fetchList();
                e.target.reset();
                showSuccess("Subject details saved successfully.");
            }
        } catch (err) { showAlert("Action Failed: " + (err.response.data.message || "Error"), "Error"); }
    };

    document.getElementById('addSubjectForm').onsubmit = e => handleForm(e, '/ajax/subjects', 'post', 'addSubjectModal');
    document.getElementById('editSubjectForm').onsubmit = e => {
        let id = document.getElementById('edit_subject_id').value;
        handleForm(e, `/ajax/subjects/${id}`, 'put', 'editSubjectModal');
    };

    // ৪. ডিলিট লজিক
    window.DeleteID = async function(id) {
        if (await showDanger('Delete Subject', 'Are you sure you want to delete this global subject? This action cannot be undone.')) {
            try {
                await axios.delete(`/ajax/subjects/${id}`, getAuthHeaders());
                window.fetchList();
                showSuccess("Subject deleted successfully.");
            } catch (e) { 
                showAlert("Delete failed!", "Error"); 
            }
        }
    };

    window.resetFilter = () => { document.getElementById('filter_search').value = ""; window.fetchList(); };
    
    // Live Search
    let typingTimer;
    document.getElementById('filter_search').oninput = () => {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(window.fetchList, 400);
    };

    // Initial Load
    document.addEventListener('DOMContentLoaded', async () => {
        await window.fetchClasses();
        await window.fetchList();
    });
</script>
@endpush