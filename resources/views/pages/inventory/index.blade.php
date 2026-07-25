@extends('tyro-dashboard::layouts.admin')

@section('title', 'Inventory & Stock Management')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<!-- Load Alpine.js to fix dropdown component issues -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Table padding override to align with MACS Design guidelines */
    .table th, .table td {
        padding: 0.875rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div x-data="inventoryPage()" x-init="init()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Inventory & Stock Management
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Track and manage school assets, stationery items, textbooks, and storybooks</p>
        </div>
        <button type="button" @click="openItemModal()" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add New Item
        </button>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Asset Cards -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-950/20 text-themeBlue rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="stats.assets">0</span>
            </div>
            <div class="mt-4">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">Assets & Equipment</h3>
                <p class="text-[10px] font-semibold text-gray-450 dark:text-gray-500 mt-0.5">Fans, Benches, Lights, Furniture</p>
            </div>
        </div>

        <!-- Stationery Cards -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 bg-purple-50 dark:bg-purple-950/20 text-purple-600 dark:text-purple-450 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </div>
                <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="stats.stationery">0</span>
            </div>
            <div class="mt-4">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">Stationery Stock</h3>
                <p class="text-[10px] font-semibold text-gray-450 dark:text-gray-500 mt-0.5">Pens, Papers, Reams, Registers</p>
            </div>
        </div>

        <!-- Books Cards -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/20 text-themeGreen rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="stats.books">0</span>
            </div>
            <div class="mt-4">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">Books & Storybooks</h3>
                <p class="text-[10px] font-semibold text-gray-450 dark:text-gray-500 mt-0.5">Textbooks, Library Materials</p>
            </div>
        </div>

        <!-- Warning Cards -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/20 text-rose-600 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="stats.outOfStock">0</span>
            </div>
            <div class="mt-4">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest">Out of Stock</h3>
                <p class="text-[10px] font-semibold text-gray-450 dark:text-gray-500 mt-0.5">Items needing replenishment</p>
            </div>
        </div>
    </div>

    <!-- Filters Panel Card -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm mb-8 no-print">
        <div class="flex flex-col lg:flex-row gap-4 items-end">
            <!-- Search Database -->
            <div class="flex-grow w-full">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Search stock items</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" x-model="filters.search" @input.debounce.350ms="fetchItems()" placeholder="Enter item name..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 pl-11 placeholder-gray-400">
                </div>
            </div>

            <!-- Type Filter (Custom Select) -->
            <div class="relative w-full lg:w-56" @click.away="activeDropdown === 'type' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Filter Type</label>
                <button type="button" @click="activeDropdown = activeDropdown === 'type' ? null : 'type'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="typeText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'type'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectFilter('type', '', 'All Types')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                        All Types
                    </button>
                    <button type="button" @click="selectFilter('type', 'asset', 'Asset')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="filters.type === 'asset' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                        <span>Asset</span>
                    </button>
                    <button type="button" @click="selectFilter('type', 'stationery', 'Stationery')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="filters.type === 'stationery' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                        <span>Stationery</span>
                    </button>
                    <button type="button" @click="selectFilter('type', 'book', 'Textbook')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="filters.type === 'book' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                        <span>Textbook</span>
                    </button>
                </div>
            </div>

            <!-- Class Filter (Custom Select - only relevant for books, but always handy) -->
            <div class="relative w-full lg:w-56" @click.away="activeDropdown === 'class' && (activeDropdown = null)">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Class</label>
                <button type="button" @click="activeDropdown = activeDropdown === 'class' ? null : 'class'" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="classText"></span>
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeDropdown === 'class'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                    <button type="button" @click="selectFilter('class_id', '', 'All Classes')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                        All Classes
                    </button>
                    <template x-for="c in classes" :key="c.id">
                        <button type="button" @click="selectFilter('class_id', c.id, c.class_name)" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="filters.class_id === c.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                            <span x-text="c.class_name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Reset Button -->
            <button type="button" @click="resetFilters()" class="w-full lg:w-auto h-11 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black px-6 rounded-xl transition-all uppercase tracking-widest flex items-center justify-center shrink-0">
                Reset
            </button>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-16">SL</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Item Profile</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-32">Type</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-32">Class</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-36">Current Stock</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right w-52">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading skeleton state -->
                    <template x-if="loading">
                        <template x-for="i in 5">
                            <tr class="animate-pulse">
                                <td class="py-4 px-4 text-center"><div class="h-4 w-6 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                <td class="py-4 px-4"><div class="h-4 w-48 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-2"></div><div class="h-3 w-32 bg-gray-100 dark:bg-gray-800/40 rounded-md"></div></td>
                                <td class="py-4 px-4"><div class="h-6 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-lg"></div></td>
                                <td class="py-4 px-4"><div class="h-4 w-12 bg-gray-250 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="py-4 px-4 text-center"><div class="h-4 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                <td class="py-4 px-4"><div class="h-8 w-32 bg-gray-200 dark:bg-gray-700/60 rounded-xl ml-auto"></div></td>
                            </tr>
                        </template>
                    </template>

                    <!-- Empty Data Placeholder -->
                    <template x-if="!loading && items.length === 0">
                        <tr>
                            <td colspan="6" class="py-12 text-center text-sm font-bold text-red-500 uppercase tracking-widest">No stock items found.</td>
                        </tr>
                    </template>

                    <!-- Items rows -->
                    <template x-if="!loading">
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                                <td class="py-4 px-4 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm" x-text="index + 1"></td>
                                <td class="py-4 px-4">
                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="item.name"></div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="item.description || 'No description provided.'"></div>
                                </td>
                                <td class="py-4 px-4">
                                    <!-- Type Badges -->
                                    <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg inline-block"
                                          :class="{
                                              'bg-blue-500/10 text-themeBlue border border-themeBlue/20': item.type === 'asset',
                                              'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20': item.type === 'stationery',
                                              'bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20': item.type === 'book'
                                          }"
                                          x-text="item.type">
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-sm font-semibold text-gray-600 dark:text-gray-400" x-text="item.class ? item.class.class_name : '--'"></td>
                                <td class="py-4 px-4 text-center">
                                    <span class="text-sm font-black" :class="item.current_quantity <= 0 ? 'text-red-500' : 'text-gray-900 dark:text-white'" x-text="item.current_quantity + ' ' + item.unit"></span>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Stock Adjust button (+/-) -->
                                        <button @click="openAdjustModal(item)" class="action-btn text-themeGreen hover:text-themeGreen hover:border-themeGreen" title="Adjust Stock (Plus/Minus)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                        <!-- Stock History button -->
                                        <button @click="openHistoryModal(item)" class="action-btn text-purple-600 hover:text-purple-600 hover:border-purple-600" title="Stock Transaction History">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <!-- Edit button -->
                                        <button @click="openItemModal(item)" class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue" title="Edit Item Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <!-- Delete button -->
                                        <button @click="deleteItem(item.id)" class="action-btn text-red-600 hover:text-red-800 hover:border-red-650" title="Delete Item">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==============================================
         MODAL 1: ADD/EDIT INVENTORY ITEM
         ============================================== -->
    <div x-show="modals.item" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-themeDark/40 backdrop-blur-md" x-transition>
        <div class="bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeDark/30 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest" x-text="itemForm.id ? 'Edit Item Details' : 'Add New Item'"></h3>
                <button type="button" @click="modals.item = false" class="text-gray-400 hover:text-gray-550 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form @submit.prevent="submitItemForm()">
                <!-- Modal Body -->
                <div class="p-6 space-y-4 text-left">
                    <!-- Item Name -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-550 dark:text-gray-450 uppercase tracking-widest mb-1.5 ml-1">Item Name *</label>
                        <input type="text" x-model="itemForm.name" required placeholder="e.g. Ceiling Fan, Offset Paper..." class="w-full h-11 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Item Type -->
                        <div class="relative" @click.away="dropdowns.formType = false">
                            <label class="block text-[10px] font-black text-gray-550 dark:text-gray-450 uppercase tracking-widest mb-1.5 ml-1">Type *</label>
                            <button type="button" @click="dropdowns.formType = !dropdowns.formType" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                                <span class="capitalize" x-text="itemForm.type || 'Select Type'"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="dropdowns.formType" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                                <button type="button" @click="itemForm.type = 'asset'; dropdowns.formType = false" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 transition-colors">Asset</button>
                                <button type="button" @click="itemForm.type = 'stationery'; dropdowns.formType = false" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 transition-colors">Stationery</button>
                                <button type="button" @click="itemForm.type = 'book'; dropdowns.formType = false" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 transition-colors">Textbook</button>
                            </div>
                        </div>

                        <!-- Unit -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-450 uppercase tracking-widest mb-1.5 ml-1">Unit *</label>
                            <input type="text" x-model="itemForm.unit" required placeholder="e.g. pcs, box, ream" class="w-full h-11 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250">
                        </div>
                    </div>

                    <!-- Class Select (only visible if type is textbook) -->
                    <div x-show="itemForm.type === 'book'" x-transition @click.away="dropdowns.formClass = false" class="relative">
                        <label class="block text-[10px] font-black text-gray-550 dark:text-gray-450 uppercase tracking-widest mb-1.5 ml-1">Class *</label>
                        <button type="button" @click="dropdowns.formClass = !dropdowns.formClass" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-205 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                            <span x-text="formClassText"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="dropdowns.formClass" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="itemForm.class_id = ''; formClassText = 'Select Class'; dropdowns.formClass = false" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">Select Class</button>
                            <template x-for="c in classes" :key="c.id">
                                <button type="button" @click="itemForm.class_id = c.id; formClassText = c.class_name; dropdowns.formClass = false" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-700 dark:text-gray-200 transition-colors" x-text="c.class_name"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-550 dark:text-gray-450 uppercase tracking-widest mb-1.5 ml-1">Description</label>
                        <textarea x-model="itemForm.description" placeholder="Optional notes about location, vendor, or item details..." rows="3" class="w-full p-3 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeDark/30 border-t border-gray-100 dark:border-white/[0.06] flex justify-end gap-3">
                    <button type="button" @click="modals.item = false" class="h-10 px-5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-lg transition-all uppercase tracking-widest">Cancel</button>
                    <button type="submit" class="h-10 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-lg shadow-sm hover:shadow-md transition-all uppercase tracking-widest" x-text="itemForm.id ? 'Save Changes' : 'Create Item'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==============================================
         MODAL 2: ADJUST STOCK (Plus / Minus)
         ============================================== -->
    <div x-show="modals.adjust" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-themeDark/40 backdrop-blur-md" x-transition>
        <div class="bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeDark/30 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Adjust Stock</h3>
                <button type="button" @click="modals.adjust = false" class="text-gray-400 hover:text-gray-550 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form @submit.prevent="submitAdjustForm()">
                <!-- Modal Body -->
                <div class="p-6 space-y-4 text-left">
                    <div class="p-4 bg-gray-50 dark:bg-themeDark/40 rounded-2xl border border-gray-100 dark:border-white/[0.05]">
                        <div class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Selected Item</div>
                        <div class="text-sm font-black text-gray-900 dark:text-white mt-0.5" x-text="selectedItem.name"></div>
                        <div class="text-xs font-bold text-gray-500 mt-0.5">Current Stock: <span class="text-themeBlue" x-text="selectedItem.current_quantity + ' ' + selectedItem.unit"></span></div>
                    </div>

                    <!-- Adjust Direction Type Toggle -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-550 dark:text-gray-450 uppercase tracking-widest mb-1.5 ml-1">Adjustment Type *</label>
                        <div class="flex gap-3">
                            <button type="button" @click="adjustForm.type = 'in'" class="flex-1 py-2.5 rounded-xl border text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1.5"
                                    :class="adjustForm.type === 'in' ? 'bg-themeGreen text-white border-themeGreen shadow-sm' : 'bg-gray-50/50 dark:bg-themeDark border-gray-150 dark:border-gray-800 text-gray-400'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Add Stock (Plus)
                            </button>
                            <button type="button" @click="adjustForm.type = 'out'" class="flex-1 py-2.5 rounded-xl border text-xs font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1.5"
                                    :class="adjustForm.type === 'out' ? 'bg-rose-600 text-white border-rose-650 shadow-sm' : 'bg-gray-50/50 dark:bg-themeDark border-gray-150 dark:border-gray-800 text-gray-400'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                Discard / Reduce (Minus)
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Quantity -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-455 uppercase tracking-widest mb-1.5 ml-1">Quantity *</label>
                            <input type="number" min="1" x-model="adjustForm.quantity" required class="w-full h-11 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250">
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-555 dark:text-gray-455 uppercase tracking-widest mb-1.5 ml-1">Date *</label>
                            <input type="date" x-model="adjustForm.date" required class="w-full h-11 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250">
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-550 dark:text-gray-450 uppercase tracking-widest mb-1.5 ml-1">Remarks / Reason *</label>
                        <input type="text" x-model="adjustForm.remarks" required placeholder="e.g. Purchased 10 new pcs, Broken during class..." class="w-full h-11 px-3.5 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250">
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeDark/30 border-t border-gray-100 dark:border-white/[0.06] flex justify-end gap-3">
                    <button type="button" @click="modals.adjust = false" class="h-10 px-5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-lg transition-all uppercase tracking-widest">Cancel</button>
                    <button type="submit" class="h-10 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-lg shadow-sm hover:shadow-md transition-all uppercase tracking-widest">Apply Adjust</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==============================================
         MODAL 3: INVENTORY TRANSACTION HISTORY
         ============================================== -->
    <div x-show="modals.history" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-themeDark/40 backdrop-blur-md" x-transition>
        <div class="bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeDark/30 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Transaction History</h3>
                    <p class="text-[10px] font-semibold text-gray-500 mt-0.5" x-text="selectedItem.name"></p>
                </div>
                <button type="button" @click="modals.history = false" class="text-gray-400 hover:text-gray-550 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6 max-h-[400px] overflow-y-auto">
                <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Date</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-24">Type</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-24">Quantity</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Remarks</th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-2 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Operator</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loading Skeleton -->
                            <template x-if="loadingHistory">
                                <template x-for="i in 3">
                                    <tr class="animate-pulse">
                                        <td class="py-2.5 px-3"><div class="h-3 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                        <td class="py-2.5 px-3 text-center"><div class="h-5 w-12 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                        <td class="py-2.5 px-3 text-center"><div class="h-3 w-8 bg-gray-200 dark:bg-gray-700/60 rounded-md mx-auto"></div></td>
                                        <td class="py-2.5 px-3"><div class="h-3 w-32 bg-gray-250 dark:bg-gray-700/60 rounded-md"></div></td>
                                        <td class="py-2.5 px-3"><div class="h-3 w-16 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                    </tr>
                                </template>
                            </template>

                            <!-- Empty History -->
                            <template x-if="!loadingHistory && historyLogs.length === 0">
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-xs font-bold text-gray-400 uppercase tracking-widest">No transactions logged for this item yet.</td>
                                </tr>
                            </template>

                            <!-- History Rows -->
                            <template x-if="!loadingHistory">
                                <template x-for="log in historyLogs" :key="log.id">
                                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04] text-xs font-semibold">
                                        <td class="py-2.5 px-3 font-mono text-gray-500 dark:text-gray-400" x-text="log.date"></td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider inline-block"
                                                  :class="log.type === 'in' ? 'bg-green-500/10 text-themeGreen' : 'bg-rose-500/10 text-rose-500'"
                                                  x-text="log.type === 'in' ? 'IN' : 'OUT'"></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-mono font-black text-gray-900 dark:text-white" x-text="log.quantity"></td>
                                        <td class="py-2.5 px-3 text-gray-750 dark:text-gray-300" x-text="log.remarks || '--'"></td>
                                        <td class="py-2.5 px-3 text-gray-500 dark:text-gray-450" x-text="log.user ? log.user.name : 'System'"></td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-themeDark/30 border-t border-gray-100 dark:border-white/[0.06] flex justify-end">
                <button type="button" @click="modals.history = false" class="h-10 px-6 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-655 dark:text-gray-300 text-xs font-black rounded-lg transition-all uppercase tracking-widest">Close</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    function inventoryPage() {
        return {
            loading: false,
            loadingHistory: false,
            activeDropdown: null,
            
            // Dropdown helper labels
            typeText: 'All Types',
            classText: 'All Classes',
            formClassText: 'Select Class',

            // Data storage
            items: [],
            classes: [],
            historyLogs: [],
            stats: {
                assets: 0,
                stationery: 0,
                books: 0,
                outOfStock: 0
            },

            // Filtering inputs
            filters: {
                search: '',
                type: '',
                class_id: ''
            },

            // Modal states
            modals: {
                item: false,
                adjust: false,
                history: false
            },

            // Alpine dropdown show state within modals
            dropdowns: {
                formType: false,
                formClass: false
            },

            // Forms structures
            itemForm: {
                id: '',
                name: '',
                type: 'asset',
                class_id: '',
                description: '',
                unit: 'pcs'
            },

            selectedItem: {},
            adjustForm: {
                inventory_item_id: '',
                type: 'in',
                quantity: 1,
                date: new Date().toISOString().split('T')[0],
                remarks: ''
            },

            async init() {
                this.loading = true;
                try {
                    // Load Classes
                    let classRes = await axios.get('/ajax/classes', getAuthHeaders());
                    this.classes = classRes.data.classData || [];
                    
                    await this.fetchItems();
                } catch (e) {
                    console.error(e);
                    showAlert("Failed to initialize inventory dashboard filters.", "Error");
                }
            },

            async fetchItems() {
                this.loading = true;
                try {
                    let query = new URLSearchParams(this.filters).toString();
                    let res = await axios.get(`/ajax/inventory/items?${query}`, getAuthHeaders());
                    this.items = res.data.items || [];
                    this.calculateStats();
                } catch (e) {
                    console.error(e);
                    showAlert("Failed to retrieve inventory items.", "Error");
                } finally {
                    this.loading = false;
                }
            },

            calculateStats() {
                this.stats.assets = this.items.filter(i => i.type === 'asset').length;
                this.stats.stationery = this.items.filter(i => i.type === 'stationery').length;
                this.stats.books = this.items.filter(i => i.type === 'book').length;
                this.stats.outOfStock = this.items.filter(i => i.current_quantity <= 0).length;
            },

            selectFilter(field, val, text) {
                this.filters[field] = val;
                if (field === 'type') this.typeText = text;
                if (field === 'class_id') this.classText = text;
                this.activeDropdown = null;
                this.fetchItems();
            },

            resetFilters() {
                this.filters.search = '';
                this.filters.type = '';
                this.filters.class_id = '';
                this.typeText = 'All Types';
                this.classText = 'All Classes';
                this.fetchItems();
            },

            openItemModal(item = null) {
                if (item) {
                    this.itemForm = {
                        id: item.id,
                        name: item.name,
                        type: item.type,
                        class_id: item.class_id || '',
                        description: item.description || '',
                        unit: item.unit
                    };
                    const matchedClass = this.classes.find(c => c.id === item.class_id);
                    this.formClassText = matchedClass ? matchedClass.class_name : 'Select Class';
                } else {
                    this.itemForm = {
                        id: '',
                        name: '',
                        type: 'asset',
                        class_id: '',
                        description: '',
                        unit: 'pcs'
                    };
                    this.formClassText = 'Select Class';
                }
                this.dropdowns.formClass = false;
                this.dropdowns.formType = false;
                this.modals.item = true;
            },

            async submitItemForm() {
                try {
                    let res = await axios.post('/ajax/inventory/items', this.itemForm, getAuthHeaders());
                    showSuccess(res.data.message);
                    this.modals.item = false;
                    this.fetchItems();
                } catch (err) {
                    let errMsg = err.response?.data?.message || "Failed to save inventory item.";
                    showAlert(errMsg, "Error");
                }
            },

            openAdjustModal(item) {
                this.selectedItem = item;
                this.adjustForm = {
                    inventory_item_id: item.id,
                    type: 'in',
                    quantity: 1,
                    date: new Date().toISOString().split('T')[0],
                    remarks: ''
                };
                this.modals.adjust = true;
            },

            async submitAdjustForm() {
                try {
                    let res = await axios.post('/ajax/inventory/adjust', this.adjustForm, getAuthHeaders());
                    showSuccess(res.data.message);
                    this.modals.adjust = false;
                    this.fetchItems();
                } catch (err) {
                    let errMsg = err.response?.data?.message || "Failed to adjust stock level.";
                    showAlert(errMsg, "Stock Error");
                }
            },

            async openHistoryModal(item) {
                this.selectedItem = item;
                this.historyLogs = [];
                this.modals.history = true;
                this.loadingHistory = true;
                try {
                    let res = await axios.get(`/ajax/inventory/logs?inventory_item_id=${item.id}`, getAuthHeaders());
                    this.historyLogs = res.data.logs || [];
                } catch (err) {
                    showAlert("Failed to retrieve audit log history.", "Error");
                } finally {
                    this.loadingHistory = false;
                }
            },

            async deleteItem(id) {
                const confirmed = await showDanger(
                    "Delete Inventory Item", 
                    "Are you sure you want to delete this inventory item? All stock records and history logs will be permanently deleted."
                );
                if (!confirmed) return;

                try {
                    let res = await axios.delete(`/ajax/inventory/items/${id}`, getAuthHeaders());
                    showSuccess(res.data.message);
                    this.fetchItems();
                } catch (e) {
                    showAlert("Authorization Error: Delete Failed!", "Error");
                }
            }
        };
    }

    const getAuthHeaders = () => ({ 
        headers: { 
            'Accept': 'application/json', 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
        } 
    });
</script>
@endpush
