@extends('tyro-dashboard::layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                fontFamily: {
                    sans: ['Figtree', 'Onest', 'sans-serif'],
                },
                colors: { 
                    themeGreen: '#1e4630', 
                    themeIndigo: '#4f46e5', 
                    themePink: '#d97782' 
                } 
            } 
        } 
    }
</script>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    <div class="mb-10">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Welcome, {{ Auth::user()->name }}</h1>
        <p class="text-xs text-gray-500 font-bold uppercase tracking-[0.1em] mt-1">Pabna International School Management System</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-10">
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-5">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 text-themeGreen rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Students</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $totalStudents }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-5">
            <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Class</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $totalClasses }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-5">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-themeIndigo rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Teachers</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $totalTeachers }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-5">
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sections</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $totalSections }}</h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-5">
            <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/30 text-themePink rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Branches</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ $totalBranches }}</h3>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-50 dark:border-gray-700">
            <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-tighter">Recent Admissions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Student</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Identity</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Class</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    @foreach($recentStudents as $student)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/20 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $student->student_name }}</td>
                        <td class="px-6 py-4 font-mono text-sm text-themeIndigo font-black">{{ $student->student_identity }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-bold">{{ $student->schoolClass->class_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500 font-bold uppercase">{{ $student->created_at->format('d M, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-12 text-center">
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em]">Powered by <span class="text-themeGreen font-bold">Code Next IT</span></p>
    </div>
</div>
@endsection