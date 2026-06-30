

<aside class="sidebar group/sidebar !overflow-y-hidden bg-[var(--sidebar)] border-r border-[var(--sidebar-border)]" id="sidebar">
    <div class="sidebar-header h-16 flex items-center !px-5 border-b border-[var(--sidebar-border)]">
       <a href="{{ route('tyro-dashboard.index') }}" class="sidebar-logo flex items-center gap-2.5 !no-underline !border-none">
            <div class="!w-10 !h-10 !min-w-[40px] !min-h-[40px] overflow-hidden !bg-white !rounded-xl !flex !items-center !justify-center border border-white/20 !shrink-0 shadow-md">
                <img src="{{ asset('img/macs_logo.jpeg') }}" 
                     style="width: 100% !important; height: 100% !important; object-fit: contain !important; display: block !important; padding: 1px !important;" 
                     alt="{{ config('app.name', 'MACS School & College') }} Logo">
            </div>
            
            <span class="sidebar-logo-text sidebar-text !font-black !whitespace-nowrap !overflow-hidden !text-ellipsis bg-gradient-to-r from-themeBlue to-themeGreen bg-clip-text !text-transparent !text-[0.95rem] tracking-tight uppercase">
                {{ config('app.name', 'MACS School & College') }}
            </span>
        </a>
    </div>
   
    <nav class="sidebar-nav !overflow-y-auto !flex-1 !pt-4 scrollbar-thin scrollbar-thumb-[var(--border)] scrollbar-track-transparent">
        
        <div class="sidebar-section mb-2 !px-1 group relative">
            <a href="{{ route('dashboard.dashboard') }}" class="group/link sidebar-link !mx-2 !mb-1 py-2.5 !px-4 !rounded-xl !text-[14px] !font-semibold !normal-case !tracking-normal !text-[var(--sidebar-foreground)] border-l-4 border-transparent transition-all duration-150 ease-in-out whitespace-nowrap overflow-hidden !no-underline !shadow-none hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!border-themeBlue [&.active]:!pl-3 !flex !items-center !gap-3 group-[.collapsed]/sidebar:!justify-center group-[.collapsed]/sidebar:!py-2.5 group-[.collapsed]/sidebar:!px-0 group-[.collapsed]/sidebar:!mx-2.5 group-[.collapsed]/sidebar:!rounded-lg {{ request()->routeIs('dashboard.dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="10" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                <span class="sidebar-text group-[.collapsed]/sidebar:hidden">Dashboard</span>
            </a>
        </div>

        <div class="sidebar-section mb-2 !px-1 group relative">
            <a href="{{ route('tyro-dashboard.profile') }}" class="group/link sidebar-link !mx-2 !mb-1 py-2.5 !px-4 !rounded-xl !text-[14px] !font-semibold !normal-case !tracking-normal !text-[var(--sidebar-foreground)] border-l-4 border-transparent transition-all duration-150 ease-in-out whitespace-nowrap overflow-hidden !no-underline !shadow-none hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!border-themeBlue [&.active]:!pl-3 !flex !items-center !gap-3 group-[.collapsed]/sidebar:!justify-center group-[.collapsed]/sidebar:!py-2.5 group-[.collapsed]/sidebar:!px-0 group-[.collapsed]/sidebar:!mx-2.5 group-[.collapsed]/sidebar:!rounded-lg {{ request()->routeIs('tyro-dashboard.profile*') ? 'active' : '' }}">
                <svg class="w-5 h-5 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="sidebar-text group-[.collapsed]/sidebar:hidden">My Profile</span>
            </a>
        </div>

        @if(auth()->check() && (auth()->user()->hasRole('editor') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin')))
        
        @php $isStudentOpen = request()->routeIs('students.*', 'student.*', 'id-cards.*'); @endphp
        <div class="sidebar-section mb-2 !px-1 group relative {{ $isStudentOpen ? 'open' : '' }}">
            <div class="sidebar-section-title cursor-pointer flex items-center justify-between select-none py-2.5 !px-4 !mx-2 !mb-1 !rounded-xl !text-[14px] !font-semibold !normal-case !tracking-normal !text-[var(--sidebar-foreground)] border-l-4 border-transparent transition-all duration-150 ease-in-out whitespace-nowrap overflow-hidden !no-underline !shadow-none hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] group-[.open]:!bg-[var(--sidebar-accent)] group-[.open]:!text-[var(--sidebar-accent-foreground)] group-[.open]:!border-themeBlue group-[.open]:!pl-3 group-[.collapsed]/sidebar:!justify-center group-[.collapsed]/sidebar:!py-2.5 group-[.collapsed]/sidebar:!px-0 group-[.collapsed]/sidebar:!mx-2.5 group-[.collapsed]/sidebar:!rounded-lg !gap-3" onclick="toggleMenuSection(this)">
                <svg class="w-5 h-5 opacity-70 shrink-0 group-hover/section:opacity-100 group-[.open]:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10v6M2 10l10-5 10 5-10 5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg><span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">Student Management</span>
                <svg class="chevron-icon w-4 h-4 transition-transform duration-300 ease-in-out opacity-90 shrink-0 group-[.open]:rotate-90 group-[.open]:opacity-100 group-[.collapsed]/sidebar:!hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
            </div>
            <div class="sidebar-submenu max-h-0 overflow-hidden transition-[max-height,padding] duration-300 ease-in-out !bg-transparent !rounded-none border-l border-[var(--sidebar-border)] !ml-[27px] !mr-2 !pl-1.5 !pt-0 !pb-0 group-[.open]:max-h-[1500px] group-[.open]:!py-1.5 group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:flex-col group-[.collapsed]/sidebar:group-hover/section:absolute group-[.collapsed]/sidebar:group-hover/section:left-[69px] group-[.collapsed]/sidebar:group-hover/section:top-0 group-[.collapsed]/sidebar:group-hover/section:!bg-[var(--sidebar)] group-[.collapsed]/sidebar:group-hover/section:border group-[.collapsed]/sidebar:group-hover/section:!border-[var(--sidebar-border)] group-[.collapsed]/sidebar:group-hover/section:rounded-lg group-[.collapsed]/sidebar:group-hover/section:shadow-xl group-[.collapsed]/sidebar:group-hover/section:min-w-[220px] group-[.collapsed]/sidebar:group-hover/section:z-[1000] group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!max-h-none group-[.collapsed]/sidebar:group-hover/section:!overflow-visible group-[.collapsed]/sidebar:group-hover/section:!margin-0 group-[.collapsed]/sidebar:group-hover/section:before:content-[''] group-[.collapsed]/sidebar:group-hover/section:before:absolute group-[.collapsed]/sidebar:group-hover/section:before:top-0 group-[.collapsed]/sidebar:group-hover/section:before:-left-5 group-[.collapsed]/sidebar:group-hover/section:before:w-5 group-[.collapsed]/sidebar:group-hover/section:before:h-full group-[.collapsed]/sidebar:group-hover/section:before:bg-transparent group-[.collapsed]/sidebar:group-hover/section:before:z-[-1]">
                <a href="{{ route('students.index') }}" class="group/link sidebar-link !mx-1.5 !mb-[2px] py-1.5 !px-2.5 !pl-4 !rounded-lg !text-[13px] !font-medium !text-[var(--sidebar-foreground)] !normal-case !tracking-normal opacity-85 hover:opacity-100 hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:opacity-100 [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!font-semibold [&.active]:!pl-3 !shadow-none !flex !items-center !gap-2 group-[.collapsed]/sidebar:group-hover/section:!justify-start group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!my-0.5 group-[.collapsed]/sidebar:group-hover/section:!mx-0 group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:!items-center group-[.collapsed]/sidebar:group-hover/section:!gap-2.5 group-[.collapsed]/sidebar:group-hover/section:!rounded-md group-[.collapsed]/sidebar:group-hover/section:!w-auto group-[.collapsed]/sidebar:group-hover/section:!text-left group-[.collapsed]/sidebar:group-hover/section:!bg-transparent group-[.collapsed]/sidebar:group-hover/section:!shadow-none group-[.collapsed]/sidebar:group-hover/section:hover:!bg-[var(--sidebar-accent)] group-[.collapsed]/sidebar:group-hover/section:hover:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!bg-[var(--sidebar-primary)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!text-[var(--sidebar-primary-foreground)] {{ request()->routeIs('students.*') ? 'active' : '' }}">
                    <svg class="!w-3 !h-3 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:!w-4 group-[.collapsed]/sidebar:group-hover/section:!h-4 group-[.collapsed]/sidebar:group-hover/section:!opacity-70 group-[.collapsed]/sidebar:group-hover/section:!block group-[.collapsed]/sidebar:group-hover/section:!m-0 group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-hover/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    <span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">Students Lists</span>
                </a>
                <a href="{{ route('student.admission') }}" class="group/link sidebar-link !mx-1.5 !mb-[2px] py-1.5 !px-2.5 !pl-4 !rounded-lg !text-[13px] !font-medium !text-[var(--sidebar-foreground)] !normal-case !tracking-normal opacity-85 hover:opacity-100 hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:opacity-100 [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!font-semibold [&.active]:!pl-3 !shadow-none !flex !items-center !gap-2 group-[.collapsed]/sidebar:group-hover/section:!justify-start group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!my-0.5 group-[.collapsed]/sidebar:group-hover/section:!mx-0 group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:!items-center group-[.collapsed]/sidebar:group-hover/section:!gap-2.5 group-[.collapsed]/sidebar:group-hover/section:!rounded-md group-[.collapsed]/sidebar:group-hover/section:!w-auto group-[.collapsed]/sidebar:group-hover/section:!text-left group-[.collapsed]/sidebar:group-hover/section:!bg-transparent group-[.collapsed]/sidebar:group-hover/section:!shadow-none group-[.collapsed]/sidebar:group-hover/section:hover:!bg-[var(--sidebar-accent)] group-[.collapsed]/sidebar:group-hover/section:hover:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!bg-[var(--sidebar-primary)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!text-[var(--sidebar-primary-foreground)] {{ request()->routeIs('student.admission') ? 'active' : '' }}">
                    <svg class="!w-3 !h-3 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:!w-4 group-[.collapsed]/sidebar:group-hover/section:!h-4 group-[.collapsed]/sidebar:group-hover/section:!opacity-70 group-[.collapsed]/sidebar:group-hover/section:!block group-[.collapsed]/sidebar:group-hover/section:!m-0 group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-hover/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                    <span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">Add New Students</span>
                </a>
                @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin'))
                <a href="{{ route('id-cards.index') }}" class="group/link sidebar-link !mx-1.5 !mb-[2px] py-1.5 !px-2.5 !pl-4 !rounded-lg !text-[13px] !font-medium !text-[var(--sidebar-foreground)] !normal-case !tracking-normal opacity-85 hover:opacity-100 hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:opacity-100 [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!font-semibold [&.active]:!pl-3 !shadow-none !flex !items-center !gap-2 group-[.collapsed]/sidebar:group-hover/section:!justify-start group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!my-0.5 group-[.collapsed]/sidebar:group-hover/section:!mx-0 group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:!items-center group-[.collapsed]/sidebar:group-hover/section:!gap-2.5 group-[.collapsed]/sidebar:group-hover/section:!rounded-md group-[.collapsed]/sidebar:group-hover/section:!w-auto group-[.collapsed]/sidebar:group-hover/section:!text-left group-[.collapsed]/sidebar:group-hover/section:!bg-transparent group-[.collapsed]/sidebar:group-hover/section:!shadow-none group-[.collapsed]/sidebar:group-hover/section:hover:!bg-[var(--sidebar-accent)] group-[.collapsed]/sidebar:group-hover/section:hover:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!bg-[var(--sidebar-primary)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!text-[var(--sidebar-primary-foreground)] {{ request()->routeIs('id-cards.index') ? 'active' : '' }}">
                    <svg class="!w-3 !h-3 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:!w-4 group-[.collapsed]/sidebar:group-hover/section:!h-4 group-[.collapsed]/sidebar:group-hover/section:!opacity-70 group-[.collapsed]/sidebar:group-hover/section:!block group-[.collapsed]/sidebar:group-hover/section:!m-0 group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-hover/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M8 16h8"/></svg>
                    <span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">ID Card Generation</span>
                </a>
                @endif
            </div>
        </div> 

        @php $isTeacherOpen = request()->routeIs('teachers.*', 'teacher.*'); @endphp
        <div class="sidebar-section mb-2 !px-1 group relative {{ $isTeacherOpen ? 'open' : '' }}">
            <div class="sidebar-section-title cursor-pointer flex items-center justify-between select-none py-2.5 !px-4 !mx-2 !mb-1 !rounded-xl !text-[14px] !font-semibold !normal-case !tracking-normal !text-[var(--sidebar-foreground)] border-l-4 border-transparent transition-all duration-150 ease-in-out whitespace-nowrap overflow-hidden !no-underline !shadow-none hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] group-[.open]:!bg-[var(--sidebar-accent)] group-[.open]:!text-[var(--sidebar-accent-foreground)] group-[.open]:!border-themeBlue group-[.open]:!pl-3 group-[.collapsed]/sidebar:!justify-center group-[.collapsed]/sidebar:!py-2.5 group-[.collapsed]/sidebar:!px-0 group-[.collapsed]/sidebar:!mx-2.5 group-[.collapsed]/sidebar:!rounded-lg !gap-3" onclick="toggleMenuSection(this)">
                <svg class="w-5 h-5 opacity-70 shrink-0 group-hover/section:opacity-100 group-[.open]:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">Teacher Management</span>
                <svg class="chevron-icon w-4 h-4 transition-transform duration-300 ease-in-out opacity-90 shrink-0 group-[.open]:rotate-90 group-[.open]:opacity-100 group-[.collapsed]/sidebar:!hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
            </div>
            <div class="sidebar-submenu max-h-0 overflow-hidden transition-[max-height,padding] duration-300 ease-in-out !bg-transparent !rounded-none border-l border-[var(--sidebar-border)] !ml-[27px] !mr-2 !pl-1.5 !pt-0 !pb-0 group-[.open]:max-h-[1500px] group-[.open]:!py-1.5 group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:flex-col group-[.collapsed]/sidebar:group-hover/section:absolute group-[.collapsed]/sidebar:group-hover/section:left-[69px] group-[.collapsed]/sidebar:group-hover/section:top-0 group-[.collapsed]/sidebar:group-hover/section:!bg-[var(--sidebar)] group-[.collapsed]/sidebar:group-hover/section:border group-[.collapsed]/sidebar:group-hover/section:!border-[var(--sidebar-border)] group-[.collapsed]/sidebar:group-hover/section:rounded-lg group-[.collapsed]/sidebar:group-hover/section:shadow-xl group-[.collapsed]/sidebar:group-hover/section:min-w-[220px] group-[.collapsed]/sidebar:group-hover/section:z-[1000] group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!max-h-none group-[.collapsed]/sidebar:group-hover/section:!overflow-visible group-[.collapsed]/sidebar:group-hover/section:!margin-0 group-[.collapsed]/sidebar:group-hover/section:before:content-[''] group-[.collapsed]/sidebar:group-hover/section:before:absolute group-[.collapsed]/sidebar:group-hover/section:before:top-0 group-[.collapsed]/sidebar:group-hover/section:before:-left-5 group-[.collapsed]/sidebar:group-hover/section:before:w-5 group-[.collapsed]/sidebar:group-hover/section:before:h-full group-[.collapsed]/sidebar:group-hover/section:before:bg-transparent group-[.collapsed]/sidebar:group-hover/section:before:z-[-1]">
                 <a href="{{ route('teachers.index') }}" class="group/link sidebar-link !mx-1.5 !mb-[2px] py-1.5 !px-2.5 !pl-4 !rounded-lg !text-[13px] !font-medium !text-[var(--sidebar-foreground)] !normal-case !tracking-normal opacity-85 hover:opacity-100 hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:opacity-100 [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!font-semibold [&.active]:!pl-3 !shadow-none !flex !items-center !gap-2 group-[.collapsed]/sidebar:group-hover/section:!justify-start group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!my-0.5 group-[.collapsed]/sidebar:group-hover/section:!mx-0 group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:!items-center group-[.collapsed]/sidebar:group-hover/section:!gap-2.5 group-[.collapsed]/sidebar:group-hover/section:!rounded-md group-[.collapsed]/sidebar:group-hover/section:!w-auto group-[.collapsed]/sidebar:group-hover/section:!text-left group-[.collapsed]/sidebar:group-hover/section:!bg-transparent group-[.collapsed]/sidebar:group-hover/section:!shadow-none group-[.collapsed]/sidebar:group-hover/section:hover:!bg-[var(--sidebar-accent)] group-[.collapsed]/sidebar:group-hover/section:hover:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!bg-[var(--sidebar-primary)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!text-[var(--sidebar-primary-foreground)] {{ request()->routeIs('teachers.index') ? 'active' : '' }}">
                    <svg class="!w-3 !h-3 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:!w-4 group-[.collapsed]/sidebar:group-hover/section:!h-4 group-[.collapsed]/sidebar:group-hover/section:!opacity-70 group-[.collapsed]/sidebar:group-hover/section:!block group-[.collapsed]/sidebar:group-hover/section:!m-0 group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-hover/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">Teachers List</span>
                </a>
                <a href="{{ route('teacher.add') }}" class="group/link sidebar-link !mx-1.5 !mb-[2px] py-1.5 !px-2.5 !pl-4 !rounded-lg !text-[13px] !font-medium !text-[var(--sidebar-foreground)] !normal-case !tracking-normal opacity-85 hover:opacity-100 hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:opacity-100 [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!font-semibold [&.active]:!pl-3 !shadow-none !flex !items-center !gap-2 group-[.collapsed]/sidebar:group-hover/section:!justify-start group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!my-0.5 group-[.collapsed]/sidebar:group-hover/section:!mx-0 group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:!items-center group-[.collapsed]/sidebar:group-hover/section:!gap-2.5 group-[.collapsed]/sidebar:group-hover/section:!rounded-md group-[.collapsed]/sidebar:group-hover/section:!w-auto group-[.collapsed]/sidebar:group-hover/section:!text-left group-[.collapsed]/sidebar:group-hover/section:!bg-transparent group-[.collapsed]/sidebar:group-hover/section:!shadow-none group-[.collapsed]/sidebar:group-hover/section:hover:!bg-[var(--sidebar-accent)] group-[.collapsed]/sidebar:group-hover/section:hover:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!bg-[var(--sidebar-primary)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!text-[var(--sidebar-primary-foreground)] {{ request()->routeIs('teacher.add') ? 'active' : '' }}">
                    <svg class="!w-3 !h-3 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:!w-4 group-[.collapsed]/sidebar:group-hover/section:!h-4 group-[.collapsed]/sidebar:group-hover/section:!opacity-70 group-[.collapsed]/sidebar:group-hover/section:!block group-[.collapsed]/sidebar:group-hover/section:!m-0 group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-hover/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                    <span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">Add Teacher</span>
                </a>    
            </div>
        </div> 

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
        @php $isResourcesOpen = request()->is('*resources*'); @endphp
        <div class="sidebar-section mb-2 !px-1 group relative {{ $isResourcesOpen ? 'open' : '' }}">
            <div class="sidebar-section-title cursor-pointer flex items-center justify-between select-none py-2.5 !px-4 !mx-2 !mb-1 !rounded-xl !text-[14px] !font-semibold !normal-case !tracking-normal !text-[var(--sidebar-foreground)] border-l-4 border-transparent transition-all duration-150 ease-in-out whitespace-nowrap overflow-hidden !no-underline !shadow-none hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] group-[.open]:!bg-[var(--sidebar-accent)] group-[.open]:!text-[var(--sidebar-accent-foreground)] group-[.open]:!border-themeBlue group-[.open]:!pl-3 group-[.collapsed]/sidebar:!justify-center group-[.collapsed]/sidebar:!py-2.5 group-[.collapsed]/sidebar:!px-0 group-[.collapsed]/sidebar:!mx-2.5 group-[.collapsed]/sidebar:!rounded-lg !gap-3" onclick="toggleMenuSection(this)">
                <svg class="w-5 h-5 opacity-70 shrink-0 group-hover/section:opacity-100 group-[.open]:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polygon points="2 17 12 22 22 17"/><polygon points="2 12 12 17 22 12"/></svg><span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">Resources</span>
                <svg class="chevron-icon w-4 h-4 transition-transform duration-300 ease-in-out opacity-90 shrink-0 group-[.open]:rotate-90 group-[.open]:opacity-100 group-[.collapsed]/sidebar:!hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
            </div>
            <div class="sidebar-submenu max-h-0 overflow-hidden transition-[max-height,padding] duration-300 ease-in-out !bg-transparent !rounded-none border-l border-[var(--sidebar-border)] !ml-[27px] !mr-2 !pl-1.5 !pt-0 !pb-0 group-[.open]:max-h-[1500px] group-[.open]:!py-1.5 group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:flex-col group-[.collapsed]/sidebar:group-hover/section:absolute group-[.collapsed]/sidebar:group-hover/section:left-[69px] group-[.collapsed]/sidebar:group-hover/section:top-0 group-[.collapsed]/sidebar:group-hover/section:!bg-[var(--sidebar)] group-[.collapsed]/sidebar:group-hover/section:border group-[.collapsed]/sidebar:group-hover/section:!border-[var(--sidebar-border)] group-[.collapsed]/sidebar:group-hover/section:rounded-lg group-[.collapsed]/sidebar:group-hover/section:shadow-xl group-[.collapsed]/sidebar:group-hover/section:min-w-[220px] group-[.collapsed]/sidebar:group-hover/section:z-[1000] group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!max-h-none group-[.collapsed]/sidebar:group-hover/section:!overflow-visible group-[.collapsed]/sidebar:group-hover/section:!margin-0 group-[.collapsed]/sidebar:group-hover/section:before:content-[''] group-[.collapsed]/sidebar:group-hover/section:before:absolute group-[.collapsed]/sidebar:group-hover/section:before:top-0 group-[.collapsed]/sidebar:group-hover/section:before:-left-5 group-[.collapsed]/sidebar:group-hover/section:before:w-5 group-[.collapsed]/sidebar:group-hover/section:before:h-full group-[.collapsed]/sidebar:group-hover/section:before:bg-transparent group-[.collapsed]/sidebar:group-hover/section:before:z-[-1]">
                @foreach($accessibleResources as $key => $resource)
                    <a href="{{ route('tyro-dashboard.resources.index', $key) }}" class="group/link sidebar-link !mx-1.5 !mb-[2px] py-1.5 !px-2.5 !pl-4 !rounded-lg !text-[13px] !font-medium !text-[var(--sidebar-foreground)] !normal-case !tracking-normal opacity-85 hover:opacity-100 hover:!bg-[var(--sidebar-accent)] hover:!text-[var(--sidebar-accent-foreground)] [&.active]:opacity-100 [&.active]:!bg-[var(--sidebar-accent)] [&.active]:!text-[var(--sidebar-accent-foreground)] [&.active]:!font-semibold [&.active]:!pl-3 !shadow-none !flex !items-center !gap-2 group-[.collapsed]/sidebar:group-hover/section:!justify-start group-[.collapsed]/sidebar:group-hover/section:!p-2 group-[.collapsed]/sidebar:group-hover/section:!my-0.5 group-[.collapsed]/sidebar:group-hover/section:!mx-0 group-[.collapsed]/sidebar:group-hover/section:!flex group-[.collapsed]/sidebar:group-hover/section:!items-center group-[.collapsed]/sidebar:group-hover/section:!gap-2.5 group-[.collapsed]/sidebar:group-hover/section:!rounded-md group-[.collapsed]/sidebar:group-hover/section:!w-auto group-[.collapsed]/sidebar:group-hover/section:!text-left group-[.collapsed]/sidebar:group-hover/section:!bg-transparent group-[.collapsed]/sidebar:group-hover/section:!shadow-none group-[.collapsed]/sidebar:group-hover/section:hover:!bg-[var(--sidebar-accent)] group-[.collapsed]/sidebar:group-hover/section:hover:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!bg-[var(--sidebar-primary)] group-[.collapsed]/sidebar:group-hover/section:[&.active]:!text-[var(--sidebar-primary-foreground)] {{ request()->is('*resources/'.$key.'*') ? 'active' : '' }}">
                        @if(isset($resource['icon']))
                            {!! $resource['icon'] !!}
                        @else
                            <svg class="!w-3 !h-3 opacity-70 shrink-0 group-hover/link:opacity-100 group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:!w-4 group-[.collapsed]/sidebar:group-hover/section:!h-4 group-[.collapsed]/sidebar:group-hover/section:!opacity-70 group-[.collapsed]/sidebar:group-hover/section:!block group-[.collapsed]/sidebar:group-hover/section:!m-0 group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-hover/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:opacity-100 group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        @endif
                        <span class="sidebar-text group-[.collapsed]/sidebar:hidden group-[.collapsed]/sidebar:group-hover/section:!inline-block group-[.collapsed]/sidebar:group-hover/section:!text-[13px] group-[.collapsed]/sidebar:group-hover/section:!font-semibold group-[.collapsed]/sidebar:group-hover/section:!text-[var(--sidebar-foreground)] group-[.collapsed]/sidebar:group-hover/section:!normal-case group-[.collapsed]/sidebar:group-hover/section:!tracking-normal group-[.collapsed]/sidebar:group-hover/section:group-hover/link:!text-[var(--sidebar-accent-foreground)] group-[.collapsed]/sidebar:group-hover/section:group-[.active]/link:!text-[var(--sidebar-primary-foreground)]">{{ $resource['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        @endif </nav>

    @php
        $sidebarUser = auth()->user();
        $hasPhoto = $sidebarUser && ((method_exists($sidebarUser, 'hasProfilePhotoColumn') && $sidebarUser->hasProfilePhotoColumn() && $sidebarUser->profile_photo_path) || (method_exists($sidebarUser, 'hasGravatarColumn') && $sidebarUser->hasGravatarColumn() && $sidebarUser->use_gravatar && $sidebarUser->email));
    @endphp

    @if($sidebarUser)
    <div class="sidebar-footer border-t border-[var(--sidebar-border)] p-2 relative" id="sidebarUserFooter">
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

        <!-- Toggle Button -->
        <button type="button" class="w-full flex items-center justify-between p-2 rounded-lg hover:bg-[var(--sidebar-accent)] hover:text-[var(--sidebar-accent-foreground)] transition-all duration-150 border-none bg-transparent cursor-pointer group/footerbtn group-[.collapsed]/sidebar:justify-center" onclick="toggleSidebarUserDropdown(event)">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 min-w-[36px] min-h-[36px] overflow-hidden rounded-full flex items-center justify-center bg-[var(--sidebar-foreground)] text-[var(--sidebar)] font-bold text-[14px] {{ $hasPhoto ? '!bg-none !p-0' : '' }}">
                    @if($hasPhoto)
                        <img src="{{ $sidebarUser->profile_photo_url }}" alt="{{ $sidebarUser->name }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr($sidebarUser->name ?? 'U', 0, 1)) }}
                    @endif
                </div>
                <div class="text-left min-w-0 group-[.collapsed]/sidebar:hidden">
                    <p class="text-[13px] font-semibold text-[var(--sidebar-foreground)] truncate leading-tight">{{ $sidebarUser->name }}</p>
                    <p class="text-[11px] text-[var(--sidebar-foreground)] opacity-70 truncate leading-tight mt-0.5">
                        @if(method_exists($sidebarUser, 'roles') && $sidebarUser->roles->count())
                            {{ $sidebarUser->roles->first()->name }}
                        @else
                            User
                        @endif
                    </p>
                </div>
            </div>
            <svg class="w-4 h-4 text-[var(--sidebar-foreground)] opacity-50 transition-transform duration-200 group-[.collapsed]/sidebar:hidden group-[.open-dropdown]/footerbtn:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>
    </div>
    @endif
</aside>

<script>
    function toggleMenuSection(element) {
        const currentSection = element.parentElement;
        const isOpen = currentSection.classList.contains('open');
        const submenu = currentSection.querySelector('.sidebar-submenu');
        
        // Close all other sections
        document.querySelectorAll('.sidebar-section').forEach(section => {
            if (section !== currentSection) {
                section.classList.remove('open');
                const sub = section.querySelector('.sidebar-submenu');
                if (sub) {
                    sub.style.maxHeight = null;
                }
            }
        });
        
        // Toggle the clicked one
        if (isOpen) {
            currentSection.classList.remove('open');
            if (submenu) {
                submenu.style.maxHeight = null;
            }
        } else {
            currentSection.classList.add('open');
            if (submenu) {
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
            }
        }
    }

    // Initialize open submenus on page load
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.sidebar-section.open').forEach(section => {
            const submenu = section.querySelector('.sidebar-submenu');
            if (submenu) {
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
            }
        });
    });

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