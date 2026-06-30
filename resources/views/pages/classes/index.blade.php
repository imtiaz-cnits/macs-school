@extends('tyro-dashboard::layouts.admin')

@section('title', 'Class Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
                },
                fontFamily: { 
                    sans: ['Figtree', 'Onest', 'sans-serif'] 
                } 
            } 
        }
    }
</script>
<style>
    [x-cloak] { display: none !important; }
    .table th, .table td { padding: 0.625rem 1rem !important; }
</style>
<script>
    // ==========================================
    // CSRF and Headers utility
    // ==========================================
    const getAuthHeaders = () => {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        return { 
            headers: { 
                'Accept': 'application/json', 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
            } 
        };
    };

    // ==========================================
    // Alpine Component for Class Management
    // ==========================================
    function classManagement() {
        return {
            classesList: [],
            showCreate: false,
            showUpdate: false,
            className: '',
            updateName: '',
            updateId: '',
            loading: false,

            async fetchList() {
                this.loading = true;
                try {
                    let res = await axios.get("/ajax/classes", getAuthHeaders());
                    this.classesList = res.data.classData || [];
                } catch (e) {
                    console.error("Failed to fetch classes:", e);
                } finally {
                    this.loading = false;
                }
            },

            async saveData() {
                if(!this.className.trim()) {
                    showAlert("Class Name is required!", "Attention");
                    return;
                }

                try {
                    let res = await axios.post("/ajax/classes", {class_name: this.className}, getAuthHeaders());
                    if(res.status === 200 || res.status === 201) {
                        this.showCreate = false;
                        this.className = '';
                        await showAlert("Class saved successfully!", "Success");
                        this.fetchList();
                    }
                } catch (e) {
                    showAlert("Failed to save class.", "Error");
                }
            },

            async fillUpdateForm(id) {
                try {
                    let res = await axios.get("/ajax/classes/" + id, getAuthHeaders());
                    let data = res.data.rows || res.data.data || res.data;
                    this.updateId = id;
                    this.updateName = data.class_name;
                    this.showUpdate = true;
                } catch (e) {
                    showAlert("Failed to fetch class data.", "Error");
                }
            },

            async updateData() {
                if(!this.updateName.trim()) {
                    showAlert("Class Name is required!", "Attention");
                    return;
                }

                try {
                    let res = await axios.put("/ajax/classes/" + this.updateId, {class_name: this.updateName}, getAuthHeaders());
                    if(res.status === 200) {
                        this.showUpdate = false;
                        await showAlert("Class updated successfully!", "Success");
                        this.fetchList();
                    }
                } catch (e) {
                    showAlert("Failed to update class.", "Error");
                }
            },

            async deleteClass(id) {
                const confirmed = await showDanger("Delete Class", "Are you sure you want to delete this class? All associated class records will be affected.");
                if (confirmed) {
                    try {
                        let res = await axios.delete("/ajax/classes/" + id, getAuthHeaders());
                        if(res.status === 200) {
                            await showAlert("Class deleted successfully!", "Success");
                            this.fetchList();
                        }
                    } catch (e) {
                        showAlert("Failed to delete class.", "Error");
                    }
                }
            }
        };
    }
</script>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeBlue font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Class Management</span>
@endsection

@section('content')
<div x-data="classManagement()" x-init="fetchList()">
    
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 21.033c0-3.224 2.78-5.833 6-5.833h0"/>
                </svg>
                Class Management
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Manage all classes for Pabna International School</p>
        </div>
        <div>
            <button @click="showCreate = true; className = ''" class="btn-sm bg-gradient-to-r from-themeBlue to-themeGreen text-white border-none rounded-xl hover:-translate-y-0.5 hover:shadow-lg transition-all flex items-center gap-1.5 !h-9 !px-4 uppercase text-xs font-black tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Add New Class
            </button>
        </div>
    </div>

    <!-- Classes Table (Borderless layout matching Student List) -->
    <div class="table-container bg-transparent !shadow-none !mt-2 !mb-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-none table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="w-20 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">SL</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Class Name</th>
                        <th class="w-48 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                    <!-- Loading Skeleton Rows -->
                    <tr x-show="loading" class="animate-pulse">
                        <td class="py-0 px-0 text-center"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                        <td class="py-0 px-0"><div class="h-4 w-32 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                        <td class="py-0 px-0 text-right"><div class="flex items-center justify-end gap-3"><div class="h-4 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></div></td>
                    </tr>
                    <tr x-show="loading" class="animate-pulse">
                        <td class="py-0 px-0 text-center"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                        <td class="py-0 px-0"><div class="h-4 w-24 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                        <td class="py-0 px-0 text-right"><div class="flex items-center justify-end gap-3"><div class="h-4 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></div></td>
                    </tr>
                    <tr x-show="loading" class="animate-pulse">
                        <td class="py-0 px-0 text-center"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                        <td class="py-0 px-0"><div class="h-4 w-40 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                        <td class="py-0 px-0 text-right"><div class="flex items-center justify-end gap-3"><div class="h-4 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></div></td>
                    </tr>
                    
                    <!-- Table Rows -->
                    <template x-for="(item, index) in classesList" :key="item.id">
                        <tr x-show="!loading" class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <td class="py-0 px-0 text-center font-mono font-black text-gray-555 dark:text-gray-400" x-text="index + 1"></td>
                            <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100" x-text="item.class_name"></td>
                            <td class="py-0 px-0 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="fillUpdateForm(item.id)" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Class">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="deleteClass(item.id)" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Class">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    
                    <!-- Empty Row -->
                    <tr x-show="classesList.length === 0 && !loading" x-cloak>
                        <td colspan="3" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No classes found. Add a new class.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal (Alpine.js State) -->
    <div x-show="showCreate" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-md p-4 transition-all duration-300" x-transition>
        <div @click.away="showCreate = false" class="bg-white dark:bg-themeNavy w-full max-w-md rounded-[2.2rem] shadow-2xl overflow-hidden border border-gray-100 dark:border-white/[0.08] transform transition-transform duration-300">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-white/[0.06] flex justify-between items-center bg-gray-50/50 dark:bg-themeDark/30">
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Add New Class</h3>
                <button @click="showCreate = false" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 md:p-8">
                <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Class Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="className" class="w-full h-11 px-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark text-gray-900 dark:text-white focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue outline-none transition-all font-semibold text-sm placeholder-gray-400" placeholder="e.g. Class Six" @keydown.enter="saveData()">
            </div>
            <div class="px-6 py-5 bg-gray-50/50 dark:bg-themeDark/30 border-t border-gray-100 dark:border-white/[0.06] flex justify-end gap-3">
                <button @click="showCreate = false" class="px-5 py-2.5 text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl hover:bg-gray-50 transition-all">Cancel</button>
                <button @click="saveData()" class="px-5 py-2.5 text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-themeBlue to-themeGreen rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all">Save Class</button>
            </div>
        </div>
    </div>

    <!-- Update Modal (Alpine.js State) -->
    <div x-show="showUpdate" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-md p-4 transition-all duration-300" x-transition>
        <div @click.away="showUpdate = false" class="bg-white dark:bg-themeNavy w-full max-w-md rounded-[2.2rem] shadow-2xl overflow-hidden border border-gray-100 dark:border-white/[0.08] transform transition-transform duration-300">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-white/[0.06] flex justify-between items-center bg-gray-50/50 dark:bg-themeDark/30">
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Update Class</h3>
                <button @click="showUpdate = false" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 md:p-8">
                <input type="hidden" x-model="updateId">
                <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2 ml-1">Class Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="updateName" class="w-full h-11 px-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark text-gray-900 dark:text-white focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue outline-none transition-all font-semibold text-sm placeholder-gray-400" @keydown.enter="updateData()">
            </div>
            <div class="px-6 py-5 bg-gray-50/50 dark:bg-themeDark/30 border-t border-gray-100 dark:border-white/[0.06] flex justify-end gap-3">
                <button @click="showUpdate = false" class="px-5 py-2.5 text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl hover:bg-gray-50 transition-all">Cancel</button>
                <button @click="updateData()" class="px-5 py-2.5 text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-themeBlue to-themeGreen rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all">Update Changes</button>
            </div>
        </div>
    </div>

</div>
@endsection