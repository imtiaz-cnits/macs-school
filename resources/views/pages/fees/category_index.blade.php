@extends('tyro-dashboard::layouts.admin')

@section('title', 'Manage Fee Categories')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#1e4630', themeRed: '#cc0000', themeIndigo: '#4f46e5' },
                fontFamily: { sans: ['Figtree', 'sans-serif'], secondary: ['Onest', 'sans-serif'] } 
            } 
        } 
    }
</script>
<style>
    .form-label { @apply block text-xs font-black text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wider; }
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-themeGreen outline-none transition shadow-sm placeholder-gray-400; }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Fee Categories</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    
    <div class="mb-8">
        <h2 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Fee Categories</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create and manage different types of fees (e.g., Tuition, Exam)</p>
    </div>

    @if(session('success')) 
        <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 p-4 rounded-xl mb-6 font-bold border border-green-200 dark:border-green-800">{{ session('success') }}</div> 
    @endif
    @if($errors->any()) 
        <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 p-4 rounded-xl mb-6 font-bold border border-red-200 dark:border-red-800">{{ $errors->first() }}</div> 
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 lg:col-span-1 h-fit">
            <h3 class="text-lg font-black text-themeGreen dark:text-green-500 mb-6 border-b border-gray-100 dark:border-gray-700 pb-3 uppercase tracking-wider">Add New Category</h3>
            
            <form action="{{ route('fees.categories.store') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="form-label">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Monthly Tuition Fee" required>
                </div>
                <div class="mb-5">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Brief details about this fee..."></textarea>
                </div>
                <button type="submit" class="w-full bg-themeGreen hover:bg-green-900 text-white font-black py-3.5 rounded-xl shadow-lg transition-all hover:scale-[1.02] uppercase tracking-widest text-sm">Save Category</button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden lg:col-span-2">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-wider">Category List</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Name</th>
                            <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Status</th>
                            <th class="py-4 px-6 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($categories as $cat)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="py-4 px-6 font-bold text-gray-900 dark:text-gray-100">{{ $cat->name }}</td>
                            <td class="py-4 px-6">
                                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-full {{ $cat->status == 'Active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $cat->status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right flex justify-end gap-2 items-center">
                                <a href="{{ route('fees.categories.edit', $cat->id) }}" class="text-themeIndigo hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-bold text-sm bg-indigo-50 dark:bg-indigo-900/20 px-3 py-1.5 rounded-lg transition-colors">Edit</a>
                                
                                <form action="{{ route('fees.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Delete this category? This might affect related fee setups.');">
                                    @csrf @method('DELETE')
                                    <button class="text-themeRed hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-bold text-sm bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg transition-colors">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-gray-500 dark:text-gray-400 font-medium">No fee categories found. Create one to get started!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection