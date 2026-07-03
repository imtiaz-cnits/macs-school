@extends('tyro-dashboard::layouts.admin')

@section('title', 'Staff Attendance')

@section('content')
<div x-data="staffAttendance()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Staff Attendance
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Biometric card/fingerprint attendance records synchronization and status checks</p>
        </div>
    </div>

    <!-- Metrics Cards & Connection Status -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Present Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">Present</div>
            <div class="text-3xl font-black text-themeGreen">{{ $totalPresent }} <span class="text-xs font-semibold text-gray-400">staff</span></div>
        </div>

        <!-- Late Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">Late</div>
            <div class="text-3xl font-black text-amber-500">{{ $totalLate }} <span class="text-xs font-semibold text-gray-400">staff</span></div>
        </div>

        <!-- Absent Card -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">Absent</div>
            <div class="text-3xl font-black text-red-500">{{ $totalAbsent }} <span class="text-xs font-semibold text-gray-400">staff</span></div>
        </div>

        <!-- Biometric Connection Status -->
        <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-between">
            <div>
                <div class="text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">ZKTeco K60 Connection</div>
                <div class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $connection['ip'] }}</div>
                <div class="text-[9px] font-bold text-gray-500 mt-0.5">{{ $connection['message'] }}</div>
            </div>
            <div>
                <span class="inline-flex h-3 w-3 rounded-full {{ $connection['status'] === 'Connected' ? 'bg-green-500 animate-pulse' : ($connection['status'] === 'Simulated' ? 'bg-themeBlue animate-pulse' : 'bg-red-500') }}"></span>
            </div>
        </div>
    </div>

    <!-- Filters & Actions Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 mb-8">
        <form action="{{ route('staff-attendance.index') }}" method="GET" class="flex flex-col lg:flex-row gap-6 items-end">
            <!-- Search Database -->
            <div class="flex-grow w-full lg:w-auto">
                <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Search Staff</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Enter Name or Employee ID..." class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 pl-11 placeholder-gray-400">
                </div>
            </div>

            <!-- Custom Date Picker Component -->
            <div class="relative w-full lg:w-48" x-data="datePicker('{{ $date }}')" @date-selected.window="if($event.detail) { $el.closest('form').submit(); }" @click.away="show = false">
                <label class="block text-[10px] font-black text-gray-550 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Attendance Date</label>
                <input type="hidden" name="date" :value="value">
                <button type="button" @click="show = !show" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-xs font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                    <span class="truncate" x-text="formatDisplay(value)"></span>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                
                <!-- Calendar Dropdown panel -->
                <div x-show="show" x-cloak class="absolute right-0 z-50 mt-1.5 w-64 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl p-3" x-transition>
                    <div class="flex items-center justify-between mb-2">
                        <button type="button" @click="prevMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider" x-text="monthNames[currentMonth] + ' ' + currentYear"></span>
                        <button type="button" @click="nextMonth()" class="p-1 hover:bg-gray-50 dark:hover:bg-themeDark/45 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5 text-gray-550" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    
                    <!-- Days header -->
                    <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-widest mb-1">
                        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    
                    <!-- Days grid -->
                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="(d, i) in days" :key="i">
                            <button type="button" @click="selectDay(d.day)" 
                                    class="h-7 w-7 text-[10px] font-bold rounded-lg flex items-center justify-center transition-all"
                                    :class="d.day === parseInt(value.split('-')[2]) && d.isCurrentMonth ? 'bg-themeBlue text-white font-black shadow-sm' : d.isCurrentMonth ? 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-themeDark/45' : 'text-transparent cursor-default'"
                                    :disabled="!d.isCurrentMonth">
                                <span x-text="d.day"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Sync & Simulation Action buttons -->
            <div class="flex gap-3 shrink-0 w-full lg:w-auto">
                <button type="submit" class="flex-1 lg:flex-none h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black px-6 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest active:scale-95 flex items-center justify-center">
                    Filter Logs
                </button>
                <button type="button" @click="triggerSync()" :disabled="syncing" class="flex-1 lg:flex-none h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white text-xs font-black px-6 rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all uppercase tracking-widest active:scale-95 flex items-center justify-center disabled:opacity-50">
                    <span x-text="syncing ? 'Syncing...' : 'Sync Logs'"></span>
                </button>
                <button type="button" @click="triggerSimulation()" :disabled="simulating" class="flex-1 lg:flex-none h-11 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-650 dark:text-gray-300 text-xs font-black px-6 rounded-xl transition-all uppercase tracking-widest active:scale-95 flex items-center justify-center disabled:opacity-50">
                    <span x-text="simulating ? 'Simulating...' : 'Simulate Logs'"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Data Logs List Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center w-20">SL</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] w-24">Avatar</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em]">Personnel Profile</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Check-In</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Check-Out</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-center">Status</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-550 uppercase tracking-[0.2em] text-right">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">{{ $index + 1 }}</td>
                        <td class="py-4 px-4">
                            <div class="relative w-11 h-11 rounded-xl overflow-hidden border border-gray-100 dark:border-white/[0.06] shadow-sm">
                                <img src="{{ $log->teacher->photo ? '/storage/' . $log->teacher->photo : 'https://ui-avatars.com/api/?name=' . urlencode($log->teacher->user->name ?? 'N A') . '&background=008ED6&color=fff&bold=true' }}" alt="{{ $log->teacher->user->name ?? 'Staff' }}" class="w-full h-full object-cover">
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $log->teacher->user->name ?? 'Unknown Staff' }}</div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider">ID: {{ $log->teacher->employee_id }}</span>
                                <span class="text-gray-300 dark:text-gray-700">•</span>
                                <span class="text-[9px] font-black text-themeBlue uppercase tracking-wider">Bio ID: {{ $log->teacher->biometric_id ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-center text-sm font-bold text-gray-800 dark:text-gray-250">
                            {{ $log->formatted_in }}
                        </td>
                        <td class="py-4 px-4 text-center text-sm font-bold text-gray-800 dark:text-gray-250">
                            {{ $log->formatted_out }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 text-xs font-black rounded-full {{ $log->status === 'Present' ? 'bg-green-100 text-green-700 dark:bg-green-950/20 dark:text-green-400' : ($log->status === 'Late' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-950/20 dark:text-red-400') }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $log->remarks ?? '--' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-sm font-bold text-gray-400 uppercase tracking-widest">
                            No biometric logs synced for {{ date('d M, Y', strtotime($date)) }} yet. Try running sync or simulation.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    function datePicker(initialValue = '') {
        return {
            show: false,
            value: initialValue,
            currentYear: new Date(initialValue || new Date()).getFullYear(),
            currentMonth: new Date(initialValue || new Date()).getMonth(),
            days: [],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            
            init() {
                this.generateCalendar();
                this.$watch('value', val => {
                    if (val) {
                        const d = new Date(val);
                        this.currentYear = d.getFullYear();
                        this.currentMonth = d.getMonth();
                        this.generateCalendar();
                    }
                });
            },
            
            generateCalendar() {
                const firstDayIndex = new Date(this.currentYear, this.currentMonth, 1).getDay();
                const totalDays = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                
                const days = [];
                for (let i = 0; i < firstDayIndex; i++) {
                    days.push({ day: '', isCurrentMonth: false });
                }
                for (let i = 1; i <= totalDays; i++) {
                    days.push({ day: i, isCurrentMonth: true });
                }
                this.days = days;
            },
            
            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
                this.generateCalendar();
            },
            
            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
                this.generateCalendar();
            },
            
            selectDay(day) {
                if (!day) return;
                const formattedMonth = String(this.currentMonth + 1).padStart(2, '0');
                const formattedDay = String(day).padStart(2, '0');
                this.value = `${this.currentYear}-${formattedMonth}-${formattedDay}`;
                this.show = false;
                this.$dispatch('date-selected', this.value);
            },
            
            formatDisplay(val) {
                if (!val) return 'Select Date';
                const d = new Date(val);
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        }
    }

    function staffAttendance() {
        return {
            syncing: false,
            simulating: false,

            async triggerSync() {
                this.syncing = true;
                try {
                    const res = await axios.post("{{ route('staff-attendance.sync') }}", {
                        _token: "{{ csrf_token() }}",
                        date: "{{ $date }}"
                    });
                    
                    if (res.data.success) {
                        await showSuccess(res.data.message || "Attendance logs synced successfully.");
                        window.location.reload();
                    }
                } catch (err) {
                    const msg = err.response?.data?.message || "Failed to establish biometric socket handshake.";
                    showAlert(msg, "Biometric Machine Offline");
                } finally {
                    this.syncing = false;
                }
            },

            async triggerSimulation() {
                this.simulating = true;
                try {
                    const res = await axios.post("{{ route('staff-attendance.simulate') }}", {
                        _token: "{{ csrf_token() }}",
                        date: "{{ $date }}"
                    });

                    if (res.data.success) {
                        await showSuccess(res.data.message || "Simulated staff check-in/out records generated.");
                        window.location.reload();
                    }
                } catch (err) {
                    showAlert("Failed to run biometric simulator.", "Simulation Error");
                } finally {
                    this.simulating = false;
                }
            }
        };
    }
</script>
@endpush
