@extends('tyro-dashboard::layouts.admin')

@section('title', 'Manage Fee Categories')

@push('styles')
<style>
    select option { background: #ffffff; color: #1f2937; }
    .dark select option { background: #0f1e2c; color: #ffffff; }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div class="w-full">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Fee Categories
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Create and manage different types of fees (e.g. Tuition, Exam)</p>
        </div>
    </div>

    <!-- Alert Banners -->
    @if(session('success')) 
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-950/20 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('success') }}
        </div> 
    @endif
    @if($errors->any()) 
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ $errors->first() }}
        </div> 
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Side: Add Form -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 lg:col-span-1 h-fit shadow-sm hover:shadow-md transition-all duration-300">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider mb-6 border-b border-gray-100 dark:border-white/[0.05] pb-3 block">Add New Category</h3>
            
            <form action="{{ route('fees.categories.store') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Category Name <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" name="name" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" placeholder="e.g. Monthly Tuition Fee" required>
                </div>
                <div class="mb-5">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Status</label>
                    <div x-data="{ 
                        open: false, 
                        value: 'Active', 
                        label: 'Active',
                        items: [
                            { value: 'Active', label: 'Active' },
                            { value: 'Inactive', label: 'Inactive' }
                        ],
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
                        <input type="hidden" name="status" x-ref="hiddenInput" value="Active">
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
                <div class="mb-6">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Description (Optional)</label>
                    <textarea name="description" class="w-full min-h-[100px] border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 p-3 placeholder-gray-400" rows="3" placeholder="Brief details about this fee..."></textarea>
                </div>
                <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                    Save Category
                </button>
            </form>
        </div>

        <!-- Right Side: Category List -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 lg:col-span-2 overflow-hidden">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider mb-6 block">Category List</h3>
            
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Name</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-36">Status</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-36">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-4 px-4 font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $cat->name }}</td>
                            <td class="py-4 px-4">
                                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block {{ $cat->status == 'Active' ? 'bg-green-50 text-themeGreen dark:bg-green-950/20 dark:text-green-400' : 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400' }}">
                                    {{ $cat->status }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit Button -->
                                    <a href="{{ route('fees.categories.edit', $cat->id) }}" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Category">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <!-- Delete Button -->
                                    <form action="{{ route('fees.categories.destroy', $cat->id) }}" method="POST" class="inline" onsubmit="return confirmDelete(event);">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn text-red-650 hover:text-red-800 hover:border-red-600" title="Delete Category">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">No fee categories found. Create one to get started!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    async function confirmDelete(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (await showDanger('Delete Category', 'Are you sure you want to delete this category? This might affect related fee setups.')) {
            form.submit();
        }
    }
</script>
@endpush
@endsection