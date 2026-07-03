@extends($isAdmin ? 'tyro-dashboard::layouts.admin' : 'tyro-dashboard::layouts.user')

@section('title', 'Profile & Settings')

@push('styles')
<!-- Load Alpine.js for interactive preview and file upload handler -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush

@section('content')
<div x-data="userProfilePage()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.43l-1.003.828c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Profile & Settings
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Manage your account settings and preferences</p>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        
        <!-- Profile Information -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Profile Information</h3>
            
            <form action="{{ route('tyro-dashboard.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                @if((config('tyro-dashboard.features.profile_photo_upload') && method_exists($user, 'hasProfilePhotoColumn') && $user->hasProfilePhotoColumn()) || (config('tyro-dashboard.features.gravatar') && method_exists($user, 'hasGravatarColumn') && $user->hasGravatarColumn()))
                <div class="mb-6">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-3 ml-1">Profile Photo</label>
                    <div class="flex items-center gap-6">
                        <!-- Avatar Wrapper with Custom Upload Click -->
                        <div class="relative group cursor-pointer w-24 h-24 rounded-3xl overflow-hidden border-4 border-gray-150 dark:border-white/[0.08] shadow-lg flex-shrink-0">
                            @if((method_exists($user, 'hasProfilePhotoColumn') && $user->hasProfilePhotoColumn() && $user->profile_photo_path) || (method_exists($user, 'hasGravatarColumn') && $user->hasGravatarColumn() && $user->use_gravatar && $user->email))
                                <img id="avatar-preview" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                            @else
                                <div id="avatar-placeholder" class="w-full h-full bg-gradient-to-tr from-themeBlue to-themeGreen text-white flex items-center justify-center text-3xl font-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <!-- Hover Overlay -->
                            <div class="absolute inset-0 bg-black/45 backdrop-blur-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            @if(config('tyro-dashboard.features.profile_photo_upload') && method_exists($user, 'hasProfilePhotoColumn') && $user->hasProfilePhotoColumn())
                                <input type="file" name="photo" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" @change="previewPhoto($event)">
                            @endif
                        </div>
                        
                        <div class="flex-1 space-y-2">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Click on the image to upload a new profile picture.</p>
                            @if(config('tyro-dashboard.features.profile_photo_upload') && method_exists($user, 'hasProfilePhotoColumn') && $user->hasProfilePhotoColumn())
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium">Allowed types: JPG, PNG, GIF, WEBP. Max size: {{ config('tyro-dashboard.profile_photo.max_size', 10240) / 1024 }}MB.</p>
                            @endif
                            
                            @if(method_exists($user, 'hasProfilePhotoColumn') && $user->hasProfilePhotoColumn() && $user->profile_photo_path)
                                <button type="button" class="flex items-center gap-1 text-[11px] font-black uppercase tracking-wider text-red-600 hover:text-red-800 transition-colors" @click="removePhoto()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Remove Photo
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    @if(config('tyro-dashboard.features.gravatar') && method_exists($user, 'hasGravatarColumn') && $user->hasGravatarColumn())
                    <div class="mt-4 flex items-center gap-2">
                        <input type="checkbox" id="use_gravatar" name="use_gravatar" value="1" {{ old('use_gravatar', $user->use_gravatar) ? 'checked' : '' }} class="w-4 h-4 text-themeBlue border-gray-200 dark:border-gray-800 rounded focus:ring-themeBlue/15 focus:ring-4 transition-all">
                        <label for="use_gravatar" class="text-xs font-semibold text-gray-700 dark:text-gray-300">Use Gravatar</label>
                    </div>
                    @endif
                </div>
                @endif

                <!-- User Name -->
                <div class="mb-4">
                    <label for="name" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Name</label>
                    <input type="text" id="name" name="name" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all @error('name') border-red-500 @enderror" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- User Email -->
                <div class="mb-6">
                    <label for="email" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Email</label>
                    <input type="email" id="email" name="email" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all @error('email') border-red-550 @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                    
                    <div class="mt-2.5 ml-1 flex items-center gap-1">
                        @if($user->email_verified_at)
                            <svg class="w-4 h-4 text-themeGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs font-bold text-themeGreen">Verified on {{ $user->email_verified_at->format('M d, Y') }}</span>
                        @else
                            <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-xs font-bold text-amber-500">Email not verified</span>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Update Password -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Update Password</h3>
            
            <form action="{{ route('tyro-dashboard.profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Current Password -->
                <div class="mb-4">
                    <label for="current_password" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all @error('current_password') border-red-500 @enderror" required>
                    @error('current_password')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="mb-4">
                    <label for="password" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">New Password</label>
                    <input type="password" id="password" name="password" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all @error('password') border-red-500 @enderror" required>
                    @error('password')
                        <span class="text-xs font-semibold text-red-550 mt-1 block ml-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black py-4 px-12 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Second Row Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Two-Factor Authentication -->
        @if(config('tyro-login.two_factor.enabled'))
        <div class="lg:col-span-6 bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Two-Factor Authentication (2FA)</h3>
            
            <div class="space-y-4">
                @if($user->two_factor_secret)
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        Two-factor authentication is currently <strong class="text-themeGreen">enabled</strong> for your account.
                    </p>
                    <form action="{{ route('tyro-dashboard.profile.2fa.reset') }}" method="POST" id="reset-profile-2fa-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="bg-amber-600 hover:bg-amber-700 text-white font-black py-3.5 px-8 rounded-xl shadow-md transition-all uppercase tracking-widest text-[10px] active:scale-95" @click="resetTwoFactor()">
                            Reset 2FA Configuration
                        </button>
                    </form>
                @else
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        Two-factor authentication is currently <strong class="text-red-500">disabled</strong> for your account.
                    </p>
                    <button type="button" class="opacity-50 cursor-not-allowed bg-gray-200 dark:bg-gray-800 text-gray-400 dark:text-gray-600 font-black py-3.5 px-8 rounded-xl text-[10px] uppercase tracking-widest" disabled>
                        Reset 2FA Configuration
                    </button>
                @endif
            </div>
        </div>
        @endif

        <!-- Account Information -->
        <div class="{{ config('tyro-login.two_factor.enabled') ? 'lg:col-span-6' : 'lg:col-span-12' }} bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Account Information</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Account ID</label>
                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200">#{{ $user->id }}</p>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Member Since</label>
                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $user->created_at->format('M d, Y') }}</p>
                </div>
                @if(method_exists($user, 'roles') && $user->roles->count())
                <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Roles</label>
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->roles as $role)
                            <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider bg-themeBlue/10 text-themeBlue rounded-lg">{{ $role->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Status</label>
                    <div>
                        @if(method_exists($user, 'isSuspended') && $user->isSuspended())
                            <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider bg-red-100 text-red-600 dark:bg-red-950/30 dark:text-red-500 rounded-lg">Suspended</span>
                        @else
                            <span class="inline-block px-2.5 py-1 text-[9px] font-black uppercase tracking-wider bg-green-100 text-themeGreen dark:bg-green-950/30 dark:text-green-500 rounded-lg">Active</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Photo Delete Form -->
    <form id="delete-photo-form" action="{{ route('tyro-dashboard.profile.photo.delete') }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
    function userProfilePage() {
        return {
            previewPhoto(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const preview = document.getElementById('avatar-preview');
                        const placeholder = document.getElementById('avatar-placeholder');
                        if (preview) {
                            preview.src = e.target.result;
                        } else if (placeholder) {
                            const img = document.createElement('img');
                            img.id = 'avatar-preview';
                            img.src = e.target.result;
                            img.className = 'w-full h-full object-cover';
                            placeholder.replaceWith(img);
                        }
                    };
                    reader.readAsDataURL(file);
                }
            },
            
            async removePhoto() {
                const confirmed = await showDanger('Remove Photo', 'Are you sure you want to remove your profile photo?');
                if (confirmed) {
                    document.getElementById('delete-photo-form').submit();
                }
            },
            
            async resetTwoFactor() {
                const confirmed = await showConfirm('Reset 2FA', 'Are you sure you want to reset your 2FA? You will need to set it up again.');
                if (confirmed) {
                    document.getElementById('reset-profile-2fa-form').submit();
                }
            }
        };
    }
</script>
@endpush
