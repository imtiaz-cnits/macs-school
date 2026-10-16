@extends('tyro-dashboard::layouts.admin')

@section('title', 'Expense Categories')

@push('styles')
<style>
    .table th, .table td {
        padding: 0.625rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="expenseCategoryManager()">

    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z" />
                </svg>
                Expense Categories
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Manage expenditure accounts, chart heads, and active category statuses
            </p>
        </div>

        <div class="flex flex-wrap gap-3 w-full md:w-auto justify-start md:justify-end">
            <a href="{{ route('accounts.expenses.index') }}" class="h-11 px-4 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 text-xs font-black uppercase tracking-wider flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95">
                <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Expenses List
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-themeGreen/10 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-600 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-600 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left Side: Add Category Form Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 lg:col-span-1 h-fit shadow-sm hover:shadow-md transition-all duration-300">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider mb-6 border-b border-gray-100 dark:border-white/[0.05] pb-3 block">
                Add New Category
            </h3>

            <form action="{{ route('accounts.categories.store') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                        Category Name <span class="text-red-500 ml-0.5">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3.5 placeholder-gray-400" placeholder="e.g. Utility Bills, Office Supplies" required>
                </div>

                <div class="mb-5">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">Status</label>
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
                            let inp = this.$refs.addStatusInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="status" x-ref="addStatusInput" value="Active">
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
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">Description (Optional)</label>
                    <textarea name="description" class="w-full min-h-[95px] border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 p-3 placeholder-gray-400" rows="3" placeholder="Brief details about what this expense category covers...">{{ old('description') }}</textarea>
                </div>

                <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                    Save Category
                </button>
            </form>
        </div>

        <!-- Right Side: Category List Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 lg:col-span-2 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 border-b border-gray-100 dark:border-white/[0.05] pb-4">
                    <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider block">
                        Expense Categories List
                    </h3>

                    <!-- Quick Search / Filter Form -->
                    <form action="{{ route('accounts.categories.index') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search category..." class="h-9 px-3 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-themeDark text-gray-700 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                        <button type="submit" class="h-9 px-3 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors">
                            Search
                        </button>
                        @if(request('search'))
                            <a href="{{ route('accounts.categories.index') }}" class="h-9 px-2.5 text-xs text-gray-400 hover:text-red-500 flex items-center">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Name & Description</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-28">Expenses</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-28">Status</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ $cat->name }}</div>
                                    @if($cat->description)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 font-normal mt-0.5 line-clamp-1">{{ $cat->description }}</div>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="px-2.5 py-1 text-xs font-mono font-bold rounded-lg bg-gray-100 dark:bg-white/[0.04] text-gray-700 dark:text-gray-300">
                                        {{ $cat->expenses_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block {{ $cat->status === 'Active' ? 'bg-green-50 text-themeGreen dark:bg-green-950/20 dark:text-green-400' : 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400' }}">
                                        {{ $cat->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Modal Trigger Button -->
                                        <button type="button" @click="openEditModal({ id: {{ $cat->id }}, name: '{{ addslashes($cat->name) }}', status: '{{ $cat->status }}', description: '{{ addslashes($cat->description ?? '') }}' })" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Category">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <form action="{{ route('accounts.categories.destroy', $cat->id) }}" method="POST" class="inline" onsubmit="return handleCategoryDelete(event, '{{ addslashes($cat->name) }}', {{ $cat->expenses_count ?? 0 }});">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Category">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider text-xs">
                                    No expense categories found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Links -->
            @if($categories->hasPages())
                <div class="pt-6 border-t border-gray-100 dark:border-white/[0.05]">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Category Modal (Alpine.js) -->
    <div x-show="isEditModalOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto" style="display: none;">
        <!-- Backdrop Blur -->
        <div class="fixed inset-0 bg-themeDark/60 backdrop-blur-md transition-opacity" @click="isEditModalOpen = false"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl max-w-lg w-full p-6 overflow-visible" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

                <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/[0.05] pb-4 mb-5">
                    <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Expense Category
                    </h3>
                    <button type="button" @click="isEditModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('accounts/categories') }}/' + editForm.id" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                            Category Name <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <input type="text" name="name" x-model="editForm.name" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3.5 placeholder-gray-400" required>
                    </div>

                    <div class="mb-4">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">Status</label>
                        <input type="hidden" name="status" :value="editForm.status">
                        <div x-data="{ open: false }" class="relative w-full" @click.away="open = false">
                            <button type="button" @click="open = !open" class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate" x-text="editForm.status"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                <template x-for="st in ['Active', 'Inactive']" :key="st">
                                    <button type="button" @click="editForm.status = st; open = false" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="editForm.status === st ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span x-text="st"></span>
                                        <svg x-show="editForm.status === st" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">Description (Optional)</label>
                        <textarea name="description" x-model="editForm.description" class="w-full min-h-[90px] border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 p-3 placeholder-gray-400" rows="3"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 dark:border-white/[0.05]">
                        <button type="button" @click="isEditModalOpen = false" class="h-11 px-5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-white dark:bg-themeDark text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-wider hover:bg-gray-50 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest active:scale-95">
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function expenseCategoryManager() {
        return {
            isEditModalOpen: false,
            editForm: {
                id: null,
                name: '',
                status: 'Active',
                description: ''
            },
            openEditModal(category) {
                this.editForm = {
                    id: category.id,
                    name: category.name,
                    status: category.status,
                    description: category.description || ''
                };
                this.isEditModalOpen = true;
            }
        };
    }

    async function handleCategoryDelete(event, categoryName, expenseCount) {
        event.preventDefault();
        const form = event.currentTarget;

        if (expenseCount > 0) {
            await showAlert(
                `Cannot delete category "${categoryName}" because it has ${expenseCount} recorded expense(s). Please set its status to Inactive instead.`,
                'Category In Use'
            );
            return false;
        }

        const confirmed = await showDanger(
            'Delete Category',
            `Are you sure you want to delete the expense category "${categoryName}"? This action cannot be undone.`
        );

        if (confirmed) {
            form.submit();
        }
        return false;
    }
</script>
@endpush
