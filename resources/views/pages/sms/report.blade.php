@extends('tyro-dashboard::layouts.admin')

@section('title', 'SMS Delivery Report')

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                SMS Delivery Report
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Monitor, filter, and review text message delivery status and balance metrics</p>
        </div>
        
        <a href="{{ route('sms.general-notice') }}" class="h-11 px-6 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest flex items-center justify-center gap-2 whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Send New SMS
        </a>
    </div>

    <!-- Metrics row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Sent Today -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">Sent Today</div>
            <div class="text-2xl font-black text-themeBlue">{{ number_format($todaySent) }} <span class="text-xs font-semibold text-gray-400">sms</span></div>
        </div>

        <!-- This Month -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">This Month</div>
            <div class="text-2xl font-black text-purple-500">{{ number_format($monthSent) }} <span class="text-xs font-semibold text-gray-400">sms</span></div>
        </div>

        <!-- Total Used -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">Total Used</div>
            <div class="text-2xl font-black text-amber-500">{{ number_format($totalSent) }} <span class="text-xs font-semibold text-gray-400">sms</span></div>
        </div>

        <!-- SMS Balance -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-widest mb-1">SMS Balance</div>
            <div class="text-2xl font-black {{ $remainingBalance < 500 ? 'text-red-500' : 'text-themeGreen' }}">
                {{ number_format($remainingBalance) }} <span class="text-xs font-semibold text-gray-400">sms</span>
            </div>
        </div>
    </div>

    <!-- Data Logs List Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        
        @if($logs->isEmpty())
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">No SMS Logs Found</h3>
                <p class="text-sm text-gray-500">You haven't sent any messages yet.</p>
            </div>
        @else
            <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
                <table class="w-full text-left border-collapse table">
                    <thead>
                        <tr class="!bg-transparent">
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-36">Date & Time</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Recipient Info</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] w-36">Mobile Number</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Message Content</th>
                            <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-28">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                            <td class="py-4 px-4 align-top">
                                <div class="font-bold text-gray-800 dark:text-gray-250 text-sm">{{ \Carbon\Carbon::parse($log->created_at)->format('d M, Y') }}</div>
                                <div class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase mt-1">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</div>
                            </td>
                            <td class="py-4 px-4 align-top">
                                @if($log->student)
                                    <div class="font-bold text-gray-900 dark:text-gray-100 text-sm uppercase">{{ $log->student->student_name ?? $log->student->first_name }}</div>
                                    <div class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase mt-1">ID: {{ $log->student->student_identity }}</div>
                                @else
                                    <div class="text-gray-450 dark:text-gray-500 italic text-sm font-semibold">Unknown / Deleted Student</div>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-top text-sm font-mono font-black text-gray-650 dark:text-gray-400">
                                {{ $log->mobile_number }}
                            </td>
                            <td class="py-4 px-4 align-top">
                                <div class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50/50 dark:bg-themeDark p-3 rounded-xl border border-gray-100 dark:border-white/[0.05]">
                                    {{ $log->message }}
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center align-top whitespace-nowrap">
                                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block {{ strtolower($log->status) == 'sent' ? 'bg-green-50 dark:bg-green-950/20 text-themeGreen dark:text-green-400' : 'bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 flex justify-end">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection