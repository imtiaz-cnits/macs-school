@extends('tyro-dashboard::layouts.admin')

@section('title', 'Create Role')

@push('styles')
<style>
    .table th { background-color: transparent !important; }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Create Role
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Add a new role with associated privileges</p>
        </div>
        
        <a href="{{ route('tyro-dashboard.roles.index') }}" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            Back to Roles
        </a>
    </div>

    <!-- Create Form Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-8 mb-8 relative shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('tyro-dashboard.roles.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Role Name Field -->
                <div>
                    <label for="name" class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Role Name <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="name" name="name" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400 @error('name') border-red-500 @enderror" value="{{ old('name') }}" required placeholder="e.g., Editor">
                    @error('name')
                        <span class="text-xs text-red-500 font-semibold mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Slug Field -->
                <div>
                    <label for="slug" class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Slug <span class="text-gray-400 font-medium normal-case ml-1">(auto-generated if empty)</span></label>
                    <input type="text" id="slug" name="slug" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400 @error('slug') border-red-500 @enderror" value="{{ old('slug') }}" placeholder="e.g., editor">
                    <span class="text-[10px] text-gray-450 dark:text-gray-500 mt-1.5 block">Used for programmatic access. Must be unique.</span>
                    @error('slug')
                        <span class="text-xs text-red-500 font-semibold mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Privileges Grid Section -->
            <div class="mb-8 border-t border-gray-100 dark:border-white/[0.05] pt-6">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-4 block">Assign Privileges</label>
                
                @if($privileges->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($privileges as $privilege)
                    <label class="relative flex items-start p-4 border border-gray-150 dark:border-white/[0.06] rounded-2xl cursor-pointer hover:bg-gray-50/50 dark:hover:bg-themeDark/45 hover:shadow-sm transition-all group">
                        <input type="checkbox" name="privileges[]" value="{{ $privilege->id }}" class="w-4 h-4 text-themeGreen rounded border-gray-250 dark:border-gray-800 focus:ring-themeGreen cursor-pointer mt-1" {{ in_array($privilege->id, old('privileges', [])) ? 'checked' : '' }}>
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-900 dark:text-white group-hover:text-themeBlue transition-colors">{{ $privilege->name }}</span>
                            <span class="block text-[10px] text-gray-450 dark:text-gray-500 font-mono mt-0.5 uppercase tracking-wider">{{ $privilege->slug }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
                @else
                <div class="p-5 rounded-2xl bg-indigo-50/50 dark:bg-themeBlue/5 border border-indigo-100/50 dark:border-white/[0.04] flex items-start gap-3">
                    <svg class="w-5 h-5 text-themeBlue mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063.854l-.518.775a.75.75 0 01-1.008-.324l-.067-.102a.75.75 0 00-1.068-.088c-.896.7-1.135 1.776-.757 2.762" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">No Privileges Available</h4>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1">Please <a href="{{ route('tyro-dashboard.privileges.create') }}" class="text-themeBlue hover:underline font-black">create a privilege first</a> before assigning them to this role.</p>
                    </div>
                </div>
                @endif
                
                @error('privileges')
                    <span class="text-xs text-red-500 font-semibold mt-3 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Actions buttons footer -->
            <div class="flex gap-3 border-t border-gray-100 dark:border-white/[0.05] pt-6 justify-end">
                <a href="{{ route('tyro-dashboard.roles.index') }}" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center">Cancel</a>
                <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center whitespace-nowrap active:scale-95">Create Role</button>
            </div>
        </form>
    </div>

</div>
@endsection
