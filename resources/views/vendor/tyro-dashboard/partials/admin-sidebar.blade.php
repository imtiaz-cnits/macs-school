@php
    $sidebarUser = auth()->user();
    $hasPhoto = $sidebarUser && ((method_exists($sidebarUser, 'hasProfilePhotoColumn') && $sidebarUser->hasProfilePhotoColumn() && $sidebarUser->profile_photo_path) || (method_exists($sidebarUser, 'hasGravatarColumn') && $sidebarUser->hasGravatarColumn() && $sidebarUser->use_gravatar && $sidebarUser->email));
    
    // Determine the initial drill-down view based on the current active route
    $initialView = 'main';
    if (request()->routeIs('students.*', 'student.*', 'id-cards.*', 'attendance.*')) {
        $initialView = 'students';
    } elseif (request()->routeIs('exams.*', 'exam-routine.*', 'exam-schedules.*', 'admit-cards.*', 'seat-plans.*', 'marks.*', 'results.*', 'certificates.*')) {
        $initialView = 'exams';
    } elseif (request()->routeIs('teachers.*', 'teacher.*', 'staff-attendance.*')) {
        $initialView = 'teachers';
    } elseif (request()->routeIs('sms.*')) {
        $initialView = 'sms';
    } elseif (request()->routeIs('sections.*', 'shifts.*', 'sessions.*', 'branches.*', 'subjects.*', 'grades.*', 'classes.*', 'routine.*')) {
        $initialView = 'academic';
    } elseif (request()->routeIs('fees.*')) {
        $initialView = 'fees';
    } elseif (request()->routeIs('tyro-dashboard.users.*', 'tyro-dashboard.roles.*', 'tyro-dashboard.privileges.*', 'tyro-dashboard.dashboard.*', 'tyro-dashboard.profile*')) {
        $initialView = 'admin';
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
                    <a href="{{ route('attendance.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Attendance</span>
                    </a>
                    <a href="{{ route('attendance.report') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Attendance Report</span>
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

            <!-- Exam Management (Drilldown Trigger) -->
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'exams'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></svg>
                        <span class="sidebar-text">Exam Management</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        Exam Management
                    </div>
                    <a href="{{ route('exams.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Exam List</span>
                    </a>
                    <a href="{{ route('exam-routine.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Exam Routine</span>
                    </a>
                    <a href="{{ route('exam-schedules.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Exam Setup</span>
                    </a>
                    <a href="{{ route('admit-cards.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Admit Card</span>
                    </a>
                    <a href="{{ route('seat-plans.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Seat Plan</span>
                    </a>
                    <a href="{{ route('marks.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Marks Entry</span>
                    </a>
                    <a href="{{ route('results.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Results</span>
                    </a>
                    <a href="{{ route('results.tabulation') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Tabulation Sheet</span>
                    </a>
                    <a href="{{ route('certificates.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Certificates</span>
                    </a>
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

            <!-- SMS Management (Drilldown Trigger) -->
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'sms'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span class="sidebar-text">SMS Management</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        SMS Management
                    </div>
                    <a href="{{ route('sms.general-notice') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Notice SMS</span>
                    </a>
                    <a href="{{ route('sms.result') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Result SMS</span>
                    </a>
                    <a href="{{ route('sms.report') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>SMS Report</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- Academic Setup (Drilldown Trigger) -->
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'academic'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 22H2"/><path d="M18 22V10a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v12"/><path d="M12 18h.01"/><path d="M12 14h.01"/><path d="M12 10h.01"/><path d="M8 14h.01"/><path d="M16 14h.01"/></svg>
                        <span class="sidebar-text">Academic Setup</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        Academic Setup
                    </div>
                    <a href="{{ route('classes.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Class List</span>
                    </a>
                    <a href="{{ route('routine.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Class Routine</span>
                    </a>
                    <a href="{{ route('sections.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Sections</span>
                    </a>
                    <a href="{{ route('shifts.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Shifts</span>
                    </a>
                    <a href="{{ route('sessions.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Sessions</span>
                    </a>
                    <a href="{{ route('branches.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Branches</span>
                    </a>
                    <a href="{{ route('subjects.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Subjects</span>
                    </a>
                    <a href="{{ route('grades.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Grades</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- Fee Management (Drilldown Trigger) -->
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'fees'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><path d="M15 10H11a1.5 1.5 0 0 0 0 3h2a1.5 1.5 0 0 1 0 3H9"/></svg>
                        <span class="sidebar-text">Fee Management</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        Fee Management
                    </div>
                    <a href="{{ route('fees.categories.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Fee Categories</span>
                    </a>
                    <a href="{{ route('fees.setup.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Fee Setup</span>
                    </a>
                    <a href="{{ route('fees.invoice.generate') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Generate Invoices</span>
                    </a>
                    <a href="{{ route('fees.collection.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Collect Fees</span>
                    </a>
                    <a href="{{ route('fees.reports.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Financial Reports</span>
                    </a>
                    <a href="{{ route('fees.reports.summary') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Category Summary</span>
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

            <!-- Administration (Drilldown Trigger) -->
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <div class="relative group/trigger">
                <button type="button" @click="currentView = 'admin'" class="flex items-center justify-between sidebar-link border-none bg-transparent group !w-[calc(100%-16px)] !mx-2">
                    <span class="flex items-center gap-2.5">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1 4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span class="sidebar-text">Administration</span>
                    </span>
                    <span class="w-5 h-5 rounded-[5px] flex items-center justify-center bg-gray-100/70 dark:bg-white/[0.04] border border-gray-200/50 dark:border-white/[0.05] transition-all group-hover:bg-gray-200/80 dark:group-hover:bg-white/[0.15] group-hover:border-gray-300 dark:group-hover:border-white/[0.18] shrink-0">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                </button>
                <div class="sidebar-popup-menu absolute left-[62px] top-0 ml-2 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-2 min-w-[200px] z-[9999] pointer-events-auto text-left hidden">
                    <div class="px-4 py-1.5 border-b border-gray-100 dark:border-white/[0.05] text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">
                        Administration
                    </div>
                    <a href="{{ route('tyro-dashboard.users.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Users</span>
                    </a>
                    <a href="{{ route('tyro-dashboard.roles.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Roles</span>
                    </a>
                    <a href="{{ route('tyro-dashboard.privileges.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Privileges</span>
                    </a>
                    <a href="{{ route('tyro-dashboard.profile') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-semibold text-gray-700 dark:text-gray-300 hover:text-themeBlue dark:hover:text-themeBlue hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors !no-underline">
                        <span>Settings</span>
                    </a>
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
            <a href="{{ route('attendance.index') }}" class="sidebar-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span class="sidebar-text">Attendance</span>
            </a>
            <a href="{{ route('attendance.report') }}" class="sidebar-link {{ request()->routeIs('attendance.report') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                <span class="sidebar-text">Attendance Report</span>
            </a>
            @if(auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
            <a href="{{ route('id-cards.index') }}" class="sidebar-link {{ request()->routeIs('id-cards.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M8 16h8"/></svg>
                <span class="sidebar-text">ID Card Generation</span>
            </a>
            <a href="{{ route('student.promotion') }}" class="sidebar-link {{ request()->routeIs('student.promotion') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                <span class="sidebar-text">Student Promotion</span>
            </a>
            @endif
        </div>



        <!-- ==============================================
             EXAMS VIEW
             ============================================== -->
        <div x-show="currentView === 'exams'" style="display: {{ $initialView === 'exams' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>Exam Management</span>
            </button>

            <!-- Menu links -->
            <a href="{{ route('exams.index') }}" class="sidebar-link {{ request()->routeIs('exams.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span class="sidebar-text">Exam List</span>
            </a>
            <a href="{{ route('exam-routine.index') }}" class="sidebar-link {{ request()->routeIs('exam-routine.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span class="sidebar-text">Exam Routine</span>
            </a>
            <a href="{{ route('exam-schedules.index') }}" class="sidebar-link {{ request()->routeIs('exam-schedules.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                <span class="sidebar-text">Exam Setup</span>
            </a>
            <a href="{{ route('admit-cards.index') }}" class="sidebar-link {{ request()->routeIs('admit-cards.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 10h10M7 14h6"/></svg>
                <span class="sidebar-text">Admit Card</span>
            </a>
            <a href="{{ route('seat-plans.index') }}" class="sidebar-link {{ request()->routeIs('seat-plans.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span class="sidebar-text">Seat Plan</span>
            </a>
            <a href="{{ route('marks.index') }}" class="sidebar-link {{ request()->routeIs('marks.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                <span class="sidebar-text">Marks Entry</span>
            </a>
            <a href="{{ route('results.index') }}" class="sidebar-link {{ request()->routeIs('results.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                <span class="sidebar-text">Results</span>
            </a>
            <a href="{{ route('results.tabulation') }}" class="sidebar-link {{ request()->routeIs('results.tabulation') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18"/><path d="M3 12h18"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                <span class="sidebar-text">Tabulation Sheet</span>
            </a>
            <a href="{{ route('certificates.index') }}" class="sidebar-link {{ request()->routeIs('certificates.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 11 2 2 4-4"/></svg>
                <span class="sidebar-text">Certificates</span>
            </a>
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
             SMS VIEW
             ============================================== -->
        <div x-show="currentView === 'sms'" style="display: {{ $initialView === 'sms' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>SMS Management</span>
            </button>

            <!-- Menu links -->
            <a href="{{ route('sms.general-notice') }}" class="sidebar-link {{ request()->routeIs('sms.general-notice') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <span class="sidebar-text">Notice SMS</span>
            </a>
            <a href="{{ route('sms.result') }}" class="sidebar-link {{ request()->routeIs('sms.result') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span class="sidebar-text">Result SMS</span>
            </a>
            <a href="{{ route('sms.report') }}" class="sidebar-link {{ request()->routeIs('sms.report') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                <span class="sidebar-text">SMS Report</span>
            </a>
        </div>

        <!-- ==============================================
             ACADEMIC SETUP VIEW
             ============================================== -->
        <div x-show="currentView === 'academic'" style="display: {{ $initialView === 'academic' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>Academic Setup</span>
            </button>

            <!-- Menu links -->
            <a href="{{ route('classes.index') }}" class="sidebar-link {{ request()->routeIs('classes.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                <span class="sidebar-text">Class List</span>
            </a>
            <a href="{{ route('routine.index') }}" class="sidebar-link {{ request()->routeIs('routine.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span class="sidebar-text">Class Routine</span>
            </a>
            <a href="{{ route('sections.index') }}" class="sidebar-link {{ request()->routeIs('sections.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/></svg>
                <span class="sidebar-text">Sections</span>
            </a>
            <a href="{{ route('shifts.index') }}" class="sidebar-link {{ request()->routeIs('shifts.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span class="sidebar-text">Shifts</span>
            </a>
            <a href="{{ route('sessions.index') }}" class="sidebar-link {{ request()->routeIs('sessions.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
                <span class="sidebar-text">Sessions</span>
            </a>
            <a href="{{ route('branches.index') }}" class="sidebar-link {{ request()->routeIs('branches.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v12a2 2 0 0 0 2 2h10M18 21l3-3-3-3"/><circle cx="6" cy="3" r="1"/><circle cx="6" cy="18" r="1"/><circle cx="18" cy="18" r="1"/></svg>
                <span class="sidebar-text">Branches</span>
            </a>
            <a href="{{ route('subjects.index') }}" class="sidebar-link {{ request()->routeIs('subjects.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10M6 10h10"/></svg>
                <span class="sidebar-text">Subjects</span>
            </a>
            <a href="{{ route('grades.index') }}" class="sidebar-link {{ request()->routeIs('grades.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span class="sidebar-text">Grades</span>
            </a>
        </div>

        <!-- ==============================================
             FEES VIEW
             ============================================== -->
        <div x-show="currentView === 'fees'" style="display: {{ $initialView === 'fees' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>Fee Management</span>
            </button>

            <!-- Menu links -->
            <a href="{{ route('fees.categories.index') }}" class="sidebar-link {{ request()->routeIs('fees.categories.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <span class="sidebar-text">Fee Categories</span>
            </a>
            <a href="{{ route('fees.setup.index') }}" class="sidebar-link {{ request()->routeIs('fees.setup.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                <span class="sidebar-text">Fee Setup</span>
            </a>
            <a href="{{ route('fees.invoice.generate') }}" class="sidebar-link {{ request()->routeIs('fees.invoice.generate') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                <span class="sidebar-text">Generate Invoices</span>
            </a>
            <a href="{{ route('fees.collection.index') }}" class="sidebar-link {{ request()->routeIs('fees.collection.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 10h18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2z"/></svg>
                <span class="sidebar-text">Collect Fees</span>
            </a>
            <a href="{{ route('fees.reports.index') }}" class="sidebar-link {{ request()->routeIs('fees.reports.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                <span class="sidebar-text">Financial Reports</span>
            </a>
            <a href="{{ route('fees.reports.summary') }}" class="sidebar-link {{ request()->routeIs('fees.reports.summary') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="16" y1="14" x2="16" y2="18"/><path d="M16 10h.01M12 10h.01M8 10h.01M12 14h.01M8 14h.01M12 18h.01M8 18h.01"/></svg>
                <span class="sidebar-text">Category Summary</span>
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
                    <span class="sidebar-text">{{ $resource['label'] ?? ucwords(str_replace('_', ' ', $key)) }}</span>
                </a>
            @endforeach
        </div>
        @endif

        <!-- ==============================================
             ADMINISTRATION VIEW
             ============================================== -->
        <div x-show="currentView === 'admin'" style="display: {{ $initialView === 'admin' ? 'block' : 'none' }};" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="px-2">
            <!-- Back Button -->
            <button type="button" @click="currentView = 'main'" class="sidebar-back-btn border-none bg-transparent">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                <span>Administration</span>
            </button>

            <!-- Menu links -->
            <a href="{{ route('tyro-dashboard.users.index') }}" class="sidebar-link {{ request()->routeIs('tyro-dashboard.users.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span class="sidebar-text">Users</span>
            </a>
            <a href="{{ route('tyro-dashboard.roles.index') }}" class="sidebar-link {{ request()->routeIs('tyro-dashboard.roles.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span class="sidebar-text">Roles</span>
            </a>
            <a href="{{ route('tyro-dashboard.privileges.index') }}" class="sidebar-link {{ request()->routeIs('tyro-dashboard.privileges.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3"/></svg>
                <span class="sidebar-text">Privileges</span>
            </a>
            <a href="{{ route('tyro-dashboard.profile') }}" class="sidebar-link {{ request()->routeIs('tyro-dashboard.profile*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                <span class="sidebar-text">Settings</span>
            </a>
        </div>

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