@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Fee Category')

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
    .form-input { @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-themeGreen outline-none transition shadow-sm; }
</style>
@endpush

@section('breadcrumb')
<a href="{{ route('dashboard.dashboard') }}" class="text-themeGreen font-bold hover:underline">Dashboard</a>
<span class="text-gray-400 mx-2">/</span>
<a href="{{ route('fees.categories.index') }}" class="text-themeGreen font-bold hover:underline">Fee Categories</a>
<span class="text-gray-400 mx-2">/</span>
<span class="text-gray-600 dark:text-gray-300 font-medium">Edit</span>
@endsection

@section('content')
<div class="p-4 md:p-8 max-w-xl mx-auto">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-xl font-black text-themeIndigo dark:text-indigo-400 mb-6 border-b border-gray-100 dark:border-gray-700 pb-3 uppercase tracking-wider">Edit Fee Category</h3>
        
        @if($errors->any()) 
            <div class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 p-4 rounded-xl mb-6 font-bold border border-red-200 dark:border-red-800">{{ $errors->first() }}</div> 
        @endif

        <form action="{{ route('fees.categories.update', $category->id) }}" method="POST">
            @csrf
            @method('PUT') <div class="mb-5">
                <label class="form-label">Category Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" class="form-input" value="{{ $category->name }}" required>
            </div>
            <div class="mb-5">
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="Active" {{ $category->status == 'Active' ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ $category->status == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="mb-8">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" class="form-input" rows="3">{{ $category->description }}</textarea>
            </div>
            
            <div class="flex gap-3">
                <a href="{{ route('fees.categories.index') }}" class="flex-1 text-center bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-black py-3.5 rounded-xl transition-all hover:bg-gray-200 dark:hover:bg-gray-600 uppercase tracking-widest text-sm">Cancel</a>
                <button type="submit" class="flex-1 bg-themeIndigo hover:bg-indigo-700 text-white font-black py-3.5 rounded-xl shadow-lg transition-all hover:scale-[1.02] uppercase tracking-widest text-sm">Update</button>
            </div>
        </form>
    </div>

</div>
@endsection