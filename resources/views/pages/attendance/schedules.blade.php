@extends('tyro-dashboard::layouts.admin')

@section('title', 'Student Attendance Schedules')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div x-data="attendanceScheduleManager()" x-init="init()" class="w-full min-h-screen">

    <!-- Page Header -->
    <div class="mb-6 flex flex-col lg:flex-row justify-between lg:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Student Attendance Schedules</span>
            </h1>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Manage default normal school timings and date-bound exam campaigns for biometric RFID student attendance.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('attendance.index') }}"
               class="inline-flex items-center justify-center gap-2 px-4 h-11 bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-200 border-2 border-gray-100 dark:border-gray-800 hover:border-themeBlue hover:text-themeBlue rounded-xl text-xs font-black uppercase tracking-[0.12em] shadow-sm hover:-translate-y-0.5 transition-all !no-underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                <span>Daily Attendance</span>
            </a>
            <button type="button" @click="openCreateExamModal()"
                    class="inline-flex items-center justify-center gap-2 px-5 h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white rounded-xl text-xs font-black uppercase tracking-[0.14em] shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span>Create Exam Schedule</span>
            </button>
        </div>
    </div>

    <!-- Today's Live Effective Schedule Status Banner -->
    @php
        $todayMode = $todayOverview['active_mode'] ?? 'normal';
        $todayCampaigns = $todayOverview['exam_campaigns'] ?? [];
        $todaySlots = $todayOverview['slots'] ?? [];
        $modeBadgeClass = match ($todayMode) {
            'exam'  => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/25',
            'mixed' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/25',
            default => 'bg-themeGreen/10 text-themeGreen border-themeGreen/25',
        };
        $modeHeading = match ($todayMode) {
            'exam'  => 'Exam Time Active Today',
            'mixed' => 'Mixed Schedule Active Today (Partial Exam + Normal)',
            default => 'Normal School Time Active Today',
        };
        $format12h = static function (?string $time): string {
            if (!$time) {
                return '—';
            }
            return \Carbon\Carbon::parse($time, 'Asia/Dhaka')->format('h:i A');
        };
    @endphp
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-white/[0.06]">
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $todayMode === 'exam' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : ($todayMode === 'mixed' ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400' : 'bg-themeGreen/10 text-themeGreen') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.15em] border {{ $modeBadgeClass }}">
                            {{ $modeHeading }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.15em] bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-white/[0.08]">
                            Today: {{ $todayFormatted }} (Asia/Dhaka)
                        </span>
                    </div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-2">
                        @if(count($todayCampaigns) > 0)
                            Running campaign{{ count($todayCampaigns) > 1 ? 's' : '' }}:
                            <span class="font-bold text-gray-800 dark:text-gray-200">
                                {{ collect($todayCampaigns)->pluck('name')->implode(', ') }}
                            </span>
                        @else
                            All classes are currently evaluated using the default Normal School Time schedule.
                        @endif
                    </p>
                </div>
            </div>

            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 shrink-0">
                <span class="font-black text-gray-900 dark:text-white">{{ count($todaySlots) }}</span> effective student slot{{ count($todaySlots) === 1 ? '' : 's' }} active
            </div>
        </div>

        <!-- Effective Today Slot Summary Pills -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 pt-4">
            @forelse($todaySlots as $tSlot)
                <div class="rounded-2xl border border-gray-100 dark:border-white/[0.06] bg-gray-50/70 dark:bg-themeDark/60 px-4 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-gray-900 dark:text-white truncate">{{ $tSlot['slot_name'] }}</span>
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider {{ ($tSlot['mode'] ?? 'normal') === 'exam' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-themeBlue/10 text-themeBlue' }}">
                                {{ ($tSlot['mode'] ?? 'normal') === 'exam' ? 'Exam' : 'Normal' }}
                            </span>
                        </div>
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
                            Classes: <span class="text-gray-700 dark:text-gray-200 font-bold">{{ $tSlot['class_summary'] }}</span>
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-[11px] font-mono font-bold text-themeGreen">{{ $format12h($tSlot['on_time_start']) }} – {{ $format12h($tSlot['on_time_end']) }}</p>
                        <p class="text-[10px] font-mono font-semibold text-themeBlue">Out: {{ $format12h($tSlot['check_out_from']) }}+</p>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    No active student schedules found. Run StudentAttendanceScheduleSeeder or configure a schedule below.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section A: Normal School Time (Default Baseline) -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-white/[0.06] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-themeBlue/10 text-themeBlue flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-base font-black text-gray-900 dark:text-white tracking-tight">
                            {{ $normalSchedule['name'] ?? 'Normal School Time' }}
                        </h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-themeGreen/10 text-themeGreen border border-themeGreen/20">
                            Default Baseline
                        </span>
                    </div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                        Applies automatically to all classes outside any active Exam Time campaign date range.
                    </p>
                </div>
            </div>

            @if($normalSchedule)
                <button type="button" @click="openEditNormalModal()"
                        class="inline-flex items-center justify-center gap-2 px-4 h-10 bg-gray-50/80 dark:bg-themeDark text-themeBlue border-2 border-gray-100 dark:border-gray-800 hover:border-themeBlue hover:bg-themeBlue/5 rounded-xl text-xs font-black uppercase tracking-[0.14em] transition-all hover:-translate-y-0.5 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    <span>Edit Normal Timings</span>
                </button>
            @endif
        </div>

        <div class="px-6 pb-5 pt-1">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                <table class="w-full text-left border-collapse table tabular-nums [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="w-14 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">SL</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Slot Name</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Classes Covered</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">On-Time In (Present)</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">Late Window</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">Absent From</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">Check-Out Time</th>
                        </tr>
                    </thead>
                    <tbody class="font-medium">
                        @if($normalSchedule && count($normalSchedule['slots']) > 0)
                            @foreach($normalSchedule['slots'] as $idx => $slot)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                    <td class="py-0 px-0 text-center font-mono font-black text-gray-500 dark:text-gray-400 text-sm">{{ $idx + 1 }}</td>
                                    <td class="py-0 px-0">
                                        <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $slot['slot_name'] }}</div>
                                        <div class="text-[11px] font-semibold text-gray-400 dark:text-gray-500">{{ $slot['shift_group'] }} Shift</div>
                                    </td>
                                    <td class="py-0 px-0 whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-[11px] font-black bg-themeBlue/10 text-themeBlue border border-themeBlue/20 inline-block">
                                            {{ $slot['class_summary'] }}
                                        </span>
                                    </td>
                                    <td class="py-0 px-0 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-green-500/10 text-themeGreen border border-themeGreen/20 inline-block">
                                            {{ $format12h($slot['on_time_start']) }} – {{ $format12h($slot['on_time_end']) }}
                                        </span>
                                    </td>
                                    <td class="py-0 px-0 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-block">
                                            {{ $format12h($slot['late_start']) }} – {{ $format12h($slot['late_end']) }}
                                        </span>
                                    </td>
                                    <td class="py-0 px-0 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-rose-500/10 text-rose-500 border border-rose-500/20 inline-block">
                                            {{ $format12h($slot['absent_from']) }} onwards
                                        </span>
                                    </td>
                                    <td class="py-0 px-0 text-center whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-themeBlue/10 text-themeBlue border border-themeBlue/20 inline-block">
                                            {{ $format12h($slot['check_out_from']) }} onwards
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="py-10 text-center text-xs font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                                    Default Normal School Time schedule has not been seeded yet.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section B: Exam Time Campaigns (Term Exams & Monthly Tests) -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-white/[0.06] flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-white tracking-tight">Exam Time Campaigns (Term Exams &amp; Monthly Tests)</h2>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">
                        Create full-school term exams or partial monthly tests from the standard 3-slot template. Automatically reverts to Normal Time after the end date.
                    </p>
                </div>
            </div>

            <button type="button" @click="openCreateExamModal()"
                    class="inline-flex items-center justify-center gap-2 px-5 h-10 bg-gradient-to-r from-themeBlue to-themeGreen text-white rounded-xl text-xs font-black uppercase tracking-[0.12em] shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span>Create Exam Schedule (From Standard Template)</span>
            </button>
        </div>

        <div class="px-6 pb-5 pt-1">
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 !rounded-none overflow-x-auto">
                <table class="w-full text-left border-collapse table tabular-nums [&>thead>tr]:!bg-transparent [&>thead>tr>th]:!bg-transparent [&>thead>tr>th]:!py-2.5 [&>thead>tr>th]:!px-3 [&>thead>tr>th:first-child]:!pl-0.5 [&>thead>tr>th:last-child]:!pr-0.5 [&>thead>tr>th]:!border-b [&>thead>tr>th]:!border-gray-200 dark:[&>thead>tr>th]:!border-white/[0.08] [&>tbody>tr]:!bg-transparent [&>tbody>tr>td]:!py-3 [&>tbody>tr>td]:!px-3 [&>tbody>tr>td:first-child]:!pl-0.5 [&>tbody>tr>td:last-child]:!pr-0.5 [&>tbody>tr>td]:!border-b [&>tbody>tr>td]:!border-gray-100 dark:[&>tbody>tr>td]:!border-white/[0.06] [&>tbody>tr:last-child>td]:!border-b-0 [&>tbody>tr:hover>td]:!bg-gray-50/60 dark:[&>tbody>tr:hover>td]:!bg-themeDark/35">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="w-14 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">SL</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Campaign Name &amp; Scope</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] whitespace-nowrap">Effective Dates</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Slots &amp; Mapped Classes</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-center">Status</th>
                            <th class="w-36 !bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="font-medium">
                        @forelse($examCampaigns as $cIdx => $campaign)
                            @php
                                $badgeClass = match ($campaign['status_code']) {
                                    'active_now' => 'bg-themeGreen/10 text-themeGreen border-themeGreen/25',
                                    'upcoming'   => 'bg-themeBlue/10 text-themeBlue border-themeBlue/25',
                                    'expired'    => 'bg-gray-500/10 text-gray-500 dark:text-gray-400 border-gray-400/25',
                                    default      => 'bg-rose-500/10 text-rose-500 border-rose-500/25',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                                <td class="py-0 px-0 text-center font-mono font-black text-gray-500 dark:text-gray-400 text-sm">{{ $cIdx + 1 }}</td>
                                <td class="py-0 px-0">
                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $campaign['name'] }}</div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-themeDark text-gray-600 dark:text-gray-300 border border-gray-200/80 dark:border-white/[0.08]">
                                            {{ $campaign['branch_name'] }}
                                        </span>
                                        @if(!empty($campaign['exam_name']))
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue border border-themeBlue/20">
                                                Exam: {{ $campaign['exam_name'] }}
                                            </span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                            P{{ $campaign['priority'] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-0 px-0 whitespace-nowrap">
                                    <span class="text-xs font-mono font-bold text-gray-700 dark:text-gray-300">
                                        {{ $campaign['date_range_text'] }}
                                    </span>
                                </td>
                                <td class="py-0 px-0">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($campaign['slots'] as $cSlot)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-gray-50 dark:bg-themeDark text-gray-700 dark:text-gray-200 border border-gray-200/70 dark:border-white/[0.08]">
                                                <span class="text-themeBlue">{{ $cSlot['class_summary'] }}</span>
                                                <span class="text-gray-400">·</span>
                                                <span class="font-mono text-[10px] text-gray-500 dark:text-gray-400">{{ $format12h($cSlot['on_time_start']) }}–{{ $format12h($cSlot['on_time_end']) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-0 px-0 text-center whitespace-nowrap">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border inline-block {{ $badgeClass }}">
                                        {{ $campaign['status_label'] }}
                                    </span>
                                </td>
                                <td class="py-0 px-0">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Toggle Active/Inactive -->
                                        <button type="button"
                                                @click="toggleCampaignStatus({{ $campaign['id'] }}, {{ $campaign['is_active'] ? 'true' : 'false' }}, @js($campaign['name']))"
                                                title="{{ $campaign['is_active'] ? 'Deactivate Campaign' : 'Activate Campaign' }}"
                                                class="action-btn {{ $campaign['is_active'] ? 'text-amber-600 hover:text-amber-700 hover:border-amber-500' : 'text-themeGreen hover:text-themeGreen hover:border-themeGreen' }}">
                                            @if($campaign['is_active'])
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
                                                </svg>
                                            @endif
                                        </button>

                                        <!-- Edit Campaign -->
                                        <button type="button"
                                                @click="openEditCampaignModal({{ $campaign['id'] }})"
                                                title="Edit Campaign"
                                                class="action-btn text-themeBlue hover:text-themeBlue hover:border-themeBlue">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                            </svg>
                                        </button>

                                        <!-- Delete Campaign -->
                                        <button type="button"
                                                @click="deleteCampaign({{ $campaign['id'] }}, @js($campaign['name']))"
                                                title="Delete Campaign"
                                                class="action-btn text-red-600 hover:text-red-800 hover:border-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <p class="text-xs font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 mb-3">
                                        No Exam Time Campaigns Created Yet
                                    </p>
                                    <button type="button" @click="openCreateExamModal()"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-themeBlue/10 text-themeBlue text-xs font-black uppercase tracking-wider hover:bg-themeBlue/20 transition-colors">
                                        + Create First Exam Schedule From Template
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Schedule Create / Edit Modal (z-[9999]) -->
    <div x-show="modalOpen"
         x-cloak
         @keydown.escape.window="if(modalOpen) closeModal()"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-themeDark/45 backdrop-blur-md overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.stop
             class="relative w-full max-w-5xl max-h-[90vh] flex flex-col bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.08] shadow-2xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100 dark:border-white/[0.08] flex items-center justify-between gap-4 shrink-0">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight" x-text="modalTitle"></h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                              :class="form.mode === 'exam' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-themeGreen/10 text-themeGreen border border-themeGreen/20'"
                              x-text="form.mode === 'exam' ? 'Exam Campaign' : 'Normal Baseline'"></span>
                    </div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5" x-text="modalSubtitle"></p>
                </div>
                <button type="button" @click="closeModal()"
                        class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-themeDark text-gray-500 hover:text-gray-900 dark:hover:text-white flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Scrollable Body -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1">

                <!-- Validation Error Summary -->
                <template x-if="formErrors.length > 0">
                    <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3.5 text-rose-600 dark:text-rose-400">
                        <p class="text-xs font-black uppercase tracking-wider mb-1.5">Please fix the following issues:</p>
                        <ul class="list-disc list-inside text-xs font-semibold space-y-0.5">
                            <template x-for="(err, eIdx) in formErrors" :key="eIdx">
                                <li x-text="err"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <!-- Schedule Metadata Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Campaign / Schedule Name -->
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                            Schedule / Campaign Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-model="form.name" placeholder="e.g., 1st Term Exam 2026 or Class 9-10 Monthly Test"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark text-sm font-semibold text-gray-800 dark:text-gray-100 focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all">
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                            Priority (Higher Wins)
                        </label>
                        <input type="number" min="1" max="999" x-model.number="form.priority"
                               class="w-full h-11 px-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark text-sm font-mono font-bold text-gray-800 dark:text-gray-100 focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all">
                    </div>

                    <!-- Active Status Toggle (Exam campaigns only) -->
                    <div x-show="form.mode === 'exam'" class="flex flex-col justify-end">
                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                            Campaign Status
                        </label>
                        <button type="button" @click="form.is_active = !form.is_active"
                                class="h-11 px-4 rounded-xl border-2 flex items-center justify-between text-xs font-black uppercase tracking-wider transition-all"
                                :class="form.is_active ? 'bg-themeGreen/10 border-themeGreen/30 text-themeGreen' : 'bg-gray-50 dark:bg-themeDark border-gray-200 dark:border-gray-800 text-gray-500'">
                            <span x-text="form.is_active ? 'Active' : 'Inactive'"></span>
                            <span class="w-9 h-5 rounded-full relative transition-colors" :class="form.is_active ? 'bg-themeGreen' : 'bg-gray-300 dark:bg-gray-700'">
                                <span class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-all" :class="form.is_active ? 'left-4' : 'left-0.5'"></span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Exam-Only Date Range, Branch Scope & Linked Exam -->
                <div x-show="form.mode === 'exam'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Custom Start Date Picker (Rule 10) -->
                    <div class="relative" @click.away="activePicker === 'start_date' && (activePicker = null)">
                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                            Start Date <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="openDatePicker('start_date')"
                                class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            <span x-text="formatDateDisplay(form.start_date)"></span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>
                        <div x-show="activePicker === 'start_date'" x-cloak class="absolute left-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-2xl shadow-xl p-3.5" x-transition>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <button type="button" @click="shiftCalMonth(-1)" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-themeDark text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <div class="flex items-center gap-1.5 relative">
                                    <button type="button" @click="calMonthOpen = !calMonthOpen" class="px-2.5 py-1 rounded-lg bg-gray-50 dark:bg-themeDark text-xs font-black text-gray-800 dark:text-gray-100 hover:text-themeBlue">
                                        <span x-text="monthNames[calMonth]"></span>
                                    </button>
                                    <div x-show="calMonthOpen" @click.away="calMonthOpen = false" class="absolute left-0 top-8 z-50 w-36 max-h-48 overflow-y-auto bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-xl shadow-xl py-1">
                                        <template x-for="(mName, mIdx) in monthNames" :key="mIdx">
                                            <button type="button" @click="calMonth = mIdx; calMonthOpen = false"
                                                    class="w-full text-left px-3 py-1.5 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-themeDark/50"
                                                    :class="calMonth === mIdx ? 'text-themeBlue font-black bg-indigo-50 dark:bg-themeBlue/10' : 'text-gray-700 dark:text-gray-200'"
                                                    x-text="mName"></button>
                                        </template>
                                    </div>
                                    <input type="number" x-model.number="calYear" min="2020" max="2099"
                                           class="w-16 px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-themeDark text-xs font-mono font-black text-center text-gray-800 dark:text-gray-100 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <button type="button" @click="shiftCalMonth(1)" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-themeDark text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black text-gray-400 uppercase mb-1">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(cell, idx) in getCalendarDays()" :key="idx">
                                    <button type="button" :disabled="!cell.day" @click="pickCalendarDay('start_date', cell.day)"
                                            class="h-8 w-8 rounded-lg text-xs font-bold flex items-center justify-center transition-all"
                                            :class="cell.dateStr && cell.dateStr === form.start_date ? 'bg-themeBlue text-white font-black shadow-sm' : (cell.day ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark' : 'text-transparent cursor-default')"
                                            x-text="cell.day"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Custom End Date Picker (Rule 10) -->
                    <div class="relative" @click.away="activePicker === 'end_date' && (activePicker = null)">
                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                            End Date <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="openDatePicker('end_date')"
                                class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            <span x-text="formatDateDisplay(form.end_date)"></span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </button>
                        <div x-show="activePicker === 'end_date'" x-cloak class="absolute left-0 z-50 mt-1.5 w-72 bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-2xl shadow-xl p-3.5" x-transition>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <button type="button" @click="shiftCalMonth(-1)" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-themeDark text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <div class="flex items-center gap-1.5 relative">
                                    <button type="button" @click="calMonthOpen = !calMonthOpen" class="px-2.5 py-1 rounded-lg bg-gray-50 dark:bg-themeDark text-xs font-black text-gray-800 dark:text-gray-100 hover:text-themeBlue">
                                        <span x-text="monthNames[calMonth]"></span>
                                    </button>
                                    <div x-show="calMonthOpen" @click.away="calMonthOpen = false" class="absolute left-0 top-8 z-50 w-36 max-h-48 overflow-y-auto bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-xl shadow-xl py-1">
                                        <template x-for="(mName, mIdx) in monthNames" :key="mIdx">
                                            <button type="button" @click="calMonth = mIdx; calMonthOpen = false"
                                                    class="w-full text-left px-3 py-1.5 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-themeDark/50"
                                                    :class="calMonth === mIdx ? 'text-themeBlue font-black bg-indigo-50 dark:bg-themeBlue/10' : 'text-gray-700 dark:text-gray-200'"
                                                    x-text="mName"></button>
                                        </template>
                                    </div>
                                    <input type="number" x-model.number="calYear" min="2020" max="2099"
                                           class="w-16 px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-themeDark text-xs font-mono font-black text-center text-gray-800 dark:text-gray-100 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                </div>
                                <button type="button" @click="shiftCalMonth(1)" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-themeDark text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black text-gray-400 uppercase mb-1">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(cell, idx) in getCalendarDays()" :key="idx">
                                    <button type="button" :disabled="!cell.day" @click="pickCalendarDay('end_date', cell.day)"
                                            class="h-8 w-8 rounded-lg text-xs font-bold flex items-center justify-center transition-all"
                                            :class="cell.dateStr && cell.dateStr === form.end_date ? 'bg-themeBlue text-white font-black shadow-sm' : (cell.day ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark' : 'text-transparent cursor-default')"
                                            x-text="cell.day"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Branch Scope Custom Dropdown (Rule 7) -->
                    <div class="relative" @click.away="activeDropdown === 'branch' && (activeDropdown = null)">
                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                            Branch Scope
                        </label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'branch' ? null : 'branch'"
                                class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            <span class="truncate" x-text="selectedBranchLabel()"></span>
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'branch'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="form.branch_id = null; activeDropdown = null"
                                    class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors"
                                    :class="form.branch_id === null ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>All Branches (Global)</span>
                                <template x-if="form.branch_id === null">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                            <template x-for="b in branches" :key="b.id">
                                <button type="button" @click="form.branch_id = b.id; activeDropdown = null"
                                        class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors"
                                        :class="form.branch_id === b.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span x-text="b.branch_name"></span>
                                    <template x-if="form.branch_id === b.id">
                                        <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Linked Exam Custom Dropdown (Rule 7) -->
                    <div class="relative" @click.away="activeDropdown === 'exam' && (activeDropdown = null)">
                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                            Linked Exam (Optional)
                        </label>
                        <button type="button" @click="activeDropdown = activeDropdown === 'exam' ? null : 'exam'"
                                class="w-full h-11 px-3.5 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all">
                            <span class="truncate" x-text="selectedExamLabel()"></span>
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="activeDropdown === 'exam'" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                            <button type="button" @click="form.exam_id = null; activeDropdown = null"
                                    class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors"
                                    :class="form.exam_id === null ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>No Linked Exam</span>
                                <template x-if="form.exam_id === null">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                            <template x-for="ex in exams" :key="ex.id">
                                <button type="button" @click="selectExam(ex)"
                                        class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors"
                                        :class="form.exam_id === ex.id ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                    <span class="truncate" x-text="ex.name + (ex.session_name ? ' (' + ex.session_name + ')' : '')"></span>
                                    <template x-if="form.exam_id === ex.id">
                                        <svg class="w-3.5 h-3.5 text-themeBlue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Time Slots Builder -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-white/[0.08]">
                        <div>
                            <h4 class="text-sm font-black text-gray-900 dark:text-white tracking-tight">Shift Time Slots &amp; Class Mapping</h4>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Each class can be assigned to at most one slot. For partial monthly tests, keep only the affected classes checked.
                            </p>
                        </div>
                        <button type="button" @click="addSlot()"
                                class="inline-flex items-center justify-center gap-1.5 px-4 h-9 rounded-xl bg-themeBlue/10 hover:bg-themeBlue/20 text-themeBlue text-xs font-black uppercase tracking-wider transition-all shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>Add Time Slot</span>
                        </button>
                    </div>

                    <template x-for="(slot, sIdx) in form.slots" :key="slot._key">
                        <div class="rounded-2xl border border-gray-200/80 dark:border-white/[0.08] bg-gray-50/50 dark:bg-themeDark/55 p-4 space-y-4">
                            <!-- Slot Top Bar -->
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1 ml-0.5">
                                            Slot Name <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" x-model="slot.slot_name" placeholder="e.g., Morning Exam (Play - Four)"
                                               class="w-full h-10 px-3.5 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-themeNavy text-xs font-bold text-gray-800 dark:text-gray-100 focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1 ml-0.5">
                                            Shift Group
                                        </label>
                                        <div class="grid grid-cols-2 gap-1.5 h-10 p-1 rounded-xl bg-white dark:bg-themeNavy border-2 border-gray-100 dark:border-gray-800">
                                            <button type="button" @click="slot.shift_group = 'Morning'"
                                                    class="rounded-lg text-[11px] font-black uppercase tracking-wider transition-all"
                                                    :class="slot.shift_group === 'Morning' ? 'bg-themeBlue text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800'">
                                                Morning
                                            </button>
                                            <button type="button" @click="slot.shift_group = 'Day'"
                                                    class="rounded-lg text-[11px] font-black uppercase tracking-wider transition-all"
                                                    :class="slot.shift_group === 'Day' ? 'bg-themeGreen text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-800'">
                                                Day
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" @click="removeSlot(sIdx)" :disabled="form.slots.length <= 1"
                                        class="self-end md:self-center h-10 px-3 rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-500 hover:bg-rose-500/20 text-[11px] font-black uppercase tracking-wider disabled:opacity-40 disabled:cursor-not-allowed transition-all shrink-0">
                                    Remove Slot
                                </button>
                            </div>

                            <!-- 5 Custom Time Pickers (Rule 10) -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                                <template x-for="tf in timeFields" :key="tf.key">
                                    <div class="relative" @click.away="activeTimeKey === (slot._key + ':' + tf.key) && (activeTimeKey = null)">
                                        <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1 ml-0.5" x-text="tf.label"></label>
                                        <button type="button" @click="openTimePicker(slot, tf.key)"
                                                class="w-full h-10 px-3 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-themeNavy flex items-center justify-between text-xs font-mono font-bold text-gray-800 dark:text-gray-100 focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all">
                                            <span x-text="formatTime12h(slot[tf.key])"></span>
                                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                        </button>

                                        <!-- Popover Time Selector -->
                                        <div x-show="activeTimeKey === (slot._key + ':' + tf.key)" x-cloak
                                             class="absolute left-0 z-50 mt-1.5 w-60 bg-white dark:bg-themeNavy border border-gray-200 dark:border-white/[0.08] rounded-2xl shadow-xl p-3 space-y-3" x-transition>
                                            <div class="grid grid-cols-3 gap-2">
                                                <!-- Hour (1-12) -->
                                                <div>
                                                    <span class="block text-[9px] font-black uppercase tracking-widest text-gray-400 text-center mb-1">Hour</span>
                                                    <div class="max-h-36 overflow-y-auto space-y-0.5 pr-0.5">
                                                        <template x-for="h in hours12" :key="h">
                                                            <button type="button" @click="setTimePart(slot, tf.key, 'hour', h)"
                                                                    class="w-full py-1 rounded-lg text-xs font-mono font-bold text-center transition-colors"
                                                                    :class="parseTimeParts(slot[tf.key]).hour === h ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark'"
                                                                    x-text="String(h).padStart(2, '0')"></button>
                                                        </template>
                                                    </div>
                                                </div>
                                                <!-- Minute (00-59) -->
                                                <div>
                                                    <span class="block text-[9px] font-black uppercase tracking-widest text-gray-400 text-center mb-1">Min</span>
                                                    <div class="max-h-36 overflow-y-auto space-y-0.5 pr-0.5">
                                                        <template x-for="m in minutes60" :key="m">
                                                            <button type="button" @click="setTimePart(slot, tf.key, 'minute', m)"
                                                                    class="w-full py-1 rounded-lg text-xs font-mono font-bold text-center transition-colors"
                                                                    :class="parseTimeParts(slot[tf.key]).minute === m ? 'bg-themeBlue text-white font-black' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-themeDark'"
                                                                    x-text="String(m).padStart(2, '0')"></button>
                                                        </template>
                                                    </div>
                                                </div>
                                                <!-- AM / PM -->
                                                <div>
                                                    <span class="block text-[9px] font-black uppercase tracking-widest text-gray-400 text-center mb-1">AM/PM</span>
                                                    <div class="space-y-1">
                                                        <button type="button" @click="setTimePart(slot, tf.key, 'period', 'AM')"
                                                                class="w-full py-2 rounded-lg text-xs font-black uppercase transition-colors"
                                                                :class="parseTimeParts(slot[tf.key]).period === 'AM' ? 'bg-themeGreen text-white' : 'bg-gray-50 dark:bg-themeDark text-gray-600 dark:text-gray-300'">AM</button>
                                                        <button type="button" @click="setTimePart(slot, tf.key, 'period', 'PM')"
                                                                class="w-full py-2 rounded-lg text-xs font-black uppercase transition-colors"
                                                                :class="parseTimeParts(slot[tf.key]).period === 'PM' ? 'bg-themeGreen text-white' : 'bg-gray-50 dark:bg-themeDark text-gray-600 dark:text-gray-300'">PM</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" @click="activeTimeKey = null"
                                                    class="w-full py-1.5 rounded-lg bg-themeBlue/10 text-themeBlue text-[10px] font-black uppercase tracking-wider hover:bg-themeBlue/20">
                                                Done
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Advanced Grace / Debounce Settings -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1 ml-0.5">Early Grace (Min)</label>
                                    <input type="number" min="0" max="180" x-model.number="slot.early_grace_minutes"
                                           class="w-full h-9 px-3 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-themeNavy text-xs font-mono font-bold text-gray-700 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1 ml-0.5">Double-Tap Debounce (Min)</label>
                                    <input type="number" min="0" max="60" x-model.number="slot.debounce_minutes"
                                           class="w-full h-9 px-3 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-themeNavy text-xs font-mono font-bold text-gray-700 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1 ml-0.5">Min Check-Out Gap (Min)</label>
                                    <input type="number" min="0" max="120" x-model.number="slot.min_checkout_gap_minutes"
                                           class="w-full h-9 px-3 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-themeNavy text-xs font-mono font-bold text-gray-700 dark:text-gray-200 focus:outline-none focus:border-themeBlue">
                                </div>
                            </div>

                            <!-- Class Checkboxes (Auto-Disables Classes Assigned to Other Slots) -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 ml-0.5">
                                        Assigned Classes <span class="text-rose-500">*</span>
                                        <span class="text-themeBlue ml-1" x-text="'(' + slot.class_ids.length + ' selected)'"></span>
                                    </label>
                                    <button type="button" @click="slot.class_ids = []" x-show="slot.class_ids.length > 0"
                                            class="text-[10px] font-black uppercase tracking-wider text-rose-500 hover:underline">
                                        Clear Slot Classes
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="cls in classes" :key="cls.id">
                                        <button type="button"
                                                @click="toggleSlotClass(sIdx, cls.id)"
                                                :disabled="isClassTakenByOtherSlot(sIdx, cls.id)"
                                                :title="isClassTakenByOtherSlot(sIdx, cls.id) ? ('Already assigned to ' + getClassOwnerSlotName(sIdx, cls.id)) : ('Toggle ' + cls.class_name)"
                                                class="px-3 py-1.5 rounded-xl text-xs font-bold border-2 transition-all flex items-center gap-1.5"
                                                :class="slot.class_ids.includes(cls.id)
                                                    ? 'bg-themeBlue text-white border-themeBlue shadow-sm'
                                                    : (isClassTakenByOtherSlot(sIdx, cls.id)
                                                        ? 'bg-gray-100 dark:bg-themeDark/40 text-gray-400 dark:text-gray-600 border-gray-200/70 dark:border-white/[0.04] cursor-not-allowed opacity-60'
                                                        : 'bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700 hover:border-themeBlue hover:text-themeBlue')">
                                            <span x-text="cls.class_name"></span>
                                            <template x-if="slot.class_ids.includes(cls.id)">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Optional Notes -->
                <div>
                    <label class="block text-[10px] font-black tracking-widest uppercase text-gray-500 dark:text-gray-400 mb-1.5 ml-1">
                        Operator Notes (Optional)
                    </label>
                    <textarea rows="2" x-model="form.notes" placeholder="Optional note about exam schedule or special timings..."
                              class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-themeDark text-xs font-semibold text-gray-800 dark:text-gray-100 focus:outline-none focus:border-themeBlue focus:ring-4 focus:ring-themeBlue/10 transition-all"></textarea>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 dark:border-white/[0.08] bg-gray-50/60 dark:bg-themeDark/40 flex items-center justify-end gap-3 shrink-0">
                <button type="button" @click="closeModal()" :disabled="saving"
                        class="px-5 h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-themeNavy text-gray-600 dark:text-gray-300 text-xs font-black uppercase tracking-[0.14em] hover:border-gray-400 transition-all">
                    Cancel
                </button>
                <button type="button" @click="submitForm()" :disabled="saving"
                        class="inline-flex items-center justify-center gap-2 px-6 h-11 rounded-xl bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black uppercase tracking-[0.16em] shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 disabled:opacity-50 transition-all">
                    <span x-text="saving ? 'Saving...' : (editingId ? 'Save Changes' : 'Create Campaign')"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    function attendanceScheduleManager() {
        const normalSchedule = @json($normalSchedule);
        const examTemplate = @json($examTemplate);
        const examCampaigns = @json($examCampaigns);
        const classesList = @json($classesData);
        const branchesList = @json($branchesData);
        const examsList = @json($examsData);
        const todayDate = @json($todayDate);

        const csrfHeaders = () => ({
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        let slotKeySeq = 1;

        const cloneSlots = (slots = [], keepIds = false) => slots.map(s => ({
            _key: 'slot_' + (slotKeySeq++),
            id: keepIds ? (s.id || null) : null,
            slot_name: s.slot_name || '',
            shift_group: s.shift_group || 'Morning',
            on_time_start: s.on_time_start || '08:00:00',
            on_time_end: s.on_time_end || '09:00:00',
            late_end: s.late_end || '09:10:00',
            absent_from: s.absent_from || '09:11:00',
            check_out_from: s.check_out_from || '11:00:00',
            early_grace_minutes: s.early_grace_minutes ?? 60,
            debounce_minutes: s.debounce_minutes ?? 10,
            min_checkout_gap_minutes: s.min_checkout_gap_minutes ?? 15,
            class_ids: Array.isArray(s.class_ids) ? [...s.class_ids.map(Number)] : []
        }));

        return {
            classes: classesList,
            branches: branchesList,
            exams: examsList,
            campaigns: examCampaigns,
            normalSchedule: normalSchedule,
            examTemplate: examTemplate,

            modalOpen: false,
            modalTitle: '',
            modalSubtitle: '',
            editingId: null,
            saving: false,
            formErrors: [],

            activeDropdown: null,
            activePicker: null,
            activeTimeKey: null,
            calYear: parseInt(todayDate.split('-')[0], 10),
            calMonth: parseInt(todayDate.split('-')[1], 10) - 1,
            calMonthOpen: false,
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            hours12: [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            minutes60: Array.from({ length: 60 }, (_, i) => i),
            timeFields: [
                { key: 'on_time_start', label: 'On-Time Start' },
                { key: 'on_time_end', label: 'On-Time End' },
                { key: 'late_end', label: 'Late Until' },
                { key: 'absent_from', label: 'Absent From' },
                { key: 'check_out_from', label: 'Check-Out From' }
            ],

            form: {
                name: '',
                mode: 'exam',
                branch_id: null,
                exam_id: null,
                start_date: todayDate,
                end_date: todayDate,
                priority: 20,
                is_active: true,
                notes: '',
                slots: []
            },

            init() {},

            openCreateExamModal() {
                this.editingId = null;
                this.formErrors = [];
                this.activeDropdown = null;
                this.activePicker = null;
                this.activeTimeKey = null;
                this.modalTitle = 'Create Exam Time Campaign';
                this.modalSubtitle = 'Pre-filled from the Standard Exam Time Template. Adjust dates, timings, or uncheck classes for partial tests.';

                const templateSlots = (this.examTemplate && this.examTemplate.slots && this.examTemplate.slots.length > 0)
                    ? cloneSlots(this.examTemplate.slots, false)
                    : [{
                        _key: 'slot_' + (slotKeySeq++),
                        id: null,
                        slot_name: 'Morning Exam',
                        shift_group: 'Morning',
                        on_time_start: '08:00:00',
                        on_time_end: '09:00:00',
                        late_end: '09:10:00',
                        absent_from: '09:11:00',
                        check_out_from: '09:40:00',
                        early_grace_minutes: 60,
                        debounce_minutes: 10,
                        min_checkout_gap_minutes: 15,
                        class_ids: []
                    }];

                this.form = {
                    name: '',
                    mode: 'exam',
                    branch_id: null,
                    exam_id: null,
                    start_date: todayDate,
                    end_date: todayDate,
                    priority: 20,
                    is_active: true,
                    notes: '',
                    slots: templateSlots
                };
                this.modalOpen = true;
            },

            openEditNormalModal() {
                if (!this.normalSchedule) return;
                this.editingId = this.normalSchedule.id;
                this.formErrors = [];
                this.activeDropdown = null;
                this.activePicker = null;
                this.activeTimeKey = null;
                this.modalTitle = 'Edit Normal School Time';
                this.modalSubtitle = 'Updates the default baseline schedule applied on all non-exam school days.';

                this.form = {
                    name: this.normalSchedule.name,
                    mode: 'normal',
                    branch_id: this.normalSchedule.branch_id,
                    exam_id: null,
                    start_date: null,
                    end_date: null,
                    priority: this.normalSchedule.priority ?? 10,
                    is_active: true,
                    notes: this.normalSchedule.notes || '',
                    slots: cloneSlots(this.normalSchedule.slots || [], true)
                };
                this.modalOpen = true;
            },

            openEditCampaignModal(campaignId) {
                const target = this.campaigns.find(c => Number(c.id) === Number(campaignId));
                if (!target) return;

                this.editingId = target.id;
                this.formErrors = [];
                this.activeDropdown = null;
                this.activePicker = null;
                this.activeTimeKey = null;
                this.modalTitle = 'Edit Exam Time Campaign';
                this.modalSubtitle = 'Modify effective dates, class assignments, or biometric punch windows for this exam campaign.';

                this.form = {
                    name: target.name,
                    mode: 'exam',
                    branch_id: target.branch_id,
                    exam_id: target.exam_id,
                    start_date: target.start_date || todayDate,
                    end_date: target.end_date || todayDate,
                    priority: target.priority ?? 20,
                    is_active: Boolean(target.is_active),
                    notes: target.notes || '',
                    slots: cloneSlots(target.slots || [], true)
                };
                this.modalOpen = true;
            },

            closeModal() {
                this.modalOpen = false;
                this.activeDropdown = null;
                this.activePicker = null;
                this.activeTimeKey = null;
            },

            addSlot() {
                this.form.slots.push({
                    _key: 'slot_' + (slotKeySeq++),
                    id: null,
                    slot_name: 'Custom Slot ' + (this.form.slots.length + 1),
                    shift_group: 'Day',
                    on_time_start: '11:30:00',
                    on_time_end: '12:00:00',
                    late_end: '12:10:00',
                    absent_from: '12:11:00',
                    check_out_from: '13:00:00',
                    early_grace_minutes: 60,
                    debounce_minutes: 10,
                    min_checkout_gap_minutes: 15,
                    class_ids: []
                });
            },

            removeSlot(idx) {
                if (this.form.slots.length <= 1) return;
                this.form.slots.splice(idx, 1);
            },

            isClassTakenByOtherSlot(currentSlotIdx, classId) {
                return this.form.slots.some((s, idx) => idx !== currentSlotIdx && s.class_ids.includes(classId));
            },

            getClassOwnerSlotName(currentSlotIdx, classId) {
                const owner = this.form.slots.find((s, idx) => idx !== currentSlotIdx && s.class_ids.includes(classId));
                return owner ? (owner.slot_name || 'another slot') : 'another slot';
            },

            toggleSlotClass(slotIdx, classId) {
                if (this.isClassTakenByOtherSlot(slotIdx, classId)) return;
                const list = this.form.slots[slotIdx].class_ids;
                const pos = list.indexOf(classId);
                if (pos >= 0) {
                    list.splice(pos, 1);
                } else {
                    list.push(classId);
                }
            },

            selectedBranchLabel() {
                if (this.form.branch_id === null || this.form.branch_id === '') return 'All Branches (Global)';
                const found = this.branches.find(b => Number(b.id) === Number(this.form.branch_id));
                return found ? found.branch_name : 'All Branches (Global)';
            },

            selectedExamLabel() {
                if (this.form.exam_id === null || this.form.exam_id === '') return 'No Linked Exam';
                const found = this.exams.find(e => Number(e.id) === Number(this.form.exam_id));
                return found ? (found.name + (found.session_name ? ' (' + found.session_name + ')' : '')) : 'No Linked Exam';
            },

            selectExam(exam) {
                this.form.exam_id = exam.id;
                if (!this.form.name.trim()) {
                    this.form.name = exam.name + (exam.session_name ? ' ' + exam.session_name : '');
                }
                if (exam.start_date) {
                    this.form.start_date = exam.start_date;
                }
                if (exam.end_date) {
                    this.form.end_date = exam.end_date;
                }
                this.activeDropdown = null;
            },

            // Custom Date Picker helpers (Rule 10)
            openDatePicker(field) {
                if (this.activePicker === field) {
                    this.activePicker = null;
                    return;
                }
                const currentVal = this.form[field] || todayDate;
                const parts = currentVal.split('-');
                if (parts.length === 3) {
                    this.calYear = parseInt(parts[0], 10) || this.calYear;
                    this.calMonth = Math.max(0, Math.min(11, (parseInt(parts[1], 10) || 1) - 1));
                }
                this.calMonthOpen = false;
                this.activePicker = field;
            },

            shiftCalMonth(delta) {
                let m = this.calMonth + delta;
                if (m < 0) {
                    this.calMonth = 11;
                    this.calYear--;
                } else if (m > 11) {
                    this.calMonth = 0;
                    this.calYear++;
                } else {
                    this.calMonth = m;
                }
            },

            getCalendarDays() {
                const year = parseInt(this.calYear, 10) || 2026;
                const month = this.calMonth;
                const firstDay = new Date(year, month, 1).getDay();
                const totalDays = new Date(year, month + 1, 0).getDate();
                const cells = [];

                for (let i = 0; i < firstDay; i++) {
                    cells.push({ day: '', dateStr: '' });
                }
                for (let d = 1; d <= totalDays; d++) {
                    const mm = String(month + 1).padStart(2, '0');
                    const dd = String(d).padStart(2, '0');
                    cells.push({ day: d, dateStr: `${year}-${mm}-${dd}` });
                }
                return cells;
            },

            pickCalendarDay(field, day) {
                if (!day) return;
                const year = parseInt(this.calYear, 10) || 2026;
                const mm = String(this.calMonth + 1).padStart(2, '0');
                const dd = String(day).padStart(2, '0');
                this.form[field] = `${year}-${mm}-${dd}`;
                this.activePicker = null;
            },

            formatDateDisplay(dateStr) {
                if (!dateStr) return 'Choose Date';
                const parts = String(dateStr).split('-');
                if (parts.length !== 3) return dateStr;
                const mIdx = parseInt(parts[1], 10) - 1;
                const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return `${parseInt(parts[2], 10)} ${shortMonths[mIdx] || ''}, ${parts[0]}`;
            },

            // Custom Time Picker helpers (Rule 10)
            openTimePicker(slot, fieldKey) {
                const key = slot._key + ':' + fieldKey;
                this.activeTimeKey = this.activeTimeKey === key ? null : key;
            },

            parseTimeParts(timeStr) {
                const parts = String(timeStr || '08:00:00').split(':');
                let h24 = parseInt(parts[0], 10) || 0;
                const minute = parseInt(parts[1], 10) || 0;
                const period = h24 >= 12 ? 'PM' : 'AM';
                let hour = h24 % 12;
                if (hour === 0) hour = 12;
                return { hour, minute, period };
            },

            setTimePart(slot, fieldKey, part, val) {
                const cur = this.parseTimeParts(slot[fieldKey]);
                cur[part] = val;
                let h24 = cur.hour % 12;
                if (cur.period === 'PM') h24 += 12;
                slot[fieldKey] = `${String(h24).padStart(2, '0')}:${String(cur.minute).padStart(2, '0')}:00`;
            },

            formatTime12h(timeStr) {
                if (!timeStr) return '—';
                const { hour, minute, period } = this.parseTimeParts(timeStr);
                return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')} ${period}`;
            },

            async submitForm() {
                this.formErrors = [];
                this.saving = true;

                const payload = {
                    name: this.form.name,
                    mode: this.form.mode,
                    branch_id: this.form.branch_id || null,
                    exam_id: this.form.mode === 'exam' ? (this.form.exam_id || null) : null,
                    start_date: this.form.mode === 'exam' ? this.form.start_date : null,
                    end_date: this.form.mode === 'exam' ? this.form.end_date : null,
                    priority: this.form.priority,
                    is_active: this.form.is_active,
                    notes: this.form.notes || null,
                    slots: this.form.slots.map(s => ({
                        id: s.id || null,
                        slot_name: s.slot_name,
                        shift_group: s.shift_group,
                        on_time_start: s.on_time_start,
                        on_time_end: s.on_time_end,
                        late_end: s.late_end,
                        absent_from: s.absent_from,
                        check_out_from: s.check_out_from,
                        early_grace_minutes: s.early_grace_minutes,
                        debounce_minutes: s.debounce_minutes,
                        min_checkout_gap_minutes: s.min_checkout_gap_minutes,
                        class_ids: s.class_ids
                    }))
                };

                try {
                    const url = this.editingId
                        ? `/attendance/schedules/${this.editingId}`
                        : '/attendance/schedules';
                    const res = this.editingId
                        ? await axios.put(url, payload, csrfHeaders())
                        : await axios.post(url, payload, csrfHeaders());

                    this.closeModal();
                    await showSuccess(res.data.message || 'Schedule saved successfully.');
                    window.location.reload();
                } catch (err) {
                    if (err.response && err.response.status === 422) {
                        const data = err.response.data || {};
                        if (data.errors) {
                            this.formErrors = Object.values(data.errors).flat();
                        } else if (data.message) {
                            this.formErrors = [data.message];
                        }
                    } else {
                        const msg = err.response?.data?.message || 'Failed to save schedule.';
                        await showAlert(msg, 'Save Failed');
                    }
                } finally {
                    this.saving = false;
                }
            },

            async toggleCampaignStatus(id, currentlyActive, name) {
                const actionLabel = currentlyActive ? 'Deactivate' : 'Activate';
                const confirmed = await showConfirm(
                    `${actionLabel} Exam Campaign`,
                    `Are you sure you want to ${actionLabel.toLowerCase()} "${name}"?`
                );
                if (!confirmed) return;

                try {
                    const res = await axios.post(`/attendance/schedules/${id}/toggle`, {}, csrfHeaders());
                    await showSuccess(res.data.message || 'Campaign status updated.');
                    window.location.reload();
                } catch (err) {
                    await showAlert(err.response?.data?.message || 'Failed to update campaign status.', 'Error');
                }
            },

            async deleteCampaign(id, name) {
                const confirmed = await showDanger(
                    'Delete Exam Campaign',
                    `Delete "${name}"? Historical attendance records will retain their saved snapshot text.`
                );
                if (!confirmed) return;

                try {
                    const res = await axios.delete(`/attendance/schedules/${id}`, csrfHeaders());
                    await showSuccess(res.data.message || 'Campaign deleted successfully.');
                    window.location.reload();
                } catch (err) {
                    await showAlert(err.response?.data?.message || 'Failed to delete campaign.', 'Error');
                }
            }
        };
    }
</script>
@endpush
