@extends('tyro-dashboard::layouts.admin')

@section('title', 'General Notice SMS Broadcast')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="w-full min-h-screen" x-data="noticeBroadcastApp()">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                General Notice Broadcast
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Filter students and send SMS to eligible guardian contacts.</p>
        </div>

        <!-- Current Balance & Mode Badges -->
        <div class="flex items-center gap-3">
            @if($testMode)
                <div class="px-3.5 py-1.5 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Test Mode Active
                </div>
            @endif

            @if($currentBalance !== null)
                <div class="px-4 py-2 bg-themeNavy/5 dark:bg-themeNavy border border-gray-200/60 dark:border-white/[0.08] rounded-xl text-xs font-bold text-gray-700 dark:text-gray-200 flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Balance: <span class="font-black text-themeGreen dark:text-green-400">৳{{ number_format($currentBalance, 2) }}</span></span>
                </div>
            @endif
        </div>
    </div>

    <!-- Flash Notifications -->
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-950/20 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Alert / Toast Banner from AJAX -->
    <template x-if="ajaxToast.message">
        <div class="mb-6 p-4 border-l-4 rounded-r-2xl shadow-sm text-sm font-bold flex items-center justify-between"
             :class="ajaxToast.type === 'success' ? 'bg-green-50 dark:bg-green-950/20 border-themeGreen text-themeGreen dark:text-green-400' : 'bg-red-50 dark:bg-red-950/20 border-red-500 text-red-700 dark:text-red-400'">
            <span x-text="ajaxToast.message"></span>
            <button type="button" @click="ajaxToast.message = ''" class="ml-4 text-xs opacity-70 hover:opacity-100">&times;</button>
        </div>
    </template>

    <!-- Sub-header Navigation Tabs -->
    <div class="mb-6 flex items-center gap-3">
        <button type="button" @click="activeMainTab = 'compose'"
                :class="activeMainTab === 'compose' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm font-black' : 'bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-300 font-bold border border-gray-150 dark:border-white/[0.08] hover:bg-gray-50 dark:hover:bg-themeDark/40'"
                class="px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>Compose Notice</span>
        </button>
        <button type="button" @click="activeMainTab = 'schedules'; fetchSchedules()"
                :class="activeMainTab === 'schedules' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm font-black' : 'bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-300 font-bold border border-gray-150 dark:border-white/[0.08] hover:bg-gray-50 dark:hover:bg-themeDark/40'"
                class="px-5 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Scheduled Broadcasts</span>
            <span x-show="scheduleCounts.pending > 0" x-text="scheduleCounts.pending" class="px-2 py-0.5 rounded-full text-[10px] bg-themeBlue/20 text-current font-black"></span>
        </button>
    </div>

    <!-- Tab 1: Compose Notice View -->
    <div x-show="activeMainTab === 'compose'">

    <!-- Main Broadcast Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-10 shadow-sm hover:shadow-md transition-all duration-300">
               <!-- Recipient Source Selector Segmented Tabs -->
        <div class="mb-8">
            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                Recipient Source <span class="text-red-500 ml-0.5">*</span>
            </label>
            <div class="p-1.5 bg-gray-50/80 dark:bg-themeDark/60 border border-gray-150 dark:border-white/[0.08] rounded-2xl flex flex-col sm:flex-row gap-2">
                <!-- Option 1: Filtered Students -->
                <button type="button" @click="setRecipientSource('students')"
                        :class="filters.recipient_source === 'students' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm font-black' : 'text-gray-600 dark:text-gray-300 font-bold hover:bg-white/60 dark:hover:bg-themeNavy/50'"
                        class="flex-1 py-3 px-4 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Filtered Students</span>
                </button>

                <!-- Option 2: Custom Mobile Numbers -->
                <button type="button" @click="setRecipientSource('custom')"
                        :class="filters.recipient_source === 'custom' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm font-black' : 'text-gray-600 dark:text-gray-300 font-bold hover:bg-white/60 dark:hover:bg-themeNavy/50'"
                        class="flex-1 py-3 px-4 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Custom Mobile Numbers</span>
                </button>

                <!-- Option 3: Both (Students & Custom Numbers) -->
                <button type="button" @click="setRecipientSource('both')"
                        :class="filters.recipient_source === 'both' ? 'bg-gradient-to-r from-themeBlue to-themeGreen text-white shadow-sm font-black' : 'text-gray-600 dark:text-gray-300 font-bold hover:bg-white/60 dark:hover:bg-themeNavy/50'"
                        class="flex-1 py-3 px-4 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Both (Students & Custom Numbers)</span>
                </button>
            </div>
        </div>

        <!-- Custom Mobile Numbers Input Section (Shown for 'custom' or 'both') -->
        <div x-show="filters.recipient_source === 'custom' || filters.recipient_source === 'both'"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mb-8 p-6 bg-gray-50/50 dark:bg-themeDark/40 border-2 border-dashed border-gray-200 dark:border-white/[0.08] rounded-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase block">
                        Custom Recipient Mobile Numbers <span class="text-red-500 ml-0.5" x-show="filters.recipient_source === 'custom'">*</span>
                    </label>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium mt-0.5">
                        Enter numbers separated by commas or new lines.
                    </p>
                </div>
                <!-- Live Badges for Custom Numbers -->
                <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                    <template x-if="summary.custom_valid_count > 0">
                        <span class="px-2.5 py-1 rounded-lg bg-green-50 dark:bg-green-950/40 text-themeGreen dark:text-green-400 border border-green-200 dark:border-green-800">
                            <span class="font-black" x-text="summary.custom_valid_count"></span> Valid
                        </span>
                    </template>
                    <template x-if="summary.custom_invalid_count > 0">
                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                            <span class="font-black" x-text="summary.custom_invalid_count"></span> Invalid
                        </span>
                    </template>
                    <template x-if="summary.custom_duplicate_count > 0">
                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-themeDark text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                            <span class="font-black" x-text="summary.custom_duplicate_count"></span> Duplicates Removed
                        </span>
                    </template>
                </div>
            </div>

            <textarea x-model="filters.custom_numbers" @input="onCustomNumbersInput()"
                      rows="4"
                      class="w-full border-2 border-gray-100 dark:border-gray-800 rounded-2xl bg-white dark:bg-themeNavy focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-xs font-mono font-semibold text-gray-700 dark:text-gray-200 p-3.5 placeholder-gray-400"
                      placeholder="01712345678, 01812345678&#10;01912345678"></textarea>
            
            <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-[10px] text-gray-400 dark:text-gray-500">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Format: 01712345678, 01812345678 or +88017... separated by commas or line breaks. Numbers are deduplicated and validated on the server.</span>
                </div>
                <button type="button" @click="clearCustomNumbers()" x-show="filters.custom_numbers" class="text-red-500 hover:text-red-700 font-bold underline">
                    Clear Numbers
                </button>
            </div>
        </div>
        
        <!-- Recipient Filters Section (Active for 'students' or 'both') -->
        <div class="mb-8" x-show="filters.recipient_source !== 'custom'" x-cloak>
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-white/[0.06]">
                <h2 class="text-xs font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase flex items-center gap-2">
                    <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Recipient Filters (AND Logic Applied)</span>
                </h2>
                <button type="button" @click="resetFilters()" class="text-[11px] font-bold text-gray-400 hover:text-themeBlue transition-colors uppercase tracking-wider flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
            </div>

            <!-- Dynamic Filter Controls Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- 1. Academic Session Custom Dropdown (Always Visible) -->
                <div class="relative" :class="dropdownOpen === 'session' ? 'z-40' : 'z-10'" @click.away="if(dropdownOpen === 'session') dropdownOpen = null">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                        Academic Session <span class="text-red-500 ml-0.5">*</span>
                    </label>
                    <button @click="toggleDropdown('session')" @keydown.escape.window="dropdownOpen = null" type="button"
                            class="w-full h-11 flex items-center justify-between px-3 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="getSessionLabel()"></span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="dropdownOpen === 'session' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="dropdownOpen === 'session'" x-cloak x-transition
                         class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        @foreach($sessions as $session)
                            <button type="button" @click="onSessionChange('{{ $session->id }}'); dropdownOpen = null"
                                     :class="filters.session_year_id == '{{ $session->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                     class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span>{{ $session->session_name }}</span>
                                <template x-if="filters.session_year_id == '{{ $session->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Recipient Group Preset Custom Dropdown (Always Visible) -->
                <div class="relative" :class="dropdownOpen === 'group' ? 'z-40' : 'z-10'" @click.away="if(dropdownOpen === 'group') dropdownOpen = null">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                        Recipient Group
                    </label>
                    <button @click="toggleDropdown('group')" @keydown.escape.window="dropdownOpen = null" type="button"
                            class="w-full h-11 flex items-center justify-between px-3 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="getRecipientGroupLabel()"></span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="dropdownOpen === 'group' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="dropdownOpen === 'group'" x-cloak x-transition
                         class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="grp in recipientGroups" :key="grp.id">
                            <button type="button" @click="onRecipientGroupChange(grp.id); dropdownOpen = null"
                                     :class="filters.recipient_group === grp.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                     class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="grp.name"></span>
                                <template x-if="filters.recipient_group === grp.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 3. Class Filter Custom Dropdown (Always Accessible) -->
                <div class="relative" :class="dropdownOpen === 'class' ? 'z-40' : 'z-10'" @click.away="if(dropdownOpen === 'class') dropdownOpen = null">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                        Class
                    </label>
                    <button @click="toggleDropdown('class')" @keydown.escape.window="dropdownOpen = null" type="button"
                            class="w-full h-11 flex items-center justify-between px-3 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="getClassLabel()"></span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="dropdownOpen === 'class' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="dropdownOpen === 'class'" x-cloak x-transition
                         class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <button type="button" @click="onClassChange('all'); dropdownOpen = null"
                                :class="filters.class_id === 'all' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>All classes</span>
                            <template x-if="filters.class_id === 'all'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        @foreach($classes as $class)
                            <button type="button" @click="onClassChange('{{ $class->id }}'); dropdownOpen = null"
                                    :class="filters.class_id == '{{ $class->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                    class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span>{{ $class->class_name }}</span>
                                <template x-if="filters.class_id == '{{ $class->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 4. Section Filter Custom Dropdown (Always Accessible) -->
                <div class="relative" :class="dropdownOpen === 'section' ? 'z-40' : 'z-10'" @click.away="if(dropdownOpen === 'section') dropdownOpen = null">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                        Section
                    </label>
                    <button @click="toggleDropdown('section')" @keydown.escape.window="dropdownOpen = null" type="button"
                            class="w-full h-11 flex items-center justify-between px-3 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="getSectionLabel()"></span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="dropdownOpen === 'section' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="dropdownOpen === 'section'" x-cloak x-transition
                         class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <button type="button" @click="onSectionChange('all'); dropdownOpen = null"
                                :class="filters.section_id === 'all' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                            <span>All sections</span>
                            <template x-if="filters.section_id === 'all'">
                                <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </button>
                        <template x-for="sec in getAvailableSections()" :key="sec.id">
                            <button type="button" @click="onSectionChange(sec.id); dropdownOpen = null"
                                    :class="filters.section_id == sec.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                    class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="sec.name"></span>
                                <template x-if="filters.section_id == sec.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 5. Gender Filter Custom Dropdown (Always Accessible across All Groups) -->
                <div class="relative" :class="dropdownOpen === 'gender' ? 'z-40' : 'z-10'" @click.away="if(dropdownOpen === 'gender') dropdownOpen = null">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                        Gender
                    </label>
                    <button @click="toggleDropdown('gender')" @keydown.escape.window="dropdownOpen = null" type="button"
                            class="w-full h-11 flex items-center justify-between px-3 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="getGenderLabel()"></span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="dropdownOpen === 'gender' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="dropdownOpen === 'gender'" x-cloak x-transition
                         class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="g in genderOptions" :key="g.id">
                            <button type="button" @click="onGenderChange(g.id); dropdownOpen = null"
                                    :class="filters.gender === g.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                    class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="g.name"></span>
                                <template x-if="filters.gender === g.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 6. Recipient Selection Mode Custom Dropdown (Always Accessible) -->
                <div class="relative" :class="dropdownOpen === 'selection' ? 'z-40' : 'z-10'" @click.away="if(dropdownOpen === 'selection') dropdownOpen = null">
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                        Recipient Selection
                    </label>
                    <button @click="toggleDropdown('selection')" @keydown.escape.window="dropdownOpen = null" type="button"
                            class="w-full h-11 flex items-center justify-between px-3 text-sm font-semibold bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="getSelectionModeLabel()"></span>
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200" :class="dropdownOpen === 'selection' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="dropdownOpen === 'selection'" x-cloak x-transition
                         class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto">
                        <template x-for="sel in selectionModes" :key="sel.id">
                            <button type="button" @click="onSelectionModeSelect(sel.id); dropdownOpen = null"
                                    :class="filters.recipient_selection === sel.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200 font-semibold'"
                                    class="w-full flex items-center justify-between px-4 py-2.5 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors">
                                <span x-text="sel.name"></span>
                                <template x-if="filters.recipient_selection === sel.id">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>

            </div>

            <!-- Selective Students Status Banner -->
            <template x-if="filters.recipient_selection === 'selective'">
                <div class="mt-5 p-3.5 bg-indigo-50/70 dark:bg-themeBlue/10 border border-indigo-200 dark:border-themeBlue/20 rounded-2xl flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2 text-indigo-900 dark:text-indigo-200 font-bold">
                        <svg class="w-4 h-4 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>
                            Selective Mode Active: <span class="font-black text-themeBlue" x-text="filters.selected_student_ids.length"></span> student(s) explicitly selected.
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openPreviewModal()" class="px-3.5 py-1.5 bg-themeBlue text-white font-black rounded-lg text-[11px] uppercase tracking-wider hover:bg-themeBlue/90 transition-all flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Manage Selection
                        </button>
                        <button type="button" @click="clearSelectedStudents()" class="px-3.5 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold rounded-lg text-[11px] uppercase tracking-wider hover:bg-gray-300 transition-all">
                            Clear
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Recipient Summary Metrics Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-xs font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase">
                        Recipient Summary
                    </h2>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium">Counts update automatically when filters, custom numbers, or message content change.</p>
                </div>
                <div x-show="loadingSummary" class="flex items-center gap-2 text-xs font-bold text-themeBlue">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Calculating...</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Box 1: Matching Recipients / Students -->
                <div class="bg-gray-50/70 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] rounded-2xl p-5 transition-all">
                    <div class="flex items-center justify-between text-gray-400 mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest"
                              x-text="filters.recipient_source === 'custom' ? 'Custom Contacts' : (filters.recipient_source === 'both' ? 'Total Recipients' : 'Matching Students')">
                            Matching Students
                        </span>
                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white">
                        <template x-if="filters.recipient_source === 'custom'">
                            <span x-text="summary.custom_tokens_count">0</span>
                        </template>
                        <template x-if="filters.recipient_source === 'students'">
                            <span x-text="summary.total_students">0</span>
                        </template>
                        <template x-if="filters.recipient_source === 'both'">
                            <span x-text="summary.total_students + summary.custom_tokens_count">0</span>
                        </template>
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">
                        <template x-if="filters.recipient_source === 'custom'">
                            <span>Manually entered numbers</span>
                        </template>
                        <template x-if="filters.recipient_source === 'students'">
                            <span>Active enrolled students</span>
                        </template>
                        <template x-if="filters.recipient_source === 'both'">
                            <span><span x-text="summary.total_students"></span> students + <span x-text="summary.custom_tokens_count"></span> custom</span>
                        </template>
                    </div>
                </div>

                <!-- Box 2: Valid Numbers -->
                <div class="bg-gray-50/70 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] rounded-2xl p-5 transition-all">
                    <div class="flex items-center justify-between text-gray-400 mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest">Valid Numbers</span>
                        <svg class="w-4 h-4 text-themeGreen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div class="text-2xl font-black text-themeGreen dark:text-green-400 flex items-baseline gap-2">
                        <span x-text="summary.unique_numbers_count">0</span>
                        <template x-if="summary.duplicate_phone_count > 0">
                            <span class="text-[10px] font-bold text-gray-400 font-mono" :title="summary.duplicate_phone_count + ' duplicate numbers merged to single SMS'">
                                (<span x-text="summary.valid_numbers_count"></span> total)
                            </span>
                        </template>
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">
                        <span x-text="summary.unique_numbers_count">0</span> unique numbers to bill
                    </div>
                </div>

                <!-- Box 3: Invalid / Missing -->
                <div class="bg-gray-50/70 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] rounded-2xl p-5 transition-all">
                    <div class="flex items-center justify-between text-gray-400 mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest">Missing / Invalid</span>
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-2xl font-black" :class="summary.missing_invalid_count > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-700 dark:text-gray-300'" x-text="summary.missing_invalid_count">
                        0
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-1">
                        Skipped safely before dispatch
                    </div>
                </div>

                <!-- Box 4: SMS Estimate -->
                <div class="bg-gray-50/70 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] rounded-2xl p-5 transition-all">
                    <div class="flex items-center justify-between text-gray-400 mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest">SMS Estimate</span>
                        <svg class="w-4 h-4 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                        </svg>
                    </div>
                    <div class="text-2xl font-black text-themeBlue dark:text-blue-400">
                        <span x-text="summary.unique_numbers_count * summary.sms_segments">0</span>
                        <span class="text-xs font-bold text-gray-400 font-normal">segments</span>
                    </div>
                    <div class="text-[11px] font-bold text-themeGreen dark:text-green-400 mt-1">
                        Est. Cost: ৳<span x-text="summary.estimated_cost">0.00</span>
                    </div>
                </div>

            </div>
            
            <p class="text-[10px] text-gray-400 dark:text-gray-500 italic mt-2.5">
                Calculations strictly reflect unique deliverable numbers and message segments.
            </p>
        </div>

        <!-- Message Content Section -->
        <div class="mb-8">
            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-2 block">
                Message Content <span class="text-red-500 ml-0.5">*</span>
            </label>
            <textarea x-model="message" @input="onMessageInput()"
                      rows="5"
                      class="w-full border-2 border-gray-100 dark:border-gray-800 rounded-2xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 p-4 placeholder-gray-400"
                      placeholder="Type your notice here..."></textarea>
            
            <!-- MIM SMS Gateway Character Counter & Encoding Guide -->
            <div class="mt-4 p-4 bg-gray-50/70 dark:bg-themeDark/60 border border-gray-150 dark:border-white/[0.08] rounded-2xl text-xs">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 pb-3 border-b border-gray-200/60 dark:border-white/[0.06]">
                    <!-- Active Encoding Pill -->
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Encoding Mode:</span>
                        <span class="px-2.5 py-1 rounded-lg font-black text-[10px] uppercase tracking-wider flex items-center gap-1.5"
                              :class="summary.encoding === 'Unicode' ? 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700' : 'bg-blue-50 dark:bg-themeBlue/10 text-themeBlue dark:text-blue-400 border border-themeBlue/30'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="summary.encoding === 'Unicode' ? 'bg-amber-500 animate-pulse' : 'bg-themeBlue'"></span>
                            <span x-text="summary.encoding === 'Unicode' ? 'Unicode (Bengali / UTF-8)' : 'GSM (Standard English)'"></span>
                        </span>
                    </div>

                    <!-- Characters & Segment Metrics -->
                    <div class="flex items-center gap-4 text-xs font-bold text-gray-600 dark:text-gray-300">
                        <div>Characters: <span class="font-black text-themeBlue text-sm" x-text="summary.characters">0</span></div>
                        <div>SMS Segments: <span class="font-black text-themeGreen dark:text-green-400 text-sm" x-text="summary.sms_segments">0</span></div>
                    </div>
                </div>

                <!-- Concatenation Rules Breakdown (MIM Gateway Guideline) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-3 text-[11px]">
                    <div class="flex items-start gap-2" :class="summary.encoding === 'GSM' ? 'text-gray-800 dark:text-gray-200 font-bold' : 'text-gray-400 dark:text-gray-500'">
                        <span class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="summary.encoding === 'GSM' ? 'bg-themeBlue' : 'bg-gray-300 dark:bg-gray-700'"></span>
                        <span><strong>GSM Encoding:</strong> 160 characters for 1st SMS, and 153 characters per segment for multipart concatenated SMS.</span>
                    </div>
                    <div class="flex items-start gap-2" :class="summary.encoding === 'Unicode' ? 'text-amber-700 dark:text-amber-300 font-bold' : 'text-gray-400 dark:text-gray-500'">
                        <span class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="summary.encoding === 'Unicode' ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-700'"></span>
                        <span><strong>Unicode Encoding:</strong> 70 characters for 1st SMS, and 67 characters per segment for multipart concatenated SMS.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Delivery Options (Send Now vs Schedule for Later) -->
        <div class="mb-8 p-5 bg-gray-50/70 dark:bg-themeDark/60 border border-gray-150 dark:border-white/[0.08] rounded-2xl">
            <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-3 block">
                SMS Delivery Options <span class="text-red-500 ml-0.5">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <!-- Option 1: Send Now -->
                <label class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3"
                       :class="delivery_mode === 'now' ? 'border-themeBlue bg-blue-50/50 dark:bg-themeBlue/10 text-themeBlue dark:text-blue-400 shadow-sm' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-300 hover:border-gray-300'">
                    <input type="radio" name="delivery_mode" value="now" x-model="delivery_mode" class="mt-1 text-themeBlue focus:ring-themeBlue">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                            <span>Send Now</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] bg-themeGreen/10 text-themeGreen font-bold uppercase">Immediate</span>
                        </div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                            Dispatch broadcast immediately upon confirmation
                        </div>
                    </div>
                </label>

                <!-- Option 2: Schedule for Later -->
                <label class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3"
                       :class="delivery_mode === 'schedule' ? 'border-themeBlue bg-blue-50/50 dark:bg-themeBlue/10 text-themeBlue dark:text-blue-400 shadow-sm' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-300 hover:border-gray-300'">
                    <input type="radio" name="delivery_mode" value="schedule" x-model="delivery_mode" class="mt-1 text-themeBlue focus:ring-themeBlue">
                    <div>
                        <div class="text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                            <span>Schedule for Later</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold uppercase">Automated</span>
                        </div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                            Specify target date & time in Bangladesh Standard Time (Asia/Dhaka)
                        </div>
                    </div>
                </label>
            </div>

            <!-- Scheduled Date & Time Controls -->
            <div x-show="delivery_mode === 'schedule'" x-transition class="pt-4 border-t border-gray-200/60 dark:border-white/[0.06] space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Scheduled Date -->
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Scheduled Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" x-model="scheduled_date" :min="todayDhakaDate"
                               class="w-full h-11 px-3.5 border-2 border-gray-150 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    </div>

                    <!-- Scheduled Time -->
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1.5 block">
                            Scheduled Time (Bangladesh Time) <span class="text-red-500">*</span>
                        </label>
                        <input type="time" x-model="scheduled_time"
                               class="w-full h-11 px-3.5 border-2 border-gray-150 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    </div>
                </div>

                <!-- Summary / Timezone Confirmation Card -->
                <div class="p-3 bg-blue-50/60 dark:bg-themeNavy/60 border border-themeBlue/20 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-gray-600 dark:text-gray-300 font-medium">
                            Timezone: <strong class="text-gray-900 dark:text-white font-black">Asia/Dhaka (GMT+6)</strong>
                        </span>
                    </div>
                    <div class="text-left sm:text-right">
                        <template x-if="scheduled_date && scheduled_time">
                            <span class="font-black text-themeBlue dark:text-blue-400">
                                Proposed Dispatch: <span x-text="formatScheduledPreview()"></span>
                            </span>
                        </template>
                        <template x-if="!scheduled_date || !scheduled_time">
                            <span class="text-gray-400 italic">Please select both date and time</span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons Footer -->
        <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-6 border-t border-gray-100 dark:border-white/[0.06]">
            
            <!-- Preview Recipients Button -->
            <button type="button" @click="openPreviewModal()"
                    class="w-full sm:w-auto h-11 px-6 border-2 border-themeBlue text-themeBlue dark:text-blue-400 hover:bg-themeBlue/10 font-black rounded-xl transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Preview Recipients
            </button>

            <!-- Review & Send Button -->
            <button type="button" @click="openReviewModal()"
                    class="w-full sm:w-auto h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
                <span x-text="delivery_mode === 'now' ? 'Review & Send' : 'Review & Schedule'"></span>
            </button>
        </div>

    </div>
    </div>

    <!-- Tab 2: Scheduled Broadcasts Management View -->
    <div x-show="activeMainTab === 'schedules'" x-cloak
         class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-8 shadow-sm hover:shadow-md transition-all duration-300">
        
        <!-- Controls Header: Search & Status Filters -->
        <div class="mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <!-- Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
                <button type="button" @click="filterSchedulesByStatus('all')"
                        :class="scheduleFilterStatus === 'all' ? 'bg-themeBlue text-white shadow-sm font-black' : 'bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-400 font-bold hover:bg-gray-200 dark:hover:bg-themeNavy'"
                        class="px-3.5 py-1.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 shrink-0">
                    <span>All</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10 dark:bg-white/10" x-text="scheduleCounts.all || 0"></span>
                </button>
                <button type="button" @click="filterSchedulesByStatus('pending')"
                        :class="scheduleFilterStatus === 'pending' ? 'bg-amber-500 text-white shadow-sm font-black' : 'bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-400 font-bold hover:bg-gray-200 dark:hover:bg-themeNavy'"
                        class="px-3.5 py-1.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 shrink-0">
                    <span>Pending</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10 dark:bg-white/10" x-text="scheduleCounts.pending || 0"></span>
                </button>
                <button type="button" @click="filterSchedulesByStatus('completed')"
                        :class="scheduleFilterStatus === 'completed' ? 'bg-themeGreen text-white shadow-sm font-black' : 'bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-400 font-bold hover:bg-gray-200 dark:hover:bg-themeNavy'"
                        class="px-3.5 py-1.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 shrink-0">
                    <span>Completed</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10 dark:bg-white/10" x-text="scheduleCounts.completed || 0"></span>
                </button>
                <button type="button" @click="filterSchedulesByStatus('partial_failure')"
                        :class="scheduleFilterStatus === 'partial_failure' ? 'bg-orange-500 text-white shadow-sm font-black' : 'bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-400 font-bold hover:bg-gray-200 dark:hover:bg-themeNavy'"
                        class="px-3.5 py-1.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 shrink-0">
                    <span>Partial</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10 dark:bg-white/10" x-text="scheduleCounts.partial_failure || 0"></span>
                </button>
                <button type="button" @click="filterSchedulesByStatus('failed')"
                        :class="scheduleFilterStatus === 'failed' ? 'bg-red-500 text-white shadow-sm font-black' : 'bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-400 font-bold hover:bg-gray-200 dark:hover:bg-themeNavy'"
                        class="px-3.5 py-1.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 shrink-0">
                    <span>Failed</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10 dark:bg-white/10" x-text="scheduleCounts.failed || 0"></span>
                </button>
                <button type="button" @click="filterSchedulesByStatus('cancelled')"
                        :class="scheduleFilterStatus === 'cancelled' ? 'bg-gray-500 text-white shadow-sm font-black' : 'bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-400 font-bold hover:bg-gray-200 dark:hover:bg-themeNavy'"
                        class="px-3.5 py-1.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 shrink-0">
                    <span>Cancelled</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-black/10 dark:bg-white/10" x-text="scheduleCounts.cancelled || 0"></span>
                </button>
            </div>

            <!-- Search Bar & Refresh -->
            <div class="flex items-center gap-2">
                <div class="relative w-full md:w-64">
                    <input type="text" x-model="scheduleSearch" @input.debounce.350ms="fetchSchedules(1)"
                           placeholder="Search schedules..."
                           class="w-full h-10 pl-9 pr-3 text-xs font-semibold rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-gray-700 dark:text-gray-200">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="button" @click="fetchSchedules(1)" title="Refresh"
                        class="w-10 h-10 rounded-xl border border-gray-200 dark:border-gray-800 flex items-center justify-center text-gray-500 hover:text-themeBlue hover:border-themeBlue transition-all">
                    <svg class="w-4 h-4" :class="loadingSchedules ? 'animate-spin text-themeBlue' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>

        <!-- Schedules Table -->
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Schedule Details</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Recipients</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Message</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Scheduled At (Dhaka)</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Cost</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loading Skeleton Rows -->
                    <template x-if="loadingSchedules">
                        <template x-for="i in [1, 2, 3, 4]" :key="i">
                            <tr class="animate-pulse border-b border-gray-100 dark:border-white/[0.04]">
                                <td class="py-3 px-3"><div class="h-4 w-32 bg-gray-200 dark:bg-gray-700/60 rounded-md mb-1"></div><div class="h-3 w-20 bg-gray-100 dark:bg-gray-800 rounded"></div></td>
                                <td class="py-3 px-3"><div class="h-4 w-20 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="py-3 px-3"><div class="h-4 w-48 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="py-3 px-3"><div class="h-4 w-28 bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="py-3 px-3 text-center"><div class="h-5 w-16 mx-auto bg-gray-200 dark:bg-gray-700/60 rounded-full"></div></td>
                                <td class="py-3 px-3 text-right"><div class="h-4 w-14 ml-auto bg-gray-200 dark:bg-gray-700/60 rounded-md"></div></td>
                                <td class="py-3 px-3 text-right"><div class="h-7 w-20 ml-auto bg-gray-200 dark:bg-gray-700/60 rounded-lg"></div></td>
                            </tr>
                        </template>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!loadingSchedules && schedulesList.length === 0">
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-400 dark:text-gray-500 text-xs font-bold">
                                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                No scheduled SMS broadcasts match the current filters.
                            </td>
                        </tr>
                    </template>

                    <!-- Data Rows -->
                    <template x-if="!loadingSchedules">
                        <template x-for="item in schedulesList" :key="item.id">
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100/50 dark:border-white/[0.04]">
                                <!-- Title & UUID -->
                                <td class="py-3 px-3">
                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="item.title"></div>
                                    <div class="font-mono text-[10px] text-gray-400 dark:text-gray-550" x-text="item.schedule_uuid"></div>
                                </td>

                                <!-- Recipients & Source -->
                                <td class="py-3 px-3">
                                    <span class="font-mono font-black text-sm text-themeBlue dark:text-blue-400" x-text="item.total_recipients"></span>
                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">recipients</span>
                                    <div class="text-[10px] font-black uppercase tracking-wider text-gray-400 mt-0.5" x-text="item.recipient_source"></div>
                                </td>

                                <!-- Message Excerpt -->
                                <td class="py-3 px-3 max-w-xs">
                                    <div class="text-xs text-gray-700 dark:text-gray-300 font-medium truncate" x-text="item.message"></div>
                                    <div class="text-[10px] text-gray-400" x-text="item.total_segments + ' seg (' + (item.encoding || 'GSM') + ')'"></div>
                                </td>

                                <!-- Scheduled At -->
                                <td class="py-3 px-3">
                                    <div class="text-xs font-bold text-gray-800 dark:text-gray-200" x-text="formatDateTime(item.scheduled_at)"></div>
                                    <div class="text-[10px] text-gray-400">Asia/Dhaka</div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                          :class="getStatusBadgeClass(item.status)">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(item.status)"></span>
                                        <span x-text="formatStatusLabel(item.status)"></span>
                                    </span>
                                </td>

                                <!-- Cost -->
                                <td class="py-3 px-3 text-right">
                                    <div class="text-sm font-black text-themeGreen dark:text-green-400">
                                        ৳<span x-text="Number(item.actual_cost || item.estimated_cost).toFixed(2)"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-400" x-text="item.actual_cost ? 'Actual' : 'Estimated'"></div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- View Details Button -->
                                        <button type="button" @click="openScheduleDetailsModal(item)" title="View Details"
                                                class="action-btn p-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-themeBlue hover:text-white hover:bg-themeBlue hover:border-themeBlue transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Edit Button (Only Pending) -->
                                        <template x-if="item.status === 'pending'">
                                            <button type="button" @click="openScheduleEditModal(item)" title="Edit Schedule"
                                                    class="action-btn p-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-themeBlue hover:text-white hover:bg-themeBlue hover:border-themeBlue transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                        </template>

                                        <!-- Cancel Button (Only Pending) -->
                                        <template x-if="item.status === 'pending'">
                                            <button type="button" @click="cancelScheduleAction(item)" title="Cancel Schedule"
                                                    class="action-btn p-1.5 rounded-lg border border-red-200 dark:border-red-900 text-red-600 hover:text-white hover:bg-red-600 hover:border-red-600 transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <template x-if="schedulePagination.total > schedulePagination.per_page">
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/[0.06] flex items-center justify-between text-xs">
                <span class="text-gray-500">
                    Showing <span class="font-bold text-gray-800 dark:text-gray-200" x-text="((schedulePagination.current_page - 1) * schedulePagination.per_page) + 1"></span>
                    to <span class="font-bold text-gray-800 dark:text-gray-200" x-text="Math.min(schedulePagination.current_page * schedulePagination.per_page, schedulePagination.total)"></span>
                    of <span class="font-bold text-gray-800 dark:text-gray-200" x-text="schedulePagination.total"></span> schedules
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="fetchSchedules(schedulePagination.current_page - 1)"
                            :disabled="schedulePagination.current_page <= 1"
                            class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-themeDark font-bold text-gray-700 dark:text-gray-300">
                        Previous
                    </button>
                    <span class="px-2 font-bold text-themeBlue" x-text="schedulePagination.current_page + ' / ' + schedulePagination.last_page"></span>
                    <button type="button" @click="fetchSchedules(schedulePagination.current_page + 1)"
                            :disabled="schedulePagination.current_page >= schedulePagination.last_page"
                            class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-themeDark font-bold text-gray-700 dark:text-gray-300">
                        Next
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 1: RECIPIENT PREVIEW & SELECTIVE STUDENT MODAL    -->
    <!-- ======================================================== -->
    <div x-show="previewModalOpen" x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        
        <!-- Backdrop Blur Overlay -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
             @click="previewModalOpen = false"></div>

        <!-- Modal Card Container -->
        <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden z-10">
            
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                        <svg class="w-6 h-6 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Recipient Preview & Selection
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Inspect recipient student data and masked guardian numbers. Check boxes to choose specific students.
                    </p>
                </div>
                <button type="button" @click="previewModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Search & Filter Bar -->
            <div class="p-6 pb-4 bg-gray-50/50 dark:bg-themeDark/40 border-b border-gray-100 dark:border-white/[0.06] flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <!-- Search Input -->
                <div class="relative w-full sm:w-80">
                    <input type="text" x-model="previewSearch" @input.debounce.400ms="fetchPreview(1)"
                           placeholder="Search student name, roll, ID, phone..."
                           class="w-full h-10 pl-9 pr-4 text-xs font-semibold rounded-xl border-2 border-gray-200 dark:border-gray-800 bg-white dark:bg-themeNavy focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-gray-700 dark:text-gray-200">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>

                <!-- Selective Mode Actions -->
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button type="button" @click="selectAllOnPage()"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-themeDark hover:bg-gray-200 text-gray-700 dark:text-gray-200 font-bold rounded-lg text-xs transition-colors">
                        Select Visible Page
                    </button>
                    <button type="button" @click="clearSelectedStudents()"
                            class="px-3 py-1.5 bg-gray-100 dark:bg-themeDark hover:bg-gray-200 text-gray-700 dark:text-gray-200 font-bold rounded-lg text-xs transition-colors">
                        Clear Selection
                    </button>
                </div>
            </div>

            <!-- Table Container (Borderless Standard Table) -->
            <div class="flex-1 overflow-y-auto p-6 pt-2">
                
                <!-- Loading State Skeleton -->
                <div x-show="loadingPreview" class="py-8 space-y-3">
                    <template x-for="i in 5" :key="i">
                        <div class="h-12 bg-gray-100 dark:bg-gray-800/60 rounded-xl animate-pulse"></div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="!loadingPreview && previewStudents.length === 0" class="py-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">No active students match the selected filter criteria.</p>
                </div>

                <!-- Student List Table -->
                <div x-show="!loadingPreview && previewStudents.length > 0" class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                    <table class="w-full text-left border-collapse table">
                        <thead>
                            <tr class="!bg-transparent">
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-2 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-10 text-center">
                                    <template x-if="filters.recipient_source !== 'custom'">
                                        <input type="checkbox" :checked="isAllPageSelected()" @change="togglePageSelection($event.target.checked)"
                                               class="rounded border-gray-300 text-themeBlue focus:ring-themeBlue cursor-pointer">
                                    </template>
                                    <template x-if="filters.recipient_source === 'custom'">
                                        <span class="text-[10px] font-mono text-gray-400">#</span>
                                    </template>
                                </th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">
                                    Roll / ID
                                </th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">
                                    Recipient Name
                                </th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">
                                    Class & Section
                                </th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">
                                    Gender
                                </th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">
                                    Recipient Mobile
                                </th>
                                <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-3 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="st in previewStudents" :key="st.id">
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100/50 dark:border-white/[0.04]">
                                    
                                    <!-- Selection Checkbox / Type Badge -->
                                    <td class="py-2.5 px-2 text-center">
                                        <template x-if="!st.is_custom">
                                            <input type="checkbox" :checked="isStudentSelected(st.id)" @change="toggleStudentSelection(st.id)"
                                                   class="rounded border-gray-300 text-themeBlue focus:ring-themeBlue cursor-pointer">
                                        </template>
                                        <template x-if="st.is_custom">
                                            <span class="text-[9px] font-black uppercase tracking-wider text-themeBlue bg-themeBlue/10 px-1.5 py-0.5 rounded">Custom</span>
                                        </template>
                                    </td>

                                    <!-- Roll / ID -->
                                    <td class="py-2.5 px-3">
                                        <div class="font-mono font-black text-sm text-gray-700 dark:text-gray-300" x-text="st.roll_number"></div>
                                        <div class="text-[10px] text-gray-400 font-mono" x-text="st.student_identity"></div>
                                    </td>

                                    <!-- Student Name -->
                                    <td class="py-2.5 px-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="st.student_name"></span>
                                            <template x-if="st.is_custom">
                                                <span class="px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider rounded bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800">Direct Entry</span>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Class & Section -->
                                    <td class="py-2.5 px-3">
                                        <div class="text-xs font-bold text-gray-700 dark:text-gray-300" x-text="st.class_name"></div>
                                        <div class="text-[10px] font-medium text-gray-400" x-text="st.section_name"></div>
                                    </td>

                                    <!-- Gender -->
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider"
                                              :class="st.gender === 'Male' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' : (st.gender === 'Female' ? 'bg-pink-50 text-pink-700 dark:bg-pink-950/40 dark:text-pink-300' : 'bg-gray-100 text-gray-600 dark:bg-themeDark dark:text-gray-300')"
                                              x-text="st.gender"></span>
                                    </td>

                                    <!-- Masked Mobile -->
                                    <td class="py-2.5 px-3">
                                        <span class="font-mono text-xs font-bold text-gray-800 dark:text-gray-200" x-text="st.masked_phone"></span>
                                    </td>

                                    <!-- Phone Validity Status Badge -->
                                    <td class="py-2.5 px-3 text-center">
                                        <template x-if="st.phone_status === 'valid'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-green-50 dark:bg-green-950/40 text-themeGreen dark:text-green-400 uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 rounded-full bg-themeGreen"></span>
                                                Valid
                                            </span>
                                        </template>
                                        <template x-if="st.phone_status === 'invalid'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Invalid
                                            </span>
                                        </template>
                                        <template x-if="st.phone_status === 'missing'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Missing
                                            </span>
                                        </template>
                                    </td>

                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Modal Pagination & Footer -->
            <div class="p-6 border-t border-gray-100 dark:border-white/[0.06] bg-gray-50/50 dark:bg-themeDark/40 flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <!-- Pagination Stats -->
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                    Showing Page <span class="font-bold text-gray-800 dark:text-gray-200" x-text="previewPagination.current_page"></span> of 
                    <span class="font-bold text-gray-800 dark:text-gray-200" x-text="previewPagination.last_page"></span> 
                    (<span class="font-bold" x-text="previewPagination.total"></span> matching students)
                </div>

                <!-- Page Navigation Buttons -->
                <div class="flex items-center gap-2">
                    <button type="button" @click="fetchPreview(previewPagination.current_page - 1)"
                            :disabled="previewPagination.current_page <= 1"
                            class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-xs font-bold text-gray-700 dark:text-gray-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                        Previous
                    </button>
                    <button type="button" @click="fetchPreview(previewPagination.current_page + 1)"
                            :disabled="previewPagination.current_page >= previewPagination.last_page"
                            class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-800 text-xs font-bold text-gray-700 dark:text-gray-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                        Next
                    </button>
                    <button type="button" @click="previewModalOpen = false"
                            class="ml-2 px-5 py-1.5 bg-themeBlue text-white font-black rounded-lg text-xs uppercase tracking-wider hover:bg-themeBlue/90 transition-all">
                        Apply & Close
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 2: PRE-FLIGHT REVIEW & SEND CONFIRMATION MODAL    -->
    <!-- ======================================================== -->
    <div x-show="reviewModalOpen" x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        
        <!-- Backdrop Blur Overlay -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity"
             @click="if(!sendingBroadcast) reviewModalOpen = false"></div>

        <!-- Confirmation Card -->
        <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl w-full max-w-2xl flex flex-col overflow-hidden z-10">
            
            <!-- Header -->
            <div class="p-6 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-themeBlue to-themeGreen flex items-center justify-center text-white shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight" x-text="delivery_mode === 'now' ? 'Review Broadcast Details' : 'Review Scheduled Broadcast'">Review Broadcast Details</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="delivery_mode === 'now' ? 'Pre-flight verification summary before SMS dispatch.' : 'Pre-flight verification before scheduling automated SMS broadcast.'">Pre-flight verification summary before SMS dispatch.</p>
                    </div>
                </div>
                <button type="button" @click="if(!sendingBroadcast && !savingSchedule) reviewModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Content & Verification Breakdown -->
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                
                <!-- Breakdown Table -->
                <div class="bg-gray-50/70 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] rounded-2xl p-4 divide-y divide-gray-100 dark:divide-white/[0.06] text-xs">
                    
                    <div class="py-2 flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Delivery Mode</span>
                        <span class="font-black" :class="delivery_mode === 'now' ? 'text-themeBlue' : 'text-amber-600 dark:text-amber-400'"
                              x-text="delivery_mode === 'now' ? 'Send Now (Immediate)' : 'Schedule for Later'"></span>
                    </div>

                    <template x-if="delivery_mode === 'schedule'">
                        <div class="py-2 flex justify-between items-center bg-blue-50/40 dark:bg-themeBlue/5 px-2 rounded-lg">
                            <span class="text-gray-600 dark:text-gray-300 font-medium">Scheduled Time (Asia/Dhaka)</span>
                            <span class="font-black text-themeBlue dark:text-blue-400" x-text="formatScheduledPreview()"></span>
                        </div>
                    </template>

                    <div class="py-2 flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Recipient Source</span>
                        <span class="font-black text-themeBlue" x-text="getRecipientSourceLabel()"></span>
                    </div>

                    <template x-if="filters.recipient_source !== 'custom'">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Academic Session</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200" x-text="getSessionLabel()"></span>
                        </div>
                    </template>

                    <template x-if="filters.recipient_source !== 'custom'">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Recipient Mode</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200" x-text="getRecipientGroupLabel()"></span>
                        </div>
                    </template>

                    <template x-if="filters.recipient_source !== 'custom'">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Applied Filters</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200 text-right" x-text="getFilterSummaryText()"></span>
                        </div>
                    </template>

                    <template x-if="filters.recipient_source !== 'custom'">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Matching Students</span>
                            <span class="font-black text-gray-900 dark:text-white" x-text="summary.total_students"></span>
                        </div>
                    </template>

                    <template x-if="filters.recipient_source !== 'students'">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Custom Numbers Entered</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">
                                <span class="font-black text-themeGreen dark:text-green-400" x-text="summary.custom_valid_count"></span> valid (<span x-text="summary.custom_tokens_count"></span> total)
                            </span>
                        </div>
                    </template>

                    <div class="py-2 flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Unique Deliverable Numbers</span>
                        <span class="font-black text-themeGreen dark:text-green-400 text-sm" x-text="summary.unique_numbers_count"></span>
                    </div>

                    <template x-if="summary.duplicate_phone_count > 0">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Duplicates Avoided (Deduplicated)</span>
                            <span class="font-bold text-gray-600 dark:text-gray-400" x-text="summary.duplicate_phone_count + ' avoided (merged to 1 SMS)'"></span>
                        </div>
                    </template>

                    <template x-if="summary.missing_invalid_count > 0">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-amber-600 dark:text-amber-400 font-medium">Excluded Missing/Invalid</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400" x-text="summary.missing_invalid_count + ' contacts skipped'"></span>
                        </div>
                    </template>

                    <div class="py-2 flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Message Segments</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200" x-text="summary.sms_segments + ' (' + summary.encoding + ', ' + summary.characters + ' chars)'"></span>
                    </div>

                    <div class="py-2 flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Estimated Broadcast Cost</span>
                        <span class="font-black text-themeGreen dark:text-green-400 text-sm">৳<span x-text="summary.estimated_cost"></span></span>
                    </div>

                    <template x-if="currentBalance !== null">
                        <div class="py-2 flex justify-between items-center">
                            <span class="text-gray-500 font-medium">Current SMS Balance</span>
                            <span class="font-black text-themeBlue dark:text-blue-400">৳<span x-text="Number(currentBalance).toFixed(2)"></span></span>
                        </div>
                    </template>
                </div>

                <!-- Insufficient Balance Warning -->
                <template x-if="currentBalance !== null && currentBalance < summary.estimated_cost">
                    <div class="p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl text-xs text-red-700 dark:text-red-400 font-bold flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Insufficient balance to cover this broadcast. Please recharge your SMS balance before dispatching.</span>
                    </div>
                </template>

                <!-- Message Preview Box -->
                <div class="bg-gray-50/50 dark:bg-themeDark/40 border border-gray-100 dark:border-white/[0.06] rounded-xl p-3.5">
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Message Preview</div>
                    <div class="text-xs text-gray-700 dark:text-gray-200 font-medium whitespace-pre-wrap max-h-24 overflow-y-auto" x-text="message"></div>
                </div>

                <!-- Explicit Confirmation Checkbox -->
                <label class="flex items-start gap-3 p-3 bg-gray-50/50 dark:bg-themeDark/40 border border-gray-200 dark:border-gray-800 rounded-2xl cursor-pointer">
                    <input type="checkbox" x-model="confirmedCheck" class="mt-0.5 rounded border-gray-300 text-themeBlue focus:ring-themeBlue cursor-pointer">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        <span x-text="delivery_mode === 'now' ? 'I confirm that the recipient selections, contact count, and message text have been verified, and I authorize sending this broadcast to' : 'I confirm that the recipient selections, scheduled time, and message text have been verified, and I authorize scheduling this broadcast for'"></span>
                        <span class="font-black text-themeGreen dark:text-green-400" x-text="summary.unique_numbers_count"></span> unique numbers.
                    </span>
                </label>

            </div>

            <!-- Modal Footer -->
            <div class="p-6 border-t border-gray-100 dark:border-white/[0.06] bg-gray-50/50 dark:bg-themeDark/40 flex items-center justify-end gap-3">
                <button type="button" @click="reviewModalOpen = false" :disabled="sendingBroadcast || savingSchedule"
                        class="px-5 h-11 rounded-xl border-2 border-gray-200 dark:border-gray-800 text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                    Cancel & Edit
                </button>

                <button type="button" @click="delivery_mode === 'now' ? dispatchBroadcast() : saveSchedule()"
                        :disabled="!confirmedCheck || sendingBroadcast || savingSchedule || (currentBalance !== null && currentBalance < summary.estimated_cost) || summary.unique_numbers_count === 0"
                        class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-40 disabled:hover:translate-y-0 disabled:cursor-not-allowed transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                    <template x-if="sendingBroadcast || savingSchedule">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </template>
                    <span x-text="delivery_mode === 'now' ? (sendingBroadcast ? 'Sending Broadcast...' : 'Confirm & Dispatch SMS') : (savingSchedule ? 'Saving Schedule...' : 'Confirm & Save Schedule')"></span>
                </button>
            </div>

        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 3: SCHEDULE DETAILS MODAL                         -->
    <!-- ======================================================== -->
    <div x-show="scheduleDetailsModalOpen" x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity" @click="scheduleDetailsModalOpen = false"></div>
        <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden z-10"
             x-show="activeScheduleDetails">
            <div class="p-6 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight" x-text="activeScheduleDetails?.title"></h3>
                    <div class="font-mono text-[10px] text-gray-400" x-text="activeScheduleDetails?.schedule_uuid"></div>
                </div>
                <button type="button" @click="scheduleDetailsModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4 overflow-y-auto text-xs">
                <div class="bg-gray-50/70 dark:bg-themeDark/60 border border-gray-100 dark:border-white/[0.06] rounded-2xl p-4 divide-y divide-gray-100 dark:divide-white/[0.06]">
                    <div class="py-2 flex justify-between">
                        <span class="text-gray-500 font-medium">Status</span>
                        <span class="font-black uppercase" :class="getStatusBadgeClass(activeScheduleDetails?.status)" x-text="formatStatusLabel(activeScheduleDetails?.status)"></span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-gray-500 font-medium">Scheduled For</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200" x-text="formatDateTime(activeScheduleDetails?.scheduled_at) + ' (Asia/Dhaka)'"></span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-gray-500 font-medium">Recipient Source</span>
                        <span class="font-black text-themeBlue uppercase" x-text="activeScheduleDetails?.recipient_source"></span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-gray-500 font-medium">Total Unique Recipients</span>
                        <span class="font-black text-themeGreen dark:text-green-400" x-text="activeScheduleDetails?.total_recipients"></span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-gray-500 font-medium">Segments & Encoding</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200" x-text="(activeScheduleDetails?.total_segments || 1) + ' seg (' + (activeScheduleDetails?.encoding || 'GSM') + ')'"></span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-gray-500 font-medium">Estimated Cost</span>
                        <span class="font-black text-themeGreen dark:text-green-400">৳<span x-text="Number(activeScheduleDetails?.estimated_cost || 0).toFixed(2)"></span></span>
                    </div>
                    <template x-if="activeScheduleDetails?.actual_cost">
                        <div class="py-2 flex justify-between">
                            <span class="text-gray-500 font-medium">Actual Cost Incurred</span>
                            <span class="font-black text-themeGreen dark:text-green-400">৳<span x-text="Number(activeScheduleDetails?.actual_cost).toFixed(2)"></span></span>
                        </div>
                    </template>
                    <template x-if="activeScheduleDetails?.dispatched_at">
                        <div class="py-2 flex justify-between">
                            <span class="text-gray-500 font-medium">Dispatched At</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200" x-text="formatDateTime(activeScheduleDetails?.dispatched_at)"></span>
                        </div>
                    </template>
                    <template x-if="activeScheduleDetails?.sent_count !== undefined">
                        <div class="py-2 flex justify-between">
                            <span class="text-gray-500 font-medium">Delivery Results</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">
                                Sent: <span class="text-themeGreen font-black" x-text="activeScheduleDetails?.sent_count || 0"></span> |
                                Failed: <span class="text-red-500 font-black" x-text="activeScheduleDetails?.failed_count || 0"></span>
                            </span>
                        </div>
                    </template>
                </div>
                <div>
                    <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Message Content</div>
                    <div class="p-3 bg-gray-50 dark:bg-themeDark rounded-xl border border-gray-150 dark:border-white/[0.08] text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-medium"
                         x-text="activeScheduleDetails?.message"></div>
                </div>
                <template x-if="activeScheduleDetails?.error_message">
                    <div class="p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl text-red-600 dark:text-red-400">
                        <strong class="font-bold">Error:</strong> <span x-text="activeScheduleDetails?.error_message"></span>
                    </div>
                </template>
            </div>
            <div class="p-4 border-t border-gray-100 dark:border-white/[0.06] bg-gray-50/50 dark:bg-themeDark/40 flex justify-end">
                <button type="button" @click="scheduleDetailsModalOpen = false" class="px-5 h-10 rounded-xl border border-gray-200 dark:border-gray-800 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-themeDark">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL 4: SCHEDULE EDIT MODAL                            -->
    <!-- ======================================================== -->
    <div x-show="scheduleEditModalOpen" x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6"
         style="display: none;">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-md transition-opacity" @click="if(!savingEditSchedule) scheduleEditModalOpen = false"></div>
        <div class="relative bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden z-10">
            <div class="p-6 border-b border-gray-100 dark:border-white/[0.06] flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Edit Pending Schedule</h3>
                    <p class="text-xs text-gray-500">Update broadcast title, message content, or scheduled dispatch time.</p>
                </div>
                <button type="button" @click="if(!savingEditSchedule) scheduleEditModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4 overflow-y-auto">
                <!-- Title -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Title</label>
                    <input type="text" x-model="editingSchedule.title"
                           class="w-full h-11 px-3.5 border-2 border-gray-150 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                </div>
                <!-- Date & Time -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Date (Asia/Dhaka)</label>
                        <input type="date" x-model="editingSchedule.scheduled_date" :min="todayDhakaDate"
                               class="w-full h-11 px-3.5 border-2 border-gray-150 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Time</label>
                        <input type="time" x-model="editingSchedule.scheduled_time"
                               class="w-full h-11 px-3.5 border-2 border-gray-150 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                    </div>
                </div>
                <!-- Message -->
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-500 dark:text-gray-400 uppercase mb-1 block">Message Content</label>
                    <textarea rows="4" x-model="editingSchedule.message"
                              class="w-full p-3.5 border-2 border-gray-150 dark:border-gray-800 rounded-xl bg-white dark:bg-themeNavy text-xs font-medium text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all"></textarea>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 dark:border-white/[0.06] bg-gray-50/50 dark:bg-themeDark/40 flex justify-end gap-3">
                <button type="button" @click="scheduleEditModalOpen = false" :disabled="savingEditSchedule"
                        class="px-5 h-11 rounded-xl border-2 border-gray-200 dark:border-gray-800 text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark transition-colors">
                    Cancel
                </button>
                <button type="button" @click="saveEditedSchedule()" :disabled="savingEditSchedule"
                        class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg transition-all text-xs uppercase tracking-widest flex items-center gap-2">
                    <template x-if="savingEditSchedule">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    </template>
                    <span x-text="savingEditSchedule ? 'Saving...' : 'Save Changes'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function noticeBroadcastApp() {
    return {
        // Lookups from Server
        sessionsList: @json($sessions->map(fn($s) => ['id' => (string)$s->id, 'name' => $s->session_name])),
        classesList: @json($classes->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->class_name])),
        sectionsList: @json($sections->map(fn($sec) => ['id' => (string)$sec->id, 'name' => $sec->section_name])),
        classSectionsMap: @json($classSections ?? []),

        recipientGroups: [
            { id: 'all', name: 'All active students' },
            { id: 'class_wise', name: 'Class Wise' },
            { id: 'section_wise', name: 'Section Wise' },
            { id: 'gender_wise', name: 'Gender Wise' },
            { id: 'combined', name: 'Combined Filters' },
            { id: 'selective', name: 'Selective Students' },
        ],
        genderOptions: [
            { id: 'all', name: 'All genders' },
            { id: 'Male', name: 'Boys / Male' },
            { id: 'Female', name: 'Girls / Female' },
        ],
        selectionModes: [
            { id: 'all', name: 'All matching students' },
            { id: 'selective', name: 'Select specific students' },
        ],

        // Unified Dropdown State to ensure only 1 dropdown is open at a time
        dropdownOpen: null,

        // Filter States
        filters: {
            recipient_source: 'students',
            custom_numbers: '',
            session_year_id: '{{ $sessions->first()->id ?? "" }}',
            recipient_group: 'all',
            class_id: 'all',
            section_id: 'all',
            gender: 'all',
            recipient_selection: 'all',
            selected_student_ids: []
        },

        message: '',
        currentBalance: {{ json_encode($currentBalance) }},
        testMode: {{ json_encode($testMode) }},
        smsRate: {{ (float) $smsRate }},

        // Summary State
        summary: {
            total_students: 0,
            valid_numbers_count: 0,
            unique_numbers_count: 0,
            missing_invalid_count: 0,
            sms_segments: 0,
            encoding: 'GSM',
            characters: 0,
            rate_per_sms: {{ (float) $smsRate }},
            estimated_cost: '0.00',
            duplicate_phone_count: 0,
            custom_tokens_count: 0,
            custom_valid_count: 0,
            custom_invalid_count: 0,
            custom_duplicate_count: 0
        },

        loadingSummary: false,
        summaryDebounceTimer: null,

        // Tab & Delivery State
        activeMainTab: 'compose',
        delivery_mode: 'now',
        scheduled_date: '',
        scheduled_time: '',
        todayDhakaDate: '',
        savingSchedule: false,

        // Schedules State
        schedulesList: [],
        scheduleCounts: { all: 0, pending: 0, completed: 0, partial_failure: 0, failed: 0, cancelled: 0 },
        scheduleFilterStatus: 'all',
        scheduleSearch: '',
        schedulePagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 10
        },
        loadingSchedules: false,

        // Schedule Modals State
        scheduleDetailsModalOpen: false,
        activeScheduleDetails: null,
        scheduleEditModalOpen: false,
        editingSchedule: {
            id: null,
            title: '',
            message: '',
            scheduled_date: '',
            scheduled_time: ''
        },
        savingEditSchedule: false,

        // Preview Modal State
        previewModalOpen: false,
        previewStudents: [],
        previewSearch: '',
        loadingPreview: false,
        previewPagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 15
        },

        // Review Modal State
        reviewModalOpen: false,
        confirmedCheck: false,
        sendingBroadcast: false,
        ajaxToast: { message: '', type: 'success' },

        init() {
            try {
                const dhakaFormatter = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Dhaka', year: 'numeric', month: '2-digit', day: '2-digit' });
                this.todayDhakaDate = dhakaFormatter.format(new Date());
            } catch (e) {
                this.todayDhakaDate = new Date().toISOString().split('T')[0];
            }
            if (this.filters.session_year_id) {
                this.fetchSummary();
            }
            this.fetchScheduleCounts();
        },

        setRecipientSource(src) {
            if (this.filters.recipient_source === src) return;
            this.filters.recipient_source = src;
            this.debouncedFetchSummary();
        },

        onCustomNumbersInput() {
            this.debouncedFetchSummary();
        },

        clearCustomNumbers() {
            this.filters.custom_numbers = '';
            this.debouncedFetchSummary();
        },

        getRecipientSourceLabel() {
            if (this.filters.recipient_source === 'students') {
                return 'Filtered Students';
            } else if (this.filters.recipient_source === 'custom') {
                return 'Custom Mobile Numbers';
            } else if (this.filters.recipient_source === 'both') {
                return 'Both (Students & Custom Numbers)';
            }
            return 'Filtered Students';
        },

        resetSummary() {
            this.summary = {
                total_students: 0,
                valid_numbers_count: 0,
                unique_numbers_count: 0,
                missing_invalid_count: 0,
                sms_segments: 0,
                encoding: 'GSM',
                characters: 0,
                rate_per_sms: this.smsRate,
                estimated_cost: '0.00',
                duplicate_phone_count: 0,
                custom_tokens_count: 0,
                custom_valid_count: 0,
                custom_invalid_count: 0,
                custom_duplicate_count: 0
            };
        },

        toggleDropdown(name) {
            this.dropdownOpen = this.dropdownOpen === name ? null : name;
        },

        // Label Helpers for Custom Dropdowns
        getSessionLabel() {
            const s = this.sessionsList.find(x => String(x.id) === String(this.filters.session_year_id));
            return s ? s.name : 'Select session';
        },
        getRecipientGroupLabel() {
            const g = this.recipientGroups.find(x => x.id === this.filters.recipient_group);
            return g ? g.name : 'All active students';
        },
        getClassLabel() {
            if (this.filters.class_id === 'all') return 'All classes';
            const c = this.classesList.find(x => String(x.id) === String(this.filters.class_id));
            return c ? c.name : 'All classes';
        },
        getSectionLabel() {
            if (this.filters.section_id === 'all') return 'All sections';
            const sec = this.sectionsList.find(x => String(x.id) === String(this.filters.section_id));
            return sec ? sec.name : 'All sections';
        },
        getGenderLabel() {
            const gen = this.genderOptions.find(x => x.id === this.filters.gender);
            return gen ? gen.name : 'All genders';
        },
        getSelectionModeLabel() {
            const m = this.selectionModes.find(x => x.id === this.filters.recipient_selection);
            return m ? m.name : 'All matching students';
        },

        // Dependent Dropdown: Available Sections based on selected Class
        getAvailableSections() {
            if (this.filters.class_id === 'all') {
                return this.sectionsList;
            }
            const validSectionIds = this.classSectionsMap[this.filters.class_id] || [];
            if (!validSectionIds.length) {
                return this.sectionsList;
            }
            return this.sectionsList.filter(sec => 
                validSectionIds.some(v => String(v) === String(sec.id))
            );
        },

        // Control Visibility based on Recipient Group
        isClassFilterVisible() {
            return ['class_wise', 'section_wise', 'combined', 'selective'].includes(this.filters.recipient_group);
        },
        isSectionFilterVisible() {
            return ['section_wise', 'combined', 'selective'].includes(this.filters.recipient_group);
        },
        isGenderFilterVisible() {
            return ['gender_wise', 'combined'].includes(this.filters.recipient_group);
        },
        isSelectionModeFilterVisible() {
            return ['selective', 'combined'].includes(this.filters.recipient_group);
        },

        // Event Handlers for Dependent Cascading & State Sync
        onSessionChange(newSessionId) {
            this.filters.session_year_id = newSessionId;
            // Clear specific student selection when session changes
            this.filters.selected_student_ids = [];
            this.onFilterChange();
        },

        onClassChange(newClassId) {
            this.filters.class_id = newClassId;
            // Sync recipient group
            if (newClassId !== 'all') {
                if (this.filters.recipient_group === 'all') {
                    this.filters.recipient_group = 'class_wise';
                }
            } else {
                if (this.filters.section_id === 'all' && this.filters.recipient_selection === 'all') {
                    this.filters.recipient_group = 'all';
                }
            }
            // Check if current section_id is still valid for this class
            if (this.filters.section_id !== 'all') {
                const available = this.getAvailableSections();
                const isValid = available.some(sec => String(sec.id) === String(this.filters.section_id));
                if (!isValid) {
                    this.filters.section_id = 'all'; // Reset invalid dependent selection
                }
            }
            if (this.filters.selected_student_ids.length > 0) {
                this.filters.selected_student_ids = [];
            }
            this.onFilterChange();
        },

        onSectionChange(newSectionId) {
            this.filters.section_id = newSectionId;
            if (newSectionId !== 'all') {
                this.filters.recipient_group = 'section_wise';
            } else if (this.filters.class_id !== 'all') {
                this.filters.recipient_group = 'class_wise';
            }
            if (this.filters.selected_student_ids.length > 0) {
                this.filters.selected_student_ids = [];
            }
            this.onFilterChange();
        },

        onGenderChange(newGender) {
            this.filters.gender = newGender;
            if (this.filters.selected_student_ids.length > 0) {
                this.filters.selected_student_ids = [];
            }
            this.onFilterChange();
        },

        onRecipientGroupChange(newGroupId) {
            this.filters.recipient_group = newGroupId;

            // Reset only invalid dependent controls, strictly preserving Gender!
            if (newGroupId === 'all') {
                this.filters.class_id = 'all';
                this.filters.section_id = 'all';
                this.filters.recipient_selection = 'all';
                this.filters.selected_student_ids = [];
                // Gender is preserved across all active students!
            } else if (newGroupId === 'class_wise') {
                this.filters.section_id = 'all';
                this.filters.recipient_selection = 'all';
                this.filters.selected_student_ids = [];
                // Gender is preserved within class!
            } else if (newGroupId === 'section_wise') {
                this.filters.recipient_selection = 'all';
                this.filters.selected_student_ids = [];
                // Gender is preserved within section!
            } else if (newGroupId === 'gender_wise') {
                this.filters.class_id = 'all';
                this.filters.section_id = 'all';
                this.filters.recipient_selection = 'all';
                this.filters.selected_student_ids = [];
            } else if (newGroupId === 'selective') {
                this.filters.recipient_selection = 'selective';
                if (this.filters.selected_student_ids.length === 0) {
                    this.openPreviewModal();
                }
            }

            this.onFilterChange();
        },

        onSelectionModeSelect(modeId) {
            this.filters.recipient_selection = modeId;
            if (modeId === 'all') {
                this.filters.selected_student_ids = [];
                if (this.filters.section_id !== 'all') {
                    this.filters.recipient_group = 'section_wise';
                } else if (this.filters.class_id !== 'all') {
                    this.filters.recipient_group = 'class_wise';
                } else {
                    this.filters.recipient_group = 'all';
                }
            } else if (modeId === 'selective') {
                this.filters.recipient_group = 'selective';
                if (this.filters.selected_student_ids.length === 0) {
                    this.openPreviewModal();
                }
            }
            this.onFilterChange();
        },

        onFilterChange() {
            this.debouncedFetchSummary();
        },

        onMessageInput() {
            this.debouncedFetchSummary();
        },

        debouncedFetchSummary() {
            clearTimeout(this.summaryDebounceTimer);
            this.summaryDebounceTimer = setTimeout(() => {
                this.fetchSummary();
            }, 300);
        },

        async fetchSummary() {
            const hasSession = !!this.filters.session_year_id;
            const hasCustom = !!(this.filters.custom_numbers && this.filters.custom_numbers.trim());

            if (this.filters.recipient_source === 'students' && !hasSession) {
                this.resetSummary();
                return;
            }
            if (this.filters.recipient_source === 'custom' && !hasCustom) {
                this.resetSummary();
                return;
            }
            if (this.filters.recipient_source === 'both' && !hasSession && !hasCustom) {
                this.resetSummary();
                return;
            }

            this.loadingSummary = true;
            try {
                const response = await fetch("{{ route('sms.general-notice.recipient-summary') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_year_id: this.filters.session_year_id,
                        class_id: this.filters.class_id,
                        section_id: this.filters.section_id,
                        gender: this.filters.gender,
                        recipient_selection: this.filters.recipient_selection,
                        selected_student_ids: this.filters.selected_student_ids,
                        recipient_source: this.filters.recipient_source,
                        custom_numbers: this.filters.custom_numbers,
                        message: this.message
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.summary = data;
                }
            } catch (err) {
                console.error("Error fetching recipient summary:", err);
            } finally {
                this.loadingSummary = false;
            }
        },

        async openPreviewModal() {
            if (this.filters.recipient_source === 'students' && !this.filters.session_year_id) {
                showAlert("Please select an Academic Session first.", "Required Filter");
                return;
            }
            if (this.filters.recipient_source === 'custom' && !(this.filters.custom_numbers && this.filters.custom_numbers.trim())) {
                showAlert("Please enter custom recipient phone numbers first.", "Numbers Required");
                return;
            }
            if (this.filters.recipient_source === 'both' && !this.filters.session_year_id && !(this.filters.custom_numbers && this.filters.custom_numbers.trim())) {
                showAlert("Please select an Academic Session or enter custom numbers.", "Required Input");
                return;
            }
            this.previewModalOpen = true;
            this.previewSearch = '';
            await this.fetchPreview(1);
        },

        async fetchPreview(page = 1) {
            this.loadingPreview = true;
            try {
                const response = await fetch("{{ route('sms.general-notice.preview-recipients') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_year_id: this.filters.session_year_id,
                        class_id: this.filters.class_id,
                        section_id: this.filters.section_id,
                        gender: this.filters.gender,
                        recipient_selection: this.filters.recipient_selection,
                        selected_student_ids: this.filters.selected_student_ids,
                        recipient_source: this.filters.recipient_source,
                        custom_numbers: this.filters.custom_numbers,
                        search: this.previewSearch,
                        page: page,
                        per_page: 15
                    })
                });

                const res = await response.json();
                if (res.success) {
                    this.previewStudents = res.data;
                    this.previewPagination = res.pagination;
                }
            } catch (err) {
                console.error("Error loading preview recipients:", err);
            } finally {
                this.loadingPreview = false;
            }
        },

        isStudentSelected(id) {
            return this.filters.selected_student_ids.some(x => String(x) === String(id));
        },

        toggleStudentSelection(id) {
            this.filters.recipient_selection = 'selective';
            const index = this.filters.selected_student_ids.findIndex(x => String(x) === String(id));
            if (index > -1) {
                this.filters.selected_student_ids.splice(index, 1);
            } else {
                this.filters.selected_student_ids.push(id);
            }
            this.debouncedFetchSummary();
        },

        isAllPageSelected() {
            if (this.previewStudents.length === 0) return false;
            return this.previewStudents.every(st => this.isStudentSelected(st.id));
        },

        togglePageSelection(checked) {
            this.filters.recipient_selection = 'selective';
            this.previewStudents.forEach(st => {
                const idx = this.filters.selected_student_ids.findIndex(x => String(x) === String(st.id));
                if (checked && idx === -1) {
                    this.filters.selected_student_ids.push(st.id);
                } else if (!checked && idx > -1) {
                    this.filters.selected_student_ids.splice(idx, 1);
                }
            });
            this.debouncedFetchSummary();
        },

        selectAllOnPage() {
            this.togglePageSelection(true);
        },

        clearSelectedStudents() {
            this.filters.selected_student_ids = [];
            this.debouncedFetchSummary();
        },

        resetFilters() {
            this.filters.recipient_group = 'all';
            this.filters.class_id = 'all';
            this.filters.section_id = 'all';
            this.filters.gender = 'all';
            this.filters.recipient_selection = 'all';
            this.filters.selected_student_ids = [];
            this.debouncedFetchSummary();
        },

        formatScheduledPreview() {
            if (!this.scheduled_date || !this.scheduled_time) return '';
            return `${this.scheduled_date} ${this.scheduled_time} (Asia/Dhaka)`;
        },

        formatDateTime(dtStr) {
            if (!dtStr) return '—';
            try {
                const dt = new Date(dtStr);
                return new Intl.DateTimeFormat('en-US', {
                    timeZone: 'Asia/Dhaka',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                }).format(dt);
            } catch (e) {
                return dtStr;
            }
        },

        getStatusBadgeClass(status) {
            switch (status) {
                case 'pending':
                    return 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800';
                case 'processing':
                    return 'bg-blue-50 dark:bg-themeBlue/10 text-themeBlue dark:text-blue-400 border border-themeBlue/30';
                case 'completed':
                    return 'bg-green-50 dark:bg-green-950/40 text-themeGreen dark:text-green-400 border border-green-200 dark:border-green-800';
                case 'partial_failure':
                    return 'bg-orange-50 dark:bg-orange-950/40 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-800';
                case 'failed':
                    return 'bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800';
                case 'cancelled':
                    return 'bg-gray-100 dark:bg-themeDark text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-800';
                default:
                    return 'bg-gray-50 text-gray-600 border border-gray-200';
            }
        },

        getStatusDotClass(status) {
            switch (status) {
                case 'pending': return 'bg-amber-500 animate-pulse';
                case 'processing': return 'bg-themeBlue animate-spin';
                case 'completed': return 'bg-themeGreen';
                case 'partial_failure': return 'bg-orange-500';
                case 'failed': return 'bg-red-500';
                case 'cancelled': return 'bg-gray-400';
                default: return 'bg-gray-400';
            }
        },

        formatStatusLabel(status) {
            switch (status) {
                case 'pending': return 'Pending';
                case 'processing': return 'Processing';
                case 'completed': return 'Completed';
                case 'partial_failure': return 'Partial';
                case 'failed': return 'Failed';
                case 'cancelled': return 'Cancelled';
                default: return status || 'Unknown';
            }
        },

        filterSchedulesByStatus(status) {
            this.scheduleFilterStatus = status;
            this.fetchSchedules(1);
        },

        async fetchScheduleCounts() {
            try {
                const response = await fetch("{{ route('sms.general-notice.schedules') }}", {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.counts) {
                    this.scheduleCounts = data.counts;
                }
            } catch (e) {
                console.error("Failed to load schedule counts:", e);
            }
        },

        async fetchSchedules(page = 1) {
            this.loadingSchedules = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    status: this.scheduleFilterStatus,
                    search: this.scheduleSearch || ''
                });
                const response = await fetch(`{{ route('sms.general-notice.schedules') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success) {
                    this.schedulesList = data.schedules.data || [];
                    this.schedulePagination = {
                        current_page: data.schedules.current_page || 1,
                        last_page: data.schedules.last_page || 1,
                        total: data.schedules.total || 0,
                        per_page: data.schedules.per_page || 10
                    };
                    if (data.counts) {
                        this.scheduleCounts = data.counts;
                    }
                }
            } catch (err) {
                console.error("Error fetching schedules:", err);
            } finally {
                this.loadingSchedules = false;
            }
        },

        async openReviewModal() {
            if (this.filters.recipient_source === 'students' && !this.filters.session_year_id) {
                showAlert("Please select an Academic Session first.", "Required Filter");
                return;
            }
            if (this.filters.recipient_source === 'custom' && !(this.filters.custom_numbers && this.filters.custom_numbers.trim())) {
                showAlert("Please enter custom recipient mobile numbers.", "Numbers Required");
                return;
            }
            if (this.filters.recipient_source === 'both' && !this.filters.session_year_id && !(this.filters.custom_numbers && this.filters.custom_numbers.trim())) {
                showAlert("Please select an Academic Session or enter custom numbers.", "Required Input");
                return;
            }
            if (!this.message.trim()) {
                showAlert("Please type the notice message content.", "Message Required");
                return;
            }

            // Scheduled delivery validation
            if (this.delivery_mode === 'schedule') {
                if (!this.scheduled_date || !this.scheduled_time) {
                    showAlert("Please select both a scheduled date and time.", "Schedule Time Required");
                    return;
                }

                // Verify future time in Asia/Dhaka
                const targetIso = `${this.scheduled_date}T${this.scheduled_time}:00`;
                const dhakaNowStr = new Intl.DateTimeFormat('en-CA', {
                    timeZone: 'Asia/Dhaka',
                    year: 'numeric', month: '2-digit', day: '2-digit',
                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                    hour12: false
                }).format(new Date()).replace(', ', 'T');

                if (targetIso <= dhakaNowStr) {
                    showAlert("Scheduled time must be at least 2 minutes in the future (Asia/Dhaka timezone).", "Invalid Time");
                    return;
                }
            }

            // If summary calculation is in flight or zero, fetch immediately to ensure fresh counts
            if (this.loadingSummary || this.summary.unique_numbers_count === 0) {
                await this.fetchSummary();
            }

            if (this.summary.unique_numbers_count === 0) {
                showAlert("No valid recipient phone numbers match your current criteria.", "Cannot Proceed");
                return;
            }
            this.confirmedCheck = false;
            this.reviewModalOpen = true;
        },

        getFilterSummaryText() {
            const parts = [];
            parts.push(this.getClassLabel());
            if (this.filters.section_id !== 'all') {
                parts.push(this.getSectionLabel());
            }
            parts.push(this.getGenderLabel());

            if (this.filters.recipient_selection === 'selective') {
                parts.push('(' + this.filters.selected_student_ids.length + ' Selected)');
            }

            return parts.join(' · ');
        },

        async dispatchBroadcast() {
            if (!this.confirmedCheck || this.sendingBroadcast) return;

            this.sendingBroadcast = true;
            try {
                const response = await fetch("{{ route('sms.general-notice.send') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_year_id: this.filters.session_year_id,
                        class_id: this.filters.class_id,
                        section_id: this.filters.section_id,
                        gender: this.filters.gender,
                        recipient_selection: this.filters.recipient_selection,
                        selected_student_ids: this.filters.selected_student_ids,
                        recipient_source: this.filters.recipient_source,
                        custom_numbers: this.filters.custom_numbers,
                        message: this.message
                    })
                });

                const data = await response.json();
                this.reviewModalOpen = false;

                if (data.success) {
                    this.ajaxToast = {
                        message: data.message,
                        type: 'success'
                    };
                    this.message = '';
                    this.fetchSummary();
                } else {
                    this.ajaxToast = {
                        message: data.message || 'Failed to dispatch broadcast.',
                        type: 'error'
                    };
                }
            } catch (err) {
                console.error("Broadcast dispatch error:", err);
                this.ajaxToast = {
                    message: "A network error occurred while submitting the broadcast. Please check SMS delivery reports.",
                    type: 'error'
                };
            } finally {
                this.sendingBroadcast = false;
            }
        },

        async saveSchedule() {
            if (!this.confirmedCheck || this.savingSchedule) return;

            this.savingSchedule = true;
            try {
                const response = await fetch("{{ route('sms.general-notice.schedule.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        session_year_id: this.filters.session_year_id,
                        class_id: this.filters.class_id,
                        section_id: this.filters.section_id,
                        gender: this.filters.gender,
                        recipient_selection: this.filters.recipient_selection,
                        selected_student_ids: this.filters.selected_student_ids,
                        recipient_source: this.filters.recipient_source,
                        custom_numbers: this.filters.custom_numbers,
                        message: this.message,
                        scheduled_date: this.scheduled_date,
                        scheduled_time: this.scheduled_time
                    })
                });

                const data = await response.json();
                this.reviewModalOpen = false;

                if (data.success) {
                    this.ajaxToast = {
                        message: data.message,
                        type: 'success'
                    };
                    this.message = '';
                    this.scheduled_date = '';
                    this.scheduled_time = '';
                    this.delivery_mode = 'now';
                    this.fetchSummary();
                    this.fetchScheduleCounts();
                    this.activeMainTab = 'schedules';
                    this.fetchSchedules(1);
                } else {
                    this.ajaxToast = {
                        message: data.message || 'Failed to schedule broadcast.',
                        type: 'error'
                    };
                }
            } catch (err) {
                console.error("Schedule creation error:", err);
                this.ajaxToast = {
                    message: "A network error occurred while saving the schedule.",
                    type: 'error'
                };
            } finally {
                this.savingSchedule = false;
            }
        },

        openScheduleDetailsModal(item) {
            this.activeScheduleDetails = item;
            this.scheduleDetailsModalOpen = true;
        },

        openScheduleEditModal(item) {
            const rawDt = item.scheduled_at || '';
            const datePart = rawDt.includes('T') ? rawDt.split('T')[0] : rawDt.split(' ')[0];
            const timePart = rawDt.includes('T') ? rawDt.split('T')[1].substring(0, 5) : (rawDt.split(' ')[1] ? rawDt.split(' ')[1].substring(0, 5) : '09:00');

            this.editingSchedule = {
                id: item.id,
                title: item.title,
                message: item.message,
                scheduled_date: datePart,
                scheduled_time: timePart
            };
            this.scheduleEditModalOpen = true;
        },

        async saveEditedSchedule() {
            if (!this.editingSchedule.message.trim() || !this.editingSchedule.scheduled_date || !this.editingSchedule.scheduled_time) {
                showAlert("Please fill in message, date, and time.", "Required Fields");
                return;
            }

            this.savingEditSchedule = true;
            try {
                const url = `{{ url('/sms/general-notice/schedules') }}/${this.editingSchedule.id}`;
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: this.editingSchedule.title,
                        message: this.editingSchedule.message,
                        scheduled_date: this.editingSchedule.scheduled_date,
                        scheduled_time: this.editingSchedule.scheduled_time
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.scheduleEditModalOpen = false;
                    this.ajaxToast = { message: data.message, type: 'success' };
                    this.fetchSchedules(this.schedulePagination.current_page);
                } else {
                    showAlert(data.message || 'Failed to update schedule.', 'Error');
                }
            } catch (err) {
                console.error("Error updating schedule:", err);
                showAlert("Network error updating schedule.", "Error");
            } finally {
                this.savingEditSchedule = false;
            }
        },

        async cancelScheduleAction(item) {
            const confirmed = typeof showConfirm === 'function'
                ? await showConfirm("Cancel Scheduled SMS?", `Are you sure you want to cancel the scheduled broadcast "${item.title}"?`)
                : confirm(`Are you sure you want to cancel the scheduled broadcast "${item.title}"?`);

            if (!confirmed) return;

            try {
                const url = `{{ url('/sms/general-notice/schedules') }}/${item.id}/cancel`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.ajaxToast = { message: data.message, type: 'success' };
                    this.fetchSchedules(this.schedulePagination.current_page);
                    this.fetchScheduleCounts();
                } else {
                    showAlert(data.message || 'Failed to cancel schedule.', 'Error');
                }
            } catch (err) {
                console.error("Error cancelling schedule:", err);
                showAlert("Network error cancelling schedule.", "Error");
            }
        }
    };
}
</script>
@endpush
@endsection