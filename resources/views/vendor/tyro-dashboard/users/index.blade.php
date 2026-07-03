@extends('tyro-dashboard::layouts.admin')

@section('title', 'Users')

@push('styles')
<style>
    select option { background: #ffffff; color: #1f2937; }
    .dark select option { background: #0f1e2c; color: #ffffff; }
    .table th { background-color: transparent !important; }
    .modal-active { display: flex !important; animation: fadeIn 0.2s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="{ suspendModalOpen: false }">
    
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                Users
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Manage user accounts, roles, and permissions</p>
        </div>
        
        <a href="{{ route('tyro-dashboard.users.create') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Add User
        </a>
    </div>

    <!-- Filters Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 mb-8 relative z-20 no-print shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('tyro-dashboard.users.index') }}" method="GET" id="usersFilterForm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <!-- Search Input -->
                <div class="relative md:col-span-2">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Search Users</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 pl-10 pr-3 placeholder-gray-400" placeholder="Search users..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>

                <!-- Role Selector -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Role</label>
                    <div x-data="{ 
                        open: false, 
                        value: '{{ $filters['role'] ?? '' }}', 
                        label: '{{ !empty($filters['role']) ? ($roles->firstWhere('slug', $filters['role'])->name ?? 'All Roles') : 'All Roles' }}',
                        items: [
                            { value: '', label: 'All Roles' },
                            @foreach($roles as $role)
                                { value: '{{ $role->slug }}', label: '{{ $role->name }}' },
                            @endforeach
                        ],
                        select(val, txt) {
                            this.value = val;
                            this.label = txt;
                            this.open = false;
                            let inp = this.$refs.hiddenInput;
                            inp.value = val;
                            inp.dispatchEvent(new Event('input', { bubbles: true }));
                            inp.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                        <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span class="truncate" x-text="label"></span>
                            <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <input type="hidden" name="role" x-ref="hiddenInput" :value="value">
                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <template x-for="item in items" :key="item.value">
                                <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="item.label"></span>
                                    <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Status Selector & Action Row -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div class="sm:col-span-3">
                        <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Status</label>
                        <div x-data="{ 
                            open: false, 
                            value: '{{ $filters['status'] ?? '' }}', 
                            label: '{{ ($filters['status'] ?? '') === 'active' ? 'Active' : (($filters['status'] ?? '') === 'suspended' ? 'Suspended' : 'All Status') }}',
                            items: [
                                { value: '', label: 'All Status' },
                                { value: 'active', label: 'Active' },
                                { value: 'suspended', label: 'Suspended' }
                            ],
                            select(val, txt) {
                                this.value = val;
                                this.label = txt;
                                this.open = false;
                                let inp = this.$refs.hiddenInput;
                                inp.value = val;
                                inp.dispatchEvent(new Event('input', { bubbles: true }));
                                inp.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }" class="relative w-full text-gray-900 dark:text-white" @click.away="open = false">
                            <button type="button" @click="open = !open" class="w-full h-11 px-3 bg-gray-55/50 dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="truncate" x-text="label"></span>
                                <svg class="w-4 h-4 text-gray-455 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <input type="hidden" name="status" x-ref="hiddenInput" :value="value">
                            <div x-show="open" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                <template x-for="item in items" :key="item.value">
                                    <button type="button" @click="select(item.value, item.label)" class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="value == item.value ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                        <span x-text="item.label"></span>
                                        <svg x-show="value == item.value" class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="h-11 px-4 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center whitespace-nowrap active:scale-95 flex-1">Filter</button>
                        @if(!empty($filters['search']) || !empty($filters['role']) || !empty($filters['status']))
                            <a href="{{ route('tyro-dashboard.users.index') }}" class="h-11 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider flex items-center justify-center shadow-sm">Clear</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        @if($users->count())
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">User</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Roles</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Status</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Joined</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-44">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($users as $listUser)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4">
                            <a href="{{ route('tyro-dashboard.users.edit', $listUser->id) }}" class="flex items-center gap-3 group">
                                <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] flex items-center justify-center font-mono font-black text-themeBlue overflow-hidden shadow-sm">
                                    @if($listUser->profile_photo_path || ($listUser->use_gravatar && $listUser->email))
                                        <img src="{{ $listUser->profile_photo_url }}" alt="{{ $listUser->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($listUser->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-themeBlue transition-colors">{{ $listUser->name }}</div>
                                    <div class="text-[10px] text-gray-450 dark:text-gray-500 font-mono mt-0.5">{{ $listUser->email }}</div>
                                </div>
                            </a>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($listUser->roles as $role)
                                    <span class="px-2.5 py-0.5 bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue text-[9px] font-black uppercase tracking-wider rounded-lg border border-indigo-100 dark:border-white/[0.04]">{{ $role->name }}</span>
                                @empty
                                    <span class="px-2.5 py-0.5 bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-455 dark:text-gray-400 text-[9px] font-black uppercase tracking-wider rounded-lg">No Roles</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            @if(method_exists($listUser, 'isSuspended') && $listUser->isSuspended())
                                <span class="px-2.5 py-0.5 bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 text-[9px] font-black uppercase tracking-wider rounded-lg border border-red-200/20">Suspended</span>
                            @else
                                <span class="px-2.5 py-0.5 bg-themeGreen/10 text-themeGreen dark:text-themeGreen text-[9px] font-black uppercase tracking-wider rounded-lg border border-green-200/20">Active</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-sm font-bold text-gray-600 dark:text-gray-450">{{ $listUser->created_at->format('M d, Y') }}</td>
                        <td class="py-4 px-4">
                            <!-- Action Buttons Alignment (Rule 9) -->
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('tyro-dashboard.users.edit', $listUser->id) }}" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-themeBlue dark:border-gray-800 text-themeBlue hover:bg-themeBlue/10 flex items-center justify-center transition-all shadow-sm" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                @if($listUser->id !== $user->id)
                                    <form action="{{ route('tyro-dashboard.users.login-as', $listUser->id) }}" method="POST" class="inline animate-none" id="login-as-form-{{ $listUser->id }}">
                                        @csrf
                                        <button type="button" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-indigo-600 dark:border-gray-800 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 flex items-center justify-center transition-all shadow-sm" title="Login As" onclick="event.preventDefault(); showConfirm('Login As User', 'Are you sure you want to log in as {{ addslashes($listUser->name) }}?').then(confirmed => { if(confirmed) document.getElementById('login-as-form-{{ $listUser->id }}').submit(); })">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </button>
                                    </form>
                                @endif
                                @if(method_exists($listUser, 'isSuspended') && $listUser->isSuspended())
                                    <form action="{{ route('tyro-dashboard.users.unsuspend', $listUser->id) }}" method="POST" class="inline animate-none" id="unsuspend-form-{{ $listUser->id }}">
                                        @csrf
                                        <button type="button" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-themeGreen dark:border-gray-800 text-themeGreen hover:bg-themeGreen/10 flex items-center justify-center transition-all shadow-sm" title="Unsuspend" onclick="event.preventDefault(); showConfirm('Unsuspend User', 'Are you sure you want to unsuspend this user?').then(confirmed => { if(confirmed) document.getElementById('unsuspend-form-{{ $listUser->id }}').submit(); })">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </form>
                                @elseif(method_exists($listUser, 'suspend'))
                                    <button type="button" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-amber-600 dark:border-gray-800 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-955/20 flex items-center justify-center transition-all shadow-sm" title="Suspend" @click="openSuspendModal({{ $listUser->id }}, '{{ addslashes($listUser->name) }}')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0V10.5m-2.25 10.5h13.5c.621 0 1.125-.504 1.125-1.125V11.25c0-.621-.504-1.125-1.125-1.125H5.25c-.621 0-1.125.504-1.125 1.125v7.875c0 .621.504 1.125 1.125 1.125z" /></svg>
                                    </button>
                                @endif
                                @if($listUser->id !== $user->id)
                                    <form action="{{ route('tyro-dashboard.users.destroy', $listUser->id) }}" method="POST" class="inline animate-none" id="delete-user-form-{{ $listUser->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-red-600 dark:border-gray-800 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 flex items-center justify-center transition-all shadow-sm" title="Delete" onclick="event.preventDefault(); showDanger('Delete User', 'Are you sure you want to delete this user? This action cannot be undone.').then(confirmed => { if(confirmed) document.getElementById('delete-user-form-{{ $listUser->id }}').submit(); })">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.05]">
            {{ $users->links() }}
        </div>
        @endif
        @else
        <div class="py-20 text-center text-gray-500 font-bold uppercase tracking-wider">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            <h3 class="text-sm font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] mb-4">No users found</h3>
            <a href="{{ route('tyro-dashboard.users.create') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add User
            </a>
        </div>
        @endif
    </div>

    <!-- Suspend User Modal popup (Rule 3) -->
    <div id="suspendModal" class="hidden fixed inset-0 z-[9999] items-center justify-center bg-themeDark/40 backdrop-blur-md p-4" x-show="suspendModalOpen" x-transition>
        <div class="bg-white dark:bg-themeNavy w-full max-w-md rounded-3xl shadow-xl border border-gray-100 dark:border-white/[0.08]">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-white/[0.05] flex justify-between items-center">
                <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider">Suspend User</h3>
                <button type="button" class="text-gray-450 hover:text-red-500 transition-colors" @click="suspendModalOpen = false; closeModal('suspendModal')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="suspendForm" method="POST">
                @csrf
                <div class="p-6">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-5">
                        You are about to suspend <strong id="suspendUserName" class="text-gray-900 dark:text-white font-black"></strong>. This will revoke all their active sessions.
                    </p>
                    
                    <div class="mb-5">
                        <label for="reason" class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">
                            Reason for suspension <span class="text-gray-400 font-medium">(optional)</span>
                        </label>
                        <textarea id="reason" name="reason" class="w-full border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 p-3 placeholder-gray-450" rows="3" placeholder="Enter a reason for suspension..."></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.05] flex gap-3">
                    <button type="button" class="flex-1 h-11 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider transition-all" @click="suspendModalOpen = false; closeModal('suspendModal')">Cancel</button>
                    <button type="submit" class="flex-1 h-11 px-6 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">Suspend User</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function openSuspendModal(userId, userName) {
        document.getElementById('suspendForm').action = '{{ url(config('tyro-dashboard.route_prefix', 'dashboard')) }}/users/' + userId + '/suspend';
        document.getElementById('suspendUserName').textContent = userName;
        document.getElementById('reason').value = '';
        
        // Alpine data binding bridge
        const appDiv = document.querySelector('[x-data]');
        if(appDiv && appDiv.__x) {
            appDiv.__x.$data.suspendModalOpen = true;
        }
        openModal('suspendModal');
    }

    // Auto-focus search input and move cursor to end if search is present
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && searchInput.value) {
            searchInput.focus();
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }
    });
</script>
@endpush
