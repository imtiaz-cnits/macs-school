@extends('tyro-dashboard::layouts.admin')

@section('title', 'Create User')

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
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                </svg>
                Create User
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Add a new user to the system</p>
        </div>
        
        <a href="{{ route('tyro-dashboard.users.index') }}" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            Back to Users
        </a>
    </div>

    <!-- Create Form Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-8 mb-8 relative shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('tyro-dashboard.users.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Name Field -->
                <div>
                    <label for="name" class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Name <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" id="name" name="name" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400 @error('name') border-red-500 @enderror" value="{{ old('name') }}" required placeholder="John Doe">
                    @error('name')
                        <span class="text-xs text-red-500 font-semibold mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Email <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="email" id="email" name="email" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400 @error('email') border-red-500 @enderror" value="{{ old('email') }}" required placeholder="john@example.com">
                    @error('email')
                        <span class="text-xs text-red-500 font-semibold mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Password Field -->
                <div>
                    <label for="password" class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Password <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="password" id="password" name="password" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400 @error('password') border-red-500 @enderror" required placeholder="••••••••">
                    @error('password')
                        <span class="text-xs text-red-500 font-semibold mt-1.5 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Confirm Password <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" required placeholder="••••••••">
                </div>
            </div>

            <!-- Role Assignment Grid -->
            <div class="mb-8 border-t border-gray-100 dark:border-white/[0.05] pt-6">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-4 block">Assign Roles</label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($roles as $role)
                    <label class="relative flex items-start p-4 border border-gray-150 dark:border-white/[0.06] rounded-2xl cursor-pointer hover:bg-gray-50/50 dark:hover:bg-themeDark/45 hover:shadow-sm transition-all group">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="w-4 h-4 text-themeGreen rounded border-gray-250 dark:border-gray-800 focus:ring-themeGreen cursor-pointer mt-1" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-900 dark:text-white group-hover:text-themeBlue transition-colors">{{ $role->name }}</span>
                            <span class="block text-[10px] text-gray-450 dark:text-gray-500 font-mono mt-0.5 uppercase tracking-wider">{{ $role->slug }}</span>
                        </div>
                    </label>
                    @endforeach
                </div>
                
                @error('roles')
                    <span class="text-xs text-red-500 font-semibold mt-3 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Actions buttons footer -->
            <div class="flex gap-3 border-t border-gray-100 dark:border-white/[0.05] pt-6 justify-end">
                <a href="{{ route('tyro-dashboard.users.index') }}" class="h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all flex items-center justify-center">Cancel</a>
                <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center whitespace-nowrap active:scale-95">Create User</button>
            </div>
        </form>
    </div>

</div>
@endsection
