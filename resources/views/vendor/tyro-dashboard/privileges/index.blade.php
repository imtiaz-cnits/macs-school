@extends('tyro-dashboard::layouts.admin')

@section('title', 'Privileges')

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
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                </svg>
                Privileges
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Manage granular permissions that can be assigned to roles</p>
        </div>
        
        <a href="{{ route('tyro-dashboard.privileges.create') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Add Privilege
        </a>
    </div>

    <!-- Filters Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 mb-8 relative z-20 no-print shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('tyro-dashboard.privileges.index') }}" method="GET" id="privilegesFilterForm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <!-- Search Input -->
                <div class="relative md:col-span-3">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Search Privileges</label>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl !bg-white dark:!bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 pl-10 pr-3 placeholder-gray-400" placeholder="Search privileges..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="h-11 px-4 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center whitespace-nowrap active:scale-95 flex-1">Search</button>
                    @if(!empty($filters['search']))
                        <a href="{{ route('tyro-dashboard.privileges.index') }}" class="h-11 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-xl uppercase tracking-wider flex items-center justify-center shadow-sm">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Privileges Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        @if($privileges->count())
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Privilege</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Slug</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Description</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Roles</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-44">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($privileges as $privilege)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4">
                            <a href="{{ route('tyro-dashboard.privileges.show', $privilege->id) }}" class="flex items-center gap-3 group">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-themeBlue to-themeGreen text-white flex items-center justify-center shadow-sm">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-themeBlue transition-colors">{{ $privilege->name }}</span>
                            </a>
                        </td>
                        <td class="py-4 px-4">
                            <span class="px-2 py-0.5 bg-gray-55 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-655 dark:text-gray-300 text-[10px] font-mono rounded-lg inline-block">{{ $privilege->slug }}</span>
                        </td>
                        <td class="py-4 px-4 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ Str::limit($privilege->description, 50) ?: '-' }}</td>
                        <td class="py-4 px-4">
                            <span class="px-2.5 py-0.5 bg-indigo-55 dark:bg-themeBlue/10 text-themeBlue text-[9px] font-black uppercase tracking-wider rounded-lg border border-indigo-100 dark:border-white/[0.04]">{{ $privilege->roles_count }} Roles</span>
                        </td>
                        <td class="py-4 px-4">
                            <!-- Action Buttons Alignment (Rule 9) -->
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('tyro-dashboard.privileges.show', $privilege->id) }}" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-themeBlue dark:border-gray-800 text-themeBlue hover:bg-themeBlue/10 flex items-center justify-center transition-all shadow-sm" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </a>
                                <a href="{{ route('tyro-dashboard.privileges.edit', $privilege->id) }}" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-themeBlue dark:border-gray-800 text-themeBlue hover:bg-themeBlue/10 flex items-center justify-center transition-all shadow-sm" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                <form action="{{ route('tyro-dashboard.privileges.destroy', $privilege->id) }}" method="POST" class="inline animate-none" id="delete-privilege-form-{{ $privilege->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="h-8 w-8 rounded-xl border border-gray-150 hover:border-red-600 dark:border-gray-800 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 flex items-center justify-center transition-all shadow-sm" title="Delete" onclick="event.preventDefault(); showDanger('Delete Privilege', 'Are you sure you want to delete this privilege? It will be removed from all roles.').then(confirmed => { if(confirmed) document.getElementById('delete-privilege-form-{{ $privilege->id }}').submit(); })">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($privileges->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.05]">
            {{ $privileges->links() }}
        </div>
        @endif
        @else
        <!-- Corrected Giant Icon Bug (Width/Height set explicitly) -->
        <div class="py-20 text-center text-gray-500 font-bold uppercase tracking-wider">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
            </svg>
            <h3 class="text-sm font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] mb-4">No privileges found</h3>
            <a href="{{ route('tyro-dashboard.privileges.create') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Privilege
            </a>
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
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
