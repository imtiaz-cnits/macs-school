@php
    $sidebarUser = auth()->user();
    $hasPhoto = $sidebarUser && ((method_exists($sidebarUser, 'hasProfilePhotoColumn') && $sidebarUser->hasProfilePhotoColumn() && $sidebarUser->profile_photo_path) || (method_exists($sidebarUser, 'hasGravatarColumn') && $sidebarUser->hasGravatarColumn() && $sidebarUser->use_gravatar && $sidebarUser->email));
    
    // Determine the initial drill-down view based on the current active route
    $initialView = 'main';
    if (request()->routeIs('students.*', 'student.*', 'id-cards.*')) {
        $initialView = 'students';
    } elseif (request()->routeIs('teachers.*', 'teacher.*', 'staff-attendance.*')) {
        $initialView = 'teachers';
    } elseif (request()->is('*resources*')) {
        $initialView = 'resources';
    }
@endphp

<aside class="sidebar group/sidebar !overflow-y-hidden bg-[var(--sidebar)] border-r border-[var(--sidebar-border)]" id="sidebar">
    <!-- Sidebar Header (Vercel Style) -->
    <div class="sidebar-header h-16 flex items-center !px-5 border-b border-[var(--sidebar-border)]">
       <a href="{{ route('tyro-dashboard.index') }}" class="sidebar-logo flex items-center gap-2.5 !no-underline !border-none">
            <div class="!w-9 !h-9 !min-w-[36px] !min-h-[36px] overflow-hidden !bg-white !rounded-lg !flex !items-center !justify-center border border-gray-200 dark:border-white/10 !shrink-0 shadow-sm">
                <img src="{{ asset('img/macs_logo.jpeg') }}" 
                     style="width: 100% !important; height: 100% !important; object-fit: contain !important; display: block !important; padding: 1px !important;" 
                     alt="{{ config('app.name', 'MACS School & College') }} Logo">
            </div>
            
            <span class="sidebar-logo-text sidebar-text !font-black !whitespace-nowrap !overflow-hidden !text-ellipsis bg-gradient-to-r from-themeBlue to-themeGreen bg-clip-text !text-transparent !text-[0.9rem] tracking-tight uppercase">
                MACS School
            </span>
        </a>
    </div>
   
    <!-- Sidebar Nav with Alpine Drilldown System -->
    <nav class="sidebar-nav !overflow-y-auto !overflow-x-hidden !flex-1 !pt-4 scrollbar-thin scrollbar-track-transparent" x-data="{ currentView: '{{ $initialView }}' }">
        
        <!-- ==============================================
             MAIN VIEW (Root Level Navigation)
             ============================================== -->
        <div x-show="currentView === 'main'" style="display: {{ $initialView === 'main' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0">
            <!-- Dashboard Link (Direct link) -->
            <a href="{{ route('dashboard.dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="10" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                <span class="sidebar-text">Dashboard</span>
            </a>

            <!-- My Profile Link (Direct link) -->
            <a href="{{ route('tyro-dashboard.profile') }}" class="sidebar-link {{ request()->routeIs('tyro-dashboard.profile*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="sidebar-text">My Profile</span>
            </a>

            <!-- Student Management (Drilldown Trigger) -->
            @if(auth()->check() && (auth()->user()->hasRole('editor') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'students'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10v6M2 10l10-5 10 5-10 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
                        <span class="sidebar-text">Student Management</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        Student Management
                    </div>
                    <a href="{{ route('students.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Students Lists</span>
                    </a>
                    <a href="{{ route('student.admission') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Add New Students</span>
                    </a>
                    @if(auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
                    <a href="{{ route('id-cards.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>ID Card Generation</span>
                    </a>
                    <a href="{{ route('student.promotion') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Student Promotion</span>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Teacher Management (Drilldown Trigger) -->
            @if(auth()->check() && (auth()->user()->hasRole('editor') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'teachers'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span class="sidebar-text">Staff Management</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        Staff Management
                    </div>
                    <a href="{{ route('teachers.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Teachers List</span>
                    </a>
                    <a href="{{ route('teacher.add') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Add Teacher</span>
                    </a>
                    <a href="{{ route('staff-attendance.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Staff Attendance</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- Dynamic Resources (Drilldown Trigger) -->
            @php
                $accessibleResources = [];
                foreach ($allResources ?? config('tyro-dashboard.resources', []) as $key => $resource) {
                    $canAccess = true;
                    if (isset($resource['roles']) && !empty($resource['roles'])) {
                        $canAccess = false;
                        $user = auth()->user();
                        if ($user && method_exists($user, 'tyroRoleSlugs')) {
                            $userRoles = $user->tyroRoleSlugs();
                            foreach ($resource['roles'] as $role) {
                                if (in_array($role, $userRoles)) { $canAccess = true; break; }
                            }
                        }
                    }
                    if ($canAccess) { $accessibleResources[$key] = $resource; }
                }
            @endphp
            @if(!empty($accessibleResources))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'resources'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polygon points="2 17 12 22 22 17"/><polygon points="2 12 12 17 22 12"/></svg>
                        <span class="sidebar-text">Resources</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        Resources
                    </div>
                    @foreach($accessibleResources as $rKey => $resource)
                        <a href="{{ route('tyro-dashboard.resources.index', $rKey) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                            <span>{{ $resource['label'] ?? ucwords(str_replace('_', ' ', $rKey)) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- ==============================================
             STUDENTS VIEW
             ============================================== -->
        <div x-show="currentView === 'students'" style="display: {{ $initialView === 'students' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>Student Management</span>
            </button>

            <!-- Menu links -->
            <a href="{{ route('students.index') }}" class="sidebar-link {{ request()->routeIs('students.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <span class="sidebar-text">Students Lists</span>
            </a>
            <a href="{{ route('student.admission') }}" class="sidebar-link {{ request()->routeIs('student.admission') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                <span class="sidebar-text">Add New Students</span>
            </a>
            @if(auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <a href="{{ route('id-cards.index') }}" class="sidebar-link {{ request()->routeIs('id-cards.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M8 16h8"/></svg>
                <span class="sidebar-text">ID Card Generation</span>
            </a>
            @endif
        </div>

        <!-- ==============================================
             TEACHERS VIEW
             ============================================== -->
        <div x-show="currentView === 'teachers'" style="display: {{ $initialView === 'teachers' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>Staff Management</span>
            </button>

            <!-- Menu links -->
            <a href="{{ route('teachers.index') }}" class="sidebar-link {{ request()->routeIs('teachers.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="sidebar-text">Teachers List</span>
            </a>
            <a href="{{ route('teacher.add') }}" class="sidebar-link {{ request()->routeIs('teacher.add') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                <span class="sidebar-text">Add Teacher</span>
            </a>
            <a href="{{ route('staff-attendance.index') }}" class="sidebar-link {{ request()->routeIs('staff-attendance.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span class="sidebar-text">Staff Attendance</span>
            </a>
        </div>

        <!-- ==============================================
             DYNAMIC RESOURCES VIEW
             ============================================== -->
        @if(!empty($accessibleResources))
        <div x-show="currentView === 'resources'" style="display: {{ $initialView === 'resources' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>Resources</span>
            </button>

            <!-- Dynamic Resources Loop -->
            @foreach($accessibleResources as $key => $resource)
                <a href="{{ route('tyro-dashboard.resources.index', $key) }}" class="sidebar-link {{ request()->is('*resources/'.$key.'*') ? 'active' : '' }}">
                    @if(isset($resource['icon']))
                        {!! $resource['icon'] !!}
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg>
                    @endif
                    <span class="sidebar-text">{{ $resource['title'] }}</span>
                </a>
            @endforeach
        </div>
        @endif

    </nav>

    <!-- Sidebar Footer (Vercel Style) -->
    @if($sidebarUser)
    <div class="sidebar-footer border-t border-[var(--sidebar-border)] p-3 relative flex items-center justify-between group-[.collapsed]/sidebar:justify-center" id="sidebarUserFooter">
        <!-- Dropdown Menu -->
        <div id="sidebarUserDropdownMenu" class="absolute bottom-full left-4 right-4 mb-2 bg-[var(--background)] border border-[var(--border)] rounded-lg shadow-lg opacity-0 invisible translate-y-2 transition-all duration-200 z-[1100] p-1 group-[.collapsed]/sidebar:left-[75px] group-[.collapsed]/sidebar:right-auto group-[.collapsed]/sidebar:w-[200px] group-[.collapsed]/sidebar:bottom-4">
            <div class="px-3 py-2 border-b border-[var(--border)]">
                <p class="text-[14px] font-semibold text-[var(--foreground)] truncate">{{ $sidebarUser->name }}</p>
                <p class="text-[11px] text-[var(--muted-foreground)] truncate">{{ $sidebarUser->email }}</p>
            </div>
            <div class="py-1">
                <a href="{{ route('tyro-dashboard.profile') }}" class="flex items-center gap-2 px-3 py-1.5 text-[13px] text-[var(--muted-foreground)] hover:text-[var(--foreground)] hover:bg-[var(--sidebar-accent)] rounded-md transition-colors duration-150 !no-underline">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Settings</span>
                </a>
                <div class="h-[1px] bg-[var(--border)] my-1"></div>
                @if(session('impersonator_id'))
                    <form action="{{ route('tyro-dashboard.leave-impersonation') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full text-left border-none bg-transparent cursor-pointer px-3 py-1.5 text-[13px] text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-md transition-colors duration-150">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>Leave Impersonation</span>
                        </button>
                    </form>
                @else
                    <form action="{{ route('tyro-login.logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full text-left border-none bg-transparent cursor-pointer px-3 py-1.5 text-[13px] text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-md transition-colors duration-150">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span>Sign out</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Left Part: Avatar & Name -->
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 min-w-[28px] min-h-[28px] overflow-hidden rounded-full flex items-center justify-center bg-gray-900 dark:bg-gray-100 text-white dark:text-black font-bold text-[11px] {{ $hasPhoto ? '!bg-none !p-0' : '' }}">
                @if($hasPhoto)
                    <img src="{{ $sidebarUser->profile_photo_url }}" alt="{{ $sidebarUser->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($sidebarUser->name ?? 'U', 0, 1)) }}
                @endif
            </div>
            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate group-[.collapsed]/sidebar:hidden">{{ $sidebarUser->name }}</span>
        </div>

        <!-- Right Part: Actions (Ellipsis & Bell) -->
        <div class="flex items-center gap-1 group-[.collapsed]/sidebar:hidden shrink-0">
            <button type="button" onclick="toggleSidebarUserDropdown(event)" class="w-6 h-6 rounded-md hover:bg-gray-100 dark:hover:bg-white/[0.08] flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors cursor-pointer border-none bg-transparent" title="More options">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="12" cy="12" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
            </button>
            <a href="#" class="w-6 h-6 rounded-md hover:bg-gray-100 dark:hover:bg-white/[0.08] flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors" title="Notifications">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </a>
        </div>
    </div>
    @endif
</aside>

<script>
    function toggleSidebarUserDropdown(event) {
        event.stopPropagation();
        const menu = document.getElementById('sidebarUserDropdownMenu');
        const btn = event.currentTarget;
        const isVisible = menu.classList.contains('active-dropdown');
        
        if (isVisible) {
            menu.classList.remove('opacity-100', 'visible', 'translate-y-0', 'active-dropdown');
            menu.classList.add('opacity-0', 'invisible', 'translate-y-2');
            btn.classList.remove('open-dropdown');
        } else {
            menu.classList.remove('opacity-0', 'invisible', 'translate-y-2');
            menu.classList.add('opacity-100', 'visible', 'translate-y-0', 'active-dropdown');
            btn.classList.add('open-dropdown');
        }
    }

    document.addEventListener('click', function(event) {
        const menu = document.getElementById('sidebarUserDropdownMenu');
        const footer = document.getElementById('sidebarUserFooter');
        if (menu && menu.classList.contains('active-dropdown') && footer && !footer.contains(event.target)) {
            menu.classList.remove('opacity-100', 'visible', 'translate-y-0', 'active-dropdown');
            menu.classList.add('opacity-0', 'invisible', 'translate-y-2');
            const btn = footer.querySelector('button');
            if (btn) btn.classList.remove('open-dropdown');
        }
    });
</script>