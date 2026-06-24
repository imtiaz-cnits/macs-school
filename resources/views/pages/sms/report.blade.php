@extends('tyro-dashboard::layouts.admin')

@section('title', 'SMS Delivery Report')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }</script>
<style>
    .smart-table-container { @apply overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm; }
    .smart-table { @apply w-full text-left border-collapse; }
    .smart-table th { @apply bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-bold text-[11px] uppercase tracking-wider py-4 px-6 border-b border-gray-200 dark:border-gray-700; }
    .smart-table td { @apply py-4 px-6 text-sm text-gray-800 dark:text-gray-200 border-b border-gray-100 dark:border-gray-700 align-top; }
    .smart-table tbody tr:hover { @apply bg-green-50/50 dark:bg-gray-800/50 transition-colors duration-200; }
    
    .status-badge { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold capitalize; }
    .status-sent { @apply bg-green-100 text-green-800 border border-green-200; }
    .status-failed { @apply bg-red-100 text-red-800 border border-red-200; }
    
    /* Widget Styles */
    .stat-card { @apply bg-white dark:bg-gray-800 rounded-[1.5rem] p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-5 transition-transform hover:-translate-y-1; }
    .stat-icon-box { @apply w-14 h-14 rounded-2xl flex items-center justify-center shrink-0; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-10 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">SMS Delivery Report</h1>
            <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Track & Monitor SMS Usage</p>
        </div>
        
        <div class="flex gap-3">
            <a href="{{ route('sms.general-notice') }}" class="bg-[#1e4630] hover:bg-green-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition-all text-xs uppercase tracking-wider flex items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Send New SMS
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        
        <div class="stat-card">
            <div class="stat-icon-box bg-blue-50 text-blue-500 border border-blue-100">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Sent Today</p>
                <h3 class="text-2xl font-black text-gray-800 dark:text-white">{{ number_format($todaySent) }}</h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box bg-purple-50 text-purple-500 border border-purple-100">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">This Month</p>
                <h3 class="text-2xl font-black text-gray-800 dark:text-white">{{ number_format($monthSent) }}</h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box bg-orange-50 text-orange-500 border border-orange-100">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Used</p>
                <h3 class="text-2xl font-black text-gray-800 dark:text-white">{{ number_format($totalSent) }}</h3>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon-box {{ $remainingBalance < 500 ? 'bg-red-50 text-red-500 border-red-100' : 'bg-green-50 text-[#1e4630] border-green-100' }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">SMS Balance</p>
                <h3 class="text-2xl font-black {{ $remainingBalance < 500 ? 'text-red-500' : 'text-[#1e4630]' }}">
                    {{ number_format($remainingBalance) }}
                </h3>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-xl p-6 border border-gray-100 dark:border-gray-800">
        
        @if($logs->isEmpty())
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">No SMS Logs Found</h3>
                <p class="text-sm text-gray-500">You haven't sent any messages yet.</p>
            </div>
        @else
            <div class="smart-table-container">
                <table class="smart-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Date & Time</th>
                            <th style="width: 25%;">Recipient Info</th>
                            <th style="width: 15%;">Mobile Number</th>
                            <th style="width: 35%;">Message Content</th>
                            <th style="width: 10%; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td>
                                <div class="font-bold text-[#1e4630] dark:text-green-400">{{ \Carbon\Carbon::parse($log->created_at)->format('d M, Y') }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</div>
                            </td>
                            <td>
                                @if($log->student)
                                    <div class="font-bold text-gray-900 dark:text-white uppercase">{{ $log->student->student_name ?? $log->student->first_name }}</div>
                                    <div class="text-xs text-gray-500 mt-1"><span class="font-semibold text-gray-400">ID:</span> {{ $log->student->student_identity }}</div>
                                @else
                                    <div class="text-gray-500 italic text-sm">Unknown / Deleted Student</div>
                                @endif
                            </td>
                            <td>
                                <div class="font-bold text-gray-800 dark:text-gray-200">{{ $log->mobile_number }}</div>
                            </td>
                            <td>
                                <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
                                    {{ $log->message }}
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <span class="status-badge {{ strtolower($log->status) == 'sent' ? 'status-sent' : 'status-failed' }}">
                                    @if(strtolower($log->status) == 'sent')
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    @endif
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