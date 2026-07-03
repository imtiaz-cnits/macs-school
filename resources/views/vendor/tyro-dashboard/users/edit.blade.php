@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit User')

@push('styles')
<!-- Load Alpine.js to handle custom behaviors if needed -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit User
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Update user information, roles, and status</p>
        </div>
        
        <a href="{{ route('tyro-dashboard.users.index') }}" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-250 font-black py-3 px-6 rounded-xl shadow-sm transition-all uppercase tracking-widest text-[10px] flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-555" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Users
        </a>
    </div>

    <!-- Main Content Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-8">
        
        <!-- Left Side: User Information Form -->
        <div class="lg:col-span-7 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">User Information</h3>
            
            <form action="{{ route('tyro-dashboard.users.update', $editUser->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Name -->
                <div class="mb-4">
                    <label for="name" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Name</label>
                    <input type="text" id="name" name="name" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all @error('name') border-red-500 @enderror" value="{{ old('name', $editUser->name) }}" required>
                    @error('name')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                    <input type="email" id="email" name="email" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all @error('email') border-red-500 @enderror" value="{{ old('email', $editUser->email) }}" required>
                    @error('email')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="mb-4">
                    <label for="password" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                        New Password <span class="text-gray-400 font-medium normal-case">(leave blank to keep current)</span>
                    </label>
                    <input type="password" id="password" name="password" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all @error('password') border-red-500 @enderror" placeholder="••••••••">
                    @error('password')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" placeholder="••••••••">
                </div>

                <!-- Assign Roles -->
                <div class="mb-6">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-3 ml-1">Assign Roles</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($roles as $role)
                        <label class="flex items-start gap-3 p-3.5 bg-gray-50/50 dark:bg-themeDark/40 border border-gray-100 dark:border-white/[0.04] rounded-2xl cursor-pointer hover:border-themeBlue/25 transition-all">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="w-4 h-4 mt-0.5 text-themeBlue border-gray-250 dark:border-gray-800 rounded focus:ring-themeBlue/15 focus:ring-4 transition-all" {{ in_array($role->id, old('roles', $editUser->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                            <div class="flex-1">
                                <div class="text-xs font-black text-gray-800 dark:text-gray-200">{{ $role->name }}</div>
                                <div class="text-[9px] font-semibold text-gray-400 uppercase tracking-widest mt-0.5">{{ $role->slug }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.06] pt-6">
                    <a href="{{ route('tyro-dashboard.users.index') }}" class="bg-gray-100 hover:bg-gray-250 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-200 font-black py-4 px-10 rounded-xl transition-all uppercase tracking-widest text-[10px]">Cancel</a>
                    <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Side: Account Status, 2FA, Danger Zone -->
        <div class="lg:col-span-5 space-y-8">
            
            <!-- Status Card -->
            <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Account Status</h3>
                
                <div class="flex items-center gap-4 mb-6">
                    <div class="relative w-16 h-16 rounded-2xl overflow-hidden border-2 border-gray-150 dark:border-white/[0.08] shadow-md flex-shrink-0 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                        @if((method_exists($editUser, 'hasProfilePhotoColumn') && $editUser->hasProfilePhotoColumn() && $editUser->profile_photo_path) || (method_exists($editUser, 'hasGravatarColumn') && $editUser->hasGravatarColumn() && $editUser->use_gravatar && $editUser->email))
                            <img src="{{ $editUser->profile_photo_url }}" alt="{{ $editUser->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-tr from-themeBlue to-themeGreen text-white flex items-center justify-center text-xl font-bold">
                                {{ strtoupper(substr($editUser->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wide">{{ $editUser->name }}</div>
                        <div class="text-[10px] font-bold text-gray-400 mt-0.5">Member since {{ $editUser->created_at->format('M d, Y') }}</div>
                        
                        @if(method_exists($editUser, 'hasProfilePhotoColumn') && $editUser->hasProfilePhotoColumn() && $editUser->profile_photo_path)
                        <div class="mt-1.5">
                            <form id="delete-user-photo-form" action="{{ route('tyro-dashboard.users.photo.delete', $editUser->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="flex items-center gap-1 text-[9px] font-black uppercase tracking-wider text-red-600 hover:text-red-800 transition-colors" onclick="showDanger('Remove Photo', 'Are you sure you want to remove this user\'s profile photo?').then(confirmed => { if(confirmed) document.getElementById('delete-user-photo-form').submit(); })">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-3 h-3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Remove Photo
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="p-4 bg-gray-50/50 dark:bg-themeDark/50 border border-gray-100 dark:border-white/[0.04] rounded-2xl mb-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest">Status</span>
                        @if(method_exists($editUser, 'isSuspended') && $editUser->isSuspended())
                            <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider bg-red-100 text-red-650 dark:bg-red-950/30 dark:text-red-500 rounded-lg">Suspended</span>
                        @else
                            <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider bg-green-100 text-themeGreen dark:bg-green-950/30 dark:text-green-500 rounded-lg">Active</span>
                        @endif
                    </div>
                    @if(method_exists($editUser, 'isSuspended') && $editUser->isSuspended() && method_exists($editUser, 'getSuspensionReason') && $editUser->getSuspensionReason())
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-white/[0.06]">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Suspension Reason:</span>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">{{ $editUser->getSuspensionReason() }}</p>
                        </div>
                    @endif
                </div>

                <!-- 2FA Status -->
                @if(config('tyro-login.two_factor.enabled'))
                <div class="py-4 border-t border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                    <span class="text-xs font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest">Two-Factor Auth</span>
                    @if($editUser->two_factor_secret)
                        <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider bg-green-100 text-themeGreen dark:bg-green-950/30 dark:text-green-500 rounded-lg">Enabled</span>
                    @else
                        <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-500 dark:bg-gray-850 dark:text-gray-400 rounded-lg">Disabled</span>
                    @endif
                </div>
                @if($editUser->two_factor_secret)
                    <div class="pb-4">
                        <form action="{{ route('tyro-dashboard.users.2fa.reset', $editUser->id) }}" method="POST" id="reset-2fa-form">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-black py-3 px-6 rounded-xl transition-all uppercase tracking-widest text-[10px]" onclick="event.preventDefault(); showConfirm('Reset 2FA', 'Are you sure you want to reset 2FA for this user?').then(confirmed => { if(confirmed) document.getElementById('reset-2fa-form').submit(); })">
                                Reset 2FA
                            </button>
                        </form>
                    </div>
                @endif
                @endif

                <!-- Suspend / Unsuspend Actions -->
                <div class="border-t border-gray-100 dark:border-white/[0.06] pt-4">
                    @if(method_exists($editUser, 'isSuspended') && $editUser->isSuspended())
                        <form action="{{ route('tyro-dashboard.users.unsuspend', $editUser->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full bg-themeGreen hover:bg-green-700 text-white font-black py-3.5 px-6 rounded-xl transition-all uppercase tracking-widest text-[10px] flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Unsuspend User
                            </button>
                        </form>
                    @elseif($editUser->id !== $user->id)
                        <button type="button" class="w-full bg-amber-600 hover:bg-amber-750 text-white font-black py-3.5 px-6 rounded-xl transition-all uppercase tracking-widest text-[10px] flex items-center justify-center gap-2" onclick="openSuspendModal()">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0V10.5m-2.25 10.5h13.5c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H5.25c-.621 0-1.125.504-1.125 1.125v7.875c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            Suspend User
                        </button>
                    @endif
                </div>
            </div>

            <!-- Danger Zone Card -->
            @if($editUser->id !== $user->id)
            <div class="bg-white dark:bg-themeNavy border border-red-200/50 dark:border-red-950/30 rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                <h3 class="text-sm font-black text-red-600 dark:text-red-500 uppercase tracking-widest border-b border-red-100/50 dark:border-red-950/20 pb-4 mb-4">Danger Zone</h3>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-6 leading-relaxed">
                    Once you delete a user, there is no going back. Please make sure before taking this action.
                </p>
                <form action="{{ route('tyro-dashboard.users.destroy', $editUser->id) }}" method="POST" id="delete-user-form">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="w-full bg-red-650 hover:bg-red-750 text-white font-black py-3.5 px-6 rounded-xl transition-all uppercase tracking-widest text-[10px] flex items-center justify-center gap-2" onclick="event.preventDefault(); showDanger('Delete User', 'Are you sure you want to delete this user? This action cannot be undone.').then(confirmed => { if(confirmed) document.getElementById('delete-user-form').submit(); })">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete User
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Suspend Modal -->
<div class="modal-overlay fixed inset-0 z-50 bg-black/40 backdrop-blur-md flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 [&.active]:opacity-100 [&.active]:pointer-events-auto" id="suspendModal">
    <div class="bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-3xl w-full max-w-lg p-6 shadow-2xl transform translate-y-4 transition-transform duration-300 [.modal-overlay.active_&]:translate-y-0">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/[0.06] pb-3 mb-4">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest">Suspend User</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" onclick="closeModal('suspendModal')">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('tyro-dashboard.users.suspend', $editUser->id) }}" method="POST">
            @csrf
            <div class="mb-6">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 leading-relaxed mb-4">
                    You are about to suspend <strong>{{ $editUser->name }}</strong>. This will revoke all their active sessions.
                </p>
                <div>
                    <label for="reason" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                        Reason for suspension <span class="text-gray-400 font-medium normal-case">(optional)</span>
                    </label>
                    <textarea id="reason" name="reason" class="w-full px-3 py-2 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" rows="3" placeholder="Enter a reason for suspension..."></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 dark:border-white/[0.06] pt-4">
                <button type="button" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-black py-3 px-6 rounded-xl transition-all uppercase tracking-widest text-[10px]" onclick="closeModal('suspendModal')">Cancel</button>
                <button type="submit" class="bg-red-650 hover:bg-red-750 text-white font-black py-3 px-8 rounded-xl shadow-lg transition-all uppercase tracking-widest text-[10px]">Suspend User</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<form id="delete-user-photo-form" action="{{ route('tyro-dashboard.users.photo.delete', $editUser->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
<script>
    function openSuspendModal() {
        openModal('suspendModal');
    }
</script>
@endpush
@endsection
