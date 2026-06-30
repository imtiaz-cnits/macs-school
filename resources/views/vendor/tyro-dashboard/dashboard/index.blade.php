@extends('tyro-dashboard::layouts.admin')

@section('title', 'Dashboard')

@section('breadcrumb')
<span>Command Center</span>
@endsection

@section('content')
<!-- Header Section -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
            <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            Command Center
        </h1>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Welcome back, {{ Auth::user()->name }}. Here's the daily school status summary.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('student.admission') }}" class="btn inline-flex items-center gap-2 bg-gradient-to-r from-themeBlue to-themeGreen text-white border-none rounded-2xl py-2.5 px-5 text-xs font-black tracking-wider uppercase hover:-translate-y-0.5 hover:shadow-lg transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Add Student
        </a>
        <a href="{{ route('teacher.add') }}" class="btn inline-flex items-center gap-2 bg-white dark:bg-themeNavy text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-white/[0.08] rounded-2xl py-2 px-4 text-xs font-black tracking-wider uppercase hover:-translate-y-0.5 hover:shadow-md transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Add Teacher
        </a>
    </div>
</div>

<!-- Premium Greeting & Info Card -->
<div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
        <!-- Greetings and Calendars -->
        <div class="lg:col-span-8 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <span id="greeting-label" class="text-[10px] font-black tracking-widest text-amber-500 uppercase">GOOD AFTERNOON</span>
                    <h2 id="greeting-text" class="text-xl font-extrabold text-gray-800 dark:text-gray-150">Good Afternoon, {{ Auth::user()->name }}</h2>
                </div>
            </div>

            <!-- Calendars Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-themeDark/50 rounded-2xl p-4 border border-gray-100/50 dark:border-white/[0.06]">
                    <div class="text-[9px] font-black tracking-widest text-gray-500 uppercase">ENGLISH CALENDAR</div>
                    <div class="text-sm font-bold text-gray-800 dark:text-gray-200 mt-1" id="english-date">Tuesday, June 30, 2026</div>
                </div>
                <div class="bg-gray-50 dark:bg-themeDark/50 rounded-2xl p-4 border border-gray-100/50 dark:border-white/[0.06]">
                    <div class="text-[9px] font-black tracking-widest text-gray-500 uppercase">BENGALI CALENDAR</div>
                    <div class="text-sm font-bold text-gray-800 dark:text-gray-200 mt-1">Tuesday, 16 Ashadh 1433</div>
                </div>
                <div class="bg-gray-50 dark:bg-themeDark/50 rounded-2xl p-4 border border-gray-100/50 dark:border-white/[0.06]">
                    <div class="text-[9px] font-black tracking-widest text-gray-500 uppercase">ACADEMIC YEAR</div>
                    <div class="text-sm font-bold text-themeBlue dark:text-themeBlue/90 mt-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-themeBlue animate-ping"></span>
                        Session: {{ date('Y') }}
                    </div>
                </div>
            </div>

            <!-- Day Progress -->
            <div class="space-y-1.5 pt-2">
                <div class="flex items-center justify-between text-xs font-bold text-gray-500 dark:text-gray-400">
                    <span>Day Progress</span>
                    <span id="day-progress-text">61.4%</span>
                </div>
                <div class="w-full h-2.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-themeBlue to-themeGreen transition-all duration-500" id="day-progress-bar" style="width: 61.4%;"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500">
                    <span>12 AM</span>
                    <span>12 PM</span>
                    <span>12 AM</span>
                </div>
            </div>
        </div>

        <!-- Clock Widget -->
        <div class="lg:col-span-4 bg-gray-50 dark:bg-themeDark/50 border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 text-center space-y-3 flex flex-col items-center justify-center min-h-[220px]">
            <span class="inline-flex items-center gap-1.5 bg-green-500/10 text-themeGreen dark:text-green-400 text-[10px] font-black tracking-widest uppercase py-1 px-3 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-themeGreen"></span>
                School Session
            </span>
            <div class="text-4xl font-black text-gray-800 dark:text-white font-mono tracking-tight" id="live-clock">
                02:43<span class="text-2xl text-gray-400 dark:text-gray-500">:35</span> <span class="text-lg text-themeBlue">PM</span>
            </div>
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                Day <span id="day-of-year" class="text-gray-600 dark:text-gray-300">181</span> of 2026 | Week <span id="week-of-year" class="text-gray-600 dark:text-gray-300">27</span>
            </div>
        </div>
    </div>
</div>

<!-- Metrics Grid (5 Column Layout) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <!-- Stat 1: Students -->
    <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
        <div>
            <div class="text-[10px] font-black tracking-widest text-gray-500 uppercase">Total Students</div>
            <div class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ number_format($totalStudents) }}</div>
            <div class="text-[10px] font-bold text-themeGreen dark:text-green-400 mt-0.5 flex items-center gap-1">
                <span>Active Roll</span>
            </div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-sky-50 dark:bg-sky-950/20 flex items-center justify-center text-themeBlue">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
    </div>

    <!-- Stat 2: Classes -->
    <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
        <div>
            <div class="text-[10px] font-black tracking-widest text-gray-500 uppercase">Active Classes</div>
            <div class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ number_format($totalClasses) }}</div>
            <div class="text-[10px] font-bold text-themeGreen dark:text-green-400 mt-0.5 flex items-center gap-1">
                <span>Standard Curricula</span>
            </div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/20 flex items-center justify-center text-themeGreen">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
            </svg>
        </div>
    </div>

    <!-- Stat 3: Teachers -->
    <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
        <div>
            <div class="text-[10px] font-black tracking-widest text-gray-500 uppercase">Teachers</div>
            <div class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ number_format($totalTeachers) }}</div>
            <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5">Faculty staff</div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/20 flex items-center justify-center text-indigo-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
    </div>

    <!-- Stat 4: Sections -->
    <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
        <div>
            <div class="text-[10px] font-black tracking-widest text-gray-500 uppercase">Sections</div>
            <div class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ number_format($totalSections) }}</div>
            <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5">Class divisions</div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-950/20 flex items-center justify-center text-amber-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
        </div>
    </div>

    <!-- Stat 5: Branches -->
    <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
        <div>
            <div class="text-[10px] font-black tracking-widest text-gray-500 uppercase">Branches</div>
            <div class="text-2xl font-black text-gray-800 dark:text-white mt-1">{{ number_format($totalBranches) }}</div>
            <div class="text-[10px] font-bold text-themeGreen dark:text-green-400 mt-0.5">School campuses</div>
        </div>
        <div class="w-11 h-11 rounded-2xl bg-pink-50 dark:bg-pink-950/20 flex items-center justify-center text-pink-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
    </div>
</div>

<!-- Split Columns -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Column (Quran/Hadith Card) -->
    <div class="lg:col-span-6 space-y-6">
        <!-- Verse Card -->
        <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/[0.08] pb-3 mb-4">
                <div class="flex items-center gap-2 text-themeBlue">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="text-xs font-black tracking-widest uppercase">VERSE OF THE DAY</span>
                </div>
                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 flex items-center gap-1">
                    <span class="w-1 h-1 rounded-full bg-gray-400"></span>
                    Al-Baqarah 2:13
                </span>
            </div>
            
            <div class="space-y-4">
                <div class="text-right text-2xl font-semibold leading-loose text-gray-800 dark:text-gray-100 font-serif" dir="rtl">
                    وَإِذَا قِيلَ لَهُمْ آمِنُوا كَمَا آمَنَ النَّاسُ قَالُوا أَنُؤْمِنُ كَمَا آمَنَ السُّفَهَاءُ ۗ أَلَا إِنَّهُمْ هُمُ السُّفَهَاءُ وَلَٰكِنْ لَا يَعْلَمُونَ
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed pl-3 border-l-2 border-themeBlue/30">
                    And when it is said to them, "Believe as the people have believed," they say, "Should we believe as the foolish have believed?" Unquestionably, it is they who are the foolish, but they know [it] not.
                </div>
                <div class="flex justify-between items-center text-[10px] text-gray-400 dark:text-gray-500 pt-2">
                    <span>Surah: Al-Baqarah - Ayah: 13</span>
                </div>
            </div>
        </div>

        <!-- Hadith Card -->
        <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-white/[0.08] pb-3 mb-4">
                <div class="flex items-center gap-2 text-themeGreen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                    <span class="text-xs font-black tracking-widest uppercase">HADITH OF THE DAY</span>
                </div>
                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 flex items-center gap-1">
                    <span class="w-1 h-1 rounded-full bg-gray-400"></span>
                    Sunan Abi Dawud
                </span>
            </div>
            
            <div class="space-y-4">
                <div class="text-right text-2xl font-semibold leading-loose text-gray-800 dark:text-gray-100 font-serif" dir="rtl">
                    لاَ يَشْكُرُ اللَّهَ مَنْ لاَ يَشْكُرُ النَّاسَ
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed pl-3 border-l-2 border-themeGreen/30">
                    He who does not thank people, does not thank Allah.
                </div>
                <div class="flex justify-between items-center text-[10px] text-gray-400 dark:text-gray-500 pt-2">
                    <span>Source: Sunan Abi Dawud, 4811 • Sahih</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column (Recent Admissions Table) -->
    <div class="lg:col-span-6">
        <div class="card bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.08] rounded-3xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col h-full">
            <div class="p-6 border-b border-gray-100 dark:border-white/[0.08] flex justify-between items-center bg-gray-50/50 dark:bg-themeNavy/30">
                <h3 class="text-lg font-black text-gray-800 dark:text-white uppercase tracking-tighter">Recent Admissions</h3>
                <span class="text-[10px] font-black text-themeBlue bg-themeBlue/10 dark:bg-themeBlue/20 border border-themeBlue/10 px-4 py-1.5 rounded-full uppercase tracking-wider">Live Log</span>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/30 dark:bg-themeNavy/10">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.08]">Student Name</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.08]">Identity Number</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.08]">Assigned Class</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.08]">Admission Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/[0.06]">
                        @forelse($recentStudents as $student)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-gray-100">{{ $student->student_name }}</td>
                            <td class="px-6 py-4 font-mono text-sm text-themeBlue font-black">{{ $student->student_identity }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-bold">{{ $student->schoolClass->class_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 font-bold uppercase">{{ $student->created_at->format('d M, Y') }}</td>
                        </tr>
                        @empty
                        <tr class="dark:border-white/[0.06]">
                            <td colspan="4" class="px-6 py-8 text-center text-sm font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">No recent admissions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Centered Premium Footer -->
<div class="mt-16 pt-8 border-t border-gray-150 dark:border-white/[0.08] text-center">
    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-black uppercase tracking-[0.2em] flex items-center justify-center gap-2">
        <span>Powered by</span>
        <a href="https://www.codenextit.com" target="_blank" class="text-themeBlue hover:text-themeGreen font-bold transition-colors">
            Code Next IT
        </a>
    </p>
</div>

<script>
    // Live ticking clock widget logic
    function startClock() {
        const liveClock = document.getElementById('live-clock');
        if (!liveClock) return;

        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            const formattedHours = String(hours).padStart(2, '0');
            
            liveClock.innerHTML = `${formattedHours}:${minutes}<span class="text-2xl text-gray-400 dark:text-gray-500">:${seconds}</span> <span class="text-lg text-themeBlue">${ampm}</span>`;
            
            // Dynamic Day Progress calculation (24-hour baseline)
            const minutesPassed = now.getHours() * 60 + now.getMinutes();
            const progressPercent = ((minutesPassed / 1440) * 100).toFixed(1);
            
            const progressText = document.getElementById('day-progress-text');
            const progressBar = document.getElementById('day-progress-bar');
            if (progressText && progressBar) {
                progressText.textContent = `${progressPercent}%`;
                progressBar.style.width = `${progressPercent}%`;
            }
        }

        // Initialize details once
        updateClock();
        setInterval(updateClock, 1000);
        
        // Static day/week counts helper
        const now = new Date();
        const start = new Date(now.getFullYear(), 0, 1);
        const diff = now - start;
        const oneDay = 1000 * 60 * 60 * 24;
        const dayOfYear = Math.floor(diff / oneDay) + 1;
        const weekOfYear = Math.ceil(dayOfYear / 7);
        
        const dayEl = document.getElementById('day-of-year');
        const weekEl = document.getElementById('week-of-year');
        if (dayEl) dayEl.textContent = dayOfYear;
        if (weekEl) weekEl.textContent = weekOfYear;

        // Dynamic Greeting Translation & Icon based on hour of day
        const hr = now.getHours();
        const greetingText = document.getElementById('greeting-text');
        const greetingLabel = document.getElementById('greeting-label');
        
        if (greetingText && greetingLabel) {
            let label = "GOOD MORNING";
            let engText = "Good Morning";
            
            if (hr >= 12 && hr < 16) {
                label = "GOOD AFTERNOON";
                engText = "Good Afternoon";
            } else if (hr >= 16 && hr < 19) {
                label = "GOOD EVENING";
                engText = "Good Evening";
            } else if (hr >= 19 || hr < 4) {
                label = "GOOD NIGHT";
                engText = "Good Night";
            }
            
            greetingLabel.textContent = label;
            greetingText.innerHTML = `${engText}, {{ Auth::user()->name }}`;
        }
        
        // Dynamic English Calendar
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const englishDateEl = document.getElementById('english-date');
        if (englishDateEl) {
            englishDateEl.textContent = now.toLocaleDateString('en-US', options);
        }
    }

    document.addEventListener('DOMContentLoaded', startClock);
</script>
@endsection
