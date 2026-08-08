<header class="topbar !backdrop-blur-md !bg-white/70 dark:!bg-themeDark/75 !border-b !border-gray-200 dark:!border-white/[0.08] !shadow-sm">
    <div class="topbar-left flex items-center">
        <button type="button" class="mobile-menu-btn" onclick="toggleSidebar()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <button type="button" class="topbar-btn desktop-collapse-btn !w-9 !h-9 !rounded-xl !bg-gray-100 dark:!bg-themeNavy/45 !border !border-gray-200 dark:!border-gray-800/80 hover:!text-themeBlue hover:scale-105 active:scale-95 transition-all cursor-pointer flex items-center justify-center" onclick="toggleSidebarCollapse()" aria-label="Toggle sidebar">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18" />
            </svg>
        </button>

        @hasSection('back_button')
            <div class="ml-3.5 border-l border-gray-200 dark:border-white/[0.08] pl-3.5 flex items-center h-6">
                @yield('back_button')
            </div>
        @endif
    </div>

    <div class="topbar-right flex items-center gap-3">
        <!-- Biometric Machine Status Badge -->
        <div x-data="{
            deviceStatus: null,
            async fetchDeviceStatus() {
                try {
                    let res = await axios.get('/ajax/attendance/device-status');
                    this.deviceStatus = res.data.device || null;
                } catch (e) {
                    this.deviceStatus = {
                        status: 'Disconnected',
                        connected: false,
                        ip: 'Unknown'
                    };
                }
            },
            init() {
                this.fetchDeviceStatus();
                // Refresh status every 30 seconds
                setInterval(() => this.fetchDeviceStatus(), 30000);
            }
        }" class="px-3 py-1.5 rounded-xl border flex items-center gap-2 transition-all shrink-0 select-none bg-rose-500/10 border-rose-500/20 text-rose-500"
           :class="deviceStatus && deviceStatus.connected ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-rose-500/10 border-rose-500/20 text-rose-500'">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                      :class="deviceStatus && deviceStatus.connected ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                <span class="relative inline-flex rounded-full h-2 w-2"
                      :class="deviceStatus && deviceStatus.connected ? 'bg-emerald-500' : 'bg-rose-500'"></span>
            </span>
            <span class="text-[10px] font-black uppercase tracking-wider" x-text="deviceStatus ? (deviceStatus.status + ' (' + deviceStatus.ip + ')') : 'Checking Machine...'"></span>
        </div>

        <!-- Theme Toggle -->
        <button type="button" class="topbar-btn flex items-center justify-center !w-9 !h-9 !rounded-xl !bg-gray-100 dark:!bg-themeNavy/45 !border !border-gray-200 dark:!border-gray-800/80 hover:!text-themeBlue hover:scale-105 active:scale-95 transition-all cursor-pointer" onclick="toggleTheme()" aria-label="Toggle theme">
            <svg class="sun-icon hidden !w-5 !h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg class="moon-icon !w-5 !h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>

        <!-- User Dropdown -->
        <div class="user-dropdown" id="userDropdown">
            <button type="button" class="user-dropdown-btn flex items-center gap-3 !rounded-xl !border !border-gray-200 dark:!border-gray-800/80 !bg-gray-100 dark:!bg-themeNavy/45 hover:!bg-gray-200 dark:hover:!bg-themeNavy/80 hover:scale-102 transition-all !px-3 !py-1.5 cursor-pointer" onclick="toggleUserDropdown()">
                <div class="user-avatar flex items-center justify-center text-xs font-black !w-8 !h-8 !rounded-lg border border-white/10 shadow-sm {{ ((method_exists($user, 'hasProfilePhotoColumn') && $user->hasProfilePhotoColumn() && $user->profile_photo_path) || (method_exists($user, 'hasGravatarColumn') && $user->hasGravatarColumn() && $user->use_gravatar)) ? '!bg-none !p-0' : '' }}">
                    @if((method_exists($user, 'hasProfilePhotoColumn') && $user->hasProfilePhotoColumn() && $user->profile_photo_path) || (method_exists($user, 'hasGravatarColumn') && $user->hasGravatarColumn() && $user->use_gravatar && $user->email))
                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover rounded-lg">
                    @else
                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                    @endif
                </div>
                <div class="user-info flex flex-col items-start leading-tight">
                    <div class="user-name !text-sm !font-bold text-gray-850 dark:text-gray-200">{{ $user->name ?? 'User' }}</div>
                    <div class="user-role !text-[9px] !font-black uppercase tracking-widest text-themeBlue mt-0.5">
                        @if(method_exists($user, 'roles') && $user->roles->count())
                            {{ $user->roles->first()->name }}
                        @else
                            User
                        @endif
                    </div>
                </div>
                <svg class="user-dropdown-arrow !w-4 !h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div class="user-dropdown-menu">
                <a href="{{ route('tyro-dashboard.profile') }}" class="dropdown-item">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    My Profile
                </a>
                <button type="button" onclick="toggleTheme()" class="dropdown-item w-full text-left border-none bg-transparent cursor-pointer flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="sun-icon hidden w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="moon-icon w-4 h-4 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span>Theme</span>
                    </span>
                    <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider bg-gray-100 dark:bg-themeNavy/80 py-0.5 px-2 rounded-md theme-mode-label">Light</span>
                </button>
                <div class="dropdown-divider"></div>
                @if(session('impersonator_id'))
                    <form action="{{ route('tyro-dashboard.leave-impersonation') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item dropdown-item-danger w-full text-left border-none bg-transparent cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Exit Impersonation
                        </button>
                    </form>
                @else
                    <form action="{{ route('tyro-login.logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item dropdown-item-danger w-full text-left border-none bg-transparent cursor-pointer">
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</header>
